<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "model/ordemservico.php";
include_once "model/profissional.php";
include_once "model/servico.php";
include_once "model/financeiro.php";
include_once "model/material.php";
include_once "dao/EquipamentoDao.php";
include_once "dao/OrdemServicoDao.php";
include_once "dao/ProfissionalDao.php";
include_once "dao/ServicoDao.php";
include_once "dao/FinanceiroDao.php";
include_once "dao/MaterialDao.php";

$oDao = new OrdemServicoDao();
$eDao = new EquipamentoDao();
$pDao = new ProfissionalDao();
$fDao = new FinanceiroDao();
$mDao = new MaterialDao();

$alertas  = $oDao->buscarAlertas(5)  ?: [];
$vencidas = $oDao->buscarVencidas()  ?: [];
$equips   = $eDao->read()            ?: [];
$ordens   = $oDao->read()            ?: [];
$profs    = $pDao->read()            ?: [];

$totalEquip  = count($equips);
$totalOrdens = count($ordens);
$totalProf   = count($profs);
$totalAlerta = count($alertas) + count($vencidas);

$resumoFin    = $fDao->resumoMes();
$recServicos  = $fDao->receitaServicosTotal();
$recMateriais = $fDao->receitaMateriais();
$recTotal     = $recServicos + $recMateriais;
$saidas       = $resumoFin["total_saidas"] ?? 0;
$saldoMes     = $recTotal - $saidas;
$estqAlerta   = $mDao->buscarAbaixoMinimo() ?: [];

include "topo.html";
?>

<!-- ── Cards dinâmicos ── -->
<div class="cards-grid mb-4">

  <?php
  function dashCard(string $icon, string $cor, string $valor, string $label, ?string $link = null): void {
      $open  = $link ? "<a href=\"$link\" class=\"text-decoration-none\">" : "<div>";
      $close = $link ? "</a>" : "</div>";
      echo "$open
        <div class='dash-card'>
          <div class='dash-icon bg-{$cor} bg-opacity-10'>
            <i class='bi bi-{$icon} text-{$cor}'></i>
          </div>
          <div class='dash-info'>
            <div class='dash-valor text-{$cor}'>$valor</div>
            <div class='dash-label'>$label</div>
          </div>
        </div>
      $close";
  }
  ?>

  <?php dashCard('cpu', 'primary', (string)$totalEquip, 'Equipamentos'); ?>
  <?php dashCard('clipboard2-check', 'success', (string)$totalOrdens, 'Ordens de Serviço'); ?>
  <?php dashCard('person-badge', 'info', (string)$totalProf, 'Profissionais'); ?>
  <?php dashCard('bell', $totalAlerta > 0 ? 'warning':'secondary', (string)$totalAlerta, 'Alertas'); ?>
  <?php dashCard('tools',          'success',  'R$ '.number_format($recServicos, 2,',','.'),   'Receita Serviços',   'indexfinanceiro.php'); ?>
  <?php dashCard('box-seam',       'info',     'R$ '.number_format($recMateriais,2,',','.'),   'Receita Materiais',  'indexfinanceiro.php'); ?>
  <?php dashCard('graph-up-arrow', 'primary',  'R$ '.number_format($recTotal,   2,',','.'),    'Receita Total',      'indexfinanceiro.php'); ?>
  <?php dashCard('wallet2',        $saldoMes>=0?'success':'danger',
                                              'R$ '.number_format($saldoMes,   2,',','.'),    'Saldo do Mês',       'indexfinanceiro.php'); ?>
  <?php dashCard('boxes',          count($estqAlerta)>0?'warning':'info',       (string)count($estqAlerta), 'Estoque Baixo', 'indexmaterial.php'); ?>

</div>

<style>
.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 16px;
}
.dash-card {
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 2px 12px rgba(0,0,0,.08);
  padding: 16px;
  display: flex;
  align-items: center;
  gap: 14px;
  height: 100%;
  transition: box-shadow .15s;
}
a:hover .dash-card { box-shadow: 0 4px 18px rgba(0,0,0,.14); }
.dash-icon {
  width: 52px; height: 52px;
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: 1.5rem;
}
.dash-valor { font-size: 1.25rem; font-weight: 700; line-height: 1.2; }
.dash-label { font-size: .78rem; color: #6c757d; margin-top: 2px; }
</style>

<!-- Serviços vencidos -->
<?php if (!empty($vencidas)): ?>
<div class="card mb-4">
  <div class="card-header bg-danger text-white fw-semibold">
    <i class="bi bi-exclamation-octagon-fill me-2"></i>Serviços Vencidos (<?php echo count($vencidas); ?>)
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Cliente</th><th>Telefone</th><th>Serviço</th>
        <th>Vencimento</th><th>Atraso</th><th>Profissional</th><th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($vencidas as $v):
          $waTel = preg_replace('/\D/', '', $v["tel_cliente"]);
          $waMsg = urlencode("Olá {$v['nome_cliente']}! Gostaríamos de agendar a manutenção do seu ar condicionado ({$v['nome_servico']}). Quando seria um bom momento?");
        ?>
        <tr class="table-danger">
          <td><?php echo htmlspecialchars($v["nome_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($v["tel_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($v["nome_servico"]); ?></td>
          <td><?php echo date('d/m/Y', strtotime($v["data_vencimento"])); ?></td>
          <td><span class="badge bg-danger"><?php echo $v["dias_atraso"]; ?> dias</span></td>
          <td><?php echo htmlspecialchars($v["nome_profissional"]); ?></td>
          <td>
            <a href="https://wa.me/55<?php echo $waTel; ?>?text=<?php echo $waMsg; ?>"
               target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
              <i class="bi bi-whatsapp"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Serviços a vencer -->
<?php if (!empty($alertas)): ?>
<div class="card mb-4">
  <div class="card-header bg-warning text-dark fw-semibold">
    <i class="bi bi-bell-fill me-2"></i>Serviços a Vencer em até 5 Dias (<?php echo count($alertas); ?>)
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Cliente</th><th>Telefone</th><th>Serviço</th>
        <th>Vencimento</th><th>Restam</th><th>Profissional</th><th></th>
      </tr></thead>
      <tbody>
        <?php foreach ($alertas as $a):
          $waTel = preg_replace('/\D/', '', $a["tel_cliente"]);
          $waMsg = urlencode("Olá {$a['nome_cliente']}! O prazo do serviço {$a['nome_servico']} do seu ar condicionado está se aproximando. Gostaria de agendar a manutenção?");
        ?>
        <tr class="table-warning">
          <td><?php echo htmlspecialchars($a["nome_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($a["tel_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($a["nome_servico"]); ?></td>
          <td><?php echo date('d/m/Y', strtotime($a["data_vencimento"])); ?></td>
          <td><span class="badge bg-warning text-dark"><?php echo $a["dias_restantes"]; ?> dias</span></td>
          <td><?php echo htmlspecialchars($a["nome_profissional"]); ?></td>
          <td>
            <a href="https://wa.me/55<?php echo $waTel; ?>?text=<?php echo $waMsg; ?>"
               target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
              <i class="bi bi-whatsapp"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (empty($vencidas) && empty($alertas)): ?>
<div class="alert alert-success">
  <i class="bi bi-check-circle-fill me-2"></i>Nenhum alerta de vencimento nos próximos 5 dias. Tudo em dia!
</div>
<?php endif; ?>

<!-- Últimas ordens -->
<div class="card">
  <div class="card-header bg-primary text-white fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="bi bi-list-check me-2"></i>Últimas Ordens de Serviço</span>
    <a href="indexordem.php" class="btn btn-sm btn-light">Ver todas</a>
  </div>
  <div class="card-body p-0">
    <table class="table table-hover mb-0">
      <thead><tr>
        <th>Cliente</th><th>Serviço</th><th>Data</th><th>Vencimento</th><th>Profissional</th><th>Status</th><th></th>
      </tr></thead>
      <tbody>
        <?php
        $ultimas = array_slice($ordens, 0, 10);
        foreach ($ultimas as $o):
          $statusBadge = match($o["status"]) {
            'ativo'     => 'bg-success',
            'concluido' => 'bg-secondary',
            'cancelado' => 'bg-danger',
            default     => 'bg-secondary'
          };
          $waTel = preg_replace('/\D/', '', $o["tel_cliente"]);
          $waMsg = urlencode("Olá {$o['nome_cliente']}! Tudo bem? Sou da equipe AC Manager e gostaria de falar sobre o serviço de {$o['nome_servico']}.");
        ?>
        <tr>
          <td><?php echo htmlspecialchars($o["nome_cliente"]); ?></td>
          <td><?php echo htmlspecialchars($o["nome_servico"]); ?></td>
          <td><?php echo date('d/m/Y', strtotime($o["data_servico"])); ?></td>
          <td><?php echo $o["data_vencimento"] ? date('d/m/Y', strtotime($o["data_vencimento"])) : '<span class="text-muted">—</span>'; ?></td>
          <td><?php echo htmlspecialchars($o["nome_profissional"]); ?></td>
          <td><span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($o["status"]); ?></span></td>
          <td>
            <a href="https://wa.me/55<?php echo $waTel; ?>?text=<?php echo $waMsg; ?>"
               target="_blank" class="btn btn-sm btn-success" title="WhatsApp">
              <i class="bi bi-whatsapp"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($ultimas)): ?>
          <tr><td colspan="7" class="text-center text-muted py-3">Nenhuma ordem cadastrada ainda.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include "rodape.html"; ?>