<?php
/**
 * O FAROL - Listagem por Categorias Editoriais (Layout Modernizado)
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$pdo = getDBConnection();

$categoria = null;
$noticias = [];
$total_noticias = 0;
$pagina = max(1, (int)($_GET['p'] ?? 1));
$por_pagina = 6;
$offset = ($pagina - 1) * $por_pagina;

if ($pdo && !empty($slug)) {
    try {
        $stmtCat = $pdo->prepare("SELECT * FROM categorias WHERE slug = ? AND ativo = 1 LIMIT 1");
        $stmtCat->execute([$slug]);
        $categoria = $stmtCat->fetch();

        if ($categoria) {
            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM noticias WHERE categoria_id = ? AND status = 'publicado'");
            $stmtCount->execute([$categoria['id']]);
            $total_noticias = (int)$stmtCount->fetchColumn();

            $stmtNews = $pdo->prepare("SELECT * FROM noticias 
                                       WHERE categoria_id = ? AND status = 'publicado' 
                                       ORDER BY publicado_em DESC 
                                       LIMIT ? OFFSET ?");
            $stmtNews->bindValue(1, $categoria['id'], PDO::PARAM_INT);
            $stmtNews->bindValue(2, $por_pagina, PDO::PARAM_INT);
            $stmtNews->bindValue(3, $offset, PDO::PARAM_INT);
            $stmtNews->execute();
            $noticias = $stmtNews->fetchAll();
        }
    } catch (Exception $e) {}
}

if (!$categoria) {
    http_response_code(404);
    $page_title = "Editoria Não Encontrada";
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container-farol py-20 text-center">
            <h2 class="text-3xl font-headline font-bold text-gray-800 mb-2">Editoria não encontrada</h2>
            <p class="text-gray-600 mb-6">A seção procurada não existe ou foi desativada.</p>
            <a href="' . BASE_URL . '/index.php" class="btn-editorial-primary">Voltar à Capa</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $categoria['nome'];
$page_desc = $categoria['descricao'] ?? 'Reportagens e coberturas de ' . $categoria['nome'];
require_once __DIR__ . '/includes/header.php';
$total_paginas = ceil($total_noticias / $por_pagina);
?>

<main class="container-farol py-8">
  
  <!-- CABEÇALHO DA SEÇÃO / EDITORIA -->
  <div class="editorial-card p-6 sm:p-8 mb-8 bg-white border-l-4 border-[#b91c1c]">
    <div class="flex items-center gap-2 mb-2">
      <span class="editorial-badge badge-crimson text-[9px]">CADERNO TEMÁTICO</span>
      <span class="text-xs text-gray-400 font-sans uppercase tracking-wider"><?= $total_noticias ?> publicações</span>
    </div>
    <h1 class="text-3xl sm:text-4xl md:text-5xl font-headline font-extrabold text-[#111827]">
      <?= e($categoria['nome']) ?>
    </h1>
    <?php if (!empty($categoria['descricao'])): ?>
      <p class="text-sm sm:text-base text-gray-600 font-sans mt-2 max-w-3xl leading-relaxed">
        <?= e($categoria['descricao']) ?>
      </p>
    <?php endif; ?>
  </div>

  <!-- GRADE DE NOTÍCIAS DA CATEGORIA -->
  <?php if (!empty($noticias)): ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach ($noticias as $item): ?>
        <article class="editorial-card flex flex-col justify-between overflow-hidden bg-white">
          <div>
            <?php if (!empty($item['imagem_capa'])): ?>
              <div class="img-zoom-wrapper border-b border-gray-200">
                <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($item['slug']) ?>">
                  <img src="<?= BASE_URL ?>/<?= e($item['imagem_capa']) ?>" 
                       alt="<?= e($item['titulo']) ?>" 
                       class="w-full h-48 object-cover">
                </a>
              </div>
            <?php else: ?>
              <div class="w-full h-36 bg-[#0f4c5c] flex items-center justify-center text-white text-xs font-headline font-bold p-4 text-center">
                <?= e($categoria['nome']) ?>
              </div>
            <?php endif; ?>

            <div class="p-5">
              <?php if (!empty($item['chapeu'])): ?>
                <span class="editorial-badge badge-teal text-[8px] mb-2"><?= e($item['chapeu']) ?></span>
              <?php endif; ?>

              <h2 class="text-lg font-headline font-bold text-[#111827] leading-snug my-2 hover:text-[#b91c1c] transition-colors">
                <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($item['slug']) ?>">
                  <?= e($item['titulo']) ?>
                </a>
              </h2>

              <p class="text-xs text-gray-600 font-sans leading-relaxed line-clamp-3 mb-4">
                <?= e($item['subtitulo']) ?>
              </p>
            </div>
          </div>

          <div class="px-5 py-3 bg-[#fbfbf9] border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 font-sans">
            <span><?= date('d/m/Y', strtotime($item['publicado_em'])) ?></span>
            <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($item['slug']) ?>" class="font-bold text-[#b91c1c] hover:underline flex items-center gap-1">
              Ler mais <span>→</span>
            </a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- PAGINAÇÃO MODERNA -->
    <?php if ($total_paginas > 1): ?>
      <div class="mt-10 flex justify-center items-center space-x-2">
        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
          <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($slug) ?>&p=<?= $i ?>" 
             class="w-9 h-9 flex items-center justify-center text-xs font-bold rounded <?= $i === $pagina ? 'bg-[#0f4c5c] text-white shadow-sm' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-300' ?>">
            <?= $i ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

  <?php else: ?>
    <div class="editorial-card p-12 text-center bg-white">
      <p class="text-gray-500 font-headline italic text-base">Ainda não há reportagens publicadas nesta categoria.</p>
      <a href="<?= BASE_URL ?>/index.php" class="btn-editorial-primary mt-4 inline-flex">
        Voltar para a Capa
      </a>
    </div>
  <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
