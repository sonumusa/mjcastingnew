<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Invoices';
$db = getDB();

// Filters
$search = query('search', '');
$customerId = query('customer_id', '');
$invoiceType = query('invoice_type', '');
$fromDate = query('from_date', '');
$toDate = query('to_date', '');
$status = query('status', '');
$page = max(1, (int) query('page', 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

// Build query
$countSql = "SELECT COUNT(*) FROM invoices i WHERE 1=1";
$sql = "SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE 1=1";
$params = [];

if ($search) {
    $countSql .= " AND (i.invoice_no LIKE ? OR i.manual_book_no LIKE ? OR c.name LIKE ?)";
    $sql .= " AND (i.invoice_no LIKE ? OR i.manual_book_no LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($customerId) {
    $countSql .= " AND i.customer_id = ?";
    $sql .= " AND i.customer_id = ?";
    $params[] = (int)$customerId;
}
if ($invoiceType && in_array($invoiceType, ['customer','dukandar','karigar'])) {
    $countSql .= " AND i.invoice_type = ?";
    $sql .= " AND i.invoice_type = ?";
    $params[] = $invoiceType;
}
if ($fromDate) {
    $countSql .= " AND i.invoice_date >= ?";
    $sql .= " AND i.invoice_date >= ?";
    $params[] = $fromDate;
}
if ($toDate) {
    $countSql .= " AND i.invoice_date <= ?";
    $sql .= " AND i.invoice_date <= ?";
    $params[] = $toDate;
}
if ($status && in_array($status, ['active','cancelled'])) {
    $countSql .= " AND i.status = ?";
    $sql .= " AND i.status = ?";
    $params[] = $status;
} else {
    $sql .= " AND i.status = 'active'";
}

$total = $db->prepare($countSql);
$total->execute($params);
$totalCount = $total->fetchColumn();
$lastPage = max(1, ceil($totalCount / $perPage));
$page = min($page, $lastPage);

$sql .= " ORDER BY i.invoice_date DESC, i.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

// Get customers for filter
$customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Invoices</h1>
        <p class="font-urdu" style="margin-top:4px;">بل</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('invoices/create.php') ?>" class="btn btn-gold">
            <i class="bi bi-plus-circle"></i> New Invoice
        </a>
    </div>
</div>

<form method="GET" class="filter-bar">
    <div class="form-group" style="flex:1;">
        <input type="text" name="search" class="form-control" placeholder="Search invoice no, book no, party..." value="<?= htmlspecialchars($search) ?>">
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
        <select name="invoice_type" class="form-control" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="customer" <?= $invoiceType === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="dukandar" <?= $invoiceType === 'dukandar' ? 'selected' : '' ?>>Dukandar</option>
            <option value="karigar" <?= $invoiceType === 'karigar' ? 'selected' : '' ?>>Karigar</option>
        </select>
    </div>
    <div class="form-group">
        <input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>" placeholder="From">
    </div>
    <div class="form-group">
        <input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>" placeholder="To">
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
    <a href="<?= url('invoices/index.php') ?>" class="btn btn-outline"><i class="bi bi-x-circle"></i></a>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Book #</th>
                <th>Date</th>
                <th>Type</th>
                <th>Party</th>
                <th class="text-right">Casting (g)</th>
                <th class="text-right">Effective (g)</th>
                <th class="text-right">Wasooli</th>
                <th class="text-right">Balance (g)</th>
                <th>Status</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($invoices)): ?>
                <tr><td colspan="11" class="text-center text-muted">No invoices found</td></tr>
            <?php else: ?>
                <?php foreach ($invoices as $inv): ?>
                <tr>
                    <td><a href="<?= url('invoices/show.php?id=' . $inv['id']) ?>" style="color:var(--gold-primary);text-decoration:none;font-weight:600;"><?= htmlspecialchars($inv['invoice_no']) ?></a></td>
                    <td><?= htmlspecialchars($inv['manual_book_no'] ?? '-') ?></td>
                    <td><?= formatDate($inv['invoice_date']) ?></td>
                    <td><span class="badge bg-info"><?= ucfirst($inv['invoice_type']) ?></span></td>
                    <td><?= htmlspecialchars($inv['customer_name'] ?? 'N/A') ?></td>
                    <td class="text-right mono"><?= number_format($inv['casting_weight'], 3) ?></td>
                    <td class="text-right mono"><?= number_format($inv['effective_gold'], 3) ?></td>
                    <td class="text-right mono"><?= number_format($inv['wasooli'], 3) ?></td>
                    <td class="text-right mono <?= $inv['remaining_balance'] < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($inv['remaining_balance'], 3) ?></td>
                    <td><span class="badge <?= $inv['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($inv['status']) ?></span></td>
                    <td class="text-center" style="white-space:nowrap;">
                        <a href="<?= url('invoices/show.php?id=' . $inv['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('invoices/edit.php?id=' . $inv['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                        <a href="<?= url('invoices/print.php?id=' . $inv['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-printer"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
if ($lastPage > 1):
    $urlTemplate = url('invoices/index.php?' . http_build_query(array_merge($_GET, ['page' => '__PAGE__'])));
?>
<div class="pagination">
    <nav><ul class="pagination-list">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $page - 1, $urlTemplate) ?>">&laquo;</a></li>
        <?php for ($i = 1; $i <= $lastPage; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $i, $urlTemplate) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $page + 1, $urlTemplate) ?>">&raquo;</a></li>
    </ul></nav>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>