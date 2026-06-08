<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$pdo = getDB();

// ==========================================
// CONFIGURATION: Faisal Wax Rates
// ==========================================
$faisal_worker_name = 'Fahad';
$default_wax_rate = 700; // Other customers rate

// Special rate customers (100 Rs per gram)
$special_rate_customers = [
    'Mohsin Wax' => 700,
    'Waseem Bhai' => 650,
];

// Get Faisal's worker ID
$stmtFaisal = $pdo->prepare("SELECT id FROM wax_workers WHERE name LIKE ? AND active = 1 LIMIT 1");
$stmtFaisal->execute(['%' . $faisal_worker_name . '%']);
$faisal_id = $stmtFaisal->fetchColumn();

// Get WAX item ID (item_id = 1 based on your data, or find by name)
$stmtWaxItem = $pdo->prepare("SELECT id FROM wax_items WHERE LOWER(name) LIKE '%wax%' OR name_urdu LIKE '%ویکس%' LIMIT 1");
$stmtWaxItem->execute();
$wax_item_id = $stmtWaxItem->fetchColumn() ?: 1; // Default to 1 if not found

// Fetch all active workers
$workers = $pdo->query("SELECT id, name FROM wax_workers WHERE active = 1 ORDER BY name ASC")->fetchAll();

// Function to match special customer (flexible matching)
function match_special_customer($customer_name, $special_rate_customers) {
    $customer_lower = strtolower(trim($customer_name));
    foreach ($special_rate_customers as $special_customer => $rate) {
        $special_lower = strtolower(trim($special_customer));
        // Check both directions and partial match
        if (strpos($customer_lower, $special_lower) !== false || 
            strpos($special_lower, $customer_lower) !== false ||
            strpos($customer_lower, str_replace(' ', '', $special_lower)) !== false) {
            return $special_customer;
        }
    }
    return null;
}

// ==========================================
// Calculate Faisal's Wax Summary - CURRENT PERIOD
// FIXED: Using worker_id and qty for pure wax items (item_id = 1)
// ==========================================
$faisal_wax_summary = [
    'Mohsin Wax' => ['qty' => 0, 'rate' => 700, 'amount' => 0],
    'Waseem Bhai' => ['qty' => 0, 'rate' => 650, 'amount' => 0],
];
$faisal_other_customers = []; // Store other customer details
$faisal_other_total_qty = 0;
$faisal_other_amount = 0;
$faisal_total_wax_qty = 0;
$faisal_total_wax_amount = 0;

// For debugging
$faisal_customer_details = [];

if ($faisal_id) {
    // ==========================================
    // FIXED QUERY: Get Faisal's WAX work
    // Pure wax items use: item_id = wax_item, worker_id = Faisal, qty = weight
    // ==========================================
    $stmtFaisalWax = $pdo->prepare("
        SELECT 
            c.name as customer_name, 
            c.id as customer_id,
            SUM(ii.qty) as total_qty
        FROM wax_invoice_items ii
        JOIN wax_invoices i ON ii.invoice_id = i.id
        JOIN wax_customers c ON i.customer_id = c.id
        WHERE ii.worker_id = ? 
          AND ii.item_id = ?
          AND i.invoice_date BETWEEN ? AND ?
          AND ii.qty > 0
        GROUP BY c.id, c.name
        ORDER BY c.name
    ");
    $stmtFaisalWax->execute([$faisal_id, $wax_item_id, $start_date, $end_date]);
    $faisal_wax_items = $stmtFaisalWax->fetchAll();
    
    foreach ($faisal_wax_items as $item) {
        $customer_name = $item['customer_name'];
        $qty = floatval($item['total_qty']);
        
        // Store for debugging
        $faisal_customer_details[$customer_name] = $qty;
        
        $matched = match_special_customer($customer_name, $special_rate_customers);
        if ($matched) {
            $faisal_wax_summary[$matched]['qty'] += $qty;
        } else {
            $faisal_other_total_qty += $qty;
            // Store individual customer for detail display
            if (!isset($faisal_other_customers[$customer_name])) {
                $faisal_other_customers[$customer_name] = 0;
            }
            $faisal_other_customers[$customer_name] += $qty;
        }
    }
    
    // ==========================================
    // ALSO CHECK: wax_qty where wax_worker_id = Faisal (for design+wax combos)
    // ==========================================
    $stmtFaisalWaxCombo = $pdo->prepare("
        SELECT 
            c.name as customer_name, 
            SUM(ii.wax_qty) as total_qty
        FROM wax_invoice_items ii
        JOIN wax_invoices i ON ii.invoice_id = i.id
        JOIN wax_customers c ON i.customer_id = c.id
        WHERE ii.wax_worker_id = ? 
          AND i.invoice_date BETWEEN ? AND ?
          AND ii.wax_qty > 0
        GROUP BY c.id, c.name
        ORDER BY c.name
    ");
    $stmtFaisalWaxCombo->execute([$faisal_id, $start_date, $end_date]);
    $faisal_wax_combo_items = $stmtFaisalWaxCombo->fetchAll();
    
    foreach ($faisal_wax_combo_items as $item) {
        $customer_name = $item['customer_name'];
        $qty = floatval($item['total_qty']);
        
        // Add to debug
        if (isset($faisal_customer_details[$customer_name])) {
            $faisal_customer_details[$customer_name] += $qty;
        } else {
            $faisal_customer_details[$customer_name] = $qty;
        }
        
        $matched = match_special_customer($customer_name, $special_rate_customers);
        if ($matched) {
            $faisal_wax_summary[$matched]['qty'] += $qty;
        } else {
            $faisal_other_total_qty += $qty;
            if (!isset($faisal_other_customers[$customer_name])) {
                $faisal_other_customers[$customer_name] = 0;
            }
            $faisal_other_customers[$customer_name] += $qty;
        }
    }
    
    // Calculate amounts
    foreach ($faisal_wax_summary as $cust => $data) {
        $faisal_wax_summary[$cust]['amount'] = $data['qty'] * $data['rate'];
        $faisal_total_wax_qty += $data['qty'];
        $faisal_total_wax_amount += $faisal_wax_summary[$cust]['amount'];
    }
    
    $faisal_other_amount = $faisal_other_total_qty * $default_wax_rate;
    $faisal_total_wax_qty += $faisal_other_total_qty;
    $faisal_total_wax_amount += $faisal_other_amount;
}

// ==========================================
// Faisal Opening Wax (before start_date)
// FIXED: Using same logic as current period
// ==========================================
$faisal_opening_wax = 0;
$faisal_opening_qty = 0;
if ($faisal_id) {
    // Pure wax items before start_date
    $stmtFaisalWaxOp = $pdo->prepare("
        SELECT c.name as customer_name, SUM(ii.qty) as total_qty
        FROM wax_invoice_items ii
        JOIN wax_invoices i ON ii.invoice_id = i.id
        JOIN wax_customers c ON i.customer_id = c.id
        WHERE ii.worker_id = ? 
          AND ii.item_id = ?
          AND i.invoice_date < ?
          AND ii.qty > 0
        GROUP BY c.id, c.name
    ");
    $stmtFaisalWaxOp->execute([$faisal_id, $wax_item_id, $start_date]);
    $op_items = $stmtFaisalWaxOp->fetchAll();
    
    foreach ($op_items as $item) {
        $qty = floatval($item['total_qty']);
        $faisal_opening_qty += $qty;
        $matched = match_special_customer($item['customer_name'], $special_rate_customers);
        if ($matched) {
            $faisal_opening_wax += $qty * $special_rate_customers[$matched];
        } else {
            $faisal_opening_wax += $qty * $default_wax_rate;
        }
    }
    
    // Also check wax_qty/wax_worker_id combos before start_date
    $stmtFaisalWaxOpCombo = $pdo->prepare("
        SELECT c.name as customer_name, SUM(ii.wax_qty) as total_qty
        FROM wax_invoice_items ii
        JOIN wax_invoices i ON ii.invoice_id = i.id
        JOIN wax_customers c ON i.customer_id = c.id
        WHERE ii.wax_worker_id = ? 
          AND i.invoice_date < ?
          AND ii.wax_qty > 0
        GROUP BY c.id, c.name
    ");
    $stmtFaisalWaxOpCombo->execute([$faisal_id, $start_date]);
    $op_combo_items = $stmtFaisalWaxOpCombo->fetchAll();
    
    foreach ($op_combo_items as $item) {
        $qty = floatval($item['total_qty']);
        $faisal_opening_qty += $qty;
        $matched = match_special_customer($item['customer_name'], $special_rate_customers);
        if ($matched) {
            $faisal_opening_wax += $qty * $special_rate_customers[$matched];
        } else {
            $faisal_opening_wax += $qty * $default_wax_rate;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <title>Worker Statements: <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f0f2f5;
            font-size: 13px;
        }
        .page-break { 
            page-break-after: always; 
            background: white; 
            padding: 25px; 
            margin-bottom: 20px; 
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header { 
            text-align: center; 
            margin-bottom: 20px; 
            padding-bottom: 15px;
            border-bottom: 3px solid #2c3e50;
        }
        .header h1 { 
            margin: 0 0 5px 0; 
            color: #2c3e50; 
            font-size: 22px;
        }
        .header h3 { 
            margin: 5px 0; 
            color: #7f8c8d; 
            font-weight: normal;
            font-size: 14px;
        }
        .header p { 
            margin: 5px 0 0 0; 
            color: #34495e;
            font-weight: 600;
        }
        .worker-details { 
            margin-bottom: 15px; 
            padding: 12px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 6px;
            font-size: 15px;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 12px;
            margin-bottom: 15px;
        }
        th, td { 
            border: 1px solid #ddd; 
            padding: 8px 6px; 
            text-align: center; 
        }
        th { 
            background: #34495e; 
            color: white; 
            font-weight: 600;
            font-size: 11px;
            text-transform: uppercase;
        }
        .urdu { 
            font-family: 'Noto Nastaliq Urdu', serif; 
            direction: rtl; 
        }
        .right { text-align: right; }
        .left { text-align: left; }
        .total-row { 
            font-weight: bold; 
            background: #ecf0f1; 
        }
        
        /* Debug Box */
        .debug-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .debug-box h5 {
            margin: 0 0 10px 0;
            color: #856404;
        }
        .debug-box table {
            font-size: 11px;
        }
        .debug-box th {
            background: #ffc107;
            color: #333;
        }
        
        /* Wax Summary Box */
        .wax-summary-box {
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            border: 2px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .wax-summary-box h4 {
            margin: 0 0 12px 0;
            color: #2980b9;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .wax-summary-box table {
            margin: 0;
            background: white;
            border-radius: 5px;
            overflow: hidden;
        }
        .wax-summary-box th {
            background: #3498db;
        }
        .wax-summary-box .special-row {
            background: #fef9e7;
        }
        .wax-summary-box .other-row {
            background: #d5f5e3;
        }
        .wax-summary-box .total-row {
            background: #2c3e50;
            color: white;
        }
        .wax-summary-box .total-row td {
            border-color: #2c3e50;
        }
        
        /* Transaction Table */
        .transaction-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .transaction-table tbody tr:hover {
            background: #e8f4fc;
        }
        .wax-total-row {
            background: #d6eaf8 !important;
            font-weight: 600;
        }
        .closing-row {
            background: #2c3e50;
            color: white;
            font-size: 14px;
        }
        .closing-row td {
            border-color: #2c3e50;
            padding: 12px 8px;
        }
        
        /* Print Button */
        .no-print {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            padding: 12px 25px;
            font-size: 14px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 0 5px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
        }
        
        @media print {
            .no-print { display: none; }
            .debug-box { display: none; }
            body { padding: 0; background: white; }
            .page-break { 
                box-shadow: none; 
                margin-bottom: 0; 
                border-radius: 0;
                padding: 15px;
            }
            .worker-details {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button onclick="window.print()" class="btn btn-primary">🖨️ Print All Statements</button>
    <a href="reports.php" class="btn btn-secondary">← Back to Reports</a>
    
    <!-- Debug Info 
    <div style="margin-top: 15px; padding: 10px; background: #e8f4fc; border-radius: 5px; font-size: 12px;">
        <strong>Debug:</strong> Faisal ID = <?= $faisal_id ?: 'NOT FOUND' ?> | 
        Wax Item ID = <?= $wax_item_id ?> | 
        Total Wax Qty = <?= number_format($faisal_total_wax_qty, 3) ?> grams | 
        Total Amount = <?= format_currency($faisal_total_wax_amount) ?>
    </div>-->
</div>

<!-- SUMMARY PAGE -->
<div class="page-break">
    <div class="header">
        <h1>M.J Casting</h1>
        <h3>All Workers Summary</h3>
        <p><?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">SR</th>
                <th width="30%">Worker Name</th>
                <th width="13%">Opening</th>
                <th width="13%">Wax Qty</th>
                <th width="13%">Commission</th>
                <th width="13%">Payment</th>
                <th width="13%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $summary_sr = 1;
            $all_op = 0; $all_comm = 0; $all_paid = 0; $all_bal = 0; $all_wax_qty = 0;
            
            $worker_data = [];
            foreach ($workers as $w) {
                $wid = $w['id'];
                $is_faisal = ($wid == $faisal_id);
                
                if ($is_faisal) {
                    // FAISAL: Only wax work with fixed rates (no regular commission)
                    $op_comm = $faisal_opening_wax;
                    $curr_comm = $faisal_total_wax_amount;
                    $wax_qty = $faisal_total_wax_qty;
                } else {
                    $wax_qty = 0;
                    // OTHER WORKERS: Regular commission + wax commission from database
                    
                    // Opening - Regular commission
                    $stmtOpComm = $pdo->prepare("
                        SELECT COALESCE(SUM(commission_amount), 0)
                        FROM wax_invoice_items ii 
                        JOIN wax_invoices i ON ii.invoice_id = i.id 
                        WHERE ii.worker_id = ? AND i.invoice_date < ?
                    ");
                    $stmtOpComm->execute([$wid, $start_date]);
                    $op_comm = $stmtOpComm->fetchColumn() ?: 0;
                    
                    // Opening - Wax commission
                    $stmtOpWax = $pdo->prepare("
                        SELECT COALESCE(SUM(wax_commission), 0)
                        FROM wax_invoice_items ii 
                        JOIN wax_invoices i ON ii.invoice_id = i.id 
                        WHERE ii.wax_worker_id = ? AND i.invoice_date < ?
                    ");
                    $stmtOpWax->execute([$wid, $start_date]);
                    $op_comm += $stmtOpWax->fetchColumn() ?: 0;
                    
                    // Current - Regular commission
                    $stmtCurComm = $pdo->prepare("
                        SELECT COALESCE(SUM(commission_amount), 0)
                        FROM wax_invoice_items ii 
                        JOIN wax_invoices i ON ii.invoice_id = i.id 
                        WHERE ii.worker_id = ? AND i.invoice_date BETWEEN ? AND ?
                    ");
                    $stmtCurComm->execute([$wid, $start_date, $end_date]);
                    $curr_comm = $stmtCurComm->fetchColumn() ?: 0;
                    
                    // Current - Wax commission
                    $stmtCurWax = $pdo->prepare("
                        SELECT COALESCE(SUM(wax_commission), 0)
                        FROM wax_invoice_items ii 
                        JOIN wax_invoices i ON ii.invoice_id = i.id 
                        WHERE ii.wax_worker_id = ? AND i.invoice_date BETWEEN ? AND ?
                    ");
                    $stmtCurWax->execute([$wid, $start_date, $end_date]);
                    $curr_comm += $stmtCurWax->fetchColumn() ?: 0;
                }
                
                // Payments (same for all)
                $stmtOpPaid = $pdo->prepare("SELECT SUM(amount) FROM wax_worker_payments WHERE worker_id = ? AND payment_date < ?");
                $stmtOpPaid->execute([$wid, $start_date]);
                $op_paid = $stmtOpPaid->fetchColumn() ?: 0;
                $opening = $op_comm - $op_paid;
                
                $stmtCurPaid = $pdo->prepare("SELECT SUM(amount) FROM wax_worker_payments WHERE worker_id = ? AND payment_date BETWEEN ? AND ?");
                $stmtCurPaid->execute([$wid, $start_date, $end_date]);
                $curr_paid = $stmtCurPaid->fetchColumn() ?: 0;
                
                $closing = $opening + $curr_comm - $curr_paid;
                
                if ($opening == 0 && $curr_comm == 0 && $curr_paid == 0 && $closing == 0) continue;
                
                $worker_data[] = [
                    'id' => $wid,
                    'name' => $w['name'],
                    'op' => $opening,
                    'wax_qty' => $wax_qty,
                    'comm' => $curr_comm,
                    'paid' => $curr_paid,
                    'bal' => $closing,
                    'is_faisal' => $is_faisal
                ];
                
                $all_op += $opening;
                $all_wax_qty += $wax_qty;
                $all_comm += $curr_comm;
                $all_paid += $curr_paid;
                $all_bal += $closing;
            }
            
            foreach ($worker_data as $wd):
            ?>
            <tr>
                <td><?= $summary_sr++ ?></td>
                <td class="left"><?= htmlspecialchars($wd['name']) ?><?= $wd['is_faisal'] ? ' ⚡' : '' ?></td>
                <td class="right"><?= format_currency($wd['op']) ?></td>
                <td class="right"><?= $wd['wax_qty'] > 0 ? number_format($wd['wax_qty'], 3) . 'g' : '-' ?></td>
                <td class="right"><?= format_currency($wd['comm']) ?></td>
                <td class="right"><?= format_currency($wd['paid']) ?></td>
                <td class="right"><?= format_currency($wd['bal']) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="2" class="right">Grand Total</td>
                <td class="right"><?= format_currency($all_op) ?></td>
                <td class="right"><?= $all_wax_qty > 0 ? number_format($all_wax_qty, 3) . 'g' : '-' ?></td>
                <td class="right"><?= format_currency($all_comm) ?></td>
                <td class="right"><?= format_currency($all_paid) ?></td>
                <td class="right"><?= format_currency($all_bal) ?></td>
            </tr>
        </tbody>
    </table>
</div>

<?php foreach ($workers as $worker): 
    $worker_id = $worker['id'];
    $is_faisal = ($worker_id == $faisal_id);

    // ==========================================
    // OPENING BALANCE CALCULATION
    // ==========================================
    if ($is_faisal) {
        // FAISAL: Only wax with fixed rates
        $op_comm = $faisal_opening_wax;
    } else {
        // OTHER WORKERS: Regular + Wax from database
        $stmtOpComm = $pdo->prepare("
            SELECT COALESCE(SUM(commission_amount), 0)
            FROM wax_invoice_items ii 
            JOIN wax_invoices i ON ii.invoice_id = i.id 
            WHERE ii.worker_id = ? AND i.invoice_date < ?
        ");
        $stmtOpComm->execute([$worker_id, $start_date]);
        $op_comm = $stmtOpComm->fetchColumn() ?: 0;

        $stmtOpWax = $pdo->prepare("
            SELECT COALESCE(SUM(wax_commission), 0)
            FROM wax_invoice_items ii 
            JOIN wax_invoices i ON ii.invoice_id = i.id 
            WHERE ii.wax_worker_id = ? AND i.invoice_date < ?
        ");
        $stmtOpWax->execute([$worker_id, $start_date]);
        $op_comm += $stmtOpWax->fetchColumn() ?: 0;
    }

    $stmtOpPaid = $pdo->prepare("SELECT SUM(amount) FROM wax_worker_payments WHERE worker_id = ? AND payment_date < ?");
    $stmtOpPaid->execute([$worker_id, $start_date]);
    $op_paid = $stmtOpPaid->fetchColumn() ?: 0;

    $opening_balance = $op_comm - $op_paid;

    // ==========================================
    // BUILD TRANSACTIONS
    // ==========================================
    $transactions = [];
    
    if ($is_faisal) {
        // FAISAL: NO wax details - only payments go in transaction table
        // Wax is shown in summary box only
    } else {
        // OTHER WORKERS: Regular commission work
        $stmtReg = $pdo->prepare("
            SELECT 
                i.invoice_date as date,
                c.name as customer_name,
                it.name_urdu as design,
                ii.qty as qty,
                ii.amount as full_amount,
                ii.commission_amount as debit
            FROM wax_invoice_items ii
            JOIN wax_invoices i ON ii.invoice_id = i.id
            JOIN wax_customers c ON i.customer_id = c.id
            JOIN wax_items it ON ii.item_id = it.id
            WHERE ii.worker_id = ? AND i.invoice_date BETWEEN ? AND ?
            ORDER BY i.invoice_date ASC
        ");
        $stmtReg->execute([$worker_id, $start_date, $end_date]);
        
        foreach ($stmtReg->fetchAll() as $r) {
            $transactions[] = [
                'type' => 'COMM',
                'date' => $r['date'],
                'customer_name' => $r['customer_name'],
                'design' => $r['design'],
                'qty' => $r['qty'],
                'full_amount' => $r['full_amount'],
                'debit' => $r['debit'],
                'credit' => 0
            ];
        }

        // OTHER WORKERS: Wax work (from wax_worker_id)
        $stmtWax = $pdo->prepare("
            SELECT 
                i.invoice_date as date,
                c.name as customer_name,
                'ویکس' as design,
                ii.wax_qty,
                ii.wax_commission
            FROM wax_invoice_items ii
            JOIN wax_invoices i ON ii.invoice_id = i.id
            JOIN wax_customers c ON i.customer_id = c.id
            WHERE ii.wax_worker_id = ? AND i.invoice_date BETWEEN ? AND ? AND ii.wax_qty > 0
            ORDER BY i.invoice_date ASC
        ");
        $stmtWax->execute([$worker_id, $start_date, $end_date]);
        
        foreach ($stmtWax->fetchAll() as $w) {
            $transactions[] = [
                'type' => 'COMM',
                'date' => $w['date'],
                'customer_name' => $w['customer_name'],
                'design' => $w['design'],
                'qty' => $w['wax_qty'],
                'full_amount' => $w['wax_commission'],
                'debit' => $w['wax_commission'],
                'credit' => 0
            ];
        }
    }

    // PAYMENTS (for all workers including Faisal)
    $stmtPay = $pdo->prepare("
        SELECT payment_date as date, notes as design, amount
        FROM wax_worker_payments
        WHERE worker_id = ? AND payment_date BETWEEN ? AND ?
        ORDER BY payment_date ASC
    ");
    $stmtPay->execute([$worker_id, $start_date, $end_date]);
    
    foreach ($stmtPay->fetchAll() as $p) {
        $transactions[] = [
            'type' => 'PAY',
            'date' => $p['date'],
            'customer_name' => 'Payment',
            'design' => $p['design'] ?: '-',
            'qty' => 0,
            'full_amount' => 0,
            'debit' => 0,
            'credit' => $p['amount']
        ];
    }

    // Sort by date
    usort($transactions, function($a, $b) {
        $cmp = strtotime($a['date']) - strtotime($b['date']);
        if ($cmp == 0) return ($a['type'] == 'COMM' ? 0 : 1) - ($b['type'] == 'COMM' ? 0 : 1);
        return $cmp;
    });
    
    // Skip empty workers (but Faisal with wax should still show)
    if (!$is_faisal && empty($transactions) && $opening_balance == 0) continue;
    if ($is_faisal && $faisal_total_wax_amount == 0 && empty($transactions) && $opening_balance == 0) continue;
?>

<div class="page-break">
    <div class="header">
        <h1>M.J Casting</h1>
        <h3>Worker Statement (کاریگر لیجر)</h3>
        <p><?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></p>
    </div>

    <div class="worker-details">
        <strong>Worker:</strong> <?= htmlspecialchars($worker['name']) ?>
        <?php if ($is_faisal): ?> <span style="background: #27ae60; padding: 2px 8px; border-radius: 3px; margin-left: 10px;">WAX WORKER</span><?php endif; ?>
    </div>

    <?php if ($is_faisal): ?>
    
    <!-- DEBUG BOX - Customer-wise Wax Details 
    <div class="debug-box no-print">
        <h5>🔍 Debug: Customer-wise Wax Qty (Total: <?= number_format($faisal_total_wax_qty, 3) ?> grams)</h5>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Wax Qty (Gram)</th>
                    <th>Category</th>
                    <th>Rate</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $debug_sr = 1;
                $debug_total = 0;
                $debug_amount_total = 0;
                ksort($faisal_customer_details);
                foreach ($faisal_customer_details as $cust => $qty): 
                    $debug_total += $qty;
                    $matched = match_special_customer($cust, $special_rate_customers);
                    $rate = $matched ? $special_rate_customers[$matched] : $default_wax_rate;
                    $amount = $qty * $rate;
                    $debug_amount_total += $amount;
                ?>
                <tr>
                    <td><?= $debug_sr++ ?></td>
                    <td class="left"><?= htmlspecialchars($cust) ?></td>
                    <td><?= number_format($qty, 3) ?></td>
                    <td><?= $matched ?: 'Other' ?></td>
                    <td><?= $rate ?></td>
                    <td class="right"><?= format_currency($amount) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" class="right"><strong>Total</strong></td>
                    <td><strong><?= number_format($debug_total, 3) ?></strong></td>
                    <td colspan="2"></td>
                    <td class="right"><strong><?= format_currency($debug_amount_total) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>-->
    
    <!-- FAISAL WAX SUMMARY BOX -->
    <div class="wax-summary-box">
        <h4>📦 Wax Commission Summary (واکس کمیشن خلاصہ)</h4>
        <table>
            <thead>
                <tr>
                    <th width="40%">Customer</th>
                    <th width="20%">Wax (Gram)</th>
                    <th width="20%">Rate/Gram</th>
                    <th width="20%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- Row 1: Mohsin Wax (100 Rs/gram) -->
                <tr class="special-row">
                    <td class="left"><strong>Mohsin Wax</strong></td>
                    <td><?= number_format($faisal_wax_summary['Mohsin Wax']['qty'], 3) ?></td>
                    <td>700</td>
                    <td class="right"><strong><?= format_currency($faisal_wax_summary['Mohsin Wax']['amount']) ?></strong></td>
                </tr>
                <!-- Row 2: Mamu Javed (100 Rs/gram) -->
                <tr class="special-row">
                    <td class="left"><strong>Waseem Bhai</strong></td>
                    <td><?= number_format($faisal_wax_summary['Waseem Bhai']['qty'], 3) ?></td>
                    <td>650</td>
                    <td class="right"><strong><?= format_currency($faisal_wax_summary['Waseem Bhai']['amount']) ?></strong></td>
                </tr>
                <!-- Row 3: All Other Customers (150 Rs/gram) -->
                <tr class="other-row">
                    <td class="left"><strong>Other Customers (دیگر گاہک)</strong></td>
                    <td><?= number_format($faisal_other_total_qty, 3) ?></td>
                    <td>700</td>
                    <td class="right"><strong><?= format_currency($faisal_other_amount) ?></strong></td>
                </tr>
                <!-- Total Row -->
                <tr class="total-row">
                    <td class="left"><strong>Total Wax (کل ویکس)</strong></td>
                    <td><strong><?= number_format($faisal_total_wax_qty, 3) ?></strong></td>
                    <td>-</td>
                    <td class="right"><strong><?= format_currency($faisal_total_wax_amount) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- FAISAL SIMPLE SUMMARY TABLE -->
    <table class="transaction-table">
        <thead>
            <tr>
                <th width="30%">Description</th>
                <th width="20%">Wax (Gram)</th>
                <th width="15%">Debit</th>
                <th width="15%">Credit</th>
                <th width="20%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $balance = $opening_balance;
            $total_paid = 0;
            ?>
            <tr style="background: #f8f9fa;">
                <td class="left"><strong>Opening Balance (سابقہ بقایا)</strong></td>
                <td>-</td>
                <td>-</td>
                <td>-</td>
                <td class="right"><strong><?= format_currency($opening_balance) ?></strong></td>
            </tr>
            
            <?php if ($faisal_total_wax_amount > 0): 
                $balance += $faisal_total_wax_amount;
            ?>
            <tr class="wax-total-row">
                <td class="left"><strong>📦 Wax Commission (واکس کمیشن)</strong></td>
                <td><?= number_format($faisal_total_wax_qty, 3) ?></td>
                <td class="right"><strong><?= format_currency($faisal_total_wax_amount) ?></strong></td>
                <td>-</td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
            <?php endif; ?>
            
            <?php foreach ($transactions as $t): 
                if ($t['type'] == 'PAY'):
                    $balance -= $t['credit'];
                    $total_paid += $t['credit'];
            ?>
            <tr>
                <td class="left"><?= date('d-M', strtotime($t['date'])) ?> - Payment <?= $t['design'] != '-' ? '(' . htmlspecialchars($t['design']) . ')' : '' ?></td>
                <td>-</td>
                <td>-</td>
                <td class="right"><?= format_currency($t['credit']) ?></td>
                <td class="right"><?= format_currency($balance) ?></td>
            </tr>
            <?php endif; endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td class="left"><strong>Totals (کل)</strong></td>
                <td><strong><?= number_format($faisal_total_wax_qty, 3) ?></strong></td>
                <td class="right"><strong><?= format_currency($faisal_total_wax_amount) ?></strong></td>
                <td class="right"><strong><?= format_currency($total_paid) ?></strong></td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
            <tr class="closing-row">
                <td colspan="4" class="right"><strong>Closing Balance / موجودہ بقایا (Payable)</strong></td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <?php else: ?>
    <!-- OTHER WORKERS - NORMAL DETAILED TABLE -->
    <table class="transaction-table">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="18%">Customer</th>
                <th width="22%">Design (تفصیل)</th>
                <th width="8%">Qty</th>
                <th width="12%">Amount</th>
                <th width="10%">Debit</th>
                <th width="10%">Credit</th>
                <th width="10%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background: #f8f9fa;">
                <td colspan="5" class="right"><strong>Opening Balance (سابقہ بقایا)</strong></td>
                <td>-</td>
                <td>-</td>
                <td class="right"><strong><?= format_currency($opening_balance) ?></strong></td>
            </tr>

            <?php
            $balance = $opening_balance;
            $total_earned = 0;
            $total_paid = 0;

            foreach ($transactions as $t):
                $balance += $t['debit'] - $t['credit'];
                $total_earned += $t['debit'];
                $total_paid += $t['credit'];
            ?>
            <tr>
                <td><?= date('d-M', strtotime($t['date'])) ?></td>
                <td><?= htmlspecialchars($t['customer_name']) ?></td>
                <td class="<?= $t['type'] == 'COMM' ? 'urdu' : '' ?>"><?= htmlspecialchars($t['design']) ?></td>
                <td><?= $t['qty'] > 0 ? (is_float($t['qty']) ? number_format($t['qty'], 2) : $t['qty']) : '-' ?></td>
                <td class="right"><?= $t['full_amount'] > 0 ? format_currency($t['full_amount']) : '-' ?></td>
                <td class="right"><?= $t['debit'] > 0 ? format_currency($t['debit']) : '-' ?></td>
                <td class="right"><?= $t['credit'] > 0 ? format_currency($t['credit']) : '-' ?></td>
                <td class="right"><?= format_currency($balance) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="right"><strong>Totals (کل)</strong></td>
                <td class="right"><strong><?= format_currency($total_earned) ?></strong></td>
                <td class="right"><strong><?= format_currency($total_paid) ?></strong></td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
            <tr class="closing-row">
                <td colspan="7" class="right"><strong>Closing Balance / موجودہ بقایا (Payable)</strong></td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

<?php endforeach; ?>

</body>
</html>