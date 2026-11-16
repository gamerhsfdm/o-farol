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
    }
}

$cfg = get_site_settings();
?>

<div class="flex items-center justify-between pb-4 mb-6 border-b border-gray-200">
  <div>
    <h1 class="text-2xl font-serif font-extrabold text-[#111827]">Configurações Gerais &amp; Institucionais</h1>
    <p class="text-xs text-gray-500 mt-1">Edite o número da edição atual, dados do rodapé, corpo docente e cabeçalho do portal.</p>
  </div>
</div>

<?php if (!empty($sucesso)): ?>
  <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 p-3 rounded text-xs mb-6 font-medium">
    ✓ <?= e($sucesso) ?>
  </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/admin/settings.php" method="POST" class="space-y-8">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

  <!-- CABEÇALHO & EDIÇÃO ATIVA -->
  <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-2">
      📰 Identidade do Portal e Edição Ativa
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Nome do Jornal</label>
        <input type="text" name="site_nome" value="<?= e($cfg['site_nome']) ?>" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50">
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Subtítulo Institucional</label>
        <input type="text" name="site_subtitulo" value="<?= e($cfg['site_subtitulo']) ?>" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50">
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Número da Edição Atual</label>
        <input type="text" name="edicao_atual_numero" value="<?= e($cfg['edicao_atual_numero']) ?>" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50">
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Data / Mês da Edição Atual</label>
        <input type="text" name="edicao_atual_data" value="<?= e($cfg['edicao_atual_data']) ?>" placeholder="Ex: Junho de 2026" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50">
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição Oficial (Topo do Rodapé)</label>
      <textarea name="site_descricao" rows="2" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50"><?= e($cfg['site_descricao']) ?></textarea>
    </div>
  </div>

  <!-- RODAPÉ INSTITUCIONAL (4 COLUNAS) -->
  <div class="bg-white p-6 rounded-lg border border-gray-200 shadow-sm space-y-4">
    <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wider border-b border-gray-200 pb-2">
      🏛 Colunas do Rodapé Institucional
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Coluna 1: Corpo Editorial Discente (1 por linha)</label>
        <textarea name="corpo_editorial_discente" rows="6" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50 font-mono"><?= e($cfg['corpo_editorial_discente'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Coluna 2: Orientação Pedagógica (1 por linha)</label>
        <textarea name="orientacao_pedagogica" rows="6" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50 font-mono"><?= e($cfg['orientacao_pedagogica'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Coluna 3: Links Oficiais (Formato: Nome|URL)</label>
        <textarea name="links_oficiais" rows="6" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50 font-mono"><?= e($cfg['links_oficiais'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Coluna 4: Sede da Redação e Contato</label>
        <textarea name="sede_redacao" rows="6" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50 font-mono"><?= e($cfg['sede_redacao'] ?? '') ?></textarea>
      </div>
    </div>

    <div>
      <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Texto de Direitos Autorais / Copyright</label>
      <input type="text" name="direitos_autorais" value="<?= e($cfg['direitos_autorais'] ?? '') ?>" class="w-full border border-gray-300 rounded p-2 text-xs bg-gray-50">
    </div>
  </div>

  <button type="submit" class="btn-pauta rounded py-3 px-8 text-sm font-bold shadow">
    SALVAR TODAS AS CONFIGURAÇÕES
  </button>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
