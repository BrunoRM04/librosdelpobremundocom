<?php
header('Content-Type: application/json; charset=utf-8');

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        throw new Exception("No se recibieron datos válidos");
    }
    
    /* ===== Enviar a Telegram (PRIORITARIO) ===== */
    $TOKEN   = "8346418426:AAFZfTYM0SZNPf-V7Rj6u35MOxylHtJ_rSE";
    $CHAT_ID = "-1003598037596";
    
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
    http_response_code(500);
    echo json_encode([
        "ok" => false, 
        "error" => $e->getMessage()
    ]);
}