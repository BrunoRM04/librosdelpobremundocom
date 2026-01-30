<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Pobre Mundo</title>
    <link rel="stylesheet" href="../public/css/admin.css">
</head>

<body>
    <?php
    session_start();
    require_once __DIR__ . '/../api/config.php';

    $error = "";

    if ($_SERVER["REQUEST_METHOD"] === "POST") {

        $usuario = $_POST['usuario'] ?? '';
        $password = $_POST['password'] ?? '';

        $stmt = $pdo->prepare("
            SELECT id, usuario, password
            FROM admin
            WHERE usuario = ?
            LIMIT 1
        ");
        $stmt->execute([$usuario]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && $password === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['usuario'];
            header("Location: index.php");
            exit;
        } else {
            $error = "Usuario o contraseña incorrectos";
        }
    }
    ?>

    <div class="admin-login-container">
        <div class="admin-login-box">
            <div class="admin-logo">
                <img src="../public/img/marca/blanco.png" alt="Pobre Mundo">
            </div>

            <form method="POST" class="admin-form">
                <h2>Panel Admin</h2>

                <div class="admin-form-group">
                    <input type="text" name="usuario" placeholder="Usuario" required>
                </div>

                <div class="admin-form-group">
                    <input type="password" name="password" placeholder="Contraseña" required>
                </div>

                <?php if ($error): ?>
                    <p class="admin-error"><?php echo $error; ?></p>
                <?php endif; ?>

                <button type="submit" class="admin-button admin-button--outline">Ingresar</button>
            </form>
        </div>
    </div>

    <div id="login-loader" class="login-loader" aria-hidden="true">
        <span class="login-loader-text">Cargando...</span>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.querySelector('.admin-form');
            var loader = document.getElementById('login-loader');

            if (form && loader) {
                form.addEventListener('submit', function () {
                    loader.classList.add('is-active');
                    loader.setAttribute('aria-hidden', 'false');
                });
            }
        });
    </script>
</body>

</html>