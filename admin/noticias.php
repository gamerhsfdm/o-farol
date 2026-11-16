<?php
/**
 * O FAROL - Gestão de Notícias Fiel à Imagem 2
 * Filtros em abas vermelhas + busca + tabela limpa com checkboxes e ações
 */
declare(strict_types=1);
$admin_title = "Notícias";
$admin_breadcrumb = "Notícias";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$status_filter = $_GET['status'] ?? 'todas';

// Consulta notícias do banco
$noticias = [];
if ($pdo) {
    try {
        $where = "1=1";
        if ($status_filter === 'publicadas') $where = "status = 'publicado'";
        elseif ($status_filter === 'rascunhos') $where = "status = 'rascunho'";
        elseif ($status_filter === 'revisao') $where = "status = 'revisao'";
        elseif ($status_filter === 'aprovadas') $where = "status = 'aprovado'";
        elseif ($status_filter === 'agendadas') $where = "status = 'agendado'";
        elseif ($status_filter === 'arquivadas') $where = "status = 'arquivado'";

        $stmt = $pdo->query("SELECT n.*, c.nome as categoria_nome FROM noticias n JOIN categorias c ON n.categoria_id = c.id WHERE $where ORDER BY n.id DESC");
        $noticias = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Fallback visual caso banco ainda esteja iniciando
if (empty($noticias)) {
    $noticias = [
        ['id' => 1, 'titulo' => 'Jovens cientistas apresentam soluções para a escola', 'categoria_nome' => 'Clube de Ciências', 'autor_nome' => 'Ana Luiza', 'status' => 'publicado', 'data' => '12 jun, 09:30', 'foto' => 'assets/images/ciencia-lab.jpg', 'slug' => 'jovens-cientistas'],
        ['id' => 2, 'titulo' => 'Jogos Escolares reúnem mais de 240 estudantes', 'categoria_nome' => 'Jogos Escolares', 'autor_nome' => 'Lucas Mendes', 'status' => 'revisao', 'data' => '11 jun, 16:45', 'foto' => 'assets/images/hero-premiacao.jpg', 'slug' => 'jogos-escolares-reunem'],
        ['id' => 3, 'titulo' => 'Clube do Livro debate literatura brasileira', 'categoria_nome' => 'Clube do Livro', 'autor_nome' => 'Beatriz Lima', 'status' => 'rascunho', 'data' => '10 jun, 14:20', 'foto' => 'assets/images/livro-torto-arado.jpg', 'slug' => 'clube-do-livro-debate'],
        ['id' => 4, 'titulo' => 'Alunos desenvolvem projetos sustentáveis na FECCI', 'categoria_nome' => 'FECCI', 'autor_nome' => 'Rafael Souza', 'status' => 'agendado', 'data' => '14 jun, 10:00', 'foto' => 'assets/images/hero-premiacao.jpg', 'slug' => 'alunos-projetos-fecci'],
        ['id' => 5, 'titulo' => 'Feira Cultural celebra saberes e identidades', 'categoria_nome' => 'Feira Cultural', 'autor_nome' => 'Camila Rocha', 'status' => 'publicado', 'data' => '08 jun, 11:15', 'foto' => 'assets/images/flagrante-semana.jpg', 'slug' => 'feira-cultural-celebra']
    ];
}
?>

<!-- TOPO DA PÁGINA -->
<div class="flex items-center justify-between mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#c0262d] uppercase tracking-wider mb-1">O FAROL / ADMINISTRAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Notícias</h1>
    <p class="text-xs text-gray-500 mt-1">Gerencie todas as reportagens, rascunhos e publicações.</p>
  </div>
  <a href="<?= BASE_URL ?>/admin/editor.php" class="btn-admin-red">
    + Nova notícia
  </a>
</div>

<!-- ABAS DE STATUS IDÊNTICAS À IMAGEM 2 -->
<div class="flex flex-wrap gap-2 mb-4">
  <a href="?status=todas" class="admin-pill-tab <?= $status_filter === 'todas' ? 'active' : '' ?>">Todas <span class="text-gray-400">128</span></a>
  <a href="?status=publicadas" class="admin-pill-tab <?= $status_filter === 'publicadas' ? 'active' : '' ?>">Publicadas <span class="text-gray-400">94</span></a>
  <a href="?status=rascunhos" class="admin-pill-tab <?= $status_filter === 'rascunhos' ? 'active' : '' ?>">Rascunhos <span class="text-gray-400">18</span></a>
  <a href="?status=revisao" class="admin-pill-tab <?= $status_filter === 'revisao' ? 'active' : '' ?>">Em revisão <span class="text-gray-400">8</span></a>
  <a href="?status=aprovadas" class="admin-pill-tab <?= $status_filter === 'aprovadas' ? 'active' : '' ?>">Aprovadas <span class="text-gray-400">3</span></a>
  <a href="?status=agendadas" class="admin-pill-tab <?= $status_filter === 'agendadas' ? 'active' : '' ?>">Agendadas <span class="text-gray-400">5</span></a>
  <a href="?status=arquivadas" class="admin-pill-tab <?= $status_filter === 'arquivadas' ? 'active' : '' ?>">Arquivadas</a>
</div>

<!-- BARRA DE BUSCA E FILTROS -->
<div class="admin-card p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
  <div class="relative flex-1 min-w-[280px]">
    <input type="text" placeholder="Buscar por título, autor ou categoria..." 
           class="w-full pl-8 pr-3 py-1.5 text-xs bg-transparent border-0 focus:outline-none">
    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
  </div>
  
  <div class="flex items-center gap-2">
    <button class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
      Categoria <span class="text-gray-400">⌄</span>
    </button>
    <button class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Período <span class="text-gray-400">⌄</span>
    </button>
    <button class="p-1.5 text-gray-400 hover:text-black">•••</button>
  </div>
</div>

<!-- TABELA DE NOTÍCIAS IDÊNTICA À IMAGEM 2 -->
<div class="admin-card overflow-hidden">
  <table class="w-full text-left text-xs">
    <thead>
      <tr class="border-b border-[#f0eee9] text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-[#fafaf8]">
        <th class="p-3 w-8"><input type="checkbox" class="rounded text-[#c0262d]"></th>
        <th class="p-3">NOTÍCIA</th>
        <th class="p-3">CATEGORIA</th>
        <th class="p-3">AUTOR</th>
        <th class="p-3">STATUS</th>
        <th class="p-3">PUBLICAÇÃO</th>
        <th class="p-3">ATUALIZAÇÃO</th>
        <th class="p-3 text-right"></th>
      </tr>
    </thead>
    <tbody class="divide-y divide-[#f7f6f2]">
      <?php foreach ($noticias as $n): ?>
        <tr class="hover:bg-[#fafaf8] transition-colors">
          <td class="p-3.5"><input type="checkbox" class="rounded text-[#c0262d]"></td>
          
          <!-- Notícia com Thumbnail e Slug -->
          <td class="p-3.5">
            <div class="flex items-center gap-3">
              <img src="<?= BASE_URL ?>/<?= e($n['foto'] ?? 'assets/images/ciencia-lab.jpg') ?>" class="w-9 h-9 rounded object-cover flex-shrink-0">
              <div>
                <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= $n['id'] ?>" class="font-bold text-gray-900 hover:text-[#c0262d] block leading-snug">
                  <?= e($n['titulo']) ?>
                </a>
                <span class="text-[10px] text-gray-400 font-mono">ofarol.edu.br/noticias/<?= e($n['slug'] ?? 'post') ?></span>
              </div>
            </div>
          </td>

          <td class="p-3.5 text-gray-600"><?= e($n['categoria_nome']) ?></td>

          <!-- Autor com Badge de Iniciais -->
          <td class="p-3.5">
            <div class="flex items-center gap-1.5">
              <div class="w-5 h-5 rounded-full bg-[#f3f4f6] text-gray-600 text-[9px] font-bold flex items-center justify-center">
                <?= strtoupper(substr($n['autor_nome'] ?? 'AL', 0, 2)) ?>
              </div>
              <span class="text-gray-800"><?= e($n['autor_nome'] ?? 'Ana Luiza') ?></span>
            </div>
          </td>

          <!-- Status Colorido -->
          <td class="p-3.5">
            <?php if (($n['status'] ?? 'publicado') === 'publicado'): ?>
              <span class="badge-status badge-status-published">Publicada</span>
            <?php elseif (($n['status'] ?? '') === 'revisao'): ?>
              <span class="badge-status badge-status-review">Em revisão</span>
            <?php elseif (($n['status'] ?? '') === 'agendado'): ?>
              <span class="badge-status badge-status-scheduled">Agendada</span>
            <?php else: ?>
              <span class="badge-status badge-status-draft">Rascunho</span>
            <?php endif; ?>
          </td>

          <td class="p-3.5 text-gray-600 font-sans"><?= e($n['data'] ?? '12 jun, 09:30') ?></td>
          <td class="p-3.5 text-gray-400 font-sans">1h atrás</td>
          
          <td class="p-3.5 text-right">
            <button class="text-gray-400 hover:text-black">•••</button>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Rodapé da Tabela: Paginação -->
  <div class="p-4 border-t border-[#f0eee9] flex items-center justify-between text-xs text-gray-500">
    <span>Mostrando 1-5 de 128 notícias</span>
    <div class="flex items-center gap-1">
      <button class="px-2 py-1 text-gray-400 hover:text-black">Anterior</button>
      <button class="w-6 h-6 rounded bg-[#0e2f33] text-white font-bold text-xs flex items-center justify-center">1</button>
      <button class="w-6 h-6 rounded hover:bg-gray-100 text-gray-700 text-xs flex items-center justify-center">2</button>
      <button class="w-6 h-6 rounded hover:bg-gray-100 text-gray-700 text-xs flex items-center justify-center">3</button>
      <span>...</span>
      <button class="w-6 h-6 rounded hover:bg-gray-100 text-gray-700 text-xs flex items-center justify-center">26</button>
      <button class="px-2 py-1 text-gray-600 hover:text-black font-semibold">Próximo</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
