<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$usuario  = trim($_POST['usuario'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($usuario === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Usuario y contraseña requeridos']);
    exit;
}

try {
    require_once __DIR__ . '/../_shared/config.php';

    $stmt = $pdo->prepare(
        'SELECT id, usuario, password, puntos
         FROM clientes
         WHERE usuario = ?
         LIMIT 1'
    );
    $stmt->execute([$usuario]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
        exit;
    }

    $passwordDB = $cliente['password'];
    $loginOK = false;

    // 1️⃣ Si ya es hash
    if (password_get_info($passwordDB)['algo'] !== 0) {
        if (password_verify($password, $passwordDB)) {
            $loginOK = true;
        }
    } 
    // 2️⃣ Si es texto plano (legacy)
    else {
        if ($password === $passwordDB) {
            $loginOK = true;

            // 🔐 Migrar a hash automáticamente
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $upd = $pdo->prepare('UPDATE clientes SET password = ? WHERE id = ?');
            $upd->execute([$nuevoHash, $cliente['id']]);
        }
    }

    if (!$loginOK) {
        echo json_encode(['success' => false, 'message' => 'Usuario o contraseña incorrectos']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Acceso exitoso',
        'id'      => $cliente['id'],
        'usuario' => $cliente['usuario'],
        'puntos'  => (int)$cliente['puntos']
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
}