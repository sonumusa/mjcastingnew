<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'Edit Invoice';

$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect('invoices/index.php');
}

$stmt = $db->prepare("SELECT * FROM invoice_receives WHERE invoice_id = ?");
$stmt->execute([$id]);
$existingReceives = $stmt->fetchAll();

$customers = $db->query("SELECT id, name FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $customerId = (int) post('customer_id');
    $invoiceType = post('invoice_type', 'customer');
    $invoiceDate = post('invoice_date', date('Y-m-d'));
    $manualBookNo = post('manual_book_no');
    $remarks = post('remarks');
    $status = post('status', 'active');
    
    $castingWeight = parseDecimal(post('casting_weight', 0));
    $ratti = parseDecimal(post('ratti', 0));
    $rattiRate = parseDecimal(post('ratti_rate', 0));
    $rpRate = parseDecimal(post('rp_rate', 0));
    $rpMazdoriWeight = parseDecimal(post('rp_mazdori_weight', 0));
    $rpMazdoriRate = parseDecimal(post('rp_mazdori_rate', 0));
    $castingMazdoriWeight = parseDecimal(post('casting_mazdori_weight', 0));
    $castingMazdoriRate = parseDecimal(post('casting_mazdori_rate', 0));
    $wasooli = parseDecimal(post('wasooli', 0));
    
    $totalReceivedKhalis = 0;
    $receivesData = [];
    $receives = post('receives', []);
    if (is_array($receives)) {
        foreach ($receives as $rec) {
            $gross = parseDecimal($rec['gross_weight'] ?? 0);
            $rattiImpurity = parseDecimal($rec['ratti_impurity'] ?? 0);
            if ($gross > 0) {
                $khalis = convertToKhalis($gross, $rattiImpurity);
                $totalReceivedKhalis += $khalis;
                $receivesData[] = [
                    'description' => $rec['description'] ?? '',
                    'gross_weight' => $gross,
                    'ratti_impurity' => $rattiImpurity,
                    'khalis_weight' => $khalis,
                ];
            }
        }
    }
    $totalReceivedKhalis = round($totalReceivedKhalis, 3);
    
    $previousBalance = getPreviousBalance($customerId, $id);
    
    $calc = calculateGold([
        'casting_weight' => $castingWeight,
        'ratti' => $ratti,
        'ratti_rate' => $rattiRate,
        'rp_rate' => $rpRate,
        'rp_mazdori_weight' => $rpMazdoriWeight,
        'rp_mazdori_rate' => $rpMazdoriRate,
        'casting_mazdori_weight' => $castingMazdoriWeight,
        'casting_mazdori_rate' => $castingMazdoriRate,
        'wasooli' => $wasooli,
        'previous_balance' => $previousBalance,
        'total_received_khalis' => $totalReceivedKhalis,
    ]);
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("UPDATE invoices SET 
            customer_id=?, invoice_type=?, invoice_date=?, manual_book_no=?,
            casting_weight=?, waste_weight=?, total_weight=?, ratti=?, ratti_rate=?, male_waste=?, gold_khalis=?, total_received_khalis=?,
            rp_rate=?, rp_amount=?, rp_mazdori_weight=?, rp_mazdori_rate=?, rp_mazdori_amount=?,
            casting_mazdori_weight=?, casting_mazdori_rate=?, casting_mazdori_amount=?,
            effective_gold=?, grand_total=?, wasooli=?, previous_balance=?, remaining_balance=?,
            remarks=?, status=?, updated_by=?
            WHERE id=?");
        
        $stmt->execute([
            $customerId, $invoiceType, $invoiceDate, $manualBookNo,
            $calc['casting_weight'], $calc['waste_weight'], $calc['total_weight'],
            $calc['ratti'], $calc['ratti_rate'], $calc['male_waste'], $calc['gold_khalis'], $totalReceivedKhalis,
            $calc['rp_rate'], $calc['rp_amount'], $calc['rp_mazdori_weight'], $calc['rp_mazdori_rate'], $calc['rp_mazdori_amount'],
            $calc['casting_mazdori_weight'], $calc['casting_mazdori_rate'], $calc['casting_mazdori_amount'],
            $calc['effective_gold'], $calc['grand_total'], $calc['wasooli'], $previousBalance, $calc['remaining_balance'],
            $remarks, $status, $_SESSION['user_id'],
            $id
        ]);
        
        // Delete old receives and recreate
        $db->prepare("DELETE FROM invoice_receives WHERE invoice_id = ?")->execute([$id]);
        foreach ($receivesData as $rec) {
            $stmt = $db->prepare("INSERT INTO invoice_receives (invoice_id, description, gross_weight, ratti_impurity, khalis_weight) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $rec['description'], $rec['gross_weight'], $rec['ratti_impurity'], $rec['khalis_weight']]);
        }
        
        recalculateChain($customerId);
        
        $db->commit();
        
        setFlash('success', "Invoice {$invoice['invoice_no']} updated successfully.");
        redirect('invoices/show.php?id=' . $id);
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed to update invoice: ' . $e->getMessage());
        back();
    }
}

$breakdown = buildCalculationBreakdown($invoice);

require_once __DIR__ . '/../includes/header.php';
// (Same form as create but with values filled - using same JS logic)
?>
<style>
.invoice-grid { display: grid; grid-template-columns: 1fr 400px; gap: 32px; align-items: start; }
.form-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 28px; margin-bottom: 28px; }
.section-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid var(--border-color); position: relative; }
.section-header::after { content: ""; position: absolute; bottom: -1px; left: 0; width: 80px; height: 2px; background: var(--gold-primary); border-radius: 2px; }
.section-header i { color: var(--gold-primary); font-size: 1.35rem; }
.section-header h3 { font-family: "Playfair Display", serif; font-size: 1.15rem; color: var(--text-primary); margin: 0; }
.input-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
.full-width { grid-column: span 2; }
.calc-input-wrapper { position: relative; }
.calc-input-wrapper input { padding-right: 44px; }
.unit-label { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem; pointer-events: none; font-weight: 500; }
.receive-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 48px; gap: 12px; align-items: end; margin-bottom: 12px; padding: 14px; background: var(--bg-surface); border-radius: 12px; border: 1px solid var(--border-color); }
.receive-row .form-group { margin-bottom: 0; }
.btn-add-row { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: rgba(16,185,129,0.12); color: var(--success); border: 1px dashed var(--success); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
.btn-add-row:hover { background: rgba(16,185,129,0.2); }
.btn-remove-row { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: rgba(244,63,94,0.1); color: var(--error); border: 1px solid rgba(244,63,94,0.2); border-radius: 8px; cursor: pointer; }
.live-panel { position: sticky; top: 88px; background-color: var(--bg-card); border: 1px solid var(--gold-primary); border-radius: 16px; padding: 28px; box-shadow: var(--shadow-3); }
.live-panel h4 { color: var(--gold-primary); margin-bottom: 24px; font-family: "Playfair Display", serif; font-size: 1.15rem; }
.live-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.88rem; }
.live-row:last-child { border-bottom: none; }
.live-label { color: var(--text-secondary); font-weight: 500; }
.live-value { font-family: "JetBrains Mono", monospace; color: var(--text-primary); font-weight: 600; }
.highlight-gold { background: rgba(218, 165, 32, 0.08); margin: 6px -28px; padding: 12px 28px; color: var(--gold-bright); border-left: 3px solid var(--gold-primary); border-right: 3px solid var(--gold-primary); }
.highlight-gold .live-value { color: var(--gold-bright); font-weight: 700; }
.total-box { margin-top: 24px; padding: 18px; border-radius: 12px; text-align: center; }
.box-grand-total { background: linear-gradient(135deg, #064e3b, #059669); border: 1px solid var(--success); }
.box-remaining { background: linear-gradient(135deg, #450a0a, #b91c1c); border: 1px solid var(--error); }
.box-remaining.cleared { background: linear-gradient(135deg, #064e3b, #059669); border: 1px solid var(--success); }
.total-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.9; margin-bottom: 6px; font-weight: 700; }
.total-value { font-family: "JetBrains Mono", monospace; font-size: 1.6rem; font-weight: 700; }
@media (max-width: 1024px) { .invoice-grid { grid-template-columns: 1fr; } .live-panel { position: static; } }
</style>

<form method="POST" id="invoice-form">
    <?= csrfField() ?>
    <input type="hidden" name="previous_balance" id="previous_balance" value="<?= $invoice['previous_balance'] ?>">
    <input type="hidden" name="total_received_khalis" id="total_received_khalis" value="<?= $invoice['total_received_khalis'] ?>">
    
    <div class="invoice-grid">
        <div class="form-column">
            <div class="page-header" style="margin-bottom:28px;">
                <div class="page-title-group">
                    <h1>Edit Invoice: <?= htmlspecialchars($invoice['invoice_no']) ?></h1>
                    <p class="font-urdu" style="margin-top:4px;">بل میں ترمیم</p>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header"><i class="bi bi-info-circle"></i><h3>Invoice Details</h3></div>
                <div class="input-grid">
                    <div class="form-group full-width">
                        <label>Invoice Type</label>
                        <select name="invoice_type" id="invoice_type" class="form-control" required>
                            <option value="customer" <?= $invoice['invoice_type']==='customer'?'selected':'' ?>>Customer</option>
                            <option value="dukandar" <?= $invoice['invoice_type']==='dukandar'?'selected':'' ?>>Dukandar</option>
                            <option value="karigar" <?= $invoice['invoice_type']==='karigar'?'selected':'' ?>>Karigar</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Party <span class="font-urdu">گاہک / پارٹی</span></label>
                        <select name="customer_id" id="customer_id" class="form-control" required onchange="updateCustomerBalance(this.value)">
                            <option value="">Select Party</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $invoice['customer_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="customer-info" class="customer-info-box" style="display:<?= $invoice['customer_id'] ? 'block' : 'none' ?>;background:var(--bg-surface);border:1px solid var(--gold-muted);padding:12px 16px;border-radius:10px;margin-top:12px;">
                            <div style="display:flex;justify-content:space-between;">
                                <span class="text-muted" style="font-size:0.8rem;">Last Balance: <span id="last-balance-display" class="mono" style="font-weight:600;"><?= number_format($invoice['previous_balance'], 3) ?> g</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="invoice_date" class="form-control" value="<?= $invoice['invoice_date'] ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Book No</label>
                        <input type="text" name="manual_book_no" class="form-control" value="<?= htmlspecialchars($invoice['manual_book_no'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="active" <?= $invoice['status']==='active'?'selected':'' ?>>Active</option>
                            <option value="cancelled" <?= $invoice['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header"><i class="bi bi-calculator"></i><h3>Gold Calculation</h3></div>
                <div class="input-grid">
                    <div class="form-group">
                        <label>Casting Weight (g)</label>
                        <input type="number" name="casting_weight" id="casting_weight" class="form-control" step="0.001" value="<?= $invoice['casting_weight'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>Ratti</label>
                        <input type="number" name="ratti" id="ratti" class="form-control" step="0.001" value="<?= $invoice['ratti'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>Ratti Rate</label>
                        <input type="number" name="ratti_rate" id="ratti_rate" class="form-control" step="0.001" value="<?= $invoice['ratti_rate'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>RP Rate</label>
                        <input type="number" name="rp_rate" id="rp_rate" class="form-control" step="0.01" value="<?= $invoice['rp_rate'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>RP Mazdori Weight</label>
                        <input type="number" name="rp_mazdori_weight" id="rp_mazdori_weight" class="form-control" step="0.001" value="<?= $invoice['rp_mazdori_weight'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>RP Mazdori Rate</label>
                        <input type="number" name="rp_mazdori_rate" id="rp_mazdori_rate" class="form-control" step="0.01" value="<?= $invoice['rp_mazdori_rate'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>Casting Mazdori Weight</label>
                        <input type="number" name="casting_mazdori_weight" id="casting_mazdori_weight" class="form-control" step="0.001" value="<?= $invoice['casting_mazdori_weight'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>Casting Mazdori Rate</label>
                        <input type="number" name="casting_mazdori_rate" id="casting_mazdori_rate" class="form-control" step="0.01" value="<?= $invoice['casting_mazdori_rate'] ?>" oninput="calculateLive()">
                    </div>
                    <div class="form-group">
                        <label>Wasooli</label>
                        <input type="number" name="wasooli" id="wasooli" class="form-control" step="0.001" value="<?= $invoice['wasooli'] ?>" oninput="calculateLive()">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="section-header"><i class="bi bi-box-arrow-in-down"></i><h3>Gold Received</h3></div>
                <div id="receives-container"></div>
                <button type="button" class="btn-add-row" onclick="addReceiveRow()"><i class="bi bi-plus-lg"></i> Add Row</button>
            </div>

            <div class="form-section">
                <div class="section-header"><i class="bi bi-chat-text"></i><h3>Remarks</h3></div>
                <textarea name="remarks" class="form-control" rows="3"><?= htmlspecialchars($invoice['remarks'] ?? '') ?></textarea>
            </div>

            <div style="display:flex;gap:12px;padding:24px 0;">
                <button type="submit" class="btn btn-gold" style="flex:1;"><i class="bi bi-save"></i> Update Invoice</button>
                <button type="submit" name="action" value="print" class="btn btn-primary"><i class="bi bi-printer"></i> Update & Print</button>
                <a href="<?= url('invoices/show.php?id=' . $id) ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
            </div>
        </div>

        <div class="live-panel" id="live-panel">
            <h4><i class="bi bi-lightning-charge"></i> Live Calculation</h4>
            <div class="live-row"><span class="live-label">Casting Weight</span><span class="live-value" id="live-casting">0.000 g</span></div>
            <div class="live-row"><span class="live-label">+ Waste Weight</span><span class="live-value" id="live-waste">0.000 g</span></div>
            <div class="live-row highlight-blue"><span class="live-label">= Total Weight</span><span class="live-value" id="live-total">0.000 g</span></div>
            <div class="live-row"><span class="live-label">- Male Waste</span><span class="live-value" id="live-male-waste">0.000 g</span></div>
            <div class="live-row highlight-gold"><span class="live-label">= Gold Khalis</span><span class="live-value" id="live-gold-khalis">0.000 g</span></div>
            <div class="live-row"><span class="live-label">+ RP Mazdori Wt</span><span class="live-value" id="live-rp-mazdori-wt">0.000 g</span></div>
            <div class="live-row"><span class="live-label">+ Casting Mazdori Wt</span><span class="live-value" id="live-casting-mazdori-wt">0.000 g</span></div>
            <div class="live-row highlight-gold"><span class="live-label">= Effective Gold</span><span class="live-value" id="live-effective">0.000 g</span></div>
            <div class="total-box box-grand-total">
                <div class="total-label">Grand Total</div>
                <div class="total-value" id="live-grand-total" style="color:#fff;">0.000 g</div>
            </div>
            <div class="total-box box-remaining" id="remaining-box">
                <div class="total-label">Remaining Balance</div>
                <div class="total-value" id="live-remaining" style="color:#fff;">0.000 g</div>
            </div>
        </div>
    </div>
</form>

<script>
let receiveRowCount = <?= count($existingReceives) ?>;
let existingReceiveData = <?= json_encode($existingReceives) ?>;

function addReceiveRow(data = null) {
    const container = document.getElementById('receives-container');
    const rowId = receiveRowCount++;
    const desc = data ? (data.description || '') : '';
    const gross = data ? (data.gross_weight || 0) : 0;
    const ratti = data ? (data.ratti_impurity || 0) : 0;
    const html = `<div class="receive-row" id="receive-row-${rowId}">
        <div class="form-group"><label style="font-size:0.7rem;">Description</label><input type="text" name="receives[${rowId}][description]" class="form-control" value="${desc}"></div>
        <div class="form-group"><label style="font-size:0.7rem;">Gross Weight</label><input type="number" name="receives[${rowId}][gross_weight]" class="form-control" step="0.001" value="${gross}" oninput="calculateLive()"></div>
        <div class="form-group"><label style="font-size:0.7rem;">Ratti</label><input type="number" name="receives[${rowId}][ratti_impurity]" class="form-control" step="0.001" value="${ratti}" oninput="calculateLive()"></div>
        <div class="form-group"><label style="font-size:0.7rem;">Khalis</label><input type="text" class="form-control" id="khalis-display-${rowId}" readonly style="color:var(--success);font-weight:600;" value="0.000"></div>
        <button type="button" class="btn-remove-row" onclick="removeReceiveRow(${rowId})"><i class="bi bi-trash"></i></button>
    </div>`;
    container.insertAdjacentHTML('beforeend', html);
    calculateLive();
}

function removeReceiveRow(id) {
    const row = document.getElementById('receive-row-' + id);
    if (row) row.remove();
    calculateLive();
}

function calculateLive() {
    const castingWeight = parseFloat(document.getElementById('casting_weight').value) || 0;
    const ratti = parseFloat(document.getElementById('ratti').value) || 0;
    const rattiRate = parseFloat(document.getElementById('ratti_rate').value) || 0;
    const rpRate = parseFloat(document.getElementById('rp_rate').value) || 0;
    const rpMazdoriWeight = parseFloat(document.getElementById('rp_mazdori_weight').value) || 0;
    const rpMazdoriRate = parseFloat(document.getElementById('rp_mazdori_rate').value) || 0;
    const castingMazdoriWeight = parseFloat(document.getElementById('casting_mazdori_weight').value) || 0;
    const castingMazdoriRate = parseFloat(document.getElementById('casting_mazdori_rate').value) || 0;
    const wasooli = parseFloat(document.getElementById('wasooli').value) || 0;
    
    let totalReceivedKhalis = 0;
    document.querySelectorAll('[id^="receive-row-"]').forEach(row => {
        const grossInput = row.querySelector('[name$="[gross_weight]"]');
        const rattiInput = row.querySelector('[name$="[ratti_impurity]"]');
        const display = row.querySelector('[id^="khalis-display-"]');
        if (grossInput && rattiInput && display) {
            const gross = parseFloat(grossInput.value) || 0;
            const rattiImp = parseFloat(rattiInput.value) || 0;
            let khalis = 0;
            if (gross > 0) { khalis = Math.round((gross - (gross / 96 * rattiImp)) * 1000) / 1000; }
            display.value = khalis.toFixed(3);
            totalReceivedKhalis += khalis;
        }
    });
    totalReceivedKhalis = Math.round(totalReceivedKhalis * 1000) / 1000;
    document.getElementById('total_received_khalis').value = totalReceivedKhalis;
    const previousBalance = parseFloat(document.getElementById('previous_balance').value) || 0;
    
    let wasteWeight = 0;
    if (castingWeight > 0 && rattiRate > 0) { wasteWeight = Math.round((castingWeight / 10 * rattiRate) * 1000) / 1000; }
    const totalWeight = Math.round((castingWeight + wasteWeight) * 1000) / 1000;
    let maleWaste = 0;
    if (totalWeight > 0 && ratti > 0) { maleWaste = Math.round((totalWeight / 96 * ratti) * 1000) / 1000; }
    const goldKhalis = Math.round((totalWeight - maleWaste) * 1000) / 1000;
    const rpAmount = Math.round(goldKhalis * rpRate * 100) / 100;
    const rpMazdoriAmount = Math.round(rpMazdoriWeight * rpMazdoriRate * 100) / 100;
    const castingMazdoriAmount = Math.round(castingMazdoriWeight * castingMazdoriRate * 100) / 100;
    const effectiveGold = Math.round((goldKhalis + rpMazdoriWeight + castingMazdoriWeight) * 1000) / 1000;
    const remainingBalance = Math.round((previousBalance + effectiveGold - wasooli - totalReceivedKhalis) * 1000) / 1000;
    
    document.getElementById('live-casting').textContent = castingWeight.toFixed(3) + ' g';
    document.getElementById('live-waste').textContent = wasteWeight.toFixed(3) + ' g';
    document.getElementById('live-total').textContent = totalWeight.toFixed(3) + ' g';
    document.getElementById('live-male-waste').textContent = maleWaste.toFixed(3) + ' g';
    document.getElementById('live-gold-khalis').textContent = goldKhalis.toFixed(3) + ' g';
    document.getElementById('live-rp-mazdori-wt').textContent = rpMazdoriWeight.toFixed(3) + ' g';
    document.getElementById('live-casting-mazdori-wt').textContent = castingMazdoriWeight.toFixed(3) + ' g';
    document.getElementById('live-effective').textContent = effectiveGold.toFixed(3) + ' g';
    document.getElementById('live-grand-total').textContent = effectiveGold.toFixed(3) + ' g';
    document.getElementById('live-remaining').textContent = remainingBalance.toFixed(3) + ' g';
    const remBox = document.getElementById('remaining-box');
    remBox.className = 'total-box box-remaining' + (remainingBalance <= 0 ? ' cleared' : '');
}

function updateCustomerBalance(customerId) {
    if (!customerId) { document.getElementById('customer-info').style.display = 'none'; calculateLive(); return; }
    fetch('<?= url('api/customer_balance.php?id=') ?>' + customerId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('last-balance-display').textContent = data.balance.toFixed(3) + ' g';
            document.getElementById('previous_balance').value = data.balance;
            document.getElementById('customer-info').style.display = 'block';
            calculateLive();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    existingReceiveData.forEach(d => addReceiveRow(d));
    calculateLive();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>