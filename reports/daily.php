<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/ledger_functions.php';

$pageTitle = 'Daily Report';
$db = getDB();

// Default current week: Monday to Saturday
$todayTs = strtotime(date('Y-m-d'));
$mondayTs = (date('N', $todayTs) == 1) ? $todayTs : strtotime('last monday', $todayTs);
$saturdayTs = strtotime('+5 days', $mondayTs);

$defaultFrom = date('Y-m-d', $mondayTs);
$defaultTo   = date('Y-m-d', $saturdayTs);

$singleDate = trim((string) query('date', ''));
$fromDate   = trim((string) query('from_date', ''));
$toDate     = trim((string) query('to_date', ''));
$isPrint    = (query('print', '') === '1');

// Resolve active date range (defaults to Current Week Mon - Sat)
if ($fromDate !== '' && $toDate !== '') {
    $effFrom = $fromDate;
    $effTo   = $toDate;
    $hasRange = true;
} elseif ($singleDate !== '') {
    $effFrom = $singleDate;
    $effTo   = $singleDate;
    $hasRange = false;
    $fromDate = $singleDate;
    $toDate   = $singleDate;
} else {
    $effFrom = $defaultFrom;
    $effTo   = $defaultTo;
    $hasRange = true;
    $fromDate = $defaultFrom;
    $toDate   = $defaultTo;
}

if ($isPrint) {
    $workshopName     = getSetting('workshop_name', 'M.J Casting');
    $workshopNameUrdu = getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ');

    // Fetch all customers & EXCLUDE dukandar completely
    $custRows = $db->query("SELECT * FROM customers ORDER BY name ASC")->fetchAll();
    $partyMap = [];
    foreach ($custRows as $c) {
        $cid = (int)$c['id'];
        $cname = trim((string)($c['name'] ?? ''));
        $cType = strtolower(trim((string)(
            $c['customer_type']
            ?? $c['type']
            ?? $c['party_type']
            ?? $c['account_type']
            ?? ''
        )));
        // Skip Dukandar by type or name
        if ($cType === 'dukandar' || stripos($cname, 'dukandar') !== false) {
            continue;
        }
        $partyMap[$cid] = $cname;
    }

    // Track which parties appear in Multiple Invoice (they go to the RIGHT side)
    $isMultipleKarigar = [];

    // Data containers for the 3 tables
    $castingEntries   = []; // Table 1: Casting Given
    $receivingEntries = []; // Table 2: Gold Receiving
    $wapsiEntries     = []; // Table 3: Casting Wapsi

    foreach ($partyMap as $cid => $cname) {
        $castingEntries[$cid]   = [];
        $receivingEntries[$cid] = [];
        $wapsiEntries[$cid]     = [];
    }

    // ---------------------------------------------------------
    // 1. STANDARD INVOICES (Casting Given)
    // ---------------------------------------------------------
    $invStmt = $db->prepare("
        SELECT i.id, i.customer_id, i.invoice_date, i.casting_weight
        FROM invoices i
        WHERE i.invoice_date BETWEEN ? AND ?
        ORDER BY i.invoice_date ASC, i.id ASC
    ");
    $invStmt->execute([$effFrom, $effTo]);
    $invList = $invStmt->fetchAll();

    foreach ($invList as $inv) {
        $cid = (int)$inv['customer_id'];
        if (!isset($partyMap[$cid])) {
            continue;
        }
        $cw = round((float)($inv['casting_weight'] ?? 0), 3);
        $d  = (string)($inv['invoice_date'] ?? '');
        if ($cw > 0.0001) {
            $castingEntries[$cid][] = [
                'sort' => $d . '_1_' . str_pad((string)$inv['id'], 10, '0', STR_PAD_LEFT),
                'val'  => $cw,
            ];
        }
    }

    // ---------------------------------------------------------
    // 2. MULTIPLE INVOICES (Casting Given & Casting Wapsi)
    // ---------------------------------------------------------
    $miStmt = $db->prepare("
        SELECT imi.id,
               im.customer_id,
               COALESCE(imi.item_date, im.invoice_date) AS eff_date,
               imi.casting_weight,
               COALESCE(imi.ratti, 0) AS ratti_val
        FROM invoice_multiple_items imi
        INNER JOIN invoice_multiple im ON im.id = imi.invoice_multiple_id
        WHERE COALESCE(imi.item_date, im.invoice_date) BETWEEN ? AND ?
        ORDER BY COALESCE(imi.item_date, im.invoice_date) ASC, imi.id ASC
    ");
    $miStmt->execute([$effFrom, $effTo]);
    $miItems = $miStmt->fetchAll();

    foreach ($miItems as $item) {
        $cid = (int)$item['customer_id'];
        if (!isset($partyMap[$cid])) {
            continue;
        }
        // Mark this party as Multiple Invoice Karigar so it shifts to the right side
        $isMultipleKarigar[$cid] = true;

        $cw       = round((float)($item['casting_weight'] ?? 0), 3);
        $rattiVal = abs((float)($item['ratti_val'] ?? 0));
        $d        = (string)($item['eff_date'] ?? '');

        if (abs($cw) > 0.0001 && $rattiVal > 0.0001) {
            if ($cw > 0) {
                $castingEntries[$cid][] = [
                    'sort' => $d . '_2_' . str_pad((string)$item['id'], 10, '0', STR_PAD_LEFT),
                    'val'  => $cw,
                ];
            } else {
                // Negative casting_weight in multiple invoice = Casting Wapsi
                $wapsiEntries[$cid][] = [
                    'sort' => $d . '_2_' . str_pad((string)$item['id'], 10, '0', STR_PAD_LEFT),
                    'val'  => abs($cw),
                ];
            }
        }
    }

    // ---------------------------------------------------------
    // 3. SINGLE INVOICE WAPSI (invoice_receives where return_casting_enabled = 1)
    // ---------------------------------------------------------
    $retSingleStmt = $db->prepare("
        SELECT ir.id, i.customer_id, i.invoice_date, ir.return_casting_weight
        FROM invoice_receives ir
        INNER JOIN invoices i ON i.id = ir.invoice_id
        WHERE ir.return_casting_enabled = 1
          AND ir.return_casting_weight > 0
          AND i.invoice_date BETWEEN ? AND ?
        ORDER BY i.invoice_date ASC, ir.id ASC
    ");
    $retSingleStmt->execute([$effFrom, $effTo]);
    foreach ($retSingleStmt->fetchAll() as $rr) {
        $cid = (int)$rr['customer_id'];
        if (!isset($partyMap[$cid])) {
            continue;
        }
        $rw = round((float)($rr['return_casting_weight'] ?? 0), 3);
        $d  = (string)($rr['invoice_date'] ?? '');
        if ($rw > 0.0001) {
            $wapsiEntries[$cid][] = [
                'sort' => $d . '_1_' . str_pad((string)$rr['id'], 10, '0', STR_PAD_LEFT),
                'val'  => $rw,
            ];
        }
    }

    // ---------------------------------------------------------
    // 4. GOLD RECEIVING (Single Invoice Receives + Multiple Receives + Gold Receipts)
    // ---------------------------------------------------------
    // 4a. Normal receives on Single Invoices
    $recInvStmt = $db->prepare("
        SELECT ir.id, i.customer_id, i.invoice_date, ir.khalis_weight
        FROM invoice_receives ir
        INNER JOIN invoices i ON i.id = ir.invoice_id
        WHERE (ir.return_casting_enabled = 0 OR ir.return_casting_enabled IS NULL)
          AND ir.khalis_weight > 0
          AND i.invoice_date BETWEEN ? AND ?
        ORDER BY i.invoice_date ASC, ir.id ASC
    ");
    $recInvStmt->execute([$effFrom, $effTo]);
    foreach ($recInvStmt->fetchAll() as $r) {
        $cid = (int)$r['customer_id'];
        if (!isset($partyMap[$cid])) {
            continue;
        }
        $kw = round((float)($r['khalis_weight'] ?? 0), 3);
        $d  = (string)($r['invoice_date'] ?? '');
        if ($kw > 0.0001) {
            $receivingEntries[$cid][] = [
                'sort' => $d . '_1_' . str_pad((string)$r['id'], 10, '0', STR_PAD_LEFT),
                'val'  => $kw,
            ];
        }
    }

    // 4b. Receives on Multiple Invoices
    $recMiStmt = $db->prepare("
        SELECT imr.id,
               im.customer_id,
               COALESCE(imr.receive_date, im.invoice_date) AS eff_date,
               imr.khalis_weight
        FROM invoice_multiple_receives imr
        INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id
        WHERE imr.khalis_weight > 0
          AND COALESCE(imr.receive_date, im.invoice_date) BETWEEN ? AND ?
        ORDER BY COALESCE(imr.receive_date, im.invoice_date) ASC, imr.id ASC
    ");
    $recMiStmt->execute([$effFrom, $effTo]);
    foreach ($recMiStmt->fetchAll() as $r) {
        $cid = (int)$r['customer_id'];
        if (!isset($partyMap[$cid])) {
            continue;
        }
        $isMultipleKarigar[$cid] = true;
        $kw = round((float)($r['khalis_weight'] ?? 0), 3);
        $d  = (string)($r['eff_date'] ?? '');
        if ($kw > 0.0001) {
            $receivingEntries[$cid][] = [
                'sort' => $d . '_2_' . str_pad((string)$r['id'], 10, '0', STR_PAD_LEFT),
                'val'  => $kw,
            ];
        }
    }

    // 4c. Standalone Gold Receipts (if table exists)
    try {
        $grStmt = $db->prepare("
            SELECT id, customer_id, receipt_date, total_khalis_weight
            FROM gold_receipts
            WHERE total_khalis_weight > 0
              AND receipt_date BETWEEN ? AND ?
            ORDER BY receipt_date ASC, id ASC
        ");
        $grStmt->execute([$effFrom, $effTo]);
        foreach ($grStmt->fetchAll() as $r) {
            $cid = (int)$r['customer_id'];
            if (!isset($partyMap[$cid])) {
                continue;
            }
            $kw = round((float)($r['total_khalis_weight'] ?? 0), 3);
            $d  = (string)($r['receipt_date'] ?? '');
            if ($kw > 0.0001) {
                $receivingEntries[$cid][] = [
                    'sort' => $d . '_3_' . str_pad((string)$r['id'], 10, '0', STR_PAD_LEFT),
                    'val'  => $kw,
                ];
            }
        }
    } catch (Throwable $e) {
        // Ignore if gold_receipts table or column differs
    }

    // Helper to order columns: Left = Regular Customers, Right = Multiple Invoice Karigars
    $buildMatrix = function (array $entriesMap) use ($partyMap, $isMultipleKarigar) {
        $leftCols  = [];
        $rightCols = [];
        $totals    = [];
        $maxRows   = 0;
        $grandSum  = 0.0;

        foreach ($partyMap as $cid => $cname) {
            $list = $entriesMap[$cid] ?? [];
            if (empty($list)) {
                continue;
            }
            usort($list, fn($a, $b) => strcmp($a['sort'], $b['sort']));
            $entriesMap[$cid] = $list;

            if (count($list) > $maxRows) {
                $maxRows = count($list);
            }

            $colSum = 0.0;
            foreach ($list as $item) {
                $colSum += $item['val'];
            }
            $totals[$cid] = round($colSum, 3);
            $grandSum += $totals[$cid];

            if (!empty($isMultipleKarigar[$cid])) {
                $rightCols[$cid] = $cname;
            } else {
                $leftCols[$cid] = $cname;
            }
        }

        $orderedCols = $leftCols + $rightCols;

        return [
            'cols'     => $orderedCols,
            'entries'  => $entriesMap,
            'totals'   => $totals,
            'maxRows'  => $maxRows,
            'grandSum' => round($grandSum, 3),
        ];
    };

    $castingMatrix   = $buildMatrix($castingEntries);
    $receivingMatrix = $buildMatrix($receivingEntries);
    $wapsiMatrix     = $buildMatrix($wapsiEntries);

    // Auto-fit proportional widths for bottom 2 tables based on column counts
    $recColCount   = count($receivingMatrix['cols']);
    $wapsiColCount = count($wapsiMatrix['cols']);
    if ($recColCount > 0 && $wapsiColCount > 0) {
        $recWeight   = $recColCount + 0.85;
        $wapsiWeight = $wapsiColCount + 0.85;
        $totalW      = $recWeight + $wapsiWeight;
        $recPct      = round(($recWeight / $totalW) * 100, 2);
        $wapsiPct    = round(100 - $recPct, 2);
    } else {
        $recPct   = 50;
        $wapsiPct = 50;
    }

    $periodLabel = ($effFrom === $effTo)
        ? date('d M Y', strtotime($effFrom))
        : date('d M Y', strtotime($effFrom)) . ' - ' . date('d M Y', strtotime($effTo));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Casting Summary Report - <?= htmlspecialchars($periodLabel) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<style>
@page { size: A4 landscape; margin: 5mm; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body {
    background: #e5e7eb;
    color: #111;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 12px;
}
.no-print {
    background: #1f242d;
    color: #fff;
    padding: 10px 16px;
    text-align: center;
    position: sticky;
    top: 0;
    z-index: 100;
}
.no-print button, .no-print a {
    display: inline-block;
    padding: 7px 14px;
    margin: 0 4px;
    border: none;
    border-radius: 4px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
}
.btn-print { background: #d4af37; color: #111; }
.btn-jpg   { background: #059669; color: #fff; }
.btn-back  { background: #4b5563; color: #fff; }

.sheet {
    width: 287mm;
    min-height: 198mm;
    margin: 12px auto;
    background: #fff;
    padding: 5mm;
    box-shadow: 0 4px 18px rgba(0,0,0,0.15);
}

/* Header styled like Customer Master reference */
.master-banner {
    background: #1f242d;
    color: #fff;
    padding: 6px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid #d4af37;
}
.master-banner .title {
    font-size: 17px;
    font-weight: 800;
    letter-spacing: 0.6px;
}
.master-banner .urdu {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 16px;
    font-weight: 700;
    color: #f3d266;
    line-height: 1.5;
}
.sub-banner {
    background: #e9c46a;
    color: #111;
    text-align: center;
    font-size: 11px;
    font-weight: 800;
    padding: 4px 8px;
    letter-spacing: 0.4px;
    border: 1px solid #1f242d;
    border-top: none;
    margin-bottom: 8px;
}

.section-bar {
    background: #1f242d;
    color: #f3d266;
    font-size: 11.5px;
    font-weight: 800;
    padding: 4px 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border: 1px solid #1f242d;
    border-bottom: none;
}
.section-bar .urdu-tag {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 12px;
    color: #fff;
    line-height: 1.4;
}

table.matrix-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-bottom: 8px;
}
table.matrix-table th,
table.matrix-table td {
    border: 1px solid #2b303a;
    text-align: center;
    vertical-align: middle;
    padding: 3px 2px;
}
table.matrix-table thead th {
    background: #d4af37;
    color: #111;
    font-size: 11px;
    font-weight: 800;
    padding: 5px 2px;
    overflow-wrap: break-word;
    word-wrap: break-word;
    line-height: 1.15;
}
table.matrix-table th.entry-col,
table.matrix-table td.entry-col {
    width: 44px;
    font-weight: 800;
    background: #f8f5eb;
    color: #111;
    font-size: 11.5px;
}
table.matrix-table thead th.entry-col {
    background: #c59b27;
    color: #111;
}
/* Increased numbering font size for clear readability */
td.val-cell {
    font-size: 12.5px;
    font-weight: 700;
    color: #111;
    font-variant-numeric: tabular-nums;
}
tr.grand-total-row td {
    background: #1f242d;
    color: #f3d266;
    font-size: 12.5px;
    font-weight: 900;
    padding: 5px 2px;
    font-variant-numeric: tabular-nums;
}
tr.grand-total-row td.entry-col {
    background: #1f242d;
    color: #fff;
    font-size: 11px;
}

/* Bottom Horizontal Grid: Auto-fit proportional columns so Receiving gets wide space */
.bottom-grid {
    display: flex;
    gap: 8px;
    align-items: flex-start;
    width: 100%;
}
.bottom-col-rec {
    flex: 0 0 calc(<?= $recPct ?>% - 4px);
    min-width: 0;
}
.bottom-col-wapsi {
    flex: 0 0 calc(<?= $wapsiPct ?>% - 4px);
    min-width: 0;
}
.bottom-grid table.matrix-table {
    margin-bottom: 0;
}
.bottom-grid table.matrix-table thead th {
    font-size: 10.5px;
    padding: 4px 2px;
}
.bottom-grid td.val-cell {
    font-size: 12px;
    padding: 3px 2px;
}
.bottom-grid tr.grand-total-row td {
    font-size: 12px;
    padding: 4px 2px;
}
.bottom-grid th.entry-col,
.bottom-grid td.entry-col {
    width: 34px;
    font-size: 11px;
}

.empty-box {
    border: 1px solid #2b303a;
    padding: 14px;
    text-align: center;
    color: #6b7280;
    font-weight: 700;
    font-size: 11px;
    background: #fafafa;
}

.report-footer {
    margin-top: 8px;
    padding-top: 4px;
    border-top: 1px solid #cbd5e1;
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: #1f242d;
    font-weight: 700;
}

@media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .sheet { width: 100%; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
    * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>

<div class="no-print">
    <button type="button" class="btn-print" onclick="window.print()">Print Report</button>
    <button type="button" class="btn-jpg" id="btnJpg" onclick="exportJpg()">Export JPG</button>
    <a class="btn-back" href="<?= url('reports/daily.php?from_date=' . urlencode($effFrom) . '&to_date=' . urlencode($effTo)) ?>">Back to Report</a>
</div>

<div class="sheet" id="reportSheet">
    <div class="master-banner">
        <div class="title"><?= htmlspecialchars(strtoupper($workshopName)) ?> &mdash; DAILY SUMMARY REPORT</div>
        <div class="urdu"><?= htmlspecialchars($workshopNameUrdu) ?></div>
    </div>
    <div class="sub-banner">
        GOLD CASTING WORKSHOP &mdash; CASTING, RECEIVING &amp; WAPSI SUMMARY &nbsp;|&nbsp; PERIOD: <?= htmlspecialchars(strtoupper($periodLabel)) ?>
    </div>

    <!-- TABLE 1: CASTING GIVEN -->
    <div class="section-bar">
        <span>1. CASTING GIVEN (CUSTOMERS &amp; KARIGARS)</span>
        <span class="urdu-tag">کاسٹنگ دیا (کل: <?= number_format($castingMatrix['grandSum'], 3) ?> g)</span>
    </div>
    <?php if (empty($castingMatrix['cols'])): ?>
        <div class="empty-box" style="margin-bottom:8px;">No Casting entries found for this period.</div>
    <?php else: ?>
    <table class="matrix-table">
        <thead>
            <tr>
                <th class="entry-col">Entry #</th>
                <?php foreach ($castingMatrix['cols'] as $cid => $cname): ?>
                    <th><?= htmlspecialchars($cname) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php for ($idx = 0; $idx < $castingMatrix['maxRows']; $idx++): ?>
                <tr>
                    <td class="entry-col"><?= $idx + 1 ?></td>
                    <?php foreach ($castingMatrix['cols'] as $cid => $cname): ?>
                        <?php $entry = $castingMatrix['entries'][$cid][$idx] ?? null; ?>
                        <?php if ($entry !== null): ?>
                            <td class="val-cell"><?= number_format($entry['val'], 3) ?></td>
                        <?php else: ?>
                            <td class="val-cell" style="color:#9ca3af;">-</td>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endfor; ?>
            <tr class="grand-total-row">
                <td class="entry-col">Total</td>
                <?php foreach ($castingMatrix['cols'] as $cid => $cname): ?>
                    <td><?= number_format($castingMatrix['totals'][$cid], 3) ?></td>
                <?php endforeach; ?>
            </tr>
        </tbody>
    </table>
    <?php endif; ?>

    <!-- BOTTOM HORIZONTAL ROW: TABLE 2 (LEFT - RECEIVING) & TABLE 3 (RIGHT - WAPSI) -->
    <div class="bottom-grid">
        <!-- TABLE 2: GOLD RECEIVING -->
        <div class="bottom-col-rec">
            <div class="section-bar">
                <span>2. GOLD RECEIVING (WASOOLI)</span>
                <span class="urdu-tag">سونا وصول (<?= number_format($receivingMatrix['grandSum'], 3) ?> g)</span>
            </div>
            <?php if (empty($receivingMatrix['cols'])): ?>
                <div class="empty-box">No Gold Receiving entries found.</div>
            <?php else: ?>
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th class="entry-col">#</th>
                        <?php foreach ($receivingMatrix['cols'] as $cid => $cname): ?>
                            <th><?= htmlspecialchars($cname) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($idx = 0; $idx < $receivingMatrix['maxRows']; $idx++): ?>
                        <tr>
                            <td class="entry-col"><?= $idx + 1 ?></td>
                            <?php foreach ($receivingMatrix['cols'] as $cid => $cname): ?>
                                <?php $entry = $receivingMatrix['entries'][$cid][$idx] ?? null; ?>
                                <?php if ($entry !== null): ?>
                                    <td class="val-cell"><?= number_format($entry['val'], 3) ?></td>
                                <?php else: ?>
                                    <td class="val-cell" style="color:#9ca3af;">-</td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                    <tr class="grand-total-row">
                        <td class="entry-col">Total</td>
                        <?php foreach ($receivingMatrix['cols'] as $cid => $cname): ?>
                            <td><?= number_format($receivingMatrix['totals'][$cid], 3) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- TABLE 3: CASTING WAPSI (SINGLE & MULTIPLE) -->
        <div class="bottom-col-wapsi">
            <div class="section-bar">
                <span>3. CASTING WAPSI (SINGLE &amp; MULTIPLE)</span>
                <span class="urdu-tag">کاسٹنگ واپسی (<?= number_format($wapsiMatrix['grandSum'], 3) ?> g)</span>
            </div>
            <?php if (empty($wapsiMatrix['cols'])): ?>
                <div class="empty-box">No Casting Wapsi entries found.</div>
            <?php else: ?>
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th class="entry-col">#</th>
                        <?php foreach ($wapsiMatrix['cols'] as $cid => $cname): ?>
                            <th><?= htmlspecialchars($cname) ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($idx = 0; $idx < $wapsiMatrix['maxRows']; $idx++): ?>
                        <tr>
                            <td class="entry-col"><?= $idx + 1 ?></td>
                            <?php foreach ($wapsiMatrix['cols'] as $cid => $cname): ?>
                                <?php $entry = $wapsiMatrix['entries'][$cid][$idx] ?? null; ?>
                                <?php if ($entry !== null): ?>
                                    <td class="val-cell"><?= number_format($entry['val'], 3) ?></td>
                                <?php else: ?>
                                    <td class="val-cell" style="color:#9ca3af;">-</td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                    <tr class="grand-total-row">
                        <td class="entry-col">Total</td>
                        <?php foreach ($wapsiMatrix['cols'] as $cid => $cname): ?>
                            <td><?= number_format($wapsiMatrix['totals'][$cid], 3) ?></td>
                        <?php endforeach; ?>
                    </tr>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="report-footer">
        <div>
            Casting Given: <strong><?= number_format($castingMatrix['grandSum'], 3) ?> g</strong> &nbsp;|&nbsp;
            Gold Receiving: <strong><?= number_format($receivingMatrix['grandSum'], 3) ?> g</strong> &nbsp;|&nbsp;
            Casting Wapsi: <strong><?= number_format($wapsiMatrix['grandSum'], 3) ?> g</strong>
        </div>
        <div>Printed: <?= date('d-m-Y h:i A') ?></div>
    </div>
</div>

<script>
async function exportJpg() {
    const el = document.getElementById('reportSheet');
    const btn = document.getElementById('btnJpg');
    if (typeof html2canvas === 'undefined') {
        alert('Export library is still loading. Please check internet connection.');
        return;
    }
    btn.disabled = true;
    try {
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }
        const canvas = await html2canvas(el, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff'
        });
        const a = document.createElement('a');
        a.download = 'Summary-Report-<?= preg_replace('/[^A-Za-z0-9_-]/', '_', $effFrom . '_to_' . $effTo) ?>.jpg';
        a.href = canvas.toDataURL('image/jpeg', 0.95);
        a.click();
    } catch (e) {
        alert('JPG export failed: ' + e.message);
    } finally {
        btn.disabled = false;
    }
}
</script>
</body>
</html>
<?php
    exit;
}

// Normal Daily Report View (Existing 4-table layout, without top stats badges)
$report = $hasRange ? getDailyReportRange($effFrom, $effTo) : getDailyReport($effFrom);
$dateLabel = $hasRange
    ? date('d M Y', strtotime($effFrom)) . ' - ' . date('d M Y', strtotime($effTo))
    : date('l, d F Y', strtotime($effFrom));

$printUrl = '?from_date=' . urlencode($effFrom) . '&to_date=' . urlencode($effTo) . '&print=1';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Daily Report</h1>
        <p class="font-urdu" style="margin-top:4px;">یومیہ رپورٹ</p>
    </div>
</div>

<form method="GET" class="filter-bar">
    <div class="form-group">
        <label>Single Date</label>
        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($singleDate) ?>">
    </div>
    <div class="form-group">
        <label>From</label>
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
    </div>
    <div class="form-group">
        <label>To</label>
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View</button>
    <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" class="btn btn-secondary" style="background:#111;color:#fff;border-color:#111;">
        <i class="bi bi-printer"></i> Print
    </a>
</form>

<?php if ($report): ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <!-- Invoices -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-file-text"></i> Invoices (<?= $report['total_invoices'] ?>)
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Party</th>
                        <th class="text-right">Casting (g)</th>
                        <th class="text-right">Khalis (g)</th>
                        <th class="text-right">Effective (g)</th>
                        <th class="text-right">Wasooli</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['invoices'])): ?>
                        <tr><td colspan="6" class="text-center text-muted">No invoices</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['invoices'] as $inv): ?>
                        <tr>
                            <td><a href="<?= url('invoices/show.php?id=' . $inv['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($inv['invoice_no']) ?></a></td>
                            <td><?= htmlspecialchars($inv['customer_name'] ?? '') ?></td>
                            <td class="text-right mono"><?= number_format((float)$inv['casting_weight'], 3) ?></td>
                            <td class="text-right mono"><?= number_format((float)$inv['gold_khalis'], 3) ?></td>
                            <td class="text-right mono"><?= number_format((float)$inv['effective_gold'], 3) ?></td>
                            <td class="text-right mono"><?= number_format((float)$inv['wasooli'], 3) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Receipts -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-inbox"></i> Gold Receipts (<?= $report['total_receipts'] ?>)
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Party</th>
                        <th class="text-right">Gross (g)</th>
                        <th class="text-right">Khalis (g)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['receipts'])): ?>
                        <tr><td colspan="4" class="text-center text-muted">No receipts</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['receipts'] as $r): ?>
                        <tr>
                            <td><a href="<?= url('gold-receipts/show.php?id=' . $r['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($r['receipt_no']) ?></a></td>
                            <td><?= htmlspecialchars($r['customer_name'] ?? '') ?></td>
                            <td class="text-right mono"><?= number_format((float)$r['total_gross_weight'], 3) ?></td>
                            <td class="text-right mono text-success"><?= number_format((float)$r['total_khalis_weight'], 3) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Invoice Multiple -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-files"></i> Invoice Multiple (<?= $report['total_multiple_invoices'] ?? 0 ?>)
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Party</th>
                        <th class="text-right">Casting</th>
                        <th class="text-right">Effective</th>
                        <th class="text-right">Received</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['multiple_invoices'])): ?>
                        <tr><td colspan="5" class="text-center text-muted">No multiple invoices</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['multiple_invoices'] as $mi): ?>
                        <tr>
                            <td><a href="<?= url('invoice-multiple/show.php?id=' . $mi['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($mi['invoice_no']) ?></a></td>
                            <td><?= htmlspecialchars($mi['customer_name'] ?? '') ?></td>
                            <td class="text-right mono"><?= number_format((float)$mi['total_casting_weight'], 3) ?></td>
                            <td class="text-right mono"><?= number_format((float)$mi['effective_gold'], 3) ?></td>
                            <td class="text-right mono"><?= number_format((float)$mi['total_received_khalis'], 3) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Gold Gives -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-box-arrow-up-right"></i> Gold Gives (<?= $report['total_gold_gives'] ?? 0 ?>)
        </h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Give #</th>
                        <th>Party</th>
                        <th class="text-right">Gross</th>
                        <th class="text-right">Khalis Given</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($report['gold_gives'])): ?>
                        <tr><td colspan="4" class="text-center text-muted">No gold gives</td></tr>
                    <?php else: ?>
                        <?php foreach ($report['gold_gives'] as $g): ?>
                        <tr>
                            <td><a href="<?= url('gold-gives/show.php?id=' . $g['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($g['give_no']) ?></a></td>
                            <td><?= htmlspecialchars($g['customer_name'] ?? '') ?></td>
                            <td class="text-right mono"><?= number_format((float)$g['total_gross_weight'], 3) ?></td>
                            <td class="text-right mono text-danger"><?= number_format((float)$g['total_khalis_weight'], 3) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
