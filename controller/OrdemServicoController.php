<?php
// controller/OrdemServicoController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/ordemservico.php";
include_once "../model/servico.php";
include_once "../model/financeiro.php";
include_once "../model/equipamento.php";
include_once "../dao/OrdemServicoDao.php";
include_once "../dao/ServicoDao.php";
include_once "../dao/FinanceiroDao.php";
include_once "../dao/EquipamentoDao.php";

if (!isset($_SESSION["idusuario"])) { header("location:../login.php"); exit; }

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $oDao = new OrdemServicoDao();
    $sDao = new ServicoDao();
    $fDao = new FinanceiroDao();
    $eDao = new EquipamentoDao();

    if (isset($_GET["id"])) {
        $resultado = $oDao->delete($_GET["id"]);
        $_SESSION["mensagem"]  = "Ordem de serviço excluída com sucesso!";
        $_SESSION["resultado"] = $resultado;
        header("location:../indexordem.php");
        exit;
    }

    // Calcular data de vencimento
    $idservico   = (int)($_POST["cbIdServico"] ?? 0);
    $dataServico = $_POST["txtDataServico"] ?? date('Y-m-d');
    $servico     = $sDao->readId($idservico);
    $dataVenc    = null;

    if ($servico && !empty($servico["validade_dias"])) {
        $dataVenc = date('Y-m-d', strtotime($dataServico . " +" . $servico["validade_dias"] . " days"));
    }

    $isNova = empty($_POST["txtIdOrdem"]);

    $o1 = new ordemservico(
        $_POST["txtIdOrdem"]       ?? "",
        $_POST["cbIdEquipamento"]  ?? "",
        $idservico,
        $_POST["cbIdProfissional"] ?? "",
        $dataServico,
        $dataVenc,
        $_POST["txtPreco"]         ?? 0,
        $_POST["txtObservacoes"]   ?? "",
        $_POST["cbStatus"]         ?? "ativo"
    );

    if ($isNova) {
        $resultado = $oDao->create($o1);

        if ($resultado) {
            // Busca ID da ordem recém-criada e dados para descrição
            $pdo    = conexao::conectar();
            $idOrdem = $pdo->lastInsertId();
            conexao::desconectar();

            $equip      = $eDao->readId($o1->idequipamento);
            $nomeCliente = $equip ? $equip["nome_cliente"] : "Cliente";
            $nomeServico = $servico ? $servico["nome"]     : "Serviço";

            // Lança entrada automática no financeiro
            $f = new financeiro(
                "",
                "entrada",
                "OS #{$idOrdem} – {$nomeServico} – {$nomeCliente}",
                $o1->preco_cobrado,
                $dataServico,
                $idOrdem,
                "servico",
                "Entrada automática gerada ao cadastrar a ordem de serviço."
            );
            $fDao->create($f);
        }

        $_SESSION["mensagem"] = "Ordem de serviço cadastrada! Entrada financeira gerada automaticamente.";
    } else {
        $resultado = $oDao->update($o1);
        $_SESSION["mensagem"] = "Ordem de serviço alterada com sucesso!";
    }

    $_SESSION["resultado"] = $resultado;
    header("location:../indexordem.php");
}
