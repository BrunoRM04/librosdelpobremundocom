<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require 'config.php';

$termino = isset($_POST['termino']) ? trim($_POST['termino']) : '';

if ($termino === '') {
    echo json_encode(["success" => false, "message" => "Término de búsqueda requerido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, titulo, autor, isbn, precio, stock 
        FROM libros 
        WHERE titulo LIKE :termino OR isbn LIKE :termino 
        LIMIT 1
    ");
    
    $stmt->execute([":termino" => "%$termino%"]);
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($libro) {
        echo json_encode([
            "success" => true,
            "libro" => [
                "id" => (int)$libro['id'],
                "titulo" => $libro['titulo'],
                "autor" => $libro['autor'],
                "isbn" => $libro['isbn'],
                "precio" => (int)$libro['precio'],
                "stock" => (int)$libro['stock']
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Libro no encontrado"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
}
