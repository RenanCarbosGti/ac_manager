<?php
// setup.php — RODE ESTE ARQUIVO UMA ÚNICA VEZ PELO NAVEGADOR APÓS IMPORTAR O BANCO
// Acesse: http://localhost/Renan/ac_manager/setup.php
// APAGUE OU RENOMEIE este arquivo após usar!

include_once "config/conexao.php";

$senha = "admin123";
$hash  = password_hash($senha, PASSWORD_BCRYPT);

try {
    $pdo = conexao::conectar();

    // Remove admin anterior se existir e recria com hash correto
    $pdo->exec("DELETE FROM usuario WHERE email = 'admin@acmanager.com'");
    $q = $pdo->prepare(
        "INSERT INTO usuario (nome, email, senha, tipo) VALUES (?, ?, ?, ?)"
    );
    $q->execute(['Administrador', 'admin@acmanager.com', $hash, 'admin']);

    echo "<h2 style='font-family:sans-serif; color:green'>✅ Setup concluído!</h2>";
    echo "<p style='font-family:sans-serif'>Usuário criado com sucesso:</p>";
    echo "<ul style='font-family:sans-serif'>";
    echo "<li><strong>E-mail:</strong> admin@acmanager.com</li>";
    echo "<li><strong>Senha:</strong> admin123</li>";
    echo "</ul>";
    echo "<p style='font-family:sans-serif; color:red'><strong>⚠️ APAGUE o arquivo setup.php agora!</strong></p>";
    echo "<a href='login.php' style='font-family:sans-serif'>Ir para o Login →</a>";

} catch (PDOException $e) {
    echo "<h2 style='font-family:sans-serif; color:red'>❌ Erro</h2>";
    echo "<p style='font-family:sans-serif'>" . $e->getMessage() . "</p>";
    echo "<p>Verifique se o banco foi importado corretamente.</p>";
}
?>
