<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include_once "../config/conexao.php";
include_once "../model/usuario.php";
include_once "../dao/UsuarioDao.php";

/* LOGIN */
if (isset($_POST["btEntrar"])) {

    $email = trim($_POST["txtEmail"]);
    $senha = trim($_POST["txtSenha"]);

    $dao = new UsuarioDao();
    $usuario = $dao->buscarPorEmail($email);

    if ($usuario) {

        // TEMPORÁRIO PARA TESTES
        // depois trocamos para password_verify()
        if ($senha == "123456" || $senha == "admin123") {

            $_SESSION["idusuario"] = $usuario["idusuario"];
            $_SESSION["nome"]      = $usuario["nome"];
            $_SESSION["tipo"]      = $usuario["tipo"];

            header("Location: ../dashboard.php");
            exit;
        }
    }

    $_SESSION["erro_login"] = "E-mail ou senha inválidos.";
    header("Location: ../login.php");
    exit;
}

/* LOGOUT */
if (isset($_GET["sair"])) {

    $_SESSION = array();

    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit;
}