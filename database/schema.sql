-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 10/03/2026 às 10:35
-- Versão do servidor: 8.0.45
-- Versão do PHP: 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `flivocom_bd_pi1`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `aplicacoes_peca`
--

CREATE TABLE `aplicacoes_peca` (
  `id` int UNSIGNED NOT NULL,
  `tipo_peca_id` int UNSIGNED NOT NULL,
  `veiculo_configuracao_id` int UNSIGNED NOT NULL,
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias_peca`
--

CREATE TABLE `categorias_peca` (
  `id` int UNSIGNED NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoques`
--

CREATE TABLE `estoques` (
  `id` int UNSIGNED NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localizacao` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `marcas_produto`
--

CREATE TABLE `marcas_produto` (
  `id` int UNSIGNED NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `marcas_veiculo`
--

CREATE TABLE `marcas_veiculo` (
  `id` int UNSIGNED NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `modelos_veiculo`
--

CREATE TABLE `modelos_veiculo` (
  `id` int UNSIGNED NOT NULL,
  `marca_veiculo_id` int UNSIGNED NOT NULL,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `movimentacoes_estoque`
--

CREATE TABLE `movimentacoes_estoque` (
  `id` bigint UNSIGNED NOT NULL,
  `produto_id` int UNSIGNED NOT NULL,
  `estoque_id` int UNSIGNED NOT NULL,
  `usuario_id` int UNSIGNED DEFAULT NULL,
  `tipo_movimento` enum('entrada','saida','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantidade` int NOT NULL,
  `custo_unitario` decimal(10,2) DEFAULT NULL,
  `observacao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int UNSIGNED NOT NULL,
  `tipo_peca_id` int UNSIGNED NOT NULL,
  `marca_produto_id` int UNSIGNED NOT NULL,
  `sku_interno` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_fabricante` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo_barras` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nome_comercial` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `custo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `preco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `estoque_minimo` int NOT NULL DEFAULT '0',
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `tipos_peca`
--

CREATE TABLE `tipos_peca` (
  `id` int UNSIGNED NOT NULL,
  `categoria_peca_id` int UNSIGNED NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int UNSIGNED NOT NULL,
  `nome` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `veiculos_configuracao`
--

CREATE TABLE `veiculos_configuracao` (
  `id` int UNSIGNED NOT NULL,
  `modelo_veiculo_id` int UNSIGNED NOT NULL,
  `ano_inicio` smallint UNSIGNED NOT NULL,
  `ano_fim` smallint UNSIGNED NOT NULL,
  `motorizacao` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `combustivel` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `versao` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `aplicacoes_peca`
--
ALTER TABLE `aplicacoes_peca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_aplicacoes_peca_tipo_veiculo` (`tipo_peca_id`,`veiculo_configuracao_id`),
  ADD KEY `idx_aplicacoes_peca_tipo` (`tipo_peca_id`),
  ADD KEY `idx_aplicacoes_peca_veiculo` (`veiculo_configuracao_id`);

--
-- Índices de tabela `categorias_peca`
--
ALTER TABLE `categorias_peca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_categorias_peca_nome` (`nome`),
  ADD KEY `idx_categorias_peca_ativo` (`ativo`);

--
-- Índices de tabela `estoques`
--
ALTER TABLE `estoques`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_estoques_nome` (`nome`),
  ADD KEY `idx_estoques_ativo` (`ativo`);

--
-- Índices de tabela `marcas_produto`
--
ALTER TABLE `marcas_produto`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marcas_produto_nome` (`nome`),
  ADD KEY `idx_marcas_produto_ativo` (`ativo`);

--
-- Índices de tabela `marcas_veiculo`
--
ALTER TABLE `marcas_veiculo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_marcas_veiculo_nome` (`nome`),
  ADD KEY `idx_marcas_veiculo_ativo` (`ativo`);

--
-- Índices de tabela `modelos_veiculo`
--
ALTER TABLE `modelos_veiculo`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_modelos_veiculo_marca_nome` (`marca_veiculo_id`,`nome`),
  ADD KEY `idx_modelos_veiculo_marca` (`marca_veiculo_id`),
  ADD KEY `idx_modelos_veiculo_nome` (`nome`),
  ADD KEY `idx_modelos_veiculo_ativo` (`ativo`);

--
-- Índices de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_movimentacoes_estoque_produto` (`produto_id`),
  ADD KEY `idx_movimentacoes_estoque_estoque` (`estoque_id`),
  ADD KEY `idx_movimentacoes_estoque_usuario` (`usuario_id`),
  ADD KEY `idx_movimentacoes_estoque_tipo` (`tipo_movimento`),
  ADD KEY `idx_movimentacoes_estoque_criado_em` (`criado_em`),
  ADD KEY `idx_movimentacoes_estoque_produto_data` (`produto_id`,`criado_em`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_produtos_sku_interno` (`sku_interno`),
  ADD UNIQUE KEY `uq_produtos_marca_codigo_fabricante` (`marca_produto_id`,`codigo_fabricante`),
  ADD UNIQUE KEY `uq_produtos_codigo_barras` (`codigo_barras`),
  ADD KEY `idx_produtos_tipo_peca` (`tipo_peca_id`),
  ADD KEY `idx_produtos_marca_produto` (`marca_produto_id`),
  ADD KEY `idx_produtos_nome_comercial` (`nome_comercial`),
  ADD KEY `idx_produtos_ativo` (`ativo`),
  ADD KEY `idx_produtos_codigo_fabricante` (`codigo_fabricante`);

--
-- Índices de tabela `tipos_peca`
--
ALTER TABLE `tipos_peca`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tipos_peca_categoria_nome` (`categoria_peca_id`,`nome`),
  ADD KEY `idx_tipos_peca_nome` (`nome`),
  ADD KEY `idx_tipos_peca_ativo` (`ativo`),
  ADD KEY `idx_tipos_peca_categoria` (`categoria_peca_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`),
  ADD KEY `idx_usuarios_ativo` (`ativo`),
  ADD KEY `idx_usuarios_nome` (`nome`);

--
-- Índices de tabela `veiculos_configuracao`
--
ALTER TABLE `veiculos_configuracao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_veiculos_configuracao_unica` (`modelo_veiculo_id`,`ano_inicio`,`ano_fim`,`motorizacao`,`combustivel`,`versao`),
  ADD KEY `idx_veiculos_configuracao_modelo` (`modelo_veiculo_id`),
  ADD KEY `idx_veiculos_configuracao_ano_inicio` (`ano_inicio`),
  ADD KEY `idx_veiculos_configuracao_ano_fim` (`ano_fim`),
  ADD KEY `idx_veiculos_configuracao_motorizacao` (`motorizacao`),
  ADD KEY `idx_veiculos_configuracao_combustivel` (`combustivel`),
  ADD KEY `idx_veiculos_configuracao_versao` (`versao`),
  ADD KEY `idx_veiculos_configuracao_ativo` (`ativo`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `aplicacoes_peca`
--
ALTER TABLE `aplicacoes_peca`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias_peca`
--
ALTER TABLE `categorias_peca`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `estoques`
--
ALTER TABLE `estoques`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `marcas_produto`
--
ALTER TABLE `marcas_produto`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `marcas_veiculo`
--
ALTER TABLE `marcas_veiculo`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `modelos_veiculo`
--
ALTER TABLE `modelos_veiculo`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  MODIFY `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `tipos_peca`
--
ALTER TABLE `tipos_peca`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `veiculos_configuracao`
--
ALTER TABLE `veiculos_configuracao`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `aplicacoes_peca`
--
ALTER TABLE `aplicacoes_peca`
  ADD CONSTRAINT `fk_aplicacoes_peca_tipo` FOREIGN KEY (`tipo_peca_id`) REFERENCES `tipos_peca` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_aplicacoes_peca_veiculo` FOREIGN KEY (`veiculo_configuracao_id`) REFERENCES `veiculos_configuracao` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Restrições para tabelas `modelos_veiculo`
--
ALTER TABLE `modelos_veiculo`
  ADD CONSTRAINT `fk_modelos_veiculo_marca` FOREIGN KEY (`marca_veiculo_id`) REFERENCES `marcas_veiculo` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Restrições para tabelas `movimentacoes_estoque`
--
ALTER TABLE `movimentacoes_estoque`
  ADD CONSTRAINT `fk_movimentacoes_estoque_estoque` FOREIGN KEY (`estoque_id`) REFERENCES `estoques` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimentacoes_estoque_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_movimentacoes_estoque_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produtos_marca_produto` FOREIGN KEY (`marca_produto_id`) REFERENCES `marcas_produto` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_produtos_tipo_peca` FOREIGN KEY (`tipo_peca_id`) REFERENCES `tipos_peca` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Restrições para tabelas `tipos_peca`
--
ALTER TABLE `tipos_peca`
  ADD CONSTRAINT `fk_tipos_peca_categoria` FOREIGN KEY (`categoria_peca_id`) REFERENCES `categorias_peca` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Restrições para tabelas `veiculos_configuracao`
--
ALTER TABLE `veiculos_configuracao`
  ADD CONSTRAINT `fk_veiculos_configuracao_modelo` FOREIGN KEY (`modelo_veiculo_id`) REFERENCES `modelos_veiculo` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
