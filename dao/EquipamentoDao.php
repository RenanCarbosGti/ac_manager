<?php
// dao/EquipamentoDao.php

class EquipamentoDao
{
    private function gerarCodigoQR(): string
    {
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
            return $novoId;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Cria equipamento já vinculando ao idcliente
    public function createComCliente(equipamento $e, ?int $idcliente)
    {
        try {
            $pdo    = conexao::conectar();
            $codigo = $this->gerarCodigoQR();
            $sql    = "INSERT INTO equipamento (codigo_qr, idcliente, nome_cliente, endereco, telefone, modelo, marca)
                       VALUES (?,?,?,?,?,?,?)";
            $q      = $pdo->prepare($sql);
            $q->execute([
                $codigo, $idcliente ?: null,
                $e->nome_cliente, $e->endereco, $e->telefone,
                $e->modelo, $e->marca
            ]);
            $novoId = $pdo->lastInsertId();
            conexao::desconectar();
            return $novoId;
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

    public function readId(int $id)
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

    public function buscarPorQR(string $codigo)
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

    public function buscarPorTelefone(string $telefone)
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

    public function buscarPorIdCliente(int $idcliente)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare(
                "SELECT * FROM equipamento WHERE idcliente = ? ORDER BY idequipamento"
            );
            $q->execute([$idcliente]);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarPorCliente(string $nome)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare(
                "SELECT * FROM equipamento WHERE nome_cliente LIKE ? ORDER BY nome_cliente LIMIT 20"
            );
            $q->execute(["%$nome%"]);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    // Retorna clientes únicos via tabela cliente (com FK) ou fallback em equipamento
    public function buscarClientesUnicos()
    {
        try {
            $pdo = conexao::conectar();
            // Tenta usar tabela cliente (após migração)
            $sql = "SELECT c.idcliente, c.nome AS nome_cliente, c.telefone, c.endereco,
                           MIN(e.idequipamento) AS idequipamento
                    FROM cliente c
                    LEFT JOIN equipamento e ON e.idcliente = c.idcliente
                    GROUP BY c.idcliente
                    ORDER BY c.nome";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            // Fallback: se tabela cliente vazia, busca nos equipamentos
            if (empty($r)) {
                $pdo = conexao::conectar();
                $sql = "SELECT MIN(idequipamento) AS idequipamento,
                               nome_cliente, telefone, endereco,
                               NULL AS idcliente
                        FROM equipamento
                        GROUP BY nome_cliente, telefone
                        ORDER BY nome_cliente";
                $res = $pdo->query($sql);
                $r   = $res->fetchAll(PDO::FETCH_ASSOC);
                conexao::desconectar();
            }
            return $r ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function buscarComFiltro(string $nome = '', string $cliente = '')
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

    public function delete(int $id)
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