<?php
/**
 * O FAROL - Funções Utilitárias, Sessão e Segurança
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Sanitiza saída para evitar XSS
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Gera ou recupera token CSRF para formulários
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado
 */
function verify_csrf(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Formata data em português, sem depender de locale/intl do servidor.
 * Aceita os tokens de date(); "F" vira o mês por extenso e "M" a abreviação (jan, fev...).
 * Use "\\" para escapar caracteres literais, como em date().
 */
function format_date_ptbr(?string $date, string $format = 'd/m/Y'): ?string {
    if (empty($date)) return null;
    $ts = strtotime($date);
    if ($ts === false) return null;

    $meses = ['janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
    $abrev = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
    $n = (int)date('n', $ts) - 1;

    $out = '';
    $len = strlen($format);
    for ($i = 0; $i < $len; $i++) {
        $ch = $format[$i];
        if ($ch === '\\' && $i + 1 < $len) { $out .= $format[++$i]; continue; }
        if ($ch === 'F') { $out .= $meses[$n]; continue; }
        if ($ch === 'M') { $out .= $abrev[$n]; continue; }
        $out .= date($ch, $ts);
    }
    return $out;
}

/**
 * Gera slug limpo para URLs amigáveis
 */
function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'n-a-' . time() : $text;
}

/**
 * Obtém todas as configurações institucionais
 */
function get_site_settings(): array {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }

    $pdo = getDBConnection();
    $settings = [];

    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT chave, valor FROM configuracoes");
            while ($row = $stmt->fetch()) {
                $settings[$row['chave']] = $row['valor'];
            }
        } catch (Exception $e) {
            // Em caso de tabela ainda não importada
        }
    }

    // Valores padrão caso o banco ainda esteja sendo importado
    $defaults = [
        'site_nome' => 'O FAROL',
        'site_subtitulo' => 'Jornal Escolar Sesc-Senac',
        'edicao_atual_numero' => '18',
        'edicao_atual_data' => 'Junho de 2026',
        'site_descricao' => 'Órgão informativo laboratorial do Centro Integrado de Ensino Médio e Técnico.',
        'banner_chamada_titulo' => 'Tem uma história, projeto científico ou evento na sua turma?',
        'banner_chamada_texto' => 'A redação de "O Farol" é feita por e para os estudantes. Envie sua sugestão de pauta, resenha de livro ou resultado esportivo para os nossos repórteres.',
        'banner_chamada_botao' => 'ENVIE SUA PAUTA / SUGESTÃO',
        'modulo_cabecalho' => '1',
        'modulo_manchete' => '1',
        'modulo_destaques' => '1',
        'modulo_ciencia' => '1',
        'modulo_jogos' => '1',
        'modulo_cultura' => '1',
        'modulo_opiniao' => '1',
        'modulo_flagrantes' => '1',
        'modulo_clube_livro' => '1',
        'modulo_processos' => '0',
        'modulo_enquete' => '1',
        'modulo_banner_pauta' => '1'
    ];

    $settings = array_merge($defaults, $settings);
    return $settings;
}

/**
 * Obtém categorias ativas para o menu
 */
function get_active_categories(): array {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM categorias WHERE ativo = 1 AND no_menu = 1 ORDER BY ordem ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Autenticação e Verificação de Usuário
 */
function is_logged_in(): bool {
    return !empty($_SESSION['usuario_id']);
}

function get_logged_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id' => $_SESSION['usuario_id'],
        'nome' => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email' => $_SESSION['usuario_email'] ?? '',
        'papel' => $_SESSION['usuario_papel'] ?? 'colaborador'
    ];
}

function require_login(array $allowed_roles = []): void {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "/admin/index.php");
        exit;
    }

    if (!empty($allowed_roles)) {
        $role = $_SESSION['usuario_papel'] ?? 'colaborador';
        if (!in_array($role, $allowed_roles, true) && $role !== 'admin') {
            http_response_code(403);
            die("Acesso Negado: Permissão insuficiente para acessar este recurso.");
        }
    }
}

/**
 * Registra ação no log de auditoria
 */
function log_audit(string $acao, ?string $tabela = null, ?int $registro_id = null, ?string $detalhes = null): void {
    $pdo = getDBConnection();
    if (!$pdo) return;

    try {
        $usuario_id = $_SESSION['usuario_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("INSERT INTO logs_auditoria (usuario_id, acao, tabela_afetada, registro_id, detalhes, ip) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$usuario_id, $acao, $tabela, $registro_id, $detalhes, $ip]);
    } catch (Exception $e) {
        // Silencioso
    }
}

/**
 * Sanitizador de HTML por allowlist estrita para o corpo das matérias.
 * Permite apenas tags e atributos editoriais seguros:
 * p, h2, h3, strong, em, a (href http/https/mailto, rel, target), ul, ol, li,
 * blockquote, img (src de uploads/ ou http/https, alt, title), figure, figcaption, br.
 * Remove scripts, styles, iframes, atributos on*, javascript:, etc.
 */
function sanitize_html_content(?string $html): string {
    if (empty($html)) {
        return '';
    }

    $trimmed = trim($html);
    if ($trimmed === '') {
        return '';
    }

    $dom = new DOMDocument();
    // Suprime warnings de parsing de trechos HTML incompletos
    $prev_err = libxml_use_internal_errors(true);
    // Adiciona meta para UTF-8 correto
    $dom->loadHTML('<?xml encoding="utf-8" ?><div>' . $trimmed . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    libxml_use_internal_errors($prev_err);

    $allowedTags = [
        'p', 'h2', 'h3', 'strong', 'b', 'em', 'i', 'a',
        'ul', 'ol', 'li', 'blockquote', 'img', 'figure', 'figcaption', 'br'
    ];

    $cleanNode = function (DOMNode $node) use (&$cleanNode, $allowedTags): void {
        if ($node->nodeType === XML_ELEMENT_NODE) {
            $tagName = strtolower($node->nodeName);

            // Se a tag não for permitida, remove ou desenvelopa
            if (!in_array($tagName, $allowedTags, true)) {
                $parent = $node->parentNode;
                if ($parent) {
                    if (in_array($tagName, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button'], true)) {
                        $parent->removeChild($node);
                        return;
                    }
                    // Para outras tags (ex: div, span), preserva o conteúdo movendo filhos para o pai
                    while ($node->firstChild) {
                        $parent->insertBefore($node->firstChild, $node);
                    }
                    $parent->removeChild($node);
                }
                return;
            }

            // Sanitiza atributos
            if ($node->hasAttributes()) {
                $attrsToRemove = [];
                foreach ($node->attributes as $attr) {
                    $attrName = strtolower($attr->nodeName);
                    $attrValue = trim($attr->nodeValue);

                    // Remove qualquer evento on* (onclick, onerror, onload...)
                    if (str_starts_with($attrName, 'on')) {
                        $attrsToRemove[] = $attrName;
                        continue;
                    }

                    // Validação de atributos específicos
                    if ($tagName === 'a') {
                        if ($attrName === 'href') {
                            $lowerHref = strtolower($attrValue);
                            if (str_starts_with($lowerHref, 'javascript:') || str_starts_with($lowerHref, 'data:') || str_starts_with($lowerHref, 'vbscript:')) {
                                $attrsToRemove[] = $attrName;
                            }
                        } elseif (!in_array($attrName, ['href', 'target', 'rel', 'title', 'class'], true)) {
                            $attrsToRemove[] = $attrName;
                        }
                    } elseif ($tagName === 'img') {
                        if ($attrName === 'src') {
                            $lowerSrc = strtolower($attrValue);
                            if (str_starts_with($lowerSrc, 'javascript:') || str_starts_with($lowerSrc, 'vbscript:')) {
                                $attrsToRemove[] = $attrName;
                            }
                        } elseif (!in_array($attrName, ['src', 'alt', 'title', 'class', 'loading', 'decoding'], true)) {
                            $attrsToRemove[] = $attrName;
                        }
                    } else {
                        // Para tags gerais, permite apenas class ou title básico (remove style)
                        if (!in_array($attrName, ['class', 'title'], true)) {
                            $attrsToRemove[] = $attrName;
                        }
                    }
                }

                foreach ($attrsToRemove as $attrName) {
                    $node->removeAttribute($attrName);
                }

                // Garante rel seguro em links
                if ($tagName === 'a' && $node->hasAttribute('href')) {
                    $node->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        // Processa filhos recursivamente
        $child = $node->firstChild;
        while ($child) {
            $next = $child->nextSibling;
            $cleanNode($child);
            $child = $next;
        }
    };

    // A raiz do DOM é o <div> encapsulado
    $body = $dom->getElementsByTagName('div')->item(0);
    if ($body) {
        $child = $body->firstChild;
        while ($child) {
            $next = $child->nextSibling;
            $cleanNode($child);
            $child = $next;
        }

        // Serializa apenas o conteúdo interno da div
        $output = '';
        foreach ($body->childNodes as $c) {
            $output .= $dom->saveHTML($c);
        }
        return $output;
    }

    return '';
}
