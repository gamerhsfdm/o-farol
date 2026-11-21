<?php
/**
 * O FAROL - Histórico de Versões e Auditoria de Conteúdo com Diff Visual
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

$stmtN = $pdo->prepare("SELECT id, titulo, subtitulo, conteudo, imagem_capa, slug FROM noticias WHERE id = ?");
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
            // Salva o estado atual como uma nova versão antes de restaurar
            try {
                $stmtMax = $pdo->prepare("SELECT COALESCE(MAX(versao), 0) + 1 FROM noticia_versoes WHERE noticia_id = ?");
                $stmtMax->execute([$noticia_id]);
                $nextVer = (int)$stmtMax->fetchColumn();
                $uid = $_SESSION['usuario_id'] ?? null;
                $stmtSaveCurrent = $pdo->prepare("INSERT INTO noticia_versoes (noticia_id, usuario_id, titulo, subtitulo, conteudo, imagem_capa, versao, motivo_alteracao, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, 'Backup antes de restaurar #' || ?, NOW())");
                $stmtSaveCurrent->execute([$noticia_id, $uid, $noticia['titulo'], $noticia['subtitulo'], $noticia['conteudo'], $noticia['imagem_capa'], $nextVer, (string)$versao_recuperada['versao']]);
            } catch (Exception $eBkp) {}

            // Atualiza a notícia com o conteúdo da versão restaurada
            $stmtUp = $pdo->prepare("UPDATE noticias SET titulo = ?, subtitulo = ?, conteudo = ?, imagem_capa = ?, atualizado_em = NOW() WHERE id = ?");
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

/**
 * Função simples e rápida para computar diff visual de palavras entre duas strings
 */
function render_word_diff(string $oldText, string $newText): string {
    $oldWords = preg_split('/\s+/', strip_tags($oldText));
    $newWords = preg_split('/\s+/', strip_tags($newText));

    $out = [];
    $max = max(count($oldWords), count($newWords));
    
    // Comparação simples palavra por palavra
    $i = 0; $j = 0;
    while ($i < count($oldWords) || $j < count($newWords)) {
        $wOld = $oldWords[$i] ?? null;
        $wNew = $newWords[$j] ?? null;

        if ($wOld === $wNew) {
            $out[] = htmlspecialchars($wOld ?? '', ENT_QUOTES, 'UTF-8');
            $i++; $j++;
        } elseif ($wOld !== null && !in_array($wOld, array_slice($newWords, $j, 5), true)) {
            $out[] = '<del style="background:#fee2e2;color:#991b1b;text-decoration:line-through;padding:1px 3px;border-radius:2px">' . htmlspecialchars($wOld, ENT_QUOTES, 'UTF-8') . '</del>';
            $i++;
        } elseif ($wNew !== null) {
            $out[] = '<ins style="background:#dcfce7;color:#166534;text-decoration:none;font-weight:600;padding:1px 3px;border-radius:2px">' . htmlspecialchars($wNew, ENT_QUOTES, 'UTF-8') . '</ins>';
            $j++;
        } else {
            $i++; $j++;
        }
    }
    return implode(' ', $out);
}
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
            <?php if (!empty($v['motivo_alteracao'])): ?>
              <span class="badge badge--neutral"><?= e($v['motivo_alteracao']) ?></span>
            <?php endif; ?>
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
          <div><span class="muted text-sm">Título arquivado:</span><div class="strong" style="font-size:16px"><?= e($v['titulo']) ?></div></div>
          <?php if (!empty($v['subtitulo'])): ?>
            <div><span class="muted text-sm">Subtítulo arquivado:</span><div><?= e($v['subtitulo']) ?></div></div>
          <?php endif; ?>

          <!-- Comparação Diff Visual com a Versão Atual Ativa -->
          <details style="margin-top:8px">
            <summary class="link" style="cursor:pointer;font-weight:600">🔍 Ver Comparação Diff com o Artigo Atual Ativo</summary>
            <div style="margin-top:10px;padding:14px;background:#fff;border:1px solid var(--line);border-radius:var(--r);font-size:13.5px;line-height:1.7">
              <div style="margin-bottom:8px;font-size:11.5px;color:var(--text-muted)">
                Legenda: <del style="background:#fee2e2;color:#991b1b;padding:1px 4px">Removido da versão arquivada</del> e <ins style="background:#dcfce7;color:#166534;padding:1px 4px;text-decoration:none">Novo no artigo atual</ins>
              </div>
              <hr style="border:0;border-top:1px dashed var(--line);margin:10px 0">
              <div>
                <strong>Título:</strong><br>
                <?= render_word_diff($v['titulo'], $noticia['titulo']) ?>
              </div>
              <div style="margin-top:10px">
                <strong>Corpo do Texto:</strong><br>
                <?= render_word_diff($v['conteudo'], $noticia['conteudo']) ?>
              </div>
            </div>
          </details>

          <details style="margin-top:4px">
            <summary class="link" style="cursor:pointer">Ver HTML completo arquivado desta versão</summary>
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
