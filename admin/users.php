<?php
/**
 * O FAROL - Gestão de Equipe e Permissões de Usuários do Sistema
 */
declare(strict_types=1);
$admin_title = "Equipe e permissões";
$admin_breadcrumb = "Equipe e permissões";
require_once __DIR__ . '/header.php';

// Apenas Administradores e Editores-Chefe podem gerenciar usuários
require_login(['admin', 'editor_chefe']);

$pdo = getDBConnection();
$sucesso = '';
$erro = '';

// Processamento de Ações: Criar Usuário, Alternar Status e Excluir
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = 'Sessão expirada. Tente novamente.';
    } else {
        $acao = $_POST['acao'] ?? '';

        if ($acao === 'salvar_usuario') {
            $id = (int)($_POST['id'] ?? 0);
            $nome = trim($_POST['nome'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $papel = $_POST['papel'] ?? 'editor';
            $senha = $_POST['senha'] ?? '';
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if (empty($nome) || empty($email)) {
                $erro = 'Nome e e-mail são obrigatórios.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Formato de e-mail inválido.';
            } elseif ($id === 0 && (empty($senha) || strlen($senha) < 6)) {
                $erro = 'Para novos usuários, a senha deve ter pelo menos 6 caracteres.';
            } else {
                try {
                    if ($id > 0) {
                        // Edição de usuário existente
                        if (!empty($senha)) {
                            if (strlen($senha) < 6) {
                                throw new Exception('A nova senha deve ter no mínimo 6 caracteres.');
                            }
                            $hash = password_hash($senha, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, papel = ?, ativo = ?, senha = ? WHERE id = ?");
                            $stmt->execute([$nome, $email, $papel, $ativo, $hash, $id]);
                        } else {
                            $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, papel = ?, ativo = ? WHERE id = ?");
                            $stmt->execute([$nome, $email, $papel, $ativo, $id]);
                        }
                        log_audit('editar_usuario', 'usuarios', $id, "Usuário {$nome} atualizado");
                        $sucesso = "Usuário <strong>{$nome}</strong> atualizado com sucesso!";
                    } else {
                        // Novo usuário
                        $stmtCheck = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
                        $stmtCheck->execute([$email]);
                        if ($stmtCheck->fetch()) {
                            $erro = 'Este endereço de e-mail já está cadastrado no sistema.';
                        } else {
                            $hash = password_hash($senha, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, papel, ativo) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$nome, $email, $hash, $papel, $ativo]);
                            $novoId = (int)$pdo->lastInsertId();
                            log_audit('criar_usuario', 'usuarios', $novoId, "Novo usuário {$nome} criado com papel {$papel}");
                            $sucesso = "Novo usuário <strong>{$nome}</strong> cadastrado com sucesso!";
                        }
                    }
                } catch (Exception $e) {
                    $erro = 'Erro: ' . $e->getMessage();
                }
            }
        } elseif ($acao === 'toggle_status') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
                $erro = 'Você não pode desativar seu próprio usuário em uso.';
            } else {
                $stmt = $pdo->prepare("UPDATE usuarios SET ativo = NOT ativo WHERE id = ?");
                $stmt->execute([$id]);
                $sucesso = "Status do usuário atualizado!";
            }
        } elseif ($acao === 'excluir') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id === (int)($_SESSION['usuario_id'] ?? 0)) {
                $erro = 'Você não pode excluir sua própria conta enquanto estiver logado.';
            } else {
                $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $stmt->execute([$id]);
                log_audit('excluir_usuario', 'usuarios', $id, "Usuário excluído do sistema");
                $sucesso = "Usuário excluído com sucesso!";
            }
        }
    }
}

// Lista usuários
$usuarios = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY id ASC");
        $usuarios = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Papéis disponíveis com descrições amigáveis
$papeis = [
    'admin' => ['nome' => 'Administrador', 'desc' => 'Acesso total a todas as configurações e equipe', 'cor' => 'bg-red-50 text-red-700 border-red-200'],
    'editor_chefe' => ['nome' => 'Editor-chefe', 'desc' => 'Aprova, agenda e publica conteúdos da redação', 'cor' => 'bg-amber-50 text-amber-700 border-amber-200'],
    'editor' => ['nome' => 'Editor', 'desc' => 'Escreve, formata e edita matérias e mídias', 'cor' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'colaborador' => ['nome' => 'Colaborador', 'desc' => 'Envia sugestões e rascunhos para revisão', 'cor' => 'bg-sky-50 text-sky-700 border-sky-200']
];
?>

<?= ui_page_header('Equipe e permissões', 'Gerencie os acessos administrativos, credenciais e níveis editoriais do jornal.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-novo-usuario">' . ui_icon('user-plus') . ' Novo membro da equipe</button>') ?>

<?= ui_alerts(strip_tags((string)$sucesso), (string)$erro) ?>

<?php $tons_papel = ['admin' => 'accent', 'editor_chefe' => 'warn', 'editor' => 'ok', 'colaborador' => 'info']; ?>

<!-- Níveis de acesso -->
<div class="cols cols--4 mb-6">
  <?php foreach ($papeis as $chave => $info): ?>
    <div class="card" style="padding:16px 18px">
      <?= ui_badge($info['nome'], $tons_papel[$chave] ?? 'neutral') ?>
      <p class="muted text-sm" style="margin-top:10px"><?= e($info['desc']) ?></p>
    </div>
  <?php endforeach; ?>
</div>

<div class="card card--flush">
  <div class="card__head">
    <div>
      <div class="card__title">Membros com acesso ao CMS</div>
      <div class="card__sub"><?= count($usuarios) ?> cadastrado<?= count($usuarios) === 1 ? '' : 's' ?></div>
    </div>
  </div>

  <?php if (empty($usuarios)): ?>
    <?= ui_empty('users', 'Nenhum membro encontrado', 'Cadastre os membros da equipe que terão acesso ao painel.') ?>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl" style="min-width:820px">
        <thead>
          <tr><th>Usuário</th><th>E-mail</th><th>Papel</th><th>Status</th><th>Último login</th><th class="t-right">Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
            <?php $eu = ((int)$u['id'] === (int)($_SESSION['usuario_id'] ?? 0)); ?>
            <tr>
              <td>
                <div class="cell">
                  <span class="avatar"><?= e(ui_initials($u['nome'])) ?></span>
                  <div>
                    <div class="cell__title"><?= e($u['nome']) ?> <?php if ($eu): ?><?= ui_badge('Você', 'brand') ?><?php endif; ?></div>
                    <div class="cell__meta">Cadastrado em <?= e(ui_date($u['criado_em'] ?? null, false)) ?></div>
                  </div>
                </div>
              </td>
              <td class="mono"><?= e($u['email']) ?></td>
              <td><?= ui_badge($papeis[$u['papel']]['nome'] ?? ucfirst((string)$u['papel']), $tons_papel[$u['papel']] ?? 'neutral') ?></td>
              <td><?= $u['ativo'] ? ui_badge('Ativo', 'ok') : ui_badge('Inativo', 'bad') ?></td>
              <td class="t-nowrap muted"><?= $u['ultimo_login'] ? e(ui_date($u['ultimo_login'])) : 'Nunca acessou' ?></td>
              <td class="t-right t-nowrap">
                <span class="row-actions">
                  <button type="button" class="btn btn--sm btn--ghost" data-edit-user="<?= e((string)json_encode(['id' => (int)$u['id'], 'nome' => $u['nome'], 'email' => $u['email'], 'papel' => $u['papel'], 'ativo' => (int)$u['ativo']], JSON_UNESCAPED_UNICODE)) ?>">Editar</button>
                  <?php if (!$eu): ?>
                    <form action="<?= BASE_URL ?>/admin/users.php" method="POST" style="display:inline">
                      <input type="hidden" name="acao" value="toggle_status">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <button type="submit" class="btn btn--sm btn--ghost"><?= $u['ativo'] ? 'Desativar' : 'Ativar' ?></button>
                    </form>
                    <form action="<?= BASE_URL ?>/admin/users.php" method="POST" style="display:inline"
                          data-confirm="O usuário <?= e($u['nome']) ?> será excluído permanentemente." data-confirm-title="Excluir usuário?" data-confirm-ok="Excluir">
                      <input type="hidden" name="acao" value="excluir">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                      <button type="submit" class="icon-btn icon-btn--sm icon-btn--danger" title="Excluir" aria-label="Excluir <?= e($u['nome']) ?>"><?= ui_icon('trash') ?></button>
                    </form>
                  <?php endif; ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: cadastro / edição -->
<div id="modal-novo-usuario" class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-titulo">
  <form action="<?= BASE_URL ?>/admin/users.php" method="POST" class="modal__panel">
    <input type="hidden" name="acao" value="salvar_usuario">
    <input type="hidden" name="id" id="modal-id" value="0">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="modal-titulo">Novo membro da equipe</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    <div class="modal__body">
      <div class="field">
        <label class="label" for="modal-nome">Nome completo *</label>
        <input type="text" name="nome" id="modal-nome" required placeholder="Ex: Marina Costa" class="input">
      </div>
      <div class="field">
        <label class="label" for="modal-email">E-mail institucional *</label>
        <input type="email" name="email" id="modal-email" required placeholder="nome@ofarol.sescsenac.edu.br" class="input">
      </div>
      <div class="form-grid">
        <div class="field">
          <label class="label" for="modal-papel">Papel / nível *</label>
          <select name="papel" id="modal-papel" class="select">
            <option value="editor">Editor</option>
            <option value="editor_chefe">Editor-chefe</option>
            <option value="colaborador">Colaborador</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
        <div class="field">
          <span class="label">Status</span>
          <label class="check" style="min-height:40px"><input type="checkbox" name="ativo" id="modal-ativo" value="1" checked> Conta ativa</label>
        </div>
      </div>
      <div class="field">
        <label class="label" for="modal-senha" id="label-senha">Senha de acesso *</label>
        <input type="password" name="senha" id="modal-senha" placeholder="Mínimo de 6 caracteres" autocomplete="new-password" class="input">
        <span class="hint" id="hint-senha" hidden>Deixe em branco para manter a senha atual.</span>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" class="btn btn--primary">Salvar membro</button>
    </div>
  </form>
</div>

<script>
(function () {
  var modal = document.getElementById('modal-novo-usuario');
  var f = function (id) { return document.getElementById(id); };

  function modoNovo() {
    f('modal-titulo').textContent = 'Novo membro da equipe';
    f('modal-id').value = '0';
    f('modal-nome').value = ''; f('modal-email').value = ''; f('modal-senha').value = '';
    f('modal-papel').value = 'editor'; f('modal-ativo').checked = true;
    f('modal-senha').required = true;
    f('label-senha').textContent = 'Senha de acesso *';
    f('modal-senha').placeholder = 'Mínimo de 6 caracteres';
    f('hint-senha').hidden = true;
  }
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-edit-user]');
    if (!b) { return; }
    var u = JSON.parse(b.getAttribute('data-edit-user'));
    f('modal-titulo').textContent = 'Editar membro: ' + u.nome;
    f('modal-id').value = u.id; f('modal-nome').value = u.nome; f('modal-email').value = u.email;
    f('modal-papel').value = u.papel; f('modal-ativo').checked = (u.ativo == 1);
    f('modal-senha').value = ''; f('modal-senha').required = false;
    f('label-senha').textContent = 'Nova senha (opcional)';
    f('modal-senha').placeholder = '••••••••';
    f('hint-senha').hidden = false;
    window.farolModal.open('modal-novo-usuario');
  });
  modal.addEventListener('modal:close', modoNovo);
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
