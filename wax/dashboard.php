<?php
require_once __DIR__ . '/config.php';
requireAuth();
require_once __DIR__ . '/functions/ledger_functions.php';

$pageTitle = 'Dashboard';
$db = getDB();

if (!function_exists('tableExists')) {
    function tableExists(string $table): bool {
        try {
            $stmt = getDB()->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
}

function dashScalar(PDO $db, string $sql, array $params = []): float {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0.0;
    }
}

function dashRows(PDO $db, string $sql, array $params = []): array {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

// ============================================================
// Dashboard stats - calculated directly here so Dashboard matches Inventory.
// Inventory / balance formula:
// Closing = Opening + Given - Received
// ============================================================
$totalCustomers = (int)dashScalar($db, "SELECT COUNT(*) FROM customers");
$totalInvoices = (int)dashScalar($db, "SELECT COUNT(*) FROM invoices WHERE COALESCE(status,'active')='active'");
$totalMultipleInvoices = (int)dashScalar($db, "SELECT COUNT(*) FROM invoice_multiple WHERE COALESCE(status,'active')='active'");
$totalReceipts = (int)dashScalar($db, "SELECT COUNT(*) FROM gold_receipts WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'");
$totalGoldGives = (int)dashScalar($db, "SELECT COUNT(*) FROM gold_gives WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'");

$openingBalance = dashScalar($db, "SELECT COALESCE(SUM(opening_balance),0) FROM customers WHERE status='active'");

$invoiceGiven = dashScalar($db, "SELECT COALESCE(SUM(effective_gold),0) FROM invoices WHERE COALESCE(status,'active')='active'");
$multipleGiven = dashScalar($db, "SELECT COALESCE(SUM(effective_gold),0) FROM invoice_multiple WHERE COALESCE(status,'active')='active'");
$goldGiven = dashScalar($db, "SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'");
$totalGiven = round($invoiceGiven + $multipleGiven + $goldGiven, 3);

$receiptKhalis = dashScalar($db, "SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'");
$invoiceReceived = dashScalar($db, "
    SELECT COALESCE(SUM(ir.khalis_weight),0)
    FROM invoice_receives ir
    INNER JOIN invoices i ON i.id = ir.invoice_id
    WHERE COALESCE(i.status,'active')='active'
");
$multipleReceived = dashScalar($db, "
    SELECT COALESCE(SUM(imr.khalis_weight),0)
    FROM invoice_multiple_receives imr
    INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id
    WHERE COALESCE(im.status,'active')='active'
");
$totalReceived = round($receiptKhalis + $invoiceReceived + $multipleReceived, 3);

$inventoryClosing = round($openingBalance + $totalGiven - $totalReceived, 3);

$singleWasooli = dashScalar($db, "SELECT COALESCE(SUM(wasooli),0) FROM invoices WHERE COALESCE(status,'active')='active'");
$multipleWasooli = dashScalar($db, "SELECT COALESCE(SUM(wasooli),0) FROM invoice_multiple WHERE COALESCE(status,'active')='active'");
$totalWasooli = round($singleWasooli + $multipleWasooli, 3);

$today = date('Y-m-d');
$todayInvoices = (int)dashScalar($db, "SELECT COUNT(*) FROM invoices WHERE COALESCE(status,'active')='active' AND invoice_date=?", [$today]);
$todayMultipleInvoices = (int)dashScalar($db, "SELECT COUNT(*) FROM invoice_multiple WHERE COALESCE(status,'active')='active' AND invoice_date=?", [$today]);
$todayReceipts = (int)dashScalar($db, "SELECT COUNT(*) FROM gold_receipts WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') AND receipt_date=?", [$today]);
$todayGoldGives = (int)dashScalar($db, "SELECT COUNT(*) FROM gold_gives WHERE (deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00') AND give_date=?", [$today]);

$partyTypes = ['customer' => 0, 'dukandar' => 0, 'karigar' => 0];
foreach (dashRows($db, "SELECT party_type, COUNT(*) AS cnt FROM customers WHERE status='active' GROUP BY party_type") as $row) {
    $partyTypes[$row['party_type']] = (int)$row['cnt'];
}

$totalRemaining = 0.0;
foreach (dashRows($db, "SELECT id FROM customers WHERE status='active'") as $c) {
    $totalRemaining += getCustomerCurrentBalance((int)$c['id']);
}
$totalRemaining = round($totalRemaining, 3);

// Persist inventory totals for any page that reads inventory table.
try {
    $inventory = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1")->fetch();
    if ($inventory) {
        $upd = $db->prepare("UPDATE inventory SET opening_balance=?, received=?, given_invoices=?, closing_balance=? WHERE id=?");
        $upd->execute([$openingBalance, $totalReceived, $totalGiven, $inventoryClosing, $inventory['id']]);
    }
} catch (Throwable $e) {}

// Recent transactions - no tableExists dependency; safe helpers return [] if table is missing.
$recentInvoices = dashRows($db, "
    SELECT i.*, c.name AS customer_name
    FROM invoices i
    LEFT JOIN customers c ON c.id = i.customer_id
    WHERE COALESCE(i.status,'active')='active'
    ORDER BY i.invoice_date DESC, i.id DESC
    LIMIT 10
");
$recentMultipleInvoices = dashRows($db, "
    SELECT im.*, c.name AS customer_name
    FROM invoice_multiple im
    LEFT JOIN customers c ON c.id = im.customer_id
    WHERE COALESCE(im.status,'active')='active'
    ORDER BY im.invoice_date DESC, im.id DESC
    LIMIT 10
");
$recentGoldGives = dashRows($db, "
    SELECT g.*, c.name AS customer_name
    FROM gold_gives g
    LEFT JOIN customers c ON c.id = g.customer_id
    WHERE g.deleted_at IS NULL OR g.deleted_at = '0000-00-00 00:00:00'
    ORDER BY g.give_date DESC, g.id DESC
    LIMIT 10
");

// Top customers by current balance.
$topCustomers = [];
foreach (dashRows($db, "SELECT id, name FROM customers WHERE status='active'") as $c) {
    $bal = getCustomerCurrentBalance((int)$c['id']);
    $topCustomers[] = ['name' => $c['name'], 'balance' => $bal, 'id' => $c['id']];
}
usort($topCustomers, fn($a, $b) => $b['balance'] <=> $a['balance']);
$topCustomers = array_slice($topCustomers, 0, 5);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Dashboard</h1>
        <p class="font-urdu" style="margin-top:4px;">ڈیش بورڈ</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('invoices/create.php') ?>" class="btn btn-gold"><i class="bi bi-plus-circle"></i> New Invoice</a>
        <a href="<?= url('invoice-multiple/create.php') ?>" class="btn btn-gold"><i class="bi bi-plus-circle"></i> New Multi Invoice</a>
        <a href="<?= url('gold-receipts/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Receipt</a>
        <a href="<?= url('gold-gives/create.php') ?>" class="btn btn-primary"><i class="bi bi-plus-circle"></i> New Gold Give</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div class="stat-label">Total Parties</div>
        <div class="stat-value"><?= $totalCustomers ?></div>
        <div class="stat-sub"><?= $partyTypes['customer'] ?> Customer · <?= $partyTypes['dukandar'] ?> Dukandar · <?= $partyTypes['karigar'] ?> Karigar</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-file-text"></i></div>
        <div class="stat-label">Active Invoices</div>
        <div class="stat-value"><?= $totalInvoices + $totalMultipleInvoices ?></div>
        <div class="stat-sub">Today: <?= $todayInvoices + $todayMultipleInvoices ?> invoices</div>
    </div>
<!--
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-inbox"></i></div>
        <div class="stat-label">Receipts / Gold Gives</div>
        <div class="stat-value"><?= $totalReceipts ?> / <?= $totalGoldGives ?></div>
        <div class="stat-sub">Today: <?= $todayReceipts ?> receipts · <?= $todayGoldGives ?> gives</div>
    </div>-->

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-gem"></i></div>
        <div class="stat-label">Gold Given (Effective)</div>
        <div class="stat-value"><?= number_format($totalGiven, 3) ?> g</div>
        <div class="stat-sub">Single: <?= number_format($invoiceGiven, 3) ?> · Multi: <?= number_format($multipleGiven, 3) ?> · Given: <?= number_format($goldGiven, 3) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div>
        <div class="stat-label">Gold Received</div>
        <div class="stat-value"><?= number_format($totalReceived, 3) ?> g</div>
        <div class="stat-sub">Receipts: <?= number_format($receiptKhalis, 3) ?> · Inv Rec: <?= number_format($invoiceReceived, 3) ?> · Multi Rec: <?= number_format($multipleReceived, 3) ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box"></i></div>
        <div class="stat-label">Inventory Closing</div>
        <div class="stat-value"><?= number_format($inventoryClosing, 3) ?> g</div>
        <div class="stat-sub">Opening <?= number_format($openingBalance, 3) ?> + Given - Received</div>
    </div>
<!--
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-label">Wasooli (Gold)</div>
        <div class="stat-value"><?= number_format($totalWasooli, 3) ?> g</div>
        <div class="stat-sub">Single + Multiple wasooli</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        <div class="stat-label">Remaining Balance</div>
        <div class="stat-value"><?= number_format($totalRemaining, 3) ?> g</div>
        <div class="stat-sub">Total customer outstanding</div>
    </div>-->
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <div class="card" style="padding: 24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Recent Single Invoices</h3>
        <table>
            <thead><tr><th>Invoice #</th><th>Party</th><th>Date</th><th class="text-right">Gold (g)</th></tr></thead>
            <tbody>
                <?php if (empty($recentInvoices)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No invoices yet</td></tr>
                <?php else: foreach ($recentInvoices as $inv): ?>
                    <tr>
                        <td><a href="<?= url('invoices/show.php?id=' . $inv['id']) ?>" style="color:var(--gold-primary);text-decoration:none;"><?= htmlspecialchars($inv['invoice_no']) ?></a></td>
                        <td><?= htmlspecialchars($inv['customer_name'] ?? 'N/A') ?></td>
                        <td><?= formatDate($inv['invoice_date']) ?></td>
                        <td class="text-right mono"><?= number_format($inv['effective_gold'], 3) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding: 24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Recent Multiple Invoices</h3>
        <table>
            <thead><tr><th>Invoice #</th><th>Party</th><th>Date</th><th class="text-right">Gold (g)</th></tr></thead>
            <tbody>
                <?php if (empty($recentMultipleInvoices)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No multiple invoices</td></tr>
                <?php else: foreach ($recentMultipleInvoices as $mi): ?>
                    <tr>
                        <td><a href="<?= url('invoice-multiple/show.php?id=' . $mi['id']) ?>" style="color:var(--gold-primary);text-decoration:none;"><?= htmlspecialchars($mi['invoice_no']) ?></a></td>
                        <td><?= htmlspecialchars($mi['customer_name'] ?? 'N/A') ?></td>
                        <td><?= formatDate($mi['invoice_date']) ?></td>
                        <td class="text-right mono"><?= number_format($mi['effective_gold'], 3) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding: 24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Recent Gold Gives / Diya</h3>
        <table>
            <thead><tr><th>Give #</th><th>Party</th><th>Date</th><th class="text-right">Khalis (g)</th></tr></thead>
            <tbody>
                <?php if (empty($recentGoldGives)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No gold gives</td></tr>
                <?php else: foreach ($recentGoldGives as $g): ?>
                    <tr>
                        <td><a href="<?= url('gold-gives/show.php?id=' . $g['id']) ?>" style="color:var(--gold-primary);text-decoration:none;"><?= htmlspecialchars($g['give_no']) ?></a></td>
                        <td><?= htmlspecialchars($g['customer_name'] ?? 'N/A') ?></td>
                        <td><?= formatDate($g['give_date']) ?></td>
                        <td class="text-right mono text-danger"><?= number_format($g['total_khalis_weight'], 3) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card" style="padding: 24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Top Parties (by Balance)</h3>
        <table>
            <thead><tr><th>Party Name</th><th class="text-right">Balance (g)</th></tr></thead>
            <tbody>
                <?php if (empty($topCustomers)): ?>
                    <tr><td colspan="2" class="text-center text-muted">No parties yet</td></tr>
                <?php else: foreach ($topCustomers as $tc): ?>
                    <tr>
                        <td><a href="<?= url('customers/show.php?id=' . $tc['id']) ?>" style="color:var(--gold-primary);text-decoration:none;"><?= htmlspecialchars($tc['name']) ?></a></td>
                        <td class="text-right mono <?= $tc['balance'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($tc['balance'], 3) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
