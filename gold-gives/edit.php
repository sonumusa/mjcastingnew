<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'Edit Gold Give';
$id = (int) query('id', 0);
$db = getDB();
$stmt = $db->prepare("SELECT * FROM gold_gives WHERE id=? AND deleted_at IS NULL"); $stmt->execute([$id]); $give = $stmt->fetch();
if (!$give) { setFlash('error','Gold give not found.'); redirect('gold-gives/index.php'); }
$stmt = $db->prepare("SELECT * FROM gold_give_items WHERE give_id=?"); $stmt->execute([$id]); $existingItems = $stmt->fetchAll();
$customers = $db->query("SELECT id, name FROM customers WHERE status='active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    requireCsrf();
    $customerId=(int)post('customer_id'); $giveType=post('give_type','customer'); $giveDate=post('give_date',date('Y-m-d')); $remarks=post('remarks'); $items=post('items',[]);
    try { $db->beginTransaction();
        $db->prepare("UPDATE gold_gives SET customer_id=?, give_type=?, give_date=?, remarks=?, updated_by=? WHERE id=?")->execute([$customerId,$giveType,$giveDate,$remarks,$_SESSION['user_id']??null,$id]);
        $db->prepare("DELETE FROM gold_give_items WHERE give_id=?")->execute([$id]);
        $totalGross=0; $totalKhalis=0;
        foreach ($items as $item) { $gross=parseDecimal($item['gross_weight']??0); $ratti=parseDecimal($item['ratti_impurity']??0); if($gross<=0) continue; $khalis=convertToKhalis($gross,$ratti); $db->prepare("INSERT INTO gold_give_items (give_id,description,gross_weight,ratti_impurity,khalis_weight) VALUES (?,?,?,?,?)")->execute([$id,$item['description']??'',$gross,$ratti,$khalis]); $totalGross+=$gross; $totalKhalis+=$khalis; }
        $db->prepare("UPDATE gold_gives SET total_gross_weight=?, total_khalis_weight=? WHERE id=?")->execute([round($totalGross,3),round($totalKhalis,3),$id]);
        recalculateChain($customerId);
        if ((int)$give['customer_id'] !== $customerId) {
            recalculateChain((int)$give['customer_id']);
        }
        recalculateInventoryStock();
        $db->commit(); setFlash('success', "Gold Give {$give['give_no']} updated successfully."); redirect('gold-gives/show.php?id='.$id);
    } catch(Exception $e){ $db->rollBack(); setFlash('error','Failed: '.$e->getMessage()); back(); }
}
$extraCss = '<style>.receive-row{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 48px;gap:12px;align-items:end;margin-bottom:12px;padding:14px;background:var(--bg-surface);border-radius:12px;border:1px solid var(--border-color)}.receive-row .form-group{margin-bottom:0}.btn-add-row{display:inline-flex;align-items:center;gap:8px;padding:10px 18px;background:rgba(244,63,94,.10);color:var(--error);border:1px dashed var(--error);border-radius:10px;cursor:pointer;font-weight:600;font-size:.85rem}.btn-remove-row{width:36px;height:36px;display:flex;align-items:center;justify-content:center;background:rgba(244,63,94,.1);color:var(--error);border:1px solid rgba(244,63,94,.2);border-radius:8px;cursor:pointer}.input-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:22px}.full-width{grid-column:span 2}</style>';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header"><div class="page-title-group"><h1>Edit Gold Give: <?= htmlspecialchars($give['give_no']) ?></h1></div></div>
<div class="card" style="max-width:900px;padding:28px;"><form method="POST"><?= csrfField() ?><div class="input-grid">
<div class="form-group full-width"><label>Party</label><select name="customer_id" class="form-control" required><?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>" <?= $give['customer_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option><?php endforeach; ?></select></div>
<div class="form-group full-width"><label>Give Type</label><select name="give_type" class="form-control"><option value="customer" <?= $give['give_type']==='customer'?'selected':'' ?>>Customer</option><option value="dukandar" <?= $give['give_type']==='dukandar'?'selected':'' ?>>Dukandar</option><option value="karigar" <?= $give['give_type']==='karigar'?'selected':'' ?>>Karigar</option></select></div>
<div class="form-group"><label>Date</label><input type="date" name="give_date" class="form-control" value="<?= htmlspecialchars($give['give_date']) ?>" required></div>
<div class="form-group full-width"><label>Remarks</label><textarea name="remarks" class="form-control" rows="2"><?= htmlspecialchars($give['remarks'] ?? '') ?></textarea></div>
</div><h3 style="font-family:'Playfair Display',serif;margin:24px 0 16px;color:var(--gold-primary);font-size:1rem;">Items Given</h3><div id="items-container"></div><button type="button" class="btn-add-row" onclick="addItem()"><i class="bi bi-plus-lg"></i> Add Item</button><div style="display:flex;gap:10px;margin-top:24px;"><button type="submit" class="btn btn-gold"><i class="bi bi-save"></i> Update</button><a href="<?= url('gold-gives/show.php?id='.$id) ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a></div></form></div>
<script>
let itemCount=0; const existingItems=<?= json_encode($existingItems) ?>;
function customRound2(v){let scaled=Math.round(v*1000), hundreds=Math.floor(scaled/10), rem=Math.abs(scaled)%10; return (rem>=8?(hundreds+1):hundreds)/100;} function esc(s){return String(s).replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));}
function addItem(data=null){const c=document.getElementById('items-container'), id=itemCount++, desc=data?(data.description||''):'', gross=data?(data.gross_weight||0):0, ratti=data?(data.ratti_impurity||0):0; c.insertAdjacentHTML('beforeend',`<div class="receive-row" id="item-row-${id}"><div class="form-group"><label style="font-size:.7rem;">Description</label><input type="text" name="items[${id}][description]" class="form-control" value="${esc(desc)}"></div><div class="form-group"><label style="font-size:.7rem;">Gross Weight (g)</label><input type="number" name="items[${id}][gross_weight]" class="form-control" step="0.001" value="${gross}" oninput="calcAll()"></div><div class="form-group"><label style="font-size:.7rem;">Ratti Impurity</label><input type="number" name="items[${id}][ratti_impurity]" class="form-control" step="0.001" value="${ratti}" oninput="calcAll()"></div><div class="form-group"><label style="font-size:.7rem;">Khalis</label><input type="text" class="form-control khalis-display" readonly style="color:var(--error);font-weight:600;" value="0.000"></div><button type="button" class="btn-remove-row" onclick="this.closest('.receive-row').remove();calcAll();"><i class="bi bi-trash"></i></button></div>`); calcAll();}
function calcAll(){document.querySelectorAll('[id^="item-row-"]').forEach(row=>{const gross=parseFloat(row.querySelector('[name$="[gross_weight]"]').value)||0, ratti=parseFloat(row.querySelector('[name$="[ratti_impurity]"]').value)||0; let khalis=0; if(gross>0) khalis=customRound2(gross-(gross/96*ratti)); row.querySelector('.khalis-display').value=khalis.toFixed(3);});}
document.addEventListener('DOMContentLoaded',()=>{ if(existingItems.length) existingItems.forEach(d=>addItem(d)); else addItem(); });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
