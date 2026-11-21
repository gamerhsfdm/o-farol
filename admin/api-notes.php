<?php
/**
 * O FAROL — API de Notas Editoriais (Diálogo Orientador-Redator)
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['sucesso' => false, 'erro' => 'Banco inacessível']);
    exit;
}

$noticia_id = (int)($_REQUEST['noticia_id'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$action = $_REQUEST['action'] ?? 'list'; // 'list', 'add', 'toggle'

if ($noticia_id <= 0) {
    echo json_encode(['sucesso' => false, 'erro' => 'Notícia inválida']);
    exit;
}

// 1. Listar notas da notícia
if ($action === 'list') {
    try {
        $stmt = $pdo->prepare("SELECT n.*, u.nome as autor_nome, u.papel as autor_papel 
                               FROM noticia_notas n 
                               JOIN usuarios u ON n.usuario_id = u.id 
                               WHERE n.noticia_id = ? 
                               ORDER BY n.criado_em ASC");
        $stmt->execute([$noticia_id]);
        $notas = $stmt->fetchAll();

        echo json_encode(['sucesso' => true, 'notas' => $notas]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
    exit;
}

// 2. Adicionar nova nota
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['sucesso' => false, 'erro' => 'CSRF inválido']);
        exit;
    }

    $texto = trim($_POST['texto'] ?? '');
    if (empty($texto)) {
        echo json_encode(['sucesso' => false, 'erro' => 'Nota vazia']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO noticia_notas (noticia_id, usuario_id, texto, status_resolvido, criado_em) VALUES (?, ?, ?, 0, NOW())");
        $stmt->execute([$noticia_id, $usuario_id, $texto]);
        $nota_id = (int)$pdo->lastInsertId();

        log_audit('adicionar_nota_editorial', 'noticia_notas', $nota_id, "Nova nota adicionada na matéria #{$noticia_id}");

        echo json_encode([
            'sucesso' => true,
            'nota' => [
                'id' => $nota_id,
                'texto' => $texto,
                'status_resolvido' => 0,
                'autor_nome' => $_SESSION['usuario_nome'] ?? 'Usuário',
                'autor_papel' => $_SESSION['usuario_papel'] ?? 'editor',
                'criado_em' => date('Y-m-d H:i:s')
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
    exit;
}

// 3. Alternar status resolvido / pendente
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        echo json_encode(['sucesso' => false, 'erro' => 'CSRF inválido']);
        exit;
    }

    $nota_id = (int)($_POST['nota_id'] ?? 0);
    try {
        $stmt = $pdo->prepare("UPDATE noticia_notas SET status_resolvido = 1 - status_resolvido WHERE id = ? AND noticia_id = ?");
        $stmt->execute([$nota_id, $noticia_id]);

        echo json_encode(['sucesso' => true]);
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
    }
    exit;
}
