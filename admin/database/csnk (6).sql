-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 10, 2026 at 07:24 AM
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
(1111, 12, 'Login', 'User logged in successfully', '::1', '2026-03-27 05:12:29'),
(1112, 27, 'Login', 'User logged in successfully', '::1', '2026-03-27 06:30:47'),
(1113, 27, 'Logout', 'User logged out', '::1', '2026-03-27 07:03:11'),
(1114, 35, 'Login', 'User logged in successfully', '::1', '2026-03-27 07:03:19'),
(1115, 35, 'Logout', 'User logged out', '::1', '2026-03-27 07:03:33'),
(1116, 33, 'Login', 'User logged in successfully', '::1', '2026-03-27 07:03:48'),
(1117, 12, 'Login', 'User logged in successfully', '::1', '2026-03-30 00:12:19'),
(1118, 12, 'Login', 'User logged in successfully', '::1', '2026-03-30 05:05:49'),
(1119, 12, 'Logout', 'User logged out', '::1', '2026-03-30 05:41:45'),
(1120, 4, 'Logout', 'User logged out', '::1', '2026-03-30 05:42:02'),
(1121, 12, 'Login', 'User logged in successfully', '::1', '2026-03-30 05:42:09'),
(1122, 12, 'Logout', 'User logged out', '::1', '2026-03-30 06:00:06'),
(1123, 31, 'Login', 'User logged in successfully', '::1', '2026-03-30 06:00:22'),
(1124, 31, 'Add Applicant', 'Added new applicant: Renz Roann asdasd', '::1', '2026-03-30 06:01:22'),
(1125, 31, 'Delete Applicant', 'Deleted applicant Renz Roann Batuigas asdasd', '::1', '2026-03-30 06:01:37'),
(1126, 31, 'Logout', 'User logged out', '::1', '2026-03-30 07:22:08'),
(1127, 12, 'Login', 'User logged in successfully', '::1', '2026-03-30 07:22:45'),
(1128, 12, 'Logout', 'User logged out', '::1', '2026-03-30 07:23:50'),
(1129, 4, 'Logout', 'User logged out', '::1', '2026-03-30 07:35:06'),
(1130, 31, 'Login', 'User logged in successfully', '::1', '2026-03-30 07:35:26'),
(1131, 31, 'Permanent Delete', 'Permanently deleted applicant ID 45', '::1', '2026-03-30 07:35:37'),
(1132, 31, 'Delete Applicant', 'Deleted applicant Renz Roann Batuigas Renz Diaz', '::1', '2026-03-30 07:51:21'),
(1133, 31, 'Add Applicant', 'Added new applicant: Renz Roann Diaz', '::1', '2026-03-30 07:54:04'),
(1134, 31, 'Logout', 'User logged out', '::1', '2026-03-30 08:01:21'),
(1135, 12, 'Login', 'User logged in successfully', '::1', '2026-03-30 08:21:36'),
(1136, 12, 'Delete Applicant', 'Deleted applicant asd asd awdasd', '::1', '2026-03-30 08:28:06'),
(1137, 12, 'Delete Applicant', 'Deleted applicant Bruh Trial', '::1', '2026-03-30 08:28:10'),
(1138, 12, 'Login', 'User logged in successfully', '::1', '2026-04-01 00:31:04'),
(1139, 12, 'Add Applicant', 'Added new applicant: asd asd', '::1', '2026-04-01 00:34:07'),
(1140, 12, 'Add Applicant', 'Added new applicant: test2 test2', '::1', '2026-04-01 00:37:10'),
(1141, 12, 'Login', 'User logged in successfully', '::1', '2026-04-06 00:39:34'),
(1142, 12, 'Logout', 'User logged out', '::1', '2026-04-06 02:32:14'),
(1143, 35, 'Login', 'User logged in successfully', '::1', '2026-04-06 02:32:23'),
(1144, 35, 'Logout', 'User logged out', '::1', '2026-04-06 02:50:49'),
(1145, 12, 'Login', 'User logged in successfully', '::1', '2026-04-06 02:50:54'),
(1146, 12, 'Login', 'User logged in successfully', '::1', '2026-04-06 05:17:11'),
(1147, 12, 'Logout', 'User logged out', '::1', '2026-04-06 08:22:43'),
(1148, 12, 'Login', 'User logged in successfully', '::1', '2026-04-08 02:22:37'),
(1149, 27, 'Login', 'User logged in successfully', '::1', '2026-04-08 05:46:50'),
(1150, 27, 'Add Applicant', 'Added new applicant: Ryzza Mae Atayde', '::1', '2026-04-08 05:54:16'),
(1151, 27, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae Benjo Atayde → approved; Reason: Ready for deployment / assignment: Approved with their client', '::1', '2026-04-08 06:02:00'),
(1152, 27, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Performance Issue', '::1', '2026-04-08 06:03:53'),
(1153, 12, 'Login', 'User logged in successfully', '::1', '2026-04-08 07:15:07'),
(1154, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:16:30'),
(1155, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:16:34'),
(1156, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:16:39'),
(1157, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:16:56'),
(1158, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Client Left', '::1', '2026-04-08 07:21:42'),
(1159, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Client Left', '::1', '2026-04-08 07:21:47'),
(1160, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:26:31'),
(1161, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Performance Issue', '::1', '2026-04-08 07:26:41'),
(1162, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Performance Issue', '::1', '2026-04-08 07:26:46'),
(1163, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Performance Issue', '::1', '2026-04-08 07:26:52'),
(1164, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: AWOL', '::1', '2026-04-08 07:28:18'),
(1165, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: AWOL', '::1', '2026-04-08 07:28:23'),
(1166, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:30:59'),
(1167, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:31:03'),
(1168, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:36:27'),
(1169, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Client Left', '::1', '2026-04-08 07:36:54'),
(1170, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Client Left', '::1', '2026-04-08 07:38:09'),
(1171, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:38:31'),
(1172, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Not Finished Contract', '::1', '2026-04-08 07:39:13'),
(1173, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Not Finished Contract', '::1', '2026-04-08 07:40:25'),
(1174, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:47'),
(1175, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:49'),
(1176, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:49'),
(1177, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:49'),
(1178, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:50'),
(1179, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:50'),
(1180, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:50'),
(1181, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:50'),
(1182, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:51'),
(1183, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:51'),
(1184, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:51'),
(1185, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:55'),
(1186, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:55'),
(1187, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:55'),
(1188, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:55'),
(1189, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:55'),
(1190, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:56'),
(1191, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:56'),
(1192, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 07:40:56'),
(1193, 12, 'Login', 'User logged in successfully', '::1', '2026-04-08 07:53:08'),
(1194, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-08 08:11:30'),
(1195, 12, 'Login', 'User logged in successfully', '::1', '2026-04-10 01:30:28'),
(1196, 12, 'Start Replacement', 'Start replacement for Applicant ID 31; Reason: Other', '::1', '2026-04-10 01:30:36'),
(1197, 12, 'Assign Replacement (Turkey/SMC)', 'Assigned Applicant ID 32 as replacement for Original ID 31; original set to On Hold', '::1', '2026-04-10 01:30:41'),
(1198, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-10 01:30:51'),
(1199, 12, 'Start Replacement', 'Start replacement for Applicant ID 50; Reason: Other', '::1', '2026-04-10 01:39:18'),
(1200, 12, 'Update Applicant Status (with report)', 'Updated status for Abigail Nicole Sanders → approved; Reason: All requirements completed: asdasdas', '::1', '2026-04-10 01:39:34'),
(1201, 12, 'Start Replacement', 'Start replacement for Applicant ID 41; Reason: Other', '::1', '2026-04-10 01:39:42'),
(1202, 12, 'Update Applicant Status', 'Updated status for Ryzza Mae Benjo Atayde → pending (CSNK)', '::1', '2026-04-10 01:41:34'),
(1203, 12, 'Start Replacement', 'Start replacement for Applicant ID 41; Reason: Client Left', '::1', '2026-04-10 01:42:49'),
(1204, 12, 'Start Replacement', 'Start replacement for Applicant ID 41; Reason: Client Left', '::1', '2026-04-10 01:42:52'),
(1205, 12, 'Start Replacement', 'Start replacement for Applicant ID 41; Reason: Client Left', '::1', '2026-04-10 01:42:53'),
(1206, 12, 'Start Replacement', 'Start replacement for Applicant ID 41; Reason: Other', '::1', '2026-04-10 01:48:08'),
(1207, 12, 'Start Replacement', 'Start replacement for Applicant ID 41; Reason: Other', '::1', '2026-04-10 01:49:13'),
(1208, 12, 'Assign Replacement', 'Assigned Applicant ID 33 as replacement for Original ID 41; original set to On Hold', '::1', '2026-04-10 01:51:17'),
(1209, 12, 'Revert On Hold Applicant', 'Reverted applicant Abigail Nicole Sanders (ID: 41) from On Hold to Pending. Reason: Ready to Work', '::1', '2026-04-10 01:51:35'),
(1210, 12, 'Permanent Delete', 'Permanently deleted applicant ID 45', '::1', '2026-04-10 01:51:40'),
(1211, 12, 'Permanent Delete', 'Permanently deleted applicant ID 46', '::1', '2026-04-10 01:51:41'),
(1212, 12, 'Permanent Delete', 'Permanently deleted applicant ID 1', '::1', '2026-04-10 01:51:42'),
(1213, 12, 'Update Applicant Status (with report)', 'Updated status for Ava Marie Thompson → pending; Reason: Client request / feedback: asdasd', '::1', '2026-04-10 01:51:49'),
(1214, 12, 'Update Applicant Status (with report)', 'Updated status for Emily Rose Johnson → pending; Reason: Client request / feedback: asdasd', '::1', '2026-04-10 01:51:53'),
(1215, 12, 'Update Applicant Status (with report)', 'Updated status for Olivia Jane Peterson → approved; Reason: All requirements completed: asdasd', '::1', '2026-04-10 01:51:57'),
(1216, 12, 'Start Replacement', 'Start replacement for Applicant ID 38; Reason: Other', '::1', '2026-04-10 01:52:10'),
(1217, 12, 'Assign Replacement', 'Assigned Applicant ID 34 as replacement for Original ID 38; original set to On Hold', '::1', '2026-04-10 01:52:13'),
(1218, 12, 'Update Applicant Status (with report)', 'Updated status for Sophia Claire Ramirez → pending; Reason: Applicant availability issueasdasdas', '::1', '2026-04-10 01:52:21'),
(1219, 12, 'Revert On Hold Applicant', 'Reverted applicant Olivia Jane Peterson (ID: 38) from On Hold to Pending. Reason: Ready to Work', '::1', '2026-04-10 01:52:27'),
(1220, 12, 'Delete Applicant', 'Deleted applicant Ryzza Mae Benjo Atayde', '::1', '2026-04-10 02:03:13'),
(1221, 12, 'Delete Applicant', 'Deleted applicant test2 test2 test2', '::1', '2026-04-10 02:03:15'),
(1222, 12, 'Delete Applicant', 'Deleted applicant asd asd asd', '::1', '2026-04-10 02:03:16'),
(1223, 12, 'Update Applicant Status', 'Updated status for Renz Roann Batuigas Diaz → approved', '::1', '2026-04-10 02:03:19'),
(1224, 12, 'Start Replacement', 'Start replacement for Applicant ID 47; Reason: Other', '::1', '2026-04-10 02:03:25'),
(1225, 12, 'Assign Replacement', 'Assigned Applicant ID 34 as replacement for Original ID 47; original set to On Hold', '::1', '2026-04-10 02:03:28'),
(1226, 12, 'Update Applicant Status (with report)', 'Updated status for Sophia Claire Ramirez → approved; Reason: All requirements completed: asdasdasda', '::1', '2026-04-10 02:03:40'),
(1227, 12, 'Start Replacement', 'Start replacement for Applicant ID 34; Reason: Not Finished Contract', '::1', '2026-04-10 02:03:49'),
(1228, 12, 'Assign Replacement', 'Assigned Applicant ID 33 as replacement for Original ID 34; original set to On Hold', '::1', '2026-04-10 02:04:02'),
(1229, 12, 'Update Applicant Status (with report)', 'Updated status for Ava Marie Thompson → pending; Reason: Client request / feedback: asdasdasda', '::1', '2026-04-10 02:04:09'),
(1230, 12, 'Revert On Hold Applicant', 'Reverted applicant Renz Roann Batuigas Diaz (ID: 47) from On Hold to Pending. Reason: Personal Problems Solved', '::1', '2026-04-10 02:04:14'),
(1231, 12, 'Revert On Hold Applicant', 'Reverted applicant Sophia Claire Ramirez (ID: 34) from On Hold to Pending. Reason: Health Issues Resolved', '::1', '2026-04-10 02:04:18'),
(1232, 12, 'Permanent Delete', 'Permanently deleted applicant ID 48', '::1', '2026-04-10 02:04:21'),
(1233, 12, 'Permanent Delete', 'Permanently deleted applicant ID 49', '::1', '2026-04-10 02:04:23'),
(1234, 12, 'Permanent Delete', 'Permanently deleted applicant ID 50', '::1', '2026-04-10 02:04:24'),
(1235, 12, 'Delete Applicant', 'Deleted applicant Johny Ocamps', '::1', '2026-04-10 02:04:37'),
(1236, 12, 'Update Applicant Status', 'Updated status for Renz Roann Batuigas Diaz → approved', '::1', '2026-04-10 05:10:14');

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
-- Table structure for table `admin_user_business_units`
--

CREATE TABLE `admin_user_business_units` (
  `id` int(10) UNSIGNED NOT NULL,
  `admin_user_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_user_business_units`
--

INSERT INTO `admin_user_business_units` (`id`, `admin_user_id`, `business_unit_id`) VALUES
(1, 4, 1),
(2, 4, 2),
(3, 4, 3),
(4, 4, 4);

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
(31, 2, NULL, 2, 'Lea Catherine', 'Fernandez', 'Rivera', '', '09190456722', '09175346098', 'learivera27@example.com', '1998-12-02', '300 San Guillermo St., Brgy. Hulo, Mandaluyong City', '{\"elementary\":{\"school\":\"Hulo Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Mandaluyong High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"MetroClean\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Ortigas\"}]', '[\"Mandaluyong\",\"Pasig\",\"QC\"]', '[]', '[]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9b841dc91_1770953604.jpg', 'video/698e9b8425cda_1770953604.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'on_hold', NULL, '2026-02-13 03:33:24', '2026-04-10 01:30:41', NULL),
(32, 2, NULL, 2, 'Denise Grace', 'Angeles', 'Mendiola', '', '09956873410', '09359872140', 'denisemendiola33@example.com', '1992-08-19', '5124 A. Bonifacio St., Brgy. Western Bicutan, Taguig City', '{\"elementary\":{\"school\":\"Western Bicutan Elementary School\",\"year\":\"1999\\u20132005\"},\"highschool\":{\"school\":\"Taguig National High School\",\"year\":\"2005\\u20132009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Taguig Home Services\",\"years\":\"2019\\u20132024\",\"role\":\"Housemaid\\/Caregiver\",\"location\":\"BGC\"},{\"company\":\"UrbanClean Agency\",\"years\":\"2016\\u20132019\",\"role\":\"Cleaner\",\"location\":\"Pasay\"}]', '[\"Taguig\",\"Pasay\",\"Makati\"]', '[\"Filipino\",\"English\"]', '[]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9c75149ea_1770953845.jpg', 'video/698e9c751c0ff_1770953845.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'on_process', NULL, '2026-02-13 03:37:25', '2026-04-10 01:30:41', NULL),
(33, 1, NULL, 1, 'Ava', 'Marie', 'Thompson', '', '09999999999', '09999999999', 'email@gmail.com', '1998-02-19', '1234 address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Tertiary Graduate (Bachelor’s Degree)', 3, 'applicants/698e8d360baa7_1770949942.jpg', 'video/698e8d3610907_1770949942.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:32:22', '2026-04-10 02:04:09', NULL),
(34, 1, NULL, 1, 'Sophia', 'Claire', 'Ramirez', '', '09999999999', '09999999999', 'email@gmail.com', '1990-11-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Mandaluyong\",\"makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e8df92b357_1770950137.jpg', 'video/698e8df92cdd7_1770950137.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:35:37', '2026-04-10 02:04:18', NULL),
(35, 1, NULL, 1, 'Isabella', 'Grace', 'Mitchell', '', '09999999999', '09999999999', 'email@gmail.com', '2000-08-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"IT\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"Mandaluyong\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Technical-Vocational / TESDA Graduate', 2, 'applicants/698e8e832247e_1770950275.jpg', 'video/698e8e83233c5_1770950275.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:37:55', '2026-03-25 05:21:46', NULL),
(36, 1, NULL, 1, 'Emily', 'Rose', 'Johnson', '', '09999999999', '09999999999', 'email@gmail.com', '1960-02-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"paranaque\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning &amp;amp; Housekeeping (General)\",\"Cooking &amp;amp; Food Service\",\"Pet &amp;amp; Outdoor Maintenance\"]', 'Full Time', NULL, 'Senior High School Graduate (K-12 Curriculum)', 2, 'applicants/698e8f3716c79_1770950455.jpg', 'video/698e8f231b3bd_1770950435.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:40:35', '2026-04-10 01:51:52', NULL),
(37, 1, NULL, 1, 'Mia', 'Elizabeth', 'Carter', '', '09999999999', '09999999999', 'email@gmail.com', '2001-11-02', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e8fa38a07a_1770950563.jpg', 'video/698e8fa38af70_1770950563.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:42:43', '2026-03-02 01:46:55', NULL),
(38, 1, NULL, 1, 'Olivia', 'Jane', 'Peterson', '', '09999999999', '09999999999', 'email@gmail.com', '1990-03-06', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e901853864_1770950680.jpg', 'video/698e9018547b8_1770950680.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:44:40', '2026-04-10 01:52:27', NULL),
(39, 1, NULL, 1, 'Chloe', 'Ann', 'Sullivan', '', '09999999999', '09999999999', 'email@gmail.com', '1989-01-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Taguig\",\"BGC\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Level (Attended High School)', 2, 'applicants/698e90a11d029_1770950817.jpg', 'video/698e90a11f3d4_1770950817.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:46:57', '2026-03-02 01:46:55', NULL),
(40, 1, NULL, 1, 'Hannah', 'Louise', 'Parker', '', '09999999999', '09999999999', 'email@gmail.com', '1999-08-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Elementary Graduate', 3, 'applicants/698e910d1e60e_1770950925.jpg', 'video/698e910d1fc78_1770950925.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:48:45', '2026-03-24 07:04:33', NULL),
(41, 1, NULL, 1, 'Abigail', 'Nicole', 'Sanders', '', '09999999999', '09999999999', 'email@gmail.com', '2000-11-08', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/698e918576116_1770951045.jpg', 'video/698e918577686_1770951045.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:50:45', '2026-04-10 01:51:35', NULL),
(42, 1, NULL, 1, 'Natalie', 'Faith', 'Rogers', '', '09999999999', '09999999999', 'email@gmail.com', '1999-01-23', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e9220edd8f_1770951200.jpg', 'video/698e9220ee585_1770951200.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:53:20', '2026-03-02 01:46:55', NULL),
(43, 1, NULL, 1, 'Ryzza Mae', 'B.', 'Diaz', '', '09123123718', '09817238712', 'renzdiaz.contact@gmail.com', '2026-02-25', '87412 ajllmdawudawdawdasdawds', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2026 - 2028\",\"role\":\"Housemaid\",\"location\":\"Ermita Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati\"}]', '[\"Makati City\",\"Mandaluyong CIty\"]', '[]', '[\"Cleaning & Housekeeping (General)\",\"Childcare & Maternity (Yaya)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 150.00, 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/6996b581e440f_1771484545.jpg', 'video/6996b581ecad4_1771484545.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', 12, '2026-02-19 07:02:25', '2026-03-21 01:21:48', NULL),
(44, 1, NULL, NULL, 'Johny', '', 'Ocamps', '', '09999999999', '09999999991', '', '2000-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2026 - 2028\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Manila\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', 1000.00, 'Elementary Graduate', 2, 'applicants/69aa7106cc2f4_1772777734.jpg', NULL, NULL, 'iframe', NULL, NULL, NULL, 'deleted', 5, '2026-03-06 06:15:34', '2026-04-10 02:04:37', '2026-04-10 02:04:37'),
(47, 1, 4, NULL, 'Renz Roann', 'Batuigas', 'Diaz', '', '09123861273', '09128361628', 'roannrenz19@gmail.com', '2003-07-19', 'asdasd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2010 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Marikina City\",\"Mandaluyong City\"]', '[\"Filipino\",\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\"]', 'Part Time', 695.00, 'Elementary Graduate', 4, 'applicants/file_69ca2c1c754666.43058126.jpg', 'video/file_69ca2c1c8623f3.47045879.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'approved', 31, '2026-03-30 07:54:04', '2026-04-10 05:10:14', NULL);

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
  `document_type` enum('brgy_clearance','birth_certificate','sss','pagibig','nbi','police_clearance','tin_id','passport') NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_documents`
--

INSERT INTO `applicant_documents` (`id`, `applicant_id`, `business_unit_id`, `document_type`, `file_path`, `uploaded_at`) VALUES
(1, 47, 1, 'brgy_clearance', 'documents/file_69ca2c1c79b589.69367973.jpg', '2026-03-30 07:54:04'),
(2, 47, 1, 'birth_certificate', 'documents/file_69ca2c1c7acc93.42121908.jpg', '2026-03-30 07:54:04'),
(3, 47, 1, 'sss', 'documents/file_69ca2c1c7bb7f2.13861022.jpg', '2026-03-30 07:54:04'),
(4, 47, 1, 'pagibig', 'documents/file_69ca2c1c7cd249.46130190.jpg', '2026-03-30 07:54:04'),
(5, 47, 1, 'nbi', 'documents/file_69ca2c1c7dc726.29013699.jpg', '2026-03-30 07:54:04'),
(6, 47, 1, 'police_clearance', 'documents/file_69ca2c1c7e7a26.09529823.jpg', '2026-03-30 07:54:04'),
(7, 47, 1, 'tin_id', 'documents/file_69ca2c1c8490e5.07959764.jpg', '2026-03-30 07:54:04'),
(8, 47, 1, 'passport', 'documents/file_69ca2c1c8557c2.54434414.jpg', '2026-03-30 07:54:04'),
(9, 48, 1, 'brgy_clearance', 'documents/file_69cc67ff2f8fc5.92152583.jpg', '2026-04-01 00:34:07'),
(10, 48, 1, 'tin_id', 'documents/file_69cc67ff3f1c88.09608429.jpg', '2026-04-01 00:34:07'),
(11, 49, 1, 'brgy_clearance', 'documents/file_69cc68b5f3d7b3.47494196.jpg', '2026-04-01 00:37:10'),
(12, 49, 1, 'pagibig', 'documents/file_69cc68b600c9f3.46219017.jpg', '2026-04-01 00:37:10'),
(13, 49, 1, 'police_clearance', 'documents/file_69cc68b60210c5.09137784.jpg', '2026-04-01 00:37:10'),
(14, 49, 1, 'tin_id', 'documents/file_69cc68b6031347.24392417.jpg', '2026-04-01 00:37:10'),
(15, 50, 1, 'brgy_clearance', 'documents/file_69d5ed882cf379.42899331.jpg', '2026-04-08 05:54:16'),
(16, 50, 1, 'sss', 'documents/file_69d5ed8834cb79.19416020.jpg', '2026-04-08 05:54:16'),
(17, 50, 1, 'nbi', 'documents/file_69d5ed8835dfc9.11285017.jpg', '2026-04-08 05:54:16'),
(18, 50, 1, 'tin_id', 'documents/file_69d5ed8836b790.04674041.jpg', '2026-04-08 05:54:16');

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
(113, NULL, 29, 30, 22, 'Other', 'asdasd', '[]', 'assigned', 12, '2026-03-23 01:04:05', '2026-03-24 06:57:10', '2026-03-23 09:05:07'),
(114, 1, 50, NULL, 30, 'Performance Issue', 'Performance Issue: Laging nakahiga, laging cellphone, lagi nagsusugal', '[]', 'selection', 27, '2026-04-08 06:03:52', '2026-04-08 06:03:52', NULL),
(115, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: Client Requested Repasdlacement: asdasdas', '[]', 'selection', 12, '2026-04-08 07:16:30', '2026-04-08 07:16:30', NULL),
(116, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: Client Requested Repasdlacement: asdasdas', '[]', 'selection', 12, '2026-04-08 07:16:34', '2026-04-08 07:16:34', NULL),
(117, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: Client Requested Repasdlacement: asdasdas', '[]', 'selection', 12, '2026-04-08 07:16:39', '2026-04-08 07:16:39', NULL),
(118, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: Client Requested Repasdlacement: asdasdas', '[]', 'selection', 12, '2026-04-08 07:16:56', '2026-04-08 07:16:56', NULL),
(119, 1, 50, NULL, 30, 'Client Left', 'Client Left: asdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:21:42', '2026-04-08 07:21:42', NULL),
(120, 1, 50, NULL, 30, 'Client Left', 'Client Left: asdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:21:47', '2026-04-08 07:21:47', NULL),
(121, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: asdasdasdsa', '[]', 'selection', 12, '2026-04-08 07:26:31', '2026-04-08 07:26:31', NULL),
(122, 1, 50, NULL, 30, 'Performance Issue', 'Performance Issue: asdasdasda', '[]', 'selection', 12, '2026-04-08 07:26:41', '2026-04-08 07:26:41', NULL),
(123, 1, 50, NULL, 30, 'Performance Issue', 'Performance Issue: asdasdasda', '[]', 'selection', 12, '2026-04-08 07:26:46', '2026-04-08 07:26:46', NULL),
(124, 1, 50, NULL, 30, 'Performance Issue', 'Performance Issue: asdasdasda', '[]', 'selection', 12, '2026-04-08 07:26:52', '2026-04-08 07:26:52', NULL),
(125, 1, 50, NULL, 30, 'AWOL', 'AWOL: asdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:28:18', '2026-04-08 07:28:18', NULL),
(126, 1, 50, NULL, 30, 'AWOL', 'AWOL: asdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:28:23', '2026-04-08 07:28:23', NULL),
(127, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: AWOL: Violation of Company Pasaolicies: asdasdasda', '[]', 'selection', 12, '2026-04-08 07:30:59', '2026-04-08 07:30:59', NULL),
(128, 1, 50, NULL, 30, 'Other', 'Violation of Company Policies: AWOL: Violation of Company Pasaolicies: asdasdasda', '[]', 'selection', 12, '2026-04-08 07:31:03', '2026-04-08 07:31:03', NULL),
(129, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:36:27', '2026-04-08 07:36:27', NULL),
(130, 1, 50, NULL, 30, 'Client Left', 'Client Left: asdasdasd', '[]', 'selection', 12, '2026-04-08 07:36:54', '2026-04-08 07:36:54', NULL),
(131, 1, 50, NULL, 30, 'Client Left', 'Client Left: asdasd', '[]', 'selection', 12, '2026-04-08 07:38:09', '2026-04-08 07:38:09', NULL),
(132, 1, 50, NULL, 30, 'Other', 'Client Requested Replacemenasdt: asdasdasdasdasda', '[]', 'selection', 12, '2026-04-08 07:38:31', '2026-04-08 07:38:31', NULL),
(133, 1, 50, NULL, 30, 'Not Finished Contract', 'Not Finished Contract: asdasdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:39:13', '2026-04-08 07:39:13', NULL),
(134, 1, 50, NULL, 30, 'Not Finished Contract', 'Not Finished Contract: asdasdasdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:24', '2026-04-08 07:40:24', NULL),
(135, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:47', '2026-04-08 07:40:47', NULL),
(136, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:49', '2026-04-08 07:40:49', NULL),
(137, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:49', '2026-04-08 07:40:49', NULL),
(138, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:49', '2026-04-08 07:40:49', NULL),
(139, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:50', '2026-04-08 07:40:50', NULL),
(140, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:50', '2026-04-08 07:40:50', NULL),
(141, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:50', '2026-04-08 07:40:50', NULL),
(142, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:50', '2026-04-08 07:40:50', NULL),
(143, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:50', '2026-04-08 07:40:50', NULL),
(144, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:51', '2026-04-08 07:40:51', NULL),
(145, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:51', '2026-04-08 07:40:51', NULL),
(146, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:55', '2026-04-08 07:40:55', NULL),
(147, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:55', '2026-04-08 07:40:55', NULL),
(148, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:55', '2026-04-08 07:40:55', NULL),
(149, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:55', '2026-04-08 07:40:55', NULL),
(150, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:55', '2026-04-08 07:40:55', NULL),
(151, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:56', '2026-04-08 07:40:56', NULL),
(152, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:56', '2026-04-08 07:40:56', NULL),
(153, 1, 50, NULL, 30, 'Other', 'Client Feedback (Negative): asdasdasd', '[]', 'selection', 12, '2026-04-08 07:40:56', '2026-04-08 07:40:56', NULL),
(154, 1, 50, NULL, 30, 'Other', 'Client Requested Replacement: asdasd', '[]', 'selection', 12, '2026-04-08 08:11:30', '2026-04-08 08:11:30', NULL),
(155, NULL, 31, 32, 28, 'Other', 'asdasd', '[]', 'assigned', 12, '2026-04-10 01:30:36', '2026-04-10 01:30:41', '2026-04-10 09:30:41'),
(156, 1, 50, NULL, 30, 'Other', 'Client Requested Replacement: asdasd', '[]', 'selection', 12, '2026-04-10 01:30:51', '2026-04-10 01:30:51', NULL),
(157, 1, 50, NULL, 30, 'Other', 'Client Requested Replacement: asdasdasd', '[]', 'selection', 12, '2026-04-10 01:39:18', '2026-04-10 01:39:18', NULL),
(158, 1, 41, NULL, 24, 'Other', 'Client Feedback (Negative): asdasdasdasdasd', '[]', 'selection', 12, '2026-04-10 01:39:42', '2026-04-10 01:39:42', NULL),
(159, 1, 41, NULL, 24, 'Client Left', 'Client Left: asdasdasdasdasd', '[]', 'selection', 12, '2026-04-10 01:42:49', '2026-04-10 01:42:49', NULL),
(160, 1, 41, NULL, 24, 'Client Left', 'Client Left: asdasdasdasdasd', '[]', 'selection', 12, '2026-04-10 01:42:52', '2026-04-10 01:42:52', NULL),
(161, 1, 41, NULL, 24, 'Client Left', 'Client Left: asdasdasdasdasd', '[]', 'selection', 12, '2026-04-10 01:42:53', '2026-04-10 01:42:53', NULL),
(162, 1, 41, NULL, 24, 'Other', 'Client Feedback (Negative): asdasdasdasd', '[]', 'selection', 12, '2026-04-10 01:48:08', '2026-04-10 01:48:08', NULL),
(163, 1, 41, 33, 24, 'Other', 'Did Not Report to Client: asdasdasdasda', '[]', 'assigned', 12, '2026-04-10 01:49:13', '2026-04-10 01:51:17', '2026-04-10 09:51:17'),
(164, 1, 38, 34, 25, 'Other', 'Mismatch to Client Requirements: asdasdasda', '[]', 'assigned', 12, '2026-04-10 01:52:10', '2026-04-10 01:52:13', '2026-04-10 09:52:13'),
(165, 1, 47, 34, NULL, 'Other', 'Client Requested Replacement: asdasda', '[]', 'assigned', 12, '2026-04-10 02:03:25', '2026-04-10 02:03:28', '2026-04-10 10:03:28'),
(166, 1, 34, 33, NULL, 'Not Finished Contract', 'Not Finished Contract: asdasdasdsa', '[]', 'assigned', 12, '2026-04-10 02:03:49', '2026-04-10 02:04:02', '2026-04-10 10:04:02');

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

--
-- Dumping data for table `applicant_reports`
--

INSERT INTO `applicant_reports` (`id`, `applicant_id`, `business_unit_id`, `admin_id`, `note_text`, `created_at`) VALUES
(106, 50, 1, 27, 'Replacement Initiated (Reason: Performance Issue)\nPerformance Issue: Laging nakahiga, laging cellphone, lagi nagsusugal', '2026-04-08 14:03:53'),
(107, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: Client Requested Repasdlacement: asdasdas', '2026-04-08 15:16:30'),
(108, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: Client Requested Repasdlacement: asdasdas', '2026-04-08 15:16:34'),
(109, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: Client Requested Repasdlacement: asdasdas', '2026-04-08 15:16:39'),
(110, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: Client Requested Repasdlacement: asdasdas', '2026-04-08 15:16:56'),
(111, 50, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasdasdasd', '2026-04-08 15:21:42'),
(112, 50, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasdasdasd', '2026-04-08 15:21:47'),
(113, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: asdasdasdsa', '2026-04-08 15:26:31'),
(114, 50, 1, 12, 'Replacement Initiated (Reason: Performance Issue)\nPerformance Issue: asdasdasda', '2026-04-08 15:26:41'),
(115, 50, 1, 12, 'Replacement Initiated (Reason: Performance Issue)\nPerformance Issue: asdasdasda', '2026-04-08 15:26:46'),
(116, 50, 1, 12, 'Replacement Initiated (Reason: Performance Issue)\nPerformance Issue: asdasdasda', '2026-04-08 15:26:52'),
(117, 50, 1, 12, 'Replacement Initiated (Reason: AWOL)\nAWOL: asdasdasdasd', '2026-04-08 15:28:18'),
(118, 50, 1, 12, 'Replacement Initiated (Reason: AWOL)\nAWOL: asdasdasdasd', '2026-04-08 15:28:23'),
(119, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: AWOL: Violation of Company Pasaolicies: asdasdasda', '2026-04-08 15:30:59'),
(120, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nViolation of Company Policies: AWOL: Violation of Company Pasaolicies: asdasdasda', '2026-04-08 15:31:03'),
(121, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasdasd', '2026-04-08 15:36:27'),
(122, 50, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasdasd', '2026-04-08 15:36:54'),
(123, 50, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasd', '2026-04-08 15:38:09'),
(124, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Requested Replacemenasdt: asdasdasdasdasda', '2026-04-08 15:38:31'),
(125, 50, 1, 12, 'Replacement Initiated (Reason: Not Finished Contract)\nNot Finished Contract: asdasdasdasdasd', '2026-04-08 15:39:13'),
(126, 50, 1, 12, 'Replacement Initiated (Reason: Not Finished Contract)\nNot Finished Contract: asdasdasdasdasd', '2026-04-08 15:40:24'),
(127, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:47'),
(128, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:49'),
(129, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:49'),
(130, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:49'),
(131, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:50'),
(132, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:50'),
(133, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:50'),
(134, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:50'),
(135, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:51'),
(136, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:51'),
(137, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:51'),
(138, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:55'),
(139, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:55'),
(140, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:55'),
(141, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:55'),
(142, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:55'),
(143, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:56'),
(144, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:56'),
(145, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasd', '2026-04-08 15:40:56'),
(146, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Requested Replacement: asdasd', '2026-04-08 16:11:30'),
(147, 31, 2, 12, 'Replacement Initiated (Reason: Other)\nasdasd', '2026-04-10 09:30:36'),
(148, 31, 2, 12, 'Replaced by Applicant ID 32. Status moved to On Hold.', '2026-04-10 09:30:41'),
(149, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Requested Replacement: asdasd', '2026-04-10 09:30:51'),
(150, 50, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Requested Replacement: asdasdasd', '2026-04-10 09:39:18'),
(151, 41, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasdasdasd', '2026-04-10 09:39:42'),
(152, 41, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasdasdasdasd', '2026-04-10 09:42:49'),
(153, 41, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasdasdasdasd', '2026-04-10 09:42:52'),
(154, 41, 1, 12, 'Replacement Initiated (Reason: Client Left)\nClient Left: asdasdasdasdasd', '2026-04-10 09:42:53'),
(155, 41, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Feedback (Negative): asdasdasdasd', '2026-04-10 09:48:08'),
(156, 41, 1, 12, 'Replacement Initiated (Reason: Other)\nDid Not Report to Client: asdasdasdasda', '2026-04-10 09:49:13'),
(157, 41, 1, 12, 'Replaced by Applicant ID 33. Status moved to On Hold.', '2026-04-10 09:51:17'),
(158, 41, 1, 12, 'Revert to Pending - Reason: Ready to Work. Description: asdasd', '2026-04-10 09:51:35'),
(159, 38, 1, 12, 'Replacement Initiated (Reason: Other)\nMismatch to Client Requirements: asdasdasda', '2026-04-10 09:52:10'),
(160, 38, 1, 12, 'Replaced by Applicant ID 34. Status moved to On Hold.', '2026-04-10 09:52:13'),
(161, 38, 1, 12, 'Revert to Pending - Reason: Ready to Work. Description: asdasd', '2026-04-10 09:52:27'),
(162, 47, 1, 12, 'Replacement Initiated (Reason: Other)\nClient Requested Replacement: asdasda', '2026-04-10 10:03:25'),
(163, 47, 1, 12, 'Replaced by Applicant ID 34. Status moved to On Hold.', '2026-04-10 10:03:28'),
(164, 34, 1, 12, 'Replacement Initiated (Reason: Not Finished Contract)\nNot Finished Contract: asdasdasdsa', '2026-04-10 10:03:49'),
(165, 34, 1, 12, 'Replaced by Applicant ID 33. Status moved to On Hold.', '2026-04-10 10:04:02'),
(166, 47, 1, 12, 'Revert to Pending - Reason: Personal Problems Solved. Description: asdasd', '2026-04-10 10:04:14'),
(167, 34, 1, 12, 'Revert to Pending - Reason: Health Issues Resolved. Description: asdasdas', '2026-04-10 10:04:18');

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
(230, 30, 2, 'on_process', 'approved', 'All requirements completed: adasdasd', 12, '2026-03-26 10:08:59'),
(231, 50, 1, 'on_process', 'approved', 'Ready for deployment / assignment: Approved with their client', 27, '2026-04-08 14:02:00'),
(232, 32, 2, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-04-10 09:30:41'),
(233, 31, 2, 'approved', 'on_hold', 'Replaced by Applicant ID 32. Original moved to on_hold.', 12, '2026-04-10 09:30:41'),
(234, 41, 1, 'on_process', 'approved', 'All requirements completed: asdasdas', 12, '2026-04-10 09:39:34'),
(235, 50, 1, 'approved', 'pending', 'Status changed from Approved to Pending', 12, '2026-04-10 09:41:34'),
(236, 33, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-04-10 09:51:17'),
(237, 41, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 33. Original moved to on_hold.', 12, '2026-04-10 09:51:17'),
(238, 41, 0, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Ready to Work. Description: asdasd', 12, '2026-04-10 09:51:35'),
(239, 33, 1, 'on_process', 'pending', 'Client request / feedback: asdasd', 12, '2026-04-10 09:51:49'),
(240, 36, 1, 'on_process', 'pending', 'Client request / feedback: asdasd', 12, '2026-04-10 09:51:52'),
(241, 38, 1, 'on_process', 'approved', 'All requirements completed: asdasd', 12, '2026-04-10 09:51:57'),
(242, 34, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-04-10 09:52:13'),
(243, 38, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 34. Original moved to on_hold.', 12, '2026-04-10 09:52:13'),
(244, 34, 1, 'on_process', 'pending', 'Applicant availability issueasdasdas', 12, '2026-04-10 09:52:21'),
(245, 38, 0, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Ready to Work. Description: asdasd', 12, '2026-04-10 09:52:27'),
(246, 47, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-04-10 10:03:19'),
(247, 34, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-04-10 10:03:28'),
(248, 47, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 34. Original moved to on_hold.', 12, '2026-04-10 10:03:28'),
(249, 34, 1, 'on_process', 'approved', 'All requirements completed: asdasdasda', 12, '2026-04-10 10:03:40'),
(250, 33, 1, 'pending', 'on_process', 'Replacement assignment — moved from pending to on_process.', 12, '2026-04-10 10:04:02'),
(251, 34, 1, 'approved', 'on_hold', 'Replaced by Applicant ID 33. Original moved to on_hold.', 12, '2026-04-10 10:04:02'),
(252, 33, 1, 'on_process', 'pending', 'Client request / feedback: asdasdasda', 12, '2026-04-10 10:04:09'),
(253, 47, 0, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Personal Problems Solved. Description: asdasd', 12, '2026-04-10 10:04:14'),
(254, 34, 0, 'on_hold', 'pending', 'Reverted from On Hold to Pending. Reason: Health Issues Resolved. Description: asdasdas', 12, '2026-04-10 10:04:18'),
(255, 47, 1, 'pending', 'approved', 'Status changed from Pending to Approved', 12, '2026-04-10 13:10:14');

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
(29, 30, 2, '[\"Cooking & Food Service\"]', 'Video Call', '2026-03-27', '10:08:00', 'Bembol', 'B.', 'Roco', '09270746258', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 'submitted', '2026-03-26 02:08:44', '2026-03-26 02:08:44', NULL),
(30, 50, 1, '[\"Cleaning & Housekeeping (General)\",\"Laundry & Clothing Care\"]', 'Office Visit', '2026-04-10', '10:00:00', 'Renz Roann', 'Batuigas', 'Diaz', '09270746258', 'roannrenz19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 'submitted', '2026-04-08 05:57:35', '2026-04-08 05:57:35', NULL);

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
(45, 2, 29, 'SMC-20260326-373', '2026-03-26', '2026-03-27', NULL, 'REF-20260326-764232', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 19000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":19000}]', 'SMC-20260326-373.pdf', '2026-03-26 06:22:43', 'SMC', 'Pending', 'XENDIT', '69c4d0a77cba7679600cc1f6', 'https://checkout-staging.xendit.co/web/69c4d0a77cba7679600cc1f6', 'Pending', NULL),
(46, 1, 24, 'CSNK-20260327-116', '2026-03-27', '2026-03-28', NULL, 'REF-20260327-669936', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 22000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":22000}]', 'CSNK-20260327-116.pdf', '2026-03-27 08:00:19', 'CSNK', 'Pending', 'XENDIT', '69c63906eea2af3427b1f22c', 'https://checkout-staging.xendit.co/web/69c63906eea2af3427b1f22c', 'Pending', NULL),
(47, 1, 24, 'CSNK-20260330-762', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-257056', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 31000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":31000}]', 'CSNK-20260330-762.pdf', '2026-03-30 02:01:03', 'CSNK', 'Pending', 'XENDIT', '69c9d9517cba767960137811', 'https://checkout-staging.xendit.co/web/69c9d9517cba767960137811', 'Pending', NULL),
(48, 1, 25, 'CSNK-20260330-534', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-234745', 'Ralph Justine Gallentes', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 21000.00, '[{\"name\":\"Olivia Jane Peterson\",\"start_date\":\"2026-03-03\",\"end_date\":\"2026-03-31\",\"days\":29,\"amount\":21000}]', 'CSNK-20260330-534.pdf', '2026-03-30 02:09:43', 'CSNK', 'Pending', 'XENDIT', '69c9db58eea2af3427b6855b', 'https://checkout-staging.xendit.co/web/69c9db58eea2af3427b6855b', 'Paid', '2026-03-30 10:22:30'),
(49, 2, 29, 'SMC-20260330-529', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-808375', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 21000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-31\",\"days\":30,\"amount\":21000}]', 'SMC-20260330-529.pdf', '2026-03-30 02:57:36', 'SMC', 'Pending', 'XENDIT', '69c9e6917cba76796013995b', 'https://checkout-staging.xendit.co/web/69c9e6917cba76796013995b', 'Pending', NULL),
(50, 2, 27, 'SMC-20260330-665', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-514421', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 9000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-10\",\"days\":9,\"amount\":9000}]', 'SMC-20260330-665.pdf', '2026-03-30 02:59:03', 'SMC', 'Pending', 'XENDIT', '69c9e6e9eea2af3427b6a288', 'https://checkout-staging.xendit.co/web/69c9e6e9eea2af3427b6a288', 'Pending', NULL),
(51, 1, 24, 'CSNK-20260330-135', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-852599', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 18000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-10\",\"end_date\":\"2026-03-27\",\"days\":18,\"amount\":18000}]', 'CSNK-20260330-135.pdf', '2026-03-30 03:03:59', 'CSNK', 'Pending', 'XENDIT', '69c9e811eea2af3427b6a5e4', 'https://checkout-staging.xendit.co/web/69c9e811eea2af3427b6a5e4', 'Pending', NULL),
(52, 2, 29, 'SMC-20260330-710', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-773263', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 17000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-18\",\"days\":17,\"amount\":17000}]', 'SMC-20260330-710.pdf', '2026-03-30 03:05:20', 'SMC', 'Pending', 'XENDIT', '69c9e8617cba767960139e7e', 'https://checkout-staging.xendit.co/web/69c9e8617cba767960139e7e', 'Pending', NULL),
(53, 2, 28, 'SMC-20260330-684', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-222501', 'Renz Roann Diaz', 'renzfour19@gmail.com', '2381 luakwhduiawdluawliudwa', 19000.00, '[{\"name\":\"Lea Catherine Fernandez Rivera\",\"start_date\":\"2026-03-03\",\"end_date\":\"2026-03-24\",\"days\":22,\"amount\":19000}]', 'SMC-20260330-684.pdf', '2026-03-30 03:19:56', 'SMC', 'Pending', 'XENDIT', '69c9ebcdeea2af3427b6b003', 'https://checkout-staging.xendit.co/web/69c9ebcdeea2af3427b6b003', 'Pending', NULL),
(54, 1, 24, 'CSNK-20260330-729', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-381785', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-03\",\"end_date\":\"2026-03-17\",\"days\":15,\"amount\":17000}]', 'CSNK-20260330-729.pdf', '2026-03-30 03:23:25', 'CSNK', 'Pending', 'XENDIT', '69c9ec9e7cba76796013a997', 'https://checkout-staging.xendit.co/web/69c9ec9e7cba76796013a997', 'Pending', NULL),
(55, 2, 28, 'SMC-20260330-136', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-789646', 'Renz Roann Diaz', 'renzfour19@gmail.com', '2381 luakwhduiawdluawliudwa', 17000.00, '[{\"name\":\"Lea Catherine Fernandez Rivera\",\"start_date\":\"2026-03-09\",\"end_date\":\"2026-03-25\",\"days\":17,\"amount\":17000}]', 'SMC-20260330-136.pdf', '2026-03-30 03:26:29', 'SMC', 'Pending', 'XENDIT', '69c9ed577cba76796013ab94', 'https://checkout-staging.xendit.co/web/69c9ed577cba76796013ab94', 'Pending', NULL),
(56, 1, 24, 'CSNK-20260330-297', '2026-03-30', '0000-00-00', NULL, 'REF-20260330-564966', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 2231312.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-09\",\"end_date\":\"2026-03-25\",\"days\":17,\"amount\":2231312}]', 'CSNK-20260330-297.pdf', '2026-03-30 03:27:15', 'CSNK', 'Pending', 'XENDIT', '69c9ed847cba76796013abf7', 'https://checkout-staging.xendit.co/web/69c9ed847cba76796013abf7', 'Pending', NULL),
(57, 2, 27, 'SMC-20260330-260', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-909898', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 24000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-25\",\"days\":24,\"amount\":24000}]', 'SMC-20260330-260.pdf', '2026-03-30 03:34:09', 'SMC', 'Pending', 'XENDIT', '69c9ef227cba76796013b071', 'https://checkout-staging.xendit.co/web/69c9ef227cba76796013b071', 'Pending', NULL),
(58, 2, 27, 'SMC-20260330-936', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-875150', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 17000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-03-03\",\"end_date\":\"2026-03-26\",\"days\":24,\"amount\":17000}]', 'SMC-20260330-936.pdf', '2026-03-30 03:36:03', 'SMC', 'Pending', 'XENDIT', '69c9ef94eea2af3427b6b9ca', 'https://checkout-staging.xendit.co/web/69c9ef94eea2af3427b6b9ca', 'Pending', NULL),
(59, 2, 28, 'SMC-20260330-173', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-542313', 'Renz Roann Diaz', 'renzfour19@gmail.com', '2381 luakwhduiawdluawliudwa', 17000.00, '[{\"name\":\"Lea Catherine Fernandez Rivera\",\"start_date\":\"2026-03-10\",\"end_date\":\"2026-03-25\",\"days\":16,\"amount\":17000}]', 'SMC-20260330-173.pdf', '2026-03-30 03:42:31', 'SMC', 'Pending', 'XENDIT', '69c9f1197cba76796013b5c6', 'https://checkout-staging.xendit.co/web/69c9f1197cba76796013b5c6', 'Pending', NULL),
(60, 1, 24, 'CSNK-20260330-757', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-113220', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 18000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-09\",\"end_date\":\"2026-03-26\",\"days\":18,\"amount\":18000}]', 'CSNK-20260330-757.pdf', '2026-03-30 03:43:43', 'CSNK', 'Pending', 'XENDIT', '69c9f1607cba76796013b667', 'https://checkout-staging.xendit.co/web/69c9f1607cba76796013b667', 'Pending', NULL),
(61, 2, 29, 'SMC-20260330-174', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-530858', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 17000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-09\",\"end_date\":\"2026-03-25\",\"days\":17,\"amount\":17000}]', 'SMC-20260330-174.pdf', '2026-03-30 03:53:52', 'SMC', 'Pending', 'XENDIT', '69c9f3c27cba76796013bcda', 'https://checkout-staging.xendit.co/web/69c9f3c27cba76796013bcda', 'Pending', NULL),
(62, 1, 25, 'CSNK-20260330-276', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-981232', 'Ralph Justine Gallentes', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 13000.00, '[{\"name\":\"Olivia Jane Peterson\",\"start_date\":\"2026-03-16\",\"end_date\":\"2026-03-28\",\"days\":13,\"amount\":13000}]', 'CSNK-20260330-276.pdf', '2026-03-30 03:54:51', 'CSNK', 'Pending', 'XENDIT', '69c9f3fdeea2af3427b6c5b7', 'https://checkout-staging.xendit.co/web/69c9f3fdeea2af3427b6c5b7', 'Pending', NULL),
(63, 2, 27, 'SMC-20260330-742', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-670759', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 25000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-26\",\"days\":25,\"amount\":25000}]', 'SMC-20260330-742.pdf', '2026-03-30 03:56:02', 'SMC', 'Pending', 'XENDIT', '69c9f4447cba76796013be4d', 'https://checkout-staging.xendit.co/web/69c9f4447cba76796013be4d', 'Pending', NULL),
(64, 2, 29, 'SMC-20260330-864', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-131565', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 17000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-09\",\"end_date\":\"2026-03-25\",\"days\":17,\"amount\":17000}]', 'SMC-20260330-864.pdf', '2026-03-30 04:01:17', 'SMC', 'Pending', 'XENDIT', '69c9f57f7cba76796013c1f0', 'https://checkout-staging.xendit.co/web/69c9f57f7cba76796013c1f0', 'Pending', NULL),
(65, 1, 25, 'CSNK-20260330-474', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-148818', 'Ralph Justine Gallentes', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 24000.00, '[{\"name\":\"Olivia Jane Peterson\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-25\",\"days\":24,\"amount\":24000}]', 'CSNK-20260330-474.pdf', '2026-03-30 05:10:21', 'CSNK', 'Pending', 'XENDIT', '69ca05ae7cba76796013efca', 'https://checkout-staging.xendit.co/web/69ca05ae7cba76796013efca', 'Pending', NULL),
(66, 1, 25, 'CSNK-20260330-312', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-249340', 'Ralph Justine Gallentes', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 12000.00, '[{\"name\":\"Olivia Jane Peterson\",\"start_date\":\"2026-03-03\",\"end_date\":\"2026-03-16\",\"days\":14,\"amount\":12000}]', 'CSNK-20260330-312.pdf', '2026-03-30 05:10:45', 'CSNK', 'Pending', 'XENDIT', '69ca05c6eea2af3427b6f825', 'https://checkout-staging.xendit.co/web/69ca05c6eea2af3427b6f825', 'Pending', NULL),
(67, 2, 27, 'SMC-20260330-708', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-950598', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 17000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-03-10\",\"end_date\":\"2026-03-24\",\"days\":15,\"amount\":17000}]', 'SMC-20260330-708.pdf', '2026-03-30 05:16:22', 'SMC', 'Pending', 'XENDIT', '69ca0717eea2af3427b6fbfd', 'https://checkout-staging.xendit.co/web/69ca0717eea2af3427b6fbfd', 'Pending', NULL),
(68, 2, 29, 'SMC-20260330-155', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-876322', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 9000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-10\",\"end_date\":\"2026-03-18\",\"days\":9,\"amount\":9000}]', 'SMC-20260330-155.pdf', '2026-03-30 05:17:34', 'SMC', 'Pending', 'XENDIT', '69ca07607cba76796013f46f', 'https://checkout-staging.xendit.co/web/69ca07607cba76796013f46f', 'Pending', NULL),
(69, 1, 24, 'CSNK-20260330-589', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-914595', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 1990.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-03-02\",\"end_date\":\"2026-03-17\",\"days\":16,\"amount\":1990}]', 'CSNK-20260330-589.pdf', '2026-03-30 05:31:04', 'CSNK', 'Pending', 'XENDIT', '69ca0a8a7cba76796013fd5c', 'https://checkout-staging.xendit.co/web/69ca0a8a7cba76796013fd5c', 'Pending', NULL),
(70, 2, 29, 'SMC-20260330-146', '2026-03-30', '2026-03-31', NULL, 'REF-20260330-644924', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 22000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-03-03\",\"end_date\":\"2026-03-24\",\"days\":22,\"amount\":22000}]', 'SMC-20260330-146.pdf', '2026-03-30 05:35:03', 'SMC', 'Pending', 'XENDIT', '69ca0b79eea2af3427b707e8', 'https://checkout-staging.xendit.co/web/69ca0b79eea2af3427b707e8', 'Pending', NULL),
(71, 1, 24, 'CSNK-20260406-114', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-839787', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-04-06\",\"end_date\":\"2026-04-28\",\"days\":23,\"amount\":17000}]', 'CSNK-20260406-114.pdf', '2026-04-06 02:51:48', 'CSNK', 'Pending', 'XENDIT', '69d31fb11c934dec38d61671', 'https://checkout-staging.xendit.co/web/69d31fb11c934dec38d61671', 'Pending', NULL),
(72, 2, 29, 'SMC-20260406-271', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-594583', 'Bembol Roco', 'renzfour19@gmail.com', '2461 Princess Floresca St. Pandacan, Manila', 17000.00, '[{\"name\":\"Lorna Fe Bagtas Malabanan\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-21\",\"days\":15,\"amount\":17000}]', 'SMC-20260406-271.pdf', '2026-04-06 03:08:08', 'SMC', 'Pending', 'XENDIT', '69d323843263d1649f23bbb1', 'https://checkout-staging.xendit.co/web/69d323843263d1649f23bbb1', 'Pending', NULL),
(73, 1, 24, 'CSNK-20260406-620', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-764117', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-23\",\"days\":17,\"amount\":17000}]', 'CSNK-20260406-620.pdf', '2026-04-06 03:12:06', 'CSNK', 'Pending', 'XENDIT', '69d324721c934dec38d61ccf', 'https://checkout-staging.xendit.co/web/69d324721c934dec38d61ccf', 'Pending', NULL),
(74, 2, 28, 'SMC-20260406-785', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-381379', 'Renz Roann Diaz', 'renzfour19@gmail.com', '2381 luakwhduiawdluawliudwa', 30000.00, '[{\"name\":\"Lea Catherine Fernandez Rivera\",\"start_date\":\"2026-03-31\",\"end_date\":\"2026-04-29\",\"days\":30,\"amount\":30000}]', 'SMC-20260406-785.pdf', '2026-04-06 05:18:22', 'SMC', 'Pending', 'XENDIT', '69d3420a3263d1649f23e95a', 'https://checkout-staging.xendit.co/web/69d3420a3263d1649f23e95a', 'Pending', NULL),
(75, 1, 24, 'CSNK-20260406-821', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-192180', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-29\",\"days\":23,\"amount\":17000}]', 'CSNK-20260406-821.pdf', '2026-04-06 07:44:06', 'CSNK', 'Pending', 'XENDIT', '69d364321c934dec38d68747', 'https://checkout-staging.xendit.co/web/69d364321c934dec38d68747', 'Pending', NULL),
(76, 2, 27, 'SMC-20260406-335', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-462577', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 17000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-23\",\"days\":17,\"amount\":17000}]', 'SMC-20260406-335.pdf', '2026-04-06 07:46:11', 'SMC', 'Pending', 'XENDIT', '69d364b03263d1649f24289a', 'https://checkout-staging.xendit.co/web/69d364b03263d1649f24289a', 'Pending', NULL),
(77, 1, 24, 'CSNK-20260406-157', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-954067', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-23\",\"days\":17,\"amount\":17000}]', 'CSNK-20260406-157.pdf', '2026-04-06 07:57:05', 'CSNK', 'Pending', 'XENDIT', '69d3673d1c934dec38d68c92', 'https://checkout-staging.xendit.co/web/69d3673d1c934dec38d68c92', 'Pending', NULL),
(78, 2, 27, 'SMC-20260406-201', '2026-04-06', '2026-04-07', NULL, 'REF-20260406-803559', 'Andrei Jherico Javillo', 'renzfour19@gmail.com', '381lkseajhdawdawdaw', 18000.00, '[{\"name\":\"Charmaine Rose Dimapilis Jimenez\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-23\",\"days\":17,\"amount\":18000}]', 'SMC-20260406-201.pdf', '2026-04-06 08:07:33', 'SMC', 'Pending', 'XENDIT', '69d369b11c934dec38d6917e', 'https://checkout-staging.xendit.co/web/69d369b11c934dec38d6917e', 'Pending', NULL),
(79, 1, 24, 'CSNK-20260408-697', '2026-04-08', '2026-04-08', NULL, 'REF-20260408-507406', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 19000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-23\",\"days\":17,\"amount\":19000}]', 'CSNK-20260408-697.pdf', '2026-04-08 02:23:02', 'CSNK', 'Pending', 'XENDIT', '69d5bbf1da22a35849935ac6', 'https://checkout-staging.xendit.co/web/69d5bbf1da22a35849935ac6', 'Pending', NULL),
(80, 1, 24, 'CSNK-20260410-314', '2026-04-10', '2026-04-11', NULL, 'REF-20260410-165612', 'John Adrian Cabrito', 'renzfour19@gmail.com', '666 Paco Hellfire St. Paco Manila', 17000.00, '[{\"name\":\"Abigail Nicole Sanders\",\"start_date\":\"2026-04-07\",\"end_date\":\"2026-04-23\",\"days\":17,\"amount\":17000}]', 'CSNK-20260410-314.pdf', '2026-04-10 03:45:12', 'CSNK', 'Pending', 'XENDIT', '69d872317317077585fd61c2', 'https://checkout-staging.xendit.co/web/69d872317317077585fd61c2', 'Pending', NULL);

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
('applicants', 45),
('applicants', 46),
('applicants', 48),
('applicants', 49),
('applicants', 50);

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
(278, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 13:12:29', NULL),
(279, 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 14:30:47', '2026-03-27 15:03:11'),
(280, 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 15:03:19', '2026-03-27 15:03:33'),
(281, 33, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-27 15:03:48', NULL),
(282, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 08:12:19', NULL),
(283, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:05:49', '2026-03-30 13:41:45'),
(284, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 13:42:09', '2026-03-30 14:00:06'),
(285, 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 14:00:22', '2026-03-30 15:22:08'),
(286, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 15:22:45', '2026-03-30 15:23:50'),
(287, 31, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 15:35:26', '2026-03-30 16:01:21'),
(288, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-03-30 16:21:36', NULL),
(289, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-01 08:31:04', NULL),
(290, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 08:39:34', '2026-04-06 10:32:14'),
(291, 35, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 10:32:23', '2026-04-06 10:50:49'),
(292, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 10:50:54', NULL),
(293, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-06 13:17:11', '2026-04-06 16:22:43'),
(294, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 10:22:37', NULL),
(295, 27, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 13:46:50', NULL),
(296, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 15:15:07', NULL),
(297, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-08 15:53:08', NULL),
(298, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-10 09:30:28', NULL);

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
-- Indexes for table `admin_user_business_units`
--
ALTER TABLE `admin_user_business_units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_admin_bu` (`admin_user_id`,`business_unit_id`),
  ADD KEY `idx_admin_user` (`admin_user_id`),
  ADD KEY `idx_bu` (`business_unit_id`);

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
  ADD UNIQUE KEY `uniq_applicant_doc` (`applicant_id`,`document_type`),
  ADD KEY `idx_app_docs_applicant` (`applicant_id`),
  ADD KEY `idx_app_docs_bu` (`business_unit_id`);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1237;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `admin_user_business_units`
--
ALTER TABLE `admin_user_business_units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `applicant_replacements`
--
ALTER TABLE `applicant_replacements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=256;

--
-- AUTO_INCREMENT for table `business_units`
--
ALTER TABLE `business_units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `client_bookings`
--
ALTER TABLE `client_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=299;

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
