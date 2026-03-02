-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 02, 2026 at 03:52 AM
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
(396, 12, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae B. Diaz → pending; Reason: no client', '::1', '2026-02-23 13:22:23'),
(397, 12, 'Update Applicant Status', 'Updated status for Denise Grace Angeles Mendiola → pending', '::1', '2026-02-23 13:25:36'),
(398, 12, 'Revert On Hold Applicant', 'Reverted applicant Maria Lourdes Santos Cruz (ID: 23) from On Hold to Pending. Reason: Documents Complete', '::1', '2026-02-23 13:27:27'),
(399, 12, 'Blacklist Applicant', 'Blacklisted applicant Ryzza Mae B. Diaz (ID: 43) - Reason: sad', '::1', '2026-02-23 13:28:25'),
(400, 12, 'Add Applicant Report', 'Applicant ID 23: asdasd', '::1', '2026-02-23 13:29:26'),
(401, 12, 'Update Applicant Status', 'Updated status for Denise Grace Angeles Mendiola → on_process', '::1', '2026-02-23 13:31:00'),
(402, 12, 'Update Applicant Status (with report)', 'Updated status for Denise Grace Angeles Mendiola → approved; Reason: Client confirmed / Ready: settled', '::1', '2026-02-23 13:31:15'),
(403, 12, 'Update Applicant Status', 'Updated status for Denise Grace Angeles Mendiola → pending', '::1', '2026-02-23 13:31:20'),
(404, 12, 'Revert Blacklist', 'Reverted blacklist for applicant Ryzza Mae B. Diaz (ID: 43) - Compliance note: sawdas', '::1', '2026-02-23 13:32:00'),
(405, 12, 'Update Applicant Status', 'Updated status for Lorna Fe Bagtas Malabanan → on_process', '::1', '2026-02-23 13:36:03'),
(406, 12, 'Update Applicant Status (with report)', 'Updated status for Lorna Fe Bagtas Malabanan → approved; Reason: Client confirmed / Ready: awdasd', '::1', '2026-02-23 13:36:16'),
(407, 12, 'Update Applicant Status', 'Updated status for Ryzza Mae B. Diaz → on_process', '::1', '2026-02-23 13:39:24'),
(408, 12, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae B. Diaz → approved; Reason: Client confirmed / Ready: awdasd', '::1', '2026-02-23 13:39:45'),
(409, 12, 'Update Applicant Status', 'Updated status for Ryzza Mae B. Diaz → on_process', '::1', '2026-02-23 13:40:34'),
(410, 12, 'Update Applicant Status', 'Updated status for Lorna Fe Bagtas Malabanan → pending', '::1', '2026-02-23 13:40:37'),
(411, 12, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae B. Diaz → pending; Reason: Interview rescheduled: awdas', '::1', '2026-02-23 13:40:48'),
(412, 12, 'Add Applicant Report', 'Applicant ID 23: czxcz', '::1', '2026-02-23 13:41:06'),
(413, 12, 'Update Applicant Status', 'Updated status for Ryzza Mae B. Diaz → approved', '::1', '2026-02-23 13:41:28'),
(414, 12, 'Start Replacement', 'Start replacement for Applicant ID 43; Reason: Other', '::1', '2026-02-23 13:41:36'),
(415, 12, 'Assign Replacement', 'Assigned Applicant ID 28 as replacement for Original ID 43', '::1', '2026-02-23 13:41:38'),
(416, 12, 'Revert On Hold Applicant', 'Reverted applicant Ryzza Mae B. Diaz (ID: 43) from On Hold to Pending. Reason: Documents Complete', '::1', '2026-02-23 13:42:07'),
(417, 12, 'Update Applicant Status (with report)', 'Updated status for Rowena Liza Cruz Mariano → pending; Reason: Interview rescheduled: awdas', '::1', '2026-02-23 13:42:55'),
(418, 12, 'Update Applicant Status', 'Updated status for Lea Catherine Fernandez Rivera → approved', '::1', '2026-02-23 13:49:40'),
(419, 12, 'Update Applicant Status', 'Updated status for Ryzza Mae B. Diaz → on_process', '::1', '2026-02-23 14:05:06'),
(420, 12, 'Update Applicant Status', 'Updated status for Lea Catherine Fernandez Rivera → pending', '::1', '2026-02-23 14:05:31'),
(421, 12, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae B. Diaz → pending; Reason: Requirements complete: awdawds', '::1', '2026-02-23 14:05:45'),
(422, 12, 'Update Applicant Status', 'Updated status for Ryzza Mae B. Diaz → on_process', '::1', '2026-02-23 14:13:09'),
(423, 12, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae B. Diaz → pending; Reason: Client confirmed / Ready: awdas', '::1', '2026-02-23 14:50:02'),
(424, 5, 'Login', 'User logged in successfully', '127.0.0.1', '2026-02-24 02:55:21'),
(425, 5, 'Logout', 'User logged out', '127.0.0.1', '2026-02-24 02:56:44'),
(426, 5, 'Login', 'User logged in successfully', '127.0.0.1', '2026-02-24 02:57:03'),
(427, 5, 'Logout', 'User logged out', '127.0.0.1', '2026-02-24 03:18:19'),
(428, 5, 'Login', 'User logged in successfully', '127.0.0.1', '2026-02-24 03:18:30'),
(429, 5, 'Update Applicant', 'Updated applicant Ryzza Mae B. Diaz (ID: 43)', '127.0.0.1', '2026-02-24 03:19:04'),
(430, 5, 'Update Applicant Status (with report)', 'Updated status for Ryzza Mae B. Diaz → approved; Reason: dasdasfafsada', '127.0.0.1', '2026-02-24 03:19:12'),
(431, 5, 'Logout', 'User logged out', '127.0.0.1', '2026-02-24 03:26:41'),
(432, 5, 'Login', 'User logged in successfully', '127.0.0.1', '2026-02-24 03:26:49'),
(433, 5, 'Update Applicant', 'Updated applicant Denise Grace Angeles Mendiola (ID: 32)', '127.0.0.1', '2026-02-24 05:57:20'),
(434, 5, 'Update Applicant', 'Updated applicant Lea Catherine Fernandez Rivera (ID: 31)', '127.0.0.1', '2026-02-24 06:58:45'),
(435, 12, 'Login', 'User logged in successfully', '::1', '2026-02-25 00:44:39'),
(436, 12, 'Delete Account', 'Deleted ID 16', '::1', '2026-02-25 01:04:58'),
(437, 12, 'Delete Account', 'Deleted ID 13', '::1', '2026-02-25 01:05:00'),
(438, 12, 'Delete Account', 'Deleted ID 15', '::1', '2026-02-25 01:05:07'),
(439, 12, 'Delete Account', 'Deleted ID 14', '::1', '2026-02-25 01:05:10'),
(440, 12, 'Create Account', 'Created employee smc001 (smc)', '::1', '2026-02-25 01:05:52'),
(441, 12, 'Create Account', 'Created employee csnk001 (csnk)', '::1', '2026-02-25 01:06:21'),
(442, 12, 'Logout', 'User logged out', '::1', '2026-02-25 01:10:02'),
(443, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 01:10:09'),
(444, 17, 'Logout', 'User logged out', '::1', '2026-02-25 01:14:22'),
(445, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 01:14:28'),
(446, 18, 'Logout', 'User logged out', '::1', '2026-02-25 01:18:31'),
(447, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 01:18:37'),
(448, 17, 'Logout', 'User logged out', '::1', '2026-02-25 01:34:59'),
(449, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 01:35:05'),
(450, 17, 'Logout', 'User logged out', '::1', '2026-02-25 01:43:50'),
(451, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 01:43:58'),
(452, 18, 'Logout', 'User logged out', '::1', '2026-02-25 01:44:07'),
(453, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 01:44:13'),
(454, 17, 'Logout', 'User logged out', '::1', '2026-02-25 03:27:13'),
(455, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 03:27:19'),
(456, 18, 'Logout', 'User logged out', '::1', '2026-02-25 03:27:37'),
(457, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 03:27:43'),
(458, 17, 'Logout', 'User logged out', '::1', '2026-02-25 03:36:39'),
(459, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 03:36:45'),
(460, 18, 'Logout', 'User logged out', '::1', '2026-02-25 03:36:55'),
(461, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 03:37:02'),
(462, 17, 'Logout', 'User logged out', '::1', '2026-02-25 03:56:16'),
(463, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 03:56:42'),
(464, 18, 'Logout', 'User logged out', '::1', '2026-02-25 03:58:01'),
(465, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 03:58:09'),
(466, 17, 'Logout', 'User logged out', '::1', '2026-02-25 05:39:32'),
(467, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 05:39:48'),
(468, 17, 'Logout', 'User logged out', '::1', '2026-02-25 05:49:39'),
(469, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 05:49:45'),
(470, 18, 'Logout', 'User logged out', '::1', '2026-02-25 05:50:08'),
(471, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 05:50:13'),
(472, 17, 'Logout', 'User logged out', '::1', '2026-02-25 06:23:27'),
(473, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 06:23:40'),
(474, 18, 'Logout', 'User logged out', '::1', '2026-02-25 06:25:50'),
(475, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 06:25:58'),
(476, 17, 'Logout', 'User logged out', '::1', '2026-02-25 06:31:10'),
(477, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 06:31:19'),
(478, 18, 'Logout', 'User logged out', '::1', '2026-02-25 06:31:26'),
(479, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 06:31:37'),
(480, 17, 'Logout', 'User logged out', '::1', '2026-02-25 06:38:52'),
(481, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 06:39:16'),
(482, 18, 'Logout', 'User logged out', '::1', '2026-02-25 06:39:25'),
(483, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 06:39:34'),
(484, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 12:26:05'),
(485, 17, 'Logout', 'User logged out', '::1', '2026-02-25 12:33:18'),
(486, 18, 'Login', 'User logged in successfully', '::1', '2026-02-25 12:33:24'),
(487, 18, 'Logout', 'User logged out', '::1', '2026-02-25 12:33:43'),
(488, 17, 'Login', 'User logged in successfully', '::1', '2026-02-25 12:33:51'),
(489, 17, 'Login', 'User logged in successfully', '::1', '2026-02-26 00:44:32'),
(490, 17, 'Logout', 'User logged out', '::1', '2026-02-26 00:48:08'),
(491, 5, 'Login', 'User logged in successfully', '::1', '2026-02-26 00:48:11'),
(492, 5, 'Logout', 'User logged out', '::1', '2026-02-26 00:48:47'),
(493, 17, 'Login', 'User logged in successfully', '::1', '2026-02-26 00:49:15'),
(494, 17, 'Logout', 'User logged out', '::1', '2026-02-26 04:00:48'),
(495, 5, 'Login', 'User logged in successfully', '::1', '2026-02-26 05:07:04'),
(496, 5, 'Add Applicant', 'Added new applicant: Mhi Mha Diaz', '::1', '2026-02-26 05:08:29'),
(497, 5, 'Delete Applicant', 'Deleted applicant Mhi Mha Diaz', '::1', '2026-02-26 05:19:01'),
(498, 5, 'Add Applicant', 'Added new applicant: Mhi Mha Diaz', '::1', '2026-02-26 05:20:12'),
(499, 5, 'Add Applicant', 'Added new applicant: Mhi Mha Diaz', '::1', '2026-02-26 05:30:39'),
(500, 5, 'Logout', 'User logged out', '::1', '2026-02-26 05:32:55'),
(501, 17, 'Login', 'User logged in successfully', '::1', '2026-02-26 05:33:00'),
(502, 17, 'Logout', 'User logged out', '::1', '2026-02-26 05:34:42'),
(503, 5, 'Login', 'User logged in successfully', '::1', '2026-02-26 05:34:46'),
(504, 5, 'Add Applicant', 'Added new applicant: Mhi Mha Diaz', '::1', '2026-02-26 05:35:45'),
(505, 5, 'Logout', 'User logged out', '::1', '2026-02-26 05:36:29'),
(506, 12, 'Login', 'User logged in successfully', '::1', '2026-02-26 05:36:48'),
(507, 12, 'Logout', 'User logged out', '::1', '2026-02-26 05:37:41'),
(508, 5, 'Login', 'User logged in successfully', '::1', '2026-02-26 05:37:45'),
(509, 5, 'Logout', 'User logged out', '::1', '2026-02-26 05:37:52'),
(510, 17, 'Login', 'User logged in successfully', '::1', '2026-02-26 05:37:55'),
(511, 17, 'Logout', 'User logged out', '::1', '2026-02-26 06:07:32'),
(512, 5, 'Login', 'User logged in successfully', '::1', '2026-02-26 06:07:43'),
(513, 5, 'Create Account', 'Created super_admin SMCsuper', '::1', '2026-02-26 06:09:06'),
(514, 5, 'Logout', 'User logged out', '::1', '2026-02-26 06:09:12'),
(515, 19, 'Login', 'User logged in successfully', '::1', '2026-02-26 06:09:17'),
(516, 19, 'Logout', 'User logged out', '::1', '2026-02-26 06:09:24'),
(517, 19, 'Login', 'User logged in successfully', '::1', '2026-02-26 06:09:29'),
(518, 19, 'Logout', 'User logged out', '::1', '2026-02-26 06:11:03'),
(519, 17, 'Login', 'User logged in successfully', '::1', '2026-02-26 06:11:09'),
(520, 17, 'Logout', 'User logged out', '127.0.0.1', '2026-02-26 06:54:08'),
(521, 17, 'Login', 'User logged in successfully', '127.0.0.1', '2026-02-26 06:54:12'),
(522, 17, 'Logout', 'User logged out', '::1', '2026-02-26 08:11:23'),
(523, 17, 'Login', 'User logged in successfully', '::1', '2026-02-26 08:11:27'),
(524, 5, 'Login', 'User logged in successfully', '::1', '2026-03-01 05:07:52'),
(525, 17, 'Login', 'User logged in successfully', '::1', '2026-03-02 00:44:07'),
(526, 17, 'Logout', 'User logged out', '::1', '2026-03-02 00:55:03'),
(527, 5, 'Login', 'User logged in successfully', '::1', '2026-03-02 00:55:08'),
(528, 5, 'Add Applicant', 'Added new applicant: Johnny Ocampo', '::1', '2026-03-02 00:56:46'),
(529, 5, 'Add Applicant', 'Added new applicant: Trial Trial', '::1', '2026-03-02 00:57:46'),
(530, 5, 'Logout', 'User logged out', '::1', '2026-03-02 01:01:00'),
(531, 17, 'Login', 'User logged in successfully', '::1', '2026-03-02 01:01:04'),
(532, 17, 'Add Applicant', 'Added new applicant: Trial Trial', '::1', '2026-03-02 01:07:53'),
(533, 17, 'Add Applicant', 'Added new applicant: Trial Trial', '::1', '2026-03-02 01:09:52'),
(534, 17, 'Add Applicant', 'Added new applicant: china Trial', '::1', '2026-03-02 02:43:10');

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
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `email`, `password`, `full_name`, `avatar`, `role`, `agency`, `business_unit_id`, `status`, `created_at`, `updated_at`) VALUES
(4, 'renzadmin', 'renzdiaz.contact@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$US9RRDloSHR3MGlzeUdGdw$cjNozvyDewv1phUaRVyn/6zcDKOdoSGJp1fBt5MABFE', 'Renz Diaz', 'avatars/699556f4c657c_1771394804.jpg', 'super_admin', NULL, NULL, 'active', '2026-02-07 10:20:55', '2026-02-18 06:06:44'),
(5, 'elliadmin', 'elli@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cFZvOHZCckJkcDd4a0Y1cA$d+28H23RKZagXG81OSdY8xWa8x2KNSFuHip8xsxI2No', 'John Ellijah', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-07 10:21:28', '2026-02-26 08:05:17'),
(6, 'andreiadmin', 'andrei@gmail.com', '$2y$10$ROQGHUJso58ON6NCsv2PRO14x3Nviq3fZrkEU8KLne6BTEbVuhSq2', 'Andrei Javillo', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-07 10:22:05', '2026-02-07 10:22:05'),
(7, 'ralphadmin', 'ralph@gmail.com', '$2y$10$MUi6.7QJykPG48jx9e8lLu2V72JRHYu91.aRd5LFviHcJokQfvaf2', 'Ralph Justine Gallentes', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-10 00:32:00', '2026-02-10 00:32:00'),
(8, 'cabritoadmin', 'cabs@gmail.com', '$2y$10$AbWEDXv5fqBAkhk1quS.7.eJKD2uyUyenhinmN906bbJlePsxOlSq', 'John Adrian Cabrito', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-10 00:32:53', '2026-02-10 00:32:53'),
(12, 'jmpogi', 'jm@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$QkMvd1FUc2Q0bnBjWHB0Uw$kltUwYy7N9gm+yGcuxlWqQFXnwD/EPRKRexQ1sDBYQM', 'John Michael Masmela', 'avatars/699c53d80ff2a_1771852760.png', 'admin', NULL, NULL, 'active', '2026-02-12 02:33:42', '2026-02-23 13:19:20'),
(17, 'smc001', 'smc001@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$S3BRVFB6YjIxcC5nUDI0Tw$BVJWYVKCIl952PAdRuNwYf4ovRdfKmpAOptWrOu6kvU', 'smc001', NULL, 'super_admin', 'smc', NULL, 'active', '2026-02-25 01:05:52', '2026-02-26 06:10:58'),
(18, 'csnk001', 'csnk001@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$cTRHa1lhLkJvQjYwWlFpYg$7QXiD7qZtT316LFZErAQBtqSLkiorr/BSe2rZGecD58', 'csnk001', NULL, 'employee', 'csnk', NULL, 'active', '2026-02-25 01:06:21', '2026-02-25 01:06:21'),
(19, 'SMCsuper', 'ocampojohn13@gmail.com', '$argon2id$v=19$m=65536,t=4,p=1$SVhESWoweG4xVURpLk9ZNg$3nlUFoWx4wueaYi52OP0zi/sXT13cP3UWzZRtZIAjvg', 'super', NULL, 'super_admin', NULL, NULL, 'active', '2026-02-26 06:09:06', '2026-02-26 06:09:06');

-- --------------------------------------------------------

--
-- Table structure for table `admin_user_business_units`
--

CREATE TABLE `admin_user_business_units` (
  `admin_user_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agencies`
--

CREATE TABLE `agencies` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 1
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
  `id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
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
  `educational_attainment` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `work_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `preferred_location` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `languages` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `specialization_skills` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicants`
--

INSERT INTO `applicants` (`id`, `business_unit_id`, `country_id`, `first_name`, `middle_name`, `last_name`, `suffix`, `phone_number`, `alt_phone_number`, `email`, `date_of_birth`, `address`, `educational_attainment`, `work_history`, `preferred_location`, `languages`, `specialization_skills`, `employment_type`, `daily_rate`, `education_level`, `years_experience`, `picture`, `video_url`, `video_provider`, `video_type`, `video_title`, `video_thumbnail_url`, `video_duration_seconds`, `status`, `created_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(23, 1, 1, 'Maria Lourdes', 'Santos', 'Cruz', '', '09124567831', '09167345218', 'maria.cruz28@example.com', '1997-03-14', '1241 Ilang‑Ilang St., Brgy. 105, Tondo, Manila', '{\"elementary\":{\"school\":\"Jose Corazon de Jesus Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Tondo High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeepe\",\"location\":\"Pasay\"}]', '[\"Manila\",\"Pasay\",\"Makati\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\"]', 'Full Time', 700.00, 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9436d886b_1770951734.jpg', 'video/698e9436e46e0_1770951734.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:02:14', '2026-03-02 01:46:55', NULL),
(24, 1, 1, 'Joanna Marie', 'Pascual', 'Dela Torre', '', '09983457621', '09284567310', 'joannamdtorre@example.com', '1991-07-22', '92 Dahlia St., Brgy. Baesa, Quezon City', '{\"elementary\":{\"school\":\"Baesa Elementary School\",\"year\":\"1998\\u20132004\"},\"highschool\":{\"school\":\"Quezon City High Schoo\",\"year\":\"2004\\u20132008\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CleanPro Manila\",\"years\":\"2018\\u20132023\",\"role\":\"All\\u2011Around Helper\",\"location\":\"Quezon City\"}]', '[\"Quezon City\",\"Manila\",\"San Juan\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 5, 'applicants/698e9578069e6_1770952056.jpg', 'video/698e95781c1dc_1770952056.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:07:36', '2026-03-02 01:46:55', NULL),
(25, 1, 1, 'Ana Beatriz', 'Gomez', 'Reyes', '', '09156780234', '09156780234', 'ana.reyes25@example.com', '2001-01-09', '815 San Marcelino St., Brgy. Malate, Manila', '{\"elementary\":{\"school\":\"Malate Elementary School\",\"year\":\"2007\\u20132013\"},\"highschool\":{\"school\":\"Manila High School\",\"year\":\"2013\\u20132017\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Family Care Agency\",\"years\":\"2022\\u20132024\",\"role\":\"Babysitter\",\"location\":\"Ermita\"}]', '[\"Manila\",\"Pasay\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e96988e7b0_1770952344.jpg', 'video/698e96989948b_1770952344.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:12:24', '2026-03-02 01:46:55', NULL),
(26, 1, 1, 'Kristine Joy', 'Villanueva', 'Ramos', '', '09097865432', '09120457839', 'kjramos42@example.com', '1983-06-03', '54 Sampaguita St., Brgy. Cupang, Muntinlupa City', '{\"elementary\":{\"school\":\"Cupang Elementary School\",\"year\":\"1990\\u20131996\"},\"highschool\":{\"school\":\"Muntinlupa National High School\",\"year\":\"1996\\u20132000\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"SouthClean Services\",\"years\":\"2017\\u20132023\",\"role\":\"Housemaid\",\"location\":\"Muntinlupa\"},{\"company\":\"Evergreen Laundry\",\"years\":\"2014\\u20132017\",\"role\":\"Laundry Worker\",\"location\":\"Pasig\"}]', '[\"Muntinlupa\",\"Las Pi\\u00f1as\",\"Para\\u00f1aque\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 9, 'applicants/698e977bb17ee_1770952571.jpg', 'video/698e977bc23d6_1770952571.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:16:11', '2026-03-02 01:46:55', NULL),
(27, 1, 1, 'Shiela May', 'Basco', 'Cortez', '', '09189234577', '09361245780', 'shielamcortez30@example.com', '1995-11-16', '2385 Mabini St., Brgy. San Andres Bukid, Manila', '{\"elementary\":{\"school\":\"San Andres Elementary School\",\"year\":\"2002\\u20132008\"},\"highschool\":{\"school\":\"Arellano High School\",\"year\":\"2008\\u20132012\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"FreshStart Maid Agency\",\"years\":\"2020\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Makati\"}]', '[\"Makati\",\"Manila\",\"Taguig\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/698e984dec764_1770952781.jpg', 'video/698e984e007ce_1770952782.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:19:41', '2026-03-02 01:46:55', NULL),
(28, 1, 1, 'Rowena Liza', 'Cruz', 'Mariano', '', '09351240988', '09278450329', 'rowenamariano45@example.com', '1980-09-28', '702 Maliputo St., Brgy. Karuhatan, Valenzuela City', '{\"elementary\":{\"school\":\"Karuhatan Elementary School\",\"year\":\"1987\\u20131993\"},\"highschool\":{\"school\":\"Valenzuela National High School\",\"year\":\"1993\\u20131997\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"North Metro Helpers\",\"years\":\"2018\\u20132024\",\"role\":\"Cook\\/Housemaid\",\"location\":\"Valenzuela\"},{\"company\":\"CarePlus\",\"years\":\"2014\\u20132018\",\"role\":\"All\\u2011Around Helper\",\"location\":\"Valenzuela\"}]', '[\"Valenzuela\",\"Quezon City\",\"Caloocan\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 10, 'applicants/698e992bbb3a6_1770953003.jpg', 'video/698e992bc7543_1770953003.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:23:23', '2026-03-02 01:46:55', NULL),
(29, 2, 2, 'Charmaine Rose', 'Dimapilis', 'Jimenez', '', '09273659012', '09190345711', 'charmainejimenez22@example.com', '2004-02-04', '1789 Camarin Road, Brgy. 178, Camarin, Caloocan City', '{\"elementary\":{\"school\":\"Camarin Elementary School\",\"year\":\"2010\\u20132016\"},\"highschool\":{\"school\":\"Caloocan High School\",\"year\":\"2016\\u20132020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Clean &amp;amp;amp; Care Services\",\"years\":\"2023\\u20132024\",\"role\":\"Housemaid\",\"location\":\"Caloocan\"}]', '[\"Caloocan\",\"QC\",\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Cooking and Food Service\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 1, 'applicants/698e9a253267f_1770953253.jpg', 'video/698e9a253ea3a_1770953253.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:27:33', '2026-03-02 01:46:55', NULL),
(30, 2, 2, 'Lorna Fe', 'Bagtas', 'Malabanan', '', '09172349850', '09351867209', 'lornamalabanan39@example.com', '1986-04-10', '443 P. Burgos St., Brgy. Poblacion, Makati City', '{\"elementary\":{\"school\":\"Poblacion Elementary School\",\"year\":\"1992\\u20131998\"},\"highschool\":{\"school\":\"Makati High School\",\"year\":\"1998\\u20132002\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Makati HomeCare\",\"years\":\"2020\\u20132024\",\"role\":\"Housemaid\",\"location\":\"Bangkal Makati\"},{\"company\":\"Taguig Helpers Agency\",\"years\":\"2016\\u20132020\",\"role\":\"Cook\",\"location\":\"Makati\"}]', '[\"Makati\",\"Taguig\",\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9adbdc727_1770953435.jpg', 'video/698e9adbe929e_1770953435.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:30:35', '2026-03-02 01:46:55', NULL),
(31, 2, 2, 'Lea Catherine', 'Fernandez', 'Rivera', '', '09190456722', '09175346098', 'learivera27@example.com', '1998-12-02', '300 San Guillermo St., Brgy. Hulo, Mandaluyong City', '{\"elementary\":{\"school\":\"Hulo Elementary School\",\"year\":\"2004\\u20132010\"},\"highschool\":{\"school\":\"Mandaluyong High School\",\"year\":\"2010\\u20132014\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"MetroClean\",\"years\":\"2021\\u20132024\",\"role\":\"Housekeeper\",\"location\":\"Ortigas\"}]', '[\"Mandaluyong\",\"Pasig\",\"QC\"]', '[]', '[]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 3, 'applicants/698e9b841dc91_1770953604.jpg', 'video/698e9b8425cda_1770953604.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:33:24', '2026-03-02 01:46:55', NULL),
(32, 2, 2, 'Denise Grace', 'Angeles', 'Mendiola', '', '09956873410', '09359872140', 'denisemendiola33@example.com', '1992-08-19', '5124 A. Bonifacio St., Brgy. Western Bicutan, Taguig City', '{\"elementary\":{\"school\":\"Western Bicutan Elementary School\",\"year\":\"1999\\u20132005\"},\"highschool\":{\"school\":\"Taguig National High School\",\"year\":\"2005\\u20132009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Taguig Home Services\",\"years\":\"2019\\u20132024\",\"role\":\"Housemaid\\/Caregiver\",\"location\":\"BGC\"},{\"company\":\"UrbanClean Agency\",\"years\":\"2016\\u20132019\",\"role\":\"Cleaner\",\"location\":\"Pasay\"}]', '[\"Taguig\",\"Pasay\",\"Makati\"]', '[\"Filipino\",\"English\"]', '[]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 8, 'applicants/698e9c75149ea_1770953845.jpg', 'video/698e9c751c0ff_1770953845.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'pending', NULL, '2026-02-13 03:37:25', '2026-03-02 01:46:55', NULL),
(33, 1, 1, 'Ava', 'Marie', 'Thompson', '', '09999999999', '09999999999', 'email@gmail.com', '1998-02-19', '1234 address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Tertiary Graduate (Bachelor’s Degree)', 3, 'applicants/698e8d360baa7_1770949942.jpg', 'video/698e8d3610907_1770949942.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:32:22', '2026-03-02 01:46:55', NULL),
(34, 1, 1, 'Sophia', 'Claire', 'Ramirez', '', '09999999999', '09999999999', 'email@gmail.com', '1990-11-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Mandaluyong\",\"makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e8df92b357_1770950137.jpg', 'video/698e8df92cdd7_1770950137.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:35:37', '2026-03-02 01:46:55', NULL),
(35, 1, 1, 'Isabella', 'Grace', 'Mitchell', '', '09999999999', '09999999999', 'email@gmail.com', '2000-08-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"IT\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"Mandaluyong\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Technical-Vocational / TESDA Graduate', 2, 'applicants/698e8e832247e_1770950275.jpg', 'video/698e8e83233c5_1770950275.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:37:55', '2026-03-02 01:46:55', NULL),
(36, 1, 1, 'Emily', 'Rose', 'Johnson', '', '09999999999', '09999999999', 'email@gmail.com', '1960-02-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\",\"paranaque\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning &amp;amp; Housekeeping (General)\",\"Cooking &amp;amp; Food Service\",\"Pet &amp;amp; Outdoor Maintenance\"]', 'Full Time', NULL, 'Senior High School Graduate (K-12 Curriculum)', 2, 'applicants/698e8f3716c79_1770950455.jpg', 'video/698e8f231b3bd_1770950435.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:40:35', '2026-03-02 01:46:55', NULL),
(37, 1, 1, 'Mia', 'Elizabeth', 'Carter', '', '09999999999', '09999999999', 'email@gmail.com', '2001-11-02', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e8fa38a07a_1770950563.jpg', 'video/698e8fa38af70_1770950563.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:42:43', '2026-03-02 01:46:55', NULL),
(38, 1, 1, 'Olivia', 'Jane', 'Peterson', '', '09999999999', '09999999999', 'email@gmail.com', '1990-03-06', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\"]', '[\"Cleaning and Housekeeping (General)\",\"Laundry and Clothing Care\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 2, 'applicants/698e901853864_1770950680.jpg', 'video/698e9018547b8_1770950680.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:44:40', '2026-03-02 01:46:55', NULL),
(39, 1, 1, 'Chloe', 'Ann', 'Sullivan', '', '09999999999', '09999999999', 'email@gmail.com', '1989-01-15', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Sta. Ana Manila\"}]', '[\"Taguig\",\"BGC\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Secondary Level (Attended High School)', 2, 'applicants/698e90a11d029_1770950817.jpg', 'video/698e90a11f3d4_1770950817.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:46:57', '2026-03-02 01:46:55', NULL),
(40, 1, 1, 'Hannah', 'Louise', 'Parker', '', '09999999999', '09999999999', 'email@gmail.com', '1999-08-12', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"Kasambahay\",\"location\":\"Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Childcare and Maternity (Yaya)\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Elementary Graduate', 3, 'applicants/698e910d1e60e_1770950925.jpg', 'video/698e910d1fc78_1770950925.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:48:45', '2026-03-02 01:46:55', NULL),
(41, 1, 1, 'Abigail', 'Nicole', 'Sanders', '', '09999999999', '09999999999', 'email@gmail.com', '2000-11-08', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"\",\"year\":\"2019\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kasambahay\",\"location\":\"Ermita Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\"]', 'Full Time', NULL, 'Tertiary Graduate (Bachelor’s Degree)', 2, 'applicants/698e918576116_1770951045.jpg', 'video/698e918577686_1770951045.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:50:45', '2026-03-02 01:46:55', NULL),
(42, 1, 1, 'Natalie', 'Faith', 'Rogers', '', '09999999999', '09999999999', 'email@gmail.com', '1999-01-23', '123 Address', '{\"elementary\":{\"school\":\"Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"High School\",\"year\":\"2009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"STEM\",\"year\":\"2010\"},\"college\":{\"school\":\"College school\",\"course\":\"BSIT\",\"year\":\"2026\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2011 - 2014\",\"role\":\"IT Programmer\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\",\"Makati\"]', '[\"English\",\"Filipino\"]', '[\"Cooking and Food Service\",\"Childcare and Maternity (Yaya)\",\"Pet and Outdoor Maintenance\"]', 'Full Time', NULL, 'Tertiary Level (College Undergraduate)', 3, 'applicants/698e9220edd8f_1770951200.jpg', 'video/698e9220ee585_1770951200.mp4', 'file', 'file', '', NULL, NULL, 'pending', 5, '2026-02-13 02:53:20', '2026-03-02 01:46:55', NULL),
(43, 1, 1, 'Ryzza Mae', 'B.', 'Diaz', '', '09123123718', '09817238712', 'renzdiaz.contact@gmail.com', '2026-02-25', '87412 ajllmdawudawdawdasdawds', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"BrightClean Services\",\"years\":\"2026 - 2028\",\"role\":\"Housemaid\",\"location\":\"Ermita Manila\"},{\"company\":\"The Grill Makati\",\"years\":\"2026 - 2028\",\"role\":\"Service Crew\",\"location\":\"Makati\"}]', '[\"Makati City\",\"Mandaluyong CIty\"]', '[]', '[\"Cleaning & Housekeeping (General)\",\"Childcare & Maternity (Yaya)\",\"Elderly & Special Care (Caregiver)\"]', 'Full Time', 150.00, 'Secondary Graduate (Junior High School / Old Curriculum)', 4, 'applicants/6996b581e440f_1771484545.jpg', 'video/6996b581ecad4_1771484545.mp4', 'file', 'file', 'My Introduction', NULL, NULL, 'approved', 12, '2026-02-19 07:02:25', '2026-03-02 01:46:55', NULL),
(46, 3, 3, 'Mhi Mha', '', 'Diaz', '', '09128319264', '09128361628', '', '2001-02-12', '1234 wertyuiohjk', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2005\\u20132009\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"CREMPCO\",\"years\":\"2026 - 2028\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\"]', '[\"Filipino\",\"aeamic\"]', '[]', 'Full Time', NULL, 'Secondary Level (Attended High School)', 2, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 5, '2026-02-26 05:30:39', '2026-03-02 01:46:55', NULL),
(47, 2, 2, 'Mhi Mha', '', 'Diaz', '', '09283718231', '09359872140', '', '2000-12-12', '123 ertyujf', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"1992\\u20131998\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2020\\u20132024\",\"role\":\"Kumekendeng\",\"location\":\"Ermita Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Secondary Graduate (Junior High School / Old Curriculum)', 4, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 5, '2026-02-26 05:35:45', '2026-03-02 01:46:55', NULL),
(48, 1, 1, 'Johnny', 'lawin', 'Ocampo', '', '09283718231', '09128361628', 'email@gmail.com', '2003-11-11', '1234  mia[fjdvfkas', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Makati High School\",\"year\":\"2005\\u20132009\"},\"senior_high\":{\"school\":\"Senior High School\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\"]', 'Full Time', NULL, 'Senior High School Graduate (K-12 Curriculum)', 3, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 5, '2026-03-02 00:56:46', '2026-03-02 01:46:55', NULL),
(49, 2, 2, 'Trial', '', 'Trial', '', '09999999999', '09999999999', '', '2001-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[]', '[]', '[]', 'Full Time', NULL, 'Elementary Graduate', 3, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 5, '2026-03-02 00:57:46', '2026-03-02 01:46:55', NULL),
(50, 2, 2, 'Trial', '', 'Trial', '', '09999999999', '', '', '2003-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[]', '[]', '[]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\"]', 'Full Time', NULL, 'Secondary Level (Attended High School)', 0, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 17, '2026-03-02 01:07:53', '2026-03-02 01:46:55', NULL),
(51, 3, 3, 'Trial', '', 'Trial', '', '09999999999', '09999999992', '', '2000-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"Mendioland Elementary School\",\"year\":\"2010 - 2016\"},\"highschool\":{\"school\":\"Dr. Juan G. Nolasco High School\",\"year\":\"2016 - 2020\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2026 - 2028\",\"role\":\"Housemaid\",\"location\":\"Ermita Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Senior High School Graduate (K-12 Curriculum)', 2, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 17, '2026-03-02 01:09:52', '2026-03-02 01:46:55', NULL),
(52, 4, NULL, 'china', '', 'Trial', '', '09999999999', '09999999999', '', '2000-12-12', '123131 snytgrfdehjghgfd', '{\"elementary\":{\"school\":\"Emillio Elementary School\",\"year\":\"2001\"},\"highschool\":{\"school\":\"\",\"year\":\"\"},\"senior_high\":{\"school\":\"\",\"strand\":\"\",\"year\":\"\"},\"college\":{\"school\":\"\",\"course\":\"\",\"year\":\"\"}}', '[{\"company\":\"Luxurias Bar\",\"years\":\"2011 - 2014\",\"role\":\"Kumekendeng\",\"location\":\"Sta. Ana Manila\"}]', '[\"Manila\"]', '[\"Filipino\"]', '[\"Cleaning and Housekeeping (General)\",\"Cooking and Food Service\",\"Elderly and Special Care (Caregiver)\"]', 'Full Time', NULL, 'Elementary Graduate', 3, NULL, NULL, NULL, 'iframe', NULL, NULL, NULL, 'pending', 17, '2026-03-02 02:43:10', '2026-03-02 02:43:10', NULL);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `original_applicant_id` int(10) UNSIGNED NOT NULL,
  `replacement_applicant_id` int(10) UNSIGNED DEFAULT NULL,
  `client_booking_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` enum('AWOL','Client Left','Not Finished Contract','Performance Issue','Other') NOT NULL,
  `report_text` text NOT NULL,
  `attachments_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `status` enum('selection','assigned','cancelled') NOT NULL DEFAULT 'selection',
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `assigned_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_replacements`
--

INSERT INTO `applicant_replacements` (`id`, `business_unit_id`, `original_applicant_id`, `replacement_applicant_id`, `client_booking_id`, `reason`, `report_text`, `attachments_json`, `status`, `created_by`, `created_at`, `updated_at`, `assigned_at`) VALUES
(22, 1, 43, 27, NULL, 'Other', 'Health Problem oh their lungs at sakit sa bulsa', '[\"replacements/699bef28049d1_1771826984.png\"]', 'assigned', 12, '2026-02-23 06:09:44', '2026-02-24 01:31:53', '2026-02-23 14:09:54'),
(23, 1, 23, 37, NULL, 'Other', 'AWOL', '[]', 'assigned', 12, '2026-02-23 06:21:04', '2026-02-24 01:31:53', '2026-02-23 14:21:07'),
(24, 1, 43, 28, NULL, 'Other', 'awdaw', '[]', 'assigned', 12, '2026-02-23 13:41:36', '2026-02-24 01:31:53', '2026-02-23 21:41:38');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_reports`
--

CREATE TABLE `applicant_reports` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `note_text` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_reports`
--

INSERT INTO `applicant_reports` (`id`, `applicant_id`, `business_unit_id`, `admin_id`, `note_text`, `created_at`) VALUES
(31, 23, 1, 12, 'Revert to Pending - Reason: Documents Complete. Description: solved', '2026-02-23 21:27:27'),
(32, 23, 1, 12, 'asdasd', '2026-02-23 21:29:26'),
(33, 23, 1, 12, 'czxcz', '2026-02-23 21:41:06'),
(34, 43, 1, 12, 'Replacement Initiated (Reason: Other)\nawdaw', '2026-02-23 21:41:36'),
(35, 43, 1, 12, 'Revert to Pending - Reason: Documents Complete. Description: awd', '2026-02-23 21:42:07');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applicant_status_reports`
--

INSERT INTO `applicant_status_reports` (`id`, `applicant_id`, `business_unit_id`, `from_status`, `to_status`, `report_text`, `admin_id`, `created_at`) VALUES
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
(46, 43, 1, 'on_process', 'pending', 'Client confirmed / Ready: awdas', 12, '2026-02-23 22:50:02');

-- --------------------------------------------------------

--
-- Table structure for table `blacklisted_applicants`
--

CREATE TABLE `blacklisted_applicants` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `issue` text DEFAULT NULL,
  `proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_by` int(10) UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reverted_at` datetime DEFAULT NULL,
  `reverted_by` int(10) UNSIGNED DEFAULT NULL,
  `compliance_note` text DEFAULT NULL,
  `compliance_proof_paths` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blacklisted_applicants`
--

INSERT INTO `blacklisted_applicants` (`id`, `applicant_id`, `reason`, `issue`, `proof_paths`, `created_by`, `is_active`, `created_at`, `reverted_at`, `reverted_by`, `compliance_note`, `compliance_proof_paths`, `updated_at`) VALUES
(5, 43, 'sad', 'asdasd', NULL, 12, 0, '2026-02-23 13:28:25', '2026-02-23 21:32:00', 12, 'sawdas', NULL, '2026-02-23 13:32:00');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_units`
--

INSERT INTO `business_units` (`id`, `agency_id`, `country_id`, `code`, `name`, `active`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'CSNK-PH', 'CSNK Philippines', 1, '2026-02-24 01:19:27', '2026-02-24 01:19:27'),
(2, 2, 2, 'SMC-TR', 'SMC Turkey', 1, '2026-02-24 01:19:27', '2026-02-24 01:19:27'),
(3, 2, 3, 'SMC-BH', 'SMC Bahrain', 1, '2026-02-25 12:47:51', '2026-02-25 12:47:51'),
(4, 2, 5, 'SMC-CHI', 'SMC China', 1, '2026-03-02 02:41:37', '2026-03-02 02:41:37');

-- --------------------------------------------------------

--
-- Table structure for table `client_bookings`
--

CREATE TABLE `client_bookings` (
  `id` int(10) UNSIGNED NOT NULL,
  `applicant_id` int(10) UNSIGNED NOT NULL,
  `business_unit_id` int(10) UNSIGNED NOT NULL,
  `services_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `countries`
--

INSERT INTO `countries` (`id`, `iso2`, `iso3`, `name`, `default_tz`, `phone_country_code`, `currency_code`, `locale`, `date_format`, `active`) VALUES
(1, 'PH', 'PHL', 'Philippines', 'Asia/Manila', '+63', 'PHP', 'en-PH', 'MM/DD/YYYY', 1),
(2, 'TR', 'TUR', 'Turkey', 'Europe/Istanbul', '+90', 'TRY', 'tr-TR', 'DD.MM.YYYY', 1),
(3, 'BH', 'BHR', 'Bahrain', 'Asia/Bahrain', '+973', 'BHD', 'en-BH', 'DD/MM/YYYY', 1),
(4, 'JP', 'JPN', 'Japan', 'Asia/Japan', '+81', 'YEN', 'en_JP', '', 1),
(5, 'CN', 'CHN', 'China', 'Beijing Time (CST)', '+852', 'CNY', 'zh-CN', 'Y-m-d', 1);

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
(42, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 10:41:56', NULL),
(43, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:20:42', '2026-02-13 09:24:05'),
(44, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:24:17', '2026-02-13 09:53:18'),
(45, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 09:53:24', '2026-02-13 13:24:00'),
(46, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-13 13:24:10', NULL),
(47, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 13:11:56', NULL),
(48, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-18 16:48:20', NULL),
(49, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 09:36:36', '2026-02-19 09:39:53'),
(50, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 09:40:05', '2026-02-19 09:55:50'),
(51, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 10:55:23', '2026-02-19 10:57:48'),
(52, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 10:58:01', '2026-02-19 11:56:26'),
(53, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 11:56:45', '2026-02-19 13:10:05'),
(54, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:10:21', '2026-02-19 13:10:45'),
(55, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:11:02', '2026-02-19 13:14:49'),
(56, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 13:14:55', '2026-02-19 13:46:11'),
(57, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 14:37:06', '2026-02-19 15:06:51'),
(58, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 15:07:00', '2026-02-19 15:07:45'),
(59, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-19 15:07:52', NULL),
(60, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-20 10:33:14', NULL),
(61, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 08:38:39', '2026-02-23 11:02:29'),
(62, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 11:02:40', '2026-02-23 11:04:14'),
(63, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 11:04:19', NULL),
(64, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-23 20:51:17', NULL),
(65, 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 10:55:20', '2026-02-24 10:56:44'),
(66, 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 10:57:03', '2026-02-24 11:18:19'),
(67, 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 11:18:30', '2026-02-24 11:26:41'),
(68, 5, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-24 11:26:49', NULL),
(69, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 08:44:39', '2026-02-25 09:10:02'),
(70, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 09:10:09', '2026-02-25 09:14:22'),
(71, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 09:14:28', '2026-02-25 09:18:31'),
(72, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 09:18:37', '2026-02-25 09:34:59'),
(73, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 09:35:05', '2026-02-25 09:43:50'),
(74, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 09:43:58', '2026-02-25 09:44:07'),
(75, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 09:44:13', '2026-02-25 11:27:13'),
(76, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:27:19', '2026-02-25 11:27:37'),
(77, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:27:43', '2026-02-25 11:36:39'),
(78, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:36:45', '2026-02-25 11:36:55'),
(79, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:37:02', '2026-02-25 11:56:16'),
(80, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:56:42', '2026-02-25 11:58:01'),
(81, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 11:58:09', '2026-02-25 13:39:32'),
(82, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 13:39:48', '2026-02-25 13:49:39'),
(83, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 13:49:45', '2026-02-25 13:50:08'),
(84, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 13:50:13', '2026-02-25 14:23:27'),
(85, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 14:23:40', '2026-02-25 14:25:50'),
(86, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 14:25:58', '2026-02-25 14:31:10'),
(87, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 14:31:19', '2026-02-25 14:31:26'),
(88, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 14:31:37', '2026-02-25 14:38:52'),
(89, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 14:39:16', '2026-02-25 14:39:25'),
(90, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 14:39:34', NULL),
(91, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:26:05', '2026-02-25 20:33:18'),
(92, 18, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:33:24', '2026-02-25 20:33:43'),
(93, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-25 20:33:51', NULL),
(94, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 08:44:32', '2026-02-26 08:48:08'),
(95, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 08:48:11', '2026-02-26 08:48:47'),
(96, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 08:49:15', '2026-02-26 12:00:48'),
(97, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 13:07:04', '2026-02-26 13:32:55'),
(98, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 13:33:00', '2026-02-26 13:34:42'),
(99, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 13:34:46', '2026-02-26 13:36:29'),
(100, 12, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 13:36:48', '2026-02-26 13:37:41'),
(101, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 13:37:45', '2026-02-26 13:37:52'),
(102, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 13:37:55', '2026-02-26 14:07:32'),
(103, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 14:07:43', '2026-02-26 14:09:12'),
(104, 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 14:09:17', '2026-02-26 14:09:24'),
(105, 19, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 14:09:29', '2026-02-26 14:11:03'),
(106, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 14:11:09', '2026-02-26 14:54:08'),
(107, 17, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 14:54:12', '2026-02-26 16:11:23'),
(108, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-02-26 16:11:27', NULL),
(109, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '2026-03-01 13:07:52', NULL),
(110, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 08:44:07', '2026-03-02 08:55:03'),
(111, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 08:55:08', '2026-03-02 09:01:00'),
(112, 17, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '2026-03-02 09:01:04', NULL);

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
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=535;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `agencies`
--
ALTER TABLE `agencies`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `applicant_documents`
--
ALTER TABLE `applicant_documents`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=249;

--
-- AUTO_INCREMENT for table `applicant_replacements`
--
ALTER TABLE `applicant_replacements`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `applicant_reports`
--
ALTER TABLE `applicant_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `applicant_status_reports`
--
ALTER TABLE `applicant_status_reports`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `blacklisted_applicants`
--
ALTER TABLE `blacklisted_applicants`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `business_units`
--
ALTER TABLE `business_units`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `client_bookings`
--
ALTER TABLE `client_bookings`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `countries`
--
ALTER TABLE `countries`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `document_types`
--
ALTER TABLE `document_types`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `session_logs`
--
ALTER TABLE `session_logs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

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
