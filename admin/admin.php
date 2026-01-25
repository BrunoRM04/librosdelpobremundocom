<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Panel Administrador</title>

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


        /* CONTENEDOR PRINCIPAL */

        .admin-contenedor {
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* TARJETA */

        .admin-panel {
            background-color: var(--blanco);
            width: 420px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* TITULOS */

        .admin-titulo {
            color: var(--rojo-principal);
            margin-bottom: 10px;
        }

        .admin-subtitulo {
            margin-top: 30px;
            margin-bottom: 15px;
            color: var(--negro);
        }

        /* LINK LOGOUT */

        .admin-logout {
            display: inline-block;
            margin-top: 5px;
            color: var(--rojo-principal);
            text-decoration: none;
            font-weight: 600;
        }

        .admin-logout:hover {
            text-decoration: underline;
        }

        /* SEPARADOR */

        .admin-separador {
            margin: 25px 0;
            border: none;
            height: 1px;
            background-color: #ddd;
        }

        /* FORMULARIO */

        .admin-formulario {
            margin-top: 10px;
        }

        /* INPUT ARCHIVO */

        .admin-input-archivo {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* BOTON */

        .admin-boton {
            margin-top: 20px;
            width: 100%;
            padding: 12px;
            background-color: var(--rojo-principal);
            color: var(--blanco);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .admin-boton:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="admin-contenedor">

        <div class="admin-panel">

            <h2 class="admin-titulo">Bienvenido Administrador</h2>

            <a href="logout.php" class="admin-logout">Cerrar sesión</a>

            <hr class="admin-separador">

            <h3 class="admin-subtitulo">Importar libros CSV</h3>

            <form
                class="admin-formulario"
                action="importar_csv.php"
                method="POST"
                enctype="multipart/form-data">

                <input
                    class="admin-input-archivo"
                    type="file"
                    name="archivo"
                    accept=".csv"
                    required>

                <button
                    class="admin-boton"
                    type="submit">
                    Subir CSV
                </button>

            </form>

        </div>

    </div>

</body>

</html>