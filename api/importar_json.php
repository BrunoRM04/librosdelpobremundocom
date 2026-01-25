<?php
require "config.php";

$json = file_get_contents("../libros.json");
$libros = json_decode($json, true);

$pdo->exec("TRUNCATE TABLE libros");

$sql = $pdo->prepare("
INSERT INTO libros
(id, titulo, autor, isbn, editorial, numPaginas, precio, descripcion, imagen, stock)
VALUES
(:id, :titulo, :autor, :isbn, :editorial, :numPaginas, :precio, :descripcion, :imagen, :stock)
");

foreach ($libros as $l) {

    $precio = preg_replace('/[^0-9]/', '', $l['precio']);

    $sql->execute([
        ':id' => $l['id'],
        ':titulo' => $l['titulo'],
        ':autor' => $l['autor'],
        ':isbn' => $l['isbn'] ?? '',
        ':editorial' => $l['editorial'] ?? '',
        ':numPaginas' => $l['numPaginas'] ?? 0,
        ':precio' => $precio,
        ':descripcion' => $l['descripcion'] ?? '',
        ':imagen' => $l['imagen'] ?? '',
        ':stock' => $l['stock'] ?? 0
    ]);
}

echo "IMPORTACION OK";
