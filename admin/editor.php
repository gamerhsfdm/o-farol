<?php
/**
 * O FAROL - Editor de Notícias Fiel à Imagem 3
 * Layout Documental Limpo (Estilo Medium/Substack) + Barra Lateral Direita de Configurações e SEO
 */
declare(strict_types=1);
$admin_title = "Editor de notícias";
$admin_breadcrumb = "Nova reportagem";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$id = (int)($_GET['id'] ?? 0);

// Dados padrão idênticos à imagem 3
$materia = [
    'categoria' => 'CLUBE DE CIÊNCIAS',
    'titulo' => 'Jovens cientistas apresentam soluções para uma escola mais sustentável',
    'subtitulo' => 'Projetos desenvolvidos pelos estudantes unem criatividade, pesquisa e compromisso com o futuro da comunidade escolar.',
    'autor' => 'Ana Luiza',
    'tempo_leitura' => '6 min de leitura',
    'orientador' => 'Prof. Ricardo Nunes',
    'capa' => 'assets/images/ciencia-lab.jpg'
];
?>

<!-- TOPO DO EDITOR: AÇÕES DE SALVAMENTO IDÊNTICAS À IMAGEM 3 -->
<div class="flex items-center justify-between pb-4 mb-4 border-b border-[#f0eee9]">
  <div class="flex items-center gap-3">
    <a href="<?= BASE_URL ?>/admin/noticias.php" class="text-xs text-gray-500 hover:text-black flex items-center gap-1">
      ‹ Notícias
    </a>
    <span class="text-[11px] text-gray-400">● Salvo automaticamente às 10:42</span>
  </div>

  <div class="flex items-center gap-2">
    <button type="button" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      Visualizar
    </button>
    <button type="button" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
      Salvar rascunho
    </button>
    <button type="button" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
      Enviar para revisão
    </button>
    <button type="button" class="btn-admin-red">
      Publicar ›
    </button>
  </div>
</div>

<!-- GRADE DOCUMENTO CENTRAL + SIDEBAR DE METADADOS -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- FOLHA DOCUMENTAL CENTRAL (8 Colunas) -->
  <div class="lg:col-span-8 bg-white p-10 rounded-lg border border-[#f0eee9] shadow-sm min-h-[750px]">
    
    <span class="text-[9.5px] font-bold text-[#c0262d] uppercase tracking-wider block mb-3">
      <?= e($materia['categoria']) ?>
    </span>

    <!-- Título em Playfair Display -->
    <h1 class="text-3xl sm:text-4xl font-editorial-heading font-bold text-[#111827] leading-tight mb-4">
      <?= e($materia['titulo']) ?>
    </h1>

    <!-- Subtítulo -->
    <p class="text-base text-gray-500 font-sans leading-relaxed mb-6">
      <?= e($materia['subtitulo']) ?>
    </p>

    <!-- Assinatura do Autor -->
    <div class="flex items-center gap-3 pb-6 border-b border-gray-100 mb-6 text-xs text-gray-500">
      <div class="w-6 h-6 rounded-full bg-[#f3f4f6] text-gray-700 font-bold text-[9px] flex items-center justify-center">
        AL
      </div>
      <span>Por <strong><?= e($materia['autor']) ?></strong></span>
      <span>•</span>
      <span><?= e($materia['tempo_leitura']) ?></span>
    </div>

    <!-- Barra de Ferramentas Flutuante Idêntica à Imagem 3 -->
    <div class="inline-flex items-center gap-3 px-3 py-1.5 bg-[#fafaf8] border border-gray-200 rounded-lg text-xs text-gray-700 mb-6 shadow-sm">
      <span class="font-medium cursor-pointer">Parágrafo ⌄</span>
      <span class="text-gray-300">|</span>
      <button class="font-bold hover:text-black">B</button>
      <button class="italic hover:text-black font-serif">I</button>
      <button class="hover:text-black">≡</button>
      <button class="hover:text-black">🔗</button>
      <button class="hover:text-black">🖼</button>
      <button class="hover:text-black">•••</button>
    </div>

    <!-- Corpo de Texto da Matéria -->
    <div class="text-sm text-gray-800 leading-relaxed space-y-4 font-sans text-justify">
      <p>
        Na última quarta-feira, o pátio da escola se transformou em um verdadeiro laboratório de ideias. Estudantes do Clube de Ciências apresentaram projetos que buscam soluções simples e criativas para desafios do cotidiano escolar.
      </p>

      <h2 class="text-xl font-editorial-heading font-bold text-[#111827] pt-4">
        Ciência que transforma o cotidiano
      </h2>

      <p>
        Entre as iniciativas, destacaram-se um sistema de captação de água da chuva, uma horta irrigada automaticamente e novas propostas para reduzir o desperdício de materiais.
      </p>

      <div class="border-l-2 border-[#c0262d] pl-4 py-2 my-4 bg-gray-50 text-xs italic text-gray-700 font-editorial-heading">
        “A ciência faz mais sentido quando conseguimos perceber como ela pode melhorar a vida das pessoas ao nosso redor.”
      </div>
    </div>

  </div>

  <!-- SIDEBAR DIREITA DE CONFIGURAÇÕES E SEO (4 Colunas) IDÊNTICA À IMAGEM 3 -->
  <div class="lg:col-span-4 bg-white p-6 rounded-lg border border-[#f0eee9] shadow-sm space-y-5">
    
    <!-- Abas: Configurações | SEO -->
    <div class="flex items-center border-b border-gray-200 text-xs font-semibold pb-1">
      <button class="pb-2 border-b-2 border-[#c0262d] text-[#c0262d] px-3">Configurações</button>
      <button class="pb-2 text-gray-400 hover:text-gray-700 px-3">SEO</button>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
      <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded">Rascunho</span>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Categoria</label>
      <select class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] outline-none">
        <option>Clube de Ciências</option>
        <option>Jogos Escolares</option>
        <option>Cultura e Arte</option>
      </select>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Autor</label>
      <div class="flex items-center justify-between p-2 border border-gray-200 rounded bg-[#fafaf8] text-xs">
        <div class="flex items-center gap-2">
          <div class="w-5 h-5 rounded-full bg-gray-200 text-[9px] flex items-center justify-center font-bold">AL</div>
          <span>Ana Luiza</span>
        </div>
        <span class="text-gray-400">⌄</span>
      </div>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Professor orientador</label>
      <select class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] outline-none">
        <option>Prof. Ricardo Nunes</option>
        <option>Prof. Dr. Hamilton Queiroz</option>
      </select>
    </div>

    <!-- Imagem de Capa com Botão de Alteração -->
    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Imagem de capa</label>
      <div class="relative rounded overflow-hidden border border-gray-200">
        <img src="<?= BASE_URL ?>/assets/images/ciencia-lab.jpg" class="w-full h-32 object-cover">
        <button class="absolute inset-x-4 bottom-3 bg-white/90 hover:bg-white text-gray-800 text-[11px] font-semibold py-1 rounded shadow-sm text-center">
          Alterar imagem
        </button>
      </div>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Tags</label>
      <div class="flex flex-wrap gap-1.5 p-2 border border-gray-200 rounded bg-[#fafaf8] text-xs">
        <span class="bg-white border border-gray-200 px-2 py-0.5 rounded text-[10.5px]">sustentabilidade ×</span>
        <span class="bg-white border border-gray-200 px-2 py-0.5 rounded text-[10.5px]">ciência ×</span>
        <span class="text-gray-400 text-[11px] self-center">Adicionar...</span>
      </div>
    </div>

    <div>
      <label class="block text-[11px] font-medium text-gray-500 mb-1">Slug</label>
      <input type="text" value="jovens-cientistas-solucoes..." class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] text-gray-500">
    </div>

    <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
      <span>Notícias relacionadas</span>
      <button class="text-[#c0262d] font-bold text-sm">+</button>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
