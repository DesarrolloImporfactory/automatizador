<?php
session_start();

// Eliminar todas las variables de sesión
$_SESSION = [];

// Destruir la sesión
session_destroy();

// Eliminar la cookie si existe
if (isset($_COOKIE['id_plataforma'])) {
    setcookie('id_plataforma', '', time() - 3600, '/');
}

// Redirigir al login
header("Location: login.php");
exit();
