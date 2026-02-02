<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require 'config.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$precio = isset($_POST['precio']) ? (int)$_POST['precio'] : 0;
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de libro inválido"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE libros SET precio = :precio, stock = :stock WHERE id = :id");
    $result = $stmt->execute([
        ":precio" => $precio,
        ":stock" => $stock,
        ":id" => $id
    ]);

    $pdo->commit();

    if ($result) {
        echo json_encode(["success" => true, "message" => "Libro actualizado correctamente"]);
    } else {
        echo json_encode(["success" => false, "message" => "No se pudo actualizar el libro"]);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
}
