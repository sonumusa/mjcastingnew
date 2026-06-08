<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$pdo = getDB();

// 1. Total Customer Receipts (CASH IN)
$stmt = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE payment_date BETWEEN ? AND ?");
$stmt->execute([$start_date, $end_date]);
$total_receipts = $stmt->fetchColumn() ?: 0;

// 2. Worker Commission Breakdown
$stmt = $pdo->prepare("
    SELECT w.name, SUM(ii.amount * (ii.worker_percentage / 100)) as commission
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_workers w ON ii.worker_id = w.id
    WHERE i.invoice_date BETWEEN ? AND ?
    GROUP BY w.name
    ORDER BY commission DESC
");
$stmt->execute([$start_date, $end_date]);
$worker_commissions = $stmt->fetchAll();

$total_worker_income = 0;
foreach ($worker_commissions as $w) $total_worker_income += $w['commission'];

// 3. Expenses Breakdown
$stmt = $pdo->prepare("SELECT category, SUM(amount) as total FROM wax_expenses WHERE expense_date BETWEEN ? AND ? GROUP BY category");
$stmt->execute([$start_date, $end_date]);
$expenses_by_cat = $stmt->fetchAll();

$total_expenses = 0;
foreach ($expenses_by_cat as $e) $total_expenses += $e['total'];

// 4. Net Partner Profit
// Formula: Total Receipts - Expenses = Net Cash Profit
// (Note: Worker commission is paid separately, usually. If it comes from receipts, deduct it. 
// "Worker commission is NEVER reduced" implies it's a cost. 
// Standard Logic: Receipts - Expenses - Worker Payments? 
// Prompt said: "Expenses reduce partner net profit only". 
// Prompt said: "Receipts" replace "Gross Income".
// Let's assume Net Profit = Receipts - Expenses. (Worker commission is separate liability).
// Actually, if we use Receipts as Income, we must deduct ALL Cash Outflows to get Net Cash Profit.
// But usually, "Partner Share" is based on Accrual or Cash? 
// Prompt: "Partner Gross Income is only receipt from customer this replace whtih receipt."
// So: Profit = Receipts - Expenses.
$net_profit = $total_receipts - $total_expenses;

// 5. Closing Receivables (Outstanding)
$total_billed_all = $pdo->query("SELECT SUM(total_amount) FROM wax_invoices")->fetchColumn() ?: 0;
$total_paid_all = $pdo->query("SELECT SUM(amount) FROM wax_payments")->fetchColumn() ?: 0;
$closing_receivables = $total_billed_all - $total_paid_all;

// Partners
$partners = $pdo->query("SELECT name, share_percentage FROM wax_partners WHERE active = 1")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Partner Report</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 3px solid #333; padding-bottom: 10px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .box { border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #fff; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .box h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px; font-size: 1.1em; color: #555; }
        .row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px dotted #eee; font-size: 0.95em; }
        .row:last-child { border-bottom: none; }
        .total { font-weight: bold; font-size: 1.1em; color: #000; border-top: 2px solid #ddd; margin-top: 5px; padding-top: 5px; }
        .highlight { color: #28a745; }
        .danger { color: #dc3545; }
        .info { color: #17a2b8; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .box { break-inside: avoid; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Report</button>
    <a href="reports.php" style="margin-left: 20px;">Back</a>
</div>

<div class="header">
    <h1 style="margin: 0;">M.J Casting</h1>
    <p style="margin: 5px 0;">Partner Executive Summary</p>
    <small><?= date('d M Y', strtotime($start_date)) ?> &mdash; <?= date('d M Y', strtotime($end_date)) ?></small>
</div>

<div class="grid">
    <!-- Left Column -->
    <div>
        <!-- Income -->
        <div class="box">
            <h3>Income (Cash Flow)</h3>
            <div class="row">
                <span>Total Receipts</span>
                <span class="highlight total"><?= format_currency($total_receipts) ?></span>
            </div>
        </div>

        <!-- Expenses -->
        <div class="box">
            <h3>Expenses Breakdown</h3>
            <?php foreach ($expenses_by_cat as $ex): ?>
            <div class="row">
                <span><?= htmlspecialchars($ex['category']) ?></span>
                <span><?= format_currency($ex['total']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="row total danger">
                <span>Total Expenses</span>
                <span>- <?= format_currency($total_expenses) ?></span>
            </div>
        </div>

        <!-- Profit -->
        <div class="box" style="background: #e8f5e9; border-color: #c3e6cb;">
            <h3>Net Profit Share</h3>
            <div class="row">
                <span>Total Receipts</span>
                <span><?= format_currency($total_receipts) ?></span>
            </div>
            <div class="row">
                <span>Less: Expenses</span>
                <span class="danger">- <?= format_currency($total_expenses) ?></span>
            </div>
            <div class="row total highlight" style="font-size: 1.3em;">
                <span>NET PROFIT</span>
                <span><?= format_currency($net_profit) ?></span>
            </div>
            
            <div style="margin-top: 15px;">
                <?php foreach ($partners as $p): 
                    $share = $net_profit * ($p['share_percentage'] / 100);
                ?>
                <div class="row">
                    <strong><?= htmlspecialchars($p['name']) ?> (<?= $p['share_percentage'] ?>%)</strong>
                    <strong><?= format_currency($share) ?></strong>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Right Column -->
    <div>
        <!-- Worker Commissions -->
        <div class="box">
            <h3>Worker Income (Info Only)</h3>
            <?php foreach ($worker_commissions as $w): ?>
            <div class="row">
                <span><?= htmlspecialchars($w['name']) ?></span>
                <span><?= format_currency($w['commission']) ?></span>
            </div>
            <?php endforeach; ?>
            <div class="row total info">
                <span>Total Commissions</span>
                <span><?= format_currency($total_worker_income) ?></span>
            </div>
            <small style="color: #666; display: block; margin-top: 5px;">* Not deducted from Partner Profit</small>
        </div>

        <!-- Market Outstanding -->
        <div class="box" style="background: #fff3cd; border-color: #ffeeba;">
            <h3>Market Receivables</h3>
            <div class="row total" style="color: #856404;">
                <span>Total Outstanding</span>
                <span><?= format_currency($closing_receivables) ?></span>
            </div>
            <small>Total amount yet to be collected FROM wax_customers.</small>
        </div>
    </div>
</div>

</body>
</html>
