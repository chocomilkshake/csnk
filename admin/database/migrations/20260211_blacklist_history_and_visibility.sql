-- Migration: add blacklist history + visibility flags
-- Purpose:
--  - Keep blacklist history even after "revert" (do not DELETE records)
--  - Allow hiding active-blacklisted applicants from all other lists (admin + client)

ALTER TABLE `blacklisted_applicants`
  ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `created_by`,
  ADD COLUMN `reverted_at` DATETIME NULL DEFAULT NULL AFTER `created_at`,
  ADD COLUMN `reverted_by` INT UNSIGNED NULL DEFAULT NULL AFTER `reverted_at`,
  ADD COLUMN `compliance_note` TEXT NULL DEFAULT NULL AFTER `reverted_by`,
  ADD COLUMN `compliance_proof_paths` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL
    CHECK (json_valid(`compliance_proof_paths`)) AFTER `compliance_note`;

CREATE INDEX `idx_blacklist_is_active` ON `blacklisted_applicants` (`is_active`);
CREATE INDEX `idx_blacklist_reverted_at` ON `blacklisted_applicants` (`reverted_at`);

-- Optional FK for reverted_by (safe if admin_users exists)
ALTER TABLE `blacklisted_applicants`
  ADD CONSTRAINT `fk_blacklist_reverted_by`
  FOREIGN KEY (`reverted_by`) REFERENCES `admin_users` (`id`)
  ON DELETE SET NULL ON UPDATE CASCADE;

