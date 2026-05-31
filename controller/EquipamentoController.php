<?php
// controller/EquipamentoController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/equipamento.php";
include_once "../model/cliente.php";
include_once "../dao/EquipamentoDao.php";
include_once "../dao/ClienteDao.php";

if (!isset($_SESSION["idusuario"])) { header("location:../login.php"); exit; }

if ((isset($_POST["btGravar"])) || (isset($_GET["id"]))) {

    $eDao = new EquipamentoDao();
    $cDao = new ClienteDao();

    // Excluir
    if (isset($_GET["id"])) {
        $resultado = $eDao->delete($_GET["id"]);
        $_SESSION["mensagem"]  = "Equipamento excluído com sucesso!";
        $_SESSION["resultado"] = $resultado;
        header("location:../indexequipamento.php");
        exit;
    }

    $isNovo = empty($_POST["txtIdEquipamento"]);

    // Determinar idcliente
    $idcliente = null;
    if ($isNovo) {
        $tipoCliente = $_POST["tipoCliente"] ?? "novo";
        if ($tipoCliente === "existente" && !empty($_POST["cbClienteExist"])) {
            // Busca o cliente pelo equipamento selecionado para pegar o idcliente
            $equipRef = $eDao->readId((int)$_POST["cbClienteExist"]);
            $idcliente = $equipRef["idcliente"] ?? null;
            // Se não tiver idcliente ainda (banco antigo), busca ou cria pelo nome/telefone
            if (!$idcliente) {
                $idcliente = $cDao->buscarOuCriar(
                    $_POST["txtNomeCliente"],
                    $_POST["txtTelefone"],
                    $_POST["txtEndereco"]
                );
            }
        } else {
            // Novo cliente: busca ou cria para evitar duplicatas
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
        $_POST["txtMarca"]         ?? ""
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
