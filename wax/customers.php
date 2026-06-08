<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Customers";
$pdo = getDB();

// Handle Single Delete (Deactivate)
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("UPDATE wax_customers SET active = 0 WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    $message = "Customer deactivated successfully.";
}

// Handle Bulk Delete (Deactivate)
if (isset($_POST['bulk_delete_ids'])) {
    $ids = $_POST['bulk_delete_ids'];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE wax_customers SET active = 0 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " customers deactivated successfully.";
    }
}

// Search & Pagination
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND (name LIKE ? OR contact LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM wax_customers $where");
$stmtCount->execute($params);
$total_rows = $stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$sql = "SELECT * FROM wax_customers $where ORDER BY active DESC, name ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customers = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2>Customers</h2>
        <div style="display: flex; gap: 5px;">
            <a href="export.php?type=customers" class="btn btn-secondary" style="background: #28a745;">Export CSV</a>
            <a href="import.php?type=customers" class="btn btn-secondary" style="background: #17a2b8;">Import CSV</a>
            <button onclick="openModal()" class="btn btn-primary">+ New Customer</button>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;"><?= $message ?></div>
    <?php endif; ?>

    <form method="GET" style="margin-top: 15px; display: flex; gap: 10px;">
        <input type="text" name="search" placeholder="Search name or contact..." value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>
    
    <form method="POST" id="bulkForm">
        <div style="margin-top: 10px; margin-bottom: 5px;">
            <button type="button" class="btn btn-danger" onclick="submitBulkDelete()" style="padding: 5px 10px; font-size: 0.9rem;">Delete Selected</button>
        </div>
        <div style="overflow-x: auto;">
            <table style="margin-top: 5px;">
                <thead>
                    <tr>
                        <th style="width: 30px;"><input type="checkbox" onclick="toggleAll(this)"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Contact</th>
                        <th>Opening Bal.</th>
                        <th>Billing Style</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td><input type="checkbox" name="bulk_delete_ids[]" value="<?= $c['id'] ?>"></td>
                        <td><?= $c['id'] ?></td>
                        <td><?= htmlspecialchars($c['name']) ?></td>
                        <td><?= htmlspecialchars($c['contact']) ?></td>
                        <td><?= format_currency($c['opening_balance']) ?></td>
                        <td><?= ucfirst($c['billing_style']) ?></td>
                        <td>
                            <span style="color: <?= $c['active'] ? 'green' : 'red' ?>;">
                                <?= $c['active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem;" 
                                    onclick='editCustomer(<?= json_encode($c) ?>)'>Edit</button>
                            <a href="price_list.php?customer_id=<?= $c['id'] ?>" class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem; background: #17a2b8;">Rates</a>
                            
                            <button type="submit" name="delete_id" value="<?= $c['id'] ?>" class="btn btn-danger" 
                                    style="padding: 2px 5px; font-size: 0.8rem;" 
                                    onclick="return confirm('Deactivate this customer?');">X</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>

    <!-- Pagination -->
    <div style="margin-top: 15px; display: flex; justify-content: center; gap: 5px;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="btn btn-primary" style="<?= $i == $page ? 'background:#0056b3;' : 'background:#6c757d;' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal -->
<div id="customerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index: 1000;">
    <div class="card" style="width: 400px; margin-top: 100px;">
        <h3 id="modalTitle">New Customer</h3>
        <form method="POST" action="customer_save.php">
            <input type="hidden" name="id" id="custId">
            <label>Name</label>
            <input type="text" name="name" id="custName" required>
            <label>Contact</label>
            <input type="text" name="contact" id="custContact">
            <label>Opening Balance</label>
            <input type="number" step="0.01" name="opening_balance" id="custOpening">
            <label>Billing Style</label>
            <select name="billing_style" id="custBilling">
                <option value="cash">Cash</option>
                <option value="weekly">Weekly</option>
                <option value="monthly">Monthly</option>
            </select>
            <label>Status</label>
            <select name="active" id="custActive">
                <option value="1">Active</option>
                <option value="0">Inactive</option>
            </select>
            <div style="margin-top: 15px; text-align: right;">
                <button type="button" class="btn btn-danger" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleAll(source) {
        checkboxes = document.getElementsByName('bulk_delete_ids[]');
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }
    function submitBulkDelete() {
        if (confirm('Are you sure you want to deactivate selected customers?')) {
            document.getElementById('bulkForm').submit();
        }
    }
    function openModal() {
        document.getElementById('customerModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'New Customer';
        document.getElementById('custId').value = '';
        document.getElementById('custName').value = '';
        document.getElementById('custContact').value = '';
        document.getElementById('custOpening').value = '0.00';
        document.getElementById('custBilling').value = 'cash';
        document.getElementById('custActive').value = '1';
    }
    function editCustomer(c) {
        document.getElementById('customerModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'Edit Customer';
        document.getElementById('custId').value = c.id;
        document.getElementById('custName').value = c.name;
        document.getElementById('custContact').value = c.contact;
        document.getElementById('custOpening').value = c.opening_balance;
        document.getElementById('custBilling').value = c.billing_style;
        document.getElementById('custActive').value = c.active;
    }
    function closeModal() {
        document.getElementById('customerModal').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
