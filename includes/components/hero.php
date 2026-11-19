<?php
/**
 * Componente: Manchete Principal (Hero)
 */
declare(strict_types=1);
if (empty($modulos_ativos['modulo_manchete'])) return;
?>
<?php if ($hero): ?>
  <section class="farol-card p-4 sm:p-5 mb-5">
    
    <!-- Metadados do Topo -->
    <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-100">
      <div class="flex items-center gap-2">
        <span class="farol-tag-red"><?= e($hero['chapeu'] ?: 'PREMIAÇÃO NACIONAL') ?></span>
        <span class="text-[9px] font-bold text-gray-500 uppercase tracking-wider"><?= e($hero['categoria_nome'] ?? 'FECCI & SBPC JOVEM') ?></span>
      </div>
      <div class="text-[9px] text-gray-500 font-sans">
        Tempo de leitura: <?= e((string)$hero['tempo_leitura']) ?> min • Atualizado às <?= date('H:i', strtotime($hero['publicado_em'] ?? 'now')) ?>
      </div>
    </div>

    <!-- Grade Texto (Esq) + Foto Exata da Imagem (Dir) -->
    <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-start">
      
      <!-- Texto da Manchete (7 Colunas) -->
      <div class="md:col-span-7 flex flex-col justify-between">
        <div>
          <h2 class="text-xl sm:text-2xl font-headline font-bold text-[#111827] leading-tight mb-2">
            <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($hero['slug']) ?>" class="hover:text-[#c0262d]">
              <?= e($hero['titulo']) ?>
            </a>
          </h2>

          <p class="text-[10.5px] text-[#444444] font-text leading-relaxed text-justify mb-2">
            <?= e($hero['subtitulo']) ?>
          </p>

          <!-- Bloco da Citação -->
          <?php if (!empty($hero['citacao_destaque'])): ?>
            <div class="farol-quote-box">
              <p>“<?= e($hero['citacao_destaque']) ?>”</p>
              <?php if (!empty($hero['citacao_autor'])): ?>
                <span>— <?= e($hero['citacao_autor']) ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Assinatura da Redação e Botão Ler Completa -->
        <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <div class="w-6 h-6 rounded-full bg-[#38bdf8] text-white flex items-center justify-center text-[9px] font-bold">
              01
            </div>
            <div class="text-[9.5px]">
              <div class="font-bold text-[#111827]">
                Redação Discente: 
                <span class="font-normal text-gray-600">
                  <?php 
                    if (!empty($hero['autores'])) {
                      $nomes = array_map(fn($a) => $a['nome'], $hero['autores']);
                      echo e(implode(' e ', $nomes));
                    } else {
                      echo "Enzo Oliveira e Marina Prado";
                    }
                  ?>
                </span>
              </div>
              <?php if (!empty($hero['professor_orientador'])): ?>
                <div class="text-[8.5px] text-gray-500">Orientador: <?= e($hero['professor_orientador']) ?></div>
              <?php endif; ?>
            </div>
          </div>

          <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($hero['slug']) ?>" class="text-[9px] font-bold text-[#c0262d] uppercase tracking-wider hover:underline flex items-center gap-1">
            LER REPORTAGEM COMPLETA →
          </a>
        </div>
      </div>

      <!-- Fotografia -->
      <div class="md:col-span-5">
        <div class="border border-[#e2ded5] bg-white p-1">
          <img src="<?= BASE_URL ?>/<?= e($hero['imagem_capa'] ?: 'assets/images/hero-premiacao.jpg') ?>" 
               alt="<?= e($hero['titulo']) ?>" 
               class="w-full h-auto object-cover">
          <?php if (!empty($hero['legenda_capa']) || !empty($hero['credito_foto'])): ?>
            <div class="p-1.5 bg-[#fbfbf9] text-[8.5px] text-gray-600 leading-tight border-t border-gray-100">
              <strong>Registro:</strong> <?= e($hero['legenda_capa'] ?: 'Alunos expositores no estande institucional.') ?>
              <?php if (!empty($hero['credito_foto'])): ?>
                <div class="text-[7.5px] text-gray-400 text-right mt-0.5"><?= e($hero['credito_foto']) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </section>
<?php endif; ?>
