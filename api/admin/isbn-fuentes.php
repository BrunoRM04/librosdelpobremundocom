<?php
ob_start();
$isbnRespuestaEnviada = false;

register_shutdown_function(static function () use (&$isbnRespuestaEnviada) {
    if ($isbnRespuestaEnviada) return;
    if (ob_get_level() > 0) ob_clean();
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'La búsqueda se interrumpió en el servidor. Revisá el registro de errores de PHP.'], JSON_UNESCAPED_UNICODE);
});

function responder_isbn_json($datos, $estado = 200)
{
    global $isbnRespuestaEnviada;
    if (ob_get_level() > 0) ob_clean();
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        http_response_code(500);
        $json = '{"error":"No se pudo generar la respuesta JSON."}';
    }
    echo $json;
    $isbnRespuestaEnviada = true;
    exit;
}

try {
    session_start();
    if (!isset($_SESSION['admin_id'])) responder_isbn_json(['error' => 'Sesión de admin requerida'], 401);
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_SESSION['csrf_admin']) || !is_string($_POST['csrf'] ?? null)
        || !hash_equals($_SESSION['csrf_admin'], $_POST['csrf'])) {
        responder_isbn_json(['error' => 'Solicitud no autorizada'], 403);
    }

    require_once __DIR__ . '/../_shared/openai-isbn.php';
    $isbn = isbn_normalizado($_POST['isbn'] ?? null);
    if ($isbn === '') responder_isbn_json(['error' => 'ISBN inválido'], 400);

    require_once __DIR__ . '/../_shared/config.php';
    $clave = trim((string)($OPENAI_API_KEY ?? ''));
    if ($clave === '') $clave = trim((string)(getenv('OPENAI_API_KEY') ?: ''));
    if ($clave === '') responder_isbn_json(['error' => 'Falta OPENAI_API_KEY en la configuración del servidor'], 503);

    if (function_exists('set_time_limit')) @set_time_limit(120);
    $resultado = buscar_isbn_openai($isbn, $clave, $OPENAI_ISBN_MODEL ?? 'gpt-6-luna');
    responder_isbn_json($resultado, isset($resultado['error']) ? 502 : 200);
} catch (Throwable $error) {
    error_log('ISBN endpoint error: ' . get_class($error) . ' in ' . basename($error->getFile()) . ':' . $error->getLine());
    responder_isbn_json(['error' => 'La búsqueda falló en el servidor. Revisá el registro de errores de PHP.'], 500);
}
