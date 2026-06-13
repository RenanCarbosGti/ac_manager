<?php
// controller/ProfissionalController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/profissional.php";
include_once "../dao/ProfissionalDao.php";

if (!isset($_SESSION["idusuario"])) {
    header("location:../login.php");
    exit;
}
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:../dashboard.php"); exit;
}

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $p1 = new profissional(
        $_POST["txtIdProfissional"] ?? "",
        $_POST["txtNome"]           ?? "",
        $_POST["txtTelefone"]       ?? "",
        $_POST["cbIdUsuario"]       ?? null
    );

    $p1Dao = new ProfissionalDao();

    if (isset($_GET["id"])) {
        $resultado = $p1Dao->delete($_GET["id"]);
        $_SESSION["mensagem"] = "Profissional excluído com sucesso!";
    } elseif (empty($_POST["txtIdProfissional"])) {
        $resultado = $p1Dao->create($p1);
        $_SESSION["mensagem"] = "Profissional cadastrado com sucesso!";
    } else {
        $resultado = $p1Dao->update($p1);
        $_SESSION["mensagem"] = "Profissional alterado com sucesso!";
    }

    $_SESSION["resultado"] = $resultado;
    header("location:../indexprofissional.php");
}
