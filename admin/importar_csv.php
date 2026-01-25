<?php
session_start([
    'cookie_httponly' => true,
    'use_strict_mode' => true
]);

/* SOLO ADMIN */
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

/* SOLO POST */
if ($_SERVER['REQUEST_METHOD'] != "POST") {
    die("Acceso denegado");
}

include("conexion.php");

/* VALIDAR ARCHIVO */
if (!isset($_FILES['archivo'])) {
    die("No se recibió archivo");
}

/* VALIDAR TIPO */
$tipo = mime_content_type($_FILES['archivo']['tmp_name']);

if ($tipo != "text/plain" && $tipo != "text/csv") {
    die("Formato no permitido");
}

$archivo = $_FILES['archivo']['tmp_name'];

$fp = fopen($archivo, "r");

if (!$fp) {
    die("No se pudo abrir el archivo");
}

$linea = 0;

while (($datos = fgetcsv($fp, 1000, ",")) !== false) {

    /* SALTAR ENCABEZADO */
    if ($linea == 0) {
        $linea++;
        continue;
    }

    $titulo      = trim($datos[0] ?? '');
    $autor       = trim($datos[1] ?? '');
    $isbn        = trim($datos[2] ?? '');
    $editorial   = trim($datos[3] ?? '');
    $numPaginas  = trim($datos[4] ?? '');
    $precio      = trim($datos[5] ?? '');
    $descripcion = trim($datos[6] ?? '');
    $imagen      = trim($datos[7] ?? '');
    $stock       = trim($datos[8] ?? '');

    /* CAMPOS MINIMOS */
    if ($titulo == '' || $autor == '') {
        continue;
    }

    /* EVITAR ISBN DUPLICADO */
    if ($isbn != '') {
        $check = mysqli_query(
            $conn,
            "SELECT id FROM libros WHERE isbn='$isbn'"
        );

        if (mysqli_num_rows($check) > 0) {
            continue;
        }
    }

    mysqli_query($conn, "
        INSERT INTO libros
        (titulo,autor,isbn,editorial,numPaginas,precio,descripcion,imagen,stock)
        VALUES
        ('$titulo','$autor','$isbn','$editorial','$numPaginas','$precio','$descripcion','$imagen','$stock')
    ");
}

fclose($fp);

echo "Importación completada correctamente";
