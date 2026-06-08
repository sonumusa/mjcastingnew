<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Bill Book Report";
$pdo = getDB();

$customers = get_customers();

$start_date = date('Y-m-d');
$end_date = date('Y-m-d');

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <h2>Bill Book (Print Pad)</h2>
    <p>Generate a continuous, linear bill book for printing.</p>
    
    <form action="print_bill_book.php" method="GET" target="_blank">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <label>From Date</label>
                <input type="date" name="start_date" value="<?= $start_date ?>" required>
            </div>
            <div>
                <label>To Date</label>
                <input type="date" name="end_date" value="<?= $end_date ?>" required>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <label>Customer (Optional - Leave empty for ALL)</label>
            <select name="customer_id" class="select2">
                <option value="">All Customers</option>
                <?php foreach ($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 10px 20px; font-size: 1.1em;">Generate Bill Book</button>
    </form>
</div>

<script>
    $(document).ready(function() {
        $('.select2').select2({ width: '100%' });
    });
</script>

<?php include __DIR__ . '/templates/footer.php'; ?>
