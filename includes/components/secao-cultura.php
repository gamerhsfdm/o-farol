<?php
/**
 * Componente: Cultura & Empreendedorismo Estudantil
 */
declare(strict_types=1);
if (empty($modulos_ativos['modulo_cultura'])) return;
?>
<section class="farol-card p-4">
  <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-200">
    <h3 class="text-sm font-headline font-bold text-[#111827] flex items-center gap-1.5">
      <span class="text-amber-600">🎨</span> Cultura &amp; Empreendedorismo Estudantil
    </h3>
    <span class="farol-tag-orange">PROGRAMAÇÃO DE JUNHO</span>
  </div>

  <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <?php if (!empty($cultura_noticias)): ?>
      <?php foreach ($cultura_noticias as $idx => $cult): ?>
        <div class="<?= $idx === 0 ? 'border-b sm:border-b-0 sm:border-r border-gray-200 sm:pr-3 pb-3 sm:pb-0' : 'sm:pl-1' ?>">
          <span class="text-[8px] font-bold text-[#0284c7] uppercase"><?= e($cult['categoria_nome'] ?? 'FEIRA DO EMPREENDEDORISMO') ?></span>
          <h4 class="text-xs font-headline font-bold text-[#111827] leading-snug my-1">
            <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($cult['slug']) ?>" class="hover:text-[#c0262d]">
              <?= e($cult['titulo']) ?>
            </a>
          </h4>
          <p class="text-[9.5px] text-gray-600 leading-relaxed mb-2">
            <?= e($cult['subtitulo']) ?>
          </p>
          <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($cult['slug']) ?>" class="text-[8px] font-bold text-[#c0262d] uppercase hover:underline">
            <?= $idx === 0 ? 'CONFIRA AS MARCAS PARTICIPANTES →' : 'CONFIRA OS HORÁRIOS DAS OFICINAS →' ?>
          </a>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <!-- Conteúdo Padrão Fiel -->
      <div class="border-b sm:border-b-0 sm:border-r border-gray-200 sm:pr-3 pb-3 sm:pb-0">
        <span class="text-[8px] font-bold text-[#0284c7] uppercase">FEIRA DO EMPREENDEDORISMO</span>
        <h4 class="text-xs font-headline font-bold text-[#111827] leading-snug my-1">
          <a href="<?= BASE_URL ?>/noticia.php?slug=cooperativa-jovem-prepara-estandes-gastronomicos-e-biojoias" class="hover:text-[#c0262d]">
            Cooperativa jovem prepara estandes gastronômicos e biojóias
          </a>
        </h4>
        <p class="text-[9.5px] text-gray-600 leading-relaxed mb-2">
          Mais de 18 planos de negócios desenvolvidos nas disciplinas de gestão técnica viram empresas reais com moeda social fictícia e faturamento auditado.
        </p>
        <a href="<?= BASE_URL ?>/noticia.php?slug=cooperativa-jovem-prepara-estandes-gastronomicos-e-biojoias" class="text-[8px] font-bold text-[#c0262d] uppercase hover:underline">
          CONFIRA AS MARCAS PARTICIPANTES →
        </a>
      </div>

      <div class="sm:pl-1">
        <span class="text-[8px] font-bold text-[#0284c7] uppercase">FEIRA CULTURAL SESC-SENAC</span>
        <h4 class="text-xs font-headline font-bold text-[#111827] leading-snug my-1">
          <a href="<?= BASE_URL ?>/noticia.php?slug=oficinas-de-cordel-teatro-lambe-lambe-e-orquestra-de-violoes" class="hover:text-[#c0262d]">
            Oficinas de cordel, teatro lambe-lambe e orquestra de violões
          </a>
        </h4>
        <p class="text-[9.5px] text-gray-600 leading-relaxed mb-2">
          O circuito cultural acolhe mais de 12 apresentações autorais de poesia e música compostas por estudantes no palco do Memorial Sesc.
        </p>
        <a href="<?= BASE_URL ?>/noticia.php?slug=oficinas-de-cordel-teatro-lambe-lambe-e-orquestra-de-violoes" class="text-[8px] font-bold text-[#c0262d] uppercase hover:underline">
          CONFIRA OS HORÁRIOS DAS OFICINAS →
        </a>
      </div>
    <?php endif; ?>
  </div>
</section>
