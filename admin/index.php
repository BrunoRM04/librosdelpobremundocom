<?php
session_start();

// Si no hay sesión iniciada, redirigir al login
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros Del Pobre Mundo | Admin</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=99">
    <link rel="shortcut icon" href="../public/img/marca/icono.png">
    <style>
        body {
            background-color: #9E0001;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            font-family: "Raleway", sans-serif;
        }

        .contenedor-admin {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            max-width: 600px;
            margin: 0 auto;
        }

        .logo-admin {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-admin img {
            height: 50px;
        }

        h1 {
            text-align: center;
            color: #000000;
            font-size: 24px;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .bienvenida {
            text-align: center;
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .usuario-actual {
            text-align: center;
            color: #9E0001;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .boton-salir {
            display: block;
            padding: 12px 20px;
            background-color: #9E0001;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: opacity 0.3s;
            font-family: "Raleway", sans-serif;
        }

        .boton-salir:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="contenedor-admin">
        <div class="logo-admin">
            <img src="../public/img/marca/blanco.png" alt="Libros Del Pobre Mundo">
        </div>

        <h1>Panel Admin</h1>

        <div class="bienvenida">
            Bienvenido al panel administrativo
        </div>

        <div class="usuario-actual">
            Usuario: <?php echo htmlspecialchars($_SESSION['admin_usuario']); ?>
        </div>

        <a href="logout.php" class="boton-salir">Cerrar Sesión</a>
    </div>
</body>
</html>
