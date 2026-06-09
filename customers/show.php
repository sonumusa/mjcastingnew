<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';
require_once __DIR__ . '/../functions/ledger_functions.php';

$pageTitle = 'Party Details';

$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Party not found.');
    redirect('customers/index.php');
}

// Get invoices
$stmt = $db->prepare("SELECT * FROM invoices WHERE customer_id = ? AND status = 'active' ORDER BY invoice_date DESC, id DESC");
$stmt->execute([$id]);
$invoices = $stmt->fetchAll();

// Get receipts
$stmt = $db->prepare("SELECT * FROM gold_receipts WHERE customer_id = ? ORDER BY receipt_date DESC, id DESC");
$stmt->execute([$id]);
$receipts = $stmt->fetchAll();

$balance = getCustomerCurrentBalance($id);
$showLedger = query('ledger') === '1';

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1><?= htmlspecialchars($customer['name']) ?></h1>
        <p class="font-urdu" style="margin-top:4px;">
            <?= ucfirst($customer['party_type']) ?> 
            <?= $customer['status'] === 'active' ? '' : '(Inactive)' ?>
        </p>
    </div>
    <div class="page-actions">
        <a href="<?= url('customers/edit.php?id=' . $id) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="<?= url('invoices/create.php?customer_id=' . $id) ?>" class="btn btn-gold"><i class="bi bi-plus-circle"></i> New Invoice</a>
        <a href="<?= url('gold-receipts/create.php?customer_id=' . $id) ?>" class="btn btn-primary"><i class="bi bi-inbox"></i> New Receipt</a>
        <?php if (!$showLedger): ?>
        <a href="<?= url('customers/show.php?id=' . $id . '&ledger=1') ?>" class="btn btn-outline"><i class="bi bi-journal"></i> Full Ledger</a>
        <?php endif; ?>
    </div>
</div>

<!-- Customer Info + Balance -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-bottom:32px;">
    <div class="card" style="padding:24px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <div><span class="text-muted" style="font-size:0.8rem;">Phone</span><br><?= htmlspecialchars($customer['phone'] ?? '-') ?></div>
            <div><span class="text-muted" style="font-size:0.8rem;">CNIC</span><br><?= htmlspecialchars($customer['cnic'] ?? '-') ?></div>
            <div><span class="text-muted" style="font-size:0.8rem;">City</span><br><?= htmlspecialchars($customer['city'] ?? '-') ?></div>
            <div><span class="text-muted" style="font-size:0.8rem;">Party Type</span><br><span class="badge bg-info"><?= ucfirst($customer['party_type']) ?></span></div>
            <div style="grid-column:span 2;"><span class="text-muted" style="font-size:0.8rem;">Address</span><br><?= htmlspecialchars($customer['address'] ?? '-') ?></div>
        </div>
    </div>
    
    <div class="card" style="padding:24px;border-left:3px solid <?= $balance < 0 ? 'var(--error)' : 'var(--success)' ?>;">
        <div class="text-muted" style="font-size:0.75rem;text-transform:uppercase;letter-spacing:0.06em;">Current Balance</div>
        <div class="mono" style="font-size:2rem;font-weight:700;color:<?= $balance < 0 ? 'var(--error)' : 'var(--success)' ?>;">
            <?= number_format($balance, 3) ?> g
        </div>
        <div style="display:flex;gap:24px;margin-top:12px;">
            <div><span class="text-muted" style="font-size:0.72rem;">Opening</span><br><span class="mono"><?= number_format($customer['opening_balance'], 3) ?> g</span></div>
            <div><span class="text-muted" style="font-size:0.72rem;">Invoices</span><br><span class="mono"><?= count($invoices) ?></span></div>
            <div><span class="text-muted" style="font-size:0.72rem;">Receipts</span><br><span class="mono"><?= count($receipts) ?></span></div>
        </div>
    </div>
</div>

<?php if ($showLedger): ?>
<!-- Full Ledger View -->
<?php
$ledgerData = getCustomerLedger($id);
$transactions = $ledgerData['transactions'] ?? [];
?>
<div class="card" style="padding:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
        <i class="bi bi-journal"></i> Full Ledger
    </h3>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;padding:12px;background:var(--bg-surface);border-radius:var(--radius-sm);">
        <div><span class="text-muted">Opening:</span> <span class="mono"><?= number_format($ledgerData['opening_balance'], 3) ?> g</span></div>
        <div><span class="text-muted">Total Given:</span> <span class="mono"><?= number_format($ledgerData['total_effective_gold'], 3) ?> g</span></div>
        <div><span class="text-muted">Total Received:</span> <span class="mono"><?= number_format($ledgerData['total_received_khalis'], 3) ?> g</span></div>
        <div><span class="text-muted">Wasooli:</span> <span class="mono"><?= number_format($ledgerData['total_wasooli'], 3) ?> g</span></div>
        <div><span class="text-muted">Balance:</span> <span class="mono" style="font-weight:700;color:<?= $ledgerData['current_balance'] < 0 ? 'var(--error)' : 'var(--success)' ?>;"><?= number_format($ledgerData['current_balance'], 3) ?> g</span></div>
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
                    <th class="text-right">Wasooli (g)</th>
                    <th class="text-right">Net (g)</th>
                    <th class="text-right">Balance (g)</th>
                </tr>
            </thead>
            <tbody>
                <tr style="font-weight:600;">
                    <td colspan="7">Opening Balance</td>
                    <td class="text-right mono"><?= number_format($ledgerData['opening_balance'], 3) ?></td>
                </tr>
                <?php foreach ($transactions as $txn): ?>
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
                    <td class="text-right"><?= $txn['type'] === 'invoice' ? number_format($txn['effective_gold'], 3) : '-' ?></td>
                    <td class="text-right"><?= $txn['type'] === 'invoice' ? number_format($txn['received_khalis'], 3) : number_format($txn['khalis_weight'], 3) ?></td>
                    <td class="text-right"><?= $txn['type'] === 'invoice' ? number_format($txn['wasooli'], 3) : '-' ?></td>
                    <td class="text-right mono <?= $txn['net_amount'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($txn['net_amount'], 3) ?></td>
                    <td class="text-right mono"><?= number_format($txn['running_balance_after'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php else: ?>
<!-- Invoices & Receipts -->
<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Invoices</h3>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th class="text-right">Effective (g)</th>
                    <th class="text-right">Wasooli</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($invoices)): ?>
                    <tr><td colspan="5" class="text-center text-muted">No invoices</td></tr>
                <?php else: ?>
                    <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><a href="<?= url('invoices/show.php?id=' . $inv['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($inv['invoice_no']) ?></a></td>
                        <td><?= formatDate($inv['invoice_date']) ?></td>
                        <td class="text-right mono"><?= number_format($inv['effective_gold'], 3) ?></td>
                        <td class="text-right mono"><?= number_format($inv['wasooli'], 3) ?></td>
                        <td class="text-right mono"><?= number_format($inv['remaining_balance'], 3) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Gold Receipts</h3>
        <table>
            <thead>
                <tr>
                    <th>Receipt #</th>
                    <th>Date</th>
                    <th class="text-right">Gross (g)</th>
                    <th class="text-right">Khalis (g)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($receipts)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No receipts</td></tr>
                <?php else: ?>
                    <?php foreach ($receipts as $rec): ?>
                    <tr>
                        <td><a href="<?= url('gold-receipts/show.php?id=' . $rec['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($rec['receipt_no']) ?></a></td>
                        <td><?= formatDate($rec['receipt_date']) ?></td>
                        <td class="text-right mono"><?= number_format($rec['total_gross_weight'], 3) ?></td>
                        <td class="text-right mono"><?= number_format($rec['total_khalis_weight'], 3) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>