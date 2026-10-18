<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
$_SESSION['csrf_admin'] ??= bin2hex(random_bytes(32));
?>
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
    require_once __DIR__ . '/../api/_shared/config.php';

    function esc($valor)
    {
        return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
    }

    function fmt_money($valor)
    {
        return number_format((float)$valor, 2, ',', '.');
    }

    $ventasTotal = 0;
    $ingresosTotal = 0;
    $ticketPromedio = 0;
    $ventasHoy = 0;
    $ventasSemana = 0;
    $ventasMes = 0;
    $ultimaVenta = null;
    $ventasPorPago = [];
    $ventasPorEntrega = [];
    $ventasPorDepto = [];

    $clientesTotal = 0;
    $clientesConPuntos = 0;
    $puntosTotales = 0;

    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(total), 0) AS ingresos, COALESCE(AVG(total), 0) AS ticket FROM ventas");
        $resumen = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resumen) {
            $ventasTotal = (int)$resumen['total'];
            $ingresosTotal = (float)$resumen['ingresos'];
            $ticketPromedio = (float)$resumen['ticket'];
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM ventas WHERE DATE(fecha_registro) = CURDATE()");
        $ventasHoy = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM ventas WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)");
        $ventasSemana = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT COUNT(*) FROM ventas WHERE fecha_registro >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        $ventasMes = (int)$stmt->fetchColumn();

        $stmt = $pdo->query("SELECT MAX(fecha_registro) FROM ventas");
        $ultimaVenta = $stmt->fetchColumn();

        $stmt = $pdo->query("SELECT metodo_pago, COUNT(*) AS total FROM ventas GROUP BY metodo_pago ORDER BY total DESC");
        $ventasPorPago = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->query("SELECT metodo_entrega, COUNT(*) AS total FROM ventas GROUP BY metodo_entrega ORDER BY total DESC");
        $ventasPorEntrega = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->query("SELECT departamento, COUNT(*) AS total FROM ventas GROUP BY departamento ORDER BY total DESC");
        $ventasPorDepto = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $pdo->query("SELECT COUNT(*) AS total, COALESCE(SUM(puntos), 0) AS puntos FROM clientes");
        $clientesResumen = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($clientesResumen) {
            $clientesTotal = (int)$clientesResumen['total'];
            $puntosTotales = (int)$clientesResumen['puntos'];
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM clientes WHERE puntos > 0");
        $clientesConPuntos = (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        // Si falla la BD, se muestran ceros para mantener el panel operativo.
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
                <p>Bienvenido: <strong><?php echo esc($_SESSION['admin_user'] ?? 'Admin'); ?></strong></p>
            </div>

            <div class="admin-content">
                <section class="admin-section admin-kpi-section">
                    <h2>Indicadores del negocio</h2>
                    <div class="admin-kpi-grid">
                        <div class="admin-kpi-card">
                            <h3>Ventas totales</h3>
                            <p class="admin-kpi-value"><?php echo esc($ventasTotal); ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Ingresos totales</h3>
                            <p class="admin-kpi-value">$<?php echo esc(fmt_money($ingresosTotal)); ?> UYU</p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Ticket promedio</h3>
                            <p class="admin-kpi-value">$<?php echo esc(fmt_money($ticketPromedio)); ?> UYU</p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Ventas hoy</h3>
                            <p class="admin-kpi-value"><?php echo esc($ventasHoy); ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Ventas semana</h3>
                            <p class="admin-kpi-value"><?php echo esc($ventasSemana); ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Ventas mes</h3>
                            <p class="admin-kpi-value"><?php echo esc($ventasMes); ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Ultima venta</h3>
                            <p class="admin-kpi-value"><?php echo $ultimaVenta ? esc($ultimaVenta) : 'Sin ventas'; ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Clientes registrados</h3>
                            <p class="admin-kpi-value"><?php echo esc($clientesTotal); ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Clientes con puntos</h3>
                            <p class="admin-kpi-value"><?php echo esc($clientesConPuntos); ?></p>
                        </div>
                        <div class="admin-kpi-card">
                            <h3>Puntos totales</h3>
                            <p class="admin-kpi-value"><?php echo esc($puntosTotales); ?></p>
                        </div>
                        <div class="admin-kpi-card admin-kpi-list">
                            <h3>Ventas por metodo de pago</h3>
                            <ul>
                                <?php if (empty($ventasPorPago)): ?>
                                    <li>Sin datos</li>
                                <?php else: ?>
                                    <?php foreach ($ventasPorPago as $fila): ?>
                                        <li><?php echo esc($fila['metodo_pago'] ?? 'N/D'); ?>: <?php echo esc($fila['total']); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="admin-kpi-card admin-kpi-list">
                            <h3>Ventas por metodo de entrega</h3>
                            <ul>
                                <?php if (empty($ventasPorEntrega)): ?>
                                    <li>Sin datos</li>
                                <?php else: ?>
                                    <?php foreach ($ventasPorEntrega as $fila): ?>
                                        <li><?php echo esc($fila['metodo_entrega'] ?? 'N/D'); ?>: <?php echo esc($fila['total']); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="admin-kpi-card admin-kpi-list">
                            <h3>Ventas por departamento</h3>
                            <ul>
                                <?php if (empty($ventasPorDepto)): ?>
                                    <li>Sin datos</li>
                                <?php else: ?>
                                    <?php foreach ($ventasPorDepto as $fila): ?>
                                        <li><?php echo esc($fila['departamento'] ?? 'N/D'); ?>: <?php echo esc($fila['total']); ?></li>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </section>

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
                    <h2>Buscar y editar libros</h2>
                    <div class="admin-search-box">
                        <div class="search-inputs">
                            <input type="search" id="buscar-libro" class="admin-input" placeholder="Título, autor, editorial, ISBN o ID" aria-label="Buscar libros">
                            <button class="admin-btn" id="btn-buscar">Buscar</button>
                        </div>
                        <p>Podés buscar varias palabras. Dejá el campo vacío para recorrer todo el catálogo.</p>
                        <div id="lista-libros" class="admin-book-results" aria-live="polite"></div>
                        <div id="paginacion-libros" class="admin-book-pagination" style="display: none;">
                            <button type="button" class="admin-btn" id="pagina-anterior">Anterior</button>
                            <span id="pagina-estado"></span>
                            <button type="button" class="admin-btn" id="pagina-siguiente">Siguiente</button>
                        </div>
                        <div id="resultado-busqueda" class="resultado-busqueda" style="display: none;">
                            <div class="libro-resultado">
                                <div class="libro-info">
                                    <p><strong>ID:</strong> <span id="libro-id"></span></p>
                                </div>
                                <div class="libro-edicion">
                                    <div class="form-group">
                                        <label>Título:</label>
                                        <input type="text" id="libro-titulo" class="admin-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Autor:</label>
                                        <input type="text" id="libro-autor" class="admin-input">
                                    </div>
                                    <div class="form-group">
                                        <label>ISBN:</label>
                                        <input type="text" id="libro-isbn" class="admin-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Editorial:</label>
                                        <input type="text" id="libro-editorial" class="admin-input">
                                    </div>
                                    <div class="form-group">
                                        <label>Número de páginas:</label>
                                        <input type="number" id="libro-num-paginas" class="admin-input" step="1" min="0">
                                    </div>
                                    <div class="form-group">
                                        <label>Precio de lista (UYU):</label>
                                        <input type="number" id="libro-precio" class="admin-input" step="1" min="0">
                                        <small>La tienda aplica automáticamente el 20% de descuento.</small>
                                    </div>
                                    <div class="form-group">
                                        <label>Descripción:</label>
                                        <textarea id="libro-descripcion" class="admin-input admin-textarea" rows="4"></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label>Imagen (ruta):</label>
                                        <input type="text" id="libro-imagen" class="admin-input">
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
        const listaLibros = document.getElementById('lista-libros');
        const paginacionLibros = document.getElementById('paginacion-libros');
        const paginaAnterior = document.getElementById('pagina-anterior');
        const paginaSiguiente = document.getElementById('pagina-siguiente');
        const paginaEstado = document.getElementById('pagina-estado');
        const mensajeBusqueda = document.getElementById('mensaje-busqueda');
        const btnGuardar = document.getElementById('btn-guardar');
        const csrfAdmin = <?php echo json_encode($_SESSION['csrf_admin']); ?>;
        let libroActualId = null;
        let ultimaBusqueda = 0;
        let paginaActual = 1;

        btnBuscar.addEventListener('click', buscarLibro);
        inputBuscar.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') buscarLibro();
        });
        btnGuardar.addEventListener('click', guardarCambios);
        paginaAnterior.addEventListener('click', () => buscarPagina(paginaActual - 1));
        paginaSiguiente.addEventListener('click', () => buscarPagina(paginaActual + 1));
        buscarLibro();

        async function buscarLibro() {
            return buscarPagina(1);
        }

        async function buscarPagina(pagina) {
            const numeroBusqueda = ++ultimaBusqueda;
            const termino = inputBuscar.value.trim();
            btnBuscar.disabled = true;
            listaLibros.textContent = 'Buscando libros...';
            paginacionLibros.style.display = 'none';
            resultadoBusqueda.style.display = 'none';
            libroActualId = null;
            try {
                const response = await fetch('../api/admin/buscar-libro-admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({ termino, pagina })
                });
                const data = await response.json();
                if (numeroBusqueda !== ultimaBusqueda) return;
                if (!response.ok || !data.success) throw new Error(data.message || 'Error al buscar libros');
                paginaActual = data.pagina;
                listaLibros.replaceChildren();
                if (!data.libros.length) {
                    listaLibros.textContent = 'No encontramos libros con esos datos. Probá con parte del título, autor o ISBN.';
                    return;
                }
                const resumen = document.createElement('p');
                resumen.textContent = `${data.total} libro(s) encontrados`;
                listaLibros.appendChild(resumen);
                data.libros.forEach(libro => {
                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'admin-book-result';
                    boton.textContent = `${libro.titulo} · ${libro.autor} · ISBN ${libro.isbn || 'sin dato'} · ID ${libro.id}`;
                    boton.addEventListener('click', () => seleccionarLibro(libro));
                    listaLibros.appendChild(boton);
                });
                paginacionLibros.style.display = 'flex';
                paginaEstado.textContent = `Página ${data.pagina} de ${data.paginas}`;
                paginaAnterior.disabled = data.pagina <= 1;
                paginaSiguiente.disabled = data.pagina >= data.paginas;
                mensajeBusqueda.style.display = 'none';
            } catch (error) {
                if (numeroBusqueda !== ultimaBusqueda) return;
                console.error('Error:', error);
                listaLibros.textContent = '';
                mostrarMensaje(error.message || 'Error al buscar libros', 'error');
            } finally {
                if (numeroBusqueda === ultimaBusqueda) btnBuscar.disabled = false;
            }
        }

        function seleccionarLibro(libro) {
            libroActualId = libro.id;
            document.getElementById('libro-titulo').value = libro.titulo || '';
            document.getElementById('libro-autor').value = libro.autor || '';
            document.getElementById('libro-isbn').value = libro.isbn || '';
            document.getElementById('libro-editorial').value = libro.editorial || '';
            document.getElementById('libro-num-paginas').value = libro.numPaginas ?? '';
            document.getElementById('libro-id').textContent = libro.id;
            document.getElementById('libro-precio').value = libro.precio ?? '';
            document.getElementById('libro-descripcion').value = libro.descripcion || '';
            document.getElementById('libro-imagen').value = libro.imagen || '';
            document.getElementById('libro-stock').value = libro.stock ?? '';
            resultadoBusqueda.style.display = 'block';
            resultadoBusqueda.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        async function guardarCambios() {
            if (!libroActualId) {
                mostrarMensaje('No hay libro seleccionado', 'error');
                return;
            }

            const titulo = document.getElementById('libro-titulo').value.trim();
            const autor = document.getElementById('libro-autor').value.trim();
            const isbn = document.getElementById('libro-isbn').value.trim();
            const editorial = document.getElementById('libro-editorial').value.trim();
            const numPaginas = document.getElementById('libro-num-paginas').value;
            const precio = document.getElementById('libro-precio').value;
            const descripcion = document.getElementById('libro-descripcion').value.trim();
            const imagen = document.getElementById('libro-imagen').value.trim();
            const stock = document.getElementById('libro-stock').value;

            if (!titulo || !autor) {
                mostrarMensaje('Ingresa título y autor', 'error');
                return;
            }

            if (precio === '' || stock === '') {
                mostrarMensaje('Ingresa precio y stock', 'error');
                return;
            }

            try {
                const response = await fetch('../api/admin/actualizar-libro-admin.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        id: libroActualId,
                        csrf: csrfAdmin,
                        titulo,
                        autor,
                        isbn,
                        editorial,
                        numPaginas,
                        precio,
                        descripcion,
                        imagen,
                        stock
                    }).toString()
                });

                const data = await response.json();

                if (data.success) {
                    await buscarPagina(paginaActual);
                    mostrarMensaje('Cambios guardados correctamente', 'success');
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
