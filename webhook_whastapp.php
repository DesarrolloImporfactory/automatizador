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
$data_msg_whatsapp = json_decode($input, true);

if (!$data_msg_whatsapp) {
    echo json_encode(["status" => "error", "message" => "Datos inválidos."]);
    exit;
}

$debug_log['data_msg_whatsapp'] = $data_msg_whatsapp;

$business_phone_id = $data_msg_whatsapp['entry'][0]['id'] ?? '';
$phone_whatsapp_from = $data_msg_whatsapp['entry'][0]['changes'][0]['value']['messages'][0]['from'] ?? '';
$name_whatsapp_from = $data_msg_whatsapp['entry'][0]['changes'][0]['value']['contacts'][0]['profile']['name'] ?? '';
$respuesta_whatsapp_type = $data_msg_whatsapp['entry'][0]['changes'][0]['value']['messages'][0]['type'] ?? '';
$body = $data_msg_whatsapp['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'] ?? '';

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

        $id_automatizador = $selected_condition['id_automatizador'];
        $parent_id_sql = $selected_condition['parent_sql']['parent_id'];
        $parent_table_sql = $selected_condition['parent_sql']['parent_table'];
        $user_id = $phone_whatsapp_from;

        // Obtener el último registro y extraer user_info
        $sql = "SELECT json_interaccion FROM interacciones_usuarios WHERE tipo_interaccion = ? AND id_interaccion = ? AND uid_usuario = ? ORDER BY created_at DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sis', $parent_table_sql, $parent_id_sql, $user_id);
        $stmt->execute();
        $stmt->bind_result($interaccion_json);
        $stmt->fetch();
        $stmt->close();

        $interaccion_data = json_decode($interaccion_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Error al decodificar interaccion_json.");
        }

        // Ingresar user_info
        $user_info = $interaccion_data['user_info'] ?? null;
        $debug_log['user_info'] = $user_info;

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

        function convertBlockToDetails($block) {
            $block_details = [
                "id" => $block['id'],
                "parent" => $block['parent'],
                "blockelemtype" => null,
                "blockid" => null,
                "class" => null,
                "style" => null
            ];
        
            foreach ($block['data'] as $data) {
                if ($data['name'] == "blockelemtype") {
                    $block_details["blockelemtype"] = $data['value'];
                } elseif ($data['name'] == "blockid") {
                    $block_details["blockid"] = $data['value'];
                }
            }
        
            foreach ($block['attr'] as $attr) {
                if (isset($attr['class'])) {
                    $block_details["class"] = $attr['class'];
                } elseif (isset($attr['style'])) {
                    $block_details["style"] = $attr['style'];
                }
            }
        
            return $block_details;
        }
        
        function getBlocksInfo($conn, $id_automatizador, $block_id) {
            $query_automatizador = "
                SELECT json_bloques
                FROM automatizadores
                WHERE id = ?
            ";
            $stmt_automatizador = $conn->prepare($query_automatizador);
            if ($stmt_automatizador === false) {
                throw new Exception("Falló la preparación de la consulta: " . $conn->error);
            }
            $stmt_automatizador->bind_param('i', $id_automatizador);
            $stmt_automatizador->execute();
            $result_automatizador = $stmt_automatizador->get_result();
            
            if ($result_automatizador->num_rows == 0) {
                throw new Exception("No se encontró el automatizador con el id especificado.");
            }
            
            $automatizador = $result_automatizador->fetch_assoc();
            $json_bloques = $automatizador['json_bloques'];
            $stmt_automatizador->close();
        
            $blocks = json_decode($json_bloques, true);
        
            $block_data = null;
            foreach ($blocks as $block) {
                if ($block['id_block'] == $block_id) {
                    $block_data = $block;
                    break;
                }
            }
        
            if ($block_data === null) {
                throw new Exception("No se encontró el bloque con el id especificado en el JSON.");
            }
        
            $query = "
                SELECT id, block_id, 'disparadores' AS table_name, id, block_id, id_automatizador, tipo, productos, categorias, status, novedad, provincia, ciudad, created_at, updated_at, NULL as id_condicion, NULL as id_disparador, NULL as id_accion, NULL as id_whatsapp_message_template, NULL as asunto, NULL as mensaje, NULL as opciones, NULL as tiempo_envio, NULL as unidad_envio, NULL as tiempo_reenvio, NULL as unidad_reenvio, NULL as reenvios, NULL as cambiar_status, NULL as texto
                FROM disparadores WHERE id_automatizador = ? AND block_id = ?
                UNION ALL
                SELECT id, block_id, 'acciones' AS table_name, id, block_id, id_automatizador, tipo, NULL as productos, NULL as categorias, NULL as status, NULL as novedad, NULL as provincia, NULL as ciudad, created_at, updated_at, id_condicion, id_disparador, id_accion, id_whatsapp_message_template, asunto, mensaje, opciones, tiempo_envio, unidad_envio, tiempo_reenvio, unidad_reenvio, reenvios, cambiar_status, NULL as texto
                FROM acciones WHERE id_automatizador = ? AND block_id = ?
                UNION ALL
                SELECT id, block_id, 'condiciones' AS table_name, id, block_id, id_automatizador, 10 as tipo, NULL as productos, NULL as categorias, NULL as status, NULL as novedad, NULL as provincia, NULL as ciudad, created_at, updated_at, id_accion, id_condicion, id_disparador, NULL as id_whatsapp_message_template, NULL as asunto, NULL as mensaje, NULL as opciones, NULL as tiempo_envio, NULL as unidad_envio, NULL as tiempo_reenvio, NULL as unidad_reenvio, NULL as reenvios, NULL as cambiar_status, texto
                FROM condiciones WHERE id_automatizador = ? AND block_id = ?
            ";
            $stmt = $conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Falló la preparación de la consulta: " . $conn->error);
            }
            $stmt->bind_param('iiiiii', $id_automatizador, $block_id, $id_automatizador, $block_id, $id_automatizador, $block_id);
            $stmt->execute();
            $result = $stmt->get_result();
        
            if ($result->num_rows > 0) {
                $data = $result->fetch_assoc();
                $stmt->close();
        
                foreach ($block_data as $key => $value) {
                    if (!array_key_exists($key, $data) || $data[$key] === null) {
                        $data[$key] = $value;
                    }
                }
        
                return $data;
            }
        
            $stmt->close();
            return null;
        }
        
        // Obtener bloques descendientes
        $descendants = getDescendantBlocks($json_bloques['blocks'], $selected_condition['block_id']);

        foreach ($descendants as $block) {
            $converted_block = convertBlockToDetails($block);
            $debug_log['block'][] = $converted_block; // Convertimos y añadimos el bloque al log
            try {
                $block_info = getBlocksInfo($conn, $id_automatizador, $block['id']); // Obtener información del bloque
                if ($block_info !== null) {
                    // Aquí puedes almacenar $block_info según tus necesidades, por ejemplo, en una base de datos o en un archivo de log
                    
                    $block_details_variable = [
                        'block_id' => $block['id'],
                        'block_table' => $block_info['table_name'],
                        'block_sql_id' => $block_info['id'],
                        'block_sql_data' => $block_info,
                        'parent_block_id' => $block['parent'],
                        'parent_block_table' => $parent_info['parent_table'] ?? null,
                        'parent_block_sql_id' => $parent_info['parent_id'] ?? null,
                        'child_blocks' => $child_blocks ?? null
                    ];

                    $block_details[] = $block_details_variable;

                    $debug_log['block_details'][] = $block_details_variable;
                }
            } catch (Exception $e) {
                // Manejo de errores si no se encuentra el bloque
                $debug_log['errors'][] = $e->getMessage();
            }
        }

        $data['user_info'] = $user_info;
        $data['id_configuracion'] = $id_configuracion;
        $data['id_automatizador'] = $id_automatizador;

        function replacePlaceholders($text, $placeholders) {
            foreach ($placeholders as $key => $value) {
                $text = str_replace("{{{$key}}}", $value, $text);
            }
            return $text;
        }
        
        function getWhatsappMessageTemplate($config) {
            $url = 'https://graph.facebook.com/v20.0/' . $config['id_whatsapp'] . '/message_templates';
            $params = array(
                'access_token' => $config['token']
            );
            $url .= '?' . http_build_query($params);
        
            // Inicializar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
            // Ejecutar la solicitud cURL
            $response = curl_exec($ch);
        
            // Manejar errores de cURL
            if (curl_errno($ch)) {
                $error_msg = curl_error($ch);
                curl_close($ch);
                return array('error' => $error_msg);
            }
        
            // Cerrar cURL
            curl_close($ch);
        
            // Decodificar respuesta JSON
            return json_decode($response, true);
        }
        
        // Function to extract placeholders from the message
        function extract_placeholders($mensaje) {
            preg_match_all('/{{(.*?)}}/', $mensaje, $matches);
            return $matches[1];
        }
        
        function insertMessageDetails($conn, $id_automatizador, $uid_whatsapp, $mensaje, $json_mensaje) {
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
        
            $stmt = $conn->prepare("
                INSERT INTO mensajes_usuarios (id_automatizador, uid_whatsapp, mensaje, rol, json_mensaje, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare the query: " . $conn->error);
            }
        
            // Convert all variables to appropriate types
            $id_automatizador = (int)$id_automatizador;
            $uid_whatsapp = (string)$uid_whatsapp;
            $mensaje = (string)$mensaje;
            $rol = 0; // Assuming 'rol' is always 0 for this function
            $json_mensaje = (string)$json_mensaje;
            $created_at = (string)$created_at;
            $updated_at = (string)$updated_at;
        
            // Bind parameters
            $stmt->bind_param('ississs', $id_automatizador, $uid_whatsapp, $mensaje, $rol, $json_mensaje, $created_at, $updated_at);
            $stmt->execute();
            $stmt->close();
        }
        
        function sendWhatsappMessage($conn, $user_info, $block_sql_data, $config) {
            // Obtener la información del template de mensaje block_sql_data
            $id_whatsapp_message_template = $block_sql_data['id_whatsapp_message_template'];
            
            $template_info = getWhatsappMessageTemplate($config);
            if (isset($template_info['error'])) {
                // Si hay un error, devolverlo
                return "Error al consultar el template de WhatsApp: " . $template_info['error'];
            }
        
            // Buscar la plantilla específica en la respuesta
            $template_name = '';
            $language_code = '';
            foreach ($template_info['data'] as $template) {
                if ($template['id'] == $id_whatsapp_message_template) {
                    $template_name = $template['name'];
                    $language_code = $template['language'];
                    break;
                }
            }
        
            if (empty($template_name) || empty($language_code)) {
                return "No se encontró la plantilla con ID: $id_whatsapp_message_template";
            }
        
            // Configurar el envío del mensaje de WhatsApp
            $url = 'https://graph.facebook.com/v20.0/' . $config['id_telefono'] . '/messages';
            $token = $config['token'];
        
            $recipient = $user_info['celular'];
        
            $mensaje = $block_sql_data['mensaje'];
        
            // Extract placeholders
            if (!function_exists('extract_placeholders')) {
                function extract_placeholders($message) {
                    preg_match_all('/{{(.*?)}}/', $message, $matches);
                    return $matches[1];
                }
            }
        
            $placeholders = extract_placeholders($mensaje);
        
            // Initialize components
            $components = [];
        
            if (!empty($placeholders)) {
                $parameters = [];
        
                // Map placeholders to user_info
                foreach ($placeholders as $placeholder) {
                    if (isset($user_info[$placeholder])) {
                        $parameters[] = [
                            "type" => "text",
                            "text" => $user_info[$placeholder]
                        ];
                    }
                }
        
                if (!empty($parameters)) {
                    $components[] = [
                        "type" => "body",
                        "parameters" => $parameters
                    ];
                }
            }
        
            // Create final data structure
            $data = [
                "messaging_product" => "whatsapp",
                "to" => $recipient,
                "type" => "template",
                "template" => [
                    "name" => $template_name,
                    "language" => [
                        "code" => $language_code
                    ]
                ]
            ];
        
            // Add components if they exist
            if (!empty($components)) {
                $data["template"]["components"] = $components;
            }
        
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        
            $json_mensaje = json_encode($data);
            $respuesta = '';
        
            if ($result === false || $httpCode >= 400) {
                $respuesta = "Error al enviar el mensaje de WhatsApp: " . ($result ? $result : curl_error($ch));
                echo $respuesta;
            } else {
                $respuesta = "Mensaje de WhatsApp enviado a {$user_info['celular']}: " . $result;
                echo $respuesta;
            }
        
            // Insert message details into the database
            insertMessageDetails($conn, $block_sql_data['id_automatizador'], $recipient, $mensaje, $json_mensaje);
        
            return $respuesta;
        }
        
        function sendEmail($user_info, $subject, $message) {
            $subject = replacePlaceholders($subject, $user_info);
            $message = replacePlaceholders($message, $user_info);
            return "Correo enviado a {$user_info['email']} con el asunto '$subject' y el mensaje '$message'";
        }
        
        function changeOrderStatus($order_id, $new_status) {
            return "Estado de la orden $order_id cambiado a $new_status";
        }
        
        function getConfigurations($conn, $id_configuracion) {
            $stmt = $conn->prepare("
                SELECT * FROM configuraciones WHERE id = ?
            ");
            if ($stmt === false) {
                throw new Exception("Falló la preparación de la consulta: " . $conn->error);
            }
            $stmt->bind_param('i', $id_configuracion);
            $stmt->execute();
            $result = $stmt->get_result();
            $config = $result->fetch_assoc();
            $stmt->close();
            
            return $config;
        }
        
        function insertInteractions($conn, $block_details, $id_automatizador, $user_id, $data) {
            $user_id = $data['user_info']['celular'];
            $config = getConfigurations($conn, $data['id_configuracion']);
            $interaction_log = [];
            
            foreach ($block_details as $block) {
                //echo json_encode($block);
                //echo $block['block_sql_data']['tipo'];
                $tipo_interaccion = $block['block_table'] ?? $block['tipo'];
                $id_interaccion = $block['block_sql_id'] ?? $block['id'];
                $json_interaccion = $block['block_sql_data'] ?? null;
                
                if (is_null($tipo_interaccion) || is_null($id_interaccion) || is_null($json_interaccion)) {
                    continue;
                }
                
                $respuesta_accion = '';
                
                if ($block['block_sql_data']['tipo'] == "8") {
                    $respuesta_accion = sendWhatsappMessage($conn, $data['user_info'], $block['block_sql_data'], $config);
                } elseif ($block['block_sql_data']['tipo'] == "7") {
                    $respuesta_accion = sendEmail($data['user_info'], 'subject', 'message');
                } elseif ($block['block_sql_data']['tipo'] == "9") {
                    $respuesta_accion = changeOrderStatus($data['user_info']['order_id'], 'new_status');
                }
                
                $json_interaccion['respuesta_accion'] = $respuesta_accion;
                $json_interaccion['user_info'] = $data['user_info'];
                
                $json_interaccion = json_encode($json_interaccion);
                $created_at = date('Y-m-d H:i:s');
                $updated_at = date('Y-m-d H:i:s');
                
                $stmt = $conn->prepare("
                    INSERT INTO interacciones_usuarios (id_automatizador, tipo_interaccion, id_interaccion, uid_usuario, json_interaccion, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                if ($stmt === false) {
                    throw new Exception("Falló la preparación de la consulta: " . $conn->error);
                }
                $stmt->bind_param('ississs', $id_automatizador, $tipo_interaccion, $id_interaccion, $user_id, $json_interaccion, $created_at, $updated_at);
                $stmt->execute();
                $interaction_log[] = [
                    'id_automatizador' => $id_automatizador,
                    'tipo_interaccion' => $tipo_interaccion,
                    'id_interaccion' => $id_interaccion,
                    'uid_usuario' => $user_id,
                    'json_interaccion' => $json_interaccion,
                    'created_at' => $created_at,
                    'updated_at' => $updated_at
                ];
                $stmt->close();
            }
            return $interaction_log;
        }

        function insertMessageDetailsUser($conn, $id_automatizador, $uid_whatsapp, $mensaje, $json_mensaje) {
            $created_at = date('Y-m-d H:i:s');
            $updated_at = date('Y-m-d H:i:s');
        
            $stmt = $conn->prepare("
                INSERT INTO mensajes_usuarios (id_automatizador, uid_whatsapp, mensaje, rol, json_mensaje, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare the query: " . $conn->error);
            }
        
            // Convert all variables to appropriate types
            $id_automatizador = (int)$id_automatizador;
            $uid_whatsapp = (string)$uid_whatsapp;
            $mensaje = (string)$mensaje;
            $rol = 1; // Assuming 'rol' is always 0 for this function
            $json_mensaje = (string)$json_mensaje;
            $created_at = (string)$created_at;
            $updated_at = (string)$updated_at;
        
            // Bind parameters
            $stmt->bind_param('ississs', $id_automatizador, $uid_whatsapp, $mensaje, $rol, $json_mensaje, $created_at, $updated_at);
            $stmt->execute();
            $stmt->close();
        }

        //ingresar el mensaje del usuario
        $interaction_message_details_user = insertMessageDetailsUser($conn, $id_automatizador, $user_id, $body, json_encode($data_msg_whatsapp));

        // Insert interactions
        $interaction_results = insertInteractions($conn, $block_details, $id_automatizador, $user_id, $data);
        $debug_log['interaction_results'] = $interaction_results;

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
