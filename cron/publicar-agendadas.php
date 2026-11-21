<?php
/**
 * O FAROL — Script de Publicação Automática de Notícias Agendadas
 * Execução via CLI / Cron: php cron/publicar-agendadas.php
 * Ou acionado como rotina de fallback leve ao carregar a página inicial.
 */
declare(strict_types=1);

// Permite execução CLI ou chamada interna protegida
if (php_sapi_name() !== 'cli' && !defined('CRON_ALLOWED')) {
    // Se acessado via web diretamente, requer chave secreta ou bloqueia
    $secret = $_GET['secret'] ?? '';
    // Permite se houver token ou se chamado pelo próprio servidor
    if ($secret !== 'o-farol-cron-key' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '127.0.0.1' && ($_SERVER['REMOTE_ADDR'] ?? '') !== '::1') {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso não autorizado ao script cron']);
        exit;
    }
}

require_once __DIR__ . '/../includes/functions.php';

$pdo = getDBConnection();
if (!$pdo) {
    if (php_sapi_name() === 'cli') {
        echo "[CRON " . date('Y-m-d H:i:s') . "] Erro: Banco de dados inacessível.\n";
    }
    exit(1);
}

try {
    // Busca todas as matérias com status 'agendado' cuja data de agendamento já passou
    $stmt = $pdo->prepare("SELECT id, titulo, agendado_para FROM noticias WHERE status = 'agendado' AND agendado_para <= NOW()");
    $stmt->execute();
    $agendadas = $stmt->fetchAll();

    $total = count($agendadas);
    if ($total === 0) {
        if (php_sapi_name() === 'cli') {
            echo "[CRON " . date('Y-m-d H:i:s') . "] Nenhuma matéria agendada para publicação no momento.\n";
        }
        exit(0);
    }

    $stmtUp = $pdo->prepare("UPDATE noticias SET status = 'publicado', publicado_em = COALESCE(agendado_para, NOW()), atualizado_em = NOW() WHERE id = ?");

    $publicadas = [];
    foreach ($agendadas as $item) {
        $stmtUp->execute([$item['id']]);
        $publicadas[] = "#{$item['id']} '{$item['titulo']}'";
        log_audit('publicacao_agendada', 'noticias', (int)$item['id'], "Publicação agendada disparada automaticamente");
    }

    $msg = "[CRON " . date('Y-m-d H:i:s') . "] {$total} matéria(s) publicada(s) com sucesso: " . implode(', ', $publicadas) . "\n";
    if (php_sapi_name() === 'cli') {
        echo $msg;
    } else {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => true, 'total' => $total, 'publicadas' => $publicadas]);
    }

} catch (Exception $e) {
    if (php_sapi_name() === 'cli') {
        echo "[CRON " . date('Y-m-d H:i:s') . "] Erro na execução: " . $e->getMessage() . "\n";
    }
    exit(1);
}
