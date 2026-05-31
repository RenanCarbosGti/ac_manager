<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "model/ordemservico.php";
include_once "model/servico.php";
include_once "model/profissional.php";
include_once "dao/EquipamentoDao.php";
include_once "dao/OrdemServicoDao.php";
include "topo.html";

$eDao = new EquipamentoDao();
$oDao = new OrdemServicoDao();

$tipo             = $_GET["tipo"]      ?? "cliente";
$idCliente        = (int)($_GET["idcliente"]  ?? 0);
$idEquip          = (int)($_GET["equip_id"]   ?? 0);
$busca            = trim($_GET["busca"] ?? "");
$erro             = "";
$cliente          = null;
$equipamentos     = [];   // todos os aparelhos do cliente
$equipSelecionado = null; // aparelho específico (QR/telefone/clique)
$ordens           = [];

// ── Por cliente (select suspensa) ────────────────────────────
if ($tipo === "cliente" && $idCliente > 0) {
    $equipamentos = $eDao->buscarPorIdCliente($idCliente);
    if (!empty($equipamentos)) {
        $p = $equipamentos[0];
        $cliente = ["nome" => $p["nome_cliente"], "telefone" => $p["telefone"], "endereco" => $p["endereco"]];
        if (count($equipamentos) === 1) {
            $equipSelecionado = $p;
            $ordens = $oDao->buscarPorEquipamento($p["idequipamento"]);
            $equipamentos = [];
        }
    } else {
        $erro = "Nenhum equipamento encontrado para este cliente.";
    }
}

// ── Equipamento específico clicado na lista ──────────────────
if ($idEquip > 0) {
    $equipSelecionado = $eDao->readId($idEquip);
    if ($equipSelecionado) {
        $ordens  = $oDao->buscarPorEquipamento($equipSelecionado["idequipamento"]);
        $cliente = [
            "nome"     => $equipSelecionado["nome_cliente"],
            "telefone" => $equipSelecionado["telefone"],
            "endereco" => $equipSelecionado["endereco"],
        ];
    }
}

// ── Por QR Code ──────────────────────────────────────────────
if ($tipo === "qr" && !empty($busca)) {
    $equipSelecionado = $eDao->buscarPorQR($busca);
    if ($equipSelecionado) $ordens = $oDao->buscarPorEquipamento($equipSelecionado["idequipamento"]);
    else                   $erro   = "Nenhum equipamento encontrado com este QR Code.";
}

// ── Por Telefone ─────────────────────────────────────────────
if ($tipo === "telefone" && !empty($busca)) {
    $equipSelecionado = $eDao->buscarPorTelefone($busca);
    if ($equipSelecionado) $ordens = $oDao->buscarPorEquipamento($equipSelecionado["idequipamento"]);
    else                   $erro   = "Nenhum equipamento encontrado com este telefone.";
}

// Helper: badge de vencimento
function vencBadge($dataVenc, $status) {
    if (!$dataVenc || $status !== 'ativo') return '';
    $diff = (strtotime($dataVenc) - time()) / 86400;
    if ($diff < 0)       return "<br><span class='badge bg-danger'><i class='bi bi-x-circle me-1'></i>Vencido há ".abs((int)$diff)." dias</span>";
    if ($diff <= 5)      return "<br><span class='badge bg-warning text-dark'><i class='bi bi-exclamation-triangle me-1'></i>Vence em ".(int)$diff." dias</span>";
    if ($diff <= 30)     return "<br><span class='badge bg-info text-dark'><i class='bi bi-clock me-1'></i>Vence em ".(int)$diff." dias</span>";
    return "";
}
?>

<div class="row justify-content-center">
  <div class="col-lg-8">

    <!-- Campo de busca -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-search me-2"></i>Buscar Equipamento do Cliente
      </div>
      <div class="card-body">
        <form method="get" action="buscar_equipamento.php">
          <div class="btn-group w-100 mb-3" role="group">
            <a href="buscar_equipamento.php?tipo=cliente"
               class="btn btn-<?php echo $tipo==='cliente'?'primary':'outline-primary'; ?>">
              <i class="bi bi-person me-1"></i>Por Cliente
            </a>
            <a href="buscar_equipamento.php?tipo=telefone"
               class="btn btn-<?php echo $tipo==='telefone'?'primary':'outline-primary'; ?>">
              <i class="bi bi-telephone me-1"></i>Por Telefone
            </a>
            <a href="buscar_equipamento.php?tipo=qr"
               class="btn btn-<?php echo $tipo==='qr'?'primary':'outline-primary'; ?>">
              <i class="bi bi-qr-code-scan me-1"></i>Por QR Code
            </a>
          </div>

          <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">

          <?php if ($tipo === 'cliente'): ?>
          <select class="form-select form-select-lg" name="idcliente" onchange="this.form.submit()">
            <option value="">Selecione o cliente...</option>
            <?php foreach ($eDao->buscarClientesUnicos() as $c): ?>
              <option value="<?php echo $c['idcliente'] ?: $c['idequipamento']; ?>"
                <?php echo $idCliente == ($c['idcliente'] ?: $c['idequipamento']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['nome_cliente']); ?>
                — <?php echo htmlspecialchars($c['telefone']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text"><i class="bi bi-info-circle me-1"></i>Selecione para ver todos os aparelhos e histórico.</div>

          <?php else: ?>
          <div class="input-group">
            <input type="text" class="form-control form-control-lg" name="busca"
                   value="<?php echo htmlspecialchars($busca); ?>"
                   placeholder="<?php echo $tipo==='qr' ? 'Ex: AC-A1B2C3D4-123' : '(00) 00000-0000'; ?>"
                   autofocus autocomplete="off">
            <button class="btn btn-primary px-4" type="submit"><i class="bi bi-search"></i></button>
          </div>
          <div class="form-text"><i class="bi bi-info-circle me-1"></i>
            <?php echo $tipo==='qr' ? 'Cole o código do QR colado no equipamento.' : 'Digite o telefone do cliente.'; ?>
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Erro -->
    <?php if ($erro): ?>
      <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i><?php echo $erro; ?></div>
    <?php endif; ?>

    <!-- Cabeçalho do cliente (quando tem múltiplos equipamentos) -->
    <?php if ($cliente && !empty($equipamentos)): ?>
      <?php $waTel = preg_replace('/\D/', '', $cliente["telefone"]);
            $waMsg = urlencode("Olá {$cliente['nome']}! Tudo bem? Sou da equipe AC Manager."); ?>
      <div class="card mb-3">
        <div class="card-header bg-success text-white fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="bi bi-person-check me-2"></i><?php echo htmlspecialchars($cliente["nome"]); ?></span>
          <a href="https://wa.me/55<?php echo $waTel; ?>?text=<?php echo $waMsg; ?>"
             target="_blank" class="btn btn-sm btn-light text-success fw-bold">
            <i class="bi bi-whatsapp me-1"></i>WhatsApp
          </a>
        </div>
        <div class="card-body py-2">
          <span class="text-muted small me-3"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($cliente["telefone"]); ?></span>
          <span class="text-muted small"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($cliente["endereco"]); ?></span>
        </div>
      </div>

      <!-- Lista de aparelhos do cliente -->
      <div class="row g-3 mb-3">
        <?php foreach ($equipamentos as $eq): ?>
        <div class="col-sm-6">
          <div class="card h-100 border-primary">
            <div class="card-body">
              <div class="fw-semibold mb-1">
                <i class="bi bi-wind text-primary me-1"></i>
                <?php echo htmlspecialchars(trim($eq["marca"] . " " . $eq["modelo"])) ?: "Aparelho sem identificação"; ?>
              </div>
              <div class="small text-muted mb-2">
                <code><?php echo htmlspecialchars($eq["codigo_qr"]); ?></code>
              </div>
              <div class="d-flex gap-2 flex-wrap">
                <a href="buscar_equipamento.php?equip_id=<?php echo $eq['idequipamento']; ?>&tipo=cliente&idcliente=<?php echo $idCliente; ?>"
                   class="btn btn-primary btn-sm">
                  <i class="bi bi-clipboard2-check me-1"></i>Ver Serviços
                </a>
                <a href="indexordem.php?equip=<?php echo $eq['idequipamento']; ?>"
                   class="btn btn-outline-success btn-sm">
                  <i class="bi bi-plus-circle me-1"></i>Novo Serviço
                </a>
                <a href="qrcode.php?id=<?php echo $eq['idequipamento']; ?>"
                   class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-qr-code me-1"></i>QR
                </a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- Equipamento selecionado + histórico -->
    <?php if ($equipSelecionado): ?>
      <?php $waTel = preg_replace('/\D/', '', $equipSelecionado["telefone"]);
            $waMsg = urlencode("Olá {$equipSelecionado['nome_cliente']}! Tudo bem? Sou da equipe AC Manager e gostaria de conversar sobre o seu ar condicionado."); ?>

      <!-- Botão voltar para lista se veio de cliente com múltiplos -->
      <?php if ($idCliente > 0 && $tipo === 'cliente'): ?>
        <a href="buscar_equipamento.php?tipo=cliente&idcliente=<?php echo $idCliente; ?>"
           class="btn btn-outline-secondary btn-sm mb-3">
          <i class="bi bi-arrow-left me-1"></i>Ver todos os aparelhos
        </a>
      <?php endif; ?>

      <div class="card mb-3">
        <div class="card-header bg-success text-white fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="bi bi-cpu me-2"></i>Equipamento Encontrado</span>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white text-success"><?php echo htmlspecialchars($equipSelecionado["codigo_qr"]); ?></span>
            <a href="https://wa.me/55<?php echo $waTel; ?>?text=<?php echo $waMsg; ?>"
               target="_blank" class="btn btn-sm btn-light text-success fw-bold">
              <i class="bi bi-whatsapp me-1"></i>WhatsApp
            </a>
          </div>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-sm-6">
              <div class="small text-muted">Cliente</div>
              <div class="fw-semibold"><?php echo htmlspecialchars($equipSelecionado["nome_cliente"]); ?></div>
            </div>
            <div class="col-sm-6">
              <div class="small text-muted">Telefone</div>
              <div><?php echo htmlspecialchars($equipSelecionado["telefone"]); ?></div>
            </div>
            <div class="col-sm-6">
              <div class="small text-muted">Endereço</div>
              <div class="small"><?php echo htmlspecialchars($equipSelecionado["endereco"]); ?></div>
            </div>
            <div class="col-sm-6">
              <div class="small text-muted">Aparelho</div>
              <div class="small"><?php echo htmlspecialchars(trim($equipSelecionado["marca"] . " " . $equipSelecionado["modelo"])); ?></div>
            </div>
          </div>
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="indexordem.php?equip=<?php echo $equipSelecionado['idequipamento']; ?>"
               class="btn btn-primary btn-sm">
              <i class="bi bi-clipboard2-plus me-1"></i>Adicionar Novo Serviço
            </a>
            <a href="qrcode.php?id=<?php echo $equipSelecionado['idequipamento']; ?>"
               class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-qr-code me-1"></i>Imprimir QR Code
            </a>
            <a href="indexequipamento.php?id=<?php echo $equipSelecionado['idequipamento']; ?>"
               class="btn btn-outline-primary btn-sm">
              <i class="bi bi-pencil me-1"></i>Editar Equipamento
            </a>
          </div>
        </div>
      </div>

      <!-- Histórico de serviços -->
      <div class="card">
        <div class="card-header bg-white text-dark border-bottom fw-semibold">
          <i class="bi bi-clipboard2-check text-primary me-2"></i>
          Serviços Realizados (<?php echo count($ordens); ?>)
        </div>
        <div class="card-body <?php echo empty($ordens) ? '' : 'p-0'; ?>">
          <?php if (empty($ordens)): ?>
            <p class="text-muted text-center small mb-0">Nenhum serviço registrado para este equipamento ainda.</p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr><th>Serviço</th><th>Data</th><th>Vencimento</th><th>Profissional</th><th>Status</th></tr>
                </thead>
                <tbody>
                  <?php foreach ($ordens as $o):
                    $dv          = $o["data_vencimento"] ?? null;
                    $diff        = $dv ? (strtotime($dv) - time()) / 86400 : null;
                    $trClass     = $diff !== null && $o["status"]==="ativo" ? ($diff < 0 ? "table-danger" : ($diff <= 5 ? "table-warning" : "")) : "";
                    $statusBadge = match($o["status"]) { 'ativo'=>'bg-success','concluido'=>'bg-secondary','cancelado'=>'bg-danger',default=>'bg-secondary' };
                  ?>
                  <tr class="<?php echo $trClass; ?>">
                    <td class="fw-semibold"><?php echo htmlspecialchars($o["nome_servico"]); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($o["data_servico"])); ?></td>
                    <td>
                      <?php if ($dv): ?>
                        <?php echo date('d/m/Y', strtotime($dv)); ?>
                        <?php echo vencBadge($dv, $o["status"]); ?>
                      <?php else: ?>
                        <span class="text-muted small">Sem retorno</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($o["nome_profissional"]); ?></td>
                    <td><span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($o["status"]); ?></span></td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="card-footer bg-white text-end">
              <a href="indexordem.php?equip=<?php echo $equipSelecionado['idequipamento']; ?>"
                 class="btn btn-primary btn-sm">
                <i class="bi bi-clipboard2-plus me-1"></i>Adicionar Novo Serviço
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</div>

<?php include "rodape.html"; ?>
