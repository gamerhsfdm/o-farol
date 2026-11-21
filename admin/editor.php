<?php
/**
 * O FAROL - Editor de Notícias
 * Folha de redação (título, subtítulo, corpo) + painel lateral de publicação, capa e URL.
 */
declare(strict_types=1);
$admin_title = "Editor de notícias";
$admin_breadcrumb = ((int)($_GET['id'] ?? 0) > 0) ? "Editar reportagem" : "Nova reportagem";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

$id = (int)($_GET['id'] ?? 0);

// Lista de categorias do banco
$lista_categorias = [];
if ($pdo) {
    try {
        $stmtCat = $pdo->query("SELECT id, nome, slug FROM categorias WHERE ativo = 1 ORDER BY nome ASC");
        $lista_categorias = $stmtCat->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

// Lista de autores
$lista_autores = [];
if ($pdo) {
    try {
        $stmtAut = $pdo->query("SELECT id, nome, cargo, turma FROM autores WHERE ativo = 1 ORDER BY nome ASC");
        $lista_autores = $stmtAut->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

// Valores iniciais de uma nova reportagem (em branco) ou carregamento de notícia existente
$noticia = [
    'id' => 0,
    'categoria_id' => 0,
    'categoria_nome' => '',
    'titulo' => '',
    'subtitulo' => '',
    'conteudo' => '',
    'autor_id' => 0,
    'autor_nome' => '',
    'tempo_leitura' => 5,
    'professor_orientador' => '',
    'imagem_capa' => '',
    'tags' => '',
    'slug' => '',
    'status' => 'rascunho',
    'posicao_homepage' => 'nenhuma'
];
if ($id === 0 && isset($_GET['cat_id'])) {
    $noticia['categoria_id'] = (int)$_GET['cat_id'];
}

// Se tiver ID, busca do banco
if ($id > 0 && $pdo) {
    try {
        $stmtNot = $pdo->prepare("SELECT n.*, c.nome as categoria_nome FROM noticias n JOIN categorias c ON n.categoria_id = c.id WHERE n.id = ?");
        $stmtNot->execute([$id]);
        $bdNot = $stmtNot->fetch();
        if ($bdNot) {
            $noticia = array_merge($noticia, $bdNot);
            // Autor vinculado à reportagem (exibição)
            $stmtAu = $pdo->prepare("SELECT a.id, a.nome FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = ? LIMIT 1");
            $stmtAu->execute([$id]);
            $autorVinc = $stmtAu->fetch();
            if ($autorVinc) {
                $noticia['autor_id'] = $autorVinc['id'];
                $noticia['autor_nome'] = $autorVinc['nome'];
            }
        } else {
            $erro = "Reportagem não encontrada.";
            $id = 0;
        }
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}

// Processar Salvamento via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_salvar'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = 'Não foi possível validar a solicitação. Atualize a página e tente novamente.';
    } else {
    $acao = $_POST['acao_salvar']; // 'rascunho', 'revisao', 'publicar'
    $status_novo = match($acao) {
        'publicar' => 'publicado',
        'revisao' => 'revisao',
        default => 'rascunho'
    };

    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 1);
    $autor_id = (int)($_POST['autor_id'] ?? 1);
    $orientador = trim($_POST['professor_orientador'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = slugify($titulo);
    }
    $imagem_capa = trim($_POST['imagem_capa'] ?? '');
    $tempo_leitura = (int)($_POST['tempo_leitura'] ?? 5);

    if (empty($titulo)) {
        $erro = "O título da reportagem não pode ficar em branco.";
    } else {
        if ($pdo) {
            try {
                if ($id > 0) {
                    $stmtUp = $pdo->prepare("UPDATE noticias SET categoria_id = ?, titulo = ?, subtitulo = ?, conteudo = ?, imagem_capa = ?, slug = ?, tempo_leitura = ?, professor_orientador = ?, status = ?, atualizado_em = NOW() WHERE id = ?");
                    $stmtUp->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $imagem_capa, $slug, $tempo_leitura, $orientador, $status_novo, $id]);
                    $mensagem = "Reportagem atualizada com sucesso como " . strtoupper($status_novo) . "!";
                } else {
                    $stmtIn = $pdo->prepare("INSERT INTO noticias (categoria_id, titulo, subtitulo, conteudo, imagem_capa, slug, tempo_leitura, professor_orientador, status, publicado_em, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'publicado' THEN NOW() ELSE NULL END, NOW())");
                    $stmtIn->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $imagem_capa, $slug, $tempo_leitura, $orientador, $status_novo, $status_novo]);
                    $id = (int)$pdo->lastInsertId();
                    
                    // Vincula autor na tabela pivô
                    if ($autor_id > 0) {
                        $stmtNa = $pdo->prepare("INSERT IGNORE INTO noticia_autores (noticia_id, autor_id) VALUES (?, ?)");
                        $stmtNa->execute([$id, $autor_id]);
                    }
                    $mensagem = "Reportagem criada e salva com sucesso como " . strtoupper($status_novo) . "!";
                }
                // Atualiza array em memória
                $noticia['titulo'] = $titulo;
                $noticia['subtitulo'] = $subtitulo;
                $noticia['conteudo'] = $conteudo;
                $noticia['categoria_id'] = $categoria_id;
                $noticia['imagem_capa'] = $imagem_capa;
                $noticia['slug'] = $slug;
                $noticia['status'] = $status_novo;
            } catch (Exception $e) {
                $erro = "Erro ao salvar no banco: " . $e->getMessage();
            }
        } else {
            $mensagem = "Reportagem salva com sucesso (modo visual/demonstração)!";
        }
    }
    }
}
?>

<form method="POST" action="" id="form-editor" data-dirty-guard>
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="id" value="<?= $id ?>">

  <!-- Barra de ações do editor -->
  <div class="editor-bar">
    <div class="row row--wrap">
      <a href="<?= BASE_URL ?>/admin/noticias.php" class="btn btn--ghost btn--sm"><?= ui_icon('arrow-l', 'ic--sm') ?> Notícias</a>
      <?= str_replace('<span ', '<span data-dirty-flag ', $id > 0 ? ui_news_status((string)($noticia['status'] ?? 'rascunho')) : ui_badge('Nova reportagem', 'brand')) ?>
    </div>
    <div class="editor-bar__actions">
      <?php if ($id > 0 && !empty($noticia['slug'])): ?>
        <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode((string)$noticia['slug']) ?>" target="_blank" rel="noopener" class="btn btn--sm"><?= ui_icon('eye', 'ic--sm') ?> Visualizar</a>
      <?php endif; ?>
      <?php if ($id > 0): ?>
        <a href="<?= BASE_URL ?>/admin/history.php?id=<?= $id ?>" class="btn btn--sm"><?= ui_icon('history', 'ic--sm') ?> Histórico</a>
      <?php endif; ?>
      <button type="submit" name="acao_salvar" value="rascunho" class="btn btn--sm"><?= ui_icon('save', 'ic--sm') ?> Salvar rascunho</button>
      <button type="submit" name="acao_salvar" value="revisao" class="btn btn--sm"><?= ui_icon('send', 'ic--sm') ?> Enviar para revisão</button>
      <button type="submit" name="acao_salvar" value="publicar" class="btn btn--primary btn--sm">Publicar</button>
    </div>
  </div>

  <?= ui_alerts($mensagem, $erro) ?>
  <?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>
  <?php if ($pdo && empty($lista_categorias) && $erro_carga === ''): ?>
    <?= ui_alerts('', '', 'Nenhuma categoria ativa encontrada. Cadastre uma categoria antes de salvar a reportagem.') ?>
  <?php endif; ?>

  <div class="editor-layout">

    <!-- Folha de redação -->
    <div class="paper">
      <div class="paper__eyebrow" id="eyebrow-categoria"><?= e(mb_strtoupper((string)$noticia['categoria_nome'])) ?></div>

      <textarea name="titulo" rows="1" placeholder="Título da reportagem" required data-autogrow class="paper__title" aria-label="Título"><?= e($noticia['titulo']) ?></textarea>
      <textarea name="subtitulo" rows="1" placeholder="Subtítulo ou resumo da reportagem" data-autogrow class="paper__sub" aria-label="Subtítulo"><?= e($noticia['subtitulo']) ?></textarea>

      <div class="paper__byline">
        <span class="avatar avatar--sm avatar--soft" id="byline-avatar"><?= e(ui_initials($noticia['autor_nome'] ?: '·')) ?></span>
        <span>Por <strong id="byline-nome"><?= e($noticia['autor_nome'] ?: 'autor não definido') ?></strong></span>
        <span aria-hidden="true">·</span>
        <span><span id="byline-leitura"><?= e((string)$noticia['tempo_leitura']) ?></span> min de leitura</span>
      </div>

      <div class="rte" role="toolbar" aria-label="Formatação do texto">
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('p')" title="Parágrafo" aria-label="Parágrafo"><?= ui_icon('pilcrow') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('h2')" title="Intertítulo" aria-label="Intertítulo"><?= ui_icon('heading') ?></button>
        <span class="rte__sep"></span>
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('bold')" title="Negrito" aria-label="Negrito"><?= ui_icon('bold') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('italic')" title="Itálico" aria-label="Itálico"><?= ui_icon('italic') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('createLink')" title="Inserir link" aria-label="Inserir link"><?= ui_icon('link') ?></button>
        <span class="rte__sep"></span>
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('list')" title="Lista" aria-label="Lista"><?= ui_icon('list') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="formatDoc('quote')" title="Citação" aria-label="Citação"><?= ui_icon('quote') ?></button>
      </div>
      <textarea name="conteudo" id="corpo-materia" placeholder="Escreva o texto completo da matéria..." class="textarea editor-body" aria-label="Corpo da matéria"><?= e($noticia['conteudo']) ?></textarea>
      <p class="hint" style="margin-top:8px">O texto é salvo em HTML. Use a barra acima para aplicar formatação à seleção.</p>
    </div>

    <!-- Painel lateral -->
    <div class="card">
      <div class="side-section">
        <div class="side-section__title">Publicação</div>
        <div class="field">
          <label class="label" for="campo-categoria">Categoria</label>
          <select name="categoria_id" id="campo-categoria" class="select">
            <?php foreach ($lista_categorias as $cat): ?>
              <option value="<?= (int)$cat['id'] ?>" <?= ((int)$cat['id'] === (int)$noticia['categoria_id']) ? 'selected' : '' ?>><?= e($cat['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label" for="campo-autor">Autor</label>
          <select name="autor_id" id="campo-autor" class="select">
            <?php foreach ($lista_autores as $aut): ?>
              <option value="<?= (int)$aut['id'] ?>" data-nome="<?= e($aut['nome']) ?>" <?= ((int)$aut['id'] === (int)$noticia['autor_id']) ? 'selected' : '' ?>><?= e($aut['nome']) ?> (<?= e((string)($aut['turma'] ?? $aut['cargo'] ?? '')) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label class="label" for="campo-orientador">Professor orientador</label>
          <input type="text" name="professor_orientador" id="campo-orientador" value="<?= e((string)$noticia['professor_orientador']) ?>" class="input">
        </div>
        <div class="field">
          <label class="label" for="campo-leitura">Tempo de leitura <small>(minutos)</small></label>
          <input type="number" name="tempo_leitura" id="campo-leitura" value="<?= e((string)$noticia['tempo_leitura']) ?>" min="1" max="60" class="input">
        </div>
      </div>

      <div class="side-section">
        <div class="side-section__title">Imagem de capa</div>
        <div class="cover-box">
          <img src="<?= BASE_URL ?>/<?= e((string)$noticia['imagem_capa']) ?>" alt="Pré-visualização da capa" id="preview-capa" <?= empty($noticia['imagem_capa']) ? 'hidden' : '' ?>>
          <div class="cover-box__empty" id="capa-vazia" <?= !empty($noticia['imagem_capa']) ? 'hidden' : '' ?>>Sem imagem de capa</div>
          <input type="text" name="imagem_capa" id="campo-capa" value="<?= e((string)$noticia['imagem_capa']) ?>" placeholder="Caminho ou URL da imagem…" class="input" aria-label="Caminho da imagem de capa">
        </div>
        <a href="<?= BASE_URL ?>/admin/media.php" target="_blank" rel="noopener" class="link text-sm">Abrir biblioteca de mídia <?= ui_icon('external', 'ic--sm') ?></a>
      </div>

      <div class="side-section">
        <div class="side-section__title">URL amigável</div>
        <div class="field">
          <label class="label" for="campo-slug">Slug</label>
          <input type="text" name="slug" id="campo-slug" value="<?= e((string)$noticia['slug']) ?>" placeholder="gerado a partir do título" class="input mono">
          <span class="hint">/noticia/<span id="slug-preview"><?= e((string)$noticia['slug']) ?></span></span>
        </div>
      </div>
    </div>

  </div>
</form>

<script>
(function () {
  var base = '<?= BASE_URL ?>';
  var ta = document.getElementById('corpo-materia');

  function wrap(open, close, placeholder) {
    var s = ta.selectionStart, en = ta.selectionEnd, v = ta.value;
    var sel = v.slice(s, en) || placeholder;
    ta.value = v.slice(0, s) + open + sel + close + v.slice(en);
    ta.focus();
    ta.selectionStart = s + open.length;
    ta.selectionEnd = s + open.length + sel.length;
    ta.dispatchEvent(new Event('input', { bubbles: true }));
  }
  window.formatDoc = function (cmd) {
    if (cmd === 'bold') { wrap('<strong>', '</strong>', 'texto'); }
    else if (cmd === 'italic') { wrap('<em>', '</em>', 'texto'); }
    else if (cmd === 'h2') { wrap('<h2>', '</h2>', 'Intertítulo'); }
    else if (cmd === 'p') { wrap('<p>', '</p>', 'Parágrafo'); }
    else if (cmd === 'quote') { wrap('<blockquote>', '</blockquote>', 'Citação'); }
    else if (cmd === 'list') { wrap('<ul>\n  <li>', '</li>\n</ul>', 'item'); }
    else if (cmd === 'createLink') {
      var url = window.prompt('Digite o link (URL):');
      if (url) { wrap('<a href="' + url.replace(/"/g, '&quot;') + '">', '</a>', 'texto do link'); }
    }
  };

  // Categoria -> chapéu da folha
  var cat = document.getElementById('campo-categoria');
  var eyebrow = document.getElementById('eyebrow-categoria');
  if (cat && eyebrow) {
    cat.addEventListener('change', function () {
      eyebrow.textContent = cat.options[cat.selectedIndex] ? cat.options[cat.selectedIndex].text.toUpperCase() : '';
    });
    if (!eyebrow.textContent.trim() && cat.options[cat.selectedIndex]) { eyebrow.textContent = cat.options[cat.selectedIndex].text.toUpperCase(); }
  }

  // Autor / tempo de leitura -> assinatura
  var aut = document.getElementById('campo-autor');
  function initials(n) { var p = n.trim().split(/\s+/); return ((p[0] || '?')[0] + (p.length > 1 ? p[p.length - 1][0] : '')).toUpperCase(); }
  if (aut) {
    aut.addEventListener('change', function () {
      var o = aut.options[aut.selectedIndex]; var n = o ? (o.getAttribute('data-nome') || '') : '';
      document.getElementById('byline-nome').textContent = n || 'autor não definido';
      document.getElementById('byline-avatar').textContent = n ? initials(n) : '·';
    });
  }
  var lei = document.getElementById('campo-leitura');
  if (lei) { lei.addEventListener('input', function () { document.getElementById('byline-leitura').textContent = lei.value || '–'; }); }

  // Capa -> pré-visualização
  var capa = document.getElementById('campo-capa'), img = document.getElementById('preview-capa'), vazio = document.getElementById('capa-vazia');
  function setCapa() {
    var v = capa.value.trim();
    if (!v) { img.hidden = true; vazio.hidden = false; return; }
    img.src = /^https?:\/\//i.test(v) ? v : base + '/' + v.replace(/^\/+/, '');
    img.hidden = false; vazio.hidden = true;
  }
  if (capa) { capa.addEventListener('input', setCapa); img.addEventListener('error', function () { img.hidden = true; vazio.hidden = false; }); }

  // Slug -> pré-visualização da URL
  var slug = document.getElementById('campo-slug');
  if (slug) { slug.addEventListener('input', function () { document.getElementById('slug-preview').textContent = slug.value; }); }
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
