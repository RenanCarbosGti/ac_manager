<?php
// controller/MaterialController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/material.php";
include_once "../dao/MaterialDao.php";

if (!isset($_SESSION["idusuario"])) { header("location:../login.php"); exit; }

$mDao = new MaterialDao();

// Excluir
if (isset($_GET["id"])) {
    $resultado = $mDao->delete($_GET["id"]);
    $_SESSION["mensagem"] = "Material excluído com sucesso!";
    $_SESSION["resultado"] = $resultado;
    header("location:../indexmaterial.php");
    exit;
}

// Movimentação de estoque
if (isset($_POST["btMovimentar"])) {
    $resultado = $mDao->registrarMovimentacao(
        $_POST["cbIdMaterial"],
        $_POST["cbTipoMov"],
        $_POST["txtQuantidade"],
        $_POST["txtMotivo"],
        $_POST["cbIdOrdem"] ?? null,
        $_POST["txtDataMov"] ?? date('Y-m-d')
    );
    $_SESSION["mensagem"] = $resultado
        ? "Movimentação registrada com sucesso!"
        : "Erro ao registrar movimentação.";
    $_SESSION["resultado"] = $resultado;
    header("location:../indexmaterial.php");
    exit;
}

// Gravar material (novo ou editar)
if (isset($_POST["btGravar"])) {
    $m1 = new material(
        $_POST["txtIdMaterial"]   ?? "",
        $_POST["txtNome"]         ?? "",
        $_POST["txtDescricao"]    ?? "",
        $_POST["txtUnidade"]      ?? "un",
        $_POST["txtEstoqueAtual"] ?? 0,
        $_POST["txtEstoqueMin"]   ?? 1,
        $_POST["txtPrecoCusto"]   ?? 0
    );

    if (empty($_POST["txtIdMaterial"])) {
        // Na criação, registra como entrada inicial no estoque
        $resultado = $mDao->create($m1);
        if ($resultado && $m1->estoque_atual > 0) {
            // Busca o id recém-criado
            $pdo = conexao::conectar();
            $id  = $pdo->lastInsertId();
            conexao::desconectar();
            $mDao->registrarMovimentacao($id, 'entrada', $m1->estoque_atual, 'Estoque inicial', null, date('Y-m-d'));
        }
        $_SESSION["mensagem"] = "Material cadastrado com sucesso!";
    } else {
        $resultado = $mDao->update($m1);
        $_SESSION["mensagem"] = "Material alterado com sucesso!";
    }

    $_SESSION["resultado"] = $resultado;
    header("location:../indexmaterial.php");
}
