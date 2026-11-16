<?php
/**
 * O FAROL - Cabeçalho Modernizado do Jornal
 * Visual Editorial de Alto Padrão (The New York Times / The Guardian / Atlantic)
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$settings = get_site_settings();
$categorias = get_active_categories();
$current_page = basename($_SERVER['PHP_SELF'] ?? 'index.php');
$current_cat = $_GET['cat'] ?? ($_GET['slug'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?><?= e($settings['site_nome']) ?> | <?= e($settings['site_subtitulo']) ?></title>
  <meta name="description" content="<?= isset($page_desc) ? e($page_desc) : e($settings['site_descricao']) ?>">
  
  <!-- Fontes Editoriais Selecionadas -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;0,6..72,600;0,6..72,700;1,6..72,400&family=Playfair+Display:ital,wght@0,600;0,700;0,800;0,900;1,600;1,700&display=swap" rel="stylesheet">
  
  <!-- CSS Base + Tailwind Pré-compilado + CSS Editorial Moderno -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/editorial-modern.css">
  
  <style>
    /* Estilos do Cabeçalho Modernizado */
    .top-bar-editorial {
      background: #ffffff;
      border-bottom: 1px solid var(--farol-border);
      font-size: 0.72rem;
      letter-spacing: 0.04em;
    }
    
    .nav-editorial-link {
      font-family: var(--font-sans-ui);
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      color: #374151;
      text-decoration: none;
      padding: 0.85rem 0.6rem;
      border-bottom: 2px solid transparent;
      display: inline-flex;
      align-items: center;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    
    .nav-editorial-link:hover {
      color: var(--farol-crimson);
      border-bottom-color: var(--farol-crimson);
    }
    
    .nav-editorial-link.active {
      color: var(--farol-crimson);
      border-bottom-color: var(--farol-crimson);
      font-weight: 800;
    }

    .sticky-nav-container {
      position: sticky;
      top: 0;
      background: #ffffff;
      z-index: 50;
      box-shadow: 0 2px 8px -2px rgba(0,0,0,0.06);
    }
  </style>
</head>
<body class="bg-[#f6f5f0] text-[#111827]">

  <!-- Barra de Progresso de Leitura Superior -->
  <div id="reading-progress"></div>

  <!-- 1. BARRA SUPERIOR INSTITUCIONAL DISCRETA -->
  <div class="top-bar-editorial py-1.5 text-gray-600">
    <div class="container-farol flex justify-between items-center">
      <div class="flex items-center gap-3">
        <span class="font-bold text-gray-900 uppercase">EDIÇÃO Nº <?= e($settings['edicao_atual_numero']) ?></span>
        <span class="text-gray-300">•</span>
        <span class="font-medium text-gray-500"><?= e($settings['edicao_atual_data']) ?></span>
        <span class="text-gray-300 hidden sm:inline">•</span>
        <span class="text-gray-500 hidden sm:inline">CENTRO INTEGRADO SESC-SENAC</span>
      </div>

      <div class="flex items-center gap-3">
        <span class="text-[11px] text-gray-400 font-medium hidden md:inline">Jornalismo Laboratorial Estudantil</span>
        <a href="<?= BASE_URL ?>/admin/index.php" title="Acesso Redação CMS" class="text-gray-500 hover:text-black flex items-center gap-1 text-[11px] font-bold">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <span class="hidden sm:inline">Redação CMS</span>
        </a>
      </div>
    </div>
  </div>

  <!-- 2. MASTHEAD / CABEÇALHO EDITORIAL IMPONENTE -->
  <header class="bg-white border-b border-[#e6e3da] pt-6 pb-4">
    <div class="container-farol">
      
      <div class="flex flex-col md:flex-row items-center justify-between gap-4 py-2">
        
        <!-- Bloco Esquerdo: Frase de Missão / Parceria -->
        <div class="hidden lg:flex flex-col text-left text-xs max-w-xs text-gray-500">
          <span class="font-bold text-gray-900 uppercase tracking-widest text-[10px] text-[#0f4c5c]">Parceria Institucional</span>
          <span class="leading-tight mt-0.5">Produção discente dos cursos médios e técnicos integrados Sesc e Senac.</span>
        </div>

        <!-- Centro: Identidade Visual & Logotipo O FAROL -->
        <div class="flex flex-col items-center text-center">
          <a href="<?= BASE_URL ?>/index.php" class="flex flex-col items-center group text-decoration-none">
            
            <div class="flex items-center gap-3 mb-1">
              <!-- Logotipo do Farol Modernizado -->
              <svg class="w-10 h-10 text-[#b91c1c] transition-transform group-hover:scale-105 duration-300" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="32" cy="32" r="30" fill="#fef2f2" stroke="#fecaca" stroke-width="1.5"/>
                <!-- Torre e Cúpula -->
                <path d="M26 50L29 23H35L38 50H26Z" fill="#0f4c5c" stroke="#0a2f38" stroke-width="1.5"/>
                <path d="M27.5 36.5H36.5" stroke="#ffffff" stroke-width="1.5"/>
                <path d="M28.5 28.5H35.5" stroke="#ffffff" stroke-width="1.5"/>
                <rect x="29" y="17" width="6" height="6" fill="#f59e0b" stroke="#d97706" stroke-width="1"/>
                <path d="M27 17H37" stroke="#0f4c5c" stroke-width="2" stroke-linecap="round"/>
                <path d="M30 13L32 11L34 13V17H30V13Z" fill="#b91c1c"/>
                <!-- Feixes Radiantes de Conhecimento -->
                <path d="M22 18L13 14M22 20L11 22M42 18L51 14M42 20L53 22" stroke="#f59e0b" stroke-width="2" stroke-linecap="round"/>
              </svg>

              <h1 class="text-4xl sm:text-5xl md:text-6xl font-extrabold font-headline text-[#111827] tracking-tight leading-none group-hover:text-black transition-colors">
                <?= e($settings['site_nome']) ?>
              </h1>
            </div>

            <p class="text-xs sm:text-sm text-gray-600 font-sans tracking-widest uppercase font-semibold mt-1">
              <?= e($settings['site_subtitulo']) ?>
            </p>
          </a>
        </div>

        <!-- Bloco Direito: Botão Envie sua Pauta & Data Completa -->
        <div class="hidden lg:flex flex-col items-end text-right">
          <span class="text-xs text-gray-500 font-medium mb-1.5"><?= date('l, d \d\e F \d\e Y') ?></span>
          <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn-editorial-primary text-[11px] py-1.5 px-3">
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
            Sugerir Pauta
          </a>
        </div>

      </div>

    </div>
  </header>

  <!-- 3. BARRA DE NAVEGAÇÃO STICKY MODERNA -->
  <div class="sticky-nav-container border-b border-[#111827]">
    <div class="container-farol">
      
      <div class="flex items-center justify-between">
        
        <!-- Botão Menu Mobile (Hamburguer) -->
        <button id="btn-mobile-menu-toggle" class="lg:hidden p-2 text-gray-700 hover:text-black focus:outline-none" aria-label="Abrir menu" aria-expanded="false">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        <!-- Menu Desktop Horizontal -->
        <nav class="hidden lg:flex items-center space-x-1 overflow-x-auto py-0.5">
          <a href="<?= BASE_URL ?>/index.php" class="nav-editorial-link <?= ($current_page === 'index.php' && empty($current_cat)) ? 'active' : '' ?>">
            CAPA
          </a>
          <?php foreach ($categorias as $cat): ?>
            <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>" 
               class="nav-editorial-link <?= ($current_cat === $cat['slug']) ? 'active' : '' ?>">
              <?= strtoupper(e($cat['nome'])) ?>
            </a>
          <?php endforeach; ?>
        </nav>

        <!-- Ações do Canto Direito (Busca Moderna) -->
        <div class="flex items-center gap-2 py-1.5">
          <form action="<?= BASE_URL ?>/busca.php" method="GET" class="relative flex items-center">
            <input type="text" name="q" placeholder="Buscar no acervo..." 
                   class="text-xs border border-gray-300 rounded-full pl-3 pr-8 py-1.5 w-32 sm:w-44 focus:w-56 transition-all duration-300 bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
            <button type="submit" title="Buscar" class="absolute right-2 text-gray-400 hover:text-[#0f4c5c]">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
          </form>
        </div>

      </div>

    </div>

    <!-- MENU MOBILE EXPANSÍVEL -->
    <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-gray-200 px-4 pt-3 pb-6 shadow-xl">
      <div class="flex flex-col space-y-1">
        <a href="<?= BASE_URL ?>/index.php" class="py-2 text-sm font-bold border-b border-gray-100 <?= ($current_page === 'index.php' && empty($current_cat)) ? 'text-[#b91c1c]' : 'text-gray-800' ?>">
          Página Inicial (Capa)
        </a>
        <?php foreach ($categorias as $cat): ?>
          <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>" 
             class="py-2 text-sm font-medium border-b border-gray-100 <?= ($current_cat === $cat['slug']) ? 'text-[#b91c1c] font-bold' : 'text-gray-700' ?>">
            <?= e($cat['nome']) ?>
          </a>
        <?php endforeach; ?>
        <div class="pt-3">
          <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn-editorial-primary w-full text-center">
            Envie sua Pauta / Sugestão
          </a>
        </div>
      </div>
    </div>

  </div>
