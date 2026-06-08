<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$page_title = "Dashboard";
include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <h2>Welcome, <?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></h2>
    <p>Quick Actions:</p>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="invoice_create.php" class="btn btn-primary">Create Invoice</a>
        <a href="bulk_invoice.php" class="btn btn-primary" style="background-color: #17a2b8;">Bulk Invoice</a>
        <a href="bulk_payment.php" class="btn btn-primary" style="background-color: #fd7e14;">Bulk Payment</a>
        <a href="payments.php" class="btn btn-primary" style="background-color: #fd7e14;">Payment List</a>
        <a href="expenses.php" class="btn btn-primary" style="background-color: #6c757d;">Add Expense</a>
        <a href="report_bill_book.php" class="btn btn-primary" style="background-color: #6f42c1;">Bill Book</a>
        <a href="reports.php" class="btn btn-primary" style="background-color: #28a745;">Reports</a>
    </div>
</div>

<div class="card">
    <h3>Recent Invoices</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $pdo = getDB();
            $stmt = $pdo->query("
                SELECT i.id, i.invoice_date, c.name as customer_name, i.total_amount 
                FROM wax_invoices i 
                JOIN wax_customers c ON i.customer_id = c.id 
                ORDER BY i.id DESC LIMIT 10
            ");
            while ($row = $stmt->fetch()):
            ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['invoice_date'] ?></td>
                <td><?= htmlspecialchars($row['customer_name']) ?></td>
                <td><?= format_currency($row['total_amount']) ?></td>
                <td>
                    <a href="invoice_edit.php?id=<?= $row['id'] ?>" class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem;">Edit</a>
                    <a href="invoice_print.php?id=<?= $row['id'] ?>" target="_blank" class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem; background: #6c757d;">PDF</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
