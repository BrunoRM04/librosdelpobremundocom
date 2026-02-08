<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../_shared/config.php';

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$titulo = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
$autor = isset($_POST['autor']) ? trim($_POST['autor']) : '';
$isbn = isset($_POST['isbn']) ? trim($_POST['isbn']) : '';
$editorial = isset($_POST['editorial']) ? trim($_POST['editorial']) : '';
$numPaginas = isset($_POST['numPaginas']) ? (int)$_POST['numPaginas'] : 0;
$precio = isset($_POST['precio']) ? (int)$_POST['precio'] : 0;
$descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
$imagen = isset($_POST['imagen']) ? trim($_POST['imagen']) : '';
$stock = isset($_POST['stock']) ? (int)$_POST['stock'] : 0;

if ($id <= 0) {
    echo json_encode(["success" => false, "message" => "ID de libro inválido"]);
    exit;
}

if ($titulo === '' || $autor === '') {
    echo json_encode(["success" => false, "message" => "Título y autor son obligatorios"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("UPDATE libros SET titulo = :titulo, autor = :autor, isbn = :isbn, editorial = :editorial, numPaginas = :numPaginas, precio = :precio, descripcion = :descripcion, imagen = :imagen, stock = :stock WHERE id = :id");
    $result = $stmt->execute([
        ":titulo" => $titulo,
        ":autor" => $autor,
        ":isbn" => $isbn,
        ":editorial" => $editorial,
        ":numPaginas" => $numPaginas,
        ":precio" => $precio,
        ":descripcion" => $descripcion,
        ":imagen" => $imagen,
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
