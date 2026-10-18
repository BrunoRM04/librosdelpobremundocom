<?php
// Copiar a config.php en el servidor y completar allí. config.php está ignorado por Git.
$host = 'localhost';
$db = 'nombre_base';
$user = 'usuario_base';
$pass = 'clave_base';

$TOKEN = 'token_telegram';
$CHAT_ID = 'id_chat_telegram';
$MERCADOPAGO_ACCESS_TOKEN = 'token_mercadopago';
// Búsqueda de libros por ISBN en el admin (ambas opcionales).
$GOOGLE_BOOKS_API_KEY = ''; // console.cloud.google.com → habilitar "Books API" → Credenciales → Clave de API
$ANTHROPIC_API_KEY = '';    // platform.claude.com → API Keys; se usa sólo si ninguna biblioteca encuentra el ISBN

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
