<?php
/**
 * O FAROL - Rodapé público
 */
declare(strict_types=1);
require_once __DIR__ . '/functions.php';

$settings = get_site_settings();

// Na capa a faixa de pauta já aparece antes do rodapé; não repetir.
$is_home = basename($_SERVER['PHP_SELF'] ?? '') === 'index.php';
$home_has_pitch = $is_home && !empty($settings['modulo_banner_pauta']);

/** Quebra um campo de várias linhas em linhas não vazias. */
$footer_lines = static fn(?string $text): array => array_values(array_filter(array_map('trim', explode("\n", (string)$text)), 'strlen'));
?>

  <?php if (!$home_has_pitch) require __DIR__ . '/components/banner-pauta.php'; ?>

  <footer class="footer">
    <div class="container-farol">

      <div class="footer__top">
        <div>
          <p class="footer__name"><?= e($settings['site_nome']) ?></p>
          <p><?= e($settings['site_descricao']) ?></p>
        </div>
        <div class="footer__edition">
          <span>Edição digital nº <?= e($settings['edicao_atual_numero']) ?></span>
          <span class="footer__tag">Sesc • Senac</span>
        </div>
      </div>

      <div class="footer__cols">
        <div>
          <h5>Corpo editorial discente</h5>
          <div>
            <?php foreach ($footer_lines($settings['corpo_editorial_discente'] ?? '') as $linha): ?>
              <div><?= e($linha) ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <h5>Orientação pedagógica</h5>
          <div>
            <?php foreach ($footer_lines($settings['orientacao_pedagogica'] ?? '') as $linha): ?>
              <div><?= e($linha) ?></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div>
          <h5>Links oficiais</h5>
          <ul>
            <?php foreach ($footer_lines($settings['links_oficiais'] ?? '') as $link_str):
              $parts = explode('|', $link_str);
              $label = trim($parts[0] ?? '');
              $url = trim($parts[1] ?? '#');
              if ($label === '') continue; ?>
              <li><a href="<?= e($url) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div>
          <h5>Sede da redação</h5>
          <div>
            <?php foreach ($footer_lines($settings['sede_redacao'] ?? '') as $linha): ?>
              <div><?= e($linha) ?></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="footer__bottom">
        <p><?= e($settings['direitos_autorais']) ?></p>
        <nav aria-label="Rodapé">
          <a href="#">Termos de uso</a>
          <a href="#">Privacidade estudantil</a>
          <a href="#">Acessibilidade</a>
          <a href="<?= BASE_URL ?>/admin/index.php">Painel CMS</a>
        </nav>
      </div>

    </div>
  </footer>

  <button type="button" id="btn-back-to-top" title="Voltar ao topo" aria-label="Voltar ao topo">
    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
  </button>

  <!-- Lightbox de imagens -->
  <div id="image-lightbox" class="hidden fixed inset-0 z-50 bg-black bg-opacity-90 flex flex-col items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Imagem ampliada">
    <button type="button" id="lightbox-close" class="absolute top-6 right-6 text-white text-3xl font-bold hover:text-gray-300 focus:outline-none" aria-label="Fechar">&times;</button>
    <div class="max-w-4xl max-h-[85vh] flex flex-col items-center">
      <img id="lightbox-img" src="" alt="" class="max-h-[75vh] w-auto rounded shadow-2xl object-contain">
      <p id="lightbox-caption" class="text-xs sm:text-sm text-gray-300 font-sans mt-3 text-center max-w-xl"></p>
    </div>
  </div>

  <script src="<?= BASE_URL ?>/assets/js/portal.js"></script>

</body>
</html>
