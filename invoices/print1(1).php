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
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet">
    <style>
        @page { size: 148mm 210mm; margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            margin: 0; 
            padding: 0; 
            background: #fff; 
            color: #000; 
            font-family: 'Segoe UI', Arial, sans-serif; 
            -webkit-print-color-adjust: exact; 
            font-size: 7pt;
            line-height: 1.2;
        }
        .print-container { 
            width: 148mm; 
            height: 210mm;
            margin: 0 auto; 
            padding: 3mm;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        /* Compact 2-Column Header */
        .header-compact {
            background: linear-gradient(135deg, #1e3a5f, #2d5a87);
            color: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 8px;
            border-radius: 2px 2px 0 0;
            border: 1px solid #1e3a5f;
        }
        .header-col-left { text-align: left; }
        .header-col-left .shop-name-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 10pt;
            font-weight: 700;
            line-height: 1.2;
        }
        .header-col-left .shop-name {
            font-size: 7pt;
            font-weight: 600;
            letter-spacing: 0.5px;
            opacity: 0.9;
        }
        .header-col-right {
            text-align: right;
            font-size: 6.5pt;
            line-height: 1.3;
        }
        .header-col-right .phone {
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            font-size: 7pt;
        }
        .header-col-right .address {
            font-size: 6pt;
            opacity: 0.9;
        }
        
        /* Meta row */
        .meta-row {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            border-top: none;
            padding: 2px 24px;
            font-size: 6pt;
            background: #fafafa;
        }
        .meta-item { text-align: center; }
        .meta-item .label {
            font-size: 5.5pt;
            color: #666;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 2.1;
        }
        .meta-item .value {
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
            font-size: 6.5pt;
        }
        
        /* Party info */
        .party-row {
            border: 1px solid #000;
            border-top: none;
            padding: 2px 4px;
            font-size: 6.5pt;
            display: flex;
            justify-content: space-between;
            align-items: right;
        }
        .party-name { font-weight: 700; font-size: 7pt; }
        
        /* Main table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            font-size: 6.5pt;
        }
        .main-table th {
            background: #e0e0e0;
            border: 1px solid #000;
            padding: 2px 1px;
            text-align: center;
            font-weight: 700;
            font-size: 6pt;
        }
        .main-table th.urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 6.5pt;
            line-height: 2.1;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 2px 2px;
            text-align: center;
            vertical-align: middle;
        }
        .main-table td.desc {
            text-align: right;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 6.5pt;
            padding-right: 3px;
            line-height: 2.1;
        }
        .main-table td.number {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 6.5pt;
        }
        .main-table td.remark {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 6pt;
        }
        .main-table tr.highlight td {
            background: #f0f0f0;
            font-weight: 700;
            line-height: 2.1;
        }
        .main-table tr.total-row td {
            background: #d0d0d0;
            font-weight: 700;
            border-top: 1.5px solid #000;
            line-height: 2.1;
        }
        
        /* Balance box */
        .balance-box {
            border: 1.5px solid #000;
            margin-top: 2px;
            padding: 3px 10px;
            background: #fffef0;
        }
        .balance-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 7.5pt;
            border-bottom: 1px solid #000;
            padding-bottom: 1px;
            margin-bottom: 2px;
            line-height: 2.1;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 6.5pt;
        }
        .balance-row.total {
            border-top: 1.5px solid #000;
            margin-top: 2px;
            padding-top: 2px;
            font-weight: 700;
            font-size: 7.5pt;
        }
        .balance-label { font-family: 'Noto Nastaliq Urdu', serif; }
        .balance-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }
        .balance-value.positive { color: #c00; }
        .balance-value.negative { color: #080; }

        /* ===== NEW: Horizontal Received Detail IN BETWEEN Row ===== */
        .balance-row-received {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 6.5pt;
            gap: 6px;
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
            font-size: 5pt;
            color: #444;
            font-family: 'JetBrains Mono', monospace;
            line-height: 1.3;
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
            font-size: 4.5pt;
            color: #888;
            font-style: italic;
        }
        .r-khalis {
            color: #080;
            font-weight: 700;
        }
        /* ===== END NEW ===== */
        
        /* History section - Compact */
        .history-section {
            margin-top: 2px;
            border: 1.5px solid #1e3a5f;
            background: #f8fbff;
            
        }
        .history-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 7.5pt;
            background: #1e3a5f;
            color: #fff;
            padding: 2px;
            line-height: 2.1;
        }
        .history-row {
            display: flex;
            border-bottom: 1px solid #1e3a5f;
        }
        .history-row:last-child { border-bottom: none; }
        .history-invoice {
            flex: 1;
            padding: 2px 10px;
            border-right: 1px solid #1e3a5f;
        }
        .history-invoice:last-child { border-right: none; }
        .history-invoice-header {
            text-align: center;
            font-weight: 700;
            font-size: 6pt;
            border-bottom: 1px dashed #999;
            padding-bottom: 1px;
            margin-bottom: 2px;
            font-family: 'JetBrains Mono', monospace;
        }
        .history-invoice-header .inv-date {
            font-size: 5.5pt;
            color: #666;
            font-weight: 400;
        }
        .history-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5px 0;
            font-size: 5.5pt;
            border-bottom: 1px dotted #ddd;
        }
        .history-item:last-child { border-bottom: none; }
        .history-item .hist-label {
            font-family: 'Noto Nastaliq Urdu', serif;
            color: #444;
        }
        .history-item .hist-value {
            font-family: 'JetBrains Mono', monospace;
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
            padding: 2px;
            font-size: 5.5pt;
            text-align: center;
            color: #555;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.3;
        }
        
        /* Signature */
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 4px;
            padding: 0 6px;
        }
        .signature-box {
            width: 40%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 2px;
            font-size: 6pt;
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        
        /* Print controls */
        .no-print { 
            padding: 10px; 
            text-align: center;
            background: #f0f0f0;
            border-bottom: 1px solid #ccc;
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
            body { margin: 0; }
            .print-container { width: 148mm; height: 210mm; padding: 0; }
        }
    </style>
</head>
<body >

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print Receipt</button>
        <a href="<?= url('invoices/show.php?id=' . $id) ?>">← Back to Invoice</a>
        <a href="<?= url('invoices/print.php?id=' . $id) ?>">📄 Full Invoice</a>
    </div>

    <div class="print-container">

        <!-- COMPACT 2-COLUMN HEADER -->
        <div class="header-compact">
            <div class="header-col-left">
                <div class="shop-name-urdu"><?= htmlspecialchars($workshopNameUrdu) ?></div>
                <div class="shop-name"><?= htmlspecialchars($workshopName) ?></div>
            </div>
            <div class="header-col-right">
                <div class="phone">📞 <?= htmlspecialchars($workshopPhone ?: '') ?><?= $workshopPhone2 ? ' | ' . htmlspecialchars($workshopPhone2) : '' ?></div>
                <?php if ($workshopAddress): ?>
                <div class="address"><?= htmlspecialchars($workshopAddress) ?></div>
                <?php endif; ?>
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
                  <!--<div class="value"><?= $invoice['id'] ?> </div> -->
                 <div class="value"><?= number_format($invoice['manual_book_no'], 0) ?></div>
            </div>
            <div class="meta-item">
                <div class="label">رتی</div>
                <div class="value"><?= number_format($invoice['ratti'], 0) ?></div>
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
           <!--  <div class="balance-title">بیلنس کا خلاصہ</div> -->
            <div class="balance-row">
                 <span class="balance-value"><?= number_format($invoice['previous_balance'], 3) ?> g</span>
                <span class="balance-label">سابقہ بیلنس:</span>
               
            </div>
            <div class="balance-row">
                <span class="balance-value"><?= number_format($invoice['effective_gold'], 3) ?> g</span>
                <span class="balance-label">+ ٹوٹل خالص:</span>
                
            </div>

            <?php if (!empty($receives)): ?>
            <!-- ===== NEW: Horizontal Received Detail IN BETWEEN ===== -->
            <div class="balance-row-received">
                <span class="balance-value"><?= number_format($invoice['total_received_khalis'], 3) ?> g</span>
                
                <div class="received-details-horizontal">
                    <?php foreach ($receives as $rec): ?>
                        <?php 
                            $desc = htmlspecialchars($rec['description'] ?: 'خالص');
                            $gross = number_format($rec['gross_weight'], 3);
                            $ratti = number_format($rec['ratti_impurity'], 0);
                            $khalis = number_format($rec['khalis_weight'], 3);
                        ?>
                        <span class="r-item">{
                            <?php if ((float)$rec['ratti_impurity'] > 0): ?>
                                <!-- Has Ratti: Show desc, gross, ratti, and khalis -->
                                <span class="r-name"><?= $desc ?></span>:<span class="r-gross"><?= $gross ?></span> (رتی <span class="r-ratti"><?= $ratti ?></span>) = <span class="r-khalis"><?= $khalis ?></span>
                            <?php else: ?>
                                <!-- No Ratti: Show only desc and khalis -->
                                <span class="r-name"><?= $desc ?></span>: <span class="r-khalis"><?= $khalis ?></span>
                            <?php endif; ?>
                        },</span>
                    <?php endforeach; ?>
                </div>
                <span class="balance-label">- وصولی (سونا):</span>
            </div>
            <!-- ===== END NEW ===== -->
            <?php elseif ((float)$invoice['total_received_khalis'] > 0): ?>
            <!-- Fallback for legacy data without receive rows -->
            <div class="balance-row">
                <span class="balance-label">- وصولی (سونا):</span>
                <span class="balance-value"><?= number_format($invoice['total_received_khalis'], 3) ?> g</span>
            </div>
            <?php endif; ?>

            <?php if ((float)$invoice['wasooli'] > 0): ?>
            <div class="balance-row">
                <span class="balance-label">- وصولی (کیش):</span>
                <span class="balance-value"><?= number_format($invoice['wasooli'], 3) ?> g</span>
            </div>
            <?php endif; ?>
            
            <div class="balance-row total">
                
                <span class="balance-value <?= $invoice['remaining_balance'] > 0 ? 'positive' : 'negative' ?>">
                    <?= number_format($invoice['remaining_balance'], 3) ?> g
                </span>
                <span class="balance-label">باقی بیلنس:</span>
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
                       
                        <span class="hist-value"><?= number_format($prev['ratti'], 0) ?> رتی</span>
                         <span class="hist-label">رتی:</span>
                    </div>
                    <div class="history-item">
                        
                        <span class="hist-value"><?= number_format($prev['total_weight'], 3) ?> (گرام)</span>
                        <span class="hist-label">گولڈ کاسٹنگ:</span>
                    </div>
                    <div class="history-item">
                       
                        <span class="hist-value"><?= number_format($prev['gold_khalis'], 3) ?> (گرام)</span>
                         <span class="hist-label">کل خالص:</span>
                    </div>
                    <div class="history-item">
                        
                        <span class="hist-value"><?= number_format($prev['rp_mazdori_weight'] ?? 0, 3) ?> (گرام) </span>
                        <span class="hist-label">آر پی وزن:</span>
                    </div>
                    <div class="history-item">
                        
                        <span class="hist-value"><?= number_format($prev['casting_mazdori_weight'] ?? 0, 3) ?> (گرام) </span>
                        <span class="hist-label">کاسٹنگ مزدوری:</span>
                    </div>
                    <div class="history-item total-gold">
                       
                        <span class="hist-value"><strong><?= number_format($prev['effective_gold'] ?? 0, 3) ?> (گرام) </strong></span>
                         <span class="hist-label"><strong>کل سونا:</strong></span>
                    </div>
                    <div class="history-item">
                        
                        <span class="hist-value"><strong><?= number_format($prev['total_received_khalis'] ?? 0, 3) ?> (گرام) </strong></span>
                        <span class="hist-label"><strong>وصولی:</strong></span>
                    </div>
                    <div class="history-item">
                       
                        <span class="hist-value"><strong><?= number_format($prev['previous_balance'] ?? 0, 3) ?> (گرام) </strong></span>
                         <span class="hist-label"><strong>سابقہ بیلنس:</strong></span>
                    </div>
                    <div class="history-item balance-due">
                     
                        <span class="hist-value"><strong><?= number_format($prev['remaining_balance'] ?? 0, 3) ?> (گرام) </strong></span>
                           <span class="hist-label"><strong>بقایا بیلنس:</strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($prevInvoices) < 2): ?>
                <div class="history-invoice" style="text-align:center; padding:10px; color:#999; font-size:6pt; font-family:'Noto Nastaliq Urdu',serif;">
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
        <div class="signature-area">
            <div class="signature-box">
                <div ></div>
            </div>
            <div class="signature-box">
                <div class="signature-line">دستخط</div>
            </div>
        </div>

        <!-- BOTTOM INFO -->
        <div style="text-align:center; margin-top:2px; font-size:5pt; color:#888;">
            <?= htmlspecialchars($workshopName) ?> | Printed: <?= date('d-m-Y h:i A') ?>
        </div>

    </div>
</body>
</html>