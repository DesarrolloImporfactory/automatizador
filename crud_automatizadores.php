<?php
include 'db.php';

$action = $_REQUEST['action'];

// Imprimir datos recibidos por POST
error_log("Datos recibidos por POST: " . print_r($_POST, true));

switch ($action) {
    case 'create':
        $id_configuracion = $_POST['id_configuracion'];
        $nombre = $_POST['nombre'];
        $json_output = $_POST['json_output'];
        $json_bloques = $_POST['json_bloques'];
        $estado = $_POST['estado'];

        $stmt = $conn->prepare("INSERT INTO automatizadores (id_configuracion, nombre, json_output, json_bloques, estado) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("isssi", $id_configuracion, $nombre, $json_output, $json_bloques, $estado);

        if ($stmt->execute()) {
            echo "Record created successfully";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        break;

    case 'read':
        $id_configuracion = isset($_POST['id_configuracion']) ? $_POST['id_configuracion'] : null;
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($id) {
            $stmt = $conn->prepare("SELECT a.*, c.nombre_configuracion FROM automatizadores a LEFT JOIN configuraciones c ON a.id_configuracion = c.id WHERE a.id = ?");
            $stmt->bind_param("i", $id);
        } else {
            $stmt = $conn->prepare("SELECT a.*, c.nombre_configuracion FROM automatizadores a LEFT JOIN configuraciones c ON a.id_configuracion = c.id WHERE a.id_configuracion = ?");
            $stmt->bind_param("i", $id_configuracion);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $data = array();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode($data);

        $stmt->close();
        break;

    case 'update':
        $id = $_POST['id'];
        $id_configuracion = $_POST['id_configuracion'];
        $nombre = $_POST['nombre'];
        $json_output = $_POST['json_output'];
        $json_bloques = $_POST['json_bloques'];
        $estado = $_POST['estado'];

        $stmt = $conn->prepare("UPDATE automatizadores SET id_configuracion=?, nombre=?, json_output=?, json_bloques=?, estado=? WHERE id=?");
        $stmt->bind_param("isssii", $id_configuracion, $nombre, $json_output, $json_bloques, $estado, $id);

        if ($stmt->execute()) {
            echo "Record updated successfully";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        break;

    case 'delete':
        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM automatizadores WHERE id=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo "Record deleted successfully";
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
        break;

    default:
        echo "Invalid action";
        break;
}

$conn->close();
?>
