<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}

require '../api/config.php';

$exito = false;
$mensaje = "";
$insertados = 0;
$errores = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $mensaje = "Método no permitido";
} else {
    // Obtener los arrays de datos
    $titulos = $_POST['titulo'] ?? [];
    $autores = $_POST['autor'] ?? [];
    $isbns = $_POST['isbn'] ?? [];
    $editoriales = $_POST['editorial'] ?? [];
    $numPaginas = $_POST['numPaginas'] ?? [];
    $precios = $_POST['precio'] ?? [];
    $descripciones = $_POST['descripcion'] ?? [];
    $imagenes = $_POST['imagen'] ?? [];
    $stocks = $_POST['stock'] ?? [];

    // Verificar que todos los arrays tengan el mismo tamaño
    $count = count($titulos);
    if (
        count($autores) !== $count ||
        count($isbns) !== $count ||
        count($editoriales) !== $count ||
        count($numPaginas) !== $count ||
        count($precios) !== $count ||
        count($descripciones) !== $count ||
        count($imagenes) !== $count ||
        count($stocks) !== $count
    ) {
        $mensaje = "Error en los datos enviados. Datos inconsistentes.";
    } else {
        // Preparar statements
        $stmt = $pdo->prepare("
            INSERT INTO libros
            (titulo,autor,isbn,editorial,numPaginas,precio,descripcion,imagen,stock)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM libros WHERE isbn = ?");

        // Procesar cada libro
        for ($i = 0; $i < $count; $i++) {
            $fila = $i + 1;

            // Validar que los campos obligatorios no estén vacíos
            if (
                trim($titulos[$i]) === '' ||
                trim($autores[$i]) === '' ||
                trim($isbns[$i]) === '' ||
                trim($editoriales[$i]) === '' ||
                trim($numPaginas[$i]) === '' ||
                trim($precios[$i]) === '' ||
                trim($descripciones[$i]) === '' ||
                trim($imagenes[$i]) === '' ||
                trim($stocks[$i]) === ''
            ) {
                $errores[] = "Fila $fila: Todos los campos son obligatorios. Esta fila fue omitida.";
                continue;
            }

            $isbn = limpiar($isbns[$i]);
            $titulo = mayus($titulos[$i]);

            // Verificar si el ISBN ya existe
            $stmtCheck->execute([$isbn]);
            $existe = $stmtCheck->fetchColumn();

            if ($existe > 0) {
                $errores[] = "Fila $fila: El libro \"$titulo\" (ISBN: $isbn) ya existe en la base de datos y no fue agregado.";
                continue;
            }

            try {
                $stmt->execute([
                    $titulo,
                    mayus($autores[$i]),
                    $isbn,
                    mayus($editoriales[$i]),
                    (int)$numPaginas[$i],
                    (float)$precios[$i],
                    limpiar($descripciones[$i]),
                    limpiar($imagenes[$i]),
                    (int)$stocks[$i]
                ]);

                $insertados++;
            } catch (Exception $e) {
                $errores[] = "Fila $fila: Error al insertar - " . $e->getMessage();
            }
        }

        $exito = true;
        if ($insertados > 0) {
            $mensaje = "Libros cargados correctamente: $insertados";
        } else {
            $mensaje = "No se cargó ningún libro. Verifica los errores a continuación.";
        }
    }
}

function limpiar($v)
{
    return trim(strip_tags($v));
}

function mayus($v)
{
    return mb_strtoupper(trim(strip_tags($v)), 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado - Panel Admin</title>
    <link rel="stylesheet" href="../public/css/admin.css">
</head>

<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-header-content">
                <h1>Panel Admin - Resultado de Carga</h1>
                <div>
                    <a href="upload_books_table.php" class="admin-logout-btn" style="margin-right: 10px;">← Volver</a>
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
                    <h2>Resultado de la carga</h2>

                    <?php if ($exito): ?>
                        <?php if ($insertados > 0): ?>
                            <div style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                                <h3 style="color: #155724; margin: 0 0 10px 0; font-size: 20px;">✓ Éxito</h3>
                                <p style="color: #155724; margin: 0; font-size: 16px;"><strong><?php echo $mensaje; ?></strong></p>
                            </div>
                        <?php else: ?>
                            <div style="background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                                <h3 style="color: #856404; margin: 0 0 10px 0; font-size: 20px;">⚠ Advertencia</h3>
                                <p style="color: #856404; margin: 0; font-size: 16px;"><strong><?php echo $mensaje; ?></strong></p>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="background-color: #f8d7da; border-left: 5px solid #dc3545; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                            <h3 style="color: #721c24; margin: 0 0 10px 0; font-size: 20px;">✗ Error</h3>
                            <p style="color: #721c24; margin: 0; font-size: 16px;"><strong><?php echo $mensaje; ?></strong></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errores)): ?>
                        <div style="background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                            <h3 style="color: #856404; margin: 0 0 15px 0; font-size: 18px;">Advertencias y Errores</h3>
                            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                                <?php foreach ($errores as $error): ?>
                                    <li style="margin-bottom: 5px;"><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px;">
                        <a href="upload_books_table.php" class="admin-button" style="display: inline-block; max-width: 250px; text-align: center; text-decoration: none;">Cargar más libros</a>
                        <a href="index.php" class="admin-button" style="display: inline-block; max-width: 250px; text-align: center; text-decoration: none; margin-left: 10px; background-color: #666;">Volver al panel</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>
