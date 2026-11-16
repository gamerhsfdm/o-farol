<?php
/**
 * O FAROL - Formulário Moderno de Sugestão e Envio de Pautas Estudantis
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

        if (empty($nome) || empty($email) || empty($turma) || empty($titulo) || empty($descricao)) {
            $erro = 'Por favor, preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe um endereço de e-mail institucional válido.';
        } elseif (!$pdo) {
            $erro = 'Banco de dados inacessível no momento.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO pautas_sugestoes (nome_estudante, email, turma_curso, tipo_sugestao, titulo_sugestao, descricao) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nome, $email, $turma, $tipo, $titulo, $descricao]);
                $sucesso = true;
            } catch (Exception $e) {
                $erro = 'Erro ao registrar pauta: ' . $e->getMessage();
            }
        }
    }
}

$page_title = "Envie sua Pauta ou Sugestão";
require_once __DIR__ . '/includes/header.php';
?>

<main class="container-farol py-12">
  <div class="max-w-2xl mx-auto editorial-card p-6 sm:p-10 bg-white">
    
    <div class="border-b-2 border-[#111827] pb-4 mb-6">
      <span class="editorial-badge badge-crimson text-[9px] mb-2">CANAL DE PARTICIPAÇÃO ESTUDANTIL</span>
      <h1 class="text-3xl sm:text-4xl font-headline font-extrabold text-[#111827] mt-1">
        Envie sua Pauta ou Sugestão
      </h1>
      <p class="text-xs sm:text-sm text-gray-600 font-sans mt-2 leading-relaxed">
        Você tem uma boa história sobre sua turma, um projeto integrador relevante, uma iniciativa cultural ou um resultado esportivo? Compartilhe com os repórteres de O Farol!
      </p>
    </div>

    <?php if ($sucesso): ?>
      <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 p-8 rounded text-center">
        <svg class="w-14 h-14 text-emerald-600 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <h3 class="text-xl font-headline font-bold text-gray-900 mb-2">Pauta Recebida pela Redação!</h3>
        <p class="text-xs sm:text-sm text-gray-600 mb-6 leading-relaxed max-w-lg mx-auto">
          Nossa equipe editorial discente analisará sua proposta na próxima reunião de pauta. Caso selecionada, um repórter entrará em contato pelo seu e-mail institucional.
        </p>
        <a href="<?= BASE_URL ?>/index.php" class="btn-editorial-primary">
          Voltar à Capa do Jornal
        </a>
      </div>
    <?php else: ?>

      <?php if (!empty($erro)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded text-xs mb-6">
          <?= e($erro) ?>
        </div>
      <?php endif; ?>

      <form action="<?= BASE_URL ?>/enviar-pauta.php" method="POST" class="space-y-5">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Seu Nome Completo *</label>
            <input type="text" name="nome_estudante" required class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">E-mail Institucional *</label>
            <input type="email" name="email" required placeholder="exemplo@aluno.sescsenac.edu.br" class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Sua Turma / Curso Técnico *</label>
            <input type="text" name="turma_curso" required placeholder="Ex: 3º Ano Técnico em Informática" class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Tipo de Pauta *</label>
            <select name="tipo_sugestao" class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
              <option value="reportagem">Reportagem Geral</option>
              <option value="projeto_cientifico">Projeto Científico / Integrador</option>
              <option value="evento">Evento Cultural / Feira</option>
              <option value="resenha">Resenha Literária / Crítica</option>
              <option value="esporte">Jogos Interclasses / Torneio</option>
              <option value="outro">Outro assunto</option>
            </select>
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Título Resumido da Pauta *</label>
          <input type="text" name="titulo_sugestao" required placeholder="Ex: Alunos desenvolvem sistema de irrigação automatizado" class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]">
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 uppercase mb-1">Descrição Detalhada do Fato / Pauta *</label>
          <textarea name="descricao" rows="5" required placeholder="Explique o que aconteceu ou vai acontecer, quem são os envolvidos, por que o assunto é relevante para o jornal..." class="w-full border border-gray-300 rounded p-2.5 text-xs bg-gray-50 focus:bg-white focus:outline-none focus:border-[#0f4c5c]"></textarea>
        </div>

        <button type="submit" class="w-full btn-editorial-primary justify-center py-3 text-xs font-bold shadow">
          Enviar Pauta para a Redação
        </button>
      </form>

    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
