<?php
/**
 * Componente: Processos Seletivos (Sidebar)
 * O FAROL - Jornal Escolar Sesc-Senac
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../includes/config.php';
}

if (!empty($processos)):
?>
<div class="editorial-card p-6 bg-white">
  <div class="flex items-center gap-2 pb-2 mb-4 border-b-2 border-[#111827]">
    <svg class="w-4 h-4 text-[#0f4c5c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">PROCESSOS SELETIVOS 2026/2027</h4>
  </div>

  <div class="space-y-4">
    <?php foreach ($processos as $proc): ?>
      <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
        <span class="editorial-badge badge-teal text-[8px]"><?= e($proc['categoria']) ?></span>
        <h5 class="text-xs font-bold text-gray-900 mt-1"><?= e($proc['titulo']) ?></h5>
        <p class="text-xs text-gray-600 leading-snug mt-1"><?= e($proc['descricao']) ?></p>
        <?php if (!empty($proc['data_limite'])): ?>
          <span class="text-[11px] font-bold text-[#b91c1c] block mt-1.5 flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            Prazo: <?= e($proc['data_limite']) ?>
          </span>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="mt-4 pt-3 border-t border-gray-100">
    <a href="#" class="btn-editorial-teal w-full text-center text-[11px]">
      Baixar Calendário Acadêmico em PDF
    </a>
  </div>
</div>
<?php endif; ?>
