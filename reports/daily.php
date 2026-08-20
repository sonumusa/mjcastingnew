<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/ledger_functions.php';

$pageTitle = 'Daily Report';
$db = getDB();

$date = query('date', date('Y-m-d'));
$fromDate = query('from_date', '');
$toDate = query('to_date', '');
$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();

$report = null;
$dateLabel = '';
$hasRange = false;

if ($fromDate && $toDate) {
    $hasRange = true;
    $report = getDailyReportRange($fromDate, $toDate);
    $dateLabel = date('d M Y', strtotime($fromDate)) . ' - ' . date('d M Y', strtotime($toDate));
    $dateForView = $fromDate;
} else {
    $report = getDailyReport($date);
    $dateLabel = date('l, d F Y', strtotime($date));
    $dateForView = $date;
}

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
        <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
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
</form>

<?php if ($report): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-file-text"></i></div>
        <div class="stat-label">Invoices</div>
        <div class="stat-value"><?= $report['total_invoices'] + ($report['total_multiple_invoices'] ?? 0) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-inbox"></i></div>
        <div class="stat-label">Receipts / Gives</div>
        <div class="stat-value"><?= $report['total_receipts'] ?> / <?= $report['total_gold_gives'] ?? 0 ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-gem"></i></div>
        <div class="stat-label">Gold Khalis</div>
        <div class="stat-value"><?= number_format($report['total_gold_khalis'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div class="stat-label">Total Given (All)</div>
        <div class="stat-value"><?= number_format($report['total_grand_total'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div>
        <div class="stat-label">Received (Invoice/Multi)</div>
        <div class="stat-value"><?= number_format($report['total_received_invoice'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-inboxes"></i></div>
        <div class="stat-label">Receipt Khalis</div>
        <div class="stat-value"><?= number_format($report['total_receipt_khalis'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-label">Wasooli</div>
        <div class="stat-value"><?= number_format($report['total_wasooli'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-arrow-left-right"></i></div>
        <div class="stat-label">Net Movement</div>
        <div class="stat-value" style="color:<?= $report['net_movement'] < 0 ? 'var(--error)' : 'var(--success)' ?>;">
            <?= number_format($report['net_movement'], 3) ?> g
        </div>
    </div>
</div>

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
                            <td class="text-right mono"><?= number_format($inv['casting_weight'], 3) ?></td>
                            <td class="text-right mono"><?= number_format($inv['gold_khalis'], 3) ?></td>
                            <td class="text-right mono"><?= number_format($inv['effective_gold'], 3) ?></td>
                            <td class="text-right mono"><?= number_format($inv['wasooli'], 3) ?></td>
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
                            <td class="text-right mono"><?= number_format($r['total_gross_weight'], 3) ?></td>
                            <td class="text-right mono text-success"><?= number_format($r['total_khalis_weight'], 3) ?></td>
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
        <div class="table-container"><table><thead><tr><th>Invoice #</th><th>Party</th><th class="text-right">Casting</th><th class="text-right">Effective</th><th class="text-right">Received</th></tr></thead><tbody>
        <?php if (empty($report['multiple_invoices'])): ?><tr><td colspan="5" class="text-center text-muted">No multiple invoices</td></tr><?php else: foreach ($report['multiple_invoices'] as $mi): ?>
            <tr><td><a href="<?= url('invoice-multiple/show.php?id=' . $mi['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($mi['invoice_no']) ?></a></td><td><?= htmlspecialchars($mi['customer_name'] ?? '') ?></td><td class="text-right mono"><?= number_format($mi['total_casting_weight'],3) ?></td><td class="text-right mono"><?= number_format($mi['effective_gold'],3) ?></td><td class="text-right mono"><?= number_format($mi['total_received_khalis'],3) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
    </div>

    <!-- Gold Gives -->
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-box-arrow-up-right"></i> Gold Gives (<?= $report['total_gold_gives'] ?? 0 ?>)
        </h3>
        <div class="table-container"><table><thead><tr><th>Give #</th><th>Party</th><th class="text-right">Gross</th><th class="text-right">Khalis Given</th></tr></thead><tbody>
        <?php if (empty($report['gold_gives'])): ?><tr><td colspan="4" class="text-center text-muted">No gold gives</td></tr><?php else: foreach ($report['gold_gives'] as $g): ?>
            <tr><td><a href="<?= url('gold-gives/show.php?id=' . $g['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($g['give_no']) ?></a></td><td><?= htmlspecialchars($g['customer_name'] ?? '') ?></td><td class="text-right mono"><?= number_format($g['total_gross_weight'],3) ?></td><td class="text-right mono text-danger"><?= number_format($g['total_khalis_weight'],3) ?></td></tr>
        <?php endforeach; endif; ?>
        </tbody></table></div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>