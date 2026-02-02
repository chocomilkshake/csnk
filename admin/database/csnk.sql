-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 02, 2026 at 09:00 AM
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
(61, 1, 'Update Applicant', 'Updated applicant ID: 14', '::1', '2026-02-02 05:50:22');

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
(9, 'Renz Roann', 'Batuigas', 'Diaz', '', '09123861273', '09123617263', 'renzdiaz.contact@gmail.com', '2003-12-07', '2461 Princess Floresca St. Pandacan, Manila 1011 6th District', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"ACLC Northbay Branch\",\"strand\":\"ICT\",\"year\":\"2020 - 2022\"},\"college\":{\"school\":\"Universdad De Manila\",\"course\":\"BSIT\",\"year\":\"2022 - 2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati City\"}]', '[\"Metro Manila\",\"Mandaluyong City\",\"Makati City\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning & Housekeeping (General)\",\"Cooking & Food Service\",\"Elderly & Special Care (Caregiver)\",\"Pet & Outdoor Maintenance\"]', 'Full Time', 'Tertiary Level (College Undergraduate)', 4, 'applicants/698013003d8a5_1770001152.jpg', 'pending', 1, '2026-02-02 02:59:12', '2026-02-02 06:32:05', NULL),
(10, 'Imee', 'Bangag', 'Marcos', '', '09128319264', '09817238712', 'awdawd@gmail.com', '1967-12-20', 'Cubao Ibabao Philippines', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2003\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"Universdad De Manila\",\"course\":\"BSIT\",\"year\":\"2022 - 2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"IT Programmer\",\"location\":\"\"}]', '[\"Ilocos Norte\",\"Sultan Kudarat\"]', '[\"English\",\"Filipino\"]', '[\"Cooking & Food Service\",\"Childcare & Maternity (Yaya)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/6980338fa3535_1770009487.png', 'pending', 1, '2026-02-02 03:46:22', '2026-02-02 06:32:05', NULL),
(11, 'Jolly', 'Jens', 'Takolokoy', '', '09128319264', '09971286128', 'renztwelve19@gmail.com', '2003-02-09', 'Metro Manila', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"ACLC Northbay Branch\",\"strand\":\"ICT\",\"year\":\"2020 - 2022\"},\"college\":{\"school\":\"Universdad De Manila\",\"course\":\"BSIT\",\"year\":\"2022 - 2026\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2026 - 2028\",\"role\":\"awdw\",\"location\":\"Sta. Ana Manila\"}]', '[\"Marikina City\",\"Metro Manila\"]', '[\"Filipino\"]', '[\"Cooking & Food Service\",\"Elderly & Special Care (Caregiver)\",\"Pet & Outdoor Maintenance\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/69801f096b991_1770004233.jpg', 'pending', 1, '2026-02-02 03:50:33', '2026-02-02 06:32:05', NULL),
(12, 'Dixon', 'F', 'Myas', '', '09128319264', '09971286128', 'renztwelve19@gmail.com', '1997-12-21', 'Caloocan Philippines', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2010 - 2015\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[\"Metro Manila\",\"Makati City\",\"Mandaluyong City\"]', '[\"Filipino\"]', '[\"Cleaning & Housekeeping (General)\",\"Childcare & Maternity (Yaya)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 'Secondary Level (Attended High School)', 5, 'applicants/69801fad9d33e_1770004397.jpg', 'pending', 1, '2026-02-02 03:53:17', '2026-02-02 06:32:05', NULL),
(13, 'Test2', 'awd', 'awda', '', '09128319264', '09823648123', 'renzdiaz.contact@gmail.com', '2001-09-11', 'awdalkwjbdawdawdasdawdawd', '{\"elementary\":{\"school\":\"awdawdawd\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2010 - 2015\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[\"Marikina City\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning & Housekeeping (General)\",\"Laundry & Clothing Care\",\"Elderly & Special Care (Caregiver)\",\"Pet & Outdoor Maintenance\"]', 'Full Time', 'Secondary Level (Attended High School)', 5, 'applicants/6980203d71d0c_1770004541.jpg', 'pending', 1, '2026-02-02 03:55:41', '2026-02-02 06:32:05', NULL),
(14, 'Test 2', 'Batuigas', 'Myas', '', '09123123718', '09128361628', 'zinnerbro@gmail.com', '1992-02-02', 'awdawdawdasdawd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2026 - 2027\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Metro Manila\",\"Espana Manila\"]', '[\"Filipino\"]', '[\"Cleaning & Housekeeping (General)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 1, 'applicants/698020d52a15e_1770004693.jpg', 'pending', 1, '2026-02-02 03:58:13', '2026-02-02 06:32:05', NULL),
(15, 'Test3', 'awdaw', 'awdawd', '', '09123123718', '09128361628', 'ryzza@gmail.com', '1987-12-07', 'awdawdadawdawd', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2003\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Makati City\"]', '[\"English\",\"Filipino\"]', '[\"Cooking & Food Service\",\"Childcare & Maternity (Yaya)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698021a265ec0_1770004898.jpg', 'pending', 1, '2026-02-02 04:01:38', '2026-02-02 06:32:05', NULL);

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
(57, 9, 'brgy_clearance', 'documents/698013003eccd_1770001152.jpg', '2026-02-02 02:59:12'),
(58, 9, 'birth_certificate', 'documents/698013003ffba_1770001152.jpg', '2026-02-02 02:59:12'),
(59, 9, 'sss', 'documents/69801300411e7_1770001152.jpg', '2026-02-02 02:59:12'),
(60, 9, 'pagibig', 'documents/6980130042184_1770001152.jpg', '2026-02-02 02:59:12'),
(61, 9, 'nbi', 'documents/6980130042e5d_1770001152.jpg', '2026-02-02 02:59:12'),
(62, 9, 'police_clearance', 'documents/6980130043d31_1770001152.jpg', '2026-02-02 02:59:12'),
(63, 9, 'tin_id', 'documents/6980130044d55_1770001152.jpg', '2026-02-02 02:59:12'),
(64, 9, 'passport', 'documents/6980130045b6b_1770001152.jpeg', '2026-02-02 02:59:12'),
(65, 10, 'brgy_clearance', 'documents/69801e0e82918_1770003982.jpg', '2026-02-02 03:46:22'),
(66, 10, 'birth_certificate', 'documents/69801e0e83566_1770003982.jpg', '2026-02-02 03:46:22'),
(67, 10, 'sss', 'documents/69801e0e83d27_1770003982.jpg', '2026-02-02 03:46:22'),
(68, 10, 'pagibig', 'documents/69801e0e8454f_1770003982.jpg', '2026-02-02 03:46:22'),
(69, 10, 'nbi', 'documents/69801e0e8505e_1770003982.jpg', '2026-02-02 03:46:22'),
(70, 10, 'police_clearance', 'documents/69801e0e858a9_1770003982.jpg', '2026-02-02 03:46:22'),
(71, 10, 'tin_id', 'documents/69801e0e85f7c_1770003982.jpg', '2026-02-02 03:46:22'),
(72, 10, 'passport', 'documents/69801e0e866fc_1770003982.jpg', '2026-02-02 03:46:22'),
(73, 11, 'brgy_clearance', 'documents/69801f096ceee_1770004233.jpg', '2026-02-02 03:50:33'),
(74, 11, 'birth_certificate', 'documents/69801f096ef5f_1770004233.jpg', '2026-02-02 03:50:33'),
(75, 11, 'sss', 'documents/69801f096f8b1_1770004233.jpg', '2026-02-02 03:50:33'),
(76, 11, 'pagibig', 'documents/69801f0970281_1770004233.jpg', '2026-02-02 03:50:33'),
(77, 11, 'nbi', 'documents/69801f0970eb3_1770004233.jpg', '2026-02-02 03:50:33'),
(78, 11, 'police_clearance', 'documents/69801f0971707_1770004233.jpg', '2026-02-02 03:50:33'),
(79, 11, 'tin_id', 'documents/69801f0971e60_1770004233.jpg', '2026-02-02 03:50:33'),
(80, 11, 'passport', 'documents/69801f0972602_1770004233.jpg', '2026-02-02 03:50:33'),
(81, 12, 'brgy_clearance', 'documents/69801fad9e0c0_1770004397.jpg', '2026-02-02 03:53:17'),
(82, 12, 'birth_certificate', 'documents/69801fad9e9dc_1770004397.jpg', '2026-02-02 03:53:17'),
(83, 12, 'sss', 'documents/69801fad9f5e2_1770004397.jpg', '2026-02-02 03:53:17'),
(84, 12, 'pagibig', 'documents/69801fad9fe4f_1770004397.jpg', '2026-02-02 03:53:17'),
(85, 12, 'nbi', 'documents/69801fada0802_1770004397.jpg', '2026-02-02 03:53:17'),
(86, 12, 'police_clearance', 'documents/69801fada14fb_1770004397.jpg', '2026-02-02 03:53:17'),
(87, 12, 'tin_id', 'documents/69801fada1dc0_1770004397.jpg', '2026-02-02 03:53:17'),
(88, 12, 'passport', 'documents/69801fada28fa_1770004397.jpg', '2026-02-02 03:53:17');

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
(3, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-02 09:06:56', NULL);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

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
