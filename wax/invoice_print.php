<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$invoice_id = $_GET['id'] ?? 0;
$pdo = getDB();

// Fetch Invoice Header
$stmt = $pdo->prepare("
    SELECT i.*, c.name as customer_name, c.contact 
    FROM wax_invoices i 
    JOIN wax_customers c ON i.customer_id = c.id 
    WHERE i.id = ?
");
$stmt->execute([$invoice_id]);
$invoice = $stmt->fetch();

if (!$invoice) die("Invoice not found");

// Fetch Invoice Items
$stmtItems = $pdo->prepare("
    SELECT ii.*, it.name_urdu, it.category 
    FROM wax_invoice_items ii 
    JOIN wax_items it ON ii.item_id = it.id 
    WHERE ii.invoice_id = ?
");
$stmtItems->execute([$invoice_id]);
$items = $stmtItems->fetchAll();

?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <title>Invoice #<?= $invoice['id'] ?></title>
    <style>
        body { font-family: 'Times New Roman', 'Noto Nastaliq Urdu', serif; margin: 0; padding: 20px; color: #000; background: #f9f9f9; }
        .invoice-box { 
            max-width: 800px; 
            margin: auto; 
            padding: 30px; 
            border: 2px solid #333; 
            background: #fff;
            position: relative;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1); 
            font-size: 16px; 
            line-height: 24px; 
            overflow: hidden;
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            height: 400px;
            background-image: url("https://e7.pngegg.com/pngimages/980/433/png-clipart-jewellery-necklace-jewellery-miscellaneous-gemstone-thumbnail.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.15;
            z-index: 0;
            pointer-events: none;
            filter: grayscale(100%);
        }
        
        .content { position: relative; z-index: 1; }
        
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .company-name { font-size: 28px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .invoice-details { text-align: right; }
        table { width: 100%; line-height: inherit; text-align: left; border-collapse: collapse; }
        table td, table th { padding: 8px; border: 1px solid #000; }
        table th { background: #f0f0f0; font-weight: bold; text-align: center; }
        .total-row td { font-weight: bold; }
        .rtl { direction: rtl; text-align: right; font-family: 'Noto Nastaliq Urdu', serif; }
        .center { text-align: center; }
        .right { text-align: right; }
        
        @media print {
            .invoice-box { border: none; box-shadow: none; padding: 0; }
            .no-print { display: none; }
            body { margin: 0; padding: 0; }
        }
    </style>
    <!-- Google Fonts for Urdu -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<div class="no-print" style="text-align: center; margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Invoice</button>
    <a href="index.php" style="margin-left: 10px;">Back to Dashboard</a>
    </div>
</div>

<div class="invoice-box">
    <div class="watermark"></div>
    <div class="content">
        <div class="header">
            <div>
                <div class="company-name">M.J Casting</div>
                <div>Lahore, Pakistan</div>
                <div>Contact: 0300-1234567</div>
            </div>
            <div class="invoice-details">
            <h2>INVOICE</h2>
            <div>Invoice #: <strong><?= $invoice['id'] ?></strong></div>
            <div>Date: <?= date('d-M-Y', strtotime($invoice['invoice_date'])) ?></div>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <strong>Bill To:</strong><br>
        <?= htmlspecialchars($invoice['customer_name']) ?><br>
        <?= htmlspecialchars($invoice['contact']) ?>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 50%;">Item (تفصیل)</th>
                <th style="width: 15%;">Qty</th>
                <th style="width: 15%;">Rate</th>
                <th style="width: 15%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $count = 1;
            foreach ($items as $item): 
            ?>
            <tr>
                <td class="center"><?= $count++ ?></td>
                <td class="rtl"><?= htmlspecialchars($item['name_urdu']) ?></td>
                <td class="center"><?= $item['qty'] ?></td>
                <td class="right"><?= format_currency($item['rate']) ?></td>
                <td class="right"><?= format_currency($item['amount']) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="4" class="right">Total Amount:</td>
                <td class="right"><?= format_currency($invoice['total_amount']) ?></td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 40px; display: flex; justify-content: space-between;">
        <div style="text-align: center;">
            _______________________<br>
            Receiver Signature
        </div>
        <div style="text-align: center;">
            _______________________<br>
            Authorized Signature
        </div>
    </div>
    </div> <!-- End Content -->
</div>

<script>
    // Auto-print on load if needed, or just let user click
    // window.onload = function() { window.print(); }
</script>

</body>
</html>
