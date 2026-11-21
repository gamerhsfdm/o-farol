<?php
/**
 * O FAROL - Configurações Gerais e Institucionais do Jornal
 */
declare(strict_types=1);
$admin_title = "Configurações Gerais";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verify_csrf($_POST['csrf_token'] ?? '')) {
        $chaves = [
            'site_nome', 'site_subtitulo', 'site_descricao', 'edicao_atual_numero',
            'edicao_atual_data', 'corpo_editorial_discente', 'orientacao_pedagogica',
            'links_oficiais', 'sede_redacao', 'direitos_autorais',
            'banner_chamada_titulo', 'banner_chamada_texto', 'banner_chamada_botao'
        ];

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach ($chaves as $ch) {
                if (isset($_POST[$ch])) {
                    $stmt->execute([$ch, trim($_POST[$ch])]);
                }
            }
            $pdo->commit();
            log_audit('atualizar_config', 'configuracoes', 0, 'Configurações institucionais do jornal atualizadas');
            $sucesso = 'Configurações institucionais atualizadas com sucesso!';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $erro = 'Erro ao salvar configurações: ' . $e->getMessage();
        }
    } else {
        $erro = 'Sessão expirada. Recarregue a página e tente novamente.';
    }
}

$cfg = get_site_settings();
?>

<form action="<?= BASE_URL ?>/admin/settings.php" method="POST">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <?= ui_page_header('Configurações gerais', 'Edite a identidade do portal, a edição atual e as colunas do rodapé institucional.',
      '<button type="submit" class="btn btn--primary">' . ui_icon('save') . ' Salvar configurações</button>') ?>

  <?= ui_alerts((string)$sucesso, (string)$erro) ?>

  <div class="card">
    <div class="form-section">
      <div class="form-section__title">Identidade do portal e edição ativa</div>
      <div class="form-section__sub">Nome, subtítulo e a edição exibida no topo do site.</div>
      <div class="form-grid">
        <div class="field"><label class="label" for="cfg-site_nome">Nome do jornal</label><input type="text" id="cfg-site_nome" name="site_nome" value="<?= e((string)($cfg['site_nome'])) ?>"  class="input"></div>
        <div class="field"><label class="label" for="cfg-site_subtitulo">Subtítulo institucional</label><input type="text" id="cfg-site_subtitulo" name="site_subtitulo" value="<?= e((string)($cfg['site_subtitulo'])) ?>"  class="input"></div>
        <div class="field"><label class="label" for="cfg-edicao_atual_numero">Número da edição atual</label><input type="text" id="cfg-edicao_atual_numero" name="edicao_atual_numero" value="<?= e((string)($cfg['edicao_atual_numero'])) ?>"  class="input"></div>
        <div class="field"><label class="label" for="cfg-edicao_atual_data">Data / mês da edição atual</label><input type="text" id="cfg-edicao_atual_data" name="edicao_atual_data" value="<?= e((string)($cfg['edicao_atual_data'])) ?>" placeholder="Ex: Junho de 2026" class="input"></div>
        <div class="span-2"><div class="field"><label class="label" for="cfg-site_descricao">Descrição oficial (topo do rodapé)</label><textarea id="cfg-site_descricao" name="site_descricao" rows="2" class="textarea"><?= e((string)($cfg['site_descricao'])) ?></textarea></div></div>
      </div>
    </div>

    <div class="form-section">
      <div class="form-section__title">Colunas do rodapé institucional</div>
      <div class="form-section__sub">Conteúdo das quatro colunas exibidas no rodapé do portal.</div>
      <div class="form-grid">
        <div class="field"><label class="label" for="cfg-corpo_editorial_discente">Coluna 1: corpo editorial discente</label><textarea id="cfg-corpo_editorial_discente" name="corpo_editorial_discente" rows="6" class="textarea mono"><?= e((string)($cfg['corpo_editorial_discente'] ?? '')) ?></textarea><span class="hint">Um nome por linha.</span></div>
        <div class="field"><label class="label" for="cfg-orientacao_pedagogica">Coluna 2: orientação pedagógica</label><textarea id="cfg-orientacao_pedagogica" name="orientacao_pedagogica" rows="6" class="textarea mono"><?= e((string)($cfg['orientacao_pedagogica'] ?? '')) ?></textarea><span class="hint">Um nome por linha.</span></div>
        <div class="field"><label class="label" for="cfg-links_oficiais">Coluna 3: links oficiais</label><textarea id="cfg-links_oficiais" name="links_oficiais" rows="6" class="textarea mono"><?= e((string)($cfg['links_oficiais'] ?? '')) ?></textarea><span class="hint">Formato: Nome|URL, um por linha.</span></div>
        <div class="field"><label class="label" for="cfg-sede_redacao">Coluna 4: sede da redação e contato</label><textarea id="cfg-sede_redacao" name="sede_redacao" rows="6" class="textarea mono"><?= e((string)($cfg['sede_redacao'] ?? '')) ?></textarea></div>
        <div class="span-2"><div class="field"><label class="label" for="cfg-direitos_autorais">Texto de direitos autorais</label><input type="text" id="cfg-direitos_autorais" name="direitos_autorais" value="<?= e((string)($cfg['direitos_autorais'] ?? '')) ?>"  class="input"></div></div>
      </div>
    </div>

    <div class="card__foot" style="justify-content:flex-end">
      <button type="submit" class="btn btn--primary"><?= ui_icon('save') ?> Salvar configurações</button>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
