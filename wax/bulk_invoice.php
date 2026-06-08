<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Bulk Invoice Generator";
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
    * {
        box-sizing: border-box;
    }

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

    /* ========== SELECT2 DIRECT TYPING FIX ========== */
    /* Make Select2 search box always visible and focused */
    .select2-container--default .select2-search--dropdown .select2-search__field {
        padding: 10px 12px;
        font-size: 16px; /* Prevents iOS zoom */
        border: 2px solid var(--primary);
        border-radius: 6px;
        width: 100%;
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
        margin-top: 4px;
    }

    /* Larger dropdown for mobile */
    @media (max-width: 768px) {
        .select2-container--default .select2-results__option {
            padding: 14px 12px;
            font-size: 16px;
        }
        .select2-search--dropdown .select2-search__field {
            padding: 12px;
            font-size: 16px;
        }
    }

    /* ========== TOP SECTION ========== */
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
        font-size: 1.4rem;
        color: var(--dark);
    }

    .header-controls {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        align-items: center;
    }

    .date-picker {
        padding: 10px 14px;
        font-size: 16px;
        border: 2px solid var(--primary);
        border-radius: 8px;
        font-weight: 600;
        background: white;
    }

    /* ========== SHORTCUTS BAR ========== */
    .shortcuts-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 12px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 10px;
        margin-bottom: 15px;
        color: white;
    }

    .shortcut-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        background: rgba(255,255,255,0.15);
        padding: 6px 10px;
        border-radius: 6px;
    }

    .shortcut-item kbd {
        background: rgba(0,0,0,0.3);
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
    }

    /* ========== SUMMARY BAR ========== */
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
        .summary-bar {
            grid-template-columns: repeat(2, 1fr);
        }
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
        letter-spacing: 0.5px;
    }

    .summary-value {
        font-size: 1.5rem;
        font-weight: 700;
        margin-top: 4px;
    }

    /* ========== QUICK CUSTOMER BUTTONS ========== */
    .quick-customers {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        padding: 12px;
        background: #e3f2fd;
        border-radius: 10px;
        margin-bottom: 15px;
        align-items: center;
    }

    .quick-label {
        font-size: 12px;
        color: #1565c0;
        font-weight: 600;
    }

    .quick-btn {
        padding: 8px 16px;
        background: white;
        border: 2px solid #90caf9;
        border-radius: 25px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s;
        font-weight: 500;
    }

    .quick-btn:hover, .quick-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
    }

    /* ========== GRID CONTAINER ========== */
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
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    #bulkContainer {
        max-height: 55vh;
        overflow-y: auto;
        padding: 10px;
    }

    /* ========== ROW STYLES ========== */
    .entry-row {
        background: #fafafa;
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 12px;
        transition: all 0.2s;
    }

    .entry-row:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .entry-row.complete {
        background: #e8f5e9;
        border-color: var(--success);
    }

    .entry-row.duplicate-flash {
        animation: flashHighlight 0.6s ease;
    }

    @keyframes flashHighlight {
        0%, 100% { background: #fafafa; }
        50% { background: #fff9c4; }
    }

    /* ========== ROW HEADER (Number + Actions) ========== */
    .row-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px dashed var(--border);
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

    .row-actions {
        display: flex;
        gap: 8px;
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

    .action-btn:active {
        transform: scale(0.9);
    }

    .btn-copy { background: #e1bee7; color: #7b1fa2; }
    .btn-duplicate { background: #b3e5fc; color: #0277bd; }
    .btn-delete { background: #ffcdd2; color: #c62828; }

    /* ========== FORM FIELDS ========== */
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
        letter-spacing: 0.5px;
    }

    .field-input {
        width: 100%;
        padding: 12px;
        font-size: 16px; /* Prevents iOS zoom */
        border: 1px solid var(--border);
        border-radius: 8px;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: white;
    }

    .field-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 4px rgba(0,123,255,0.15);
    }

    /* Select2 Custom Styling */
    .entry-row .select2-container {
        width: 100% !important;
    }

    .entry-row .select2-selection--single {
        height: 48px !important;
        padding: 8px 12px;
        border: 1px solid var(--border) !important;
        border-radius: 8px !important;
        font-size: 16px;
    }

    .entry-row .select2-selection__rendered {
        line-height: 30px !important;
        padding-left: 0 !important;
    }

    .entry-row .select2-selection__arrow {
        height: 46px !important;
    }

    .entry-row .select2-selection__placeholder {
        color: #999;
    }

    /* Category indicators */
    .entry-row.cat-wax .select2-selection--single {
        border-left: 4px solid var(--warning) !important;
    }

    .entry-row.cat-design .select2-selection--single {
        border-left: 4px solid var(--info) !important;
    }

    /* Amount field */
    .amount-field {
        background: var(--light) !important;
        font-weight: 700;
        font-size: 18px !important;
        text-align: right;
        color: var(--success);
    }

    /* Worker disabled state */
    .worker-disabled .select2-selection--single {
        background: #f5f5f5 !important;
        cursor: not-allowed;
    }

    /* ========== RESPONSIVE GRID LAYOUTS ========== */
    
    /* Mobile (< 576px) - Stack all fields */
    @media (max-width: 575.98px) {
        .fields-grid {
            grid-template-columns: 1fr;
        }
        
        .qty-rate-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
    }

    /* Tablet Portrait (576px - 767px) */
    @media (min-width: 576px) and (max-width: 767.98px) {
        .fields-grid {
            grid-template-columns: 1fr 1fr;
        }
        
        .field-customer, .field-item {
            grid-column: span 2;
        }
    }

    /* Tablet Landscape (768px - 991px) */
    @media (min-width: 768px) and (max-width: 991.98px) {
        .fields-grid {
            grid-template-columns: repeat(3, 1fr);
        }
        
        .field-customer {
            grid-column: span 2;
        }
        
        .field-amount {
            grid-column: span 1;
        }
    }

    /* Desktop (992px+) - Inline Grid */
    @media (min-width: 992px) {
        .grid-header {
            display: grid;
            grid-template-columns: 50px 1fr 1fr 150px 90px 100px 120px 100px;
            gap: 10px;
            padding: 12px 15px;
        }

        .entry-row {
            padding: 8px 15px;
            margin-bottom: 4px;
            border-radius: 6px;
        }

        .row-header {
            display: none;
        }

        .fields-grid {
            grid-template-columns: 50px 1fr 1fr 150px 90px 100px 120px 100px;
            align-items: center;
        }

        .field-label {
            display: none;
        }

        .field-input,
        .entry-row .select2-selection--single {
            height: 40px !important;
            padding: 6px 10px;
            font-size: 14px;
        }

        .entry-row .select2-selection__rendered {
            line-height: 26px !important;
        }

        .entry-row .select2-selection__arrow {
            height: 38px !important;
        }

        .row-number-desktop {
            display: flex !important;
        }

        .actions-desktop {
            display: flex !important;
            gap: 4px;
        }

        .action-btn {
            width: 30px;
            height: 30px;
            font-size: 14px;
        }

        #bulkContainer {
            max-height: 50vh;
            padding: 5px;
        }
    }

    /* Large Desktop (1200px+) */
    @media (min-width: 1200px) {
        .grid-header {
            grid-template-columns: 50px 1.2fr 1.2fr 180px 100px 110px 130px 110px;
        }

        .fields-grid {
            grid-template-columns: 50px 1.2fr 1.2fr 180px 100px 110px 130px 110px;
        }
    }

    /* Desktop-only elements */
    .row-number-desktop,
    .actions-desktop {
        display: none;
    }

    /* ========== FLOATING ACTIONS ========== */
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
        transition: transform 0.15s, box-shadow 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .fab:hover {
        transform: scale(1.1);
    }

    .fab:active {
        transform: scale(0.95);
    }

    .fab-primary { background: var(--primary); }
    .fab-success { background: var(--success); }
    .fab-warning { background: var(--warning); color: var(--dark); }
    .fab-info { background: var(--info); }

    /* FAB tooltip */
    .fab-wrap {
        position: relative;
    }

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

    .fab-wrap:hover::before {
        opacity: 1;
    }

    /* ========== EMPTY STATE ========== */
    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #999;
    }

    .empty-state-icon {
        font-size: 60px;
        margin-bottom: 15px;
    }

    .empty-state p {
        margin: 0;
        font-size: 14px;
    }

    /* ========== UTILITY ========== */
    .btn {
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity 0.2s;
    }

    .btn:hover {
        opacity: 0.9;
    }

    .btn-danger {
        background: var(--danger);
        color: white;
    }

    .btn-outline {
        background: white;
        border: 2px solid var(--border);
        color: var(--dark);
    }

    .spacer {
        height: 100px;
    }
</style>

<div class="page-header">
    <h2>⚡ Bulk Invoice Generator</h2>
    <div class="header-controls">
        <input type="date" id="invoiceDate" class="date-picker" value="<?= date('Y-m-d') ?>">
        <button type="button" class="btn btn-outline" onclick="clearAll()">🗑️ Clear</button>
        <a href="invoices.php" class="btn btn-danger">✕ Close</a>
    </div>
</div>

<div class="shortcuts-bar">
    <div class="shortcut-item"><kbd>Ctrl+D</kbd> Duplicate</div>
    <div class="shortcut-item"><kbd>Alt+N</kbd> New Row</div>
    <div class="shortcut-item"><kbd>Alt+W</kbd> Wax</div>
    <div class="shortcut-item"><kbd>Alt+A</kbd> Ring</div>
    <div class="shortcut-item"><kbd>Ctrl+S</kbd> Save</div>
    <div class="shortcut-item"><kbd>↑</kbd> Copy Customer</div>
</div>

<div class="quick-customers" id="quickCustomers">
    <span class="quick-label">⚡ Quick Select:</span>
    <!-- Populated by JS -->
</div>

<div class="summary-bar">
    <div class="summary-card">
        <div class="summary-label">Rows</div>
        <div class="summary-value" id="sumRows">0</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Quantity</div>
        <div class="summary-value" id="sumQty">0</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Amount</div>
        <div class="summary-value" id="sumAmount">₨0</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Customers</div>
        <div class="summary-value" id="sumCustomers">0</div>
    </div>
</div>

<form id="bulkForm" method="POST" action="bulk_invoice_save.php">
    <input type="hidden" name="date" id="formDate" value="<?= date('Y-m-d') ?>">

    <div class="grid-wrapper">
        <div class="grid-header">
            <div>#</div>
            <div>Customer</div>
            <div>Item</div>
            <div>Worker</div>
            <div>Qty</div>
            <div>Rate</div>
            <div>Amount</div>
            <div>Actions</div>
        </div>

        <div id="bulkContainer">
            <div class="empty-state" id="emptyState">
                <div class="empty-state-icon">📋</div>
                <p>No entries yet.<br>Press <strong>Alt+N</strong> or tap <strong>+</strong> to start</p>
            </div>
        </div>
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
    <div class="fab-wrap" data-tip="New Row (Alt+N)">
        <button type="button" class="fab fab-primary" onclick="addRow()">+</button>
    </div>
    <div class="fab-wrap" data-tip="Save (Ctrl+S)">
        <button type="button" class="fab fab-success" onclick="saveForm()">💾</button>
    </div>
</div>

<script>
// ========== DATA ==========
const customers = <?= json_encode($customers) ?>;
const workers = <?= json_encode($workers) ?>;
const items = <?= json_encode($items) ?>;
const priceList = <?= json_encode($price_list) ?>;

// ========== STATE ==========
let rowCounter = 0;
let lastCustomerId = null;
let recentCustomers = [];

// ========== PRESETS ==========
const PRESET_WAX = { itemId: 1, workerId: 1 };
const PRESET_RING = { itemId: 6 };

// ========== SELECT2 CONFIG - DIRECT TYPING ==========
const select2Config = {
    width: '100%',
    minimumResultsForSearch: 0, // Always show search
    dropdownAutoWidth: false,
    language: {
        searching: function() { return "Searching..."; },
        noResults: function() { return "No match found"; }
    }
};

// Customer-specific config
const customerSelect2Config = {
    ...select2Config,
    placeholder: 'Type to search customer...',
    allowClear: true,
    matcher: function(params, data) {
        // Custom matcher for better search
        if ($.trim(params.term) === '') return data;
        if (typeof data.text === 'undefined') return null;
        
        // Case-insensitive search from beginning or anywhere
        const term = params.term.toLowerCase();
        const text = data.text.toLowerCase();
        
        if (text.indexOf(term) > -1) return data;
        return null;
    }
};

// ========== BUILD OPTIONS HTML ==========
function getCustomerOptions() {
    let html = '<option value=""></option>';
    customers.forEach(c => {
        html += `<option value="${c.id}">${c.name}</option>`;
    });
    return html;
}

function getItemOptions(presetId = null) {
    let html = '<option value=""></option>';
    items.forEach(i => {
        const label = i.name ? `${i.name} - ${i.name_urdu}` : i.name_urdu;
        const selected = presetId == i.id ? 'selected' : '';
        html += `<option value="${i.id}" data-cat="${i.category || ''}" data-rate="${i.default_rate || ''}" ${selected}>${label}</option>`;
    });
    return html;
}

function getWorkerOptions(presetId = null) {
    let html = '<option value=""></option>';
    workers.forEach(w => {
        const selected = presetId == w.id ? 'selected' : '';
        html += `<option value="${w.id}" ${selected}>${w.name}</option>`;
    });
    return html;
}

// ========== ADD ROW ==========
function addRow(preset = null, copyData = null) {
    // Remove empty state
    document.getElementById('emptyState')?.remove();
    
    rowCounter++;
    const container = document.getElementById('bulkContainer');
    
    const row = document.createElement('div');
    row.className = 'entry-row';
    row.dataset.id = rowCounter;

    row.innerHTML = `
        <!-- Mobile Header -->
        <div class="row-header">
            <div class="row-number">${rowCounter}</div>
            <div class="row-actions">
                <button type="button" class="action-btn btn-copy" onclick="copyCustomerAbove(this)" title="Copy Customer ↑">↑</button>
                <button type="button" class="action-btn btn-duplicate" onclick="duplicateRow(this)" title="Duplicate">⧉</button>
                <button type="button" class="action-btn btn-delete" onclick="deleteRow(this)" title="Delete">✕</button>
            </div>
        </div>

        <div class="fields-grid">
            <!-- Desktop: Row Number -->
            <div class="row-number-desktop field-group">
                <div class="row-number">${rowCounter}</div>
            </div>

            <!-- Customer -->
            <div class="field-group field-customer">
                <label class="field-label">Customer</label>
                <select name="customer_ids[]" class="sel-customer" required>
                    ${getCustomerOptions()}
                </select>
            </div>

            <!-- Item -->
            <div class="field-group field-item">
                <label class="field-label">Item</label>
                <select name="item_ids[]" class="sel-item" required>
                    ${getItemOptions(preset?.itemId)}
                </select>
            </div>

            <!-- Worker -->
            <div class="field-group field-worker">
                <label class="field-label">Worker</label>
                <select name="worker_ids[]" class="sel-worker">
                    ${getWorkerOptions(preset?.workerId)}
                </select>
            </div>

            <!-- Qty & Rate (Mobile: side by side) -->
            <div class="field-group field-qty">
                <label class="field-label">Qty</label>
                <input type="number" name="qtys[]" class="field-input inp-qty" value="1" min="0.001" step="0.001" required>
            </div>

            <div class="field-group field-rate">
                <label class="field-label">Rate</label>
                <input type="number" name="rates[]" class="field-input inp-rate" step="0.01" placeholder="0.00" required>
            </div>

            <!-- Amount -->
            <div class="field-group field-amount">
                <label class="field-label">Amount</label>
                <input type="text" class="field-input amount-field inp-amount" readonly value="0">
            </div>

            <!-- Desktop: Actions -->
            <div class="actions-desktop field-group">
                <button type="button" class="action-btn btn-copy" onclick="copyCustomerAbove(this)" title="↑">↑</button>
                <button type="button" class="action-btn btn-duplicate" onclick="duplicateRow(this)" title="Dup">⧉</button>
                <button type="button" class="action-btn btn-delete" onclick="deleteRow(this)" title="Del">✕</button>
            </div>
        </div>
    `;

    container.appendChild(row);

    // Initialize Select2 with DIRECT TYPING enabled
    const $row = $(row);
    
    // Customer Select - Opens with search focused
    $row.find('.sel-customer').select2({
        ...customerSelect2Config,
        dropdownParent: $(document.body)
    }).on('select2:open', function() {
        // Auto-focus search input when opened
        setTimeout(() => {
            document.querySelector('.select2-container--open .select2-search__field')?.focus();
        }, 10);
    }).on('select2:select', function() {
        onCustomerSelect(this);
    });

    // Item Select
    $row.find('.sel-item').select2({
        ...select2Config,
        placeholder: 'Type to search item...',
        dropdownParent: $(document.body)
    }).on('select2:open', function() {
        setTimeout(() => {
            document.querySelector('.select2-container--open .select2-search__field')?.focus();
        }, 10);
    }).on('select2:select', function() {
        onItemSelect(this);
    });

    // Worker Select
    $row.find('.sel-worker').select2({
        ...select2Config,
        placeholder: 'Select worker...',
        dropdownParent: $(document.body)
    }).on('select2:open', function() {
        setTimeout(() => {
            document.querySelector('.select2-container--open .select2-search__field')?.focus();
        }, 10);
    });

    // Input events
    $row.find('.inp-qty, .inp-rate').on('input change', function() {
        calcAmount(this);
    });

    // Apply copied data
    if (copyData) {
        row.classList.add('duplicate-flash');
        $row.find('.sel-customer').val(copyData.customer).trigger('change');
        $row.find('.sel-item').val(copyData.item).trigger('change');
        $row.find('.sel-worker').val(copyData.worker).trigger('change');
        $row.find('.inp-qty').val(copyData.qty);
        $row.find('.inp-rate').val(copyData.rate);
        
        setTimeout(() => {
            onItemSelect($row.find('.sel-item')[0]);
            calcAmount($row.find('.inp-qty')[0]);
        }, 100);
    }
    // Apply preset
    else if (preset) {
        setTimeout(() => {
            onItemSelect($row.find('.sel-item')[0]);
            // Auto-fill last customer
            if (lastCustomerId) {
                $row.find('.sel-customer').val(lastCustomerId).trigger('change');
                setTimeout(() => updateRate(row), 50);
            }
        }, 50);
    }

    // Focus logic
    setTimeout(() => {
        if (copyData) {
            $row.find('.inp-qty').focus().select();
        } else if (preset && lastCustomerId) {
            $row.find('.inp-qty').focus().select();
        } else {
            $row.find('.sel-customer').select2('open');
        }
    }, 150);

    updateSummary();
    scrollToRow(row);
}

// ========== ROW OPERATIONS ==========
function duplicateRow(btn) {
    const row = btn.closest('.entry-row');
    const data = {
        customer: row.querySelector('.sel-customer').value,
        item: row.querySelector('.sel-item').value,
        worker: row.querySelector('.sel-worker').value,
        qty: row.querySelector('.inp-qty').value,
        rate: row.querySelector('.inp-rate').value
    };
    addRow(null, data);
}

function copyCustomerAbove(btn) {
    const currentRow = btn.closest('.entry-row');
    const rows = [...document.querySelectorAll('.entry-row')];
    const idx = rows.indexOf(currentRow);

    if (idx > 0) {
        const aboveCustomer = rows[idx - 1].querySelector('.sel-customer').value;
        if (aboveCustomer) {
            $(currentRow).find('.sel-customer').val(aboveCustomer).trigger('change');
            updateRate(currentRow);
            // Move focus to item
            $(currentRow).find('.sel-item').select2('open');
        }
    }
}

function deleteRow(btn) {
    const row = btn.closest('.entry-row');
    row.style.transition = 'all 0.25s';
    row.style.transform = 'translateX(100%)';
    row.style.opacity = '0';
    
    setTimeout(() => {
        row.remove();
        renumberRows();
        updateSummary();
        
        // Show empty state if no rows
        if (!document.querySelector('.entry-row')) {
            document.getElementById('bulkContainer').innerHTML = `
                <div class="empty-state" id="emptyState">
                    <div class="empty-state-icon">📋</div>
                    <p>No entries yet.<br>Press <strong>Alt+N</strong> or tap <strong>+</strong> to start</p>
                </div>
            `;
        }
    }, 250);
}

function renumberRows() {
    document.querySelectorAll('.entry-row').forEach((row, i) => {
        row.querySelectorAll('.row-number').forEach(el => {
            el.textContent = i + 1;
        });
    });
}

// ========== SELECT HANDLERS ==========
function onCustomerSelect(select) {
    const customerId = select.value;
    if (customerId) {
        lastCustomerId = customerId;
        addRecentCustomer(customerId);
    }
    updateRate(select.closest('.entry-row'));
    checkComplete(select.closest('.entry-row'));
}

function onItemSelect(select) {
    const row = select.closest('.entry-row');
    const opt = select.options[select.selectedIndex];
    const cat = opt?.dataset?.cat || '';
    const workerSel = row.querySelector('.sel-worker');
    const workerGroup = row.querySelector('.field-worker');

    // Remove category classes
    row.classList.remove('cat-wax', 'cat-design');

    if (cat === 'design') {
        workerSel.disabled = false;
        workerSel.required = true;
        workerGroup.classList.remove('worker-disabled');
        row.classList.add('cat-design');
        $(workerSel).prop('disabled', false);
    } else if (cat === 'wax') {
        workerSel.disabled = false;
        workerSel.required = false;
        workerGroup.classList.remove('worker-disabled');
        row.classList.add('cat-wax');
        $(workerSel).prop('disabled', false);
    } else {
        workerSel.disabled = true;
        workerSel.required = false;
        workerSel.value = '';
        workerGroup.classList.add('worker-disabled');
        $(workerSel).val(null).trigger('change').prop('disabled', true);
    }

    updateRate(row);
    checkComplete(row);
}

function updateRate(row) {
    const itemSel = row.querySelector('.sel-item');
    const custSel = row.querySelector('.sel-customer');
    const rateInp = row.querySelector('.inp-rate');

    if (!itemSel.value) return;

    const opt = itemSel.options[itemSel.selectedIndex];
    const cat = opt?.dataset?.cat || '';
    const defRate = opt?.dataset?.rate || '';
    const itemId = itemSel.value;
    const custId = custSel.value;

    // Auto-rate for wax
    if (cat === 'wax' && custId) {
        const key = `${custId}_${itemId}`;
        rateInp.value = priceList[key] || defRate || '';
    }

    calcAmount(rateInp);
}

function calcAmount(input) {
    const row = input.closest('.entry-row');
    const qty = parseFloat(row.querySelector('.inp-qty').value) || 0;
    const rate = parseFloat(row.querySelector('.inp-rate').value) || 0;
    const amount = qty * rate;
    row.querySelector('.inp-amount').value = amount.toLocaleString('en-PK', { maximumFractionDigits: 0 });
    updateSummary();
    checkComplete(row);
}

function checkComplete(row) {
    const cust = row.querySelector('.sel-customer').value;
    const item = row.querySelector('.sel-item').value;
    const qty = row.querySelector('.inp-qty').value;
    const rate = row.querySelector('.inp-rate').value;

    if (cust && item && qty && rate) {
        row.classList.add('complete');
    } else {
        row.classList.remove('complete');
    }
}

// ========== QUICK CUSTOMERS ==========
function addRecentCustomer(id) {
    recentCustomers = recentCustomers.filter(x => x != id);
    recentCustomers.unshift(id);
    if (recentCustomers.length > 6) recentCustomers.pop();
    renderRecentCustomers();
}

function renderRecentCustomers() {
    const container = document.getElementById('quickCustomers');
    container.innerHTML = '<span class="quick-label">⚡ Quick:</span>';

    recentCustomers.forEach(id => {
        const cust = customers.find(c => c.id == id);
        if (cust) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'quick-btn';
            btn.textContent = cust.name;
            btn.onclick = () => applyQuickCustomer(id);
            container.appendChild(btn);
        }
    });
}

function applyQuickCustomer(id) {
    // Find focused row or last row
    const focusedRow = document.querySelector('.entry-row:focus-within') 
                    || document.querySelector('.entry-row:last-child');
    
    if (focusedRow) {
        $(focusedRow).find('.sel-customer').val(id).trigger('change');
        updateRate(focusedRow);
        // Focus next logical field
        $(focusedRow).find('.sel-item').select2('open');
    }
}

// ========== SUMMARY ==========
function updateSummary() {
    const rows = document.querySelectorAll('.entry-row');
    let totalQty = 0;
    let totalAmount = 0;
    const custSet = new Set();

    rows.forEach(row => {
        totalQty += parseFloat(row.querySelector('.inp-qty').value) || 0;
        const amtStr = row.querySelector('.inp-amount').value.replace(/,/g, '');
        totalAmount += parseFloat(amtStr) || 0;
        const cust = row.querySelector('.sel-customer').value;
        if (cust) custSet.add(cust);
    });

    document.getElementById('sumRows').textContent = rows.length;
    document.getElementById('sumQty').textContent = totalQty.toFixed(1);
    document.getElementById('sumAmount').textContent = '₨' + totalAmount.toLocaleString('en-PK');
    document.getElementById('sumCustomers').textContent = custSet.size;
}

// ========== UTILITIES ==========
function scrollToRow(row) {
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearAll() {
    if (!confirm('Clear all entries?')) return;
    
    document.getElementById('bulkContainer').innerHTML = `
        <div class="empty-state" id="emptyState">
            <div class="empty-state-icon">📋</div>
            <p>No entries yet.<br>Press <strong>Alt+N</strong> or tap <strong>+</strong> to start</p>
        </div>
    `;
    rowCounter = 0;
    updateSummary();
}

function saveForm() {
    const rows = document.querySelectorAll('.entry-row');
    
    if (rows.length === 0) {
        alert('Please add at least one entry.');
        return;
    }

    // Validation
    let valid = true;
    rows.forEach(row => {
        const cust = row.querySelector('.sel-customer').value;
        const item = row.querySelector('.sel-item').value;
        const qty = row.querySelector('.inp-qty').value;
        const rate = row.querySelector('.inp-rate').value;

        if (!cust || !item || !qty || !rate) {
            valid = false;
            row.style.background = '#fff3cd';
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    if (!valid) {
        alert('Please complete all highlighted entries.');
        return;
    }

    // Sync date
    document.getElementById('formDate').value = document.getElementById('invoiceDate').value;

    if (confirm(`Save ${rows.length} entries?`)) {
        document.getElementById('bulkForm').submit();
    }
}

// ========== KEYBOARD SHORTCUTS ==========
document.addEventListener('keydown', function(e) {
    // Ctrl+D: Duplicate
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        const row = document.querySelector('.entry-row:focus-within');
        if (row) duplicateRow(row.querySelector('.btn-duplicate'));
    }

    // Alt+N: New row
    if (e.altKey && e.key === 'n') {
        e.preventDefault();
        addRow();
    }

    // Alt+W: Wax
    if (e.altKey && e.key === 'w') {
        e.preventDefault();
        addRow(PRESET_WAX);
    }

    // Alt+A: Ring
    if (e.altKey && e.key === 'a') {
        e.preventDefault();
        addRow(PRESET_RING);
    }

    // Ctrl+S: Save
    if (e.ctrlKey && e.key === 's') {
        e.preventDefault();
        saveForm();
    }

    // Tab/Enter on rate: Add new row
    if ((e.key === 'Tab' || e.key === 'Enter') && e.target.classList.contains('inp-rate')) {
        const rows = document.querySelectorAll('.entry-row');
        const lastRow = rows[rows.length - 1];
        if (e.target.closest('.entry-row') === lastRow && !e.shiftKey) {
            e.preventDefault();
            addRow();
        }
    }

    // Arrow Up in Select2 search: Copy customer from above
    if (e.key === 'ArrowUp') {
        const searchField = document.querySelector('.select2-container--open .select2-search__field');
        if (searchField && searchField.value === '') {
            // Check if it's customer dropdown
            const openSelect2 = document.querySelector('.select2-container--open');
            if (openSelect2) {
                const relatedSelect = $(openSelect2).prev('select');
                if (relatedSelect.hasClass('sel-customer')) {
                    e.preventDefault();
                    relatedSelect.select2('close');
                    const row = relatedSelect.closest('.entry-row')[0];
                    copyCustomerAbove(row.querySelector('.btn-copy'));
                }
            }
        }
    }
});

// ========== INIT ==========
$(document).ready(function() {
    // Start with 2 empty rows
    addRow();
    addRow();
});
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>