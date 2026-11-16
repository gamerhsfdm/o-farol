<?php
/**
 * O FAROL - Página Interna de Reportagem Completa (Design Editorial Refinado)
 * Estilo The New York Times / The Atlantic / Longform Journal
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$pdo = getDBConnection();

$noticia = null;
$autores = [];
$relacionadas = [];

if ($pdo && !empty($slug)) {
    try {
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug, c.cor as categoria_cor 
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.slug = ? AND (n.status = 'publicado' OR ?) LIMIT 1");
        $can_preview = is_logged_in() ? 1 : 0;
        $stmt->execute([$slug, $can_preview]);
        $noticia = $stmt->fetch();

        if ($noticia) {
            $stmtView = $pdo->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?");
            $stmtView->execute([$noticia['id']]);

            $stmtA = $pdo->prepare("SELECT a.* FROM autores a 
                                    JOIN noticia_autores na ON a.id = na.autor_id 
                                    WHERE na.noticia_id = ?");
            $stmtA->execute([$noticia['id']]);
            $autores = $stmtA->fetchAll();

            $stmtRel = $pdo->prepare("SELECT id, titulo, slug, imagem_capa, publicado_em 
                                      FROM noticias 
                                      WHERE categoria_id = ? AND id != ? AND status = 'publicado' 
                                      ORDER BY publicado_em DESC LIMIT 4");
            $stmtRel->execute([$noticia['categoria_id'], $noticia['id']]);
            $relacionadas = $stmtRel->fetchAll();
        }
    } catch (Exception $e) {}
}

if (!$noticia) {
    http_response_code(404);
    $page_title = "Reportagem Não Encontrada";
    require_once __DIR__ . '/includes/header.php';
    echo '<div class="container-farol py-20 text-center">
            <h2 class="text-3xl font-headline font-bold text-gray-800 mb-2">Reportagem não encontrada</h2>
            <p class="text-gray-600 mb-6">A matéria procurada não existe ou foi arquivada.</p>
            <a href="' . BASE_URL . '/index.php" class="btn-editorial-primary">Voltar à Capa</a>
          </div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $noticia['titulo'];
$page_desc = $noticia['subtitulo'];
require_once __DIR__ . '/includes/header.php';
?>

<main class="container-farol py-8">
  
  <!-- BREADCRUMB EDITORIAL -->
  <nav class="flex items-center gap-2 text-xs text-gray-500 font-sans mb-6 pb-2 border-b border-gray-200">
    <a href="<?= BASE_URL ?>/index.php" class="hover:text-black">Capa</a>
    <span>/</span>
    <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($noticia['categoria_slug']) ?>" class="hover:text-[#b91c1c] font-bold uppercase">
      <?= e($noticia['categoria_nome']) ?>
    </a>
    <span>/</span>
    <span class="text-gray-400 truncate max-w-sm"><?= e($noticia['titulo']) ?></span>
  </nav>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
    
    <!-- ARTIGO PRINCIPAL (8 Colunas) -->
    <article class="lg:col-span-8 editorial-card p-6 sm:p-10 bg-white">
      
      <!-- Cabeçalho do Artigo -->
      <header class="mb-8 pb-6 border-b border-gray-200">
        <div class="flex items-center gap-2 mb-4">
          <span class="editorial-badge badge-crimson"><?= strtoupper(e($noticia['categoria_nome'])) ?></span>
          <?php if (!empty($noticia['chapeu'])): ?>
            <span class="editorial-badge badge-teal"><?= e($noticia['chapeu']) ?></span>
          <?php endif; ?>
        </div>

        <h1 class="text-3xl sm:text-4xl md:text-5xl font-headline font-extrabold text-[#111827] leading-[1.14] mb-4">
          <?= e($noticia['titulo']) ?>
        </h1>

        <p class="text-base sm:text-lg text-gray-600 font-editorial-body leading-relaxed mb-6">
          <?= e($noticia['subtitulo']) ?>
        </p>

        <!-- Assinatura Editorial e Metadados -->
        <div class="flex flex-wrap items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-100 gap-3">
          <div class="flex items-center gap-2">
            <span class="font-bold text-gray-900">
              Por <?php 
                if (!empty($autores)) {
                  echo e(implode(' e ', array_map(fn($a) => $a['nome'], $autores)));
                } else {
                  echo "Redação O Farol";
                }
              ?>
            </span>
            <span>•</span>
            <span><?= date('d/m/Y \à\s H:i', strtotime($noticia['publicado_em'] ?? 'now')) ?></span>
          </div>
          <div class="flex items-center gap-3">
            <span>Tempo de leitura: <strong><?= e((string)$noticia['tempo_leitura']) ?> min</strong></span>
            <span>•</span>
            <span><?= e((string)$noticia['visualizacoes']) ?> leituras</span>
          </div>
        </div>
      </header>

      <!-- Imagem de Capa com Lightbox -->
      <?php if (!empty($noticia['imagem_capa'])): ?>
        <figure class="mb-8 rounded overflow-hidden border border-gray-200">
          <img src="<?= BASE_URL ?>/<?= e($noticia['imagem_capa']) ?>" 
               alt="<?= e($noticia['titulo']) ?>" 
               data-caption="<?= e($noticia['legenda_capa'] ?? '') ?>"
               class="w-full max-h-[520px] object-cover zoomable-image">
          <?php if (!empty($noticia['legenda_capa']) || !empty($noticia['credito_foto'])): ?>
            <figcaption class="p-3 bg-gray-50 text-xs text-gray-600 font-sans border-t border-gray-200">
              <span class="leading-relaxed"><?= e($noticia['legenda_capa']) ?></span>
              <?php if (!empty($noticia['credito_foto'])): ?>
                <span class="block text-right text-[11px] text-gray-400 mt-1 font-medium"><?= e($noticia['credito_foto']) ?></span>
              <?php endif; ?>
            </figcaption>
          <?php endif; ?>
        </figure>
      <?php endif; ?>

      <!-- Pull Quote em Destaque -->
      <?php if (!empty($noticia['citacao_destaque'])): ?>
        <blockquote class="modern-pullquote my-8">
          <p>“<?= e($noticia['citacao_destaque']) ?>”</p>
          <?php if (!empty($noticia['citacao_autor'])): ?>
            <cite class="block text-right text-xs font-sans not-italic font-bold text-gray-500 mt-2">
              — <?= e($noticia['citacao_autor']) ?>
            </cite>
          <?php endif; ?>
        </blockquote>
      <?php endif; ?>

      <!-- Corpo da Matéria (Tipografia Confortável para Leitura Longa) -->
      <div class="prose max-w-none text-gray-800 font-editorial-body text-lg leading-[1.75] space-y-6 text-justify">
        <?= $noticia['conteudo'] ?>
      </div>

      <!-- Orientação Pedagógica -->
      <?php if (!empty($noticia['professor_orientador'])): ?>
        <div class="mt-8 p-4 bg-[#f8fafc] border-l-4 border-[#0f4c5c] text-xs text-gray-700 rounded-r">
          <strong>Supervisão Pedagógica:</strong> <?= e($noticia['professor_orientador']) ?>
        </div>
      <?php endif; ?>

      <!-- Ficha dos Autores Discentes -->
      <?php if (!empty($autores)): ?>
        <div class="mt-10 pt-6 border-t-2 border-[#111827]">
          <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Equipe Responsável por Esta Cobertura</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php foreach ($autores as $autor): ?>
              <div class="flex items-start gap-3 p-4 bg-[#fbfbf9] border border-gray-200 rounded">
                <div class="w-12 h-12 rounded-full bg-[#0f4c5c] text-white flex items-center justify-center font-bold text-base font-headline flex-shrink-0">
                  <?= strtoupper(substr($autor['nome'], 0, 1)) ?>
                </div>
                <div class="text-xs">
                  <div class="font-bold text-gray-900 text-sm"><?= e($autor['nome']) ?></div>
                  <div class="text-gray-500"><?= e($autor['cargo'] ?? 'Redação Discente') ?></div>
                  <div class="text-[11px] text-gray-400 mt-0.5"><?= e($autor['turma'] ?? '') ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- Barra de Compartilhamento -->
      <div class="mt-8 pt-4 border-t border-gray-200 flex items-center justify-between">
        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Compartilhar Artigo:</span>
        <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link da reportagem copiado!');" 
                class="btn-editorial-teal text-xs py-1.5 px-3">
          Copiar Link da Matéria
        </button>
      </div>

    </article>

    <!-- SIDEBAR DE REPORTAGENS RELACIONADAS (4 Colunas) -->
    <aside class="lg:col-span-4 space-y-6">
      
      <!-- Mais da Mesma Seção -->
      <div class="editorial-card p-6 bg-white">
        <div class="flex items-center gap-2 pb-2 mb-4 border-b-2 border-[#111827]">
          <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
          <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">MAIS EM <?= e($noticia['categoria_nome']) ?></h4>
        </div>

        <?php if (!empty($relacionadas)): ?>
          <div class="space-y-4">
            <?php foreach ($relacionadas as $rel): ?>
              <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                <h5 class="text-sm font-headline font-bold text-gray-900 leading-snug hover:text-[#b91c1c] transition-colors">
                  <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($rel['slug']) ?>">
                    <?= e($rel['titulo']) ?>
                  </a>
                </h5>
                <span class="text-[11px] text-gray-400 block mt-1">
                  <?= date('d/m/Y', strtotime($rel['publicado_em'])) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-xs text-gray-500">Nenhuma outra reportagem cadastrada nesta editoria.</p>
        <?php endif; ?>
      </div>

      <!-- Convite Editorial -->
      <div class="editorial-card p-6 bg-[#0f4c5c] text-white">
        <span class="editorial-badge badge-crimson text-[9px] mb-2">CANAL ABERTO</span>
        <h4 class="text-lg font-headline font-bold text-white mb-2">Participe de O Farol</h4>
        <p class="text-xs text-teal-100 mb-4 leading-relaxed font-sans">
          Envie ideias de pautas, coberturas fotográficas ou artigos opinativos sobre sua turma.
        </p>
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn-editorial-primary w-full text-center">
          Enviar Pauta Estudantil
        </a>
      </div>

    </aside>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
