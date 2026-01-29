<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Pobre Mundo</title>
    <link rel="stylesheet" href="../public/css/admin.css">
</head>

<body>
    <?php
    session_start();

    if (!isset($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit;
    }
    ?>

    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-header-content">
                <h1>Panel Admin</h1>
                <a href="logout.php" class="admin-logout-btn">Cerrar sesión</a>
            </div>
        </header>

        <main class="admin-main">
            <div class="admin-welcome">
                <p>Bienvenido: <strong><?php echo $_SESSION['admin_user']; ?></strong></p>
            </div>

            <div class="admin-content">
                <section class="admin-section">
                    <h2>Opciones disponibles</h2>
                    <div class="admin-options">
                        <div class="admin-option-card">
                            <h3>Gestionar Libros</h3>
                            <p>Agregar, editar o eliminar libros del catálogo</p>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>