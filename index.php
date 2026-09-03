<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Conteo de tráfico y personas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5">
    <h1 class="mb-4 text-center">Conteo de vehículos y personas</h1>

    <div class="row text-center g-4 mb-5">
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="card-title text-muted">Vehículos</h2>
            <p class="display-1 fw-bold text-primary mb-0" id="totalVehiculos">0</p>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="card shadow-sm">
          <div class="card-body">
            <h2 class="card-title text-muted">Personas</h2>
            <p class="display-1 fw-bold text-success mb-0" id="totalPersonas">0</p>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <form id="formFiltro" class="row g-3 align-items-end">
          <div class="col-auto">
            <label for="desde" class="form-label">Desde</label>
            <input type="date" class="form-control" id="desde">
          </div>
          <div class="col-auto">
            <label for="hasta" class="form-label">Hasta</label>
            <input type="date" class="form-control" id="hasta">
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-primary">Filtrar</button>
          </div>
          <div class="col-auto">
            <button type="button" class="btn btn-outline-secondary" id="btnLimpiar">Ver todo</button>
          </div>
        </form>
      </div>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h5 card-title">Desglose por clase</h2>
        <table class="table table-striped mb-0">
          <thead>
            <tr><th>Clase</th><th class="text-end">Cantidad</th></tr>
          </thead>
          <tbody id="tablaDesglose">
            <tr><td colspan="2" class="text-muted">Sin datos</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    const totalVehiculos = document.getElementById('totalVehiculos');
    const totalPersonas = document.getElementById('totalPersonas');
    const tablaDesglose = document.getElementById('tablaDesglose');
    const formFiltro = document.getElementById('formFiltro');
    const desdeInput = document.getElementById('desde');
    const hastaInput = document.getElementById('hasta');
    const btnLimpiar = document.getElementById('btnLimpiar');

    async function cargarConteo(desde, hasta) {
      let url = 'api/conteo.php';
      if (desde && hasta) {
        url += `?desde=${encodeURIComponent(desde)}&hasta=${encodeURIComponent(hasta)}`;
      }

      const respuesta = await fetch(url);
      const datos = await respuesta.json();

      totalVehiculos.textContent = datos.vehiculos;
      totalPersonas.textContent = datos.personas;

      const clases = Object.entries(datos.desglose || {});
      tablaDesglose.innerHTML = clases.length
        ? clases.map(([clase, cantidad]) => `<tr><td>${clase}</td><td class="text-end">${cantidad}</td></tr>`).join('')
        : '<tr><td colspan="2" class="text-muted">Sin datos</td></tr>';
    }

    formFiltro.addEventListener('submit', (evento) => {
      evento.preventDefault();
      cargarConteo(desdeInput.value, hastaInput.value);
    });

    btnLimpiar.addEventListener('click', () => {
      desdeInput.value = '';
      hastaInput.value = '';
      cargarConteo();
    });

    cargarConteo();
  </script>
</body>
</html>
