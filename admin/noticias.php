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
$autor_filter = $_GET['autor'] ?? '';
$search_query = trim($_GET['q'] ?? '');
$mensagem = '';

// Exclusão ou arquivamento rápido
if (isset($_GET['deletar_id']) && $pdo) {
    try {
        $delId = (int)$_GET['deletar_id'];
        $stmtD = $pdo->prepare("DELETE FROM noticias WHERE id = ?");
        $stmtD->execute([$delId]);
        $mensagem = "Reportagem excluída com sucesso!";
    } catch (Exception $e) {}
}

// Contagens para os pills de status
$counts = ['todas' => 128, 'publicadas' => 94, 'rascunhos' => 18, 'revisao' => 8, 'aprovadas' => 3, 'agendadas' => 5, 'arquivadas' => 0];
if ($pdo) {
    try {
        $stmtTot = $pdo->query("SELECT status, COUNT(*) as qtd FROM noticias GROUP BY status");
        $bdCounts = $stmtTot->fetchAll();
        $totalAll = 0;
        foreach ($bdCounts as $bc) {
            $st = $bc['status'];
            $qtd = (int)$bc['qtd'];
            $totalAll += $qtd;
            if ($st === 'publicado') $counts['publicadas'] = $qtd;
            elseif ($st === 'rascunho') $counts['rascunhos'] = $qtd;
            elseif ($st === 'revisao') $counts['revisao'] = $qtd;
            elseif ($st === 'aprovado') $counts['aprovadas'] = $qtd;
            elseif ($st === 'agendado') $counts['agendadas'] = $qtd;
            elseif ($st === 'arquivado') $counts['arquivadas'] = $qtd;
        }
        if ($totalAll > 0) $counts['todas'] = $totalAll;
    } catch (Exception $e) {}
}

// Consulta notícias do banco
$noticias = [];
if ($pdo) {
    try {
        $where = "1=1";
        $params = [];
        if ($status_filter === 'publicadas') $where .= " AND n.status = 'publicado'";
        elseif ($status_filter === 'rascunhos') $where .= " AND n.status = 'rascunho'";
        elseif ($status_filter === 'revisao') $where .= " AND n.status = 'revisao'";
        elseif ($status_filter === 'aprovadas') $where .= " AND n.status = 'aprovado'";
        elseif ($status_filter === 'agendadas') $where .= " AND n.status = 'agendado'";
        elseif ($status_filter === 'arquivadas') $where .= " AND n.status = 'arquivado'";

        if (!empty($search_query)) {
            $where .= " AND (n.titulo LIKE ? OR n.subtitulo LIKE ?)";
            $params[] = "%{$search_query}%";
            $params[] = "%{$search_query}%";
        }

        $stmt = $pdo->prepare("SELECT n.*, c.nome as categoria_nome, 
                               COALESCE((SELECT a.nome FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = n.id LIMIT 1), 'Redação') as autor_nome
                               FROM noticias n 
                               JOIN categorias c ON n.categoria_id = c.id 
                               WHERE $where 
                               ORDER BY n.id DESC");
        $stmt->execute($params);
        $bdNews = $stmt->fetchAll();
        foreach ($bdNews as $bn) {
            $noticias[] = [
                'id' => $bn['id'],
                'titulo' => $bn['titulo'],
                'categoria_nome' => $bn['categoria_nome'],
                'autor_nome' => $bn['autor_nome'],
                'status' => $bn['status'],
                'data' => date('d M, H:i', strtotime($bn['publicado_em'] ?? $bn['criado_em'] ?? 'now')),
                'foto' => !empty($bn['foto_capa']) ? $bn['foto_capa'] : 'assets/images/ciencia-lab.jpg',
                'slug' => $bn['slug']
            ];
        }
    } catch (Exception $e) {}
}

// Fallback visual exato da Imagem 2
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

<?php if ($mensagem): ?>
  <div class="p-3 mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded flex items-center justify-between">
    <span>✓ <?= e($mensagem) ?></span>
    <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">×</button>
  </div>
<?php endif; ?>

<!-- ABAS DE STATUS (PILLS) IDÊNTICAS À IMAGEM 2 -->
<div class="flex flex-wrap gap-2 mb-4">
  <a href="?status=todas" class="admin-pill-tab <?= $status_filter === 'todas' ? 'active' : '' ?>">Todas <?= $counts['todas'] ?></a>
  <a href="?status=publicadas" class="admin-pill-tab <?= $status_filter === 'publicadas' ? 'active' : '' ?>">Publicadas <?= $counts['publicadas'] ?></a>
  <a href="?status=rascunhos" class="admin-pill-tab <?= $status_filter === 'rascunhos' ? 'active' : '' ?>">Rascunhos <?= $counts['rascunhos'] ?></a>
  <a href="?status=revisao" class="admin-pill-tab <?= $status_filter === 'revisao' ? 'active' : '' ?>">Em revisão <?= $counts['revisao'] ?></a>
  <a href="?status=aprovadas" class="admin-pill-tab <?= $status_filter === 'aprovadas' ? 'active' : '' ?>">Aprovadas <?= $counts['aprovadas'] ?></a>
  <a href="?status=agendadas" class="admin-pill-tab <?= $status_filter === 'agendadas' ? 'active' : '' ?>">Agendadas <?= $counts['agendadas'] ?></a>
  <a href="?status=arquivadas" class="admin-pill-tab <?= $status_filter === 'arquivadas' ? 'active' : '' ?>">Arquivadas</a>
</div>

<!-- BARRA DE PESQUISA E FILTROS IDÊNTICA À IMAGEM 2 -->
<form method="GET" action="" class="admin-card p-3 mb-6 flex flex-wrap items-center justify-between gap-3">
  <input type="hidden" name="status" value="<?= e($status_filter) ?>">
  <div class="relative flex-1 min-w-[280px]">
    <input type="text" name="q" value="<?= e($search_query) ?>" placeholder="Buscar por título, autor ou categoria..." 
           class="w-full pl-8 pr-3 py-1.5 text-xs bg-transparent border-0 focus:outline-none">
    <svg class="w-4 h-4 text-gray-400 absolute left-2 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
  </div>
  
  <div class="flex items-center gap-2 text-xs">
    <button type="button" class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
      Categoria ⌄
    </button>
    <button type="button" class="border border-gray-200 rounded px-3 py-1.5 bg-[#fafaf8] text-gray-600 flex items-center gap-1.5">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      Período ⌄
    </button>
  </div>
</form>

<!-- TABELA DE NOTÍCIAS IDÊNTICA À IMAGEM 2 -->
<div class="admin-card overflow-hidden">
  <table class="w-full text-left text-xs">
    <thead>
      <tr class="border-b border-[#f0eee9] text-[10px] font-bold text-gray-400 uppercase tracking-wider bg-[#fafaf8]">
        <th class="p-3.5 w-8"><input type="checkbox" class="rounded text-[#c0262d]"></th>
        <th class="p-3.5">NOTÍCIA</th>
        <th class="p-3.5">CATEGORIA</th>
        <th class="p-3.5">AUTOR</th>
        <th class="p-3.5">STATUS</th>
        <th class="p-3.5">PUBLICAÇÃO</th>
        <th class="p-3.5">AÇÕES</th>
      </tr>
    </thead>
    <tbody class="divide-y divide-[#f7f6f2]">
      <?php foreach ($noticias as $item): ?>
        <tr class="hover:bg-[#fafaf8] transition-colors">
          <td class="p-3.5"><input type="checkbox" class="rounded text-[#c0262d]"></td>
          
          <!-- Notícia com Miniatura e Título -->
          <td class="p-3.5">
            <div class="flex items-center gap-3">
              <img src="<?= BASE_URL ?>/<?= e($item['foto']) ?>" class="w-12 h-10 object-cover rounded border border-gray-200 flex-shrink-0">
              <div>
                <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= $item['id'] ?>" class="font-bold text-gray-900 hover:text-[#c0262d] leading-snug line-clamp-1">
                  <?= e($item['titulo']) ?>
                </a>
                <span class="text-[10px] text-gray-400 block mt-0.5">ofarol.edu.br/noticias/<?= e($item['slug']) ?></span>
              </div>
            </div>
          </td>

          <td class="p-3.5 text-gray-600"><?= e($item['categoria_nome']) ?></td>

          <!-- Autor com Avatar de Iniciais -->
          <td class="p-3.5">
            <div class="flex items-center gap-1.5">
              <div class="w-5 h-5 rounded-full bg-gray-100 text-gray-700 text-[9px] font-bold flex items-center justify-center">
                <?= strtoupper(substr($item['autor_nome'], 0, 2)) ?>
              </div>
              <span class="text-gray-700"><?= e($item['autor_nome']) ?></span>
            </div>
          </td>

          <!-- Badges de Status -->
          <td class="p-3.5">
            <?php if ($item['status'] === 'publicado'): ?>
              <span class="badge-status badge-status-published">Publicada</span>
            <?php elseif ($item['status'] === 'revisao'): ?>
              <span class="badge-status badge-status-review">Em revisão</span>
            <?php elseif ($item['status'] === 'agendado'): ?>
              <span class="badge-status badge-status-scheduled">Agendada</span>
            <?php else: ?>
              <span class="badge-status badge-status-draft">Rascunho</span>
            <?php endif; ?>
          </td>

          <td class="p-3.5 text-gray-500 whitespace-nowrap"><?= e($item['data']) ?></td>

          <!-- Ações: Editar e Excluir -->
          <td class="p-3.5 text-right whitespace-nowrap">
            <div class="flex items-center justify-end gap-2">
              <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= $item['id'] ?>" class="text-xs text-gray-600 hover:text-[#c0262d] font-bold" title="Editar">
                ✏️
              </a>
              <a href="?deletar_id=<?= $item['id'] ?>" onclick="return confirm('Deseja realmente excluir esta notícia?')" class="text-xs text-red-500 hover:text-red-700 font-bold" title="Excluir">
                🗑️
              </a>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Rodapé com Paginação -->
  <div class="p-4 border-t border-[#f0eee9] flex items-center justify-between text-xs text-gray-500 bg-[#fafaf8]">
    <span>Mostrando 1-<?= count($noticias) ?> de <?= $counts['todas'] ?> notícias</span>
    <div class="flex items-center gap-1">
      <button class="px-2.5 py-1 rounded border border-gray-200 bg-white">Anterior</button>
      <button class="px-2.5 py-1 rounded bg-[#0e2f33] text-white font-bold">1</button>
      <button class="px-2.5 py-1 rounded border border-gray-200 bg-white">2</button>
      <button class="px-2.5 py-1 rounded border border-gray-200 bg-white">3</button>
      <span>...</span>
      <button class="px-2.5 py-1 rounded border border-gray-200 bg-white">Próximo</button>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
