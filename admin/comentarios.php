<?php
/**
 * O FAROL - Central de Comentários Fiel à Imagem 10
 * Painel Administrativo de Moderação de Conversas e Opiniões das Reportagens
 */
declare(strict_types=1);
$admin_title = "Comentários";
$admin_breadcrumb = "Comentários";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Ações de Moderação (Aprovar, Rejeitar, Excluir)
if (isset($_GET['acao']) && isset($_GET['id']) && $pdo) {
    $cId = (int)$_GET['id'];
    $acao = $_GET['acao'];

    try {
        if ($acao === 'aprovar') {
            $stmt = $pdo->prepare("UPDATE comentarios SET status = 'aprovado' WHERE id = ?");
            $stmt->execute([$cId]);
            $mensagem = "Comentário aprovado e visível na reportagem!";
        } elseif ($acao === 'rejeitar') {
            $stmt = $pdo->prepare("UPDATE comentarios SET status = 'rejeitado' WHERE id = ?");
            $stmt->execute([$cId]);
            $mensagem = "Comentário rejeitado e ocultado do site!";
        } elseif ($acao === 'excluir') {
            $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id = ?");
            $stmt->execute([$cId]);
            $mensagem = "Comentário excluído com sucesso!";
        }
    } catch (Exception $e) {
        $erro = "Erro ao moderar comentário: " . $e->getMessage();
    }
}

// Consulta Comentários
$comentarios_lista = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT c.*, n.titulo as noticia_titulo, n.slug as noticia_slug 
                             FROM comentarios c 
                             JOIN noticias n ON c.noticia_id = n.id 
                             ORDER BY c.id DESC");
        $comentarios_lista = $stmt->fetchAll();
    } catch (Exception $e) {}
}
?>

<!-- TOPO: TÍTULO + BOTÃO CONFIGURAR MODERAÇÃO -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Comentários</h1>
    <p class="text-xs text-gray-500 mt-1">Modere as conversas publicadas nas reportagens.</p>
  </div>
  <button type="button" class="btn-admin-red">
    + Configurar moderação
  </button>
</div>

<?php if ($mensagem): ?>
  <div class="p-3 mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded flex items-center justify-between">
    <span>✓ <?= e($mensagem) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">×</button>
  </div>
<?php endif; ?>

<?php if ($erro): ?>
  <div class="p-3 mb-6 bg-red-50 border border-red-200 text-red-800 text-xs rounded flex items-center justify-between">
    <span>⚠️ <?= e($erro) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-red-700 font-bold">×</button>
  </div>
<?php endif; ?>

<?php if (empty($comentarios_lista)): ?>
  <!-- CARD CENTRAL VAZIO COM ÍCONE DE BALÃO IDÊNTICO À IMAGEM 10 -->
  <div class="admin-card p-20 flex flex-col items-center justify-center text-center min-h-[460px]">
    
    <!-- Ícone Balão de Fala Vermelho com Fundo Suave -->
    <div class="w-14 h-14 rounded-full bg-[#fef2f2] flex items-center justify-center text-[#c0262d] mb-4">
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
    </div>

    <h3 class="text-lg font-editorial-heading font-bold text-gray-900 mb-1">
      Central de comentários
    </h3>

    <p class="text-xs text-gray-400 max-w-sm mb-6">
      Acompanhe, aprove ou oculte comentários enviados pelos leitores nas reportagens do jornal.
    </p>

    <button type="button" class="border border-gray-200 rounded px-4 py-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm">
      Explorar configurações
    </button>

  </div>
<?php else: ?>
  <!-- TABELA DE MODERAÇÃO DE COMENTÁRIOS -->
  <div class="admin-card overflow-hidden">
    <table class="w-full text-left text-xs">
      <thead>
        <tr class="border-b border-[#f0eee9] text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-[#fafaf8]">
          <th class="p-3.5">AUTOR / ESTUDANTE</th>
          <th class="p-3.5">REPORTAGEM</th>
          <th class="p-3.5">COMENTÁRIO</th>
          <th class="p-3.5">STATUS</th>
          <th class="p-3.5">DATA</th>
          <th class="p-3.5 text-right">AÇÕES</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[#f7f6f2]">
        <?php foreach ($comentarios_lista as $com): ?>
          <tr class="hover:bg-[#fafaf8] transition-colors">
            <td class="p-3.5 whitespace-nowrap">
              <div class="font-bold text-gray-900"><?= e($com['nome_autor']) ?></div>
              <div class="text-[10px] text-gray-400"><?= e($com['turma_autor'] ?? 'Estudante') ?></div>
            </td>
            <td class="p-3.5">
              <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($com['noticia_slug']) ?>" target="_blank" class="font-medium text-gray-800 hover:text-[#c0262d] line-clamp-1">
                <?= e($com['noticia_titulo']) ?>
              </a>
            </td>
            <td class="p-3.5">
              <p class="text-gray-700 line-clamp-2 max-w-md"><?= e($com['conteudo']) ?></p>
            </td>
            <td class="p-3.5 whitespace-nowrap">
              <?php if ($com['status'] === 'aprovado'): ?>
                <span class="badge-status badge-status-published">Aprovado</span>
              <?php elseif ($com['status'] === 'rejeitado'): ?>
                <span class="badge-status bg-red-100 text-red-700">Rejeitado</span>
              <?php else: ?>
                <span class="badge-status badge-status-review">Pendente</span>
              <?php endif; ?>
            </td>
            <td class="p-3.5 text-gray-400 whitespace-nowrap">
              <?= date('d M, H:i', strtotime($com['criado_em'])) ?>
            </td>
            <td class="p-3.5 text-right whitespace-nowrap">
              <div class="flex items-center justify-end gap-1.5">
                <?php if ($com['status'] !== 'aprovado'): ?>
                  <a href="?acao=aprovar&id=<?= $com['id'] ?>" class="px-2 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded text-[10px] font-bold hover:bg-emerald-100" title="Aprovar">
                    ✓ Aprovar
                  </a>
                <?php endif; ?>
                <?php if ($com['status'] !== 'rejeitado'): ?>
                  <a href="?acao=rejeitar&id=<?= $com['id'] ?>" class="px-2 py-1 bg-amber-50 text-amber-700 border border-amber-200 rounded text-[10px] font-bold hover:bg-amber-100" title="Rejeitar">
                    ✕ Ocultar
                  </a>
                <?php endif; ?>
                <a href="?acao=excluir&id=<?= $com['id'] ?>" onclick="return confirm('Deseja excluir este comentário permanentemente?')" class="p-1 text-red-400 hover:text-red-700" title="Excluir">
                  🗑️
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
