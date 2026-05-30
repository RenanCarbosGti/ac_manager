<?php
session_start();
// Se já estiver logado, redireciona
if (isset($_SESSION["idusuario"])) {
    header("location:dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AC Manager – Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background: linear-gradient(135deg, #0d6efd, #0a58ca); min-height: 100vh; display:flex; align-items:center; }
    .card { border:none; border-radius:16px; box-shadow: 0 8px 32px rgba(0,0,0,.25); }
    .logo-icon { font-size: 3rem; color: #0d6efd; }
  </style>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-4 col-sm-10">
        <div class="card p-4">
          <div class="text-center mb-3">
            <i class="bi bi-wind logo-icon"></i>
            <h4 class="fw-bold mt-1">AC Manager</h4>
            <p class="text-muted small">Sistema de Gestão de Serviços</p>
          </div>

          <?php if (isset($_SESSION["erro_login"])): ?>
            <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <?php echo $_SESSION["erro_login"]; unset($_SESSION["erro_login"]); ?>
              <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="post" action="controller/LoginController.php">
            <div class="mb-3">
              <label class="form-label fw-semibold">E-mail</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" class="form-control" name="txtEmail"
                       placeholder="seu@email.com" required autofocus>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label fw-semibold">Senha</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control" name="txtSenha"
                       placeholder="••••••••" required>
              </div>
            </div>
            <div class="d-grid mt-4">
              <button type="submit" name="btEntrar" class="btn btn-primary btn-lg">
                <i class="bi bi-box-arrow-in-right me-1"></i> Entrar
              </button>
            </div>
          </form>
          <p class="text-center text-muted small mt-3 mb-0">
            Acesso restrito a profissionais cadastrados
          </p>
        </div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
