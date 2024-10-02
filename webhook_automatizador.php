<?php
require 'db.php';

header("Access-Control-Allow-Origin: *"); // Permite todos los orígenes
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); // Métodos permitidos
header("Access-Control-Allow-Headers: Content-Type"); // Cabeceras permitidas

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos."]);
    exit;
}

$id_configuracion = isset($data['id_configuracion']) ? (int)$data['id_configuracion'] : 0;
$value_blocks_type = isset($data['value_blocks_type']) ? $data['value_blocks_type'] : '';
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;

function getAutomatizador($conn, $id_configuracion, $value_blocks_type, $data)
{
    $id_automatizador = $json_output = $productos = $categorias = $status = $novedad = $provincia = $ciudad = "";
    $stmt = $conn->prepare("
        SELECT a.id, a.json_output, d.productos, d.categorias, d.status, d.novedad, d.provincia, d.ciudad
        FROM automatizadores a
        JOIN disparadores d ON a.id = d.id_automatizador
        WHERE a.id_configuracion = ? AND d.tipo = ?
    ");
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('is', $id_configuracion, $value_blocks_type);
    $stmt->execute();
    $stmt->bind_result($id_automatizador, $json_output, $productos, $categorias, $status, $novedad, $provincia, $ciudad);

    $selected_automatizador = null;

    while ($stmt->fetch()) {
        $productos_arr = json_decode($productos, true) ?? [];
        $categorias_arr = json_decode($categorias, true) ?? [];
        $status_arr = json_decode($status, true) ?? [];
        $novedad_arr = json_decode($novedad, true) ?? [];
        $provincia_arr = json_decode($provincia, true) ?? [];
        $ciudad_arr = json_decode($ciudad, true) ?? [];

        if (
            (empty($productos_arr) || in_array("0", $productos_arr) || !empty(array_intersect($data['productos'], $productos_arr))) &&
            (empty($categorias_arr) || in_array("0", $categorias_arr) || !empty(array_intersect($data['categorias'], $categorias_arr))) &&
            (empty($status_arr) || in_array("0", $status_arr) || !empty(array_intersect($data['status'], $status_arr))) &&
            (empty($novedad_arr) || in_array("0", $novedad_arr) || !empty(array_intersect($data['novedad'], $novedad_arr))) &&
            (empty($provincia_arr) || in_array("0", $provincia_arr) || !empty(array_intersect($data['provincia'], $provincia_arr))) &&
            (empty($ciudad_arr) || in_array("0", $ciudad_arr) || !empty(array_intersect($data['ciudad'], $ciudad_arr)))
        ) {
            $selected_automatizador = ['id' => $id_automatizador, 'json_output' => $json_output];
            break;
        }
    }

    $stmt->close();

    return $selected_automatizador;
}

function getBlocksInfo($conn, $id_automatizador, $block_id)
{
    // Extraer json_bloques de la tabla automatizadores
    $query_automatizador = "
        SELECT json_bloques
        FROM automatizadores
        WHERE id = ?
    ";
    $stmt_automatizador = $conn->prepare($query_automatizador);
    if ($stmt_automatizador === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt_automatizador->bind_param('i', $id_automatizador);
    $stmt_automatizador->execute();
    $result_automatizador = $stmt_automatizador->get_result();

    if ($result_automatizador->num_rows == 0) {
        throw new Exception("No se encontró el automatizador con el id especificado.");
    }

    $automatizador = $result_automatizador->fetch_assoc();
    $json_bloques = $automatizador['json_bloques'];
    $stmt_automatizador->close();

    // Decodificar el json_bloques
    $blocks = json_decode($json_bloques, true);
    //echo $block_id;
    // Buscar el bloque correspondiente en el JSON
    $block_data = null;
    foreach ($blocks as $block) {
        //echo json_encode($block);
        if ($block['id_block'] == $block_id) {
            //echo $block['id_block']." == ".$block_id." then ".json_encode($block);
            $block_data = $block;
            break;
        }
    }


    if ($block_data === null) {
        throw new Exception("No se encontró el bloque con el id especificado en el JSON.");
    }

    // Obtener información del bloque desde las tablas disparadores, acciones y condiciones
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

        // Rellenar los campos faltantes con los datos del JSON
        foreach ($block_data as $key => $value) {
            if (!array_key_exists($key, $data) || $data[$key] === null) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    $stmt->close();
    return null;
}

function getParentBlockInfo($conn, $id_automatizador, $parent_id)
{
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

function getChildBlocks($blockarr, $parent_id)
{
    $child_blocks = [];
    foreach ($blockarr as $block) {
        if ($block['parent'] == $parent_id) {
            $child_blocks[] = $block['id'];
        }
    }
    return $child_blocks;
}

function sortBlocksByHierarchy($blocks, $blockarr)
{
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

function removeConditionsAndDescendants(&$blocks)
{
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

function removeOrphanBlocks(&$blocks)
{
    $blocks_map = [];
    foreach ($blocks as $block) {
        $blocks_map[$block['block_id']] = $block;
    }

    $valid_parents = array_column($blocks, 'block_id');
    $valid_parents[] = -1; // Allow root level block to exist without parent

    $filtered_blocks = array_filter($blocks, function ($block) use ($valid_parents) {
        return in_array($block['parent_block_id'], $valid_parents);
    });

    return array_values($filtered_blocks);
}

function replacePlaceholders($text, $placeholders)
{
    foreach ($placeholders as $key => $value) {
        $text = str_replace("{{{$key}}}", $value, $text);
    }
    return $text;
}

function getWhatsappMessageTemplate($config)
{
    $url = 'https://graph.facebook.com/v20.0/' . $config['id_whatsapp'] . '/message_templates';
    $params = array(
        'access_token' => $config['token']
    );
    $url .= '?' . http_build_query($params);

    // Inicializar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // Ejecutar la solicitud cURL
    $response = curl_exec($ch);

    // Manejar errores de cURL
    if (curl_errno($ch)) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return array('error' => $error_msg);
    }

    // Cerrar cURL
    curl_close($ch);

    // Decodificar respuesta JSON
    return json_decode($response, true);
}

// Function to extract placeholders from the message
function extract_placeholders($mensaje)
{
    preg_match_all('/{{(.*?)}}/', $mensaje, $matches);
    return $matches[1];
}

function insertMessageDetails($conn, $id_automatizador, $uid_whatsapp, $mensaje, $json_mensaje)
{
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("
        INSERT INTO mensajes_clientes (id_automatizador, uid_whatsapp, mensaje, rol, json_mensaje, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($stmt === false) {
        throw new Exception("Failed to prepare the query: " . $conn->error);
    }

    // Convert all variables to appropriate types
    $id_automatizador = (int)$id_automatizador;
    $uid_whatsapp = (string)$uid_whatsapp;
    $mensaje = (string)$mensaje;
    $rol = 0; // Assuming 'rol' is always 0 for this function
    $json_mensaje = (string)$json_mensaje;
    $created_at = (string)$created_at;
    $updated_at = (string)$updated_at;

    // Bind parameters
    $stmt->bind_param('ississs', $id_automatizador, $uid_whatsapp, $mensaje, $rol, $json_mensaje, $created_at, $updated_at);
    $stmt->execute();
    $stmt->close();
}

function sendWhatsappMessage($conn, $user_info, $block_sql_data, $config)
{
    // Obtener la información del template de mensaje block_sql_data
    $id_whatsapp_message_template = $block_sql_data['id_whatsapp_message_template'];

    $template_info = getWhatsappMessageTemplate($config);
    if (isset($template_info['error'])) {
        // Si hay un error, devolverlo
        return "Error al consultar el template de WhatsApp: " . $template_info['error'];
    }

    // Buscar la plantilla específica en la respuesta
    $template_name = '';
    $language_code = '';
    foreach ($template_info['data'] as $template) {
        if ($template['id'] == $id_whatsapp_message_template) {
            $template_name = $template['name'];
            $language_code = $template['language'];
            break;
        }
    }

    if (empty($template_name) || empty($language_code)) {
        return "No se encontró la plantilla con ID: $id_whatsapp_message_template";
    }

    // Configurar el envío del mensaje de WhatsApp
    $url = 'https://graph.facebook.com/v20.0/' . $config['id_telefono'] . '/messages';
    $token = $config['token'];

    $recipient = $user_info['celular'];

    $mensaje = $block_sql_data['mensaje'];

    // Extract placeholders
    if (!function_exists('extract_placeholders')) {
        function extract_placeholders($message)
        {
            preg_match_all('/{{(.*?)}}/', $message, $matches);
            return $matches[1];
        }
    }

    $placeholders = extract_placeholders($mensaje);

    // Initialize components
    $components = [];

    if (!empty($placeholders)) {
        $parameters = [];

        // Map placeholders to user_info
        foreach ($placeholders as $placeholder) {
            if (isset($user_info[$placeholder])) {
                $parameters[] = [
                    "type" => "text",
                    "text" => $user_info[$placeholder]
                ];
            }
        }

        if (!empty($parameters)) {
            $components[] = [
                "type" => "body",
                "parameters" => $parameters
            ];
        }
    }

    // Create final data structure
    $data = [
        "messaging_product" => "whatsapp",
        "to" => $recipient,
        "type" => "template",
        "template" => [
            "name" => $template_name,
            "language" => [
                "code" => $language_code
            ]
        ]
    ];

    // Add components if they exist
    if (!empty($components)) {
        $data["template"]["components"] = $components;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $json_mensaje = json_encode($data);
    $respuesta = '';

    if ($result === false || $httpCode >= 400) {
        $respuesta = "Error al enviar el mensaje de WhatsApp: " . ($result ? $result : curl_error($ch));
        echo $respuesta;
    } else {
        $respuesta = "Mensaje de WhatsApp enviado a {$user_info['celular']}: " . $result;
        echo $respuesta;
    }

    // Insert message details into the database
    insertMessageDetails($conn, $block_sql_data['id_automatizador'], $recipient, $mensaje, $json_mensaje);

    return $respuesta;
}

function sendEmail($user_info, $subject, $message)
{
    $subject = replacePlaceholders($subject, $user_info);
    $message = replacePlaceholders($message, $user_info);
    return "Correo enviado a {$user_info['email']} con el asunto '$subject' y el mensaje '$message'";
}

function changeOrderStatus($order_id, $new_status)
{
    return "Estado de la orden $order_id cambiado a $new_status";
}

function getConfigurations($conn, $id_configuracion)
{
    $stmt = $conn->prepare("
        SELECT * FROM configuraciones WHERE id = ?
    ");
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('i', $id_configuracion);
    $stmt->execute();
    $result = $stmt->get_result();
    $config = $result->fetch_assoc();
    $stmt->close();

    return $config;
}

function insertInteractions($conn, $block_details, $id_automatizador, $user_id, $data)
{
    $user_id = $data['user_info']['celular'];
    $config = getConfigurations($conn, $data['id_configuracion']);

    foreach ($block_details as $block) {
        $tipo_interaccion = $block['block_table'];
        $id_interaccion = $block['block_sql_id'];
        $json_interaccion = $block['block_sql_data'];

        $respuesta_accion = '';

        if ($block['block_sql_data']['tipo'] == 8) {
            $respuesta_accion = sendWhatsappMessage($conn, $data['user_info'], $block['block_sql_data'], $config);
        } elseif ($block['block_sql_data']['tipo'] == 7) {
            $respuesta_accion = sendEmail($data['user_info'], 'subject', 'message');
        } elseif ($block['block_sql_data']['tipo'] == 9) {
            $respuesta_accion = changeOrderStatus($data['user_info']['order_id'], 'new_status');
        }

        $json_interaccion['respuesta_accion'] = $respuesta_accion;
        $json_interaccion['user_info'] = $data['user_info'];

        $json_interaccion = json_encode($json_interaccion);
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');

        $stmt = $conn->prepare("
            INSERT INTO interacciones_usuarios (id_automatizador, tipo_interaccion, id_interaccion, uid_usuario, json_interaccion, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        if ($stmt === false) {
            throw new Exception("Falló la preparación de la consulta: " . $conn->error);
        }
        $stmt->bind_param('ississs', $id_automatizador, $tipo_interaccion, $id_interaccion, $user_id, $json_interaccion, $created_at, $updated_at);
        $stmt->execute();
        $stmt->close();
    }
}

try {
    $automatizador = getAutomatizador($conn, $id_configuracion, $value_blocks_type, $data);

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
    insertInteractions($conn, $block_details, $id_automatizador, $user_id, $data);

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
