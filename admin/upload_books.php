<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargar libros - Panel Admin</title>
    <link rel="stylesheet" href="../public/css/admin.css">
</head>

<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-header-content">
                <h1>Panel Admin - Cargar Libros</h1>
                <div>
                    <a href="index.php" class="admin-logout-btn" style="margin-right: 10px;">← Volver</a>
                    <a href="logout.php" class="admin-logout-btn">Cerrar sesión</a>
                </div>
            </div>
        </header>

        <main class="admin-main">
            <div class="admin-welcome">
                <p>Usuario: <strong><?php echo $_SESSION['admin_user']; ?></strong></p>
            </div>

            <div class="admin-content">
                <section class="admin-section">
                    <h2>Cargar libros por CSV</h2>
                    
                    <form action="process_books.php" method="POST" enctype="multipart/form-data" style="margin-bottom: 30px;">
                        <div class="admin-form-group">
                            <label for="archivo" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--negro);">Seleccionar archivo CSV:</label>
                            <input type="file" id="archivo" name="archivo" accept=".csv" required class="admin-file-input">
                        </div>
                        <button type="submit" class="admin-button" style="max-width: 200px; margin-top: 20px;">Subir CSV</button>
                    </form>

                    <div style="background-color: var(--gris-claro); padding: 20px; border-radius: 5px; border-left: 4px solid var(--rojo-principal);">
                        <h3 style="color: var(--rojo-principal); margin-bottom: 15px; font-size: 18px;">Columnas obligatorias del CSV:</h3>
                        <pre style="background-color: var(--blanco); padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 14px; line-height: 1.5;">titulo,autor,isbn,editorial,numPaginas,precio,descripcion,imagen,stock</pre>
                        <p style="margin-top: 15px; color: #666; font-size: 14px;"><strong>Nota:</strong> Asegúrate de que el archivo CSV tenga exactamente estos nombres de columna en la primera fila.</p>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>