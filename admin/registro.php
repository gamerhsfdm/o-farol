<?php
/**
 * O FAROL - Registro de Novo Membro / Criar Conta
 * Cadastro para novos repórteres e estudantes da redação com papel inicial 'colaborador'
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/ui.php';

$erro = '';
$sucesso = '';
$pdo = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($csrf)) {
        $erro = 'Sessão expirada. Tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $senha_confirma = $_POST['senha_confirma'] ?? '';

        if (empty($nome) || empty($email) || empty($senha)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Por favor, informe um endereço de e-mail válido.';
        } elseif (strlen($senha) < 6) {
            $erro = 'A senha deve conter no mínimo 6 caracteres.';
        } elseif ($senha !== $senha_confirma) {
            $erro = 'As senhas digitadas não coincidem.';
        } elseif (!$pdo) {
            $erro = 'Banco de dados temporariamente inacessível.';
        } else {
            try {
                // Verifica se e-mail já existe
                $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmtCheck->execute([$email]);

                if ($stmtCheck->fetch()) {
                    $erro = 'Este endereço de e-mail já possui cadastro. Faça login ou use outro.';
                } else {
                    $hash = password_hash($senha, PASSWORD_DEFAULT);
                    $papel = 'colaborador'; // Novos registros entram como colaboradores por padrão seguro
                    $stmtIn = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, 1)");
                    $stmtIn->execute([$nome, $email, $hash, $papel]);
                    $novoId = (int)$pdo->lastInsertId();

                    log_audit('registro_conta', 'usuarios', $novoId, "Conta criada pelo formulário de cadastro");

                    $sucesso = 'Sua conta foi criada com sucesso! Você já pode fazer login.';
                }
            } catch (Exception $e) {
                $erro = 'Erro ao processar o cadastro: ' . $e->getMessage();
            }
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
  <title>Criar Conta — O FAROL</title>
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
        <h1>Criar conta no CMS</h1>
        <p class="auth-card__sub">Junte-se à equipe editorial de O FAROL. Novas contas entram como colaborador.</p>

        <?= ui_alerts('', (string)$erro) ?>

        <?php if (!empty($sucesso)): ?>
          <div class="alert alert--ok" role="status"><?= ui_icon('check-c') ?><div class="alert__text"><?= e(strip_tags((string)$sucesso)) ?></div></div>
          <a href="<?= BASE_URL ?>/admin/index.php" class="btn btn--primary btn--block" style="height:44px">Ir para o login</a>
        <?php else: ?>
          <form action="<?= BASE_URL ?>/admin/registro.php" method="POST" class="stack stack--sm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="field">
              <label class="label" for="rg-nome">Nome completo *</label>
              <input type="text" id="rg-nome" name="nome" required placeholder="Ex: Lucas Mendes" value="<?= e($_POST['nome'] ?? '') ?>" class="input">
            </div>
            <div class="field">
              <label class="label" for="rg-email">E-mail institucional *</label>
              <input type="email" id="rg-email" name="email" required placeholder="aluno@sescsenac.edu.br" value="<?= e($_POST['email'] ?? '') ?>" class="input">
            </div>
            <div class="form-grid">
              <div class="field">
                <label class="label" for="rg-senha">Senha *</label>
                <input type="password" id="rg-senha" name="senha" required placeholder="Mínimo de 6 caracteres" autocomplete="new-password" class="input">
              </div>
              <div class="field">
                <label class="label" for="rg-conf">Confirmar senha *</label>
                <input type="password" id="rg-conf" name="senha_confirma" required placeholder="Repita a senha" autocomplete="new-password" class="input">
              </div>
            </div>
            <button type="submit" class="btn btn--primary btn--block" style="height:44px">Criar minha conta</button>
          </form>
        <?php endif; ?>

        <div class="auth-links">
          <a href="<?= BASE_URL ?>/admin/index.php" class="link">Já tenho conta (login)</a>
          <a href="<?= BASE_URL ?>/index.php" class="muted">← Ir para o site</a>
        </div>
      </div>
    </main>
  </div>
<script>
document.addEventListener('submit', function (e) {
  var b = e.target.querySelector('button[type=submit]');
  if (b && !e.defaultPrevented) { setTimeout(function () { b.classList.add('is-loading'); }, 0); }
});
</script>
</body>
</html>
