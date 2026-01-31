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
    <title>Subir libros por tabla - Panel Admin</title>
    <link rel="stylesheet" href="../public/css/admin.css">
    <style>
        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
            background-color: var(--blanco);
            min-width: 1200px;
        }

        .books-table thead {
            background-color: var(--rojo-principal);
            color: var(--blanco);
        }

        .books-table th {
            padding: 12px 8px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid #ddd;
        }

        .books-table td {
            padding: 8px;
            border: 1px solid #ddd;
        }

        .books-table input,
        .books-table textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: "Raleway", sans-serif;
            font-size: 13px;
        }

        .books-table textarea {
            resize: vertical;
            min-height: 60px;
        }

        .books-table input:focus,
        .books-table textarea:focus {
            outline: none;
            border-color: var(--rojo-principal);
        }

        .add-row-btn {
            background-color: var(--rojo-principal);
            color: var(--blanco);
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .add-row-btn:hover {
            filter: brightness(1.1);
        }

        .remove-row-btn {
            background-color: #dc3545;
            color: var(--blanco);
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
        }

        .remove-row-btn:hover {
            background-color: #c82333;
        }

        .col-small {
            width: 80px;
        }

        .col-medium {
            width: 120px;
        }

        .col-large {
            width: 200px;
        }

        .col-xlarge {
            width: 250px;
        }
    </style>
</head>

<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="admin-header-content">
                <h1>Panel Admin - Subir Libros por Tabla</h1>
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
                    <h2>Agregar libros manualmente</h2>

                    <button type="button" class="add-row-btn" onclick="agregarFila()">+ Agregar fila</button>

                    <form action="process_books_table.php" method="POST" id="booksForm">
                        <div class="table-container">
                            <table class="books-table" id="booksTable">
                                <thead>
                                    <tr>
                                        <th class="col-large">Título *</th>
                                        <th class="col-medium">Autor *</th>
                                        <th class="col-medium">ISBN *</th>
                                        <th class="col-medium">Editorial *</th>
                                        <th class="col-small">N° Páginas *</th>
                                        <th class="col-small">Precio *</th>
                                        <th class="col-xlarge">Descripción *</th>
                                        <th class="col-large">Imagen *</th>
                                        <th class="col-small">Stock *</th>
                                        <th class="col-small">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td><input type="text" name="titulo[]" required></td>
                                        <td><input type="text" name="autor[]" required></td>
                                        <td><input type="text" name="isbn[]" required></td>
                                        <td><input type="text" name="editorial[]" required></td>
                                        <td><input type="number" name="numPaginas[]" required min="1"></td>
                                        <td><input type="number" name="precio[]" required step="0.01" min="0"></td>
                                        <td><textarea name="descripcion[]" required></textarea></td>
                                        <td><input type="text" name="imagen[]" required></td>
                                        <td><input type="number" name="stock[]" required min="0"></td>
                                        <td><button type="button" class="remove-row-btn" onclick="eliminarFila(this)">Eliminar</button></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <button type="submit" class="admin-button" style="max-width: 200px;">Subir libros</button>
                    </form>

                    <div style="background-color: var(--gris-claro); padding: 20px; border-radius: 5px; border-left: 4px solid var(--rojo-principal); margin-top: 30px;">
                        <h3 style="color: var(--rojo-principal); margin-bottom: 15px; font-size: 18px;">Instrucciones:</h3>
                        <ul style="margin: 0; padding-left: 20px; color: #666; font-size: 14px; line-height: 1.8;">
                            <li>Completa todos los campos marcados con * (obligatorios)</li>
                            <li>El título, autor y editorial se guardarán en MAYÚSCULAS automáticamente</li>
                            <li>El ISBN debe ser único, no se pueden subir libros con ISBN duplicado</li>
                            <li>Para la imagen, ingresa el nombre del archivo con extensión (ej: libro.jpg)</li>
                            <li>Usa el botón "+ Agregar fila" para agregar más libros</li>
                        </ul>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        function agregarFila() {
            const tableBody = document.getElementById('tableBody');
            const newRow = document.createElement('tr');
            newRow.innerHTML = `
                <td><input type="text" name="titulo[]" required></td>
                <td><input type="text" name="autor[]" required></td>
                <td><input type="text" name="isbn[]" required></td>
                <td><input type="text" name="editorial[]" required></td>
                <td><input type="number" name="numPaginas[]" required min="1"></td>
                <td><input type="number" name="precio[]" required step="0.01" min="0"></td>
                <td><textarea name="descripcion[]" required></textarea></td>
                <td><input type="text" name="imagen[]" required></td>
                <td><input type="number" name="stock[]" required min="0"></td>
                <td><button type="button" class="remove-row-btn" onclick="eliminarFila(this)">Eliminar</button></td>
            `;
            tableBody.appendChild(newRow);
        }

        function eliminarFila(btn) {
            const tableBody = document.getElementById('tableBody');
            if (tableBody.children.length > 1) {
                btn.closest('tr').remove();
            } else {
                alert('Debe haber al menos una fila en la tabla');
            }
        }
    </script>
</body>

</html>
