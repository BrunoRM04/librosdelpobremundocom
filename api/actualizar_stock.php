<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require "config.php";

header("Content-Type: application/json; charset=utf-8");

$input = json_decode(file_get_contents('php://input'), true);
$items = $input['items'] ?? [];

if (!is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'items_invalid']);
    exit;
}

try {
    $pdo->beginTransaction();

    $update = $pdo->prepare("UPDATE libros SET stock = stock - :cantidad WHERE id = :id AND stock >= :cantidad");

    foreach ($items as $item) {
        $id = (int)($item['id'] ?? 0);
        $cantidad = (int)($item['cantidad'] ?? 0);

        if ($id <= 0 || $cantidad <= 0) {
            continue;
        }

        $update->execute([
            ':id' => $id,
            ':cantidad' => $cantidad
        ]);

        if ($update->rowCount() === 0) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['ok' => false, 'error' => 'stock_insuficiente', 'id' => $id]);
            exit;
        }
    }

    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'server_error']);
}
