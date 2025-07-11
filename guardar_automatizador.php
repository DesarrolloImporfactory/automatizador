<?php
// Conexión con base de datos MySQL
include 'db.php';

// Obtener el JSON enviado desde el cliente
$json = file_get_contents('php://input');

// Decodificar el JSON en un array asociativo de PHP
$data = json_decode($json, true);

// Imprimir el JSON recibido
//echo json_encode($data, JSON_PRETTY_PRINT);

// Obtener la información necesaria del JSON decodificado
$id_automatizador = $data['id_automatizador'];
$flowly_output = json_encode($data['flowly_output']);
$info_bloques = json_encode($data['info_bloques']);
$resultado_automatizador = $data['resultado_automatizador'];

// Función para ejecutar una consulta SQL con manejo de errores y log
function executeQuery($conn, $query, $params)
{
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param(...$params);
        $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        // Imprimir en la consola
        error_log("Query: $query");
        error_log("Params: " . json_encode($params));
        error_log("Insert ID: $insert_id");
        return $insert_id;
    } else {
        echo "Error en la consulta: " . $conn->error;
        error_log("Error en la consulta: " . $conn->error);
        return null;
    }
}

// Actualizar la tabla `automatizadores`
$update_automatizadores_query = "
    UPDATE automatizadores 
    SET json_output = ?, json_bloques = ?, updated_at = NOW()
    WHERE id = ?
";
executeQuery($conn, $update_automatizadores_query, [
    'ssi',
    $flowly_output,
    $info_bloques,
    $id_automatizador
]);

// Mapear para almacenar las relaciones parent-child
$parent_map = [];

// Función para verificar si un block_id ya existe y retornar su id
function checkExistingBlockId($conn, $table, $id_automatizador, $block_id)
{
    $query = "SELECT id FROM $table WHERE id_automatizador = ? AND block_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $id_automatizador, $block_id);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    // Imprimir en la consola
    error_log("Check Existing Block ID Query: $query");
    error_log("Params: " . json_encode([$id_automatizador, $block_id]));
    error_log("Result ID: $id");
    return $id;
}

// Iterar a través de `resultado_automatizador` y procesar cada entrada
foreach ($resultado_automatizador as $resultado) {
    $tipo = $resultado['tipo'];
    $info = $resultado['info'];
    $parent = $resultado['parent'];
    $block_id = $resultado['id'];

    // Mapear los campos del formulario a las variables correctas
    $productos = isset($info['productos[]']) ? json_encode($info['productos[]']) : null;
    $abandonados = isset($info['abandonados[]']) ? json_encode($info['abandonados[]']) : null;
    $status = isset($info['status[]']) ? json_encode($info['status[]']) : null;
    $estado_pedido = isset($info['estado_pedido[]']) ? json_encode($info['estado_pedido[]']) : null;
    $provincia = isset($info['provincia[]']) ? json_encode($info['provincia[]']) : null;
    $ciudad = isset($info['ciudad[]']) ? json_encode($info['ciudad[]']) : null;
    $mensaje = isset($info['mensaje']) ? $info['mensaje'] : null;
    $asunto = isset($info['asunto']) ? $info['asunto'] : null;
    //echo $asunto;
    $opciones = isset($info['mensaje_opcion_1']) ? json_encode([
        'mensaje_opcion_1' => $info['mensaje_opcion_1'],
        'mensaje_opcion_2' => $info['mensaje_opcion_2'],
        'mensaje_opcion_3' => $info['mensaje_opcion_3']
    ]) : null;
    $tiempo_envio = isset($info['tiempo_envio']) ? $info['tiempo_envio'] : null;
    $unidad_envio = isset($info['tipo_envio']) ? $info['tipo_envio'] : null;
    $tiempo_reenvio = isset($info['tiempo_reenvio']) ? $info['tiempo_reenvio'] : null;
    $unidad_reenvio = isset($info['tipo_reenvio']) ? $info['tipo_reenvio'] : null;
    $reenvios = isset($info['veces_reenvio']) ? $info['veces_reenvio'] : null;
    $cambiar_status = isset($info['status_a[]']) ? json_encode($info['status_a[]']) : null;
    $texto = isset($info['texto_recibir']) ? $info['texto_recibir'] : null;
    $wait = isset($info['wait[]'])
        ? (is_array($info['wait[]']) ? json_encode($info['wait[]']) : json_encode([$info['wait[]']]))
        : null;


    // Procesar según el tipo
    if ($tipo <= 6) { // Disparadores
        $existing_id = checkExistingBlockId($conn, 'disparadores', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_disparadores_query = "
                UPDATE disparadores
                SET productos = ?, abandonados = ?, status = ?, estado_pedido = ?, provincia = ?, ciudad = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_disparadores_query, [
                'ssssssi',
                $productos,
                $abandonados,
                $status,
                $estado_pedido,
                $provincia,
                $ciudad,
                $existing_id
            ]);
        } else {
            // Insertar nuevo registro
            $insert_disparadores_query = "
                INSERT INTO disparadores (id_automatizador, block_id, tipo, productos, abandonados, status, estado_pedido, provincia, ciudad, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_disparador = executeQuery($conn, $insert_disparadores_query, [
                'iisssssss',
                $id_automatizador,
                $block_id,
                $tipo,
                $productos,
                $abandonados,
                $status,
                $estado_pedido,
                $provincia,
                $ciudad
            ]);
            $parent_map[$resultado['id']] = ['id' => $id_disparador, 'type' => 'disparador'];
        }
    } elseif ($tipo == 7 || $tipo == 8 || $tipo == 9) { // Acciones
        $id_condicion = isset($parent_map[$parent]) && $parent_map[$parent]['type'] == 'condicion' ? $parent_map[$parent]['id'] : null;
        $id_disparador = isset($parent_map[$parent]) && $parent_map[$parent]['type'] == 'disparador' ? $parent_map[$parent]['id'] : null;

        $existing_id = checkExistingBlockId($conn, 'acciones', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_acciones_query = "
                UPDATE acciones
                SET asunto = ?, mensaje = ?, opciones = ?, tiempo_envio = ?, unidad_envio = ?, tiempo_reenvio = ?, unidad_reenvio = ?, reenvios = ?, cambiar_status = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_acciones_query, [
                'sssssssssi',
                $asunto,
                $mensaje,
                $opciones,
                $tiempo_envio,
                $unidad_envio,
                $tiempo_reenvio,
                $unidad_reenvio,
                $reenvios,
                $cambiar_status,
                $existing_id
            ]);
        } else {
            // Insertar nuevo registro
            $insert_acciones_query = "
            INSERT INTO acciones (id_condicion, id_disparador, id_automatizador, block_id, tipo, asunto, mensaje, opciones, tiempo_envio, unidad_envio, tiempo_reenvio, unidad_reenvio, reenvios, cambiar_status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_accion = executeQuery($conn, $insert_acciones_query, [
                'iiisisssssssss',
                $id_condicion,
                $id_disparador,
                $id_automatizador,
                $block_id,
                $tipo,
                $asunto,
                $mensaje,
                $opciones,
                $tiempo_envio,
                $unidad_envio,
                $tiempo_reenvio,
                $unidad_reenvio,
                $reenvios,
                $cambiar_status
            ]);
            $parent_map[$resultado['id']] = ['id' => $id_accion, 'type' => 'accion'];
        }
    } elseif ($tipo == 10) { // Condiciones
        $id_accion = isset($parent_map[$parent]) && $parent_map[$parent]['type'] == 'accion' ? $parent_map[$parent]['id'] : null;
        $id_disparador = isset($parent_map[$parent]) && $parent_map[$parent]['type'] == 'disparador' ? $parent_map[$parent]['id'] : null;

        $existing_id = checkExistingBlockId($conn, 'condiciones', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_condiciones_query = "
                UPDATE condiciones
                SET texto = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_condiciones_query, [
                'si',
                $texto,
                $existing_id
            ]);
        } else {
            // Insertar nuevo registro
            $insert_condiciones_query = "
                INSERT INTO condiciones (id_accion, id_disparador, id_automatizador, block_id, texto, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_condicion = executeQuery($conn, $insert_condiciones_query, [
                'iiiss',
                $id_accion,
                $id_disparador,
                $id_automatizador,
                $block_id,
                $texto
            ]);
            $parent_map[$resultado['id']] = ['id' => $id_condicion, 'type' => 'condicion'];
        }
    } elseif ($tipo == 13) { // Condiciones
        $id_accion = isset($parent_map[$parent]) && $parent_map[$parent]['type'] == 'accion' ? $parent_map[$parent]['id'] : null;
        $id_disparador = isset($parent_map[$parent]) && $parent_map[$parent]['type'] == 'disparador' ? $parent_map[$parent]['id'] : null;

        $existing_id = checkExistingBlockId($conn, 'condiciones', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_condiciones_query = "
                UPDATE condiciones
                SET texto = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_condiciones_query, [
                'si',
                $wait,
                $existing_id
            ]);
        } else {
            // Insertar nuevo registro
            $insert_condiciones_query = "
                INSERT INTO condiciones (id_accion, id_disparador, id_automatizador, block_id, texto, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_condicion = executeQuery($conn, $insert_condiciones_query, [
                'iiiss',
                $id_accion,
                $id_disparador,
                $id_automatizador,
                $block_id,
                $wait
            ]);
            $parent_map[$resultado['id']] = ['id' => $id_condicion, 'type' => 'condicion'];
        }
    }
}

// Cerrar la conexión
$conn->close();
