<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Items & Rates";
$pdo = getDB();

// Handle Single Delete (Deactivate)
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("UPDATE wax_items SET active = 0 WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    $message = "Item deactivated successfully.";
}

// Handle Bulk Delete (Deactivate)
if (isset($_POST['bulk_delete_ids'])) {
    $ids = $_POST['bulk_delete_ids'];
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE wax_items SET active = 0 WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $message = count($ids) . " items deactivated successfully.";
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
    $where .= " AND (name_urdu LIKE ? OR name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM wax_items $where");
$stmtCount->execute($params);
$total_rows = $stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$sql = "SELECT * FROM wax_items $where ORDER BY active DESC, category ASC, name_urdu ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <h2>Items (Wax & Design)</h2>
        <div style="display: flex; gap: 5px;">
            <a href="export.php?type=items" class="btn btn-secondary" style="background: #28a745;">Export CSV</a>
            <a href="import.php?type=items" class="btn btn-secondary" style="background: #17a2b8;">Import CSV</a>
            <button onclick="openModal()" class="btn btn-primary">+ New Item</button>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;"><?= $message ?></div>
    <?php endif; ?>

    <form method="GET" style="margin-top: 15px; display: flex; gap: 10px;">
        <input type="text" name="search" placeholder="Search item name..." value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
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
                        <th>Name (English)</th>
                        <th>Name (Urdu)</th>
                        <th>Category</th>
                        <th>Default Rate</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $i): ?>
                    <tr>
                        <td><input type="checkbox" name="bulk_delete_ids[]" value="<?= $i['id'] ?>"></td>
                        <td><?= $i['id'] ?></td>
                        <td><?= htmlspecialchars($i['name'] ?? '') ?></td>
                        <td style="font-family: 'Noto Nastaliq Urdu', serif; font-size: 1.2em; direction: rtl;"><?= htmlspecialchars($i['name_urdu']) ?></td>
                        <td>
                            <?php 
                                $bg = '#d1ecf1'; // Default blue for design
                                if ($i['category'] == 'wax') $bg = '#fff3cd'; // Yellow
                                if ($i['category'] == 'mix') $bg = '#d4edda'; // Green
                            ?>
                            <span style="padding: 2px 5px; border-radius: 4px; background: <?= $bg ?>;">
                                <?= strtoupper($i['category']) ?>
                            </span>
                        </td>
                        <td><?= $i['default_rate'] > 0 ? format_currency($i['default_rate']) : '-' ?></td>
                        <td>
                            <span style="color: <?= $i['active'] ? 'green' : 'red' ?>;">
                                <?= $i['active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem;" 
                                    onclick='editItem(<?= json_encode($i) ?>)'>Edit</button>
                            
                            <button type="submit" name="delete_id" value="<?= $i['id'] ?>" class="btn btn-danger" 
                                    style="padding: 2px 5px; font-size: 0.8rem;" 
                                    onclick="return confirm('Deactivate this item?');">X</button>
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
<div id="itemModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index: 1000;">
    <div class="card" style="width: 400px; margin-top: 50px;">
        <h3 id="modalTitle">New Item</h3>
        <form method="POST" action="item_save.php">
            <input type="hidden" name="id" id="itemId">
            
            <label>Name (English)</label>
            <input type="text" name="name" id="itemNameEng" placeholder="English Name">

            <label>Name (Urdu)</label>
            <input type="text" name="name_urdu" id="itemName" required dir="rtl" placeholder="اردو نام">
            <label>Category</label>
            <select name="category" id="itemCategory">
                <option value="wax">Wax</option>
                <option value="design">Design</option>
                <option value="mix">Wax & Design (Mix)</option>
            </select>
            <label>Default Rate (Optional)</label>
            <input type="number" step="0.01" name="default_rate" id="itemRate">
            <label>Status</label>
            <select name="active" id="itemActive">
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
        if (confirm('Are you sure you want to deactivate selected items?')) {
            document.getElementById('bulkForm').submit();
        }
    }
    function openModal() {
        document.getElementById('itemModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'New Item';
        document.getElementById('itemId').value = '';
        document.getElementById('itemNameEng').value = '';
        document.getElementById('itemName').value = '';
        document.getElementById('itemCategory').value = 'wax';
        document.getElementById('itemRate').value = '';
        document.getElementById('itemActive').value = '1';
    }
    function editItem(i) {
        document.getElementById('itemModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'Edit Item';
        document.getElementById('itemId').value = i.id;
        document.getElementById('itemNameEng').value = i.name || '';
        document.getElementById('itemName').value = i.name_urdu;
        document.getElementById('itemCategory').value = i.category;
        document.getElementById('itemRate').value = i.default_rate;
        document.getElementById('itemActive').value = i.active;
    }
    function closeModal() {
        document.getElementById('itemModal').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
