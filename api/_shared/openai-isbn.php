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
        if (($item['type'] ?? '') === 'web_search_call' && ($item['status'] ?? '') === 'completed') $busco = true;
        if (($item['type'] ?? '') !== 'message') continue;
        foreach ($item['content'] ?? [] as $contenido) {
            if (($contenido['type'] ?? '') !== 'output_text') continue;
            $texto .= $contenido['text'] ?? '';
            foreach ($contenido['annotations'] ?? [] as $cita) {
                $url = $cita['url'] ?? '';
                if (($cita['type'] ?? '') === 'url_citation' && is_string($url) && strlen($url) <= 2048 && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https') {
                    $fuentes[$url] = ['url' => $url, 'titulo' => substr((string)($cita['title'] ?? 'Fuente'), 0, 120)];
                }
            }
        }
    }
    if (!$busco || !$fuentes) return null;
    $datos = json_decode(trim($texto), true);
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

function buscar_isbn_openai($isbn, $clave)
{
    $cuerpo = [
        'model' => 'gpt-5-nano',
        'store' => false,
        'tools' => [['type' => 'web_search', 'search_context_size' => 'low']],
        'tool_choice' => 'required',
        'max_tool_calls' => 2,
        'max_output_tokens' => 1600,
        'text' => ['format' => ['type' => 'json_object']],
        'instructions' => 'Buscá en la web la edición exacta del ISBN solicitado. Respondé solamente un objeto JSON con encontrado (booleano), isbn, titulo, autor, editorial, numPaginas (entero o null), descripcion (sinopsis breve en español, máximo 1000 caracteres) e imagen (URL HTTPS directa de la portada o cadena vacía). Usá únicamente datos respaldados por páginas que mencionen exactamente el ISBN. No inventes ni completes campos inciertos; usá cadenas vacías o null. Si no encontrás una coincidencia segura, devolvé encontrado=false y el resto vacío. No incluyas precio ni stock.',
        'input' => 'Buscá los datos bibliográficos y la portada para el ISBN exacto ' . $isbn . '. Devolvé JSON.',
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
    $respuesta = curl_exec($curl);
    $estado = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($respuesta === false || $estado !== 200) {
        error_log('OpenAI ISBN HTTP status: ' . $estado);
        return ['error' => $estado === 429 ? 'OpenAI alcanzó su límite de uso. Probá más tarde.' : 'OpenAI no pudo completar la búsqueda. Revisá la clave y los créditos de la API.'];
    }
    $datos = json_decode($respuesta, true);
    $libro = libro_desde_respuesta_openai($datos, $isbn);
    return $libro ?? ['error' => 'OpenAI no devolvió datos verificables para este ISBN. Cargá el libro manualmente o probá de nuevo.'];
}
