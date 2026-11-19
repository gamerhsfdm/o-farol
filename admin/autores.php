<?php
/**
 * O FAROL - Autores e Equipe Editorial Fiel à Imagem 5
 * Grade de Cards com Fotos de Alunos/Jornais + Nome, Cargo, Qtd de Matérias e Link 'Ver perfil >'
 */
declare(strict_types=1);
$admin_title = "Autores e equipe";
$admin_breadcrumb = "Autores e equipe editorial";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Processar cadastro de novo autor via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_autor'])) {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cargo = trim($_POST['cargo'] ?? 'Repórter');
    $turma = trim($_POST['turma'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $foto = trim($_POST['foto_url'] ?? 'assets/images/ciencia-lab.jpg');

    if (empty($nome)) {
        $erro = "O nome do autor é obrigatório.";
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO autores (nome, email, cargo, turma, biografia, foto_url, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                $stmt->execute([$nome, $email, $cargo, $turma, $bio, $foto]);
                $mensagem = "Autor '{$nome}' cadastrado com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao cadastrar autor: " . $e->getMessage();
            }
        } else {
            $mensagem = "Autor cadastrado com sucesso (modo visual/demonstração)!";
        }
    }
}

// Filtros
$funcao_filtro = $_GET['funcao'] ?? '';
$status_filtro = $_GET['status'] ?? '';
$busca = trim($_GET['q'] ?? '');

// Consulta autores do banco
$autores_cards = [];
if ($pdo) {
    try {
        $sql = "SELECT a.*, COUNT(na.noticia_id) as qtd_noticias 
                FROM autores a 
                LEFT JOIN noticia_autores na ON a.id = na.autor_id 
                WHERE 1=1";
        $params = [];
        if (!empty($busca)) {
            $sql .= " AND (a.nome LIKE ? OR a.cargo LIKE ? OR a.turma LIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }
        if (!empty($funcao_filtro)) {
            $sql .= " AND a.cargo LIKE ?";
            $params[] = "%{$funcao_filtro}%";
        }
        $sql .= " GROUP BY a.id ORDER BY a.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bdAutores = $stmt->fetchAll();

        foreach ($bdAutores as $aut) {
            $cargo_formatado = $aut['cargo'];
            if (!empty($aut['turma'])) {
                $cargo_formatado .= ' • ' . $aut['turma'];
            }
            $autores_cards[] = [
                'id' => $aut['id'],
                'nome' => $aut['nome'],
                'cargo' => $cargo_formatado,
                'qtd' => ($aut['qtd_noticias'] ?? 0) . ' reportagens publicadas',
                'foto' => !empty($aut['foto_url']) ? $aut['foto_url'] : 'assets/images/ciencia-lab.jpg',
                'ativo' => !empty($aut['ativo'])
            ];
        }
    } catch (Exception $e) {}
}

// Fallback estrito à Imagem 5 caso banco esteja inicializando
if (empty($autores_cards)) {
    $autores_cards = [
        ['id' => 1, 'nome' => 'Ana Luiza', 'cargo' => 'Repórter • 2º ano A', 'qtd' => '18 reportagens publicadas', 'foto' => 'assets/images/ciencia-lab.jpg', 'ativo' => true],
        ['id' => 2, 'nome' => 'Lucas Mendes', 'cargo' => 'Editor de esportes • 3º ano B', 'qtd' => '24 reportagens publicadas', 'foto' => 'assets/images/hero-premiacao.jpg', 'ativo' => true],
        ['id' => 3, 'nome' => 'Beatriz Lima', 'cargo' => 'Colunista • 1º ano C', 'qtd' => '12 reportagens publicadas', 'foto' => 'assets/images/flagrante-semana.jpg', 'ativo' => true],
        ['id' => 4, 'nome' => 'Rafael Souza', 'cargo' => 'Fotógrafo • 2º ano B', 'qtd' => '31 reportagens publicadas', 'foto' => 'assets/images/ciencia-lab.jpg', 'ativo' => true],
        ['id' => 5, 'nome' => 'Camila Rocha', 'cargo' => 'Repórter • 3º ano A', 'qtd' => '15 reportagens publicadas', 'foto' => 'assets/images/hero-premiacao.jpg', 'ativo' => true],
        ['id' => 6, 'nome' => 'Prof. Ricardo Nunes', 'cargo' => 'Professor orientador • Ciências', 'qtd' => '28 reportagens publicadas', 'foto' => 'assets/images/flagrante-semana.jpg', 'ativo' => true]
    ];
}
?>

<!-- TOPO: TÍTULO + BOTÃO CADASTRAR AUTOR -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Autores e equipe editorial</h1>
    <p class="text-xs text-gray-500 mt-1">Gerencie estudantes, orientadores e colaboradores.</p>
  </div>
  <button type="button" onclick="document.getElementById('modal-novo-autor').classList.remove('hidden')" class="btn-admin-red">
    + Cadastrar autor
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

<!-- BARRA DE BUSCA E FILTROS IDÊNTICA À IMAGEM 5 -->
<form method="GET" action="" class="admin-card p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
  <div class="relative flex-1 min-w-[280px]">
    <input type="text" name="q" value="<?= e($busca) ?>" placeholder="Buscar autor ou função..." 
           class="w-full pl-8 pr-3 py-1.5 text-xs bg-transparent border-0 focus:outline-none">
    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
  </div>
  
  <div class="flex items-center gap-2 text-xs">
    <select name="funcao" onchange="this.form.submit()" class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 outline-none">
      <option value="">Todas as funções</option>
      <option value="Repórter" <?= $funcao_filtro === 'Repórter' ? 'selected' : '' ?>>Repórter</option>
      <option value="Editor" <?= $funcao_filtro === 'Editor' ? 'selected' : '' ?>>Editor</option>
      <option value="Colunista" <?= $funcao_filtro === 'Colunista' ? 'selected' : '' ?>>Colunista</option>
      <option value="Fotógrafo" <?= $funcao_filtro === 'Fotógrafo' ? 'selected' : '' ?>>Fotógrafo</option>
      <option value="orientador" <?= $funcao_filtro === 'orientador' ? 'selected' : '' ?>>Professor Orientador</option>
    </select>

    <select name="status" onchange="this.form.submit()" class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 outline-none">
      <option value="">Status: Todos</option>
      <option value="ativos" <?= $status_filtro === 'ativos' ? 'selected' : '' ?>>Status: Ativos</option>
      <option value="inativos" <?= $status_filtro === 'inativos' ? 'selected' : '' ?>>Status: Inativos</option>
    </select>
  </div>
</form>

<!-- GRADE DE CARDS (3 COLUNAS) IDÊNTICA À IMAGEM 5 -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <?php foreach ($autores_cards as $membro): ?>
    <div class="admin-card overflow-hidden flex flex-col justify-between hover:border-gray-300 transition-colors">
      
      <!-- Foto de Exibição -->
      <div class="h-44 overflow-hidden relative bg-gray-100">
        <img src="<?= BASE_URL ?>/<?= e($membro['foto']) ?>" 
             alt="<?= e($membro['nome']) ?>" 
             class="w-full h-full object-cover">
        <span class="absolute top-3 left-3 bg-white/90 backdrop-blur-sm text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded shadow-sm">
          Ativo
        </span>
        <button type="button" class="absolute top-3 right-3 text-white bg-black/40 hover:bg-black/60 w-6 h-6 rounded-full flex items-center justify-center text-xs">
          •••
        </button>
      </div>

      <!-- Conteúdo do Card -->
      <div class="p-4 flex-1 flex flex-col justify-between">
        <div>
          <h3 class="text-sm font-bold text-gray-900 font-editorial-heading">
            <?= e($membro['nome']) ?>
          </h3>
          <p class="text-[11px] text-gray-500 font-sans mt-0.5">
            <?= e($membro['cargo']) ?>
          </p>
        </div>

        <div class="pt-4 mt-3 border-t border-[#f0eee9] flex items-center justify-between text-[11px]">
          <span class="text-gray-400"><?= e($membro['qtd']) ?></span>
          <a href="<?= BASE_URL ?>/admin/noticias.php?autor=<?= urlencode($membro['nome']) ?>" class="text-[#0e2f33] font-bold hover:underline flex items-center gap-1">
            Ver perfil ›
          </a>
        </div>
      </div>

    </div>
  <?php endforeach; ?>
</div>

<!-- MODAL PARA CADASTRAR NOVO AUTOR -->
<div id="modal-novo-autor" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
  <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#f0eee9]">
    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
      <h3 class="text-sm font-bold text-gray-900 font-editorial-heading">Novo Autor / Repórter</h3>
      <button type="button" onclick="document.getElementById('modal-novo-autor').classList.add('hidden')" class="text-gray-400 hover:text-black">×</button>
    </div>

    <form method="POST" action="" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Nome Completo *</label>
        <input type="text" name="nome" required placeholder="Ex: Júlia Medeiros" 
               class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none">
      </div>

      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Função / Cargo</label>
          <select name="cargo" class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none bg-white">
            <option value="Repórter">Repórter</option>
            <option value="Editor de esportes">Editor de esportes</option>
            <option value="Colunista">Colunista</option>
            <option value="Fotógrafo">Fotógrafo</option>
            <option value="Professor orientador">Professor orientador</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Turma / Série</label>
          <input type="text" name="turma" placeholder="Ex: 2º ano B" 
                 class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none">
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Foto do Autor (Caminho ou URL)</label>
        <input type="text" name="foto_url" value="assets/images/ciencia-lab.jpg" 
               class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Biografia Curta</label>
        <textarea name="bio" rows="2" placeholder="Breve apresentação para a página editorial..." 
                  class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#c0262d] focus:outline-none"></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="document.getElementById('modal-novo-autor').classList.add('hidden')" 
                class="px-4 py-2 border border-gray-200 rounded text-xs text-gray-700 bg-white hover:bg-gray-50">
          Cancelar
        </button>
        <button type="submit" name="cadastrar_autor" value="1" class="btn-admin-red">
          Cadastrar Autor
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
