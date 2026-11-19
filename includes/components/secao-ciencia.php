<?php
/**
 * Componente: Ciência & Prática Técnica
 */
declare(strict_types=1);
if (empty($modulos_ativos['modulo_ciencia'])) return;
?>
<?php if ($ciencia): ?>
  <section class="farol-card p-4">
    <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-200">
      <h3 class="text-sm font-headline font-bold text-[#111827] flex items-center gap-1.5">
        <span class="text-gray-700">⚗</span> Ciência &amp; Prática Técnica
      </h3>
      <span class="farol-tag-outline"><?= e($ciencia['chapeu'] ?: 'PROJETO INTEGRADOR') ?></span>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
      <div class="sm:col-span-5 border border-gray-200 p-0.5 bg-white">
        <img src="<?= BASE_URL ?>/<?= e($ciencia['imagem_capa'] ?: 'assets/images/ciencia-lab.jpg') ?>" 
             alt="<?= e($ciencia['titulo']) ?>" 
             class="w-full h-24 sm:h-28 object-cover">
        <div class="text-[7.5px] text-gray-500 p-1 bg-gray-50">
          <?= e($ciencia['legenda_capa'] ?: 'Oficina 04 - Laboratório de Energias Sustentáveis.') ?>
        </div>
      </div>
      
      <div class="sm:col-span-7 flex flex-col justify-between h-full">
        <div>
          <span class="text-[8px] font-bold text-[#0284c7] uppercase"><?= e($ciencia['categoria_nome'] ?: 'CLUBE DE CIÊNCIAS') ?></span>
          <h4 class="text-xs font-headline font-bold text-[#111827] leading-snug my-1">
            <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($ciencia['slug']) ?>" class="hover:text-[#c0262d]">
              <?= e($ciencia['titulo']) ?>
            </a>
          </h4>
          <p class="text-[9.5px] text-gray-600 leading-relaxed mb-2">
            <?= e($ciencia['subtitulo']) ?>
          </p>
        </div>
        <div class="flex items-center justify-between text-[8.5px] text-gray-500 pt-1 border-t border-gray-100">
          <span>Por: <?= !empty($ciencia['autores'][0]['nome']) ? e($ciencia['autores'][0]['nome'] . ' (' . ($ciencia['autores'][0]['turma'] ?? '3º B') . ')') : 'Aline Andrade (3º B)' ?></span>
          <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($ciencia['slug']) ?>" class="text-[#c0262d] font-bold hover:underline">Ler artigo →</a>
        </div>
      </div>
    </div>
  </section>
<?php endif; ?>
