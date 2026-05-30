<?php
// controller/OrdemServicoController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/ordemservico.php";
include_once "../model/servico.php";
include_once "../dao/OrdemServicoDao.php";
include_once "../dao/ServicoDao.php";

if (!isset($_SESSION["idusuario"])) {
    header("location:../login.php");
    exit;
}

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $oDao = new OrdemServicoDao();
    $sDao = new ServicoDao();

    if (isset($_GET["id"])) {
        $resultado = $oDao->delete($_GET["id"]);
        $_SESSION["mensagem"] = "Ordem de serviço excluída com sucesso!";
        $_SESSION["resultado"] = $resultado;
        header("location:../indexordem.php");
        exit;
    }

    // Calcular data de vencimento com base na validade do serviço
    $idservico   = (int)($_POST["cbIdServico"] ?? 0);
    $dataServico = $_POST["txtDataServico"] ?? date('Y-m-d');
    $servico     = $sDao->readId($idservico);
    $dataVenc    = null;

    if ($servico && !empty($servico["validade_dias"])) {
        $dataVenc = date('Y-m-d', strtotime($dataServico . " +" . $servico["validade_dias"] . " days"));
    }

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

    if (empty($_POST["txtIdOrdem"])) {
        $resultado = $oDao->create($o1);
        $_SESSION["mensagem"] = "Ordem de serviço cadastrada com sucesso!";
    } else {
        $resultado = $oDao->update($o1);
        $_SESSION["mensagem"] = "Ordem de serviço alterada com sucesso!";
    }

    $_SESSION["resultado"] = $resultado;
    header("location:../indexordem.php");
}
