-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 07:29 AM
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `business_unit_id` int(10) UNSIGNED DEFAULT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `avatar`, `role`, `agency`, `business_unit_id`, `branch_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 'renzadmin', 'renzdiaz.contact@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$US9RRDloSHR3MGlzeUdGdw$cjNozvyDewv1phUaRVyn/6zcDKOdoSGJp1fBt5MABFE', 'Renz Diaz', 'avatars/699556f4c657c_1771394804.jpg', 'super_admin', NULL, NULL, NULL, 'active', '2026-02-07 10:20:55', '2026-02-18 06:06:44'),
(5, 'elliadmin', 'elli@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cFZvOHZCckJkcDd4a0Y1cA$d+28H23RKZagXG81OSdY8xWa8x2KNSFuHip8xsxI2No', 'John Ellijah', NULL, 'super_admin', NULL, NULL, NULL, 'active', '2026-02-07 10:21:28', '2026-02-26 08:05:17'),
(6, 'andreiadmin', 'andrei@gmail.com', '$2y$10$ROQGHUJso58ON6NCsv2PRO14x3Nviq3fZrkEU8KLne6BTEbVuhSq2', 'Andrei Javillo', NULL, 'super_admin', NULL, NULL, NULL, 'active', '2026-02-07 10:22:05', '2026-02-07 10:22:05'),
(7, 'ralphadmin', 'ralph@gmail.com', '$2y$10$MUi6.7QJykPG48jx9e8lLu2V72JRHYu91.aRd5LFviHcJokQfvaf2', 'Ralph Justine Gallentes', NULL, 'super_admin', NULL, NULL, NULL, 'active', '2026-02-10 00:32:00', '2026-02-10 00:32:00'),
(8, 'cabritoadmin', 'cabs@gmail.com', '$2y$10$AbWEDXv5fqBAkhk1quS.7.eJKD2uyUyenhinmN906bbJlePsxOlSq', 'John Adrian Cabrito', NULL, 'super_admin', NULL, NULL, NULL, 'active', '2026-02-10 00:32:53', '2026-02-10 00:32:53'),
(12, 'jmpogi', 'jm@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QkMvd1FUc2Q0bnBjWHB0Uw$kltUwYy7N9gm+yGcuxlWqQFXnwD/EPRKRexQ1sDBYQM', 'John Michael Masmela', 'avatars/699c53d80ff2a_1771852760.png', 'admin', NULL, NULL, NULL, 'active', '2026-02-12 02:33:42', '2026-02-23 13:19:20'),
(19, 'SMCsuper', 'ocampojohn13@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SVhESWoweG4xVURpLk9ZNg$3nlUFoWx4wueaYi52OP0zi/sXT13cP3UWzZRtZIAjvg', 'super', NULL, 'super_admin', NULL, NULL, NULL, 'active', '2026-02-26 06:09:06', '2026-02-26 06:09:06'),
(24, 'smc001', 'angel@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Qks5MWRDMWx0UVg0L0lxRw$GVcSaydU57qZskXhBZLGq3WqJOglig4CW1r2C2IbHRE', 'Angel Lazaro', NULL, 'employee', 'smc', NULL, NULL, 'active', '2026-03-14 06:56:54', '2026-03-14 06:56:54'),
(25, 'smc002', 'jasz@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$VlpLRndBRmRXQTJnWk10Lw$WsqlJkKMCDGdaUD5pUKjv9OSYmzqJDSG3X4sbWpigFs', 'Jasz', NULL, 'employee', 'smc', NULL, NULL, 'active', '2026-03-14 06:57:40', '2026-03-14 06:57:40'),
(26, 'csnk001', 'liza@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$aWpHLlZKQUZIb2gudWMxZQ$eiGQdfEiYcDIgVbdRaLn7ABa6vR3zkR4+JXNlmbv838', 'Liza Belarde', NULL, 'employee', 'csnk', 1, NULL, 'active', '2026-03-14 06:59:56', '2026-03-14 07:14:19'),
(27, 'Mindoro001', 'mindoro@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$OGFJRUNReHM2bGdqdm5XYQ$Rbjup6KAa79zYbuPJNAycDI6yRlkZM/on5uaSmmHmwY', 'Mindoro Branch', NULL, 'employee', 'csnk', 5, NULL, 'active', '2026-03-20 00:50:08', '2026-03-20 00:50:08'),
(28, 'Palawan001', 'palawan@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cVdPZkpsRUVnR1d0RlZ1Vg$e3qrcyshlw67NCxVdf38XbUxtS5rR6e6FBclUVbPiIs', 'Palawan Branch', NULL, 'employee', 'csnk', 6, NULL, 'active', '2026-03-20 00:51:04', '2026-03-20 00:51:04'),
(29, 'Marinduque001', 'marinduque@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$c0lxOWEzbDE4c1JObGE1ag$9PcPPrSMxJA0a+KgiXR6Mwy81dWGWbxtfU+G0hJL4x4', 'Marinduque Branch', NULL, 'employee', 'csnk', 7, NULL, 'active', '2026-03-20 00:51:39', '2026-03-20 00:51:39'),
(31, 'Batangas001', 'batangas@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SVg3T1Blb0t0MVhzSWM2NQ$IXmnVEVslSmf1uopOMrzV8/QnHRX80ET/Bpf3QyJutg', 'Batangas Branch', NULL, 'employee', 'csnk', 4, NULL, 'active', '2026-03-20 00:53:47', '2026-03-20 00:53:47'),
(32, 'Gensan001', 'gensan@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$RDd0VDZEdW13MzJWTFo4Tw$wXPoAeFbDcVQECvIoqnW0FzZ5LIjsShehZlmp1KuLsQ', 'Gensan Branch', NULL, 'employee', 'csnk', 8, NULL, 'active', '2026-03-20 00:54:24', '2026-03-20 00:54:24'),
(33, 'Iloilo001', 'iloilo@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$NHkuUXR2ZUlCdS81ZE4ybQ$5UBLf6tHZ6RRbycbKtKRlZEIgvCanW8SgC96dlGp5z8', 'Iloilo Branch', NULL, 'employee', 'csnk', 9, NULL, 'active', '2026-03-20 00:55:03', '2026-03-20 00:55:03'),
(34, 'Dinagat001', 'dinagat@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$ZDlGTGFkVHE1UVNaenkxdw$tQplfEwi2GdmkTplkEErYLcos1J6HRMT0DUmifw4vVw', 'Dinagat Branch', NULL, 'employee', 'csnk', 10, NULL, 'active', '2026-03-20 00:55:33', '2026-03-20 00:55:33'),
(35, 'Cebu001', 'cebu@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$ZXI4TElUQXJpMm02OVpIbg$O6mx58OE2GBJrahXYdj7xf3bl01qVuI024iXbMFw4nc', 'Cebu Branch', NULL, 'employee', 'csnk', 11, NULL, 'active', '2026-03-20 00:56:06', '2026-03-20 00:56:06'),
(36, 'Nuevaecija001', 'nuevaecija@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$Yi92emI0RzJiVkxjZ29Heg$rYGwDHzjpSBepPrv42/elW/xONas7YS/VF8h0okHMFQ', 'Nueva Ecija Bnrach', NULL, 'employee', 'csnk', 12, NULL, 'active', '2026-03-20 00:56:57', '2026-03-21 01:00:00'),
(37, 'Bacolod001', 'bacolod@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$YzNkcjR0QUJsRHM3Wi5LMQ$n11uxTJ6y16N4pwk+US3xTpnBG1wEEN3kOitqw0EX10', 'Bacolod Branch', NULL, 'employee', 'csnk', 13, NULL, 'active', '2026-03-20 00:57:50', '2026-03-21 01:16:48');

-- --------------------------------------------------------

--
-- Table structure for table `agencies`
--

CREATE TABLE `agencies` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `branch_id` bigint(20) UNSIGNED DEFAULT NULL,
  `country_id` int(11) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `phone_number` varchar(20) NOT NULL,
  `alt_phone_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `date_of_birth` date NOT NULL,
  `address` text NOT NULL,
  `educational_attainment` longtext DEFAULT NULL,
  `work_history` longtext DEFAULT NULL,
  `preferred_location` longtext DEFAULT NULL,
  `languages` longtext DEFAULT NULL,
  `specialization_skills` longtext DEFAULT NULL,
  `employment_type` enum('Full Time','Part Time') DEFAULT NULL,
  `daily_rate` decimal(10,2) DEFAULT NULL,
  `education_level` enum('Elementary Graduate','Secondary Level (Attended High School)','Secondary Graduate (Junior High School / Old Curriculum)','Senior High School Graduate (K-12 Curriculum)','Technical-Vocational / TESDA Graduate','Tertiary Level (College Undergraduate)','Tertiary Graduate (Bachelor’s Degree)') DEFAULT NULL,
  `years_experience` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `picture` varchar(255) DEFAULT NULL,
  `video_url` varchar(1024) DEFAULT NULL,
  `video_provider` enum('youtube','vimeo','file','other') DEFAULT NULL,
  `video_type` enum('iframe','file') NOT NULL DEFAULT 'iframe',
  `video_title` varchar(200) DEFAULT NULL,
  `video_thumbnail_url` varchar(1024) DEFAULT NULL,
  `video_duration_seconds` int(10) UNSIGNED DEFAULT NULL,
  `status` enum('pending','on_process','approved','on_hold','deleted') NOT NULL DEFAULT 'pending',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `applicants`
--
DELIMITER $$
CREATE TRIGGER `applicants_ad_recycle` AFTER DELETE ON `applicants` FOR EACH ROW BEGIN
  /*
    If dependent rows were cascaded, the id is now safe to recycle.
    IGNORE prevents duplicate key if same id somehow re-queued.
  */
  INSERT IGNORE INTO recycled_ids (table_name, id)
  VALUES ('applicants', OLD.id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `applicants_bi_reuse` BEFORE INSERT ON `applicants` FOR EACH ROW BEGIN
  DECLARE v_reuse BIGINT UNSIGNED;

  /* Only apply if no explicit id provided (NULL or 0) */
  IF (NEW.id IS NULL OR NEW.id = 0) THEN
    /*
      Pick the smallest recycled id for this table.
      NOTE: Under concurrency this is "good enough" for an admin system.
    */
    SELECT ri.id INTO v_reuse
    FROM recycled_ids ri
    WHERE ri.table_name = 'applicants'
    ORDER BY ri.id ASC
    LIMIT 1;

    IF v_reuse IS NOT NULL THEN
      -- Set the recycled id
      SET NEW.id = v_reuse;
      -- Consume it
      DELETE FROM recycled_ids
      WHERE table_name = 'applicants' AND id = v_reuse;
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_documents`
--

CREATE TABLE `applicant_documents` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `document_type_id` int(10) UNSIGNED DEFAULT NULL,
  `document_type` enum('brgy_clearance','birth_certificate','sss','pagibig','nbi','police_clearance','tin_id','passport') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applicant_documents`
--

INSERT INTO `applicant_documents` (`id`, `applicant_id`, `business_unit_id`, `document_type_id`, `document_type`, `file_path`, `uploaded_at`) VALUES
(186, 26, 1, 2, 'birth_certificate', 'documents/698e977bb640b_1770952571.png', '2026-02-13 03:16:11'),
(187, 26, 1, 3, 'sss', 'documents/698e977bb7c1f_1770952571.jpg', '2026-02-13 03:16:11'),
(188, 26, 1, 4, 'pagibig', 'documents/698e977bb94d2_1770952571.png', '2026-02-13 03:16:11'),
(189, 26, 1, 5, 'nbi', 'documents/698e977bbae56_1770952571.jpg', '2026-02-13 03:16:11'),
(190, 26, 1, 6, 'police_clearance', 'documents/698e977bbcc0c_1770952571.png', '2026-02-13 03:16:11'),
(191, 26, 1, 7, 'tin_id', 'documents/698e977bbe689_1770952571.jpg', '2026-02-13 03:16:11'),
(192, 26, 1, 8, 'passport', 'documents/698e977bc05cf_1770952571.png', '2026-02-13 03:16:11'),
(193, 27, 1, 1, 'brgy_clearance', 'documents/698e984ded5a4_1770952781.jpg', '2026-02-13 03:19:41'),
(194, 27, 1, 2, 'birth_certificate', 'documents/698e984deea8d_1770952781.jpg', '2026-02-13 03:19:41'),
(195, 27, 1, 3, 'sss', 'documents/698e984defad2_1770952781.jpg', '2026-02-13 03:19:41'),
(196, 27, 1, 4, 'pagibig', 'documents/698e984df08c3_1770952781.jpg', '2026-02-13 03:19:41'),
(197, 27, 1, 5, 'nbi', 'documents/698e984df1697_1770952781.jpg', '2026-02-13 03:19:41'),
(198, 27, 1, 6, 'police_clearance', 'documents/698e984df22cf_1770952781.jpg', '2026-02-13 03:19:41'),
(199, 27, 1, 7, 'tin_id', 'documents/698e984df3161_1770952781.jpg', '2026-02-13 03:19:41'),
(200, 27, 1, 8, 'passport', 'documents/698e984df3d55_1770952781.jpg', '2026-02-13 03:19:41'),
(201, 28, 1, 1, 'brgy_clearance', 'documents/698e992bc0193_1770953003.jpg', '2026-02-13 03:23:23'),
(202, 28, 1, 2, 'birth_certificate', 'documents/698e992bc1089_1770953003.jpg', '2026-02-13 03:23:23'),
(203, 28, 1, 3, 'sss', 'documents/698e992bc1ce0_1770953003.jpg', '2026-02-13 03:23:23'),
(204, 28, 1, 4, 'pagibig', 'documents/698e992bc2852_1770953003.jpg', '2026-02-13 03:23:23'),
(205, 28, 1, 5, 'nbi', 'documents/698e992bc3848_1770953003.jpg', '2026-02-13 03:23:23'),
(206, 28, 1, 6, 'police_clearance', 'documents/698e992bc466e_1770953003.png', '2026-02-13 03:23:23'),
(207, 28, 1, 7, 'tin_id', 'documents/698e992bc530f_1770953003.png', '2026-02-13 03:23:23'),
(208, 28, 1, 8, 'passport', 'documents/698e992bc663f_1770953003.png', '2026-02-13 03:23:23'),
(209, 29, 2, 1, 'brgy_clearance', 'documents/698e9a25374dd_1770953253.jpg', '2026-02-13 03:27:33'),
(210, 29, 2, 2, 'birth_certificate', 'documents/698e9a253898a_1770953253.jpg', '2026-02-13 03:27:33'),
(211, 29, 2, 3, 'sss', 'documents/698e9a253950c_1770953253.jpg', '2026-02-13 03:27:33'),
(212, 29, 2, 4, 'pagibig', 'documents/698e9a253a1c1_1770953253.jpg', '2026-02-13 03:27:33'),
(213, 29, 2, 5, 'nbi', 'documents/698e9a253ad25_1770953253.jpg', '2026-02-13 03:27:33'),
(214, 29, 2, 6, 'police_clearance', 'documents/698e9a253b7d7_1770953253.jpg', '2026-02-13 03:27:33'),
(215, 29, 2, 7, 'tin_id', 'documents/698e9a253d071_1770953253.png', '2026-02-13 03:27:33'),
(216, 29, 2, 8, 'passport', 'documents/698e9a253dc2c_1770953253.jpg', '2026-02-13 03:27:33'),
(217, 30, 2, 1, 'brgy_clearance', 'documents/698e9adbe1a60_1770953435.jpg', '2026-02-13 03:30:35'),
(218, 30, 2, 2, 'birth_certificate', 'documents/698e9adbe2e7c_1770953435.jpg', '2026-02-13 03:30:35'),
(219, 30, 2, 3, 'sss', 'documents/698e9adbe3bcc_1770953435.jpg', '2026-02-13 03:30:35'),
(220, 30, 2, 4, 'pagibig', 'documents/698e9adbe48ca_1770953435.png', '2026-02-13 03:30:35'),
(221, 30, 2, 5, 'nbi', 'documents/698e9adbe58f2_1770953435.jpg', '2026-02-13 03:30:35'),
(222, 30, 2, 6, 'police_clearance', 'documents/698e9adbe65f2_1770953435.jpg', '2026-02-13 03:30:35'),
(223, 30, 2, 7, 'tin_id', 'documents/698e9adbe7b3f_1770953435.png', '2026-02-13 03:30:35'),
(224, 30, 2, 8, 'passport', 'documents/698e9adbe85b2_1770953435.jpg', '2026-02-13 03:30:35'),
(225, 31, 2, 1, 'brgy_clearance', 'documents/698e9b841eefd_1770953604.jpg', '2026-02-13 03:33:24'),
(226, 31, 2, 2, 'birth_certificate', 'documents/698e9b841ffe8_1770953604.jpg', '2026-02-13 03:33:24'),
(227, 31, 2, 3, 'sss', 'documents/698e9b8420b40_1770953604.jpg', '2026-02-13 03:33:24'),
(228, 31, 2, 4, 'pagibig', 'documents/698e9b8421c59_1770953604.jpg', '2026-02-13 03:33:24'),
(229, 31, 2, 5, 'nbi', 'documents/698e9b84227ce_1770953604.jpg', '2026-02-13 03:33:24'),
(230, 31, 2, 6, 'police_clearance', 'documents/698e9b8423305_1770953604.jpg', '2026-02-13 03:33:24'),
(231, 31, 2, 7, 'tin_id', 'documents/698e9b842427f_1770953604.png', '2026-02-13 03:33:24'),
(232, 31, 2, 8, 'passport', 'documents/698e9b8424f63_1770953604.png', '2026-02-13 03:33:24'),
(233, 32, 2, 1, 'brgy_clearance', 'documents/698e9c7515747_1770953845.jpg', '2026-02-13 03:37:25'),
(234, 32, 2, 2, 'birth_certificate', 'documents/698e9c7516293_1770953845.jpg', '2026-02-13 03:37:25'),
(235, 32, 2, 3, 'sss', 'documents/698e9c7516d92_1770953845.jpg', '2026-02-13 03:37:25'),
(236, 32, 2, 4, 'pagibig', 'documents/698e9c7517976_1770953845.png', '2026-02-13 03:37:25'),
(237, 32, 2, 5, 'nbi', 'documents/698e9c75189d6_1770953845.jpg', '2026-02-13 03:37:25'),
(238, 32, 2, 6, 'police_clearance', 'documents/698e9c75193e5_1770953845.jpg', '2026-02-13 03:37:25'),
(239, 32, 2, 7, 'tin_id', 'documents/698e9c751a0ec_1770953845.png', '2026-02-13 03:37:25'),
(240, 32, 2, 8, 'passport', 'documents/698e9c751b260_1770953845.jpg', '2026-02-13 03:37:25'),
(241, 43, 1, 1, 'brgy_clearance', 'documents/6996b581e62a7_1771484545.jpg', '2026-02-19 07:02:25'),
(242, 43, 1, 2, 'birth_certificate', 'documents/6996b581e6bbb_1771484545.jpg', '2026-02-19 07:02:25'),
(243, 43, 1, 3, 'sss', 'documents/6996b581e77ac_1771484545.png', '2026-02-19 07:02:25'),
(244, 43, 1, 4, 'pagibig', 'documents/6996b581e84b4_1771484545.jpg', '2026-02-19 07:02:25'),
(245, 43, 1, 5, 'nbi', 'documents/6996b581e9494_1771484545.jpg', '2026-02-19 07:02:25'),
(246, 43, 1, 6, 'police_clearance', 'documents/6996b581ea46b_1771484545.jpg', '2026-02-19 07:02:25'),
(247, 43, 1, 7, 'tin_id', 'documents/6996b581eb076_1771484545.jpg', '2026-02-19 07:02:25'),
(248, 43, 1, 8, 'passport', 'documents/6996b581ebc85_1771484545.jpg', '2026-02-19 07:02:25');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_replacements`
--

CREATE TABLE `applicant_replacements` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED DEFAULT NULL,
  `original_applicant_id` int(10) UNSIGNED NOT NULL,
  `replacement_applicant_id` int(10) UNSIGNED DEFAULT NULL,
  `client_booking_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` enum('AWOL','Client Left','Not Finished Contract','Performance Issue','Other') NOT NULL,
  `report_text` text NOT NULL,
  `attachments_json` longtext DEFAULT NULL,
  `status` enum('selection','assigned','cancelled') NOT NULL DEFAULT 'selection',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_reports`
--

CREATE TABLE `applicant_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_status_reports`
--

CREATE TABLE `applicant_status_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `from_status` varchar(50) NOT NULL,
  `to_status` varchar(50) NOT NULL,
  `report_text` text NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blacklisted_applicants`
--

CREATE TABLE `blacklisted_applicants` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `issue` text DEFAULT NULL,
  `proof_paths` longtext DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reverted_at` datetime DEFAULT NULL,
  `reverted_by` int(10) UNSIGNED DEFAULT NULL,
  `compliance_note` text DEFAULT NULL,
  `compliance_proof_paths` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `business_units`
--

CREATE TABLE `business_units` (
  `id` int(10) UNSIGNED NOT NULL,
  `agency_id` smallint(5) UNSIGNED NOT NULL,
  `country_id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(40) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `business_units`
--

INSERT INTO `business_units` (`id`, `agency_id`, `country_id`, `code`, `name`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'CSNK-PH', 'CSNK Philippines', 1, '2026-02-24 01:19:27', '2026-02-24 01:19:27'),
(2, 2, 2, 'SMC-TR', 'SMC Turkey', 1, '2026-02-24 01:19:27', '2026-02-24 01:19:27'),
(3, 2, 3, 'SMC-BH', 'SMC Bahrain', 1, '2026-02-25 12:47:51', '2026-02-25 12:47:51'),
(4, 2, 6, 'SMC-MY', 'SMC Malaysia', 1, '2026-03-10 05:20:28', '2026-03-10 05:20:28');

--
-- Triggers `business_units`
--
DELIMITER $$
CREATE TRIGGER `business_units_after_delete` AFTER DELETE ON `business_units` FOR EACH ROW BEGIN
  INSERT IGNORE INTO `business_unit_id_recycle` (`id`) VALUES (OLD.id);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `business_units_before_insert` BEFORE INSERT ON `business_units` FOR EACH ROW BEGIN
  DECLARE recycled_id INT;

  IF NEW.id IS NULL OR NEW.id = 0 THEN
    -- Try to take the smallest recycled id (lock row for concurrency safety)
    SELECT `id` INTO recycled_id
    FROM `business_unit_id_recycle`
    ORDER BY `id` ASC
    LIMIT 1
    FOR UPDATE;

    IF recycled_id IS NOT NULL THEN
      DELETE FROM `business_unit_id_recycle` WHERE `id` = recycled_id;
      SET NEW.id = recycled_id;
    ELSE
      SELECT COALESCE(MAX(id), 0) + 1 INTO recycled_id FROM `business_units`;
      SET NEW.id = recycled_id;
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `business_unit_id_recycle`
--

CREATE TABLE `business_unit_id_recycle` (
  `id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `client_bookings`
--

CREATE TABLE `client_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `services_json` longtext NOT NULL,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `content_categories`
--

CREATE TABLE `content_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `business_unit_id` int(10) UNSIGNED NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_categories`
--

INSERT INTO `content_categories` (`id`, `name`, `description`, `display_order`, `is_active`, `created_at`, `business_unit_id`) VALUES
(1, 'Domestic Workers', '', 1, 1, '2026-03-07 03:40:15', 1),
(2, 'Skilled Driver', '', 2, 1, '2026-03-07 03:40:31', 1),
(3, 'Cellphone Technician', '', 3, 1, '2026-03-07 03:40:45', 1),
(4, 'Trainings', '', 1, 1, '2026-03-22 05:27:12', 3),
(5, 'Assessment', '', 2, 1, '2026-03-22 05:27:32', 3),
(6, 'Assessment', '', 3, 1, '2026-03-22 05:27:59', 3);

-- --------------------------------------------------------

--
-- Table structure for table `content_items`
--

CREATE TABLE `content_items` (
  `id` int(11) NOT NULL,
  `business_unit_id` int(10) UNSIGNED DEFAULT 0,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `image_path` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(4) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE `countries` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `iso2` char(2) NOT NULL,
  `iso3` char(3) NOT NULL,
  `name` varchar(100) NOT NULL,
  `default_tz` varchar(50) NOT NULL,
  `phone_country_code` varchar(6) NOT NULL,
  `currency_code` char(3) NOT NULL,
  `locale` varchar(10) NOT NULL,
  `date_format` varchar(20) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `iso2`, `iso3`, `name`, `default_tz`, `phone_country_code`, `currency_code`, `locale`, `date_format`, `active`) VALUES
(1, 'PH', 'PHL', 'Philippines', 'Asia/Manila', '+63', 'PHP', 'en-PH', 'MM/DD/YYYY', 1),
(2, 'TR', 'TUR', 'Turkey', 'Europe/Istanbul', '+90', 'TRY', 'tr-TR', 'DD.MM.YYYY', 1),
(3, 'BH', 'BHR', 'Bahrain', 'Asia/Bahrain', '+973', 'BHD', 'en-BH', 'DD/MM/YYYY', 1),
(6, 'MY', 'MYS', 'Malaysia', 'Asia/Kuala_Lumpur', '+60', 'MYR', 'ms_MY', 'Y-m-d', 1);

-- --------------------------------------------------------

--
-- Table structure for table `csnk_branches`
--

CREATE TABLE `csnk_branches` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'ACTIVE',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` varchar(100) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `updated_by` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `csnk_branches`
--

INSERT INTO `csnk_branches` (`id`, `code`, `name`, `status`, `is_default`, `sort_order`, `created_at`, `created_by`, `updated_at`, `updated_by`) VALUES
(1, 'CSNK-MNL', 'CSNK Manila', 'ACTIVE', 1, 0, '2026-03-11 06:16:27', 'system', '2026-03-18 13:25:07', 'jmpogi'),
(3, 'CSNK-ALAMINOS', 'CSNK Alaminos', 'ACTIVE', 0, 2, '2026-03-13 02:22:43', 'jmpogi', '2026-03-13 03:28:29', 'jmpogi'),
(4, 'CSNK-BATANGAS', 'CSNK Batangas', 'ACTIVE', 0, 1, '2026-03-13 02:23:16', 'jmpogi', '2026-03-18 08:12:39', 'jmpogi'),
(5, 'CSNK-MINDORO', 'CSNK Mindoro', 'ACTIVE', 0, 0, '2026-03-18 08:05:55', 'jmpogi', NULL, NULL),
(6, 'CSNK-PALAWAN', 'CSNK Palawan', 'ACTIVE', 0, 0, '2026-03-18 08:06:17', 'jmpogi', NULL, NULL),
(7, 'CSNK-MARINDUQUE', 'CSNK Marinduque', 'ACTIVE', 0, 0, '2026-03-18 08:06:49', 'jmpogi', NULL, NULL),
(8, 'CSNK-GENSAN', 'CSNK Gensan', 'ACTIVE', 0, 0, '2026-03-18 08:12:52', 'jmpogi', NULL, NULL),
(9, 'CSNK-ILOILO', 'CSNK ILOILO', 'ACTIVE', 0, 0, '2026-03-18 08:13:26', 'jmpogi', NULL, NULL),
(10, 'CSNK-DINAGAT', 'CSNK Dinagat', 'ACTIVE', 0, 0, '2026-03-18 08:13:38', 'jmpogi', NULL, NULL),
(11, 'CSNK-CEBU', 'CSNK Cebu', 'ACTIVE', 0, 0, '2026-03-18 08:13:58', 'jmpogi', NULL, NULL),
(12, 'CSNK-NUEVA-ECIJA', 'CSNK NUEVA ECIJA', 'ACTIVE', 0, 0, '2026-03-18 08:14:36', 'jmpogi', NULL, NULL),
(13, 'CSNK-BACOLOD', 'CSNK BACOLOD', 'ACTIVE', 0, 0, '2026-03-18 08:15:04', 'jmpogi', '2026-03-21 00:42:37', 'jmpogi');

-- --------------------------------------------------------

--
-- Table structure for table `document_types`
--

CREATE TABLE `document_types` (
  `id` int(10) UNSIGNED NOT NULL,
  `country_id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `label` varchar(150) NOT NULL,
  `is_required` tinyint(1) NOT NULL DEFAULT 0,
  `active` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `invoice_history`
--

CREATE TABLE `invoice_history` (
  `id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED DEFAULT 1,
  `client_booking_id` int(10) UNSIGNED DEFAULT NULL,
  `invoice_num` varchar(50) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `paid_date` date DEFAULT NULL,
  `reference_no` varchar(50) NOT NULL,
  `client_name` varchar(255) NOT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `client_address` text DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `applicants_data` longtext DEFAULT NULL,
  `pdf_filename` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `company_type` enum('CSNK','SMC') NOT NULL DEFAULT 'CSNK',
  `status` enum('Pending','OverDue','Paid') NOT NULL DEFAULT 'Pending',
  `payment_provider` varchar(50) DEFAULT 'XENDIT',
  `xendit_invoice_id` varchar(100) DEFAULT NULL,
  `payment_link` text DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Expired','Failed') DEFAULT 'Pending',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recycled_ids`
--

CREATE TABLE `recycled_ids` (
  `table_name` varchar(50) NOT NULL,
  `id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  ADD KEY `idx_admin_users_bu` (`business_unit_id`),
  ADD KEY `branch_id` (`branch_id`);

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
  ADD KEY `idx_applicants_bu_status` (`business_unit_id`,`status`),
  ADD KEY `branch_id` (`branch_id`);

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
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_unit_id_recycle`
--
ALTER TABLE `business_unit_id_recycle`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `client_bookings`
--
ALTER TABLE `client_bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_client_bookings_deleted_at` (`deleted_at`);

--
-- Indexes for table `content_categories`
--
ALTER TABLE `content_categories`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cc_bu` (`business_unit_id`);

--
-- Indexes for table `content_items`
--
ALTER TABLE `content_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ci_bu` (`business_unit_id`),
  ADD KEY `idx_ci_bu_cat` (`business_unit_id`,`category_id`);

--
-- Indexes for table `countries`
--
ALTER TABLE `countries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `csnk_branches`
--
ALTER TABLE `csnk_branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `idx_csnk_branches_status` (`status`),
  ADD KEY `idx_csnk_branches_sort` (`sort_order`),
  ADD KEY `idx_csnk_branches_code` (`code`);

--
-- Indexes for table `invoice_history`
--
ALTER TABLE `invoice_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_num` (`invoice_num`),
  ADD KEY `idx_invoice_bu` (`business_unit_id`),
  ADD KEY `idx_invoice_created` (`created_at`),
  ADD KEY `idx_invoice_client_booking` (`client_booking_id`);

--
-- Indexes for table `recycled_ids`
--
ALTER TABLE `recycled_ids`
  ADD PRIMARY KEY (`table_name`,`id`),
  ADD KEY `idx_recycle_id` (`id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1112;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `applicant_replacements`
--
ALTER TABLE `applicant_replacements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=231;

--
-- AUTO_INCREMENT for table `business_units`
--
ALTER TABLE `business_units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `client_bookings`
--
ALTER TABLE `client_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `content_categories`
--
ALTER TABLE `content_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `content_items`
--
ALTER TABLE `content_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `csnk_branches`
--
ALTER TABLE `csnk_branches`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `invoice_history`
--
ALTER TABLE `invoice_history`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=279;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `fk_admin_branch` FOREIGN KEY (`branch_id`) REFERENCES `csnk_branches` (`id`);

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `fk_applicant_branch` FOREIGN KEY (`branch_id`) REFERENCES `csnk_branches` (`id`);

--
-- Constraints for table `invoice_history`
--
ALTER TABLE `invoice_history`
  ADD CONSTRAINT `fk_invoice_business_unit` FOREIGN KEY (`business_unit_id`) REFERENCES `business_units` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
