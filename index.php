
<?php

require __DIR__ . '/auth.php';

requiereLogin();
$usuario = usuarioActual();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#172b4d">

    <title>Panel principal | Sistema de conteo</title>

    <link rel="stylesheet" href="dashboard.css">
</head>
<body>

    <aside class="barra-lateral">
        <a href="index.php" class="marca">
            <span class="marca-icono">CT</span>
            <span class="marca-texto">
                <strong>CountTrack</strong>
                <small>Sistema inteligente</small>
            </span>
        </a>

        <div class="menu-titulo">MENÚ PRINCIPAL</div>

        <nav class="menu">
            <a href="index.php" class="menu-enlace activo">
                <span class="menu-icono">▦</span>
                <span>Panel principal</span>
            </a>

            <a href="detection/" class="menu-enlace">
                <span class="menu-icono">◉</span>
                <span>Detección en tiempo real</span>
            </a>
        </nav>

        <div class="lateral-inferior">
            <div class="estado-sistema">
                <span class="estado-punto"></span>
                <div>
                    <strong>Sistema de conteo</strong>
                    <small>Panel de monitoreo</small>
                </div>
            </div>

            <form method="post" action="logout.php">
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(tokenCsrf()) ?>"
                >

                <button type="submit" class="boton-salir">
                    <span>↪</span>
                    Cerrar sesión
                </button>
            </form>
        </div>
    </aside>

    <main class="contenido-principal">

        <header class="barra-superior">
            <div>
                <span class="subtitulo">SISTEMA DE MONITOREO</span>
                <h1>Panel principal</h1>
            </div>

            <div class="usuario">
                <span class="usuario-avatar">
                    <?= e(strtoupper(substr($usuario['nombre'], 0, 1))) ?>
                </span>

                <div class="usuario-info">
                    <strong><?= e($usuario['nombre']) ?></strong>
                    <small>Usuario</small>
                </div>
            </div>
        </header>

        <section class="bienvenida">
            <div>
                <h2>Resumen de actividad</h2>
                <p>
                    Consulta el registro de personas y vehículos detectados
                    por el sistema.
                </p>
            </div>

            <a href="detection/" class="boton-deteccion">
                <span>◉</span>
                Abrir detección en tiempo real
            </a>
        </section>

        <section class="tarjetas-conteo" aria-label="Resumen de conteos">

            <article class="tarjeta tarjeta-vehiculos">
                <div class="tarjeta-superior">
                    <span class="tarjeta-etiqueta">TOTAL REGISTRADO</span>
                    <span class="tarjeta-icono">▰</span>
                </div>

                <h2>Vehículos</h2>

                <p class="cantidad" id="totalVehiculos" aria-live="polite">
                    0
                </p>

                <span class="tarjeta-descripcion">
                    Vehículos detectados
                </span>
            </article>

            <article class="tarjeta tarjeta-personas">
                <div class="tarjeta-superior">
                    <span class="tarjeta-etiqueta">TOTAL REGISTRADO</span>
                    <span class="tarjeta-icono">♙</span>
                </div>

                <h2>Personas</h2>

                <p class="cantidad" id="totalPersonas" aria-live="polite">
                    0
                </p>

                <span class="tarjeta-descripcion">
                    Personas detectadas
                </span>
            </article>

        </section>

        <section class="panel-filtros">
            <div class="titulo-seccion">
                <div>
                    <h2>Filtrar registros</h2>
                    <p>Selecciona un período para consultar los resultados.</p>
                </div>

                <span class="filtro-icono">⌕</span>
            </div>

            <form id="formFiltro" class="formulario-filtros">

                <div class="campo">
                    <label for="desde">Fecha inicial</label>
                    <input type="date" id="desde" name="desde">
                </div>

                <div class="campo">
                    <label for="hasta">Fecha final</label>
                    <input type="date" id="hasta" name="hasta">
                </div>

                <button type="submit" class="boton-filtrar">
                    Aplicar filtros
                </button>

                <button
                    type="button"
                    class="boton-limpiar"
                    id="btnLimpiar">
                    Ver todos
                </button>

            </form>

            <p id="mensajeError" class="mensaje-error" role="alert"></p>
        </section>

        <section class="panel-tabla">

            <div class="titulo-seccion">
                <div>
                    <h2>Desglose por clase</h2>
                    <p>Cantidad de objetos registrados según su clasificación.</p>
                </div>

                <span class="etiqueta-tabla">REGISTROS</span>
            </div>

            <div class="tabla-contenedor">
                <table>
                    <thead>
                        <tr>
                            <th>Clase detectada</th>
                            <th class="columna-cantidad">Cantidad</th>
                        </tr>
                    </thead>

                    <tbody id="tablaDesglose">
                        <tr>
                            <td colspan="2" class="sin-datos">
                                Cargando información...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </section>

        <footer class="pie-pagina">
            <span>CountTrack · Sistema de conteo inteligente</span>
            <span>Actualización automática cada 3 segundos</span>
        </footer>

    </main>

    <script>
        const totalVehiculos = document.getElementById('totalVehiculos');
        const totalPersonas = document.getElementById('totalPersonas');
        const tablaDesglose = document.getElementById('tablaDesglose');

        const formFiltro = document.getElementById('formFiltro');
        const desdeInput = document.getElementById('desde');
        const hastaInput = document.getElementById('hasta');
        const btnLimpiar = document.getElementById('btnLimpiar');
        const mensajeError = document.getElementById('mensajeError');

        const INTERVALO_ACTUALIZACION_MS = 3000;

        let cargaEnCurso = false;

        async function cargarConteo(desde = '', hasta = '') {
            if (cargaEnCurso) {
                return;
            }

            cargaEnCurso = true;
            mensajeError.textContent = '';

            try {
                let url = 'api/conteo.php';

                if (desde && hasta) {
                    const parametros = new URLSearchParams({
                        desde: desde,
                        hasta: hasta
                    });

                    url += `?${parametros.toString()}`;
                }

                const respuesta = await fetch(url);

                if (respuesta.status === 401) {
                    window.location.href = 'login.php';
                    return;
                }

                if (!respuesta.ok) {
                    throw new Error('No se pudo obtener la información del servidor.');
                }

                const datos = await respuesta.json();

                if (
                    typeof datos.vehiculos !== 'number' ||
                    typeof datos.personas !== 'number' ||
                    typeof datos.desglose !== 'object' ||
                    datos.desglose === null ||
                    Array.isArray(datos.desglose)
                ) {
                    throw new Error('El servidor devolvió información inválida.');
                }

                totalVehiculos.textContent = datos.vehiculos;
                totalPersonas.textContent = datos.personas;

                mostrarDesglose(datos.desglose);

            } catch (error) {
                mensajeError.textContent = error.message;

            } finally {
                cargaEnCurso = false;
            }
        }

        function mostrarDesglose(desglose) {
            tablaDesglose.replaceChildren();

            const clases = Object.entries(desglose);

            if (clases.length === 0) {
                const fila = document.createElement('tr');
                const celda = document.createElement('td');

                celda.colSpan = 2;
                celda.className = 'sin-datos';
                celda.textContent = 'No hay registros para este período.';

                fila.appendChild(celda);
                tablaDesglose.appendChild(fila);

                return;
            }

            clases.forEach(([clase, cantidad]) => {
                const fila = document.createElement('tr');

                const celdaClase = document.createElement('td');
                const celdaCantidad = document.createElement('td');

                celdaClase.textContent = clase;
                celdaCantidad.textContent = cantidad;
                celdaCantidad.className = 'columna-cantidad';

                fila.appendChild(celdaClase);
                fila.appendChild(celdaCantidad);

                tablaDesglose.appendChild(fila);
            });
        }

        formFiltro.addEventListener('submit', (evento) => {
            evento.preventDefault();

            const desde = desdeInput.value;
            const hasta = hastaInput.value;

            if ((desde && !hasta) || (!desde && hasta)) {
                mensajeError.textContent =
                    'Debes seleccionar ambas fechas para aplicar el filtro.';
                return;
            }

            if (desde && hasta && desde > hasta) {
                mensajeError.textContent =
                    'La fecha inicial no puede ser posterior a la fecha final.';
                return;
            }

            cargarConteo(desde, hasta);
        });

        btnLimpiar.addEventListener('click', () => {
            desdeInput.value = '';
            hastaInput.value = '';

            mensajeError.textContent = '';

            cargarConteo();
        });

        cargarConteo();

        setInterval(() => {
            cargarConteo(desdeInput.value, hastaInput.value);
        }, INTERVALO_ACTUALIZACION_MS);
    </script>

</body>
</html>
