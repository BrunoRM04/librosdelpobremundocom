<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');

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
    die(json_encode([
        'success' => false,
        'message' => 'Error de conexión a base de datos'
    ]));
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

$usuario  = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    echo json_encode([
        'success' => false,
        'message' => 'El usuario y contraseña son requeridos'
    ]);
    exit;
}

if (strlen($usuario) < 3) {
    echo json_encode([
        'success' => false,
        'message' => 'El usuario debe tener al menos 3 caracteres'
    ]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode([
        'success' => false,
        'message' => 'La contraseña debe tener al menos 6 caracteres'
    ]);
    exit;
}

// Verificar si el usuario ya existe
try {
    $stmtCheck = $pdo->prepare(
        "SELECT id FROM clientes WHERE usuario = ? LIMIT 1"
    );
    $stmtCheck->execute([$usuario]);

    if ($stmtCheck->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'El usuario ya existe'
        ]);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al verificar usuario'
    ]);
    exit;
}

// 🔐 HASH DE CONTRASEÑA (CAMBIO CLAVE)
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Insertar cliente
try {
    $stmtInsert = $pdo->prepare(
        "INSERT INTO clientes (usuario, password, puntos)
         VALUES (?, ?, 0)"
    );

    $stmtInsert->execute([$usuario, $passwordHash]);

    echo json_encode([
        'success' => true,
        'message' => 'Registro completado exitosamente'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al registrar cliente'
    ]);
}