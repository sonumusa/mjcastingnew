<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

ensureInvoiceMultipleDateColumns();
$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT im.*, c.*, c.name AS customer_name, c.phone AS customer_phone
                      FROM invoice_multiple im
                      LEFT JOIN customers c ON c.id = im.customer_id
                      WHERE im.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();
if (!$invoice) {
    setFlash('error', 'Multiple invoice not found.');
    redirect('invoice-multiple/index.php');
}

// Customer type check (normal vs dukandar)
$rawCustomerType = strtolower(trim((string)(
    $invoice['customer_type']
    ?? $invoice['type']
    ?? $invoice['party_type']
    ?? $invoice['account_type']
    ?? ''
)));
$isDukandar = ($rawCustomerType === 'dukandar');

// Customer name safe resolution
$customerName = trim((string)($invoice['customer_name'] ?? ''));
if ($customerName === '' && !empty($invoice['customer_id'])) {
    $cStmt = $db->prepare("SELECT name FROM customers WHERE id = ?");
    $cStmt->execute([(int)$invoice['customer_id']]);
    $cRow = $cStmt->fetch();
    if ($cRow && trim((string)$cRow['name']) !== '') {
        $customerName = trim((string)$cRow['name']);
    }
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

function pw($value): string {
    $v = round((float)$value, 3);
    if (abs($v) < 0.0005) {
        $v = 0.0;
    }
    return number_format($v, 3, '.', '');
}

function pwg($value): string {
    return pw($value) . ' g';
}

function pRatti($value): string {
    $v = abs((float)$value);
    if ($v < 0.0001) {
        return '';
    }
    if (abs($v - round($v)) < 0.0001) {
        return number_format($v, 0, '.', '');
    }
    return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
}

function pPoints($rattiVal, bool $isDukandar = false): string {
    $r = (float)$rattiVal;
    if ($r < -0.0001) {
        $pts = round(abs($r) * 100, 1);
        $fmt = rtrim(rtrim(number_format($pts, 1, '.', ''), '0'), '.');
        return $fmt . ' Point add';
    }
    $maxLimit = $isDukandar ? 4.9999 : 0.9999;
    if ($r > 0.0001 && $r < $maxLimit) {
        $pts = round($r * 100, 1);
        $fmt = rtrim(rtrim(number_format($pts, 1, '.', ''), '0'), '.');
        return $fmt . ' Point less';
    }
    return '';
}

function pd($value): string {
    return $value ? date('d-m-y', strtotime($value)) : '-';
}

function pt($value): string {
    $s = trim((string)$value);
    return htmlspecialchars($s !== '' ? $s : '-', ENT_QUOTES, 'UTF-8');
}

function balStatusInfo(float $bal): array {
    if ($bal > 0.0005) {
        return ['tag' => 'لینا', 'class' => 'bal-lena'];
    }
    if ($bal < -0.0005) {
        return ['tag' => 'جمع', 'class' => 'bal-jama'];
    }
    return ['tag' => 'برابر', 'class' => 'bal-zero'];
}

/* ---- Build 4 Sections Data ---- */
$castingGivenRows = [];
$castingReturnRows = [];
$goldGivenRows = [];

$castingGivenTotal = 0.0;
$castingReturnTotal = 0.0;
$goldGivenTotal = 0.0;

$hasRpGiven = false;
$hasRpReturn = false;
$hasPointsGoldGiven = false;

foreach ($items as $item) {
    $casting = (float)($item['casting_weight'] ?? 0);
    $effGold = (float)($item['effective_gold'] ?? 0);
    $date = pd($item['row_date'] ?? '');
    $desc = pt($item['description'] ?? '');

    $rattiRateRaw = (float)(
        $item['ratti_impurity']
        ?? $item['ratti']
        ?? $item['ratti_rate']
        ?? $item['rate']
        ?? 0
    );

    // 1. Casting / Tiyar Mall Diya & 3. Casting Wapsi / Tiyar Mall Wapsi
    $isCastingRow = false;
    if ($isDukandar) {
        $isCastingRow = (abs($casting) > 0.0001);
    } else {
        $isCastingRow = (abs($casting) > 0.0001 && abs($rattiRateRaw) > 0.0001);
    }

    if ($isCastingRow) {
        $qtyAbs = abs($casting);
        $waste = (float)($item['waste_weight'] ?? $item['waste'] ?? $item['casting_mazdori_weight'] ?? 0);
        $rp = (float)($item['rp_mazdori_weight'] ?? $item['rp_weight'] ?? $item['rp'] ?? 0);

        $khalisVal = abs($effGold) > 0.0001
            ? abs($effGold)
            : abs((float)($item['gold_khalis'] ?? $casting));

        $mail = isset($item['mail_weight'])
            ? abs((float)$item['mail_weight'])
            : max(0.0, round($qtyAbs - $khalisVal, 3));

        $rowArr = [
            'date'   => $date,
            'desc'   => $desc,
            'qty'    => pwg($qtyAbs),
            'waste'  => pwg(abs($waste)),
            'ratti'  => pRatti($rattiRateRaw),
            'mail'   => pwg($mail),
            'rp'     => abs($rp) > 0.0001 ? pwg(abs($rp)) : '',
            'khalis' => pwg($khalisVal),
        ];

        if ($casting > 0) {
            if (abs($rp) > 0.0001) {
                $hasRpGiven = true;
            }
            $castingGivenRows[] = $rowArr;
            $castingGivenTotal += $khalisVal;
        } elseif ($casting < 0) {
            if (abs($rp) > 0.0001) {
                $hasRpReturn = true;
            }
            $castingReturnRows[] = $rowArr;
            $castingReturnTotal += $khalisVal;
        }
    }

    // 2. Gold Diya (Only for Normal Karigar when ratti/rate == 0, NEVER for Dukandar)
    if (!$isDukandar) {
        $isGoldDiyaRow = (abs($rattiRateRaw) <= 0.0001) && (abs($effGold) > 0.0001 || abs($casting) > 0.0001);
        if ($isGoldDiyaRow) {
            $gQty = abs((float)($item['gold_khalis'] ?? 0));
            if ($gQty <= 0.0001) {
                $gQty = abs($effGold) > 0.0001 ? abs($effGold) : abs($casting);
            }
            $gKhalis = abs($effGold) > 0.0001 ? abs($effGold) : $gQty;
            $gMail = max(0.0, round($gQty - $gKhalis, 3));
            $ptsText = pPoints($rattiRateRaw, false);
            if ($ptsText !== '') {
                $hasPointsGoldGiven = true;
            }

            $goldGivenRows[] = [
                'date'   => $date,
                'desc'   => $desc,
                'qty'    => pwg($gQty),
                'points' => $ptsText,
                'ratti'  => pRatti($rattiRateRaw),
                'mail'   => pwg($gMail),
                'khalis' => pwg($gKhalis),
            ];
            $goldGivenTotal += $gKhalis;
        }
    }
}

// 4. Gold Wasool (Received)
$goldReceivedRows = [];
$goldReceivedTotal = 0.0;
$hasPointsReceived = false;

foreach ($receives as $receive) {
    $gross = (float)($receive['gross_weight'] ?? 0);
    $ratti = (float)($receive['ratti_impurity'] ?? 0);
    $khalis = (float)($receive['khalis_weight'] ?? 0);
    $mail = max(0.0, round(abs($gross) - abs($khalis), 3));
    $ptsText = pPoints($ratti, $isDukandar);
    if ($ptsText !== '') {
        $hasPointsReceived = true;
    }

    $goldReceivedRows[] = [
        'date'   => pd($receive['row_date'] ?? ''),
        'desc'   => pt($receive['description'] ?? ''),
        'qty'    => pwg(abs($gross)),
        'points' => $ptsText,
        'ratti'  => pRatti($ratti),
        'mail'   => pwg($mail),
        'khalis' => pwg(abs($khalis)),
    ];
    $goldReceivedTotal += abs($khalis);
}

$prevBalance = (float)($invoice['previous_balance'] ?? 0);
$wasooli = (float)($invoice['wasooli'] ?? 0);
$finalBalance = (float)($invoice['remaining_balance'] ?? ($prevBalance + $castingGivenTotal + $goldGivenTotal - $castingReturnTotal - $goldReceivedTotal - $wasooli));

$prevBalInfo = balStatusInfo($prevBalance);
$closingInfo = balStatusInfo($finalBalance);
$closingTag = $closingInfo['tag'];
$closingClass = $closingInfo['class'];

$workshopName = getSetting('workshop_name', 'M.J Casting');
$workshopPhone = getSetting('phone', '');

// Labels based on Customer Type
$sec1En = $isDukandar ? 'Tiyar Mall Diya' : 'Casting';
$sec1Ur = $isDukandar ? 'تیار مال دیا' : 'کاسٹنگ';

$sec3En = $isDukandar ? 'Tiyar Mall Wapsi' : 'Casting Wapsi';
$sec3Ur = $isDukandar ? 'تیار مال واپسی' : 'کاسٹنگ واپسی';

// Auto-hide empty sections
$showSec1 = count($castingGivenRows) > 0;
$showSec2 = !$isDukandar && count($goldGivenRows) > 0;
$showSec3 = count($castingReturnRows) > 0;
$showSec4 = count($goldReceivedRows) > 0;

$secNum = 1;
$num1 = $showSec1 ? $secNum++ : 0;
$num2 = $showSec2 ? $secNum++ : 0;
$num3 = $showSec3 ? $secNum++ : 0;
$num4 = $showSec4 ? $secNum++ : 0;

// Running Subtotals for Summary
$subTotalAfterDiya = $prevBalance + ($showSec1 ? $castingGivenTotal : 0.0) + ($showSec2 ? $goldGivenTotal : 0.0);
$subTotalDiyaInfo = balStatusInfo($subTotalAfterDiya);
$showSubTotalDiya = ($showSec1 || $showSec2);

$subTotalAfterWapsi = $subTotalAfterDiya - ($showSec3 ? $castingReturnTotal : 0.0);
$subTotalWapsiInfo = balStatusInfo($subTotalAfterWapsi);
$showSubTotalWapsi = $showSec3 && ($showSec4 || abs($wasooli) >= 0.0005);

// Dukandar hides Waste column in Tiyar Mall Diya
$showWasteSec1 = !$isDukandar;
$showWasteSec3 = !$isDukandar;
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bill - <?= pt($invoice['invoice_no']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<style>
@page { size: A5 portrait; margin: 5mm; }
* { box-sizing: border-box; }
body {
    margin: 0;
    color: #111;
    font: 12px/1.35 'Segoe UI', Arial, sans-serif;
    background: #e9edf0;
}
/* Urdu font reduced 1 step */
.ur {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 0.85em;
    line-height: 1.35;
}
.toolbar {
    width: 148mm;
    margin: 10px auto;
    text-align: center;
}
.toolbar button, .toolbar a {
    display: inline-block;
    padding: 8px 14px;
    border: 0;
    border-radius: 4px;
    background: #173d5d;
    color: #fff;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    margin: 0 3px;
}
.toolbar button.btn-pdf { background: #a1382d; }
.toolbar button.btn-jpg { background: #176b48; }
.toolbar button:disabled { opacity: 0.65; cursor: wait; }

.page {
    width: 148mm;
    min-height: 210mm;
    margin: 10px auto;
    padding: 5mm;
    background: #fff;
    position: relative;
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 5px;
    border-bottom: 2px solid #173d5d;
}
.header h1 {
    margin: 0;
    font-size: 20px;
    color: #173d5d;
}
.header small {
    color: #444;
    font-size: 11px;
    font-weight: 600;
}
.bill-badge {
    background: #173d5d;
    color: #fff;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 700;
}

/* Meta Info */
.meta {
    display: grid;
    grid-template-columns: 1.8fr 1fr 1fr 0.9fr;
    gap: 5px;
    margin: 6px 0 8px;
}
.meta div {
    padding: 4px 6px;
    border: 1px solid #c5cdd4;
    border-radius: 3px;
    background: #fafbfc;
}
.meta small {
    display: block;
    font-size: 9.5px;
    color: #555;
    margin-bottom: 1px;
}
.meta strong {
    display: block;
    font-size: 12.5px;
    color: #111;
    overflow-wrap: anywhere;
}

/* Final Summary (Top) */
.summary-wrap {
    margin: 0 0 8px;
    border: 2px solid #173d5d;
    border-radius: 5px;
    overflow: hidden;
    background: #fff;
}
.sum-head {
    background: #173d5d;
    color: #fff;
    padding: 5px 9px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 13px;
    font-weight: 700;
}
.sum-body {
    padding: 4px 9px;
}
.sum-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    border-bottom: 1px solid #e2e8f0;
    font-size: 12.5px;
}
.sum-row:last-child {
    border-bottom: none;
}
.sum-row.sum-subtotal {
    justify-content: flex-end;
    background: #f1f5f9;
    margin: 2px -9px;
    padding: 4px 9px;
    border-top: 1.8px dotted #64748b;
    border-bottom: 1.8px dotted #64748b;
}
.sum-subtotal-group {
    display: flex;
    align-items: center;
    gap: 7px;
}
.sum-subtotal-lbl {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}
.sum-lbl {
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    color: #1e293b;
}
.ref-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    color: #fff;
    font-size: 11px;
    font-weight: 800;
}
.bg-1 { background: #176b48; }
.bg-2 { background: #0369a1; }
.bg-3 { background: #b45309; }
.bg-4 { background: #6b4678; }
.bg-prev { background: #64748b; }

.sum-val {
    font-size: 13.5px;
    font-weight: 800;
    white-space: nowrap;
}
.val-s1 { color: #176b48; }
.val-s2 { color: #0369a1; }
.val-s3 { color: #b45309; }
.val-s4 { color: #6b4678; }

.bal-inline {
    display: flex;
    align-items: center;
    gap: 6px;
}
.bal-status-pill {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 10.5px;
    font-weight: 700;
    padding: 0 6px;
    border-radius: 4px;
    line-height: 1.4;
}
.bal-lena .bal-status-pill { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.bal-jama .bal-status-pill { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.bal-zero .bal-status-pill { background: #e2e8f0; color: #334155; border: 1px solid #cbd5e1; }

.closing-bar {
    background: #f1f5f9;
    border-top: 2px solid #173d5d;
    padding: 6px 9px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.closing-title {
    font-size: 14.5px;
    font-weight: 800;
    color: #173d5d;
}
.closing-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.closing-num {
    font-size: 17px;
    font-weight: 900;
}
.closing-status {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 11.5px;
    font-weight: 700;
    padding: 0 8px;
    border-radius: 4px;
    line-height: 1.45;
}
.bal-lena { color: #b91c1c; }
.bal-lena .closing-status { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
.bal-jama { color: #15803d; }
.bal-jama .closing-status { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
.bal-zero { color: #1e293b; }
.bal-zero .closing-status { background: #e2e8f0; color: #334155; }

/* Section Card (Default / Dukandar height preserved) */
.sec {
    margin-bottom: 8px;
    border: 1.4px solid #b8c2cc;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
}
.sec-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 4px 8px;
    color: #fff;
    font-weight: 700;
    font-size: 12.5px;
}
.sec-left {
    display: flex;
    align-items: center;
    gap: 7px;
}
.s-num {
    display: inline-flex;
    width: 19px;
    height: 19px;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #fff;
    color: #111;
    font-size: 12px;
    font-weight: 800;
}

.c1 { border-color: #176b48; } .c1 .sec-head { background: #176b48; }
.c2 { border-color: #0369a1; } .c2 .sec-head { background: #0369a1; }
.c3 { border-color: #b45309; } .c3 .sec-head { background: #b45309; }
.c4 { border-color: #6b4678; } .c4 .sec-head { background: #6b4678; }

/* Table Grid - Default / Dukandar Row Height */
table {
    width: 100%;
    border-collapse: collapse;
}
th, td {
    padding: 4px 5px;
    border: 1px solid #d5dce2;
    text-align: center;
    vertical-align: middle;
}
th {
    background: #f1f4f7;
    font-size: 10px;
    font-weight: 700;
    color: #334155;
}
td {
    font-size: 12.5px;
    font-weight: 600;
    color: #111;
}
.col-date {
    width: 17mm;
    white-space: nowrap;
    font-size: 11.5px;
    color: #333;
}
.col-desc {
    text-align: left;
    font-size: 12.5px;
}
.col-num {
    text-align: right;
    white-space: nowrap;
}
.col-khalis {
    text-align: right;
    white-space: nowrap;
    font-size: 13px;
    font-weight: 800;
    color: #000;
}
.col-pts {
    color: #b91c1c;
    font-weight: 700;
    font-size: 11px;
    white-space: nowrap;
}

tr.total-row td {
    background: #f8fafc;
    border-top: 1.5px solid #cbd5e1;
    font-weight: 800;
    padding: 4px 6px;
}
.tot-lbl {
    text-align: right;
    font-size: 12px;
    color: #334155;
}
.tot-val {
    text-align: right;
    font-size: 14px;
    font-weight: 900;
    white-space: nowrap;
}
.t-c1 { color: #176b48; }
.t-c2 { color: #0369a1; }
.t-c3 { color: #b45309; }
.t-c4 { color: #6b4678; }

/* Karigar Only: Reduced Vertical Row Height (same fonts) */
body.party-karigar .ur {
    line-height: 1.1;
}
body.party-karigar .sum-head {
    padding: 3px 8px;
}
body.party-karigar .sum-body {
    padding: 2px 8px;
}
body.party-karigar .sum-row {
    padding: 1.5px 0;
    line-height: 1.15;
}
body.party-karigar .sum-row.sum-subtotal {
    margin: 1px -8px;
    padding: 1.5px 8px;
}
body.party-karigar .closing-bar {
    padding: 3px 8px;
    line-height: 1.15;
}
body.party-karigar .sec {
    margin-bottom: 5px;
}
body.party-karigar .sec-head {
    padding: 2px 7px;
    line-height: 1.15;
}
body.party-karigar th,
body.party-karigar td {
    padding: 1px 4px;
    line-height: 1.12;
}
body.party-karigar tr.total-row td {
    padding: 1.5px 5px;
    line-height: 1.15;
}

.footer {
    margin-top: 7px;
    text-align: center;
    color: #64748b;
    font-size: 9.5px;
}

@media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .page { width: auto; min-height: 0; margin: 0; padding: 0; }
    .sec { overflow: visible; }
    .sec-head { break-after: avoid; page-break-after: avoid; }
    thead { display: table-header-group; }
    tr, .summary-wrap { break-inside: avoid; page-break-inside: avoid; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body class="<?= $isDukandar ? 'party-dukandar' : 'party-karigar' ?>">

<div class="toolbar">
    <button type="button" onclick="window.print()">Print</button>
    <button type="button" class="btn-pdf" id="btnExportPdf" onclick="exportBill('pdf')">Export PDF</button>
    <button type="button" class="btn-jpg" id="btnExportJpg" onclick="exportBill('jpg')">Export JPG</button>
    <a href="<?= url('invoice-multiple/index.php') ?>">Back to list</a>
</div>

<main class="page" id="billPage">
    <header class="header">
        <div>
            <h1><?= pt($workshopName) ?></h1>
            <small><?= $workshopPhone ? 'Phone: ' . pt($workshopPhone) : 'Hisab Bill' ?></small>
        </div>
        <div class="bill-badge">Bill # <?= pt($invoice['invoice_no']) ?></div>
    </header>

    <div class="meta">
        <div>
            <small>Party / <span class="ur">پارٹی</span></small>
            <strong><?= pt($customerName) ?></strong>
        </div>
        <div>
            <small>Tareekh / <span class="ur">تاریخ</span></small>
            <strong><?= pd($invoice['invoice_date']) ?></strong>
        </div>
        <div>
            <small>Bill No</small>
            <strong><?= pt($invoice['invoice_no']) ?></strong>
        </div>
        <div>
            <small>Book No</small>
            <strong><?= pt($invoice['manual_book_no']) ?></strong>
        </div>
    </div>

    <!-- FINAL SUMMARY (TOP) -->
    <div class="summary-wrap">
        <div class="sum-head">
            <span>Final Summary (Khalis)</span>
            <span class="ur" style="font-size:10.5px;">آخری حساب (خالص)</span>
        </div>
        <div class="sum-body">
            <div class="sum-row">
                <div class="sum-lbl">
                    <span class="ref-badge bg-prev">&bull;</span>
                    <span>Sabqa / Previous Balance (<span class="ur">سابقہ</span>)</span>
                </div>
                <div class="sum-val bal-inline <?= $prevBalInfo['class'] ?>">
                    <span class="bal-status-pill"><?= $prevBalInfo['tag'] ?></span>
                    <span><?= pwg(abs($prevBalance)) ?></span>
                </div>
            </div>

            <?php if ($showSec1): ?>
            <div class="sum-row">
                <div class="sum-lbl">
                    <span class="ref-badge bg-1"><?= $num1 ?></span>
                    <span>+ <?= $sec1En ?> Total Khalis (<span class="ur"><?= $sec1Ur ?> جمع</span>)</span>
                </div>
                <div class="sum-val val-s1">+ <?= pwg($castingGivenTotal) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($showSec2): ?>
            <div class="sum-row">
                <div class="sum-lbl">
                    <span class="ref-badge bg-2"><?= $num2 ?></span>
                    <span>+ Gold Diya Total Khalis (<span class="ur">سونا دیا جمع</span>)</span>
                </div>
                <div class="sum-val val-s2">+ <?= pwg($goldGivenTotal) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($showSubTotalDiya): ?>
            <div class="sum-row sum-subtotal">
                <div class="sum-subtotal-group">
                    <span class="sum-subtotal-lbl">Sub Total (<span class="ur">میزان</span>) :</span>
                    <div class="sum-val bal-inline <?= $subTotalDiyaInfo['class'] ?>">
                        <span class="bal-status-pill"><?= $subTotalDiyaInfo['tag'] ?></span>
                        <span><?= pwg(abs($subTotalAfterDiya)) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($showSec3): ?>
            <div class="sum-row">
                <div class="sum-lbl">
                    <span class="ref-badge bg-3"><?= $num3 ?></span>
                    <span>- <?= $sec3En ?> Total Khalis (<span class="ur"><?= $sec3Ur ?> منفی</span>)</span>
                </div>
                <div class="sum-val val-s3">- <?= pwg($castingReturnTotal) ?></div>
            </div>
            <?php endif; ?>

            <?php if ($showSubTotalWapsi): ?>
            <div class="sum-row sum-subtotal">
                <div class="sum-subtotal-group">
                    <span class="sum-subtotal-lbl">Sub Total (<span class="ur">میزان</span>) :</span>
                    <div class="sum-val bal-inline <?= $subTotalWapsiInfo['class'] ?>">
                        <span class="bal-status-pill"><?= $subTotalWapsiInfo['tag'] ?></span>
                        <span><?= pwg(abs($subTotalAfterWapsi)) ?></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($showSec4): ?>
            <div class="sum-row">
                <div class="sum-lbl">
                    <span class="ref-badge bg-4"><?= $num4 ?></span>
                    <span>- Gold Wasool Total Khalis (<span class="ur">وصولی منفی</span>)</span>
                </div>
                <div class="sum-val val-s4">- <?= pwg($goldReceivedTotal) ?></div>
            </div>
            <?php endif; ?>

            <?php if (abs($wasooli) >= 0.0005): ?>
            <div class="sum-row">
                <div class="sum-lbl">
                    <span class="ref-badge bg-4">&minus;</span>
                    <span>- Cash / Other Wasooli (<span class="ur">نقد وصولی منفی</span>)</span>
                </div>
                <div class="sum-val val-s4">- <?= pwg(abs($wasooli)) ?></div>
            </div>
            <?php endif; ?>
        </div>

        <div class="closing-bar <?= $closingClass ?>">
            <div class="closing-title">Closing Khalis</div>
            <div class="closing-right">
                <span class="closing-status"><?= $closingTag ?></span>
                <span class="closing-num"><?= pwg(abs($finalBalance)) ?></span>
            </div>
        </div>
    </div>

    <!-- 1. CASTING / TIYAR MALL DIYA -->
    <?php if ($showSec1): ?>
    <section class="sec c1">
        <div class="sec-head">
            <div class="sec-left">
                <span class="s-num"><?= $num1 ?></span>
                <span><?= $sec1En ?> / <span class="ur"><?= $sec1Ur ?></span></span>
            </div>
            <span class="ur" style="font-size:9.5px;"><?= $sec1Ur ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-desc">Detail</th>
                    <th class="col-num">Qty</th>
                    <?php if ($showWasteSec1): ?><th class="col-num">Waste</th><?php endif; ?>
                    <th>Ratti</th>
                    <th class="col-num">Mail</th>
                    <?php if ($hasRpGiven): ?><th class="col-num">RP</th><?php endif; ?>
                    <th class="col-khalis">Khalis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($castingGivenRows as $r): ?>
                <tr>
                    <td class="col-date"><?= $r['date'] ?></td>
                    <td class="col-desc"><?= $r['desc'] ?></td>
                    <td class="col-num"><?= $r['qty'] ?></td>
                    <?php if ($showWasteSec1): ?><td class="col-num"><?= $r['waste'] ?></td><?php endif; ?>
                    <td><?= $r['ratti'] ?></td>
                    <td class="col-num"><?= $r['mail'] ?></td>
                    <?php if ($hasRpGiven): ?><td class="col-num"><?= $r['rp'] ?></td><?php endif; ?>
                    <td class="col-khalis"><?= $r['khalis'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php
                $span1 = 5 + ($showWasteSec1 ? 1 : 0) + ($hasRpGiven ? 1 : 0);
                ?>
                <tr class="total-row">
                    <td colspan="<?= $span1 ?>" class="tot-lbl">Total Khalis:</td>
                    <td class="tot-val t-c1"><?= pwg($castingGivenTotal) ?></td>
                </tr>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- 2. GOLD DIYA (Normal Karigar Only when ratti/rate == 0) -->
    <?php if ($showSec2): ?>
    <section class="sec c2">
        <div class="sec-head">
            <div class="sec-left">
                <span class="s-num"><?= $num2 ?></span>
                <span>Gold Diya / <span class="ur">سونا دیا</span></span>
            </div>
            <span class="ur" style="font-size:9.5px;">سونا دیا</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-desc">Detail</th>
                    <th class="col-num">Qty</th>
                    <?php if ($hasPointsGoldGiven): ?><th>Points</th><?php endif; ?>
                    <th>Ratti</th>
                    <th class="col-num">Mail</th>
                    <th class="col-khalis">Khalis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($goldGivenRows as $r): ?>
                <tr>
                    <td class="col-date"><?= $r['date'] ?></td>
                    <td class="col-desc"><?= $r['desc'] ?></td>
                    <td class="col-num"><?= $r['qty'] ?></td>
                    <?php if ($hasPointsGoldGiven): ?><td class="col-pts"><?= $r['points'] ?></td><?php endif; ?>
                    <td><?= $r['ratti'] ?></td>
                    <td class="col-num"><?= $r['mail'] ?></td>
                    <td class="col-khalis"><?= $r['khalis'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php $span2 = 5 + ($hasPointsGoldGiven ? 1 : 0); ?>
                <tr class="total-row">
                    <td colspan="<?= $span2 ?>" class="tot-lbl">Total Khalis:</td>
                    <td class="tot-val t-c2"><?= pwg($goldGivenTotal) ?></td>
                </tr>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- 3. CASTING WAPSI / TIYAR MALL WAPSI -->
    <?php if ($showSec3): ?>
    <section class="sec c3">
        <div class="sec-head">
            <div class="sec-left">
                <span class="s-num"><?= $num3 ?></span>
                <span><?= $sec3En ?> / <span class="ur"><?= $sec3Ur ?></span></span>
            </div>
            <span class="ur" style="font-size:9.5px;"><?= $sec3Ur ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-desc">Detail</th>
                    <th class="col-num">Qty</th>
                    <?php if ($showWasteSec3): ?><th class="col-num">Waste</th><?php endif; ?>
                    <th>Ratti</th>
                    <th class="col-num">Mail</th>
                    <?php if ($hasRpReturn): ?><th class="col-num">RP</th><?php endif; ?>
                    <th class="col-khalis">Khalis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($castingReturnRows as $r): ?>
                <tr>
                    <td class="col-date"><?= $r['date'] ?></td>
                    <td class="col-desc"><?= $r['desc'] ?></td>
                    <td class="col-num"><?= $r['qty'] ?></td>
                    <?php if ($showWasteSec3): ?><td class="col-num"><?= $r['waste'] ?></td><?php endif; ?>
                    <td><?= $r['ratti'] ?></td>
                    <td class="col-num"><?= $r['mail'] ?></td>
                    <?php if ($hasRpReturn): ?><td class="col-num"><?= $r['rp'] ?></td><?php endif; ?>
                    <td class="col-khalis"><?= $r['khalis'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php $span3 = 5 + ($showWasteSec3 ? 1 : 0) + ($hasRpReturn ? 1 : 0); ?>
                <tr class="total-row">
                    <td colspan="<?= $span3 ?>" class="tot-lbl">Total Khalis:</td>
                    <td class="tot-val t-c3"><?= pwg($castingReturnTotal) ?></td>
                </tr>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <!-- 4. GOLD WASOOL -->
    <?php if ($showSec4): ?>
    <section class="sec c4">
        <div class="sec-head">
            <div class="sec-left">
                <span class="s-num"><?= $num4 ?></span>
                <span>Gold Wasool / <span class="ur">سونا وصول</span></span>
            </div>
            <span class="ur" style="font-size:9.5px;">سونا وصول</span>
        </div>
        <table>
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-desc">Detail</th>
                    <th class="col-num">Qty</th>
                    <?php if ($hasPointsReceived): ?><th>Points</th><?php endif; ?>
                    <th>Ratti</th>
                    <th class="col-num">Mail</th>
                    <th class="col-khalis">Khalis</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($goldReceivedRows as $r): ?>
                <tr>
                    <td class="col-date"><?= $r['date'] ?></td>
                    <td class="col-desc"><?= $r['desc'] ?></td>
                    <td class="col-num"><?= $r['qty'] ?></td>
                    <?php if ($hasPointsReceived): ?><td class="col-pts"><?= $r['points'] ?></td><?php endif; ?>
                    <td><?= $r['ratti'] ?></td>
                    <td class="col-num"><?= $r['mail'] ?></td>
                    <td class="col-khalis"><?= $r['khalis'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php $span4 = 5 + ($hasPointsReceived ? 1 : 0); ?>
                <tr class="total-row">
                    <td colspan="<?= $span4 ?>" class="tot-lbl">Total Khalis:</td>
                    <td class="tot-val t-c4"><?= pwg($goldReceivedTotal) ?></td>
                </tr>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <div class="footer">Print: <?= date('d-m-Y h:i A') ?> | Tamam wazan gram (g) mein hain</div>
</main>

<script>
async function exportBill(format) {
    const el = document.getElementById('billPage');
    const btnPdf = document.getElementById('btnExportPdf');
    const btnJpg = document.getElementById('btnExportJpg');
    const fileBase = 'Bill-<?= preg_replace('/[^A-Za-z0-9_-]/', '_', (string)($invoice['invoice_no'] ?? 'invoice')) ?>';

    if (typeof html2canvas === 'undefined') {
        alert('Export library is still loading. Please check internet connection and try again.');
        return;
    }

    btnPdf.disabled = true;
    btnJpg.disabled = true;

    try {
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }

        const canvas = await html2canvas(el, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff'
        });

        if (format === 'jpg') {
            const link = document.createElement('a');
            link.download = fileBase + '.jpg';
            link.href = canvas.toDataURL('image/jpeg', 0.95);
            link.click();
        } else if (format === 'pdf') {
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a5'
            });

            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const imgData = canvas.toDataURL('image/jpeg', 0.95);
            const imgWidth = pageWidth;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft > 1) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            pdf.save(fileBase + '.pdf');
        }
    } catch (err) {
        alert('Export failed: ' + err.message);
    } finally {
        btnPdf.disabled = false;
        btnJpg.disabled = false;
    }
}
</script>
</body>
</html>
