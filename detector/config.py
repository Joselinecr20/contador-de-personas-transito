# Configuracion del detector. Todo puede sobreescribirse con variables de
# entorno (ver README) para levantar el servicio distinto sin tocar este
# archivo; si la variable no esta seteada, se usa el valor por defecto de aca.

import os


def _env_video_source(nombre: str, default: int):
    valor = os.environ.get(nombre)
    if valor is None:
        return default
    return int(valor) if valor.isdigit() else valor


def _env_float(nombre: str, default: float) -> float:
    valor = os.environ.get(nombre)
    return float(valor) if valor is not None else default


def _env_bool(nombre: str, default: bool) -> bool:
    valor = os.environ.get(nombre)
    if valor is None:
        return default
    return valor.strip().lower() in ("1", "true", "yes", "on")


def _env_point(nombre: str, default: tuple[int, int]) -> tuple[int, int]:
    valor = os.environ.get(nombre)
    if valor is None:
        return default
    x, y = valor.split(",")
    return (int(x), int(y))


# Endpoint del backend PHP que recibe cada cruce detectado.
ENDPOINT_URL = os.environ.get("DETECTOR_ENDPOINT_URL", "http://localhost/proyecto1/api/registrar.php")

# Fuente de video: 0 = webcam por defecto. Tambien acepta ruta a un archivo
# de video o una URL RTSP, util para probar sin camara conectada.
VIDEO_SOURCE = _env_video_source("DETECTOR_VIDEO_SOURCE", 0)

# Modelo YOLOv8 (el mas liviano, para correr en tiempo real sin GPU dedicada).
MODEL_PATH = os.environ.get("DETECTOR_MODEL_PATH", "yolov8n.pt")
CONFIDENCE_THRESHOLD = _env_float("DETECTOR_CONFIDENCE_THRESHOLD", 0.4)

# Linea virtual de conteo: (x1, y1) -> (x2, y2) en pixeles del frame.
# Depende de la resolucion y el angulo real de la camara; ajustar en sitio.
LINE_START = _env_point("DETECTOR_LINE_START", (0, 360))
LINE_END = _env_point("DETECTOR_LINE_END", (1280, 360))

# Mapeo de clases COCO relevantes -> tipo que espera el backend.
CLASE_A_TIPO = {
    "person": "persona",
    "car": "vehiculo",
    "motorcycle": "vehiculo",
    "bus": "vehiculo",
    "truck": "vehiculo",
}

# Ventana con el overlay de deteccion/conteo, util en desarrollo y evaluacion.
MOSTRAR_VENTANA = _env_bool("DETECTOR_MOSTRAR_VENTANA", True)
