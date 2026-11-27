<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$pageTitle = 'New Multiple Invoice';
$db = getDB();
ensureInvoiceMultipleDateColumns();
$customers = imLoadParties($db);
$partyJs = imPartyJs($customers);
$hasPartyType = (bool) imPartyTypeColumn($db);
$nextInvoiceNo = generateMultipleInvoiceNo();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    $customerId = (int) post('customer_id');
    $invoiceType = post('invoice_type', 'customer');
    $invoiceDate = post('invoice_date', date('Y-m-d'));
    $manualBookNo = post('manual_book_no');
    $remarks = post('remarks');
    $wasooli = parseDecimal(post('wasooli', 0));

    if (!$customerId) {
        setFlash('error', 'Please select a party.');
        back();
    }

    $items = post('items', []);
    $itemRows = [];
    $tot = ['casting' => 0, 'waste' => 0, 'total' => 0, 'male' => 0, 'khalis' => 0, 'rp_w' => 0, 'rp_amt' => 0, 'cast_w' => 0, 'cast_amt' => 0, 'effective' => 0];

    if (is_array($items)) {
        foreach ($items as $it) {
            $itemDate = !empty($it['item_date'] ?? '') ? $it['item_date'] : $invoiceDate;
            $casting = parseDecimal($it['casting_weight'] ?? 0);
            $ratti = parseDecimal($it['ratti'] ?? 0);
            $rate = parseDecimal($it['ratti_rate'] ?? 0);
            $rpW = parseDecimal($it['rp_mazdori_weight'] ?? 0);
            $rpAmt = parseDecimal($it['rp_mazdori_amount'] ?? 0);
            $castW = parseDecimal($it['casting_mazdori_weight'] ?? 0);
            $castAmt = parseDecimal($it['casting_mazdori_amount'] ?? 0);
            $wasteAdj = parseDecimal($it['waste_adjustment'] ?? 0);
            $maleAdj = parseDecimal($it['male_waste_adjustment'] ?? 0);
            $isFinish = !empty($it['is_finish']) ? 1 : 0;

            // Casting RETURN allowed: negative casting is a valid row.
            // Only completely empty, unchecked rows are skipped.
            if ($casting == 0 && $rpW == 0 && $rpAmt == 0 && $castW == 0 && $castAmt == 0 && $wasteAdj == 0 && $maleAdj == 0 && !$isFinish) {
                continue;
            }

            $calc = imCalcRow($casting, $ratti, $rate, $rpW, $castW, $wasteAdj, $maleAdj);

            $row = [
                'item_date' => $itemDate,
                'description' => $it['description'] ?? '',
                'is_finish' => $isFinish,
                'casting_weight' => $casting,
                'ratti' => $ratti,
                'ratti_rate' => $rate,
                'waste_weight' => $calc['waste_weight'],
                'total_weight' => $calc['total_weight'],
                'male_waste' => $calc['male_waste'],
                'gold_khalis' => $calc['gold_khalis'],
                'rp_mazdori_weight' => $rpW,
                'rp_mazdori_amount' => $rpAmt,
                'casting_mazdori_weight' => $castW,
                'casting_mazdori_amount' => $castAmt,
                'effective_gold' => $calc['effective_gold']
            ];

            $itemRows[] = $row;
            $tot['casting'] += $row['casting_weight'];
            $tot['waste'] += $row['waste_weight'];
            $tot['total'] += $row['total_weight'];
            $tot['male'] += $row['male_waste'];
            $tot['khalis'] += $row['gold_khalis'];
            $tot['rp_w'] += $row['rp_mazdori_weight'];
            $tot['rp_amt'] += $row['rp_mazdori_amount'];
            $tot['cast_w'] += $row['casting_mazdori_weight'];
            $tot['cast_amt'] += $row['casting_mazdori_amount'];
            $tot['effective'] += $row['effective_gold'];
        }
    }

    $receives = post('receives', []);
    $receiveRows = [];
    $totalReceived = 0;

    if (is_array($receives)) {
        foreach ($receives as $rec) {
            $receiveDate = !empty($rec['receive_date'] ?? '') ? $rec['receive_date'] : $invoiceDate;
            $gross = parseDecimal($rec['gross_weight'] ?? 0);
            $ratti = parseDecimal($rec['ratti_impurity'] ?? 0);
            $isFinish = !empty($rec['is_finish']) ? 1 : 0;
            if ($gross <= 0 && !$isFinish) continue;
            $khalis = convertToKhalis($gross, $ratti);
            $receiveRows[] = [
                'receive_date' => $receiveDate,
                'description' => $rec['description'] ?? '',
                'is_finish' => $isFinish,
                'gross_weight' => $gross,
                'ratti_impurity' => $ratti,
                'khalis_weight' => $khalis
            ];
            $totalReceived += $khalis;
        }
    }

    // Invoice can be saved with ONLY received rows (casting no longer compulsory).
    if (empty($itemRows) && empty($receiveRows)) {
        setFlash('error', 'Please add at least one Gold Calculation row or one Gold Received row.');
        back();
    }

    $totalReceived = round($totalReceived, 3);
    $previousBalance = getPreviousBalance($customerId);
    $effective = round($tot['effective'], 3);
    $remaining = round($previousBalance + $effective - $totalReceived - $wasooli, 3);

    try {
        $db->beginTransaction();
        $invoiceNo = generateMultipleInvoiceNo();

        $stmt = $db->prepare("INSERT INTO invoice_multiple (invoice_no,customer_id,invoice_type,invoice_date,manual_book_no,total_casting_weight,total_waste_weight,total_weight,total_male_waste,total_gold_khalis,total_received_khalis,total_rp_mazdori_weight,total_rp_mazdori_amount,total_casting_mazdori_weight,total_casting_mazdori_amount,effective_gold,grand_total,wasooli,previous_balance,remaining_balance,remarks,status,created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active',?)");
        $stmt->execute([
            $invoiceNo, $customerId, $invoiceType, $invoiceDate, $manualBookNo,
            round($tot['casting'], 3), round($tot['waste'], 3), round($tot['total'], 3),
            round($tot['male'], 3), round($tot['khalis'], 3), $totalReceived,
            round($tot['rp_w'], 3), round($tot['rp_amt'], 2), round($tot['cast_w'], 3),
            round($tot['cast_amt'], 2), $effective, $effective, $wasooli,
            $previousBalance, $remaining, $remarks, $_SESSION['user_id'] ?? null
        ]);

        $invoiceId = $db->lastInsertId();

        $ins = $db->prepare("INSERT INTO invoice_multiple_items (invoice_multiple_id,item_date,description,is_finish,casting_weight,ratti,ratti_rate,waste_weight,total_weight,male_waste,gold_khalis,rp_mazdori_weight,rp_mazdori_amount,casting_mazdori_weight,casting_mazdori_amount,effective_gold) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        foreach ($itemRows as $r) {
            $ins->execute([
                $invoiceId, $r['item_date'], $r['description'], $r['is_finish'], $r['casting_weight'],
                $r['ratti'], $r['ratti_rate'], $r['waste_weight'], $r['total_weight'],
                $r['male_waste'], $r['gold_khalis'], $r['rp_mazdori_weight'],
                $r['rp_mazdori_amount'], $r['casting_mazdori_weight'],
                $r['casting_mazdori_amount'], $r['effective_gold']
            ]);
        }

        $rin = $db->prepare("INSERT INTO invoice_multiple_receives (invoice_multiple_id,receive_date,description,is_finish,gross_weight,ratti_impurity,khalis_weight) VALUES (?,?,?,?,?,?,?)");
        foreach ($receiveRows as $r) {
            $rin->execute([
                $invoiceId, $r['receive_date'], $r['description'], $r['is_finish'],
                $r['gross_weight'], $r['ratti_impurity'], $r['khalis_weight']
            ]);
        }

        recalculateChain($customerId);
        recalculateInventoryStock();
        $db->commit();

        setFlash('success', "Multiple Invoice $invoiceNo created successfully.");
        if (post('action') === 'print') redirect('invoice-multiple/print.php?id=' . $invoiceId);
        if (post('action') === 'save_new') redirect('invoice-multiple/create.php');
        redirect('invoice-multiple/index.php');
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed to save multiple invoice: ' . $e->getMessage());
        back();
    }
}

$extraCss = '<style>
.invoice-grid { display: grid; grid-template-columns: 1fr; gap: 28px; align-items: start; }
.form-section { background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
.section-narrow { width: 50%; min-width: 360px; }
.section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 18px; color: var(--gold-primary); }
.input-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
.full-width { grid-column: span 3; }
.table-container { overflow-x: auto; width: 100%; }
.calc-table { min-width: 1650px; }
.calc-table input, .calc-table select { min-width: 85px; }
/* Hide number input up/down spinner arrows (not used) */
input[type=number]::-webkit-outer-spin-button,
input[type=number]::-webkit-inner-spin-button { -webkit-appearance: none; appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; appearance: textfield; }
.btn-add-row { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; background: rgba(16,185,129,.12); color: var(--success); border: 1px dashed var(--success); border-radius: 10px; cursor: pointer; font-weight: 600; font-size: .85rem; }
.btn-remove-row { width: 34px; height: 34px; background: rgba(244,63,94,.1); color: var(--error); border: 1px solid rgba(244,63,94,.2); border-radius: 8px; cursor: pointer; }
.live-panel { background: var(--bg-card); border: 1px solid var(--gold-primary); border-radius: 16px; padding: 22px; box-shadow: var(--shadow-3); }
.live-totals-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.stat-card { background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.06); border-radius: 12px; padding: 16px 8px; text-align: center; transition: all .2s ease; }
.stat-card:hover { background: rgba(255,255,255,.07); border-color: rgba(212,175,55,.25); transform: translateY(-2px); }
.stat-label { font-size: .65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; display: block; }
.stat-value { font-family: JetBrains Mono, monospace; font-weight: 700; font-size: .95rem; color: var(--gold-bright); display: block; }
.total-box { grid-column: span 4; margin-top: 8px; padding: 18px; border-radius: 12px; background: linear-gradient(135deg, #064e3b, #059669); color: #fff; text-align: center; }
.total-label { font-size: .75rem; text-transform: uppercase; letter-spacing: .1em; opacity: .9; margin-bottom: 6px; }
.total-value { font-size: 1.6rem; font-family: JetBrains Mono, monospace; font-weight: 700; }
.receive-row { display: grid; grid-template-columns: 44px 1fr 2fr 1fr 1fr 1fr 44px; gap: 12px; align-items: end; margin-bottom: 12px; padding: 14px; background: var(--bg-surface); border-radius: 12px; border: 1px solid var(--border-color); }
@media (max-width: 1100px) { .section-narrow { width: 100%; min-width: auto; } }
@media (max-width: 768px) { .receive-row { grid-template-columns: 1fr; } }
</style>';

$extraJs = '<script src="' . htmlspecialchars(url('assets/invoice-multiple-duplicate-check.js'), ENT_QUOTES, 'UTF-8') . '"></script>';
require_once __DIR__ . '/../includes/header.php';
?>

<form method="POST" id="multi-form">
    <?= csrfField() ?>
    <input type="hidden" name="wasooli" id="wasooli" value="0">

    <div class="invoice-grid">
        <div>
            <div class="page-header" style="margin-bottom:24px;">
                <div class="page-title-group">
                    <h1>New Invoice Multiple</h1>
                    <p class="font-urdu" style="margin-top:4px;">نیا ملٹی بل</p>
                </div>
            </div>

            <!-- Invoice Details - 50% LEFT -->
            <div class="form-section section-narrow">
                <div class="section-header">
                    <i class="bi bi-info-circle"></i>
                    <h3>Invoice Details</h3>
                </div>
                <div class="input-grid">
                    <div class="form-group full-width">
                        <label>Invoice Type</label>
                        <select name="invoice_type" id="invoice_type" class="form-control" onchange="filterParties()">
                            <option value="customer">Customer</option>
                            <option value="dukandar">Dukandar</option>
                            <option value="karigar" selected>Karigar</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Party</label>
                        <select name="customer_id" id="customer_id" class="form-control" required onchange="updateCustomerBalance(this.value)">
                            <option value="">Select Party</option>
                        </select>
                        <div id="customer-info" style="display:none;background:var(--bg-surface);border:1px solid var(--gold-muted);padding:12px 16px;border-radius:10px;margin-top:12px;">
                            <span class="text-muted">Previous Sabqa Balance: </span>
                            <span id="last-balance-display" class="mono" style="font-weight:700;color:var(--gold-bright);">0.000 g</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Book No</label>
                        <input type="text" name="manual_book_no" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Invoice No</label>
                        <input type="text" class="form-control" value="<?= $nextInvoiceNo ?>" disabled>
                    </div>
                </div>
            </div>

            <!-- Gold Calculation - FULL WIDTH -->
            <div class="form-section">
                <div class="section-header">
                    <i class="bi bi-calculator"></i>
                    <h3>Gold Calculation Rows</h3>
                </div>
                <div class="table-container">
                    <table class="calc-table">
                        <thead>
                            <tr>
                                <th>Is Finish</th>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Casting <small style="opacity:.65;font-weight:400;">(&minus; = return)</small></th>
                                <th>Ratti</th>
                                <th>Ratti Rate</th>
                                <th>Waste</th>
                                <th>Total</th>
                                <th>Male Waste</th>
                                <th>Waste Adj</th>
                                <th>Male Adj</th>
                                <th>Khalis</th>
                                <th>RP Wt</th>
                                <th>RP Amt</th>
                                <th>Casting Wt</th>
                                <th>Casting Amt</th>
                                <th>Effective</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="calc-rows"></tbody>
                    </table>
                </div>
                <button type="button" class="btn-add-row" onclick="addCalcRow()">
                    <i class="bi bi-plus-lg"></i> Add Gold Calculation Row
                </button>
            </div>

            <!-- Gold Received - 50% LEFT -->
            <div class="form-section section-narrow">
                <div class="section-header">
                    <i class="bi bi-box-arrow-in-down"></i>
                    <h3>Gold Received Rows (optional)</h3>
                </div>
                <div id="receives-container"></div>
                <button type="button" class="btn-add-row" onclick="addReceiveRow()">
                    <i class="bi bi-plus-lg"></i> Add Received Row
                </button>
            </div>

            <!-- Live Totals - 50% LEFT -->
            <div class="form-section section-narrow live-panel">
                <div class="section-header">
                    <i class="bi bi-calculator"></i>
                    <h3>Live Totals</h3>
                </div>
                <div class="live-totals-grid">
                    <div class="stat-card">
                        <span class="stat-label">Casting</span>
                        <span class="stat-value" id="t-casting">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Waste</span>
                        <span class="stat-value" id="t-waste">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Total Weight</span>
                        <span class="stat-value" id="t-total-weight">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Male Waste</span>
                        <span class="stat-value" id="t-male-waste">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Gold Khalis</span>
                        <span class="stat-value" id="t-khalis">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Effective Given</span>
                        <span class="stat-value" id="t-effective">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Received Khalis</span>
                        <span class="stat-value" id="t-received">0.000 g</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-label">Previous Balance</span>
                        <span class="stat-value" id="t-prev">0.000 g</span>
                    </div>
                    <div class="total-box">
                        <div class="total-label">Remaining Balance</div>
                        <div class="total-value" id="t-remaining">0.000 g</div>
                    </div>
                </div>
                <div class="live-row" style="display:none;">
                    <span>Wasooli</span>
                    <input type="number" id="wasooli_visible" class="form-control" step="0.001" value="0" style="width:120px" oninput="document.getElementById('wasooli').value=this.value;calcLive();">
                </div>
            </div>

            <!-- Remarks - 50% LEFT -->
            <div class="form-section section-narrow">
                <div class="section-header">
                    <i class="bi bi-chat-left-text"></i>
                    <h3>Remarks</h3>
                </div>
                <textarea name="remarks" class="form-control" rows="3"></textarea>
                <div style="display:flex;gap:10px;margin-top:20px;">
                    <button type="submit" class="btn btn-gold" name="action" value="save">
                        <i class="bi bi-save"></i> Save
                    </button>
                    <button type="submit" class="btn btn-primary" name="action" value="save_new">
                        <i class="bi bi-plus-circle"></i> Save & New
                    </button>
                    <button type="submit" class="btn btn-success" name="action" value="print">
                        <i class="bi bi-printer"></i> Save & Print
                    </button>
                    <a href="<?= url('invoice-multiple/index.php') ?>" class="btn btn-outline">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="duplicate-weight-modal" id="duplicate-weight-modal" hidden>
    <div class="duplicate-weight-dialog" role="dialog" aria-modal="true" aria-labelledby="duplicate-weight-title" aria-describedby="duplicate-weight-message">
        <h2 id="duplicate-weight-title">Duplicate weights found</h2>
        <p id="duplicate-weight-message"></p>
        <div class="duplicate-weight-actions">
            <button type="button" class="btn btn-outline" id="duplicate-weight-cancel">Cancel</button>
            <button type="button" class="btn btn-primary" id="duplicate-weight-continue">Process Anyway</button>
        </div>
    </div>
</div>

<script>
let calcRowCount = 0, recRowCount = 0, previousBalance = 0;
const allParties = <?= json_encode($partyJs) ?>;
const partyTypeColumnExists = <?= $hasPartyType ? 'true' : 'false' ?>;
const lockedPartyId = 0; // create page: nothing locked
const preselectParty = <?= json_encode((int) (query('customer_id') ?: 0)) ?>;

function customRound2(v) {
    // Sign safe: casting return (negative values) ko bhi theek round karta hai
    const sign = v < 0 ? -1 : 1;
    const a = Math.abs(v);
    let scaled = Math.round(a * 1000), hundreds = Math.floor(scaled / 10), rem = scaled % 10;
    return sign * ((rem >= 8 ? (hundreds + 1) : hundreds) / 100);
}

function fmt(v) {
    return (Math.round(v * 1000) / 1000).toFixed(3);
}

function esc(s) {
    return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
}

function getToday() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
}

function updateRattiRate(sel) {
    const row = sel.closest('tr');
    const r = parseFloat(sel.value) || 0;
    const isDukandar = document.getElementById('invoice_type').value === 'dukandar';
    const rateInput = row.querySelector('[name$="[ratti_rate]"]');
    let rate = .100;

    if (r >= 6 && r <= 15.9) {
        rate = .100;
    } else if (r >= 16 && r <= 16.9) {
        rate = .110;
    } else if (r >= 17 && r <= 17.9) {
        rate = .120;
    } else if (r >= 18 && r <= 18.9) {
        rate = .130;
    } else if (r >= 19 && r <= 19.9) {
        rate = .140;
    } else if (r >= 20 && r <= 20.9) {
        rate = .160;
    } else if (r >=21  && r <= 21.9) {
        rate = .170;
    } else if (r >=22  && r <= 22.9) {
        rate = .180;
    } else if (r >=23  && r <= 23.9) {
        rate = .200;
    } else if (r >=24  && r <= 24.9) {
        rate = .220;
    }
    

    rateInput.dataset.nonDukandarRate = rate.toFixed(3);
    rateInput.value = (isDukandar ? 0 : rate).toFixed(3);
    calcLive();
}

function updateRatesForInvoiceType() {
    const isDukandar = document.getElementById('invoice_type').value === 'dukandar';
    document.querySelectorAll('#calc-rows tr').forEach(row => {
        const rateInput = row.querySelector('[name$="[ratti_rate]"]');
        const wasDukandar = rateInput.dataset.isDukandar === 'true';
        if (isDukandar && !wasDukandar) {
            rateInput.dataset.nonDukandarRate = rateInput.value;
            rateInput.value = '0.000';
        } else if (!isDukandar && wasDukandar) {
            rateInput.value = rateInput.dataset.nonDukandarRate || rateInput.value;
        }
        rateInput.dataset.isDukandar = String(isDukandar);
    });
    calcLive();
}

function addCalcRow(data = {}) {
    const id = calcRowCount++;
    const isDukandar = document.getElementById('invoice_type').value === 'dukandar';
    const rate = data.ratti_rate || 0.100;
    const displayedRate = isDukandar ? 0 : rate;
    document.getElementById('calc-rows').insertAdjacentHTML('beforeend', `<tr id="calc-row-${id}">
        <td class="text-center"><input type="checkbox" name="items[${id}][is_finish]" value="1" aria-label="Is finish" ${Number(data.is_finish) === 1 ? 'checked' : ''}></td>
        <td><input class="form-control" type="date" name="items[${id}][item_date]" value="${data.item_date || getToday()}" oninput="calcLive()"></td>
        <td><input class="form-control" type="text" name="items[${id}][description]" value="${esc(data.description || '')}"></td>
        <td><input class="form-control" type="number" step="0.001" name="items[${id}][casting_weight]" value="${data.casting_weight || 0}" oninput="calcLive()"></td>
        <td><select class="form-control" name="items[${id}][ratti]" onchange="updateRattiRate(this)">${Array.from({length: 49}, (_, i) => 0 + (i * 0.5)).map(v => { const label = (Math.abs(v - Math.round(v)) < 0.001) ? String(Math.round(v)) : v.toFixed(1); return `<option value="${label}" ${Math.abs(v - (parseFloat(data.ratti || 11))) < 0.001 ? 'selected' : ''}>${label}</option>`; }).join('')}</select></td>
        <td><input class="form-control" type="number" step="0.001" name="items[${id}][ratti_rate]" value="${displayedRate}" data-non-dukandar-rate="${rate}" data-is-dukandar="${isDukandar}" oninput="calcLive()"></td>
        <td class="mono waste">0.000</td>
        <td class="mono total">0.000</td>
        <td class="mono male">0.000</td>
        <td><input class="form-control" type="number" step="0.001" name="items[${id}][waste_adjustment]" value="${data.waste_adjustment || 0}" oninput="calcLive()" title="Waste adjustment +/-"></td>
        <td><input class="form-control" type="number" step="0.001" name="items[${id}][male_waste_adjustment]" value="${data.male_waste_adjustment || 0}" oninput="calcLive()" title="Male waste adjustment +/-"></td>
        <td class="mono khalis">0.000</td>
        <td><input class="form-control" type="number" step="0.001" name="items[${id}][rp_mazdori_weight]" value="${data.rp_mazdori_weight || 0}" oninput="calcLive()"></td>
        <td><input class="form-control" type="number" step="1" name="items[${id}][rp_mazdori_amount]" value="${data.rp_mazdori_amount || 0}" oninput="syncRowRpWeight(this)"></td>
        <td><input class="form-control" type="number" step="0.001" name="items[${id}][casting_mazdori_weight]" value="${data.casting_mazdori_weight || 0}" oninput="calcLive()"></td>
        <td><input class="form-control" type="number" step="1" name="items[${id}][casting_mazdori_amount]" value="${data.casting_mazdori_amount || 0}" oninput="calcLive()"></td>
        <td class="mono effective" style="color:var(--gold-bright);font-weight:700;">0.000</td>
        <td><button type="button" class="btn-remove-row" onclick="this.closest('tr').remove();calcLive();"><i class="bi bi-trash"></i></button></td>
    </tr>`);
    calcLive();
}

function addReceiveRow(data = {}) {
    const id = recRowCount++;
    document.getElementById('receives-container').insertAdjacentHTML('beforeend', `<div class="receive-row" id="rec-row-${id}">
        <div class="form-group"><label style="font-size:.7rem;">Is Finish</label><input type="checkbox" name="receives[${id}][is_finish]" value="1" aria-label="Is finish (received gold)" ${Number(data.is_finish) === 1 ? 'checked' : ''}></div>
        <div class="form-group"><label style="font-size:.7rem;">Date</label><input type="date" name="receives[${id}][receive_date]" class="form-control" value="${data.receive_date || getToday()}"></div>
        <div class="form-group"><label style="font-size:.7rem;">Description</label><input type="text" name="receives[${id}][description]" class="form-control" value="${esc(data.description || '')}"></div>
        <div class="form-group"><label style="font-size:.7rem;">Gross Weight</label><input type="number" name="receives[${id}][gross_weight]" class="form-control" step="0.001" value="${data.gross_weight || 0}" oninput="calcLive()"></div>
        <div class="form-group"><label style="font-size:.7rem;">Ratti Impurity</label><input type="number" name="receives[${id}][ratti_impurity]" class="form-control" step="0.001" value="${data.ratti_impurity || 0}" oninput="calcLive()"></div>
        <div class="form-group"><label style="font-size:.7rem;">Khalis</label><input type="text" class="form-control rec-khalis" readonly value="0.000" style="color:var(--success);font-weight:600;"></div>
        <button type="button" class="btn-remove-row" onclick="this.closest('.receive-row').remove();calcLive();"><i class="bi bi-trash"></i></button>
    </div>`);
    calcLive();
}


/* Khalis = gross se khalis weight.
   ratti >= 0 -> purana formula: gross - (gross/96 * ratti)   [LESS]
   ratti <  0 -> "P Add": point = |ratti|, value = gross/(96-point)*point, khalis = gross + value  [ADD] */
function khalisFromGross(gross, ratti) {
    if (gross <= 0) return 0;
    if (ratti < 0) {
        const p = Math.abs(ratti), d = 96 - p;
        if (d <= 0) return gross;
        return gross + (gross / d) * p;
    }
    return gross - (gross / 96 * ratti);
}


/* ---------- RP Amount -> RP Weight (settings ke Default RP Rate se) ----------
   qty = amount * 11.664 / default_rp_rate
   Rate 0 ya set na ho to auto-calc OFF rehta hai (weight ko haath nahi lagta). */
const RP_TOLA_GRAMS = <?= json_encode(IM_TOLA_GRAMS) ?>;
const RP_DEFAULT_RATE = <?= json_encode(imDefaultRpRate()) ?>;

/* RP weight ko 2 decimal par laata hai.
   Teesra decimal >= 7 ho to upar, warna cut.
   0.2638 -> 0.26   |   0.267 -> 0.27 */
function rpRound2(v) {
    const sign = v < 0 ? -1 : 1;
    const scaled = Math.round(Math.abs(v) * 1000);
    const hundreds = Math.floor(scaled / 10);
    const rem = scaled % 10;
    return sign * ((rem >= 7 ? hundreds + 1 : hundreds) / 100);
}

function rpWeightFromAmount(amount) {
    if (!RP_DEFAULT_RATE || RP_DEFAULT_RATE <= 0) return null;
    amount = parseFloat(amount) || 0;
    return rpRound2(amount * RP_TOLA_GRAMS / RP_DEFAULT_RATE);
}

/* Row ke RP Amount se usi row ka RP Weight bhar deta hai */
function syncRowRpWeight(input) {
    const row = input.closest('tr');
    const w = rpWeightFromAmount(input.value);
    if (w !== null) {
        const wEl = row.querySelector('[name$="[rp_mazdori_weight]"]');
        if (wEl) wEl.value = w.toFixed(2);
    }
    calcLive();
}

function calcLive() {
    let totals = {casting: 0, waste: 0, totalWeight: 0, male: 0, khalis: 0, effective: 0, received: 0};

    document.querySelectorAll('#calc-rows tr').forEach(row => {
        const get = s => parseFloat(row.querySelector(s)?.value) || 0;
        const casting = get('[name$="[casting_weight]"]'), ratti = get('[name$="[ratti]"]'), rate = get('[name$="[ratti_rate]"]'), rp = get('[name$="[rp_mazdori_weight]"]'), cw = get('[name$="[casting_mazdori_weight]"]');
        // no max(0) clamps -> casting return (negative) allowed
        let waste = (casting !== 0 && rate !== 0) ? customRound2((casting / 10) * rate) : 0;
        const wasteAdj = get('[name$="[waste_adjustment]"]');
        waste = Math.round((waste + wasteAdj) * 1000) / 1000;
        let total = Math.round((casting + waste) * 1000) / 1000;
        let male = (total !== 0 && ratti !== 0) ? customRound2((total / 96) * ratti) : 0;
        const maleAdj = get('[name$="[male_waste_adjustment]"]');
        male = Math.round((male + maleAdj) * 1000) / 1000;
        let khalis = Math.round((total - male) * 1000) / 1000;
        let eff = Math.round((khalis + rp + cw) * 1000) / 1000;

        row.querySelector('.waste').textContent = fmt(waste);
        row.querySelector('.total').textContent = fmt(total);
        row.querySelector('.male').textContent = fmt(male);
        row.querySelector('.khalis').textContent = fmt(khalis);
        const effCell = row.querySelector('.effective');
        effCell.textContent = fmt(eff);
        effCell.style.color = (eff < 0) ? 'var(--error)' : 'var(--gold-bright)';

        totals.casting += casting;
        totals.waste += waste;
        totals.totalWeight += total;
        totals.male += male;
        totals.khalis += khalis;
        totals.effective += eff;
    });

    document.querySelectorAll('[id^="rec-row-"]').forEach(row => {
        const gross = parseFloat(row.querySelector('[name$="[gross_weight]"]').value) || 0,
              ratti = parseFloat(row.querySelector('[name$="[ratti_impurity]"]').value) || 0;
        let k = 0;
        if (gross > 0) k = customRound2(khalisFromGross(gross, ratti));
        row.querySelector('.rec-khalis').value = fmt(k);
        totals.received += k;
    });

    const wasooli = parseFloat(document.getElementById('wasooli').value) || 0;
    const remaining = previousBalance + totals.effective - totals.received - wasooli;

    document.getElementById('t-casting').textContent = fmt(totals.casting) + ' g';
    document.getElementById('t-waste').textContent = fmt(totals.waste) + ' g';
    document.getElementById('t-total-weight').textContent = fmt(totals.totalWeight) + ' g';
    document.getElementById('t-male-waste').textContent = fmt(totals.male) + ' g';
    document.getElementById('t-khalis').textContent = fmt(totals.khalis) + ' g';
    document.getElementById('t-effective').textContent = fmt(totals.effective) + ' g';
    document.getElementById('t-received').textContent = fmt(totals.received) + ' g';
    document.getElementById('t-prev').textContent = fmt(previousBalance) + ' g';
    document.getElementById('t-remaining').textContent = fmt(remaining) + ' g';
}


/* ---------- Party list filtering by Invoice Type ---------- */
function normType(t) {
    t = String(t || '').toLowerCase().trim();
    if (['dukandar', 'dukaandar', 'dukan', 'shopkeeper', 'shop', 'dealer'].indexOf(t) !== -1) return 'dukandar';
    if (['karigar', 'kareegar', 'kaarigar', 'worker', 'artisan', 'maker'].indexOf(t) !== -1) return 'karigar';
    if (['customer', 'client', 'party', 'grahak'].indexOf(t) !== -1) return 'customer';
    return t;
}

function filterParties(forceSelectId) {
    const sel = document.getElementById('customer_id');
    const typeSel = document.getElementById('invoice_type');
    if (!sel || !typeSel) return;

    const want = normType(typeSel.value);
    const keep = (forceSelectId !== undefined && forceSelectId !== null && forceSelectId !== '')
        ? String(forceSelectId) : String(sel.value || '');

    let list = allParties;
    if (partyTypeColumnExists) {
        list = allParties.filter(p => normType(p.type) === want || String(p.id) === String(lockedPartyId));
    }

    let html = '<option value="">Select Party</option>';
    list.forEach(p => {
        html += '<option value="' + p.id + '" data-opening="' + p.opening + '"' +
                (String(p.id) === keep ? ' selected' : '') + '>' + esc(p.name) + '</option>';
    });
    if (!list.length) {
        html += '<option value="" disabled>No ' + want + ' party found - set party type in Customers</option>';
    }
    sel.innerHTML = html;

    if (String(sel.value || '') !== keep) {
        updateCustomerBalance(sel.value);
    }
    updateRatesForInvoiceType();
}

function updateCustomerBalance(customerId) {
    if (!customerId) {
        previousBalance = 0;
        document.getElementById('customer-info').style.display = 'none';
        calcLive();
        return;
    }
    fetch('<?= url('api/customer_balance.php') ?>?customer_id=' + customerId)
        .then(r => r.json())
        .then(d => {
            previousBalance = parseFloat(d.current_balance ?? d.balance ?? d.opening_balance ?? 0) || 0;
            document.getElementById('last-balance-display').textContent = fmt(previousBalance) + ' g';
            document.getElementById('customer-info').style.display = 'block';
            calcLive();
        })
        .catch(() => {
            const opt = document.querySelector('#customer_id option:checked');
            previousBalance = parseFloat(opt?.dataset.opening || 0) || 0;
            document.getElementById('last-balance-display').textContent = fmt(previousBalance) + ' g';
            document.getElementById('customer-info').style.display = 'block';
            calcLive();
        });
}

document.addEventListener('wheel', function (e) {
    if (document.activeElement && document.activeElement.type === 'number' && document.activeElement === e.target) {
        document.activeElement.blur();
    }
}, { passive: true });

document.addEventListener('DOMContentLoaded', () => {
    filterParties(preselectParty || '');
    addCalcRow();
    const cid = document.getElementById('customer_id').value;
    if (cid) updateCustomerBalance(cid);
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>