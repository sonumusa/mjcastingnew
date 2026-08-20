<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

ensureInvoiceMultipleDateColumns();

$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT im.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address, c.city as customer_city
                      FROM invoice_multiple im
                      LEFT JOIN customers c ON c.id = im.customer_id
                      WHERE im.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();
if (!$invoice) {
    redirect('invoice-multiple/index.php');
}

$stmt = $db->prepare("SELECT *, COALESCE(item_date, ?) AS row_date
                      FROM invoice_multiple_items
                      WHERE invoice_multiple_id = ?
                      ORDER BY COALESCE(item_date, ?), id");
$stmt->execute([$invoice['invoice_date'], $id, $invoice['invoice_date']]);
$items = $stmt->fetchAll();

$stmt = $db->prepare("SELECT *, COALESCE(receive_date, ?) AS row_date
                      FROM invoice_multiple_receives
                      WHERE invoice_multiple_id = ?
                      ORDER BY COALESCE(receive_date, ?), id");
$stmt->execute([$invoice['invoice_date'], $id, $invoice['invoice_date']]);
$receives = $stmt->fetchAll();

$workshopName = getSetting('workshop_name', 'M.J Casting');
$workshopNameUrdu = getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ');
$workshopAddress = getSetting('address', '');
$workshopPhone = getSetting('phone', '');
$workshopPhone2 = getSetting('phone2', '');
$format = query('format', 'a4');

function mpWeight($value): string {
    return number_format((float)$value, 3, '.', '');
}
function mpRatti($value): string {
    $v = (float)$value;
    return abs($v - round($v)) < 0.0001 ? number_format($v, 0) : number_format($v, 1);
}
function mpBalanceWord($value): string {
    $v = (float)$value;
    if ($v > 0.0005) return 'لینا';
    if ($v < -0.0005) return 'جمع';
    return 'صاف';
}
function mpBalanceClass($value): string {
    $v = (float)$value;
    if ($v > 0.0005) return 'balance-lena';
    if ($v < -0.0005) return 'balance-jama';
    return 'balance-clear';
}

$sequence = [];
foreach ($items as $it) {
    $sequence[] = [
        'date' => $it['row_date'] ?: $invoice['invoice_date'],
        'sort' => strtotime($it['row_date'] ?: $invoice['invoice_date']) . '-1-' . str_pad((string)$it['id'], 8, '0', STR_PAD_LEFT),
        'kind' => 'given',
        'urdu_label' => 'سونا دیا',
        'simple_text' => 'ہم نے کاریگر کو سونا دیا',
        'description' => $it['description'] ?: '-',
        'main_weight' => (float)$it['casting_weight'],
        'ratti' => (float)$it['ratti'],
        'khalis' => (float)$it['gold_khalis'],
        'rp_weight' => (float)($it['rp_mazdori_weight'] ?? 0),
        'casting_mazdori_weight' => (float)($it['casting_mazdori_weight'] ?? 0),
        'given' => (float)$it['effective_gold'],
        'received' => 0.0,
    ];
}
foreach ($receives as $r) {
    $sequence[] = [
        'date' => $r['row_date'] ?: $invoice['invoice_date'],
        'sort' => strtotime($r['row_date'] ?: $invoice['invoice_date']) . '-2-' . str_pad((string)$r['id'], 8, '0', STR_PAD_LEFT),
        'kind' => 'received',
        'urdu_label' => 'سونا وصول',
        'simple_text' => 'کاریگر سے سونا واپس وصول کیا',
        'description' => $r['description'] ?: '-',
        'main_weight' => (float)$r['gross_weight'],
        'ratti' => (float)$r['ratti_impurity'],
        'khalis' => (float)$r['khalis_weight'],
        'rp_weight' => 0.0,
        'casting_mazdori_weight' => 0.0,
        'given' => 0.0,
        'received' => (float)$r['khalis_weight'],
    ];
}
usort($sequence, fn($a, $b) => strcmp($a['sort'], $b['sort']));

$running = (float)$invoice['previous_balance'];
foreach ($sequence as &$row) {
    $running = round($running + $row['given'] - $row['received'], 3);
    $row['balance'] = $running;
}
unset($row);

$totalGiven = array_sum(array_column($sequence, 'given'));
$totalReceived = array_sum(array_column($sequence, 'received'));
$wasooli = (float)($invoice['wasooli'] ?? 0);
$finalBalance = round((float)$invoice['previous_balance'] + $totalGiven - $totalReceived - $wasooli, 3);

$showRpWeight = array_sum(array_column($sequence, 'rp_weight')) > 0.0005;
$showCastingMazdoriWeight = array_sum(array_column($sequence, 'casting_mazdori_weight')) > 0.0005;
$totalColumns = 7 + ($showRpWeight ? 1 : 0) + ($showCastingMazdoriWeight ? 1 : 0);
$middleColspan = 2 + ($showRpWeight ? 1 : 0) + ($showCastingMazdoriWeight ? 1 : 0);
$totalLabelColspan = $totalColumns - 3;

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Karigar Hisab - <?= htmlspecialchars($invoice['invoice_no']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;600;700&display=swap" rel="stylesheet">
<style>
@page { size: 145mm 200mm; margin: 0; }
* { box-sizing: border-box; }
body { margin:0; padding:0; background:#f5f5f5; color:#000; font-family: Arial, sans-serif; -webkit-print-color-adjust: exact; display:flex; flex-direction:column; align-items:center; justify-content:center; }
.urdu { font-family:'Noto Nastaliq Urdu', serif; direction:rtl; }
.print-container { width:145mm; height:200mm; margin:0 auto; background:#fff; font-size:8.5px; padding:3mm; overflow:hidden; box-shadow:0 0 20px rgba(0,0,0,0.15); }
.header { border:2px solid #16395f; border-radius:8px; overflow:hidden; display:grid; grid-template-columns:70px 1fr 118px; min-height:48px; margin-bottom:4px; }
.logo-box { background:#14395f; color:#d9b14a; display:flex; flex-direction:column; align-items:center; justify-content:center; border-right:3px solid #d9b14a; }
.logo-square { width:36px; height:28px; border:3px solid #d9b14a; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:800; letter-spacing:1px; margin-bottom:4px; }
.logo-small { font-size:6px; letter-spacing:1.2px; font-weight:700; }
.header-center { text-align:center; padding:4px 8px; display:flex; flex-direction:column; justify-content:center; }
.header-center h1 { margin:0; font-size:19px; letter-spacing:2px; color:#11395c; line-height:1; }
.header-center .addr { font-size:8px; margin-top:3px; }
.header-center .phone { font-size:8px; font-weight:700; margin-top:2px; }
.header-urdu { background:#14395f; color:#d9b14a; display:flex; flex-direction:column; align-items:center; justify-content:center; padding:3px; border-left:3px solid #d9b14a; }
.header-urdu .main { font-size:12px; font-weight:700; line-height:1.5; }
.header-urdu .sub { font-size:8px; line-height:1.4; }
.title-strip { background:#222; color:#fff; text-align:center; padding:3px; font-weight:800; font-size:8.5px; letter-spacing:.5px; }
.title-strip .urdu { font-size:9px; }
.meta { display:grid; grid-template-columns:1.2fr 1fr 1fr 1fr; border:2px solid #000; border-top:0; margin-bottom:4px; }
.meta div { padding:3px 5px; border-left:1px solid #000; min-height:24px; }
.meta div:first-child { border-left:0; }
.meta .label { color:#666; font-size:8px; display:block; }
.meta .value { font-size:9px; font-weight:800; display:block; margin-top:2px; }
.explain { border:2px solid #1f4e79; background:#eaf3ff; padding:4px 8px; margin:8px 0; font-weight:700; font-size:8.5px; text-align:center; }
.big-summary { display:grid; grid-template-columns:repeat(4, 1fr); gap:8px; margin:10px 0; }
.sum-card { border:2px solid #000; padding:8px; text-align:center; background:#fffdf0; min-height:62px; }
.sum-card .label { font-size:13px; color:#444; }
.sum-card .value { font-size:22px; font-weight:900; margin-top:3px; }
.sum-card.given { background:#fff1f1; border-color:#b91c1c; }
.sum-card.received { background:#effaf2; border-color:#047857; }
.sum-card.final { background:#fff7cc; border-color:#b8860b; }
.table-title { background:#16395f; color:#fff; text-align:center; padding:4px; font-weight:800; margin-top:5px; font-size:12px; }
table { width:100%; border-collapse:collapse; }
.hisab-table th { background:#d9b14a; color:#000; border:1.5px solid #000; padding:2px 2px; font-size:7.5px; text-align:center; }
.hisab-table td { border:1px solid #000; padding:2px 2px; font-size:7.5px; vertical-align:middle; }
.hisab-table tbody tr:nth-child(even) { background:#f8f8f8; }
.badge { display:inline-block; padding:2px 5px; border-radius:4px; color:#fff; font-weight:800; min-width:52px; text-align:center; }
.badge.given { background:#b91c1c; }
.badge.received { background:#047857; }
.num { font-weight:800; font-size:8.5px; text-align:right; font-family:Arial, sans-serif; }
.center { text-align:center; }
.details { font-size:7px; color:#333; line-height:1.15; }
.given-text { color:#b91c1c; }
.received-text { color:#047857; }
.balance-lena { color:#b91c1c; font-weight:900; }
.balance-jama { color:#047857; font-weight:900; }
.balance-clear { color:#111; font-weight:900; }
.total-row td { background:#e5e5e5; font-weight:900; border-top:3px solid #000; }
.final-box { border:2px solid #000; background:#fff9c4; margin-top:5px; padding:5px 8px; }
.final-line { display:flex; justify-content:space-between; align-items:center; padding:4px 0; font-size:9px; font-weight:800; }
.final-line.total { border-top:2px solid #000; margin-top:3px; padding-top:4px; font-size:12px; }
.note { border:1px solid #000; padding:3px; text-align:center; margin-top:5px; font-size:8px; color:#444; }
.footer { text-align:center; margin-top:4px; font-size:7px; color:#666; }
.no-print { padding:12px; background:#f1f5f9; text-align:center; }
.no-print button, .no-print a { margin:0 4px; padding:7px 12px; font-weight:700; text-decoration:none; }
@media print { .no-print { display:none!important; } .header, .title-strip, .table-title, .sum-card, .final-box { -webkit-print-color-adjust: exact; } }
@media print { body { background:#fff; align-items:flex-start; justify-content:flex-start; } .print-container { box-shadow:none; } }

</style>
</head>
<body>
<div class="no-print">
    <button onclick="window.print()">Print</button>
    <button onclick="exportPdf()">Export PDF</button>
    <button onclick="exportJpg()">Export JPG</button>
    <a href="<?= url('invoice-multiple/show.php?id=' . $id) ?>">Back</a>
</div>

<div class="print-container">
    <div class="header">
        <div class="logo-box">
            <div class="logo-square">MJ</div>
            <div class="logo-small">GOLD CASTING</div>
        </div>
        <div class="header-center">
            <h1>MJ CASTING</h1>
            <?php if ($workshopAddress): ?><div class="addr urdu"><?= htmlspecialchars($workshopAddress) ?></div><?php endif; ?>
            <div class="phone"><?= htmlspecialchars($workshopPhone ?: '') ?> <?= $workshopPhone && $workshopPhone2 ? ' | ' : '' ?> <?= htmlspecialchars($workshopPhone2 ?: '') ?></div>
        </div>
        <div class="header-urdu urdu">
            <div class="main"><?= htmlspecialchars($workshopNameUrdu) ?></div>
            <div class="sub">گولڈ کاسٹنگ اینڈ جیولری ورکس</div>
        </div>
    </div>

    <div class="title-strip"><span class="urdu">کاریگر کا سادہ سونا حساب</span> — MULTIPLE INVOICE</div>

    <div class="meta">
        <div><span class="label urdu">کاریگر / پارٹی</span><span class="value"><?= htmlspecialchars($invoice['customer_name'] ?? '-') ?></span></div>
        <div><span class="label urdu">بل نمبر</span><span class="value"><?= htmlspecialchars($invoice['invoice_no']) ?></span></div>
        <div><span class="label urdu">تاریخ</span><span class="value"><?= formatDate($invoice['invoice_date']) ?></span></div>
        <div><span class="label urdu">بک نمبر</span><span class="value"><?= htmlspecialchars($invoice['manual_book_no'] ?: '-') ?></span></div>
    </div>

    <div class="table-title urdu">تاریخ وار سونا دیا / وصول کیا</div>
    <table class="hisab-table">
        <thead>
            <tr>
                <th class="urdu">تاریخ</th>
                <th class="urdu">کام</th>
                <th class="urdu">تفصیل</th>
                <th class="urdu">وزن / رتی</th>
                <?php if ($showRpWeight): ?><th class="urdu">آر پی وزن</th><?php endif; ?>
                <?php if ($showCastingMazdoriWeight): ?><th class="urdu">کاسٹنگ مزدوری وزن</th><?php endif; ?>
                <th class="urdu">سونا دیا</th>
                <th class="urdu">سونا وصول</th>
                <th class="urdu">باقی حساب</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sequence)): ?>
                <tr><td colspan="<?= $totalColumns ?>" class="center urdu">کوئی اندراج موجود نہیں</td></tr>
            <?php else: ?>
                <tr style="background:#fff9c4;font-weight:900;">
                    <td class="center">-</td>
                    <td class="center"><span class="badge" style="background:#111;">سابقہ</span></td>
                    <td class="urdu" colspan="<?= $middleColspan ?>" style="text-align:right;">سابقہ اوپننگ بیلنس / پچھلا حساب</td>
                    <td class="num">-</td>
                    <td class="num">-</td>
                    <td class="num <?= mpBalanceClass($invoice['previous_balance']) ?>"><?= mpWeight(abs((float)$invoice['previous_balance'])) ?> <span class="urdu"><?= mpBalanceWord($invoice['previous_balance']) ?></span></td>
                </tr>
                <?php foreach ($sequence as $row): ?>
                <tr>
                    <td class="center"><?= formatDate($row['date']) ?></td>
                    <td class="center"><span class="badge <?= $row['kind'] === 'given' ? 'given' : 'received' ?> urdu"><?= htmlspecialchars($row['urdu_label']) ?></span></td>
                    <td>
                        <div class="details" style="font-weight:700;color:#111;"><?= htmlspecialchars($row['description']) ?></div>
                    </td>
                    <td class="center">
                        <div class="num"><?= mpWeight($row['main_weight']) ?> g</div>
                        <?php if ($row['ratti'] > 0): ?><div class="details urdu">رتی: <?= mpRatti($row['ratti']) ?></div><?php endif; ?>
                        <?php if ($row['khalis'] > 0): ?><div class="details urdu">خالص: <?= mpWeight($row['khalis']) ?> g</div><?php endif; ?>
                    </td>
                    <?php if ($showRpWeight): ?><td class="num"><?= !empty($row['rp_weight']) && $row['rp_weight'] > 0 ? mpWeight($row['rp_weight']) : '-' ?></td><?php endif; ?>
                    <?php if ($showCastingMazdoriWeight): ?><td class="num"><?= !empty($row['casting_mazdori_weight']) && $row['casting_mazdori_weight'] > 0 ? mpWeight($row['casting_mazdori_weight']) : '-' ?></td><?php endif; ?>
                    <td class="num given-text"><?= $row['given'] > 0 ? mpWeight($row['given']) : '-' ?></td>
                    <td class="num received-text"><?= $row['received'] > 0 ? mpWeight($row['received']) : '-' ?></td>
                    <td class="num <?= mpBalanceClass($row['balance']) ?>"><?= mpWeight(abs($row['balance'])) ?> <span class="urdu"><?= mpBalanceWord($row['balance']) ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="<?= $totalLabelColspan ?>" class="right urdu">ٹوٹل</td>
                <td class="num given-text"><?= mpWeight($totalGiven) ?></td>
                <td class="num received-text"><?= mpWeight($totalReceived) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="final-box">
        <div class="final-line"><span class="urdu">پچھلا بیلنس</span><span class="<?= mpBalanceClass($invoice['previous_balance']) ?>"><?= mpWeight(abs((float)$invoice['previous_balance'])) ?> g <?= mpBalanceWord($invoice['previous_balance']) ?></span></div>
        <div class="final-line"><span class="urdu">+ کل سونا دیا</span><span class="given-text">+ <?= mpWeight($totalGiven) ?> g</span></div>
        <div class="final-line"><span class="urdu">- کل سونا وصول</span><span class="received-text">- <?= mpWeight($totalReceived) ?> g</span></div>
        <?php if ($wasooli > 0): ?><div class="final-line"><span class="urdu">- وصولی / کیش</span><span>- <?= mpWeight($wasooli) ?> g</span></div><?php endif; ?>
        <div class="final-line total"><span class="urdu">باقی بیلنس</span><span class="<?= mpBalanceClass($finalBalance) ?>"><?= mpWeight(abs($finalBalance)) ?> g <?= mpBalanceWord($finalBalance) ?></span></div>
    </div>

    <div class="note urdu">نوٹ: پٹھور سونے کی کوئی گارنٹی نہیں ہے، براہ کرم مال وصول کرتے وقت چیک کریں بعد میں شکایت قابل قبول نہیں۔</div>
    <div class="footer">Printed: <?= date('d-m-Y h:i A') ?> | <?= htmlspecialchars($workshopName) ?> | <?= htmlspecialchars($invoice['invoice_no']) ?></div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const exportName = <?= json_encode($invoice['invoice_no']) ?>;
async function captureInvoice() {
    const el = document.querySelector('.print-container');
    if (!window.html2canvas) { alert('Export library not loaded. Please check internet connection.'); throw new Error('html2canvas missing'); }
    return await html2canvas(el, {scale: 3, backgroundColor: '#ffffff', useCORS: true});
}
async function exportJpg() { const c = await captureInvoice(); const a = document.createElement('a'); a.download = exportName + '.jpg'; a.href = c.toDataURL('image/jpeg', 0.95); a.click(); }
async function exportPdf() { const c = await captureInvoice(); if (!window.jspdf) { alert('PDF library not loaded.'); return; } const { jsPDF } = window.jspdf; const pdf = new jsPDF({orientation: c.width > c.height ? 'landscape' : 'portrait', unit: 'pt', format: [c.width, c.height]}); pdf.addImage(c.toDataURL('image/jpeg', 0.98), 'JPEG', 0, 0, c.width, c.height); pdf.save(exportName + '.pdf'); }
</script>
</body>
</html>
