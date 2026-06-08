<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $worker_id = $_POST['worker_id'];
    $date = $_POST['date'];
    $amount = floatval($_POST['amount']);
    $notes = safe_input($_POST['notes']);

    if ($worker_id && $amount > 0) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO wax_worker_payments (worker_id, amount, payment_date, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$worker_id, $amount, $date, $notes]);
        
        // Return to statement
        //header("Location: statement_worker.php?worker_id=$worker_id&start_date=" . date('Y-m-01'));
              redirect('wax/statement_worker.php?worker_id=' . urlencode($worker_id) . '&start_date=' . date('Y-m-01'));
        exit;
    }
}
die("Invalid Data");
