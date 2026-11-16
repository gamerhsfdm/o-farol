<?php
/**
 * O FAROL - Gestão de Sugestões de Pautas Fiel à Imagem 9
 * Lista Master-Detail: Pautas Recebidas (Esq) + Detalhe com Informações e Botão "Transformar em reportagem" (Dir)
 */
declare(strict_types=1);
$admin_title = "Sugestões de pautas";
$admin_breadcrumb = "Sugestões de pautas";
require_once __DIR__ . '/header.php';

// Pautas exatas da imagem 9
$pautas_lista = [
    ['sigla' => 'MA', 'titulo' => 'Uma horta comunitária para nossa escola', 'aluno' => 'Mariana Alves', 'categoria' => 'Meio ambiente', 'turma' => '2º ano A', 'data' => 'Hoje, 09:14', 'status' => 'Nova', 'ativo' => true],
    ['sigla' => 'JP', 'titulo' => 'Entrevista com ex-alunos empreendedores', 'aluno' => 'João Pedro', 'categoria' => 'Empreendedorismo', 'turma' => '3º ano B', 'data' => 'Ontem, 15:30', 'status' => 'Em análise', 'ativo' => false],
    ['sigla' => 'LS', 'titulo' => 'Campeonato de xadrez entre turmas', 'aluno' => 'Laura Silva', 'categoria' => 'Jogos Escolares', 'turma' => '1º ano C', 'data' => '10 jun', 'status' => 'Aprovada', 'ativo' => false],
    ['sigla' => 'PH', 'titulo' => 'Mais espaços de leitura no intervalo', 'aluno' => 'Pedro Henrique', 'categoria' => 'Clube do Livro', 'turma' => '2º ano B', 'data' => '08 jun', 'status' => 'Recusada', 'ativo' => false]
];
?>

<!-- TOPO: TÍTULO DA PÁGINA -->
<div class="mb-6">
  <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
  <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Sugestões de pautas</h1>
  <p class="text-xs text-gray-500 mt-1">Analise ideias enviadas pela comunidade escolar.</p>
</div>

<!-- ABAS DE FILTROS IDÊNTICAS À IMAGEM 9 -->
<div class="flex gap-2 mb-4">
  <button class="admin-pill-tab active">Todas</button>
  <button class="admin-pill-tab">Novas</button>
  <button class="admin-pill-tab">Em análise</button>
</div>

<!-- GRADE MASTER-DETAIL (LISTA À ESQUERDA + DETALHE À DIREITA) IDÊNTICO À IMAGEM 9 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
  
  <!-- Lista de Pautas (5 Colunas) -->
  <div class="lg:col-span-5 space-y-2">
    <?php foreach ($pautas_lista as $p): ?>
      <div class="admin-card p-3.5 cursor-pointer transition-colors <?= $p['ativo'] ? 'border-l-4 border-l-[#c0262d] bg-[#fbfbf9]' : 'hover:bg-white' ?>">
        <div class="flex items-start gap-3">
          <div class="w-8 h-8 rounded-full bg-[#f3f4f6] text-gray-600 font-bold text-xs flex items-center justify-center flex-shrink-0">
            <?= $p['sigla'] ?>
          </div>
          <div class="flex-1">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-bold text-gray-900 leading-snug"><?= e($p['titulo']) ?></h4>
              <span class="text-[9px] text-gray-400"><?= e($p['data']) ?></span>
            </div>
            <div class="text-[10px] text-gray-500 mt-0.5"><?= e($p['aluno']) ?> • <?= e($p['categoria']) ?></div>
            
            <div class="mt-2">
              <span class="text-[9px] px-2 py-0.5 rounded font-semibold <?= $p['status'] === 'Nova' ? 'bg-sky-50 text-sky-700' : ($p['status'] === 'Em análise' ? 'bg-amber-50 text-amber-700' : ($p['status'] === 'Aprovada' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700')) ?>">
                <?= e($p['status']) ?>
              </span>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Detalhe da Pauta Selecionada (7 Colunas) Idêntico à Imagem 9 -->
  <div class="lg:col-span-7 admin-card p-7 space-y-6">
    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
      <span class="text-[9px] px-2 py-0.5 rounded bg-sky-50 text-sky-700 font-semibold">Nova</span>
      <button class="text-gray-400 hover:text-black">•••</button>
    </div>

    <div>
      <h2 class="text-2xl font-editorial-heading font-bold text-gray-900 leading-tight">
        Uma horta comunitária para nossa escola
      </h2>
      <div class="text-[11px] text-gray-400 mt-1">Enviado por <strong>Mariana Alves</strong> • Hoje, 09:14</div>
    </div>

    <!-- Tabela Cinza de Metadados da Pauta -->
    <div class="bg-[#fafaf8] border border-gray-200 rounded p-3 grid grid-cols-3 gap-2 text-xs">
      <div>
        <span class="text-[9.5px] text-gray-400 block uppercase font-bold">ESTUDANTE</span>
        <span class="font-bold text-gray-800">Mariana Alves</span>
      </div>
      <div>
        <span class="text-[9.5px] text-gray-400 block uppercase font-bold">CATEGORIA</span>
        <span class="font-bold text-gray-800">Meio ambiente</span>
      </div>
      <div>
        <span class="text-[9.5px] text-gray-400 block uppercase font-bold">TURMA</span>
        <span class="font-bold text-gray-800">2º ano A</span>
      </div>
    </div>

    <div class="space-y-4 text-xs text-gray-700 font-sans leading-relaxed">
      <div>
        <h4 class="font-bold text-gray-900 text-sm mb-1">Descrição da sugestão</h4>
        <p>Acho que seria interessante produzir uma reportagem mostrando como uma horta comunitária pode envolver estudantes de diferentes turmas. Podemos entrevistar professores de Ciências e pesquisar exemplos de outras escolas.</p>
      </div>

      <div>
        <h4 class="font-bold text-gray-900 text-sm mb-1">Por que essa pauta é importante?</h4>
        <p>Além de melhorar o espaço da escola, o projeto pode ensinar sobre alimentação saudável e sustentabilidade.</p>
      </div>
    </div>

    <!-- Ações Finais: Recusar, Marcar em análise e Botão Vermelho "Transformar em reportagem" -->
    <div class="pt-6 border-t border-gray-100 flex items-center justify-end gap-3 text-xs">
      <button class="border border-gray-200 px-4 py-2 rounded text-gray-600 hover:bg-gray-50">Recusar</button>
      <button class="border border-gray-200 px-4 py-2 rounded text-gray-600 hover:bg-gray-50">Marcar em análise</button>
      <a href="<?= BASE_URL ?>/admin/editor.php" class="btn-admin-red py-2 px-4">
        <span>✎</span> Transformar em reportagem
      </a>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
