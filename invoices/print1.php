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
            font-size: 8pt;
        }
        .print-container { 
            width: 148mm; 
            min-height: 210mm;
            margin: 0 auto; 
            padding: 4mm;
            display: flex;
            flex-direction: column;
        }
        
        /* Header */
        .header-top {
            background: linear-gradient(135deg, #1e3a5f, #2d5a87);
            color: #fff;
            text-align: center;
            padding: 5px 4px;
            border-radius: 3px 3px 0 0;
        }
        .header-top .shop-name-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 14pt;
            font-weight: 700;
            line-height: 1.3;
        }
        .header-top .shop-name {
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 1px;
            margin-top: 1px;
        }
        .header-contact {
            background: #f0f0f0;
            border: 1px solid #ccc;
            border-top: none;
            padding: 3px;
            text-align: center;
            font-size: 7pt;
        }
        .header-contact .phone {
            font-weight: 600;
            color: #1e3a5f;
        }
        .header-contact .address {
            font-size: 6pt;
            color: #555;
            margin-top: 1px;
        }
        
        /* Meta row */
        .meta-row {
            display: flex;
            justify-content: space-between;
            border: 1px solid #000;
            border-top: none;
            padding: 3px 5px;
            font-size: 7pt;
            background: #fafafa;
        }
        .meta-item {
            text-align: center;
        }
        .meta-item .label {
            font-size: 6pt;
            color: #666;
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        .meta-item .value {
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        
        /* Party info */
        .party-row {
            border: 1px solid #000;
            border-top: none;
            padding: 3px 5px;
            font-size: 7pt;
            display: flex;
            justify-content: space-between;
        }
        .party-name {
            font-weight: 700;
        }
        .party-type {
            background: #1e3a5f;
            color: #fff;
            padding: 1px 5px;
            border-radius: 2px;
            font-size: 6pt;
        }
        
        /* Main table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
            font-size: 8pt;
        }
        .main-table th {
            background: #e8e8e8;
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-weight: 700;
            font-size: 7pt;
        }
        .main-table th.urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 8pt;
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
            font-size: 8pt;
            padding-right: 4px;
        }
        .main-table td.number {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
            font-size: 8pt;
        }
        .main-table td.remark {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 7pt;
        }
        .main-table tr.highlight td {
            background: #f5f5f5;
            font-weight: 700;
        }
        .main-table tr.total-row td {
            background: #e8e8e8;
            font-weight: 700;
            border-top: 2px solid #000;
        }
        .main-table td.empty {
            background: #fafafa;
        }
        
        /* Mazdori section */
        .mazdori-section {
            border: 1px solid #000;
            border-top: none;
            padding: 4px;
        }
        .mazdori-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 8pt;
            border-bottom: 1px dashed #999;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }
        .mazdori-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 7pt;
            border-bottom: 1px dotted #ccc;
        }
        .mazdori-row:last-child {
            border-bottom: none;
        }
        .mazdori-label {
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        .mazdori-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 600;
        }
        
        /* Balance box */
        .balance-box {
            border: 2px solid #000;
            margin-top: 3px;
            padding: 4px;
            background: #fffef0;
        }
        .balance-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 9pt;
            border-bottom: 1px solid #000;
            padding-bottom: 2px;
            margin-bottom: 3px;
        }
        .balance-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
            font-size: 7pt;
        }
        .balance-row.total {
            border-top: 2px solid #000;
            margin-top: 3px;
            padding-top: 4px;
            font-weight: 700;
            font-size: 9pt;
        }
        .balance-label {
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        .balance-value {
            font-family: 'JetBrains Mono', monospace;
            font-weight: 700;
        }
        .balance-value.positive { color: #c00; }
        .balance-value.negative { color: #080; }
        
        /* ===== PREVIOUS INVOICES HISTORY SECTION ===== */
        .history-section {
            margin-top: 4px;
            border: 2px solid #1e3a5f;
            background: #f8fbff;
        }
        .history-title {
            text-align: center;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 9pt;
            background: #1e3a5f;
            color: #fff;
            padding: 3px;
        }
        .history-row {
            display: flex;
            border-bottom: 1px solid #1e3a5f;
        }
        .history-row:last-child {
            border-bottom: none;
        }
        .history-invoice {
            flex: 1;
            padding: 4px 5px;
            border-right: 1px solid #1e3a5f;
        }
        .history-invoice:last-child {
            border-right: none;
        }
        .history-invoice-header {
            text-align: center;
            font-weight: 700;
            font-size: 7pt;
            border-bottom: 1px dashed #999;
            padding-bottom: 2px;
            margin-bottom: 3px;
            font-family: 'JetBrains Mono', monospace;
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
            font-size: 6pt;
            border-bottom: 1px dotted #ddd;
        }
        .history-item:last-child {
            border-bottom: none;
        }
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
            padding: 2px 3px;
            margin: 2px -3px;
            border-radius: 2px;
        }
        .history-item.balance-due {
            background: #fff0e8;
            padding: 2px 3px;
            margin: 2px -3px;
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
            padding: 4px;
            font-size: 6pt;
            text-align: center;
            color: #555;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.4;
        }
        
        /* Signature */
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            padding: 0 8px;
        }
        .signature-box {
            width: 40%;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            padding-top: 3px;
            font-size: 7pt;
            font-family: 'Noto Nastaliq Urdu', serif;
        }
        
        /* Stamp box */
        .stamp-box {
            border: 2px dashed #999;
            margin-top: 6px;
            padding: 6px;
            text-align: center;
            min-height: 35px;
        }
        
        /* Print controls */
        .no-print { 
            padding: 12px; 
            text-align: center;
            background: #f0f0f0;
            border-bottom: 1px solid #ccc;
        }
        .no-print button, .no-print a {
            padding: 6px 12px;
            margin: 0 3px;
            font-size: 12px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        @media print { 
            .no-print { display: none !important; } 
            body { margin: 0; }
            .print-container { width: 148mm; min-height: 210mm; padding: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()">🖨️ Print Receipt</button>
        <a href="<?= url('invoices/show.php?id=' . $id) ?>">← Back to Invoice</a>
        <a href="<?= url('invoices/print.php?id=' . $id) ?>">📄 Full Invoice</a>
    </div>

    <div class="print-container">

        <!-- HEADER -->
        <div class="header-top">
            <div class="shop-name-urdu"><?= htmlspecialchars($workshopNameUrdu) ?></div>
            <div class="shop-name"><?= htmlspecialchars($workshopName) ?></div>
        </div>
        <div class="header-contact">
            <div class="phone">
                📞 <?= htmlspecialchars($workshopPhone ?: '') ?> 
                <?= $workshopPhone2 ? ' | ' . htmlspecialchars($workshopPhone2) : '' ?>
            </div>
            <?php if ($workshopAddress): ?>
            <div class="address"><?= htmlspecialchars($workshopAddress) ?></div>
            <?php endif; ?>
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
                <div class="label">آرڈر نمبر</div>
                <div class="value"><?= $invoice['id'] ?></div>
            </div>
            <div class="meta-item">
                <div class="label">کل رقم</div>
                <div class="value">Rs <?= number_format($invoice['rp_amount'], 0) ?></div>
            </div>
        </div>

        <!-- PARTY ROW -->
        <div class="party-row">
            <div>
                <strong>پارٹی:</strong> <span class="party-name"><?= htmlspecialchars($invoice['customer_name'] ?? 'Cash Party') ?></span>
            </div>
            <span class="party-type">Cash Party</span>
        </div>

        <!-- MAIN TABLE -->
        <table class="main-table">
            <thead>
                <tr>
                    <th class="urdu">نام اشیاء</th>
                    <th class="urdu">تعداد</th>
                    <th class="urdu">فی کلو</th>
                    <th class="urdu">کل</th>
                    <th class="urdu">ریمارکس</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="desc">سون کا سٹاک</td>
                    <td class="number"><?= number_format($invoice['casting_weight'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['gold_khalis'], 3) ?></td>
                    <td class="remark">خالص وزنی</td>
                </tr>
                <tr>
                    <td class="desc">ویسٹ</td>
                    <td class="number"><?= number_format($invoice['waste_weight'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['total_weight'], 3) ?></td>
                    <td class="remark">کل خالص</td>
                </tr>
                <tr class="highlight">
                    <td class="desc">کل وزن</td>
                    <td class="number"><?= number_format($invoice['total_weight'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['gold_khalis'], 3) ?></td>
                    <td class="remark">بچول خالص</td>
                </tr>
                <tr>
                    <td class="desc">میل کاٹ</td>
                    <td class="number"><?= number_format($invoice['male_waste'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['gold_khalis'] - $invoice['male_waste'], 3) ?></td>
                    <td class="remark">بقایا خالص</td>
                </tr>
                <?php if ((float)$invoice['rp_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="desc">آر پی مزدوری</td>
                    <td class="number"><?= number_format($invoice['rp_mazdori_weight'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['rp_mazdori_amount'], 2) ?></td>
                    <td class="remark">مزدوری</td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['casting_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="desc">کاسٹنگ مزدوری</td>
                    <td class="number"><?= number_format($invoice['casting_mazdori_weight'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['casting_mazdori_amount'], 2) ?></td>
                    <td class="remark">مزدوری</td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['total_received_khalis'] > 0): ?>
                <tr>
                    <td class="desc">پارٹی سے وصولی</td>
                    <td class="number"><?= number_format($invoice['total_received_khalis'], 3) ?></td>
                    <td class="number">-</td>
                    <td class="number">-</td>
                    <td class="remark">سونا واپس</td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="desc">خالص بیلنس</td>
                    <td class="number">-</td>
                    <td class="number">-</td>
                    <td class="number"><?= number_format($invoice['effective_gold'], 3) ?></td>
                    <td class="remark">کل ایفیکٹو</td>
                </tr>
            </tbody>
        </table>

        <!-- MAZDORI AMOUNTS SECTION -->
        <?php if ((float)$invoice['rp_mazdori_amount'] > 0 || (float)$invoice['casting_mazdori_amount'] > 0 || (float)$invoice['rp_amount'] > 0): ?>
        <div class="mazdori-section">
            <div class="mazdori-title">رقوم کی تفصیل</div>
            <?php if ((float)$invoice['rp_amount'] > 0): ?>
            <div class="mazdori-row">
                <span class="mazdori-label">آر پی رقم (<?= number_format($invoice['rp_rate'], 2) ?>/g):</span>
                <span class="mazdori-value">Rs <?= number_format($invoice['rp_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if ((float)$invoice['rp_mazdori_amount'] > 0): ?>
            <div class="mazdori-row">
                <span class="mazdori-label">آر پی مزدوری:</span>
                <span class="mazdori-value">Rs <?= number_format($invoice['rp_mazdori_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
            <?php if ((float)$invoice['casting_mazdori_amount'] > 0): ?>
            <div class="mazdori-row">
                <span class="mazdori-label">کاسٹنگ مزدوری:</span>
                <span class="mazdori-value">Rs <?= number_format($invoice['casting_mazdori_amount'], 2) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- BALANCE BOX -->
        <div class="balance-box">
            <div class="balance-title">بیلنس کا خلاصہ</div>
            <div class="balance-row">
                <span class="balance-label">پچھلا بیلنس:</span>
                <span class="balance-value"><?= number_format($invoice['previous_balance'], 3) ?> g</span>
            </div>
            <div class="balance-row">
                <span class="balance-label">+ ایفیکٹو گولڈ:</span>
                <span class="balance-value"><?= number_format($invoice['effective_gold'], 3) ?> g</span>
            </div>
            <?php if ((float)$invoice['total_received_khalis'] > 0): ?>
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
                <span class="balance-label">باقی بیلنس:</span>
                <span class="balance-value <?= $invoice['remaining_balance'] > 0 ? 'positive' : 'negative' ?>">
                    <?= number_format($invoice['remaining_balance'], 3) ?> g
                </span>
            </div>
        </div>

        <!-- ===== PREVIOUS INVOICES HISTORY ===== -->
        <?php if (count($prevInvoices) > 0): ?>
        <div class="history-section">
            <div class="history-title">پچھلے انوائس کی تاریخ</div>
            <div class="history-row">
                <?php foreach ($prevInvoices as $idx => $prev): ?>
                <div class="history-invoice">
                    <div class="history-invoice-header">
                        <?= htmlspecialchars($prev['invoice_no']) ?>
                        <span class="inv-date">(<?= date('d-m-Y', strtotime($prev['invoice_date'])) ?>)</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-label">رتی:</span>
                        <span class="hist-value"><?= number_format($prev['ratti'], 0) ?> r</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-label">گولڈ کاسٹنگ:</span>
                        <span class="hist-value"><?= number_format($prev['total_weight'], 3) ?> g</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-label">کل خالص:</span>
                        <span class="hist-value"><?= number_format($prev['gold_khalis'], 3) ?> g</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-label">آر پی وزن:</span>
                        <span class="hist-value"><?= number_format($prev['rp_mazdori_weight'] ?? 0, 3) ?> g</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-label">کاسٹنگ مزدوری:</span>
                        <span class="hist-value"><?= number_format($prev['casting_mazdori_weight'] ?? 0, 3) ?> g</span>
                    </div>
                    <div class="history-item total-gold">
                        <span class="hist-label"><strong>کل سونا:</strong></span>
                        <span class="hist-value"><strong><?= number_format($prev['effective_gold'] ?? 0, 3) ?> g</strong></span>
                    </div>
                    <div class="history-item">
                        <span class="hist-label"><strong>وصولی</td></tr>:</strong></span>
                        <span class="hist-value"><strong>-<?= number_format($prev['total_received_khalis'] ?? 0, 3) ?> g</strong></span>
                    </div>
                                        <div class="history-item">
                        <span class="hist-label"><strong>پچھلا بیلنس</td></tr>:</strong></span>
                        <span class="hist-value"><strong><?= number_format($prev['previous_balance'] ?? 0, 3) ?> g</strong></span>
                    </div>
                    <div class="history-item balance-due">
                        <span class="hist-label"><strong>بقایا بیلنس:</strong></span>
                        <span class="hist-value"><strong><?= number_format($prev['remaining_balance'] ?? 0, 3) ?> g</strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($prevInvoices) < 2): ?>
                <div class="history-invoice" style="text-align:center; padding:15px; color:#999; font-size:7pt; font-family:'Noto Nastaliq Urdu',serif;">
                    کوئی پچھلا انوائس نہیں
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- FOOTER NOTE -->
        <div class="footer-note">
            نوٹ: <br>
            براہ کرم مال وصول کرتے وقت چیک کریں بعد میں شکایت قابل قبول نہیں
        </div>

        <!-- SIGNATURE -->
        <div class="signature-area">
            <div class="signature-box">
                <div class="signature-line">پارٹی دستخط</div>
            </div>
            <div class="signature-box">
                <div class="signature-line">جاری کرنے والے دستخط</div>
            </div>
        </div>



        <!-- BOTTOM INFO -->
        <div style="text-align:center; margin-top:4px; font-size:5pt; color:#888;">
            <?= htmlspecialchars($workshopName) ?> | Printed: <?= date('d-m-Y h:i A') ?>
        </div>

    </div>
</body>
</html>
