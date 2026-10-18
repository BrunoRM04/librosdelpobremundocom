<?php
require __DIR__ . '/../_shared/config.php';

$ids = $_GET['ids'] ?? '';
$lista = array_filter(array_map('intval', explode(',', $ids)));

if (!$lista) {
    echo json_encode([]);
    exit;
}

$in = implode(',', array_fill(0, count($lista), '?'));

$stmt = $pdo->prepare("
SELECT id,titulo,autor,precio,imagen,stock
FROM libros
WHERE id IN ($in)
");
$stmt->execute($lista);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
