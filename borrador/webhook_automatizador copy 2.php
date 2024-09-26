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
        SELECT a.id 
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
    $stmt->bind_result($id_automatizador);
    $stmt->fetch();
    $stmt->close();

    return $id_automatizador ? $id_automatizador : null;
}

function getDisparadores($conn, $id_automatizador) {
    $disparadores = [];
    $query = "
        SELECT id, block_id, tipo, productos, categorias, status, novedad, provincia, ciudad, created_at, updated_at 
        FROM disparadores 
        WHERE id_automatizador = ?
    ";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('i', $id_automatizador);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $disparadores[] = $row;
    }

    $stmt->close();
    return $disparadores;
}

function getAcciones($conn, $id_automatizador) {
    $acciones = [];
    $query = "
        SELECT id, block_id, id_condicion, id_disparador, id_accion, tipo, id_whatsapp_message_template, asunto, mensaje, opciones, tiempo_envio, unidad_envio, tiempo_reenvio, unidad_reenvio, reenvios, cambiar_status, created_at, updated_at 
        FROM acciones 
        WHERE id_automatizador = ?
    ";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('i', $id_automatizador);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $acciones[] = $row;
    }

    $stmt->close();
    return $acciones;
}

function getCondiciones($conn, $id_automatizador) {
    $condiciones = [];
    $query = "
        SELECT id, block_id, id_accion, id_condicion, id_disparador, texto, created_at, updated_at 
        FROM condiciones 
        WHERE id_automatizador = ?
    ";
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Falló la preparación de la consulta: " . $conn->error);
    }
    $stmt->bind_param('i', $id_automatizador);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $condiciones[] = $row;
    }

    $stmt->close();
    return $condiciones;
}

function createExecutionOrder($disparadores, $acciones, $condiciones) {
    $executionOrder = [];

    foreach ($disparadores as $disparador) {
        $executionOrder[] = [
            'type' => 'disparador',
            'data' => $disparador
        ];
        findLinkedActions($disparador['id'], $acciones, $executionOrder, $condiciones);
    }

    return $executionOrder;
}

function findLinkedActions($disparadorId, $acciones, &$executionOrder, $condiciones) {
    foreach ($acciones as $accion) {
        if ($accion['id_disparador'] == $disparadorId) {
            $executionOrder[] = [
                'type' => 'accion',
                'data' => $accion
            ];
            findLinkedActionsByActionId($accion['id'], $acciones, $executionOrder, $condiciones);
        }
    }

    foreach ($condiciones as $condicion) {
        if ($condicion['id_disparador'] == $disparadorId) {
            $executionOrder[] = [
                'type' => 'condicion',
                'data' => $condicion
            ];
        }
    }
}

function findLinkedActionsByActionId($accionId, $acciones, &$executionOrder, $condiciones) {
    foreach ($acciones as $accion) {
        if ($accion['id_accion'] == $accionId) {
            $executionOrder[] = [
                'type' => 'accion',
                'data' => $accion
            ];
            findLinkedActionsByActionId($accion['id'], $acciones, $executionOrder, $condiciones);
        }
    }
}

try {
    $id_automatizador = getAutomatizador($conn, $id_configuracion, $value_blocks_type);

    if ($id_automatizador === null) {
        throw new Exception("No se encontró un automatizador asociado a la configuración especificada y el tipo de bloque.");
    }

    $insert_query = $conn->prepare("
        INSERT INTO interacciones_usuarios (id_automatizador, tipo_interaccion, id_interaccion, uid_usuario, json_interaccion)
        VALUES (?, ?, ?, ?, ?)
    ");
    if ($insert_query === false) {
        throw new Exception("Falló la preparación de la consulta de inserción: " . $conn->error);
    }
    $json_interaccion = json_encode($data);
    $tipo_interaccion = 'disparadores'; // Placeholder, cambiar según la lógica
    $id_interaccion = $id_automatizador; // Placeholder, cambiar según la lógica

    $insert_query->bind_param('isiss', $id_automatizador, $tipo_interaccion, $id_interaccion, $user_id, $json_interaccion);
    $insert_query->execute();
    $insert_query->close();

    // Obtener todos los datos asociados
    $disparadores = getDisparadores($conn, $id_automatizador);
    $acciones = getAcciones($conn, $id_automatizador);
    $condiciones = getCondiciones($conn, $id_automatizador);

    $executionOrder = createExecutionOrder($disparadores, $acciones, $condiciones);

    $response = [
        "id_configuracion" => $id_configuracion,
        "id_automatizador" => $id_automatizador,
        "execution_order" => $executionOrder
    ];

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>