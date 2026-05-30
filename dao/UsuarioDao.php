<?php
// dao/UsuarioDao.php

class UsuarioDao
{
    public function create(usuario $u)
    {
        try {
            $pdo = conexao::conectar();
            $sql = "INSERT INTO usuario (nome, email, senha, tipo) VALUES (?,?,?,?)";
            $q   = $pdo->prepare($sql);
            $q->execute([$u->nome, $u->email, password_hash($u->senha, PASSWORD_BCRYPT), $u->tipo]);
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
            $sql  = "SELECT * FROM usuario WHERE ativo = 1 ORDER BY nome";
            $res  = $pdo->query($sql);
            $lista = [];
            foreach ($res as $l) {
                $lista[] = new usuario($l['idusuario'], $l['nome'], $l['email'], $l['senha'], $l['tipo'], $l['ativo']);
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
            $q   = $pdo->prepare("SELECT * FROM usuario WHERE idusuario = ?");
            $q->execute([$id]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function buscarPorEmail($email)
    {
        try {
            $pdo = conexao::conectar();
            $q   = $pdo->prepare("SELECT * FROM usuario WHERE email = ? AND ativo = 1");
            $q->execute([$email]);
            $r = $q->fetch(PDO::FETCH_ASSOC);
            conexao::desconectar();
            return $r;
        } catch (PDOException $e) {
            return null;
        }
    }

    public function update(usuario $u)
    {
        try {
            $pdo = conexao::conectar();
            // Só atualiza senha se informada
            if (!empty($u->senha)) {
                $sql = "UPDATE usuario SET nome=?, email=?, senha=?, tipo=? WHERE idusuario=?";
                $q   = $pdo->prepare($sql);
                $q->execute([
                    $u->nome, $u->email,
                    password_hash($u->senha, PASSWORD_BCRYPT),
                    $u->tipo, $u->idusuario
                ]);
            } else {
                $sql = "UPDATE usuario SET nome=?, email=?, tipo=? WHERE idusuario=?";
                $q   = $pdo->prepare($sql);
                $q->execute([$u->nome, $u->email, $u->tipo, $u->idusuario]);
            }
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
            $q   = $pdo->prepare("UPDATE usuario SET ativo = 0 WHERE idusuario = ?");
            $q->execute([$id]);
            conexao::desconectar();
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
}
