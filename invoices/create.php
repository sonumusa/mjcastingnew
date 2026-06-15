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
    .input-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 22px; }
    .full-width { grid-column: span 3; }
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
    .hidden-field { display: none !important; }

    /* Adjustment Panel Styles */
    .adjustment-panel {
        margin-bottom: 16px;
        padding: 12px 14px;
        background: rgba(67, 97, 238, 0.05);
        border: 1px solid rgba(67, 97, 238, 0.2);
        border-radius: 10px;
        transition: all 0.2s ease;
    }
    .adjustment-panel.active {
        background: rgba(67, 97, 238, 0.1);
        border-color: #4361ee;
    }
    .adjustment-toggle {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        font-size: 0.8rem;
        color: var(--text-secondary);
        font-weight: 600;
    }
    .adjustment-toggle input[type="checkbox"] {
        width: 16px;
        height: 16px;
        accent-color: #4361ee;
    }
    .adjustment-fields {
        display: none;
        margin-top: 12px;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .adjustment-panel.active .adjustment-fields {
        display: grid;
    }
    .adj-control {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .adj-control label {
        font-size: 0.65rem;
        color: var(--text-muted);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .adj-input-group {
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .adj-btn {
        width: 28px;
        height: 28px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        background: var(--bg-surface);
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--text-secondary);
        transition: all 0.15s ease;
    }
    .adj-btn:hover {
        background: #4361ee;
        color: #fff;
        border-color: #4361ee;
    }
    .adj-input {
        width: 70px;
        text-align: center;
        font-size: 0.8rem;
        padding: 4px 6px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        background: var(--bg-card);
        color: var(--text-primary);
        font-family: "JetBrains Mono", monospace;
    }

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
    $rpMazdoriAmount = parseDecimal(post('rp_mazdori_amount', 0));
    $castingMazdoriWeight = parseDecimal(post('casting_mazdori_weight', 0));
    $castingMazdoriAmount = parseDecimal(post('casting_mazdori_amount', 0));
    $wasooli = parseDecimal(post('wasooli', 0));

    // Fraction adjustment overrides
    $wasteWeightOverride = post('waste_weight_override', null);
    $maleWasteOverride = post('male_waste_override', null);

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
        'rp_mazdori_rate' => 0,
        'casting_mazdori_weight' => $castingMazdoriWeight,
        'casting_mazdori_rate' => 0,
        'wasooli' => $wasooli,
        'previous_balance' => $previousBalance,
        'total_received_khalis' => $totalReceivedKhalis,
    ]);

    // Override amounts with manually entered values
    $calc['rp_mazdori_amount'] = $rpMazdoriAmount;
    $calc['casting_mazdori_amount'] = $castingMazdoriAmount;

    // Apply fraction adjustment overrides
    if ($wasteWeightOverride !== null && $wasteWeightOverride !== '') {
        $calc['waste_weight'] = parseDecimal($wasteWeightOverride);
        $calc['total_weight'] = round($calc['casting_weight'] + $calc['waste_weight'], 3);

        if ($maleWasteOverride !== null && $maleWasteOverride !== '') {
            $calc['male_waste'] = parseDecimal($maleWasteOverride);
        } else {
            $calc['male_waste'] = round(($calc['total_weight'] / 96) * $calc['ratti'], 3);
        }
    } elseif ($maleWasteOverride !== null && $maleWasteOverride !== '') {
        $calc['male_waste'] = parseDecimal($maleWasteOverride);
    }

    // Recalculate downstream values after overrides
    $calc['gold_khalis'] = round($calc['total_weight'] - $calc['male_waste'], 3);
    $calc['effective_gold'] = round($calc['gold_khalis'] + $calc['rp_mazdori_weight'] + $calc['casting_mazdori_weight'], 3);
    $calc['grand_total'] = $calc['effective_gold'];
    $calc['remaining_balance'] = round($previousBalance + $calc['effective_gold'] - $totalReceivedKhalis, 3);

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
            $calc['rp_rate'], $calc['rp_amount'], $calc['rp_mazdori_weight'], 0, $calc['rp_mazdori_amount'],
            $calc['casting_mazdori_weight'], 0, $calc['casting_mazdori_amount'],
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
        if (post('action') === 'print_receipt') {
            redirect('invoices/print1.php?id=' . $invoiceId);
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
    <input type="hidden" name="wasooli" id="wasooli" value="0">
    <input type="hidden" name="rp_rate" id="rp_rate" value="0">
    <input type="hidden" name="waste_weight_override" id="waste_weight_override" value="">
    <input type="hidden" name="male_waste_override" id="male_waste_override" value="">

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
                            <div style="font-weight:600;font-size:1rem;color:var(--gold-primary);margin-bottom:8px;" id="selected-customer-name"></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;">
                                <span class="text-muted" style="font-size:0.8rem;">Previous Sabqa Balance: <span id="last-balance-display" class="mono" style="font-weight:600;color:var(--gold-bright);font-size:1.1rem;">0.000 g</span></span>
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
                        <select name="ratti" id="ratti" class="form-control" required onchange="updateRattiRate(); calculateLive();">
                            <?php for ($i = 6; $i <= 24; $i++): ?>
                                <option value="<?= $i ?>" <?= $i == 11 ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="formula-hint">Ratti impurity level (6 to 24)</div>
                    </div>
                    <div class="form-group">
                        <label>Ratti Rate <span class="font-urdu">رتی ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="ratti_rate" id="ratti_rate" class="form-control" step="0.001" value="0.100" required oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                        <div class="formula-hint">Auto-set by Ratti, but editable</div>
                    </div>
                    <div class="form-group">
                        <label>RP Mazdori Weight <span class="font-urdu">آر پی مزدوری وزن</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="rp_mazdori_weight" id="rp_mazdori_weight" class="form-control" step="0.001" value="0" oninput="calculateLive()">
                            <span class="unit-label">g</span>
                        </div>
                    </div>
                    <div class="form-group hidden-field">
                        <label>RP Mazdori Rate <span class="font-urdu">آر پی مزدوری ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="rp_mazdori_rate" id="rp_mazdori_rate" class="form-control" step="0.01" value="0" oninput="calculateLive()">
                            <span class="unit-label">Rs</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>RP Mazdori Amount <span class="font-urdu">آر پی مزدوری رقم</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="rp_mazdori_amount" id="rp_mazdori_amount" class="form-control" step="1" value="0" oninput="calculateLive()">
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
                    <div class="form-group hidden-field">
                        <label>Casting Mazdori Rate <span class="font-urdu">کاسٹنگ مزدوری ریٹ</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="casting_mazdori_rate" id="casting_mazdori_rate" class="form-control" step="0.01" value="0" oninput="calculateLive()">
                            <span class="unit-label">Rs</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Casting Mazdori Amount <span class="font-urdu">کاسٹنگ مزدوری رقم</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="casting_mazdori_amount" id="casting_mazdori_amount" class="form-control" step="0.01" value="0" oninput="calculateLive()">
                            <span class="unit-label">Rs</span>
                        </div>
                    </div>
                    <div class="form-group hidden-field">
                        <label>Wasooli <span class="font-urdu">وصولی</span></label>
                        <div class="calc-input-wrapper">
                            <input type="number" name="wasooli_visible" id="wasooli_visible" class="form-control" step="0.001" value="0" oninput="updateWasooli()">
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
                <button type="submit" name="action" value="print_receipt" class="btn btn-secondary"><i class="bi bi-receipt"></i> Save & Receipt</button>
                <a href="<?= url('invoices/index.php') ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
            </div>
        </div>

        <!-- Right Column: Live Calculation Panel -->
        <div class="live-panel" id="live-panel">
            <h4>
                <span><i class="bi bi-lightning-charge"></i> Live Calculation</span>
                <span class="font-urdu" style="font-size:0.85rem;color:var(--text-muted);">فوری حساب</span>
            </h4>

            <!-- Fraction Adjustment Panel -->
            <div class="adjustment-panel" id="adjustment-panel">
                <label class="adjustment-toggle">
                    <input type="checkbox" id="enable-adjustment" onchange="toggleAdjustment()">
                    <span>Enable Fraction Adjustment</span>
                    <span class="font-urdu" style="font-size:0.7rem;opacity:0.7;">کسر ایڈجسٹمنٹ</span>
                </label>
                <div class="adjustment-fields">
                    <div class="adj-control">
                        <label>Waste Weight Adj.</label>
                        <div class="adj-input-group">
                            <button type="button" class="adj-btn" onclick="adjustValue('waste-adjustment', -0.01)">−</button>
                            <input type="number" id="waste-adjustment" class="adj-input" step="0.001" value="0.000" oninput="calculateLive()">
                            <button type="button" class="adj-btn" onclick="adjustValue('waste-adjustment', 0.01)">+</button>
                        </div>
                    </div>
                    <div class="adj-control">
                        <label>Male Waste Adj.</label>
                        <div class="adj-input-group">
                            <button type="button" class="adj-btn" onclick="adjustValue('male-waste-adjustment', -0.01)">−</button>
                            <input type="number" id="male-waste-adjustment" class="adj-input" step="0.001" value="0.000" oninput="calculateLive()">
                            <button type="button" class="adj-btn" onclick="adjustValue('male-waste-adjustment', 0.01)">+</button>
                        </div>
                    </div>
                </div>
            </div>

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

            <!-- Balance Chain Steps -->
            <div style="margin-top:12px;padding:14px 18px;background:linear-gradient(135deg, #1a1a2e, #16213e);border:1px solid #4361ee;border-radius:12px;">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <span class="live-label" style="color:#b0c4ff;font-size:0.85rem;">
                        <i class="bi bi-plus-circle" style="color:#4361ee;"></i> + Previous Sabqa Balance
                    </span>
                    <span class="live-value" id="live-previous-balance" style="color:#4361ee;font-weight:700;font-size:1.3rem;">0.000 g</span>
                </div>
            </div>
            <div >
                <div style="margin-top:8px;padding:10px 18px;background:rgba(220,53,69,0.08);border:1px solid rgba(220,53,69,0.3);border-radius:12px;">
                    <div style="display:none;">
                        <span class="live-label" style="color:#f87171;font-size:0.8rem;">
                            <i class="bi bi-dash-circle" style="color:#dc3545;"></i> - Wasooli
                        </span>
                        <span class="live-value" id="live-wasooli-display" style="color:#f87171;font-size:1.1rem;">0.000 g</span>
                    </div>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span class="live-label" style="color:#f87171;font-size:0.8rem;">
                            <i class="bi bi-dash-circle" style="color:#dc3545;"></i> - Received Khalis
                        </span>
                        <span class="live-value" id="live-received-display" style="color:#f87171;font-size:1.1rem;">0.000 g</span>
                    </div>
                </div>
            </div>
            <div class="total-box box-remaining" id="remaining-box">
                <div class="total-label">= Remaining Balance (موجودہ بیلنس)</div>
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

// Custom rounding: threshold at 0.8 instead of 0.5
// 10.156 -> 10.15, 10.154 -> 10.15, 10.158 -> 10.16
function customRound2(val) {
    let scaled = Math.round(val * 1000); // Work with integers to avoid float issues
    let hundreds = Math.floor(scaled / 10); // Value * 100 truncated
    let remainder = scaled % 10; // Third decimal digit (0-9)
    if (remainder >= 8) {
        return (hundreds + 1) / 100;
    } else {
        return hundreds / 100;
    }
}

// Auto-set Ratti Rate based on Ratti value
function updateRattiRate() {
    const ratti = parseInt(document.getElementById('ratti').value);
    let rate = 0.100;
    if (ratti >= 6 && ratti <= 15) {
        rate = 0.100;
    } else if (ratti === 16) {
        rate = 0.110;
    } else if (ratti === 17) {
        rate = 0.120;
    } else if (ratti >= 18 && ratti <= 24) {
        rate = 0.150;
    }
    document.getElementById('ratti_rate').value = rate.toFixed(3);
}

// Toggle adjustment fields visibility
function toggleAdjustment() {
    const checkbox = document.getElementById('enable-adjustment');
    const panel = document.getElementById('adjustment-panel');
    if (checkbox.checked) {
        panel.classList.add('active');
    } else {
        panel.classList.remove('active');
        // Reset adjustments
        document.getElementById('waste-adjustment').value = '0.000';
        document.getElementById('male-waste-adjustment').value = '0.000';
    }
    calculateLive();
}

// Adjust value by delta (for +/- buttons)
function adjustValue(id, delta) {
    const input = document.getElementById(id);
    let val = parseFloat(input.value) || 0;
    val = Math.round((val + delta) * 1000) / 1000;
    input.value = val.toFixed(3);
    calculateLive();
}

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

function updateWasooli() {
    const val = parseFloat(document.getElementById('wasooli_visible').value) || 0;
    document.getElementById('wasooli').value = val;
    calculateLive();
}

function calculateLive() {
    const getVal = (id) => {
        const el = document.getElementById(id);
        return el ? parseFloat(el.value) || 0 : 0;
    };
    const castingWeight = getVal('casting_weight');
    const ratti = getVal('ratti');
    const rattiRate = getVal('ratti_rate');
    const rpRate = getVal('rp_rate');
    const rpMazdoriWeight = getVal('rp_mazdori_weight');
    const rpMazdoriAmount = getVal('rp_mazdori_amount');
    const castingMazdoriWeight = getVal('casting_mazdori_weight');
    const castingMazdoriAmount = getVal('casting_mazdori_amount');
    const wasooli = getVal('wasooli');
    
    // Get adjustments
    const adjustmentEnabled = document.getElementById('enable-adjustment').checked;
    const wasteAdjustment = adjustmentEnabled ? (parseFloat(document.getElementById('waste-adjustment').value) || 0) : 0;
    const maleWasteAdjustment = adjustmentEnabled ? (parseFloat(document.getElementById('male-waste-adjustment').value) || 0) : 0;
    
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
//                khalis = Math.round(khalis * 1000) / 1000;
                khalis = customRound2(khalis);
            }
//            display.value = khalis.toFixed(3);
            display.value = khalis.toFixed(3);
            totalReceivedKhalis += khalis;
        }
    });
    //totalReceivedKhalis = Math.round(totalReceivedKhalis * 1000) / 1000;
    totalReceivedKhalis = customRound2(totalReceivedKhalis);
    
    document.getElementById('total_received_khalis').value = totalReceivedKhalis;
    
    const previousBalance = parseFloat(document.getElementById('previous_balance').value) || 0;
    
    // 1. Waste Weight - apply custom rounding then add adjustment
    let wasteWeight = 0;
    if (castingWeight > 0 && rattiRate > 0) {
        wasteWeight = (castingWeight / 10) * rattiRate;
    }
    let roundedWaste = customRound2(wasteWeight);
    let finalWaste = roundedWaste + wasteAdjustment;
    if (finalWaste < 0) finalWaste = 0; // Clamp to 0
    
    // 2. Total Weight
    const totalWeight = Math.round((castingWeight + finalWaste) * 1000) / 1000;
    
    // 3. Male Waste - apply custom rounding then add adjustment
    let maleWaste = 0;
    if (totalWeight > 0 && ratti > 0) {
        maleWaste = (totalWeight / 96) * ratti;
    }
    let roundedMaleWaste = customRound2(maleWaste);
    let finalMaleWaste = roundedMaleWaste + maleWasteAdjustment;
    if (finalMaleWaste < 0) finalMaleWaste = 0; // Clamp to 0
    
    // 4. Gold Khalis
    let goldKhalis = Math.round((totalWeight - finalMaleWaste) * 1000) / 1000;
    if (goldKhalis < 0) goldKhalis = 0; // Clamp to 0
    
    // 5. RP Amount
    const rpAmount = Math.round(goldKhalis * rpRate * 100) / 100;
    
    // 6. Effective Gold
    const effectiveGold = Math.round((goldKhalis + rpMazdoriWeight + castingMazdoriWeight) * 1000) / 1000;
    
    // 7. Grand Total
    const grandTotal = effectiveGold;
    
    // 8. Remaining Balance
    const remainingBalance = Math.round((previousBalance + effectiveGold - totalReceivedKhalis) * 1000) / 1000;
    
    // Store override values in hidden fields
    document.getElementById('waste_weight_override').value = finalWaste.toFixed(3);
    document.getElementById('male_waste_override').value = finalMaleWaste.toFixed(3);
    
    // Update live panel
    document.getElementById('live-casting').textContent = castingWeight.toFixed(3) + ' g';
    document.getElementById('live-waste').textContent = finalWaste.toFixed(3) + ' g';
    document.getElementById('live-total').textContent = totalWeight.toFixed(3) + ' g';
    document.getElementById('live-male-waste').textContent = finalMaleWaste.toFixed(3) + ' g';
    document.getElementById('live-gold-khalis').textContent = goldKhalis.toFixed(3) + ' g';
    document.getElementById('live-rp-mazdori-wt').textContent = rpMazdoriWeight.toFixed(3) + ' g';
    document.getElementById('live-casting-mazdori-wt').textContent = castingMazdoriWeight.toFixed(3) + ' g';
    document.getElementById('live-effective').textContent = effectiveGold.toFixed(3) + ' g';
    document.getElementById('live-grand-total').textContent = grandTotal.toFixed(3) + ' g';
    document.getElementById('live-previous-balance').textContent = previousBalance.toFixed(3) + ' g';
    document.getElementById('live-wasooli-display').textContent = wasooli.toFixed(3) + ' g';
    document.getElementById('live-received-display').textContent = totalReceivedKhalis.toFixed(3) + ' g';
    
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
    
    fetch('<?= url('api/customer_balance.php?id=') ?>' + customerId, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
        .then(r => {
            if (!r.ok) throw new Error('Network error');
            return r.json();
        })
        .then(data => {
            if (data.error) throw new Error(data.error);
            document.getElementById('selected-customer-name').textContent = '👤 ' + data.customer_name;
            document.getElementById('last-balance-display').textContent = parseFloat(data.balance).toFixed(3) + ' g';
            document.getElementById('opening-balance-display').textContent = parseFloat(data.opening_balance).toFixed(3) + ' g';
            document.getElementById('previous_balance').value = data.balance;
            document.getElementById('customer-info').style.display = 'block';
            calculateLive();
        })
        .catch((err) => {
            console.error('Balance fetch failed:', err);
            document.getElementById('customer-info').style.display = 'none';
            document.getElementById('previous_balance').value = 0;
            calculateLive();
        });
}

// Auto-load customer balance if pre-selected
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer_id');
    if (customerSelect.value) {
        updateCustomerBalance(customerSelect.value);
    }
    // Set initial Ratti Rate based on default Ratti (11)
    updateRattiRate();
    calculateLive();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>