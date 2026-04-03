-- Database for Bajot CRM
CREATE DATABASE IF NOT EXISTS `bajot_crm`;
USE `bajot_crm`;

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin', 'staff') DEFAULT 'staff',
  `status` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Parties Table
CREATE TABLE IF NOT EXISTS `parties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type` ENUM('customer', 'supplier') NOT NULL,
  `mobile` VARCHAR(15),
  `gst` VARCHAR(15),
  `address` TEXT,
  `opening_balance` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products Table
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `unit` VARCHAR(20) DEFAULT 'Kg',
  `opening_pcs` DECIMAL(15, 2) DEFAULT 0.00,
  `opening_kgs` DECIMAL(15, 2) DEFAULT 0.00,
  `total_pcs` DECIMAL(15, 2) DEFAULT 0.00,
  `total_kgs` DECIMAL(15, 2) DEFAULT 0.00,
  `rate` DECIMAL(15, 2) DEFAULT 0.00,
  `gst_percent` DECIMAL(5, 2) DEFAULT 0.00,
  `current_stock` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Inwards Table (Purchases)
CREATE TABLE IF NOT EXISTS `inwards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `party_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `bill_no` VARCHAR(50),
  `sub_total` DECIMAL(15, 2) DEFAULT 0.00,
  `gst_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`party_id`) REFERENCES `parties`(`id`)
);

-- Inward Items
CREATE TABLE IF NOT EXISTS `inward_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `inward_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` DECIMAL(15, 2) NOT NULL,
  `rate` DECIMAL(15, 2) NOT NULL,
  `total` DECIMAL(15, 2) NOT NULL,
  FOREIGN KEY (`inward_id`) REFERENCES `inwards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
);

-- Outwards Table (Sales)
CREATE TABLE IF NOT EXISTS `outwards` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `party_id` INT NOT NULL,
  `date` DATE NOT NULL,
  `bill_no` VARCHAR(50),
  `sub_total` DECIMAL(15, 2) DEFAULT 0.00,
  `gst_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `total_amount` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`party_id`) REFERENCES `parties`(`id`)
);

-- Outward Items
CREATE TABLE IF NOT EXISTS `outward_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `outward_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `qty` DECIMAL(15, 2) NOT NULL,
  `rate` DECIMAL(15, 2) NOT NULL,
  `total` DECIMAL(15, 2) NOT NULL,
  FOREIGN KEY (`outward_id`) REFERENCES `outwards`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`)
);

-- Vouchers Table (Cash/Bank)
CREATE TABLE IF NOT EXISTS `vouchers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` ENUM('receipt', 'payment', 'expense') NOT NULL,
  `party_id` INT,
  `amount` DECIMAL(15, 2) NOT NULL,
  `date` DATE NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`party_id`) REFERENCES `parties`(`id`)
);

-- Employees Table
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `role` VARCHAR(50),
  `salary` DECIMAL(15, 2) DEFAULT 0.00,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Settings Table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_name` VARCHAR(255),
  `company_gst` VARCHAR(20),
  `company_address` TEXT
);

-- Default User Login: admin / admin123
INSERT INTO `users` (`username`, `password`, `name`, `role`) VALUES ('admin', '$2y$10$8W3bFvKk6p9qJjLp4pZpZuJ7F6G8a/W3bFvKk6p9qJjLp4pZpZ', 'Admin User', 'admin');
