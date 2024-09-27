<?php
session_start();

// Comprobar si la sesión o la cookie ya existen y redirigir si es así
if (isset($_SESSION['id_plataforma'])) {
    header("Location: constructor_automatizador.php");
    exit();
}

if (isset($_COOKIE["id_plataforma"])) {
    $_SESSION["id_plataforma"] = $_COOKIE["id_plataforma"];
    header("Location: constructor_automatizador.php");
    exit();
}
// Procesar el formulario si se ha enviado
if (isset($_POST['email'])) {
    $servername = "localhost";
    $dbname = "imporsuitpro_new";
    $username = "imporsuit_system"; // Usuario por defecto de XAMPP
    $password = "imporsuit_system"; // Sin contraseña por defecto

    $conn = new mysqli($servername, $username, $password, $dbname);


    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } else {
        //echo "Connection successful!";
    }

    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prevenir inyección SQL utilizando consultas preparadas
    $stmt = $conn->prepare("SELECT * FROM users WHERE email_users = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();

        // Verificar contraseña
        if (password_verify($password, $row['con_users'])) {

            // Obtener la plataforma del usuario
            $stmt = $conn->prepare("SELECT * FROM usuario_plataforma WHERE id_usuario = ?");
            $stmt->bind_param('i', $row['id_users']);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();


            // Guardar en la sesión y cookie
            $_SESSION['id_plataforma'] = $row['id_plataforma'];
            setcookie('id_plataforma', $row['id_plataforma'], time() + 60 * 60 * 24 * 30, "/", "", true, true); // Cookie segura (HTTPOnly y Secure)

            // Si el usuario selecciona 'Recordar', guardar una cookie adicional
            if (isset($_POST['remember'])) {
                setcookie('id_plataforma', $row['id_plataforma'], time() + 60 * 60 * 24 * 30, "/", "", true, true);
            }

            // Redirigir al constructor
            header("Location: constructor_automatizador.php");
            exit();
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

<body class="bg-gray-900 min-h-screen flex justify-center items-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="flex justify-center mb-6">
            <!-- Aquí puedes poner tu logo -->
            <img src="path-to-your-logo.png" alt="Logo" class="h-12">
        </div>
        <h1 class="text-3xl font-bold text-center text-gray-800 mb-6">Login</h1>
        <form action="login.php" method="POST">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Correo</label>
                <input type="email" name="email" id="email" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required placeholder="Correo">
            </div>
            <div class="mb-4 relative">
                <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                <input type="password" name="password" id="password" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required placeholder="Contraseña">
                <!-- Icono del ojo para ver contraseña -->
                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-sm leading-5">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-.048.176-.145.516-.358 1.04-.214.526-.53 1.202-1.01 1.84-1.253 1.707-3.248 3.12-8.174 3.12-4.926 0-6.92-1.413-8.174-3.12-.48-.638-.796-1.314-1.01-1.84C2.603 12.516 2.506 12.176 2.458 12z" />
                    </svg>
                </span>
            </div>
            <div class="flex items-center justify-between mb-4">
                <label for="remember" class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="mr-2">
                    <span class="text-sm text-gray-700">Recuérdame</span>
                </label>
                <a href="#" class="text-sm text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white p-3 rounded-md hover:bg-blue-700">Iniciar Sesión</button>
        </form>
        <p class="mt-4 text-sm text-center text-gray-600">
            ¿No tienes una cuenta?
            <a href="#" class="text-blue-600 hover:underline">Regístrate ahora</a>
        </p>
    </div>
</body>

</html>