<?php
session_start();

// Eliminar variables de sesión
$_SESSION = [];
session_destroy();

// Función para borrar cookie en dominio y subdominio
function borrar_cookie($nombre)
{
    setcookie($nombre, '', time() - 3600, '/', '.imporsuitpro.com'); // borra cookie del dominio principal
    setcookie($nombre, '', time() - 3600, '/'); // borra cookie del subdominio actual (por si acaso)
}

// Eliminar cookies compartidas
borrar_cookie('user');
borrar_cookie('id_plataforma');
borrar_cookie('login_time');
borrar_cookie('cargo');
borrar_cookie('id');

// Redirigir
header("Location: login.php");
exit();
