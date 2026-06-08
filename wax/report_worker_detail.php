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
$stmt = $pdo->prepare("SELECT name FROM wax_workers WHERE id = ?");
$stmt->execute([$worker_id]);
$worker = $stmt->fetch();

if (!$worker) die("Worker not found");

$page_title = "Commission Report: " . $worker['name'];

// Fetch Commission Details
$sql = "
    SELECT 
        i.invoice_date,
        i.id as invoice_id,
        c.name as customer_name,
        it.name_urdu,
        ii.qty,
        ii.rate,
        ii.amount,
        ii.worker_percentage,
        ii.commission_amount as commission
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it ON ii.item_id = it.id
    WHERE ii.worker_id = ? 
    AND i.invoice_date BETWEEN ? AND ?

    UNION ALL

    SELECT 
        i.invoice_date,
        i.id as invoice_id,
        c.name as customer_name,
        it_wax.name_urdu,
        ii.wax_qty as qty,
        ii.wax_rate as rate,
        (ii.wax_qty * ii.wax_rate) as amount,
        0 as worker_percentage,
        ii.wax_commission as commission
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it_wax ON ii.wax_item_id = it_wax.id
    WHERE ii.wax_worker_id = ? 
    AND i.invoice_date BETWEEN ? AND ?

    ORDER BY invoice_date ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$worker_id, $start_date, $end_date, $worker_id, $start_date, $end_date]);
$details = $stmt->fetchAll();

$total_commission = 0;

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Commission Report: <?= htmlspecialchars($worker['name']) ?></h2>
        <div>
            <strong><?= $start_date ?></strong> to <strong><?= $end_date ?></strong>
            <button onclick="window.print()" class="btn btn-primary" style="margin-left: 10px;">Print</button>
            <a href="reports.php" class="btn btn-danger">Back</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Inv #</th>
                <th>Customer</th>
                <th>Item</th>
                <th>Qty</th>
                <th>Rate</th>
                <th>Amount</th>
                <th>Comm %</th>
                <th>Commission</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($details as $d): 
                $total_commission += $d['commission'];
            ?>
            <tr>
                <td><?= $d['invoice_date'] ?></td>
                <td><?= $d['invoice_id'] ?></td>
                <td><?= htmlspecialchars($d['customer_name']) ?></td>
                <td style="font-family: 'Noto Nastaliq Urdu', serif; direction: rtl;"><?= htmlspecialchars($d['name_urdu']) ?></td>
                <td><?= $d['qty'] ?></td>
                <td><?= format_currency($d['rate']) ?></td>
                <td><?= format_currency($d['amount']) ?></td>
                <td><?= $d['worker_percentage'] ?>%</td>
                <td style="font-weight: bold;"><?= format_currency($d['commission']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background: #e9ecef; font-weight: bold;">
                <td colspan="8" style="text-align: right;">Total Payable:</td>
                <td><?= format_currency($total_commission) ?></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
