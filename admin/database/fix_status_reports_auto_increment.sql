-- ==========================================
-- FIX: applicant_status_reports AUTO_INCREMENT
-- ==========================================
-- Run this in phpMyAdmin to fix the "Duplicate entry '0'" error

-- Step 1: Fix the column to have AUTO_INCREMENT
ALTER TABLE `applicant_status_reports` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- Step 2: Reset AUTO_INCREMENT to max ID + 1 (to avoid conflicts)
ALTER TABLE `applicant_status_reports` AUTO_INCREMENT = 1;

-- Verify the fix
SHOW CREATE TABLE `applicant_status_reports`;

