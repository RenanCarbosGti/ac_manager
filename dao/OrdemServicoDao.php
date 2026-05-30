<?php
// dao/OrdemServicoDao.php

class OrdemServicoDao
{
    public function create(ordemservico $o)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO ordem_servico
                        (idequipamento, idservico, idprofissional, data_servico, data_vencimento, preco_cobrado, observacoes, status)
                    VALUES (?,?,?,?,?,?,?,?)";
            $q = $pdo->prepare($sql);
            $q->execute([
                $o->idequipamento, $o->idservico, $o->idprofissional,
                $o->data_servico,  $o->data_vencimento, $o->preco_cobrado,
                $o->observacoes,   $o->status
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
            $sql  = "SELECT os.*, e.nome_cliente, e.telefone AS tel_cliente, e.codigo_qr,
                            s.nome AS nome_servico, s.validade_dias,
                            p.nome AS nome_profissional
                     FROM ordem_servico os
                     JOIN equipamento  e ON os.idequipamento  = e.idequipamento
                     JOIN servico      s ON os.idservico      = s.idservico
                     JOIN profissional p ON os.idprofissional = p.idprofissional
                     ORDER BY os.data_servico DESC";
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
            $sql = "SELECT os.*, e.nome_cliente, e.endereco, e.telefone AS tel_cliente, e.codigo_qr,
                           e.modelo, e.marca,
                           s.nome AS nome_servico, s.validade_dias,
                           p.nome AS nome_profissional
                    FROM ordem_servico os
                    JOIN equipamento  e ON os.idequipamento  = e.idequipamento
                    JOIN servico      s ON os.idservico      = s.idservico
                    JOIN profissional p ON os.idprofissional = p.idprofissional
                    WHERE os.idordem = ?";
            $q = $pdo->prepare($sql);
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Ordens que vencem nos próximos $dias dias
    public function buscarAlertas($dias = 5)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT os.*, e.nome_cliente, e.telefone AS tel_cliente, e.codigo_qr,
                           s.nome AS nome_servico,
                           p.nome AS nome_profissional,
                           DATEDIFF(os.data_vencimento, CURDATE()) AS dias_restantes
                    FROM ordem_servico os
                    JOIN equipamento  e ON os.idequipamento  = e.idequipamento
                    JOIN servico      s ON os.idservico      = s.idservico
                    JOIN profissional p ON os.idprofissional = p.idprofissional
                    WHERE os.data_vencimento IS NOT NULL
                      AND os.status = 'ativo'
                      AND DATEDIFF(os.data_vencimento, CURDATE()) BETWEEN 0 AND ?
                    ORDER BY os.data_vencimento ASC";
            $q = $pdo->prepare($sql);
            $q->execute([$dias]);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    // Ordens já vencidas
    public function buscarVencidas()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT os.*, e.nome_cliente, e.telefone AS tel_cliente,
                           s.nome AS nome_servico,
                           p.nome AS nome_profissional,
                           DATEDIFF(CURDATE(), os.data_vencimento) AS dias_atraso
                    FROM ordem_servico os
                    JOIN equipamento  e ON os.idequipamento  = e.idequipamento
                    JOIN servico      s ON os.idservico      = s.idservico
                    JOIN profissional p ON os.idprofissional = p.idprofissional
                    WHERE os.data_vencimento IS NOT NULL
                      AND os.status = 'ativo'
                      AND os.data_vencimento < CURDATE()
                    ORDER BY os.data_vencimento ASC";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarPorEquipamento($idequipamento)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT os.*, s.nome AS nome_servico, p.nome AS nome_profissional
                    FROM ordem_servico os
                    JOIN servico      s ON os.idservico      = s.idservico
                    JOIN profissional p ON os.idprofissional = p.idprofissional
                    WHERE os.idequipamento = ?
                    ORDER BY os.data_servico DESC";
            $q = $pdo->prepare($sql);
            $q->execute([$idequipamento]);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarComFiltro($cliente = '', $servico = '', $profissional = '', $status = '')
    {
        try {
            $pdo    = conexao::conectar();
            $where  = ["1=1"];
            $params = [];

            if (!empty($cliente)) {
                $where[]  = "e.nome_cliente LIKE ?";
                $params[] = "%$cliente%";
            }
            if (!empty($servico)) {
                $where[]  = "s.nome LIKE ?";
                $params[] = "%$servico%";
            }
            if (!empty($profissional)) {
                $where[]  = "p.nome LIKE ?";
                $params[] = "%$profissional%";
            }
            if (!empty($status)) {
                $where[]  = "os.status = ?";
                $params[] = $status;
            }

            $sql = "SELECT os.*, e.nome_cliente, e.telefone AS tel_cliente, e.codigo_qr,
                           s.nome AS nome_servico,
                           p.nome AS nome_profissional
                    FROM ordem_servico os
                    JOIN equipamento  e ON os.idequipamento  = e.idequipamento
                    JOIN servico      s ON os.idservico      = s.idservico
                    JOIN profissional p ON os.idprofissional = p.idprofissional
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY os.data_servico DESC";
            $q = $pdo->prepare($sql);
            $q->execute($params);
            $r = $q->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(ordemservico $o)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "UPDATE ordem_servico
                    SET idequipamento=?, idservico=?, idprofissional=?,
                        data_servico=?, data_vencimento=?, preco_cobrado=?,
                        observacoes=?, status=?
                    WHERE idordem=?";
            $q = $pdo->prepare($sql);
            $q->execute([
                $o->idequipamento, $o->idservico, $o->idprofissional,
                $o->data_servico,  $o->data_vencimento, $o->preco_cobrado,
                $o->observacoes,   $o->status, $o->idordem
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
            $q   = $pdo->prepare("DELETE FROM ordem_servico WHERE idordem = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function totalPorStatus()
    {
        try {
            $pdo = conexao::conectar();
            $sql = "SELECT status, COUNT(*) as total FROM ordem_servico GROUP BY status";
            $res = $pdo->query($sql);
            $r   = $res->fetchAll(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }
}
