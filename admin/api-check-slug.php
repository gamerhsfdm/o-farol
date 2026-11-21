<?php
/**
 * O FAROL — API de Verificação de Slug em Tempo Real
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$slug = trim($_GET['slug'] ?? '');
$current_id = (int)($_GET['id'] ?? 0);

if (empty($slug)) {
    echo json_encode(['disponivel' => false, 'motivo' => 'Slug vazio']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['disponivel' => true, 'mensagem' => 'Banco offline']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, titulo, status FROM noticias WHERE slug = ? AND id != ? LIMIT 1");
    $stmt->execute([$slug, $current_id]);
    $existente = $stmt->fetch();

    if ($existente) {
        echo json_encode([
            'disponivel' => false,
            'motivo' => 'Já utilizado',
            'noticia_id' => $existente['id'],
            'titulo' => $existente['titulo'],
            'status' => $existente['status']
        ]);
    } else {
        echo json_encode(['disponivel' => true]);
    }
} catch (Exception $e) {
    echo json_encode(['disponivel' => true, 'erro' => $e->getMessage()]);
}
