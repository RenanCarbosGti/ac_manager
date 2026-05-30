<?php
// dao/ServicoDao.php

class ServicoDao
{
    public function create(servico $s)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO servico (nome, descricao, validade_dias, preco) VALUES (?,?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([$s->nome, $s->descricao, $s->validade_dias ?: null, $s->preco]);
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
            $sql  = "SELECT * FROM servico ORDER BY nome";
            $res  = $pdo->query($sql);
            $lista = [];
            foreach ($res as $l) {
                $lista[] = new servico($l['idservico'], $l['nome'], $l['descricao'], $l['validade_dias'], $l['preco']);
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
            $q   = $pdo->prepare("SELECT * FROM servico WHERE idservico = ?");
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
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
            $sql = "SELECT * FROM servico WHERE nome LIKE ? ORDER BY nome";
            $q   = $pdo->prepare($sql);
            $q->execute(["%$nome%"]);
            $lista = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $lista[] = new servico($l['idservico'], $l['nome'], $l['descricao'], $l['validade_dias'], $l['preco']);
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(servico $s)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE servico SET nome=?, descricao=?, validade_dias=?, preco=? WHERE idservico=?";
            $q   = $pdo->prepare($sql);
            $q->execute([$s->nome, $s->descricao, $s->validade_dias ?: null, $s->preco, $s->idservico]);
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
            $q   = $pdo->prepare("DELETE FROM servico WHERE idservico = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
