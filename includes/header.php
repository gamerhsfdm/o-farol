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

// Versão do CSS = data de modificação do arquivo: evita cache velho após deploy.
$css_version = (int)@filemtime(__DIR__ . '/../assets/css/farol-ui.css');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?><?= e($settings['site_nome']) ?> | <?= e($settings['site_subtitulo']) ?></title>
  <meta name="description" content="<?= isset($page_desc) ? e($page_desc) : e($settings['site_descricao']) ?>">
  <meta name="theme-color" content="#0b3f50">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400;1,6..72,500&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/farol-ui.css?v=<?= $css_version ?>">
</head>
<body class="site-public">
  <a class="skip-link" href="#conteudo-principal">Pular para o conteúdo principal</a>
  <div id="reading-progress" aria-hidden="true"></div>

  <!-- Faixa institucional -->
  <div class="topbar">
    <div class="container-farol topbar__inner">
      <p class="topbar__edition">
        <strong>Edição nº <?= e($settings['edicao_atual_numero']) ?></strong>
        <span><?= e($settings['edicao_atual_data']) ?></span>
      </p>
      <p class="topbar__tagline">Centro Integrado Sesc-Senac</p>
    </div>
  </div>

  <!-- Identidade -->
  <header class="masthead">
    <div class="container-farol masthead__inner">
      <p class="masthead__mission">Produção discente dos cursos médios e técnicos integrados Sesc e Senac.</p>

      <a class="masthead__brand" href="<?= BASE_URL ?>/index.php" aria-label="<?= e($settings['site_nome']) ?> — página inicial">
        <svg class="masthead__logo" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
          <circle cx="32" cy="32" r="30" fill="#fef2f2" stroke="#fecaca" stroke-width="1.5"/>
          <path d="M26 50L29 23H35L38 50H26Z" fill="#0f5368" stroke="#0b3f50" stroke-width="1.5"/>
          <path d="M27.5 36.5H36.5" stroke="#ffffff" stroke-width="1.5"/>
          <path d="M28.5 28.5H35.5" stroke="#ffffff" stroke-width="1.5"/>
          <rect x="29" y="17" width="6" height="6" fill="#f59e0b" stroke="#d97706" stroke-width="1"/>
          <path d="M27 17H37" stroke="#0f5368" stroke-width="2" stroke-linecap="round"/>
          <path d="M30 13L32 11L34 13V17H30V13Z" fill="#a51f2a"/>
          <path d="M22 18L13 14M22 20L11 22M42 18L51 14M42 20L53 22" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span class="masthead__name"><?= e($settings['site_nome']) ?></span>
        <span class="masthead__sub"><?= e($settings['site_subtitulo']) ?></span>
      </a>

      <div class="masthead__action">
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn btn--outline">
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
      </button>

      <nav class="nav__links" aria-label="Editorias">
        <a href="<?= BASE_URL ?>/index.php" class="nav__link<?= $current_page === 'index.php' ? ' is-active' : '' ?>"<?= $current_page === 'index.php' ? ' aria-current="page"' : '' ?>>Capa</a>
        <?php foreach ($categorias_visiveis as $cat): $ativa = $current_cat === $cat['slug']; ?>
          <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>" class="nav__link<?= $ativa ? ' is-active' : '' ?>"<?= $ativa ? ' aria-current="page"' : '' ?>><?= e($cat['nome']) ?></a>
        <?php endforeach; ?>
        <?php if (!empty($categorias_extras)): ?>
          <details class="nav__more">
            <summary class="nav__link<?= $extras_active ? ' is-active' : '' ?>">Mais editorias</summary>
            <div class="nav__menu">
              <?php foreach ($categorias_extras as $cat): ?>
                <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>"><?= e($cat['nome']) ?></a>
              <?php endforeach; ?>
            </div>
          </details>
        <?php endif; ?>
      </nav>

      <form class="nav__search" action="<?= BASE_URL ?>/busca.php" method="GET" role="search">
        <label class="visually-hidden" for="nav-busca">Buscar no acervo</label>
        <input type="search" id="nav-busca" name="q" placeholder="Buscar" autocomplete="off">
        <button type="submit" aria-label="Buscar">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </button>
      </form>
    </div>

    <!-- Menu móvel -->
    <div id="mobile-menu" class="drawer" hidden>
      <div class="container-farol">
        <a href="<?= BASE_URL ?>/index.php" class="<?= $current_page === 'index.php' ? 'is-active' : '' ?>">Capa</a>
        <?php foreach ($categorias as $cat): ?>
          <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>" class="<?= $current_cat === $cat['slug'] ? 'is-active' : '' ?>"><?= e($cat['nome']) ?></a>
        <?php endforeach; ?>
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn btn-editorial-primary">Sugerir pauta</a>
      </div>
    </div>
  </div>
