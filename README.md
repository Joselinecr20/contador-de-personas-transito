# traffic-people-counter

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
2. **Backend + frontend**: proyecto ya vive en `htdocs/`, solo hace falta
   Apache corriendo. Página en `http://localhost/proyecto1/`.
3. **Detector** (requiere webcam):
   ```
   cd detector
   python -m venv venv
   ./venv/Scripts/pip install -r requirements.txt
   ./venv/Scripts/python main.py
   ```
   Ajustar `detector/config.py` para cambiar la fuente de video, la línea
   de conteo o el umbral de confianza. `q` cierra la ventana de overlay.
