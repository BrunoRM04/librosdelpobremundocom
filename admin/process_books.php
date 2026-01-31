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

if (!isset($_FILES['archivo'])) {
    $mensaje = "No se subió archivo";
} else {
    $archivo = $_FILES['archivo']['tmp_name'];

    $fp = fopen($archivo, 'r');
    if (!$fp) {
        $mensaje = "No se pudo abrir el archivo";
    } else {
        /* Cabecera */
        $header = fgetcsv($fp);
        $header = array_map('strtolower', $header);

        $requeridas = [
            'titulo',
            'autor',
            'isbn',
            'editorial',
            'numpaginas',
            'precio',
            'descripcion',
            'imagen',
            'stock'
        ];

        $columnasFaltantes = [];
        foreach ($requeridas as $r) {
            if (!in_array($r, $header)) {
                $columnasFaltantes[] = $r;
            }
        }

        if (!empty($columnasFaltantes)) {
            $mensaje = "Faltan columnas requeridas: " . implode(", ", $columnasFaltantes);
        } else {
            $map = array_flip($header);

            $stmt = $pdo->prepare("
            INSERT INTO libros
            (titulo,autor,isbn,editorial,numPaginas,precio,descripcion,imagen,stock)
            VALUES (?,?,?,?,?,?,?,?,?)
            ");

            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM libros WHERE isbn = ?");

            $lineaActual = 1; 

            while (($row = fgetcsv($fp)) !== false) {
                $lineaActual++;

                if (trim($row[$map['titulo']]) == '') {
                    continue;
                }

                $isbn = limpiar($row[$map['isbn']]);
                $titulo = mayus($row[$map['titulo']]);

                $stmtCheck->execute([$isbn]);
                $existe = $stmtCheck->fetchColumn();

                if ($existe > 0) {
                    $errores[] = "Línea $lineaActual: El libro \"$titulo\" (ISBN: $isbn) ya existe en la base de datos y no fue agregado.";
                    continue;
                }

                try {
                    $stmt->execute([
                        $titulo,
                        mayus($row[$map['autor']]),
                        $isbn,
                        mayus($row[$map['editorial']]),
                        (int)$row[$map['numpaginas']],
                        (float)$row[$map['precio']],
                        limpiar($row[$map['descripcion']]),
                        limpiar($row[$map['imagen']]),
                        (int)$row[$map['stock']]
                    ]);

                    $insertados++;
                } catch (Exception $e) {
                    $errores[] = "Línea $lineaActual: " . $e->getMessage();
                }
            }

            fclose($fp);
            $exito = true;
            $mensaje = "Libros cargados correctamente: $insertados";
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
                    <a href="upload_books.php" class="admin-logout-btn" style="margin-right: 10px;">← Volver</a>
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
                        <div style="background-color: #d4edda; border-left: 5px solid #28a745; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                            <h3 style="color: #155724; margin: 0 0 10px 0; font-size: 20px;">✓ Éxito</h3>
                            <p style="color: #155724; margin: 0; font-size: 16px;"><strong><?php echo $mensaje; ?></strong></p>
                        </div>
                    <?php else: ?>
                        <div style="background-color: #f8d7da; border-left: 5px solid #dc3545; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                            <h3 style="color: #721c24; margin: 0 0 10px 0; font-size: 20px;">✗ Error</h3>
                            <p style="color: #721c24; margin: 0; font-size: 16px;"><strong><?php echo $mensaje; ?></strong></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errores)): ?>
                        <div style="background-color: #fff3cd; border-left: 5px solid #ffc107; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                            <h3 style="color: #856404; margin: 0 0 15px 0; font-size: 18px;">Advertencias</h3>
                            <ul style="margin: 0; padding-left: 20px; color: #856404;">
                                <?php foreach ($errores as $error): ?>
                                    <li style="margin-bottom: 5px;"><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top: 30px;">
                        <a href="upload_books.php" class="admin-button" style="display: inline-block; max-width: 250px; text-align: center; text-decoration: none;">Cargar más libros</a>
                        <a href="index.php" class="admin-button" style="display: inline-block; max-width: 250px; text-align: center; text-decoration: none; margin-left: 10px; background-color: #666;">Volver al panel</a>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>

</html>