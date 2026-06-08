<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$customer_id = intval($_GET['customer_id'] ?? 0);
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

$pdo = getDB();

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM wax_customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$customer) {
    die("Customer not found");
}

// Calculate Opening Balance
$stmtOpInv = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM wax_invoices WHERE customer_id = ? AND invoice_date < ?");
$stmtOpInv->execute([$customer_id, $start_date]);
$op_inv = floatval($stmtOpInv->fetchColumn());

$stmtOpPay = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM wax_payments WHERE customer_id = ? AND payment_date < ?");
$stmtOpPay->execute([$customer_id, $start_date]);
$op_pay = floatval($stmtOpPay->fetchColumn());

$opening_balance = floatval($customer['opening_balance'] ?? 0) + $op_inv - $op_pay;

// Fetch Transactions
$sql = "
    SELECT 
        'INV' as type,
        i.invoice_date as date,
        i.id as ref_id,
        it.name_urdu as description,
        ii.qty as qty,
        ii.rate as rate,
        ii.amount as debit,
        0 as credit
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_items it ON ii.item_id = it.id
    WHERE i.customer_id = ? AND i.invoice_date BETWEEN ? AND ?

    UNION ALL

    SELECT 
        'PAY' as type,
        p.payment_date as date,
        p.id as ref_id,
        CONCAT('Payment - ', UPPER(p.method)) as description,
        0 as qty,
        0 as rate,
        0 as debit,
        p.amount as credit
    FROM wax_payments p
    WHERE p.customer_id = ? AND p.payment_date BETWEEN ? AND ?

    ORDER BY date ASC, type ASC, ref_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id, $start_date, $end_date, $customer_id, $start_date, $end_date]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate totals
$balance = $opening_balance;
$total_debit = 0;
$total_credit = 0;

foreach ($transactions as $t) {
    $total_debit += floatval($t['debit']);
    $total_credit += floatval($t['credit']);
}
$closing_balance = $opening_balance + $total_debit - $total_credit;
?>
<!DOCTYPE html>
<html lang="ur">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statement: <?= htmlspecialchars($customer['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Top Action Bar */
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .action-bar-left {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-light {
            background: rgba(255,255,255,0.95);
            color: #4f46e5;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .btn-print {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-download {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
        }

        /* Main Statement Card */
        .statement-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
        }

        /* Header Section */
        .statement-header {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .statement-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
        }

        .company-info {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .company-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            box-shadow: 0 10px 30px rgba(99, 102, 241, 0.3);
        }

        .company-name {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .company-tagline {
            color: #94a3b8;
            font-size: 1rem;
        }

        .company-contact {
            margin-top: 15px;
            display: flex;
            justify-content: center;
            gap: 25px;
            color: #94a3b8;
            font-size: 0.9rem;
        }

        .company-contact span {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Info Cards Row */
        .info-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px 40px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-bottom: 1px solid #e2e8f0;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .info-card-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .info-card-value {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .info-card-value.customer-name {
            color: #4f46e5;
        }

        /* Summary Cards */
        .summary-section {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            padding: 30px 40px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }

        .summary-card {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            background: #f8fafc;
        }

        .summary-card.opening { border-left: 4px solid #6366f1; }
        .summary-card.debit { border-left: 4px solid #f59e0b; }
        .summary-card.credit { border-left: 4px solid #10b981; }
        .summary-card.balance { 
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white;
        }

        .summary-icon {
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.2rem;
        }

        .summary-card.opening .summary-icon { background: #e0e7ff; color: #4f46e5; }
        .summary-card.debit .summary-icon { background: #fef3c7; color: #d97706; }
        .summary-card.credit .summary-icon { background: #d1fae5; color: #059669; }
        .summary-card.balance .summary-icon { background: rgba(255,255,255,0.2); color: white; }

        .summary-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            opacity: 0.8;
        }

        .summary-value {
            font-size: 1.4rem;
            font-weight: 700;
            font-family: 'SF Mono', monospace;
        }

        .summary-card.balance .summary-label,
        .summary-card.balance .summary-value { color: white; }

        /* Transactions Table */
        .transactions-section {
            padding: 30px 40px;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #6366f1;
        }

        .transactions-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .transactions-table thead th {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
            padding: 15px 16px;
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .transactions-table tbody td {
            padding: 14px 16px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #374151;
        }

        .transactions-table tbody tr:hover {
            background: #f8fafc;
        }

        .transactions-table tbody tr.opening-row {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }

        .transactions-table tbody tr.opening-row td {
            font-weight: 600;
            color: #92400e;
        }

        .transactions-table tbody tr.closing-row {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
        }

        .transactions-table tbody tr.closing-row td {
            font-weight: 700;
            color: #4338ca;
        }

        /* Invoice Link Styling */
        .ref-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
            transition: all 0.2s;
        }

        .ref-link:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .ref-link.payment {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .ref-link.payment:hover {
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .ref-link i {
            font-size: 0.7rem;
        }

        /* Type Badge */
        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .type-badge.invoice {
            background: #e0e7ff;
            color: #4338ca;
        }

        .type-badge.payment {
            background: #d1fae5;
            color: #047857;
        }

        /* Description Cell */
        .description-cell {
            text-align: left !important;
        }

        .urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            direction: rtl;
            font-size: 1.05rem;
            line-height: 1.8;
        }

        /* Amount Cells */
        .debit-cell {
            color: #dc2626;
            font-weight: 600;
            font-family: 'SF Mono', monospace;
        }

        .credit-cell {
            color: #059669;
            font-weight: 600;
            font-family: 'SF Mono', monospace;
        }

        .balance-cell {
            font-weight: 700;
            font-family: 'SF Mono', monospace;
            color: #1e293b;
        }

        /* Footer */
        .statement-footer {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-message {
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .footer-note {
            font-size: 0.85rem;
            color: #64748b;
        }

        .footer-note i {
            color: #6366f1;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .action-bar {
                display: none !important;
            }

            .statement-card {
                box-shadow: none;
                border-radius: 0;
            }

            .statement-header {
                background: #1e293b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .summary-card.balance {
                background: #4f46e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .ref-link {
                background: #4f46e5 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .transactions-table thead th {
                background: #1e293b !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .transactions-table tbody tr.opening-row,
            .transactions-table tbody tr.closing-row {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 10px; }

            .statement-header { padding: 25px 20px; }
            .company-name { font-size: 1.6rem; }
            .company-contact { flex-direction: column; gap: 8px; }

            .info-row { padding: 20px; gap: 15px; }
            .summary-section { 
                grid-template-columns: repeat(2, 1fr); 
                padding: 20px;
            }

            .transactions-section { padding: 20px; }

            .transactions-table {
                display: block;
                overflow-x: auto;
            }

            .statement-footer { padding: 25px 20px; }
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Action Bar (Hidden on Print) -->
    <div class="action-bar">
        <div class="action-bar-left">
            <a href="reports.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Back to Reports
            </a>
            <a href="customer_view.php?id=<?= $customer_id ?>" class="btn btn-light" target="_blank">
                <i class="fas fa-user"></i> Customer Profile
            </a>
        </div>
        <div class="action-bar-right">
            <button onclick="window.print()" class="btn btn-print">
                <i class="fas fa-print"></i> Print Statement
            </button>
        </div>
    </div>

    <!-- Main Statement Card -->
    <div class="statement-card">
        <!-- Header -->


        <!-- Info Cards -->
        <div class="info-row">
            <div class="info-card">
                <div class="info-card-label">Customer Name</div>
                <div class="info-card-value customer-name"><?= htmlspecialchars($customer['name']) ?></div>
            </div>
            <div class="info-card">
                <div class="info-card-label">Statement Period</div>
                <div class="info-card-value"><?= date('d M Y', strtotime($start_date)) ?> — <?= date('d M Y', strtotime($end_date)) ?></div>
            </div>

            <div class="info-card">
                <div class="info-card-label">Total Transactions</div>
                <div class="info-card-value"><?= count($transactions) ?></div>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="summary-section">
            <div class="summary-card opening">
              
                <div class="summary-label">Opening Balance</div>
                <div class="summary-value">Rs.<?= number_format($opening_balance, 2) ?></div>
            </div>
            <div class="summary-card debit">
              
                <div class="summary-label">Total Billed</div>
                <div class="summary-value">Rs.<?= number_format($total_debit, 2) ?></div>
            </div>
            <div class="summary-card credit">
              
                <div class="summary-label">Total Paid</div>
                <div class="summary-value">Rs.<?= number_format($total_credit, 2) ?></div>
            </div>
            <div class="summary-card balance">
               
                <div class="summary-label">Net Payable</div>
                <div class="summary-value">Rs.<?= number_format($closing_balance, 2) ?></div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="transactions-section">
            <h3 class="section-title">
                <i class="fas fa-list-alt"></i> Transaction Details
            </h3>

            <table class="transactions-table">
                <thead>
                    <tr>
                        <th width="10%">Date</th>
                        <th width="8%">Type</th>
                        <th width="10%">Ref #</th>
                        <th width="28%">Description</th>
                        <th width="8%">Qty</th>
                        <th width="10%">Rate</th>
                        <th width="12%">Debit</th>
                        <th width="12%">Credit</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Opening Balance Row -->
                    <tr class="opening-row">
                        <td><?= date('d M', strtotime($start_date)) ?></td>
                        <td><span class="type-badge invoice"><i class="fas fa-arrow-right"></i> B/F</span></td>
                        <td>—</td>
                        <td class="description-cell"><strong>Opening Balance</strong></td>
                        <td>—</td>
                        <td>—</td>
                        <td class="debit-cell"><?= $opening_balance > 0 ? 'Rs.' . number_format($opening_balance, 2) : '—' ?></td>
                        <td class="credit-cell"><?= $opening_balance < 0 ? 'Rs.' . number_format(abs($opening_balance), 2) : '—' ?></td>
                    </tr>

                    <?php 
                    $running_balance = $opening_balance;
                    foreach ($transactions as $t): 
                        $running_balance += floatval($t['debit']) - floatval($t['credit']);
                    ?>
                    <tr>
                        <td><?= date('d M', strtotime($t['date'])) ?></td>
                        <td>
                            <?php if ($t['type'] === 'INV'): ?>
                                <span class="type-badge invoice"><i class="fas fa-file-invoice"></i> INV</span>
                            <?php else: ?>
                                <span class="type-badge payment"><i class="fas fa-check-circle"></i> PAY</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($t['type'] === 'INV'): ?>
                                <!-- Invoice Link - Opens in New Tab -->
                                <a href="invoice_edit.php?id=<?= $t['ref_id'] ?>" target="_blank" class="ref-link" title="View Invoice #<?= $t['ref_id'] ?>">
                                    <i class="fas fa-external-link-alt"></i> #<?= str_pad($t['ref_id'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            <?php else: ?>
                                <!-- Payment Link - Opens in New Tab -->
                                <a href="payment_view.php?id=<?= $t['ref_id'] ?>" target="_blank" class="ref-link payment" title="View Payment #<?= $t['ref_id'] ?>">
                                    <i class="fas fa-external-link-alt"></i> #<?= str_pad($t['ref_id'], 4, '0', STR_PAD_LEFT) ?>
                                </a>
                            <?php endif; ?>
                        </td>
                        <td class="description-cell <?= $t['type'] === 'INV' ? 'urdu' : '' ?>">
                            <?= htmlspecialchars($t['description']) ?>
                        </td>
                        <td>
    <?= floatval($t['qty']) > 0 
        ? number_format((float)$t['qty'], 3, '.', '') 
        : '—' ?>
</td>
                        <td><?= floatval($t['rate']) > 0 ? 'Rs.' . number_format($t['rate']) : '—' ?></td>
                        <td class="debit-cell"><?= floatval($t['debit']) > 0 ? 'Rs.' . number_format($t['debit'], 2) : '—' ?></td>
                        <td class="credit-cell"><?= floatval($t['credit']) > 0 ? 'Rs.' . number_format($t['credit'], 2) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <!-- Closing Balance Row -->
                    <tr class="closing-row">
                        <td><?= date('d M', strtotime($end_date)) ?></td>
                        <td><span class="type-badge invoice"><i class="fas fa-flag-checkered"></i> C/F</span></td>
                        <td>—</td>
                        <td class="description-cell"><strong>Closing Balance</strong></td>
                        <td>—</td>
                        <td>—</td>
                        <td class="balance-cell" colspan="2" style="text-align: center; font-size: 1.1rem;">
                            <?= $closing_balance >= 0 ? '' : '-' ?>Rs.<?= number_format(abs($closing_balance), 2) ?>
                            <?= $closing_balance >= 0 ? '<span style="color: #dc2626; font-size: 0.8rem;"> (Due)</span>' : '<span style="color: #059669; font-size: 0.8rem;"> (Advance)</span>' ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Footer -->
        <div class="statement-footer">
            <p class="footer-message">
                <i class="fas fa-heart" style="color: #ef4444;"></i> Thank you for your business!
            </p>
            <p class="footer-note">
                <i class="fas fa-info-circle"></i> This is a system-generated statement. For queries, contact us at 0300-1234567
            </p>
        </div>
    </div>
</div>

<script>
// Keyboard shortcut for print
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
});
</script>

</body>
</html>