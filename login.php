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
            <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-md">Iniciar sesión</button>
        </form>
    </div>

</body>

</html>