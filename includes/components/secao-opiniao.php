<?php
/**
 * Componente: Coluna de Opinião e Flagrantes da Semana
 */
declare(strict_types=1);
$exibir_opiniao = !empty($modulos_ativos['modulo_opiniao']);
$exibir_flagrantes = !empty($modulos_ativos['modulo_flagrantes']);

if (!$exibir_opiniao && !$exibir_flagrantes) return;
?>
<div class="grid grid-cols-1 sm:grid-cols-12 gap-4">
  
  <!-- Coluna de Opinião (7 Colunas ou 12 se Flagrantes estiver oculto) -->
  <?php if ($exibir_opiniao): ?>
    <div class="<?= $exibir_flagrantes ? 'sm:col-span-7' : 'sm:col-span-12' ?> farol-card p-4">
      <div class="flex items-center gap-1.5 mb-1.5">
        <span class="farol-tag-red text-[7.5px]">COLUNA DE OPINIÃO</span>
        <span class="text-[8px] text-gray-400 uppercase">CIDADANIA E EDUCAÇÃO DISCENTE</span>
      </div>

      <?php if ($opiniao): ?>
        <h4 class="text-xs font-headline font-bold text-[#111827] leading-tight mb-2">
          <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($opiniao['slug']) ?>" class="hover:text-[#c0262d]">
            “<?= e($opiniao['titulo']) ?>”
          </a>
        </h4>

        <p class="text-[9.5px] text-gray-600 leading-relaxed mb-3 text-justify">
          <?= e($opiniao['subtitulo']) ?>
        </p>

        <div class="flex items-center gap-2 pt-2 border-t border-gray-100">
          <div class="w-6 h-6 rounded bg-red-100 text-[#c0262d] font-bold text-[9px] flex items-center justify-center">
            <?= strtoupper(substr($opiniao['autor']['nome'] ?? 'GC', 0, 2)) ?>
          </div>
          <div class="text-[8.5px]">
            <div class="font-bold text-gray-900"><?= e($opiniao['autor']['nome'] ?? 'Gabriel Castanho') ?></div>
            <div class="text-gray-400"><?= e($opiniao['autor']['cargo'] ?? 'Editor-Chefe Discente') ?> • <?= e($opiniao['autor']['turma'] ?? '3º ano do Ensino Médio') ?></div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- Flagrantes da Semana (5 Colunas ou 12 se Opinião estiver oculta) -->
  <?php if ($exibir_flagrantes): ?>
    <div class="<?= $exibir_opiniao ? 'sm:col-span-5' : 'sm:col-span-12' ?> farol-card p-4 flex flex-col justify-between">
      <div>
        <div class="flex items-center justify-between pb-1 mb-2 border-b border-gray-100">
          <span class="text-[9px] font-bold text-gray-800 uppercase flex items-center gap-1">
            📸 Flagrantes da Semana
          </span>
          <span class="text-[7.5px] text-[#0284c7] font-bold uppercase"><?= e($flagrante['campus_local'] ?? 'Campus em Foco') ?></span>
        </div>

        <div class="border border-gray-200 p-0.5 bg-white mb-1.5">
          <img src="<?= BASE_URL ?>/<?= e($flagrante['foto_url'] ?? 'assets/images/flagrante-semana.jpg') ?>" 
               alt="<?= e($flagrante['titulo'] ?? 'Intervalo cultural') ?>" 
               class="w-full h-24 object-cover">
        </div>

        <p class="text-[8.5px] text-gray-600 leading-tight">
          “<?= e($flagrante['legenda'] ?? 'Intervalo cultural espontâneo no pátio com alunos do 2º ano tocando violão e flauta antes da Feira Cultural.') ?>” (Foto: <?= e($flagrante['fotografo'] ?? 'Lucas Rios') ?>)
        </p>
      </div>
    </div>
  <?php endif; ?>

</div>
