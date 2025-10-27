<?php
session_start();

if (isset($_SESSION['id_plataforma'])) {
} else {
    if (isset($_COOKIE['id_plataforma'])) {
        $_SESSION['id_plataforma'] = $_COOKIE['id_plataforma'];
        $_SESSION["user"] = $_COOKIE["user"];
    } else {
        header("Location: login.php");
        exit();
    }
}
$servername = "44.215.62.184";
$dbname = "chat_center";
$username = "shadow"; // Usuario por defecto de XAMPP
$password = "Ahrj45@21"; // Sin contraseña por defecto

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    //echo "Connection successful!";
}
