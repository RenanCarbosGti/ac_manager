<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:dashboard.php"); exit;
}

include_once "config/conexao.php";
include_once "model/material.php";
include_once "dao/MaterialDao.php";
include_once "dao/OrdemServicoDao.php";
include "topo.html";

$mDao = new MaterialDao();
$oDao = new OrdemServicoDao();

// Feedback
if (isset($_SESSION["resultado"])) {
    $cls = $_SESSION["resultado"] ? "alert-success" : "alert-danger";
    echo "<div class='alert $cls alert-dismissible fade show'>
            <i class='bi bi-check-circle me-1'></i> {$_SESSION['mensagem']}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
    unset($_SESSION["resultado"], $_SESSION["mensagem"]);
}

$result = isset($_GET["id"]) ? $mDao->readId($_GET["id"])
        : ["idmaterial"=>"","nome"=>"","descricao"=>"","unidade"=>"un",
           "estoque_atual"=>"0","estoque_minimo"=>"1","preco_custo"=>""];

$filtro  = $_GET["filtro"] ?? "";
$lista   = $filtro ? $mDao->buscarComFiltro($filtro) : $mDao->read();
$alertas = $mDao->buscarAbaixoMinimo() ?: [];
$movs    = $mDao->buscarMovimentacoes() ?: [];
$ordens  = $oDao->read() ?: [];
$todosMat = $mDao->read() ?: [];
?>

<!-- Alertas de estoque baixo -->
<?php if (!empty($alertas)): ?>
<div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2">
  <i class="bi bi-exclamation-triangle-fill fs-5"></i>
  <div>
    <strong><?php echo count($alertas); ?> material(is) abaixo do estoque mínimo:</strong>
    <?php echo implode(', ', array_map(fn($a) => htmlspecialchars($a["nome"]), $alertas)); ?>
  </div>
  <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-3">
  <!-- Formulário cadastro/edição -->
  <div class="col-lg-4">
    <div class="card mb-3">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-box-seam me-2"></i>
        <?php echo empty($result["idmaterial"]) ? "Novo Material" : "Editar Material"; ?>
      </div>
      <div class="card-body">
        <form method="post" action="controller/MaterialController.php">
          <input type="hidden" name="txtIdMaterial" value="<?php echo $result["idmaterial"]; ?>">

          <div class="mb-2">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="txtNome" required
                   placeholder="Ex: Gás R-410A"
                   value="<?php echo htmlspecialchars($result["nome"]); ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Descrição</label>
            <input type="text" class="form-control" name="txtDescricao"
                   placeholder="Descrição do material"
                   value="<?php echo htmlspecialchars($result["descricao"]); ?>">
          </div>
          <div class="row g-2 mb-2">
            <div class="col-4">
              <label class="form-label">Unidade</label>
              <select class="form-select" name="txtUnidade">
                <?php foreach (["un","kg","l","m","cx","rolo","par"] as $u): ?>
                  <option value="<?php echo $u; ?>" <?php echo $result["unidade"]==$u?"selected":""; ?>><?php echo $u; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label">Qtd. Inicial</label>
              <input type="number" class="form-control" name="txtEstoqueAtual"
                     step="0.01" min="0" value="<?php echo $result["estoque_atual"]; ?>">
            </div>
            <div class="col-4">
              <label class="form-label">Estq. Mínimo</label>
              <input type="number" class="form-control" name="txtEstoqueMin"
                     step="0.01" min="0" value="<?php echo $result["estoque_minimo"]; ?>">
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Preço de Custo (R$)</label>
            <input type="number" class="form-control" name="txtPrecoCusto"
                   step="0.01" min="0" placeholder="0,00"
                   value="<?php echo $result["preco_custo"]; ?>">
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="btGravar" class="btn btn-primary flex-fill">
              <i class="bi bi-floppy me-1"></i> Gravar
            </button>
            <a href="indexmaterial.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
          </div>
        </form>
      </div>
    </div>

    <!-- Card: registrar movimentação -->
    <div class="card">
      <div class="card-header bg-success text-white fw-semibold">
        <i class="bi bi-arrow-left-right me-2"></i>Registrar Movimentação
      </div>
      <div class="card-body">
        <form method="post" action="controller/MaterialController.php">
          <div class="mb-2">
            <label class="form-label">Material <span class="text-danger">*</span></label>
            <select class="form-select" name="cbIdMaterial" required>
              <option value="">Selecione...</option>
              <?php foreach ($todosMat as $tm): ?>
                <option value="<?php echo $tm->idmaterial; ?>">
                  <?php echo htmlspecialchars($tm->nome); ?>
                  (<?php echo number_format($tm->estoque_atual,2,',','.'); ?> <?php echo $tm->unidade; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label">Tipo</label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="cbTipoMov" id="rbEntrada" value="entrada" checked>
              <label class="btn btn-outline-success" for="rbEntrada"><i class="bi bi-plus-circle me-1"></i>Entrada</label>
              <input type="radio" class="btn-check" name="cbTipoMov" id="rbSaida" value="saida">
              <label class="btn btn-outline-danger" for="rbSaida"><i class="bi bi-dash-circle me-1"></i>Saída</label>
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label">Quantidade <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="txtQuantidade"
                   step="0.01" min="0.01" required placeholder="0">
          </div>
          <div class="mb-2">
            <label class="form-label">Data</label>
            <input type="date" class="form-control" name="txtDataMov" value="<?php echo date('Y-m-d'); ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Motivo</label>
            <input type="text" class="form-control" name="txtMotivo"
                   placeholder="Ex: Compra, Uso em serviço...">
          </div>
          <div class="mb-3">
            <label class="form-label">Vincular à Ordem</label>
            <select class="form-select" name="cbIdOrdem">
              <option value="">Nenhuma</option>
              <?php foreach ($ordens as $o): ?>
                <option value="<?php echo $o["idordem"]; ?>">
                  #<?php echo $o["idordem"]; ?> – <?php echo htmlspecialchars($o["nome_cliente"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" name="btMovimentar" class="btn btn-success w-100">
            <i class="bi bi-arrow-left-right me-1"></i> Registrar
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- Tabelas direita -->
  <div class="col-lg-8">

    <!-- Estoque atual -->
    <div class="card mb-3">
      <div class="card-header bg-primary text-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-boxes me-2"></i>Estoque Atual</span>
      </div>
      <div class="card-body pb-1">
        <form method="get" action="indexmaterial.php" class="mb-3">
          <div class="input-group">
            <input type="text" class="form-control" name="filtro"
                   placeholder="Filtrar por nome..."
                   value="<?php echo htmlspecialchars($filtro); ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($filtro): ?>
              <a href="indexmaterial.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Material</th><th>Unid.</th><th>Estoque</th><th>Mínimo</th><th>Custo Unit.</th><th>Valor Total</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php if (empty($lista)): ?>
              <tr><td colspan="7" class="text-center text-muted py-3">Nenhum material cadastrado.</td></tr>
            <?php else: foreach ($lista as $m):
              $abaixo = $m->estoque_atual <= $m->estoque_minimo;
              $valorTotal = $m->estoque_atual * $m->preco_custo;
            ?>
            <tr class="<?php echo $abaixo ? 'table-warning' : ''; ?>">
              <td>
                <div class="fw-semibold"><?php echo htmlspecialchars($m->nome); ?></div>
                <small class="text-muted"><?php echo htmlspecialchars($m->descricao); ?></small>
              </td>
              <td><?php echo $m->unidade; ?></td>
              <td>
                <span class="fw-bold <?php echo $abaixo ? 'text-danger' : 'text-success'; ?>">
                  <?php echo number_format($m->estoque_atual,2,',','.'); ?>
                </span>
                <?php if ($abaixo): ?>
                  <i class="bi bi-exclamation-triangle-fill text-warning ms-1" title="Abaixo do mínimo!"></i>
                <?php endif; ?>
              </td>
              <td><?php echo number_format($m->estoque_minimo,2,',','.'); ?></td>
              <td>R$ <?php echo number_format($m->preco_custo,2,',','.'); ?></td>
              <td>R$ <?php echo number_format($valorTotal,2,',','.'); ?></td>
              <td>
                <a href="indexmaterial.php?id=<?php echo $m->idmaterial; ?>"
                   class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <a href="controller/MaterialController.php?id=<?php echo $m->idmaterial; ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Confirma exclusão?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
          <?php if (!empty($lista)): ?>
          <tfoot class="table-light">
            <tr>
              <td colspan="5" class="text-end fw-semibold">Valor total em estoque:</td>
              <td class="fw-bold text-primary">
                R$ <?php
                  $total = array_sum(array_map(fn($m) => $m->estoque_atual * $m->preco_custo, $lista));
                  echo number_format($total,2,',','.');
                ?>
              </td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <!-- Últimas movimentações -->
    <div class="card">
      <div class="card-header bg-white text-dark border-bottom fw-semibold">
        <i class="bi bi-clock-history text-primary me-2"></i>Últimas Movimentações
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Data</th><th>Material</th><th>Tipo</th><th>Qtd.</th><th>Motivo</th><th>Ordem</th></tr>
          </thead>
          <tbody>
            <?php if (empty($movs)): ?>
              <tr><td colspan="6" class="text-center text-muted py-3">Nenhuma movimentação registrada.</td></tr>
            <?php else: foreach (array_slice($movs, 0, 15) as $mv): ?>
            <tr>
              <td><?php echo date('d/m/Y', strtotime($mv["data_movimentacao"])); ?></td>
              <td><?php echo htmlspecialchars($mv["nome_material"]); ?></td>
              <td>
                <span class="badge <?php echo $mv["tipo"]==='entrada' ? 'bg-success' : 'bg-danger'; ?>">
                  <?php echo $mv["tipo"]==='entrada' ? '⬇ Entrada' : '⬆ Saída'; ?>
                </span>
              </td>
              <td><?php echo number_format($mv["quantidade"],2,',','.'); ?> <?php echo $mv["unidade"]; ?></td>
              <td><?php echo htmlspecialchars($mv["motivo"] ?? "—"); ?></td>
              <td><?php echo $mv["idordem"] ? "#".$mv["idordem"] : "—"; ?></td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<?php include "rodape.html"; ?>
