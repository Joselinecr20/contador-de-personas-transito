<?php

require __DIR__ . '/auth.php';

if (usuarioActual() !== null) {
    header('Location: index.php');
    exit;
}

$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!csrfValido($_POST['csrf'] ?? null)) {
        $error = 'La sesión expiró, intenta de nuevo.';
    } else {
        $stmt = getPdo()->prepare('SELECT id, nombre, email, password_hash FROM usuarios WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($password, $usuario['password_hash'])) {
            iniciarSesion($usuario);
            header('Location: index.php');
            exit;
        }
        $error = 'Email o contraseña incorrectos.';
    }
}

$registrado = isset($_GET['registrado']);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar sesión</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container py-5" style="max-width: 420px;">
    <h1 class="h3 mb-4 text-center">Iniciar sesión</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <?php if ($registrado): ?>
          <div class="alert alert-success">Cuenta creada. Ya puedes iniciar sesión.</div>
        <?php endif; ?>
        <?php if ($error): ?>
          <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php">
          <input type="hidden" name="csrf" value="<?= e(tokenCsrf()) ?>">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
      </div>
    </div>

    <p class="text-center mt-3 mb-0">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
  </div>
</body>
</html>
