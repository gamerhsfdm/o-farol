<?php
/**
 * O FAROL - Central de Comentários
 * Moderação de comentários enviados pelos leitores nas reportagens.
 */
declare(strict_types=1);
$admin_title = "Comentários";
$admin_breadcrumb = "Comentários";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

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
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}
$pendentes = 0;
foreach ($comentarios_lista as $cm) {
    if ($cm['status'] === 'pendente') {
        $pendentes++;
    }
}
?>

<?= ui_page_header('Comentários', 'Modere as conversas publicadas nas reportagens.') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<?php if (empty($comentarios_lista)): ?>
  <div class="card">
    <?= ui_empty('message', 'Nenhum comentário por aqui', 'Quando os leitores comentarem nas reportagens, você poderá aprovar, ocultar ou excluir os comentários nesta tela.') ?>
  </div>
<?php else: ?>
  <?php if ($pendentes > 0): ?>
    <?= ui_alerts('', '', $pendentes . ($pendentes === 1 ? ' comentário aguarda moderação.' : ' comentários aguardam moderação.')) ?>
  <?php endif; ?>
  <div class="card card--flush">
    <div class="tbl-wrap">
      <table class="tbl" style="min-width:860px">
        <thead>
          <tr><th>Autor</th><th>Reportagem</th><th>Comentário</th><th>Status</th><th>Data</th><th class="t-right">Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach ($comentarios_lista as $com): ?>
            <tr>
              <td class="t-nowrap">
                <div class="cell">
                  <span class="avatar avatar--sm avatar--soft"><?= e(ui_initials($com['nome_autor'])) ?></span>
                  <div><div class="cell__title"><?= e($com['nome_autor']) ?></div><div class="cell__meta"><?= e($com['turma_autor'] ?? 'Estudante') ?></div></div>
                </div>
              </td>
              <td style="max-width:240px">
                <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode((string)$com['noticia_slug']) ?>" target="_blank" rel="noopener" class="cell__title clamp-2"><?= e($com['noticia_titulo']) ?></a>
              </td>
              <td style="max-width:360px"><p class="clamp-2"><?= e($com['conteudo']) ?></p></td>
              <td class="t-nowrap">
                <?php if ($com['status'] === 'aprovado'): ?><?= ui_badge('Aprovado', 'ok') ?>
                <?php elseif ($com['status'] === 'rejeitado'): ?><?= ui_badge('Rejeitado', 'bad') ?>
                <?php else: ?><?= ui_badge('Pendente', 'warn') ?><?php endif; ?>
              </td>
              <td class="t-nowrap muted"><?= e(ui_date($com['criado_em'] ?? null)) ?></td>
              <td class="t-right t-nowrap">
                <span class="row-actions">
                  <?php if ($com['status'] !== 'aprovado'): ?>
                    <a href="?acao=aprovar&id=<?= (int)$com['id'] ?>" class="btn btn--sm"><?= ui_icon('check', 'ic--sm') ?> Aprovar</a>
                  <?php endif; ?>
                  <?php if ($com['status'] !== 'rejeitado'): ?>
                    <a href="?acao=rejeitar&id=<?= (int)$com['id'] ?>" class="btn btn--sm btn--ghost">Ocultar</a>
                  <?php endif; ?>
                  <a href="?acao=excluir&id=<?= (int)$com['id'] ?>" class="icon-btn icon-btn--sm icon-btn--danger" title="Excluir" aria-label="Excluir comentário"
                     data-confirm="Este comentário será excluído permanentemente." data-confirm-title="Excluir comentário?" data-confirm-ok="Excluir"><?= ui_icon('trash') ?></a>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card__foot"><span><?= count($comentarios_lista) ?> comentário<?= count($comentarios_lista) === 1 ? '' : 's' ?></span><span><?= $pendentes ?> pendente<?= $pendentes === 1 ? '' : 's' ?></span></div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
