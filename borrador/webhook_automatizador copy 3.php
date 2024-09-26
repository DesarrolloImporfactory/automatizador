<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos."]);
    exit;
}

$id_configuracion = isset($data['id_configuracion']) ? (int)$data['id_configuracion'] : 0;
$value_blocks_type = isset($data['value_blocks_type']) ? $data['value_blocks_type'] : '';
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

function getAutomatizador($conn, $id_configuracion, $value_blocks_type) {
    $stmt = $conn->prepare("
        SELECT a.id, a.json_output
        FROM automatizadores a
        JOIN disparadores d ON a.id = d.id_automatizador
        WHERE a.id_configuracion = ? AND d.tipo = ?
        LIMIT 1
    ");
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('is', $id_configuracion, $value_blocks_type);
    $stmt->execute();
    $stmt->bind_result($id_automatizador, $json_output);
    $stmt->fetch();
    $stmt->close();

    return $id_automatizador ? ['id' => $id_automatizador, 'json_output' => $json_output] : null;
}

function getBlocksInfo($conn, $id_automatizador, $block_id) {
    $query = "
        SELECT id, block_id, 'disparadores' AS table_name, id, block_id, id_automatizador, tipo, productos, categorias, status, novedad, provincia, ciudad, created_at, updated_at, NULL as id_condicion, NULL as id_disparador, NULL as id_accion, NULL as id_whatsapp_message_template, NULL as asunto, NULL as mensaje, NULL as opciones, NULL as tiempo_envio, NULL as unidad_envio, NULL as tiempo_reenvio, NULL as unidad_reenvio, NULL as reenvios, NULL as cambiar_status, NULL as texto
        FROM disparadores WHERE id_automatizador = ? AND block_id = ?
        UNION ALL
        SELECT id, block_id, 'acciones' AS table_name, id, block_id, id_automatizador, tipo, NULL as productos, NULL as categorias, NULL as status, NULL as novedad, NULL as provincia, NULL as ciudad, created_at, updated_at, id_condicion, id_disparador, id_accion, id_whatsapp_message_template, asunto, mensaje, opciones, tiempo_envio, unidad_envio, tiempo_reenvio, unidad_reenvio, reenvios, cambiar_status, NULL as texto
        FROM acciones WHERE id_automatizador = ? AND block_id = ?
        UNION ALL
        SELECT id, block_id, 'condiciones' AS table_name, id, block_id, id_automatizador, 10 as tipo, NULL as productos, NULL as categorias, NULL as status, NULL as novedad, NULL as provincia, NULL as ciudad, created_at, updated_at, id_accion, id_condicion, id_disparador, NULL as id_whatsapp_message_template, NULL as asunto, NULL as mensaje, NULL as opciones, NULL as tiempo_envio, NULL as unidad_envio, NULL as tiempo_reenvio, NULL as unidad_reenvio, NULL as reenvios, NULL as cambiar_status, texto
        FROM condiciones WHERE id_automatizador = ? AND block_id = ?
    ";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('iiiiii', $id_automatizador, $block_id, $id_automatizador, $block_id, $id_automatizador, $block_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    $stmt->close();
    return null;
}

function getParentBlockInfo($conn, $id_automatizador, $parent_id) {
    if ($parent_id == -1) {
        return ['parent_id' => -1, 'parent_table' => null];
    }

    $query = "
        SELECT id, 'disparadores' AS table_name FROM disparadores WHERE id_automatizador = ? AND block_id = ?
        UNION ALL
        SELECT id, 'acciones' AS table_name FROM acciones WHERE id_automatizador = ? AND block_id = ?
        UNION ALL
        SELECT id, 'condiciones' AS table_name FROM condiciones WHERE id_automatizador = ? AND block_id = ?
    ";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('iiiiii', $id_automatizador, $parent_id, $id_automatizador, $parent_id, $id_automatizador, $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $stmt->close();
        return ['parent_id' => $data['id'], 'parent_table' => $data['table_name']];
    }

    $stmt->close();
    return ['parent_id' => null, 'parent_table' => null];
}

function getChildBlocks($blockarr, $parent_id) {
    $child_blocks = [];
    foreach ($blockarr as $block) {
        if ($block['parent'] == $parent_id) {
            $child_blocks[] = $block['id'];
        }
    }
    return $child_blocks;
}

function sortBlocksByHierarchy($blocks, $blockarr) {
    $sorted = [];
    $to_visit = [0]; // Comenzar con el bloque raíz

    while (!empty($to_visit)) {
        $current = array_shift($to_visit);
        foreach ($blocks as $block) {
            if ($block['block_id'] == $current) {
                $sorted[] = $block;
                $children = getChildBlocks($blockarr, $current);
                $to_visit = array_merge($children, $to_visit);
            }
        }
    }

    return $sorted;
}

function removeConditionsAndDescendants(&$blocks) {
    $blocks_map = [];
    foreach ($blocks as $block) {
        $blocks_map[$block['block_id']] = $block;
    }

    $to_remove = [];
    foreach ($blocks as $block) {
        if ($block['block_table'] === 'condiciones') {
            $to_remove[] = $block['block_id'];
        }
    }

    while (!empty($to_remove)) {
        $current = array_pop($to_remove);
        unset($blocks_map[$current]);
        foreach ($blocks_map as $block) {
            if ($block['parent_block_id'] == $current) {
                $to_remove[] = $block['block_id'];
            }
        }
    }

    return array_values($blocks_map);
}

function removeOrphanBlocks(&$blocks) {
    $blocks_map = [];
    foreach ($blocks as $block) {
        $blocks_map[$block['block_id']] = $block;
    }

    $valid_parents = array_column($blocks, 'block_id');
    $valid_parents[] = -1; // Allow root level block to exist without parent

    $filtered_blocks = array_filter($blocks, function($block) use ($valid_parents) {
        return in_array($block['parent_block_id'], $valid_parents);
    });

    return array_values($filtered_blocks);
}

function insertInteractions($conn, $block_details, $id_automatizador, $user_id) {
    $stmt = $conn->prepare("
        INSERT INTO `interacciones_usuarios`(`id`, `id_automatizador`, `tipo_interaccion`, `id_interaccion`, `uid_usuario`, `json_interaccion`, `created_at`, `updated_at`)
        VALUES (NULL, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }

    foreach ($block_details as $block) {
        $tipo_interaccion = $block['block_table'];
        $id_interaccion = $block['block_sql_id'];
        $json_interaccion = json_encode($block['block_sql_data']);

        $stmt->bind_param('issis', $id_automatizador, $tipo_interaccion, $id_interaccion, $user_id, $json_interaccion);
        $stmt->execute();
    }

    $stmt->close();
}

try {
    $automatizador = getAutomatizador($conn, $id_configuracion, $value_blocks_type);

    if ($automatizador === null) {
        throw new Exception("No se encontró un automatizador asociado a la configuración especificada y el tipo de bloque.");
    }

    $json_output = $automatizador['json_output'];
    $id_automatizador = $automatizador['id'];

    $json_data = json_decode($json_output, true);
    $blockarr = $json_data['blockarr'];

    $block_details = [];

    foreach ($blockarr as $block) {
        $block_info = getBlocksInfo($conn, $id_automatizador, $block['id']);
        if ($block_info) {
            $parent_info = getParentBlockInfo($conn, $id_automatizador, $block['parent']);
            $child_blocks = getChildBlocks($blockarr, $block['id']);
            $block_details[] = [
                'block_id' => $block['id'],
                'block_table' => $block_info['table_name'],
                'block_sql_id' => $block_info['id'],
                'block_sql_data' => $block_info,
                'parent_block_id' => $block['parent'],
                'parent_block_table' => $parent_info['parent_table'],
                'parent_block_sql_id' => $parent_info['parent_id'],
                'child_blocks' => $child_blocks
            ];
        }
    }

    $block_details = sortBlocksByHierarchy($block_details, $blockarr);
    $block_details = removeConditionsAndDescendants($block_details);
    $block_details = removeOrphanBlocks($block_details);

    // Insert interactions
    insertInteractions($conn, $block_details, $id_automatizador, $user_id);

    $response = [
        'id_configuracion' => $id_configuracion,
        'id_automatizador' => $id_automatizador,
        'block_details' => $block_details
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>