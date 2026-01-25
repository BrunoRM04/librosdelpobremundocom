<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require "config.php";

$archivo = dirname(__DIR__) . "/libros.json";

if (!file_exists($archivo)) {
    die("NO SE ENCUENTRA libros.json EN RAIZ");
}

$json = file_get_contents($archivo);

if (!$json) {
    die("NO SE PUDO LEER libros.json");
}

$libros = json_decode($json, true);

if (!is_array($libros)) {
    die("JSON INVALIDO");
}

$insertados = 0;
$omitidos = 0;

$checkId = $pdo->prepare("SELECT id FROM libros WHERE id = ?");
$checkIsbn = $pdo->prepare("SELECT id FROM libros WHERE isbn = ?");

$insert = $pdo->prepare("
INSERT INTO libros
(id, titulo, autor, isbn, editorial, numPaginas, precio, descripcion, imagen, stock)
VALUES
(?,?,?,?,?,?,?,?,?,?)
");

foreach ($libros as $l) {

    $id = (int)($l['id'] ?? 0);
    $titulo = trim($l['titulo'] ?? '');
    $autor = trim($l['autor'] ?? '');
    $isbn = trim($l['isbn'] ?? '');
    $editorial = trim($l['editorial'] ?? '');
    $numPaginas = (int)($l['numPaginas'] ?? 0);
    $precio = (int)preg_replace('/[^0-9]/', '', $l['precio'] ?? 0);
    $descripcion = trim($l['descripcion'] ?? '');
    $imagen = trim($l['imagen'] ?? '');
    $stock = (int)($l['stock'] ?? 0);

    if ($id <= 0 || $titulo === '' || $autor === '') {
        continue;
    }

    $checkId->execute([$id]);
    if ($checkId->fetch()) {
        $omitidos++;
        continue;
    }

    if ($isbn !== '') {
        $checkIsbn->execute([$isbn]);
        if ($checkIsbn->fetch()) {
            $omitidos++;
            continue;
        }
    }

    $insert->execute([
        $id,
        $titulo,
        $autor,
        $isbn ?: null,
        $editorial ?: null,
        $numPaginas ?: null,
        $precio ?: null,
        $descripcion ?: null,
        $imagen ?: null,
        $stock ?: null
    ]);

    $insertados++;
}

echo "NUEVOS INSERTADOS: $insertados<br>";
echo "OMITIDOS (YA EXISTENTES): $omitidos";