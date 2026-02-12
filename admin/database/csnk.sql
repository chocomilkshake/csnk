-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 12, 2026 at 01:39 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `csnk`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(139, 4, 'Login', 'User logged in successfully', '::1', '2026-02-07 10:25:43'),
(140, 4, 'Login', 'User logged in successfully', '::1', '2026-02-07 14:28:49'),
(141, 4, 'Login', 'User logged in successfully', '::1', '2026-02-10 00:25:48'),
(142, 4, 'Create Account', 'Created super_admin ralphadmin', '::1', '2026-02-10 00:32:00'),
(143, 4, 'Create Account', 'Created super_admin cabritoadmin', '::1', '2026-02-10 00:32:53'),
(144, 4, 'Create Account', 'Created admin csnk001', '::1', '2026-02-10 00:34:01'),
(145, 4, 'Create Account', 'Created admin csnk002', '::1', '2026-02-10 00:34:48'),
(146, 4, 'Update Applicant', 'Updated applicant ID: 20', '::1', '2026-02-10 01:39:02'),
(147, 4, 'Update Applicant', 'Updated applicant ID: 19', '::1', '2026-02-10 01:59:06'),
(148, 4, 'Update Applicant', 'Updated applicant ID: 20', '::1', '2026-02-10 02:51:16'),
(149, 4, 'Update Applicant', 'Updated applicant ID: 19', '::1', '2026-02-10 02:51:24'),
(150, 4, 'Logout', 'User logged out', '::1', '2026-02-10 02:55:10'),
(151, 4, 'Login', 'User logged in successfully', '::1', '2026-02-10 02:55:26'),
(152, 4, 'Logout', 'User logged out', '::1', '2026-02-10 02:55:32'),
(153, 4, 'Login', 'User logged in successfully', '::1', '2026-02-10 02:55:44'),
(154, 4, 'Update Applicant Status', 'Applicant ID 22 → on_process', '::1', '2026-02-10 03:25:50'),
(155, 4, 'Update Applicant', 'Updated applicant ID: 22', '::1', '2026-02-10 03:26:01'),
(156, 4, 'Delete Applicant', 'Deleted applicant ID: 22', '::1', '2026-02-10 03:51:01'),
(169, 4, 'Update Applicant Status', 'Applicant ID 19 → on_process', '::1', '2026-02-10 06:26:00'),
(170, 4, 'Update Applicant Status', 'Applicant ID 20 → pending', '::1', '2026-02-10 06:26:11'),
(171, 4, 'Update Applicant Status', 'Applicant ID 20 → on_process', '::1', '2026-02-10 06:26:46'),
(172, 4, 'Update Applicant Status', 'Applicant ID 20 → approved', '::1', '2026-02-10 06:26:58'),
(173, 4, 'Update Applicant Status', 'Applicant ID 19 → approved', '::1', '2026-02-10 06:28:19'),
(174, 4, 'Update Applicant Status', 'Applicant ID 22 → approved', '::1', '2026-02-10 06:30:57'),
(175, 4, 'Update Applicant Status', 'Applicant ID 18 → approved', '::1', '2026-02-10 06:30:58'),
(176, 4, 'Update Applicant Status', 'Applicant ID 17 → approved', '::1', '2026-02-10 06:31:00'),
(177, 4, 'Update Applicant Status', 'Applicant ID 22 → on_process', '::1', '2026-02-10 06:31:19'),
(178, 4, 'Update Applicant Status', 'Applicant ID 17 → on_process', '::1', '2026-02-10 06:31:22'),
(179, 4, 'Update Applicant Status', 'Applicant ID 18 → on_process', '::1', '2026-02-10 06:31:24'),
(180, 4, 'Update Applicant Status', 'Applicant ID 22 → pending', '::1', '2026-02-10 06:31:33'),
(181, 4, 'Update Applicant Status', 'Applicant ID 18 → pending', '::1', '2026-02-10 06:31:35'),
(182, 4, 'Update Applicant Status', 'Applicant ID 17 → pending', '::1', '2026-02-10 06:31:37'),
(183, 4, 'Update Applicant Status', 'Applicant ID 20 → on_process', '::1', '2026-02-10 07:55:03'),
(184, 4, 'Update Applicant Status', 'Applicant ID 20 → approved', '::1', '2026-02-10 07:58:06'),
(185, 4, 'Update Applicant Status', 'Applicant ID 20 → pending', '::1', '2026-02-10 08:00:37'),
(186, 4, 'Login', 'User logged in successfully', '::1', '2026-02-10 08:27:53'),
(187, 4, 'Login', 'User logged in successfully', '::1', '2026-02-10 11:14:25'),
(188, 5, 'Login', 'User logged in successfully', '::1', '2026-02-10 11:15:33'),
(189, 5, 'Update Applicant Status', 'Applicant ID 19 → on_process', '::1', '2026-02-10 11:15:46'),
(190, 5, 'Create Account', 'Created employee employee001', '::1', '2026-02-10 11:17:21'),
(191, 5, 'Logout', 'User logged out', '::1', '2026-02-10 11:17:39'),
(192, 11, 'Login', 'User logged in successfully', '::1', '2026-02-10 11:17:47'),
(193, 11, 'Logout', 'User logged out', '::1', '2026-02-10 11:17:55'),
(194, 4, 'Update Applicant Status', 'Updated status for Jennifer Would You Refer → on_process', '::1', '2026-02-10 11:44:56'),
(195, 4, 'Update Applicant Status', 'Updated status for Jennifer Would You Refer → approved', '::1', '2026-02-10 11:48:43'),
(196, 4, 'Update Applicant Status', 'Updated status for Dixon hoyayo Myas → approved', '::1', '2026-02-10 11:48:46'),
(197, 4, 'Update Applicant Status', 'Updated status for Imee B Bangag → approved', '::1', '2026-02-10 11:48:49'),
(198, 5, 'Update Applicant Status', 'Updated status for Annie Are You Okay → on_process', '::1', '2026-02-11 00:50:14'),
(199, 5, 'Update Applicant Status', 'Updated status for Annie Are You Okay → pending', '::1', '2026-02-11 00:50:18'),
(200, 5, 'Update Applicant Status', 'Updated status for Annie Are You Okay → on_process', '::1', '2026-02-11 01:14:06'),
(201, 5, 'Update Applicant', 'Updated applicant Mhi Mha Central Mha (ID: 18)', '::1', '2026-02-11 02:20:59'),
(202, 5, 'Logout', 'User logged out', '::1', '2026-02-11 02:48:16'),
(203, 5, 'Login', 'User logged in successfully', '::1', '2026-02-11 02:48:46'),
(204, 5, 'Login', 'User logged in successfully', '::1', '2026-02-11 03:12:38'),
(205, 5, 'Update Applicant Status (with report)', 'Updated status for Annie Are You Okay → pending; Reason: trial', '::1', '2026-02-11 03:13:15'),
(206, 5, 'Update Applicant Status', 'Updated status for Annie Are You Okay → on_process', '::1', '2026-02-11 03:21:00'),
(207, 5, 'Update Applicant Status (with report)', 'Updated status for Annie Are You Okay → approved; Reason: moved', '::1', '2026-02-11 03:21:14'),
(208, 5, 'Update Applicant Status (with report)', 'Updated status for Mhi Mha Central Mha → pending; Reason: trial', '::1', '2026-02-11 03:59:41'),
(209, 5, 'Login', 'User logged in successfully', '::1', '2026-02-11 05:11:28'),
(210, 5, 'Update Applicant Status', 'Updated status for Annie Are You Okay → on_process', '::1', '2026-02-11 05:28:49'),
(211, 5, 'Update Applicant Status (with report)', 'Updated status for Annie Are You Okay → approved; Reason: whattt', '::1', '2026-02-11 05:28:59'),
(212, 5, 'Login', 'User logged in successfully', '::1', '2026-02-11 05:52:23'),
(213, 4, 'Update Applicant Status', 'Updated status for Annie Are You Okay → on_process', '::1', '2026-02-11 06:03:45'),
(214, 4, 'Update Applicant Status', 'Updated status for Jennifer Would You Refer → pending', '::1', '2026-02-11 06:03:50'),
(215, 4, 'Update Applicant Status (with report)', 'Updated status for Annie Are You Okay → pending; Reason: no clients', '::1', '2026-02-11 06:04:56'),
(216, 4, 'Update Applicant Status', 'Updated status for Jennifer Would You Refer → on_process', '::1', '2026-02-11 06:07:40'),
(217, 4, 'Update Applicant Status (with report)', 'Updated status for Jennifer Would You Refer → approved; Reason: apporved relocate to client', '::1', '2026-02-11 06:20:52'),
(218, 4, 'Blacklist Applicant', 'Blacklisted applicant Mhi Mha Central Mha (ID: 18) - Reason: awd', '::1', '2026-02-11 06:37:33'),
(219, 4, 'Logout', 'User logged out', '::1', '2026-02-11 06:42:53'),
(220, 11, 'Login', 'User logged in successfully', '::1', '2026-02-11 06:43:01'),
(221, 11, 'Logout', 'User logged out', '::1', '2026-02-11 06:43:20'),
(222, 9, 'Login', 'User logged in successfully', '::1', '2026-02-11 06:43:30'),
(223, 9, 'Logout', 'User logged out', '::1', '2026-02-11 06:48:47'),
(224, 4, 'Login', 'User logged in successfully', '::1', '2026-02-11 06:52:50'),
(225, 4, 'Revert Blacklist', 'Removed blacklist for applicant Mhi Mha Central Mha (ID: 18)', '::1', '2026-02-11 07:24:58'),
(226, 4, 'Logout', 'User logged out', '::1', '2026-02-11 07:28:02'),
(227, 9, 'Login', 'User logged in successfully', '::1', '2026-02-11 07:28:20'),
(228, 9, 'Blacklist Applicant', 'Blacklisted applicant Annie Are You Okay (ID: 22) - Reason: asd', '::1', '2026-02-11 07:33:22'),
(229, 4, 'Login', 'User logged in successfully', '::1', '2026-02-12 00:34:04'),
(230, 4, 'Add Applicant Report', 'Applicant ID 20: awd', '::1', '2026-02-12 00:38:25'),
(231, 4, 'Add Applicant Report', 'Applicant ID 20: awdawd', '::1', '2026-02-12 00:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `role` enum('super_admin','admin','employee') DEFAULT 'employee',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `avatar`, `role`, `status`, `created_at`, `updated_at`) VALUES
(4, 'renzadmin', 'renzdiaz.contact@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$US9RRDloSHR3MGlzeUdGdw$cjNozvyDewv1phUaRVyn/6zcDKOdoSGJp1fBt5MABFE', 'Renz Diaz', NULL, 'super_admin', 'active', '2026-02-07 10:20:55', '2026-02-10 08:27:53'),
(5, 'elliadmin', 'elli@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cFZvOHZCckJkcDd4a0Y1cA$d+28H23RKZagXG81OSdY8xWa8x2KNSFuHip8xsxI2No', 'John Ellijah', NULL, 'super_admin', 'active', '2026-02-07 10:21:28', '2026-02-10 11:15:33'),
(6, 'andreiadmin', 'andrei@gmail.com', '$2y$10$ROQGHUJso58ON6NCsv2PRO14x3Nviq3fZrkEU8KLne6BTEbVuhSq2', 'Andrei Javillo', NULL, 'super_admin', 'active', '2026-02-07 10:22:05', '2026-02-07 10:22:05'),
(7, 'ralphadmin', 'ralph@gmail.com', '$2y$10$MUi6.7QJykPG48jx9e8lLu2V72JRHYu91.aRd5LFviHcJokQfvaf2', 'Ralph Justine Gallentes', NULL, 'super_admin', 'active', '2026-02-10 00:32:00', '2026-02-10 00:32:00'),
(8, 'cabritoadmin', 'cabs@gmail.com', '$2y$10$AbWEDXv5fqBAkhk1quS.7.eJKD2uyUyenhinmN906bbJlePsxOlSq', 'John Adrian Cabrito', NULL, 'super_admin', 'active', '2026-02-10 00:32:53', '2026-02-10 00:32:53'),
(9, 'csnk001', 'csnk@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$aVBWMGZwbTV2SUllSnhlVA$MftWb38bySoYhAFpnkQxDSfdwUp80yUKvIB4mY8zVWI', 'admin001', NULL, 'admin', 'active', '2026-02-10 00:34:01', '2026-02-11 06:43:30'),
(10, 'csnk002', 'admin@gmail.com', '$2y$10$TcPdwmJVbpCvl1ekZ8n73eCzlxZvM1ROPaXPurFhJq3F/WV0yDgTW', 'admin002', NULL, 'admin', 'active', '2026-02-10 00:34:48', '2026-02-10 00:34:48'),
(11, 'employee001', 'emp@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$a1g3M0M5d3hXbDAxQnVKRg$Pn8dOinMMFzjLbiJacQU2rx5+hyZ7Vq/FzU4hyeBGJI', 'emp1', NULL, 'employee', 'active', '2026-02-10 11:17:21', '2026-02-10 11:17:47');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(10) UNSIGNED NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `alt_phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `address` text NOT NULL,
  `educational_attainment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`educational_attainment`)),
  `work_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`work_history`)),
  `preferred_location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preferred_location`)),
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`languages`)),
  `specialization_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`specialization_skills`)),
  `employment_type` enum('Full Time','Part Time') DEFAULT NULL,
  `education_level` enum('Elementary Graduate','Secondary Level (Attended High School)','Secondary Graduate (Junior High School / Old Curriculum)','Senior High School Graduate (K-12 Curriculum)','Technical-Vocational / TESDA Graduate','Tertiary Level (College Undergraduate)','Tertiary Graduate (Bachelor’s Degree)') DEFAULT NULL,
  `years_experience` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `picture` varchar(255) DEFAULT NULL,
  `video_url` varchar(1024) DEFAULT NULL,
  `video_provider` enum('youtube','vimeo','file','other') DEFAULT NULL,
  `video_type` enum('iframe','file') NOT NULL DEFAULT 'iframe',
  `video_title` varchar(200) DEFAULT NULL,
  `video_thumbnail_url` varchar(1024) DEFAULT NULL,
  `video_duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','on_process','approved','deleted') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone_number`, `alt_phone_number`, `email`, `date_of_birth`, `address`, `educational_attainment`, `work_history`, `preferred_location`, `languages`, `specialization_skills`, `employment_type`, `education_level`, `years_experience`, `picture`, `video_url`, `video_provider`, `video_type`, `video_title`, `video_thumbnail_url`, `video_duration_seconds`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(17, 'Dixon', 'hoyayo', 'Myas', '', '09128319264', '09128361628', 'ryzza@gmail.com', '1997-02-02', 'awawdknawldjakwdawdawdawdasdawd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Mandaluyong City\",\"Makati City\",\"Tondo Manila\",\"Pandacan Manila\",\"Paco Manila\",\"Pasay Manila\"]', '[\"Filipino\"]', '[\"Childcare &amp; Maternity (Yaya)\"]', 'Full Time', 'Secondary Level (Attended High School)', 2, 'applicants/698091c082c32_1770033600.jpg', 'admin/uploads/video/trial1.mp4', 'file', 'file', 'Video', NULL, NULL, 'approved', NULL, '2026-02-02 12:00:00', '2026-02-10 11:48:46', NULL),
(18, 'Mhi Mha', 'Central', 'Mha', '', '09283718231', '09128361628', 'renzeleven19@gmail.com', '2003-12-07', 'awdawd', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Metro Manila\",\"Pasay City\"]', '[\"Filipino\"]', '[\"Cleaning & Housekeeping (General)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698097177ea33_1770034967.jpg', NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', NULL, '2026-02-02 12:22:47', '2026-02-11 03:59:41', NULL),
(19, 'Imee', 'B', 'Bangag', '', '09283718231', '09128361628', 'ryzza@gmail.com', '1967-06-10', 'awdawdawdwad', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"ACLC Northbay Branch\",\"strand\":\"ICT\",\"year\":\"2020 - 2022\"},\"college\":{\"school\":\"Universdad De Manila\",\"course\":\"BSIT\",\"year\":\"2022 - 2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati City\"}]', '[\"Makati City\",\"Pasig City\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning &amp; Housekeeping (General)\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 4, 'applicants/69809f700d2cc_1770037104.png', NULL, NULL, 'iframe', NULL, NULL, NULL, 'approved', NULL, '2026-02-02 12:58:24', '2026-02-10 11:48:49', NULL),
(20, 'Jennifer', 'Would You', 'Refer', '', '09128319264', '09817238712', 'renzdiaz.contact@gmail.com', '1997-12-07', 'awdwadwadwadawdawd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Tondo Manila\",\"Espana Manila\"]', '[\"Filipino\"]', '[\"Cooking &amp; Food Service\",\"Elderly &amp; Special Care (Caregiver)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698184c4d0544_1770095812.jpg', 'video/a15648b66a48c21bcda8a11e33101712.mp4', 'file', 'file', 'Zyan Cabrera', NULL, NULL, 'approved', NULL, '2026-02-03 05:16:52', '2026-02-11 06:20:52', NULL),
(22, 'Annie', 'Are', 'You Okay', '', '09123861273', '09971286128', 'zinnerbro@gmail.com', '1995-04-17', '1223 kjlabmkdawkdkbawhdawdw', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2010 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[\"Mandaluyong City\",\"Makati City\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning &amp; Housekeeping (General)\"]', 'Part Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/6985660b79316_1770350091.jpg', 'video/698565f248a69_1770350066.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-06 03:54:26', '2026-02-11 06:04:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `document_type` enum('brgy_clearance','birth_certificate','sss','pagibig','nbi','police_clearance','tin_id','passport') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_documents`
--

INSERT INTO `applicant_documents` (`id`, `applicant_id`, `document_type`, `file_path`, `uploaded_at`) VALUES
(121, 17, 'brgy_clearance', 'documents/698091c08446c_1770033600.jpg', '2026-02-02 12:00:00'),
(122, 17, 'birth_certificate', 'documents/698091c08547d_1770033600.jpg', '2026-02-02 12:00:00'),
(123, 17, 'sss', 'documents/698091c0865b7_1770033600.jpg', '2026-02-02 12:00:00'),
(124, 17, 'pagibig', 'documents/698091c087950_1770033600.jpg', '2026-02-02 12:00:00'),
(125, 17, 'nbi', 'documents/698091c088ded_1770033600.jpg', '2026-02-02 12:00:00'),
(126, 17, 'police_clearance', 'documents/698091c089e36_1770033600.jpg', '2026-02-02 12:00:00'),
(127, 17, 'tin_id', 'documents/698091c09004b_1770033600.jpg', '2026-02-02 12:00:00'),
(128, 17, 'passport', 'documents/698091c0911ed_1770033600.jpg', '2026-02-02 12:00:00'),
(129, 18, 'brgy_clearance', 'documents/6980971780044_1770034967.jpg', '2026-02-02 12:22:47'),
(130, 18, 'birth_certificate', 'documents/69809717810e5_1770034967.jpg', '2026-02-02 12:22:47'),
(131, 18, 'sss', 'documents/6980971782153_1770034967.jpg', '2026-02-02 12:22:47'),
(132, 18, 'pagibig', 'documents/698097178303d_1770034967.jpg', '2026-02-02 12:22:47'),
(133, 18, 'nbi', 'documents/6980971783fab_1770034967.jpg', '2026-02-02 12:22:47'),
(134, 18, 'police_clearance', 'documents/6980971784cca_1770034967.jpg', '2026-02-02 12:22:47'),
(135, 18, 'tin_id', 'documents/6980971785bb4_1770034967.jpg', '2026-02-02 12:22:47'),
(136, 18, 'passport', 'documents/6980971786c2b_1770034967.jpg', '2026-02-02 12:22:47'),
(137, 19, 'brgy_clearance', 'documents/69809f700e97b_1770037104.jpg', '2026-02-02 12:58:24'),
(138, 19, 'birth_certificate', 'documents/69809f700f87c_1770037104.jpg', '2026-02-02 12:58:24'),
(139, 19, 'sss', 'documents/69809f70105f2_1770037104.jpg', '2026-02-02 12:58:24'),
(140, 19, 'pagibig', 'documents/69809f7011362_1770037104.jpg', '2026-02-02 12:58:24'),
(141, 19, 'nbi', 'documents/69809f7012e60_1770037104.jpg', '2026-02-02 12:58:24'),
(142, 19, 'police_clearance', 'documents/69809f7013bee_1770037104.jpg', '2026-02-02 12:58:24'),
(143, 19, 'tin_id', 'documents/69809f7014911_1770037104.jpg', '2026-02-02 12:58:24'),
(144, 19, 'passport', 'documents/69809f70155da_1770037104.jpg', '2026-02-02 12:58:24'),
(145, 20, 'brgy_clearance', 'documents/698184c4d1e92_1770095812.jpg', '2026-02-03 05:16:52'),
(146, 20, 'birth_certificate', 'documents/698184c4d2de7_1770095812.jpg', '2026-02-03 05:16:52'),
(147, 20, 'sss', 'documents/698184c4d3ca9_1770095812.jpg', '2026-02-03 05:16:52'),
(148, 20, 'pagibig', 'documents/698184c4d4d19_1770095812.jpg', '2026-02-03 05:16:52'),
(149, 20, 'nbi', 'documents/698184c4d7908_1770095812.jpg', '2026-02-03 05:16:52'),
(150, 20, 'police_clearance', 'documents/698184c4d9126_1770095812.jpg', '2026-02-03 05:16:52'),
(151, 20, 'tin_id', 'documents/698184c4da8fc_1770095812.jpg', '2026-02-03 05:16:52'),
(152, 20, 'passport', 'documents/698184c4dbe86_1770095812.jpg', '2026-02-03 05:16:52'),
(153, 22, 'brgy_clearance', 'documents/698565f23f917_1770350066.jpg', '2026-02-06 03:54:26'),
(154, 22, 'birth_certificate', 'documents/698565f240bfa_1770350066.jpg', '2026-02-06 03:54:26'),
(155, 22, 'sss', 'documents/698565f241b61_1770350066.jpg', '2026-02-06 03:54:26'),
(156, 22, 'pagibig', 'documents/698565f242a04_1770350066.jpg', '2026-02-06 03:54:26'),
(157, 22, 'nbi', 'documents/698565f2438fb_1770350066.jpg', '2026-02-06 03:54:26'),
(158, 22, 'police_clearance', 'documents/698565f244ccf_1770350066.jpg', '2026-02-06 03:54:26'),
(159, 22, 'tin_id', 'documents/698565f246001_1770350066.jpg', '2026-02-06 03:54:26'),
(160, 22, 'passport', 'documents/698565f2476fd_1770350066.jpg', '2026-02-06 03:54:26');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_reports`
--

CREATE TABLE `applicant_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_reports`
--

INSERT INTO `applicant_reports` (`id`, `applicant_id`, `admin_id`, `note_text`, `created_at`) VALUES
(1, 20, 4, 'awd', '2026-02-12 08:38:25'),
(2, 20, 4, 'awdawd', '2026-02-12 08:38:29');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_status_reports`
--

CREATE TABLE `applicant_status_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `from_status` varchar(50) NOT NULL,
  `to_status` varchar(50) NOT NULL,
  `report_text` text NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_status_reports`
--

INSERT INTO `applicant_status_reports` (`id`, `applicant_id`, `from_status`, `to_status`, `report_text`, `admin_id`, `created_at`) VALUES
(1, 17, 'on_process', 'approved', 'Manual test insert', 1, '2026-02-11 09:28:34'),
(2, 22, 'on_process', 'pending', 'trial', 5, '2026-02-11 11:13:15'),
(3, 22, 'on_process', 'approved', 'moved', 5, '2026-02-11 11:21:14'),
(4, 18, 'on_process', 'pending', 'trial', 5, '2026-02-11 11:59:41'),
(5, 22, 'on_process', 'approved', 'whattt', 5, '2026-02-11 13:28:59'),
(6, 22, 'on_process', 'pending', 'no clients', 4, '2026-02-11 14:04:56'),
(7, 20, 'on_process', 'approved', 'apporved relocate to client', 4, '2026-02-11 14:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `blacklisted_applicants`
--

CREATE TABLE `blacklisted_applicants` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `issue` text DEFAULT NULL,
  `proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`proof_paths`)),
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reverted_at` datetime DEFAULT NULL,
  `reverted_by` int(10) UNSIGNED DEFAULT NULL,
  `compliance_note` text DEFAULT NULL,
  `compliance_proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`compliance_proof_paths`)),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blacklisted_applicants`
--

INSERT INTO `blacklisted_applicants` (`id`, `applicant_id`, `reason`, `issue`, `proof_paths`, `created_by`, `is_active`, `created_at`, `reverted_at`, `reverted_by`, `compliance_note`, `compliance_proof_paths`, `updated_at`) VALUES
(2, 22, 'asd', 'asdasd', '[\"blacklist\\/698c30c2dbdbd_1770795202.jpg\"]', 9, 1, '2026-02-11 07:33:22', NULL, NULL, NULL, NULL, '2026-02-11 07:33:22');

-- --------------------------------------------------------

--
-- Table structure for table `client_bookings`
--

CREATE TABLE `client_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `services_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`services_json`)),
  `appointment_type` enum('Video Call','Audio Call','Chat','Office Visit') NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `client_first_name` varchar(100) NOT NULL,
  `client_middle_name` varchar(100) DEFAULT NULL,
  `client_last_name` varchar(100) NOT NULL,
  `client_phone` varchar(30) NOT NULL,
  `client_email` varchar(150) NOT NULL,
  `client_address` varchar(255) NOT NULL,
  `status` enum('submitted','confirmed','cancelled') NOT NULL DEFAULT 'submitted',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `client_bookings`
--

INSERT INTO `client_bookings` (`id`, `applicant_id`, `services_json`, `appointment_type`, `appointment_date`, `appointment_time`, `client_first_name`, `client_middle_name`, `client_last_name`, `client_phone`, `client_email`, `client_address`, `status`, `created_at`, `updated_at`) VALUES
(4, 19, '[\"Cooking &amp; Food Service\",\"Pet &amp; Outdoor Maintenance\"]', 'Office Visit', '2026-02-25', '10:00:00', 'Renz Roann', 'B.', 'Diaz', '09270746258', 'renzdiaz.contact@gmai.com', '2461 Princess Floresca St. Pandacan, Manila', 'submitted', '2026-02-04 02:49:38', '2026-02-04 02:49:38'),
(5, 20, '[\"Cleaning &amp; Housekeeping (General)\",\"Elderly &amp; Special Care (Caregiver)\"]', 'Audio Call', '2026-02-05', '11:20:00', 'wertyuio', 'wertyui', 'rtyui', '234567890', 'ty@gmail.com', '1231 rjgu', 'submitted', '2026-02-05 03:20:33', '2026-02-05 03:20:33'),
(6, 19, '[\"Cleaning &amp; Housekeeping (General)\",\"Childcare &amp; Maternity (Yaya)\"]', 'Audio Call', '2026-02-21', '09:00:00', 'John Ellijah', 'M.', 'Ocampo', '09128371827', 'elli@gmail.com', '2381 luakwhduiawdluawliudwa', 'submitted', '2026-02-07 11:01:51', '2026-02-07 11:01:51'),
(7, 20, '[\"Cooking &amp; Food Service\",\"Laundry &amp; Clothing Care\"]', 'Office Visit', '2026-02-26', '09:00:00', 'Andrei', 'B.', 'Javillo', '09123971283', 'renzdiaz.contact@gmai.com', '381lkseajhdawdawdaw', 'submitted', '2026-02-10 06:12:54', '2026-02-10 06:12:54'),
(8, 17, '[\"Cooking &amp; Food Service\"]', 'Office Visit', '2026-03-05', '08:00:00', 'Berloloy', 'S.', 'Tambaloloy', '09278713871', 'renzdiaz.contact@gmai.com', '8123 iawkhdwiadwaawdaw awd awd', 'submitted', '2026-02-10 11:44:20', '2026-02-10 11:44:20');

-- --------------------------------------------------------

--
-- Table structure for table `session_logs`
--

CREATE TABLE `session_logs` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `session_logs`
--

INSERT INTO `session_logs` (`id`, `admin_id`, `ip_address`, `user_agent`, `login_time`, `logout_time`) VALUES
(1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 08:46:42', '2026-02-02 08:46:44'),
(24, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 10:48:46', NULL),
(25, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 11:12:38', NULL),
(26, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 13:11:28', NULL),
(27, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 13:52:23', NULL),
(28, 11, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:43:01', '2026-02-11 14:43:20'),
(29, 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:43:30', '2026-02-11 14:48:47'),
(30, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:52:50', '2026-02-11 15:28:02'),
(31, 9, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:28:20', NULL),
(32, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 08:34:04', NULL);

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
  ADD UNIQUE KEY `uniq_admin_username` (`username`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_applicants_status` (`status`),
  ADD KEY `idx_applicants_deleted_at` (`deleted_at`),
  ADD KEY `idx_applicants_created_by` (`created_by`),
  ADD KEY `idx_applicants_created_at` (`created_at`);

--
-- Indexes for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_applicant_documents_applicant_id` (`applicant_id`);

--
-- Indexes for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_reports_applicant` (`applicant_id`);

--
-- Indexes for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `applicant_id` (`applicant_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `idx_asr_applicant_id` (`applicant_id`);

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
-- Indexes for table `client_bookings`
--
ALTER TABLE `client_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_applicant` (`applicant_id`),
  ADD KEY `idx_client_bookings_created_at` (`created_at`),
  ADD KEY `idx_client_bookings_status` (`status`),
  ADD KEY `idx_client_bookings_app_created` (`applicant_id`,`created_at`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=232;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=161;

--
-- AUTO_INCREMENT for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `client_bookings`
--
ALTER TABLE `client_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_activity_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `fk_applicants_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  ADD CONSTRAINT `fk_applicant_documents_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  ADD CONSTRAINT `fk_app_reports_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  ADD CONSTRAINT `fk_asr_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  ADD CONSTRAINT `fk_blacklist_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blacklist_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_blacklist_reverted_by` FOREIGN KEY (`reverted_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `client_bookings`
--
ALTER TABLE `client_bookings`
  ADD CONSTRAINT `fk_booking_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `session_logs`
--
ALTER TABLE `session_logs`
  ADD CONSTRAINT `fk_session_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
