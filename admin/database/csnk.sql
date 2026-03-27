-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 27, 2026 at 06:53 AM
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

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(996, 12, 'Create Account', 'Created employee Dinagat001 (Branch ID:10)', '::1', '2026-03-20 00:55:33'),
(997, 12, 'Create Account', 'Created employee Cebu001 (Branch ID:11)', '::1', '2026-03-20 00:56:06'),
(998, 12, 'Create Account', 'Created employee Nuevaecija001 (Branch ID:12)', '::1', '2026-03-20 00:56:57'),
(999, 12, 'Create Account', 'Created employee Bacolod001 (Branch ID:13)', '::1', '2026-03-20 00:57:50'),
(1000, 12, 'Logout', 'User logged out', '::1', '2026-03-20 00:58:45'),
(1001, 28, 'Login', 'User logged in successfully', '::1', '2026-03-20 00:58:56'),
(1002, 28, 'Logout', 'User logged out', '::1', '2026-03-20 00:59:27'),
(1003, 12, 'Login', 'User logged in successfully', '::1', '2026-03-20 00:59:32'),
(1004, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:41:40'),
(1005, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:41:50'),
(1006, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:42:03'),
(1007, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:42:16'),
(1008, 12, 'Update Branch', 'Updated branch \'CSNK BACOLOD\' (CSNK-BACOLOD)', '::1', '2026-03-21 00:42:37'),
(1009, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:42:55'),
(1010, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:58:57'),
(1011, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 00:59:07'),
(1012, 12, 'Edit Account', 'Edited ID 36', '::1', '2026-03-21 00:59:22'),
(1013, 12, 'Edit Account', 'Edited ID 36', '::1', '2026-03-21 01:00:00'),
(1014, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 01:00:09'),
(1015, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 01:00:17'),
(1016, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 01:08:10'),
(1017, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 01:08:20'),
(1018, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 01:16:30'),
(1019, 12, 'Edit Account', 'Edited ID 37', '::1', '2026-03-21 01:16:48'),
(1020, 12, 'Logout', 'User logged out', '::1', '2026-03-21 01:17:36'),
(1021, 37, 'Login', 'User logged in successfully', '::1', '2026-03-21 01:17:56'),
(1022, 37, 'Delete Applicant', 'Deleted applicant Ryzza Mae B. Diaz', '::1', '2026-03-21 01:21:44'),
(1023, 37, 'Restore Applicant', 'Restored applicant Ryzza Mae B. Diaz', '::1', '2026-03-21 01:21:48'),
(1024, 37, 'Logout', 'User logged out', '::1', '2026-03-21 01:23:37'),
(1025, 12, 'Login', 'User logged in successfully', '::1', '2026-03-21 01:23:42'),
(1026, 12, 'Logout', 'User logged out', '::1', '2026-03-21 01:23:52'),
(1027, 37, 'Login', 'User logged in successfully', '::1', '2026-03-21 01:24:00'),
(1028, 37, 'Logout', 'User logged out', '::1', '2026-03-21 01:41:27'),
(1029, 12, 'Login', 'User logged in successfully', '::1', '2026-03-21 01:41:32'),
(1030, 12, 'Logout', 'User logged out', '::1', '2026-03-21 01:42:03'),
(1031, 24, 'Login', 'User logged in successfully', '::1', '2026-03-21 01:42:13'),
(1032, 24, 'Logout', 'User logged out', '::1', '2026-03-21 01:42:38'),
(1033, 31, 'Login', 'User logged in successfully', '::1', '2026-03-21 01:42:47'),
(1034, 31, 'Logout', 'User logged out', '::1', '2026-03-21 02:30:47'),
(1035, 12, 'Login', 'User logged in successfully', '::1', '2026-03-21 02:30:52'),
(1036, 12, 'Login', 'User logged in successfully', '::1', '2026-03-21 06:53:55'),
(1037, 12, 'Login', 'User logged in successfully', '::1', '2026-03-22 05:24:14'),
(1038, 12, 'Start Replacement', 'Start replacement for Applicant ID 32; Reason: Other', '::1', '2026-03-22 05:24:45'),
(1039, 12, 'Assign Replacement (Turkey/SMC)', 'Assigned Applicant ID 30 as replacement for Original ID 32; original set to On Hold', '::1', '2026-03-22 05:24:51'),
(1040, 12, 'Revert On Hold Applicant (SMC/TR)', 'Reverted applicant Denise Grace Angeles Mendiola (ID: 32) from On Hold to Pending. Reason: Health Issues Resolved', '::1', '2026-03-22 05:25:23'),
(1041, 12, 'Add Content Category', 'Added category: Trainings (BU: 3)', '::1', '2026-03-22 05:27:12'),
(1042, 12, 'Add Content Category', 'Added category: Assessment (BU: 3)', '::1', '2026-03-22 05:27:32'),
(1043, 12, 'Add Content Items (Bulk)', 'Added 1 item(s) to category 4', '::1', '2026-03-22 05:27:59'),
(1044, 12, 'Add Content Category', 'Added category: Assessment (BU: 3)', '::1', '2026-03-22 05:27:59'),
(1045, 12, 'Delete Content Item', 'Deleted content ID: 9', '::1', '2026-03-22 05:28:53'),
(1046, 12, 'Login', 'User logged in successfully', '::1', '2026-03-23 00:17:34'),
(1047, 12, 'Update Applicant Status (with report)', 'Updated status for Johny Ocamps → approved; Reason: Client confirmed approval:', '::1', '2026-03-23 00:36:52'),
(1048, 12, 'Update Applicant Status', 'Updated status for Johny Ocamps → pending (CSNK)', '::1', '2026-03-23 00:36:59'),
(1049, 12, 'Update Applicant Status', 'Updated status for Johny Ocamps → on_process', '::1', '2026-03-23 00:37:06'),
(1050, 12, 'Update Applicant Status (with report)', 'Updated status for Hannah Louise Parker → approved; Reason: Client confirmed approval:', '::1', '2026-03-23 00:37:16'),
(1051, 12, 'Update Applicant Status', 'Updated status for Hannah Louise Parker → on_process (CSNK)', '::1', '2026-03-23 00:37:21'),
(1052, 12, 'Update Applicant Status', 'Updated status for Denise Grace Angeles Mendiola → on_process (SMC)', '::1', '2026-03-23 00:41:44'),
(1053, 12, 'Update Applicant Status', 'Updated status for Charmaine Rose Dimapilis Jimenez → on_process (SMC)', '::1', '2026-03-23 01:00:23'),
(1054, 12, 'Start Replacement', 'Start replacement for Applicant ID 29; Reason: Other', '::1', '2026-03-23 01:01:00'),
(1055, 12, 'Start Replacement', 'Start replacement for Applicant ID 29; Reason: Other', '::1', '2026-03-23 01:01:10'),
(1056, 12, 'Start Replacement', 'Start replacement for Applicant ID 29; Reason: Other', '::1', '2026-03-23 01:01:20'),
(1057, 12, 'Start Replacement', 'Start replacement for Applicant ID 29; Reason: Other', '::1', '2026-03-23 01:01:26'),
(1058, 12, 'Start Replacement', 'Start replacement for Applicant ID 29; Reason: Other', '::1', '2026-03-23 01:03:24'),
(1059, 12, 'Start Replacement', 'Start replacement for Applicant ID 29; Reason: Other', '::1', '2026-03-23 01:04:05'),
(1060, 12, 'Assign Replacement (Turkey/SMC)', 'Assigned Applicant ID 30 as replacement for Original ID 29; original set to On Hold', '::1', '2026-03-23 01:05:07'),
(1061, 12, 'Revert On Hold Applicant (SMC/TR)', 'Reverted applicant Charmaine Rose Dimapilis Jimenez (ID: 29) from On Hold to Pending. Reason: Personal Problems Solved', '::1', '2026-03-23 01:10:33'),
(1062, 12, 'Login', 'User logged in successfully', '::1', '2026-03-24 00:46:13'),
(1063, 12, 'Update Applicant Status (with report)', 'Updated status for Isabella Grace Mitchell → approved; Reason: Passed interview / assessment: passed with interview of client', '::1', '2026-03-24 00:48:20'),
(1064, 12, 'Logout', 'User logged out', '::1', '2026-03-24 03:02:06'),
(1065, 27, 'Login', 'User logged in successfully', '::1', '2026-03-24 03:02:16'),
(1066, 27, 'Logout', 'User logged out', '::1', '2026-03-24 03:03:11'),
(1067, 12, 'Login', 'User logged in successfully', '::1', '2026-03-24 03:03:17'),
(1068, 12, 'Login', 'User logged in successfully', '::1', '2026-03-24 03:07:56'),
(1069, 12, 'Hard Delete Client Booking', 'Permanently deleted client booking ID 19', '::1', '2026-03-24 06:57:56'),
(1070, 12, 'Hard Delete Client Booking', 'Permanently deleted client booking ID 22', '::1', '2026-03-24 06:58:17'),
(1071, 12, 'Hard Delete Client Booking', 'Permanently deleted client booking ID 18', '::1', '2026-03-24 06:58:45'),
(1072, 12, 'Hard Delete Client Booking', 'Permanently deleted client booking ID 17', '::1', '2026-03-24 06:58:51'),
(1073, 12, 'Hard Delete Client Booking', 'Permanently deleted client booking ID 23', '::1', '2026-03-24 06:59:37'),
(1074, 12, 'Hard Delete Client Booking', 'Permanently deleted client booking ID 21', '::1', '2026-03-24 06:59:39'),
(1075, 12, 'Update Applicant Status (with report)', 'Updated status for Hannah Louise Parker → pending; Reason: Documents incomplete / pending:', '::1', '2026-03-24 07:04:33'),
(1076, 12, 'Update Applicant Status (with report)', 'Updated status for Johny Ocamps → pending; Reason: Client request / feedback: asdasdasd', '::1', '2026-03-24 07:04:43'),
(1077, 12, 'Login', 'User logged in successfully', '::1', '2026-03-24 12:11:50'),
(1078, 12, 'Login', 'User logged in successfully', '::1', '2026-03-25 00:20:29'),
(1079, 5, 'Login', 'User logged in successfully', '::1', '2026-03-25 03:13:14'),
(1080, 5, 'Add Applicant', 'Added new applicant: bruhhhhh Trial', '::1', '2026-03-25 03:23:28'),
(1081, 5, 'Delete Applicant', 'Deleted applicant bruhhhhh Trial', '::1', '2026-03-25 03:23:34'),
(1082, 5, 'Delete Applicant', 'Deleted applicant bruhhhhh Trial', '::1', '2026-03-25 03:24:16'),
(1083, 5, 'Add Applicant', 'Added new applicant: Bruh Trial', '::1', '2026-03-25 03:25:09'),
(1084, 27, 'Login', 'User logged in successfully', '::1', '2026-03-25 03:44:34'),
(1085, 27, 'Update Applicant Status', 'Updated status for Johny Ocamps → on_process', '::1', '2026-03-25 03:45:00'),
(1086, 27, 'Update Applicant Status (with report)', 'Updated status for Johny Ocamps → pending; Reason: Client request / feedback: adada', '::1', '2026-03-25 03:45:17'),
(1087, 27, 'Logout', 'User logged out', '::1', '2026-03-25 03:53:44'),
(1088, 27, 'Login', 'User logged in successfully', '::1', '2026-03-25 03:54:06'),
(1089, 27, 'Logout', 'User logged out', '::1', '2026-03-25 03:56:36'),
(1090, 27, 'Login', 'User logged in successfully', '::1', '2026-03-25 03:56:49'),
(1091, 27, 'Logout', 'User logged out', '::1', '2026-03-25 04:41:54'),
(1092, 27, 'Login', 'User logged in successfully', '::1', '2026-03-25 05:04:41'),
(1093, 27, 'Logout', 'User logged out', '::1', '2026-03-25 05:04:52'),
(1094, 25, 'Login', 'User logged in successfully', '::1', '2026-03-25 05:05:02'),
(1095, 25, 'Logout', 'User logged out', '::1', '2026-03-25 05:05:11'),
(1096, 35, 'Login', 'User logged in successfully', '::1', '2026-03-25 05:05:24'),
(1097, 35, 'Logout', 'User logged out', '::1', '2026-03-25 05:05:45'),
(1098, 12, 'Logout', 'User logged out', '::1', '2026-03-25 05:20:21'),
(1099, 27, 'Login', 'User logged in successfully', '::1', '2026-03-25 05:20:42'),
(1100, 27, 'Logout', 'User logged out', '::1', '2026-03-25 05:21:09'),
(1101, 12, 'Login', 'User logged in successfully', '::1', '2026-03-25 05:21:18'),
(1102, 12, 'Update Applicant Status', 'Updated status for Isabella Grace Mitchell → pending (CSNK)', '::1', '2026-03-25 05:21:46'),
(1103, 12, 'Permanent Delete', 'Permanently deleted applicant ID 45', '::1', '2026-03-25 05:21:53'),
(1104, 12, 'Permanent Delete', 'Permanently deleted applicant ID 1', '::1', '2026-03-25 05:21:59'),
(1105, 12, 'Login', 'User logged in successfully', '::1', '2026-03-26 00:25:51'),
(1106, 12, 'Logout', 'User logged out', '::1', '2026-03-26 00:27:35'),
(1107, 12, 'Login', 'User logged in successfully', '::1', '2026-03-26 00:27:43'),
(1108, 12, 'Login', 'User logged in successfully', '::1', '2026-03-26 00:29:27'),
(1109, 12, 'Login', 'User logged in successfully', '::1', '2026-03-26 01:40:14'),
(1110, 12, 'Login', 'User logged in successfully', '::1', '2026-03-26 05:19:34'),
(1111, 12, 'Login', 'User logged in successfully', '::1', '2026-03-27 05:12:29');

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
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `business_unit_id`, `branch_id`, `country_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone_number`, `alt_phone_number`, `email`, `date_of_birth`, `address`, `educational_attainment`, `work_history`, `preferred_location`, `languages`, `specialization_skills`, `employment_type`, `daily_rate`, `education_level`, `years_experience`, `picture`, `video_url`, `video_provider`, `video_type`, `video_title`, `video_thumbnail_url`, `video_duration_seconds`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(23, 1, NULL, 1, 'Maria Lourdes', 'Santos', 'Cruz', '', '09124567831', '09167345218', 'maria.cruz28@example.com', '1997-03-14', '1241 Ilang‑Ilang St., Brgy. 105, Tondo, Manila', '{\"elementary\":{\"school\":\"Jose Corazon de Jesus Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Tondo High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeepe\",\"location\":\"Pasay\"}]', '[\"Manila\",\"Pasay\",\"Makati\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\"]', 'Full Time', 700.00, 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9436d886b_1770951734.jpg', 'video/698e9436e46e0_1770951734.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:02:14', '2026-03-02 01:46:55', NULL),
(24, 1, NULL, 1, 'Joanna Marie', 'Pascual', 'Dela Torre', '', '09983457621', '09284567310', 'joannamdtorre@example.com', '1991-07-22', '92 Dahlia St., Brgy. Baesa, Quezon City', '{\"elementary\":{\"school\":\"Baesa Elementary School\",\"year\":\"1998\\u20132004\"},\"highschool\":{\"school\":\"Quezon City High Schoo\",\"year\":\"2004\\u20132008\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CleanPro Manila\",\"years\":\"2018\\u20132023\",\"role\":\"All\\u2011Around Helper\",\"location\":\"Quezon City\"}]', '[\"Quezon City\",\"Manila\",\"San Juan\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 5, 'applicants/698e9578069e6_1770952056.jpg', 'video/698e95781c1dc_1770952056.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:07:36', '2026-03-11 05:14:01', NULL),
(25, 1, NULL, 1, 'Ana Beatriz', 'Gomez', 'Reyes', '', '09156780234', '09156780234', 'ana.reyes25@example.com', '2001-01-09', '815 San Marcelino St., Brgy. Malate, Manila', '{\"elementary\":{\"school\":\"Malate Elementary School\",\"year\":\"2007\\u20132013\"},\"highschool\":{\"school\":\"Manila High School\",\"year\":\"2013\\u20132017\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Family Care Agency\",\"years\":\"2022\\u20132024\",\"role\":\"Babysitter\",\"location\":\"Ermita\"}]', '[\"Manila\",\"Pasay\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e96988e7b0_1770952344.jpg', 'video/698e96989948b_1770952344.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:12:24', '2026-03-02 01:46:55', NULL),
(26, 1, NULL, 1, 'Kristine Joy', 'Villanueva', 'Ramos', '', '09097865432', '09120457839', 'kjramos42@example.com', '1983-06-03', '54 Sampaguita St., Brgy. Cupang, Muntinlupa City', '{\"elementary\":{\"school\":\"Cupang Elementary School\",\"year\":\"1990\\u20131996\"},\"highschool\":{\"school\":\"Muntinlupa National High School\",\"year\":\"1996\\u20132000\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SouthClean Services\",\"years\":\"2017\\u20132023\",\"role\":\"Housemaid\",\"location\":\"Muntinlupa\"},{\"company\":\"Evergreen Laundry\",\"years\":\"2014\\u20132017\",\"role\":\"Laundry Worker\",\"location\":\"Pasig\"}]', '[\"Muntinlupa\",\"Las Pi\\u00f1as\",\"Para\\u00f1aque\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 9, 'applicants/698e977bb17ee_1770952571.jpg', 'video/698e977bc23d6_1770952571.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:16:11', '2026-03-07 08:12:06', NULL),
(27, 1, NULL, 1, 'Shiela May', 'Basco', 'Cortez', '', '09189234577', '09361245780', 'shielamcortez30@example.com', '1995-11-16', '2385 Mabini St., Brgy. San Andres Bukid, Manila', '{\"elementary\":{\"school\":\"San Andres Elementary School\",\"year\":\"2002\\u20132008\"},\"highschool\":{\"school\":\"Arellano High School\",\"year\":\"2008\\u20132012\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"FreshStart Maid Agency\",\"years\":\"2020\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Makati\"}]', '[\"Makati\",\"Manila\",\"Taguig\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/698e984dec764_1770952781.jpg', 'video/698e984e007ce_1770952782.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:19:41', '2026-03-07 08:12:04', NULL),
(28, 1, NULL, 1, 'Rowena Liza', 'Cruz', 'Mariano', '', '09351240988', '09278450329', 'rowenamariano45@example.com', '1980-09-28', '702 Maliputo St., Brgy. Karuhatan, Valenzuela City', '{\"elementary\":{\"school\":\"Karuhatan Elementary School\",\"year\":\"1987\\u20131993\"},\"highschool\":{\"school\":\"Valenzuela National High School\",\"year\":\"1993\\u20131997\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"North Metro Helpers\",\"years\":\"2018\\u20132024\",\"role\":\"Cook\\/Housemaid\",\"location\":\"Valenzuela\"},{\"company\":\"CarePlus\",\"years\":\"2014\\u20132018\",\"role\":\"All\\u2011Around Helper\",\"location\":\"Valenzuela\"}]', '[\"Valenzuela\",\"Quezon City\",\"Caloocan\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 10, 'applicants/698e992bbb3a6_1770953003.jpg', 'video/698e992bc7543_1770953003.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:23:23', '2026-03-11 05:27:08', NULL),
(29, 2, NULL, 2, 'Charmaine Rose', 'Dimapilis', 'Jimenez', '', '09273659012', '09190345711', 'charmainejimenez22@example.com', '2004-02-04', '1789 Camarin Road, Brgy. 178, Camarin, Caloocan City', '{\"elementary\":{\"school\":\"Camarin Elementary School\",\"year\":\"2010\\u20132016\"},\"highschool\":{\"school\":\"Caloocan High School\",\"year\":\"2016\\u20132020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Clean &amp;amp;amp; Care Services\",\"years\":\"2023\\u20132024\",\"role\":\"Housemaid\",\"location\":\"Caloocan\"}]', '[\"Caloocan\",\"QC\",\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 1, 'applicants/698e9a253267f_1770953253.jpg', 'video/698e9a253ea3a_1770953253.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'approved', NULL, '2026-02-13 03:27:33', '2026-03-26 02:01:12', NULL),
(30, 2, NULL, 2, 'Lorna Fe', 'Bagtas', 'Malabanan', '', '09172349850', '09351867209', 'lornamalabanan39@example.com', '1986-04-10', '443 P. Burgos St., Brgy. Poblacion, Makati City', '{\"elementary\":{\"school\":\"Poblacion Elementary School\",\"year\":\"1992\\u20131998\"},\"highschool\":{\"school\":\"Makati High School\",\"year\":\"1998\\u20132002\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Makati HomeCare\",\"years\":\"2020\\u20132024\",\"role\":\"Housemaid\",\"location\":\"Bangkal Makati\"},{\"company\":\"Taguig Helpers Agency\",\"years\":\"2016\\u20132020\",\"role\":\"Cook\",\"location\":\"Makati\"}]', '[\"Makati\",\"Taguig\",\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9adbdc727_1770953435.jpg', 'video/698e9adbe929e_1770953435.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'approved', NULL, '2026-02-13 03:30:35', '2026-03-26 02:08:59', NULL),
(31, 2, NULL, 2, 'Lea Catherine', 'Fernandez', 'Rivera', '', '09190456722', '09175346098', 'learivera27@example.com', '1998-12-02', '300 San Guillermo St., Brgy. Hulo, Mandaluyong City', '{\"elementary\":{\"school\":\"Hulo Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Mandaluyong High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"MetroClean\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Ortigas\"}]', '[\"Mandaluyong\",\"Pasig\",\"QC\"]', '[]', '[]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9b841dc91_1770953604.jpg', 'video/698e9b8425cda_1770953604.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'approved', NULL, '2026-02-13 03:33:24', '2026-03-26 02:01:08', NULL),
(32, 2, NULL, 2, 'Denise Grace', 'Angeles', 'Mendiola', '', '09956873410', '09359872140', 'denisemendiola33@example.com', '1992-08-19', '5124 A. Bonifacio St., Brgy. Western Bicutan, Taguig City', '{\"elementary\":{\"school\":\"Western Bicutan Elementary School\",\"year\":\"1999\\u20132005\"},\"highschool\":{\"school\":\"Taguig National High School\",\"year\":\"2005\\u20132009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Taguig Home Services\",\"years\":\"2019\\u20132024\",\"role\":\"Housemaid\\/Caregiver\",\"location\":\"BGC\"},{\"company\":\"UrbanClean Agency\",\"years\":\"2016\\u20132019\",\"role\":\"Cleaner\",\"location\":\"Pasay\"}]', '[\"Taguig\",\"Pasay\",\"Makati\"]', '[\"Filipino\",\"English\"]', '[]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9c75149ea_1770953845.jpg', 'video/698e9c751c0ff_1770953845.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:37:25', '2026-03-25 05:22:08', NULL),
(33, 1, NULL, 1, 'Ava', 'Marie', 'Thompson', '', '09999999999', '09999999999', 'email@gmail.com', '1998-02-19', '1234 address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Tertiary Graduate (Bachelor’s Degree)', 3, 'applicants/698e8d360baa7_1770949942.jpg', 'video/698e8d3610907_1770949942.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:32:22', '2026-03-05 01:31:56', NULL),
(34, 1, NULL, 1, 'Sophia', 'Claire', 'Ramirez', '', '09999999999', '09999999999', 'email@gmail.com', '1990-11-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Mandaluyong\",\"makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e8df92b357_1770950137.jpg', 'video/698e8df92cdd7_1770950137.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:35:37', '2026-03-02 01:46:55', NULL),
(35, 1, NULL, 1, 'Isabella', 'Grace', 'Mitchell', '', '09999999999', '09999999999', 'email@gmail.com', '2000-08-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"IT\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"Mandaluyong\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Technical-Vocational / TESDA Graduate', 2, 'applicants/698e8e832247e_1770950275.jpg', 'video/698e8e83233c5_1770950275.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:37:55', '2026-03-25 05:21:46', NULL),
(36, 1, NULL, 1, 'Emily', 'Rose', 'Johnson', '', '09999999999', '09999999999', 'email@gmail.com', '1960-02-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"paranaque\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning &amp;amp; Housekeeping (General)\",\"Cooking &amp;amp; Food Service\",\"Pet &amp;amp; Outdoor Maintenance\"]', 'Full Time', NULL, 'Senior High School Graduate (K-12 Curriculum)', 2, 'applicants/698e8f3716c79_1770950455.jpg', 'video/698e8f231b3bd_1770950435.mp4', 'file', 'file', '', NULL, NULL, 'on_process', 5, '2026-02-13 02:40:35', '2026-03-17 00:43:00', NULL),
(37, 1, NULL, 1, 'Mia', 'Elizabeth', 'Carter', '', '09999999999', '09999999999', 'email@gmail.com', '2001-11-02', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e8fa38a07a_1770950563.jpg', 'video/698e8fa38af70_1770950563.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:42:43', '2026-03-02 01:46:55', NULL),
(38, 1, NULL, 1, 'Olivia', 'Jane', 'Peterson', '', '09999999999', '09999999999', 'email@gmail.com', '1990-03-06', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e901853864_1770950680.jpg', 'video/698e9018547b8_1770950680.mp4', 'file', 'file', '', NULL, NULL, 'on_process', 5, '2026-02-13 02:44:40', '2026-03-24 13:43:18', NULL),
(39, 1, NULL, 1, 'Chloe', 'Ann', 'Sullivan', '', '09999999999', '09999999999', 'email@gmail.com', '1989-01-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Taguig\",\"BGC\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Level (Attended High School)', 2, 'applicants/698e90a11d029_1770950817.jpg', 'video/698e90a11f3d4_1770950817.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:46:57', '2026-03-02 01:46:55', NULL),
(40, 1, NULL, 1, 'Hannah', 'Louise', 'Parker', '', '09999999999', '09999999999', 'email@gmail.com', '1999-08-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Elementary Graduate', 3, 'applicants/698e910d1e60e_1770950925.jpg', 'video/698e910d1fc78_1770950925.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:48:45', '2026-03-24 07:04:33', NULL),
(41, 1, NULL, 1, 'Abigail', 'Nicole', 'Sanders', '', '09999999999', '09999999999', 'email@gmail.com', '2000-11-08', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/698e918576116_1770951045.jpg', 'video/698e918577686_1770951045.mp4', 'file', 'file', '', NULL, NULL, 'on_process', 5, '2026-02-13 02:50:45', '2026-03-24 07:00:57', NULL),
(42, 1, NULL, 1, 'Natalie', 'Faith', 'Rogers', '', '09999999999', '09999999999', 'email@gmail.com', '1999-01-23', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e9220edd8f_1770951200.jpg', 'video/698e9220ee585_1770951200.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:53:20', '2026-03-02 01:46:55', NULL),
(43, 1, NULL, 1, 'Ryzza Mae', 'B.', 'Diaz', '', '09123123718', '09817238712', 'renzdiaz.contact@gmail.com', '2026-02-25', '87412 ajllmdawudawdawdasdawds', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2026 - 2028\",\"role\":\"Housemaid\",\"location\":\"Ermita Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati\"}]', '[\"Makati City\",\"Mandaluyong CIty\"]', '[]', '[\"Cleaning & Housekeeping (General)\",\"Childcare & Maternity (Yaya)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 150.00, 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/6996b581e440f_1771484545.jpg', 'video/6996b581ecad4_1771484545.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 12, '2026-02-19 07:02:25', '2026-03-21 01:21:48', NULL),
(44, 1, NULL, NULL, 'Johny', '', 'Ocamps', '', '09999999999', '09999999991', '', '2000-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2026 - 2028\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Manila\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 1000.00, 'Elementary Graduate', 2, 'applicants/69aa7106cc2f4_1772777734.jpg', NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 5, '2026-03-06 06:15:34', '2026-03-25 03:45:17', NULL),
(46, 1, 5, NULL, 'Bruh', '', 'Trial', '', '09999999999', '', '', '2000-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"\",\"year\":\"\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Ermita Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Secondary Level (Attended High School)', 3, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 5, '2026-03-25 03:25:09', '2026-03-25 03:25:09', NULL);

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
(161, 23, 1, 1, 'brgy_clearance', 'documents/698e9436dda8e_1770951734.jpg', '2026-02-13 03:02:14'),
(162, 23, 1, 2, 'birth_certificate', 'documents/698e9436de60c_1770951734.jpg', '2026-02-13 03:02:14'),
(163, 23, 1, 3, 'sss', 'documents/698e9436df31c_1770951734.jpg', '2026-02-13 03:02:14'),
(164, 23, 1, 4, 'pagibig', 'documents/698e9436e0517_1770951734.jpg', '2026-02-13 03:02:14'),
(165, 23, 1, 5, 'nbi', 'documents/698e9436e16f0_1770951734.jpg', '2026-02-13 03:02:14'),
(166, 23, 1, 6, 'police_clearance', 'documents/698e9436e223c_1770951734.jpg', '2026-02-13 03:02:14'),
(167, 23, 1, 7, 'tin_id', 'documents/698e9436e2e94_1770951734.jpg', '2026-02-13 03:02:14'),
(168, 23, 1, 8, 'passport', 'documents/698e9436e3ade_1770951734.jpg', '2026-02-13 03:02:14'),
(169, 24, 1, 1, 'brgy_clearance', 'documents/698e95780d9c3_1770952056.jpg', '2026-02-13 03:07:36'),
(170, 24, 1, 2, 'birth_certificate', 'documents/698e95780f5d0_1770952056.png', '2026-02-13 03:07:36'),
(171, 24, 1, 3, 'sss', 'documents/698e957810ec3_1770952056.jpg', '2026-02-13 03:07:36'),
(172, 24, 1, 4, 'pagibig', 'documents/698e957812bfd_1770952056.jpg', '2026-02-13 03:07:36'),
(173, 24, 1, 5, 'nbi', 'documents/698e957814ca4_1770952056.jpg', '2026-02-13 03:07:36'),
(174, 24, 1, 6, 'police_clearance', 'documents/698e957816f42_1770952056.png', '2026-02-13 03:07:36'),
(175, 24, 1, 7, 'tin_id', 'documents/698e9578189a4_1770952056.png', '2026-02-13 03:07:36'),
(176, 24, 1, 8, 'passport', 'documents/698e95781a55f_1770952056.jpg', '2026-02-13 03:07:36'),
(177, 25, 1, 1, 'brgy_clearance', 'documents/698e969891f78_1770952344.jpg', '2026-02-13 03:12:24'),
(178, 25, 1, 2, 'birth_certificate', 'documents/698e969893293_1770952344.png', '2026-02-13 03:12:24'),
(179, 25, 1, 3, 'sss', 'documents/698e969893eac_1770952344.jpg', '2026-02-13 03:12:24'),
(180, 25, 1, 4, 'pagibig', 'documents/698e969894b88_1770952344.png', '2026-02-13 03:12:24'),
(181, 25, 1, 5, 'nbi', 'documents/698e969895f80_1770952344.jpg', '2026-02-13 03:12:24'),
(182, 25, 1, 6, 'police_clearance', 'documents/698e969896ba6_1770952344.jpg', '2026-02-13 03:12:24'),
(183, 25, 1, 7, 'tin_id', 'documents/698e969897a2d_1770952344.png', '2026-02-13 03:12:24'),
(184, 25, 1, 8, 'passport', 'documents/698e9698985d9_1770952344.jpg', '2026-02-13 03:12:24'),
(185, 26, 1, 1, 'brgy_clearance', 'documents/698e977bb470b_1770952571.jpg', '2026-02-13 03:16:11'),
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

--
-- Dumping data for table `applicant_replacements`
--

INSERT INTO `applicant_replacements` (`id`, `business_unit_id`, `original_applicant_id`, `replacement_applicant_id`, `client_booking_id`, `reason`, `report_text`, `attachments_json`, `status`, `created_by`, `created_at`, `updated_at`, `assigned_at`) VALUES
(103, NULL, 31, 29, 19, 'Other', 'asd', '[]', 'assigned', 12, '2026-03-11 05:27:26', '2026-03-11 05:27:27', '2026-03-11 13:27:27'),
(104, NULL, 29, 32, 19, 'Other', 'asdasd', '[]', 'assigned', 12, '2026-03-11 05:36:42', '2026-03-11 05:36:44', '2026-03-11 13:36:44'),
(105, NULL, 32, 31, NULL, 'Other', 'asdasd', '[]', 'assigned', 12, '2026-03-11 05:39:18', '2026-03-11 05:39:20', '2026-03-11 13:39:20'),
(106, NULL, 31, 30, NULL, 'Other', 'asdasd', '[]', 'assigned', 12, '2026-03-11 05:58:35', '2026-03-11 05:58:38', '2026-03-11 13:58:38'),
(107, NULL, 32, 30, NULL, 'Other', 'asd', '[]', 'assigned', 12, '2026-03-22 05:24:45', '2026-03-22 05:24:51', '2026-03-22 13:24:51'),
(108, NULL, 29, NULL, 22, 'Other', 'asddasdsaadsdasdas', '[]', 'selection', 12, '2026-03-23 01:01:00', '2026-03-24 06:57:10', NULL),
(109, NULL, 29, NULL, 22, 'Other', 'asddasdsaadsdasdas', '[]', 'selection', 12, '2026-03-23 01:01:10', '2026-03-24 06:57:10', NULL),
(110, NULL, 29, NULL, 22, 'Other', 'asddasdsaadsdasdas', '[]', 'selection', 12, '2026-03-23 01:01:20', '2026-03-24 06:57:10', NULL),
(111, NULL, 29, NULL, 22, 'Other', 'asddasdsaadsdasdas', '[]', 'selection', 12, '2026-03-23 01:01:26', '2026-03-24 06:57:10', NULL),
(112, NULL, 29, NULL, 22, 'Other', 'asddasdsaadsdasdas', '[]', 'selection', 12, '2026-03-23 01:03:24', '2026-03-24 06:57:10', NULL),
(113, NULL, 29, 30, 22, 'Other', 'asdasd', '[]', 'assigned', 12, '2026-03-23 01:04:05', '2026-03-24 06:57:10', '2026-03-23 09:05:07');

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

--
-- Dumping data for table `applicant_status_reports`
--

INSERT INTO `applicant_status_reports` (`id`, `applicant_id`, `business_unit_id`, `from_status`, `to_status`, `report_text`, `admin_id`, `created_at`) VALUES
(1, 32, 2, 'on_process', 'pending', 'Client confirmed / Ready: asdawds', 12, '2026-03-07 14:50:54'),
(32, 37, 1, 'on_process', 'pending', 'no client', 12, '2026-02-23 14:59:56'),
(33, 43, 1, 'on_process', 'pending', 'no client', 12, '2026-02-23 21:22:23'),
(34, 23, 1, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Documents Complete. Description: solved', 12, '2026-02-23 21:27:27'),
(35, 32, 2, 'on_process', 'approved', 'Client confirmed / Ready: settled', 12, '2026-02-23 21:31:15'),
(36, 30, 2, 'on_process', 'approved', 'Client confirmed / Ready: awdasd', 12, '2026-02-23 21:36:16'),
(37, 43, 1, 'on_process', 'approved', 'Client confirmed / Ready: awdasd', 12, '2026-02-23 21:39:45'),
(38, 43, 1, 'on_process', 'pending', 'Interview rescheduled: awdas', 12, '2026-02-23 21:40:48'),
(39, 28, 1, 'pending', 'on_process', 'Replacement for Ryzza Mae Diaz (ID: 43) due to Other.', 12, '2026-02-23 21:41:38'),
(40, 43, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 28. Reason: Other.', 12, '2026-02-23 21:41:38'),
(41, 43, 1, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Documents Complete. Description: awd', 12, '2026-02-23 21:42:07'),
(42, 28, 1, 'on_process', 'pending', 'Interview rescheduled: awdas', 12, '2026-02-23 21:42:55'),
(43, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-02-23 22:05:06'),
(44, 43, 1, 'on_process', 'pending', 'Requirements complete: awdawds', 12, '2026-02-23 22:05:45'),
(45, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-02-23 22:13:09'),
(46, 43, 1, 'on_process', 'pending', 'Client confirmed / Ready: awdas', 12, '2026-02-23 22:50:02'),
(50, 43, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 20:38:01'),
(51, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-04 20:39:48'),
(52, 43, 1, 'on_process', 'approved', 'Interview rescheduled: asd', 12, '2026-03-04 20:39:55'),
(53, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-04 20:58:17'),
(54, 43, 1, 'on_process', 'approved', 'Passed interview / assessment: goods', 12, '2026-03-04 20:58:26'),
(55, 28, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:22:46'),
(56, 27, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:36:11'),
(57, 32, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-04 21:39:27'),
(58, 32, 2, 'on_process', 'pending', 'Status changed from On process to Pending', 12, '2026-03-04 21:41:01'),
(59, 30, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:41:47'),
(60, 30, 2, 'on_process', 'approved', 'Status changed from On process to Approved', 12, '2026-03-04 21:43:13'),
(61, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:44:19'),
(62, 31, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:44:47'),
(63, 30, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:44:59'),
(64, 29, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 21:45:03'),
(65, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 20, '2026-03-04 21:51:32'),
(66, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-04 22:02:52'),
(67, 30, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-04 22:03:11'),
(68, 30, 2, 'on_process', 'pending', 'Status changed from On process to Pending', 12, '2026-03-04 22:03:22'),
(69, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-05 08:06:56'),
(70, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 08:27:14'),
(71, 43, 1, 'on_process', 'pending', 'Interview rescheduled: asd', 12, '2026-03-05 08:27:26'),
(72, 32, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 08:27:32'),
(73, 32, 2, 'on_process', 'pending', 'Status changed from On process to Pending', 12, '2026-03-05 08:27:46'),
(74, 32, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 08:28:40'),
(75, 29, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 08:39:54'),
(76, 32, 2, 'on_process', 'pending', 'Status changed from On process to Pending', 12, '2026-03-05 08:39:59'),
(77, 29, 2, 'on_process', 'pending', 'Status changed from On process to Pending', 12, '2026-03-05 08:40:04'),
(78, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-05 08:40:08'),
(79, 32, 2, 'on_process', 'approved', 'Status changed from On process to Approved', 12, '2026-03-05 08:40:24'),
(80, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 08:58:56'),
(81, 43, 1, 'on_process', 'pending', 'Interview rescheduled: asdasd', 12, '2026-03-05 08:59:05'),
(82, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 08:59:30'),
(83, 32, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 09:06:13'),
(84, 32, 2, 'on_process', 'pending', 'Passed interview / assessment: asdasd', 12, '2026-03-05 09:18:59'),
(85, 32, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 09:19:20'),
(86, 32, 2, 'on_process', 'approved', 'Requirements complete: asd', 12, '2026-03-05 09:19:37'),
(87, 43, 1, 'on_process', 'pending', 'Interview rescheduled: asd', 12, '2026-03-05 09:20:17'),
(88, 33, 1, 'on_process', 'approved', 'Client confirmed / Ready: asdasd', 12, '2026-03-05 09:31:52'),
(89, 29, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-05 09:44:42'),
(90, 31, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 09:44:56'),
(91, 31, 2, 'on_process', 'pending', 'Interview rescheduled: asdasdas', 12, '2026-03-05 09:45:03'),
(92, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-05 10:24:46'),
(93, 43, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 10:26:00'),
(94, 29, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-05 10:44:17'),
(95, 43, 1, 'on_process', 'pending', 'Requirements complete: asd', 18, '2026-03-05 11:01:56'),
(96, 29, 2, 'on_process', 'pending', 'Client confirmed / Ready: asdasd', 20, '2026-03-05 13:14:11'),
(97, 32, 2, 'on_process', 'pending', 'Passed interview / assessment: asddas', 20, '2026-03-05 13:14:19'),
(98, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 20, '2026-03-05 13:15:32'),
(99, 43, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-05 14:20:47'),
(100, 29, 2, 'pending', 'on_process', 'Replacement for Denise Grace Mendiola (ID: 32) due to Other.', 20, '2026-03-05 14:55:48'),
(101, 29, 2, 'on_process', 'pending', 'Interview rescheduled: asdasd', 20, '2026-03-05 14:56:15'),
(102, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 20, '2026-03-05 15:34:38'),
(103, 29, 2, 'pending', 'on_process', 'Replacement for Denise Grace Mendiola (ID: 32) due to Other.', 20, '2026-03-05 15:34:48'),
(104, 29, 2, 'on_process', 'pending', 'Requirements complete: acwasdwa', 20, '2026-03-05 15:35:03'),
(105, 31, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 20, '2026-03-06 09:31:06'),
(106, 40, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 18, '2026-03-06 09:33:37'),
(107, 28, 1, 'on_process', 'pending', 'Client confirmed / Ready: awdasdaw', 18, '2026-03-06 09:59:41'),
(108, 38, 1, 'on_process', 'pending', 'Interview rescheduled: adwas', 18, '2026-03-06 10:20:24'),
(109, 43, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 18, '2026-03-06 10:35:39'),
(110, 28, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 18, '2026-03-06 10:35:44'),
(111, 28, 1, 'on_process', 'pending', 'Interview rescheduled: asd', 18, '2026-03-06 10:35:49'),
(112, 43, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 18, '2026-03-06 10:58:56'),
(113, 28, 1, 'on_process', 'pending', 'Client confirmed / Ready: asdawc', 18, '2026-03-06 10:59:13'),
(114, 28, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 18, '2026-03-06 11:01:04'),
(115, 36, 1, 'on_process', 'pending', 'Client confirmed / Ready: awdas', 18, '2026-03-06 11:01:24'),
(116, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-06 11:31:12'),
(117, 29, 2, 'pending', 'on_process', 'Replacement for Denise Grace Mendiola (ID: 32) due to Other.', 12, '2026-03-06 11:31:27'),
(118, 29, 2, 'on_process', 'pending', 'Client confirmed / Ready: asd', 12, '2026-03-06 11:31:39'),
(119, 44, 1, 'on_process', 'approved', 'Passed interview / assessment: GOOOOOOD BOIII', 5, '2026-03-07 09:37:08'),
(120, 43, 1, 'on_process', 'pending', 'Client confirmed / Ready: asdasd', 12, '2026-03-07 16:20:32'),
(121, 24, 1, 'on_process', 'pending', 'Requirements complete: asdasd', 12, '2026-03-07 16:20:44'),
(122, 32, 2, 'on_process', 'pending', 'Client confirmed / Ready: asdas', 12, '2026-03-07 16:20:58'),
(123, 31, 2, 'on_process', 'pending', 'Client confirmed / Ready: asdasd', 12, '2026-03-07 16:33:17'),
(124, 31, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-07 16:33:24'),
(125, 31, 2, 'on_process', 'approved', 'Client confirmed / Ready: asdasd', 12, '2026-03-10 08:58:24'),
(126, 31, 2, 'on_process', 'approved', 'Client confirmed / Ready: asdasdas', 12, '2026-03-10 16:28:31'),
(127, 32, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-10 16:37:46'),
(128, 28, 1, 'on_process', 'approved', 'Interview rescheduled: asd', 12, '2026-03-10 16:41:08'),
(129, 32, 2, 'on_process', 'pending', 'Interview rescheduled: asdasd', 12, '2026-03-10 16:41:29'),
(130, 28, 1, 'on_process', 'approved', 'Qualified based on evaluation: asdasd', 12, '2026-03-11 09:54:00'),
(131, 28, 1, 'on_process', 'approved', 'Passed interview / assessment: asd', 12, '2026-03-11 09:54:13'),
(132, 44, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-11 09:55:04'),
(133, 44, 1, 'on_process', 'approved', 'Client confirmed approval: \r\n\r\nDeal Done!', 12, '2026-03-11 10:02:55'),
(134, 31, 2, 'on_process', 'approved', 'Requirements complete: asd', 12, '2026-03-11 10:34:34'),
(135, 44, 1, 'on_process', 'approved', 'Passed interview / assessment:', 12, '2026-03-11 10:45:27'),
(136, 38, 0, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 11:22:49'),
(137, 43, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-11 11:24:27'),
(138, 38, 1, 'on_process', 'pending', 'Client confirmed approval:', 12, '2026-03-11 11:24:46'),
(139, 43, 1, 'on_process', 'pending', 'Passed interview / assessment: asd', 12, '2026-03-11 11:24:52'),
(140, 28, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-11 11:26:28'),
(141, 28, 1, 'on_process', 'approved', 'Client confirmed approval:', 12, '2026-03-11 11:26:34'),
(142, 44, 0, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 11:26:50'),
(143, 44, 1, 'on_process', 'pending', 'Qualified based on evaluation: Qualified based on evaasduation:', 12, '2026-03-11 11:31:19'),
(144, 44, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 11:31:35'),
(145, 28, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 44. Original moved to on_hold.', 12, '2026-03-11 11:31:35'),
(146, 44, 1, 'on_process', 'approved', 'Client confirmed approval:', 12, '2026-03-11 11:36:20'),
(147, 28, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-11 11:37:28'),
(148, 44, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 11:37:37'),
(149, 28, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 44. Original moved to on_hold.', 12, '2026-03-11 11:37:37'),
(150, 44, 1, 'on_process', 'approved', 'Client confirmed approval:', 12, '2026-03-11 11:51:47'),
(151, 24, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 13:13:54'),
(152, 44, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 24. Original moved to on_hold.', 12, '2026-03-11 13:13:54'),
(153, 24, 1, 'on_process', 'pending', 'Qualified based on evaluation:', 12, '2026-03-11 13:14:01'),
(154, 44, 0, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Ready to Work. Description: asd', 12, '2026-03-11 13:27:02'),
(155, 28, 0, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Health Issues Resolved. Description: asdasd', 12, '2026-03-11 13:27:08'),
(156, 29, 2, 'pending', 'on_process', 'Replacement for Lea Catherine Rivera (ID: 31) due to Other.', 12, '2026-03-11 13:27:27'),
(157, 29, 2, 'on_process', 'approved', 'Interview rescheduled: asd', 12, '2026-03-11 13:36:36'),
(158, 32, 2, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 13:36:44'),
(159, 29, 2, 'approved', 'on_hold', 'Replaced by Applicant ID 32. Original moved to on_hold.', 12, '2026-03-11 13:36:44'),
(160, 32, 2, 'on_process', 'approved', 'Client confirmed / Ready: asd', 12, '2026-03-11 13:39:04'),
(161, 31, 2, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 13:39:20'),
(162, 32, 2, 'approved', 'on_hold', 'Replaced by Applicant ID 31. Original moved to on_hold.', 12, '2026-03-11 13:39:20'),
(163, 32, 2, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Ready to Work. Description: asd', 12, '2026-03-11 13:51:48'),
(164, 29, 2, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Ready to Work. Description: asd', 12, '2026-03-11 13:51:56'),
(165, 31, 2, 'on_process', 'approved', 'Requirements complete: asd', 12, '2026-03-11 13:57:59'),
(166, 31, 2, 'on_process', 'approved', 'Interview rescheduled: asd', 12, '2026-03-11 13:58:24'),
(167, 30, 2, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-11 13:58:38'),
(168, 31, 2, 'approved', 'on_hold', 'Replaced by Applicant ID 30. Original moved to on_hold.', 12, '2026-03-11 13:58:38'),
(169, 30, 2, 'on_process', 'pending', 'Client confirmed / Ready: asd', 20, '2026-03-11 15:29:23'),
(170, 29, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 20, '2026-03-11 15:29:51'),
(171, 29, 2, 'on_process', 'approved', 'Requirements complete: asda', 20, '2026-03-11 15:30:02'),
(172, 31, 2, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Personal Problems Solved. Description: asd', 12, '2026-03-11 15:33:16'),
(173, 29, 2, 'on_process', 'pending', 'Client confirmed / Ready: asd', 20, '2026-03-11 15:36:32'),
(174, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 20, '2026-03-11 15:36:39'),
(175, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-12 11:55:12'),
(176, 44, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-12 11:56:50'),
(177, 44, 1, 'on_process', 'approved', 'Qualified based on evaluation: asdasd', 12, '2026-03-12 11:56:57'),
(178, 44, 1, 'on_process', 'pending', 'Ready for deployment / assignment: sa', 12, '2026-03-12 11:57:51'),
(179, 44, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-12 12:01:38'),
(180, 44, 1, 'approved', 'pending', 'Status changed from Approved to Pending', 12, '2026-03-12 12:01:42'),
(181, 44, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-12 13:34:47'),
(182, 44, 1, 'on_process', 'approved', 'Client confirmed approval: asda', 12, '2026-03-12 13:35:31'),
(183, 44, 1, 'approved', 'pending', 'Status changed from Approved to Pending', 12, '2026-03-12 13:35:49'),
(184, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-12 14:47:22'),
(185, 32, 2, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-12 14:47:35'),
(186, 32, 2, 'on_process', 'approved', 'Client confirmed / Ready: asd', 12, '2026-03-12 14:47:58'),
(187, 44, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-12 14:48:34'),
(188, 44, 1, 'on_process', 'approved', 'Qualified based on evaluation:', 12, '2026-03-12 14:57:58'),
(189, 44, 1, 'approved', 'pending', 'Status changed from Approved to Pending', 12, '2026-03-12 15:00:22'),
(190, 44, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-12 15:00:27'),
(191, 44, 1, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-12 15:00:29'),
(192, 44, 1, 'on_process', 'approved', 'Client confirmed approval: asd', 12, '2026-03-12 15:21:22'),
(193, 44, 1, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-12 15:26:05'),
(194, 44, 1, 'on_process', 'approved', 'Passed interview / assessment:', 12, '2026-03-12 15:26:14'),
(195, 44, 1, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-12 15:27:27'),
(196, 31, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-12 15:31:52'),
(197, 31, 2, 'on_process', 'pending', 'Needs further evaluation: asd', 12, '2026-03-12 15:44:21'),
(198, 32, 2, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-12 15:52:30'),
(199, 32, 2, 'on_process', 'approved', 'Qualified based on evaluation: asddddddddddasddddddddddasddddddddddasddddddddddasdddddddddd', 12, '2026-03-12 15:52:39'),
(200, 30, 2, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-22 13:24:51'),
(201, 32, 2, 'approved', 'on_hold', 'Replaced by Applicant ID 30. Original moved to on_hold.', 12, '2026-03-22 13:24:51'),
(202, 32, 2, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Health Issues Resolved. Description: asd', 12, '2026-03-22 13:25:23'),
(203, 32, 2, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-03-23 08:34:25'),
(204, 29, 2, 'on_process', 'approved', 'Cleared for endorsement: asdas', 12, '2026-03-23 08:35:23'),
(205, 44, 1, 'on_process', 'approved', 'Client confirmed approval:', 12, '2026-03-23 08:36:52'),
(206, 44, 1, 'approved', 'pending', 'Status changed from Approved to Pending', 12, '2026-03-23 08:36:59'),
(207, 44, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-23 08:37:06'),
(208, 40, 1, 'on_process', 'approved', 'Client confirmed approval:', 12, '2026-03-23 08:37:16'),
(209, 40, 1, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-23 08:37:21'),
(210, 32, 2, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-23 08:41:44'),
(211, 31, 2, 'pending', 'on_process', 'Status changed from Pending to On process', 12, '2026-03-23 09:00:11'),
(212, 29, 2, 'approved', 'on_process', 'Status changed from Approved to On process', 12, '2026-03-23 09:00:23'),
(213, 29, 2, 'on_process', 'approved', 'Passed interview / assessment: asdasd', 12, '2026-03-23 09:00:33'),
(214, 32, 2, 'on_process', 'pending', 'Client request / feedback: asd', 12, '2026-03-23 09:03:49'),
(215, 31, 2, 'on_process', 'pending', 'Interview reschedule needed: asdasd', 12, '2026-03-23 09:03:55'),
(216, 30, 2, 'on_process', 'pending', 'Documents incomplete / pending: asdasd', 12, '2026-03-23 09:04:00'),
(217, 30, 2, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-03-23 09:05:07'),
(218, 29, 2, 'approved', 'on_hold', 'Replaced by Applicant ID 30. Original moved to on_hold.', 12, '2026-03-23 09:05:07'),
(219, 29, 2, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Personal Problems Solved. Description: asdasd', 12, '2026-03-23 09:10:33'),
(220, 35, 1, 'on_process', 'approved', 'Passed interview / assessment: passed with interview of client', 12, '2026-03-24 08:48:20'),
(221, 40, 1, 'on_process', 'pending', 'Documents incomplete / pending:', 12, '2026-03-24 15:04:33'),
(222, 44, 1, 'on_process', 'pending', 'Client request / feedback: asdasdasd', 12, '2026-03-24 15:04:43'),
(223, 44, 1, 'pending', 'on_process', 'Status changed from Pending to On process', 27, '2026-03-25 11:45:00'),
(224, 44, 1, 'on_process', 'pending', 'Client request / feedback: adada', 27, '2026-03-25 11:45:17'),
(225, 35, 1, 'approved', 'pending', 'Status changed from Approved to Pending', 12, '2026-03-25 13:21:46'),
(226, 32, 2, 'on_process', 'pending', 'Client request / feedback: adssssssss', 12, '2026-03-25 13:22:08'),
(227, 30, 2, 'on_process', 'pending', 'Client request / feedback: asdasadsdasadasd', 12, '2026-03-25 13:22:12'),
(228, 31, 2, 'on_process', 'approved', 'Qualified based on evaluation: awdasdffiedx', 12, '2026-03-26 10:01:08'),
(229, 29, 2, 'on_process', 'approved', 'All requirements completed: dawdawdsss', 12, '2026-03-26 10:01:12'),
(230, 30, 2, 'on_process', 'approved', 'All requirements completed: adasdasd', 12, '2026-03-26 10:08:59');

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

--
-- Dumping data for table `business_unit_id_recycle`
--

INSERT INTO `business_unit_id_recycle` (`id`) VALUES
(5);

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

--
-- Dumping data for table `client_bookings`
--

INSERT INTO `client_bookings` (`id`, `applicant_id`, `business_unit_id`, `services_json`, `appointment_type`, `appointment_date`, `appointment_time`, `client_first_name`, `client_middle_name`, `client_last_name`, `client_phone`, `client_email`, `client_address`, `status`, `created_at`, `updated_at`, `deleted_at`) VALUES
(24, 41, 1, '[\"Cleaning & Housekeeping (General)\"]', 'Office Visit', '2026-03-30', '11:03:00', 'John Adrian', '', 'Cabrito', '09270746258', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 'submitted', '2026-03-24 07:00:57', '2026-03-24 07:00:57', NULL),
(25, 38, 1, '[\"Cleaning & Housekeeping (General)\"]', 'Office Visit', '2026-03-26', '10:42:00', 'Ralph Justine', 'Icay', 'Gallentes', '09270746258', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 'submitted', '2026-03-24 13:43:18', '2026-03-24 13:43:18', NULL),
(27, 29, 2, '[\"Cleaning & Housekeeping (General)\"]', 'Video Call', '2026-03-27', '09:59:00', 'Andrei Jherico', 'Biteno', 'Javillo', '09270746258', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 'submitted', '2026-03-26 01:59:51', '2026-03-26 01:59:51', NULL),
(28, 31, 2, '[\"Laundry & Clothing Care\",\"Pet & Outdoor Maintenance\"]', 'Video Call', '2026-03-28', '10:00:00', 'Renz Roann', 'Batuigas', 'Diaz', '09270746258', 'renzfour19@gmail.com', '2381 luakwhduiawdluawliudwa', 'submitted', '2026-03-26 02:00:28', '2026-03-26 02:00:28', NULL),
(29, 30, 2, '[\"Cooking & Food Service\"]', 'Video Call', '2026-03-27', '10:08:00', 'Bembol', 'B.', 'Roco', '09270746258', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 'submitted', '2026-03-26 02:08:44', '2026-03-26 02:08:44', NULL);

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

--
-- Dumping data for table `content_items`
--

INSERT INTO `content_items` (`id`, `business_unit_id`, `category_id`, `title`, `image_path`, `description`, `display_order`, `is_active`, `created_at`) VALUES
(2, 1, 1, 'about4', 'contents/69abb86477f24_1772861540.jpg', '', 2, 1, '2026-03-07 05:32:20'),
(3, 1, 1, 'about5', 'contents/69abb86478d67_1772861540.jpg', '', 3, 1, '2026-03-07 05:32:20'),
(4, 1, 1, 'about8', 'contents/69abb86479e29_1772861540.jpg', '', 4, 1, '2026-03-07 05:32:20'),
(5, 1, 1, 'about6', 'contents/69abb8647af71_1772861540.jpg', '', 5, 1, '2026-03-07 05:32:20'),
(6, 1, 3, 'CELLPHONE_TECHNICIAN_eef6c313ee', 'contents/69abb8932a922_1772861587.jpg', '', 1, 1, '2026-03-07 05:33:07');

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

--
-- Dumping data for table `invoice_history`
--

INSERT INTO `invoice_history` (`id`, `business_unit_id`, `client_booking_id`, `invoice_num`, `invoice_date`, `due_date`, `paid_date`, `reference_no`, `client_name`, `client_email`, `client_address`, `total_amount`, `applicants_data`, `pdf_filename`, `created_at`, `company_type`, `status`, `payment_provider`, `xendit_invoice_id`, `payment_link`, `payment_status`, `paid_at`) VALUES
(38, 1, 24, 'CSNK-20260325-440', '2026-03-25', '2026-03-26', NULL, 'REF-20260325-594357', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-08\",\"end_date\":\"2026-03-31\",\"days\":24,\"amount\":17000}]', 'CSNK-20260325-440.pdf', '2026-03-25 06:25:22', 'CSNK', 'Pending', 'XENDIT', '69c37fc67cba7679600ae3d9', 'https://checkout-staging.xendit.co/web/69c37fc67cba7679600ae3d9', 'Paid', '2026-03-25 15:32:29'),
(39, 1, 24, 'CSNK-20260325-956', '2026-03-25', '2026-03-26', NULL, 'REF-20260325-533877', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 22000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":22000}]', 'CSNK-20260325-956.pdf', '2026-03-25 07:25:06', 'CSNK', 'Pending', 'XENDIT', '69c38dc67cba7679600afcd4', 'https://checkout-staging.xendit.co/web/69c38dc67cba7679600afcd4', 'Paid', '2026-03-25 15:48:59'),
(40, 1, 24, 'CSNK-20260325-899', '2026-03-25', '2026-03-26', NULL, 'REF-20260325-934413', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 19000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-01\",\"end_date\":\"2026-03-29\",\"days\":29,\"amount\":19000}]', 'CSNK-20260325-899.pdf', '2026-03-25 07:31:15', 'CSNK', 'Pending', 'XENDIT', '69c38f38eea2af3427ae0732', 'https://checkout-staging.xendit.co/web/69c38f38eea2af3427ae0732', 'Paid', '2026-03-25 15:32:59'),
(41, 1, 25, 'CSNK-20260325-918', '2026-03-25', '2026-03-26', NULL, 'REF-20260325-893550', 'Ralph Justine Gallentes', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 21000.00, '[{\"name\":\"Olivia Jane Peterson\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":21000}]', 'CSNK-20260325-918.pdf', '2026-03-25 08:54:29', 'CSNK', 'Pending', 'XENDIT', '69c3a2b9eea2af3427ae2b35', 'https://checkout-staging.xendit.co/web/69c3a2b9eea2af3427ae2b35', 'Pending', NULL),
(42, 2, 27, 'SMC-20260326-717', '2026-03-26', '2026-03-27', NULL, 'REF-20260326-243393', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 30000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":30000}]', 'SMC-20260326-717.pdf', '2026-03-26 02:01:58', 'SMC', 'Pending', 'XENDIT', '69c4938aeea2af3427af6703', 'https://checkout-staging.xendit.co/web/69c4938aeea2af3427af6703', 'Pending', NULL),
(43, 2, 28, 'SMC-20260326-216', '2026-03-26', '2026-03-27', NULL, 'REF-20260326-421907', 'Renz Roann Diaz', 'renzfour19@gmail.com', '2381 luakwhduiawdluawliudwa', 17000.00, '[{\"name\":\"Lea Catherine Fernandez Rivera\",\"start_date\":\"2026-03-04\",\"end_date\":\"2026-03-24\",\"days\":21,\"amount\":17000}]', 'SMC-20260326-216.pdf', '2026-03-26 02:03:48', 'SMC', 'Pending', 'XENDIT', '69c493f87cba7679600c5fa2', 'https://checkout-staging.xendit.co/web/69c493f87cba7679600c5fa2', 'Paid', '2026-03-26 11:51:13'),
(44, 1, 25, 'CSNK-20260326-507', '2026-03-26', '2026-03-27', NULL, 'REF-20260326-503777', 'Ralph Justine Gallentes', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 19000.00, '[{\"name\":\"Olivia Jane Peterson\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":19000}]', 'CSNK-20260326-507.pdf', '2026-03-26 06:05:28', 'CSNK', 'Pending', 'XENDIT', '69c4cc9ceea2af3427afc2ed', 'https://checkout-staging.xendit.co/web/69c4cc9ceea2af3427afc2ed', 'Pending', NULL),
(45, 2, 29, 'SMC-20260326-373', '2026-03-26', '2026-03-27', NULL, 'REF-20260326-764232', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 19000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":19000}]', 'SMC-20260326-373.pdf', '2026-03-26 06:22:43', 'SMC', 'Pending', 'XENDIT', '69c4d0a77cba7679600cc1f6', 'https://checkout-staging.xendit.co/web/69c4d0a77cba7679600cc1f6', 'Pending', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `recycled_ids`
--

CREATE TABLE `recycled_ids` (
  `table_name` varchar(50) NOT NULL,
  `id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recycled_ids`
--

INSERT INTO `recycled_ids` (`table_name`, `id`) VALUES
('applicants', 1),
('applicants', 45);

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
(273, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 08:25:51', '2026-03-26 08:27:35'),
(274, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 08:27:43', NULL),
(275, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 08:29:27', NULL),
(276, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 09:40:14', NULL),
(277, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-26 13:19:34', NULL),
(278, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 13:12:29', NULL);

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