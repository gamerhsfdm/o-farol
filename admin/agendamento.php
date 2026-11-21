<?php
/**
 * O FAROL - Agendamento de Publicações
 * Planejamento de reportagens com data e hora programada para publicação automática.
 */
declare(strict_types=1);
$admin_title = "Agendamento";
$admin_breadcrumb = "Agendamento de publicações";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

// Ação de agendamento rápido ou alteração de data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agendar_noticia'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $noticia_id = (int)($_POST['noticia_id'] ?? 0);
        $data_agendada = trim($_POST['agendado_para'] ?? '');

        if ($noticia_id <= 0 || empty($data_agendada)) {
            $erro = "Selecione a notícia e informe a data/hora para publicação.";
        } elseif ($pdo) {
            try {
                $stmt = $pdo->prepare("UPDATE noticias SET status = 'agendado', agendado_para = ?, atualizado_em = NOW() WHERE id = ?");
                $stmt->execute([$data_agendada, $noticia_id]);
                log_audit('agendar_noticia', 'noticias', $noticia_id, "Notícia agendada para {$data_agendada}");
                $mensagem = "Publicação programada com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao agendar notícia: " . $e->getMessage();
            }
        }
    }
}

// Ação de cancelar agendamento (volta para rascunho)
if (isset($_GET['cancelar_id']) && $pdo) {
    if (!verify_csrf($_GET['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        try {
            $cancId = (int)$_GET['cancelar_id'];
            $stmt = $pdo->prepare("UPDATE noticias SET status = 'rascunho', agendado_para = NULL, atualizado_em = NOW() WHERE id = ?");
            $stmt->execute([$cancId]);
            log_audit('cancelar_agendamento', 'noticias', $cancId, "Agendamento cancelado; retornou a rascunho");
            $mensagem = "Agendamento cancelado. A matéria retornou para rascunho.";
        } catch (Exception $e) {
            $erro = "Erro ao cancelar agendamento: " . $e->getMessage();
        }
    }
}

// Consultar matérias agendadas
$agendadas = [];
$elegiveis = [];

if ($pdo) {
    try {
        $stmtAg = $pdo->query("SELECT n.*, c.nome as categoria_nome,
                               COALESCE((SELECT a.nome FROM autores a JOIN noticia_autores na ON a.id = na.autor_id WHERE na.noticia_id = n.id LIMIT 1), 'Redação') as autor_nome
                               FROM noticias n
                               JOIN categorias c ON n.categoria_id = c.id
                               WHERE n.status = 'agendado'
                               ORDER BY n.agendado_para ASC");
        $agendadas = $stmtAg->fetchAll();

        // Matérias que podem ser agendadas (rascunhos, em revisão ou aprovadas)
        $stmtEl = $pdo->query("SELECT id, titulo FROM noticias WHERE status IN ('rascunho', 'revisao', 'aprovado') ORDER BY id DESC LIMIT 50");
        $elegiveis = $stmtEl->fetchAll();
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}
?>

<?= ui_page_header('Agendamento de publicações', 'Programe matérias para serem publicadas automaticamente na data e hora desejadas.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-novo-agendamento">' . ui_icon('plus') . ' Novo agendamento</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<?php if (empty($agendadas)): ?>
  <div class="card">
    <?= ui_empty('calendar', 'Nenhuma matéria agendada', 'Não há matérias com publicação programada no momento.',
        '<button type="button" class="btn btn--primary" data-modal-open="modal-novo-agendamento">' . ui_icon('plus') . ' Agendar reportagem</button>') ?>
  </div>
<?php else: ?>
  <div class="card card--flush">
    <div class="tbl-wrap">
      <table class="tbl" style="min-width:760px">
        <thead>
          <tr>
            <th>Matéria</th>
            <th>Categoria</th>
            <th>Autor</th>
            <th>Data e hora programadas</th>
            <th>Status</th>
            <th class="t-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($agendadas as $ag): ?>
            <tr>
              <td>
                <div class="cell">
                  <div style="min-width:0">
                    <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$ag['id'] ?>" class="cell__title clamp-2"><?= e($ag['titulo']) ?></a>
                    <div class="cell__meta truncate-1">/noticia/<?= e($ag['slug']) ?></div>
                  </div>
                </div>
              </td>
              <td class="t-nowrap"><?= e($ag['categoria_nome']) ?></td>
              <td class="t-nowrap">
                <div class="cell"><span class="avatar avatar--sm avatar--soft"><?= e(ui_initials($ag['autor_nome'])) ?></span><?= e($ag['autor_nome']) ?></div>
              </td>
              <td class="t-nowrap strong">
                <?= !empty($ag['agendado_para']) ? date('d/m/Y \à\s H:i', strtotime($ag['agendado_para'])) : '—' ?>
              </td>
              <td><?= ui_news_status('agendado') ?></td>
              <td class="t-right t-nowrap">
                <span class="row-actions">
                  <a href="<?= BASE_URL ?>/admin/editor.php?id=<?= (int)$ag['id'] ?>" class="icon-btn icon-btn--sm" title="Editar no editor" aria-label="Editar"><?= ui_icon('pen') ?></a>
                  <a href="?cancelar_id=<?= (int)$ag['id'] ?>&csrf_token=<?= csrf_token() ?>" class="btn btn--sm btn--ghost"
                     data-confirm="A publicação programada será cancelada e a matéria voltará para rascunho."
                     data-confirm-title="Cancelar agendamento?" data-confirm-ok="Cancelar agendamento">Cancelar</a>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="card__foot">
      <span><?= count($agendadas) ?> matéria<?= count($agendadas) === 1 ? '' : 's' ?> agendada<?= count($agendadas) === 1 ? '' : 's' ?></span>
    </div>
  </div>
<?php endif; ?>

<!-- MODAL NOVO AGENDAMENTO -->
<div id="modal-novo-agendamento" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-agendamento">
  <form method="POST" action="" class="modal__panel">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-agendamento">Agendar publicação de matéria</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>

    <div class="modal__body stack stack--sm">
      <div class="field">
        <label class="label" for="ag-noticia">Selecione a reportagem *</label>
        <?php if (!empty($elegiveis)): ?>
          <select name="noticia_id" id="ag-noticia" required class="select">
            <option value="">Selecione uma matéria...</option>
            <?php foreach ($elegiveis as $el): ?>
              <option value="<?= (int)$el['id'] ?>">#<?= (int)$el['id'] ?> — <?= e($el['titulo']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <p class="hint">Nenhuma matéria elegível encontrada (crie um rascunho primeiro).</p>
        <?php endif; ?>
      </div>

      <div class="field">
        <label class="label" for="ag-data">Data e hora para publicação *</label>
        <input type="datetime-local" id="ag-data" name="agendado_para" required value="<?= date('Y-m-d\TH:i', strtotime('+1 day 08:00')) ?>" class="input">
      </div>
    </div>

    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" name="agendar_noticia" value="1" class="btn btn--primary">Confirmar agendamento</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
