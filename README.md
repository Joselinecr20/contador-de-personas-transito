# contador-de-personas-transito

Laboratorio de conteo de vehículos y personas a partir de cámara (webcam)
usando detección/tracking en Python (`ultralytics` + `supervision`), con un
backend PHP plano (sin framework) que persiste el conteo en MySQL y una
página con Bootstrap que muestra los totales y permite filtrar por rango de
fechas.

Ver el plan de ejecución completo en [plan-ejecucion.md](plan-ejecucion.md).

Proyecto standalone, sin relación con el framework LesliePhp/`fac/`.

## Cómo levantarlo

1. **Base de datos**: corre en un contenedor MySQL 8.4 (ya no se usa el
   MySQL de XAMPP; dejarlo apagado porque usa el mismo puerto 3306). Con
   Docker Desktop abierto, desde la raíz del proyecto:
   ```
   docker compose up -d
   ```
   La primera vez (volumen vacío) aplica `db/schema.sql` automáticamente.
   Datos de conexión: `127.0.0.1:3306`, base `traffic_people_counter`,
   usuario `contador` / `contador_pass` (root: `root_contador`).

   Para aplicar el esquema a mano, o restaurar la copia completa
   (`db/backup.sql`, generada con `mysqldump`):
   ```
   docker exec -i contador-mysql mysql -uroot -proot_contador < db/schema.sql
   docker exec -i contador-mysql mysql -uroot -proot_contador < db/backup.sql
   ```
2. **Backend + frontend**: proyecto ya vive en `htdocs/`, solo hace falta
   Apache de XAMPP corriendo. Página en `http://localhost/proyecto1/`.
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
