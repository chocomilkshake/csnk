-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 03, 2026 at 01:43 AM
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
(1, 1, 'Login', 'User logged in successfully', '::1', '2026-01-28 05:38:25'),
(2, 1, 'Add Applicant', 'Added new applicant: Renz Roann Diaz', '::1', '2026-01-30 06:44:59'),
(3, 1, 'Update Applicant', 'Updated applicant ID: 4', '::1', '2026-01-30 07:36:50'),
(4, 1, 'Update Applicant', 'Updated applicant ID: 4', '::1', '2026-01-30 07:37:20'),
(5, 1, 'Delete Applicant', 'Deleted applicant ID: 4', '::1', '2026-01-30 07:38:16'),
(6, 1, 'Delete Applicant', 'Deleted applicant ID: 3', '::1', '2026-01-30 07:38:19'),
(7, 1, 'Delete Applicant', 'Deleted applicant ID: 2', '::1', '2026-01-30 07:38:22'),
(8, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 2', '::1', '2026-01-30 07:38:26'),
(9, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 3', '::1', '2026-01-30 07:38:28'),
(10, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 4', '::1', '2026-01-30 07:38:30'),
(11, 1, 'Add Applicant', 'Added new applicant: Renz Roann Diaz', '::1', '2026-01-30 07:41:29'),
(12, 1, 'Update Applicant', 'Updated applicant ID: 5', '::1', '2026-01-30 07:42:01'),
(13, 1, 'Update Applicant', 'Updated applicant ID: 5', '::1', '2026-01-30 07:55:30'),
(14, 1, 'Add Applicant', 'Added new applicant: awdaw awdawdw', '::1', '2026-01-30 07:58:49'),
(15, 1, 'Delete Applicant', 'Deleted applicant ID: 6', '::1', '2026-01-30 08:04:28'),
(16, 1, 'Delete Applicant', 'Deleted applicant ID: 5', '::1', '2026-01-30 08:04:32'),
(17, 1, 'Add Applicant', 'Added new applicant: Renz Roann Diaz', '::1', '2026-01-30 08:06:50'),
(18, 1, 'Logout', 'User logged out', '::1', '2026-02-02 00:46:38'),
(19, 1, 'Login', 'User logged in successfully', '::1', '2026-02-02 00:46:42'),
(20, 1, 'Logout', 'User logged out', '::1', '2026-02-02 00:46:44'),
(21, 1, 'Login', 'User logged in successfully', '::1', '2026-02-02 01:01:52'),
(22, 1, 'Logout', 'User logged out', '::1', '2026-02-02 01:04:08'),
(23, 1, 'Login', 'User logged in successfully', '::1', '2026-02-02 01:06:56'),
(24, 1, 'Update Applicant', 'Updated applicant ID: 7', '::1', '2026-02-02 01:40:51'),
(25, 1, 'Restore Applicant', 'Restored applicant ID: 5', '::1', '2026-02-02 01:43:28'),
(26, 1, 'Restore Applicant', 'Restored applicant ID: 6', '::1', '2026-02-02 01:43:29'),
(27, 1, 'Export Excel', 'Exported all applicants list', '::1', '2026-02-02 01:52:53'),
(28, 1, 'Delete Applicant', 'Deleted applicant ID: 7', '::1', '2026-02-02 02:29:49'),
(29, 1, 'Delete Applicant', 'Deleted applicant ID: 6', '::1', '2026-02-02 02:29:51'),
(30, 1, 'Delete Applicant', 'Deleted applicant ID: 5', '::1', '2026-02-02 02:29:55'),
(31, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 5', '::1', '2026-02-02 02:30:06'),
(32, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 6', '::1', '2026-02-02 02:30:10'),
(33, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 7', '::1', '2026-02-02 02:30:19'),
(34, 1, 'Add Applicant', 'Added new applicant: Ryzza Mae Dizon', '::1', '2026-02-02 02:35:00'),
(35, 1, 'Update Applicant', 'Updated applicant ID: 8', '::1', '2026-02-02 02:35:20'),
(36, 1, 'Update Applicant', 'Updated applicant ID: 8', '::1', '2026-02-02 02:36:24'),
(37, 1, 'Update Applicant', 'Updated applicant ID: 8', '::1', '2026-02-02 02:40:31'),
(38, 1, 'Update Applicant', 'Updated applicant ID: 8', '::1', '2026-02-02 02:40:56'),
(39, 1, 'Delete Applicant', 'Deleted applicant ID: 8', '::1', '2026-02-02 02:55:02'),
(40, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 8', '::1', '2026-02-02 02:55:10'),
(41, 1, 'Add Applicant', 'Added new applicant: Renz Roann Diaz', '::1', '2026-02-02 02:59:12'),
(42, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:17:37'),
(43, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:31:39'),
(44, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:31:53'),
(45, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:32:51'),
(46, 1, 'Add Applicant', 'Added new applicant: Imee Marcos', '::1', '2026-02-02 03:46:22'),
(47, 1, 'Update Applicant', 'Updated applicant ID: 10', '::1', '2026-02-02 03:47:02'),
(48, 1, 'Update Applicant', 'Updated applicant ID: 10', '::1', '2026-02-02 03:47:12'),
(49, 1, 'Add Applicant', 'Added new applicant: Jolly Takolokoy', '::1', '2026-02-02 03:50:33'),
(50, 1, 'Add Applicant', 'Added new applicant: Dixon Myas', '::1', '2026-02-02 03:53:17'),
(51, 1, 'Update Applicant', 'Updated applicant ID: 12', '::1', '2026-02-02 03:53:35'),
(52, 1, 'Add Applicant', 'Added new applicant: Test2 awda', '::1', '2026-02-02 03:55:41'),
(53, 1, 'Add Applicant', 'Added new applicant: Test 2 Myas', '::1', '2026-02-02 03:58:13'),
(54, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:58:35'),
(55, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:58:59'),
(56, 1, 'Update Applicant', 'Updated applicant ID: 9', '::1', '2026-02-02 03:59:16'),
(57, 1, 'Add Applicant', 'Added new applicant: Test3 awdawd', '::1', '2026-02-02 04:01:38'),
(58, 1, 'Update Applicant', 'Updated applicant ID: 15', '::1', '2026-02-02 05:12:47'),
(59, 1, 'Update Applicant', 'Updated applicant ID: 10', '::1', '2026-02-02 05:18:07'),
(60, 1, 'Update Applicant', 'Updated applicant ID: 15', '::1', '2026-02-02 05:22:11'),
(61, 1, 'Update Applicant', 'Updated applicant ID: 14', '::1', '2026-02-02 05:50:22'),
(62, 1, 'Add Applicant', 'Added new applicant: awd awd', '::1', '2026-02-02 08:10:25'),
(63, 1, 'Delete Applicant', 'Deleted applicant ID: 15', '::1', '2026-02-02 08:13:07'),
(64, 1, 'Delete Applicant', 'Deleted applicant ID: 14', '::1', '2026-02-02 08:13:11'),
(65, 1, 'Delete Applicant', 'Deleted applicant ID: 13', '::1', '2026-02-02 08:13:13'),
(66, 1, 'Delete Applicant', 'Deleted applicant ID: 12', '::1', '2026-02-02 08:13:17'),
(67, 1, 'Delete Applicant', 'Deleted applicant ID: 11', '::1', '2026-02-02 08:13:20'),
(68, 1, 'Delete Applicant', 'Deleted applicant ID: 10', '::1', '2026-02-02 08:13:24'),
(69, 1, 'Delete Applicant', 'Deleted applicant ID: 9', '::1', '2026-02-02 08:13:34'),
(70, 1, 'Update Applicant', 'Updated applicant ID: 16', '::1', '2026-02-02 08:13:55'),
(71, 1, 'Logout', 'User logged out', '::1', '2026-02-02 08:14:07'),
(72, 1, 'Login', 'User logged in successfully', '::1', '2026-02-02 08:14:11'),
(73, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 9', '::1', '2026-02-02 08:14:27'),
(74, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 10', '::1', '2026-02-02 08:14:31'),
(75, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 11', '::1', '2026-02-02 08:14:34'),
(76, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 12', '::1', '2026-02-02 08:14:36'),
(77, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 13', '::1', '2026-02-02 08:14:39'),
(78, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 14', '::1', '2026-02-02 08:14:51'),
(79, 1, 'Permanent Delete', 'Permanently deleted applicant ID: 15', '::1', '2026-02-02 08:14:57'),
(80, 1, 'Login', 'User logged in successfully', '::1', '2026-02-02 11:55:56'),
(81, 1, 'Add Applicant', 'Added new applicant: Dixon Myas', '::1', '2026-02-02 12:00:00'),
(82, 1, 'Add Applicant', 'Added new applicant: Mhi Mha Mha', '::1', '2026-02-02 12:22:47'),
(83, 1, 'Add Applicant', 'Added new applicant: Imee Bangag', '::1', '2026-02-02 12:58:24'),
(84, 1, 'Login', 'User logged in successfully', '::1', '2026-02-03 00:17:53');

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
(1, 'csnk001', 'admin@csnk.com', '$2y$10$UMSfwBdWu90vEe5hte4GwuV9Lz0SpimOTD34OA9eRN9jqgDhQqw1W', 'System Administrator', 'avatars/6979a2a03d893_1769579168.jpg', 'super_admin', 'active', '2026-01-28 05:25:51', '2026-01-28 05:46:08'),
(2, 'csnk002', 'renzdiaz.contact@gmail.com', '$2y$10$RNZ33JEaaTDThQ.q7PqGrOM40LkCOMuy0RFQaLUvBMEiYknT9aUK.', 'Renz Roann B. Diaz', NULL, 'super_admin', 'active', '2026-01-28 07:51:17', '2026-01-28 07:51:17');

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
  `status` enum('pending','on_process','approved','deleted') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone_number`, `alt_phone_number`, `email`, `date_of_birth`, `address`, `educational_attainment`, `work_history`, `preferred_location`, `languages`, `specialization_skills`, `employment_type`, `education_level`, `years_experience`, `picture`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(17, 'Dixon', 'hoyayo', 'Myas', '', '09128319264', '09128361628', 'ryzza@gmail.com', '1997-02-02', 'awawdknawldjakwdawdawdawdasdawd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Mandaluyong City\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\",\"Elderly nad Special Care (Caregiver)\"]', 'Full Time', 'Secondary Level (Attended High School)', 2, 'applicants/698091c082c32_1770033600.jpg', 'pending', 1, '2026-02-02 12:00:00', '2026-02-02 12:00:00', NULL),
(18, 'Mhi Mha', 'Central', 'Mha', '', '09283718231', '09128361628', 'renzeleven19@gmail.com', '2003-12-07', 'awdawd', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Metro Manila\",\"Pasay City\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly nad Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698097177ea33_1770034967.jpg', 'pending', 1, '2026-02-02 12:22:47', '2026-02-02 12:22:47', NULL),
(19, 'Imee', 'B', 'Bangag', '', '09283718231', '09128361628', 'ryzza@gmail.com', '1967-06-10', 'awdawdawdwad', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"ACLC Northbay Branch\",\"strand\":\"ICT\",\"year\":\"2020 - 2022\"},\"college\":{\"school\":\"Universdad De Manila\",\"course\":\"BSIT\",\"year\":\"2022 - 2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati City\"}]', '[\"Makati City\",\"Pasig City\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Elderly nad Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 4, 'applicants/69809f700d2cc_1770037104.png', 'pending', 1, '2026-02-02 12:58:24', '2026-02-02 12:58:24', NULL);

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
(144, 19, 'passport', 'documents/69809f70155da_1770037104.jpg', '2026-02-02 12:58:24');

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
(1, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 08:46:42', '2026-02-02 08:46:44'),
(2, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:01:52', '2026-02-02 09:04:08'),
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:06:56', '2026-02-02 16:14:07'),
(4, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 16:14:11', NULL),
(5, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 19:55:56', NULL),
(6, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-03 08:17:53', NULL);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

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
-- Constraints for table `session_logs`
--
ALTER TABLE `session_logs`
  ADD CONSTRAINT `fk_session_logs_admin` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
