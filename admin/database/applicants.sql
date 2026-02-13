-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 13, 2026 at 04:44 AM
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
(23, 'Ava', 'Marie', 'Thompson', '', '09999999999', '09999999999', 'email@gmail.com', '1998-02-19', '1234 address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 3, 'applicants/698e8d360baa7_1770949942.jpg', 'video/698e8d3610907_1770949942.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:32:22', '2026-02-13 02:32:22', NULL),
(24, 'Sophia', 'Claire', 'Ramirez', '', '09999999999', '09999999999', 'email@gmail.com', '1990-11-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Mandaluyong\",\"makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e8df92b357_1770950137.jpg', 'video/698e8df92cdd7_1770950137.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:35:37', '2026-02-13 02:35:37', NULL),
(25, 'Isabella', 'Grace', 'Mitchell', '', '09999999999', '09999999999', 'email@gmail.com', '2000-08-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"IT\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"Mandaluyong\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Technical-Vocational / TESDA Graduate', 2, 'applicants/698e8e832247e_1770950275.jpg', 'video/698e8e83233c5_1770950275.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:37:55', '2026-02-13 02:37:55', NULL),
(26, 'Emily', 'Rose', 'Johnson', '', '09999999999', '09999999999', 'email@gmail.com', '1960-02-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"paranaque\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning & Housekeeping (General)\",\"Cooking & Food Service\",\"Pet & Outdoor Maintenance\"]', 'Full Time', 'Senior High School Graduate (K-12 Curriculum)', 2, 'applicants/698e8f3716c79_1770950455.jpg', 'video/698e8f231b3bd_1770950435.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:40:35', '2026-02-13 02:40:55', NULL),
(27, 'Mia', 'Elizabeth', 'Carter', '', '09999999999', '09999999999', 'email@gmail.com', '2001-11-02', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e8fa38a07a_1770950563.jpg', 'video/698e8fa38af70_1770950563.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:42:43', '2026-02-13 02:42:43', NULL),
(28, 'Olivia', 'Jane', 'Peterson', '', '09999999999', '09999999999', 'email@gmail.com', '1990-03-06', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e901853864_1770950680.jpg', 'video/698e9018547b8_1770950680.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:44:40', '2026-02-13 02:44:40', NULL),
(29, 'Chloe', 'Ann', 'Sullivan', '', '09999999999', '09999999999', 'email@gmail.com', '1989-01-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Taguig\",\"BGC\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Secondary Level (Attended High School)', 2, 'applicants/698e90a11d029_1770950817.jpg', 'video/698e90a11f3d4_1770950817.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:46:57', '2026-02-13 02:46:57', NULL),
(30, 'Hannah', 'Louise', 'Parker', '', '09999999999', '09999999999', 'email@gmail.com', '1999-08-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', 'Elementary Graduate', 3, 'applicants/698e910d1e60e_1770950925.jpg', 'video/698e910d1fc78_1770950925.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:48:45', '2026-02-13 02:48:45', NULL),
(31, 'Abigail', 'Nicole', 'Sanders', '', '09999999999', '09999999999', 'email@gmail.com', '2000-11-08', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/698e918576116_1770951045.jpg', 'video/698e918577686_1770951045.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:50:45', '2026-02-13 02:50:45', NULL),
(32, 'Natalie', 'Faith', 'Rogers', '', '09999999999', '09999999999', 'email@gmail.com', '1999-01-23', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e9220edd8f_1770951200.jpg', 'video/698e9220ee585_1770951200.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:53:20', '2026-02-13 02:53:20', NULL);

--
-- Indexes for dumped tables
--

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
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `fk_applicants_created_by` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
