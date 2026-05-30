<?php
// equipamento_publico.php — página pública acessada pelo cliente ao escanear o QR Code

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "model/ordemservico.php";
include_once "model/servico.php";
include_once "model/profissional.php";
include_once "dao/EquipamentoDao.php";
include_once "dao/OrdemServicoDao.php";

$codigo = trim($_GET["qr"] ?? "");
if (empty($codigo)) { http_response_code(404); die("QR Code inválido."); }

$eDao  = new EquipamentoDao();
$oDao  = new OrdemServicoDao();

$equip  = $eDao->buscarPorQR($codigo);
$ordens = $equip ? $oDao->buscarPorEquipamento($equip["idequipamento"]) : [];

// Verifica se há serviço vencido ou próximo do vencimento
$temPendente = false;
if ($ordens) {
    foreach ($ordens as $o) {
        if ($o["data_vencimento"] && $o["status"] === "ativo") {
            $diff = (strtotime($o["data_vencimento"]) - time()) / 86400;
            if ($diff <= 30) { $temPendente = true; break; }
        }
    }
}

// ── Dados da empresa ── edite aqui ──────────────────────────
$empresa = [
    "nome"      => "AC Refrigeração",
    "telefone"  => "(32) 99999-0000",
    "whatsapp"  => "5532999990000",  // só números, com DDI
];
// ────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($empresa["nome"]); ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <style>
    body { background: #f0f6ff; font-family: 'Segoe UI', sans-serif; }

    .hero {
      background: linear-gradient(135deg, #0d6efd, #0a58ca);
      color: #fff;
      padding: 24px 20px 18px;
      text-align: center;
    }
    .hero h1 { font-size: 1.3rem; font-weight: 700; margin: 0 0 12px; }

    .contact-bar {
      background: #fff;
      border-bottom: 1px solid #dee2e6;
      padding: 10px 16px;
      display: flex;
      gap: 10px;
      justify-content: center;
    }
    .contact-bar a {
      font-size: .85rem; text-decoration: none;
      display: flex; align-items: center; gap: 6px;
      padding: 6px 16px; border-radius: 20px; font-weight: 600;
    }
    .btn-whats { background: #25d366; color: #fff; }
    .btn-tel   { background: #0d6efd;  color: #fff; }

    .card { border: none; border-radius: 14px; box-shadow: 0 2px 12px rgba(0,0,0,.07); margin-bottom: 16px; }
    .card-header { border-radius: 14px 14px 0 0 !important; font-weight: 600; }

    /* Aviso de pendência */
    .alerta-pendente {
      background: #fff3cd; border-left: 5px solid #ffc107;
      border-radius: 10px; padding: 14px 16px;
      display: flex; align-items: flex-start; gap: 10px;
    }
    .alerta-pendente.vencido {
      background: #f8d7da; border-left-color: #dc3545;
    }
    .alerta-pendente .icone { font-size: 1.5rem; line-height: 1; }
    .alerta-pendente .texto-titulo { font-weight: 700; font-size: .92rem; }
    .alerta-pendente .texto-sub    { font-size: .8rem; margin-top: 2px; }

    /* Timeline */
    .timeline { position: relative; padding-left: 28px; }
    .timeline::before {
      content: ''; position: absolute;
      left: 10px; top: 0; bottom: 0;
      width: 2px; background: #dee2e6;
    }
    .tl-item { position: relative; margin-bottom: 20px; }
    .tl-dot {
      position: absolute; left: -22px; top: 4px;
      width: 14px; height: 14px; border-radius: 50%;
      background: #0d6efd; border: 2px solid #fff;
      box-shadow: 0 0 0 2px #0d6efd;
    }
    .tl-dot.vencido   { background:#dc3545; box-shadow:0 0 0 2px #dc3545; }
    .tl-dot.vence     { background:#ffc107; box-shadow:0 0 0 2px #ffc107; }
    .tl-dot.concluido { background:#6c757d; box-shadow:0 0 0 2px #6c757d; }
    .tl-titulo  { font-size: .92rem; font-weight: 700; color: #111; }
    .tl-meta    { font-size: .78rem; color: #6c757d; margin-top: 2px; }
    .tl-prof    { font-size: .80rem; color: #0d6efd; margin-top: 2px; }
    .tl-venc    { font-size: .78rem; margin-top: 4px; }
  </style>
</head>
<body>

<div class="hero">
  <div style="font-size:2.2rem;">❄️</div>
  <h1><?php echo htmlspecialchars($empresa["nome"]); ?></h1>
</div>

<div class="contact-bar">
  <a href="https://wa.me/<?php echo $empresa['whatsapp']; ?>?text=Olá!%20Escaneei%20o%20QR%20Code%20do%20meu%20ar%20condicionado."
     class="btn-whats" target="_blank">
    <i class="bi bi-whatsapp"></i> WhatsApp
  </a>
  <a href="tel:<?php echo preg_replace('/\D/', '', $empresa['telefone']); ?>" class="btn-tel">
    <i class="bi bi-telephone-fill"></i> Ligar
  </a>
</div>

<div class="container py-3" style="max-width:500px;">

<?php if (!$equip): ?>
  <div class="card p-4 text-center">
    <i class="bi bi-exclamation-triangle text-warning" style="font-size:2.2rem;"></i>
    <h6 class="mt-2 fw-bold">Equipamento não encontrado</h6>
    <p class="text-muted small">Entre em contato conosco para mais informações.</p>
    <a href="https://wa.me/<?php echo $empresa['whatsapp']; ?>"
       class="btn btn-success mt-1" target="_blank">
      <i class="bi bi-whatsapp me-1"></i> Falar pelo WhatsApp
    </a>
  </div>

<?php else: ?>

  <!-- Aviso de pendência no topo — bem visível -->
  <?php
  // Calcula o pior status entre todas as ordens ativas com vencimento
  $piorDiff   = null;
  $piorServico = "";
  foreach ($ordens as $o) {
      if ($o["data_vencimento"] && $o["status"] === "ativo") {
          $diff = (strtotime($o["data_vencimento"]) - time()) / 86400;
          if ($piorDiff === null || $diff < $piorDiff) {
              $piorDiff    = $diff;
              $piorServico = $o["nome_servico"];
          }
      }
  }

  if ($piorDiff !== null && $piorDiff <= 30):
      $eVencido = $piorDiff < 0;
  ?>
  <div class="alerta-pendente <?php echo $eVencido ? 'vencido' : ''; ?> mb-3">
    <div class="icone"><?php echo $eVencido ? '🔴' : '🟡'; ?></div>
    <div>
      <div class="texto-titulo">
        <?php if ($eVencido): ?>
          Serviço vencido — recomendamos agendar a manutenção!
        <?php else: ?>
          Serviço próximo do vencimento!
        <?php endif; ?>
      </div>
      <div class="texto-sub">
        <strong><?php echo htmlspecialchars($piorServico); ?></strong>
        <?php if ($eVencido): ?>
          venceu há <strong><?php echo abs((int)$piorDiff); ?> dias</strong>.
        <?php else: ?>
          vence em <strong><?php echo (int)$piorDiff; ?> dias</strong>.
        <?php endif; ?>
        Entre em contato para agendar.
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Dados do equipamento -->
  <div class="card">
    <div class="card-header bg-primary text-white">
      <i class="bi bi-cpu me-2"></i>Seu Equipamento
    </div>
    <div class="card-body">
      <div class="fw-semibold"><?php echo htmlspecialchars($equip["nome_cliente"]); ?></div>
      <?php if ($equip["marca"] || $equip["modelo"]): ?>
        <div class="text-muted small mt-1">
          <i class="bi bi-wind me-1"></i>
          <?php echo htmlspecialchars(trim($equip["marca"] . " " . $equip["modelo"])); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Histórico de serviços -->
  <div class="card">
    <div class="card-header bg-white text-dark border-bottom">
      <i class="bi bi-clipboard2-check text-primary me-2"></i>Histórico de Serviços
    </div>
    <div class="card-body">
      <?php if (empty($ordens)): ?>
        <p class="text-muted text-center small mb-0">Nenhum serviço registrado ainda.</p>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($ordens as $o):
            $dataServ = date('d/m/Y', strtotime($o["data_servico"]));
            $dataVenc = $o["data_vencimento"] ? date('d/m/Y', strtotime($o["data_vencimento"])) : null;
            $dotClass  = "";
            $vencInfo  = "";

            if ($dataVenc && $o["status"] === "ativo") {
                $diff = (strtotime($o["data_vencimento"]) - time()) / 86400;
                if ($diff < 0) {
                    $dotClass = "vencido";
                    $vencInfo = "<div class='tl-venc'>
                                   <span class='badge bg-danger'>
                                     <i class='bi bi-x-circle me-1'></i>
                                     Vencido há " . abs((int)$diff) . " dias — agende a manutenção
                                   </span>
                                 </div>";
                } elseif ($diff <= 5) {
                    $dotClass = "vence";
                    $vencInfo = "<div class='tl-venc'>
                                   <span class='badge bg-warning text-dark'>
                                     <i class='bi bi-exclamation-triangle me-1'></i>
                                     Vence em " . (int)$diff . " dias
                                   </span>
                                 </div>";
                } elseif ($diff <= 30) {
                    $dotClass = "vence";
                    $vencInfo = "<div class='tl-venc'>
                                   <span class='badge bg-info text-dark'>
                                     <i class='bi bi-clock me-1'></i>
                                     Próximo serviço em " . (int)$diff . " dias
                                   </span>
                                 </div>";
                } else {
                    $vencInfo = "<div class='tl-venc text-muted'>
                                   <i class='bi bi-arrow-repeat me-1'></i>
                                   Próximo: <strong>{$dataVenc}</strong>
                                 </div>";
                }
            } elseif ($o["status"] === "concluido") {
                $dotClass = "concluido";
            } elseif ($o["status"] === "cancelado") {
                $dotClass = "concluido";
            }
          ?>
          <div class="tl-item">
            <div class="tl-dot <?php echo $dotClass; ?>"></div>
            <div class="tl-titulo"><?php echo htmlspecialchars($o["nome_servico"]); ?></div>
            <div class="tl-meta">
              <i class="bi bi-calendar3 me-1"></i><?php echo $dataServ; ?>
              &nbsp;·&nbsp;
              <span class="badge bg-<?php echo match($o['status']) {
                'ativo'     => 'success',
                'concluido' => 'secondary',
                'cancelado' => 'danger',
                default     => 'secondary'
              }; ?> bg-opacity-75">
                <?php echo ucfirst($o["status"]); ?>
              </span>
            </div>
            <div class="tl-prof">
              <i class="bi bi-person-check me-1"></i><?php echo htmlspecialchars($o["nome_profissional"]); ?>
            </div>
            <?php echo $vencInfo; ?>
          </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- CTA WhatsApp -->
  <div class="card text-center p-3">
    <p class="text-muted small mb-2">Precisa de manutenção? Fale conosco!</p>
    <a href="https://wa.me/<?php echo $empresa['whatsapp']; ?>?text=Olá!%20Gostaria%20de%20agendar%20um%20serviço%20para%20o%20equipamento%20<?php echo urlencode($equip['codigo_qr']); ?>."
       class="btn btn-success w-100" target="_blank">
      <i class="bi bi-whatsapp me-2"></i>Agendar pelo WhatsApp
    </a>
  </div>

<?php endif; ?>
</div>

<div class="text-center py-3 text-muted" style="font-size:.75rem;">
  <?php echo htmlspecialchars($empresa["nome"]); ?> · <?php echo htmlspecialchars($empresa["telefone"]); ?>
</div>
</body>
</html>