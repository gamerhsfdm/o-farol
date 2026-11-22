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

$footer_categories = get_active_categories();
$js_version = (int)@filemtime(__DIR__ . '/../assets/js/portal.js');

/** Quebra um campo de várias linhas em linhas não vazias. */
$footer_lines = static fn(?string $text): array => array_values(array_filter(array_map('trim', explode("\n", (string)$text)), 'strlen'));
?>

  <?php if (!$home_has_pitch) require __DIR__ . '/components/banner-pauta.php'; ?>

  <footer class="footer">
    <div class="container-farol">

      <div class="footer__grid">
        <div class="footer__brand">
          <a class="footer__logo" href="<?= BASE_URL ?>/index.php" aria-label="<?= e($settings['site_nome']) ?> — página inicial">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
              <path d="M24 57L28.5 24H35.5L40 57H24Z" fill="#e3a1a8"/>
              <path d="M26.2 41H37.8L36.6 50H27.4L26.2 41Z" fill="#2b1014"/>
              <path d="M28 30.5H36L35.4 36H28.6L28 30.5Z" fill="#2b1014"/>
              <rect x="27" y="20" width="10" height="4" fill="#f4efe6"/>
              <rect x="29.5" y="12.5" width="5" height="7.5" fill="#2b1014" stroke="#f4efe6" stroke-width="1.6"/>
              <path d="M28 12.5L32 7.5L36 12.5H28Z" fill="#f4efe6"/>
              <path d="M25 15.5L8 11M25 17.5L5 18M39 15.5L56 11M39 17.5L59 18" stroke="#e3a1a8" stroke-width="1.8" stroke-linecap="round"/>
              <path d="M16 57H48" stroke="#f4efe6" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span><?= e($settings['site_nome']) ?></span>
          </a>
          <p class="footer__about"><?= e($settings['site_descricao']) ?></p>
          <p class="footer__edition">
            <strong>Edição digital nº <?= e($settings['edicao_atual_numero']) ?></strong>
            <span><?= e($settings['site_subtitulo']) ?></span>
          </p>
        </div>

        <nav class="footer__col" aria-label="Navegação do site">
          <h5>Navegação</h5>
          <ul>
            <li><a href="<?= BASE_URL ?>/index.php">Capa</a></li>
            <li><a href="<?= BASE_URL ?>/busca.php">Buscar no acervo</a></li>
            <li><a href="<?= BASE_URL ?>/enviar-pauta.php">Sugerir pauta</a></li>
          </ul>
        </nav>

        <?php if ($footer_categories): ?>
        <nav class="footer__col" aria-label="Editorias">
          <h5>Editorias</h5>
          <ul>
            <?php foreach ($footer_categories as $cat): ?>
              <li><a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($cat['slug']) ?>"><?= e(category_label($cat['nome'])) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </nav>
        <?php endif; ?>

        <?php $sede = $footer_lines($settings['sede_redacao'] ?? ''); if ($sede): ?>
        <div class="footer__col">
          <h5>Redação</h5>
          <address>
            <?php foreach ($sede as $linha): ?>
              <?php if (filter_var($linha, FILTER_VALIDATE_EMAIL)): ?>
                <div><a href="mailto:<?= e($linha) ?>"><?= e($linha) ?></a></div>
              <?php else: ?>
                <div><?= e($linha) ?></div>
              <?php endif; ?>
            <?php endforeach; ?>
          </address>
        </div>
        <?php endif; ?>

        <?php $equipe = $footer_lines($settings['corpo_editorial_discente'] ?? ''); $orient = $footer_lines($settings['orientacao_pedagogica'] ?? ''); if ($equipe || $orient): ?>
        <div class="footer__col">
          <?php if ($equipe): ?>
            <h5>Corpo editorial discente</h5>
            <div>
              <?php foreach ($equipe as $linha): ?><div><?= e($linha) ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($orient): ?>
            <h5 class="footer__subhead">Orientação pedagógica</h5>
            <div>
              <?php foreach ($orient as $linha): ?><div><?= e($linha) ?></div><?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>

      <?php
        // Links oficiais cadastrados no painel; sem endereço real, aparecem só como texto.
        $oficiais = [];
        foreach ($footer_lines($settings['links_oficiais'] ?? '') as $link_str) {
            $parts = explode('|', $link_str);
            $label = trim($parts[0] ?? '');
            $url = trim($parts[1] ?? '');
            if ($label !== '') $oficiais[] = [$label, ($url === '' || $url === '#') ? null : $url];
        }
      ?>
      <?php if ($oficiais): ?>
        <ul class="footer__official" aria-label="Links oficiais">
          <?php foreach ($oficiais as [$label, $url]): ?>
            <li><?php if ($url): ?><a href="<?= e($url) ?>"><?= e($label) ?></a><?php else: ?><span><?= e($label) ?></span><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <div class="footer__bottom">
        <p><?= e($settings['direitos_autorais']) ?></p>
        <nav aria-label="Rodapé">
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

  <script src="<?= BASE_URL ?>/assets/js/portal.js?v=<?= $js_version ?>"></script>

</body>
</html>
