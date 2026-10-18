<?php
require_once __DIR__ . '/../api/_shared/openai-isbn.php';

function verificar($condicion, $mensaje)
{
    if (!$condicion) throw new RuntimeException($mensaje);
}

$isbn = '9780306406157';
verificar(isbn_normalizado('978-0-306-40615-7') === $isbn, 'ISBN-13 con guiones');
verificar(isbn_normalizado('0-306-40615-2') === $isbn, 'ISBN-10 a ISBN-13');
verificar(isbn_normalizado('9780306406158') === '', 'ISBN con control incorrecto');

$respuesta = [
    'status' => 'completed',
    'output' => [
        ['type' => 'web_search_call', 'status' => 'completed'],
        ['type' => 'message', 'content' => [[
            'type' => 'output_text',
            'text' => json_encode([
                'encontrado' => true, 'isbn' => $isbn, 'titulo' => 'Libro de prueba',
                'autor' => 'Autora de prueba', 'editorial' => 'Editorial',
                'numPaginas' => 200, 'descripcion' => 'Sinopsis',
                'imagen' => 'https://example.org/portada.jpg',
            ]),
            'annotations' => [[
                'type' => 'url_citation', 'url' => 'https://example.org/libro', 'title' => 'Catálogo',
            ]],
        ]]],
    ],
];
$libro = libro_desde_respuesta_openai($respuesta, $isbn);
verificar($libro['items'][0]['isbn'] === $isbn && count($libro['fuentes']) === 1, 'Resultado verificable');

$sinFuentes = $respuesta;
$sinFuentes['output'][1]['content'][0]['annotations'] = [];
verificar(libro_desde_respuesta_openai($sinFuentes, $isbn) === null, 'No aceptar datos sin fuentes');

$sinBusqueda = $respuesta;
unset($sinBusqueda['output'][0]);
verificar(libro_desde_respuesta_openai($sinBusqueda, $isbn) === null, 'No aceptar datos sin búsqueda web');

$otraEdicion = $respuesta;
$otraEdicion['output'][1]['content'][0]['text'] = str_replace($isbn, '9780306406158', $respuesta['output'][1]['content'][0]['text']);
verificar(libro_desde_respuesta_openai($otraEdicion, $isbn) === null, 'No aceptar ISBN diferente');

echo "Pruebas ISBN/OpenAI correctas\n";
