<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require "config.php";

$q = trim($_GET['q'] ?? '');

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("
SELECT id, titulo, autor, imagen
FROM libros
WHERE 
    titulo LIKE :q OR
    autor LIKE :q OR
    editorial LIKE :q OR
    isbn LIKE :q
LIMIT 30
");

$param = "%$q%";
$stmt->execute([':q' => $param]);

$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header("Content-Type: application/json; charset=utf-8");
echo json_encode($resultados);
