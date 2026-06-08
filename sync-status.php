<?php
require_once __DIR__ . '/config.php';
requireAuth();

$pageTitle = 'Sync Status';

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Sync Status</h1>
        <p class="font-urdu" style="margin-top:4px;">ہم آہنگی کی صورتحال</p>
    </div>
</div>

<p style="color:var(--text-muted);margin-bottom:24px;">System is running in server mode. All data is synced live with the database.</p>

<?php
$db = getDB();
$today = date('Y-m-d');
$stmt = $db->prepare("SELECT COUNT(*) FROM invoices WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayInvoices = $stmt->fetchColumn();

$stmt = $db->prepare("SELECT COUNT(*) FROM gold_receipts WHERE DATE(created_at) = ?");
$stmt->execute([$today]);
$todayReceipts = $stmt->fetchColumn();

$stmt = $db->query("SELECT COUNT(*) FROM customers");
$totalCustomers = $stmt->fetchColumn();

$stmt = $db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoices WHERE status='active'");
$totalGold = (float)$stmt->fetchColumn();
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-circle-fill text-success"></i></div>
        <div class="stat-label">Connection</div>
        <div class="stat-value" style="color:var(--success);">Live (Online)</div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-file-text"></i></div>
        <div class="stat-label">Today's Invoices</div>
        <div class="stat-value"><?= $todayInvoices ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-inbox"></i></div>
        <div class="stat-label">Today's Receipts</div>
        <div class="stat-value"><?= $todayReceipts ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon"><i class="bi bi-people"></i></div>
        <div class="stat-label">Total Parties</div>
        <div class="stat-value"><?= $totalCustomers ?></div>
    </div>
</div>

<div class="card" style="padding:24px;margin-top:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">System Status</h3>
    <div class="detail-row"><span class="detail-label">Database</span><span class="detail-value" style="color:var(--success);">Connected (<?= DB_NAME ?>)</span></div>
    <div class="detail-row"><span class="detail-label">PHP Version</span><span class="detail-value"><?= phpversion() ?></span></div>
    <div class="detail-row"><span class="detail-label">Server Time</span><span class="detail-value"><?= date('Y-m-d h:i:s A') ?></span></div>
    <div class="detail-row"><span class="detail-label">Total Gold Given (All Time)</span><span class="detail-value mono"><?= number_format($totalGold, 3) ?> g</span></div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>