<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $custs = $_POST['customer_ids'] ?? [];
    $amounts = $_POST['amounts'] ?? [];
    $methods = $_POST['methods'] ?? [];
    $notes = $_POST['notes'] ?? [];

    if ($date) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO wax_payments (customer_id, payment_date, amount, method, notes) VALUES (?, ?, ?, ?, ?)");
        
        for ($i = 0; $i < count($custs); $i++) {
            $cust = $custs[$i];
            $amount = floatval($amounts[$i]);
            $method = safe_input($methods[$i]);
            $note = safe_input($notes[$i]);
            
            if (!empty($cust) && $amount > 0) {
                $stmt->execute([$cust, $date, $amount, $method, $note]);
            }
        }
    }
     redirect('wax/payments.php');
}
