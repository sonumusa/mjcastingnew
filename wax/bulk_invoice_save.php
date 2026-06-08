<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('wax/bulk_invoice.php');
}

// Inputs
$date = $_POST['date'] ?? date('Y-m-d');
$customer_ids = $_POST['customer_ids'] ?? [];
$item_ids = $_POST['item_ids'] ?? [];
$worker_ids = $_POST['worker_ids'] ?? [];
$qtys = $_POST['qtys'] ?? [];
$rates = $_POST['rates'] ?? [];

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // Group rows by Customer (Since Date is same for all)
    $grouped = [];

    for ($i = 0; $i < count($item_ids); $i++) {
        $custId = $customer_ids[$i];
        $itemId = $item_ids[$i];
        $qty = floatval($qtys[$i]);
        $rate = floatval($rates[$i]);
        $workerId = (isset($worker_ids[$i]) && $worker_ids[$i] !== '') ? $worker_ids[$i] : null;

        if (empty($custId) || empty($itemId) || $qty <= 0) continue;

        // Key is just customer now, date is global
        $key = $custId; 
        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                'date' => $date,
                'customer_id' => $custId,
                'items' => []
            ];
        }

        $grouped[$key]['items'][] = [
            'item_id' => $itemId,
            'worker_id' => $workerId,
            'qty' => $qty,
            'rate' => $rate
        ];
    }

    // Prepare Statements
    $stmtInvoice = $pdo->prepare("INSERT INTO wax_invoices (customer_id, invoice_date, created_by) VALUES (?, ?, ?)");
    $stmtItem = $pdo->prepare("SELECT category FROM wax_items WHERE id = ?");
    $stmtWorker = $pdo->prepare("SELECT default_percentage, commission_type, commission_rate FROM wax_workers WHERE id = ?");
    $insertItem = $pdo->prepare("
        INSERT INTO wax_invoice_items 
        (invoice_id, item_id, worker_id, qty, rate, amount, worker_percentage, partner_percentage, commission_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $updateInvoice = $pdo->prepare("UPDATE wax_invoices SET total_amount = ? WHERE id = ?");

    // Process Groups -> Create Invoices
    foreach ($grouped as $group) {
        // Create Invoice Header
        $stmtInvoice->execute([$group['customer_id'], $group['date'], $_SESSION['user_id']]);
        $invoice_id = $pdo->lastInsertId();
        
        $total_amount = 0;

        // Create Invoice Items
        foreach ($group['items'] as $index => $row) {
            $amount = $row['qty'] * $row['rate'];
            $total_amount += $amount;

            // Determine Commission
            $stmtItem->execute([$row['item_id']]);
            $category = $stmtItem->fetchColumn();

            $worker_percentage = 0.00;
            $partner_percentage = 100.00;
            $commission_amount = 0.00;

            if ($category === 'design' && !$row['worker_id']) {
                throw new Exception("Worker is missing for a Design item in bulk entry (Row " . ($index + 1) . ").");
            }

            if ($row['worker_id']) {
                $stmtWorker->execute([$row['worker_id']]);
                $wData = $stmtWorker->fetch();
                
                if ($wData) {
                    if ($wData['commission_type'] == 'fixed_per_unit') {
                        $commission_amount = $row['qty'] * $wData['commission_rate'];
                        $worker_percentage = 0;
                    } else {
                        $worker_percentage = $wData['default_percentage'];
                        $commission_amount = $amount * ($worker_percentage / 100);
                    }

                    if ($amount > 0) {
                        $partner_percentage = (($amount - $commission_amount) / $amount) * 100;
                    } else {
                        $partner_percentage = 0;
                    }
                }
            }

            $insertItem->execute([
                $invoice_id,
                $row['item_id'],
                $row['worker_id'],
                $row['qty'],
                $row['rate'],
                $amount,
                $worker_percentage,
                $partner_percentage,
                $commission_amount
            ]);
        }

        // Update Total
        $updateInvoice->execute([$total_amount, $invoice_id]);
    }

    $pdo->commit();
    
    // Redirect to Invoices List (To be built next)
   redirect('wax/invoices.php'); 

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error in Bulk Generation: " . $e->getMessage());
}
