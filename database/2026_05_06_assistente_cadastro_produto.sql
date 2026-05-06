SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `assistente_cadastro_produto` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` int UNSIGNED NOT NULL,
  `produto_id` int UNSIGNED DEFAULT NULL,
  `etapa_atual` tinyint UNSIGNED NOT NULL DEFAULT 1,
  `dados_json` json DEFAULT NULL,
  `status` enum('rascunho','em_andamento','concluido','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rascunho',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `concluido_em` datetime DEFAULT NULL,
  `cancelado_em` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_assistente_cadastro_produto_usuario` (`usuario_id`),
  KEY `idx_assistente_cadastro_produto_status` (`status`),
  KEY `idx_assistente_cadastro_produto_produto` (`produto_id`),
  KEY `idx_assistente_usuario_status` (`usuario_id`, `status`),
  CONSTRAINT `fk_assistente_cadastro_produto_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_assistente_cadastro_produto_produto`
    FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
