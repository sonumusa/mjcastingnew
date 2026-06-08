<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Invoices";
$pdo = getDB();

$message = '';
$messageType = '';

// Handle Single Delete
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("DELETE FROM wax_invoice_items WHERE invoice_id = ?");
        $stmt->execute([$id]);
        $stmt = $pdo->prepare("DELETE FROM wax_invoices WHERE id = ?");
        $stmt->execute([$id]);
        $pdo->commit();
        $message = "Invoice #$id deleted successfully.";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error deleting invoice: " . $e->getMessage();
        $messageType = "error";
    }
}

// Handle Bulk Delete
if (isset($_POST['bulk_delete_ids'])) {
    $ids = $_POST['bulk_delete_ids'];
    if (!empty($ids)) {
        try {
            $pdo->beginTransaction();
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            
            $stmt = $pdo->prepare("DELETE FROM wax_invoice_items WHERE invoice_id IN ($placeholders)");
            $stmt->execute($ids);
            
            $stmt = $pdo->prepare("DELETE FROM wax_invoices WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            
            $pdo->commit();
            $message = count($ids) . " invoice(s) deleted successfully.";
            $messageType = "success";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error deleting invoices: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Filters
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $sql_search = is_numeric($search) ? intval($search) : 0;
    $where .= " AND (c.name LIKE ? OR i.id = ?)";
    $params[] = "%$search%";
    $params[] = $sql_search;
}
if ($dateFrom) {
    $where .= " AND i.invoice_date >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where .= " AND i.invoice_date <= ?";
    $params[] = $dateTo;
}

// Count & Stats
$stmtCount = $pdo->prepare("SELECT COUNT(*) as total_count, COALESCE(SUM(total_amount), 0) as total_amount FROM wax_invoices i JOIN wax_customers c ON i.customer_id = c.id $where");
$stmtCount->execute($params);
$countResult = $stmtCount->fetch(PDO::FETCH_ASSOC);
$total_rows = intval($countResult['total_count'] ?? 0);
$total_amount = floatval($countResult['total_amount'] ?? 0);
$total_pages = max(1, ceil($total_rows / $limit));

// Fetch Data
$sql = "
    SELECT i.*, c.name as customer_name 
    FROM wax_invoices i 
    JOIN wax_customers c ON i.customer_id = c.id 
    $where 
    ORDER BY i.id DESC 
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Today's stats
$todayStmt = $pdo->query("SELECT COUNT(*) as today_count, COALESCE(SUM(total_amount), 0) as today_amount FROM wax_invoices WHERE invoice_date = CURDATE()");
$todayStats = $todayStmt->fetch(PDO::FETCH_ASSOC);
$todayCount = intval($todayStats['today_count'] ?? 0);
$todayAmount = floatval($todayStats['today_amount'] ?? 0);

// This month stats
$monthStmt = $pdo->query("SELECT COUNT(*) as month_count, COALESCE(SUM(total_amount), 0) as month_amount FROM wax_invoices WHERE MONTH(invoice_date) = MONTH(CURDATE()) AND YEAR(invoice_date) = YEAR(CURDATE())");
$monthStats = $monthStmt->fetch(PDO::FETCH_ASSOC);
$monthCount = intval($monthStats['month_count'] ?? 0);
$monthAmount = floatval($monthStats['month_amount'] ?? 0);

include __DIR__ . '/templates/header.php';
?>

<style>
* { box-sizing: border-box; }

.invoices-page { max-width: 1400px; margin: 0 auto; padding: 15px; }

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-title { display: flex; align-items: center; gap: 12px; }
.page-title h1 { margin: 0; font-size: 1.5rem; color: #1e293b; }

.page-title .icon {
    width: 45px; height: 45px;
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.2rem;
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

.btn-primary { background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: white; }
.btn-primary:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3); }
.btn-secondary { background: #f1f5f9; color: #475569; }
.btn-secondary:hover { background: #e2e8f0; }
.btn-success { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-info { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; }
.btn-purple { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); color: white; }
.btn-purple:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3); }
.btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.btn-warning:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3); }
.btn-danger { background: #fee2e2; color: #dc2626; }
.btn-danger:hover { background: #fecaca; }
.btn-dark { background: #374151; color: white; }
.btn-dark:hover { background: #1f2937; }
.btn-sm { padding: 6px 12px; font-size: 0.8rem; }
.btn-xs { padding: 5px 10px; font-size: 0.75rem; }

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.stat-icon {
    width: 50px; height: 50px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem;
}

.stat-icon.purple { background: #ede9fe; color: #7c3aed; }
.stat-icon.blue { background: #dbeafe; color: #2563eb; }
.stat-icon.green { background: #d1fae5; color: #059669; }
.stat-icon.indigo { background: #e0e7ff; color: #4f46e5; }

.stat-info small { color: #64748b; font-size: 0.75rem; display: block; margin-bottom: 2px; }
.stat-info strong { font-size: 1.25rem; color: #1e293b; }

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

.filter-row { display: flex; gap: 12px; flex-wrap: wrap; align-items: flex-end; }
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
}

.filter-input:focus { outline: none; border-color: #6366f1; }
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
    background: #e0e7ff;
    color: #4f46e5;
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
    border-bottom: 2px solid #e2e8f0;
    white-space: nowrap;
}

tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.9rem;
    color: #374151;
}

tbody tr:hover { background: #f5f3ff; }
tbody tr.selected { background: #ede9fe; }

.checkbox-cell { width: 40px; text-align: center; }

.invoice-id {
    font-weight: 700;
    color: #4f46e5;
    font-family: 'SF Mono', monospace;
}

.customer-name { font-weight: 600; color: #1e293b; }

.amount-cell { 
    font-family: 'SF Mono', monospace; 
    font-weight: 600; 
    color: #059669;
    font-size: 0.95rem;
}

.date-cell { white-space: nowrap; color: #64748b; }

.action-btns { display: flex; gap: 6px; white-space: nowrap; flex-wrap: wrap; }

.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
.empty-state i { font-size: 3rem; margin-bottom: 15px; color: #c7d2fe; }

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
}

.page-link:hover { background: #f1f5f9; }
.page-link.active { background: #6366f1; color: white; border-color: #6366f1; }
.page-link.disabled { opacity: 0.5; pointer-events: none; }

.custom-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: #6366f1; }

/* Quick Actions Row */
.quick-actions {
    display: flex;
    gap: 8px;
    margin-top: 6px;
}

.quick-action-btn {
    padding: 3px 8px;
    font-size: 0.7rem;
    border-radius: 4px;
    background: #f1f5f9;
    color: #64748b;
    text-decoration: none;
    transition: all 0.15s;
}

.quick-action-btn:hover {
    background: #e0e7ff;
    color: #4f46e5;
}

/* Invoice Status Badge (if you have status) */
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
}

.status-badge.paid { background: #d1fae5; color: #065f46; }
.status-badge.pending { background: #fef3c7; color: #92400e; }
.status-badge.overdue { background: #fee2e2; color: #991b1b; }

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
        position: relative;
    }
    
    tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
    
    .action-btns { 
        justify-content: flex-end; 
        width: 100%;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        margin-top: 5px;
    }
}
</style>

<div class="invoices-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-file-invoice"></i></div>
            <h1>Invoices</h1>
        </div>
        <div class="header-actions">
            <a href="invoice_create.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Invoice
            </a>
            <a href="bulk_invoice.php" class="btn btn-purple">
                <i class="fas fa-layer-group"></i> Bulk Generator
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
            <div class="stat-icon purple"><i class="fas fa-file-invoice-dollar"></i></div>
            <div class="stat-info">
                <small>Today's Invoices</small>
                <strong><?= $todayCount ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-rupee-sign"></i></div>
            <div class="stat-info">
                <small>Today's Amount</small>
                <strong>Rs.<?= number_format($todayAmount, 2) ?></strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-info">
                <small>This Month</small>
                <strong><?= $monthCount ?> invoices</strong>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon indigo"><i class="fas fa-chart-line"></i></div>
            <div class="stat-info">
                <small>Month Total</small>
                <strong>Rs.<?= number_format($monthAmount, 2) ?></strong>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filter-card">
        <form method="GET">
            <div class="filter-row">
                <div class="filter-group" style="flex: 2;">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="search" class="filter-input" 
                           placeholder="Customer name or Invoice ID..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> From Date</label>
                    <input type="date" name="date_from" class="filter-input" value="<?= htmlspecialchars($dateFrom) ?>">
                </div>
                <div class="filter-group">
                    <label><i class="fas fa-calendar"></i> To Date</label>
                    <input type="date" name="date_to" class="filter-input" value="<?= htmlspecialchars($dateTo) ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                    <a href="invoices.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
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
                            <th>Invoice ID</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($invoices)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-file-invoice"></i>
                                        <p>No invoices found</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($invoices as $inv): ?>
                                <tr data-id="<?= $inv['id'] ?>">
                                    <td class="checkbox-cell">
                                        <input type="checkbox" name="bulk_delete_ids[]" value="<?= $inv['id'] ?>" 
                                               class="row-checkbox custom-checkbox" onchange="updateSelection()">
                                    </td>
                                    <td data-label="Invoice ID">
                                        <span class="invoice-id">#<?= str_pad($inv['id'], 5, '0', STR_PAD_LEFT) ?></span>
                                    </td>
                                    <td class="date-cell" data-label="Date">
                                        <i class="fas fa-calendar-alt" style="color: #a5b4fc; margin-right: 5px;"></i>
                                        <?= date('d M Y', strtotime($inv['invoice_date'])) ?>
                                    </td>
                                    <td data-label="Customer">
                                        <span class="customer-name"><?= htmlspecialchars($inv['customer_name']) ?></span>
                                    </td>
                                    <td class="amount-cell" data-label="Amount">
                                        Rs.<?= number_format(floatval($inv['total_amount']), 2) ?>
                                    </td>
                                    <td data-label="Actions">
                                        <div class="action-btns">
                                            <!-- EDIT BUTTON -->
                                            <a href="invoice_edit.php?id=<?= $inv['id'] ?>" class="btn btn-warning btn-xs" title="Edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <!-- PRINT BUTTON -->
                                            <a href="invoice_print.php?id=<?= $inv['id'] ?>" target="_blank" class="btn btn-dark btn-xs" title="Print">
                                                <i class="fas fa-print"></i> Print
                                            </a>
                                            <!-- DELETE BUTTON -->
                                            <button type="button" class="btn btn-danger btn-xs" onclick="deleteSingle(<?= $inv['id'] ?>)" title="Delete">
                                                <i class="fas fa-trash"></i> Delete
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
                        Showing <?= $offset + 1 ?> - <?= min($offset + $limit, $total_rows) ?> of <?= $total_rows ?> invoices
                    </div>
                    <div class="pagination-links">
                        <?php
                        $queryParams = http_build_query(array_filter([
                            'search' => $search,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo
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
    
    // Update select all checkbox state
    const total = document.querySelectorAll('.row-checkbox').length;
    const selectAll = document.getElementById('selectAll');
    selectAll.checked = checked === total && total > 0;
    selectAll.indeterminate = checked > 0 && checked < total;
}

function bulkDelete() {
    const count = document.querySelectorAll('.row-checkbox:checked').length;
    if (confirm(`Delete ${count} invoice(s)? This will also delete all associated items and cannot be undone.`)) {
        document.getElementById('bulkForm').submit();
    }
}

function deleteSingle(id) {
    if (confirm(`Delete Invoice #${String(id).padStart(5, '0')}? This will also delete all items and cannot be undone.`)) {
        document.getElementById('deleteIdInput').value = id;
        document.getElementById('deleteIdInput').name = 'delete_id';
        document.getElementById('bulkForm').submit();
    }
}

// Row click to toggle checkbox
document.querySelectorAll('tbody tr[data-id]').forEach(row => {
    row.addEventListener('click', function(e) {
        if (e.target.type === 'checkbox' || e.target.closest('.action-btns') || e.target.closest('a') || e.target.closest('button')) return;
        const cb = this.querySelector('.row-checkbox');
        if (cb) {
            cb.checked = !cb.checked;
            this.classList.toggle('selected', cb.checked);
            updateSelection();
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape to clear selection
    if (e.key === 'Escape') {
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = false;
            cb.closest('tr').classList.remove('selected');
        });
        document.getElementById('selectAll').checked = false;
        updateSelection();
    }
});

// Auto-hide alert
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 300);
    }, 5000);
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>