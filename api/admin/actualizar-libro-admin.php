<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión de admin requerida']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !is_string($_POST['csrf'] ?? null) ||
    !hash_equals($_SESSION['csrf_admin'] ?? '', $_POST['csrf'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Solicitud no autorizada']);
    exit;
}
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$titulo = trim((string)($_POST['titulo'] ?? ''));
$autor = trim((string)($_POST['autor'] ?? ''));
$isbn = trim((string)($_POST['isbn'] ?? ''));
$editorial = trim((string)($_POST['editorial'] ?? ''));
$paginas = trim((string)($_POST['numPaginas'] ?? ''));
$precio = trim((string)($_POST['precio'] ?? ''));
$descripcion = trim((string)($_POST['descripcion'] ?? ''));
$imagen = trim((string)($_POST['imagen'] ?? ''));
$stock = trim((string)($_POST['stock'] ?? ''));
if (!$id || $titulo === '' || $autor === '' || !is_numeric($precio) || (float)$precio < 0 ||
    filter_var($stock, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false ||
    ($paginas !== '' && filter_var($paginas, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Revisá título, autor, precio, páginas y stock']);
    exit;
}
try {
    require_once __DIR__ . '/../_shared/config.php';
    $existe = $pdo->prepare('SELECT 1 FROM libros WHERE id = ?');
    $existe->execute([$id]);
    if (!$existe->fetchColumn()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'El libro ya no existe']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE libros SET titulo = ?, autor = ?, isbn = ?, editorial = ?, numPaginas = ?, precio = ?, descripcion = ?, imagen = ?, stock = ? WHERE id = ?');
    $stmt->execute([$titulo, $autor, $isbn, $editorial, $paginas === '' ? 0 : (int)$paginas,
        (float)$precio, $descripcion, $imagen, (int)$stock, $id]);
    echo json_encode(['success' => true, 'message' => 'Cambios guardados']);
} catch (PDOException $e) {
    error_log('Admin book update: ' . $e->getMessage());
    http_response_code($e->getCode() === '23000' ? 409 : 500);
    echo json_encode(['success' => false, 'message' => $e->getCode() === '23000' ? 'Ya existe un libro con ese ISBN' : 'No se pudo guardar el libro']);
} catch (Throwable $e) {
    error_log('Admin book update: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el libro']);
}
