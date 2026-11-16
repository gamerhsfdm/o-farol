<?php
/**
 * O FAROL - Gestão de Enquetes Fiel à Imagem 8
 * Tabela de Enquetes + Painel de Resultados ao Vivo com Barras Verdes Escuras (#0e2f33)
 */
declare(strict_types=1);
$admin_title = "Enquetes";
$admin_breadcrumb = "Enquetes";
require_once __DIR__ . '/header.php';

// Enquetes exatas da imagem 8
$enquetes_tabela = [
    ['pergunta' => 'Qual tema você quer ver na próxima edição?', 'votos' => '482', 'data' => '20 jun', 'status' => 'Ativa'],
    ['pergunta' => 'Qual projeto mais inspira você?', 'votos' => '328', 'data' => '12 jun', 'status' => 'Encerrada'],
    ['pergunta' => 'Você participa de algum clube escolar?', 'votos' => '216', 'data' => '30 jun', 'status' => 'Rascunho']
];
?>

<!-- TOPO: TÍTULO + BOTÃO NOVA ENQUETE -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Enquetes</h1>
    <p class="text-xs text-gray-500 mt-1">Crie perguntas e acompanhe a participação dos leitores.</p>
  </div>
  <button class="btn-admin-red">
    + Nova enquete
  </button>
</div>

<!-- GRADE TABELA DE ENQUETES (ESQ) + RESULTADOS AO VIVO (DIR) IDÊNTICO À IMAGEM 8 -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
  
  <!-- Tabela de Enquetes (7 Colunas) -->
  <div class="lg:col-span-7 admin-card overflow-hidden">
    <table class="w-full text-left text-xs">
      <thead>
        <tr class="border-b border-[#f0eee9] text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-[#fafaf8]">
          <th class="p-3.5">PERGUNTA</th>
          <th class="p-3.5">VOTOS</th>
          <th class="p-3.5">ENCERRAMENTO</th>
          <th class="p-3.5">STATUS</th>
          <th class="p-3.5 text-right"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-[#f7f6f2]">
        <?php foreach ($enquetes_tabela as $eq): ?>
          <tr class="hover:bg-[#fafaf8] transition-colors">
            <td class="p-3.5">
              <div class="font-bold text-gray-900"><?= e($eq['pergunta']) ?></div>
              <span class="text-[10px] text-gray-400">4 alternativas</span>
            </td>
            <td class="p-3.5 font-mono text-gray-600"><?= e($eq['votos']) ?></td>
            <td class="p-3.5 text-gray-500 font-sans"><?= e($eq['data']) ?></td>
            <td class="p-3.5">
              <span class="text-[9.5px] px-2 py-0.5 rounded <?= $eq['status'] === 'Ativa' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'bg-gray-100 text-gray-500' ?>">
                <?= e($eq['status']) ?>
              </span>
            </td>
            <td class="p-3.5 text-right">
              <button class="text-gray-400 hover:text-black">•••</button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Painel de Resultados ao Vivo (5 Colunas) Idêntico à Imagem 8 -->
  <div class="lg:col-span-5 admin-card p-6 space-y-4">
    <div class="flex items-center justify-between pb-2 border-b border-gray-100">
      <div>
        <h3 class="text-xs font-bold text-gray-900">Resultados ao vivo</h3>
        <p class="text-[10px] text-gray-400">482 votos • Atualizado agora</p>
      </div>
      <span class="text-[9.5px] px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">Ativa</span>
    </div>

    <h4 class="text-base font-editorial-heading font-bold text-gray-900 leading-snug">
      Qual tema você quer ver na próxima edição?
    </h4>

    <div class="space-y-3 pt-2 text-xs">
      <div>
        <div class="flex justify-between text-gray-700 mb-1">
          <span>Ciência e tecnologia</span>
          <span class="font-bold">42%</span>
        </div>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
          <div class="bg-[#0e2f33] h-full" style="width: 42%"></div>
        </div>
      </div>

      <div>
        <div class="flex justify-between text-gray-700 mb-1">
          <span>Cultura e arte</span>
          <span class="font-bold">27%</span>
        </div>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
          <div class="bg-[#0e2f33] h-full" style="width: 27%"></div>
        </div>
      </div>

      <div>
        <div class="flex justify-between text-gray-700 mb-1">
          <span>Esportes</span>
          <span class="font-bold">19%</span>
        </div>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
          <div class="bg-[#0e2f33] h-full" style="width: 19%"></div>
        </div>
      </div>

      <div>
        <div class="flex justify-between text-gray-700 mb-1">
          <span>Carreiras e futuro</span>
          <span class="font-bold">12%</span>
        </div>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
          <div class="bg-[#0e2f33] h-full" style="width: 12%"></div>
        </div>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
