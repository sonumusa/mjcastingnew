-- ============================================================
-- MJ Casting - Gold Workshop Management System
-- Database Schema (MySQL / phpMyAdmin)
-- ============================================================

CREATE DATABASE IF NOT EXISTS mj_casting DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mj_casting_wax;

-- -----------------------------------------------------------
-- 1. Users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at DATETIME DEFAULT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 2. Customers (Parties)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    cnic VARCHAR(30) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    city VARCHAR(50) DEFAULT NULL,
    opening_balance DECIMAL(15,2) DEFAULT 0.00,
    status ENUM('active','inactive') DEFAULT 'active',
    party_type ENUM('customer','dukandar','karigar') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    INDEX idx_customers_name (name),
    INDEX idx_customers_phone (phone),
    INDEX idx_customers_status (status),
    INDEX idx_customers_party_type (party_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 3. Invoices
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    invoice_type ENUM('customer','dukandar','karigar') DEFAULT 'customer',
    invoice_date DATE NOT NULL,
    
    -- Weight fields
    casting_weight DECIMAL(10,3) DEFAULT 0.000,
    waste_weight DECIMAL(10,3) DEFAULT 0.000,
    total_weight DECIMAL(10,3) DEFAULT 0.000,
    ratti DECIMAL(10,3) DEFAULT 0.000,
    ratti_rate DECIMAL(10,3) DEFAULT 0.000,
    male_waste DECIMAL(10,3) DEFAULT 0.000,
    gold_khalis DECIMAL(10,3) DEFAULT 0.000,
    total_received_khalis DECIMAL(10,3) DEFAULT 0.000,
    
    -- RP (Redemption Price) fields
    rp_rate DECIMAL(15,2) DEFAULT 0.00,
    rp_amount DECIMAL(15,2) DEFAULT 0.00,
    rp_mazdori_weight DECIMAL(10,3) DEFAULT 0.000,
    rp_mazdori_rate DECIMAL(15,2) DEFAULT 0.00,
    rp_mazdori_amount DECIMAL(15,2) DEFAULT 0.00,
    
    -- Casting Mazdori fields
    casting_mazdori_weight DECIMAL(10,3) DEFAULT 0.000,
    casting_mazdori_rate DECIMAL(15,2) DEFAULT 0.00,
    casting_mazdori_amount DECIMAL(15,2) DEFAULT 0.00,
    
    -- Calculated fields
    effective_gold DECIMAL(10,3) DEFAULT 0.000,
    grand_total DECIMAL(15,2) DEFAULT 0.00,
    
    -- Payment fields
    wasooli DECIMAL(15,2) DEFAULT 0.00,
    previous_balance DECIMAL(15,2) DEFAULT 0.00,
    remaining_balance DECIMAL(15,2) DEFAULT 0.00,
    
    -- Additional fields
    manual_book_no VARCHAR(50) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    status ENUM('active','cancelled') DEFAULT 'active',
    
    -- Auto flags
    ratti_auto TINYINT(1) DEFAULT 0,
    waste_auto TINYINT(1) DEFAULT 0,
    male_waste_auto TINYINT(1) DEFAULT 0,
    
    -- Audit
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_invoices_no (invoice_no),
    INDEX idx_invoices_date (invoice_date),
    INDEX idx_invoices_status (status),
    INDEX idx_invoices_type (invoice_type),
    INDEX idx_invoices_book_no (manual_book_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 4. Invoice Receives (Gold received within an invoice)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS invoice_receives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    gross_weight DECIMAL(10,3) DEFAULT 0.000,
    ratti_impurity DECIMAL(10,3) DEFAULT 0.000,
    khalis_weight DECIMAL(10,3) DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 5. Gold Receipts (Standalone gold received)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS gold_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    receipt_type ENUM('customer','dukandar','karigar') DEFAULT 'customer',
    receipt_date DATE NOT NULL,
    total_gross_weight DECIMAL(10,3) DEFAULT 0.000,
    total_khalis_weight DECIMAL(10,3) DEFAULT 0.000,
    remarks TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_receipts_no (receipt_no),
    INDEX idx_receipts_date (receipt_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 6. Gold Receipt Items
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS gold_receipt_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_id INT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    gross_weight DECIMAL(10,3) DEFAULT 0.000,
    ratti_impurity DECIMAL(10,3) DEFAULT 0.000,
    khalis_weight DECIMAL(10,3) DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (receipt_id) REFERENCES gold_receipts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 7. Inventory
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opening_balance DECIMAL(10,3) DEFAULT 0.000,
    received DECIMAL(10,3) DEFAULT 0.000,
    given_invoices DECIMAL(10,3) DEFAULT 0.000,
    closing_balance DECIMAL(10,3) DEFAULT 0.000,
    period_label VARCHAR(100) DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- 8. Settings
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_type ENUM('text','number','boolean','json') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Default Data
-- -----------------------------------------------------------

-- Default Admin User (password: admin123)
INSERT INTO users (name, email, password) VALUES 
('Admin', 'admin@goldworkshop.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('User', 'user@goldworkshop.test', '$2y$10$HfzIhGCCaxqya1G3I8R6f.Rx0YN0kPwz1kJRozJ1UNRmBKXny1K2');

-- Default Settings
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES
('workshop_name', 'M.J Casting', 'text'),
('workshop_name_urdu', 'ایم جے کاسٹنگ', 'text'),
('address', 'Shop #3, Gold Market', 'text'),
('phone', '0300-1234567', 'text'),
('phone2', '', 'text'),
('phone3', '', 'text'),
('city', 'Karachi', 'text'),
('messenger', '', 'text'),
('social', '', 'text'),
('shop_name', 'Gold Workshop', 'text'),
('shop_address', '', 'text'),
('shop_phone', '', 'text'),
('currency', 'PKR', 'text'),
('default_rp_rate', '0', 'number'),
('default_gram_rate', '0', 'number'),
('default_waste_rate', '0.125', 'number'),
('default_ratti_rate', '0', 'number'),
('ratti_tiers', '[{"max_weight":15,"ratti":0.1},{"max_weight":25,"ratti":0.2},{"max_weight":40,"ratti":0.3},{"max_weight":60,"ratti":0.4},{"max_weight":9999,"ratti":0.5}]', 'json');

-- Default Inventory Record
INSERT INTO inventory (opening_balance, received, given_invoices, closing_balance, period_label) VALUES
(0, 0, 0, 0, 'Current Stock');