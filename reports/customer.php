<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/ledger_functions.php';

$pageTitle = 'Customer Master Report';
$db = getDB();

$fromDate = query('from_date', date('Y-m-01'));
$toDate = query('to_date', date('Y-m-d'));
$partyType = query('party_type', '');
$viewReport = query('view', '') === '1';

function cmrFmt($value, int $decimals = 3): string {
    $value = (float)$value;
    if (abs($value) < 0.0005) return '0.000';
    return number_format($value, $decimals, '.', '');
}

function cmrStatus(float $remaining): string {
    if ($remaining < -0.0005) return 'Advance';
    if ($remaining > 0.0005) return 'Pending';
    return 'Settled';
}

$rows = [];
$totals = [
    'opening_balance' => 0,
    'total_casting' => 0,
    'total_waste' => 0,
    'total_male' => 0,
    'total_khalis' => 0,
    'rp_mazdori' => 0,
    'gold_dya' => 0,
    'grand_total' => 0,
    'total_wasool' => 0,
    'remaining' => 0,
];

if ($viewReport) {
    $customerSql = "SELECT * FROM customers WHERE status = 'active'";
    $customerParams = [];
    if ($partyType && in_array($partyType, ['customer', 'dukandar', 'karigar'], true)) {
        $customerSql .= " AND party_type = ?";
        $customerParams[] = $partyType;
    }
    $customerSql .= " ORDER BY FIELD(party_type,'customer','dukandar','karigar'), name ASC";
    $stmt = $db->prepare($customerSql);
    $stmt->execute($customerParams);
    $customers = $stmt->fetchAll();

    foreach ($customers as $customer) {
        $customerId = (int)$customer['id'];

        // ============================================================
        // Single source of truth: reuse the exact same function that
        // powers the Ledger page, so this report can NEVER disagree
        // with the Ledger again (opening/given/received/balance are
        // guaranteed identical to what the Ledger page shows).
        // ============================================================
        $ledgerData = getCustomerLedger($customerId, $fromDate, $toDate);

        $opening      = (float)($ledgerData['opening_balance'] ?? 0);
        $grandTotal   = (float)($ledgerData['total_effective_gold'] ?? 0);   // Given (invoice + invoice_multiple + gold_give)
        $totalWasool  = (float)($ledgerData['total_received_khalis'] ?? 0);  // Received (invoice + invoice_multiple + receipts)
        $remaining    = (float)($ledgerData['current_balance'] ?? ($opening + $grandTotal - $totalWasool));

        // ============================================================
        // Breakdown columns (Casting / Waste / Male / Khalis / Mazdori / Gold dya)
        // built by walking the SAME transaction list the ledger uses,
        // so single AND multi invoices both count, and nothing gets
        // silently dropped by a failed/mismatched query.
        // ============================================================
        $totalCasting = 0.0;
        $totalWaste   = 0.0;
        $totalMale    = 0.0;
        $totalKhalis  = 0.0;
        $rpMazdori    = 0.0;
        $goldDya      = 0.0;
        $lastEntryDate = null;

        foreach (($ledgerData['transactions'] ?? []) as $t) {
            $type = $t['type'] ?? '';
            $data = $t['data'] ?? [];

            if (!empty($t['date']) && ($lastEntryDate === null || $t['date'] > $lastEntryDate)) {
                $lastEntryDate = $t['date'];
            }

            if ($type === 'invoice') {
                // NOTE: verify these column names against ledger_functions.php
                $totalCasting += (float)($data['casting_weight'] ?? 0);
                $totalWaste   += (float)($data['waste_weight'] ?? 0);
                $totalMale    += (float)($data['male_waste'] ?? 0);
                $totalKhalis  += (float)($data['gold_khalis'] ?? 0);
                $rpMazdori    += (float)($data['rp_mazdori_weight'] ?? 0) + (float)($data['casting_mazdori_weight'] ?? 0);
            } elseif ($type === 'invoice_multiple') {
                // NOTE: verify these column names against ledger_functions.php
                $totalCasting += (float)($data['total_casting_weight'] ?? 0);
                $totalWaste   += (float)($data['total_waste_weight'] ?? 0);
                $totalMale    += (float)($data['total_male_waste'] ?? 0);
                $totalKhalis  += (float)($data['total_gold_khalis'] ?? 0);
                $rpMazdori    += (float)($data['total_rp_mazdori_weight'] ?? 0) + (float)($data['total_casting_mazdori_weight'] ?? 0);
            } elseif ($type === 'gold_give') {
                $goldDya += (float)($t['effective_gold'] ?? 0);
            }
        }

        $hasActivity = abs($opening) > 0.0005 || abs($grandTotal) > 0.0005 || abs($totalWasool) > 0.0005;
        if (!$hasActivity) continue;

        $row = [
            'id' => $customerId,
            'name' => $customer['name'],
            'type' => ucfirst($customer['party_type'] ?? 'customer'),
            'opening_balance' => $opening,
            'total_casting' => $totalCasting,
            'total_waste' => $totalWaste,
            'total_male' => $totalMale,
            'total_khalis' => $totalKhalis,
            'rp_mazdori' => $rpMazdori,
            'gold_dya' => $goldDya,
            'grand_total' => $grandTotal,
            'total_wasool' => $totalWasool,
            'remaining' => $remaining,
            'last_entry_date' => $lastEntryDate,
            'status_text' => cmrStatus($remaining),
        ];
        $rows[] = $row;

        foreach ($totals as $key => $_) {
            if (isset($row[$key])) $totals[$key] += (float)$row[$key];
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.customer-master-wrap { overflow-x:auto; }
.customer-master-table { min-width:1360px; border-collapse:collapse; background:#fff; color:#000; font-size:12px; }
.customer-master-table th, .customer-master-table td { border:1px solid #bfbfbf; padding:4px 6px; color:#000; }
.customer-master-table thead .title-row th { background:#222 !important; color:#fff !important; font-size:16px; text-align:center; padding:5px; letter-spacing:.03em; }
.customer-master-table thead .subtitle-row th { background:#d8b230 !important; color:#000 !important; text-align:center; font-size:11px; padding:4px; }
.customer-master-table thead .header-row th { background:#d8b230 !important; color:#fff !important; text-align:center; font-weight:700; white-space:nowrap; }
.customer-master-table tbody tr:nth-child(even) { background:#fbf8ef; }
.customer-master-table tbody tr:hover { background:#fff7cc; }
.customer-master-table .num { text-align:right; font-family:'JetBrains Mono', monospace; }
.customer-master-table .center { text-align:center; }
.customer-master-table .name { font-weight:700; white-space:nowrap; }
.customer-master-table .remaining.settled { background:#ffff00; }
.customer-master-table .remaining.advance { color:#000; }
.customer-master-table .remaining.pending { background:#fff59d; color:#000; }
.customer-master-table tfoot td { background:#fff; font-weight:700; border-top:2px solid #000; }
.report-note { color:var(--text-muted); margin-top:8px; font-size:.85rem; }
@media print {
    .sidebar,.top-header,.filter-bar,.page-header,.no-print { display:none!important; }
    .main-wrapper { margin-left:0!important; }
    .content-area { padding:0!important; }
    .customer-master-table { width:100%; min-width:0; font-size:9px; }
    .customer-master-table th,.customer-master-table td { padding:2px 3px; }
}
</style>

<div class="page-header">
    <div class="page-title-group">
        <h1>Customer Master</h1>
        <p class="font-urdu" style="margin-top:4px;">کسٹمر ماسٹر رپورٹ</p>
    </div>
    <?php if ($viewReport): ?>
    <div class="page-actions no-print">
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    </div>
    <?php endif; ?>
</div>

<form method="GET" class="filter-bar no-print">
    <input type="hidden" name="view" value="1">
    <div class="form-group">
        <label>From Date</label>
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>" required>
    </div>
    <div class="form-group">
        <label>To Date</label>
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>" required>
    </div>
    <div class="form-group">
        <label>Type</label>
        <select name="party_type" class="form-control">
            <option value="">All</option>
            <option value="customer" <?= $partyType === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="dukandar" <?= $partyType === 'dukandar' ? 'selected' : '' ?>>Dukandar</option>
            <option value="karigar" <?= $partyType === 'karigar' ? 'selected' : '' ?>>Karigar</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View Report</button>
    <a href="<?= url('reports/customer.php') ?>" class="btn btn-outline"><i class="bi bi-x-circle"></i> Clear</a>
</form>

<?php if (!$viewReport): ?>
    <div class="card" style="padding:28px;text-align:center;color:var(--text-muted);">
        Select date range and click <strong>View Report</strong> to build Customer Master summary.
    </div>
<?php else: ?>
    <div class="customer-master-wrap">
        <table class="customer-master-table">
            <thead>
                <tr class="title-row"><th colspan="15">MJ CASTING — CUSTOMER MASTER</th></tr>
                <tr class="subtitle-row"><th colspan="15">Traditional Pakistan Gold Casting Workshop — Centralized Customer Directory &nbsp; | &nbsp; <?= formatDate($fromDate) ?> to <?= formatDate($toDate) ?></th></tr>
                <tr class="header-row">
                    <th>S.No</th>
                    <th>Customer / Karigar Name</th>
                    <th>Type</th>
                    <th>Opening Bal</th>
                    <th>Total Casting</th>
                    <th>Total Waste</th>
                    <th>Total Male</th>
                    <th>Total Khalis</th>
                    <th>RP / Mazdori</th>
                    <th>Gold dya</th>
                    <th>Grand Total</th>
                    <th>Total Wasool</th>
                    <th>Remaining (Gold)</th>
                    <th>Last Entry Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="15" class="center">No data found for selected dates.</td></tr>
                <?php else: $serial = 1; foreach ($rows as $row): ?>
                    <?php $statusClass = strtolower($row['status_text']); ?>
                    <tr>
                        <td class="center"><?= $serial++ ?></td>
                        <td class="name"><?= htmlspecialchars($row['name']) ?></td>
                        <td class="center"><?= htmlspecialchars($row['type']) ?></td>
                        <td class="num"><?= cmrFmt($row['opening_balance']) ?></td>
                        <td class="num"><?= cmrFmt($row['total_casting']) ?></td>
                        <td class="num"><?= cmrFmt($row['total_waste']) ?></td>
                        <td class="num"><?= cmrFmt($row['total_male']) ?></td>
                        <td class="num"><?= cmrFmt($row['total_khalis']) ?></td>
                        <td class="num"><?= cmrFmt($row['rp_mazdori']) ?></td>
                        <td class="num"><?= cmrFmt($row['gold_dya']) ?></td>
                        <td class="num"><?= cmrFmt($row['grand_total']) ?></td>
                        <td class="num"><?= cmrFmt($row['total_wasool']) ?></td>
                        <td class="num remaining <?= $statusClass ?>"><?= cmrFmt($row['remaining']) ?></td>
                        <td class="center"><?= $row['last_entry_date'] ? formatDate($row['last_entry_date'], 'j-M-Y') : '-' ?></td>
                        <td class="center"><strong><?= htmlspecialchars($row['status_text']) ?></strong></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td></td><td></td><td class="center">TOTAL</td>
                    <td class="num"><?= cmrFmt($totals['opening_balance']) ?></td>
                    <td class="num"><?= cmrFmt($totals['total_casting']) ?></td>
                    <td class="num"><?= cmrFmt($totals['total_waste']) ?></td>
                    <td class="num"><?= cmrFmt($totals['total_male']) ?></td>
                    <td class="num"><?= cmrFmt($totals['total_khalis']) ?></td>
                    <td class="num"><?= cmrFmt($totals['rp_mazdori']) ?></td>
                    <td class="num"><?= cmrFmt($totals['gold_dya']) ?></td>
                    <td class="num"><?= cmrFmt($totals['grand_total']) ?></td>
                    <td class="num"><?= cmrFmt($totals['total_wasool']) ?></td>
                    <td class="num"><?= cmrFmt($totals['remaining']) ?></td>
                    <td></td><td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="report-note no-print">Remaining = Opening + Grand Total - Total Wasool (same Opening/Given/Received/Balance figures shown on the Ledger page for each party). Grand Total includes Single Invoice + Multiple Invoice + Gold dya.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>