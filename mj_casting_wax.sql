-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 02, 2026 at 05:03 PM
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
-- Database: `mj_casting_wax`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cnic` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(50) DEFAULT NULL,
  `opening_balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive') DEFAULT 'active',
  `party_type` enum('customer','dukandar','karigar') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `name`, `phone`, `cnic`, `address`, `city`, `opening_balance`, `status`, `party_type`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'Waseem Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-03 08:16:00', '2026-06-09 15:05:40', NULL),
(2, 'Faheem Bhai', '', '', '', '', 49.32, 'active', 'customer', '2026-06-08 15:12:52', '2026-06-09 15:46:34', NULL),
(3, 'Ahsan Karigar', '', '', '', '', 6.89, 'active', 'karigar', '2026-06-09 15:19:16', '2026-06-15 09:54:23', NULL),
(4, 'Ali Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-09 15:21:14', '2026-06-09 15:21:14', NULL),
(5, 'Furqan', '', '', '', '', 0.00, 'active', 'customer', '2026-06-09 15:26:08', '2026-06-09 15:26:08', NULL),
(6, 'Fahad', '', '', '', '', -4.66, 'active', 'customer', '2026-06-10 15:32:35', '2026-06-10 15:32:35', NULL),
(7, 'Hassan Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:34:44', '2026-06-10 15:34:44', NULL),
(8, 'Mamu Safdar', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:37:30', '2026-06-10 15:37:30', NULL),
(9, 'Mamu Faraz', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:37:38', '2026-06-10 15:37:38', NULL),
(10, 'Kashif Bhai', '', '', '', '', 8.57, 'active', 'customer', '2026-06-10 15:38:29', '2026-06-10 15:38:29', NULL),
(11, 'Khurrum Taj', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:38:40', '2026-06-10 15:38:40', NULL),
(12, 'Rohail', '', '', '', '', 2.44, 'active', 'customer', '2026-06-10 15:38:59', '2026-06-10 15:38:59', NULL),
(13, 'Rana Ijaz Sb', '', '', '', '', 0.12, 'active', 'customer', '2026-06-10 15:39:12', '2026-06-10 15:39:12', NULL),
(14, 'Shahid Bhai', '', '', '', '', 5.90, 'active', 'customer', '2026-06-10 15:39:37', '2026-06-10 15:39:37', NULL),
(15, 'Shafiq Bhai', '', '', '', '', 0.73, 'active', 'customer', '2026-06-10 15:41:36', '2026-06-10 15:41:36', NULL),
(16, 'Uncle Zulfiqar', '', '', '', '', 6.54, 'active', 'customer', '2026-06-10 15:41:53', '2026-06-10 15:41:53', NULL),
(17, 'Umar Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:43:39', '2026-06-10 15:43:39', NULL),
(18, 'Ustad Fija', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:56:46', '2026-06-10 15:56:46', NULL),
(19, 'Yasir Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-10 15:56:56', '2026-06-10 15:56:56', NULL),
(20, 'Sunny Karigar', '', '', '', '', -29.78, 'active', 'karigar', '2026-06-10 15:57:17', '2026-06-10 15:57:17', NULL),
(21, 'Khurrum Bhai Karigar', '', '', '', '', 2.00, 'active', 'karigar', '2026-06-16 09:01:03', '2026-06-16 09:01:22', NULL),
(22, 'Rameez Bhai', '', '', '', '', 0.00, 'active', 'karigar', '2026-06-16 09:10:06', '2026-06-16 09:10:16', NULL),
(23, 'Naveed Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-16 09:24:50', '2026-06-16 09:24:50', NULL),
(24, 'Saqib Bhai', '', '', '', '', 0.00, 'active', 'customer', '2026-06-16 09:36:49', '2026-06-16 09:36:49', NULL),
(25, 'Asad', '', '', '', '', 0.00, 'active', 'customer', '2026-06-18 10:27:34', '2026-06-18 10:27:34', NULL),
(26, 'Muhammad Ali Dukandar', '', '', '', '', 0.00, 'active', 'dukandar', '2026-06-18 15:29:34', '2026-06-18 15:29:34', NULL),
(27, 'Ali Haider Dukandar', '', '', '', '', 0.00, 'active', 'dukandar', '2026-06-18 15:29:51', '2026-06-18 15:29:51', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expense_categories`
--

CREATE TABLE `expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gold_gives`
--

CREATE TABLE `gold_gives` (
  `id` int(11) NOT NULL,
  `give_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `give_type` enum('customer','dukandar','karigar') DEFAULT 'customer',
  `give_date` date NOT NULL,
  `total_gross_weight` decimal(10,3) DEFAULT 0.000,
  `total_khalis_weight` decimal(10,3) DEFAULT 0.000,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gold_gives`
--

INSERT INTO `gold_gives` (`id`, `give_no`, `customer_id`, `give_type`, `give_date`, `total_gross_weight`, `total_khalis_weight`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'GV-00001', 3, 'customer', '2026-06-18', 60.000, 52.710, '', 1, NULL, '2026-06-18 14:30:11', '2026-06-18 15:10:39', '2026-06-18 20:10:39'),
(2, 'GV-00002', 3, 'customer', '2026-06-29', 10.000, 9.160, '', 1, 1, '2026-06-29 12:18:06', '2026-07-02 10:48:15', NULL),
(3, 'GV-00003', 13, 'customer', '2026-07-02', 13.000, 11.450, '', 1, NULL, '2026-07-02 11:25:34', '2026-07-02 11:25:34', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gold_give_items`
--

CREATE TABLE `gold_give_items` (
  `id` int(11) NOT NULL,
  `give_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gross_weight` decimal(10,3) DEFAULT 0.000,
  `ratti_impurity` decimal(10,3) DEFAULT 0.000,
  `khalis_weight` decimal(10,3) DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gold_give_items`
--

INSERT INTO `gold_give_items` (`id`, `give_id`, `description`, `gross_weight`, `ratti_impurity`, `khalis_weight`, `created_at`, `updated_at`) VALUES
(1, 1, '', 50.000, 11.000, 44.270, '2026-06-18 14:30:11', '2026-06-18 14:30:11'),
(2, 1, '', 10.000, 15.000, 8.440, '2026-06-18 14:30:11', '2026-06-18 14:30:11'),
(9, 2, '', 10.000, 8.000, 9.160, '2026-07-02 10:48:15', '2026-07-02 10:48:15'),
(10, 3, '', 8.000, 11.000, 7.080, '2026-07-02 11:25:34', '2026-07-02 11:25:34'),
(11, 3, '', 5.000, 12.000, 4.370, '2026-07-02 11:25:34', '2026-07-02 11:25:34');

-- --------------------------------------------------------

--
-- Table structure for table `gold_receipts`
--

CREATE TABLE `gold_receipts` (
  `id` int(11) NOT NULL,
  `receipt_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `receipt_type` enum('customer','dukandar','karigar') DEFAULT 'customer',
  `receipt_date` date NOT NULL,
  `total_gross_weight` decimal(10,3) DEFAULT 0.000,
  `total_khalis_weight` decimal(10,3) DEFAULT 0.000,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gold_receipts`
--

INSERT INTO `gold_receipts` (`id`, `receipt_no`, `customer_id`, `receipt_type`, `receipt_date`, `total_gross_weight`, `total_khalis_weight`, `remarks`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'RCV-00001', 1, 'customer', '2026-06-08', 10.000, 10.000, NULL, 1, 1, '2026-06-08 14:59:56', '2026-06-18 15:10:34', '2026-06-18 20:10:34'),
(2, 'RCV-00002', 3, 'customer', '2026-07-02', 10.000, 8.850, NULL, 1, 1, '2026-07-02 10:43:31', '2026-07-02 10:48:51', NULL),
(3, 'RCV-00003', 13, 'customer', '2026-06-28', 10.000, 9.160, NULL, 1, NULL, '2026-07-02 11:25:08', '2026-07-02 11:25:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `gold_receipt_items`
--

CREATE TABLE `gold_receipt_items` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gross_weight` decimal(10,3) DEFAULT 0.000,
  `ratti_impurity` decimal(10,3) DEFAULT 0.000,
  `khalis_weight` decimal(10,3) DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gold_receipt_items`
--

INSERT INTO `gold_receipt_items` (`id`, `receipt_id`, `description`, `gross_weight`, `ratti_impurity`, `khalis_weight`, `created_at`, `updated_at`) VALUES
(2, 1, '', 10.000, 0.000, 10.000, '2026-06-18 15:00:29', '2026-06-18 15:00:29'),
(7, 2, '', 10.000, 11.000, 8.850, '2026-07-02 10:48:51', '2026-07-02 10:48:51'),
(8, 3, '', 10.000, 8.000, 9.160, '2026-07-02 11:25:08', '2026-07-02 11:25:08');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `opening_balance` decimal(10,3) DEFAULT 0.000,
  `received` decimal(10,3) DEFAULT 0.000,
  `given_invoices` decimal(10,3) DEFAULT 0.000,
  `closing_balance` decimal(10,3) DEFAULT 0.000,
  `period_label` varchar(100) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `opening_balance`, `received`, `given_invoices`, `closing_balance`, `period_label`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 48.070, 2210.660, 2291.700, 129.110, 'Current Stock', 1, '2026-06-02 11:35:08', '2026-07-02 14:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_type` enum('customer','dukandar','karigar') DEFAULT 'customer',
  `invoice_date` date NOT NULL,
  `casting_weight` decimal(10,3) DEFAULT 0.000,
  `waste_weight` decimal(10,3) DEFAULT 0.000,
  `total_weight` decimal(10,3) DEFAULT 0.000,
  `ratti` decimal(10,3) DEFAULT 0.000,
  `ratti_rate` decimal(10,3) DEFAULT 0.000,
  `male_waste` decimal(10,3) DEFAULT 0.000,
  `gold_khalis` decimal(10,3) DEFAULT 0.000,
  `total_received_khalis` decimal(10,3) DEFAULT 0.000,
  `rp_rate` decimal(15,2) DEFAULT 0.00,
  `rp_amount` decimal(15,2) DEFAULT 0.00,
  `rp_mazdori_weight` decimal(10,3) DEFAULT 0.000,
  `rp_mazdori_rate` decimal(15,2) DEFAULT 0.00,
  `rp_mazdori_amount` decimal(15,2) DEFAULT 0.00,
  `casting_mazdori_weight` decimal(10,3) DEFAULT 0.000,
  `casting_mazdori_rate` decimal(15,2) DEFAULT 0.00,
  `casting_mazdori_amount` decimal(15,2) DEFAULT 0.00,
  `effective_gold` decimal(10,3) DEFAULT 0.000,
  `grand_total` decimal(15,2) DEFAULT 0.00,
  `wasooli` decimal(15,2) DEFAULT 0.00,
  `previous_balance` decimal(15,2) DEFAULT 0.00,
  `remaining_balance` decimal(15,2) DEFAULT 0.00,
  `manual_book_no` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `status` enum('active','cancelled') DEFAULT 'active',
  `ratti_auto` tinyint(1) DEFAULT 0,
  `waste_auto` tinyint(1) DEFAULT 0,
  `male_waste_auto` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `invoice_no`, `customer_id`, `invoice_type`, `invoice_date`, `casting_weight`, `waste_weight`, `total_weight`, `ratti`, `ratti_rate`, `male_waste`, `gold_khalis`, `total_received_khalis`, `rp_rate`, `rp_amount`, `rp_mazdori_weight`, `rp_mazdori_rate`, `rp_mazdori_amount`, `casting_mazdori_weight`, `casting_mazdori_rate`, `casting_mazdori_amount`, `effective_gold`, `grand_total`, `wasooli`, `previous_balance`, `remaining_balance`, `manual_book_no`, `remarks`, `status`, `ratti_auto`, `waste_auto`, `male_waste_auto`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'INV-00001', 1, 'customer', '2026-06-06', 47.460, 0.470, 47.930, 15.000, 0.100, 7.490, 40.440, 26.080, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 40.440, 40.44, 0.00, 0.00, 14.36, '146', '', 'active', 0, 0, 0, 1, 1, '2026-06-03 08:16:38', '2026-06-10 10:00:47', NULL),
(2, 'INV-00002', 1, 'customer', '2026-06-09', 36.030, 0.360, 36.390, 15.000, 0.100, 5.680, 30.710, 31.910, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 30.710, 30.71, 0.00, 14.36, 13.16, '148', '', 'active', 0, 0, 0, 1, 1, '2026-06-08 15:45:29', '2026-06-18 15:10:34', NULL),
(3, 'INV-00003', 2, 'customer', '2026-06-03', 66.960, 0.670, 67.630, 15.000, 0.100, 10.570, 57.060, 57.950, 0.00, 0.00, 0.090, 0.00, 3500.00, 0.000, 0.00, 0.00, 57.150, 57.15, 0.00, 49.32, 48.52, '137', '', 'active', 0, 0, 0, 1, 1, '2026-06-09 09:32:52', '2026-06-10 11:55:49', NULL),
(4, 'INV-00004', 1, 'customer', '2026-06-09', 25.840, 0.260, 26.100, 11.000, 0.100, 2.990, 23.110, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 23.110, 23.11, 0.00, 13.16, 36.27, '149', '', 'active', 0, 0, 0, 1, 1, '2026-06-09 09:56:29', '2026-06-18 15:10:34', NULL),
(5, 'INV-00005', 3, 'customer', '2026-06-05', 21.400, 0.210, 21.610, 11.000, 0.100, 2.480, 19.130, 25.010, 0.00, 0.00, 0.000, 0.00, 0.00, 5.590, 0.00, 0.00, 24.720, 24.72, 0.00, 6.89, 6.60, '145', '', 'active', 0, 0, 0, 1, 1, '2026-06-09 09:57:25', '2026-06-15 10:15:48', NULL),
(6, 'INV-00006', 4, 'customer', '2026-06-03', 8.690, 0.090, 8.780, 15.000, 0.100, 1.370, 7.410, 7.410, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 7.410, 7.41, 0.00, 0.00, 0.00, '139', '', 'active', 0, 0, 0, 1, 1, '2026-06-09 09:57:46', '2026-06-10 15:31:25', NULL),
(7, 'INV-00007', 5, 'customer', '2026-06-06', 0.000, 0.000, 0.000, 13.000, 0.000, 0.000, 0.000, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.00, 0.00, '144', '', 'active', 0, 0, 0, 1, 1, '2026-06-09 13:44:06', '2026-06-18 15:18:54', NULL),
(8, 'INV-00008', 2, 'customer', '2026-06-10', 216.820, 2.170, 218.990, 15.000, 0.100, 34.220, 184.770, 180.710, 0.00, 0.00, 0.330, 0.00, 12000.00, 0.000, 0.00, 0.00, 185.100, 185.10, 0.00, 48.52, 52.91, '154', '', 'active', 0, 0, 0, 1, 1, '2026-06-10 09:26:50', '2026-06-10 12:22:39', NULL),
(9, 'INV-00009', 7, 'customer', '2026-06-04', 44.380, 0.490, 44.870, 16.000, 0.110, 7.480, 37.390, 33.230, 0.00, 0.00, 0.120, 0.00, 4500.00, 0.000, 0.00, 0.00, 37.510, 37.51, 0.00, 0.00, 4.28, '140', '', 'active', 0, 0, 0, 1, NULL, '2026-06-10 15:36:16', '2026-06-10 15:36:16', NULL),
(10, 'INV-00010', 7, 'customer', '2026-06-09', 57.130, 0.630, 57.760, 16.000, 0.110, 9.620, 48.140, 46.250, 0.00, 0.00, 0.130, 0.00, 5000.00, 0.000, 0.00, 0.00, 48.270, 48.27, 0.00, 4.28, 6.30, '153', '', 'active', 0, 0, 0, 1, 1, '2026-06-10 15:37:05', '2026-06-16 09:23:34', NULL),
(11, 'INV-00011', 14, 'customer', '2026-06-09', 67.000, 0.670, 67.670, 8.000, 0.100, 5.640, 62.030, 50.000, 0.00, 0.00, 0.060, 0.00, 2500.00, 0.000, 0.00, 0.00, 62.090, 62.09, 0.00, 5.90, 17.99, '151', '', 'active', 0, 0, 0, 1, 1, '2026-06-10 15:40:44', '2026-06-16 09:29:49', NULL),
(12, 'INV-00012', 14, 'customer', '2026-06-10', 11.350, 0.110, 11.460, 7.000, 0.100, 0.830, 10.630, 24.170, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 10.630, 10.63, 0.00, 17.99, 4.45, '157', '', 'active', 0, 0, 0, 1, 1, '2026-06-10 15:41:13', '2026-06-16 09:30:39', NULL),
(13, 'INV-00013', 16, 'customer', '2026-06-02', 44.440, 0.440, 44.880, 15.000, 0.100, 7.010, 37.870, 29.840, 0.00, 0.00, 0.190, 0.00, 7500.00, 0.000, 0.00, 0.00, 38.060, 38.06, 0.00, 6.54, 14.76, '136', '', 'active', 0, 0, 0, 1, NULL, '2026-06-10 15:43:03', '2026-06-10 15:43:03', NULL),
(14, 'INV-00014', 16, 'customer', '2026-06-10', 36.280, 0.360, 36.640, 15.000, 0.100, 5.720, 30.920, 35.330, 0.00, 0.00, 0.130, 0.00, 4500.00, 0.000, 0.00, 0.00, 31.050, 31.05, 0.00, 14.76, 10.48, '160', '', 'active', 0, 0, 0, 1, 1, '2026-06-10 15:43:21', '2026-06-15 09:51:48', NULL),
(15, 'INV-00015', 17, 'customer', '2026-06-04', 117.130, 0.940, 118.070, 11.000, 0.080, 13.530, 104.540, 106.210, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 104.540, 104.54, 0.00, 0.00, -1.67, '138', '', 'active', 0, 0, 0, 1, NULL, '2026-06-10 15:47:48', '2026-06-10 15:47:48', NULL),
(16, 'INV-00016', 17, 'customer', '2026-06-06', 126.760, 1.010, 127.770, 11.000, 0.080, 14.640, 113.130, 102.780, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 113.130, 113.13, 0.00, -1.67, 8.68, '147', '', 'active', 0, 0, 0, 1, NULL, '2026-06-10 15:55:51', '2026-06-10 15:55:51', NULL),
(17, 'INV-00017', 17, 'customer', '2026-06-09', 5.380, 0.040, 5.420, 11.000, 0.080, 0.620, 4.800, 5.800, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 4.800, 4.80, 0.00, 8.68, 7.68, '152', '', 'active', 0, 0, 0, 1, NULL, '2026-06-10 15:56:32', '2026-06-10 15:56:32', NULL),
(18, 'INV-00018', 20, 'karigar', '2026-06-05', 30.370, 0.300, 30.670, 11.000, 0.100, 3.510, 27.160, 8.050, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 27.160, 27.16, 0.00, -29.78, -10.67, '141', '', 'active', 0, 0, 0, 1, 1, '2026-06-10 15:59:09', '2026-06-16 09:28:40', NULL),
(19, 'INV-00019', 16, 'customer', '2026-06-12', 43.210, 0.520, 43.730, 16.000, 0.120, 7.290, 36.440, 25.750, 0.00, 0.00, 0.270, 0.00, 9500.00, 0.000, 0.00, 0.00, 36.710, 36.71, 0.00, 10.48, 21.44, '164', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 08:54:55', '2026-06-16 08:54:55', NULL),
(20, 'INV-00020', 17, 'customer', '2026-06-11', 129.600, 1.040, 130.640, 11.000, 0.080, 14.970, 115.670, 116.560, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 115.670, 115.67, 0.00, 7.68, 6.79, '159', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 08:56:23', '2026-06-16 08:56:23', NULL),
(21, 'INV-00021', 17, 'customer', '2026-06-13', 5.220, 0.040, 5.260, 11.000, 0.080, 0.600, 4.660, 6.880, 0.00, 0.00, 0.000, 0.00, 0.00, 6.880, 0.00, 0.00, 11.540, 11.54, 0.00, 6.79, 11.45, '169', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 08:58:26', '2026-06-16 08:58:26', NULL),
(22, 'INV-00022', 20, 'customer', '2026-06-05', 0.000, 0.000, 0.000, 11.000, 0.100, 0.000, 0.000, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, -10.67, -10.67, '', '', 'active', 0, 0, 0, 1, 1, '2026-06-16 08:59:49', '2026-06-18 09:57:54', NULL),
(23, 'INV-00023', 20, 'customer', '2026-06-15', 35.580, 0.350, 35.930, 11.000, 0.100, 4.120, 31.810, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 31.810, 31.81, 0.00, -10.67, 21.14, '171', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:00:31', '2026-06-18 09:57:54', NULL),
(24, 'INV-00024', 21, 'customer', '2026-06-12', 27.120, 0.270, 27.390, 11.000, 0.100, 3.140, 24.250, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 39.270, 0.00, 0.00, 63.520, 63.52, 0.00, 2.00, 65.52, '163', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:03:09', '2026-06-16 09:03:09', NULL),
(25, 'INV-00025', 3, 'customer', '2026-06-10', 2.600, 0.030, 2.630, 15.000, 0.100, 0.410, 2.220, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 2.220, 2.22, 0.00, 6.60, 8.82, '155', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:04:51', '2026-06-16 09:04:51', NULL),
(26, 'INV-00026', 3, 'customer', '2026-06-10', 89.210, 0.890, 90.100, 11.000, 0.100, 10.320, 79.780, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 2.660, 0.00, 0.00, 82.440, 82.44, 0.00, 8.82, 91.26, '156', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:05:24', '2026-06-16 09:05:24', NULL),
(27, 'INV-00027', 3, 'customer', '2026-06-12', 87.400, 0.870, 88.270, 11.000, 0.100, 10.110, 78.160, 2.610, 0.00, 0.00, 0.000, 0.00, 0.00, 2.950, 0.00, 0.00, 81.110, 81.11, 0.00, 91.26, 169.76, '161', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:06:32', '2026-06-16 09:06:32', NULL),
(28, 'INV-00028', 3, 'customer', '2026-06-13', 23.350, 0.250, 23.600, 16.000, 0.110, 3.930, 19.670, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 24.180, 0.00, 0.00, 43.850, 43.85, 0.00, 169.76, 213.61, '167', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:08:40', '2026-06-16 09:08:40', NULL),
(29, 'INV-00029', 3, 'customer', '2026-06-13', 110.040, 1.100, 111.140, 11.000, 0.100, 12.730, 98.410, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 98.410, 98.41, 0.00, 213.61, 312.02, '168', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:09:04', '2026-06-16 09:09:04', NULL),
(30, 'INV-00030', 3, 'customer', '2026-06-15', 27.040, 0.270, 27.310, 11.000, 0.100, 3.130, 24.180, 318.170, 0.00, 0.00, 0.000, 0.00, 0.00, 0.700, 0.00, 0.00, 24.880, 24.88, 0.00, 312.02, 18.73, '172', '', 'active', 0, 0, 0, 1, 1, '2026-06-16 09:09:26', '2026-06-17 10:19:50', NULL),
(31, 'INV-00031', 22, 'customer', '2026-06-05', 123.020, 1.230, 124.250, 11.000, 0.100, 14.240, 110.010, 109.370, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 110.010, 110.01, 0.00, 0.00, 0.64, '142', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:11:56', '2026-06-16 09:11:56', NULL),
(32, 'INV-00032', 1, 'customer', '2026-06-10', 39.950, 0.400, 40.350, 15.000, 0.100, 6.300, 34.050, 50.520, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 34.050, 34.05, 0.00, 36.27, 19.80, '158', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:12:45', '2026-06-18 15:10:34', NULL),
(33, 'INV-00033', 1, 'customer', '2026-06-13', 32.340, 0.320, 32.660, 14.000, 0.100, 4.760, 27.900, 39.620, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 27.900, 27.90, 0.00, 19.80, 8.08, '166', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:13:16', '2026-06-18 15:10:34', NULL),
(34, 'INV-00034', 1, 'customer', '2026-06-15', 60.740, 0.600, 61.340, 15.000, 0.100, 9.580, 51.760, 49.680, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 51.760, 51.76, 0.00, 8.08, 10.16, '174', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:13:53', '2026-06-18 15:10:34', NULL),
(35, 'INV-00035', 1, 'customer', '2026-06-16', 52.650, 0.580, 53.230, 16.000, 0.110, 8.870, 44.360, 40.480, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 44.360, 44.36, 0.00, 10.16, 14.04, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-16 09:15:57', '2026-06-18 15:10:34', NULL),
(36, 'INV-00036', 6, 'customer', '2026-06-15', 5.750, 0.060, 5.810, 16.000, 0.110, 0.970, 4.840, 3.180, 0.00, 0.00, 0.030, 0.00, 1000.00, 0.000, 0.00, 0.00, 4.870, 4.87, 0.00, -4.66, -2.97, '173', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:21:01', '2026-06-16 09:21:01', NULL),
(37, 'INV-00037', 7, 'customer', '2026-06-15', 61.510, 0.670, 62.180, 16.000, 0.110, 10.360, 51.820, 51.110, 0.00, 0.00, 0.140, 0.00, 5000.00, 0.000, 0.00, 0.00, 51.960, 51.96, 0.00, 6.30, 7.15, '170', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:24:24', '2026-06-16 09:24:24', NULL),
(38, 'INV-00038', 23, 'customer', '2026-06-12', 5.030, 0.050, 5.080, 11.000, 0.100, 0.580, 4.500, 4.530, 0.00, 0.00, 0.030, 0.00, 1000.00, 0.000, 0.00, 0.00, 4.530, 4.53, 0.00, 0.00, 0.00, '162', '', 'active', 0, 0, 0, 1, 1, '2026-06-16 09:25:20', '2026-06-16 09:26:22', NULL),
(39, 'INV-00039', 14, 'customer', '2026-06-13', 29.210, 0.320, 29.530, 16.000, 0.110, 4.920, 24.610, 27.180, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 24.610, 24.61, 0.00, 4.45, 1.88, '165', '', 'active', 0, 0, 0, 1, NULL, '2026-06-16 09:31:23', '2026-06-16 09:31:23', NULL),
(40, 'INV-00040', 17, 'customer', '2026-06-17', 8.870, 0.070, 8.940, 12.000, 0.080, 1.120, 7.820, 140.840, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 7.820, 7.82, 0.00, 11.45, -121.57, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-16 09:34:29', '2026-06-18 10:25:10', NULL),
(41, 'INV-00041', 24, 'customer', '2026-06-16', 8.240, 0.090, 8.330, 16.000, 0.110, 1.390, 6.940, 7.030, 0.00, 0.00, 0.090, 0.00, 3000.00, 0.000, 0.00, 0.00, 7.030, 7.03, 0.00, 0.00, 0.00, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-16 09:37:24', '2026-06-17 10:39:15', NULL),
(42, 'INV-00042', 2, 'customer', '2026-06-17', 89.060, 0.890, 89.950, 15.000, 0.100, 14.050, 75.900, 50.710, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 75.900, 75.90, 0.00, 52.91, 78.10, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-17 10:15:58', '2026-06-17 11:15:02', NULL),
(43, 'INV-00043', 24, 'customer', '2026-06-17', 7.790, 0.080, 7.870, 15.000, 0.100, 1.230, 6.640, 6.690, 0.00, 0.00, 0.050, 0.00, 2000.00, 0.000, 0.00, 0.00, 6.690, 6.69, 0.00, 0.00, 0.00, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-17 10:40:32', '2026-06-17 15:34:37', NULL),
(44, 'INV-00044', 21, 'customer', '2026-06-17', 47.190, 0.470, 47.660, 11.000, 0.100, 5.460, 42.200, 61.620, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 42.200, 42.20, 0.00, 65.52, 46.10, '177', '', 'active', 0, 0, 0, 1, NULL, '2026-06-17 10:59:39', '2026-06-17 10:59:39', NULL),
(45, 'INV-00045', 14, 'customer', '2026-06-17', 23.000, 0.000, 23.000, 7.000, 0.000, 0.000, 23.000, 65.540, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 23.000, 23.00, 0.00, 1.88, -40.66, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-17 15:04:57', '2026-06-17 15:51:00', NULL),
(46, 'INV-00046', 3, 'customer', '2026-06-17', 46.750, 0.470, 47.220, 11.000, 0.100, 6.370, 40.850, 20.730, 0.00, 0.00, 0.070, 0.00, 2500.00, 5.760, 0.00, 0.00, 46.680, 46.68, 0.00, 18.73, 44.68, '175176', '', 'active', 0, 0, 0, 1, 1, '2026-06-17 15:33:55', '2026-06-18 11:45:13', NULL),
(47, 'INV-00047', 1, 'customer', '2026-06-17', 46.980, 0.470, 47.450, 14.000, 0.100, 6.920, 40.530, 71.860, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 40.530, 40.53, 0.00, 14.04, -17.29, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-17 15:37:18', '2026-06-18 15:10:34', NULL),
(48, 'INV-00048', 21, 'customer', '2026-06-17', 61.880, 0.620, 62.500, 11.000, 0.100, 7.160, 55.340, 51.440, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 55.340, 55.34, 0.00, 46.10, 50.00, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-17 15:50:01', '2026-06-18 11:49:50', NULL),
(49, 'INV-00049', 14, 'customer', '2026-06-18', 51.780, 0.570, 52.350, 16.000, 0.110, 8.720, 43.630, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 43.630, 43.63, 0.00, -40.66, 2.97, '0', '', 'active', 0, 0, 0, 1, NULL, '2026-06-18 10:22:08', '2026-06-18 10:22:08', NULL),
(50, 'INV-00050', 14, 'customer', '2026-06-18', 13.300, 0.130, 13.430, 12.000, 0.100, 1.680, 11.750, 0.000, 0.00, 0.00, 0.260, 0.00, 9500.00, 1.070, 0.00, 0.00, 13.080, 13.08, 0.00, 2.97, 16.05, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-18 10:22:44', '2026-06-18 11:57:30', NULL),
(51, 'INV-00051', 25, 'customer', '2026-06-18', 3.060, 0.030, 3.090, 12.000, 0.100, 0.380, 2.710, 2.710, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 2.710, 2.71, 0.00, 0.00, 0.00, '0', '', 'active', 0, 0, 0, 1, 1, '2026-06-18 10:28:56', '2026-06-18 15:11:52', NULL),
(52, 'INV-00052', 1, 'customer', '2026-06-18', 33.850, 0.340, 34.190, 12.000, 0.100, 4.270, 29.920, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 29.920, 29.92, 0.00, -17.29, 12.63, '0', '', 'active', 0, 0, 0, 1, NULL, '2026-06-18 10:33:04', '2026-06-18 15:10:34', NULL),
(53, 'INV-00053', 4, 'customer', '2026-06-29', 11.000, 0.110, 11.110, 13.000, 0.100, 1.500, 9.610, 50.000, 0.00, 0.00, 0.000, 0.00, 0.00, 0.000, 0.00, 0.00, 9.610, 9.61, 0.00, 0.00, -40.39, '0', '', 'active', 0, 0, 0, 1, NULL, '2026-06-29 11:10:42', '2026-06-29 11:10:42', NULL),
(54, 'INV-00054', 13, 'customer', '2026-07-02', 11.000, 0.110, 11.110, 11.000, 0.100, 1.270, 9.840, 8.640, 0.00, 0.00, 0.010, 0.00, 2500.00, 0.020, 0.00, 6000.00, 9.870, 9.87, 0.00, -9.04, -7.81, '0', '', 'active', 0, 0, 0, 1, NULL, '2026-07-02 11:23:44', '2026-07-02 11:25:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_multiple`
--

CREATE TABLE `invoice_multiple` (
  `id` int(11) NOT NULL,
  `invoice_no` varchar(50) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_type` enum('customer','dukandar','karigar') DEFAULT 'customer',
  `invoice_date` date NOT NULL,
  `manual_book_no` varchar(50) DEFAULT NULL,
  `total_casting_weight` decimal(10,3) DEFAULT 0.000,
  `total_waste_weight` decimal(10,3) DEFAULT 0.000,
  `total_weight` decimal(10,3) DEFAULT 0.000,
  `total_male_waste` decimal(10,3) DEFAULT 0.000,
  `total_gold_khalis` decimal(10,3) DEFAULT 0.000,
  `total_received_khalis` decimal(10,3) DEFAULT 0.000,
  `total_rp_mazdori_weight` decimal(10,3) DEFAULT 0.000,
  `total_rp_mazdori_amount` decimal(15,2) DEFAULT 0.00,
  `total_casting_mazdori_weight` decimal(10,3) DEFAULT 0.000,
  `total_casting_mazdori_amount` decimal(15,2) DEFAULT 0.00,
  `effective_gold` decimal(10,3) DEFAULT 0.000,
  `grand_total` decimal(15,3) DEFAULT 0.000,
  `wasooli` decimal(15,3) DEFAULT 0.000,
  `previous_balance` decimal(15,3) DEFAULT 0.000,
  `remaining_balance` decimal(15,3) DEFAULT 0.000,
  `remarks` text DEFAULT NULL,
  `status` enum('active','cancelled') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_multiple`
--

INSERT INTO `invoice_multiple` (`id`, `invoice_no`, `customer_id`, `invoice_type`, `invoice_date`, `manual_book_no`, `total_casting_weight`, `total_waste_weight`, `total_weight`, `total_male_waste`, `total_gold_khalis`, `total_received_khalis`, `total_rp_mazdori_weight`, `total_rp_mazdori_amount`, `total_casting_mazdori_weight`, `total_casting_mazdori_amount`, `effective_gold`, `grand_total`, `wasooli`, `previous_balance`, `remaining_balance`, `remarks`, `status`, `created_by`, `updated_by`, `created_at`, `updated_at`, `deleted_at`) VALUES
(1, 'MINV-00001', 3, 'customer', '2026-06-18', '', 15.000, 0.150, 15.150, 1.730, 13.420, 4.420, 0.000, 0.00, 0.000, 0.00, 13.420, 13.420, 0.000, 44.680, 53.680, '', 'cancelled', 1, 1, '2026-06-18 15:05:56', '2026-06-18 15:12:41', '2026-06-18 20:12:41'),
(2, 'MINV-00002', 3, 'karigar', '2026-06-26', '0', 15.000, 0.150, 15.150, 1.730, 13.420, 0.000, 0.000, 0.00, 0.000, 0.00, 13.420, 13.420, 0.000, 44.680, 58.100, '', 'active', 1, 1, '2026-06-29 11:08:42', '2026-06-29 14:10:32', NULL),
(3, 'MINV-00003', 3, 'customer', '2026-06-29', '', 30.000, 0.300, 30.300, 4.300, 26.000, 20.000, 0.000, 0.00, 0.000, 0.00, 26.000, 26.000, 0.000, 44.680, 50.680, '', 'active', 1, 1, '2026-06-29 14:10:44', '2026-06-29 15:18:12', NULL),
(4, 'MINV-00004', 13, 'customer', '2026-07-02', '0', 30.000, 0.330, 30.330, 4.860, 25.470, 18.470, 0.030, 0.00, 0.070, 0.00, 25.570, 25.570, 0.000, 1.350, 8.450, '', 'active', 1, 1, '2026-07-02 11:24:48', '2026-07-02 13:15:01', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `invoice_multiple_items`
--

CREATE TABLE `invoice_multiple_items` (
  `id` int(11) NOT NULL,
  `invoice_multiple_id` int(11) NOT NULL,
  `item_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `casting_weight` decimal(10,3) DEFAULT 0.000,
  `ratti` decimal(10,3) DEFAULT 0.000,
  `ratti_rate` decimal(10,3) DEFAULT 0.000,
  `waste_weight` decimal(10,3) DEFAULT 0.000,
  `total_weight` decimal(10,3) DEFAULT 0.000,
  `male_waste` decimal(10,3) DEFAULT 0.000,
  `gold_khalis` decimal(10,3) DEFAULT 0.000,
  `rp_mazdori_weight` decimal(10,3) DEFAULT 0.000,
  `rp_mazdori_amount` decimal(15,2) DEFAULT 0.00,
  `casting_mazdori_weight` decimal(10,3) DEFAULT 0.000,
  `casting_mazdori_amount` decimal(15,2) DEFAULT 0.00,
  `effective_gold` decimal(10,3) DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_multiple_items`
--

INSERT INTO `invoice_multiple_items` (`id`, `invoice_multiple_id`, `item_date`, `description`, `casting_weight`, `ratti`, `ratti_rate`, `waste_weight`, `total_weight`, `male_waste`, `gold_khalis`, `rp_mazdori_weight`, `rp_mazdori_amount`, `casting_mazdori_weight`, `casting_mazdori_amount`, `effective_gold`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-06-18', '', 10.000, 11.000, 0.100, 0.100, 10.100, 1.150, 8.950, 0.000, 0.00, 0.000, 0.00, 8.950, '2026-06-18 15:05:56', '2026-06-29 15:55:54'),
(2, 1, '2026-06-18', '', 5.000, 11.000, 0.100, 0.050, 5.050, 0.580, 4.470, 0.000, 0.00, 0.000, 0.00, 4.470, '2026-06-18 15:05:56', '2026-06-29 15:55:54'),
(9, 2, '2026-06-26', '', 15.000, 11.000, 0.100, 0.150, 15.150, 1.730, 13.420, 0.000, 0.00, 0.000, 0.00, 13.420, '2026-06-29 14:10:32', '2026-06-29 15:55:54'),
(15, 3, '2026-06-17', '', 10.000, 11.000, 0.100, 0.100, 10.100, 1.150, 8.950, 0.000, 0.00, 0.000, 0.00, 8.950, '2026-06-29 16:00:48', '2026-06-29 16:00:48'),
(16, 3, '2026-06-29', '', 20.000, 15.000, 0.100, 0.200, 20.200, 3.150, 17.050, 0.000, 0.00, 0.000, 0.00, 17.050, '2026-06-29 16:00:48', '2026-06-29 16:00:48'),
(19, 4, '2026-07-01', '', 10.000, 14.000, 0.100, 0.110, 10.110, 1.470, 8.640, 0.010, 0.00, 0.030, 0.00, 8.680, '2026-07-02 13:15:01', '2026-07-02 13:15:01'),
(20, 4, '2026-07-02', '', 20.000, 16.000, 0.110, 0.220, 20.220, 3.390, 16.830, 0.020, 0.00, 0.040, 0.00, 16.890, '2026-07-02 13:15:01', '2026-07-02 13:15:01');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_multiple_receives`
--

CREATE TABLE `invoice_multiple_receives` (
  `id` int(11) NOT NULL,
  `invoice_multiple_id` int(11) NOT NULL,
  `receive_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gross_weight` decimal(10,3) DEFAULT 0.000,
  `ratti_impurity` decimal(10,3) DEFAULT 0.000,
  `khalis_weight` decimal(10,3) DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_multiple_receives`
--

INSERT INTO `invoice_multiple_receives` (`id`, `invoice_multiple_id`, `receive_date`, `description`, `gross_weight`, `ratti_impurity`, `khalis_weight`, `created_at`, `updated_at`) VALUES
(1, 1, '2026-06-18', '', 5.000, 10.997, 4.420, '2026-06-18 15:05:56', '2026-06-29 15:55:54'),
(5, 3, '2026-06-27', '', 20.000, 0.000, 20.000, '2026-06-29 16:00:48', '2026-06-29 16:00:48'),
(8, 4, '2026-06-30', '', 10.000, 11.000, 8.850, '2026-07-02 13:15:01', '2026-07-02 13:15:01'),
(9, 4, '2026-07-01', '', 11.000, 12.000, 9.620, '2026-07-02 13:15:01', '2026-07-02 13:15:01');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_receives`
--

CREATE TABLE `invoice_receives` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gross_weight` decimal(10,3) DEFAULT 0.000,
  `ratti_impurity` decimal(10,3) DEFAULT 0.000,
  `khalis_weight` decimal(10,3) DEFAULT 0.000,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `invoice_receives`
--

INSERT INTO `invoice_receives` (`id`, `invoice_id`, `description`, `gross_weight`, `ratti_impurity`, `khalis_weight`, `created_at`, `updated_at`) VALUES
(34, 1, '', 26.080, 0.000, 26.080, '2026-06-10 10:00:47', '2026-06-10 10:00:47'),
(35, 2, '', 31.910, 0.000, 31.910, '2026-06-10 10:02:12', '2026-06-10 10:02:12'),
(55, 3, 'khalis', 17.330, 0.000, 17.330, '2026-06-10 11:55:49', '2026-06-10 11:55:49'),
(56, 3, 'khalis', 8.770, 0.000, 8.770, '2026-06-10 11:55:49', '2026-06-10 11:55:49'),
(57, 3, 'khalis', 31.850, 0.000, 31.850, '2026-06-10 11:55:49', '2026-06-10 11:55:49'),
(58, 8, '', 180.710, 0.000, 180.710, '2026-06-10 12:22:39', '2026-06-10 12:22:39'),
(59, 6, '282000 rs', 7.410, 0.000, 7.410, '2026-06-10 15:31:25', '2026-06-10 15:31:25'),
(60, 9, '', 33.230, 0.000, 33.230, '2026-06-10 15:36:16', '2026-06-10 15:36:16'),
(63, 13, '', 29.840, 0.000, 29.840, '2026-06-10 15:43:03', '2026-06-10 15:43:03'),
(65, 15, 'rs.693650 ', 17.660, 0.000, 17.660, '2026-06-10 15:47:48', '2026-06-10 15:47:48'),
(66, 15, '', 28.000, 0.000, 28.000, '2026-06-10 15:47:48', '2026-06-10 15:47:48'),
(67, 15, '', 41.980, 0.000, 41.980, '2026-06-10 15:47:48', '2026-06-10 15:47:48'),
(68, 15, 'Wapsi 20.82+waste', 20.980, 11.000, 18.570, '2026-06-10 15:47:48', '2026-06-10 15:47:48'),
(69, 16, '', 95.710, 0.000, 95.710, '2026-06-10 15:55:51', '2026-06-10 15:55:51'),
(70, 16, '', 7.070, 0.000, 7.070, '2026-06-10 15:55:51', '2026-06-10 15:55:51'),
(71, 17, '', 5.800, 0.000, 5.800, '2026-06-10 15:56:32', '2026-06-10 15:56:32'),
(73, 14, '', 35.330, 0.000, 35.330, '2026-06-15 09:51:48', '2026-06-15 09:51:48'),
(74, 5, '', 25.010, 0.000, 25.010, '2026-06-15 10:15:48', '2026-06-15 10:15:48'),
(75, 19, '', 25.750, 0.000, 25.750, '2026-06-16 08:54:55', '2026-06-16 08:54:55'),
(76, 20, '', 101.660, 0.000, 101.660, '2026-06-16 08:56:23', '2026-06-16 08:56:23'),
(77, 20, '', 14.900, 0.000, 14.900, '2026-06-16 08:56:23', '2026-06-16 08:56:23'),
(78, 21, 'return', 7.770, 11.000, 6.880, '2026-06-16 08:58:26', '2026-06-16 08:58:26'),
(80, 27, '4.09 ring with naag', 2.950, 11.000, 2.610, '2026-06-16 09:06:32', '2026-06-16 09:06:32'),
(81, 31, '', 105.630, 8.495, 96.280, '2026-06-16 09:11:56', '2026-06-16 09:11:56'),
(82, 31, '', 13.090, 0.000, 13.090, '2026-06-16 09:11:56', '2026-06-16 09:11:56'),
(83, 32, '', 50.520, 0.000, 50.520, '2026-06-16 09:12:45', '2026-06-16 09:12:45'),
(84, 33, '', 39.620, 0.000, 39.620, '2026-06-16 09:13:16', '2026-06-16 09:13:16'),
(85, 34, '', 49.680, 0.000, 49.680, '2026-06-16 09:13:53', '2026-06-16 09:13:53'),
(88, 36, '', 3.180, 0.000, 3.180, '2026-06-16 09:21:01', '2026-06-16 09:21:01'),
(89, 10, '', 41.510, 0.000, 41.510, '2026-06-16 09:23:34', '2026-06-16 09:23:34'),
(90, 10, '', 5.680, 15.920, 4.740, '2026-06-16 09:23:34', '2026-06-16 09:23:34'),
(91, 37, '', 51.110, 0.000, 51.110, '2026-06-16 09:24:24', '2026-06-16 09:24:24'),
(92, 38, '', 4.530, 0.000, 4.530, '2026-06-16 09:26:22', '2026-06-16 09:26:22'),
(93, 18, '', 9.090, 11.000, 8.050, '2026-06-16 09:28:40', '2026-06-16 09:28:40'),
(94, 11, '', 50.000, 0.000, 50.000, '2026-06-16 09:29:49', '2026-06-16 09:29:49'),
(95, 12, '', 24.170, 0.000, 24.170, '2026-06-16 09:30:39', '2026-06-16 09:30:39'),
(96, 39, '', 27.180, 0.000, 27.180, '2026-06-16 09:31:23', '2026-06-16 09:31:23'),
(97, 35, '', 40.480, 0.000, 40.480, '2026-06-16 09:38:03', '2026-06-16 09:38:03'),
(102, 30, '', 19.320, 8.000, 17.710, '2026-06-17 10:19:50', '2026-06-17 10:19:50'),
(103, 30, '', 109.530, 8.500, 99.830, '2026-06-17 10:19:50', '2026-06-17 10:19:50'),
(104, 30, '', 218.870, 8.000, 200.630, '2026-06-17 10:19:50', '2026-06-17 10:19:50'),
(106, 41, '', 7.030, 0.000, 7.030, '2026-06-17 10:39:15', '2026-06-17 10:39:15'),
(107, 44, '', 40.110, 8.500, 36.560, '2026-06-17 10:59:39', '2026-06-17 10:59:39'),
(108, 44, '', 10.600, 8.500, 9.660, '2026-06-17 10:59:39', '2026-06-17 10:59:39'),
(109, 44, 'wapsi', 17.390, 10.950, 15.400, '2026-06-17 10:59:39', '2026-06-17 10:59:39'),
(110, 42, '', 50.710, 0.000, 50.710, '2026-06-17 11:15:02', '2026-06-17 11:15:02'),
(112, 43, '', 6.690, 0.000, 6.690, '2026-06-17 15:34:37', '2026-06-17 15:34:37'),
(118, 45, '', 51.550, 0.000, 51.550, '2026-06-17 15:51:00', '2026-06-17 15:51:00'),
(119, 45, '', 13.990, 0.000, 13.990, '2026-06-17 15:51:00', '2026-06-17 15:51:00'),
(122, 40, '', 140.360, 7.500, 129.390, '2026-06-18 10:25:10', '2026-06-18 10:25:10'),
(123, 40, '', 11.450, 0.000, 11.450, '2026-06-18 10:25:10', '2026-06-18 10:25:10'),
(124, 47, '', 32.910, 0.000, 32.910, '2026-06-18 10:31:48', '2026-06-18 10:31:48'),
(125, 47, '', 38.950, 0.000, 38.950, '2026-06-18 10:31:48', '2026-06-18 10:31:48'),
(126, 46, '', 22.490, 7.500, 20.730, '2026-06-18 11:45:13', '2026-06-18 11:45:13'),
(127, 48, '', 29.060, 8.490, 26.490, '2026-06-18 11:49:50', '2026-06-18 11:49:50'),
(128, 48, '', 27.370, 8.490, 24.950, '2026-06-18 11:49:50', '2026-06-18 11:49:50'),
(129, 51, '', 2.710, 0.000, 2.710, '2026-06-18 15:11:52', '2026-06-18 15:11:52'),
(130, 53, '', 50.000, 0.000, 50.000, '2026-06-29 11:10:42', '2026-06-29 11:10:42'),
(131, 54, '', 10.000, 13.000, 8.640, '2026-07-02 11:23:44', '2026-07-02 11:23:44');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('text','number','boolean','json') DEFAULT 'text',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `setting_key`, `setting_value`, `setting_type`, `created_at`, `updated_at`) VALUES
(1, 'workshop_name', 'M.J Casting', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(2, 'workshop_name_urdu', 'ایم جے کاسٹنگ', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(3, 'address', 'دوکان # 882 ، عتیتق سنٹر، گجر گلی ،رنگ محل لاہور۔', 'text', '2026-06-02 11:35:08', '2026-06-10 14:50:51'),
(4, 'phone', '0302-4098908', 'text', '2026-06-02 11:35:08', '2026-06-10 14:48:58'),
(5, 'phone2', '0307-0003777', 'text', '2026-06-02 11:35:08', '2026-06-10 14:48:58'),
(6, 'phone3', '03224773342', 'text', '2026-06-02 11:35:08', '2026-06-10 14:48:58'),
(7, 'city', 'Lahore', 'text', '2026-06-02 11:35:08', '2026-06-10 14:48:58'),
(8, 'messenger', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(9, 'social', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(10, 'shop_name', 'Gold Workshop', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(11, 'shop_address', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(12, 'shop_phone', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(13, 'currency', 'PKR', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(14, 'default_rp_rate', '0', 'text', '2026-06-02 11:35:08', '2026-06-08 15:30:43'),
(15, 'default_gram_rate', '0', 'text', '2026-06-02 11:35:08', '2026-06-08 15:30:43'),
(16, 'default_waste_rate', '0.125', 'text', '2026-06-02 11:35:08', '2026-06-08 15:30:43'),
(17, 'default_ratti_rate', '0.100', 'text', '2026-06-02 11:35:08', '2026-06-08 15:30:43'),
(18, 'ratti_tiers', '[{\"max_weight\":15,\"ratti\":0.1},{\"max_weight\":25,\"ratti\":0.2},{\"max_weight\":40,\"ratti\":0.3},{\"max_weight\":60,\"ratti\":0.4},{\"max_weight\":9999,\"ratti\":0.5}]', 'json', '2026-06-02 11:35:08', '2026-06-02 11:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@goldworkshop.test', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(2, 'User', 'user@goldworkshop.test', NULL, '$2y$10$HfzIhGCCaxqya1G3I8R6f.Rx0YN0kPwz1kJRozJ1UNRmBKXny1K2', NULL, '2026-06-02 11:35:08', '2026-06-02 11:35:08');

-- --------------------------------------------------------

--
-- Table structure for table `wax_customers`
--

CREATE TABLE `wax_customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `billing_style` enum('weekly','monthly','cash') DEFAULT 'cash',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_customers`
--

INSERT INTO `wax_customers` (`id`, `name`, `contact`, `address`, `opening_balance`, `billing_style`, `active`, `created_at`) VALUES
(1, 'Sunny', '', NULL, 104350.00, 'cash', 1, '2026-06-02 11:39:50'),
(2, 'Waseem Bhai', '', NULL, 0.00, 'cash', 1, '2026-06-02 11:42:32'),
(3, 'Kashif Bhai', '', NULL, 5250.00, 'cash', 1, '2026-06-05 08:40:42'),
(4, 'Faheem Bhai', '', NULL, 0.00, 'cash', 1, '2026-06-05 08:54:39'),
(5, 'Shahid Bhai', '', NULL, 0.00, 'cash', 1, '2026-06-05 08:54:53'),
(6, 'Mohsin Wax', '', NULL, 0.00, 'cash', 1, '2026-06-05 08:55:29'),
(7, 'Fija', '', NULL, 0.00, 'cash', 1, '2026-06-06 08:47:27'),
(8, 'Furqan', '', NULL, 0.00, 'cash', 1, '2026-06-06 08:49:59'),
(9, 'Rohail', '', NULL, 0.00, 'cash', 1, '2026-06-06 08:50:04'),
(10, 'Shafiq', '', NULL, 85200.00, 'cash', 1, '2026-06-06 08:50:15'),
(11, 'Uncle Zulfiqar', '', NULL, 0.00, 'cash', 1, '2026-06-06 08:50:30'),
(12, 'Munawar Pattan', '', NULL, 0.00, 'cash', 1, '2026-06-06 08:50:43'),
(13, 'Khurrum', '', NULL, 63500.00, 'cash', 1, '2026-06-06 08:51:01'),
(14, 'Hassan Bhai', '', NULL, 0.00, 'cash', 1, '2026-06-06 08:51:09'),
(15, 'Waseem Sargodha', '', NULL, 0.00, 'cash', 1, '2026-06-06 12:24:53'),
(16, 'Qazim', '', NULL, 0.00, 'cash', 1, '2026-06-13 13:00:11'),
(17, 'Ahsan', '', NULL, 0.00, 'cash', 1, '2026-06-13 13:09:15'),
(18, 'Waheed Khalil', '', NULL, 0.00, 'cash', 1, '2026-06-13 14:58:04');

-- --------------------------------------------------------

--
-- Table structure for table `wax_expenses`
--

CREATE TABLE `wax_expenses` (
  `id` int(11) NOT NULL,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_expenses`
--

INSERT INTO `wax_expenses` (`id`, `expense_date`, `category`, `amount`, `notes`, `created_at`) VALUES
(1, '2026-06-02', 'Abdul Rehman', 100.00, '', '2026-06-02 13:00:15'),
(2, '2026-06-02', 'Bijli Bill', 200.00, '', '2026-06-02 13:00:15');

-- --------------------------------------------------------

--
-- Table structure for table `wax_expense_categories`
--

CREATE TABLE `wax_expense_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_expense_categories`
--

INSERT INTO `wax_expense_categories` (`id`, `name`, `active`) VALUES
(1, 'Abdul Rehman', 1),
(2, 'Rent Shop', 1),
(3, 'Roti Khana', 1),
(4, 'Misc Exp', 1),
(5, 'Bijli Bill', 1),
(6, 'Sohail Khan', 1);

-- --------------------------------------------------------

--
-- Table structure for table `wax_invoices`
--

CREATE TABLE `wax_invoices` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `editable_flag` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_invoices`
--

INSERT INTO `wax_invoices` (`id`, `customer_id`, `invoice_date`, `total_amount`, `editable_flag`, `created_by`, `created_at`) VALUES
(4, 3, '2026-06-02', 825.00, 1, 1, '2026-06-02 13:51:30'),
(5, 4, '2026-06-03', 3275.00, 1, 1, '2026-06-02 13:52:19'),
(6, 1, '2026-06-03', 6350.00, 1, 1, '2026-06-02 13:52:19'),
(7, 6, '2026-06-02', 644.00, 1, 1, '2026-06-02 14:20:55'),
(8, 1, '2026-06-04', 1750.00, 1, 1, '2026-06-06 08:45:00'),
(9, 6, '2026-06-04', 252.00, 1, 1, '2026-06-06 08:46:11'),
(10, 7, '2026-06-04', 1925.00, 1, 1, '2026-06-06 08:48:01'),
(11, 1, '2026-06-04', 122400.00, 1, 1, '2026-06-06 08:56:50'),
(12, 8, '2026-06-04', 1725.00, 1, 1, '2026-06-06 08:56:50'),
(13, 9, '2026-06-04', 6187.50, 1, 1, '2026-06-06 08:56:50'),
(14, 10, '2026-06-04', 2125.00, 1, 1, '2026-06-06 08:56:50'),
(15, 11, '2026-06-04', 4987.50, 1, 1, '2026-06-06 08:56:50'),
(16, 12, '2026-06-04', 4925.00, 1, 1, '2026-06-06 08:56:50'),
(17, 2, '2026-06-04', 9636.00, 1, 1, '2026-06-06 08:56:50'),
(18, 6, '2026-06-04', 10353.00, 1, 1, '2026-06-06 08:56:50'),
(19, 13, '2026-06-04', 1925.00, 1, 1, '2026-06-06 08:56:50'),
(20, 14, '2026-06-04', 5012.50, 1, 1, '2026-06-06 08:56:50'),
(22, 16, '2026-06-12', 2650.00, 1, 1, '2026-06-13 13:09:03'),
(23, 10, '2026-06-12', 2950.00, 1, 1, '2026-06-13 13:09:03'),
(24, 1, '2026-06-12', 6850.00, 1, 1, '2026-06-13 13:09:03'),
(25, 13, '2026-06-12', 1150.00, 1, 1, '2026-06-13 13:09:03'),
(26, 14, '2026-06-12', 5812.50, 1, 1, '2026-06-13 13:09:03'),
(27, 18, '2026-06-12', 5650.00, 1, 1, '2026-06-13 13:09:03'),
(28, 17, '2026-06-12', 450.00, 1, 1, '2026-06-13 13:13:51'),
(29, 6, '2026-06-12', 21511.00, 1, 1, '2026-06-13 13:13:51'),
(30, 1, '2026-06-12', 44387.50, 1, 1, '2026-06-13 13:13:51'),
(31, 13, '2026-06-12', 612.50, 1, 1, '2026-06-13 13:13:51'),
(32, 4, '2026-06-12', 12500.00, 1, 1, '2026-06-13 13:13:51'),
(33, 2, '2026-06-12', 7260.00, 1, 1, '2026-06-13 13:13:51'),
(34, 11, '2026-06-12', 3500.00, 1, 1, '2026-06-13 13:13:51'),
(35, 9, '2026-06-12', 7862.50, 1, 1, '2026-06-13 13:13:51'),
(36, 3, '2026-06-12', 12550.00, 1, 1, '2026-06-13 13:13:51');

-- --------------------------------------------------------

--
-- Table structure for table `wax_invoice_items`
--

CREATE TABLE `wax_invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `wax_worker_id` int(11) DEFAULT NULL,
  `wax_qty` decimal(10,2) DEFAULT NULL,
  `wax_rate` decimal(10,2) DEFAULT NULL,
  `des_qty` int(11) DEFAULT NULL,
  `des_rate` decimal(10,2) DEFAULT NULL,
  `qty` decimal(10,2) NOT NULL DEFAULT 1.00,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `worker_percentage` decimal(5,2) DEFAULT 0.00,
  `partner_percentage` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `design_qty` decimal(10,2) DEFAULT NULL,
  `design_rate` decimal(10,2) DEFAULT NULL,
  `wax_commission` decimal(12,2) NOT NULL DEFAULT 0.00,
  `wax_item_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_invoice_items`
--

INSERT INTO `wax_invoice_items` (`id`, `invoice_id`, `item_id`, `worker_id`, `wax_worker_id`, `wax_qty`, `wax_rate`, `des_qty`, `des_rate`, `qty`, `rate`, `amount`, `worker_percentage`, `partner_percentage`, `commission_amount`, `design_qty`, `design_rate`, `wax_commission`, `wax_item_id`) VALUES
(69, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 7.92, 1250.00, 9900.00, 80.00, 20.00, 7920.00, 7.92, 1250.00, 0.00, NULL),
(70, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 10.00, 1250.00, 12500.00, 80.00, 20.00, 10000.00, 10.00, 1250.00, 0.00, NULL),
(71, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 17.73, 1250.00, 22162.50, 80.00, 20.00, 17730.00, 17.73, 1250.00, 0.00, NULL),
(72, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 5.86, 1250.00, 7325.00, 80.00, 20.00, 5860.00, 5.86, 1250.00, 0.00, NULL),
(73, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.99, 1250.00, 2487.50, 80.00, 20.00, 1990.00, 1.99, 1250.00, 0.00, NULL),
(74, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 3.26, 1250.00, 4075.00, 80.00, 20.00, 3260.00, 3.26, 1250.00, 0.00, NULL),
(75, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 12.79, 1250.00, 15987.50, 80.00, 20.00, 12790.00, 12.79, 1250.00, 0.00, NULL),
(76, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 5.91, 1250.00, 7387.50, 80.00, 20.00, 5910.00, 5.91, 1250.00, 0.00, NULL),
(77, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 3.80, 1250.00, 4750.00, 80.00, 20.00, 3800.00, 3.80, 1250.00, 0.00, NULL),
(78, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.24, 1250.00, 300.00, 80.00, 20.00, 240.00, 0.24, 1250.00, 0.00, NULL),
(79, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 15.03, 1250.00, 18787.50, 80.00, 20.00, 15030.00, 15.03, 1250.00, 0.00, NULL),
(80, 11, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 13.39, 1250.00, 16737.50, 80.00, 20.00, 13390.00, 13.39, 1250.00, 0.00, NULL),
(81, 12, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.31, 1250.00, 387.50, 80.00, 20.00, 310.00, 0.31, 1250.00, 0.00, NULL),
(82, 12, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.07, 1250.00, 1337.50, 80.00, 20.00, 1070.00, 1.07, 1250.00, 0.00, NULL),
(83, 13, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.63, 1250.00, 787.50, 80.00, 20.00, 630.00, 0.63, 1250.00, 0.00, NULL),
(84, 13, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.09, 1250.00, 1362.50, 80.00, 20.00, 1090.00, 1.09, 1250.00, 0.00, NULL),
(85, 13, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.58, 1250.00, 1975.00, 80.00, 20.00, 1580.00, 1.58, 1250.00, 0.00, NULL),
(86, 13, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.65, 1250.00, 2062.50, 80.00, 20.00, 1650.00, 1.65, 1250.00, 0.00, NULL),
(87, 14, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.70, 1250.00, 2125.00, 80.00, 20.00, 1700.00, 1.70, 1250.00, 0.00, NULL),
(88, 15, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 3.99, 1250.00, 4987.50, 80.00, 20.00, 3990.00, 3.99, 1250.00, 0.00, NULL),
(89, 16, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 3.94, 1250.00, 4925.00, 80.00, 20.00, 3940.00, 3.94, 1250.00, 0.00, NULL),
(90, 17, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 3.14, 1200.00, 3768.00, 80.00, 20.00, 3014.40, 3.14, 1200.00, 0.00, NULL),
(91, 17, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 4.24, 1200.00, 5088.00, 80.00, 20.00, 4070.40, 4.24, 1200.00, 0.00, NULL),
(92, 17, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.65, 1200.00, 780.00, 80.00, 20.00, 624.00, 0.65, 1200.00, 0.00, NULL),
(93, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 2.54, 700.00, 1778.00, 80.00, 20.00, 1422.40, 2.54, 700.00, 0.00, NULL),
(94, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 6.44, 700.00, 4508.00, 80.00, 20.00, 3606.40, 6.44, 700.00, 0.00, NULL),
(95, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.42, 700.00, 994.00, 80.00, 20.00, 795.20, 1.42, 700.00, 0.00, NULL),
(96, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.34, 700.00, 938.00, 80.00, 20.00, 750.40, 1.34, 700.00, 0.00, NULL),
(97, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.32, 700.00, 224.00, 80.00, 20.00, 179.20, 0.32, 700.00, 0.00, NULL),
(98, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.81, 700.00, 567.00, 80.00, 20.00, 453.60, 0.81, 700.00, 0.00, NULL),
(99, 18, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.92, 700.00, 1344.00, 80.00, 20.00, 1075.20, 1.92, 700.00, 0.00, NULL),
(100, 19, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.60, 1250.00, 750.00, 80.00, 20.00, 600.00, 0.60, 1250.00, 0.00, NULL),
(101, 19, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 0.94, 1250.00, 1175.00, 80.00, 20.00, 940.00, 0.94, 1250.00, 0.00, NULL),
(102, 20, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 4.01, 1250.00, 5012.50, 80.00, 20.00, 4010.00, 4.01, 1250.00, 0.00, NULL),
(104, 4, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 0.66, 1250.00, 825.00, 100.00, 0.00, 825.00, 0.66, 1250.00, 0.00, NULL),
(105, 5, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 2.62, 1250.00, 3275.00, 100.00, 0.00, 3275.00, 2.62, 1250.00, 0.00, NULL),
(106, 6, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 1.50, 1250.00, 1875.00, 100.00, 0.00, 1875.00, 1.50, 1250.00, 0.00, NULL),
(107, 6, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 3.58, 1250.00, 4475.00, 100.00, 0.00, 4475.00, 3.58, 1250.00, 0.00, NULL),
(108, 7, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 0.60, 700.00, 420.00, 100.00, 0.00, 420.00, 0.60, 700.00, 0.00, NULL),
(109, 7, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 0.32, 700.00, 224.00, 100.00, 0.00, 224.00, 0.32, 700.00, 0.00, NULL),
(112, 9, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 0.36, 700.00, 252.00, 100.00, 0.00, 252.00, 0.36, 700.00, 0.00, NULL),
(113, 10, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 1.54, 1250.00, 1925.00, 100.00, 0.00, 1925.00, 1.54, 1250.00, 0.00, NULL),
(114, 8, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 1.34, 1250.00, 1675.00, 100.00, 0.00, 1675.00, 1.34, 1250.00, 0.00, NULL),
(115, 8, 1, 3, NULL, 0.00, 0.00, NULL, NULL, 0.06, 1250.00, 75.00, 100.00, 0.00, 75.00, 0.06, 1250.00, 0.00, NULL),
(116, 22, 1, 3, NULL, NULL, NULL, NULL, NULL, 2.28, 1162.28, 2650.00, 100.00, 0.00, 2650.00, NULL, NULL, 0.00, NULL),
(117, 23, 1, 3, NULL, NULL, NULL, NULL, NULL, 2.36, 1250.00, 2950.00, 100.00, 0.00, 2950.00, NULL, NULL, 0.00, NULL),
(118, 24, 1, 1, NULL, NULL, NULL, NULL, NULL, 5.48, 1250.00, 6850.00, 0.00, 44.00, 3836.00, NULL, NULL, 0.00, NULL),
(119, 25, 1, 1, NULL, NULL, NULL, NULL, NULL, 0.54, 1250.00, 675.00, 0.00, 44.00, 378.00, NULL, NULL, 0.00, NULL),
(120, 25, 1, 1, NULL, NULL, NULL, NULL, NULL, 0.38, 1250.00, 475.00, 0.00, 44.00, 266.00, NULL, NULL, 0.00, NULL),
(121, 26, 1, 1, NULL, NULL, NULL, NULL, NULL, 4.65, 1250.00, 5812.50, 0.00, 44.00, 3255.00, NULL, NULL, 0.00, NULL),
(124, 28, 1, 1, NULL, NULL, NULL, NULL, NULL, 0.36, 1250.00, 450.00, 0.00, 44.00, 252.00, NULL, NULL, 0.00, NULL),
(125, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 3.86, 700.00, 2702.00, 0.00, 0.00, 2702.00, NULL, NULL, 0.00, NULL),
(126, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.09, 700.00, 763.00, 0.00, 0.00, 763.00, NULL, NULL, 0.00, NULL),
(127, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.25, 700.00, 875.00, 0.00, 0.00, 875.00, NULL, NULL, 0.00, NULL),
(128, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 3.46, 700.00, 2422.00, 0.00, 0.00, 2422.00, NULL, NULL, 0.00, NULL),
(129, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 7.95, 700.00, 5565.00, 0.00, 0.00, 5565.00, NULL, NULL, 0.00, NULL),
(130, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.21, 700.00, 847.00, 0.00, 0.00, 847.00, NULL, NULL, 0.00, NULL),
(131, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 5.00, 700.00, 3500.00, 0.00, 0.00, 3500.00, NULL, NULL, 0.00, NULL),
(132, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 4.97, 700.00, 3479.00, 0.00, 0.00, 3479.00, NULL, NULL, 0.00, NULL),
(133, 29, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.94, 700.00, 1358.00, 0.00, 0.00, 1358.00, NULL, NULL, 0.00, NULL),
(134, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 14.99, 1250.00, 18737.50, 0.00, 44.00, 10493.00, NULL, NULL, 0.00, NULL),
(135, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.46, 1250.00, 1825.00, 0.00, 44.00, 1022.00, NULL, NULL, 0.00, NULL),
(136, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.85, 1250.00, 2312.50, 0.00, 44.00, 1295.00, NULL, NULL, 0.00, NULL),
(137, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 1.69, 1250.00, 2112.50, 0.00, 44.00, 1183.00, NULL, NULL, 0.00, NULL),
(138, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 6.69, 1250.00, 8362.50, 0.00, 44.00, 4683.00, NULL, NULL, 0.00, NULL),
(139, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 6.40, 1250.00, 8000.00, 0.00, 44.00, 4480.00, NULL, NULL, 0.00, NULL),
(140, 30, 1, 1, NULL, NULL, NULL, NULL, NULL, 2.43, 1250.00, 3037.50, 0.00, 44.00, 1701.00, NULL, NULL, 0.00, NULL),
(141, 31, 1, 1, NULL, NULL, NULL, NULL, NULL, 0.49, 1250.00, 612.50, 0.00, 44.00, 343.00, NULL, NULL, 0.00, NULL),
(142, 32, 1, 1, NULL, NULL, NULL, NULL, NULL, 10.00, 1250.00, 12500.00, 0.00, 44.00, 7000.00, NULL, NULL, 0.00, NULL),
(143, 33, 1, 1, NULL, NULL, NULL, NULL, NULL, 3.32, 1200.00, 3984.00, 0.00, 41.67, 2324.00, NULL, NULL, 0.00, NULL),
(144, 33, 1, 1, NULL, NULL, NULL, NULL, NULL, 2.73, 1200.00, 3276.00, 0.00, 41.67, 1911.00, NULL, NULL, 0.00, NULL),
(145, 34, 1, 1, NULL, NULL, NULL, NULL, NULL, 2.80, 1250.00, 3500.00, 0.00, 44.00, 1960.00, NULL, NULL, 0.00, NULL),
(146, 35, 1, 1, NULL, NULL, NULL, NULL, NULL, 6.29, 1250.00, 7862.50, 0.00, 44.00, 4403.00, NULL, NULL, 0.00, NULL),
(147, 36, 1, 1, NULL, NULL, NULL, NULL, NULL, 10.04, 1250.00, 12550.00, 0.00, 44.00, 7028.00, NULL, NULL, 0.00, NULL),
(148, 27, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 1.89, 1250.00, 2362.50, 80.00, 20.00, 1890.00, 1.89, 1250.00, 0.00, NULL),
(149, 27, 1, 1, NULL, 0.00, 0.00, NULL, NULL, 2.63, 1250.00, 3287.50, 80.00, 20.00, 2630.00, 2.63, 1250.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `wax_items`
--

CREATE TABLE `wax_items` (
  `id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `name_urdu` varchar(255) NOT NULL,
  `category` enum('wax','design','mix') NOT NULL,
  `default_rate` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_items`
--

INSERT INTO `wax_items` (`id`, `name`, `name_urdu`, `category`, `default_rate`, `active`) VALUES
(1, 'Wax', 'ویکس', 'wax', 1250.00, 1),
(2, 'Full Wax', 'فل ویکس', 'wax', 1000.00, 0),
(3, 'Set', 'سیٹ', 'design', NULL, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wax_partners`
--

CREATE TABLE `wax_partners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `share_percentage` decimal(5,2) NOT NULL,
  `active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_partners`
--

INSERT INTO `wax_partners` (`id`, `name`, `share_percentage`, `active`) VALUES
(2, 'Mohsin', 100.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `wax_payments`
--

CREATE TABLE `wax_payments` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` enum('cash','bank','check') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_payments`
--

INSERT INTO `wax_payments` (`id`, `customer_id`, `amount`, `payment_date`, `method`, `notes`, `created_at`) VALUES
(2, 10, 37000.00, '2026-06-08', 'cash', '', '2026-06-08 09:29:13'),
(3, 13, 60000.00, '2026-06-05', 'cash', '', '2026-06-08 11:15:07'),
(4, 1, 200000.00, '2026-06-05', 'cash', '', '2026-06-08 11:15:27'),
(5, 2, 15000.00, '2026-06-18', 'cash', '', '2026-06-18 15:34:08');

-- --------------------------------------------------------

--
-- Table structure for table `wax_price_list`
--

CREATE TABLE `wax_price_list` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_price_list`
--

INSERT INTO `wax_price_list` (`id`, `customer_id`, `item_id`, `rate`) VALUES
(1, 2, 1, 1200.00),
(2, 6, 1, 700.00);

-- --------------------------------------------------------

--
-- Table structure for table `wax_workers`
--

CREATE TABLE `wax_workers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `default_percentage` decimal(5,2) DEFAULT 80.00,
  `active` tinyint(1) DEFAULT 1,
  `commission_type` enum('percentage','fixed_per_unit') NOT NULL DEFAULT 'percentage',
  `commission_rate` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_workers`
--

INSERT INTO `wax_workers` (`id`, `name`, `default_percentage`, `active`, `commission_type`, `commission_rate`) VALUES
(1, 'Fahad', 80.00, 1, 'fixed_per_unit', 700.00),
(2, 'Abdul Rehman', 80.00, 1, 'percentage', 0.00),
(3, 'Faisal', 100.00, 1, 'percentage', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `wax_worker_payments`
--

CREATE TABLE `wax_worker_payments` (
  `id` int(11) NOT NULL,
  `worker_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customers_name` (`name`),
  ADD KEY `idx_customers_phone` (`phone`),
  ADD KEY `idx_customers_status` (`status`),
  ADD KEY `idx_customers_party_type` (`party_type`);

--
-- Indexes for table `expense_categories`
--
ALTER TABLE `expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `gold_gives`
--
ALTER TABLE `gold_gives`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `give_no` (`give_no`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_gold_gives_no` (`give_no`),
  ADD KEY `idx_gold_gives_date` (`give_date`),
  ADD KEY `idx_gold_gives_customer` (`customer_id`),
  ADD KEY `idx_gold_gives_type` (`give_type`),
  ADD KEY `idx_gold_gives_deleted` (`deleted_at`);

--
-- Indexes for table `gold_give_items`
--
ALTER TABLE `gold_give_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gold_give_items_give` (`give_id`);

--
-- Indexes for table `gold_receipts`
--
ALTER TABLE `gold_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_receipts_no` (`receipt_no`),
  ADD KEY `idx_receipts_date` (`receipt_date`);

--
-- Indexes for table `gold_receipt_items`
--
ALTER TABLE `gold_receipt_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_invoices_no` (`invoice_no`),
  ADD KEY `idx_invoices_date` (`invoice_date`),
  ADD KEY `idx_invoices_status` (`status`),
  ADD KEY `idx_invoices_type` (`invoice_type`),
  ADD KEY `idx_invoices_book_no` (`manual_book_no`);

--
-- Indexes for table `invoice_multiple`
--
ALTER TABLE `invoice_multiple`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_no` (`invoice_no`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `updated_by` (`updated_by`),
  ADD KEY `idx_invoice_multiple_no` (`invoice_no`),
  ADD KEY `idx_invoice_multiple_date` (`invoice_date`),
  ADD KEY `idx_invoice_multiple_status` (`status`),
  ADD KEY `idx_invoice_multiple_type` (`invoice_type`),
  ADD KEY `idx_invoice_multiple_customer` (`customer_id`),
  ADD KEY `idx_invoice_multiple_book_no` (`manual_book_no`);

--
-- Indexes for table `invoice_multiple_items`
--
ALTER TABLE `invoice_multiple_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_multiple_items_invoice` (`invoice_multiple_id`);

--
-- Indexes for table `invoice_multiple_receives`
--
ALTER TABLE `invoice_multiple_receives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_multiple_receives_invoice` (`invoice_multiple_id`);

--
-- Indexes for table `invoice_receives`
--
ALTER TABLE `invoice_receives`
  ADD PRIMARY KEY (`id`),
  ADD KEY `invoice_id` (`invoice_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `wax_customers`
--
ALTER TABLE `wax_customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wax_customer_name` (`name`);

--
-- Indexes for table `wax_expenses`
--
ALTER TABLE `wax_expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wax_expense_date` (`expense_date`);

--
-- Indexes for table `wax_expense_categories`
--
ALTER TABLE `wax_expense_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `wax_invoices`
--
ALTER TABLE `wax_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wax_invoice_date` (`invoice_date`),
  ADD KEY `idx_wax_invoice_customer` (`customer_id`);

--
-- Indexes for table `wax_invoice_items`
--
ALTER TABLE `wax_invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wax_inv_item_invoice` (`invoice_id`),
  ADD KEY `idx_wax_inv_item_worker` (`worker_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `wax_items`
--
ALTER TABLE `wax_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wax_item_category` (`category`);

--
-- Indexes for table `wax_partners`
--
ALTER TABLE `wax_partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wax_payments`
--
ALTER TABLE `wax_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wax_payment_date` (`payment_date`),
  ADD KEY `idx_wax_payment_customer` (`customer_id`);

--
-- Indexes for table `wax_price_list`
--
ALTER TABLE `wax_price_list`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_wax_customer_item` (`customer_id`,`item_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `wax_workers`
--
ALTER TABLE `wax_workers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `wax_worker_payments`
--
ALTER TABLE `wax_worker_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `worker_id` (`worker_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gold_gives`
--
ALTER TABLE `gold_gives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gold_give_items`
--
ALTER TABLE `gold_give_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `gold_receipts`
--
ALTER TABLE `gold_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `gold_receipt_items`
--
ALTER TABLE `gold_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `invoice_multiple`
--
ALTER TABLE `invoice_multiple`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `invoice_multiple_items`
--
ALTER TABLE `invoice_multiple_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `invoice_multiple_receives`
--
ALTER TABLE `invoice_multiple_receives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `invoice_receives`
--
ALTER TABLE `invoice_receives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wax_customers`
--
ALTER TABLE `wax_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `wax_expenses`
--
ALTER TABLE `wax_expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wax_expense_categories`
--
ALTER TABLE `wax_expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `wax_invoices`
--
ALTER TABLE `wax_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `wax_invoice_items`
--
ALTER TABLE `wax_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=150;

--
-- AUTO_INCREMENT for table `wax_items`
--
ALTER TABLE `wax_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wax_partners`
--
ALTER TABLE `wax_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wax_payments`
--
ALTER TABLE `wax_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `wax_price_list`
--
ALTER TABLE `wax_price_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wax_workers`
--
ALTER TABLE `wax_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `wax_worker_payments`
--
ALTER TABLE `wax_worker_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `gold_gives`
--
ALTER TABLE `gold_gives`
  ADD CONSTRAINT `gold_gives_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gold_gives_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gold_gives_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gold_give_items`
--
ALTER TABLE `gold_give_items`
  ADD CONSTRAINT `gold_give_items_ibfk_1` FOREIGN KEY (`give_id`) REFERENCES `gold_gives` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `gold_receipts`
--
ALTER TABLE `gold_receipts`
  ADD CONSTRAINT `gold_receipts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `gold_receipts_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gold_receipts_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `gold_receipt_items`
--
ALTER TABLE `gold_receipt_items`
  ADD CONSTRAINT `gold_receipt_items_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `gold_receipts` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoices_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_multiple`
--
ALTER TABLE `invoice_multiple`
  ADD CONSTRAINT `invoice_multiple_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_multiple_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `invoice_multiple_ibfk_3` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoice_multiple_items`
--
ALTER TABLE `invoice_multiple_items`
  ADD CONSTRAINT `invoice_multiple_items_ibfk_1` FOREIGN KEY (`invoice_multiple_id`) REFERENCES `invoice_multiple` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_multiple_receives`
--
ALTER TABLE `invoice_multiple_receives`
  ADD CONSTRAINT `invoice_multiple_receives_ibfk_1` FOREIGN KEY (`invoice_multiple_id`) REFERENCES `invoice_multiple` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `invoice_receives`
--
ALTER TABLE `invoice_receives`
  ADD CONSTRAINT `invoice_receives_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wax_invoices`
--
ALTER TABLE `wax_invoices`
  ADD CONSTRAINT `wax_invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `wax_customers` (`id`);

--
-- Constraints for table `wax_invoice_items`
--
ALTER TABLE `wax_invoice_items`
  ADD CONSTRAINT `wax_invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `wax_invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wax_invoice_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `wax_items` (`id`),
  ADD CONSTRAINT `wax_invoice_items_ibfk_3` FOREIGN KEY (`worker_id`) REFERENCES `wax_workers` (`id`);

--
-- Constraints for table `wax_payments`
--
ALTER TABLE `wax_payments`
  ADD CONSTRAINT `wax_payments_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `wax_customers` (`id`);

--
-- Constraints for table `wax_price_list`
--
ALTER TABLE `wax_price_list`
  ADD CONSTRAINT `wax_price_list_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `wax_customers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `wax_price_list_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `wax_items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `wax_worker_payments`
--
ALTER TABLE `wax_worker_payments`
  ADD CONSTRAINT `wax_worker_payments_ibfk_1` FOREIGN KEY (`worker_id`) REFERENCES `wax_workers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
