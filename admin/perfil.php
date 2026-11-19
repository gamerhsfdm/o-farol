<?php
/**
 * O FAROL - Edição de Perfil do Usuário Logado
 */
declare(strict_types=1);
$admin_title = "Meu perfil";
$admin_breadcrumb = "Meu perfil";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$userId = (int)($_SESSION['usuario_id'] ?? 0);
$sucesso = '';
$erro = '';

// Carrega dados atuais do usuário
$currentUser = null;
if ($pdo && $userId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Tente novamente.';
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha_atual = $_POST['senha_atual'] ?? '';
        $nova_senha = $_POST['nova_senha'] ?? '';
        $confirma_senha = $_POST['confirma_senha'] ?? '';

        if (empty($nome) || empty($email)) {
            $erro = 'Nome e e-mail são obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Formato de e-mail inválido.';
        } else {
            try {
                // Se o usuário deseja alterar a senha
                if (!empty($nova_senha)) {
                    if (empty($senha_atual)) {
                        throw new Exception('Para cadastrar uma nova senha, informe a sua senha atual.');
                    }
                    if (!password_verify($senha_atual, $currentUser['senha'])) {
                        throw new Exception('A senha atual informada está incorreta.');
                    }
                    if (strlen($nova_senha) < 6) {
                        throw new Exception('A nova senha deve ter no mínimo 6 caracteres.');
                    }
                    if ($nova_senha !== $confirma_senha) {
                        throw new Exception('A confirmação da nova senha não confere.');
                    }

                    $novoHash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, senha = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $novoHash, $userId]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ? WHERE id = ?");
                    $stmt->execute([$nome, $email, $userId]);
                }

                // Atualiza sessão
                $_SESSION['usuario_nome'] = $nome;
                $_SESSION['usuario_email'] = $email;
                $currentUser['nome'] = $nome;
                $currentUser['email'] = $email;

                log_audit('editar_perfil', 'usuarios', $userId, "Usuário {$nome} alterou seu próprio perfil");
                $sucesso = 'Perfil atualizado com sucesso!';
            } catch (Exception $e) {
                $erro = $e->getMessage();
            }
        }
    }
}
?>

<div class="mb-6">
  <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / CONFIGURAÇÕES PESSOAIS</div>
  <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Meu perfil</h1>
  <p class="text-xs text-gray-500 mt-1">Gerencie suas informações de acesso e credenciais de segurança.</p>
</div>

<?php if (!empty($sucesso)): ?>
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3 rounded-lg mb-6">
    ✓ <?= $sucesso ?>
  </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 text-xs p-3 rounded-lg mb-6">
    ⚠ <?= e($erro) ?>
  </div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- Formulário Principal (8 Colunas) -->
  <div class="lg:col-span-8 admin-card p-6">
    <form action="<?= BASE_URL ?>/admin/perfil.php" method="POST" class="space-y-6">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div class="border-b border-gray-100 pb-4">
        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-3">Dados de Identificação</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Seu Nome *</label>
            <input type="text" name="nome" required value="<?= e($currentUser['nome'] ?? '') ?>" 
                   class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Seu E-mail *</label>
            <input type="email" name="email" required value="<?= e($currentUser['email'] ?? '') ?>" 
                   class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
          </div>
        </div>
      </div>

      <div>
        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider mb-2">Alteração de Senha</h3>
        <p class="text-[11px] text-gray-400 mb-4">Preencha os campos abaixo apenas se desejar modificar a sua senha atual.</p>

        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Senha Atual</label>
            <input type="password" name="senha_atual" placeholder="••••••••" 
                   class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nova Senha</label>
              <input type="password" name="nova_senha" placeholder="Mínimo 6 dígitos" 
                     class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Confirmar Nova Senha</label>
              <input type="password" name="confirma_senha" placeholder="Repita a nova senha" 
                     class="w-full border border-gray-200 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
            </div>
          </div>
        </div>
      </div>

      <div class="pt-4 border-t border-gray-100 flex items-center justify-end">
        <button type="submit" class="btn-admin-red">
          Salvar Alterações do Perfil
        </button>
      </div>
    </form>
  </div>

  <!-- Cartão de Informações da Conta (4 Colunas) -->
  <div class="lg:col-span-4 admin-card p-6 space-y-4">
    <div class="text-center pb-4 border-b border-gray-100">
      <div class="w-16 h-16 rounded-full bg-[#0e2f33] text-white font-bold text-xl flex items-center justify-center mx-auto mb-3 shadow-sm">
        <?= strtoupper(substr($currentUser['nome'] ?? 'MC', 0, 2)) ?>
      </div>
      <h3 class="text-sm font-bold text-gray-900"><?= e($currentUser['nome'] ?? 'Usuário') ?></h3>
      <span class="text-xs text-gray-500"><?= e($currentUser['email'] ?? '') ?></span>
      <div class="mt-2">
        <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">
          <?= e(ucfirst($currentUser['papel'] ?? 'editor')) ?>
        </span>
      </div>
    </div>

    <div class="text-xs space-y-2 text-gray-500">
      <div class="flex justify-between">
        <span>Conta criada em:</span>
        <span class="text-gray-800"><?= date('d/m/Y', strtotime($currentUser['criado_em'] ?? 'now')) ?></span>
      </div>
      <div class="flex justify-between">
        <span>Último acesso:</span>
        <span class="text-gray-800"><?= $currentUser['ultimo_login'] ? date('d/m/Y H:i', strtotime($currentUser['ultimo_login'])) : 'Sessão atual' ?></span>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
