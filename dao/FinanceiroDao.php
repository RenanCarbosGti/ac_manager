<?php
// dao/FinanceiroDao.php

class FinanceiroDao
{
    public function create(financeiro $f)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO financeiro (tipo, descricao, valor, data_lancamento, idordem, categoria, observacoes)
                    VALUES (?,?,?,?,?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([
                $f->tipo, $f->descricao, $f->valor,
                $f->data_lancamento, $f->idordem ?: null,
                $f->categoria, $f->observacoes
            ]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function read()
    {
        try {
            $pdo  = conexao::conectar();
            $sql  = "SELECT f.*, os.idordem as num_ordem
                     FROM financeiro f
                     LEFT JOIN ordem_servico os ON f.idordem = os.idordem
                     ORDER BY f.data_lancamento DESC, f.criado_em DESC";
            $res  = $pdo->query($sql);
            $lista = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $lista;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function readId($id)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare("SELECT * FROM financeiro WHERE idfinanceiro = ?");
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(financeiro $f)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE financeiro SET tipo=?, descricao=?, valor=?, data_lancamento=?,
                    idordem=?, categoria=?, observacoes=? WHERE idfinanceiro=?";
            $q   = $pdo->prepare($sql);
            $q->execute([
                $f->tipo, $f->descricao, $f->valor,
                $f->data_lancamento, $f->idordem ?: null,
                $f->categoria, $f->observacoes, $f->idfinanceiro
            ]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare("DELETE FROM financeiro WHERE idfinanceiro = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Resumo do mês atual
    public function resumoMes($mes = null, $ano = null)
    {
        $mes = $mes ?? date('m');
        $ano = $ano ?? date('Y');
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT
                        SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) AS total_entradas,
                        SUM(CASE WHEN tipo='saida'   THEN valor ELSE 0 END) AS total_saidas,
                        COUNT(*) AS total_lancamentos
                    FROM financeiro
                    WHERE MONTH(data_lancamento) = ? AND YEAR(data_lancamento) = ?";
            $q   = $pdo->prepare($sql);
            $q->execute([$mes, $ano]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Evolução mensal dos últimos 6 meses
    public function evolucaoMensal()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT
                        DATE_FORMAT(data_lancamento, '%m/%Y') AS mes,
                        SUM(CASE WHEN tipo='entrada' THEN valor ELSE 0 END) AS entradas,
                        SUM(CASE WHEN tipo='saida'   THEN valor ELSE 0 END) AS saidas
                    FROM financeiro
                    WHERE data_lancamento >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                    GROUP BY YEAR(data_lancamento), MONTH(data_lancamento)
                    ORDER BY YEAR(data_lancamento), MONTH(data_lancamento)";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Receita por tipo de serviço
    public function receitaPorServico()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT s.nome AS servico,
                           SUM(os.preco_cobrado) AS total
                    FROM ordem_servico os
                    JOIN servico s ON os.idservico = s.idservico
                    WHERE YEAR(os.data_servico) = YEAR(CURDATE())
                    GROUP BY s.idservico
                    ORDER BY total DESC";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Receita de materiais (saídas categoria=material com valor registrado)
    public function receitaMateriais()
    {
        try {
            $pdo = conexao::conectar();
            // Entradas de categoria material = venda de material
            $sql = "SELECT SUM(valor) AS total
                    FROM financeiro
                    WHERE tipo = 'entrada'
                      AND categoria = 'material'
                      AND YEAR(data_lancamento) = YEAR(CURDATE())";
            $res = $pdo->query($sql);
            $r   = $res->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return (float)($r["total"] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Receita total de serviços (ordens de serviço) no mês
    public function receitaServicosTotal()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT SUM(preco_cobrado) AS total
                    FROM ordem_servico
                    WHERE MONTH(data_servico) = MONTH(CURDATE())
                      AND YEAR(data_servico)  = YEAR(CURDATE())
                      AND status != 'cancelado'";
            $res = $pdo->query($sql);
            $r   = $res->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return (float)($r["total"] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    // Receita por profissional
    public function receitaPorProfissional()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT p.nome AS profissional,
                           SUM(os.preco_cobrado) AS total,
                           COUNT(*) AS qtd_ordens
                    FROM ordem_servico os
                    JOIN profissional p ON os.idprofissional = p.idprofissional
                    WHERE YEAR(os.data_servico) = YEAR(CURDATE())
                    GROUP BY p.idprofissional
                    ORDER BY total DESC";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarComFiltro($tipo = '', $categoria = '', $dataIni = '', $dataFim = '')
    {
        try {
            $pdo    = conexao::conectar();
            $where  = ["1=1"];
            $params = [];

            if (!empty($tipo)) {
                $where[] = "f.tipo = ?";
                $params[] = $tipo;
            }
            if (!empty($categoria)) {
                $where[] = "f.categoria = ?";
                $params[] = $categoria;
            }
            if (!empty($dataIni)) {
                $where[] = "f.data_lancamento >= ?";
                $params[] = $dataIni;
            }
            if (!empty($dataFim)) {
                $where[] = "f.data_lancamento <= ?";
                $params[] = $dataFim;
            }

            $sql = "SELECT f.* FROM financeiro f WHERE " . implode(' AND ', $where) .
                   " ORDER BY f.data_lancamento DESC";
            $q   = $pdo->prepare($sql);
            $q->execute($params);
            $r   = $q->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }
}
