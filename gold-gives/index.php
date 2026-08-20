<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Gold Gives / Diya';
$db = getDB();

$search = query('search', '');
$customerId = query('customer_id', '');
$giveType = query('give_type', '');
$fromDate = query('from_date', '');
$toDate = query('to_date', '');
$page = max(1, (int) query('page', 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM gold_gives g LEFT JOIN customers c ON c.id = g.customer_id WHERE g.deleted_at IS NULL";
$sql = "SELECT g.*, c.name as customer_name FROM gold_gives g LEFT JOIN customers c ON c.id = g.customer_id WHERE g.deleted_at IS NULL";
$params = [];

if ($search) {
    $countSql .= " AND (g.give_no LIKE ? OR c.name LIKE ?)";
    $sql .= " AND (g.give_no LIKE ? OR c.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($customerId) {
    $countSql .= " AND g.customer_id = ?";
    $sql .= " AND g.customer_id = ?";
    $params[] = (int)$customerId;
}
if ($giveType && in_array($giveType, ['customer','dukandar','karigar'])) {
    $countSql .= " AND g.give_type = ?";
    $sql .= " AND g.give_type = ?";
    $params[] = $giveType;
}
if ($fromDate) { $countSql .= " AND g.give_date >= ?"; $sql .= " AND g.give_date >= ?"; $params[] = $fromDate; }
if ($toDate) { $countSql .= " AND g.give_date <= ?"; $sql .= " AND g.give_date <= ?"; $params[] = $toDate; }

$total = $db->prepare($countSql);
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$lastPage = max(1, (int)ceil($totalCount / $perPage));
$page = min($page, $lastPage);
$offset = ($page - 1) * $perPage;

// Filtered totals (before pagination)
$totalsSql = str_replace(
    'SELECT g.*, c.name as customer_name',
    'SELECT COALESCE(SUM(g.total_gross_weight),0) AS total_gross, COALESCE(SUM(g.total_khalis_weight),0) AS total_khalis',
    $sql
);
$totalsStmt = $db->prepare($totalsSql);
$totalsStmt->execute($params);
$listTotals = $totalsStmt->fetch() ?: ['total_gross' => 0, 'total_khalis' => 0];

$sql .= " ORDER BY g.give_date DESC, g.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$gives = $stmt->fetchAll();

$customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Gold Gives / Diya</h1>
        <p class="font-urdu" style="margin-top:4px;">سونا دیا</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('gold-gives/create.php') ?>" class="btn btn-gold"><i class="bi bi-plus-circle"></i> New Gold Give</a>
    </div>
</div>

<form method="GET" class="filter-bar">
    <div class="form-group" style="flex:1;"><input type="text" name="search" class="form-control" placeholder="Search give no or party..." value="<?= htmlspecialchars($search) ?>"></div>
    <div class="form-group"><select name="customer_id" class="form-control" onchange="this.form.submit()"><option value="">All Parties</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $customerId == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><select name="give_type" class="form-control" onchange="this.form.submit()"><option value="">All Types</option><option value="customer" <?= $giveType==='customer'?'selected':'' ?>>Customer</option><option value="dukandar" <?= $giveType==='dukandar'?'selected':'' ?>>Dukandar</option><option value="karigar" <?= $giveType==='karigar'?'selected':'' ?>>Karigar</option></select></div>
    <div class="form-group"><input type="date" name="from_date" class="form-control" value="<?= htmlspecialchars($fromDate) ?>"></div>
    <div class="form-group"><input type="date" name="to_date" class="form-control" value="<?= htmlspecialchars($toDate) ?>"></div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
    <a href="<?= url('gold-gives/index.php') ?>" class="btn btn-outline"><i class="bi bi-x-circle"></i></a>
</form>

<div class="table-container"><table>
    <thead><tr><th>Give #</th><th>Date</th><th>Type</th><th>Party</th><th class="text-right">Gross (g)</th><th class="text-right">Khalis Given (g)</th><th class="text-center">Actions</th></tr></thead>
    <tbody>
    <?php if (empty($gives)): ?><tr><td colspan="7" class="text-center text-muted">No gold gives found</td></tr><?php else: foreach ($gives as $g): ?>
        <tr>
            <td><a href="<?= url('gold-gives/show.php?id=' . $g['id']) ?>" style="color:var(--gold-primary);text-decoration:none;font-weight:600;"><?= htmlspecialchars($g['give_no']) ?></a></td>
            <td><?= formatDate($g['give_date']) ?></td>
            <td><span class="badge bg-info"><?= ucfirst($g['give_type']) ?></span></td>
            <td><?= htmlspecialchars($g['customer_name'] ?? 'N/A') ?></td>
            <td class="text-right mono"><?= number_format($g['total_gross_weight'], 3) ?></td>
            <td class="text-right mono text-danger" style="font-weight:600;"><?= number_format($g['total_khalis_weight'], 3) ?></td>
            <td class="text-center" style="white-space:nowrap;"><a href="<?= url('gold-gives/show.php?id=' . $g['id']) ?>" class="btn btn-sm btn-outline" title="View"><i class="bi bi-eye"></i></a> <a href="<?= url('gold-gives/edit.php?id=' . $g['id']) ?>" class="btn btn-sm btn-outline" title="Edit"><i class="bi bi-pencil"></i></a> <a href="<?= url('gold-gives/print.php?id=' . $g['id']) ?>" class="btn btn-sm btn-outline" title="Print"><i class="bi bi-printer"></i></a> <a href="<?= url('gold-gives/delete.php?id=' . $g['id']) ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete gold give <?= htmlspecialchars($g['give_no']) ?>?')"><i class="bi bi-trash"></i></a></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <tfoot><tr style="font-weight:700;background:var(--bg-surface);border-top:2px solid var(--gold-primary);"><td colspan="4">Filtered Total (<?= number_format((float)$totalCount) ?> records)</td><td class="text-right mono"><?= number_format($listTotals['total_gross'], 3) ?></td><td class="text-right mono text-danger"><?= number_format($listTotals['total_khalis'], 3) ?></td><td></td></tr></tfoot>
</table></div>

<?php if ($lastPage > 1): $urlTemplate = url('gold-gives/index.php?' . http_build_query(array_merge($_GET, ['page' => '__PAGE__']))); ?>
<div class="pagination"><nav><ul class="pagination-list">
<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $page - 1, $urlTemplate) ?>">&laquo;</a></li>
<?php for ($i=1; $i<=$lastPage; $i++): ?><li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $i, $urlTemplate) ?>"><?= $i ?></a></li><?php endfor; ?>
<li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>"><a class="page-link" href="<?= str_replace('__PAGE__', $page + 1, $urlTemplate) ?>">&raquo;</a></li>
</ul></nav></div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
