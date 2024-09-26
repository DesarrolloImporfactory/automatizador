<?php
require 'db.php';

// Asegúrate de que los encabezados CORS están configurados si es necesario
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Lee el cuerpo de la solicitud POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Verifica si los datos fueron decodificados correctamente
if (!$data) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos."]);
    exit;
}

$id_configuracion = isset($data['id_configuracion']) ? (int)$data['id_configuracion'] : 0;
$value_blocks_type = isset($data['value_blocks_type']) ? $data['value_blocks_type'] : '';
$user_id = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$user_info = isset($data['user_info']) ? $data['user_info'] : [];

// Funciones auxiliares
function replacePlaceholders($text, $placeholders) {
    foreach ($placeholders as $key => $value) {
        $text = str_replace("{{{$key}}}", $value, $text);
    }
    return $text;
}

function getConfig($id_configuracion, $conn) {
    $query = $conn->prepare("SELECT id_telefono, token FROM configuraciones WHERE id = ?");
    $query->bind_param('i', $id_configuracion);
    $query->execute();
    $query->bind_result($id_telefono, $token);
    $query->fetch();
    $query->close();
    return ['id_telefono' => $id_telefono, 'token' => $token];
}

function sendWhatsappMessage($user_info, $template_name, $template_parameters, $config) {
    $url = 'https://graph.facebook.com/v19.0/' . $config['id_telefono'] . '/messages';
    $token = $config['token'];
    $recipient = $user_info['celular'];
    $template_name = 'hello_world';
    $language_code = 'en_US';

    $data = [
        "messaging_product" => "whatsapp",
        "to" => $recipient,
        "type" => "template",
        "template" => [
            "name" => $template_name,
            "language" => [
                "code" => $language_code,
            ]
            /*
            ,
            "components" => [
                [
                    "type" => "body",
                    "parameters" => array_map(function ($param) {
                        return ["type" => "text", "text" => $param];
                    }, $template_parameters)
                ]
            ]
            */
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($result === FALSE || $http_code != 200) {
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ["status" => "error", "message" => "Error al enviar el mensaje de WhatsApp.", "details" => $error_msg, "http_code" => $http_code, "response" => $result];
    }

    curl_close($ch);
    return ["status" => "success", "message" => "Mensaje de WhatsApp enviado a {$user_info['celular']}", "response" => $result];
}

function sendEmail($user_info, $subject, $message) {
    $subject = replacePlaceholders($subject, $user_info);
    $message = replacePlaceholders($message, $user_info);
    return ["status" => "success", "message" => "Correo enviado a {$user_info['email']} con el asunto '$subject' y el mensaje '$message'"];
}

function changeOrderStatus($order_id, $new_status) {
    return ["status" => "success", "message" => "Estado de la orden $order_id cambiado a $new_status"];
}

try {
    $id_automatizador = null;
    $tipo_interaccion = '';
    $id_interaccion = null;

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

    $insert_query = $conn->prepare("
        INSERT INTO interacciones_usuarios (id_automatizador, tipo_interaccion, id_interaccion, uid_usuario, json_interaccion)
        VALUES (?, ?, ?, ?, ?)
    ");
    $json_interaccion = json_encode($data);

    $insert_query->bind_param('isiss', $id_automatizador, $tipo_interaccion, $id_interaccion, $user_id, $json_interaccion);
    $insert_query->execute();
    $insert_query->close();

    echo json_encode(["status" => "success", "message" => "Interacción registrada con éxito."]);

    $config = getConfig($id_configuracion, $conn);

    $responses = [];
    if ($tipo_interaccion === 'disparadores') {
        $query = $conn->prepare("
            SELECT a.tipo, a.mensaje, a.asunto, a.id_whatsapp_message_template, a.cambiar_status
            FROM acciones a
            WHERE a.id_disparador = ?
        ");
        $query->bind_param('i', $id_interaccion);
        $query->execute();
        $query->bind_result($accion_tipo, $mensaje, $asunto, $id_whatsapp_message_template, $cambiar_status);
        while ($query->fetch()) {
            switch ($accion_tipo) {
                case 7:
                    if (!empty($user_info['email'])) {
                        $responses[] = sendEmail($user_info, $asunto, $mensaje);
                    }
                    break;
                case 8:
                    if (!empty($user_info['celular'])) {
                        $template_parameters = [$user_info['nombre'], $user_info['celular'], $user_info['direccion'], $user_info['email'], $user_info['order_id']];
                        $responses[] = sendWhatsappMessage($user_info, $id_whatsapp_message_template, $template_parameters, $config);
                    }
                    break;
                case 9:
                    if (!empty($data['order_id'])) {
                        $responses[] = changeOrderStatus($data['order_id'], $cambiar_status);
                    }
                    break;
            }
        }
        $query->close();
    }

    foreach ($responses as $response) {
        echo json_encode($response);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
} catch (mysqli_sql_exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>