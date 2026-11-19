<?php
/**
 * Componente: Jogos Escolares Interclasses
 */
declare(strict_types=1);
if (empty($modulos_ativos['modulo_jogos'])) return;
?>
<section class="farol-card p-4">
  <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-200">
    <h3 class="text-sm font-headline font-bold text-[#111827] flex items-center gap-1.5">
      <span class="text-[#c0262d]">⚽</span> Jogos Escolares Interclasses
    </h3>
    <span class="farol-tag-red text-[8px]"><?= e($jogos_noticia['chapeu'] ?? 'FASE SEMIFINAL') ?></span>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-start">
    <div class="sm:col-span-7">
      <?php if ($jogos_noticia): ?>
        <h4 class="text-xs font-headline font-bold text-[#111827] leading-snug mb-1">
          <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($jogos_noticia['slug']) ?>" class="hover:text-[#c0262d]">
            <?= e($jogos_noticia['titulo']) ?>
          </a>
        </h4>
        <p class="text-[9.5px] text-gray-600 leading-relaxed mb-2">
          <?= e($jogos_noticia['subtitulo']) ?>
        </p>
        <div class="text-[8px] text-gray-400">
          Reportagem de quadra: <?= !empty($jogos_noticia['autores']) ? e(implode(' e ', array_map(fn($a)=>$a['nome'], $jogos_noticia['autores']))) : 'Mateus Guedes e Sofia Alencar' ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Tabela de Resultados -->
    <div class="sm:col-span-5 tabela-jogos-box">
      <div class="text-[8.5px] font-bold text-[#c0262d] uppercase text-center mb-1 pb-1 border-b border-gray-200">
        Quadro de Resultados
      </div>
      <table class="w-full">
        <tbody>
          <?php foreach ($jogos_tabela as $jg): ?>
            <tr class="border-b border-gray-100 last:border-0">
              <td class="font-medium text-gray-800"><?= e($jg['equipe_a']) ?></td>
              <td class="text-center font-bold font-mono bg-white px-1 border border-gray-200 text-[#14505c]">
                <?= e((string)$jg['placar_a']) ?> × <?= e((string)$jg['placar_b']) ?>
              </td>
              <td class="text-right font-medium text-gray-800"><?= e($jg['equipe_b']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="text-center mt-1.5 pt-1 border-t border-gray-200">
        <a href="<?= BASE_URL ?>/categoria.php?slug=jogos-escolares" class="text-[8px] font-bold text-[#0284c7] hover:underline uppercase">
          Ver chaveamento completo do torneio
        </a>
      </div>
    </div>
  </div>
</section>
