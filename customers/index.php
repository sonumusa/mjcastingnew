<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions/ledger_functions.php';
requireAuth();

$pageTitle = 'Parties List';

$db = getDB();

// Filters
$search = query('search', '');
$partyType = query('party_type', '');
$page = max(1, (int) query('page', 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count
$countSql = "SELECT COUNT(*) FROM customers WHERE 1=1";
$params = [];
if ($search) {
    $countSql .= " AND (name LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($partyType && in_array($partyType, ['customer','dukandar','karigar'])) {
    $countSql .= " AND party_type = ?";
    $params[] = $partyType;
}
$total = $db->prepare($countSql);
$total->execute($params);
$totalCount = $total->fetchColumn();
$lastPage = max(1, ceil($totalCount / $perPage));
$page = min($page, $lastPage);

// Fetch
$sql = "SELECT * FROM customers WHERE 1=1";
$params2 = [];
if ($search) {
    $sql .= " AND (name LIKE ? OR phone LIKE ?)";
    $params2[] = "%$search%";
    $params2[] = "%$search%";
}
if ($partyType && in_array($partyType, ['customer','dukandar','karigar'])) {
    $sql .= " AND party_type = ?";
    $params2[] = $partyType;
}
$sql .= " ORDER BY name ASC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params2);
$customers = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Parties</h1>
        <p class="font-urdu" style="margin-top:4px;">گاہک / پارٹیاں</p>
    </div>
    <div class="page-actions">
        <a href="<?= url('customers/create.php') ?>" class="btn btn-gold">
            <i class="bi bi-plus-circle"></i> New Party
        </a>
    </div>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar">
    <div class="form-group" style="flex:1;">
        <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="form-group">
        <select name="party_type" class="form-control" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="customer" <?= $partyType === 'customer' ? 'selected' : '' ?>>Customer</option>
            <option value="dukandar" <?= $partyType === 'dukandar' ? 'selected' : '' ?>>Dukandar</option>
            <option value="karigar" <?= $partyType === 'karigar' ? 'selected' : '' ?>>Karigar</option>
        </select>
    </div>
    <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
    <a href="<?= url('customers/index.php') ?>" class="btn btn-outline"><i class="bi bi-x-circle"></i> Clear</a>
</form>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Phone</th>
                <th>City</th>
                <th>Type</th>
                <th>Status</th>
                <th class="text-right">Opening Balance (g)</th>
                <th class="text-right">Current Balance (g)</th>
                <th class="text-center">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($customers)): ?>
                <tr><td colspan="8" class="text-center text-muted">No parties found</td></tr>
            <?php else: ?>
                <?php foreach ($customers as $c): 
                    $balance = getCustomerCurrentBalance((int)$c['id']);
                ?>
                <tr>
                    <td><a href="<?= url('customers/show.php?id=' . $c['id']) ?>" style="color:var(--gold-primary);text-decoration:none;font-weight:600;"><?= htmlspecialchars($c['name']) ?></a></td>
                    <td><?= htmlspecialchars($c['phone'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($c['city'] ?? '-') ?></td>
                    <td><span class="badge bg-info"><?= ucfirst($c['party_type']) ?></span></td>
                    <td><span class="badge <?= $c['status'] === 'active' ? 'bg-success' : 'bg-danger' ?>"><?= ucfirst($c['status']) ?></span></td>
                    <td class="text-right mono"><?= number_format($c['opening_balance'], 3) ?></td>
                    <td class="text-right mono <?= $balance < 0 ? 'text-danger' : 'text-success' ?>"><?= number_format($balance, 3) ?></td>
                    <td class="text-center" style="white-space:nowrap;">
                        <a href="<?= url('customers/show.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-eye"></i></a>
                        <a href="<?= url('customers/edit.php?id=' . $c['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-pencil"></i></a>
                        <a href="<?= url('ledger/index.php?customer_id=' . $c['id']) ?>" class="btn btn-sm btn-outline"><i class="bi bi-journal"></i> Ledger</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// Pagination
if ($lastPage > 1):
    $urlTemplate = url('customers/index.php?' . http_build_query(array_merge($_GET, ['page' => '__PAGE__'])));
?>
<div class="pagination">
    <nav>
        <ul class="pagination-list">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= str_replace('__PAGE__', $page - 1, $urlTemplate) ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $lastPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= str_replace('__PAGE__', $i, $urlTemplate) ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $lastPage ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= str_replace('__PAGE__', $page + 1, $urlTemplate) ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>