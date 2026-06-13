<?php
// controller/EquipamentoController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/equipamento.php";
include_once "../model/cliente.php";
include_once "../dao/EquipamentoDao.php";
include_once "../dao/ClienteDao.php";

if (!isset($_SESSION["idusuario"])) { header("location:../login.php"); exit; }
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:../dashboard.php"); exit;
}

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $eDao = new EquipamentoDao();
    $cDao = new ClienteDao();

    if (isset($_GET["id"])) {
        $resultado = $eDao->delete((int)$_GET["id"]);
        $_SESSION["mensagem"]  = "Equipamento excluído com sucesso!";
        $_SESSION["resultado"] = $resultado;
        header("location:../indexequipamento.php");
        exit;
    }

    $isNovo = empty($_POST["txtIdEquipamento"]);
    $idcliente = null;

    if ($isNovo) {
        $tipoCliente = $_POST["tipoCliente"] ?? "novo";
        if ($tipoCliente === "existente" && !empty($_POST["cbClienteExist"])) {
            $equipRef  = $eDao->readId((int)$_POST["cbClienteExist"]);
            $idcliente = $equipRef["idcliente"] ?? null;
            if (!$idcliente) {
                $idcliente = $cDao->buscarOuCriar($_POST["txtNomeCliente"], $_POST["txtTelefone"], $_POST["txtEndereco"]);
            }
        } else {
            $idcliente = $cDao->buscarOuCriar(
                $_POST["txtNomeCliente"] ?? "",
                $_POST["txtTelefone"]   ?? "",
                $_POST["txtEndereco"]   ?? ""
            );
        }
    }

    $e1 = new equipamento(
        $_POST["txtIdEquipamento"] ?? "",
        "",
        $_POST["txtNomeCliente"]   ?? "",
        $_POST["txtEndereco"]      ?? "",
        $_POST["txtTelefone"]      ?? "",
        $_POST["txtModelo"]        ?? "",
        $_POST["txtMarca"]         ?? "",
        $_POST["txtObservacao"]    ?? ""
    );

    if ($isNovo) {
        $novoId = $eDao->createComCliente($e1, $idcliente);
        if ($novoId) {
            $_SESSION["resultado"] = true;
            $_SESSION["mensagem"]  = "Equipamento cadastrado com sucesso!";
            header("location:../qrcode.php?id=$novoId");
        } else {
            $_SESSION["resultado"] = false;
            $_SESSION["mensagem"]  = "Erro ao cadastrar equipamento.";
            header("location:../indexequipamento.php");
        }
    } else {
        $resultado = $eDao->update($e1);
        $_SESSION["mensagem"]  = "Equipamento alterado com sucesso!";
        $_SESSION["resultado"] = $resultado;
        header("location:../indexequipamento.php");
    }
}
