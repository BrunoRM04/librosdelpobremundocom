<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../_shared/config.php';

$termino = isset($_POST['termino']) ? trim($_POST['termino']) : '';

if ($termino === '') {
    echo json_encode(["success" => false, "message" => "Término de búsqueda requerido"]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, titulo, autor, isbn, editorial, numPaginas, precio, descripcion, imagen, stock
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
                "editorial" => $libro['editorial'],
                "numPaginas" => isset($libro['numPaginas']) ? (int)$libro['numPaginas'] : null,
                "precio" => isset($libro['precio']) ? (int)$libro['precio'] : null,
                "descripcion" => $libro['descripcion'],
                "imagen" => $libro['imagen'],
                "stock" => isset($libro['stock']) ? (int)$libro['stock'] : null
            ]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Libro no encontrado"]);
    }
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Error de conexión a la base de datos"]);
}
