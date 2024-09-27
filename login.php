<?php
session_start();
isset($_COOKIE["id_plataforma"]) ? $_SESSION["id_plataforma"] = $_COOKIE["id_plataforma"] : "";
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

<body class="bg-gray-200 min-h-screen grid place-content-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-2xl font-bold text-center">Iniciar sesión</h1>
        <form action="login.php" method="POST" class="mt-4">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>
            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
            </div>
            <div class="mb-4">
                <input type="checkbox" name="remember" id="remember" class="mr-2">
                <label for="remember" class="text-sm font-medium text-gray-700">Recordar mi cuenta</label>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white p-2 rounded-md">Iniciar sesión</button>
        </form>
    </div>

</body>

</html>