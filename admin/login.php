<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Login Administrador</title>

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

        .admin-login-contenedor {
            width: 100%;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* TARJETA */

        .admin-login-panel {
            background-color: var(--blanco);
            width: 380px;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* TITULO */

        .admin-login-titulo {
            color: var(--rojo-principal);
            margin-bottom: 25px;
        }

        /* INPUTS */

        .admin-login-input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        /* BOTON */

        .admin-login-boton {
            width: 100%;
            padding: 12px;
            background-color: var(--rojo-principal);
            color: var(--blanco);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        .admin-login-boton:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>

    <div class="admin-login-contenedor">

        <div class="admin-login-panel">

            <h2 class="admin-login-titulo">Panel Administración</h2>

            <form action="procesar_login.php" method="POST">

                <input
                    class="admin-login-input"
                    type="text"
                    name="usuario"
                    placeholder="Usuario"
                    required>

                <input
                    class="admin-login-input"
                    type="password"
                    name="password"
                    placeholder="Contraseña"
                    required>

                <button
                    class="admin-login-boton"
                    type="submit">
                    Ingresar
                </button>

            </form>

        </div>

    </div>

</body>

</html>