# Configuracion del detector. Ajustar aqui, no en main.py.

# Endpoint del backend PHP que recibe cada cruce detectado.
ENDPOINT_URL = "http://localhost/proyecto1/api/registrar.php"

# Fuente de video: 0 = webcam por defecto. Tambien acepta ruta a un archivo
# de video o una URL RTSP, util para probar sin camara conectada.
VIDEO_SOURCE = 0

# Modelo YOLOv8 (el mas liviano, para correr en tiempo real sin GPU dedicada).
MODEL_PATH = "yolov8n.pt"
CONFIDENCE_THRESHOLD = 0.4

# Linea virtual de conteo: (x1, y1) -> (x2, y2) en pixeles del frame.
# Depende de la resolucion y el angulo real de la camara; ajustar en sitio.
LINE_START = (0, 360)
LINE_END = (1280, 360)

# Mapeo de clases COCO relevantes -> tipo que espera el backend.
CLASE_A_TIPO = {
    "person": "persona",
    "car": "vehiculo",
    "motorcycle": "vehiculo",
    "bus": "vehiculo",
    "truck": "vehiculo",
}

# Ventana con el overlay de deteccion/conteo, util en desarrollo y evaluacion.
MOSTRAR_VENTANA = True
