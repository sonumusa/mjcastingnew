<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Gold Receipts';
$db = getDB();

$search = query('search', '');
$customerId = query('customer_id', '');
$receiptType = query('receipt_type', '');
$fromDate = query('from_date', '');
$toDate = query('to_date', '');
$page = max(1, (int) query('page', 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM gold_receipts r LEFT JOIN customers c ON c.id = r.customer_id WHERE r.deleted_at IS NULL";
$sql = "SELECT r.*, c.name as customer_name FROM gold_receipts r LEFT JOIN customers c ON c.id = r.customer_id WHERE r.deleted_at IS NULL";
$params = [];

if ($search) {
    $countSql .= " AND (r.receipt_no LIKE ? OR c.name LIKE ?)";
    $sql .= " AND (r.receipt_no LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($customerId) {
    $countSql .= " AND r.customer_id = ?";
    $sql .= " AND r.customer_id = ?";
    $params[] = (int)$customerId;
}
if ($receiptType && in_array($receiptType, ['customer','dukandar','karigar'])) {
    $countSql .= " AND r.receipt_type = ?";
    $sql .= " AND r.receipt_type = ?";
    $params[] = $receiptType;
}
if ($fromDate) {
    $countSql .= " AND r.receipt_date >= ?";
    $sql .= " AND r.receipt_date >= ?";
    $params[] = $fromDate;
}
if ($toDate) {
    $countSql .= " AND r.receipt_date <= ?";
    $sql .= " AND r.receipt_date <= ?";
    $params[] = $toDate;
}

$total = $db->prepare($countSql);
$total->execute($params);
$totalCount = $total->fetchColumn();
$lastPage = max(1, ceil($totalCount / $perPage));
$page = min($page, $lastPage);
$offset = ($page - 1) * $perPage;

$totalsSql = str_replace('SELECT r.*, c.name as customer_name', 'SELECT COALESCE(SUM(r.total_gross_weight),0) AS total_gross, COALESCE(SUM(r.total_khalis_weight),0) AS total_khalis', $sql);
$totalsStmt = $db->prepare($totalsSql);
$totalsStmt->execute($params);
$listTotals = $totalsStmt->fetch() ?: ['total_gross'=>0,'total_khalis'=>0];

$sql .= " ORDER BY r.receipt_date DESC, r.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$receipts = $stmt->fetchAll();

$customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Gold Receipts</h1>
        <p class="font-urdu" style="margin-top:4px;">سونا وصولی</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('gold-receipts/create.php') ?>" class="btn btn-gold">
            <i class="bi bi-plus-circle"></i> New Receipt
        </a>
    </div>
</div>

<form method="GET" class="filter-bar">
    <div class="form-group" style="flex:1;">
        <input type="text" name="search" class="form-control" placeholder="Search receipt no or party..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group">
        <select name="customer_id" class="form-control" onchange="this.form.submit()">
            <option value="">All Parties</option>
            <?php foreach ($customers as $c): ?>
                <option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <select name="receipt_type" class="form-control" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="customer" <?= $receiptType === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="dukandar" <?= $receiptType === 'dukandar' ? 'selected' : '' ?>>Dukandar</option>
            <option value="karigar" <?= $receiptType === 'karigar' ? 'selected' : '' ?>>Karigar</option>
        </select>
    </div>
    <div class="form-group">
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>">
    </div>
    <div class="form-group">
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
    <a href="<?= url('gold-receipts/index.php') ?>" class="btn btn-outline"><i class="bi bi-x-circle"></i></a>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Receipt #</th>
                <th>Date</th>
                <th>Type</th>
                <th>Party</th>
                <th class="text-right">Gross (g)</th>
                <th class="text-right">Khalis (g)</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($receipts)): ?>
                <tr><td colspan="7" class="text-center text-muted">No receipts found</td></tr>
            <?php else: ?>
                <?php foreach ($receipts as $r): ?>
                <tr>
                    <td><a href="<?= url('gold-receipts/show.php?id=' . $r['id']) ?>" style="color:var(--gold-primary);text-decoration:none;font-weight:600;"><?= htmlspecialchars($r['receipt_no']) ?></a></td>
                    <td><?= formatDate($r['receipt_date']) ?></td>
                    <td><span class="badge bg-info"><?= ucfirst($r['receipt_type']) ?></span></td>
                    <td><?= htmlspecialchars($r['customer_name'] ?? 'N/A') ?></td>
                    <td class="text-right mono"><?= number_format($r['total_gross_weight'], 3) ?></td>
                    <td class="text-right mono" style="color:var(--success);font-weight:600;"><?= number_format($r['total_khalis_weight'], 3) ?></td>
                    <td class="text-center" style="white-space:nowrap;">
                        <a href="<?= url('gold-receipts/show.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('gold-receipts/edit.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                        <a href="<?= url('gold-receipts/print.php?id=' . $r['id']) ?>" class="btn btn-sm btn-outline" title="Print"><i class="bi bi-printer"></i></a>
                        <a href="<?= url('gold-receipts/delete.php?id=' . $r['id']) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete receipt <?= htmlspecialchars($r['receipt_no']) ?>?')"><i class="bi bi-trash"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:700;background:var(--bg-surface);border-top:2px solid var(--gold-primary);">
                <td colspan="4">Filtered Total (<?= number_format((float)$totalCount) ?> records)</td>
                <td class="text-right mono"><?= number_format($listTotals['total_gross'], 3) ?></td>
                <td class="text-right mono" style="color:var(--success);"><?= number_format($listTotals['total_khalis'], 3) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>

<?php
if ($lastPage > 1):
    $urlTemplate = url('gold-receipts/index.php?' . http_build_query(array_merge($_GET, ['page' => '__PAGE__'])));
?>
<div class="pagination"><nav><ul class="pagination-list">
    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $page - 1, $urlTemplate) ?>">&laquo;</a></li>
    <?php for ($i = 1; $i <= $lastPage; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $i, $urlTemplate) ?>"><?= $i ?></a></li>
    <?php endfor; ?>
    <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $page + 1, $urlTemplate) ?>">&raquo;</a></li>
</ul></nav></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>