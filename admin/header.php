<?php
/**
 * O FAROL - Cabeçalho do Painel Administrativo Fiel às Imagens Enviadas
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

require_login();
$user = get_logged_user();
$current_page = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($admin_title) ? e($admin_title) . ' | ' : '' ?>O FAROL — Painel Administrativo</title>
  
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-fiel.css">
  
  <!-- Chart.js para o gráfico idêntico à imagem 1 -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-[#fafaf8]">

  <!-- ========================================================
       SIDEBAR LATERAL PETRÓLEO ESCURO (#0e2f33)
       ======================================================== -->
  <aside class="admin-sidebar">
    
    <!-- Topo do Sidebar: Logo 'F' Vermelho e Nome -->
    <div class="px-5 py-4 border-b border-[#143c41] flex items-center gap-3">
      <div class="w-8 h-8 rounded bg-[#c0262d] flex items-center justify-center text-white font-serif font-black text-lg shadow-sm">
        F
      </div>
      <div>
        <div class="text-white font-serif font-bold text-sm tracking-tight leading-none">O FAROL</div>
        <div class="text-[9.5px] text-[#55878d] uppercase tracking-wider font-semibold mt-0.5">JORNAL ESCOLAR</div>
      </div>
    </div>

    <!-- Grupos de Menus -->
    <div class="flex-1 py-3 overflow-y-auto">
      
      <!-- 1. GERENCIAMENTO -->
      <div class="admin-sidebar-group-title">GERENCIAMENTO</div>
      
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="admin-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>
        <span>Dashboard</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/noticias.php" class="admin-nav-item <?= $current_page === 'noticias.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Notícias</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/editor.php" class="admin-nav-item <?= $current_page === 'editor.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        <span>Editor de notícias</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/categorias.php" class="admin-nav-item <?= $current_page === 'categorias.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
        <span>Categorias</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/autores.php" class="admin-nav-item <?= $current_page === 'autores.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        <span>Autores e equipe</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/media.php" class="admin-nav-item <?= $current_page === 'media.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Biblioteca de mídia</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/homepage.php" class="admin-nav-item <?= $current_page === 'homepage.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
        <span>Página Inicial</span>
      </a>

      <!-- 2. INTERAÇÃO -->
      <div class="admin-sidebar-group-title mt-2">INTERAÇÃO</div>

      <a href="<?= BASE_URL ?>/admin/enquetes.php" class="admin-nav-item <?= $current_page === 'enquetes.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span>Enquetes</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/pautas.php" class="admin-nav-item <?= $current_page === 'pautas.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
        <span>Sugestões de pautas</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/comentarios.php" class="admin-nav-item <?= $current_page === 'comentarios.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        <span>Comentários</span>
      </a>

      <!-- 3. PUBLICAÇÃO -->
      <div class="admin-sidebar-group-title mt-2">PUBLICAÇÃO</div>

      <a href="<?= BASE_URL ?>/admin/edicoes.php" class="admin-nav-item <?= $current_page === 'edicoes.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
        <span>Edições</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/agendamento.php" class="admin-nav-item <?= $current_page === 'agendamento.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Agendamento</span>
      </a>

      <!-- 4. SISTEMA & AUDITORIA -->
      <div class="admin-sidebar-group-title mt-2">SISTEMA</div>

      <a href="<?= BASE_URL ?>/admin/users.php" class="admin-nav-item <?= $current_page === 'users.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Equipe e permissões</span>
      </a>

      <a href="<?= BASE_URL ?>/admin/settings.php" class="admin-nav-item <?= $current_page === 'settings.php' ? 'active' : '' ?>">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>Configurações gerais</span>
      </a>

    </div>

    <!-- Rodapé do Sidebar: Botão Visualizar Jornal + Perfil Marina Costa -->
    <div class="p-3 border-t border-[#143c41] space-y-2">
      <a href="<?= BASE_URL ?>/index.php" target="_blank" class="w-full bg-[#153c41] hover:bg-[#1a4a50] text-[#b7d5d8] text-xs font-medium py-2 px-3 rounded flex items-center justify-between transition-colors">
        <span class="flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
          Visualizar jornal
        </span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>

      <!-- Card do Usuário Logado -->
      <a href="<?= BASE_URL ?>/admin/perfil.php" title="Editar meu perfil" class="flex items-center justify-between pt-1 group text-decoration-none">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-full bg-[#e6ceb5] text-[#78350f] font-bold text-[11px] flex items-center justify-center group-hover:ring-2 group-hover:ring-white transition-all">
            <?= strtoupper(substr($user['nome'] ?? 'MC', 0, 2)) ?>
          </div>
          <div>
            <div class="text-white text-xs font-semibold leading-tight group-hover:text-amber-200"><?= e($user['nome'] ?: 'Marina Costa') ?></div>
            <div class="text-[9.5px] text-[#55878d] leading-none"><?= e(ucfirst($user['papel'])) ?></div>
          </div>
        </div>
        <a href="<?= BASE_URL ?>/admin/logout.php" title="Sair" class="text-[#55878d] hover:text-white p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </a>
      </a>
    </div>

  </aside>

  <!-- ========================================================
       ÁREA PRINCIPAL DE CONTEÚDO (COM MARGEM DO SIDEBAR)
       ======================================================== -->
  <div class="ml-[250px] min-h-screen flex flex-col">
    
    <!-- Topbar Branca com Breadcrumbs, Busca e Notificações -->
    <header class="bg-white border-b border-[#f0eee9] px-8 py-3 flex items-center justify-between sticky top-0 z-30 shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
      
      <!-- Breadcrumbs discretos -->
      <div class="flex items-center gap-2 text-xs text-gray-500 font-medium">
        <span>Painel</span>
        <span class="text-gray-300">›</span>
        <span class="text-gray-900 font-semibold"><?= isset($admin_breadcrumb) ? e($admin_breadcrumb) : e($admin_title ?? 'Visão geral') ?></span>
      </div>

      <!-- Barra de Pesquisa e Perfil Superior -->
      <div class="flex items-center gap-4">
        <div class="relative">
          <input type="text" placeholder="Pesquisar no painel..." 
                 class="admin-search-input pl-8 pr-8 py-1.5 w-64 text-xs">
          <svg class="w-3.5 h-3.5 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <span class="absolute right-2.5 top-2 text-[10px] text-gray-400 border border-gray-200 px-1 rounded">⌘K</span>
        </div>

        <button class="text-gray-400 hover:text-gray-600 p-1 relative">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m-6 0H9"/></svg>
        </button>

        <button class="text-gray-400 hover:text-gray-600 p-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>

        <div class="flex items-center gap-2 pl-2 border-l border-gray-200">
          <div class="w-7 h-7 rounded-full bg-[#e6ceb5] text-[#78350f] font-bold text-[10px] flex items-center justify-center">
            MC
          </div>
          <div class="text-left text-xs">
            <div class="font-bold text-gray-900 leading-none">Marina</div>
            <div class="text-[9.5px] text-gray-400">Editora-chefe</div>
          </div>
        </div>
      </div>

    </header>

    <!-- Conteúdo Específico da Tela -->
    <main class="flex-1 p-8">
