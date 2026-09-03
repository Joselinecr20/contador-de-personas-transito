<?php

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
    exit;
}

function esFechaValida(?string $fecha): bool
{
    if ($fecha === null || $fecha === '') {
        return false;
    }
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return $d && $d->format('Y-m-d') === $fecha;
}

$desde = $_GET['desde'] ?? null;
$hasta = $_GET['hasta'] ?? null;

$where = '';
$params = [];

if (esFechaValida($desde) && esFechaValida($hasta)) {
    $where = 'WHERE fecha_hora BETWEEN :desde AND :hasta';
    $params['desde'] = $desde . ' 00:00:00';
    $params['hasta'] = $hasta . ' 23:59:59';
}

$pdo = getPdo();

$stmtTotales = $pdo->prepare(
    "SELECT tipo, COUNT(*) AS total FROM conteo {$where} GROUP BY tipo"
);
$stmtTotales->execute($params);

$totales = ['vehiculo' => 0, 'persona' => 0];
foreach ($stmtTotales->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $totales[$fila['tipo']] = (int) $fila['total'];
}

$stmtDesglose = $pdo->prepare(
    "SELECT clase, COUNT(*) AS total FROM conteo {$where} GROUP BY clase"
);
$stmtDesglose->execute($params);

$desglose = [];
foreach ($stmtDesglose->fetchAll(PDO::FETCH_ASSOC) as $fila) {
    $desglose[$fila['clase']] = (int) $fila['total'];
}

echo json_encode([
    'vehiculos' => $totales['vehiculo'],
    'personas' => $totales['persona'],
    'desglose' => $desglose,
]);
