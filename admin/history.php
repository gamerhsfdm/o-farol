<?php
/**
 * O FAROL - Histórico de Versões e Auditoria de Conteúdo
 */
declare(strict_types=1);
$admin_title = "Histórico de Versões";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$noticia_id = (int)($_GET['id'] ?? 0);

if ($noticia_id <= 0 || !$pdo) {
    echo '<div class="p-4 bg-red-50 text-red-700 text-xs">Notícia inválida.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

$stmtN = $pdo->prepare("SELECT id, titulo, slug FROM noticias WHERE id = ?");
$stmtN->execute([$noticia_id]);
$noticia = $stmtN->fetch();

if (!$noticia) {
    echo '<div class="p-4 bg-red-50 text-red-700 text-xs">Notícia não encontrada.</div>';
    require_once __DIR__ . '/footer.php';
    exit;
}

// Ação de restauração de versão antiga
if (isset($_POST['acao']) && $_POST['acao'] === 'restaurar') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $versao_id = (int)($_POST['versao_id'] ?? 0);
        $stmtV = $pdo->prepare("SELECT * FROM noticia_versoes WHERE id = ? AND noticia_id = ?");
        $stmtV->execute([$versao_id, $noticia_id]);
        $versao_recuperada = $stmtV->fetch();

        if ($versao_recuperada) {
            // Atualiza a notícia com o conteúdo da versão restaurada
            $stmtUp = $pdo->prepare("UPDATE noticias SET titulo = ?, subtitulo = ?, conteudo = ?, imagem_capa = ? WHERE id = ?");
            $stmtUp->execute([
                $versao_recuperada['titulo'],
                $versao_recuperada['subtitulo'],
                $versao_recuperada['conteudo'],
                $versao_recuperada['imagem_capa'],
                $noticia_id
            ]);

            log_audit('restaurar_versao', 'noticias', $noticia_id, "Versão #{$versao_recuperada['versao']} restaurada com sucesso");
            header("Location: " . BASE_URL . "/admin/history.php?id=" . $noticia_id . "&msg=restaurado");
            exit;
        }
    }
}

// Busca lista de versões arquivadas
$stmtList = $pdo->prepare("SELECT v.*, u.nome as usuario_nome 
                           FROM noticia_versoes v 
                           LEFT JOIN usuarios u ON v.usuario_id = u.id 
                           WHERE v.noticia_id = ? 
                           ORDER BY v.versao DESC");
$stmtList->execute([$noticia_id]);
$versoes = $stmtList->fetchAll();
?>

<div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-200">
  <div>
    <h1 class="text-2xl font-serif font-extrabold text-[#111827]">Histórico de Versões</h1>
    <p class="text-xs text-gray-500 mt-1">
      Matéria: <strong>“<?= e($noticia['titulo']) ?>”</strong>
    </p>
  </div>
  <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= $noticia_id ?>" class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold px-3 py-1.5 rounded border border-gray-300">
    ← Voltar ao Editor
  </a>
</div>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'restaurado'): ?>
  <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 p-3 rounded text-xs mb-6 font-medium">
    ✓ Versão restaurada com sucesso no artigo ativo!
  </div>
<?php endif; ?>

<?php if (!empty($versoes)): ?>
  <div class="space-y-4">
    <?php foreach ($versoes as $v): ?>
      <div class="bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-3 border-b border-gray-100 gap-2 mb-3">
          <div class="flex items-center gap-3">
            <span class="bg-[#164e63] text-white font-bold text-xs px-2.5 py-0.5 rounded">
              Versão #<?= e((string)$v['versao']) ?>
            </span>
            <span class="text-xs text-gray-500">
              Arquivada em <?= date('d/m/Y \à\s H:i:s', strtotime($v['criado_em'])) ?> por <strong><?= e($v['usuario_nome'] ?? 'Redação') ?></strong>
            </span>
          </div>
          
          <form action="<?= BASE_URL ?>/admin/history.php?id=<?= $noticia_id ?>" method="POST" onsubmit="return confirm('Tem certeza que deseja restaurar esta versão antiga para a matéria ativa?');">
            <input type="hidden" name="acao" value="restaurar">
            <input type="hidden" name="versao_id" value="<?= $v['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs px-3 py-1.5 rounded shadow">
              ↺ Restaurar Esta Versão
            </button>
          </form>
        </div>

        <div class="text-xs space-y-2">
          <div><strong class="text-gray-700">Título:</strong> <span class="text-gray-900 font-serif font-bold"><?= e($v['titulo']) ?></span></div>
          <?php if (!empty($v['subtitulo'])): ?>
            <div><strong class="text-gray-700">Subtítulo:</strong> <span class="text-gray-600"><?= e($v['subtitulo']) ?></span></div>
          <?php endif; ?>
          <details class="text-[11px] text-gray-500 pt-2 cursor-pointer">
            <summary class="font-bold text-[#164e63] hover:underline">Ver Conteúdo Arquivado da Versão</summary>
            <div class="p-3 bg-gray-50 rounded border border-gray-200 mt-2 text-gray-700 text-xs">
              <?= $v['conteudo'] ?>
            </div>
          </details>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="bg-white p-12 text-center rounded-lg border border-gray-200">
    <p class="text-gray-500 text-xs">Ainda não existem versões arquivadas para esta matéria. Novas versões são geradas a cada alteração salva no editor.</p>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
