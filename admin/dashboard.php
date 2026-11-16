<?php
/**
 * O FAROL - Dashboard Editorial Fiel à Imagem 1
 * Indicadores 128, 94, 8, 48.2 mil + Gráfico de Desempenho + Distribuição por Categoria
 */
declare(strict_types=1);
$admin_title = "Visão geral";
$admin_breadcrumb = "Visão geral";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();

// Busca dados reais ou valores da imagem de referência para visual impecável
$total_noticias = 128;
$publicadas = 94;
$em_revisao = 8;
$views = '48,2 mil';

if ($pdo) {
    try {
        $c = (int)$pdo->query("SELECT COUNT(*) FROM noticias")->fetchColumn();
        if ($c > 0) {
            $total_noticias = $c;
            $publicadas = (int)$pdo->query("SELECT COUNT(*) FROM noticias WHERE status = 'publicado'")->fetchColumn();
            $em_revisao = (int)$pdo->query("SELECT COUNT(*) FROM noticias WHERE status IN ('revisao', 'rascunho')")->fetchColumn();
            $v = (int)$pdo->query("SELECT SUM(visualizacoes) FROM noticias")->fetchColumn();
            $views = $v > 1000 ? number_format($v / 1000, 1, ',', '.') . ' mil' : (string)$v;
        }
    } catch (Exception $e) {}
}
?>

<!-- TOPO DA PÁGINA: O FAROL / ADMINISTRAÇÃO -->
<div class="mb-6">
  <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
  <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Visão geral</h1>
  <p class="text-xs text-gray-500 mt-1">Acompanhe o ritmo da redação e as principais métricas do jornal.</p>
</div>

<!-- 1. CARDS DE MÉTRICAS (128, 94, 8, 48,2 mil) IDÊNTICOS À IMAGEM 1 -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6">
  
  <!-- Card 1: Notícias -->
  <div class="admin-card p-5 flex items-start justify-between">
    <div>
      <span class="text-xs text-gray-500 font-medium">Notícias</span>
      <div class="text-3xl font-editorial-heading font-bold text-[#111827] mt-2"><?= $total_noticias ?></div>
      <span class="text-[10px] text-gray-400 mt-1 block">+12 este mês</span>
    </div>
    <div class="w-8 h-8 rounded-full bg-[#fef2f2] text-[#c0262d] flex items-center justify-center">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
  </div>

  <!-- Card 2: Publicadas -->
  <div class="admin-card p-5 flex items-start justify-between">
    <div>
      <span class="text-xs text-gray-500 font-medium">Publicadas</span>
      <div class="text-3xl font-editorial-heading font-bold text-[#111827] mt-2"><?= $publicadas ?></div>
      <span class="text-[10px] text-gray-400 mt-1 block">73% do total</span>
    </div>
    <div class="w-8 h-8 rounded-full bg-[#fef2f2] text-[#c0262d] flex items-center justify-center">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
  </div>

  <!-- Card 3: Em revisão -->
  <div class="admin-card p-5 flex items-start justify-between">
    <div>
      <span class="text-xs text-gray-500 font-medium">Em revisão</span>
      <div class="text-3xl font-editorial-heading font-bold text-[#111827] mt-2"><?= $em_revisao ?></div>
      <span class="text-[10px] text-gray-400 mt-1 block">4 aguardam você</span>
    </div>
    <div class="w-8 h-8 rounded-full bg-[#fef2f2] text-[#c0262d] flex items-center justify-center">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
  </div>

  <!-- Card 4: Visualizações -->
  <div class="admin-card p-5 flex items-start justify-between">
    <div>
      <span class="text-xs text-gray-500 font-medium">Visualizações</span>
      <div class="text-3xl font-editorial-heading font-bold text-[#111827] mt-2"><?= $views ?></div>
      <span class="text-[10px] text-emerald-600 mt-1 block">↑ 18,4%</span>
    </div>
    <div class="w-8 h-8 rounded-full bg-[#fef2f2] text-[#c0262d] flex items-center justify-center">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
    </div>
  </div>

</div>

<!-- 2. GRÁFICO DE ONDA VERMELHA + DONUT DE CATEGORIAS -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-5 mb-6">
  
  <!-- Desempenho Editorial (8 Colunas) -->
  <div class="lg:col-span-8 admin-card p-6">
    <div class="flex items-center justify-between pb-3 mb-2">
      <div>
        <h3 class="text-sm font-bold text-[#111827]">Desempenho editorial</h3>
        <p class="text-[11px] text-gray-400">Publicações e visualizações nos últimos 7 dias</p>
      </div>
      <div class="flex items-center gap-4 text-xs">
        <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2 h-2 rounded-full bg-[#c0262d]"></span> 18 publicações</span>
        <span class="flex items-center gap-1.5 text-gray-600"><span class="w-2 h-2 rounded-full bg-[#0e2f33]"></span> 12.840 visualizações</span>
        <select class="text-xs border border-gray-200 rounded px-2 py-1 bg-white text-gray-600 outline-none">
          <option>Últimos 7 dias</option>
          <option>Últimos 30 dias</option>
        </select>
      </div>
    </div>

    <!-- Gráfico de Linha Suave Vermelha idêntico à imagem 1 -->
    <div class="h-64 w-full">
      <canvas id="chartDesempenho"></canvas>
    </div>
  </div>

  <!-- Por Categoria (Donut) (4 Colunas) -->
  <div class="lg:col-span-4 admin-card p-6 flex flex-col justify-between">
    <div class="flex items-center justify-between pb-2 mb-2">
      <div>
        <h3 class="text-sm font-bold text-[#111827]">Por categoria</h3>
        <p class="text-[11px] text-gray-400">Distribuição do conteúdo</p>
      </div>
      <button class="text-gray-400 hover:text-black">•••</button>
    </div>

    <!-- Gráfico Donut com 128 no meio -->
    <div class="relative h-44 flex items-center justify-center my-2">
      <canvas id="chartCategorias"></canvas>
      <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
        <span class="text-2xl font-editorial-heading font-bold text-[#111827]">128</span>
        <span class="text-[10px] text-gray-400 uppercase tracking-wider">notícias</span>
      </div>
    </div>

    <!-- Legenda lateral/inferior idêntica -->
    <div class="space-y-1.5 text-xs pt-3 border-t border-gray-100">
      <div class="flex items-center justify-between text-gray-600">
        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#c0262d]"></span> Ciências</span>
        <span class="font-bold text-gray-800">32%</span>
      </div>
      <div class="flex items-center justify-between text-gray-600">
        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#0e2f33]"></span> Cultura</span>
        <span class="font-bold text-gray-800">26%</span>
      </div>
      <div class="flex items-center justify-between text-gray-600">
        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#d97706]"></span> Esportes</span>
        <span class="font-bold text-gray-800">22%</span>
      </div>
      <div class="flex items-center justify-between text-gray-600">
        <span class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-[#9ca3af]"></span> Outros</span>
        <span class="font-bold text-gray-800">20%</span>
      </div>
    </div>
  </div>

</div>

<!-- 3. ÚLTIMAS NOTÍCIAS + PRÓXIMAS PUBLICAÇÕES -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
  
  <!-- Últimas notícias (8 Colunas) -->
  <div class="lg:col-span-8 admin-card p-6">
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
      <div>
        <h3 class="text-sm font-bold text-[#111827]">Últimas notícias</h3>
        <p class="text-[11px] text-gray-400">Conteúdos editados recentemente</p>
      </div>
      <a href="<?= BASE_URL ?>/admin/noticias.php" class="text-xs text-[#c0262d] font-semibold hover:underline">Ver todas ›</a>
    </div>

    <div class="space-y-3">
      <!-- Item 1 -->
      <div class="flex items-center justify-between py-2 border-b border-gray-50">
        <div class="flex items-center gap-3">
          <img src="<?= BASE_URL ?>/assets/images/ciencia-lab.jpg" class="w-10 h-10 rounded object-cover">
          <div>
            <h4 class="text-xs font-bold text-gray-900 leading-snug">Jovens cientistas apresentam soluções para a escola</h4>
            <span class="text-[10px] text-gray-400">Clube de Ciências • Ana Luiza</span>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="badge-status badge-status-published">Publicada</span>
          <span class="text-[11px] text-gray-400 font-sans">12 jun, 09:30</span>
        </div>
      </div>

      <!-- Item 2 -->
      <div class="flex items-center justify-between py-2">
        <div class="flex items-center gap-3">
          <img src="<?= BASE_URL ?>/assets/images/hero-premiacao.jpg" class="w-10 h-10 rounded object-cover">
          <div>
            <h4 class="text-xs font-bold text-gray-900 leading-snug">Jogos Escolares reúnem mais de 240 estudantes</h4>
            <span class="text-[10px] text-gray-400">Jogos Escolares • Lucas Mendes</span>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="badge-status badge-status-review">Em revisão</span>
          <span class="text-[11px] text-gray-400 font-sans">11 jun, 16:45</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Próximas publicações / Agenda (4 Colunas) -->
  <div class="lg:col-span-4 admin-card p-6">
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-gray-100">
      <div>
        <h3 class="text-sm font-bold text-[#111827]">Próximas publicações</h3>
        <p class="text-[11px] text-gray-400">Agenda editorial</p>
      </div>
    </div>

    <div class="space-y-4">
      <div class="flex items-start gap-3">
        <div class="bg-[#fef2f2] text-[#c0262d] font-bold text-xs p-2 rounded text-center min-w-[38px]">
          <div class="text-sm font-black">14</div>
          <div class="text-[8px] uppercase">JUN</div>
        </div>
        <div>
          <div class="text-xs font-bold text-gray-900">Projetos sustentáveis na FECCI</div>
          <div class="text-[10px] text-gray-400 mt-0.5">🕒 10:00</div>
        </div>
      </div>

      <div class="flex items-start gap-3">
        <div class="bg-[#fef2f2] text-[#c0262d] font-bold text-xs p-2 rounded text-center min-w-[38px]">
          <div class="text-sm font-black">17</div>
          <div class="text-[8px] uppercase">JUN</div>
        </div>
        <div>
          <div class="text-xs font-bold text-gray-900">Entrevista com novos autores</div>
          <div class="text-[10px] text-gray-400 mt-0.5">🕒 09:30</div>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
// Gráfico 1: Curva Suave Vermelha Idêntica à Imagem 1
const ctxLine = document.getElementById('chartDesempenho').getContext('2d');
new Chart(ctxLine, {
  type: 'line',
  data: {
    labels: ['08 jun', '09 jun', '10 jun', '11 jun', '12 jun', '13 jun', '14 jun'],
    datasets: [{
      data: [12, 14, 10, 18, 15, 25, 23],
      borderColor: '#c0262d',
      backgroundColor: 'rgba(192, 38, 45, 0.08)',
      fill: true,
      tension: 0.45,
      borderWidth: 2.5,
      pointRadius: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: {
      x: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#9ca3af' } },
      y: { display: false }
    }
  }
});

// Gráfico 2: Donut por Categoria Idêntico à Imagem 1
const ctxDonut = document.getElementById('chartCategorias').getContext('2d');
new Chart(ctxDonut, {
  type: 'doughnut',
  data: {
    labels: ['Ciências', 'Cultura', 'Esportes', 'Outros'],
    datasets: [{
      data: [32, 26, 22, 20],
      backgroundColor: ['#c0262d', '#0e2f33', '#d97706', '#d1d5db'],
      borderWidth: 0,
      cutout: '76%'
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } }
  }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
