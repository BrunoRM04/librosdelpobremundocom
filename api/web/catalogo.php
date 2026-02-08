<?php
require __DIR__ . '/../_shared/config.php';

$page = max(1, intval($_GET['page'] ?? 1));
$limit = 1000000;
$offset = ($page-1)*$limit;

$stmt = $pdo->prepare("
SELECT id,titulo,autor,precio,imagen,stock
FROM libros
ORDER BY titulo
LIMIT $limit OFFSET $offset
");
$stmt->execute();

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));