-- Criação incremental da tabela de múltiplas imagens por produto.
-- Compatível com o schema atual: produtos.id = INT UNSIGNED AUTO_INCREMENT.
-- Não altera a tabela produtos.
-- Engine/charset/collation alinhados ao projeto.

SET NAMES utf8mb4;

-- ============================================================
-- VERSÃO 1 (preferencial): com CHECK para validar principal
-- Use esta versão em MySQL/MariaDB com suporte efetivo a CHECK.
-- ============================================================

CREATE TABLE IF NOT EXISTS `produto_imagens` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `produto_id` int UNSIGNED NOT NULL,
  `caminho_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_arquivo` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tamanho_bytes` int UNSIGNED NOT NULL,
  `largura` int UNSIGNED DEFAULT NULL,
  `altura` int UNSIGNED DEFAULT NULL,
  `ordem` smallint UNSIGNED NOT NULL DEFAULT 0,
  `principal` tinyint(1) NOT NULL DEFAULT 0,
  `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_produto_imagens_caminho_arquivo` (`caminho_arquivo`),
  KEY `idx_produto_imagens_produto` (`produto_id`),
  KEY `idx_produto_imagens_produto_ordem` (`produto_id`,`ordem`),
  KEY `idx_produto_imagens_produto_principal` (`produto_id`,`principal`),
  CONSTRAINT `fk_produto_imagens_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `chk_produto_imagens_principal` CHECK (`principal` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- VERSÃO 2 (alternativa): sem CHECK
-- Use esta versão se seu ambiente não suportar CHECK de forma
-- confiável (algumas versões antigas de MySQL/MariaDB).
-- ============================================================

-- CREATE TABLE IF NOT EXISTS `produto_imagens` (
--   `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
--   `produto_id` int UNSIGNED NOT NULL,
--   `caminho_arquivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `nome_arquivo` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `nome_original` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
--   `mime_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
--   `tamanho_bytes` int UNSIGNED NOT NULL,
--   `largura` int UNSIGNED DEFAULT NULL,
--   `altura` int UNSIGNED DEFAULT NULL,
--   `ordem` smallint UNSIGNED NOT NULL DEFAULT 0,
--   `principal` tinyint(1) NOT NULL DEFAULT 0,
--   `alt_text` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
--   `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `uq_produto_imagens_caminho_arquivo` (`caminho_arquivo`),
--   KEY `idx_produto_imagens_produto` (`produto_id`),
--   KEY `idx_produto_imagens_produto_ordem` (`produto_id`,`ordem`),
--   KEY `idx_produto_imagens_produto_principal` (`produto_id`,`principal`),
--   CONSTRAINT `fk_produto_imagens_produto`
--     FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
--     ON DELETE CASCADE
--     ON UPDATE CASCADE
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
