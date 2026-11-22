<?php
/**
 * Capa editorial do O FAROL.
 * Espera de index.php: $home (ver includes/home-data.php) e $home_settings.
 * Seções sem conteúdo real simplesmente não aparecem; só a capa vazia mostra um aviso.
 */
$hero        = $home['hero'] ?? null;
$secondary   = $home['secondary'] ?? [];
$latest      = $home['latest'] ?? [];
$most_read   = $home['most_read'] ?? [];
$sections    = $home['sections'] ?? [];
$opiniao     = $home['opiniao'] ?? null;
$flagrante   = $home['flagrante'] ?? null;
$clube_livro = $home['clube_livro'] ?? null;
$processos   = $home['processos'] ?? [];
$enquete     = $home['enquete'] ?? null;
$enquete_opcoes = $home['enquete_opcoes'] ?? [];
$jogos_tabela   = $home['jogos_tabela'] ?? [];
$edicao      = $home['edicao'] ?? null;

$news_url = static fn(array $item): string => BASE_URL . '/noticia.php?slug=' . urlencode((string)($item['slug'] ?? ''));
$cat_url  = static fn(array $item): string => BASE_URL . '/categoria.php?slug=' . urlencode((string)($item['categoria_slug'] ?? ''));
$date     = static fn(?string $value, string $format = 'd M Y'): string => (string)(format_date_ptbr($value, $format) ?? '');
$iso      = static fn(?string $value): string => ($value && strtotime($value) !== false) ? date('Y-m-d\TH:i', strtotime($value)) : '';
$credit   = static fn(string $text): string => stripos($text, 'foto') === 0 ? $text : 'Foto: ' . $text;

/** Etiqueta da editoria. O link fica acima do link esticado do cartão. */
$tag = static function (array $item) use ($cat_url): void {
    if (empty($item['categoria_nome'])) return;
    $label = category_label($item['categoria_nome']);
    if (!empty($item['categoria_slug'])) {
        echo '<a class="tag" href="' . e($cat_url($item)) . '">' . e($label) . '</a>';
    } else {
        echo '<span class="tag">' . e($label) . '</span>';
    }
};

/** Foto de capa na proporção pedida; sem foto, bloco tipográfico com o nome da editoria. */
$image = static function (array $item, string $ratio = 'r32', string $alt = '', bool $priority = false): void {
    echo '<div class="m m--' . e($ratio) . '">';
    if (!empty($item['imagem_capa'])) {
        echo '<img class="m__img" src="' . BASE_URL . '/' . e((string)$item['imagem_capa']) . '" alt="' . e($alt) . '"'
            . ($priority ? ' fetchpriority="high"' : ' loading="lazy" decoding="async"') . '>';
    } else {
        echo '<span class="m__none">' . e(category_label($item['categoria_nome'] ?? 'O Farol')) . '</span>';
    }
    echo '</div>';
};

/** Data (e hora, quando pedida) de publicação de um item. */
$when = static function (array $item, bool $with_time = false) use ($date, $iso): void {
    $published = $item['publicado_em'] ?? null;
    if ($date($published) === '') return;
    echo '<time datetime="' . e($iso($published)) . '">' . e($date($published)) . '</time>';
    if ($with_time) echo '<span>' . e($date($published, 'H:i')) . '</span>';
};

$hero_authors = $hero && !empty($hero['autores_nomes']) ? (string)$hero['autores_nomes'] : null;

// Quadros da faixa "Do campus": só entram os que têm conteúdo.
$campus_blocks = [];
if ($clube_livro)  $campus_blocks[] = 'livro';
if ($jogos_tabela) $campus_blocks[] = 'jogos';
if ($processos)    $campus_blocks[] = 'processos';
if ($edicao && (!empty($edicao['capa_url']) || !empty($edicao['pdf_url']))) $campus_blocks[] = 'edicao';

$has_main_columns = $latest || $most_read || $enquete;
?>
<main id="conteudo-principal" class="container-farol home">

  <?php if (empty($home['has_news'])): ?>
    <section class="home-empty" aria-label="Capa sem publicações">
      <h1>A capa será montada com as primeiras publicações.</h1>
      <p>Assim que uma matéria for publicada no painel, ela aparece aqui.</p>
    </section>
  <?php endif; ?>

  <?php if ($hero): ?>
  <!-- Manchete -->
  <section class="home-hero" aria-label="Manchete">
    <div class="home-hero__copy">
      <p class="kicker">
        <?php $tag($hero); ?>
        <?php if (!empty($hero['chapeu'])): ?><span class="kicker__hat"><?= e($hero['chapeu']) ?></span><?php endif; ?>
      </p>
      <h1><a href="<?= e($news_url($hero)) ?>"><?= e($hero['titulo']) ?></a></h1>
      <?php if (!empty($hero['subtitulo'])): ?><p class="home-hero__summary"><?= e($hero['subtitulo']) ?></p><?php endif; ?>
      <p class="byline">
        <?php if ($hero_authors): ?><span class="byline__who">Por <strong><?= e($hero_authors) ?></strong></span><?php endif; ?>
        <?php if ($date($hero['publicado_em'] ?? null) !== ''): ?><time datetime="<?= e($iso($hero['publicado_em'])) ?>"><?= e($date($hero['publicado_em'], 'j \d\e F \d\e Y')) ?></time><?php endif; ?>
        <?php if ((int)($hero['tempo_leitura'] ?? 0) > 0): ?><span><?= (int)$hero['tempo_leitura'] ?> min de leitura</span><?php endif; ?>
      </p>
      <a class="btn btn--primary home-hero__cta" href="<?= e($news_url($hero)) ?>">Ler a reportagem</a>
    </div>
    <figure class="home-hero__media">
      <?php $image($hero, 'hero', (string)($hero['legenda_capa'] ?? ''), true); ?>
      <?php if (!empty($hero['legenda_capa']) || !empty($hero['credito_foto'])): ?>
        <figcaption>
          <span><?= e((string)($hero['legenda_capa'] ?? '')) ?></span>
          <?php if (!empty($hero['credito_foto'])): ?><span class="credit"><?= e($credit((string)$hero['credito_foto'])) ?></span><?php endif; ?>
        </figcaption>
      <?php endif; ?>
    </figure>
  </section>
  <?php endif; ?>

  <?php if ($secondary): ?>
  <!-- Notícias secundárias -->
  <section class="home-top" aria-label="Outros destaques">
    <?php foreach ($secondary as $i => $article): ?>
      <article class="card top-card<?= $i === 0 ? ' top-card--lead' : '' ?>">
        <?php $image($article, 'r32'); ?>
        <p class="card__tag"><?php $tag($article); ?></p>
        <h2><a href="<?= e($news_url($article)) ?>"><?= e($article['titulo']) ?></a></h2>
        <?php if (!empty($article['subtitulo'])): ?><p class="card__summary"><?= e($article['subtitulo']) ?></p><?php endif; ?>
        <p class="meta"><?php $when($article); ?><?php if (!empty($article['autores_nomes'])): ?><span>Por <?= e($article['autores_nomes']) ?></span><?php endif; ?></p>
      </article>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($has_main_columns): ?>
  <!-- Últimas notícias + lateral -->
  <div class="home-cols<?= $latest ? '' : ' home-cols--single' ?>">
    <?php if ($latest): ?>
    <section class="home-latest" aria-labelledby="latest-title">
      <div class="sec-head"><h2 id="latest-title">Últimas notícias</h2></div>
      <ol class="feed">
        <?php foreach ($latest as $article): ?>
          <li class="card feed__item">
            <div class="feed__text">
              <p class="card__tag"><?php $tag($article); ?></p>
              <h3><a href="<?= e($news_url($article)) ?>"><?= e($article['titulo']) ?></a></h3>
              <p class="meta"><?php $when($article, true); ?></p>
            </div>
            <?php $image($article, 'thumb'); ?>
          </li>
        <?php endforeach; ?>
      </ol>
    </section>
    <?php endif; ?>

    <?php if ($most_read || $enquete): ?>
    <aside class="home-aside" aria-label="Leitura e participação">
      <?php if ($most_read): ?>
      <section class="rank" aria-labelledby="mais-lidas-title">
        <div class="sec-head"><h2 id="mais-lidas-title">Mais lidas</h2></div>
        <ol>
          <?php foreach ($most_read as $index => $item): ?>
            <li class="card">
              <b class="rank__n" aria-hidden="true"><?= $index + 1 ?></b>
              <div>
                <p class="card__tag"><?php $tag($item); ?></p>
                <h3><a href="<?= e($news_url($item)) ?>"><?= e($item['titulo']) ?></a></h3>
                <p class="meta"><?php $when($item); ?><span><?= (int)$item['visualizacoes'] ?> <?= (int)$item['visualizacoes'] === 1 ? 'leitura' : 'leituras' ?></span></p>
              </div>
            </li>
          <?php endforeach; ?>
        </ol>
      </section>
      <?php endif; ?>

      <?php if ($enquete):
        $multipla = (int)($enquete['permitir_multipla'] ?? 0) === 1;
        $max_escolhas = max(1, (int)($enquete['max_escolhas'] ?? 1));
      ?>
      <section class="poll" aria-labelledby="poll-title">
        <p class="poll__kicker">Voz do aluno</p>
        <h2 id="poll-title"><?= e($enquete['pergunta']) ?></h2>
        <?php if (!empty($enquete['descricao'])): ?><p class="poll__desc"><?= e($enquete['descricao']) ?></p><?php endif; ?>
        <form id="form-enquete" action="<?= BASE_URL ?>/api/votar-enquete.php" method="POST" data-max-escolhas="<?= $multipla ? $max_escolhas : 1 ?>">
          <input type="hidden" name="enquete_id" value="<?= (int)$enquete['id'] ?>">
          <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
          <fieldset id="enquete-opcoes-container">
            <legend class="visually-hidden"><?= $multipla ? 'Escolha até ' . $max_escolhas . ' opções' : 'Escolha uma opção' ?></legend>
            <?php if ($multipla): ?><p class="poll__hint">Escolha até <?= $max_escolhas ?> opções.</p><?php endif; ?>
            <?php foreach ($enquete_opcoes as $op): ?>
              <label class="poll__option">
                <?php if ($multipla): ?>
                  <input type="checkbox" name="opcoes_ids[]" value="<?= (int)$op['id'] ?>">
                <?php else: ?>
                  <input type="radio" name="opcao_id" value="<?= (int)$op['id'] ?>" required>
                <?php endif; ?>
                <span><?= e($op['opcao']) ?></span>
              </label>
            <?php endforeach; ?>
          </fieldset>
          <button type="submit" id="btn-votar" class="btn btn--primary">Registrar meu voto</button>
          <p id="enquete-feedback" class="poll__feedback" role="status" aria-live="polite" hidden></p>
        </form>
      </section>
      <?php endif; ?>
    </aside>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php foreach ($sections as $s_index => $section):
    $items = $section['items'];
    $lead  = $items[0];
    $rest  = array_slice($items, 1);
    $variant = ['a', 'b', 'c'][$s_index % 3];
  ?>
  <!-- Editoria: <?= e($section['nome']) ?> -->
  <section class="cat cat--<?= $variant ?>" aria-labelledby="cat-<?= (int)$section['id'] ?>-title">
    <div class="cat__head">
      <div>
        <h2 id="cat-<?= (int)$section['id'] ?>-title"><?= e(category_label($section['nome'])) ?></h2>
        <?php if ($section['descricao'] !== ''): ?><p><?= e($section['descricao']) ?></p><?php endif; ?>
      </div>
      <a class="cat__all" href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($section['slug']) ?>">
        Todas as publicações<span><?= (int)$section['total'] ?></span>
      </a>
    </div>

    <?php if ($variant === 'a'): ?>
      <div class="cat__body">
        <article class="card cat-lead">
          <?php $image($lead, 'r32'); ?>
          <h3><a href="<?= e($news_url($lead)) ?>"><?= e($lead['titulo']) ?></a></h3>
          <?php if (!empty($lead['subtitulo'])): ?><p class="card__summary"><?= e($lead['subtitulo']) ?></p><?php endif; ?>
          <p class="meta"><?php $when($lead); ?><?php if (!empty($lead['autores_nomes'])): ?><span>Por <?= e($lead['autores_nomes']) ?></span><?php endif; ?></p>
        </article>
        <?php if ($rest): ?>
        <div class="cat-rail">
          <?php foreach (array_slice($rest, 0, 3) as $article): ?>
            <article class="card rail-item">
              <div>
                <h3><a href="<?= e($news_url($article)) ?>"><?= e($article['titulo']) ?></a></h3>
                <p class="meta"><?php $when($article); ?></p>
              </div>
              <?php $image($article, 'thumb'); ?>
            </article>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    <?php elseif ($variant === 'b'): ?>
      <div class="cat__body">
        <article class="card cat-lead">
          <?php $image($lead, 'r32'); ?>
          <h3><a href="<?= e($news_url($lead)) ?>"><?= e($lead['titulo']) ?></a></h3>
          <?php if (!empty($lead['subtitulo'])): ?><p class="card__summary"><?= e($lead['subtitulo']) ?></p><?php endif; ?>
          <p class="meta"><?php $when($lead); ?><?php if (!empty($lead['autores_nomes'])): ?><span>Por <?= e($lead['autores_nomes']) ?></span><?php endif; ?></p>
        </article>
        <?php foreach (array_slice($rest, 0, 2) as $article): ?>
          <article class="card cat-card">
            <?php $image($article, 'r32'); ?>
            <h3><a href="<?= e($news_url($article)) ?>"><?= e($article['titulo']) ?></a></h3>
            <p class="meta"><?php $when($article); ?></p>
          </article>
        <?php endforeach; ?>
        <?php foreach (array_slice($rest, 2, 2) as $article): ?>
          <article class="card cat-brief">
            <h3><a href="<?= e($news_url($article)) ?>"><?= e($article['titulo']) ?></a></h3>
            <p class="meta"><?php $when($article); ?></p>
          </article>
        <?php endforeach; ?>
      </div>

    <?php else: ?>
      <article class="card cat-wide">
        <div class="cat-wide__text">
          <h3><a href="<?= e($news_url($lead)) ?>"><?= e($lead['titulo']) ?></a></h3>
          <?php if (!empty($lead['subtitulo'])): ?><p class="card__summary"><?= e($lead['subtitulo']) ?></p><?php endif; ?>
          <p class="meta"><?php $when($lead); ?><?php if (!empty($lead['autores_nomes'])): ?><span>Por <?= e($lead['autores_nomes']) ?></span><?php endif; ?></p>
        </div>
        <?php $image($lead, 'r32'); ?>
      </article>
      <?php if ($rest): ?>
      <div class="cat-briefs">
        <?php foreach (array_slice($rest, 0, 3) as $article): ?>
          <article class="card cat-brief">
            <h3><a href="<?= e($news_url($article)) ?>"><?= e($article['titulo']) ?></a></h3>
            <?php if (!empty($article['subtitulo'])): ?><p class="card__summary"><?= e($article['subtitulo']) ?></p><?php endif; ?>
            <p class="meta"><?php $when($article); ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </section>
  <?php endforeach; ?>

  <?php if ($opiniao || ($flagrante && !empty($flagrante['foto_url']))): ?>
  <!-- Opinião e flagrante -->
  <section class="opinion" aria-label="Opinião e flagrantes">
    <?php if ($opiniao): ?>
      <article class="card opinion__text">
        <p class="opinion__kicker">Coluna de opinião</p>
        <h2><a href="<?= e($news_url($opiniao)) ?>">“<?= e($opiniao['titulo']) ?>”</a></h2>
        <?php if (!empty($opiniao['subtitulo'])): ?><p class="card__summary"><?= e($opiniao['subtitulo']) ?></p><?php endif; ?>
        <div class="opinion__author">
          <?php if (!empty($opiniao['autor']['foto'])): ?>
            <img src="<?= BASE_URL ?>/<?= e((string)$opiniao['autor']['foto']) ?>" alt="" loading="lazy" decoding="async">
          <?php else: ?>
            <span class="opinion__initials" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string)($opiniao['autor']['nome'] ?? 'OF'), 0, 2, 'UTF-8'), 'UTF-8')) ?></span>
          <?php endif; ?>
          <span>
            <strong><?= e((string)($opiniao['autor']['nome'] ?? $opiniao['autores_nomes'] ?? 'Redação discente')) ?></strong>
            <?php if (!empty($opiniao['autor']['turma'])): ?><small><?= e($opiniao['autor']['turma']) ?></small><?php endif; ?>
          </span>
        </div>
      </article>
    <?php endif; ?>
    <?php if ($flagrante && !empty($flagrante['foto_url'])): ?>
      <figure class="opinion__photo">
        <div class="m m--r32"><img class="m__img" src="<?= BASE_URL ?>/<?= e((string)$flagrante['foto_url']) ?>" alt="<?= e((string)($flagrante['legenda'] ?? $flagrante['titulo'] ?? '')) ?>" loading="lazy" decoding="async"></div>
        <figcaption>
          <span><strong>Flagrante da semana.</strong> <?= e((string)($flagrante['legenda'] ?? '')) ?></span>
          <?php if (!empty($flagrante['fotografo'])): ?><span class="credit"><?= e($credit((string)$flagrante['fotografo'])) ?></span><?php endif; ?>
        </figcaption>
      </figure>
    <?php endif; ?>
  </section>
  <?php endif; ?>

  <?php if ($campus_blocks): ?>
  <!-- Do campus: clube do livro, placares, processos seletivos, edição -->
  <section class="campus" style="--cols: <?= count($campus_blocks) ?>" aria-label="Do campus">
    <?php if (in_array('livro', $campus_blocks, true)): ?>
    <div class="campus__col campus-book">
      <h2>Clube do Livro</h2>
      <div class="campus-book__row">
        <?php if (!empty($clube_livro['capa_url'])): ?>
          <img src="<?= BASE_URL ?>/<?= e((string)$clube_livro['capa_url']) ?>" alt="Capa de <?= e($clube_livro['titulo_livro']) ?>" loading="lazy" decoding="async">
        <?php endif; ?>
        <div>
          <h3><?= e($clube_livro['titulo_livro']) ?></h3>
          <p class="meta"><span><?= e($clube_livro['autor_livro']) ?></span></p>
        </div>
      </div>
      <?php if (!empty($clube_livro['chamada_editorial'])): ?><p class="campus__lead"><?= e($clube_livro['chamada_editorial']) ?></p><?php endif; ?>
      <?php if (!empty($clube_livro['proxima_leitura_titulo'])): ?>
        <p class="campus__note">Próxima leitura: <strong><?= e($clube_livro['proxima_leitura_titulo']) ?></strong><?php if (!empty($clube_livro['proxima_leitura_autor'])): ?>, de <?= e($clube_livro['proxima_leitura_autor']) ?><?php endif; ?>.</p>
      <?php endif; ?>
      <?php if (!empty($clube_livro['info_inscricao'])): ?><p class="campus__note"><?= e($clube_livro['info_inscricao']) ?></p><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (in_array('jogos', $campus_blocks, true)): ?>
    <div class="campus__col campus-scores">
      <h2>Placares dos jogos</h2>
      <ul>
        <?php foreach ($jogos_tabela as $jogo): ?>
          <li>
            <?php if (!empty($jogo['modalidade'])): ?><small><?= e($jogo['modalidade']) ?><?php if (!empty($jogo['fase'])): ?>, <?= e($jogo['fase']) ?><?php endif; ?></small><?php endif; ?>
            <span class="score">
              <span><?= e($jogo['equipe_a']) ?></span>
              <b><?= (int)$jogo['placar_a'] ?> × <?= (int)$jogo['placar_b'] ?></b>
              <span><?= e($jogo['equipe_b']) ?></span>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (in_array('processos', $campus_blocks, true)): ?>
    <div class="campus__col campus-process">
      <h2>Processos seletivos</h2>
      <ul>
        <?php foreach ($processos as $proc): ?>
          <li>
            <small><?= e($proc['categoria']) ?></small>
            <h3><?php if (!empty($proc['link_edital'])): ?><a href="<?= e($proc['link_edital']) ?>" rel="noopener"><?= e($proc['titulo']) ?></a><?php else: ?><?= e($proc['titulo']) ?><?php endif; ?></h3>
            <?php if (!empty($proc['data_limite'])): ?><p class="campus__deadline">Prazo: <?= e($proc['data_limite']) ?></p><?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endif; ?>

    <?php if (in_array('edicao', $campus_blocks, true)): ?>
    <div class="campus__col campus-edition">
      <h2>Edição nº <?= (int)$edicao['numero'] ?></h2>
      <div class="campus-book__row">
        <?php if (!empty($edicao['capa_url'])): ?>
          <img src="<?= BASE_URL ?>/<?= e((string)$edicao['capa_url']) ?>" alt="Capa da edição nº <?= (int)$edicao['numero'] ?>" loading="lazy" decoding="async">
        <?php endif; ?>
        <div>
          <h3><?= e($edicao['titulo']) ?></h3>
          <p class="meta"><span><?= e($edicao['mes_ano']) ?></span></p>
        </div>
      </div>
      <?php if (!empty($edicao['pdf_url'])): ?>
        <a class="btn btn--outline" href="<?= BASE_URL ?>/<?= e((string)$edicao['pdf_url']) ?>" rel="noopener">Baixar a edição em PDF</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </section>
  <?php endif; ?>

</main>

<?php if (!empty($home_settings['modulo_banner_pauta'])) require __DIR__ . '/banner-pauta.php'; ?>
