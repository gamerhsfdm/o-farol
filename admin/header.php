<?php
/**
 * O FAROL - Shell do Painel Administrativo (sidebar + topbar)
 * Cada página define $admin_title (e opcionalmente $admin_breadcrumb) antes de incluir este arquivo.
 */
declare(strict_types=1);

// Mantém a saída em buffer: páginas que chamam header("Location: ...") depois de incluir
// este arquivo (ex.: history.php, pautas.php) continuam podendo redirecionar.
ob_start();

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/ui.php';

require_login();
$user = get_logged_user();
$current_page = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');

// Páginas que pertencem a um item de menu diferente do próprio nome
$nav_alias = ['history.php' => 'noticias.php'];
$nav_active = $nav_alias[$current_page] ?? $current_page;

// Pendências reais (somente leitura) para notificações e selos do menu
$pend_pautas = 0;
$pend_coment = 0;
$rev_noticias = 0;
$pdo_shell = getDBConnection();
if ($pdo_shell) {
    try {
        $pend_pautas = (int)$pdo_shell->query("SELECT COUNT(*) FROM pautas_sugestoes WHERE status = 'pendente'")->fetchColumn();
    } catch (Exception $e) {
        $pend_pautas = 0;
    }
    try {
        $pend_coment = (int)$pdo_shell->query("SELECT COUNT(*) FROM comentarios WHERE status = 'pendente'")->fetchColumn();
    } catch (Exception $e) {
        $pend_coment = 0;
    }
    try {
        $rev_noticias = (int)$pdo_shell->query("SELECT COUNT(*) FROM noticias WHERE status = 'revisao'")->fetchColumn();
    } catch (Exception $e) {
        $rev_noticias = 0;
    }
}
$nav_badges = ['pautas.php' => $pend_pautas, 'comentarios.php' => $pend_coment, 'noticias.php' => $rev_noticias];

$notificacoes = [];
if ($pend_pautas > 0) {
    $notificacoes[] = ['pautas.php', 'inbox', $pend_pautas . ($pend_pautas === 1 ? ' nova sugestão de pauta' : ' novas sugestões de pauta'), 'Aguardando análise da redação'];
}
if ($pend_coment > 0) {
    $notificacoes[] = ['comentarios.php', 'message', $pend_coment . ($pend_coment === 1 ? ' comentário pendente' : ' comentários pendentes'), 'Aguardando moderação'];
}
if ($rev_noticias > 0) {
    $notificacoes[] = ['noticias.php?status=revisao', 'news', $rev_noticias . ($rev_noticias === 1 ? ' notícia em revisão' : ' notícias em revisão'), 'Aguardando aprovação'];
}
$notif_total = $pend_pautas + $pend_coment + $rev_noticias;

// Estrutura do menu (grupos > itens). Item cujo arquivo ainda não existe é exibido desativado.
$nav_groups = [
    'Gerenciamento' => [
        ['dashboard.php',   'Dashboard',            'dashboard'],
        ['noticias.php',    'Notícias',             'news'],
        ['editor.php',      'Editor de notícias',   'pen'],
        ['categorias.php',  'Categorias',           'folder'],
        ['autores.php',     'Autores e equipe',     'users'],
        ['media.php',       'Biblioteca de mídia',  'image'],
        ['homepage.php',    'Página Inicial',       'layout'],
    ],
    'Interação' => [
        ['enquetes.php',    'Enquetes',             'chart'],
        ['pautas.php',      'Sugestões de pautas',  'inbox'],
        ['comentarios.php', 'Comentários',          'message'],
    ],
    'Publicação' => [
        ['edicoes.php',     'Edições',              'newspaper'],
        ['agendamento.php', 'Agendamento',          'calendar'],
    ],
    'Sistema' => [
        ['users.php',       'Equipe e permissões',  'shield'],
        ['settings.php',    'Configurações gerais', 'sliders'],
    ],
];

$crumb = isset($admin_breadcrumb) ? (string)$admin_breadcrumb : (string)($admin_title ?? 'Visão geral');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?= isset($admin_title) ? e((string)$admin_title) . ' | ' : '' ?>O FAROL — Painel Administrativo</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/main.css">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-fiel.css">
</head>
<body class="admin">

  <a href="#conteudo" class="skip-link">Pular para o conteúdo</a>
  <div class="sidebar-scrim" data-menu-close></div>

  <!-- ===== SIDEBAR ===== -->
  <aside class="sidebar" id="sidebar" aria-label="Navegação principal">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="sidebar__brand" aria-label="O Farol — ir para o dashboard">
      <span class="brand-mark">F</span>
      <span>
        <span class="brand-name" style="display:block">O FAROL</span>
        <span class="brand-sub" style="display:block">Jornal escolar</span>
      </span>
    </a>

    <nav class="sidebar__scroll">
      <?php foreach ($nav_groups as $group_title => $items): ?>
        <div class="nav-group">
          <div class="nav-group__title"><?= e($group_title) ?></div>
          <?php foreach ($items as [$file, $label, $icon]): ?>
            <?php if (is_file(__DIR__ . '/' . $file)): ?>
              <a href="<?= BASE_URL ?>/admin/<?= e($file) ?>" class="nav-item <?= $nav_active === $file ? 'is-active' : '' ?>"<?= $nav_active === $file ? ' aria-current="page"' : '' ?>>
                <?= ui_icon($icon) ?>
                <span class="nav-item__label"><?= e($label) ?></span>
                <?php if (($nav_badges[$file] ?? 0) > 0): ?>
                  <span class="nav-count" title="<?= e($file === 'noticias.php' ? 'Em revisão' : 'Pendentes') ?>"><?= (int)$nav_badges[$file] ?></span>
                <?php endif; ?>
              </a>
            <?php else: ?>
              <span class="nav-item is-disabled" aria-disabled="true" title="Esta seção ainda não está disponível nesta instalação">
                <?= ui_icon($icon) ?>
                <span class="nav-item__label"><?= e($label) ?></span>
                <span class="nav-soon">Em breve</span>
              </span>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </nav>

    <div class="sidebar__foot">
      <a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener" class="btn btn--block">
        <?= ui_icon('eye', 'ic--sm') ?> Visualizar jornal <?= ui_icon('external', 'ic--sm') ?>
      </a>
    </div>
  </aside>

  <!-- ===== ÁREA PRINCIPAL ===== -->
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn topbar__menu" data-menu-open aria-label="Abrir menu" aria-controls="sidebar"><?= ui_icon('menu') ?></button>

      <nav class="crumbs" aria-label="Você está em">
        <span>Painel</span><?= ui_icon('chevron-r') ?><strong><?= e($crumb) ?></strong>
      </nav>

      <div class="topbar__spacer"></div>

      <form class="topbar__search" action="<?= BASE_URL ?>/admin/noticias.php" method="GET" role="search">
        <?= ui_icon('search') ?>
        <input type="search" name="q" placeholder="Buscar notícias…" aria-label="Buscar notícias" data-global-search autocomplete="off">
        <span class="kbd" aria-hidden="true">/</span>
      </form>

      <!-- Notificações (pendências reais da redação) -->
      <div class="dropdown" data-dropdown>
        <button type="button" class="icon-btn" data-dropdown-toggle aria-label="Notificações" aria-haspopup="true" aria-expanded="false">
          <?= ui_icon('bell') ?>
          <?php if ($notif_total > 0): ?><span class="dot-badge"><?= $notif_total > 99 ? '99+' : $notif_total ?></span><?php endif; ?>
        </button>
        <div class="dropdown__panel" style="min-width:320px">
          <div class="dropdown__head">Notificações</div>
          <?php if (empty($notificacoes)): ?>
            <div class="dropdown__empty"><?= ui_icon('check-c', 'ic--lg') ?><br>Tudo em dia. Nenhuma pendência no momento.</div>
          <?php else: ?>
            <?php foreach ($notificacoes as [$n_href, $n_icon, $n_title, $n_sub]): ?>
              <a href="<?= BASE_URL ?>/admin/<?= e($n_href) ?>" class="dropdown__item">
                <?= ui_icon($n_icon) ?>
                <span><?= e($n_title) ?><small><?= e($n_sub) ?></small></span>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Perfil -->
      <div class="dropdown" data-dropdown>
        <button type="button" class="profile-btn" data-dropdown-toggle aria-haspopup="true" aria-expanded="false">
          <span class="avatar"><?= e(ui_initials($user['nome'])) ?></span>
          <span class="profile-btn__txt">
            <span class="profile-btn__name" style="display:block"><?= e($user['nome']) ?></span>
            <span class="profile-btn__role" style="display:block"><?= e(ui_role_label($user['papel'])) ?></span>
          </span>
          <?= ui_icon('chevron-d', 'ic--sm') ?>
        </button>
        <div class="dropdown__panel">
          <div class="dropdown__head" style="text-transform:none;letter-spacing:0"><?= e($user['email']) ?></div>
          <a href="<?= BASE_URL ?>/admin/perfil.php" class="dropdown__item"><?= ui_icon('user') ?> Meu perfil</a>
          <a href="<?= BASE_URL ?>/index.php" target="_blank" rel="noopener" class="dropdown__item"><?= ui_icon('eye') ?> Visualizar jornal</a>
          <div class="dropdown__sep"></div>
          <a href="<?= BASE_URL ?>/admin/logout.php" class="dropdown__item dropdown__item--danger"><?= ui_icon('logout') ?> Sair</a>
        </div>
      </div>
    </header>

    <main class="content" id="conteudo">
