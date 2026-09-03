import threading

import cv2
import numpy as np
import requests
import supervision as sv
from ultralytics import YOLO

import config


def enviar_evento(tipo: str, clase: str) -> None:
    try:
        requests.post(config.ENDPOINT_URL, json={"tipo": tipo, "clase": clase}, timeout=2)
    except requests.RequestException as error:
        print(f"[WARN] no se pudo enviar el evento ({tipo}/{clase}): {error}")


def main() -> None:
    model = YOLO(config.MODEL_PATH)
    name_to_id = {name: class_id for class_id, name in model.names.items()}
    ids_relevantes = {name_to_id[nombre] for nombre in config.CLASE_A_TIPO if nombre in name_to_id}

    tracker = sv.ByteTrack()
    box_annotator = sv.BoxAnnotator()
    label_annotator = sv.LabelAnnotator()
    line_zone_annotator = sv.LineZoneAnnotator()

    # En Windows, el backend MSMF por defecto de OpenCV corta el stream de
    # varias webcams a los pocos segundos (error interno del driver);
    # DirectShow es mas estable para dispositivos de captura locales.
    if isinstance(config.VIDEO_SOURCE, int):
        cap = cv2.VideoCapture(config.VIDEO_SOURCE, cv2.CAP_DSHOW)
    else:
        cap = cv2.VideoCapture(config.VIDEO_SOURCE)

    if not cap.isOpened():
        raise RuntimeError(f"No se pudo abrir la fuente de video: {config.VIDEO_SOURCE!r}")

    ok, frame = cap.read()
    if not ok:
        raise RuntimeError("La camara se abrio pero no entrego ningun frame")

    # La linea por defecto se calcula con la resolucion real del primer frame
    # (horizontal, a media altura) en vez de un tamano fijo que puede no
    # coincidir con la camara conectada.
    alto, ancho = frame.shape[:2]
    inicio_linea = config.LINE_START or (0, alto // 2)
    fin_linea = config.LINE_END or (ancho, alto // 2)
    line_zone = sv.LineZone(start=sv.Point(*inicio_linea), end=sv.Point(*fin_linea))

    try:
        while ok:
            resultado = model(frame, verbose=False)[0]
            detections = sv.Detections.from_ultralytics(resultado)

            mascara = (detections.confidence >= config.CONFIDENCE_THRESHOLD) & np.isin(
                detections.class_id, list(ids_relevantes)
            )
            detections = detections[mascara]
            detections = tracker.update_with_detections(detections)

            crossed_in, crossed_out = line_zone.trigger(detections)
            for idx in np.where(crossed_in | crossed_out)[0]:
                clase = model.names[detections.class_id[idx]]
                tipo = config.CLASE_A_TIPO[clase]
                threading.Thread(target=enviar_evento, args=(tipo, clase), daemon=True).start()
                print(f"Cruce detectado: {clase} -> {tipo}")

            if config.MOSTRAR_VENTANA:
                etiquetas = [
                    f"{model.names[clase_id]} {conf:.2f}"
                    for clase_id, conf in zip(detections.class_id, detections.confidence)
                ]
                frame = box_annotator.annotate(scene=frame, detections=detections)
                frame = label_annotator.annotate(scene=frame, detections=detections, labels=etiquetas)
                frame = line_zone_annotator.annotate(frame=frame, line_counter=line_zone)
                cv2.imshow("Detector de trafico y personas", frame)
                if cv2.waitKey(1) & 0xFF == ord("q"):
                    break

            ok, frame = cap.read()
    finally:
        cap.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    main()
