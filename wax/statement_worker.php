<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$worker_id = $_GET['worker_id'] ?? 0;
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$pdo = getDB();

// Fetch Worker
$stmt = $pdo->prepare("SELECT * FROM wax_workers WHERE id = ?");
$stmt->execute([$worker_id]);
$worker = $stmt->fetch();

if (!$worker) die("Worker not found");

// Opening Balance
$stmtOpComm = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN worker_id = ? THEN commission_amount ELSE 0 END), 0) +
        COALESCE(SUM(CASE WHEN wax_worker_id = ? THEN wax_commission ELSE 0 END), 0)
    FROM wax_invoice_items ii 
    JOIN wax_invoices i ON ii.invoice_id = i.id 
    WHERE (ii.worker_id = ? OR ii.wax_worker_id = ?) AND i.invoice_date < ?
");
$stmtOpComm->execute([$worker_id, $worker_id, $worker_id, $worker_id, $start_date]);
$op_comm = $stmtOpComm->fetchColumn() ?: 0;

$stmtOpPaid = $pdo->prepare("SELECT SUM(amount) FROM wax_worker_payments WHERE worker_id = ? AND payment_date < ?");
$stmtOpPaid->execute([$worker_id, $start_date]);
$op_paid = $stmtOpPaid->fetchColumn() ?: 0;

$opening_balance = $op_comm - $op_paid;

// Transactions
// We need to fetch rows where user is Design Worker AND rows where user is Wax Worker
$sql = "
    SELECT 
        'COMM' as type,
        i.invoice_date as date,
        c.name as customer_name,
        it.name_urdu as design,
        ii.qty as qty,
        ii.amount as full_amount,
        ii.commission_amount as debit,
        0 as credit
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it ON ii.item_id = it.id
    WHERE ii.worker_id = ? AND i.invoice_date BETWEEN ? AND ?

    UNION ALL

    SELECT 
        'COMM' as type,
        i.invoice_date as date,
        c.name as customer_name,
        it_wax.name_urdu as design,
        ii.wax_qty as qty,
        (ii.wax_qty * ii.wax_rate) as full_amount,
        ii.wax_commission as debit,
        0 as credit
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it_wax ON ii.wax_item_id = it_wax.id
    WHERE ii.wax_worker_id = ? AND i.invoice_date BETWEEN ? AND ?

    UNION ALL

    SELECT 
        'PAY' as type,
        payment_date as date,
        'Payment' as customer_name,
        notes as design,
        0 as qty,
        0 as full_amount,
        0 as debit,
        amount as credit
    FROM wax_worker_payments
    WHERE worker_id = ? AND payment_date BETWEEN ? AND ?

    ORDER BY date ASC, type ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    $worker_id, $start_date, $end_date, 
    $worker_id, $start_date, $end_date,
    $worker_id, $start_date, $end_date
]);
$transactions = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <title>Worker: <?= htmlspecialchars($worker['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Segoe UI', sans-serif; padding: 40px; color: #333; max-width: 900px; margin: 0 auto; background: #fff; }
        .header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 2.5em; letter-spacing: 1px; }
        
        .info-bar { display: flex; justify-content: space-between; margin-bottom: 30px; background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .info-group strong { display: block; font-size: 0.9em; color: #666; text-transform: uppercase; margin-bottom: 5px; }
        .info-group span { font-size: 1.2em; font-weight: bold; }

        table { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 30px; }
        th { background: #343a40; color: white; text-align: center; padding: 12px; font-weight: 500; }
        td { border-bottom: 1px solid #eee; padding: 10px; text-align: center; vertical-align: middle; }
        tr:nth-child(even) { background-color: #fcfcfc; }
        
        .urdu { font-family: 'Noto Nastaliq Urdu', serif; direction: rtl; font-size: 1.1em; }
        .right { text-align: right; }
        
        .summary-box { float: right; width: 300px; background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .summary-row.total { font-size: 1.4em; font-weight: bold; border-top: 2px solid #333; padding-top: 10px; margin-top: 10px; color: #28a745; }

        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            .info-bar, .summary-box { background: none; border: 1px solid #ddd; }
            th { background: #eee; color: #000; border-bottom: 1px solid #000; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print Statement</button>
    <a href="reports.php" style="margin-left: 10px; text-decoration: none; color: #007bff;">Back to Reports</a>
    <button onclick="document.getElementById('payModal').style.display='block'" style="float: right; background: #28a745; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px;">+ Record Payment</button>
</div>

<div class="header">
    <h1>M.J Casting</h1>
    <p>Worker Earning Statement</p>
</div>

<div class="info-bar">
    <div class="info-group">
        <strong>Worker</strong>
        <span><?= htmlspecialchars($worker['name']) ?></span>
    </div>
    <div class="info-group">
        <strong>Period</strong>
        <span><?= date('d M Y', strtotime($start_date)) ?> &mdash; <?= date('d M Y', strtotime($end_date)) ?></span>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th width="12%">Date</th>
            <th width="20%">Customer</th>
            <th width="20%">Design</th>
            <th width="8%">Qty</th>
            <th width="12%">Item Total</th>
            <th width="12%">Debit (Earned)</th>
            <th width="12%">Credit (Paid)</th>
        </tr>
    </thead>
    <tbody>
        <tr style="background: #fff3cd;">
            <td colspan="5" class="right"><strong>Opening Balance</strong></td>
            <td class="right"><strong><?= format_currency($opening_balance) ?></strong></td>
            <td></td>
        </tr>

        <?php
        $balance = $opening_balance;
        $total_earned = 0;
        $total_paid = 0;

        foreach ($transactions as $t):
            $balance += $t['debit'] - $t['credit'];
            $total_earned += $t['debit'];
            $total_paid += $t['credit'];
        ?>
        <tr>
            <td><?= date('d-M', strtotime($t['date'])) ?></td>
            <td><?= htmlspecialchars($t['customer_name']) ?></td>
            <td class="<?= $t['type'] == 'COMM' ? 'urdu' : '' ?>"><?= htmlspecialchars($t['design']) ?></td>
            <td><?= $t['qty'] > 0 ? $t['qty'] : '-' ?></td>
            <td><?= $t['full_amount'] > 0 ? number_format($t['full_amount']) : '-' ?></td>
            <td class="right"><?= $t['debit'] > 0 ? number_format($t['debit']) : '-' ?></td>
            <td class="right" style="color: green;"><?= $t['credit'] > 0 ? number_format($t['credit']) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="summary-box">
    <div class="summary-row">
        <span>Opening Balance:</span>
        <span><?= number_format($opening_balance) ?></span>
    </div>
    <div class="summary-row">
        <span>Total Earned:</span>
        <span>+ <?= number_format($total_earned) ?></span>
    </div>
    <div class="summary-row">
        <span>Total Paid:</span>
        <span style="color: red;">- <?= number_format($total_paid) ?></span>
    </div>
    <div class="summary-row total">
        <span>Net Payable:</span>
        <span><?= format_currency($balance) ?></span>
    </div>
</div>

<!-- Payment Modal -->
<div id="payModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 1000;">
    <div style="background:white; margin: 100px auto; padding: 20px; width: 300px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
        <h3>Record Worker Payment</h3>
        <form method="POST" action="worker_payment_save.php">
            <input type="hidden" name="worker_id" value="<?= $worker_id ?>">
            <label>Date</label><br>
            <input type="date" name="date" value="<?= date('Y-m-d') ?>" required style="width: 100%; padding: 8px; margin-bottom: 10px;"><br>
            <label>Amount</label><br>
            <input type="number" name="amount" required style="width: 100%; padding: 8px; margin-bottom: 10px;"><br>
            <label>Notes</label><br>
            <input type="text" name="notes" style="width: 100%; padding: 8px; margin-bottom: 10px;"><br>
            <div style="text-align: right;">
                <button type="button" onclick="document.getElementById('payModal').style.display='none'" style="background: #dc3545; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px;">Cancel</button>
                <button type="submit" style="background: #28a745; color: white; border: none; padding: 8px 16px; cursor: pointer; border-radius: 4px;">Save</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
