<?php
/**
 * O FAROL - Gestão e Criação Avançada de Enquetes Escolares
 * Criação com opções dinâmicas, cores, escolha única/múltipla, categorias e controle de exibição.
 */
declare(strict_types=1);
$admin_title = "Enquetes";
$admin_breadcrumb = "Enquetes";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = $_GET['msg'] ?? '';
$erro = $_GET['erro'] ?? '';
$erro_carga = '';

// 1. Criar nova enquete avançada via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_enquete'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $pergunta = trim($_POST['pergunta'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $categoria_tag = trim($_POST['categoria_tag'] ?? 'Geral');
        $permitir_multipla = isset($_POST['permitir_multipla']) ? 1 : 0;
        $max_escolhas = max(1, (int)($_POST['max_escolhas'] ?? 1));
        $exibir_resultados_apos = in_array($_POST['exibir_resultados_apos'] ?? '', ['sempre', 'apos_votar', 'apos_encerrar'], true) ? $_POST['exibir_resultados_apos'] : 'apos_votar';
        $destaque_sidebar = isset($_POST['destaque_sidebar']) ? 1 : 0;
        $data_encerramento = !empty($_POST['data_encerramento']) ? trim($_POST['data_encerramento']) : null;

        // Processa opções estruturadas enviadas dinamicamente
        $opcoes_array = array_filter(array_map('trim', (array)($_POST['opcoes_texto'] ?? [])));
        $cores_array = (array)($_POST['opcoes_cor'] ?? []);

        // Fallback: se o usuário usou o textarea rápido
        if (empty($opcoes_array) && !empty($_POST['opcoes_rapidas'])) {
            $linhas = explode("\n", trim($_POST['opcoes_rapidas']));
            foreach ($linhas as $l) {
                $t = trim($l);
                if (!empty($t)) $opcoes_array[] = $t;
            }
        }

        if (empty($pergunta)) {
            $erro = "A pergunta da enquete é obrigatória.";
        } elseif (count($opcoes_array) < 2) {
            $erro = "A enquete precisa de no mínimo duas opções de resposta.";
        } else {
            if ($pdo) {
                try {
                    $pdo->beginTransaction();

                    // Se esta for marcada como destaque da sidebar, desativa destaque das outras
                    if ($destaque_sidebar) {
                        $pdo->exec("UPDATE enquetes SET destaque_sidebar = 0");
                    }

                    $stmt = $pdo->prepare("INSERT INTO enquetes (pergunta, descricao, permitir_multipla, max_escolhas, exibir_resultados_apos, categoria_tag, destaque_sidebar, ativo, data_inicio, data_fim, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), ?, NOW())");
                    $stmt->execute([
                        $pergunta, $descricao, $permitir_multipla, $max_escolhas,
                        $exibir_resultados_apos, $categoria_tag, $destaque_sidebar, $data_encerramento
                    ]);
                    $enqueteId = (int)$pdo->lastInsertId();

                    $stmtOp = $pdo->prepare("INSERT INTO enquete_opcoes (enquete_id, opcao, votos_count, cor_destaque, ordem) VALUES (?, ?, 0, ?, ?)");
                    $ordem = 1;
                    $palette = ['#0f5368', '#a51f2a', '#15803d', '#ca8a04', '#475569', '#7c3aed'];

                    foreach ($opcoes_array as $idx => $textoOpcao) {
                        $corOpcao = !empty($cores_array[$idx]) ? $cores_array[$idx] : ($palette[$idx % count($palette)]);
                        $stmtOp->execute([$enqueteId, $textoOpcao, $corOpcao, $ordem++]);
                    }

                    $pdo->commit();
                    log_audit('criar_enquete', 'enquetes', $enqueteId, "Enquete '{$pergunta}' criada");
                    header('Location: ' . BASE_URL . '/admin/enquetes.php?msg=' . urlencode('Enquete criada e publicada com sucesso!'));
                    exit;
                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $erro = "Erro ao cadastrar enquete: " . $e->getMessage();
                }
            } else {
                $erro = "Banco de dados temporariamente inacessível.";
            }
        }
    }
}

// 2. Alternar status Ativa / Encerrada ou Destaque
if (isset($_GET['acao']) && $pdo) {
    if (!verify_csrf($_GET['csrf_token'] ?? '')) {
        $erro = "Token de segurança inválido.";
    } else {
        $acao = $_GET['acao'];
        $idAcao = (int)($_GET['id'] ?? 0);

        try {
            if ($acao === 'toggle_status') {
                $stmtT = $pdo->prepare("UPDATE enquetes SET ativo = 1 - ativo WHERE id = ?");
                $stmtT->execute([$idAcao]);
                header('Location: ' . BASE_URL . '/admin/enquetes.php?msg=' . urlencode('Status da enquete atualizado com sucesso!'));
                exit;
            } elseif ($acao === 'destacar') {
                $pdo->exec("UPDATE enquetes SET destaque_sidebar = 0");
                $stmtD = $pdo->prepare("UPDATE enquetes SET destaque_sidebar = 1, ativo = 1 WHERE id = ?");
                $stmtD->execute([$idAcao]);
                header('Location: ' . BASE_URL . '/admin/enquetes.php?msg=' . urlencode('Enquete definida como destaque principal na capa!'));
                exit;
            } elseif ($acao === 'excluir') {
                $stmtE = $pdo->prepare("DELETE FROM enquetes WHERE id = ?");
                $stmtE->execute([$idAcao]);
                log_audit('excluir_enquete', 'enquetes', $idAcao, "Enquete #{$idAcao} excluída");
                header('Location: ' . BASE_URL . '/admin/enquetes.php?msg=' . urlencode('Enquete excluída com sucesso!'));
                exit;
            }
        } catch (Exception $e) {
            $erro = "Erro ao executar ação: " . $e->getMessage();
        }
    }
}

// 3. Consulta enquetes do banco com total de votos
$enquetes_tabela = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT e.*, COALESCE(SUM(o.votos_count), 0) as total_votos, COUNT(o.id) as total_opcoes 
                             FROM enquetes e 
                             LEFT JOIN enquete_opcoes o ON e.id = o.enquete_id 
                             GROUP BY e.id 
                             ORDER BY e.destaque_sidebar DESC, e.id DESC");
        $bdEnquetes = $stmt->fetchAll();
        foreach ($bdEnquetes as $eq) {
            $isEncerrada = empty($eq['ativo']) || (!empty($eq['data_fim']) && strtotime($eq['data_fim'] . ' 23:59:59') < time());
            $status_txt = $isEncerrada ? 'Encerrada' : 'Ativa';

            $enquetes_tabela[] = [
                'id' => (int)$eq['id'],
                'pergunta' => $eq['pergunta'],
                'descricao' => $eq['descricao'] ?? '',
                'categoria_tag' => $eq['categoria_tag'] ?? 'Geral',
                'permitir_multipla' => (int)($eq['permitir_multipla'] ?? 0) === 1,
                'max_escolhas' => (int)($eq['max_escolhas'] ?? 1),
                'exibir_resultados_apos' => $eq['exibir_resultados_apos'] ?? 'apos_votar',
                'destaque_sidebar' => (int)($eq['destaque_sidebar'] ?? 0) === 1,
                'total_opcoes' => (int)$eq['total_opcoes'],
                'votos' => (string)($eq['total_votos'] ?? 0),
                'data' => !empty($eq['data_fim']) ? date('d/m/Y', strtotime($eq['data_fim'])) : 'Sem prazo',
                'status' => $status_txt
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}

// Enquete exibida no painel de resultados (?ver=ID ou a de destaque / mais recente)
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
        $stmtOp = $pdo->prepare("SELECT opcao, votos_count, cor_destaque FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem ASC");
        $stmtOp->execute([$enquete_destaque['id']]);
        $ops = $stmtOp->fetchAll();
        $totalV = array_sum(array_column($ops, 'votos_count')) ?: 1;
        foreach ($ops as $op) {
            $resultados_opcoes[] = [
                'opcao' => $op['opcao'],
                'cor' => $op['cor_destaque'] ?: '#0f5368',
                'votos' => (int)$op['votos_count'],
                'percentual' => round(($op['votos_count'] / $totalV) * 100)
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $erro_carga ?: $e->getMessage();
    }
}
?>

<?= ui_page_header('Enquetes', 'Crie perguntas, defina regras de voto e acompanhe a participação da comunidade escolar.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-enquete">' . ui_icon('plus') . ' Nova enquete avançada</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<?php if (empty($enquetes_tabela)): ?>
  <div class="card">
    <?= ui_empty('chart', 'Nenhuma enquete cadastrada', 'Crie uma pergunta para ouvir a comunidade escolar na seção Voz do Aluno.',
        '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-enquete">' . ui_icon('plus') . ' Criar enquete</button>') ?>
  </div>
<?php else: ?>
  <div class="split split--wide">
    
    <!-- Tabela de Enquetes -->
    <div class="card card--flush">
      <div class="tbl-wrap">
        <table class="tbl" style="min-width:620px">
          <thead>
            <tr>
              <th>Pergunta &amp; Categoria</th>
              <th>Tipo de Voto</th>
              <th>Votos</th>
              <th>Prazo</th>
              <th>Status</th>
              <th class="t-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($enquetes_tabela as $eq): ?>
              <tr>
                <td>
                  <div style="min-width:0">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px">
                      <span class="badge badge--brand badge--sm"><?= e($eq['categoria_tag']) ?></span>
                      <?php if ($eq['destaque_sidebar']): ?>
                        <span class="badge badge--ok badge--sm" title="Exibida na Capa do Portal">★ Capa</span>
                      <?php endif; ?>
                    </div>
                    <span class="cell__title clamp-2" style="font-weight:600"><?= e($eq['pergunta']) ?></span>
                    <span class="muted text-sm"><?= $eq['total_opcoes'] ?> opções de resposta</span>
                  </div>
                </td>
                <td class="t-nowrap">
                  <?php if ($eq['permitir_multipla']): ?>
                    <span class="badge badge--neutral">Múltipla (até <?= $eq['max_escolhas'] ?>)</span>
                  <?php else: ?>
                    <span class="muted text-sm">Escolha única</span>
                  <?php endif; ?>
                </td>
                <td class="strong" style="font-variant-numeric:tabular-nums"><?= e($eq['votos']) ?></td>
                <td class="t-nowrap muted"><?= e($eq['data']) ?></td>
                <td>
                  <?= $eq['status'] === 'Ativa' ? ui_badge('Ativa', 'ok') : ui_badge('Encerrada') ?>
                </td>
                <td class="t-right">
                  <span class="row-actions" style="display:inline-flex;gap:4px">
                    <?php if ($enquete_destaque && (int)$enquete_destaque['id'] === (int)$eq['id']): ?>
                      <span class="btn btn--sm btn--primary" style="pointer-events:none">Vendo</span>
                    <?php else: ?>
                      <a href="?ver=<?= (int)$eq['id'] ?>" class="btn btn--ghost btn--sm">Ver</a>
                    <?php endif; ?>

                    <?php if (!$eq['destaque_sidebar']): ?>
                      <a href="?acao=destacar&id=<?= (int)$eq['id'] ?>&csrf_token=<?= csrf_token() ?>" class="icon-btn icon-btn--sm" title="Definir como enquete da capa" aria-label="Destacar"><?= ui_icon('star', 'ic--sm') ?></a>
                    <?php endif; ?>

                    <a href="?acao=toggle_status&id=<?= (int)$eq['id'] ?>&csrf_token=<?= csrf_token() ?>" class="icon-btn icon-btn--sm" title="<?= $eq['status'] === 'Ativa' ? 'Encerrar enquete' : 'Reativar enquete' ?>">
                      <?= ui_icon($eq['status'] === 'Ativa' ? 'close' : 'check', 'ic--sm') ?>
                    </a>

                    <a href="?acao=excluir&id=<?= (int)$eq['id'] ?>&csrf_token=<?= csrf_token() ?>" class="icon-btn icon-btn--sm icon-btn--danger" title="Excluir enquete"
                       data-confirm="A enquete e todos os seus votos serão apagados permanentemente."
                       data-confirm-title="Excluir enquete?" data-confirm-ok="Excluir"><?= ui_icon('trash', 'ic--sm') ?></a>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Painel Lateral de Resultados e Visualização -->
    <div class="card">
      <div class="card__head">
        <div>
          <div class="card__title">Resultados em Tempo Real</div>
          <div class="card__sub"><?= e($enquete_destaque['votos'] ?? '0') ?> votos apurados</div>
        </div>
        <div style="display:flex;gap:4px">
          <?= ($enquete_destaque['status'] ?? '') === 'Ativa' ? ui_badge('Ativa', 'ok') : ui_badge('Encerrada') ?>
          <?php if (!empty($enquete_destaque['destaque_sidebar'])): ?>
            <?= ui_badge('Na Capa', 'brand') ?>
          <?php endif; ?>
        </div>
      </div>
      <div class="card__body">
        <span class="badge badge--neutral badge--sm" style="margin-bottom:8px"><?= e($enquete_destaque['categoria_tag'] ?? 'Geral') ?></span>
        <h4 style="font-size:17px;line-height:1.35;font-weight:600;margin-bottom:8px"><?= e($enquete_destaque['pergunta'] ?? '') ?></h4>
        <?php if (!empty($enquete_destaque['descricao'])): ?>
          <p class="muted text-sm" style="margin-bottom:18px"><?= e($enquete_destaque['descricao']) ?></p>
        <?php endif; ?>

        <?php if (empty($resultados_opcoes)): ?>
          <p class="muted text-sm">Esta enquete ainda não tem opções de resposta.</p>
        <?php else: ?>
          <div class="stack stack--sm">
            <?php foreach ($resultados_opcoes as $res): ?>
              <div>
                <div class="row row--between text-sm" style="margin-bottom:6px">
                  <span style="font-weight:500"><?= e($res['opcao']) ?></span>
                  <span class="strong"><?= (int)$res['percentual'] ?>% <span class="faint" style="font-weight:400">(<?= (int)$res['votos'] ?>)</span></span>
                </div>
                <div class="poll-bar">
                  <i style="width: <?= (int)$res['percentual'] ?>%; background: <?= e($res['cor']) ?>"></i>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
      <div class="card__foot" style="font-size:12px;color:var(--text-muted)">
        <span>Regra: <?= !empty($enquete_destaque['permitir_multipla']) ? 'Múltipla escolha (até ' . $enquete_destaque['max_escolhas'] . ' opções)' : 'Voto único' ?></span>
        <span>Prazo: <?= e($enquete_destaque['data'] ?? '') ?></span>
      </div>
    </div>

  </div>
<?php endif; ?>

<!-- Modal Avançado: Nova Enquete com Construtor de Opções e Configurações -->
<div id="modal-nova-enquete" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-nova-enquete">
  <form method="POST" action="" class="modal__panel" style="max-width:680px">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-nova-enquete">Criar Nova Enquete Escolar</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    
    <div class="modal__body stack stack--md" style="max-height:75vh;overflow-y:auto">
      
      <!-- 1. Pergunta e Descrição -->
      <div class="field">
        <label class="label" for="enq-pergunta">Pergunta da Enquete *</label>
        <input type="text" id="enq-pergunta" name="pergunta" required placeholder="Ex: Qual tema você prefere para a Semana da Ciência e Inovação?" class="input" style="font-size:14px;font-weight:600">
      </div>

      <div class="field">
        <label class="label" for="enq-descricao">Contexto ou Descrição Breve <small>(Opcional)</small></label>
        <textarea id="enq-descricao" name="descricao" rows="2" placeholder="Explique brevemente para os estudantes o propósito da votação..." class="textarea"></textarea>
      </div>

      <!-- 2. Categoria / Tag Temática -->
      <div class="row row--wrap" style="gap:12px">
        <div class="field" style="flex:1;min-width:200px">
          <label class="label" for="enq-cat-tag">Categoria Temática</label>
          <select id="enq-cat-tag" name="categoria_tag" class="select">
            <option value="Geral">Geral / Opinião</option>
            <option value="Cultura &amp; Eventos">Cultura &amp; Eventos</option>
            <option value="Estrutura Escolar">Estrutura Escolar &amp; Espaços</option>
            <option value="Refeitório &amp; Cardápio">Refeitório &amp; Alimentação</option>
            <option value="Jogos &amp; Esportes">Jogos &amp; Esportes Interclasses</option>
            <option value="Biblioteca &amp; Leituras">Biblioteca &amp; Leituras</option>
          </select>
        </div>

        <div class="field" style="flex:1;min-width:180px">
          <label class="label" for="enq-data">Data de Encerramento</label>
          <input type="date" id="enq-data" name="data_encerramento" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" class="input">
        </div>
      </div>

      <!-- 3. Construtor Dinâmico de Opções de Resposta -->
      <div class="card" style="padding:14px;background:var(--surface-2);border:1px solid var(--line)">
        <div class="row row--between" style="margin-bottom:10px">
          <label class="label" style="margin-bottom:0">Opções de Resposta * <small>(Personalize texto e cores)</small></label>
          <button type="button" class="btn btn--sm" onclick="adicionarOpcaoEnquete()"><?= ui_icon('plus', 'ic--sm') ?> Adicionar Opção</button>
        </div>

        <div id="container-opcoes-dinamicas" class="stack stack--sm">
          <!-- Opções iniciais padrão -->
          <div class="opcao-linha row" style="gap:8px;align-items:center">
            <span class="faint text-sm" style="width:20px;text-align:center">1</span>
            <input type="text" name="opcoes_texto[]" required placeholder="Primeira opção de resposta" class="input" style="flex:1">
            <input type="color" name="opcoes_cor[]" value="#0f5368" title="Cor da barra de progresso" style="width:36px;height:36px;padding:2px;border:1px solid var(--line);border-radius:var(--r-sm);cursor:pointer">
            <button type="button" class="icon-btn icon-btn--sm icon-btn--danger" onclick="removerOpcaoEnquete(this)" title="Remover"><?= ui_icon('trash', 'ic--sm') ?></button>
          </div>

          <div class="opcao-linha row" style="gap:8px;align-items:center">
            <span class="faint text-sm" style="width:20px;text-align:center">2</span>
            <input type="text" name="opcoes_texto[]" required placeholder="Segunda opção de resposta" class="input" style="flex:1">
            <input type="color" name="opcoes_cor[]" value="#a51f2a" title="Cor da barra de progresso" style="width:36px;height:36px;padding:2px;border:1px solid var(--line);border-radius:var(--r-sm);cursor:pointer">
            <button type="button" class="icon-btn icon-btn--sm icon-btn--danger" onclick="removerOpcaoEnquete(this)" title="Remover"><?= ui_icon('trash', 'ic--sm') ?></button>
          </div>

          <div class="opcao-linha row" style="gap:8px;align-items:center">
            <span class="faint text-sm" style="width:20px;text-align:center">3</span>
            <input type="text" name="opcoes_texto[]" placeholder="Terceira opção (opcional)" class="input" style="flex:1">
            <input type="color" name="opcoes_cor[]" value="#15803d" title="Cor da barra de progresso" style="width:36px;height:36px;padding:2px;border:1px solid var(--line);border-radius:var(--r-sm);cursor:pointer">
            <button type="button" class="icon-btn icon-btn--sm icon-btn--danger" onclick="removerOpcaoEnquete(this)" title="Remover"><?= ui_icon('trash', 'ic--sm') ?></button>
          </div>
        </div>

        <details style="margin-top:10px">
          <summary class="link text-sm" style="cursor:pointer">Ou cole várias opções de uma vez (uma por linha)</summary>
          <div style="margin-top:8px">
            <textarea name="opcoes_rapidas" rows="3" placeholder="Opção 1&#10;Opção 2&#10;Opção 3" class="textarea"></textarea>
          </div>
        </details>
      </div>

      <!-- 4. Regras e Modos de Votação -->
      <div class="card" style="padding:14px;border:1px solid var(--line)">
        <div class="strong text-sm" style="margin-bottom:10px">Regras de Participação e Privacidade</div>
        
        <div class="stack stack--sm">
          <label class="row" style="gap:8px;cursor:pointer">
            <input type="checkbox" name="permitir_multipla" id="check-multipla" value="1" onchange="toggleMultiplaConfig()">
            <span>Permitir <strong>Múltipla Escolha</strong> (aluno pode marcar mais de uma resposta)</span>
          </label>

          <div id="wrap-max-escolhas" style="display:none;margin-left:24px" class="field">
            <label class="label" for="enq-max-escolhas">Máximo de escolhas permitidas por participante:</label>
            <input type="number" id="enq-max-escolhas" name="max_escolhas" value="2" min="2" max="10" class="input" style="width:100px">
          </div>

          <div class="field" style="margin-top:6px">
            <label class="label" for="enq-exibir-quando">Exibição dos Resultados Parciais:</label>
            <select id="enq-exibir-quando" name="exibir_resultados_apos" class="select">
              <option value="apos_votar" selected>Imediatamente após o voto do participante</option>
              <option value="sempre">Sempre visíveis antes e depois de votar</option>
              <option value="apos_encerrar">Apenas quando a enquete estiver encerrada (voto secreto)</option>
            </select>
          </div>

          <label class="row" style="gap:8px;cursor:pointer;margin-top:6px">
            <input type="checkbox" name="destaque_sidebar" value="1" checked>
            <span>Definir como <strong>Destaque Principal na Capa</strong> (Voz do Aluno)</span>
          </label>
        </div>
      </div>

    </div>

    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" name="criar_enquete" value="1" class="btn btn--primary">Publicar Enquete</button>
    </div>
  </form>
</div>

<script>
function toggleMultiplaConfig() {
  var ck = document.getElementById('check-multipla');
  var wrap = document.getElementById('wrap-max-escolhas');
  if (ck && wrap) {
    wrap.style.display = ck.checked ? 'block' : 'none';
  }
}

function atualizarNumeracaoOpcoes() {
  var linhas = document.querySelectorAll('#container-opcoes-dinamicas .opcao-linha');
  linhas.forEach(function(l, idx){
    var span = l.querySelector('span');
    if (span) span.textContent = (idx + 1);
  });
}

function adicionarOpcaoEnquete() {
  var container = document.getElementById('container-opcoes-dinamicas');
  if (!container) return;
  var count = container.querySelectorAll('.opcao-linha').length + 1;
  var palette = ['#0f5368', '#a51f2a', '#15803d', '#ca8a04', '#475569', '#7c3aed', '#0284c7'];
  var cor = palette[(count - 1) % palette.length];

  var div = document.createElement('div');
  div.className = 'opcao-linha row';
  div.style.cssText = 'gap:8px;align-items:center';
  div.innerHTML = '<span class="faint text-sm" style="width:20px;text-align:center">' + count + '</span>' +
                  '<input type="text" name="opcoes_texto[]" required placeholder="Nova opção de resposta" class="input" style="flex:1">' +
                  '<input type="color" name="opcoes_cor[]" value="' + cor + '" title="Cor da barra de progresso" style="width:36px;height:36px;padding:2px;border:1px solid var(--line);border-radius:var(--r-sm);cursor:pointer">' +
                  '<button type="button" class="icon-btn icon-btn--sm icon-btn--danger" onclick="removerOpcaoEnquete(this)" title="Remover"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>';
  container.appendChild(div);
  atualizarNumeracaoOpcoes();
}

function removerOpcaoEnquete(btn) {
  var container = document.getElementById('container-opcoes-dinamicas');
  if (container.querySelectorAll('.opcao-linha').length <= 2) {
    alert('A enquete precisa de no mínimo duas opções de resposta.');
    return;
  }
  btn.closest('.opcao-linha').remove();
  atualizarNumeracaoOpcoes();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
