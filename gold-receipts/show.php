<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Gold Receipt Details';
$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT r.*, c.name as customer_name, c.phone as customer_phone FROM gold_receipts r LEFT JOIN customers c ON c.id = r.customer_id WHERE r.id = ?");
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    setFlash('error', 'Receipt not found.');
    redirect('gold-receipts/index.php');
}

$stmt = $db->prepare("SELECT * FROM gold_receipt_items WHERE receipt_id = ?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1><?= htmlspecialchars($receipt['receipt_no']) ?></h1>
        <p class="font-urdu" style="margin-top:4px;">وصولی کی تفصیلات</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('gold-receipts/edit.php?id=' . $id) ?>" class="btn btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <a href="<?= url('gold-receipts/print.php?id=' . $id) ?>" class="btn btn-success"><i class="bi bi-printer"></i> Print</a>
        <a href="<?= url('gold-receipts/delete.php?id=' . $id) ?>" class="btn btn-danger" onclick="return confirm('Delete this receipt?')"><i class="bi bi-trash"></i> Delete</a>
        <a href="<?= url('gold-receipts/index.php') ?>" class="btn btn-outline"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="invoice-detail-grid">
    <div class="detail-card">
        <h3>Receipt Information</h3>
        <div class="detail-row"><span class="detail-label">Receipt No</span><span class="detail-value"><?= htmlspecialchars($receipt['receipt_no']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Date</span><span class="detail-value"><?= formatDate($receipt['receipt_date']) ?></span></div>
        <div class="detail-row"><span class="detail-label">Type</span><span class="detail-value"><span class="badge bg-info"><?= ucfirst($receipt['receipt_type']) ?></span></span></div>
        <?php if ($receipt['remarks']): ?>
        <div class="detail-row"><span class="detail-label">Remarks</span><span class="detail-value"><?= htmlspecialchars($receipt['remarks']) ?></span></div>
        <?php endif; ?>
    </div>
    <div class="detail-card">
        <h3>Party</h3>
        <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value"><a href="<?= url('customers/show.php?id=' . $receipt['customer_id']) ?>" style="color:var(--gold-primary);"><?= htmlspecialchars($receipt['customer_name'] ?? 'N/A') ?></a></span></div>
        <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value"><?= htmlspecialchars($receipt['customer_phone'] ?? '-') ?></span></div>
    </div>
</div>

<div class="card" style="padding:24px;margin-top:24px;">
    <h3 style="font-family:'Playfair Display',serif;margin-bottom:16px;color:var(--gold-primary);">Items Received</h3>
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
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['description'] ?? '-') ?></td>
                    <td class="text-right mono"><?= number_format($item['gross_weight'], 3) ?></td>
                    <td class="text-right mono"><?= number_format($item['ratti_impurity'], 3) ?></td>
                    <td class="text-right mono" style="color:var(--success);font-weight:600;"><?= number_format($item['khalis_weight'], 3) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight:700;background:var(--bg-surface);">
                    <td>Total</td>
                    <td class="text-right mono"><?= number_format($receipt['total_gross_weight'], 3) ?></td>
                    <td></td>
                    <td class="text-right mono" style="color:var(--success);"><?= number_format($receipt['total_khalis_weight'], 3) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>