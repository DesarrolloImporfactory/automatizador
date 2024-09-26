<?php
include 'db.php';

$action = $_REQUEST['action'];

switch ($action) {
    case 'create':
        $id_automatizador = $_POST['id_automatizador'];
        $tipo_interaccion = $_POST['tipo_interaccion'];
        $id_interaccion = $_POST['id_interaccion'];
        $uid_usuario = $_POST['uid_usuario'];
        $json_interaccion = $_POST['json_interaccion'];

        $sql = "INSERT INTO interacciones_usuarios (id_automatizador, tipo_interaccion, id_interaccion, uid_usuario, json_interaccion)
                VALUES ('$id_automatizador', '$tipo_interaccion', '$id_interaccion', '$uid_usuario', '$json_interaccion')";

        if ($conn->query($sql) === TRUE) {
            echo "Record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        break;

    case 'read':
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $id_automatizador = $_POST['id_automatizador'];

        if ($id) {
            $sql = "SELECT * FROM interacciones_usuarios WHERE id='$id' and id_automatizador = '$id_automatizador' ";
        } else {
            $sql = "SELECT * FROM interacciones_usuarios WHERE id_automatizador = '$id_automatizador'";
        }

        $result = $conn->query($sql);
        $data = array();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
        }
        echo json_encode($data);
        break;

    case 'update':
        $id = $_POST['id'];
        $id_automatizador = $_POST['id_automatizador'];
        $tipo_interaccion = $_POST['tipo_interaccion'];
        $id_interaccion = $_POST['id_interaccion'];
        $uid_usuario = $_POST['uid_usuario'];
        $json_interaccion = $_POST['json_interaccion'];

        $sql = "UPDATE interacciones_usuarios SET id_automatizador='$id_automatizador', tipo_interaccion='$tipo_interaccion', id_interaccion='$id_interaccion', uid_usuario='$uid_usuario', json_interaccion='$json_interaccion' WHERE id='$id'";

        if ($conn->query($sql) === TRUE) {
            echo "Record updated successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        break;

    case 'delete':
        $id = $_POST['id'];

        $sql = "DELETE FROM interacciones_usuarios WHERE id='$id'";

        if ($conn->query($sql) === TRUE) {
            echo "Record deleted successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        break;

    default:
        echo "Invalid action";
        break;
}

$conn->close();
?>
