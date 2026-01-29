<?php
session_start();

// Si ya hay sesión iniciada, redirigir al index
if (isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../api/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $contraseña = $_POST['contraseña'] ?? '';

    if (empty($usuario) || empty($contraseña)) {
        $error = 'Usuario y contraseña son requeridos.';
    } else {
        try {
            $stmt = $pdo->prepare('SELECT id, usuario FROM admin WHERE usuario = ? AND password = ?');
            $stmt->execute([$usuario, $contraseña]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                header('Location: index.php');
                exit();
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (PDOException $e) {
            $error = 'Error en la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libros Del Pobre Mundo | Admin Login</title>
    <link rel="stylesheet" href="../public/css/styles.css?v=99">
    <link rel="shortcut icon" href="../public/img/marca/icono.png">
    <style>
        body {
            background-color: #9E0001;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
            font-family: "Raleway", sans-serif;
        }

        .contenedor-login {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
        }

        .logo-login {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-login img {
            height: 50px;
        }

        h1 {
            text-align: center;
            color: #000000;
            font-size: 24px;
            margin-bottom: 30px;
            font-weight: 600;
        }

        .formulario-login {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .grupo-input {
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 14px;
            color: #000000;
            margin-bottom: 8px;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            font-family: "Raleway", sans-serif;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #9E0001;
        }

        .boton-login {
            padding: 12px;
            background-color: #9E0001;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.3s;
            font-family: "Raleway", sans-serif;
        }

        .boton-login:hover {
            opacity: 0.9;
        }

        .error-mensaje {
            background-color: #ffebee;
            color: #c62828;
            padding: 12px 15px;
            border-radius: 4px;
            text-align: center;
            font-size: 14px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
    </style>
</head>
<body>
    <div class="contenedor-login">
        <div class="logo-login">
            <img src="../public/img/marca/blanco.png" alt="Libros Del Pobre Mundo">
        </div>

        <h1>Admin</h1>

        <?php if (!empty($error)): ?>
            <div class="error-mensaje"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="formulario-login">
            <div class="grupo-input">
                <label for="usuario">Usuario</label>
                <input type="text" id="usuario" name="usuario" required autofocus>
            </div>

            <div class="grupo-input">
                <label for="contraseña">Contraseña</label>
                <input type="password" id="contraseña" name="contraseña" required>
            </div>

            <button type="submit" class="boton-login">Ingresar</button>
        </form>
    </div>
</body>
</html>
