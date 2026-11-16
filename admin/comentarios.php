<?php
/**
 * O FAROL - Central de Comentários Fiel à Imagem 10
 * Estado Vazio Centralizado com Balão Vermelho e Botão de Configuração
 */
declare(strict_types=1);
$admin_title = "Comentários";
$admin_breadcrumb = "Comentários";
require_once __DIR__ . '/header.php';
?>

<!-- TOPO: TÍTULO + BOTÃO + CONFIGURAR MODERAÇÃO -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Comentários</h1>
    <p class="text-xs text-gray-500 mt-1">Modere as conversas publicadas nas reportagens.</p>
  </div>
  <button class="btn-admin-red">
    + Configurar moderação
  </button>
</div>

<!-- CARD CENTRAL VAZIO COM ÍCONE DE BALÃO IDÊNTICO À IMAGEM 10 -->
<div class="admin-card p-20 flex flex-col items-center justify-center text-center min-h-[460px]">
  
  <!-- Ícone Balão de Fala Vermelho com Fundo Suave -->
  <div class="w-14 h-14 rounded-full bg-[#fef2f2] flex items-center justify-center text-[#c0262d] mb-4">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
  </div>

  <h3 class="text-lg font-editorial-heading font-bold text-gray-900 mb-1">
    Central de comentários
  </h3>

  <p class="text-xs text-gray-400 max-w-sm mb-6">
    Acompanhe, aprove ou oculte comentários enviados pelos leitores.
  </p>

  <button class="border border-gray-200 rounded px-4 py-2 text-xs font-medium text-gray-700 bg-white hover:bg-gray-50 shadow-sm">
    Explorar configurações
  </button>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
