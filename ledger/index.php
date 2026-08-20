<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/ledger_functions.php';

$pageTitle = 'Ledger';
$db = getDB();

$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();
$selectedCustomer = null;
$ledgerData = null;
$customerId = query('customer_id', '');
$fromDate = query('from_date', '');
$toDate = query('to_date', '');
$dateRangeLabel = 'All Time';

if ($customerId) {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([(int)$customerId]);
    $selectedCustomer = $stmt->fetch();
    
    if ($selectedCustomer) {
        $dateRangeLabel = 'All Time';
        if ($fromDate && $toDate) {
            $dateRangeLabel = date('d M Y', strtotime($fromDate)) . ' - ' . date('d M Y', strtotime($toDate));
        } elseif ($fromDate) {
            $dateRangeLabel = 'From ' . date('d M Y', strtotime($fromDate));
        } elseif ($toDate) {
            $dateRangeLabel = 'Until ' . date('d M Y', strtotime($toDate));
        }
        $ledgerData = getCustomerLedger((int)$customerId, $fromDate ?: null, $toDate ?: null);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Ledger</h1>
        <p class="font-urdu" style="margin-top:4px;">لیجر</p>
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
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>" placeholder="From">
    </div>
    <div class="form-group">
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>" placeholder="To">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> View Ledger</button>
</form>

<?php if ($ledgerData): ?>
<div class="card" style="padding:24px;margin-bottom:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
        <div>
            <h3 style="font-family:'Playfair Display',serif;color:var(--gold-primary);">
                <?= htmlspecialchars($selectedCustomer['name']) ?>
                <span class="font-urdu" style="font-size:0.85rem;color:var(--text-muted);">لیجر</span>
            </h3>
            <div class="text-muted" style="font-size:0.8rem;"><?= $dateRangeLabel ?></div>
        </div>
        <div style="display:flex;gap:16px;flex-wrap:wrap;">
            <div><span class="text-muted">Opening:</span> <span class="mono"><?= number_format($ledgerData['opening_balance'], 3) ?> g</span></div>
            <div><span class="text-muted">Given:</span> <span class="mono text-success"><?= number_format($ledgerData['total_effective_gold'], 3) ?> g</span></div>
            <div><span class="text-muted">Received:</span> <span class="mono text-danger"><?= number_format($ledgerData['total_received_khalis'], 3) ?> g</span></div>

            <div><span class="text-muted" style="font-weight:600;">Balance:</span> 
                <span class="mono" style="font-weight:700;font-size:1.1rem;color:<?= $ledgerData['current_balance'] < 0 ? 'var(--error)' : 'var(--success)' ?>;">
                    <?= number_format($ledgerData['current_balance'], 3) ?> g
                </span>
            </div>
        </div>
    </div>
    <?php if (isset($ledgerData['calculation_breakdown'])): ?>
    <div style="display:flex;gap:24px;margin-top:12px;padding:12px;background:var(--bg-surface);border-radius:var(--radius-sm);font-size:0.85rem;">
        <span>Balance = Opening + Given - Received - Wasooli</span>
        <span>= <?= number_format($ledgerData['calculation_breakdown']['opening'], 3) ?> + <?= number_format($ledgerData['calculation_breakdown']['+ given'], 3) ?> - <?= number_format($ledgerData['calculation_breakdown']['- received'], 3) ?> - <?= number_format($ledgerData['calculation_breakdown']['- wasooli'], 3) ?></span>
        <span><strong>= <?= number_format($ledgerData['calculation_breakdown']['= balance'], 3) ?> g</strong></span>
    </div>
    <?php endif; ?>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Voucher #</th>
                <th class="text-right">Given (g)</th>
                <th class="text-right">Received (g)</th>
                <th class="text-right">Net (g)</th>
                <th class="text-right">Balance (g)</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $totalLedgerGiven = 0; $totalLedgerReceived = 0; $totalLedgerNet = 0;
                foreach ($ledgerData['transactions'] as $t) {
                    if (in_array($t['type'], ['invoice','invoice_multiple','gold_give'])) $totalLedgerGiven += (float)($t['effective_gold'] ?? 0);
                    if (in_array($t['type'], ['invoice','invoice_multiple'])) $totalLedgerReceived += (float)($t['received_khalis'] ?? 0);
                    if ($t['type'] === 'receipt') $totalLedgerReceived += (float)($t['khalis_weight'] ?? 0);
                    $totalLedgerNet += (float)($t['net_amount'] ?? 0);
                }
            ?>
            <tr style="font-weight:600;background:var(--bg-surface);">
                <td colspan="6">Opening Balance</td>
                <td class="text-right mono"><?= number_format($ledgerData['opening_balance'], 3) ?></td>
            </tr>
            <?php foreach ($ledgerData['transactions'] as $txn): ?>
            <?php
                $typeLabel = [
                    'invoice' => 'Invoice',
                    'invoice_multiple' => 'Invoice Multiple',
                    'receipt' => 'Receipt',
                    'gold_give' => 'Gold Give',
                ][$txn['type']] ?? ucfirst($txn['type']);
                $badgeClass = in_array($txn['type'], ['invoice','invoice_multiple','gold_give']) ? 'bg-gold' : 'bg-info';
                $givenDisplay = in_array($txn['type'], ['invoice','invoice_multiple','gold_give']) ? number_format($txn['effective_gold'], 3) : '-';
                $receivedDisplay = in_array($txn['type'], ['invoice','invoice_multiple']) ? number_format($txn['received_khalis'], 3) : ($txn['type'] === 'receipt' ? number_format($txn['khalis_weight'], 3) : '-');
            ?>
            <tr>
                <td><?= formatDate($txn['date']) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($typeLabel) ?></span></td>
                <td>
                    <?php if ($txn['type'] === 'invoice'): ?>
                        <a href="<?= url('invoices/show.php?id=' . $txn['data']['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($txn['invoice_no']) ?></a>
                    <?php elseif ($txn['type'] === 'invoice_multiple'): ?>
                        <a href="<?= url('invoice-multiple/show.php?id=' . $txn['data']['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($txn['invoice_no']) ?></a>
                    <?php elseif ($txn['type'] === 'gold_give'): ?>
                        <a href="<?= url('gold-gives/show.php?id=' . $txn['data']['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($txn['give_no']) ?></a>
                    <?php else: ?>
                        <a href="<?= url('gold-receipts/show.php?id=' . $txn['data']['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($txn['receipt_no']) ?></a>
                    <?php endif; ?>
                </td>
                <td class="text-right mono"><?= $givenDisplay ?></td>
                <td class="text-right mono"><?= $receivedDisplay ?></td>
                <td class="text-right mono <?= $txn['net_amount'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($txn['net_amount'], 3) ?></td>
                <td class="text-right mono"><?= number_format($txn['running_balance_after'], 3) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight:700;background:var(--bg-surface);border-top:2px solid var(--gold-primary);">
                <td colspan="3">Total</td>
                <td class="text-right mono"><?= number_format($totalLedgerGiven, 3) ?></td>
                <td class="text-right mono"><?= number_format($totalLedgerReceived, 3) ?></td>
                <td class="text-right mono <?= $totalLedgerNet < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($totalLedgerNet, 3) ?></td>
                <td class="text-right mono">—</td>
            </tr>
        </tbody>
    </table>
</div>
<?php elseif ($customerId): ?>
<div class="card" style="padding:24px;text-align:center;color:var(--text-muted);">No data found for this party.</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>