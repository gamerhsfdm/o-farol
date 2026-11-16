<?php
/**
 * O FAROL - API de Votação da Enquete com Prevenção de Voto Duplicado
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
    exit;
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf($csrf)) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Token de segurança inválido.']);
    exit;
}

$enquete_id = filter_input(INPUT_POST, 'enquete_id', FILTER_VALIDATE_INT);
$opcao_id = filter_input(INPUT_POST, 'opcao_id', FILTER_VALIDATE_INT);

if (!$enquete_id || !$opcao_id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione uma opção para votar.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Banco de dados inacessível no momento.']);
    exit;
}

try {
    // 1. Identificador anônimo do usuário (Hash seguro de IP + UserAgent com Salt)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $salt = "farol_enquete_salt_2026";
    $ip_hash = hash('sha256', $ip . $agent . $salt);

    // 2. Verifica se já votou nesta enquete
    $stmtCheck = $pdo->prepare("SELECT id FROM enquete_votos WHERE enquete_id = ? AND ip_hash = ? LIMIT 1");
    $stmtCheck->execute([$enquete_id, $ip_hash]);
    if ($stmtCheck->fetch()) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Você já participou desta enquete. Agradecemos sua colaboração!']);
        exit;
    }

    // 3. Registra voto em transação
    $pdo->beginTransaction();

    $stmtVote = $pdo->prepare("INSERT INTO enquete_votos (enquete_id, opcao_id, ip_hash) VALUES (?, ?, ?)");
    $stmtVote->execute([$enquete_id, $opcao_id, $ip_hash]);

    $stmtInc = $pdo->prepare("UPDATE enquete_opcoes SET votos_count = votos_count + 1 WHERE id = ? AND enquete_id = ?");
    $stmtInc->execute([$opcao_id, $enquete_id]);

    $pdo->commit();

    // 4. Retorna estatísticas atualizadas
    $stmtAll = $pdo->prepare("SELECT id, opcao, votos_count FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem ASC");
    $stmtAll->execute([$enquete_id]);
    $opcoes = $stmtAll->fetchAll();

    $totalVotos = array_sum(array_column($opcoes, 'votos_count'));
    foreach ($opcoes as &$op) {
        $op['percentual'] = $totalVotos > 0 ? round(($op['votos_count'] / $totalVotos) * 100, 1) : 0;
    }

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Seu voto foi registrado com sucesso!',
        'total_votos' => $totalVotos,
        'resultados' => $opcoes
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registrar voto: ' . $e->getMessage()]);
}
