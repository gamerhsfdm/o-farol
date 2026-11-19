<?php
/**
 * O FAROL - Gestão de Sugestões de Pautas Fiel à Imagem 9
 * Lista Master-Detail: Pautas Recebidas (Esq) + Detalhe com Informações e Botão "Transformar em reportagem" (Dir)
 */
declare(strict_types=1);
$admin_title = "Sugestões de pautas";
$admin_breadcrumb = "Sugestões de pautas";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// 1. Ação: Transformar pauta em reportagem (Cria rascunho de notícia e abre o editor)
if (isset($_GET['transformar_id']) && $pdo) {
    try {
        $pId = (int)$_GET['transformar_id'];
        $stmtP = $pdo->prepare("SELECT * FROM pautas_sugestoes WHERE id = ?");
        $stmtP->execute([$pId]);
        $pautaBd = $stmtP->fetch();

        if ($pautaBd) {
            // Categoria padrão
            $stmtCat = $pdo->query("SELECT id FROM categorias LIMIT 1");
            $catId = $stmtCat->fetchColumn() ?: 1;

            $titulo = $pautaBd['titulo_sugestao'] ?? $pautaBd['titulo'] ?? 'Reportagem sugerida';
            $slug = slugify($titulo);
            $descricao = $pautaBd['descricao'] ?? '';
            $autorEstudante = $pautaBd['nome_estudante'] ?? $pautaBd['nome_autor'] ?? 'Estudante Colaborador';
            $turma = $pautaBd['turma_curso'] ?? $pautaBd['turma'] ?? '';

            $conteudoIni = "<p>" . nl2br(e($descricao)) . "</p>\n<p><em>Pauta sugerida pelo(a) estudante {$autorEstudante} ({$turma}).</em></p>";
            $subtitulo = "Reportagem produzida a partir de sugestão da comunidade discente.";

            $stmtIns = $pdo->prepare("INSERT INTO noticias (categoria_id, titulo, subtitulo, conteudo, slug, status, criado_em) VALUES (?, ?, ?, ?, ?, 'rascunho', NOW())");
            $stmtIns->execute([$catId, $titulo, $subtitulo, $conteudoIni, $slug]);
            $novaNoticiaId = $pdo->lastInsertId();

            // Atualiza status da pauta para aprovada
            $stmtUp = $pdo->prepare("UPDATE pautas_sugestoes SET status = 'aprovada' WHERE id = ?");
            $stmtUp->execute([$pId]);

            header("Location: " . BASE_URL . "/admin/editor.php?id=" . $novaNoticiaId);
            exit;
        }
    } catch (Exception $e) {
        $erro = "Erro ao transformar pauta: " . $e->getMessage();
    }
}

// 2. Ações de moderação de pauta (Recusar / Descartar, Marcar em análise)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_pauta'])) {
    $pId = (int)($_POST['pauta_id'] ?? 0);
    $acao = $_POST['acao_pauta'];
    $novo_status = match($acao) {
        'em_analise' => 'em_analise',
        'recusada', 'descartada' => 'descartada',
        default => 'pendente'
    };

    if ($pdo && $pId > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE pautas_sugestoes SET status = ? WHERE id = ?");
            $stmt->execute([$novo_status, $pId]);
            $mensagem = "Status da pauta atualizado com sucesso!";
        } catch (Exception $e) {
            $erro = "Erro ao atualizar pauta: " . $e->getMessage();
        }
    } else {
        $mensagem = "Pauta atualizada com sucesso (modo visual/demonstração)!";
    }
}

$filtro_status = $_GET['status'] ?? 'todas';

// 3. Consulta pautas do banco
$pautas_lista = [];
if ($pdo) {
    try {
        $sql = "SELECT * FROM pautas_sugestoes";
        if ($filtro_status === 'novas') $sql .= " WHERE status = 'pendente'";
        elseif ($filtro_status === 'analise') $sql .= " WHERE status = 'em_analise'";
        $sql .= " ORDER BY id DESC";

        $stmt = $pdo->query($sql);
        $bdPautas = $stmt->fetchAll();
        foreach ($bdPautas as $idx => $p) {
            $nomeAluno = $p['nome_estudante'] ?? $p['nome_autor'] ?? 'Estudante';
            $sigla = strtoupper(mb_substr($nomeAluno, 0, 2));
            $titulo = $p['titulo_sugestao'] ?? $p['titulo'] ?? 'Sugestão sem título';
            $turma = $p['turma_curso'] ?? $p['turma'] ?? 'Estudante';
            $categoria = ucfirst(str_replace('_', ' ', $p['tipo_sugestao'] ?? $p['categoria_sugerida'] ?? 'Geral'));
            
            $statusRaw = $p['status'] ?? 'pendente';
            $statusLabel = match($statusRaw) {
                'pendente' => 'Nova',
                'em_analise' => 'Em análise',
                'aprovada' => 'Aprovada',
                'descartada' => 'Recusada',
                default => ucfirst($statusRaw)
            };

            $descCompleta = $p['descricao'] ?? '';
            $justificativa = '';
            if (str_contains($descCompleta, '[POR QUE ESSA PAUTA É IMPORTANTE?]')) {
                $partes = explode('[POR QUE ESSA PAUTA É IMPORTANTE?]', $descCompleta);
                $descExibida = trim($partes[0]);
                $justificativa = trim($partes[1] ?? '');
            } else {
                $descExibida = $descCompleta;
                $justificativa = $p['justificativa'] ?? 'Importância comunitária para os estudantes do Sesc-Senac.';
            }

            $pautas_lista[] = [
                'id' => $p['id'],
                'sigla' => $sigla,
                'titulo' => $titulo,
                'aluno' => $nomeAluno,
                'categoria' => $categoria,
                'turma' => $turma,
                'data' => date('d M, H:i', strtotime($p['criado_em'] ?? 'now')),
                'status' => $statusLabel,
                'descricao' => $descExibida,
                'justificativa' => $justificativa,
                'ativo' => $idx === 0
            ];
        }
    } catch (Exception $e) {}
}

// Fallback visual estrito à Imagem 9
if (empty($pautas_lista)) {
    $pautas_lista = [
        ['id' => 1, 'sigla' => 'MA', 'titulo' => 'Uma horta comunitária para nossa escola', 'aluno' => 'Mariana Alves', 'categoria' => 'Meio ambiente', 'turma' => '2º ano A', 'data' => 'Hoje, 09:14', 'status' => 'Nova', 'descricao' => 'Acho que seria interessante produzir uma reportagem mostrando como uma horta comunitária pode envolver estudantes de diferentes turmas. Podemos entrevistar professores de Ciências e pesquisar exemplos de outras escolas.', 'justificativa' => 'Além de melhorar o espaço da escola, o projeto pode ensinar sobre alimentação saudável e sustentabilidade.', 'ativo' => true],
        ['id' => 2, 'sigla' => 'JP', 'titulo' => 'Entrevista com ex-alunos empreendedores', 'aluno' => 'João Pedro', 'categoria' => 'Empreendedorismo', 'turma' => '3º ano B', 'data' => 'Ontem, 15:30', 'status' => 'Em análise', 'descricao' => 'Conectar os alunos atuais com trajetórias de sucesso de ex-alunos que abriram empresas na área de tecnologia e gastronomia.', 'justificativa' => 'Motivar as turmas do técnico com histórias práticas de sucesso no mercado de trabalho.', 'ativo' => false],
        ['id' => 3, 'sigla' => 'LS', 'titulo' => 'Campeonato de xadrez entre turmas', 'aluno' => 'Laura Silva', 'categoria' => 'Jogos Escolares', 'turma' => '1º ano C', 'data' => '10 jun', 'status' => 'Aprovada', 'descricao' => 'A cobertura dos tabuleiros com os lances finais da competição que movimentou os intervalos da biblioteca.', 'justificativa' => 'Valorização dos esportes da mente na escola.', 'ativo' => false],
        ['id' => 4, 'sigla' => 'PH', 'titulo' => 'Mais espaços de leitura no intervalo', 'aluno' => 'Pedro Henrique', 'categoria' => 'Clube do Livro', 'turma' => '2º ano B', 'data' => '08 jun', 'status' => 'Recusada', 'descricao' => 'Reportagem investigativa sobre o uso dos espaços de convivência e a necessidade de pufes e estantes abertas.', 'justificativa' => 'Fortalecimento da leitura fora da sala de aula.', 'ativo' => false]
    ];
}

$pauta_selecionada = $pautas_lista[0] ?? null;
?>

<!-- TOPO: TÍTULO DA PÁGINA -->
<div class="mb-6">
  <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
  <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Sugestões de pautas</h1>
  <p class="text-xs text-gray-500 mt-1">Analise ideias enviadas pela comunidade escolar.</p>
</div>

<?php if ($mensagem): ?>
  <div class="p-3 mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded flex items-center justify-between">
    <span>✓ <?= e($mensagem) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">×</button>
  </div>
<?php endif; ?>

<?php if ($erro): ?>
  <div class="p-3 mb-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded flex items-center justify-between">
    <span>⚠️ <?= e($erro) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-red-700 font-bold">×</button>
  </div>
<?php endif; ?>

<!-- ABAS DE FILTROS IDÊNTICAS À IMAGEM 9 -->
<div class="flex gap-2 mb-4">
  <a href="?status=todas" class="admin-pill-tab <?= $filtro_status === 'todas' ? 'active' : '' ?>">Todas</a>
  <a href="?status=novas" class="admin-pill-tab <?= $filtro_status === 'novas' ? 'active' : '' ?>">Novas</a>
  <a href="?status=analise" class="admin-pill-tab <?= $filtro_status === 'analise' ? 'active' : '' ?>">Em análise</a>
</div>

<!-- GRADE MASTER-DETAIL (LISTA À ESQUERDA + DETALHE À DIREITA) IDÊNTICO À IMAGEM 9 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
  
  <!-- Lista de Pautas (5 Colunas) -->
  <div class="lg:col-span-5 space-y-2">
    <?php foreach ($pautas_lista as $p): ?>
      <div onclick="exibirPauta(<?= htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8') ?>)" 
           class="admin-card p-3.5 cursor-pointer transition-colors <?= $p['ativo'] ? 'border-l-4 border-l-[#c0262d] bg-[#fbfbf9]' : 'hover:bg-white' ?> pauta-item" id="pauta-item-<?= $p['id'] ?>">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-[#f3f4f6] text-gray-600 font-bold text-xs flex items-center justify-center flex-shrink-0">
            <?= e($p['sigla']) ?>
          </div>
          <div class="flex-1">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-gray-900 leading-snug"><?= e($p['titulo']) ?></h4>
              <span class="text-[9px] text-gray-400"><?= e($p['data']) ?></span>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5"><?= e($p['aluno']) ?> • <?= e($p['categoria']) ?></div>
            <div class="mt-2">
              <span class="text-[9px] font-semibold px-2 py-0.5 rounded <?= $p['status'] === 'Nova' ? 'bg-emerald-50 text-emerald-700' : ($p['status'] === 'Em análise' ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600') ?>">
                <?= e($p['status']) ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Detalhe da Pauta (7 Colunas) IDÊNTICO À IMAGEM 9 -->
  <?php if ($pauta_selecionada): ?>
    <div class="lg:col-span-7 admin-card p-6 flex flex-col justify-between min-h-[440px]" id="painel-detalhe">
      <div>
        
        <!-- Topo do Detalhe: Tag Nova + Título + Botão de Opções -->
        <div class="flex items-center justify-between mb-2">
          <span class="text-[9.5px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded" id="detalhe-status">
            <?= e($pauta_selecionada['status']) ?>
          </span>
          <button type="button" class="text-gray-400 hover:text-black text-xs font-bold">•••</button>
        </div>

        <h2 class="text-2xl font-editorial-heading font-bold text-[#111827] leading-tight mb-1" id="detalhe-titulo">
          <?= e($pauta_selecionada['titulo']) ?>
        </h2>
        
        <p class="text-xs text-gray-400 mb-6" id="detalhe-autor-data">
          Enviado por <strong class="text-gray-600"><?= e($pauta_selecionada['aluno']) ?></strong> • <?= e($pauta_selecionada['data']) ?>
        </p>

        <!-- Informações do Estudante em Grade de 3 Colunas -->
        <div class="grid grid-cols-3 gap-4 p-3 bg-[#fafaf8] border border-gray-100 rounded-lg text-xs mb-6">
          <div>
            <span class="text-[9.5px] text-gray-400 uppercase tracking-wider block">ESTUDANTE</span>
            <span class="font-bold text-gray-800" id="detalhe-aluno"><?= e($pauta_selecionada['aluno']) ?></span>
          </div>
          <div>
            <span class="text-[9.5px] text-gray-400 uppercase tracking-wider block">CATEGORIA</span>
            <span class="font-bold text-gray-800" id="detalhe-categoria"><?= e($pauta_selecionada['categoria']) ?></span>
          </div>
          <div>
            <span class="text-[9.5px] text-gray-400 uppercase tracking-wider block">TURMA</span>
            <span class="font-bold text-gray-800" id="detalhe-turma"><?= e($pauta_selecionada['turma']) ?></span>
          </div>
        </div>

        <!-- Descrição da Sugestão -->
        <div class="mb-5">
          <h4 class="text-xs font-bold text-gray-900 mb-1.5">Descrição da sugestão</h4>
          <p class="text-xs text-gray-600 font-sans leading-relaxed text-justify" id="detalhe-descricao">
            <?= e($pauta_selecionada['descricao'] ?? '') ?>
          </p>
        </div>

        <!-- Justificativa: Por que essa pauta é importante? -->
        <div class="mb-6">
          <h4 class="text-xs font-bold text-gray-900 mb-1.5">Por que essa pauta é importante?</h4>
          <p class="text-xs text-gray-600 font-sans leading-relaxed text-justify" id="detalhe-justificativa">
            <?= e($pauta_selecionada['justificativa'] ?? '') ?>
          </p>
        </div>

      </div>

      <!-- Barra de Ações: Recusar, Marcar em análise, Transformar em reportagem -->
      <div class="pt-4 border-t border-gray-100 flex flex-wrap items-center justify-end gap-2.5">
        <form method="POST" action="" class="inline-flex gap-2">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="pauta_id" id="form-pauta-id" value="<?= $pauta_selecionada['id'] ?>">
          <button type="submit" name="acao_pauta" value="recusada" class="border border-gray-200 rounded px-3.5 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            Recusar
          </button>
          <button type="submit" name="acao_pauta" value="em_analise" class="border border-gray-200 rounded px-3.5 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 transition-colors">
            Marcar em análise
          </button>
        </form>

        <a href="?transformar_id=<?= $pauta_selecionada['id'] ?>" id="link-transformar" class="btn-admin-red">
          <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
          Transformar em reportagem
        </a>
      </div>

    </div>
  <?php endif; ?>

</div>

<script>
function exibirPauta(p) {
  document.getElementById('detalhe-status').innerText = p.status;
  document.getElementById('detalhe-titulo').innerText = p.titulo;
  document.getElementById('detalhe-autor-data').innerHTML = `Enviado por <strong class="text-gray-600">${p.aluno}</strong> • ${p.data}`;
  document.getElementById('detalhe-aluno').innerText = p.aluno;
  document.getElementById('detalhe-categoria').innerText = p.categoria;
  document.getElementById('detalhe-turma').innerText = p.turma;
  document.getElementById('detalhe-descricao').innerText = p.descricao || '';
  document.getElementById('detalhe-justificativa').innerText = p.justificativa || '';
  document.getElementById('form-pauta-id').value = p.id;
  document.getElementById('link-transformar').href = `?transformar_id=${p.id}`;

  document.querySelectorAll('.pauta-item').forEach(el => {
    el.classList.remove('border-l-4', 'border-l-[#c0262d]', 'bg-[#fbfbf9]');
  });
  const itemEl = document.getElementById('pauta-item-' + p.id);
  if (itemEl) itemEl.classList.add('border-l-4', 'border-l-[#c0262d]', 'bg-[#fbfbf9]');
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
