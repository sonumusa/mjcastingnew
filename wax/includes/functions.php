<?php

function safe_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Only define if not already defined (main config.php has it)
if (!function_exists('redirect')) {
    function redirect($url) {
        header("Location: " . getBaseUrl() . $url);
        exit;
    }
}

function json_response($data, $status = 200) {
    header('Content-Type: application/json');
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function format_currency($amount) {
    return number_format($amount, 2);
}

function get_customers() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name, opening_balance FROM wax_customers WHERE active = 1 ORDER BY name ASC");
    return $stmt->fetchAll();
}

function get_items() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name, name_urdu, category, default_rate FROM wax_items WHERE active = 1 ORDER BY name_urdu ASC");
    return $stmt->fetchAll();
}

function get_workers() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, name, default_percentage FROM wax_workers WHERE active = 1 ORDER BY name ASC");
    return $stmt->fetchAll();
}

function get_customer_balances() {
    $pdo = getDB();
    $customers = $pdo->query("SELECT id, opening_balance FROM wax_customers")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $billed = $pdo->query("SELECT customer_id, SUM(total_amount) FROM wax_invoices GROUP BY customer_id")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $paid = $pdo->query("SELECT customer_id, SUM(amount) FROM wax_payments GROUP BY customer_id")->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $balances = [];
    foreach ($customers as $id => $ob) {
        $b = $billed[$id] ?? 0;
        $p = $paid[$id] ?? 0;
        $balances[$id] = $ob + $b - $p;
    }
    return $balances;
}

function ensure_wax_schema(): void {
    $pdo = getDB();

    $workerColumn = $pdo->query("SHOW COLUMNS FROM wax_workers LIKE 'commission_type'")->fetch();
    if (!$workerColumn) {
        $pdo->exec("ALTER TABLE wax_workers ADD COLUMN commission_type enum('percentage','fixed_per_unit') NOT NULL DEFAULT 'percentage', ADD COLUMN commission_rate decimal(10,2) NOT NULL DEFAULT 0.00");
    }

    $commissionColumn = $pdo->query("SHOW COLUMNS FROM wax_invoice_items LIKE 'commission_amount'")->fetch();
    if (!$commissionColumn) {
        $pdo->exec("ALTER TABLE wax_invoice_items ADD COLUMN commission_amount decimal(12,2) NOT NULL DEFAULT 0.00");
    }

    $designQtyColumn = $pdo->query("SHOW COLUMNS FROM wax_invoice_items LIKE 'design_qty'")->fetch();
    if (!$designQtyColumn) {
        $pdo->exec("ALTER TABLE wax_invoice_items ADD COLUMN design_qty int(11) DEFAULT NULL, ADD COLUMN design_rate decimal(10,2) DEFAULT NULL");
        $desQtyColumn = $pdo->query("SHOW COLUMNS FROM wax_invoice_items LIKE 'des_qty'")->fetch();
        if ($desQtyColumn) {
            $pdo->exec("UPDATE wax_invoice_items SET design_qty = des_qty, design_rate = des_rate WHERE design_qty IS NULL");
        }
    }

    $waxCommissionColumn = $pdo->query("SHOW COLUMNS FROM wax_invoice_items LIKE 'wax_commission'")->fetch();
    if (!$waxCommissionColumn) {
        $pdo->exec("ALTER TABLE wax_invoice_items ADD COLUMN wax_commission decimal(12,2) NOT NULL DEFAULT 0.00");
    }
    
    // Ensure wax_item_id exists (used by invoice save/print/report)
    $waxItemColumn = $pdo->query("SHOW COLUMNS FROM wax_invoice_items LIKE 'wax_item_id'")->fetch();
    if (!$waxItemColumn) {
        $pdo->exec("ALTER TABLE wax_invoice_items ADD COLUMN wax_item_id int(11) DEFAULT NULL");
    }
}

ensure_wax_schema();