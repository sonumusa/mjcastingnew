<?php

require_once __DIR__ . '/../config.php';

requireAuth();

require_once __DIR__ . '/../functions/gold_calculations.php';

$id = (int) query('id', 0);

$db = getDB();

$stmt = $db->prepare("SELECT i.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.city as customer_city 
                      FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect('invoices/index.php');
}

$stmt = $db->prepare("SELECT * FROM invoice_receives WHERE invoice_id = ?");
$stmt->execute([$id]);
$receives = $stmt->fetchAll();

// Fetch previous 2 invoices for this customer
$stmt = $db->prepare("SELECT i.*, c.name as customer_name 
                      FROM invoices i 
                      LEFT JOIN customers c ON c.id = i.customer_id 
                      WHERE i.customer_id = ? AND i.id < ? 
                      ORDER BY i.id DESC 
                      LIMIT 2");
$stmt->execute([$invoice['customer_id'], $id]);
$prevInvoices = $stmt->fetchAll();


function formatRattiPrint($value): string {
    $v = (float)$value;
    return abs($v - round($v)) < 0.0001 ? number_format($v, 0) : number_format($v, 1);
}

$workshopName = getSetting('workshop_name', 'M.J Casting');
$workshopNameUrdu = getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ');
$workshopAddress = getSetting('address', '');
$workshopPhone = getSetting('phone', '');
$workshopPhone2 = getSetting('phone2', '');
$workshopPhone3 = getSetting('phone3', '');

$hideSidebar = true;
$pageTitle = 'Print Receipt';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?= htmlspecialchars($invoice['invoice_no']) ?></title>
    <!-- Changed from JetBrains Mono to Roboto Mono to remove the dot in zero -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet">
    <style>
        @page { size: 145mm 200mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { 
            height: 100%; 
            overflow: hidden; 
        }

        body { 
            margin: 0; 
            padding: 0; 
            background: #f5f5f5; 
            color: #000; 
            font-family: 'Segoe UI', Arial, sans-serif; 
            -webkit-print-color-adjust: exact; 
            font-size: 10pt;
            line-height: 1.2;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .print-container { 
            width: 145mm; 
            height: 200mm;
            background: #fff;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.15);
        }

/* VARIANT 1 PREMIUM HEADER */
.premium-header-v1 {
    height: 70px;
    display: flex;
    align-items: center;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
    border: 2px solid #c79b2b;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 0;
    flex-shrink: 0;
    position: relative;
}

/* Left Section */
.header-left {
    width: 110px;
    height: 80%;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-right: 3px solid #c79b2b;
    position: relative;
}

.header-left::after {
    content: '';
    position: absolute;
    right: -20px;
    top: 0;
    bottom: 0;
    width: 30px;
    background: #fff;
    clip-path: polygon(0 0, 100% 50%, 0 100%);
}

.logo-hexagon {
    width: 55px;
    height: 55px;
    border: 3px solid #d9b14a;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 5px;
    background: rgba(217, 177, 74, 0.1);
}

.logo-mj {
    font-size: 11px;
    font-weight: 800;
    color: #d9b14a;
    letter-spacing: 1px;
}

.logo-text {
    font-size: 7px;
    color: #d9b14a;
    font-weight: 700;
    letter-spacing: 1.5px;
    text-transform: uppercase;
}

/* Center Section */
.header-center {
    flex: 1;
    background: linear-gradient(90deg, #fff 0%, #fefefe 100%);
    height: 100%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 0 20px;
    position: relative;
}

.header-center::before {
    content: '';
    position: absolute;
    left: -15px;
    top: 0;
    bottom: 0;
    width: 30px;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
    clip-path: polygon(0 0, 100% 50%, 0 100%);
}

.header-center::after {
    content: '';
    position: absolute;
    right: -15px;
    top: 0;
    bottom: 0;
    width: 30px;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
    clip-path: polygon(100% 0, 0 50%, 100% 100%);
}

.company-name-en {
    font-size: 20pt;
    font-weight: 800;
    color: #0f2f4f;
    letter-spacing: 2px;
    margin-bottom: 0px;
}

.company-tagline {
    font-size: 10pt;
    font-weight: 700;
    color: #b78916;
    letter-spacing: 1.5px;
    margin-bottom: 0px;
    text-transform: uppercase;
}

.contact-info {
    display: flex;
    gap: 0px;
    align-items: center;
    flex-wrap: wrap;
    justify-content: center;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 5px;
    font-size: 6pt;
    font-weight: 600;
    color: #222;
}

.contact-item .icon {
    font-size: 7pt;
    color: #0f2f4f;
}

/* Right Section */
.header-right {
    width: 160px;
    height: 100%;
    background: linear-gradient(135deg, #143f67 0%, #0f2f4f 100%);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-left: 3px solid #c79b2b;
    position: relative;
    padding: 5px;
}

.header-right::before {
    content: '';
    position: absolute;
    left: -20px;
    top: 0;
    bottom: 0;
    width: 30px;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
    clip-path: polygon(100% 0, 0 50%, 100% 100%);
}

.urdu-main {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 14pt;
    font-weight: 700;
    color: #d9b14a;
    line-height: 1.8;
    margin-bottom: 2px;
}

.urdu-sub {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 8pt;
    color: #d9b14a;
    line-height: 1.6;
    margin-bottom: 3px;
}

.urdu-divider {
    color: #d9b14a;
    font-size: 8pt;
    margin-top: 2px;
}

/* Adjust print container padding */
.print-container {
    width: 145mm;
    height: 200mm;
    background: #fff;
    padding: 3mm;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0,0,0,0.15);
}

        /* Meta row */
        .meta-row {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            border-top: none;
            padding: 3px 20px;
            font-size: 8pt;
            background: #fafafa;
            flex-shrink: 0;
        }
        .meta-item { text-align: center; }
        .meta-item .label {
            font-size: 7pt;
            color: #666;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 2.2;
        }
        .meta-item .value {
            font-weight: 700;
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 8pt;
        }

        /* Party info */
        .party-row {
            border: 1px solid #000;
            border-top: none;
            padding: 3px 6px;
            font-size: 9pt;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        .party-name { font-weight: 700; font-size: 10pt; }

        /* Main table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            font-size: 9pt;
            table-layout: fixed;
            flex-shrink: 0;
        }
        .main-table th {
            background: #e0e0e0;
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-weight: 700;
            font-size: 8pt;
        }
        .main-table th.urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 9pt;
            line-height: 2.2;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 3px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .main-table td.desc {
            text-align: right;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 9pt;
            padding-right: 4px;
            line-height: 2.2;
            width: 45%;
        }
        .main-table td.number {
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-weight: 600;
            font-size: 9pt;
        }
        .main-table td.remark {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 8pt;
        }
        .main-table tr.highlight td {
            background: #f0f0f0;
            font-weight: 700;
            line-height: 2.2;
        }
        .main-table tr.total-row td {
            background: #d0d0d0;
            font-weight: 700;
            border-top: 1.5px solid #000;
            line-height: 2.2;
        }

        /* Balance box */
        .balance-box {
            border: 1.5px solid #000;
            margin-top: 2px;
            padding: 4px 12px;
            background: #fffef0;
            flex-shrink: 0;
        }
        .balance-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 10pt;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 2px;
            line-height: 2.2;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 9pt;
        }
        .balance-row.total {
            border-top: 1.5px solid #000;
            margin-top: 2px;
            padding-top: 3px;
            font-weight: 700;
            font-size: 10pt;
        }
        .balance-label { font-family: 'Noto Nastaliq Urdu', serif; }
        .balance-value {
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-weight: 700;
        }
        .balance-value.positive { color: #c00; }
        .balance-value.negative { color: #080; }

        /* Horizontal Received Detail */
        .balance-row-received {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 9pt;
            gap: 8px;
        }
        .balance-row-received .balance-label {
            white-space: nowrap;
            flex-shrink: 0;
        }
        .balance-row-received .balance-value {
            white-space: nowrap;
            flex-shrink: 0;
        }
        .received-details-horizontal {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            font-size: 7pt;
            color: #444;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.4;
            flex: 1;
            justify-content: center;
        }
        .r-item {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 2px;
        }
        .r-name {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 600;
            color: #1e3a5f;
        }
        .r-ratti {
            font-size: 6pt;
            color: #888;
            font-style: italic;
        }
        .r-khalis {
            color: #080;
            font-weight: 700;
        }

        /* History section */
        .history-section {
            margin-top: 2px;
            border: 1.5px solid #1e3a5f;
            background: #f8fbff;
            flex-shrink: 0;
        }
        .history-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 10pt;
            background: #1e3a5f;
            color: #fff;
            padding: 3px;
            line-height: 2.2;
        }
        .history-row {
            display: flex;
            border-bottom: 1px solid #1e3a5f;
        }
        .history-row:last-child { border-bottom: none; }
        .history-invoice {
            flex: 1;
            padding: 3px 10px;
            border-right: 1px solid #1e3a5f;
        }
        .history-invoice:last-child { border-right: none; }
        .history-invoice-header {
            text-align: center;
            font-weight: 700;
            font-size: 7pt;
            border-bottom: 1px dashed #999;
            padding-bottom: 1px;
            margin-bottom: 2px;
            font-family: 'Segoe UI', Arial, sans-serif; 
        }
        .history-invoice-header .inv-date {
            font-size: 6pt;
            color: #666;
            font-weight: 400;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 7pt;
            border-bottom: 1px dotted #ddd;
        }
        .history-item:last-child { border-bottom: none; }
        .history-item .hist-label {
            font-family: 'Noto Nastaliq Urdu', serif;
            color: #444;
        }
        .history-item .hist-value {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 600;
        }
        .history-item.total-gold {
            background: #e8f0ff;
            padding: 1px 2px;
            margin: 1px -2px;
            border-radius: 2px;
        }
        .history-item.balance-due {
            background: #fff0e8;
            padding: 1px 2px;
            margin: 1px -2px;
            border-radius: 2px;
        }
        .history-item.balance-due .hist-value {
            color: #c00;
            font-weight: 700;
        }

        /* Footer note */
        .footer-note {
            border: 1px solid #000;
            border-top: none;
            padding: 3px;
            font-size: 7pt;
            text-align: center;
            color: #555;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.4;
            flex-shrink: 0;
        }

        /* Signature */
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 4px;
            padding: 0 8px;
            flex-shrink: 0;
        }
        .signature-box {
            width: 40%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 8pt;
            font-family: 'Noto Nastaliq Urdu', serif;
        }

        /* Print controls */
        .no-print { 
            padding: 10px; 
            text-align: center;
            background: #f0f0f0;
            border-bottom: 1px solid #ccc;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        .no-print button, .no-print a {
            padding: 5px 10px;
            margin: 0 3px;
            font-size: 11px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        @media print { 
            .no-print { display: none !important; } 
            body { 
                margin: 0; 
                background: #fff;
                align-items: flex-start;
                justify-content: flex-start;
            }
            .print-container { 
                width: 145mm; 
                height: 200mm; 
                padding: 4mm; 
                box-shadow: none;
            }
        }

        @media screen {
            body {
                padding-top: 50px;
            }
        }

                <?php if (query('bulk_jpg')): ?>
        /* Bulk JPG export: capture the exact receipt only, without toolbar/screen offset */
        .no-print { display: none !important; }
        body { padding-top: 0 !important; background: #fff !important; justify-content: flex-start !important; }
        .print-container { box-shadow: none !important; margin: 0 auto !important; }
        <?php endif; ?>
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print Receipt</button>
        <a href="<?= url('invoices/show.php?id=' . $id) ?>">← Back to Invoice</a>
        <a href="<?= url('invoices/print.php?id=' . $id) ?>">📄 Full Invoice</a>
    </div>

    <div class="print-container">

<!-- PREMIUM VARIANT 1 HEADER -->
<div class="premium-header-v1">
    <!-- Left Section - Dark Blue with Logo -->
    <div class="header-left">
        <div class="logo-hexagon">
            <div class="logo-mj">MJ</div>
        </div>
        <div class="logo-text">GOLD CASTING</div>
    </div>
    
    <!-- Center Section - White/Cream -->
    <div class="header-center">
        <div class="company-name-en">MJ CASTING</div>
        <div class="contact-info">
            <div class="contact-item">
                <span class="icon">📍</span>
                <span> <?= htmlspecialchars($workshopAddress) ?></span>
            </div>
            <div class="contact-item">
                <span class="icon">📞</span>
                <span><?= htmlspecialchars($workshopPhone) ?></span><span><?= htmlspecialchars($workshopPhone2) ?></span> <span><?= htmlspecialchars($workshopPhone3) ?></span>
            </div>

        </div>
    </div>
    
    <!-- Right Section - Dark Blue with Urdu -->
    <div class="header-right">
        <div class="urdu-main"><?= htmlspecialchars($workshopNameUrdu) ?></div>
        <div class="urdu-sub">گولڈ کاسٹنگ اینڈ جیولری ورکس</div>
        <div class="urdu-divider">◆◆◆</div>
    </div>
</div>
        <!-- META ROW -->
        <div class="meta-row">
            <div class="meta-item">
                <div class="label">سیریل نمبر</div>
                <div class="value"><?= htmlspecialchars($invoice['invoice_no']) ?></div>
            </div>
            <div class="meta-item">
                <div class="label">تاریخ</div>
                <div class="value"><?= date('d-m-Y', strtotime($invoice['invoice_date'])) ?></div>
            </div>
            <div class="meta-item">
                <div class="label">بل نمبر </div>
                 <div class="value"><?= htmlspecialchars($invoice['manual_book_no'] ?: '-') ?></div>
            </div>
            <div class="meta-item">
                <div class="label">رتی</div>
              
                <div class="value"><?= formatRattiPrint($invoice['ratti']) ?></div>
            </div>
        </div>

        <!-- PARTY ROW -->
        <div class="party-row">
            <div>
                <strong>پارٹی:</strong> <span class="party-name"><?= htmlspecialchars($invoice['customer_name'] ?? 'Cash Party') ?></span>
            </div>
        </div>

        <!-- MAIN TABLE -->
        <table class="main-table">
            <thead>
                <tr>
                    <th class="urdu">نام</th>
                    <th class="urdu">رقم</th>
                    <th class="urdu">وزن (گرام)</th>
                    <th class="urdu">نام اشیاء</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="number">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['casting_weight'], 3) ?></td>
                    <td class="desc">کاسٹنگ وزن (گرام)</td>
                </tr>
                <tr>
                    <td class="number">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['waste_weight'], 3) ?></td>
                    <td class="desc">ویسٹ (گرام)</td>
                </tr>
                <tr class="highlight">
                    <td class="number">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['total_weight'], 3) ?></td>
                    <td class="desc">کل وزن (گرام)</td>
                </tr>
                <tr>
                    <td class="number">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['male_waste'], 3) ?></td>
                    <td class="desc">میل کاٹ (گرام)</td>
                </tr>
                <tr>
                    <td class="number">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['gold_khalis'], 3) ?></td>
                    <td class="desc"> (گرام)خالص وزن</td>
                </tr>
                <?php if ((float)$invoice['rp_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="remark">(روپے)آر پی مزدوری </td>
                    <td class="number"><?= number_format($invoice['rp_mazdori_amount'], 0) ?></td>
                    <td class="number"><?= number_format($invoice['rp_mazdori_weight'], 3) ?></td>
                    <td class="desc"> (گرام)آر پی مزدوری</td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['casting_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="remark">(روپے)کاسٹنگ مزدوری</td>
                    <td class="number"><?= number_format($invoice['casting_mazdori_amount'], 0) ?></td>
                    <td class="number"><?= number_format($invoice['casting_mazdori_weight'], 3) ?></td>
                    <td class="desc"> (گرام)کاسٹنگ مزدوری</td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="number">-</td>
                    <td class="remark">-</td>
                    <td class="number"><?= number_format($invoice['effective_gold'], 3) ?></td>
                    <td class="desc">(خالص بیلنس(گرام</td>
                </tr>
            </tbody>
        </table>

        <!-- BALANCE BOX -->
        <div class="balance-box">
            <?php
                $prevBal = (float)$invoice['previous_balance'];
                $prevBalWord = $prevBal > 0 ? '(لینا) ' : ($prevBal < 0 ? '(جمع) ' : '');
                $remBal = (float)$invoice['remaining_balance'];
                $remBalWord = $remBal > 0 ? '(لینا) ' : ($remBal < 0 ? '(جمع) ' : '');
                $remBalClass = $remBal > 0 ? 'positive' : ($remBal < 0 ? 'negative' : '');
                $remBalStyle = '';
                if ($remBal == 0) {
                    $remBalStyle = 'background-color: #d4edda; color: #155724; padding: 4px 12px; margin: -4px -12px;';
                }
            ?>
            
            <!-- Row 1: Previous Balance -->
            <div class="balance-row" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 2; min-height: 20px;"></div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-value"><?= $prevBalWord ?><?= number_format(abs($prevBal), 3) ?> g</span>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-label">سابقہ بیلنس:</span>
                </div>
            </div>

            <!-- Row 2: Total Khalis -->
            <div class="balance-row" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 2; min-height: 20px;"></div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-value"><?= number_format($invoice['effective_gold'], 3) ?> g</span>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-label">+ ٹوٹل خالص:</span>
                </div>
            </div>

            <!-- Row 3: Received Gold -->
            <?php if (!empty($receives)): ?>
            <div class="balance-row-received" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 2; text-align: left;">
                    <div class="received-details-horizontal">
                        <?php foreach ($receives as $rec): ?>
                            <?php 
                                $desc = htmlspecialchars($rec['description'] ?: 'خالص');
                                $gross = number_format($rec['gross_weight'], 3);
                                $ratti = formatRattiPrint($rec['ratti_impurity']);
                                $khalis = number_format($rec['khalis_weight'], 3);
                            ?>
                            <span class="r-item">{
                                <?php if ((float)$rec['ratti_impurity'] > 0): ?>
                                    <span class="r-name"><?= $desc ?></span>:<span class="r-gross"><?= $gross ?></span> (رتی <span class="r-ratti"><?= $ratti ?></span>) = <span class="r-khalis"><?= $khalis ?></span>
                                <?php else: ?>
                                    <span class="r-name"><?= $desc ?></span>: <span class="r-khalis"><?= $khalis ?> g</span>
                                <?php endif; ?>
                            },</span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-value"><?= number_format($invoice['total_received_khalis'], 3) ?> g</span>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-label">- وصولی (سونا):</span>
                </div>
            </div>
            <?php elseif ((float)$invoice['total_received_khalis'] > 0): ?>
            <div class="balance-row" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 2; min-height: 20px;"></div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-value"><?= number_format($invoice['total_received_khalis'], 3) ?> g</span>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-label">- وصولی (سونا):</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Row 4: Cash Received -->
            <?php if ((float)$invoice['wasooli'] > 0): ?>
            <div class="balance-row" style="display: flex; justify-content: space-between; align-items: center;">
                <div style="flex: 2; min-height: 20px;"></div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-value"><?= number_format($invoice['wasooli'], 3) ?> g</span>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-label">- وصولی (کیش):</span>
                </div>
            </div>
            <?php endif; ?>

            <!-- Row 5: Remaining Balance -->
            <div class="balance-row total" style="display: flex; justify-content: space-between; align-items: center; <?= $remBalStyle ?>">
                <div style="flex: 2; min-height: 20px;"></div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-value <?= $remBalClass ?>">
                        <?= $remBalWord ?><?= number_format(abs($remBal), 3) ?> g
                    </span>
                </div>
                <div style="flex: 1; text-align: right;">
                    <span class="balance-label">باقی بیلنس:</span>
                </div>
            </div>
        </div>
        

        <!-- PREVIOUS INVOICES HISTORY -->
        <?php if (count($prevInvoices) > 0): ?>
        <div class="history-section">
            <div class="history-title">پچھلے انوائس </div>
            <div class="history-row">
                <?php foreach ($prevInvoices as $idx => $prev): ?>
                <div class="history-invoice">
                    <div class="history-invoice-header">
                        <?= htmlspecialchars($prev['invoice_no']) ?>
                        <span class="inv-date">(<?= date('d-m-Y', strtotime($prev['invoice_date'])) ?>)</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= formatRattiPrint($prev['ratti']) ?>&nbsp;&nbsp; رتی اندر</span>
                        <span class="hist-label">رتی:</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= number_format($prev['total_weight'], 3) ?> &nbsp;&nbsp; (گرام)</span>
                        <span class="hist-label">گولڈ کاسٹنگ:</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= number_format($prev['gold_khalis'], 3) ?>&nbsp;&nbsp;  (گرام)</span>
                        <span class="hist-label">کل خالص:</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= number_format($prev['rp_mazdori_weight'] ?? 0, 3) ?> &nbsp;&nbsp; (گرام) </span>
                        <span class="hist-label">آر پی وزن:</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= number_format($prev['casting_mazdori_weight'] ?? 0, 3) ?> &nbsp;&nbsp; (گرام) </span>
                        <span class="hist-label">کاسٹنگ مزدوری:</span>
                    </div>
                    <div class="history-item total-gold">
                        <span class="hist-value"><strong><?= number_format($prev['effective_gold'] ?? 0, 3) ?> &nbsp;&nbsp; (گرام) </strong></span>
                        <span class="hist-label"><strong>کل سونا:</strong></span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><strong><?= number_format($prev['total_received_khalis'] ?? 0, 3) ?>&nbsp;&nbsp; (گرام) </strong></span>
                        <span class="hist-label"><strong>وصولی:</strong></span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><strong><?= number_format($prev['previous_balance'] ?? 0, 3) ?>&nbsp;&nbsp; (گرام) </strong></span>
                        <span class="hist-label"><strong>سابقہ بیلنس:</strong></span>
                    </div>
                    <div class="history-item balance-due">
                        <span class="hist-value"><strong><?= number_format($prev['remaining_balance'] ?? 0, 3) ?> &nbsp;&nbsp;(گرام) </strong></span>
                        <span class="hist-label"><strong>بقایا بیلنس:</strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($prevInvoices) < 2): ?>
                <div class="history-invoice" style="text-align:center; padding:10px; color:#999; font-size:7pt; font-family:'Noto Nastaliq Urdu',serif;">
                    کوئی پچھلا انوائس نہیں
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- FOOTER NOTE -->
        <div class="footer-note">
            نوٹ: پٹھور سونے کی کوئی گارنٹی نہیں ہے، براہ کرم مال وصول کرتے وقت چیک کریں بعد میں شکایت قابل قبول نہیں۔
        </div>

        <!-- SIGNATURE -->
        <div class="footer-note" style="font-size:7px">
            <div >This document is computer generated and does not require any stamp/signature</div>
            <div style="text-align:center; margin-top:2px; font-size:6pt; color:#888;">
            <?= htmlspecialchars($workshopName) ?> | Printed: <?= date('d-m-Y h:i A') ?>
        </div>
        </div>

        <!-- BOTTOM INFO -->
        

    </div>
</body>
</html>