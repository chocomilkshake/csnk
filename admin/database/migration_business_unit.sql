-- ========================================
-- Database Migration: Business Unit (Country) Support
-- ========================================
-- This migration adds business_unit_id to applicants and related tables,
-- creates business_units table, and admin_user_business_units table.
-- Run this script to enable multi-country/region support.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- 1. Create business_units table (Countries)
-- ========================================
CREATE TABLE IF NOT EXISTS `business_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL COMMENT 'Unique code e.g., CSNK-PH, SMC-TR',
  `name` VARCHAR(150) NOT NULL COMMENT 'Display name e.g., CSNK Philippines',
  `country` VARCHAR(100) DEFAULT NULL COMMENT 'Country name e.g., Philippines',
  `region` VARCHAR(100) DEFAULT NULL COMMENT 'Region e.g., Asia, Europe',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bu_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default business units (Countries)
INSERT INTO `business_units` (`code`, `name`, `country`, `region`, `is_active`) VALUES 
('CSNK-PH', 'CSNK Philippines', 'Philippines', 'Asia', 1),
('SMC-TR', 'SMC Turkey', 'Turkey', 'Europe/Asia', 1),
('SMC-BH', 'SMC Bahrain', 'Bahrain', 'Middle East', 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `country` = VALUES(`country`), `region` = VALUES(`region`);

-- ========================================
-- 2. Create admin_user_business_units table
-- ========================================
CREATE TABLE IF NOT EXISTS `admin_user_business_units` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_user_id` INT UNSIGNED NOT NULL,
  `business_unit_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_bu` (`admin_user_id`, `business_unit_id`),
  KEY `idx_admin_user_id` (`admin_user_id`),
  KEY `idx_business_unit_id` (`business_unit_id`),
  CONSTRAINT `fk_aub_admin` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_aub_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ========================================
-- 3. Add business_unit_id to applicants table
-- ========================================
ALTER TABLE `applicants` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `created_by`,
ADD INDEX `idx_applicants_bu` (`business_unit_id`);

-- Add foreign key constraint (optional, only if business_units table exists)
-- ALTER TABLE `applicants` 
-- ADD CONSTRAINT `fk_applicants_bu` 
-- FOREIGN KEY (`business_unit_id`) REFERENCES `business_units`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- ========================================
-- 4. Add business_unit_id to applicant_documents table
-- ========================================
ALTER TABLE `applicant_documents` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `applicant_id`,
ADD INDEX `idx_documents_bu` (`business_unit_id`);

-- ========================================
-- 5. Add business_unit_id to blacklisted_applicants table
-- ========================================
ALTER TABLE `blacklisted_applicants` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `applicant_id`,
ADD INDEX `idx_blacklist_bu` (`business_unit_id`);

-- ========================================
-- 6. Add business_unit_id to client_bookings table
-- ========================================
ALTER TABLE `client_bookings` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `applicant_id`,
ADD INDEX `idx_bookings_bu` (`business_unit_id`);

-- ========================================
-- 7. Add business_unit_id to applicant_replacements table
-- ========================================
ALTER TABLE `applicant_replacements` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `created_by`,
ADD INDEX `idx_replacements_bu` (`business_unit_id`);

-- ========================================
-- 8. Add business_unit_id to applicant_reports table
-- ========================================
ALTER TABLE `applicant_reports` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `admin_id`,
ADD INDEX `idx_reports_bu` (`business_unit_id`);

-- ========================================
-- 9. Add business_unit_id to applicant_status_reports table
-- ========================================
ALTER TABLE `applicant_status_reports` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `admin_id`,
ADD INDEX `idx_status_reports_bu` (`business_unit_id`);

-- ========================================
-- 10. Add business_unit_id to session_logs table
-- ========================================
ALTER TABLE `session_logs` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `admin_id`,
ADD INDEX `idx_session_bu` (`business_unit_id`);

-- ========================================
-- 11. Add business_unit_id to activity_logs table
-- ========================================
ALTER TABLE `activity_logs` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `admin_id`,
ADD INDEX `idx_activity_bu` (`business_unit_id`);

-- ========================================
-- 12. Update existing super_admin and admin users
-- Set their business_unit_id to NULL (global access)
-- ========================================
-- UPDATE `admin_users` SET `business_unit_id` = NULL WHERE `role` IN ('super_admin', 'admin');

-- ========================================
-- 13. Migrate existing applicants to default BU (CSNK-PH)
-- ========================================
-- This will assign all existing applicants to CSNK-PH (id=1)
-- UPDATE `applicants` SET `business_unit_id` = 1 WHERE `business_unit_id` IS NULL;

COMMIT;

/* ========================================
-- Queries to verify migration
-- ========================================
SELECT 'business_units' as tbl, COUNT(*) as cnt FROM business_units
UNION ALL
SELECT 'admin_user_business_units', COUNT(*) FROM admin_user_business_units
UNION ALL
SELECT 'applicants with bu', COUNT(*) FROM applicants WHERE business_unit_id IS NOT NULL;

-- Check columns were added
SHOW COLUMNS FROM applicants LIKE 'business_unit_id';
SHOW COLUMNS FROM applicant_documents LIKE 'business_unit_id';
SHOW COLUMNS FROM blacklisted_applicants LIKE 'business_unit_id';
SHOW COLUMNS FROM client_bookings LIKE 'business_unit_id';
*/
