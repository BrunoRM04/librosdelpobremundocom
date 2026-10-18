<?php
header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        throw new RuntimeException('Método no permitido');
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data) || empty($data['carrito']) || !is_array($data['carrito'])) {
        http_response_code(400);
        throw new RuntimeException('El carrito está vacío');
    }

    require_once __DIR__ . '/../_shared/config.php';
    $token = $MERCADOPAGO_ACCESS_TOKEN ?? getenv('MERCADOPAGO_ACCESS_TOKEN');
    if (!is_string($token) || $token === '') {
        http_response_code(503);
        throw new RuntimeException('El pago en línea no está configurado');
    }

    $buscar = $pdo->prepare('SELECT titulo, precio FROM libros WHERE id = ?');
    $items = [];
    $subtotal = 0;
    foreach ($data['carrito'] as $libro) {
        $id = filter_var($libro['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cantidad = filter_var($libro['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || !$cantidad || $cantidad > 100) {
            http_response_code(400);
            throw new RuntimeException('El carrito contiene cantidades inválidas');
        }
        $buscar->execute([$id]);
        $ficha = $buscar->fetch(PDO::FETCH_ASSOC);
        if (!$ficha) {
            http_response_code(409);
            throw new RuntimeException('Un libro ya no está disponible');
        }
        $precio = (int) round((float) $ficha['precio'] * 0.8);
        $subtotal += $precio * $cantidad;
        $items[] = [
            'title' => $ficha['titulo'],
            'quantity' => $cantidad,
            'currency_id' => 'UYU',
            'unit_price' => $precio,
        ];
    }

    $departamento = trim((string) ($data['departamento'] ?? ''));
    $entrega = $data['metodo_entrega'] ?? '';
    if ($departamento === '' || !in_array($entrega, ['punto', 'domicilio'], true)) {
        http_response_code(400);
        throw new RuntimeException('Seleccioná departamento y método de entrega');
    }
    if ($departamento === 'Montevideo') {
        $envio = $entrega === 'domicilio' && $subtotal <= 1500 ? 150 : 0;
    } else {
        $envio = $subtotal > 2000 ? 0 : ($entrega === 'domicilio' ? 280 : 240);
    }
    if ($envio > 0) {
        $items[] = ['title' => 'Costo de envío', 'quantity' => 1, 'currency_id' => 'UYU', 'unit_price' => $envio];
    }

    $preferencia = [
        'items' => $items,
        'payer' => ['email' => (string) ($data['email'] ?? '')],
        'back_urls' => [
            'success' => 'https://librospobremundo.com/exito.html',
            'failure' => 'https://librospobremundo.com/fallo.html',
            'pending' => 'https://librospobremundo.com/pendiente.html',
        ],
        'auto_return' => 'approved',
        'external_reference' => 'pedido-' . bin2hex(random_bytes(8)),
    ];

    $curl = curl_init('https://api.mercadopago.com/checkout/preferences');
    curl_setopt_array($curl, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token, 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($preferencia, JSON_UNESCAPED_UNICODE),
    ]);
    $respuesta = curl_exec($curl);
    $estadoMercadoPago = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    $resultado = is_string($respuesta) ? json_decode($respuesta, true) : null;
    if ($estadoMercadoPago < 200 || $estadoMercadoPago >= 300 || empty($resultado['init_point'])) {
        error_log('Mercado Pago preference HTTP ' . $estadoMercadoPago);
        http_response_code(502);
        throw new RuntimeException('No se pudo generar el pago');
    }
    echo json_encode(['init_point' => $resultado['init_point']]);
} catch (Throwable $e) {
    $estado = http_response_code();
    if ($estado < 400) {
        http_response_code(500);
        error_log('Mercado Pago preference: ' . $e->getMessage());
    }
    echo json_encode(['error' => $estado >= 400 ? $e->getMessage() : 'No se pudo generar el pago']);
}
