<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'Invoice Details';

$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT i.*, c.name as customer_name, c.phone as customer_phone, c.address as customer_address 
                      FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect('invoices/index.php');
}

// Get receive rows
$stmt = $db->prepare("SELECT * FROM invoice_receives WHERE invoice_id = ?");
$stmt->execute([$id]);
$receives = $stmt->fetchAll();

$breakdown = buildCalculationBreakdown($invoice);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1><?= htmlspecialchars($invoice['invoice_no']) ?></h1>
        <p class="font-urdu" style="margin-top:4px;">بل کی تفصیلات</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('invoices/edit.php?id=' . $id) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="<?= url('invoices/print1.php?id=' . $id) ?>" class="btn btn-success"><i class="bi bi-printer"></i> Print</a>
        <?php if ($invoice['status'] === 'active'): ?>
            <a href="<?= url('invoices/delete.php?id=' . $id) ?>" 
               class="btn btn-danger" 
               onclick="return confirmDelete('<?= htmlspecialchars($invoice['invoice_no']) ?>')">
                <i class="bi bi-trash"></i> Delete
            </a>
        <?php endif; ?>
        <a href="<?= url('invoices/index.php') ?>" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="invoice-detail-grid">
    <!-- Invoice Info -->
    <div class="detail-card">
        <h3><i class="bi bi-info-circle"></i> Invoice Information</h3>
        <div class="detail-row">
            <span class="detail-label">Invoice No</span>
            <span class="detail-value"><?= htmlspecialchars($invoice['invoice_no']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Date</span>
            <span class="detail-value"><?= formatDate($invoice['invoice_date']) ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Type</span>
            <span class="detail-value"><span class="badge bg-info"><?= ucfirst($invoice['invoice_type']) ?></span></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Book No</span>
            <span class="detail-value"><?= htmlspecialchars($invoice['manual_book_no'] ?? '-') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Status</span>
            <span class="detail-value"><span class="badge <?= $invoice['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($invoice['status']) ?></span></span>
        </div>
        <?php if ($invoice['remarks']): ?>
        <div class="detail-row">
            <span class="detail-label">Remarks</span>
            <span class="detail-value"><?= htmlspecialchars($invoice['remarks']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- Party Info -->
    <div class="detail-card">
        <h3><i class="bi bi-person"></i> Party</h3>
        <div class="detail-row">
            <span class="detail-label">Name</span>
            <span class="detail-value"><a href="<?= url('customers/show.php?id=' . $invoice['customer_id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($invoice['customer_name'] ?? 'N/A') ?></a></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Phone</span>
            <span class="detail-value"><?= htmlspecialchars($invoice['customer_phone'] ?? '-') ?></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">Address</span>
            <span class="detail-value"><?= htmlspecialchars($invoice['customer_address'] ?? '-') ?></span>
        </div>
    </div>

    <!-- Gold Calculation -->
    <div class="detail-card">
        <h3><i class="bi bi-calculator"></i> Gold Calculation</h3>
        <?php foreach ($breakdown['steps'] as $step): ?>
        <div class="detail-row">
            <span class="detail-label"><?= htmlspecialchars($step['label']) ?></span>
            <span class="detail-value"><?= number_format($step['value'], 3) ?> <?= $step['unit'] ?></span>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Balance Chain -->
    <div class="detail-card">
        <h3><i class="bi bi-wallet2"></i> Balance</h3>
        <div class="detail-row">
            <span class="detail-label">Previous Balance</span>
            <span class="detail-value"><?= number_format($breakdown['balance_chain']['previous_balance'], 3) ?> g</span>
        </div>
        <div class="detail-row" style="font-weight:600;color:var(--gold-primary);">
            <span class="detail-label">+ Effective Gold</span>
            <span class="detail-value">+ <?= number_format($breakdown['balance_chain']['effective_gold'], 3) ?> g</span>
        </div>
        <div class="detail-row" style="color:var(--success);">
            <span class="detail-label">- Wasooli</span>
            <span class="detail-value">- <?= number_format($breakdown['balance_chain']['wasooli'], 3) ?> g</span>
        </div>
        <div class="detail-row" style="color:var(--info);">
            <span class="detail-label">- Received Khalis</span>
            <span class="detail-value">- <?= number_format($breakdown['balance_chain']['received_khalis'], 3) ?> g</span>
        </div>
        <div class="detail-row" style="font-size:1.1rem;padding-top:12px;border-top:2px solid var(--gold-primary);">
            <span class="detail-label" style="font-weight:700;">Remaining Balance</span>
            <span class="detail-value" style="font-weight:700;color:<?= $invoice['remaining_balance'] < 0 ? 'var(--error)' : 'var(--gold-primary)' ?>;">
                <?= number_format($invoice['remaining_balance'], 3) ?> g
            </span>
        </div>
    </div>
</div>

<!-- Gold Received Rows -->
<?php if (!empty($receives)): ?>
<div class="card" style="padding:24px;margin-top:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">
        <i class="bi bi-box-arrow-in-down"></i> Gold Received
    </h3>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Gross Weight (g)</th>
                    <th class="text-right">Ratti Impurity</th>
                    <th class="text-right">Khalis Weight (g)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($receives as $rec): ?>
                <tr>
                    <td><?= htmlspecialchars($rec['description'] ?? '-') ?></td>
                    <td class="text-right mono"><?= number_format($rec['gross_weight'], 3) ?></td>
                    <td class="text-right mono"><?= number_format($rec['ratti_impurity'], 3) ?></td>
                    <td class="text-right mono" style="color:var(--success);font-weight:600;"><?= number_format($rec['khalis_weight'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:700;background:var(--bg-surface);">
                    <td>Total</td>
                    <td class="text-right mono"><?= number_format(array_sum(array_column($receives, 'gross_weight')), 3) ?></td>
                    <td></td>
                    <td class="text-right mono" style="color:var(--success);"><?= number_format(array_sum(array_column($receives, 'khalis_weight')), 3) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<script>
function confirmDelete(invoiceNo) {
    return confirm('Are you sure you want to delete/cancel invoice ' + invoiceNo + '?\n\nThis action will:\n• Mark the invoice as cancelled\n• Recalculate customer balance\n\nThis cannot be undone easily.');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>