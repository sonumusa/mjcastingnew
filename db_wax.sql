-- ============================================================
-- WAX Module Tables (Wax & 3D Design)
-- ============================================================

-- Wax Customers
CREATE TABLE IF NOT EXISTS wax_customers (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `contact` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `opening_balance` decimal(10,2) DEFAULT 0.00,
  `billing_style` enum('weekly','monthly','cash') DEFAULT 'cash',
  `active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wax_customer_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Items
CREATE TABLE IF NOT EXISTS wax_items (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `name_urdu` varchar(255) NOT NULL,
  `category` enum('wax','design','mix') NOT NULL,
  `default_rate` decimal(10,2) DEFAULT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_wax_item_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Price List (Customer-specific rates)
CREATE TABLE IF NOT EXISTS wax_price_list (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `rate` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_wax_customer_item` (`customer_id`,`item_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `wax_customers`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `wax_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Workers
CREATE TABLE IF NOT EXISTS wax_workers (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `default_percentage` decimal(5,2) DEFAULT 80.00,
  `commission_type` enum('percentage','fixed_per_unit') NOT NULL DEFAULT 'percentage',
  `commission_rate` decimal(10,2) DEFAULT 0.00,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Partners
CREATE TABLE IF NOT EXISTS wax_partners (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `share_percentage` decimal(5,2) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Invoices
CREATE TABLE IF NOT EXISTS wax_invoices (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `invoice_date` date NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `editable_flag` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wax_invoice_date` (`invoice_date`),
  KEY `idx_wax_invoice_customer` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `wax_customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Invoice Items
CREATE TABLE IF NOT EXISTS wax_invoice_items (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `worker_id` int(11) DEFAULT NULL,
  `wax_worker_id` int(11) DEFAULT NULL,
  `wax_qty` int(11) DEFAULT NULL,
  `wax_rate` decimal(10,2) DEFAULT NULL,
  `design_qty` int(11) DEFAULT NULL,
  `design_rate` decimal(10,2) DEFAULT NULL,
  `qty` int(11) NOT NULL DEFAULT 1,
  `rate` decimal(10,2) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `worker_percentage` decimal(5,2) DEFAULT 0.00,
  `partner_percentage` decimal(5,2) DEFAULT 0.00,
  `commission_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `wax_item_id` int(11) DEFAULT NULL,
  `wax_commission` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_wax_inv_item_invoice` (`invoice_id`),
  KEY `idx_wax_inv_item_worker` (`worker_id`),
  FOREIGN KEY (`invoice_id`) REFERENCES `wax_invoices`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `wax_items`(`id`),
  FOREIGN KEY (`worker_id`) REFERENCES `wax_workers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Payments
CREATE TABLE IF NOT EXISTS wax_payments (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `method` enum('cash','bank','check') DEFAULT 'cash',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wax_payment_date` (`payment_date`),
  KEY `idx_wax_payment_customer` (`customer_id`),
  FOREIGN KEY (`customer_id`) REFERENCES `wax_customers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Worker Payments
CREATE TABLE IF NOT EXISTS wax_worker_payments (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `worker_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`worker_id`) REFERENCES `wax_workers`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Expenses
CREATE TABLE IF NOT EXISTS wax_expenses (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_date` date NOT NULL,
  `category` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_wax_expense_date` (`expense_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Wax Expense Categories
CREATE TABLE IF NOT EXISTS wax_expense_categories (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default Wax Data
INSERT INTO wax_workers (name, default_percentage) VALUES ('Faisal', 80.00), ('Abdullah', 80.00);
INSERT INTO wax_partners (name, share_percentage) VALUES ('Umar', 50.00), ('Ali', 50.00);
INSERT INTO wax_expense_categories (name) VALUES ('Wax (Purchase)'), ('Rent Shop');
INSERT INTO wax_items (name, name_urdu, category, default_rate) VALUES ('Wax', 'ویکس', 'wax', 1200.00), ('Full Wax', 'فل ویکس', 'wax', 1000.00), ('Set', 'سیٹ', 'design', NULL);