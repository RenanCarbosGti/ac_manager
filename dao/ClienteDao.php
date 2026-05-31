<?php
// dao/ClienteDao.php

class ClienteDao
{
    public function create(cliente $c)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO cliente (nome, telefone, endereco) VALUES (?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([$c->nome, $c->telefone, $c->endereco]);
            $id  = $pdo->lastInsertId();
            conexao::desconectar();
            return $id; // retorna o ID para vincular ao equipamento
        } catch (PDOException $e) {
            return false;
        }
    }

    public function read()
    {
        try {
            $pdo  = conexao::conectar();
            $res  = $pdo->query("SELECT * FROM cliente ORDER BY nome");
            $lista = [];
            foreach ($res as $l) {
                $lista[] = new cliente($l['idcliente'], $l['nome'], $l['telefone'], $l['endereco']);
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function readId($id)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare("SELECT * FROM cliente WHERE idcliente = ?");
            $q->execute([$id]);
            $r   = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Busca cliente por nome+telefone para evitar duplicata
    public function buscarExistente($nome, $telefone)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare(
                "SELECT * FROM cliente WHERE nome = ? AND telefone = ? LIMIT 1"
            );
            $q->execute([$nome, $telefone]);
            $r   = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Retorna ou cria o cliente — evita duplicatas
    public function buscarOuCriar($nome, $telefone, $endereco): int
    {
        $exist = $this->buscarExistente($nome, $telefone);
        if ($exist) return (int)$exist['idcliente'];
        $c = new cliente(null, $nome, $telefone, $endereco);
        return (int)$this->create($c);
    }
}
