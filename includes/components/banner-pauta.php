<?php
/**
 * Componente: Banner de Chamada / Pauta Estudantil
 * O FAROL - Jornal Escolar Sesc-Senac
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../includes/config.php';
}
if (!isset($settings)) {
    $settings = get_site_settings();
}
?>
<section class="bg-[#0f4c5c] text-white py-8 mt-12 border-y border-[#0a2f38] shadow-inner">
  <div class="container-farol flex flex-col md:flex-row items-center justify-between gap-6">
    <div class="max-w-2xl text-center md:text-left">
      <span class="editorial-badge badge-crimson text-[9px] mb-2">PARTICIPAÇÃO ESTUDANTIL</span>
      <h3 class="text-xl sm:text-2xl font-headline font-bold text-white tracking-tight mb-1.5">
        <?= e($settings['banner_chamada_titulo'] ?? 'Tem uma história, projeto científico ou evento na sua turma?') ?>
      </h3>
      <p class="text-xs sm:text-sm text-teal-100 font-sans leading-relaxed">
        <?= e($settings['banner_chamada_texto'] ?? 'A redação de “O Farol” é feita por e para os estudantes. Envie sua sugestão de pauta, resenha de livro ou resultado esportivo para os nossos repórteres.') ?>
      </p>
    </div>
    <div>
      <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn-editorial-primary py-3 px-6 text-xs shadow-md">
        <svg class="w-4 h-4 mr-1.5 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        <?= e($settings['banner_chamada_botao'] ?? 'Envie sua Pauta / Sugestão') ?>
      </a>
    </div>
  </div>
</section>
