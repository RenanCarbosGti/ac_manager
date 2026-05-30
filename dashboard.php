<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "model/ordemservico.php";
include_once "model/profissional.php";
include_once "model/servico.php";
include_once "dao/EquipamentoDao.php";
include_once "dao/OrdemServicoDao.php";
include_once "dao/ProfissionalDao.php";
include_once "dao/ServicoDao.php";

$oDao     = new OrdemServicoDao();
$eDao     = new EquipamentoDao();
$pDao     = new ProfissionalDao();
$sDao     = new ServicoDao();

$alertas  = $oDao->buscarAlertas(5);
$vencidas = $oDao->buscarVencidas();
$equips   = $eDao->read();
$ordens   = $oDao->read();

$totalEquip  = $equips  ? count($equips)  : 0;
$totalOrdens = $ordens  ? count($ordens)  : 0;
$totalProf   = $pDao->read() ? count($pDao->read()) : 0;
$totalServs  = $sDao->read() ? count($sDao->read()) : 0;
$totalAlerta = ($alertas ? count($alertas) : 0) + ($vencidas ? count($vencidas) : 0);

include "topo.html";
?>

<!-- Cards de resumo -->
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="bg-primary bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-cpu fs-3 text-primary"></i>
      </div>
      <div>
        <div class="fs-4 fw-bold"><?php echo $totalEquip; ?></div>
        <div class="text-muted small">Equipamentos</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="bg-success bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-clipboard2-check fs-3 text-success"></i>
      </div>
      <div>
        <div class="fs-4 fw-bold"><?php echo $totalOrdens; ?></div>
        <div class="text-muted small">Ordens de Serviço</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="bg-info bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-person-badge fs-3 text-info"></i>
      </div>
      <div>
        <div class="fs-4 fw-bold"><?php echo $totalProf; ?></div>
        <div class="text-muted small">Profissionais</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="bg-<?php echo $totalAlerta > 0 ? 'warning' : 'secondary'; ?> bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-bell fs-3 text-<?php echo $totalAlerta > 0 ? 'warning' : 'secondary'; ?>"></i>
      </div>
      <div>
        <div class="fs-4 fw-bold"><?php echo $totalAlerta; ?></div>
        <div class="text-muted small">Alertas Pendentes</div>
      </div>
    </div>
  </div>
</div>

<!-- Alertas de vencimento -->
<?php if (!empty($vencidas)): ?>
<div class="card mb-4">
  <div class="card-header bg-danger text-white fw-semibold">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>
    Serviços Vencidos (<?php echo count($vencidas); ?>)
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Cliente</th><th>Telefone</th><th>Serviço</th>
        <th>Vencimento</th><th>Atraso</th><th>Profissional</th>
      </tr></thead>
      <tbody>
        <?php foreach ($vencidas as $v): ?>
        <tr class="table-danger">
          <td><?php echo htmlspecialchars($v["nome_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($v["tel_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($v["nome_servico"]); ?></td>
          <td><?php echo date('d/m/Y', strtotime($v["data_vencimento"])); ?></td>
          <td><span class="badge bg-danger"><?php echo $v["dias_atraso"]; ?> dias</span></td>
          <td><?php echo htmlspecialchars($v["nome_profissional"]); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($alertas)): ?>
<div class="card mb-4">
  <div class="card-header bg-warning text-dark fw-semibold">
    <i class="bi bi-bell-fill me-2"></i>
    Serviços a Vencer em até 5 Dias (<?php echo count($alertas); ?>)
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Cliente</th><th>Telefone</th><th>Serviço</th>
        <th>Vencimento</th><th>Restam</th><th>Profissional</th>
      </tr></thead>
      <tbody>
        <?php foreach ($alertas as $a): ?>
        <tr class="table-warning">
          <td><?php echo htmlspecialchars($a["nome_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($a["tel_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($a["nome_servico"]); ?></td>
          <td><?php echo date('d/m/Y', strtotime($a["data_vencimento"])); ?></td>
          <td><span class="badge bg-warning text-dark"><?php echo $a["dias_restantes"]; ?> dias</span></td>
          <td><?php echo htmlspecialchars($a["nome_profissional"]); ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (empty($vencidas) && empty($alertas)): ?>
<div class="alert alert-success">
  <i class="bi bi-check-circle-fill me-2"></i>
  Nenhum alerta de vencimento nos próximos 5 dias. Tudo em dia!
</div>
<?php endif; ?>

<!-- Últimas ordens de serviço -->
<div class="card">
  <div class="card-header bg-primary text-white fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="bi bi-list-check me-2"></i>Últimas Ordens de Serviço</span>
    <a href="indexordem.php" class="btn btn-sm btn-light">Ver todas</a>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Cliente</th><th>Serviço</th><th>Data</th><th>Vencimento</th><th>Profissional</th><th>Status</th>
      </tr></thead>
      <tbody>
        <?php
        $ultimas = array_slice($ordens ?: [], 0, 10);
        foreach ($ultimas as $o):
            $statusBadge = match($o["status"]) {
                'ativo'     => 'bg-success',
                'concluido' => 'bg-secondary',
                'cancelado' => 'bg-danger',
                default     => 'bg-secondary'
            };
        ?>
        <tr>
          <td><?php echo htmlspecialchars($o["nome_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($o["nome_servico"]); ?></td>
          <td><?php echo date('d/m/Y', strtotime($o["data_servico"])); ?></td>
          <td><?php echo $o["data_vencimento"] ? date('d/m/Y', strtotime($o["data_vencimento"])) : '<span class="text-muted">—</span>'; ?></td>
          <td><?php echo htmlspecialchars($o["nome_profissional"]); ?></td>
          <td><span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($o["status"]); ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ultimas)): ?>
          <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma ordem cadastrada ainda.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include "rodape.html"; ?>