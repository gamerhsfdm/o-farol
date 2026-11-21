<?php
/**
 * Componente: faixa de convite para enviar pauta.
 * Usado no fim da capa e, nas demais páginas, antes do rodapé.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/database.php';
}
if (!isset($settings)) {
    require_once __DIR__ . '/../functions.php';
    $settings = get_site_settings();
}
?>
<section class="pitch" aria-labelledby="pitch-titulo">
  <div class="container-farol pitch__inner">
    <div>
      <h3 id="pitch-titulo"><?= e($settings['banner_chamada_titulo'] ?? 'Tem uma história, projeto científico ou evento na sua turma?') ?></h3>
      <p><?= e($settings['banner_chamada_texto'] ?? 'A redação de “O Farol” é feita por e para os estudantes. Envie sua sugestão de pauta, resenha de livro ou resultado esportivo para os nossos repórteres.') ?></p>
    </div>
    <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn btn--light">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
      <?= e($settings['banner_chamada_botao'] ?? 'Envie sua pauta') ?>
    </a>
  </div>
</section>
