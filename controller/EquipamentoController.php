<?php
// controller/EquipamentoController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/equipamento.php";
include_once "../dao/EquipamentoDao.php";

if (!isset($_SESSION["idusuario"])) {
    header("location:../login.php");
    exit;
}

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $e1 = new equipamento(
        $_POST["txtIdEquipamento"] ?? "",
        "",  // codigo_qr gerado automaticamente
        $_POST["txtNomeCliente"]   ?? "",
        $_POST["txtEndereco"]      ?? "",
        $_POST["txtTelefone"]      ?? "",
        $_POST["txtModelo"]        ?? "",
        $_POST["txtMarca"]         ?? ""
    );

    $eDao = new EquipamentoDao();

    if (isset($_GET["id"])) {
        $resultado = $eDao->delete($_GET["id"]);
        $_SESSION["mensagem"] = "Equipamento excluído com sucesso!";
        $_SESSION["resultado"] = $resultado;
        header("location:../indexequipamento.php");

    } elseif (empty($_POST["txtIdEquipamento"])) {
        // Novo equipamento: gera QR Code
        $novoId = $eDao->create($e1);
        if ($novoId) {
            $_SESSION["resultado"] = true;
            $_SESSION["mensagem"]  = "Equipamento cadastrado com sucesso!";
            // Redireciona para impressão do QR Code
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
