<?php
// Busca un ISBN en las agencias ISBN nacionales (catálogos CERLALC) y, si se pide, con IA + búsqueda web.
// GET ?isbn=9789585594609&fuente=agencia|ia
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Sesión de admin requerida']);
    exit;
}

$isbn = isbn_a_13(strtoupper(preg_replace('/[\s-]/', '', (string)($_GET['isbn'] ?? ''))));
if ($isbn === '') {
    http_response_code(400);
    echo json_encode(['error' => 'ISBN inválido']);
    exit;
}

if (($_GET['fuente'] ?? 'agencia') === 'ia') {
    require_once __DIR__ . '/../_shared/config.php';
    $clave = $ANTHROPIC_API_KEY ?? getenv('ANTHROPIC_API_KEY');
    if (!$clave) {
        http_response_code(503);
        echo json_encode(['error' => 'Búsqueda con IA no configurada (falta ANTHROPIC_API_KEY en config.php)']);
        exit;
    }
    $libro = buscar_con_ia($isbn, $clave);
    if ($libro === null) {
        http_response_code(502);
        echo json_encode(['error' => 'La búsqueda con IA no respondió']);
        exit;
    }
    echo json_encode(['items' => $libro ? [$libro] : []], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['items' => buscar_en_agencia($isbn)], JSON_UNESCAPED_UNICODE);

function isbn_a_13($valor)
{
    if (preg_match('/^\d{9}[\dX]$/', $valor)) {
        $suma = 0;
        for ($i = 0; $i < 10; $i++) $suma += ($valor[$i] === 'X' ? 10 : (int)$valor[$i]) * (10 - $i);
        if ($suma % 11 !== 0) return '';
        $valor = '978' . substr($valor, 0, 9);
        $suma = 0;
        for ($i = 0; $i < 12; $i++) $suma += (int)$valor[$i] * ($i % 2 ? 3 : 1);
        return $valor . ((10 - $suma % 10) % 10);
    }
    if (!preg_match('/^97[89]\d{10}$/', $valor)) return '';
    $suma = 0;
    for ($i = 0; $i < 12; $i++) $suma += (int)$valor[$i] * ($i % 2 ? 3 : 1);
    return (10 - $suma % 10) % 10 === (int)$valor[12] ? $valor : '';
}

// Agencias que publican su catálogo con la plataforma de CERLALC (mismo HTML en todas).
function agencia_para($isbn)
{
    $agencias = [
        '978958' => 'https://isbn.camlibro.com.co',
        '978956' => 'https://isbnchile.cl',
        '978607' => 'https://isbnmexico.indautor.cerlalc.org',
        '978968' => 'https://isbnmexico.indautor.cerlalc.org',
        '978970' => 'https://isbnmexico.indautor.cerlalc.org',
        '978612' => 'https://isbn.bnp.gob.pe',
        '9789972' => 'https://isbn.bnp.gob.pe',
    ];
    foreach ($agencias as $prefijo => $url) {
        if (strpos($isbn, (string)$prefijo) === 0) return [$url, strlen((string)$prefijo) - 3];
    }
    return null;
}

function buscar_en_agencia($isbn)
{
    $agencia = agencia_para($isbn);
    if ($agencia === null) return [];
    list($base, $largoGrupo) = $agencia;
    // El buscador sólo encuentra el ISBN escrito con guiones, y el largo del prefijo editorial
    // varía: se prueban las particiones posibles, empezando por las más comunes.
    $grupo = substr($isbn, 3, $largoGrupo);
    $resto = substr($isbn, 3 + $largoGrupo, -1);
    $largos = range(1, strlen($resto) - 1);
    usort($largos, function ($a, $b) use ($resto) {
        $medio = strlen($resto) * 2 / 3;
        return abs($a - $medio) <=> abs($b - $medio);
    });
    $nt = null;
    foreach ($largos as $n) {
        $conGuiones = '978-' . $grupo . '-' . substr($resto, 0, $n) . '-' . substr($resto, $n) . '-' . substr($isbn, -1);
        $html = descargar($base . '/catalogo.php?mode=resultados_rapidos&palabra=' . urlencode($conGuiones));
        if (preg_match('/catalogo\.php\?mode=detalle&(?:amp;)?nt=(\d+)/', $html, $m)) {
            $nt = $m[1];
            break;
        }
    }
    if ($nt === null) return [];
    $html = descargar($base . '/catalogo.php?mode=detalle&nt=' . $nt);
    if ($html === '') return [];
    if (!mb_check_encoding($html, 'UTF-8')) $html = mb_convert_encoding($html, 'UTF-8', 'ISO-8859-1');

    $texto = function ($patron) use ($html) {
        if (!preg_match($patron, $html, $m)) return '';
        $limpio = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return trim(preg_replace('/\s+/u', ' ', $limpio));
    };
    $titulo = $texto('/class="TituloNolink">(.*?)<\/span>/s');
    if ($titulo === '') return [];
    $autores = [];
    if (preg_match('/>Autor:<\/span>.*?<\/table>/s', $html, $bloque)) {
        preg_match_all('/id_autor=\d+">([^<]+)<\/a>/', $bloque[0], $m);
        foreach ($m[1] as $autor) {
            $autor = html_entity_decode($autor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // "Melville, Herman" -> "Herman Melville"
            $partes = array_map('trim', explode(',', $autor, 2));
            $autores[] = count($partes) === 2 ? $partes[1] . ' ' . $partes[0] : $autor;
        }
    }
    $imagen = '';
    if (preg_match('/src="\.\/files\/titulos\/(\d+\.(?:jpe?g|png))"/i', $html, $m)) {
        $imagen = $base . '/files/titulos/' . $m[1];
    }
    $paginas = $texto('/N&uacute;mero de p&aacute;ginas:<\/span><span[^>]*>(.*?)<\/span>/s');
    return [[
        'titulo' => $titulo,
        'autor' => implode(', ', $autores),
        'isbn' => $isbn,
        'editorial' => $texto('/>Editorial:<\/span><span[^>]*>(.*?)<\/span>/s'),
        'numPaginas' => ctype_digit($paginas) ? (int)$paginas : '',
        'descripcion' => $texto('/<h5>Rese&ntilde;a<\/h5>\s*<p[^>]*>(.*?)<\/p>/s'),
        'imagen' => $imagen,
        'fuente' => 'Agencia ISBN',
    ]];
}

function descargar($url)
{
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LibrosPobreMundo/1.0)',
    ]);
    $html = curl_exec($curl);
    $codigo = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    return $codigo === 200 ? (string)$html : '';
}

// Devuelve el libro, [] si la IA no lo encontró, o null si falló la llamada.
function buscar_con_ia($isbn, $clave)
{
    $instrucciones = "Buscá en la web los datos del libro con ISBN $isbn (probá también con guiones y en sitios de "
        . "librerías, editoriales y agencias ISBN). Respondé ÚNICAMENTE con un objeto JSON, sin texto alrededor, con las claves: "
        . "encontrado (boolean), titulo, autor, editorial, numPaginas (número o null), descripcion (sinopsis breve en español, "
        . "máximo 600 caracteres), imagen (URL directa de la portada o \"\"). Usá sólo datos de páginas que mencionen "
        . "exactamente ese ISBN; si no estás seguro de un campo dejalo vacío, y si no encontrás el libro devolvé {\"encontrado\": false}.";
    $mensajes = [['role' => 'user', 'content' => $instrucciones]];
    for ($intento = 0; $intento < 3; $intento++) {
        $respuesta = llamar_claude($clave, $mensajes);
        if ($respuesta === null) return null;
        // El bucle de búsquedas del servidor puede pausarse: se reenvía el turno para que continúe.
        if (($respuesta['stop_reason'] ?? '') !== 'pause_turn') break;
        $mensajes[] = ['role' => 'assistant', 'content' => $respuesta['content']];
    }
    if (($respuesta['stop_reason'] ?? '') === 'refusal') return [];
    $texto = '';
    foreach ($respuesta['content'] ?? [] as $bloque) {
        if (($bloque['type'] ?? '') === 'text') $texto .= $bloque['text'];
    }
    $inicio = strpos($texto, '{');
    $fin = strrpos($texto, '}');
    if ($inicio === false || $fin === false) return [];
    $datos = json_decode(substr($texto, $inicio, $fin - $inicio + 1), true);
    if (!is_array($datos) || empty($datos['encontrado']) || empty($datos['titulo'])) return [];
    $imagen = (string)($datos['imagen'] ?? '');
    return [
        'titulo' => (string)$datos['titulo'],
        'autor' => (string)($datos['autor'] ?? ''),
        'isbn' => $isbn,
        'editorial' => (string)($datos['editorial'] ?? ''),
        'numPaginas' => is_numeric($datos['numPaginas'] ?? null) ? (int)$datos['numPaginas'] : '',
        'descripcion' => (string)($datos['descripcion'] ?? ''),
        'imagen' => preg_match('#^https://#', $imagen) ? $imagen : '',
        'fuente' => 'IA (verificar)',
    ];
}

function llamar_claude($clave, $mensajes)
{
    $cuerpo = [
        'model' => 'claude-opus-5-5',
        'max_tokens' => 4096,
        'output_config' => ['effort' => 'low'],
        'fallbacks' => 'default',
        'tools' => [['type' => 'web_search_20260209', 'name' => 'web_search', 'max_uses' => 4]],
        'messages' => $mensajes,
    ];
    $curl = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($cuerpo, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $clave,
            'anthropic-version: 2023-06-01',
            'anthropic-beta: server-side-fallback-2026-07-01',
        ],
    ]);
    $respuesta = curl_exec($curl);
    $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($respuesta === false || $estado !== 200) {
        error_log('Claude ISBN status: ' . $estado . ' ' . substr((string)$respuesta, 0, 300));
        return null;
    }
    $datos = json_decode($respuesta, true);
    return is_array($datos) ? $datos : null;
}
