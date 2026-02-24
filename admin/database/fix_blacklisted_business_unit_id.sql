-- Add missing business_unit_id column to blacklisted_applicants table
-- This fixes the error: Unknown column 'business_unit_id' in 'where clause'

ALTER TABLE `blacklisted_applicants` 
ADD COLUMN `business_unit_id` INT(10) UNSIGNED NOT NULL AFTER `applicant_id`,
ADD CONSTRAINT `fk_blacklist_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- Update existing blacklisted records with business_unit_id from applicants table
UPDATE blacklisted_applicants ba
JOIN applicants a ON ba.applicant_id = a.id
SET ba.business_unit_id = a.business_unit_id
WHERE ba.business_unit_id = 0 OR ba.business_unit_id IS NULL;

-- Add index for faster queries
ALTER TABLE `blacklisted_applicants` ADD INDEX `idx_blacklist_bu` (`business_unit_id`);
