<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Add Expenses";
$pdo = getDB();

$categories = $pdo->query("SELECT name FROM wax_expense_categories WHERE active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/templates/header.php';
?>

<style>
* { box-sizing: border-box; }

.expense-page {
    max-width: 1400px;
    margin: 0 auto;
    padding: 15px;
}

/* Header */
.top-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 10px;
}

.top-bar h1 {
    margin: 0;
    font-size: 1.4rem;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}

.top-bar h1 i { color: #dc2626; }

.top-actions {
    display: flex;
    gap: 8px;
    align-items: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
    white-space: nowrap;
}

.btn-light { background: #f1f5f9; color: #475569; }
.btn-light:hover { background: #e2e8f0; }

.btn-danger { background: #dc2626; color: white; }
.btn-danger:hover { background: #b91c1c; }

.btn-success { background: #16a34a; color: white; }
.btn-success:hover { background: #15803d; }

.btn-sm { padding: 6px 10px; font-size: 0.8rem; }

/* Stats Bar */
.stats-bar {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.stat-card {
    background: white;
    border-radius: 10px;
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    min-width: 140px;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
}

.stat-icon.date { background: #dbeafe; color: #2563eb; }
.stat-icon.count { background: #fef3c7; color: #d97706; }
.stat-icon.total { background: #fee2e2; color: #dc2626; }

.stat-info small { color: #64748b; font-size: 0.7rem; display: block; }
.stat-info strong { font-size: 1.1rem; color: #1e293b; }

/* Main Grid */
.grid-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    overflow: hidden;
}

.grid-header {
    display: grid;
    grid-template-columns: 40px 2fr 140px 2fr 60px;
    background: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.grid-header > div {
    padding: 12px 10px;
}

.grid-body {
    max-height: 55vh;
    overflow-y: auto;
}

.grid-row {
    display: grid;
    grid-template-columns: 40px 2fr 140px 2fr 60px;
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.15s;
}

.grid-row:hover { background: #fefce8; }
.grid-row:focus-within { background: #fef9c3; }
.grid-row.removing {
    opacity: 0;
    transform: translateX(30px);
    max-height: 0;
    padding: 0;
    border: none;
}

.grid-row > div {
    padding: 6px 8px;
    display: flex;
    align-items: center;
}

.row-num {
    justify-content: center;
    font-weight: 600;
    color: #94a3b8;
    font-size: 0.85rem;
}

.grid-input {
    width: 100%;
    border: 1px solid transparent;
    background: transparent;
    padding: 8px 10px;
    font-size: 0.9rem;
    border-radius: 4px;
    transition: all 0.15s;
}

.grid-input:hover { background: #f8fafc; }

.grid-input:focus {
    outline: none;
    border-color: #dc2626;
    background: white;
    box-shadow: 0 0 0 2px rgba(220, 38, 38, 0.1);
}

.grid-input.amount {
    text-align: right;
    font-family: 'SF Mono', 'Courier New', monospace;
    font-weight: 600;
}

/* Delete Button - Enhanced */
.del-btn {
    width: 32px;
    height: 32px;
    background: #fee2e2;
    border: 1px solid #fecaca;
    color: #dc2626;
    cursor: pointer;
    border-radius: 6px;
    transition: all 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.9rem;
}

.del-btn:hover {
    background: #dc2626;
    color: white;
    border-color: #dc2626;
    transform: scale(1.1);
}

.del-btn:active {
    transform: scale(0.95);
}

/* Quick Category Bar */
.quick-cats {
    display: flex;
    gap: 6px;
    padding: 10px 15px;
    background: #fafafa;
    border-bottom: 1px solid #e5e7eb;
    flex-wrap: wrap;
}

.quick-cat {
    padding: 5px 12px;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 15px;
    font-size: 0.75rem;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s;
}

.quick-cat:hover {
    border-color: #dc2626;
    color: #dc2626;
    background: #fef2f2;
}

/* Footer */
.grid-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 20px;
    background: #f8fafc;
    border-top: 2px solid #e2e8f0;
    flex-wrap: wrap;
    gap: 15px;
}

.add-btns { display: flex; gap: 8px; }

.total-box {
    display: flex;
    align-items: center;
    gap: 20px;
}

.total-display {
    text-align: right;
}

.total-display small { color: #64748b; font-size: 0.75rem; }
.total-display strong {
    font-size: 1.5rem;
    color: #dc2626;
    font-family: 'SF Mono', 'Courier New', monospace;
}

/* Empty State */
.empty-msg {
    text-align: center;
    padding: 40px;
    color: #94a3b8;
}

.empty-msg i { font-size: 2rem; margin-bottom: 10px; color: #e2e8f0; }

/* Toast */
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 500;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 8px;
    transform: translateX(120%);
    transition: transform 0.2s;
    z-index: 1000;
}

.toast.show { transform: translateX(0); }
.toast.success { background: #16a34a; }
.toast.error { background: #dc2626; }
.toast.info { background: #2563eb; }

/* Keyboard hints */
.kbd {
    background: #e2e8f0;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.7rem;
    font-family: monospace;
    margin-left: 4px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .grid-header { display: none; }
    
    .grid-row {
        display: flex;
        flex-direction: column;
        padding: 12px;
        gap: 8px;
        border-bottom: 2px solid #f1f5f9;
        position: relative;
    }
    
    .grid-row > div { padding: 0; }
    
    .grid-row > div:first-child { display: none; }
    
    .grid-input {
        border: 1px solid #e2e8f0;
        background: #f9fafb;
    }
    
    /* Mobile delete button - fixed position */
    .grid-row > div:last-child {
        position: absolute;
        top: 8px;
        right: 8px;
    }
    
    .del-btn {
        width: 36px;
        height: 36px;
    }
    
    .stat-card { flex: 1; min-width: 100px; }
    
    .quick-cats { display: none; }
    
    .total-display strong { font-size: 1.2rem; }
}

@media (max-width: 480px) {
    .top-bar h1 { font-size: 1.1rem; }
    .stats-bar { gap: 8px; }
    .stat-card { padding: 10px 12px; }
    .stat-icon { width: 32px; height: 32px; font-size: 0.9rem; }
    .stat-info strong { font-size: 0.95rem; }
}

/* Fast scroll */
.grid-body::-webkit-scrollbar { width: 6px; }
.grid-body::-webkit-scrollbar-track { background: #f1f5f9; }
.grid-body::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
.grid-body::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>

<div class="expense-page">
    <!-- Top Bar -->
    <div class="top-bar">
        <h1><i class="fas fa-receipt"></i> Quick Expense Entry</h1>
        <div class="top-actions">
            <span style="color: #64748b; font-size: 0.8rem;">
                <i class="fas fa-keyboard"></i> Tab to navigate <span class="kbd">Enter</span> new row <span class="kbd">Del</span> remove
            </span>
            <a href="expenses.php" class="btn btn-light"><i class="fas fa-times"></i> Cancel</a>
        </div>
    </div>

    <!-- Stats Bar -->
    <form method="POST" action="expense_save_bulk.php" id="expForm">
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-icon date"><i class="fas fa-calendar"></i></div>
                <div class="stat-info">
                    <small>Date</small>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required 
                           style="border:none;background:none;font-weight:600;font-size:0.95rem;width:130px;">
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon count"><i class="fas fa-list-ol"></i></div>
                <div class="stat-info">
                    <small>Entries</small>
                    <strong id="rowCount">0</strong>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-rupee-sign"></i></div>
                <div class="stat-info">
                    <small>Total</small>
                    <strong id="liveTotal">0.00</strong>
                </div>
            </div>
        </div>

        <!-- Grid Container -->
        <div class="grid-container">
            <!-- Quick Categories -->
            <div class="quick-cats" id="quickCats">
                <?php foreach(array_slice($categories, 0, 8) as $cat): ?>
                    <span class="quick-cat" onclick="fillCategory('<?= htmlspecialchars($cat) ?>')"><?= htmlspecialchars($cat) ?></span>
                <?php endforeach; ?>
            </div>

            <!-- Grid Header -->
            <div class="grid-header">
                <div>#</div>
                <div>Category</div>
                <div>Amount</div>
                <div>Notes</div>
                <div>Remove</div>
            </div>

            <!-- Grid Body -->
            <div class="grid-body" id="gridBody">
                <!-- Rows here -->
            </div>

            <!-- Empty State -->
            <div class="empty-msg" id="emptyMsg" style="display:none;">
                <i class="fas fa-inbox"></i>
                <p>No entries. Press <span class="kbd">+</span> or click Add Row</p>
            </div>

            <!-- Footer -->
            <div class="grid-footer">
                <div class="add-btns">
                    <button type="button" class="btn btn-light" onclick="addRow()">
                        <i class="fas fa-plus"></i> Add Row
                    </button>
                    <button type="button" class="btn btn-light btn-sm" onclick="addRows(5)">+5</button>
                    <button type="button" class="btn btn-light btn-sm" onclick="addRows(10)">+10</button>
                    <button type="button" class="btn btn-light btn-sm" onclick="clearAll()" title="Clear All">
                        <i class="fas fa-eraser"></i>
                    </button>
                </div>
                <div class="total-box">
                    <div class="total-display">
                        <small>Grand Total</small><br>
                        <strong>Rs.<span id="grandTotal">0.00</span></strong>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Save All
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<div id="toast" class="toast"></div>

<script>
const categories = <?= json_encode($categories) ?>;
let rowNum = 0;
let lastFocusedRow = null;

// Build category datalist
const datalistHTML = categories.map(c => `<option value="${c}">`).join('');
document.body.insertAdjacentHTML('beforeend', `<datalist id="catList">${datalistHTML}</datalist>`);

// Add single row
function addRow(focusAmount = false) {
    rowNum++;
    const grid = document.getElementById('gridBody');
    
    const row = document.createElement('div');
    row.className = 'grid-row';
    row.dataset.row = rowNum;
    
    row.innerHTML = `
        <div class="row-num">${rowNum}</div>
        <div>
            <input type="text" name="categories[]" class="grid-input cat-input" 
                   list="catList" placeholder="Type or select..." required
                   data-row="${rowNum}">
        </div>
        <div>
            <input type="number" name="amounts[]" class="grid-input amount" 
                   step="0.01" min="0.01" placeholder="0.00" required
                   oninput="calcTotal()" data-row="${rowNum}">
        </div>
        <div>
            <input type="text" name="notes[]" class="grid-input" 
                   placeholder="Optional..." data-row="${rowNum}">
        </div>
        <div>
            <button type="button" class="del-btn" onclick="delRow(this)" title="Remove this row">
                <i class="fas fa-trash-alt"></i>
            </button>
        </div>
    `;
    
    grid.appendChild(row);
    updateUI();
    
    // Focus
    const inputs = row.querySelectorAll('input');
    if (focusAmount && inputs[1]) {
        inputs[1].focus();
    } else if (inputs[0]) {
        inputs[0].focus();
    }
    
    // Scroll to new row
    row.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

// Add multiple rows
function addRows(n) {
    for (let i = 0; i < n; i++) addRow();
    toast(`Added ${n} rows`, 'success');
}

// Delete row - Enhanced with animation
function delRow(btn) {
    const rows = document.querySelectorAll('.grid-row');
    
    // Prevent deleting last row
    if (rows.length <= 1) {
        toast('Cannot delete the last row', 'error');
        // Shake animation
        btn.closest('.grid-row').style.animation = 'shake 0.3s';
        setTimeout(() => {
            btn.closest('.grid-row').style.animation = '';
        }, 300);
        return;
    }
    
    const row = btn.closest('.grid-row');
    row.classList.add('removing');
    
    setTimeout(() => {
        row.remove();
        reNumber();
        calcTotal();
        updateUI();
        toast('Row removed', 'info');
    }, 150);
}

// Clear all
function clearAll() {
    const rows = document.querySelectorAll('.grid-row');
    if (rows.length === 0) return;
    
    if (!confirm('Clear all entries?')) return;
    
    document.getElementById('gridBody').innerHTML = '';
    rowNum = 0;
    addRow(); // Add one empty row
    updateUI();
    calcTotal();
    toast('All entries cleared', 'info');
}

// Renumber rows
function reNumber() {
    const rows = document.querySelectorAll('.grid-row');
    rows.forEach((row, i) => {
        row.querySelector('.row-num').textContent = i + 1;
        row.dataset.row = i + 1;
        // Update data-row on inputs
        row.querySelectorAll('[data-row]').forEach(inp => {
            inp.dataset.row = i + 1;
        });
    });
    rowNum = rows.length;
}

// Calculate total
function calcTotal() {
    const amounts = document.querySelectorAll('.amount');
    let total = 0;
    amounts.forEach(inp => {
        const val = parseFloat(inp.value);
        if (!isNaN(val)) total += val;
    });
    
    const formatted = total.toLocaleString('en-IN', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    
    document.getElementById('liveTotal').textContent = formatted;
    document.getElementById('grandTotal').textContent = formatted;
}

// Update UI state
function updateUI() {
    const count = document.querySelectorAll('.grid-row').length;
    document.getElementById('rowCount').textContent = count;
    document.getElementById('emptyMsg').style.display = count === 0 ? 'block' : 'none';
}

// Fill category from quick pick
function fillCategory(cat) {
    let target = null;
    
    if (lastFocusedRow) {
        target = document.querySelector(`.grid-row[data-row="${lastFocusedRow}"] .cat-input`);
    }
    
    if (!target || target.value) {
        const cats = document.querySelectorAll('.cat-input');
        for (let inp of cats) {
            if (!inp.value) { target = inp; break; }
        }
    }
    
    if (!target) {
        addRow();
        target = document.querySelector('.grid-row:last-child .cat-input');
    }
    
    target.value = cat;
    const row = target.closest('.grid-row');
    row.querySelector('.amount').focus();
    
    toast(`${cat} selected`, 'success');
}

// Toast notification
function toast(msg, type = 'info') {
    const t = document.getElementById('toast');
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i> ${msg}`;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 2000);
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // Enter on last field = new row
    if (e.key === 'Enter' && !e.shiftKey) {
        const active = document.activeElement;
        if (active.classList.contains('grid-input')) {
            const row = active.closest('.grid-row');
            const inputs = row.querySelectorAll('.grid-input');
            
            if (active === inputs[inputs.length - 1]) {
                e.preventDefault();
                addRow();
            }
        }
    }
    
    // Ctrl + Enter = save
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('expForm').submit();
    }
    
    // + key = add row (when not in input)
    if (e.key === '+' && document.activeElement.tagName !== 'INPUT') {
        e.preventDefault();
        addRow();
    }
    
    // Delete key = remove current row (when in input)
    if (e.key === 'Delete' && e.ctrlKey) {
        const active = document.activeElement;
        if (active.classList.contains('grid-input')) {
            e.preventDefault();
            const row = active.closest('.grid-row');
            const delBtn = row.querySelector('.del-btn');
            delRow(delBtn);
        }
    }
    
    // Arrow down on last row = add row
    if (e.key === 'ArrowDown') {
        const active = document.activeElement;
        if (active.classList.contains('grid-input')) {
            const row = active.closest('.grid-row');
            if (!row.nextElementSibling) {
                e.preventDefault();
                addRow();
            }
        }
    }
});

// Track focused row
document.addEventListener('focusin', function(e) {
    if (e.target.classList.contains('grid-input')) {
        lastFocusedRow = e.target.dataset.row;
    }
});

// Form validation
document.getElementById('expForm').addEventListener('submit', function(e) {
    const rows = document.querySelectorAll('.grid-row');
    if (rows.length === 0) {
        e.preventDefault();
        toast('Add at least one expense', 'error');
        return;
    }
    
    let valid = true;
    rows.forEach(row => {
        const cat = row.querySelector('.cat-input');
        const amt = row.querySelector('.amount');
        if (!cat.value || !amt.value || parseFloat(amt.value) <= 0) {
            valid = false;
            row.style.background = '#fef2f2';
        } else {
            row.style.background = '';
        }
    });
    
    if (!valid) {
        e.preventDefault();
        toast('Fill all required fields', 'error');
    }
});

// Add shake animation for errors
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);

// Initialize with 3 rows
addRow();
addRow();
addRow();
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>