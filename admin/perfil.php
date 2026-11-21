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

<?= ui_page_header('Meu perfil', 'Gerencie suas informações de acesso e credenciais de segurança.') ?>

<?= ui_alerts(strip_tags((string)$sucesso), (string)$erro) ?>

<div class="split" style="grid-template-columns: minmax(0, 1fr) 340px">
  <form action="<?= BASE_URL ?>/admin/perfil.php" method="POST" class="card">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

    <div class="form-section">
      <div class="form-section__title">Dados de identificação</div>
      <div class="form-section__sub">Como você aparece na equipe do jornal.</div>
      <div class="form-grid">
        <div class="field">
          <label class="label" for="pf-nome">Seu nome *</label>
          <input type="text" id="pf-nome" name="nome" required value="<?= e($currentUser['nome'] ?? '') ?>" class="input">
        </div>
        <div class="field">
          <label class="label" for="pf-email">Seu e-mail *</label>
          <input type="email" id="pf-email" name="email" required value="<?= e($currentUser['email'] ?? '') ?>" class="input">
        </div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section__title">Alteração de senha</div>
      <div class="form-section__sub">Preencha apenas se quiser trocar a senha atual.</div>
      <div class="stack stack--sm">
        <div class="field">
          <label class="label" for="pf-atual">Senha atual</label>
          <input type="password" id="pf-atual" name="senha_atual" placeholder="••••••••" autocomplete="current-password" class="input">
        </div>
        <div class="form-grid">
          <div class="field">
            <label class="label" for="pf-nova">Nova senha</label>
            <input type="password" id="pf-nova" name="nova_senha" placeholder="Mínimo de 6 caracteres" autocomplete="new-password" class="input">
          </div>
          <div class="field">
            <label class="label" for="pf-conf">Confirmar nova senha</label>
            <input type="password" id="pf-conf" name="confirma_senha" placeholder="Repita a nova senha" autocomplete="new-password" class="input">
          </div>
        </div>
      </div>
    </div>

    <div class="card__foot" style="justify-content:flex-end">
      <button type="submit" class="btn btn--primary">Salvar alterações do perfil</button>
    </div>
  </form>

  <div class="card">
    <div class="card__body" style="text-align:center">
      <span class="avatar avatar--lg"><?= e(ui_initials($currentUser['nome'] ?? '')) ?></span>
      <h3 class="strong" style="font-size:16px;margin-top:14px"><?= e($currentUser['nome'] ?? 'Usuário') ?></h3>
      <p class="muted text-sm"><?= e($currentUser['email'] ?? '') ?></p>
      <div style="margin-top:12px"><?= ui_badge(ui_role_label($currentUser['papel'] ?? 'editor'), 'brand') ?></div>
    </div>
    <div class="card__body" style="border-top:1px solid var(--line)">
      <dl class="kv" style="margin:0">
        <div><span>Conta criada em</span><span><?= e(ui_date($currentUser['criado_em'] ?? null, false)) ?></span></div>
        <div><span>Último acesso</span><span><?= !empty($currentUser['ultimo_login']) ? e(ui_date($currentUser['ultimo_login'])) : 'Sessão atual' ?></span></div>
      </dl>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
