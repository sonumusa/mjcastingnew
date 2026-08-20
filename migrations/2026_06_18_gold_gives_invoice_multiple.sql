-- Migration: Gold Gives + Invoice Multiple
-- Run in phpMyAdmin / MySQL after existing db.sql is installed.

CREATE TABLE IF NOT EXISTS gold_gives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    give_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    give_type ENUM('customer','dukandar','karigar') DEFAULT 'customer',
    give_date DATE NOT NULL,
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
    INDEX idx_gold_gives_no (give_no),
    INDEX idx_gold_gives_date (give_date),
    INDEX idx_gold_gives_customer (customer_id),
    INDEX idx_gold_gives_type (give_type),
    INDEX idx_gold_gives_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS gold_give_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    give_id INT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    gross_weight DECIMAL(10,3) DEFAULT 0.000,
    ratti_impurity DECIMAL(10,3) DEFAULT 0.000,
    khalis_weight DECIMAL(10,3) DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (give_id) REFERENCES gold_gives(id) ON DELETE CASCADE,
    INDEX idx_gold_give_items_give (give_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_multiple (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    customer_id INT NOT NULL,
    invoice_type ENUM('customer','dukandar','karigar') DEFAULT 'customer',
    invoice_date DATE NOT NULL,
    manual_book_no VARCHAR(50) DEFAULT NULL,
    total_casting_weight DECIMAL(10,3) DEFAULT 0.000,
    total_waste_weight DECIMAL(10,3) DEFAULT 0.000,
    total_weight DECIMAL(10,3) DEFAULT 0.000,
    total_male_waste DECIMAL(10,3) DEFAULT 0.000,
    total_gold_khalis DECIMAL(10,3) DEFAULT 0.000,
    total_received_khalis DECIMAL(10,3) DEFAULT 0.000,
    total_rp_mazdori_weight DECIMAL(10,3) DEFAULT 0.000,
    total_rp_mazdori_amount DECIMAL(15,2) DEFAULT 0.00,
    total_casting_mazdori_weight DECIMAL(10,3) DEFAULT 0.000,
    total_casting_mazdori_amount DECIMAL(15,2) DEFAULT 0.00,
    effective_gold DECIMAL(10,3) DEFAULT 0.000,
    grand_total DECIMAL(15,3) DEFAULT 0.000,
    wasooli DECIMAL(15,3) DEFAULT 0.000,
    previous_balance DECIMAL(15,3) DEFAULT 0.000,
    remaining_balance DECIMAL(15,3) DEFAULT 0.000,
    remarks TEXT DEFAULT NULL,
    status ENUM('active','cancelled') DEFAULT 'active',
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_invoice_multiple_no (invoice_no),
    INDEX idx_invoice_multiple_date (invoice_date),
    INDEX idx_invoice_multiple_status (status),
    INDEX idx_invoice_multiple_type (invoice_type),
    INDEX idx_invoice_multiple_customer (customer_id),
    INDEX idx_invoice_multiple_book_no (manual_book_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_multiple_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_multiple_id INT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    casting_weight DECIMAL(10,3) DEFAULT 0.000,
    ratti DECIMAL(10,3) DEFAULT 0.000,
    ratti_rate DECIMAL(10,3) DEFAULT 0.000,
    waste_weight DECIMAL(10,3) DEFAULT 0.000,
    total_weight DECIMAL(10,3) DEFAULT 0.000,
    male_waste DECIMAL(10,3) DEFAULT 0.000,
    gold_khalis DECIMAL(10,3) DEFAULT 0.000,
    rp_mazdori_weight DECIMAL(10,3) DEFAULT 0.000,
    rp_mazdori_amount DECIMAL(15,2) DEFAULT 0.00,
    casting_mazdori_weight DECIMAL(10,3) DEFAULT 0.000,
    casting_mazdori_amount DECIMAL(15,2) DEFAULT 0.00,
    effective_gold DECIMAL(10,3) DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_multiple_id) REFERENCES invoice_multiple(id) ON DELETE CASCADE,
    INDEX idx_invoice_multiple_items_invoice (invoice_multiple_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS invoice_multiple_receives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_multiple_id INT NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    gross_weight DECIMAL(10,3) DEFAULT 0.000,
    ratti_impurity DECIMAL(10,3) DEFAULT 0.000,
    khalis_weight DECIMAL(10,3) DEFAULT 0.000,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_multiple_id) REFERENCES invoice_multiple(id) ON DELETE CASCADE,
    INDEX idx_invoice_multiple_receives_invoice (invoice_multiple_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
