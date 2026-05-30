<?php
// buscar_equipamento.php
// Profissional busca o aparelho por telefone do cliente ou lendo o QR Code
session_start();
if (!isset($_SESSION["idusuario"])) { header("location:login.php"); exit; }

include_once "config/conexao.php";
include_once "model/equipamento.php";
include_once "dao/EquipamentoDao.php";
include_once "dao/OrdemServicoDao.php";
include "topo.html";

$eDao   = new EquipamentoDao();
$oDao   = new OrdemServicoDao();

$equip  = null;
$ordens = [];
$busca  = trim($_GET["busca"] ?? "");
$tipo   = $_GET["tipo"]  ?? "telefone"; // 'telefone' ou 'qr'
$erro   = "";

if (!empty($busca)) {
    if ($tipo === "qr") {
        $equip = $eDao->buscarPorQR($busca);
    } else {
        $equip = $eDao->buscarPorTelefone($busca);
    }

    if ($equip) {
        $ordens = $oDao->buscarPorEquipamento($equip["idequipamento"]);
    } else {
        $erro = "Nenhum equipamento encontrado com " .
                ($tipo === "qr" ? "o QR Code" : "o telefone") .
                " informado.";
    }
}
?>

<div class="row justify-content-center">
  <div class="col-lg-7">

    <!-- Busca -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white fw-semibold">
        <i class="bi bi-search me-2"></i>Buscar Equipamento do Cliente
      </div>
      <div class="card-body">
        <form method="get" action="buscar_equipamento.php">
          <!-- Abas de tipo de busca -->
          <div class="btn-group w-100 mb-3" role="group">
            <a href="buscar_equipamento.php?tipo=telefone"
               class="btn btn-<?php echo $tipo === 'telefone' ? 'primary' : 'outline-primary'; ?>">
              <i class="bi bi-telephone me-1"></i>Por Telefone
            </a>
            <a href="buscar_equipamento.php?tipo=qr"
               class="btn btn-<?php echo $tipo === 'qr' ? 'primary' : 'outline-primary'; ?>">
              <i class="bi bi-qr-code-scan me-1"></i>Por QR Code
            </a>
          </div>

          <input type="hidden" name="tipo" value="<?php echo htmlspecialchars($tipo); ?>">

          <div class="input-group">
            <input type="text" class="form-control form-control-lg" name="busca"
                   value="<?php echo htmlspecialchars($busca); ?>"
                   placeholder="<?php echo $tipo === 'qr' ? 'Ex: AC-A1B2C3D4-123' : '(00) 00000-0000'; ?>"
                   autofocus>
            <button class="btn btn-primary px-4" type="submit">
              <i class="bi bi-search"></i>
            </button>
          </div>
          <?php if ($tipo === 'qr'): ?>
            <div class="form-text">
              <i class="bi bi-info-circle me-1"></i>
              Escaneie o QR Code colado no equipamento e cole o código aqui, ou use um leitor USB.
            </div>
          <?php else: ?>
            <div class="form-text">
              <i class="bi bi-info-circle me-1"></i>
              Digite o telefone do cliente cadastrado no equipamento.
            </div>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Erro -->
    <?php if ($erro): ?>
      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $erro; ?>
      </div>
    <?php endif; ?>

    <!-- Resultado -->
    <?php if ($equip): ?>

      <!-- Dados do equipamento encontrado -->
      <div class="card mb-3">
        <div class="card-header bg-success text-white fw-semibold d-flex justify-content-between align-items-center">
          <span><i class="bi bi-cpu me-2"></i>Equipamento Encontrado</span>
          <span class="badge bg-white text-success">
            <?php echo htmlspecialchars($equip["codigo_qr"]); ?>
          </span>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-sm-6">
              <div class="small text-muted">Cliente</div>
              <div class="fw-semibold"><?php echo htmlspecialchars($equip["nome_cliente"]); ?></div>
            </div>
            <div class="col-sm-6">
              <div class="small text-muted">Telefone</div>
              <div><?php echo htmlspecialchars($equip["telefone"]); ?></div>
            </div>
            <div class="col-sm-6">
              <div class="small text-muted">Endereço</div>
              <div class="small"><?php echo htmlspecialchars($equip["endereco"]); ?></div>
            </div>
            <div class="col-sm-6">
              <div class="small text-muted">Aparelho</div>
              <div class="small"><?php echo htmlspecialchars(trim($equip["marca"] . " " . $equip["modelo"])); ?></div>
            </div>
          </div>

          <!-- Botões de ação -->
          <div class="d-flex gap-2 mt-3 flex-wrap">
            <a href="indexordem.php?equip=<?php echo $equip['idequipamento']; ?>"
               class="btn btn-primary btn-sm">
              <i class="bi bi-clipboard2-plus me-1"></i>Adicionar Novo Serviço
            </a>
            <a href="qrcode.php?id=<?php echo $equip['idequipamento']; ?>"
               class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-qr-code me-1"></i>Imprimir QR Code
            </a>
            <a href="indexequipamento.php?id=<?php echo $equip['idequipamento']; ?>"
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
            <p class="text-muted text-center small mb-0">
              Nenhum serviço registrado para este equipamento ainda.
            </p>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-hover mb-0">
                <thead>
                  <tr>
                    <th>Serviço</th>
                    <th>Data</th>
                    <th>Vencimento</th>
                    <th>Profissional</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($ordens as $o):
                    $dataVenc   = $o["data_vencimento"] ?? null;
                    $trClass    = "";
                    $vencBadge  = "";

                    if ($dataVenc && $o["status"] === "ativo") {
                        $diff = (strtotime($dataVenc) - time()) / 86400;
                        if ($diff < 0) {
                            $trClass   = "table-danger";
                            $vencBadge = "<br><span class='badge bg-danger'>
                                            <i class='bi bi-x-circle me-1'></i>
                                            Vencido há " . abs((int)$diff) . " dias
                                          </span>";
                        } elseif ($diff <= 5) {
                            $trClass   = "table-warning";
                            $vencBadge = "<br><span class='badge bg-warning text-dark'>
                                            <i class='bi bi-exclamation-triangle me-1'></i>
                                            Vence em " . (int)$diff . " dias
                                          </span>";
                        } elseif ($diff <= 30) {
                            $vencBadge = "<br><span class='badge bg-info text-dark'>
                                            <i class='bi bi-clock me-1'></i>
                                            Vence em " . (int)$diff . " dias
                                          </span>";
                        }
                    }

                    $statusBadge = match($o["status"]) {
                        'ativo'     => 'bg-success',
                        'concluido' => 'bg-secondary',
                        'cancelado' => 'bg-danger',
                        default     => 'bg-secondary'
                    };
                  ?>
                  <tr class="<?php echo $trClass; ?>">
                    <td class="fw-semibold"><?php echo htmlspecialchars($o["nome_servico"]); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($o["data_servico"])); ?></td>
                    <td>
                      <?php if ($dataVenc): ?>
                        <?php echo date('d/m/Y', strtotime($dataVenc)); ?>
                        <?php echo $vencBadge; ?>
                      <?php else: ?>
                        <span class="text-muted small">Sem retorno</span>
                      <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($o["nome_profissional"]); ?></td>
                    <td>
                      <span class="badge <?php echo $statusBadge; ?>">
                        <?php echo ucfirst($o["status"]); ?>
                      </span>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
        <?php if (!empty($ordens)): ?>
        <div class="card-footer bg-white text-end">
          <a href="indexordem.php?equip=<?php echo $equip['idequipamento']; ?>"
             class="btn btn-primary btn-sm">
            <i class="bi bi-clipboard2-plus me-1"></i>Adicionar Novo Serviço
          </a>
        </div>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  </div>
</div>

<?php include "rodape.html"; ?>
