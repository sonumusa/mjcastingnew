<?php
require_once __DIR__ . '/bootstrap.php';

require_login();

$page_title = "New Invoice";
$pdo = getDB();

$customers = $pdo->query("SELECT id, name FROM wax_customers WHERE active = 1 ORDER BY name ASC")->fetchAll();
$workers = $pdo->query("SELECT id, name FROM wax_workers WHERE active = 1 ORDER BY name ASC")->fetchAll();
$items = $pdo->query("SELECT id, name, name_urdu, category, default_rate FROM wax_items WHERE active = 1 ORDER BY name_urdu ASC")->fetchAll();

$price_list_raw = $pdo->query("SELECT customer_id, item_id, rate FROM wax_price_list")->fetchAll();
$price_list = [];
foreach ($price_list_raw as $row) {
    $price_list[$row['customer_id'] . '_' . $row['item_id']] = $row['rate'];
}

include __DIR__ . '/templates/header.php';
?>

<style>
    * { box-sizing: border-box; }

    :root {
        --primary: #007bff;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
        --dark: #343a40;
        --light: #f8f9fa;
        --border: #dee2e6;
        --shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .select2-container--default .select2-search--dropdown .select2-search__field {
        padding: 10px 12px;
        font-size: 16px;
        border: 2px solid var(--primary);
        border-radius: 6px;
    }

    .select2-container--default .select2-results__option {
        padding: 10px 12px;
        font-size: 14px;
    }

    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: var(--primary);
    }

    .select2-dropdown {
        border: 1px solid var(--border);
        border-radius: 8px;
        box-shadow: var(--shadow);
    }

    @media (max-width: 768px) {
        .select2-container--default .select2-results__option {
            padding: 14px 12px;
            font-size: 16px;
        }
    }

    .page-header {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: var(--shadow);
        margin-bottom: 15px;
    }

    .page-header h2 {
        margin: 0;
        font-size: 1.3rem;
        color: var(--dark);
    }

    .header-controls {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .info-card {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        padding: 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        margin-bottom: 15px;
        color: white;
    }

    .info-field {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .info-label {
        font-size: 11px;
        text-transform: uppercase;
        opacity: 0.85;
    }

    .info-field input,
    .info-field select {
        padding: 10px 12px;
        font-size: 16px;
        border: none;
        border-radius: 8px;
        background: rgba(255,255,255,0.95);
        color: var(--dark);
    }

    .info-field .select2-selection--single {
        height: 44px !important;
        border: none !important;
        border-radius: 8px !important;
        background: rgba(255,255,255,0.95) !important;
    }

    .info-field .select2-selection__rendered {
        line-height: 42px !important;
        padding-left: 12px !important;
    }

    .info-field .select2-selection__arrow {
        height: 42px !important;
    }

    .shortcuts-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px;
        background: #e3f2fd;
        border: 1px solid #90caf9;
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .shortcut-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        color: #1565c0;
    }

    .shortcut-item kbd {
        background: var(--dark);
        color: white;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
    }

    .quick-customers {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px;
        background: #fff3e0;
        border: 1px solid #ffcc80;
        border-radius: 10px;
        margin-bottom: 15px;
        align-items: center;
    }

    .quick-label {
        font-size: 12px;
        color: #e65100;
        font-weight: 600;
    }

    .quick-btn {
        padding: 8px 16px;
        background: white;
        border: 2px solid #ffcc80;
        border-radius: 25px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .quick-btn:hover, .quick-btn.active {
        background: var(--warning);
        border-color: var(--warning);
    }

    .summary-bar {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
        padding: 15px;
        background: var(--dark);
        border-radius: 10px;
        margin-bottom: 15px;
        color: white;
    }

    @media (max-width: 576px) {
        .summary-bar { grid-template-columns: repeat(2, 1fr); }
    }

    .summary-card {
        text-align: center;
        padding: 10px;
        background: rgba(255,255,255,0.1);
        border-radius: 8px;
    }

    .summary-label {
        font-size: 10px;
        text-transform: uppercase;
        opacity: 0.8;
    }

    .summary-value {
        font-size: 1.4rem;
        font-weight: 700;
        margin-top: 4px;
    }

    .grid-wrapper {
        background: white;
        border-radius: 10px;
        box-shadow: var(--shadow);
        overflow: hidden;
    }

    .grid-header {
        display: none;
        background: var(--dark);
        color: white;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
    }

    #itemsContainer {
        max-height: 55vh;
        overflow-y: auto;
        padding: 10px;
    }

    .item-row {
        background: #fafafa;
        border: 2px solid var(--border);
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .item-row:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .item-row.complete {
        background: #e8f5e9;
        border-color: var(--success);
    }

    .item-row.dual-mode {
        background: linear-gradient(135deg, #fff8e1 0%, #e3f2fd 100%);
        border-color: var(--warning);
        border-width: 2px;
    }

    .item-row.duplicate-flash {
        animation: flash 0.5s ease;
    }

    @keyframes flash {
        50% { background: #fff9c4; }
    }

    .row-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px dashed var(--border);
    }

    .row-left {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .row-number {
        font-size: 14px;
        font-weight: 700;
        color: var(--primary);
        background: #e3f2fd;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }

    .dual-toggle {
        display: inline-flex !important;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #666;
        cursor: pointer;
        padding: 8px 14px;
        background: white;
        border: 2px solid #ddd;
        border-radius: 25px;
        transition: all 0.2s;
        user-select: none;
        font-weight: 500;
    }

    .dual-toggle:hover {
        background: #fff8e1;
        border-color: var(--warning);
    }

    .dual-toggle.active {
        background: var(--warning);
        color: var(--dark);
        border-color: var(--warning);
        font-weight: 600;
    }

    .dual-toggle input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: var(--dark);
    }

    .dual-toggle-icon {
        font-size: 16px;
    }

    .row-actions {
        display: flex;
        gap: 6px;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.1s;
    }

    .action-btn:active { transform: scale(0.9); }
    .btn-duplicate { background: #b3e5fc; color: #0277bd; }
    .btn-delete { background: #ffcdd2; color: #c62828; }

    .wax-section {
        display: none;
        padding: 15px;
        background: rgba(255, 193, 7, 0.15);
        border: 2px dashed var(--warning);
        border-radius: 10px;
        margin-bottom: 15px;
    }

    .item-row.dual-mode .wax-section {
        display: block;
    }

    .wax-section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #f57c00;
        margin-bottom: 12px;
    }

    .wax-fields-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    @media (max-width: 576px) {
        .wax-fields-grid { grid-template-columns: 1fr; }
        .wax-fields-grid .wax-qty-rate {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            grid-column: 1;
        }
    }

    .design-section-header {
        display: none;
        align-items: center;
        gap: 8px;
        font-size: 14px;
        font-weight: 700;
        color: #1976d2;
        margin-bottom: 12px;
    }

    .item-row.dual-mode .design-section-header {
        display: flex;
    }

    .fields-grid {
        display: grid;
        gap: 12px;
    }

    .field-group {
        display: flex;
        flex-direction: column;
    }

    .field-label {
        font-size: 11px;
        font-weight: 600;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .field-input {
        width: 100%;
        padding: 12px;
        font-size: 16px;
        border: 1px solid var(--border);
        border-radius: 8px;
        transition: all 0.2s;
        background: white;
    }

    .field-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(0,123,255,0.15);
    }

    .item-row .select2-container { width: 100% !important; }

    .item-row .select2-selection--single {
        height: 48px !important;
        padding: 8px 12px;
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
    }

    .item-row .select2-selection__rendered {
        line-height: 30px !important;
        padding-left: 0 !important;
    }

    .item-row .select2-selection__arrow { height: 46px !important; }

    .item-row.cat-wax .item-select-wrap .select2-selection--single {
        border-left: 4px solid var(--warning) !important;
    }

    .item-row.cat-design .item-select-wrap .select2-selection--single {
        border-left: 4px solid var(--info) !important;
    }

    .amount-field {
        font-weight: 700;
        font-size: 18px !important;
        text-align: right;
        color: var(--success);
    }

    /* Amount field: locked (readonly) style */
    .amount-field.amount-locked {
        background: var(--light) !important;
        cursor: not-allowed;
        color: #666 !important;
    }

    /* Amount field: editable style for single mode */
    .amount-field.amount-editable {
        background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%) !important;
        border: 2px solid var(--success) !important;
        cursor: text;
        color: var(--success) !important;
    }

    .amount-field.amount-editable:focus {
        box-shadow: 0 0 0 4px rgba(40, 167, 69, 0.25);
        border-color: var(--success) !important;
    }

    .worker-disabled .select2-selection--single {
        background: #f5f5f5 !important;
    }

    @media (max-width: 575.98px) {
        .fields-grid { grid-template-columns: 1fr; }
        .qty-rate-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
    }

    @media (min-width: 576px) and (max-width: 991.98px) {
        .fields-grid { grid-template-columns: 1fr 1fr; }
    }

    @media (min-width: 992px) {
        .grid-header {
            display: grid;
            grid-template-columns: 60px 1.5fr 160px 90px 100px 110px 90px;
            gap: 8px;
            padding: 12px 15px;
        }

        .item-row {
            padding: 10px 15px;
            margin-bottom: 6px;
            border-radius: 8px;
        }

        .row-header { display: none; }

        .fields-grid {
            grid-template-columns: 60px 1.5fr 160px 90px 100px 110px 90px;
            align-items: center;
        }

        .field-label { display: none; }

        .field-input,
        .item-row .select2-selection--single {
            height: 40px !important;
            padding: 6px 10px;
            font-size: 14px;
        }

        .item-row .select2-selection__rendered { line-height: 26px !important; }
        .item-row .select2-selection__arrow { height: 38px !important; }

        .desktop-controls { display: flex !important; }
        .action-btn { width: 30px; height: 30px; font-size: 13px; }

        #itemsContainer { max-height: 50vh; padding: 5px; }

        .dual-toggle-desktop {
            display: inline-flex !important;
            padding: 4px 10px;
            font-size: 11px;
        }

        .dual-toggle-desktop input { width: 16px; height: 16px; }
    }

    .desktop-controls { display: none; }
    .dual-toggle-desktop { display: none; }

    .floating-actions {
        position: fixed;
        bottom: 20px;
        right: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        z-index: 1000;
    }

    .fab {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        transition: transform 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fab:hover { transform: scale(1.1); }
    .fab:active { transform: scale(0.95); }
    .fab-primary { background: var(--primary); }
    .fab-success { background: var(--success); }
    .fab-warning { background: var(--warning); color: var(--dark); }
    .fab-info { background: var(--info); }

    .fab-wrap { position: relative; }
    .fab-wrap::before {
        content: attr(data-tip);
        position: absolute;
        right: 65px;
        top: 50%;
        transform: translateY(-50%);
        background: var(--dark);
        color: white;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 12px;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s;
    }
    .fab-wrap:hover::before { opacity: 1; }

    .action-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        justify-content: flex-end;
        padding: 15px;
        background: var(--light);
        border-radius: 10px;
        margin-top: 15px;
    }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn:hover { opacity: 0.9; }
    .btn:active { transform: scale(0.98); }
    .btn-primary { background: var(--primary); color: white; }
    .btn-success { background: var(--success); color: white; }
    .btn-warning { background: var(--warning); color: var(--dark); }
    .btn-info { background: var(--info); color: white; }
    .btn-danger { background: var(--danger); color: white; }
    .btn-outline { background: white; border: 2px solid var(--border); color: var(--dark); }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #999;
    }
    .empty-state-icon { font-size: 60px; margin-bottom: 15px; }
    .spacer { height: 100px; }

    /* Mode indicator for amount field */
    .amount-mode-hint {
        font-size: 9px;
        text-align: right;
        color: #999;
        margin-top: 2px;
        display: none;
    }
    .amount-editable-hint .amount-mode-hint {
        display: block;
        color: var(--success);
    }
</style>

<div class="page-header">
    <h2>📝 New Invoice</h2>
    <div class="header-controls">
        <a href="invoices.php" class="btn btn-outline">📋 All Invoices</a>
        <a href="invoices.php" class="btn btn-danger">✕ Cancel</a>
    </div>
</div>

<form id="invoiceForm" method="POST" action="invoice_save.php">
    <div class="info-card">
        <div class="info-field">
            <label class="info-label">Invoice Date</label>
            <input type="date" name="invoice_date" id="invoiceDate" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="info-field">
            <label class="info-label">Customer *</label>
            <select name="customer_id" id="customerId" class="customer-select" required>
                <option value="">-- Select Customer --</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="shortcuts-bar">
        <div class="shortcut-item"><kbd>Ctrl+D</kbd> Duplicate</div>
        <div class="shortcut-item"><kbd>Alt+N</kbd> New Row</div>
        <div class="shortcut-item"><kbd>Alt+W</kbd> Wax</div>
        <div class="shortcut-item"><kbd>Alt+A</kbd> Ring</div>
        <div class="shortcut-item"><kbd>Ctrl+S</kbd> Save</div>
    </div>

    <div class="quick-customers" id="quickCustomers">
        <span class="quick-label">⚡ Quick:</span>
        <?php foreach (array_slice($customers, 0, 6) as $c): ?>
            <button type="button" class="quick-btn" onclick="selectCustomer(<?= $c['id'] ?>)">
                <?= htmlspecialchars($c['name']) ?>
            </button>
        <?php endforeach; ?>
    </div>

    <div class="summary-bar">
        <div class="summary-card">
            <div class="summary-label">Items</div>
            <div class="summary-value" id="sumItems">0</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Quantity</div>
            <div class="summary-value" id="sumQty">0</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Total</div>
            <div class="summary-value" id="sumAmount">₨0</div>
        </div>
        <div class="summary-card">
            <div class="summary-label">Avg Rate</div>
            <div class="summary-value" id="sumAvgRate">₨0</div>
        </div>
    </div>

    <div class="grid-wrapper">
        <div class="grid-header">
            <div>Dual</div>
            <div>Item</div>
            <div>Worker</div>
            <div>Qty</div>
            <div>Rate</div>
            <div>Amount</div>
            <div>Actions</div>
        </div>

        <div id="itemsContainer">
            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">📋</div>
                <p>No items yet. Click + to add.</p>
            </div>
        </div>
    </div>

    <div class="action-bar">
        <button type="button" class="btn btn-warning" onclick="addRow(PRESET_WAX)">🔶 Wax</button>
        <button type="button" class="btn btn-info" onclick="addRow(PRESET_RING)">💍 Ring</button>
        <button type="button" class="btn btn-primary" onclick="addRow()">➕ Add Item</button>
        <button type="button" class="btn btn-success" onclick="saveForm()">💾 Save Invoice</button>
    </div>
</form>

<div class="spacer"></div>

<div class="floating-actions">
    <div class="fab-wrap" data-tip="Wax (Alt+W)">
        <button type="button" class="fab fab-warning" onclick="addRow(PRESET_WAX)">W</button>
    </div>
    <div class="fab-wrap" data-tip="Ring (Alt+A)">
        <button type="button" class="fab fab-info" onclick="addRow(PRESET_RING)">R</button>
    </div>
    <div class="fab-wrap" data-tip="New (Alt+N)">
        <button type="button" class="fab fab-primary" onclick="addRow()">+</button>
    </div>
    <div class="fab-wrap" data-tip="Save (Ctrl+S)">
        <button type="button" class="fab fab-success" onclick="saveForm()">💾</button>
    </div>
</div>

<script>
const customers = <?= json_encode($customers) ?>;
const workers = <?= json_encode($workers) ?>;
const items = <?= json_encode($items) ?>;
const priceList = <?= json_encode($price_list) ?>;

let rowCounter = 0;

const PRESET_WAX = { item_id: 1, worker_id: 5 };
const PRESET_RING = { item_id: 6 };

const select2Config = {
    width: '100%',
    minimumResultsForSearch: 0
};

function getItemOptions(selectedId = null) {
    let html = '<option value="">-- Select Item --</option>';
    items.forEach(i => {
        const label = i.name ? `${i.name} - ${i.name_urdu}` : i.name_urdu;
        const selected = selectedId == i.id ? 'selected' : '';
        html += `<option value="${i.id}" data-cat="${i.category || ''}" data-rate="${i.default_rate || ''}" ${selected}>${label}</option>`;
    });
    return html;
}

function getWorkerOptions(selectedId = null) {
    let html = '<option value="">-- Worker --</option>';
    workers.forEach(w => {
        const selected = selectedId == w.id ? 'selected' : '';
        html += `<option value="${w.id}" ${selected}>${w.name}</option>`;
    });
    return html;
}

function selectCustomer(id) {
    $('#customerId').val(id).trigger('change');
    document.querySelectorAll('.quick-btn').forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    updateAllRates();
}

/**
 * Get the current category of the selected item in a row
 */
function getRowCategory(row) {
    const itemSelect = row.querySelector('.item-select');
    const opt = itemSelect.options[itemSelect.selectedIndex];
    return opt?.dataset?.cat || '';
}

/**
 * Update the amount field's editable state based on dual mode.
 * - Single mode (non-dual): amount is ALWAYS EDITABLE
 * - Dual mode: amount is LOCKED (readonly, shows combined total)
 */
function updateAmountEditability(row) {
    const amountField = row.querySelector('.amount-display');
    const amountGroup = amountField.closest('.field-group');
    const isDual = row.classList.contains('dual-mode');

    // Single mode: always editable regardless of item category
    if (!isDual) {
        amountField.removeAttribute('readonly');
        amountField.classList.remove('amount-locked');
        amountField.classList.add('amount-editable');
        amountGroup.classList.add('amount-editable-hint');
    } else {
        // Dual mode: keep locked (combined wax+design total)
        amountField.setAttribute('readonly', true);
        amountField.classList.add('amount-locked');
        amountField.classList.remove('amount-editable');
        amountGroup.classList.remove('amount-editable-hint');
    }
}

function addRow(preset = null, copyData = null) {
    const customerId = document.getElementById('customerId').value;
    if (!customerId && !copyData) {
        alert('Please select a customer first!');
        $('#customerId').select2('open');
        return;
    }

    document.getElementById('emptyState')?.remove();
    rowCounter++;

    const container = document.getElementById('itemsContainer');
    const row = document.createElement('div');
    row.className = 'item-row';
    row.dataset.id = rowCounter;

    const itemId = copyData?.item_id || preset?.item_id || '';
    const workerId = copyData?.worker_id || preset?.worker_id || '';
    const qty = copyData?.qty || 1;
    const rate = copyData?.rate || '';

    // Copy wax data if duplicating a dual row
    const waxItemId = copyData?.wax_item_id || 1;
    const waxWorkerId = copyData?.wax_worker_id || 5;
    const waxQty = copyData?.wax_qty || '';
    const waxRate = copyData?.wax_rate || '';

    row.innerHTML = `
        <!-- HIDDEN FIELDS -->
        <input type="hidden" name="is_dual[]" class="is-dual-flag" value="0">
        <input type="hidden" name="wax_item_ids[]" class="wax-item-hidden" value="">
        <input type="hidden" name="wax_worker_ids[]" class="wax-worker-hidden" value="">
        <input type="hidden" name="wax_qtys[]" class="wax-qty-hidden" value="0">
        <input type="hidden" name="wax_rates[]" class="wax-rate-hidden" value="0">

        <!-- MOBILE: Row Header with Dual Toggle -->
        <div class="row-header">
            <div class="row-left">
                <div class="row-number">${rowCounter}</div>
                <label class="dual-toggle" title="Enable Wax + Design combined mode">
                    <input type="checkbox" class="dual-checkbox" onchange="toggleDualMode(this)">
                    <span class="dual-toggle-icon">🔶</span>
                    <span>Wax+Design</span>
                </label>
            </div>
            <div class="row-actions">
                <button type="button" class="action-btn btn-duplicate" onclick="duplicateRow(this)" title="Duplicate">⧉</button>
                <button type="button" class="action-btn btn-delete" onclick="deleteRow(this)" title="Delete">✕</button>
            </div>
        </div>

        <!-- WAX SECTION -->
        <div class="wax-section">
            <div class="wax-section-header">
                <span>🔶</span> WAX ITEM
            </div>
            <div class="wax-fields-grid">
                <div class="field-group">
                    <label class="field-label">Wax Item</label>
                    <select class="wax-item-select">${getItemOptions(waxItemId)}</select>
                </div>
                <div class="field-group">
                    <label class="field-label">Wax Worker</label>
                    <select class="wax-worker-select">${getWorkerOptions(waxWorkerId)}</select>
                </div>
                <div class="field-group">
                    <label class="field-label">Wax Qty</label>
                    <input type="number" class="field-input wax-qty" step="0.001" placeholder="0" value="${waxQty}">
                </div>
                <div class="field-group">
                    <label class="field-label">Wax Rate</label>
                    <input type="number" class="field-input wax-rate" step="0.0001" placeholder="Auto" value="${waxRate}">
                </div>
            </div>
        </div>

        <!-- Design Section Header -->
        <div class="design-section-header">
            <span>🔷</span> DESIGN ITEM
        </div>

        <!-- MAIN FIELDS -->
        <div class="fields-grid">
            <div class="field-group desktop-controls" style="justify-content: center;">
                <label class="dual-toggle dual-toggle-desktop" title="Wax+Design Mode">
                    <input type="checkbox" class="dual-checkbox-desktop" onchange="toggleDualMode(this)">
                    <span>🔶</span>
                </label>
            </div>

            <div class="field-group">
                <label class="field-label">Item</label>
                <div class="item-select-wrap">
                    <select name="items[]" class="item-select" required>${getItemOptions(itemId)}</select>
                </div>
            </div>

            <div class="field-group field-worker">
                <label class="field-label">Worker</label>
                <select name="workers[]" class="worker-select">${getWorkerOptions(workerId)}</select>
            </div>

            <div class="field-group">
                <label class="field-label">Qty</label>
                <input type="number" name="qtys[]" class="field-input qty-input" value="${qty}" min="0.001" step="0.001" required>
            </div>

            <div class="field-group">
                <label class="field-label">Rate</label>
                <input type="number" name="rates[]" class="field-input rate-input" value="${rate}" step="0.0001" placeholder="0.00" required>
            </div>

            <div class="field-group">
                <label class="field-label">Amount</label>
                <input type="text" class="field-input amount-field amount-display amount-locked" value="0" readonly>
                <div class="amount-mode-hint">✎ Editable in single mode</div>
            </div>

            <div class="field-group desktop-controls" style="flex-direction: row; gap: 4px;">
                <button type="button" class="action-btn btn-duplicate" onclick="duplicateRow(this)" title="Duplicate">⧉</button>
                <button type="button" class="action-btn btn-delete" onclick="deleteRow(this)" title="Delete">✕</button>
            </div>
        </div>
    `;

    container.appendChild(row);

    // Initialize Select2
    const $row = $(row);

    $row.find('.item-select').select2({
        ...select2Config,
        placeholder: 'Search item...',
        dropdownParent: $(document.body)
    }).on('select2:open', () => {
        setTimeout(() => document.querySelector('.select2-container--open .select2-search__field')?.focus(), 10);
    }).on('select2:select', function() {
        onItemSelect(this);
    });

    $row.find('.worker-select').select2({
        ...select2Config,
        placeholder: 'Worker...',
        dropdownParent: $(document.body)
    }).on('select2:open', () => {
        setTimeout(() => document.querySelector('.select2-container--open .select2-search__field')?.focus(), 10);
    });

    $row.find('.wax-item-select').select2({
        ...select2Config,
        placeholder: 'Wax item...',
        dropdownParent: $(document.body)
    }).on('select2:open', () => {
        setTimeout(() => document.querySelector('.select2-container--open .select2-search__field')?.focus(), 10);
    }).on('select2:select', function() {
        onWaxItemSelect(this);
    });

    $row.find('.wax-worker-select').select2({
        ...select2Config,
        placeholder: 'Worker...',
        dropdownParent: $(document.body)
    }).on('select2:open', () => {
        setTimeout(() => document.querySelector('.select2-container--open .select2-search__field')?.focus(), 10);
    });

    // Wax field events
    $row.find('.wax-qty, .wax-rate').on('input', function() {
        syncWaxHiddenFields(row);
        calculateRow(row);
    });

    // Qty and Rate input events - forward calculation (qty * rate = amount)
    $row.find('.qty-input').on('input', function() {
        calculateRowForward(row);
    });

    $row.find('.rate-input').on('input', function() {
        calculateRowForward(row);
    });

    // Amount input event - reverse calculation (amount / qty = rate)
    // Only active in single mode (dual mode amount is readonly)
$row.find('.amount-display').on('input change', function() {
    if (!row.classList.contains('dual-mode')) {
        calculateRowReverse(row);
    }
});  

    // Wax selects sync
    $row.find('.wax-item-select').on('change', function() {
        syncWaxHiddenFields(row);
    });

    $row.find('.wax-worker-select').on('change', function() {
        syncWaxHiddenFields(row);
    });

    // Sync dual checkboxes
    $row.find('.dual-checkbox, .dual-checkbox-desktop').on('change', function() {
        const isChecked = this.checked;
        row.querySelectorAll('.dual-checkbox, .dual-checkbox-desktop').forEach(cb => cb.checked = isChecked);
    });

    // Handle preset
    if (itemId) {
        setTimeout(() => onItemSelect($row.find('.item-select')[0], !copyData), 50);
    }

    // Focus
    if (copyData) {
        row.classList.add('duplicate-flash');
        setTimeout(() => $row.find('.qty-input').focus().select(), 100);
    } else if (preset) {
        setTimeout(() => $row.find('.qty-input').focus().select(), 100);
    } else {
        setTimeout(() => $row.find('.item-select').select2('open'), 100);
    }

    // Ensure amount editability is set after row is in DOM
    updateAmountEditability(row);
    calculateRow(row);
    updateSummary();
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function syncWaxHiddenFields(row) {
    const isDual = row.classList.contains('dual-mode');

    if (isDual) {
        row.querySelector('.wax-item-hidden').value   = row.querySelector('.wax-item-select').value || '';
        row.querySelector('.wax-worker-hidden').value  = row.querySelector('.wax-worker-select').value || '';
        row.querySelector('.wax-qty-hidden').value     = row.querySelector('.wax-qty').value || '0';
        row.querySelector('.wax-rate-hidden').value    = row.querySelector('.wax-rate').value || '0';
    } else {
        row.querySelector('.wax-item-hidden').value   = '';
        row.querySelector('.wax-worker-hidden').value  = '';
        row.querySelector('.wax-qty-hidden').value     = '0';
        row.querySelector('.wax-rate-hidden').value    = '0';
    }
}

function toggleDualMode(checkbox) {
    const row = checkbox.closest('.item-row');
    const isChecked = checkbox.checked;

    row.querySelectorAll('.dual-checkbox, .dual-checkbox-desktop').forEach(cb => cb.checked = isChecked);
    row.querySelector('.is-dual-flag').value = isChecked ? '1' : '0';

    row.querySelectorAll('.dual-toggle').forEach(toggle => {
        toggle.classList.toggle('active', isChecked);
    });

    if (isChecked) {
        row.classList.add('dual-mode');
        const waxItemSelect = row.querySelector('.wax-item-select');
        if (waxItemSelect.value) {
            onWaxItemSelect(waxItemSelect);
        }
    } else {
        row.classList.remove('dual-mode');
    }

    syncWaxHiddenFields(row);
    updateAmountEditability(row);
    calculateRow(row);
}

function duplicateRow(btn) {
    const row = btn.closest('.item-row');
    const isDual = row.classList.contains('dual-mode');

    const data = {
        item_id: row.querySelector('.item-select').value,
        worker_id: row.querySelector('.worker-select').value,
        qty: row.querySelector('.qty-input').value,
        rate: row.querySelector('.rate-input').value
    };

    if (isDual) {
        data.wax_item_id = row.querySelector('.wax-item-select').value;
        data.wax_worker_id = row.querySelector('.wax-worker-select').value;
        data.wax_qty = row.querySelector('.wax-qty').value;
        data.wax_rate = row.querySelector('.wax-rate').value;
    }

    addRow(null, data);

    if (isDual) {
        const rows = document.querySelectorAll('.item-row');
        const newRow = rows[rows.length - 1];

        newRow.querySelector('.dual-checkbox').checked = true;
        toggleDualMode(newRow.querySelector('.dual-checkbox'));

        $(newRow).find('.wax-item-select').val(data.wax_item_id).trigger('change');
        $(newRow).find('.wax-worker-select').val(data.wax_worker_id).trigger('change');
        newRow.querySelector('.wax-qty').value = data.wax_qty;
        newRow.querySelector('.wax-rate').value = data.wax_rate;

        syncWaxHiddenFields(newRow);
        calculateRow(newRow);
    }
}

function deleteRow(btn) {
    const row = btn.closest('.item-row');
    row.style.transition = 'all 0.25s';
    row.style.transform = 'translateX(100%)';
    row.style.opacity = '0';

    setTimeout(() => {
        row.remove();
        renumberRows();
        updateSummary();

        if (!document.querySelector('.item-row')) {
            document.getElementById('itemsContainer').innerHTML = `
                <div class="empty-state" id="emptyState">
                    <div class="empty-state-icon">📋</div>
                    <p>No items yet. Click + to add.</p>
                </div>
            `;
        }
    }, 250);
}

function renumberRows() {
    document.querySelectorAll('.item-row').forEach((row, i) => {
        row.querySelectorAll('.row-number').forEach(el => el.textContent = i + 1);
    });
}

function onItemSelect(select, autoRate = true) {
    const row = select.closest('.item-row');
    const opt = select.options[select.selectedIndex];
    const cat = opt?.dataset?.cat || '';
    const defRate = opt?.dataset?.rate || '';
    const workerSelect = row.querySelector('.worker-select');
    const workerGroup = row.querySelector('.field-worker');
    const isDual = row.classList.contains('dual-mode');

    row.classList.remove('cat-wax', 'cat-design');

    if (cat === 'design') {
        workerSelect.disabled = false;
        workerGroup.classList.remove('worker-disabled');
        row.classList.add('cat-design');
        $(workerSelect).prop('disabled', false);
    } else if (cat === 'wax') {
        // ONLY auto-set worker in DUAL mode, NOT in single mode
        if (isDual) {
            $(workerSelect).val('5').trigger('change');
        }
        workerGroup.classList.add('worker-disabled');
        row.classList.add('cat-wax');
    } else {
        workerSelect.disabled = true;
        workerGroup.classList.add('worker-disabled');
        $(workerSelect).val(null).trigger('change').prop('disabled', true);
    }

    if (autoRate) {
        const rateInput = row.querySelector('.rate-input');
        const customerId = document.getElementById('customerId').value;
        const itemId = select.value;

        if (cat === 'wax' && customerId) {
            const key = `${customerId}_${itemId}`;
            rateInput.value = priceList[key] || defRate || '';
        } else if (defRate && !rateInput.value) {
            rateInput.value = defRate;
        }
    }

    // Update amount editability whenever item changes
    updateAmountEditability(row);
    calculateRow(row);
    checkComplete(row);
}

function onWaxItemSelect(select) {
    const row = select.closest('.item-row');
    const isDual = row.classList.contains('dual-mode');
    const opt = select.options[select.selectedIndex];
    const defRate = opt?.dataset?.rate || '';
    const customerId = document.getElementById('customerId').value;
    const itemId = select.value;
    const waxRateInput = row.querySelector('.wax-rate');

    // ONLY auto-set wax worker in DUAL mode, NOT in single mode
    if (isDual) {
        $(row.querySelector('.wax-worker-select')).val('5').trigger('change');
    }

    if (customerId && itemId) {
        const key = `${customerId}_${itemId}`;
        waxRateInput.value = priceList[key] || defRate || '';
    }

    calculateRow(row);
}

function updateAllRates() {
    document.querySelectorAll('.item-row').forEach(row => {
        const itemSelect = row.querySelector('.item-select');
        if (itemSelect.value) onItemSelect(itemSelect, true);

        if (row.classList.contains('dual-mode')) {
            const waxSelect = row.querySelector('.wax-item-select');
            if (waxSelect.value) onWaxItemSelect(waxSelect);
        }
    });
}

/**
 * Forward calculation: qty * rate = amount
 * - Rate input accepts long decimals (e.g., 123.456789)
 * - Amount is rounded to 2 decimals for display/storage
 */
function calculateRowForward(row) {
    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const rateInput = row.querySelector('.rate-input');
    const rate = parseFloat(rateInput.value) || 0;
    
    let amount = qty * rate;

    if (row.classList.contains('dual-mode')) {
        const waxQty = parseFloat(row.querySelector('.wax-qty').value) || 0;
        const waxRate = parseFloat(row.querySelector('.wax-rate').value) || 0;
        amount += waxQty * waxRate;
    }

    const amountField = row.querySelector('.amount-display');
    // Round amount to 2 decimals for display
    const roundedAmount = Math.round(amount * 100) / 100;
    amountField.value = roundedAmount;
    amountField.dataset.calcSource = 'forward';
    
    updateSummary();
    checkComplete(row);
}

/**
 * Reverse calculation: amount / qty = rate
 * - Rate calculated with up to 4 decimal precision
 * - Works only in single mode (dual mode amount is readonly)
 */
function calculateRowReverse(row) {
    const amountStr = row.querySelector('.amount-display').value.replace(/,/g, '');
    const amount = parseFloat(amountStr) || 0;
    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const rateInput = row.querySelector('.rate-input');

    if (qty > 0 && amount >= 0) {
        const rate = amount / qty;
        // Round rate to 4 decimal places for precision
        const roundedRate = Math.round(rate * 10000) / 10000;
        rateInput.value = amountStr.trim() === '' ? '' : roundedRate;
    } else if (amountStr.trim() === '') {
        rateInput.value = '';
    }

    rateInput.dataset.calcSource = 'reverse';
    updateSummary();
    checkComplete(row);
}


/**
 * General calculateRow - used for initial load and wax changes
 * Always does forward calculation
 */
function calculateRow(row) {
    calculateRowForward(row);
}

function checkComplete(row) {
    const item = row.querySelector('.item-select').value;
    const qty = row.querySelector('.qty-input').value;
    const rate = row.querySelector('.rate-input').value;

    let complete = item && qty && rate;

    if (row.classList.contains('dual-mode')) {
        const waxItem = row.querySelector('.wax-item-select').value;
        const waxQty = row.querySelector('.wax-qty').value;
        const waxRate = row.querySelector('.wax-rate').value;
        complete = complete && waxItem && waxQty && waxRate;
    }

    row.classList.toggle('complete', !!complete);
}

function updateSummary() {
    const rows = document.querySelectorAll('.item-row');
    let totalQty = 0, totalAmount = 0;

    rows.forEach(row => {
        totalQty += parseFloat(row.querySelector('.qty-input').value) || 0;
        if (row.classList.contains('dual-mode')) {
            totalQty += parseFloat(row.querySelector('.wax-qty').value) || 0;
        }
        const amt = row.querySelector('.amount-display').value.replace(/,/g, '');
        totalAmount += parseFloat(amt) || 0;
    });

    const avgRate = totalQty > 0 ? totalAmount / totalQty : 0;

    document.getElementById('sumItems').textContent = rows.length;
    document.getElementById('sumQty').textContent = totalQty.toFixed(1);
    document.getElementById('sumAmount').textContent = '₨' + totalAmount.toLocaleString('en-PK', { maximumFractionDigits: 0 });
    document.getElementById('sumAvgRate').textContent = '₨' + avgRate.toFixed(0);
}

function prepareFormForSubmit() {
    document.querySelectorAll('.item-row').forEach(row => {
        const isDual = row.classList.contains('dual-mode');
        row.querySelector('.is-dual-flag').value = isDual ? '1' : '0';
        syncWaxHiddenFields(row);
    });
}

function saveForm() {
    const customerId = document.getElementById('customerId').value;
    if (!customerId) {
        alert('Please select a customer!');
        $('#customerId').select2('open');
        return;
    }

    const rows = document.querySelectorAll('.item-row');
    if (rows.length === 0) {
        alert('Please add at least one item.');
        return;
    }

    let valid = true;
    let firstInvalid = null;

    rows.forEach(row => {
        row.style.background = '';

        const item = row.querySelector('.item-select').value;
        const qty = row.querySelector('.qty-input').value;
        const rate = row.querySelector('.rate-input').value;

        let rowValid = item && qty && rate;

        if (row.classList.contains('dual-mode')) {
            const waxItem = row.querySelector('.wax-item-select').value;
            const waxQty = row.querySelector('.wax-qty').value;
            const waxRate = row.querySelector('.wax-rate').value;

            if (!waxItem || !waxQty || !waxRate) {
                rowValid = false;
            }
        }

        if (!rowValid) {
            valid = false;
            row.style.background = '#fff3cd';
            if (!firstInvalid) firstInvalid = row;
        }
    });

    if (!valid) {
        alert('Please complete all highlighted items (including wax fields in dual-mode rows).');
        if (firstInvalid) firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }

    prepareFormForSubmit();
    document.getElementById('invoiceForm').submit();
}

document.getElementById('invoiceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    saveForm();
});

// Keyboard Shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        const row = document.querySelector('.item-row:focus-within');
        if (row) duplicateRow(row.querySelector('.btn-duplicate'));
    }
    if (e.altKey && e.key === 'n') { e.preventDefault(); addRow(); }
    if (e.altKey && e.key === 'w') { e.preventDefault(); addRow(PRESET_WAX); }
    if (e.altKey && e.key === 'a') { e.preventDefault(); addRow(PRESET_RING); }
    if (e.ctrlKey && e.key === 's') { e.preventDefault(); saveForm(); }

    if ((e.key === 'Tab' || e.key === 'Enter') && e.target.classList.contains('rate-input')) {
        const rows = document.querySelectorAll('.item-row');
        const lastRow = rows[rows.length - 1];
        if (e.target.closest('.item-row') === lastRow && !e.shiftKey) {
            e.preventDefault();
            addRow();
        }
    }
});

// Init
$(document).ready(function() {
    $('.customer-select').select2({
        ...select2Config,
        placeholder: 'Search customer...',
        allowClear: true,
        dropdownParent: $(document.body)
    }).on('select2:open', () => {
        setTimeout(() => document.querySelector('.select2-container--open .select2-search__field')?.focus(), 10);
    }).on('select2:select', updateAllRates);
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>