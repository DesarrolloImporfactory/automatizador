<?php
$servername = "localhost";
$dbname = "alfabusi_automatizador_importsuit";
$username = "alfabusi__automatizador_importsuit"; // Usuario por defecto de XAMPP
$password = "AutomatizadorImportsuit2024!"; // Sin contraseña por defecto

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    //echo "Connection successful!";
}
?>