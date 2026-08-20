<?php
require_once __DIR__ . '/../config.php';
requireAuth();

if (!function_exists('tableExists')) {
    function tableExists(string $table): bool {
        try {
            $stmt = getDB()->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

$pageTitle = 'Inventory / Stock';
$db = getDB();

function invScalar(PDO $db, string $sql): float {
    try {
        return (float)$db->query($sql)->fetchColumn();
    } catch (Throwable $e) {
        return 0.0;
    }
}

function invRows(PDO $db, string $sql): array {
    try {
        return $db->query($sql)->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

$stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
$inventory = $stmt->fetch();
if (!$inventory) {
    $db->query("INSERT INTO inventory (opening_balance, received, given_invoices, closing_balance, period_label) VALUES (0,0,0,0,'Current Stock')");
    $stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
    $inventory = $stmt->fetch();
}

// ============================================================
// Opening Balance
// ============================================================
// This must match the Customer Master / Ledger opening source.
// It is the sum of all active parties' opening balances.
$openingBalance = invScalar($db, "SELECT COALESCE(SUM(opening_balance),0) FROM customers WHERE status = 'active'");

// ============================================================
// LIVE STOCK CALCULATION
// ============================================================
// Balance = Opening + Given - Received
// Received = gold_receipts + active invoice_receives + active multiple invoice receives
// Given    = active invoices + active multiple invoices + standalone gold given
$receiptKhalis = invScalar($db, "
    SELECT COALESCE(SUM(total_khalis_weight),0)
    FROM gold_receipts
    WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'
");

$invoiceReceivedKhalis = invScalar($db, "
    SELECT COALESCE(SUM(ir.khalis_weight),0)
    FROM invoice_receives ir
    INNER JOIN invoices i ON i.id = ir.invoice_id
    WHERE COALESCE(i.status,'active') = 'active'
");

$multipleReceivedKhalis = invScalar($db, "
    SELECT COALESCE(SUM(imr.khalis_weight),0)
    FROM invoice_multiple_receives imr
    INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id
    WHERE COALESCE(im.status,'active') = 'active'
");

$totalReceived = round($receiptKhalis + $invoiceReceivedKhalis + $multipleReceivedKhalis, 3);

$invoiceGivenWeight = invScalar($db, "
    SELECT COALESCE(SUM(effective_gold),0)
    FROM invoices
    WHERE COALESCE(status,'active') = 'active'
");

$multipleGivenWeight = invScalar($db, "
    SELECT COALESCE(SUM(effective_gold),0)
    FROM invoice_multiple
    WHERE COALESCE(status,'active') = 'active'
");

$goldGiveWeight = invScalar($db, "
    SELECT COALESCE(SUM(total_khalis_weight),0)
    FROM gold_gives
    WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'
");

$givenWeight = round($invoiceGivenWeight + $multipleGivenWeight + $goldGiveWeight, 3);
$closingBalance = round($openingBalance + $givenWeight - $totalReceived, 3);

// Persist calculated inventory totals so Dashboard / other pages also see latest stock.
try {
    $update = $db->prepare("UPDATE inventory SET opening_balance = ?, received = ?, given_invoices = ?, closing_balance = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
    $update->execute([$openingBalance, $totalReceived, $givenWeight, $closingBalance, $_SESSION['user_id'] ?? null, $inventory['id']]);
    $inventory['updated_at'] = date('Y-m-d H:i:s');
} catch (Throwable $e) {
    // Compatibility for older DB schema if updated_by / updated_at are missing.
    $update = $db->prepare("UPDATE inventory SET opening_balance = ?, received = ?, given_invoices = ?, closing_balance = ? WHERE id = ?");
    $update->execute([$openingBalance, $totalReceived, $givenWeight, $closingBalance, $inventory['id']]);
}

$inventory['opening_balance'] = $openingBalance;
$inventory['received'] = $totalReceived;
$inventory['given_invoices'] = $givenWeight;
$inventory['closing_balance'] = $closingBalance;

$recentReceipts = invRows($db, "
    SELECT r.*, c.name AS customer_name
    FROM gold_receipts r
    LEFT JOIN customers c ON c.id = r.customer_id
    WHERE r.deleted_at IS NULL OR r.deleted_at = '0000-00-00 00:00:00'
    ORDER BY r.receipt_date DESC, r.id DESC
    LIMIT 10
");

$recentInvoices = invRows($db, "
    SELECT i.*, c.name AS customer_name
    FROM invoices i
    LEFT JOIN customers c ON c.id = i.customer_id
    WHERE COALESCE(i.status,'active') = 'active'
    ORDER BY i.invoice_date DESC, i.id DESC
    LIMIT 10
");

$recentGoldGives = invRows($db, "
    SELECT g.*, c.name AS customer_name
    FROM gold_gives g
    LEFT JOIN customers c ON c.id = g.customer_id
    WHERE g.deleted_at IS NULL OR g.deleted_at = '0000-00-00 00:00:00'
    ORDER BY g.give_date DESC, g.id DESC
    LIMIT 10
");

$recentMultipleInvoices = invRows($db, "
    SELECT im.*, c.name AS customer_name
    FROM invoice_multiple im
    LEFT JOIN customers c ON c.id = im.customer_id
    WHERE COALESCE(im.status,'active') = 'active'
    ORDER BY im.invoice_date DESC, im.id DESC
    LIMIT 10
");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Inventory / Stock</h1>
        <p class="font-urdu" style="margin-top:4px;">اسٹاک</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('inventory/index.php?sync=1') ?>" class="btn btn-primary"><i class="bi bi-arrow-clockwise"></i> Sync Inventory</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-seam"></i></div>
        <div class="stat-label">Opening Balance</div>
        <div class="stat-value"><?= number_format($openingBalance, 3) ?> g</div>
        <div class="stat-sub">Sum of all active parties' opening balance</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-arrow-down-circle text-success"></i></div>
        <div class="stat-label">Total Received</div>
        <div class="stat-value"><?= number_format($totalReceived, 3) ?> g</div>
        <div class="stat-sub">
            Receipts: <?= number_format($receiptKhalis, 3) ?> |
            Single Inv Receives: <?= number_format($invoiceReceivedKhalis, 3) ?> |
            Multi Inv Receives: <?= number_format($multipleReceivedKhalis, 3) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-arrow-up-circle text-danger"></i></div>
        <div class="stat-label">Given (All)</div>
        <div class="stat-value"><?= number_format($givenWeight, 3) ?> g</div>
        <div class="stat-sub">
            Single Inv: <?= number_format($invoiceGivenWeight, 3) ?> |
            Multi Inv: <?= number_format($multipleGivenWeight, 3) ?> |
            Gold Given: <?= number_format($goldGiveWeight, 3) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-gem"></i></div>
        <div class="stat-label">Closing Balance</div>
        <div class="stat-value" style="color:<?= $closingBalance < 0 ? 'var(--error)' : 'var(--gold-bright)' ?>;">
            <?= number_format($closingBalance, 3) ?> g
        </div>
        <div class="stat-sub">Opening + Given - Received</div>
    </div>
</div>

<div class="card" style="padding:24px;margin-bottom:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Stock Calculation</h3>
    <div style="background:var(--bg-surface);padding:16px;border-radius:var(--radius-sm);">
        <div style="display:flex;justify-content:space-between;padding:4px 0;">
            <span>Opening (sum of parties' opening balance):</span>
            <span class="mono"><?= number_format($openingBalance, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--success);">
            <span>- Gold Receipts (Khalis):</span>
            <span class="mono">- <?= number_format($receiptKhalis, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--success);">
            <span>- Single Invoice Receives:</span>
            <span class="mono">- <?= number_format($invoiceReceivedKhalis, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--success);">
            <span>- Multiple Invoice Receives:</span>
            <span class="mono">- <?= number_format($multipleReceivedKhalis, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--error);">
            <span>+ Single Invoice Effective Gold:</span>
            <span class="mono">+ <?= number_format($invoiceGivenWeight, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--error);">
            <span>+ Multiple Invoice Effective Gold:</span>
            <span class="mono">+ <?= number_format($multipleGivenWeight, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:4px 0;color:var(--error);">
            <span>+ Gold Given / Diya:</span>
            <span class="mono">+ <?= number_format($goldGiveWeight, 3) ?> g</span>
        </div>
        <div style="display:flex;justify-content:space-between;padding:8px 0 0;border-top:2px solid var(--gold-primary);margin-top:4px;font-weight:700;font-size:1.1rem;">
            <span>= Closing Balance:</span>
            <span class="mono" style="color:<?= $closingBalance < 0 ? 'var(--error)' : 'var(--gold-bright)' ?>;">
                <?= number_format($closingBalance, 3) ?> g
            </span>
        </div>
    </div>
    <div style="margin-top:10px;color:var(--text-muted);font-size:0.8rem;">
        Inventory DB synced:
        Opening = <?= number_format($inventory['opening_balance'],3) ?> g,
        Received = <?= number_format($inventory['received'],3) ?> g,
        Given = <?= number_format($inventory['given_invoices'],3) ?> g,
        Closing = <?= number_format($inventory['closing_balance'],3) ?> g
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);"><i class="bi bi-inbox"></i> Recent Gold Receipts (- Balance)</h3>
        <table>
            <thead><tr><th>Receipt #</th><th>Party</th><th>Khalis (g)</th></tr></thead>
            <tbody>
            <?php foreach ($recentReceipts as $r): ?>
                <tr>
                    <td><a href="<?= url('gold-receipts/show.php?id='.$r['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($r['receipt_no']) ?></a></td>
                    <td><?= htmlspecialchars($r['customer_name'] ?? '') ?></td>
                    <td class="text-right mono text-success">+<?= number_format($r['total_khalis_weight'],3) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentReceipts)): ?><tr><td colspan="3" class="text-center text-muted">No receipts</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);"><i class="bi bi-file-text"></i> Recent Single Invoices (+ Balance)</h3>
        <table>
            <thead><tr><th>Invoice #</th><th>Party</th><th>Given (g)</th></tr></thead>
            <tbody>
            <?php foreach ($recentInvoices as $i): ?>
                <tr>
                    <td><a href="<?= url('invoices/show.php?id='.$i['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($i['invoice_no']) ?></a></td>
                    <td><?= htmlspecialchars($i['customer_name'] ?? '') ?></td>
                    <td class="text-right mono text-danger">-<?= number_format($i['effective_gold'],3) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentInvoices)): ?><tr><td colspan="3" class="text-center text-muted">No invoices</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);"><i class="bi bi-box-arrow-up-right"></i> Recent Gold Given (+ Balance)</h3>
        <table>
            <thead><tr><th>Give #</th><th>Party</th><th>Given (g)</th></tr></thead>
            <tbody>
            <?php foreach ($recentGoldGives as $g): ?>
                <tr>
                    <td><a href="<?= url('gold-gives/show.php?id='.$g['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($g['give_no']) ?></a></td>
                    <td><?= htmlspecialchars($g['customer_name'] ?? '') ?></td>
                    <td class="text-right mono text-danger">-<?= number_format($g['total_khalis_weight'],3) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentGoldGives)): ?><tr><td colspan="3" class="text-center text-muted">No gold given</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding:24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);"><i class="bi bi-files"></i> Recent Multiple Invoices (+ Balance)</h3>
        <table>
            <thead><tr><th>Invoice #</th><th>Party</th><th>Given (g)</th></tr></thead>
            <tbody>
            <?php foreach ($recentMultipleInvoices as $mi): ?>
                <tr>
                    <td><a href="<?= url('invoice-multiple/show.php?id='.$mi['id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($mi['invoice_no']) ?></a></td>
                    <td><?= htmlspecialchars($mi['customer_name'] ?? '') ?></td>
                    <td class="text-right mono text-danger">-<?= number_format($mi['effective_gold'],3) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($recentMultipleInvoices)): ?><tr><td colspan="3" class="text-center text-muted">No multiple invoices</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
