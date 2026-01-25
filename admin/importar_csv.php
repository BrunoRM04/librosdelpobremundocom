<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include("conexion.php");

$archivo = $_FILES['archivo']['tmp_name'];

$handle = fopen($archivo, "r");

$contador = 0;

while (($datos = fgetcsv($handle, 1000, ",")) !== false) {

    if ($contador == 0) {
        $contador++;
        continue;
    }

    $titulo = $datos[0];
    $autor = $datos[1];
    $isbn = $datos[2];
    $editorial = $datos[3];
    $numPaginas = $datos[4];
    $precio = $datos[5];
    $descripcion = $datos[6];
    $imagen = $datos[7];
    $stock = $datos[8];

    $sql = "INSERT INTO libros
    (titulo, autor, isbn, editorial, numPaginas, precio, descripcion, imagen, stock)
    VALUES ('$titulo','$autor','$isbn','$editorial','$numPaginas','$precio','$descripcion','$imagen','$stock')";

    mysqli_query($conn, $sql);
}

echo "Importación finalizada correctamente";
