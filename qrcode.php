<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "dao/EquipamentoDao.php";

$id    = (int)($_GET["id"] ?? 0);
$eDao  = new EquipamentoDao();
$equip = $eDao->readId($id);

if (!$equip) {
    header("location:indexequipamento.php");
    exit;
}

// QR aponta para a página PÚBLICA (acessível pelo celular do cliente)
$urlBase    = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
$urlPublica = $urlBase . "/equipamento_publico.php?qr=" . urlencode($equip["codigo_qr"]);
$qrImgUrl   = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($urlPublica);
// Versão maior para visualização na tela
$qrImgGrande = "https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=" . urlencode($urlPublica);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>QR Code – <?php echo htmlspecialchars($equip["nome_cliente"]); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background: #f0f4f8; }

    @media print {
      body * { visibility: hidden; }
      .qr-card, .qr-card * { visibility: visible; }
      .qr-card {
        position: fixed;
        top: 0; left: 0;
        width: 3cm !important;
        height: 4cm !important;
        margin: 0; padding: 4px !important;
        border: 1px solid #000 !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        page-break-inside: avoid;
      }
      .qr-card img { width: 78px !important; height: 78px !important; }
    }

    /* Cartão 3×4 cm — visível na tela e imprimível */
    .qr-card {
      width:  113px;  /* ≈ 3 cm a 96 dpi */
      height: 151px;  /* ≈ 4 cm a 96 dpi */
      border: 2px solid #0d6efd;
      border-radius: 10px;
      padding: 7px 6px 5px;
      background: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 10px rgba(0,0,0,.15);
      font-size: 0; /* reset */
    }
    .qr-card .brand  { font-size: 7.5px; font-weight: 700; color: #0d6efd; letter-spacing: .6px; text-align: center; }
    .qr-card img     { width: 82px; height: 82px; display: block; }
    .qr-card .linha  { font-size: 6.5px; color: #333; text-align: center; line-height: 1.35; width: 100%; }
    .qr-card .linha strong { display: block; font-size: 7px; color: #111; }
    .qr-card .linha .cod  { font-family: monospace; font-size: 5.8px; color: #555; word-break: break-all; }
  </style>
</head>
<body>
<div class="container py-4">

  <!-- Barra de ações (não aparece na impressão) -->
  <div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
    <a href="indexequipamento.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i> Voltar
    </a>
    <button onclick="window.print()" class="btn btn-primary btn-sm">
      <i class="bi bi-printer me-1"></i> Imprimir etiqueta 3×4 cm
    </button>
    <a href="<?php echo $urlPublica; ?>" target="_blank" class="btn btn-outline-success btn-sm">
      <i class="bi bi-eye me-1"></i> Ver como cliente vê
    </a>
    <span class="text-muted small">
      <i class="bi bi-info-circle me-1"></i>
      Cole o cartão no equipamento. O cliente escaneia e vê o histórico + contato da empresa.
    </span>
  </div>

  <div class="row g-4 align-items-start">

    <!-- Coluna esquerda: dados + QR grande -->
    <div class="col-md-5">
      <div class="card mb-3">
        <div class="card-header bg-primary text-white fw-semibold">
          <i class="bi bi-cpu me-2"></i>Dados do Equipamento
        </div>
        <div class="card-body">
          <table class="table table-sm mb-0">
            <tr><th>Cliente:</th><td><?php echo htmlspecialchars($equip["nome_cliente"]); ?></td></tr>
            <tr><th>Endereço:</th><td><?php echo htmlspecialchars($equip["endereco"]); ?></td></tr>
            <tr><th>Telefone:</th><td><?php echo htmlspecialchars($equip["telefone"]); ?></td></tr>
            <tr><th>Marca/Modelo:</th><td><?php echo htmlspecialchars(trim($equip["marca"] . " " . $equip["modelo"])); ?></td></tr>
            <tr><th>Código QR:</th><td><code class="small"><?php echo htmlspecialchars($equip["codigo_qr"]); ?></code></td></tr>
          </table>
        </div>
      </div>

      <!-- QR grande para conferência -->
      <div class="card text-center p-3">
        <p class="text-muted small mb-2">
          <i class="bi bi-qr-code me-1"></i>
          QR Code gerado — aponta para a página pública do equipamento
        </p>
        <img src="<?php echo $qrImgGrande; ?>" alt="QR Code"
             class="img-fluid mx-auto d-block" style="max-width:200px; border-radius:8px;">
        <p class="text-muted mt-2 mb-0" style="font-size:.72rem; word-break:break-all;">
          <?php echo htmlspecialchars($urlPublica); ?>
        </p>
      </div>
    </div>

    <!-- Coluna direita: cartão imprimível 3×4 -->
    <div class="col-md-7">
      <div class="card p-4 text-center">
        <h6 class="fw-semibold mb-1">Etiqueta para impressão (3×4 cm)</h6>
        <p class="text-muted small mb-3">
          Cole no equipamento após a impressão.
          Ao escanear, o cliente visualiza o histórico de serviços e o contato da empresa.
        </p>

        <!-- Cartão real 3×4 -->
        <div class="qr-card mx-auto">
          <div class="brand">❄ AC MANAGER</div>
          <img src="<?php echo $qrImgUrl; ?>" alt="QR Code">
          <div class="linha">
            <strong><?php echo htmlspecialchars(mb_substr($equip["nome_cliente"], 0, 18)); ?></strong>
            <span class="cod"><?php echo htmlspecialchars($equip["codigo_qr"]); ?></span>
          </div>
        </div>

        <p class="text-muted mt-3 mb-0 small">
          <i class="bi bi-phone me-1"></i>
          O cliente escaneia com a câmera do celular e acessa a página de histórico automaticamente.
        </p>
      </div>
    </div>

  </div>
</div>
</body>
</html>
