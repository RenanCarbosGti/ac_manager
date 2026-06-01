<?php
// config/conexao.php
// Padrão idêntico ao projeto_oo: classe estática com conectar() e desconectar()

class conexao
{
    private static $dbHost = "sql302.infinityfree.com";
    private static $dbName = "if0_42065046_XXX";
    private static $dbUser = "if0_42065046";
    private static $dbPass = "Luiza2023Renan";

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
