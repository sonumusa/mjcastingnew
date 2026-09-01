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
$faisal_worker_name = 'fahad';
$default_wax_rate = 750;

$special_rate_customers = [
    'Mohsin Wax' => 750,
    'Waseem Bhai' => 750,
];

$stmtFaisal = $pdo->prepare("SELECT id FROM wax_workers WHERE name LIKE ? AND active = 1 LIMIT 1");
$stmtFaisal->execute(['%' . $faisal_worker_name . '%']);
$faisal_id = $stmtFaisal->fetchColumn();

$stmtWaxItem = $pdo->prepare("SELECT id FROM wax_items WHERE LOWER(name) LIKE '%wax%' OR name_urdu LIKE '%ویکس%' LIMIT 1");
$stmtWaxItem->execute();
$wax_item_id = $stmtWaxItem->fetchColumn() ?: 1;

$workers = $pdo->query("SELECT id, name FROM wax_workers WHERE active = 1 ORDER BY name ASC")->fetchAll();

function match_special_customer($customer_name, $special_rate_customers) {
    $customer_lower = strtolower(trim($customer_name));
    foreach ($special_rate_customers as $special_customer => $rate) {
        $special_lower = strtolower(trim($special_customer));
        if (strpos($customer_lower, $special_lower) !== false || 
            strpos($special_lower, $customer_lower) !== false ||
            strpos($customer_lower, str_replace(' ', '', $special_lower)) !== false) {
            return $special_customer;
        }
    }
    return null;
}

// ==========================================
// Calculate Faisal's Wax Summary
// ==========================================
$faisal_wax_summary = [
    'Mohsin Wax' => ['qty' => 0, 'rate' => 750, 'amount' => 0],
    'Waseem Bhai' => ['qty' => 0, 'rate' => 750, 'amount' => 0],
];
$faisal_other_customers = [];
$faisal_other_total_qty = 0;
$faisal_other_amount = 0;
$faisal_total_wax_qty = 0;
$faisal_total_wax_amount = 0;
$faisal_customer_details = [];

if ($faisal_id) {
    $stmtFaisalWax = $pdo->prepare("
        SELECT c.name as customer_name, c.id as customer_id, SUM(ii.qty) as total_qty
        FROM wax_invoice_items ii
        JOIN wax_invoices i ON ii.invoice_id = i.id
        JOIN wax_customers c ON i.customer_id = c.id
        WHERE ii.worker_id = ? AND ii.item_id = ? AND i.invoice_date BETWEEN ? AND ? AND ii.qty > 0
        GROUP BY c.id, c.name ORDER BY c.name
    ");
    $stmtFaisalWax->execute([$faisal_id, $wax_item_id, $start_date, $end_date]);
    $faisal_wax_items = $stmtFaisalWax->fetchAll();
    
    foreach ($faisal_wax_items as $item) {
        $customer_name = $item['customer_name'];
        $qty = floatval($item['total_qty']);
        $faisal_customer_details[$customer_name] = $qty;
        
        $matched = match_special_customer($customer_name, $special_rate_customers);
        if ($matched) {
            $faisal_wax_summary[$matched]['qty'] += $qty;
        } else {
            $faisal_other_total_qty += $qty;
            if (!isset($faisal_other_customers[$customer_name])) $faisal_other_customers[$customer_name] = 0;
            $faisal_other_customers[$customer_name] += $qty;
        }
    }
    
    $stmtFaisalWaxCombo = $pdo->prepare("
        SELECT c.name as customer_name, SUM(ii.wax_qty) as total_qty
        FROM wax_invoice_items ii
        JOIN wax_invoices i ON ii.invoice_id = i.id
        JOIN wax_customers c ON i.customer_id = c.id
        WHERE ii.wax_worker_id = ? AND i.invoice_date BETWEEN ? AND ? AND ii.wax_qty > 0
        GROUP BY c.id, c.name ORDER BY c.name
    ");
    $stmtFaisalWaxCombo->execute([$faisal_id, $start_date, $end_date]);
    $faisal_wax_combo_items = $stmtFaisalWaxCombo->fetchAll();
    
    foreach ($faisal_wax_combo_items as $item) {
        $customer_name = $item['customer_name'];
        $qty = floatval($item['total_qty']);
        
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
            if (!isset($faisal_other_customers[$customer_name])) $faisal_other_customers[$customer_name] = 0;
            $faisal_other_customers[$customer_name] += $qty;
        }
    }
    
    foreach ($faisal_wax_summary as $cust => $data) {
        $faisal_wax_summary[$cust]['amount'] = $data['qty'] * $data['rate'];
        $faisal_total_wax_qty += $data['qty'];
        $faisal_total_wax_amount += $faisal_wax_summary[$cust]['amount'];
    }
    
    $faisal_other_amount = $faisal_other_total_qty * $default_wax_rate;
    $faisal_total_wax_qty += $faisal_other_total_qty;
    $faisal_total_wax_amount += $faisal_other_amount;
}

$faisal_opening_wax = 0;
$faisal_opening_qty = 0;
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worker Statements: <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            margin: 0; 
            padding: 15px; 
            background: #f0f2f5;
            font-size: 15px;
            line-height: 1.5;
        }

        /* ================================
           PORTRAIT PAGE CONTAINER
           A4 Portrait: 210mm x 297mm
           ================================ */
        .page-break { 
            page-break-after: always;
            background: white; 
            padding: 30px 28px;
            margin: 0 auto 25px auto;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.12);
            max-width: 750px;     /* Portrait-friendly width */
            min-height: auto;
        }

        /* ================================
           HEADER - Larger, clearer
           ================================ */
        .header { 
            text-align: center; 
            margin-bottom: 22px; 
            padding-bottom: 18px;
            border-bottom: 3px solid #2c3e50;
        }
        .header h1 { 
            margin: 0 0 6px 0; 
            color: #2c3e50; 
            font-size: 26px;
            letter-spacing: 0.5px;
        }
        .header h3 { 
            margin: 6px 0; 
            color: #7f8c8d; 
            font-weight: 500;
            font-size: 16px;
        }
        .header .date-range { 
            margin: 8px 0 0 0; 
            color: #34495e;
            font-weight: 700;
            font-size: 16px;
            background: #ecf0f1;
            display: inline-block;
            padding: 6px 18px;
            border-radius: 20px;
        }

        /* ================================
           WORKER DETAILS BAR
           ================================ */
        .worker-details { 
            margin-bottom: 20px; 
            padding: 14px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .worker-badge {
            background: #27ae60;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ================================
           TABLES - Portrait optimized
           Larger text, fewer columns, more padding
           ================================ */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            font-size: 14px;
            margin-bottom: 18px;
        }
        th, td { 
            border: 1px solid #d5d8dc; 
            padding: 10px 10px; 
            text-align: center;
            vertical-align: middle;
        }
        th { 
            background: #2c3e50; 
            color: white; 
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
        }
        
        .urdu { 
            font-family: 'Noto Nastaliq Urdu', serif; 
            direction: rtl; 
            font-size: 16px;
            line-height: 2;
        }
        .right { text-align: right; }
        .left { text-align: left; }
        .bold { font-weight: 700; }
        
        .total-row { 
            font-weight: 700; 
            background: #ecf0f1; 
            font-size: 15px;
        }

        /* ================================
           SUMMARY TABLE (All Workers)
           Portrait: 4 columns only
           ================================ */
        .summary-table th,
        .summary-table td {
            padding: 12px 10px;
            font-size: 15px;
        }
        .summary-table .worker-name {
            text-align: left;
            font-weight: 600;
            font-size: 16px;
        }
        .summary-table .total-row td {
            font-size: 16px;
            padding: 14px 10px;
            background: #2c3e50;
            color: white;
            border-color: #2c3e50;
        }
        .summary-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        .summary-table tbody tr:hover {
            background: #eaf2f8;
        }

        /* ================================
           WAX SUMMARY BOX (Faisal)
           ================================ */
        .wax-summary-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e8ecf1 100%);
            border: 2px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 22px;
        }
        .wax-summary-box h4 {
            margin: 0 0 15px 0;
            color: #2980b9;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .wax-summary-box table {
            margin: 0;
            background: white;
            border-radius: 6px;
            overflow: hidden;
        }
        .wax-summary-box th {
            background: #3498db;
            font-size: 14px;
            padding: 12px 10px;
        }
        .wax-summary-box td {
            font-size: 15px;
            padding: 12px 10px;
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
            font-size: 16px;
            padding: 14px 10px;
        }

        /* ================================
           TRANSACTION TABLE
           Portrait: Reorganized columns
           ================================ */
        .transaction-table {
            font-size: 13px;
        }
        .transaction-table th {
            font-size: 12px;
            padding: 10px 6px;
            white-space: nowrap;
        }
        .transaction-table td {
            padding: 9px 6px;
            font-size: 13px;
        }
        .transaction-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        .transaction-table tbody tr:hover {
            background: #e8f4fc;
        }
        
        /* Commission row styling */
        .transaction-table .row-comm {
            /* default */
        }
        .transaction-table .row-pay {
            background: #fef9e7 !important;
        }
        .transaction-table .row-pay:hover {
            background: #fdf2d0 !important;
        }
        
        .transaction-table .debit-cell {
            color: #27ae60;
            font-weight: 600;
        }
        .transaction-table .credit-cell {
            color: #e74c3c;
            font-weight: 600;
        }
        .transaction-table .balance-cell {
            font-weight: 700;
            font-size: 14px;
        }

        .wax-total-row {
            background: #d6eaf8 !important;
            font-weight: 600;
        }
        
        /* Closing Balance Row */
        .closing-row {
            background: #2c3e50 !important;
            color: white;
            font-size: 16px;
        }
        .closing-row td {
            border-color: #2c3e50;
            padding: 14px 10px;
        }
        
        /* Totals footer row */
        .totals-footer td {
            font-size: 14px;
            padding: 12px 8px;
            background: #ecf0f1;
            font-weight: 700;
        }

        /* ================================
           CONTROL PANEL (No Print)
           ================================ */
        .no-print {
            text-align: center;
            margin: 0 auto 25px auto;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.12);
            max-width: 750px;
        }
        .no-print h2 {
            margin: 0 0 8px 0;
            color: #2c3e50;
            font-size: 22px;
        }
        .no-print .subtitle {
            color: #666;
            margin-bottom: 18px;
            font-size: 15px;
        }
        
        .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-bottom: 10px;
        }
        .btn {
            padding: 12px 28px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .btn:hover { opacity: 0.9; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; box-shadow: none; }

        /* Progress Bar */
        .progress-container {
            display: none;
            margin-top: 20px;
            padding: 18px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .progress-bar {
            width: 100%;
            height: 32px;
            background: #e0e0e0;
            border-radius: 16px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            border-radius: 16px;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 14px;
        }
        .progress-text {
            text-align: center;
            color: #555;
            font-size: 14px;
        }

        /* ================================
           PRINT STYLES - Portrait A4
           ================================ */
        @media print {
            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
            
            .no-print { display: none !important; }
            
            body { 
                padding: 0; 
                background: white; 
                font-size: 13px;
            }
            
            .page-break { 
                box-shadow: none; 
                margin: 0;
                border-radius: 0;
                padding: 15px 12px;
                max-width: 100%;
                page-break-after: always;
            }
            .page-break:last-child {
                page-break-after: avoid;
            }
            
            .header h1 { font-size: 22px; }
            .header h3 { font-size: 14px; }
            .header .date-range { font-size: 14px; }
            
            .worker-details {
                background: #333 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 16px;
                padding: 10px 15px;
            }
            
            table { font-size: 12px; }
            th { 
                background: #2c3e50 !important; 
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-size: 11px;
                padding: 8px 5px;
            }
            td { padding: 7px 5px; font-size: 12px; }
            
            .summary-table th,
            .summary-table td {
                font-size: 13px;
                padding: 10px 8px;
            }
            
            .transaction-table th { font-size: 10px; padding: 8px 4px; }
            .transaction-table td { font-size: 11px; padding: 7px 4px; }
            
            .closing-row {
                background: #2c3e50 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .closing-row td { font-size: 14px; }
            
            .total-row {
                background: #ecf0f1 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .summary-table .total-row td {
                background: #2c3e50 !important;
                color: white !important;
            }
            
            .wax-summary-box {
                border-width: 1px;
                padding: 12px;
            }
            .wax-summary-box th {
                background: #3498db !important;
            }
            .wax-summary-box .total-row td {
                background: #2c3e50 !important;
                color: white !important;
            }
            .wax-summary-box .special-row {
                background: #fef9e7 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .wax-summary-box .other-row {
                background: #d5f5e3 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .row-pay {
                background: #fef9e7 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* ================================
           MOBILE RESPONSIVE
           ================================ */
        @media (max-width: 600px) {
            body { padding: 8px; font-size: 14px; }
            .page-break { padding: 18px 14px; }
            .header h1 { font-size: 20px; }
            .worker-details { font-size: 16px; padding: 12px 14px; }
            
            table { font-size: 12px; }
            th, td { padding: 8px 5px; }
            
            .transaction-table { font-size: 11px; }
            .transaction-table th { font-size: 10px; padding: 8px 3px; }
            .transaction-table td { padding: 7px 3px; font-size: 11px; }
            
            .btn { padding: 10px 18px; font-size: 13px; }
        }
    </style>
</head>
<body>

<!-- ================================
     CONTROL PANEL
     ================================ -->
<div class="no-print">
    <h2>📄 Worker Statements</h2>
    <p class="subtitle">
        Period: <strong><?= date('d-M-Y', strtotime($start_date)) ?></strong> to <strong><?= date('d-M-Y', strtotime($end_date)) ?></strong>
    </p>
    
    <div class="btn-group">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print All</button>
        <button onclick="exportAllAsImages()" class="btn btn-success" id="exportBtn">📸 Export as ZIP</button>
        <a href="reports.php" class="btn btn-secondary">← Back</a>
    </div>
    
    <div class="progress-container" id="progressContainer">
        <div class="progress-bar">
            <div class="progress-fill" id="progressFill" style="width: 0%">0%</div>
        </div>
        <div class="progress-text" id="progressText">Preparing export...</div>
    </div>
</div>

<!-- ================================
     SUMMARY PAGE
     ================================ -->
<div class="page-break" id="statement-summary" data-name="Summary">
    <div class="header">
        <h1>Punjab 3D Design & Wax Printing</h1>
        <h3>All Workers Summary</h3>
        <div class="date-range">
            <?= date('d-M-Y', strtotime($start_date)) ?> — <?= date('d-M-Y', strtotime($end_date)) ?>
        </div>
    </div>

    <table class="summary-table">
        <thead>
            <tr>
                <th width="8%">SR</th>
                <th width="42%">Worker Name</th>
                <th width="25%">Wax (Gram)</th>
                <th width="25%">Commission</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $summary_sr = 1;
            $all_comm = 0; $all_wax_qty = 0;
            
            $worker_data = [];
            foreach ($workers as $w) {
                $wid = $w['id'];
                $is_faisal = ($wid == $faisal_id);
                
                if ($is_faisal) {
                    $curr_comm = $faisal_total_wax_amount;
                    $wax_qty = $faisal_total_wax_qty;
                } else {
                    $wax_qty = 0;
                    
                    $stmtCurComm = $pdo->prepare("
                        SELECT COALESCE(SUM(commission_amount), 0)
                        FROM wax_invoice_items ii 
                        JOIN wax_invoices i ON ii.invoice_id = i.id 
                        WHERE ii.worker_id = ? AND i.invoice_date BETWEEN ? AND ?
                    ");
                    $stmtCurComm->execute([$wid, $start_date, $end_date]);
                    $curr_comm = $stmtCurComm->fetchColumn() ?: 0;
                    
                    $stmtCurWax = $pdo->prepare("
                        SELECT COALESCE(SUM(wax_commission), 0)
                        FROM wax_invoice_items ii 
                        JOIN wax_invoices i ON ii.invoice_id = i.id 
                        WHERE ii.wax_worker_id = ? AND i.invoice_date BETWEEN ? AND ?
                    ");
                    $stmtCurWax->execute([$wid, $start_date, $end_date]);
                    $curr_comm += $stmtCurWax->fetchColumn() ?: 0;
                }
                
                $opening = 0;
                
                $stmtCurPaid = $pdo->prepare("SELECT SUM(amount) FROM wax_worker_payments WHERE worker_id = ? AND payment_date BETWEEN ? AND ?");
                $stmtCurPaid->execute([$wid, $start_date, $end_date]);
                $curr_paid = $stmtCurPaid->fetchColumn() ?: 0;
                
                $closing = $curr_comm - $curr_paid;
                
                if ($opening == 0 && $curr_comm == 0 && $curr_paid == 0 && $closing == 0) continue;
                
                $worker_data[] = [
                    'id' => $wid,
                    'name' => $w['name'],
                    'wax_qty' => $wax_qty,
                    'comm' => $curr_comm,
                    'paid' => $curr_paid,
                    'bal' => $closing,
                    'is_faisal' => $is_faisal
                ];
                
                $all_wax_qty += $wax_qty;
                $all_comm += $curr_comm;
            }
            
            foreach ($worker_data as $wd):
            ?>
            <tr>
                <td><?= $summary_sr++ ?></td>
                <td class="worker-name"><?= htmlspecialchars($wd['name']) ?><?= $wd['is_faisal'] ? ' ⚡' : '' ?></td>
                <td class="right"><?= $wd['wax_qty'] > 0 ? number_format($wd['wax_qty'], 3) . 'g' : '—' ?></td>
                <td class="right bold"><?= format_currency($wd['comm']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" class="right">Grand Total</td>
                <td class="right"><?= $all_wax_qty > 0 ? number_format($all_wax_qty, 3) . 'g' : '—' ?></td>
                <td class="right"><?= format_currency($all_comm) ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- ================================
     INDIVIDUAL WORKER STATEMENTS
     ================================ -->
<?php 
$statement_index = 0;
foreach ($workers as $worker): 
    $worker_id = $worker['id'];
    $is_faisal = ($worker_id == $faisal_id);
    $opening_balance = 0;

    $transactions = [];
    
    if ($is_faisal) {
        // Faisal: Only payments
    } else {
        $stmtReg = $pdo->prepare("
            SELECT 
                i.invoice_date as date, c.name as customer_name,
                it.name_urdu as design, ii.qty as qty,
                ii.amount as full_amount, ii.commission_amount as debit
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
                'type' => 'COMM', 'date' => $r['date'],
                'customer_name' => $r['customer_name'], 'design' => $r['design'],
                'qty' => $r['qty'], 'full_amount' => $r['full_amount'],
                'debit' => $r['debit'], 'credit' => 0
            ];
        }

        $stmtWax = $pdo->prepare("
            SELECT 
                i.invoice_date as date, c.name as customer_name,
                'ویکس' as design, ii.wax_qty, ii.wax_commission
            FROM wax_invoice_items ii
            JOIN wax_invoices i ON ii.invoice_id = i.id
            JOIN wax_customers c ON i.customer_id = c.id
            WHERE ii.wax_worker_id = ? AND i.invoice_date BETWEEN ? AND ? AND ii.wax_qty > 0
            ORDER BY i.invoice_date ASC
        ");
        $stmtWax->execute([$worker_id, $start_date, $end_date]);
        
        foreach ($stmtWax->fetchAll() as $w) {
            $transactions[] = [
                'type' => 'COMM', 'date' => $w['date'],
                'customer_name' => $w['customer_name'], 'design' => $w['design'],
                'qty' => $w['wax_qty'], 'full_amount' => $w['wax_commission'],
                'debit' => $w['wax_commission'], 'credit' => 0
            ];
        }
    }

    $stmtPay = $pdo->prepare("
        SELECT payment_date as date, notes as design, amount
        FROM wax_worker_payments
        WHERE worker_id = ? AND payment_date BETWEEN ? AND ?
        ORDER BY payment_date ASC
    ");
    $stmtPay->execute([$worker_id, $start_date, $end_date]);
    
    foreach ($stmtPay->fetchAll() as $p) {
        $transactions[] = [
            'type' => 'PAY', 'date' => $p['date'],
            'customer_name' => 'Payment', 'design' => $p['design'] ?: '—',
            'qty' => 0, 'full_amount' => 0,
            'debit' => 0, 'credit' => $p['amount']
        ];
    }

    usort($transactions, function($a, $b) {
        $cmp = strtotime($a['date']) - strtotime($b['date']);
        if ($cmp == 0) return ($a['type'] == 'COMM' ? 0 : 1) - ($b['type'] == 'COMM' ? 0 : 1);
        return $cmp;
    });
    
    if (!$is_faisal && empty($transactions) && $opening_balance == 0) continue;
    if ($is_faisal && $faisal_total_wax_amount == 0 && empty($transactions) && $opening_balance == 0) continue;
    
    $statement_index++;
    $safe_worker_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $worker['name']);
?>

<div class="page-break" id="statement-<?= $statement_index ?>" data-name="<?= htmlspecialchars($safe_worker_name) ?>">
    <div class="header">
        <h1>Punjab 3D Design & Wax Printing</h1>
        <h3>Worker Statement (کاریگر لیجر)</h3>
        <div class="date-range">
            <?= date('d-M-Y', strtotime($start_date)) ?> — <?= date('d-M-Y', strtotime($end_date)) ?>
        </div>
    </div>

    <div class="worker-details">
        <span>👷 <?= htmlspecialchars($worker['name']) ?></span>
        <?php if ($is_faisal): ?>
            <span class="worker-badge">WAX WORKER</span>
        <?php endif; ?>
    </div>

    <?php if ($is_faisal): ?>
    <!-- ======== FAISAL WAX SUMMARY ======== -->
    <div class="wax-summary-box">
        <h4>📦 Wax Commission Summary (واکس کمیشن خلاصہ)</h4>
        <table>
            <thead>
                <tr>
                    <th width="35%">Customer</th>
                    <th width="22%">Wax (Gram)</th>
                    <th width="18%">Rate/g</th>
                    <th width="25%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr class="special-row">
                    <td class="left bold">Mohsin Wax</td>
                    <td><?= number_format($faisal_wax_summary['Mohsin Wax']['qty'], 3) ?></td>
                    <td>750</td>
                    <td class="right bold"><?= format_currency($faisal_wax_summary['Mohsin Wax']['amount']) ?></td>
                </tr>
                <tr class="special-row">
                    <td class="left bold">Waseem Bhai</td>
                    <td><?= number_format($faisal_wax_summary['Waseem Bhai']['qty'], 3) ?></td>
                    <td>750</td>
                    <td class="right bold"><?= format_currency($faisal_wax_summary['Waseem Bhai']['amount']) ?></td>
                </tr>
                <tr class="other-row">
                    <td class="left bold">Other Customers (دیگر)</td>
                    <td><?= number_format($faisal_other_total_qty, 3) ?></td>
                    <td>750</td>
                    <td class="right bold"><?= format_currency($faisal_other_amount) ?></td>
                </tr>
                <tr class="total-row">
                    <td class="left bold">Total (کل ویکس)</td>
                    <td><strong><?= number_format($faisal_total_wax_qty, 3) ?></strong></td>
                    <td>—</td>
                    <td class="right"><strong><?= format_currency($faisal_total_wax_amount) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>

    <?php if (!empty($faisal_other_customers)): ?>
    <!--<div class="wax-summary-box" style="border-color: #27ae60;">
        <h4 style="color: #27ae60;">📋 Other Customers Detail (دیگر گاہک تفصیل)</h4>
        <table>
            <thead>
                <tr>
                    <th width="10%">SR</th>
                    <th width="50%">Customer</th>
                    <th width="20%">Wax (Gram)</th>
                    <th width="20%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php $osr = 1; foreach ($faisal_other_customers as $cname => $cqty): ?>
                <tr>
                    <td><?= $osr++ ?></td>
                    <td class="left"><?= htmlspecialchars($cname) ?></td>
                    <td><?= number_format($cqty, 3) ?></td>
                    <td class="right bold"><?= format_currency($cqty * $default_wax_rate) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" class="right">Total</td>
                    <td><strong><?= number_format($faisal_other_total_qty, 3) ?></strong></td>
                    <td class="right"><strong><?= format_currency($faisal_other_amount) ?></strong></td>
                </tr>
            </tbody>
        </table>
    </div>-->
    <?php endif; ?>

    <?php 
    // Faisal payments
    if (!empty($transactions)):
        $faisal_balance = $faisal_total_wax_amount;
        $faisal_total_paid = 0;
    ?>
    <table class="transaction-table">
        <thead>
            <tr>
                <th width="20%">Date</th>
                <th width="35%">Detail</th>
                <th width="20%">Paid</th>
                <th width="25%">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr style="background: #d5f5e3;">
                <td>—</td>
                <td class="left bold">Wax Commission Total</td>
                <td>—</td>
                <td class="right bold balance-cell"><?= format_currency($faisal_balance) ?></td>
            </tr>
            <?php foreach ($transactions as $t): 
                if ($t['credit'] > 0):
                    $faisal_balance -= $t['credit'];
                    $faisal_total_paid += $t['credit'];
            ?>
            <tr class="row-pay">
                <td><?= date('d-M-Y', strtotime($t['date'])) ?></td>
                <td class="left"><?= htmlspecialchars($t['design']) ?></td>
                <td class="right credit-cell"><?= format_currency($t['credit']) ?></td>
                <td class="right balance-cell"><?= format_currency($faisal_balance) ?></td>
            </tr>
            <?php endif; endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="totals-footer">
                <td colspan="2" class="right">Totals</td>
                <td class="right"><?= format_currency($faisal_total_paid) ?></td>
                <td class="right"><?= format_currency($faisal_balance) ?></td>
            </tr>
            <tr class="closing-row">
                <td colspan="3" class="right"><strong>Closing Balance / بقایا</strong></td>
                <td class="right"><strong><?= format_currency($faisal_balance) ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>

    <?php else: ?>
    <!-- ======== OTHER WORKERS ======== -->
    <table class="transaction-table">
        <thead>
            <tr>
                <th width="10%">Date</th>
                <th width="16%">Customer</th>
                <th width="20%">Design</th>
                <th width="7%">Qty</th>
                <th width="12%">Amt</th>
                <th width="12%">Debit</th>
                <th width="12%">Credit</th>
                <th width="11%">Bal</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $balance = 0;
            $total_earned = 0;
            $total_paid = 0;

            foreach ($transactions as $t):
                $balance += $t['debit'] - $t['credit'];
                $total_earned += $t['debit'];
                $total_paid += $t['credit'];
                $row_class = $t['type'] == 'PAY' ? 'row-pay' : 'row-comm';
            ?>
            <tr class="<?= $row_class ?>">
                <td><?= date('d-M', strtotime($t['date'])) ?></td>
                <td class="left"><?= htmlspecialchars(mb_strimwidth($t['customer_name'], 0, 18, '…')) ?></td>
                <td class="<?= $t['type'] == 'COMM' ? 'urdu' : 'left' ?>"><?= htmlspecialchars($t['design']) ?></td>
                <td><?= $t['qty'] > 0 ? (fmod(floatval($t['qty']), 1) != 0 ? number_format($t['qty'], 2) : intval($t['qty'])) : '—' ?></td>
                <td class="right"><?= $t['full_amount'] > 0 ? format_currency($t['full_amount']) : '—' ?></td>
                <td class="right debit-cell"><?= $t['debit'] > 0 ? format_currency($t['debit']) : '—' ?></td>
                <td class="right credit-cell"><?= $t['credit'] > 0 ? format_currency($t['credit']) : '—' ?></td>
                <td class="right balance-cell"><?= format_currency($balance) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="totals-footer">
                <td colspan="5" class="right"><strong>Totals (کل)</strong></td>
                <td class="right"><strong><?= format_currency($total_earned) ?></strong></td>
                <td class="right"><strong><?= format_currency($total_paid) ?></strong></td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
            <tr class="closing-row">
                <td colspan="7" class="right"><strong>Closing Balance / بقایا (Payable)</strong></td>
                <td class="right"><strong><?= format_currency($balance) ?></strong></td>
            </tr>
        </tfoot>
    </table>
    <?php endif; ?>
</div>

<?php endforeach; ?>

<script>
async function exportAllAsImages() {
    const exportBtn = document.getElementById('exportBtn');
    const progressContainer = document.getElementById('progressContainer');
    const progressFill = document.getElementById('progressFill');
    const progressText = document.getElementById('progressText');
    
    exportBtn.disabled = true;
    exportBtn.textContent = '⏳ Processing...';
    progressContainer.style.display = 'block';
    
    const statements = document.querySelectorAll('.page-break');
    const totalStatements = statements.length;
    const zip = new JSZip();
    const imgFolder = zip.folder("Worker_Statements_<?= date('Y-m-d', strtotime($start_date)) ?>_to_<?= date('Y-m-d', strtotime($end_date)) ?>");
    
    try {
        for (let i = 0; i < statements.length; i++) {
            const statement = statements[i];
            const workerName = statement.dataset.name || `Statement_${i + 1}`;
            
            const progress = Math.round(((i + 1) / totalStatements) * 100);
            progressFill.style.width = progress + '%';
            progressFill.textContent = progress + '%';
            progressText.textContent = `Processing: ${workerName} (${i + 1}/${totalStatements})`;
            
            await document.fonts.ready;
            
            const canvas = await html2canvas(statement, {
                scale: 2.5,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                logging: false,
                width: statement.scrollWidth,
                windowWidth: 800,
                onclone: function(clonedDoc) {
                    const clonedElement = clonedDoc.querySelector(`#${statement.id}`);
                    if (clonedElement) {
                        clonedElement.style.boxShadow = 'none';
                        clonedElement.style.margin = '0';
                        clonedElement.style.borderRadius = '0';
                        clonedElement.style.maxWidth = '750px';
                    }
                }
            });
            
            const blob = await new Promise(resolve => {
                canvas.toBlob(resolve, 'image/jpeg', 0.95);
            });
            
            const paddedIndex = String(i + 1).padStart(2, '0');
            const fileName = `${paddedIndex}_${workerName}.jpg`;
            imgFolder.file(fileName, blob);
            
            await new Promise(resolve => setTimeout(resolve, 100));
        }
        
        progressText.textContent = 'Creating ZIP file...';
        progressFill.style.width = '100%';
        progressFill.textContent = '100%';
        
        const zipBlob = await zip.generateAsync({
            type: 'blob',
            compression: 'DEFLATE',
            compressionOptions: { level: 6 }
        }, function updateCallback(metadata) {
            progressText.textContent = `Creating ZIP: ${metadata.percent.toFixed(0)}%`;
        });
        
        const dateRange = '<?= date('d-M-Y', strtotime($start_date)) ?>_to_<?= date('d-M-Y', strtotime($end_date)) ?>';
        saveAs(zipBlob, `Worker_Statements_${dateRange}.zip`);
        
        progressText.textContent = '✅ Export completed! Download started.';
        progressFill.style.background = 'linear-gradient(90deg, #27ae60, #2ecc71)';
        
    } catch (error) {
        console.error('Export error:', error);
        progressText.textContent = '❌ Error: ' + error.message;
        progressFill.style.background = '#e74c3c';
    } finally {
        exportBtn.disabled = false;
        exportBtn.textContent = '📸 Export as ZIP';
        
        setTimeout(() => {
            progressContainer.style.display = 'none';
            progressFill.style.width = '0%';
        }, 3000);
    }
}
</script>

</body>
</html>