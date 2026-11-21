<?php
/**
 * O FAROL - Histórico de Versões e Auditoria de Conteúdo
 */
declare(strict_types=1);
$admin_title = "Histórico de versões";
$admin_breadcrumb = "Histórico de versões";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$noticia_id = (int)($_GET['id'] ?? 0);

if ($noticia_id <= 0 || !$pdo) {
    echo ui_alerts('', 'Notícia inválida.');
    require_once __DIR__ . '/footer.php';
    exit;
}

$stmtN = $pdo->prepare("SELECT id, titulo, slug FROM noticias WHERE id = ?");
$stmtN->execute([$noticia_id]);
$noticia = $stmtN->fetch();

if (!$noticia) {
    echo ui_alerts('', 'Notícia não encontrada.');
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

<?= ui_page_header('Histórico de versões', 'Matéria: ' . $noticia['titulo'],
    '<a href="' . BASE_URL . '/admin/editor.php?id=' . $noticia_id . '" class="btn">' . ui_icon('arrow-l') . ' Voltar ao editor</a>') ?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'restaurado'): ?>
  <?= ui_alerts('Versão restaurada com sucesso na matéria ativa!') ?>
<?php endif; ?>

<?php if (!empty($versoes)): ?>
  <div class="stack">
    <?php foreach ($versoes as $v): ?>
      <div class="card">
        <div class="card__head">
          <div class="row row--wrap">
            <?= ui_badge('Versão #' . (string)$v['versao'], 'brand') ?>
            <span class="muted text-sm">Arquivada em <?= date('d/m/Y \à\s H:i', strtotime($v['criado_em'])) ?> por <strong><?= e($v['usuario_nome'] ?? 'Redação') ?></strong></span>
          </div>
          <form action="<?= BASE_URL ?>/admin/history.php?id=<?= $noticia_id ?>" method="POST"
                data-confirm="A matéria ativa será substituída pelo conteúdo desta versão." data-confirm-title="Restaurar esta versão?" data-confirm-ok="Restaurar" data-confirm-tone="primary">
            <input type="hidden" name="acao" value="restaurar">
            <input type="hidden" name="versao_id" value="<?= (int)$v['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <button type="submit" class="btn btn--sm"><?= ui_icon('restore', 'ic--sm') ?> Restaurar esta versão</button>
          </form>
        </div>
        <div class="card__body stack stack--sm">
          <div><span class="muted text-sm">Título</span><div class="strong" style="font-size:16px"><?= e($v['titulo']) ?></div></div>
          <?php if (!empty($v['subtitulo'])): ?>
            <div><span class="muted text-sm">Subtítulo</span><div><?= e($v['subtitulo']) ?></div></div>
          <?php endif; ?>
          <details>
            <summary class="link" style="cursor:pointer">Ver conteúdo arquivado desta versão</summary>
            <div style="margin-top:10px;padding:16px;background:var(--surface-2);border:1px solid var(--line);border-radius:var(--r);overflow-x:auto;line-height:1.7">
              <?= $v['conteudo'] ?>
            </div>
          </details>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="card">
    <?= ui_empty('history', 'Nenhuma versão arquivada', 'Ainda não existem versões arquivadas para esta matéria. As versões aparecem aqui quando forem registradas.') ?>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
