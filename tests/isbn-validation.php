<?php
require_once __DIR__ . '/../api/_shared/openai-isbn.php';

function verificar_isbn($condicion, $mensaje)
{
    if (!$condicion) throw new RuntimeException($mensaje);
}

$isbn = '9780306406157';
verificar_isbn(isbn_normalizado('978-0-306-40615-7') === $isbn, 'ISBN-13 con guiones');
verificar_isbn(isbn_normalizado('0-306-40615-2') === $isbn, 'ISBN-10 a ISBN-13');
verificar_isbn(isbn_normalizado('9780306406158') === '', 'ISBN con control incorrecto');

$descripcionOriginal = str_repeat('Esta es la descripción original completa de la edición, con todos sus detalles. ', 18);
$url = 'https://example.org/libro';
$respuesta = [
    'status' => 'completed',
    'output' => [
        ['type' => 'web_search_call', 'status' => 'completed', 'action' => [
            'sources' => [['url' => $url, 'title' => 'Ficha editorial']],
        ]],
        ['type' => 'message', 'content' => [[
            'type' => 'output_text',
            'text' => json_encode([
                'encontrado' => true, 'isbn' => $isbn, 'titulo' => 'Libro de prueba',
                'autor' => 'Autora', 'descripcion' => 'Resumen inventado por la IA.',
                'descripcion_url' => $url,
            ]),
        ]]],
    ],
];
$html = '<html><body><span>ISBN ' . $isbn . '</span><script type="application/ld+json">'
    . json_encode(['@type' => 'Book', 'description' => $descripcionOriginal], JSON_UNESCAPED_UNICODE)
    . '</script></body></html>';
$libro = libro_desde_respuesta_openai($respuesta, $isbn, static fn($url) => $html);
verificar_isbn($libro['items'][0]['descripcion'] === trim($descripcionOriginal),
    'La sinopsis debe salir completa de la ficha, sin el resumen de la IA ni corte de 1000 caracteres');
verificar_isbn($libro['items'][0]['descripcionFuente'] === $url, 'Guardar la URL de la descripción');
verificar_isbn($libro['items'][0]['descripcionTipo'] === 'original', 'Distinguir la sinopsis original');

$htmlConFicha = '<html><body><span>ISBN ' . $isbn . '</span><script type="application/ld+json">'
    . json_encode([
        '@type' => ['Book', 'Product'], 'isbn' => $isbn, 'name' => 'Libro de prueba: edición gráfica',
        'author' => ['name' => 'Autora verificada'], 'publisher' => ['name' => 'Editorial verificada'],
        'numberOfPages' => 88, 'image' => 'https://example.org/portada.webp',
        'description' => $descripcionOriginal,
    ], JSON_UNESCAPED_UNICODE) . '</script></body></html>';
$datosEscasos = json_decode($respuesta['output'][1]['content'][0]['text'], true);
$datosEscasos['autor'] = '';
$datosEscasos['fichas'] = [$url];
$soloTitulo = $respuesta;
$soloTitulo['output'][1]['content'][0]['text'] = json_encode($datosEscasos);
$completo = libro_desde_respuesta_openai($soloTitulo, $isbn, static fn($url) => $htmlConFicha)['items'][0];
verificar_isbn($completo['titulo'] === 'Libro de prueba: edición gráfica' && $completo['autor'] === 'Autora verificada'
    && $completo['editorial'] === 'Editorial verificada' && $completo['numPaginas'] === 88
    && $completo['imagen'] === 'https://example.org/portada.webp',
    'Completar todos los campos desde la ficha comercial del ISBN');

$htmlTituloTienda = '<html><body><span>ISBN ' . $isbn . '</span><script type="application/ld+json">'
    . json_encode(['@type' => 'Book', 'isbn' => $isbn,
        'name' => 'Libro de prueba, Autora - Edición gráfica | Librería ejemplo',
        'description' => $descripcionOriginal], JSON_UNESCAPED_UNICODE) . '</script></body></html>';
$tituloLimpio = libro_desde_respuesta_openai($respuesta, $isbn, static fn($url) => $htmlTituloTienda)['items'][0];
verificar_isbn($tituloLimpio['titulo'] === 'Libro de prueba',
    'Usar el título bibliográfico sin el título SEO de la librería');

$datosGenerados = json_decode($respuesta['output'][1]['content'][0]['text'], true);
$datosGenerados['descripcion_generada'] = str_repeat('Párrafo de descripción elaborado a partir de los datos comprobados del libro. ', 10);
$conFallback = $respuesta;
$conFallback['output'][1]['content'][0]['text'] = json_encode($datosGenerados);
$generado = libro_desde_respuesta_openai($conFallback, $isbn, static fn($url) => null)['items'][0];
verificar_isbn($generado['descripcionTipo'] === 'generada' && mb_strlen($generado['descripcion'], 'UTF-8') >= 500
    && $generado['descripcionFuente'] === '', 'Usar descripción extensa marcada como generada si falta la original');
verificar_isbn(puntaje_fuente_libro('https://isbnsearch.org/isbn/' . $isbn, 'ISBN', $isbn) < 0,
    'Descartar registradores de ISBN como ficha comercial');

$htmlVisible = '<html><body><span>ISBN ' . $isbn . '</span><div class="sinopsis-body"><p>'
    . str_repeat('Primer párrafo de la descripción original. ', 4) . '</p><p>'
    . str_repeat('Segundo párrafo de la descripción original. ', 4) . '</p></div></body></html>';
$visible = libro_desde_respuesta_openai($respuesta, $isbn, static fn($url) => $htmlVisible)['items'][0]['descripcion'];
verificar_isbn(str_contains($visible, "\n\n") && str_contains($visible, 'Segundo párrafo'),
    'Extraer la sinopsis visible con sus párrafos');

$otraEdicion = str_replace($isbn, '9780140328721', $htmlVisible);
verificar_isbn(libro_desde_respuesta_openai($respuesta, $isbn, static fn($url) => $otraEdicion)['items'][0]['descripcion'] === '',
    'No usar la sinopsis de otra edición');
$incompleta = '<html><body><span>ISBN ' . $isbn . '</span><div class="sinopsis">'
    . str_repeat('Fragmento de la sinopsis. ', 5) . 'Leer más</div></body></html>';
verificar_isbn(libro_desde_respuesta_openai($respuesta, $isbn, static fn($url) => $incompleta)['items'][0]['descripcion'] === '',
    'No usar un fragmento truncado');

$sinFuentes = $respuesta;
$sinFuentes['output'][0]['action']['sources'] = [];
verificar_isbn(libro_desde_respuesta_openai($sinFuentes, $isbn) === null, 'No aceptar datos sin fuentes');
$sinBusqueda = $respuesta;
unset($sinBusqueda['output'][0]);
verificar_isbn(libro_desde_respuesta_openai($sinBusqueda, $isbn) === null, 'No aceptar datos sin búsqueda web');
$isbnDistinto = $respuesta;
$datosDistintos = json_decode($isbnDistinto['output'][1]['content'][0]['text'], true);
$datosDistintos['isbn'] = '9780140328721';
$isbnDistinto['output'][1]['content'][0]['text'] = json_encode($datosDistintos);
verificar_isbn(libro_desde_respuesta_openai($isbnDistinto, $isbn) === null, 'No aceptar ISBN diferente');

echo "Pruebas ISBN/OpenAI correctas\n";
