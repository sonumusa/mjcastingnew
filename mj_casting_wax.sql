-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 05:00 PM
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
(1, 0.000, 0.000, 0.000, 0.000, 'Current Stock', NULL, '2026-06-02 11:35:08', '2026-06-02 11:35:08');

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
(3, 'address', 'Shop #3, Gold Market', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(4, 'phone', '0300-1234567', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(5, 'phone2', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(6, 'phone3', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(7, 'city', 'Karachi', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(8, 'messenger', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(9, 'social', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(10, 'shop_name', 'Gold Workshop', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(11, 'shop_address', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(12, 'shop_phone', '', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(13, 'currency', 'PKR', 'text', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(14, 'default_rp_rate', '0', 'number', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(15, 'default_gram_rate', '0', 'number', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(16, 'default_waste_rate', '0.125', 'number', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
(17, 'default_ratti_rate', '0', 'number', '2026-06-02 11:35:08', '2026-06-02 11:35:08'),
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
(1, 'Sunny', '', NULL, 0.00, 'cash', 1, '2026-06-02 11:39:50'),
(2, 'Waseem Bhai', '', NULL, 0.00, 'cash', 1, '2026-06-02 11:42:32');

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
(4, 2, '2026-06-02', 1200.00, 1, 1, '2026-06-02 13:51:30'),
(5, 1, '2026-06-03', 1250.00, 1, 1, '2026-06-02 13:52:19'),
(6, 2, '2026-06-03', 1300.00, 1, 1, '2026-06-02 13:52:19'),
(7, 2, '2026-06-02', 1200.00, 1, 1, '2026-06-02 14:20:55');

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
  `wax_qty` int(11) DEFAULT NULL,
  `wax_rate` decimal(10,2) DEFAULT NULL,
  `des_qty` int(11) DEFAULT NULL,
  `des_rate` decimal(10,2) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `worker_percentage` decimal(5,2) DEFAULT 0.00,
  `partner_percentage` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `design_qty` int(11) DEFAULT NULL,
  `design_rate` decimal(10,2) DEFAULT NULL,
  `wax_commission` decimal(12,2) NOT NULL DEFAULT 0.00,
  `wax_item_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `wax_invoice_items`
--

INSERT INTO `wax_invoice_items` (`id`, `invoice_id`, `item_id`, `worker_id`, `wax_worker_id`, `wax_qty`, `wax_rate`, `des_qty`, `des_rate`, `qty`, `rate`, `amount`, `worker_percentage`, `partner_percentage`, `commission_amount`, `design_qty`, `design_rate`, `wax_commission`, `wax_item_id`) VALUES
(4, 6, 1, 1, NULL, 0, 0.00, NULL, NULL, 1, 1300.00, 1300.00, 80.00, 20.00, 1040.00, 1, 1300.00, 0.00, NULL),
(5, 5, 1, 1, NULL, 0, 0.00, NULL, NULL, 1, 1250.00, 1250.00, 80.00, 20.00, 1000.00, 1, 1250.00, 0.00, NULL),
(6, 4, 1, 1, NULL, 0, 0.00, NULL, NULL, 1, 1200.00, 1200.00, 80.00, 20.00, 960.00, 1, 1200.00, 0.00, NULL),
(7, 7, 1, 1, NULL, NULL, NULL, NULL, NULL, 1, 1200.00, 1200.00, 0.00, 41.67, 700.00, NULL, NULL, 0.00, NULL);

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
(1, 2, 1000.00, '2026-06-02', 'cash', '', '2026-06-02 11:51:49');

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
(1, 2, 1, 1200.00);

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
(2, 'Abdul Rehman', 80.00, 1, 'percentage', 0.00);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expense_categories`
--
ALTER TABLE `expense_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gold_receipts`
--
ALTER TABLE `gold_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gold_receipt_items`
--
ALTER TABLE `gold_receipt_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_receives`
--
ALTER TABLE `invoice_receives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wax_customers`
--
ALTER TABLE `wax_customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `wax_invoice_items`
--
ALTER TABLE `wax_invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wax_price_list`
--
ALTER TABLE `wax_price_list`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `wax_workers`
--
ALTER TABLE `wax_workers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `wax_worker_payments`
--
ALTER TABLE `wax_worker_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

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
