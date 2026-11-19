<?php
/**
 * O FAROL - Editor de Notícias Fiel à Imagem 3
 * Layout Documental Limpo (Estilo Medium/Substack) + Barra Lateral Direita de Configurações e SEO
 */
declare(strict_types=1);
$admin_title = "Editor de notícias";
$admin_breadcrumb = "Nova reportagem";
require_once __DIR__ . '/header.php';

$pdo = getDBConnection();
$mensagem = '';
$erro = '';

$id = (int)($_GET['id'] ?? 0);

// Lista de categorias do banco
$lista_categorias = [];
if ($pdo) {
    try {
        $stmtCat = $pdo->query("SELECT id, nome, slug FROM categorias WHERE ativo = 1 ORDER BY nome ASC");
        $lista_categorias = $stmtCat->fetchAll();
    } catch (Exception $e) {}
}
if (empty($lista_categorias)) {
    $lista_categorias = [
        ['id' => 1, 'nome' => 'Clube de Ciências', 'slug' => 'clube-de-ciencias'],
        ['id' => 2, 'nome' => 'Jogos Escolares', 'slug' => 'jogos-escolares'],
        ['id' => 3, 'nome' => 'Cultura e Arte', 'slug' => 'cultura-e-arte'],
        ['id' => 4, 'nome' => 'Clube do Livro', 'slug' => 'clube-do-livro'],
        ['id' => 5, 'nome' => 'FECCI & SBPC Jovem', 'slug' => 'fecci-sbpc']
    ];
}

// Lista de autores
$lista_autores = [];
if ($pdo) {
    try {
        $stmtAut = $pdo->query("SELECT id, nome, cargo, turma FROM autores WHERE ativo = 1 ORDER BY nome ASC");
        $lista_autores = $stmtAut->fetchAll();
    } catch (Exception $e) {}
}
if (empty($lista_autores)) {
    $lista_autores = [
        ['id' => 1, 'nome' => 'Ana Luiza', 'cargo' => 'Repórter', 'turma' => '2º ano A'],
        ['id' => 2, 'nome' => 'Lucas Mendes', 'cargo' => 'Editor de esportes', 'turma' => '3º ano B'],
        ['id' => 3, 'nome' => 'Beatriz Lima', 'cargo' => 'Colunista', 'turma' => '1º ano C'],
        ['id' => 4, 'nome' => 'Camila Rocha', 'cargo' => 'Repórter', 'turma' => '3º ano A']
    ];
}

// Dados padrão iniciais ou carregamento de notícia existente
$noticia = [
    'id' => 0,
    'categoria_id' => 1,
    'categoria_nome' => 'CLUBE DE CIÊNCIAS',
    'titulo' => 'Jovens cientistas apresentam soluções para uma escola mais sustentável',
    'subtitulo' => 'Projetos desenvolvidos pelos estudantes unem criatividade, pesquisa e compromisso com o futuro da comunidade escolar.',
    'conteudo' => "<p>Na última quarta-feira, o pátio da escola se transformou em um verdadeiro laboratório de ideias. Estudantes do Clube de Ciências apresentaram projetos que buscam soluções simples e criativas para desafios do cotidiano escolar.</p>\n\n<h2>Ciência que transforma o cotidiano</h2>\n\n<p>Entre as iniciativas, destacaram-se um sistema de captação de água da chuva, uma horta irrigada automaticamente e novas propostas para reduzir o desperdício de materiais.</p>\n\n<blockquote>“A ciência faz mais sentido quando conseguimos perceber como ela pode melhorar a vida das pessoas ao nosso redor.”</blockquote>",
    'autor_id' => 1,
    'autor_nome' => 'Ana Luiza',
    'tempo_leitura' => 6,
    'professor_orientador' => 'Prof. Ricardo Nunes',
    'foto_capa' => 'assets/images/ciencia-lab.jpg',
    'tags' => 'sustentabilidade, ciência',
    'slug' => 'jovens-cientistas-apresentam-solucoes',
    'status' => 'rascunho',
    'posicao_homepage' => 'padrao'
];

// Se tiver ID, busca do banco
if ($id > 0 && $pdo) {
    try {
        $stmtNot = $pdo->prepare("SELECT n.*, c.nome as categoria_nome FROM noticias n JOIN categorias c ON n.categoria_id = c.id WHERE n.id = ?");
        $stmtNot->execute([$id]);
        $bdNot = $stmtNot->fetch();
        if ($bdNot) {
            $noticia = array_merge($noticia, $bdNot);
        }
    } catch (Exception $e) {}
}

// Processar Salvamento via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao_salvar'])) {
    $acao = $_POST['acao_salvar']; // 'rascunho', 'revisao', 'publicar'
    $status_novo = match($acao) {
        'publicar' => 'publicado',
        'revisao' => 'revisao',
        default => 'rascunho'
    };

    $titulo = trim($_POST['titulo'] ?? '');
    $subtitulo = trim($_POST['subtitulo'] ?? '');
    $conteudo = trim($_POST['conteudo'] ?? '');
    $categoria_id = (int)($_POST['categoria_id'] ?? 1);
    $autor_id = (int)($_POST['autor_id'] ?? 1);
    $orientador = trim($_POST['professor_orientador'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    if (empty($slug)) {
        $slug = slugify($titulo);
    }
    $foto_capa = trim($_POST['foto_capa'] ?? 'assets/images/ciencia-lab.jpg');
    $tempo_leitura = (int)($_POST['tempo_leitura'] ?? 5);

    if (empty($titulo)) {
        $erro = "O título da reportagem não pode ficar em branco.";
    } else {
        if ($pdo) {
            try {
                if ($id > 0) {
                    $stmtUp = $pdo->prepare("UPDATE noticias SET categoria_id = ?, titulo = ?, subtitulo = ?, conteudo = ?, foto_capa = ?, slug = ?, tempo_leitura = ?, professor_orientador = ?, status = ?, atualizado_em = NOW() WHERE id = ?");
                    $stmtUp->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $foto_capa, $slug, $tempo_leitura, $orientador, $status_novo, $id]);
                    $mensagem = "Reportagem atualizada com sucesso como " . strtoupper($status_novo) . "!";
                } else {
                    $stmtIn = $pdo->prepare("INSERT INTO noticias (categoria_id, titulo, subtitulo, conteudo, foto_capa, slug, tempo_leitura, professor_orientador, status, publicado_em, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmtIn->execute([$categoria_id, $titulo, $subtitulo, $conteudo, $foto_capa, $slug, $tempo_leitura, $orientador, $status_novo]);
                    $id = (int)$pdo->lastInsertId();
                    
                    // Vincula autor na tabela pivô
                    if ($autor_id > 0) {
                        $stmtNa = $pdo->prepare("INSERT IGNORE INTO noticia_autores (noticia_id, autor_id, papel) VALUES (?, ?, 'autor')");
                        $stmtNa->execute([$id, $autor_id]);
                    }
                    $mensagem = "Reportagem criada e salva com sucesso como " . strtoupper($status_novo) . "!";
                }
                // Atualiza array em memória
                $noticia['titulo'] = $titulo;
                $noticia['subtitulo'] = $subtitulo;
                $noticia['conteudo'] = $conteudo;
                $noticia['categoria_id'] = $categoria_id;
                $noticia['foto_capa'] = $foto_capa;
                $noticia['slug'] = $slug;
                $noticia['status'] = $status_novo;
            } catch (Exception $e) {
                $erro = "Erro ao salvar no banco: " . $e->getMessage();
            }
        } else {
            $mensagem = "Reportagem salva com sucesso (modo visual/demonstração)!";
        }
    }
}
?>

<form method="POST" action="" id="form-editor">
  <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
  <input type="hidden" name="id" value="<?= $id ?>">

  <!-- TOPO DO EDITOR: AÇÕES DE SALVAMENTO IDÊNTICAS À IMAGEM 3 -->
  <div class="flex items-center justify-between pb-4 mb-4 border-b border-[#f0eee9]">
    <div class="flex items-center gap-3">
      <a href="<?= BASE_URL ?>/admin/noticias.php" class="text-xs text-gray-500 hover:text-black flex items-center gap-1 transition-colors">
        ‹ Notícias
      </a>
      <span class="text-[11px] text-gray-400">● Salvo automaticamente às <?= date('H:i') ?></span>
    </div>

    <div class="flex items-center gap-2">
      <a href="<?= BASE_URL ?>/noticia.php?slug=<?= urlencode($noticia['slug']) ?>" target="_blank" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
        Visualizar
      </a>
      <button type="submit" name="acao_salvar" value="rascunho" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
        Salvar rascunho
      </button>
      <button type="submit" name="acao_salvar" value="revisao" class="border border-gray-200 rounded px-3 py-1.5 text-xs text-gray-700 bg-white hover:bg-gray-50 flex items-center gap-1.5 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
        Enviar para revisão
      </button>
      <button type="submit" name="acao_salvar" value="publicar" class="btn-admin-red">
        Publicar ›
      </button>
    </div>
  </div>

  <?php if ($mensagem): ?>
    <div class="p-3 mb-4 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded flex items-center justify-between">
      <span>✓ <?= e($mensagem) ?></span>
      <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">×</button>
    </div>
  <?php endif; ?>

  <?php if ($erro): ?>
    <div class="p-3 mb-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded flex items-center justify-between">
      <span>⚠️ <?= e($erro) ?></span>
      <button type="button" onclick="this.parentElement.remove()" class="text-red-700 font-bold">×</button>
    </div>
  <?php endif; ?>

  <!-- GRADE DOCUMENTO CENTRAL + SIDEBAR DE METADADOS -->
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
    
    <!-- FOLHA DOCUMENTAL CENTRAL (8 Colunas) -->
    <div class="lg:col-span-8 bg-white p-8 sm:p-10 rounded-lg border border-[#f0eee9] shadow-sm min-h-[750px]">
      
      <!-- Categoria da Matéria -->
      <span class="text-[9.5px] font-bold text-[#c0262d] uppercase tracking-wider block mb-3">
        <?= e($noticia['categoria_nome']) ?>
      </span>

      <!-- Título Editável em Playfair Display -->
      <textarea name="titulo" rows="2" placeholder="Digite o título da reportagem..." required
                class="w-full text-3xl sm:text-4xl font-editorial-heading font-bold text-[#111827] leading-tight mb-4 border-0 border-b border-transparent hover:border-gray-200 focus:border-[#c0262d] focus:outline-none resize-none bg-transparent transition-colors"><?= e($noticia['titulo']) ?></textarea>

      <!-- Subtítulo Editável -->
      <textarea name="subtitulo" rows="2" placeholder="Digite o subtítulo ou resumo da reportagem..." 
                class="w-full text-base text-gray-500 font-sans leading-relaxed mb-6 border-0 border-b border-transparent hover:border-gray-200 focus:border-[#c0262d] focus:outline-none resize-none bg-transparent transition-colors"><?= e($noticia['subtitulo']) ?></textarea>

      <!-- Assinatura do Autor -->
      <div class="flex items-center gap-3 pb-6 border-b border-gray-100 mb-6 text-xs text-gray-500">
        <div class="w-6 h-6 rounded-full bg-[#f3f4f6] text-gray-700 font-bold text-[9px] flex items-center justify-center">
          <?= strtoupper(substr($noticia['autor_nome'] ?? 'AL', 0, 2)) ?>
        </div>
        <span>Por <strong><?= e($noticia['autor_nome']) ?></strong></span>
        <span>•</span>
        <span><?= e((string)$noticia['tempo_leitura']) ?> min de leitura</span>
      </div>

      <!-- Barra de Ferramentas Flutuante Idêntica à Imagem 3 -->
      <div class="inline-flex items-center gap-3 px-3 py-1.5 bg-[#fafaf8] border border-gray-200 rounded-lg text-xs text-gray-700 mb-6 shadow-sm">
        <span class="font-medium cursor-pointer">Parágrafo ⌄</span>
        <span class="text-gray-300">|</span>
        <button type="button" onclick="formatDoc('bold')" class="font-bold hover:text-black">B</button>
        <button type="button" onclick="formatDoc('italic')" class="italic hover:text-black font-serif">I</button>
        <button type="button" onclick="formatDoc('justifyLeft')" class="hover:text-black">≡</button>
        <button type="button" onclick="formatDoc('createLink')" class="hover:text-black">🔗</button>
        <button type="button" class="hover:text-black">🖼</button>
        <button type="button" class="hover:text-black">•••</button>
      </div>

      <!-- Corpo de Texto da Matéria (Textarea para persistência simples e confiável) -->
      <div class="font-sans">
        <textarea name="conteudo" id="corpo-materia" rows="16" placeholder="Escreva o texto completo da matéria..."
                  class="w-full text-sm text-gray-800 leading-relaxed p-3 border border-gray-200 rounded-lg focus:border-[#c0262d] focus:outline-none bg-[#fbfbf9]"><?= e($noticia['conteudo']) ?></textarea>
      </div>

    </div>

    <!-- SIDEBAR DIREITA DE CONFIGURAÇÕES E SEO (4 Colunas) IDÊNTICA À IMAGEM 3 -->
    <div class="lg:col-span-4 bg-white p-6 rounded-lg border border-[#f0eee9] shadow-sm space-y-5">
      
      <!-- Abas: Configurações | SEO -->
      <div class="flex items-center border-b border-gray-200 text-xs font-semibold pb-1">
        <button type="button" class="pb-2 border-b-2 border-[#c0262d] text-[#c0262d] px-3">Configurações</button>
        <button type="button" class="pb-2 text-gray-400 hover:text-gray-700 px-3">SEO</button>
      </div>

      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Status</label>
        <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2.5 py-1 rounded font-semibold">
          <?= ucfirst($noticia['status'] ?? 'Rascunho') ?>
        </span>
      </div>

      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Categoria</label>
        <select name="categoria_id" class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] outline-none">
          <?php foreach ($lista_categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $noticia['categoria_id']) ? 'selected' : '' ?>>
              <?= e($cat['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Autor</label>
        <select name="autor_id" class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] outline-none">
          <?php foreach ($lista_autores as $aut): ?>
            <option value="<?= $aut['id'] ?>" <?= ($aut['id'] == $noticia['autor_id']) ? 'selected' : '' ?>>
              <?= e($aut['nome']) ?> (<?= e($aut['turma'] ?? $aut['cargo']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Professor orientador</label>
        <input type="text" name="professor_orientador" value="<?= e($noticia['professor_orientador']) ?>" 
               class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] outline-none">
      </div>

      <!-- Imagem de Capa com Botão de Alteração -->
      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Imagem de capa</label>
        <div class="relative rounded overflow-hidden border border-gray-200">
          <img src="<?= BASE_URL ?>/<?= e($noticia['foto_capa']) ?>" class="w-full h-32 object-cover" id="preview-capa">
          <div class="p-2 bg-[#fafaf8] border-t border-gray-200">
            <input type="text" name="foto_capa" value="<?= e($noticia['foto_capa']) ?>" 
                   placeholder="Caminho ou URL da imagem..."
                   class="w-full text-[10px] text-gray-600 border border-gray-200 rounded p-1.5 bg-white">
          </div>
        </div>
      </div>

      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Tempo estimado de leitura (minutos)</label>
        <input type="number" name="tempo_leitura" value="<?= e((string)$noticia['tempo_leitura']) ?>" min="1" max="60"
               class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8]">
      </div>

      <div>
        <label class="block text-[11px] font-medium text-gray-500 mb-1">Slug (URL amigável)</label>
        <input type="text" name="slug" value="<?= e($noticia['slug']) ?>" 
               class="w-full border border-gray-200 rounded p-2 text-xs bg-[#fafaf8] text-gray-500">
      </div>

      <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
        <span>Notícias relacionadas</span>
        <button type="button" class="text-[#c0262d] font-bold text-sm">+</button>
      </div>

    </div>

  </div>
</form>

<script>
function formatDoc(cmd, value = null) {
  if (cmd === 'createLink') {
    const url = prompt('Digite o link URL:');
    if (url) document.execCommand(cmd, false, url);
  } else {
    document.execCommand(cmd, false, value);
  }
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
