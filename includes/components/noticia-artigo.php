<?php
/**
 * O FAROL — Componente de Artigo Editorial Reutilizável
 * Utilizado por noticia.php e pelo preview dinâmico em admin/preview.php
 * Espera $noticia (array) e $autores (array).
 */
declare(strict_types=1);

if (!isset($noticia)) {
    return;
}

$autores = $autores ?? [];
$foto_exibida = (string)($noticia['imagem_capa'] ?? '');
$is_preview = !empty($is_preview);
?>

<article class="editorial-card p-6 sm:p-10 bg-white border border-[#e6e3da] article-reading">
  
  <?php if ($is_preview): ?>
    <div style="background:#fef2f2;border:1px dashed #ef4444;color:#991b1b;padding:8px 14px;border-radius:4px;font-size:12px;font-weight:700;letter-spacing:0.04em;text-transform:uppercase;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between">
      <span>👁️ PRÉ-VISUALIZAÇÃO EDITORIAL — NÃO PUBLICADO</span>
      <span style="font-weight:500;text-transform:none">Atualização em tempo real</span>
    </div>
  <?php endif; ?>

  <!-- Cabeçalho Editorial da Notícia -->
  <header class="mb-8 pb-6 border-b border-gray-200">
    <div class="flex items-center gap-2 mb-3">
      <span class="editorial-badge badge-crimson"><?= strtoupper(e($noticia['categoria_nome'] ?? 'GERAL')) ?></span>
      <?php if (!empty($noticia['chapeu'])): ?>
        <span class="editorial-badge badge-teal"><?= e($noticia['chapeu']) ?></span>
      <?php endif; ?>
    </div>

    <h1 class="text-2xl sm:text-3xl md:text-4xl font-headline font-bold text-[#111827] leading-tight mb-4">
      <?= e($noticia['titulo'] ?? 'Título da reportagem') ?>
    </h1>

    <?php if (!empty($noticia['subtitulo'])): ?>
      <p class="text-sm sm:text-base text-gray-600 font-editorial-body leading-relaxed mb-6">
        <?= e($noticia['subtitulo']) ?>
      </p>
    <?php endif; ?>

    <!-- Assinatura Editorial e Metadados -->
    <div class="flex flex-wrap items-center justify-between text-xs text-gray-500 pt-4 border-t border-gray-100 gap-3">
      <div class="flex items-center gap-2">
        <span class="font-bold text-gray-900">
          Redação Discente: 
          <span class="font-normal text-gray-700">
            <?php 
              if (!empty($autores)) {
                echo e(implode(' e ', array_map(fn($a) => is_array($a) ? ($a['nome'] ?? '') : (string)$a, $autores)));
              } else {
                echo "Equipe O Farol";
              }
            ?>
          </span>
        </span>
        <span>•</span>
        <span><?= !empty($noticia['publicado_em']) ? date('d/m/Y \à\s H:i', strtotime((string)$noticia['publicado_em'])) : date('d/m/Y \à\s H:i') ?></span>
      </div>
      <div class="flex items-center gap-3">
        <span>Tempo de leitura: <strong><?= (int)($noticia['tempo_leitura'] ?? 3) ?> min</strong></span>
        <span>•</span>
        <span><?= (int)($noticia['visualizacoes'] ?? 0) ?> leituras</span>
      </div>
    </div>
  </header>

  <!-- Imagem de Capa em Alta Resolução -->
  <?php if ($foto_exibida !== ''): ?>
    <figure class="mb-8 rounded overflow-hidden border border-gray-200">
      <img src="<?= (str_starts_with($foto_exibida, 'http://') || str_starts_with($foto_exibida, 'https://')) ? e($foto_exibida) : BASE_URL . '/' . e(ltrim($foto_exibida, '/')) ?>" 
           alt="<?= e($noticia['titulo'] ?? '') ?>" 
           data-caption="<?= e($noticia['legenda_capa'] ?? '') ?>"
           class="w-full max-h-[460px] object-cover zoomable-image">
      <?php if (!empty($noticia['legenda_capa']) || !empty($noticia['credito_foto'])): ?>
        <figcaption class="p-3 bg-[#fafaf8] text-xs text-gray-600 font-sans border-t border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
          <span class="leading-relaxed"><?= e($noticia['legenda_capa'] ?? '') ?></span>
          <?php if (!empty($noticia['credito_foto'])): ?>
            <span class="text-[11px] text-gray-400 font-medium whitespace-nowrap"><?= e($noticia['credito_foto']) ?></span>
          <?php endif; ?>
        </figcaption>
      <?php endif; ?>
    </figure>
  <?php endif; ?>

  <!-- Pull Quote Editorial em Destaque -->
  <?php if (!empty($noticia['citacao_destaque'])): ?>
    <div class="farol-quote-box my-8">
      <p>“<?= e($noticia['citacao_destaque']) ?>”</p>
      <?php if (!empty($noticia['citacao_autor'])): ?>
        <span>— <?= e($noticia['citacao_autor']) ?></span>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Corpo da Matéria (Sanitizado) -->
  <div class="article-prose">
    <?= sanitize_html_content($noticia['conteudo'] ?? '') ?>
  </div>

  <!-- Orientação Pedagógica -->
  <?php if (!empty($noticia['professor_orientador'])): ?>
    <div class="mt-8 p-4 bg-[#f8fafc] border-l-4 border-[#0f4c5c] text-xs text-gray-700 rounded-r flex items-center justify-between">
      <div>
        <span class="font-bold text-[#0f4c5c] uppercase text-[10px] block">Orientação Pedagógica</span>
        <span class="text-xs"><?= e($noticia['professor_orientador']) ?></span>
      </div>
      <span class="text-gray-400 text-xs">Sesc • Senac</span>
    </div>
  <?php endif; ?>

  <!-- Ficha da Equipe Discente -->
  <?php if (!empty($autores)): ?>
    <div class="mt-10 pt-6 border-t-2 border-[#111827]">
      <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest mb-4">Equipe Responsável por Esta Cobertura</h4>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <?php foreach ($autores as $autor): 
          $nomeAut = is_array($autor) ? ($autor['nome'] ?? '') : (string)$autor;
          $cargoAut = is_array($autor) ? ($autor['cargo'] ?? 'Repórter Discente') : 'Repórter Discente';
          $turmaAut = is_array($autor) ? ($autor['turma'] ?? '') : '';
        ?>
          <div class="flex items-start gap-3 p-3.5 bg-[#fbfbf9] border border-gray-200 rounded">
            <div class="w-10 h-10 rounded-full bg-[#0f4c5c] text-white flex items-center justify-center font-bold text-sm font-headline flex-shrink-0">
              <?= strtoupper(substr($nomeAut, 0, 1)) ?>
            </div>
            <div class="text-xs">
              <div class="font-bold text-gray-900"><?= e($nomeAut) ?></div>
              <div class="text-gray-500"><?= e($cargoAut) ?></div>
              <?php if (!empty($turmaAut)): ?>
                <div class="text-[11px] text-gray-400 mt-0.5"><?= e($turmaAut) ?></div>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

</article>
