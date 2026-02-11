-- Migration: create blacklisted_applicants table

CREATE TABLE IF NOT EXISTS `blacklisted_applicants` (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `issue` text DEFAULT NULL,
  `proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`proof_paths`)),
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_blacklist_applicant_id` (`applicant_id`),
  KEY `idx_blacklist_created_by` (`created_by`),
  KEY `idx_blacklist_created_at` (`created_at`),
  CONSTRAINT `fk_blacklist_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_blacklist_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

