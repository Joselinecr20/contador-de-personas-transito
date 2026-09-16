# contador-de-personas-transito

Laboratorio de conteo de vehículos y personas a partir de cámara (webcam)
usando detección/tracking en Python (`ultralytics` + `supervision`), con un
backend PHP plano (sin framework) que persiste el conteo en MySQL y una
página con Bootstrap que muestra los totales y permite filtrar por rango de
fechas.

Ver el plan de ejecución completo en [plan-ejecucion.md](plan-ejecucion.md).

Proyecto standalone, sin relación con el framework LesliePhp/`fac/`.

## Cómo levantarlo

1. **Base de datos**: con MySQL de XAMPP corriendo, aplicar el esquema:
   ```
   mysql -u root < db/schema.sql
   ```
   También hay una copia completa (`db/backup.sql`, generada con
   `mysqldump`) por si se prefiere restaurar la base tal cual en vez de
   crearla desde cero — sirve igual, incluye la misma estructura:
   ```
   mysql -u root < db/backup.sql
   ```
2. **Backend + frontend**: proyecto ya vive en `htdocs/`, solo hace falta
   Apache corriendo. Página en `http://localhost/proyecto1/`.
3. **Detector** (requiere webcam). Primera vez, crear el entorno virtual e
   instalar dependencias:
   ```
   cd detector
   python -m venv venv
   ./venv/Scripts/pip install -r requirements.txt
   ```
   Cada vez que se quiera correr, activar el venv antes de usar `python`
   (si no, `python main.py` va a fallar con `ModuleNotFoundError` porque
   usa el Python global en vez del venv):
   ```
   . venv/Scripts/activate
   python main.py
   ```
   Ajustar `detector/config.py` para cambiar la fuente de video o el
   umbral de confianza. `q` con la ventana de overlay enfocada la cierra.

   Cada persona/vehículo se cuenta una sola vez, en el momento en que
   entra al cuadro de la cámara (no en cada frame que sigue en pantalla) —
   se deduplica por el ID de tracking que le asigna `ByteTrack`. Si el
   mismo objeto sale del cuadro y vuelve a entrar, se cuenta de nuevo (se
   le asigna un ID nuevo).

## Configurar el detector (`main.py`) por variables de entorno

Todos los valores de `detector/config.py` tienen un default, pero se
pueden sobreescribir con variables de entorno al levantar el servicio —
útil para correrlo contra otro backend, otra cámara, o sin ventana (modo
servicio/headless) sin tocar el código:

| Variable                        | Default                                          | Descripción                                    |
|----------------------------------|---------------------------------------------------|-------------------------------------------------|
| `DETECTOR_ENDPOINT_URL`          | `http://localhost/proyecto1/api/registrar.php`     | URL del endpoint PHP que recibe cada objeto contado |
| `DETECTOR_VIDEO_SOURCE`          | `0` (primera webcam)                               | Índice de cámara, ruta a video o URL RTSP       |
| `DETECTOR_MODEL_PATH`            | `yolov8n.pt`                                       | Pesos del modelo YOLOv8 a usar                  |
| `DETECTOR_CONFIDENCE_THRESHOLD`  | `0.4`                                              | Confianza mínima para considerar una detección  |
| `DETECTOR_MOSTRAR_VENTANA`       | `true`                                             | `false` para correr sin ventana de overlay      |

**Git Bash / MINGW64** (con el venv ya activado):
```bash
DETECTOR_VIDEO_SOURCE=1 DETECTOR_MOSTRAR_VENTANA=false python main.py
```

**PowerShell**:
```powershell
$env:DETECTOR_VIDEO_SOURCE = "1"
$env:DETECTOR_MOSTRAR_VENTANA = "false"
python main.py
```

Sin ninguna variable seteada, corre igual que antes con los defaults de
`config.py` (webcam 0, ventana visible, endpoint local).
