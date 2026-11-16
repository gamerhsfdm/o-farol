<?php
/**
 * O FAROL - Configuração de Conexão com Banco de Dados (PDO)
 * Ambiente: Apache / PHP / MySQL (XAMPP)
 */

declare(strict_types=1);

// Evita acesso direto indesejado
if (!defined('FAROL_APP')) {
    define('FAROL_APP', true);
}

// Configurações padrão do ambiente XAMPP
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'o_farol');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// URL Base da Aplicação
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    // Se estiver na raiz ou dentro de admin/api, normaliza para a raiz do projeto
    $root = preg_replace('/(\/admin|\/api).*$/', '', $script);
    $root = rtrim($root, '/\\');
    define('BASE_URL', $protocol . $host . $root);
}

/**
 * Obtém conexão PDO singleton segura
 */
function getDBConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Erro de Conexão PDO [O Farol]: " . $e->getMessage());
        return null;
    }
}
