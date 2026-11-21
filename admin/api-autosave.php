<?php
/**
 * O FAROL — API de Autosave & Heartbeat de Edição Simultânea
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

require_login();
header('Content-Type: application/json; charset=utf-8');

$pdo = getDBConnection();
if (!$pdo) {
    echo json_encode(['sucesso' => false, 'erro' => 'Banco de dados inacessível']);
    exit;
}

$noticia_id = (int)($_POST['id'] ?? 0);
$usuario_id = (int)($_SESSION['usuario_id'] ?? 0);
$csrf = $_POST['csrf_token'] ?? '';
$action = $_POST['action'] ?? 'autosave'; // 'autosave', 'heartbeat', 'release'

if (!verify_csrf($csrf)) {
    echo json_encode(['sucesso' => false, 'erro' => 'Token CSRF inválido']);
    exit;
}

// 1. Liberação de bloqueio (ex: ao fechar ou sair da página)
if ($action === 'release') {
    if ($noticia_id > 0 && $usuario_id > 0) {
        $stmtRel = $pdo->prepare("UPDATE noticias SET editando_por = NULL, editando_em = NULL WHERE id = ? AND editando_por = ?");
        $stmtRel->execute([$noticia_id, $usuario_id]);
    }
    echo json_encode(['sucesso' => true]);
    exit;
}

// 2. Heartbeat: verifica se outro usuário está editando e renova o lock
if ($action === 'heartbeat') {
    if ($noticia_id > 0) {
        $stmtLock = $pdo->prepare("SELECT n.editando_por, n.editando_em, u.nome as editor_nome 
                                   FROM noticias n 
                                   LEFT JOIN usuarios u ON n.editando_por = u.id 
                                   WHERE n.id = ?");
        $stmtLock->execute([$noticia_id]);
        $lock = $stmtLock->fetch();

        // Se estiver sendo editado por outro usuário há menos de 60 segundos
        if ($lock && !empty($lock['editando_por']) && (int)$lock['editando_por'] !== $usuario_id) {
            $lockTime = strtotime($lock['editando_em'] ?? '');
            if ($lockTime && (time() - $lockTime) < 60) {
                echo json_encode([
                    'bloqueado' => true,
                    'editor_nome' => $lock['editor_nome'] ?? 'Outro usuário',
                    'segundos_atras' => time() - $lockTime
                ]);
                exit;
            }
        }

        // Renova o lock para o usuário atual
        $stmtRenew = $pdo->prepare("UPDATE noticias SET editando_por = ?, editando_em = NOW() WHERE id = ?");
        $stmtRenew->execute([$usuario_id, $noticia_id]);
    }
    echo json_encode(['bloqueado' => false]);
    exit;
}

// 3. Autosave de Rascunho
if ($action === 'autosave') {
    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $conteudo = sanitize_html_content($_POST['conteudo'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 1);

    if (empty($titulo)) {
        echo json_encode(['sucesso' => false, 'erro' => 'Título vazio']);
        exit;
    }

    try {
        if ($noticia_id > 0) {
            // Verifica conflito de lock
            $stmtLock = $pdo->prepare("SELECT editando_por, editando_em FROM noticias WHERE id = ?");
            $stmtLock->execute([$noticia_id]);
            $lock = $stmtLock->fetch();
            if ($lock && !empty($lock['editando_por']) && (int)$lock['editando_por'] !== $usuario_id) {
                if (time() - strtotime($lock['editando_em']) < 60) {
                    echo json_encode(['sucesso' => false, 'conflito' => true, 'erro' => 'Outro usuário assumiu a edição desta matéria']);
                    exit;
                }
            }

            // Atualiza rascunho sem alterar status publicado/agendado se já estiver
            $stmtUp = $pdo->prepare("UPDATE noticias SET titulo = ?, subtitulo = ?, conteudo = ?, editando_por = ?, editando_em = NOW(), atualizado_em = NOW() WHERE id = ?");
            $stmtUp->execute([$titulo, $subtitulo, $conteudo, $usuario_id, $noticia_id]);

            echo json_encode([
                'sucesso' => true,
                'id' => $noticia_id,
                'salvo_em' => date('H:i:s'),
                'mensagem' => 'Rascunho salvo automaticamente'
            ]);
            exit;
        } else {
            // Nova matéria: insere como rascunho
            if (empty($slug)) {
                $slug = slugify($titulo) . '-' . substr(bin2hex(random_bytes(4)), 0, 4);
            }
            $stmtIn = $pdo->prepare("INSERT INTO noticias (categoria_id, titulo, subtitulo, conteudo, slug, status, editando_por, editando_em, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, 'rascunho', ?, NOW(), ?, NOW())");
            $stmtIn->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $slug, $usuario_id, $usuario_id]);
            $newId = (int)$pdo->lastInsertId();

            echo json_encode([
                'sucesso' => true,
                'id' => $newId,
                'salvo_em' => date('H:i:s'),
                'mensagem' => 'Criado e salvo como rascunho'
            ]);
            exit;
        }
    } catch (Exception $e) {
        echo json_encode(['sucesso' => false, 'erro' => $e->getMessage()]);
        exit;
    }
}
