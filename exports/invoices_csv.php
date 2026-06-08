<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db = getDB();

$fromDate = query('from_date', '');
$toDate = query('to_date', '');
$customerId = query('customer_id', '');

$sql = "SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.status = 'active'";
$params = [];

if ($fromDate) {
    $sql .= " AND i.invoice_date >= ?";
    $params[] = $fromDate;
}
if ($toDate) {
    $sql .= " AND i.invoice_date <= ?";
    $params[] = $toDate;
}
if ($customerId) {
    $sql .= " AND i.customer_id = ?";
    $params[] = (int)$customerId;
}
$sql .= " ORDER BY i.invoice_date DESC, i.id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="invoices_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Invoice No', 'Book No', 'Date', 'Type', 'Customer',
    'Casting (g)', 'Ratti', 'Ratti Rate', 'Waste (g)',
    'Total Weight (g)', 'Male Waste (g)', 'Gold Khalis (g)',
    'RP Rate', 'RP Amount', 'RP Mazdori Wt (g)', 'RP Mazdori Amt',
    'Casting Mazdori Wt (g)', 'Casting Mazdori Amt',
    'Effective Gold (g)', 'Grand Total (g)', 'Received Khalis (g)',
    'Wasooli (g)', 'Previous Balance (g)', 'Remaining Balance (g)',
    'Remarks', 'Status',
]);

foreach ($invoices as $inv) {
    fputcsv($output, [
        $inv['invoice_no'],
        $inv['manual_book_no'],
        $inv['invoice_date'],
        $inv['invoice_type'],
        $inv['customer_name'],
        number_format($inv['casting_weight'], 3, '.', ''),
        number_format($inv['ratti'], 2, '.', ''),
        number_format($inv['ratti_rate'], 3, '.', ''),
        number_format($inv['waste_weight'], 3, '.', ''),
        number_format($inv['total_weight'], 3, '.', ''),
        number_format($inv['male_waste'], 3, '.', ''),
        number_format($inv['gold_khalis'], 3, '.', ''),
        number_format($inv['rp_rate'], 2, '.', ''),
        number_format($inv['rp_amount'], 2, '.', ''),
        number_format($inv['rp_mazdori_weight'], 3, '.', ''),
        number_format($inv['rp_mazdori_amount'], 2, '.', ''),
        number_format($inv['casting_mazdori_weight'], 3, '.', ''),
        number_format($inv['casting_mazdori_amount'], 2, '.', ''),
        number_format($inv['effective_gold'], 3, '.', ''),
        number_format($inv['grand_total'], 3, '.', ''),
        number_format($inv['total_received_khalis'], 3, '.', ''),
        number_format($inv['wasooli'], 3, '.', ''),
        number_format($inv['previous_balance'], 3, '.', ''),
        number_format($inv['remaining_balance'], 3, '.', ''),
        $inv['remarks'],
        $inv['status'],
    ]);
}

fclose($output);