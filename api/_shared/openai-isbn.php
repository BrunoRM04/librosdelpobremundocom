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
    if (isbn_normalizado($datos['isbn'] ?? '') !== $isbn) return null;

    $campo = static function ($nombre, $limite) use ($datos) {
        $valor = $datos[$nombre] ?? '';
        return is_string($valor) ? mb_substr(trim(str_replace(['<', '>', '"'], '', strip_tags($valor))), 0, $limite, 'UTF-8') : '';
    };
    $descripcion = '';
    $descripcionFuente = '';
    $metadatos = [];
    $fuentesVerificadas = [];
    $urls = [];
    foreach ([$datos['descripcion_url'] ?? '', $datos['ficha_url'] ?? ''] as $url) {
        if (is_string($url) && isset($fuentes[$url]) && puntaje_fuente_libro($url, $fuentes[$url]['titulo'], $isbn) >= 0) $urls[$url] = true;
    }
    foreach (is_array($datos['fichas'] ?? null) ? $datos['fichas'] : [] as $url) {
        if (is_string($url) && isset($fuentes[$url]) && puntaje_fuente_libro($url, $fuentes[$url]['titulo'], $isbn) >= 0) $urls[$url] = true;
    }
    $fuentesOrdenadas = $fuentes;
    uasort($fuentesOrdenadas, static function ($a, $b) use ($isbn) {
        return puntaje_fuente_libro($b['url'], $b['titulo'], $isbn) <=> puntaje_fuente_libro($a['url'], $a['titulo'], $isbn);
    });
    foreach ($fuentesOrdenadas as $url => $fuente) {
        if (count($urls) >= 4) break;
        if (puntaje_fuente_libro($url, $fuente['titulo'], $isbn) >= 0) $urls[$url] = true;
    }
    foreach (array_slice(array_keys($urls), 0, 4) as $url) {
        $html = $obtenerHtml ? $obtenerHtml($url) : descargar_ficha_isbn($url);
        $datosFicha = [];
        $textoEncontrado = is_string($html) ? descripcion_original_de_ficha($html, $isbn, $datosFicha) : '';
        if (!$datosFicha && $textoEncontrado === '') continue;
        $fuentesVerificadas[$url] = $fuentes[$url];
        foreach ($datosFicha as $nombre => $valor) {
            if ($valor === '') continue;
            if ($nombre === 'titulo' && !empty($metadatos['titulo'])
                && strpos($metadatos['titulo'], '|') !== false
                && strpos($valor, '|') === false
                && stripos($metadatos['titulo'], $valor) !== false) {
                $metadatos['titulo'] = $valor;
            } elseif (empty($metadatos[$nombre])) {
                $metadatos[$nombre] = $valor;
            }
        }
        if ($descripcion === '' && $textoEncontrado !== '') {
            $descripcion = $textoEncontrado;
            $descripcionFuente = $url;
        }
        if ($descripcion !== '' && !empty($metadatos['autor']) && !empty($metadatos['editorial'])
            && !empty($metadatos['numPaginas']) && !empty($metadatos['imagen'])) break;
    }
    $titulo = $metadatos['titulo'] ?? $campo('titulo', 250);
    $tituloModelo = $campo('titulo', 250);
    if (strpos($titulo, '|') !== false) {
        $tituloPagina = trim(preg_replace('/\s*\|.*$/u', '', $titulo));
        $titulo = $tituloModelo !== '' && strpos($tituloModelo, '|') === false
            && (stripos($tituloPagina, $tituloModelo) !== false || stripos($tituloModelo, $tituloPagina) !== false)
            ? $tituloModelo : $tituloPagina;
    }
    if ($titulo === '') return null;
    $autor = $metadatos['autor'] ?? $campo('autor', 250);
    $autor = preg_replace('/\s*\((?:adaptaci[oó]n|seg[uú]n|atribuida|ilustraci[oó]n)\b[^)]*\)/iu', '', $autor);
    $editorial = $metadatos['editorial'] ?? $campo('editorial', 250);
    $paginas = $metadatos['numPaginas'] ?? filter_var($datos['numPaginas'] ?? null, FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1, 'max_range' => 10000]]);
    $imagen = $metadatos['imagen'] ?? $campo('imagen', 500);
    if (!filter_var($imagen, FILTER_VALIDATE_URL) || parse_url($imagen, PHP_URL_SCHEME) !== 'https') $imagen = '';
    $descripcionTipo = 'original';
    if ($descripcion === '') {
        $generada = $datos['descripcion_generada'] ?? '';
        $generada = is_string($generada) ? trim(strip_tags($generada)) : '';
        if ($autor !== '' && mb_strlen($generada, 'UTF-8') >= 500 && mb_strlen($generada, 'UTF-8') <= 2500) {
            $descripcion = $generada;
            $descripcionTipo = 'generada';
        } else {
            $descripcionTipo = 'sin_descripcion';
        }
    }
    $fuentes = $fuentesVerificadas + array_filter($fuentes, static function ($fuente) use ($isbn) {
        return puntaje_fuente_libro($fuente['url'], $fuente['titulo'], $isbn) >= 0
            && strpos(str_replace('-', '', $fuente['url']), $isbn) !== false;
    });
    return [
        'items' => [[
            'titulo' => $titulo,
            'autor' => $autor,
            'isbn' => $isbn,
            'editorial' => $editorial,
            'numPaginas' => $paginas === false ? '' : $paginas,
            'descripcion' => $descripcion,
            'descripcionFuente' => $descripcionFuente,
            'descripcionTipo' => $descripcionTipo,
            'imagen' => $imagen,
            'fuente' => 'OpenAI + fichas web',
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

function descripcion_original_de_ficha($html, $isbn, &$metadatos = null)
{
    $metadatos = [];
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
        if (is_array($datos)) {
            agregar_descripciones_estructuradas($datos, $isbn, $candidatos);
            agregar_metadatos_estructurados($datos, $isbn, $metadatos);
        }
    }
    unset($metadatos['_puntaje']);
    if ($metadatos && empty($metadatos['imagen'])) {
        $ogImagen = $xpath->query('//meta[@property="og:image"]')->item(0);
        $urlImagen = $ogImagen ? $ogImagen->getAttribute('content') : '';
        if (filter_var($urlImagen, FILTER_VALIDATE_URL) && parse_url($urlImagen, PHP_URL_SCHEME) === 'https') {
            $metadatos['imagen'] = $urlImagen;
        }
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

function agregar_descripciones_estructuradas($datos, $isbn, &$candidatos)
{
    if (!is_array($datos)) return;
    $tipo = $datos['@type'] ?? '';
    if (is_array($tipo)) $tipo = implode(' ', $tipo);
    if (is_string($tipo) && preg_match('/(?:Book|Product)/i', $tipo)
        && isset($datos['description']) && is_string($datos['description'])
        && (!isset($datos['isbn']) || isbn_normalizado((string)$datos['isbn']) === $isbn)) {
        $texto = texto_descripcion_original($datos['description']);
        if (descripcion_valida_de_ficha($texto)) $candidatos[] = ['texto' => $texto, 'puntaje' => 85];
    }
    foreach ($datos as $valor) {
        if (is_array($valor)) agregar_descripciones_estructuradas($valor, $isbn, $candidatos);
    }
}

function nombre_estructurado_isbn($valor)
{
    if (is_string($valor)) return mb_substr(texto_ficha_normalizado($valor), 0, 250, 'UTF-8');
    if (!is_array($valor)) return '';
    if (isset($valor['name'])) return nombre_estructurado_isbn($valor['name']);
    $nombres = [];
    foreach ($valor as $item) {
        $nombre = nombre_estructurado_isbn($item);
        if ($nombre !== '') $nombres[$nombre] = true;
    }
    return mb_substr(implode('; ', array_keys($nombres)), 0, 250, 'UTF-8');
}

function imagen_estructurada_isbn($valor)
{
    if (is_array($valor)) $valor = $valor['url'] ?? $valor['contentUrl'] ?? $valor[0] ?? '';
    if (!is_string($valor)) return '';
    return filter_var($valor, FILTER_VALIDATE_URL) && parse_url($valor, PHP_URL_SCHEME) === 'https'
        ? mb_substr($valor, 0, 500, 'UTF-8') : '';
}

function agregar_metadatos_estructurados($datos, $isbn, &$mejor)
{
    if (!is_array($datos)) return;
    $tipo = $datos['@type'] ?? '';
    if (is_array($tipo)) $tipo = implode(' ', $tipo);
    if (is_string($tipo) && preg_match('/(?:Book|Product)/i', $tipo)
        && (!isset($datos['isbn']) || isbn_normalizado((string)$datos['isbn']) === $isbn)) {
        $paginas = filter_var($datos['numberOfPages'] ?? null, FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 10000]]);
        $candidato = [
            'titulo' => nombre_estructurado_isbn($datos['name'] ?? ''),
            'autor' => nombre_estructurado_isbn($datos['author'] ?? ''),
            'editorial' => nombre_estructurado_isbn($datos['publisher'] ?? ''),
            'numPaginas' => $paginas === false ? '' : $paginas,
            'imagen' => imagen_estructurada_isbn($datos['image'] ?? ''),
        ];
        $puntaje = (isset($datos['isbn']) ? 20 : 0) + (stripos($tipo, 'Book') !== false ? 5 : 0);
        foreach ($candidato as $valor) if ($valor !== '' && $valor !== null) $puntaje += 5;
        if ($puntaje > ($mejor['_puntaje'] ?? 0)) {
            $candidato['_puntaje'] = $puntaje;
            $mejor = $candidato;
        }
    }
    foreach ($datos as $valor) {
        if (is_array($valor)) agregar_metadatos_estructurados($valor, $isbn, $mejor);
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

function puntaje_fuente_libro($url, $titulo, $isbn)
{
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    $ruta = strtolower((string)parse_url($url, PHP_URL_PATH));
    if (preg_match('/(?:^|\.)(?:isbn\.org|isbnsearch\.org|isbn-international\.org|isbnlookup\.com|scribd\.com)$/', $host)
        || preg_match('/\.(?:pdf|docx?|xlsx?)$/', $ruta) || $ruta === '' || $ruta === '/') return -1;
    $puntaje = 0;
    if (strpos(str_replace('-', '', $url), $isbn) !== false) $puntaje += 70;
    if (preg_match('~(?:libro|book|product|producto|/p/|/item/)~i', $ruta)) $puntaje += 50;
    if (preg_match('/(?:librer|editorial|bookstore|books|libro)/i', $host)) $puntaje += 25;
    if (preg_match('/(?:librer|editorial|sinopsis|libro|book)/iu', (string)$titulo)) $puntaje += 15;
    return $puntaje;
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
        'tools' => [[
            'type' => 'web_search',
            'search_context_size' => 'medium',
            'filters' => ['blocked_domains' => ['isbn.org', 'isbnsearch.org', 'isbn-international.org', 'isbnlookup.com']],
        ]],
        'tool_choice' => 'required',
        'max_tool_calls' => 3,
        'max_output_tokens' => 3200,
        'include' => ['web_search_call.action.sources'],
        'instructions' => 'Sos un catalogador de libros. Buscá en la web páginas DE PRODUCTO de librerías, editoriales y distribuidores que muestren el ISBN exacto de la edición. Priorizá fichas que incluyan autor, editorial, número de páginas, portada y sinopsis. No uses páginas para crear/registrar ISBN, listados de códigos, PDFs de pedidos ni resultados que solo dicen título e ISBN. Hacé búsquedas con el ISBN sin guiones y con guiones junto a palabras como librería, editorial, libro, sinopsis, páginas, autor y comprar. Abrí fichas de producto; si una fuente es pobre, seguí buscando otra. Entregá solo JSON válido sin markdown: encontrado booleano, isbn, titulo, autor, editorial, numPaginas entero o null, imagen URL HTTPS directa o vacía, fichas array de hasta 4 URLs HTTPS de páginas de producto consultadas, descripcion_url URL HTTPS de la mejor ficha con sinopsis o vacía, descripcion_generada texto de 3 a 4 párrafos y al menos 500 caracteres. La descripcion_generada se usará SOLO si el servidor no puede extraer ninguna sinopsis original: redactala en español usando únicamente hechos corroborados de esta edición y de la trama en las fichas; no inventes escenas, características físicas, premios ni reseñas. Si las fuentes no aportan suficiente información para esa redacción, dejala vacía. Los otros campos deben corresponder exactamente al ISBN; no incluyas precio ni stock.',
        'input' => 'ISBN ' . $isbn . '. Buscá fichas comerciales reales de esta edición, verificá el ISBN en la página y completá sus datos.',
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
