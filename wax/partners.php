<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Partners";
$pdo = getDB();

$partners = $pdo->query("SELECT * FROM wax_partners ORDER BY active DESC, name ASC")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <h2>Partners</h2>
        <button onclick="openModal()" class="btn btn-primary">+ New Partner</button>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Share %</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($partners as $p): ?>
            <tr>
                <td><?= $p['id'] ?></td>
                <td><?= htmlspecialchars($p['name']) ?></td>
                <td><?= $p['share_percentage'] ?>%</td>
                <td>
                    <span style="color: <?= $p['active'] ? 'green' : 'red' ?>;">
                        <?= $p['active'] ? 'Active' : 'Inactive' ?>
                    </span>
                </td>
                <td>
                    <button class="btn btn-primary" style="padding: 2px 5px; font-size: 0.8rem;" 
                            onclick='editPartner(<?= json_encode($p) ?>)'>Edit</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal -->
<div id="partnerModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center;">
    <div class="card" style="width: 400px; margin-top: 100px;">
        <h3 id="modalTitle">New Partner</h3>
        <form method="POST" action="partner_save.php">
            <input type="hidden" name="id" id="partnerId">
            <label>Name</label>
            <input type="text" name="name" id="partnerName" required>
            <label>Share Percentage</label>
            <input type="number" step="0.01" name="share_percentage" id="partnerShare" required>
            <label>Status</label>
            <select name="active" id="partnerActive">
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
    function openModal() {
        document.getElementById('partnerModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'New Partner';
        document.getElementById('partnerId').value = '';
        document.getElementById('partnerName').value = '';
        document.getElementById('partnerShare').value = '';
        document.getElementById('partnerActive').value = '1';
    }
    function editPartner(p) {
        document.getElementById('partnerModal').style.display = 'flex';
        document.getElementById('modalTitle').innerText = 'Edit Partner';
        document.getElementById('partnerId').value = p.id;
        document.getElementById('partnerName').value = p.name;
        document.getElementById('partnerShare').value = p.share_percentage;
        document.getElementById('partnerActive').value = p.active;
    }
    function closeModal() {
        document.getElementById('partnerModal').style.display = 'none';
    }
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
