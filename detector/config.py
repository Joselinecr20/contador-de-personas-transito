# Configuracion del detector.
# Los valores se pueden modificar mediante variables de entorno.

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


# Endpoint del backend PHP.
ENDPOINT_URL = os.environ.get(
    "DETECTOR_ENDPOINT_URL",
    "http://localhost/proyecto1/api/registrar.php"
)

# Clave privada para autenticar las solicitudes del detector.
API_KEY = os.environ.get("DETECTOR_API_KEY", "")

# Fuente de video: 0 = webcam por defecto.
VIDEO_SOURCE = _env_video_source("DETECTOR_VIDEO_SOURCE", 0)

# Modelo YOLOv8.
MODEL_PATH = os.environ.get("DETECTOR_MODEL_PATH", "yolov8n.pt")

CONFIDENCE_THRESHOLD = _env_float(
    "DETECTOR_CONFIDENCE_THRESHOLD",
    0.4
)

# Tamaño al que YOLO redimensiona cada imagen. 320 mantiene fluido el video
# del celular (~70 ms por imagen en CPU). 480 (el ancho que manda el celular)
# detecta más objetos lejanos pero tarda ~120 ms; 640 no detecta más y tarda
# ~170 ms.
IMG_SIZE = int(os.environ.get("DETECTOR_IMG_SIZE", "320"))

# Mapeo de clases COCO relevantes.
CLASE_A_TIPO = {
    "person": "persona",
    "car": "vehiculo",
    "motorcycle": "vehiculo",
    "bus": "vehiculo",
    "truck": "vehiculo",
}

# Mostrar ventana con detecciones.
MOSTRAR_VENTANA = _env_bool("DETECTOR_MOSTRAR_VENTANA", True)
