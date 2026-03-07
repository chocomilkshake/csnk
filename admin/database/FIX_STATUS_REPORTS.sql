-- ==========================================
-- URGENT FIX: applicant_status_reports AUTO_INCREMENT
-- ==========================================
-- This fixes the "Duplicate entry '0' for key 'PRIMARY'" error
-- Run this SQL in phpMyAdmin

-- Fix the column to have AUTO_INCREMENT
ALTER TABLE `applicant_status_reports` MODIFY `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT;

-- Reset AUTO_INCREMENT to start from 1
ALTER TABLE `applicant_status_reports` AUTO_INCREMENT = 1;

-- Verify the fix
SHOW CREATE TABLE `applicant_status_reports`;

