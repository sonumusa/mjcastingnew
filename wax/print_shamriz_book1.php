<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$customer_id = $_GET['customer_id'] ?? '';

$pdo = getDB();

// Get ALL customers
$custSql = "SELECT id, name, contact, address, opening_balance as static_opening_balance FROM wax_customers WHERE 1=1";
$custParams = [];

if ($customer_id) {
    $custSql .= " AND id = ?";
    $custParams[] = $customer_id;
}
$custSql .= " ORDER BY name ASC";

$custStmt = $pdo->prepare($custSql);
$custStmt->execute($custParams);
$allCustomers = $custStmt->fetchAll();

$customers = [];
foreach ($allCustomers as $c) {
    $customers[$c['id']] = [
        'id' => $c['id'],
        'name' => $c['name'],
        'contact' => $c['contact'],
        'address' => $c['address'],
        'static_ob' => $c['static_opening_balance'],
        'items' => [],
        'invoices' => [],
        'bill_total' => 0
    ];
}

// Fetch Transaction Data
$sql = "
    SELECT 
        c.id as customer_id,
        c.name as customer_name,
        c.contact as customer_contact,
        c.address as customer_address,
        c.opening_balance as static_opening_balance,
        i.id as invoice_id,
        i.invoice_date,
        i.total_amount as invoice_total,
        it.name_urdu as design_item_name,
        it_wax.name_urdu as wax_item_name,
        ii.qty,
        ii.rate,
        ii.amount,
        ii.wax_qty,
        ii.wax_rate,
        ii.design_qty,
        ii.design_rate,
        ii.id as item_db_id,
        it.category as item_category
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it ON ii.item_id = it.id
    LEFT JOIN wax_items it_wax ON ii.wax_item_id = it_wax.id
    WHERE i.invoice_date BETWEEN ? AND ?
";

$params = [$start_date, $end_date];

if ($customer_id) {
    $sql .= " AND c.id = ?";
    $params[] = $customer_id;
}

$sql .= " ORDER BY c.name ASC, i.invoice_date ASC, i.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// FIXED: Removed reference usage to prevent data duplication
foreach ($rows as $row) {
    $cid = $row['customer_id'];
    
    if (!isset($customers[$cid])) {
        continue;
    }
    
    $inv_id = $row['invoice_id'];
    
    if (!isset($customers[$cid]['invoices'][$inv_id])) {
        $customers[$cid]['invoices'][$inv_id] = [
            'invoice_id' => $inv_id,
            'invoice_date' => $row['invoice_date'],
            'amount' => 0,
            'lines' => []
        ];
    }
    
    $line_id = $row['item_db_id'];
    
    if (isset($customers[$cid]['invoices'][$inv_id]['lines'][$line_id])) {
        continue;
    }
    
    $customers[$cid]['invoices'][$inv_id]['amount'] += $row['amount'];
    
    $line = [
        'db_id' => $line_id,
        'wax_name' => '', 'wax_qty' => 0, 'wax_rate' => 0,
        'des_name' => '', 'des_qty' => 0, 'des_rate' => 0,
        'amount' => $row['amount']
    ];
    
    if (!empty($row['wax_item_name'])) {
        $line['wax_name'] = $row['wax_item_name'];
        $line['wax_qty'] = $row['wax_qty'];
        $line['wax_rate'] = $row['wax_rate'];
        $line['des_name'] = $row['design_item_name'];
        $line['des_qty'] = $row['design_qty'];
        $line['des_rate'] = $row['design_rate'];
    } else {
        if ($row['item_category'] === 'wax') {
            $line['wax_name'] = $row['design_item_name'];
            $line['wax_qty'] = $row['qty'];
            $line['wax_rate'] = $row['rate'];
        } else {
            $line['des_name'] = $row['design_item_name'];
            $line['des_qty'] = $row['qty'];
            $line['des_rate'] = $row['rate'];
        }
    }
    
    $customers[$cid]['invoices'][$inv_id]['lines'][$line_id] = $line;
}

// FIXED: Build items array without references
foreach (array_keys($customers) as $cid) {
    $customers[$cid]['items'] = [];
    $customers[$cid]['bill_total'] = 0;
    
    if (!empty($customers[$cid]['invoices'])) {
        foreach ($customers[$cid]['invoices'] as $inv) {
            foreach ($inv['lines'] as $line) {
                $customers[$cid]['items'][] = [
                    'invoice_id' => $inv['invoice_id'],
                    'invoice_date' => $inv['invoice_date'],
                    'wax_item_name' => $line['wax_name'] ?: '-',
                    'design_item_name' => $line['des_name'] ?: '-',
                    'wax_qty' => $line['wax_qty'],
                    'wax_rate' => $line['wax_rate'] ?: 0, 
                    'design_qty' => $line['des_qty'],
                    'design_rate' => $line['des_rate'] ?: 0,
                    'amount' => $line['amount']
                ];
            }
            $customers[$cid]['bill_total'] += $inv['amount'];
        }
    }
}

// FIXED: Calculate balances without references
foreach (array_keys($customers) as $cid) {
    $stmtInv = $pdo->prepare("SELECT SUM(total_amount) FROM wax_invoices WHERE customer_id = ? AND invoice_date < ?");
    $stmtInv->execute([$cid, $start_date]);
    $pre_invoices = $stmtInv->fetchColumn() ?: 0;
    
    $stmtPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date < ?");
    $stmtPay->execute([$cid, $start_date]);
    $pre_payments = $stmtPay->fetchColumn() ?: 0;
    
    $opening_balance = $customers[$cid]['static_ob'] + $pre_invoices - $pre_payments;
    $customers[$cid]['opening_balance'] = $opening_balance;
    
    $stmtCurPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date BETWEEN ? AND ?");
    $stmtCurPay->execute([$cid, $start_date, $end_date]);
    $current_payments = $stmtCurPay->fetchColumn() ?: 0;
    $customers[$cid]['current_payments'] = $current_payments;
    
    $customers[$cid]['closing_balance'] = $opening_balance + $customers[$cid]['bill_total'] - $current_payments;
    $customers[$cid]['is_balance_only'] = empty($customers[$cid]['items']) && ($customers[$cid]['opening_balance'] != 0 || $customers[$cid]['closing_balance'] != 0);
}

// Filter customers
$customers = array_filter($customers, function($cust) {
    return !empty($cust['items']) || $cust['opening_balance'] != 0 || $cust['closing_balance'] != 0 || $cust['current_payments'] != 0;
});

// Format functions
function format_amount($num) {
    return number_format(round($num), 0);
}

function format_rate($num) {
    return number_format($num, 2);
}

function format_wax_qty($num) {
    return number_format($num, 3);
}

// Urdu number to words
function number_to_urdu_words($number) {
    $number = abs(round($number));
    
    if ($number == 0) return 'صفر روپے';
    
    $ones = ['', 'ایک', 'دو', 'تین', 'چار', 'پانچ', 'چھ', 'سات', 'آٹھ', 'نو'];
    $tens = ['', '', 'بیس', 'تیس', 'چالیس', 'پچاس', 'ساٹھ', 'ستر', 'اسی', 'نوے'];
    $teens = ['دس', 'گیارہ', 'بارہ', 'تیرہ', 'چودہ', 'پندرہ', 'سولہ', 'سترہ', 'اٹھارہ', 'انیس'];
    
    $words = '';
    
    if ($number >= 10000000) {
        $crore = floor($number / 10000000);
        $words .= number_to_urdu_words($crore) . ' کروڑ ';
        $number %= 10000000;
    }
    
    if ($number >= 100000) {
        $lakh = floor($number / 100000);
        $words .= number_to_urdu_words($lakh) . ' لاکھ ';
        $number %= 100000;
    }
    
    if ($number >= 1000) {
        $thousand = floor($number / 1000);
        $words .= number_to_urdu_words($thousand) . ' ہزار ';
        $number %= 1000;
    }
    
    if ($number >= 100) {
        $hundred = floor($number / 100);
        $words .= $ones[$hundred] . ' سو ';
        $number %= 100;
    }
    
    if ($number >= 20) {
        $words .= $tens[floor($number / 10)] . ' ';
        if ($number % 10 > 0) {
            $words .= $ones[$number % 10] . ' ';
        }
    } elseif ($number >= 10) {
        $words .= $teens[$number - 10] . ' ';
    } elseif ($number > 0) {
        $words .= $ones[$number] . ' ';
    }
    
    return trim($words) . ' ';
}

// Calculate totals for summary page
$total_opening = 0;
$total_debit = 0;
$total_credit = 0;
$total_closing = 0;

foreach ($customers as $c) {
    $total_opening += $c['opening_balance'];
    $total_debit += $c['bill_total'];
    $total_credit += $c['current_payments'];
    $total_closing += $c['closing_balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shamriz Bill Book - Client 2</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap');
        
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background: #e0e0e0; font-size: 14px; }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 0;
            margin: 10mm auto;
            background: #fff;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.3);
            overflow: hidden;
            border: 3px solid #1B5E20;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            opacity: 0.04;
            z-index: 0;
            pointer-events: none;
        }
        .watermark img {
            width: 350px;
            height: auto;
        }
        
        /* NEW DESIGN: Striped Header Pattern */
        .header-stripe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 38mm;
            background: linear-gradient(to right, 
                #1B5E20 0%, 
                #2E7D32 25%, 
                #388E3C 50%, 
                #2E7D32 75%, 
                #1B5E20 100%);
            z-index: 0;
        }
        
        /* Diagonal stripes overlay */
        .header-stripe::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.05) 10px,
                rgba(255,255,255,0.05) 20px
            );
        }
        
        .header-accent-strip {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(to right, #FFA000, #FFB300, #FFC107, #FFB300, #FFA000);
            z-index: 1;
        }

        .content-layer {
            position: relative;
            z-index: 2;
            padding: 8mm 10mm;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5mm;
            padding-bottom: 3mm;
            border-bottom: 3px double #FFA000;
        }
        
        .company-info { text-align: left; }
        .company-info h1 { 
            margin: 0; 
            font-size: 34px; 
            color: #fff;
            font-weight: 900; 
            letter-spacing: 2px;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
            font-family: 'Georgia', serif;
        }
        .company-info .slogan {
            color: #FFD54F;
            font-style: italic;
            font-size: 12px;
            margin: 5px 0;
            display: block;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .company-info p { 
            margin: 2px 0; 
            font-size: 11px; 
            color: rgba(255,255,255,0.95);
            font-weight: 500;
        }
        
        /* Square logo design instead of circle */
        .logo-box {
            width: 80px;
            height: 80px;
            border: 4px solid #FFA000;
            border-radius: 10px;
            padding: 6px;
            background: linear-gradient(135deg, #fff 0%, #f5f5f5 100%);
            box-shadow: 0 6px 20px rgba(0,0,0,0.4);
            transform: rotate(5deg);
        }
        .logo-box img { 
            width: 100%; 
            height: 100%; 
            object-fit: contain; 
            border-radius: 6px;
            transform: rotate(-5deg);
        }

        .customer-section {
            display: flex;
            border: 3px solid #2E7D32;
            margin: 3mm 0;
            border-radius: 0;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .cust-left {
            flex: 1;
            padding: 10px 15px;
            border-right: 3px solid #2E7D32;
            background: linear-gradient(to bottom, #f1f8f4, #fff);
        }
        .cust-right {
            width: 35%;
            background: #f1f8f4;
        }
        .cust-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .cust-table td { padding: 4px 0; vertical-align: top; }
        .cust-label { font-weight: bold; width: 70px; color: #1B5E20; }
        .cust-value { color: #333; }
        
        .invoice-header {
            background: linear-gradient(to right, #2E7D32, #388E3C);
            color: #FFD54F;
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            padding: 10px;
            letter-spacing: 3px;
            text-transform: uppercase;
            border-bottom: 3px solid #FFA000;
        }
        .invoice-details-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .invoice-details-table td { padding: 6px 10px; border-bottom: 1px solid #c8e6c9; color: #333; }
        .invoice-details-table tr:last-child td { border-bottom: none; }

        /* New Balance Box Design - Flat with borders */
        .balance-summary-box {
            background: #fff;
            border: 4px solid #2E7D32;
            border-radius: 0;
            padding: 12px 15px;
            margin: 3mm 0;
            display: flex;
            justify-content: space-around;
            align-items: center;
            box-shadow: inset 0 0 0 2px #FFB300;
        }
        .balance-item {
            text-align: center;
            padding: 10px 15px;
            position: relative;
        }
        .balance-item.opening::after {
            content: '';
            position: absolute;
            right: 0;
            top: 10%;
            height: 80%;
            width: 2px;
            background: #c8e6c9;
        }
        .balance-item.closing::before {
            content: '';
            position: absolute;
            left: 0;
            top: 10%;
            height: 80%;
            width: 2px;
            background: #c8e6c9;
        }
        .balance-label {
            font-size: 11px;
            color: #2E7D32;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
            font-weight: 900;
            font-family: Arial, sans-serif;
        }
        .balance-label-urdu {
            font-family: 'Noto Nastaliq Urdu', Arial, sans-serif;
            font-size: 13px;
            display: block;
            direction: rtl;
            color: #1B5E20;
        }
        .balance-amount {
            font-size: 22px;
            font-weight: bold;
            color: #1B5E20;
            font-family: 'Georgia', serif;
        }
        .balance-amount.positive { color: #D32F2F; }
        .balance-amount.negative { color: #388E3C; }
        .balance-item.closing .balance-amount {
            font-size: 26px;
            color: #FF6F00;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        /* Table with alternating green stripes */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 3px solid #2E7D32;
            margin-bottom: 5px;
            flex: 1;
            font-size: 12px;
        }
        .items-table th {
            background: linear-gradient(to bottom, #2E7D32, #1B5E20);
            color: #FFD54F;
            padding: 8px 4px;
            font-size: 11px;
            border: 1px solid #2E7D32;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-family: Arial, sans-serif;
            font-weight: bold;
        }
        .items-table th.urdu-header {
            font-family: 'Noto Nastaliq Urdu', Arial, sans-serif;
            font-size: 12px;
            direction: rtl;
        }
        .items-table td {
            border: 1px solid #a5d6a7;
            padding: 6px 4px;
            vertical-align: middle;
            text-align: center;
            background: #fff;
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        .items-table td.urdu-cell {
            font-family: 'Noto Nastaliq Urdu', Arial, sans-serif;
            font-size: 12px;
            direction: rtl;
        }
        .items-table tbody tr:nth-child(even) td { background-color: #f1f8f4; }
        .items-table tbody tr:hover td { background-color: #e8f5e9; }

        /* Summary Table - Orange accent */
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            border: 2px solid #2E7D32;
        }
        .summary-table td {
            padding: 8px 12px;
            border: 1px solid #a5d6a7;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        .summary-row { background: #f1f8f4; }
        .summary-row td:first-child { text-align: right; font-weight: 600; color: #1B5E20; }
        .summary-row td:last-child { text-align: right; width: 130px; }
        
        .closing-row {
            background: linear-gradient(to right, #FFA000, #FFB300) !important;
        }
        .closing-row td {
            color: #1B5E20 !important;
            font-weight: bold;
            font-size: 16px;
            padding: 12px;
        }
        
        .payment-row td { color: #388E3C; font-weight: 600; }

        .words-section {
            background: linear-gradient(to right, #f1f8f4, #fff);
            border: 2px solid #a5d6a7;
            border-left: 5px solid #2E7D32;
            border-radius: 0;
            padding: 10px 15px;
            font-size: 16px;
            margin: 8px 0;
            text-align: right;
            direction: rtl;
            font-family: 'Noto Nastaliq Urdu', Arial, sans-serif;
            color: #1B5E20;
            font-weight: bold;
        }

        .bottom-section {
            display: flex;
            justify-content: flex-end;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #2E7D32;
        }
        .signature { text-align: center; width: 180px; }
        .signature img {
            max-width: 120px;
            max-height: 60px;
            object-fit: contain;
        }
        .signature-line { 
            border-top: 3px double #2E7D32; 
            margin-top: 5px; 
            padding-top: 5px; 
            font-size: 13px; 
            font-weight: bold;
            color: #1B5E20;
        }

        .balance-only-page .balance-summary-box {
            margin: 15mm 0;
            padding: 25px;
        }
        .balance-only-page .balance-item {
            padding: 15px 30px;
        }
        .balance-only-page .balance-amount {
            font-size: 32px;
        }
        .balance-only-page .balance-item.closing .balance-amount {
            font-size: 36px;
        }
        .balance-only-message {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
            font-size: 16px;
            background: #f1f8f4;
            border-radius: 0;
            margin: 8mm 0;
            font-family: 'Noto Nastaliq Urdu', Arial, sans-serif;
            border: 2px dashed #a5d6a7;
        }

        /* Summary Page */
        .summary-page-title {
            background: linear-gradient(to right, #1B5E20, #2E7D32, #388E3C, #2E7D32, #1B5E20);
            color: #FFD54F;
            text-align: center;
            padding: 15px;
            border-radius: 0;
            margin: 10px 0;
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 2px;
            border-top: 4px solid #FFA000;
            border-bottom: 4px solid #FFA000;
        }
        
        .summary-period {
            text-align: center;
            margin-bottom: 10px;
            font-size: 14px;
            color: #1B5E20;
            font-weight: 600;
        }
        
        .summary-stats {
            margin-top: 15px;
            padding: 12px;
            background: #f1f8f4;
            border-radius: 0;
            font-size: 14px;
            border: 2px solid #a5d6a7;
            border-left: 5px solid #2E7D32;
        }
        
        .summary-footer-row {
            background: linear-gradient(to right, #FFA000, #FFB300, #FFC107) !important;
        }
        
        .summary-footer-row td {
            font-weight: bold;
            color: #1B5E20 !important;
            padding: 12px !important;
        }

        .no-print {
            text-align: center;
            padding: 15px;
            background: linear-gradient(to right, #1B5E20, #2E7D32);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 4px solid #FFA000;
        }
        .no-print button {
            padding: 12px 25px;
            font-size: 16px;
            cursor: pointer;
            border: none;
            border-radius: 5px;
            margin: 0 10px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .btn-print {
            background: #FFA000;
            color: #1B5E20;
        }
        .btn-export {
            background: #388E3C;
            color: #fff;
        }
        .no-print button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        }
        
        .export-progress {
            display: none;
            color: #FFD54F;
            margin-top: 10px;
            font-size: 14px;
        }
        .export-progress.show { display: block; }

        @media print {
            body { background: none; margin: 0; }
            .page { margin: 0; border: none; box-shadow: none; page-break-after: always; height: 297mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Print All Bills</button>
    <button class="btn-export" onclick="exportAllToZip()">📦 Export All as ZIP</button>
    <div class="export-progress" id="exportProgress">
        <span id="progressText">Preparing...</span>
    </div>
</div>

<?php
$max_rows = 18;
$page_counter = 0;

// ============ SUMMARY PAGE ============
$page_counter++;
?>

<div class="page" id="bill-page-<?= $page_counter ?>" data-customer="Summary_Report">
    <div class="watermark">
        <img src="https://punjabrp.altafhussain-co.com/assets/sign.png" alt="Watermark">
    </div>
    
    <div class="header-stripe">
        <div class="header-accent-strip"></div>
    </div>
    
    <div class="content-layer">
        
        <div class="header-content">
            <div class="company-info">
                <h1>M.J Casting</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Available</span>
                <p>Shop # 882  Ateeq Center,Rang Mahal Lahore</p>
                <p>+92 302-4098908 | +92 322-4773342</p>
            </div>
            <div class="logo-box">
                <img src="https://punjabrp.altafhussain-co.com/assets/2.png" alt="Logo">
            </div>
        </div>

        <div class="summary-page-title">
            📊 CUSTOMERS SUMMARY REPORT | تمام گاہکوں کا خلاصہ
        </div>
        
        <div class="summary-period">
            <strong>Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?>
        </div>

        <table class="items-table" style="font-size: 12px;">
            <thead>
                <tr>
                    <th width="5%">Sr#</th>
                    <th width="35%" style="text-align: left; padding-left: 8px;">Customer Name<br>گاہک کا نام</th>
                    <th width="15%">Opening Bal<br>سابقہ بیلنس</th>
                    <th width="15%">Debit (Bill)<br>ڈیبٹ</th>
                    <th width="15%">Credit (Pay)<br>کریڈٹ</th>
                    <th width="15%">Closing Bal<br>موجودہ بیلنس</th>
                </tr>
            </thead>
            <tbody>
                <?php $sr = 1; foreach ($customers as $c): ?>
                <tr>
                    <td style="font-size: 13px;"><?= $sr++ ?></td>
                    <td style="text-align: left; padding-left: 8px; font-weight: 500; font-size: 13px;"><?= htmlspecialchars($c['name']) ?></td>
                    <td style="color: <?= $c['opening_balance'] >= 0 ? '#D32F2F' : '#388E3C' ?>; font-size: 13px;">
                        <?= format_amount(abs($c['opening_balance'])) ?>
                        <?= $c['opening_balance'] >= 0 ? '' : '' ?>
                    </td>
                    <td style="font-size: 13px;"><?= format_amount($c['bill_total']) ?></td>
                    <td style="color: #388E3C; font-size: 13px;"><?= format_amount($c['current_payments']) ?></td>
                    <td style="font-weight: bold; color: <?= $c['closing_balance'] >= 0 ? '#D32F2F' : '#388E3C' ?>; font-size: 13px;">
                        <?= format_amount(abs($c['closing_balance'])) ?>
                        <?= $c['closing_balance'] >= 0 ? '' : '' ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="summary-footer-row">
                    <td colspan="2" style="text-align: right; padding-right: 10px; font-size: 14px;">
                        <strong>GRAND TOTAL | کل رقم</strong>
                    </td>
                    <td style="font-size: 14px;">
                        <?= format_amount(abs($total_opening)) ?>
                        <?= $total_opening >= 0 ? '' : '' ?>
                    </td>
                    <td style="font-size: 14px;"><?= format_amount($total_debit) ?></td>
                    <td style="font-size: 14px;"><?= format_amount($total_credit) ?></td>
                    <td style="font-size: 15px;">
                        <?= format_amount(abs($total_closing)) ?>
                        <?= $total_closing >= 0 ? '' : '' ?>
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="summary-stats">
            <strong>Total Customers:</strong> <?= count($customers) ?> | 
            <strong>Total Receivable:</strong> <?= format_amount(abs($total_closing)) ?> <?= $total_closing >= 0 ? '(DR)' : '(CR)' ?> |
            <strong>Total Bills:</strong> <?= format_amount($total_debit) ?> |
            <strong>Total Payments:</strong> <?= format_amount($total_credit) ?>
        </div>

        <div class="bottom-section">
            <div class="signature">
                <img src="https://punjabrp.altafhussain-co.com/assets/sign.png" alt="Signature">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>

    </div>
</div>

<?php
// ============ INDIVIDUAL CUSTOMER BILLS ============
foreach ($customers as $cust):
    $items = $cust['items'];
    $total_items = count($items);
    $is_balance_only = $cust['is_balance_only'];
    
    if ($is_balance_only):
        $page_counter++;
?>

<div class="page balance-only-page" id="bill-page-<?= $page_counter ?>" data-customer="<?= htmlspecialchars($cust['name']) ?>">
    <div class="watermark">
        <img src="https://punjabrp.altafhussain-co.com/assets/sign.png" alt="Watermark">
    </div>
    
    <div class="header-stripe">
        <div class="header-accent-strip"></div>
    </div>
    
    <div class="content-layer">
        
        <div class="header-content">
            <div class="company-info">
                <h1>M.J Casting</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Available</span>
                <p>Shop # 01 Bahadara Market, Hingna Street, Rang Mahal Lahore</p>
                <p>+92 324-4980860 | +92 322-3220582</p>
            </div>
            <div class="logo-box">
                <img src="https://punjabrp.altafhussain-co.com/assets/2.png" alt="Logo">
            </div>
        </div>

        <div class="customer-section">
            <div class="cust-left">
                <table class="cust-table">
                    <tr><td class="cust-label">Name:</td><td class="cust-value"><strong style="font-size: 16px;"><?= htmlspecialchars($cust['name']) ?></strong></td></tr>
                    <tr><td class="cust-label">Address:</td><td class="cust-value"><?= htmlspecialchars($cust['address'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="cust-right">
                <div class="invoice-header">Statement</div>
                <table class="invoice-details-table">
                    <tr><td><strong>Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></td></tr>
                    <tr><td><strong>Date:</strong> <?= date('d-M-Y') ?></td></tr>
                </table>
            </div>
        </div>

        <div class="balance-only-message">
            اس مدت میں کوئی لین دین نہیں ہوا - No transactions during this period
        </div>

        <div class="balance-summary-box">
            <div class="balance-item opening">
                <div class="balance-label">
                    <span class="balance-label-urdu">سابقہ بیلنس</span>
                    Opening Balance
                </div>
                <div class="balance-amount <?= $cust['opening_balance'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= format_amount(abs($cust['opening_balance'])) ?>
                    <?= $cust['opening_balance'] >= 0 ? '(DR)' : '(CR)' ?>
                </div>
            </div>
            <div class="balance-item">
                <div class="balance-label">
                    <span class="balance-label-urdu">وصولی</span>
                    Payment
                </div>
                <div class="balance-amount" style="color: #388E3C;">
                    <?= format_amount($cust['current_payments']) ?>
                </div>
            </div>
            <div class="balance-item closing">
                <div class="balance-label">
                    <span class="balance-label-urdu">موجودہ بیلنس</span>
                    Closing Balance
                </div>
                <div class="balance-amount <?= $cust['closing_balance'] >= 0 ? 'positive' : 'negative' ?>">
                    <?= format_amount(abs($cust['closing_balance'])) ?>
                    <?= $cust['closing_balance'] >= 0 ? '(DR)' : '(CR)' ?>
                </div>
            </div>
        </div>

        <div class="words-section">
            <?= number_to_urdu_words($cust['closing_balance']) ?>روپے
        </div>

        <div class="bottom-section">
            <div class="signature">
                <img src="https://punjabrp.altafhussain-co.com/assets/sign.png" alt="Signature">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>

    </div>
</div>

<?php
    else:
        $total_pages = ceil($total_items / $max_rows);
        if ($total_pages == 0) $total_pages = 1;
        $chunks = $items ? array_chunk($items, $max_rows) : [[]];
        
        foreach ($chunks as $page_index => $page_items):
            $page_num = $page_index + 1;
            $is_last_page = ($page_num == $total_pages);
            $page_counter++;
?>

<div class="page" id="bill-page-<?= $page_counter ?>" data-customer="<?= htmlspecialchars($cust['name']) ?>">
    <div class="watermark">
        <img src="https://punjabrp.altafhussain-co.com/assets/sign.png" alt="Watermark">
    </div>
    
    <div class="header-stripe">
        <div class="header-accent-strip"></div>
    </div>
    
    <div class="content-layer">
        
        <div class="header-content">
            <div class="company-info">
                <h1>M.J Casting</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Available</span>
                <p>Shop # 01 Bahadara Market, Hingna Street, Rang Mahal Lahore</p>
                <p>+92 324-4980860 | +92 322-3220582</p>
            </div>
            <div class="logo-box">
                <img src="https://punjabrp.altafhussain-co.com/assets/2.png" alt="Logo">
            </div>
        </div>

        <div class="customer-section">
            <div class="cust-left">
                <table class="cust-table">
                    <tr><td class="cust-label">Name:</td><td class="cust-value"><strong style="font-size: 16px;"><?= htmlspecialchars($cust['name']) ?></strong></td></tr>
                    <tr><td class="cust-label">Address:</td><td class="cust-value"><?= htmlspecialchars($cust['address'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="cust-right">
                <div class="invoice-header">Invoice</div>
                <table class="invoice-details-table">
                    <tr><td><strong>Page:</strong> <?= $page_num ?> / <?= $total_pages ?></td></tr>
                    <tr><td><strong>Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> - <?= date('d-M-Y', strtotime($end_date)) ?></td></tr>
                </table>
            </div>
        </div>

        <?php if ($page_num == 1): ?>
        <div class="balance-summary-box" style="padding: 8px 12px;">
            <div class="balance-item opening" style="padding: 5px 15px;">
                <div class="balance-label">
                     سابقہ بیلنس | Opening Balance
                </div>
                <div class="balance-amount <?= $cust['opening_balance'] >= 0 ? 'positive' : 'negative' ?>" style="font-size: 20px;">
                    <?= format_amount(abs($cust['opening_balance'])) ?>
                    <?= $cust['opening_balance'] >= 0 ? '(DR)' : '(CR)' ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%">Date</th>
                    <th width="5%">Bill#</th>
                    <th width="14%">Wax</th>
                    <th width="14%">Design</th>
                    <th width="7%">Wax Wt</th>
                    <th width="7%">Rate</th>
                    <th width="9%">Wax Amt</th>
                    <th width="6%">Qty</th>
                    <th width="7%">Rate</th>
                    <th width="9%">Des Amt</th>
                    <th width="10%">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($page_items as $item): 
                    $wQty = $item['wax_qty'] > 0 ? format_wax_qty((float)$item['wax_qty']) : '-';
                    $dQty = $item['design_qty'] > 0 ? round($item['design_qty']) : '-';
                    $wRate = $item['wax_rate'] > 0 ? format_rate($item['wax_rate']) : '-';
                    $dRate = $item['design_rate'] > 0 ? format_rate($item['design_rate']) : '-';
                    
                    $wAmt = round($item['wax_qty'] * $item['wax_rate']);
                    $dAmt = round($item['design_qty'] * $item['design_rate']);
                    
                    $wAmtStr = $wAmt > 0 ? format_amount($wAmt) : '-';
                    $dAmtStr = $dAmt > 0 ? format_amount($dAmt) : '-';

                    $waxName = $item['wax_item_name'] ?: '-';
                    $desName = $item['design_item_name'] ?: '-';
                ?>
                <tr>
                    <td><?= date('d-M', strtotime($item['invoice_date'])) ?></td>
                    <td><?= $item['invoice_id'] ?></td>
                    <td class="urdu-cell"><?= htmlspecialchars($waxName) ?></td>
                    <td class="urdu-cell"><?= htmlspecialchars($desName) ?></td>
                    <td><?= $wQty ?></td>
                    <td><?= $wRate ?></td>
                    <td><?= $wAmtStr ?></td>
                    <td><?= $dQty ?></td>
                    <td><?= $dRate ?></td>
                    <td><?= $dAmtStr ?></td>
                    <td style="font-weight: bold; color: #1B5E20;"><?= format_amount($item['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php 
                $used_rows = count($page_items);
                $remaining = $max_rows - $used_rows;
                for($i=0; $i<$remaining; $i++): ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <?php endfor; ?>
            </tbody>
        </table>

<?php if ($is_last_page): ?>
        <table class="summary-table">
            <tr class="summary-row">
                <td>Bill Total (اس بل کی رقم)</td>
                <td><?= format_amount($cust['bill_total']) ?></td>
            </tr>
            <tr class="summary-row">
                <td>Opening Balance (سابقہ بیلنس) (+)</td>
                <td><?= format_amount($cust['opening_balance']) ?></td>
            </tr>
            <tr class="summary-row payment-row">
                <td>Payment Received (وصولی) (-)</td>
                <td style="color: #388E3C;">- <?= format_amount($cust['current_payments']) ?></td>
            </tr>
            <tr class="closing-row" style="text-align:right">
                <td>موجودہ بیلنس | CLOSING BALANCE</td>
                <td><?= format_amount($cust['closing_balance']) ?> <?= $cust['closing_balance'] >= 0 ? '(DR)' : '(CR)' ?></td>
            </tr>
        </table>

        <div class="words-section">
            <?= number_to_urdu_words($cust['closing_balance']) ?>روپے
        </div>

        <div class="bottom-section">
            <div class="signature">
                <img src="https://punjabrp.altafhussain-co.com/assets/sign.png" alt="Signature">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: right; padding: 8px; font-style: italic; color: #1B5E20; font-size: 12px;">
            Continued on next page...
        </div>
        <?php endif; ?>

    </div>
</div>

<?php 
        endforeach;
    endif;
endforeach; 
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
document.fonts.ready.then(function() {
    console.log('Fonts loaded');
});

async function exportAllToZip() {
    const pages = document.querySelectorAll('.page');
    const progress = document.getElementById('exportProgress');
    const progressText = document.getElementById('progressText');
    const zip = new JSZip();
    
    progress.classList.add('show');
    progressText.textContent = 'Loading fonts...';
    
    await document.fonts.ready;
    await new Promise(resolve => setTimeout(resolve, 500));
    
    progressText.textContent = 'Preparing export...';
    
    for (let i = 0; i < pages.length; i++) {
        progressText.textContent = `Exporting ${i + 1} of ${pages.length}...`;
        
        const page = pages[i];
        const customerName = page.dataset.customer || 'Customer';
        const safeCustomerName = customerName.replace(/[^a-zA-Z0-9\s_]/g, '').replace(/\s+/g, '_').substring(0, 50);
        
        let filename;
        if (i === 0) {
            filename = `00_Summary_Report.jpg`;
        } else {
            filename = `${String(i).padStart(2, '0')}_Bill_${safeCustomerName}.jpg`;
        }
        
        try {
            const clone = page.cloneNode(true);
            clone.style.position = 'absolute';
            clone.style.left = '-9999px';
            clone.style.top = '0';
            clone.style.margin = '0';
            clone.style.boxShadow = 'none';
            document.body.appendChild(clone);
            
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const canvas = await html2canvas(clone, {
                scale: 2,
                useCORS: true,
                allowTaint: true,
                backgroundColor: '#ffffff',
                width: clone.scrollWidth,
                height: clone.scrollHeight,
                logging: false,
                onclone: function(clonedDoc) {
                    const style = clonedDoc.createElement('style');
                    style.textContent = `
                        @import url('https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap');
                        .urdu-cell, .urdu-header, .balance-label-urdu, .words-section, .balance-only-message {
                            font-family: 'Noto Nastaliq Urdu', Arial, sans-serif !important;
                        }
                    `;
                    clonedDoc.head.appendChild(style);
                }
            });
            
            document.body.removeChild(clone);
            
            const blob = await new Promise(resolve => {
                canvas.toBlob(resolve, 'image/jpeg', 1.0);
            });
            
            zip.file(filename, blob);
            
        } catch (error) {
            console.error(`Error exporting page ${i + 1}:`, error);
        }
        
        await new Promise(resolve => setTimeout(resolve, 200));
    }
    
    progressText.textContent = 'Creating ZIP file...';
    
    try {
        const zipBlob = await zip.generateAsync({ 
            type: 'blob',
            compression: 'DEFLATE',
            compressionOptions: { level: 6 }
        });
        
        const today = new Date();
        const dateStr = today.toISOString().split('T')[0];
        const zipFilename = `Bills_Client2_${dateStr}.zip`;
        
        saveAs(zipBlob, zipFilename);
        
        progressText.textContent = 'Download started!';
        setTimeout(() => {
            progress.classList.remove('show');
        }, 2000);
        
    } catch (error) {
        console.error('Error creating ZIP:', error);
        progressText.textContent = 'Error creating ZIP file';
        setTimeout(() => {
            progress.classList.remove('show');
        }, 3000);
    }
}
</script>

</body>
</html>