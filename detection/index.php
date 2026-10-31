
<?php
require_once __DIR__ . '/../auth.php';

if (usuarioActual() === null) {
    header('Location: ../login.php');
    exit;
}

$usuario = usuarioActual();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#101827">

    <title>Detección en tiempo real | CountTrack</title>

    <link rel="stylesheet" href="styles.css">
</head>
<body>

    <aside class="barra-lateral">
        <a href="../index.php" class="marca">
            <span class="marca-icono">CT</span>
            <span class="marca-texto">
                <strong>CountTrack</strong>
                <small>Sistema inteligente</small>
            </span>
        </a>

        <div class="menu-titulo">MENÚ PRINCIPAL</div>

        <nav class="menu">
            <a href="../index.php" class="menu-enlace">
                <span class="menu-icono">▦</span>
                <span>Panel principal</span>
            </a>

            <a href="index.php" class="menu-enlace activo">
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

            <form method="post" action="../logout.php">
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
                <h1>Detección en tiempo real</h1>
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
                <h2>Monitoreo de cámara</h2>
                <p>
                    Visualiza la cámara y el procesamiento de personas
                    y vehículos en tiempo real.
                </p>
            </div>

            <div class="estado-general" id="estadoGeneral">
                <span class="estado-punto"></span>
                <span>Detector desconectado</span>
            </div>
        </section>

        <section class="tarjetas-informacion">

            <article class="tarjeta">
                <span class="tarjeta-icono">▣</span>
                <div>
                    <h3>Fuente de video</h3>
                    <p>Cámara del dispositivo móvil</p>
                </div>
            </article>

            <article class="tarjeta">
                <span class="tarjeta-icono">◉</span>
                <div>
                    <h3>Procesamiento</h3>
                    <p>Modelo YOLOv8</p>
                </div>
            </article>

            <article class="tarjeta">
                <span class="tarjeta-icono">◎</span>
                <div>
                    <h3>Seguimiento</h3>
                    <p>ByteTrack</p>
                </div>
            </article>

        </section>

        <section class="panel-camaras">

            <article class="panel-video">
                <div class="panel-video-cabecera">
                    <div>
                        <h2>Cámara original</h2>
                        <p>Imagen capturada desde el dispositivo</p>
                    </div>

                    <span class="indicador-video">EN VIVO</span>
                </div>

                <div class="contenedor-video">
                    <video
                        id="videoCamara"
                        autoplay
                        playsinline
                        muted
                    ></video>
                </div>
            </article>

            <article class="panel-video">
                <div class="panel-video-cabecera">
                    <div>
                        <h2>Detección procesada</h2>
                        <p>Personas y vehículos identificados</p>
                    </div>

                    <span class="indicador-procesamiento">
                        YOLOv8
                    </span>
                </div>

                <div class="contenedor-video">
                    <img
                        id="videoProcesado"
                        alt="Video procesado con detecciones"
                    >
                </div>
            </article>

        </section>

        <section class="panel-controles">

            <div class="titulo-seccion">
                <div>
                    <h2>Controles de detección</h2>
                    <p>
                        Inicia o detén la captura de video desde tu dispositivo.
                    </p>
                </div>
            </div>

            <div class="controles">
                <button type="button" id="btnIniciar" class="boton-iniciar">
                    <span>▶</span>
                    Iniciar detección
                </button>

                <button
                    type="button"
                    id="btnDetener"
                    class="boton-detener"
                    disabled
                >
                    <span>■</span>
                    Detener detección
                </button>
            </div>

            <p id="estado" class="mensaje-estado" role="status" aria-live="polite">
                Cámara detenida. Presiona iniciar para comenzar.
            </p>

        </section>

        <footer class="pie-pagina">
            <span>CountTrack · Sistema de conteo inteligente</span>
            <span>Detección mediante YOLOv8 y ByteTrack</span>
        </footer>

    </main>

    <script src="app.js"></script>

</body>
</html>
