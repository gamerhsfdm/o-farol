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
