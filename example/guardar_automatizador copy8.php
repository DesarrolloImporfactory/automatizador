<?php
// Conexión con base de datos MySQL
include 'db.php';

// Obtener el JSON enviado desde el cliente
$json = file_get_contents('php://input');

// Decodificar el JSON en un array asociativo de PHP
$data = json_decode($json, true);

// Obtener la información necesaria del JSON decodificado
$id_automatizador = $data['id_automatizador'];
$flowly_output = json_encode($data['flowly_output']);
$info_bloques = json_encode($data['info_bloques']);
$resultado_automatizador = $data['resultado_automatizador'];

// Función para ejecutar una consulta SQL con manejo de errores y log
function executeQuery($conn, $query, $params) {
    global $output;
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $insert_id = $stmt->insert_id;
        $stmt->close();
        // Imprimir en la consola y agregar al output
        error_log("Query: $query");
        error_log("Params: " . json_encode($params));
        error_log("Insert ID: $insert_id");
        // Agregar la consulta y parámetros al output
        $output .= "Query: $query\n";
        $output .= "Params: " . json_encode($params) . "\n";
        $output .= "Insert ID: $insert_id\n";
        return $insert_id;
    } else {
        $output .= "Error en la consulta: " . $conn->error . "\n";
        error_log("Error en la consulta: " . $conn->error);
        return null;
    }
}

// Iniciar el output para las respuestas
$output = "";

// Actualizar la tabla `automatizadores`
$update_automatizadores_query = "
    UPDATE automatizadores 
    SET json_output = ?, json_bloques = ?, updated_at = NOW()
    WHERE id = ?
";
executeQuery($conn, $update_automatizadores_query, [
    $flowly_output,
    $info_bloques,
    $id_automatizador
]);

// Mapear para almacenar las relaciones parent-child
$parent_map = [];

// Función para verificar si un block_id ya existe y retornar su id
function checkExistingBlockId($conn, $table, $id_automatizador, $block_id) {
    global $output;
    $query = "SELECT id FROM $table WHERE id_automatizador = ? AND block_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $id_automatizador, $block_id);
    $stmt->execute();
    $stmt->bind_result($id);
    $stmt->fetch();
    $stmt->close();
    // Imprimir en la consola y agregar al output
    error_log("Check Existing Block ID Query: $query");
    error_log("Params: " . json_encode([$id_automatizador, $block_id]));
    error_log("Result ID: $id");
    // Agregar la consulta y parámetros al output
    $output .= "Check Existing Block ID Query: $query\n";
    $output .= "Params: " . json_encode([$id_automatizador, $block_id]) . "\n";
    $output .= "Result ID: $id\n";
    return $id;
}

// Función para obtener el ID del padre según el tipo y el block_id
function getParentId($parent_map, $parent, $type) {
    if (isset($parent_map[$parent]) && $parent_map[$parent]['type'] == $type) {
        return $parent_map[$parent]['id'];
    }
    return null;
}

// Iterar a través de `resultado_automatizador` y procesar cada entrada
foreach ($resultado_automatizador as $resultado) {
    $tipo = $resultado['tipo'];
    $info = $resultado['info'];
    $parent = $resultado['parent'];
    $block_id = $resultado['id'];

    // Mapear los campos del formulario a las variables correctas
    $productos = isset($info['productos[]']) ? json_encode($info['productos[]']) : null;
    $categorias = isset($info['categorias[]']) ? json_encode($info['categorias[]']) : null;
    $status = isset($info['status[]']) ? json_encode($info['status[]']) : null;
    $novedad = isset($info['novedad[]']) ? json_encode($info['novedad[]']) : null;
    $provincia = isset($info['provincia[]']) ? json_encode($info['provincia[]']) : null;
    $ciudad = isset($info['ciudad[]']) ? json_encode($info['ciudad[]']) : null;
    $mensaje = isset($info['mensaje']) ? $info['mensaje'] : null;
    $asunto = isset($info['asunto']) ? $info['asunto'] : null;
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

    // Procesar según el tipo
    if ($tipo <= 6) { // Disparadores
        $existing_id = checkExistingBlockId($conn, 'disparadores', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_disparadores_query = "
                UPDATE disparadores
                SET productos = ?, categorias = ?, status = ?, novedad = ?, provincia = ?, ciudad = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_disparadores_query, [
                $productos,
                $categorias,
                $status,
                $novedad,
                $provincia,
                $ciudad,
                $existing_id
            ]);
        } else {
            // Insertar nuevo registro
            $insert_disparadores_query = "
                INSERT INTO disparadores (id_automatizador, block_id, tipo, productos, categorias, status, novedad, provincia, ciudad, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_disparador = executeQuery($conn, $insert_disparadores_query, [
                $id_automatizador,
                $block_id,
                $tipo,
                $productos,
                $categorias,
                $status,
                $novedad,
                $provincia,
                $ciudad
            ]);
            $parent_map[$resultado['id']] = ['id' => $id_disparador, 'type' => 'disparador'];
        }
    } elseif ($tipo == 7 || $tipo == 8 || $tipo == 9) { // Acciones
        $id_condicion = getParentId($parent_map, $parent, 'condicion');
        $id_disparador = getParentId($parent_map, $parent, 'disparador');
        $id_accion_padre = getParentId($parent_map, $parent, 'accion');
        $id_whatsapp_message_template = isset($info['id_whatsapp_message_template']) ? $info['id_whatsapp_message_template'] : null;

        $existing_id = checkExistingBlockId($conn, 'acciones', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_acciones_query = "
                UPDATE acciones
                SET id_accion = ?, id_condicion = ?, id_disparador = ?, asunto = ?, mensaje = ?, opciones = ?, tiempo_envio = ?, unidad_envio = ?, tiempo_reenvio = ?, unidad_reenvio = ?, reenvios = ?, cambiar_status = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_acciones_query, [
                $id_accion_padre,
                $id_condicion,
                $id_disparador,
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
                INSERT INTO acciones (id_accion, id_condicion, id_disparador, id_automatizador, block_id, tipo, id_whatsapp_message_template, asunto, mensaje, opciones, tiempo_envio, unidad_envio, tiempo_reenvio, unidad_reenvio, reenvios, cambiar_status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_accion = executeQuery($conn, $insert_acciones_query, [
                $id_accion_padre,
                $id_condicion,
                $id_disparador,
                $id_automatizador,
                $block_id,
                $tipo,
                $id_whatsapp_message_template,
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
        $id_accion = getParentId($parent_map, $parent, 'accion');
        $id_disparador = getParentId($parent_map, $parent, 'disparador');
        $id_condicion_padre = getParentId($parent_map, $parent, 'condicion');

        $existing_id = checkExistingBlockId($conn, 'condiciones', $id_automatizador, $block_id);
        if ($existing_id) {
            // Actualizar el registro existente
            $update_condiciones_query = "
                UPDATE condiciones
                SET id_condicion = ?, id_accion = ?, id_disparador = ?, texto = ?, updated_at = NOW()
                WHERE id = ?
            ";
            executeQuery($conn, $update_condiciones_query, [
                $id_condicion_padre,
                $id_accion,
                $id_disparador,
                $texto,
                $existing_id
            ]);
        } else {
            // Insertar nuevo registro
            $insert_condiciones_query = "
                INSERT INTO condiciones (id_condicion, id_accion, id_disparador, id_automatizador, block_id, texto, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
            ";
            $id_condicion = executeQuery($conn, $insert_condiciones_query, [
                $id_condicion_padre,
                $id_accion,
                $id_disparador,
                $id_automatizador,
                $block_id,
                $texto
            ]);
            $parent_map[$resultado['id']] = ['id' => $id_condicion, 'type' => 'condicion'];
        }
    }
}

// Imprimir el output final
echo nl2br($output);

// Cerrar la conexión
$conn->close();
?>