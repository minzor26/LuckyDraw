-- ============================================================
-- Mobile Gallery Lucky Draw - Database Schema
-- Database Target: MySQL 5.7+ / 8.0+ / MariaDB
-- ============================================================

CREATE DATABASE IF NOT EXISTS `mobile_gallery_draw` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `mobile_gallery_draw`;

-- ------------------------------------------------------------
-- 1. Table: users (Admin Users)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` VARCHAR(20) DEFAULT 'admin',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Table: coupons (Customer Coupons)
-- Statuses: 'eligible', 'ineligible', 'winner', 'used'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `coupons` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_number` INT NOT NULL UNIQUE,
    `customer_name` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `address` VARCHAR(255) NULL,
    `city` VARCHAR(100) NULL,
    `state` VARCHAR(100) NULL,
    `status` ENUM('eligible', 'ineligible', 'winner', 'used') DEFAULT 'eligible',
    `purchase_date` DATE NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_coupon_number` (`coupon_number`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 3. Table: prizes (Prize Inventory)
-- Types: 'special', 'regular'
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `prizes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `type` ENUM('special', 'regular') NOT NULL DEFAULT 'regular',
    `quantity` INT NOT NULL DEFAULT 1,
    `remaining_quantity` INT NOT NULL DEFAULT 1,
    `image` VARCHAR(255) NULL,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_type_status` (`type`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 4. Table: special_prize_assignments (Pre-defined Winners)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `special_prize_assignments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prize_id` INT NOT NULL UNIQUE,
    `coupon_id` INT NOT NULL UNIQUE,
    `coupon_number` INT NOT NULL UNIQUE,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`prize_id`) REFERENCES `prizes` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 5. Table: winners (Winning Draw Records)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `winners` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `coupon_id` INT NOT NULL UNIQUE,
    `prize_id` INT NOT NULL,
    `coupon_number` INT NOT NULL UNIQUE,
    `customer_name` VARCHAR(100) NOT NULL,
    `mobile` VARCHAR(20) NOT NULL,
    `prize_name` VARCHAR(100) NOT NULL,
    `prize_type` ENUM('special', 'regular') NOT NULL,
    `is_test` TINYINT(1) DEFAULT 0,
    `won_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`coupon_id`) REFERENCES `coupons` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`prize_id`) REFERENCES `prizes` (`id`) ON DELETE CASCADE,
    INDEX `idx_won_at` (`won_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 6. Table: admin_audit_logs (Audit History for Admin Actions)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admin_audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `admin_username` VARCHAR(50) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 7. Table: settings (Campaign Settings)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
