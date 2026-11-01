
<?php

require_once __DIR__ . '/../auth.php';

// Si no hay sesión, regresar al login ubicado en la carpeta principal.
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
    <title>Detección en tiempo real</title>
    <link rel="stylesheet" href="styles.css">
    <script src="app.js" defer></script>
</head>
<body>

    <header class="encabezado">
        <h1>Detección en tiempo real</h1>
        <p>Bienvenido, <?= e($usuario['nombre']) ?></p>
        <a href="../index.php">Volver al panel</a>
    </header>

    <main class="contenedor">
        <section class="panel-camara">
            <h2>Cámara del dispositivo</h2>

            <video id="videoCamara" autoplay playsinline muted></video>

            <div class="acciones">
                <button id="btnIniciar" type="button">
                    Iniciar cámara
                </button>

                <button id="btnDetener" type="button" disabled>
                    Detener cámara
                </button>
            </div>

            <p id="estado" role="status">
                Cámara detenida.
            </p>
        </section>

        <section class="panel-deteccion">
            <h2>Video procesado</h2>
            <img id="videoProcesado" alt="Video procesado por el detector">
        </section>
    </main>

</body>
</html>
