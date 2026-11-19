<?php
/**
 * O FAROL - Formulário Editorial Moderno de Sugestão e Envio de Pautas Estudantis
 * Conectado diretamente com a tabela pautas_sugestoes e com o painel admin/pautas.php
 */
declare(strict_types=1);
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();
$sucesso = false;
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf($csrf)) {
        $erro = 'Sessão expirada ou token de segurança inválido. Tente novamente.';
    } else {
        $nome = trim($_POST['nome_estudante'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $turma = trim($_POST['turma_curso'] ?? '');
        $tipo = $_POST['tipo_sugestao'] ?? 'reportagem';
        $titulo = trim($_POST['titulo_sugestao'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $justificativa = trim($_POST['justificativa'] ?? '');

        if (empty($nome) || empty($email) || empty($turma) || empty($titulo) || empty($descricao)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios (*).';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um endereço de e-mail institucional válido.';
        } else {
            // Se houver justificativa preenchida pelo aluno, anexa na descrição formatada
            $descricaoCompleta = $descricao;
            if (!empty($justificativa)) {
                $descricaoCompleta .= "\n\n[POR QUE ESSA PAUTA É IMPORTANTE?]\n" . $justificativa;
            }

            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO pautas_sugestoes (nome_estudante, email, turma_curso, tipo_sugestao, titulo_sugestao, descricao, status, criado_em) VALUES (?, ?, ?, ?, ?, ?, 'pendente', NOW())");
                    $stmt->execute([$nome, $email, $turma, $tipo, $titulo, $descricaoCompleta]);
                    $sucesso = true;
                } catch (Exception $e) {
                    $erro = 'Erro ao registrar pauta: ' . $e->getMessage();
                }
            } else {
                // Modo demonstração
                $sucesso = true;
            }
        }
    }
}

$page_title = "Envie sua Pauta ou Sugestão";
require_once __DIR__ . '/includes/header.php';
?>

<main class="container-farol py-10 sm:py-14">
  <div class="max-w-3xl mx-auto editorial-card p-6 sm:p-10 bg-white border border-[#e6e3da] shadow-sm">
    
    <div class="border-b-2 border-[#111827] pb-5 mb-8">
      <div class="flex items-center gap-2 mb-2">
        <span class="editorial-badge badge-crimson text-[9px]">PARTICIPAÇÃO DISCENTE</span>
        <span class="text-xs text-gray-500 font-sans uppercase tracking-wider">CANAL ABERTO DE REDAÇÃO</span>
      </div>
      <h1 class="text-3xl sm:text-4xl font-headline font-bold text-[#111827] leading-tight">
        Tem uma pauta, história ou projeto científico?
      </h1>
      <p class="text-xs sm:text-sm text-gray-600 font-sans mt-2.5 leading-relaxed">
        A redação de <strong>O Farol</strong> é feita por e para os estudantes do Sesc-Senac. Conte para os nossos repórteres o que está acontecendo na sua turma, oficina ou laboratório.
      </p>
    </div>

    <?php if ($sucesso): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-8 rounded-lg text-center space-y-4">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
          ✓
        </div>
        <h3 class="text-2xl font-headline font-bold text-gray-900">Pauta Enviada com Sucesso!</h3>
        <p class="text-xs sm:text-sm text-gray-600 leading-relaxed max-w-md mx-auto">
          Nossa equipe editorial discente recebeu sua sugestão. Ela será avaliada na próxima reunião de pauta e poderá virar reportagem na capa do jornal!
        </p>
        <div class="pt-4 flex flex-wrap justify-center gap-3">
          <a href="<?= BASE_URL ?>/index.php" class="btn-editorial-primary py-2.5 px-6 text-xs">
            Voltar à Capa do Jornal
          </a>
          <a href="<?= BASE_URL ?>/enviar-pauta.php" class="border border-gray-300 rounded px-5 py-2.5 text-xs text-gray-700 bg-white hover:bg-gray-50">
            Enviar Outra Ideia
          </a>
        </div>
      </div>
    <?php else: ?>

      <?php if (!empty($erro)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded text-xs mb-6 flex items-center gap-2">
          <span>⚠️</span>
          <span><?= e($erro) ?></span>
        </div>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>/enviar-pauta.php" method="POST" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <!-- Identificação do Estudante -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Seu Nome Completo *</label>
            <input type="text" name="nome_estudante" required placeholder="Ex: Mariana Alves" 
                   value="<?= e($_POST['nome_estudante'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail Institucional *</label>
            <input type="email" name="email" required placeholder="mariana.alves@aluno.sescsenac.edu.br" 
                   value="<?= e($_POST['email'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Turma / Série / Curso *</label>
            <input type="text" name="turma_curso" required placeholder="Ex: 2º ano A — Técnico em Informática" 
                   value="<?= e($_POST['turma_curso'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Editoria / Tema Sugerido *</label>
            <select name="tipo_sugestao" class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
              <option value="projeto_cientifico">Ciência e Prática Técnica</option>
              <option value="reportagem">Cultura e Empreendedorismo</option>
              <option value="esporte">Jogos Escolares Interclasses</option>
              <option value="resenha">Clube do Livro / Literatura</option>
              <option value="evento">Feira do Conhecimento / FECCI / SBPC</option>
              <option value="outro">Cotidiano Escolar / Comunidade</option>
            </select>
          </div>
        </div>

        <!-- Conteúdo da Sugestão -->
        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Ideia ou Título Proposto *</label>
          <input type="text" name="titulo_sugestao" required placeholder="Ex: Uma horta comunitária para nossa escola" 
                 value="<?= e($_POST['titulo_sugestao'] ?? '') ?>"
                 class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição da Sugestão *</label>
          <textarea name="descricao" rows="4" required placeholder="Conte em detalhes: o que aconteceu ou vai acontecer? Quem são as pessoas envolvidas? Onde e quando?" 
                    class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]"><?= e($_POST['descricao'] ?? '') ?></textarea>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Por que essa pauta é importante para a escola?</label>
          <textarea name="justificativa" rows="2" placeholder="Explique por que os outros estudantes e a comunidade escolar vão se interessar por esse assunto..." 
                    class="w-full border border-gray-300 rounded p-2.5 text-xs bg-[#fafaf8] focus:bg-white focus:outline-none focus:border-[#0f4c5c]"><?= e($_POST['justificativa'] ?? '') ?></textarea>
        </div>

        <div class="pt-3 border-t border-gray-100 flex items-center justify-between">
          <span class="text-[11px] text-gray-400">Todos os dados são protegidos e enviados à redação.</span>
          <button type="submit" class="btn-editorial-primary py-3 px-8 text-xs font-bold shadow-md">
            Enviar Pauta para a Redação →
          </button>
        </div>
      </form>

    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
