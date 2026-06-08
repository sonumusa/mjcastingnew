<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Payments";
$pdo = getDB();

$message = '';
$messageType = '';

// Handle Delete (Single)
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("DELETE FROM wax_payments WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    $message = "Payment deleted successfully.";
    $messageType = "success";
}

// Handle Bulk Delete
if (isset($_POST['bulk_delete_ids'])) {
    $ids = $_POST['bulk_delete_ids'];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("DELETE FROM wax_payments WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " payment(s) deleted successfully.";
        $messageType = "success";
    }
}

// Filters
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$method = $_GET['method'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (c.name LIKE ? OR p.notes LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($dateFrom) {
    $where .= " AND p.payment_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND p.payment_date <= ?";
    $params[] = $dateTo;
}
if ($method) {
    $where .= " AND p.method = ?";
    $params[] = $method;
}

// Count & Stats - FIXED: Using named columns
$stmtCount = $pdo->prepare("SELECT COUNT(*) as total_count, COALESCE(SUM(amount), 0) as total_amount FROM wax_payments p JOIN wax_customers c ON p.customer_id = c.id $where");
$stmtCount->execute($params);
$countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
$total_rows = intval($countResult['total_count'] ?? 0);
$total_amount = floatval($countResult['total_amount'] ?? 0);
$total_pages = max(1, ceil($total_rows / $limit));

// Fetch Data
$sql = "
    SELECT p.*, c.name as customer_name 
    FROM wax_payments p 
    JOIN wax_customers c ON p.customer_id = c.id 
    $where 
    ORDER BY p.payment_date DESC, p.id DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Today's stats - FIXED: Using named columns and COALESCE
$todayStmt = $pdo->query("SELECT COUNT(*) as today_count, COALESCE(SUM(amount), 0) as today_amount FROM wax_payments WHERE payment_date = CURDATE()");
$todayStats = $todayStmt->fetch(PDO::FETCH_ASSOC);
$todayCount = intval($todayStats['today_count'] ?? 0);
$todayAmount = floatval($todayStats['today_amount'] ?? 0);

include __DIR__ . '/templates/header.php';
?>

<style>
* { box-sizing: border-box; }

.payments-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 15px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #1e293b;
}

.page-title .icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.header-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    white-space: nowrap;
}

.btn-primary { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3); }
.btn-secondary { background: #f1f5f9; color: #475569; }
.btn-secondary:hover { background: #e2e8f0; }
.btn-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.btn-danger { background: #fee2e2; color: #dc2626; }
.btn-danger:hover { background: #fecaca; }
.btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.btn-xs { padding: 4px 8px; font-size: 0.75rem; }

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
}

.stat-icon.green { background: #d1fae5; color: #059669; }
.stat-icon.blue { background: #dbeafe; color: #2563eb; }
.stat-icon.yellow { background: #fef3c7; color: #d97706; }
.stat-icon.purple { background: #ede9fe; color: #7c3aed; }

.stat-info small { color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 2px; }
.stat-info strong { font-size: 1.2rem; color: #1e293b; }

.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

.filter-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 15px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.filter-row {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: flex-end;
}

.filter-group { flex: 1; min-width: 140px; }

.filter-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    margin-bottom: 4px;
    text-transform: uppercase;
}

.filter-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.9rem;
    transition: border-color 0.15s;
}

.filter-input:focus { outline: none; border-color: #10b981; }

.filter-actions { display: flex; gap: 8px; }

.table-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    overflow: hidden;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 10px;
}

.table-header-left { display: flex; align-items: center; gap: 12px; }

.selected-count {
    background: #dbeafe;
    color: #2563eb;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    display: none;
}

.selected-count.show { display: inline-block; }

.table-scroll { overflow-x: auto; }

table { width: 100%; border-collapse: collapse; }

thead th {
    background: #f8fafc;
    padding: 12px 16px;
    text-align: left;
    font-weight: 600;
    color: #64748b;
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
    color: #374151;
}

tbody tr:hover { background: #f8fafc; }
tbody tr.selected { background: #eff6ff; }

.checkbox-cell { width: 40px; text-align: center; }
.customer-name { font-weight: 600; color: #1e293b; }
.amount-cell { font-family: 'SF Mono', monospace; font-weight: 600; color: #059669; }

.method-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.method-badge.cash { background: #d1fae5; color: #065f46; }
.method-badge.bank { background: #dbeafe; color: #1e40af; }
.method-badge.upi { background: #ede9fe; color: #5b21b6; }
.method-badge.check { background: #fef3c7; color: #92400e; }

.date-cell { white-space: nowrap; color: #64748b; }

.notes-cell {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    color: #6b7280;
}

.action-btns { display: flex; gap: 6px; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state i { font-size: 3rem; margin-bottom: 15px; color: #cbd5e1; }

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 16px;
    background: #f8fafc;
    border-top: 1px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 10px;
}

.pagination-info { color: #64748b; font-size: 0.85rem; }
.pagination-links { display: flex; gap: 4px; }

.page-link {
    padding: 8px 14px;
    border-radius: 6px;
    background: white;
    color: #374151;
    text-decoration: none;
    font-size: 0.85rem;
    border: 1px solid #e2e8f0;
    transition: all 0.15s;
}

.page-link:hover { background: #f1f5f9; }
.page-link.active { background: #10b981; color: white; border-color: #10b981; }
.page-link.disabled { opacity: 0.5; pointer-events: none; }

.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #10b981; }

@media (max-width: 768px) {
    .page-header { flex-direction: column; align-items: flex-start; }
    .header-actions { width: 100%; }
    .header-actions .btn { flex: 1; justify-content: center; }
    .stats-row { grid-template-columns: 1fr 1fr; }
    .filter-group { min-width: 100%; }
    
    table thead { display: none; }
    
    tbody tr {
        display: block;
        padding: 15px;
        margin-bottom: 10px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    
    tbody td {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border: none;
    }
    
    tbody td::before {
        content: attr(data-label);
        font-weight: 600;
        color: #64748b;
        font-size: 0.75rem;
    }
    
    .checkbox-cell { display: none; }
}
</style>

<div class="payments-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
            <h1>Payments</h1>
        </div>
        <div class="header-actions">
            <a href="payment_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Payment
            </a>
            <a href="bulk_payment.php" class="btn btn-info">
                <i class="fas fa-layer-group"></i> Bulk Entry
            </a>
        </div>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <small>Today's Collection</small>
                <strong>Rs.<?= number_format($todayAmount, 2) ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-receipt"></i></div>
            <div class="stat-info">
                <small>Today's Entries</small>
                <strong><?= $todayCount ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-filter"></i></div>
            <div class="stat-info">
                <small>Filtered Results</small>
                <strong><?= number_format($total_rows) ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-calculator"></i></div>
            <div class="stat-info">
                <small>Filtered Total</small>
                <strong>Rs.<?= number_format($total_amount, 2) ?></strong>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET" id="filterForm">
            <div class="filter-row">
                <div class="filter-group" style="flex: 2;">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Customer or notes..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> From</label>
                    <input type="date" name="date_from" class="filter-input" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> To</label>
                    <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-credit-card"></i> Method</label>
                    <select name="method" class="filter-input">
                        <option value="">All</option>
                        <option value="cash" <?= $method === 'cash' ? 'selected' : '' ?>>Cash</option>
                        <option value="bank" <?= $method === 'bank' ? 'selected' : '' ?>>Bank</option>
                        <option value="upi" <?= $method === 'upi' ? 'selected' : '' ?>>UPI</option>
                        <option value="check" <?= $method === 'check' ? 'selected' : '' ?>>Check</option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                    <a href="payments.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </form>
    </div>

    <!-- Table -->
    <form method="POST" id="bulkForm">
        <div class="table-card">
            <div class="table-header">
                <div class="table-header-left">
                    <input type="checkbox" class="custom-checkbox" id="selectAll" onclick="toggleAll(this)">
                    <span class="selected-count" id="selectedCount">0 selected</span>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="bulkDelete()" id="bulkDeleteBtn" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>

            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell">#</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>No payments found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $p): ?>
                                <tr data-id="<?= $p['id'] ?>">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="bulk_delete_ids[]" value="<?= $p['id'] ?>" 
                                               class="row-checkbox custom-checkbox" onchange="updateSelection()">
                                    </td>
                                    <td class="date-cell" data-label="Date">
                                        <?= date('d M Y', strtotime($p['payment_date'])) ?>
                                    </td>
                                    <td data-label="Customer">
                                        <span class="customer-name"><?= htmlspecialchars($p['customer_name']) ?></span>
                                    </td>
                                    <td class="amount-cell" data-label="Amount">
                                        Rs.<?= number_format(floatval($p['amount']), 2) ?>
                                    </td>
                                    <td data-label="Method">
                                        <?php
                                        $methodClass = $p['method'] ?? 'cash';
                                        $icons = ['cash' => '💵', 'bank' => '🏦'];
                                        ?>
                                        <span class="method-badge <?= $methodClass ?>">
                                            <?= ($icons[$methodClass] ?? '') . ' ' . ucfirst($methodClass) ?>
                                        </span>
                                    </td>
                                    <td class="notes-cell" data-label="Notes" title="<?= htmlspecialchars($p['notes'] ?? '') ?>">
                                        <?= htmlspecialchars($p['notes'] ?? '') ?: '-' ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="action-btns">
                                            <a href="payment_edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-xs">
                                                <i class="fas fa-edit">Edit</i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteSingle(<?= $p['id'] ?>)">
                                                <i class="fas fa-trash">Delete</i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <div class="pagination-info">
                        Showing <?= $offset + 1 ?> - <?= min($offset + $limit, $total_rows) ?> of <?= $total_rows ?>
                    </div>
                    <div class="pagination-links">
                        <?php
                        $queryParams = http_build_query(array_filter([
                            'search' => $search,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'method' => $method
                        ]));
                        ?>
                        <a href="?page=<?= max(1, $page - 1) ?>&<?= $queryParams ?>" 
                           class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?>&<?= $queryParams ?>" 
                               class="page-link <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                        
                        <a href="?page=<?= min($total_pages, $page + 1) ?>&<?= $queryParams ?>" 
                           class="page-link <?= $page >= $total_pages ? 'disabled' : '' ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <input type="hidden" name="delete_id" id="deleteIdInput">
    </form>
</div>

<script>
function toggleAll(source) {
    document.querySelectorAll('.row-checkbox').forEach(cb => {
        cb.checked = source.checked;
        cb.closest('tr').classList.toggle('selected', source.checked);
    });
    updateSelection();
}

function updateSelection() {
    const checked = document.querySelectorAll('.row-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = checked + ' selected';
    document.getElementById('selectedCount').classList.toggle('show', checked > 0);
    document.getElementById('bulkDeleteBtn').style.display = checked > 0 ? 'inline-flex' : 'none';
}

function bulkDelete() {
    const count = document.querySelectorAll('.row-checkbox:checked').length;
    if (confirm(`Delete ${count} payment(s)?`)) {
        document.getElementById('bulkForm').submit();
    }
}

function deleteSingle(id) {
    if (confirm('Delete this payment?')) {
        document.getElementById('deleteIdInput').value = id;
        document.getElementById('deleteIdInput').name = 'delete_id';
        document.getElementById('bulkForm').submit();
    }
}

document.querySelectorAll('tbody tr[data-id]').forEach(row => {
    row.addEventListener('click', function(e) {
        if (e.target.type === 'checkbox' || e.target.closest('.action-btns')) return;
        const cb = this.querySelector('.row-checkbox');
        if (cb) {
            cb.checked = !cb.checked;
            this.classList.toggle('selected', cb.checked);
            updateSelection();
        }
    });
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>