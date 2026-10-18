<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        throw new Exception("No se recibieron datos válidos");
    }

    // Verificar el precio promocional contra el catálogo antes de avisar el pedido.
    $carrito = $data['carrito'] ?? null;
    if (!is_array($carrito) || !$carrito) {
        http_response_code(400);
        throw new Exception('El carrito está vacío');
    }
    require_once __DIR__ . '/../_shared/config.php';
    $buscarLibro = $pdo->prepare('SELECT precio FROM libros WHERE id = ?');
    $subtotalVerificado = 0;
    foreach ($carrito as $item) {
        $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $cantidad = filter_var($item['cantidad'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id || !$cantidad || $cantidad > 100) {
            http_response_code(400);
            throw new Exception('Hay un libro o cantidad inválidos en el carrito');
        }
        $buscarLibro->execute([$id]);
        $precioLista = $buscarLibro->fetchColumn();
        if ($precioLista === false) {
            http_response_code(409);
            throw new Exception('Un libro del carrito ya no está disponible');
        }
        $precioPromocional = (int) round((float) $precioLista * 0.8);
        if (!isset($item['precio']) || !is_numeric($item['precio']) || (float) $item['precio'] !== (float) $precioPromocional) {
            http_response_code(409);
            throw new Exception('Cambió el precio de un libro; actualizá el carrito');
        }
        $subtotalVerificado += $precioPromocional * $cantidad;
    }
    if (!isset($data['total_productos']) || !is_numeric($data['total_productos']) ||
        (float) $data['total_productos'] !== (float) $subtotalVerificado) {
        http_response_code(409);
        throw new Exception('Cambió el subtotal; actualizá el carrito');
    }

    /* ===== Enviar a Telegram (PRIORITARIO) ===== */

    $msg  = "📚 *Nuevo pedido / interés*\n\n";

    $msg .= "👤 *Cliente*\n";
    $msg .= ($data['nombre'] ?? 'Sin nombre') . "\n";
    $msg .= "📞 " . ($data['telefono'] ?? 'Sin teléfono') . "\n";
    $msg .= "📧 " . ($data['email'] ?? 'Sin email') . "\n\n";

    $msg .= "📍 *Dirección*\n";
    $msg .= ($data['direccion'] ?? 'Sin dirección') . "\n";
    $msg .= "CP: " . ($data['codigoPostal'] ?? 'N/A') . "\n";
    $msg .= ($data['departamento'] ?? 'Sin departamento');
    if (!empty($data['barrio'])) {
        $msg .= " – {$data['barrio']}";
    }
    $msg .= "\n\n";

    $msg .= "🚚 *Entrega*\n";
    $msg .= ($data['metodo_entrega'] ?? 'Sin método') . "\n\n";

    $msg .= "💳 *Pago*\n";
    $msg .= ($data['metodo_pago'] ?? 'Sin método') . "\n";
    if (!empty($data['monto_efectivo'])) {
        $msg .= "💵 Paga con: UYU {$data['monto_efectivo']}\n";
    }
    $msg .= "\n";

    $msg .= "🎟️ *Miserias*\n";
    $puntosUsados = $data['puntos_usados'] ?? 0;
    $msg .= ($puntosUsados > 0)
        ? "Usó {$puntosUsados} miserias\n"
        : "No usó miserias\n";
    $msg .= "\n";

    $msg .= "📚 *Libros*\n";
    foreach (($data['carrito'] ?? []) as $l) {
        $titulo = $l['titulo'] ?? 'Sin título';
        $autor = $l['autor'] ?? 'Sin autor';
        $cantidad = $l['cantidad'] ?? 1;
        $precio = $l['precio'] ?? 0;
        $msg .= "• {$titulo} ({$autor}) x{$cantidad} – UYU {$precio}\n";
    }

    $msg .= "\n💰 *Totales*\n";
    $msg .= "Productos: UYU " . ($data['total_productos'] ?? 0) . "\n";
    $msg .= "Envío: UYU " . ($data['costo_envio'] ?? 0) . "\n";
    $msg .= "*TOTAL FINAL: UYU " . ($data['total_final'] ?? 0) . "*\n\n";

    $msg .= "👤 *Usuario registrado*: ";
    $msg .= ($data['usuario_registrado'] ?? false)
        ? "Sí (" . ($data['email_usuario_registrado'] ?? 'Sin email') . ")"
        : "No";
    $msg .= "\n";

    $msg .= "🕒 " . ($data['fecha_hora'] ?? date('Y-m-d H:i:s'));

    $telegramUrl = "https://api.telegram.org/bot$TOKEN/sendMessage?" . http_build_query([
        'chat_id' => $CHAT_ID,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]);

    $telegramResponse = @file_get_contents($telegramUrl);

    if ($telegramResponse === false) {
        throw new Exception("Error al enviar mensaje a Telegram");
    }

    /* ===== Intentar guardar en BD (OPCIONAL) ===== */
    try {
        require_once __DIR__ . '/../_shared/config.php';

        $stmt = $pdo->prepare("
            INSERT INTO ventas
            (nombre, email, telefono, direccion, departamento, metodo_entrega, metodo_pago, carrito, total)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $data['nombre'] ?? '',
            $data['email'] ?? '',
            $data['telefono'] ?? '',
            $data['direccion'] ?? '',
            $data['departamento'] ?? '',
            $data['metodo_entrega'] ?? '',
            $data['metodo_pago'] ?? '',
            json_encode($data['carrito'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['total'] ?? 0
        ]);
    } catch (Exception $dbError) {
        // Si falla la BD, continuamos igual (ya enviamos a Telegram)
        error_log("Error al guardar en BD: " . $dbError->getMessage());
    }

    echo json_encode([
        "ok" => true,
        "message" => "Pedido procesado correctamente"
    ]);

} catch (Exception $e) {
    $estado = http_response_code();
    if ($estado < 400) {
        http_response_code(500);
        error_log('Pedido: ' . $e->getMessage());
    }
    echo json_encode([
        "ok" => false,
        "error" => $estado >= 400 ? $e->getMessage() : 'No se pudo procesar el pedido'
    ]);
}
