<?php
session_start();

if (isset($_SESSION['id_plataforma'])) {
} else {
    if (isset($_COOKIE['id_plataforma'])) {
        $_SESSION['id_plataforma'] = $_COOKIE['id_plataforma'];
    } else {
        header("Location: login.php");
        exit();
    }
}
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
