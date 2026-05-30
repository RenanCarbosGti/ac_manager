<?php
// dao/EquipamentoDao.php

class EquipamentoDao
{
    private function gerarCodigoQR(): string
    {
        // Código único: AC + timestamp + random
        return 'AC-' . strtoupper(substr(uniqid('', true), -8)) . '-' . rand(100, 999);
    }

    public function create(equipamento $e)
    {
        try {
            $pdo    = conexao::conectar();
            $codigo = $this->gerarCodigoQR();
            $sql    = "INSERT INTO equipamento (codigo_qr, nome_cliente, endereco, telefone, modelo, marca)
                       VALUES (?,?,?,?,?,?)";
            $q      = $pdo->prepare($sql);
            $q->execute([$codigo, $e->nome_cliente, $e->endereco, $e->telefone, $e->modelo, $e->marca]);
            $novoId = $pdo->lastInsertId();
            conexao::desconectar();
            return $novoId; // Retorna o ID para geração do QR Code
        } catch (PDOException $e) {
            return false;
        }
    }

    public function read()
    {
        try {
            $pdo   = conexao::conectar();
            $sql   = "SELECT * FROM equipamento ORDER BY nome_cliente";
            $res   = $pdo->query($sql);
            $lista = [];
            foreach ($res as $l) {
                $lista[] = new equipamento(
                    $l['idequipamento'], $l['codigo_qr'], $l['nome_cliente'],
                    $l['endereco'],      $l['telefone'],  $l['modelo'], $l['marca']
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
            $q   = $pdo->prepare("SELECT * FROM equipamento WHERE idequipamento = ?");
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarPorQR($codigo)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare("SELECT * FROM equipamento WHERE codigo_qr = ?");
            $q->execute([$codigo]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarPorTelefone($telefone)
    {
        try {
            // Remove formatação para comparar só os dígitos
            $digits = preg_replace('/\D/', '', $telefone);
            $pdo = conexao::conectar();
            $q   = $pdo->prepare(
                "SELECT * FROM equipamento
                 WHERE REGEXP_REPLACE(telefone, '[^0-9]', '') LIKE ?
                 ORDER BY nome_cliente LIMIT 1"
            );
            $q->execute(["%$digits%"]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarComFiltro($nome = '', $cliente = '')
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT * FROM equipamento WHERE nome_cliente LIKE ? AND (modelo LIKE ? OR marca LIKE ?) ORDER BY nome_cliente";
            $q   = $pdo->prepare($sql);
            $q->execute(["%$cliente%", "%$nome%", "%$nome%"]);
            $lista = [];
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $l) {
                $lista[] = new equipamento(
                    $l['idequipamento'], $l['codigo_qr'], $l['nome_cliente'],
                    $l['endereco'],      $l['telefone'],  $l['modelo'], $l['marca']
                );
            }
            conexao::desconectar();
            return $lista;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(equipamento $e)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE equipamento SET nome_cliente=?, endereco=?, telefone=?, modelo=?, marca=?
                    WHERE idequipamento=?";
            $q   = $pdo->prepare($sql);
            $q->execute([
                $e->nome_cliente, $e->endereco, $e->telefone,
                $e->modelo, $e->marca, $e->idequipamento
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
            $q   = $pdo->prepare("DELETE FROM equipamento WHERE idequipamento = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
