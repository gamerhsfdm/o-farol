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
// Suporte a voto único (opcao_id) ou múltiplo (opcoes_ids[])
$opcao_id = filter_input(INPUT_POST, 'opcao_id', FILTER_VALIDATE_INT);
$opcoes_ids = array_filter(array_map('intval', (array)($_POST['opcoes_ids'] ?? [])));

if (!$opcao_id && !empty($opcoes_ids)) {
    // Voto múltiplo
    $selecionadas = $opcoes_ids;
} elseif ($opcao_id) {
    // Voto individual
    $selecionadas = [$opcao_id];
} else {
    $selecionadas = [];
}

if (!$enquete_id || empty($selecionadas)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Selecione ao menos uma opção para votar.']);
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Banco de dados inacessível no momento.']);
    exit;
}

try {
    // Valida regras da enquete (se é múltipla escolha, max de escolhas, prazo de encerramento)
    $stmtEnq = $pdo->prepare("SELECT * FROM enquetes WHERE id = ? AND ativo = 1");
    $stmtEnq->execute([$enquete_id]);
    $enqueteDados = $stmtEnq->fetch();

    if (!$enqueteDados) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta enquete não está ativa no momento.']);
        exit;
    }

    if (!empty($enqueteDados['data_fim']) && strtotime($enqueteDados['data_fim'] . ' 23:59:59') < time()) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta enquete já foi encerrada.']);
        exit;
    }

    $permitirMultipla = (int)($enqueteDados['permitir_multipla'] ?? 0) === 1;
    $maxEscolhas = max(1, (int)($enqueteDados['max_escolhas'] ?? 1));

    if (!$permitirMultipla && count($selecionadas) > 1) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Esta enquete permite apenas um único voto por participante.']);
        exit;
    }

    if ($permitirMultipla && count($selecionadas) > $maxEscolhas) {
        echo json_encode(['sucesso' => false, 'mensagem' => "Você pode selecionar no máximo {$maxEscolhas} opções nesta enquete."]);
        exit;
    }

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
    $stmtInc = $pdo->prepare("UPDATE enquete_opcoes SET votos_count = votos_count + 1 WHERE id = ? AND enquete_id = ?");

    foreach ($selecionadas as $opId) {
        $stmtVote->execute([$enquete_id, $opId, $ip_hash]);
        $stmtInc->execute([$opId, $enquete_id]);
    }

    $pdo->commit();

    // 4. Retorna estatísticas atualizadas
    $stmtAll = $pdo->prepare("SELECT id, opcao, votos_count, cor_destaque FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem ASC");
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
        'exibir_resultados' => $enqueteDados['exibir_resultados_apos'] !== 'apos_encerrar',
        'resultados' => $opcoes
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao registrar voto: ' . $e->getMessage()]);
}
