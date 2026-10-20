<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit;
}
$_SESSION['csrf_admin'] ??= bin2hex(random_bytes(32));
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

        .isbn-message.warning {
            color: #8a5a00;
        }

        .isbn-resultados {
            display: grid;
            gap: 6px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
            font-size: 14px;
        }

        .isbn-resultados a {
            color: var(--rojo-principal);
            text-decoration: underline;
            overflow-wrap: anywhere;
        }

        .isbn-resultados a:focus-visible {
            outline: 2px solid var(--rojo-principal);
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
                            <input type="search" id="isbn-buscar" placeholder="ISBN de 10 o 13 dígitos" autocomplete="off" aria-label="Buscar libro por ISBN">
                            <button type="button" class="admin-btn" id="btn-isbn">Completar por ISBN</button>
                        </div>
                        <div id="isbn-message" class="isbn-message"></div>
                        <div id="isbn-resultados" class="isbn-resultados" aria-live="polite"></div>
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
                                        <th class="col-medium">Editorial</th>
                                        <th class="col-small">N° Páginas</th>
                                        <th class="col-small">Precio de lista *</th>
                                        <th class="col-xlarge">Descripción</th>
                                        <th class="col-large">Imagen</th>
                                        <th class="col-small">Stock *</th>
                                        <th class="col-small">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tableBody">
                                    <tr>
                                        <td><input type="text" name="titulo[]" required></td>
                                        <td><input type="text" name="autor[]" required></td>
                                        <td><input type="text" name="isbn[]" required></td>
                                        <td><input type="text" name="editorial[]"></td>
                                        <td><input type="number" name="numPaginas[]" min="0"></td>
                                        <td><input type="number" name="precio[]" required step="0.01" min="0"></td>
                                        <td><textarea name="descripcion[]"></textarea></td>
                                        <td><input type="text" name="imagen[]"></td>
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
                            <li>Para la imagen, ingresá el nombre de un archivo de portadas o una URL HTTPS de la portada.</li>
                            <li>La búsqueda por ISBN completa los datos bibliográficos para revisión. Precio y stock se cargan manualmente.</li>
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
                <td><input type="text" name="editorial[]"></td>
                <td><input type="number" name="numPaginas[]" min="0"></td>
                <td><input type="number" name="precio[]" required step="0.01" min="0"></td>
                <td><textarea name="descripcion[]"></textarea></td>
                <td><input type="text" name="imagen[]"></td>
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
        const isbnResultados = document.getElementById('isbn-resultados');
        const csrfISBN = <?php echo json_encode($_SESSION['csrf_admin']); ?>;

        isbnButton.addEventListener('click', buscarISBN);
        isbnInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarISBN();
            }
        });

        async function buscarISBN() {
            if (isbnButton.disabled) return;
            const isbn = isbnInput.value.replace(/[\s-]/g, '').toUpperCase();
            if (!isbnValido(isbn)) {
                mostrarISBNMensaje('Ingresá un ISBN válido de 10 o 13 dígitos.', 'error');
                return;
            }
            isbnButton.disabled = true;
            isbnResultados.replaceChildren();
            mostrarISBNMensaje('Buscando la edición con OpenAI y búsqueda web...', 'success');
            try {
                const response = await fetch('../api/admin/isbn-fuentes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ isbn, csrf: csrfISBN })
                });
                const texto = await response.text();
                let data;
                try {
                    data = JSON.parse(texto);
                } catch {
                    throw new Error(`El servidor interrumpió la búsqueda (HTTP ${response.status}). Revisá el registro de errores de PHP.`);
                }
                if (!response.ok) throw new Error(data.error || 'No se pudo buscar el libro.');
                const libro = data.items?.[0];
                if (!libro) {
                    obtenerFilaDestino().querySelector('[name="isbn[]"]').value = isbn13(isbn);
                    mostrarISBNMensaje('No se encontraron datos verificables para ese ISBN. Podés completar la fila manualmente.', 'error');
                    return;
                }
                cargarLibroEncontrado(libro);
                const fuentes = Array.isArray(data.fuentes) ? data.fuentes : [];
                if (libro.descripcionFuente) {
                    const etiquetaDescripcion = document.createElement('span');
                    etiquetaDescripcion.textContent = 'Descripción original: ';
                    const enlaceDescripcion = document.createElement('a');
                    enlaceDescripcion.href = libro.descripcionFuente;
                    enlaceDescripcion.target = '_blank';
                    enlaceDescripcion.rel = 'noopener noreferrer';
                    enlaceDescripcion.textContent = 'ver ficha verificada';
                    isbnResultados.append(etiquetaDescripcion, enlaceDescripcion);
                }
                if (fuentes.length) {
                    const etiqueta = document.createElement('span');
                    etiqueta.textContent = 'Fuentes consultadas: ';
                    isbnResultados.appendChild(etiqueta);
                    fuentes.forEach((fuente, indice) => {
                        if (!/^https:\/\//.test(fuente.url || '')) return;
                        const enlace = document.createElement('a');
                        enlace.href = fuente.url;
                        enlace.target = '_blank';
                        enlace.rel = 'noopener noreferrer';
                        enlace.textContent = fuente.titulo || `Fuente ${indice + 1}`;
                        isbnResultados.appendChild(enlace);
                    });
                }
            } catch (error) {
                mostrarISBNMensaje(error.message || 'No se pudo buscar el libro.', 'error');
            } finally {
                isbnButton.disabled = false;
            }
        }

        function cargarLibroEncontrado(libro) {
            const row = obtenerFilaDestino();
            for (const campo of ['titulo', 'autor', 'isbn', 'editorial', 'numPaginas', 'descripcion', 'imagen']) {
                row.querySelector(`[name="${campo}[]"]`).value = libro[campo] || '';
            }
            const faltantes = [['autor', 'autor'], ['editorial', 'editorial'], ['numPaginas', 'páginas'], ['imagen', 'portada']]
                .filter(([campo]) => !libro[campo]).map(([, etiqueta]) => etiqueta);
            const avisoFaltantes = faltantes.length ? ` Faltan: ${faltantes.join(', ')}.` : '';
            if (libro.descripcionTipo === 'original') {
                mostrarISBNMensaje(`Datos cargados con sinopsis de la ficha original.${avisoFaltantes} Completá precio y stock y revisá la edición antes de guardar.`, faltantes.length ? 'warning' : 'success');
            } else if (libro.descripcionTipo === 'generada') {
                mostrarISBNMensaje(`Datos cargados. La descripción fue generada por IA porque no se pudo extraer una sinopsis original; revisala antes de guardar.${avisoFaltantes} Completá precio y stock.`, 'warning');
            } else {
                mostrarISBNMensaje(`Datos cargados sin descripción verificable.${avisoFaltantes} Completá ese campo desde una fuente confiable y revisá la edición.`, 'warning');
            }
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
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

        function isbn13(valor) {
            const limpio = String(valor).replace(/[\s-]/g, '').toUpperCase();
            if (/^\d{13}$/.test(limpio)) return limpio;
            if (!/^\d{9}[\dX]$/.test(limpio)) return '';
            const base = `978${limpio.slice(0, 9)}`;
            const suma = [...base].reduce((total, digito, indice) => total + Number(digito) * (indice % 2 ? 3 : 1), 0);
            return base + ((10 - suma % 10) % 10);
        }

        function isbnValido(valor) {
            if (/^\d{13}$/.test(valor)) {
                const suma = [...valor.slice(0, 12)].reduce((total, digito, indice) => total + Number(digito) * (indice % 2 ? 3 : 1), 0);
                return (10 - suma % 10) % 10 === Number(valor[12]);
            }
            if (/^\d{9}[\dX]$/.test(valor)) {
                const suma = [...valor].reduce((total, digito, indice) => total + (digito === 'X' ? 10 : Number(digito)) * (10 - indice), 0);
                return suma % 11 === 0;
            }
            return false;
        }

        function mostrarISBNMensaje(texto, tipo) {
            isbnMessage.textContent = texto;
            isbnMessage.className = `isbn-message ${tipo}`;
            isbnMessage.style.display = 'block';
        }
    </script>
</body>

</html>
