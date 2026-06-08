<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Bulk Payments";
$pdo = getDB();

$customers = get_customers();
$outstanding = get_customer_balances();

include __DIR__ . '/templates/header.php';
?>

<style>
    .bulk-payment-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e9ecef;
    }

    .page-header h1 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 600;
        color: #2d3748;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-header h1 i {
        color: #4f46e5;
    }

    .header-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        border: none;
        border-radius: 8px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(79, 70, 229, 0.4);
    }

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
    }

    .btn-success {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        color: white;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .btn-danger {
        background: #fee2e2;
        color: #dc2626;
        padding: 8px 12px;
    }

    .btn-danger:hover {
        background: #fecaca;
    }

    .payment-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 20px 24px;
        border-bottom: 1px solid #e2e8f0;
    }

    .date-section {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .date-section label {
        font-weight: 600;
        color: #374151;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .date-input {
        padding: 10px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.2s;
    }

    .date-input:focus {
        outline: none;
        border-color: #4f46e5;
    }

    .summary-badges {
        display: flex;
        gap: 20px;
        margin-left: auto;
    }

    .summary-badge {
        background: white;
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    .summary-badge.entries {
        color: #4f46e5;
    }

    .summary-badge.total {
        color: #059669;
        font-weight: 600;
    }

    .card-body {
        padding: 24px;
    }

    .payment-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .payment-table thead th {
        background: #f8fafc;
        padding: 12px 16px;
        text-align: left;
        font-weight: 600;
        color: #64748b;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
    }

    .payment-table thead th:first-child {
        border-radius: 8px 0 0 0;
    }

    .payment-table thead th:last-child {
        border-radius: 0 8px 0 0;
        text-align: center;
        width: 60px;
    }

    .payment-row {
        animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .payment-row td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .payment-row:hover {
        background: #fafbfc;
    }

    .customer-cell {
        min-width: 250px;
    }

    .balance-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-top: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .balance-badge.has-balance {
        background: #fef3c7;
        color: #d97706;
    }

    .balance-badge.no-balance {
        background: #d1fae5;
        color: #059669;
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: all 0.2s;
    }

    .form-control:focus {
        outline: none;
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .amount-input {
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    .method-select {
        cursor: pointer;
    }

    .method-icon {
        margin-right: 6px;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #94a3b8;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        color: #cbd5e1;
    }

    .empty-state p {
        margin: 0;
        font-size: 1rem;
    }

    .card-footer {
        background: #f8fafc;
        padding: 20px 24px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .add-row-btn {
        background: white;
        border: 2px dashed #cbd5e1;
        color: #64748b;
        border-radius: 8px;
        padding: 12px 24px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .add-row-btn:hover {
        border-color: #4f46e5;
        color: #4f46e5;
        background: #f5f3ff;
    }

    .submit-section {
        display: flex;
        gap: 12px;
        align-items: center;
    }

    .total-display {
        font-size: 1.1rem;
        color: #374151;
        padding-right: 20px;
        border-right: 1px solid #e2e8f0;
        margin-right: 8px;
    }

    .total-display strong {
        color: #059669;
        font-size: 1.25rem;
    }

    /* Select2 Custom Styling */
    .select2-container--default .select2-selection--single {
        height: 42px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 38px;
        padding-left: 14px;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 40px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #4f46e5;
    }

    .select2-dropdown {
        border: 2px solid #e2e8f0;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .select2-results__option--highlighted {
        background-color: #4f46e5 !important;
    }

    /* Quick Actions */
    .quick-actions {
        display: flex;
        gap: 8px;
        margin-top: 8px;
    }

    .quick-btn {
        padding: 4px 10px;
        font-size: 0.7rem;
        border-radius: 4px;
        background: #f1f5f9;
        color: #64748b;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
    }

    .quick-btn:hover {
        background: #e2e8f0;
    }

    /* Toast Notification */
    .toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        padding: 16px 24px;
        border-radius: 12px;
        color: white;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        transform: translateX(120%);
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .toast.show {
        transform: translateX(0);
    }

    .toast.success {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }

    .toast.error {
        background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .date-section {
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        .summary-badges {
            margin-left: 0;
            width: 100%;
            justify-content: space-between;
        }

        .payment-table {
            display: block;
        }

        .payment-table thead {
            display: none;
        }

        .payment-row {
            display: block;
            padding: 16px;
            margin-bottom: 12px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .payment-row td {
            display: block;
            padding: 8px 0;
            border: none;
        }

        .payment-row td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            display: block;
            margin-bottom: 4px;
        }

        .card-footer {
            flex-direction: column;
            gap: 15px;
        }

        .submit-section {
            width: 100%;
            flex-direction: column;
        }

        .total-display {
            border: none;
            padding: 0;
            margin: 0;
            text-align: center;
        }
    }

    /* Row number */
    .row-number {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.85rem;
    }
</style>

<div class="bulk-payment-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-layer-group"></i>
            Bulk Payment Entry
        </h1>
        <div class="header-actions">
            <a href="payments.php" class="btn btn-secondary">
                <i class="fas fa-list"></i>
                View All Payments
            </a>
        </div>
    </div>

    <!-- Main Card -->
    <form method="POST" action="payment_save_bulk.php" id="bulkPaymentForm">
        <div class="payment-card">
            <!-- Card Header -->
            <div class="card-header">
                <div class="date-section">
                    <label>
                        <i class="fas fa-calendar-alt"></i>
                        Payment Date
                    </label>
                    <input type="date" name="date" value="<?= date('Y-m-d') ?>" required class="date-input">
                    
                    <div class="summary-badges">
                        <div class="summary-badge entries">
                            <i class="fas fa-receipt"></i>
                            <span id="entryCount">0</span> Entries
                        </div>
                        <div class="summary-badge total">
                            <i class="fas fa-coins"></i>
                            Total: <span id="totalAmount">0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Body -->
            <div class="card-body">
                <table class="payment-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Customer</th>
                            <th style="width: 150px;">Amount</th>
                            <th style="width: 140px;">Method</th>
                            <th style="width: 200px;">Notes</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="paymentTableBody">
                        <!-- Dynamic rows -->
                    </tbody>
                </table>

                <div id="emptyState" class="empty-state" style="display: none;">
                    <i class="fas fa-inbox"></i>
                    <p>No payment entries yet. Click "Add Payment" to get started.</p>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="card-footer">
                <button type="button" class="btn add-row-btn" onclick="addPaymentRow()">
                    <i class="fas fa-plus"></i>
                    Add Payment Row
                </button>

                <div class="submit-section">
                    <div class="total-display">
                        Grand Total: <strong id="grandTotal">Rs. 0.00</strong>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="clearAllRows()">
                        <i class="fas fa-eraser"></i>
                        Clear All
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i>
                        Save All Payments
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Toast Notification -->
<div id="toast" class="toast"></div>

<script>
    // Data from PHP
    const customers = <?= json_encode($customers) ?>;
    const balances = <?= json_encode($outstanding) ?>;
    let rowCounter = 0;

    // Add Payment Row
    function addPaymentRow() {
        rowCounter++;
        const tbody = document.getElementById('paymentTableBody');
        const row = document.createElement('tr');
        row.className = 'payment-row';
        row.id = `row-${rowCounter}`;

        let customerOptions = '<option value="">Choose customer...</option>';
        customers.forEach(c => {
            customerOptions += `<option value="${c.id}">${c.name}</option>`;
        });

        row.innerHTML = `
            <td data-label="#">
                <div class="row-number">${rowCounter}</div>
            </td>
            <td data-label="Customer" class="customer-cell">
                <select name="customer_ids[]" class="form-control customer-select" required data-row="${rowCounter}">
                    ${customerOptions}
                </select>
                <div class="balance-badge-container"></div>
                <div class="quick-actions">
                    <button type="button" class="quick-btn" onclick="fillOutstanding(${rowCounter})">
                        <i class="fas fa-wallet"></i> Fill Outstanding
                    </button>
                </div>
            </td>
            <td data-label="Amount">
                <input type="number" name="amounts[]" class="form-control amount-input" 
                       placeholder="0.00" step="0.01" min="0" required 
                       oninput="updateTotals()" data-row="${rowCounter}">
            </td>
            <td data-label="Method">
                <select name="methods[]" class="form-control method-select">
                    <option value="cash">💵 Cash</option>
                    <option value="bank">🏦 Bank Transfer</option>
                </select>
            </td>
            <td data-label="Notes">
                <input type="text" name="notes[]" class="form-control" placeholder="Optional note...">
            </td>
            <td>
                <button type="button" class="btn btn-danger" onclick="removeRow(${rowCounter})" title="Remove">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
        
        // Initialize Select2 for the new row
        $(`#row-${rowCounter} .customer-select`).select2({
            placeholder: 'Search customer...',
            allowClear: true,
            width: '100%'
        }).on('select2:select', function(e) {
            updateCustomerBalance(this);
        }).on('select2:clear', function(e) {
            clearCustomerBalance(this);
        });

        updateUI();
        showToast('Row added', 'success');
    }

    // Remove Row
    function removeRow(id) {
        const row = document.getElementById(`row-${id}`);
        if (row) {
            row.style.animation = 'slideIn 0.3s ease reverse';
            setTimeout(() => {
                row.remove();
                updateTotals();
                updateUI();
            }, 250);
        }
    }

    // Clear All Rows
    function clearAllRows() {
        if (confirm('Are you sure you want to clear all entries?')) {
            document.getElementById('paymentTableBody').innerHTML = '';
            rowCounter = 0;
            updateUI();
            showToast('All entries cleared', 'success');
        }
    }

    // Update Customer Balance Display
    function updateCustomerBalance(select) {
        const row = select.closest('.payment-row');
        const container = row.querySelector('.balance-badge-container');
        const customerId = select.value;

        if (customerId && balances[customerId] !== undefined) {
            const balance = parseFloat(balances[customerId]);
            const badgeClass = balance > 0 ? 'has-balance' : 'no-balance';
            const icon = balance > 0 ? 'exclamation-triangle' : 'check-circle';
            container.innerHTML = `
                <span class="balance-badge ${badgeClass}">
                    <i class="fas fa-${icon}"></i>
                    Outstanding: Rs.${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}
                </span>
            `;
        } else {
            container.innerHTML = '';
        }
    }

    // Clear Balance Display
    function clearCustomerBalance(select) {
        const row = select.closest('.payment-row');
        const container = row.querySelector('.balance-badge-container');
        container.innerHTML = '';
    }

    // Fill Outstanding Amount
    function fillOutstanding(rowId) {
        const row = document.getElementById(`row-${rowId}`);
        const select = row.querySelector('.customer-select');
        const amountInput = row.querySelector('.amount-input');
        const customerId = select.value;

        if (customerId && balances[customerId] !== undefined) {
            const balance = parseFloat(balances[customerId]);
            if (balance > 0) {
                amountInput.value = balance.toFixed(2);
                updateTotals();
                showToast('Outstanding amount filled', 'success');
            } else {
                showToast('No outstanding balance', 'error');
            }
        } else {
            showToast('Please select a customer first', 'error');
        }
    }

    // Update Totals
    function updateTotals() {
        const amounts = document.querySelectorAll('.amount-input');
        let total = 0;
        amounts.forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        document.getElementById('totalAmount').textContent = total.toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('grandTotal').textContent = 'Rs.' + total.toLocaleString('en-IN', {minimumFractionDigits: 2});
    }

    // Update UI State
    function updateUI() {
        const rows = document.querySelectorAll('.payment-row');
        const emptyState = document.getElementById('emptyState');
        const entryCount = document.getElementById('entryCount');

        if (rows.length === 0) {
            emptyState.style.display = 'block';
        } else {
            emptyState.style.display = 'none';
        }

        entryCount.textContent = rows.length;
        updateTotals();

        // Update row numbers
        rows.forEach((row, index) => {
            const numDiv = row.querySelector('.row-number');
            if (numDiv) numDiv.textContent = index + 1;
        });
    }

    // Show Toast
    function showToast(message, type) {
        const toast = document.getElementById('toast');
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        toast.className = `toast ${type} show`;
        
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }

    // Form Validation
    document.getElementById('bulkPaymentForm').addEventListener('submit', function(e) {
        const rows = document.querySelectorAll('.payment-row');
        if (rows.length === 0) {
            e.preventDefault();
            showToast('Please add at least one payment entry', 'error');
            return;
        }

        // Check for duplicate customers
        const customerSelects = document.querySelectorAll('.customer-select');
        const selectedCustomers = [];
        let hasDuplicate = false;

        customerSelects.forEach(select => {
            if (select.value && selectedCustomers.includes(select.value)) {
                hasDuplicate = true;
            }
            if (select.value) selectedCustomers.push(select.value);
        });

        if (hasDuplicate) {
            e.preventDefault();
            showToast('Duplicate customers detected!', 'error');
            return;
        }
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to add row
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            addPaymentRow();
        }
    });

    // Initialize with one row
    addPaymentRow();
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>