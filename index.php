<?php
/**
 * O FAROL - Página Inicial Modernizada
 * Layout Editorial Premium (The New York Times / The Guardian / Atlantic)
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();
$home_settings = get_site_settings();

// 1. Manchete Hero de Alto Impacto
$hero = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.cor as categoria_cor 
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.status = 'publicado' AND n.posicao_homepage = 'manchete_hero' 
                               ORDER BY n.publicado_em DESC LIMIT 1");
        $stmt->execute();
        $hero = $stmt->fetch();

        if ($hero) {
            $stmtAutores = $pdo->prepare("SELECT a.* FROM autores a 
                                          JOIN noticia_autores na ON a.id = na.autor_id 
                                          WHERE na.noticia_id = ?");
            $stmtAutores->execute([$hero['id']]);
            $hero['autores'] = $stmtAutores->fetchAll();
        }
    } catch (Exception $e) {}
}

// 2. Seção Ciência & Prática Técnica
$ciencia = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.status = 'publicado' AND n.posicao_homepage = 'ciencia_tecnica' 
                               LIMIT 1");
        $stmt->execute();
        $ciencia = $stmt->fetch();
        if ($ciencia) {
            $stmtA = $pdo->prepare("SELECT a.nome, a.turma FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = ?");
            $stmtA->execute([$ciencia['id']]);
            $ciencia['autores'] = $stmtA->fetchAll();
        }
    } catch (Exception $e) {}
}

// 3. Jogos Escolares Interclasses
$jogos_noticia = null;
$jogos_tabela = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM noticias WHERE status = 'publicado' AND posicao_homepage = 'jogos_esportes' LIMIT 1");
        $stmt->execute();
        $jogos_noticia = $stmt->fetch();
        if ($jogos_noticia) {
            $stmtA = $pdo->prepare("SELECT a.nome FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = ?");
            $stmtA->execute([$jogos_noticia['id']]);
            $jogos_noticia['autores'] = $stmtA->fetchAll();
        }

        $stmtTab = $pdo->query("SELECT * FROM jogos_resultados WHERE destaque_home = 1 ORDER BY ordem ASC LIMIT 4");
        $jogos_tabela = $stmtTab->fetchAll();
    } catch (Exception $e) {}
}

// 4. Cultura & Empreendedorismo Estudantil
$cultura_noticias = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.status = 'publicado' AND n.posicao_homepage = 'cultura_empreendedorismo' 
                               ORDER BY n.ordem_home ASC LIMIT 2");
        $stmt->execute();
        $cultura_noticias = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// 5. Coluna de Opinião
$opiniao = null;
if ($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT n.* FROM noticias n WHERE n.status = 'publicado' AND n.posicao_homepage = 'coluna_opiniao' LIMIT 1");
        $stmt->execute();
        $opiniao = $stmt->fetch();
        if ($opiniao) {
            $stmtA = $pdo->prepare("SELECT a.* FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = ?");
            $stmtA->execute([$opiniao['id']]);
            $opiniao['autor'] = $stmtA->fetch();
        }
    } catch (Exception $e) {}
}

// 6. Sidebar: Clube do Livro
$clube_livro = null;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM clube_livro WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
        $clube_livro = $stmt->fetch();
    } catch (Exception $e) {}
}

// 7. Sidebar: Processos Seletivos
$processos = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM processos_seletivos WHERE ativo = 1 ORDER BY ordem ASC LIMIT 3");
        $processos = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// 8. Sidebar: Enquete (Voz do Aluno)
$enquete = null;
$enquete_opcoes = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM enquetes WHERE ativo = 1 ORDER BY id DESC LIMIT 1");
        $enquete = $stmt->fetch();
        if ($enquete) {
            $stmtO = $pdo->prepare("SELECT * FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem ASC");
            $stmtO->execute([$enquete['id']]);
            $enquete_opcoes = $stmtO->fetchAll();
        }
    } catch (Exception $e) {}
}

// 9. Flagrantes da Semana
$flagrante = null;
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM flagrantes_semana WHERE destaque = 1 ORDER BY id DESC LIMIT 1");
        $flagrante = $stmt->fetch();
    } catch (Exception $e) {}
}
?>

<main id="conteudo-principal" class="container-farol py-8 site-home">

  <!-- ========================================================
       1. MANCHETE HERO DE GRANDE IMPACTO EDITORIAL
       ======================================================== -->
  <?php if (!empty($home_settings['modulo_manchete']) && $hero): ?>
    <section class="editorial-card p-6 sm:p-8 lg:p-10 mb-10 bg-white border border-[#e6e3da]">
      
      <!-- Topo Editorial com Badges e Metadata -->
      <div class="flex flex-wrap items-center justify-between gap-3 pb-3 mb-6 border-b border-gray-100">
        <div class="flex items-center gap-2">
          <span class="editorial-badge badge-crimson"><?= e($hero['chapeu'] ?: 'REPORTAGEM PRINCIPAL') ?></span>
          <span class="editorial-badge badge-teal"><?= e($hero['categoria_nome']) ?></span>
        </div>
        <div class="text-xs text-gray-400 font-sans flex items-center gap-2">
          <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span><?= e((string)$hero['tempo_leitura']) ?> min de leitura</span>
          <span>•</span>
          <span>Atualizado às <?= date('H:i', strtotime($hero['publicado_em'] ?? 'now')) ?></span>
        </div>
      </div>

      <!-- Composição Fotográfica e Textual Equilibrada -->
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Bloco de Texto (7 Colunas) -->
        <div class="lg:col-span-7 flex flex-col justify-between">
          <div>
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-headline font-bold text-[#111827] leading-[1.12] mb-4 hover:text-[#b91c1c] transition-colors">
              <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($hero['slug']) ?>">
                <?= e($hero['titulo']) ?>
              </a>
            </h2>

            <p class="text-base sm:text-lg text-gray-600 font-editorial-body leading-relaxed mb-6 text-justify">
              <?= e($hero['subtitulo']) ?>
            </p>

            <!-- Pull Quote Editorial Moderno -->
            <?php if (!empty($hero['citacao_destaque'])): ?>
              <div class="modern-pullquote">
                <p>“<?= e($hero['citacao_destaque']) ?>”</p>
                <?php if (!empty($hero['citacao_autor'])): ?>
                  <span class="block text-right text-xs font-sans not-italic font-bold text-gray-500 mt-2">
                    — <?= e($hero['citacao_autor']) ?>
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Assinatura da Redação Discente e Botão de Leitura -->
          <div class="pt-6 border-t border-gray-100 flex flex-wrap items-center justify-between gap-4 mt-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-[#0f4c5c] text-white flex items-center justify-center text-xs font-bold font-headline shadow-sm">
                OF
              </div>
              <div class="text-xs">
                <div class="font-bold text-gray-900 font-sans">
                  Redação Discente: 
                  <span class="font-normal text-gray-700">
                    <?php 
                      if (!empty($hero['autores'])) {
                        $nomes = array_map(fn($a) => $a['nome'], $hero['autores']);
                        echo e(implode(' e ', $nomes));
                      } else {
                        echo "Equipe O Farol";
                      }
                    ?>
                  </span>
                </div>
                <?php if (!empty($hero['professor_orientador'])): ?>
                  <div class="text-[11px] text-gray-500 font-sans">Orientação: <?= e($hero['professor_orientador']) ?></div>
                <?php endif; ?>
              </div>
            </div>

            <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($hero['slug']) ?>" class="btn-editorial-primary">
              Ler Reportagem Completa
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
          </div>
        </div>

        <!-- Bloco de Fotografia com Lightbox (5 Colunas) -->
        <div class="lg:col-span-5">
          <div class="editorial-card p-1 bg-white">
            <div class="img-zoom-wrapper rounded overflow-hidden">
              <img src="<?= BASE_URL ?>/<?= e($hero['imagem_capa']) ?>" 
                   alt="<?= e($hero['titulo']) ?>" 
                   data-caption="<?= e($hero['legenda_capa'] ?? '') ?>"
                   class="w-full h-72 sm:h-96 object-cover zoomable-image">
            </div>
            
            <?php if (!empty($hero['legenda_capa']) || !empty($hero['credito_foto'])): ?>
              <div class="p-3 bg-[#fbfbf9] text-xs text-gray-600 font-sans border-t border-gray-100 rounded-b">
                <p class="leading-snug"><?= e($hero['legenda_capa']) ?></p>
                <?php if (!empty($hero['credito_foto'])): ?>
                  <p class="text-[10px] text-gray-400 text-right mt-1.5 font-medium"><?= e($hero['credito_foto']) ?></p>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </section>
  <?php endif; ?>

  <!-- ========================================================
       2. GRID PRINCIPAL (CONTEÚDO + SIDEBAR LATERAL)
       ======================================================== -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
    
    <!-- COLUNA PRINCIPAL DE CADERNOS TEMÁTICOS (8 Colunas) -->
    <div class="lg:col-span-8 space-y-10">
      
      <!-- SEÇÃO 1: CIÊNCIA & PRÁTICA TÉCNICA -->
      <?php if (!empty($home_settings['modulo_ciencia'])): ?><section class="editorial-card p-6 sm:p-7">
        <div class="section-header-modern">
          <h3 class="text-xl sm:text-2xl flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#0f4c5c] inline-block"></span>
            Ciência &amp; Prática Técnica
          </h3>
          <span class="section-tag">Projetos Integradores</span>
        </div>

        <?php if ($ciencia): ?>
          <div class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-start">
            <div class="sm:col-span-5 img-zoom-wrapper rounded overflow-hidden border border-gray-200">
              <img src="<?= BASE_URL ?>/<?= e($ciencia['imagem_capa']) ?>" 
                   alt="<?= e($ciencia['titulo']) ?>" 
                   data-caption="<?= e($ciencia['legenda_capa'] ?? '') ?>"
                   class="w-full h-44 object-cover zoomable-image">
              <?php if (!empty($ciencia['legenda_capa'])): ?>
                <p class="p-2 bg-gray-50 text-[10px] text-gray-500 leading-tight"><?= e($ciencia['legenda_capa']) ?></p>
              <?php endif; ?>
            </div>
            
            <div class="sm:col-span-7 flex flex-col justify-between h-full">
              <div>
                <span class="editorial-badge badge-teal mb-2"><?= e($ciencia['categoria_nome']) ?></span>
                <h4 class="text-xl font-headline font-bold text-[#111827] leading-snug hover:text-[#b91c1c] transition-colors mb-2">
                  <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($ciencia['slug']) ?>">
                    <?= e($ciencia['titulo']) ?>
                  </a>
                </h4>
                <p class="text-xs sm:text-sm text-gray-600 font-sans leading-relaxed mb-4">
                  <?= e($ciencia['subtitulo']) ?>
                </p>
              </div>

              <div class="flex items-center justify-between text-xs text-gray-500 pt-3 border-t border-gray-100">
                <span class="font-medium">Por <?= !empty($ciencia['autores'][0]['nome']) ? e($ciencia['autores'][0]['nome'] . ' (' . $ciencia['autores'][0]['turma'] . ')') : 'Redação Discente' ?></span>
                <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($ciencia['slug']) ?>" class="font-bold text-[#b91c1c] hover:underline flex items-center gap-1">
                  Ler artigo <span>→</span>
                </a>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </section><?php endif; ?>

      <!-- SEÇÃO 2: JOGOS ESCOLARES INTERCLASSES -->
      <?php if (!empty($home_settings['modulo_jogos'])): ?><section class="editorial-card p-6 sm:p-7">
        <div class="section-header-modern">
          <h3 class="text-xl sm:text-2xl flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-[#b91c1c] inline-block"></span>
            Jogos Escolares Interclasses
          </h3>
          <span class="section-tag">Fase Semifinal</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
          
          <!-- Notícia dos Jogos (7 Colunas) -->
          <div class="md:col-span-7">
            <?php if ($jogos_noticia): ?>
              <h4 class="text-lg sm:text-xl font-headline font-bold text-[#111827] leading-snug hover:text-[#b91c1c] transition-colors mb-3">
                <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($jogos_noticia['slug']) ?>">
                  <?= e($jogos_noticia['titulo']) ?>
                </a>
              </h4>
              <p class="text-xs sm:text-sm text-gray-600 font-sans leading-relaxed mb-4">
                <?= e($jogos_noticia['subtitulo']) ?>
              </p>
              <div class="text-xs text-gray-500 pt-2 border-t border-gray-100">
                Cobertura de quadra: <span class="font-bold text-gray-800"><?= !empty($jogos_noticia['autores']) ? e(implode(' e ', array_map(fn($a)=>$a['nome'], $jogos_noticia['autores']))) : 'Redação Esportiva' ?></span>
              </div>
            <?php endif; ?>
          </div>

          <!-- Quadro de Resultados Integrado (5 Colunas) -->
          <div class="md:col-span-5 bg-[#fbfbf9] p-4 rounded border border-[#e6e3da]">
            <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-200">
              <span class="text-xs font-bold text-gray-800 uppercase tracking-wider font-sans">Quadro de Resultados</span>
              <span class="text-[10px] font-bold text-[#b91c1c] uppercase">Ao Vivo</span>
            </div>

            <div class="space-y-2">
              <?php foreach ($jogos_tabela as $jogo): ?>
                <div class="flex items-center justify-between bg-white px-3 py-2 rounded border border-gray-200 text-xs shadow-sm">
                  <span class="font-semibold text-gray-800 truncate max-w-[110px]"><?= e($jogo['equipe_a']) ?></span>
                  <span class="font-bold bg-[#0f4c5c] text-white px-2 py-0.5 rounded text-[11px] font-mono">
                    <?= e((string)$jogo['placar_a']) ?> × <?= e((string)$jogo['placar_b']) ?>
                  </span>
                  <span class="font-semibold text-gray-800 truncate max-w-[110px] text-right"><?= e($jogo['equipe_b']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>

            <a href="<?= BASE_URL ?>/categoria.php?slug=jogos-escolares" class="block text-center text-xs font-bold text-[#0f4c5c] hover:underline uppercase tracking-wider mt-3 pt-2 border-t border-gray-200">
              Ver Chaveamento Completo →
            </a>
          </div>

        </div>
      </section><?php endif; ?>

      <!-- SEÇÃO 3: CULTURA & EMPREENDEDORISMO -->
      <?php if (!empty($home_settings['modulo_cultura'])): ?><section class="editorial-card p-6 sm:p-7">
        <div class="section-header-modern">
          <h3 class="text-xl sm:text-2xl flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>
            Cultura &amp; Empreendedorismo Estudantil
          </h3>
          <span class="section-tag">Circuito de Junho</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <?php foreach ($cultura_noticias as $cultura): ?>
            <div class="flex flex-col justify-between p-4 bg-[#fbfbf9] rounded border border-gray-200 hover:border-gray-300 transition-colors">
              <div>
                <span class="editorial-badge badge-teal mb-2"><?= e($cultura['categoria_nome']) ?></span>
                <h4 class="text-base sm:text-lg font-headline font-bold text-[#111827] leading-snug hover:text-[#b91c1c] transition-colors my-2">
                  <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($cultura['slug']) ?>">
                    <?= e($cultura['titulo']) ?>
                  </a>
                </h4>
                <p class="text-xs text-gray-600 font-sans leading-relaxed mb-4">
                  <?= e($cultura['subtitulo']) ?>
                </p>
              </div>
              <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($cultura['slug']) ?>" class="text-xs font-bold text-[#b91c1c] hover:underline flex items-center gap-1">
                Confira a programação completa <span>→</span>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </section><?php endif; ?>

      <!-- SEÇÃO 4: COLUNA DE OPINIÃO + FLAGRANTES DA SEMANA -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
        
        <!-- Coluna de Opinião (7 Colunas) -->
        <div class="md:col-span-7 editorial-card p-6">
          <div class="flex items-center gap-2 mb-3">
            <span class="editorial-badge badge-crimson text-[9px]">COLUNA DE OPINIÃO</span>
            <span class="text-xs text-gray-400 font-sans uppercase tracking-wider">CIDADANIA &amp; ESCOLA</span>
          </div>

          <?php if ($opiniao): ?>
            <h4 class="text-xl font-headline font-bold text-[#111827] leading-tight hover:text-[#b91c1c] transition-colors mb-3">
              <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($opiniao['slug']) ?>">
                “<?= e($opiniao['titulo']) ?>”
              </a>
            </h4>

            <p class="text-xs sm:text-sm text-gray-700 font-editorial-body leading-relaxed mb-4 text-justify">
              <?= e($opiniao['subtitulo']) ?>
            </p>

            <div class="flex items-center gap-3 pt-3 border-t border-gray-100">
              <div class="w-10 h-10 rounded-full bg-red-50 text-[#b91c1c] flex items-center justify-center font-bold text-sm font-headline border border-red-200">
                GC
              </div>
              <div class="text-xs">
                <div class="font-bold text-gray-900"><?= e($opiniao['autor']['nome'] ?? 'Gabriel Castanho') ?></div>
                <div class="text-gray-500"><?= e($opiniao['autor']['turma'] ?? '3º ano do Ensino Médio Integrado') ?></div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Flagrantes da Semana / Campus em Foco (5 Colunas) -->
        <div class="md:col-span-5 editorial-card p-6 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-200">
              <span class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5 font-sans">
                <svg class="w-4 h-4 text-[#0f4c5c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Flagrantes da Semana
              </span>
              <span class="editorial-badge badge-teal text-[8px]">EM FOCO</span>
            </div>

            <?php if ($flagrante): ?>
              <div class="img-zoom-wrapper rounded overflow-hidden border border-gray-200 mb-2">
                <img src="<?= BASE_URL ?>/<?= e($flagrante['foto_url']) ?>" 
                     alt="<?= e($flagrante['titulo']) ?>" 
                     data-caption="<?= e($flagrante['legenda']) ?>"
                     class="w-full h-40 object-cover zoomable-image">
              </div>
              <p class="text-xs text-gray-600 font-sans leading-snug">
                “<?= e($flagrante['legenda']) ?>”
              </p>
            <?php endif; ?>
          </div>

          <div class="text-[11px] text-gray-400 text-right mt-3 pt-2 border-t border-gray-100 font-medium">
            Registro: <?= e($flagrante['fotografo'] ?? 'Lucas Rios') ?>
          </div>
        </div>

      </div>

    </div>

    <!-- SIDEBAR LATERAL MODERNA (4 Colunas) -->
    <aside class="lg:col-span-4 space-y-6">

      <!-- MÓDULO 1: CLUBE DO LIVRO (OBRA DO MÊS) -->
      <?php if ($clube_livro): ?>
        <div class="editorial-card p-6 bg-white">
          <div class="flex items-center gap-2 pb-2 mb-4 border-b-2 border-[#111827]">
            <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">CLUBE DO LIVRO • OBRA DO MÊS</h4>
          </div>

          <!-- Destaque do Livro -->
          <div class="bg-[#f8fafc] border border-gray-200 p-4 rounded text-center mb-4">
            <span class="text-[11px] text-gray-400 uppercase tracking-wider font-bold block mb-1">Leitura Selecionada</span>
            <h5 class="text-xl font-headline font-bold text-[#111827]">“<?= e($clube_livro['titulo_livro']) ?>”</h5>
            <span class="text-xs text-gray-600 font-medium block mt-0.5"><?= e($clube_livro['autor_livro']) ?></span>
          </div>

          <h5 class="text-sm font-headline font-bold text-[#111827] leading-snug mb-2">
            <?= e($clube_livro['chamada_editorial']) ?>
          </h5>

          <p class="text-xs text-gray-600 font-sans leading-relaxed mb-4 text-justify">
            <?= e($clube_livro['resumo']) ?>
          </p>

          <div class="border-t border-gray-100 pt-3 text-xs bg-gray-50 p-3 rounded">
            <span class="text-[10px] text-gray-400 uppercase font-bold block mb-1">Próxima Leitura Selecionada:</span>
            <div class="font-bold text-gray-800">
              <?= e($clube_livro['proxima_leitura_titulo']) ?> — <span class="font-normal text-gray-600"><?= e($clube_livro['proxima_leitura_autor']) ?></span>
            </div>
            <p class="text-[11px] text-gray-500 mt-1.5"><?= e($clube_livro['info_inscricao']) ?></p>
          </div>
        </div>
      <?php endif; ?>

      <!-- MÓDULO 2: PROCESSOS SELETIVOS -->
      <div class="editorial-card p-6 bg-white">
        <div class="flex items-center gap-2 pb-2 mb-4 border-b-2 border-[#111827]">
          <svg class="w-4 h-4 text-[#0f4c5c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">PROCESSOS SELETIVOS 2026/2027</h4>
        </div>

        <div class="space-y-4">
          <?php foreach ($processos as $proc): ?>
            <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
              <span class="editorial-badge badge-teal text-[8px]"><?= e($proc['categoria']) ?></span>
              <h5 class="text-xs font-bold text-gray-900 mt-1"><?= e($proc['titulo']) ?></h5>
              <p class="text-xs text-gray-600 leading-snug mt-1"><?= e($proc['descricao']) ?></p>
              <?php if (!empty($proc['data_limite'])): ?>
                <span class="text-[11px] font-bold text-[#b91c1c] block mt-1.5 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  Prazo: <?= e($proc['data_limite']) ?>
                </span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100">
          <a href="#" class="btn-editorial-teal w-full text-center text-[11px]">
            Baixar Calendário Acadêmico em PDF
          </a>
        </div>
      </div>

      <!-- MÓDULO 3: VOZ DO ALUNO (ENQUETE COM FEEDBACK VISUAL) -->
      <?php if ($enquete): ?>
        <div class="editorial-card p-6 bg-white">
          <div class="flex items-center gap-2 pb-2 mb-3 border-b-2 border-[#111827]">
            <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">VOZ DO ALUNO • ENQUETE</h4>
          </div>

          <h5 class="text-sm font-headline font-bold text-[#111827] leading-snug mb-4">
            <?= e($enquete['pergunta']) ?>
          </h5>

          <form id="form-enquete" action="<?= BASE_URL ?>/api/votar-enquete.php" method="POST">
            <input type="hidden" name="enquete_id" value="<?= e((string)$enquete['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="space-y-2 mb-4" id="enquete-opcoes-container">
              <?php foreach ($enquete_opcoes as $idx => $op): ?>
                <label class="flex items-start gap-2.5 text-xs text-gray-700 cursor-pointer p-2.5 rounded hover:bg-gray-50 border border-gray-200 transition-colors">
                  <input type="radio" name="opcao_id" value="<?= e((string)$op['id']) ?>" <?= $idx === 0 ? 'checked' : '' ?> class="mt-0.5 text-[#0f4c5c] focus:ring-[#0f4c5c]">
                  <span class="font-medium"><?= e($op['opcao']) ?></span>
                </label>
              <?php endforeach; ?>
            </div>

            <button type="submit" id="btn-votar" class="w-full btn-editorial-primary text-xs py-2.5">
              Registrar Meu Voto
            </button>
            <div id="enquete-feedback" class="mt-2 text-center text-xs hidden"></div>
          </form>
        </div>
      <?php endif; ?>

    </aside>

  </div>

</main>

<script>
// Script Interativo para Voto na Enquete sem recarregar a página
document.getElementById('form-enquete')?.addEventListener('submit', async function(e) {
  e.preventDefault();
  const form = e.target;
  const btn = document.getElementById('btn-votar');
  const feedback = document.getElementById('enquete-feedback');
  
  btn.disabled = true;
  btn.innerText = 'Processando voto...';

  try {
    const formData = new FormData(form);
    const resp = await fetch(form.action, {
      method: 'POST',
      body: formData
    });
    const data = await resp.json();

    feedback.classList.remove('hidden');
    if (data.sucesso) {
      feedback.className = 'mt-3 text-center text-xs text-emerald-800 bg-emerald-50 p-2.5 rounded border border-emerald-200';
      feedback.innerText = '✓ ' + data.mensagem;
      btn.innerText = 'VOTO COMPUTADO';
      
      if (data.resultados) {
        let html = '<div class="space-y-3 mt-3">';
        data.resultados.forEach(r => {
          html += `<div>
            <div class="flex justify-between text-xs mb-1 font-semibold text-gray-800">
              <span>${r.opcao}</span>
              <span>${r.percentual}%</span>
            </div>
            <div class="w-full bg-gray-200 h-2.5 rounded-full overflow-hidden">
              <div class="bg-[#0f4c5c] h-full transition-all duration-500" style="width: ${r.percentual}%"></div>
            </div>
          </div>`;
        });
        html += '</div>';
        document.getElementById('enquete-opcoes-container').innerHTML = html;
      }
    } else {
      feedback.className = 'mt-3 text-center text-xs text-red-800 bg-red-50 p-2.5 rounded border border-red-200';
      feedback.innerText = data.mensagem || 'Erro ao registrar voto.';
      btn.disabled = false;
      btn.innerText = 'REGISTRAR MEU VOTO';
    }
  } catch (err) {
    feedback.classList.remove('hidden');
    feedback.className = 'mt-3 text-center text-xs text-red-800 bg-red-50 p-2.5 rounded border border-red-200';
    feedback.innerText = 'Erro de comunicação com o servidor.';
    btn.disabled = false;
    btn.innerText = 'REGISTRAR MEU VOTO';
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
