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

// Función para obtener información del bloque padre
function getParentBlockInfo($conn, $id_automatizador, $parent_id) {
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

        // Obtener condiciones vinculadas al id_configuracion y que coincidan con el texto del body
        $stmt = $conn->prepare("
            SELECT c.*, a.id AS id_accion, aut.id AS id_automatizador, aut.json_output
            FROM condiciones c
            LEFT JOIN acciones a ON c.id = a.id_condicion
            LEFT JOIN automatizadores aut ON c.id_automatizador = aut.id
            WHERE aut.id_configuracion = ? AND aut.estado = 1 AND c.texto = ?
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

        // Evaluar las condiciones con el json_output
        $truth_table = [];
        foreach ($conditions as $condition) {
            $json_output = json_decode($condition['json_output'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Error al decodificar json_output.");
            }

            $debug_log['decoded_json_output'][] = $json_output;

            // Obtener los bloques relacionados y vincular el parent
            $block_info = null;
            foreach ($json_output['blocks'] as $block) {
                if ($block['id'] == $condition['block_id']) {
                    $block_info = $block;
                    break;
                }
            }

            if ($block_info !== null) {
                // Buscar información del parent
                $parent_info = getParentBlockInfo($conn, $condition['id_automatizador'], $block_info['parent']);
                if ($parent_info['parent_id'] !== null) {
                    $condition['parent_sql'] = $parent_info;

                    // Verificar en interacciones_usuarios usando parent_sql
                    $sql = "SELECT COUNT(*) FROM interacciones_usuarios WHERE tipo_interaccion = ? AND id_interaccion = ? AND uid_usuario = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param('sis', $parent_info['parent_table'], $parent_info['parent_id'], $phone_whatsapp_from);
                    $stmt->execute();
                    $stmt->bind_result($count);
                    $stmt->fetch();
                    $stmt->close();

                    $condition['parent_interaction_count'] = $count;

                    $debug_log['parent_sql_verification'][] = [
                        'sql' => $sql,
                        'params' => [$parent_info['parent_table'], $parent_info['parent_id'], $phone_whatsapp_from],
                        'count' => $count
                    ];

                    $truth_table[] = ['condition' => $condition, 'true' => $count > 0];
                } else {
                    $condition['parent_sql'] = null;
                    $truth_table[] = ['condition' => $condition, 'true' => false];
                }

                $condition['parent'] = $block_info['parent'];
            } else {
                $truth_table[] = ['condition' => $condition, 'true' => false];
            }
        }

        $debug_log['truth_table'] = $truth_table;

        // Priorizar y seleccionar la primera condición verdadera
        usort($truth_table, function($a, $b) {
            if (!empty($a['condition']['id_accion'])) return -1;
            if (!empty($a['condition']['id'])) return 1;
            if (!empty($a['condition']['id_disparador'])) return 1;
            return 0;
        });

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

        // Decodificar json_bloques del selected_condition
        $json_bloques = json_decode($selected_condition['json_output'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar json_bloques.");
        }

        $debug_log['decoded_json_bloques'] = $json_bloques;

        // Función para obtener bloques descendientes excluyendo aquellos con blockelemtype == 10 y sus descendientes
        function getDescendantBlocks($blockarr, $block_id) {
            $descendants = [];
            $to_visit = [$block_id];
            $exclude_blocks = [];

            while (!empty($to_visit)) {
                $current = array_shift($to_visit);
                foreach ($blockarr as $block) {
                    if ($block['parent'] == $current) {
                        $blockelemtype = null;
                        foreach ($block['data'] as $data) {
                            if ($data['name'] == 'blockelemtype') {
                                $blockelemtype = $data['value'];
                                break;
                            }
                        }
                        if ($blockelemtype === "10") {
                            $exclude_blocks[] = $block['id'];
                            continue;
                        } elseif (!in_array($current, $exclude_blocks)) {
                            $descendants[] = $block;
                            $to_visit[] = $block['id'];
                        }
                    }
                }
            }

            return $descendants;
        }

        // Almacenar las acciones en interacciones_usuarios y mensajes_usuarios
        $descendants = getDescendantBlocks($json_bloques['blocks'], $selected_condition['block_id']);
        foreach ($descendants as $block) {
            $debug_log['block'][] = $block; // Añadimos el bloque al log
        }

        echo json_encode(["status" => "success", "message" => "Proceso completado.", "debug" => $debug_log]);

    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage(), "debug" => $debug_log]);
    } catch (mysqli_sql_exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage(), "debug" => $debug_log]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Faltan datos para procesar.", "debug" => $debug_log]);
}
?>