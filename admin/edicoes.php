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
$erro_carga = '';

// Criar nova edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_edicao'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
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
                $novoId = (int)$pdo->lastInsertId();
                log_audit('criar_edicao', 'edicoes', $novoId, "Edição Nº {$numero} cadastrada");
                $mensagem = "Edição Nº {$numero} cadastrada com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao cadastrar edição: " . $e->getMessage();
            }
        } else {
            $erro = "Banco de dados temporariamente inacessível.";
        }
    }
}

// Consultar edições reais do banco
$edicoes = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT e.*, COUNT(n.id) as total_noticias 
                             FROM edicoes e 
                             LEFT JOIN noticias n ON e.id = n.edicao_id 
                             GROUP BY e.id 
                             ORDER BY e.numero DESC");
        $edicoes = $stmt->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}
?>

<?= ui_page_header('Edições do jornal', 'Gerencie as publicações mensais, impressas e digitais de O Farol.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-edicao">' . ui_icon('plus') . ' Nova edição</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<?php if (empty($edicoes)): ?>
  <div class="card">
    <?= ui_empty('newspaper', 'Nenhuma edição cadastrada', 'Cadastre a primeira edição digital ou impressa do jornal.',
        '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-edicao">' . ui_icon('plus') . ' Nova edição</button>') ?>
  </div>
<?php else: ?>
  <div class="cols cols--3">
    <?php foreach ($edicoes as $ed): ?>
      <div class="card card--hover card--flush" style="display:flex;flex-direction:column;justify-content:space-between">
        <div style="position:relative;height:180px;background:var(--surface-2);overflow:hidden">
          <?php if (!empty($ed['capa_url'])): ?>
            <img src="<?= BASE_URL ?>/<?= e((string)$ed['capa_url']) ?>" alt="Capa Edição <?= e((string)$ed['numero']) ?>" style="width:100%;height:100%;object-fit:cover">
          <?php else: ?>
            <div style="width:100%;height:100%;display:grid;place-items:center;color:var(--muted)">Sem capa</div>
          <?php endif; ?>
          <div style="position:absolute;top:10px;left:10px;display:flex;gap:6px">
            <?= ui_badge('Nº ' . (string)$ed['numero'], 'brand') ?>
            <?php if (!empty($ed['destaque'])): ?>
              <?= ui_badge('Atual', 'accent') ?>
            <?php endif; ?>
          </div>
        </div>

        <div style="padding:16px;flex:1;display:flex;flex-direction:column;justify-content:space-between">
          <div>
            <span class="muted text-sm font-bold uppercase" style="letter-spacing:0.04em;display:block;margin-bottom:4px"><?= e($ed['mes_ano']) ?></span>
            <h3 class="strong" style="font-size:16px;line-height:1.25;margin-bottom:8px"><?= e($ed['titulo']) ?></h3>
            <p class="muted text-sm clamp-2"><?= e($ed['descricao'] ?: 'Edição oficial do jornal escolar.') ?></p>
          </div>

          <div class="card__foot" style="margin-top:14px;padding:12px 0 0 0">
            <span><?= (int)$ed['total_noticias'] ?> matérias</span>
            <a href="<?= BASE_URL ?>/admin/noticias.php?edicao=<?= (int)$ed['id'] ?>" class="link text-sm strong">Ver matérias →</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- MODAL NOVA EDIÇÃO -->
<div id="modal-nova-edicao" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-nova-edicao">
  <form method="POST" action="" class="modal__panel">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-nova-edicao">Nova Edição do Jornal</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>

    <div class="modal__body stack stack--sm">
      <div class="form-grid">
        <div class="field">
          <label class="label" for="ed-numero">Número *</label>
          <input type="number" id="ed-numero" name="numero" required min="1" placeholder="Ex: 19" class="input">
        </div>
        <div class="field">
          <label class="label" for="ed-mesano">Mês / Ano *</label>
          <input type="text" id="ed-mesano" name="mes_ano" required placeholder="Ex: Julho de 2026" class="input">
        </div>
      </div>

      <div class="field">
        <label class="label" for="ed-titulo">Título Editorial da Edição *</label>
        <input type="text" id="ed-titulo" name="titulo" required placeholder="Ex: Juventude, Clima e Futuro" class="input">
      </div>

      <div class="field">
        <label class="label" for="ed-data">Data de Publicação</label>
        <input type="date" id="ed-data" name="data_publicacao" value="<?= date('Y-m-d') ?>" class="input">
      </div>

      <div class="field">
        <label class="label" for="ed-capa">Capa da Edição (Caminho ou URL)</label>
        <input type="text" id="ed-capa" name="capa_url" value="assets/images/hero-premiacao.jpg" class="input">
      </div>

      <div class="field">
        <label class="label" for="ed-desc">Descrição</label>
        <textarea id="ed-desc" name="descricao" rows="2" placeholder="Resumo dos principais tópicos da edição..." class="textarea"></textarea>
      </div>
    </div>

    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" name="criar_edicao" value="1" class="btn btn--primary">Cadastrar Edição</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
