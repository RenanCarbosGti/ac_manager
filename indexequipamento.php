<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "dao/EquipamentoDao.php";
include "topo.html";

$eDao = new EquipamentoDao();

if (isset($_SESSION["resultado"])) {
    $cls = $_SESSION["resultado"] ? "alert-success" : "alert-danger";
    echo "<div class='alert $cls alert-dismissible fade show'>
            <i class='bi bi-check-circle me-1'></i> {$_SESSION['mensagem']}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
    $_SESSION["resultado"] = null;
    $_SESSION["mensagem"]  = null;
}

if (isset($_GET["id"])) {
    $result = $eDao->readId($_GET["id"]);
} elseif (isset($_GET["qr"])) {
    $result = $eDao->buscarPorQR($_GET["qr"]);
    if ($result) $_SESSION["qr_equipamento"] = $result["idequipamento"];
} else {
    $result = ["idequipamento"=>"","codigo_qr"=>"","nome_cliente"=>"","endereco"=>"","telefone"=>"","modelo"=>"","marca"=>""];
}

$filtro        = $_GET["filtro"] ?? "";
$lista         = $filtro ? $eDao->buscarComFiltro('', $filtro) : $eDao->read();
$clientesExist = $eDao->buscarClientesUnicos();
$isEdicao      = !empty($result["idequipamento"]);
?>

<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-cpu me-2"></i>
        <?php echo $isEdicao ? "Editar Equipamento" : "Novo Equipamento"; ?>
      </div>
      <div class="card-body">
        <form method="post" action="controller/EquipamentoController.php">
          <input type="hidden" name="txtIdEquipamento" value="<?php echo $result["idequipamento"]; ?>">

          <?php if (!empty($result["codigo_qr"])): ?>
            <div class="mb-2">
              <label class="form-label small text-muted">Código QR</label>
              <input type="text" class="form-control form-control-sm bg-light"
                     value="<?php echo htmlspecialchars($result["codigo_qr"]); ?>" readonly>
            </div>
          <?php endif; ?>

          <?php if (!$isEdicao): ?>
          <!-- Novo cliente ou existente -->
          <div class="mb-3">
            <label class="form-label fw-semibold">Cliente <span class="text-danger">*</span></label>
            <div class="btn-group w-100 mb-2" role="group">
              <input type="radio" class="btn-check" name="tipoCliente" id="rbNovo" value="novo"
                     checked onchange="toggleCliente()">
              <label class="btn btn-outline-primary btn-sm" for="rbNovo">
                <i class="bi bi-person-plus me-1"></i>Novo Cliente
              </label>
              <input type="radio" class="btn-check" name="tipoCliente" id="rbExist" value="existente"
                     onchange="toggleCliente()">
              <label class="btn btn-outline-success btn-sm" for="rbExist">
                <i class="bi bi-person-check me-1"></i>Cliente Existente
              </label>
            </div>
            <div id="secaoExistente" style="display:none;">
              <select class="form-select" id="cbClienteExist" onchange="preencherCliente()">
                <option value="">Selecione o cliente...</option>
                <?php foreach ($clientesExist as $c): ?>
                  <option value="<?php echo $c['idequipamento']; ?>"
                          data-nome="<?php echo htmlspecialchars($c['nome_cliente']); ?>"
                          data-tel="<?php echo htmlspecialchars($c['telefone']); ?>"
                          data-end="<?php echo htmlspecialchars($c['endereco']); ?>">
                    <?php echo htmlspecialchars($c['nome_cliente']); ?>
                    &mdash; <?php echo htmlspecialchars($c['telefone']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="form-text text-success small">
                <i class="bi bi-info-circle me-1"></i>Dados preenchidos automaticamente.
              </div>
            </div>
          </div>
          <?php endif; ?>

          <div id="secaoDadosCliente">
            <div class="mb-2">
              <label class="form-label">Nome do Cliente <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="txtNomeCliente" id="txtNomeCliente"
                     placeholder="Nome completo" required
                     value="<?php echo htmlspecialchars($result["nome_cliente"]); ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Endereço</label>
              <input type="text" class="form-control" name="txtEndereco" id="txtEndereco"
                     placeholder="Rua, número, bairro, cidade"
                     value="<?php echo htmlspecialchars($result["endereco"]); ?>">
            </div>
            <div class="mb-2">
              <label class="form-label">Telefone</label>
              <input type="tel" class="form-control" name="txtTelefone" id="txtTelefone"
                     placeholder="(00) 00000-0000"
                     value="<?php echo htmlspecialchars($result["telefone"]); ?>">
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label">Marca do AC</label>
            <input type="text" class="form-control" name="txtMarca"
                   placeholder="Ex: Samsung, LG, Midea..."
                   value="<?php echo htmlspecialchars($result["marca"]); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Modelo do AC</label>
            <input type="text" class="form-control" name="txtModelo"
                   placeholder="Ex: Split 9000 BTU inverter"
                   value="<?php echo htmlspecialchars($result["modelo"]); ?>">
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="btGravar" class="btn btn-primary flex-fill">
              <i class="bi bi-floppy me-1"></i> Gravar
            </button>
            <a href="indexequipamento.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg"></i>
            </a>
          </div>
        </form>
      </div>
    </div>

    <!-- Busca por QR Code -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white fw-semibold">
        <i class="bi bi-qr-code-scan me-2"></i>Ler QR Code
      </div>
      <div class="card-body">
        <p class="text-muted small">Insira o código do QR colado no equipamento para localizar o cliente.</p>
        <form method="get" action="indexequipamento.php">
          <div class="input-group">
            <input type="text" class="form-control" name="qr" placeholder="Ex: AC-A1B2C3D4-123">
            <button class="btn btn-success" type="submit">
              <i class="bi bi-search"></i>
            </button>
          </div>
        </form>
        <?php if (isset($_GET["qr"]) && empty($result["idequipamento"])): ?>
          <div class="alert alert-warning mt-2 mb-0 py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>QR Code não encontrado.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Tabela de listagem -->
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-primary text-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Equipamentos Cadastrados</span>
      </div>
      <div class="card-body pb-1">
        <form method="get" action="indexequipamento.php" class="mb-3">
          <div class="input-group">
            <input type="text" class="form-control" name="filtro"
                   placeholder="Filtrar por nome do cliente..."
                   value="<?php echo htmlspecialchars($filtro); ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($filtro): ?>
              <a href="indexequipamento.php" class="btn btn-outline-secondary">
                <i class="bi bi-x-lg"></i>
              </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr>
              <th>#</th><th>Cliente</th><th>Telefone</th>
              <th>Marca / Modelo</th><th>Cód. QR</th><th>Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php if (is_null($lista)): ?>
              <tr><td colspan="6" class="text-center text-danger py-3">Erro ao buscar dados.</td></tr>
            <?php elseif (empty($lista)): ?>
              <tr><td colspan="6" class="text-center text-muted py-3">Nenhum equipamento cadastrado.</td></tr>
            <?php else: ?>
              <?php foreach ($lista as $item): ?>
              <tr>
                <td><?php echo $item->idequipamento; ?></td>
                <td>
                  <div><?php echo htmlspecialchars($item->nome_cliente); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($item->endereco); ?></small>
                </td>
                <td><?php echo htmlspecialchars($item->telefone); ?></td>
                <td><?php echo htmlspecialchars(trim($item->marca . " " . $item->modelo)); ?></td>
                <td><code class="small"><?php echo htmlspecialchars($item->codigo_qr); ?></code></td>
                <td>
                  <a href="qrcode.php?id=<?php echo $item->idequipamento; ?>"
                     class="btn btn-sm btn-outline-success" title="Imprimir QR Code">
                    <i class="bi bi-qr-code"></i>
                  </a>
                  <a href="indexordem.php?equip=<?php echo $item->idequipamento; ?>"
                     class="btn btn-sm btn-outline-info" title="Ver ordens">
                    <i class="bi bi-clipboard2-check"></i>
                  </a>
                  <a href="indexequipamento.php?id=<?php echo $item->idequipamento; ?>"
                     class="btn btn-sm btn-outline-primary" title="Editar">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="controller/EquipamentoController.php?id=<?php echo $item->idequipamento; ?>"
                     class="btn btn-sm btn-outline-danger" title="Excluir"
                     onclick="return confirm('Confirma exclusão deste equipamento?')">
                    <i class="bi bi-trash"></i>
                  </a>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function toggleCliente() {
    const tipo   = document.querySelector('input[name="tipoCliente"]:checked').value;
    const secEx  = document.getElementById('secaoExistente');
    const campos = document.getElementById('secaoDadosCliente');
    if (tipo === 'existente') {
        secEx.style.display  = 'block';
        campos.style.display = 'none';
    } else {
        secEx.style.display  = 'none';
        campos.style.display = 'block';
        // Limpa os campos ao voltar para novo
        document.getElementById('txtNomeCliente').value = '';
        document.getElementById('txtEndereco').value    = '';
        document.getElementById('txtTelefone').value    = '';
    }
}

function preencherCliente() {
    const sel    = document.getElementById('cbClienteExist');
    const opt    = sel.options[sel.selectedIndex];
    const campos = document.getElementById('secaoDadosCliente');
    if (!opt.value) return;
    campos.style.display = 'block';
    document.getElementById('txtNomeCliente').value = opt.getAttribute('data-nome') || '';
    document.getElementById('txtEndereco').value    = opt.getAttribute('data-end')  || '';
    document.getElementById('txtTelefone').value    = opt.getAttribute('data-tel')  || '';
}
</script>

<?php include "rodape.html"; ?>
