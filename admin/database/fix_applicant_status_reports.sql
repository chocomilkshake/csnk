-- Fix for applicant_status_reports table
-- The issue is that the 'id' column doesn't have AUTO_INCREMENT
-- Run this SQL in phpMyAdmin to fix the table

-- First, check current AUTO_INCREMENT value
SHOW TABLE STATUS LIKE 'applicant_status_reports';

-- If AUTO_INCREMENT is not set, add it
ALTER TABLE `applicant_status_reports` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- Also reset the auto_increment if there are issues with duplicate IDs
-- Get the max ID first
-- SELECT MAX(id) FROM applicant_status_reports;

-- Then set AUTO_INCREMENT to max+1
-- ALTER TABLE `applicant_status_reports` AUTO_INCREMENT = [max_id + 1];

-- Verify the fix
SHOW CREATE TABLE `applicant_status_reports`;

