<?php
/**
 * O FAROL - Sugestões de Pautas
 * Caixa de entrada (lista + detalhe) para avaliar ideias enviadas pela comunidade escolar.
 */
declare(strict_types=1);
$admin_title = "Sugestões de pautas";
$admin_breadcrumb = "Sugestões de pautas";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

// 1. Ação: Transformar pauta em reportagem (Cria rascunho de notícia e abre o editor)
if (isset($_GET['transformar_id']) && $pdo) {
    try {
        $pId = (int)$_GET['transformar_id'];
        $stmtP = $pdo->prepare("SELECT * FROM pautas_sugestoes WHERE id = ?");
        $stmtP->execute([$pId]);
        $pautaBd = $stmtP->fetch();

        if ($pautaBd) {
            // Categoria padrão
            $stmtCat = $pdo->query("SELECT id FROM categorias LIMIT 1");
            $catId = $stmtCat->fetchColumn() ?: 1;

            $titulo = $pautaBd['titulo_sugestao'] ?? $pautaBd['titulo'] ?? 'Reportagem sugerida';
            $slug = slugify($titulo);
            $descricao = $pautaBd['descricao'] ?? '';
            $autorEstudante = $pautaBd['nome_estudante'] ?? $pautaBd['nome_autor'] ?? 'Estudante Colaborador';
            $turma = $pautaBd['turma_curso'] ?? $pautaBd['turma'] ?? '';

            $conteudoIni = "<p>" . nl2br(e($descricao)) . "</p>\n<p><em>Pauta sugerida pelo(a) estudante {$autorEstudante} ({$turma}).</em></p>";
            $subtitulo = "Reportagem produzida a partir de sugestão da comunidade discente.";

            $stmtIns = $pdo->prepare("INSERT INTO noticias (categoria_id, titulo, subtitulo, conteudo, slug, status, criado_em) VALUES (?, ?, ?, ?, ?, 'rascunho', NOW())");
            $stmtIns->execute([$catId, $titulo, $subtitulo, $conteudoIni, $slug]);
            $novaNoticiaId = $pdo->lastInsertId();

            // Atualiza status da pauta para aprovada
            $stmtUp = $pdo->prepare("UPDATE pautas_sugestoes SET status = 'aprovada' WHERE id = ?");
            $stmtUp->execute([$pId]);

            header("Location: " . BASE_URL . "/admin/editor.php?id=" . $novaNoticiaId);
            exit;
        }
    } catch (Exception $e) {
        $erro = "Erro ao transformar pauta: " . $e->getMessage();
    }
}

// 2. Ações de moderação de pauta (Recusar / Descartar, Marcar em análise)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_pauta'])) {
    $pId = (int)($_POST['pauta_id'] ?? 0);
    $acao = $_POST['acao_pauta'];
    $novo_status = match($acao) {
        'em_analise' => 'em_analise',
        'recusada', 'descartada' => 'descartada',
        default => 'pendente'
    };

    if ($pdo && $pId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE pautas_sugestoes SET status = ? WHERE id = ?");
            $stmt->execute([$novo_status, $pId]);
            $mensagem = "Status da pauta atualizado com sucesso!";
        } catch (Exception $e) {
            $erro = "Erro ao atualizar pauta: " . $e->getMessage();
        }
    } else {
        $mensagem = "Pauta atualizada com sucesso (modo visual/demonstração)!";
    }
}

$filtro_status = $_GET['status'] ?? 'todas';

// 3. Consulta pautas do banco
$pautas_lista = [];
if ($pdo) {
    try {
        $sql = "SELECT * FROM pautas_sugestoes";
        if ($filtro_status === 'novas') $sql .= " WHERE status = 'pendente'";
        elseif ($filtro_status === 'analise') $sql .= " WHERE status = 'em_analise'";
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->query($sql);
        $bdPautas = $stmt->fetchAll();
        foreach ($bdPautas as $idx => $p) {
            $nomeAluno = $p['nome_estudante'] ?? $p['nome_autor'] ?? 'Estudante';
            $sigla = strtoupper(mb_substr($nomeAluno, 0, 2));
            $titulo = $p['titulo_sugestao'] ?? $p['titulo'] ?? 'Sugestão sem título';
            $turma = $p['turma_curso'] ?? $p['turma'] ?? 'Estudante';
            $categoria = ucfirst(str_replace('_', ' ', $p['tipo_sugestao'] ?? $p['categoria_sugerida'] ?? 'Geral'));
            
            $statusRaw = $p['status'] ?? 'pendente';
            $statusLabel = match($statusRaw) {
                'pendente' => 'Nova',
                'em_analise' => 'Em análise',
                'aprovada' => 'Aprovada',
                'descartada' => 'Recusada',
                default => ucfirst($statusRaw)
            };

            $descCompleta = $p['descricao'] ?? '';
            $justificativa = '';
            if (str_contains($descCompleta, '[POR QUE ESSA PAUTA É IMPORTANTE?]')) {
                $partes = explode('[POR QUE ESSA PAUTA É IMPORTANTE?]', $descCompleta);
                $descExibida = trim($partes[0]);
                $justificativa = trim($partes[1] ?? '');
            } else {
                $descExibida = $descCompleta;
                $justificativa = $p['justificativa'] ?? 'Importância comunitária para os estudantes do Sesc-Senac.';
            }

            $pautas_lista[] = [
                'id' => $p['id'],
                'sigla' => $sigla,
                'titulo' => $titulo,
                'aluno' => $nomeAluno,
                'categoria' => $categoria,
                'turma' => $turma,
                'data' => ui_date($p['criado_em'] ?? null),
                'status' => $statusLabel,
                'descricao' => $descExibida,
                'justificativa' => $justificativa,
                'ativo' => $idx === 0
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}


$pauta_selecionada = $pautas_lista[0] ?? null;
$tons_pauta = ['Nova' => 'info', 'Em análise' => 'warn', 'Aprovada' => 'ok', 'Recusada' => 'bad'];
?>

<?= ui_page_header('Sugestões de pautas', 'Analise ideias enviadas pela comunidade escolar.') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<nav class="tabs" aria-label="Filtrar sugestões">
  <a href="?status=todas" class="tab <?= $filtro_status === 'todas' ? 'is-active' : '' ?>">Todas</a>
  <a href="?status=novas" class="tab <?= $filtro_status === 'novas' ? 'is-active' : '' ?>">Novas</a>
  <a href="?status=analise" class="tab <?= $filtro_status === 'analise' ? 'is-active' : '' ?>">Em análise</a>
</nav>

<?php if (empty($pautas_lista)): ?>
  <div class="card">
    <?= ui_empty('inbox', 'Nenhuma sugestão por aqui', $filtro_status === 'todas' ? 'Quando os estudantes enviarem pautas pelo portal, elas aparecerão nesta caixa de entrada.' : 'Nenhuma sugestão com este status.') ?>
  </div>
<?php else: ?>
  <div class="split split--even" style="grid-template-columns: minmax(0, 5fr) minmax(0, 7fr)">
    <div class="stack stack--sm" id="lista-pautas">
      <?php foreach ($pautas_lista as $p): ?>
        <button type="button" class="inbox-item <?= $p['ativo'] ? 'is-selected' : '' ?>" id="pauta-item-<?= (int)$p['id'] ?>" data-pauta="<?= e((string)json_encode($p, JSON_UNESCAPED_UNICODE)) ?>">
          <span class="avatar avatar--soft"><?= e($p['sigla']) ?></span>
          <span class="grow">
            <span class="row row--between" style="align-items:flex-start;gap:8px">
              <span class="cell__title"><?= e($p['titulo']) ?></span>
              <span class="faint text-xs t-nowrap"><?= e($p['data']) ?></span>
            </span>
            <span class="cell__meta" style="display:block;margin-top:2px"><?= e($p['aluno']) ?> · <?= e($p['categoria']) ?></span>
            <span style="display:block;margin-top:8px"><?= ui_badge($p['status'], $tons_pauta[$p['status']] ?? 'neutral') ?></span>
          </span>
        </button>
      <?php endforeach; ?>
    </div>

    <?php if ($pauta_selecionada): ?>
      <div class="card sticky-panel" id="painel-detalhe">
        <div class="card__body stack">
          <div>
            <span id="detalhe-status"><?= ui_badge($pauta_selecionada['status'], $tons_pauta[$pauta_selecionada['status']] ?? 'neutral') ?></span>
            <h2 id="detalhe-titulo" style="font-size:22px;line-height:1.3;font-weight:700;margin-top:10px"><?= e($pauta_selecionada['titulo']) ?></h2>
            <p class="muted text-sm" style="margin-top:4px">Enviado por <strong id="detalhe-autor"><?= e($pauta_selecionada['aluno']) ?></strong> · <span id="detalhe-data"><?= e($pauta_selecionada['data']) ?></span></p>
          </div>

          <div class="info-grid">
            <div><small>Estudante</small><span class="strong" id="detalhe-aluno"><?= e($pauta_selecionada['aluno']) ?></span></div>
            <div><small>Categoria</small><span class="strong" id="detalhe-categoria"><?= e($pauta_selecionada['categoria']) ?></span></div>
            <div><small>Turma</small><span class="strong" id="detalhe-turma"><?= e($pauta_selecionada['turma']) ?></span></div>
          </div>

          <div>
            <h4 class="strong" style="margin-bottom:6px">Descrição da sugestão</h4>
            <p class="muted" style="white-space:pre-line;line-height:1.65" id="detalhe-descricao"><?= e($pauta_selecionada['descricao'] ?? '') ?></p>
          </div>
          <div>
            <h4 class="strong" style="margin-bottom:6px">Por que essa pauta é importante?</h4>
            <p class="muted" style="white-space:pre-line;line-height:1.65" id="detalhe-justificativa"><?= e($pauta_selecionada['justificativa'] ?? '') ?></p>
          </div>
        </div>

        <div class="card__foot" style="justify-content:flex-end">
          <form method="POST" action="" class="row row--wrap">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="pauta_id" id="form-pauta-id" value="<?= (int)$pauta_selecionada['id'] ?>">
            <button type="submit" name="acao_pauta" value="recusada" class="btn btn--danger">Recusar</button>
            <button type="submit" name="acao_pauta" value="em_analise" class="btn">Marcar em análise</button>
          </form>
          <a href="?transformar_id=<?= (int)$pauta_selecionada['id'] ?>" id="link-transformar" class="btn btn--primary"><?= ui_icon('pen', 'ic--sm') ?> Transformar em reportagem</a>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script>
  (function () {
    var tons = { 'Nova': 'badge--info', 'Em análise': 'badge--warn', 'Aprovada': 'badge--ok', 'Recusada': 'badge--bad' };
    var lista = document.getElementById('lista-pautas');
    function set(id, txt) { document.getElementById(id).textContent = txt || ''; }
    lista.addEventListener('click', function (ev) {
      var item = ev.target.closest('.inbox-item');
      if (!item) { return; }
      var p = JSON.parse(item.getAttribute('data-pauta'));
      var st = document.getElementById('detalhe-status');
      st.innerHTML = '<span class="badge ' + (tons[p.status] || '') + '"></span>';
      st.firstChild.textContent = p.status;
      set('detalhe-titulo', p.titulo); set('detalhe-autor', p.aluno); set('detalhe-data', p.data);
      set('detalhe-aluno', p.aluno); set('detalhe-categoria', p.categoria); set('detalhe-turma', p.turma);
      set('detalhe-descricao', p.descricao); set('detalhe-justificativa', p.justificativa);
      document.getElementById('form-pauta-id').value = p.id;
      document.getElementById('link-transformar').href = '?transformar_id=' + encodeURIComponent(p.id);
      Array.prototype.forEach.call(lista.querySelectorAll('.inbox-item'), function (el) { el.classList.remove('is-selected'); });
      item.classList.add('is-selected');
      if (window.innerWidth < 1024) { document.getElementById('painel-detalhe').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  })();
  </script>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
