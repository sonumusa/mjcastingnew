<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$type = $_GET['type'] ?? '';
$pdo = getDB();

if ($type == 'items') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=items.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name Urdu', 'Category', 'Default Rate', 'Active']);
    
    $stmt = $pdo->query("SELECT id, name_urdu, category, default_rate, active FROM wax_items");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

if ($type == 'customers') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=customers.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Phone', 'Address', 'Opening Balance']);
    
    $stmt = $pdo->query("SELECT id, name, phone, address, opening_balance FROM wax_customers");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

if ($type == 'expenses') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=expenses.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Date', 'Category', 'Amount', 'Notes']);
    
    $stmt = $pdo->query("SELECT id, expense_date, category, amount, notes FROM wax_expenses");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

echo "Invalid export type.";
