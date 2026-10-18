<?php
// Copiar a config.php y completar allí los valores de la instalación.
$host = 'localhost';
$db = 'nombre_base';
$user = 'usuario_base';
$pass = 'clave_base';

$TOKEN = 'token_telegram';
$CHAT_ID = 'id_chat_telegram';
$MERCADOPAGO_ACCESS_TOKEN = 'token_mercadopago';
// Búsqueda de libros por ISBN en el admin. Configurá la clave en config.php.
$OPENAI_API_KEY = '';
$OPENAI_ISBN_MODEL = 'gpt-6-luna';

$pdo = new PDO(
    "mysql:host=$host;dbname=$db;charset=utf8mb4",
    $user,
    $pass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
