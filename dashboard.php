<?php
require_once __DIR__ . '/config.php';
requireAuth();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/functions/ledger_functions.php';

$stats = getDashboardStats();

// Recent invoices
$db = getDB();
$recentInvoices = $db->query("SELECT i.*, c.name as customer_name 
                             FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id 
                             WHERE i.status = 'active' 
                             ORDER BY i.invoice_date DESC, i.id DESC LIMIT 10")->fetchAll();

// Top customers by balance
$topCustomers = [];
$allCustomers = $db->query("SELECT id, name, opening_balance FROM customers WHERE status = 'active'")->fetchAll();
foreach ($allCustomers as $c) {
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
        <a href="<?= url('invoices/create.php') ?>" class="btn btn-gold">
            <i class="bi bi-plus-circle"></i> New Invoice
        </a>
        <a href="<?= url('gold-receipts/create.php') ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> New Receipt
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div class="stat-label">Total Parties</div>
        <div class="stat-value"><?= $stats['total_customers'] ?></div>
        <div class="stat-sub">
            <?= $stats['customers_by_type']['customer'] ?> Customer · 
            <?= $stats['customers_by_type']['dukandar'] ?> Dukandar · 
            <?= $stats['customers_by_type']['karigar'] ?> Karigar
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-file-text"></i></div>
        <div class="stat-label">Active Invoices</div>
        <div class="stat-value"><?= $stats['total_invoices'] ?></div>
        <div class="stat-sub">Today: <?= $stats['today_invoices'] ?> invoices</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-inbox"></i></div>
        <div class="stat-label">Gold Receipts</div>
        <div class="stat-value"><?= $stats['total_gold_receipts'] ?></div>
        <div class="stat-sub">Today: <?= $stats['today_receipts'] ?> receipts</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-gem"></i></div>
        <div class="stat-label">Gold Given (Effective)</div>
        <div class="stat-value"><?= number_format($stats['total_gold_khalis_given'], 3) ?> g</div>
        <div class="stat-sub">Total gold given to parties</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></div>
        <div class="stat-label">Gold Received</div>
        <div class="stat-value"><?= number_format($stats['total_received_khalis'], 3) ?> g</div>
        <div class="stat-sub">From receipts & invoice receives</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-box"></i></div>
        <div class="stat-label">Inventory Closing</div>
        <div class="stat-value"><?= number_format($stats['total_inventory_closing'], 3) ?> g</div>
        <div class="stat-sub">Opening + Received - Given</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-label">Wasooli (Gold)</div>
        <div class="stat-value"><?= number_format($stats['total_wasooli'], 2) ?> g</div>
        <div class="stat-sub">Total cash received</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        <div class="stat-label">Remaining Balance</div>
        <div class="stat-value"><?= number_format($stats['total_remaining_balance'], 3) ?> g</div>
        <div class="stat-sub">Total outstanding</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Recent Invoices -->
    <div class="card" style="padding: 24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Recent Invoices</h3>
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Party</th>
                    <th>Date</th>
                    <th class="text-right">Gold (g)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentInvoices)): ?>
                    <tr><td colspan="4" class="text-center text-muted">No invoices yet</td></tr>
                <?php else: ?>
                    <?php foreach ($recentInvoices as $inv): ?>
                    <tr>
                        <td><a href="<?= url('invoices/show.php?id=' . $inv['id']) ?>" style="color:var(--gold-primary);text-decoration:none;"><?= htmlspecialchars($inv['invoice_no']) ?></a></td>
                        <td><?= htmlspecialchars($inv['customer_name'] ?? 'N/A') ?></td>
                        <td><?= formatDate($inv['invoice_date']) ?></td>
                        <td class="text-right mono"><?= number_format($inv['effective_gold'], 3) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Top Customers -->
    <div class="card" style="padding: 24px;">
        <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Top Parties (by Balance)</h3>
        <table>
            <thead>
                <tr>
                    <th>Party Name</th>
                    <th class="text-right">Balance (g)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topCustomers)): ?>
                    <tr><td colspan="2" class="text-center text-muted">No parties yet</td></tr>
                <?php else: ?>
                    <?php foreach ($topCustomers as $tc): ?>
                    <tr>
                        <td><a href="<?= url('customers/show.php?id=' . $tc['id']) ?>" style="color:var(--gold-primary);text-decoration:none;"><?= htmlspecialchars($tc['name']) ?></a></td>
                        <td class="text-right mono <?= $tc['balance'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($tc['balance'], 3) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>