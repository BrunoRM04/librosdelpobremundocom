<?php
// Producción: sin mostrar errores
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require "config.php";

// Obtener libros ordenados por título
$stmt = $pdo->query("SELECT * FROM libros ORDER BY titulo ASC");
$libros = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Respuesta JSON
header("Content-Type: application/json; charset=utf-8");
echo json_encode($libros);