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
    <style>
        body { margin: 0; padding: 0; background: #fff; color: #000; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-print-color-adjust: exact; }
        .print-container { margin: 0 auto; }
        .format-slip { width: 80mm; padding: 3mm; }
        .format-a5 { width: 148mm; padding: 8mm; }
        .print-header { text-align: center; border-bottom: 2px double #000; padding-bottom: 12px; margin-bottom: 12px; }
        .print-header h2 { margin: 0; font-size: 18px; }
        .print-header h3 { margin: 4px 0; font-size: 14px; }
        .print-header p { margin: 2px 0; font-size: 10px; }
        .print-table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        .print-table th, .print-table td { border: 1px solid #333; padding: 4px 6px; text-align: left; font-size: 10px; }
        .print-table th { background: #eee; }
        .print-info { margin-top: 12px; }
        .print-info div { display: flex; justify-content: space-between; padding: 2px 0; font-size: 10px; }
        .print-remarks { margin-top: 8px; font-size: 10px; }
        .no-print { text-align: center; margin-bottom: 16px; }
        .no-print button { padding: 10px 24px; font-size: 14px; cursor: pointer; }
        @media print { .no-print { display: none !important; } @page { margin: 0; } }
        .urdu-text { font-family: 'Noto Nastaliq Urdu', serif; direction: rtl; }
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
        <div class="print-header">
            <h2><?= htmlspecialchars($workshopName) ?></h2>
            <h3 class="urdu-text"><?= htmlspecialchars($workshopNameUrdu) ?></h3>
            <?php if ($workshopAddress): ?><p><?= htmlspecialchars($workshopAddress) ?></p><?php endif; ?>
            <?php if ($workshopPhone || $workshopCity): ?><p>Phone: <?= htmlspecialchars($workshopPhone) ?><?= $workshopCity ? ', ' . htmlspecialchars($workshopCity) : '' ?></p><?php endif; ?>
        </div>

        <div style="display:flex;justify-content:space-between;">
            <div><strong>Invoice:</strong> <?= htmlspecialchars($invoice['invoice_no']) ?></div>
            <div><strong>Date:</strong> <?= formatDate($invoice['invoice_date']) ?></div>
        </div>
        <div style="font-size:10px;margin-top:4px;">
            <strong>Party:</strong> <?= htmlspecialchars($invoice['customer_name'] ?? '') ?><br>
            <?php if ($invoice['manual_book_no']): ?><strong>Book:</strong> <?= htmlspecialchars($invoice['manual_book_no']) ?><?php endif; ?>
        </div>

        <table class="print-table">
            <tr><th>Description</th><th>Weight (g)</th></tr>
            <tr><td>Casting Weight</td><td class="text-right"><?= number_format($invoice['casting_weight'], 3) ?></td></tr>
            <tr><td>+ Waste Weight (Casting ÷ 10 × Ratti Rate)</td><td class="text-right"><?= number_format($invoice['waste_weight'], 3) ?></td></tr>
            <tr><td><strong>= Total Weight</strong></td><td class="text-right"><strong><?= number_format($invoice['total_weight'], 3) ?></strong></td></tr>
            <tr><td>- Male Waste (Total ÷ 96 × Ratti)</td><td class="text-right"><?= number_format($invoice['male_waste'], 3) ?></td></tr>
            <tr><td><strong>= Gold Khalis</strong></td><td class="text-right"><strong><?= number_format($invoice['gold_khalis'], 3) ?></strong></td></tr>
            <?php if ($invoice['rp_mazdori_weight'] > 0): ?><tr><td>+ RP Mazdori Weight</td><td class="text-right"><?= number_format($invoice['rp_mazdori_weight'], 3) ?></td></tr><?php endif; ?>
            <?php if ($invoice['casting_mazdori_weight'] > 0): ?><tr><td>+ Casting Mazdori Weight</td><td class="text-right"><?= number_format($invoice['casting_mazdori_weight'], 3) ?></td></tr><?php endif; ?>
            <tr style="font-weight:700;border-top:2px solid #000;"><td>Effective Gold</td><td class="text-right"><?= number_format($invoice['effective_gold'], 3) ?> g</td></tr>
        </table>

        <?php if (!empty($receives)): ?>
        <table class="print-table">
            <tr><th>Received</th><th>Gross (g)</th><th>Ratti</th><th>Khalis (g)</th></tr>
            <?php foreach ($receives as $rec): ?>
            <tr>
                <td><?= htmlspecialchars($rec['description'] ?? '-') ?></td>
                <td class="text-right"><?= number_format($rec['gross_weight'], 3) ?></td>
                <td class="text-right"><?= number_format($rec['ratti_impurity'], 3) ?></td>
                <td class="text-right"><?= number_format($rec['khalis_weight'], 3) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight:700;">
                <td>Total Received</td><td></td><td></td>
                <td class="text-right"><?= number_format(array_sum(array_column($receives, 'khalis_weight')), 3) ?></td>
            </tr>
        </table>
        <?php endif; ?>

        <div class="print-info">
            <div><span>Previous Balance:</span><span><?= number_format($invoice['previous_balance'], 3) ?> g</span></div>
            <div><span>+ Effective Gold:</span><span><?= number_format($invoice['effective_gold'], 3) ?> g</span></div>
            <div><span>- Wasooli:</span><span><?= number_format($invoice['wasooli'], 3) ?> g</span></div>
            <div><span>- Received:</span><span><?= number_format($invoice['total_received_khalis'], 3) ?> g</span></div>
            <div style="font-weight:700;border-top:1px solid #000;padding-top:2px;">
                <span>Remaining Balance:</span>
                <span><?= number_format($invoice['remaining_balance'], 3) ?> g</span>
            </div>
        </div>

        <?php if ($invoice['remarks']): ?>
        <div class="print-remarks"><strong>Remarks:</strong> <?= htmlspecialchars($invoice['remarks']) ?></div>
        <?php endif; ?>

        <div style="margin-top:16px;text-align:center;font-size:8px;">
            <p>Generated by M.J Casting Management System</p>
        </div>
    </div>
</body>
</html>