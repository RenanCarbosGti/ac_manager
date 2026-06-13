<?php
// controller/FinanceiroController.php
session_start();

include_once "../config/conexao.php";
include_once "../model/financeiro.php";
include_once "../dao/FinanceiroDao.php";
include_once "../dao/MaterialDao.php";

if (!isset($_SESSION["idusuario"])) { header("location:../login.php"); exit; }
if (!in_array($_SESSION["tipo"] ?? "", ["admin","profissional"])) {
    header("location:../dashboard.php"); exit;
}

if (isset($_POST["btGravar"]) || isset($_GET["id"])) {

    $fDao = new FinanceiroDao();
    $mDao = new MaterialDao();

    if (isset($_GET["id"])) {
        $resultado = $fDao->delete($_GET["id"]);
        $_SESSION["mensagem"] = "Lançamento excluído com sucesso!";
    } else {
        $f1 = new financeiro(
            $_POST["txtIdFinanceiro"] ?? "",
            $_POST["cbTipo"]          ?? "saida",
            $_POST["txtDescricao"]    ?? "",
            $_POST["txtValor"]        ?? 0,
            $_POST["txtData"]         ?? date('Y-m-d'),
            $_POST["cbIdOrdem"]       ?? null,
            $_POST["cbCategoria"]     ?? "outros",
            $_POST["txtObservacoes"]  ?? ""
        );

        if (empty($_POST["txtIdFinanceiro"])) {
            $resultado = $fDao->create($f1);

            // Se for saída e tiver materiais informados, dá baixa no estoque
            if ($resultado && $f1->tipo === "saida") {
                $matIds  = $_POST["mat_id"]  ?? [];
                $matQtds = $_POST["mat_qtd"] ?? [];
                $idOrdem = $f1->idordem ?: null;
                $data    = $f1->data_lancamento;

                foreach ($matIds as $i => $idMat) {
                    if (!empty($idMat) && !empty($matQtds[$i]) && $matQtds[$i] > 0) {
                        $mDao->registrarMovimentacao(
                            (int)$idMat,
                            'saida',
                            (float)$matQtds[$i],
                            "Saída vinculada ao lançamento financeiro: " . htmlspecialchars($f1->descricao),
                            $idOrdem,
                            $data
                        );
                    }
                }
            }

            $_SESSION["mensagem"] = "Lançamento cadastrado com sucesso!";
        } else {
            $resultado = $fDao->update($f1);
            $_SESSION["mensagem"] = "Lançamento alterado com sucesso!";
        }
    }

    $_SESSION["resultado"] = $resultado;
    header("location:../indexfinanceiro.php");
}
