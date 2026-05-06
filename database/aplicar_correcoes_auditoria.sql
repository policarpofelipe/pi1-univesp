-- Aplicar em bases MySQL já existentes criadas antes das correções de modelagem.
-- Bancos novos: use schema.sql completo.

SET NAMES utf8mb4;

ALTER TABLE `movimentacoes_estoque`
  MODIFY COLUMN `quantidade` decimal(10,2) NOT NULL;

CREATE TABLE IF NOT EXISTS `config_empresa` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `razao_social` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome_fantasia` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cnpj` varchar(18) COLLATE utf8mb4_unicode_ci NOT NULL,
  `inscricao_estadual` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cep` varchar(9) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logradouro` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `complemento` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bairro` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cidade` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uf` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_config_empresa_cnpj` (`cnpj`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `aplicacoes_peca`;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

CREATE TABLE IF NOT EXISTS `aplicacoes_produto` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `produto_id` int UNSIGNED NOT NULL,
  `veiculo_configuracao_id` int UNSIGNED NOT NULL,
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_aplicacoes_produto_produto_veiculo` (`produto_id`, `veiculo_configuracao_id`),
  KEY `idx_aplicacoes_produto_produto` (`produto_id`),
  KEY `idx_aplicacoes_produto_veiculo` (`veiculo_configuracao_id`),
  KEY `idx_aplicacoes_produto_ativo` (`ativo`),
  CONSTRAINT `fk_aplicacoes_produto_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_aplicacoes_produto_veiculo`
    FOREIGN KEY (`veiculo_configuracao_id`) REFERENCES `veiculos_configuracao` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
