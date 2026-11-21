<?php
/**
 * O FAROL - Gestão da Página Inicial
 * Ativação/desativação dos módulos da capa + pré-visualização real do portal.
 */
declare(strict_types=1);
$admin_title = "Página inicial";
$admin_breadcrumb = "Página inicial";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

// Definição dos módulos gerenciáveis da Homepage
$modulos_definicao = [
    'modulo_cabecalho'    => ['nome' => 'Cabeçalho institucional', 'desc' => 'Logo, menu e pesquisa', 'destaque' => false],
    'modulo_manchete'     => ['nome' => 'Manchete principal', 'desc' => 'Principal chamada da capa do jornal', 'destaque' => true],
    'modulo_destaques'    => ['nome' => 'Notícias em destaque', 'desc' => 'Conteúdo automático da editoria', 'destaque' => false],
    'modulo_ciencia'      => ['nome' => 'Ciência e Prática Técnica', 'desc' => 'Conteúdo automático da editoria', 'destaque' => false],
    'modulo_jogos'        => ['nome' => 'Jogos Escolares', 'desc' => 'Conteúdo automático da editoria', 'destaque' => false],
    'modulo_cultura'      => ['nome' => 'Cultura e Empreendedorismo', 'desc' => 'Conteúdo automático da editoria', 'destaque' => false],
    'modulo_opiniao'      => ['nome' => 'Coluna de Opinião & Flagrantes', 'desc' => 'Conteúdo reflexivo e visual da semana', 'destaque' => false],
    'modulo_clube_livro'  => ['nome' => 'Clube do Livro', 'desc' => 'Obra do mês e resenhas na sidebar', 'destaque' => false],
    'modulo_processos'    => ['nome' => 'Processos Seletivos', 'desc' => 'Calendário e prazos acadêmicos', 'destaque' => false],
    'modulo_enquete'      => ['nome' => 'Enquetes', 'desc' => 'Voz do Aluno e participação da comunidade', 'destaque' => false],
    'modulo_banner_pauta' => ['nome' => 'Banner de Envio de Pautas', 'desc' => 'Faixa inferior de incentivo à participação', 'destaque' => false]
];

// Salvar alterações de ativação/desativação de módulos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_homepage'])) {
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("INSERT INTO configuracoes (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
            foreach (array_keys($modulos_definicao) as $chave) {
                $valor = isset($_POST[$chave]) ? '1' : '0';
                $stmt->execute([$chave, $valor]);
            }
            $mensagem = "Estrutura da página inicial atualizada e publicada com sucesso!";
        } catch (Exception $e) {
            $erro = "Erro ao salvar configurações: " . $e->getMessage();
        }
    } else {
        $mensagem = "Alterações registradas com sucesso no modo demonstração!";
    }
}

// Recarrega configurações atualizadas
$settings = get_site_settings();
$icones_mod = [
    'modulo_manchete' => 'newspaper', 'modulo_destaques' => 'news', 'modulo_clube_livro' => 'news',
    'modulo_processos' => 'calendar', 'modulo_enquete' => 'chart', 'modulo_banner_pauta' => 'inbox', 'modulo_opiniao' => 'message'
];
$ativos = 0;
foreach (array_keys($modulos_definicao) as $kk) {
    if (!empty($settings[$kk])) {
        $ativos++;
    }
}
?>

<form method="POST" action="">
  <?= ui_page_header('Página inicial', 'Defina quais módulos aparecem na capa do jornal.',
      '<a href="' . BASE_URL . '/index.php" target="_blank" rel="noopener" class="btn">' . ui_icon('eye') . ' Abrir o jornal</a>'
      . '<button type="submit" name="salvar_homepage" value="1" class="btn btn--primary">' . ui_icon('save') . ' Publicar alterações</button>') ?>

  <?= ui_alerts($mensagem, $erro) ?>

  <div class="split" style="grid-template-columns: minmax(0, 5fr) minmax(0, 7fr)">
    <div class="card card--flush">
      <div class="card__head">
        <div>
          <div class="card__title">Estrutura da página</div>
          <div class="card__sub"><?= $ativos ?> de <?= count($modulos_definicao) ?> módulos ativos</div>
        </div>
      </div>
      <?php foreach ($modulos_definicao as $chave => $mod): ?>
        <?php $ativo = !empty($settings[$chave]); ?>
        <div class="module-row">
          <div class="module-row__icon"><?= ui_icon($icones_mod[$chave] ?? 'layout') ?></div>
          <div class="grow">
            <div class="strong"><?= e($mod['nome']) ?> <?php if (!empty($mod['destaque'])): ?><?= ui_badge('Destaque', 'accent') ?><?php endif; ?></div>
            <div class="cell__meta truncate-1"><?= e($mod['desc']) ?></div>
          </div>
          <label class="switch" title="<?= e($mod['nome']) ?>">
            <input type="checkbox" name="<?= e($chave) ?>" value="1" <?= $ativo ? 'checked' : '' ?> aria-label="Exibir <?= e($mod['nome']) ?>">
            <i></i>
          </label>
        </div>
      <?php endforeach; ?>
      <div class="card__foot"><span>As alterações só valem depois de clicar em “Publicar alterações”.</span></div>
    </div>

    <div class="card sticky-panel">
      <div class="card__head">
        <div>
          <div class="card__title">Pré-visualização</div>
          <div class="card__sub">Versão publicada do portal (1440 px)</div>
        </div>
      </div>
      <div class="card__body">
        <div class="device">
          <div class="device__bar"><i></i><i></i><i></i><span>Capa do jornal</span></div>
          <div class="device__view" data-device>
            <iframe src="<?= BASE_URL ?>/index.php" title="Pré-visualização do jornal" loading="lazy" tabindex="-1"></iframe>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
