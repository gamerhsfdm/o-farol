<?php
/**
 * O FAROL - Dashboard Editorial
 * Indicadores, publicações por dia, distribuição por categoria, últimas notícias, agenda e atividade (dados reais).
 */
declare(strict_types=1);
$admin_title = "Visão geral";
$admin_breadcrumb = "Visão geral";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

$total_noticias = 0;
$publicadas = 0;
$em_revisao = 0;
$rascunhos = 0;
$agendadas = 0;
$views = 0;
$erro_carga = '';
$dias = [];
$por_categoria = [];
$ultimas = [];
$agenda = [];
$atividades = [];

for ($i = 13; $i >= 0; $i--) {
    $dias[date('Y-m-d', strtotime("-{$i} days"))] = 0;
}

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT status, COUNT(*) AS qtd, COALESCE(SUM(visualizacoes), 0) AS v FROM noticias GROUP BY status");
        foreach ($stmt->fetchAll() as $r) {
            $q = (int)$r['qtd'];
            $total_noticias += $q;
            $views += (int)$r['v'];
            if ($r['status'] === 'publicado') {
                $publicadas = $q;
            } elseif ($r['status'] === 'revisao') {
                $em_revisao = $q;
            } elseif ($r['status'] === 'rascunho') {
                $rascunhos = $q;
            } elseif ($r['status'] === 'agendado') {
                $agendadas = $q;
            }
        }
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }

    try {
        $stmt = $pdo->query("SELECT DATE(COALESCE(publicado_em, criado_em)) AS dia, COUNT(*) AS qtd FROM noticias WHERE status = 'publicado' AND COALESCE(publicado_em, criado_em) >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY dia");
        foreach ($stmt->fetchAll() as $r) {
            if (isset($dias[$r['dia']])) {
                $dias[$r['dia']] = (int)$r['qtd'];
            }
        }
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }

    try {
        $stmt = $pdo->query("SELECT c.nome, c.cor, COUNT(n.id) AS qtd FROM categorias c LEFT JOIN noticias n ON n.categoria_id = c.id GROUP BY c.id, c.nome, c.cor ORDER BY qtd DESC, c.nome ASC LIMIT 5");
        $por_categoria = $stmt->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }

    try {
        $stmt = $pdo->query("SELECT n.*, c.nome AS categoria_nome FROM noticias n JOIN categorias c ON n.categoria_id = c.id ORDER BY n.id DESC LIMIT 6");
        $ultimas = $stmt->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }

    try {
        $stmt = $pdo->query("SELECT id, titulo, agendado_para FROM noticias WHERE status = 'agendado' AND agendado_para IS NOT NULL ORDER BY agendado_para ASC LIMIT 4");
        $agenda = $stmt->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }

    try {
        $stmt = $pdo->query("SELECT l.acao, l.detalhes, l.criado_em, u.nome AS usuario_nome FROM logs_auditoria l LEFT JOIN usuarios u ON u.id = l.usuario_id ORDER BY l.id DESC LIMIT 8");
        $atividades = $stmt->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

$views_fmt = $views >= 1000 ? number_format($views / 1000, 1, ',', '.') . ' mil' : (string)$views;
$pct_pub = $total_noticias > 0 ? (int)round($publicadas / $total_noticias * 100) : 0;
$max_dia = max(1, max($dias));
$total_14 = array_sum($dias);

$paleta = ['#c0262d', '#0e3a4b', '#d97706', '#6b21a8', '#0d9488'];
$total_cat = 0;
foreach ($por_categoria as $c) {
    $total_cat += (int)$c['qtd'];
}
$grad = [];
$acc = 0.0;
foreach ($por_categoria as $i => $c) {
    $por_categoria[$i]['_cor'] = ui_color($c['cor'] ?? null, $paleta[$i % 5]);
    $pct = $total_cat > 0 ? ((int)$c['qtd'] / $total_cat * 100) : 0;
    $grad[] = $por_categoria[$i]['_cor'] . ' ' . round($acc, 2) . '% ' . round($acc + $pct, 2) . '%';
    $acc += $pct;
}
$donut_bg = ($total_cat > 0) ? 'conic-gradient(' . implode(', ', $grad) . ')' : 'conic-gradient(#e5e7eb 0 100%)';

$rotulos = [
    'login'            => ['Entrou no painel', 'user'],
    'logout'           => ['Saiu do painel', 'logout'],
    'criar_usuario'    => ['Cadastrou um membro da equipe', 'user-plus'],
    'editar_usuario'   => ['Editou um membro da equipe', 'users'],
    'excluir_usuario'  => ['Excluiu um membro da equipe', 'trash'],
    'atualizar_config' => ['Atualizou as configurações', 'sliders'],
    'restaurar_versao' => ['Restaurou uma versão de matéria', 'restore'],
    'editar_perfil'    => ['Editou o próprio perfil', 'user'],
    'registro_conta'   => ['Nova conta criada', 'user-plus'],
];
$meses = ['', 'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN', 'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ'];
?>

<?= ui_page_header('Visão geral', 'Acompanhe o ritmo da redação e as principais métricas do jornal.',
    '<a href="' . BASE_URL . '/admin/editor.php" class="btn btn--primary">' . ui_icon('plus') . ' Nova notícia</a>') ?>

<?php if ($erro_carga !== ''): ?>
  <?= ui_load_error($erro_carga) ?>
<?php endif; ?>

<!-- Indicadores -->
<div class="cols cols--4 mb-6">
  <div class="card stat">
    <div>
      <div class="stat__label">Notícias</div>
      <div class="stat__value"><?= $total_noticias ?></div>
      <div class="stat__foot">Total cadastrado</div>
    </div>
    <div class="stat__icon"><?= ui_icon('news') ?></div>
  </div>
  <div class="card stat">
    <div>
      <div class="stat__label">Publicadas</div>
      <div class="stat__value"><?= $publicadas ?></div>
      <div class="stat__foot"><?= $pct_pub ?>% do total</div>
    </div>
    <div class="stat__icon stat__icon--ok"><?= ui_icon('check-c') ?></div>
  </div>
  <div class="card stat">
    <div>
      <div class="stat__label">Em revisão</div>
      <div class="stat__value"><?= $em_revisao ?></div>
      <div class="stat__foot"><?= $rascunhos ?> rascunho<?= $rascunhos === 1 ? '' : 's' ?> · <?= $agendadas ?> agendada<?= $agendadas === 1 ? '' : 's' ?></div>
    </div>
    <div class="stat__icon stat__icon--warn"><?= ui_icon('clock') ?></div>
  </div>
  <div class="card stat">
    <div>
      <div class="stat__label">Visualizações</div>
      <div class="stat__value"><?= e($views_fmt) ?></div>
      <div class="stat__foot">Soma de todas as notícias</div>
    </div>
    <div class="stat__icon stat__icon--accent"><?= ui_icon('trending') ?></div>
  </div>
</div>

<!-- Publicações por dia + categorias -->
<div class="split split--wide split--stretch mb-6">
  <div class="card">
    <div class="card__head">
      <div>
        <div class="card__title">Publicações por dia</div>
        <div class="card__sub"><?= $total_14 ?> publicaç<?= $total_14 === 1 ? 'ão' : 'ões' ?> nos últimos 14 dias</div>
      </div>
    </div>
    <div class="card__body">
      <?php if ($total_14 === 0): ?>
        <?= ui_empty('chart', 'Sem publicações no período', 'Quando houver notícias publicadas nos últimos 14 dias, o gráfico aparecerá aqui.') ?>
      <?php else: ?>
        <div class="bars" role="img" aria-label="Publicações por dia nos últimos 14 dias">
          <?php foreach ($dias as $dia => $qtd): ?>
            <div class="bars__col" title="<?= e(date('d/m/Y', strtotime($dia))) ?>: <?= $qtd ?>">
              <div class="bars__bar-wrap">
                <div class="bars__bar" style="height: <?= $qtd > 0 ? round($qtd / $max_dia * 88) : 0 ?>%"><?php if ($qtd > 0): ?><span class="bars__val"><?= $qtd ?></span><?php endif; ?></div>
              </div>
              <div class="bars__lbl"><?= e(date('d/m', strtotime($dia))) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card__head">
      <div>
        <div class="card__title">Por categoria</div>
        <div class="card__sub">Editorias com mais notícias</div>
      </div>
    </div>
    <div class="card__body">
      <?php if ($total_cat === 0): ?>
        <?= ui_empty('folder', 'Nenhuma notícia categorizada', 'A distribuição aparece quando houver notícias nas categorias.') ?>
      <?php else: ?>
        <div class="donut" style="background: <?= e($donut_bg) ?>">
          <div class="donut__center"><strong><?= $total_noticias ?></strong><span>notícias</span></div>
        </div>
        <div class="legend" style="margin-top: 22px">
          <?php foreach ($por_categoria as $c): ?>
            <div class="legend__row">
              <i class="legend__dot" style="background: <?= e($c['_cor']) ?>"></i>
              <span class="truncate-1"><?= e($c['nome']) ?></span>
              <span class="legend__val"><?= (int)$c['qtd'] ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Últimas notícias + agenda + atividade -->
<div class="split split--wide split--stretch">
  <div class="card card--flush">
    <div class="card__head">
      <div>
        <div class="card__title">Últimas notícias</div>
        <div class="card__sub">Conteúdos cadastrados recentemente</div>
      </div>
      <a href="<?= BASE_URL ?>/admin/noticias.php" class="link text-sm">Ver todas</a>
    </div>
    <?php if (empty($ultimas)): ?>
      <?= ui_empty('news', 'Nenhuma notícia cadastrada', 'Crie a primeira reportagem para começar a alimentar o jornal.',
          '<a href="' . BASE_URL . '/admin/editor.php" class="btn btn--primary">' . ui_icon('plus') . ' Nova notícia</a>') ?>
    <?php else: ?>
      <div class="list">
        <?php foreach ($ultimas as $n): ?>
          <?php $capa = $n['imagem_capa'] ?? ''; ?>
          <div class="list__item">
            <?php if (!empty($capa)): ?>
              <img src="<?= BASE_URL ?>/<?= e((string)$capa) ?>" alt="" class="thumb" loading="lazy">
            <?php else: ?>
              <div class="thumb"></div>
            <?php endif; ?>
            <div class="grow">
              <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$n['id'] ?>" class="cell__title truncate-1" style="display:block"><?= e($n['titulo']) ?></a>
              <div class="cell__meta"><?= e($n['categoria_nome']) ?> · <?= e(ui_date($n['criado_em'] ?? null)) ?></div>
            </div>
            <?= ui_news_status($n['status'] ?? '') ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="stack">
    <div class="card card--flush">
      <div class="card__head">
        <div>
          <div class="card__title">Próximas publicações</div>
          <div class="card__sub">Agenda editorial</div>
        </div>
      </div>
      <?php if (empty($agenda)): ?>
        <div class="card__body muted text-sm">Nenhuma notícia agendada.</div>
      <?php else: ?>
        <div class="list">
          <?php foreach ($agenda as $a): ?>
            <?php $ts = strtotime($a['agendado_para']); ?>
            <div class="list__item">
              <div class="date-chip"><b><?= date('d', $ts) ?></b><span><?= $meses[(int)date('n', $ts)] ?></span></div>
              <div class="grow">
                <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$a['id'] ?>" class="cell__title clamp-2"><?= e($a['titulo']) ?></a>
                <div class="cell__meta"><?= date('H:i', $ts) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="card card--flush">
      <div class="card__head">
        <div>
          <div class="card__title">Atividade recente</div>
          <div class="card__sub">Histórico de ações no painel</div>
        </div>
      </div>
      <?php if (empty($atividades)): ?>
        <div class="card__body muted text-sm">Nenhuma atividade registrada ainda.</div>
      <?php else: ?>
        <div class="timeline">
          <?php foreach ($atividades as $at): ?>
            <?php $rot = $rotulos[$at['acao']] ?? [ucfirst(str_replace('_', ' ', (string)$at['acao'])), 'info']; ?>
            <div class="timeline__item">
              <div class="timeline__dot"><?= ui_icon($rot[1], 'ic--sm') ?></div>
              <div class="grow">
                <div class="strong text-sm"><?= e($at['usuario_nome'] ?? 'Sistema') ?> · <?= e($rot[0]) ?></div>
                <div class="cell__meta"><?= e(ui_ago($at['criado_em'] ?? null)) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
