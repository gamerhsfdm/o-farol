<?php
/**
 * O FAROL - Gestão de Notícias
 * Abas de status, busca, filtros por categoria/autor, tabela e ações rápidas.
 */
declare(strict_types=1);
$admin_title = "Notícias";
$admin_breadcrumb = "Notícias";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$status_filter = $_GET['status'] ?? 'todas';
$autor_filter = trim($_GET['autor'] ?? '');
$categoria_filter = (int)($_GET['categoria'] ?? 0);
$search_query = trim($_GET['q'] ?? '');
$mensagem = '';
$erro = '';
$erro_carga = '';

// Exclusão ou arquivamento rápido
if (isset($_GET['deletar_id']) && $pdo) {
    if (!verify_csrf($_GET['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        try {
            $delId = (int)$_GET['deletar_id'];
            $stmtD = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
            $stmtD->execute([$delId]);
            log_audit('excluir_noticia', 'noticias', $delId, "Reportagem #{$delId} excluída");
            $mensagem = "Reportagem excluída com sucesso!";
        } catch (Exception $e) {
            $erro = "Não foi possível excluir a reportagem: " . $e->getMessage();
        }
    }
}

// Contagens para as abas de status
$counts = ['todas' => 0, 'publicadas' => 0, 'rascunhos' => 0, 'revisao' => 0, 'aprovadas' => 0, 'agendadas' => 0, 'arquivadas' => 0];
if ($pdo) {
    try {
        $stmtTot = $pdo->query("SELECT status, COUNT(*) as qtd FROM noticias GROUP BY status");
        $bdCounts = $stmtTot->fetchAll();
        $totalAll = 0;
        foreach ($bdCounts as $bc) {
            $st = $bc['status'];
            $qtd = (int)$bc['qtd'];
            $totalAll += $qtd;
            if ($st === 'publicado') $counts['publicadas'] = $qtd;
            elseif ($st === 'rascunho') $counts['rascunhos'] = $qtd;
            elseif ($st === 'revisao') $counts['revisao'] = $qtd;
            elseif ($st === 'aprovado') $counts['aprovadas'] = $qtd;
            elseif ($st === 'agendado') $counts['agendadas'] = $qtd;
            elseif ($st === 'arquivado') $counts['arquivadas'] = $qtd;
        }
        $counts['todas'] = $totalAll;
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

// Categorias para o filtro
$cats_filtro = [];
if ($pdo) {
    try {
        $cats_filtro = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome ASC")->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

// Consulta notícias do banco
$noticias = [];
if ($pdo) {
    try {
        $where = "1=1";
        $params = [];
        if ($status_filter === 'publicadas') $where .= " AND n.status = 'publicado'";
        elseif ($status_filter === 'rascunhos') $where .= " AND n.status = 'rascunho'";
        elseif ($status_filter === 'revisao') $where .= " AND n.status = 'revisao'";
        elseif ($status_filter === 'aprovadas') $where .= " AND n.status = 'aprovado'";
        elseif ($status_filter === 'agendadas') $where .= " AND n.status = 'agendado'";
        elseif ($status_filter === 'arquivadas') $where .= " AND n.status = 'arquivado'";

        if (!empty($search_query)) {
            $where .= " AND (n.titulo LIKE ? OR n.subtitulo LIKE ?)";
            $params[] = "%{$search_query}%";
            $params[] = "%{$search_query}%";
        }
        if ($categoria_filter > 0) {
            $where .= " AND n.categoria_id = ?";
            $params[] = $categoria_filter;
        }
        if ($autor_filter !== '') {
            $where .= " AND EXISTS (SELECT 1 FROM noticia_autores nax JOIN autores ax ON ax.id = nax.autor_id WHERE nax.noticia_id = n.id AND ax.nome = ?)";
            $params[] = $autor_filter;
        }

        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, 
                               COALESCE((SELECT a.nome FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = n.id LIMIT 1), 'Redação') as autor_nome
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE $where 
                               ORDER BY n.id DESC");
        $stmt->execute($params);
        $bdNews = $stmt->fetchAll();
        foreach ($bdNews as $bn) {
            $noticias[] = [
                'id' => $bn['id'],
                'titulo' => $bn['titulo'],
                'categoria_nome' => $bn['categoria_nome'],
                'autor_nome' => $bn['autor_nome'],
                'status' => $bn['status'],
                'data' => ui_date($bn['publicado_em'] ?? $bn['criado_em'] ?? null),
                'foto' => $bn['imagem_capa'] ?? '',
                'slug' => $bn['slug']
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

$tem_filtro = ($search_query !== '' || $categoria_filter > 0 || $autor_filter !== '' || $status_filter !== 'todas');
$tabs = [
    'todas' => 'Todas', 'publicadas' => 'Publicadas', 'rascunhos' => 'Rascunhos', 'revisao' => 'Em revisão',
    'aprovadas' => 'Aprovadas', 'agendadas' => 'Agendadas', 'arquivadas' => 'Arquivadas'
];
$qs_base = ['q' => $search_query, 'categoria' => $categoria_filter ?: '', 'autor' => $autor_filter];
?>

<?= ui_page_header('Notícias', 'Gerencie todas as reportagens, rascunhos e publicações.',
    '<a href="' . BASE_URL . '/admin/editor.php" class="btn btn--primary">' . ui_icon('plus') . ' Nova notícia</a>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<!-- Abas de status -->
<nav class="tabs" aria-label="Filtrar por status">
  <?php foreach ($tabs as $chave => $rotulo): ?>
    <a href="?<?= e(http_build_query(array_filter(array_merge($qs_base, ['status' => $chave]), static fn($v) => $v !== '' && $v !== null))) ?>"
       class="tab <?= $status_filter === $chave ? 'is-active' : '' ?>">
      <?= e($rotulo) ?> <span class="tab__count"><?= (int)$counts[$chave] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<!-- Busca e filtros -->
<form method="GET" action="" class="card toolbar">
  <input type="hidden" name="status" value="<?= e($status_filter) ?>">
  <?php if ($autor_filter !== ''): ?><input type="hidden" name="autor" value="<?= e($autor_filter) ?>"><?php endif; ?>
  <div class="input-icon">
    <?= ui_icon('search') ?>
    <input type="search" name="q" value="<?= e($search_query) ?>" placeholder="Buscar por título ou subtítulo…" class="input" aria-label="Buscar notícias">
  </div>
  <div class="toolbar__group">
    <select name="categoria" class="select" aria-label="Filtrar por categoria" onchange="this.form.submit()">
      <option value="">Todas as categorias</option>
      <?php foreach ($cats_filtro as $cf): ?>
        <option value="<?= (int)$cf['id'] ?>" <?= $categoria_filter === (int)$cf['id'] ? 'selected' : '' ?>><?= e($cf['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn--primary"><?= ui_icon('filter', 'ic--sm') ?> Filtrar</button>
    <?php if ($tem_filtro): ?>
      <a href="<?= BASE_URL ?>/admin/noticias.php" class="btn btn--ghost">Limpar</a>
    <?php endif; ?>
  </div>
</form>

<?php if ($autor_filter !== ''): ?>
  <div class="mb-4"><?= ui_badge('Autor: ' . $autor_filter, 'brand') ?></div>
<?php endif; ?>

<div class="card card--flush">
  <?php if (empty($noticias)): ?>
    <?php if ($tem_filtro): ?>
      <?= ui_empty('search', 'Nenhuma notícia encontrada', 'Nenhum resultado para os filtros aplicados. Tente outros termos ou limpe os filtros.',
          '<a href="' . BASE_URL . '/admin/noticias.php" class="btn">Limpar filtros</a>') ?>
    <?php else: ?>
      <?= ui_empty('news', 'Nenhuma notícia cadastrada', 'Crie a primeira reportagem do jornal.',
          '<a href="' . BASE_URL . '/admin/editor.php" class="btn btn--primary">' . ui_icon('plus') . ' Nova notícia</a>') ?>
    <?php endif; ?>
  <?php else: ?>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>Notícia</th>
            <th>Categoria</th>
            <th>Autor</th>
            <th>Status</th>
            <th>Publicação</th>
            <th class="t-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($noticias as $item): ?>
            <tr>
              <td>
                <div class="cell">
                  <?php if (!empty($item['foto'])): ?>
                    <img src="<?= BASE_URL ?>/<?= e((string)$item['foto']) ?>" alt="" class="thumb" loading="lazy">
                  <?php else: ?>
                    <div class="thumb"></div>
                  <?php endif; ?>
                  <div style="min-width:0">
                    <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$item['id'] ?>" class="cell__title clamp-2"><?= e($item['titulo']) ?></a>
                    <div class="cell__meta truncate-1">/noticia/<?= e($item['slug']) ?></div>
                  </div>
                </div>
              </td>
              <td class="t-nowrap"><?= e($item['categoria_nome']) ?></td>
              <td class="t-nowrap">
                <div class="cell"><span class="avatar avatar--sm avatar--soft"><?= e(ui_initials($item['autor_nome'])) ?></span><?= e($item['autor_nome']) ?></div>
              </td>
              <td><?= ui_news_status($item['status']) ?></td>
              <td class="t-nowrap muted"><?= e($item['data']) ?></td>
              <td class="t-right">
                <span class="row-actions">
                  <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$item['id'] ?>" class="icon-btn icon-btn--sm" title="Editar" aria-label="Editar"><?= ui_icon('pen') ?></a>
                  <a href="<?= BASE_URL ?>/admin/history.php?id=<?= (int)$item['id'] ?>" class="icon-btn icon-btn--sm" title="Histórico de versões" aria-label="Histórico de versões"><?= ui_icon('history') ?></a>
                  <?php if ($item['status'] === 'publicado'): ?>
                    <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode((string)$item['slug']) ?>" target="_blank" rel="noopener" class="icon-btn icon-btn--sm" title="Ver no site" aria-label="Ver no site"><?= ui_icon('external') ?></a>
                  <?php endif; ?>
                  <a href="?deletar_id=<?= (int)$item['id'] ?>&csrf_token=<?= csrf_token() ?>" class="icon-btn icon-btn--sm icon-btn--danger" title="Excluir" aria-label="Excluir"
                     data-confirm="Esta reportagem será excluída permanentemente. Essa ação não pode ser desfeita."
                     data-confirm-title="Excluir notícia?" data-confirm-ok="Excluir"><?= ui_icon('trash') ?></a>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card__foot">
      <span><?= count($noticias) ?> notícia<?= count($noticias) === 1 ? '' : 's' ?> exibida<?= count($noticias) === 1 ? '' : 's' ?></span>
      <span>Total no jornal: <?= (int)$counts['todas'] ?></span>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
