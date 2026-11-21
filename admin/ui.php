<?php
/**
 * O FAROL - Componentes de interface do painel administrativo.
 * Somente apresentação: nenhuma regra de negócio, consulta ou escrita em banco aqui.
 */
declare(strict_types=1);

/**
 * Ícone SVG inline (traço 1.75, 24x24). Nomes desconhecidos retornam um ícone neutro.
 */
function ui_icon(string $name, string $class = ''): string
{
    static $paths = [
        'dashboard' => '<rect width="7" height="9" x="3" y="3" rx="1"/><rect width="7" height="5" x="14" y="3" rx="1"/><rect width="7" height="9" x="14" y="12" rx="1"/><rect width="7" height="5" x="3" y="16" rx="1"/>',
        'news'      => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'pen'       => '<path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'folder'    => '<path d="M20 20a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.9a2 2 0 0 1-1.69-.9L9.6 3.9A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2Z"/>',
        'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'user'      => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'user-plus' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/>',
        'image'     => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'layout'    => '<rect width="18" height="7" x="3" y="3" rx="1"/><rect width="9" height="7" x="3" y="14" rx="1"/><rect width="5" height="7" x="16" y="14" rx="1"/>',
        'chart'     => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
        'inbox'     => '<polyline points="22 12 16 12 14 15 10 15 8 12 2 12"/><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'message'   => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'newspaper' => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>',
        'calendar'  => '<rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>',
        'shield'    => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/>',
        'sliders'   => '<line x1="4" x2="4" y1="21" y2="14"/><line x1="4" x2="4" y1="10" y2="3"/><line x1="12" x2="12" y1="21" y2="12"/><line x1="12" x2="12" y1="8" y2="3"/><line x1="20" x2="20" y1="21" y2="16"/><line x1="20" x2="20" y1="12" y2="3"/><line x1="2" x2="6" y1="14" y2="14"/><line x1="10" x2="14" y1="8" y2="8"/><line x1="18" x2="22" y1="16" y2="16"/>',
        'eye'       => '<path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>',
        'search'    => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
        'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
        'plus'      => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'trash'     => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'check'     => '<path d="M20 6 9 17l-5-5"/>',
        'x'         => '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
        'menu'      => '<line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/>',
        'chevron-r' => '<path d="m9 18 6-6-6-6"/>',
        'chevron-d' => '<path d="m6 9 6 6 6-6"/>',
        'external'  => '<path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'history'   => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/>',
        'restore'   => '<path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/>',
        'clock'     => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
        'upload'    => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
        'more'      => '<circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/>',
        'copy'      => '<rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>',
        'arrow-l'   => '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>',
        'send'      => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        'save'      => '<path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/>',
        'alert'     => '<path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/>',
        'info'      => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/>',
        'check-c'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'bold'      => '<path d="M6 12h9a4 4 0 0 1 0 8H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1h7a4 4 0 0 1 0 8"/>',
        'italic'    => '<line x1="19" x2="10" y1="4" y2="4"/><line x1="14" x2="5" y1="20" y2="20"/><line x1="15" x2="9" y1="4" y2="20"/>',
        'link'      => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'list'      => '<line x1="8" x2="21" y1="6" y2="6"/><line x1="8" x2="21" y1="12" y2="12"/><line x1="8" x2="21" y1="18" y2="18"/><line x1="3" x2="3.01" y1="6" y2="6"/><line x1="3" x2="3.01" y1="12" y2="12"/><line x1="3" x2="3.01" y1="18" y2="18"/>',
        'heading'   => '<path d="M6 12h12"/><path d="M6 20V4"/><path d="M18 20V4"/>',
        'quote'     => '<path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/><path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>',
        'filter'    => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'trending'  => '<polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/>',
        'lock'      => '<rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'pilcrow'   => '<path d="M13 4v16"/><path d="M17 4v16"/><path d="M19 4H9.5a4.5 4.5 0 0 0 0 9H13"/>',
        'globe'     => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
    ];

    $inner = $paths[$name] ?? $paths['news'];
    $cls = trim('ic ' . $class);
    return '<svg class="' . htmlspecialchars($cls, ENT_QUOTES, 'UTF-8') . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $inner . '</svg>';
}

/**
 * Iniciais (até 2 letras) de um nome, compatível com UTF-8.
 */
function ui_initials(?string $name): string
{
    $name = trim((string)$name);
    if ($name === '') {
        return '?';
    }
    $parts = preg_split('/\s+/u', $name) ?: [$name];
    $first = mb_strtoupper(mb_substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        return $first . mb_strtoupper(mb_substr($parts[count($parts) - 1], 0, 1));
    }
    return $first . mb_strtolower(mb_substr($parts[0], 1, 1));
}

/**
 * Cor hexadecimal segura para uso em atributos style (cai para $fallback se inválida).
 */
function ui_color(?string $color, string $fallback = '#0e3a4b'): string
{
    $color = trim((string)$color);
    return preg_match('/^#[0-9a-fA-F]{3,8}$/', $color) === 1 ? $color : $fallback;
}

/**
 * Rótulo amigável do papel do usuário.
 */
function ui_role_label(?string $papel): string
{
    return match ((string)$papel) {
        'admin'        => 'Administrador',
        'editor_chefe' => 'Editor-chefe',
        'editor'       => 'Editor',
        'colaborador'  => 'Colaborador',
        default        => ucfirst((string)$papel),
    };
}

/**
 * Tamanho de arquivo legível (pt-BR).
 */
function ui_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
    }
    if ($bytes >= 1024) {
        return number_format($bytes / 1024, 0, ',', '.') . ' KB';
    }
    return $bytes . ' B';
}

/**
 * Badge pronto. $tone: ok | warn | info | bad | brand | accent | neutral
 */
function ui_badge(string $text, string $tone = 'neutral'): string
{
    $tone = in_array($tone, ['ok', 'warn', 'info', 'bad', 'brand', 'accent'], true) ? ' badge--' . $tone : '';
    return '<span class="badge' . $tone . '">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
}

/**
 * Badge de status de notícia (todos os estados do ENUM do banco).
 */
function ui_news_status(?string $status): string
{
    return match ((string)$status) {
        'publicado' => ui_badge('Publicada', 'ok'),
        'revisao'   => ui_badge('Em revisão', 'warn'),
        'aprovado'  => ui_badge('Aprovada', 'brand'),
        'agendado'  => ui_badge('Agendada', 'info'),
        'arquivado' => ui_badge('Arquivada'),
        default     => ui_badge('Rascunho'),
    };
}

/**
 * Cabeçalho de página: título, subtítulo e área de ações (HTML confiável montado pela página).
 */
function ui_page_header(string $title, string $subtitle = '', string $actions = ''): string
{
    $html = '<div class="page-head"><div><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
    if ($subtitle !== '') {
        $html .= '<p>' . htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    $html .= '</div>';
    if ($actions !== '') {
        $html .= '<div class="page-head__actions">' . $actions . '</div>';
    }
    return $html . '</div>';
}

/**
 * Alertas de sucesso/erro/aviso. Todo texto é escapado aqui.
 */
function ui_alerts(string $ok = '', string $bad = '', string $warn = ''): string
{
    $items = [['ok', $ok, 'check-c'], ['bad', $bad, 'alert'], ['warn', $warn, 'alert']];
    $html = '';
    foreach ($items as [$tone, $text, $icon]) {
        if ($text === '') {
            continue;
        }
        $html .= '<div class="alert alert--' . $tone . '" role="' . ($tone === 'ok' ? 'status' : 'alert') . '">'
            . ui_icon($icon)
            . '<div class="alert__text">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<button type="button" class="alert__close" data-dismiss aria-label="Fechar aviso">' . ui_icon('x', 'ic--sm') . '</button>'
            . '</div>';
    }
    return $html;
}

/**
 * Estado de falha ao carregar dados. Detalhe técnico só é exibido para administradores.
 */
function ui_load_error(string $detail = ''): string
{
    $html = '<div class="alert alert--bad" role="alert">' . ui_icon('alert')
        . '<div class="alert__text"><strong>Não foi possível carregar estes dados.</strong> '
        . 'Verifique a conexão com o banco e se a estrutura das tabelas está atualizada.';
    if ($detail !== '' && ($_SESSION['usuario_papel'] ?? '') === 'admin') {
        $html .= '<details><summary>Detalhes técnicos</summary><code>' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</code></details>';
    }
    return $html . '</div></div>';
}

/**
 * Estado vazio. $action é HTML confiável opcional (botão/link).
 */
function ui_empty(string $icon, string $title, string $text = '', string $action = ''): string
{
    $html = '<div class="empty"><div class="empty__icon">' . ui_icon($icon, 'ic--lg') . '</div>'
        . '<h3 class="empty__title">' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h3>';
    if ($text !== '') {
        $html .= '<p class="empty__text">' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    return $html . $action . '</div>';
}

/**
 * Tempo relativo em português ("há 5 min", "ontem", "12/11/2026").
 */
function ui_ago(?string $datetime): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    if (!$ts) {
        return '';
    }
    $d = time() - $ts;
    if ($d < 60) {
        return 'agora mesmo';
    }
    if ($d < 3600) {
        return 'há ' . intdiv($d, 60) . ' min';
    }
    if ($d < 86400) {
        return 'há ' . intdiv($d, 3600) . ' h';
    }
    if ($d < 172800) {
        return 'ontem';
    }
    return date('d/m/Y', $ts);
}

/**
 * Data no formato brasileiro (dd/mm/aaaa [hh:mm]).
 */
function ui_date(?string $datetime, bool $withTime = true): string
{
    $ts = $datetime ? strtotime($datetime) : false;
    if (!$ts) {
        return '—';
    }
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $ts);
}
