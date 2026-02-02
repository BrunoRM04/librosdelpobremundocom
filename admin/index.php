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

                <section class="admin-section">
                    <h2>Buscar y Editar Libro</h2>
                    <div class="admin-search-box">
                        <div class="search-inputs">
                            <input type="text" id="buscar-libro" class="admin-input" placeholder="Buscar por nombre o ISBN...">
                            <button class="admin-btn" id="btn-buscar">Buscar</button>
                        </div>
                        <div id="resultado-busqueda" class="resultado-busqueda" style="display: none;">
                            <div class="libro-resultado">
                                <div class="libro-info">
                                    <p><strong>Título:</strong> <span id="libro-titulo"></span></p>
                                    <p><strong>Autor:</strong> <span id="libro-autor"></span></p>
                                    <p><strong>ISBN:</strong> <span id="libro-isbn"></span></p>
                                    <p><strong>ID:</strong> <span id="libro-id"></span></p>
                                </div>
                                <div class="libro-edicion">
                                    <div class="form-group">
                                        <label>Precio (UYU):</label>
                                        <input type="number" id="libro-precio" class="admin-input" step="1" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label>Stock:</label>
                                        <input type="number" id="libro-stock" class="admin-input" step="1" min="0">
                                    </div>
                                    <button class="admin-btn admin-btn-save" id="btn-guardar">Guardar cambios</button>
                                </div>
                            </div>
                        </div>
                        <div id="mensaje-busqueda" class="admin-mensaje" style="display: none;"></div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
        const btnBuscar = document.getElementById('btn-buscar');
        const inputBuscar = document.getElementById('buscar-libro');
        const resultadoBusqueda = document.getElementById('resultado-busqueda');
        const mensajeBusqueda = document.getElementById('mensaje-busqueda');
        const btnGuardar = document.getElementById('btn-guardar');
        let libroActualId = null;

        btnBuscar.addEventListener('click', buscarLibro);
        inputBuscar.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') buscarLibro();
        });
        btnGuardar.addEventListener('click', guardarCambios);

        async function buscarLibro() {
            const termino = inputBuscar.value.trim();
            if (!termino) {
                mostrarMensaje('Por favor ingresa un nombre o ISBN', 'error');
                resultadoBusqueda.style.display = 'none';
                return;
            }

            try {
                const response = await fetch('../api/buscar-libro-admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `termino=${encodeURIComponent(termino)}`
                });

                const data = await response.json();

                if (data.success && data.libro) {
                    const libro = data.libro;
                    libroActualId = libro.id;
                    
                    document.getElementById('libro-titulo').textContent = libro.titulo;
                    document.getElementById('libro-autor').textContent = libro.autor;
                    document.getElementById('libro-isbn').textContent = libro.isbn || 'N/A';
                    document.getElementById('libro-id').textContent = libro.id;
                    document.getElementById('libro-precio').value = libro.precio;
                    document.getElementById('libro-stock').value = libro.stock;

                    resultadoBusqueda.style.display = 'block';
                    mensajeBusqueda.style.display = 'none';
                } else {
                    mostrarMensaje(data.message || 'Libro no encontrado', 'error');
                    resultadoBusqueda.style.display = 'none';
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error al buscar el libro', 'error');
                resultadoBusqueda.style.display = 'none';
            }
        }

        async function guardarCambios() {
            if (!libroActualId) {
                mostrarMensaje('No hay libro seleccionado', 'error');
                return;
            }

            const precio = document.getElementById('libro-precio').value;
            const stock = document.getElementById('libro-stock').value;

            if (precio === '' || stock === '') {
                mostrarMensaje('Ingresa precio y stock', 'error');
                return;
            }

            try {
                const response = await fetch('../api/actualizar-libro-admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `id=${encodeURIComponent(libroActualId)}&precio=${encodeURIComponent(precio)}&stock=${encodeURIComponent(stock)}`
                });

                const data = await response.json();

                if (data.success) {
                    mostrarMensaje('Cambios guardados correctamente', 'success');
                    setTimeout(() => {
                        resultadoBusqueda.style.display = 'none';
                        inputBuscar.value = '';
                    }, 1500);
                } else {
                    mostrarMensaje(data.message || 'Error al guardar', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                mostrarMensaje('Error al guardar cambios', 'error');
            }
        }

        function mostrarMensaje(texto, tipo) {
            mensajeBusqueda.textContent = texto;
            mensajeBusqueda.className = `admin-mensaje ${tipo}`;
            mensajeBusqueda.style.display = 'block';
            if (tipo === 'success') {
                setTimeout(() => {
                    mensajeBusqueda.style.display = 'none';
                }, 3000);
            }
        }
    </script>