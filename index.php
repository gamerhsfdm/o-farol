<?php
/**
 * O FAROL - Página inicial
 *
 * Os dados vêm de includes/home-data.php e o desenho está em
 * includes/components/home-reference.php.
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/home-data.php';

$pdo = getDBConnection();

// Fallback leve para publicar notícias agendadas
if ($pdo) {
    try {
        $pdo->query("UPDATE noticias SET status = 'publicado', publicado_em = COALESCE(agendado_para, NOW()), atualizado_em = NOW() WHERE status = 'agendado' AND agendado_para <= NOW()");
    } catch (Throwable $e) {}
}

$home_settings = get_site_settings();
$home = load_home_data($pdo, $home_settings);

require __DIR__ . '/includes/components/home-reference.php';
require __DIR__ . '/includes/footer.php';
