<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/servico.php";
include_once "dao/ServicoDao.php";
include "topo.html";

$sDao = new ServicoDao();

if (isset($_SESSION["resultado"])) {
    $cls = $_SESSION["resultado"] ? "alert-success" : "alert-danger";
    echo "<div class='alert $cls alert-dismissible fade show'>
            <i class='bi bi-check-circle me-1'></i> {$_SESSION['mensagem']}
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
          </div>";
    $_SESSION["resultado"] = null;
    $_SESSION["mensagem"]  = null;
}

$result = isset($_GET["id"])
    ? $sDao->readId($_GET["id"])
    : ["idservico"=>"","nome"=>"","descricao"=>"","validade_dias"=>"","preco"=>""];

$filtro = $_GET["filtro"] ?? "";
$lista  = $filtro ? $sDao->buscarComFiltro($filtro) : $sDao->read();
?>

<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-gear me-2"></i>
        <?php echo empty($result["idservico"]) ? "Novo Serviço" : "Editar Serviço"; ?>
      </div>
      <div class="card-body">
        <form method="post" action="controller/ServicoController.php">
          <input type="hidden" name="txtIdServico" value="<?php echo $result["idservico"]; ?>">

          <div class="mb-2">
            <label class="form-label">Nome do Serviço <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="txtNome" required
                   placeholder="Ex: Higienização Completa"
                   value="<?php echo htmlspecialchars($result["nome"]); ?>">
          </div>
          <div class="mb-2">
            <label class="form-label">Descrição</label>
            <textarea class="form-control" name="txtDescricao" rows="2"
                      placeholder="Descreva o serviço..."><?php echo htmlspecialchars($result["descricao"]); ?></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label">Validade (dias)</label>
            <input type="number" class="form-control" name="txtValidade" min="1"
                   placeholder="Deixe em branco se não tem recorrência"
                   value="<?php echo $result["validade_dias"]; ?>">
            <div class="form-text">Ex: 180 para 6 meses. Vazio = serviço sem recorrência.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Preço Base (R$) <span class="text-danger">*</span></label>
            <input type="number" class="form-control" name="txtPreco"
                   step="0.01" min="0" required placeholder="0,00"
                   value="<?php echo $result["preco"]; ?>">
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="btGravar" class="btn btn-primary flex-fill">
              <i class="bi bi-floppy me-1"></i> Gravar
            </button>
            <a href="indexservico.php" class="btn btn-outline-secondary">
              <i class="bi bi-x-lg"></i>
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-list-ul me-2"></i>Serviços Cadastrados
      </div>
      <div class="card-body pb-0">
        <form method="get" action="indexservico.php" class="mb-3">
          <div class="input-group">
            <input type="text" class="form-control" name="filtro"
                   placeholder="Filtrar por nome do serviço..."
                   value="<?php echo htmlspecialchars($filtro); ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($filtro): ?>
              <a href="indexservico.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>#</th><th>Nome</th><th>Validade</th><th>Preço Base</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php if (is_null($lista)): ?>
              <tr><td colspan="5" class="text-center text-danger py-3">Erro ao buscar dados.</td></tr>
            <?php elseif (empty($lista)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">Nenhum serviço cadastrado.</td></tr>
            <?php else: ?>
              <?php foreach ($lista as $s): ?>
              <tr>
                <td><?php echo $s->idservico; ?></td>
                <td>
                  <div><?php echo htmlspecialchars($s->nome); ?></div>
                  <small class="text-muted"><?php echo htmlspecialchars($s->descricao); ?></small>
                </td>
                <td>
                  <?php if ($s->validade_dias): ?>
                    <span class="badge bg-info text-dark"><?php echo $s->validade_dias; ?> dias</span>
                  <?php else: ?>
                    <span class="text-muted small">Sem recorrência</span>
                  <?php endif; ?>
                </td>
                <td>R$ <?php echo number_format($s->preco, 2, ',', '.'); ?></td>
                <td>
                  <a href="indexservico.php?id=<?php echo $s->idservico; ?>"
                     class="btn btn-sm btn-outline-primary" title="Editar">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="controller/ServicoController.php?id=<?php echo $s->idservico; ?>"
                     class="btn btn-sm btn-outline-danger" title="Excluir"
                     onclick="return confirm('Confirma exclusão deste serviço?')">
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

<?php include "rodape.html"; ?>
