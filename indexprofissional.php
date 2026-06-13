<?php
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:dashboard.php"); exit;
}

include_once "config/conexao.php";
include_once "model/profissional.php";
include_once "dao/ProfissionalDao.php";
include "topo.html";

$pDao = new ProfissionalDao();

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
    ? $pDao->readId($_GET["id"])
    : ["idprofissional"=>"","nome"=>"","telefone"=>"","idusuario"=>""];

$filtro = $_GET["filtro"] ?? "";
$lista  = $filtro ? $pDao->buscarComFiltro($filtro) : $pDao->read();
?>

<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-person-badge me-2"></i>
        <?php echo empty($result["idprofissional"]) ? "Novo Profissional" : "Editar Profissional"; ?>
      </div>
      <div class="card-body">
        <form method="post" action="controller/ProfissionalController.php">
          <input type="hidden" name="txtIdProfissional" value="<?php echo $result["idprofissional"]; ?>">

          <div class="mb-2">
            <label class="form-label">Nome <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="txtNome" required
                   placeholder="Nome completo"
                   value="<?php echo htmlspecialchars($result["nome"]); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Telefone <span class="text-danger">*</span></label>
            <input type="tel" class="form-control" name="txtTelefone" required
                   placeholder="(00) 00000-0000"
                   value="<?php echo htmlspecialchars($result["telefone"]); ?>">
          </div>
          <div class="d-flex gap-2">
            <button type="submit" name="btGravar" class="btn btn-primary flex-fill">
              <i class="bi bi-floppy me-1"></i> Gravar
            </button>
            <a href="indexprofissional.php" class="btn btn-outline-secondary">
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
        <i class="bi bi-people me-2"></i>Profissionais Cadastrados
      </div>
      <div class="card-body pb-0">
        <form method="get" action="indexprofissional.php" class="mb-3">
          <div class="input-group">
            <input type="text" class="form-control" name="filtro"
                   placeholder="Filtrar por nome..."
                   value="<?php echo htmlspecialchars($filtro); ?>">
            <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($filtro): ?>
              <a href="indexprofissional.php" class="btn btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </div>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0">
          <thead>
            <tr><th>#</th><th>Nome</th><th>Telefone</th><th>Ações</th></tr>
          </thead>
          <tbody>
            <?php if (is_null($lista)): ?>
              <tr><td colspan="4" class="text-center text-danger py-3">Erro ao buscar dados.</td></tr>
            <?php elseif (empty($lista)): ?>
              <tr><td colspan="4" class="text-center text-muted py-3">Nenhum profissional cadastrado.</td></tr>
            <?php else: ?>
              <?php foreach ($lista as $p): ?>
              <tr>
                <td><?php echo $p->idprofissional; ?></td>
                <td>
                  <i class="bi bi-person-circle text-primary me-1"></i>
                  <?php echo htmlspecialchars($p->nome); ?>
                </td>
                <td><?php echo htmlspecialchars($p->telefone); ?></td>
                <td>
                  <a href="indexprofissional.php?id=<?php echo $p->idprofissional; ?>"
                     class="btn btn-sm btn-outline-primary" title="Editar">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <a href="controller/ProfissionalController.php?id=<?php echo $p->idprofissional; ?>"
                     class="btn btn-sm btn-outline-danger" title="Excluir"
                     onclick="return confirm('Confirma exclusão deste profissional?')">
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
