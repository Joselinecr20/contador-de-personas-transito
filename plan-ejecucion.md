# Plan de ejecución — Contador de vehículos y personas por cámara

Basado en `Documentacion/planeacion-proyecto.txt`.

## 1. Objetivo

Detectar vehículos y personas en video de cámara (Python + `supervision`),
enviar cada detección/cruce exitoso a un endpoint PHP que lleva el conteo en
MySQL, y mostrarlo en una página Bootstrap con dos números grandes
(vehículos / personas) filtrable por rango de fechas, sin login.

## 2. Arquitectura

```
[Cámara / video] → [Python: YOLO + supervision (tracking + line-zone)]
        │  POST HTTP (JSON) por cada cruce único
        ▼
[PHP API en XAMPP] → [MySQL: tabla unica de conteo]
        ▲  GET (con filtro de fechas)
        │
[index.php: Bootstrap, tabla 2 columnas, números grandes, filtro fechas]
```

Dos procesos independientes: el script Python corre aparte (no dentro de
Apache) y le pega al endpoint PHP por HTTP a `http://localhost/proyecto1/api/...`.

## 3. Stack

- **Detección**: Python 3.10+, `ultralytics` (YOLOv8, clases COCO ya incluyen
  `person` y varias de vehículo: `car`, `truck`, `bus`, `motorcycle`), 
  `supervision` (tracking con ByteTrack + `LineZone`/`LineZoneAnnotator` para
  contar cruces sin duplicar), `opencv-python`, `requests`.
- **Backend**: PHP puro (mismo patrón simple, sin framework) sobre XAMPP,
  `PDO` para MySQL.
- **DB**: MySQL/MariaDB (el de XAMPP), una sola tabla.
- **Frontend**: HTML + Bootstrap 5 (CDN) + JS vanilla (`fetch`) para el
  filtro de fechas sin recargar.

## 4. Estructura de carpetas propuesta

```
proyecto1/
├── api/
│   ├── db.php              # conexión PDO
│   ├── registrar.php       # POST: recibe detección desde Python
│   └── conteo.php          # GET: totales (con filtro ?desde=&hasta=)
├── db/
│   └── schema.sql          # CREATE DATABASE + CREATE TABLE
├── detector/
│   ├── main.py              # loop de captura + detección + tracking + POST
│   ├── config.py            # URL del endpoint, fuente de video, umbrales
│   └── requirements.txt
├── index.php                 # página única: tabla + números grandes + filtro
└── Documentacion/            # (ya existe, fuera de git)
```

## 5. Base de datos (tabla única)

```sql
CREATE TABLE conteo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('vehiculo','persona') NOT NULL,
  clase VARCHAR(30) NOT NULL,        -- ej: car, truck, bus, motorcycle, person
  fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tipo_fecha (tipo, fecha_hora)
);
```

Cada fila = un cruce/detección único ya deduplicado por tracking (no un
snapshot de "cuántos hay en pantalla ahora").

## 6. Contrato de la API PHP

- `POST /api/registrar.php`
  Body JSON: `{"tipo":"vehiculo","clase":"car"}` (o `"persona"`).
  Inserta 1 fila con `fecha_hora = NOW()`. Responde `{"ok":true}`.

- `GET /api/conteo.php?desde=2026-09-01&hasta=2026-09-03`
  Sin parámetros → totales globales. Con `desde`/`hasta` → totales en ese
  rango (inclusive). Responde:
  `{"vehiculos": 123, "personas": 45}`

Sin autenticación (fuera de alcance según el planeamiento). Validar que
`tipo` sea uno de los dos valores permitidos antes de insertar (evitar
inyección de datos basura al ENUM).

## 7. Script de detección (Python)

- Captura de video: por defecto webcam (`cv2.VideoCapture(0)`), configurable
  por argumento a ruta de archivo o URL RTSP — así se puede evaluar con un
  video de prueba sin depender de tener una cámara físicamente conectada.
- Modelo YOLOv8 (`yolov8n.pt`, el más liviano, para correr en tiempo real
  sin GPU dedicada) filtrando solo clases relevantes:
  `person` → tipo `persona`; `car`, `motorcycle`, `bus`, `truck` → tipo
  `vehiculo`.
- Tracking con `supervision.ByteTrack` para asignar un ID persistente a cada
  objeto entre frames.
- Conteo con `supervision.LineZone`: se define una línea virtual en el
  frame; el conteo real ocurre cuando un track **cruza** la línea (evita
  contar el mismo objeto muchas veces mientras está en pantalla, que sería
  el bug más obvio de esta arquitectura).
- Al detectar un cruce nuevo, POST inmediato a `registrar.php` con
  `requests` (timeout corto, no bloquear el loop de video si el request
  falla — solo loguear el error).
- Overlay visual opcional (`supervision.LineZoneAnnotator` +
  `BoxAnnotator`) para poder verificar visualmente que cuenta bien durante
  desarrollo/evaluación.

## 8. Frontend (`index.php`)

- Bootstrap 5 vía CDN, sin build.
- Tabla de 2 columnas ("Vehículos" / "Personas"), números en fuente grande
  (ej. `display-1`), obtenidos vía `fetch('api/conteo.php')` al cargar.
- Formulario simple con dos `<input type="date">` (desde/hasta) + botón
  "Filtrar" que vuelve a pedir `conteo.php` con los parámetros y actualiza
  los mismos números in-place (misma página, sin reload), tal como pide el
  planeamiento.
- Botón/link "Ver todo" que limpia el filtro (vuelve a totales globales).

## 9. Fases de ejecución

**Fase 1 — Base de datos**
- [x] Crear `db/schema.sql` con la base de datos y la tabla `conteo`.
- [x] Ejecutar contra MySQL local de XAMPP.

**Fase 2 — API PHP**
- [x] `api/db.php` (conexión PDO).
- [x] `api/registrar.php` (POST, validación de `tipo`).
- [x] `api/conteo.php` (GET, con y sin filtro de fechas).
- [x] Probar ambos endpoints con `curl`/Postman antes de tocar Python.

**Fase 3 — Detección en Python**
- [x] `requirements.txt` + entorno virtual (instalado y probado).
- [x] Script base con YOLOv8 + `ByteTrack` + `LineZone` (conteo por cruce)
      y POST del evento hacia `registrar.php`.
- [x] Probado con webcam real: captura estable 25s+ sin errores ni falsos
      positivos de cruce. Se encontró y corrigió un bug real — el backend
      MSMF por defecto de OpenCV en Windows cortaba el stream a los ~17s;
      se cambió a `cv2.CAP_DSHOW` para la webcam (ver `main.py`).
- [ ] Pendiente: validar visualmente (overlay, `MOSTRAR_VENTANA=True`) que
      un cruce real de una persona/vehículo dispara el POST correcto — no
      se pudo forzar un cruce real durante la prueba automatizada.

**Fase 4 — Frontend**
- [x] `index.php` con la tabla y números grandes leyendo `conteo.php`.
- [x] Filtro de fechas con `fetch` en la misma página.

**Fase 5 — Integración y prueba end-to-end**
- [x] Correr XAMPP (Apache+MySQL) en paralelo al backend/frontend — probado
      con `curl` (registrar → conteo con y sin filtro de fechas, OK).
- [x] Detector real corriendo contra la webcam sin errores (25s+, backend
      DirectShow). Pipeline completo (captura → detección → tracking →
      línea de conteo) verificado; falta solo el paso manual de cruzar la
      línea frente a la cámara para confirmar el POST real end-to-end.
- [ ] Probar el filtro de fechas con datos de al menos 2 días distintos.

**Fase 6 — Documentación**
- [x] README corto: cómo levantar XAMPP, cómo correr el detector, cómo
      cambiar la fuente de video.

## 10. Decisiones confirmadas

- **Fuente de video**: webcam real (`cv2.VideoCapture(0)` por defecto). Se
  deja igual configurable a archivo/RTSP en `config.py` por si hace falta
  probar sin la cámara conectada, pero el flujo principal es webcam.
- **Desglose por tipo de vehículo**: sí. El campo `clase` (`car`,
  `motorcycle`, `bus`, `truck`, `person`) ya lo soporta; la página principal
  muestra el total agregado (vehículos/personas) pero `conteo.php` puede
  devolver también el desglose por `clase` para una vista futura de detalle.
- **Alcance PHP**: plano, standalone — sin relación con el framework
  `fac/`/LesliePhp. Es un laboratorio aparte que evoluciona en paralelo;
  se evaluará más adelante si se integra.
- **Ubicación/ángulo de la línea de conteo**: depende de la cámara real;
  se deja como configuración (coordenadas) en `config.py`, no hardcodeada.

## 12. Repositorio

- GitHub privado: `traffic-people-counter` (cuenta `bardcrack`).
- Colaborador: `Joselinecr20`.

## 11. Criterio de aceptación (según el planeamiento)

1. La detección funciona (se ve en overlay o logs que identifica
   vehículos/personas correctamente).
2. Cada detección exitosa llega por API al backend y se refleja en el
   conteo persistido en MySQL.
3. La página muestra los números correctos y el filtro por rango de
   fechas funciona sobre esos mismos datos.
