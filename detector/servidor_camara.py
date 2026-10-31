
import asyncio
import time

import cv2
import numpy as np
import requests
import supervision as sv

from fastapi import FastAPI, WebSocket, WebSocketDisconnect
from ultralytics import YOLO

import config


app = FastAPI(title="Detector de cámara móvil")

# El modelo se carga una sola vez.
modelo = YOLO(config.MODEL_PATH)

# Evita que varias conexiones procesen el modelo simultáneamente.
bloqueo_detector = asyncio.Lock()

# Elimina cajas muy superpuestas antes de asignar identificadores.
UMBRAL_NMS = 0.5


def registrar_evento(tipo: str, clase: str) -> None:
    if not config.API_KEY:
        print("[API] Error: DETECTOR_API_KEY no esta configurada.")
        return

    payload = {
        "tipo": tipo,
        "clase": clase
    }

    headers = {
        "X-Detector-Key": config.API_KEY
    }

    try:
        respuesta = requests.post(
            config.ENDPOINT_URL,
            json=payload,
            headers=headers,
            timeout=3
        )

        respuesta.raise_for_status()
        print(f"[API] Registrado: {tipo} / {clase}")

    except requests.RequestException as error:
        print(f"[API] Error al registrar {tipo}/{clase}: {error}")


async def registrar_evento_async(tipo: str, clase: str) -> None:
    # La petición HTTP se ejecuta en un hilo para no bloquear FastAPI.
    await asyncio.to_thread(registrar_evento, tipo, clase)


def procesar_frame(
    frame: np.ndarray,
    tracker: sv.ByteTrack,
    ids_contados: set
) -> tuple[np.ndarray, list[tuple[str, str]]]:

    resultado = modelo(frame, verbose=False)[0]
    detecciones = sv.Detections.from_ultralytics(resultado)

    nombres = modelo.names
    eventos = []

    ids_relevantes = {
        class_id
        for class_id, nombre in nombres.items()
        if nombre in config.CLASE_A_TIPO
    }

    if detecciones.class_id is None:
        return frame, eventos

    mascara = (
        (detecciones.confidence >= config.CONFIDENCE_THRESHOLD)
        & np.isin(detecciones.class_id, list(ids_relevantes))
    )

    detecciones = detecciones[mascara]

    # Evita que cajas superpuestas representen varias veces el mismo objeto.
    detecciones = detecciones.with_nms(
        threshold=UMBRAL_NMS
    )

    # ByteTrack recibe las detecciones después de eliminar duplicados.
    detecciones = tracker.update_with_detections(detecciones)

    if detecciones.tracker_id is not None:
        for tracker_id, class_id in zip(
            detecciones.tracker_id,
            detecciones.class_id
        ):
            if tracker_id is None or tracker_id in ids_contados:
                continue

            ids_contados.add(tracker_id)

            clase = nombres[int(class_id)]
            tipo = config.CLASE_A_TIPO[clase]
            eventos.append((tipo, clase))

    etiquetas = [
        f"{nombres[int(class_id)]} {conf:.2f}"
        for class_id, conf in zip(
            detecciones.class_id,
            detecciones.confidence
        )
    ]

    anotador_cajas = sv.BoxAnnotator()
    anotador_etiquetas = sv.LabelAnnotator()

    frame = anotador_cajas.annotate(
        scene=frame,
        detections=detecciones
    )

    frame = anotador_etiquetas.annotate(
        scene=frame,
        detections=detecciones,
        labels=etiquetas
    )

    return frame, eventos


def procesar_datos_fotograma(
    datos: bytes,
    tracker: sv.ByteTrack,
    ids_contados: set
) -> tuple[bytes | None, list[tuple[str, str]]]:

    inicio_total = time.perf_counter()

    # Medir la decodificación de la imagen recibida.
    inicio = time.perf_counter()

    imagen = cv2.imdecode(
        np.frombuffer(datos, dtype=np.uint8),
        cv2.IMREAD_COLOR
    )

    tiempo_decodificacion = time.perf_counter() - inicio

    if imagen is None:
        return None, []

    # Medir YOLO, ByteTrack y anotaciones.
    inicio = time.perf_counter()

    imagen_procesada, eventos = procesar_frame(
        imagen,
        tracker,
        ids_contados
    )

    tiempo_deteccion = time.perf_counter() - inicio

    # Medir la codificación de la imagen procesada.
    inicio = time.perf_counter()

    correcto, buffer = cv2.imencode(
        ".jpg",
        imagen_procesada
    )

    tiempo_codificacion = time.perf_counter() - inicio

    tiempo_total = time.perf_counter() - inicio_total

    print(
        f"[RENDIMIENTO] "
        f"Decodificación: {tiempo_decodificacion * 1000:.1f} ms | "
        f"YOLO + ByteTrack: {tiempo_deteccion * 1000:.1f} ms | "
        f"Codificación: {tiempo_codificacion * 1000:.1f} ms | "
        f"Total: {tiempo_total * 1000:.1f} ms"
    )

    if not correcto:
        return None, eventos

    return buffer.tobytes(), eventos


@app.get("/")
def estado():
    return {
        "ok": True,
        "servicio": "Detector de cámara móvil"
    }


@app.websocket("/ws/deteccion")
async def detectar(websocket: WebSocket):
    await websocket.accept()

    tracker = sv.ByteTrack()
    ids_contados = set()

    print("[CAMARA] Nueva sesión iniciada")

    try:
        async with bloqueo_detector:
            while True:
                datos = await websocket.receive_bytes()

                # YOLO, OpenCV y el procesamiento se ejecutan fuera del event loop.
                imagen_bytes, eventos = await asyncio.to_thread(
                    procesar_datos_fotograma,
                    datos,
                    tracker,
                    ids_contados
                )

                if imagen_bytes is None:
                    continue

                # Se responde primero al navegador para mantener fluido el video.
                await websocket.send_bytes(imagen_bytes)

                # El registro PHP se realiza en segundo plano y conserva la API Key.
                for tipo, clase in eventos:
                    asyncio.create_task(
                        registrar_evento_async(tipo, clase)
                    )

    except WebSocketDisconnect:
        print("[CAMARA] Sesión finalizada")

    except Exception as error:
        print(f"[CAMARA] Error: {error}")

        try:
            await websocket.close(code=1011)
        except RuntimeError:
            pass
