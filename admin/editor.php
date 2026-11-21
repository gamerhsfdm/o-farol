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

// Lista de mídias para inserção direta no corpo
$lista_midias = [];
if ($pdo) {
    try {
        $stmtMid = $pdo->query("SELECT id, nome_arquivo, caminho, alt_text, legenda, credito FROM media_library ORDER BY id DESC LIMIT 40");
        $lista_midias = $stmtMid->fetchAll();
    } catch (Exception $e) {}
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
    $conteudo = sanitize_html_content($_POST['conteudo'] ?? '');
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
                    $stmtUp = $pdo->prepare("UPDATE noticias SET categoria_id = ?, titulo = ?, subtitulo = ?, conteudo = ?, imagem_capa = ?, slug = ?, tempo_leitura = ?, professor_orientador = ?, status = ?, publicado_em = CASE WHEN ? = 'publicado' AND publicado_em IS NULL THEN NOW() ELSE publicado_em END, atualizado_em = NOW() WHERE id = ?");
                    $stmtUp->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $imagem_capa, $slug, $tempo_leitura, $orientador, $status_novo, $status_novo, $id]);

                    // Sincroniza autor na tabela pivô
                    if ($autor_id > 0) {
                        $stmtCheckNa = $pdo->prepare("SELECT COUNT(*) FROM noticia_autores WHERE noticia_id = ?");
                        $stmtCheckNa->execute([$id]);
                        if ((int)$stmtCheckNa->fetchColumn() > 0) {
                            $stmtUpNa = $pdo->prepare("UPDATE noticia_autores SET autor_id = ? WHERE noticia_id = ? LIMIT 1");
                            $stmtUpNa->execute([$autor_id, $id]);
                        } else {
                            $stmtInNa = $pdo->prepare("INSERT INTO noticia_autores (noticia_id, autor_id) VALUES (?, ?)");
                            $stmtInNa->execute([$id, $autor_id]);
                        }
                    }

                    // Registra versão no histórico
                    try {
                        $stmtVer = $pdo->prepare("SELECT COALESCE(MAX(versao), 0) + 1 FROM noticia_versoes WHERE noticia_id = ?");
                        $stmtVer->execute([$id]);
                        $proximaVersao = (int)$stmtVer->fetchColumn();
                        $uid = $_SESSION['usuario_id'] ?? null;
                        $stmtInVer = $pdo->prepare("INSERT INTO noticia_versoes (noticia_id, usuario_id, titulo, subtitulo, conteudo, imagem_capa, versao, motivo_alteracao, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, 'Edição no CMS', NOW())");
                        $stmtInVer->execute([$id, $uid, $titulo, $subtitulo, $conteudo, $imagem_capa, $proximaVersao]);
                    } catch (Exception $eVer) {
                        // Não interrompe fluxo principal se histórico falhar
                    }

                    log_audit('editar_noticia', 'noticias', $id, "Reportagem '{$titulo}' atualizada");
                    $mensagem = "Reportagem atualizada com sucesso como " . strtoupper($status_novo) . "!";
                } else {
                    $stmtIn = $pdo->prepare("INSERT INTO noticias (categoria_id, titulo, subtitulo, conteudo, imagem_capa, slug, tempo_leitura, professor_orientador, status, publicado_em, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'publicado' THEN NOW() ELSE NULL END, NOW())");
                    $stmtIn->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $imagem_capa, $slug, $tempo_leitura, $orientador, $status_novo, $status_novo]);
                    $id = (int)$pdo->lastInsertId();
                    
                    // Vincula autor na tabela pivô
                    if ($autor_id > 0) {
                        $stmtNa = $pdo->prepare("INSERT INTO noticia_autores (noticia_id, autor_id) VALUES (?, ?)");
                        $stmtNa->execute([$id, $autor_id]);
                    }

                    // Versão inicial #1
                    try {
                        $uid = $_SESSION['usuario_id'] ?? null;
                        $stmtInVer = $pdo->prepare("INSERT INTO noticia_versoes (noticia_id, usuario_id, titulo, subtitulo, conteudo, imagem_capa, versao, motivo_alteracao, criado_em) VALUES (?, ?, ?, ?, ?, ?, 1, 'Versão inicial', NOW())");
                        $stmtInVer->execute([$id, $uid, $titulo, $subtitulo, $conteudo, $imagem_capa]);
                    } catch (Exception $eVer) {}

                    log_audit('criar_noticia', 'noticias', $id, "Nova reportagem '{$titulo}' criada");
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
      
      <!-- Seletor de Modo de Exibição -->
      <div class="preview-view-tabs" role="group" aria-label="Modo de Exibição">
        <button type="button" class="preview-tab-btn is-active" id="btn-mode-edit" onclick="setEditorMode('edit')">Editar</button>
        <button type="button" class="preview-tab-btn" id="btn-mode-split" onclick="setEditorMode('split')">Dividido</button>
        <button type="button" class="preview-tab-btn" id="btn-mode-preview" onclick="setEditorMode('preview')">Só Preview</button>
      </div>
    </div>
    <div class="editor-bar__actions">
      <?php if ($id > 0): ?>
        <a href="<?= BASE_URL ?>/admin/preview.php?id=<?= $id ?>" target="_blank" rel="noopener" class="btn btn--sm"><?= ui_icon('external', 'ic--sm') ?> Preview em nova aba</a>
      <?php endif; ?>
      <?php if ($id > 0 && !empty($noticia['slug']) && $noticia['status'] === 'publicado'): ?>
        <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode((string)$noticia['slug']) ?>" target="_blank" rel="noopener" class="btn btn--sm"><?= ui_icon('eye', 'ic--sm') ?> Ver no site</a>
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

  <!-- Avisos Visuais de Pendências Editoriais -->
  <div id="box-avisos-pendencias" class="editorial-warnings-bar" style="display:none">
    <span style="font-weight:700">Avisos de publicação:</span>
    <span id="pendencias-lista" style="display:inline-flex;gap:6px;flex-wrap:wrap"></span>
  </div>

  <div class="editor-layout" id="editor-main-layout">

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
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('formatBlock', '<p>')" title="Parágrafo" aria-label="Parágrafo"><?= ui_icon('pilcrow') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('formatBlock', '<h2>')" title="Intertítulo H2" aria-label="Intertítulo H2"><?= ui_icon('heading') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('formatBlock', '<h3>')" title="Subtítulo H3" aria-label="Subtítulo H3"><strong>H3</strong></button>
        <span class="rte__sep"></span>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('bold')" title="Negrito (Ctrl+B)" aria-label="Negrito"><?= ui_icon('bold') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('italic')" title="Itálico (Ctrl+I)" aria-label="Itálico"><?= ui_icon('italic') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="insertLinkPrompt()" title="Inserir Link (Ctrl+K)" aria-label="Inserir link"><?= ui_icon('link') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('unlink')" title="Remover Link" aria-label="Remover link"><?= ui_icon('x', 'ic--sm') ?></button>
        <span class="rte__sep"></span>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('insertUnorderedList')" title="Lista com Marcadores" aria-label="Lista"><?= ui_icon('list') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('insertOrderedList')" title="Lista Numerada" aria-label="Lista numerada">1.</button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('formatBlock', '<blockquote>')" title="Citação Editorial" aria-label="Citação"><?= ui_icon('quote') ?></button>
        <span class="rte__sep"></span>
        <button type="button" class="icon-btn icon-btn--sm" onclick="openMediaModalForBody()" title="Inserir Imagem da Biblioteca" aria-label="Imagem da biblioteca"><?= ui_icon('image') ?></button>
        <span class="rte__sep"></span>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('undo')" title="Desfazer (Ctrl+Z)" aria-label="Desfazer"><?= ui_icon('arrow-l', 'ic--sm') ?></button>
        <button type="button" class="icon-btn icon-btn--sm" onclick="execCmd('redo')" title="Refazer (Ctrl+Y / Ctrl+Shift+Z)" aria-label="Refazer"><?= ui_icon('arrow-r', 'ic--sm') ?></button>
      </div>

      <!-- Área do Editor Visual (contenteditable) + Sincronização com o textarea oculto/HTML -->
      <div class="wysiwyg-wrapper">
        <div id="wysiwyg-editor" class="wysiwyg-content" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Corpo da matéria"><?= sanitize_html_content($noticia['conteudo']) ?></div>
        <textarea name="conteudo" id="corpo-materia-html" style="display:none" class="textarea editor-body" aria-label="HTML da matéria"><?= e($noticia['conteudo']) ?></textarea>

        <div class="wysiwyg-footer">
          <div class="wysiwyg-counters">
            <span><strong id="contador-palavras">0</strong> palavras</span>
            <span>•</span>
            <span><strong id="contador-caracteres">0</strong> caracteres</span>
            <span>•</span>
            <span>Tempo sugerido: <strong id="sugestao-leitura">1</strong> min</span>
          </div>
          <div>
            <button type="button" id="btn-toggle-html" class="wysiwyg-mode-btn" onclick="toggleHtmlMode()">Ver Código HTML</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Painel de Preview ao Vivo -->
    <div class="preview-pane" id="painel-preview" style="display:none">
      <div class="preview-pane__bar">
        <!-- Abas de Visão (Matéria, Card Capa, Card Editoria) -->
        <div class="preview-view-tabs" role="tablist">
          <button type="button" class="preview-tab-btn is-active" id="tab-view-materia" onclick="setPreviewView('materia')">Página da Matéria</button>
          <button type="button" class="preview-tab-btn" id="tab-view-card-home" onclick="setPreviewView('card_home')">Card na Capa</button>
          <button type="button" class="preview-tab-btn" id="tab-view-card-cat" onclick="setPreviewView('card_categoria')">Card na Editoria</button>
        </div>

        <!-- Seletor de Dispositivo (Desktop, Tablet, Celular) -->
        <div class="preview-device-tabs" role="group" aria-label="Dispositivo">
          <button type="button" class="preview-tab-btn is-active" id="btn-dev-desktop" onclick="setPreviewDevice('desktop')" title="Desktop (1180px)">Desktop</button>
          <button type="button" class="preview-tab-btn" id="btn-dev-tablet" onclick="setPreviewDevice('tablet')" title="Tablet (768px)">Tablet</button>
          <button type="button" class="preview-tab-btn" id="btn-dev-mobile" onclick="setPreviewDevice('mobile')" title="Celular (390px)">Celular</button>
        </div>
      </div>

      <div class="preview-pane__body">
        <div class="preview-viewport-wrap device--desktop" id="preview-viewport-wrap">
          <iframe id="iframe-live-preview" name="iframe-live-preview" class="preview-iframe" title="Pré-visualização da matéria"></iframe>
        </div>
      </div>
    </div>

    <!-- Painel lateral -->
    <div class="card" id="painel-lateral-editor">
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
        <div class="row row--wrap" style="margin-top:6px;gap:8px">
          <button type="button" class="btn btn--sm" onclick="openMediaModalForCover()"><?= ui_icon('image', 'ic--sm') ?> Selecionar da biblioteca</button>
          <a href="<?= BASE_URL ?>/admin/media.php" target="_blank" rel="noopener" class="link text-sm">Biblioteca completa <?= ui_icon('external', 'ic--sm') ?></a>
        </div>
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

<!-- Modal: Inserção de Imagem com Acessibilidade (Alt, Legenda, Crédito Obrigatórios) -->
<div id="modal-inserir-midia" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-inserir-midia">
  <div class="modal__panel" style="max-width:680px">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-inserir-midia">Biblioteca de Mídia — Selecionar Imagem</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    <div class="modal__body stack stack--sm">
      <div style="max-height:240px;overflow-y:auto;display:grid;grid-template-columns:repeat(auto-fill, minmax(110px, 1fr));gap:8px;padding:4px">
        <?php foreach ($lista_midias as $m): ?>
          <button type="button" class="media-item" onclick="selectMediaForModal(<?= e((string)json_encode($m)) ?>)" style="cursor:pointer">
            <img src="<?= BASE_URL ?>/<?= e($m['caminho']) ?>" alt="<?= e($m['alt_text'] ?? '') ?>" class="media-item__img" style="aspect-ratio:1;object-fit:cover">
            <div class="media-item__name truncate-1"><?= e($m['nome_arquivo']) ?></div>
          </button>
        <?php endforeach; ?>
      </div>

      <div class="card" style="padding:14px;background:var(--surface-2);border:1px solid var(--line)">
        <input type="hidden" id="modal-midia-caminho">
        <input type="hidden" id="modal-midia-contexto" value="body"> <!-- 'body' ou 'capa' -->

        <div class="field">
          <label class="label" for="modal-midia-alt">Texto Alternativo (Alt Text) * <small>(Obrigatório para acessibilidade)</small></label>
          <input type="text" id="modal-midia-alt" class="input" placeholder="Descreva a imagem para leitores de tela..." required>
        </div>
        <div id="modal-midia-fields-extras">
          <div class="field">
            <label class="label" for="modal-midia-legenda">Legenda da Imagem *</label>
            <input type="text" id="modal-midia-legenda" class="input" placeholder="Legenda explicativa que acompanha a imagem na matéria..." required>
          </div>
          <div class="field">
            <label class="label" for="modal-midia-credito">Crédito Fotográfico *</label>
            <input type="text" id="modal-midia-credito" class="input" placeholder="Ex: Foto: Marina Prado / Sesc-Senac" required>
          </div>
        </div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="button" class="btn btn--primary" id="btn-confirmar-inserir-midia" onclick="confirmMediaInsertion()">Inserir Imagem</button>
    </div>
  </div>
</div>

<script>
(function () {
  var base = '<?= BASE_URL ?>';
  var csrf = '<?= csrf_token() ?>';
  var form = document.getElementById('form-editor');
  var wysiwyg = document.getElementById('wysiwyg-editor');
  var htmlTextarea = document.getElementById('corpo-materia-html');
  var isHtmlView = false;
  var iframe = document.getElementById('iframe-live-preview');
  var currentView = 'materia';
  var currentDevice = 'desktop';
  var currentMode = 'edit';
  var debounceTimer = null;
  var savedRange = null;

  // 1. Sincronização WYSIWYG <-> Textarea HTML
  function syncWysiwygToTextarea() {
    if (!isHtmlView && wysiwyg && htmlTextarea) {
      htmlTextarea.value = wysiwyg.innerHTML;
    }
  }

  function syncTextareaToWysiwyg() {
    if (isHtmlView && wysiwyg && htmlTextarea) {
      wysiwyg.innerHTML = htmlTextarea.value;
    }
  }

  // 2. Alternar entre Modo Visual e Modo Código HTML
  window.toggleHtmlMode = function () {
    isHtmlView = !isHtmlView;
    var btn = document.getElementById('btn-toggle-html');
    if (isHtmlView) {
      syncWysiwygToTextarea();
      wysiwyg.style.display = 'none';
      htmlTextarea.style.display = 'block';
      if (btn) btn.textContent = 'Voltar para Editor Visual';
    } else {
      syncTextareaToWysiwyg();
      htmlTextarea.style.display = 'none';
      wysiwyg.style.display = 'block';
      if (btn) btn.textContent = 'Ver Código HTML';
    }
    updateWordCount();
    triggerPreviewUpdate(true);
  };

  // 3. Execução de comandos do editor com suporte a seleção preservada
  window.execCmd = function (cmd, val) {
    if (isHtmlView) return;
    wysiwyg.focus();
    document.execCommand(cmd, false, val || null);
    syncWysiwygToTextarea();
    updateWordCount();
    triggerPreviewUpdate(false);
  };

  // Atalhos de teclado (Ctrl+B, Ctrl+I, Ctrl+K, Ctrl+Z, Ctrl+Y)
  wysiwyg.addEventListener('keydown', function (e) {
    if (e.ctrlKey || e.metaKey) {
      if (e.key === 'b' || e.key === 'B') {
        e.preventDefault();
        execCmd('bold');
      } else if (e.key === 'i' || e.key === 'I') {
        e.preventDefault();
        execCmd('italic');
      } else if (e.key === 'k' || e.key === 'K') {
        e.preventDefault();
        insertLinkPrompt();
      }
    }
  });

  // Limpeza de estilos colados de Word / Google Docs
  wysiwyg.addEventListener('paste', function (e) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData('text/html') || (e.clipboardData || window.clipboardData).getData('text/plain');

    if (text) {
      // Limpeza de tags desnecessárias e atributos de estilo
      var temp = document.createElement('div');
      temp.innerHTML = text;

      // Remove comentários Mso e tags <style>
      temp.querySelectorAll('style, script, meta, link').forEach(function(el){ el.remove(); });
      temp.querySelectorAll('*').forEach(function(el){
        el.removeAttribute('style');
        el.removeAttribute('class');
        el.removeAttribute('lang');
      });

      var cleanHtml = temp.innerHTML.replace(/<!--[\s\S]*?-->/g, '');
      document.execCommand('insertHTML', false, cleanHtml);
      syncWysiwygToTextarea();
      updateWordCount();
      triggerPreviewUpdate(false);
    }
  });

  // Inserir link com validação
  window.insertLinkPrompt = function () {
    if (isHtmlView) return;
    var url = window.prompt('Digite a URL do link (ex: https://...):');
    if (url) {
      url = url.trim();
      if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url) && !url.startsWith('/')) {
        url = 'https://' + url;
      }
      execCmd('createLink', url);
    }
  };

  // Contagem de palavras, caracteres e cálculo de tempo de leitura (~200 palavras/min)
  function updateWordCount() {
    var text = wysiwyg.innerText || wysiwyg.textContent || '';
    var words = text.trim().split(/\s+/).filter(function(w){ return w.length > 0; });
    var wordCount = words.length;
    var charCount = text.length;

    var cw = document.getElementById('contador-palavras');
    var cc = document.getElementById('contador-caracteres');
    var cl = document.getElementById('sugestao-leitura');
    if (cw) cw.textContent = wordCount;
    if (cc) cc.textContent = charCount;

    var readingTime = Math.max(1, Math.ceil(wordCount / 200));
    if (cl) cl.textContent = readingTime;

    var campoLeitura = document.getElementById('campo-leitura');
    var bylineLeitura = document.getElementById('byline-leitura');
    // Atualiza automaticamente o campo de minutos se ainda não tiver sido editado manualmente
    if (campoLeitura && (!campoLeitura.value || campoLeitura.dataset.userEdited !== 'true')) {
      campoLeitura.value = readingTime;
      if (bylineLeitura) bylineLeitura.textContent = readingTime;
    }
  }

  document.getElementById('campo-leitura')?.addEventListener('input', function(){
    this.dataset.userEdited = 'true';
    var byline = document.getElementById('byline-leitura');
    if (byline) byline.textContent = this.value || '1';
  });

  wysiwyg.addEventListener('input', function () {
    syncWysiwygToTextarea();
    updateWordCount();
    triggerPreviewUpdate(false);
  });
  htmlTextarea.addEventListener('input', function () {
    syncTextareaToWysiwyg();
    updateWordCount();
    triggerPreviewUpdate(false);
  });

  // 4. Modal de Inserção de Imagem com Acessibilidade (media_library)
  var modalMidia = document.getElementById('modal-inserir-midia');
  window.openMediaModalForBody = function () {
    document.getElementById('modal-midia-contexto').value = 'body';
    document.getElementById('modal-midia-fields-extras').style.display = 'block';
    document.getElementById('modal-midia-caminho').value = '';
    document.getElementById('modal-midia-alt').value = '';
    document.getElementById('modal-midia-legenda').value = '';
    document.getElementById('modal-midia-credito').value = 'Redação O Farol';
    
    // Salva seleção atual do editor
    var sel = window.getSelection();
    if (sel.getRangeAt && sel.rangeCount) {
      savedRange = sel.getRangeAt(0);
    }

    if (modalMidia) modalMidia.classList.add('is-open');
  };

  window.openMediaModalForCover = function () {
    document.getElementById('modal-midia-contexto').value = 'capa';
    document.getElementById('modal-midia-fields-extras').style.display = 'none';
    document.getElementById('modal-midia-caminho').value = '';
    document.getElementById('modal-midia-alt').value = '';
    if (modalMidia) modalMidia.classList.add('is-open');
  };

  window.selectMediaForModal = function (media) {
    document.getElementById('modal-midia-caminho').value = media.caminho;
    document.getElementById('modal-midia-alt').value = media.alt_text || media.nome_arquivo || '';
    document.getElementById('modal-midia-legenda').value = media.legenda || '';
    document.getElementById('modal-midia-credito').value = media.credito || 'Redação O Farol';
  };

  window.confirmMediaInsertion = function () {
    var caminho = document.getElementById('modal-midia-caminho').value;
    var alt = document.getElementById('modal-midia-alt').value.trim();
    var legenda = document.getElementById('modal-midia-legenda').value.trim();
    var credito = document.getElementById('modal-midia-credito').value.trim();
    var contexto = document.getElementById('modal-midia-contexto').value;

    if (!caminho) {
      alert('Selecione uma imagem na lista acima.');
      return;
    }
    if (!alt) {
      alert('O texto alternativo (alt) é obrigatório para acessibilidade.');
      return;
    }

    if (contexto === 'capa') {
      var campoCapa = document.getElementById('campo-capa');
      if (campoCapa) {
        campoCapa.value = caminho;
        campoCapa.dispatchEvent(new Event('input', { bubbles: true }));
      }
      modalMidia.classList.remove('is-open');
      return;
    }

    // Inserção no corpo da matéria como <figure><img ...><figcaption>...</figcaption></figure>
    if (!legenda) {
      alert('A legenda da imagem é obrigatória para matérias editoriais.');
      return;
    }

    var imgUrl = (caminho.startsWith('http://') || caminho.startsWith('https://')) ? caminho : base + '/' + caminho.replace(/^\/+/, '');
    var figureHtml = '<figure class="editorial-body-figure"><img src="' + imgUrl + '" alt="' + alt.replace(/"/g, '&quot;') + '"><figcaption>' + legenda + (credito ? ' <span class="credito">(' + credito + ')</span>' : '') + '</figcaption></figure><p></p>';

    wysiwyg.focus();
    if (savedRange) {
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(savedRange);
    }
    document.execCommand('insertHTML', false, figureHtml);
    syncWysiwygToTextarea();
    updateWordCount();
    triggerPreviewUpdate(true);
    modalMidia.classList.remove('is-open');
  };

  // 5. Controle de Modos de Exibição (edit, split, preview)
  window.setEditorMode = function (mode) {
    currentMode = mode;
    var layout = document.getElementById('editor-main-layout');
    var paper = document.querySelector('.paper');
    var prevPane = document.getElementById('painel-preview');
    var btnEdit = document.getElementById('btn-mode-edit');
    var btnSplit = document.getElementById('btn-mode-split');
    var btnPrev = document.getElementById('btn-mode-preview');

    [btnEdit, btnSplit, btnPrev].forEach(function(b){ b?.classList.remove('is-active'); });

    if (mode === 'split') {
      btnSplit?.classList.add('is-active');
      if (paper) paper.style.display = 'block';
      if (prevPane) prevPane.style.display = 'flex';
      if (layout) layout.className = 'editor-layout editor-layout--split';
      triggerPreviewUpdate(true);
    } else if (mode === 'preview') {
      btnPrev?.classList.add('is-active');
      if (paper) paper.style.display = 'none';
      if (prevPane) prevPane.style.display = 'flex';
      if (layout) layout.className = 'editor-layout editor-layout--preview-only';
      triggerPreviewUpdate(true);
    } else {
      btnEdit?.classList.add('is-active');
      if (paper) paper.style.display = 'block';
      if (prevPane) prevPane.style.display = 'none';
      if (layout) layout.className = 'editor-layout';
    }
  };

  // 6. Seletor de Visão do Preview (Matéria, Card Capa, Card Editoria)
  window.setPreviewView = function (v) {
    currentView = v;
    ['tab-view-materia', 'tab-view-card-home', 'tab-view-card-cat'].forEach(function(id){
      document.getElementById(id)?.classList.remove('is-active');
    });
    if (v === 'materia') document.getElementById('tab-view-materia')?.classList.add('is-active');
    if (v === 'card_home') document.getElementById('tab-view-card-home')?.classList.add('is-active');
    if (v === 'card_categoria') document.getElementById('tab-view-card-cat')?.classList.add('is-active');
    triggerPreviewUpdate(true);
  };

  // 7. Seletor de Dispositivo
  window.setPreviewDevice = function (d) {
    currentDevice = d;
    var wrap = document.getElementById('preview-viewport-wrap');
    ['btn-dev-desktop', 'btn-dev-tablet', 'btn-dev-mobile'].forEach(function(id){
      document.getElementById(id)?.classList.remove('is-active');
    });
    if (d === 'desktop') { document.getElementById('btn-dev-desktop')?.classList.add('is-active'); if (wrap) wrap.className = 'preview-viewport-wrap device--desktop'; }
    if (d === 'tablet') { document.getElementById('btn-dev-tablet')?.classList.add('is-active'); if (wrap) wrap.className = 'preview-viewport-wrap device--tablet'; }
    if (d === 'mobile') { document.getElementById('btn-dev-mobile')?.classList.add('is-active'); if (wrap) wrap.className = 'preview-viewport-wrap device--mobile'; }
  };

  // 8. Atualização com Debounce de 600ms e Preservação da Rolagem
  function triggerPreviewUpdate(immediate) {
    if (currentMode === 'edit') return;
    clearTimeout(debounceTimer);
    if (immediate) {
      updateLivePreview();
    } else {
      debounceTimer = setTimeout(updateLivePreview, 600);
    }
    checkEditorialWarnings();
  }

  function updateLivePreview() {
    syncWysiwygToTextarea();
    var fd = new FormData(form);
    fd.set('view_mode', currentView);

    // Salva scroll anterior do iframe se acessível
    var prevScroll = 0;
    try {
      if (iframe && iframe.contentWindow) {
        prevScroll = iframe.contentWindow.scrollY || 0;
      }
    } catch(e) {}

    fetch(base + '/admin/preview.php', {
      method: 'POST',
      body: fd
    })
    .then(function(res){ return res.text(); })
    .then(function(html){
      if (iframe) {
        iframe.srcdoc = html;
        iframe.onload = function() {
          try {
            if (iframe.contentWindow && prevScroll > 0) {
              iframe.contentWindow.scrollTo(0, prevScroll);
            }
          } catch(e) {}
        };
      }
    })
    .catch(function(err){ console.error('Erro no live preview:', err); });
  }

  // 9. Avisos de Pendências de Publicação
  function checkEditorialWarnings() {
    var tit = (form.querySelector('[name=titulo]')?.value || '').trim();
    var sub = (form.querySelector('[name=subtitulo]')?.value || '').trim();
    var capa = (form.querySelector('[name=imagem_capa]')?.value || '').trim();
    var aut = (form.querySelector('[name=autor_id]')?.value || '');

    var box = document.getElementById('box-avisos-pendencias');
    var lista = document.getElementById('pendencias-lista');
    if (!box || !lista) return;

    var warnings = [];
    if (!capa) warnings.push('Sem imagem de capa');
    if (!sub) warnings.push('Sem subtítulo');
    if (sub.length > 220) warnings.push('Subtítulo longo demais (' + sub.length + ' / 220 carac.)');
    if (tit.length > 110) warnings.push('Título longo demais (' + tit.length + ' / 110 carac.)');
    if (!aut || aut === '0') warnings.push('Sem autor discente');

    if (warnings.length > 0) {
      box.style.display = 'flex';
      lista.innerHTML = warnings.map(function(w){ return '<span class="warning-badge-pill">⚠️ ' + w + '</span>'; }).join('');
    } else {
      box.style.display = 'none';
      lista.innerHTML = '';
    }
  }

  // Ouvintes de digitação para disparar preview
  ['input', 'change'].forEach(function(evt){
    form.addEventListener(evt, function(e){
      triggerPreviewUpdate(false);
    });
  });

  // Executa contagem e checagem inicial de avisos
  updateWordCount();
  checkEditorialWarnings();

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

  // Garante sincronização final no submit
  form.addEventListener('submit', function () {
    syncWysiwygToTextarea();
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
