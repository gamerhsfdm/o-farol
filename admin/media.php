<?php
/**
 * O FAROL - Biblioteca de Mídia Fiel à Imagem 6
 * Grade de Fotografias + Painel Lateral de Detalhes do Arquivo (Alt, Legenda, Crédito, Dimensões)
 */
declare(strict_types=1);
$admin_title = "Biblioteca de mídia";
$admin_breadcrumb = "Biblioteca de mídia";
require_once __DIR__ . '/header.php';

// Imagens para o acervo
$fotos = [
    'assets/images/ciencia-lab.jpg',
    'assets/images/hero-premiacao.jpg',
    'assets/images/flagrante-semana.jpg',
    'assets/images/livro-torto-arado.jpg',
    'assets/images/ciencia-lab.jpg',
    'assets/images/hero-premiacao.jpg',
    'assets/images/flagrante-semana.jpg',
    'assets/images/ciencia-lab.jpg',
    'assets/images/hero-premiacao.jpg',
    'assets/images/flagrante-semana.jpg',
    'assets/images/ciencia-lab.jpg',
    'assets/images/hero-premiacao.jpg'
];
?>

<!-- TOPO: TÍTULO + BOTÃO ENVIAR ARQUIVOS -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Biblioteca de mídia</h1>
    <p class="text-xs text-gray-500 mt-1">Organize imagens, capas e arquivos editoriais.</p>
  </div>
  <button class="btn-admin-red">
    + Enviar arquivos
  </button>
</div>

<!-- BARRA DE BUSCA E FILTROS IDÊNTICA À IMAGEM 6 -->
<div class="admin-card p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
  <div class="relative flex-1 min-w-[280px]">
    <input type="text" placeholder="Pesquisar arquivos..." 
           class="w-full pl-8 pr-3 py-1.5 text-xs bg-transparent border-0 focus:outline-none">
    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
  </div>
  
  <div class="flex items-center gap-2 text-xs">
    <select class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 outline-none">
      <option>Todos os tipos</option>
      <option>Imagens JPG/PNG</option>
      <option>Documentos PDF</option>
    </select>

    <select class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 outline-none">
      <option>Junho de 2026</option>
      <option>Maio de 2026</option>
    </select>

    <div class="flex items-center border border-gray-200 rounded overflow-hidden">
      <button class="p-1.5 bg-[#0e2f33] text-white">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
      </button>
      <button class="p-1.5 text-gray-400 hover:text-black">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
    </div>
  </div>
</div>

<!-- GRADE DE IMAGENS (ESQUERDA) + PAINEL DE DETALHES (DIREITA) IDÊNTICO À IMAGEM 6 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
  
  <!-- Grade de Thumbnails (8 Colunas) -->
  <div class="lg:col-span-8 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
    <?php foreach ($fotos as $idx => $f): ?>
      <div class="admin-card p-1 cursor-pointer transition-all <?= $idx === 0 ? 'ring-2 ring-[#c0262d]' : 'hover:border-gray-400' ?>">
        <div class="relative">
          <img src="<?= BASE_URL ?>/<?= $f ?>" class="w-full h-24 object-cover rounded">
          <?php if ($idx === 0): ?>
            <div class="absolute top-1 right-1 w-4 h-4 rounded-full bg-[#c0262d] text-white flex items-center justify-center text-[9px] font-bold">✓</div>
          <?php endif; ?>
        </div>
        <span class="text-[9.5px] text-gray-500 block truncate mt-1 px-1">farol_ciencia_0<?= $idx+1 ?>.jpg</span>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Painel de Detalhes do Arquivo (4 Colunas) Idêntico à Imagem 6 -->
  <div class="lg:col-span-4 admin-card p-5 space-y-4">
    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
      <h3 class="text-xs font-bold text-gray-900">Detalhes do arquivo</h3>
      <button class="text-gray-400 hover:text-black text-sm">&times;</button>
    </div>

    <!-- Preview da Foto Selecionada -->
    <div class="rounded overflow-hidden border border-gray-200">
      <img src="<?= BASE_URL ?>/assets/images/ciencia-lab.jpg" class="w-full h-36 object-cover">
    </div>

    <div>
      <div class="text-xs font-bold text-gray-900">farol_ciencia_01.jpg</div>
      <div class="text-[10px] text-gray-400">Imagem JPEG • 1,8 MB</div>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Texto alternativo</label>
      <input type="text" value="Estudantes apresentam projeto na feira" class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8]">
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Legenda</label>
      <textarea rows="2" class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8]">Clube de Ciências apresenta soluções sustentáveis.</textarea>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Crédito</label>
      <input type="text" value="Redação O Farol" class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8]">
    </div>

    <!-- Metadados Técnicos no Rodapé -->
    <div class="pt-3 border-t border-gray-100 text-[10.5px] space-y-1 text-gray-500">
      <div class="flex justify-between"><span>Dimensões:</span> <span class="font-mono text-gray-800">2400 × 1600 px</span></div>
      <div class="flex justify-between"><span>Enviado em:</span> <span class="text-gray-800">12 jun 2026</span></div>
      <div class="flex justify-between"><span>Enviado por:</span> <span class="text-gray-800">Marina Costa</span></div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
