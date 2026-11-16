<?php
/**
 * O FAROL - Gestão da Página Inicial Fiel à Imagem 7
 * Lista de Módulos Arrastáveis com Toggles (Switch) + Live Preview do Jornal
 */
declare(strict_types=1);
$admin_title = "Página inicial";
$admin_breadcrumb = "Página inicial";
require_once __DIR__ . '/header.php';

// Módulos exatos da imagem 7
$modulos = [
    ['nome' => 'Cabeçalho institucional', 'desc' => 'Logo, menu e pesquisa', 'ativo' => true],
    ['nome' => 'Manchete principal', 'desc' => 'Jovens cientistas apresentam soluções...', 'ativo' => true, 'destaque' => true],
    ['nome' => 'Notícias em destaque', 'desc' => 'Conteúdo automático da editoria', 'ativo' => true],
    ['nome' => 'Ciência e Prática Técnica', 'desc' => 'Conteúdo automático da editoria', 'ativo' => true],
    ['nome' => 'Jogos Escolares', 'desc' => 'Conteúdo automático da editoria', 'ativo' => true],
    ['nome' => 'Cultura e Empreendedorismo', 'desc' => 'Conteúdo automático da editoria', 'ativo' => true],
    ['nome' => 'Clube do Livro', 'desc' => 'Conteúdo automático da editoria', 'ativo' => true],
    ['nome' => 'Processos Seletivos', 'desc' => 'Conteúdo automático da editoria', 'ativo' => false],
    ['nome' => 'Enquetes', 'desc' => 'Enquete da semana', 'ativo' => true]
];
?>

<!-- TOPO: TÍTULO + PRÉ-VISUALIZAR + PUBLICAR ALTERAÇÕES -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Página inicial</h1>
    <p class="text-xs text-gray-500 mt-1">Defina a composição e a ordem dos módulos do jornal.</p>
  </div>
  <div class="flex items-center gap-2">
    <a href="<?= BASE_URL ?>/index.php" target="_blank" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      Pré-visualizar
    </a>
    <button class="btn-admin-red">
      Publicar alterações
    </button>
  </div>
</div>

<!-- GRADE COM ESTRUTURA DA PÁGINA (ESQ) + PRÉ-VISUALIZAÇÃO (DIR) IDÊNTICO À IMAGEM 7 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- Lista de Módulos com Toggles (5 Colunas) -->
  <div class="lg:col-span-5 admin-card p-5 space-y-3">
    <div class="pb-2 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h3 class="text-xs font-bold text-gray-900">Estrutura da página</h3>
        <p class="text-[10px] text-gray-400">Arraste os módulos para reordenar</p>
      </div>
      <span class="text-[10px] text-emerald-600 flex items-center gap-1">● Alterações salvas</span>
    </div>

    <div class="space-y-2">
      <?php foreach ($modulos as $mod): ?>
        <div class="p-3 rounded-lg border flex items-center justify-between text-xs transition-colors <?= !empty($mod['destaque']) ? 'bg-[#fff5f5] border-[#fecaca]' : 'bg-[#fafaf8] border-gray-200' ?>">
          <div class="flex items-center gap-2.5">
            <span class="text-gray-400 cursor-move">≡</span>
            <div>
              <div class="font-bold text-gray-900"><?= e($mod['nome']) ?></div>
              <div class="text-[9.5px] text-gray-400 truncate max-w-[180px]"><?= e($mod['desc']) ?></div>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <!-- Toggle Switch -->
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" <?= $mod['ativo'] ? 'checked' : '' ?> class="sr-only peer">
              <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:bg-[#0e2f33]"></div>
            </label>
            <button class="text-gray-400 hover:text-black text-xs">⚙</button>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Pré-Visualização Simulada (7 Colunas) Idêntica à Imagem 7 -->
  <div class="lg:col-span-7 admin-card p-5">
    <div class="flex items-center justify-between pb-2 mb-3 border-b border-gray-100 text-[10px] text-gray-400">
      <span>PRÉ-VISUALIZAÇÃO</span>
      <span>1440 × 900</span>
    </div>

    <div class="border border-gray-200 rounded overflow-hidden bg-white shadow-sm">
      
      <!-- Mini Cabeçalho -->
      <div class="p-3 border-b border-gray-100 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-5 h-5 rounded bg-[#c0262d] text-white font-serif font-black text-[10px] flex items-center justify-center">F</div>
          <div>
            <div class="font-serif font-bold text-xs text-gray-900 leading-none">O FAROL</div>
            <div class="text-[7.5px] text-gray-400 font-semibold">JORNAL ESCOLAR</div>
          </div>
        </div>
        <div class="flex items-center gap-2 text-[9px] text-gray-500">
          <span>Notícias</span>
          <span>Ciência</span>
          <span>Cultura</span>
          <span>Esportes</span>
        </div>
      </div>

      <!-- Hero com Alunos e Bloco Verde Escuro -->
      <div class="grid grid-cols-12 bg-white">
        <div class="col-span-7">
          <img src="<?= BASE_URL ?>/assets/images/ciencia-lab.jpg" class="w-full h-48 object-cover">
        </div>
        <div class="col-span-5 bg-[#0e2f33] p-4 text-white flex flex-col justify-between">
          <div>
            <span class="text-[8px] font-bold text-amber-400 uppercase tracking-wider block mb-1">CLUBE DE CIÊNCIAS</span>
            <h4 class="text-sm font-editorial-heading font-bold leading-tight">
              Jovens cientistas apresentam soluções para uma escola mais sustentável
            </h4>
            <p class="text-[9px] text-gray-300 mt-2 line-clamp-2">
              Projetos desenvolvidos pelos estudantes unem criatividade, pesquisa e compromisso.
            </p>
          </div>
        </div>
      </div>

      <!-- Destaques da Semana no Mini Preview -->
      <div class="p-4 bg-gray-50 border-t border-gray-100">
        <span class="text-[9px] font-bold text-gray-700 uppercase tracking-wider block mb-2">DESTAQUES DA SEMANA</span>
        <div class="grid grid-cols-3 gap-2 text-[8.5px]">
          <div>
            <img src="<?= BASE_URL ?>/assets/images/hero-premiacao.jpg" class="w-full h-14 object-cover rounded mb-1">
            <span class="font-bold text-gray-800 line-clamp-1">Jogos Escolares reúnem mais de 240 estudantes</span>
          </div>
          <div>
            <img src="<?= BASE_URL ?>/assets/images/flagrante-semana.jpg" class="w-full h-14 object-cover rounded mb-1">
            <span class="font-bold text-gray-800 line-clamp-1">Clube do Livro debate literatura brasileira</span>
          </div>
          <div>
            <img src="<?= BASE_URL ?>/assets/images/livro-torto-arado.jpg" class="w-full h-14 object-cover rounded mb-1">
            <span class="font-bold text-gray-800 line-clamp-1">Feira Cultural celebra saberes</span>
          </div>
        </div>
      </div>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
