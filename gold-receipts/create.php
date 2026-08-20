<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'New Gold Receipt';
$db = getDB();
$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();
$nextReceiptNo = generateReceiptNo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $customerId = (int) post('customer_id');
    $receiptType = post('receipt_type', 'customer');
    $receiptDate = post('receipt_date', date('Y-m-d'));
    $remarks = post('remarks');
    $items = post('items', []);
    
    if (!$customerId || !is_array($items) || count($items) === 0) {
        setFlash('error', 'Please select a party and add at least one item.');
        back();
    }
    
    try {
        $db->beginTransaction();
        
        $receiptNo = generateReceiptNo();
        $stmt = $db->prepare("INSERT INTO gold_receipts (receipt_no, customer_id, receipt_type, receipt_date, total_gross_weight, total_khalis_weight, remarks, created_by) VALUES (?, ?, ?, ?, 0, 0, ?, ?)");
        $stmt->execute([$receiptNo, $customerId, $receiptType, $receiptDate, $remarks, $_SESSION['user_id']]);
        $receiptId = $db->lastInsertId();
        
        $totalGross = 0;
        $totalKhalis = 0;
        
        foreach ($items as $item) {
            $gross = parseDecimal($item['gross_weight'] ?? 0);
            $ratti = parseDecimal($item['ratti_impurity'] ?? 0);
            $desc = $item['description'] ?? '';
            
            if ($gross <= 0) continue;
            
            $khalis = convertToKhalis($gross, $ratti);
            
            $stmt = $db->prepare("INSERT INTO gold_receipt_items (receipt_id, description, gross_weight, ratti_impurity, khalis_weight) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$receiptId, $desc, $gross, $ratti, $khalis]);
            
            $totalGross += $gross;
            $totalKhalis += $khalis;
        }
        
        $stmt = $db->prepare("UPDATE gold_receipts SET total_gross_weight = ?, total_khalis_weight = ? WHERE id = ?");
        $stmt->execute([round($totalGross, 3), round($totalKhalis, 3), $receiptId]);
        
        recalculateChain($customerId);
        recalculateInventoryStock();
        $db->commit();
        
        setFlash('success', "Receipt $receiptNo created successfully.");
        if (post('action') === 'save_new') {
            redirect('gold-receipts/create.php');
        }
        redirect('gold-receipts/show.php?id=' . $receiptId);
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed to create receipt: ' . $e->getMessage());
        back();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>New Gold Receipt</h1>
        <p class="font-urdu" style="margin-top:4px;">نیا سونا وصولی</p>
    </div>
</div>

<div class="card" style="max-width:800px;padding:28px;">
    <form method="POST">
        <?= csrfField() ?>
        <div class="input-grid">
            <div class="form-group full-width">
                <label>Party <span class="font-urdu">گاہک / پارٹی</span></label>
                <select name="customer_id" class="form-control" required>
                    <option value="">Select Party</option>
                    <?php foreach ($customers as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= (query('customer_id') == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group full-width">
                <label>Receipt Type <span class="font-urdu">وصولی کی قسم</span></label>
                <select name="receipt_type" class="form-control">
                    <option value="customer">Customer</option>
                    <option value="dukandar">Dukandar</option>
                    <option value="karigar">Karigar</option>
                </select>
            </div>
            <div class="form-group">
                <label>Date <span class="font-urdu">تاریخ</span></label>
                <input type="date" name="receipt_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Receipt No <span class="font-urdu">وصولی نمبر</span></label>
                <input type="text" class="form-control" value="<?= $nextReceiptNo ?>" disabled>
            </div>
        </div>

        <h3 style="font-family:'Playfair Display',serif;margin:24px 0 16px;color:var(--gold-primary);font-size:1rem;">
            <i class="bi bi-box-arrow-in-down"></i> Items
        </h3>
        <div id="items-container"></div>
        <button type="button" class="btn-add-row" onclick="addItem()"><i class="bi bi-plus-lg"></i> Add Item</button>

        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-gold" name="action" value="save"><i class="bi bi-save"></i> Save Receipt</button>
            <button type="submit" class="btn btn-primary" name="action" value="save_new"><i class="bi bi-plus-circle"></i> Save & New</button>
            <a href="<?= url('gold-receipts/index.php') ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
        </div>
    </form>
</div>

<script>
let itemCount = 0;
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>