<?php
$host = "localhost";
$db   = "l0012920_pobre";
$user = "l0012920_libros";
$pass = "LibrosDelPobreMundo26";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("ERROR CONEXION BD");
}
