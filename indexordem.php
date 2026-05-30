<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/ordemservico.php";
include_once "model/equipamento.php";
include_once "model/servico.php";
include_once "model/profissional.php";
include_once "dao/OrdemServicoDao.php";
include_once "dao/EquipamentoDao.php";
include_once "dao/ServicoDao.php";
include_once "dao/ProfissionalDao.php";
include "topo.html";

$oDao = new OrdemServicoDao();
$eDao = new EquipamentoDao();
$sDao = new ServicoDao();
$pDao = new ProfissionalDao();

// Feedback
if (isset($_SESSION["resultado"])) {
    $cls = $_SESSION["resultado"] ? "alert-success" : "alert-danger";
    echo "<div class='alert $cls alert-dismissible fade show'>
            <i class='bi bi-check-circle me-1'></i> {$_SESSION['mensagem']}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
    $_SESSION["resultado"] = null;
    $_SESSION["mensagem"]  = null;
}

$equipamentos   = $eDao->read()  ?: [];
$servicos       = $sDao->read()  ?: [];
$profissionais  = $pDao->read()  ?: [];

// Editar ou pré-preencher por equipamento
$preEquip = $_GET["equip"] ?? "";
if (isset($_GET["id"])) {
    $result = $oDao->readId($_GET["id"]);
} else {
    $result = [
        "idordem"=>"","idequipamento"=>$preEquip,"idservico"=>"",
        "idprofissional"=>"","data_servico"=>date('Y-m-d'),
        "data_vencimento"=>"","preco_cobrado"=>"","observacoes"=>"","status"=>"ativo"
    ];
}

// Filtros de busca
$fCliente      = $_GET["fcliente"]      ?? "";
$fServico      = $_GET["fservico"]      ?? "";
$fProfissional = $_GET["fprofissional"] ?? "";
$fStatus       = $_GET["fstatus"]       ?? "";

if ($fCliente || $fServico || $fProfissional || $fStatus) {
    $lista = $oDao->buscarComFiltro($fCliente, $fServico, $fProfissional, $fStatus);
} elseif ($preEquip) {
    $lista = $oDao->buscarPorEquipamento($preEquip);
} else {
    $lista = $oDao->read();
}
?>

<div class="row">
  <!-- Formulário -->
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-clipboard2-plus me-2"></i>
        <?php echo empty($result["idordem"]) ? "Nova Ordem de Serviço" : "Editar Ordem #" . $result["idordem"]; ?>
      </div>
      <div class="card-body">
        <form method="post" action="controller/OrdemServicoController.php">
          <input type="hidden" name="txtIdOrdem" value="<?php echo $result["idordem"]; ?>">

          <div class="mb-2">
            <label class="form-label">Equipamento / Cliente <span class="text-danger">*</span></label>
            <select class="form-select" name="cbIdEquipamento" required>
              <option value="">Selecione...</option>
              <?php foreach ($equipamentos as $e): ?>
                <option value="<?php echo $e->idequipamento; ?>"
                  <?php echo $result["idequipamento"] == $e->idequipamento ? "selected" : ""; ?>>
                  <?php echo htmlspecialchars($e->nome_cliente . " — " . $e->codigo_qr); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Serviço <span class="text-danger">*</span></label>
            <select class="form-select" name="cbIdServico" id="cbIdServico" required
                    onchange="preencherPreco(this)">
              <option value="">Selecione...</option>
              <?php foreach ($servicos as $s): ?>
                <option value="<?php echo $s->idservico; ?>"
                        data-preco="<?php echo $s->preco; ?>"
                        data-validade="<?php echo $s->validade_dias; ?>"
                  <?php echo $result["idservico"] == $s->idservico ? "selected" : ""; ?>>
                  <?php echo htmlspecialchars($s->nome);
                  echo $s->validade_dias ? " ({$s->validade_dias} dias)" : " (sem recorrência)"; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Profissional <span class="text-danger">*</span></label>
            <select class="form-select" name="cbIdProfissional" required>
              <option value="">Selecione...</option>
              <?php foreach ($profissionais as $p): ?>
                <option value="<?php echo $p->idprofissional; ?>"
                  <?php echo $result["idprofissional"] == $p->idprofissional ? "selected" : ""; ?>>
                  <?php echo htmlspecialchars($p->nome); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Data do Serviço <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="txtDataServico"
                   value="<?php echo $result["data_servico"]; ?>" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Preço Cobrado (R$) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="txtPreco" id="txtPreco"
                   step="0.01" min="0" placeholder="0,00"
                   value="<?php echo $result["preco_cobrado"]; ?>" required>
          </div>

          <div class="mb-2">
            <label class="form-label">Status</label>
            <select class="form-select" name="cbStatus">
              <option value="ativo"     <?php echo $result["status"]=="ativo"     ? "selected":""; ?>>Ativo</option>
              <option value="concluido" <?php echo $result["status"]=="concluido" ? "selected":""; ?>>Concluído</option>
              <option value="cancelado" <?php echo $result["status"]=="cancelado" ? "selected":""; ?>>Cancelado</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Observações</label>
            <textarea class="form-control" name="txtObservacoes" rows="2"
                      placeholder="Informações adicionais..."><?php echo htmlspecialchars($result["observacoes"]); ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" name="btGravar" class="btn btn-primary flex-fill">
              <i class="bi bi-floppy me-1"></i> Gravar
            </button>
            <a href="indexordem.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg"></i>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Tabela -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-list-check me-2"></i>Ordens de Serviço
        <?php if ($preEquip): ?>
          <span class="badge bg-warning text-dark ms-2">Filtrado por equipamento</span>
          <a href="indexordem.php" class="btn btn-sm btn-light ms-2">Ver todas</a>
        <?php endif; ?>
      </div>
      <div class="card-body pb-0">
        <!-- Filtros -->
        <form method="get" action="indexordem.php" class="row g-2 mb-3">
          <div class="col-sm-3">
            <input type="text" class="form-control form-control-sm" name="fcliente"
                   placeholder="Cliente..." value="<?php echo htmlspecialchars($fCliente); ?>">
          </div>
          <div class="col-sm-3">
            <input type="text" class="form-control form-control-sm" name="fservico"
                   placeholder="Serviço..." value="<?php echo htmlspecialchars($fServico); ?>">
          </div>
          <div class="col-sm-3">
            <input type="text" class="form-control form-control-sm" name="fprofissional"
                   placeholder="Profissional..." value="<?php echo htmlspecialchars($fProfissional); ?>">
          </div>
          <div class="col-sm-2">
            <select class="form-select form-select-sm" name="fstatus">
              <option value="">Todos</option>
              <option value="ativo"     <?php echo $fStatus=="ativo"     ? "selected":""; ?>>Ativo</option>
              <option value="concluido" <?php echo $fStatus=="concluido" ? "selected":""; ?>>Concluído</option>
              <option value="cancelado" <?php echo $fStatus=="cancelado" ? "selected":""; ?>>Cancelado</option>
            </select>
          </div>
          <div class="col-sm-1">
            <button type="submit" class="btn btn-outline-primary btn-sm w-100">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th><th>Cliente</th><th>Serviço</th>
              <th>Data</th><th>Vencimento</th><th>Profissional</th>
              <th>Valor</th><th>Status</th><th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (is_null($lista)): ?>
              <tr><td colspan="9" class="text-center text-danger py-3">Erro ao buscar dados.</td></tr>
            <?php elseif (empty($lista)): ?>
              <tr><td colspan="9" class="text-center text-muted py-3">Nenhuma ordem encontrada.</td></tr>
            <?php else:
              foreach ($lista as $o):
                $statusBadge = match($o["status"]) {
                    'ativo'     => 'bg-success',
                    'concluido' => 'bg-secondary',
                    'cancelado' => 'bg-danger',
                    default     => 'bg-secondary'
                };
                // Alerta de vencimento
                $trClass    = "";
                $vencBadge  = "";
                if ($o["data_vencimento"] && $o["status"] === "ativo") {
                    $diff = (strtotime($o["data_vencimento"]) - time()) / 86400;
                    if ($diff < 0) {
                        $trClass   = "table-danger";
                        $vencBadge = "<br><span class='badge bg-danger mt-1'>
                                        <i class='bi bi-x-circle me-1'></i>Vencido há " . abs((int)$diff) . " dias
                                      </span>";
                    } elseif ($diff <= 5) {
                        $trClass   = "table-warning";
                        $vencBadge = "<br><span class='badge bg-warning text-dark mt-1'>
                                        <i class='bi bi-exclamation-triangle me-1'></i>Vence em " . (int)$diff . " dias
                                      </span>";
                    } elseif ($diff <= 30) {
                        $vencBadge = "<br><span class='badge bg-info text-dark mt-1'>
                                        <i class='bi bi-clock me-1'></i>Vence em " . (int)$diff . " dias
                                      </span>";
                    }
                }
            ?>
            <tr class="<?php echo $trClass; ?>">
              <td><?php echo $o["idordem"]; ?></td>
              <td>
                <div><?php echo htmlspecialchars($o["nome_cliente"]); ?></div>
                <small class="text-muted"><?php echo htmlspecialchars($o["tel_cliente"]); ?></small>
              </td>
              <td><?php echo htmlspecialchars($o["nome_servico"]); ?></td>
              <td><?php echo date('d/m/Y', strtotime($o["data_servico"])); ?></td>
              <td>
                <?php if ($o["data_vencimento"]): ?>
                  <?php echo date('d/m/Y', strtotime($o["data_vencimento"])); ?>
                  <?php echo $vencBadge; ?>
                <?php else: ?>
                  <span class="text-muted small">Sem retorno</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($o["nome_profissional"]); ?></td>
              <td>R$ <?php echo number_format($o["preco_cobrado"], 2, ',', '.'); ?></td>
              <td><span class="badge <?php echo $statusBadge; ?>"><?php echo ucfirst($o["status"]); ?></span></td>
              <td>
                <a href="indexordem.php?id=<?php echo $o["idordem"]; ?>"
                   class="btn btn-sm btn-outline-primary" title="Editar">
                  <i class="bi bi-pencil"></i>
                </a>
                <a href="controller/OrdemServicoController.php?id=<?php echo $o["idordem"]; ?>"
                   class="btn btn-sm btn-outline-danger" title="Excluir"
                   onclick="return confirm('Confirma exclusão desta ordem?')">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
// Preenche o preço sugerido ao selecionar o serviço
function preencherPreco(sel) {
    const opt = sel.options[sel.selectedIndex];
    const preco = opt.getAttribute('data-preco');
    if (preco) {
        document.getElementById('txtPreco').value = preco;
    }
}
</script>

<?php include "rodape.html"; ?>
