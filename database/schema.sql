-- ========================================================
-- Prisca Pizza - Banco de Dados MySQL
-- Schema de Criação das Tabelas
-- ========================================================

CREATE DATABASE IF NOT EXISTS `prisca_pizza` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `prisca_pizza`;

-- Desabilita checagem de chaves para recriação limpa se necessário
SET FOREIGN_KEY_CHECKS = 0;

-- 1. TABELA DE USUÁRIOS
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `telefone` VARCHAR(25) NULL,
  `endereco` TEXT NULL,
  `perfil` ENUM('cliente', 'caixa', 'pizzaiolo', 'entregador', 'admin') NOT NULL DEFAULT 'cliente',
  `tentativas_login` INT DEFAULT 0,
  `bloqueado_ate` DATETIME NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_usuario_email` (`email`),
  INDEX `idx_usuario_perfil` (`perfil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABELA DE CATEGORIAS DO CARDÁPIO
DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(50) NOT NULL UNIQUE,
  `nome` VARCHAR(100) NOT NULL,
  `descricao` VARCHAR(255) NULL,
  `icone` VARCHAR(50) DEFAULT 'pizza-slice',
  `ordem` INT DEFAULT 0,
  `ativo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABELA DE PRODUTOS
DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_id` INT NOT NULL,
  `nome` VARCHAR(150) NOT NULL,
  `descricao` TEXT NULL,
  `preco_base` DECIMAL(10,2) NOT NULL,
  `imagem` VARCHAR(255) NULL,
  `disponivel` TINYINT(1) DEFAULT 1,
  `permite_meio_a_meio` TINYINT(1) DEFAULT 0,
  `destaque` TINYINT(1) DEFAULT 0,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE CASCADE,
  INDEX `idx_prod_cat` (`categoria_id`),
  INDEX `idx_prod_disp` (`disponivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABELA DE TAMANHOS
DROP TABLE IF EXISTS `tamanhos`;
CREATE TABLE `tamanhos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `categoria_slug` VARCHAR(50) NOT NULL,
  `nome` VARCHAR(60) NOT NULL,
  `fatias` VARCHAR(30) NULL,
  `multiplicador_preco` DECIMAL(4,2) DEFAULT 1.00,
  `max_sabores` INT DEFAULT 1,
  `ativo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABELA DE ADICIONAIS E BORDAS
DROP TABLE IF EXISTS `adicionais`;
CREATE TABLE `adicionais` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` VARCHAR(50) NOT NULL DEFAULT 'ingrediente', -- 'borda', 'massa', 'ingrediente'
  `nome` VARCHAR(100) NOT NULL,
  `preco` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `categoria_slug` VARCHAR(50) DEFAULT 'pizzas',
  `ativo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABELA DE PEDIDOS
DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(30) NOT NULL UNIQUE,
  `cliente_id` INT NOT NULL,
  `entregador_id` INT NULL,
  `tipo_entrega` ENUM('entrega', 'retirada') NOT NULL DEFAULT 'entrega',
  `endereco_entrega` TEXT NULL,
  `telefone_contato` VARCHAR(25) NULL,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `taxa_entrega` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `desconto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(10,2) NOT NULL,
  `forma_pagamento` ENUM('pix', 'cartao', 'dinheiro') NOT NULL,
  `status_pagamento` ENUM('pendente', 'pago', 'recusado', 'a_receber') NOT NULL DEFAULT 'pendente',
  `troco_para` DECIMAL(10,2) NULL,
  `pix_codigo` TEXT NULL,
  `pix_qrcode` TEXT NULL,
  `status` ENUM('Recebido', 'Em preparo', 'Pronto', 'Saiu para entrega', 'Entregue', 'Cancelado') NOT NULL DEFAULT 'Recebido',
  `tempo_estimado_min` INT DEFAULT 45,
  `observacoes` TEXT NULL,
  `motivo_cancelamento` TEXT NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`entregador_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_ped_status` (`status`),
  INDEX `idx_ped_cliente` (`cliente_id`),
  INDEX `idx_ped_entregador` (`entregador_id`),
  INDEX `idx_ped_criado` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABELA DE ITENS DO PEDIDO
DROP TABLE IF EXISTS `itens_pedido`;
CREATE TABLE `itens_pedido` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `produto_id` INT NOT NULL,
  `nome_produto` VARCHAR(150) NOT NULL,
  `tamanho_nome` VARCHAR(60) NULL,
  `sabor_secundario_nome` VARCHAR(150) NULL,
  `adicionais_selecionados` TEXT NULL,
  `observacoes` TEXT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  `preco_unitario` DECIMAL(10,2) NOT NULL,
  `subtotal_item` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE RESTRICT,
  INDEX `idx_item_ped` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABELA DE HISTÓRICO E AUDITORIA (Regra CORE de rastreabilidade)
DROP TABLE IF EXISTS `logs_auditoria`;
CREATE TABLE `logs_auditoria` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NULL,
  `usuario_id` INT NULL,
  `usuario_nome` VARCHAR(120) NOT NULL,
  `usuario_perfil` VARCHAR(50) NOT NULL,
  `acao` VARCHAR(100) NOT NULL,
  `status_anterior` VARCHAR(50) NULL,
  `status_novo` VARCHAR(50) NULL,
  `detalhes` TEXT NULL,
  `ip` VARCHAR(45) NULL,
  `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL,
  INDEX `idx_log_ped` (`pedido_id`),
  INDEX `idx_log_user` (`usuario_id`),
  INDEX `idx_log_acao` (`acao`),
  INDEX `idx_log_data` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABELA DE CONFIGURAÇÕES DO SISTEMA
DROP TABLE IF EXISTS `configuracoes`;
CREATE TABLE `configuracoes` (
  `chave` VARCHAR(60) PRIMARY KEY,
  `valor` TEXT NOT NULL,
  `descricao` VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
