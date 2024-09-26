<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

$debug_log = [];

// Verificación del webhook
if (isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token'])) {
    $stmt = $conn->prepare("SELECT webhook_url FROM configuraciones WHERE webhook_url = ?");
    $stmt->bind_param('s', $_GET['hub_verify_token']);
    $stmt->execute();
    $stmt->bind_result($webhook_token);
    $stmt->fetch();
    $stmt->close();

    if ($webhook_token === $_GET['hub_verify_token']) {
        echo $_GET['hub_challenge'];
        exit;
    } else {
        echo json_encode(["status" => "error", "message" => "Token de verificación incorrecto."]);
        exit;
    }
}

// Leer datos enviados por WhatsApp
$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos."]);
    exit;
}

$debug_log['data'] = $data;

$business_phone_id = $data['entry'][0]['id'];
$phone_whatsapp_from = $data['entry'][0]['changes'][0]['value']['messages'][0]['from'];
$name_whatsapp_from = $data['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'];
$respuesta_whatsapp_type = $data['entry'][0]['changes'][0]['value']['messages'][0]['type'];
$body = $data['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];

$debug_log['business_phone_id'] = $business_phone_id;
$debug_log['phone_whatsapp_from'] = $phone_whatsapp_from;
$debug_log['name_whatsapp_from'] = $name_whatsapp_from;
$debug_log['respuesta_whatsapp_type'] = $respuesta_whatsapp_type;
$debug_log['body'] = $body;

// Verificar si hay información para procesar
if (strlen($business_phone_id) > 0 && strlen($phone_whatsapp_from) > 0) {
    try {
        // Obtener id_configuracion a partir de business_phone_id y webhook_url
        $stmt = $conn->prepare("SELECT id FROM configuraciones WHERE id_whatsapp = ? AND webhook_url = ?");
        $stmt->bind_param('ss', $business_phone_id, $_GET['hub_verify_token']);
        $stmt->execute();
        $stmt->bind_result($id_configuracion);
        $stmt->fetch();
        $stmt->close();

        if (!$id_configuracion) {
            throw new Exception("Configuración no encontrada.");
        }

        $debug_log['id_configuracion'] = $id_configuracion;

        // Obtener automatizador asociado a la configuración y verificar condiciones
        $stmt = $conn->prepare("
            SELECT a.id, a.json_output, c.id AS condicion_id, c.block_id, c.texto
            FROM automatizadores a
            JOIN condiciones c ON a.id = c.id_automatizador
            WHERE a.id_configuracion = ? AND a.estado = 1 AND c.texto = ?
        ");
        $stmt->bind_param('is', $id_configuracion, $body);
        $stmt->execute();
        $result = $stmt->get_result();

        $conditions = [];
        while ($row = $result->fetch_assoc()) {
            $conditions[] = $row;
        }
        $stmt->close();

        $debug_log['conditions'] = $conditions;

        if (empty($conditions)) {
            throw new Exception("No se encontraron condiciones que coincidan con el texto del mensaje.");
        }

        // Procesar condiciones para verificar interacciones previas
        $truth_table = [];
        foreach ($conditions as $condition) {
            $block_id = $condition['block_id'];
            $json_output = $condition['json_output'];
            $json_blocks = json_decode($json_output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error al decodificar json_output.");
            }

            // Buscar el bloque y su padre
            $parent_id = null;
            foreach ($json_blocks['blockarr'] as $block) {
                if ($block['id'] == $block_id) {
                    $parent_id = $block['parent'];
                    break;
                }
            }

            $true = false;
            if ($parent_id === -1) {
                $true = true;
            } else {
                // Verificar interacciones previas
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM interacciones_usuarios 
                    WHERE (tipo_interaccion = 'acciones' AND id_interaccion = ? AND uid_usuario = ?)
                    OR (tipo_interaccion = 'disparadores' AND id_interaccion = ? AND uid_usuario = ?)
                    OR (tipo_interaccion = 'condiciones' AND id_interaccion = ? AND uid_usuario = ?)
                ");
                $stmt->bind_param('iiiiii', $parent_id, $phone_whatsapp_from, $parent_id, $phone_whatsapp_from, $parent_id, $phone_whatsapp_from);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                $true = $count > 0;
            }

            $truth_table[] = ['condition' => $condition, 'true' => $true];
        }

        $debug_log['truth_table'] = $truth_table;

        // Seleccionar la primera condición verdadera
        $selected_condition = null;
        foreach ($truth_table as $entry) {
            if ($entry['true']) {
                $selected_condition = $entry['condition'];
                break;
            }
        }

        $debug_log['selected_condition'] = $selected_condition;

        if (!$selected_condition) {
            throw new Exception("No se encontraron condiciones verdaderas.");
        }

        echo json_encode(["status" => "success", "message" => "Proceso completado.", "debug" => $debug_log]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage(), "debug" => $debug_log]);
    } catch (mysqli_sql_exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage(), "debug" => $debug_log]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Faltan datos para procesar."]);
}
?>