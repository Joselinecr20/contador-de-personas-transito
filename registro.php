<?php

require __DIR__ . '/auth.php';

if (usuarioActual() !== null) {
    header('Location: index.php');
    exit;
}

const PASSWORD_MIN_LARGO = 8;

$errores = [];
$nombre = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmacion = $_POST['password_confirmacion'] ?? '';

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $errores[] = 'La sesión expiró, intenta de nuevo.';
    }
    if ($nombre === '' || mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre es obligatorio (máximo 100 caracteres).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errores[] = 'El email no es válido.';
    }
    if (mb_strlen($password) < PASSWORD_MIN_LARGO) {
        $errores[] = 'La contraseña debe tener al menos ' . PASSWORD_MIN_LARGO . ' caracteres.';
    }
    if ($password !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        $stmt = getPdo()->prepare(
            'INSERT INTO usuarios (nombre, email, password_hash) VALUES (:nombre, :email, :hash)'
        );
        try {
            $stmt->execute([
                'nombre' => $nombre,
                'email' => $email,
                'hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
            header('Location: login.php?registrado=1');
            exit;
        } catch (PDOException $ex) {
            // 23000 = violacion de UNIQUE: el email ya existe.
            if ($ex->getCode() !== '23000') {
                throw $ex;
            }
            $errores[] = 'Ya existe una cuenta con ese email.';
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Crear cuenta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width: 420px;">
    <h1 class="h3 mb-4 text-center">Crear cuenta</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <?php if ($errores): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" action="registro.php">
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
          <div class="mb-3">
            <label for="nombre" class="form-label">Nombre</label>
            <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e($nombre) ?>" maxlength="100" required autofocus>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" maxlength="150" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" minlength="<?= PASSWORD_MIN_LARGO ?>" required>
          </div>
          <div class="mb-3">
            <label for="password_confirmacion" class="form-label">Repetir contraseña</label>
            <input type="password" class="form-control" id="password_confirmacion" name="password_confirmacion" minlength="<?= PASSWORD_MIN_LARGO ?>" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Crear cuenta</button>
        </form>
      </div>
    </div>

    <p class="text-center mt-3 mb-0">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
  </div>
</body>
</html>
