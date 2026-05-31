<?php
// dao/MaterialDao.php

class MaterialDao
{
    public function create(material $m)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO material (nome, descricao, unidade, estoque_atual, estoque_minimo, preco_custo)
                    VALUES (?,?,?,?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([$m->nome, $m->descricao, $m->unidade, $m->estoque_atual, $m->estoque_minimo, $m->preco_custo]);
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
            $sql  = "SELECT * FROM material ORDER BY nome";
            $res  = $pdo->query($sql);
            $lista = [];
            foreach ($res as $l) {
                $lista[] = new material(
                    $l['idmaterial'], $l['nome'], $l['descricao'],
                    $l['unidade'], $l['estoque_atual'], $l['estoque_minimo'], $l['preco_custo']
                );
            }
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
            $q   = $pdo->prepare("SELECT * FROM material WHERE idmaterial = ?");
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Materiais abaixo do estoque mínimo
    public function buscarAbaixoMinimo()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM material WHERE estoque_atual <= estoque_minimo ORDER BY nome";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarComFiltro($nome = '')
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare("SELECT * FROM material WHERE nome LIKE ? ORDER BY nome");
            $q->execute(["%$nome%"]);
            $lista = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $lista[] = new material(
                    $l['idmaterial'], $l['nome'], $l['descricao'],
                    $l['unidade'], $l['estoque_atual'], $l['estoque_minimo'], $l['preco_custo']
                );
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(material $m)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE material SET nome=?, descricao=?, unidade=?,
                    estoque_minimo=?, preco_custo=? WHERE idmaterial=?";
            $q   = $pdo->prepare($sql);
            $q->execute([$m->nome, $m->descricao, $m->unidade,
                         $m->estoque_minimo, $m->preco_custo, $m->idmaterial]);
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
            $q   = $pdo->prepare("DELETE FROM material WHERE idmaterial = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // ── Movimentações ────────────────────────────────────────

    public function registrarMovimentacao($idmaterial, $tipo, $quantidade, $motivo, $idordem, $data)
    {
        try {
            $pdo = conexao::conectar();

            // Insere movimentação
            $sql = "INSERT INTO estoque_movimentacao (idmaterial, tipo, quantidade, motivo, idordem, data_movimentacao)
                    VALUES (?,?,?,?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([$idmaterial, $tipo, $quantidade, $motivo, $idordem ?: null, $data]);

            // Atualiza estoque_atual
            if ($tipo === 'entrada') {
                $op = "estoque_atual + ?";
            } else {
                $op = "GREATEST(estoque_atual - ?, 0)";
            }
            $q2 = $pdo->prepare("UPDATE material SET estoque_atual = $op WHERE idmaterial = ?");
            $q2->execute([$quantidade, $idmaterial]);

            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function buscarMovimentacoes($idmaterial = null)
    {
        try {
            $pdo   = conexao::conectar();
            $where = $idmaterial ? "WHERE em.idmaterial = $idmaterial" : "";
            $sql   = "SELECT em.*, m.nome AS nome_material, m.unidade
                      FROM estoque_movimentacao em
                      JOIN material m ON em.idmaterial = m.idmaterial
                      $where
                      ORDER BY em.data_movimentacao DESC, em.criado_em DESC
                      LIMIT 100";
            $res   = $pdo->query($sql);
            $r     = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }
}
