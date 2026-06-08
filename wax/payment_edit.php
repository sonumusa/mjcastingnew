<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Edit Payment";
$pdo = getDB();

$message = '';
$messageType = '';

// Get payment ID
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    //header('Location: payments.php');
    redirect('wax/payments.php');
    exit;
}

// Fetch payment
$stmt = $pdo->prepare("SELECT p.*, c.name as customer_name FROM wax_payments p JOIN wax_customers c ON p.customer_id = c.id WHERE p.id = ?");
$stmt->execute([$id]);
$payment = $stmt->fetch();

if (!$payment) {
    //header('Location: payments.php');
    redirect('wax/payments.php');
    exit;
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_date = $_POST['payment_date'];
    $customer_id = $_POST['customer_id'];
    $amount = floatval($_POST['amount']);
    $method = $_POST['method'];
    $notes = trim($_POST['notes']);

    if ($amount > 0 && $customer_id) {
        $stmt = $pdo->prepare("UPDATE wax_payments SET payment_date = ?, customer_id = ?, amount = ?, method = ?, notes = ?, created_at = NOW() WHERE id = ?");
        $stmt->execute([$payment_date, $customer_id, $amount, $method, $notes, $id]);
        
        $message = "Payment updated successfully!";
        $messageType = "success";
        
        // Refresh payment data
        $stmt = $pdo->prepare("SELECT p.*, c.name as customer_name FROM wax_payments p JOIN wax_customers c ON p.customer_id = c.id WHERE p.id = ?");
        $stmt->execute([$id]);
        $payment = $stmt->fetch();
    } else {
        $message = "Please fill all required fields correctly.";
        $messageType = "error";
    }
}

// Fetch customers and balances
$customers = get_customers();
$balances = get_customer_balances();

include __DIR__ . '/templates/header.php';
?>

<style>
* { box-sizing: border-box; }

.edit-page {
    max-width: 700px;
    margin: 0 auto;
    padding: 20px;
}

/* Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title .icon {
    width: 45px;
    height: 45px;
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.page-title h1 {
    margin: 0;
    font-size: 1.5rem;
    color: #1e293b;
}

.page-title small {
    display: block;
    color: #64748b;
    font-size: 0.85rem;
    font-weight: normal;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 18px;
    border: none;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    text-decoration: none;
}

.btn-secondary {
    background: #f1f5f9;
    color: #475569;
}

.btn-secondary:hover { background: #e2e8f0; }

.btn-primary {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
}

.btn-success:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.btn-danger {
    background: #fee2e2;
    color: #dc2626;
}

.btn-danger:hover { background: #fecaca; }

/* Alert */
.alert {
    padding: 14px 18px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
.alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid #dc2626; }

/* Edit Card */
.edit-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    padding: 25px 30px;
    color: white;
    display: flex;
    align-items: center;
    gap: 15px;
}

.card-header-icon {
    width: 60px;
    height: 60px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.card-header-info h2 {
    margin: 0;
    font-size: 1.25rem;
}

.card-header-info p {
    margin: 5px 0 0;
    opacity: 0.9;
    font-size: 0.9rem;
}

.card-body {
    padding: 30px;
}

/* Original Value Badge */
.original-value {
    display: inline-block;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 6px;
}

.original-value i { margin-right: 4px; }

/* Form Grid */
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
    border-color: #f59e0b;
    background: white;
    box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
}

/* Amount Input */
.amount-wrapper {
    position: relative;
}

.amount-wrapper .currency {
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
    font-family: 'SF Mono', monospace;
}

/* Customer Section */
.customer-section {
    background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    border-radius: 12px;
    padding: 20px;
    border: 2px solid #fde68a;
}

.balance-card {
    margin-top: 12px;
    padding: 14px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    background: white;
}

.balance-card.has-balance { border: 1px solid #fbbf24; }
.balance-card.no-balance { border: 1px solid #34d399; }

.balance-icon {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.balance-card.has-balance .balance-icon { background: #fbbf24; color: white; }
.balance-card.no-balance .balance-icon { background: #10b981; color: white; }

.balance-info { flex: 1; }
.balance-info small { color: #6b7280; font-size: 0.75rem; }
.balance-info strong { display: block; font-size: 1.1rem; font-family: monospace; }

.balance-card.has-balance strong { color: #b45309; }
.balance-card.no-balance strong { color: #047857; }

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
    padding: 14px 10px;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.15s;
    background: #f9fafb;
}

.method-card:hover {
    border-color: #f59e0b;
    background: #fffbeb;
}

.method-option input:checked + .method-card {
    border-color: #f59e0b;
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    box-shadow: 0 4px 12px rgba(245, 158, 11, 0.2);
}

.method-icon { font-size: 1.4rem; margin-bottom: 4px; }
.method-label { font-size: 0.8rem; font-weight: 600; color: #374151; }

/* Notes */
.notes-input { min-height: 80px; resize: vertical; }

/* Card Footer */
.card-footer {
    padding: 20px 30px;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.footer-info {
    color: #6b7280;
    font-size: 0.85rem;
}

.footer-actions {
    display: flex;
    gap: 10px;
}

/* Change Indicator */
.changed {
    border-color: #f59e0b !important;
    background: #fffbeb !important;
}

/* Select2 */
.select2-container--default .select2-selection--single {
    height: 50px;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    background: #f9fafb;
}

.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 46px;
    padding-left: 16px;
}

.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 48px;
}

.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #f59e0b;
}

/* Mobile */
@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
    .method-options { grid-template-columns: repeat(2, 1fr); }
    .card-footer { flex-direction: column; }
    .footer-actions { width: 100%; }
    .footer-actions .btn { flex: 1; justify-content: center; }
}

/* Toast */
.toast {
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 14px 22px;
    border-radius: 10px;
    color: white;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    transform: translateX(120%);
    transition: transform 0.2s;
    z-index: 1000;
}

.toast.show { transform: translateX(0); }
.toast.success { background: #059669; }
.toast.error { background: #dc2626; }
</style>

<div class="edit-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="page-title">
            <div class="icon"><i class="fas fa-edit"></i></div>
            <div>
                <h1>Edit Payment</h1>
                <small>ID: #<?= $payment['id'] ?> • Created: <?= date('d M Y', strtotime($payment['created_at'] ?? $payment['payment_date'])) ?></small>
            </div>
        </div>
        <a href="payments.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Alert -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= $message ?>
            <?php if ($messageType === 'success'): ?>
                <a href="payments.php" style="margin-left: auto; color: inherit; text-decoration: underline;">Go to list</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Edit Card -->
    <div class="edit-card">
        <div class="card-header">
            <div class="card-header-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="card-header-info">
                <h2>Payment Details</h2>
                <p><?= htmlspecialchars($payment['customer_name']) ?> • Rs.<?= number_format($payment['amount'], 2) ?></p>
            </div>
        </div>

        <form method="POST" id="editForm">
            <div class="card-body">
                <div class="form-grid">
                    <!-- Date -->
                    <div class="form-group">
                        <label><i class="fas fa-calendar-alt"></i> Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" 
                               value="<?= $payment['payment_date'] ?>" required
                               data-original="<?= $payment['payment_date'] ?>">
                        <span class="original-value">
                            <i class="fas fa-history"></i> Original: <?= date('d M Y', strtotime($payment['payment_date'])) ?>
                        </span>
                    </div>

                    <!-- Amount -->
                    <div class="form-group">
                        <label><i class="fas fa-coins"></i> Amount</label>
                        <div class="amount-wrapper">
                            <span class="currency">₹</span>
                            <input type="number" name="amount" id="amountInput" class="form-control" 
                                   step="0.01" min="0.01" value="<?= $payment['amount'] ?>" required
                                   data-original="<?= $payment['amount'] ?>">
                        </div>
                        <span class="original-value">
                            <i class="fas fa-history"></i> Original: Rs.<?= number_format($payment['amount'], 2) ?>
                        </span>
                    </div>

                    <!-- Customer -->
                    <div class="form-group full-width">
                        <div class="customer-section">
                            <label><i class="fas fa-user"></i> Customer</label>
                            <select name="customer_id" id="customerSelect" class="form-control select2" required
                                    data-original="<?= $payment['customer_id'] ?>">
                                <?php foreach ($customers as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= $c['id'] == $payment['customer_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="balanceDisplay"></div>
                            <span class="original-value">
                                <i class="fas fa-history"></i> Original: <?= htmlspecialchars($payment['customer_name']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-credit-card"></i> Payment Method</label>
                        <div class="method-options">
                            <label class="method-option">
                                <input type="radio" name="method" value="cash" <?= $payment['method'] === 'cash' ? 'checked' : '' ?>>
                                <div class="method-card">
                                    <span class="method-icon">💵</span>
                                    <span class="method-label">Cash</span>
                                </div>
                            </label>
                            <label class="method-option">
                                <input type="radio" name="method" value="bank" <?= $payment['method'] === 'bank' ? 'checked' : '' ?>>
                                <div class="method-card">
                                    <span class="method-icon">🏦</span>
                                    <span class="method-label">Bank</span>
                                </div>
                            </label>
                            <label class="method-option">
                                <input type="radio" name="method" value="upi" <?= $payment['method'] === 'upi' ? 'checked' : '' ?>>
                                <div class="method-card">
                                    <span class="method-icon">📱</span>
                                    <span class="method-label">UPI</span>
                                </div>
                            </label>
                            <label class="method-option">
                                <input type="radio" name="method" value="check" <?= $payment['method'] === 'check' ? 'checked' : '' ?>>
                                <div class="method-card">
                                    <span class="method-icon">📝</span>
                                    <span class="method-label">Check</span>
                                </div>
                            </label>
                        </div>
                        <span class="original-value">
                            <i class="fas fa-history"></i> Original: <?= ucfirst($payment['method']) ?>
                        </span>
                    </div>

                    <!-- Notes -->
                    <div class="form-group full-width">
                        <label><i class="fas fa-sticky-note"></i> Notes</label>
                        <textarea name="notes" class="form-control notes-input" 
                                  placeholder="Optional notes..."
                                  data-original="<?= htmlspecialchars($payment['notes']) ?>"><?= htmlspecialchars($payment['notes']) ?></textarea>
                        <?php if ($payment['notes']): ?>
                            <span class="original-value">
                                <i class="fas fa-history"></i> Original: <?= htmlspecialchars(substr($payment['notes'], 0, 50)) ?><?= strlen($payment['notes']) > 50 ? '...' : '' ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card-footer">
                <div class="footer-info">
                    <i class="fas fa-info-circle"></i>
                    Changes will be saved immediately
                </div>
                <div class="footer-actions">
                    <a href="payments.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteForm" method="POST" action="payments.php" style="display: none;">
    <input type="hidden" name="delete_id" value="<?= $payment['id'] ?>">
</form>

<!-- Toast -->
<div id="toast" class="toast"></div>

<script>
const balances = <?= json_encode($balances) ?>;

// Update balance display
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
                    <small>Current Outstanding</small>
                    <strong>Rs.${balance.toLocaleString('en-IN', {minimumFractionDigits: 2})}</strong>
                </div>
            </div>
        `;
    } else {
        balDiv.innerHTML = '';
    }
}

// Track changes
function trackChanges() {
    document.querySelectorAll('[data-original]').forEach(el => {
        el.addEventListener('input', function() {
            this.classList.toggle('changed', this.value !== this.dataset.original);
        });
        el.addEventListener('change', function() {
            this.classList.toggle('changed', this.value !== this.dataset.original);
        });
    });
}

// Confirm delete
function confirmDelete() {
    if (confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
        document.getElementById('deleteForm').submit();
    }
}

// Toast
function toast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${msg}`;
    t.className = `toast ${type} show`;
    setTimeout(() => t.classList.remove('show'), 3000);
}

// Initialize
$(document).ready(function() {
    $('.select2').select2({
        width: '100%'
    }).on('change', updateBalance);
    
    updateBalance();
    trackChanges();
});

// Keyboard shortcut
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('editForm').submit();
    }
});

// Warn on unsaved changes
let formChanged = false;
document.getElementById('editForm').addEventListener('input', () => formChanged = true);

window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
    }
});

document.getElementById('editForm').addEventListener('submit', () => formChanged = false);
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>