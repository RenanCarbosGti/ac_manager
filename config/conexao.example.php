<?php
// config/conexao.example.php
// COPIE este arquivo para conexao.php e preencha com seus dados locais.

class conexao
{
    private static $dbName = "ac_manager";   // nome do banco
    private static $dbHost = "localhost";
    private static $dbUser = "root";
    private static $dbPass = "";             // XAMPP padrão: vazio | MySQL installer: sua senha
    private static $con    = null;

    public static function conectar()
    {
        if (self::$con == null) {
            try {
                self::$con = new PDO(
                    "mysql:host=" . self::$dbHost . ";dbname=" . self::$dbName . ";charset=utf8mb4",
                    self::$dbUser,
                    self::$dbPass
                );
                self::$con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch (PDOException $exception) {
                die("Erro de conexão: " . $exception->getMessage());
            }
        }
        return self::$con;
    }

    public static function desconectar()
    {
        self::$con = null;
    }
}
