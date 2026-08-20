<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'New Gold Give';
$db = getDB();
$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();
$nextGiveNo = generateGoldGiveNo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $customerId = (int) post('customer_id');
    $giveType = post('give_type', 'customer');
    $giveDate = post('give_date', date('Y-m-d'));
    $remarks = post('remarks');
    $items = post('items', []);
    if (!$customerId || !is_array($items) || count($items) === 0) { setFlash('error', 'Please select a party and add at least one item.'); back(); }

    try {
        $db->beginTransaction();
        $giveNo = generateGoldGiveNo();
        $stmt = $db->prepare("INSERT INTO gold_gives (give_no, customer_id, give_type, give_date, total_gross_weight, total_khalis_weight, remarks, created_by) VALUES (?, ?, ?, ?, 0, 0, ?, ?)");
        $stmt->execute([$giveNo, $customerId, $giveType, $giveDate, $remarks, $_SESSION['user_id'] ?? null]);
        $giveId = $db->lastInsertId();
        $totalGross = 0; $totalKhalis = 0;
        foreach ($items as $item) {
            $gross = parseDecimal($item['gross_weight'] ?? 0); $ratti = parseDecimal($item['ratti_impurity'] ?? 0); $desc = $item['description'] ?? '';
            if ($gross <= 0) continue;
            $khalis = convertToKhalis($gross, $ratti);
            $db->prepare("INSERT INTO gold_give_items (give_id, description, gross_weight, ratti_impurity, khalis_weight) VALUES (?, ?, ?, ?, ?)")->execute([$giveId, $desc, $gross, $ratti, $khalis]);
            $totalGross += $gross; $totalKhalis += $khalis;
        }
        if ($totalKhalis <= 0) { throw new Exception('At least one item must have gross weight greater than zero.'); }
        $db->prepare("UPDATE gold_gives SET total_gross_weight=?, total_khalis_weight=? WHERE id=?")->execute([round($totalGross,3), round($totalKhalis,3), $giveId]);
        recalculateChain($customerId);
        recalculateInventoryStock();
        $db->commit();
        setFlash('success', "Gold Give $giveNo created successfully.");
        if (post('action') === 'save_new') { redirect('gold-gives/create.php'); }
        redirect('gold-gives/show.php?id=' . $giveId);
    } catch (Exception $e) {
        $db->rollBack(); setFlash('error', 'Failed to create gold give: ' . $e->getMessage()); back();
    }
}

$extraCss = '<style>.receive-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 48px;gap:12px;align-items:end;margin-bottom:12px;padding:14px;background:var(--bg-surface);border-radius:12px;border:1px solid var(--border-color)}.receive-row .form-group{margin-bottom:0}.btn-add-row{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:rgba(244,63,94,.10);color:var(--error);border:1px dashed var(--error);border-radius:10px;cursor:pointer;font-weight:600;font-size:.85rem}.btn-remove-row{width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:rgba(244,63,94,.1);color:var(--error);border:1px solid rgba(244,63,94,.2);border-radius:8px;cursor:pointer}.input-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:22px}.full-width{grid-column:span 2}</style>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div class="page-title-group"><h1>New Gold Give</h1><p class="font-urdu" style="margin-top:4px;">نیا سونا دیا</p></div></div>
<div class="card" style="max-width:900px;padding:28px;"><form method="POST"><?= csrfField() ?>
<div class="input-grid">
<div class="form-group full-width"><label>Party <span class="font-urdu">گاہک / پارٹی</span></label><select name="customer_id" class="form-control" required><option value="">Select Party</option><?php foreach ($customers as $c): ?><option value="<?= $c['id'] ?>" <?= query('customer_id') == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group full-width"><label>Give Type <span class="font-urdu">دینے کی قسم</span></label><select name="give_type" class="form-control"><option value="customer">Customer</option><option value="dukandar">Dukandar</option><option value="karigar">Karigar</option></select></div>
<div class="form-group"><label>Date <span class="font-urdu">تاریخ</span></label><input type="date" name="give_date" class="form-control" value="<?= date('Y-m-d') ?>" required></div>
<div class="form-group"><label>Give No <span class="font-urdu">نمبر</span></label><input type="text" class="form-control" value="<?= $nextGiveNo ?>" disabled></div>
<div class="form-group full-width"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"></textarea></div>
</div>
<h3 style="font-family:'Playfair Display',serif;margin:24px 0 16px;color:var(--gold-primary);font-size:1rem;"><i class="bi bi-box-arrow-up-right"></i> Items Given</h3>
<div id="items-container"></div><button type="button" class="btn-add-row" onclick="addItem()"><i class="bi bi-plus-lg"></i> Add Item</button>
<div id="totals" style="margin-top:16px;padding:14px;background:var(--bg-surface);border-radius:10px;font-weight:600;">Total Khalis Given: <span id="total-khalis" class="mono text-danger">0.000 g</span></div>
<div style="display:flex;gap:10px;margin-top:24px;"><button type="submit" class="btn btn-gold" name="action" value="save"><i class="bi bi-save"></i> Save Gold Give</button><button type="submit" class="btn btn-primary" name="action" value="save_new"><i class="bi bi-plus-circle"></i> Save & New</button><a href="<?= url('gold-gives/index.php') ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a></div>
</form></div>
<script>
let itemCount=0;
function customRound2(v){let scaled=Math.round(v*1000), hundreds=Math.floor(scaled/10), rem=Math.abs(scaled)%10; return (rem>=8?(hundreds+1):hundreds)/100;}
function esc(s){return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
function addItem(data=null){const c=document.getElementById('items-container'), id=itemCount++, desc=data?(data.description||''):'', gross=data?(data.gross_weight||0):0, ratti=data?(data.ratti_impurity||0):0; c.insertAdjacentHTML('beforeend',`<div class="receive-row" id="item-row-${id}"><div class="form-group"><label style="font-size:.7rem;">Description</label><input type="text" name="items[${id}][description]" class="form-control" value="${esc(desc)}"></div><div class="form-group"><label style="font-size:.7rem;">Gross Weight (g)</label><input type="number" name="items[${id}][gross_weight]" class="form-control" step="0.001" value="${gross}" oninput="calcAll()"></div><div class="form-group"><label style="font-size:.7rem;">Ratti Impurity</label><input type="number" name="items[${id}][ratti_impurity]" class="form-control" step="0.001" value="${ratti}" oninput="calcAll()"></div><div class="form-group"><label style="font-size:.7rem;">Khalis</label><input type="text" class="form-control khalis-display" readonly style="color:var(--error);font-weight:600;" value="0.000"></div><button type="button" class="btn-remove-row" onclick="this.closest('.receive-row').remove();calcAll();"><i class="bi bi-trash"></i></button></div>`); calcAll();}
function calcAll(){let total=0; document.querySelectorAll('[id^="item-row-"]').forEach(row=>{const gross=parseFloat(row.querySelector('[name$="[gross_weight]"]').value)||0, ratti=parseFloat(row.querySelector('[name$="[ratti_impurity]"]').value)||0; let khalis=0; if(gross>0) khalis=customRound2(gross-(gross/96*ratti)); row.querySelector('.khalis-display').value=khalis.toFixed(3); total+=khalis;}); document.getElementById('total-khalis').textContent=total.toFixed(3)+' g';}
document.addEventListener('DOMContentLoaded',()=>addItem());
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
