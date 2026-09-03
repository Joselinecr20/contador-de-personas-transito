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

    # Se cuenta cada objeto una sola vez, la primera vez que aparece su ID de
    # tracking (no en cada frame en que sigue en pantalla). Si el mismo
    # objeto sale del cuadro y vuelve a entrar, ByteTrack le asigna un ID
    # nuevo y se cuenta de nuevo -- es el comportamiento esperado ("contar
    # al ingresar al cuadro").
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
                cv2.imshow("Detector de trafico y personas", frame)
                if cv2.waitKey(1) & 0xFF == ord("q"):
                    break

            ok, frame = cap.read()
    finally:
        cap.release()
        cv2.destroyAllWindows()


if __name__ == "__main__":
    main()
