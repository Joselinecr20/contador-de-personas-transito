import threading

import cv2
import numpy as np
import requests
import supervision as sv
from ultralytics import YOLO

import config

# Guarda la ultima peticion/respuesta a la API para pintarla sobre el video.
# El lock existe porque enviar_evento corre en un hilo aparte por evento.
_estado_lock = threading.Lock()
_ultima_peticion = ""
_ultima_respuesta = ""


def enviar_evento(tipo: str, clase: str) -> None:
    global _ultima_peticion, _ultima_respuesta
    payload = {"tipo": tipo, "clase": clase}
    peticion_str = f"POST {config.ENDPOINT_URL} body={payload}"
    print(f"[API] {peticion_str}")
    with _estado_lock:
        _ultima_peticion = peticion_str
        _ultima_respuesta = "esperando respuesta..."
    try:
        respuesta = requests.post(config.ENDPOINT_URL, json=payload, timeout=2)
        respuesta_str = f"{respuesta.status_code}: {respuesta.text}"
        print(f"[API] respuesta {respuesta_str}")
        with _estado_lock:
            _ultima_respuesta = respuesta_str
    except requests.RequestException as error:
        print(f"[WARN] no se pudo enviar el evento ({tipo}/{clase}): {error}")
        with _estado_lock:
            _ultima_respuesta = f"ERROR: {error}"


def _dibujar_estado_api(frame: np.ndarray, peticion: str, respuesta: str) -> np.ndarray:
    alto, ancho = frame.shape[:2]
    max_chars = max(20, ancho // 9)
    lineas = [f"REQ: {peticion[:max_chars]}", f"RES: {respuesta[:max_chars]}"]

    caja_alto = 22 * len(lineas) + 10
    overlay = frame.copy()
    cv2.rectangle(overlay, (0, alto - caja_alto), (ancho, alto), (0, 0, 0), -1)
    frame = cv2.addWeighted(overlay, 0.6, frame, 0.4, 0)

    for i, linea in enumerate(lineas):
        y = alto - caja_alto + 20 + i * 22
        cv2.putText(frame, linea, (8, y), cv2.FONT_HERSHEY_SIMPLEX, 0.45, (255, 255, 255), 1, cv2.LINE_AA)
    return frame


def main() -> None:
    model = YOLO(config.MODEL_PATH)
    name_to_id = {name: class_id for class_id, name in model.names.items()}
    ids_relevantes = {name_to_id[nombre] for nombre in config.CLASE_A_TIPO if nombre in name_to_id}

    tracker = sv.ByteTrack()
    box_annotator = sv.BoxAnnotator()
    label_annotator = sv.LabelAnnotator()

    # El backend MSMF de OpenCV corta el stream de varias webcams en Windows
    # a los pocos segundos; DirectShow no tiene ese problema.
    if isinstance(config.VIDEO_SOURCE, int):
        cap = cv2.VideoCapture(config.VIDEO_SOURCE, cv2.CAP_DSHOW)
    else:
        cap = cv2.VideoCapture(config.VIDEO_SOURCE)

    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la fuente de video: {config.VIDEO_SOURCE!r}")

    ok, frame = cap.read()
    if not ok:
        raise RuntimeError("La camara se abrio pero no entrego ningun frame")

    # Se cuenta una vez por ID de tracking, apenas aparece (no en cada frame).
    # Si el objeto sale del cuadro y vuelve a entrar, ByteTrack le da un ID
    # nuevo y se cuenta otra vez; es justo lo que queremos.
    ids_contados: set[int] = set()

    try:
        while ok:
            resultado = model(frame, verbose=False)[0]
            detections = sv.Detections.from_ultralytics(resultado)

            mascara = (detections.confidence >= config.CONFIDENCE_THRESHOLD) & np.isin(
                detections.class_id, list(ids_relevantes)
            )
            detections = detections[mascara]
            detections = tracker.update_with_detections(detections)

            for tracker_id, class_id in zip(detections.tracker_id, detections.class_id):
                if tracker_id is None or tracker_id in ids_contados:
                    continue
                ids_contados.add(tracker_id)

                clase = model.names[class_id]
                tipo = config.CLASE_A_TIPO[clase]
                threading.Thread(target=enviar_evento, args=(tipo, clase), daemon=True).start()
                print(f"Nuevo objeto contado: {clase} -> {tipo}")

            if config.MOSTRAR_VENTANA:
                etiquetas = [
                    f"{model.names[clase_id]} {conf:.2f}"
                    for clase_id, conf in zip(detections.class_id, detections.confidence)
                ]
                frame = box_annotator.annotate(scene=frame, detections=detections)
                frame = label_annotator.annotate(scene=frame, detections=detections, labels=etiquetas)

                with _estado_lock:
                    peticion_actual, respuesta_actual = _ultima_peticion, _ultima_respuesta
                if peticion_actual:
                    frame = _dibujar_estado_api(frame, peticion_actual, respuesta_actual)

                cv2.imshow("Detector de trafico y personas", frame)
                if cv2.waitKey(1) & 0xFF == ord("q"):
                    break

            ok, frame = cap.read()
    finally:
        cap.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    main()
