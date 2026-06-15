<?php
// API endpoint: Get customer balance
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions/ledger_functions.php';
require_once __DIR__ . '/../functions/gold_calculations.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id = (int) query('id', 0);
$excludeInvoiceId = (int) query('exclude', 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Customer ID required']);
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, name, opening_balance FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    http_response_code(404);
    echo json_encode(['error' => 'Customer not found']);
    exit;
}

if ($excludeInvoiceId > 0) {
    $balance = getPreviousBalance($id, $excludeInvoiceId);
} else {
    $balance = getCustomerCurrentBalance($id);
}

echo json_encode([
    'balance' => $balance,
    'opening_balance' => (float)$customer['opening_balance'],
    'customer_name' => $customer['name'],
]);