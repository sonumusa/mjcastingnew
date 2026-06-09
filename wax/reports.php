<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$page_title = "Reports & Analytics";
$pdo = getDB();

$start_date = $_GET['start_date'] ?? $_COOKIE['report_start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? $_COOKIE['report_end_date'] ?? date('Y-m-t');

// Persist dates in cookies
setcookie('report_start_date', $start_date, time() + (86400 * 30), "/");
setcookie('report_end_date', $end_date, time() + (86400 * 30), "/");

// 1. Total Payments Received (FROM wax_payments table)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE payment_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_payments = $stmt->fetchColumn() ?: 0;

// 2. Total Expenses
$stmt = $pdo->prepare("SELECT SUM(amount) FROM wax_expenses WHERE expense_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_expenses = $stmt->fetchColumn() ?: 0;

// 3. Net Profit (Simple: Payments - Expenses)
$net_profit = $total_payments - $total_expenses;

// 4. Invoice Stats (for reference/other sections)
$sql = "
    SELECT 
        SUM(ii.amount) as total_billed,
        SUM(ii.amount * (ii.worker_percentage / 100)) as total_worker_comm
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    WHERE i.invoice_date BETWEEN ? AND ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$invoice_stats = $stmt->fetch();

$total_billed = $invoice_stats['total_billed'] ?: 0;
$total_worker_comm = $invoice_stats['total_worker_comm'] ?: 0;

// 5. Worker Breakdown
$sql_workers = "
    SELECT 
        w.id,
        w.name, 
        SUM(ii.commission_amount) as commission
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_workers w ON ii.worker_id = w.id
    WHERE i.invoice_date BETWEEN ? AND ?
    GROUP BY w.id, w.name
    HAVING commission > 0
    ORDER BY commission DESC
";
$stmt = $pdo->prepare($sql_workers);
$stmt->execute([$start_date, $end_date]);
$worker_stats = $stmt->fetchAll();

// 6. Partners - Share of Net Profit
$partners = $pdo->query("SELECT name, share_percentage FROM wax_partners WHERE active = 1")->fetchAll();

// 7. Customers
$customers = get_customers();

// Calculate profit margin
$profit_margin = $total_payments > 0 ? ($net_profit / $total_payments) * 100 : 0;

include __DIR__ . '/templates/header.php';
?>

<style>
:root {
    --primary: #4f46e5;
    --primary-light: #818cf8;
    --primary-dark: #3730a3;
    --success: #10b981;
    --success-light: #d1fae5;
    --warning: #f59e0b;
    --warning-light: #fef3c7;
    --danger: #ef4444;
    --danger-light: #fee2e2;
    --info: #3b82f6;
    --info-light: #dbeafe;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --radius: 12px;
    --radius-sm: 8px;
}

* {
    box-sizing: border-box;
}

.reports-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

/* Page Header */
.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 8px 0;
}

.page-header p {
    color: var(--gray-500);
    margin: 0;
    font-size: 15px;
}

/* Filter Section */
.filter-section {
    background: white;
    border-radius: var(--radius);
    padding: 20px 24px;
    box-shadow: var(--shadow);
    margin-bottom: 24px;
    border: 1px solid var(--gray-200);
}

.filter-row {
    display: flex;
    align-items: flex-end;
    gap: 16px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
    color: var(--gray-700);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-group input[type="date"] {
    padding: 10px 14px;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-size: 14px;
    color: var(--gray-800);
    background: white;
    min-width: 160px;
    transition: all 0.2s;
}

.filter-group input[type="date"]:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.filter-actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 18px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    border: none;
    text-decoration: none;
}

.btn-primary {
    background: var(--primary);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-dark);
    transform: translateY(-1px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-300);
}

.btn-secondary:hover {
    background: var(--gray-200);
}

/* Quick Actions Grid */
.quick-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-bottom: 24px;
}

.quick-action-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    text-decoration: none;
    color: inherit;
    transition: all 0.2s;
}

.quick-action-card:hover {
    border-color: var(--primary);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.quick-action-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.quick-action-icon.purple { background: #ede9fe; color: #7c3aed; }
.quick-action-icon.blue { background: #dbeafe; color: #2563eb; }
.quick-action-icon.amber { background: #fef3c7; color: #d97706; }
.quick-action-icon.green { background: #d1fae5; color: #059669; }

.quick-action-text h4 {
    margin: 0 0 2px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-800);
}

.quick-action-text span {
    font-size: 12px;
    color: var(--gray-500);
}

/* Stats Grid - 3 Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

@media (max-width: 900px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

.stat-card {
    background: white;
    border-radius: var(--radius);
    padding: 24px;
    border: 1px solid var(--gray-200);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
}

.stat-card.payments::before { background: linear-gradient(90deg, #10b981, #34d399); }
.stat-card.expenses::before { background: linear-gradient(90deg, #ef4444, #f87171); }
.stat-card.profit::before { background: linear-gradient(90deg, #3b82f6, #60a5fa); }

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.stat-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-card.payments .stat-icon { background: var(--success-light); }
.stat-card.expenses .stat-icon { background: var(--danger-light); }
.stat-card.profit .stat-icon { background: var(--info-light); }

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 8px;
}

.stat-card.payments .stat-value { color: var(--success); }
.stat-card.expenses .stat-value { color: var(--danger); }
.stat-card.profit .stat-value { color: <?= $net_profit >= 0 ? 'var(--success)' : 'var(--danger)' ?>; }

.stat-subtitle {
    font-size: 13px;
    color: var(--gray-500);
}

/* Main Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

@media (max-width: 900px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

/* Cards */
.card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    overflow: hidden;
}

.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-header h3 .icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

.card-body {
    padding: 24px;
}

/* Business Summary Card - Highlighted */
.summary-card {
    background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
    color: white;
    border: none;
}

.summary-card .card-header {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.summary-card .card-header h3 {
    color: white;
}

.summary-table {
    width: 100%;
    border-collapse: collapse;
}

.summary-table tr {
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.summary-table tr:last-child {
    border-bottom: none;
}

.summary-table th,
.summary-table td {
    padding: 18px 0;
    font-size: 16px;
}

.summary-table th {
    text-align: left;
    font-weight: 500;
    color: rgba(255,255,255,0.7);
}

.summary-table td {
    text-align: right;
    font-weight: 600;
    color: white;
    font-size: 18px;
}

.summary-table tr.total {
    background: rgba(255,255,255,0.1);
    margin: 0 -24px;
}

.summary-table tr.total th,
.summary-table tr.total td {
    padding: 20px 16px;
    font-size: 20px;
    font-weight: 700;
}

.summary-table tr.total td.profit-positive {
    color: #4ade80;
}

.summary-table tr.total td.profit-negative {
    color: #f87171;
}

.text-green { color: #4ade80 !important; }
.text-red { color: #f87171 !important; }

/* Formula Display */
.formula-display {
    background: rgba(255,255,255,0.05);
    border-radius: var(--radius-sm);
    padding: 16px;
    margin-top: 20px;
    text-align: center;
}

.formula-display .formula {
    font-size: 14px;
    color: rgba(255,255,255,0.6);
    margin-bottom: 8px;
}

.formula-display .result {
    font-size: 13px;
    color: rgba(255,255,255,0.5);
}

/* Partner Cards */
.partner-grid {
    display: grid;
    gap: 12px;
}

.partner-card {
    display: flex;
    align-items: center;
    padding: 16px;
    background: var(--gray-50);
    border-radius: var(--radius-sm);
    gap: 16px;
    transition: all 0.2s;
}

.partner-card:hover {
    background: var(--gray-100);
}

.partner-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.partner-info {
    flex: 1;
}

.partner-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 2px;
}

.partner-percentage {
    font-size: 13px;
    color: var(--gray-500);
}

.partner-amount {
    text-align: right;
}

.partner-amount .value {
    font-size: 20px;
    font-weight: 700;
    color: <?= $net_profit >= 0 ? 'var(--success)' : 'var(--danger)' ?>;
}

.partner-amount .label {
    font-size: 12px;
    color: var(--gray-500);
}

/* Progress Bar */
.progress-bar {
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 8px;
}

.progress-fill {
    height: 100%;
    border-radius: 3px;
    transition: width 0.5s ease;
}

/* Worker Table */
.worker-table {
    width: 100%;
    border-collapse: collapse;
}

.worker-table thead th {
    padding: 12px 16px;
    text-align: left;
    font-size: 12px;
    font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.worker-table tbody tr {
    border-bottom: 1px solid var(--gray-100);
    transition: background 0.2s;
}

.worker-table tbody tr:hover {
    background: var(--gray-50);
}

.worker-table tbody td {
    padding: 16px;
    font-size: 14px;
}

.worker-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.worker-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f0abfc 0%, #c084fc 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.worker-name {
    font-weight: 500;
    color: var(--gray-800);
}

.commission-amount {
    font-weight: 700;
    color: var(--gray-800);
    font-size: 15px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 6px;
}

/* Customer Statement Section */
.customer-statement-section {
    background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
    padding: 20px 24px;
    border-radius: var(--radius);
    margin-bottom: 24px;
}

.customer-statement-section h4 {
    margin: 0 0 16px 0;
    font-size: 15px;
    font-weight: 600;
    color: #5b21b6;
    display: flex;
    align-items: center;
    gap: 8px;
}

.customer-statement-form {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.customer-statement-form .select-wrapper {
    flex: 1;
    min-width: 250px;
}

.customer-statement-form select {
    width: 100%;
    padding: 10px 14px;
    border: 1px solid rgba(91, 33, 182, 0.3);
    border-radius: var(--radius-sm);
    font-size: 14px;
    background: white;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--gray-500);
}

.empty-state .icon {
    font-size: 48px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-state p {
    margin: 0;
    font-size: 14px;
}

/* Print styles */
@media print {
    .filter-section,
    .quick-actions,
    .customer-statement-section,
    .btn {
        display: none !important;
    }
    
    .reports-container {
        padding: 0;
    }
    
    .card, .summary-card {
        box-shadow: none;
        border: 1px solid #ddd;
        break-inside: avoid;
    }
    
    .summary-card {
        background: #1e3a5f !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<div class="reports-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>📊 Reports & Analytics</h1>
        <p>Financial overview for <?= date('F j', strtotime($start_date)) ?> - <?= date('F j, Y', strtotime($end_date)) ?></p>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" class="filter-row">
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= $start_date ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= $end_date ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Apply Filter
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>
            </div>
        </form>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="print_shamriz_book.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="quick-action-card">
            <div class="quick-action-icon purple">📚</div>
            <div class="quick-action-text">
                <h4>Shamriz Book</h4>
                <span>Special format report</span>
            </div>
        </a>
        <a href="report_bulk_worker.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="quick-action-card">
            <div class="quick-action-icon blue">👷</div>
            <div class="quick-action-text">
                <h4>Worker Statements</h4>
                <span>Bulk worker reports</span>
            </div>
        </a>
        <a href="report_partner_advanced.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="quick-action-card">
            <div class="quick-action-icon amber">📈</div>
            <div class="quick-action-text">
                <h4>Partner Report</h4>
                <span>Detailed breakdown</span>
            </div>
        </a>
                <a href="report_wax.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="quick-action-card">
            <div class="quick-action-icon amber">📈</div>
            <div class="quick-action-text">
                <h4>Wax Report</h4>
                <span>Detailed breakdown</span>
            </div>
        </a>
        <a href="#" class="quick-action-card" onclick="document.getElementById('customerSelect').focus(); return false;">
            <div class="quick-action-icon green">📄</div>
            <div class="quick-action-text">
                <h4>Customer Statement</h4>
                <span>Individual statements</span>
            </div>
        </a>
    </div>

    <!-- Stats Cards - 3 Main Metrics -->
    <div class="stats-grid">
        <div class="stat-card payments">
            <div class="stat-header">
                <span class="stat-label">Total Payments</span>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-value"><?= format_currency($total_payments) ?></div>
            <div class="stat-subtitle">Received FROM wax_customers</div>
        </div>
        
        <div class="stat-card expenses">
            <div class="stat-header">
                <span class="stat-label">Total Expenses</span>
                <div class="stat-icon">📉</div>
            </div>
            <div class="stat-value"><?= format_currency($total_expenses) ?></div>
            <div class="stat-subtitle">Business expenses</div>
        </div>
        
        <div class="stat-card profit">
            <div class="stat-header">
                <span class="stat-label">Net Profit</span>
                <div class="stat-icon"><?= $net_profit >= 0 ? '📈' : '📉' ?></div>
            </div>
            <div class="stat-value"><?= format_currency($net_profit) ?></div>
            <div class="stat-subtitle"><?= $net_profit >= 0 ? 'Profit' : 'Loss' ?> for the period</div>
        </div>
    </div>

    <!-- Customer Statement Section -->
    <div class="customer-statement-section">
        <h4>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Generate Customer Statement
        </h4>
        <form action="statement_customer.php" method="GET" class="customer-statement-form">
            <input type="hidden" name="start_date" value="<?= $start_date ?>">
            <input type="hidden" name="end_date" value="<?= $end_date ?>">
            <div class="select-wrapper">
                <select name="customer_id" id="customerSelect" class="select2" required>
                    <option value="">Select a customer...</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
                View Statement
            </button>
        </form>
    </div>

    <!-- Main Content Grid -->
    <div class="content-grid">
        <!-- Business Summary Card - Dark Theme -->
        <div class="card summary-card">
            <div class="card-header">
                <h3>
                    <span class="icon" style="background: rgba(255,255,255,0.1);">💼</span>
                    Business Summary
                </h3>
            </div>
            <div class="card-body">
                <table class="summary-table">
                    <tr>
                        <td class="text-green">Total Payments</td>
                        <td class="text-green"><?= format_currency($total_payments) ?></td>
                    </tr>
                    <tr>
                        <td class="text-red">Total Expenses</td>
                        <td class="text-red">- <?= format_currency($total_expenses) ?></td>
                    </tr>
                    <tr class="total">
                        <td class="<?= $net_profit >= 0 ? 'profit-positive' : 'profit-negative' ?>">Net Profit</td>
                        <td class="<?= $net_profit >= 0 ? 'profit-positive' : 'profit-negative' ?>">
                            <?= format_currency($net_profit) ?>
                        </td>
                    </tr>
                </table>
                

            </div>
        </div>

        <!-- Partner Shares Card -->
        <div class="card">
            <div class="card-header">
                <h3>
                    <span class="icon" style="background: #fef3c7; color: #d97706;">🤝</span>
                    Partner Shares
                </h3>
                <span style="font-size: 12px; color: var(--gray-500);">Based on net profit</span>
            </div>
            <div class="card-body">
                <div class="partner-grid">
                    <?php foreach ($partners as $index => $p): 
                        $share_amt = $net_profit * ($p['share_percentage'] / 100);
                        $colors = ['#4f46e5', '#7c3aed', '#2563eb', '#0891b2'];
                        $color = $colors[$index % count($colors)];
                    ?>
                    <div class="partner-card">
                        <div class="partner-avatar" style="background: linear-gradient(135deg, <?= $color ?> 0%, <?= $color ?>cc 100%);">
                            <?= strtoupper(substr($p['name'], 0, 1)) ?>
                        </div>
                        <div class="partner-info">
                            <div class="partner-name"><?= htmlspecialchars($p['name']) ?></div>
                            <div class="partner-percentage"><?= $p['share_percentage'] ?>% of net profit</div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?= $p['share_percentage'] ?>%; background: <?= $color ?>;"></div>
                            </div>
                        </div>
                        <div class="partner-amount">
                            <div class="value"><?= format_currency($share_amt) ?></div>
                            <div class="label"><?= $share_amt >= 0 ? 'Profit Share' : 'Loss Share' ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php if (empty($partners)): ?>
                <div class="empty-state">
                    <div class="icon">👥</div>
                    <p>No active partners found</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Additional Info Card -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3>
                <span class="icon" style="background: #dbeafe; color: #2563eb;">📋</span>
                Invoice Summary (Reference)
            </h3>
        </div>
        <div class="card-body">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="padding: 16px; background: var(--gray-50); border-radius: var(--radius-sm);">
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Total Invoiced</div>
                    <div style="font-size: 24px; font-weight: 700; color: var(--gray-800);"><?= format_currency($total_billed) ?></div>
                </div>
                <div style="padding: 16px; background: var(--gray-50); border-radius: var(--radius-sm);">
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Worker Commissions</div>
                    <div style="font-size: 24px; font-weight: 700; color: #ec4899;"><?= format_currency($total_worker_comm) ?></div>
                </div>
                <div style="padding: 16px; background: var(--gray-50); border-radius: var(--radius-sm);">
                    <div style="font-size: 13px; color: var(--gray-500); margin-bottom: 4px;">Outstanding Balance</div>
                    <div style="font-size: 24px; font-weight: 700; color: var(--warning);"><?= format_currency($total_billed - $total_payments) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Worker Commissions -->
    <div class="card">
        <div class="card-header">
            <h3>
                <span class="icon" style="background: #fce7f3; color: #ec4899;">👷</span>
                Worker Commissions
            </h3>
            <span style="font-size: 13px; color: var(--gray-500);"><?= count($worker_stats) ?> workers</span>
        </div>
        <?php if (!empty($worker_stats)): ?>
        <table class="worker-table">
            <thead>
                <tr>
                    <th>Worker</th>
                    <th style="text-align: right;">Commission Amount</th>
                    <th style="text-align: right; width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $max_commission = max(array_column($worker_stats, 'commission'));
                foreach ($worker_stats as $w): 
                    $percentage = $max_commission > 0 ? ($w['commission'] / $max_commission) * 100 : 0;
                ?>
                <tr>
                    <td>
                        <div class="worker-info">
                            <div class="worker-avatar">
                                <?= strtoupper(substr($w['name'], 0, 2)) ?>
                            </div>
                            <div>
                                <div class="worker-name"><?= htmlspecialchars($w['name']) ?></div>
                                <div style="margin-top: 4px;">
                                    <div class="progress-bar" style="width: 100px; height: 4px;">
                                        <div class="progress-fill" style="width: <?= $percentage ?>%; background: linear-gradient(90deg, #f0abfc, #c084fc);"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <span class="commission-amount"><?= format_currency($w['commission']) ?></span>
                    </td>
                    <td style="text-align: right;">
                        <a href="statement_worker.php?worker_id=<?= $w['id'] ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
                           class="btn btn-secondary btn-sm">
                            <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Statement
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="card-body">
            <div class="empty-state">
                <div class="icon">💰</div>
                <p>No worker commissions recorded for this period</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2').select2({
        placeholder: 'Select a customer...',
        allowClear: true,
        width: '100%'
    });
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>