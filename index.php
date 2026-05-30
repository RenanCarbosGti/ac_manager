<?php
// Ponto de entrada: redireciona para login ou dashboard
session_start();
if (isset($_SESSION["idusuario"])) {
    header("location:dashboard.php");
} else {
    header("location:login.php");
}
exit;
