<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$customer_ids = $_GET['customer_ids'] ?? []; // Can be array if multi-select supported

$pdo = getDB();

// If no specific customers selected, maybe select all who have invoices in this period?
// For bulk bill, usually we want *all* active or specific ones.
// Let's assume we want to print bills for ALL customers who had activity.

$sql = "
    SELECT DISTINCT i.customer_id, c.name as customer_name, c.contact
    FROM wax_invoices i
    JOIN wax_customers c ON i.customer_id = c.id
    WHERE i.invoice_date BETWEEN ? AND ?
    ORDER BY c.name ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$start_date, $end_date]);
$customers_with_invoices = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <title>Bulk Bills: <?= $start_date ?> to <?= $end_date ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 20px; color: #000; }
        .bill-container { page-break-after: always; max-width: 800px; margin: 0 auto; padding-bottom: 20px; border-bottom: 1px dashed #ccc; }
        .header { text-align: center; margin-bottom: 20px; }
        .customer-info { margin-bottom: 10px; font-weight: bold; font-size: 1.2em; border-bottom: 2px solid #000; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 14px; }
        th, td { border: 1px solid #000; padding: 5px; text-align: center; }
        th { background: #f0f0f0; }
        .urdu { font-family: 'Noto Nastaliq Urdu', serif; direction: rtl; text-align: right; }
        .right { text-align: right; }
        .total-row td { font-weight: bold; border-top: 2px solid #000; }
        
        @media print {
            .no-print { display: none; }
            .bill-container { border-bottom: none; height: 100vh; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom: 20px; text-align: center;">
    <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer;">Print All Bills</button>
    <a href="reports.php" style="margin-left: 20px;">Back to Reports</a>
</div>

<?php foreach ($customers_with_invoices as $cust): 
    // 1. Calculate Opening Balance (Sum of invoices < start_date - payments < start_date)
    // For simplicity in this scope, let's assume we just want the invoices in the range. 
    // A full ledger requires complex running balance logic. 
    // "Opening balance (customer-wise)" was requested.
    
    // Invoices before start_date
    $stmtOpInv = $pdo->prepare("SELECT SUM(total_amount) FROM wax_invoices WHERE customer_id = ? AND invoice_date < ?");
    $stmtOpInv->execute([$cust['customer_id'], $start_date]);
    $op_inv = $stmtOpInv->fetchColumn() ?: 0;

    // Payments before start_date
    $stmtOpPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date < ?");
    $stmtOpPay->execute([$cust['customer_id'], $start_date]);
    $op_pay = $stmtOpPay->fetchColumn() ?: 0;

    $opening_balance = $op_inv - $op_pay;

    // 2. Fetch Invoices in Range
    $stmtInvs = $pdo->prepare("
        SELECT i.id, i.invoice_date, i.total_amount
        FROM wax_invoices i
        WHERE i.customer_id = ? AND i.invoice_date BETWEEN ? AND ?
        ORDER BY i.invoice_date ASC
    ");
    $stmtInvs->execute([$cust['customer_id'], $start_date, $end_date]);
    $invoices = $stmtInvs->fetchAll();

    // 3. Fetch Payments in Range
    $stmtPays = $pdo->prepare("
        SELECT p.payment_date, p.amount, p.method
        FROM wax_payments p
        WHERE p.customer_id = ? AND p.payment_date BETWEEN ? AND ?
        ORDER BY p.payment_date ASC
    ");
    $stmtPays->execute([$cust['customer_id'], $start_date, $end_date]);
    $payments = $stmtPays->fetchAll();

    $total_bill = 0;
    $total_paid = 0;
?>

<div class="bill-container">
    <div class="header">
        <h2>M.J Casting</h2>
        <div style="font-size: 0.9em;">Bill Statement: <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></div>
    </div>

    <div class="customer-info">
        <?= htmlspecialchars($cust['customer_name']) ?> <span style="font-size: 0.8em; font-weight: normal;">(<?= htmlspecialchars($cust['contact']) ?>)</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Debit (Inv)</th>
                <th>Credit (Rec)</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening -->
            <tr style="background: #f9f9f9;">
                <td colspan="2" class="right"><strong>Opening Balance</strong></td>
                <td></td>
                <td></td>
                <td class="right"><strong><?= format_currency($opening_balance) ?></strong></td>
            </tr>

            <?php 
            $running_balance = $opening_balance;
            
            // Merge and Sort Invoices & Payments by Date? 
            // Or just list Invoices then Payments? 
            // Chronological is better.
            $ledger = [];
            foreach ($invoices as $inv) {
                $ledger[] = ['type' => 'INV', 'date' => $inv['invoice_date'], 'amount' => $inv['total_amount'], 'ref' => $inv['id']];
            }
            foreach ($payments as $pay) {
                $ledger[] = ['type' => 'PAY', 'date' => $pay['payment_date'], 'amount' => $pay['amount'], 'ref' => $pay['method']];
            }
            
            // Sort by Date
            usort($ledger, function($a, $b) {
                return strtotime($a['date']) - strtotime($b['date']);
            });

            foreach ($ledger as $row):
                if ($row['type'] == 'INV') {
                    $running_balance += $row['amount'];
                    $total_bill += $row['amount'];
                } else {
                    $running_balance -= $row['amount'];
                    $total_paid += $row['amount'];
                }
            ?>
            <tr>
                <td><?= date('d-M', strtotime($row['date'])) ?></td>
                <td>
                    <?php if ($row['type'] == 'INV'): ?>
                        Invoice #<?= $row['ref'] ?>
                        <!-- Optional: List Items here? The prompt said "Item name (Urdu)" -->
                        <?php 
                            // Fetch items for this invoice
                            $stmtItems = $pdo->prepare("SELECT it.name_urdu, ii.qty, ii.rate FROM wax_invoice_items ii JOIN wax_items it ON ii.item_id = it.id WHERE ii.invoice_id = ?");
                            $stmtItems->execute([$row['ref']]);
                            $items = $stmtItems->fetchAll();
                            echo "<br><span style='font-size:0.85em; color: #555;'>";
                            foreach ($items as $it) {
                                echo "<span class='urdu'>{$it['name_urdu']}</span> ({$it['qty']} x {$it['rate']})<br>";
                            }
                            echo "</span>";
                        ?>
                    <?php else: ?>
                        Payment (<?= ucfirst($row['ref']) ?>)
                    <?php endif; ?>
                </td>
                <td class="right"><?= $row['type'] == 'INV' ? format_currency($row['amount']) : '-' ?></td>
                <td class="right"><?= $row['type'] == 'PAY' ? format_currency($row['amount']) : '-' ?></td>
                <td class="right"><?= format_currency($running_balance) ?></td>
            </tr>
            <?php endforeach; ?>
            
            <tr class="total-row">
                <td colspan="2" class="right">Totals</td>
                <td class="right"><?= format_currency($total_bill) ?></td>
                <td class="right"><?= format_currency($total_paid) ?></td>
                <td class="right" style="font-size: 1.2em; background: #eee;"><?= format_currency($running_balance) ?></td>
            </tr>
        </tbody>
    </table>
    
    <div style="margin-top: 10px; font-size: 0.9em; text-align: center;">
        Please pay <strong><?= format_currency($running_balance) ?></strong> at your earliest convenience.
    </div>
</div>

<?php endforeach; ?>

</body>
</html>
