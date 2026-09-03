<?php

require __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

const CLASES_POR_TIPO = [
    'persona' => ['person'],
    'vehiculo' => ['car', 'motorcycle', 'bus', 'truck'],
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Metodo no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$tipo = $body['tipo'] ?? null;
$clase = $body['clase'] ?? null;

if (!isset(CLASES_POR_TIPO[$tipo]) || !in_array($clase, CLASES_POR_TIPO[$tipo], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'tipo/clase invalidos']);
    exit;
}

$stmt = getPdo()->prepare('INSERT INTO conteo (tipo, clase) VALUES (:tipo, :clase)');
$stmt->execute(['tipo' => $tipo, 'clase' => $clase]);

echo json_encode(['ok' => true]);
