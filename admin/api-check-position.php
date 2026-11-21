<?php
/**
 * O FAROL — API de Verificação de Ocupação de Posição na Homepage
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$posicao = trim($_GET['posicao'] ?? 'nenhuma');
$current_id = (int)($_GET['id'] ?? 0);

if (empty($posicao) || $posicao === 'nenhuma') {
    echo json_encode(['ocupada' => false]);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['ocupada' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, titulo, status, publicado_em FROM noticias WHERE posicao_homepage = ? AND id != ? AND status = 'publicado' ORDER BY publicado_em DESC LIMIT 1");
    $stmt->execute([$posicao, $current_id]);
    $atual = $stmt->fetch();

    if ($atual) {
        echo json_encode([
            'ocupada' => true,
            'noticia_id' => $atual['id'],
            'titulo' => $atual['titulo']
        ]);
    } else {
        echo json_encode(['ocupada' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['ocupada' => false, 'erro' => $e->getMessage()]);
}
