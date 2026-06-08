<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Inventory / Stock';
$db = getDB();

// Get inventory record
$stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
$inventory = $stmt->fetch();
if (!$inventory) {
    $db->query("INSERT INTO inventory (opening_balance, received, given_invoices, closing_balance, period_label) VALUES (0,0,0,0,'Current Stock')");
    $stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
    $inventory = $stmt->fetch();
}

$fromDate = query('from_date', '');
$toDate = query('to_date', '');

// Recalculate stock in / out
$stmt = $db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts");
$receiptKhalis = (float)$stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(khalis_weight),0) FROM invoice_receives");
$invoiceReceivedKhalis = (float)$stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(total_received_khalis),0) FROM invoices WHERE status='active'");
$invoiceInternalReceived = (float)$stmt->fetchColumn();

$totalReceived = $receiptKhalis + $invoiceReceivedKhalis + $invoiceInternalReceived;

$stmt = $db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoices WHERE status='active'");
$givenWeight = (float)$stmt->fetchColumn();

$closingBalance = round((float)$inventory['opening_balance'] + $totalReceived - $givenWeight, 3);

// Recent transactions
$recentReceipts = $db->query("SELECT r.*, c.name as customer_name FROM gold_receipts r LEFT JOIN customers c ON c.id=r.customer_id ORDER BY r.receipt_date DESC LIMIT 10")->fetchAll();
$recentInvoices = $db->query("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id WHERE i.status='active' ORDER BY i.invoice_date DESC LIMIT 10")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Inventory / Stock</h1>
        <p class="font-urdu" style="margin-top:4px;">اسٹاک</p>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        <div class="stat-label">Opening Balance</div>
        <div class="stat-value"><?= number_format($inventory['opening_balance'], 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-arrow-down-circle text-success"></i></div>
        <div class="stat-label">Total Received</div>
        <div class="stat-value"><?= number_format($totalReceived, 3) ?> g</div>
        <div class="stat-sub">Receipts: <?= number_format($receiptKhalis, 3) ?> | Invoice Receives: <?= number_format($invoiceReceivedKhalis, 3) ?> | Internal: <?= number_format($invoiceInternalReceived, 3) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-arrow-up-circle text-danger"></i></div>
        <div class="stat-label">Given (Invoices)</div>
        <div class="stat-value"><?= number_format($givenWeight, 3) ?> g</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-gem"></i></div>
        <div class="stat-label">Closing Balance</div>
        <div class="stat-value" style="color:<?= $closingBalance < 0 ? 'var(--error)' : 'var(--gold-bright)' ?>;"><?= number_format($closingBalance, 3) ?> g</div>
        <div class="stat-sub">Opening + Received - Given</div>
    </div>
</div>

<div class="card" style="padding:24px;margin-bottom:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Stock Calculation</h3>
    <div style="background:var(--bg-surface);padding:16px;border-radius:var(--radius-sm);">
        <div style="display:flex;justify-content:space-between;padding:4px 0;"><span>Opening:</span><span class="mono"><?= number_format($inventory['opening_balance'], 3) ?> g</span></div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--success);"><span>+ Gold Receipts (Khalis):</span><span class="mono">+ <?= number_format($receiptKhalis, 3) ?> g</span></div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--success);"><span>+ Invoice Receives (Khalis):</span><span class="mono">+ <?= number_format($invoiceReceivedKhalis, 3) ?> g</span></div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--success);"><span>+ Invoice Internal Received:</span><span class="mono">+ <?= number_format($invoiceInternalReceived, 3) ?> g</span></div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--error);"><span>- Invoice Effective Gold Given:</span><span class="mono">- <?= number_format($givenWeight, 3) ?> g</span></div>
        <div style="display:flex;justify-content:space-between;padding:8px 0 0;border-top:2px solid var(--gold-primary);margin-top:4px;font-weight:700;font-size:1.1rem;">
            <span>= Closing Balance:</span>
            <span class="mono" style="color:<?= $closingBalance < 0 ? 'var(--error)' : 'var(--gold-bright)' ?>;"><?= number_format($closingBalance, 3) ?> g</span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-inbox"></i> Recent Gold Receipts (+ Stock)
        </h3>
        <table>
            <thead><tr><th>Receipt #</th><th>Party</th><th>Khalis (g)</th></tr></thead>
            <tbody>
                <?php foreach ($recentReceipts as $r): ?>
                <tr><td><a href="<?= url('gold-receipts/show.php?id='.$r['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($r['receipt_no']) ?></a></td>
                    <td><?= htmlspecialchars($r['customer_name']??'') ?></td>
                    <td class="text-right mono text-success">+<?= number_format($r['total_khalis_weight'],3) ?></td></tr>
                <?php endforeach; ?>
                <?php if(empty($recentReceipts)): ?><tr><td colspan="3" class="text-center text-muted">No receipts</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
            <i class="bi bi-file-text"></i> Recent Invoices (- Stock)
        </h3>
        <table>
            <thead><tr><th>Invoice #</th><th>Party</th><th>Given (g)</th></tr></thead>
            <tbody>
                <?php foreach ($recentInvoices as $i): ?>
                <tr><td><a href="<?= url('invoices/show.php?id='.$i['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($i['invoice_no']) ?></a></td>
                    <td><?= htmlspecialchars($i['customer_name']??'') ?></td>
                    <td class="text-right mono text-danger">-<?= number_format($i['effective_gold'],3) ?></td></tr>
                <?php endforeach; ?>
                <?php if(empty($recentInvoices)): ?><tr><td colspan="3" class="text-center text-muted">No invoices</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>