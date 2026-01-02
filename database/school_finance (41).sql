-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 02, 2026 at 01:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_finance`
--

-- --------------------------------------------------------

--
-- Table structure for table `accounts`
--

CREATE TABLE `accounts` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(100) NOT NULL,
  `account_type` enum('asset','liability','equity','revenue','expense') NOT NULL,
  `description` text DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `accounts`
--

INSERT INTO `accounts` (`id`, `school_id`, `account_code`, `account_name`, `account_type`, `description`, `balance`, `created_at`, `updated_at`) VALUES
(1, 1, '101', 'Owner\'s Equity', 'equity', NULL, 178500.00, '2025-04-28 19:15:13', '2025-07-01 10:58:59'),
(2, 1, '102', 'Cooperative Bank', 'revenue', NULL, 10000.00, '2025-04-28 19:24:19', '2025-07-01 10:58:59'),
(3, 1, '103', 'Petty Cash', '', NULL, 35330.00, '2025-04-28 19:26:45', '2025-07-01 10:58:59'),
(7, 1, '104', 'Equity', 'revenue', NULL, 0.00, '2025-05-27 17:40:32', '2025-07-01 10:58:59'),
(8, 1, '201', 'Salary Expense', '', NULL, 0.00, '2025-05-27 17:56:54', '2025-07-01 10:58:59'),
(9, 1, '202', 'Liability Account', '', NULL, 0.00, '2025-05-27 17:57:24', '2025-07-01 10:58:59'),
(10, 1, '203', 'Utilities', '', NULL, 0.00, '2025-06-06 17:51:50', '2025-07-01 10:58:59'),
(11, 1, '204', 'Rent', '', NULL, 0.00, '2025-06-06 17:52:08', '2025-07-01 10:58:59'),
(14, 2, '1002', 'Equity Bank', 'asset', NULL, 5000.00, '2025-07-01 23:59:26', '2025-07-09 07:43:57'),
(15, 3, '110', 'M-Pesa Paybill', 'asset', NULL, 25000.00, '2025-07-09 08:28:54', '2025-07-09 09:45:01'),
(16, 3, '220', 'Petty Cash', 'expense', NULL, 0.00, '2025-07-09 09:00:55', '2025-07-09 09:00:55'),
(17, 3, '010', 'Cooperative Bank', 'asset', NULL, 49900.00, '2025-07-09 09:28:11', '2025-07-09 09:45:01'),
(19, 3, '19003', 'Undeposited Funds', 'asset', NULL, 10000.00, '2025-07-09 11:52:19', '2025-07-09 11:52:19'),
(20, 4, '19004', 'Undeposited Funds', 'asset', NULL, 60000.00, '2025-07-09 13:40:37', '2025-07-10 12:18:56'),
(21, 4, '1900', 'Cooperative Bank', 'asset', NULL, 27500.00, '2025-07-09 15:16:16', '2025-07-09 15:16:16'),
(22, 5, '1900-5', 'Undeposited Funds', 'asset', NULL, 0.00, '2025-07-15 13:58:39', '2025-07-15 20:37:00'),
(23, 5, '1800-1', 'Cooperative Bank', 'asset', NULL, -75760.00, '2025-07-15 16:43:49', '2025-07-16 21:36:09'),
(24, 5, '1800-2', 'Equity Bank', 'asset', NULL, 0.00, '2025-07-15 16:44:10', '2025-07-15 16:44:10'),
(25, 5, '1700-3', 'Petty Cash', 'expense', NULL, 89060.00, '2025-07-15 20:03:20', '2025-07-16 21:36:09'),
(26, 5, '6010', 'Salaries & Wages', 'expense', NULL, 46950.00, '2025-07-16 20:33:25', '2025-07-16 21:36:09'),
(27, 6, '1900-6', 'Undeposited Funds', 'asset', NULL, -35233.00, '2025-07-16 22:12:10', '2025-09-03 21:22:39'),
(28, 6, '1800-3', 'Cooperative Bank', 'asset', NULL, 382619.33, '2025-07-16 22:14:19', '2025-08-26 18:57:57'),
(29, 6, '1900-4', 'Petty Cash', 'asset', NULL, 86937.00, '2025-07-16 22:16:35', '2025-08-03 09:32:06'),
(30, 6, '1700-2', 'Food & Beverages', 'expense', NULL, 98207.00, '2025-07-16 22:24:35', '2025-07-25 17:58:29'),
(36, 6, '1700-5', 'Salaries & Wages', 'expense', NULL, 192360.00, '2025-07-16 22:53:03', '2025-08-05 07:37:53'),
(37, 6, '1700-6', 'Miscellaneous', 'expense', NULL, 13070.00, '2025-07-16 23:39:43', '2025-07-19 23:01:01'),
(38, 6, '1700-7', 'Vehicle', 'expense', NULL, 23021.00, '2025-07-19 22:59:43', '2025-07-25 18:04:47'),
(39, 6, '4000', 'Tuition Revenue', 'revenue', NULL, 463300.00, '2025-07-20 00:51:56', '2025-08-24 12:03:32'),
(40, 6, '2100', 'PAYE Payable', 'liability', NULL, 3909.33, '2025-08-05 07:37:53', '2025-08-05 07:37:53'),
(41, 6, '2110', 'NHIF Payable', 'liability', NULL, 1000.00, '2025-08-05 07:37:53', '2025-08-05 07:37:53'),
(42, 6, '2120', 'NSSF Payable', 'liability', NULL, 1080.00, '2025-08-05 07:37:53', '2025-08-05 07:37:53'),
(43, 6, '2130', 'Housing Levy Payable', 'liability', NULL, 600.00, '2025-08-05 07:37:53', '2025-08-05 07:37:53'),
(44, 7, '1001', 'Cooperative Bank', 'asset', NULL, 47500.00, '2025-08-12 08:20:51', '2025-08-12 08:51:41'),
(47, 7, '4000-7-R', 'Tuition Revenue', 'revenue', NULL, 25000.00, '2025-08-12 08:32:58', '2025-08-12 08:32:58'),
(48, 7, '1200', 'Accounts Receivable', 'asset', NULL, -22500.00, '2025-08-12 08:51:41', '2025-08-12 08:51:41'),
(50, 8, '101', 'Co-op Bank', 'asset', NULL, 25000.00, '2025-08-12 09:03:57', '2025-08-12 09:04:21'),
(51, 8, '1200-8-A', 'Accounts Receivable', 'asset', NULL, -25000.00, '2025-08-12 09:04:21', '2025-08-12 09:04:21'),
(52, 9, '1900-9', 'Undeposited Funds', 'asset', NULL, 85500.00, '2025-08-12 11:26:38', '2025-08-18 19:20:58'),
(53, 9, '1200-9-A', 'Accounts Receivable', 'asset', NULL, 1277000.00, '2025-08-12 11:26:38', '2025-08-28 11:47:52'),
(54, 9, '4000-9-R', 'Tuition Revenue', 'revenue', NULL, 1362500.00, '2025-08-13 15:18:48', '2025-08-28 11:47:52'),
(55, 6, '1200-6-A', 'Accounts Receivable', 'asset', NULL, -78035.00, '2025-08-24 12:03:32', '2025-09-03 21:22:39'),
(56, 11, '1200-11-A', 'Accounts Receivable', 'asset', NULL, 990800.00, '2025-08-28 17:02:20', '2025-09-24 04:06:10'),
(57, 11, '4000-11-R', 'Tuition Revenue', 'revenue', NULL, 1122650.00, '2025-08-28 17:02:20', '2025-09-24 04:06:10'),
(58, 11, '1900-11', 'Undeposited Funds', 'asset', NULL, 0.00, '2025-08-30 07:07:51', '2025-09-07 12:03:52'),
(59, 11, '5678', 'Petty Cash', 'asset', NULL, 0.00, '2025-08-30 18:12:48', '2025-08-30 18:15:13'),
(60, 11, '3456', 'Co-op Bank', 'asset', NULL, -43578.00, '2025-08-30 18:13:21', '2025-09-09 14:19:14'),
(61, 11, '23456', 'Car expense account', 'expense', NULL, 7768.00, '2025-09-03 15:28:16', '2025-09-03 15:28:39'),
(62, 11, '6010-11-E', 'Salaries & Wages', 'expense', NULL, 189000.00, '2025-09-04 10:31:22', '2025-09-09 14:19:14'),
(63, 11, '2100-11-L', 'PAYE Payable', 'liability', NULL, 102.50, '2025-09-04 10:31:22', '2025-09-04 10:31:22'),
(64, 11, '2110-11-L', 'NHIF Payable', 'liability', NULL, 4800.00, '2025-09-04 10:31:22', '2025-09-09 14:19:14'),
(65, 11, '2120-11-L', 'NSSF Payable', 'liability', NULL, 8640.00, '2025-09-04 10:31:22', '2025-09-09 14:19:14'),
(66, 11, '2130-11-L', 'Housing Levy Payable', 'liability', NULL, 2797.50, '2025-09-04 10:31:22', '2025-09-09 14:19:14');

-- --------------------------------------------------------

--
-- Table structure for table `activities`
--

CREATE TABLE `activities` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `activity_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fee_per_term` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(50) DEFAULT NULL COMMENT 'e.g., Sports, Arts, Music, Languages',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activities`
--

INSERT INTO `activities` (`id`, `school_id`, `activity_name`, `description`, `fee_per_term`, `category`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Skating', 'Ice skating lessons', 5000.00, 'Sports', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(2, 1, 'Ballet', 'Classical ballet classes', 5000.00, 'Arts', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(3, 1, 'Taekwondo', 'Martial arts training', 5000.00, 'Sports', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(4, 1, 'Chess', 'Strategic chess lessons', 5000.00, 'Games', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(5, 1, 'Music (Piano, Guitar, Drums or Violin)', 'Musical instrument training', 5000.00, 'Music', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(6, 1, 'Languages (Chinese or French)', 'Foreign language classes', 5000.00, 'Languages', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(7, 1, 'Basketball', 'Basketball training', 5000.00, 'Sports', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(8, 1, 'Gymnastics', 'Gymnastics and flexibility training', 5000.00, 'Sports', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(9, 1, 'Swimming', 'Swimming lessons', 5000.00, 'Sports', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(10, 1, 'Sports (Basketball, badminton, Dance and Fitness)', 'General sports and fitness', 5000.00, 'Sports', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(11, 1, 'Abacus (Optional Grade 1 - 3)', 'Mental math with abacus', 2000.00, 'Academic', 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(12, 7, 'Taekwondo', '', 5000.00, 'Sports', 'active', '2026-01-02 00:01:44', '2026-01-02 00:01:44');

-- --------------------------------------------------------

--
-- Table structure for table `annual_fees_billed`
--

CREATE TABLE `annual_fees_billed` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `billed_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `id` bigint(20) NOT NULL,
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','SYSTEM') NOT NULL,
  `target_table` varchar(100) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `audit_log`
--

INSERT INTO `audit_log` (`id`, `school_id`, `user_id`, `user_name`, `ip_address`, `action_type`, `target_table`, `target_id`, `details`, `created_at`) VALUES
(1, 4, 3, 'Macharia Mungai', '::1', 'UPDATE', 'items', 115, '{\"before\":{\"id\":\"115\",\"school_id\":\"4\",\"name\":\"GRADE 1\",\"price\":\"5000.00\",\"description\":\"Fee for SPORTS\\/SWIMMING \\/KINDER MUSIC (GRADE 1)\",\"parent_id\":\"102\",\"item_type\":\"child\",\"created_at\":\"2025-07-09 17:07:21\"},\"after\":{\"id\":\"115\",\"name\":\"GRADE 1\",\"price\":\"6000\",\"description\":\"Fee for SPORTS\\/SWIMMING \\/KINDER MUSIC (GRADE 1)\",\"parent_id\":\"102\",\"item_type\":\"child\",\"school_id\":\"4\"}}', '2025-07-10 05:46:13'),
(2, 4, 3, 'Macharia Mungai', '::1', 'UPDATE', 'items', 115, '{\"before\":{\"id\":\"115\",\"school_id\":\"4\",\"name\":\"GRADE 1\",\"price\":\"6000.00\",\"description\":\"Fee for SPORTS\\/SWIMMING \\/KINDER MUSIC (GRADE 1)\",\"parent_id\":\"102\",\"item_type\":\"child\",\"created_at\":\"2025-07-09 17:07:21\"},\"after\":{\"id\":\"115\",\"name\":\"GRADE 1\",\"price\":\"5000\",\"description\":\"Fee for SPORTS\\/SWIMMING \\/KINDER MUSIC (GRADE 1)\",\"parent_id\":\"102\",\"item_type\":\"child\",\"school_id\":\"4\"}}', '2025-07-10 05:46:27'),
(3, 4, 3, 'Macharia Mungai', '::1', 'DELETE', 'items', 101, '{\"data\":{\"id\":\"101\",\"school_id\":\"4\",\"name\":\"LUNCH & BREAK\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-09 16:52:01\"}}', '2025-07-10 12:18:24'),
(4, 4, 3, 'Macharia Mungai', '::1', 'DELETE', 'items', 102, '{\"data\":{\"id\":\"102\",\"school_id\":\"4\",\"name\":\"SPORTS\\/SWIMMING \\/KINDER MUSIC\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-09 16:52:01\"}}', '2025-07-10 12:18:28'),
(5, 4, 3, 'Macharia Mungai', '::1', 'DELETE', 'items', 100, '{\"data\":{\"id\":\"100\",\"school_id\":\"4\",\"name\":\"TUITION\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-09 16:52:01\"}}', '2025-07-10 12:18:32'),
(6, 4, 3, 'Macharia Mungai', '::1', 'CREATE', 'payment_receipts', 23, '{\"data\":{\"receipt_number\":\"REC-686FAFB0C59EB\",\"student_id\":39,\"amount\":20000,\"method\":\"Mobile Money\"}}', '2025-07-10 12:18:56'),
(7, 4, 3, 'Macharia Mungai', '::1', 'CREATE', 'students', 40, '{\"data\":{\"student_id_no\":\"BM1250\",\"name\":\"William Macharia Mungai\",\"class_id\":17}}', '2025-07-11 14:37:38'),
(8, 4, 3, 'Macharia Mungai', '::1', 'DELETE', 'invoice_templates', 5, '{\"data\":{\"id\":\"5\",\"school_id\":\"4\",\"name\":\"Grade 1 Template\",\"items\":\"[{\\\"item_id\\\":\\\"110\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"10000.00\\\"},{\\\"item_id\\\":\\\"115\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5000.00\\\"},{\\\"item_id\\\":\\\"105\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"38500.00\\\"}]\",\"created_at\":\"2025-07-09 18:14:31\"}}', '2025-07-11 14:51:22'),
(9, 4, 3, 'Macharia Mungai', '::1', 'CREATE', 'invoices', 34, '{\"data\":{\"id\":\"34\",\"school_id\":\"4\",\"student_id\":40,\"invoice_date\":\"2025-07-11\",\"due_date\":\"2025-08-10\",\"items\":[{\"item_id\":\"121\",\"description\":\"\",\"quantity\":1,\"unit_price\":38500},{\"item_id\":\"127\",\"description\":\"\",\"quantity\":1,\"unit_price\":10000},{\"item_id\":\"133\",\"description\":\"\",\"quantity\":1,\"unit_price\":5000}],\"notes\":\"\"}}', '2025-07-11 14:57:30'),
(10, 4, 3, 'Macharia Mungai', '::1', 'CREATE', 'students', 41, '{\"data\":{\"student_id_no\":\"BM1251\",\"name\":\"John Gathua Mungai\",\"class_id\":19}}', '2025-07-11 15:06:21'),
(11, 4, 3, 'Macharia Mungai', '::1', 'CREATE', 'invoices', 35, '{\"data\":{\"id\":\"35\",\"school_id\":\"4\",\"student_id\":40,\"invoice_date\":\"2025-07-11\",\"due_date\":\"2025-08-10\",\"items\":[{\"item_id\":\"123\",\"description\":\"\",\"quantity\":1,\"unit_price\":38500},{\"item_id\":\"129\",\"description\":\"\",\"quantity\":1,\"unit_price\":10000},{\"item_id\":\"135\",\"description\":\"\",\"quantity\":1,\"unit_price\":5000}],\"notes\":\"\"}}', '2025-07-11 15:07:06'),
(12, 4, 3, 'Macharia Mungai', '::1', 'DELETE', 'classes', 14, '{\"data\":{\"id\":\"14\",\"school_id\":\"4\",\"name\":\"Beginner\",\"description\":null,\"created_at\":\"2025-07-10 15:25:04\"}}', '2025-07-11 20:57:26'),
(13, 4, 3, 'Macharia Mungai', '::1', 'CREATE', 'classes', 20, '{\"data\":{\"name\":\"Playgroup\"}}', '2025-07-11 20:57:37'),
(15, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 22, '{\"data\":{\"name\":\"ava.mwangi@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(16, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 23, '{\"data\":{\"name\":\"liam.otieno@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(17, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 24, '{\"data\":{\"name\":\"zuri.njeri@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(18, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 25, '{\"data\":{\"name\":\"ethan.kiptoo@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(19, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 26, '{\"data\":{\"name\":\"amara.wanjiru@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(20, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 27, '{\"data\":{\"name\":\"jayden.mutiso@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(21, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 28, '{\"data\":{\"name\":\"maya.chebet@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(22, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 29, '{\"data\":{\"name\":\"ryan.odhiambo@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(23, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 30, '{\"data\":{\"name\":\"nia.muthoni@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(24, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 31, '{\"data\":{\"name\":\"elijah.ouma@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(25, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 32, '{\"data\":{\"name\":\"layla.kamau@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(26, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 33, '{\"data\":{\"name\":\"noah.kibet@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(27, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 34, '{\"data\":{\"name\":\"sasha.njuguna@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(28, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 35, '{\"data\":{\"name\":\"caleb.mwende@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(29, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 36, '{\"data\":{\"name\":\"talia.wekesa@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(30, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 37, '{\"data\":{\"name\":\"ivy.kimani@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(31, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 38, '{\"data\":{\"name\":\"nathan.barasa@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(32, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 39, '{\"data\":{\"name\":\"aisha.kiplangat@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(33, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 40, '{\"data\":{\"name\":\"derrick.onyango@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(34, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 41, '{\"data\":{\"name\":\"hope.muriuki@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(35, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 42, '{\"data\":{\"name\":\"kevin.simiyu@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(36, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 43, '{\"data\":{\"name\":\"sienna.gichuru@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(37, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 44, '{\"data\":{\"name\":\"trevor.ndungu@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(38, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 45, '{\"data\":{\"name\":\"faith.korir@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(39, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 46, '{\"data\":{\"name\":\"brian.otiso@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(40, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 47, '{\"data\":{\"name\":\"nicole.achieng@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(41, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 48, '{\"data\":{\"name\":\"alex.kilonzo@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(42, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 49, '{\"data\":{\"name\":\"michelle.rono@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(43, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 50, '{\"data\":{\"name\":\"tobias.kariuki@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(44, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 51, '{\"data\":{\"name\":\"grace.wanjala@example.com\",\"note\":\"Auto-created during student import.\"}}', '2025-07-11 22:12:28'),
(45, 5, 4, 'Mash Mungai', '::1', 'SYSTEM', 'students', NULL, '{\"data\":{\"note\":\"Bulk student upload processed 30 new students and skipped 0 duplicates.\"}}', '2025-07-11 22:12:28'),
(46, 5, 4, 'Mash Mungai', '::1', 'SYSTEM', 'items', NULL, '{\"data\":{\"note\":\"Fee structure CSV upload processed 42 items.\"}}', '2025-07-11 22:17:09'),
(47, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 178, '{\"data\":{\"id\":\"178\",\"school_id\":\"5\",\"name\":\"Insurance\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:32'),
(48, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 164, '{\"data\":{\"id\":\"164\",\"school_id\":\"5\",\"name\":\"Activity Fee\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:36'),
(49, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 150, '{\"data\":{\"id\":\"150\",\"school_id\":\"5\",\"name\":\"Books & Stationery\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:38'),
(50, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 171, '{\"data\":{\"id\":\"171\",\"school_id\":\"5\",\"name\":\"Development Levy\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:41'),
(51, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 143, '{\"data\":{\"id\":\"143\",\"school_id\":\"5\",\"name\":\"Lunch\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:44'),
(52, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 136, '{\"data\":{\"id\":\"136\",\"school_id\":\"5\",\"name\":\"Tuition\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:46'),
(53, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 157, '{\"data\":{\"id\":\"157\",\"school_id\":\"5\",\"name\":\"Uniform\",\"price\":\"0.00\",\"description\":null,\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-12 01:17:09\"}}', '2025-07-14 12:40:49'),
(54, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'items', 185, '{\"data\":{\"name\":\"tuition\",\"price\":\"0\",\"parent_id\":null}}', '2025-07-14 12:41:40'),
(55, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'items', 186, '{\"data\":{\"name\":\"Playgroup\",\"price\":\"30000\",\"parent_id\":185}}', '2025-07-14 12:42:02'),
(56, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'items', 185, '{\"data\":{\"id\":\"185\",\"school_id\":\"5\",\"name\":\"tuition\",\"price\":\"0.00\",\"description\":\"\",\"parent_id\":null,\"item_type\":\"parent\",\"created_at\":\"2025-07-14 15:41:40\"}}', '2025-07-14 12:42:10'),
(57, 5, 4, 'Mash Mungai', '::1', 'SYSTEM', 'items', NULL, '{\"data\":{\"note\":\"Fee structure CSV upload processed 42 items.\"}}', '2025-07-14 12:42:33'),
(58, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'invoices', 36, '{\"data\":{\"id\":\"36\",\"school_id\":\"5\",\"student_id\":42,\"invoice_date\":\"2025-07-15\",\"due_date\":\"2025-08-13\",\"items\":[{\"item_id\":\"191\",\"description\":\"\",\"quantity\":1,\"unit_price\":40000},{\"item_id\":\"219\",\"description\":\"\",\"quantity\":1,\"unit_price\":3000},{\"item_id\":\"205\",\"description\":\"\",\"quantity\":1,\"unit_price\":4500},{\"item_id\":\"226\",\"description\":\"\",\"quantity\":1,\"unit_price\":2500},{\"item_id\":\"233\",\"description\":\"\",\"quantity\":1,\"unit_price\":1000},{\"item_id\":\"198\",\"description\":\"\",\"quantity\":1,\"unit_price\":12000},{\"item_id\":\"212\",\"description\":\"\",\"quantity\":1,\"unit_price\":5500}],\"notes\":\"\"}}', '2025-07-14 23:17:31'),
(59, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 42, '{\"data\":{\"id\":\"42\",\"school_id\":\"5\",\"student_id_no\":\"STM001\",\"name\":\"Ava Mwangi\",\"email\":\"Playgroup\",\"phone\":\"12 Apple St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"22\",\"status\":\"active\"}}', '2025-07-15 11:53:48'),
(60, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 42, '{\"data\":{\"id\":\"42\",\"school_id\":\"5\",\"student_id_no\":\"STM001\",\"name\":\"Ava Mwangi\",\"email\":\"Playgroup\",\"phone\":\"12 Apple St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"22\",\"status\":\"inactive\"}}', '2025-07-15 11:53:50'),
(61, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 42, '{\"data\":{\"id\":\"42\",\"school_id\":\"5\",\"student_id_no\":\"STM001\",\"name\":\"Ava Mwangi\",\"email\":\"Playgroup\",\"phone\":\"12 Apple St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"22\",\"status\":\"inactive\"}}', '2025-07-15 11:53:53'),
(62, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 42, '{\"data\":{\"id\":\"42\",\"school_id\":\"5\",\"student_id_no\":\"STM001\",\"name\":\"Ava Mwangi\",\"email\":\"Playgroup\",\"phone\":\"12 Apple St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"22\",\"status\":\"inactive\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(63, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 43, '{\"data\":{\"id\":\"43\",\"school_id\":\"5\",\"student_id_no\":\"STM002\",\"name\":\"Liam Otieno\",\"email\":\"PP1\",\"phone\":\"45 Garden Estate Rd,\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"23\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(64, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 44, '{\"data\":{\"id\":\"44\",\"school_id\":\"5\",\"student_id_no\":\"STM003\",\"name\":\"Zuri Njeri\",\"email\":\"PP2\",\"phone\":\"7 Rose Ave, Kiambu\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"24\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(65, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 45, '{\"data\":{\"id\":\"45\",\"school_id\":\"5\",\"student_id_no\":\"STM004\",\"name\":\"Ethan Kiptoo\",\"email\":\"Grade 1\",\"phone\":\"34 Kericho Lane, Eld\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"25\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(66, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 46, '{\"data\":{\"id\":\"46\",\"school_id\":\"5\",\"student_id_no\":\"STM005\",\"name\":\"Amara Wanjiru\",\"email\":\"Grade 2\",\"phone\":\"23 Lavington Crescen\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"26\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(67, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 47, '{\"data\":{\"id\":\"47\",\"school_id\":\"5\",\"student_id_no\":\"STM006\",\"name\":\"Jayden Mutiso\",\"email\":\"Grade 3\",\"phone\":\"10 Mlolongo Drive, M\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"27\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(68, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 48, '{\"data\":{\"id\":\"48\",\"school_id\":\"5\",\"student_id_no\":\"STM007\",\"name\":\"Maya Chebet\",\"email\":\"Playgroup\",\"phone\":\"56 Moi Ave, Nakuru\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"28\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(69, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 49, '{\"data\":{\"id\":\"49\",\"school_id\":\"5\",\"student_id_no\":\"STM008\",\"name\":\"Ryan Odhiambo\",\"email\":\"PP1\",\"phone\":\"22 Rongo Rd, Kisumu\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"29\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(70, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 50, '{\"data\":{\"id\":\"50\",\"school_id\":\"5\",\"student_id_no\":\"STM009\",\"name\":\"Nia Muthoni\",\"email\":\"PP2\",\"phone\":\"11 Ngong View, Nairo\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"30\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(71, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 51, '{\"data\":{\"id\":\"51\",\"school_id\":\"5\",\"student_id_no\":\"STM010\",\"name\":\"Elijah Ouma\",\"email\":\"Grade 1\",\"phone\":\"9 Kisii Heights, Kis\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"31\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(72, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 52, '{\"data\":{\"id\":\"52\",\"school_id\":\"5\",\"student_id_no\":\"STM011\",\"name\":\"Layla Kamau\",\"email\":\"Grade 2\",\"phone\":\"70 Ridgeways Blvd, N\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"32\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(73, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 53, '{\"data\":{\"id\":\"53\",\"school_id\":\"5\",\"student_id_no\":\"STM012\",\"name\":\"Noah Kibet\",\"email\":\"Grade 3\",\"phone\":\"88 Eldama Ravine Rd,\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"33\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(74, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 54, '{\"data\":{\"id\":\"54\",\"school_id\":\"5\",\"student_id_no\":\"STM013\",\"name\":\"Sasha Njuguna\",\"email\":\"Playgroup\",\"phone\":\"14 Thome Estate, Nai\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"34\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(75, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 55, '{\"data\":{\"id\":\"55\",\"school_id\":\"5\",\"student_id_no\":\"STM014\",\"name\":\"Caleb Mwende\",\"email\":\"PP1\",\"phone\":\"91 South C Rd, Nairo\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"35\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(76, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 56, '{\"data\":{\"id\":\"56\",\"school_id\":\"5\",\"student_id_no\":\"STM015\",\"name\":\"Talia Wekesa\",\"email\":\"PP2\",\"phone\":\"67 Kizingo Crescent,\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"36\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(77, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 57, '{\"data\":{\"id\":\"57\",\"school_id\":\"5\",\"student_id_no\":\"STM016\",\"name\":\"Ivy Kimani\",\"email\":\"Grade 1\",\"phone\":\"28 Umoja St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"37\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(78, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 58, '{\"data\":{\"id\":\"58\",\"school_id\":\"5\",\"student_id_no\":\"STM017\",\"name\":\"Nathan Barasa\",\"email\":\"Grade 2\",\"phone\":\"32 Kitale Lane, Tran\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"38\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(79, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 59, '{\"data\":{\"id\":\"59\",\"school_id\":\"5\",\"student_id_no\":\"STM018\",\"name\":\"Aisha Kiplang?at\",\"email\":\"Grade 3\",\"phone\":\"13 Kiptagat Rd, Keri\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"39\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(80, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 60, '{\"data\":{\"id\":\"60\",\"school_id\":\"5\",\"student_id_no\":\"STM019\",\"name\":\"Derrick Onyango\",\"email\":\"Playgroup\",\"phone\":\"17 Kisumu Ndogo, Kis\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"40\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(81, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 61, '{\"data\":{\"id\":\"61\",\"school_id\":\"5\",\"student_id_no\":\"STM020\",\"name\":\"Hope Muriuki\",\"email\":\"PP1\",\"phone\":\"31 Limuru Rd, Nairob\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"41\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(82, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 62, '{\"data\":{\"id\":\"62\",\"school_id\":\"5\",\"student_id_no\":\"STM021\",\"name\":\"Kevin Simiyu\",\"email\":\"PP2\",\"phone\":\"60 Kakamega Town, Ka\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"42\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(83, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 63, '{\"data\":{\"id\":\"63\",\"school_id\":\"5\",\"student_id_no\":\"STM022\",\"name\":\"Sienna Gichuru\",\"email\":\"Grade 1\",\"phone\":\"21 Parklands Lane, N\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"43\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(84, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 64, '{\"data\":{\"id\":\"64\",\"school_id\":\"5\",\"student_id_no\":\"STM023\",\"name\":\"Trevor Ndung?u\",\"email\":\"Grade 2\",\"phone\":\"15 Embu Avenue, Embu\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"44\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(85, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 65, '{\"data\":{\"id\":\"65\",\"school_id\":\"5\",\"student_id_no\":\"STM024\",\"name\":\"Faith Korir\",\"email\":\"Grade 3\",\"phone\":\"6 Bomet Rd, Bomet\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"45\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(86, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 66, '{\"data\":{\"id\":\"66\",\"school_id\":\"5\",\"student_id_no\":\"STM025\",\"name\":\"Brian Otiso\",\"email\":\"Playgroup\",\"phone\":\"5 Nyali Blvd, Mombas\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"46\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(87, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 67, '{\"data\":{\"id\":\"67\",\"school_id\":\"5\",\"student_id_no\":\"STM026\",\"name\":\"Nicole Achieng\",\"email\":\"PP1\",\"phone\":\"44 Nyalenda, Kisumu\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"47\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(88, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 68, '{\"data\":{\"id\":\"68\",\"school_id\":\"5\",\"student_id_no\":\"STM027\",\"name\":\"Alex Kilonzo\",\"email\":\"PP2\",\"phone\":\"18 Mlolongo Ave, Mac\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"48\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(89, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 69, '{\"data\":{\"id\":\"69\",\"school_id\":\"5\",\"student_id_no\":\"STM028\",\"name\":\"Michelle Rono\",\"email\":\"Grade 1\",\"phone\":\"25 Rongai St, Kajiad\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"49\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(90, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 70, '{\"data\":{\"id\":\"70\",\"school_id\":\"5\",\"student_id_no\":\"STM029\",\"name\":\"Tobias Kariuki\",\"email\":\"Grade 2\",\"phone\":\"39 Thika Greens, Thi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"50\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(91, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 71, '{\"data\":{\"id\":\"71\",\"school_id\":\"5\",\"student_id_no\":\"STM030\",\"name\":\"Grace Wanjala\",\"email\":\"Grade 3\",\"phone\":\"66 Kitengela Drive, \",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"51\",\"status\":\"active\"},\"note\":\"Bulk action.\"}', '2025-07-15 11:54:07'),
(92, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 42, '{\"data\":{\"id\":\"42\",\"school_id\":\"5\",\"student_id_no\":\"STM001\",\"name\":\"Ava Mwangi\",\"email\":\"Playgroup\",\"phone\":\"12 Apple St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"22\",\"status\":\"inactive\"}}', '2025-07-15 11:54:33'),
(93, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 59, '{\"data\":{\"id\":\"59\",\"school_id\":\"5\",\"student_id_no\":\"STM018\",\"name\":\"Aisha Kiplang?at\",\"email\":\"Grade 3\",\"phone\":\"13 Kiptagat Rd, Keri\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"39\",\"status\":\"inactive\"}}', '2025-07-15 12:13:04'),
(94, 5, 4, 'Mash Mungai', '::1', 'DELETE', 'students', 42, '{\"data\":{\"id\":\"42\",\"school_id\":\"5\",\"student_id_no\":\"STM001\",\"name\":\"Ava Mwangi\",\"email\":\"Playgroup\",\"phone\":\"12 Apple St, Nairobi\",\"address\":\"\",\"created_at\":\"2025-07-12 01:12:28\",\"class_id\":\"22\",\"status\":\"inactive\"}}', '2025-07-15 12:13:11'),
(95, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'invoices', 37, '{\"data\":{\"id\":\"37\",\"school_id\":\"5\",\"student_id\":59,\"invoice_date\":\"2025-07-15\",\"due_date\":\"2025-08-14\",\"items\":[{\"item_id\":\"191\",\"description\":\"\",\"quantity\":1,\"unit_price\":40000},{\"item_id\":\"219\",\"description\":\"\",\"quantity\":1,\"unit_price\":3000},{\"item_id\":\"205\",\"description\":\"\",\"quantity\":1,\"unit_price\":4500},{\"item_id\":\"226\",\"description\":\"\",\"quantity\":1,\"unit_price\":2500},{\"item_id\":\"233\",\"description\":\"\",\"quantity\":1,\"unit_price\":1000},{\"item_id\":\"198\",\"description\":\"\",\"quantity\":1,\"unit_price\":12000},{\"item_id\":\"212\",\"description\":\"\",\"quantity\":1,\"unit_price\":5500}],\"notes\":\"\"}}', '2025-07-15 13:42:49'),
(96, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'invoices', 38, '{\"data\":{\"id\":\"38\",\"school_id\":\"5\",\"student_id\":66,\"invoice_date\":\"2025-07-15\",\"due_date\":\"2025-08-14\",\"items\":[{\"item_id\":\"192\",\"description\":\"\",\"quantity\":1,\"unit_price\":43500},{\"item_id\":\"220\",\"description\":\"\",\"quantity\":1,\"unit_price\":2900},{\"item_id\":\"206\",\"description\":\"\",\"quantity\":1,\"unit_price\":5075},{\"item_id\":\"227\",\"description\":\"\",\"quantity\":1,\"unit_price\":2175},{\"item_id\":\"234\",\"description\":\"\",\"quantity\":1,\"unit_price\":725},{\"item_id\":\"213\",\"description\":\"\",\"quantity\":1,\"unit_price\":6525},{\"item_id\":\"199\",\"description\":\"\",\"quantity\":1,\"unit_price\":14500}],\"notes\":\"\"}}', '2025-07-15 13:50:52'),
(97, 5, 4, 'Mash Mungai', '::1', 'SYSTEM', 'accounts', 22, '{\"data\":{\"note\":\"Auto-created Undeposited Funds account.\"}}', '2025-07-15 13:58:39'),
(98, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 24, '{\"data\":{\"receipt_number\":\"REC-68765E8F796D0\",\"student_id\":59,\"amount\":25750,\"method\":\"Cash\"}}', '2025-07-15 13:58:39'),
(99, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 25, '{\"data\":{\"receipt_number\":\"REC-6876B0B9014B1\",\"student_id\":66,\"amount\":34500,\"method\":\"Mobile Money\"}}', '2025-07-15 19:49:13'),
(100, 5, 4, 'Mash Mungai', '::1', 'CREATE', 'classes', 52, '{\"data\":{\"name\":\"Grade 3\",\"note\":\"Auto-created during student import.\"}}', '2025-07-16 21:49:58'),
(101, 5, 4, 'Mash Mungai', '::1', 'SYSTEM', 'students', NULL, '{\"data\":{\"note\":\"Bulk student upload processed 1 new students and skipped 0 duplicates.\"}}', '2025-07-16 21:49:58'),
(102, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'classes', 53, '{\"data\":{\"name\":\"Grade 1\",\"note\":\"Auto-created during student import.\"}}', '2025-07-16 22:00:20'),
(103, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'classes', 54, '{\"data\":{\"name\":\"Grade 2\",\"note\":\"Auto-created during student import.\"}}', '2025-07-16 22:00:20'),
(104, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'classes', 55, '{\"data\":{\"name\":\"Grade 3\",\"note\":\"Auto-created during student import.\"}}', '2025-07-16 22:00:20'),
(105, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'classes', 56, '{\"data\":{\"name\":\"Grade 4\",\"note\":\"Auto-created during student import.\"}}', '2025-07-16 22:00:20'),
(106, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'classes', 57, '{\"data\":{\"name\":\"Grade 5\",\"note\":\"Auto-created during student import.\"}}', '2025-07-16 22:00:20'),
(107, 6, 5, 'Mash Mungai', '::1', 'SYSTEM', 'students', NULL, '{\"data\":{\"note\":\"Bulk student upload processed 50 new students and skipped 0 duplicates.\"}}', '2025-07-16 22:00:20'),
(108, 6, 5, 'Mash Mungai', '::1', 'SYSTEM', 'items', NULL, '{\"data\":{\"note\":\"Fee structure CSV upload processed 35 items.\"}}', '2025-07-16 22:01:20'),
(109, 6, 5, 'Mash Mungai', '::1', 'SYSTEM', 'accounts', 27, '{\"data\":{\"note\":\"Auto-created Undeposited Funds account.\"}}', '2025-07-16 22:12:10'),
(110, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 26, '{\"data\":{\"receipt_number\":\"REC-687823BA21638\",\"student_id\":99,\"amount\":75000,\"method\":\"Mobile Money\"}}', '2025-07-16 22:12:10'),
(111, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 27, '{\"data\":{\"receipt_number\":\"REC-687823D6F2133\",\"student_id\":100,\"amount\":47500,\"method\":\"Mobile Money\"}}', '2025-07-16 22:12:38'),
(112, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 28, '{\"data\":{\"receipt_number\":\"REC-6878245F04612\",\"student_id\":74,\"amount\":35490,\"method\":\"Bank Transfer\"}}', '2025-07-16 22:14:55'),
(113, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 29, '{\"data\":{\"receipt_number\":\"REC-687C339B9EE93\",\"student_id\":74,\"amount\":15000,\"method\":\"Bank Transfer\"}}', '2025-07-20 00:08:59'),
(114, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 30, '{\"data\":{\"receipt_number\":\"REC-687C36C1D0382\",\"student_id\":99,\"amount\":5000,\"method\":\"Cash\"}}', '2025-07-20 00:22:25'),
(115, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 31, '{\"data\":{\"receipt_number\":\"REC-687C375C46EE7\",\"student_id\":82,\"amount\":32500,\"method\":\"Mobile Money\"}}', '2025-07-20 00:25:00'),
(116, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 32, '{\"data\":{\"receipt_number\":\"REC-687C3DAC83D9A\",\"student_id\":77,\"amount\":74500,\"method\":\"Mobile Money\"}}', '2025-07-20 00:51:56'),
(117, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 33, '{\"data\":{\"receipt_number\":\"REC-687C404F551E3\",\"student_id\":102,\"amount\":25000,\"method\":\"Cash\"}}', '2025-07-20 01:03:11'),
(118, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 34, '{\"data\":{\"receipt_number\":\"REC-6883C14C88478\",\"student_id\":75,\"amount\":21300,\"method\":\"Mobile Money\"}}', '2025-07-25 17:39:24'),
(119, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 35, '{\"data\":{\"receipt_number\":\"REC-6883E61094F46\",\"student_id\":105,\"amount\":37500,\"method\":\"Bank Transfer\"}}', '2025-07-25 20:16:16'),
(120, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'payment_receipts', 36, '{\"data\":{\"receipt_number\":\"REC-688685F285B9E\",\"student_id\":100,\"amount\":25000,\"method\":\"Bank Transfer\"}}', '2025-07-27 20:02:58'),
(121, 6, 5, 'Mash Mungai', '::1', 'UPDATE', 'invoice_templates', 13, '{\"before\":{\"id\":\"13\",\"school_id\":\"6\",\"name\":\"Grade 1\",\"items\":\"[]\",\"created_at\":\"2025-07-17 01:09:32\",\"class_id\":\"53\"},\"after\":{\"name\":\"Grade 1\",\"items\":\"[{\\\"item_id\\\":\\\"261\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3000.00\\\"},{\\\"item_id\\\":\\\"249\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"4500.00\\\"},{\\\"item_id\\\":\\\"267\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\"},{\\\"item_id\\\":\\\"273\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"1000.00\\\"},{\\\"item_id\\\":\\\"237\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"40000.00\\\"},{\\\"item_id\\\":\\\"255\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\"}]\"}}', '2025-08-02 03:54:00'),
(122, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'invoices', 100, '{\"data\":{\"id\":\"100\",\"school_id\":\"6\",\"student_id\":77,\"invoice_date\":\"2025-08-03\",\"due_date\":\"2025-09-02\",\"items\":[{\"item_id\":\"261\",\"description\":\"\",\"quantity\":1,\"unit_price\":3000},{\"item_id\":\"249\",\"description\":\"\",\"quantity\":1,\"unit_price\":4500},{\"item_id\":\"267\",\"description\":\"\",\"quantity\":1,\"unit_price\":2500},{\"item_id\":\"273\",\"description\":\"\",\"quantity\":1,\"unit_price\":1000},{\"item_id\":\"237\",\"description\":\"\",\"quantity\":1,\"unit_price\":40000},{\"item_id\":\"255\",\"description\":\"\",\"quantity\":1,\"unit_price\":5500}],\"notes\":\"\"}}', '2025-08-03 13:47:29'),
(123, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'invoices', 101, '{\"data\":{\"id\":\"101\",\"school_id\":\"6\",\"student_id\":74,\"invoice_date\":\"2025-04-01\",\"due_date\":\"2024-04-30\",\"items\":[{\"item_id\":\"261\",\"description\":\"\",\"quantity\":1,\"unit_price\":3000},{\"item_id\":\"249\",\"description\":\"\",\"quantity\":1,\"unit_price\":4500},{\"item_id\":\"267\",\"description\":\"\",\"quantity\":1,\"unit_price\":2500},{\"item_id\":\"273\",\"description\":\"\",\"quantity\":1,\"unit_price\":1000},{\"item_id\":\"237\",\"description\":\"\",\"quantity\":1,\"unit_price\":40000},{\"item_id\":\"255\",\"description\":\"\",\"quantity\":1,\"unit_price\":5500}],\"notes\":\"\"}}', '2025-08-05 11:05:46'),
(124, 7, 6, 'King Mungai', '::1', 'CREATE', 'classes', 58, '{\"data\":{\"name\":\"Grade 2\",\"note\":\"Auto-created during student import.\"}}', '2025-08-06 14:30:22'),
(125, 7, 6, 'King Mungai', '::1', 'CREATE', 'classes', 59, '{\"data\":{\"name\":\"Grade 1\",\"note\":\"Auto-created during student import.\"}}', '2025-08-06 14:30:22'),
(126, 7, 6, 'King Mungai', '::1', 'CREATE', 'classes', 60, '{\"data\":{\"name\":\"Grade 4\",\"note\":\"Auto-created during student import.\"}}', '2025-08-06 14:30:22'),
(127, 7, 6, 'King Mungai', '::1', 'CREATE', 'classes', 61, '{\"data\":{\"name\":\"Grade 5\",\"note\":\"Auto-created during student import.\"}}', '2025-08-06 14:30:22'),
(128, 7, 6, 'King Mungai', '::1', 'CREATE', 'classes', 62, '{\"data\":{\"name\":\"Grade 3\",\"note\":\"Auto-created during student import.\"}}', '2025-08-06 14:30:22'),
(129, 7, 6, 'King Mungai', '::1', 'CREATE', 'classes', 63, '{\"data\":{\"name\":\"Grade 6\",\"note\":\"Auto-created during student import.\"}}', '2025-08-06 14:30:22'),
(130, 7, 6, 'King Mungai', '::1', 'SYSTEM', 'students', NULL, '{\"data\":{\"note\":\"Bulk student upload processed 100 new students and skipped 0 duplicates.\"}}', '2025-08-06 14:30:22'),
(131, 7, 6, 'King Mungai', '::1', 'SYSTEM', 'items', NULL, '{\"data\":{\"note\":\"Fee structure CSV upload processed 35 items.\"}}', '2025-08-11 15:22:31'),
(132, 7, 6, 'King Mungai', '::1', 'UPDATE', 'invoice_templates', 14, '{\"before\":{\"id\":\"14\",\"school_id\":\"7\",\"name\":\"Grade 1\",\"items\":\"[{\\\"item_id\\\":\\\"279\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"40000.00\\\"},{\\\"item_id\\\":\\\"297\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\"},{\\\"item_id\\\":\\\"291\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"4500.00\\\"},{\\\"item_id\\\":\\\"309\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\"},{\\\"item_id\\\":\\\"315\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"1000.00\\\"},{\\\"item_id\\\":\\\"303\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3000.00\\\"},{\\\"item_id\\\":\\\"285\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"12000.00\\\"}]\",\"created_at\":\"2025-08-11 22:07:49\",\"class_id\":null},\"after\":{\"name\":\"Grade 1\",\"items\":\"[{\\\"item_id\\\":\\\"279\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"40000.00\\\"},{\\\"item_id\\\":\\\"297\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\"},{\\\"item_id\\\":\\\"291\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"4500.00\\\"},{\\\"item_id\\\":\\\"309\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\"},{\\\"item_id\\\":\\\"315\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"1000.00\\\"},{\\\"item_id\\\":\\\"303\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3000.00\\\"},{\\\"item_id\\\":\\\"285\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"12000.00\\\"}]\"}}', '2025-08-11 19:18:00'),
(133, 7, 6, 'King Mungai', '::1', 'UPDATE', 'invoice_templates', 15, '{\"before\":{\"id\":\"15\",\"school_id\":\"7\",\"name\":\"Grade 2\",\"items\":\"[{\\\"item_id\\\":\\\"304\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2900.00\\\"},{\\\"item_id\\\":\\\"292\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5075.00\\\"},{\\\"item_id\\\":\\\"310\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2175.00\\\"},{\\\"item_id\\\":\\\"316\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"725.00\\\"},{\\\"item_id\\\":\\\"286\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"14500.00\\\"},{\\\"item_id\\\":\\\"280\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"43500.00\\\"},{\\\"item_id\\\":\\\"298\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"6525.00\\\"}]\",\"created_at\":\"2025-08-11 22:08:42\",\"class_id\":null},\"after\":{\"name\":\"Grade 2\",\"items\":\"[{\\\"item_id\\\":\\\"304\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2900.00\\\"},{\\\"item_id\\\":\\\"292\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5075.00\\\"},{\\\"item_id\\\":\\\"310\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2175.00\\\"},{\\\"item_id\\\":\\\"316\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"725.00\\\"},{\\\"item_id\\\":\\\"286\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"14500.00\\\"},{\\\"item_id\\\":\\\"280\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"43500.00\\\"},{\\\"item_id\\\":\\\"298\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"6525.00\\\"}]\"}}', '2025-08-11 19:18:08'),
(134, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 181, '{\"data\":{\"student_id\":126,\"template_id\":14,\"invoice_number\":\"INV-SCH7-1\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(135, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 182, '{\"data\":{\"student_id\":127,\"template_id\":14,\"invoice_number\":\"INV-SCH7-2\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(136, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 183, '{\"data\":{\"student_id\":142,\"template_id\":14,\"invoice_number\":\"INV-SCH7-3\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(137, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 184, '{\"data\":{\"student_id\":152,\"template_id\":14,\"invoice_number\":\"INV-SCH7-4\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(138, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 185, '{\"data\":{\"student_id\":154,\"template_id\":14,\"invoice_number\":\"INV-SCH7-5\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(139, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 186, '{\"data\":{\"student_id\":155,\"template_id\":14,\"invoice_number\":\"INV-SCH7-6\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(140, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 187, '{\"data\":{\"student_id\":157,\"template_id\":14,\"invoice_number\":\"INV-SCH7-7\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(141, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 188, '{\"data\":{\"student_id\":160,\"template_id\":14,\"invoice_number\":\"INV-SCH7-8\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(142, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 189, '{\"data\":{\"student_id\":167,\"template_id\":14,\"invoice_number\":\"INV-SCH7-9\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(143, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 190, '{\"data\":{\"student_id\":173,\"template_id\":14,\"invoice_number\":\"INV-SCH7-10\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(144, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 191, '{\"data\":{\"student_id\":180,\"template_id\":14,\"invoice_number\":\"INV-SCH7-11\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(145, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 192, '{\"data\":{\"student_id\":202,\"template_id\":14,\"invoice_number\":\"INV-SCH7-12\",\"total_amount\":68500}}', '2025-08-11 19:43:33'),
(146, 7, 6, 'King Mungai', '::1', 'CREATE', 'invoices', 193, '{\"data\":{\"student_id\":207,\"template_id\":14,\"invoice_number\":\"INV-SCH7-13\",\"total_amount\":68500}}', '2025-08-11 19:43:34'),
(147, 7, 6, 'King Mungai', '::1', 'UPDATE', 'schools', 7, '{\"before\":{\"id\":\"7\",\"name\":\"The School\",\"created_at\":\"2025-08-06 17:08:44\"},\"after\":{\"name\":\"The School\"}}', '2025-08-12 06:22:41'),
(148, 7, 6, 'King Mungai', '::1', 'UPDATE', 'school_details', 7, '{\"before\":false,\"after\":{\"address\":\"Nairobi City\",\"phone\":\"0769855953\",\"email\":\"info@mash.com\",\"logo_url\":\"\"}}', '2025-08-12 06:22:41'),
(150, 8, 7, 'King Mungai', '::1', 'CREATE', 'classes', 65, '{\"data\":{\"name\":\"Grade 2\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 08:57:52'),
(151, 8, 7, 'King Mungai', '::1', 'CREATE', 'classes', 66, '{\"data\":{\"name\":\"Grade 1\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 08:57:52'),
(152, 8, 7, 'King Mungai', '::1', 'CREATE', 'classes', 67, '{\"data\":{\"name\":\"Grade 4\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 08:57:52'),
(153, 8, 7, 'King Mungai', '::1', 'CREATE', 'classes', 68, '{\"data\":{\"name\":\"Grade 5\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 08:57:52'),
(154, 8, 7, 'King Mungai', '::1', 'CREATE', 'classes', 69, '{\"data\":{\"name\":\"Grade 3\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 08:57:52'),
(155, 8, 7, 'King Mungai', '::1', 'CREATE', 'classes', 70, '{\"data\":{\"name\":\"Grade 6\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 08:57:52'),
(156, 8, 7, 'King Mungai', '::1', 'SYSTEM', 'students', NULL, '{\"data\":{\"note\":\"Bulk student upload processed 100 new students and skipped 0 duplicates.\"}}', '2025-08-12 08:57:52'),
(157, 8, 7, 'King Mungai', '::1', 'SYSTEM', 'items', NULL, '{\"data\":{\"note\":\"Fee structure CSV upload processed 35 items.\"}}', '2025-08-12 08:58:11'),
(158, 8, 7, 'King Mungai', '::1', 'UPDATE', 'invoice_templates', 19, '{\"before\":{\"id\":\"19\",\"school_id\":\"8\",\"name\":\"Grade 1\",\"items\":\"[{\\\"item_id\\\":\\\"345\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3000.00\\\"},{\\\"item_id\\\":\\\"333\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"4500.00\\\"},{\\\"item_id\\\":\\\"351\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\"},{\\\"item_id\\\":\\\"357\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"1000.00\\\"},{\\\"item_id\\\":\\\"327\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"12000.00\\\"},{\\\"item_id\\\":\\\"321\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"40000.00\\\"},{\\\"item_id\\\":\\\"339\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\"}]\",\"created_at\":\"2025-08-12 11:59:41\",\"class_id\":null},\"after\":{\"name\":\"Grade 1\",\"items\":\"[{\\\"item_id\\\":\\\"345\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3000.00\\\"},{\\\"item_id\\\":\\\"333\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"4500.00\\\"},{\\\"item_id\\\":\\\"351\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\"},{\\\"item_id\\\":\\\"357\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"1000.00\\\"},{\\\"item_id\\\":\\\"327\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"12000.00\\\"},{\\\"item_id\\\":\\\"321\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"40000.00\\\"},{\\\"item_id\\\":\\\"339\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\"}]\"}}', '2025-08-12 10:45:33'),
(159, 9, 8, 'Dwgiht', '::1', 'CREATE', 'classes', 71, '{\"data\":{\"name\":\"Grade 2\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 11:02:22'),
(160, 9, 8, 'Dwgiht', '::1', 'CREATE', 'classes', 72, '{\"data\":{\"name\":\"Grade 1\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 11:02:22'),
(161, 9, 8, 'Dwgiht', '::1', 'CREATE', 'classes', 73, '{\"data\":{\"name\":\"Grade 4\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 11:02:22'),
(162, 9, 8, 'Dwgiht', '::1', 'CREATE', 'classes', 74, '{\"data\":{\"name\":\"Grade 5\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 11:02:22'),
(163, 9, 8, 'Dwgiht', '::1', 'CREATE', 'classes', 75, '{\"data\":{\"name\":\"Grade 3\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 11:02:22'),
(164, 9, 8, 'Dwgiht', '::1', 'CREATE', 'classes', 76, '{\"data\":{\"name\":\"Grade 6\",\"note\":\"Auto-created during student import.\"}}', '2025-08-12 11:02:22'),
(165, 9, 8, 'Dwgiht', '::1', 'SYSTEM', 'students', NULL, '{\"data\":{\"note\":\"Bulk student upload processed 100 new students and skipped 0 duplicates.\"}}', '2025-08-12 11:02:22'),
(166, 9, 8, 'Dwgiht', '::1', 'SYSTEM', 'items', NULL, '{\"data\":{\"note\":\"Fee structure CSV upload processed 35 items.\"}}', '2025-08-12 11:02:39'),
(167, 9, 8, 'Dwgiht', '::1', 'SYSTEM', 'accounts', 52, '{\"data\":{\"note\":\"Auto-created Undeposited Funds account.\"}}', '2025-08-12 11:26:38'),
(168, 6, 5, 'Mash Mungai', '::1', 'CREATE', 'invoices', 275, '{\"data\":{\"id\":\"275\",\"school_id\":\"6\",\"student_id\":424,\"invoice_date\":\"2025-08-24\",\"due_date\":\"2025-09-23\",\"items\":[{\"item_id\":\"261\",\"description\":\"\",\"quantity\":1,\"unit_price\":3000},{\"item_id\":\"249\",\"description\":\"\",\"quantity\":1,\"unit_price\":4500},{\"item_id\":\"267\",\"description\":\"\",\"quantity\":1,\"unit_price\":2500},{\"item_id\":\"273\",\"description\":\"\",\"quantity\":1,\"unit_price\":1000},{\"item_id\":\"237\",\"description\":\"\",\"quantity\":1,\"unit_price\":40000},{\"item_id\":\"255\",\"description\":\"\",\"quantity\":1,\"unit_price\":5500}],\"notes\":\"\"}}', '2025-08-24 12:03:32'),
(169, 9, 8, 'Dwgiht', '::1', 'CREATE', 'items', 406, '{\"data\":{\"name\":\"Tuition Fee\"}}', '2025-08-28 11:23:57'),
(170, 9, 8, 'Dwgiht', '::1', 'CREATE', 'items', 407, '{\"data\":{\"name\":\"Transport Fees\"}}', '2025-08-28 11:39:31'),
(171, 9, 8, 'Dwgiht', '::1', 'CREATE', 'invoices', 276, '{\"data\":{\"id\":\"276\",\"school_id\":\"9\",\"student_id\":376,\"invoice_date\":\"2025-08-28\",\"due_date\":\"2025-09-27\",\"items\":[{\"item_id\":\"362\",\"description\":\"Tuition\",\"quantity\":1,\"unit_price\":44500},{\"item_id\":\"386\",\"description\":\"Activity Fee\",\"quantity\":1,\"unit_price\":5000},{\"item_id\":\"407\",\"description\":\"Transport Fees\",\"quantity\":1,\"unit_price\":10000}],\"notes\":\"\"}}', '2025-08-28 11:47:34'),
(172, 9, 8, 'Dwgiht', '::1', 'CREATE', 'invoices', 277, '{\"data\":{\"id\":\"277\",\"school_id\":\"9\",\"student_id\":377,\"invoice_date\":\"2025-08-28\",\"due_date\":\"2025-09-27\",\"items\":[{\"item_id\":\"386\",\"description\":\"Activity Fee\",\"quantity\":1,\"unit_price\":5000},{\"item_id\":\"406\",\"description\":\"Tuition Fee\",\"quantity\":1,\"unit_price\":40000},{\"item_id\":\"407\",\"description\":\"Transport Fees\",\"quantity\":1,\"unit_price\":10000}],\"notes\":\"\"}}', '2025-08-28 11:47:52');
INSERT INTO `audit_log` (`id`, `school_id`, `user_id`, `user_name`, `ip_address`, `action_type`, `target_table`, `target_id`, `details`, `created_at`) VALUES
(173, 9, 8, 'Dwgiht', '::1', 'DELETE', 'invoice_templates', 20, '{\"data\":{\"id\":\"20\",\"school_id\":\"9\",\"name\":\"Grade 1\",\"items\":\"[{\\\"item_id\\\":\\\"363\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"40000.00\\\"},{\\\"item_id\\\":\\\"375\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"4500.00\\\"},{\\\"item_id\\\":\\\"387\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3000.00\\\"},{\\\"item_id\\\":\\\"393\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\"},{\\\"item_id\\\":\\\"399\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"1000.00\\\"},{\\\"item_id\\\":\\\"381\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\"},{\\\"item_id\\\":\\\"369\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"12000.00\\\"}]\",\"created_at\":\"2025-08-12 14:18:59\",\"class_id\":null}}', '2025-08-28 13:22:50'),
(174, 9, 8, 'Dwgiht', '::1', 'DELETE', 'invoice_templates', 21, '{\"data\":{\"id\":\"21\",\"school_id\":\"9\",\"name\":\"Grade 2\",\"items\":\"[{\\\"item_id\\\":\\\"388\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2900.00\\\"},{\\\"item_id\\\":\\\"376\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5075.00\\\"},{\\\"item_id\\\":\\\"394\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2175.00\\\"},{\\\"item_id\\\":\\\"400\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"725.00\\\"},{\\\"item_id\\\":\\\"364\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"43500.00\\\"},{\\\"item_id\\\":\\\"370\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"14500.00\\\"},{\\\"item_id\\\":\\\"382\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"6525.00\\\"}]\",\"created_at\":\"2025-08-12 14:41:04\",\"class_id\":null}}', '2025-08-28 13:22:53'),
(175, 9, 8, 'Dwgiht', '::1', 'DELETE', 'invoice_templates', 22, '{\"data\":{\"id\":\"22\",\"school_id\":\"9\",\"name\":\"Grade 3\",\"items\":\"[{\\\"item_id\\\":\\\"389\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3200.00\\\"},{\\\"item_id\\\":\\\"377\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5600.00\\\"},{\\\"item_id\\\":\\\"395\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2400.00\\\"},{\\\"item_id\\\":\\\"401\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"800.00\\\"},{\\\"item_id\\\":\\\"371\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"16000.00\\\"},{\\\"item_id\\\":\\\"365\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"48000.00\\\"},{\\\"item_id\\\":\\\"383\\\",\\\"description\\\":\\\"\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"7200.00\\\"}]\",\"created_at\":\"2025-08-13 18:18:34\",\"class_id\":null}}', '2025-08-28 13:22:56'),
(176, 11, 10, 'Clark Kent', '::1', 'CREATE', 'invoices', 278, '{\"data\":{\"id\":\"278\",\"school_id\":\"11\",\"student_id\":432,\"invoice_date\":\"2025-08-28\",\"due_date\":\"2025-09-27\",\"items\":[{\"item_id\":\"414\",\"description\":\"Tuition\",\"quantity\":1,\"unit_price\":38500},{\"item_id\":\"415\",\"description\":\"LUNCH & BREAK\",\"quantity\":1,\"unit_price\":10000},{\"item_id\":\"442\",\"description\":\"Sports\",\"quantity\":1,\"unit_price\":5000},{\"item_id\":\"433\",\"description\":\"Skating\",\"quantity\":1,\"unit_price\":5000}],\"notes\":\"\"}}', '2025-08-28 17:02:20'),
(177, 11, 10, 'Clark Kent', '::1', 'SYSTEM', 'accounts', 58, '{\"data\":{\"note\":\"Auto-created Undeposited Funds account.\"}}', '2025-08-30 07:07:51'),
(178, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'schools', 11, '{\"before\":{\"id\":\"11\",\"name\":\"Metropolis School\",\"created_at\":\"2025-08-28 17:01:10\"},\"after\":{\"name\":\"Metropolis School\"}}', '2025-08-31 14:13:40'),
(179, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'school_details', 11, '{\"before\":false,\"after\":{\"address\":\"\",\"phone\":\"\",\"email\":\"\",\"logo_url\":\"file:\\/\\/\\/C:\\/Users\\/Admin\\/Downloads\\/Bloomsfield%20Logo%20Mockup%201.svg\"}}', '2025-08-31 14:13:40'),
(180, 11, 10, 'Clark Kent', '::1', 'CREATE', 'invoices', 279, '{\"data\":{\"id\":\"279\",\"school_id\":\"11\",\"student_id\":430,\"invoice_date\":\"2025-08-31\",\"due_date\":\"2025-09-30\",\"items\":[{\"item_id\":\"414\",\"description\":\"Tuition\",\"quantity\":1,\"unit_price\":36000},{\"item_id\":\"415\",\"description\":\"LUNCH & BREAK\",\"quantity\":1,\"unit_price\":10000},{\"item_id\":\"435\",\"description\":\"Taekwondo\",\"quantity\":1,\"unit_price\":5000},{\"item_id\":\"433\",\"description\":\"Skating\",\"quantity\":1,\"unit_price\":5000}],\"notes\":\"\"}}', '2025-08-31 21:31:22'),
(181, 11, 10, 'Clark Kent', '::1', 'CREATE', 'invoices', 280, '{\"data\":{\"id\":\"280\",\"school_id\":\"11\",\"student_id\":433,\"invoice_date\":\"2025-09-01\",\"due_date\":\"2025-10-01\",\"items\":[{\"item_id\":\"410\",\"description\":\"Diary (BB, Beginner)\",\"quantity\":1,\"unit_price\":500},{\"item_id\":\"414\",\"description\":\"Tuition\",\"quantity\":1,\"unit_price\":38500},{\"item_id\":\"415\",\"description\":\"LUNCH & BREAK\",\"quantity\":1,\"unit_price\":10000},{\"item_id\":\"417\",\"description\":\"Transport - ZONE 1 (Round Trip)\",\"quantity\":1,\"unit_price\":10000}],\"notes\":\"\"}}', '2025-09-01 06:32:08'),
(182, 11, 10, 'Clark Kent', '::1', 'CREATE', 'invoices', 281, '{\"data\":{\"id\":\"281\",\"school_id\":\"11\",\"student_id\":429,\"invoice_date\":\"2025-09-01\",\"due_date\":\"2025-10-01\",\"items\":[{\"item_id\":\"410\",\"description\":\"Diary (BB, Beginner)\",\"quantity\":1,\"unit_price\":500},{\"item_id\":\"414\",\"description\":\"Tuition\",\"quantity\":1,\"unit_price\":32500},{\"item_id\":\"415\",\"description\":\"LUNCH & BREAK\",\"quantity\":1,\"unit_price\":9000},{\"item_id\":\"416\",\"description\":\"SPORTS\\/SWIMMING \\/KINDER MUSIC\",\"quantity\":1,\"unit_price\":3000}],\"notes\":\"\"}}', '2025-09-01 17:45:57'),
(183, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'schools', 11, '{\"before\":{\"id\":\"11\",\"name\":\"Metropolis School\",\"created_at\":\"2025-08-28 17:01:10\"},\"after\":{\"name\":\"Metropolis School\"}}', '2025-09-05 09:41:44'),
(184, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'school_details', 11, '{\"before\":{\"id\":\"2\",\"school_id\":\"11\",\"address\":\"\",\"phone\":\"\",\"email\":\"\",\"logo_url\":\"file:\\/\\/\\/C:\\/Users\\/Admin\\/Downloads\\/Bloomsfield%20Logo%20Mockup%201.svg\",\"currency\":\"$\",\"created_at\":\"2025-08-31 17:13:40\",\"updated_at\":\"2025-08-31 17:13:40\"},\"after\":{\"address\":\"\",\"phone\":\"\",\"email\":\"\",\"logo_url\":\"uploads\\/logos\\/logo_11_1757065304.png\"}}', '2025-09-05 09:41:44'),
(185, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'schools', 11, '{\"before\":{\"id\":\"11\",\"name\":\"Metropolis School\",\"created_at\":\"2025-08-28 17:01:10\"},\"after\":{\"name\":\"Metropolis School\"}}', '2025-09-05 17:15:58'),
(186, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'school_details', 11, '{\"before\":{\"id\":\"2\",\"school_id\":\"11\",\"address\":\"\",\"phone\":\"\",\"email\":\"\",\"logo_url\":\"uploads\\/logos\\/logo_11_1757065304.png\",\"currency\":\"$\",\"created_at\":\"2025-08-31 17:13:40\",\"updated_at\":\"2025-09-05 12:41:44\"},\"after\":{\"address\":\"rfyitfuyhjmvk,iu,fktrytedjl;kjlytukkiug\\r\\nyutumrnytryygeuy\\r\\n8tytqrtyukjyrtu\",\"phone\":\"0769855953\",\"email\":\"mungaimacharia308@gmail.com\",\"logo_url\":\"uploads\\/logos\\/logo_11_1757065304.png\"}}', '2025-09-05 17:15:58'),
(187, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'schools', 11, '{\"before\":{\"id\":\"11\",\"name\":\"Metropolis School\",\"created_at\":\"2025-08-28 17:01:10\"},\"after\":{\"name\":\"BLOOMSFIELD SCHOOL\"}}', '2025-09-07 11:59:44'),
(188, 11, 10, 'Clark Kent', '::1', 'UPDATE', 'school_details', 11, '{\"before\":{\"id\":\"2\",\"school_id\":\"11\",\"address\":\"rfyitfuyhjmvk,iu,fktrytedjl;kjlytukkiug\\r\\nyutumrnytryygeuy\\r\\n8tytqrtyukjyrtu\",\"phone\":\"0769855953\",\"email\":\"mungaimacharia308@gmail.com\",\"logo_url\":\"uploads\\/logos\\/logo_11_1757065304.png\",\"currency\":\"$\",\"created_at\":\"2025-08-31 17:13:40\",\"updated_at\":\"2025-09-05 20:15:58\"},\"after\":{\"address\":\"rfyitfuyhjmvk,iu,fktrytedjl;kjlytukkiug\\r\\nyutumrnytryygeuy\\r\\n8tytqrtyukjyrtu\",\"phone\":\"0769855953\",\"email\":\"mungaimacharia308@gmail.com\",\"logo_url\":\"uploads\\/logos\\/logo_11_1757065304.png\"}}', '2025-09-07 11:59:44'),
(189, 11, 10, 'Clark Kent', '::1', 'CREATE', 'invoices', 283, '{\"data\":{\"id\":\"283\",\"student_id\":431,\"date\":\"2025-09-07\",\"items_count\":4}}', '2025-09-07 12:00:28'),
(190, 11, 10, 'Clark Kent', '::1', 'CREATE', 'items', 444, '{\"data\":{\"name\":\"Balance Brought Forward\",\"note\":\"Auto-created system item.\"}}', '2025-09-16 23:42:40'),
(191, 11, 10, 'Clark Kent', '::1', 'CREATE', 'invoices', 295, '{\"data\":{\"id\":\"295\",\"student_id\":431,\"date\":\"2025-09-24\",\"items_count\":2}}', '2025-09-24 04:06:10'),
(192, 12, 11, 'Macharia Mungai', '::1', 'CREATE', 'items', 445, '{\"data\":{\"name\":\"Tuition\"}}', '2025-11-29 20:10:08'),
(193, 12, 11, 'Macharia Mungai', '::1', 'CREATE', 'items', 446, '{\"data\":{\"name\":\"Tuition\"}}', '2025-11-29 20:10:45'),
(194, 12, 11, 'Macharia Mungai', '::1', 'CREATE', 'items', 447, '{\"data\":{\"name\":\"Tuition\"}}', '2025-11-29 20:10:45'),
(195, 7, 6, 'King Mungai', '::1', 'CREATE', 'items', 448, '{\"data\":{\"name\":\"Transport\"}}', '2026-01-01 11:21:44');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','archived') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budgets`
--

INSERT INTO `budgets` (`id`, `school_id`, `name`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 6, 'Term 2 Budget', '2025-05-01', '2025-07-28', 'active', '2025-08-01 11:15:37'),
(2, 6, 'Term 2 Budget', '2025-05-01', '2025-07-28', 'active', '2025-08-01 11:18:40');

-- --------------------------------------------------------

--
-- Table structure for table `budget_lines`
--

CREATE TABLE `budget_lines` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `budgeted_amount` decimal(15,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_lines`
--

INSERT INTO `budget_lines` (`id`, `budget_id`, `account_id`, `budgeted_amount`) VALUES
(1, 1, 30, 700000.00),
(2, 1, 37, 500000.00),
(3, 1, 36, 1500000.00),
(4, 1, 38, 500000.00),
(5, 2, 30, 700000.00),
(6, 2, 37, 500000.00),
(7, 2, 36, 1500000.00),
(8, 2, 38, 500000.00);

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `next_class_id` int(11) DEFAULT NULL,
  `invoice_template_id` int(11) DEFAULT NULL,
  `is_archived` tinyint(1) NOT NULL DEFAULT 0,
  `display_order` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`id`, `school_id`, `name`, `description`, `created_at`, `next_class_id`, `invoice_template_id`, `is_archived`, `display_order`) VALUES
(1, 1, 'Playgroup', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(2, 1, 'Beginner', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(3, 1, 'PP1', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(4, 1, 'PP2', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(5, 1, 'Grade 1', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(6, 1, 'Grade 2', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(7, 1, 'Grade 3', NULL, '2025-06-16 17:01:13', NULL, NULL, 0, 0),
(8, 3, 'Grade 1', NULL, '2025-07-09 15:14:54', NULL, NULL, 0, 0),
(9, 3, 'Grade 2', NULL, '2025-07-09 15:17:36', NULL, NULL, 0, 0),
(10, 3, 'Grade 3', NULL, '2025-07-09 15:17:42', NULL, NULL, 0, 0),
(11, 3, 'PP1', NULL, '2025-07-09 15:17:54', NULL, NULL, 0, 0),
(12, 3, 'PP2', NULL, '2025-07-09 15:18:26', NULL, NULL, 0, 0),
(13, 3, 'BEGINNER', NULL, '2025-07-09 15:18:45', NULL, NULL, 0, 0),
(15, 4, 'PP1', NULL, '2025-07-10 15:25:12', NULL, NULL, 0, 0),
(16, 4, 'PP2', NULL, '2025-07-10 15:25:17', NULL, NULL, 0, 0),
(17, 4, 'Grade 1', NULL, '2025-07-10 15:25:22', NULL, NULL, 0, 0),
(18, 4, 'Grade 2', NULL, '2025-07-10 15:25:29', NULL, NULL, 0, 0),
(19, 4, 'Grade 3', NULL, '2025-07-10 15:25:34', NULL, NULL, 0, 0),
(20, 4, 'Playgroup', NULL, '2025-07-11 23:57:37', NULL, NULL, 0, 0),
(22, 5, 'ava.mwangi@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(23, 5, 'liam.otieno@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(24, 5, 'zuri.njeri@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(25, 5, 'ethan.kiptoo@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(26, 5, 'amara.wanjiru@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(27, 5, 'jayden.mutiso@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(28, 5, 'maya.chebet@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(29, 5, 'ryan.odhiambo@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(30, 5, 'nia.muthoni@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(31, 5, 'elijah.ouma@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(32, 5, 'layla.kamau@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(33, 5, 'noah.kibet@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(34, 5, 'sasha.njuguna@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(35, 5, 'caleb.mwende@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(36, 5, 'talia.wekesa@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(37, 5, 'ivy.kimani@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(38, 5, 'nathan.barasa@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(39, 5, 'aisha.kiplangat@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(40, 5, 'derrick.onyango@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(41, 5, 'hope.muriuki@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(42, 5, 'kevin.simiyu@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(43, 5, 'sienna.gichuru@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(44, 5, 'trevor.ndungu@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(45, 5, 'faith.korir@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(46, 5, 'brian.otiso@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(47, 5, 'nicole.achieng@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(48, 5, 'alex.kilonzo@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(49, 5, 'michelle.rono@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(50, 5, 'tobias.kariuki@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(51, 5, 'grace.wanjala@example.com', NULL, '2025-07-12 01:12:28', NULL, NULL, 0, 0),
(52, 5, 'Grade 3', NULL, '2025-07-17 00:49:58', NULL, NULL, 0, 0),
(53, 6, 'Grade 1', NULL, '2025-07-17 01:00:20', 54, NULL, 0, 0),
(54, 6, 'Grade 2', NULL, '2025-07-17 01:00:20', 55, NULL, 0, 0),
(55, 6, 'Grade 3', NULL, '2025-07-17 01:00:20', 56, NULL, 0, 0),
(56, 6, 'Grade 4', NULL, '2025-07-17 01:00:20', 57, NULL, 0, 0),
(57, 6, 'Grade 5', NULL, '2025-07-17 01:00:20', NULL, NULL, 0, 0),
(58, 7, 'Grade 2', NULL, '2025-08-06 17:30:22', NULL, NULL, 0, 0),
(59, 7, 'Grade 1', NULL, '2025-08-06 17:30:22', NULL, NULL, 0, 0),
(60, 7, 'Grade 4', NULL, '2025-08-06 17:30:22', NULL, NULL, 0, 0),
(61, 7, 'Grade 5', NULL, '2025-08-06 17:30:22', NULL, NULL, 0, 0),
(62, 7, 'Grade 3', NULL, '2025-08-06 17:30:22', NULL, NULL, 0, 0),
(63, 7, 'Grade 6', NULL, '2025-08-06 17:30:22', NULL, NULL, 0, 0),
(65, 8, 'Grade 2', NULL, '2025-08-12 11:57:52', NULL, NULL, 0, 0),
(66, 8, 'Grade 1', NULL, '2025-08-12 11:57:52', NULL, NULL, 0, 0),
(67, 8, 'Grade 4', NULL, '2025-08-12 11:57:52', NULL, NULL, 0, 0),
(68, 8, 'Grade 5', NULL, '2025-08-12 11:57:52', NULL, NULL, 0, 0),
(69, 8, 'Grade 3', NULL, '2025-08-12 11:57:52', NULL, NULL, 0, 0),
(70, 8, 'Grade 6', NULL, '2025-08-12 11:57:52', NULL, NULL, 0, 0),
(71, 9, 'Grade 2', NULL, '2025-08-12 14:02:22', NULL, NULL, 0, 0),
(72, 9, 'Grade 1', NULL, '2025-08-12 14:02:22', NULL, NULL, 0, 0),
(73, 9, 'Grade 4', NULL, '2025-08-12 14:02:22', NULL, NULL, 0, 0),
(74, 9, 'Grade 5', NULL, '2025-08-12 14:02:22', NULL, NULL, 0, 0),
(75, 9, 'Grade 3', NULL, '2025-08-12 14:02:22', NULL, NULL, 0, 0),
(76, 9, 'Grade 6', NULL, '2025-08-12 14:02:22', NULL, NULL, 0, 0),
(77, 11, 'PLAYGROUP', NULL, '2025-08-28 18:12:05', 83, NULL, 0, 0),
(78, 11, 'PP1', NULL, '2025-08-28 18:12:12', 79, NULL, 0, 2),
(79, 11, 'PP2', NULL, '2025-08-28 18:12:18', 80, NULL, 0, 3),
(80, 11, 'Grade 1', NULL, '2025-08-28 18:12:26', 81, NULL, 0, 4),
(81, 11, 'Grade 2', NULL, '2025-08-28 18:12:33', 82, NULL, 0, 5),
(82, 11, 'Grade 3', NULL, '2025-08-28 18:12:41', NULL, NULL, 0, 6),
(83, 11, 'BEGINNER', NULL, '2025-08-28 18:12:48', 78, NULL, 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `admission_no` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deduction_templates`
--

CREATE TABLE `deduction_templates` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `deductions` text NOT NULL COMMENT 'JSON object with all deduction settings',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deposits`
--

CREATE TABLE `deposits` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `deposit_date` date NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `memo` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deposits`
--

INSERT INTO `deposits` (`id`, `school_id`, `deposit_date`, `account_id`, `amount`, `memo`, `created_at`) VALUES
(1, 5, '2025-07-15', 23, 60250.00, 'Bank Deposit of 2 payments.', '2025-07-15 20:36:59'),
(2, 6, '2025-07-17', 28, 157990.00, 'Bank Deposit of 3 payments.', '2025-07-16 22:17:35'),
(3, 6, '2025-07-20', 28, 15000.00, 'Bank Deposit of 1 payments.', '2025-07-20 00:09:36'),
(4, 6, '2025-07-20', 28, 5000.00, 'Bank Deposit of 1 payments.', '2025-07-20 00:22:57'),
(5, 6, '2025-07-20', 28, 32500.00, 'Bank Deposit of 1 payments.', '2025-07-20 00:25:13'),
(6, 6, '2025-07-20', 28, 74500.00, 'Bank Deposit of 1 payments.', '2025-07-20 00:52:54'),
(7, 6, '2025-08-26', 28, 80000.00, 'Bank Deposit of 1 payments.', '2025-08-26 18:57:57'),
(8, 11, '2025-08-30', 60, 25400.00, 'Bank Deposit of 1 payments.', '2025-08-30 18:13:41'),
(9, 11, '2025-09-04', 60, 81450.00, 'Bank Deposit of 4 payments.', '2025-09-04 10:28:06'),
(10, 11, '2025-09-07', 60, 25000.00, 'Bank Deposit of 1 payments.', '2025-09-07 12:03:52');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `employee_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `position` varchar(100) NOT NULL,
  `employment_type` enum('monthly','daily') NOT NULL DEFAULT 'monthly',
  `hire_date` date NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `daily_rate` decimal(10,2) DEFAULT 0.00,
  `kra_pin` varchar(20) DEFAULT NULL,
  `nhif_number` varchar(20) DEFAULT NULL,
  `nssf_number` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_branch` varchar(100) DEFAULT NULL,
  `bank_account_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `school_id`, `employee_id`, `first_name`, `last_name`, `email`, `phone`, `department`, `position`, `employment_type`, `hire_date`, `basic_salary`, `daily_rate`, `kra_pin`, `nhif_number`, `nssf_number`, `bank_name`, `bank_branch`, `bank_account_number`, `notes`, `status`, `created_at`, `updated_at`) VALUES
(1, 11, '002', 'William', 'Mungai', 'mungaimacharia308@gmail.com', '0769855953', 'Transport', 'Driver', 'monthly', '2025-09-01', 21000.00, 0.00, 'L464956K', '89765342', '9087234', 'Equity Bank', 'Ruaka', '23456789098', '', 'active', '2025-09-04 10:22:35', '2025-09-04 10:22:35'),
(3, 11, '003', 'Christina', 'Mungai', 'john@gmail.com', '0790346966', 'Cleaning Staff', 'Cleaning Staff', 'daily', '2025-09-01', 21000.00, 500.00, 'J9876543', '9876543', '68765678', 'Cooperative Bank', 'Ruaka', '987654345', '', 'active', '2025-09-04 10:37:57', '2025-09-04 10:37:57'),
(4, 11, '005', 'Chris', 'Rock', 'john@gmail.com', '0718760077', 'Teaching', 'Grade 3 Class Teacher', 'monthly', '2025-09-01', 25500.00, 0.00, 'L45786745', '456785086', '378868780', 'Cooperative Bank', 'Ruaka', '974558877678', '', 'active', '2025-09-04 11:57:35', '2025-09-04 11:57:35');

-- --------------------------------------------------------

--
-- Table structure for table `employee_payroll_meta`
--

CREATE TABLE `employee_payroll_meta` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `payroll_meta_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `transaction_type` enum('debit','credit') NOT NULL,
  `reference_number` varchar(50) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `entity_name` varchar(100) DEFAULT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `expense_type` varchar(50) DEFAULT NULL,
  `odometer_reading` decimal(10,1) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `receipt_image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `school_id`, `transaction_date`, `description`, `amount`, `type`, `account_id`, `transaction_type`, `reference_number`, `payment_method`, `entity_name`, `entity_type`, `expense_type`, `odometer_reading`, `invoice_number`, `created_at`, `receipt_image_url`) VALUES
(1, 1, '2025-04-28', 'Invest', 10000.00, 'journal', 1, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-28 20:20:06', NULL),
(2, 1, '2025-04-28', 'Invest', 10000.00, 'journal', 2, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-28 20:20:06', NULL),
(3, 1, '2025-04-28', 'Payment to Tembo talents: Skating, taekwondo and chess', 75450.00, 'service_payment', 2, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-28 21:50:35', NULL),
(4, 1, '2025-04-28', 'Payment to Tembo talents: Skating, taekwondo and chess', 75450.00, 'service_payment', 2, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-04-28 21:50:35', NULL),
(5, 1, '2025-05-05', 'Vehicle expense: Fuel for vehicle KBZ 467Z (Odometer: 123456)', 11500.00, 'vehicle_expense', 1, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-05 16:38:57', NULL),
(6, 1, '2025-05-05', 'Vehicle expense: Fuel for vehicle KCX 879K (Odometer: 123456)', 9670.00, 'vehicle_expense', 3, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-05 16:44:43', NULL),
(7, 1, '2025-05-06', 'Payment to supplier: Wajoy food services (Invoice #: 1003)', 5000.00, 'supplier_payment', 3, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-05-05 22:50:25', NULL),
(8, 1, '2025-06-02', 'Payment to KPLC: Payment for Electricity, KPLC.', 21250.00, 'service_payment', 10, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-06 18:05:54', NULL),
(9, 1, '2025-06-02', 'Payment to KPLC: Payment for Electricity, KPLC.', 21250.00, 'service_payment', 2, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-06 18:05:54', NULL),
(10, 1, '2025-06-02', 'Payment to Mr. Mwangi: Rent for section A.', 150000.00, 'service_payment', 11, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-06 18:07:07', NULL),
(11, 1, '2025-06-02', 'Payment to Mr. Mwangi: Rent for section A.', 150000.00, 'service_payment', 2, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-06-06 18:07:07', NULL),
(12, 3, '2025-07-01', 'Fund Transfer: ', 24500.00, 'transfer', 15, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-09 09:45:01', NULL),
(13, 3, '2025-07-01', 'Fund Transfer: ', 24500.00, 'transfer', 17, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-09 09:45:01', NULL),
(14, 5, '2025-07-16', 'Weekly casual wages payment for week ending 2025-07-16', 4750.00, 'payroll', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 20:33:25', NULL),
(15, 5, '2025-07-16', 'Weekly casual wages payment for week ending 2025-07-16', 4750.00, 'payroll', 23, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 20:33:25', NULL),
(16, 5, '2025-07-16', 'Joseph Wanjiru-(0720341957)', 4250.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(17, 5, '2025-07-16', 'Dennis Maina-(0798320389)', 4250.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(18, 5, '2025-07-16', 'Teresiah-(0717617246)', 2500.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(19, 5, '2025-07-16', 'Rahab-(0793505868)', 3000.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(20, 5, '2025-07-16', 'Sarah-(0797979124)', 2500.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(21, 5, '2025-07-16', 'Meryln-(0718698353)', 2500.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(22, 5, '2025-07-16', 'William', 2100.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(23, 5, '2025-07-16', 'Office', 300.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(24, 5, '2025-07-16', 'Vans', 300.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(25, 5, '2025-07-16', 'Uniforms-(0724477430)', 4370.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(26, 5, '2025-07-16', 'Car wash - 0790697466(Musyoka)', 600.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(27, 5, '2025-07-16', 'Swimming - Paybill(880100) Acc No.(600006)', 8100.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(28, 5, '2025-07-16', 'Mechanic Inspection (KBZ)', 2100.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(29, 5, '2025-07-16', 'Fuel Inspection (KBZ)', 7553.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(30, 5, '2025-07-16', 'Breakpads +Labour', 3000.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(31, 5, '2025-07-16', 'Milk', 1387.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(32, 5, '2025-07-16', 'Spinach', 600.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(33, 5, '2025-07-16', 'Tomatoes', 600.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(34, 5, '2025-07-16', 'Carrots', 400.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(35, 5, '2025-07-16', 'Fruits', 1250.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(36, 5, '2025-07-16', 'Bread', 780.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(37, 5, '2025-07-16', 'Dhania/Saumu', 180.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(38, 5, '2025-07-16', 'Water', 200.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(39, 5, '2025-07-16', 'Cabbage', 780.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(40, 5, '2025-07-16', 'Leek', 150.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(41, 5, '2025-07-16', 'Firewood', 1500.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(42, 5, '2025-07-16', 'Charcoal', 1800.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(43, 5, '2025-07-16', 'Salt', 880.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(44, 5, '2025-07-16', 'Meat', 7700.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(45, 5, '2025-07-16', 'Bulk payment for requisition batch #8 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'requisition', 23, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:01', NULL),
(46, 5, '2025-07-16', 'Joseph Wanjiru-(0720341957)', 4250.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(47, 5, '2025-07-16', 'Dennis Maina-(0798320389)', 4250.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(48, 5, '2025-07-16', 'Teresiah-(0717617246)', 2500.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(49, 5, '2025-07-16', 'Rahab-(0793505868)', 3000.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(50, 5, '2025-07-16', 'Sarah-(0797979124)', 2500.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(51, 5, '2025-07-16', 'Meryln-(0718698353)', 2500.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(52, 5, '2025-07-16', 'William', 2100.00, 'requisition', 26, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(53, 5, '2025-07-16', 'Office', 300.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(54, 5, '2025-07-16', 'Vans', 300.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(55, 5, '2025-07-16', 'Uniforms-(0724477430)', 4370.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(56, 5, '2025-07-16', 'Car wash - 0790697466(Musyoka)', 600.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(57, 5, '2025-07-16', 'Swimming - Paybill(880100) Acc No.(600006)', 8100.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(58, 5, '2025-07-16', 'Mechanic Inspection (KBZ)', 2100.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(59, 5, '2025-07-16', 'Fuel Inspection (KBZ)', 7553.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(60, 5, '2025-07-16', 'Breakpads +Labour', 3000.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(61, 5, '2025-07-16', 'Milk', 1387.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(62, 5, '2025-07-16', 'Spinach', 600.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(63, 5, '2025-07-16', 'Tomatoes', 600.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(64, 5, '2025-07-16', 'Carrots', 400.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(65, 5, '2025-07-16', 'Fruits', 1250.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(66, 5, '2025-07-16', 'Bread', 780.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(67, 5, '2025-07-16', 'Dhania/Saumu', 180.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(68, 5, '2025-07-16', 'Water', 200.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(69, 5, '2025-07-16', 'Cabbage', 780.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(70, 5, '2025-07-16', 'Leek', 150.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(71, 5, '2025-07-16', 'Firewood', 1500.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(72, 5, '2025-07-16', 'Charcoal', 1800.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(73, 5, '2025-07-16', 'Salt', 880.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(74, 5, '2025-07-16', 'Meat', 7700.00, 'requisition', 25, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(75, 5, '2025-07-16', 'Bulk payment for requisition batch #8 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'requisition', 23, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 21:36:09', NULL),
(76, 6, '2025-07-17', 'Fund Transfer: Petty Cash Replenishment', 70000.00, 'transfer', 28, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 22:18:16', NULL),
(77, 6, '2025-07-17', 'Fund Transfer: Petty Cash Replenishment', 70000.00, 'transfer', 29, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-16 22:18:16', NULL),
(78, 6, '2025-07-20', 'Bulk requisition expenses from file: Requisition_Week 5 Test.csv', 21100.00, 'requisition', 36, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-19 23:01:01', NULL),
(79, 6, '2025-07-20', 'Bulk requisition expenses from file: Requisition_Week 5 Test.csv', 13070.00, 'requisition', 37, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-19 23:01:01', NULL),
(80, 6, '2025-07-20', 'Bulk requisition expenses from file: Requisition_Week 5 Test.csv', 13253.00, 'requisition', 38, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-19 23:01:01', NULL),
(81, 6, '2025-07-20', 'Bulk requisition expenses from file: Requisition_Week 5 Test.csv', 18207.00, 'requisition', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-19 23:01:01', NULL),
(82, 6, '2025-07-20', 'Bulk payment for requisition batch #13 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'requisition', 29, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-19 23:01:01', NULL),
(83, 6, '2025-07-20', 'Bank Deposit of 1 payments.', 5000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:22:57', NULL),
(84, 6, '2025-07-20', 'Bank Deposit of 1 payments.', 5000.00, 'journal', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:22:57', NULL),
(85, 6, '2025-07-20', 'Bulk payment for requisition batch #14 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'journal', 36, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:30:42', NULL),
(86, 6, '2025-07-20', 'Bulk payment for requisition batch #14 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'journal', 28, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:30:42', NULL),
(87, 6, '2025-07-20', 'Payment from Daniel Kimani. Receipt #REC-687C3DAC83D9A', 74500.00, 'journal', 27, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:51:56', NULL),
(88, 6, '2025-07-20', 'Payment from Daniel Kimani. Receipt #REC-687C3DAC83D9A', 74500.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:51:56', NULL),
(89, 6, '2025-07-20', 'Bank Deposit of 1 payments.', 74500.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:52:54', NULL),
(90, 6, '2025-07-20', 'Bank Deposit of 1 payments.', 74500.00, 'journal', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 00:52:54', NULL),
(91, 6, '2025-07-20', 'Payment from Diana Atieno. Receipt #REC-687C404F551E3', 25000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 01:03:11', NULL),
(92, 6, '2025-07-20', 'Payment from Diana Atieno. Receipt #REC-687C404F551E3', 25000.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-20 01:03:11', NULL),
(93, 6, '2025-07-25', 'Payment from Brian Otieno. Receipt #REC-6883C14C88478', 21300.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:39:24', NULL),
(94, 6, '2025-07-25', 'Payment from Brian Otieno. Receipt #REC-6883C14C88478', 21300.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:39:24', NULL),
(95, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:32', NULL),
(96, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:32', NULL),
(97, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:33', NULL),
(98, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:33', NULL),
(99, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:34', NULL),
(100, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:35', NULL),
(101, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:35', NULL),
(102, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:50:35', NULL),
(103, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:54:25', NULL),
(104, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:54:25', NULL),
(105, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:54:27', NULL),
(106, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:54:27', NULL),
(107, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:58:26', NULL),
(108, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:58:26', NULL),
(109, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 30, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:58:29', NULL),
(110, 6, '2025-07-25', 'Payment to supplier: Quickmart (Invoice #: 724)', 10000.00, 'supplier_payment', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 17:58:29', NULL),
(111, 6, '2025-07-25', 'Vehicle expense: Fuel for vehicle KCK 393F (Odometer: 134569)', 9768.00, 'journal', 38, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 18:04:47', NULL),
(112, 6, '2025-07-25', 'Vehicle expense: Fuel for vehicle KCK 393F (Odometer: 134569)', 9768.00, 'journal', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 18:04:47', NULL),
(113, 6, '2025-07-25', 'Payment from George Lumumba. Receipt #REC-6883E61094F46', 37500.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 20:16:16', NULL),
(114, 6, '2025-07-25', 'Payment from George Lumumba. Receipt #REC-6883E61094F46', 37500.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-25 20:16:16', NULL),
(115, 6, '2025-07-27', 'Payment from Bella Anyango. Receipt #REC-688685F285B9E', 25000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-27 20:02:58', NULL),
(116, 6, '2025-07-27', 'Payment from Bella Anyango. Receipt #REC-688685F285B9E', 25000.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-27 20:02:58', NULL),
(117, 6, '2025-07-27', 'Fund Transfer: ', 5000.00, 'transfer', 28, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-27 20:09:32', NULL),
(118, 6, '2025-07-27', 'Fund Transfer: ', 5000.00, 'transfer', 29, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-27 20:09:32', NULL),
(119, 6, '2025-07-27', 'Bulk payment for requisition batch #15 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'journal', 36, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-27 20:19:10', NULL),
(120, 6, '2025-07-27', 'Bulk payment for requisition batch #15 (Ref: Requisition_Week 5 Test.csv)', 65630.00, 'journal', 28, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-07-27 20:19:10', NULL),
(121, 6, '2025-08-03', 'Fund Transfer: Requisition Replenishment.', 75000.00, 'transfer', 28, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-03 09:32:06', NULL),
(122, 6, '2025-08-03', 'Fund Transfer: Requisition Replenishment.', 75000.00, 'transfer', 29, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-03 09:32:06', NULL),
(123, 6, '2025-08-03', 'Fee payment from Daniel Kimani.', 25000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-03 13:45:34', NULL),
(124, 6, '2025-08-03', 'Fee payment from Daniel Kimani.', 25000.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-03 13:45:34', NULL),
(125, 6, '2025-08-03', 'Fee payment from Diana Atieno.', 100000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-03 13:54:57', NULL),
(126, 6, '2025-08-03', 'Fee payment from Diana Atieno.', 100000.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-03 13:54:57', NULL),
(127, 6, '2025-08-31', 'Monthly salary for Matt Murdock (2025-08)', 40000.00, 'journal', 36, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 07:37:53', NULL),
(128, 6, '2025-08-31', 'Net pay to Matt', 33410.67, 'journal', 28, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 07:37:53', NULL),
(129, 6, '2025-08-31', 'PAYE for Matt', 3909.33, 'journal', 40, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 07:37:53', NULL),
(130, 6, '2025-08-31', 'NHIF for Matt', 1000.00, 'journal', 41, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 07:37:53', NULL),
(131, 6, '2025-08-31', 'NSSF for Matt', 1080.00, 'journal', 42, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 07:37:53', NULL),
(132, 6, '2025-08-31', 'Housing Levy for Matt', 600.00, 'journal', 43, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 07:37:53', NULL),
(133, 6, '2025-08-05', 'Fee payment from Alice Wambui.', 25000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:13:25', NULL),
(134, 6, '2025-08-05', 'Fee payment from Alice Wambui.', 25000.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 11:13:25', NULL),
(135, 6, '2025-08-05', 'Fee payment from Zara Mwikali.', 73500.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 13:08:38', NULL),
(136, 6, '2025-08-05', 'Fee payment from Zara Mwikali.', 73500.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-05 13:08:38', NULL),
(137, 7, '2025-08-12', 'Fee payment from Agnes Kosgey.', 25000.00, 'journal', 44, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 08:32:58', NULL),
(138, 7, '2025-08-12', 'Fee payment from Agnes Kosgey.', 25000.00, 'journal', 47, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 08:32:58', NULL),
(139, 7, '2025-08-12', 'Fee payment from Agnes Ochieng.', 22500.00, 'journal', 44, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 08:51:41', NULL),
(140, 7, '2025-08-12', 'Fee payment from Agnes Ochieng.', 22500.00, 'journal', 48, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 08:51:41', NULL),
(141, 8, '2025-08-12', 'Fee payment from Agnes Ochieng.', 25000.00, 'journal', 50, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 09:04:21', NULL),
(142, 8, '2025-08-12', 'Fee payment from Agnes Ochieng.', 25000.00, 'journal', 51, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 09:04:21', NULL),
(143, 9, '2025-08-12', 'Fee payment from Agnes Ochieng.', 24500.00, 'journal', 52, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 11:26:38', NULL),
(144, 9, '2025-08-12', 'Fee payment from Agnes Ochieng.', 24500.00, 'journal', 53, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-12 11:26:38', NULL),
(145, 9, '2025-08-13', 'Invoice #INV-SCH9-034 created for student ID 332.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(146, 9, '2025-08-13', 'Invoice #INV-SCH9-034 created for student ID 332.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(147, 9, '2025-08-13', 'Invoice #INV-SCH9-035 created for student ID 333.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(148, 9, '2025-08-13', 'Invoice #INV-SCH9-035 created for student ID 333.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(149, 9, '2025-08-13', 'Invoice #INV-SCH9-036 created for student ID 337.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(150, 9, '2025-08-13', 'Invoice #INV-SCH9-036 created for student ID 337.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(151, 9, '2025-08-13', 'Invoice #INV-SCH9-037 created for student ID 362.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(152, 9, '2025-08-13', 'Invoice #INV-SCH9-037 created for student ID 362.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(153, 9, '2025-08-13', 'Invoice #INV-SCH9-038 created for student ID 375.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(154, 9, '2025-08-13', 'Invoice #INV-SCH9-038 created for student ID 375.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(155, 9, '2025-08-13', 'Invoice #INV-SCH9-039 created for student ID 377.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(156, 9, '2025-08-13', 'Invoice #INV-SCH9-039 created for student ID 377.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(157, 9, '2025-08-13', 'Invoice #INV-SCH9-040 created for student ID 378.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(158, 9, '2025-08-13', 'Invoice #INV-SCH9-040 created for student ID 378.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(159, 9, '2025-08-13', 'Invoice #INV-SCH9-041 created for student ID 384.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(160, 9, '2025-08-13', 'Invoice #INV-SCH9-041 created for student ID 384.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(161, 9, '2025-08-13', 'Invoice #INV-SCH9-042 created for student ID 387.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(162, 9, '2025-08-13', 'Invoice #INV-SCH9-042 created for student ID 387.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(163, 9, '2025-08-13', 'Invoice #INV-SCH9-043 created for student ID 393.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(164, 9, '2025-08-13', 'Invoice #INV-SCH9-043 created for student ID 393.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(165, 9, '2025-08-13', 'Invoice #INV-SCH9-044 created for student ID 396.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:48', NULL),
(166, 9, '2025-08-13', 'Invoice #INV-SCH9-044 created for student ID 396.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(167, 9, '2025-08-13', 'Invoice #INV-SCH9-045 created for student ID 401.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(168, 9, '2025-08-13', 'Invoice #INV-SCH9-045 created for student ID 401.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(169, 9, '2025-08-13', 'Invoice #INV-SCH9-046 created for student ID 413.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(170, 9, '2025-08-13', 'Invoice #INV-SCH9-046 created for student ID 413.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(171, 9, '2025-08-13', 'Invoice #INV-SCH9-047 created for student ID 415.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(172, 9, '2025-08-13', 'Invoice #INV-SCH9-047 created for student ID 415.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(173, 9, '2025-08-13', 'Invoice #INV-SCH9-048 created for student ID 417.', 83200.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(174, 9, '2025-08-13', 'Invoice #INV-SCH9-048 created for student ID 417.', 83200.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:18:49', NULL),
(175, 9, '2025-08-13', 'Fee payment from Alex Njoroge.', 35500.00, 'journal', 52, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:21:21', NULL),
(176, 9, '2025-08-13', 'Fee payment from Alex Njoroge.', 35500.00, 'journal', 53, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-13 15:21:21', NULL),
(177, 9, '2025-08-18', 'Fee payment from Agnes Kosgey.', 25500.00, 'journal', 52, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-18 19:20:58', NULL),
(178, 9, '2025-08-18', 'Fee payment from Agnes Kosgey.', 25500.00, 'journal', 53, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-18 19:20:58', NULL),
(179, 6, '2025-08-24', 'Invoice #INV-SCH6-002 created for student ID 424.', 56500.00, 'journal', 55, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-24 12:03:32', NULL),
(180, 6, '2025-08-24', 'Invoice #INV-SCH6-002 created for student ID 424.', 56500.00, 'journal', 39, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-24 12:03:32', NULL),
(181, 6, '2025-08-26', 'Fee payment from Eric Njuguna.', 80000.00, 'journal', 27, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-26 18:54:45', NULL),
(182, 6, '2025-08-26', 'Fee payment from Eric Njuguna.', 80000.00, 'journal', 55, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-26 18:54:45', NULL),
(183, 6, '2025-08-26', 'Bank Deposit of 1 payments.', 80000.00, 'journal', 28, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-26 18:57:57', NULL),
(184, 6, '2025-08-26', 'Bank Deposit of 1 payments.', 80000.00, 'journal', 27, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-26 18:57:57', NULL),
(185, 9, '2025-08-28', 'Invoice #INV-SCH9-049 created for student ID 376.', 59500.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-28 11:47:34', NULL),
(186, 9, '2025-08-28', 'Invoice #INV-SCH9-049 created for student ID 376.', 59500.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-28 11:47:34', NULL),
(187, 9, '2025-08-28', 'Invoice #INV-SCH9-050 created for student ID 377.', 55000.00, 'journal', 53, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-28 11:47:52', NULL),
(188, 9, '2025-08-28', 'Invoice #INV-SCH9-050 created for student ID 377.', 55000.00, 'journal', 54, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-28 11:47:52', NULL),
(189, 11, '2025-08-28', 'Invoice #INV-SCH11-001 created for student ID 432.', 58500.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-28 17:02:20', NULL),
(190, 11, '2025-08-28', 'Invoice #INV-SCH11-001 created for student ID 432.', 58500.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-28 17:02:20', NULL),
(191, 11, '2025-08-30', 'Fee payment from Ken Kimani.', 25400.00, 'journal', 58, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-30 07:07:51', NULL),
(192, 11, '2025-08-30', 'Fee payment from Ken Kimani.', 25400.00, 'journal', 56, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-30 07:07:51', NULL),
(193, 11, '2025-08-30', 'Bank Deposit of 1 payments.', 25400.00, 'journal', 60, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-30 18:13:41', NULL),
(194, 11, '2025-08-30', 'Bank Deposit of 1 payments.', 25400.00, 'journal', 58, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-30 18:13:41', NULL),
(195, 11, '2025-08-31', 'Invoice #INV-SCH11-002 created for student ID 430.', 56000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-31 21:31:22', NULL),
(196, 11, '2025-08-31', 'Invoice #INV-SCH11-002 created for student ID 430.', 56000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-08-31 21:31:22', NULL),
(197, 11, '2025-09-01', 'Invoice #INV-SCH11-003 created for student ID 433.', 59000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 06:32:08', NULL),
(198, 11, '2025-09-01', 'Invoice #INV-SCH11-003 created for student ID 433.', 59000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 06:32:08', NULL),
(199, 11, '2025-09-01', 'Fee payment from Maryann Wanjiku.', 22500.00, 'journal', 58, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 11:14:20', NULL),
(200, 11, '2025-09-01', 'Fee payment from Maryann Wanjiku.', 22500.00, 'journal', 56, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 11:14:20', NULL),
(201, 11, '2025-09-01', 'Invoice #INV-SCH11-004 created for student ID 429.', 45000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 17:45:57', NULL),
(202, 11, '2025-09-01', 'Invoice #INV-SCH11-004 created for student ID 429.', 45000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 17:45:57', NULL),
(203, 11, '2025-09-01', 'Fee payment from William Mungai.', 23450.00, 'journal', 58, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 17:46:23', NULL),
(204, 11, '2025-09-01', 'Fee payment from William Mungai.', 23450.00, 'journal', 56, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-01 17:46:23', NULL),
(205, 11, '2025-09-03', 'Fee payment from John Gathua Mungai.', 30500.00, 'journal', 58, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 15:16:58', NULL),
(206, 11, '2025-09-03', 'Fee payment from John Gathua Mungai.', 30500.00, 'journal', 56, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 15:16:58', NULL),
(207, 11, '2025-09-03', 'Fee payment from Ken Kimani.', 5000.00, 'journal', 58, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 15:24:09', NULL),
(208, 11, '2025-09-03', 'Fee payment from Ken Kimani.', 5000.00, 'journal', 56, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 15:24:09', NULL),
(209, 11, '2025-09-03', 'Vehicle expense: Fuel for vehicle KBZ 467Z', 7768.00, 'journal', 61, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 15:28:39', NULL),
(210, 11, '2025-09-03', 'Vehicle expense: Fuel for vehicle KBZ 467Z', 7768.00, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 15:28:39', NULL),
(211, 6, '2025-09-03', 'Fee payment from Emily Achieng.', 54535.00, 'journal', 27, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 21:22:39', NULL),
(212, 6, '2025-09-03', 'Fee payment from Emily Achieng.', 54535.00, 'journal', 55, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 21:22:39', NULL),
(213, 11, '2025-09-04', 'Invoice #INV-SCH11-005 created for student ID 432.', 49000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 22:56:47', NULL),
(214, 11, '2025-09-04', 'Invoice #INV-SCH11-005 created for student ID 432.', 49000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-03 22:56:47', NULL),
(215, 11, '2025-09-04', 'Bank Deposit of 4 payments.', 81450.00, 'journal', 60, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:28:06', NULL),
(216, 11, '2025-09-04', 'Bank Deposit of 4 payments.', 81450.00, 'journal', 58, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:28:06', NULL),
(217, 11, '2025-09-30', 'Monthly salary for William Mungai (2025-09)', 26000.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:31:22', NULL),
(218, 11, '2025-09-30', 'Net pay for William', 21577.50, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:31:22', NULL),
(219, 11, '2025-09-30', 'PAYE for William', 102.50, 'journal', 63, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:31:22', NULL),
(220, 11, '2025-09-30', 'NHIF for William', 850.00, 'journal', 64, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:31:22', NULL),
(221, 11, '2025-09-30', 'NSSF for William', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:31:22', NULL),
(222, 11, '2025-09-30', 'Housing Levy for William', 390.00, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:31:22', NULL),
(223, 11, '2025-09-04', 'Weekly casual wages payment for week ending 2025-09-04', 2500.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:38:30', NULL),
(224, 11, '2025-09-04', 'Weekly casual wages payment for week ending 2025-09-04', 2500.00, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 10:38:30', NULL),
(225, 11, '2025-09-30', 'Monthly salary for William Mungai (2025-09)', 21000.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:31:51', NULL),
(226, 11, '2025-09-30', 'Net pay for William', 18855.00, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:31:51', NULL),
(227, 11, '2025-09-30', 'NHIF for William', 750.00, 'journal', 64, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:31:51', NULL),
(228, 11, '2025-09-30', 'NSSF for William', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:31:51', NULL),
(229, 11, '2025-09-30', 'Housing Levy for William', 315.00, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:31:51', NULL),
(230, 11, '2025-09-30', 'Monthly salary for William Mungai (2025-09)', 21000.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(231, 11, '2025-09-30', 'Net pay to William', 19605.00, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(232, 11, '2025-09-30', 'NSSF for William', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(233, 11, '2025-09-30', 'Housing Levy for William', 315.00, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(234, 11, '2025-09-30', 'Monthly salary for Chris Rock (2025-09)', 25500.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(235, 11, '2025-09-30', 'Net pay to Chris', 23037.50, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(236, 11, '2025-09-30', 'NSSF for Chris', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(237, 11, '2025-09-30', 'Housing Levy for Chris', 382.50, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-04 11:57:56', NULL),
(238, 11, '2025-10-31', 'Monthly salary for William Mungai (2025-10)', 21000.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(239, 11, '2025-10-31', 'Net pay to William', 18855.00, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(240, 11, '2025-10-31', 'NHIF for William', 750.00, 'journal', 64, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(241, 11, '2025-10-31', 'NSSF for William', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(242, 11, '2025-10-31', 'Housing Levy for William', 315.00, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(243, 11, '2025-10-31', 'Monthly salary for Chris Rock (2025-10)', 25500.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(244, 11, '2025-10-31', 'Net pay to Chris', 21187.50, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(245, 11, '2025-10-31', 'NHIF for Chris', 850.00, 'journal', 64, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(246, 11, '2025-10-31', 'NSSF for Chris', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(247, 11, '2025-10-31', 'Housing Levy for Chris', 382.50, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-05 17:18:23', NULL),
(248, 11, '2025-09-07', 'Invoice #INV-SCH11-006 created for student ID 431.', 59000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-07 12:00:28', NULL),
(249, 11, '2025-09-07', 'Invoice #INV-SCH11-006 created for student ID 431.', 59000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-07 12:00:28', NULL),
(250, 11, '2025-09-07', 'Fee payment from Brian Gacheru.', 25000.00, 'journal', 58, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-07 12:02:15', NULL),
(251, 11, '2025-09-07', 'Fee payment from Brian Gacheru.', 25000.00, 'journal', 56, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-07 12:02:15', NULL),
(252, 11, '2025-09-07', 'Bank Deposit of 1 payments.', 25000.00, 'journal', 60, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-07 12:03:52', NULL),
(253, 11, '2025-09-07', 'Bank Deposit of 1 payments.', 25000.00, 'journal', 58, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-07 12:03:52', NULL),
(254, 11, '2025-09-30', 'Monthly salary for William Mungai (2025-09)', 21000.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(255, 11, '2025-09-30', 'Net pay to William', 18855.00, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(256, 11, '2025-09-30', 'NHIF for William', 750.00, 'journal', 64, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(257, 11, '2025-09-30', 'NSSF for William', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(258, 11, '2025-09-30', 'Housing Levy for William', 315.00, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(259, 11, '2025-09-30', 'Monthly salary for Chris Rock (2025-09)', 25500.00, 'journal', 62, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(260, 11, '2025-09-30', 'Net pay to Chris', 23187.50, 'journal', 60, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(261, 11, '2025-09-30', 'NHIF for Chris', 850.00, 'journal', 64, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(262, 11, '2025-09-30', 'NSSF for Chris', 1080.00, 'journal', 65, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(263, 11, '2025-09-30', 'Housing Levy for Chris', 382.50, 'journal', 66, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-09 14:19:14', NULL),
(264, 11, '2025-09-16', 'Invoice #INV-SCH11-007 created for student ID 430.', 10000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-16 21:42:43', NULL),
(265, 11, '2025-09-16', 'Invoice #INV-SCH11-007 created for student ID 430.', 10000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-16 21:42:43', NULL),
(266, 11, '2025-09-16', 'Invoice #INV-SCH11-008 created for student ID 429.', 46500.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-16 21:45:01', NULL),
(267, 11, '2025-09-16', 'Invoice #INV-SCH11-008 created for student ID 429.', 46500.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-16 21:45:01', NULL),
(268, 11, '2025-09-17', 'Invoice #INV-SCH11-009 created for student ID 433.', 47000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-16 23:42:40', NULL),
(269, 11, '2025-09-17', 'Invoice #INV-SCH11-009 created for student ID 433.', 47000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-16 23:42:40', NULL),
(270, 11, '2025-09-17', 'Invoice #INV-SCH11-010 created for student ID 432.', 126100.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(271, 11, '2025-09-17', 'Invoice #INV-SCH11-010 created for student ID 432.', 126100.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(272, 11, '2025-09-17', 'Invoice #INV-SCH11-011 created for student ID 430.', 94000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(273, 11, '2025-09-17', 'Invoice #INV-SCH11-011 created for student ID 430.', 94000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(274, 11, '2025-09-17', 'Invoice #INV-SCH11-012 created for student ID 431.', 93000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(275, 11, '2025-09-17', 'Invoice #INV-SCH11-012 created for student ID 431.', 93000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(276, 11, '2025-09-17', 'Invoice #INV-SCH11-013 created for student ID 429.', 115550.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(277, 11, '2025-09-17', 'Invoice #INV-SCH11-013 created for student ID 429.', 115550.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:06:48', NULL),
(278, 11, '2025-09-17', 'Invoice #INV-SCH11-014 created for student ID 432.', 49000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(279, 11, '2025-09-17', 'Invoice #INV-SCH11-014 created for student ID 432.', 49000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(280, 11, '2025-09-17', 'Invoice #INV-SCH11-015 created for student ID 430.', 58500.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(281, 11, '2025-09-17', 'Invoice #INV-SCH11-015 created for student ID 430.', 58500.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(282, 11, '2025-09-17', 'Invoice #INV-SCH11-016 created for student ID 431.', 59000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(283, 11, '2025-09-17', 'Invoice #INV-SCH11-016 created for student ID 431.', 59000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(284, 11, '2025-09-17', 'Invoice #INV-SCH11-017 created for student ID 429.', 49000.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(285, 11, '2025-09-17', 'Invoice #INV-SCH11-017 created for student ID 429.', 49000.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-17 00:18:09', NULL),
(286, 11, '2025-09-24', 'Invoice #INV-SCH11-018 created for student ID 431.', 48500.00, 'journal', 56, 'debit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-24 04:06:10', NULL),
(287, 11, '2025-09-24', 'Invoice #INV-SCH11-018 created for student ID 431.', 48500.00, 'journal', 57, 'credit', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-09-24 04:06:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure_items`
--

CREATE TABLE `fee_structure_items` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `academic_year` varchar(10) NOT NULL COMMENT 'e.g., 2025-2026',
  `term` varchar(50) NOT NULL COMMENT 'e.g., Term 1, Semester 1',
  `amount` decimal(10,2) NOT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Mandatory, 0 = Optional'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structure_items`
--

INSERT INTO `fee_structure_items` (`id`, `school_id`, `class_id`, `item_id`, `academic_year`, `term`, `amount`, `is_mandatory`) VALUES
(1, 9, 72, 406, '2025-2026', 'Term 1', 40000.00, 1),
(2, 9, 71, 406, '2025-2026', 'Term 1', 40000.00, 1),
(3, 9, 75, 406, '2025-2026', 'Term 1', 40000.00, 1),
(4, 9, 73, 362, '2025-2026', 'Term 1', 44500.00, 1),
(5, 9, 74, 362, '2025-2026', 'Term 1', 46500.00, 1),
(6, 9, 76, 362, '2025-2026', 'Term 1', 48000.00, 1),
(7, 9, 72, 386, '2025-2026', 'Term 1', 3500.00, 1),
(8, 9, 71, 386, '2025-2026', 'Term 1', 5000.00, 1),
(9, 9, 75, 386, '2025-2026', 'Term 1', 5000.00, 1),
(10, 9, 73, 386, '2025-2026', 'Term 1', 5000.00, 1),
(11, 9, 74, 386, '2025-2026', 'Term 1', 5000.00, 1),
(12, 9, 76, 386, '2025-2026', 'Term 1', 5000.00, 1),
(13, 9, 72, 407, '2025-2026', 'Term 1', 10000.00, 0),
(14, 9, 71, 407, '2025-2026', 'Term 1', 10000.00, 0),
(15, 9, 75, 407, '2025-2026', 'Term 1', 10000.00, 0),
(16, 9, 73, 407, '2025-2026', 'Term 1', 10000.00, 0),
(17, 9, 74, 407, '2025-2026', 'Term 1', 10000.00, 0),
(18, 9, 76, 407, '2025-2026', 'Term 1', 10000.00, 0),
(19, 11, 77, 414, '2025-2026', 'Term 1', 26000.00, 1),
(20, 11, 77, 415, '2025-2026', 'Term 1', 9000.00, 1),
(21, 11, 77, 416, '2025-2026', 'Term 1', 3000.00, 1),
(22, 11, 83, 414, '2025-2026', 'Term 1', 32500.00, 1),
(23, 11, 83, 415, '2025-2026', 'Term 1', 9000.00, 1),
(24, 11, 83, 416, '2025-2026', 'Term 1', 3000.00, 1),
(25, 11, 78, 414, '2025-2026', 'Term 1', 36000.00, 1),
(26, 11, 78, 415, '2025-2026', 'Term 1', 10000.00, 1),
(27, 11, 79, 414, '2025-2026', 'Term 1', 37000.00, 1),
(28, 11, 79, 415, '2025-2026', 'Term 1', 10000.00, 1),
(29, 11, 80, 414, '2025-2026', 'Term 1', 38500.00, 1),
(30, 11, 80, 415, '2025-2026', 'Term 1', 10000.00, 1),
(31, 11, 81, 414, '2025-2026', 'Term 1', 38500.00, 1),
(32, 11, 81, 415, '2025-2026', 'Term 1', 10000.00, 1),
(33, 11, 82, 414, '2025-2026', 'Term 1', 38500.00, 1),
(34, 11, 82, 415, '2025-2026', 'Term 1', 10000.00, 1),
(35, 11, 77, 433, '2025-2026', 'Term 1', 5000.00, 0),
(36, 11, 77, 434, '2025-2026', 'Term 1', 5000.00, 0),
(37, 11, 77, 435, '2025-2026', 'Term 1', 5000.00, 0),
(38, 11, 77, 436, '2025-2026', 'Term 1', 5000.00, 0),
(39, 11, 77, 437, '2025-2026', 'Term 1', 5000.00, 0),
(40, 11, 77, 438, '2025-2026', 'Term 1', 5000.00, 0),
(41, 11, 77, 439, '2025-2026', 'Term 1', 5000.00, 0),
(42, 11, 77, 440, '2025-2026', 'Term 1', 5000.00, 0),
(43, 11, 77, 441, '2025-2026', 'Term 1', 5000.00, 0),
(44, 11, 77, 442, '2025-2026', 'Term 1', 5000.00, 0),
(45, 11, 83, 433, '2025-2026', 'Term 1', 5000.00, 0),
(46, 11, 83, 434, '2025-2026', 'Term 1', 5000.00, 0),
(47, 11, 83, 435, '2025-2026', 'Term 1', 5000.00, 0),
(48, 11, 83, 436, '2025-2026', 'Term 1', 5000.00, 0),
(49, 11, 83, 437, '2025-2026', 'Term 1', 5000.00, 0),
(50, 11, 83, 438, '2025-2026', 'Term 1', 5000.00, 0),
(51, 11, 83, 439, '2025-2026', 'Term 1', 5000.00, 0),
(52, 11, 83, 440, '2025-2026', 'Term 1', 5000.00, 0),
(53, 11, 83, 441, '2025-2026', 'Term 1', 5000.00, 0),
(54, 11, 83, 442, '2025-2026', 'Term 1', 5000.00, 0),
(55, 11, 78, 433, '2025-2026', 'Term 1', 5000.00, 0),
(56, 11, 78, 434, '2025-2026', 'Term 1', 5000.00, 0),
(57, 11, 78, 435, '2025-2026', 'Term 1', 5000.00, 0),
(58, 11, 78, 436, '2025-2026', 'Term 1', 5000.00, 0),
(59, 11, 78, 437, '2025-2026', 'Term 1', 5000.00, 0),
(60, 11, 78, 438, '2025-2026', 'Term 1', 5000.00, 0),
(61, 11, 78, 439, '2025-2026', 'Term 1', 5000.00, 0),
(62, 11, 78, 440, '2025-2026', 'Term 1', 5000.00, 0),
(63, 11, 78, 441, '2025-2026', 'Term 1', 5000.00, 0),
(64, 11, 78, 442, '2025-2026', 'Term 1', 5000.00, 0),
(65, 11, 79, 433, '2025-2026', 'Term 1', 5000.00, 0),
(66, 11, 79, 434, '2025-2026', 'Term 1', 5000.00, 0),
(67, 11, 79, 435, '2025-2026', 'Term 1', 5000.00, 0),
(68, 11, 79, 436, '2025-2026', 'Term 1', 5000.00, 0),
(69, 11, 79, 437, '2025-2026', 'Term 1', 5000.00, 0),
(70, 11, 79, 438, '2025-2026', 'Term 1', 5000.00, 0),
(71, 11, 79, 439, '2025-2026', 'Term 1', 5000.00, 0),
(72, 11, 79, 440, '2025-2026', 'Term 1', 5000.00, 0),
(73, 11, 79, 441, '2025-2026', 'Term 1', 5000.00, 0),
(74, 11, 79, 442, '2025-2026', 'Term 1', 5000.00, 0),
(75, 11, 80, 433, '2025-2026', 'Term 1', 5000.00, 0),
(76, 11, 80, 434, '2025-2026', 'Term 1', 5000.00, 0),
(77, 11, 80, 435, '2025-2026', 'Term 1', 5000.00, 0),
(78, 11, 80, 436, '2025-2026', 'Term 1', 5000.00, 0),
(79, 11, 80, 437, '2025-2026', 'Term 1', 5000.00, 0),
(80, 11, 80, 438, '2025-2026', 'Term 1', 5000.00, 0),
(81, 11, 80, 439, '2025-2026', 'Term 1', 5000.00, 0),
(82, 11, 80, 440, '2025-2026', 'Term 1', 5000.00, 0),
(83, 11, 80, 441, '2025-2026', 'Term 1', 5000.00, 0),
(84, 11, 80, 442, '2025-2026', 'Term 1', 5000.00, 0),
(85, 11, 80, 443, '2025-2026', 'Term 1', 2000.00, 0),
(86, 11, 81, 433, '2025-2026', 'Term 1', 5000.00, 0),
(87, 11, 81, 434, '2025-2026', 'Term 1', 5000.00, 0),
(88, 11, 81, 435, '2025-2026', 'Term 1', 5000.00, 0),
(89, 11, 81, 436, '2025-2026', 'Term 1', 5000.00, 0),
(90, 11, 81, 437, '2025-2026', 'Term 1', 5000.00, 0),
(91, 11, 81, 438, '2025-2026', 'Term 1', 5000.00, 0),
(92, 11, 81, 439, '2025-2026', 'Term 1', 5000.00, 0),
(93, 11, 81, 440, '2025-2026', 'Term 1', 5000.00, 0),
(94, 11, 81, 441, '2025-2026', 'Term 1', 5000.00, 0),
(95, 11, 81, 442, '2025-2026', 'Term 1', 5000.00, 0),
(96, 11, 81, 443, '2025-2026', 'Term 1', 2000.00, 0),
(97, 11, 82, 433, '2025-2026', 'Term 1', 5000.00, 0),
(98, 11, 82, 434, '2025-2026', 'Term 1', 5000.00, 0),
(99, 11, 82, 435, '2025-2026', 'Term 1', 5000.00, 0),
(100, 11, 82, 436, '2025-2026', 'Term 1', 5000.00, 0),
(101, 11, 82, 437, '2025-2026', 'Term 1', 5000.00, 0),
(102, 11, 82, 438, '2025-2026', 'Term 1', 5000.00, 0),
(103, 11, 82, 439, '2025-2026', 'Term 1', 5000.00, 0),
(104, 11, 82, 440, '2025-2026', 'Term 1', 5000.00, 0),
(105, 11, 82, 441, '2025-2026', 'Term 1', 5000.00, 0),
(106, 11, 82, 442, '2025-2026', 'Term 1', 5000.00, 0),
(107, 11, 82, 443, '2025-2026', 'Term 1', 2000.00, 0),
(108, 11, 83, 408, '2025-2026', 'Term 1', 5000.00, 0),
(109, 11, 80, 408, '2025-2026', 'Term 1', 5000.00, 0),
(110, 11, 81, 408, '2025-2026', 'Term 1', 5000.00, 0),
(111, 11, 82, 408, '2025-2026', 'Term 1', 5000.00, 0),
(112, 11, 77, 408, '2025-2026', 'Term 1', 5000.00, 0),
(113, 11, 78, 408, '2025-2026', 'Term 1', 5000.00, 0),
(114, 11, 79, 408, '2025-2026', 'Term 1', 5000.00, 0),
(115, 11, 83, 410, '2025-2026', 'Term 1', 500.00, 0),
(116, 11, 80, 410, '2025-2026', 'Term 1', 500.00, 0),
(122, 11, 83, 411, '2025-2026', 'Term 1', 400.00, 0),
(123, 11, 80, 411, '2025-2026', 'Term 1', 400.00, 0),
(124, 11, 81, 411, '2025-2026', 'Term 1', 400.00, 0),
(125, 11, 82, 411, '2025-2026', 'Term 1', 400.00, 0),
(126, 11, 77, 411, '2025-2026', 'Term 1', 400.00, 0),
(127, 11, 78, 411, '2025-2026', 'Term 1', 400.00, 0),
(128, 11, 79, 411, '2025-2026', 'Term 1', 400.00, 0),
(129, 11, 83, 417, '2025-2026', 'Term 1', 10000.00, 0),
(130, 11, 80, 417, '2025-2026', 'Term 1', 10000.00, 0),
(131, 11, 81, 417, '2025-2026', 'Term 1', 10000.00, 0),
(132, 11, 82, 417, '2025-2026', 'Term 1', 10000.00, 0),
(133, 11, 77, 417, '2025-2026', 'Term 1', 10000.00, 0),
(134, 11, 78, 417, '2025-2026', 'Term 1', 10000.00, 0),
(135, 11, 79, 417, '2025-2026', 'Term 1', 10000.00, 0);

-- --------------------------------------------------------

--
-- Table structure for table `fiscal_settings`
--

CREATE TABLE `fiscal_settings` (
  `id` int(11) NOT NULL,
  `fiscal_year` varchar(9) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `inventory_categories`
--

INSERT INTO `inventory_categories` (`id`, `school_id`, `name`) VALUES
(1, 11, 'Uniform'),
(4, 11, 'Uniforms - Boys'),
(3, 11, 'Uniforms - Girls');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `sku` varchar(50) NOT NULL COMMENT 'Stock Keeping Unit',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `item_type` enum('Stock','Made-to-Order') NOT NULL DEFAULT 'Stock',
  `unit_price` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'The selling/issuance price',
  `average_cost` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Weighted average cost for COGS',
  `quantity_on_hand` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) NOT NULL DEFAULT 0 COMMENT 'Low stock warning threshold',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `movement_type` enum('purchase','issuance','adjustment_in','adjustment_out','return') NOT NULL,
  `quantity_changed` int(11) NOT NULL COMMENT 'Positive for additions, negative for reductions',
  `cost_at_time` decimal(10,2) DEFAULT NULL COMMENT 'Cost per unit at the time of movement',
  `price_at_time` decimal(10,2) DEFAULT NULL COMMENT 'Price per unit for issuances',
  `related_entity_type` enum('student','employee','supplier','internal') DEFAULT NULL,
  `related_entity_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `transaction_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('Draft','Sent','Paid','Overdue') DEFAULT 'Draft',
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `balance` decimal(10,2) GENERATED ALWAYS AS (`total_amount` - `paid_amount`) STORED,
  `token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `school_id`, `student_id`, `invoice_number`, `invoice_date`, `due_date`, `notes`, `status`, `paid_amount`, `total_amount`, `created_at`, `token`) VALUES
(1, 1, 1, '1', '2025-04-07', '2025-05-07', '', '', 10000.00, 47000.00, '2025-04-07 21:40:53', NULL),
(2, 1, 2, '2', '2025-04-07', '2025-05-07', '', '', 40000.00, 50000.00, '2025-04-07 21:46:40', NULL),
(3, 1, 3, '3', '2025-04-09', '2025-05-09', '', '', 24500.00, 50000.00, '2025-04-09 13:32:51', NULL),
(4, 1, 4, '4', '2025-04-13', '2025-05-13', '', '', 44500.00, 56000.00, '2025-04-13 20:31:04', NULL),
(5, 1, 5, '5', '2025-04-13', '2025-05-13', '', 'Paid', 57500.00, 57500.00, '2025-04-13 20:36:47', NULL),
(6, 1, 9, '6', '2025-04-30', '2025-05-30', '', 'Paid', 93300.00, 50500.00, '2025-04-30 11:56:39', NULL),
(7, 1, 9, '7', '2025-04-30', '2025-05-30', '', 'Draft', 0.00, 50500.00, '2025-05-04 18:47:45', NULL),
(8, 1, 13, '8', '2025-05-06', '2025-06-05', '', '', 45790.00, 57000.00, '2025-05-06 11:21:58', NULL),
(9, 1, 7, '9', '2025-05-06', '2025-06-05', '', 'Draft', 0.00, 73550.00, '2025-05-06 11:38:00', NULL),
(10, 1, 14, '10', '2025-05-06', '2025-06-05', '', '', 75680.00, 94550.00, '2025-05-06 12:06:19', NULL),
(11, 1, 14, '11', '2025-05-06', '2025-06-05', '', '', 57645.00, 96600.00, '2025-05-06 12:15:54', NULL),
(12, 1, 15, '12', '2025-05-06', '2025-06-05', '', '', 45450.00, 147550.00, '2025-05-06 16:08:21', NULL),
(13, 1, 16, '13', '2025-05-06', '2025-06-05', '', '', 45250.00, 173050.00, '2025-05-06 17:46:52', NULL),
(14, 1, 18, '14', '2025-05-06', '2025-06-05', '', 'Draft', 0.00, 138750.00, '2025-05-06 18:40:30', NULL),
(15, 1, 19, '15', '2025-05-19', '2025-06-18', '', '', 82250.00, 95050.00, '2025-05-19 22:05:51', NULL),
(16, 1, 20, '16', '2025-05-19', '2025-06-18', '', '', 47500.00, 94050.00, '2025-05-20 00:33:36', NULL),
(17, 1, 21, '17', '2025-05-21', '2025-06-20', '', '', 52950.00, 98100.00, '2025-05-21 16:10:44', NULL),
(18, 1, 22, '18', '2025-05-22', '2025-06-21', '', '', 34500.00, 117550.00, '2025-05-22 11:17:51', NULL),
(19, 1, 23, '19', '2025-05-27', '2025-06-26', '', '', 37750.00, 86600.00, '2025-05-27 14:47:19', NULL),
(20, 1, 24, '20', '2025-05-27', '2025-06-26', '', '', 60450.00, 83050.00, '2025-05-27 15:00:33', NULL),
(21, 1, 4, '21', '2025-06-01', '2025-07-01', '', 'Draft', 0.00, 40000.00, '2025-06-01 10:40:37', NULL),
(22, 1, 26, '22', '2025-06-02', '2025-07-02', '', '', 47000.00, 73550.00, '2025-06-02 14:17:33', NULL),
(23, 1, 27, '23', '2025-06-10', '2025-07-10', '', '', 72450.00, 78000.00, '2025-06-11 00:45:34', NULL),
(24, 1, 29, '24', '2025-06-15', '2025-07-14', '', '', 32650.00, 72500.00, '2025-06-15 02:13:16', NULL),
(25, 1, 24, '25', '2025-06-29', '2025-07-29', '', 'Draft', 0.00, 62500.00, '2025-06-29 23:05:26', NULL),
(26, 1, 30, '26', '2025-06-30', '2025-07-30', 'Kindly pay before today', 'Draft', 0.00, 62500.00, '2025-06-30 13:51:20', NULL),
(27, 1, 31, '27', '2025-06-30', '2025-07-30', '', '', 25750.00, 72000.00, '2025-06-30 15:20:01', NULL),
(28, 1, 30, '28', '2025-06-30', '2025-07-30', '', 'Draft', 0.00, 72000.00, '2025-06-30 15:22:29', NULL),
(29, 1, 31, '29', '2025-06-30', '2025-07-30', '', 'Draft', 0.00, 72000.00, '2025-06-30 15:22:29', NULL),
(30, 2, 34, '1', '2025-07-01', '2025-07-31', '', '', 50500.00, 70500.00, '2025-07-02 00:06:00', NULL),
(31, 3, 37, '1', '2025-07-09', '2025-08-08', '', '', 60000.00, 65000.00, '2025-07-09 11:24:11', NULL),
(32, 4, 39, '1', '2025-07-09', '2025-08-08', '', '', 60000.00, 65000.00, '2025-07-09 16:39:52', NULL),
(33, 4, 39, '2', '2025-07-09', '2025-08-08', '', 'Draft', 0.00, 53500.00, '2025-07-09 18:14:51', NULL),
(34, 4, 40, '3', '2025-07-11', '2025-08-10', '', 'Draft', 0.00, 53500.00, '2025-07-11 17:57:30', NULL),
(35, 4, 40, '4', '2025-07-11', '2025-08-10', '', 'Draft', 0.00, 53500.00, '2025-07-11 18:07:06', NULL),
(36, 5, 42, '1', '2025-07-15', '2025-08-13', '', 'Draft', 0.00, 68500.00, '2025-07-15 02:17:31', NULL),
(37, 5, 59, '2', '2025-07-15', '2025-08-14', '', '', 25750.00, 68500.00, '2025-07-15 16:42:49', NULL),
(38, 5, 66, '3', '2025-07-15', '2025-08-14', '', '', 34500.00, 75400.00, '2025-07-15 16:50:51', NULL),
(39, 6, 73, '2', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:55', NULL),
(40, 6, 76, '3', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:55', NULL),
(41, 6, 82, '4', '2025-07-17', '2025-08-15', '', '', 32500.00, 68500.00, '2025-07-17 01:09:56', NULL),
(42, 6, 87, '5', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(43, 6, 92, '6', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(44, 6, 96, '7', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(45, 6, 101, '8', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(46, 6, 106, '9', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(47, 6, 111, '10', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(48, 6, 116, '11', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(49, 6, 121, '12', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 68500.00, '2025-07-17 01:09:56', NULL),
(50, 6, 74, '13', '2025-07-17', '2025-08-15', '', 'Paid', 75490.00, 75400.00, '2025-07-17 01:10:10', NULL),
(51, 6, 78, '14', '2025-07-17', '2025-08-15', '', '', 54535.00, 75400.00, '2025-07-17 01:10:10', NULL),
(52, 6, 83, '15', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(53, 6, 86, '16', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(54, 6, 91, '17', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(55, 6, 97, '18', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(56, 6, 102, '19', '2025-07-17', '2025-08-15', '', 'Paid', 125000.00, 75400.00, '2025-07-17 01:10:10', NULL),
(57, 6, 107, '20', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(58, 6, 112, '21', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(59, 6, 117, '22', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(60, 6, 122, '23', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 75400.00, '2025-07-17 01:10:10', NULL),
(61, 6, 75, '24', '2025-07-17', '2025-08-15', '', '', 21300.00, 83200.00, '2025-07-17 01:10:28', NULL),
(62, 6, 79, '25', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(63, 6, 84, '26', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(64, 6, 89, '27', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(65, 6, 93, '28', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(66, 6, 98, '29', '2025-07-17', '2025-08-15', '', '', 73500.00, 83200.00, '2025-07-17 01:10:28', NULL),
(67, 6, 103, '30', '2025-07-17', '2025-08-15', '', '', 80000.00, 83200.00, '2025-07-17 01:10:28', NULL),
(68, 6, 108, '31', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(69, 6, 113, '32', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(70, 6, 118, '33', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 83200.00, '2025-07-17 01:10:28', NULL),
(71, 6, 77, '34', '2025-07-17', '2025-08-15', '', 'Paid', 99500.00, 91500.00, '2025-07-17 01:10:40', NULL),
(72, 6, 81, '35', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(73, 6, 88, '36', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(74, 6, 94, '37', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(75, 6, 99, '38', '2025-07-17', '2025-08-15', '', '', 80000.00, 91500.00, '2025-07-17 01:10:40', NULL),
(76, 6, 104, '39', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(77, 6, 109, '40', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(78, 6, 114, '41', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(79, 6, 119, '42', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 91500.00, '2025-07-17 01:10:40', NULL),
(80, 6, 80, '43', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(81, 6, 85, '44', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(82, 6, 90, '45', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(83, 6, 95, '46', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(84, 6, 100, '47', '2025-07-17', '2025-08-15', '', '', 72500.00, 100800.00, '2025-07-17 01:10:57', NULL),
(85, 6, 105, '48', '2025-07-17', '2025-08-15', '', '', 37500.00, 100800.00, '2025-07-17 01:10:57', NULL),
(86, 6, 110, '49', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(87, 6, 115, '50', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(88, 6, 120, '51', '2025-07-17', '2025-08-15', '', 'Draft', 0.00, 100800.00, '2025-07-17 01:10:57', NULL),
(89, 6, 73, '52', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(90, 6, 76, '53', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(91, 6, 82, '54', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(92, 6, 87, '55', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(93, 6, 92, '56', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(94, 6, 96, '57', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(95, 6, 101, '58', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(96, 6, 106, '59', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(97, 6, 111, '60', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(98, 6, 116, '61', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(99, 6, 121, '62', '2025-07-27', '2025-08-26', '', 'Draft', 0.00, 68500.00, '2025-07-27 23:05:55', NULL),
(100, 6, 77, '63', '2025-08-03', '2025-09-02', '', 'Draft', 0.00, 56500.00, '2025-08-03 16:47:29', NULL),
(101, 6, 74, '1', '2025-04-01', '2024-04-30', '', 'Draft', 0.00, 56500.00, '2025-08-05 14:05:46', NULL),
(181, 7, 126, '1', '2025-08-11', '2025-09-10', NULL, '', 22500.00, 68500.00, '2025-08-11 22:43:33', NULL),
(182, 7, 127, '2', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(183, 7, 142, '3', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(184, 7, 152, '4', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(185, 7, 154, '5', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(186, 7, 155, '6', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(187, 7, 157, '7', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(188, 7, 160, '8', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(189, 7, 167, '9', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(190, 7, 173, '10', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(191, 7, 180, '11', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(192, 7, 202, '12', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(193, 7, 207, '13', '2025-08-11', '2025-09-10', NULL, 'Draft', 0.00, 68500.00, '2025-08-11 22:43:33', NULL),
(194, 7, 123, '14', '2025-08-12', '2025-09-11', '', '', 25000.00, 75400.00, '2025-08-12 09:55:04', NULL),
(195, 7, 124, '15', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(196, 7, 125, '16', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(197, 7, 140, '17', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(198, 7, 146, '18', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(199, 7, 147, '19', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(200, 7, 158, '20', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(201, 7, 166, '21', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(202, 7, 169, '22', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(203, 7, 188, '23', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(204, 7, 194, '24', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(205, 7, 196, '25', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(206, 7, 197, '26', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(207, 7, 203, '27', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(208, 7, 204, '28', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(209, 7, 206, '29', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(210, 7, 208, '30', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(211, 7, 215, '31', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(212, 7, 219, '32', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(213, 7, 221, '33', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 09:55:04', NULL),
(214, 8, 227, 'INV-SCH8-001', '2025-08-12', '2025-09-11', '', '', 25000.00, 68500.00, '2025-08-12 12:00:09', NULL),
(215, 8, 228, 'INV-SCH8-002', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(216, 8, 243, 'INV-SCH8-003', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(217, 8, 253, 'INV-SCH8-004', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(218, 8, 255, 'INV-SCH8-005', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(219, 8, 256, 'INV-SCH8-006', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(220, 8, 258, 'INV-SCH8-007', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(221, 8, 261, 'INV-SCH8-008', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(222, 8, 268, 'INV-SCH8-009', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(223, 8, 274, 'INV-SCH8-010', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(224, 8, 281, 'INV-SCH8-011', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(225, 8, 303, 'INV-SCH8-012', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(226, 8, 308, 'INV-SCH8-013', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 12:00:09', NULL),
(227, 9, 327, 'INV-SCH9-001', '2025-08-12', '2025-09-11', '', '', 24500.00, 68500.00, '2025-08-12 14:20:38', NULL),
(228, 9, 328, 'INV-SCH9-002', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(229, 9, 343, 'INV-SCH9-003', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(230, 9, 353, 'INV-SCH9-004', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(231, 9, 355, 'INV-SCH9-005', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(232, 9, 356, 'INV-SCH9-006', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(233, 9, 358, 'INV-SCH9-007', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(234, 9, 361, 'INV-SCH9-008', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(235, 9, 368, 'INV-SCH9-009', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(236, 9, 374, 'INV-SCH9-010', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(237, 9, 381, 'INV-SCH9-011', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(238, 9, 403, 'INV-SCH9-012', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(239, 9, 408, 'INV-SCH9-013', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 68500.00, '2025-08-12 14:20:38', NULL),
(240, 9, 324, 'INV-SCH9-014', '2025-08-12', '2025-09-11', '', '', 25500.00, 75400.00, '2025-08-12 14:41:22', NULL),
(241, 9, 325, 'INV-SCH9-015', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(242, 9, 326, 'INV-SCH9-016', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(243, 9, 341, 'INV-SCH9-017', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(244, 9, 347, 'INV-SCH9-018', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(245, 9, 348, 'INV-SCH9-019', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(246, 9, 359, 'INV-SCH9-020', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(247, 9, 367, 'INV-SCH9-021', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(248, 9, 370, 'INV-SCH9-022', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(249, 9, 389, 'INV-SCH9-023', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(250, 9, 395, 'INV-SCH9-024', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(251, 9, 397, 'INV-SCH9-025', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(252, 9, 398, 'INV-SCH9-026', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(253, 9, 404, 'INV-SCH9-027', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(254, 9, 405, 'INV-SCH9-028', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(255, 9, 407, 'INV-SCH9-029', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(256, 9, 409, 'INV-SCH9-030', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(257, 9, 416, 'INV-SCH9-031', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(258, 9, 420, 'INV-SCH9-032', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(259, 9, 422, 'INV-SCH9-033', '2025-08-12', '2025-09-11', '', 'Draft', 0.00, 75400.00, '2025-08-12 14:41:22', NULL),
(260, 9, 332, 'INV-SCH9-034', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(261, 9, 333, 'INV-SCH9-035', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(262, 9, 337, 'INV-SCH9-036', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(263, 9, 362, 'INV-SCH9-037', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(264, 9, 375, 'INV-SCH9-038', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(265, 9, 377, 'INV-SCH9-039', '2025-08-13', '2025-09-12', '', '', 35500.00, 83200.00, '2025-08-13 18:18:48', NULL),
(266, 9, 378, 'INV-SCH9-040', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(267, 9, 384, 'INV-SCH9-041', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(268, 9, 387, 'INV-SCH9-042', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(269, 9, 393, 'INV-SCH9-043', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(270, 9, 396, 'INV-SCH9-044', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:48', NULL),
(271, 9, 401, 'INV-SCH9-045', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:49', NULL),
(272, 9, 413, 'INV-SCH9-046', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:49', NULL),
(273, 9, 415, 'INV-SCH9-047', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:49', NULL),
(274, 9, 417, 'INV-SCH9-048', '2025-08-13', '2025-09-12', '', 'Draft', 0.00, 83200.00, '2025-08-13 18:18:49', NULL),
(275, 6, 424, 'INV-SCH6-002', '2025-08-24', '2025-09-23', '', 'Draft', 0.00, 56500.00, '2025-08-24 15:03:32', NULL),
(276, 9, 376, 'INV-SCH9-049', '2025-08-28', '2025-09-27', '', 'Draft', 0.00, 59500.00, '2025-08-28 14:47:34', NULL),
(277, 9, 377, 'INV-SCH9-050', '2025-08-28', '2025-09-27', '', 'Draft', 0.00, 55000.00, '2025-08-28 14:47:52', NULL),
(278, 11, 432, 'INV-SCH11-001', '2025-08-28', '2025-09-27', '', '', 30400.00, 58500.00, '2025-08-28 20:02:20', 'cd33969c9417d4ea8b6988b8f57ea76d48b51850f9ffa4353d2923f0d197d305'),
(279, 11, 430, 'INV-SCH11-002', '2025-08-31', '2025-09-30', '', '', 30500.00, 56000.00, '2025-09-01 00:31:22', 'd4ace8f4a20289b3a685ff12736ff23f929c826fbb324ed1f14397ad4f1679ee'),
(280, 11, 433, 'INV-SCH11-003', '2025-09-01', '2025-10-01', '', '', 22500.00, 59000.00, '2025-09-01 09:32:08', '0622bacca35c3e30a350f89b6ebc48d93762f7ce7e53eb3d3dd42c7c5faf8d8b'),
(281, 11, 429, 'INV-SCH11-004', '2025-09-01', '2025-10-01', '', '', 23450.00, 45000.00, '2025-09-01 20:45:57', 'bf241d934d1d12603ad8ce26957efb61d020eb0ae18321951fc4a71b98737e5e'),
(282, 11, 432, 'INV-SCH11-005', '2025-09-04', '2025-10-03', '', 'Draft', 0.00, 49000.00, '2025-09-04 01:56:47', '91f287a36c5cb193785255fcb19061c7733849458283b772bfd44aed5d2d27df'),
(283, 11, 431, 'INV-SCH11-006', '2025-09-07', '2025-10-07', '', '', 25000.00, 59000.00, '2025-09-07 15:00:28', 'd43068f4b487e42166cdf79bc43b849fa6eed175c90e6dbc73dc654a7743e65f'),
(284, 11, 430, 'INV-SCH11-007', '2025-09-16', '2025-10-16', 'New term invoice.', 'Draft', 0.00, 10000.00, '2025-09-17 00:42:43', 'a19a98e22d0578859376bc60ca3201574d38b56caf53396d1328d1bd28fd285e'),
(285, 11, 429, 'INV-SCH11-008', '2025-09-16', '2025-10-16', 'New term invoice.', 'Draft', 0.00, 46500.00, '2025-09-17 00:45:01', '5d01145fea8a0824541353609bce899f2304fcd30e150c75f0fbed8ecbca6da5'),
(286, 11, 433, 'INV-SCH11-009', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2026-2027 Term 1 term.', 'Draft', 0.00, 47000.00, '2025-09-17 02:42:40', '2ffe817d994cefa8066434bd1c5d089a1206d418b491f3def0540011fca7e157'),
(287, 11, 432, 'INV-SCH11-010', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 126100.00, '2025-09-17 03:06:48', '354be585bf93d7896d7345b2a179d6dd0e0d11269b2ffbe98e1d4ea4d96f6cff'),
(288, 11, 430, 'INV-SCH11-011', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 94000.00, '2025-09-17 03:06:48', '62318c0868768967981b195f5be24e17cc3eb461d76222588b5d08ae5d0ea7d9'),
(289, 11, 431, 'INV-SCH11-012', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 93000.00, '2025-09-17 03:06:48', '9aecd5ccc93e7e9a5a285b186e9f2210af3136d3dd5caadb0a015cec56ed47a8'),
(290, 11, 429, 'INV-SCH11-013', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 115550.00, '2025-09-17 03:06:48', '5c3092dac8dc37566e3c488492306e2ae7d4ebab928a5147ff350f604c707db5'),
(291, 11, 432, 'INV-SCH11-014', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 49000.00, '2025-09-17 03:18:09', '3dec7719fb025ccc4280db017c845e0825a0c05f485cbaa774a5385b99ef1061'),
(292, 11, 430, 'INV-SCH11-015', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 58500.00, '2025-09-17 03:18:09', 'a1a83a0b92bcd4302ba9b09c59e1f5e670efa0b2fef416cd25def5280cdb76b5'),
(293, 11, 431, 'INV-SCH11-016', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 59000.00, '2025-09-17 03:18:09', '3fa0d283f9d45663b65711aea741060231e9d20c4fdca39d2eb3561f66aaf45b'),
(294, 11, 429, 'INV-SCH11-017', '2025-09-17', '2025-10-17', 'New invoice generated after promotion for the 2025-2026 Term 1 term.', 'Draft', 0.00, 49000.00, '2025-09-17 03:18:09', '465d263fb26c63890e14bc53efc744ad965625c330e6bdfde6f833fa00bdba24'),
(295, 11, 431, 'INV-SCH11-018', '2025-09-24', '2025-10-24', '', 'Draft', 0.00, 48500.00, '2025-09-24 07:06:10', '7fbc2319d2bf47752e878304c8ef06e0871688b5e145148470aa18305866de88');

--
-- Triggers `invoices`
--
DELIMITER $$
CREATE TRIGGER `update_invoice_status` BEFORE UPDATE ON `invoices` FOR EACH ROW BEGIN
  IF NEW.paid_amount >= NEW.total_amount THEN
    SET NEW.status = 'Paid';
  ELSEIF NEW.paid_amount > 0 THEN
    SET NEW.status = 'Partially Paid';
  ELSE
    SET NEW.status = 'Draft';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `school_id`, `invoice_id`, `item_id`, `quantity`, `unit_price`) VALUES
(1, 1, 1, 3, 1, 38000.00),
(2, 1, 1, 5, 1, 9000.00),
(3, 1, 2, 6, 1, 10000.00),
(4, 1, 2, 2, 1, 40000.00),
(5, 1, 3, 2, 1, 40000.00),
(6, 1, 3, 6, 1, 10000.00),
(7, 1, 4, 3, 1, 38000.00),
(8, 1, 4, 5, 1, 9000.00),
(9, 1, 4, 5, 1, 9000.00),
(10, 1, 5, 7, 1, 12000.00),
(11, 1, 5, 11, 1, 5500.00),
(12, 1, 5, 2, 1, 40000.00),
(13, 1, 6, 5, 1, 9000.00),
(14, 1, 6, 9, 1, 3500.00),
(15, 1, 6, 3, 1, 38000.00),
(16, 1, 7, 5, 1, 9000.00),
(17, 1, 7, 9, 1, 3500.00),
(18, 1, 7, 3, 1, 38000.00),
(19, 1, 8, 2, 1, 40000.00),
(20, 1, 8, 16, 1, 2500.00),
(21, 1, 8, 6, 1, 10000.00),
(22, 1, 8, 10, 1, 4500.00),
(23, 1, 9, 18, 1, 52500.00),
(24, 1, 9, 7, 1, 12000.00),
(25, 1, 9, 11, 1, 5500.00),
(26, 1, 9, 12, 1, 3550.00),
(27, 1, 10, 18, 1, 52500.00),
(28, 1, 10, 7, 1, 12000.00),
(29, 1, 10, 24, 1, 18500.00),
(30, 1, 10, 15, 1, 2500.00),
(31, 1, 10, 11, 1, 5500.00),
(32, 1, 10, 12, 1, 3550.00),
(33, 1, 11, 18, 1, 52500.00),
(34, 1, 11, 6, 1, 10000.00),
(35, 1, 11, 15, 1, 2500.00),
(36, 1, 11, 25, 1, 22550.00),
(37, 1, 11, 11, 1, 5500.00),
(38, 1, 11, 12, 1, 3550.00),
(39, 1, 12, 18, 1, 52500.00),
(40, 1, 12, 11, 1, 5500.00),
(41, 1, 12, 15, 1, 2500.00),
(42, 1, 12, 7, 1, 12000.00),
(43, 1, 12, 25, 1, 22550.00),
(44, 1, 12, 18, 1, 52500.00),
(45, 1, 13, 18, 1, 52500.00),
(46, 1, 13, 2, 1, 40000.00),
(47, 1, 13, 3, 1, 38000.00),
(48, 1, 13, 25, 1, 22550.00),
(49, 1, 13, 15, 1, 2500.00),
(50, 1, 13, 7, 1, 12000.00),
(51, 1, 13, 11, 1, 5500.00),
(52, 1, 14, 15, 1, 2500.00),
(53, 1, 14, 7, 1, 12000.00),
(54, 1, 14, 22, 1, 13750.00),
(55, 1, 14, 11, 1, 5500.00),
(56, 1, 14, 18, 1, 52500.00),
(57, 1, 14, 18, 1, 52500.00),
(58, 1, 15, 18, 1, 52500.00),
(59, 1, 15, 15, 1, 2500.00),
(60, 1, 15, 7, 1, 12000.00),
(61, 1, 15, 11, 1, 5500.00),
(62, 1, 15, 25, 1, 22550.00),
(63, 1, 16, 18, 1, 52500.00),
(64, 1, 16, 15, 1, 2500.00),
(65, 1, 16, 7, 1, 12000.00),
(66, 1, 16, 25, 1, 22550.00),
(67, 1, 16, 10, 1, 4500.00),
(68, 1, 17, 18, 1, 52500.00),
(69, 1, 17, 15, 1, 2500.00),
(70, 1, 17, 7, 1, 12000.00),
(71, 1, 17, 25, 1, 22550.00),
(72, 1, 17, 12, 1, 3550.00),
(73, 1, 17, 26, 1, 5000.00),
(74, 1, 18, 2, 1, 40000.00),
(75, 1, 18, 16, 1, 2500.00),
(76, 1, 18, 6, 1, 10000.00),
(77, 1, 18, 10, 1, 4500.00),
(78, 1, 18, 25, 1, 22550.00),
(79, 1, 18, 3, 1, 38000.00),
(80, 1, 19, 18, 1, 52500.00),
(81, 1, 19, 15, 1, 2500.00),
(82, 1, 19, 25, 1, 22550.00),
(83, 1, 19, 12, 1, 3550.00),
(84, 1, 19, 11, 1, 5500.00),
(85, 1, 20, 18, 1, 52500.00),
(86, 1, 20, 15, 1, 2500.00),
(87, 1, 20, 11, 1, 5500.00),
(88, 1, 20, 25, 1, 22550.00),
(89, 1, 21, 3, 1, 38000.00),
(90, 1, 21, 17, 1, 2000.00),
(91, 1, 22, 2, 1, 40000.00),
(92, 1, 22, 21, 1, 12500.00),
(93, 1, 22, 6, 1, 10000.00),
(94, 1, 22, 16, 1, 2500.00),
(95, 1, 22, 26, 1, 5000.00),
(96, 1, 22, 12, 1, 3550.00),
(97, 1, 23, 18, 1, 52500.00),
(98, 1, 23, 15, 1, 2500.00),
(99, 1, 23, 7, 1, 12000.00),
(100, 1, 23, 11, 1, 5500.00),
(101, 1, 23, 11, 1, 5500.00),
(102, 1, 24, 15, 1, 2500.00),
(103, 1, 24, 7, 1, 12000.00),
(104, 1, 24, 18, 1, 52500.00),
(105, 1, 24, 11, 1, 5500.00),
(106, 1, 25, 3, 1, 38000.00),
(107, 1, 25, 17, 1, 2000.00),
(108, 1, 25, 5, 1, 9000.00),
(109, 1, 25, 9, 1, 3500.00),
(110, 1, 25, 20, 1, 10000.00),
(111, 1, 26, 3, 1, 38000.00),
(112, 1, 26, 17, 1, 2000.00),
(113, 1, 26, 5, 1, 9000.00),
(114, 1, 26, 9, 1, 3500.00),
(115, 1, 26, 20, 1, 10000.00),
(116, 1, 27, 2, 1, 40000.00),
(117, 1, 27, 16, 1, 2500.00),
(118, 1, 27, 10, 1, 4500.00),
(119, 1, 27, 23, 1, 15000.00),
(120, 1, 27, 6, 1, 10000.00),
(121, 1, 28, 2, 1, 40000.00),
(122, 1, 28, 16, 1, 2500.00),
(123, 1, 28, 10, 1, 4500.00),
(124, 1, 28, 23, 1, 15000.00),
(125, 1, 28, 6, 1, 10000.00),
(126, 1, 29, 2, 1, 40000.00),
(127, 1, 29, 16, 1, 2500.00),
(128, 1, 29, 10, 1, 4500.00),
(129, 1, 29, 23, 1, 15000.00),
(130, 1, 29, 6, 1, 10000.00),
(131, 2, 30, 44, 1, 50500.00),
(132, 2, 30, 46, 1, 10000.00),
(133, 2, 30, 53, 1, 5000.00),
(134, 2, 30, 52, 1, 5000.00),
(135, 3, 31, 56, 1, 50000.00),
(136, 3, 31, 57, 1, 15000.00),
(143, 4, 34, 121, 1, 38500.00),
(144, 4, 34, 127, 1, 10000.00),
(145, 4, 34, 133, 1, 5000.00),
(146, 4, 35, 123, 1, 38500.00),
(147, 4, 35, 129, 1, 10000.00),
(148, 4, 35, 135, 1, 5000.00),
(149, 5, 36, 191, 1, 40000.00),
(150, 5, 36, 219, 1, 3000.00),
(151, 5, 36, 205, 1, 4500.00),
(152, 5, 36, 226, 1, 2500.00),
(153, 5, 36, 233, 1, 1000.00),
(154, 5, 36, 198, 1, 12000.00),
(155, 5, 36, 212, 1, 5500.00),
(156, 5, 37, 191, 1, 40000.00),
(157, 5, 37, 219, 1, 3000.00),
(158, 5, 37, 205, 1, 4500.00),
(159, 5, 37, 226, 1, 2500.00),
(160, 5, 37, 233, 1, 1000.00),
(161, 5, 37, 198, 1, 12000.00),
(162, 5, 37, 212, 1, 5500.00),
(163, 5, 38, 192, 1, 43500.00),
(164, 5, 38, 220, 1, 2900.00),
(165, 5, 38, 206, 1, 5075.00),
(166, 5, 38, 227, 1, 2175.00),
(167, 5, 38, 234, 1, 725.00),
(168, 5, 38, 213, 1, 6525.00),
(169, 5, 38, 199, 1, 14500.00),
(170, 6, 39, 237, 1, 40000.00),
(171, 6, 39, 249, 1, 4500.00),
(172, 6, 39, 261, 1, 3000.00),
(173, 6, 39, 243, 1, 12000.00),
(174, 6, 39, 255, 1, 5500.00),
(175, 6, 39, 273, 1, 1000.00),
(176, 6, 39, 267, 1, 2500.00),
(177, 6, 40, 237, 1, 40000.00),
(178, 6, 40, 249, 1, 4500.00),
(179, 6, 40, 261, 1, 3000.00),
(180, 6, 40, 243, 1, 12000.00),
(181, 6, 40, 255, 1, 5500.00),
(182, 6, 40, 273, 1, 1000.00),
(183, 6, 40, 267, 1, 2500.00),
(184, 6, 41, 237, 1, 40000.00),
(185, 6, 41, 249, 1, 4500.00),
(186, 6, 41, 261, 1, 3000.00),
(187, 6, 41, 243, 1, 12000.00),
(188, 6, 41, 255, 1, 5500.00),
(189, 6, 41, 273, 1, 1000.00),
(190, 6, 41, 267, 1, 2500.00),
(191, 6, 42, 237, 1, 40000.00),
(192, 6, 42, 249, 1, 4500.00),
(193, 6, 42, 261, 1, 3000.00),
(194, 6, 42, 243, 1, 12000.00),
(195, 6, 42, 255, 1, 5500.00),
(196, 6, 42, 273, 1, 1000.00),
(197, 6, 42, 267, 1, 2500.00),
(198, 6, 43, 237, 1, 40000.00),
(199, 6, 43, 249, 1, 4500.00),
(200, 6, 43, 261, 1, 3000.00),
(201, 6, 43, 243, 1, 12000.00),
(202, 6, 43, 255, 1, 5500.00),
(203, 6, 43, 273, 1, 1000.00),
(204, 6, 43, 267, 1, 2500.00),
(205, 6, 44, 237, 1, 40000.00),
(206, 6, 44, 249, 1, 4500.00),
(207, 6, 44, 261, 1, 3000.00),
(208, 6, 44, 243, 1, 12000.00),
(209, 6, 44, 255, 1, 5500.00),
(210, 6, 44, 273, 1, 1000.00),
(211, 6, 44, 267, 1, 2500.00),
(212, 6, 45, 237, 1, 40000.00),
(213, 6, 45, 249, 1, 4500.00),
(214, 6, 45, 261, 1, 3000.00),
(215, 6, 45, 243, 1, 12000.00),
(216, 6, 45, 255, 1, 5500.00),
(217, 6, 45, 273, 1, 1000.00),
(218, 6, 45, 267, 1, 2500.00),
(219, 6, 46, 237, 1, 40000.00),
(220, 6, 46, 249, 1, 4500.00),
(221, 6, 46, 261, 1, 3000.00),
(222, 6, 46, 243, 1, 12000.00),
(223, 6, 46, 255, 1, 5500.00),
(224, 6, 46, 273, 1, 1000.00),
(225, 6, 46, 267, 1, 2500.00),
(226, 6, 47, 237, 1, 40000.00),
(227, 6, 47, 249, 1, 4500.00),
(228, 6, 47, 261, 1, 3000.00),
(229, 6, 47, 243, 1, 12000.00),
(230, 6, 47, 255, 1, 5500.00),
(231, 6, 47, 273, 1, 1000.00),
(232, 6, 47, 267, 1, 2500.00),
(233, 6, 48, 237, 1, 40000.00),
(234, 6, 48, 249, 1, 4500.00),
(235, 6, 48, 261, 1, 3000.00),
(236, 6, 48, 243, 1, 12000.00),
(237, 6, 48, 255, 1, 5500.00),
(238, 6, 48, 273, 1, 1000.00),
(239, 6, 48, 267, 1, 2500.00),
(240, 6, 49, 237, 1, 40000.00),
(241, 6, 49, 249, 1, 4500.00),
(242, 6, 49, 261, 1, 3000.00),
(243, 6, 49, 243, 1, 12000.00),
(244, 6, 49, 255, 1, 5500.00),
(245, 6, 49, 273, 1, 1000.00),
(246, 6, 49, 267, 1, 2500.00),
(247, 6, 50, 238, 1, 43500.00),
(248, 6, 50, 250, 1, 5075.00),
(249, 6, 50, 262, 1, 2900.00),
(250, 6, 50, 268, 1, 2175.00),
(251, 6, 50, 274, 1, 725.00),
(252, 6, 50, 244, 1, 14500.00),
(253, 6, 50, 256, 1, 6525.00),
(254, 6, 51, 238, 1, 43500.00),
(255, 6, 51, 250, 1, 5075.00),
(256, 6, 51, 262, 1, 2900.00),
(257, 6, 51, 268, 1, 2175.00),
(258, 6, 51, 274, 1, 725.00),
(259, 6, 51, 244, 1, 14500.00),
(260, 6, 51, 256, 1, 6525.00),
(261, 6, 52, 238, 1, 43500.00),
(262, 6, 52, 250, 1, 5075.00),
(263, 6, 52, 262, 1, 2900.00),
(264, 6, 52, 268, 1, 2175.00),
(265, 6, 52, 274, 1, 725.00),
(266, 6, 52, 244, 1, 14500.00),
(267, 6, 52, 256, 1, 6525.00),
(268, 6, 53, 238, 1, 43500.00),
(269, 6, 53, 250, 1, 5075.00),
(270, 6, 53, 262, 1, 2900.00),
(271, 6, 53, 268, 1, 2175.00),
(272, 6, 53, 274, 1, 725.00),
(273, 6, 53, 244, 1, 14500.00),
(274, 6, 53, 256, 1, 6525.00),
(275, 6, 54, 238, 1, 43500.00),
(276, 6, 54, 250, 1, 5075.00),
(277, 6, 54, 262, 1, 2900.00),
(278, 6, 54, 268, 1, 2175.00),
(279, 6, 54, 274, 1, 725.00),
(280, 6, 54, 244, 1, 14500.00),
(281, 6, 54, 256, 1, 6525.00),
(282, 6, 55, 238, 1, 43500.00),
(283, 6, 55, 250, 1, 5075.00),
(284, 6, 55, 262, 1, 2900.00),
(285, 6, 55, 268, 1, 2175.00),
(286, 6, 55, 274, 1, 725.00),
(287, 6, 55, 244, 1, 14500.00),
(288, 6, 55, 256, 1, 6525.00),
(289, 6, 56, 238, 1, 43500.00),
(290, 6, 56, 250, 1, 5075.00),
(291, 6, 56, 262, 1, 2900.00),
(292, 6, 56, 268, 1, 2175.00),
(293, 6, 56, 274, 1, 725.00),
(294, 6, 56, 244, 1, 14500.00),
(295, 6, 56, 256, 1, 6525.00),
(296, 6, 57, 238, 1, 43500.00),
(297, 6, 57, 250, 1, 5075.00),
(298, 6, 57, 262, 1, 2900.00),
(299, 6, 57, 268, 1, 2175.00),
(300, 6, 57, 274, 1, 725.00),
(301, 6, 57, 244, 1, 14500.00),
(302, 6, 57, 256, 1, 6525.00),
(303, 6, 58, 238, 1, 43500.00),
(304, 6, 58, 250, 1, 5075.00),
(305, 6, 58, 262, 1, 2900.00),
(306, 6, 58, 268, 1, 2175.00),
(307, 6, 58, 274, 1, 725.00),
(308, 6, 58, 244, 1, 14500.00),
(309, 6, 58, 256, 1, 6525.00),
(310, 6, 59, 238, 1, 43500.00),
(311, 6, 59, 250, 1, 5075.00),
(312, 6, 59, 262, 1, 2900.00),
(313, 6, 59, 268, 1, 2175.00),
(314, 6, 59, 274, 1, 725.00),
(315, 6, 59, 244, 1, 14500.00),
(316, 6, 59, 256, 1, 6525.00),
(317, 6, 60, 238, 1, 43500.00),
(318, 6, 60, 250, 1, 5075.00),
(319, 6, 60, 262, 1, 2900.00),
(320, 6, 60, 268, 1, 2175.00),
(321, 6, 60, 274, 1, 725.00),
(322, 6, 60, 244, 1, 14500.00),
(323, 6, 60, 256, 1, 6525.00),
(324, 6, 61, 239, 1, 48000.00),
(325, 6, 61, 263, 1, 3200.00),
(326, 6, 61, 251, 1, 5600.00),
(327, 6, 61, 269, 1, 2400.00),
(328, 6, 61, 245, 1, 16000.00),
(329, 6, 61, 257, 1, 7200.00),
(330, 6, 61, 275, 1, 800.00),
(331, 6, 62, 239, 1, 48000.00),
(332, 6, 62, 263, 1, 3200.00),
(333, 6, 62, 251, 1, 5600.00),
(334, 6, 62, 269, 1, 2400.00),
(335, 6, 62, 245, 1, 16000.00),
(336, 6, 62, 257, 1, 7200.00),
(337, 6, 62, 275, 1, 800.00),
(338, 6, 63, 239, 1, 48000.00),
(339, 6, 63, 263, 1, 3200.00),
(340, 6, 63, 251, 1, 5600.00),
(341, 6, 63, 269, 1, 2400.00),
(342, 6, 63, 245, 1, 16000.00),
(343, 6, 63, 257, 1, 7200.00),
(344, 6, 63, 275, 1, 800.00),
(345, 6, 64, 239, 1, 48000.00),
(346, 6, 64, 263, 1, 3200.00),
(347, 6, 64, 251, 1, 5600.00),
(348, 6, 64, 269, 1, 2400.00),
(349, 6, 64, 245, 1, 16000.00),
(350, 6, 64, 257, 1, 7200.00),
(351, 6, 64, 275, 1, 800.00),
(352, 6, 65, 239, 1, 48000.00),
(353, 6, 65, 263, 1, 3200.00),
(354, 6, 65, 251, 1, 5600.00),
(355, 6, 65, 269, 1, 2400.00),
(356, 6, 65, 245, 1, 16000.00),
(357, 6, 65, 257, 1, 7200.00),
(358, 6, 65, 275, 1, 800.00),
(359, 6, 66, 239, 1, 48000.00),
(360, 6, 66, 263, 1, 3200.00),
(361, 6, 66, 251, 1, 5600.00),
(362, 6, 66, 269, 1, 2400.00),
(363, 6, 66, 245, 1, 16000.00),
(364, 6, 66, 257, 1, 7200.00),
(365, 6, 66, 275, 1, 800.00),
(366, 6, 67, 239, 1, 48000.00),
(367, 6, 67, 263, 1, 3200.00),
(368, 6, 67, 251, 1, 5600.00),
(369, 6, 67, 269, 1, 2400.00),
(370, 6, 67, 245, 1, 16000.00),
(371, 6, 67, 257, 1, 7200.00),
(372, 6, 67, 275, 1, 800.00),
(373, 6, 68, 239, 1, 48000.00),
(374, 6, 68, 263, 1, 3200.00),
(375, 6, 68, 251, 1, 5600.00),
(376, 6, 68, 269, 1, 2400.00),
(377, 6, 68, 245, 1, 16000.00),
(378, 6, 68, 257, 1, 7200.00),
(379, 6, 68, 275, 1, 800.00),
(380, 6, 69, 239, 1, 48000.00),
(381, 6, 69, 263, 1, 3200.00),
(382, 6, 69, 251, 1, 5600.00),
(383, 6, 69, 269, 1, 2400.00),
(384, 6, 69, 245, 1, 16000.00),
(385, 6, 69, 257, 1, 7200.00),
(386, 6, 69, 275, 1, 800.00),
(387, 6, 70, 239, 1, 48000.00),
(388, 6, 70, 263, 1, 3200.00),
(389, 6, 70, 251, 1, 5600.00),
(390, 6, 70, 269, 1, 2400.00),
(391, 6, 70, 245, 1, 16000.00),
(392, 6, 70, 257, 1, 7200.00),
(393, 6, 70, 275, 1, 800.00),
(394, 6, 71, 240, 1, 52800.00),
(395, 6, 71, 246, 1, 17600.00),
(396, 6, 71, 264, 1, 3500.00),
(397, 6, 71, 252, 1, 6200.00),
(398, 6, 71, 270, 1, 2600.00),
(399, 6, 71, 276, 1, 900.00),
(400, 6, 71, 258, 1, 7900.00),
(401, 6, 72, 240, 1, 52800.00),
(402, 6, 72, 246, 1, 17600.00),
(403, 6, 72, 264, 1, 3500.00),
(404, 6, 72, 252, 1, 6200.00),
(405, 6, 72, 270, 1, 2600.00),
(406, 6, 72, 276, 1, 900.00),
(407, 6, 72, 258, 1, 7900.00),
(408, 6, 73, 240, 1, 52800.00),
(409, 6, 73, 246, 1, 17600.00),
(410, 6, 73, 264, 1, 3500.00),
(411, 6, 73, 252, 1, 6200.00),
(412, 6, 73, 270, 1, 2600.00),
(413, 6, 73, 276, 1, 900.00),
(414, 6, 73, 258, 1, 7900.00),
(415, 6, 74, 240, 1, 52800.00),
(416, 6, 74, 246, 1, 17600.00),
(417, 6, 74, 264, 1, 3500.00),
(418, 6, 74, 252, 1, 6200.00),
(419, 6, 74, 270, 1, 2600.00),
(420, 6, 74, 276, 1, 900.00),
(421, 6, 74, 258, 1, 7900.00),
(422, 6, 75, 240, 1, 52800.00),
(423, 6, 75, 246, 1, 17600.00),
(424, 6, 75, 264, 1, 3500.00),
(425, 6, 75, 252, 1, 6200.00),
(426, 6, 75, 270, 1, 2600.00),
(427, 6, 75, 276, 1, 900.00),
(428, 6, 75, 258, 1, 7900.00),
(429, 6, 76, 240, 1, 52800.00),
(430, 6, 76, 246, 1, 17600.00),
(431, 6, 76, 264, 1, 3500.00),
(432, 6, 76, 252, 1, 6200.00),
(433, 6, 76, 270, 1, 2600.00),
(434, 6, 76, 276, 1, 900.00),
(435, 6, 76, 258, 1, 7900.00),
(436, 6, 77, 240, 1, 52800.00),
(437, 6, 77, 246, 1, 17600.00),
(438, 6, 77, 264, 1, 3500.00),
(439, 6, 77, 252, 1, 6200.00),
(440, 6, 77, 270, 1, 2600.00),
(441, 6, 77, 276, 1, 900.00),
(442, 6, 77, 258, 1, 7900.00),
(443, 6, 78, 240, 1, 52800.00),
(444, 6, 78, 246, 1, 17600.00),
(445, 6, 78, 264, 1, 3500.00),
(446, 6, 78, 252, 1, 6200.00),
(447, 6, 78, 270, 1, 2600.00),
(448, 6, 78, 276, 1, 900.00),
(449, 6, 78, 258, 1, 7900.00),
(450, 6, 79, 240, 1, 52800.00),
(451, 6, 79, 246, 1, 17600.00),
(452, 6, 79, 264, 1, 3500.00),
(453, 6, 79, 252, 1, 6200.00),
(454, 6, 79, 270, 1, 2600.00),
(455, 6, 79, 276, 1, 900.00),
(456, 6, 79, 258, 1, 7900.00),
(457, 6, 80, 241, 1, 58100.00),
(458, 6, 80, 247, 1, 19400.00),
(459, 6, 80, 265, 1, 3900.00),
(460, 6, 80, 253, 1, 6800.00),
(461, 6, 80, 271, 1, 2900.00),
(462, 6, 80, 277, 1, 1000.00),
(463, 6, 80, 259, 1, 8700.00),
(464, 6, 81, 241, 1, 58100.00),
(465, 6, 81, 247, 1, 19400.00),
(466, 6, 81, 265, 1, 3900.00),
(467, 6, 81, 253, 1, 6800.00),
(468, 6, 81, 271, 1, 2900.00),
(469, 6, 81, 277, 1, 1000.00),
(470, 6, 81, 259, 1, 8700.00),
(471, 6, 82, 241, 1, 58100.00),
(472, 6, 82, 247, 1, 19400.00),
(473, 6, 82, 265, 1, 3900.00),
(474, 6, 82, 253, 1, 6800.00),
(475, 6, 82, 271, 1, 2900.00),
(476, 6, 82, 277, 1, 1000.00),
(477, 6, 82, 259, 1, 8700.00),
(478, 6, 83, 241, 1, 58100.00),
(479, 6, 83, 247, 1, 19400.00),
(480, 6, 83, 265, 1, 3900.00),
(481, 6, 83, 253, 1, 6800.00),
(482, 6, 83, 271, 1, 2900.00),
(483, 6, 83, 277, 1, 1000.00),
(484, 6, 83, 259, 1, 8700.00),
(485, 6, 84, 241, 1, 58100.00),
(486, 6, 84, 247, 1, 19400.00),
(487, 6, 84, 265, 1, 3900.00),
(488, 6, 84, 253, 1, 6800.00),
(489, 6, 84, 271, 1, 2900.00),
(490, 6, 84, 277, 1, 1000.00),
(491, 6, 84, 259, 1, 8700.00),
(492, 6, 85, 241, 1, 58100.00),
(493, 6, 85, 247, 1, 19400.00),
(494, 6, 85, 265, 1, 3900.00),
(495, 6, 85, 253, 1, 6800.00),
(496, 6, 85, 271, 1, 2900.00),
(497, 6, 85, 277, 1, 1000.00),
(498, 6, 85, 259, 1, 8700.00),
(499, 6, 86, 241, 1, 58100.00),
(500, 6, 86, 247, 1, 19400.00),
(501, 6, 86, 265, 1, 3900.00),
(502, 6, 86, 253, 1, 6800.00),
(503, 6, 86, 271, 1, 2900.00),
(504, 6, 86, 277, 1, 1000.00),
(505, 6, 86, 259, 1, 8700.00),
(506, 6, 87, 241, 1, 58100.00),
(507, 6, 87, 247, 1, 19400.00),
(508, 6, 87, 265, 1, 3900.00),
(509, 6, 87, 253, 1, 6800.00),
(510, 6, 87, 271, 1, 2900.00),
(511, 6, 87, 277, 1, 1000.00),
(512, 6, 87, 259, 1, 8700.00),
(513, 6, 88, 241, 1, 58100.00),
(514, 6, 88, 247, 1, 19400.00),
(515, 6, 88, 265, 1, 3900.00),
(516, 6, 88, 253, 1, 6800.00),
(517, 6, 88, 271, 1, 2900.00),
(518, 6, 88, 277, 1, 1000.00),
(519, 6, 88, 259, 1, 8700.00),
(520, 6, 89, 237, 1, 40000.00),
(521, 6, 89, 249, 1, 4500.00),
(522, 6, 89, 261, 1, 3000.00),
(523, 6, 89, 243, 1, 12000.00),
(524, 6, 89, 255, 1, 5500.00),
(525, 6, 89, 273, 1, 1000.00),
(526, 6, 89, 267, 1, 2500.00),
(527, 6, 90, 237, 1, 40000.00),
(528, 6, 90, 249, 1, 4500.00),
(529, 6, 90, 261, 1, 3000.00),
(530, 6, 90, 243, 1, 12000.00),
(531, 6, 90, 255, 1, 5500.00),
(532, 6, 90, 273, 1, 1000.00),
(533, 6, 90, 267, 1, 2500.00),
(534, 6, 91, 237, 1, 40000.00),
(535, 6, 91, 249, 1, 4500.00),
(536, 6, 91, 261, 1, 3000.00),
(537, 6, 91, 243, 1, 12000.00),
(538, 6, 91, 255, 1, 5500.00),
(539, 6, 91, 273, 1, 1000.00),
(540, 6, 91, 267, 1, 2500.00),
(541, 6, 92, 237, 1, 40000.00),
(542, 6, 92, 249, 1, 4500.00),
(543, 6, 92, 261, 1, 3000.00),
(544, 6, 92, 243, 1, 12000.00),
(545, 6, 92, 255, 1, 5500.00),
(546, 6, 92, 273, 1, 1000.00),
(547, 6, 92, 267, 1, 2500.00),
(548, 6, 93, 237, 1, 40000.00),
(549, 6, 93, 249, 1, 4500.00),
(550, 6, 93, 261, 1, 3000.00),
(551, 6, 93, 243, 1, 12000.00),
(552, 6, 93, 255, 1, 5500.00),
(553, 6, 93, 273, 1, 1000.00),
(554, 6, 93, 267, 1, 2500.00),
(555, 6, 94, 237, 1, 40000.00),
(556, 6, 94, 249, 1, 4500.00),
(557, 6, 94, 261, 1, 3000.00),
(558, 6, 94, 243, 1, 12000.00),
(559, 6, 94, 255, 1, 5500.00),
(560, 6, 94, 273, 1, 1000.00),
(561, 6, 94, 267, 1, 2500.00),
(562, 6, 95, 237, 1, 40000.00),
(563, 6, 95, 249, 1, 4500.00),
(564, 6, 95, 261, 1, 3000.00),
(565, 6, 95, 243, 1, 12000.00),
(566, 6, 95, 255, 1, 5500.00),
(567, 6, 95, 273, 1, 1000.00),
(568, 6, 95, 267, 1, 2500.00),
(569, 6, 96, 237, 1, 40000.00),
(570, 6, 96, 249, 1, 4500.00),
(571, 6, 96, 261, 1, 3000.00),
(572, 6, 96, 243, 1, 12000.00),
(573, 6, 96, 255, 1, 5500.00),
(574, 6, 96, 273, 1, 1000.00),
(575, 6, 96, 267, 1, 2500.00),
(576, 6, 97, 237, 1, 40000.00),
(577, 6, 97, 249, 1, 4500.00),
(578, 6, 97, 261, 1, 3000.00),
(579, 6, 97, 243, 1, 12000.00),
(580, 6, 97, 255, 1, 5500.00),
(581, 6, 97, 273, 1, 1000.00),
(582, 6, 97, 267, 1, 2500.00),
(583, 6, 98, 237, 1, 40000.00),
(584, 6, 98, 249, 1, 4500.00),
(585, 6, 98, 261, 1, 3000.00),
(586, 6, 98, 243, 1, 12000.00),
(587, 6, 98, 255, 1, 5500.00),
(588, 6, 98, 273, 1, 1000.00),
(589, 6, 98, 267, 1, 2500.00),
(590, 6, 99, 237, 1, 40000.00),
(591, 6, 99, 249, 1, 4500.00),
(592, 6, 99, 261, 1, 3000.00),
(593, 6, 99, 243, 1, 12000.00),
(594, 6, 99, 255, 1, 5500.00),
(595, 6, 99, 273, 1, 1000.00),
(596, 6, 99, 267, 1, 2500.00),
(597, 6, 100, 261, 1, 3000.00),
(598, 6, 100, 249, 1, 4500.00),
(599, 6, 100, 267, 1, 2500.00),
(600, 6, 100, 273, 1, 1000.00),
(601, 6, 100, 237, 1, 40000.00),
(602, 6, 100, 255, 1, 5500.00),
(603, 6, 101, 261, 1, 3000.00),
(604, 6, 101, 249, 1, 4500.00),
(605, 6, 101, 267, 1, 2500.00),
(606, 6, 101, 273, 1, 1000.00),
(607, 6, 101, 237, 1, 40000.00),
(608, 6, 101, 255, 1, 5500.00),
(609, 7, 181, 279, 1, 40000.00),
(610, 7, 181, 297, 1, 5500.00),
(611, 7, 181, 291, 1, 4500.00),
(612, 7, 181, 309, 1, 2500.00),
(613, 7, 181, 315, 1, 1000.00),
(614, 7, 181, 303, 1, 3000.00),
(615, 7, 181, 285, 1, 12000.00),
(616, 7, 182, 279, 1, 40000.00),
(617, 7, 182, 297, 1, 5500.00),
(618, 7, 182, 291, 1, 4500.00),
(619, 7, 182, 309, 1, 2500.00),
(620, 7, 182, 315, 1, 1000.00),
(621, 7, 182, 303, 1, 3000.00),
(622, 7, 182, 285, 1, 12000.00),
(623, 7, 183, 279, 1, 40000.00),
(624, 7, 183, 297, 1, 5500.00),
(625, 7, 183, 291, 1, 4500.00),
(626, 7, 183, 309, 1, 2500.00),
(627, 7, 183, 315, 1, 1000.00),
(628, 7, 183, 303, 1, 3000.00),
(629, 7, 183, 285, 1, 12000.00),
(630, 7, 184, 279, 1, 40000.00),
(631, 7, 184, 297, 1, 5500.00),
(632, 7, 184, 291, 1, 4500.00),
(633, 7, 184, 309, 1, 2500.00),
(634, 7, 184, 315, 1, 1000.00),
(635, 7, 184, 303, 1, 3000.00),
(636, 7, 184, 285, 1, 12000.00),
(637, 7, 185, 279, 1, 40000.00),
(638, 7, 185, 297, 1, 5500.00),
(639, 7, 185, 291, 1, 4500.00),
(640, 7, 185, 309, 1, 2500.00),
(641, 7, 185, 315, 1, 1000.00),
(642, 7, 185, 303, 1, 3000.00),
(643, 7, 185, 285, 1, 12000.00),
(644, 7, 186, 279, 1, 40000.00),
(645, 7, 186, 297, 1, 5500.00),
(646, 7, 186, 291, 1, 4500.00),
(647, 7, 186, 309, 1, 2500.00),
(648, 7, 186, 315, 1, 1000.00),
(649, 7, 186, 303, 1, 3000.00),
(650, 7, 186, 285, 1, 12000.00),
(651, 7, 187, 279, 1, 40000.00),
(652, 7, 187, 297, 1, 5500.00),
(653, 7, 187, 291, 1, 4500.00),
(654, 7, 187, 309, 1, 2500.00),
(655, 7, 187, 315, 1, 1000.00),
(656, 7, 187, 303, 1, 3000.00),
(657, 7, 187, 285, 1, 12000.00),
(658, 7, 188, 279, 1, 40000.00),
(659, 7, 188, 297, 1, 5500.00),
(660, 7, 188, 291, 1, 4500.00),
(661, 7, 188, 309, 1, 2500.00),
(662, 7, 188, 315, 1, 1000.00),
(663, 7, 188, 303, 1, 3000.00),
(664, 7, 188, 285, 1, 12000.00),
(665, 7, 189, 279, 1, 40000.00),
(666, 7, 189, 297, 1, 5500.00),
(667, 7, 189, 291, 1, 4500.00),
(668, 7, 189, 309, 1, 2500.00),
(669, 7, 189, 315, 1, 1000.00),
(670, 7, 189, 303, 1, 3000.00),
(671, 7, 189, 285, 1, 12000.00),
(672, 7, 190, 279, 1, 40000.00),
(673, 7, 190, 297, 1, 5500.00),
(674, 7, 190, 291, 1, 4500.00),
(675, 7, 190, 309, 1, 2500.00),
(676, 7, 190, 315, 1, 1000.00),
(677, 7, 190, 303, 1, 3000.00),
(678, 7, 190, 285, 1, 12000.00),
(679, 7, 191, 279, 1, 40000.00),
(680, 7, 191, 297, 1, 5500.00),
(681, 7, 191, 291, 1, 4500.00),
(682, 7, 191, 309, 1, 2500.00),
(683, 7, 191, 315, 1, 1000.00),
(684, 7, 191, 303, 1, 3000.00),
(685, 7, 191, 285, 1, 12000.00),
(686, 7, 192, 279, 1, 40000.00),
(687, 7, 192, 297, 1, 5500.00),
(688, 7, 192, 291, 1, 4500.00),
(689, 7, 192, 309, 1, 2500.00),
(690, 7, 192, 315, 1, 1000.00),
(691, 7, 192, 303, 1, 3000.00),
(692, 7, 192, 285, 1, 12000.00),
(693, 7, 193, 279, 1, 40000.00),
(694, 7, 193, 297, 1, 5500.00),
(695, 7, 193, 291, 1, 4500.00),
(696, 7, 193, 309, 1, 2500.00),
(697, 7, 193, 315, 1, 1000.00),
(698, 7, 193, 303, 1, 3000.00),
(699, 7, 193, 285, 1, 12000.00),
(700, 7, 194, 304, 1, 2900.00),
(701, 7, 194, 292, 1, 5075.00),
(702, 7, 194, 310, 1, 2175.00),
(703, 7, 194, 316, 1, 725.00),
(704, 7, 194, 286, 1, 14500.00),
(705, 7, 194, 280, 1, 43500.00),
(706, 7, 194, 298, 1, 6525.00),
(707, 7, 195, 304, 1, 2900.00),
(708, 7, 195, 292, 1, 5075.00),
(709, 7, 195, 310, 1, 2175.00),
(710, 7, 195, 316, 1, 725.00),
(711, 7, 195, 286, 1, 14500.00),
(712, 7, 195, 280, 1, 43500.00),
(713, 7, 195, 298, 1, 6525.00),
(714, 7, 196, 304, 1, 2900.00),
(715, 7, 196, 292, 1, 5075.00),
(716, 7, 196, 310, 1, 2175.00),
(717, 7, 196, 316, 1, 725.00),
(718, 7, 196, 286, 1, 14500.00),
(719, 7, 196, 280, 1, 43500.00),
(720, 7, 196, 298, 1, 6525.00),
(721, 7, 197, 304, 1, 2900.00),
(722, 7, 197, 292, 1, 5075.00),
(723, 7, 197, 310, 1, 2175.00),
(724, 7, 197, 316, 1, 725.00),
(725, 7, 197, 286, 1, 14500.00),
(726, 7, 197, 280, 1, 43500.00),
(727, 7, 197, 298, 1, 6525.00),
(728, 7, 198, 304, 1, 2900.00),
(729, 7, 198, 292, 1, 5075.00),
(730, 7, 198, 310, 1, 2175.00),
(731, 7, 198, 316, 1, 725.00),
(732, 7, 198, 286, 1, 14500.00),
(733, 7, 198, 280, 1, 43500.00),
(734, 7, 198, 298, 1, 6525.00),
(735, 7, 199, 304, 1, 2900.00),
(736, 7, 199, 292, 1, 5075.00),
(737, 7, 199, 310, 1, 2175.00),
(738, 7, 199, 316, 1, 725.00),
(739, 7, 199, 286, 1, 14500.00),
(740, 7, 199, 280, 1, 43500.00),
(741, 7, 199, 298, 1, 6525.00),
(742, 7, 200, 304, 1, 2900.00),
(743, 7, 200, 292, 1, 5075.00),
(744, 7, 200, 310, 1, 2175.00),
(745, 7, 200, 316, 1, 725.00),
(746, 7, 200, 286, 1, 14500.00),
(747, 7, 200, 280, 1, 43500.00),
(748, 7, 200, 298, 1, 6525.00),
(749, 7, 201, 304, 1, 2900.00),
(750, 7, 201, 292, 1, 5075.00),
(751, 7, 201, 310, 1, 2175.00),
(752, 7, 201, 316, 1, 725.00),
(753, 7, 201, 286, 1, 14500.00),
(754, 7, 201, 280, 1, 43500.00),
(755, 7, 201, 298, 1, 6525.00),
(756, 7, 202, 304, 1, 2900.00),
(757, 7, 202, 292, 1, 5075.00),
(758, 7, 202, 310, 1, 2175.00),
(759, 7, 202, 316, 1, 725.00),
(760, 7, 202, 286, 1, 14500.00),
(761, 7, 202, 280, 1, 43500.00),
(762, 7, 202, 298, 1, 6525.00),
(763, 7, 203, 304, 1, 2900.00),
(764, 7, 203, 292, 1, 5075.00),
(765, 7, 203, 310, 1, 2175.00),
(766, 7, 203, 316, 1, 725.00),
(767, 7, 203, 286, 1, 14500.00),
(768, 7, 203, 280, 1, 43500.00),
(769, 7, 203, 298, 1, 6525.00),
(770, 7, 204, 304, 1, 2900.00),
(771, 7, 204, 292, 1, 5075.00),
(772, 7, 204, 310, 1, 2175.00),
(773, 7, 204, 316, 1, 725.00),
(774, 7, 204, 286, 1, 14500.00),
(775, 7, 204, 280, 1, 43500.00),
(776, 7, 204, 298, 1, 6525.00),
(777, 7, 205, 304, 1, 2900.00),
(778, 7, 205, 292, 1, 5075.00),
(779, 7, 205, 310, 1, 2175.00),
(780, 7, 205, 316, 1, 725.00),
(781, 7, 205, 286, 1, 14500.00),
(782, 7, 205, 280, 1, 43500.00),
(783, 7, 205, 298, 1, 6525.00),
(784, 7, 206, 304, 1, 2900.00),
(785, 7, 206, 292, 1, 5075.00),
(786, 7, 206, 310, 1, 2175.00),
(787, 7, 206, 316, 1, 725.00),
(788, 7, 206, 286, 1, 14500.00),
(789, 7, 206, 280, 1, 43500.00),
(790, 7, 206, 298, 1, 6525.00),
(791, 7, 207, 304, 1, 2900.00),
(792, 7, 207, 292, 1, 5075.00),
(793, 7, 207, 310, 1, 2175.00),
(794, 7, 207, 316, 1, 725.00),
(795, 7, 207, 286, 1, 14500.00),
(796, 7, 207, 280, 1, 43500.00),
(797, 7, 207, 298, 1, 6525.00),
(798, 7, 208, 304, 1, 2900.00),
(799, 7, 208, 292, 1, 5075.00),
(800, 7, 208, 310, 1, 2175.00),
(801, 7, 208, 316, 1, 725.00),
(802, 7, 208, 286, 1, 14500.00),
(803, 7, 208, 280, 1, 43500.00),
(804, 7, 208, 298, 1, 6525.00),
(805, 7, 209, 304, 1, 2900.00),
(806, 7, 209, 292, 1, 5075.00),
(807, 7, 209, 310, 1, 2175.00),
(808, 7, 209, 316, 1, 725.00),
(809, 7, 209, 286, 1, 14500.00),
(810, 7, 209, 280, 1, 43500.00),
(811, 7, 209, 298, 1, 6525.00),
(812, 7, 210, 304, 1, 2900.00),
(813, 7, 210, 292, 1, 5075.00),
(814, 7, 210, 310, 1, 2175.00),
(815, 7, 210, 316, 1, 725.00),
(816, 7, 210, 286, 1, 14500.00),
(817, 7, 210, 280, 1, 43500.00),
(818, 7, 210, 298, 1, 6525.00),
(819, 7, 211, 304, 1, 2900.00),
(820, 7, 211, 292, 1, 5075.00),
(821, 7, 211, 310, 1, 2175.00),
(822, 7, 211, 316, 1, 725.00),
(823, 7, 211, 286, 1, 14500.00),
(824, 7, 211, 280, 1, 43500.00),
(825, 7, 211, 298, 1, 6525.00),
(826, 7, 212, 304, 1, 2900.00),
(827, 7, 212, 292, 1, 5075.00),
(828, 7, 212, 310, 1, 2175.00),
(829, 7, 212, 316, 1, 725.00),
(830, 7, 212, 286, 1, 14500.00),
(831, 7, 212, 280, 1, 43500.00),
(832, 7, 212, 298, 1, 6525.00),
(833, 7, 213, 304, 1, 2900.00),
(834, 7, 213, 292, 1, 5075.00),
(835, 7, 213, 310, 1, 2175.00),
(836, 7, 213, 316, 1, 725.00),
(837, 7, 213, 286, 1, 14500.00),
(838, 7, 213, 280, 1, 43500.00),
(839, 7, 213, 298, 1, 6525.00),
(840, 8, 214, 345, 1, 3000.00),
(841, 8, 214, 333, 1, 4500.00),
(842, 8, 214, 351, 1, 2500.00),
(843, 8, 214, 357, 1, 1000.00),
(844, 8, 214, 327, 1, 12000.00),
(845, 8, 214, 321, 1, 40000.00),
(846, 8, 214, 339, 1, 5500.00),
(847, 8, 215, 345, 1, 3000.00),
(848, 8, 215, 333, 1, 4500.00),
(849, 8, 215, 351, 1, 2500.00),
(850, 8, 215, 357, 1, 1000.00),
(851, 8, 215, 327, 1, 12000.00),
(852, 8, 215, 321, 1, 40000.00),
(853, 8, 215, 339, 1, 5500.00),
(854, 8, 216, 345, 1, 3000.00),
(855, 8, 216, 333, 1, 4500.00),
(856, 8, 216, 351, 1, 2500.00),
(857, 8, 216, 357, 1, 1000.00),
(858, 8, 216, 327, 1, 12000.00),
(859, 8, 216, 321, 1, 40000.00),
(860, 8, 216, 339, 1, 5500.00),
(861, 8, 217, 345, 1, 3000.00),
(862, 8, 217, 333, 1, 4500.00),
(863, 8, 217, 351, 1, 2500.00),
(864, 8, 217, 357, 1, 1000.00),
(865, 8, 217, 327, 1, 12000.00),
(866, 8, 217, 321, 1, 40000.00),
(867, 8, 217, 339, 1, 5500.00),
(868, 8, 218, 345, 1, 3000.00),
(869, 8, 218, 333, 1, 4500.00),
(870, 8, 218, 351, 1, 2500.00),
(871, 8, 218, 357, 1, 1000.00),
(872, 8, 218, 327, 1, 12000.00),
(873, 8, 218, 321, 1, 40000.00),
(874, 8, 218, 339, 1, 5500.00),
(875, 8, 219, 345, 1, 3000.00),
(876, 8, 219, 333, 1, 4500.00),
(877, 8, 219, 351, 1, 2500.00),
(878, 8, 219, 357, 1, 1000.00),
(879, 8, 219, 327, 1, 12000.00),
(880, 8, 219, 321, 1, 40000.00),
(881, 8, 219, 339, 1, 5500.00),
(882, 8, 220, 345, 1, 3000.00),
(883, 8, 220, 333, 1, 4500.00),
(884, 8, 220, 351, 1, 2500.00),
(885, 8, 220, 357, 1, 1000.00),
(886, 8, 220, 327, 1, 12000.00),
(887, 8, 220, 321, 1, 40000.00),
(888, 8, 220, 339, 1, 5500.00),
(889, 8, 221, 345, 1, 3000.00),
(890, 8, 221, 333, 1, 4500.00),
(891, 8, 221, 351, 1, 2500.00),
(892, 8, 221, 357, 1, 1000.00),
(893, 8, 221, 327, 1, 12000.00),
(894, 8, 221, 321, 1, 40000.00),
(895, 8, 221, 339, 1, 5500.00),
(896, 8, 222, 345, 1, 3000.00),
(897, 8, 222, 333, 1, 4500.00),
(898, 8, 222, 351, 1, 2500.00),
(899, 8, 222, 357, 1, 1000.00),
(900, 8, 222, 327, 1, 12000.00),
(901, 8, 222, 321, 1, 40000.00),
(902, 8, 222, 339, 1, 5500.00),
(903, 8, 223, 345, 1, 3000.00),
(904, 8, 223, 333, 1, 4500.00),
(905, 8, 223, 351, 1, 2500.00),
(906, 8, 223, 357, 1, 1000.00),
(907, 8, 223, 327, 1, 12000.00),
(908, 8, 223, 321, 1, 40000.00),
(909, 8, 223, 339, 1, 5500.00),
(910, 8, 224, 345, 1, 3000.00),
(911, 8, 224, 333, 1, 4500.00),
(912, 8, 224, 351, 1, 2500.00),
(913, 8, 224, 357, 1, 1000.00),
(914, 8, 224, 327, 1, 12000.00),
(915, 8, 224, 321, 1, 40000.00),
(916, 8, 224, 339, 1, 5500.00),
(917, 8, 225, 345, 1, 3000.00),
(918, 8, 225, 333, 1, 4500.00),
(919, 8, 225, 351, 1, 2500.00),
(920, 8, 225, 357, 1, 1000.00),
(921, 8, 225, 327, 1, 12000.00),
(922, 8, 225, 321, 1, 40000.00),
(923, 8, 225, 339, 1, 5500.00),
(924, 8, 226, 345, 1, 3000.00),
(925, 8, 226, 333, 1, 4500.00),
(926, 8, 226, 351, 1, 2500.00),
(927, 8, 226, 357, 1, 1000.00),
(928, 8, 226, 327, 1, 12000.00),
(929, 8, 226, 321, 1, 40000.00),
(930, 8, 226, 339, 1, 5500.00),
(931, 9, 227, 363, 1, 40000.00),
(932, 9, 227, 375, 1, 4500.00),
(933, 9, 227, 387, 1, 3000.00),
(934, 9, 227, 393, 1, 2500.00),
(935, 9, 227, 399, 1, 1000.00),
(936, 9, 227, 381, 1, 5500.00),
(937, 9, 227, 369, 1, 12000.00),
(938, 9, 228, 363, 1, 40000.00),
(939, 9, 228, 375, 1, 4500.00),
(940, 9, 228, 387, 1, 3000.00),
(941, 9, 228, 393, 1, 2500.00),
(942, 9, 228, 399, 1, 1000.00),
(943, 9, 228, 381, 1, 5500.00),
(944, 9, 228, 369, 1, 12000.00),
(945, 9, 229, 363, 1, 40000.00),
(946, 9, 229, 375, 1, 4500.00),
(947, 9, 229, 387, 1, 3000.00),
(948, 9, 229, 393, 1, 2500.00),
(949, 9, 229, 399, 1, 1000.00),
(950, 9, 229, 381, 1, 5500.00),
(951, 9, 229, 369, 1, 12000.00),
(952, 9, 230, 363, 1, 40000.00),
(953, 9, 230, 375, 1, 4500.00),
(954, 9, 230, 387, 1, 3000.00),
(955, 9, 230, 393, 1, 2500.00),
(956, 9, 230, 399, 1, 1000.00),
(957, 9, 230, 381, 1, 5500.00),
(958, 9, 230, 369, 1, 12000.00),
(959, 9, 231, 363, 1, 40000.00),
(960, 9, 231, 375, 1, 4500.00),
(961, 9, 231, 387, 1, 3000.00),
(962, 9, 231, 393, 1, 2500.00),
(963, 9, 231, 399, 1, 1000.00),
(964, 9, 231, 381, 1, 5500.00),
(965, 9, 231, 369, 1, 12000.00),
(966, 9, 232, 363, 1, 40000.00),
(967, 9, 232, 375, 1, 4500.00),
(968, 9, 232, 387, 1, 3000.00),
(969, 9, 232, 393, 1, 2500.00),
(970, 9, 232, 399, 1, 1000.00),
(971, 9, 232, 381, 1, 5500.00),
(972, 9, 232, 369, 1, 12000.00),
(973, 9, 233, 363, 1, 40000.00),
(974, 9, 233, 375, 1, 4500.00),
(975, 9, 233, 387, 1, 3000.00),
(976, 9, 233, 393, 1, 2500.00),
(977, 9, 233, 399, 1, 1000.00),
(978, 9, 233, 381, 1, 5500.00),
(979, 9, 233, 369, 1, 12000.00),
(980, 9, 234, 363, 1, 40000.00),
(981, 9, 234, 375, 1, 4500.00),
(982, 9, 234, 387, 1, 3000.00),
(983, 9, 234, 393, 1, 2500.00),
(984, 9, 234, 399, 1, 1000.00),
(985, 9, 234, 381, 1, 5500.00),
(986, 9, 234, 369, 1, 12000.00),
(987, 9, 235, 363, 1, 40000.00),
(988, 9, 235, 375, 1, 4500.00),
(989, 9, 235, 387, 1, 3000.00),
(990, 9, 235, 393, 1, 2500.00),
(991, 9, 235, 399, 1, 1000.00),
(992, 9, 235, 381, 1, 5500.00),
(993, 9, 235, 369, 1, 12000.00),
(994, 9, 236, 363, 1, 40000.00),
(995, 9, 236, 375, 1, 4500.00),
(996, 9, 236, 387, 1, 3000.00),
(997, 9, 236, 393, 1, 2500.00),
(998, 9, 236, 399, 1, 1000.00),
(999, 9, 236, 381, 1, 5500.00),
(1000, 9, 236, 369, 1, 12000.00),
(1001, 9, 237, 363, 1, 40000.00),
(1002, 9, 237, 375, 1, 4500.00),
(1003, 9, 237, 387, 1, 3000.00),
(1004, 9, 237, 393, 1, 2500.00),
(1005, 9, 237, 399, 1, 1000.00),
(1006, 9, 237, 381, 1, 5500.00),
(1007, 9, 237, 369, 1, 12000.00),
(1008, 9, 238, 363, 1, 40000.00),
(1009, 9, 238, 375, 1, 4500.00),
(1010, 9, 238, 387, 1, 3000.00),
(1011, 9, 238, 393, 1, 2500.00),
(1012, 9, 238, 399, 1, 1000.00),
(1013, 9, 238, 381, 1, 5500.00),
(1014, 9, 238, 369, 1, 12000.00),
(1015, 9, 239, 363, 1, 40000.00),
(1016, 9, 239, 375, 1, 4500.00),
(1017, 9, 239, 387, 1, 3000.00),
(1018, 9, 239, 393, 1, 2500.00),
(1019, 9, 239, 399, 1, 1000.00),
(1020, 9, 239, 381, 1, 5500.00),
(1021, 9, 239, 369, 1, 12000.00),
(1022, 9, 240, 388, 1, 2900.00),
(1023, 9, 240, 376, 1, 5075.00),
(1024, 9, 240, 394, 1, 2175.00),
(1025, 9, 240, 400, 1, 725.00),
(1026, 9, 240, 364, 1, 43500.00),
(1027, 9, 240, 370, 1, 14500.00),
(1028, 9, 240, 382, 1, 6525.00),
(1029, 9, 241, 388, 1, 2900.00),
(1030, 9, 241, 376, 1, 5075.00),
(1031, 9, 241, 394, 1, 2175.00),
(1032, 9, 241, 400, 1, 725.00),
(1033, 9, 241, 364, 1, 43500.00),
(1034, 9, 241, 370, 1, 14500.00),
(1035, 9, 241, 382, 1, 6525.00),
(1036, 9, 242, 388, 1, 2900.00),
(1037, 9, 242, 376, 1, 5075.00),
(1038, 9, 242, 394, 1, 2175.00),
(1039, 9, 242, 400, 1, 725.00),
(1040, 9, 242, 364, 1, 43500.00),
(1041, 9, 242, 370, 1, 14500.00),
(1042, 9, 242, 382, 1, 6525.00),
(1043, 9, 243, 388, 1, 2900.00),
(1044, 9, 243, 376, 1, 5075.00),
(1045, 9, 243, 394, 1, 2175.00),
(1046, 9, 243, 400, 1, 725.00),
(1047, 9, 243, 364, 1, 43500.00),
(1048, 9, 243, 370, 1, 14500.00),
(1049, 9, 243, 382, 1, 6525.00),
(1050, 9, 244, 388, 1, 2900.00),
(1051, 9, 244, 376, 1, 5075.00),
(1052, 9, 244, 394, 1, 2175.00),
(1053, 9, 244, 400, 1, 725.00),
(1054, 9, 244, 364, 1, 43500.00),
(1055, 9, 244, 370, 1, 14500.00),
(1056, 9, 244, 382, 1, 6525.00),
(1057, 9, 245, 388, 1, 2900.00),
(1058, 9, 245, 376, 1, 5075.00),
(1059, 9, 245, 394, 1, 2175.00),
(1060, 9, 245, 400, 1, 725.00),
(1061, 9, 245, 364, 1, 43500.00),
(1062, 9, 245, 370, 1, 14500.00),
(1063, 9, 245, 382, 1, 6525.00),
(1064, 9, 246, 388, 1, 2900.00),
(1065, 9, 246, 376, 1, 5075.00),
(1066, 9, 246, 394, 1, 2175.00),
(1067, 9, 246, 400, 1, 725.00),
(1068, 9, 246, 364, 1, 43500.00),
(1069, 9, 246, 370, 1, 14500.00),
(1070, 9, 246, 382, 1, 6525.00),
(1071, 9, 247, 388, 1, 2900.00),
(1072, 9, 247, 376, 1, 5075.00),
(1073, 9, 247, 394, 1, 2175.00),
(1074, 9, 247, 400, 1, 725.00),
(1075, 9, 247, 364, 1, 43500.00),
(1076, 9, 247, 370, 1, 14500.00),
(1077, 9, 247, 382, 1, 6525.00),
(1078, 9, 248, 388, 1, 2900.00),
(1079, 9, 248, 376, 1, 5075.00),
(1080, 9, 248, 394, 1, 2175.00),
(1081, 9, 248, 400, 1, 725.00),
(1082, 9, 248, 364, 1, 43500.00),
(1083, 9, 248, 370, 1, 14500.00),
(1084, 9, 248, 382, 1, 6525.00),
(1085, 9, 249, 388, 1, 2900.00),
(1086, 9, 249, 376, 1, 5075.00),
(1087, 9, 249, 394, 1, 2175.00),
(1088, 9, 249, 400, 1, 725.00),
(1089, 9, 249, 364, 1, 43500.00),
(1090, 9, 249, 370, 1, 14500.00),
(1091, 9, 249, 382, 1, 6525.00),
(1092, 9, 250, 388, 1, 2900.00),
(1093, 9, 250, 376, 1, 5075.00),
(1094, 9, 250, 394, 1, 2175.00),
(1095, 9, 250, 400, 1, 725.00),
(1096, 9, 250, 364, 1, 43500.00),
(1097, 9, 250, 370, 1, 14500.00),
(1098, 9, 250, 382, 1, 6525.00),
(1099, 9, 251, 388, 1, 2900.00),
(1100, 9, 251, 376, 1, 5075.00),
(1101, 9, 251, 394, 1, 2175.00),
(1102, 9, 251, 400, 1, 725.00),
(1103, 9, 251, 364, 1, 43500.00),
(1104, 9, 251, 370, 1, 14500.00),
(1105, 9, 251, 382, 1, 6525.00),
(1106, 9, 252, 388, 1, 2900.00),
(1107, 9, 252, 376, 1, 5075.00),
(1108, 9, 252, 394, 1, 2175.00),
(1109, 9, 252, 400, 1, 725.00),
(1110, 9, 252, 364, 1, 43500.00),
(1111, 9, 252, 370, 1, 14500.00),
(1112, 9, 252, 382, 1, 6525.00),
(1113, 9, 253, 388, 1, 2900.00),
(1114, 9, 253, 376, 1, 5075.00),
(1115, 9, 253, 394, 1, 2175.00),
(1116, 9, 253, 400, 1, 725.00),
(1117, 9, 253, 364, 1, 43500.00),
(1118, 9, 253, 370, 1, 14500.00),
(1119, 9, 253, 382, 1, 6525.00),
(1120, 9, 254, 388, 1, 2900.00),
(1121, 9, 254, 376, 1, 5075.00),
(1122, 9, 254, 394, 1, 2175.00),
(1123, 9, 254, 400, 1, 725.00),
(1124, 9, 254, 364, 1, 43500.00),
(1125, 9, 254, 370, 1, 14500.00),
(1126, 9, 254, 382, 1, 6525.00),
(1127, 9, 255, 388, 1, 2900.00),
(1128, 9, 255, 376, 1, 5075.00),
(1129, 9, 255, 394, 1, 2175.00),
(1130, 9, 255, 400, 1, 725.00),
(1131, 9, 255, 364, 1, 43500.00),
(1132, 9, 255, 370, 1, 14500.00),
(1133, 9, 255, 382, 1, 6525.00),
(1134, 9, 256, 388, 1, 2900.00),
(1135, 9, 256, 376, 1, 5075.00),
(1136, 9, 256, 394, 1, 2175.00),
(1137, 9, 256, 400, 1, 725.00),
(1138, 9, 256, 364, 1, 43500.00),
(1139, 9, 256, 370, 1, 14500.00),
(1140, 9, 256, 382, 1, 6525.00),
(1141, 9, 257, 388, 1, 2900.00),
(1142, 9, 257, 376, 1, 5075.00),
(1143, 9, 257, 394, 1, 2175.00),
(1144, 9, 257, 400, 1, 725.00),
(1145, 9, 257, 364, 1, 43500.00),
(1146, 9, 257, 370, 1, 14500.00),
(1147, 9, 257, 382, 1, 6525.00),
(1148, 9, 258, 388, 1, 2900.00),
(1149, 9, 258, 376, 1, 5075.00),
(1150, 9, 258, 394, 1, 2175.00),
(1151, 9, 258, 400, 1, 725.00),
(1152, 9, 258, 364, 1, 43500.00),
(1153, 9, 258, 370, 1, 14500.00),
(1154, 9, 258, 382, 1, 6525.00),
(1155, 9, 259, 388, 1, 2900.00),
(1156, 9, 259, 376, 1, 5075.00),
(1157, 9, 259, 394, 1, 2175.00),
(1158, 9, 259, 400, 1, 725.00),
(1159, 9, 259, 364, 1, 43500.00),
(1160, 9, 259, 370, 1, 14500.00),
(1161, 9, 259, 382, 1, 6525.00),
(1162, 9, 260, 389, 1, 3200.00),
(1163, 9, 260, 377, 1, 5600.00),
(1164, 9, 260, 395, 1, 2400.00),
(1165, 9, 260, 401, 1, 800.00),
(1166, 9, 260, 371, 1, 16000.00),
(1167, 9, 260, 365, 1, 48000.00),
(1168, 9, 260, 383, 1, 7200.00),
(1169, 9, 261, 389, 1, 3200.00),
(1170, 9, 261, 377, 1, 5600.00),
(1171, 9, 261, 395, 1, 2400.00),
(1172, 9, 261, 401, 1, 800.00),
(1173, 9, 261, 371, 1, 16000.00),
(1174, 9, 261, 365, 1, 48000.00),
(1175, 9, 261, 383, 1, 7200.00),
(1176, 9, 262, 389, 1, 3200.00),
(1177, 9, 262, 377, 1, 5600.00),
(1178, 9, 262, 395, 1, 2400.00),
(1179, 9, 262, 401, 1, 800.00),
(1180, 9, 262, 371, 1, 16000.00),
(1181, 9, 262, 365, 1, 48000.00),
(1182, 9, 262, 383, 1, 7200.00),
(1183, 9, 263, 389, 1, 3200.00),
(1184, 9, 263, 377, 1, 5600.00),
(1185, 9, 263, 395, 1, 2400.00),
(1186, 9, 263, 401, 1, 800.00),
(1187, 9, 263, 371, 1, 16000.00),
(1188, 9, 263, 365, 1, 48000.00),
(1189, 9, 263, 383, 1, 7200.00),
(1190, 9, 264, 389, 1, 3200.00),
(1191, 9, 264, 377, 1, 5600.00),
(1192, 9, 264, 395, 1, 2400.00),
(1193, 9, 264, 401, 1, 800.00),
(1194, 9, 264, 371, 1, 16000.00),
(1195, 9, 264, 365, 1, 48000.00),
(1196, 9, 264, 383, 1, 7200.00),
(1197, 9, 265, 389, 1, 3200.00),
(1198, 9, 265, 377, 1, 5600.00),
(1199, 9, 265, 395, 1, 2400.00),
(1200, 9, 265, 401, 1, 800.00),
(1201, 9, 265, 371, 1, 16000.00),
(1202, 9, 265, 365, 1, 48000.00),
(1203, 9, 265, 383, 1, 7200.00),
(1204, 9, 266, 389, 1, 3200.00),
(1205, 9, 266, 377, 1, 5600.00),
(1206, 9, 266, 395, 1, 2400.00),
(1207, 9, 266, 401, 1, 800.00),
(1208, 9, 266, 371, 1, 16000.00),
(1209, 9, 266, 365, 1, 48000.00),
(1210, 9, 266, 383, 1, 7200.00),
(1211, 9, 267, 389, 1, 3200.00),
(1212, 9, 267, 377, 1, 5600.00),
(1213, 9, 267, 395, 1, 2400.00),
(1214, 9, 267, 401, 1, 800.00),
(1215, 9, 267, 371, 1, 16000.00),
(1216, 9, 267, 365, 1, 48000.00),
(1217, 9, 267, 383, 1, 7200.00),
(1218, 9, 268, 389, 1, 3200.00),
(1219, 9, 268, 377, 1, 5600.00),
(1220, 9, 268, 395, 1, 2400.00),
(1221, 9, 268, 401, 1, 800.00),
(1222, 9, 268, 371, 1, 16000.00),
(1223, 9, 268, 365, 1, 48000.00),
(1224, 9, 268, 383, 1, 7200.00),
(1225, 9, 269, 389, 1, 3200.00),
(1226, 9, 269, 377, 1, 5600.00),
(1227, 9, 269, 395, 1, 2400.00),
(1228, 9, 269, 401, 1, 800.00),
(1229, 9, 269, 371, 1, 16000.00),
(1230, 9, 269, 365, 1, 48000.00),
(1231, 9, 269, 383, 1, 7200.00),
(1232, 9, 270, 389, 1, 3200.00),
(1233, 9, 270, 377, 1, 5600.00),
(1234, 9, 270, 395, 1, 2400.00),
(1235, 9, 270, 401, 1, 800.00),
(1236, 9, 270, 371, 1, 16000.00),
(1237, 9, 270, 365, 1, 48000.00),
(1238, 9, 270, 383, 1, 7200.00),
(1239, 9, 271, 389, 1, 3200.00),
(1240, 9, 271, 377, 1, 5600.00),
(1241, 9, 271, 395, 1, 2400.00),
(1242, 9, 271, 401, 1, 800.00),
(1243, 9, 271, 371, 1, 16000.00),
(1244, 9, 271, 365, 1, 48000.00),
(1245, 9, 271, 383, 1, 7200.00),
(1246, 9, 272, 389, 1, 3200.00),
(1247, 9, 272, 377, 1, 5600.00),
(1248, 9, 272, 395, 1, 2400.00),
(1249, 9, 272, 401, 1, 800.00),
(1250, 9, 272, 371, 1, 16000.00),
(1251, 9, 272, 365, 1, 48000.00),
(1252, 9, 272, 383, 1, 7200.00),
(1253, 9, 273, 389, 1, 3200.00),
(1254, 9, 273, 377, 1, 5600.00),
(1255, 9, 273, 395, 1, 2400.00),
(1256, 9, 273, 401, 1, 800.00),
(1257, 9, 273, 371, 1, 16000.00),
(1258, 9, 273, 365, 1, 48000.00),
(1259, 9, 273, 383, 1, 7200.00),
(1260, 9, 274, 389, 1, 3200.00),
(1261, 9, 274, 377, 1, 5600.00),
(1262, 9, 274, 395, 1, 2400.00),
(1263, 9, 274, 401, 1, 800.00),
(1264, 9, 274, 371, 1, 16000.00),
(1265, 9, 274, 365, 1, 48000.00),
(1266, 9, 274, 383, 1, 7200.00),
(1267, 6, 275, 261, 1, 3000.00),
(1268, 6, 275, 249, 1, 4500.00),
(1269, 6, 275, 267, 1, 2500.00),
(1270, 6, 275, 273, 1, 1000.00),
(1271, 6, 275, 237, 1, 40000.00),
(1272, 6, 275, 255, 1, 5500.00),
(1273, 9, 276, 362, 1, 44500.00),
(1274, 9, 276, 386, 1, 5000.00),
(1275, 9, 276, 407, 1, 10000.00),
(1276, 9, 277, 386, 1, 5000.00),
(1277, 9, 277, 406, 1, 40000.00),
(1278, 9, 277, 407, 1, 10000.00),
(1279, 11, 278, 414, 1, 38500.00),
(1280, 11, 278, 415, 1, 10000.00),
(1281, 11, 278, 442, 1, 5000.00),
(1282, 11, 278, 433, 1, 5000.00),
(1283, 11, 279, 414, 1, 36000.00),
(1284, 11, 279, 415, 1, 10000.00),
(1285, 11, 279, 435, 1, 5000.00),
(1286, 11, 279, 433, 1, 5000.00),
(1287, 11, 280, 410, 1, 500.00),
(1288, 11, 280, 414, 1, 38500.00),
(1289, 11, 280, 415, 1, 10000.00),
(1290, 11, 280, 417, 1, 10000.00),
(1291, 11, 281, 410, 1, 500.00),
(1292, 11, 281, 414, 1, 32500.00),
(1293, 11, 281, 415, 1, 9000.00),
(1294, 11, 281, 416, 1, 3000.00),
(1295, 11, 282, 410, 1, 500.00),
(1296, 11, 282, 414, 1, 38500.00),
(1297, 11, 282, 415, 1, 10000.00),
(1298, 11, 283, 410, 1, 500.00),
(1299, 11, 283, 414, 1, 38500.00),
(1300, 11, 283, 415, 1, 10000.00),
(1301, 11, 283, 418, 1, 10000.00),
(1302, 11, 284, 435, 1, 5000.00),
(1303, 11, 284, 433, 1, 5000.00),
(1304, 11, 285, 410, 1, 500.00),
(1305, 11, 285, 414, 1, 36000.00),
(1306, 11, 285, 415, 1, 10000.00),
(1307, 11, 286, 444, 1, 36500.00),
(1308, 11, 286, 410, 1, 500.00),
(1309, 11, 286, 417, 1, 10000.00),
(1310, 11, 287, 444, 1, 77100.00),
(1311, 11, 287, 414, 1, 38500.00),
(1312, 11, 287, 415, 1, 10000.00),
(1313, 11, 287, 410, 1, 500.00),
(1314, 11, 288, 444, 1, 35500.00),
(1315, 11, 288, 414, 1, 38500.00),
(1316, 11, 288, 415, 1, 10000.00),
(1317, 11, 288, 435, 1, 5000.00),
(1318, 11, 288, 433, 1, 5000.00),
(1319, 11, 289, 444, 1, 34000.00),
(1320, 11, 289, 414, 1, 38500.00),
(1321, 11, 289, 415, 1, 10000.00),
(1322, 11, 289, 410, 1, 500.00),
(1323, 11, 289, 418, 1, 10000.00),
(1324, 11, 290, 444, 1, 68050.00),
(1325, 11, 290, 414, 1, 37000.00),
(1326, 11, 290, 415, 1, 10000.00),
(1327, 11, 290, 410, 1, 500.00),
(1328, 11, 291, 414, 1, 38500.00),
(1329, 11, 291, 415, 1, 10000.00),
(1330, 11, 291, 410, 1, 500.00),
(1331, 11, 292, 414, 1, 38500.00),
(1332, 11, 292, 415, 1, 10000.00),
(1333, 11, 292, 435, 1, 5000.00),
(1334, 11, 292, 433, 1, 5000.00),
(1335, 11, 293, 414, 1, 38500.00),
(1336, 11, 293, 415, 1, 10000.00),
(1337, 11, 293, 410, 1, 500.00),
(1338, 11, 293, 418, 1, 10000.00),
(1339, 11, 294, 414, 1, 38500.00),
(1340, 11, 294, 415, 1, 10000.00),
(1341, 11, 294, 410, 1, 500.00),
(1342, 11, 295, 414, 1, 38500.00),
(1343, 11, 295, 415, 1, 10000.00);

--
-- Triggers `invoice_items`
--
DELIMITER $$
CREATE TRIGGER `update_invoice_total` AFTER INSERT ON `invoice_items` FOR EACH ROW BEGIN
  UPDATE invoices 
  SET total_amount = (
    SELECT SUM(quantity * unit_price) 
    FROM invoice_items 
    WHERE invoice_id = NEW.invoice_id
  )
  WHERE id = NEW.invoice_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_invoice_total_delete` AFTER DELETE ON `invoice_items` FOR EACH ROW BEGIN
  UPDATE invoices 
  SET total_amount = (
    SELECT SUM(quantity * unit_price) 
    FROM invoice_items 
    WHERE invoice_id = OLD.invoice_id
  )
  WHERE id = OLD.invoice_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_invoice_total_update` AFTER UPDATE ON `invoice_items` FOR EACH ROW BEGIN
  UPDATE invoices 
  SET total_amount = (
    SELECT SUM(quantity * unit_price) 
    FROM invoice_items 
    WHERE invoice_id = NEW.invoice_id
  )
  WHERE id = NEW.invoice_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_templates`
--

CREATE TABLE `invoice_templates` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `items` text NOT NULL COMMENT 'JSON array of items',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `class_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_templates`
--

INSERT INTO `invoice_templates` (`id`, `school_id`, `name`, `items`, `created_at`, `class_id`) VALUES
(1, 1, 'Grade 3', '\"[{\\\"item_id\\\":\\\"18\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"52500.00\\\",\\\"description\\\":\\\"\\\"},{\\\"item_id\\\":\\\"15\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"2500.00\\\",\\\"description\\\":\\\"\\\"},{\\\"item_id\\\":\\\"7\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"12000.00\\\",\\\"description\\\":\\\"\\\"},{\\\"item_id\\\":\\\"11\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"5500.00\\\",\\\"description\\\":\\\"\\\"},{\\\"item_id\\\":\\\"25\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"22550.00\\\",\\\"description\\\":\\\"\\\"},{\\\"item_id\\\":\\\"12\\\",\\\"quantity\\\":\\\"1\\\",\\\"unit_price\\\":\\\"3550.00\\\",\\\"description\\\":\\\"\\\"}]\"', '2025-06-16 09:25:12', NULL),
(2, 1, 'Grade 1 Template', '[{\"item_id\":\"3\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"38000.00\"},{\"item_id\":\"17\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2000.00\"},{\"item_id\":\"5\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"9000.00\"},{\"item_id\":\"9\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"3500.00\"},{\"item_id\":\"20\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"10000.00\"}]', '2025-06-29 19:57:56', NULL),
(3, 1, 'Grade 2', '[{\"item_id\":\"2\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"40000.00\"},{\"item_id\":\"16\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2500.00\"},{\"item_id\":\"10\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"4500.00\"},{\"item_id\":\"23\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"15000.00\"},{\"item_id\":\"6\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"10000.00\"}]', '2025-06-30 12:19:29', NULL),
(4, 2, 'Grade 1 Template', '[{\"item_id\":\"44\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"50500.00\"},{\"item_id\":\"46\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"10000.00\"},{\"item_id\":\"53\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"5000.00\"},{\"item_id\":\"52\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"5000.00\"}]', '2025-07-01 21:05:48', NULL),
(6, 4, 'Grade 1', '[{\"item_id\":\"121\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"38500.00\"},{\"item_id\":\"127\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"10000.00\"},{\"item_id\":\"133\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"5000.00\"}]', '2025-07-11 14:57:16', NULL),
(7, 5, 'Grade 1', '[{\"item_id\":\"191\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"40000.00\"},{\"item_id\":\"219\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"3000.00\"},{\"item_id\":\"205\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"4500.00\"},{\"item_id\":\"226\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2500.00\"},{\"item_id\":\"233\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"1000.00\"},{\"item_id\":\"198\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"12000.00\"},{\"item_id\":\"212\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"5500.00\"}]', '2025-07-14 23:17:23', NULL),
(8, 5, 'Grade 2', '[{\"item_id\":\"192\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"43500.00\"},{\"item_id\":\"220\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2900.00\"},{\"item_id\":\"206\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"5075.00\"},{\"item_id\":\"227\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2175.00\"},{\"item_id\":\"234\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"725.00\"},{\"item_id\":\"213\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"6525.00\"},{\"item_id\":\"199\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"14500.00\"}]', '2025-07-15 13:50:43', NULL),
(9, 6, 'Grade 5', '[]', '2025-07-16 22:05:49', 57),
(10, 6, 'Grade 4', '[]', '2025-07-16 22:06:59', 56),
(11, 6, 'Grade 3', '[]', '2025-07-16 22:07:51', 55),
(12, 6, 'Grade 2', '[]', '2025-07-16 22:08:43', 54),
(13, 6, 'Grade 1', '[{\"item_id\":\"261\",\"quantity\":\"1\",\"unit_price\":\"3000.00\"},{\"item_id\":\"249\",\"quantity\":\"1\",\"unit_price\":\"4500.00\"},{\"item_id\":\"267\",\"quantity\":\"1\",\"unit_price\":\"2500.00\"},{\"item_id\":\"273\",\"quantity\":\"1\",\"unit_price\":\"1000.00\"},{\"item_id\":\"237\",\"quantity\":\"1\",\"unit_price\":\"40000.00\"},{\"item_id\":\"255\",\"quantity\":\"1\",\"unit_price\":\"5500.00\"}]', '2025-07-16 22:09:32', 53),
(14, 7, 'Grade 1', '[{\"item_id\":\"279\",\"quantity\":\"1\",\"unit_price\":\"40000.00\"},{\"item_id\":\"297\",\"quantity\":\"1\",\"unit_price\":\"5500.00\"},{\"item_id\":\"291\",\"quantity\":\"1\",\"unit_price\":\"4500.00\"},{\"item_id\":\"309\",\"quantity\":\"1\",\"unit_price\":\"2500.00\"},{\"item_id\":\"315\",\"quantity\":\"1\",\"unit_price\":\"1000.00\"},{\"item_id\":\"303\",\"quantity\":\"1\",\"unit_price\":\"3000.00\"},{\"item_id\":\"285\",\"quantity\":\"1\",\"unit_price\":\"12000.00\"}]', '2025-08-11 19:07:49', NULL),
(15, 7, 'Grade 2', '[{\"item_id\":\"304\",\"quantity\":\"1\",\"unit_price\":\"2900.00\"},{\"item_id\":\"292\",\"quantity\":\"1\",\"unit_price\":\"5075.00\"},{\"item_id\":\"310\",\"quantity\":\"1\",\"unit_price\":\"2175.00\"},{\"item_id\":\"316\",\"quantity\":\"1\",\"unit_price\":\"725.00\"},{\"item_id\":\"286\",\"quantity\":\"1\",\"unit_price\":\"14500.00\"},{\"item_id\":\"280\",\"quantity\":\"1\",\"unit_price\":\"43500.00\"},{\"item_id\":\"298\",\"quantity\":\"1\",\"unit_price\":\"6525.00\"}]', '2025-08-11 19:08:42', NULL),
(16, 7, 'Grade 3', '[{\"item_id\":\"305\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"3200.00\"},{\"item_id\":\"293\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"5600.00\"},{\"item_id\":\"311\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2400.00\"},{\"item_id\":\"317\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"800.00\"},{\"item_id\":\"287\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"16000.00\"},{\"item_id\":\"281\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"48000.00\"},{\"item_id\":\"299\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"7200.00\"}]', '2025-08-11 19:09:25', NULL),
(17, 7, 'Grade 4', '[{\"item_id\":\"306\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"3500.00\"},{\"item_id\":\"294\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"6200.00\"},{\"item_id\":\"312\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2600.00\"},{\"item_id\":\"318\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"900.00\"},{\"item_id\":\"288\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"17600.00\"},{\"item_id\":\"282\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"52800.00\"},{\"item_id\":\"300\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"7900.00\"}]', '2025-08-11 19:10:19', NULL),
(18, 7, 'Grade 5', '[{\"item_id\":\"307\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"3900.00\"},{\"item_id\":\"295\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"6800.00\"},{\"item_id\":\"313\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"2900.00\"},{\"item_id\":\"319\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"1000.00\"},{\"item_id\":\"289\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"19400.00\"},{\"item_id\":\"283\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"58100.00\"},{\"item_id\":\"301\",\"description\":\"\",\"quantity\":\"1\",\"unit_price\":\"8700.00\"}]', '2025-08-11 19:11:35', NULL),
(19, 8, 'Grade 1', '[{\"item_id\":\"345\",\"quantity\":\"1\",\"unit_price\":\"3000.00\"},{\"item_id\":\"333\",\"quantity\":\"1\",\"unit_price\":\"4500.00\"},{\"item_id\":\"351\",\"quantity\":\"1\",\"unit_price\":\"2500.00\"},{\"item_id\":\"357\",\"quantity\":\"1\",\"unit_price\":\"1000.00\"},{\"item_id\":\"327\",\"quantity\":\"1\",\"unit_price\":\"12000.00\"},{\"item_id\":\"321\",\"quantity\":\"1\",\"unit_price\":\"40000.00\"},{\"item_id\":\"339\",\"quantity\":\"1\",\"unit_price\":\"5500.00\"}]', '2025-08-12 08:59:41', NULL),
(23, 11, 'Grade 1', '[{\"item_id\":\"410\",\"description\":\"Diary (BB, Beginner)\",\"quantity\":\"1\",\"unit_price\":\"500.00\"},{\"item_id\":\"414\",\"description\":\"Tuition\",\"quantity\":\"1\",\"unit_price\":\"38500.00\"},{\"item_id\":\"415\",\"description\":\"LUNCH & BREAK\",\"quantity\":\"1\",\"unit_price\":\"10000.00\"},{\"item_id\":\"418\",\"description\":\"One-way transport for Delta, Ruaka town, Joyland.\",\"quantity\":\"1\",\"unit_price\":\"10000\"}]', '2025-09-03 22:56:24', 80);

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

CREATE TABLE `items` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `fee_frequency` enum('recurring','one_time','annual') NOT NULL DEFAULT 'recurring',
  `applies_to` enum('all','new_students_only','existing_students_only') DEFAULT 'all',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `items`
--

INSERT INTO `items` (`id`, `school_id`, `name`, `description`, `fee_frequency`, `applies_to`, `created_at`) VALUES
(1, 1, 'Tuition', '', 'recurring', 'all', '2025-04-07 21:37:47'),
(2, 1, 'Tuition Grade 2', '', 'recurring', 'all', '2025-04-07 21:38:16'),
(3, 1, 'Tuition Grade 1', '', 'recurring', 'all', '2025-04-07 21:38:31'),
(4, 1, 'Meal', '', 'recurring', 'all', '2025-04-07 21:38:58'),
(5, 1, 'Meal Grade 1', '', 'recurring', 'all', '2025-04-07 21:39:40'),
(6, 1, 'Meal Grade 2', '', 'recurring', 'all', '2025-04-07 21:39:59'),
(7, 1, 'Meal Grade 3', '', 'recurring', 'all', '2025-04-13 16:40:03'),
(8, 1, 'Swimming/Music/Sports', '', 'recurring', 'all', '2025-04-13 20:27:52'),
(9, 1, 'Swimming/Music/Sports Grade 1', '', 'recurring', 'all', '2025-04-13 20:30:03'),
(10, 1, 'Swimming/Music/Sports Grade 2', '', 'recurring', 'all', '2025-04-13 20:30:16'),
(11, 1, 'Swimming/Music/Sports Grade 3', '', 'recurring', 'all', '2025-04-13 20:30:25'),
(12, 1, 'Trips', '', 'recurring', 'all', '2025-05-06 10:59:50'),
(14, 1, 'Books', '', 'recurring', 'all', '2025-05-06 11:07:46'),
(15, 1, 'Grade 3 Books', '', 'recurring', 'all', '2025-05-06 11:19:39'),
(16, 1, 'Grade 2 Books', '', 'recurring', 'all', '2025-05-06 11:19:54'),
(17, 1, 'Grade 1 Books', '', 'recurring', 'all', '2025-05-06 11:20:32'),
(18, 1, 'Tuition Grade 3', '', 'recurring', 'all', '2025-05-06 11:29:32'),
(19, 1, 'Transport', '', 'recurring', 'all', '2025-05-06 12:01:30'),
(20, 1, 'Transport Zone A ', '', 'recurring', 'all', '2025-05-06 12:02:56'),
(21, 1, 'Transport Zone B', '', 'recurring', 'all', '2025-05-06 12:03:11'),
(22, 1, 'Transport Zone C', '', 'recurring', 'all', '2025-05-06 12:03:31'),
(23, 1, 'Transport Zone D', '', 'recurring', 'all', '2025-05-06 12:03:41'),
(24, 1, 'Transport Zone E', '', 'recurring', 'all', '2025-05-06 12:04:10'),
(25, 1, 'Transport Zone F', '', 'recurring', 'all', '2025-05-06 12:04:36'),
(26, 1, 'Skating', '', 'recurring', 'all', '2025-05-21 16:08:56'),
(27, 1, 'Abacus', '', 'recurring', 'all', '2025-06-15 17:32:54'),
(28, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:00'),
(29, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:03'),
(30, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:03'),
(31, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:04'),
(32, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:04'),
(33, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:04'),
(34, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:04'),
(35, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:05'),
(36, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:05'),
(37, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:05'),
(38, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:06'),
(39, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:08'),
(40, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:08'),
(41, 1, 'Insurance (Personal Accident)', '', 'recurring', 'all', '2025-06-30 12:26:08'),
(42, 1, 'Grade 4 tuition', '', 'recurring', 'all', '2025-06-30 12:29:13'),
(43, 2, 'Tution', '', 'recurring', 'all', '2025-07-01 15:35:26'),
(44, 2, 'Grade 1', '', 'recurring', 'all', '2025-07-01 15:36:32'),
(45, 2, 'Meal', '', 'recurring', 'all', '2025-07-01 23:30:59'),
(46, 2, 'Grade 1', '', 'recurring', 'all', '2025-07-01 23:31:56'),
(47, 2, 'Grade 2', '', 'recurring', 'all', '2025-07-01 23:32:40'),
(48, 2, 'Grade 3', '', 'recurring', 'all', '2025-07-01 23:34:39'),
(49, 2, 'Grade 2', '', 'recurring', 'all', '2025-07-01 23:36:35'),
(50, 2, 'Grade 3', '', 'recurring', 'all', '2025-07-01 23:37:46'),
(51, 2, 'Activity', '', 'recurring', 'all', '2025-07-01 23:53:14'),
(52, 2, 'Swimming/Music/Sports', '', 'recurring', 'all', '2025-07-01 23:54:54'),
(53, 2, 'Skating', '', 'recurring', 'all', '2025-07-01 23:55:13'),
(54, 2, 'Creative Arts', '', 'recurring', 'all', '2025-07-01 23:55:32'),
(55, 2, 'Chess', '', 'recurring', 'all', '2025-07-01 23:56:30'),
(56, 3, 'Tution', '', 'recurring', 'all', '2025-07-09 11:23:33'),
(57, 3, 'Meal', '', 'recurring', 'all', '2025-07-09 11:23:50'),
(118, 4, 'TUITION', NULL, 'recurring', 'all', '2025-07-10 15:46:04'),
(119, 4, 'PP1', 'Fee for TUITION (PP1)', 'recurring', 'all', '2025-07-10 15:46:04'),
(120, 4, 'PP2', 'Fee for TUITION (PP2)', 'recurring', 'all', '2025-07-10 15:46:04'),
(121, 4, 'GRADE 1', 'Fee for TUITION (GRADE 1)', 'recurring', 'all', '2025-07-10 15:46:04'),
(122, 4, 'GRADE 2', 'Fee for TUITION (GRADE 2)', 'recurring', 'all', '2025-07-10 15:46:04'),
(123, 4, 'GRADE 3', 'Fee for TUITION (GRADE 3)', 'recurring', 'all', '2025-07-10 15:46:04'),
(124, 4, 'LUNCH & BREAK', NULL, 'recurring', 'all', '2025-07-10 15:46:04'),
(125, 4, 'PP1', 'Fee for LUNCH & BREAK (PP1)', 'recurring', 'all', '2025-07-10 15:46:04'),
(126, 4, 'PP2', 'Fee for LUNCH & BREAK (PP2)', 'recurring', 'all', '2025-07-10 15:46:04'),
(127, 4, 'GRADE 1', 'Fee for LUNCH & BREAK (GRADE 1)', 'recurring', 'all', '2025-07-10 15:46:04'),
(128, 4, 'GRADE 2', 'Fee for LUNCH & BREAK (GRADE 2)', 'recurring', 'all', '2025-07-10 15:46:04'),
(129, 4, 'GRADE 3', 'Fee for LUNCH & BREAK (GRADE 3)', 'recurring', 'all', '2025-07-10 15:46:04'),
(130, 4, 'SPORTS/SWIMMING /KINDER MUSIC', NULL, 'recurring', 'all', '2025-07-10 15:46:04'),
(131, 4, 'PP1', 'Fee for SPORTS/SWIMMING /KINDER MUSIC (PP1)', 'recurring', 'all', '2025-07-10 15:46:04'),
(132, 4, 'PP2', 'Fee for SPORTS/SWIMMING /KINDER MUSIC (PP2)', 'recurring', 'all', '2025-07-10 15:46:04'),
(133, 4, 'GRADE 1', 'Fee for SPORTS/SWIMMING /KINDER MUSIC (GRADE 1)', 'recurring', 'all', '2025-07-10 15:46:04'),
(134, 4, 'GRADE 2', 'Fee for SPORTS/SWIMMING /KINDER MUSIC (GRADE 2)', 'recurring', 'all', '2025-07-10 15:46:04'),
(135, 4, 'GRADE 3', 'Fee for SPORTS/SWIMMING /KINDER MUSIC (GRADE 3)', 'recurring', 'all', '2025-07-10 15:46:04'),
(187, 5, 'Tuition', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(188, 5, 'PLAYGROUP', 'Fee for Tuition (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(189, 5, 'PP1', 'Fee for Tuition (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(190, 5, 'PP2', 'Fee for Tuition (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(191, 5, 'GRADE 1', 'Fee for Tuition (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(192, 5, 'GRADE 2', 'Fee for Tuition (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(193, 5, 'GRADE 3', 'Fee for Tuition (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(194, 5, 'Lunch', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(195, 5, 'PLAYGROUP', 'Fee for Lunch (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(196, 5, 'PP1', 'Fee for Lunch (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(197, 5, 'PP2', 'Fee for Lunch (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(198, 5, 'GRADE 1', 'Fee for Lunch (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(199, 5, 'GRADE 2', 'Fee for Lunch (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(200, 5, 'GRADE 3', 'Fee for Lunch (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(201, 5, 'Books & Stationery', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(202, 5, 'PLAYGROUP', 'Fee for Books & Stationery (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(203, 5, 'PP1', 'Fee for Books & Stationery (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(204, 5, 'PP2', 'Fee for Books & Stationery (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(205, 5, 'GRADE 1', 'Fee for Books & Stationery (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(206, 5, 'GRADE 2', 'Fee for Books & Stationery (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(207, 5, 'GRADE 3', 'Fee for Books & Stationery (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(208, 5, 'Uniform', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(209, 5, 'PLAYGROUP', 'Fee for Uniform (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(210, 5, 'PP1', 'Fee for Uniform (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(211, 5, 'PP2', 'Fee for Uniform (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(212, 5, 'GRADE 1', 'Fee for Uniform (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(213, 5, 'GRADE 2', 'Fee for Uniform (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(214, 5, 'GRADE 3', 'Fee for Uniform (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(215, 5, 'Activity Fee', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(216, 5, 'PLAYGROUP', 'Fee for Activity Fee (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(217, 5, 'PP1', 'Fee for Activity Fee (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(218, 5, 'PP2', 'Fee for Activity Fee (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(219, 5, 'GRADE 1', 'Fee for Activity Fee (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(220, 5, 'GRADE 2', 'Fee for Activity Fee (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(221, 5, 'GRADE 3', 'Fee for Activity Fee (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(222, 5, 'Development Levy', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(223, 5, 'PLAYGROUP', 'Fee for Development Levy (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(224, 5, 'PP1', 'Fee for Development Levy (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(225, 5, 'PP2', 'Fee for Development Levy (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(226, 5, 'GRADE 1', 'Fee for Development Levy (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(227, 5, 'GRADE 2', 'Fee for Development Levy (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(228, 5, 'GRADE 3', 'Fee for Development Levy (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(229, 5, 'Insurance', NULL, 'recurring', 'all', '2025-07-14 15:42:33'),
(230, 5, 'PLAYGROUP', 'Fee for Insurance (PLAYGROUP)', 'recurring', 'all', '2025-07-14 15:42:33'),
(231, 5, 'PP1', 'Fee for Insurance (PP1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(232, 5, 'PP2', 'Fee for Insurance (PP2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(233, 5, 'GRADE 1', 'Fee for Insurance (GRADE 1)', 'recurring', 'all', '2025-07-14 15:42:33'),
(234, 5, 'GRADE 2', 'Fee for Insurance (GRADE 2)', 'recurring', 'all', '2025-07-14 15:42:33'),
(235, 5, 'GRADE 3', 'Fee for Insurance (GRADE 3)', 'recurring', 'all', '2025-07-14 15:42:33'),
(236, 6, 'Tuition', NULL, 'recurring', 'all', '2025-07-17 01:01:19'),
(237, 6, 'GRADE 1', 'Fee for Tuition (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:19'),
(238, 6, 'GRADE 2', 'Fee for Tuition (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:19'),
(239, 6, 'GRADE 3', 'Fee for Tuition (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:19'),
(240, 6, 'GRADE 4', 'Fee for Tuition (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:19'),
(241, 6, 'GRADE 5', 'Fee for Tuition (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:19'),
(242, 6, 'Lunch', NULL, 'recurring', 'all', '2025-07-17 01:01:19'),
(243, 6, 'GRADE 1', 'Fee for Lunch (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:19'),
(244, 6, 'GRADE 2', 'Fee for Lunch (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:19'),
(245, 6, 'GRADE 3', 'Fee for Lunch (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:19'),
(246, 6, 'GRADE 4', 'Fee for Lunch (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:19'),
(247, 6, 'GRADE 5', 'Fee for Lunch (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:19'),
(248, 6, 'Books & Stationery', NULL, 'recurring', 'all', '2025-07-17 01:01:19'),
(249, 6, 'GRADE 1', 'Fee for Books & Stationery (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:19'),
(250, 6, 'GRADE 2', 'Fee for Books & Stationery (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:19'),
(251, 6, 'GRADE 3', 'Fee for Books & Stationery (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:20'),
(252, 6, 'GRADE 4', 'Fee for Books & Stationery (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:20'),
(253, 6, 'GRADE 5', 'Fee for Books & Stationery (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:20'),
(254, 6, 'Uniform', NULL, 'recurring', 'all', '2025-07-17 01:01:20'),
(255, 6, 'GRADE 1', 'Fee for Uniform (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:20'),
(256, 6, 'GRADE 2', 'Fee for Uniform (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:20'),
(257, 6, 'GRADE 3', 'Fee for Uniform (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:20'),
(258, 6, 'GRADE 4', 'Fee for Uniform (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:20'),
(259, 6, 'GRADE 5', 'Fee for Uniform (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:20'),
(260, 6, 'Activity Fee', NULL, 'recurring', 'all', '2025-07-17 01:01:20'),
(261, 6, 'GRADE 1', 'Fee for Activity Fee (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:20'),
(262, 6, 'GRADE 2', 'Fee for Activity Fee (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:20'),
(263, 6, 'GRADE 3', 'Fee for Activity Fee (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:20'),
(264, 6, 'GRADE 4', 'Fee for Activity Fee (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:20'),
(265, 6, 'GRADE 5', 'Fee for Activity Fee (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:20'),
(266, 6, 'Development Levy', NULL, 'recurring', 'all', '2025-07-17 01:01:20'),
(267, 6, 'GRADE 1', 'Fee for Development Levy (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:20'),
(268, 6, 'GRADE 2', 'Fee for Development Levy (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:20'),
(269, 6, 'GRADE 3', 'Fee for Development Levy (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:20'),
(270, 6, 'GRADE 4', 'Fee for Development Levy (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:20'),
(271, 6, 'GRADE 5', 'Fee for Development Levy (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:20'),
(272, 6, 'Insurance', NULL, 'recurring', 'all', '2025-07-17 01:01:20'),
(273, 6, 'GRADE 1', 'Fee for Insurance (GRADE 1)', 'recurring', 'all', '2025-07-17 01:01:20'),
(274, 6, 'GRADE 2', 'Fee for Insurance (GRADE 2)', 'recurring', 'all', '2025-07-17 01:01:20'),
(275, 6, 'GRADE 3', 'Fee for Insurance (GRADE 3)', 'recurring', 'all', '2025-07-17 01:01:20'),
(276, 6, 'GRADE 4', 'Fee for Insurance (GRADE 4)', 'recurring', 'all', '2025-07-17 01:01:20'),
(277, 6, 'GRADE 5', 'Fee for Insurance (GRADE 5)', 'recurring', 'all', '2025-07-17 01:01:20'),
(278, 7, 'Tuition', NULL, 'recurring', 'all', '2025-08-11 18:22:30'),
(279, 7, 'GRADE 1', 'Fee for Tuition (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:30'),
(280, 7, 'GRADE 2', 'Fee for Tuition (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:30'),
(281, 7, 'GRADE 3', 'Fee for Tuition (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:30'),
(282, 7, 'GRADE 4', 'Fee for Tuition (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:30'),
(283, 7, 'GRADE 5', 'Fee for Tuition (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(284, 7, 'Lunch', NULL, 'recurring', 'all', '2025-08-11 18:22:31'),
(285, 7, 'GRADE 1', 'Fee for Lunch (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:31'),
(286, 7, 'GRADE 2', 'Fee for Lunch (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:31'),
(287, 7, 'GRADE 3', 'Fee for Lunch (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:31'),
(288, 7, 'GRADE 4', 'Fee for Lunch (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:31'),
(289, 7, 'GRADE 5', 'Fee for Lunch (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(290, 7, 'Books & Stationery', NULL, 'recurring', 'all', '2025-08-11 18:22:31'),
(291, 7, 'GRADE 1', 'Fee for Books & Stationery (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:31'),
(292, 7, 'GRADE 2', 'Fee for Books & Stationery (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:31'),
(293, 7, 'GRADE 3', 'Fee for Books & Stationery (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:31'),
(294, 7, 'GRADE 4', 'Fee for Books & Stationery (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:31'),
(295, 7, 'GRADE 5', 'Fee for Books & Stationery (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(296, 7, 'Uniform', NULL, 'recurring', 'all', '2025-08-11 18:22:31'),
(297, 7, 'GRADE 1', 'Fee for Uniform (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:31'),
(298, 7, 'GRADE 2', 'Fee for Uniform (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:31'),
(299, 7, 'GRADE 3', 'Fee for Uniform (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:31'),
(300, 7, 'GRADE 4', 'Fee for Uniform (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:31'),
(301, 7, 'GRADE 5', 'Fee for Uniform (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(302, 7, 'Activity Fee', NULL, 'recurring', 'all', '2025-08-11 18:22:31'),
(303, 7, 'GRADE 1', 'Fee for Activity Fee (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:31'),
(304, 7, 'GRADE 2', 'Fee for Activity Fee (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:31'),
(305, 7, 'GRADE 3', 'Fee for Activity Fee (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:31'),
(306, 7, 'GRADE 4', 'Fee for Activity Fee (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:31'),
(307, 7, 'GRADE 5', 'Fee for Activity Fee (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(308, 7, 'Development Levy', NULL, 'recurring', 'all', '2025-08-11 18:22:31'),
(309, 7, 'GRADE 1', 'Fee for Development Levy (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:31'),
(310, 7, 'GRADE 2', 'Fee for Development Levy (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:31'),
(311, 7, 'GRADE 3', 'Fee for Development Levy (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:31'),
(312, 7, 'GRADE 4', 'Fee for Development Levy (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:31'),
(313, 7, 'GRADE 5', 'Fee for Development Levy (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(314, 7, 'Insurance', NULL, 'recurring', 'all', '2025-08-11 18:22:31'),
(315, 7, 'GRADE 1', 'Fee for Insurance (GRADE 1)', 'recurring', 'all', '2025-08-11 18:22:31'),
(316, 7, 'GRADE 2', 'Fee for Insurance (GRADE 2)', 'recurring', 'all', '2025-08-11 18:22:31'),
(317, 7, 'GRADE 3', 'Fee for Insurance (GRADE 3)', 'recurring', 'all', '2025-08-11 18:22:31'),
(318, 7, 'GRADE 4', 'Fee for Insurance (GRADE 4)', 'recurring', 'all', '2025-08-11 18:22:31'),
(319, 7, 'GRADE 5', 'Fee for Insurance (GRADE 5)', 'recurring', 'all', '2025-08-11 18:22:31'),
(320, 8, 'Tuition', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(321, 8, 'GRADE 1', 'Fee for Tuition (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(322, 8, 'GRADE 2', 'Fee for Tuition (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(323, 8, 'GRADE 3', 'Fee for Tuition (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(324, 8, 'GRADE 4', 'Fee for Tuition (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(325, 8, 'GRADE 5', 'Fee for Tuition (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(326, 8, 'Lunch', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(327, 8, 'GRADE 1', 'Fee for Lunch (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(328, 8, 'GRADE 2', 'Fee for Lunch (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(329, 8, 'GRADE 3', 'Fee for Lunch (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(330, 8, 'GRADE 4', 'Fee for Lunch (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(331, 8, 'GRADE 5', 'Fee for Lunch (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(332, 8, 'Books & Stationery', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(333, 8, 'GRADE 1', 'Fee for Books & Stationery (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(334, 8, 'GRADE 2', 'Fee for Books & Stationery (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(335, 8, 'GRADE 3', 'Fee for Books & Stationery (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(336, 8, 'GRADE 4', 'Fee for Books & Stationery (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(337, 8, 'GRADE 5', 'Fee for Books & Stationery (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(338, 8, 'Uniform', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(339, 8, 'GRADE 1', 'Fee for Uniform (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(340, 8, 'GRADE 2', 'Fee for Uniform (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(341, 8, 'GRADE 3', 'Fee for Uniform (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(342, 8, 'GRADE 4', 'Fee for Uniform (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(343, 8, 'GRADE 5', 'Fee for Uniform (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(344, 8, 'Activity Fee', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(345, 8, 'GRADE 1', 'Fee for Activity Fee (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(346, 8, 'GRADE 2', 'Fee for Activity Fee (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(347, 8, 'GRADE 3', 'Fee for Activity Fee (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(348, 8, 'GRADE 4', 'Fee for Activity Fee (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(349, 8, 'GRADE 5', 'Fee for Activity Fee (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(350, 8, 'Development Levy', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(351, 8, 'GRADE 1', 'Fee for Development Levy (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(352, 8, 'GRADE 2', 'Fee for Development Levy (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(353, 8, 'GRADE 3', 'Fee for Development Levy (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(354, 8, 'GRADE 4', 'Fee for Development Levy (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(355, 8, 'GRADE 5', 'Fee for Development Levy (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(356, 8, 'Insurance', NULL, 'recurring', 'all', '2025-08-12 11:58:11'),
(357, 8, 'GRADE 1', 'Fee for Insurance (GRADE 1)', 'recurring', 'all', '2025-08-12 11:58:11'),
(358, 8, 'GRADE 2', 'Fee for Insurance (GRADE 2)', 'recurring', 'all', '2025-08-12 11:58:11'),
(359, 8, 'GRADE 3', 'Fee for Insurance (GRADE 3)', 'recurring', 'all', '2025-08-12 11:58:11'),
(360, 8, 'GRADE 4', 'Fee for Insurance (GRADE 4)', 'recurring', 'all', '2025-08-12 11:58:11'),
(361, 8, 'GRADE 5', 'Fee for Insurance (GRADE 5)', 'recurring', 'all', '2025-08-12 11:58:11'),
(362, 9, 'Tuition', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(363, 9, 'GRADE 1', 'Fee for Tuition (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(364, 9, 'GRADE 2', 'Fee for Tuition (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(365, 9, 'GRADE 3', 'Fee for Tuition (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(366, 9, 'GRADE 4', 'Fee for Tuition (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(367, 9, 'GRADE 5', 'Fee for Tuition (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(368, 9, 'Lunch', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(369, 9, 'GRADE 1', 'Fee for Lunch (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(370, 9, 'GRADE 2', 'Fee for Lunch (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(371, 9, 'GRADE 3', 'Fee for Lunch (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(372, 9, 'GRADE 4', 'Fee for Lunch (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(373, 9, 'GRADE 5', 'Fee for Lunch (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(374, 9, 'Books & Stationery', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(375, 9, 'GRADE 1', 'Fee for Books & Stationery (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(376, 9, 'GRADE 2', 'Fee for Books & Stationery (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(377, 9, 'GRADE 3', 'Fee for Books & Stationery (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(378, 9, 'GRADE 4', 'Fee for Books & Stationery (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(379, 9, 'GRADE 5', 'Fee for Books & Stationery (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(380, 9, 'Uniform', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(381, 9, 'GRADE 1', 'Fee for Uniform (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(382, 9, 'GRADE 2', 'Fee for Uniform (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(383, 9, 'GRADE 3', 'Fee for Uniform (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(384, 9, 'GRADE 4', 'Fee for Uniform (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(385, 9, 'GRADE 5', 'Fee for Uniform (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(386, 9, 'Activity Fee', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(387, 9, 'GRADE 1', 'Fee for Activity Fee (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(388, 9, 'GRADE 2', 'Fee for Activity Fee (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(389, 9, 'GRADE 3', 'Fee for Activity Fee (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(390, 9, 'GRADE 4', 'Fee for Activity Fee (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(391, 9, 'GRADE 5', 'Fee for Activity Fee (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(392, 9, 'Development Levy', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(393, 9, 'GRADE 1', 'Fee for Development Levy (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(394, 9, 'GRADE 2', 'Fee for Development Levy (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(395, 9, 'GRADE 3', 'Fee for Development Levy (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(396, 9, 'GRADE 4', 'Fee for Development Levy (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(397, 9, 'GRADE 5', 'Fee for Development Levy (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(398, 9, 'Insurance', NULL, 'recurring', 'all', '2025-08-12 14:02:39'),
(399, 9, 'GRADE 1', 'Fee for Insurance (GRADE 1)', 'recurring', 'all', '2025-08-12 14:02:39'),
(400, 9, 'GRADE 2', 'Fee for Insurance (GRADE 2)', 'recurring', 'all', '2025-08-12 14:02:39'),
(401, 9, 'GRADE 3', 'Fee for Insurance (GRADE 3)', 'recurring', 'all', '2025-08-12 14:02:39'),
(402, 9, 'GRADE 4', 'Fee for Insurance (GRADE 4)', 'recurring', 'all', '2025-08-12 14:02:39'),
(403, 9, 'GRADE 5', 'Fee for Insurance (GRADE 5)', 'recurring', 'all', '2025-08-12 14:02:39'),
(404, 10, 'Tuition', '', 'recurring', 'all', '2025-08-26 16:52:36'),
(405, 10, 'Ruaka', '', 'recurring', 'all', '2025-08-26 16:54:45'),
(406, 9, 'Tuition Fee', '', 'recurring', 'all', '2025-08-28 14:23:57'),
(407, 9, 'Transport Fees', '', 'recurring', 'all', '2025-08-28 14:39:31'),
(408, 11, 'Admission Fee', 'One-time fee for new students upon admission.', 'recurring', 'all', '2025-08-28 18:11:10'),
(409, 11, 'Personal Accident Insuarance', 'Annual insurance coverage for students.', 'recurring', 'all', '2025-08-28 18:11:10'),
(410, 11, 'Diary (BB, Beginner)', 'Student diary for Daycare and Beginner classes.', 'recurring', 'all', '2025-08-28 18:11:10'),
(411, 11, 'Diary (PP1 - GR3)', 'Student diary for PP1 through Grade 3 classes.', 'recurring', 'all', '2025-08-28 18:11:10'),
(412, 11, 'Pouch', 'School branded pouch for materials.', 'recurring', 'all', '2025-08-28 18:11:10'),
(413, 11, 'Covers', 'Protective covers for books.', 'recurring', 'all', '2025-08-28 18:11:10'),
(414, 11, 'Tuition', 'Core termly tuition fee.', 'recurring', 'all', '2025-08-28 18:11:10'),
(415, 11, 'LUNCH & BREAK', 'Termly fee for school meals.', 'recurring', 'all', '2025-08-28 18:11:10'),
(416, 11, 'SPORTS/SWIMMING /KINDER MUSIC', 'Combined activity fee for kindergarten levels.', 'recurring', 'all', '2025-08-28 18:11:10'),
(417, 11, 'Transport - ZONE 1 (Round Trip)', 'Transport for Delta, Ruaka town, Joyland.', 'recurring', 'all', '2025-08-28 18:11:10'),
(418, 11, 'Transport - ZONE 1 (One Way)', 'One-way transport for Delta, Ruaka town, Joyland.', 'recurring', 'all', '2025-08-28 18:11:10'),
(419, 11, 'Transport - ZONE 2 (Round Trip)', 'Transport for Mifereji, Kahigo, Guango, Kigwaru.', 'recurring', 'all', '2025-08-28 18:11:10'),
(420, 11, 'Transport - ZONE 2 (One Way)', 'One-way transport for Mifereji, Kahigo, Guango, Kigwaru.', 'recurring', 'all', '2025-08-28 18:11:10'),
(421, 11, 'Transport - ZONE 3 (Round Trip)', 'Transport for Muchatha, Clifftop Sacred heart, Ndenderu.', 'recurring', 'all', '2025-08-28 18:11:10'),
(422, 11, 'Transport - ZONE 3 (One Way)', 'One-way transport for Muchatha, Clifftop Sacred heart, Ndenderu.', 'recurring', 'all', '2025-08-28 18:11:10'),
(423, 11, 'Transport - ZONE 4 (Round Trip)', 'Transport for Runda, Gigiri, Banana, Karura, Gachie.', 'recurring', 'all', '2025-08-28 18:11:10'),
(424, 11, 'Transport - ZONE 4 (One Way)', 'One-way transport for Runda, Gigiri, Banana, Karura, Gachie.', 'recurring', 'all', '2025-08-28 18:11:10'),
(425, 11, 'Transport - ZONE 5 (Round Trip)', 'Transport for Kiambu, Marurui, Kihara, Laini Ridgeways.', 'recurring', 'all', '2025-08-28 18:11:10'),
(426, 11, 'Transport - ZONE 5 (One Way)', 'One-way transport for Kiambu, Marurui, Kihara, Laini Ridgeways.', 'recurring', 'all', '2025-08-28 18:11:10'),
(427, 11, 'Transport - ZONE 6 (Round Trip)', 'Transport for Redhill, Nazareth, Windsor.', 'recurring', 'all', '2025-08-28 18:11:10'),
(428, 11, 'Transport - ZONE 6 (One Way)', 'One-way transport for Redhill, Nazareth, Windsor.', 'recurring', 'all', '2025-08-28 18:11:10'),
(429, 11, 'Transport - ZONE 7 (Round Trip)', 'Transport for Lower Kabete, Mwimuto, Kitsuru.', 'recurring', 'all', '2025-08-28 18:11:10'),
(430, 11, 'Transport - ZONE 7 (One Way)', 'One-way transport for Lower Kabete, Mwimuto, Kitsuru.', 'recurring', 'all', '2025-08-28 18:11:10'),
(431, 11, 'Transport - ZONE 8 (Round Trip)', 'Transport for Poster Kabuku, Kiambu town.', 'recurring', 'all', '2025-08-28 18:11:10'),
(432, 11, 'Transport - ZONE 8 (One Way)', 'One-way transport for Poster Kabuku, Kiambu town.', 'recurring', 'all', '2025-08-28 18:11:10'),
(433, 11, 'Skating', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(434, 11, 'Ballet', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(435, 11, 'Taekwondo', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(436, 11, 'Chess', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(437, 11, 'Music', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(438, 11, 'Languages', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(439, 11, 'Basketball', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(440, 11, 'Gymnastics', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(441, 11, 'Swimming', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(442, 11, 'Sports', 'Optional activity fee per term.', 'recurring', 'all', '2025-08-28 18:11:10'),
(443, 11, 'Abacus', 'Optional activity for Grades 1-3.', 'recurring', 'all', '2025-08-28 18:11:10'),
(444, 11, 'Balance Brought Forward', 'An automatically generated item to carry over outstanding balances.', 'recurring', 'all', '2025-09-17 02:42:40'),
(445, 12, 'Tuition', '', 'recurring', 'all', '2025-11-30 04:10:08'),
(446, 12, 'Tuition', '', 'recurring', 'all', '2025-11-30 04:10:45'),
(447, 12, 'Tuition', '', 'recurring', 'all', '2025-11-30 04:10:45'),
(448, 7, 'Transport', 'Zone A', 'recurring', 'all', '2026-01-01 19:21:44');

-- --------------------------------------------------------

--
-- Table structure for table `journal_details`
--

CREATE TABLE `journal_details` (
  `id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(10,2) DEFAULT 0.00,
  `credit` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_details`
--

INSERT INTO `journal_details` (`id`, `journal_id`, `account_id`, `debit`, `credit`) VALUES
(1, 3, 2, 50000.00, 0.00),
(2, 3, 3, 0.00, 50000.00),
(3, 4, 2, 0.00, 100000.00),
(4, 4, 3, 100000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `journal_entries`
--

CREATE TABLE `journal_entries` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `entry_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `reference` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `journal_entries`
--

INSERT INTO `journal_entries` (`id`, `school_id`, `entry_date`, `description`, `reference`, `created_at`) VALUES
(3, 1, '2025-04-28', 'Petty cash replenishment', NULL, '2025-04-28 19:36:57'),
(4, 1, '2025-04-28', 'Petty cash replenishment', NULL, '2025-04-28 19:37:50');

-- --------------------------------------------------------

--
-- Table structure for table `journal_lines`
--

CREATE TABLE `journal_lines` (
  `id` int(11) NOT NULL,
  `journal_id` int(11) NOT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `mpesa_transactions`
--

CREATE TABLE `mpesa_transactions` (
  `id` int(11) NOT NULL,
  `transaction_id` varchar(20) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `raw_data` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `mpesa_transactions`
--

INSERT INTO `mpesa_transactions` (`id`, `transaction_id`, `amount`, `student_id`, `raw_data`, `created_at`) VALUES
(1, 'QG912ABCDE', 500.00, 37, '{\n    \"TransactionType\": \"Pay Bill\",\n    \"TransID\": \"QG912ABCDE\",\n    \"TransTime\": \"20250709112000\",\n    \"TransAmount\": \"500.00\",\n    \"BusinessShortCode\": \"600984\",\n    \"BillRefNumber\": \"1234\",\n    \"InvoiceNumber\": \"\",\n    \"OrgAccountBalance\": \"\",\n    \"ThirdPartyTransID\": \"\",\n    \"MSISDN\": \"254700123456\",\n    \"FirstName\": \"John\",\n    \"MiddleName\": \"\",\n    \"LastName\": \"Doe\"\n  }', '2025-07-09 08:44:24'),
(2, 'QG912ABCDF', 50000.00, 37, '{\n    \"TransactionType\": \"Pay Bill\",\n    \"TransID\": \"QG912ABCDF\",\n    \"TransTime\": \"20250709112000\",\n    \"TransAmount\": \"50000.00\",\n    \"BusinessShortCode\": \"600984\",\n    \"BillRefNumber\": \"1234\",\n    \"InvoiceNumber\": \"\",\n    \"OrgAccountBalance\": \"\",\n    \"ThirdPartyTransID\": \"\",\n    \"MSISDN\": \"254700123456\",\n    \"FirstName\": \"William\",\n    \"MiddleName\": \"\",\n    \"LastName\": \"Macharia\"\n  }', '2025-07-09 08:56:47');

-- --------------------------------------------------------

--
-- Table structure for table `one_time_fees_billed`
--

CREATE TABLE `one_time_fees_billed` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) NOT NULL,
  `billed_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `memo` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `receipt_id` int(11) DEFAULT NULL,
  `coa_account_id` int(11) DEFAULT NULL,
  `deposit_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `school_id`, `invoice_id`, `student_id`, `payment_date`, `amount`, `payment_method`, `memo`, `created_at`, `receipt_id`, `coa_account_id`, `deposit_id`) VALUES
(2, 1, 2, 2, '0000-00-00', 40000.00, NULL, NULL, '2025-04-09 13:21:04', 5, NULL, NULL),
(3, 1, 1, 1, '0000-00-00', 10000.00, NULL, NULL, '2025-04-09 13:21:57', 6, NULL, NULL),
(7, 1, 5, 5, '2025-05-04', 30000.00, 'Mobile Money', '', '2025-05-04 19:33:41', NULL, NULL, NULL),
(9, 1, 6, 9, '2025-05-06', 47550.00, 'Cash', 'Paid half today to be cleared within the next 7 days', '2025-05-06 10:55:51', NULL, NULL, NULL),
(10, 1, 6, 9, '2025-05-06', 45750.00, 'Mobile Money', '', '2025-05-06 10:56:21', NULL, NULL, NULL),
(11, 1, 8, 13, '2025-05-06', 45790.00, 'Cash', '', '2025-05-06 11:28:04', NULL, NULL, NULL),
(12, 1, 10, 14, '2025-05-06', 75680.00, 'Mobile Money', 'Reciept No: 1760', '2025-05-06 12:18:19', NULL, NULL, NULL),
(13, 1, 11, 14, '2025-05-06', 57645.00, 'Mobile Money', 'Reciept No: 1760', '2025-05-06 12:18:19', NULL, NULL, NULL),
(16, 1, 12, 15, '2025-05-06', 10000.00, 'Cash', '', '2025-05-06 17:08:48', NULL, NULL, NULL),
(17, 1, 12, 15, '2025-05-06', 35450.00, 'Mobile Money', '', '2025-05-06 17:15:31', NULL, NULL, NULL),
(18, 1, 13, 16, '2025-05-06', 45250.00, 'Cash', '', '2025-05-06 17:49:48', NULL, NULL, NULL),
(19, 1, 15, 19, '2025-05-19', 24500.00, 'Mobile Money', '2345', '2025-05-19 22:12:39', NULL, NULL, NULL),
(20, 1, 15, 19, '2025-05-21', 32750.00, 'Cash', '', '2025-05-21 16:05:19', NULL, NULL, NULL),
(21, 1, 17, 21, '2025-05-21', 42950.00, 'Mobile Money', '', '2025-05-21 16:11:09', NULL, NULL, NULL),
(22, 1, 18, 22, '2025-05-22', 34500.00, 'Cash', '', '2025-05-22 12:59:43', NULL, NULL, NULL),
(23, 1, 5, 5, '2025-05-22', 27500.00, NULL, NULL, '2025-05-23 14:52:56', NULL, NULL, NULL),
(24, 1, 19, 23, '2025-05-27', 32000.00, 'Mobile Money', 'Receipt No: 9687', '2025-05-27 14:48:17', NULL, NULL, NULL),
(25, 1, 20, 24, '2025-05-27', 12500.00, 'Cash', 'Receipt No:1986', '2025-05-27 15:01:11', NULL, NULL, NULL),
(26, 1, 20, 24, '2025-05-27', 25000.00, 'Cash', '', '2025-05-27 22:11:58', NULL, NULL, NULL),
(27, 1, 20, 24, '2025-05-27', 12450.00, 'Cash', 'installment 3', '2025-05-27 22:14:43', NULL, NULL, NULL),
(28, 1, 20, 24, '2025-05-27', 5000.00, 'Cash', 'Receipt No. 171651', '2025-05-27 22:33:02', NULL, NULL, NULL),
(29, 1, 22, 26, '2025-06-03', 24500.00, 'Cash', '5679', '2025-06-03 14:55:04', NULL, NULL, NULL),
(30, 1, 22, 26, '2025-06-05', 12500.00, 'Mobile Money', '', '2025-06-05 12:57:10', NULL, NULL, NULL),
(31, 1, 22, 26, '2025-06-05', 10000.00, 'Cash', '', '2025-06-05 16:16:02', NULL, NULL, NULL),
(32, 1, 20, 24, '2025-06-05', 5500.00, 'Cash', '', '2025-06-05 23:34:47', NULL, NULL, NULL),
(33, 1, 3, 3, '2025-06-06', 24500.00, 'Bank Transfer', 'No:1001', '2025-06-06 20:25:08', NULL, NULL, NULL),
(34, 1, 15, 19, '2025-06-10', 25000.00, 'Bank Transfer', '', '2025-06-11 00:13:16', NULL, NULL, NULL),
(35, 1, 23, 27, '2025-06-10', 22450.00, 'Check', 'Receipt No; 2095', '2025-06-11 00:46:29', NULL, NULL, NULL),
(36, 1, 23, 27, '2025-06-10', 50000.00, 'Cash', '', '2025-06-11 00:49:08', NULL, NULL, NULL),
(37, 1, 24, 29, '2025-06-15', 27650.00, 'Mobile Money', 'Receipt No. 5894', '2025-06-15 02:13:55', NULL, NULL, NULL),
(38, 1, 24, 29, '2025-06-15', 5000.00, 'Cash', 'Receipt No.8796', '2025-06-15 02:33:31', NULL, NULL, NULL),
(39, 1, 19, 23, '2025-06-15', 5750.00, 'Cash', '', '2025-06-15 03:57:26', NULL, NULL, NULL),
(40, 1, 4, 4, '2025-06-15', 30000.00, 'Cash', '1st Payment', '2025-06-15 17:33:45', NULL, NULL, NULL),
(41, 1, 4, 4, '2025-06-30', 14500.00, 'Bank Transfer', '', '2025-06-30 13:01:13', NULL, NULL, NULL),
(42, 1, 17, 21, '2025-06-30', 10000.00, 'Cash', '', '2025-06-30 13:05:21', NULL, NULL, NULL),
(43, 1, 16, 20, '2025-06-30', 47500.00, 'Cash', '', '2025-06-30 13:15:56', 12, NULL, NULL),
(44, 1, 27, 31, '2025-06-30', 25750.00, 'Cash', '', '2025-06-30 15:20:33', 13, NULL, NULL),
(45, 2, 30, 34, '2025-07-01', 25000.00, 'Mobile Money', '', '2025-07-02 00:11:27', 14, NULL, NULL),
(46, 2, 30, 34, '2025-07-09', 15500.00, 'Cash', '', '2025-07-09 10:22:39', 15, 14, NULL),
(47, 2, 30, 34, '2025-07-09', 5000.00, 'Cash', '', '2025-07-09 10:25:40', 16, 14, NULL),
(48, 2, 30, 34, '2025-07-09', 5000.00, 'Cash', '', '2025-07-09 10:43:57', 17, 14, NULL),
(49, 3, 31, 37, '2025-07-09', 50000.00, 'M-Pesa', 'M-Pesa payment from William. Ref: QG912ABCDF', '2025-07-09 11:56:47', 19, 15, NULL),
(50, 3, 31, 37, '2025-07-09', 10000.00, 'Mobile Money', '', '2025-07-09 14:52:19', 20, 19, NULL),
(51, 4, 32, 39, '2025-07-09', 15500.00, 'Mobile Money', '', '2025-07-09 16:40:37', 21, 20, NULL),
(52, 4, 32, 39, '2025-07-09', 24500.00, 'Mobile Money', '', '2025-07-09 18:17:23', 22, 20, NULL),
(53, 4, 32, 39, '2025-07-10', 20000.00, 'Mobile Money', '', '2025-07-10 15:18:56', 23, 20, NULL),
(54, 5, 37, 59, '2025-07-15', 25750.00, 'Cash', '', '2025-07-15 16:58:39', 24, 22, 1),
(55, 5, 38, 66, '2025-07-15', 34500.00, 'Mobile Money', '', '2025-07-15 22:49:13', 25, 22, 1),
(56, 6, 75, 99, '2025-07-17', 75000.00, 'Mobile Money', '', '2025-07-17 01:12:10', 26, 27, 2),
(57, 6, 84, 100, '2025-07-17', 47500.00, 'Mobile Money', '', '2025-07-17 01:12:38', 27, 27, 2),
(58, 6, 50, 74, '2025-07-17', 35490.00, 'Bank Transfer', '', '2025-07-17 01:14:55', 28, 27, 2),
(59, 6, 50, 74, '2025-07-20', 15000.00, 'Bank Transfer', '', '2025-07-20 03:08:59', 29, 27, 3),
(60, 6, 75, 99, '2025-07-20', 5000.00, 'Cash', '', '2025-07-20 03:22:25', 30, 27, 4),
(61, 6, 41, 82, '2025-07-20', 32500.00, 'Mobile Money', '', '2025-07-20 03:25:00', 31, 27, 5),
(62, 6, 71, 77, '2025-07-20', 74500.00, 'Mobile Money', '', '2025-07-20 03:51:56', 32, 27, 6),
(63, 6, 56, 102, '2025-07-20', 25000.00, 'Cash', '', '2025-07-20 04:03:11', 33, 28, NULL),
(64, 6, 61, 75, '2025-07-25', 21300.00, 'Mobile Money', '', '2025-07-25 20:39:24', 34, 28, NULL),
(65, 6, 85, 105, '2025-07-25', 37500.00, 'Bank Transfer', '', '2025-07-25 23:16:16', 35, 28, NULL),
(66, 6, 84, 100, '2025-07-27', 25000.00, 'Bank Transfer', '', '2025-07-27 23:02:58', 36, 28, NULL),
(67, 6, 71, 77, '2025-08-03', 25000.00, 'Mobile Money', 'Ref No; GHO9876567OP', '2025-08-03 16:45:34', 37, 28, NULL),
(68, 6, 56, 102, '2025-08-03', 100000.00, 'Bank Transfer', '', '2025-08-03 16:54:57', 38, 28, NULL),
(69, 6, 50, 74, '2025-08-05', 25000.00, 'Cash', '', '2025-08-05 14:13:25', 39, 28, NULL),
(70, 6, 66, 98, '2025-08-05', 73500.00, 'Mobile Money', '', '2025-08-05 16:08:38', 40, 28, NULL),
(73, 7, 194, 123, '2025-08-12', 25000.00, 'Cash', '', '2025-08-12 11:32:58', 43, 44, NULL),
(74, 7, 181, 126, '2025-08-12', 22500.00, 'Mobile Money', '', '2025-08-12 11:51:41', 44, 44, NULL),
(75, 8, 214, 227, '2025-08-12', 25000.00, 'Cash', '', '2025-08-12 12:04:21', 45, 50, NULL),
(76, 9, 227, 327, '2025-08-12', 24500.00, 'Mobile Money', '', '2025-08-12 14:26:38', 46, 52, NULL),
(77, 9, 265, 377, '2025-08-13', 35500.00, 'Mobile Money', '', '2025-08-13 18:21:21', 47, 52, NULL),
(78, 9, 240, 324, '2025-08-18', 25500.00, 'Mobile Money', '', '2025-08-18 22:20:58', 48, 52, NULL),
(79, 6, 67, 103, '2025-08-26', 80000.00, 'Cash', '', '2025-08-26 21:54:45', 49, 27, 7),
(80, 11, 278, 432, '2025-08-30', 25400.00, 'Mobile Money', '', '2025-08-30 10:07:51', 50, 58, 8),
(81, 11, 280, 433, '2025-09-01', 22500.00, 'Cash', '', '2025-09-01 14:14:20', 51, 58, 9),
(82, 11, 281, 429, '2025-09-01', 23450.00, 'Bank Transfer', '', '2025-09-01 20:46:23', 52, 58, 9),
(83, 11, 279, 430, '2025-09-03', 30500.00, 'Cash', '', '2025-09-03 18:16:58', 53, 58, 9),
(84, 11, 278, 432, '2025-09-03', 5000.00, 'Mobile Money', '', '2025-09-03 18:24:09', 54, 58, 9),
(85, 6, 51, 78, '2025-09-03', 54535.00, 'Mobile Money', '', '2025-09-04 00:22:39', 55, 27, NULL),
(86, 11, 283, 431, '2025-09-07', 25000.00, 'Mobile Money', 'MPESA', '2025-09-07 15:02:15', 56, 58, 10);

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `update_invoice_paid` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
  UPDATE invoices 
  SET paid_amount = (
    SELECT SUM(amount) 
    FROM payments 
    WHERE invoice_id = NEW.invoice_id
  )
  WHERE id = NEW.invoice_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_invoice_paid_delete` AFTER DELETE ON `payments` FOR EACH ROW BEGIN
  UPDATE invoices 
  SET paid_amount = (
    SELECT SUM(amount) 
    FROM payments 
    WHERE invoice_id = OLD.invoice_id
  )
  WHERE id = OLD.invoice_id;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_invoice_paid_update` AFTER UPDATE ON `payments` FOR EACH ROW BEGIN
  UPDATE invoices 
  SET paid_amount = (
    SELECT SUM(amount) 
    FROM payments 
    WHERE invoice_id = NEW.invoice_id
  )
  WHERE id = NEW.invoice_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_allocations`
--

CREATE TABLE `payment_allocations` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_promises`
--

CREATE TABLE `payment_promises` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `promise_date` date NOT NULL,
  `promised_due_date` date NOT NULL,
  `promised_amount` decimal(15,2) NOT NULL,
  `status` enum('Pending','Kept','Broken') NOT NULL DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_promises`
--

INSERT INTO `payment_promises` (`id`, `school_id`, `student_id`, `invoice_id`, `promise_date`, `promised_due_date`, `promised_amount`, `status`, `notes`, `created_at`) VALUES
(1, 6, 99, 75, '2025-07-01', '2025-07-10', 11500.00, 'Pending', 'Sent a whatsapp message', '2025-08-02 03:50:04');

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `receipt_number` varchar(20) NOT NULL,
  `student_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `memo` text DEFAULT NULL,
  `coa_account_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payment_receipts`
--

INSERT INTO `payment_receipts` (`id`, `school_id`, `receipt_number`, `student_id`, `payment_date`, `amount`, `payment_method`, `memo`, `coa_account_id`, `created_at`) VALUES
(1, 1, 'REC-684169F6A7488', 26, '2025-06-05', 12500.00, 'Mobile Money', '', NULL, '2025-06-05 09:57:10'),
(2, 1, 'REC-6841989292913', 26, '2025-06-05', 10000.00, 'Cash', '', NULL, '2025-06-05 13:16:02'),
(3, 1, 'REC-6841FF673FF14', 24, '2025-06-05', 5500.00, 'Cash', '', NULL, '2025-06-05 20:34:47'),
(4, 1, 'REC-68432474EF20A', 3, '2025-06-06', 24500.00, 'Bank Transfer', 'No:1001', NULL, '2025-06-06 17:25:08'),
(5, 1, 'REC-68489FECD12C5', 19, '2025-06-10', 25000.00, 'Bank Transfer', '', NULL, '2025-06-10 21:13:16'),
(6, 1, 'REC-6848A7B551DE5', 27, '2025-06-10', 22450.00, 'Check', 'Receipt No; 2095', NULL, '2025-06-10 21:46:29'),
(7, 1, 'REC-6848A8547AD3C', 27, '2025-06-10', 50000.00, 'Cash', '', NULL, '2025-06-10 21:49:08'),
(8, 1, 'REC-684E023362872', 29, '2025-06-15', 27650.00, 'Mobile Money', 'Receipt No. 5894', NULL, '2025-06-14 23:13:55'),
(9, 1, 'REC-684E06CB26394', 29, '2025-06-15', 5000.00, 'Cash', 'Receipt No.8796', NULL, '2025-06-14 23:33:31'),
(10, 1, 'REC-684E1A76570E7', 23, '2025-06-15', 5750.00, 'Cash', '', NULL, '2025-06-15 00:57:26'),
(11, 1, 'REC-684ED9C9C58E6', 4, '2025-06-15', 30000.00, 'Cash', '1st Payment', NULL, '2025-06-15 14:33:45'),
(12, 1, 'REC-686263DC89DFD', 20, '2025-06-30', 47500.00, 'Cash', '', NULL, '2025-06-30 10:15:56'),
(13, 1, 'REC-6862811168FAC', 31, '2025-06-30', 25750.00, 'Cash', '', NULL, '2025-06-30 12:20:33'),
(14, 2, 'REC-68644EFF14009', 34, '2025-07-01', 25000.00, 'Mobile Money', '', NULL, '2025-07-01 21:11:27'),
(15, 2, 'REC-686E18BF954A6', 34, '2025-07-09', 15500.00, 'Cash', '', 14, '2025-07-09 07:22:39'),
(16, 2, 'REC-686E197454F0F', 34, '2025-07-09', 5000.00, 'Cash', '', 14, '2025-07-09 07:25:40'),
(17, 2, 'REC-686E1DBDBF61E', 34, '2025-07-09', 5000.00, 'Cash', '', 14, '2025-07-09 07:43:57'),
(18, 3, 'MP-QG912ABCDE', 37, '2025-07-09', 500.00, 'M-Pesa', 'M-Pesa payment from John Doe (254700123456). Ref: QG912ABCDE', 15, '2025-07-09 08:44:24'),
(19, 3, 'MP-QG912ABCDF', 37, '2025-07-09', 50000.00, 'M-Pesa', 'M-Pesa payment from William. Ref: QG912ABCDF', 15, '2025-07-09 08:56:47'),
(20, 3, 'REC-686E57F37AC81', 37, '2025-07-09', 10000.00, 'Mobile Money', '', 19, '2025-07-09 11:52:19'),
(21, 4, 'REC-686E715589FCA', 39, '2025-07-09', 15500.00, 'Mobile Money', '', 20, '2025-07-09 13:40:37'),
(22, 4, 'REC-686E88036D7AF', 39, '2025-07-09', 24500.00, 'Mobile Money', '', 20, '2025-07-09 15:17:23'),
(23, 4, 'REC-686FAFB0C59EB', 39, '2025-07-10', 20000.00, 'Mobile Money', '', 20, '2025-07-10 12:18:56'),
(24, 5, 'REC-68765E8F796D0', 59, '2025-07-15', 25750.00, 'Cash', '', 22, '2025-07-15 13:58:39'),
(25, 5, 'REC-6876B0B9014B1', 66, '2025-07-15', 34500.00, 'Mobile Money', '', 22, '2025-07-15 19:49:13'),
(26, 6, 'REC-687823BA21638', 99, '2025-07-17', 75000.00, 'Mobile Money', '', 27, '2025-07-16 22:12:10'),
(27, 6, 'REC-687823D6F2133', 100, '2025-07-17', 47500.00, 'Mobile Money', '', 27, '2025-07-16 22:12:38'),
(28, 6, 'REC-6878245F04612', 74, '2025-07-17', 35490.00, 'Bank Transfer', '', 27, '2025-07-16 22:14:55'),
(29, 6, 'REC-687C339B9EE93', 74, '2025-07-20', 15000.00, 'Bank Transfer', '', 27, '2025-07-20 00:08:59'),
(30, 6, 'REC-687C36C1D0382', 99, '2025-07-20', 5000.00, 'Cash', '', 27, '2025-07-20 00:22:25'),
(31, 6, 'REC-687C375C46EE7', 82, '2025-07-20', 32500.00, 'Mobile Money', '', 27, '2025-07-20 00:25:00'),
(32, 6, 'REC-687C3DAC83D9A', 77, '2025-07-20', 74500.00, 'Mobile Money', '', 27, '2025-07-20 00:51:56'),
(33, 6, 'REC-687C404F551E3', 102, '2025-07-20', 25000.00, 'Cash', '', 28, '2025-07-20 01:03:11'),
(34, 6, 'REC-6883C14C88478', 75, '2025-07-25', 21300.00, 'Mobile Money', '', 28, '2025-07-25 17:39:24'),
(35, 6, 'REC-6883E61094F46', 105, '2025-07-25', 37500.00, 'Bank Transfer', '', 28, '2025-07-25 20:16:16'),
(36, 6, 'REC-688685F285B9E', 100, '2025-07-27', 25000.00, 'Bank Transfer', '', 28, '2025-07-27 20:02:58'),
(37, 6, 'REC-688F67FE00797', 77, '2025-08-03', 25000.00, 'Mobile Money', 'Ref No; GHO9876567OP', 28, '2025-08-03 13:45:34'),
(38, 6, 'REC-688F6A31CFCF4', 102, '2025-08-03', 100000.00, 'Bank Transfer', '', 28, '2025-08-03 13:54:57'),
(39, 6, 'REC-6891E75533F55', 74, '2025-08-05', 25000.00, 'Cash', '', 28, '2025-08-05 11:13:25'),
(40, 6, 'REC-68920256387CB', 98, '2025-08-05', 73500.00, 'Mobile Money', '', 28, '2025-08-05 13:08:38'),
(43, 7, 'REC-689AFC3A762AC', 123, '2025-08-12', 25000.00, 'Cash', '', 44, '2025-08-12 08:32:58'),
(44, 7, 'REC-689B009D2F37E', 126, '2025-08-12', 22500.00, 'Mobile Money', '', 44, '2025-08-12 08:51:41'),
(45, 8, 'REC-689B0395238F5', 227, '2025-08-12', 25000.00, 'Cash', '', 50, '2025-08-12 09:04:21'),
(46, 9, 'REC-689B24EE7E77F', 327, '2025-08-12', 24500.00, 'Mobile Money', '', 52, '2025-08-12 11:26:38'),
(47, 9, 'REC-689CAD716281C', 377, '2025-08-13', 35500.00, 'Mobile Money', '', 52, '2025-08-13 15:21:21'),
(48, 9, 'REC-68A37D1A92029', 324, '2025-08-18', 25500.00, 'Mobile Money', '', 52, '2025-08-18 19:20:58'),
(49, 6, 'REC-68AE02F560C37', 103, '2025-08-26', 80000.00, 'Cash', '', 27, '2025-08-26 18:54:45'),
(50, 11, 'REC-68B2A3472C714', 432, '2025-08-30', 25400.00, 'Mobile Money', '', 58, '2025-08-30 07:07:51'),
(51, 11, 'REC-68B5800CDB414', 433, '2025-09-01', 22500.00, 'Cash', '', 58, '2025-09-01 11:14:20'),
(52, 11, 'REC-68B5DBEFA73C6', 429, '2025-09-01', 23450.00, 'Bank Transfer', '', 58, '2025-09-01 17:46:23'),
(53, 11, 'REC-68B85BEAA39DA', 430, '2025-09-03', 30500.00, 'Cash', '', 58, '2025-09-03 15:16:58'),
(54, 11, 'REC-68B85D990E5F3', 432, '2025-09-03', 5000.00, 'Mobile Money', '', 58, '2025-09-03 15:24:09'),
(55, 6, 'REC-68B8B19F2D37F', 78, '2025-09-03', 54535.00, 'Mobile Money', '', 27, '2025-09-03 21:22:39'),
(56, 11, 'REC-68BD7447CBB5B', 431, '2025-09-07', 25000.00, 'Mobile Money', 'MPESA', 58, '2025-09-07 12:02:15');

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `employee_type` enum('monthly','daily') NOT NULL,
  `pay_period` varchar(7) NOT NULL COMMENT 'Format YYYY-MM',
  `pay_date` date NOT NULL,
  `gross_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paye` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nhif` decimal(15,2) NOT NULL DEFAULT 0.00,
  `nssf` decimal(15,2) NOT NULL DEFAULT 0.00,
  `housing_levy` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `net_pay` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payslip_data` text DEFAULT NULL COMMENT 'JSON object containing the full payslip breakdown',
  `status` varchar(50) NOT NULL DEFAULT 'Processed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll`
--

INSERT INTO `payroll` (`id`, `school_id`, `employee_id`, `employee_name`, `employee_type`, `pay_period`, `pay_date`, `gross_pay`, `paye`, `nhif`, `nssf`, `housing_levy`, `total_deductions`, `net_pay`, `payslip_data`, `status`, `created_at`) VALUES
(1, 11, 1, 'William Mungai', 'monthly', '2025-09', '2025-09-30', 26000.00, 102.50, 850.00, 1080.00, 390.00, 4422.50, 21577.50, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":21000},{\"name\":\"Bonus\\/Other Earning\",\"amount\":5000}],\"deductions\":[{\"name\":\"Advance\\/Other Deduction\",\"amount\":2000},{\"name\":\"PAYE\",\"amount\":102.5},{\"name\":\"NHIF\",\"amount\":850},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":390}],\"summary\":{\"total_earnings\":26000,\"total_deductions\":4422.5,\"net_pay\":21577.5,\"pay_period\":\"September 2025\",\"pay_date\":\"2025-09-30\"}}', 'Processed', '2025-09-04 10:31:22'),
(2, 11, 3, 'Christina Mungai', 'daily', '2025-09', '2025-09-04', 2500.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2500.00, NULL, 'Processed', '2025-09-04 10:38:30'),
(3, 11, 1, 'William Mungai', 'monthly', '2025-09', '2025-09-30', 21000.00, 0.00, 750.00, 1080.00, 315.00, 2145.00, 18855.00, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":21000}],\"deductions\":[{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":750},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":315}],\"summary\":{\"total_earnings\":21000,\"total_deductions\":2145,\"net_pay\":18855,\"pay_period\":\"September 2025\",\"pay_date\":\"2025-09-30\"}}', 'Processed', '2025-09-04 11:31:51'),
(4, 11, 1, 'William Mungai', 'monthly', '2025-09', '2025-09-30', 21000.00, 0.00, 0.00, 1080.00, 315.00, 1395.00, 19605.00, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":21000}],\"deductions\":[{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":0},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":315}],\"summary\":{\"total_earnings\":21000,\"total_deductions\":1395,\"net_pay\":19605,\"pay_period\":\"September 2025\",\"pay_date\":\"2025-09-30\"}}', 'Processed', '2025-09-04 11:57:56'),
(5, 11, 4, 'Chris Rock', 'monthly', '2025-09', '2025-09-30', 25500.00, 0.00, 0.00, 1080.00, 382.50, 2462.50, 23037.50, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":25500}],\"deductions\":[{\"name\":\"Advance\\/Other Deduction\",\"amount\":1000},{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":0},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":382.5}],\"summary\":{\"total_earnings\":25500,\"total_deductions\":2462.5,\"net_pay\":23037.5,\"pay_period\":\"September 2025\",\"pay_date\":\"2025-09-30\"}}', 'Processed', '2025-09-04 11:57:56'),
(6, 11, 1, 'William Mungai', 'monthly', '2025-10', '2025-10-31', 21000.00, 0.00, 750.00, 1080.00, 315.00, 2145.00, 18855.00, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":21000}],\"deductions\":[{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":750},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":315}],\"summary\":{\"total_earnings\":21000,\"total_deductions\":2145,\"net_pay\":18855,\"pay_period\":\"October 2025\",\"pay_date\":\"2025-10-31\"}}', 'Processed', '2025-09-05 17:18:23'),
(7, 11, 4, 'Chris Rock', 'monthly', '2025-10', '2025-10-31', 25500.00, 0.00, 850.00, 1080.00, 382.50, 4312.50, 21187.50, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":25500}],\"deductions\":[{\"name\":\"Advance\\/Other Deduction\",\"amount\":2000},{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":850},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":382.5}],\"summary\":{\"total_earnings\":25500,\"total_deductions\":4312.5,\"net_pay\":21187.5,\"pay_period\":\"October 2025\",\"pay_date\":\"2025-10-31\"}}', 'Processed', '2025-09-05 17:18:23'),
(8, 11, 1, 'William Mungai', 'monthly', '2025-09', '2025-09-30', 21000.00, 0.00, 750.00, 1080.00, 315.00, 2145.00, 18855.00, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":21000}],\"deductions\":[{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":750},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":315}],\"summary\":{\"total_earnings\":21000,\"total_deductions\":2145,\"net_pay\":18855,\"pay_period\":\"September 2025\",\"pay_date\":\"2025-09-30\"}}', 'Processed', '2025-09-09 14:19:14'),
(9, 11, 4, 'Chris Rock', 'monthly', '2025-09', '2025-09-30', 25500.00, 0.00, 850.00, 1080.00, 382.50, 2312.50, 23187.50, '{\"earnings\":[{\"name\":\"Basic Salary\",\"amount\":25500}],\"deductions\":[{\"name\":\"PAYE\",\"amount\":0},{\"name\":\"NHIF\",\"amount\":850},{\"name\":\"NSSF\",\"amount\":1080},{\"name\":\"Housing Levy\",\"amount\":382.5}],\"summary\":{\"total_earnings\":25500,\"total_deductions\":2312.5,\"net_pay\":23187.5,\"pay_period\":\"September 2025\",\"pay_date\":\"2025-09-30\"}}', 'Processed', '2025-09-09 14:19:14');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_meta`
--

CREATE TABLE `payroll_meta` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('Earning','Deduction') NOT NULL,
  `is_taxable` tinyint(1) NOT NULL DEFAULT 1,
  `is_system` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_meta`
--

INSERT INTO `payroll_meta` (`id`, `school_id`, `name`, `type`, `is_taxable`, `is_system`) VALUES
(309, 1, 'PAYE', 'Deduction', 1, 1),
(310, 1, 'NHIF', 'Deduction', 1, 1),
(311, 1, 'NSSF', 'Deduction', 1, 1),
(312, 1, 'Housing Levy', 'Deduction', 1, 1),
(313, 2, 'PAYE', 'Deduction', 1, 1),
(314, 2, 'NHIF', 'Deduction', 1, 1),
(315, 2, 'NSSF', 'Deduction', 1, 1),
(316, 2, 'Housing Levy', 'Deduction', 1, 1),
(317, 3, 'PAYE', 'Deduction', 1, 1),
(318, 3, 'NHIF', 'Deduction', 1, 1),
(319, 3, 'NSSF', 'Deduction', 1, 1),
(320, 3, 'Housing Levy', 'Deduction', 1, 1),
(321, 4, 'PAYE', 'Deduction', 1, 1),
(322, 4, 'NHIF', 'Deduction', 1, 1),
(323, 4, 'NSSF', 'Deduction', 1, 1),
(324, 4, 'Housing Levy', 'Deduction', 1, 1),
(325, 5, 'PAYE', 'Deduction', 1, 1),
(326, 5, 'NHIF', 'Deduction', 1, 1),
(327, 5, 'NSSF', 'Deduction', 1, 1),
(328, 5, 'Housing Levy', 'Deduction', 1, 1),
(329, 6, 'PAYE', 'Deduction', 1, 1),
(330, 6, 'NHIF', 'Deduction', 1, 1),
(331, 6, 'NSSF', 'Deduction', 1, 1),
(332, 6, 'Housing Levy', 'Deduction', 1, 1),
(333, 7, 'PAYE', 'Deduction', 1, 1),
(334, 7, 'NHIF', 'Deduction', 1, 1),
(335, 7, 'NSSF', 'Deduction', 1, 1),
(336, 7, 'Housing Levy', 'Deduction', 1, 1),
(337, 8, 'PAYE', 'Deduction', 1, 1),
(338, 8, 'NHIF', 'Deduction', 1, 1),
(339, 8, 'NSSF', 'Deduction', 1, 1),
(340, 8, 'Housing Levy', 'Deduction', 1, 1),
(341, 9, 'PAYE', 'Deduction', 1, 1),
(342, 9, 'NHIF', 'Deduction', 1, 1),
(343, 9, 'NSSF', 'Deduction', 1, 1),
(344, 9, 'Housing Levy', 'Deduction', 1, 1),
(345, 10, 'PAYE', 'Deduction', 1, 1),
(346, 10, 'NHIF', 'Deduction', 1, 1),
(347, 10, 'NSSF', 'Deduction', 1, 1),
(348, 10, 'Housing Levy', 'Deduction', 1, 1),
(349, 11, 'PAYE', 'Deduction', 1, 1),
(350, 11, 'NHIF', 'Deduction', 1, 1),
(351, 11, 'NSSF', 'Deduction', 1, 1),
(352, 11, 'Housing Levy', 'Deduction', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--

CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT INTO `payroll_settings` (`id`, `school_id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 1, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(2, 1, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(3, 1, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(4, 1, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(5, 1, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(6, 1, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(7, 1, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(8, 2, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(9, 2, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(10, 2, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(11, 2, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(12, 2, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(13, 2, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(14, 2, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(15, 3, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(16, 3, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(17, 3, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(18, 3, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(19, 3, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(20, 3, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(21, 3, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(22, 4, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(23, 4, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(24, 4, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(25, 4, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(26, 4, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(27, 4, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(28, 4, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(29, 5, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(30, 5, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(31, 5, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(32, 5, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(33, 5, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(34, 5, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(35, 5, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(36, 6, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(37, 6, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(38, 6, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(39, 6, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(40, 6, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(41, 6, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(42, 6, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(43, 7, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(44, 7, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(45, 7, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(46, 7, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(47, 7, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(48, 7, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(49, 7, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(50, 8, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(51, 8, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(52, 8, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(53, 8, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(54, 8, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(55, 8, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(56, 8, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(57, 9, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(58, 9, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(59, 9, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(60, 9, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(61, 9, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(62, 9, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(63, 9, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(64, 10, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(65, 10, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(66, 10, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(67, 10, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(68, 10, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(69, 10, 'nhif_brackets', '[\r\n            {\"max_gross\": 5999, \"deduction\": 150},\r\n            {\"max_gross\": 7999, \"deduction\": 300},\r\n            {\"max_gross\": 11999, \"deduction\": 400},\r\n            {\"max_gross\": 14999, \"deduction\": 500},\r\n            {\"max_gross\": 19999, \"deduction\": 600},\r\n            {\"max_gross\": 24999, \"deduction\": 750},\r\n            {\"max_gross\": 29999, \"deduction\": 850},\r\n            {\"max_gross\": 34999, \"deduction\": 900},\r\n            {\"max_gross\": 39999, \"deduction\": 950},\r\n            {\"max_gross\": 44999, \"deduction\": 1000},\r\n            {\"max_gross\": 49999, \"deduction\": 1100},\r\n            {\"max_gross\": 59999, \"deduction\": 1200},\r\n            {\"max_gross\": 69999, \"deduction\": 1300},\r\n            {\"max_gross\": 79999, \"deduction\": 1400},\r\n            {\"max_gross\": 89999, \"deduction\": 1500},\r\n            {\"max_gross\": 99999, \"deduction\": 1600},\r\n            {\"max_gross\": \"Infinity\", \"deduction\": 1700}\r\n        ]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:31:50'),
(70, 10, 'paye_brackets', '[\r\n            {\"max_annual\": 288000, \"rate\": 0.10, \"base_tax\": 0, \"prev_max\": 0},\r\n            {\"max_annual\": 388000, \"rate\": 0.25, \"base_tax\": 28800, \"prev_max\": 288000},\r\n            {\"max_annual\": \"Infinity\", \"rate\": 0.30, \"base_tax\": 53800, \"prev_max\": 388000}\r\n        ]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:31:50'),
(71, 11, 'nssf_rate', '0.06', 'NSSF contribution rate (Tier 1).', '2025-09-04 12:31:50'),
(72, 11, 'nssf_cap', '1080', 'Maximum monthly NSSF contribution amount.', '2025-09-04 12:31:50'),
(73, 11, 'housing_levy_rate', '0.015', 'Housing Levy contribution rate.', '2025-09-04 12:31:50'),
(74, 11, 'personal_relief', '2400', 'Monthly personal tax relief amount.', '2025-09-04 12:31:50'),
(75, 11, 'insurance_relief_rate', '0.15', 'Insurance relief rate applied to NHIF contribution.', '2025-09-04 12:31:50'),
(76, 11, 'nhif_brackets', '[\r\n  {\r\n    \"max_gross\": 5999,\r\n    \"deduction\": 150\r\n  },\r\n  {\r\n    \"max_gross\": 7999,\r\n    \"deduction\": 300\r\n  },\r\n  {\r\n    \"max_gross\": 11999,\r\n    \"deduction\": 400\r\n  },\r\n  {\r\n    \"max_gross\": 14999,\r\n    \"deduction\": 500\r\n  },\r\n  {\r\n    \"max_gross\": 19999,\r\n    \"deduction\": 600\r\n  },\r\n  {\r\n    \"max_gross\": 24999,\r\n    \"deduction\": 750\r\n  },\r\n  {\r\n    \"max_gross\": 29999,\r\n    \"deduction\": 850\r\n  },\r\n  {\r\n    \"max_gross\": 34999,\r\n    \"deduction\": 900\r\n  },\r\n  {\r\n    \"max_gross\": 39999,\r\n    \"deduction\": 950\r\n  },\r\n  {\r\n    \"max_gross\": 44999,\r\n    \"deduction\": 1000\r\n  },\r\n  {\r\n    \"max_gross\": 49999,\r\n    \"deduction\": 1100\r\n  },\r\n  {\r\n    \"max_gross\": 59999,\r\n    \"deduction\": 1200\r\n  },\r\n  {\r\n    \"max_gross\": 69999,\r\n    \"deduction\": 1300\r\n  },\r\n  {\r\n    \"max_gross\": 79999,\r\n    \"deduction\": 1400\r\n  },\r\n  {\r\n    \"max_gross\": 89999,\r\n    \"deduction\": 1500\r\n  },\r\n  {\r\n    \"max_gross\": 99999,\r\n    \"deduction\": 1600\r\n  },\r\n  {\r\n    \"max_gross\": \"Infinity\",\r\n    \"deduction\": 1700\r\n  }\r\n]', 'NHIF contribution brackets (JSON format).', '2025-09-04 12:48:44'),
(77, 11, 'paye_brackets', '[\r\n  {\r\n    \"max_annual\": 288000,\r\n    \"rate\": 0.1,\r\n    \"base_tax\": 0,\r\n    \"prev_max\": 0\r\n  },\r\n  {\r\n    \"max_annual\": 388000,\r\n    \"rate\": 0.25,\r\n    \"base_tax\": 28800,\r\n    \"prev_max\": 288000\r\n  },\r\n  {\r\n    \"max_annual\": \"Infinity\",\r\n    \"rate\": 0.3,\r\n    \"base_tax\": 53800,\r\n    \"prev_max\": 388000\r\n  }\r\n]', 'PAYE tax brackets based on Annual Taxable Pay (JSON format).', '2025-09-04 12:48:44');

-- --------------------------------------------------------

--
-- Table structure for table `receipt_uploads`
--

CREATE TABLE `receipt_uploads` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `temp_filepath` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','expired') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `receipt_uploads`
--

INSERT INTO `receipt_uploads` (`id`, `school_id`, `token`, `temp_filepath`, `status`, `created_at`) VALUES
(1, 11, '07be70bf55e6194b9e20070d109d13e6cc01b5be959f63931d7516708b932e64', NULL, 'pending', '2025-09-03 15:13:49'),
(2, 11, 'ebba24ff67c2ea089b12884db6c45300b24136be991264368fd930a494fff6b2', NULL, 'pending', '2025-09-03 15:19:46'),
(3, 11, '04ca9a65abeb474186a025f330581b2daae424fc636b4306e7c9edd9b0cd0385', NULL, 'pending', '2025-09-03 15:25:39'),
(4, 11, '843c1d5aa31b36be29ac41a445b315a0c4cf0891ea8a226ff9557f5c2ce2550e', NULL, 'pending', '2025-09-03 15:29:37'),
(5, 11, 'c1489299703830fb6bd4d384b4962bfb548c0976aa147248bf2b43b31a1a301e', NULL, 'pending', '2025-09-03 15:34:33'),
(6, 11, 'c2638585faed4e844246f16684425b7477ce3771a092ac0e97ac0e94ab15eb9f', NULL, 'pending', '2025-09-03 15:34:53'),
(7, 11, 'c8ede783608d0646d4418fe063c66eb3feea88bdb99c174418a3954e36aebe14', NULL, 'pending', '2025-09-03 15:35:01'),
(8, 6, '418ad68e47c043da81c687b579b1b8b9408c81190476d007fe2cbda87189f3e8', NULL, 'pending', '2025-09-03 19:53:43'),
(9, 6, '77bc3891aba09640977c8210b9f8ac597a9b92129b7064e3cf6bb802b76a0756', NULL, 'pending', '2025-09-03 20:04:37'),
(10, 6, 'cfbab63609a34f60bc87690e4367b4bb748fc3a82493f851f3cebded79b2b41a', NULL, 'pending', '2025-09-03 20:05:23'),
(11, 6, '88ffc8036ec4ee22abc0279a54f35ce33a9c416ade58d4b713a5afbccdc34092', NULL, 'pending', '2025-09-03 20:20:30'),
(12, 6, 'd5985a7c53a4573477ce4d51e9836356cdbd71ce70d5ab4ee4cae7c7e4b725a7', NULL, 'pending', '2025-09-03 20:20:55'),
(13, 6, '5f8483b419ae0019cc0e3f74e0d45c7975754b98e680e187282e618b4c0316a1', NULL, 'pending', '2025-09-03 20:20:58'),
(14, 6, '92115d9d2053266159df79ca670eb295a36965aa605c0e86590ebf34994267fc', NULL, 'pending', '2025-09-03 20:26:42'),
(15, 6, '9f3875e02046b78fdb803d31c3b000ac30bf21a88caf0f613ea7f1c013f340ed', NULL, 'pending', '2025-09-03 20:27:00'),
(16, 6, '347efe439fb9178c18c70dce64540b91a765bc7c23362d18706c14666f16cef0', NULL, 'pending', '2025-09-03 20:31:36'),
(17, 6, '03606cfbdfd54bb610f6d1914994743e33cbe89dd92eaee512e81c63899bbedb', NULL, 'pending', '2025-09-03 20:56:19'),
(18, 6, '498a407486b8abc96f09f9b853c0c254315af6fbd27a5fa9d82294443d7fad3e', NULL, 'pending', '2025-09-03 20:56:24'),
(19, 6, 'cb59407bd2ed7ffb907b3692a6f4b9f252b2febad1ca2e79e55566b3743e0e6e', NULL, 'pending', '2025-09-03 20:57:04'),
(20, 6, '28cebbc17c059ef16d240b367ec319ddbc56a04348bcd08ab83ebda656c15760', NULL, 'pending', '2025-09-03 21:07:13'),
(21, 6, '47152f8d620d922e984a3f0cca67b0fb8fc27c881ba3e78508a1f6a40712179b', NULL, 'pending', '2025-09-03 21:17:44'),
(22, 6, 'e3285372f055517dc33e2767e3ce740ac5bf8dd804ce52d0c57b50b9a3271dce', 'uploads/temp/receipt_e3285372f055517dc33e2767e3ce740ac5bf8dd804ce52d0c57b50b9a3271dce.jpg', 'completed', '2025-09-03 21:21:23'),
(23, 11, 'cbdb7e816d36dcb69305842d2c3e2dae31eebe2264fcee29f64fcda33275e3b1', NULL, 'pending', '2025-09-03 22:57:10'),
(24, 11, '75c2e49febc7fc25f5f5b762276bedb892bcac91c784d2a59b54b83fa3eefe79', NULL, 'pending', '2025-09-04 12:50:22'),
(25, 11, 'e468ca4854c7665fa602516be6085d5dc90f4916292dd5cefd7906a6a3595834', NULL, 'pending', '2025-09-07 12:19:31'),
(26, 12, '263940b13af800b5f2631c587532f602a6d69ad0699f72c4a249cd6f546aa64f', NULL, 'pending', '2025-11-29 20:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `recurring_expenses`
--

CREATE TABLE `recurring_expenses` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `expense_account_id` int(11) NOT NULL,
  `payment_account_id` int(11) NOT NULL COMMENT 'The Asset account to credit (e.g., Petty Cash, Bank)',
  `frequency` enum('daily','weekly','monthly','yearly') NOT NULL,
  `next_due_date` date NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requisition_batches`
--

CREATE TABLE `requisition_batches` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `transaction_date` date NOT NULL,
  `payment_account_id` int(11) NOT NULL COMMENT 'e.g., Petty Cash account ID',
  `original_filename` varchar(255) NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending_categorization','processed','cancelled') NOT NULL DEFAULT 'pending_categorization'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisition_batches`
--

INSERT INTO `requisition_batches` (`id`, `school_id`, `user_id`, `upload_date`, `transaction_date`, `payment_account_id`, `original_filename`, `total_amount`, `status`) VALUES
(1, 5, 4, '2025-07-16 20:36:54', '2025-07-16', 23, 'Requisition_Week 5 Test.csv', 0.00, 'pending_categorization'),
(2, 5, 4, '2025-07-16 20:40:24', '2025-07-16', 23, 'Requisition_Week 5 Test.csv', 0.00, 'pending_categorization'),
(3, 5, 4, '2025-07-16 20:43:51', '2025-07-16', 23, 'Requisition_Week 5 Test.csv', 0.00, 'pending_categorization'),
(4, 5, 4, '2025-07-16 20:51:06', '2025-07-16', 23, 'Requisition_Week 5 Test.csv', 0.00, 'pending_categorization'),
(5, 5, 4, '2025-07-16 20:59:13', '2025-07-16', 23, 'Requisition_Week 5 Test.csv', 0.00, 'pending_categorization'),
(8, 5, 4, '2025-07-16 21:34:23', '2025-07-16', 23, 'Requisition_Week 5 Test.csv', 65630.00, 'processed'),
(9, 6, 5, '2025-07-16 22:37:01', '2025-07-17', 29, 'Requisition_Week 5 Test.csv', 65630.00, 'pending_categorization'),
(11, 6, 5, '2025-07-19 22:21:05', '2025-07-20', 29, 'Requisition_Week 5 Test.csv', 65630.00, 'pending_categorization'),
(12, 6, 5, '2025-07-19 22:55:39', '2025-07-20', 29, 'Requisition_Week 5 Test.csv', 65630.00, 'pending_categorization'),
(13, 6, 5, '2025-07-19 23:00:26', '2025-07-20', 29, 'Requisition_Week 5 Test.csv', 65630.00, 'processed'),
(14, 6, 5, '2025-07-20 00:30:37', '2025-07-20', 28, 'Requisition_Week 5 Test.csv', 65630.00, 'processed'),
(15, 6, 5, '2025-07-27 20:18:57', '2025-07-27', 28, 'Requisition_Week 5 Test.csv', 65630.00, 'processed'),
(16, 11, 10, '2025-08-30 18:14:17', '2025-08-30', 60, 'Requisition_Week 5 Test.csv', 65630.00, 'pending_categorization'),
(17, 11, 10, '2025-08-30 18:15:32', '2025-08-30', 59, 'Requisition_Week 5 Test.csv', 65630.00, 'pending_categorization'),
(18, 6, 5, '2025-08-30 18:16:57', '2025-08-30', 29, 'Requisition_Week 5 Test.csv', 65630.00, 'pending_categorization');

-- --------------------------------------------------------

--
-- Table structure for table `requisition_items`
--

CREATE TABLE `requisition_items` (
  `id` int(11) NOT NULL,
  `batch_id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `assigned_expense_account_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `requisition_items`
--

INSERT INTO `requisition_items` (`id`, `batch_id`, `description`, `quantity`, `unit_cost`, `total_cost`, `assigned_expense_account_id`) VALUES
(1, 8, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, 26),
(2, 8, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, 26),
(3, 8, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, 26),
(4, 8, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, 26),
(5, 8, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, 26),
(6, 8, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, 26),
(7, 8, 'William', 1.00, 2100.00, 2100.00, 26),
(8, 8, 'Office', 1.00, 300.00, 300.00, 25),
(9, 8, 'Vans', 1.00, 300.00, 300.00, 25),
(10, 8, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, 25),
(11, 8, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, 25),
(12, 8, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, 25),
(13, 8, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, 25),
(14, 8, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, 25),
(15, 8, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, 25),
(16, 8, 'Milk', 1.00, 1387.00, 1387.00, 25),
(17, 8, 'Spinach', 1.00, 600.00, 600.00, 25),
(18, 8, 'Tomatoes', 1.00, 600.00, 600.00, 25),
(19, 8, 'Carrots', 1.00, 400.00, 400.00, 25),
(20, 8, 'Fruits', 1.00, 1250.00, 1250.00, 25),
(21, 8, 'Bread', 1.00, 780.00, 780.00, 25),
(22, 8, 'Dhania/Saumu', 1.00, 180.00, 180.00, 25),
(23, 8, 'Water', 1.00, 200.00, 200.00, 25),
(24, 8, 'Cabbage', 1.00, 780.00, 780.00, 25),
(25, 8, 'Leek', 1.00, 150.00, 150.00, 25),
(26, 8, 'Firewood', 1.00, 1500.00, 1500.00, 25),
(27, 8, 'Charcoal', 1.00, 1800.00, 1800.00, 25),
(28, 8, 'Salt', 1.00, 880.00, 880.00, 25),
(29, 8, 'Meat', 1.00, 7700.00, 7700.00, 25),
(30, 9, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, NULL),
(31, 9, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, NULL),
(32, 9, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, NULL),
(33, 9, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, NULL),
(34, 9, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, NULL),
(35, 9, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, NULL),
(36, 9, 'William', 1.00, 2100.00, 2100.00, NULL),
(37, 9, 'Office', 1.00, 300.00, 300.00, NULL),
(38, 9, 'Vans', 1.00, 300.00, 300.00, NULL),
(39, 9, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, NULL),
(40, 9, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, NULL),
(41, 9, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, NULL),
(42, 9, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, NULL),
(43, 9, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, NULL),
(44, 9, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, NULL),
(45, 9, 'Milk', 1.00, 1387.00, 1387.00, NULL),
(46, 9, 'Spinach', 1.00, 600.00, 600.00, NULL),
(47, 9, 'Tomatoes', 1.00, 600.00, 600.00, NULL),
(48, 9, 'Carrots', 1.00, 400.00, 400.00, NULL),
(49, 9, 'Fruits', 1.00, 1250.00, 1250.00, NULL),
(50, 9, 'Bread', 1.00, 780.00, 780.00, NULL),
(51, 9, 'Dhania/Saumu', 1.00, 180.00, 180.00, NULL),
(52, 9, 'Water', 1.00, 200.00, 200.00, NULL),
(53, 9, 'Cabbage', 1.00, 780.00, 780.00, NULL),
(54, 9, 'Leek', 1.00, 150.00, 150.00, NULL),
(55, 9, 'Firewood', 1.00, 1500.00, 1500.00, NULL),
(56, 9, 'Charcoal', 1.00, 1800.00, 1800.00, NULL),
(57, 9, 'Salt', 1.00, 880.00, 880.00, NULL),
(58, 9, 'Meat', 1.00, 7700.00, 7700.00, NULL),
(59, 11, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, NULL),
(60, 11, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, NULL),
(61, 11, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, NULL),
(62, 11, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, NULL),
(63, 11, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, NULL),
(64, 11, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, NULL),
(65, 11, 'William', 1.00, 2100.00, 2100.00, NULL),
(66, 11, 'Office', 1.00, 300.00, 300.00, NULL),
(67, 11, 'Vans', 1.00, 300.00, 300.00, NULL),
(68, 11, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, NULL),
(69, 11, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, NULL),
(70, 11, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, NULL),
(71, 11, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, NULL),
(72, 11, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, NULL),
(73, 11, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, NULL),
(74, 11, 'Milk', 1.00, 1387.00, 1387.00, NULL),
(75, 11, 'Spinach', 1.00, 600.00, 600.00, NULL),
(76, 11, 'Tomatoes', 1.00, 600.00, 600.00, NULL),
(77, 11, 'Carrots', 1.00, 400.00, 400.00, NULL),
(78, 11, 'Fruits', 1.00, 1250.00, 1250.00, NULL),
(79, 11, 'Bread', 1.00, 780.00, 780.00, NULL),
(80, 11, 'Dhania/Saumu', 1.00, 180.00, 180.00, NULL),
(81, 11, 'Water', 1.00, 200.00, 200.00, NULL),
(82, 11, 'Cabbage', 1.00, 780.00, 780.00, NULL),
(83, 11, 'Leek', 1.00, 150.00, 150.00, NULL),
(84, 11, 'Firewood', 1.00, 1500.00, 1500.00, NULL),
(85, 11, 'Charcoal', 1.00, 1800.00, 1800.00, NULL),
(86, 11, 'Salt', 1.00, 880.00, 880.00, NULL),
(87, 11, 'Meat', 1.00, 7700.00, 7700.00, NULL),
(88, 12, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, NULL),
(89, 12, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, NULL),
(90, 12, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, NULL),
(91, 12, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, NULL),
(92, 12, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, NULL),
(93, 12, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, NULL),
(94, 12, 'William', 1.00, 2100.00, 2100.00, NULL),
(95, 12, 'Office', 1.00, 300.00, 300.00, NULL),
(96, 12, 'Vans', 1.00, 300.00, 300.00, NULL),
(97, 12, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, NULL),
(98, 12, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, NULL),
(99, 12, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, NULL),
(100, 12, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, NULL),
(101, 12, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, NULL),
(102, 12, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, NULL),
(103, 12, 'Milk', 1.00, 1387.00, 1387.00, NULL),
(104, 12, 'Spinach', 1.00, 600.00, 600.00, NULL),
(105, 12, 'Tomatoes', 1.00, 600.00, 600.00, NULL),
(106, 12, 'Carrots', 1.00, 400.00, 400.00, NULL),
(107, 12, 'Fruits', 1.00, 1250.00, 1250.00, NULL),
(108, 12, 'Bread', 1.00, 780.00, 780.00, NULL),
(109, 12, 'Dhania/Saumu', 1.00, 180.00, 180.00, NULL),
(110, 12, 'Water', 1.00, 200.00, 200.00, NULL),
(111, 12, 'Cabbage', 1.00, 780.00, 780.00, NULL),
(112, 12, 'Leek', 1.00, 150.00, 150.00, NULL),
(113, 12, 'Firewood', 1.00, 1500.00, 1500.00, NULL),
(114, 12, 'Charcoal', 1.00, 1800.00, 1800.00, NULL),
(115, 12, 'Salt', 1.00, 880.00, 880.00, NULL),
(116, 12, 'Meat', 1.00, 7700.00, 7700.00, NULL),
(117, 13, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, 36),
(118, 13, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, 36),
(119, 13, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, 36),
(120, 13, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, 36),
(121, 13, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, 36),
(122, 13, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, 36),
(123, 13, 'William', 1.00, 2100.00, 2100.00, 36),
(124, 13, 'Office', 1.00, 300.00, 300.00, 37),
(125, 13, 'Vans', 1.00, 300.00, 300.00, 37),
(126, 13, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, 37),
(127, 13, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, 38),
(128, 13, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, 37),
(129, 13, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, 38),
(130, 13, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, 38),
(131, 13, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, 38),
(132, 13, 'Milk', 1.00, 1387.00, 1387.00, 30),
(133, 13, 'Spinach', 1.00, 600.00, 600.00, 30),
(134, 13, 'Tomatoes', 1.00, 600.00, 600.00, 30),
(135, 13, 'Carrots', 1.00, 400.00, 400.00, 30),
(136, 13, 'Fruits', 1.00, 1250.00, 1250.00, 30),
(137, 13, 'Bread', 1.00, 780.00, 780.00, 30),
(138, 13, 'Dhania/Saumu', 1.00, 180.00, 180.00, 30),
(139, 13, 'Water', 1.00, 200.00, 200.00, 30),
(140, 13, 'Cabbage', 1.00, 780.00, 780.00, 30),
(141, 13, 'Leek', 1.00, 150.00, 150.00, 30),
(142, 13, 'Firewood', 1.00, 1500.00, 1500.00, 30),
(143, 13, 'Charcoal', 1.00, 1800.00, 1800.00, 30),
(144, 13, 'Salt', 1.00, 880.00, 880.00, 30),
(145, 13, 'Meat', 1.00, 7700.00, 7700.00, 30),
(146, 14, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, 36),
(147, 14, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, 36),
(148, 14, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, 36),
(149, 14, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, 36),
(150, 14, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, 36),
(151, 14, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, 36),
(152, 14, 'William', 1.00, 2100.00, 2100.00, 36),
(153, 14, 'Office', 1.00, 300.00, 300.00, 37),
(154, 14, 'Vans', 1.00, 300.00, 300.00, 37),
(155, 14, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, 36),
(156, 14, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, 37),
(157, 14, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, 37),
(158, 14, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, 37),
(159, 14, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, 37),
(160, 14, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, 37),
(161, 14, 'Milk', 1.00, 1387.00, 1387.00, 30),
(162, 14, 'Spinach', 1.00, 600.00, 600.00, 30),
(163, 14, 'Tomatoes', 1.00, 600.00, 600.00, 30),
(164, 14, 'Carrots', 1.00, 400.00, 400.00, 30),
(165, 14, 'Fruits', 1.00, 1250.00, 1250.00, 30),
(166, 14, 'Bread', 1.00, 780.00, 780.00, 30),
(167, 14, 'Dhania/Saumu', 1.00, 180.00, 180.00, 30),
(168, 14, 'Water', 1.00, 200.00, 200.00, 30),
(169, 14, 'Cabbage', 1.00, 780.00, 780.00, 30),
(170, 14, 'Leek', 1.00, 150.00, 150.00, 30),
(171, 14, 'Firewood', 1.00, 1500.00, 1500.00, 30),
(172, 14, 'Charcoal', 1.00, 1800.00, 1800.00, 30),
(173, 14, 'Salt', 1.00, 880.00, 880.00, 30),
(174, 14, 'Meat', 1.00, 7700.00, 7700.00, 30),
(175, 15, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, 36),
(176, 15, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, 36),
(177, 15, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, 36),
(178, 15, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, 36),
(179, 15, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, 36),
(180, 15, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, 36),
(181, 15, 'William', 1.00, 2100.00, 2100.00, 36),
(182, 15, 'Office', 1.00, 300.00, 300.00, 37),
(183, 15, 'Vans', 1.00, 300.00, 300.00, 37),
(184, 15, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, 36),
(185, 15, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, 37),
(186, 15, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, 37),
(187, 15, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, 37),
(188, 15, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, 37),
(189, 15, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, 37),
(190, 15, 'Milk', 1.00, 1387.00, 1387.00, 30),
(191, 15, 'Spinach', 1.00, 600.00, 600.00, 30),
(192, 15, 'Tomatoes', 1.00, 600.00, 600.00, 30),
(193, 15, 'Carrots', 1.00, 400.00, 400.00, 30),
(194, 15, 'Fruits', 1.00, 1250.00, 1250.00, 30),
(195, 15, 'Bread', 1.00, 780.00, 780.00, 30),
(196, 15, 'Dhania/Saumu', 1.00, 180.00, 180.00, 30),
(197, 15, 'Water', 1.00, 200.00, 200.00, 30),
(198, 15, 'Cabbage', 1.00, 780.00, 780.00, 30),
(199, 15, 'Leek', 1.00, 150.00, 150.00, 30),
(200, 15, 'Firewood', 1.00, 1500.00, 1500.00, 30),
(201, 15, 'Charcoal', 1.00, 1800.00, 1800.00, 30),
(202, 15, 'Salt', 1.00, 880.00, 880.00, 30),
(203, 15, 'Meat', 1.00, 7700.00, 7700.00, 30),
(204, 16, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, NULL),
(205, 16, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, NULL),
(206, 16, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, NULL),
(207, 16, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, NULL),
(208, 16, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, NULL),
(209, 16, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, NULL),
(210, 16, 'William', 1.00, 2100.00, 2100.00, NULL),
(211, 16, 'Office', 1.00, 300.00, 300.00, NULL),
(212, 16, 'Vans', 1.00, 300.00, 300.00, NULL),
(213, 16, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, NULL),
(214, 16, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, NULL),
(215, 16, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, NULL),
(216, 16, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, NULL),
(217, 16, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, NULL),
(218, 16, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, NULL),
(219, 16, 'Milk', 1.00, 1387.00, 1387.00, NULL),
(220, 16, 'Spinach', 1.00, 600.00, 600.00, NULL),
(221, 16, 'Tomatoes', 1.00, 600.00, 600.00, NULL),
(222, 16, 'Carrots', 1.00, 400.00, 400.00, NULL),
(223, 16, 'Fruits', 1.00, 1250.00, 1250.00, NULL),
(224, 16, 'Bread', 1.00, 780.00, 780.00, NULL),
(225, 16, 'Dhania/Saumu', 1.00, 180.00, 180.00, NULL),
(226, 16, 'Water', 1.00, 200.00, 200.00, NULL),
(227, 16, 'Cabbage', 1.00, 780.00, 780.00, NULL),
(228, 16, 'Leek', 1.00, 150.00, 150.00, NULL),
(229, 16, 'Firewood', 1.00, 1500.00, 1500.00, NULL),
(230, 16, 'Charcoal', 1.00, 1800.00, 1800.00, NULL),
(231, 16, 'Salt', 1.00, 880.00, 880.00, NULL),
(232, 16, 'Meat', 1.00, 7700.00, 7700.00, NULL),
(233, 17, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, NULL),
(234, 17, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, NULL),
(235, 17, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, NULL),
(236, 17, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, NULL),
(237, 17, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, NULL),
(238, 17, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, NULL),
(239, 17, 'William', 1.00, 2100.00, 2100.00, NULL),
(240, 17, 'Office', 1.00, 300.00, 300.00, NULL),
(241, 17, 'Vans', 1.00, 300.00, 300.00, NULL),
(242, 17, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, NULL),
(243, 17, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, NULL),
(244, 17, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, NULL),
(245, 17, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, NULL),
(246, 17, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, NULL),
(247, 17, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, NULL),
(248, 17, 'Milk', 1.00, 1387.00, 1387.00, NULL),
(249, 17, 'Spinach', 1.00, 600.00, 600.00, NULL),
(250, 17, 'Tomatoes', 1.00, 600.00, 600.00, NULL),
(251, 17, 'Carrots', 1.00, 400.00, 400.00, NULL),
(252, 17, 'Fruits', 1.00, 1250.00, 1250.00, NULL),
(253, 17, 'Bread', 1.00, 780.00, 780.00, NULL),
(254, 17, 'Dhania/Saumu', 1.00, 180.00, 180.00, NULL),
(255, 17, 'Water', 1.00, 200.00, 200.00, NULL),
(256, 17, 'Cabbage', 1.00, 780.00, 780.00, NULL),
(257, 17, 'Leek', 1.00, 150.00, 150.00, NULL),
(258, 17, 'Firewood', 1.00, 1500.00, 1500.00, NULL),
(259, 17, 'Charcoal', 1.00, 1800.00, 1800.00, NULL),
(260, 17, 'Salt', 1.00, 880.00, 880.00, NULL),
(261, 17, 'Meat', 1.00, 7700.00, 7700.00, NULL),
(262, 18, 'Joseph Wanjiru-(0720341957)', 1.00, 4250.00, 4250.00, NULL),
(263, 18, 'Dennis Maina-(0798320389)', 1.00, 4250.00, 4250.00, NULL),
(264, 18, 'Teresiah-(0717617246)', 1.00, 2500.00, 2500.00, NULL),
(265, 18, 'Rahab-(0793505868)', 1.00, 3000.00, 3000.00, NULL),
(266, 18, 'Sarah-(0797979124)', 1.00, 2500.00, 2500.00, NULL),
(267, 18, 'Meryln-(0718698353)', 1.00, 2500.00, 2500.00, NULL),
(268, 18, 'William', 1.00, 2100.00, 2100.00, NULL),
(269, 18, 'Office', 1.00, 300.00, 300.00, NULL),
(270, 18, 'Vans', 1.00, 300.00, 300.00, NULL),
(271, 18, 'Uniforms-(0724477430)', 1.00, 4370.00, 4370.00, NULL),
(272, 18, 'Car wash - 0790697466(Musyoka)', 1.00, 600.00, 600.00, NULL),
(273, 18, 'Swimming - Paybill(880100) Acc No.(600006)', 1.00, 8100.00, 8100.00, NULL),
(274, 18, 'Mechanic Inspection (KBZ)', 1.00, 2100.00, 2100.00, NULL),
(275, 18, 'Fuel Inspection (KBZ)', 1.00, 7553.00, 7553.00, NULL),
(276, 18, 'Breakpads +Labour', 1.00, 3000.00, 3000.00, NULL),
(277, 18, 'Milk', 1.00, 1387.00, 1387.00, NULL),
(278, 18, 'Spinach', 1.00, 600.00, 600.00, NULL),
(279, 18, 'Tomatoes', 1.00, 600.00, 600.00, NULL),
(280, 18, 'Carrots', 1.00, 400.00, 400.00, NULL),
(281, 18, 'Fruits', 1.00, 1250.00, 1250.00, NULL),
(282, 18, 'Bread', 1.00, 780.00, 780.00, NULL),
(283, 18, 'Dhania/Saumu', 1.00, 180.00, 180.00, NULL),
(284, 18, 'Water', 1.00, 200.00, 200.00, NULL),
(285, 18, 'Cabbage', 1.00, 780.00, 780.00, NULL),
(286, 18, 'Leek', 1.00, 150.00, 150.00, NULL),
(287, 18, 'Firewood', 1.00, 1500.00, 1500.00, NULL),
(288, 18, 'Charcoal', 1.00, 1800.00, 1800.00, NULL),
(289, 18, 'Salt', 1.00, 880.00, 880.00, NULL),
(290, 18, 'Meat', 1.00, 7700.00, 7700.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `created_at`) VALUES
(1, 'Default School', '2025-07-01 10:58:59'),
(2, 'Strathmore University', '2025-07-01 11:15:12'),
(3, 'My School', '2025-07-09 08:08:56'),
(4, 'Test School', '2025-07-09 12:41:06'),
(5, 'St.Marys School', '2025-07-11 21:00:10'),
(6, 'Bright Horizon School', '2025-07-16 21:57:11'),
(7, 'The School', '2025-08-06 14:08:44'),
(8, 'Tesla', '2025-08-12 08:55:26'),
(9, 'Office', '2025-08-12 11:00:05'),
(10, 'Oakwood Academy', '2025-08-26 12:16:37'),
(11, 'BLOOMSFIELD SCHOOL', '2025-08-28 14:01:10'),
(12, 'APU', '2025-11-29 18:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `school_details`
--

CREATE TABLE `school_details` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `address` text DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `currency` varchar(10) NOT NULL DEFAULT '$',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `school_details`
--

INSERT INTO `school_details` (`id`, `school_id`, `address`, `phone`, `email`, `logo_url`, `currency`, `created_at`, `updated_at`) VALUES
(1, 7, 'Nairobi City', '0769855953', 'info@mash.com', '', '$', '2025-08-12 06:22:41', '2025-08-12 06:22:41'),
(2, 11, 'rfyitfuyhjmvk,iu,fktrytedjl;kjlytukkiug\r\nyutumrnytryygeuy\r\n8tytqrtyukjyrtu', '0769855953', 'mungaimacharia308@gmail.com', 'uploads/logos/logo_11_1757065304.png', '$', '2025-08-31 14:13:40', '2025-09-05 17:15:58');

-- --------------------------------------------------------

--
-- Table structure for table `service_payments`
--

CREATE TABLE `service_payments` (
  `id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `provider_name` varchar(100) NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `service_providers`
--

CREATE TABLE `service_providers` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id_no` varchar(50) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `class_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `token` varchar(64) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `school_id`, `student_id_no`, `name`, `email`, `phone`, `address`, `created_at`, `class_id`, `status`, `token`) VALUES
(1, 1, NULL, 'William Macharia Mungai', 'william.mungai@strathmore.edu', '0769855953', 'Lavington', '2025-04-07 21:09:29', NULL, 'active', NULL),
(2, 1, NULL, 'John Gathua', 'mungaimacharia308@gmail.com', '0769855953', 'Lavington', '2025-04-07 21:44:09', NULL, 'active', NULL),
(3, 1, NULL, 'Michael Mungai', 'mungaimacharia308@gmail.com', '0769855953', 'Kiambu', '2025-04-09 13:32:16', NULL, 'active', NULL),
(4, 1, NULL, 'Brian Gacheru', 'info@bloomsfieldschool.co.ke', '0769855953', 'Kilimani', '2025-04-13 20:26:52', NULL, 'active', NULL),
(5, 1, NULL, 'Ken Kimani', 'salt@gmail.com', '0769855953', 'Ruaka', '2025-04-13 20:35:28', NULL, 'active', NULL),
(6, 1, NULL, 'John Doe', 'johndoe@gmail.com', '0712345678', 'Kileleshwa', '2025-04-18 09:22:35', NULL, 'active', NULL),
(7, 1, NULL, 'Maryann Wanjiku', 'mary@gmail.com', '0718760077', 'Ruaka', '2025-04-19 14:19:26', NULL, 'active', NULL),
(8, 1, NULL, 'Jane Doe', 'janedoe@gmail.com', '0769855953', 'Kiambu', '2025-04-29 00:57:26', NULL, 'active', NULL),
(9, 1, NULL, 'Admin one', 'adminone@gmail.com', '0769855953', 'Lavington', '2025-04-29 01:47:27', NULL, 'active', NULL),
(10, 1, NULL, 'Admin Two', 'admintwo@gmail.com', '0769855953', 'Wherever', '2025-04-29 16:05:26', NULL, 'active', NULL),
(11, 1, NULL, 'Admin Three', 'adminthree@gmail.com', '0769855953', 'Kiambu', '2025-04-29 21:45:57', NULL, 'active', NULL),
(12, 1, NULL, 'William Macharia Mungai', 'william.mungai@strathmore.edu', '0769855953', 'Lavington', '2025-05-04 20:33:05', NULL, 'active', NULL),
(13, 1, NULL, 'Miriam Wangari', 'miriam@gmail.com', '0726730582', 'Karen', '2025-05-06 10:58:29', NULL, 'active', NULL),
(14, 1, NULL, 'New Student', 'newstudent@gmail.com', '0712345678', 'Nakuru', '2025-05-06 12:00:52', NULL, 'active', NULL),
(15, 1, NULL, 'Peter Parker', 'spiderman@gmail.com', '0798576438', 'Queens, New York', '2025-05-06 16:07:13', NULL, 'active', NULL),
(16, 1, NULL, 'Lex Luthor', 'doomsday@gmail.com', '079865435', 'Billionaire Street', '2025-05-06 17:22:44', NULL, 'active', NULL),
(17, 1, NULL, 'Bruce Wayne', 'batman@gmail.com', '0798564890', 'The Batcave.', '2025-05-06 18:39:27', NULL, 'active', NULL),
(18, 1, NULL, 'Bruce Wayne', 'batman@gmail.com', '0798564890', 'The Batcave.', '2025-05-06 18:39:45', NULL, 'active', NULL),
(19, 1, NULL, 'Clark Kent', 'superman@gmail.com', '07956433567', 'Planet Krypton', '2025-05-19 22:04:41', NULL, 'active', NULL),
(20, 1, NULL, 'Barry Allen', 'flash@gmail.com', '0798567403', 'Seattle city', '2025-05-20 00:31:24', NULL, 'active', NULL),
(21, 1, NULL, 'Tony Stark', 'ironman@gmail.com', '0798568495', 'Stark Tower', '2025-05-21 16:07:26', NULL, 'active', NULL),
(22, 1, NULL, 'Pepper Pots', 'pepperpots@gmail.com', '0798456328', 'Ruaraka', '2025-05-22 11:16:42', NULL, 'active', NULL),
(23, 1, NULL, 'Bruce Banner', 'hulk@gmail.com', '07984527645', 'India, Mumbai', '2025-05-27 14:46:34', NULL, 'active', NULL),
(24, 1, NULL, 'Thor Odinson', 'Thor@gmail.com', '0712345678', 'Asgard', '2025-05-27 15:00:02', NULL, 'active', NULL),
(25, 1, NULL, 'Steve Rogers', 'captain@gmail.com', '0819274653', 'The USA.', '2025-05-28 16:07:49', NULL, 'active', NULL),
(26, 1, NULL, 'Example Person', 'example@gmail.com', '07986954322', 'Example, Location', '2025-06-02 14:15:14', NULL, 'active', NULL),
(27, 1, NULL, 'Stephen Strange', 'drstrange@gmail.com', '0743672898', 'Time, Stone.', '2025-06-11 00:44:53', NULL, 'active', NULL),
(28, 1, NULL, 'Matt Murdock', 'daredevil@gmail.com', '0786534923', 'New York, Hells Kitchen', '2025-06-13 14:06:02', NULL, 'active', NULL),
(29, 1, NULL, 'Homelander', 'homelander@gmal.com', '0786947253', 'Voight', '2025-06-13 15:55:13', NULL, 'active', NULL),
(30, 1, NULL, 'T\'challa', 'blackpanther@gmail.com', '07844326394', 'Wakanda, Africa', '2025-06-30 13:50:11', 6, 'active', NULL),
(31, 1, '31', 'Jake Peralta', 'brooklyn99@gmail.com', '0756947839', 'Brooklyn, New York', '2025-06-30 15:15:06', 6, 'active', NULL),
(34, 2, '001', 'William Macharia Mungai', 'william.mungai@strathmore.edu', '0769855953', '', '2025-07-01 15:19:26', NULL, 'active', NULL),
(35, 2, '002', 'Maryann Wanjiku', 'mary@gmail.com', '0718760077', 'Ruaka', '2025-07-01 23:29:56', NULL, 'active', NULL),
(36, 2, '003', 'John Gathua Mungai', 'john@gmail.com', '0790346966', 'Lavington', '2025-07-02 01:59:45', NULL, 'active', NULL),
(37, 3, '1234', 'William Mungai', 'mungaimacharia308@gmail.com', '0769855953', 'Ruaka', '2025-07-09 11:22:56', NULL, 'active', NULL),
(38, 3, '1235', 'Red John', 'redjohn@gmail.com', '07584969323', 'Joyland, Ruaka.', '2025-07-09 15:20:05', 9, 'active', NULL),
(39, 4, '12356', 'Red John', 'redjohn@gmail.com', '07584969323', '', '2025-07-09 16:39:26', NULL, 'active', NULL),
(40, 4, 'BM1250', 'William Macharia Mungai', 'william.mungai@strathmore.edu', '0769855953', 'Lavington', '2025-07-11 17:37:38', 17, 'active', NULL),
(41, 4, 'BM1251', 'John Gathua Mungai', 'john@gmail.com', '0790346966', 'Lavington', '2025-07-11 18:06:21', 19, 'active', NULL),
(42, 5, 'STM001', 'Ava Mwangi', 'Playgroup', '12 Apple St, Nairobi', '', '2025-07-12 01:12:28', 22, 'active', NULL),
(43, 5, 'STM002', 'Liam Otieno', 'PP1', '45 Garden Estate Rd,', '', '2025-07-12 01:12:28', 23, 'active', NULL),
(44, 5, 'STM003', 'Zuri Njeri', 'PP2', '7 Rose Ave, Kiambu', '', '2025-07-12 01:12:28', 24, 'active', NULL),
(45, 5, 'STM004', 'Ethan Kiptoo', 'Grade 1', '34 Kericho Lane, Eld', '', '2025-07-12 01:12:28', 25, 'active', NULL),
(46, 5, 'STM005', 'Amara Wanjiru', 'Grade 2', '23 Lavington Crescen', '', '2025-07-12 01:12:28', 26, 'active', NULL),
(47, 5, 'STM006', 'Jayden Mutiso', 'Grade 3', '10 Mlolongo Drive, M', '', '2025-07-12 01:12:28', 27, 'active', NULL),
(48, 5, 'STM007', 'Maya Chebet', 'Playgroup', '56 Moi Ave, Nakuru', '', '2025-07-12 01:12:28', 28, 'active', NULL),
(49, 5, 'STM008', 'Ryan Odhiambo', 'PP1', '22 Rongo Rd, Kisumu', '', '2025-07-12 01:12:28', 29, 'active', NULL),
(50, 5, 'STM009', 'Nia Muthoni', 'PP2', '11 Ngong View, Nairo', '', '2025-07-12 01:12:28', 30, 'active', NULL),
(51, 5, 'STM010', 'Elijah Ouma', 'Grade 1', '9 Kisii Heights, Kis', '', '2025-07-12 01:12:28', 31, 'active', NULL),
(52, 5, 'STM011', 'Layla Kamau', 'Grade 2', '70 Ridgeways Blvd, N', '', '2025-07-12 01:12:28', 32, 'active', NULL),
(53, 5, 'STM012', 'Noah Kibet', 'Grade 3', '88 Eldama Ravine Rd,', '', '2025-07-12 01:12:28', 33, 'active', NULL),
(54, 5, 'STM013', 'Sasha Njuguna', 'Playgroup', '14 Thome Estate, Nai', '', '2025-07-12 01:12:28', 34, 'active', NULL),
(55, 5, 'STM014', 'Caleb Mwende', 'PP1', '91 South C Rd, Nairo', '', '2025-07-12 01:12:28', 35, 'active', NULL),
(56, 5, 'STM015', 'Talia Wekesa', 'PP2', '67 Kizingo Crescent,', '', '2025-07-12 01:12:28', 36, 'active', NULL),
(57, 5, 'STM016', 'Ivy Kimani', 'Grade 1', '28 Umoja St, Nairobi', '', '2025-07-12 01:12:28', 37, 'active', NULL),
(58, 5, 'STM017', 'Nathan Barasa', 'Grade 2', '32 Kitale Lane, Tran', '', '2025-07-12 01:12:28', 38, 'active', NULL),
(59, 5, 'STM018', 'Aisha Kiplang?at', 'Grade 3', '13 Kiptagat Rd, Keri', '', '2025-07-12 01:12:28', 39, 'active', NULL),
(60, 5, 'STM019', 'Derrick Onyango', 'Playgroup', '17 Kisumu Ndogo, Kis', '', '2025-07-12 01:12:28', 40, 'active', NULL),
(61, 5, 'STM020', 'Hope Muriuki', 'PP1', '31 Limuru Rd, Nairob', '', '2025-07-12 01:12:28', 41, 'active', NULL),
(62, 5, 'STM021', 'Kevin Simiyu', 'PP2', '60 Kakamega Town, Ka', '', '2025-07-12 01:12:28', 42, 'active', NULL),
(63, 5, 'STM022', 'Sienna Gichuru', 'Grade 1', '21 Parklands Lane, N', '', '2025-07-12 01:12:28', 43, 'active', NULL),
(64, 5, 'STM023', 'Trevor Ndung?u', 'Grade 2', '15 Embu Avenue, Embu', '', '2025-07-12 01:12:28', 44, 'active', NULL),
(65, 5, 'STM024', 'Faith Korir', 'Grade 3', '6 Bomet Rd, Bomet', '', '2025-07-12 01:12:28', 45, 'active', NULL),
(66, 5, 'STM025', 'Brian Otiso', 'Playgroup', '5 Nyali Blvd, Mombas', '', '2025-07-12 01:12:28', 46, 'active', NULL),
(67, 5, 'STM026', 'Nicole Achieng', 'PP1', '44 Nyalenda, Kisumu', '', '2025-07-12 01:12:28', 47, 'active', NULL),
(68, 5, 'STM027', 'Alex Kilonzo', 'PP2', '18 Mlolongo Ave, Mac', '', '2025-07-12 01:12:28', 48, 'active', NULL),
(69, 5, 'STM028', 'Michelle Rono', 'Grade 1', '25 Rongai St, Kajiad', '', '2025-07-12 01:12:28', 49, 'active', NULL),
(70, 5, 'STM029', 'Tobias Kariuki', 'Grade 2', '39 Thika Greens, Thi', '', '2025-07-12 01:12:28', 50, 'active', NULL),
(71, 5, 'STM030', 'Grace Wanjala', 'Grade 3', '66 Kitengela Drive, ', '', '2025-07-12 01:12:28', 51, 'active', NULL),
(72, 5, 'STM032', 'William Mungai', 'williammungai10@gmail.com', '769855953', '910 James Gichuru, Lavington', '2025-07-17 00:49:58', 52, 'active', NULL),
(73, 6, 'STU0001', 'James Kariuki', 'james.kariuki@email.com', '712345678', 'Nairobi, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(74, 6, 'STU0002', 'Alice Wambui', 'alice.wambui@email.com', '712345679', 'Nakuru, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(75, 6, 'STU0003', 'Brian Otieno', 'brian.otieno@email.com', '712345680', 'Kisumu, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(76, 6, 'STU0004', 'Cynthia Mwende', 'cynthia.mwende@email.com', '712345681', 'Mombasa, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(77, 6, 'STU0005', 'Daniel Kimani', 'daniel.kimani@email.com', '712345682', 'Thika, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(78, 6, 'STU0006', 'Emily Achieng', 'emily.achieng@email.com', '712345683', 'Kisii, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(79, 6, 'STU0007', 'Felix Mutua', 'felix.mutua@email.com', '712345684', 'Machakos, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(80, 6, 'STU0008', 'Grace Njeri', 'grace.njeri@email.com', '712345685', 'Nyeri, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(81, 6, 'STU0009', 'Henry Kiplangat', 'henry.kiplangat@email.com', '712345686', 'Eldoret, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(82, 6, 'STU0010', 'Ivy Chebet', 'ivy.chebet@email.com', '712345687', 'Kericho, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(83, 6, 'STU0011', 'John Maina', 'john.maina@email.com', '712345688', 'Nairobi, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(84, 6, 'STU0012', 'Karen Waithira', 'karen.waithira@email.com', '712345689', 'Murang\'a, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(85, 6, 'STU0013', 'Leon Otieno', 'leon.otieno@email.com', '712345690', 'Kisumu, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(86, 6, 'STU0014', 'Mary Wanjiku', 'mary.wanjiku@email.com', '712345691', 'Kiambu, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(87, 6, 'STU0015', 'Noah Kiptoo', 'noah.kiptoo@email.com', '712345692', 'Eldoret, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(88, 6, 'STU0016', 'Olivia Nyambura', 'olivia.nyambura@email.com', '712345693', 'Nyandarua, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(89, 6, 'STU0017', 'Peter Njoroge', 'peter.njoroge@email.com', '712345694', 'Laikipia, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(90, 6, 'STU0018', 'Queen Akinyi', 'queen.akinyi@email.com', '712345695', 'Siaya, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(91, 6, 'STU0019', 'Ryan Omondi', 'ryan.omondi@email.com', '712345696', 'Homa Bay, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(92, 6, 'STU0020', 'Sharon Nduta', 'sharon.nduta@email.com', '712345697', 'Nyeri, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(93, 6, 'STU0021', 'Tom Barasa', 'tom.barasa@email.com', '712345698', 'Bungoma, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(94, 6, 'STU0022', 'Vanessa Waceke', 'vanessa.waceke@email.com', '712345699', 'Nairobi, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(95, 6, 'STU0023', 'William Kiprono', 'william.kiprono@email.com', '712345700', 'Bomet, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(96, 6, 'STU0024', 'Xenia Moraa', 'xenia.moraa@email.com', '712345701', 'Kisii, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(97, 6, 'STU0025', 'Yusuf Abdi', 'yusuf.abdi@email.com', '712345702', 'Garissa, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(98, 6, 'STU0026', 'Zara Mwikali', 'zara.mwikali@email.com', '712345703', 'Kitui, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(99, 6, 'STU0027', 'Arnold Kipchoge', 'arnold.kipchoge@email.com', '712345704', 'Nandi, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(100, 6, 'STU0028', 'Bella Anyango', 'bella.anyango@email.com', '712345705', 'Kisumu, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(101, 6, 'STU0029', 'Calvin Wekesa', 'calvin.wekesa@email.com', '712345706', 'Kakamega, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(102, 6, 'STU0030', 'Diana Atieno', 'diana.atieno@email.com', '712345707', 'Migori, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(103, 6, 'STU0031', 'Eric Njuguna', 'eric.njuguna@email.com', '712345708', 'Nairobi, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(104, 6, 'STU0032', 'Faith Muthoni', 'faith.muthoni@email.com', '712345709', 'Nyeri, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(105, 6, 'STU0033', 'George Lumumba', 'george.lumumba@email.com', '712345710', 'Kisumu, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(106, 6, 'STU0034', 'Hannah Wairimu', 'hannah.wairimu@email.com', '712345711', 'Murang\'a, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(107, 6, 'STU0035', 'Ian Mbugua', 'ian.mbugua@email.com', '712345712', 'Kiambu, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(108, 6, 'STU0036', 'Janet Chebet', 'janet.chebet@email.com', '712345713', 'Kericho, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(109, 6, 'STU0037', 'Kevin Kipkemboi', 'kevin.kipkemboi@email.com', '712345714', 'Eldoret, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(110, 6, 'STU0038', 'Linda Nyaguthii', 'linda.nyaguthii@email.com', '712345715', 'Laikipia, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(111, 6, 'STU0039', 'Mike Ochieng', 'mike.ochieng@email.com', '712345716', 'Nairobi, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(112, 6, 'STU0040', 'Nancy Auma', 'nancy.auma@email.com', '712345717', 'Kisumu, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(113, 6, 'STU0041', 'Oscar Kirui', 'oscar.kirui@email.com', '712345718', 'Bomet, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(114, 6, 'STU0042', 'Patricia Naliaka', 'patricia.naliaka@email.com', '712345719', 'Bungoma, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(115, 6, 'STU0043', 'Quincy Wambua', 'quincy.wambua@email.com', '712345720', 'Machakos, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(116, 6, 'STU0044', 'Rachel Wairimu', 'rachel.wairimu@email.com', '712345721', 'Nairobi, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(117, 6, 'STU0045', 'Steve Okoth', 'steve.okoth@email.com', '712345722', 'Siaya, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(118, 6, 'STU0046', 'Tina Chepchumba', 'tina.chepchumba@email.com', '712345723', 'Uasin Gishu, Kenya', '2025-07-17 01:00:20', 55, 'active', NULL),
(119, 6, 'STU0047', 'Umar Hassan', 'umar.hassan@email.com', '712345724', 'Mandera, Kenya', '2025-07-17 01:00:20', 56, 'active', NULL),
(120, 6, 'STU0048', 'Violet Achieng', 'violet.achieng@email.com', '712345725', 'Kisumu, Kenya', '2025-07-17 01:00:20', 57, 'active', NULL),
(121, 6, 'STU0049', 'Wesley Muli', 'wesley.muli@email.com', '712345726', 'Kitui, Kenya', '2025-07-17 01:00:20', 53, 'active', NULL),
(122, 6, 'STU0050', 'Zainab Yusuf', 'zainab.yusuf@email.com', '712345727', 'Wajir, Kenya', '2025-07-17 01:00:20', 54, 'active', NULL),
(123, 7, 'S001', 'Agnes Kosgey', 'agnes.kosgey@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 58, 'active', NULL),
(124, 7, 'S002', 'Stephen Lagat', 'stephen.lagat@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-06 17:30:22', 58, 'active', NULL),
(125, 7, 'S003', 'Grace Odhiambo', 'grace.odhiambo@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 58, 'active', NULL),
(126, 7, 'S004', 'Agnes Ochieng', 'agnes.ochieng@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-06 17:30:22', 59, 'active', NULL),
(127, 7, 'S005', 'Peter Korir', 'peter.korir@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-06 17:30:22', 59, 'active', NULL),
(128, 7, 'S006', 'Samuel Kariuki', 'samuel.kariuki@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 60, 'active', NULL),
(129, 7, 'S007', 'Allan Nyongesa', 'allan.nyongesa@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 60, 'active', NULL),
(130, 7, 'S008', 'Anthony Kiplagat', 'anthony.kiplagat@schoolmail.com', '769855953', 'Kakamega, Lurambi', '2025-08-06 17:30:22', 61, 'active', NULL),
(131, 7, 'S009', 'Elijah Makori', 'elijah.makori@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-06 17:30:22', 62, 'active', NULL),
(132, 7, 'S010', 'Sarah Maraga', 'sarah.maraga@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-06 17:30:22', 62, 'active', NULL),
(133, 7, 'S011', 'Florence Mutinda', 'florence.mutinda@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 60, 'active', NULL),
(134, 7, 'S012', 'Mercy Njenga', 'mercy.njenga@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 61, 'active', NULL),
(135, 7, 'S013', 'Cynthia Kihara', 'cynthia.kihara@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 60, 'active', NULL),
(136, 7, 'S014', 'Kevin Kiptoo', 'kevin.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 62, 'active', NULL),
(137, 7, 'S015', 'Beatrice Mboya', 'beatrice.mboya@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 63, 'active', NULL),
(138, 7, 'S016', 'James Nyongesa', 'james.nyongesa@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-06 17:30:22', 60, 'active', NULL),
(139, 7, 'S017', 'David Muli', 'david.muli@schoolmail.com', '769855953', 'Meru, Makutano', '2025-08-06 17:30:22', 60, 'active', NULL),
(140, 7, 'S018', 'Caroline Kiptoo', 'caroline.kiptoo@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 58, 'active', NULL),
(141, 7, 'S019', 'Michael Kiplangat', 'michael.kiplangat@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-06 17:30:22', 60, 'active', NULL),
(142, 7, 'S020', 'Diana Muthoni', 'diana.muthoni@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 59, 'active', NULL),
(143, 7, 'S021', 'Philip Kamau', 'philip.kamau@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 61, 'active', NULL),
(144, 7, 'S022', 'John Kiplagat', 'john.kiplagat@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-06 17:30:22', 61, 'active', NULL),
(145, 7, 'S023', 'Ruth Wekesa', 'ruth.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-06 17:30:22', 60, 'active', NULL),
(146, 7, 'S024', 'Joseph Cheruiyot', 'joseph.cheruiyot@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-06 17:30:22', 58, 'active', NULL),
(147, 7, 'S025', 'Naomi Kiplangat', 'naomi.kiplangat@schoolmail.com', '769855953', 'Kiambu, Ruiru', '2025-08-06 17:30:22', 58, 'active', NULL),
(148, 7, 'S026', 'Mercy Muli', 'mercy.muli@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 63, 'active', NULL),
(149, 7, 'S027', 'Elijah Wekundu', 'elijah.wekundu@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 60, 'active', NULL),
(150, 7, 'S028', 'David Kiptoo', 'david.kiptoo@schoolmail.com', '769855953', 'Machakos, Mua Hills', '2025-08-06 17:30:22', 60, 'active', NULL),
(151, 7, 'S029', 'Rose Mutiso', 'rose.mutiso@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-06 17:30:22', 60, 'active', NULL),
(152, 7, 'S030', 'John Mboya', 'john.mboya@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-06 17:30:22', 59, 'active', NULL),
(153, 7, 'S031', 'Lucy Maina', 'lucy.maina@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 63, 'active', NULL),
(154, 7, 'S032', 'Violet Kiptoo', 'violet.kiptoo@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 59, 'active', NULL),
(155, 7, 'S033', 'Joseph Kiplagat', 'joseph.kiplagat@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-06 17:30:22', 59, 'active', NULL),
(156, 7, 'S034', 'Naomi Nyongesa', 'naomi.nyongesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-06 17:30:22', 61, 'active', NULL),
(157, 7, 'S035', 'Kelvin Kiptoo', 'kelvin.kiptoo@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-06 17:30:22', 59, 'active', NULL),
(158, 7, 'S036', 'Florence Kiplangat', 'florence.kiplangat@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 58, 'active', NULL),
(159, 7, 'S037', 'Anthony Akinyi', 'anthony.akinyi@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 61, 'active', NULL),
(160, 7, 'S038', 'George Odhiambo', 'george.odhiambo@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-06 17:30:22', 59, 'active', NULL),
(161, 7, 'S039', 'Alex Wekesa', 'alex.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-06 17:30:22', 62, 'active', NULL),
(162, 7, 'S040', 'Lucy Ndegwa', 'lucy.ndegwa@schoolmail.com', '769855953', 'Meru, Makutano', '2025-08-06 17:30:22', 63, 'active', NULL),
(163, 7, 'S041', 'Jane Kiptoo', 'jane.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 60, 'active', NULL),
(164, 7, 'S042', 'Ruth Kiplagat', 'ruth.kiplagat@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 60, 'active', NULL),
(165, 7, 'S043', 'George Chepkoech', 'george.chepkoech@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 63, 'active', NULL),
(166, 7, 'S044', 'Mercy Mboya', 'mercy.mboya@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 58, 'active', NULL),
(167, 7, 'S045', 'Joy Barasa', 'joy.barasa@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 59, 'active', NULL),
(168, 7, 'S046', 'Philip Kiplagat', 'philip.kiplagat@schoolmail.com', '769855953', 'Kerugoya, Kutus', '2025-08-06 17:30:22', 63, 'active', NULL),
(169, 7, 'S047', 'Caroline Muli', 'caroline.muli@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-06 17:30:22', 58, 'active', NULL),
(170, 7, 'S048', 'Edwin Lagat', 'edwin.lagat@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 61, 'active', NULL),
(171, 7, 'S049', 'Ruth Wairimu', 'ruth.wairimu@schoolmail.com', '769855953', 'Thika, Makongeni', '2025-08-06 17:30:22', 63, 'active', NULL),
(172, 7, 'S050', 'Victor Kiplangat', 'victor.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 63, 'active', NULL),
(173, 7, 'S051', 'John Mutua', 'john.mutua@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-06 17:30:22', 59, 'active', NULL),
(174, 7, 'S052', 'Lucy Wambui', 'lucy.wambui@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-06 17:30:22', 62, 'active', NULL),
(175, 7, 'S053', 'Anthony Korir', 'anthony.korir@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-06 17:30:22', 60, 'active', NULL),
(176, 7, 'S054', 'Alex Njoroge', 'alex.njoroge@schoolmail.com', '769855953', 'Kerugoya, Kutus', '2025-08-06 17:30:22', 62, 'active', NULL),
(177, 7, 'S055', 'George Kariuki', 'george.kariuki@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 62, 'active', NULL),
(178, 7, 'S056', 'Jane Kosgey', 'jane.kosgey@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 60, 'active', NULL),
(179, 7, 'S057', 'Sarah Macharia', 'sarah.macharia@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 60, 'active', NULL),
(180, 7, 'S058', 'Michael Wekesa', 'michael.wekesa@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-06 17:30:22', 59, 'active', NULL),
(181, 7, 'S059', 'Caroline Wairimu', 'caroline.wairimu@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-06 17:30:22', 61, 'active', NULL),
(182, 7, 'S060', 'Mercy Njenga', 'mercy.njenga@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 61, 'active', NULL),
(183, 7, 'S061', 'Beatrice Achieng', 'beatrice.achieng@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-06 17:30:22', 62, 'active', NULL),
(184, 7, 'S062', 'George Kiptoo', 'george.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 61, 'active', NULL),
(185, 7, 'S063', 'Lucy Lagat', 'lucy.lagat@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-06 17:30:22', 63, 'active', NULL),
(186, 7, 'S064', 'Ruth Ndegwa', 'ruth.ndegwa@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 62, 'active', NULL),
(187, 7, 'S065', 'Jane Mboya', 'jane.mboya@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 63, 'active', NULL),
(188, 7, 'S066', 'Kelvin Maraga', 'kelvin.maraga@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 58, 'active', NULL),
(189, 7, 'S067', 'Florence Kihara', 'florence.kihara@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-06 17:30:22', 63, 'active', NULL),
(190, 7, 'S068', 'Joseph Wekundu', 'joseph.wekundu@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-06 17:30:22', 61, 'active', NULL),
(191, 7, 'S069', 'Elijah Nyambura', 'elijah.nyambura@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 61, 'active', NULL),
(192, 7, 'S070', 'Agnes Onyiso', 'agnes.onyiso@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 62, 'active', NULL),
(193, 7, 'S071', 'Samuel Njenga', 'samuel.njenga@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-06 17:30:22', 60, 'active', NULL),
(194, 7, 'S072', 'Philip Kiplagat', 'philip.kiplagat@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-06 17:30:22', 58, 'active', NULL),
(195, 7, 'S073', 'Allan Mutiso', 'allan.mutiso@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 62, 'active', NULL),
(196, 7, 'S074', 'Mercy Kiptoo', 'mercy.kiptoo@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 58, 'active', NULL),
(197, 7, 'S075', 'George Kiplangat', 'george.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 58, 'active', NULL),
(198, 7, 'S076', 'Peter Wekesa', 'peter.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-06 17:30:22', 63, 'active', NULL),
(199, 7, 'S077', 'Elijah Macharia', 'elijah.macharia@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-06 17:30:22', 61, 'active', NULL),
(200, 7, 'S078', 'Diana Nyambura', 'diana.nyambura@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 62, 'active', NULL),
(201, 7, 'S079', 'Lucy Chepkoech', 'lucy.chepkoech@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 60, 'active', NULL),
(202, 7, 'S080', 'Florence Wekesa', 'florence.wekesa@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 59, 'active', NULL),
(203, 7, 'S081', 'Beatrice Lagat', 'beatrice.lagat@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 58, 'active', NULL),
(204, 7, 'S082', 'James Odhiambo', 'james.odhiambo@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 58, 'active', NULL),
(205, 7, 'S083', 'Jane Ndegwa', 'jane.ndegwa@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-06 17:30:22', 63, 'active', NULL),
(206, 7, 'S084', 'David Kiptoo', 'david.kiptoo@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-06 17:30:22', 58, 'active', NULL),
(207, 7, 'S085', 'Violet Barasa', 'violet.barasa@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 59, 'active', NULL),
(208, 7, 'S086', 'Michael Kiplangat', 'michael.kiplangat@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-06 17:30:22', 58, 'active', NULL),
(209, 7, 'S087', 'Stephen Kiplangat', 'stephen.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 60, 'active', NULL),
(210, 7, 'S088', 'Lucy Wekundu', 'lucy.wekundu@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 63, 'active', NULL),
(211, 7, 'S089', 'Sarah Kiptoo', 'sarah.kiptoo@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 61, 'active', NULL),
(212, 7, 'S090', 'Peter Kiplangat', 'peter.kiplangat@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-06 17:30:22', 62, 'active', NULL),
(213, 7, 'S091', 'Victor Kiptoo', 'victor.kiptoo@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-06 17:30:22', 61, 'active', NULL),
(214, 7, 'S092', 'Joy Lagat', 'joy.lagat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 62, 'active', NULL),
(215, 7, 'S093', 'Lucy Kosgey', 'lucy.kosgey@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 58, 'active', NULL),
(216, 7, 'S094', 'Michael Korir', 'michael.korir@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 62, 'active', NULL),
(217, 7, 'S095', 'Florence Kiptoo', 'florence.kiptoo@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 63, 'active', NULL),
(218, 7, 'S096', 'Diana Kiptoo', 'diana.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-06 17:30:22', 60, 'active', NULL),
(219, 7, 'S097', 'Samuel Kiplangat', 'samuel.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-06 17:30:22', 58, 'active', NULL),
(220, 7, 'S098', 'Agnes Nyambura', 'agnes.nyambura@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-06 17:30:22', 60, 'active', NULL),
(221, 7, 'S099', 'Peter Nyongesa', 'peter.nyongesa@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-06 17:30:22', 58, 'active', NULL),
(222, 7, 'S100', 'Caroline Kosgey', 'caroline.kosgey@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-06 17:30:22', 63, 'active', NULL),
(224, 8, 'MM1200', 'Agnes Kosgey', 'agnes.kosgey@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 65, 'active', NULL),
(225, 8, 'MM1201', 'Stephen Lagat', 'stephen.lagat@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 11:57:52', 65, 'active', NULL),
(226, 8, 'MM1202', 'Grace Odhiambo', 'grace.odhiambo@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 65, 'active', NULL),
(227, 8, 'MM1203', 'Agnes Ochieng', 'agnes.ochieng@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 11:57:52', 66, 'active', NULL),
(228, 8, 'MM1204', 'Peter Korir', 'peter.korir@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 11:57:52', 66, 'active', NULL),
(229, 8, 'MM1205', 'Samuel Kariuki', 'samuel.kariuki@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 67, 'active', NULL),
(230, 8, 'MM1206', 'Allan Nyongesa', 'allan.nyongesa@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 67, 'active', NULL),
(231, 8, 'MM1207', 'Anthony Kiplagat', 'anthony.kiplagat@schoolmail.com', '769855953', 'Kakamega, Lurambi', '2025-08-12 11:57:52', 68, 'active', NULL),
(232, 8, 'MM1208', 'Elijah Makori', 'elijah.makori@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 11:57:52', 69, 'active', NULL),
(233, 8, 'MM1209', 'Sarah Maraga', 'sarah.maraga@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 11:57:52', 69, 'active', NULL),
(234, 8, 'MM1210', 'Florence Mutinda', 'florence.mutinda@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 67, 'active', NULL),
(235, 8, 'MM1211', 'Mercy Njenga', 'mercy.njenga@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 68, 'active', NULL),
(236, 8, 'MM1212', 'Cynthia Kihara', 'cynthia.kihara@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 67, 'active', NULL),
(237, 8, 'MM1213', 'Kevin Kiptoo', 'kevin.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 69, 'active', NULL),
(238, 8, 'MM1214', 'Beatrice Mboya', 'beatrice.mboya@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 70, 'active', NULL),
(239, 8, 'MM1215', 'James Nyongesa', 'james.nyongesa@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-12 11:57:52', 67, 'active', NULL),
(240, 8, 'MM1216', 'David Muli', 'david.muli@schoolmail.com', '769855953', 'Meru, Makutano', '2025-08-12 11:57:52', 67, 'active', NULL),
(241, 8, 'MM1217', 'Caroline Kiptoo', 'caroline.kiptoo@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 65, 'active', NULL),
(242, 8, 'MM1218', 'Michael Kiplangat', 'michael.kiplangat@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 11:57:52', 67, 'active', NULL),
(243, 8, 'MM1219', 'Diana Muthoni', 'diana.muthoni@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 66, 'active', NULL),
(244, 8, 'MM1220', 'Philip Kamau', 'philip.kamau@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 68, 'active', NULL),
(245, 8, 'MM1221', 'John Kiplagat', 'john.kiplagat@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 11:57:52', 68, 'active', NULL),
(246, 8, 'MM1222', 'Ruth Wekesa', 'ruth.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 11:57:52', 67, 'active', NULL),
(247, 8, 'MM1223', 'Joseph Cheruiyot', 'joseph.cheruiyot@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 11:57:52', 65, 'active', NULL),
(248, 8, 'MM1224', 'Naomi Kiplangat', 'naomi.kiplangat@schoolmail.com', '769855953', 'Kiambu, Ruiru', '2025-08-12 11:57:52', 65, 'active', NULL),
(249, 8, 'MM1225', 'Mercy Muli', 'mercy.muli@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 70, 'active', NULL),
(250, 8, 'MM1226', 'Elijah Wekundu', 'elijah.wekundu@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 67, 'active', NULL),
(251, 8, 'MM1227', 'David Kiptoo', 'david.kiptoo@schoolmail.com', '769855953', 'Machakos, Mua Hills', '2025-08-12 11:57:52', 67, 'active', NULL),
(252, 8, 'MM1228', 'Rose Mutiso', 'rose.mutiso@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-12 11:57:52', 67, 'active', NULL),
(253, 8, 'MM1229', 'John Mboya', 'john.mboya@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 11:57:52', 66, 'active', NULL),
(254, 8, 'MM1230', 'Lucy Maina', 'lucy.maina@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 70, 'active', NULL),
(255, 8, 'MM1231', 'Violet Kiptoo', 'violet.kiptoo@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 66, 'active', NULL),
(256, 8, 'MM1232', 'Joseph Kiplagat', 'joseph.kiplagat@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 11:57:52', 66, 'active', NULL),
(257, 8, 'MM1233', 'Naomi Nyongesa', 'naomi.nyongesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 11:57:52', 68, 'active', NULL),
(258, 8, 'MM1234', 'Kelvin Kiptoo', 'kelvin.kiptoo@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 11:57:52', 66, 'active', NULL),
(259, 8, 'MM1235', 'Florence Kiplangat', 'florence.kiplangat@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 65, 'active', NULL),
(260, 8, 'MM1236', 'Anthony Akinyi', 'anthony.akinyi@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 68, 'active', NULL),
(261, 8, 'MM1237', 'George Odhiambo', 'george.odhiambo@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 11:57:52', 66, 'active', NULL),
(262, 8, 'MM1238', 'Alex Wekesa', 'alex.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 11:57:52', 69, 'active', NULL),
(263, 8, 'MM1239', 'Lucy Ndegwa', 'lucy.ndegwa@schoolmail.com', '769855953', 'Meru, Makutano', '2025-08-12 11:57:52', 70, 'active', NULL),
(264, 8, 'MM1240', 'Jane Kiptoo', 'jane.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 67, 'active', NULL),
(265, 8, 'MM1241', 'Ruth Kiplagat', 'ruth.kiplagat@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 67, 'active', NULL),
(266, 8, 'MM1242', 'George Chepkoech', 'george.chepkoech@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 70, 'active', NULL),
(267, 8, 'MM1243', 'Mercy Mboya', 'mercy.mboya@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 65, 'active', NULL),
(268, 8, 'MM1244', 'Joy Barasa', 'joy.barasa@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 66, 'active', NULL),
(269, 8, 'MM1245', 'Philip Kiplagat', 'philip.kiplagat@schoolmail.com', '769855953', 'Kerugoya, Kutus', '2025-08-12 11:57:52', 70, 'active', NULL),
(270, 8, 'MM1246', 'Caroline Muli', 'caroline.muli@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 11:57:52', 65, 'active', NULL),
(271, 8, 'MM1247', 'Edwin Lagat', 'edwin.lagat@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 68, 'active', NULL),
(272, 8, 'MM1248', 'Ruth Wairimu', 'ruth.wairimu@schoolmail.com', '769855953', 'Thika, Makongeni', '2025-08-12 11:57:52', 70, 'active', NULL),
(273, 8, 'MM1249', 'Victor Kiplangat', 'victor.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 70, 'active', NULL),
(274, 8, 'MM1250', 'John Mutua', 'john.mutua@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-12 11:57:52', 66, 'active', NULL),
(275, 8, 'MM1251', 'Lucy Wambui', 'lucy.wambui@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 11:57:52', 69, 'active', NULL),
(276, 8, 'MM1252', 'Anthony Korir', 'anthony.korir@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 11:57:52', 67, 'active', NULL),
(277, 8, 'MM1253', 'Alex Njoroge', 'alex.njoroge@schoolmail.com', '769855953', 'Kerugoya, Kutus', '2025-08-12 11:57:52', 69, 'active', NULL),
(278, 8, 'MM1254', 'George Kariuki', 'george.kariuki@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 69, 'active', NULL),
(279, 8, 'MM1255', 'Jane Kosgey', 'jane.kosgey@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 67, 'active', NULL),
(280, 8, 'MM1256', 'Sarah Macharia', 'sarah.macharia@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 67, 'active', NULL),
(281, 8, 'MM1257', 'Michael Wekesa', 'michael.wekesa@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 11:57:52', 66, 'active', NULL),
(282, 8, 'MM1258', 'Caroline Wairimu', 'caroline.wairimu@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 11:57:52', 68, 'active', NULL),
(283, 8, 'MM1259', 'Mercy Njenga', 'mercy.njenga@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 68, 'active', NULL),
(284, 8, 'MM1260', 'Beatrice Achieng', 'beatrice.achieng@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 11:57:52', 69, 'active', NULL),
(285, 8, 'MM1261', 'George Kiptoo', 'george.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 68, 'active', NULL),
(286, 8, 'MM1262', 'Lucy Lagat', 'lucy.lagat@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 11:57:52', 70, 'active', NULL),
(287, 8, 'MM1263', 'Ruth Ndegwa', 'ruth.ndegwa@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 69, 'active', NULL),
(288, 8, 'MM1264', 'Jane Mboya', 'jane.mboya@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 70, 'active', NULL),
(289, 8, 'MM1265', 'Kelvin Maraga', 'kelvin.maraga@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 65, 'active', NULL),
(290, 8, 'MM1266', 'Florence Kihara', 'florence.kihara@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 11:57:52', 70, 'active', NULL),
(291, 8, 'MM1267', 'Joseph Wekundu', 'joseph.wekundu@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 11:57:52', 68, 'active', NULL),
(292, 8, 'MM1268', 'Elijah Nyambura', 'elijah.nyambura@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 68, 'active', NULL),
(293, 8, 'MM1269', 'Agnes Onyiso', 'agnes.onyiso@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 69, 'active', NULL),
(294, 8, 'MM1270', 'Samuel Njenga', 'samuel.njenga@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 11:57:52', 67, 'active', NULL),
(295, 8, 'MM1271', 'Philip Kiplagat', 'philip.kiplagat@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 11:57:52', 65, 'active', NULL),
(296, 8, 'MM1272', 'Allan Mutiso', 'allan.mutiso@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 69, 'active', NULL),
(297, 8, 'MM1273', 'Mercy Kiptoo', 'mercy.kiptoo@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 65, 'active', NULL),
(298, 8, 'MM1274', 'George Kiplangat', 'george.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 65, 'active', NULL),
(299, 8, 'MM1275', 'Peter Wekesa', 'peter.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 11:57:52', 70, 'active', NULL),
(300, 8, 'MM1276', 'Elijah Macharia', 'elijah.macharia@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 11:57:52', 68, 'active', NULL),
(301, 8, 'MM1277', 'Diana Nyambura', 'diana.nyambura@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 69, 'active', NULL),
(302, 8, 'MM1278', 'Lucy Chepkoech', 'lucy.chepkoech@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 67, 'active', NULL),
(303, 8, 'MM1279', 'Florence Wekesa', 'florence.wekesa@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 66, 'active', NULL),
(304, 8, 'MM1280', 'Beatrice Lagat', 'beatrice.lagat@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 65, 'active', NULL),
(305, 8, 'MM1281', 'James Odhiambo', 'james.odhiambo@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 65, 'active', NULL),
(306, 8, 'MM1282', 'Jane Ndegwa', 'jane.ndegwa@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 11:57:52', 70, 'active', NULL),
(307, 8, 'MM1283', 'David Kiptoo', 'david.kiptoo@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 11:57:52', 65, 'active', NULL),
(308, 8, 'MM1284', 'Violet Barasa', 'violet.barasa@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 66, 'active', NULL),
(309, 8, 'MM1285', 'Michael Kiplangat', 'michael.kiplangat@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 11:57:52', 65, 'active', NULL),
(310, 8, 'MM1286', 'Stephen Kiplangat', 'stephen.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 67, 'active', NULL),
(311, 8, 'MM1287', 'Lucy Wekundu', 'lucy.wekundu@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 70, 'active', NULL),
(312, 8, 'MM1288', 'Sarah Kiptoo', 'sarah.kiptoo@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 68, 'active', NULL),
(313, 8, 'MM1289', 'Peter Kiplangat', 'peter.kiplangat@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 11:57:52', 69, 'active', NULL),
(314, 8, 'MM1290', 'Victor Kiptoo', 'victor.kiptoo@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 11:57:52', 68, 'active', NULL),
(315, 8, 'MM1291', 'Joy Lagat', 'joy.lagat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 69, 'active', NULL),
(316, 8, 'MM1292', 'Lucy Kosgey', 'lucy.kosgey@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 65, 'active', NULL),
(317, 8, 'MM1293', 'Michael Korir', 'michael.korir@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 69, 'active', NULL),
(318, 8, 'MM1294', 'Florence Kiptoo', 'florence.kiptoo@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 70, 'active', NULL),
(319, 8, 'MM1295', 'Diana Kiptoo', 'diana.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 11:57:52', 67, 'active', NULL),
(320, 8, 'MM1296', 'Samuel Kiplangat', 'samuel.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 11:57:52', 65, 'active', NULL),
(321, 8, 'MM1297', 'Agnes Nyambura', 'agnes.nyambura@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 11:57:52', 67, 'active', NULL),
(322, 8, 'MM1298', 'Peter Nyongesa', 'peter.nyongesa@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 11:57:52', 65, 'active', NULL),
(323, 8, 'MM1299', 'Caroline Kosgey', 'caroline.kosgey@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 11:57:52', 70, 'active', NULL),
(324, 9, 'OFFICE251', 'Agnes Kosgey', 'agnes.kosgey@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 71, 'active', NULL),
(325, 9, 'OFFICE252', 'Stephen Lagat', 'stephen.lagat@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 14:02:22', 71, 'active', NULL),
(326, 9, 'OFFICE253', 'Grace Odhiambo', 'grace.odhiambo@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 71, 'active', NULL),
(327, 9, 'OFFICE254', 'Agnes Ochieng', 'agnes.ochieng@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 14:02:22', 72, 'active', NULL),
(328, 9, 'OFFICE255', 'Peter Korir', 'peter.korir@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 14:02:22', 72, 'active', NULL),
(329, 9, 'OFFICE256', 'Samuel Kariuki', 'samuel.kariuki@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 73, 'active', NULL),
(330, 9, 'OFFICE257', 'Allan Nyongesa', 'allan.nyongesa@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 73, 'active', NULL),
(331, 9, 'OFFICE258', 'Anthony Kiplagat', 'anthony.kiplagat@schoolmail.com', '769855953', 'Kakamega, Lurambi', '2025-08-12 14:02:22', 74, 'active', NULL),
(332, 9, 'OFFICE259', 'Elijah Makori', 'elijah.makori@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 14:02:22', 75, 'active', NULL),
(333, 9, 'OFFICE260', 'Sarah Maraga', 'sarah.maraga@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 14:02:22', 75, 'active', NULL),
(334, 9, 'OFFICE261', 'Florence Mutinda', 'florence.mutinda@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 73, 'active', NULL),
(335, 9, 'OFFICE262', 'Mercy Njenga', 'mercy.njenga@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 74, 'active', NULL),
(336, 9, 'OFFICE263', 'Cynthia Kihara', 'cynthia.kihara@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 73, 'active', NULL),
(337, 9, 'OFFICE264', 'Kevin Kiptoo', 'kevin.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 75, 'active', NULL),
(338, 9, 'OFFICE265', 'Beatrice Mboya', 'beatrice.mboya@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 76, 'active', NULL),
(339, 9, 'OFFICE266', 'James Nyongesa', 'james.nyongesa@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-12 14:02:22', 73, 'active', NULL),
(340, 9, 'OFFICE267', 'David Muli', 'david.muli@schoolmail.com', '769855953', 'Meru, Makutano', '2025-08-12 14:02:22', 73, 'active', NULL),
(341, 9, 'OFFICE268', 'Caroline Kiptoo', 'caroline.kiptoo@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 71, 'active', NULL),
(342, 9, 'OFFICE269', 'Michael Kiplangat', 'michael.kiplangat@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 14:02:22', 73, 'active', NULL),
(343, 9, 'OFFICE270', 'Diana Muthoni', 'diana.muthoni@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 72, 'active', NULL),
(344, 9, 'OFFICE271', 'Philip Kamau', 'philip.kamau@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 74, 'active', NULL),
(345, 9, 'OFFICE272', 'John Kiplagat', 'john.kiplagat@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 14:02:22', 74, 'active', NULL),
(346, 9, 'OFFICE273', 'Ruth Wekesa', 'ruth.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 14:02:22', 73, 'active', NULL),
(347, 9, 'OFFICE274', 'Joseph Cheruiyot', 'joseph.cheruiyot@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 14:02:22', 71, 'active', NULL),
(348, 9, 'OFFICE275', 'Naomi Kiplangat', 'naomi.kiplangat@schoolmail.com', '769855953', 'Kiambu, Ruiru', '2025-08-12 14:02:22', 71, 'active', NULL),
(349, 9, 'OFFICE276', 'Mercy Muli', 'mercy.muli@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 76, 'active', NULL),
(350, 9, 'OFFICE277', 'Elijah Wekundu', 'elijah.wekundu@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 73, 'active', NULL),
(351, 9, 'OFFICE278', 'David Kiptoo', 'david.kiptoo@schoolmail.com', '769855953', 'Machakos, Mua Hills', '2025-08-12 14:02:22', 73, 'active', NULL),
(352, 9, 'OFFICE279', 'Rose Mutiso', 'rose.mutiso@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-12 14:02:22', 73, 'active', NULL),
(353, 9, 'OFFICE280', 'John Mboya', 'john.mboya@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 14:02:22', 72, 'active', NULL),
(354, 9, 'OFFICE281', 'Lucy Maina', 'lucy.maina@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 76, 'active', NULL),
(355, 9, 'OFFICE282', 'Violet Kiptoo', 'violet.kiptoo@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 72, 'active', NULL),
(356, 9, 'OFFICE283', 'Joseph Kiplagat', 'joseph.kiplagat@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 14:02:22', 72, 'active', NULL),
(357, 9, 'OFFICE284', 'Naomi Nyongesa', 'naomi.nyongesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 14:02:22', 74, 'active', NULL),
(358, 9, 'OFFICE285', 'Kelvin Kiptoo', 'kelvin.kiptoo@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 14:02:22', 72, 'active', NULL),
(359, 9, 'OFFICE286', 'Florence Kiplangat', 'florence.kiplangat@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 71, 'active', NULL),
(360, 9, 'OFFICE287', 'Anthony Akinyi', 'anthony.akinyi@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 74, 'active', NULL),
(361, 9, 'OFFICE288', 'George Odhiambo', 'george.odhiambo@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 14:02:22', 72, 'active', NULL),
(362, 9, 'OFFICE289', 'Alex Wekesa', 'alex.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 14:02:22', 75, 'active', NULL),
(363, 9, 'OFFICE290', 'Lucy Ndegwa', 'lucy.ndegwa@schoolmail.com', '769855953', 'Meru, Makutano', '2025-08-12 14:02:22', 76, 'active', NULL),
(364, 9, 'OFFICE291', 'Jane Kiptoo', 'jane.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 73, 'active', NULL),
(365, 9, 'OFFICE292', 'Ruth Kiplagat', 'ruth.kiplagat@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 73, 'active', NULL);
INSERT INTO `students` (`id`, `school_id`, `student_id_no`, `name`, `email`, `phone`, `address`, `created_at`, `class_id`, `status`, `token`) VALUES
(366, 9, 'OFFICE293', 'George Chepkoech', 'george.chepkoech@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 76, 'active', NULL),
(367, 9, 'OFFICE294', 'Mercy Mboya', 'mercy.mboya@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 71, 'active', NULL),
(368, 9, 'OFFICE295', 'Joy Barasa', 'joy.barasa@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 72, 'active', NULL),
(369, 9, 'OFFICE296', 'Philip Kiplagat', 'philip.kiplagat@schoolmail.com', '769855953', 'Kerugoya, Kutus', '2025-08-12 14:02:22', 76, 'active', NULL),
(370, 9, 'OFFICE297', 'Caroline Muli', 'caroline.muli@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 14:02:22', 71, 'active', NULL),
(371, 9, 'OFFICE298', 'Edwin Lagat', 'edwin.lagat@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 74, 'active', NULL),
(372, 9, 'OFFICE299', 'Ruth Wairimu', 'ruth.wairimu@schoolmail.com', '769855953', 'Thika, Makongeni', '2025-08-12 14:02:22', 76, 'active', NULL),
(373, 9, 'OFFICE300', 'Victor Kiplangat', 'victor.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 76, 'active', NULL),
(374, 9, 'OFFICE301', 'John Mutua', 'john.mutua@schoolmail.com', '769855953', 'Nyeri, Kiganjo', '2025-08-12 14:02:22', 72, 'active', NULL),
(375, 9, 'OFFICE302', 'Lucy Wambui', 'lucy.wambui@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 14:02:22', 75, 'active', NULL),
(376, 9, 'OFFICE303', 'Anthony Korir', 'anthony.korir@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 14:02:22', 73, 'active', NULL),
(377, 9, 'OFFICE304', 'Alex Njoroge', 'alex.njoroge@schoolmail.com', '769855953', 'Kerugoya, Kutus', '2025-08-12 14:02:22', 75, 'active', NULL),
(378, 9, 'OFFICE305', 'George Kariuki', 'george.kariuki@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 75, 'active', NULL),
(379, 9, 'OFFICE306', 'Jane Kosgey', 'jane.kosgey@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 73, 'active', NULL),
(380, 9, 'OFFICE307', 'Sarah Macharia', 'sarah.macharia@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 73, 'active', NULL),
(381, 9, 'OFFICE308', 'Michael Wekesa', 'michael.wekesa@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 14:02:22', 72, 'active', NULL),
(382, 9, 'OFFICE309', 'Caroline Wairimu', 'caroline.wairimu@schoolmail.com', '769855953', 'Mombasa, Nyali', '2025-08-12 14:02:22', 74, 'active', NULL),
(383, 9, 'OFFICE310', 'Mercy Njenga', 'mercy.njenga@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 74, 'active', NULL),
(384, 9, 'OFFICE311', 'Beatrice Achieng', 'beatrice.achieng@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 14:02:22', 75, 'active', NULL),
(385, 9, 'OFFICE312', 'George Kiptoo', 'george.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 74, 'active', NULL),
(386, 9, 'OFFICE313', 'Lucy Lagat', 'lucy.lagat@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 14:02:22', 76, 'active', NULL),
(387, 9, 'OFFICE314', 'Ruth Ndegwa', 'ruth.ndegwa@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 75, 'active', NULL),
(388, 9, 'OFFICE315', 'Jane Mboya', 'jane.mboya@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 76, 'active', NULL),
(389, 9, 'OFFICE316', 'Kelvin Maraga', 'kelvin.maraga@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 71, 'active', NULL),
(390, 9, 'OFFICE317', 'Florence Kihara', 'florence.kihara@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 14:02:22', 76, 'active', NULL),
(391, 9, 'OFFICE318', 'Joseph Wekundu', 'joseph.wekundu@schoolmail.com', '769855953', 'Kitale, Milimani', '2025-08-12 14:02:22', 74, 'active', NULL),
(392, 9, 'OFFICE319', 'Elijah Nyambura', 'elijah.nyambura@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 74, 'active', NULL),
(393, 9, 'OFFICE320', 'Agnes Onyiso', 'agnes.onyiso@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 75, 'active', NULL),
(394, 9, 'OFFICE321', 'Samuel Njenga', 'samuel.njenga@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 14:02:22', 73, 'active', NULL),
(395, 9, 'OFFICE322', 'Philip Kiplagat', 'philip.kiplagat@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 14:02:22', 71, 'active', NULL),
(396, 9, 'OFFICE323', 'Allan Mutiso', 'allan.mutiso@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 75, 'active', NULL),
(397, 9, 'OFFICE324', 'Mercy Kiptoo', 'mercy.kiptoo@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 71, 'active', NULL),
(398, 9, 'OFFICE325', 'George Kiplangat', 'george.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 71, 'active', NULL),
(399, 9, 'OFFICE326', 'Peter Wekesa', 'peter.wekesa@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 14:02:22', 76, 'active', NULL),
(400, 9, 'OFFICE327', 'Elijah Macharia', 'elijah.macharia@schoolmail.com', '769855953', 'Kisii, Nyanchwa', '2025-08-12 14:02:22', 74, 'active', NULL),
(401, 9, 'OFFICE328', 'Diana Nyambura', 'diana.nyambura@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 75, 'active', NULL),
(402, 9, 'OFFICE329', 'Lucy Chepkoech', 'lucy.chepkoech@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 73, 'active', NULL),
(403, 9, 'OFFICE330', 'Florence Wekesa', 'florence.wekesa@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 72, 'active', NULL),
(404, 9, 'OFFICE331', 'Beatrice Lagat', 'beatrice.lagat@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 71, 'active', NULL),
(405, 9, 'OFFICE332', 'James Odhiambo', 'james.odhiambo@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 71, 'active', NULL),
(406, 9, 'OFFICE333', 'Jane Ndegwa', 'jane.ndegwa@schoolmail.com', '769855953', 'Kisumu, Nyalenda', '2025-08-12 14:02:22', 76, 'active', NULL),
(407, 9, 'OFFICE334', 'David Kiptoo', 'david.kiptoo@schoolmail.com', '769855953', 'Eldoret, Langas', '2025-08-12 14:02:22', 71, 'active', NULL),
(408, 9, 'OFFICE335', 'Violet Barasa', 'violet.barasa@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 72, 'active', NULL),
(409, 9, 'OFFICE336', 'Michael Kiplangat', 'michael.kiplangat@schoolmail.com', '769855953', 'Kericho, Litein', '2025-08-12 14:02:22', 71, 'active', NULL),
(410, 9, 'OFFICE337', 'Stephen Kiplangat', 'stephen.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 73, 'active', NULL),
(411, 9, 'OFFICE338', 'Lucy Wekundu', 'lucy.wekundu@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 76, 'active', NULL),
(412, 9, 'OFFICE339', 'Sarah Kiptoo', 'sarah.kiptoo@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 74, 'active', NULL),
(413, 9, 'OFFICE340', 'Peter Kiplangat', 'peter.kiplangat@schoolmail.com', '769855953', 'Narok, Ololulunga', '2025-08-12 14:02:22', 75, 'active', NULL),
(414, 9, 'OFFICE341', 'Victor Kiptoo', 'victor.kiptoo@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 14:02:22', 74, 'active', NULL),
(415, 9, 'OFFICE342', 'Joy Lagat', 'joy.lagat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 75, 'active', NULL),
(416, 9, 'OFFICE343', 'Lucy Kosgey', 'lucy.kosgey@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 71, 'active', NULL),
(417, 9, 'OFFICE344', 'Michael Korir', 'michael.korir@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 75, 'active', NULL),
(418, 9, 'OFFICE345', 'Florence Kiptoo', 'florence.kiptoo@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 76, 'active', NULL),
(419, 9, 'OFFICE346', 'Diana Kiptoo', 'diana.kiptoo@schoolmail.com', '769855953', 'Nairobi, Donholm', '2025-08-12 14:02:22', 73, 'active', NULL),
(420, 9, 'OFFICE347', 'Samuel Kiplangat', 'samuel.kiplangat@schoolmail.com', '769855953', 'Nakuru, Milimani', '2025-08-12 14:02:22', 71, 'active', NULL),
(421, 9, 'OFFICE348', 'Agnes Nyambura', 'agnes.nyambura@schoolmail.com', '769855953', 'Nairobi, Kasarani', '2025-08-12 14:02:22', 73, 'active', NULL),
(422, 9, 'OFFICE349', 'Peter Nyongesa', 'peter.nyongesa@schoolmail.com', '769855953', 'Bungoma, Kanduyi', '2025-08-12 14:02:22', 71, 'active', NULL),
(423, 9, 'OFFICE350', 'Caroline Kosgey', 'caroline.kosgey@schoolmail.com', '769855953', 'Kisumu, Manyatta', '2025-08-12 14:02:22', 76, 'active', NULL),
(424, 6, 'STU0100', 'William Mungai', 'mungaimacharia308@gmail.com', '0769855953', 'Ruaka', '2025-08-24 15:03:12', 53, 'active', NULL),
(426, 9, 'OFFICE100', 'William Mungai', 'mungaimacharia308@gmail.com', '0769855953', '', '2025-08-24 16:58:45', 72, 'active', NULL),
(428, 10, 'M8778', 'William Mungai', 'mungaimacharia308@gmail.com', '0769855953', '', '2025-08-26 15:28:24', NULL, 'active', NULL),
(429, 11, 'BMF001', 'William Mungai', '', '0769855953', '', '2025-08-28 18:36:36', 80, 'active', '43c85f83c7cb819f7eb271ea1b0d07493fef870af978b310d293cc09e33da13e'),
(430, 11, 'BMF002', 'John Gathua Mungai', '', '0790346966', '', '2025-08-28 18:37:10', 81, 'active', '583282531e40be63bbf201f09713e3ebe0aa31640044bf1e2a4e74c57ea82286'),
(431, 11, 'BMF003', 'Brian Gacheru', '', '0702628211', '', '2025-08-28 18:37:42', 81, 'active', 'aa327565becccf72891442b303af784f82ec0925f101bdc33714619abb747697'),
(432, 11, 'BMF004', 'Ken Kimani', '', '0701085745', '', '2025-08-28 18:39:14', 82, 'active', '3414b90ca156e42c6988446631627600a91675273124de03ac7656b82cd28d36'),
(433, 11, 'BMF005', 'Maryann Wanjiku', '', '0718760077', '', '2025-08-28 18:41:14', 82, 'active', '095f12c6ba2159e1b5051732622e8aef454a5fe0beae18465b188e448781c6fc');

-- --------------------------------------------------------

--
-- Table structure for table `student_activities`
--

CREATE TABLE `student_activities` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `term` varchar(20) NOT NULL,
  `enrolled_date` date NOT NULL,
  `status` enum('active','inactive','completed') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_activities`
--

INSERT INTO `student_activities` (`id`, `school_id`, `student_id`, `activity_id`, `academic_year`, `term`, `enrolled_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 7, 123, 12, '2026-2027', 'Term 1', '2026-01-02', 'active', '2026-01-02 00:01:57', '2026-01-02 00:01:57');

-- --------------------------------------------------------

--
-- Table structure for table `student_transport`
--

CREATE TABLE `student_transport` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `transport_zone_id` int(11) NOT NULL,
  `trip_type` enum('round_trip','one_way') NOT NULL DEFAULT 'round_trip',
  `academic_year` varchar(20) NOT NULL,
  `term` varchar(20) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_transport`
--

INSERT INTO `student_transport` (`id`, `school_id`, `student_id`, `transport_zone_id`, `trip_type`, `academic_year`, `term`, `status`, `created_at`, `updated_at`) VALUES
(1, 11, 431, 9, 'round_trip', '2026-2027', 'Term 1', 'active', '2026-01-01 23:51:07', '2026-01-01 23:51:07'),
(2, 7, 123, 10, 'round_trip', '2026-2027', 'Term 1', 'active', '2026-01-01 23:54:53', '2026-01-01 23:54:53');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `account_id` int(11) NOT NULL,
  `debit` decimal(10,2) DEFAULT 0.00,
  `credit` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transport_zones`
--

CREATE TABLE `transport_zones` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `zone_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `round_trip_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `one_way_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transport_zones`
--

INSERT INTO `transport_zones` (`id`, `school_id`, `zone_name`, `description`, `round_trip_amount`, `one_way_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'ZONE-1', 'Delta, Ruaka town, Joyland', 10000.00, 7000.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(2, 1, 'ZONE-2', 'Mifereji, Kahigo, Guango, Kigwaru', 11500.00, 9000.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(3, 1, 'ZONE-3', 'Mucatha, Gacharage, Sacred heart, Ndenderu', 14000.00, 12500.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(4, 1, 'ZONE-4', 'Runda, Gigiri, Banana, Karura, Gachie', 15500.00, 13000.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(5, 1, 'ZONE-5', 'Kiambu, Marurul, Kihara, Laini Ridgeways', 19500.00, 17500.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(6, 1, 'ZONE-6', 'Redhill, Nazareth, Windsor', 22500.00, 18500.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(7, 1, 'ZONE-7', 'Lower Kabete, Mwimuto, Kitsuru', 24500.00, 21500.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(8, 1, 'ZONE-8', 'Poster Kabuku, Kiambu town', 26500.00, 23000.00, 'active', '2026-01-01 22:39:58', '2026-01-01 22:39:58'),
(9, 11, 'Zone 1', 'Delta, Ruaka town, Joyland', 10000.00, 70000.00, 'active', '2026-01-01 23:23:10', '2026-01-01 23:23:10'),
(10, 7, 'Zone 1', 'Delta, Ruaka, Joyland', 11000.00, 8000.00, 'active', '2026-01-01 23:54:40', '2026-01-01 23:54:40');

-- --------------------------------------------------------

--
-- Table structure for table `uniform_orders`
--

CREATE TABLE `uniform_orders` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL COMMENT 'Link to the final invoice after issuance',
  `order_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending Payment','Processing','With Tailor','Ready for Pickup','Completed','Cancelled') NOT NULL DEFAULT 'Processing',
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uniform_order_items`
--

CREATE TABLE `uniform_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `size` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `school_id`, `name`, `email`, `password`, `created_at`) VALUES
(1, 2, 'William Mungai', 'william.mungai@strathmore.edu', '$2y$10$VfEm73GX36QO6G/utywCXumK4xjc3QI.p/aAo3N2REEM8KW1y7BQ.', '2025-07-01 11:15:13'),
(2, 3, 'Macharia Mungai', 'williammungai10@gmail.com', '$2y$10$Ykjy9.VIz6HSyhEq2FtEpOB27I9SI2Z8zOGVLTOViAhj80ZOXVyBu', '2025-07-09 08:08:56'),
(3, 4, 'Macharia Mungai', 'testschool@gmail.com', '$2y$10$XRbfSi9uej9DDKiFJm8Fsu/1OVQ/rTvhBOshGwJ1X4vojJbBn7UAi', '2025-07-09 12:41:06'),
(4, 5, 'Mash Mungai', 'stmarys@gmail.com', '$2y$10$yS7A/BxPxUIQzVtOvEzSKelfy0wELmp9k0In02D42XTkqpKet6kZu', '2025-07-11 21:00:10'),
(5, 6, 'Mash Mungai', 'info@brighthorizonschool.com', '$2y$10$KvbZ44jXGEqZhpFrUGxBSekadrRf6E.sA7uy23n0fWMsFHggn/xfq', '2025-07-16 21:57:11'),
(6, 7, 'King Mungai', 'theschool@gmail.com', '$2y$10$Kyw.6mR/Dv4TL44aYe30NO6.UBgu0J1Nn5/XVoIbdy0DXkInJjQe6', '2025-08-06 14:08:44'),
(7, 8, 'King Mungai', 'info@mash.com', '$2y$10$V14LXA/7zOh/2C6tHsLVVOfC6kAXq2DLQaxD2b99geJp2h3tZMWOC', '2025-08-12 08:55:27'),
(8, 9, 'Dwgiht', 'office@gmail.com', '$2y$10$LNy5TpTWm3S071CQkahBiOPcvQCRNjLFoEudSmTXSutugRrbjst9y', '2025-08-12 11:00:06'),
(9, 10, 'Bruce Wayne', 'batman@gmail.com', '$2y$10$06R0AUR6xC1ojQNHOfZtfejb6EvoYtLHFdnYxuexmJJf.bMk/d5p6', '2025-08-26 12:16:37'),
(10, 11, 'Clark Kent', 'superman@gmail.com', '$2y$10$32P3POI5PXz6GhqPWSWO6.NuZt3qiQc6gpdJwb9cs0E21IeT4CDGG', '2025-08-28 14:01:10'),
(11, 12, 'Macharia Mungai', 'texas@chicken.com', '$2y$10$TtAwup8dTBW7UsSB8/AD3eOIUzHNVNlYEhTozrX1bIKe01ytK1zlm', '2025-11-29 18:42:03');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `vehicle_id` varchar(50) NOT NULL,
  `make` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `registration_number` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicle_expenses`
--

CREATE TABLE `vehicle_expenses` (
  `id` int(11) NOT NULL,
  `school_id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `vehicle_id` varchar(50) NOT NULL,
  `expense_type` enum('fuel','maintenance','insurance','repairs','other') NOT NULL,
  `account_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `odometer` decimal(10,1) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accounts`
--
ALTER TABLE `accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_account_code_unique` (`school_id`,`account_code`),
  ADD KEY `idx_account_type` (`account_type`),
  ADD KEY `idx_accounts_school_id` (`school_id`);

--
-- Indexes for table `activities`
--
ALTER TABLE `activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_activity_per_school` (`school_id`,`activity_name`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `annual_fees_billed`
--
ALTER TABLE `annual_fees_billed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_annual_fee` (`student_id`,`item_id`,`academic_year`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_school_id` (`school_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action_type` (`action_type`),
  ADD KEY `idx_target` (`target_table`,`target_id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `budget_lines`
--
ALTER TABLE `budget_lines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `budget_account_unique` (`budget_id`,`account_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_classes_school_id` (`school_id`),
  ADD KEY `fk_next_class` (`next_class_id`),
  ADD KEY `idx_invoice_template_id` (`invoice_template_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `admission_no` (`admission_no`);

--
-- Indexes for table `deduction_templates`
--
ALTER TABLE `deduction_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deposits`
--
ALTER TABLE `deposits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_employee_id_unique` (`school_id`,`employee_id`),
  ADD KEY `idx_employees_status` (`status`),
  ADD KEY `fk_employees_school_id` (`school_id`);

--
-- Indexes for table `employee_payroll_meta`
--
ALTER TABLE `employee_payroll_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_employee_meta` (`employee_id`,`payroll_meta_id`),
  ADD KEY `fk_epm_school` (`school_id`),
  ADD KEY `fk_epm_meta` (`payroll_meta_id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `idx_expenses_school_id` (`school_id`);

--
-- Indexes for table `fee_structure_items`
--
ALTER TABLE `fee_structure_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_fee_item` (`school_id`,`class_id`,`item_id`,`academic_year`,`term`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `fiscal_settings`
--
ALTER TABLE `fiscal_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_category_name_unique` (`school_id`,`name`),
  ADD KEY `fk_inv_cat_school` (`school_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_sku_unique` (`school_id`,`sku`),
  ADD KEY `fk_inv_item_school` (`school_id`),
  ADD KEY `fk_inv_item_category` (`category_id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_inv_move_school` (`school_id`),
  ADD KEY `fk_inv_move_item` (`item_id`),
  ADD KEY `fk_inv_move_user` (`user_id`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_school_invoice_number` (`school_id`,`invoice_number`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_invoice_date` (`invoice_date`),
  ADD KEY `idx_invoices_school_id` (`school_id`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `idx_invoice_items_school_id` (`school_id`);

--
-- Indexes for table `invoice_templates`
--
ALTER TABLE `invoice_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_class_unique` (`school_id`,`class_id`),
  ADD KEY `idx_invoice_templates_school_id` (`school_id`),
  ADD KEY `fk_template_class` (`class_id`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_items_school_id` (`school_id`);

--
-- Indexes for table `journal_details`
--
ALTER TABLE `journal_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_id` (`journal_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_journal_date` (`entry_date`),
  ADD KEY `idx_journal_entries_school_id` (`school_id`);

--
-- Indexes for table `journal_lines`
--
ALTER TABLE `journal_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `journal_id` (`journal_id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_id` (`transaction_id`);

--
-- Indexes for table `one_time_fees_billed`
--
ALTER TABLE `one_time_fees_billed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_one_time_fee` (`student_id`,`item_id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `fk_payments_receipt` (`receipt_id`),
  ADD KEY `fk_payments_invoice` (`invoice_id`),
  ADD KEY `coa_account_id` (`coa_account_id`),
  ADD KEY `idx_payments_school_id` (`school_id`),
  ADD KEY `idx_deposit_id` (`deposit_id`);

--
-- Indexes for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `payment_id` (`payment_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `payment_promises`
--
ALTER TABLE `payment_promises`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_payment_receipts_school_id` (`school_id`),
  ADD KEY `fk_receipts_to_accounts` (`coa_account_id`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payroll_pay_period` (`pay_period`),
  ADD KEY `idx_payroll_school_id` (`school_id`),
  ADD KEY `fk_payroll_employee` (`employee_id`);

--
-- Indexes for table `payroll_meta`
--
ALTER TABLE `payroll_meta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_name_type_unique` (`school_id`,`name`,`type`),
  ADD KEY `fk_payroll_meta_school` (`school_id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_setting_unique` (`school_id`,`setting_key`);

--
-- Indexes for table `receipt_uploads`
--
ALTER TABLE `receipt_uploads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `recurring_expenses`
--
ALTER TABLE `recurring_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `expense_account_id` (`expense_account_id`),
  ADD KEY `payment_account_id` (`payment_account_id`);

--
-- Indexes for table `requisition_batches`
--
ALTER TABLE `requisition_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `payment_account_id` (`payment_account_id`);

--
-- Indexes for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `school_details`
--
ALTER TABLE `school_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_id` (`school_id`);

--
-- Indexes for table `service_payments`
--
ALTER TABLE `service_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `provider_id` (`provider_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `idx_service_payment_date` (`payment_date`);

--
-- Indexes for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_service_providers_school_id` (`school_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id_no` (`student_id_no`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `idx_school_id` (`school_id`);

--
-- Indexes for table `student_activities`
--
ALTER TABLE `student_activities`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_activity_per_term` (`student_id`,`activity_id`,`academic_year`,`term`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `activity_id` (`activity_id`);

--
-- Indexes for table `student_transport`
--
ALTER TABLE `student_transport`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_transport_per_term` (`student_id`,`academic_year`,`term`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `transport_zone_id` (`transport_zone_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_suppliers_school_id` (`school_id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `idx_supplier_payment_date` (`payment_date`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`);

--
-- Indexes for table `transport_zones`
--
ALTER TABLE `transport_zones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_zone_per_school` (`school_id`,`zone_name`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `uniform_orders`
--
ALTER TABLE `uniform_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_uorder_school` (`school_id`),
  ADD KEY `fk_uorder_student` (`student_id`),
  ADD KEY `fk_uorder_invoice` (`invoice_id`);

--
-- Indexes for table `uniform_order_items`
--
ALTER TABLE `uniform_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_uorderitem_order` (`order_id`),
  ADD KEY `fk_uorderitem_item` (`item_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicle_id` (`vehicle_id`),
  ADD KEY `idx_vehicles_school_id` (`school_id`);

--
-- Indexes for table `vehicle_expenses`
--
ALTER TABLE `vehicle_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `account_id` (`account_id`),
  ADD KEY `idx_vehicle_expense_date` (`expense_date`),
  ADD KEY `idx_vehicle_expenses_school_id` (`school_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accounts`
--
ALTER TABLE `accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `activities`
--
ALTER TABLE `activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `annual_fees_billed`
--
ALTER TABLE `annual_fees_billed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=196;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `budget_lines`
--
ALTER TABLE `budget_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deduction_templates`
--
ALTER TABLE `deduction_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposits`
--
ALTER TABLE `deposits`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `employee_payroll_meta`
--
ALTER TABLE `employee_payroll_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=288;

--
-- AUTO_INCREMENT for table `fee_structure_items`
--
ALTER TABLE `fee_structure_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=136;

--
-- AUTO_INCREMENT for table `fiscal_settings`
--
ALTER TABLE `fiscal_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=296;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1344;

--
-- AUTO_INCREMENT for table `invoice_templates`
--
ALTER TABLE `invoice_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=449;

--
-- AUTO_INCREMENT for table `journal_details`
--
ALTER TABLE `journal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `journal_entries`
--
ALTER TABLE `journal_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `journal_lines`
--
ALTER TABLE `journal_lines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `mpesa_transactions`
--
ALTER TABLE `mpesa_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `one_time_fees_billed`
--
ALTER TABLE `one_time_fees_billed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=87;

--
-- AUTO_INCREMENT for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_promises`
--
ALTER TABLE `payment_promises`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `payroll_meta`
--
ALTER TABLE `payroll_meta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=353;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `receipt_uploads`
--
ALTER TABLE `receipt_uploads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `recurring_expenses`
--
ALTER TABLE `recurring_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requisition_batches`
--
ALTER TABLE `requisition_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `requisition_items`
--
ALTER TABLE `requisition_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=291;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `school_details`
--
ALTER TABLE `school_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `service_payments`
--
ALTER TABLE `service_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `service_providers`
--
ALTER TABLE `service_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=434;

--
-- AUTO_INCREMENT for table `student_activities`
--
ALTER TABLE `student_activities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_transport`
--
ALTER TABLE `student_transport`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transport_zones`
--
ALTER TABLE `transport_zones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `uniform_orders`
--
ALTER TABLE `uniform_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uniform_order_items`
--
ALTER TABLE `uniform_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicle_expenses`
--
ALTER TABLE `vehicle_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accounts`
--
ALTER TABLE `accounts`
  ADD CONSTRAINT `fk_accounts_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `budget_lines`
--
ALTER TABLE `budget_lines`
  ADD CONSTRAINT `budget_lines_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `fk_classes_invoice_template` FOREIGN KEY (`invoice_template_id`) REFERENCES `invoice_templates` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_classes_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_next_class` FOREIGN KEY (`next_class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `fk_employees_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_payroll_meta`
--
ALTER TABLE `employee_payroll_meta`
  ADD CONSTRAINT `fk_epm_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_epm_meta` FOREIGN KEY (`payroll_meta_id`) REFERENCES `payroll_meta` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_epm_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`),
  ADD CONSTRAINT `fk_expenses_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `fee_structure_items`
--
ALTER TABLE `fee_structure_items`
  ADD CONSTRAINT `fee_structure_items_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_structure_items_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_structure_items_ibfk_3` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD CONSTRAINT `fk_inv_cat_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `fk_inv_item_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inv_item_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `fk_inv_move_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_move_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inv_move_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `fk_invoices_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `fk_invoice_items_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_templates`
--
ALTER TABLE `invoice_templates`
  ADD CONSTRAINT `fk_invoice_templates_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_template_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `fk_items_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_details`
--
ALTER TABLE `journal_details`
  ADD CONSTRAINT `journal_details_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `journal_entries` (`id`),
  ADD CONSTRAINT `journal_details_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `journal_entries`
--
ALTER TABLE `journal_entries`
  ADD CONSTRAINT `fk_journal_entries_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `journal_lines`
--
ALTER TABLE `journal_lines`
  ADD CONSTRAINT `journal_lines_ibfk_1` FOREIGN KEY (`journal_id`) REFERENCES `journal_entries` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `journal_lines_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`),
  ADD CONSTRAINT `fk_payments_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `payment_receipts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_payments_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`coa_account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `payment_allocations`
--
ALTER TABLE `payment_allocations`
  ADD CONSTRAINT `payment_allocations_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`),
  ADD CONSTRAINT `payment_allocations_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`);

--
-- Constraints for table `payment_promises`
--
ALTER TABLE `payment_promises`
  ADD CONSTRAINT `payment_promises_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_promises_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD CONSTRAINT `fk_payment_receipts_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_receipts_to_accounts` FOREIGN KEY (`coa_account_id`) REFERENCES `accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `fk_payroll_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_payroll_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_meta`
--
ALTER TABLE `payroll_meta`
  ADD CONSTRAINT `fk_payroll_meta_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD CONSTRAINT `payroll_settings_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `requisition_items`
--
ALTER TABLE `requisition_items`
  ADD CONSTRAINT `fk_requisition_items_batch` FOREIGN KEY (`batch_id`) REFERENCES `requisition_batches` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `school_details`
--
ALTER TABLE `school_details`
  ADD CONSTRAINT `school_details_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `service_payments`
--
ALTER TABLE `service_payments`
  ADD CONSTRAINT `service_payments_ibfk_1` FOREIGN KEY (`provider_id`) REFERENCES `service_providers` (`id`),
  ADD CONSTRAINT `service_payments_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `service_providers`
--
ALTER TABLE `service_providers`
  ADD CONSTRAINT `fk_service_providers_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_students_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `fk_suppliers_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD CONSTRAINT `supplier_payments_ibfk_1` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`),
  ADD CONSTRAINT `supplier_payments_ibfk_2` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uniform_orders`
--
ALTER TABLE `uniform_orders`
  ADD CONSTRAINT `fk_uorder_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_uorder_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uorder_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uniform_order_items`
--
ALTER TABLE `uniform_order_items`
  ADD CONSTRAINT `fk_uorderitem_item` FOREIGN KEY (`item_id`) REFERENCES `inventory_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uorderitem_order` FOREIGN KEY (`order_id`) REFERENCES `uniform_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `fk_vehicles_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicle_expenses`
--
ALTER TABLE `vehicle_expenses`
  ADD CONSTRAINT `fk_vehicle_expenses_school_id` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `vehicle_expenses_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
