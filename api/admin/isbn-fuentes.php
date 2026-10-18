<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión de admin requerida']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf_admin']) || !is_string($_POST['csrf'] ?? null)
    || !hash_equals($_SESSION['csrf_admin'], $_POST['csrf'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Solicitud no autorizada']);
    exit;
}
require_once __DIR__ . '/../_shared/openai-isbn.php';
$isbn = isbn_normalizado($_POST['isbn'] ?? null);
if ($isbn === '') {
    http_response_code(400);
    echo json_encode(['error' => 'ISBN inválido']);
    exit;
}
require_once __DIR__ . '/../_shared/config.php';
$clave = trim((string)($OPENAI_API_KEY ?? ''));
if ($clave === '') $clave = trim((string)(getenv('OPENAI_API_KEY') ?: ''));
if (!$clave) {
    http_response_code(503);
    echo json_encode(['error' => 'Falta OPENAI_API_KEY en la configuración del servidor']);
    exit;
}
$resultado = buscar_isbn_openai($isbn, $clave, $OPENAI_ISBN_MODEL ?? 'gpt-6-luna');
if (isset($resultado['error'])) http_response_code(502);
echo json_encode($resultado, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
