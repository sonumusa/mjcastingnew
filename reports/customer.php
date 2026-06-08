<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/ledger_functions.php';

$pageTitle = 'Customer Report';
$db = getDB();

$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();
$report = null;
$selectedCustomer = null;
$customerId = query('customer_id', '');
$fromDate = query('from_date', date('Y-m-01'));
$toDate = query('to_date', date('Y-m-d'));

if ($customerId) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([(int)$customerId]);
    $selectedCustomer = $stmt->fetch();
    if ($selectedCustomer) {
        $report = getCustomerReport((int)$customerId, $fromDate, $toDate);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Customer Report</h1>
        <p class="font-urdu" style="margin-top:4px;">گاہک رپورٹ</p>
    </div>
</div>

<form method="GET" class="filter-bar">
    <div class="form-group" style="flex:1;">
        <select name="customer_id" class="form-control" required>
            <option value="">Select Party</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label>From</label>
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
    </div>
    <div class="form-group">
        <label>To</label>
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View Report</button>
</form>

<?php if ($report): ?>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-person"></i></div>
        <div class="stat-label">Party</div>
        <div class="stat-value" style="font-size:1.2rem;"><?= htmlspecialchars($selectedCustomer['name']) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-file-text"></i></div>
        <div class="stat-label">Invoices</div>
        <div class="stat-value"><?= $report['total_invoices'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-inbox"></i></div>
        <div class="stat-label">Receipts</div>
        <div class="stat-value"><?= $report['total_receipts'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-gem"></i></div>
        <div class="stat-label">Gold Khalis</div>
        <div class="stat-value"><?= number_format($report['total_gold_khalis'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
        <div class="stat-label">Grand Total</div>
        <div class="stat-value"><?= number_format($report['total_grand_total'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div>
        <div class="stat-label">Received</div>
        <div class="stat-value"><?= number_format($report['total_received_khalis'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-label">Wasooli</div>
        <div class="stat-value"><?= number_format($report['total_wasooli'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        <div class="stat-label">Current Balance</div>
        <div class="stat-value" style="color:<?= $report['current_balance'] < 0 ? 'var(--error)' : 'var(--success)' ?>;">
            <?= number_format($report['current_balance'], 3) ?> g
        </div>
    </div>
</div>

<div class="card" style="padding:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
        <i class="bi bi-journal"></i> Transactions (<?= $report['total_invoices'] + $report['total_receipts'] ?>)
    </h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Voucher #</th>
                    <th class="text-right">Given (g)</th>
                    <th class="text-right">Received (g)</th>
                    <th class="text-right">Wasooli</th>
                    <th class="text-right">Net (g)</th>
                    <th class="text-right">Balance (g)</th>
                </tr>
            </thead>
            <tbody>
                <tr style="font-weight:600;background:var(--bg-surface);">
                    <td colspan="7">Opening Balance</td>
                    <td class="text-right mono"><?= number_format($report['opening_balance'], 3) ?></td>
                </tr>
                <?php foreach ($report['transactions'] as $txn): ?>
                <tr>
                    <td><?= formatDate($txn['date']) ?></td>
                    <td><span class="badge <?= $txn['type'] === 'invoice' ? 'bg-gold' : 'bg-info' ?>"><?= ucfirst($txn['type']) ?></span></td>
                    <td>
                        <?php if ($txn['type'] === 'invoice'): ?>
                            <a href="<?= url('invoices/show.php?id=' . $txn['data']['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($txn['invoice_no']) ?></a>
                        <?php else: ?>
                            <a href="<?= url('gold-receipts/show.php?id=' . $txn['data']['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($txn['receipt_no']) ?></a>
                        <?php endif; ?>
                    </td>
                    <td class="text-right mono"><?= $txn['type'] === 'invoice' ? number_format($txn['effective_gold'], 3) : '-' ?></td>
                    <td class="text-right mono"><?= $txn['type'] === 'invoice' ? number_format($txn['received_khalis'], 3) : number_format($txn['khalis_weight'], 3) ?></td>
                    <td class="text-right mono"><?= $txn['type'] === 'invoice' ? number_format($txn['wasooli'], 3) : '-' ?></td>
                    <td class="text-right mono <?= $txn['amount'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($txn['amount'], 3) ?></td>
                    <td class="text-right mono"><?= number_format($txn['running_balance'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php elseif ($customerId): ?>
<div class="card" style="padding:24px;text-align:center;color:var(--text-muted);">No data found for this period.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>