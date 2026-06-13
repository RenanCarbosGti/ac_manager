<?php
// controller/ServicoController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/servico.php";
include_once "../dao/ServicoDao.php";

if (!isset($_SESSION["idusuario"])) {
    header("location:../login.php");
    exit;
}
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:../dashboard.php"); exit;
}

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $validade = !empty($_POST["txtValidade"]) ? (int)$_POST["txtValidade"] : null;

    $s1 = new servico(
        $_POST["txtIdServico"] ?? "",
        $_POST["txtNome"]      ?? "",
        $_POST["txtDescricao"] ?? "",
        $validade,
        $_POST["txtPreco"]     ?? 0
    );

    $sDao = new ServicoDao();

    if (isset($_GET["id"])) {
        $resultado = $sDao->delete($_GET["id"]);
        $_SESSION["mensagem"] = "Serviço excluído com sucesso!";
    } elseif (empty($_POST["txtIdServico"])) {
        $resultado = $sDao->create($s1);
        $_SESSION["mensagem"] = "Serviço cadastrado com sucesso!";
    } else {
        $resultado = $sDao->update($s1);
        $_SESSION["mensagem"] = "Serviço alterado com sucesso!";
    }

    $_SESSION["resultado"] = $resultado;
    header("location:../indexservico.php");
}
