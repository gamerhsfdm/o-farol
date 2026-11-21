<?php
/**
 * O FAROL — Endpoint Seguro de Pré-Visualização (Preview)
 * Recebe o estado atual do formulário via POST ou carrega rascunho/agendada via GET ?id=N.
 * Exclusivo para usuários autenticados no CMS.
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

// Apenas usuários logados podem visualizar previews
require_login();

$pdo = getDBConnection();
$settings = get_site_settings();

// Identifica a visão solicitada: 'materia' (default), 'card_home' ou 'card_categoria'
$view_mode = $_REQUEST['view_mode'] ?? 'materia';

$noticia = [
    'id' => 0,
    'categoria_id' => 1,
    'categoria_nome' => 'Geral',
    'categoria_slug' => 'geral',
    'chapeu' => '',
    'titulo' => 'Título da reportagem',
    'subtitulo' => '',
    'conteudo' => '',
    'imagem_capa' => '',
    'legenda_capa' => '',
    'credito_foto' => '',
    'citacao_destaque' => '',
    'citacao_autor' => '',
    'tempo_leitura' => 3,
    'professor_orientador' => '',
    'visualizacoes' => 0,
    'publicado_em' => date('Y-m-d H:i:s'),
    'slug' => 'pre-visualizacao',
    'posicao_homepage' => 'nenhuma'
];
$autores = [];

// 1. Se receber dados via POST (Live Preview com debounce do editor)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die("CSRF Inválido para pré-visualização.");
    }

    $noticia['titulo'] = trim($_POST['titulo'] ?? '') ?: 'Título da reportagem';
    $noticia['subtitulo'] = trim($_POST['subtitulo'] ?? '');
    $noticia['conteudo'] = $_POST['conteudo'] ?? '';
    $noticia['chapeu'] = trim($_POST['chapeu'] ?? '');
    $noticia['citacao_destaque'] = trim($_POST['citacao_destaque'] ?? '');
    $noticia['citacao_autor'] = trim($_POST['citacao_autor'] ?? '');
    $noticia['imagem_capa'] = trim($_POST['imagem_capa'] ?? '');
    $noticia['legenda_capa'] = trim($_POST['legenda_capa'] ?? '');
    $noticia['credito_foto'] = trim($_POST['credito_foto'] ?? '');
    $noticia['tempo_leitura'] = (int)($_POST['tempo_leitura'] ?? 3);
    $noticia['professor_orientador'] = trim($_POST['professor_orientador'] ?? '');
    $noticia['categoria_id'] = (int)($_POST['categoria_id'] ?? 1);
    $noticia['slug'] = trim($_POST['slug'] ?? 'pre-visualizacao');
    $noticia['posicao_homepage'] = trim($_POST['posicao_homepage'] ?? 'nenhuma');

    // Categoria do banco
    if ($pdo && $noticia['categoria_id'] > 0) {
        try {
            $stmtC = $pdo->prepare("SELECT nome, slug FROM categorias WHERE id = ?");
            $stmtC->execute([$noticia['categoria_id']]);
            $c = $stmtC->fetch();
            if ($c) {
                $noticia['categoria_nome'] = $c['nome'];
                $noticia['categoria_slug'] = $c['slug'];
            }
        } catch (Exception $e) {}
    }

    // Autor informado
    $autor_id = (int)($_POST['autor_id'] ?? 0);
    if ($pdo && $autor_id > 0) {
        try {
            $stmtA = $pdo->prepare("SELECT nome, cargo, turma FROM autores WHERE id = ?");
            $stmtA->execute([$autor_id]);
            $au = $stmtA->fetch();
            if ($au) {
                $autores[] = $au;
            }
        } catch (Exception $e) {}
    }
}
// 2. Se receber via GET ?id=N (Abrir preview em nova aba para rascunho/agendada)
elseif (isset($_GET['id']) && (int)$_GET['id'] > 0 && $pdo) {
    $id = (int)$_GET['id'];
    try {
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug 
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if ($row) {
            $noticia = array_merge($noticia, $row);

            $stmtAuts = $pdo->prepare("SELECT a.nome, a.cargo, a.turma FROM autores a 
                                       JOIN noticia_autores na ON a.id = na.autor_id 
                                       WHERE na.noticia_id = ?");
            $stmtAuts->execute([$id]);
            $autores = $stmtAuts->fetchAll();
        }
    } catch (Exception $e) {}
}

$css_version = (int)@filemtime(__DIR__ . '/../assets/css/farol-ui.css');
$is_preview = true;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pré-visualização: <?= e($noticia['titulo']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/farol-ui.css?v=<?= $css_version ?>">
  <style>
    body.preview-embedded {
      background: var(--paper);
      padding: 1.5rem 1rem;
      min-height: 100vh;
    }
    .preview-card-wrapper {
      max-width: 640px;
      margin: 2rem auto;
      padding: 1.5rem;
      background: #ffffff;
      border: 1px solid var(--line);
      border-radius: var(--radius);
    }
  </style>
</head>
<body class="site-public preview-embedded">

  <?php if ($view_mode === 'materia'): ?>
    <!-- 1. VISÃO DA PÁGINA COMPLETA DA MATÉRIA -->
    <div style="max-width: 820px; margin: 0 auto;">
      <?php require __DIR__ . '/../includes/components/noticia-artigo.php'; ?>
    </div>

  <?php elseif ($view_mode === 'card_home'): ?>
    <!-- 2. VISÃO DO CARD NA HOMEPAGE -->
    <div class="preview-card-wrapper">
      <div style="margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--crimson);font-size:12px;font-weight:700;text-transform:uppercase;color:var(--teal)">
        Prévia de Exibição na Capa (Homepage)
      </div>

      <article class="portal-card-standard">
        <div class="portal-card-thumb-wrap">
          <?php if (!empty($noticia['imagem_capa'])): ?>
            <img src="<?= (str_starts_with($noticia['imagem_capa'], 'http://') || str_starts_with($noticia['imagem_capa'], 'https://')) ? e($noticia['imagem_capa']) : BASE_URL . '/' . e(ltrim($noticia['imagem_capa'], '/')) ?>" alt="<?= e($noticia['titulo']) ?>" class="portal-card-thumb">
          <?php else: ?>
            <div class="portal-img-fallback portal-img-fallback--sm">
              <span><?= e($noticia['categoria_nome']) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <div class="portal-card-body">
          <span class="portal-card-tag"><?= e($noticia['categoria_nome']) ?></span>
          <h3 class="portal-card-title"><?= e($noticia['titulo']) ?></h3>
          <?php if (!empty($noticia['subtitulo'])): ?>
            <p class="portal-card-excerpt"><?= e($noticia['subtitulo']) ?></p>
          <?php endif; ?>
          <div class="portal-card-meta">
            <time><?= date('d/m/Y') ?></time>
            <span>•</span>
            <span><?= (int)($noticia['tempo_leitura'] ?? 3) ?> min</span>
            <?php if (!empty($autores)): ?>
              <span>•</span>
              <span>Por <?= e(implode(' e ', array_map(fn($a) => is_array($a) ? ($a['nome'] ?? '') : (string)$a, $autores))) ?></span>
            <?php endif; ?>
          </div>
        </div>
      </article>
    </div>

  <?php elseif ($view_mode === 'card_categoria'): ?>
    <!-- 3. VISÃO DO CARD NA LISTAGEM DA EDITORIA -->
    <div class="preview-card-wrapper">
      <div style="margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--teal);font-size:12px;font-weight:700;text-transform:uppercase;color:var(--crimson)">
        Prévia de Exibição na Página da Editoria
      </div>

      <div class="portal-cat-lead">
        <div class="portal-cat-lead-img-wrap">
          <?php if (!empty($noticia['imagem_capa'])): ?>
            <img src="<?= (str_starts_with($noticia['imagem_capa'], 'http://') || str_starts_with($noticia['imagem_capa'], 'https://')) ? e($noticia['imagem_capa']) : BASE_URL . '/' . e(ltrim($noticia['imagem_capa'], '/')) ?>" alt="<?= e($noticia['titulo']) ?>">
          <?php else: ?>
            <div class="portal-img-fallback">
              <span><?= e($noticia['categoria_nome']) ?></span>
            </div>
          <?php endif; ?>
        </div>
        <h4><?= e($noticia['titulo']) ?></h4>
        <?php if (!empty($noticia['subtitulo'])): ?>
          <p><?= e($noticia['subtitulo']) ?></p>
        <?php endif; ?>
        <div class="portal-card-meta">
          <time><?= date('d/m/Y') ?></time>
          <span>•</span>
          <span><?= (int)($noticia['tempo_leitura'] ?? 3) ?> min de leitura</span>
        </div>
      </div>
    </div>
  <?php endif; ?>

</body>
</html>
