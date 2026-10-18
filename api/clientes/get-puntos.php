<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

header('Content-Type: application/json; charset=utf-8');


$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : (isset($_GET['usuario']) ? trim($_GET['usuario']) : '');
$puntosUsados = isset($_POST['puntos_usados']) ? (int)$_POST['puntos_usados'] : (isset($_GET['puntos_usados']) ? (int)$_GET['puntos_usados'] : 0);

if ($usuario === '') {
    echo json_encode(["success" => false, "message" => "Usuario requerido"]);
    exit;
}

try {
    require_once __DIR__ . '/../_shared/config.php';

    $pdo->beginTransaction();

    if ($puntosUsados > 0) {
        $stmtUpdate = $pdo->prepare("UPDATE clientes SET puntos = GREATEST(puntos - :puntos, 0) WHERE usuario = :usuario");
        $stmtUpdate->execute([":puntos" => $puntosUsados, ":usuario" => $usuario]);
    }

    $stmt = $pdo->prepare("SELECT puntos FROM clientes WHERE usuario = :usuario LIMIT 1");
    $stmt->execute([":usuario" => $usuario]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();

    if ($row) {
        echo json_encode(["success" => true, "puntos" => (int)$row['puntos']]);
    } else {
        echo json_encode(["success" => false, "message" => "Usuario no encontrado"]);
    }
} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(["success" => false, "message" => "Error de conexión"]);
}