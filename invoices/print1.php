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

// Separate normal receives vs return casting rows
$normalReceives = [];
$normalReceivedTotal = 0.0;
$hasReturnCasting = false;
$retCastingSum = 0.0;
$retGrossSum = 0.0;
$retKhalisAfterRattiSum = 0.0;
$retRpWeightSum = 0.0;
$retRpAmountSum = 0.0;
$retTotalKhalisSum = 0.0;
$retRattiList = [];

foreach ($receives as $rec) {
    if (!empty($rec['return_casting_enabled'])) {
        $hasReturnCasting = true;
        $rcw = (float)($rec['return_casting_weight'] ?? 0);
        $rgw = (float)($rec['gross_weight'] ?? 0);
        $rri = (float)($rec['ratti_impurity'] ?? 0);
        $rrp = (float)($rec['return_rp_weight'] ?? 0);
        $rrpAmt = (float)($rec['return_rp_amount'] ?? 0);
        $rck = (float)($rec['return_casting_khalis'] ?? 0);

        $baseK = customRoundTo2(convertToKhalis($rgw, $rri));
        if ($rck <= 0 && ($baseK > 0 || $rrp > 0)) {
            $rck = round($baseK + $rrp, 3);
        }

        $retCastingSum += $rcw;
        $retGrossSum += $rgw;
        $retKhalisAfterRattiSum += $baseK;
        $retRpWeightSum += $rrp;
        $retRpAmountSum += $rrpAmt;
        $retTotalKhalisSum += $rck;

        if ($rri != 0) {
            $retRattiList[] = abs($rri - round($rri)) < 0.0001 ? number_format($rri, 0) : number_format($rri, 1);
        }
    } else {
        $normalReceives[] = $rec;
        $normalReceivedTotal += (float)($rec['khalis_weight'] ?? 0);
    }
}

$retCastingSum = round($retCastingSum, 3);
$retGrossSum = round($retGrossSum, 3);
$retWasteSum = round(max(0, $retGrossSum - $retCastingSum), 3);
$retKhalisAfterRattiSum = round($retKhalisAfterRattiSum, 3);
$retMaleWasteSum = round(max(0, $retGrossSum - $retKhalisAfterRattiSum), 3);
$retRpWeightSum = round($retRpWeightSum, 3);
$retRpAmountSum = round($retRpAmountSum, 2);
$retTotalKhalisSum = round($retTotalKhalisSum, 3);
$normalReceivedTotal = round($normalReceivedTotal, 3);
$retRattiDisplay = !empty($retRattiList) ? implode(', ', array_unique($retRattiList)) : '';

$hasGivenMazdori = ((float)$invoice['rp_mazdori_weight'] > 0) || ((float)$invoice['casting_mazdori_weight'] > 0);
$hasRetRp = ($retRpWeightSum > 0 || $retRpAmountSum > 0);

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

        .ur-rtl {
            direction: rtl;
            unicode-bidi: isolate;
        }

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

        /* PREMIUM HEADER */
        .premium-header-v1 {
            height: 68px;
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
        .header-left {
            width: 105px;
            height: 80%;
            background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            border-right: 3px solid #c79b2b;
            position: relative;
        }
        .logo-hexagon {
            width: 50px;
            height: 50px;
            border: 3px solid #d9b14a;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 4px;
            background: rgba(217, 177, 74, 0.1);
        }
        .logo-mj { font-size: 11px; font-weight: 800; color: #d9b14a; letter-spacing: 1px; }
        .logo-text { font-size: 7px; color: #d9b14a; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; }

        .header-center {
            flex: 1;
            background: linear-gradient(90deg, #fff 0%, #fefefe 100%);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0 18px;
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
        .company-name-en { font-size: 20pt; font-weight: 800; color: #0f2f4f; letter-spacing: 2px; }
        .contact-info { display: flex; align-items: center; flex-wrap: wrap; justify-content: center; }
        .contact-item { display: flex; align-items: center; gap: 4px; font-size: 6pt; font-weight: 600; color: #222; }
        .contact-item .icon { font-size: 7pt; color: #0f2f4f; }

        .header-right {
            width: 155px;
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
        .urdu-main { font-family: 'Noto Nastaliq Urdu', serif; font-size: 14pt; font-weight: 700; color: #d9b14a; line-height: 1.8; margin-bottom: 2px; }
        .urdu-sub { font-family: 'Noto Nastaliq Urdu', serif; font-size: 8pt; color: #d9b14a; line-height: 1.6; margin-bottom: 3px; }
        .urdu-divider { color: #d9b14a; font-size: 8pt; margin-top: 2px; }

        /* Combined Meta + Party Row (Auto-fit 5 columns, no blank gap) */
        .meta-row {
            display: grid;
            grid-template-columns: 1.15fr 1.1fr 0.7fr 0.75fr 1.55fr;
            align-items: center;
            border: 1px solid #000;
            border-top: none;
            background: #fafafa;
            flex-shrink: 0;
        }
        .meta-item {
            text-align: center;
            padding: 2px 4px;
            border-right: 1px solid #d4d4d8;
            min-width: 0;
        }
        .meta-item:last-child {
            border-right: none;
        }
        .meta-item .label {
            font-size: 7pt;
            color: #555;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.9;
            direction: rtl;
        }
        .meta-item .value {
            font-weight: 700;
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-size: 8.5pt;
            white-space: nowrap;
        }
        .meta-item.party-col {
            padding: 2px 6px;
        }
        .meta-item.party-col .value {
            font-size: 9.5pt;
            font-weight: 800;
            color: #0f2f4f;
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.15;
        }

        /* Main table - Exact 25% x 4 grid so Weight column aligns with Balance Box */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
            font-size: 9pt;
            table-layout: fixed;
            flex-shrink: 0;
        }
        .main-table col.col-w { width: 25%; }
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
            font-size: 8.5pt;
            line-height: 2.0;
            direction: rtl;
        }
        .main-table td {
            border: 1px solid #000;
            padding: 2.5px 4px;
            text-align: center;
            vertical-align: middle;
        }
        .main-table td.desc {
            text-align: right;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 8.5pt;
            padding-right: 5px;
            line-height: 2.0;
            direction: rtl;
        }
        .main-table td.number {
            font-family: 'Segoe UI', Arial, sans-serif; 
            font-weight: 700;
            font-size: 9pt;
            text-align: right;
            padding-right: 10px;
        }
        .main-table td.center-val {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-weight: 600;
            font-size: 9pt;
            text-align: center;
        }
        .main-table td.remark {
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 8pt;
            direction: rtl;
        }
        .main-table tr.highlight td {
            background: #f0f0f0;
            font-weight: 700;
            line-height: 2.0;
        }
        .main-table tr.total-row td {
            background: #d0d0d0;
            font-weight: 800;
            border-top: 1.5px solid #000;
            line-height: 2.0;
        }

        /* Wapsi (Return Casting) Left Columns Styling (Teal/Emerald Theme) */
        .main-table.has-wapsi {
            border: 1.5px solid #0d6e5b;
        }
        .main-table.has-wapsi tr.group-banner th.wapsi-banner {
            background: #0d6e5b;
            color: #fff;
            font-family: 'Noto Nastaliq Urdu', 'Segoe UI', sans-serif;
            font-size: 8.5pt;
            line-height: 1.9;
            border: 1px solid #0d6e5b;
            border-right: 2.5px solid #0f2f4f;
            direction: rtl;
        }
        .main-table.has-wapsi tr.group-banner th.casting-banner {
            background: #0f2f4f;
            color: #f3d58a;
            font-family: 'Noto Nastaliq Urdu', 'Segoe UI', sans-serif;
            font-size: 8.5pt;
            line-height: 1.9;
            border: 1px solid #0f2f4f;
            direction: rtl;
        }
        .main-table.has-wapsi th.wapsi-th {
            background: #d9f2ec;
            color: #064e3b;
            border-color: #5ea897;
        }
        .main-table.has-wapsi th.wapsi-split,
        .main-table.has-wapsi td.wapsi-split {
            border-right: 2.5px solid #0f2f4f !important;
        }
        .main-table.has-wapsi td.wapsi-val {
            background: #f2fbf9;
            color: #064e3b;
            border-color: #7ab8a8;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            font-size: 9pt;
            text-align: right;
            padding-right: 10px;
        }
        .main-table.has-wapsi td.wapsi-lbl {
            background: #e7f6f3;
            color: #064e3b;
            border-color: #7ab8a8;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-size: 8pt;
            text-align: right;
            padding-right: 5px;
            line-height: 2.0;
            direction: rtl;
        }
        .main-table.has-wapsi tr.highlight td.wapsi-val,
        .main-table.has-wapsi tr.highlight td.wapsi-lbl {
            background: #cfeee7;
        }
        .main-table.has-wapsi tr.total-row td.wapsi-val,
        .main-table.has-wapsi tr.total-row td.wapsi-lbl {
            background: #b5e2d8;
            color: #04392c;
            border-top: 1.5px solid #0d6e5b;
            font-weight: 800;
        }

        /* Balance box - Aligned with 25% columns above */
        .balance-box {
            border: 1.5px solid #000;
            margin-top: 2px;
            padding: 3px 10px;
            background: #fffef0;
            flex-shrink: 0;
        }
        .previous-balance-row {
            display: grid;
            grid-template-columns: 25% 25% 25% 25%;
            margin: -3px -10px 3px -10px;
            border-bottom: 1px solid #000;
            background: #fff3cd;
        }
        .previous-balance-row > div {
            padding: 4px 10px 4px 4px;
            display: flex;
            align-items: center;
        }
        .pb-value1 {
            justify-content: flex-end;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            font-size: 9pt;
            direction: ltr;
        }
        .pb-label1 {
            justify-content: flex-end;
            text-align: right;
            font-family: 'Noto Nastaliq Urdu', serif;
            font-weight: 700;
            font-size: 9pt;
            line-height: 1.3;
            direction: rtl;
        }
        .bal-grid-row {
            display: grid;
            grid-template-columns: 50% 25% 25%;
            align-items: center;
            margin: 0 -10px;
            padding: 1.5px 10px;
            font-size: 9pt;
        }
        .bal-grid-row .bal-left {
            text-align: left;
            min-width: 0;
        }
        .bal-grid-row .bal-val {
            text-align: right;
            padding-right: 10px;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            white-space: nowrap;
            direction: ltr;
        }
        .bal-grid-row .bal-lbl {
            text-align: right;
            font-family: 'Noto Nastaliq Urdu', serif;
            white-space: nowrap;
            direction: rtl;
        }
        .bal-grid-row.subtotal-wapsi {
            border-top: 1px dashed #64748b;
            border-bottom: 1px dashed #64748b;
            background: #f1f5f9;
            margin-top: 1px;
            margin-bottom: 1px;
            padding-top: 2px;
            padding-bottom: 2px;
        }
        .bal-grid-row.total {
            border-top: 1.5px solid #000;
            margin-top: 2px;
            padding-top: 3px;
            font-weight: 700;
            font-size: 10pt;
        }
        .balance-value.positive { color: #c00; }
        .balance-value.negative { color: #080; }

        .received-details-horizontal {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            font-size: 7pt;
            color: #444;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.4;
            justify-content: flex-start;
            direction: rtl;
        }
        .r-item {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 2px;
            direction: rtl;
        }
        .r-name { font-family: 'Noto Nastaliq Urdu', serif; font-weight: 600; color: #1e3a5f; }
        .r-ratti { font-size: 6pt; color: #888; font-style: italic; direction: ltr; unicode-bidi: isolate; }
        .r-gross, .r-khalis { direction: ltr; unicode-bidi: isolate; }
        .r-khalis { color: #080; font-weight: 700; }

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
            font-size: 9.5pt;
            background: #1e3a5f;
            color: #fff;
            padding: 2px;
            line-height: 2.0;
            direction: rtl;
        }
        .history-row { display: flex; border-bottom: 1px solid #1e3a5f; }
        .history-row:last-child { border-bottom: none; }
        .history-invoice { flex: 1; padding: 2px 10px; border-right: 1px solid #1e3a5f; }
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
        .history-invoice-header .inv-date { font-size: 6pt; color: #666; font-weight: 400; }
        .history-item {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
            font-size: 7pt;
            border-bottom: 1px dotted #ddd;
        }
        .history-item:last-child { border-bottom: none; }
        .history-item .hist-label { font-family: 'Noto Nastaliq Urdu', serif; color: #444; direction: rtl; }
        .history-item .hist-value { font-family: 'Noto Nastaliq Urdu', serif; font-weight: 600; direction: rtl; }
        .history-item.total-gold { background: #e8f0ff; padding: 1px 2px; margin: 1px -2px; border-radius: 2px; }
        .history-item.balance-due { background: #fff0e8; padding: 1px 2px; margin: 1px -2px; border-radius: 2px; }
        .history-item.balance-due .hist-value { color: #c00; font-weight: 700; }

        /* Footer note */
        .footer-note {
            border: 1px solid #000;
            border-top: none;
            padding: 2.5px;
            font-size: 7pt;
            text-align: center;
            color: #555;
            font-family: 'Noto Nastaliq Urdu', serif;
            line-height: 1.4;
            flex-shrink: 0;
            direction: rtl;
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
                padding: 3mm; 
                box-shadow: none;
            }
        }

        @media screen {
            body { padding-top: 50px; }
        }

        <?php if (query('bulk_jpg')): ?>
        .no-print { display: none !important; }
        body { padding-top: 0 !important; background: #fff !important; justify-content: flex-start !important; }
        .print-container { box-shadow: none !important; margin: 0 auto !important; }
        <?php endif; ?>
    </style>
</head>
<body>

    <div class="no-print" style="padding:16px;">
        <button onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
        <button onclick="exportPdf()">Export PDF</button>
        <button onclick="exportJpg()">Export JPG</button> |
        <a href="<?= url('invoices/index.php?id=' . $id) ?>">Back Invoice List</a>
    </div>

    <div class="print-container">

        <!-- PREMIUM HEADER -->
        <div class="premium-header-v1">
            <div class="header-left">
                <div class="logo-hexagon">
                    <div class="logo-mj">MJ</div>
                </div>
                <div class="logo-text">GOLD CASTING</div>
            </div>
            
            <div class="header-center">
                <div class="company-name-en">MJ CASTING</div>
                <div class="contact-info">
                    <div class="contact-item">
                        <span class="icon">📍</span>
                        <span><?= htmlspecialchars($workshopAddress) ?></span>
                    </div>
                    <div class="contact-item">
                        <span class="icon">📞</span>
                        <span><?= htmlspecialchars($workshopPhone) ?></span>
                        <span><?= htmlspecialchars($workshopPhone2) ?></span>
                        <span><?= htmlspecialchars($workshopPhone3) ?></span>
                    </div>
                </div>
            </div>
            
            <div class="header-right">
                <div class="urdu-main"><?= htmlspecialchars($workshopNameUrdu) ?></div>
                <div class="urdu-sub">گولڈ کاسٹنگ اینڈ جیولری ورکس</div>
                <div class="urdu-divider">◆◆◆</div>
            </div>
        </div>

        <!-- COMBINED META + PARTY ROW -->
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
                <div class="label">بل نمبر</div>
                <div class="value"><?= htmlspecialchars($invoice['manual_book_no'] ?: '-') ?></div>
            </div>
            <div class="meta-item">
                <div class="label">رتی اندر</div>
                <div class="value"><?= formatRattiPrint($invoice['ratti']) ?></div>
            </div>
            <div class="meta-item party-col">
                <div class="label">پارٹی</div>
                <div class="value"><?= htmlspecialchars($invoice['customer_name'] ?? 'Cash Party') ?></div>
            </div>
        </div>

        <!-- MAIN TABLE -->
        <?php if ($hasReturnCasting): ?>
        <table class="main-table has-wapsi">
            <colgroup>
                <col class="col-w"><col class="col-w"><col class="col-w"><col class="col-w">
            </colgroup>
            <thead>
                <tr class="group-banner">
                    <th colspan="2" class="wapsi-banner wapsi-split">
                        واپسی (Wapsi)<?= $retRattiDisplay !== '' ? ' &nbsp;|&nbsp; رتی: ' . htmlspecialchars($retRattiDisplay) : '' ?>
                    </th>
                    <th colspan="2" class="casting-banner">کاسٹنگ دیا (Casting)</th>
                </tr>
                <tr>
                    <th class="urdu wapsi-th">وزن (واپسی)</th>
                    <th class="urdu wapsi-th wapsi-split">نام (واپسی)</th>
                    <th class="urdu">وزن (گرام)</th>
                    <th class="urdu">نام اشیاء</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="wapsi-val"><?= number_format($retCastingSum, 3) ?></td>
                    <td class="wapsi-lbl wapsi-split">واپسی کاسٹنگ</td>
                    <td class="number"><?= number_format($invoice['casting_weight'], 3) ?></td>
                    <td class="desc">کاسٹنگ وزن (گرام)</td>
                </tr>
                <tr>
                    <td class="wapsi-val"><?= number_format($retWasteSum, 3) ?></td>
                    <td class="wapsi-lbl wapsi-split">واپسی ویسٹ</td>
                    <td class="number"><?= number_format($invoice['waste_weight'], 3) ?></td>
                    <td class="desc">ویسٹ (گرام)</td>
                </tr>
                <tr class="highlight">
                    <td class="wapsi-val"><?= number_format($retGrossSum, 3) ?></td>
                    <td class="wapsi-lbl wapsi-split">واپسی کل وزن</td>
                    <td class="number"><?= number_format($invoice['total_weight'], 3) ?></td>
                    <td class="desc">کل وزن (گرام)</td>
                </tr>
                <tr>
                    <td class="wapsi-val"><?= number_format($retMaleWasteSum, 3) ?></td>
                    <td class="wapsi-lbl wapsi-split">واپسی میل کاٹ</td>
                    <td class="number"><?= number_format($invoice['male_waste'], 3) ?></td>
                    <td class="desc">میل کاٹ (گرام)</td>
                </tr>
                <?php if ($hasGivenMazdori || $hasRetRp): ?>
                <tr>
                    <td class="wapsi-val"><?= number_format($retKhalisAfterRattiSum, 3) ?></td>
                    <td class="wapsi-lbl wapsi-split">واپسی خالص</td>
                    <td class="number"><?= number_format($invoice['gold_khalis'], 3) ?></td>
                    <td class="desc">خالص وزن (گرام)</td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['rp_mazdori_weight'] > 0 || $retRpWeightSum > 0 || $retRpAmountSum > 0): ?>
                <tr>
                    <td class="wapsi-val">
                        <?= $retRpWeightSum > 0 ? number_format($retRpWeightSum, 3) : '-' ?>
                        <?php if ($retRpAmountSum > 0): ?>
                            <span style="font-size:7pt;color:#064e3b;font-weight:600;">(Rs <?= number_format($retRpAmountSum, 0) ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="wapsi-lbl wapsi-split">واپسی آر پی</td>
                    <td class="number"><?= (float)$invoice['rp_mazdori_weight'] > 0 ? number_format($invoice['rp_mazdori_weight'], 3) : '-' ?></td>
                    <td class="desc">
                        آر پی مزدوری (گرام)
                        <?php if ((float)$invoice['rp_mazdori_amount'] > 0): ?>
                            <span style="font-family:'Segoe UI',Arial,sans-serif;font-size:7pt;font-weight:700;">(Rs <?= number_format($invoice['rp_mazdori_amount'], 0) ?>)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['casting_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="wapsi-val">-</td>
                    <td class="wapsi-lbl wapsi-split">-</td>
                    <td class="number"><?= number_format($invoice['casting_mazdori_weight'], 3) ?></td>
                    <td class="desc">
                        کاسٹنگ مزدوری (گرام)
                        <?php if ((float)$invoice['casting_mazdori_amount'] > 0): ?>
                            <span style="font-family:'Segoe UI',Arial,sans-serif;font-size:7pt;font-weight:700;">(Rs <?= number_format($invoice['casting_mazdori_amount'], 0) ?>)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="wapsi-val"><?= number_format($retTotalKhalisSum, 3) ?></td>
                    <td class="wapsi-lbl wapsi-split">ٹوٹل واپسی خالص</td>
                    <td class="number"><?= number_format($invoice['effective_gold'], 3) ?></td>
                    <td class="desc">خالص بیلنس (گرام)</td>
                </tr>
            </tbody>
        </table>
        <?php else: ?>
        <table class="main-table">
            <colgroup>
                <col class="col-w"><col class="col-w"><col class="col-w"><col class="col-w">
            </colgroup>
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
                    <td class="center-val">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['casting_weight'], 3) ?></td>
                    <td class="desc">کاسٹنگ وزن (گرام)</td>
                </tr>
                <tr>
                    <td class="center-val">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['waste_weight'], 3) ?></td>
                    <td class="desc">ویسٹ (گرام)</td>
                </tr>
                <tr class="highlight">
                    <td class="center-val">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['total_weight'], 3) ?></td>
                    <td class="desc">کل وزن (گرام)</td>
                </tr>
                <tr>
                    <td class="center-val">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['male_waste'], 3) ?></td>
                    <td class="desc">میل کاٹ (گرام)</td>
                </tr>
                <?php if ($hasGivenMazdori): ?>
                <tr>
                    <td class="center-val">-</td>
                    <td class="remark"></td>
                    <td class="number"><?= number_format($invoice['gold_khalis'], 3) ?></td>
                    <td class="desc">خالص وزن (گرام)</td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['rp_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="remark">آر پی مزدوری (روپے)</td>
                    <td class="center-val"><?= number_format($invoice['rp_mazdori_amount'], 0) ?></td>
                    <td class="number"><?= number_format($invoice['rp_mazdori_weight'], 3) ?></td>
                    <td class="desc">آر پی مزدوری (گرام)</td>
                </tr>
                <?php endif; ?>
                <?php if ((float)$invoice['casting_mazdori_weight'] > 0): ?>
                <tr>
                    <td class="remark">کاسٹنگ مزدوری (روپے)</td>
                    <td class="center-val"><?= number_format($invoice['casting_mazdori_amount'], 0) ?></td>
                    <td class="number"><?= number_format($invoice['casting_mazdori_weight'], 3) ?></td>
                    <td class="desc">کاسٹنگ مزدوری (گرام)</td>
                </tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td class="center-val">-</td>
                    <td class="remark">-</td>
                    <td class="number"><?= number_format($invoice['effective_gold'], 3) ?></td>
                    <td class="desc">خالص بیلنس (گرام)</td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>

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
                    $remBalStyle = 'background-color: #d4edda; color: #155724; padding-top: 4px; padding-bottom: 4px;';
                }
                $totalWithPrev = round((float)$invoice['effective_gold'] + $prevBal, 3);
                $afterWapsiSubTotal = round($totalWithPrev - $retTotalKhalisSum, 3);
            ?>
            
            <div class="previous-balance-row">
                <div></div>
                <div></div>
                <div class="pb-value1" style="padding-right:10px;">
                    <span class="ur-rtl"><?= $prevBalWord ?></span><?= number_format(abs($prevBal), 3) ?> g
                </div>
                <div class="pb-label1">سابقہ بیلنس</div>
            </div>

            <!-- Row 2: Total Khalis -->
            <div class="bal-grid-row">
                <div class="bal-left"></div>
                <div class="bal-val"><?= number_format($totalWithPrev, 3) ?> g</div>
                <div class="bal-lbl">+ ٹوٹل خالص:</div>
            </div>

            <!-- Wapsi Row + Subtotal after Wapsi -->
            <?php if ($hasReturnCasting): ?>
            <div class="bal-grid-row">
                <div class="bal-left"></div>
                <div class="bal-val" style="color:#0d6e5b;">- <?= number_format($retTotalKhalisSum, 3) ?> g</div>
                <div class="bal-lbl" style="color:#0d6e5b;font-weight:700;">- واپسی (کاسٹنگ):</div>
            </div>
            <div class="bal-grid-row subtotal-wapsi">
                <div class="bal-left"></div>
                <div class="bal-val" style="color:#0f2f4f;font-weight:800;"><?= number_format($afterWapsiSubTotal, 3) ?> g</div>
                <div class="bal-lbl" style="color:#0f2f4f;font-weight:700;">میزان (بعد واپسی):</div>
            </div>
            <?php endif; ?>

            <!-- Row 3: Received Gold (Normal Receives Only) -->
            <?php if (!empty($normalReceives)): ?>
            <div class="bal-grid-row">
                <div class="bal-left">
                    <div class="received-details-horizontal">
                        <?php foreach ($normalReceives as $rec): ?>
                            <?php 
                                $desc = htmlspecialchars($rec['description'] ?: 'خالص');
                                $gross = number_format($rec['gross_weight'], 3);
                                $ratti = formatRattiPrint($rec['ratti_impurity']);
                                $khalis = number_format($rec['khalis_weight'], 3);
                            ?>
                            <span class="r-item">{
                                <?php if ((float)$rec['ratti_impurity'] > 0): ?>
                                    <span class="r-name"><?= $desc ?></span>: <span class="r-gross"><?= $gross ?></span> (رتی <span class="r-ratti"><?= $ratti ?></span>) = <span class="r-khalis"><?= $khalis ?></span>
                                <?php else: ?>
                                    <span class="r-name"><?= $desc ?></span>: <span class="r-khalis"><?= $khalis ?> g</span>
                                <?php endif; ?>
                            },</span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bal-val"><?= number_format($normalReceivedTotal, 3) ?> g</div>
                <div class="bal-lbl">- وصولی (سونا):</div>
            </div>
            <?php elseif ($normalReceivedTotal > 0): ?>
            <div class="bal-grid-row">
                <div class="bal-left"></div>
                <div class="bal-val"><?= number_format($normalReceivedTotal, 3) ?> g</div>
                <div class="bal-lbl">- وصولی (سونا):</div>
            </div>
            <?php endif; ?>

            <!-- Row 4: Cash Received -->
            <?php if ((float)$invoice['wasooli'] > 0): ?>
            <div class="bal-grid-row">
                <div class="bal-left"></div>
                <div class="bal-val"><?= number_format($invoice['wasooli'], 3) ?> g</div>
                <div class="bal-lbl">- وصولی (کیش):</div>
            </div>
            <?php endif; ?>

            <!-- Row 5: Remaining Balance -->
            <div class="bal-grid-row total" style="<?= $remBalStyle ?>">
                <div class="bal-left"></div>
                <div class="bal-val balance-value <?= $remBalClass ?>">
                    <span class="ur-rtl"><?= $remBalWord ?></span><?= number_format(abs($remBal), 3) ?> g
                </div>
                <div class="bal-lbl">باقی بیلنس:</div>
            </div>
        </div>

        <!-- PREVIOUS INVOICES HISTORY -->
        <?php if (count($prevInvoices) > 0): ?>
        <div class="history-section">
            <div class="history-title">پچھلے انوائس</div>
            <div class="history-row">
                <?php foreach ($prevInvoices as $idx => $prev): ?>
                <div class="history-invoice">
                    <div class="history-invoice-header">
                        <?= htmlspecialchars($prev['invoice_no']) ?>
                        <span class="inv-date">(<?= date('d-m-Y', strtotime($prev['invoice_date'])) ?>)</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= formatRattiPrint($prev['ratti']) ?> &nbsp; رتی اندر</span>
                        <span class="hist-label">رتی اندر:</span>
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
                        <span class="hist-value"><?= number_format($prev['rp_mazdori_weight'] ?? 0, 3) ?> (گرام)</span>
                        <span class="hist-label">آر پی وزن:</span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><?= number_format($prev['casting_mazdori_weight'] ?? 0, 3) ?> (گرام)</span>
                        <span class="hist-label">کاسٹنگ مزدوری:</span>
                    </div>
                    <div class="history-item total-gold">
                        <span class="hist-value"><strong><?= number_format($prev['effective_gold'] ?? 0, 3) ?> (گرام)</strong></span>
                        <span class="hist-label"><strong>کل سونا:</strong></span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><strong><?= number_format($prev['total_received_khalis'] ?? 0, 3) ?> (گرام)</strong></span>
                        <span class="hist-label"><strong>وصولی:</strong></span>
                    </div>
                    <div class="history-item">
                        <span class="hist-value"><strong><?= number_format($prev['previous_balance'] ?? 0, 3) ?> (گرام)</strong></span>
                        <span class="hist-label"><strong>سابقہ بیلنس:</strong></span>
                    </div>
                    <div class="history-item balance-due">
                        <span class="hist-value"><strong><?= number_format($prev['remaining_balance'] ?? 0, 3) ?> (گرام)</strong></span>
                        <span class="hist-label"><strong>بقایا بیلنس:</strong></span>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (count($prevInvoices) < 2): ?>
                <div class="history-invoice" style="text-align:center; padding:10px; color:#999; font-size:7pt; font-family:'Noto Nastaliq Urdu',serif; direction:rtl;">
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
        <div class="footer-note" style="font-size:7px; direction:ltr;">
            <div>This document is computer generated and does not require any stamp/signature</div>
            <div style="text-align:center; margin-top:2px; font-size:6pt; color:#888;">
                <?= htmlspecialchars($workshopName) ?> | Printed: <?= date('d-m-Y h:i A') ?>
            </div>
        </div>

    </div>
    
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const exportName = <?= json_encode($invoice['invoice_no']) ?>;

function fixRtlBracketsForCanvas(rootEl) {
    const walker = document.createTreeWalker(rootEl, NodeFilter.SHOW_TEXT, null);
    const nodes = [];
    let n;
    while ((n = walker.nextNode())) {
        if (/[()]/.test(n.nodeValue) && /[\u0600-\u06FF]/.test(n.nodeValue)) {
            nodes.push(n);
        }
    }
    const backups = [];
    nodes.forEach(node => {
        backups.push([node, node.nodeValue]);
        node.nodeValue = node.nodeValue.replace(/\(/g, '\u200F(').replace(/\)/g, ')\u200F');
    });
    return () => backups.forEach(([node, orig]) => { node.nodeValue = orig; });
}

async function captureInvoice() {
    const el = document.querySelector('.print-container');
    if (!window.html2canvas) { alert('Export library not loaded. Please check internet connection.'); throw new Error('html2canvas missing'); }
    await document.fonts.ready;
    const restore = fixRtlBracketsForCanvas(el);
    try {
        return await html2canvas(el, {scale: 3, backgroundColor: '#ffffff', useCORS: true});
    } finally {
        restore();
    }
}
async function exportJpg() {
    const canvas = await captureInvoice();
    const a = document.createElement('a');
    a.download = exportName + '.jpg';
    a.href = canvas.toDataURL('image/jpeg', 0.95);
    a.click();
}
async function exportPdf() {
    const canvas = await captureInvoice();
    if (!window.jspdf) { alert('PDF library not loaded. Please check internet connection.'); return; }
    const img = canvas.toDataURL('image/jpeg', 0.98);
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF({orientation: canvas.width > canvas.height ? 'landscape' : 'portrait', unit: 'pt', format: [canvas.width, canvas.height]});
    pdf.addImage(img, 'JPEG', 0, 0, canvas.width, canvas.height);
    pdf.save(exportName + '.pdf');
}
function togglePrintTheme(){var t=document.documentElement.getAttribute('data-theme')==='light'?'dark':'light';document.documentElement.setAttribute('data-theme',t);try{localStorage.setItem('mj_theme',t)}catch(e){}}
</script>
    
</body>
</html>
