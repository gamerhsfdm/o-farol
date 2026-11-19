<?php
/**
 * Componente Sidebar: Clube do Livro (Obra do Mês)
 */
declare(strict_types=1);
if (empty($modulos_ativos['modulo_clube_livro'])) return;
?>
<div class="farol-card p-3.5">
  <div class="flex items-center gap-1 pb-1.5 mb-2 border-b border-gray-200">
    <span class="text-[#c0262d] text-xs">📖</span>
    <span class="text-[8.5px] font-bold text-gray-800 uppercase tracking-wider">CLUBE DO LIVRO • OBRA DO MÊS</span>
  </div>

  <?php if ($clube_livro): ?>
    <!-- Capa do Livro e Título -->
    <div class="bg-[#f0f9ff] border border-[#bae6fd] p-2.5 rounded-sm flex items-center gap-2.5 mb-2.5">
      <img src="<?= BASE_URL ?>/<?= e($clube_livro['capa_url'] ?: 'assets/images/livro-torto-arado.jpg') ?>" 
           alt="<?= e($clube_livro['titulo_livro']) ?>" 
           class="w-9 h-12 object-cover border border-gray-300 shadow-sm flex-shrink-0">
      <div>
        <h5 class="text-[11px] font-headline font-bold text-gray-900 leading-tight">“<?= e($clube_livro['titulo_livro']) ?>”</h5>
        <span class="text-[8.5px] text-gray-500 block"><?= e($clube_livro['autor_livro']) ?></span>
      </div>
    </div>

    <h5 class="text-[10px] font-headline font-bold text-gray-900 leading-snug mb-1">
      <?= e($clube_livro['chamada_editorial']) ?>
    </h5>

    <p class="text-[8.5px] text-gray-600 leading-relaxed mb-2.5 text-justify">
      <?= e($clube_livro['resumo']) ?>
    </p>

    <div class="border-t border-gray-100 pt-2 text-[8px] text-gray-500">
      <span class="text-gray-400 uppercase font-bold block">PRÓXIMA LEITURA SELECIONADA:</span>
      <div class="font-bold text-gray-800 mt-0.5"><?= e($clube_livro['proxima_leitura_titulo'] ?: '“Quarto de Despejo”') ?> — <?= e($clube_livro['proxima_leitura_autor'] ?: 'Carolina Maria de Jesus') ?></div>
      <div class="text-gray-400 mt-0.5"><?= e($clube_livro['info_inscricao'] ?: 'Inscrições abertas no balcão da biblioteca.') ?></div>
    </div>
  <?php endif; ?>
</div>
