<?php
/**
 * Componente: Enquete - Voz do Aluno (Sidebar Reutilizável)
 * O FAROL - Jornal Escolar Sesc-Senac
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../includes/config.php';
}

if (!empty($enquete)):
  $isMultipla = (int)($enquete['permitir_multipla'] ?? 0) === 1;
  $maxEscolhas = (int)($enquete['max_escolhas'] ?? 1);
  $catTag = $enquete['categoria_tag'] ?? 'Opinião Discente';
?>
<div class="editorial-card p-6 bg-white">
  <div class="flex items-center justify-between pb-2 mb-3 border-b-2 border-[#111827]">
    <div class="flex items-center gap-2">
      <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
      </svg>
      <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">VOZ DO ALUNO</h4>
    </div>
    <span class="editorial-badge badge-teal text-[8px]"><?= e($catTag) ?></span>
  </div>

  <h5 class="text-sm font-headline font-bold text-[#111827] leading-snug mb-2">
    <?= e($enquete['pergunta']) ?>
  </h5>

  <?php if (!empty($enquete['descricao'])): ?>
    <p class="text-xs text-gray-500 font-sans mb-3"><?= e($enquete['descricao']) ?></p>
  <?php endif; ?>

  <?php if ($isMultipla): ?>
    <span class="text-[10px] text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded font-medium inline-block mb-3">
      Múltipla escolha: selecione até <?= $maxEscolhas ?> opções
    </span>
  <?php endif; ?>

  <form id="form-enquete" action="<?= BASE_URL ?>/api/votar-enquete.php" method="POST" data-max-escolhas="<?= $isMultipla ? $maxEscolhas : 1 ?>">
    <input type="hidden" name="enquete_id" value="<?= e((string)$enquete['id']) ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    
    <div class="space-y-2 mb-4" id="enquete-opcoes-container">
      <?php foreach ($enquete_opcoes as $idx => $op): ?>
        <label class="flex items-start gap-2.5 text-xs text-gray-700 cursor-pointer p-2.5 rounded hover:bg-gray-50 border border-gray-200 transition-colors">
          <?php if ($isMultipla): ?>
            <input type="checkbox" name="opcoes_ids[]" value="<?= e((string)$op['id']) ?>" class="mt-0.5 text-[#0f4c5c] focus:ring-[#0f4c5c] rounded">
          <?php else: ?>
            <input type="radio" name="opcao_id" value="<?= e((string)$op['id']) ?>" <?= $idx === 0 ? 'checked' : '' ?> class="mt-0.5 text-[#0f4c5c] focus:ring-[#0f4c5c]">
          <?php endif; ?>
          <span class="font-medium"><?= e($op['opcao']) ?></span>
        </label>
      <?php endforeach; ?>
    </div>

    <button type="submit" id="btn-votar" class="w-full btn-editorial-primary text-xs py-2.5">
      Registrar Meu Voto
    </button>
    <div id="enquete-feedback" class="mt-2 text-center text-xs hidden"></div>
  </form>
</div>
<?php endif; ?>
