
const videoCamara = document.getElementById('videoCamara');
const videoProcesado = document.getElementById('videoProcesado');
const btnIniciar = document.getElementById('btnIniciar');
const btnDetener = document.getElementById('btnDetener');
const estado = document.getElementById('estado');
const estadoGeneral = document.getElementById('estadoGeneral');

let flujoCamara = null;
let conexionSocket = null;
let intervaloEnvio = null;
let urlImagenAnterior = null;
let enviandoFotograma = false;

const INTERVALO_FOTOGRAMAS = 200;
const CALIDAD_IMAGEN = 0.5;

function actualizarEstadoGeneral(activo, mensaje) {
    const color = activo ? '#22c55e' : '#64748b';

    estadoGeneral.innerHTML = `
        <span class="indicador-punto"
              style="background:${color}"></span>
        ${mensaje}
    `;
}

function actualizarEstado(mensaje) {
    estado.textContent = mensaje;
}

async function iniciarCamara() {
    try {
        btnIniciar.disabled = true;
        btnDetener.disabled = true;

        actualizarEstado('Comprobando acceso a la cámara...');
        actualizarEstadoGeneral(false, 'Conectando');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error(
                'getUserMedia no está disponible. Verifica que uses HTTPS y un navegador compatible.'
            );
        }

        actualizarEstado('Solicitando permiso para utilizar la cámara...');

        flujoCamara = await navigator.mediaDevices.getUserMedia({
            audio: false,
            video: {
                facingMode: 'environment',
                width: { ideal: 640 },
                height: { ideal: 480 }
            }
        });

        actualizarEstado('Cámara obtenida. Iniciando reproducción...');

        videoCamara.srcObject = flujoCamara;

        await videoCamara.play();

        actualizarEstado('Cámara iniciada. Conectando con el detector Python...');

        conectarDetector();

    } catch (error) {
        console.error('Error al iniciar la cámara:', error);

        actualizarEstado(
            `Error: ${error.name || 'Error desconocido'} - ${error.message}`
        );

        actualizarEstadoGeneral(false, 'Error');

        detenerCamara();
    }
}

function conectarDetector() {
    const protocolo = window.location.protocol === 'https:' ? 'wss:' : 'ws:';

    const direccionSocket =
        `${protocolo}//${window.location.host}/proyecto1/ws/deteccion`;

    actualizarEstado('Conectando con el detector Python...');

    conexionSocket = new WebSocket(direccionSocket);
    conexionSocket.binaryType = 'blob';

    conexionSocket.addEventListener('open', () => {
        actualizarEstado('Cámara activa. Detector conectado.');
        actualizarEstadoGeneral(true, 'Activo');

        btnIniciar.disabled = true;
        btnDetener.disabled = false;

        iniciarEnvioFotogramas();
    });

    conexionSocket.addEventListener('message', (evento) => {
        if (!(evento.data instanceof Blob)) {
            return;
        }

        const nuevaImagen = URL.createObjectURL(evento.data);

        videoProcesado.src = nuevaImagen;

        if (urlImagenAnterior) {
            URL.revokeObjectURL(urlImagenAnterior);
        }

        urlImagenAnterior = nuevaImagen;

        enviandoFotograma = false;
    });

    conexionSocket.addEventListener('error', () => {
        actualizarEstado(
            'Error de WebSocket: no se pudo conectar con FastAPI mediante Apache.'
        );

        actualizarEstadoGeneral(false, 'Error de conexión');
    });

    conexionSocket.addEventListener('close', (evento) => {
        detenerEnvioFotogramas();

        if (flujoCamara) {
            flujoCamara.getTracks().forEach((pista) => pista.stop());
            flujoCamara = null;
        }

        videoCamara.srcObject = null;

        if (urlImagenAnterior) {
            URL.revokeObjectURL(urlImagenAnterior);
            urlImagenAnterior = null;
        }

        conexionSocket = null;

        btnIniciar.disabled = false;
        btnDetener.disabled = true;

        actualizarEstado(
            `Conexión WebSocket cerrada. Código: ${evento.code}`
        );

        actualizarEstadoGeneral(false, 'Inactivo');
    });
}

function iniciarEnvioFotogramas() {
    const canvas = document.createElement('canvas');
    const contexto = canvas.getContext('2d');

canvas.width = 480;
canvas.height = 360;

    intervaloEnvio = setInterval(() => {
        if (
            !conexionSocket ||
            conexionSocket.readyState !== WebSocket.OPEN ||
            videoCamara.readyState < HTMLMediaElement.HAVE_CURRENT_DATA ||
            enviandoFotograma
        ) {
            return;
        }

        enviandoFotograma = true;

        contexto.drawImage(
            videoCamara,
            0,
            0,
            canvas.width,
            canvas.height
        );

        canvas.toBlob((fotograma) => {
            if (
                !fotograma ||
                !conexionSocket ||
                conexionSocket.readyState !== WebSocket.OPEN
            ) {
                enviandoFotograma = false;
                return;
            }

            try {
                conexionSocket.send(fotograma);
            } catch (error) {
                enviandoFotograma = false;
                console.error('Error al enviar el fotograma:', error);
            }

        }, 'image/jpeg', CALIDAD_IMAGEN);

    }, INTERVALO_FOTOGRAMAS);
}

function detenerEnvioFotogramas() {
    if (intervaloEnvio !== null) {
        clearInterval(intervaloEnvio);
        intervaloEnvio = null;
    }

    enviandoFotograma = false;
}

function detenerCamara() {
    detenerEnvioFotogramas();

    if (conexionSocket) {
        const socket = conexionSocket;
        conexionSocket = null;

        if (
            socket.readyState === WebSocket.OPEN ||
            socket.readyState === WebSocket.CONNECTING
        ) {
            socket.close();
        }
    }

    if (flujoCamara) {
        flujoCamara.getTracks().forEach((pista) => pista.stop());
        flujoCamara = null;
    }

    videoCamara.srcObject = null;

    if (urlImagenAnterior) {
        URL.revokeObjectURL(urlImagenAnterior);
        urlImagenAnterior = null;
    }

    enviandoFotograma = false;

    btnIniciar.disabled = false;
    btnDetener.disabled = true;

    actualizarEstadoGeneral(false, 'Inactivo');
}

btnIniciar.addEventListener('click', iniciarCamara);
btnDetener.addEventListener('click', detenerCamara);

window.addEventListener('beforeunload', detenerCamara);
