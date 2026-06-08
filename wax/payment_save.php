<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'];
    $payment_date = $_POST['payment_date'];
    $amount = floatval($_POST['amount']);
    $method = safe_input($_POST['method']);
    $notes = safe_input($_POST['notes']);

    if (empty($customer_id) || $amount <= 0) {
        die("Invalid Data");
    }

    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO wax_payments (customer_id, payment_date, amount, method, notes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$customer_id, $payment_date, $amount, $method, $notes]);

   redirect('wax/payments.php');
}
