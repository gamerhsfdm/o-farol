<?php
/**
 * O FAROL - Rodapé Institucional Modernizado
 * Design Editorial Contemporâneo (The Guardian / NYTimes)
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$settings = get_site_settings();
?>

  <!-- FAIXA DE CONVITE EDITORIAL MODERNA -->
  <section class="site-cta bg-[#0f4c5c] text-white py-8 mt-12 border-y border-[#0a2f38] shadow-inner">
    <div class="container-farol flex flex-col md:flex-row items-center justify-between gap-6">
      <div class="max-w-2xl text-center md:text-left">
        <span class="editorial-badge badge-crimson text-[9px] mb-2">PARTICIPAÇÃO ESTUDANTIL</span>
        <h3 class="text-xl sm:text-2xl font-headline font-bold text-white tracking-tight mb-1.5">
          <?= e($settings['banner_chamada_titulo']) ?>
        </h3>
        <p class="text-xs sm:text-sm text-teal-100 font-sans leading-relaxed">
          <?= e($settings['banner_chamada_texto']) ?>
        </p>
      </div>
      <div>
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn-editorial-primary py-3 px-6 text-xs shadow-md">
          <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
          <?= e($settings['banner_chamada_botao']) ?>
        </a>
      </div>
    </div>
  </section>

  <!-- RODAPÉ INSTITUCIONAL EDITORIAL -->
  <footer class="site-footer bg-[#111827] text-[#9ca3af] pt-12 pb-8 font-sans border-t border-[#1f2937]">
    <div class="container-farol">
      
      <!-- Topo do Rodapé: Logo e Identidade -->
      <div class="flex flex-col md:flex-row items-start md:items-center justify-between pb-8 mb-8 border-b border-[#1f2937] gap-4">
        <div>
          <h4 class="text-2xl font-headline font-extrabold text-white tracking-tight">
            <?= e($settings['site_nome']) ?>
          </h4>
          <p class="text-xs text-gray-400 mt-1 max-w-xl">
            <?= e($settings['site_descricao']) ?>
          </p>
        </div>

        <div class="flex items-center gap-2">
          <span class="text-xs text-gray-400 uppercase tracking-widest font-bold">EDIÇÃO DIGITAL Nº <?= e($settings['edicao_atual_numero']) ?></span>
          <span class="bg-[#b91c1c] text-white text-[10px] font-bold px-2 py-0.5 rounded">SESC • SENAC</span>
        </div>
      </div>

      <!-- 4 Colunas Institucionais da Referência -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8 text-xs mb-10">
        
        <!-- Coluna 1: Corpo Editorial Discente -->
        <div>
          <h5 class="font-bold text-white uppercase tracking-widest mb-3 border-b border-[#374151] pb-1.5 font-sans">
            Corpo Editorial Discente
          </h5>
          <div class="space-y-1.5 text-gray-400 leading-relaxed">
            <?php 
              $editorial = explode("\n", $settings['corpo_editorial_discente'] ?? '');
              foreach ($editorial as $linha):
                if (trim($linha)):
            ?>
              <div><?= e($linha) ?></div>
            <?php endif; endforeach; ?>
          </div>
        </div>

        <!-- Coluna 2: Orientação Pedagógica -->
        <div>
          <h5 class="font-bold text-white uppercase tracking-widest mb-3 border-b border-[#374151] pb-1.5 font-sans">
            Orientação Pedagógica
          </h5>
          <div class="space-y-1.5 text-gray-400 leading-relaxed">
            <?php 
              $orient = explode("\n", $settings['orientacao_pedagogica'] ?? '');
              foreach ($orient as $linha):
                if (trim($linha)):
            ?>
              <div><?= e($linha) ?></div>
            <?php endif; endforeach; ?>
          </div>
        </div>

        <!-- Coluna 3: Links Oficiais do Jornal -->
        <div>
          <h5 class="font-bold text-white uppercase tracking-widest mb-3 border-b border-[#374151] pb-1.5 font-sans">
            Links Oficiais do Jornal
          </h5>
          <ul class="space-y-1.5 text-gray-400">
            <?php 
              $links = explode("\n", $settings['links_oficiais'] ?? '');
              foreach ($links as $link_str):
                $parts = explode('|', $link_str);
                $label = trim($parts[0] ?? '');
                $url = trim($parts[1] ?? '#');
                if ($label):
            ?>
              <li><a href="<?= e($url) ?>" class="hover:text-white transition-colors underline decoration-gray-600"><?= e($label) ?></a></li>
            <?php endif; endforeach; ?>
          </ul>
        </div>

        <!-- Coluna 4: Sede da Redação e Contato -->
        <div>
          <h5 class="font-bold text-white uppercase tracking-widest mb-3 border-b border-[#374151] pb-1.5 font-sans">
            Sede da Redação
          </h5>
          <div class="space-y-1.5 text-gray-400 leading-relaxed">
            <?php 
              $sede = explode("\n", $settings['sede_redacao'] ?? '');
              foreach ($sede as $linha):
                if (trim($linha)):
            ?>
              <div><?= e($linha) ?></div>
            <?php endif; endforeach; ?>
          </div>
        </div>

      </div>

      <!-- Barra de Copyright e Políticas -->
      <div class="border-t border-[#1f2937] pt-6 flex flex-col md:flex-row justify-between items-center text-[11px] text-gray-500 gap-3">
        <div>
          <?= e($settings['direitos_autorais']) ?>
        </div>
        <div class="flex space-x-4">
          <a href="#" class="hover:text-white transition-colors">Termos de Uso</a>
          <a href="#" class="hover:text-white transition-colors">Privacidade Estudantil</a>
          <a href="#" class="hover:text-white transition-colors">Acessibilidade</a>
          <a href="<?= BASE_URL ?>/admin/index.php" class="text-gray-400 hover:text-white font-bold">Painel CMS</a>
        </div>
      </div>

    </div>
  </footer>

  <!-- BOTÃO VOLTAR AO TOPO -->
  <button id="btn-back-to-top" title="Voltar ao Topo" aria-label="Voltar ao topo">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
  </button>

  <!-- MODAL LIGHTBOX DE IMAGENS -->
  <div id="image-lightbox" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex flex-col items-center justify-center p-4">
    <button id="lightbox-close" class="absolute top-6 right-6 text-white text-3xl font-bold hover:text-gray-300 focus:outline-none">&times;</button>
    <div class="max-w-4xl max-h-[85vh] flex flex-col items-center">
      <img id="lightbox-img" src="" alt="" class="max-h-[75vh] w-auto rounded shadow-2xl object-contain">
      <p id="lightbox-caption" class="text-xs sm:text-sm text-gray-300 font-sans mt-3 text-center max-w-xl"></p>
    </div>
  </div>

  <!-- SCRIPT DE INTERAÇÕES MODERNAS -->
  <script src="<?= BASE_URL ?>/assets/js/portal.js"></script>

</body>
</html>
