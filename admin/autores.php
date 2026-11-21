<?php
/**
 * O FAROL - Autores e Equipe Editorial
 * Grade de cards com foto, função, turma, quantidade de reportagens e acesso às matérias do autor.
 */
declare(strict_types=1);
$admin_title = "Autores e equipe";
$admin_breadcrumb = "Autores e equipe editorial";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

// Processar cadastro de novo autor via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_autor'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $cargo = trim($_POST['cargo'] ?? 'Repórter');
        $turma = trim($_POST['turma'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $foto = trim($_POST['foto'] ?? '');

        if (empty($nome)) {
            $erro = "O nome do autor é obrigatório.";
        } else {
            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO autores (nome, email, cargo, turma, biografia, foto, ativo, criado_em) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
                    $stmt->execute([$nome, $email, $cargo, $turma, $bio, $foto]);
                    $novoId = (int)$pdo->lastInsertId();
                    log_audit('criar_autor', 'autores', $novoId, "Autor '{$nome}' cadastrado");
                    $mensagem = "Autor '{$nome}' cadastrado com sucesso!";
                } catch (Exception $e) {
                    $erro = "Erro ao cadastrar autor: " . $e->getMessage();
                }
            } else {
                $erro = "Banco de dados temporariamente inacessível.";
            }
        }
    }
}

// Filtros
$funcao_filtro = $_GET['funcao'] ?? '';
$status_filtro = $_GET['status'] ?? '';
$busca = trim($_GET['q'] ?? '');

// Consulta autores do banco
$autores_cards = [];
if ($pdo) {
    try {
        $sql = "SELECT a.*, COUNT(na.noticia_id) as qtd_noticias 
                FROM autores a 
                LEFT JOIN noticia_autores na ON a.id = na.autor_id 
                WHERE 1=1";
        $params = [];
        if (!empty($busca)) {
            $sql .= " AND (a.nome LIKE ? OR a.cargo LIKE ? OR a.turma LIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }
        if (!empty($funcao_filtro)) {
            $sql .= " AND a.cargo LIKE ?";
            $params[] = "%{$funcao_filtro}%";
        }
        if ($status_filtro === 'ativos') {
            $sql .= " AND a.ativo = 1";
        } elseif ($status_filtro === 'inativos') {
            $sql .= " AND a.ativo = 0";
        }
        $sql .= " GROUP BY a.id ORDER BY a.id ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bdAutores = $stmt->fetchAll();

        foreach ($bdAutores as $aut) {
            $cargo_formatado = (string)($aut['cargo'] ?? '');
            if (!empty($aut['turma'])) {
                $cargo_formatado .= ($cargo_formatado !== '' ? ' • ' : '') . $aut['turma'];
            }
            $qtdN = (int)($aut['qtd_noticias'] ?? 0);
            $autores_cards[] = [
                'id' => $aut['id'],
                'nome' => $aut['nome'],
                'cargo' => $cargo_formatado,
                'qtd' => $qtdN . ($qtdN === 1 ? ' reportagem' : ' reportagens'),
                'foto' => $aut['foto'] ?? '',
                'ativo' => !empty($aut['ativo'])
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}
?>

<?= ui_page_header('Autores e equipe editorial', 'Gerencie estudantes, orientadores e colaboradores.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-novo-autor">' . ui_icon('user-plus') . ' Cadastrar autor</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<form method="GET" action="" class="card toolbar">
  <div class="input-icon">
    <?= ui_icon('search') ?>
    <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome, função ou turma…" class="input" aria-label="Buscar autor">
  </div>
  <div class="toolbar__group">
    <select name="funcao" onchange="this.form.submit()" class="select" aria-label="Filtrar por função">
      <option value="">Todas as funções</option>
      <?php foreach (['Repórter' => 'Repórter', 'Editor' => 'Editor', 'Colunista' => 'Colunista', 'Fotógrafo' => 'Fotógrafo', 'orientador' => 'Professor orientador'] as $v => $l): ?>
        <option value="<?= e($v) ?>" <?= $funcao_filtro === $v ? 'selected' : '' ?>><?= e($l) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="status" onchange="this.form.submit()" class="select" aria-label="Filtrar por status">
      <option value="">Todos os status</option>
      <option value="ativos" <?= $status_filtro === 'ativos' ? 'selected' : '' ?>>Ativos</option>
      <option value="inativos" <?= $status_filtro === 'inativos' ? 'selected' : '' ?>>Inativos</option>
    </select>
    <?php if ($busca !== '' || $funcao_filtro !== '' || $status_filtro !== ''): ?><a href="<?= BASE_URL ?>/admin/autores.php" class="btn btn--ghost">Limpar</a><?php endif; ?>
  </div>
</form>

<?php if (empty($autores_cards)): ?>
  <div class="card">
    <?php if ($busca !== '' || $funcao_filtro !== '' || $status_filtro !== ''): ?>
      <?= ui_empty('search', 'Nenhum autor encontrado', 'Nenhum resultado para os filtros aplicados.') ?>
    <?php else: ?>
      <?= ui_empty('users', 'Nenhum autor cadastrado', 'Cadastre os estudantes e orientadores que assinam as reportagens.',
          '<button type="button" class="btn btn--primary" data-modal-open="modal-novo-autor">' . ui_icon('user-plus') . ' Cadastrar autor</button>') ?>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="cols cols--3">
    <?php foreach ($autores_cards as $membro): ?>
      <div class="card card--hover card--flush">
        <div class="person-card__photo">
          <?php if (!empty($membro['foto'])): ?>
            <img src="<?= BASE_URL ?>/<?= e((string)$membro['foto']) ?>" alt="<?= e($membro['nome']) ?>" loading="lazy">
          <?php else: ?>
            <div class="avatar avatar--soft" style="width:100%;height:100%;border-radius:0;font-size:40px"><?= e(ui_initials($membro['nome'])) ?></div>
          <?php endif; ?>
          <span class="person-card__badge"><?= $membro['ativo'] ? ui_badge('Ativo', 'ok') : ui_badge('Inativo') ?></span>
        </div>
        <div class="person-card__body">
          <h3 class="strong" style="font-size:15px"><?= e($membro['nome']) ?></h3>
          <p class="muted text-sm" style="margin-top:2px"><?= e($membro['cargo']) ?></p>
        </div>
        <div class="person-card__foot">
          <span><?= e($membro['qtd']) ?></span>
          <a href="<?= BASE_URL ?>/admin/noticias.php?autor=<?= urlencode($membro['nome']) ?>" class="link">Ver reportagens</a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<!-- Modal: novo autor -->
<div id="modal-novo-autor" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-novo-autor">
  <form method="POST" action="" class="modal__panel">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-novo-autor">Novo autor / repórter</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    <div class="modal__body">
      <div class="field">
        <label class="label" for="aut-nome">Nome completo *</label>
        <input type="text" id="aut-nome" name="nome" required placeholder="Ex: Júlia Medeiros" class="input">
      </div>
      <div class="form-grid">
        <div class="field">
          <label class="label" for="aut-cargo">Função / cargo</label>
          <select id="aut-cargo" name="cargo" class="select">
            <option value="Repórter">Repórter</option>
            <option value="Editor de esportes">Editor de esportes</option>
            <option value="Colunista">Colunista</option>
            <option value="Fotógrafo">Fotógrafo</option>
            <option value="Professor orientador">Professor orientador</option>
          </select>
        </div>
        <div class="field">
          <label class="label" for="aut-turma">Turma / série</label>
          <input type="text" id="aut-turma" name="turma" placeholder="Ex: 2º ano B" class="input">
        </div>
      </div>
      <div class="field">
        <label class="label" for="aut-foto">Foto do autor <small>(caminho ou URL, opcional)</small></label>
        <input type="text" id="aut-foto" name="foto" placeholder="uploads/media/arquivo.jpg" class="input">
      </div>
      <div class="field">
        <label class="label" for="aut-bio">Biografia curta</label>
        <textarea id="aut-bio" name="bio" rows="2" placeholder="Breve apresentação para a página editorial..." class="textarea"></textarea>
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" name="cadastrar_autor" value="1" class="btn btn--primary">Cadastrar autor</button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
