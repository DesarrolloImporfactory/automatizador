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
$servername = "ls-8e06bb570f2cc81e5d618c19210a6effa1ee9ab6.czuooq2g4q5f.us-east-2.rds.amazonaws.com";
$dbname = "imporsuitpro_new_mx";
$username = "dbmasteruser"; // Usuario por defecto de XAMPP
$password = "db_82569_soi2uj32_ksn19210a6efczuooq2g4q"; // Sin contraseña por defecto

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    //echo "Connection successful!";
}
