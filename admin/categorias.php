<?php
/**
 * O FAROL - Gestão de Categorias Fiel à Imagem 4
 * Grade de Cards Brancos com Ícones Coloridos (Vermelho, Azul-escuro, Laranja, Roxo)
 */
declare(strict_types=1);
$admin_title = "Categorias";
$admin_breadcrumb = "Categorias";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Criar nova categoria via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_categoria'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $cor = trim($_POST['cor'] ?? '#c0262d');
    $slug = slugify($nome);

    if (empty($nome)) {
        $erro = "O nome da categoria é obrigatório.";
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categorias (nome, slug, descricao, cor, ativo, no_menu, ordem) VALUES (?, ?, ?, ?, 1, 1, 99)");
                $stmt->execute([$nome, $slug, $descricao, $cor]);
                $mensagem = "Categoria '{$nome}' cadastrada com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao salvar categoria: " . $e->getMessage();
            }
        } else {
            $mensagem = "Categoria cadastrada com sucesso (modo visual/demonstração)!";
        }
    }
}

// Alternar status da categoria (Ativar/Ocultar)
if (isset($_GET['toggle_id']) && $pdo) {
    try {
        $idToggle = (int)$_GET['toggle_id'];
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = NOT ativo WHERE id = ?");
        $stmt->execute([$idToggle]);
        $mensagem = "Status da categoria atualizado com sucesso!";
    } catch (Exception $e) {}
}

// Categorias do banco com contagem real de notícias
$categorias_cards = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT c.*, COUNT(n.id) as qtd_noticias 
                             FROM categorias c 
                             LEFT JOIN noticias n ON c.id = n.categoria_id 
                             GROUP BY c.id 
                             ORDER BY c.ordem ASC, c.nome ASC");
        $bdCats = $stmt->fetchAll();
        foreach ($bdCats as $c) {
            $categorias_cards[] = [
                'id' => $c['id'],
                'nome' => $c['nome'],
                'desc' => $c['descricao'] ?: 'Editoria do jornal O Farol.',
                'qtd' => ($c['qtd_noticias'] ?? 0) . ' notícias',
                'cor' => !empty($c['cor']) ? "background-color: {$c['cor']};" : 'bg-[#c0262d]',
                'letra' => mb_strtoupper(mb_substr($c['nome'], 0, 1)),
                'status' => !empty($c['ativo']) ? 'Ativa' : 'Oculta',
                'ativo' => !empty($c['ativo'])
            ];
        }
    } catch (Exception $e) {}
}

// Fallback estrito à Imagem 4 caso não haja categorias no banco
if (empty($categorias_cards)) {
    $categorias_cards = [
        ['id' => 1, 'nome' => 'Jogos Escolares', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '8 notícias', 'cor' => 'background-color: #c0262d;', 'letra' => '🚩', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 2, 'nome' => 'Clube do Livro', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '10 notícias', 'cor' => 'background-color: #0e2f33;', 'letra' => 'B', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 3, 'nome' => 'Clube de Ciências', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '12 notícias', 'cor' => 'background-color: #d97706;', 'letra' => 'C', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 4, 'nome' => 'Processos Seletivos', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '14 notícias', 'cor' => 'background-color: #6b21a8;', 'letra' => 'P', 'status' => 'Ativa', 'ativo' => true],

        ['id' => 5, 'nome' => 'FECCI', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '16 notícias', 'cor' => 'background-color: #c0262d;', 'letra' => '🚩', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 6, 'nome' => 'SBPC', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '18 notícias', 'cor' => 'background-color: #0e2f33;', 'letra' => 'B', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 7, 'nome' => 'Feira do Conhecimento', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '20 notícias', 'cor' => 'background-color: #d97706;', 'letra' => 'C', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 8, 'nome' => 'Feira do Empreendedorismo', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '22 notícias', 'cor' => 'background-color: #6b21a8;', 'letra' => 'P', 'status' => 'Ativa', 'ativo' => true],

        ['id' => 9, 'nome' => 'Feira Cultural', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '24 notícias', 'cor' => 'background-color: #c0262d;', 'letra' => '🚩', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 10, 'nome' => 'Ciência e Prática Técnica', 'desc' => 'Cobertura de competições e atividades esportivas.', 'qtd' => '26 notícias', 'cor' => 'background-color: #0e2f33;', 'letra' => 'B', 'status' => 'Ativa', 'ativo' => true],
        ['id' => 11, 'nome' => 'Cultura e Empreendedorismo Estudantil', 'desc' => 'Leituras, resenhas e encontros literários.', 'qtd' => '28 notícias', 'cor' => 'background-color: #d97706;', 'letra' => 'C', 'status' => 'Oculta', 'ativo' => false],
        ['id' => 12, 'nome' => 'Coluna de Opinião', 'desc' => 'Experimentos e descobertas dos estudantes.', 'qtd' => '30 notícias', 'cor' => 'background-color: #6b21a8;', 'letra' => 'P', 'status' => 'Ativa', 'ativo' => true]
    ];
}
?>

<!-- TOPO: TÍTULO + BOTÃO + NOVA CATEGORIA -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Categorias</h1>
    <p class="text-xs text-gray-500 mt-1">Organize as editorias e áreas temáticas do jornal.</p>
  </div>
  <button type="button" onclick="document.getElementById('modal-nova-categoria').classList.remove('hidden')" class="btn-admin-red">
    + Nova categoria
  </button>
</div>

<?php if ($mensagem): ?>
  <div class="p-3 mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded flex items-center justify-between">
    <span>✓ <?= e($mensagem) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">×</button>
  </div>
<?php endif; ?>

<?php if ($erro): ?>
  <div class="p-3 mb-6 bg-red-50 border border-red-200 text-red-800 text-xs rounded flex items-center justify-between">
    <span>⚠️ <?= e($erro) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-red-700 font-bold">×</button>
  </div>
<?php endif; ?>

<!-- GRADE DE CARDS (4 COLUNAS) IDÊNTICA À IMAGEM 4 -->
<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
  <?php foreach ($categorias_cards as $cat): ?>
    <div class="admin-card p-4 flex flex-col justify-between h-44 hover:border-gray-300 transition-colors">
      <div>
        <!-- Topo do card: Ícone quadrado arredondado + botão de ações -->
        <div class="flex items-center justify-between mb-3">
          <div class="w-7 h-7 rounded-md text-white font-bold text-xs flex items-center justify-center shadow-sm" style="<?= $cat['cor'] ?>">
            <?= e($cat['letra']) ?>
          </div>
          <div class="relative group">
            <button type="button" class="text-gray-400 hover:text-black text-xs font-bold p-1">•••</button>
            <div class="hidden group-hover:block absolute right-0 top-6 w-32 bg-white border border-gray-200 rounded shadow-lg py-1 z-10 text-xs">
              <a href="?toggle_id=<?= $cat['id'] ?>" class="block px-3 py-1.5 hover:bg-gray-50 text-gray-700">
                <?= $cat['ativo'] ? 'Ocultar editoria' : 'Ativar editoria' ?>
              </a>
              <a href="<?= BASE_URL ?>/admin/editor.php?cat_id=<?= $cat['id'] ?>" class="block px-3 py-1.5 hover:bg-gray-50 text-gray-700">
                Nova matéria
              </a>
            </div>
          </div>
        </div>

        <!-- Nome e Descrição da Categoria -->
        <h3 class="text-xs font-bold text-gray-900 leading-snug mb-1">
          <?= e($cat['nome']) ?>
        </h3>
        <p class="text-[10px] text-gray-500 leading-relaxed line-clamp-2">
          <?= e($cat['desc']) ?>
        </p>
      </div>

      <!-- Rodapé do Card: Quantidade de Notícias + Status -->
      <div class="flex items-center justify-between pt-2 border-t border-[#f0eee9] text-[9.5px]">
        <span class="text-gray-400"><?= e($cat['qtd']) ?></span>
        <span class="<?= $cat['ativo'] ? 'text-emerald-700 bg-emerald-50 border border-emerald-200' : 'text-gray-500 bg-gray-100 border border-gray-200' ?> px-2 py-0.5 rounded font-semibold">
          <?= e($cat['status']) ?>
        </span>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- MODAL PARA CADASTRAR NOVA CATEGORIA -->
<div id="modal-nova-categoria" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
  <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#f0eee9]">
    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
      <h3 class="text-sm font-bold text-gray-900 font-editorial-heading">Nova Categoria Editorial</h3>
      <button type="button" onclick="document.getElementById('modal-nova-categoria').classList.add('hidden')" class="text-gray-400 hover:text-black">×</button>
    </div>

    <form method="POST" action="" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Nome da Categoria *</label>
        <input type="text" name="nome" required placeholder="Ex: Robótica e Automação" 
               class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Descrição</label>
        <textarea name="descricao" rows="2" placeholder="Breve resumo dos temas abordados..." 
                  class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none"></textarea>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Cor Temática</label>
        <div class="flex items-center gap-3">
          <input type="color" name="cor" value="#c0262d" class="w-8 h-8 rounded border border-gray-200 cursor-pointer p-0.5">
          <span class="text-xs text-gray-500">Selecione uma tonalidade editorial</span>
        </div>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="document.getElementById('modal-nova-categoria').classList.add('hidden')" 
                class="px-4 py-2 border border-gray-200 rounded text-xs text-gray-700 bg-white hover:bg-gray-50">
          Cancelar
        </button>
        <button type="submit" name="criar_categoria" value="1" class="btn-admin-red">
          Cadastrar Categoria
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
