<?php
require_once __DIR__ . '/bootstrap.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('wax/invoice_create.php');
}

// 1. Inputs
$invoice_date = $_POST['invoice_date'];
$customer_id = $_POST['customer_id'];
$item_ids = $_POST['items'] ?? [];
$qtys = $_POST['qtys'] ?? [];
$rates = $_POST['rates'] ?? [];
$worker_ids = $_POST['workers'] ?? [];

// NEW: Get the is_dual array
$is_dual = $_POST['is_dual'] ?? [];

if (empty($invoice_date) || empty($customer_id) || empty($item_ids)) {
    die("Invalid Data: Missing required fields.");
}

$pdo = getDB();

try {
    $pdo->beginTransaction();

    // 2. Create Invoice
    $stmt = $pdo->prepare("INSERT INTO wax_invoices (customer_id, invoice_date, created_by) VALUES (?, ?, ?)");
    $stmt->execute([$customer_id, $invoice_date, $_SESSION['user_id']]);
    $invoice_id = $pdo->lastInsertId();

    $total_invoice_amount = 0;

    // 3. Process Items
    $stmtItem = $pdo->prepare("SELECT category FROM wax_items WHERE id = ?");
    $stmtWorker = $pdo->prepare("SELECT default_percentage, commission_type, commission_rate FROM wax_workers WHERE id = ?");
    
    $insertItem = $pdo->prepare("
        INSERT INTO wax_invoice_items 
        (invoice_id, item_id, worker_id, qty, rate, amount, worker_percentage, partner_percentage, commission_amount, wax_qty, wax_rate, design_qty, design_rate, wax_item_id, wax_worker_id, wax_commission) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    for ($i = 0; $i < count($item_ids); $i++) {
        $item_id = $item_ids[$i];
        
        if (empty($item_id)) continue;

        $qty = floatval($qtys[$i]);
        $rate = floatval($rates[$i]);
        $worker_id = (isset($worker_ids[$i]) && $worker_ids[$i] !== '') ? $worker_ids[$i] : null;
        
        // Check the is_dual flag
        $dual_enabled = isset($is_dual[$i]) && $is_dual[$i] == '1';
        
        // Initialize wax values
        $wax_qty = 0;
        $wax_rate = 0;
        $wax_item_id = null;
        $wax_worker_id = null;
        $design_qty = $qty;
        $design_rate = $rate;
        
        // Only process wax data if dual mode is enabled
        if ($dual_enabled) {
            $wax_qty = !empty($_POST['wax_qtys'][$i]) ? floatval($_POST['wax_qtys'][$i]) : 0;
            $wax_rate = !empty($_POST['wax_rates'][$i]) ? floatval($_POST['wax_rates'][$i]) : 0;
            $wax_item_id = !empty($_POST['wax_item_ids'][$i]) ? $_POST['wax_item_ids'][$i] : null;
            $wax_worker_id = !empty($_POST['wax_worker_ids'][$i]) ? $_POST['wax_worker_ids'][$i] : null;
        }

        // Fetch Item Category
        $stmtItem->execute([$item_id]);
        $itemCat = $stmtItem->fetchColumn();

        $amount = 0;

        if ($dual_enabled && $wax_item_id) {
            $amount = ($wax_qty * $wax_rate) + ($design_qty * $design_rate);
            if ($qty <= 0) $qty = 1;
        } else {
            if ($qty <= 0) continue;
            $amount = $qty * $rate;
            
            // Reset wax values if not dual mode
            $wax_qty = 0;
            $wax_rate = 0;
            $wax_item_id = null;
            $wax_worker_id = null;
        }

        $total_invoice_amount += $amount;

        $worker_percentage = 0.00;
        $partner_percentage = 100.00;
        $commission_amount = 0.00;

        // Validation
        if ($itemCat === 'design' && empty($worker_id)) {
            throw new Exception("Worker is mandatory for Design items (Row " . ($i + 1) . ").");
        }

        // 1. Calculate Design Worker Commission
        if ($worker_id) {
            $stmtWorker->execute([$worker_id]);
            $wData = $stmtWorker->fetch();
            
            if ($wData) {
                if ($wData['commission_type'] == 'percentage' || $wData['default_percentage'] > 0) {
                    $worker_percentage = $wData['default_percentage'];
                    $baseAmount = ($dual_enabled && $wax_item_id) ? ($design_qty * $design_rate) : $amount;
                    $commission_amount = $baseAmount * ($worker_percentage / 100);
                } else {
                    $commission_amount = (($dual_enabled && $wax_item_id) ? $design_qty : $qty) * $wData['commission_rate'];
                }
                
                if ($amount > 0) {
                    $partner_percentage = (($amount - $commission_amount) / $amount) * 100;
                }
            }
        }

        // 2. Calculate Wax Worker Commission
        $wax_comm = 0;
        if ($dual_enabled && $wax_worker_id) {
            $stmtWorker->execute([$wax_worker_id]);
            $waxWData = $stmtWorker->fetch();
            
            if ($waxWData) {
                $wax_comm = $wax_qty * $waxWData['commission_rate'];
            }
        }
        
        // 3. Insert Row
        $insertItem->execute([
            $invoice_id,
            $item_id,
            $worker_id,
            $qty,
            $rate,
            $amount,
            $worker_percentage,
            $partner_percentage,
            $commission_amount,
            $wax_qty,
            $wax_rate,
            $design_qty,
            $design_rate,
            $wax_item_id,
            $wax_worker_id,
            $wax_comm
        ]);
    }

    // 4. Update Invoice Total
    $pdo->prepare("UPDATE wax_invoices SET total_amount = ? WHERE id = ?")
        ->execute([$total_invoice_amount, $invoice_id]);

    $pdo->commit();
    
    redirect('wax/invoices.php');

} catch (Exception $e) {
    $pdo->rollBack();
    die("Error Saving Invoice: " . $e->getMessage());
}