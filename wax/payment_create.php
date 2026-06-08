<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "New Payment";
$pdo = getDB();

$customers = get_customers();
$balances = get_customer_balances();

include __DIR__ . '/templates/header.php';
?>

<style>
    .payment-container {
        max-width: 700px;
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
        color: #10b981;
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        padding: 12px;
        border-radius: 12px;
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

    .btn-secondary {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    .btn-secondary:hover {
        background: #e2e8f0;
        transform: translateY(-1px);
    }

    .btn-primary {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }

    .payment-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        padding: 30px;
        text-align: center;
        color: white;
    }

    .card-header-icon {
        width: 70px;
        height: 70px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.8rem;
    }

    .card-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .card-header p {
        margin: 8px 0 0;
        opacity: 0.9;
        font-size: 0.95rem;
    }

    .card-body {
        padding: 30px;
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
    }

    .form-group {
        position: relative;
    }

    .form-group.full-width {
        grid-column: 1 / -1;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
        font-size: 0.9rem;
    }

    .form-group label i {
        margin-right: 6px;
        color: #6b7280;
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.2s;
        background: #f9fafb;
    }

    .form-control:focus {
        outline: none;
        border-color: #10b981;
        background: white;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    .form-control::placeholder {
        color: #9ca3af;
    }

    /* Customer Section Special Styling */
    .customer-section {
        background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        border-radius: 12px;
        padding: 20px;
        border: 2px solid #bbf7d0;
    }

    .balance-card {
        margin-top: 12px;
        padding: 15px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-5px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .balance-card.has-balance {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #fbbf24;
    }

    .balance-card.no-balance {
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        border: 1px solid #34d399;
    }

    .balance-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
    }

    .balance-card.has-balance .balance-icon {
        background: #fbbf24;
        color: white;
    }

    .balance-card.no-balance .balance-icon {
        background: #10b981;
        color: white;
    }

    .balance-info {
        flex: 1;
    }

    .balance-label {
        font-size: 0.8rem;
        color: #6b7280;
        margin-bottom: 2px;
    }

    .balance-amount {
        font-size: 1.25rem;
        font-weight: 700;
        font-family: 'Courier New', monospace;
    }

    .balance-card.has-balance .balance-amount {
        color: #b45309;
    }

    .balance-card.no-balance .balance-amount {
        color: #047857;
    }

    .quick-fill-btn {
        padding: 8px 16px;
        background: white;
        border: 2px solid #fbbf24;
        border-radius: 8px;
        color: #b45309;
        font-weight: 600;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }

    .quick-fill-btn:hover {
        background: #fbbf24;
        color: white;
    }

    /* Amount Input Special */
    .amount-wrapper {
        position: relative;
    }

    .amount-wrapper .currency-symbol {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        font-size: 1.1rem;
        font-weight: 600;
        color: #6b7280;
    }

    .amount-wrapper .form-control {
        padding-left: 40px;
        font-size: 1.25rem;
        font-weight: 600;
        font-family: 'Courier New', monospace;
    }

    /* Method Cards */
    .method-options {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 10px;
    }

    .method-option {
        position: relative;
    }

    .method-option input {
        position: absolute;
        opacity: 0;
        cursor: pointer;
    }

    .method-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px 10px;
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.2s;
        background: #f9fafb;
    }

    .method-card:hover {
        border-color: #10b981;
        background: #f0fdf4;
    }

    .method-option input:checked + .method-card {
        border-color: #10b981;
        background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .method-icon {
        font-size: 1.5rem;
        margin-bottom: 6px;
    }

    .method-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
    }

    /* Notes Section */
    .notes-input {
        min-height: 80px;
        resize: vertical;
    }

    /* Submit Section */
    .card-footer {
        padding: 24px 30px;
        background: #f8fafc;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .submit-btn {
        padding: 14px 32px;
        font-size: 1rem;
    }

    /* Select2 Custom */
    .select2-container--default .select2-selection--single {
        height: 50px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        background: #f9fafb;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 46px;
        padding-left: 16px;
        font-size: 1rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 48px;
        right: 10px;
    }

    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #10b981;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
    }

    .select2-dropdown {
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        margin-top: 5px;
    }

    .select2-results__option--highlighted {
        background-color: #10b981 !important;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }

        .method-options {
            grid-template-columns: repeat(2, 1fr);
        }

        .card-footer {
            flex-direction: column;
            gap: 15px;
        }

        .submit-btn {
            width: 100%;
            justify-content: center;
        }
    }

    /* Toast */
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
        transform: translateX(150%);
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .toast.show {
        transform: translateX(0);
    }

    .toast.success {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .toast.info {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    }
</style>

<div class="payment-container">
    <!-- Page Header -->
    <div class="page-header">
        <h1>
            <i class="fas fa-hand-holding-usd"></i>
            Record Payment
        </h1>
        <a href="payments.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i>
            Back to List
        </a>
    </div>

    <!-- Payment Card -->
    <div class="payment-card">
        <div class="card-header">
            <div class="card-header-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <h2>New Payment Entry</h2>
            <p>Record a customer payment quickly and easily</p>
        </div>

        <form method="POST" action="payment_save.php" id="paymentForm">
            <div class="card-body">
                <div class="form-grid">
                    <!-- Date -->
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Amount -->
                    <div class="form-group">
                        <label><i class="fas fa-coins"></i> Amount</label>
                        <div class="amount-wrapper">
                            <span class="currency-symbol">Rs.</span>
                            <input type="number" name="amount" id="amountInput" class="form-control" 
                                   step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                    </div>

                    <!-- Customer Selection -->
                    <div class="form-group full-width">
                        <div class="customer-section">
                            <label><i class="fas fa-user"></i> Select Customer</label>
                            <select name="customer_id" id="customerSelect" class="form-control select2" required>
                                <option value="">Search or select customer...</option>
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="balanceDisplay"></div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-credit-card"></i> Payment Method</label>
                        <div class="method-options">
                            <label class="method-option">
                                <input type="radio" name="method" value="cash" checked>
                                <div class="method-card">
                                    <span class="method-icon">💵</span>
                                    <span class="method-label">Cash</span>
                                </div>
                            </label>
                            <label class="method-option">
                                <input type="radio" name="method" value="bank">
                                <div class="method-card">
                                    <span class="method-icon">🏦</span>
                                    <span class="method-label">Bank</span>
                                </div>
                            </label>
                            <label class="method-option">
                                <input type="radio" name="method" value="upi">
                                <div class="method-card">
                                    <span class="method-icon">📱</span>
                                    <span class="method-label">UPI</span>
                                </div>
                            </label>
                            <label class="method-option">
                                <input type="radio" name="method" value="check">
                                <div class="method-card">
                                    <span class="method-icon">📝</span>
                                    <span class="method-label">Check</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-sticky-note"></i> Notes (Optional)</label>
                        <textarea name="notes" class="form-control notes-input" 
                                  placeholder="Invoice number, reference, or any additional details..."></textarea>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div style="color: #6b7280; font-size: 0.9rem;">
                    <i class="fas fa-info-circle"></i>
                    Press <kbd style="background: #e5e7eb; padding: 2px 6px; border-radius: 4px;">Ctrl</kbd> + 
                    <kbd style="background: #e5e7eb; padding: 2px 6px; border-radius: 4px;">Enter</kbd> to save
                </div>
                <button type="submit" class="btn btn-primary submit-btn">
                    <i class="fas fa-check-circle"></i>
                    Save Payment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
    const balances = <?= json_encode($balances) ?>;

    // Update Balance Display
    function updateBalance() {
        const select = document.getElementById('customerSelect');
        const custId = select.value;
        const balDiv = document.getElementById('balanceDisplay');

        if (custId && balances[custId] !== undefined) {
            const balance = parseFloat(balances[custId]);
            const hasBalance = balance > 0;
            
            balDiv.innerHTML = `
                <div class="balance-card ${hasBalance ? 'has-balance' : 'no-balance'}">
                    <div class="balance-icon">
                        <i class="fas fa-${hasBalance ? 'exclamation-triangle' : 'check-circle'}"></i>
                    </div>
                    <div class="balance-info">
                        <div class="balance-label">Outstanding Balance</div>
                        <div class="balance-amount">Rs.${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}</div>
                    </div>
                    ${hasBalance ? `<button type="button" class="quick-fill-btn" onclick="fillAmount(${balance})">
                        <i class="fas fa-magic"></i> Fill Amount
                    </button>` : ''}
                </div>
            `;
        } else {
            balDiv.innerHTML = '';
        }
    }

    // Fill Amount
    function fillAmount(amount) {
        document.getElementById('amountInput').value = amount.toFixed(2);
        showToast('Amount filled!', 'success');
    }

    // Show Toast
    function showToast(message, type) {
        const toast = document.getElementById('toast');
        toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i> ${message}`;
        toast.className = `toast ${type} show`;
        setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Initialize
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: 'Search or select customer...',
            allowClear: true,
            width: '100%'
        });

        $('#customerSelect').on('select2:select select2:clear', updateBalance);
    });

    // Keyboard Shortcut
    document.addEventListener('keydown', function(e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('paymentForm').submit();
        }
    });

    // Form Validation
    document.getElementById('paymentForm').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amountInput').value);
        if (amount <= 0) {
            e.preventDefault();
            showToast('Please enter a valid amount', 'info');
        }
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>