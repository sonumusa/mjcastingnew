<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$customer_id = $_GET['customer_id'] ?? '';

$pdo = getDB();

// Fetch Data
$sql = "
    SELECT 
        c.id as customer_id,
        c.name as customer_name,
        c.contact as customer_contact,
        c.address as customer_address,
        c.opening_balance as static_opening_balance,
        i.id as invoice_id,
        i.invoice_date,
        it.name_urdu,
        ii.qty,
        ii.rate,
        ii.amount
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it ON ii.item_id = it.id
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

// Group by Customer
$customers = [];
foreach ($rows as $row) {
    $cid = $row['customer_id'];
    if (!isset($customers[$cid])) {
        $customers[$cid] = [
            'id' => $row['customer_id'],
            'name' => $row['customer_name'],
            'contact' => $row['customer_contact'],
            'address' => $row['customer_address'],
            'static_ob' => $row['static_opening_balance'],
            'items' => [],
            'bill_total' => 0
        ];
    }
    if (!isset($customers[$cid]['invoices'][$row['invoice_id']])) {
        $customers[$cid]['invoices'][$row['invoice_id']] = ['amount' => 0]; // Initialize
    }
    $inv = &$customers[$cid]['invoices'][$row['invoice_id']];
    $inv['amount'] += $row['amount'];
    
    $row['description'] = $row['name_urdu']; 
    $customers[$cid]['items'][] = $row;
    $customers[$cid]['bill_total'] += $row['amount'];
}

// Calculate Financials for each customer
foreach ($customers as $cid => &$cust) {
    // 1. Previous Dues (Before Start Date)
    //    = Static OB + Invoices (Before Start) - Payments (Before Start)
    
    // Invoices before start
    $stmtInv = $pdo->prepare("SELECT SUM(total_amount) FROM wax_invoices WHERE customer_id = ? AND invoice_date < ?");
    $stmtInv->execute([$cid, $start_date]);
    $pre_invoices = $stmtInv->fetchColumn() ?: 0;
    
    // Payments before start
    $stmtPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date < ?");
    $stmtPay->execute([$cid, $start_date]);
    $pre_payments = $stmtPay->fetchColumn() ?: 0;
    
    $opening_balance = $cust['static_ob'] + $pre_invoices - $pre_payments;
    $cust['opening_balance'] = $opening_balance;
    
    // 2. Payments During Period
    $stmtCurPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date BETWEEN ? AND ?");
    $stmtCurPay->execute([$cid, $start_date, $end_date]);
    $current_payments = $stmtCurPay->fetchColumn() ?: 0;
    $cust['current_payments'] = $current_payments;
    
    // 3. Closing Balance
    //    = Opening + Current Bill - Current Payments
    $cust['closing_balance'] = $opening_balance + $cust['bill_total'] - $current_payments;
}
unset($cust); // Break reference

function number_to_words_simple($number) {
    $hyphen      = '-';
    $conjunction = ' and ';
    $separator   = ', ';
    $dictionary  = array(
        0 => 'zero', 1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
        6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
        11 => 'eleven', 12 => 'twelve', 13 => 'thirteen', 14 => 'fourteen',
        15 => 'fifteen', 16 => 'sixteen', 17 => 'seventeen', 18 => 'eighteen',
        19 => 'nineteen', 20 => 'twenty', 30 => 'thirty', 40 => 'forty',
        50 => 'fifty', 60 => 'sixty', 70 => 'seventy', 80 => 'eighty',
        90 => 'ninety', 100 => 'hundred', 1000 => 'thousand', 1000000 => 'million'
    );
    
    if (!is_numeric($number)) return false;
    $string = $fraction = null;
    if (strpos($number, '.') !== false) {
        list($number, $fraction) = explode('.', $number);
    }
    
    // Fix Deprecated Float to Int conversion
    $number = (int)$number;

    switch (true) {
        case $number < 21: $string = $dictionary[$number]; break;
        case $number < 100:
            $tens = ((int) ($number / 10)) * 10;
            $units = $number % 10;
            $string = $dictionary[$tens];
            if ($units) $string .= $hyphen . $dictionary[$units];
            break;
        case $number < 1000:
            $hundreds = (int)($number / 100);
            $remainder = $number % 100;
            $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
            if ($remainder) $string .= $conjunction . number_to_words_simple($remainder);
            break;
        default:
            $baseUnit = (int) pow(1000, floor(log($number, 1000)));
            $numBaseUnits = (int) ($number / $baseUnit);
            $remainder = $number % $baseUnit;
            $string = number_to_words_simple($numBaseUnits) . ' ' . $dictionary[$baseUnit];
            if ($remainder) {
                $string .= $remainder < 100 ? $conjunction : $separator;
                $string .= number_to_words_simple($remainder);
            }
            break;
    }
    return ucfirst($string);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill Book</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4; margin: 0; }
        body { font-family: 'Times New Roman', serif; margin: 0; padding: 0; background: #ccc; }
        
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 0; /* Remove padding to let header touch edges */
            margin: 10mm auto;
            background: #fff;
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* Header Curve */
        .header-curve {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 30mm;
            background: linear-gradient(to bottom, #8b4513 80%, #a0522d 100%);
            border-bottom-left-radius: 50% 20%;
            border-bottom-right-radius: 50% 20%;
            z-index: 0;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }

        .content-layer {
            position: relative;
            z-index: 1;
            padding: 10mm;
            padding-top: 35mm; /* Space for curve */
            height: 100%;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 5mm;
        }
        
        .company-info { text-align: left; }
        .company-info h1 { 
            margin: 0; 
            font-size: 36px; 
            color: #8b4513; 
            text-transform: uppercase; 
            font-weight: bold; 
            letter-spacing: 1px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        .company-info .slogan {
            color: #d2691e;
            font-style: italic;
            font-size: 14px;
            margin-bottom: 5px;
            display: block;
        }
        .company-info p { margin: 2px 0; font-size: 13px; color: #333; }
        
        .logo-box {
            width: 100px;
            height: 100px;
            border: 2px solid #8b4513;
            padding: 2px;
            background: #fff;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            transform: rotate(2deg);
        }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; }

        /* Customer Box */
        .customer-section {
            display: flex;
            border: 2px solid #8b4513;
            margin-bottom: 2mm;
            border-radius: 4px;
            overflow: hidden;
        }
        .cust-left {
            flex: 1;
            padding: 5px;
            border-right: 1px solid #8b4513;
        }
        .cust-right {
            width: 35%;
        }
        .cust-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .cust-table td { padding: 4px; vertical-align: top; }
        .cust-label { font-weight: bold; width: 80px; color: #8b4513; }
        
        .invoice-header {
            background: #fff;
            color: #d2691e;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            padding: 5px;
            border-bottom: 1px solid #8b4513;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .invoice-details-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .invoice-details-table td { padding: 4px; border-bottom: 1px solid #eee; }
        .invoice-details-table tr:last-child td { border-bottom: none; }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border: 2px solid #8b4513;
            margin-bottom: 5px;
            flex: 1;
        }
        .items-table th {
            background: #fff;
            color: #000;
            padding: 8px;
            font-size: 13px;
            border: 1px solid #8b4513;
            border-bottom: 2px solid #8b4513;
            text-transform: uppercase;
        }
        .items-table td {
            border-left: 1px solid #8b4513;
            border-right: 1px solid #8b4513;
            padding: 6px;
            font-size: 13px;
            vertical-align: middle;
        }
        .items-table tr:nth-child(even) { background-color: transparent; } 

        .watermark {
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            background-image: url("https://e7.pngegg.com/pngimages/980/433/png-clipart-jewellery-necklace-jewellery-miscellaneous-gemstone-thumbnail.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.1;
            z-index: 0;
            pointer-events: none;
            filter: grayscale(100%);
        }

        /* Footer */
        .footer-total-row {
            border-top: 2px solid #8b4513;
            border-bottom: 2px solid #8b4513;
            padding: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: bold;
            background: #fff;
        }
        
        .words-section {
            border-bottom: 1px solid #8b4513;
            padding: 5px 0;
            font-size: 13px;
            margin-bottom: 10px;
        }

        .bottom-section {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .terms ul { margin: 0; padding-left: 20px; font-size: 11px; }
        .signature { text-align: center; width: 150px; }
        .signature-line { border-top: 1px solid #000; margin-top: 30px; padding-top: 5px; font-size: 12px; font-weight: bold; }

        @media print {
            body { background: none; margin: 0; }
            .page { margin: 0; border: none; box-shadow: none; page-break-after: always; height: 297mm; }
        }
    </style>
</head>
<body>

<div class="no-print" style="text-align:center; padding: 10px; background: #eee;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 1.2em; cursor: pointer;">Print Bill Book</button>
</div>

<div class="page">
    <div class="header-curve"></div>
    <div class="watermark"></div>
    <div class="content-layer">
        
        <div class="header-content">
            <div class="company-info">
                <h1>M.J Casting</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Avaiable</span>
                <p>Shop # 01 Bahadara Market,Hingna Street,Rang Mahal Lahore</p>
                <p>+92 324-4980860 , +92 322-3220582</p>
            </div>
            <div class="logo-box">
                <img src="https://e7.pngegg.com/pngimages/980/433/png-clipart-jewellery-necklace-jewellery-miscellaneous-gemstone-thumbnail.png" alt="Logo">
            </div>
        </div>
        
        <h2 style="text-align: center; color: #8b4513; text-transform: uppercase; border-bottom: 2px solid #8b4513; padding-bottom: 5px;">Customer Summary Report</h2>
        <div style="text-align: center; margin-bottom: 20px;">
            <strong>Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="5%">SR</th>
                    <th width="35%">Party Name</th>
                    <th width="15%">Opening Balance</th>
                    <th width="15%">Total Debit</th>
                    <th width="15%">Payment</th>
                    <th width="15%">Closing Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $summary_sr = 1;
                $total_opening = 0;
                $total_debit = 0;
                $total_payment = 0;
                $total_closing = 0;

                foreach ($customers as $cust): 
                    // Filter: Exclude if all zero
                    if ($cust['opening_balance'] == 0 && $cust['bill_total'] == 0 && $cust['current_payments'] == 0 && $cust['closing_balance'] == 0) continue;
                    
                    $total_opening += $cust['opening_balance'];
                    $total_debit += $cust['bill_total'];
                    $total_payment += $cust['current_payments'];
                    $total_closing += $cust['closing_balance'];
                ?>
                <tr>
                    <td style="text-align: center; border-right: 1px solid #8b4513;"><?= $summary_sr++ ?></td>
                    <td style="border-right: 1px solid #8b4513; font-weight: bold;"><?= htmlspecialchars($cust['name']) ?></td>
                    <td style="text-align: right; border-right: 1px solid #8b4513;"><?= format_currency($cust['opening_balance']) ?></td>
                    <td style="text-align: right; border-right: 1px solid #8b4513;"><?= format_currency($cust['bill_total']) ?></td>
                    <td style="text-align: right; border-right: 1px solid #8b4513;"><?= format_currency($cust['current_payments']) ?></td>
                    <td style="text-align: right;"><?= format_currency($cust['closing_balance']) ?></td>
                </tr>
                <?php endforeach; ?>
                
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td colspan="2" style="text-align: right; border-right: 1px solid #8b4513; padding: 8px;">Grand Total</td>
                    <td style="text-align: right; border-right: 1px solid #8b4513;"><?= format_currency($total_opening) ?></td>
                    <td style="text-align: right; border-right: 1px solid #8b4513;"><?= format_currency($total_debit) ?></td>
                    <td style="text-align: right; border-right: 1px solid #8b4513;"><?= format_currency($total_payment) ?></td>
                    <td style="text-align: right;"><?= format_currency($total_closing) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php
foreach ($customers as $cust):
    // Standard Bill Generation
    $items = $cust['items'];
    
    $rows_p1 = 20;     // Fits comfortably with reduced padding
    $rows_other = 35;  // Fits comfortably on full pages
    
    // Filter out zero balance customers from printing individual bills too
    if ($cust['opening_balance'] == 0 && $cust['bill_total'] == 0 && $cust['current_payments'] == 0 && $cust['closing_balance'] == 0) continue;
    
    $total_items = count($items); // Define total_items here
    $chunks = [];
    if ($total_items == 0) {
        $chunks[] = [];
    } else {
        $offset = 0;
        $page_idx = 0;
        while ($offset < $total_items) {
            $limit = ($page_idx == 0) ? $rows_p1 : $rows_other;
            $chunks[] = array_slice($items, $offset, $limit);
            $offset += $limit;
            $page_idx++;
        }
    }
    
    $total_pages = count($chunks);
    $sr_counter = 1; // Reset SR for each customer
    
    foreach ($chunks as $page_index => $page_items):
        $page_num = $page_index + 1;
        $is_last_page = ($page_num == $total_pages);
        $current_page_limit = ($page_num == 1) ? $rows_p1 : $rows_other;
?>

<div class="page">
    <div class="header-curve"></div>
    <div class="watermark"></div>
    <div class="content-layer">
        
        <?php if ($page_num == 1): ?>
        <div class="header-content">
            <div class="company-info">
                <h1>M.J Casting</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Avaiable</span>
                <p>Shop # 01 Bahadara Market,Hingna Street,Rang Mahal Lahore</p>
                <p>+92 324-4980860 , +92 322-3220582</p>
            </div>
            <div class="logo-box">
                <img src="https://e7.pngegg.com/pngimages/980/433/png-clipart-jewellery-necklace-jewellery-miscellaneous-gemstone-thumbnail.png" alt="Logo">
            </div>
        </div>

        <div class="customer-section">
            <div class="cust-left">
                <table class="cust-table">
                    <tr><td class="cust-label">Name :</td><td><strong><?= htmlspecialchars($cust['name']) ?></strong></td></tr>
                    <tr><td class="cust-label">Address :</td><td><?= htmlspecialchars($cust['address'] ?? '') ?></td></tr>
                </table>
            </div>
            <div class="cust-right">
                <div class="invoice-header">INVOICE</div>
                <table class="invoice-details-table">
                    <tr><td>Invoice No.: <strong><?= $cust['id'] ?>-<?= date('m') ?></strong></td></tr>
                    <tr><td><strong>Invoice Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></td></tr>
                </table>
            </div>
        </div>
        <?php else: ?>
            <div style="margin-bottom: 10px; border-bottom: 2px solid #8b4513; padding-bottom: 5px; font-size: 12px;">
                <strong><?= htmlspecialchars($cust['name']) ?></strong> (Cont.) - Page <?= $page_num ?>
            </div>
        <?php endif; ?>

        <table class="items-table">
            <thead>
                <tr>
                    <th width="8%">Sl.No.</th>
                    <th width="52%">Description</th>
                    <th width="10%">Qty (Grams)</th>
                    <th width="15%">Rate</th>
                    <th width="15%">Amount</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <?php if ($page_num == 1): ?>
                <tr style="background-color: #f9f9f9;">
                    <td style="border-right: 1px solid #8b4513;"></td>
                    <td style="font-weight: bold; border-right: 1px solid #8b4513;">سابقہ بیلنس</td>
                    <td style="border-right: 1px solid #8b4513;"></td>
                    <td style="border-right: 1px solid #8b4513;"></td>
                    <td style="text-align: right; font-weight: bold;"><?= format_currency($cust['opening_balance']) ?></td>
                </tr>
                <?php endif; ?>

                <?php foreach ($page_items as $item): ?>
                <tr>
                    <td style="text-align: center; border-right: 1px solid #8b4513;"><?= $sr_counter++ ?></td>
                    <td class="urdu" style="border-right: 1px solid #8b4513;"><?= htmlspecialchars($item['name_urdu']) ?></td>
                    <td style="text-align: center; border-right: 1px solid #8b4513;"><?= (float)$item['qty'] ?></td>
                    <td style="text-align: center; border-right: 1px solid #8b4513;"><?= format_currency($item['rate']) ?></td>
                    <td style="text-align: right;"><?= format_currency($item['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                
                <?php 
                // Adjust rows for opening balance
                $used_rows = count($page_items) + ($page_num == 1 ? 1 : 0);
                $remaining_rows = $current_page_limit - $used_rows;
                for($i=0; $i<$remaining_rows; $i++): 
                ?>
                <tr>
                    <td style="color: transparent; border-right: 1px solid #8b4513;">.</td>
                    <td style="border-right: 1px solid #8b4513;"></td>
                    <td style="border-right: 1px solid #8b4513;"></td>
                    <td style="border-right: 1px solid #8b4513;"></td>
                    <td></td>
                </tr>
                <?php endfor; ?>
                
                <tr>
                    <td colspan="4" style="text-align: right; font-weight: bold; border-top: 2px solid #8b4513; padding: 8px;">Total Bill</td>
                    <td style="text-align: right; font-weight: bold; border-top: 2px solid #8b4513; padding: 8px;"><?= $is_last_page ? format_currency($cust['bill_total']) : 'Continued...' ?></td>
                </tr>
                <?php if ($is_last_page): ?>
                <tr>
                    <td colspan="4" style="text-align: right; border-top: 1px solid #8b4513; padding: 4px;">سابقہ بیلنس (+)</td>
                    <td style="text-align: right; border-top: 1px solid #8b4513; padding: 4px;"><?= format_currency($cust['opening_balance']) ?></td>
                </tr>
                <tr>
                    <td colspan="4" style="text-align: right; border-top: 1px solid #8b4513; padding: 4px;">Payment Received (-)</td>
                    <td style="text-align: right; border-top: 1px solid #8b4513; padding: 4px; color: red;"><?= format_currency($cust['current_payments']) ?></td>
                </tr>
                <tr style="background-color: #e9ecef; font-size: 1.1em;">
                    <td colspan="4" style="text-align: right; font-weight: bold; border-top: 2px solid #8b4513; padding: 8px;">موجودہ بیلنس</td>
                    <td style="text-align: right; font-weight: bold; border-top: 2px solid #8b4513; padding: 8px;"><?= format_currency($cust['closing_balance']) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($is_last_page): ?>
        <div class="words-section">
            <!-- Words removed -->
        </div>

        <div class="bottom-section">
            <div class="terms">
                <!-- Terms removed -->
            </div>
            <div class="signature">
                <div class="signature-line">Signature</div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php 
    endforeach;
endforeach; 
?>

</body>
</html>
