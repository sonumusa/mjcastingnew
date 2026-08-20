<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$pdo = getDB();

$start_date   = $_GET['start_date']   ?? $_COOKIE['report_start_date'] ?? date('Y-m-01');
$end_date     = $_GET['end_date']     ?? $_COOKIE['report_end_date']   ?? date('Y-m-t');
$report_type  = $_GET['report_type']  ?? 'wax'; // 'wax' or 'design'

// Persist dates in cookies
setcookie('report_start_date', $start_date, time() + (86400 * 30), "/");
setcookie('report_end_date',   $end_date,   time() + (86400 * 30), "/");

$page_title = ($report_type === 'design') ? "Design Report" : "Wax Report";

// ──────────────────────────────────────────────
//  FETCH DATA BASED ON REPORT TYPE
// ──────────────────────────────────────────────

if ($report_type === 'design') {

    /*
     * DESIGN REPORT
     * All NON-wax items from invoice_items, including worker name.
     *
     * Case 1: Combined row (wax_item_id IS NOT NULL) → design part lives in
     *         item_id (name), design_qty, design_rate
     * Case 2: Standalone design row (wax_item_id IS NULL, category != 'wax')
     *         → qty, rate, item name
     *
     * ORDER BY ii.id ASC = exact entry/insertion order
     */

    $sql = "
        SELECT 
            sub.item_db_id,
            sub.invoice_id,
            sub.invoice_date,
            sub.customer_name,
            sub.design_item_name,
            sub.des_qty,
            sub.des_rate,
            sub.des_amount,
            sub.worker_name
        FROM (
            /* Case 1: Combined row — take design part */
            SELECT 
                ii.id            AS item_db_id,
                i.id             AS invoice_id,
                i.invoice_date,
                c.name           AS customer_name,
                it.name_urdu     AS design_item_name,
                ii.design_qty    AS des_qty,
                ii.design_rate   AS des_rate,
                ROUND(ii.design_qty * ii.design_rate) AS des_amount,
                w.name           AS worker_name
            FROM wax_invoice_items ii
            JOIN wax_invoices  i     ON ii.invoice_id = i.id
            JOIN wax_customers c     ON i.customer_id = c.id
            JOIN wax_items     it    ON ii.item_id    = it.id
            LEFT JOIN wax_workers w  ON ii.worker_id  = w.id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND ii.wax_item_id IS NOT NULL
              AND ii.design_qty > 0

            UNION ALL

            /* Case 2: Standalone design row */
            SELECT 
                ii.id            AS item_db_id,
                i.id             AS invoice_id,
                i.invoice_date,
                c.name           AS customer_name,
                it.name_urdu     AS design_item_name,
                ii.qty           AS des_qty,
                ii.rate          AS des_rate,
                ROUND(ii.qty * ii.rate) AS des_amount,
                w.name           AS worker_name
            FROM wax_invoice_items ii
            JOIN wax_invoices  i     ON ii.invoice_id = i.id
            JOIN wax_customers c     ON i.customer_id = c.id
            JOIN wax_items     it    ON ii.item_id    = it.id
            LEFT JOIN wax_workers w  ON ii.worker_id  = w.id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND ii.wax_item_id IS NULL
              AND it.category != 'wax'
              AND ii.qty > 0
        ) sub
        ORDER BY sub.item_db_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
    $rows = $stmt->fetchAll();

    // Totals
    $total_qty    = 0;
    $total_amount = 0;
    foreach ($rows as $r) {
        $total_qty    += (float)$r['des_qty'];
        $total_amount += (float)$r['des_amount'];
    }

} else {

    /*
     * WAX REPORT  (original)
     * Case 1: Combined row → wax_item_id set, wax_qty, wax_rate
     * Case 2: Standalone wax → item category = 'wax', qty, rate
     */

    $sql = "
        SELECT 
            sub.item_db_id,
            sub.invoice_id,
            sub.invoice_date,
            sub.customer_name,
            sub.wax_item_name,
            sub.wax_qty,
            sub.wax_rate,
            sub.wax_amount
        FROM (
            SELECT 
                ii.id            AS item_db_id,
                i.id             AS invoice_id,
                i.invoice_date,
                c.name           AS customer_name,
                it_wax.name_urdu AS wax_item_name,
                ii.wax_qty,
                ii.wax_rate,
                ROUND(ii.wax_qty * ii.wax_rate) AS wax_amount
            FROM wax_invoice_items ii
            JOIN wax_invoices  i      ON ii.invoice_id  = i.id
            JOIN wax_customers c      ON i.customer_id  = c.id
            JOIN wax_items     it_wax ON ii.wax_item_id = it_wax.id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND ii.wax_item_id IS NOT NULL
              AND ii.wax_qty > 0

            UNION ALL

            SELECT 
                ii.id            AS item_db_id,
                i.id             AS invoice_id,
                i.invoice_date,
                c.name           AS customer_name,
                it.name_urdu     AS wax_item_name,
                ii.qty           AS wax_qty,
                ii.rate          AS wax_rate,
                ROUND(ii.qty * ii.rate) AS wax_amount
            FROM wax_invoice_items ii
            JOIN wax_invoices  i  ON ii.invoice_id = i.id
            JOIN wax_customers c  ON i.customer_id = c.id
            JOIN wax_items     it ON ii.item_id    = it.id
            WHERE i.invoice_date BETWEEN ? AND ?
              AND ii.wax_item_id IS NULL
              AND it.category = 'wax'
              AND ii.qty > 0
        ) sub
        ORDER BY sub.item_db_id ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$start_date, $end_date, $start_date, $end_date]);
    $rows = $stmt->fetchAll();

    // Totals
    $total_qty    = 0;
    $total_amount = 0;
    foreach ($rows as $r) {
        $total_qty    += (float)$r['wax_qty'];
        $total_amount += (float)$r['wax_amount'];
    }
}

$total_entries = count($rows);

// Labels that change based on type
$is_wax        = ($report_type === 'wax');
$type_icon     = $is_wax ? '🕯️' : '✏️';
$type_label    = $is_wax ? 'Wax'    : 'Design';
$qty_label     = $is_wax ? 'Wax Weight' : 'Design Qty';
$qty_sub       = $is_wax ? 'Total wax quantity (grams)' : 'Total design pieces';
$amount_label  = $is_wax ? 'Total Wax Amount' : 'Total Design Amount';
$detail_title  = $is_wax ? 'Wax Items Detail — Entry Order' : 'Design Items Detail — Entry Order';

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

* { box-sizing: border-box; }

.wax-report-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 24px;
}

.page-header { margin-bottom: 32px; }
.page-header h1 {
    font-size: 28px; font-weight: 700;
    color: var(--gray-900); margin: 0 0 8px 0;
}
.page-header p {
    color: var(--gray-500); margin: 0; font-size: 15px;
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
    font-size: 13px; font-weight: 600;
    color: var(--gray-700);
    text-transform: uppercase; letter-spacing: 0.5px;
}
.filter-group input[type="date"],
.filter-group select {
    padding: 10px 14px;
    border: 1px solid var(--gray-300);
    border-radius: var(--radius-sm);
    font-size: 14px;
    color: var(--gray-800);
    background: white;
    min-width: 160px;
    transition: all 0.2s;
}
.filter-group input[type="date"]:focus,
.filter-group select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}
.filter-actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
}

/* Type selector highlight */
.filter-group select.type-select {
    font-weight: 600;
    min-width: 180px;
    padding-right: 32px;
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 12px center;
}
.filter-group select.type-select.wax-active {
    border-color: #f59e0b;
    background-color: #fffbeb;
    color: #92400e;
}
.filter-group select.type-select.design-active {
    border-color: #8b5cf6;
    background-color: #f5f3ff;
    color: #5b21b6;
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
    background: var(--primary); color: white;
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
.btn-secondary:hover { background: var(--gray-200); }
.btn-back {
    background: var(--gray-100);
    color: var(--gray-700);
    border: 1px solid var(--gray-300);
    text-decoration: none;
}
.btn-back:hover {
    background: var(--gray-200);
    text-decoration: none;
    color: var(--gray-700);
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}
@media (max-width: 768px) {
    .stats-grid { grid-template-columns: 1fr; }
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
    top: 0; left: 0; right: 0;
    height: 4px;
}
.stat-card.entries::before { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }
.stat-card.weight::before  { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.stat-card.amount::before  { background: linear-gradient(90deg, #10b981, #34d399); }

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}
.stat-label {
    font-size: 14px; font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase; letter-spacing: 0.5px;
}
.stat-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.stat-card.entries .stat-icon { background: #ede9fe; }
.stat-card.weight .stat-icon  { background: var(--warning-light); }
.stat-card.amount .stat-icon  { background: var(--success-light); }

.stat-value {
    font-size: 32px; font-weight: 700;
    color: var(--gray-900); margin-bottom: 8px;
}
.stat-card.entries .stat-value { color: #7c3aed; }
.stat-card.weight .stat-value  { color: var(--warning); }
.stat-card.amount .stat-value  { color: var(--success); }

.stat-subtitle { font-size: 13px; color: var(--gray-500); }

/* Card */
.card {
    background: white;
    border-radius: var(--radius);
    border: 1px solid var(--gray-200);
    overflow: hidden;
    margin-bottom: 24px;
}
.card-header {
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.card-header h3 {
    margin: 0; font-size: 16px; font-weight: 600;
    color: var(--gray-800);
    display: flex; align-items: center; gap: 10px;
}
.card-header h3 .icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}
.data-table thead th {
    padding: 14px 16px;
    text-align: left;
    font-size: 12px; font-weight: 600;
    color: var(--gray-500);
    text-transform: uppercase; letter-spacing: 0.5px;
    background: var(--gray-50);
    border-bottom: 2px solid var(--gray-200);
    white-space: nowrap;
}
.data-table thead th.text-right  { text-align: right; }
.data-table thead th.text-center { text-align: center; }

.data-table tbody tr {
    border-bottom: 1px solid var(--gray-100);
    transition: background 0.2s;
}
.data-table tbody tr:hover { background: var(--gray-50); }

.data-table tbody td {
    padding: 14px 16px;
    font-size: 14px;
    color: var(--gray-700);
}
.data-table tbody td.text-right  { text-align: right; }
.data-table tbody td.text-center { text-align: center; }

.data-table tbody td.item-name {
    font-weight: 500;
    color: var(--gray-800);
    direction: rtl;
    text-align: right;
    font-family: 'Noto Nastaliq Urdu', Arial, sans-serif;
    font-size: 15px;
}
.data-table tbody td.mono {
    font-family: 'Courier New', monospace;
    font-weight: 600;
}
.data-table tbody td .sr-num {
    display: inline-flex;
    align-items: center; justify-content: center;
    width: 32px; height: 32px;
    background: var(--gray-100);
    border-radius: 50%;
    font-size: 13px; font-weight: 600;
    color: var(--gray-600);
}
.data-table tbody td .customer-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #dbeafe; color: #1e40af;
    border-radius: 20px;
    font-size: 13px; font-weight: 500;
}
.data-table tbody td .inv-badge {
    display: inline-block;
    padding: 3px 8px;
    background: var(--gray-100); color: var(--gray-600);
    border-radius: 6px;
    font-size: 12px; font-weight: 600;
}
.data-table tbody td .date-text {
    color: var(--gray-500); font-size: 13px;
}
.data-table tbody td .amount-text {
    font-weight: 700; color: var(--gray-800); font-size: 15px;
}
.data-table tbody td .qty-text {
    font-weight: 600; color: var(--warning);
}
.data-table tbody td .rate-text {
    color: var(--gray-600);
}

/* Worker badge */
.worker-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #fce7f3;
    color: #9d174d;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}
.worker-badge .worker-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #ec4899;
}
.worker-badge.no-worker {
    background: var(--gray-100);
    color: var(--gray-400);
}
.worker-badge.no-worker .worker-dot {
    background: var(--gray-300);
}

/* Footer Row */
.data-table tfoot tr {
    border-top: 3px solid var(--gray-300);
}
.data-table tfoot td {
    padding: 18px 16px;
    font-size: 15px; font-weight: 700;
    background: linear-gradient(135deg, #1e3a5f 0%, #0f172a 100%);
    color: white;
}
.data-table tfoot td.text-right { text-align: right; }
.data-table tfoot td .total-label {
    font-size: 16px; letter-spacing: 1px;
}
.data-table tfoot td .total-value {
    font-size: 18px; color: #fbbf24;
}
.data-table tfoot td .total-value-green {
    font-size: 20px; color: #4ade80;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--gray-500);
}
.empty-state .icon {
    font-size: 64px; margin-bottom: 16px; opacity: 0.4;
}
.empty-state h3 {
    font-size: 18px; color: var(--gray-700); margin: 0 0 8px 0;
}
.empty-state p { margin: 0; font-size: 14px; }

/* Type indicator pill */
.type-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 16px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: 0.5px;
}
.type-indicator.wax {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fbbf24;
}
.type-indicator.design {
    background: #f5f3ff;
    color: #5b21b6;
    border: 1px solid #a78bfa;
}

/* Responsive */
@media (max-width: 768px) {
    .wax-report-container { padding: 16px; }
    .filter-row { flex-direction: column; align-items: stretch; }
    .filter-actions { margin-left: 0; }
    .data-table { font-size: 12px; }
    .data-table thead th, .data-table tbody td { padding: 10px 8px; }
}

/* Print */
@media print {
    .filter-section, .btn-back, .btn { display: none !important; }
    .wax-report-container { padding: 0; }
    .card { box-shadow: none; border: 1px solid #ddd; }
    .data-table tfoot td {
        background: #1e3a5f !important;
        color: white !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .stat-card::before {
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">

<div class="wax-report-container">

    <!-- Page Header <a href="report.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-back">-->
    <div class="page-header">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
            <div>
                <h1><?= $type_icon ?> <?= $type_label ?> Report</h1>
                <p>All <?= strtolower($type_label) ?> items from invoices — <?= date('F j', strtotime($start_date)) ?> to <?= date('F j, Y', strtotime($end_date)) ?></p>
            </div>
            <a href="reports.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-back">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Reports
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form method="GET" id="filterForm" class="filter-row">
            <div class="filter-group">
                <label>Report Type</label>
                <select name="report_type" class="type-select <?= $is_wax ? 'wax-active' : 'design-active' ?>"
                        onchange="document.getElementById('filterForm').submit();">
                    <option value="wax"    <?= $is_wax ? 'selected' : '' ?>>🕯️  Wax Items</option>
                    <option value="design" <?= !$is_wax ? 'selected' : '' ?>>✏️  Design Items</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="filter-group">
                <label>End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Apply Filter
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.print()">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Print
                </button>
            </div>
        </form>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card entries">
            <div class="stat-header">
                <span class="stat-label">Total Entries</span>
                <div class="stat-icon">📋</div>
            </div>
            <div class="stat-value"><?= number_format($total_entries) ?></div>
            <div class="stat-subtitle"><?= $type_label ?> line items in date range</div>
        </div>

        <div class="stat-card weight">
            <div class="stat-header">
                <span class="stat-label"><?= $qty_label ?></span>
                <div class="stat-icon"><?= $is_wax ? '⚖️' : '🔢' ?></div>
            </div>
            <div class="stat-value"><?= $is_wax ? number_format($total_qty, 3) : number_format($total_qty, 0) ?></div>
            <div class="stat-subtitle"><?= $qty_sub ?></div>
        </div>

        <div class="stat-card amount">
            <div class="stat-header">
                <span class="stat-label"><?= $amount_label ?></span>
                <div class="stat-icon">💰</div>
            </div>
            <div class="stat-value"><?= format_currency($total_amount) ?></div>
            <div class="stat-subtitle">Total <?= strtolower($type_label) ?> billing amount</div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="card">
        <div class="card-header">
            <h3>
                <span class="icon" style="background: <?= $is_wax ? '#fef3c7' : '#ede9fe' ?>; color: <?= $is_wax ? '#d97706' : '#7c3aed' ?>;">
                    <?= $type_icon ?>
                </span>
                <?= $detail_title ?>
            </h3>
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="type-indicator <?= $report_type ?>"><?= $type_icon ?> <?= strtoupper($type_label) ?></span>
                <span style="font-size: 13px; color: var(--gray-500);"><?= $total_entries ?> entries</span>
            </div>
        </div>

        <?php if (!empty($rows)): ?>
        <div style="overflow-x: auto;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px;">Sr#</th>
                        <th style="width: 95px;">Date</th>
                        <th class="text-center" style="width: 65px;">Bill#</th>
                        <th>Customer</th>
                        <?php if (!$is_wax): ?>
                        <th>Worker</th>
                        <?php endif; ?>
                        <th><?= $type_label ?> Item</th>
                        <th class="text-right" style="width: 95px;"><?= $is_wax ? 'Wax Qty' : 'Qty' ?></th>
                        <th class="text-right" style="width: 95px;">Rate</th>
                        <th class="text-right" style="width: 115px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sr = 1;
                    foreach ($rows as $row):

                        if ($is_wax) {
                            $item_name = $row['wax_item_name'] ?: '-';
                            $qty_val   = (float)$row['wax_qty'];
                            $rate_val  = (float)$row['wax_rate'];
                            $amt_val   = (float)$row['wax_amount'];
                            $qty_fmt   = number_format($qty_val, 3);
                        } else {
                            $item_name   = $row['design_item_name'] ?: '-';
                            $qty_val     = (float)$row['des_qty'];
                            $rate_val    = (float)$row['des_rate'];
                            $amt_val     = (float)$row['des_amount'];
                            $qty_fmt     = number_format($qty_val, 0);
                            $worker_name = $row['worker_name'] ?: '';
                        }
                    ?>
                    <tr>
                        <td class="text-center">
                            <span class="sr-num"><?= $sr++ ?></span>
                        </td>
                        <td>
                            <span class="date-text"><?= date('d-M-Y', strtotime($row['invoice_date'])) ?></span>
                        </td>
                        <td class="text-center">
                            <span class="inv-badge">#<?= $row['invoice_id'] ?></span>
                        </td>
                        <td>
                            <span class="customer-badge"><?= htmlspecialchars($row['customer_name']) ?></span>
                        </td>
                        <?php if (!$is_wax): ?>
                        <td>
                            <?php if (!empty($worker_name)): ?>
                                <span class="worker-badge">
                                    <span class="worker-dot"></span>
                                    <?= htmlspecialchars($worker_name) ?>
                                </span>
                            <?php else: ?>
                                <span class="worker-badge no-worker">
                                    <span class="worker-dot"></span>
                                    No Worker
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td class="item-name">
                            <?= htmlspecialchars($item_name) ?>
                        </td>
                        <td class="text-right mono">
                            <span class="qty-text"><?= $qty_fmt ?></span>
                        </td>
                        <td class="text-right mono">
                            <span class="rate-text"><?= number_format($rate_val, 2) ?></span>
                        </td>
                        <td class="text-right">
                            <span class="amount-text"><?= number_format($amt_val, 0) ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="<?= $is_wax ? 5 : 6 ?>" class="text-right">
                            <span class="total-label">GRAND TOTAL</span>
                        </td>
                        <td class="text-right">
                            <span class="total-value"><?= $is_wax ? number_format($total_qty, 3) : number_format($total_qty, 0) ?></span>
                        </td>
                        <td class="text-right">&mdash;</td>
                        <td class="text-right">
                            <span class="total-value-green"><?= number_format($total_amount, 0) ?></span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="icon"><?= $type_icon ?></div>
            <h3>No <?= $type_label ?> Items Found</h3>
            <p>There are no <?= strtolower($type_label) ?> items in invoices for the selected date range.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>