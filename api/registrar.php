<?php

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

const CLASES_POR_TIPO = [
    'persona' => ['person'],
    'vehiculo' => ['car', 'motorcycle', 'bus', 'truck'],
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'ok' => false,
        'error' => 'Metodo no permitido'
    ]);
    exit;
}

$claveConfigurada = getenv('DETECTOR_API_KEY');

if ($claveConfigurada === false || $claveConfigurada === '') {
    http_response_code(503);
    echo json_encode([
        'ok' => false,
        'error' => 'Autenticacion del detector no configurada'
    ]);
    exit;
}

$claveRecibida = $_SERVER['HTTP_X_DETECTOR_KEY'] ?? '';

if (!is_string($claveRecibida) || !hash_equals($claveConfigurada, $claveRecibida)) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'No autorizado'
    ]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'JSON invalido'
    ]);
    exit;
}

$tipo = $body['tipo'] ?? null;
$clase = $body['clase'] ?? null;

if (
    !is_string($tipo) ||
    !is_string($clase) ||
    !isset(CLASES_POR_TIPO[$tipo]) ||
    !in_array($clase, CLASES_POR_TIPO[$tipo], true)
) {
    http_response_code(400);
    echo json_encode([
        'ok' => false,
        'error' => 'tipo/clase invalidos'
    ]);
    exit;
}

$stmt = getPdo()->prepare(
    'INSERT INTO conteo (tipo, clase) VALUES (:tipo, :clase)'
);

$stmt->execute([
    'tipo' => $tipo,
    'clase' => $clase
]);

echo json_encode(['ok' => true]);