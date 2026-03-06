-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Mar 06, 2026 at 01:15 PM
-- Server version: 8.0.45-cll-lve
-- PHP Version: 8.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `xwhylhzb_csnk`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int UNSIGNED NOT NULL,
  `admin_id` int UNSIGNED DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 4, 'Login', 'User logged in successfully', '136.158.56.173', '2026-02-26 12:44:17'),
(2, 4, 'Update Profile', 'Updated profile information', '136.158.56.173', '2026-02-26 12:47:15'),
(3, 4, 'Update Profile', 'Updated profile information', '136.158.56.173', '2026-02-26 12:47:43'),
(4, 4, 'Logout', 'User logged out', '136.158.56.173', '2026-02-26 12:47:48'),
(5, 5, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-02 00:42:28'),
(6, 5, 'Logout', 'User logged out', '112.209.73.113', '2026-03-02 00:43:18'),
(7, 18, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-02 07:06:04'),
(8, 18, 'Login', 'User logged in successfully', '136.158.56.173', '2026-03-02 15:01:36'),
(9, 18, 'Logout', 'User logged out', '136.158.56.173', '2026-03-02 15:01:46'),
(10, 17, 'Login', 'User logged in successfully', '136.158.56.173', '2026-03-02 15:01:55'),
(11, 17, 'Logout', 'User logged out', '136.158.56.173', '2026-03-02 15:03:18'),
(12, 18, 'Login', 'User logged in successfully', '136.158.56.173', '2026-03-02 15:03:25'),
(13, 18, 'Add Applicant', 'Added new applicant: Renz Roann Diaz', '136.158.56.173', '2026-03-02 15:11:30'),
(14, 18, 'Update Applicant', 'Updated applicant Renz Roann Batuigas Diaz (ID: 44)', '136.158.56.173', '2026-03-02 15:12:46'),
(15, 18, 'Logout', 'User logged out', '136.158.56.173', '2026-03-02 15:13:54'),
(16, 17, 'Login', 'User logged in successfully', '136.158.56.173', '2026-03-02 15:14:01'),
(17, 17, 'Logout', 'User logged out', '136.158.56.173', '2026-03-02 15:15:03'),
(18, 18, 'Login', 'User logged in successfully', '136.158.56.173', '2026-03-02 15:15:10'),
(19, 18, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-05 00:08:01'),
(20, 5, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-05 00:11:57'),
(21, 5, 'Add Applicant', 'Added new applicant: REMY ENGLAN', '112.209.73.113', '2026-03-05 00:24:10'),
(22, 5, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-05 02:55:41'),
(23, 5, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-05 05:17:36'),
(24, 5, 'Add Applicant', 'Added new applicant: ROSITA GALVEZ', '112.209.73.113', '2026-03-05 05:26:04'),
(25, 5, 'Update Applicant', 'Updated applicant ROSITA CABARLES GALVEZ (ID: 46)', '112.209.73.113', '2026-03-05 05:27:02'),
(26, 5, 'Update Applicant', 'Updated applicant ROSITA CABARLES GALVEZ (ID: 46)', '112.209.73.113', '2026-03-05 05:27:32'),
(27, 5, 'Add Applicant', 'Added new applicant: JINKY TAMAYO', '112.209.73.113', '2026-03-05 05:33:00'),
(28, 5, 'Login', 'User logged in successfully', '112.209.73.113', '2026-03-05 07:46:34'),
(29, 5, 'Add Applicant', 'Added new applicant: TARA ANN MILLARE', '112.209.73.113', '2026-03-05 07:52:18'),
(30, 5, 'Update Applicant', 'Updated applicant Renz Roann Batuigas Diaz (ID: 44)', '112.209.73.113', '2026-03-05 08:07:06');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` enum('super_admin','admin','employee') COLLATE utf8mb4_general_ci DEFAULT 'employee',
  `agency` enum('csnk','smc') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `business_unit_id` int UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `avatar`, `role`, `agency`, `business_unit_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 'renzadmin', 'renzdiaz.contact@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$US9RRDloSHR3MGlzeUdGdw$cjNozvyDewv1phUaRVyn/6zcDKOdoSGJp1fBt5MABFE', 'Renz Diaz', 'avatars/69a040ef7811b_1772110063.jpg', 'super_admin', NULL, NULL, 'active', '2026-02-07 10:20:55', '2026-02-26 12:47:43'),
(5, 'elliadmin', 'elli@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cFZvOHZCckJkcDd4a0Y1cA$d+28H23RKZagXG81OSdY8xWa8x2KNSFuHip8xsxI2No', 'John Ellijah', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-07 10:21:28', '2026-02-10 11:15:33'),
(6, 'andreiadmin', 'andrei@gmail.com', '$2y$10$ROQGHUJso58ON6NCsv2PRO14x3Nviq3fZrkEU8KLne6BTEbVuhSq2', 'Andrei Javillo', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-07 10:22:05', '2026-02-07 10:22:05'),
(7, 'ralphadmin', 'ralph@gmail.com', '$2y$10$MUi6.7QJykPG48jx9e8lLu2V72JRHYu91.aRd5LFviHcJokQfvaf2', 'Ralph Justine Gallentes', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-10 00:32:00', '2026-02-10 00:32:00'),
(8, 'cabritoadmin', 'cabs@gmail.com', '$2y$10$AbWEDXv5fqBAkhk1quS.7.eJKD2uyUyenhinmN906bbJlePsxOlSq', 'John Adrian Cabrito', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-10 00:32:53', '2026-02-10 00:32:53'),
(12, 'jmpogi', 'jm@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QkMvd1FUc2Q0bnBjWHB0Uw$kltUwYy7N9gm+yGcuxlWqQFXnwD/EPRKRexQ1sDBYQM', 'John Michael Masmela', 'avatars/699c53d80ff2a_1771852760.png', 'admin', NULL, NULL, 'active', '2026-02-12 02:33:42', '2026-02-23 13:19:20'),
(17, 'smc001', 'smc001@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$S3BRVFB6YjIxcC5nUDI0Tw$BVJWYVKCIl952PAdRuNwYf4ovRdfKmpAOptWrOu6kvU', 'smc001', NULL, 'employee', 'smc', NULL, 'active', '2026-02-25 01:05:52', '2026-02-25 01:05:52'),
(18, 'csnk001', 'csnk001@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cTRHa1lhLkJvQjYwWlFpYg$7QXiD7qZtT316LFZErAQBtqSLkiorr/BSe2rZGecD58', 'csnk001', NULL, 'employee', 'csnk', NULL, 'active', '2026-02-25 01:06:21', '2026-02-25 01:06:21');

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_business_units`
--

CREATE TABLE `admin_user_business_units` (
  `admin_user_id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agencies`
--

CREATE TABLE `agencies` (
  `id` smallint UNSIGNED NOT NULL,
  `code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agencies`
--

INSERT INTO `agencies` (`id`, `code`, `name`, `active`) VALUES
(1, 'csnk', 'CSNK', 1),
(2, 'smc', 'SMC', 1);

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `suffix` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `alt_phone_number` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `address` text COLLATE utf8mb4_general_ci NOT NULL,
  `educational_attainment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `work_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `preferred_location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `specialization_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `employment_type` enum('Full Time','Part Time') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `education_level` enum('Elementary Graduate','Secondary Level (Attended High School)','Secondary Graduate (Junior High School / Old Curriculum)','Senior High School Graduate (K-12 Curriculum)','Technical-Vocational / TESDA Graduate','Tertiary Level (College Undergraduate)','Tertiary Graduate (Bachelor’s Degree)') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `years_experience` int UNSIGNED NOT NULL DEFAULT '0',
  `picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_url` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_provider` enum('youtube','vimeo','file','other') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_type` enum('iframe','file') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'iframe',
  `video_title` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_thumbnail_url` varchar(1024) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `video_duration_seconds` int UNSIGNED DEFAULT NULL,
  `status` enum('pending','on_process','approved','on_hold','deleted') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `business_unit_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone_number`, `alt_phone_number`, `email`, `date_of_birth`, `address`, `educational_attainment`, `work_history`, `preferred_location`, `languages`, `specialization_skills`, `employment_type`, `daily_rate`, `education_level`, `years_experience`, `picture`, `video_url`, `video_provider`, `video_type`, `video_title`, `video_thumbnail_url`, `video_duration_seconds`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(44, 1, 'Renz Roann', 'Batuigas', 'Diaz', '', '09187238712', '09817287381', 'renzdiaz@gmail.com', '2003-12-17', '2461 Princess Floresca St. Pandacan, Manila', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati City\"}]', '[\"Makati\",\"Malate\"]', '[]', '[\"Cooking & Food Service\",\"Pet & Outdoor Maintenance\"]', 'Full Time', 900.00, 'Secondary Level (Attended High School)', 4, 'applicants/69a939aa5fdbf_1772698026.jpg', NULL, NULL, 'iframe', NULL, NULL, NULL, 'on_process', 18, '2026-03-02 15:11:30', '2026-03-05 08:07:06', NULL),
(45, 1, 'REMY', 'MANZANO', 'ENGLAN', '', '', '', '', '1981-04-05', 'BINALONAN PGN', '{\"elementary\":{\"school\":\"\",\"year\":\"\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SMC Manpower Agency Co.\",\"years\":\"6\",\"role\":\"Domestic Worker\",\"location\":\"Singapore\"}]', '[\"Manila\"]', '[\"English\",\"Arabic\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Part Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 6, 'applicants/69a8cd2a875fb_1772670250.png', 'video/69a8cd2a8a2a5_1772670250.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-03-05 00:24:10', '2026-03-05 00:24:10', NULL),
(46, 1, 'ROSITA', 'CABARLES', 'GALVEZ', '', '09999999999', '', '', '1995-01-01', 'Manila', '{\"elementary\":{\"school\":\"\",\"year\":\"\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SMC Manpower Agency Co.\",\"years\":\"2\",\"role\":\"Domestic Worker\",\"location\":\"United Arab Emirates\"}]', '[\"Manila\"]', '[\"Filipino\",\"English\"]', '[\"Cleaning & Housekeeping (General)\",\"Laundry & Clothing Care\",\"Cooking & Food Service\",\"Childcare & Maternity (Yaya)\"]', 'Part Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/69a913ebd49bc_1772688363.jpg', 'video/69a913ebdfdf3_1772688363.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-03-05 05:26:03', '2026-03-05 05:27:32', NULL),
(47, 1, 'JINKY', 'ASENCIO', 'TAMAYO', '', '', '', '', '1989-11-22', 'OTON ILOILO', '{\"elementary\":{\"school\":\"\",\"year\":\"\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SMC Manpower Agency Co.\",\"years\":\"1\",\"role\":\"Domestic Worker\",\"location\":\"Kuwait\"}]', '[\"Manila\"]', '[\"Filipino\",\"English\",\"Arabic\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Part Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 1, 'applicants/69a9158c24acd_1772688780.png', 'video/69a9158c2777d_1772688780.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-03-05 05:33:00', '2026-03-05 05:33:00', NULL),
(48, 1, 'TARA ANN', 'REQUERQUE', 'MILLARE', '', '', '', '', '1990-02-08', 'Manila', '{\"elementary\":{\"school\":\"\",\"year\":\"\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SMC Manpower Agency Co.\",\"years\":\"1\",\"role\":\"Domestic Worker\",\"location\":\"Saudi\"}]', '[\"Manila\"]', '[\"Filipino\",\"English\",\"Arabic\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Part Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 1, 'applicants/69a936327a1ca_1772697138.png', 'video/69a936327f351_1772697138.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-03-05 07:52:18', '2026-03-05 07:52:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `id` int UNSIGNED NOT NULL,
  `applicant_id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL,
  `document_type_id` int UNSIGNED DEFAULT NULL,
  `document_type` enum('brgy_clearance','birth_certificate','sss','pagibig','nbi','police_clearance','tin_id','passport') COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_replacements`
--

CREATE TABLE `applicant_replacements` (
  `id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL,
  `original_applicant_id` int UNSIGNED NOT NULL,
  `replacement_applicant_id` int UNSIGNED DEFAULT NULL,
  `client_booking_id` int UNSIGNED DEFAULT NULL,
  `reason` enum('AWOL','Client Left','Not Finished Contract','Performance Issue','Other') COLLATE utf8mb4_general_ci NOT NULL,
  `report_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `attachments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('selection','assigned','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'selection',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `assigned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_reports`
--

CREATE TABLE `applicant_reports` (
  `id` int UNSIGNED NOT NULL,
  `applicant_id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL,
  `admin_id` int UNSIGNED DEFAULT NULL,
  `note_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_status_reports`
--

CREATE TABLE `applicant_status_reports` (
  `id` int UNSIGNED NOT NULL,
  `applicant_id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL,
  `from_status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `to_status` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `report_text` text COLLATE utf8mb4_general_ci NOT NULL,
  `admin_id` int UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blacklisted_applicants`
--

CREATE TABLE `blacklisted_applicants` (
  `id` int UNSIGNED NOT NULL,
  `applicant_id` int UNSIGNED NOT NULL,
  `reason` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `issue` text COLLATE utf8mb4_general_ci,
  `proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_by` int UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reverted_at` datetime DEFAULT NULL,
  `reverted_by` int UNSIGNED DEFAULT NULL,
  `compliance_note` text COLLATE utf8mb4_general_ci,
  `compliance_proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_units`
--

CREATE TABLE `business_units` (
  `id` int UNSIGNED NOT NULL,
  `agency_id` smallint UNSIGNED NOT NULL,
  `country_id` smallint UNSIGNED NOT NULL,
  `code` varchar(40) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_units`
--

INSERT INTO `business_units` (`id`, `agency_id`, `country_id`, `code`, `name`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'CSNK-PH', 'CSNK Philippines', 1, '2026-02-24 01:19:27', '2026-02-24 01:19:27'),
(2, 2, 2, 'SMC-TR', 'SMC Turkey', 1, '2026-02-24 01:19:27', '2026-02-24 01:19:27'),
(3, 2, 3, 'SMC-BH', 'SMC Bahrain', 1, '2026-02-25 12:47:51', '2026-02-25 12:47:51');

-- --------------------------------------------------------

--
-- Table structure for table `client_bookings`
--

CREATE TABLE `client_bookings` (
  `id` int UNSIGNED NOT NULL,
  `applicant_id` int UNSIGNED NOT NULL,
  `business_unit_id` int UNSIGNED NOT NULL,
  `services_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `appointment_type` enum('Video Call','Audio Call','Chat','Office Visit') COLLATE utf8mb4_general_ci NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `client_first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `client_middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `client_last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `client_phone` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `client_email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `client_address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('submitted','confirmed','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_bookings`
--

INSERT INTO `client_bookings` (`id`, `applicant_id`, `business_unit_id`, `services_json`, `appointment_type`, `appointment_date`, `appointment_time`, `client_first_name`, `client_middle_name`, `client_last_name`, `client_phone`, `client_email`, `client_address`, `status`, `created_at`, `updated_at`) VALUES
(13, 44, 1, '[\"Cleaning & Housekeeping (General)\"]', 'Office Visit', '2026-03-17', '09:29:00', 'John Ellijah', 'Mulawin', 'Renz Diaz', '09270746258', 'roannrenz19@gmail.com', '666 Paco Hellfire St. Paco Manila', 'submitted', '2026-03-05 01:29:45', '2026-03-05 01:29:45');

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` smallint UNSIGNED NOT NULL,
  `iso2` char(2) COLLATE utf8mb4_general_ci NOT NULL,
  `iso3` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `default_tz` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `phone_country_code` varchar(6) COLLATE utf8mb4_general_ci NOT NULL,
  `currency_code` char(3) COLLATE utf8mb4_general_ci NOT NULL,
  `locale` varchar(10) COLLATE utf8mb4_general_ci NOT NULL,
  `date_format` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `iso2`, `iso3`, `name`, `default_tz`, `phone_country_code`, `currency_code`, `locale`, `date_format`, `active`) VALUES
(1, 'PH', 'PHL', 'Philippines', 'Asia/Manila', '+63', 'PHP', 'en-PH', 'MM/DD/YYYY', 1),
(2, 'TR', 'TUR', 'Turkey', 'Europe/Istanbul', '+90', 'TRY', 'tr-TR', 'DD.MM.YYYY', 1),
(3, 'BH', 'BHR', 'Bahrain', 'Asia/Bahrain', '+973', 'BHD', 'en-BH', 'DD/MM/YYYY', 1);

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int UNSIGNED NOT NULL,
  `country_id` smallint UNSIGNED NOT NULL,
  `code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `label` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `document_types`
--

INSERT INTO `document_types` (`id`, `country_id`, `code`, `label`, `is_required`, `active`) VALUES
(1, 1, 'brgy_clearance', 'Barangay Clearance', 1, 1),
(2, 1, 'birth_certificate', 'Birth Certificate', 1, 1),
(3, 1, 'sss', 'SSS', 0, 1),
(4, 1, 'pagibig', 'PAG-IBIG', 0, 1),
(5, 1, 'nbi', 'NBI Clearance', 1, 1),
(6, 1, 'police_clearance', 'Police Clearance', 0, 1),
(7, 1, 'tin_id', 'TIN ID', 0, 1),
(8, 1, 'passport', 'Passport', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `session_logs`
--

CREATE TABLE `session_logs` (
  `id` int UNSIGNED NOT NULL,
  `admin_id` int UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `login_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_logs`
--

INSERT INTO `session_logs` (`id`, `admin_id`, `ip_address`, `user_agent`, `login_time`, `logout_time`) VALUES
(100, 4, '136.158.56.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 20:44:17', '2026-02-26 20:47:48'),
(101, 5, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 08:42:28', '2026-03-02 08:43:18'),
(102, 18, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.95 Safari/537.36', '2026-03-02 15:06:04', NULL),
(103, 18, '136.158.56.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:01:36', '2026-03-02 23:01:46'),
(104, 17, '136.158.56.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:01:55', '2026-03-02 23:03:18'),
(105, 18, '136.158.56.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:03:25', '2026-03-02 23:13:54'),
(106, 17, '136.158.56.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:14:01', '2026-03-02 23:15:03'),
(107, 18, '136.158.56.173', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 23:15:10', NULL),
(108, 18, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 08:08:01', NULL),
(109, 5, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 08:11:57', NULL),
(110, 5, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 10:55:41', NULL),
(111, 5, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 13:17:36', NULL),
(112, 5, '112.209.73.113', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-05 15:46:34', NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_activity_logs_admin_id` (`admin_id`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_admin_username` (`username`),
  ADD KEY `idx_admin_users_role` (`role`),
  ADD KEY `idx_admin_users_agency` (`agency`),
  ADD KEY `idx_admin_users_bu` (`business_unit_id`);

--
-- Indexes for table `admin_user_business_units`
--
ALTER TABLE `admin_user_business_units`
  ADD PRIMARY KEY (`admin_user_id`,`business_unit_id`),
  ADD KEY `fk_aubu_bu` (`business_unit_id`);

--
-- Indexes for table `agencies`
--
ALTER TABLE `agencies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_applicant_id_bu` (`id`,`business_unit_id`),
  ADD KEY `idx_applicants_status` (`status`),
  ADD KEY `idx_applicants_deleted_at` (`deleted_at`),
  ADD KEY `idx_applicants_created_by` (`created_by`),
  ADD KEY `idx_applicants_created_at` (`created_at`),
  ADD KEY `idx_applicants_bu` (`business_unit_id`),
  ADD KEY `idx_applicants_bu_status` (`business_unit_id`,`status`);

--
-- Indexes for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_applicant_documents_applicant_id` (`applicant_id`),
  ADD KEY `idx_app_docs_bu` (`business_unit_id`),
  ADD KEY `fk_app_docs_app_bu` (`applicant_id`,`business_unit_id`),
  ADD KEY `idx_app_docs_doc_type` (`document_type_id`);

--
-- Indexes for table `applicant_replacements`
--
ALTER TABLE `applicant_replacements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ar_original_applicant_id` (`original_applicant_id`),
  ADD KEY `idx_ar_replacement_applicant_id` (`replacement_applicant_id`),
  ADD KEY `idx_ar_client_booking_id` (`client_booking_id`),
  ADD KEY `idx_ar_status` (`status`),
  ADD KEY `fk_ar_created_by_admin` (`created_by`),
  ADD KEY `idx_ar_bu` (`business_unit_id`),
  ADD KEY `fk_ar_original_app_bu` (`original_applicant_id`,`business_unit_id`),
  ADD KEY `fk_ar_client_booking_bu` (`client_booking_id`,`business_unit_id`),
  ADD KEY `fk_ar_replacement_app_bu` (`replacement_applicant_id`,`business_unit_id`);

--
-- Indexes for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_reports_applicant` (`applicant_id`),
  ADD KEY `idx_app_reports_bu` (`business_unit_id`),
  ADD KEY `fk_app_reports_app_bu` (`applicant_id`,`business_unit_id`);

--
-- Indexes for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_asr_applicant_id` (`applicant_id`),
  ADD KEY `idx_asr_bu` (`business_unit_id`),
  ADD KEY `fk_asr_app_bu` (`applicant_id`,`business_unit_id`);

--
-- Indexes for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_blacklisted_applicants_applicant_id` (`applicant_id`),
  ADD KEY `idx_blacklisted_applicants_created_by` (`created_by`),
  ADD KEY `idx_blacklisted_applicants_created_at` (`created_at`),
  ADD KEY `idx_blacklist_is_active` (`is_active`),
  ADD KEY `idx_blacklist_reverted_at` (`reverted_at`),
  ADD KEY `fk_blacklist_reverted_by` (`reverted_by`);

--
-- Indexes for table `business_units`
--
ALTER TABLE `business_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD UNIQUE KEY `uq_bu_ag_country` (`agency_id`,`country_id`),
  ADD KEY `fk_bu_country` (`country_id`);

--
-- Indexes for table `client_bookings`
--
ALTER TABLE `client_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cb_id_bu` (`id`,`business_unit_id`),
  ADD KEY `idx_applicant` (`applicant_id`),
  ADD KEY `idx_client_bookings_created_at` (`created_at`),
  ADD KEY `idx_client_bookings_status` (`status`),
  ADD KEY `idx_client_bookings_app_created` (`applicant_id`,`created_at`),
  ADD KEY `idx_client_bookings_bu` (`business_unit_id`),
  ADD KEY `fk_cb_app_bu` (`applicant_id`,`business_unit_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `iso2` (`iso2`),
  ADD UNIQUE KEY `iso3` (`iso3`),
  ADD UNIQUE KEY `uq_countries_name` (`name`);

--
-- Indexes for table `document_types`
--
ALTER TABLE `document_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_doc_type_country_code` (`country_id`,`code`);

--
-- Indexes for table `session_logs`
--
ALTER TABLE `session_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session_logs_admin_id` (`admin_id`),
  ADD KEY `idx_session_logs_login_time` (`login_time`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `agencies`
--
ALTER TABLE `agencies`
  MODIFY `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_replacements`
--
ALTER TABLE `applicant_replacements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `business_units`
--
ALTER TABLE `business_units`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `client_bookings`
--
ALTER TABLE `client_bookings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` smallint UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `fk_admin_users_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `admin_user_business_units`
--
ALTER TABLE `admin_user_business_units`
  ADD CONSTRAINT `fk_aubu_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_aubu_user` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `fk_applicants_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_applicants_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD CONSTRAINT `fk_app_docs_app_bu` FOREIGN KEY (`applicant_id`,`business_unit_id`) REFERENCES `applicants` (`id`, `business_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_docs_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`),
  ADD CONSTRAINT `fk_app_docs_doc_type` FOREIGN KEY (`document_type_id`) REFERENCES `document_types` (`id`),
  ADD CONSTRAINT `fk_applicant_documents_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applicant_replacements`
--
ALTER TABLE `applicant_replacements`
  ADD CONSTRAINT `fk_ar_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`),
  ADD CONSTRAINT `fk_ar_client_booking` FOREIGN KEY (`client_booking_id`) REFERENCES `client_bookings` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_client_booking_bu` FOREIGN KEY (`client_booking_id`,`business_unit_id`) REFERENCES `client_bookings` (`id`, `business_unit_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_created_by_admin` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_original_app_bu` FOREIGN KEY (`original_applicant_id`,`business_unit_id`) REFERENCES `applicants` (`id`, `business_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_original_applicant` FOREIGN KEY (`original_applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_replacement_app_bu` FOREIGN KEY (`replacement_applicant_id`,`business_unit_id`) REFERENCES `applicants` (`id`, `business_unit_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ar_replacement_applicant` FOREIGN KEY (`replacement_applicant_id`) REFERENCES `applicants` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  ADD CONSTRAINT `fk_app_reports_app_bu` FOREIGN KEY (`applicant_id`,`business_unit_id`) REFERENCES `applicants` (`id`, `business_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_reports_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_app_reports_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`);

--
-- Constraints for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  ADD CONSTRAINT `fk_asr_app_bu` FOREIGN KEY (`applicant_id`,`business_unit_id`) REFERENCES `applicants` (`id`, `business_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asr_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_asr_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`);

--
-- Constraints for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  ADD CONSTRAINT `fk_blacklist_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blacklist_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blacklist_reverted_by` FOREIGN KEY (`reverted_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `business_units`
--
ALTER TABLE `business_units`
  ADD CONSTRAINT `fk_bu_agency` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`),
  ADD CONSTRAINT `fk_bu_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Constraints for table `client_bookings`
--
ALTER TABLE `client_bookings`
  ADD CONSTRAINT `fk_booking_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cb_app_bu` FOREIGN KEY (`applicant_id`,`business_unit_id`) REFERENCES `applicants` (`id`, `business_unit_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_cb_bu` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`);

--
-- Constraints for table `document_types`
--
ALTER TABLE `document_types`
  ADD CONSTRAINT `fk_doc_types_country` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`);

--
-- Constraints for table `session_logs`
--
ALTER TABLE `session_logs`
  ADD CONSTRAINT `fk_session_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
