<?php
/**
 * O FAROL - Gestão de Notícias
 * Abas de status, busca, filtros por categoria/autor, ordenação, ações em lote, pendências e duplicação.
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
$order_by = $_GET['ordem'] ?? 'id_desc'; // 'id_desc', 'id_asc', 'views_desc', 'titulo_asc'
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$itens_por_pagina = 15;

$mensagem = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';
$erro_carga = '';

// Processamento de Ações em Lote (Bulk Actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_lote']) && $pdo) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $acao_lote = $_POST['acao_lote'];
        $ids = array_filter(array_map('intval', (array)($_POST['selecionados'] ?? [])));

        if (empty($ids)) {
            $erro = "Nenhuma notícia foi selecionada para a ação em lote.";
        } else {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            try {
                if ($acao_lote === 'publicar') {
                    $stmtB = $pdo->prepare("UPDATE noticias SET status = 'publicado', publicado_em = COALESCE(publicado_em, NOW()), atualizado_em = NOW() WHERE id IN ($placeholders)");
                    $stmtB->execute($ids);
                    $mensagem = count($ids) . " notícia(s) publicada(s) com sucesso!";
                } elseif ($acao_lote === 'revisao') {
                    $stmtB = $pdo->prepare("UPDATE noticias SET status = 'revisao', atualizado_em = NOW() WHERE id IN ($placeholders)");
                    $stmtB->execute($ids);
                    $mensagem = count($ids) . " notícia(s) enviada(s) para revisão!";
                } elseif ($acao_lote === 'aprovado') {
                    $stmtB = $pdo->prepare("UPDATE noticias SET status = 'aprovado', atualizado_em = NOW() WHERE id IN ($placeholders)");
                    $stmtB->execute($ids);
                    $mensagem = count($ids) . " notícia(s) marcada(s) como aprovada(s)!";
                } elseif ($acao_lote === 'arquivar') {
                    $stmtB = $pdo->prepare("UPDATE noticias SET status = 'arquivado', atualizado_em = NOW() WHERE id IN ($placeholders)");
                    $stmtB->execute($ids);
                    $mensagem = count($ids) . " notícia(s) arquivada(s)!";
                } elseif ($acao_lote === 'excluir') {
                    $stmtB = $pdo->prepare("DELETE FROM noticias WHERE id IN ($placeholders)");
                    $stmtB->execute($ids);
                    $mensagem = count($ids) . " notícia(s) excluída(s) permanentemente!";
                }
            } catch (Exception $e) {
                $erro = "Erro ao executar ação em lote: " . $e->getMessage();
            }
        }
    }
}

// Exclusão rápida individual
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

// Construção da consulta de notícias com paginação e ordenação
$noticias = [];
$total_filtrado = 0;
$total_paginas = 1;

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

        // Total para paginação
        $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM noticias n WHERE $where");
        $stmtCount->execute($params);
        $total_filtrado = (int)$stmtCount->fetchColumn();
        $total_paginas = max(1, (int)ceil($total_filtrado / $itens_por_pagina));
        $offset = ($pagina - 1) * $itens_por_pagina;

        // Ordenação
        $sqlOrder = match($order_by) {
            'id_asc' => 'n.id ASC',
            'views_desc' => 'n.visualizacoes DESC, n.id DESC',
            'titulo_asc' => 'n.titulo ASC',
            default => 'n.id DESC'
        };

        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, 
                               COALESCE((SELECT GROUP_CONCAT(a.nome SEPARATOR ', ') FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = n.id), 'Redação') as autores_nomes,
                               (SELECT COUNT(*) FROM noticia_notas nn WHERE nn.noticia_id = n.id AND nn.status_resolvido = 0) as notas_pendentes
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE $where 
                               ORDER BY $sqlOrder 
                               LIMIT $itens_por_pagina OFFSET $offset");
        $stmt->execute($params);
        $bdNews = $stmt->fetchAll();

        foreach ($bdNews as $bn) {
            // Checagem de pendências editoriais
            $pendencias = [];
            if (empty(trim((string)$bn['imagem_capa']))) $pendencias[] = 'Sem capa';
            if (empty(trim((string)$bn['subtitulo']))) $pendencias[] = 'Sem subtítulo';
            if ($bn['autores_nomes'] === 'Redação') $pendencias[] = 'Sem autor discente';
            if ((int)$bn['notas_pendentes'] > 0) $pendencias[] = $bn['notas_pendentes'] . ' nota(s)';

            $noticias[] = [
                'id' => (int)$bn['id'],
                'titulo' => $bn['titulo'],
                'categoria_nome' => $bn['categoria_nome'],
                'autor_nome' => $bn['autores_nomes'],
                'status' => $bn['status'],
                'data' => ui_date($bn['publicado_em'] ?? $bn['criado_em'] ?? null),
                'foto' => $bn['imagem_capa'] ?? '',
                'slug' => $bn['slug'],
                'visualizacoes' => (int)($bn['visualizacoes'] ?? 0),
                'pendencias' => $pendencias
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
$qs_base = ['q' => $search_query, 'categoria' => $categoria_filter ?: '', 'autor' => $autor_filter, 'ordem' => $order_by];
?>

<?= ui_page_header('Notícias', 'Gerencie todas as reportagens, rascunhos e publicações.',
    '<a href="' . BASE_URL . '/admin/editor.php" class="btn btn--primary">' . ui_icon('plus') . ' Nova notícia</a>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<!-- Abas de status -->
<nav class="tabs" aria-label="Filtrar por status">
  <?php foreach ($tabs as $chave => $rotulo): ?>
    <a href="?<?= e(http_build_query(array_filter(array_merge($qs_base, ['status' => $chave, 'pagina' => 1]), static fn($v) => $v !== '' && $v !== null))) ?>"
       class="tab <?= $status_filter === $chave ? 'is-active' : '' ?>">
      <?= e($rotulo) ?> <span class="tab__count"><?= (int)$counts[$chave] ?></span>
    </a>
  <?php endforeach; ?>
</nav>

<!-- Busca e filtros expandidos -->
<form method="GET" action="" class="card toolbar" style="flex-wrap:wrap;gap:10px">
  <input type="hidden" name="status" value="<?= e($status_filter) ?>">
  <?php if ($autor_filter !== ''): ?><input type="hidden" name="autor" value="<?= e($autor_filter) ?>"><?php endif; ?>
  
  <div class="input-icon" style="flex:1;min-width:220px">
    <?= ui_icon('search') ?>
    <input type="search" name="q" value="<?= e($search_query) ?>" placeholder="Buscar por título ou subtítulo…" class="input" aria-label="Buscar notícias">
  </div>
  
  <div class="toolbar__group" style="flex-wrap:wrap;gap:8px">
    <select name="categoria" class="select" aria-label="Filtrar por categoria" onchange="this.form.submit()">
      <option value="">Todas as categorias</option>
      <?php foreach ($cats_filtro as $cf): ?>
        <option value="<?= (int)$cf['id'] ?>" <?= $categoria_filter === (int)$cf['id'] ? 'selected' : '' ?>><?= e($cf['nome']) ?></option>
      <?php endforeach; ?>
    </select>

    <select name="ordem" class="select" aria-label="Ordenar por" onchange="this.form.submit()">
      <option value="id_desc" <?= $order_by === 'id_desc' ? 'selected' : '' ?>>Mais recentes (ID desc)</option>
      <option value="id_asc" <?= $order_by === 'id_asc' ? 'selected' : '' ?>>Mais antigas (ID asc)</option>
      <option value="views_desc" <?= $order_by === 'views_desc' ? 'selected' : '' ?>>Mais visualizadas</option>
      <option value="titulo_asc" <?= $order_by === 'titulo_asc' ? 'selected' : '' ?>>Título (A-Z)</option>
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

<!-- Formulário com suporte a Ações em Lote -->
<form method="POST" action="" id="form-noticias-lote">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <!-- Barra de Ações em Lote (Sticky/Visível ao selecionar) -->
  <div id="barra-acoes-lote" class="card" style="display:none;background:#f8fafc;border:1px solid #cbd5e1;padding:10px 16px;margin-bottom:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="font-weight:600;font-size:13px">
      <span id="contador-selecionados">0</span> matéria(s) selecionada(s)
    </div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <button type="submit" name="acao_lote" value="publicar" class="btn btn--sm btn--primary">Publicar selecionadas</button>
      <button type="submit" name="acao_lote" value="revisao" class="btn btn--sm">Enviar p/ revisão</button>
      <button type="submit" name="acao_lote" value="aprovado" class="btn btn--sm">Aprovar</button>
      <button type="submit" name="acao_lote" value="arquivar" class="btn btn--sm">Arquivar</button>
      <button type="submit" name="acao_lote" value="excluir" class="btn btn--sm btn--danger" onclick="return confirm('Deseja excluir permanentemente todas as matérias selecionadas?')">Excluir</button>
    </div>
  </div>

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
              <th style="width:36px;text-align:center"><input type="checkbox" id="check-all" aria-label="Selecionar todas"></th>
              <th>Notícia</th>
              <th>Categoria</th>
              <th>Autores</th>
              <th>Status</th>
              <th>Pendências</th>
              <th>Visualizações</th>
              <th>Publicação</th>
              <th class="t-right">Ações Rápidas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($noticias as $item): ?>
              <tr>
                <td style="text-align:center">
                  <input type="checkbox" name="selecionados[]" value="<?= $item['id'] ?>" class="item-check">
                </td>
                <td>
                  <div class="cell">
                    <?php if (!empty($item['foto'])): ?>
                      <img src="<?= BASE_URL ?>/<?= e((string)$item['foto']) ?>" alt="" class="thumb" loading="lazy" style="width:48px;height:48px;border-radius:4px;object-fit:cover">
                    <?php else: ?>
                      <div class="thumb" style="width:48px;height:48px;border-radius:4px;background:var(--surface-2);display:flex;align-items:center;justify-content:center;color:var(--text-muted)">
                        <?= ui_icon('image', 'ic--sm') ?>
                      </div>
                    <?php endif; ?>
                    <div style="min-width:0">
                      <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$item['id'] ?>" class="cell__title clamp-2" style="font-weight:600"><?= e($item['titulo']) ?></a>
                      <div class="cell__meta truncate-1" style="font-size:11.5px;color:var(--text-muted)">/noticia/<?= e($item['slug']) ?></div>
                    </div>
                  </div>
                </td>
                <td class="t-nowrap"><?= e($item['categoria_nome']) ?></td>
                <td class="t-nowrap">
                  <div class="cell"><span class="avatar avatar--sm avatar--soft"><?= e(ui_initials($item['autor_nome'])) ?></span><?= e($item['autor_nome']) ?></div>
                </td>
                <td><?= ui_news_status($item['status']) ?></td>
                <td class="t-nowrap">
                  <?php if (empty($item['pendencias'])): ?>
                    <span style="color:#10b981;font-size:11.5px;font-weight:600">✓ Completa</span>
                  <?php else: ?>
                    <span title="<?= e(implode(', ', $item['pendencias'])) ?>" style="display:inline-block;padding:2px 6px;border-radius:4px;background:#fef2f2;color:#b91c1c;font-size:11px;font-weight:600">
                      ⚠️ <?= count($item['pendencias']) ?> pendência(s)
                    </span>
                  <?php endif; ?>
                </td>
                <td class="t-nowrap" style="font-variant-numeric:tabular-nums"><?= number_format($item['visualizacoes'], 0, ',', '.') ?></td>
                <td class="t-nowrap muted"><?= e($item['data']) ?></td>
                <td class="t-right">
                  <span class="row-actions" style="display:inline-flex;gap:4px">
                    <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$item['id'] ?>" class="icon-btn icon-btn--sm" title="Editar reportagem" aria-label="Editar"><?= ui_icon('pen') ?></a>
                    <a href="<?= BASE_URL ?>/admin/preview.php?id=<?= (int)$item['id'] ?>" target="_blank" rel="noopener" class="icon-btn icon-btn--sm" title="Pré-visualização" aria-label="Preview"><?= ui_icon('eye') ?></a>
                    <a href="<?= BASE_URL ?>/admin/noticia-duplicar.php?id=<?= (int)$item['id'] ?>&csrf_token=<?= csrf_token() ?>" class="icon-btn icon-btn--sm" title="Duplicar como novo rascunho" aria-label="Duplicar"><?= ui_icon('copy') ?></a>
                    <a href="<?= BASE_URL ?>/admin/history.php?id=<?= (int)$item['id'] ?>" class="icon-btn icon-btn--sm" title="Histórico de versões" aria-label="Histórico de versões"><?= ui_icon('history') ?></a>
                    <?php if ($item['status'] === 'publicado'): ?>
                      <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode((string)$item['slug']) ?>" target="_blank" rel="noopener" class="icon-btn icon-btn--sm" title="Ver no portal público" aria-label="Ver no site"><?= ui_icon('external') ?></a>
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

      <!-- Rodapé com Paginação e Totais -->
      <div class="card__foot" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px">
        <div style="font-size:13px;color:var(--text-muted)">
          Exibindo <?= count($noticias) ?> de <?= $total_filtrado ?> reportagem(ns) filtrada(s) (Total geral: <?= (int)$counts['todas'] ?>)
        </div>

        <?php if ($total_paginas > 1): ?>
          <div class="pagination" style="display:flex;gap:4px">
            <?php for ($p = 1; $p <= $total_paginas; $p++): ?>
              <a href="?<?= e(http_build_query(array_merge($_GET, ['pagina' => $p]))) ?>" class="btn btn--sm <?= $pagina === $p ? 'btn--primary' : 'btn--ghost' ?>"><?= $p ?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</form>

<script>
(function(){
  var checkAll = document.getElementById('check-all');
  var itemChecks = document.querySelectorAll('.item-check');
  var barraLote = document.getElementById('barra-acoes-lote');
  var contador = document.getElementById('contador-selecionados');

  function updateLoteBar() {
    var checked = document.querySelectorAll('.item-check:checked');
    var count = checked.length;
    if (contador) contador.textContent = count;
    if (barraLote) {
      barraLote.style.display = count > 0 ? 'flex' : 'none';
    }
    if (checkAll) {
      checkAll.checked = (count > 0 && count === itemChecks.length);
      checkAll.indeterminate = (count > 0 && count < itemChecks.length);
    }
  }

  if (checkAll) {
    checkAll.addEventListener('change', function(){
      itemChecks.forEach(function(ck){ ck.checked = checkAll.checked; });
      updateLoteBar();
    });
  }

  itemChecks.forEach(function(ck){
    ck.addEventListener('change', updateLoteBar);
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
