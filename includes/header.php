<?php
/**
 * O FAROL - Cabeçalho público
 * Estilos em assets/css/farol-ui.css (único CSS do site público).
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$settings = get_site_settings();
$categorias = get_active_categories();

// Seis editorias no menu; as demais ficam em "Mais editorias".
$nav_visible_count = 6;
$categorias_visiveis = array_slice($categorias, 0, $nav_visible_count);
$categorias_extras = array_slice($categorias, $nav_visible_count);

$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$current_cat = $current_page === 'categoria.php' ? (string)($_GET['slug'] ?? '') : '';
$extras_active = $current_cat !== '' && in_array($current_cat, array_column($categorias_extras, 'slug'), true);

// Data de hoje por extenso (a função de datas do site não cobre o dia da semana).
$dias_semana = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
$hoje_iso = date('Y-m-d');
$hoje_extenso = $dias_semana[(int)date('w')] . ', ' . format_date_ptbr($hoje_iso, 'j \d\e F \d\e Y');

// Versão do CSS = data de modificação do arquivo: evita cache velho após deploy.
$css_version = (int)@filemtime(__DIR__ . '/../assets/css/farol-ui.css');
$js_version = (int)@filemtime(__DIR__ . '/../assets/js/portal.js');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?><?= e($settings['site_nome']) ?> | <?= e($settings['site_subtitulo']) ?></title>
  <meta name="description" content="<?= isset($page_desc) ? e($page_desc) : e($settings['site_descricao']) ?>">
  <?php if (!empty($page_keywords)): ?>
    <meta name="keywords" content="<?= e($page_keywords) ?>">
  <?php endif; ?>
  <meta name="theme-color" content="#8a1c27">

  <!-- Open Graph / Redes Sociais -->
  <meta property="og:type" content="article">
  <meta property="og:title" content="<?= isset($page_title) ? e($page_title) : e($settings['site_nome']) ?>">
  <meta property="og:description" content="<?= isset($page_desc) ? e($page_desc) : e($settings['site_descricao']) ?>">
  <?php if (!empty($page_image)): ?>
    <meta property="og:image" content="<?= (str_starts_with($page_image, 'http://') || str_starts_with($page_image, 'https://')) ? e($page_image) : BASE_URL . '/' . e(ltrim($page_image, '/')) ?>">
  <?php endif; ?>
  <meta property="og:url" content="<?= BASE_URL . '/' . e(ltrim($_SERVER['REQUEST_URI'] ?? '', '/')) ?>">
  <meta property="og:site_name" content="<?= e($settings['site_nome']) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/farol-ui.css?v=<?= $css_version ?>">
  <script>document.documentElement.classList.add('js');</script>
</head>
<body class="site-public">
  <a class="skip-link" href="#conteudo-principal">Pular para o conteúdo principal</a>
  <div id="reading-progress" aria-hidden="true"></div>

  <!-- Barra institucional -->
  <div class="topbar">
    <div class="container-farol topbar__inner">
      <p class="topbar__edition">
        <strong>Edição nº <?= e($settings['edicao_atual_numero']) ?></strong>
        <span><?= e($settings['edicao_atual_data']) ?></span>
        <time class="topbar__today" datetime="<?= e($hoje_iso) ?>"><?= e($hoje_extenso) ?></time>
      </p>
      <p class="topbar__tagline">Centro Integrado Sesc-Senac</p>
    </div>
  </div>

  <!-- Nameplate -->
  <header class="masthead">
    <div class="container-farol masthead__inner">
      <p class="masthead__mission">Produção discente dos cursos médios e técnicos integrados Sesc e Senac.</p>

      <a class="masthead__brand" href="<?= BASE_URL ?>/index.php" aria-label="<?= e($settings['site_nome']) ?> — página inicial">
        <span class="masthead__lockup">
          <svg class="masthead__logo" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <path d="M24 57L28.5 24H35.5L40 57H24Z" fill="#8a1c27"/>
            <path d="M26.2 41H37.8L36.6 50H27.4L26.2 41Z" fill="#faf8f4"/>
            <path d="M28 30.5H36L35.4 36H28.6L28 30.5Z" fill="#faf8f4"/>
            <rect x="27" y="20" width="10" height="4" fill="#1e1a18"/>
            <rect x="29.5" y="12.5" width="5" height="7.5" fill="#faf8f4" stroke="#1e1a18" stroke-width="1.6"/>
            <path d="M28 12.5L32 7.5L36 12.5H28Z" fill="#1e1a18"/>
            <path d="M25 15.5L8 11M25 17.5L5 18M39 15.5L56 11M39 17.5L59 18" stroke="#8a1c27" stroke-width="1.8" stroke-linecap="round"/>
            <path d="M16 57H48" stroke="#1e1a18" stroke-width="2" stroke-linecap="round"/>
          </svg>
          <span class="masthead__name"><?= e($settings['site_nome']) ?></span>
        </span>
        <span class="masthead__sub"><?= e($settings['site_subtitulo']) ?></span>
      </a>

      <div class="masthead__action">
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn btn--primary">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
          Sugerir pauta
        </a>
      </div>
    </div>
  </header>

  <!-- Navegação (fixa ao rolar) -->
  <div class="nav-wrap">
    <div class="container-farol nav">
      <button type="button" class="nav__toggle" id="btn-mobile-menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="mobile-menu">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        <span>Menu</span>
      </button>
      <a class="nav__brand" href="<?= BASE_URL ?>/index.php"><?= e($settings['site_nome']) ?></a>

      <nav class="nav__links" aria-label="Editorias">
        <a href="<?= BASE_URL ?>/index.php" class="nav__link<?= $current_page === 'index.php' ? ' is-active' : '' ?>"<?= $current_page === 'index.php' ? ' aria-current="page"' : '' ?>>Capa</a>
        <?php foreach ($categorias_visiveis as $cat): $ativa = $current_cat === $cat['slug']; ?>
          <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>" class="nav__link<?= $ativa ? ' is-active' : '' ?>"<?= $ativa ? ' aria-current="page"' : '' ?>><?= e(category_label($cat['nome'])) ?></a>
        <?php endforeach; ?>
        <?php if (!empty($categorias_extras)): ?>
          <details class="nav__more">
            <summary class="nav__link<?= $extras_active ? ' is-active' : '' ?>">Mais editorias</summary>
            <div class="nav__menu">
              <?php foreach ($categorias_extras as $cat): ?>
                <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>"><?= e(category_label($cat['nome'])) ?></a>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>
      </nav>

      <form class="nav__search" action="<?= BASE_URL ?>/busca.php" method="GET" role="search">
        <label class="visually-hidden" for="nav-busca">Buscar no acervo</label>
        <input type="search" id="nav-busca" name="q" placeholder="Buscar no acervo" autocomplete="off">
        <button type="submit" aria-label="Buscar">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </button>
      </form>

      <button type="button" class="nav__searchbtn" id="btn-mobile-search" aria-label="Buscar no acervo" aria-controls="mobile-menu">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </button>
    </div>

    <!-- Menu móvel -->
    <div id="mobile-menu" class="drawer" hidden>
      <div class="container-farol">
        <form class="drawer__search" action="<?= BASE_URL ?>/busca.php" method="GET" role="search">
          <label class="visually-hidden" for="drawer-busca">Buscar no acervo</label>
          <input type="search" id="drawer-busca" name="q" placeholder="Buscar no acervo" autocomplete="off">
          <button type="submit" class="btn btn--primary">Buscar</button>
        </form>
        <a href="<?= BASE_URL ?>/index.php" class="<?= $current_page === 'index.php' ? 'is-active' : '' ?>">Capa</a>
        <?php foreach ($categorias as $cat): ?>
          <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>" class="<?= $current_cat === $cat['slug'] ? 'is-active' : '' ?>"><?= e(category_label($cat['nome'])) ?></a>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn btn--primary">Sugerir pauta</a>
      </div>
    </div>
  </div>
