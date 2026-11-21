<?php
/**
 * Home editorial.
 * Espera as variáveis preparadas por index.php: $hero, $ultimas_noticias, $mais_lidas,
 * $ciencia, $jogos_noticia, $jogos_tabela, $cultura_noticias, $opiniao, $flagrante, $home_settings.
 */
$latest_cards = array_slice($ultimas_noticias, 0, 3);
$hero_authors = $hero ? home_author_names($hero) : null;

// Módulos ligados/desligados em Admin > Layout da Homepage.
$module = static fn(string $key): bool => !empty($home_settings[$key]);

$news_url = static fn(array $item): string => BASE_URL . '/noticia.php?slug=' . urlencode((string)($item['slug'] ?? ''));

// Link "ver mais" da seção: página da editoria do item em destaque (ou nenhum).
// Aceita false/null: PDO::fetch() devolve false quando não há linha.
$section_url = static fn($item): ?string => (is_array($item) && !empty($item['categoria_slug']))
    ? BASE_URL . '/categoria.php?slug=' . urlencode((string)$item['categoria_slug'])
    : null;

// Foto de capa; sem foto, mostra um bloco com o nome da editoria.
// $alt vazio = imagem decorativa (o título ao lado já descreve o conteúdo).
$image = static function (array $item, string $alt = '', bool $priority = false): void {
    if (!empty($item['imagem_capa'])) {
        echo '<img class="ref-media-image" src="' . BASE_URL . '/' . e($item['imagem_capa']) . '" alt="' . e($alt) . '"'
            . ($priority ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"') . '>';
    } else {
        echo '<div class="ref-no-image"><span>' . e($item['categoria_nome'] ?? 'O Farol') . '</span></div>';
    }
};
?>
<main id="conteudo-principal" class="container-farol reference-home">

  <?php if ($module('modulo_manchete')): ?>
  <section class="ref-hero" aria-label="Manchete">
    <?php if ($hero): ?>
      <div class="ref-hero__copy">
        <div class="ref-kickers">
          <span class="ref-label"><?= e($hero['chapeu'] ?: 'Destaque') ?></span>
          <span><?= e($hero['categoria_nome']) ?></span>
        </div>
        <h1><a href="<?= $news_url($hero) ?>"><?= e($hero['titulo']) ?></a></h1>
        <?php if (!empty($hero['subtitulo'])): ?><p class="ref-hero__summary"><?= e($hero['subtitulo']) ?></p><?php endif; ?>
        <div class="ref-byline">
          <span class="ref-avatar" aria-hidden="true">OF</span>
          <span>
            <strong><?= $hero_authors ? e($hero_authors) : 'Redação discente' ?></strong>
            <?php if ($published = home_format_date($hero['publicado_em'] ?? null, 'd \d\e F \d\e Y')): ?><small><?= e($published) ?></small><?php endif; ?>
          </span>
          <?php if ((int)($hero['tempo_leitura'] ?? 0) > 0): ?><span class="ref-reading"><?= (int)$hero['tempo_leitura'] ?> min de leitura</span><?php endif; ?>
        </div>
        <a class="ref-more" href="<?= $news_url($hero) ?>">Ler reportagem completa</a>
      </div>
      <figure class="ref-hero__media">
        <?php $image($hero, (string)($hero['legenda_capa'] ?? ''), true); ?>
        <?php if (!empty($hero['legenda_capa']) || !empty($hero['credito_foto'])): ?>
          <figcaption><span><?= e($hero['legenda_capa'] ?? '') ?></span><span><?= e($hero['credito_foto'] ?? '') ?></span></figcaption>
        <?php endif; ?>
      </figure>
    <?php else: ?>
      <p class="ref-empty">A manchete será exibida quando houver uma publicação em destaque.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($module('modulo_destaques')): ?>
  <section class="ref-section ref-latest" aria-labelledby="latest-title">
    <div class="ref-section-heading"><div><span>Agora no Farol</span><h2 id="latest-title">Últimas notícias</h2></div></div>
    <?php if ($latest_cards): ?>
      <div class="ref-latest-grid">
        <?php foreach ($latest_cards as $article): ?>
          <article class="ref-latest-card">
            <?php $image($article); ?>
            <span class="ref-category"><?= e($article['categoria_nome']) ?></span>
            <h3><a href="<?= $news_url($article) ?>"><?= e($article['titulo']) ?></a></h3>
            <?php if (!empty($article['subtitulo'])): ?><p><?= e($article['subtitulo']) ?></p><?php endif; ?>
            <div class="ref-card-meta">
              <time datetime="<?= e(date('Y-m-d', strtotime((string)$article['publicado_em']) ?: 0)) ?>"><?= e(home_format_date($article['publicado_em'], 'd M Y') ?? '') ?></time>
              <?php if (!empty($article['autores_nomes'])): ?><span>Por <?= e($article['autores_nomes']) ?></span><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="ref-empty">Ainda não há notícias publicadas.</p>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($module('modulo_ciencia')): ?>
  <section id="ciencia" class="ref-feature" aria-labelledby="ciencia-title">
    <div class="ref-feature__story">
      <div class="ref-section-heading">
        <div><span>Ciência &amp; prática técnica</span><h2 id="ciencia-title">Ideias que saem do laboratório</h2></div>
        <?php if ($url = $section_url($ciencia)): ?><a href="<?= e($url) ?>">Ver editoria</a><?php endif; ?>
      </div>
      <?php if ($ciencia): ?>
        <article class="ref-overlay-card">
          <?php $image($ciencia); ?>
          <div>
            <span class="ref-category"><?= e($ciencia['categoria_nome']) ?></span>
            <h3><a href="<?= $news_url($ciencia) ?>"><?= e($ciencia['titulo']) ?></a></h3>
            <p><?= e($ciencia['subtitulo']) ?></p>
          </div>
        </article>
      <?php else: ?>
        <p class="ref-empty">Nenhuma pauta de ciência está em destaque.</p>
      <?php endif; ?>
    </div>

    <?php if ($mais_lidas): ?>
      <aside class="ref-ranking" aria-labelledby="mais-lidas-title">
        <h2 id="mais-lidas-title">Mais lidas</h2>
        <ol>
          <?php foreach (array_slice($mais_lidas, 0, 3) as $index => $item): ?>
            <li>
              <b aria-hidden="true"><?= $index + 1 ?></b>
              <div>
                <span class="ref-category"><?= e($item['categoria_nome']) ?></span>
                <a href="<?= $news_url($item) ?>"><?= e($item['titulo']) ?></a>
                <small><?= (int)$item['visualizacoes'] ?> leituras</small>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </aside>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($module('modulo_jogos') && ($jogos_noticia || $jogos_tabela)): ?>
  <section id="jogos" class="ref-section ref-games" aria-labelledby="jogos-title">
    <div class="ref-section-heading">
      <div><span>Esporte escolar</span><h2 id="jogos-title">Jogos Escolares Interclasses</h2></div>
      <?php if ($url = $section_url($jogos_noticia)): ?><a href="<?= e($url) ?>">Ver cobertura</a><?php endif; ?>
    </div>
    <div class="ref-games-grid">
      <?php if ($jogos_noticia): ?>
        <article class="ref-overlay-card ref-overlay-card--wide">
          <?php $image($jogos_noticia); ?>
          <div>
            <h3><a href="<?= $news_url($jogos_noticia) ?>"><?= e($jogos_noticia['titulo']) ?></a></h3>
            <p><?= e($jogos_noticia['subtitulo']) ?></p>
          </div>
        </article>
      <?php endif; ?>
      <?php if ($jogos_tabela): ?>
        <aside class="ref-results" aria-label="Quadro de resultados">
          <span>Quadro de resultados</span>
          <h3>Últimos placares</h3>
          <?php foreach ($jogos_tabela as $jogo): ?>
            <p><span><?= e($jogo['equipe_a']) ?></span><b><?= e((string)$jogo['placar_a']) ?> × <?= e((string)$jogo['placar_b']) ?></b><span><?= e($jogo['equipe_b']) ?></span></p>
          <?php endforeach; ?>
        </aside>
      <?php endif; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($module('modulo_cultura') && $cultura_noticias): ?>
  <section id="cultura" class="ref-section ref-culture" aria-labelledby="cultura-title">
    <div class="ref-section-heading">
      <div><span>Cultura &amp; empreendedorismo estudantil</span><h2 id="cultura-title">O campus em movimento</h2></div>
      <?php if ($url = $section_url($cultura_noticias[0] ?? null)): ?><a href="<?= e($url) ?>">Ver editoria</a><?php endif; ?>
    </div>
    <div class="ref-culture-grid">
      <?php foreach ($cultura_noticias as $article): ?>
        <article class="ref-overlay-card">
          <?php $image($article); ?>
          <div>
            <span class="ref-category"><?= e($article['categoria_nome']) ?></span>
            <h3><a href="<?= $news_url($article) ?>"><?= e($article['titulo']) ?></a></h3>
            <p><?= e($article['subtitulo']) ?></p>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($module('modulo_opiniao') && ($opiniao || $flagrante)): ?>
  <section id="opiniao" class="ref-section ref-opinion" aria-label="Opinião e flagrantes">
    <?php if ($opiniao): ?>
      <article>
        <span>Coluna de opinião</span>
        <h2><a href="<?= $news_url($opiniao) ?>">“<?= e($opiniao['titulo']) ?>”</a></h2>
        <p><?= e($opiniao['subtitulo']) ?></p>
        <div class="ref-byline">
          <span class="ref-avatar" aria-hidden="true"><?= e(strtoupper(substr($opiniao['autor']['nome'] ?? 'OF', 0, 2))) ?></span>
          <span>
            <strong><?= e($opiniao['autor']['nome'] ?? 'Redação discente') ?></strong>
            <?php if (!empty($opiniao['autor']['turma'])): ?><small><?= e($opiniao['autor']['turma']) ?></small><?php endif; ?>
          </span>
        </div>
      </article>
    <?php endif; ?>
    <?php if ($flagrante && !empty($flagrante['foto_url'])): ?>
      <figure>
        <img src="<?= BASE_URL ?>/<?= e($flagrante['foto_url']) ?>" alt="<?= e($flagrante['legenda'] ?? $flagrante['titulo'] ?? '') ?>" loading="lazy" decoding="async">
        <figcaption><span><?= e($flagrante['legenda'] ?? '') ?></span><span><?= e($flagrante['fotografo'] ?? '') ?></span></figcaption>
      </figure>
    <?php endif; ?>
  </section>
  <?php endif; ?>

</main>

<?php if ($module('modulo_banner_pauta')) require __DIR__ . '/banner-pauta.php'; ?>
