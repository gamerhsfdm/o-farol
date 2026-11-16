<?php
/**
 * O FAROL - Gestão de Categorias Fiel à Imagem 4
 * Grade de Cards Brancos com Ícones Coloridos (Vermelho, Azul-escuro, Laranja, Roxo)
 */
declare(strict_types=1);
$admin_title = "Categorias";
$admin_breadcrumb = "Categorias";
require_once __DIR__ . '/header.php';

// Categorias e ícones exatos da imagem 4
$categorias_cards = [
    ['nome' => 'Jogos Escolares', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '8 notícias', 'cor' => 'bg-[#c0262d]', 'letra' => '🚩', 'status' => 'Ativa'],
    ['nome' => 'Clube do Livro', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '10 notícias', 'cor' => 'bg-[#0e2f33]', 'letra' => 'B', 'status' => 'Ativa'],
    ['nome' => 'Clube de Ciências', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '12 notícias', 'cor' => 'bg-[#d97706]', 'letra' => 'C', 'status' => 'Ativa'],
    ['nome' => 'Processos Seletivos', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '14 notícias', 'cor' => 'bg-[#6b21a8]', 'letra' => 'P', 'status' => 'Ativa'],

    ['nome' => 'FECCI', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '16 notícias', 'cor' => 'bg-[#c0262d]', 'letra' => '🚩', 'status' => 'Ativa'],
    ['nome' => 'SBPC', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '18 notícias', 'cor' => 'bg-[#0e2f33]', 'letra' => 'B', 'status' => 'Ativa'],
    ['nome' => 'Feira do Conhecimento', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '20 notícias', 'cor' => 'bg-[#d97706]', 'letra' => 'C', 'status' => 'Ativa'],
    ['nome' => 'Feira do Empreendedorismo', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '22 notícias', 'cor' => 'bg-[#6b21a8]', 'letra' => 'P', 'status' => 'Ativa'],

    ['nome' => 'Feira Cultural', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '24 notícias', 'cor' => 'bg-[#c0262d]', 'letra' => '🚩', 'status' => 'Ativa'],
    ['nome' => 'Ciência e Prática Técnica', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '26 notícias', 'cor' => 'bg-[#0e2f33]', 'letra' => 'B', 'status' => 'Ativa'],
    ['nome' => 'Cultura e Empreendedorismo Estudantil', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '28 notícias', 'cor' => 'bg-[#d97706]', 'letra' => 'C', 'status' => 'Oculta'],
    ['nome' => 'Coluna de Opinião', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '30 notícias', 'cor' => 'bg-[#6b21a8]', 'letra' => 'P', 'status' => 'Ativa']
];
?>

<!-- TOPO: TÍTULO + BOTÃO + NOVA CATEGORIA -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Categorias</h1>
    <p class="text-xs text-gray-500 mt-1">Organize as editorias e áreas temáticas do jornal.</p>
  </div>
  <button class="btn-admin-red">
    + Nova categoria
  </button>
</div>

<!-- GRADE DE CARDS (4 COLUNAS) IDÊNTICA À IMAGEM 4 -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
  <?php foreach ($categorias_cards as $cat): ?>
    <div class="admin-card p-4 flex flex-col justify-between h-44 hover:border-gray-300 transition-colors">
      <div>
        <!-- Topo do card: Ícone quadrado arredondado + botão de ações -->
        <div class="flex items-center justify-between mb-3">
          <div class="w-7 h-7 rounded-md <?= $cat['cor'] ?> text-white font-bold text-xs flex items-center justify-center shadow-sm">
            <?= $cat['letra'] ?>
          </div>
          <button class="text-gray-400 hover:text-black">•••</button>
        </div>

        <h3 class="text-xs font-bold text-gray-900 leading-snug mb-1"><?= e($cat['nome']) ?></h3>
        <p class="text-[10.5px] text-gray-400 leading-tight line-clamp-2"><?= e($cat['desc']) ?></p>
      </div>

      <!-- Rodapé do Card: Quantidade de notícias + Status Ativa/Oculta -->
      <div class="flex items-center justify-between pt-3 border-t border-gray-100 text-[10.5px]">
        <span class="text-gray-400 font-medium"><?= e($cat['qtd']) ?></span>
        <span class="text-[9.5px] px-2 py-0.5 rounded <?= $cat['status'] === 'Ativa' ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
          <?= e($cat['status']) ?>
        </span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
