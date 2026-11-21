<?php
/**
 * O FAROL - Login no Painel Administrativo CMS
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/ui.php';

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
  <meta name="robots" content="noindex, nofollow">
  <title>Login Administrativo — O FAROL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-fiel.css">
</head>
<body class="admin auth">
  <div class="auth-shell">
    <aside class="auth-aside">
      <div class="row">
        <span class="brand-mark">F</span>
        <div><div class="brand-name">O FAROL</div><div class="brand-sub">Jornal escolar</div></div>
      </div>
      <div class="auth-aside__quote">
        <h2>A redação do Farol em um só lugar.</h2>
        <p>Escreva, revise e publique as reportagens da comunidade escolar com organização e segurança.</p>
      </div>
      <div class="auth-aside__foot">Jornal Escolar Sesc-Senac · Gestão editorial</div>
    </aside>
    <main class="auth-main">
      <div class="auth-card">
        <div class="auth-brand-mobile">
          <span class="brand-mark">F</span>
          <div><div class="brand-name">O FAROL</div><div class="brand-sub">Jornal escolar</div></div>
        </div>
        <h1>Entrar no painel</h1>
        <p class="auth-card__sub">Acesso restrito à equipe editorial. Use seu e-mail institucional.</p>

        <?= ui_alerts('', (string)$erro) ?>

        <form action="<?= BASE_URL ?>/admin/index.php" method="POST" class="stack stack--sm">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <div class="field">
            <label class="label" for="login-email">E-mail administrativo</label>
            <input type="email" id="login-email" name="email" required autofocus autocomplete="username" placeholder="nome@ofarol.sescsenac.edu.br" class="input">
          </div>
          <div class="field">
            <div class="row row--between">
              <label class="label" for="login-senha">Senha</label>
              <a href="<?= BASE_URL ?>/admin/registro.php" class="link text-sm">Criar conta</a>
            </div>
            <input type="password" id="login-senha" name="senha" required autocomplete="current-password" placeholder="••••••••" class="input">
          </div>
          <button type="submit" class="btn btn--primary btn--block" style="height:44px">Entrar no painel</button>
        </form>

        <details class="install-note">
          <summary>Primeiro acesso após a instalação</summary>
          <p style="margin-top:8px">Credencial padrão: <code>admin@ofarol.sescsenac.edu.br</code> / <code>Admin@2026Farol</code></p>
        </details>

        <div class="auth-links">
          <a href="<?= BASE_URL ?>/index.php" class="muted">← Voltar ao portal público</a>
        </div>
      </div>
    </main>
  </div>
<script>
document.addEventListener('submit', function (e) {
  var b = e.target.querySelector('button[type=submit]');
  if (b && !e.defaultPrevented) { setTimeout(function () { b.classList.add('is-loading'); }, 0); }
});
window.addEventListener('pageshow', function () { Array.prototype.forEach.call(document.querySelectorAll('.is-loading'), function (b) { b.classList.remove('is-loading'); }); });
</script>
</body>
</html>
