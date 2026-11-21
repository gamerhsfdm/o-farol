<?php
/**
 * O FAROL - Gestão de Categorias (editorias)
 */
declare(strict_types=1);
$admin_title = "Categorias";
$admin_breadcrumb = "Categorias";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

// Criar nova categoria via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['criar_categoria'])) {
    $nome = trim($_POST['nome'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $cor = trim($_POST['cor'] ?? '#c0262d');
    $slug = slugify($nome);

    if (empty($nome)) {
        $erro = "O nome da categoria é obrigatório.";
    } else {
        if ($pdo) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categorias (nome, slug, descricao, cor, ativo, no_menu, ordem) VALUES (?, ?, ?, ?, 1, 1, 99)");
                $stmt->execute([$nome, $slug, $descricao, $cor]);
                $mensagem = "Categoria '{$nome}' cadastrada com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao salvar categoria: " . $e->getMessage();
            }
        } else {
            $erro = "Banco de dados inacessível no momento.";
        }
    }
}

// Alternar status da categoria (Ativar/Ocultar)
if (isset($_GET['toggle_id']) && $pdo) {
    try {
        $idToggle = (int)$_GET['toggle_id'];
        $stmt = $pdo->prepare("UPDATE categorias SET ativo = NOT ativo WHERE id = ?");
        $stmt->execute([$idToggle]);
        $mensagem = "Status da categoria atualizado com sucesso!";
    } catch (Exception $e) {
        $erro = "Não foi possível atualizar o status da categoria.";
    }
}

// Categorias do banco com contagem real de notícias
$categorias_cards = [];
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT c.*, COUNT(n.id) as qtd_noticias 
                             FROM categorias c 
                             LEFT JOIN noticias n ON c.id = n.categoria_id 
                             GROUP BY c.id 
                             ORDER BY c.ordem ASC, c.nome ASC");
        $bdCats = $stmt->fetchAll();
        foreach ($bdCats as $c) {
            $categorias_cards[] = [
                'id' => $c['id'],
                'nome' => $c['nome'],
                'desc' => $c['descricao'] ?: 'Editoria do jornal O Farol.',
                'qtd' => (int)($c['qtd_noticias'] ?? 0),
                'cor' => ui_color($c['cor'] ?? null, '#c0262d'),
                'letra' => mb_strtoupper(mb_substr($c['nome'], 0, 1)),
                'ativo' => !empty($c['ativo'])
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}
?>

<?= ui_page_header('Categorias', 'Organize as editorias e áreas temáticas do jornal.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-categoria">' . ui_icon('plus') . ' Nova categoria</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<?php if (empty($categorias_cards)): ?>
  <div class="card">
    <?= ui_empty('folder', 'Nenhuma categoria cadastrada', 'Crie a primeira editoria para organizar as reportagens do jornal.',
        '<button type="button" class="btn btn--primary" data-modal-open="modal-nova-categoria">' . ui_icon('plus') . ' Nova categoria</button>') ?>
  </div>
<?php else: ?>
  <div class="cols cols--4">
    <?php foreach ($categorias_cards as $cat): ?>
      <div class="card card--hover cat-card">
        <div class="cat-card__top">
          <div class="cat-chip" style="background: <?= e($cat['cor']) ?>"><?= e($cat['letra']) ?></div>
          <div class="dropdown" data-dropdown>
            <button type="button" class="icon-btn icon-btn--sm" data-dropdown-toggle aria-label="Ações de <?= e($cat['nome']) ?>" aria-haspopup="true" aria-expanded="false"><?= ui_icon('more') ?></button>
            <div class="dropdown__panel" style="min-width:200px">
              <a href="?toggle_id=<?= (int)$cat['id'] ?>" class="dropdown__item"><?= ui_icon($cat['ativo'] ? 'lock' : 'check-c') ?> <?= $cat['ativo'] ? 'Ocultar editoria' : 'Ativar editoria' ?></a>
              <a href="<?= BASE_URL ?>/admin/editor.php?cat_id=<?= (int)$cat['id'] ?>" class="dropdown__item"><?= ui_icon('pen') ?> Nova matéria</a>
            </div>
          </div>
        </div>
        <div>
          <h3 class="strong" style="font-size:15px"><?= e($cat['nome']) ?></h3>
          <p class="muted text-sm clamp-2" style="margin-top:4px"><?= e($cat['desc']) ?></p>
        </div>
        <div class="cat-card__foot">
          <span><?= $cat['qtd'] ?> notícia<?= $cat['qtd'] === 1 ? '' : 's' ?></span>
          <?= $cat['ativo'] ? ui_badge('Ativa', 'ok') : ui_badge('Oculta') ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Modal: nova categoria -->
<div id="modal-nova-categoria" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-nova-categoria">
  <form method="POST" action="" class="modal__panel">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-nova-categoria">Nova categoria editorial</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    <div class="modal__body">
      <div class="field">
        <label class="label" for="cat-nome">Nome da categoria *</label>
        <input type="text" id="cat-nome" name="nome" required placeholder="Ex: Robótica e Automação" class="input">
      </div>
      <div class="field">
        <label class="label" for="cat-desc">Descrição</label>
        <textarea id="cat-desc" name="descricao" rows="2" placeholder="Breve resumo dos temas abordados..." class="textarea"></textarea>
      </div>
      <div class="field">
        <label class="label" for="cat-cor">Cor temática</label>
        <div class="row">
          <input type="color" id="cat-cor" name="cor" value="#c0262d" class="color-input">
          <span class="hint">Usada na identificação visual da editoria.</span>
        </div>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" name="criar_categoria" value="1" class="btn btn--primary">Cadastrar categoria</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
