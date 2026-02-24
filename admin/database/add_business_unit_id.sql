-- Add business_unit_id column to admin_users table
-- This is needed for multi-business-unit support

ALTER TABLE `admin_users` 
ADD COLUMN `business_unit_id` INT UNSIGNED DEFAULT NULL AFTER `agency`;

-- Add foreign key constraint (optional, only if business_units table exists)
-- ALTER TABLE `admin_users` 
-- ADD CONSTRAINT `fk_admin_users_bu` 
-- FOREIGN KEY (`business_unit_id`) REFERENCES `business_units`(`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Update existing super_admin and admin users to have no specific BU (null means global access)
-- UPDATE `admin_users` SET `business_unit_id` = NULL WHERE `role` IN ('super_admin', 'admin');

-- Note: You may need to create the business_units table first if it doesn't exist
-- Here's a basic structure if needed:

-- CREATE TABLE IF NOT EXISTS `business_units` (
--   `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
--   `code` VARCHAR(50) NOT NULL,
--   `name` VARCHAR(100) NOT NULL,
--   `active` TINYINT(1) NOT NULL DEFAULT 1,
--   `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
--   PRIMARY KEY (`id`),
--   UNIQUE KEY `uniq_bu_code` (`code`)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERT INTO `business_units` (`code`, `name`, `active`) VALUES 
-- ('CSNK-PH', 'CSNK Philippines', 1),
-- ('SMC-TR', 'SMC Turkey', 1),
-- ('SMC-BH', 'SMC Bahrain', 1);
