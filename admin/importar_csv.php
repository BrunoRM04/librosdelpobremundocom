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
$importados = 0;
$duplicados = 0;

while (($datos = fgetcsv($fp, 1000, ",")) !== false) {

    if ($linea == 0) {
        $linea++;
        continue;
    }

    $titulo    = mb_strtoupper(trim($datos[0] ?? ''), 'UTF-8');
    $autor     = mb_strtoupper(trim($datos[1] ?? ''), 'UTF-8');
    $isbn      = trim($datos[2] ?? '');
    $editorial = mb_strtoupper(trim($datos[3] ?? ''), 'UTF-8');
    $numPaginas  = trim($datos[4] ?? '');
    $precio      = trim($datos[5] ?? '');
    $descripcion = trim($datos[6] ?? '');
    $imagen      = trim($datos[7] ?? '');
    $stock       = trim($datos[8] ?? '');

    if ($titulo == '' || $autor == '') {
        continue;
    }

    if ($isbn != '') {

        $check = mysqli_query(
            $conn,
            "SELECT id FROM libros WHERE isbn='$isbn'"
        );

        if (mysqli_num_rows($check) > 0) {
            $duplicados++;
            continue;
        }
    }

    mysqli_query($conn, "
        INSERT INTO libros
        (titulo,autor,isbn,editorial,numPaginas,precio,descripcion,imagen,stock)
        VALUES
        ('$titulo','$autor','$isbn','$editorial','$numPaginas','$precio','$descripcion','$imagen','$stock')
    ");

    $importados++;
}

fclose($fp);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Resultado Importación</title>

    <style>
        /* GENERAL */

        @import url('https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: "Raleway", sans-serif;
            background-color: #f5f5f5;
        }

        :root {
            --rojo-principal: #9E0001;
            --blanco: #ffffff;
            --negro: #000000;
        }

        /* GENERAL */


        /* CONTENEDOR */

        .admin-resultado-contenedor {
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* TARJETA */

        .admin-resultado-panel {
            background-color: var(--blanco);
            width: 420px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* TITULO */

        .admin-resultado-titulo {
            color: var(--rojo-principal);
            margin-bottom: 20px;
        }

        /* TEXTO */

        .admin-resultado-texto {
            margin-bottom: 10px;
            font-size: 16px;
        }

        /* BOTON */

        .admin-resultado-boton {
            margin-top: 25px;
            padding: 12px 25px;
            background-color: var(--rojo-principal);
            color: var(--blanco);
            border: none;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
        }

        .admin-resultado-boton:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="admin-resultado-contenedor">

        <div class="admin-resultado-panel">

            <h2 class="admin-resultado-titulo">Importación Finalizada</h2>

            <p class="admin-resultado-texto">
                Libros importados: <strong><?php echo $importados; ?></strong>
            </p>

            <p class="admin-resultado-texto">
                ISBN duplicados ignorados: <strong><?php echo $duplicados; ?></strong>
            </p>

            <a class="admin-resultado-boton" href="admin.php">
                Volver al panel
            </a>

        </div>

    </div>

</body>

</html>