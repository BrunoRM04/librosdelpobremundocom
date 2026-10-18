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

        .isbn-resultados {
            display: grid;
            gap: 8px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }

        .isbn-result {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            text-align: left;
            cursor: pointer;
            overflow-wrap: anywhere;
        }

        .isbn-result:hover,
        .isbn-result:focus-visible {
            border-color: var(--rojo-principal);
            background: #fff7f7;
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
                            <input type="search" id="isbn-buscar" placeholder="ISBN, título o autor" autocomplete="off" aria-label="Buscar datos de un libro">
                            <button type="button" class="admin-btn" id="btn-isbn">Buscar libro</button>
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
        const cacheOpenLibrary = new Map();

        isbnButton.addEventListener('click', buscarISBN);
        isbnInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                buscarISBN();
            }
        });

        async function buscarISBN() {
            if (isbnButton.disabled) return;
            const consulta = isbnInput.value.trim();
            if (consulta.length < 2) {
                mostrarISBNMensaje('Ingresá un ISBN, título o autor', 'error');
                return;
            }
            const isbn = consulta.replace(/[\s-]/g, '').toUpperCase();
            const esISBN = /^(?:\d{13}|\d{9}[\dX])$/.test(isbn);
            if (esISBN && !isbnValido(isbn)) {
                mostrarISBNMensaje('El ISBN tiene un dígito de control incorrecto. Revisalo y probá otra vez.', 'error');
                return;
            }
            isbnButton.disabled = true;
            isbnResultados.replaceChildren();
            mostrarISBNMensaje('Buscando en Google Books, Open Library y Crossref...', 'success');

            const solicitudes = [buscarGoogle(esISBN ? isbn : consulta, esISBN), buscarOpenLibrary(esISBN ? isbn : consulta, esISBN)];
            if (esISBN) solicitudes.push(buscarCrossref(isbn));
            const respuestas = await Promise.allSettled(solicitudes);
            isbnButton.disabled = false;
            const encontrados = respuestas.flatMap(r => r.status === 'fulfilled' ? r.value : []);
            const unicos = new Map();
            encontrados.forEach(libro => {
                const clave = isbn13(libro.isbn) || libro.isbn || `${libro.titulo.toLowerCase()}|${libro.autor.toLowerCase()}`;
                if (!unicos.has(clave)) unicos.set(clave, libro);
                else {
                    const anterior = unicos.get(clave);
                    for (const campo of ['titulo', 'autor', 'editorial', 'numPaginas', 'descripcion', 'imagen']) {
                        if (!anterior[campo] && libro[campo]) anterior[campo] = libro[campo];
                    }
                }
            });
            const libros = [...unicos.values()];
            if (!libros.length) {
                const fallaron = respuestas.every(r => r.status === 'rejected');
                if (esISBN) obtenerFilaDestino().querySelector('[name="isbn[]"]').value = isbn;
                mostrarISBNMensaje(fallaron ? 'No se pudieron consultar las bibliotecas. Probá de nuevo o cargá el libro manualmente.' : 'No se encontraron datos. Probá con parte del título o cargá el libro manualmente.', 'error');
                return;
            }
            mostrarISBNMensaje(`${libros.length} resultado(s). Elegí la edición correcta y revisá los datos antes de guardar.`, 'success');
            libros.forEach(libro => {
                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'isbn-result';
                boton.textContent = `${libro.titulo} · ${libro.autor || 'Autor sin dato'} · ISBN ${libro.isbn || 'sin dato'} · ${libro.fuente}`;
                boton.addEventListener('click', () => cargarLibroEncontrado(libro));
                isbnResultados.appendChild(boton);
            });
        }

        async function obtenerJSON(url) {
            const controller = new AbortController();
            const timeout = setTimeout(() => controller.abort(), 10000);
            try {
                const response = await fetch(url, { signal: controller.signal });
                if (!response.ok) throw new Error(`Servicio no disponible: ${response.status}`);
                return await response.json();
            } finally {
                clearTimeout(timeout);
            }
        }

        async function buscarGoogle(consulta, esISBN) {
            const q = esISBN ? `isbn:${consulta}` : consulta;
            const data = await obtenerJSON(`../api/admin/google-books.php?q=${encodeURIComponent(q)}`);
            return (data.items || []).map(item => {
                const info = item.volumeInfo || {};
                const identificadores = info.industryIdentifiers || [];
                const isbn = obtenerISBN(identificadores, '');
                if (esISBN && !identificadores.some(i => mismoISBN(i.identifier || '', consulta))) return null;
                return {
                    titulo: info.title || '', autor: (info.authors || []).join(', '), isbn,
                    editorial: info.publisher || '', numPaginas: info.pageCount || '',
                    descripcion: info.description || '', imagen: (info.imageLinks?.thumbnail || info.imageLinks?.smallThumbnail || '').replace(/^http:/, 'https:'),
                    fuente: 'Google Books'
                };
            }).filter(libro => libro?.titulo);
        }

        async function buscarOpenLibrary(consulta, esISBN) {
            const claveCache = `${esISBN ? 'isbn' : 'texto'}:${consulta}`;
            if (cacheOpenLibrary.has(claveCache)) return cacheOpenLibrary.get(claveCache);
            if (esISBN) {
                const clave = `ISBN:${consulta}`;
                const libros = [];
                let ficha = null;
                try {
                    const data = await obtenerJSON(`https://openlibrary.org/api/books?bibkeys=${encodeURIComponent(clave)}&jscmd=data&format=json`);
                    ficha = data[clave];
                } catch (error) { /* probar otra ruta */ }
                if (ficha) libros.push({
                    titulo: ficha.title || '', autor: (ficha.authors || []).map(a => a.name).join(', '), isbn: consulta,
                    editorial: (ficha.publishers || []).map(p => p.name).join(', '), numPaginas: ficha.number_of_pages || '',
                    descripcion: typeof ficha.notes === 'string' ? ficha.notes : '',
                    imagen: ficha.cover?.medium || ficha.cover?.small || '', fuente: 'Open Library'
                });
                if (!libros.length) {
                    await new Promise(resolve => setTimeout(resolve, 1100));
                    try {
                        const edicion = await obtenerJSON(`https://openlibrary.org/isbn/${encodeURIComponent(consulta)}.json`);
                        if (edicion?.title) libros.push({
                            titulo: edicion.title, autor: '', isbn: consulta,
                            editorial: (edicion.publishers || []).join(', '), numPaginas: edicion.number_of_pages || '',
                            descripcion: typeof edicion.description === 'string' ? edicion.description : edicion.description?.value || '',
                            imagen: edicion.covers?.[0] ? `https://covers.openlibrary.org/b/id/${edicion.covers[0]}-M.jpg` : '',
                            fuente: 'Open Library'
                        });
                    } catch (error) { /* probar búsqueda de ISBN */ }
                }
                if (libros.length && libros[0].autor) {
                    cacheOpenLibrary.set(claveCache, libros);
                    return libros;
                }
                await new Promise(resolve => setTimeout(resolve, 1100));
                let busqueda = null;
                try {
                    busqueda = await obtenerJSON(`https://openlibrary.org/search.json?isbn=${encodeURIComponent(consulta)}&limit=5&fields=title,author_name,isbn,publisher,number_of_pages_median,cover_i`);
                } catch (error) {
                    if (!libros.length) throw error;
                }
                for (const info of busqueda?.docs || []) {
                    if (!(info.isbn || []).some(valor => mismoISBN(valor, consulta))) continue;
                    libros.push({
                        titulo: info.title || '', autor: (info.author_name || []).slice(0, 2).join(', '), isbn: consulta,
                        editorial: (info.publisher || [])[0] || '', numPaginas: info.number_of_pages_median || '',
                        descripcion: '', imagen: info.cover_i ? `https://covers.openlibrary.org/b/id/${info.cover_i}-M.jpg` : '',
                        fuente: 'Open Library'
                    });
                }
                const resultado = libros.filter(libro => libro.titulo);
                cacheOpenLibrary.set(claveCache, resultado);
                return resultado;
            }
            const data = await obtenerJSON(`https://openlibrary.org/search.json?q=${encodeURIComponent(consulta)}&limit=10&fields=title,author_name,isbn,publisher,number_of_pages_median,cover_i`);
            const resultado = (data.docs || []).map(info => ({
                titulo: info.title || '', autor: (info.author_name || []).slice(0, 2).join(', '),
                isbn: (info.isbn || [])[0] || '', editorial: (info.publisher || [])[0] || '',
                numPaginas: info.number_of_pages_median || '', descripcion: '',
                imagen: info.cover_i ? `https://covers.openlibrary.org/b/id/${info.cover_i}-M.jpg` : '',
                fuente: 'Open Library'
            })).filter(libro => libro.titulo);
            cacheOpenLibrary.set(claveCache, resultado);
            return resultado;
        }

        async function buscarCrossref(isbn) {
            const url = `https://api.crossref.org/works?filter=isbn:${encodeURIComponent(isbn)}&rows=50&select=title,author,publisher,ISBN,type`;
            const data = await obtenerJSON(url);
            const tiposLibro = new Set(['book', 'monograph', 'edited-book', 'reference-book']);
            return (data.message?.items || [])
                .filter(item => tiposLibro.has(item.type) && (item.ISBN || []).some(valor => mismoISBN(valor, isbn)))
                .map(item => ({
                    titulo: item.title?.[0] || '',
                    autor: (item.author || []).map(a => [a.given, a.family].filter(Boolean).join(' ')).join(', '),
                    isbn, editorial: item.publisher || '', numPaginas: '', descripcion: '', imagen: '',
                    fuente: 'Crossref'
                }))
                .filter(libro => libro.titulo);
        }

        function cargarLibroEncontrado(libro) {
            const row = obtenerFilaDestino();
            for (const campo of ['titulo', 'autor', 'isbn', 'editorial', 'numPaginas', 'descripcion', 'imagen']) {
                row.querySelector(`[name="${campo}[]"]`).value = libro[campo] || '';
            }
            mostrarISBNMensaje('Datos cargados. Completá precio y stock, y verificá la edición antes de guardar.', 'success');
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

        function isbn13(valor) {
            const limpio = String(valor).replace(/[\s-]/g, '').toUpperCase();
            if (/^\d{13}$/.test(limpio)) return limpio;
            if (!/^\d{9}[\dX]$/.test(limpio)) return '';
            const base = `978${limpio.slice(0, 9)}`;
            const suma = [...base].reduce((total, digito, indice) => total + Number(digito) * (indice % 2 ? 3 : 1), 0);
            return base + ((10 - suma % 10) % 10);
        }

        function mismoISBN(a, b) {
            const primero = isbn13(a);
            return primero !== '' && primero === isbn13(b);
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
