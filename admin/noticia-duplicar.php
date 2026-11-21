<?php
/**
 * O FAROL — Duplicar Notícia como Rascunho
 */
declare(strict_types=1);
require_once __DIR__ . '/../includes/functions.php';

require_login();

$id = (int)($_POST['id'] ?? ($_GET['id'] ?? 0));
$token = $_POST['csrf_token'] ?? ($_GET['csrf_token'] ?? '');

if (!verify_csrf($token) || $id <= 0) {
    header('Location: ' . BASE_URL . '/admin/noticias.php?erro=' . urlencode('Ação não autorizada ou dados inválidos.'));
    exit;
}

$pdo = getDBConnection();
if (!$pdo) {
    header('Location: ' . BASE_URL . '/admin/noticias.php?erro=' . urlencode('Banco de dados indisponível.'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM noticias WHERE id = ?");
    $stmt->execute([$id]);
    $orig = $stmt->fetch();

    if (!$orig) {
        header('Location: ' . BASE_URL . '/admin/noticias.php?erro=' . urlencode('Notícia original não encontrada.'));
        exit;
    }

    $novoTitulo = '[Cópia] ' . $orig['titulo'];
    $novoSlug = slugify($novoTitulo) . '-' . substr(bin2hex(random_bytes(4)), 0, 4);
    $usuarioId = $_SESSION['usuario_id'] ?? null;

    $stmtIn = $pdo->prepare("INSERT INTO noticias (categoria_id, edicao_id, titulo, subtitulo, conteudo, chapeu, citacao_destaque, citacao_autor, imagem_capa, legenda_capa, credito_foto, posicao_homepage, ordem_home, meta_keywords, meta_description, slug, tempo_leitura, professor_orientador, status, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'nenhuma', 0, ?, ?, ?, ?, ?, 'rascunho', ?, NOW())");
    $stmtIn->execute([
        $orig['categoria_id'], $orig['edicao_id'], $novoTitulo, $orig['subtitulo'],
        $orig['conteudo'], $orig['chapeu'], $orig['citacao_destaque'], $orig['citacao_autor'],
        $orig['imagem_capa'], $orig['legenda_capa'], $orig['credito_foto'],
        $orig['meta_keywords'], $orig['meta_description'], $novoSlug, $orig['tempo_leitura'],
        $orig['professor_orientador'], $usuarioId
    ]);
    $newId = (int)$pdo->lastInsertId();

    // Duplica autores vinculados (N:N)
    $stmtAu = $pdo->prepare("SELECT autor_id FROM noticia_autores WHERE noticia_id = ?");
    $stmtAu->execute([$id]);
    $autores = $stmtAu->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($autores)) {
        $stmtInNa = $pdo->prepare("INSERT INTO noticia_autores (noticia_id, autor_id) VALUES (?, ?)");
        foreach ($autores as $aId) {
            $stmtInNa->execute([$newId, $aId]);
        }
    }

    // Cria versão #1 para a duplicata
    try {
        $stmtVer = $pdo->prepare("INSERT INTO noticia_versoes (noticia_id, usuario_id, titulo, subtitulo, conteudo, imagem_capa, versao, motivo_alteracao, criado_em) VALUES (?, ?, ?, ?, ?, ?, 1, 'Duplicado da matéria #{$id}', NOW())");
        $stmtVer->execute([$newId, $usuarioId, $novoTitulo, $orig['subtitulo'], $orig['conteudo'], $orig['imagem_capa']]);
    } catch (Exception $eVer) {}

    log_audit('duplicar_noticia', 'noticias', $newId, "Matéria #{$newId} duplicada a partir de #{$id}");

    header('Location: ' . BASE_URL . '/admin/editor.php?id=' . $newId . '&msg=' . urlencode('Reportagem duplicada com sucesso como rascunho!'));
    exit;

} catch (Exception $e) {
    header('Location: ' . BASE_URL . '/admin/noticias.php?erro=' . urlencode('Erro ao duplicar: ' . $e->getMessage()));
    exit;
}
