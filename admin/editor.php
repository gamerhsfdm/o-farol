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

// Lista de edições para vínculo
$lista_edicoes = [];
if ($pdo) {
    try {
        $stmtEd = $pdo->query("SELECT id, numero, titulo, mes_ano FROM edicoes ORDER BY numero DESC");
        $lista_edicoes = $stmtEd->fetchAll();
    } catch (Exception $e) {}
}

// Valores iniciais de uma nova reportagem (em branco) ou carregamento de notícia existente
$noticia = [
    'id' => 0,
    'categoria_id' => 0,
    'categoria_nome' => '',
    'edicao_id' => null,
    'titulo' => '',
    'subtitulo' => '',
    'conteudo' => '',
    'chapeu' => '',
    'citacao_destaque' => '',
    'citacao_autor' => '',
    'legenda_capa' => '',
    'credito_foto' => '',
    'posicao_homepage' => 'nenhuma',
    'ordem_home' => 0,
    'meta_keywords' => '',
    'meta_description' => '',
    'autor_id' => 0,
    'autor_nome' => '',
    'autores_ids' => [],
    'tempo_leitura' => 5,
    'professor_orientador' => '',
    'imagem_capa' => '',
    'slug' => '',
    'status' => 'rascunho'
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
            // Autores vinculados à reportagem (N:N)
            $stmtAu = $pdo->prepare("SELECT a.id, a.nome FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = ?");
            $stmtAu->execute([$id]);
            $autoresVincs = $stmtAu->fetchAll();
            $noticia['autores_ids'] = array_column($autoresVincs, 'id');
            if (!empty($autoresVincs)) {
                $noticia['autor_id'] = $autoresVincs[0]['id'];
                $noticia['autor_nome'] = implode(' e ', array_column($autoresVincs, 'nome'));
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
    $acao = $_POST['acao_salvar']; // 'rascunho', 'revisao', 'aprovado', 'agendado', 'publicar', 'arquivar'
    $status_novo = match($acao) {
        'publicar' => 'publicado',
        'revisao' => 'revisao',
        'aprovado' => 'aprovado',
        'agendado' => 'agendado',
        'arquivar' => 'arquivado',
        default => 'rascunho'
    };

    $agendado_para_input = trim($_POST['agendado_para'] ?? '');
    $agendado_para = (!empty($agendado_para_input) && strtotime($agendado_para_input) !== false) ? date('Y-m-d H:i:s', strtotime($agendado_para_input)) : null;

    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $conteudo = sanitize_html_content($_POST['conteudo'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 1);
    $edicao_id = !empty($_POST['edicao_id']) ? (int)$_POST['edicao_id'] : null;
    $chapeu = trim($_POST['chapeu'] ?? '');
    $citacao_destaque = trim($_POST['citacao_destaque'] ?? '');
    $citacao_autor = trim($_POST['citacao_autor'] ?? '');
    $legenda_capa = trim($_POST['legenda_capa'] ?? '');
    $credito_foto = trim($_POST['credito_foto'] ?? '');
    $posicao_homepage = trim($_POST['posicao_homepage'] ?? 'nenhuma');
    $ordem_home = (int)($_POST['ordem_home'] ?? 0);
    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $meta_description = trim($_POST['meta_description'] ?? '');

    $autores_selecionados = array_map('intval', (array)($_POST['autores_ids'] ?? []));
    if (empty($autores_selecionados) && !empty($_POST['autor_id'])) {
        $autores_selecionados[] = (int)$_POST['autor_id'];
    }

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
                    $stmtUp = $pdo->prepare("UPDATE noticias SET categoria_id = ?, edicao_id = ?, titulo = ?, subtitulo = ?, conteudo = ?, chapeu = ?, citacao_destaque = ?, citacao_autor = ?, imagem_capa = ?, legenda_capa = ?, credito_foto = ?, posicao_homepage = ?, ordem_home = ?, meta_keywords = ?, meta_description = ?, slug = ?, tempo_leitura = ?, professor_orientador = ?, status = ?, agendado_para = ?, publicado_em = CASE WHEN ? = 'publicado' AND publicado_em IS NULL THEN NOW() ELSE publicado_em END, atualizado_em = NOW() WHERE id = ?");
                    $stmtUp->execute([
                        $categoria_id, $edicao_id, $titulo, $subtitulo, $conteudo,
                        $chapeu, $citacao_destaque, $citacao_autor, $imagem_capa,
                        $legenda_capa, $credito_foto, $posicao_homepage, $ordem_home,
                        $meta_keywords, $meta_description, $slug, $tempo_leitura,
                        $orientador, $status_novo, $agendado_para, $status_novo, $id
                    ]);

                    // Sincroniza autores na tabela pivô (N:N)
                    $stmtDelNa = $pdo->prepare("DELETE FROM noticia_autores WHERE noticia_id = ?");
                    $stmtDelNa->execute([$id]);
                    if (!empty($autores_selecionados)) {
                        $stmtInNa = $pdo->prepare("INSERT IGNORE INTO noticia_autores (noticia_id, autor_id) VALUES (?, ?)");
                        foreach ($autores_selecionados as $aId) {
                            if ($aId > 0) $stmtInNa->execute([$id, $aId]);
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
                    } catch (Exception $eVer) {}

                    log_audit('editar_noticia', 'noticias', $id, "Reportagem '{$titulo}' atualizada");
                    $mensagem = "Reportagem atualizada com sucesso como " . strtoupper($status_novo) . "!";
                } else {
                    $stmtIn = $pdo->prepare("INSERT INTO noticias (categoria_id, edicao_id, titulo, subtitulo, conteudo, chapeu, citacao_destaque, citacao_autor, imagem_capa, legenda_capa, credito_foto, posicao_homepage, ordem_home, meta_keywords, meta_description, slug, tempo_leitura, professor_orientador, status, agendado_para, publicado_em, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CASE WHEN ? = 'publicado' THEN NOW() ELSE NULL END, NOW())");
                    $stmtIn->execute([
                        $categoria_id, $edicao_id, $titulo, $subtitulo, $conteudo,
                        $chapeu, $citacao_destaque, $citacao_autor, $imagem_capa,
                        $legenda_capa, $credito_foto, $posicao_homepage, $ordem_home,
                        $meta_keywords, $meta_description, $slug, $tempo_leitura,
                        $orientador, $status_novo, $agendado_para, $status_novo
                    ]);
                    $id = (int)$pdo->lastInsertId();
                    
                    // Vincula autores na tabela pivô
                    if (!empty($autores_selecionados)) {
                        $stmtInNa = $pdo->prepare("INSERT IGNORE INTO noticia_autores (noticia_id, autor_id) VALUES (?, ?)");
                        foreach ($autores_selecionados as $aId) {
                            if ($aId > 0) $stmtInNa->execute([$id, $aId]);
                        }
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
      <!-- Indicador de Autosave -->
      <span id="autosave-status" style="font-size:12px;color:var(--text-muted);display:inline-flex;align-items:center;gap:4px">
        <span class="autosave-dot" style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#10b981"></span>
        <span id="autosave-label">Pronto</span>
      </span>

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
      <button type="submit" name="acao_salvar" value="revisao" class="btn btn--sm"><?= ui_icon('send', 'ic--sm') ?> Enviar p/ revisão</button>
      <?php if (in_array($_SESSION['usuario_papel'] ?? 'editor', ['admin', 'editor_chefe'], true)): ?>
        <button type="submit" name="acao_salvar" value="aprovado" class="btn btn--sm" style="background:#0284c7;color:#fff"><?= ui_icon('check', 'ic--sm') ?> Aprovar</button>
      <?php endif; ?>
      <button type="submit" name="acao_salvar" value="publicar" class="btn btn--primary btn--sm">Publicar</button>
      <button type="submit" name="acao_salvar" value="arquivar" class="btn btn--ghost btn--sm" onclick="return confirm('Deseja arquivar esta notícia? Ela sairá do ar.');">Arquivar</button>
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

    <!-- Painel lateral com Seções Recolhíveis (Accordion) -->
    <div class="card" id="painel-lateral-editor" style="padding:0;overflow:hidden">
      
      <!-- 1. PUBLICAÇÃO BÁSICA & EDIÇÃO -->
      <details class="side-details" open>
        <summary>Publicação &amp; Edição</summary>
        <div class="side-details-content">
          <div class="field">
            <label class="label" for="campo-status">Status Editorial da Matéria</label>
            <select name="status" id="campo-status" class="select" onchange="toggleAgendamentoField()">
              <option value="rascunho" <?= ($noticia['status'] === 'rascunho') ? 'selected' : '' ?>>Rascunho</option>
              <option value="revisao" <?= ($noticia['status'] === 'revisao') ? 'selected' : '' ?>>Em Revisão</option>
              <option value="aprovado" <?= ($noticia['status'] === 'aprovado') ? 'selected' : '' ?>>Aprovado</option>
              <option value="agendado" <?= ($noticia['status'] === 'agendado') ? 'selected' : '' ?>>Agendado</option>
              <option value="publicado" <?= ($noticia['status'] === 'publicado') ? 'selected' : '' ?>>Publicado</option>
              <option value="arquivado" <?= ($noticia['status'] === 'arquivado') ? 'selected' : '' ?>>Arquivado</option>
            </select>
          </div>

          <div class="field" id="wrap-agendamento" style="<?= ($noticia['status'] === 'agendado') ? '' : 'display:none' ?>">
            <label class="label" for="campo-agendado-para">Data e Hora de Publicação Automática</label>
            <input type="datetime-local" name="agendado_para" id="campo-agendado-para" value="<?= !empty($noticia['agendado_para']) ? date('Y-m-d\TH:i', strtotime($noticia['agendado_para'])) : '' ?>" class="input">
            <span class="hint">A matéria será publicada automaticamente pelo cron nesta data.</span>
          </div>

          <div class="field">
            <label class="label" for="campo-categoria">Categoria Editorial *</label>
            <select name="categoria_id" id="campo-categoria" class="select" required>
              <?php foreach ($lista_categorias as $cat): ?>
                <option value="<?= (int)$cat['id'] ?>" <?= ((int)$cat['id'] === (int)$noticia['categoria_id']) ? 'selected' : '' ?>><?= e($cat['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label class="label" for="campo-edicao">Edição Vinculada do Jornal</label>
            <select name="edicao_id" id="campo-edicao" class="select">
              <option value="">Sem vínculo de edição (Publicação isolada)</option>
              <?php foreach ($lista_edicoes as $ed): ?>
                <option value="<?= (int)$ed['id'] ?>" <?= ((int)$ed['id'] === (int)($noticia['edicao_id'] ?? 0)) ? 'selected' : '' ?>>
                  Edição Nº <?= (int)$ed['numero'] ?> — <?= e($ed['mes_ano']) ?> (<?= e($ed['titulo']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="field">
            <label class="label">Autores Discentes (Múltipla Seleção) *</label>
            <div class="authors-multiselect-list">
              <?php foreach ($lista_autores as $aut): 
                $isChecked = in_array((int)$aut['id'], (array)$noticia['autores_ids'], true);
              ?>
                <label class="author-checkbox-label">
                  <input type="checkbox" name="autores_ids[]" value="<?= (int)$aut['id'] ?>" data-nome="<?= e($aut['nome']) ?>" <?= $isChecked ? 'checked' : '' ?>>
                  <span><strong><?= e($aut['nome']) ?></strong> <small class="muted">(<?= e((string)($aut['turma'] ?? $aut['cargo'] ?? '')) ?>)</small></span>
                </label>
              <?php endforeach; ?>
            </div>
            <span class="hint">Selecione todos os alunos autores desta cobertura.</span>
          </div>

          <div class="field">
            <label class="label" for="campo-orientador">Professor Orientador Pedagógico</label>
            <input type="text" name="professor_orientador" id="campo-orientador" value="<?= e((string)$noticia['professor_orientador']) ?>" placeholder="Ex: Prof. Dr. Hamilton Queiroz" class="input">
          </div>

          <div class="field">
            <label class="label" for="campo-leitura">Tempo de Leitura <small>(minutos)</small></label>
            <input type="number" name="tempo_leitura" id="campo-leitura" value="<?= e((string)$noticia['tempo_leitura']) ?>" min="1" max="60" class="input">
          </div>
        </div>
      </details>

      <!-- 2. ELEMENTOS EDITORIAIS DE DESTAQUE -->
      <details class="side-details" open>
        <summary>Elementos Editoriais</summary>
        <div class="side-details-content">
          <div class="field">
            <label class="label" for="campo-chapeu">Chapéu Editorial</label>
            <input type="text" name="chapeu" id="campo-chapeu" value="<?= e((string)($noticia['chapeu'] ?? '')) ?>" placeholder="Ex: PREMIAÇÃO NACIONAL, SEMIFINAL" class="input">
            <span class="hint">Exibido em caixa alta logo acima do título.</span>
          </div>

          <div class="field">
            <label class="label" for="campo-citacao">Citação em Destaque (Pull Quote)</label>
            <textarea name="citacao_destaque" id="campo-citacao" rows="2" placeholder="Frase marcante da entrevista ou matéria..." class="textarea"><?= e((string)($noticia['citacao_destaque'] ?? '')) ?></textarea>
          </div>

          <div class="field">
            <label class="label" for="campo-citacao-autor">Autor da Citação</label>
            <input type="text" name="citacao_autor" id="campo-citacao-autor" value="<?= e((string)($noticia['citacao_autor'] ?? '')) ?>" placeholder="Ex: Beatriz Vasconcelos, 3º Ano Automação" class="input">
          </div>
        </div>
      </details>

      <!-- 3. IMAGEM DE CAPA & CRÉDITOS -->
      <details class="side-details" open>
        <summary>Imagem de Capa &amp; Créditos</summary>
        <div class="side-details-content">
          <div class="cover-box">
            <img src="<?= BASE_URL ?>/<?= e((string)$noticia['imagem_capa']) ?>" alt="Pré-visualização da capa" id="preview-capa" <?= empty($noticia['imagem_capa']) ? 'hidden' : '' ?>>
            <div class="cover-box__empty" id="capa-vazia" <?= !empty($noticia['imagem_capa']) ? 'hidden' : '' ?>>Sem imagem de capa</div>
            <input type="text" name="imagem_capa" id="campo-capa" value="<?= e((string)$noticia['imagem_capa']) ?>" placeholder="Caminho ou URL da imagem…" class="input" aria-label="Caminho da imagem de capa">
          </div>
          <div class="row row--wrap" style="gap:8px">
            <button type="button" class="btn btn--sm" onclick="openMediaModalForCover()"><?= ui_icon('image', 'ic--sm') ?> Selecionar da biblioteca</button>
            <a href="<?= BASE_URL ?>/admin/media.php" target="_blank" rel="noopener" class="link text-sm">Biblioteca <?= ui_icon('external', 'ic--sm') ?></a>
          </div>

          <div class="field">
            <label class="label" for="campo-legenda-capa">Legenda da Foto de Capa</label>
            <textarea name="legenda_capa" id="campo-legenda-capa" rows="2" placeholder="Descreva o que acontece na fotografia..." class="textarea"><?= e((string)($noticia['legenda_capa'] ?? '')) ?></textarea>
          </div>

          <div class="field">
            <label class="label" for="campo-credito-foto">Crédito Fotográfico da Capa</label>
            <input type="text" name="credito_foto" id="campo-credito-foto" value="<?= e((string)($noticia['credito_foto'] ?? '')) ?>" placeholder="Ex: Foto: Marina Costa / O Farol" class="input">
          </div>
        </div>
      </details>

      <!-- 4. POSICIONAMENTO NA CAPA (HOMEPAGE) -->
      <details class="side-details">
        <summary>Posicionamento na Capa</summary>
        <div class="side-details-content">
          <div class="field">
            <label class="label" for="campo-posicao-home">Posição de Destaque na Capa</label>
            <select name="posicao_homepage" id="campo-posicao-home" class="select" onchange="checkHomepagePositionConflict()">
              <option value="nenhuma" <?= ($noticia['posicao_homepage'] === 'nenhuma') ? 'selected' : '' ?>>Nenhuma (Fluxo cronológico normal)</option>
              <option value="manchete_hero" <?= ($noticia['posicao_homepage'] === 'manchete_hero') ? 'selected' : '' ?>>Manchete Principal (Hero Superior)</option>
              <option value="ciencia_tecnica" <?= ($noticia['posicao_homepage'] === 'ciencia_tecnica') ? 'selected' : '' ?>>Ciência &amp; Prática Técnica</option>
              <option value="jogos_esportes" <?= ($noticia['posicao_homepage'] === 'jogos_esportes') ? 'selected' : '' ?>>Jogos Escolares Interclasses</option>
              <option value="cultura_empreendedorismo" <?= ($noticia['posicao_homepage'] === 'cultura_empreendedorismo') ? 'selected' : '' ?>>Cultura &amp; Empreendedorismo</option>
              <option value="coluna_opiniao" <?= ($noticia['posicao_homepage'] === 'coluna_opiniao') ? 'selected' : '' ?>>Coluna de Opinião &amp; Cotidiano</option>
            </select>
          </div>

          <div id="box-conflito-posicao" style="display:none;padding:10px;background:#fef2f2;border:1px solid #fecaca;border-radius:var(--r-sm);font-size:12px;color:#991b1b">
            ⚠️ <strong>Atenção:</strong> Esta posição na capa está atualmente ocupada por: <em id="titulo-materia-substituida"></em>. Ao publicar, ela será substituída por esta reportagem.
          </div>

          <div class="field">
            <label class="label" for="campo-ordem-home">Ordem de Prioridade na Home</label>
            <input type="number" name="ordem_home" id="campo-ordem-home" value="<?= (int)($noticia['ordem_home'] ?? 0) ?>" class="input">
            <span class="hint">Valores menores aparecem primeiro dentro da mesma seção.</span>
          </div>
        </div>
      </details>

      <!-- 5. URL AMIGÁVEL & SLUG EM TEMPO REAL -->
      <details class="side-details" open>
        <summary>URL Amigável (Slug)</summary>
        <div class="side-details-content">
          <div class="field">
            <label class="label" for="campo-slug">Slug da Matéria</label>
            <input type="text" name="slug" id="campo-slug" value="<?= e((string)$noticia['slug']) ?>" placeholder="gerado a partir do título" class="input mono">
            <div id="slug-feedback" style="margin-top:4px;font-size:11.5px"></div>
            <span class="hint">/noticia/<span id="slug-preview"><?= e((string)$noticia['slug']) ?></span></span>
            <?php if ($id > 0 && $noticia['status'] === 'publicado'): ?>
              <div id="slug-change-warning" style="display:none;margin-top:6px;padding:8px;background:#fffbeb;border:1px solid #fef3c7;border-radius:4px;font-size:11.5px;color:#b45309">
                ⚠️ <strong>Aviso:</strong> Esta matéria já foi publicada. Alterar o slug pode quebrar links compartilhados anteriormente.
              </div>
            <?php endif; ?>
          </div>
        </div>
      </details>

      <!-- 6. OTIMIZAÇÃO PARA BUSCADORES (SEO) & PREVIEW GOOGLE -->
      <details class="side-details">
        <summary>SEO &amp; Redes Sociais</summary>
        <div class="side-details-content">
          <div class="field">
            <label class="label" for="campo-meta-desc">Meta Description <small>(<span id="char-count-meta">0</span> / 155 carac.)</small></label>
            <textarea name="meta_description" id="campo-meta-desc" rows="3" maxlength="180" placeholder="Resumo atrativo para motores de busca e compartilhamento..." class="textarea"><?= e((string)($noticia['meta_description'] ?? '')) ?></textarea>
          </div>

          <div class="field">
            <label class="label" for="campo-meta-keywords">Palavras-chave (Keywords)</label>
            <input type="text" name="meta_keywords" id="campo-meta-keywords" value="<?= e((string)($noticia['meta_keywords'] ?? '')) ?>" placeholder="robótica, sesc, senac, inovação, ciência" class="input">
          </div>

          <!-- Snippet Google Preview -->
          <div>
            <span class="label" style="margin-bottom:6px">Pré-visualização no Google</span>
            <div class="seo-google-snippet">
              <div class="seo-google-url"><?= BASE_URL ?>/noticia/<span id="seo-preview-slug"><?= e((string)$noticia['slug']) ?></span></div>
              <div class="seo-google-title" id="seo-preview-title"><?= e($noticia['titulo'] ?: 'Título da reportagem') ?> — O FAROL</div>
              <div class="seo-google-desc" id="seo-preview-desc"><?= e($noticia['meta_description'] ?: ($noticia['subtitulo'] ?: 'Leia a cobertura completa no jornal escolar O Farol.')) ?></div>
            </div>
          </div>
        </div>
      </details>

      <!-- 7. NOTAS EDITORIAIS INTERNAS (Diálogo Orientador-Redator) -->
      <?php if ($id > 0): ?>
      <details class="side-details" open>
        <summary>Notas Editoriais (Orientação) <span id="notas-count-badge" class="badge badge--brand badge--sm" style="display:none">0</span></summary>
        <div class="side-details-content">
          <div id="lista-notas-editoriais" style="max-height:220px;overflow-y:auto;display:flex;flex-direction:column;gap:8px;margin-bottom:10px">
            <span class="muted text-sm">Carregando notas...</span>
          </div>
          <div class="field" style="margin-bottom:6px">
            <textarea id="novo-texto-nota" rows="2" placeholder="Orientação ou observação para a equipe..." class="textarea" style="font-size:12.5px"></textarea>
          </div>
          <button type="button" class="btn btn--sm btn--primary" onclick="adicionarNotaEditorial()"><?= ui_icon('plus', 'ic--sm') ?> Adicionar nota</button>
        </div>
      </details>
      <?php endif; ?>

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

  // Slug -> pré-visualização da URL e verificação de duplicidade / aviso
  var slug = document.getElementById('campo-slug');
  var slugOriginal = '<?= addslashes((string)$noticia['slug']) ?>';
  var noticiaStatus = '<?= addslashes((string)$noticia['status']) ?>';
  var noticiaId = <?= (int)$id ?>;
  var slugFeedback = document.getElementById('slug-feedback');
  var slugChangeWarning = document.getElementById('slug-change-warning');
  var slugTimer = null;

  function checkSlugAvailability(val) {
    if (!slugFeedback) return;
    if (!val) {
      slugFeedback.innerHTML = '';
      return;
    }
    fetch(base + '/admin/api-check-slug.php?slug=' + encodeURIComponent(val) + '&id=' + noticiaId)
      .then(function(res){ return res.json(); })
      .then(function(data){
        if (data.disponivel) {
          slugFeedback.innerHTML = '<span style="color:#059669;font-weight:600">✓ Slug disponível</span>';
        } else {
          slugFeedback.innerHTML = '<span style="color:#dc2626;font-weight:600">✕ Slug já está em uso por "' + (data.titulo || 'outra matéria') + '"</span>';
        }
      })
      .catch(function(){});
  }

  if (slug) {
    slug.addEventListener('input', function () {
      var val = slug.value.trim();
      document.getElementById('slug-preview').textContent = val;
      var seoSlug = document.getElementById('seo-preview-slug');
      if (seoSlug) seoSlug.textContent = val;

      if (slugChangeWarning && noticiaId > 0 && noticiaStatus === 'publicado') {
        slugChangeWarning.style.display = (val !== slugOriginal) ? 'block' : 'none';
      }

      clearTimeout(slugTimer);
      slugTimer = setTimeout(function(){ checkSlugAvailability(val); }, 400);
    });
  }

  // Posição na Homepage -> verificação de conflito / substituição
  window.checkHomepagePositionConflict = function() {
    var posSelect = document.getElementById('campo-posicao-home');
    var boxConflito = document.getElementById('box-conflito-posicao');
    var elTitulo = document.getElementById('titulo-materia-substituida');
    if (!posSelect || !boxConflito) return;

    var pos = posSelect.value;
    if (pos === 'nenhuma') {
      boxConflito.style.display = 'none';
      return;
    }

    fetch(base + '/admin/api-check-position.php?posicao=' + encodeURIComponent(pos) + '&id=' + noticiaId)
      .then(function(res){ return res.json(); })
      .then(function(data){
        if (data.ocupada) {
          boxConflito.style.display = 'block';
          if (elTitulo) elTitulo.textContent = '"' + data.titulo + '"';
        } else {
          boxConflito.style.display = 'none';
        }
      })
      .catch(function(){});
  };
  checkHomepagePositionConflict();

  // SEO & Redes Sociais -> Contadores e Snippet Google em Tempo Real
  var metaDesc = document.getElementById('campo-meta-desc');
  var countMeta = document.getElementById('char-count-meta');
  var seoTitle = document.getElementById('seo-preview-title');
  var seoDesc = document.getElementById('seo-preview-desc');
  var campoTitulo = form.querySelector('[name=titulo]');
  var campoSubtitulo = form.querySelector('[name=subtitulo]');

  function updateSeoSnippet() {
    var tit = (campoTitulo ? campoTitulo.value.trim() : '') || 'Título da reportagem';
    var desc = (metaDesc ? metaDesc.value.trim() : '') || (campoSubtitulo ? campoSubtitulo.value.trim() : '') || 'Leia a cobertura completa no jornal escolar O Farol.';
    
    if (seoTitle) seoTitle.textContent = tit + ' — O FAROL';
    if (seoDesc) seoDesc.textContent = desc;

    if (countMeta && metaDesc) {
      var len = metaDesc.value.length;
      countMeta.textContent = len;
      countMeta.style.color = len > 155 ? '#dc2626' : 'inherit';
    }
  }

  if (metaDesc) { metaDesc.addEventListener('input', updateSeoSnippet); }
  if (campoTitulo) {
    campoTitulo.addEventListener('input', function(){
      updateSeoSnippet();
      // Sugere slug se campo slug estiver vazio
      if (slug && !slug.value.trim()) {
        var autoSlug = campoTitulo.value.toLowerCase()
          .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/^-+|-+$/g, '');
        slug.value = autoSlug;
        document.getElementById('slug-preview').textContent = autoSlug;
        var seoSlug = document.getElementById('seo-preview-slug');
        if (seoSlug) seoSlug.textContent = autoSlug;
        checkSlugAvailability(autoSlug);
      }
    });
  }
  if (campoSubtitulo) { campoSubtitulo.addEventListener('input', updateSeoSnippet); }
  updateSeoSnippet();

  // Multi-seleção de autores discentes -> sincroniza Byline do editor
  var authorCheckboxes = document.querySelectorAll('.author-checkbox-item');
  function updateAuthorBylineFromCheckboxes() {
    var checkedNames = [];
    authorCheckboxes.forEach(function(cb){
      if (cb.checked) {
        var label = cb.closest('label');
        if (label) checkedNames.push(label.textContent.trim());
      }
    });
    var bylineNome = document.getElementById('byline-nome');
    var bylineAvatar = document.getElementById('byline-avatar');
    if (bylineNome) {
      bylineNome.textContent = checkedNames.length > 0 ? checkedNames.join(', ') : 'autor não definido';
    }
    if (bylineAvatar) {
      bylineAvatar.textContent = checkedNames.length > 0 ? initials(checkedNames[0]) : '·';
    }
  }
  authorCheckboxes.forEach(function(cb){
    cb.addEventListener('change', updateAuthorBylineFromCheckboxes);
  });
  if (authorCheckboxes.length > 0) {
    var hasChecked = Array.from(authorCheckboxes).some(function(cb){ return cb.checked; });
    if (hasChecked) updateAuthorBylineFromCheckboxes();
  }

  // Toggle de campo de agendamento de data/hora
  window.toggleAgendamentoField = function() {
    var st = document.getElementById('campo-status');
    var wrap = document.getElementById('wrap-agendamento');
    if (st && wrap) {
      wrap.style.display = (st.value === 'agendado') ? 'block' : 'none';
    }
  };

  // Diálogo e Notas Editoriais (Orientador-Redator)
  window.carregarNotasEditoriais = function() {
    if (noticiaId <= 0) return;
    fetch(base + '/admin/api-notes.php?noticia_id=' + noticiaId)
      .then(function(r){ return r.json(); })
      .then(function(res){
        var container = document.getElementById('lista-notas-editoriais');
        var badge = document.getElementById('notas-count-badge');
        if (!container || !res.sucesso) return;
        var notas = res.notas || [];
        if (badge) {
          badge.textContent = notas.length;
          badge.style.display = notas.length > 0 ? 'inline-block' : 'none';
        }
        if (notas.length === 0) {
          container.innerHTML = '<span class="muted text-sm">Nenhuma nota ou orientação registrada ainda.</span>';
          return;
        }
        container.innerHTML = notas.map(function(n){
          var isResolvido = parseInt(n.status_resolvido, 10) === 1;
          return '<div style="padding:8px;border-radius:4px;background:' + (isResolvido ? '#f3f4f6' : '#eff6ff') + ';border:1px solid ' + (isResolvido ? '#e5e7eb' : '#bfdbfe') + ';font-size:12px">' +
                 '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">' +
                 '<strong>' + (n.autor_nome || 'Orientação') + '</strong>' +
                 '<button type="button" class="btn btn--sm" style="padding:1px 6px;font-size:10px" onclick="toggleNota(' + n.id + ')">' + (isResolvido ? '✓ Resolvido' : 'Marcar resolvido') + '</button>' +
                 '</div>' +
                 '<div style="' + (isResolvido ? 'text-decoration:line-through;color:#6b7280' : 'color:#1e3a8a') + '">' + n.texto + '</div>' +
                 '</div>';
        }).join('');
      })
      .catch(function(){});
  };

  window.adicionarNotaEditorial = function() {
    var txtEl = document.getElementById('novo-texto-nota');
    var txt = (txtEl ? txtEl.value.trim() : '');
    if (!txt || noticiaId <= 0) return;
    var fd = new FormData();
    fd.append('noticia_id', noticiaId);
    fd.append('action', 'add');
    fd.append('texto', txt);
    fd.append('csrf_token', csrf);
    fetch(base + '/admin/api-notes.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (res.sucesso) {
          txtEl.value = '';
          carregarNotasEditoriais();
        }
      });
  };

  window.toggleNota = function(notaId) {
    var fd = new FormData();
    fd.append('noticia_id', noticiaId);
    fd.append('action', 'toggle');
    fd.append('nota_id', notaId);
    fd.append('csrf_token', csrf);
    fetch(base + '/admin/api-notes.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(){ carregarNotasEditoriais(); });
  };
  carregarNotasEditoriais();

  // Autosave a cada 30 segundos e Heartbeat de Edição Simultânea
  var autoSaveTimer = null;
  var heartbeatTimer = null;
  var autosaveLabel = document.getElementById('autosave-label');
  var autosaveDot = document.querySelector('.autosave-dot');

  function triggerAutosave() {
    if (currentMode === 'preview') return;
    syncWysiwygToTextarea();
    var tit = (campoTitulo ? campoTitulo.value.trim() : '');
    if (!tit) return; // não salva sem título

    if (autosaveLabel) autosaveLabel.textContent = 'Salvando...';
    if (autosaveDot) autosaveDot.style.background = '#f59e0b';

    var fd = new FormData(form);
    fd.append('action', 'autosave');
    fd.append('id', noticiaId);

    fetch(base + '/admin/api-autosave.php', {
      method: 'POST',
      body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(res){
      if (res.sucesso) {
        if (autosaveLabel) autosaveLabel.textContent = 'Salvo às ' + res.salvo_em;
        if (autosaveDot) autosaveDot.style.background = '#10b981';
        if (noticiaId === 0 && res.id) {
          noticiaId = res.id;
          form.querySelector('[name=id]').value = res.id;
        }
      } else if (res.conflito) {
        if (autosaveLabel) autosaveLabel.textContent = 'Conflito de edição!';
        if (autosaveDot) autosaveDot.style.background = '#ef4444';
        alert('Atenção: ' + res.erro);
      }
    })
    .catch(function(){
      if (autosaveLabel) autosaveLabel.textContent = 'Erro ao salvar';
      if (autosaveDot) autosaveDot.style.background = '#ef4444';
    });
  }

  function runHeartbeat() {
    if (noticiaId <= 0) return;
    var fd = new FormData();
    fd.append('action', 'heartbeat');
    fd.append('id', noticiaId);
    fd.append('csrf_token', csrf);
    fetch(base + '/admin/api-autosave.php', { method: 'POST', body: fd })
      .then(function(r){ return r.json(); })
      .then(function(res){
        if (res.bloqueado) {
          alert('⚠️ Atenção: Esta matéria está sendo editada simultaneamente por ' + res.editor_nome + '. Tenha cuidado para não sobrescrever alterações.');
        }
      })
      .catch(function(){});
  }

  // Inicia ciclos de Autosave (30s) e Heartbeat (20s)
  autoSaveTimer = setInterval(triggerAutosave, 30000);
  if (noticiaId > 0) {
    heartbeatTimer = setInterval(runHeartbeat, 20000);
    runHeartbeat();
  }

  // Libera lock ao descarregar a página
  window.addEventListener('beforeunload', function(){
    if (noticiaId > 0) {
      var fd = new FormData();
      fd.append('action', 'release');
      fd.append('id', noticiaId);
      fd.append('csrf_token', csrf);
      navigator.sendBeacon(base + '/admin/api-autosave.php', fd);
    }
  });

  // Atalhos de teclado globais no Editor
  document.addEventListener('keydown', function(e) {
    // Ctrl+S / Cmd+S: Salvar rápido (rascunho ou formulário)
    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
      e.preventDefault();
      triggerAutosave();
      return;
    }
    // Ctrl+P / Cmd+P: Alternar preview
    if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
      e.preventDefault();
      if (currentMode === 'edit') setEditorMode('split');
      else if (currentMode === 'split') setEditorMode('preview');
      else setEditorMode('edit');
      return;
    }
  });

  // Garante sincronização final no submit
  form.addEventListener('submit', function () {
    syncWysiwygToTextarea();
  });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
