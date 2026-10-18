<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

$host = "localhost";
$db   = "l0012920_pobre";
$user = "l0012920_libros";
$pass = "LibrosDelPobreMundo26";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die($e->getMessage());
}

    $TOKEN   = "8346418426:AAFZfTYM0SZNPf-V7Rj6u35MOxylHtJ_rSE";
    $CHAT_ID = "-1003598037596";
$MERCADOPAGO_ACCESS_TOKEN = 'APP_USR-1506440745694057-040819-c5e9a4b4d20927c43a80e211a197a1e3-1558317026';

// Búsqueda de libros por ISBN en el admin
$GOOGLE_BOOKS_API_KEY = 'AIzaSyCyxvCaVjbkH3yXOMGsEuL__Xrj8pqILU8';
$ANTHROPIC_API_KEY = ''; // opcional: respaldo con IA, dejar vacío si no se usa
