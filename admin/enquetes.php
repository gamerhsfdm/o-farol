<?php
/**
 * O FAROL - Gestão de Enquetes
 * Lista de enquetes com total de votos + painel de resultados.
 */
declare(strict_types=1);
$admin_title = "Enquetes";
$admin_breadcrumb = "Enquetes";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

// Criar nova enquete via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_enquete'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $pergunta = trim($_POST['pergunta'] ?? '');
        $opcoes_texto = trim($_POST['opcoes'] ?? '');
        $data_encerramento = trim($_POST['data_encerramento'] ?? date('Y-m-d', strtotime('+7 days')));

        if (empty($pergunta)) {
            $erro = "A pergunta da enquete é obrigatória.";
        } else {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO enquetes (pergunta, data_inicio, data_fim, ativo, criado_em) VALUES (?, NOW(), ?, 1, NOW())");
                    $stmt->execute([$pergunta, $data_encerramento]);
                    $enqueteId = (int)$pdo->lastInsertId();

                    $linhas = explode("\n", $opcoes_texto);
                    $ordem = 1;
                    $stmtOp = $pdo->prepare("INSERT INTO enquete_opcoes (enquete_id, opcao, votos_count, ordem) VALUES (?, ?, 0, ?)");
                    foreach ($linhas as $linha) {
                        $opcao = trim($linha);
                        if (!empty($opcao)) {
                            $stmtOp->execute([$enqueteId, $opcao, $ordem++]);
                        }
                    }
                    log_audit('criar_enquete', 'enquetes', $enqueteId, "Enquete '{$pergunta}' criada");
                    $mensagem = "Enquete criada e ativada com sucesso!";
                } catch (Exception $e) {
                    $erro = "Erro ao cadastrar enquete: " . $e->getMessage();
                }
            } else {
                $erro = "Banco de dados temporariamente inacessível.";
            }
        }
    }
}

// Consulta enquetes do banco com total de votos
$enquetes_tabela = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT e.*, COALESCE(SUM(o.votos_count), 0) as total_votos 
                             FROM enquetes e 
                             LEFT JOIN enquete_opcoes o ON e.id = o.enquete_id 
                             GROUP BY e.id 
                             ORDER BY e.id DESC");
        $bdEnquetes = $stmt->fetchAll();
        foreach ($bdEnquetes as $eq) {
            $status_txt = !empty($eq['ativo']) ? 'Ativa' : 'Encerrada';
            $enquetes_tabela[] = [
                'id' => $eq['id'],
                'pergunta' => $eq['pergunta'],
                'votos' => (string)($eq['total_votos'] ?? 0),
                'data' => !empty($eq['data_fim']) ? date('d/m/Y', strtotime($eq['data_fim'])) : 'Sem prazo',
                'status' => $status_txt
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}

// Enquete exibida no painel de resultados (?ver=ID ou a mais recente)
$ver_id = (int)($_GET['ver'] ?? 0);
$enquete_destaque = $enquetes_tabela[0] ?? null;
foreach ($enquetes_tabela as $eqt) {
    if ($ver_id > 0 && (int)$eqt['id'] === $ver_id) {
        $enquete_destaque = $eqt;
        break;
    }
}
$resultados_opcoes = [];
if ($enquete_destaque && $pdo) {
    try {
        $stmtOp = $pdo->prepare("SELECT opcao, votos_count FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem ASC");
        $stmtOp->execute([$enquete_destaque['id']]);
        $ops = $stmtOp->fetchAll();
        $totalV = array_sum(array_column($ops, 'votos_count')) ?: 1;
        foreach ($ops as $op) {
            $resultados_opcoes[] = [
                'opcao' => $op['opcao'],
                'votos' => (int)$op['votos_count'],
                'percentual' => round(($op['votos_count'] / $totalV) * 100)
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}
?>

<?= ui_page_header('Enquetes', 'Crie perguntas e acompanhe a participação dos leitores.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-enquete">' . ui_icon('plus') . ' Nova enquete</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<?php if (empty($enquetes_tabela)): ?>
  <div class="card">
    <?= ui_empty('chart', 'Nenhuma enquete cadastrada', 'Crie uma pergunta para ouvir a comunidade escolar na seção Voz do Aluno.',
        '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-enquete">' . ui_icon('plus') . ' Nova enquete</button>') ?>
  </div>
<?php else: ?>
  <div class="split split--wide">
    <div class="card card--flush">
      <div class="tbl-wrap">
        <table class="tbl" style="min-width:560px">
          <thead>
            <tr><th>Pergunta</th><th>Votos</th><th>Encerramento</th><th>Status</th><th class="t-right">Resultados</th></tr>
          </thead>
          <tbody>
            <?php foreach ($enquetes_tabela as $eq): ?>
              <tr>
                <td><span class="cell__title"><?= e($eq['pergunta']) ?></span></td>
                <td class="strong"><?= e($eq['votos']) ?></td>
                <td class="t-nowrap muted"><?= e($eq['data']) ?></td>
                <td><?= $eq['status'] === 'Ativa' ? ui_badge('Ativa', 'ok') : ui_badge('Encerrada') ?></td>
                <td class="t-right">
                  <?php if ($enquete_destaque && (int)$enquete_destaque['id'] === (int)$eq['id']): ?>
                    <?= ui_badge('Em exibição', 'brand') ?>
                  <?php else: ?>
                    <a href="?ver=<?= (int)$eq['id'] ?>" class="btn btn--ghost btn--sm">Ver</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card__head">
        <div>
          <div class="card__title">Resultados</div>
          <div class="card__sub"><?= e($enquete_destaque['votos'] ?? '0') ?> votos no total</div>
        </div>
        <?= ($enquete_destaque['status'] ?? '') === 'Ativa' ? ui_badge('Ativa', 'ok') : ui_badge('Encerrada') ?>
      </div>
      <div class="card__body">
        <h4 style="font-size:17px;line-height:1.35;font-weight:600;margin-bottom:20px"><?= e($enquete_destaque['pergunta'] ?? '') ?></h4>
        <?php if (empty($resultados_opcoes)): ?>
          <p class="muted text-sm">Esta enquete ainda não tem opções de resposta.</p>
        <?php else: ?>
          <div class="stack stack--sm">
            <?php foreach ($resultados_opcoes as $res): ?>
              <div>
                <div class="row row--between text-sm" style="margin-bottom:6px">
                  <span><?= e($res['opcao']) ?></span>
                  <span class="strong"><?= (int)$res['percentual'] ?>% <span class="faint" style="font-weight:400">(<?= (int)$res['votos'] ?>)</span></span>
                </div>
                <div class="poll-bar"><i style="width: <?= (int)$res['percentual'] ?>%"></i></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Modal: nova enquete -->
<div id="modal-nova-enquete" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-nova-enquete">
  <form method="POST" action="" class="modal__panel">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-nova-enquete">Nova enquete escolar</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    <div class="modal__body">
      <div class="field">
        <label class="label" for="enq-pergunta">Pergunta da enquete *</label>
        <input type="text" id="enq-pergunta" name="pergunta" required placeholder="Ex: Qual atração você prefere na Feira Cultural?" class="input">
      </div>
      <div class="field">
        <label class="label" for="enq-opcoes">Opções de resposta *</label>
        <textarea id="enq-opcoes" name="opcoes" rows="4" required placeholder="Uma opção por linha" class="textarea"></textarea>
        <span class="hint">Digite uma opção por linha.</span>
      </div>
      <div class="field">
        <label class="label" for="enq-data">Data de encerramento</label>
        <input type="date" id="enq-data" name="data_encerramento" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="input">
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" name="criar_enquete" value="1" class="btn btn--primary">Publicar enquete</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
