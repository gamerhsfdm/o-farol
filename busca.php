<?php
/**
 * O FAROL - Busca Funcional e Moderna no Acervo
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$termo = trim($_GET['q'] ?? '');
$pdo = getDBConnection();
$resultados = [];

if ($pdo && !empty($termo)) {
    try {
        $busca = '%' . $termo . '%';
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome 
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.status = 'publicado' 
                                 AND (n.titulo LIKE ? OR n.subtitulo LIKE ? OR n.conteudo LIKE ? OR n.meta_keywords LIKE ?) 
                               ORDER BY n.publicado_em DESC LIMIT 25");
        $stmt->execute([$busca, $busca, $busca, $busca]);
        $resultados = $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('O Farol: falha na busca pública: ' . $e->getMessage());
    }
}

$page_title = !empty($termo) ? 'Busca: ' . $termo : 'Pesquisa no Acervo';
require_once __DIR__ . '/includes/header.php';
?>

<main id="conteudo-principal" class="container-farol py-8 site-listing site-search">
  <header class="listing-masthead editorial-card p-6 sm:p-8 mb-8 bg-white">
    <div class="flex items-center gap-2 mb-2">
      <span class="editorial-badge badge-teal text-[9px]">ACERVO DIGITAL</span>
    </div>
    <h1 class="text-3xl sm:text-4xl font-headline font-extrabold text-[#111827] mb-4">
      Pesquisar no Acervo do Jornal
    </h1>
    
    <form action="<?= BASE_URL ?>/busca.php" method="GET" class="flex flex-col sm:flex-row gap-3 max-w-2xl">
      <div class="relative flex-1">
        <input type="text" name="q" value="<?= e($termo) ?>" placeholder="Digite termos, assuntos, projetos, autores..." 
               class="w-full border border-gray-300 rounded px-4 py-2.5 text-sm focus:outline-none focus:border-[#0f4c5c] bg-gray-50 focus:bg-white transition-all">
      </div>
      <button type="submit" class="btn-editorial-primary py-2.5 px-6">
        Pesquisar
      </button>
    </form>
    
    <?php if (!empty($termo)): ?>
      <p class="text-xs text-gray-500 mt-4 pt-3 border-t border-gray-100 font-sans">
        Resultados para "<strong><?= e($termo) ?></strong>" • <strong><?= count($resultados) ?></strong> reportagens encontradas
      </p>
    <?php endif; ?>
  </header>

  <?php if (!empty($termo)): ?>
    <?php if (!empty($resultados)): ?>
      <div class="space-y-4">
        <?php foreach ($resultados as $item): ?>
          <article class="news-card news-card--horizontal editorial-card p-6 bg-white hover:border-gray-400 transition-colors">
            <span class="editorial-badge badge-teal text-[9px]"><?= strtoupper(e($item['categoria_nome'])) ?></span>
            <h2 class="text-xl sm:text-2xl font-headline font-bold text-[#111827] leading-snug my-2 hover:text-[#b91c1c] transition-colors">
              <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($item['slug']) ?>">
                <?= e($item['titulo']) ?>
              </a>
            </h2>
            <p class="text-xs sm:text-sm text-gray-600 font-sans leading-relaxed mb-3">
              <?= e($item['subtitulo']) ?>
            </p>
            <div class="text-[11px] text-gray-400 font-sans flex items-center gap-3">
              <span>Publicado em <?= date('d/m/Y', strtotime($item['publicado_em'])) ?></span>
              <span>•</span>
              <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($item['slug']) ?>" class="text-[#b91c1c] font-bold hover:underline">
                Ler matéria completa →
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="editorial-card p-12 text-center bg-white">
        <p class="text-gray-500 font-headline italic text-base">Nenhuma matéria foi encontrada com os termos informados.</p>
        <p class="text-xs text-gray-400 mt-1">Experimente buscar por palavras-chave mais genéricas (ex: robótica, futsal, leitura, feira).</p>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
