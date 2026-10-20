<?php

function isbn_normalizado($valor)
{
    if (!is_string($valor)) return '';
    $valor = strtoupper(preg_replace('/[\s-]/', '', $valor));
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

function libro_desde_respuesta_openai($respuesta, $isbn, $obtenerHtml = null)
{
    if (!is_array($respuesta) || ($respuesta['status'] ?? '') !== 'completed') return null;
    $texto = '';
    $fuentes = [];
    $busco = false;
    foreach ($respuesta['output'] ?? [] as $item) {
        if (($item['type'] ?? '') === 'web_search_call' && ($item['status'] ?? '') === 'completed') {
            $busco = true;
            foreach ($item['action']['sources'] ?? [] as $fuente) {
                agregar_fuente_openai($fuentes, $fuente['url'] ?? '', $fuente['title'] ?? 'Fuente web');
            }
        }
        if (($item['type'] ?? '') !== 'message') continue;
        foreach ($item['content'] ?? [] as $contenido) {
            if (($contenido['type'] ?? '') !== 'output_text') continue;
            $texto .= $contenido['text'] ?? '';
            foreach ($contenido['annotations'] ?? [] as $cita) {
                if (($cita['type'] ?? '') === 'url_citation') agregar_fuente_openai($fuentes, $cita['url'] ?? '', $cita['title'] ?? 'Fuente web');
            }
        }
    }
    if (!$busco || !$fuentes) return null;
    $texto = trim($texto);
    if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $texto, $bloque)) $texto = $bloque[1];
    $datos = json_decode($texto, true);
    if (!is_array($datos) || ($datos['encontrado'] ?? false) !== true) return ['items' => [], 'fuentes' => array_values($fuentes)];
    if (isbn_normalizado($datos['isbn'] ?? '') !== $isbn || empty($datos['titulo']) || !is_string($datos['titulo'])) return null;

    $campo = static function ($nombre, $limite) use ($datos) {
        $valor = $datos[$nombre] ?? '';
        return is_string($valor) ? mb_substr(trim(str_replace(['<', '>', '"'], '', strip_tags($valor))), 0, $limite, 'UTF-8') : '';
    };
    $imagen = $campo('imagen', 500);
    if (!filter_var($imagen, FILTER_VALIDATE_URL) || parse_url($imagen, PHP_URL_SCHEME) !== 'https') $imagen = '';
    $paginas = $datos['numPaginas'] ?? null;
    $paginas = filter_var($paginas, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    $titulo = $campo('titulo', 250);
    if ($titulo === '') return null;
    $descripcion = '';
    $descripcionFuente = '';
    $urlDescripcion = $datos['descripcion_url'] ?? '';
    $urls = [];
    if (is_string($urlDescripcion) && isset($fuentes[$urlDescripcion])) $urls[] = $urlDescripcion;
    foreach ($fuentes as $url => $fuente) {
        if (count($urls) >= 3) break;
        if (!in_array($url, $urls, true)) $urls[] = $url;
    }
    foreach ($urls as $url) {
        $html = $obtenerHtml ? $obtenerHtml($url) : descargar_ficha_isbn($url);
        $textoEncontrado = is_string($html) ? descripcion_original_de_ficha($html, $isbn) : '';
        if ($textoEncontrado !== '') {
            $descripcion = $textoEncontrado;
            $descripcionFuente = $url;
            break;
        }
    }
    if ($descripcionFuente !== '') {
        $fuenteElegida = $fuentes[$descripcionFuente];
        unset($fuentes[$descripcionFuente]);
        $fuentes = [$descripcionFuente => $fuenteElegida] + $fuentes;
    }
    return [
        'items' => [[
            'titulo' => $titulo,
            'autor' => $campo('autor', 250),
            'isbn' => $isbn,
            'editorial' => $campo('editorial', 250),
            'numPaginas' => $paginas === false ? '' : $paginas,
            'descripcion' => $descripcion,
            'descripcionFuente' => $descripcionFuente,
            'imagen' => $imagen,
            'fuente' => 'OpenAI + búsqueda web',
        ]],
        'fuentes' => array_slice(array_values($fuentes), 0, 8),
    ];
}

function texto_ficha_normalizado($valor)
{
    $valor = html_entity_decode(strip_tags((string)$valor), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim(preg_replace('/\s+/u', ' ', $valor));
}

function texto_descripcion_original($valor)
{
    $valor = preg_replace('~<br\s*/?>|</(?:p|div|li|h[1-6])\s*>~i', "\n\n", (string)$valor);
    $valor = html_entity_decode(strip_tags($valor), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $lineas = array_map('trim', preg_split('/\R/u', $valor));
    return trim(preg_replace('/\n{3,}/', "\n\n", implode("\n", $lineas)));
}

function descripcion_valida_de_ficha($texto)
{
    $longitud = mb_strlen($texto, 'UTF-8');
    return $longitud >= 60 && $longitud <= 10000
        && !preg_match('/(?:\.\.\.|…|leer m[aá]s|read more)\s*$/iu', trim($texto));
}

function descripcion_original_de_ficha($html, $isbn)
{
    if (!class_exists('DOMDocument') || !is_string($html) || strlen($html) > 4000000) return '';
    $documento = new DOMDocument();
    $estadoAnterior = libxml_use_internal_errors(true);
    $cargado = $documento->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();
    libxml_use_internal_errors($estadoAnterior);
    if (!$cargado) return '';
    $xpath = new DOMXPath($documento);
    $fragmentosPagina = [];
    foreach ($xpath->query('//text()[not(ancestor::script) and not(ancestor::style)]') as $fragmento) {
        $fragmentosPagina[] = $fragmento->nodeValue;
    }
    $textoPagina = texto_ficha_normalizado(implode(' ', $fragmentosPagina));
    preg_match_all('/(?<![A-Za-z0-9])(?:97[89][\s-]?)?(?:\d[\s-]?){9}[\dX](?![A-Za-z0-9])/i', $textoPagina, $coincidencias);
    $isbnPresente = false;
    foreach ($coincidencias[0] as $coincidencia) {
        if (isbn_normalizado($coincidencia) === $isbn) {
            $isbnPresente = true;
            break;
        }
    }
    if (!$isbnPresente) return '';

    $candidatos = [];
    foreach ($xpath->query('//script[@type="application/ld+json"]') as $nodo) {
        $datos = json_decode($nodo->textContent, true);
        if (is_array($datos)) agregar_descripciones_estructuradas($datos, $candidatos);
    }
    foreach ($xpath->query('//*[@itemprop="description" or @id or @class]') as $nodo) {
        $atributos = $nodo->getAttribute('id') . ' ' . $nodo->getAttribute('class');
        $puntaje = 0;
        if (preg_match('/(?:synopsis|sinopsis|contratapa|book-summary)/i', $atributos)) $puntaje = 100;
        elseif (preg_match('/(?:description|resumen)/i', $atributos)) $puntaje = 75;
        elseif ($nodo->getAttribute('itemprop') === 'description') $puntaje = 70;
        if ($puntaje === 0) continue;
        if (preg_match('/(?:review|opinion|comentario)/i', $atributos)) $puntaje -= 50;
        if (preg_match('/(?:body|content|text)/i', $atributos)) $puntaje += 20;
        $texto = texto_descripcion_original($documento->saveHTML($nodo));
        if (descripcion_valida_de_ficha($texto)) $candidatos[] = ['texto' => $texto, 'puntaje' => $puntaje];
    }
    if (!$candidatos) return '';
    usort($candidatos, static function ($a, $b) {
        return $b['puntaje'] <=> $a['puntaje'] ?: mb_strlen($b['texto'], 'UTF-8') <=> mb_strlen($a['texto'], 'UTF-8');
    });
    return $candidatos[0]['texto'];
}

function agregar_descripciones_estructuradas($datos, &$candidatos)
{
    if (!is_array($datos)) return;
    $tipo = $datos['@type'] ?? '';
    if (is_array($tipo)) $tipo = implode(' ', $tipo);
    if (is_string($tipo) && preg_match('/(?:Book|Product)/i', $tipo)
        && isset($datos['description']) && is_string($datos['description'])) {
        $texto = texto_descripcion_original($datos['description']);
        if (descripcion_valida_de_ficha($texto)) $candidatos[] = ['texto' => $texto, 'puntaje' => 85];
    }
    foreach ($datos as $valor) {
        if (is_array($valor)) agregar_descripciones_estructuradas($valor, $candidatos);
    }
}

function descargar_ficha_isbn($url)
{
    $partes = parse_url($url);
    if (!is_array($partes) || ($partes['scheme'] ?? '') !== 'https' || !empty($partes['user'])
        || !empty($partes['pass']) || isset($partes['port']) || empty($partes['host'])) return null;
    $host = $partes['host'];
    if (filter_var($host, FILTER_VALIDATE_IP) || !preg_match('/^[a-z0-9.-]+$/i', $host)) return null;
    $direcciones = gethostbynamel($host);
    if (!$direcciones) return null;
    $direccion = null;
    foreach ($direcciones as $ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $direccion = $ip;
            break;
        }
    }
    if ($direccion === null) return null;
    $contenido = '';
    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 4,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_RESOLVE => [$host . ':443:' . $direccion],
        CURLOPT_HTTPHEADER => ['Accept: text/html'],
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; LibrospobremundoISBN/1.0)',
        CURLOPT_WRITEFUNCTION => static function ($curl, $bloque) use (&$contenido) {
            if (strlen($contenido) + strlen($bloque) > 4000000) return 0;
            $contenido .= $bloque;
            return strlen($bloque);
        },
    ]);
    if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }
    $resultado = curl_exec($curl);
    $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $tipo = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    curl_close($curl);
    return $resultado && $estado === 200 && strpos((string)$tipo, 'text/html') === 0 ? $contenido : null;
}

function agregar_fuente_openai(&$fuentes, $url, $titulo)
{
    if (!is_string($url) || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') return;
    $fuentes[$url] = ['url' => $url, 'titulo' => mb_substr(strip_tags((string)$titulo), 0, 120, 'UTF-8')];
}

function solicitud_isbn_openai($isbn, $modelo)
{
    return [
        'model' => $modelo,
        'store' => false,
        'reasoning' => ['effort' => 'low'],
        'tools' => [['type' => 'web_search', 'search_context_size' => 'low']],
        'tool_choice' => 'required',
        'max_tool_calls' => 2,
        'max_output_tokens' => 2000,
        'include' => ['web_search_call.action.sources'],
        'instructions' => 'Encontrá la ficha bibliográfica de la edición que coincide con el ISBN exacto. Buscá el número sin guiones y con guiones. Entregá un objeto JSON sin markdown: encontrado booleano, isbn, titulo, autor, editorial, numPaginas entero o null, descripcion_url, imagen URL HTTPS directa o vacía. descripcion_url debe ser la URL HTTPS exacta de una ficha pública que muestre ese ISBN y publique la sinopsis o descripción original del libro, preferentemente de la editorial. Incluí esa URL entre las fuentes de la búsqueda web. NO redactes ni copies una descripción: el servidor la extrae directamente de la ficha. Si no encontrás una ficha con sinopsis, dejá descripcion_url vacía. Dejá vacíos los demás campos no comprobados; encontrado=true cuando una fuente identifica claramente ese ISBN y título. No incluyas precio ni stock.',
        'input' => 'ISBN ' . $isbn,
    ];
}

function buscar_isbn_openai($isbn, $clave, $modelo = 'gpt-6-luna')
{
    $cuerpo = solicitud_isbn_openai($isbn, $modelo);
    $curl = curl_init('https://api.openai.com/v1/responses');
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($cuerpo, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $clave,
        ],
    ]);
    if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NATIVE_CA')) {
        curl_setopt($curl, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NATIVE_CA);
    }
    $respuesta = curl_exec($curl);
    $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    $curlErrno = curl_errno($curl);
    curl_close($curl);
    if ($respuesta === false) {
        error_log('OpenAI ISBN cURL error: ' . $curlErrno . ' ' . $curlError);
        return ['error' => $curlErrno === 60
            ? 'PHP no puede validar el certificado HTTPS de OpenAI. Actualizá la CA de cURL en el servidor.'
            : 'No se pudo conectar con OpenAI: ' . mb_substr($curlError, 0, 200, 'UTF-8')];
    }
    if ($estado !== 200) {
        $error = json_decode((string)$respuesta, true)['error'] ?? [];
        $detalle = preg_replace('/sk-[A-Za-z0-9_-]+/', '[clave oculta]', (string)($error['message'] ?? ''));
        $detalle = mb_substr(trim(preg_replace('/\s+/u', ' ', $detalle)), 0, 300, 'UTF-8');
        error_log('OpenAI ISBN HTTP status: ' . $estado . '; type: ' . ($error['type'] ?? '-') . '; code: ' . ($error['code'] ?? '-') . '; param: ' . ($error['param'] ?? '-'));
        if ($estado === 401) return ['error' => 'OpenAI rechazó la clave configurada (401).'];
        if ($estado === 429) return ['error' => 'OpenAI respondió 429: ' . ($detalle ?: 'límite de uso o cuota agotada.')];
        return ['error' => 'OpenAI respondió ' . $estado . ': ' . ($detalle ?: 'no se pudo completar la búsqueda.')];
    }
    $datos = json_decode($respuesta, true);
    $libro = libro_desde_respuesta_openai($datos, $isbn);
    return $libro ?? ['error' => 'OpenAI no devolvió datos verificables para este ISBN. Cargá el libro manualmente o probá de nuevo.'];
}
