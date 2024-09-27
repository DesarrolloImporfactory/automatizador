<?php
session_start();
isset($_SESSION['id_plataforma']) ? header("Location: constructor_automatizador.php") : "";

if (isset($_POST['email'])) {
    require 'db.php';

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE email_users = '$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['con_users'])) {

            $sql = "SELECT * FROM usuario_plataforma WHERE id_usuario = " . $row['id'];
            $result = $conn->query($sql);
            $row = $result->fetch_assoc();

            $_SESSION['id_plataforma'] = $row['id_plataforma'];

            if (isset($_POST['remember'])) {
                setcookie('id_plataforma', $row['id'], time() + 60 * 60 * 24 * 30);
            }
            header("Location: constructor_automatizador.php");
        } else {
            echo "Contraseña incorrecta";
        }
    } else {
        echo "Usuario no encontrado";
    }
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <h1 class="text-3xl font-bold underline">
        Hello world!
    </h1>
</body>

</html>