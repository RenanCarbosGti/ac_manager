<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:dashboard.php"); exit;
}

include_once "config/conexao.php";
include_once "model/financeiro.php";
include_once "model/material.php";
include_once "dao/FinanceiroDao.php";
include_once "dao/OrdemServicoDao.php";
include_once "dao/MaterialDao.php";
include "topo.html";

$fDao = new FinanceiroDao();
$oDao = new OrdemServicoDao();
$mDao = new MaterialDao();

// Feedback
if (isset($_SESSION["resultado"])) {
    $cls = $_SESSION["resultado"] ? "alert-success" : "alert-danger";
    echo "<div class='alert $cls alert-dismissible fade show'>
            <i class='bi bi-check-circle me-1'></i> {$_SESSION['mensagem']}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
    unset($_SESSION["resultado"], $_SESSION["mensagem"]);
}

// Edição
$result = isset($_GET["id"]) ? $fDao->readId($_GET["id"])
        : ["idfinanceiro"=>"","tipo"=>"saida","descricao"=>"","valor"=>"",
           "data_lancamento"=>date('Y-m-d'),"idordem"=>"","categoria"=>"outros","observacoes"=>""];

$ordens    = $oDao->read()  ?: [];
$materiais = $mDao->read()  ?: [];

// Filtros
$fTipo      = $_GET["ftipo"]      ?? "";
$fCategoria = $_GET["fcategoria"] ?? "";
$fDataIni   = $_GET["fdataini"]   ?? "";
$fDataFim   = $_GET["fdatafim"]   ?? "";

$lista = ($fTipo || $fCategoria || $fDataIni || $fDataFim)
    ? $fDao->buscarComFiltro($fTipo, $fCategoria, $fDataIni, $fDataFim)
    : $fDao->read();

// Resumo financeiro
$resumo       = $fDao->resumoMes();
$saidas       = $resumo["total_saidas"] ?? 0;
$recServicos  = $fDao->receitaServicosTotal();   // receita ordens do mês
$recMateriais = $fDao->receitaMateriais();        // entradas categoria=material
$recTotal     = $recServicos + $recMateriais;
$saldo        = $recTotal - $saidas;

// Gráficos
$evolucao   = $fDao->evolucaoMensal()         ?: [];
$porServico = $fDao->receitaPorServico()       ?: [];
$porProf    = $fDao->receitaPorProfissional()  ?: [];
?>

<!-- 3 Cards de receita + saída + saldo -->
<div class="row g-3 mb-3">
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3 h-100">
      <div class="bg-success bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-tools fs-3 text-success"></i>
      </div>
      <div>
        <div class="text-muted small">Receita Serviços (mês)</div>
        <div class="fs-5 fw-bold text-success">R$ <?php echo number_format($recServicos,2,',','.'); ?></div>
        <div class="text-muted" style="font-size:.72rem;">Gerada automaticamente pelas OS</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3 h-100">
      <div class="bg-info bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-box-seam fs-3 text-info"></i>
      </div>
      <div>
        <div class="text-muted small">Receita Materiais (mês)</div>
        <div class="fs-5 fw-bold text-info">R$ <?php echo number_format($recMateriais,2,',','.'); ?></div>
        <div class="text-muted" style="font-size:.72rem;">Lançamentos categoria Material</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3 h-100">
      <div class="bg-primary bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-graph-up-arrow fs-3 text-primary"></i>
      </div>
      <div>
        <div class="text-muted small">Receita Total (mês)</div>
        <div class="fs-5 fw-bold text-primary">R$ <?php echo number_format($recTotal,2,',','.'); ?></div>
        <div class="text-muted" style="font-size:.72rem;">Serviços + Materiais</div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card p-3 d-flex flex-row align-items-center gap-3 h-100">
      <div class="bg-danger bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-arrow-up-circle fs-3 text-danger"></i>
      </div>
      <div>
        <div class="text-muted small">Saídas (mês)</div>
        <div class="fs-5 fw-bold text-danger">R$ <?php echo number_format($saidas,2,',','.'); ?></div>
      </div>
    </div>
  </div>
</div>
<div class="row g-3 mb-4">
  <div class="col-12">
    <div class="card p-3 d-flex flex-row align-items-center gap-3">
      <div class="bg-<?php echo $saldo>=0?'success':'warning'; ?> bg-opacity-10 rounded-circle p-3">
        <i class="bi bi-wallet2 fs-3 text-<?php echo $saldo>=0?'success':'warning'; ?>"></i>
      </div>
      <div>
        <div class="text-muted small">Saldo do Mês (Receita Total – Saídas)</div>
        <div class="fs-4 fw-bold text-<?php echo $saldo>=0?'success':'warning'; ?>">
          R$ <?php echo number_format($saldo,2,',','.'); ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Gráficos -->
<div class="row g-3 mb-4">
  <div class="col-lg-6">
    <div class="card p-3">
      <div class="fw-semibold mb-2"><i class="bi bi-bar-chart me-2 text-primary"></i>Evolução 6 meses</div>
      <canvas id="graficoEvolucao" height="160"></canvas>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card p-3">
      <div class="fw-semibold mb-2"><i class="bi bi-bar-chart-steps me-2 text-primary"></i>Receita por Serviço</div>
      <canvas id="graficoPorServico" height="200"></canvas>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card p-3">
      <div class="fw-semibold mb-2"><i class="bi bi-people me-2 text-primary"></i>Por Profissional</div>
      <?php foreach ($porProf as $pp):
        $maxProf = max(array_column($porProf, 'total')) ?: 1;
        $pct = round(($pp["total"] / $maxProf) * 100);
      ?>
        <div class="mb-2">
          <div class="d-flex justify-content-between small mb-1">
            <span><?php echo htmlspecialchars($pp["profissional"]); ?></span>
            <span class="fw-semibold">R$ <?php echo number_format($pp["total"],2,',','.'); ?></span>
          </div>
          <div class="progress" style="height:6px;">
            <div class="progress-bar" style="width:<?php echo $pct; ?>%"></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($porProf)): ?>
        <p class="text-muted small mb-0">Sem dados ainda.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Formulário + Tabela -->
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-cash-coin me-2"></i>
        <?php echo empty($result["idfinanceiro"]) ? "Novo Lançamento" : "Editar Lançamento #".$result["idfinanceiro"]; ?>
      </div>
      <div class="card-body">
        <form method="post" action="controller/FinanceiroController.php" id="formLanc">
          <input type="hidden" name="txtIdFinanceiro" value="<?php echo $result["idfinanceiro"]; ?>">

          <div class="mb-2">
            <label class="form-label">Tipo <span class="text-danger">*</span></label>
            <select class="form-select" name="cbTipo" id="cbTipo" required onchange="toggleTipo()">
              <option value="entrada" <?php echo $result["tipo"]=="entrada"?"selected":""; ?>>⬇ Entrada (Receita)</option>
              <option value="saida"   <?php echo $result["tipo"]=="saida"  ?"selected":""; ?>>⬆ Saída (Despesa)</option>
            </select>
          </div>

          <div class="mb-2">
            <label class="form-label">Descrição <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="txtDescricao" required
                   placeholder="Ex: Compra de gás, Combustível..."
                   value="<?php echo htmlspecialchars($result["descricao"]); ?>">
          </div>

          <div class="mb-2">
            <label class="form-label">Valor (R$) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="txtValor"
                   step="0.01" min="0.01" required placeholder="0,00"
                   value="<?php echo $result["valor"]; ?>">
          </div>

          <div class="mb-2">
            <label class="form-label">Data <span class="text-danger">*</span></label>
            <input type="date" class="form-control" name="txtData" required
                   value="<?php echo $result["data_lancamento"]; ?>">
          </div>

          <div class="mb-2">
            <label class="form-label">Categoria</label>
            <select class="form-select" name="cbCategoria" id="cbCategoria" onchange="toggleTipo()">
              <?php foreach (["servico"=>"Serviço","material"=>"Material","combustivel"=>"Combustível","ferramenta"=>"Ferramenta","outros"=>"Outros"] as $v=>$l): ?>
                <option value="<?php echo $v; ?>" <?php echo $result["categoria"]==$v?"selected":""; ?>><?php echo $l; ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Vincular ordem (sempre visível) -->
          <div class="mb-2">
            <label class="form-label">Vincular a uma Ordem</label>
            <select class="form-select" name="cbIdOrdem" id="cbIdOrdem">
              <option value="">Nenhuma</option>
              <?php foreach ($ordens as $o): ?>
                <option value="<?php echo $o["idordem"]; ?>"
                  <?php echo $result["idordem"]==$o["idordem"]?"selected":""; ?>>
                  #<?php echo $o["idordem"]; ?> – <?php echo htmlspecialchars($o["nome_cliente"]); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Seção de materiais usados — só aparece em SAÍDA -->
          <div id="secaoMateriais" class="mb-2" style="display:none;">
            <label class="form-label fw-semibold text-danger">
              <i class="bi bi-boxes me-1"></i>Materiais Utilizados
              <span class="fw-normal text-muted small">(dará baixa automática no estoque)</span>
            </label>
            <div id="listaMateriais">
              <div class="material-linha d-flex gap-2 mb-2 align-items-center">
                <select class="form-select form-select-sm" name="mat_id[]">
                  <option value="">Selecione...</option>
                  <?php foreach ($materiais as $m): ?>
                    <option value="<?php echo $m->idmaterial; ?>"
                            data-unidade="<?php echo $m->unidade; ?>">
                      <?php echo htmlspecialchars($m->nome); ?> (<?php echo number_format($m->estoque_atual,2,',','.'); ?> <?php echo $m->unidade; ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
                <input type="number" class="form-control form-control-sm" name="mat_qtd[]"
                       placeholder="Qtd" step="0.01" min="0.01" style="width:80px;">
                <span class="mat-unidade text-muted small" style="width:30px;"></span>
              </div>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="addMaterial()">
              <i class="bi bi-plus-circle me-1"></i>Adicionar material
            </button>
          </div>

          <div class="mb-3">
            <label class="form-label">Observações</label>
            <textarea class="form-control" name="txtObservacoes" rows="2"><?php echo htmlspecialchars($result["observacoes"]); ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" name="btGravar" class="btn btn-primary flex-fill">
              <i class="bi bi-floppy me-1"></i> Gravar
            </button>
            <a href="indexfinanceiro.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Tabela lançamentos -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-list-ul me-2"></i>Lançamentos
      </div>
      <div class="card-body pb-0">
        <form method="get" action="indexfinanceiro.php" class="row g-2 mb-3">
          <div class="col-sm-3">
            <select class="form-select form-select-sm" name="ftipo">
              <option value="">Todos tipos</option>
              <option value="entrada" <?php echo $fTipo=="entrada"?"selected":""; ?>>Entrada</option>
              <option value="saida"   <?php echo $fTipo=="saida"  ?"selected":""; ?>>Saída</option>
            </select>
          </div>
          <div class="col-sm-3">
            <select class="form-select form-select-sm" name="fcategoria">
              <option value="">Todas categorias</option>
              <?php foreach (["servico"=>"Serviço","material"=>"Material","combustivel"=>"Combustível","ferramenta"=>"Ferramenta","outros"=>"Outros"] as $v=>$l): ?>
                <option value="<?php echo $v; ?>" <?php echo $fCategoria==$v?"selected":""; ?>><?php echo $l; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-sm-2">
            <input type="date" class="form-control form-control-sm" name="fdataini" value="<?php echo $fDataIni; ?>">
          </div>
          <div class="col-sm-2">
            <input type="date" class="form-control form-select-sm" name="fdatafim" value="<?php echo $fDataFim; ?>">
          </div>
          <div class="col-sm-2 d-flex gap-1">
            <button type="submit" class="btn btn-outline-primary btn-sm flex-fill"><i class="bi bi-search"></i></button>
            <a href="indexfinanceiro.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Categ.</th><th>Valor</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php if (empty($lista)): ?>
              <tr><td colspan="6" class="text-center text-muted py-3">Nenhum lançamento encontrado.</td></tr>
            <?php else: foreach ($lista as $item):
              $isEnt = $item["tipo"] === "entrada";
              $autoOS = !empty($item["idordem"]) && $isEnt && $item["categoria"] === "servico";
            ?>
            <tr>
              <td><?php echo date('d/m/Y', strtotime($item["data_lancamento"])); ?></td>
              <td>
                <span class="badge <?php echo $isEnt ? 'bg-success' : 'bg-danger'; ?>">
                  <?php echo $isEnt ? '⬇ Entrada' : '⬆ Saída'; ?>
                </span>
                <?php if ($autoOS): ?>
                  <span class="badge bg-secondary" title="Gerado automaticamente pela OS">⚙ Auto</span>
                <?php endif; ?>
              </td>
              <td><?php echo htmlspecialchars($item["descricao"]); ?></td>
              <td><span class="badge bg-secondary bg-opacity-75"><?php echo ucfirst($item["categoria"]); ?></span></td>
              <td class="fw-semibold <?php echo $isEnt ? 'text-success' : 'text-danger'; ?>">
                <?php echo $isEnt ? '+' : '-'; ?> R$ <?php echo number_format($item["valor"],2,',','.'); ?>
              </td>
              <td>
                <?php if (!$autoOS): // Não edita entradas automáticas ?>
                <a href="indexfinanceiro.php?id=<?php echo $item["idfinanceiro"]; ?>"
                   class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                <?php endif; ?>
                <a href="controller/FinanceiroController.php?id=<?php echo $item["idfinanceiro"]; ?>"
                   class="btn btn-sm btn-outline-danger"
                   onclick="return confirm('Confirma exclusão?')"><i class="bi bi-trash"></i></a>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Materiais HTML template
const matOpts = `<?php foreach ($materiais as $m): ?><option value="<?php echo $m->idmaterial; ?>" data-unidade="<?php echo $m->unidade; ?>"><?php echo htmlspecialchars($m->nome); ?> (<?php echo number_format($m->estoque_atual,2,',','.'); ?> <?php echo $m->unidade; ?>)</option><?php endforeach; ?>`;

function toggleTipo() {
    const tipo = document.getElementById('cbTipo').value;
    const sec  = document.getElementById('secaoMateriais');
    sec.style.display = tipo === 'saida' ? 'block' : 'none';
}

function addMaterial() {
    const div = document.createElement('div');
    div.className = 'material-linha d-flex gap-2 mb-2 align-items-center';
    div.innerHTML = `
        <select class="form-select form-select-sm" name="mat_id[]" onchange="updateUnidade(this)">
            <option value="">Selecione...</option>${matOpts}
        </select>
        <input type="number" class="form-control form-control-sm" name="mat_qtd[]"
               placeholder="Qtd" step="0.01" min="0.01" style="width:80px;">
        <span class="mat-unidade text-muted small" style="width:30px;"></span>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()">
            <i class="bi bi-x"></i>
        </button>`;
    document.getElementById('listaMateriais').appendChild(div);
}

function updateUnidade(sel) {
    const opt   = sel.options[sel.selectedIndex];
    const unid  = opt.getAttribute('data-unidade') || '';
    sel.parentElement.querySelector('.mat-unidade').textContent = unid;
}

// Init: mostrar seção se edição for saída
document.addEventListener('DOMContentLoaded', () => {
    toggleTipo();
    document.querySelectorAll('.material-linha select').forEach(s => updateUnidade(s));
});

// Gráfico evolução 6 meses
const evolucaoData = <?php echo json_encode($evolucao); ?>;
new Chart(document.getElementById('graficoEvolucao'), {
    type: 'bar',
    data: {
        labels:   evolucaoData.map(d => d.mes),
        datasets: [
            { label: 'Entradas', data: evolucaoData.map(d => d.entradas), backgroundColor: '#19875499' },
            { label: 'Saídas',   data: evolucaoData.map(d => d.saidas),   backgroundColor: '#dc354599' }
        ]
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom' } }, scales:{ y:{ beginAtZero:true } } }
});

// Gráfico receita por serviço (barras horizontais)
const servicoData = <?php echo json_encode($porServico); ?>;
new Chart(document.getElementById('graficoPorServico'), {
    type: 'bar',
    data: {
        labels:   servicoData.map(d => d.servico),
        datasets: [{ label: 'R$', data: servicoData.map(d => d.total),
            backgroundColor: ['#0d6efd','#198754','#ffc107','#dc3545','#0dcaf0','#6c757d'] }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true } }
    }
});
</script>

<?php include "rodape.html"; ?>
