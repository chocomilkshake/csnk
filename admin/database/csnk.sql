-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 20, 2026 at 03:29 AM
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
(323, 4, 'Logout', 'User logged out', '::1', '2026-02-19 03:56:26'),
(324, 4, 'Login', 'User logged in successfully', '::1', '2026-02-19 03:56:45'),
(325, 4, 'Logout', 'User logged out', '::1', '2026-02-19 05:10:05'),
(326, 13, 'Login', 'User logged in successfully', '::1', '2026-02-19 05:10:21'),
(327, 13, 'Logout', 'User logged out', '::1', '2026-02-19 05:10:45'),
(328, 12, 'Login', 'User logged in successfully', '::1', '2026-02-19 05:11:02'),
(329, 12, 'Create Account', 'Created employee employee002 (smc)', '::1', '2026-02-19 05:14:47'),
(330, 12, 'Logout', 'User logged out', '::1', '2026-02-19 05:14:49'),
(331, 16, 'Login', 'User logged in successfully', '::1', '2026-02-19 05:14:55'),
(332, 16, 'Logout', 'User logged out', '::1', '2026-02-19 05:46:11'),
(333, 12, 'Login', 'User logged in successfully', '::1', '2026-02-19 06:37:06'),
(334, 12, 'Update Applicant Status (with report)', 'Updated status for Ava Marie Thompson → approved; Reason: Hired', '::1', '2026-02-19 06:56:21'),
(335, 12, 'Add Applicant', 'Added new applicant: Ryzza Mae Diaz', '::1', '2026-02-19 07:02:25'),
(336, 12, 'Update Applicant', 'Updated applicant Ryzza Mae B. Diaz (ID: 43)', '::1', '2026-02-19 07:02:57'),
(337, 12, 'Logout', 'User logged out', '::1', '2026-02-19 07:06:51'),
(338, 13, 'Login', 'User logged in successfully', '::1', '2026-02-19 07:07:00'),
(339, 13, 'Logout', 'User logged out', '::1', '2026-02-19 07:07:45'),
(340, 12, 'Login', 'User logged in successfully', '::1', '2026-02-19 07:07:52');

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
  `agency` enum('csnk','smc') DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `avatar`, `role`, `agency`, `status`, `created_at`, `updated_at`) VALUES
(4, 'renzadmin', 'renzdiaz.contact@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$US9RRDloSHR3MGlzeUdGdw$cjNozvyDewv1phUaRVyn/6zcDKOdoSGJp1fBt5MABFE', 'Renz Diaz', 'avatars/699556f4c657c_1771394804.jpg', 'super_admin', NULL, 'active', '2026-02-07 10:20:55', '2026-02-18 06:06:44'),
(5, 'elliadmin', 'elli@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cFZvOHZCckJkcDd4a0Y1cA$d+28H23RKZagXG81OSdY8xWa8x2KNSFuHip8xsxI2No', 'John Ellijah', NULL, 'super_admin', NULL, 'active', '2026-02-07 10:21:28', '2026-02-10 11:15:33'),
(6, 'andreiadmin', 'andrei@gmail.com', '$2y$10$ROQGHUJso58ON6NCsv2PRO14x3Nviq3fZrkEU8KLne6BTEbVuhSq2', 'Andrei Javillo', NULL, 'super_admin', NULL, 'active', '2026-02-07 10:22:05', '2026-02-07 10:22:05'),
(7, 'ralphadmin', 'ralph@gmail.com', '$2y$10$MUi6.7QJykPG48jx9e8lLu2V72JRHYu91.aRd5LFviHcJokQfvaf2', 'Ralph Justine Gallentes', NULL, 'super_admin', NULL, 'active', '2026-02-10 00:32:00', '2026-02-10 00:32:00'),
(8, 'cabritoadmin', 'cabs@gmail.com', '$2y$10$AbWEDXv5fqBAkhk1quS.7.eJKD2uyUyenhinmN906bbJlePsxOlSq', 'John Adrian Cabrito', NULL, 'super_admin', NULL, 'active', '2026-02-10 00:32:53', '2026-02-10 00:32:53'),
(12, 'jmpogi', 'jm@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QkMvd1FUc2Q0bnBjWHB0Uw$kltUwYy7N9gm+yGcuxlWqQFXnwD/EPRKRexQ1sDBYQM', 'John Michael Masmela', NULL, 'admin', NULL, 'active', '2026-02-12 02:33:42', '2026-02-12 02:41:56'),
(13, 'employee001', 'employee001@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$TmI0WmdjYXlyU0padDBVNg$CkA3Ann74QvQzQ4SrZW+kHMo0YyP4ZkwvJZ8fKV9QxE', 'empoy', NULL, 'employee', 'csnk', 'active', '2026-02-13 01:20:31', '2026-02-19 03:49:35'),
(14, 'admin001', 'admin@gmail.com', '$2y$10$crH6qvre7zC4HohxKTUPS.Zd3DXUJO4uHpSq90MyvD.nHr/Waoaiy', 'admin', NULL, 'admin', NULL, 'active', '2026-02-18 05:14:06', '2026-02-18 05:14:06'),
(15, 'Admin002', 'admin002@gmail.com', '$2y$10$mc4Z1Y893ZxoLdQCHS116.ba4BGt1gesD3vr883Cx4vgkgwYriMom', 'admin', NULL, 'admin', NULL, 'active', '2026-02-18 05:15:39', '2026-02-18 05:15:39'),
(16, 'employee002', 'empkiye@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$MFMwUnUySkJIOTRETG13cg$5UFcack6t0zUG3waYREZF9YeuHxDJQ5cXYjfYmIaY2U', 'empoy002', NULL, 'employee', 'smc', 'active', '2026-02-19 05:14:47', '2026-02-19 05:14:47');

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
(23, 'Maria Lourdes', 'Santos', 'Cruz', '', '09124567831', '09167345218', 'maria.cruz28@example.com', '1997-03-14', '1241 Ilang‑Ilang St., Brgy. 105, Tondo, Manila', '{\"elementary\":{\"school\":\"Jose Corazon de Jesus Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Tondo High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeepe\",\"location\":\"Pasay\"}]', '[\"Manila\",\"Pasay\",\"Makati\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9436d886b_1770951734.jpg', 'video/698e9436e46e0_1770951734.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:02:14', '2026-02-13 03:02:14', NULL),
(24, 'Joanna Marie', 'Pascual', 'Dela Torre', '', '09983457621', '09284567310', 'joannamdtorre@example.com', '1991-07-22', '92 Dahlia St., Brgy. Baesa, Quezon City', '{\"elementary\":{\"school\":\"Baesa Elementary School\",\"year\":\"1998\\u20132004\"},\"highschool\":{\"school\":\"Quezon City High Schoo\",\"year\":\"2004\\u20132008\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CleanPro Manila\",\"years\":\"2018\\u20132023\",\"role\":\"All\\u2011Around Helper\",\"location\":\"Quezon City\"}]', '[\"Quezon City\",\"Manila\",\"San Juan\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 5, 'applicants/698e9578069e6_1770952056.jpg', 'video/698e95781c1dc_1770952056.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:07:36', '2026-02-13 03:07:36', NULL),
(25, 'Ana Beatriz', 'Gomez', 'Reyes', '', '09156780234', '09156780234', 'ana.reyes25@example.com', '2001-01-09', '815 San Marcelino St., Brgy. Malate, Manila', '{\"elementary\":{\"school\":\"Malate Elementary School\",\"year\":\"2007\\u20132013\"},\"highschool\":{\"school\":\"Manila High School\",\"year\":\"2013\\u20132017\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Family Care Agency\",\"years\":\"2022\\u20132024\",\"role\":\"Babysitter\",\"location\":\"Ermita\"}]', '[\"Manila\",\"Pasay\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e96988e7b0_1770952344.jpg', 'video/698e96989948b_1770952344.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:12:24', '2026-02-13 03:12:24', NULL),
(26, 'Kristine Joy', 'Villanueva', 'Ramos', '', '09097865432', '09120457839', 'kjramos42@example.com', '1983-06-03', '54 Sampaguita St., Brgy. Cupang, Muntinlupa City', '{\"elementary\":{\"school\":\"Cupang Elementary School\",\"year\":\"1990\\u20131996\"},\"highschool\":{\"school\":\"Muntinlupa National High School\",\"year\":\"1996\\u20132000\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SouthClean Services\",\"years\":\"2017\\u20132023\",\"role\":\"Housemaid\",\"location\":\"Muntinlupa\"},{\"company\":\"Evergreen Laundry\",\"years\":\"2014\\u20132017\",\"role\":\"Laundry Worker\",\"location\":\"Pasig\"}]', '[\"Muntinlupa\",\"Las Pi\\u00f1as\",\"Para\\u00f1aque\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 9, 'applicants/698e977bb17ee_1770952571.jpg', 'video/698e977bc23d6_1770952571.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:16:11', '2026-02-13 03:16:11', NULL),
(27, 'Shiela May', 'Basco', 'Cortez', '', '09189234577', '09361245780', 'shielamcortez30@example.com', '1995-11-16', '2385 Mabini St., Brgy. San Andres Bukid, Manila', '{\"elementary\":{\"school\":\"San Andres Elementary School\",\"year\":\"2002\\u20132008\"},\"highschool\":{\"school\":\"Arellano High School\",\"year\":\"2008\\u20132012\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"FreshStart Maid Agency\",\"years\":\"2020\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Makati\"}]', '[\"Makati\",\"Manila\",\"Taguig\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/698e984dec764_1770952781.jpg', 'video/698e984e007ce_1770952782.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:19:41', '2026-02-13 03:19:42', NULL),
(28, 'Rowena Liza', 'Cruz', 'Mariano', '', '09351240988', '09278450329', 'rowenamariano45@example.com', '1980-09-28', '702 Maliputo St., Brgy. Karuhatan, Valenzuela City', '{\"elementary\":{\"school\":\"Karuhatan Elementary School\",\"year\":\"1987\\u20131993\"},\"highschool\":{\"school\":\"Valenzuela National High School\",\"year\":\"1993\\u20131997\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"North Metro Helpers\",\"years\":\"2018\\u20132024\",\"role\":\"Cook\\/Housemaid\",\"location\":\"Valenzuela\"},{\"company\":\"CarePlus\",\"years\":\"2014\\u20132018\",\"role\":\"All\\u2011Around Helper\",\"location\":\"Valenzuela\"}]', '[\"Valenzuela\",\"Quezon City\",\"Caloocan\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 10, 'applicants/698e992bbb3a6_1770953003.jpg', 'video/698e992bc7543_1770953003.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:23:23', '2026-02-13 03:23:23', NULL),
(29, 'Charmaine Rose', 'Dimapilis', 'Jimenez', '', '09273659012', '09190345711', 'charmainejimenez22@example.com', '2004-02-04', '1789 Camarin Road, Brgy. 178, Camarin, Caloocan City', '{\"elementary\":{\"school\":\"Camarin Elementary School\",\"year\":\"2010\\u20132016\"},\"highschool\":{\"school\":\"Caloocan High School\",\"year\":\"2016\\u20132020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Clean &amp;amp; Care Services\",\"years\":\"2023\\u20132024\",\"role\":\"Housemaid\",\"location\":\"Caloocan\"}]', '[\"Caloocan\",\"QC\",\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 1, 'applicants/698e9a253267f_1770953253.jpg', 'video/698e9a253ea3a_1770953253.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:27:33', '2026-02-18 05:47:15', NULL),
(30, 'Lorna Fe', 'Bagtas', 'Malabanan', '', '09172349850', '09351867209', 'lornamalabanan39@example.com', '1986-04-10', '443 P. Burgos St., Brgy. Poblacion, Makati City', '{\"elementary\":{\"school\":\"Poblacion Elementary School\",\"year\":\"1992\\u20131998\"},\"highschool\":{\"school\":\"Makati High School\",\"year\":\"1998\\u20132002\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Makati HomeCare\",\"years\":\"2020\\u20132024\",\"role\":\"Housemaid\",\"location\":\"Bangkal Makati\"},{\"company\":\"Taguig Helpers Agency\",\"years\":\"2016\\u20132020\",\"role\":\"Cook\",\"location\":\"Makati\"}]', '[\"Makati\",\"Taguig\",\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9adbdc727_1770953435.jpg', 'video/698e9adbe929e_1770953435.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:30:35', '2026-02-18 06:07:10', NULL),
(31, 'Lea Catherine', 'Fernandez', 'Rivera', '', '09190456722', '09175346098', 'learivera27@example.com', '1998-12-02', '300 San Guillermo St., Brgy. Hulo, Mandaluyong City', '{\"elementary\":{\"school\":\"Hulo Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Mandaluyong High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"MetroClean\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Ortigas\"}]', '[\"Mandaluyong\",\"Pasig\",\"QC\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9b841dc91_1770953604.jpg', 'video/698e9b8425cda_1770953604.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:33:24', '2026-02-18 05:47:09', NULL),
(32, 'Denise Grace', 'Angeles', 'Mendiola', '', '09956873410', '09359872140', 'denisemendiola33@example.com', '1992-08-19', '5124 A. Bonifacio St., Brgy. Western Bicutan, Taguig City', '{\"elementary\":{\"school\":\"Western Bicutan Elementary School\",\"year\":\"1999\\u20132005\"},\"highschool\":{\"school\":\"Taguig National High School\",\"year\":\"2005\\u20132009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Taguig Home Services\",\"years\":\"2019\\u20132024\",\"role\":\"Housemaid\\/Caregiver\",\"location\":\"BGC\"},{\"company\":\"UrbanClean Agency\",\"years\":\"2016\\u20132019\",\"role\":\"Cleaner\",\"location\":\"Pasay\"}]', '[\"Taguig\",\"Pasay\",\"Makati\"]', '[\"Filipino\",\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9c75149ea_1770953845.jpg', 'video/698e9c751c0ff_1770953845.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 13, '2026-02-13 03:37:25', '2026-02-18 06:13:07', NULL),
(33, 'Ava', 'Marie', 'Thompson', '', '09999999999', '09999999999', 'email@gmail.com', '1998-02-19', '1234 address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 3, 'applicants/698e8d360baa7_1770949942.jpg', 'video/698e8d3610907_1770949942.mp4', 'file', 'file', '', NULL, NULL, 'approved', 5, '2026-02-13 02:32:22', '2026-02-19 06:56:21', NULL),
(34, 'Sophia', 'Claire', 'Ramirez', '', '09999999999', '09999999999', 'email@gmail.com', '1990-11-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Mandaluyong\",\"makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e8df92b357_1770950137.jpg', 'video/698e8df92cdd7_1770950137.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:35:37', '2026-02-13 02:35:37', NULL),
(35, 'Isabella', 'Grace', 'Mitchell', '', '09999999999', '09999999999', 'email@gmail.com', '2000-08-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"IT\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"Mandaluyong\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Technical-Vocational / TESDA Graduate', 2, 'applicants/698e8e832247e_1770950275.jpg', 'video/698e8e83233c5_1770950275.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:37:55', '2026-02-13 02:37:55', NULL),
(36, 'Emily', 'Rose', 'Johnson', '', '09999999999', '09999999999', 'email@gmail.com', '1960-02-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"paranaque\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning &amp; Housekeeping (General)\",\"Cooking &amp; Food Service\",\"Pet &amp; Outdoor Maintenance\"]', 'Full Time', 'Senior High School Graduate (K-12 Curriculum)', 2, 'applicants/698e8f3716c79_1770950455.jpg', 'video/698e8f231b3bd_1770950435.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:40:35', '2026-02-13 02:40:55', NULL),
(37, 'Mia', 'Elizabeth', 'Carter', '', '09999999999', '09999999999', 'email@gmail.com', '2001-11-02', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e8fa38a07a_1770950563.jpg', 'video/698e8fa38af70_1770950563.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:42:43', '2026-02-13 02:42:43', NULL),
(38, 'Olivia', 'Jane', 'Peterson', '', '09999999999', '09999999999', 'email@gmail.com', '1990-03-06', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e901853864_1770950680.jpg', 'video/698e9018547b8_1770950680.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:44:40', '2026-02-13 02:44:40', NULL),
(39, 'Chloe', 'Ann', 'Sullivan', '', '09999999999', '09999999999', 'email@gmail.com', '1989-01-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Taguig\",\"BGC\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Level (Attended High School)', 2, 'applicants/698e90a11d029_1770950817.jpg', 'video/698e90a11f3d4_1770950817.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:46:57', '2026-02-13 02:46:57', NULL),
(40, 'Hannah', 'Louise', 'Parker', '', '09999999999', '09999999999', 'email@gmail.com', '1999-08-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', 'Elementary Graduate', 3, 'applicants/698e910d1e60e_1770950925.jpg', 'video/698e910d1fc78_1770950925.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:48:45', '2026-02-13 02:48:45', NULL),
(41, 'Abigail', 'Nicole', 'Sanders', '', '09999999999', '09999999999', 'email@gmail.com', '2000-11-08', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/698e918576116_1770951045.jpg', 'video/698e918577686_1770951045.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:50:45', '2026-02-13 02:50:45', NULL),
(42, 'Natalie', 'Faith', 'Rogers', '', '09999999999', '09999999999', 'email@gmail.com', '1999-01-23', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e9220edd8f_1770951200.jpg', 'video/698e9220ee585_1770951200.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:53:20', '2026-02-13 02:53:20', NULL),
(43, 'Ryzza Mae', 'B.', 'Diaz', '', '09123123718', '09817238712', 'renzdiaz.contact@gmail.com', '2026-02-25', '87412 ajllmdawudawdawdasdawds', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2026 - 2028\",\"role\":\"Housemaid\",\"location\":\"Ermita Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati\"}]', '[\"Makati City\",\"Mandaluyong CIty\"]', '[]', '[\"Cleaning & Housekeeping (General)\",\"Childcare & Maternity (Yaya)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/6996b581e440f_1771484545.jpg', 'video/6996b581ecad4_1771484545.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 12, '2026-02-19 07:02:25', '2026-02-19 07:02:57', NULL);

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
(161, 23, 'brgy_clearance', 'documents/698e9436dda8e_1770951734.jpg', '2026-02-13 03:02:14'),
(162, 23, 'birth_certificate', 'documents/698e9436de60c_1770951734.jpg', '2026-02-13 03:02:14'),
(163, 23, 'sss', 'documents/698e9436df31c_1770951734.jpg', '2026-02-13 03:02:14'),
(164, 23, 'pagibig', 'documents/698e9436e0517_1770951734.jpg', '2026-02-13 03:02:14'),
(165, 23, 'nbi', 'documents/698e9436e16f0_1770951734.jpg', '2026-02-13 03:02:14'),
(166, 23, 'police_clearance', 'documents/698e9436e223c_1770951734.jpg', '2026-02-13 03:02:14'),
(167, 23, 'tin_id', 'documents/698e9436e2e94_1770951734.jpg', '2026-02-13 03:02:14'),
(168, 23, 'passport', 'documents/698e9436e3ade_1770951734.jpg', '2026-02-13 03:02:14'),
(169, 24, 'brgy_clearance', 'documents/698e95780d9c3_1770952056.jpg', '2026-02-13 03:07:36'),
(170, 24, 'birth_certificate', 'documents/698e95780f5d0_1770952056.png', '2026-02-13 03:07:36'),
(171, 24, 'sss', 'documents/698e957810ec3_1770952056.jpg', '2026-02-13 03:07:36'),
(172, 24, 'pagibig', 'documents/698e957812bfd_1770952056.jpg', '2026-02-13 03:07:36'),
(173, 24, 'nbi', 'documents/698e957814ca4_1770952056.jpg', '2026-02-13 03:07:36'),
(174, 24, 'police_clearance', 'documents/698e957816f42_1770952056.png', '2026-02-13 03:07:36'),
(175, 24, 'tin_id', 'documents/698e9578189a4_1770952056.png', '2026-02-13 03:07:36'),
(176, 24, 'passport', 'documents/698e95781a55f_1770952056.jpg', '2026-02-13 03:07:36'),
(177, 25, 'brgy_clearance', 'documents/698e969891f78_1770952344.jpg', '2026-02-13 03:12:24'),
(178, 25, 'birth_certificate', 'documents/698e969893293_1770952344.png', '2026-02-13 03:12:24'),
(179, 25, 'sss', 'documents/698e969893eac_1770952344.jpg', '2026-02-13 03:12:24'),
(180, 25, 'pagibig', 'documents/698e969894b88_1770952344.png', '2026-02-13 03:12:24'),
(181, 25, 'nbi', 'documents/698e969895f80_1770952344.jpg', '2026-02-13 03:12:24'),
(182, 25, 'police_clearance', 'documents/698e969896ba6_1770952344.jpg', '2026-02-13 03:12:24'),
(183, 25, 'tin_id', 'documents/698e969897a2d_1770952344.png', '2026-02-13 03:12:24'),
(184, 25, 'passport', 'documents/698e9698985d9_1770952344.jpg', '2026-02-13 03:12:24'),
(185, 26, 'brgy_clearance', 'documents/698e977bb470b_1770952571.jpg', '2026-02-13 03:16:11'),
(186, 26, 'birth_certificate', 'documents/698e977bb640b_1770952571.png', '2026-02-13 03:16:11'),
(187, 26, 'sss', 'documents/698e977bb7c1f_1770952571.jpg', '2026-02-13 03:16:11'),
(188, 26, 'pagibig', 'documents/698e977bb94d2_1770952571.png', '2026-02-13 03:16:11'),
(189, 26, 'nbi', 'documents/698e977bbae56_1770952571.jpg', '2026-02-13 03:16:11'),
(190, 26, 'police_clearance', 'documents/698e977bbcc0c_1770952571.png', '2026-02-13 03:16:11'),
(191, 26, 'tin_id', 'documents/698e977bbe689_1770952571.jpg', '2026-02-13 03:16:11'),
(192, 26, 'passport', 'documents/698e977bc05cf_1770952571.png', '2026-02-13 03:16:11'),
(193, 27, 'brgy_clearance', 'documents/698e984ded5a4_1770952781.jpg', '2026-02-13 03:19:41'),
(194, 27, 'birth_certificate', 'documents/698e984deea8d_1770952781.jpg', '2026-02-13 03:19:41'),
(195, 27, 'sss', 'documents/698e984defad2_1770952781.jpg', '2026-02-13 03:19:41'),
(196, 27, 'pagibig', 'documents/698e984df08c3_1770952781.jpg', '2026-02-13 03:19:41'),
(197, 27, 'nbi', 'documents/698e984df1697_1770952781.jpg', '2026-02-13 03:19:41'),
(198, 27, 'police_clearance', 'documents/698e984df22cf_1770952781.jpg', '2026-02-13 03:19:41'),
(199, 27, 'tin_id', 'documents/698e984df3161_1770952781.jpg', '2026-02-13 03:19:41'),
(200, 27, 'passport', 'documents/698e984df3d55_1770952781.jpg', '2026-02-13 03:19:41'),
(201, 28, 'brgy_clearance', 'documents/698e992bc0193_1770953003.jpg', '2026-02-13 03:23:23'),
(202, 28, 'birth_certificate', 'documents/698e992bc1089_1770953003.jpg', '2026-02-13 03:23:23'),
(203, 28, 'sss', 'documents/698e992bc1ce0_1770953003.jpg', '2026-02-13 03:23:23'),
(204, 28, 'pagibig', 'documents/698e992bc2852_1770953003.jpg', '2026-02-13 03:23:23'),
(205, 28, 'nbi', 'documents/698e992bc3848_1770953003.jpg', '2026-02-13 03:23:23'),
(206, 28, 'police_clearance', 'documents/698e992bc466e_1770953003.png', '2026-02-13 03:23:23'),
(207, 28, 'tin_id', 'documents/698e992bc530f_1770953003.png', '2026-02-13 03:23:23'),
(208, 28, 'passport', 'documents/698e992bc663f_1770953003.png', '2026-02-13 03:23:23'),
(209, 29, 'brgy_clearance', 'documents/698e9a25374dd_1770953253.jpg', '2026-02-13 03:27:33'),
(210, 29, 'birth_certificate', 'documents/698e9a253898a_1770953253.jpg', '2026-02-13 03:27:33'),
(211, 29, 'sss', 'documents/698e9a253950c_1770953253.jpg', '2026-02-13 03:27:33'),
(212, 29, 'pagibig', 'documents/698e9a253a1c1_1770953253.jpg', '2026-02-13 03:27:33'),
(213, 29, 'nbi', 'documents/698e9a253ad25_1770953253.jpg', '2026-02-13 03:27:33'),
(214, 29, 'police_clearance', 'documents/698e9a253b7d7_1770953253.jpg', '2026-02-13 03:27:33'),
(215, 29, 'tin_id', 'documents/698e9a253d071_1770953253.png', '2026-02-13 03:27:33'),
(216, 29, 'passport', 'documents/698e9a253dc2c_1770953253.jpg', '2026-02-13 03:27:33'),
(217, 30, 'brgy_clearance', 'documents/698e9adbe1a60_1770953435.jpg', '2026-02-13 03:30:35'),
(218, 30, 'birth_certificate', 'documents/698e9adbe2e7c_1770953435.jpg', '2026-02-13 03:30:35'),
(219, 30, 'sss', 'documents/698e9adbe3bcc_1770953435.jpg', '2026-02-13 03:30:35'),
(220, 30, 'pagibig', 'documents/698e9adbe48ca_1770953435.png', '2026-02-13 03:30:35'),
(221, 30, 'nbi', 'documents/698e9adbe58f2_1770953435.jpg', '2026-02-13 03:30:35'),
(222, 30, 'police_clearance', 'documents/698e9adbe65f2_1770953435.jpg', '2026-02-13 03:30:35'),
(223, 30, 'tin_id', 'documents/698e9adbe7b3f_1770953435.png', '2026-02-13 03:30:35'),
(224, 30, 'passport', 'documents/698e9adbe85b2_1770953435.jpg', '2026-02-13 03:30:35'),
(225, 31, 'brgy_clearance', 'documents/698e9b841eefd_1770953604.jpg', '2026-02-13 03:33:24'),
(226, 31, 'birth_certificate', 'documents/698e9b841ffe8_1770953604.jpg', '2026-02-13 03:33:24'),
(227, 31, 'sss', 'documents/698e9b8420b40_1770953604.jpg', '2026-02-13 03:33:24'),
(228, 31, 'pagibig', 'documents/698e9b8421c59_1770953604.jpg', '2026-02-13 03:33:24'),
(229, 31, 'nbi', 'documents/698e9b84227ce_1770953604.jpg', '2026-02-13 03:33:24'),
(230, 31, 'police_clearance', 'documents/698e9b8423305_1770953604.jpg', '2026-02-13 03:33:24'),
(231, 31, 'tin_id', 'documents/698e9b842427f_1770953604.png', '2026-02-13 03:33:24'),
(232, 31, 'passport', 'documents/698e9b8424f63_1770953604.png', '2026-02-13 03:33:24'),
(233, 32, 'brgy_clearance', 'documents/698e9c7515747_1770953845.jpg', '2026-02-13 03:37:25'),
(234, 32, 'birth_certificate', 'documents/698e9c7516293_1770953845.jpg', '2026-02-13 03:37:25'),
(235, 32, 'sss', 'documents/698e9c7516d92_1770953845.jpg', '2026-02-13 03:37:25'),
(236, 32, 'pagibig', 'documents/698e9c7517976_1770953845.png', '2026-02-13 03:37:25'),
(237, 32, 'nbi', 'documents/698e9c75189d6_1770953845.jpg', '2026-02-13 03:37:25'),
(238, 32, 'police_clearance', 'documents/698e9c75193e5_1770953845.jpg', '2026-02-13 03:37:25'),
(239, 32, 'tin_id', 'documents/698e9c751a0ec_1770953845.png', '2026-02-13 03:37:25'),
(240, 32, 'passport', 'documents/698e9c751b260_1770953845.jpg', '2026-02-13 03:37:25'),
(241, 43, 'brgy_clearance', 'documents/6996b581e62a7_1771484545.jpg', '2026-02-19 07:02:25'),
(242, 43, 'birth_certificate', 'documents/6996b581e6bbb_1771484545.jpg', '2026-02-19 07:02:25'),
(243, 43, 'sss', 'documents/6996b581e77ac_1771484545.png', '2026-02-19 07:02:25'),
(244, 43, 'pagibig', 'documents/6996b581e84b4_1771484545.jpg', '2026-02-19 07:02:25'),
(245, 43, 'nbi', 'documents/6996b581e9494_1771484545.jpg', '2026-02-19 07:02:25'),
(246, 43, 'police_clearance', 'documents/6996b581ea46b_1771484545.jpg', '2026-02-19 07:02:25'),
(247, 43, 'tin_id', 'documents/6996b581eb076_1771484545.jpg', '2026-02-19 07:02:25'),
(248, 43, 'passport', 'documents/6996b581ebc85_1771484545.jpg', '2026-02-19 07:02:25');

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
(10, 30, 'on_process', 'approved', 'testing', 4, '2026-02-18 13:25:59'),
(11, 32, 'on_process', 'approved', 'asdasdasd', 4, '2026-02-18 13:26:10'),
(12, 31, 'on_process', 'pending', 'no client', 4, '2026-02-18 13:47:09'),
(13, 29, 'on_process', 'pending', 'no client', 4, '2026-02-18 13:47:15'),
(14, 32, 'on_process', 'pending', 'hephep test', 4, '2026-02-18 14:13:07'),
(15, 33, 'on_process', 'approved', 'Hired', 12, '2026-02-19 14:56:21');

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
(9, 33, '[\"Laundry & Clothing Care\",\"Pet & Outdoor Maintenance\",\"Cooking & Food Service\"]', 'Office Visit', '2026-02-27', '10:00:00', 'Renz Roann', 'Berto', 'Diaz', '09817238718', 'renzdiaz.contact@gmai.com', '2461 Princess Floresca St. Pandacan, Manila', 'submitted', '2026-02-19 06:43:21', '2026-02-19 06:43:21');

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
(28, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:43:01', '2026-02-11 14:43:20'),
(29, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:43:30', '2026-02-11 14:48:47'),
(30, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 14:52:50', '2026-02-11 15:28:02'),
(31, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 15:28:20', NULL),
(32, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 08:34:04', '2026-02-12 09:24:43'),
(33, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 09:24:47', '2026-02-12 10:27:01'),
(34, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 10:27:09', '2026-02-12 10:41:35'),
(35, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 10:41:56', NULL),
(36, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 10:49:15', NULL),
(37, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 11:37:58', NULL),
(38, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 13:34:28', NULL),
(39, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-12 15:00:04', NULL),
(40, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 08:30:50', '2026-02-13 09:19:07'),
(41, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:19:30', '2026-02-13 09:19:39'),
(42, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:19:45', '2026-02-13 09:20:33'),
(43, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:20:42', '2026-02-13 09:24:05'),
(44, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:24:17', '2026-02-13 09:53:18'),
(45, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:53:24', '2026-02-13 13:24:00'),
(46, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 13:24:10', NULL),
(47, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 13:11:56', NULL),
(48, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 16:48:20', NULL),
(49, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 09:36:36', '2026-02-19 09:39:53'),
(50, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 09:40:05', '2026-02-19 09:55:50'),
(51, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 10:55:23', '2026-02-19 10:57:48'),
(52, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 10:58:01', '2026-02-19 11:56:26'),
(53, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 11:56:45', '2026-02-19 13:10:05'),
(54, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:10:21', '2026-02-19 13:10:45'),
(55, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:11:02', '2026-02-19 13:14:49'),
(56, 16, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:14:55', '2026-02-19 13:46:11'),
(57, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 14:37:06', '2026-02-19 15:06:51'),
(58, 13, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 15:07:00', '2026-02-19 15:07:45'),
(59, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 15:07:52', NULL);

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
  ADD KEY `idx_admin_users_agency` (`agency`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=341;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `client_bookings`
--
ALTER TABLE `client_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

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
