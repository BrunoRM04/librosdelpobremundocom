<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión de admin requerida']);
    exit;
}
$q = $_GET['q'] ?? '';
if (!is_string($q) || trim($q) === '' || mb_strlen($q, 'UTF-8') > 120) {
    http_response_code(400);
    echo json_encode(['error' => 'Consulta inválida']);
    exit;
}
$parametros = ['q' => trim($q), 'maxResults' => 10];
$clave = getenv('GOOGLE_BOOKS_API_KEY');
if ($clave !== false && $clave !== '') $parametros['key'] = $clave;
$url = 'https://www.googleapis.com/books/v1/volumes?' . http_build_query($parametros);
$curl = curl_init($url);
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 3,
    CURLOPT_TIMEOUT => 8,
    CURLOPT_HTTPHEADER => ['Accept: application/json'],
]);
$respuesta = curl_exec($curl);
$estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
curl_close($curl);
if ($respuesta === false || $estado !== 200) {
    error_log('Google Books status: ' . $estado);
    http_response_code(502);
    echo json_encode(['error' => 'Google Books no está disponible']);
    exit;
}
$datos = json_decode($respuesta, true);
if (!is_array($datos)) {
    http_response_code(502);
    echo json_encode(['error' => 'Respuesta inválida de Google Books']);
    exit;
}
echo json_encode(['items' => $datos['items'] ?? []], JSON_UNESCAPED_UNICODE);
