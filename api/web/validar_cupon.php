<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

require __DIR__ . '/../_shared/config.php';

header("Content-Type: application/json; charset=utf-8");

$cupon = trim($_GET['cupon'] ?? '');

if (strlen($cupon) < 2) {
    echo json_encode(['ok' => false, 'mensaje' => 'Cupón inválido']);
    exit;
}

// Convertir a mayúsculas para comparación (insensible a caso)
$cuponNormalizado = strtoupper($cupon);

try {
    $stmt = $pdo->prepare("
        SELECT porcentaje_descuento, activo
        FROM cupones
        WHERE UPPER(nombre_cupon) = ?
        LIMIT 1
    ");
    $stmt->execute([$cuponNormalizado]);
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resultado) {
        echo json_encode(['ok' => false, 'mensaje' => 'Cupón no encontrado']);
        exit;
    }

    if (!$resultado['activo']) {
        echo json_encode(['ok' => false, 'mensaje' => 'Cupón inactivo o expirado']);
        exit;
    }

    echo json_encode([
        'ok' => true,
        'porcentaje' => floatval($resultado['porcentaje_descuento']),
        'mensaje' => 'Cupón aplicado correctamente'
    ]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'mensaje' => 'Error en el servidor']);
}
