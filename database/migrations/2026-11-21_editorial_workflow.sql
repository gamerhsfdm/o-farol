-- ========================================================
-- Migração: 2026-11-21_editorial_workflow.sql
-- Adiciona suporte a notas editoriais (diálogo orientador-aluno)
-- e bloqueio otimista de edição simultânea em noticias
-- ========================================================

-- 1. Tabela noticia_notas (notas internas entre editor/orientador e redator)
CREATE TABLE IF NOT EXISTS `noticia_notas` (
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

-- 2. Colunas de bloqueio otimista de edição simultânea em noticias
-- (Verificação idempotente via procedure temporária ou alter table segura)
SET @dbname = DATABASE();
SET @tablename = "noticias";
SET @columnname = "editando_por";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE noticias ADD COLUMN `editando_por` INT NULL AFTER `criado_por`, ADD COLUMN `editando_em` DATETIME NULL AFTER `editando_por`, ADD CONSTRAINT `fk_noticias_editando_por` FOREIGN KEY (`editando_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL;"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
