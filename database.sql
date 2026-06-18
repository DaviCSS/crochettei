CREATE DATABASE IF NOT EXISTS `crochettei` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crochettei`;

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `cpf` VARCHAR(11) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `senha` VARCHAR(255) NOT NULL,
  `telefone` VARCHAR(20) DEFAULT NULL,
  `endereco_completo` TEXT,
  `is_artesa` TINYINT(1) DEFAULT 0,
  `is_admin` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL  -- Soft Delete: usuários nunca são apagados fisicamente
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `categorias` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nome` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `produtos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `artesao_id` INT NOT NULL,
  `categoria_id` INT NOT NULL,
  `nome` VARCHAR(255) NOT NULL,
  `descricao` TEXT,
  `preco` DECIMAL(10,2) NOT NULL,
  `prazo_confeccao` INT NOT NULL DEFAULT 1,
  `estoque` INT NOT NULL DEFAULT 0,
  `tipo` ENUM('pronta_entrega', 'sob_encomenda') NOT NULL DEFAULT 'pronta_entrega',
  `imagem_path` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  FOREIGN KEY (`artesao_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `categorias`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cliente_id` INT NOT NULL,
  `artesao_id` INT NOT NULL,
  `valor_total` DECIMAL(10,2) NOT NULL,
  `endereco_entrega` TEXT NOT NULL,
  `status_pagamento` ENUM('pendente', 'pago') NOT NULL DEFAULT 'pendente',
  `status_entrega` ENUM('em_producao', 'aguardando_coleta', 'enviado', 'entregue', 'cancelado') NOT NULL DEFAULT 'em_producao',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`cliente_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`artesao_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `itens_pedido` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `pedido_id` INT NOT NULL,
  `produto_id` INT DEFAULT NULL,
  `nome_produto_historico` VARCHAR(255) NOT NULL,
  `preco_unitario_historico` DECIMAL(10,2) NOT NULL,
  `quantidade` INT NOT NULL DEFAULT 1,
  `subtotal` DECIMAL(10,2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`pedido_id`) REFERENCES `pedidos`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`produto_id`) REFERENCES `produtos`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Popular categorias (SEED)
INSERT IGNORE INTO `categorias` (`id`, `nome`, `slug`) VALUES
(1, 'Roupas', 'roupas'),
(2, 'Decoração', 'decoracao'),
(3, 'Amigurumis', 'amigurumis'),
(4, 'Bolsas', 'bolsas'),
(5, 'Acessórios', 'acessorios');
