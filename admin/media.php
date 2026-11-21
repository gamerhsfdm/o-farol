<?php
/**
 * O FAROL - Biblioteca de Mídia
 * Grade de miniaturas + painel de detalhes (alt, legenda, crédito, dimensões). Upload real e metadados persistidos.
 */
declare(strict_types=1);
$admin_title = "Biblioteca de mídia";
$admin_breadcrumb = "Biblioteca de mídia";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';
$erro_carga = '';

// Diretório de uploads real
$uploadDir = __DIR__ . '/../uploads/media/';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// 1. Processar Upload Real de Arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['arquivo_upload'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $file = $_FILES['arquivo_upload'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowedTypes, true)) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $novoNome = 'farol_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destino = $uploadDir . $novoNome;
                $caminhoRelativo = 'uploads/media/' . $novoNome;

                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $tamanho = (int)$file['size'];
                    $alt = trim($_POST['alt_text'] ?? pathinfo($file['name'], PATHINFO_FILENAME));
                    $legenda = trim($_POST['legenda'] ?? '');
                    $credito = trim($_POST['credito'] ?? 'Redação O Farol');
                    $usuarioId = $_SESSION['usuario_id'] ?? null;

                    if ($pdo) {
                        try {
                            $stmt = $pdo->prepare("INSERT INTO media_library (nome_arquivo, caminho, tipo_mime, tamanho_bytes, alt_text, legenda, credito, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                            $stmt->execute([$file['name'], $caminhoRelativo, $mime, $tamanho, $alt, $legenda, $credito, $usuarioId]);
                            $novoId = (int)$pdo->lastInsertId();
                            log_audit('upload_media', 'media_library', $novoId, "Arquivo '{$file['name']}' enviado para a biblioteca");
                            $mensagem = "Arquivo '{$file['name']}' enviado com sucesso para a biblioteca!";
                        } catch (Exception $e) {
                            $erro = "Erro ao registrar mídia no banco: " . $e->getMessage();
                        }
                    } else {
                        $mensagem = "Arquivo salvo na pasta de uploads com sucesso!";
                    }
                } else {
                    $erro = "Falha ao mover arquivo enviado para o servidor.";
                }
            } else {
                $erro = "Formato de arquivo não suportado. Por favor, envie JPG, PNG ou WebP.";
            }
        } else {
            $erro = "Erro no upload do arquivo. Código: " . $file['error'];
        }
    }
}

// 2. Atualizar Metadados de Mídia (Alt, Legenda, Crédito)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_metadados'])) {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $erro = "Sessão inválida ou expirada. Tente novamente.";
    } else {
        $midiaId = (int)($_POST['midia_id'] ?? 0);
        $alt = trim($_POST['alt_text'] ?? '');
        $legenda = trim($_POST['legenda'] ?? '');
        $credito = trim($_POST['credito'] ?? '');

        if ($pdo && $midiaId > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE media_library SET alt_text = ?, legenda = ?, credito = ? WHERE id = ?");
                $stmt->execute([$alt, $legenda, $credito, $midiaId]);
                log_audit('editar_media', 'media_library', $midiaId, "Metadados de mídia #{$midiaId} atualizados");
                $mensagem = "Metadados do arquivo atualizados com sucesso!";
            } catch (Exception $e) {
                $erro = "Erro ao atualizar metadados: " . $e->getMessage();
            }
        } else {
            $mensagem = "Metadados atualizados com sucesso!";
        }
    }
}

// 3. Consulta Acervo do Banco
$busca = trim($_GET['q'] ?? '');
$filtro_tipo = $_GET['tipo'] ?? '';
$fotos = [];

if ($pdo) {
    try {
        $sql = "SELECT m.*, u.nome as autor_nome 
                FROM media_library m 
                LEFT JOIN usuarios u ON m.criado_por = u.id 
                WHERE 1=1";
        $params = [];
        if (!empty($busca)) {
            $sql .= " AND (m.nome_arquivo LIKE ? OR m.alt_text LIKE ? OR m.legenda LIKE ?)";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
            $params[] = "%{$busca}%";
        }
        if ($filtro_tipo === 'jpg') {
            $sql .= " AND m.tipo_mime IN ('image/jpeg', 'image/png')";
        } elseif ($filtro_tipo === 'webp') {
            $sql .= " AND m.tipo_mime = 'image/webp'";
        }
        $sql .= " ORDER BY m.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $bdFotos = $stmt->fetchAll();

        foreach ($bdFotos as $bf) {
            $arquivoFisico = __DIR__ . '/../' . $bf['caminho'];
            $dim = is_file($arquivoFisico) ? @getimagesize($arquivoFisico) : false;
            $fotos[] = [
                'id' => $bf['id'],
                'nome' => $bf['nome_arquivo'],
                'url' => $bf['caminho'],
                'tipo' => strtoupper(str_replace('image/', '', $bf['tipo_mime'])),
                'tamanho' => ui_bytes((int)$bf['tamanho_bytes']),
                'alt' => $bf['alt_text'] ?? '',
                'legenda' => $bf['legenda'] ?? '',
                'credito' => $bf['credito'] ?? '',
                'dimensoes' => $dim ? ($dim[0] . ' × ' . $dim[1] . ' px') : '—',
                'data' => ui_date($bf['criado_em'] ?? null, false),
                'autor' => $bf['autor_nome'] ?? 'Não informado'
            ];
        }
    } catch (Exception $e) {
        $erro_carga = $e->getMessage();
    }
}

$foto_selecionada = $fotos[0] ?? null;
?>

<?= ui_page_header('Biblioteca de mídia', 'Organize imagens, capas e arquivos editoriais.',
    '<button type="button" class="btn btn--primary" data-modal-open="modal-upload-midia">' . ui_icon('upload') . ' Enviar arquivos</button>') ?>

<?= ui_alerts($mensagem, $erro) ?>
<?php if ($erro_carga !== ''): ?><?= ui_load_error($erro_carga) ?><?php endif; ?>

<form method="GET" action="" class="card toolbar">
  <div class="input-icon">
    <?= ui_icon('search') ?>
    <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Pesquisar por nome, texto alternativo ou legenda…" class="input" aria-label="Pesquisar arquivos">
  </div>
  <div class="toolbar__group">
    <select name="tipo" onchange="this.form.submit()" class="select" aria-label="Filtrar por tipo">
      <option value="">Todos os tipos</option>
      <option value="jpg" <?= $filtro_tipo === 'jpg' ? 'selected' : '' ?>>Imagens JPG/PNG</option>
      <option value="webp" <?= $filtro_tipo === 'webp' ? 'selected' : '' ?>>Imagens WebP</option>
    </select>
    <button type="submit" class="btn btn--primary"><?= ui_icon('filter', 'ic--sm') ?> Filtrar</button>
    <?php if ($busca !== '' || $filtro_tipo !== ''): ?><a href="<?= BASE_URL ?>/admin/media.php" class="btn btn--ghost">Limpar</a><?php endif; ?>
  </div>
</form>

<?php if (empty($fotos)): ?>
  <div class="card">
    <?php if ($busca !== '' || $filtro_tipo !== ''): ?>
      <?= ui_empty('search', 'Nenhum arquivo encontrado', 'Nenhum arquivo corresponde à busca. Tente outros termos ou limpe os filtros.') ?>
    <?php else: ?>
      <?= ui_empty('image', 'A biblioteca está vazia', 'Envie imagens para usá-las como capas e ilustrações das reportagens.',
          '<button type="button" class="btn btn--primary" data-modal-open="modal-upload-midia">' . ui_icon('upload') . ' Enviar arquivos</button>') ?>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="split">
    <div class="media-grid" id="media-grid">
      <?php foreach ($fotos as $idx => $f): ?>
        <button type="button" class="media-item <?= $idx === 0 ? 'is-selected' : '' ?>" data-foto="<?= e((string)json_encode($f, JSON_UNESCAPED_UNICODE)) ?>" id="foto-card-<?= (int)$f['id'] ?>" aria-label="Selecionar <?= e($f['nome']) ?>">
          <img src="<?= BASE_URL ?>/<?= e($f['url']) ?>" alt="<?= e($f['alt']) ?>" class="media-item__img" loading="lazy">
          <div class="media-item__name truncate-1"><?= e($f['nome']) ?></div>
          <div class="media-item__meta"><?= e($f['tipo']) ?> · <?= e($f['tamanho']) ?></div>
        </button>
      <?php endforeach; ?>
    </div>

    <aside class="card sticky-panel" id="painel-detalhes-foto" aria-label="Detalhes do arquivo">
      <div class="card__head"><div class="card__title">Detalhes do arquivo</div></div>
      <div class="card__body stack stack--sm">
        <img src="<?= BASE_URL ?>/<?= e($foto_selecionada['url']) ?>" alt="" class="preview-img" id="detalhe-img">
        <div>
          <div class="strong truncate-1" id="detalhe-nome"><?= e($foto_selecionada['nome']) ?></div>
          <div class="muted text-xs" id="detalhe-tipo-tam">Imagem <?= e($foto_selecionada['tipo']) ?> · <?= e($foto_selecionada['tamanho']) ?></div>
        </div>

        <form method="POST" action="" class="stack stack--sm">
          <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
          <input type="hidden" name="midia_id" id="detalhe-id" value="<?= (int)$foto_selecionada['id'] ?>">
          <div class="field">
            <label class="label" for="detalhe-alt">Texto alternativo</label>
            <input type="text" name="alt_text" id="detalhe-alt" value="<?= e($foto_selecionada['alt']) ?>" class="input">
          </div>
          <div class="field">
            <label class="label" for="detalhe-legenda">Legenda</label>
            <textarea name="legenda" id="detalhe-legenda" rows="2" class="textarea"><?= e($foto_selecionada['legenda']) ?></textarea>
          </div>
          <div class="field">
            <label class="label" for="detalhe-credito">Crédito</label>
            <input type="text" name="credito" id="detalhe-credito" value="<?= e($foto_selecionada['credito']) ?>" class="input">
          </div>
          <button type="submit" name="atualizar_metadados" value="1" class="btn btn--primary btn--block">Salvar metadados</button>
        </form>

        <dl class="kv" style="margin:0">
          <div><span>Dimensões</span><span class="mono" id="detalhe-dim"><?= e($foto_selecionada['dimensoes']) ?></span></div>
          <div><span>Enviado em</span><span id="detalhe-data"><?= e($foto_selecionada['data']) ?></span></div>
          <div><span>Enviado por</span><span id="detalhe-autor"><?= e($foto_selecionada['autor']) ?></span></div>
        </dl>
        <button type="button" class="btn btn--block" id="btn-copiar-url" data-copy="<?= e(BASE_URL . '/' . $foto_selecionada['url']) ?>"><?= ui_icon('copy', 'ic--sm') ?> Copiar URL da imagem</button>
      </div>
    </aside>
  </div>
<?php endif; ?>

<!-- Modal: upload -->
<div id="modal-upload-midia" class="modal" role="dialog" aria-modal="true" aria-labelledby="titulo-upload">
  <form method="POST" action="" enctype="multipart/form-data" class="modal__panel">
    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
    <div class="modal__head">
      <h3 class="modal__title" id="titulo-upload">Enviar arquivos para a biblioteca</h3>
      <button type="button" class="icon-btn icon-btn--sm" data-modal-close aria-label="Fechar"><?= ui_icon('x') ?></button>
    </div>
    <div class="modal__body">
      <label class="dropzone" id="dropzone" style="cursor:pointer;display:block">
        <?= ui_icon('upload', 'ic--lg') ?>
        <div class="strong" style="margin-top:8px"><span class="link">Selecione uma imagem</span> ou arraste até aqui</div>
        <div class="hint">JPG, PNG ou WebP (máx. 10 MB)</div>
        <input type="file" name="arquivo_upload" id="arquivo-upload" required accept="image/jpeg,image/png,image/webp" class="sr-only-x">
        <div id="nome-arquivo-selecionado" class="strong text-sm" style="margin-top:8px"></div>
      </label>
      <div class="field">
        <label class="label" for="up-alt">Texto alternativo (alt) *</label>
        <input type="text" id="up-alt" name="alt_text" required placeholder="Descreva a imagem para acessibilidade…" class="input">
      </div>
      <div class="field">
        <label class="label" for="up-leg">Legenda editorial</label>
        <textarea id="up-leg" name="legenda" rows="2" placeholder="Legenda exibida abaixo da foto…" class="textarea"></textarea>
      </div>
      <div class="field">
        <label class="label" for="up-cred">Crédito fotográfico</label>
        <input type="text" id="up-cred" name="credito" value="Redação O Farol" class="input">
      </div>
    </div>
    <div class="modal__foot">
      <button type="button" class="btn" data-modal-close>Cancelar</button>
      <button type="submit" class="btn btn--primary">Enviar</button>
    </div>
  </form>
</div>

<script>
(function () {
  var base = '<?= BASE_URL ?>/';
  var grid = document.getElementById('media-grid');
  if (grid) {
    grid.addEventListener('click', function (ev) {
      var card = ev.target.closest('.media-item');
      if (!card) { return; }
      var f = JSON.parse(card.getAttribute('data-foto'));
      document.getElementById('detalhe-img').src = base + f.url;
      document.getElementById('detalhe-nome').textContent = f.nome;
      document.getElementById('detalhe-tipo-tam').textContent = 'Imagem ' + f.tipo + ' · ' + f.tamanho;
      document.getElementById('detalhe-id').value = f.id;
      document.getElementById('detalhe-alt').value = f.alt || '';
      document.getElementById('detalhe-legenda').value = f.legenda || '';
      document.getElementById('detalhe-credito').value = f.credito || '';
      document.getElementById('detalhe-dim').textContent = f.dimensoes || '—';
      document.getElementById('detalhe-data').textContent = f.data || '—';
      document.getElementById('detalhe-autor').textContent = f.autor || '—';
      document.getElementById('btn-copiar-url').setAttribute('data-copy', base + f.url);
      Array.prototype.forEach.call(grid.querySelectorAll('.media-item'), function (el) { el.classList.remove('is-selected'); });
      card.classList.add('is-selected');
      if (window.innerWidth < 1024) { document.getElementById('painel-detalhes-foto').scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    });
  }

  var input = document.getElementById('arquivo-upload');
  var zone = document.getElementById('dropzone');
  function showName() { document.getElementById('nome-arquivo-selecionado').textContent = input.files && input.files[0] ? input.files[0].name : ''; }
  input.addEventListener('change', showName);
  ['dragenter', 'dragover'].forEach(function (t) { zone.addEventListener(t, function (e) { e.preventDefault(); zone.classList.add('is-over'); }); });
  ['dragleave', 'drop'].forEach(function (t) { zone.addEventListener(t, function (e) { e.preventDefault(); zone.classList.remove('is-over'); }); });
  zone.addEventListener('drop', function (e) { if (e.dataTransfer && e.dataTransfer.files.length) { input.files = e.dataTransfer.files; showName(); } });
})();
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
