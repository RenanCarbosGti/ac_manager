<?php
// dao/ProfissionalDao.php

class ProfissionalDao
{
    public function create(profissional $p)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO profissional (nome, telefone, idusuario) VALUES (?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([$p->nome, $p->telefone, $p->idusuario ?: null]);
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
            $sql  = "SELECT * FROM profissional ORDER BY nome";
            $res  = $pdo->query($sql);
            $lista = [];
            foreach ($res as $l) {
                $lista[] = new profissional($l['idprofissional'], $l['nome'], $l['telefone'], $l['idusuario']);
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
            $q   = $pdo->prepare("SELECT * FROM profissional WHERE idprofissional = ?");
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
            $sql = "SELECT * FROM profissional WHERE nome LIKE ? ORDER BY nome";
            $q   = $pdo->prepare($sql);
            $q->execute(["%$nome%"]);
            $lista = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $lista[] = new profissional($l['idprofissional'], $l['nome'], $l['telefone'], $l['idusuario']);
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(profissional $p)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE profissional SET nome=?, telefone=?, idusuario=? WHERE idprofissional=?";
            $q   = $pdo->prepare($sql);
            $q->execute([$p->nome, $p->telefone, $p->idusuario ?: null, $p->idprofissional]);
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
            $q   = $pdo->prepare("DELETE FROM profissional WHERE idprofissional = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
