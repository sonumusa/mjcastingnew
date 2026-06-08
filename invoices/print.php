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

$format = query('format', 'slip');
$breakdown = buildCalculationBreakdown($invoice);

$workshopName = getSetting('workshop_name', 'M.J Casting');
$workshopNameUrdu = getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ');
$workshopAddress = getSetting('address', '');
$workshopPhone = getSetting('phone', '');
$workshopPhone2 = getSetting('phone2', '');
$workshopPhone3 = getSetting('phone3', '');
$workshopCity = getSetting('city', '');

$hideSidebar = true;
$pageTitle = 'Print Invoice';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print - <?= htmlspecialchars($invoice['invoice_no']) ?></title>
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu&display=swap" rel="stylesheet">
    <style>
        /* Page & Font Setup */
        @page { size: A4 portrait; margin: 12mm; }
        body { margin: 0; padding: 0; background: #fff; color: #000; font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; -webkit-print-color-adjust: exact; }
        .print-container { max-width: 210mm; margin: 0 auto; }
        .format-slip { width: 80mm; padding: 6mm; }
        .format-a5 { width: 148mm; padding: 12mm; }

        /* Header band */
        .mj-header { background-color: #E8481C; color: #fff; text-align: center; padding: 10px 0; margin-bottom: 6px; }
        .mj-header h1 { margin: 0; font-size: 24pt; font-family: 'Noto Nastaliq Urdu', serif; font-weight: 700; }
        .mj-header p { margin: 0; font-size: 14pt; font-weight: 700; }

        .contact-row { text-align: center; font-size: 8pt; font-family: 'Noto Nastaliq Urdu', serif; padding: 2px 0; border-bottom: 1px solid #000; margin-bottom: 4px; }
        .address-line { text-align: center; font-size: 8pt; margin-bottom: 6px; }

        .meta-block { border: 1px solid #000; padding: 6px; margin-bottom: 10px; }
        .meta-table { width: 100%; border-collapse: collapse; }
        .meta-table td { font-size: 9pt; padding: 4px 6px; vertical-align: middle; }
        .meta-label { font-family: 'Noto Nastaliq Urdu', serif; text-align: right; padding-left: 6px; }
        .meta-value { text-align: left; font-weight: 700; }

        .calc-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .calc-table td { padding: 4px 6px; font-size: 10pt; }
        .calc-val { text-align: left; width: 40%; font-family: 'JetBrains Mono', monospace; font-weight: 700; }
        .calc-label { text-align: right; width: 60%; font-family: 'Noto Nastaliq Urdu', serif; }
        .line-separator { border-top: 1px dashed #000; margin: 6px 0; }

        .received-section, .wasooli-section { border-radius: 4px; padding: 6px; margin: 6px 0; }
        .received-section { background: rgba(16,185,129,0.08); border: 1px solid #10b981; }
        .wasooli-section { background: rgba(59,130,246,0.08); border: 1px solid #3b82f6; }

        .balance-summary { border: 2px solid #000; padding: 8px; margin-top: 10px; background: #fff9c4; }
        .balance-row { display: flex; justify-content: space-between; padding: 2px 0; font-size: 10pt; }
        .balance-row.total { border-top: 2px solid #000; padding-top: 6px; margin-top: 6px; font-weight: 700; font-size: 12pt; }

        .print-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .print-table th, .print-table td { padding: 6px 8px; font-size: 10pt; }
        .print-table tr + tr td { border-top: 1px solid rgba(0,0,0,0.08); }

        .print-remarks { margin-top: 8px; font-size: 9pt; border-top: 1px dashed #000; padding-top: 6px; }

        .footer-section { margin-top: 14px; border-top: 1px solid #000; padding-top: 6px; text-align: center; font-size: 7pt; }

        @media print { .no-print { display: none !important; } .mj-header { -webkit-print-color-adjust: exact; } .received-section, .wasooli-section, .balance-summary { -webkit-print-color-adjust: exact; } }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="padding:16px;">
        <button onclick="window.print()"><i class="bi bi-printer"></i> Print</button> | 
        <a href="<?= url('invoices/print.php?id=' . $id . '&format=a5') ?>" style="margin:0 4px;">A5 Format</a>
        <a href="<?= url('invoices/print.php?id=' . $id . '&format=slip') ?>" style="margin:0 4px;">Slip Format</a>
        <a href="<?= url('invoices/show.php?id=' . $id) ?>">Back</a>
    </div>
    <div class="print-container <?= $format === 'a5' ? 'format-a5' : 'format-slip' ?>">
        <div class="mj-header">
            <h1 class="urdu-text"><?= htmlspecialchars($workshopNameUrdu) ?></h1>
            <p><?= htmlspecialchars($workshopName) ?></p>
        </div>

        <?php if ($workshopPhone || $workshopPhone2): ?>
        <div class="contact-row">
            <?= htmlspecialchars($workshopPhone ?: '') ?> <?= $workshopPhone && $workshopPhone2 ? ' | ' : '' ?> <?= htmlspecialchars($workshopPhone2 ?: '') ?>
        </div>
        <?php endif; ?>

        <?php if ($workshopAddress): ?>
        <div class="address-line"><?= htmlspecialchars($workshopAddress) ?></div>
        <?php endif; ?>

        <div class="meta-block">
            <table class="meta-table">
                <tr>
                    <td class="meta-value"><?= htmlspecialchars($invoice['id']) ?></td>
                    <td class="meta-label">نمبر</td>
                    <td class="meta-value" style="text-align:right;"><?= htmlspecialchars($invoice['customer_name'] ?? '') ?></td>
                </tr>
                <tr>
                    <td class="meta-value">0</td>
                    <td class="meta-label">اینٹری</td>
                    <td class="meta-value" style="text-align:right;"><?= htmlspecialchars($invoice['customer_phone'] ?? '') ?> <span class="meta-label">فون نمبر</span></td>
                </tr>
                <tr>
                    <td class="meta-value"><?= formatDate($invoice['invoice_date']) ?></td>
                    <td class="meta-label">تاریخ</td>
                    <td class="meta-value" style="text-align:right;"><?= htmlspecialchars($invoice['invoice_no']) ?> <span class="meta-label">بل نمبر</span></td>
                </tr>
                <tr>
                    <td class="meta-value"><?= date('h:i A', strtotime($invoice['created_at'])) ?></td>
                    <td class="meta-label">وقت</td>
                    <td class="meta-value" style="text-align:right;"><?= number_format($invoice['rp_rate'], 2) ?> <span class="meta-label">میل:</span></td>
                </tr>
                <?php if ($invoice['manual_book_no']): ?>
                <tr>
                    <td colspan="2"></td>
                    <td class="meta-value" style="text-align:right;"><?= htmlspecialchars($invoice['manual_book_no']) ?> <span class="meta-label">کتاب نمبر</span></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>

        <table class="calc-table">
            <tr><td class="calc-val"><?= number_format($invoice['casting_weight'], 3) ?></td><td class="calc-label">کاسٹنگ وزن</td></tr>
            <tr><td class="calc-val"><?= number_format($invoice['waste_weight'], 3) ?></td><td class="calc-label">ویسٹ</td></tr>
            <tr><td class="calc-val"><strong><?= number_format($invoice['total_weight'], 3) ?></strong></td><td class="calc-label">ٹوٹل سونا پاؤنڈ</td></tr>
            <tr><td class="calc-val"><?= number_format($invoice['male_waste'], 3) ?></td><td class="calc-label">میل کاٹ</td></tr>
            <tr><td class="calc-val"><strong><?= number_format($invoice['gold_khalis'], 3) ?></strong></td><td class="calc-label">خالص سونا</td></tr>
            <tr><td class="calc-val"><?= number_format($invoice['rp_mazdori_weight'], 3) ?> <?php if ($invoice['ratti'] > 0) echo '<span style="font-size:7pt;vertical-align:top;">' . htmlspecialchars($invoice['ratti']) . '</span>'; ?></td><td class="calc-label">اجرت کا پاسہ</td></tr>
            <tr><td class="calc-val"><?= number_format($invoice['casting_mazdori_weight'], 3) ?></td><td class="calc-label">کاسٹنگ مزدوری وزن</td></tr>
            <tr style="border-top:1px solid #000;"><td class="calc-val"><strong><?= number_format($invoice['effective_gold'], 3) ?></strong></td><td class="calc-label">ٹوٹل ایفیکٹو گولڈ</td></tr>
        </table>

        <?php if ((float)$invoice['total_received_khalis'] > 0): ?>
        <div class="received-section">
            <table class="calc-table" style="margin:0;">
                <tr><td class="calc-val" style="color:#10b981;">+ <?= number_format($invoice['total_received_khalis'], 3) ?></td><td class="calc-label">وصولی (سونے میں)</td></tr>
            </table>
        </div>
        <?php endif; ?>

        <?php if ((float)$invoice['wasooli'] > 0): ?>
        <div class="wasooli-section">
            <table class="calc-table" style="margin:0;">
                <tr><td class="calc-val" style="color:#3b82f6;">- <?= number_format($invoice['wasooli'], 3) ?></td><td class="calc-label">وصولی (کیش میں)</td></tr>
            </table>
        </div>
        <?php endif; ?>

        <div class="balance-summary">
            <div style="text-align:center;font-weight:bold;margin-bottom:6px;font-family:'Noto Nastaliq Urdu',serif;">حساب کتاب - بیلنس</div>
            <div class="balance-row"><span>پچھلا بیلنس:</span><span><?= number_format($invoice['previous_balance'], 3) ?> g</span></div>
            <div class="balance-row"><span>+ ایفیکٹو گولڈ (دیا گیا):</span><span style="color:#daa520;">+ <?= number_format($invoice['effective_gold'], 3) ?> g</span></div>
            <?php if ((float)$invoice['total_received_khalis'] > 0): ?><div class="balance-row"><span>- وصولی (سونے میں):</span><span style="color:#10b981;">- <?= number_format($invoice['total_received_khalis'], 3) ?> g</span></div><?php endif; ?>
            <?php if ((float)$invoice['wasooli'] > 0): ?><div class="balance-row"><span>- وصولی (کیش):</span><span style="color:#3b82f6;">- <?= number_format($invoice['wasooli'], 3) ?> g</span></div><?php endif; ?>
            <div class="balance-row total"><span>باقی بیلنس:</span><span style="color:<?= $invoice['remaining_balance'] > 0 ? '#dc2626' : '#10b981' ?>;"><?= number_format($invoice['remaining_balance'], 3) ?> g</span></div>
            <div style="text-align:center;margin-top:6px;font-size:8pt;color:#666;"><?= $invoice['remaining_balance'] > 0 ? 'پارٹی آپ کی مقروض ہے' : 'آپ پارٹی کے مقروض ہیں' ?></div>
        </div>

        <?php if (!empty($receives)): ?>
        <div style="margin-top:10px;border:1px solid #000;padding:6px;">
            <div style="font-weight:bold;margin-bottom:6px;font-family:'Noto Nastaliq Urdu',serif;">تفصیل وصولی (سونے کی)</div>
            <table class="calc-table" style="width:100%;font-size:9pt;">
                <thead>
                    <tr style="border-bottom:1px solid #000;"><th style="text-align:left;">تفصیل</th><th style="text-align:right;">گراس</th><th style="text-align:right;">خالص</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($receives as $rec): ?>
                    <tr>
                        <td><?= htmlspecialchars($rec['description'] ?? 'Gold') ?></td>
                        <td style="text-align:right;font-family:monospace;"><?= number_format($rec['gross_weight'], 3) ?></td>
                        <td style="text-align:right;font-family:monospace;color:#10b981;"><?= number_format($rec['khalis_weight'], 3) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($invoice['remarks']): ?>
        <div class="print-remarks"><strong>نوٹ:</strong> <?= htmlspecialchars($invoice['remarks']) ?></div>
        <?php endif; ?>

        <div class="footer-section">
            <div class="footer-disclaimer">براہ کرم مال وصول کرتے وقت چیک کریں بعد میں شکایت قابل قبول نہیں</div>
            <div>Messenger: <?= htmlspecialchars($workshopPhone ?: '0322-6796306') ?></div>
            <div><?= htmlspecialchars($workshopName) ?> یوٹیوب چینل</div>
            <div style="margin-top:6px;font-size:7pt;color:#666;">Printed: <?= date('d-m-Y h:i A') ?> | Invoice ID: <?= htmlspecialchars($invoice['id']) ?></div>
        </div>
    </div>
</body>
</html>