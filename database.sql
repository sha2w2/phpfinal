-- Password Manager Database Setup
-- Version 1
-- 1. Create database with proper character set
CREATE DATABASE IF NOT EXISTS `password_manager`
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `password_manager`;

-- 2. Users table (stores login credentials)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password` VARCHAR(255) NOT NULL COMMENT 'bcrypt hashed password',
  `encryption_key` TEXT NOT NULL COMMENT 'AES-256 encrypted master key',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `last_updated` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_unique` (`username`),
  INDEX `user_created_idx` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Stored passwords table
CREATE TABLE IF NOT EXISTS `stored_passwords` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `service_name` VARCHAR(100) NOT NULL COMMENT 'e.g. Facebook, Gmail',
  `service_username` VARCHAR(100),
  `encrypted_password` TEXT NOT NULL COMMENT 'AES-256 encrypted password',
  `url` VARCHAR(255),
  `notes` TEXT,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_service_unique` (`user_id`, `service_name`, `service_username`),
  INDEX `service_name_idx` (`service_name`),
  FULLTEXT INDEX `notes_ft_idx` (`notes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Password generation settings ( with primary key)
CREATE TABLE IF NOT EXISTS `password_settings` (
  `user_id` INT NOT NULL,
  `length` TINYINT UNSIGNED DEFAULT 12 CHECK (`length` BETWEEN 8 AND 64),
  `use_uppercase` BOOLEAN DEFAULT TRUE,
  `use_lowercase` BOOLEAN DEFAULT TRUE,
  `use_numbers` BOOLEAN DEFAULT TRUE,
  `use_special` BOOLEAN DEFAULT TRUE,
  `special_chars` VARCHAR(32) DEFAULT '!@#$%^&*',
  PRIMARY KEY (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--5. Password change history 
CREATE TABLE IF NOT EXISTS `password_history` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `changed_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  INDEX `user_changed_idx` (`user_id`, `changed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;