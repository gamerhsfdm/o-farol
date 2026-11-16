<?php
/**
 * O FAROL - Logout do Painel CMS
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

log_audit('logout', 'usuarios', $_SESSION['usuario_id'] ?? null, 'Usuário encerrou a sessão');

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: " . BASE_URL . "/admin/index.php");
exit;
