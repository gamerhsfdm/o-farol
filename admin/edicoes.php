<?php
/**
 * O FAROL - Gestão de Edições Impressas e Digitais
 */
declare(strict_types=1);
$admin_title = "Edições";
$admin_breadcrumb = "Edições do jornal";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Criar nova edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_edicao'])) {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $numero = (int)($_POST['numero'] ?? 0);
        $titulo = trim($_POST['titulo'] ?? '');
        $mes_ano = trim($_POST['mes_ano'] ?? '');
        $data_pub = trim($_POST['data_publicacao'] ?? date('Y-m-d'));
        $capa_url = trim($_POST['capa_url'] ?? 'assets/images/hero-premiacao.jpg');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($numero <= 0 || empty($titulo) || empty($mes_ano)) {
            $erro = "Preencha o número da edição, título e mês/ano.";
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO edicoes (numero, titulo, mes_ano, data_publicacao, capa_url, descricao, destaque, criado_em) VALUES (?, ?, ?, ?, ?, ?, 0, NOW())");
                $stmt->execute([$numero, $titulo, $mes_ano, $data_pub, $capa_url, $descricao]);
                $mensagem = "Edição Nº {$numero} cadastrada com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao cadastrar edição: " . $e->getMessage();
            }
        } else {
            $mensagem = "Edição cadastrada com sucesso (modo demonstração)!";
        }
    }
}

// Consultar edições
$edicoes = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT e.*, COUNT(n.id) as total_noticias 
                             FROM edicoes e 
                             LEFT JOIN noticias n ON e.id = n.edicao_id 
                             GROUP BY e.id 
                             ORDER BY e.numero DESC");
        $edicoes = $stmt->fetchAll();
    } catch (Exception $e) {}
}

if (empty($edicoes)) {
    $edicoes = [
        ['id' => 1, 'numero' => 18, 'titulo' => 'Edição Especial: Protagonismo Estudantil e Ciência', 'mes_ano' => 'Junho de 2026', 'data_publicacao' => '2026-06-15', 'capa_url' => 'assets/images/hero-premiacao.jpg', 'total_noticias' => 14, 'destaque' => 1],
        ['id' => 2, 'numero' => 17, 'titulo' => 'Tecnologia Aplicada e Inovação na Escola', 'mes_ano' => 'Maio de 2026', 'data_publicacao' => '2026-05-15', 'capa_url' => 'assets/images/ciencia-lab.jpg', 'total_noticias' => 12, 'destaque' => 0],
        ['id' => 3, 'numero' => 16, 'titulo' => 'Artes, Literatura e Sociedade', 'mes_ano' => 'Abril de 2026', 'data_publicacao' => '2026-04-15', 'capa_url' => 'assets/images/livro-torto-arado.jpg', 'total_noticias' => 10, 'destaque' => 0]
    ];
}
?>

<div class="flex flex-wrap items-center justify-between gap-4 mb-6">
  <div>
    <div class="text-[10px] font-bold text-[#b8323b] uppercase tracking-wider mb-1">O FAROL / PUBLICAÇÃO</div>
    <h1 class="text-3xl font-editorial-heading font-bold text-[#111827]">Edições do Jornal</h1>
    <p class="text-xs text-gray-500 mt-1">Gerencie as publicações mensais, impressas e digitais do O Farol.</p>
  </div>
  <button type="button" onclick="document.getElementById('modal-nova-edicao').classList.remove('hidden')" class="btn-admin-red">
    + Nova Edição
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

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
  <?php foreach ($edicoes as $ed): ?>
    <div class="admin-card overflow-hidden flex flex-col justify-between hover:border-gray-300 transition-colors">
      <div class="relative h-48 bg-gray-100 overflow-hidden">
        <img src="<?= BASE_URL ?>/<?= e($ed['capa_url']) ?>" alt="Capa Edição <?= e((string)$ed['numero']) ?>" class="w-full h-full object-cover">
        <div class="absolute top-3 left-3 flex items-center gap-2">
          <span class="bg-[#111827]/90 text-white font-mono text-[10.5px] font-bold px-2 py-0.5 rounded shadow">
            Nº <?= e((string)$ed['numero']) ?>
          </span>
          <?php if (!empty($ed['destaque'])): ?>
            <span class="bg-[#b8323b] text-white text-[9.5px] font-bold px-2 py-0.5 rounded shadow uppercase">
              Atual
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="p-5 flex-1 flex flex-col justify-between">
        <div>
          <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider block mb-1"><?= e($ed['mes_ano']) ?></span>
          <h3 class="text-base font-bold text-gray-900 font-editorial-heading leading-snug mb-2"><?= e($ed['titulo']) ?></h3>
          <p class="text-xs text-gray-500 line-clamp-2"><?= e($ed['descricao'] ?? 'Edição regular do jornal escolar.') ?></p>
        </div>

        <div class="pt-4 mt-4 border-t border-[#e8edef] flex items-center justify-between text-xs">
          <span class="text-gray-400"><?= e((string)$ed['total_noticias']) ?> matérias</span>
          <a href="<?= BASE_URL ?>/admin/noticias.php?edicao=<?= $ed['id'] ?>" class="text-[#0e2f33] font-bold hover:underline">
            Ver matérias ›
          </a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- MODAL NOVA EDIÇÃO -->
<div id="modal-nova-edicao" class="fixed inset-0 bg-black/40 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
  <div class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 border border-[#e8edef]">
    <div class="flex items-center justify-between pb-3 mb-4 border-b border-gray-100">
      <h3 class="text-sm font-bold text-gray-900 font-editorial-heading">Nova Edição do Jornal</h3>
      <button type="button" onclick="document.getElementById('modal-nova-edicao').classList.add('hidden')" class="text-gray-400 hover:text-black">×</button>
    </div>

    <form method="POST" action="" class="space-y-4">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Número *</label>
          <input type="number" name="numero" required min="1" placeholder="Ex: 19" class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#b8323b] focus:outline-none">
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1">Mês / Ano *</label>
          <input type="text" name="mes_ano" required placeholder="Ex: Julho de 2026" class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#b8323b] focus:outline-none">
        </div>
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Título Editorial da Edição *</label>
        <input type="text" name="titulo" required placeholder="Ex: Juventude, Clima e Futuro" class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#b8323b] focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Data de Publicação</label>
        <input type="date" name="data_publicacao" value="<?= date('Y-m-d') ?>" class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#b8323b] focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Capa da Edição (Caminho ou URL)</label>
        <input type="text" name="capa_url" value="assets/images/hero-premiacao.jpg" class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#b8323b] focus:outline-none">
      </div>

      <div>
        <label class="block text-xs font-medium text-gray-700 mb-1">Descrição</label>
        <textarea name="descricao" rows="2" placeholder="Resumo dos principais tópicos da edição..." class="w-full text-xs p-2.5 border border-gray-200 rounded focus:border-[#b8323b] focus:outline-none"></textarea>
      </div>

      <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100">
        <button type="button" onclick="document.getElementById('modal-nova-edicao').classList.add('hidden')" class="px-4 py-2 border border-gray-200 rounded text-xs text-gray-700 bg-white hover:bg-gray-50">
          Cancelar
        </button>
        <button type="submit" name="criar_edicao" value="1" class="btn-admin-red">
          Cadastrar Edição
        </button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
