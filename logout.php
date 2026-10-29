<?php

require __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrfValido($_POST['csrf'] ?? null)) {
    cerrarSesion();
}

header('Location: login.php');
exit;
