-- ========================================================
-- O FAROL - Jornal Escolar Sesc-Senac
-- Banco de Dados Estruturado para MySQL / MariaDB (phpMyAdmin)
-- Charset: utf8mb4_unicode_ci
-- ========================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `noticia_autores`;
DROP TABLE IF EXISTS `noticia_versoes`;
DROP TABLE IF EXISTS `enquete_votos`;
DROP TABLE IF EXISTS `enquete_opcoes`;
DROP TABLE IF EXISTS `enquetes`;
DROP TABLE IF EXISTS `jogos_resultados`;
DROP TABLE IF EXISTS `processos_seletivos`;
DROP TABLE IF EXISTS `clube_livro`;
DROP TABLE IF EXISTS `flagrantes_semana`;
DROP TABLE IF EXISTS `pautas_sugestoes`;
DROP TABLE IF EXISTS `noticias`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `autores`;
DROP TABLE IF EXISTS `edicoes`;
DROP TABLE IF EXISTS `media_library`;
DROP TABLE IF EXISTS `configuracoes`;
DROP TABLE IF EXISTS `logs_auditoria`;
DROP TABLE IF EXISTS `usuarios`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. USUÁRIOS DO SISTEMA CMS
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `papel` ENUM('admin', 'editor_chefe', 'editor', 'colaborador') NOT NULL DEFAULT 'editor',
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `ultimo_login` DATETIME NULL,
  `tentativas_falhas` INT NOT NULL DEFAULT 0,
  `bloqueado_ate` DATETIME NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. AUTORES E EQUIPE EDITORIAL DISCENTE
CREATE TABLE `autores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NULL,
  `foto` VARCHAR(255) NULL,
  `turma` VARCHAR(100) NULL, -- Ex: '3º Ano do Técnico em Automação'
  `cargo` VARCHAR(100) NULL, -- Ex: 'Redação Discente', 'Repórter', 'Fotógrafo'
  `biografia` TEXT NULL,
  `redes_sociais` JSON NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. CATEGORIAS EDITORIAIS
CREATE TABLE `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `descricao` VARCHAR(255) NULL,
  `cor` VARCHAR(30) DEFAULT '#b91c1c', -- Cor de destaque da tag
  `ordem` INT NOT NULL DEFAULT 0,
  `no_menu` TINYINT(1) NOT NULL DEFAULT 1,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. EDIÇÕES DO JORNAL
CREATE TABLE `edicoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero` INT NOT NULL UNIQUE,
  `titulo` VARCHAR(150) NOT NULL,
  `mes_ano` VARCHAR(60) NOT NULL, -- Ex: 'Junho de 2026'
  `data_publicacao` DATE NOT NULL,
  `capa_url` VARCHAR(255) NULL,
  `pdf_url` VARCHAR(255) NULL,
  `descricao` TEXT NULL,
  `destaque` TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. NOTÍCIAS E REPORTAGENS
CREATE TABLE `noticias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT NOT NULL,
  `edicao_id` INT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `subtitulo` TEXT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `chapeu` VARCHAR(80) NULL, -- Ex: 'PREMIAÇÃO NACIONAL', 'FASE SEMIFINAL'
  `conteudo` LONGTEXT NOT NULL,
  `citacao_destaque` TEXT NULL, -- Citação especial destacada
  `citacao_autor` VARCHAR(150) NULL,
  `imagem_capa` VARCHAR(255) NULL,
  `legenda_capa` TEXT NULL,
  `credito_foto` VARCHAR(150) NULL,
  `tempo_leitura` INT NOT NULL DEFAULT 3, -- minutos
  `professor_orientador` VARCHAR(150) NULL, -- Ex: 'Prof. Dr. Hamilton Queiroz'
  `status` ENUM('rascunho', 'revisao', 'aprovado', 'agendado', 'publicado', 'arquivado') NOT NULL DEFAULT 'publicado',
  `agendado_para` DATETIME NULL,
  `publicado_em` DATETIME NULL,
  `posicao_homepage` ENUM('nenhuma', 'manchete_hero', 'ciencia_tecnica', 'jogos_esportes', 'cultura_empreendedorismo', 'coluna_opiniao') NOT NULL DEFAULT 'nenhuma',
  `ordem_home` INT NOT NULL DEFAULT 0,
  `visualizacoes` INT NOT NULL DEFAULT 0,
  `meta_keywords` VARCHAR(255) NULL,
  `meta_description` TEXT NULL,
  `criado_por` INT NULL,
  `editando_por` INT NULL,
  `editando_em` DATETIME NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`edicao_id`) REFERENCES `edicoes`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`criado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`editando_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_slug` (`slug`),
  INDEX `idx_status_pub` (`status`, `publicado_em`),
  INDEX `idx_home` (`posicao_homepage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. RELAÇÃO NOTÍCIAS E AUTORES (N:N)
CREATE TABLE `noticia_autores` (
  `noticia_id` INT NOT NULL,
  `autor_id` INT NOT NULL,
  PRIMARY KEY (`noticia_id`, `autor_id`),
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`autor_id`) REFERENCES `autores`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. HISTÓRICO DE VERSÕES DE NOTÍCIAS (Auditoria & Restauração)
CREATE TABLE `noticia_versoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `noticia_id` INT NOT NULL,
  `usuario_id` INT NULL,
  `titulo` VARCHAR(255) NOT NULL,
  `subtitulo` TEXT NULL,
  `conteudo` LONGTEXT NOT NULL,
  `imagem_capa` VARCHAR(255) NULL,
  `motivo_alteracao` VARCHAR(255) NULL,
  `versao` INT NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. NOTAS EDITORIAIS INTERNAS (Diálogo Orientador / Editor e Redator)
CREATE TABLE `noticia_notas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `noticia_id` INT NOT NULL,
  `usuario_id` INT NOT NULL,
  `texto` TEXT NOT NULL,
  `status_resolvido` TINYINT(1) NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  INDEX `idx_noticia_notas` (`noticia_id`, `criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. RESULTADOS DOS JOGOS INTERCLASSES
CREATE TABLE `jogos_resultados` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `modalidade` VARCHAR(80) NOT NULL DEFAULT 'Futsal Masculino',
  `equipe_a` VARCHAR(100) NOT NULL, -- Ex: 'Informática 3º A'
  `placar_a` INT NOT NULL DEFAULT 0,
  `placar_b` INT NOT NULL DEFAULT 0,
  `equipe_b` VARCHAR(100) NOT NULL, -- Ex: 'Redes 2º B'
  `fase` VARCHAR(80) NULL, -- Ex: 'Semifinal', 'Rodada 3'
  `data_jogo` DATE NULL,
  `destaque_home` TINYINT(1) NOT NULL DEFAULT 1,
  `ordem` INT NOT NULL DEFAULT 0,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. SIDEBAR: CLUBE DO LIVRO
CREATE TABLE `clube_livro` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo_livro` VARCHAR(150) NOT NULL, -- Ex: 'Torto Arado'
  `autor_livro` VARCHAR(120) NOT NULL, -- Ex: 'Itamar Vieira Junior'
  `chamada_editorial` VARCHAR(255) NOT NULL, -- Ex: 'Terra, silêncio e ancestralidade: O debate que lotou a biblioteca escolar'
  `resumo` TEXT NOT NULL,
  `capa_url` VARCHAR(255) NULL,
  `proxima_leitura_titulo` VARCHAR(150) NULL, -- Ex: '"Quarto de Despejo"'
  `proxima_leitura_autor` VARCHAR(120) NULL, -- Ex: 'Carolina Maria de Jesus'
  `info_inscricao` VARCHAR(255) NULL, -- Ex: 'Inscrições abertas no balcão da biblioteca.'
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. SIDEBAR: PROCESSOS SELETIVOS
CREATE TABLE `processos_seletivos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ano_referencia` VARCHAR(20) NOT NULL DEFAULT '2026/2027',
  `categoria` VARCHAR(80) NOT NULL, -- Ex: 'SENAC 2026', 'FACULDADE SESC', 'VESTIBULARES PÚBLICOS'
  `titulo` VARCHAR(150) NOT NULL,
  `descricao` TEXT NOT NULL,
  `link_edital` VARCHAR(255) NULL,
  `data_limite` VARCHAR(80) NULL,
  `ordem` INT NOT NULL DEFAULT 0,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. SIDEBAR: ENQUETE E VOTOS (VOZ DO ALUNO)
CREATE TABLE `enquetes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pergunta` VARCHAR(255) NOT NULL,
  `descricao` TEXT NULL,
  `ativo` TINYINT(1) NOT NULL DEFAULT 1,
  `data_inicio` DATE NULL,
  `data_fim` DATE NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enquete_opcoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `enquete_id` INT NOT NULL,
  `opcao` VARCHAR(200) NOT NULL,
  `votos_count` INT NOT NULL DEFAULT 0,
  `ordem` INT NOT NULL DEFAULT 0,
  FOREIGN KEY (`enquete_id`) REFERENCES `enquetes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `enquete_votos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `enquete_id` INT NOT NULL,
  `opcao_id` INT NOT NULL,
  `ip_hash` VARCHAR(64) NOT NULL,
  `voto_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`enquete_id`) REFERENCES `enquetes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`opcao_id`) REFERENCES `enquete_opcoes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. FLAGRANTES DA SEMANA (Galeria Fotográfica)
CREATE TABLE `flagrantes_semana` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `titulo` VARCHAR(150) NOT NULL,
  `legenda` TEXT NOT NULL,
  `foto_url` VARCHAR(255) NOT NULL,
  `fotografo` VARCHAR(120) NULL, -- Ex: 'Lucas Rios'
  `campus_local` VARCHAR(100) NULL, -- Ex: 'Campus em Foco'
  `destaque` TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. CAIXA DE SUGESTÕES DE PAUTAS (Envio pelos estudantes)
CREATE TABLE `pautas_sugestoes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_estudante` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `turma_curso` VARCHAR(100) NOT NULL,
  `tipo_sugestao` ENUM('reportagem', 'projeto_cientifico', 'evento', 'resenha', 'esporte', 'outro') NOT NULL,
  `titulo_sugestao` VARCHAR(200) NOT NULL,
  `descricao` TEXT NOT NULL,
  `anexo_url` VARCHAR(255) NULL,
  `status` ENUM('pendente', 'em_analise', 'aprovada', 'descartada') NOT NULL DEFAULT 'pendente',
  `notas_redacao` TEXT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. BIBLIOTECA DE MÍDIA CENTRALIZADA
CREATE TABLE `media_library` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome_arquivo` VARCHAR(255) NOT NULL,
  `caminho` VARCHAR(255) NOT NULL,
  `tipo_mime` VARCHAR(100) NOT NULL,
  `tamanho_bytes` INT NOT NULL,
  `alt_text` VARCHAR(255) NULL,
  `legenda` TEXT NULL,
  `credito` VARCHAR(150) NULL,
  `criado_por` INT NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`criado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. CONFIGURAÇÕES INSTITUCIONAIS GLOBAIS
CREATE TABLE `configuracoes` (
  `chave` VARCHAR(80) PRIMARY KEY,
  `valor` LONGTEXT NULL,
  `grupo` VARCHAR(50) DEFAULT 'geral',
  `atualizado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. COMENTÁRIOS E MODERAÇÃO DAS REPORTAGENS
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `noticia_id` INT NOT NULL,
  `nome_autor` VARCHAR(120) NOT NULL,
  `email_autor` VARCHAR(150) NOT NULL,
  `turma_autor` VARCHAR(80) NULL,
  `conteudo` TEXT NOT NULL,
  `status` ENUM('pendente', 'aprovado', 'rejeitado') NOT NULL DEFAULT 'pendente',
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`noticia_id`) REFERENCES `noticias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. LOGS DE AUDITORIA ADMINISTRATIVA
CREATE TABLE `logs_auditoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `tabela_afetada` VARCHAR(80) NULL,
  `registro_id` INT NULL,
  `detalhes` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `criado_em` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================================
-- CARGA DE DADOS INICIAIS FIÉIS À IMAGEM DE REFERÊNCIA
-- ========================================================

-- Usuário Administrador Inicial (Senha padrão segura: Admin@2026Farol)
-- Hash gerado via password_hash('Admin@2026Farol', PASSWORD_DEFAULT)
INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `papel`, `ativo`) VALUES
(1, 'Administrador O Farol', 'admin@ofarol.sescsenac.edu.br', '$2y$10$DIe8rFqp/e1eaHNjjWCjVeQckSSkPU1phYx5K90D/O4ogNkOQ6.b2', 'admin', 1);

-- Autores fiéis à referência visual
INSERT INTO `autores` (`id`, `nome`, `email`, `turma`, `cargo`, `biografia`, `foto`) VALUES
(1, 'Enzo Oliveira', 'enzo.oliveira@aluno.sescsenac.edu.br', '3º Ano Técnico em Informática', 'Redação Discente', 'Estudante e repórter discente com foco em tecnologias e robótica.', 'assets/images/autores/enzo.jpg'),
(2, 'Marina Prado', 'marina.prado@aluno.sescsenac.edu.br', '2º Ano Técnico em Redes', 'Redação Discente', 'Pesquisadora júnior e redatora discente de temas científicos e sociais.', 'assets/images/autores/marina.jpg'),
(3, 'Gabriel Castanho', 'gabriel.castanho@aluno.sescsenac.edu.br', '3º ano do Ensino Médio Integrado', 'Colunista de Opinião', 'Estudante entusiasta de cidadania ativa, história e educação.', 'assets/images/autores/gabriel.jpg'),
(4, 'Mateus Guedes', 'mateus.guedes@aluno.sescsenac.edu.br', '2º Ano Técnico em Logística', 'Repórter Esportivo', 'Cobridor oficial dos Jogos Interclasses e eventos esportivos.', 'assets/images/autores/mateus.jpg'),
(5, 'Sofia Alencar', 'sofia.alencar@aluno.sescsenac.edu.br', '3º Ano Técnico em Administração', 'Repórter Esportivo', 'Fotógrafa e redatora esportiva do Farol.', 'assets/images/autores/sofia.jpg'),
(6, 'Aline Andrade', 'aline.andrade@aluno.sescsenac.edu.br', '3º B', 'Pesquisadora Discente', 'Estudante do núcleo de energias sustentáveis.', 'assets/images/autores/aline.jpg');

-- Categorias fiéis ao menu da referência visual
INSERT INTO `categorias` (`id`, `nome`, `slug`, `descricao`, `cor`, `ordem`, `no_menu`, `ativo`) VALUES
(1, 'JOGOS ESCOLARES', 'jogos-escolares', 'Cobertura esportiva dos torneios e interclasses', '#b91c1c', 1, 1, 1),
(2, 'CLUBE DO LIVRO', 'clube-do-livro', 'Leituras do mês, debates e resenhas literárias', '#b91c1c', 2, 1, 1),
(3, 'CLUBE DE CIÊNCIAS', 'clube-de-ciencias', 'Iniciação científica, laboratórios e tecnologia', '#b91c1c', 3, 1, 1),
(4, 'PROCESSOS SELETIVOS', 'processos-seletivos', 'Editais, vestibulares, bolsas e estágios', '#b91c1c', 4, 1, 1),
(5, 'FECCI', 'fecci', 'Feira de Ciência e Cultura da Instituição', '#b91c1c', 5, 1, 1),
(6, 'SBPC', 'sbpc', 'Sociedade Brasileira para o Progresso da Ciência', '#b91c1c', 6, 1, 1),
(7, 'FEIRA DO CONHECIMENTO', 'feira-do-conhecimento', 'Mostra anual de trabalhos e projetos integradores', '#b91c1c', 7, 1, 1),
(8, 'FEIRA DO EMPREENDEDORISMO', 'feira-do-empreendedorismo', 'Projetos de gestão e economia solidária', '#b91c1c', 8, 1, 1),
(9, 'FEIRA CULTURAL', 'feira-cultural', 'Expressões artísticas, literatura e apresentações', '#b91c1c', 9, 1, 1);

-- Edição Atual (Nº 18 • Junho de 2026)
INSERT INTO `edicoes` (`id`, `numero`, `titulo`, `mes_ano`, `data_publicacao`, `destaque`) VALUES
(1, 18, 'Edição Especial: Protagonismo Estudantil e Ciência', 'Junho de 2026', '2026-06-15', 1);

-- Notícias fiéis à referência visual
INSERT INTO `noticias` (`id`, `categoria_id`, `edicao_id`, `titulo`, `subtitulo`, `slug`, `chapeu`, `conteudo`, `citacao_destaque`, `citacao_autor`, `imagem_capa`, `legenda_capa`, `credito_foto`, `tempo_leitura`, `professor_orientador`, `status`, `publicado_em`, `posicao_homepage`, `ordem_home`) VALUES
(1, 5, 1, 'FECCI & SBPC Jovem: Alunos do Sesc-Senac conquistam premiação nacional com projetos de sustentabilidade e robótica', 
 'A delegação discente representou a instituição na etapa nacional realizada em Brasília, apresentando um protótipo acessível de filtragem hídrica alimentado por placas solares e um braço robótico construído com peças de descarte técnico. A bancada avaliadora laureou o grupo com o 1º Lugar em Tecnologia Social.',
 'fecci-sbpc-jovem-alunos-sesc-senac-premiacao-nacional',
 'PREMIAÇÃO NACIONAL',
 '<p>A delegação discente representou a instituição na etapa nacional realizada em Brasília, apresentando um protótipo acessível de filtragem hídrica alimentado por placas solares e um braço robótico construído com peças de descarte técnico. A bancada avaliadora laureou o grupo com o 1º Lugar em Tecnologia Social.</p><p>Durante três dias de apresentações intensas, os estudantes defenderam seus projetos diante de pesquisadores e professores de todo o país. O trabalho uniu sustentabilidade e robótica em uma solução prática para comunidades com acesso limitado a água potável.</p><p>O projeto contou com a mentoria dos professores dos cursos técnicos e reforça o compromisso da formação integral, unindo pesquisa aplicada à solução de dilemas socioambientais reais.</p>',
 'Ver nossa pesquisa desenvolvida na oficina do Senac ganhando visibilidade na maior conferência científica da América Latina comprova o poder do ensino integrado.',
 'Beatriz Vasconcelos, 3º Ano do Técnico em Automação',
 'assets/images/hero-premiacao.jpg',
 'Registro: Alunos expositores no estande institucional durante a cerimônia de encerramento da SBPC Jovem.',
 'Foto: Acervo Grêmio Estudantil / Lucas Ferraz',
 5,
 'Prof. Dr. Hamilton Queiroz',
 'publicado',
 '2026-06-16 08:30:00',
 'manchete_hero',
 1),

(2, 3, 1, 'Protótipos de energia fotovoltaica',
 'O Projeto Integrador do módulo de Eletrônica desenvolveu microgeradores solares automatizados que otimizam o sistema de irrigação por gotejamento sem demandar carga da rede elétrica escolar.',
 'prototipos-de-energia-fotovoltaica',
 'PROJETO INTEGRADOR',
 '<p>O Projeto Integrador do módulo de Eletrônica desenvolveu microgeradores solares automatizados que otimizam o sistema de irrigação por gotejamento sem demandar carga da rede elétrica escolar. A iniciativa visa diminuir o consumo institucional e educar sobre fontes renováveis de matriz energética.</p>',
 NULL, NULL,
 'assets/images/ciencia-lab.jpg',
 'Oficina 04 - Laboratório de Energias Sustentáveis.',
 'Foto: Redação',
 3,
 'Prof. Carlos Alberto',
 'publicado',
 '2026-06-16 09:00:00',
 'ciencia_tecnica',
 1),

(3, 1, 1, 'Torcida vibra com vaga histórica do Técnico em Informática na final do Futsal Masculino',
 'Num jogo equilibrado decidido nos minutos finais, a equipe derrotou os atuais campeões do Técnico em Administração pelo placar de 4 a 3. No vôlei feminino, a turma do 3º ano de Logística confirmou o favoritismo e disputará o ouro na próxima sexta-feira.',
 'torcida-vibra-com-vaga-historica-informatica-final-futsal',
 'FASE SEMIFINAL',
 '<p>Num jogo emocionante decidido nos minutos finais, a equipe de Informática 3º A derrotou Administração pelo placar apertado de 4 a 3. A torcida lotou as arquibancadas da quadra poliesportiva com cantos e bandeiras.</p>',
 NULL, NULL,
 NULL, NULL, NULL,
 4,
 'Profª. Fernanda Lima',
 'publicado',
 '2026-06-16 10:15:00',
 'jogos_esportes',
 1),

(4, 8, 1, 'Cooperativa jovem prepara estandes gastronômicos e biojóias',
 'Mais de 18 planos de negócios desenvolvidos nas disciplinas de gestão técnica viram empresas reais com moeda social fictícia e faturamento auditado.',
 'cooperativa-jovem-prepara-estandes-gastronomicos-e-biojoias',
 'FEIRA DO EMPREENDEDORISMO',
 '<p>Mais de 18 planos de negócios desenvolvidos nas disciplinas de gestão técnica viram empresas reais com moeda social fictícia e faturamento auditado durante a Feira anual de Empreendedorismo.</p>',
 NULL, NULL,
 NULL, NULL, NULL,
 3,
 'Prof. Marcos Vinicius',
 'publicado',
 '2026-06-16 11:00:00',
 'cultura_empreendedorismo',
 1),

(5, 9, 1, 'Oficinas de cordel, teatro lambe-lambe e orquestra de violões',
 'O circuito cultural acolhe mais de 12 apresentações autorais de poesia e música compostas por estudantes no palco do Memorial Sesc.',
 'oficinas-de-cordel-teatro-lambe-lambe-e-orquestra-de-violoes',
 'FEIRA CULTURAL SESC-SENAC',
 '<p>O circuito cultural acolhe mais de 12 apresentações autorais de poesia e música compostas por estudantes no palco do Memorial Sesc com oficinas abertas para a comunidade.</p>',
 NULL, NULL,
 NULL, NULL, NULL,
 4,
 'Profª. Denise Castro',
 'publicado',
 '2026-06-16 11:30:00',
 'cultura_empreendedorismo',
 2),

(6, 7, 1, 'A escola como espaço de debate vivo, não apenas de transmissão passiva',
 'Ao completarmos 18 edições ininterruptas de O Farol, percebemos que o maior aprendizado que construímos dentro das paredes do Sesc e dos laboratórios do Senac transcende os ementários formais. O jornal escolar tornou-se nosso instrumento de escuta, autocrítica e celebração dos feitos coletivos. Quando a juventude assume o comando da sua própria narrativa, a ciência ganha calor humano e a rotina escolar transforma-se em cidadania ativa.',
 'a-escola-como-espaco-de-debate-vivo-nao-apenas-de-transmissao-passiva',
 'COLUNA DE OPINIÃO',
 '<p>Ao completarmos 18 edições ininterruptas de O Farol, percebemos que o maior aprendizado que construímos dentro das paredes do Sesc e dos laboratórios do Senac transcende os ementários formais. O jornal escolar tornou-se nosso instrumento de escuta, autocrítica e celebração dos feitos coletivos. Quando a juventude assume o comando da sua própria narrativa, a ciência ganha calor humano e a rotina escolar transforma-se em cidadania ativa.</p>',
 NULL, NULL,
 NULL, NULL, NULL,
 3,
 NULL,
 'publicado',
 '2026-06-16 12:00:00',
 'coluna_opiniao',
 1);

-- Relação Notícia x Autores
INSERT INTO `noticia_autores` (`noticia_id`, `autor_id`) VALUES
(1, 1), -- Enzo Oliveira
(1, 2), -- Marina Prado
(2, 6), -- Aline Andrade
(3, 4), -- Mateus Guedes
(3, 5), -- Sofia Alencar
(6, 3); -- Gabriel Castanho

-- Quadro de Resultados Esportivos
INSERT INTO `jogos_resultados` (`id`, `modalidade`, `equipe_a`, `placar_a`, `placar_b`, `equipe_b`, `fase`, `ordem`) VALUES
(1, 'Futsal Masculino', 'Informática 3º A', 4, 3, 'Adm 2º B', 'Semifinal', 1),
(2, 'Futsal Masculino', 'Logística 1º C', 2, 1, 'Eventos 3º A', 'Semifinal', 2),
(3, 'Basquete Misto', 'Edificações 2º A', 18, 16, 'Redes 3º B', 'Classificatória', 3);

-- Sidebar: Clube do Livro (Obra do Mês)
INSERT INTO `clube_livro` (`id`, `titulo_livro`, `autor_livro`, `chamada_editorial`, `resumo`, `proxima_leitura_titulo`, `proxima_leitura_autor`, `info_inscricao`, `ativo`) VALUES
(1, 'Torto Arado', 'Itamar Vieira Junior', 'Terra, silêncio e ancestralidade: O debate que lotou a biblioteca escolar', 'O encontro mensal mediado pelo Grêmio reuniu 45 discentes e professores de sociologia para debater os paralelos entre a narrativa de Bibiana e Belonísia e as realidades no meio rural contemporâneo.', '"Quarto de Despejo"', 'Carolina Maria de Jesus', 'Inscrições abertas no balcão da biblioteca.', 1);

-- Sidebar: Processos Seletivos
INSERT INTO `processos_seletivos` (`id`, `ano_referencia`, `categoria`, `titulo`, `descricao`, `link_edital`, `data_limite`, `ordem`) VALUES
(1, '2026/2027', 'SENAC 2026', 'Período de Inscrições & Isenção', 'Abertas as inscrições para os programas de gratuidade regimental nos cursos técnicos.', '#', '05 de Julho', 1),
(2, '2026/2027', 'FACULDADE SESC', 'Bolsas de Mérito Técnico', 'Alunos concluintes do ensino médio integrado concorrem a bolsas de até 100% para os cursos superiores de tecnologia.', '#', '15 de Julho', 2),
(3, '2026/2027', 'VESTIBULARES PÚBLICOS', 'Simulado Geral UFPE / USP / UFPR', 'Guia de orientação de datas e aulões preparatórios da coordenação pedagógica.', '#', 'Consulte o calendário', 3);

-- Sidebar: Enquete Interativa (Voz do Aluno)
INSERT INTO `enquetes` (`id`, `pergunta`, `descricao`, `ativo`, `data_inicio`, `data_fim`) VALUES
(1, 'Qual tema você quer ver com maior profundidade na Feira do Conhecimento deste ano?', 'Vote na temática que deve receber maior cobertura do jornal.', 1, '2026-06-01', '2026-06-30');

INSERT INTO `enquete_opcoes` (`id`, `enquete_id`, `opcao`, `votos_count`, `ordem`) VALUES
(1, 1, 'Inteligência Artificial & O Mercado de Trabalho', 142, 1),
(2, 1, 'Bioeconomia e Soluções Climáticas', 89, 2),
(3, 1, 'Saúde Mental e Rotina de Estudos', 115, 3);

-- Flagrantes da Semana
INSERT INTO `flagrantes_semana` (`id`, `titulo`, `legenda`, `foto_url`, `fotografo`, `campus_local`, `destaque`) VALUES
(1, 'Campus em Foco', 'Intervalo cultural espontâneo no pátio com alunos do 2º ano tocando violão e flauta antes da Feira Cultural.', 'assets/images/flagrante-semana.jpg', 'Lucas Rios', 'Campus em Foco', 1);

-- Configurações Institucionais do Jornal
INSERT INTO `configuracoes` (`chave`, `valor`, `grupo`) VALUES
('site_nome', 'O FAROL', 'geral'),
('site_subtitulo', 'Jornal Escolar Sesc-Senac', 'geral'),
('site_descricao', 'Órgão informativo laboratorial do Centro Integrado de Ensino Médio e Técnico. Publicação mensal de incentivo à alfabetização científica, pensamento crítico e expressão cidadã discente.', 'geral'),
('edicao_atual_numero', '18', 'geral'),
('edicao_atual_data', 'Junho de 2026', 'geral'),
('corpo_editorial_discente', 'Gabriel Castanho (Editor-Chefe)\nMarina Prado (Editora de Ciências)\nEnzo Oliveira (Repórter de Tecnologia)\nMateus Guedes (Repórter Esportivo)\nSofia Alencar (Fotografia e Mídia)', 'rodape'),
('orientacao_pedagogica', 'Prof. Dr. Hamilton Queiroz (Língua Portuguesa)\nProfª. Me. Clara Silveira (Sociologia e Projetos)\nProf. Sérgio Nogueira (Coordenador Senac)\nCoordenação Pedagógica da Escola Média', 'rodape'),
('links_oficiais', 'Expediente Discente|#\nManual de Redação|#\nRegulamento Editorial|#\nInstituição Mantenedora|#\nConselho de Ética|#\nOuvidoria da Instituição|#', 'rodape'),
('sede_redacao', 'Sala 204 • Bloco Pedagógico Central\nCâmpus Integrado Sesc-Senac\nredacao.ofarol@sescsenac.edu.br\nRamal Interno: 74212', 'rodape'),
('direitos_autorais', '© 2026 O Farol – Jornal Escolar Sesc-Senac. Produzido colaborativamente pelo corpo discente. Todos os direitos reservados.', 'rodape'),
('banner_chamada_titulo', 'Tem uma história, projeto científico ou evento na sua turma?', 'banner'),
('banner_chamada_texto', 'A redação de "O Farol" é feita por e para os estudantes. Envie sua sugestão de pauta, resenha de livro ou resultado esportivo para os nossos repórteres.', 'banner'),
('banner_chamada_botao', 'ENVIE SUA PAUTA / SUGESTÃO', 'banner');
