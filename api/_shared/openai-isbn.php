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

function libro_desde_respuesta_openai($respuesta, $isbn)
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
    return [
        'items' => [[
            'titulo' => $titulo,
            'autor' => $campo('autor', 250),
            'isbn' => $isbn,
            'editorial' => $campo('editorial', 250),
            'numPaginas' => $paginas === false ? '' : $paginas,
            'descripcion' => $campo('descripcion', 1000),
            'imagen' => $imagen,
            'fuente' => 'OpenAI + búsqueda web',
        ]],
        'fuentes' => array_slice(array_values($fuentes), 0, 4),
    ];
}

function agregar_fuente_openai(&$fuentes, $url, $titulo)
{
    if (!is_string($url) || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') return;
    $fuentes[$url] = ['url' => $url, 'titulo' => mb_substr(strip_tags((string)$titulo), 0, 120, 'UTF-8')];
}

function buscar_isbn_openai($isbn, $clave, $modelo = 'gpt-6-luna')
{
    $cuerpo = [
        'model' => $modelo,
        'store' => false,
        'reasoning' => ['effort' => 'low'],
        'tools' => [['type' => 'web_search', 'search_context_size' => 'low']],
        'tool_choice' => 'required',
        'max_tool_calls' => 2,
        'max_output_tokens' => 3000,
        'include' => ['web_search_call.action.sources'],
        'instructions' => 'Encontrá la ficha bibliográfica de la edición que coincide con el ISBN exacto. Buscá el número sin guiones y con guiones. Entregá un objeto JSON sin markdown: encontrado booleano, isbn, titulo, autor, editorial, numPaginas entero o null, descripcion breve, imagen URL HTTPS directa o vacía. Dejá vacíos los campos no comprobados; encontrado=true cuando una fuente identifica claramente ese ISBN y título. No inventes. No incluyas precio ni stock.',
        'input' => 'ISBN ' . $isbn,
    ];
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
