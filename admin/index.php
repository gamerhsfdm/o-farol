<?php
/**
 * O FAROL - Login no Painel Administrativo CMS
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

// Se já estiver logado, redireciona ao dashboard
if (is_logged_in()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

$erro = '';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } elseif (empty($email) || empty($senha)) {
        $erro = 'Informe o e-mail e a senha de acesso.';
    } elseif (!$pdo) {
        $erro = 'Banco de dados inacessível no momento.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && $user['ativo'] && password_verify($senha, $user['senha'])) {
                // Login com sucesso
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario_nome'] = $user['nome'];
                $_SESSION['usuario_email'] = $user['email'];
                $_SESSION['usuario_papel'] = $user['papel'];

                // Atualiza último login e zera falhas
                $stmtUp = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW(), tentativas_falhas = 0 WHERE id = ?");
                $stmtUp->execute([$user['id']]);

                log_audit('login', 'usuarios', (int)$user['id'], 'Login efetuado com sucesso');

                header("Location: " . BASE_URL . "/admin/dashboard.php");
                exit;
            } else {
                $erro = 'Credenciais de acesso incorretas ou conta inativa.';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao processar autenticação: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Administrativo — O FAROL</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-[#f5f3ec] min-h-screen flex items-center justify-center p-4">

  <div class="max-w-md w-full newspaper-paper p-8 shadow-md">
    
    <!-- Cabeçalho do Card de Login -->
    <div class="text-center mb-6">
      <span class="tag-editorial text-[9px] mb-2">ACESSO RESTRITO</span>
      <h1 class="text-3xl font-serif font-extrabold text-[#111827] mt-1">O FAROL</h1>
      <p class="text-xs text-gray-600 mt-1">Gestão Editorial • Jornal Escolar Sesc-Senac</p>
    </div>

    <?php if (!empty($erro)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-xs p-3 rounded mb-4">
        <?= e($erro) ?>
      </div>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>/admin/index.php" method="POST" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail Administrativo</label>
        <input type="email" name="email" required placeholder="admin@ofarol.sescsenac.edu.br" 
               class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#164e63]">
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <label class="block text-xs font-bold text-gray-700 uppercase">Senha</label>
          <a href="<?= BASE_URL ?>/admin/registro.php" class="text-[11px] text-[#c0262d] hover:underline font-bold">
            Criar conta
          </a>
        </div>
        <input type="password" name="senha" required placeholder="••••••••" 
               class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#164e63]">
      </div>

      <button type="submit" class="w-full bg-[#164e63] hover:bg-[#0e3a4b] text-white font-bold text-xs uppercase tracking-wider py-3 rounded transition shadow">
        ENTRAR NO PAINEL CMS
      </button>
    </form>

    <div class="mt-6 pt-4 border-t border-gray-200 text-center text-xs text-gray-500">
      <p>Credencial padrão de instalação:</p>
      <code class="text-[11px] bg-gray-100 p-1 rounded text-gray-800">admin@ofarol.sescsenac.edu.br</code> / <code class="text-[11px] bg-gray-100 p-1 rounded text-gray-800">Admin@2026Farol</code>
    </div>

    <div class="mt-4 text-center">
      <a href="<?= BASE_URL ?>/index.php" class="text-xs text-[#b91c1c] hover:underline font-bold">
        ← Voltar ao Portal Público
      </a>
    </div>

  </div>

</body>
</html>
