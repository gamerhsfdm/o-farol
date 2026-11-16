<?php
/**
 * O FAROL - Autores e Equipe Editorial Fiel à Imagem 5
 * Grade de Cards com Fotos de Alunos/Jornais + Nome, Cargo, Qtd de Matérias e Link 'Ver perfil >'
 */
declare(strict_types=1);
$admin_title = "Autores e equipe";
$admin_breadcrumb = "Autores e equipe editorial";
require_once __DIR__ . '/header.php';

// Membros exatos da imagem 5
$autores_cards = [
    ['nome' => 'Ana Luiza', 'cargo' => 'Repórter • 2º ano A', 'qtd' => '18 reportagens publicadas', 'foto' => 'assets/images/ciencia-lab.jpg'],
    ['nome' => 'Lucas Mendes', 'cargo' => 'Editor de esportes • 3º ano B', 'qtd' => '24 reportagens publicadas', 'foto' => 'assets/images/hero-premiacao.jpg'],
    ['nome' => 'Beatriz Lima', 'cargo' => 'Colunista • 1º ano C', 'qtd' => '12 reportagens publicadas', 'foto' => 'assets/images/flagrante-semana.jpg'],
    ['nome' => 'Rafael Souza', 'cargo' => 'Fotógrafo • 2º ano B', 'qtd' => '31 reportagens publicadas', 'foto' => 'assets/images/ciencia-lab.jpg'],
    ['nome' => 'Camila Rocha', 'cargo' => 'Repórter • 3º ano A', 'qtd' => '15 reportagens publicadas', 'foto' => 'assets/images/hero-premiacao.jpg'],
    ['nome' => 'Prof. Ricardo Nunes', 'cargo' => 'Professor orientador • Ciências', 'qtd' => '28 reportagens publicadas', 'foto' => 'assets/images/flagrante-semana.jpg']
];
?>

<!-- TOPO: TÍTULO + BOTÃO CADASTRAR AUTOR -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Autores e equipe editorial</h1>
    <p class="text-xs text-gray-500 mt-1">Gerencie estudantes, orientadores e colaboradores.</p>
  </div>
  <button class="btn-admin-red">
    + Cadastrar autor
  </button>
</div>

<!-- BARRA DE BUSCA E FILTROS IDÊNTICA À IMAGEM 5 -->
<div class="admin-card p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
  <div class="relative flex-1 min-w-[280px]">
    <input type="text" placeholder="Buscar autor ou função..." 
           class="w-full pl-8 pr-3 py-1.5 text-xs bg-transparent border-0 focus:outline-none">
    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
  </div>
  
  <div class="flex items-center gap-2 text-xs">
    <select class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 outline-none">
      <option>Todas as funções</option>
      <option>Repórter</option>
      <option>Editor</option>
      <option>Colunista</option>
    </select>

    <select class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 outline-none">
      <option>Status: Ativos</option>
      <option>Inativos</option>
    </select>
  </div>
</div>

<!-- GRADE DE AUTORES (3 COLUNAS) IDÊNTICA À IMAGEM 5 -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
  <?php foreach ($autores_cards as $aut): ?>
    <div class="admin-card overflow-hidden flex flex-col justify-between hover:border-gray-300 transition-colors">
      <div class="flex">
        <!-- Foto lateral esquerda -->
        <img src="<?= BASE_URL ?>/<?= $aut['foto'] ?>" class="w-28 h-36 object-cover border-r border-[#f0eee9]">
        
        <!-- Conteúdo à direita -->
        <div class="p-3.5 flex-1 flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-1">
              <span class="text-[9px] px-1.5 py-0.2 rounded bg-emerald-50 text-emerald-700 font-semibold">Ativo</span>
              <button class="text-gray-400 hover:text-black">•••</button>
            </div>

            <h3 class="text-xs font-editorial-heading font-bold text-gray-900 leading-snug"><?= e($aut['nome']) ?></h3>
            <span class="text-[10px] text-gray-400 block mt-0.5"><?= e($aut['cargo']) ?></span>
          </div>

          <div class="pt-2 border-t border-gray-100">
            <span class="text-[9.5px] text-gray-400 block"><?= e($aut['qtd']) ?></span>
            <a href="#" class="text-[10px] font-bold text-[#c0262d] hover:underline block mt-1">Ver perfil ›</a>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
