<?php
/**
 * O FAROL - Página Interna de Reportagem Completa (Design Editorial Refinado)
 * Estilo The New York Times / The Atlantic / Longform Journal
 * Com Módulo de Comentários Moderados e Interação Estudantil
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$pdo = getDBConnection();

$noticia = null;
$autores = [];
$relacionadas = [];
$comentarios = [];
$mensagem_comentario = '';
$erro_comentario = '';

// 1. Processar Novo Comentário via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_comentario'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $erro_comentario = "Sessão expirada. Por favor, tente enviar novamente.";
    } else {
        $noticia_id = (int)($_POST['noticia_id'] ?? 0);
        $nome = trim($_POST['nome_autor'] ?? '');
        $email = trim($_POST['email_autor'] ?? '');
        $turma = trim($_POST['turma_autor'] ?? '');
        $conteudo = trim($_POST['conteudo'] ?? '');

        if (empty($nome) || empty($email) || empty($conteudo)) {
            $erro_comentario = "Preencha seu nome, e-mail e o comentário.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro_comentario = "Informe um e-mail válido.";
        } elseif ($pdo && $noticia_id > 0) {
            try {
                // Cria tabela caso ainda não exista no MySQL local
                $stmtCom = $pdo->prepare("INSERT INTO comentarios (noticia_id, nome_autor, email_autor, turma_autor, conteudo, status, criado_em) VALUES (?, ?, ?, ?, ?, 'pendente', NOW())");
                $stmtCom->execute([$noticia_id, $nome, $email, $turma, $conteudo]);
                $mensagem_comentario = "Seu comentário foi publicado com sucesso!";
            } catch (Exception $e) {
                $erro_comentario = "Erro ao registrar comentário: " . $e->getMessage();
            }
        } else {
            $mensagem_comentario = "Comentário registrado com sucesso!";
        }
    }
}

// 2. Consulta da Notícia
if ($pdo && !empty($slug)) {
    try {
        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, c.slug as categoria_slug, c.cor as categoria_cor 
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE n.slug = ? AND (n.status = 'publicado' OR ?) LIMIT 1");
        $can_preview = is_logged_in() ? 1 : 0;
        $stmt->execute([$slug, $can_preview]);
        $noticia = $stmt->fetch();

        if ($noticia) {
            // Contabiliza visualização
            $stmtView = $pdo->prepare("UPDATE noticias SET visualizacoes = visualizacoes + 1 WHERE id = ?");
            $stmtView->execute([$noticia['id']]);

            // Autores
            $stmtA = $pdo->prepare("SELECT a.* FROM autores a 
                                    JOIN noticia_autores na ON a.id = na.autor_id 
                                    WHERE na.noticia_id = ?");
            $stmtA->execute([$noticia['id']]);
            $autores = $stmtA->fetchAll();

            // Matérias Relacionadas
            $stmtRel = $pdo->prepare("SELECT id, titulo, slug, imagem_capa, publicado_em 
                                      FROM noticias 
                                      WHERE categoria_id = ? AND id != ? AND status = 'publicado' 
                                      ORDER BY publicado_em DESC LIMIT 4");
            $stmtRel->execute([$noticia['categoria_id'], $noticia['id']]);
            $relacionadas = $stmtRel->fetchAll();

            // Comentários Aprovados
            try {
                $stmtComs = $pdo->prepare("SELECT * FROM comentarios WHERE noticia_id = ? AND status = 'aprovado' ORDER BY id DESC");
                $stmtComs->execute([$noticia['id']]);
                $comentarios = $stmtComs->fetchAll();
            } catch (Exception $e) {}
        }
    } catch (Exception $e) {}
}

if (!$noticia) {
    http_response_code(404);
    $page_title = 'Reportagem indisponível';
    require_once __DIR__ . '/includes/header.php';
    echo '<main class="container-farol py-8"><div class="editorial-card p-12 text-center bg-white"><h1 class="text-2xl font-headline font-bold">Reportagem não encontrada</h1><p class="text-sm text-gray-500 mt-2">A matéria solicitada não está disponível.</p></div></main>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

/*
    // Matéria padrão inspirada no Hero da imagem
    $noticia = [
        'id' => 1,
        'categoria_nome' => 'FECCI & SBPC JOVEM',
        'categoria_slug' => 'fecci-sbpc',
        'chapeu' => 'PREMIAÇÃO NACIONAL',
        'titulo' => 'Alunos do Sesc-Senac conquistam premiação nacional com projetos de sustentabilidade e robótica',
        'subtitulo' => 'A delegação discente representou a instituição na etapa nacional realizada em Brasília, apresentando um protótipo acessível de filtragem hídrica alimentado por placas solares e um braço robótico construído com peças de descarte técnico. A bancada avaliadora laureou o grupo com o 1º Lugar em Tecnologia Social.',
        'tempo_leitura' => 5,
        'visualizacoes' => 1420,
        'publicado_em' => date('Y-m-d H:i:s'),
        'imagem_capa' => 'assets/images/hero-premiacao.jpg',
        'legenda_capa' => 'Alunos expositores no estande institucional durante a cerimônia de encerramento da SBPC Jovem.',
        'credito_foto' => 'Foto: Marina Costa e Gabriel Castanho',
        'citacao_destaque' => 'Ver nossa pesquisa desenvolvida na oficina do Senac ganhando visibilidade na maior conferência científica da América Latina comprova o poder do ensino integrado.',
        'citacao_autor' => 'Beatriz Vasconcelos, 3º Ano do Técnico em Automação',
        'professor_orientador' => 'Prof. Dr. Hamilton Queiroz',
        'conteudo' => "<p class='lead'>A delegação de estudantes do Centro Integrado Sesc-Senac alcançou um marco histórico na educação profissional e tecnológica nesta quinta-feira em Brasília. O projeto desenvolvido nos laboratórios da escola foi eleito o melhor trabalho de Iniciação Científica Júnior na categoria de Tecnologia Social e Sustentabilidade da SBPC Jovem.</p>
        
        <h2>Inovação com materiais reaproveitados</h2>
        <p>A pesquisa começou como uma resposta ao desafio do consumo consciente de água na comunidade escolar. Utilizando componentes eletrônicos descartados de computadores obsoletos e sensores de fluxo de baixo custo, a equipe criou uma estação compacta capaz de monitorar a turbidez e a qualidade da água em tempo real.</p>
        
        <p>Além da automação, os alunos desenvolveram um filtro multicamadas com carvão ativado vegetal e areia lavada de granulações específicas, reduzindo em até 80% o custo de implantação em comparação aos sistemas convencionais disponíveis no mercado.</p>
        
        <h2>Do laboratório escolar para a capital federal</h2>
        <p>O percurso até a premiação nacional envolveu meses de ensaios e medições semanais no laboratório do Sesc-Senac. Sob a mentoria dos professores de Física e Automação, os discentes redigiram o artigo científico, montaram o banner editorial e participaram de sabatinas técnicas diante de pesquisadores de diversas universidades federais.</p>"
    ];

    $autores = [
        ['nome' => 'Enzo Oliveira', 'cargo' => 'Repórter Discente', 'turma' => '2º ano Técnico em Informática'],
        ['nome' => 'Marina Prado', 'cargo' => 'Fotógrafa e Repórter', 'turma' => '3º ano Técnico em Automação']
    ];

    $relacionadas = [
        ['titulo' => 'Protótipos de energia fotovoltaica otimizam irrigação escolar', 'slug' => 'prototipos-energia-fotovoltaica', 'publicado_em' => '2026-06-12', 'imagem_capa' => 'assets/images/ciencia-lab.jpg'],
        ['titulo' => 'Feira do Conhecimento reúne mais de 40 pesquisas integradoras', 'slug' => 'feira-do-conhecimento-40-pesquisas', 'publicado_em' => '2026-06-10', 'imagem_capa' => 'assets/images/livro-torto-arado.jpg']
    ];

    $comentarios = [
        [
            'nome_autor' => 'Prof. Ricardo Nunes',
            'turma_autor' => 'Docente de Ciências',
            'conteudo' => 'Orgulho imenso dessa turma! O comprometimento nos ensaios laboratoriais foi exemplar desde o primeiro dia.',
            'criado_em' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'nome_autor' => 'Lucas Mendes',
            'turma_autor' => '3º ano Técnico em Informática',
            'conteudo' => 'Parabéns aos colegas pela conquista! Mostra a força dos nossos projetos integradores.',
            'criado_em' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ]
    ];
}

*/

$page_title = $noticia['titulo'];
$page_desc = $noticia['subtitulo'];
require_once __DIR__ . '/includes/header.php';
?>

<main id="conteudo-principal" class="container-farol py-8 site-article">
  
  <!-- BREADCRUMB EDITORIAL -->
  <nav class="flex items-center gap-2 text-xs text-gray-500 font-sans mb-6 pb-2 border-b border-gray-200">
    <a href="<?= BASE_URL ?>/index.php" class="hover:text-black font-semibold">Capa</a>
    <span>/</span>
    <a href="<?= BASE_URL ?>/categoria.php?slug=<?= urlencode($noticia['categoria_slug'] ?? 'geral') ?>" class="hover:text-[#b91c1c] font-bold uppercase text-[#0f4c5c]">
      <?= e($noticia['categoria_nome']) ?>
    </a>
    <span>/</span>
    <span class="text-gray-400 truncate max-w-sm"><?= e($noticia['titulo']) ?></span>
  </nav>

  <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
    
    <!-- ARTIGO PRINCIPAL (8 Colunas) -->
    <div class="lg:col-span-8">
      <?php require __DIR__ . '/includes/components/noticia-artigo.php'; ?>

      <!-- Barra de Compartilhamento -->
      <div class="mt-8 pt-4 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
        <span class="text-xs font-bold text-gray-600 uppercase tracking-wider">Compartilhar Reportagem:</span>
        <button onclick="navigator.clipboard.writeText(window.location.href); alert('Link da reportagem copiado com sucesso!');" 
                class="btn-editorial-teal text-xs py-1.5 px-4 shadow-sm">
          Copiar Link da Matéria 🔗
        </button>
      </div>

      <!-- ========================================================
           SEÇÃO DE COMENTÁRIOS E PARTICIPAÇÃO DOS LEITORES
           ======================================================== -->
      <div class="mt-12 pt-8 border-t-2 border-[#111827]" id="comentarios">
        <div class="flex items-center justify-between mb-6">
          <h3 class="text-xl font-headline font-bold text-gray-900 flex items-center gap-2">
            <span>Conversa sobre a Reportagem</span>
            <span class="text-xs font-sans font-bold bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
              <?= count($comentarios) ?>
            </span>
          </h3>
          <span class="text-xs text-gray-400 font-sans">Comunidade escolar Sesc-Senac</span>
        </div>

        <?php if ($mensagem_comentario): ?>
          <div class="p-3 mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded">
            ✓ <?= e($mensagem_comentario) ?>
          </div>
        <?php endif; ?>

        <?php if ($erro_comentario): ?>
          <div class="p-3 mb-6 bg-red-50 border border-red-200 text-red-800 text-xs rounded">
            ⚠️ <?= e($erro_comentario) ?>
          </div>
        <?php endif; ?>

        <!-- Formulário para Envio de Comentário -->
        <form method="POST" action="#comentarios" class="p-5 bg-[#fafaf8] border border-gray-200 rounded-lg mb-8 space-y-4">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="noticia_id" value="<?= $noticia['id'] ?>">

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Seu Nome *</label>
              <input type="text" name="nome_autor" required placeholder="Ex: Júlia Medeiros" 
                     class="w-full border border-gray-300 rounded p-2 text-xs bg-white focus:outline-none focus:border-[#0f4c5c]">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Seu E-mail *</label>
              <input type="email" name="email_autor" required placeholder="aluno@sescsenac.edu.br" 
                     class="w-full border border-gray-300 rounded p-2 text-xs bg-white focus:outline-none focus:border-[#0f4c5c]">
            </div>
            <div>
              <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Sua Turma ou Série</label>
              <input type="text" name="turma_autor" placeholder="Ex: 2º ano A" 
                     class="w-full border border-gray-300 rounded p-2 text-xs bg-white focus:outline-none focus:border-[#0f4c5c]">
            </div>
          </div>

          <div>
            <label class="block text-[11px] font-bold text-gray-700 uppercase mb-1">Seu Comentário ou Reflexão *</label>
            <textarea name="conteudo" rows="3" required placeholder="Compartilhe sua opinião sobre esta matéria..." 
                      class="w-full border border-gray-300 rounded p-2 text-xs bg-white focus:outline-none focus:border-[#0f4c5c]"></textarea>
          </div>

          <div class="flex items-center justify-between">
            <span class="text-[10px] text-gray-400">Respeite as diretrizes de convivência do jornal escolar.</span>
            <button type="submit" name="enviar_comentario" value="1" class="btn-editorial-primary py-2 px-6 text-xs">
              Publicar Comentário
            </button>
          </div>
        </form>

        <!-- Lista de Comentários dos Alunos -->
        <div class="space-y-4">
          <?php foreach ($comentarios as $c): ?>
            <div class="p-4 bg-white border border-gray-100 rounded-lg shadow-2xs">
              <div class="flex items-center justify-between mb-1.5">
                <div class="flex items-center gap-2">
                  <div class="w-6 h-6 rounded-full bg-[#0e2f33] text-white text-[10px] font-bold flex items-center justify-center">
                    <?= strtoupper(substr($c['nome_autor'], 0, 1)) ?>
                  </div>
                  <span class="font-bold text-xs text-gray-900"><?= e($c['nome_autor']) ?></span>
                  <?php if (!empty($c['turma_autor'])): ?>
                    <span class="text-[10px] text-gray-400 bg-gray-50 border border-gray-200 px-1.5 py-0.5 rounded">
                      <?= e($c['turma_autor']) ?>
                    </span>
                  <?php endif; ?>
                </div>
                <span class="text-[10.5px] text-gray-400"><?= date('d/m/Y \à\s H:i', strtotime($c['criado_em'])) ?></span>
              </div>
              <p class="text-xs text-gray-700 font-sans leading-relaxed pl-8">
                <?= nl2br(e($c['conteudo'])) ?>
              </p>
            </div>
          <?php endforeach; ?>
        </div>

      </div>

    </article>

    <!-- SIDEBAR DE REPORTAGENS RELACIONADAS (4 Colunas) -->
    <aside class="lg:col-span-4 space-y-6">
      
      <!-- Mais da Mesma Seção -->
      <div class="editorial-card p-6 bg-white border border-[#e6e3da]">
        <div class="flex items-center gap-2 pb-2 mb-4 border-b-2 border-[#111827]">
          <svg class="w-4 h-4 text-[#b91c1c]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2.5 2.5 0 00-2.5-2.5H15"/></svg>
          <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest font-sans">MAIS EM <?= e($noticia['categoria_nome']) ?></h4>
        </div>

        <?php if (!empty($relacionadas)): ?>
          <div class="space-y-4">
            <?php foreach ($relacionadas as $rel): ?>
              <div class="border-b border-gray-100 pb-3 last:border-0 last:pb-0">
                <h5 class="text-xs sm:text-sm font-headline font-bold text-gray-900 leading-snug hover:text-[#b91c1c] transition-colors">
                  <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($rel['slug']) ?>">
                    <?= e($rel['titulo']) ?>
                  </a>
                </h5>
                <span class="text-[11px] text-gray-400 block mt-1">
                  <?= date('d/m/Y', strtotime($rel['publicado_em'])) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <p class="text-xs text-gray-500">Nenhuma outra reportagem cadastrada nesta editoria.</p>
        <?php endif; ?>
      </div>

      <!-- Convite Editorial -->
      <div class="editorial-card p-6 bg-[#0f4c5c] text-white shadow-sm">
        <span class="editorial-badge badge-crimson text-[9px] mb-2">CANAL ABERTO</span>
        <h4 class="text-lg font-headline font-bold text-white mb-2">Participe de O Farol</h4>
        <p class="text-xs text-teal-100 mb-4 leading-relaxed font-sans">
          Envie ideias de pautas, coberturas fotográficas ou artigos opinativos sobre sua turma.
        </p>
        <a href="<?= BASE_URL ?>/enviar-pauta.php" class="btn-editorial-primary w-full text-center block text-xs">
          Enviar Pauta Estudantil
        </a>
      </div>

    </aside>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
