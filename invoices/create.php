<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'New Invoice';
$extraCss = '<style>
    .invoice-grid { display: grid; grid-template-columns: 1fr 400px; gap: 32px; align-items: start; }
    .form-section { background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 28px; margin-bottom: 28px; box-shadow: var(--shadow-1); }
    .section-header { display: flex; align-items: center; gap: 14px; margin-bottom: 24px; padding-bottom: 14px; border-bottom: 1px solid var(--border-color); position: relative; }
    .section-header::after { content: ""; position: absolute; bottom: -1px; left: 0; width: 80px; height: 2px; background: var(--gold-primary); border-radius: 2px; }
    .section-header i { color: var(--gold-primary); font-size: 1.35rem; }
    .section-header h3 { font-family: "Playfair Display", serif; font-size: 1.15rem; color: var(--text-primary); margin: 0; }
    .input-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
    .full-width { grid-column: span 2; }
    .form-group label { display: flex; justify-content: space-between; align-items: center; font-size: 0.75rem; color: var(--text-secondary); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; }
    .calc-input-wrapper { position: relative; }
    .calc-input-wrapper input { padding-right: 44px; }
    .unit-label { position: absolute; right: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 0.75rem; pointer-events: none; font-weight: 500; }
    .formula-hint { font-size: 0.7rem; color: var(--text-muted); margin-top: 6px; font-style: italic; }
    .receive-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 48px; gap: 12px; align-items: end; margin-bottom: 12px; padding: 14px; background: var(--bg-surface); border-radius: 12px; border: 1px solid var(--border-color); transition: all 0.2s ease; }
    .receive-row .form-group { margin-bottom: 0; }
    .btn-add-row { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: rgba(16,185,129,0.12); color: var(--success); border: 1px dashed var(--success); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: 0.85rem; transition: all 0.2s ease; }
    .btn-add-row:hover { background: rgba(16,185,129,0.2); transform: translateY(-1px); }
    .btn-remove-row { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; background: rgba(244,63,94,0.1); color: var(--error); border: 1px solid rgba(244,63,94,0.2); border-radius: 8px; cursor: pointer; transition: all 0.2s ease; }
    .btn-remove-row:hover { background: rgba(244,63,94,0.2); transform: scale(1.05); }
    .live-panel { position: sticky; top: 88px; background-color: var(--bg-card); border: 1px solid var(--gold-primary); border-radius: 16px; padding: 28px; box-shadow: var(--shadow-3); }
    .live-panel h4 { color: var(--gold-primary); margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; font-family: "Playfair Display", serif; font-size: 1.15rem; }
    .live-row { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.04); font-size: 0.88rem; }
    .live-row:last-child { border-bottom: none; }
    .live-label { display: flex; align-items: center; gap: 10px; color: var(--text-secondary); font-weight: 500; }
    .live-value { font-family: "JetBrains Mono", monospace; color: var(--text-primary); font-weight: 600; }
    .highlight-gold { background: rgba(218, 165, 32, 0.08); margin: 6px -28px; padding: 12px 28px; color: var(--gold-bright); border-left: 3px solid var(--gold-primary); border-right: 3px solid var(--gold-primary); }
    .highlight-gold .live-value { color: var(--gold-bright); font-weight: 700; }
    .total-box { margin-top: 24px; padding: 18px; border-radius: 12px; text-align: center; box-shadow: var(--shadow-2); }
    .box-grand-total { background: linear-gradient(135deg, #064e3b, #059669); border: 1px solid var(--success); }
    .box-remaining { background: linear-gradient(135deg, #450a0a, #b91c1c); border: 1px solid var(--error); }
    .box-remaining.cleared { background: linear-gradient(135deg, #064e3b, #059669); border: 1px solid var(--success); }
    .total-label { font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.12em; opacity: 0.9; margin-bottom: 6px; font-weight: 700; }
    .total-value { font-family: "JetBrains Mono", monospace; font-size: 1.6rem; font-weight: 700; }
    @media (max-width: 1024px) { .invoice-grid { grid-template-columns: 1fr; } .live-panel { position: static; } }
</style>';

$db = getDB();
$customers = $db->query("SELECT id, name, opening_balance FROM customers WHERE status = 'active' ORDER BY name")->fetchAll();
$nextInvoiceNo = generateInvoiceNo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $customerId = (int) post('customer_id');
    $invoiceType = post('invoice_type', 'customer');
    $invoiceDate = post('invoice_date', date('Y-m-d'));
    $manualBookNo = post('manual_book_no');
    $remarks = post('remarks');
    
    // Gold calculation inputs
    $castingWeight = parseDecimal(post('casting_weight', 0));
    $ratti = parseDecimal(post('ratti', 0));
    $rattiRate = parseDecimal(post('ratti_rate', 0));
    $rpRate = parseDecimal(post('rp_rate', 0));
    $rpMazdoriWeight = parseDecimal(post('rp_mazdori_weight', 0));
    $rpMazdoriRate = parseDecimal(post('rp_mazdori_rate', 0));
    $castingMazdoriWeight = parseDecimal(post('casting_mazdori_weight', 0));
    $castingMazdoriRate = parseDecimal(post('casting_mazdori_rate', 0));
    $wasooli = parseDecimal(post('wasooli', 0));
    
    // Validate
    if (!$customerId) {
        setFlash('error', 'Please select a party.');
        back();
    }
    
    // Calculate total received khalis from dynamic rows
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
    
    // Get previous balance
    $previousBalance = getPreviousBalance($customerId);
    
    // Run calculation
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
        
        $invoiceNo = generateInvoiceNo();
        $stmt = $db->prepare("INSERT INTO invoices (
            invoice_no, customer_id, invoice_type, invoice_date, manual_book_no,
            casting_weight, waste_weight, total_weight, ratti, ratti_rate, male_waste, gold_khalis, total_received_khalis,
            rp_rate, rp_amount, rp_mazdori_weight, rp_mazdori_rate, rp_mazdori_amount,
            casting_mazdori_weight, casting_mazdori_rate, casting_mazdori_amount,
            effective_gold, grand_total, wasooli, previous_balance, remaining_balance,
            remarks, status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)");
        
        $stmt->execute([
            $invoiceNo, $customerId, $invoiceType, $invoiceDate, $manualBookNo,
            $calc['casting_weight'], $calc['waste_weight'], $calc['total_weight'],
            $calc['ratti'], $calc['ratti_rate'], $calc['male_waste'], $calc['gold_khalis'], $totalReceivedKhalis,
            $calc['rp_rate'], $calc['rp_amount'], $calc['rp_mazdori_weight'], $calc['rp_mazdori_rate'], $calc['rp_mazdori_amount'],
            $calc['casting_mazdori_weight'], $calc['casting_mazdori_rate'], $calc['casting_mazdori_amount'],
            $calc['effective_gold'], $calc['grand_total'], $calc['wasooli'], $previousBalance, $calc['remaining_balance'],
            $remarks, $_SESSION['user_id']
        ]);
        
        $invoiceId = $db->lastInsertId();
        
        // Create receive rows
        foreach ($receivesData as $rec) {
            $stmt = $db->prepare("INSERT INTO invoice_receives (invoice_id, description, gross_weight, ratti_impurity, khalis_weight) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$invoiceId, $rec['description'], $rec['gross_weight'], $rec['ratti_impurity'], $rec['khalis_weight']]);
        }
        
        // Recalculate balance chain
        recalculateChain($customerId);
        
        $db->commit();
        
        setFlash('success', "Invoice $invoiceNo created successfully.");
        
        if (post('action') === 'print') {
            redirect('invoices/print.php?id=' . $invoiceId);
        }
        redirect('invoices/show.php?id=' . $invoiceId);
        
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed to save invoice: ' . $e->getMessage());
        back();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<form method="POST" id="invoice-form">
    <?= csrfField() ?>
    <input type="hidden" name="previous_balance" id="previous_balance" value="0">
    <input type="hidden" name="total_received_khalis" id="total_received_khalis" value="0">
    
    <div class="invoice-grid">
        <!-- Left Column: Form -->
        <div class="form-column">
            <div class="page-header" style="margin-bottom:28px;">
                <div class="page-title-group">
                    <h1>New Invoice</h1>
                    <p class="font-urdu" style="margin-top:4px;">نیا بل</p>
                </div>
            </div>

            <!-- Invoice Details -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-info-circle"></i>
                    <h3>Invoice Details</h3>
                </div>
                <div class="input-grid">
                    <div class="form-group full-width">
                        <label>Invoice Type <span class="font-urdu">بل کی قسم</span></label>
                        <select name="invoice_type" id="invoice_type" class="form-control" required>
                            <option value="customer">Customer Invoice (گاہک بل)</option>
                            <option value="dukandar">Dukandar Invoice (دوکاندار بل)</option>
                            <option value="karigar">Karigar Invoice (کاریگر بل)</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Party <span class="font-urdu">گاہک / پارٹی</span></label>
                        <select name="customer_id" id="customer_id" class="form-control" required onchange="updateCustomerBalance(this.value)">
                            <option value="">Select Party</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (query('customer_id') == $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div id="customer-info" class="customer-info-box" style="display:none;background:var(--bg-surface);border:1px solid var(--gold-muted);padding:12px 16px;border-radius:10px;margin-top:12px;">
                            <div style="display:flex;justify-content:space-between;">
                                <span class="text-muted" style="font-size:0.8rem;">Last Balance: <span id="last-balance-display" class="mono" style="font-weight:600;">0.000 g</span></span>
                                <span class="text-muted" style="font-size:0.8rem;">Opening: <span id="opening-balance-display" class="mono">0.000 g</span></span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Date <span class="font-urdu">تاریخ</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Book No <span class="font-urdu">بک نمبر</span></label>
                        <input type="text" name="manual_book_no" class="form-control" placeholder="Optional">
                    </div>
                </div>
            </div>

            <!-- Gold Calculation Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-calculator"></i>
                    <h3>Gold Calculation</h3>
                </div>
                <div class="input-grid">
                    <div class="form-group">
                        <label>Casting Weight <span class="font-urdu">کاسٹنگ وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="casting_weight" id="casting_weight" class="form-control" step="0.001" value="0" required oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Weight of gold to be cast</div>
                    </div>
                    <div class="form-group">
                        <label>Ratti <span class="font-urdu">رتی</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="ratti" id="ratti" class="form-control" step="0.001" value="0" required oninput="calculateLive()">
                        </div>
                        <div class="formula-hint">Ratti impurity level</div>
                    </div>
                    <div class="form-group">
                        <label>Ratti Rate <span class="font-urdu">رتی ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="ratti_rate" id="ratti_rate" class="form-control" step="0.001" value="0" required oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Deduction per 10g casting</div>
                    </div>
                    <div class="form-group">
                        <label>RP Rate <span class="font-urdu">آر پی ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="rp_rate" id="rp_rate" class="form-control" step="0.01" value="0" required oninput="calculateLive()">
                            <span class="unit-label">Rs</span>
                        </div>
                        <div class="formula-hint">Redemption price per gram</div>
                    </div>
                    <div class="form-group">
                        <label>RP Mazdori Weight <span class="font-urdu">آر پی مزدوری وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="rp_mazdori_weight" id="rp_mazdori_weight" class="form-control" step="0.001" value="0" oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>RP Mazdori Rate <span class="font-urdu">آر پی مزدوری ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="rp_mazdori_rate" id="rp_mazdori_rate" class="form-control" step="0.01" value="0" oninput="calculateLive()">
                            <span class="unit-label">Rs</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Casting Mazdori Weight <span class="font-urdu">کاسٹنگ مزدوری وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="casting_mazdori_weight" id="casting_mazdori_weight" class="form-control" step="0.001" value="0" oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Casting Mazdori Rate <span class="font-urdu">کاسٹنگ مزدوری ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="casting_mazdori_rate" id="casting_mazdori_rate" class="form-control" step="0.01" value="0" oninput="calculateLive()">
                            <span class="unit-label">Rs</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Wasooli <span class="font-urdu">وصولی</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="wasooli" id="wasooli" class="form-control" step="0.001" value="0" oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Cash received from party</div>
                    </div>
                </div>
            </div>

            <!-- Receive Rows Section -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <h3>Gold Received from Party <span class="font-urdu" style="font-size:0.8rem;">پارٹی سے سونا وصولی</span></h3>
                </div>
                <div id="receives-container">
                    <!-- Receive rows will be added here -->
                </div>
                <button type="button" class="btn-add-row" onclick="addReceiveRow()">
                    <i class="bi bi-plus-lg"></i> Add Receive Row
                </button>
            </div>

            <!-- Remarks -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-chat-text"></i>
                    <h3>Remarks</h3>
                </div>
                <div class="form-group">
                    <textarea name="remarks" class="form-control" rows="3" placeholder="Optional notes..."></textarea>
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="display:flex;gap:12px;padding:24px 0;">
                <button type="submit" class="btn btn-gold" style="flex:1;"><i class="bi bi-save"></i> Save Invoice</button>
                <button type="submit" name="action" value="print" class="btn btn-primary"><i class="bi bi-printer"></i> Save & Print</button>
                <a href="<?= url('invoices/index.php') ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
            </div>
        </div>

        <!-- Right Column: Live Calculation Panel -->
        <div class="live-panel" id="live-panel">
            <h4>
                <span><i class="bi bi-lightning-charge"></i> Live Calculation</span>
                <span class="font-urdu" style="font-size:0.85rem;color:var(--text-muted);">فوری حساب</span>
            </h4>

            <div class="live-row">
                <span class="live-label"><i class="bi bi-arrow-right" style="color:var(--text-muted);"></i> Casting Weight</span>
                <span class="live-value" id="live-casting">0.000 g</span>
            </div>
            <div class="live-row">
                <span class="live-label"><i class="bi bi-arrow-right" style="color:var(--text-muted);"></i> + Waste Weight</span>
                <span class="live-value" id="live-waste">0.000 g</span>
            </div>
            <div class="live-row highlight-blue">
                <span class="live-label"><i class="bi bi-plus-circle" style="color:var(--info);"></i> = Total Weight</span>
                <span class="live-value" id="live-total">0.000 g</span>
            </div>
            <div class="live-row">
                <span class="live-label"><i class="bi bi-arrow-right" style="color:var(--text-muted);"></i> - Male Waste</span>
                <span class="live-value" id="live-male-waste">0.000 g</span>
            </div>
            <div class="live-row highlight-gold">
                <span class="live-label"><i class="bi bi-gem" style="color:var(--gold-bright);"></i> = Gold Khalis</span>
                <span class="live-value" id="live-gold-khalis">0.000 g</span>
            </div>
            <div class="live-row">
                <span class="live-label"><i class="bi bi-arrow-right" style="color:var(--text-muted);"></i> + RP Mazdori Wt</span>
                <span class="live-value" id="live-rp-mazdori-wt">0.000 g</span>
            </div>
            <div class="live-row">
                <span class="live-label"><i class="bi bi-arrow-right" style="color:var(--text-muted);"></i> + Casting Mazdori Wt</span>
                <span class="live-value" id="live-casting-mazdori-wt">0.000 g</span>
            </div>
            <div class="live-row highlight-gold">
                <span class="live-label"><i class="bi bi-check-circle" style="color:var(--success);"></i> = Effective Gold</span>
                <span class="live-value" id="live-effective">0.000 g</span>
            </div>

            <div class="total-box box-grand-total">
                <div class="total-label">Grand Total (Effective Gold)</div>
                <div class="total-value" id="live-grand-total" style="color:#fff;">0.000 g</div>
            </div>

            <div class="total-box box-remaining" id="remaining-box">
                <div class="total-label">Remaining Balance</div>
                <div class="total-value" id="live-remaining" style="color:#fff;">0.000 g</div>
            </div>

            <div style="margin-top:16px;padding:14px;background:var(--bg-surface);border-radius:10px;">
                <div style="font-size:0.72rem;color:var(--text-muted);">RP Amount: <span id="live-rp-amount" class="mono">Rs 0.00</span></div>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;">RP Mazdori Amt: <span id="live-rp-mazdori-amt" class="mono">Rs 0.00</span></div>
                <div style="font-size:0.72rem;color:var(--text-muted);margin-top:4px;">Casting Mazdori Amt: <span id="live-casting-mazdori-amt" class="mono">Rs 0.00</span></div>
            </div>
        </div>
    </div>
</form>

<script>
let receiveRowCount = 0;

function addReceiveRow(data = null) {
    const container = document.getElementById('receives-container');
    const rowId = receiveRowCount++;
    
    const desc = data ? data.description || '' : '';
    const gross = data ? data.gross_weight || 0 : 0;
    const ratti = data ? data.ratti_impurity || 0 : 0;
    
    const html = `
        <div class="receive-row" id="receive-row-${rowId}">
            <div class="form-group">
                <label style="font-size:0.7rem;">Description</label>
                <input type="text" name="receives[${rowId}][description]" class="form-control" placeholder="e.g., Old Jewelry" value="${desc}">
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem;">Gross Weight (g)</label>
                <input type="number" name="receives[${rowId}][gross_weight]" class="form-control" step="0.001" value="${gross}" oninput="calculateLive()">
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem;">Ratti Impurity</label>
                <input type="number" name="receives[${rowId}][ratti_impurity]" class="form-control" step="0.001" value="${ratti}" oninput="calculateLive()">
            </div>
            <div class="form-group">
                <label style="font-size:0.7rem;">Khalis (auto)</label>
                <input type="text" class="form-control" id="khalis-display-${rowId}" readonly style="color:var(--success);font-weight:600;" value="0.000">
            </div>
            <button type="button" class="btn-remove-row" onclick="removeReceiveRow(${rowId})">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', html);
    calculateLive();
}

function removeReceiveRow(id) {
    const row = document.getElementById('receive-row-' + id);
    if (row) row.remove();
    calculateLive();
}

function calculateLive() {
    // Get input values
    const castingWeight = parseFloat(document.getElementById('casting_weight').value) || 0;
    const ratti = parseFloat(document.getElementById('ratti').value) || 0;
    const rattiRate = parseFloat(document.getElementById('ratti_rate').value) || 0;
    const rpRate = parseFloat(document.getElementById('rp_rate').value) || 0;
    const rpMazdoriWeight = parseFloat(document.getElementById('rp_mazdori_weight').value) || 0;
    const rpMazdoriRate = parseFloat(document.getElementById('rp_mazdori_rate').value) || 0;
    const castingMazdoriWeight = parseFloat(document.getElementById('casting_mazdori_weight').value) || 0;
    const castingMazdoriRate = parseFloat(document.getElementById('casting_mazdori_rate').value) || 0;
    const wasooli = parseFloat(document.getElementById('wasooli').value) || 0;
    
    // Calculate receive rows
    let totalReceivedKhalis = 0;
    document.querySelectorAll('[id^="receive-row-"]').forEach(row => {
        const grossInput = row.querySelector('[name$="[gross_weight]"]');
        const rattiInput = row.querySelector('[name$="[ratti_impurity]"]');
        const display = row.querySelector('[id^="khalis-display-"]');
        
        if (grossInput && rattiInput && display) {
            const gross = parseFloat(grossInput.value) || 0;
            const rattiImp = parseFloat(rattiInput.value) || 0;
            let khalis = 0;
            if (gross > 0) {
                khalis = gross - (gross / 96 * rattiImp);
                khalis = Math.round(khalis * 1000) / 1000;
            }
            display.value = khalis.toFixed(3);
            totalReceivedKhalis += khalis;
        }
    });
    totalReceivedKhalis = Math.round(totalReceivedKhalis * 1000) / 1000;
    
    document.getElementById('total_received_khalis').value = totalReceivedKhalis;
    
    // Previous balance (from hidden or customer)
    const previousBalance = parseFloat(document.getElementById('previous_balance').value) || 0;
    
    // Calculations
    // 1. Waste Weight
    let wasteWeight = 0;
    if (castingWeight > 0 && rattiRate > 0) {
        wasteWeight = Math.round((castingWeight / 10 * rattiRate) * 1000) / 1000;
    }
    
    // 2. Total Weight
    const totalWeight = Math.round((castingWeight + wasteWeight) * 1000) / 1000;
    
    // 3. Male Waste
    let maleWaste = 0;
    if (totalWeight > 0 && ratti > 0) {
        maleWaste = Math.round((totalWeight / 96 * ratti) * 1000) / 1000;
    }
    
    // 4. Gold Khalis
    const goldKhalis = Math.round((totalWeight - maleWaste) * 1000) / 1000;
    
    // 5. RP Amount
    const rpAmount = Math.round(goldKhalis * rpRate * 100) / 100;
    
    // 6. RP Mazdori Amount
    const rpMazdoriAmount = Math.round(rpMazdoriWeight * rpMazdoriRate * 100) / 100;
    
    // 7. Casting Mazdori Amount
    const castingMazdoriAmount = Math.round(castingMazdoriWeight * castingMazdoriRate * 100) / 100;
    
    // 8. Effective Gold
    const effectiveGold = Math.round((goldKhalis + rpMazdoriWeight + castingMazdoriWeight) * 1000) / 1000;
    
    // 9. Grand Total
    const grandTotal = effectiveGold;
    
    // 10. Remaining Balance
    const remainingBalance = Math.round((previousBalance + effectiveGold - wasooli - totalReceivedKhalis) * 1000) / 1000;
    
    // Update live panel
    document.getElementById('live-casting').textContent = castingWeight.toFixed(3) + ' g';
    document.getElementById('live-waste').textContent = wasteWeight.toFixed(3) + ' g';
    document.getElementById('live-total').textContent = totalWeight.toFixed(3) + ' g';
    document.getElementById('live-male-waste').textContent = maleWaste.toFixed(3) + ' g';
    document.getElementById('live-gold-khalis').textContent = goldKhalis.toFixed(3) + ' g';
    document.getElementById('live-rp-mazdori-wt').textContent = rpMazdoriWeight.toFixed(3) + ' g';
    document.getElementById('live-casting-mazdori-wt').textContent = castingMazdoriWeight.toFixed(3) + ' g';
    document.getElementById('live-effective').textContent = effectiveGold.toFixed(3) + ' g';
    document.getElementById('live-grand-total').textContent = grandTotal.toFixed(3) + ' g';
    
    const remainingEl = document.getElementById('live-remaining');
    remainingEl.textContent = remainingBalance.toFixed(3) + ' g';
    
    const remainingBox = document.getElementById('remaining-box');
    if (remainingBalance <= 0) {
        remainingBox.className = 'total-box box-remaining cleared';
    } else {
        remainingBox.className = 'total-box box-remaining';
    }
    
    document.getElementById('live-rp-amount').textContent = 'Rs ' + rpAmount.toFixed(2);
    document.getElementById('live-rp-mazdori-amt').textContent = 'Rs ' + rpMazdoriAmount.toFixed(2);
    document.getElementById('live-casting-mazdori-amt').textContent = 'Rs ' + castingMazdoriAmount.toFixed(2);
}

function updateCustomerBalance(customerId) {
    if (!customerId) {
        document.getElementById('customer-info').style.display = 'none';
        document.getElementById('previous_balance').value = 0;
        calculateLive();
        return;
    }
    
    fetch('<?= url('api/customer_balance.php?id=') ?>' + customerId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('last-balance-display').textContent = data.balance.toFixed(3) + ' g';
            document.getElementById('opening-balance-display').textContent = data.opening_balance.toFixed(3) + ' g';
            document.getElementById('previous_balance').value = data.balance;
            document.getElementById('customer-info').style.display = 'block';
            calculateLive();
        })
        .catch(() => {
            document.getElementById('customer-info').style.display = 'none';
        });
}

// Auto-load customer balance if pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    if (customerSelect.value) {
        updateCustomerBalance(customerSelect.value);
    }
    calculateLive();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>