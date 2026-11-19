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

<!-- TOPO: TÍTULO + BOTÃO NOVO USUÁRIO -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Equipe e permissões</h1>
    <p class="text-xs text-gray-500 mt-1">Gerencie os acessos administrativos, credenciais e níveis editoriais do jornal.</p>
  </div>
  <button onclick="document.getElementById('modal-novo-usuario').classList.remove('hidden');" class="btn-admin-red">
    + Novo membro da equipe
  </button>
</div>

<?php if (!empty($sucesso)): ?>
  <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs p-3 rounded-lg mb-6 flex items-center justify-between">
    <span>✓ <?= $sucesso ?></span>
    <button onclick="this.parentElement.remove();" class="text-emerald-500 hover:text-emerald-900">&times;</button>
  </div>
<?php endif; ?>

<?php if (!empty($erro)): ?>
  <div class="bg-red-50 border border-red-200 text-red-700 text-xs p-3 rounded-lg mb-6 flex items-center justify-between">
    <span>⚠ <?= e($erro) ?></span>
    <button onclick="this.parentElement.remove();" class="text-red-500 hover:text-red-900">&times;</button>
  </div>
<?php endif; ?>

<!-- NÍVEIS DE ACESSO INFORMATIVOS -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <?php foreach ($papeis as $chave => $info): ?>
    <div class="admin-card p-4">
      <span class="text-[10px] uppercase font-bold px-2 py-0.5 rounded border <?= $info['cor'] ?>">
        <?= $info['nome'] ?>
      </span>
      <p class="text-xs text-gray-500 mt-2 leading-relaxed"><?= $info['desc'] ?></p>
    </div>
  <?php endforeach; ?>
</div>

<!-- TABELA DE USUÁRIOS -->
<div class="admin-card overflow-hidden">
  <div class="p-4 border-b border-[#f0eee9] flex items-center justify-between">
    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Membros com Acesso ao CMS (<?= count($usuarios) ?>)</h3>
    <span class="text-[11px] text-gray-400">Total cadastrado no banco de dados</span>
  </div>

  <table class="w-full text-left text-xs">
    <thead>
      <tr class="border-b border-[#f0eee9] text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-[#fafaf8]">
        <th class="p-3.5">USUÁRIO</th>
        <th class="p-3.5">E-MAIL</th>
        <th class="p-3.5">PAPEL / NÍVEL</th>
        <th class="p-3.5">STATUS</th>
        <th class="p-3.5">ÚLTIMO LOGIN</th>
        <th class="p-3.5 text-right">AÇÕES</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-[#f7f6f2]">
      <?php foreach ($usuarios as $u): ?>
        <tr class="hover:bg-[#fafaf8] transition-colors">
          <td class="p-3.5">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-full bg-[#0e2f33] text-white font-bold text-xs flex items-center justify-center flex-shrink-0">
                <?= strtoupper(substr($u['nome'], 0, 2)) ?>
              </div>
              <div>
                <div class="font-bold text-gray-900"><?= e($u['nome']) ?></div>
                <div class="text-[10px] text-gray-400">Cadastrado em <?= date('d/m/Y', strtotime($u['criado_em'])) ?></div>
              </div>
            </div>
          </td>
          
          <td class="p-3.5 text-gray-700 font-mono"><?= e($u['email']) ?></td>

          <td class="p-3.5">
            <?php $pInfo = $papeis[$u['papel']] ?? ['nome' => ucfirst($u['papel']), 'cor' => 'bg-gray-100 text-gray-700 border-gray-200']; ?>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded border <?= $pInfo['cor'] ?>">
              <?= $pInfo['nome'] ?>
            </span>
          </td>

          <td class="p-3.5">
            <?php if ($u['ativo']): ?>
              <span class="badge-status badge-status-published">Ativo</span>
            <?php else: ?>
              <span class="badge-status bg-red-100 text-red-700">Inativo</span>
            <?php endif; ?>
          </td>

          <td class="p-3.5 text-gray-500 font-sans">
            <?= $u['ultimo_login'] ? date('d/m/Y \à\s H:i', strtotime($u['ultimo_login'])) : 'Nunca acessou' ?>
          </td>

          <td class="p-3.5 text-right space-x-2">
            <!-- Botão Editar -->
            <button onclick='abrirModalEditar(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>);' class="text-[#0e2f33] hover:underline font-bold text-xs">
              Editar
            </button>

            <!-- Alternar Status (Ativar/Desativar) -->
            <?php if ($u['id'] !== (int)($_SESSION['usuario_id'] ?? 0)): ?>
              <form action="<?= BASE_URL ?>/admin/users.php" method="POST" class="inline-block">
                <input type="hidden" name="acao" value="toggle_status">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="text-gray-500 hover:text-black text-xs font-semibold">
                  <?= $u['ativo'] ? 'Desativar' : 'Ativar' ?>
                </button>
              </form>

              <!-- Excluir -->
              <form action="<?= BASE_URL ?>/admin/users.php" method="POST" class="inline-block" onsubmit="return confirm('Tem certeza que deseja excluir o usuário <?= e($u['nome']) ?>?');">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="text-red-600 hover:underline text-xs font-semibold">
                  Excluir
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- ========================================================
     MODAL DE CADASTRO / EDIÇÃO DE USUÁRIO
     ======================================================== -->
<div id="modal-novo-usuario" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
  <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-[#f0eee9]">
    
    <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
      <h3 id="modal-titulo" class="text-base font-editorial-heading font-bold text-gray-900">Novo Membro da Equipe</h3>
      <button onclick="fecharModal();" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
    </div>

    <form action="<?= BASE_URL ?>/admin/users.php" method="POST" class="space-y-4">
      <input type="hidden" name="acao" value="salvar_usuario">
      <input type="hidden" name="id" id="modal-id" value="0">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome Completo *</label>
        <input type="text" name="nome" id="modal-nome" required placeholder="Ex: Marina Costa" 
               class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail Institucional *</label>
        <input type="email" name="email" id="modal-email" required placeholder="marina.costa@ofarol.sescsenac.edu.br" 
               class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Papel / Nível *</label>
          <select name="papel" id="modal-papel" class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none">
            <option value="editor">Editor</option>
            <option value="editor_chefe">Editor-chefe</option>
            <option value="colaborador">Colaborador</option>
            <option value="admin">Administrador</option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Status</label>
          <label class="flex items-center gap-2 mt-2 cursor-pointer text-xs">
            <input type="checkbox" name="ativo" id="modal-ativo" value="1" checked class="rounded text-[#0e2f33]">
            <span>Conta ativa</span>
          </label>
        </div>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1" id="label-senha">Senha de Acesso *</label>
        <input type="password" name="senha" id="modal-senha" placeholder="Mínimo de 6 caracteres" 
               class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0e2f33]">
        <span id="hint-senha" class="text-[10px] text-gray-400 mt-1 hidden">Deixe em branco para manter a senha atual.</span>
      </div>

      <div class="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
        <button type="button" onclick="fecharModal();" class="px-4 py-2 text-xs rounded border border-gray-200 text-gray-600 hover:bg-gray-50">
          Cancelar
        </button>
        <button type="submit" class="btn-admin-red">
          Salvar Membro
        </button>
      </div>
    </form>

  </div>
</div>

<script>
function abrirModalEditar(user) {
  document.getElementById('modal-titulo').innerText = 'Editar Membro: ' + user.nome;
  document.getElementById('modal-id').value = user.id;
  document.getElementById('modal-nome').value = user.nome;
  document.getElementById('modal-email').value = user.email;
  document.getElementById('modal-papel').value = user.papel;
  document.getElementById('modal-ativo').checked = (user.ativo == 1);
  
  // No modo edição a senha é opcional
  document.getElementById('label-senha').innerText = 'Nova Senha (Opcional)';
  document.getElementById('modal-senha').required = false;
  document.getElementById('modal-senha').placeholder = '••••••••';
  document.getElementById('hint-senha').classList.remove('hidden');

  document.getElementById('modal-novo-usuario').classList.remove('hidden');
}

function fecharModal() {
  document.getElementById('modal-novo-usuario').classList.add('hidden');
  document.getElementById('modal-id').value = '0';
  document.getElementById('modal-nome').value = '';
  document.getElementById('modal-email').value = '';
  document.getElementById('modal-senha').value = '';
  document.getElementById('modal-senha').required = true;
  document.getElementById('label-senha').innerText = 'Senha de Acesso *';
  document.getElementById('hint-senha').classList.add('hidden');
  document.getElementById('modal-titulo').innerText = 'Novo Membro da Equipe';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
