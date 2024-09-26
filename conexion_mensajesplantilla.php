<?php

include 'db.php';

header('Content-Type: application/json');

// Verificar el método de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['id_automatizador'])) {

        $id_automatizador = $_GET['id_automatizador'];

        // Asume que $conn es tu conexión a la base de datos, asegúrate de inicializarla antes
        $stmt = $conn->prepare("SELECT a.*, c.nombre_configuracion FROM automatizadores a LEFT JOIN configuraciones c ON a.id_configuracion = c.id WHERE a.id = ? LIMIT 1");
        $stmt->bind_param("i", $id_automatizador);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $id_configuracion = $result['id_configuracion'];

        $sql_configuraciones = "SELECT * FROM configuraciones WHERE id='$id_configuracion'";
        $configuracion_result = $conn->query($sql_configuraciones)->fetch_assoc();

        $whatsappBusinessAccountId = $configuracion_result['id_whatsapp'];
        $accessToken = $configuracion_result['token'];

        // Obtener parámetros
        $fields = isset($_GET['fields']) ? $_GET['fields'] : '';
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

        // Construir la URL
        $url = 'https://graph.facebook.com/v20.0/' . $whatsappBusinessAccountId . '/message_templates';
        $params = array(
            'fields' => $fields,
            'limit' => $limit,
            'access_token' => $accessToken
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
            echo json_encode(array('error' => $error_msg));
            exit;
        }

        // Cerrar cURL
        curl_close($ch);

        // Decodificar respuesta JSON
        $responseArray = json_decode($response, true);

        // Verificar si la respuesta contiene datos
        if (isset($responseArray['data'])) {
            echo json_encode($responseArray['data']);
        } else {
            echo json_encode(array());
        }
    } else {
        echo json_encode(array('error' => 'Missing id_automatizador parameter'));
    }

} else {
    echo json_encode(array('error' => 'Invalid request method'));
}
?>