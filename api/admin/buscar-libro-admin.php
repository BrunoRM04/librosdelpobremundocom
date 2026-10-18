<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión de admin requerida']);
    exit;
}
try {
    require_once __DIR__ . '/../_shared/config.php';
    $valor = $_POST['termino'] ?? $_GET['termino'] ?? '';
    if (!is_string($valor) || mb_strlen($valor, 'UTF-8') > 120) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Búsqueda inválida']);
        exit;
    }
    $termino = trim($valor);
    $pagina = filter_var($_POST['pagina'] ?? $_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($pagina === false || $pagina > 10000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Página inválida']);
        exit;
    }
    $sql = 'SELECT id, titulo, autor, isbn, editorial, numPaginas, precio, descripcion, imagen, stock FROM libros';
    $where = '';
    $params = [];
    $palabras = preg_split('/\s+/u', $termino, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    if ($palabras) {
        $condiciones = [];
        foreach ($palabras as $palabra) {
            $patron = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $palabra) . '%';
            $isbnNormalizado = str_replace(['-', ' '], '', $palabra);
            $patronISBN = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $isbnNormalizado) . '%';
            $condiciones[] = "(titulo LIKE ? ESCAPE '!' OR autor LIKE ? ESCAPE '!' OR editorial LIKE ? ESCAPE '!' OR isbn LIKE ? ESCAPE '!' OR REPLACE(REPLACE(isbn, '-', ''), ' ', '') LIKE ? ESCAPE '!' OR id = ?)";
            array_push($params, $patron, $patron, $patron, $patron, $patronISBN, ctype_digit($palabra) ? (int)$palabra : -1);
        }
        $where = ' WHERE ' . implode(' AND ', $condiciones);
        $sql .= $where;
        $isbnLimpio = preg_replace('/[\s-]/', '', $termino);
        if (ctype_digit($isbnLimpio)) {
            $sql .= ' ORDER BY (id = ?) DESC, (REPLACE(REPLACE(isbn, \'-\', \'\'), \' \', \'\') = ?) DESC, titulo ASC';
            $params[] = (int)$isbnLimpio;
            $params[] = $isbnLimpio;
        } else {
            $sql .= ' ORDER BY titulo ASC';
        }
    } else {
        $sql .= ' ORDER BY id DESC';
    }
    $conteo = $pdo->prepare('SELECT COUNT(*) FROM libros' . $where);
    $conteo->execute(array_slice($params, 0, count($palabras) * 6));
    $total = (int) $conteo->fetchColumn();
    $porPagina = 100;
    $paginas = max(1, (int) ceil($total / $porPagina));
    $pagina = min($pagina, $paginas);
    $offset = ($pagina - 1) * $porPagina;
    $stmt = $pdo->prepare($sql . " LIMIT $porPagina OFFSET $offset");
    $stmt->execute($params);
    echo json_encode(['success' => true, 'libros' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'pagina' => $pagina, 'paginas' => $paginas], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Admin book search: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo consultar el catálogo']);
}
