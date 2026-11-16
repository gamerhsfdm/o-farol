<?php
/**
 * O FAROL - Script de Redefinição Direta da Senha do Administrador
 * Execute acessando: http://localhost/o-farol/reset_admin.php
 */
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';

$pdo = getDBConnection();

if (!$pdo) {
    die("<h3 style='color:red;'>Erro: Não foi possível conectar ao MySQL. Certifique-se de que o MySQL está iniciado no XAMPP e o banco 'o_farol' foi importado.</h3>");
}

$email = 'admin@ofarol.sescsenac.edu.br';
$senha_pura = 'Admin@2026Farol';
$novo_hash = password_hash($senha_pura, PASSWORD_DEFAULT);

try {
    // Verifica se usuário existe
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmtUp = $pdo->prepare("UPDATE usuarios SET senha = ?, ativo = 1, tentativas_falhas = 0, bloqueado_ate = NULL WHERE id = ?");
        $stmtUp->execute([$novo_hash, $user['id']]);
        $msg = "A senha do usuário administrador foi atualizada com sucesso!";
    } else {
        $stmtIn = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha, papel, ativo) VALUES (1, 'Administrador O Farol', ?, ?, 'admin', 1)");
        $stmtIn->execute([$email, $novo_hash]);
        $msg = "Usuário administrador recriado com sucesso!";
    }

    echo "<!DOCTYPE html>
    <html lang='pt-BR'>
    <head>
      <meta charset='UTF-8'>
      <title>Senha Redefinida — O FAROL</title>
      <style>
        body { font-family: sans-serif; background: #f5f3ec; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .box { background: white; padding: 30px; border-radius: 6px; border: 1px solid #e2ded5; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 450px; text-align: center; }
        h2 { color: #164e63; margin-top: 0; }
        p { color: #4b5563; font-size: 14px; line-height: 1.5; }
        .cred { background: #f8fafc; border: 1px solid #cbd5e1; padding: 12px; border-radius: 4px; font-family: monospace; font-size: 13px; margin: 15px 0; text-align: left; }
        .btn { display: inline-block; background: #c0262d; color: white; text-decoration: none; padding: 10px 20px; font-weight: bold; border-radius: 4px; font-size: 13px; text-transform: uppercase; }
        .btn:hover { background: #9e1c22; }
      </style>
    </head>
    <body>
      <div class='box'>
        <h2>✓ {$msg}</h2>
        <p>Você já pode acessar o painel administrativo normalmente com as credenciais abaixo:</p>
        <div class='cred'>
          <strong>E-mail:</strong> admin@ofarol.sescsenac.edu.br<br>
          <strong>Senha:</strong> Admin@2026Farol
        </div>
        <a href='admin/index.php' class='btn'>Ir para a Tela de Login</a>
      </div>
    </body>
    </html>";

} catch (Exception $e) {
    echo "<h3 style='color:red;'>Erro ao atualizar senha: " . htmlspecialchars($e->getMessage()) . "</h3>";
}
