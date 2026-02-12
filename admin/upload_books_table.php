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
        .isbn-lookup {
            background-color: var(--gris-claro);
            border-left: 4px solid var(--rojo-principal);
            padding: 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .isbn-lookup .row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }

        .isbn-lookup input {
            flex: 1 1 260px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: "Raleway", sans-serif;
            font-size: 14px;
        }

        .isbn-lookup .admin-btn {
            background-color: var(--rojo-principal);
            color: var(--blanco);
            padding: 10px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
        }

        .isbn-lookup .admin-btn:hover {
            filter: brightness(1.1);
        }

        .isbn-message {
            margin-top: 10px;
            font-size: 14px;
            display: none;
        }

        .isbn-message.success {
            color: #155724;
        }

        .isbn-message.error {
            color: #721c24;
        }

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

                    <div class="isbn-lookup">
                        <div class="row">
                            <input type="text" id="isbn-buscar" placeholder="Buscar por ISBN (10 o 13)" autocomplete="off">
                            <button type="button" class="admin-btn" id="btn-isbn">Buscar en Google Books</button>
                        </div>
                        <div id="isbn-message" class="isbn-message"></div>
                    </div>

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
        const GOOGLE_BOOKS_API_KEY = 'AIzaSyA99TwJyi85BKC7p9lIQXiv4b_UbuZ21N4';

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

        const isbnInput = document.getElementById('isbn-buscar');
        const isbnButton = document.getElementById('btn-isbn');
        const isbnMessage = document.getElementById('isbn-message');

        isbnButton.addEventListener('click', buscarISBN);
        isbnInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarISBN();
            }
        });

        async function buscarISBN() {
            const isbn = isbnInput.value.trim();
            if (!isbn) {
                mostrarISBNMensaje('Ingresa un ISBN valido', 'error');
                return;
            }

            mostrarISBNMensaje('Buscando informacion en Google Books...', 'success');

            try {
                const url = `https://www.googleapis.com/books/v1/volumes?q=isbn:${encodeURIComponent(isbn)}&key=${GOOGLE_BOOKS_API_KEY}`;
                const response = await fetch(url);
                const data = await response.json();

                if (!data.items || data.items.length === 0) {
                    mostrarISBNMensaje('No se encontro informacion para ese ISBN', 'error');
                    return;
                }

                const book = data.items[0].volumeInfo || {};
                const titulo = book.title || '';
                const autor = (book.authors && book.authors.length > 0) ? book.authors[0] : '';
                const editorial = book.publisher || '';
                const numPaginas = book.pageCount || '';
                const descripcion = book.description || '';
                const imagen = (book.imageLinks && (book.imageLinks.thumbnail || book.imageLinks.smallThumbnail)) || '';

                const isbnValue = obtenerISBN(book.industryIdentifiers, isbn);

                const row = obtenerFilaDestino();
                row.querySelector('input[name="titulo[]"]').value = titulo;
                row.querySelector('input[name="autor[]"]').value = autor;
                row.querySelector('input[name="isbn[]"]').value = isbnValue;
                row.querySelector('input[name="editorial[]"]').value = editorial;
                row.querySelector('input[name="numPaginas[]"]').value = numPaginas;
                row.querySelector('textarea[name="descripcion[]"]').value = descripcion;
                row.querySelector('input[name="imagen[]"]').value = imagen;

                mostrarISBNMensaje('Datos cargados en la tabla', 'success');
            } catch (error) {
                console.error(error);
                mostrarISBNMensaje('Error al consultar Google Books', 'error');
            }
        }

        function obtenerFilaDestino() {
            const tableBody = document.getElementById('tableBody');
            const rows = Array.from(tableBody.querySelectorAll('tr'));

            for (const row of rows) {
                const tituloInput = row.querySelector('input[name="titulo[]"]');
                if (tituloInput && tituloInput.value.trim() === '') {
                    return row;
                }
            }

            agregarFila();
            return tableBody.lastElementChild;
        }

        function obtenerISBN(identifiers, fallback) {
            if (!Array.isArray(identifiers)) {
                return fallback || '';
            }

            const isbn13 = identifiers.find((id) => id.type === 'ISBN_13');
            const isbn10 = identifiers.find((id) => id.type === 'ISBN_10');

            if (isbn13 && isbn13.identifier) {
                return isbn13.identifier;
            }

            if (isbn10 && isbn10.identifier) {
                return isbn10.identifier;
            }

            return fallback || '';
        }

        function mostrarISBNMensaje(texto, tipo) {
            isbnMessage.textContent = texto;
            isbnMessage.className = `isbn-message ${tipo}`;
            isbnMessage.style.display = 'block';
        }
    </script>
</body>

</html>