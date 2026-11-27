<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'Finished Reconciliation Report';
$db = getDB();
ensureInvoiceMultipleDateColumns();

$fromDate = query('from_date', date('Y-m-01'));
$toDate = query('to_date', date('Y-m-d'));
$fromDate = is_string($fromDate) ? $fromDate : '';
$toDate = is_string($toDate) ? $toDate : '';
$viewReport = query('view', '') === '1';
$error = '';
$karigars = [];
$dukandars = [];
$totals = [
    'karigar_gross' => 0.0,
    'karigar_khalis' => 0.0,
    'dukandar_gross' => 0.0,
    'dukandar_khalis' => 0.0,
];

$formatWeight = static function ($value): string {
    return number_format((float)$value, 3);
};

$isValidDate = static function (string $date): bool {
    $parsed = DateTime::createFromFormat('!Y-m-d', $date);
    return $parsed !== false && $parsed->format('Y-m-d') === $date;
};

if ($viewReport) {
    if (!$isValidDate($fromDate) || !$isValidDate($toDate) || $fromDate > $toDate) {
        $error = 'Please select a valid date range.';
    } else {
        $receiveStmt = $db->prepare("
            SELECT im.customer_id,
                   im.id AS invoice_id,
                   COALESCE(c.name, CONCAT('Party #', im.customer_id)) AS party_name,
                   im.invoice_no,
                   COALESCE(imr.receive_date, im.invoice_date) AS row_date,
                   imr.description,
                   imr.gross_weight,
                   imr.ratti_impurity AS ratti,
                   imr.khalis_weight
            FROM invoice_multiple_receives imr
            INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id
            LEFT JOIN customers c ON c.id = im.customer_id
            WHERE im.status = 'active'
              AND im.invoice_type = 'karigar'
              AND imr.is_finish = 1
              AND COALESCE(imr.receive_date, im.invoice_date) BETWEEN ? AND ?
            ORDER BY party_name, row_date, imr.id
        ");
        $receiveStmt->execute([$fromDate, $toDate]);

        foreach ($receiveStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $partyId = (int)$row['customer_id'];
            if (!isset($karigars[$partyId])) {
                $karigars[$partyId] = [
                    'name' => $row['party_name'],
                    'rows' => [],
                    'gross' => 0.0,
                    'khalis' => 0.0,
                ];
            }
            $karigars[$partyId]['rows'][] = $row;
            $karigars[$partyId]['gross'] += (float)$row['gross_weight'];
            $karigars[$partyId]['khalis'] += (float)$row['khalis_weight'];
            $totals['karigar_gross'] += (float)$row['gross_weight'];
            $totals['karigar_khalis'] += (float)$row['khalis_weight'];
        }

        $castingStmt = $db->prepare("
            SELECT im.customer_id,
                   im.id AS invoice_id,
                   COALESCE(c.name, CONCAT('Party #', im.customer_id)) AS party_name,
                   im.invoice_no,
                   COALESCE(imi.item_date, im.invoice_date) AS row_date,
                   imi.description,
                   imi.casting_weight AS gross_weight,
                   imi.ratti,
                   imi.gold_khalis AS khalis_weight
            FROM invoice_multiple_items imi
            INNER JOIN invoice_multiple im ON im.id = imi.invoice_multiple_id
            LEFT JOIN customers c ON c.id = im.customer_id
            WHERE im.status = 'active'
              AND im.invoice_type = 'dukandar'
              AND imi.is_finish = 1
              AND COALESCE(imi.item_date, im.invoice_date) BETWEEN ? AND ?
            ORDER BY party_name, row_date, imi.id
        ");
        $castingStmt->execute([$fromDate, $toDate]);

        foreach ($castingStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $partyId = (int)$row['customer_id'];
            if (!isset($dukandars[$partyId])) {
                $dukandars[$partyId] = [
                    'name' => $row['party_name'],
                    'rows' => [],
                    'gross' => 0.0,
                    'khalis' => 0.0,
                ];
            }
            $dukandars[$partyId]['rows'][] = $row;
            $dukandars[$partyId]['gross'] += (float)$row['gross_weight'];
            $dukandars[$partyId]['khalis'] += (float)$row['khalis_weight'];
            $totals['dukandar_gross'] += (float)$row['gross_weight'];
            $totals['dukandar_khalis'] += (float)$row['khalis_weight'];
        }
    }
}

$grossDifference = $totals['karigar_gross'] - $totals['dukandar_gross'];

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.finished-report-section { margin-bottom: 24px; }
.finished-party-card { padding: 20px; margin-bottom: 18px; }
.finished-party-card h3 { margin-bottom: 14px; color: var(--gold-primary); }
.finished-table th, .finished-table td { white-space: nowrap; }
.finished-summary { max-width: 850px; }
.report-note { color: var(--text-muted); margin-top: 10px; font-size: .85rem; }
@media print {
    .sidebar, .top-header, .filter-bar, .no-print { display: none !important; }
    .main-wrapper { margin-left: 0 !important; }
    .content-area { padding: 0 !important; }
    .finished-party-card, .finished-summary { break-inside: avoid; }
}
</style>

<div class="page-header">
    <div class="page-title-group">
        <h1>Finished Reconciliation Report</h1>
        <?php if ($viewReport && $error === ''): ?>
            <p class="report-note">Period: <?= formatDate($fromDate) ?> – <?= formatDate($toDate) ?></p>
        <?php endif; ?>
    </div>
    <?php if ($viewReport && $error === ''): ?>
    <div class="page-actions no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
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
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View Report</button>
    <a href="<?= url('reports/finished_reconciliation.php') ?>" class="btn btn-outline">
        <i class="bi bi-x-circle"></i> Clear
    </a>
</form>

<?php if ($error !== ''): ?>
    <div class="card flash-error" style="padding:18px;border-left:4px solid var(--error);">
        <?= htmlspecialchars($error) ?>
    </div>
<?php elseif (!$viewReport): ?>
    <div class="card" style="padding:24px;text-align:center;color:var(--text-muted);">
        Select a date range and click <strong>View Report</strong>.
    </div>
<?php else: ?>
    <section class="finished-report-section">
        <h2 style="margin-bottom:16px;">Finished Karigar Receiving</h2>
        <?php if (!$karigars): ?>
            <div class="card" style="padding:18px;color:var(--text-muted);">No finished Karigar receiving rows for this period.</div>
        <?php else: ?>
            <?php foreach ($karigars as $karigar): ?>
                <div class="card finished-party-card">
                    <h3><?= htmlspecialchars($karigar['name']) ?></h3>
                    <div class="table-container">
                        <table class="finished-table">
                            <thead><tr><th>Date</th><th>Invoice</th><th>Description</th><th class="text-right">Gross Weight</th><th class="text-right">Ratti</th><th class="text-right">Receiving Khalis</th></tr></thead>
                            <tbody>
                            <?php foreach ($karigar['rows'] as $row): ?>
                                <tr>
                                    <td><?= formatDate($row['row_date']) ?></td>
                                    <td><a href="<?= url('invoice-multiple/show.php?id=' . (int)$row['invoice_id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($row['invoice_no']) ?></a></td>
                                    <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                                    <td class="text-right mono"><?= $formatWeight($row['gross_weight']) ?></td>
                                    <td class="text-right mono"><?= $formatWeight($row['ratti']) ?></td>
                                    <td class="text-right mono"><?= $formatWeight($row['khalis_weight']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr><th colspan="3">Karigar Total</th><th class="text-right mono"><?= $formatWeight($karigar['gross']) ?></th><th></th><th class="text-right mono"><?= $formatWeight($karigar['khalis']) ?></th></tr></tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="finished-report-section">
        <h2 style="margin-bottom:16px;">Finished Dukandar Gold Calculation</h2>
        <?php if (!$dukandars): ?>
            <div class="card" style="padding:18px;color:var(--text-muted);">No finished Dukandar calculation rows for this period.</div>
        <?php else: ?>
            <?php foreach ($dukandars as $dukandar): ?>
                <div class="card finished-party-card">
                    <h3><?= htmlspecialchars($dukandar['name']) ?></h3>
                    <div class="table-container">
                        <table class="finished-table">
                            <thead><tr><th>Date</th><th>Invoice</th><th>Description</th><th class="text-right">Gross Weight</th><th class="text-right">Ratti</th><th class="text-right">Khalis Weight</th></tr></thead>
                            <tbody>
                            <?php foreach ($dukandar['rows'] as $row): ?>
                                <tr>
                                    <td><?= formatDate($row['row_date']) ?></td>
                                    <td><a href="<?= url('invoice-multiple/show.php?id=' . (int)$row['invoice_id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($row['invoice_no']) ?></a></td>
                                    <td><?= htmlspecialchars($row['description'] ?: '-') ?></td>
                                    <td class="text-right mono"><?= $formatWeight($row['gross_weight']) ?></td>
                                    <td class="text-right mono"><?= $formatWeight($row['ratti']) ?></td>
                                    <td class="text-right mono"><?= $formatWeight($row['khalis_weight']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                            <tfoot><tr><th colspan="3">Dukandar Total</th><th class="text-right mono"><?= $formatWeight($dukandar['gross']) ?></th><th></th><th class="text-right mono"><?= $formatWeight($dukandar['khalis']) ?></th></tr></tfoot>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <section class="card finished-party-card finished-summary">
        <h2 style="margin-bottom:14px;color:var(--gold-primary);">Finished Reconciliation Summary</h2>
        <div class="table-container">
            <table class="finished-table">
                <thead><tr><th>Measure</th><th class="text-right">Karigar Receiving</th><th class="text-right">Dukandar Calculation</th><th class="text-right">Difference</th></tr></thead>
                <tbody>
                    <tr>
                        <th>Gross Weight</th>
                        <td class="text-right mono"><?= $formatWeight($totals['karigar_gross']) ?></td>
                        <td class="text-right mono"><?= $formatWeight($totals['dukandar_gross']) ?></td>
                        <td class="text-right mono"><?= $formatWeight($grossDifference) ?></td>
                    </tr>
                    <tr>
                        <th>Khalis Weight</th>
                        <td class="text-right mono"><?= $formatWeight($totals['karigar_khalis']) ?></td>
                        <td class="text-right mono"><?= $formatWeight($totals['dukandar_khalis']) ?></td>
                        <td class="text-right">—</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="report-note">Difference = Karigar receiving gross weight − Dukandar finished gross weight. Khalis is shown separately; it is not netted.</p>
    </section>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
