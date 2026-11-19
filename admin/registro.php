<?php
/**
 * O FAROL - Registro de Novo Membro / Criar Conta
 * Cadastro para novos repórteres e estudantes da redação com papel inicial 'colaborador'
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

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
  <title>Criar Conta — O FAROL</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-fiel.css">
</head>
<body class="bg-[#fafaf8] min-h-screen flex items-center justify-center p-4">

  <div class="max-w-md w-full admin-card p-8 shadow-sm">
    
    <!-- Cabeçalho do Card -->
    <div class="text-center mb-6">
      <div class="w-10 h-10 rounded-lg bg-[#c0262d] text-white font-serif font-black text-xl flex items-center justify-center mx-auto mb-2 shadow-sm">
        F
      </div>
      <h1 class="text-2xl font-editorial-heading font-bold text-[#111827]">Criar Conta no CMS</h1>
      <p class="text-xs text-gray-500 mt-1">Junte-se à equipe editorial de O FAROL</p>
    </div>

    <?php if (!empty($erro)): ?>
      <div class="bg-red-50 border border-red-200 text-red-700 text-xs p-3 rounded mb-4">
        <?= e($erro) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($sucesso)): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-4 rounded mb-4 text-center">
        <p class="font-bold mb-3">✓ <?= $sucesso ?></p>
        <a href="<?= BASE_URL ?>/admin/index.php" class="btn-admin-red inline-block text-xs py-2 px-4">
          Ir para a tela de Login →
        </a>
      </div>
    <?php else: ?>

      <form action="<?= BASE_URL ?>/admin/registro.php" method="POST" class="space-y-4">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome Completo *</label>
          <input type="text" name="nome" required placeholder="Ex: Lucas Mendes" 
                 value="<?= e($_POST['nome'] ?? '') ?>"
                 class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail Institucional *</label>
          <input type="email" name="email" required placeholder="aluno@sescsenac.edu.br" 
                 value="<?= e($_POST['email'] ?? '') ?>"
                 class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Senha (Mínimo de 6 dígitos) *</label>
          <input type="password" name="senha" required placeholder="••••••••" 
                 class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Confirmar Senha *</label>
          <input type="password" name="senha_confirma" required placeholder="••••••••" 
                 class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
        </div>

        <button type="submit" class="w-full btn-admin-red justify-center py-2.5 mt-2 shadow-sm font-bold text-xs uppercase tracking-wider">
          Criar Minha Conta
        </button>
      </form>

    <?php endif; ?>

    <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
      <a href="<?= BASE_URL ?>/admin/index.php" class="text-[#0e2f33] hover:underline font-bold">
        Já tenho conta (Login)
      </a>
      <a href="<?= BASE_URL ?>/index.php" class="text-gray-400 hover:text-black">
        ← Ir para o site
      </a>
    </div>

  </div>

</body>
</html>
