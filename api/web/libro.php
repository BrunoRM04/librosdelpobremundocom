<?php
require __DIR__ . '/../_shared/config.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(null);
    exit;
}

$stmt = $pdo->prepare("
SELECT id,titulo,autor,editorial,isbn,numPaginas,precio,descripcion,imagen,stock
FROM libros
WHERE id=?
LIMIT 1
");
$stmt->execute([$id]);

echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
