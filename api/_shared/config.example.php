<?php
// Copiar a config.php en el servidor y completar allí. config.php está ignorado por Git.
$host = 'localhost';
$db = 'nombre_base';
$user = 'usuario_base';
$pass = 'clave_base';

$TOKEN = 'token_telegram';
$CHAT_ID = 'id_chat_telegram';
$MERCADOPAGO_ACCESS_TOKEN = 'token_mercadopago';
// Búsqueda de libros por ISBN en el admin. Preferir OPENAI_API_KEY como variable
// de entorno del servidor. Si no está disponible, configurarla solo en el
// config.php privado, nunca en este archivo ni en Git.
$OPENAI_API_KEY = getenv('OPENAI_API_KEY') ?: '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
