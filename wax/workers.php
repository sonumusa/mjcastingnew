<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Workers";
$pdo = getDB();

// Handle Delete
if (isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("UPDATE wax_workers SET active = 0 WHERE id = ?");
    $stmt->execute([$_POST['delete_id']]);
    $message = "Worker deactivated successfully.";
}

// Search & Pagination
$search = $_GET['search'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
if ($search) {
    $where .= " AND name LIKE ?";
    $params[] = "%$search%";
}

// Count
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM wax_workers $where");
$stmtCount->execute($params);
$total_rows = $stmtCount->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Fetch Data
$sql = "SELECT * FROM wax_workers $where ORDER BY active DESC, name ASC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workers = $stmt->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Workers</h2>
        <button onclick="openModal()" class="btn btn-primary">+ New Worker</button>
    </div>
    
    <?php if (isset($message)): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;"><?= $message ?></div>
    <?php endif; ?>

    <form method="GET" style="margin-top: 15px; display: flex; gap: 10px;">
        <input type="text" name="search" placeholder="Search worker name..." value="<?= htmlspecialchars($search) ?>" style="flex: 1;">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <table style="margin-top: 15px;">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Commission Type</th>
                <th>Rate / %</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($workers as $w): ?>
            <tr>
                <td><?= $w['id'] ?></td>
                <td><?= htmlspecialchars($w['name']) ?></td>
                <?php $workerCommType = $w['commission_type'] ?? 'percentage'; ?>
            <td><?= $workerCommType === 'percentage' ? 'Percentage' : 'Fixed Per Gram' ?></td>
                <td><?= $workerCommType === 'percentage' ? ($w['default_percentage'] ?? 0) . '%' : format_currency($w['commission_rate'] ?? 0) ?></td>
                <td>
                    <span style="color: <?= $w['active'] ? 'green' : 'red' ?>;">
                        <?= $w['active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem;" 
                            onclick='editWorker(<?= json_encode($w) ?>)'>Edit</button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Deactivate this worker?');">
                        <input type="hidden" name="delete_id" value="<?= $w['id'] ?>">
                        <button type="submit" class="btn btn-danger" style="padding: 2px 5px; font-size: 0.8rem;">X</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div style="margin-top: 15px; display: flex; justify-content: center; gap: 5px;">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="btn btn-primary" style="<?= $i == $page ? 'background:#0056b3;' : 'background:#6c757d;' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>

<!-- Modal -->
<div id="workerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div class="card" style="width: 400px; margin-top: 100px;">
        <h3 id="modalTitle">New Worker</h3>
        <form method="POST" action="worker_save.php">
            <input type="hidden" name="id" id="workerId">
            <label>Name</label>
            <input type="text" name="name" id="workerName" required>
            
            <label>Commission Type</label>
            <select name="commission_type" id="workerType" onchange="toggleRateInput()">
                <option value="percentage">Percentage Share (%)</option>
                <option value="fixed_per_unit">Fixed Amount (Per Gram)</option>
            </select>
            
            <div id="divPercent">
                <label>Default Share Percentage</label>
                <input type="number" step="0.01" name="default_percentage" id="workerPercent" value="80">
            </div>
            
            <div id="divFixed" style="display:none;">
                <label>Commission Rate (Per Gram)</label>
                <input type="number" step="0.01" name="commission_rate" id="workerRate" value="0">
            </div>

            <label>Status</label>
            <select name="active" id="workerActive">
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
    function toggleRateInput() {
        const type = document.getElementById('workerType').value;
        if (type === 'percentage') {
            document.getElementById('divPercent').style.display = 'block';
            document.getElementById('divFixed').style.display = 'none';
        } else {
            document.getElementById('divPercent').style.display = 'none';
            document.getElementById('divFixed').style.display = 'block';
        }
    }

    function openModal() {
        document.getElementById('workerModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'New Worker';
        document.getElementById('workerId').value = '';
        document.getElementById('workerName').value = '';
        document.getElementById('workerType').value = 'percentage';
        document.getElementById('workerPercent').value = '80';
        document.getElementById('workerRate').value = '0';
        document.getElementById('workerActive').value = '1';
        toggleRateInput();
    }
    function editWorker(w) {
        document.getElementById('workerModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'Edit Worker';
        document.getElementById('workerId').value = w.id;
        document.getElementById('workerName').value = w.name;
        document.getElementById('workerType').value = w.commission_type || 'percentage';
        document.getElementById('workerPercent').value = w.default_percentage;
        document.getElementById('workerRate').value = w.commission_rate || 0;
        document.getElementById('workerActive').value = w.active;
        toggleRateInput();
    }
    function closeModal() {
        document.getElementById('workerModal').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
