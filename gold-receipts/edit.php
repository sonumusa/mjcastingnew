<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'Edit Gold Receipt';
$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM gold_receipts WHERE id = ?");
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) { setFlash('error', 'Receipt not found.'); redirect('gold-receipts/index.php'); }

$stmt = $db->prepare("SELECT * FROM gold_receipt_items WHERE receipt_id = ?");
$stmt->execute([$id]);
$existingItems = $stmt->fetchAll();

$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $customerId = (int) post('customer_id');
    $receiptType = post('receipt_type', 'customer');
    $receiptDate = post('receipt_date', date('Y-m-d'));
    $remarks = post('remarks');
    $items = post('items', []);
    
    try {
        $db->beginTransaction();
        
        $db->prepare("UPDATE gold_receipts SET customer_id=?, receipt_type=?, receipt_date=?, remarks=?, updated_by=? WHERE id=?")->execute([$customerId, $receiptType, $receiptDate, $remarks, $_SESSION['user_id'], $id]);
        
        $db->prepare("DELETE FROM gold_receipt_items WHERE receipt_id = ?")->execute([$id]);
        
        $totalGross = 0; $totalKhalis = 0;
        foreach ($items as $item) {
            $gross = parseDecimal($item['gross_weight'] ?? 0);
            $ratti = parseDecimal($item['ratti_impurity'] ?? 0);
            if ($gross <= 0) continue;
            $khalis = convertToKhalis($gross, $ratti);
            $db->prepare("INSERT INTO gold_receipt_items (receipt_id, description, gross_weight, ratti_impurity, khalis_weight) VALUES (?, ?, ?, ?, ?)")->execute([$id, $item['description'] ?? '', $gross, $ratti, $khalis]);
            $totalGross += $gross; $totalKhalis += $khalis;
        }
        $db->prepare("UPDATE gold_receipts SET total_gross_weight=?, total_khalis_weight=? WHERE id=?")->execute([round($totalGross,3), round($totalKhalis,3), $id]);
        
        recalculateChain($customerId);
        if ((int)$receipt['customer_id'] !== $customerId) {
            recalculateChain((int)$receipt['customer_id']);
        }
        recalculateInventoryStock();
        $db->commit();
        setFlash('success', "Receipt {$receipt['receipt_no']} updated successfully.");
        redirect('gold-receipts/show.php?id=' . $id);
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed: ' . $e->getMessage());
        back();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>.receive-row { display:grid; grid-template-columns:2fr 1fr 1fr 1fr 48px; gap:12px; align-items:end; margin-bottom:12px; padding:14px; background:var(--bg-surface); border-radius:12px; border:1px solid var(--border-color); } .receive-row .form-group{margin-bottom:0;} .btn-add-row{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:rgba(16,185,129,0.12);color:var(--success);border:1px dashed var(--success);border-radius:10px;cursor:pointer;font-weight:600;font-size:0.85rem;} .btn-remove-row{width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:rgba(244,63,94,0.1);color:var(--error);border:1px solid rgba(244,63,94,0.2);border-radius:8px;cursor:pointer;} .input-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:22px;}.full-width{grid-column:span 2;}</style>

<div class="page-header"><div class="page-title-group"><h1>Edit Receipt: <?= htmlspecialchars($receipt['receipt_no']) ?></h1></div></div>

<div class="card" style="max-width:800px;padding:28px;">
    <form method="POST">
        <?= csrfField() ?>
        <div class="input-grid">
            <div class="form-group full-width">
                <label>Party</label>
                <select name="customer_id" class="form-control" required>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $receipt['customer_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full-width">
                <label>Receipt Type</label>
                <select name="receipt_type" class="form-control">
                    <option value="customer" <?= $receipt['receipt_type']==='customer'?'selected':'' ?>>Customer</option>
                    <option value="dukandar" <?= $receipt['receipt_type']==='dukandar'?'selected':'' ?>>Dukandar</option>
                    <option value="karigar" <?= $receipt['receipt_type']==='karigar'?'selected':'' ?>>Karigar</option>
                </select>
            </div>
            <div class="form-group"><label>Date</label><input type="date" name="receipt_date" class="form-control" value="<?= $receipt['receipt_date'] ?>" required></div>
        </div>
        
        <h3 style="font-family:'Playfair Display',serif;margin:24px 0 16px;color:var(--gold-primary);font-size:1rem;">Items</h3>
        <div id="items-container"></div>
        <button type="button" class="btn-add-row" onclick="addItem()"><i class="bi bi-plus-lg"></i> Add Item</button>
        
        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-gold"><i class="bi bi-save"></i> Update Receipt</button>
            <a href="<?= url('gold-receipts/show.php?id=' . $id) ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
        </div>
    </form>
</div>

<script>
let itemCount = <?= count($existingItems) ?>;
const existingItems = <?= json_encode($existingItems) ?>;
function addItem(data = null) {
    const container = document.getElementById('items-container');
    const id = itemCount++;
    const desc = data ? (data.description || '') : '';
    const gross = data ? (data.gross_weight || 0) : 0;
    const ratti = data ? (data.ratti_impurity || 0) : 0;
    const html = `<div class="receive-row" id="item-row-${id}">
        <div class="form-group"><label style="font-size:0.7rem;">Description</label><input type="text" name="items[${id}][description]" class="form-control" value="${desc}"></div>
        <div class="form-group"><label style="font-size:0.7rem;">Gross Weight (g)</label><input type="number" name="items[${id}][gross_weight]" class="form-control" step="0.001" value="${gross}" oninput="calcItem(${id})"></div>
        <div class="form-group"><label style="font-size:0.7rem;">Ratti Impurity</label><input type="number" name="items[${id}][ratti_impurity]" class="form-control" step="0.001" value="${ratti}" oninput="calcItem(${id})"></div>
        <div class="form-group"><label style="font-size:0.7rem;">Khalis</label><input type="text" class="form-control" id="item-khalis-${id}" readonly style="color:var(--success);font-weight:600;" value="0.000"></div>
        <button type="button" class="btn-remove-row" onclick="document.getElementById('item-row-${id}').remove()"><i class="bi bi-trash"></i></button>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    calcItem(id);
}
function calcItem(id) {
    const row = document.getElementById('item-row-' + id);
    if (!row) return;
    const gross = parseFloat(row.querySelector('[name$="[gross_weight]"]').value) || 0;
    const ratti = parseFloat(row.querySelector('[name$="[ratti_impurity]"]').value) || 0;
    let khalis = 0;
    if (gross > 0) { khalis = Math.round((gross - (gross / 96 * ratti)) * 1000) / 1000; }
    document.getElementById('item-khalis-' + id).value = khalis.toFixed(3);
}
document.addEventListener('DOMContentLoaded', function() { existingItems.forEach(d => addItem(d)); });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>