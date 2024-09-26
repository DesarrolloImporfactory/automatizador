<?php
include 'db.php';

$action = $_REQUEST['action'];

switch ($action) {
    case 'create':
        $nombre_configuracion = $_POST['nombre_configuracion'];
        $telefono = $_POST['telefono'];
        $id_telefono = $_POST['id_telefono'];
        $id_whatsapp = $_POST['id_whatsapp'];
        $token = $_POST['token'];
        $crm = $_POST['crm'];
        $webhook_url = $_POST['webhook_url'];
        $server = $_POST['server'];
        $port = $_POST['port'];
        $security = $_POST['security'];
        $from_name = $_POST['from_name'];
        $from_email = $_POST['from_email'];
        $auth_required = isset($_POST['auth_required']) ? 1 : 0;
        $usuario = $_POST['usuario'];
        $contrasena = $_POST['contrasena'];

        $sql = "INSERT INTO configuraciones (nombre_configuracion, telefono, id_telefono, id_whatsapp, token, crm, webhook_url, server, port, security, from_name, from_email, auth_required, usuario, contrasena)
                VALUES ('$nombre_configuracion', '$telefono', '$id_telefono', '$id_whatsapp', '$token', '$crm', '$webhook_url', '$server', '$port', '$security', '$from_name', '$from_email', '$auth_required', '$usuario', '$contrasena')";
        
        if ($conn->query($sql) === TRUE) {
            echo "Record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        break;
    case 'read':
        $id = isset($_POST['id']) ? $_POST['id'] : null;

        if ($id) {
            $sql = "SELECT * FROM configuraciones WHERE id='$id'";
        } else {
            $sql = "SELECT * FROM configuraciones";
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
        $nombre_configuracion = $_POST['nombre_configuracion'];
        $telefono = $_POST['telefono'];
        $id_telefono = $_POST['id_telefono'];
        $id_whatsapp = $_POST['id_whatsapp'];
        $token = $_POST['token'];
        $crm = $_POST['crm'];
        $webhook_url = $_POST['webhook_url'];
        $server = $_POST['server'];
        $port = $_POST['port'];
        $security = $_POST['security'];
        $from_name = $_POST['from_name'];
        $from_email = $_POST['from_email'];
        $auth_required = isset($_POST['auth_required']) ? 1 : 0;
        $usuario = $_POST['usuario'];
        $contrasena = $_POST['contrasena'];

        $sql = "UPDATE configuraciones SET nombre_configuracion='$nombre_configuracion', telefono='$telefono', id_telefono='$id_telefono', id_whatsapp='$id_whatsapp', token='$token', crm='$crm', webhook_url='$webhook_url', server='$server', port='$port', security='$security', from_name='$from_name', from_email='$from_email', auth_required='$auth_required', usuario='$usuario', contrasena='$contrasena' WHERE id='$id'";
        
        if ($conn->query($sql) === TRUE) {
            echo "Record updated successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
        break;
    case 'delete':
        $id = $_POST['id'];

        $sql = "DELETE FROM configuraciones WHERE id='$id'";
        
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