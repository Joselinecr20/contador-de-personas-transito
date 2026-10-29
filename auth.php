<?php

require_once __DIR__ . '/api/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function usuarioActual(): ?array
{
    return $_SESSION['usuario'] ?? null;
}

function iniciarSesion(array $usuario): void
{
    session_regenerate_id(true);
    $_SESSION['usuario'] = [
        'id' => (int) $usuario['id'],
        'nombre' => $usuario['nombre'],
        'email' => $usuario['email'],
    ];
}

function cerrarSesion(): void
{
    $_SESSION = [];
    session_destroy();
}

// Para paginas HTML: si no hay sesion, manda al login.
function requiereLogin(): void
{
    if (usuarioActual() === null) {
        header('Location: login.php');
        exit;
    }
}

function requiereLoginApi(): void
{
    if (usuarioActual() === null) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'No autenticado']);
        exit;
    }
}

// Token CSRF: cada formulario POST lo manda en un campo oculto y se compara
function tokenCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfValido(?string $token): bool
{
    return is_string($token) && hash_equals(tokenCsrf(), $token);
}

function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
