<?php
// controller/LoginController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/usuario.php";
include_once "../dao/UsuarioDao.php";

if (isset($_POST["btEntrar"])) {
    $email = trim($_POST["txtEmail"]);
    $senha = trim($_POST["txtSenha"]);

    $dao     = new UsuarioDao();
    $usuario = $dao->buscarPorEmail($email);

    if ($usuario && password_verify($senha, $usuario["senha"]) && $usuario["ativo"] == 1) {
        $_SESSION["idusuario"] = $usuario["idusuario"];
        $_SESSION["nome"]      = $usuario["nome"];
        $_SESSION["tipo"]      = $usuario["tipo"];
        header("location:../dashboard.php");
    } else {
        $_SESSION["erro_login"] = "E-mail ou senha inválidos.";
        header("location:../login.php");
    }
}

if (isset($_GET["sair"])) {
    session_destroy();
    header("location:../login.php");
}
