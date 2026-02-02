-- csnk.sql (FULL, UPDATED, READY-TO-IMPORT)
-- phpMyAdmin SQL Dump (fixed / clean)
-- Host: 127.0.0.1
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.x
-- Notes:
--  • Adds `specialization_skills` (JSON) to applicants
--  • Keeps alt_phone_number, languages, education_level, employment_type, years_experience
--  • Uses ENUM for applicant_documents.document_type (strict, matches your app)
--  • All FKs use INT UNSIGNED and are consistent
--  • Includes your existing seed data

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
/*!40101 SET NAMES utf8mb4 */;

-- ======================================================
-- Drop existing (to avoid conflicts on import)
-- ======================================================
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `applicant_documents`;
DROP TABLE IF EXISTS `applicants`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `admin_users`;
SET FOREIGN_KEY_CHECKS=1;

-- ======================================================
-- Table: admin_users
-- ======================================================
CREATE TABLE `admin_users` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('super_admin','admin','employee') DEFAULT 'employee',
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `avatar`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'csnk001', 'admin@csnk.com', '$2y$10$UMSfwBdWu90vEe5hte4GwuV9Lz0SpimOTD34OA9eRN9jqgDhQqw1W', 'System Administrator', 'avatars/6979a2a03d893_1769579168.jpg', 'super_admin', 'active', '2026-01-28 05:25:51', '2026-01-28 05:46:08'),
(2, 'csnk002', 'renzdiaz.contact@gmail.com', '$2y$10$RNZ33JEaaTDThQ.q7PqGrOM40LkCOMuy0RFQaLUvBMEiYknT9aUK.', 'Renz Roann B. Diaz', NULL, 'super_admin', 'active', '2026-01-28 07:51:17', '2026-01-28 07:51:17');

-- ======================================================
-- Table: activity_logs
-- ======================================================
CREATE TABLE `activity_logs` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT(10) UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_logs_admin_id` (`admin_id`),
  CONSTRAINT `fk_activity_logs_admin`
    FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'Login', 'User logged in successfully', '::1', '2026-01-28 05:38:25');

-- ======================================================
-- Table: applicants
-- ======================================================
CREATE TABLE `applicants` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,

  -- Personal Info
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `suffix` VARCHAR(20) DEFAULT NULL,

  -- Contact Numbers
  `phone_number` VARCHAR(20) NOT NULL,
  `alt_phone_number` VARCHAR(20) DEFAULT NULL,

  `email` VARCHAR(100) DEFAULT NULL,
  `date_of_birth` DATE NOT NULL,
  `address` TEXT NOT NULL,

  -- Structured JSON Data (MariaDB 10.4 JSON-valid)
  `educational_attainment` LONGTEXT
      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
      DEFAULT NULL CHECK (JSON_VALID(`educational_attainment`)),

  `work_history` LONGTEXT
      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
      DEFAULT NULL CHECK (JSON_VALID(`work_history`)),

  `preferred_location` LONGTEXT
      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
      DEFAULT NULL CHECK (JSON_VALID(`preferred_location`)),

  `languages` LONGTEXT
      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
      DEFAULT NULL CHECK (JSON_VALID(`languages`)),

  -- NEW: Specialization Skills (chosen list from UI)
  `specialization_skills` LONGTEXT
      CHARACTER SET utf8mb4 COLLATE utf8mb4_bin
      DEFAULT NULL CHECK (JSON_VALID(`specialization_skills`)),

  -- Employment
  `employment_type` ENUM('Full Time','Part Time') DEFAULT NULL,

  `education_level` ENUM(
      'Elementary Graduate',
      'Secondary Level (Attended High School)',
      'Secondary Graduate (Junior High School / Old Curriculum)',
      'Senior High School Graduate (K-12 Curriculum)',
      'Technical-Vocational / TESDA Graduate',
      'Tertiary Level (College Undergraduate)',
      'Tertiary Graduate (Bachelor’s Degree)'
  ) DEFAULT NULL,

  `years_experience` INT(10) UNSIGNED NOT NULL DEFAULT 0,

  -- System
  `picture` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','on_process','approved','deleted') NOT NULL DEFAULT 'pending',
  `created_by` INT(10) UNSIGNED DEFAULT NULL,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,

  -- Keys
  PRIMARY KEY (`id`),
  KEY `idx_applicants_status` (`status`),
  KEY `idx_applicants_deleted_at` (`deleted_at`),
  KEY `idx_applicants_created_by` (`created_by`),
  KEY `idx_applicants_created_at` (`created_at`),

  CONSTRAINT `fk_applicants_created_by`
    FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`)
    ON DELETE SET NULL

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Seed sample applicants (specialization_skills omitted -> NULL)
INSERT INTO `applicants`
(`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone_number`, `alt_phone_number`, `email`,
 `date_of_birth`, `address`, `educational_attainment`, `work_history`, `preferred_location`, `languages`,
 `specialization_skills`, `picture`, `status`, `employment_type`, `education_level`, `years_experience`,
 `created_by`, `created_at`, `updated_at`, `deleted_at`)
VALUES
(2, 'Ryzza Mae', 'Borat', 'Dizon', '', '09123861273', NULL, 'awda@gmail.com', '2003-12-12',
 'Cubao Ibabaw 23 1012 Biringan City',
 '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2022\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}',
 '[{\"company\":\"Luxurias Bar\",\"years\":\"2\",\"role\":\"Kumekendeng\",\"location\":\"\"},{\"company\":\"Crempco\",\"years\":\"2\",\"role\":\"IT Programmer\",\"location\":\"Manila\"}]',
 '[\"Biringan City\",\"Capiz City\"]',
 '[]',
 NULL,
 'applicants/697c09eada954_1769736682.jpeg',
 'pending', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 1,
 '2026-01-30 01:31:22', '2026-01-30 05:22:09', NULL),

(3, 'awdaw', 'awd', 'awd', '', '09283718231', NULL, 'renzfour19@gmail.com', '2003-07-19',
 '17 Cubao Ibabaw St. Pandacan, Manila 1011 Sixth District',
 '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"\"},\"senior_high\":{\"school\":\"ACLC Northbay Branch\",\"strand\":\"ICT\",\"year\":\"2020 - 2022\"},\"college\":{\"school\":\"Universdad De Manila\",\"course\":\"BSIT\",\"year\":\"2022 - 2026\"}}',
 '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]',
 '[\"Manila City\",\"Makati City\",\"Marikina City\"]',
 '[\"English\",\"Filipino\"]',
 NULL,
 'applicants/697c29bc85e08_1769744828.jpeg',
 'pending', 'Full Time', 'Tertiary Level (College Undergraduate)', 2, 1,
 '2026-01-30 03:47:08', '2026-01-30 05:23:02', NULL);

-- Adjust AUTO_INCREMENT to next id
ALTER TABLE `applicants` AUTO_INCREMENT = 4;

-- ======================================================
-- Table: applicant_documents
-- ======================================================
CREATE TABLE `applicant_documents` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `applicant_id` INT(10) UNSIGNED NOT NULL,
  `document_type` ENUM(
    'brgy_clearance',
    'birth_certificate',
    'sss',
    'pagibig',
    'nbi',
    'police_clearance',
    'tin_id',
    'passport'
  ) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `uploaded_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_applicant_documents_applicant_id` (`applicant_id`),
  CONSTRAINT `fk_applicant_documents_applicant`
    FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `applicant_documents` (`id`, `applicant_id`, `document_type`, `file_path`, `uploaded_at`) VALUES
(1, 2, 'brgy_clearance', 'documents/697c09eae0732_1769736682.jpg', '2026-01-30 01:31:22'),
(2, 2, 'birth_certificate', 'documents/697c09eae2091_1769736682.jpg', '2026-01-30 01:31:22'),
(3, 2, 'sss', 'documents/697c09eae39a5_1769736682.jpg', '2026-01-30 01:31:22'),
(4, 2, 'pagibig', 'documents/697c09eae53d5_1769736682.jpg', '2026-01-30 01:31:22'),
(5, 2, 'nbi', 'documents/697c09eae6b20_1769736682.jpg', '2026-01-30 01:31:22'),
(6, 2, 'police_clearance', 'documents/697c09eae834f_1769736682.jpg', '2026-01-30 01:31:22'),
(7, 2, 'tin_id', 'documents/697c09eae9ee7_1769736682.jpg', '2026-01-30 01:31:22'),
(8, 2, 'passport', 'documents/697c09eaeb679_1769736682.jpg', '2026-01-30 01:31:22'),
(9, 3, 'brgy_clearance', 'documents/697c29bc8ad7c_1769744828.jpg', '2026-01-30 03:47:08'),
(10, 3, 'birth_certificate', 'documents/697c29bc8c585_1769744828.jpg', '2026-01-30 03:47:08'),
(11, 3, 'sss', 'documents/697c29bc8d5f7_1769744828.jpg', '2026-01-30 03:47:08'),
(12, 3, 'pagibig', 'documents/697c29bc8e4de_1769744828.jpg', '2026-01-30 03:47:08'),
(13, 3, 'nbi', 'documents/697c29bc8f88a_1769744828.jpg', '2026-01-30 03:47:08'),
(14, 3, 'police_clearance', 'documents/697c29bc9087a_1769744828.jpg', '2026-01-30 03:47:08'),
(15, 3, 'tin_id', 'documents/697c29bc91aeb_1769744828.jpg', '2026-01-30 03:47:08'),
(16, 3, 'passport', 'documents/697c29bc92fc5_1769744828.jpg', '2026-01-30 03:47:08');

-- Adjust AUTO_INCREMENT to next id
ALTER TABLE `applicant_documents` AUTO_INCREMENT = 17;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
 /*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
 /*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;