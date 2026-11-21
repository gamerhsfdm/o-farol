-- ========================================================
-- Migração: 2026-11-21_enquetes_novas_funcionalidades.sql
-- Expande recursos de criação e gestão de enquetes escolares
-- ========================================================

-- 1. Novas colunas na tabela enquetes
SET @dbname = DATABASE();
SET @tablename = "enquetes";

-- permitir_multipla (votação de múltipla escolha: checkbox vs radio)
SET @columnname = "permitir_multipla";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE enquetes ADD COLUMN `permitir_multipla` TINYINT(1) NOT NULL DEFAULT 0 AFTER `descricao`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- max_escolhas (número máximo de opções que podem ser marcadas se múltipla escolha)
SET @columnname = "max_escolhas";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE enquetes ADD COLUMN `max_escolhas` INT NOT NULL DEFAULT 1 AFTER `permitir_multipla`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- exibir_resultados_apos (sempre, apos_votar, apos_encerrar)
SET @columnname = "exibir_resultados_apos";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE enquetes ADD COLUMN `exibir_resultados_apos` ENUM('sempre', 'apos_votar', 'apos_encerrar') NOT NULL DEFAULT 'apos_votar' AFTER `max_escolhas`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- categoria_tag (Ex: 'Cultura & Lazer', 'Estrutura Escolar', 'Cardápio / Refeitório', 'Eventos & Jogos')
SET @columnname = "categoria_tag";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE enquetes ADD COLUMN `categoria_tag` VARCHAR(80) NULL DEFAULT 'Geral' AFTER `exibir_resultados_apos`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- destaque_sidebar (se é a enquete prioritária em exibição na capa)
SET @columnname = "destaque_sidebar";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE enquetes ADD COLUMN `destaque_sidebar` TINYINT(1) NOT NULL DEFAULT 0 AFTER `categoria_tag`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Novas colunas na tabela enquete_opcoes (cor visual e ícone)
SET @tablename = "enquete_opcoes";
SET @columnname = "cor_destaque";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = @tablename AND table_schema = @dbname AND column_name = @columnname) > 0,
  "SELECT 1",
  "ALTER TABLE enquete_opcoes ADD COLUMN `cor_destaque` VARCHAR(20) NULL DEFAULT '#0f5368' AFTER `votos_count`;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
