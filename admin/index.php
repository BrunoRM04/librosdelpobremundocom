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
                        <a href="upload_books.php" class="admin-option-card">
                            <h3>Cargar libros por CSV</h3>
                            <p>Subir archivo CSV generado desde Excel</p>
                        </a>
                        <a href="upload_books_table.php" class="admin-option-card">
                            <h3>Subir libros por tabla</h3>
                            <p>Agregar libros manualmente a través de formulario</p>
                        </a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>