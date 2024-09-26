<?php
require 'db.php';

$id_configuracion = isset($_GET['id_configuracion']) ? (int)$_GET['id_configuracion'] : 0;
$value_blocks_type = isset($_GET['value_blocks_type']) ? $_GET['value_blocks_type'] : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$data_json = isset($_GET['data']) ? $_GET['data'] : '';
$data = json_decode($data_json, true);

if (!$data) {
    echo "Error: datos inválidos.";
    exit;
}

$user_info = isset($data['user_info']) ? json_encode($data['user_info']) : '';

try {
    // Variables para almacenar los resultados de la búsqueda
    $id_automatizador = null;
    $tipo_interaccion = '';
    $id_interaccion = null;

    // Buscar en la tabla disparadores
    $query = $conn->prepare("
        SELECT d.id_automatizador, d.id AS id_interaccion, 'disparadores' AS tipo_interaccion
        FROM disparadores d
        JOIN automatizadores a ON d.id_automatizador = a.id
        WHERE a.id_configuracion = ? AND d.tipo = ?
    ");
    $query->bind_param('is', $id_configuracion, $value_blocks_type);
    $query->execute();
    $query->bind_result($id_automatizador, $id_interaccion, $tipo_interaccion);
    $query->fetch();
    $query->close();

    if ($id_automatizador === null) {
        // Buscar en la tabla acciones
        $query = $conn->prepare("
            SELECT a.id_automatizador, a.id AS id_interaccion, 'acciones' AS tipo_interaccion
            FROM acciones a
            JOIN automatizadores at ON a.id_automatizador = at.id
            WHERE at.id_configuracion = ? AND a.tipo = ?
        ");
        $query->bind_param('is', $id_configuracion, $value_blocks_type);
        $query->execute();
        $query->bind_result($id_automatizador, $id_interaccion, $tipo_interaccion);
        $query->fetch();
        $query->close();
    }

    if ($id_automatizador === null) {
        // Buscar en la tabla condiciones
        $query = $conn->prepare("
            SELECT c.id_automatizador, c.id AS id_interaccion, 'condiciones' AS tipo_interaccion
            FROM condiciones c
            JOIN automatizadores at ON c.id_automatizador = at.id
            WHERE at.id_configuracion = ? AND c.tipo = ?
        ");
        $query->bind_param('is', $id_configuracion, $value_blocks_type);
        $query->execute();
        $query->bind_result($id_automatizador, $id_interaccion, $tipo_interaccion);
        $query->fetch();
        $query->close();
    }

    if ($id_automatizador === null) {
        throw new Exception("No se encontró un automatizador asociado a la configuración especificada y el tipo de bloque.");
    }

    // Insertar en la tabla interacciones_usuarios
    $insert_query = $conn->prepare("
        INSERT INTO interacciones_usuarios (id_automatizador, tipo_interaccion, id_interaccion, uid_usuario, json_interaccion)
        VALUES (?, ?, ?, ?, ?)
    ");
    $json_interaccion = json_encode($data);

    $insert_query->bind_param('isiss', $id_automatizador, $tipo_interaccion, $id_interaccion, $user_id, $json_interaccion);
    $insert_query->execute();
    $insert_query->close();

    echo "Interacción registrada con éxito.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} catch (mysqli_sql_exception $e) {
    echo "Error de SQL: " . $e->getMessage();
}
?>