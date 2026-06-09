<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Edit Party';

$id = (int) query('id', 0);
$db = getDB();
$stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$id]);
$customer = $stmt->fetch();

if (!$customer) {
    setFlash('error', 'Party not found.');
    redirect('customers/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $name = post('name');
    $phone = post('phone');
    $cnic = post('cnic');
    $address = post('address');
    $city = post('city');
    $openingBalance = parseDecimal(post('opening_balance', 0));
    $status = post('status', 'active');
    $partyType = post('party_type', 'customer');
    
    if (empty($name)) {
        setFlash('error', 'Party name is required.');
        back();
    }
    
    $stmt = $db->prepare("UPDATE customers SET name=?, phone=?, cnic=?, address=?, city=?, opening_balance=?, status=?, party_type=? WHERE id=?");
    $stmt->execute([$name, $phone, $cnic, $address, $city, $openingBalance, $status, $partyType, $id]);
    
    setFlash('success', 'Party updated successfully.');
    redirect('customers/show.php?id=' . $id);
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Edit Party</h1>
        <p class="font-urdu" style="margin-top:4px;">پارٹی میں ترمیم</p>
    </div>
</div>

<div class="card" style="max-width:700px;padding:28px;">
    <form method="POST">
        <?= csrfField() ?>
        
        <div class="input-grid">
            <div class="form-group full-width">
                <label>Party Name <span class="font-urdu">نام</span> <span style="color:var(--error)">*</span></label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($customer['name']) ?>">
            </div>
            
            <div class="form-group full-width">
                <label>Party Type <span class="font-urdu">قسم</span></label>
                <select name="party_type" class="form-control">
                    <option value="customer" <?= $customer['party_type'] === 'customer' ? 'selected' : '' ?>>Customer (گاہک)</option>
                    <option value="dukandar" <?= $customer['party_type'] === 'dukandar' ? 'selected' : '' ?>>Dukandar (دوکاندار)</option>
                    <option value="karigar" <?= $customer['party_type'] === 'karigar' ? 'selected' : '' ?>>Karigar (کاریگر)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Phone <span class="font-urdu">فون</span></label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>CNIC <span class="font-urdu">شناختی کارڈ</span></label>
                <input type="text" name="cnic" class="form-control" value="<?= htmlspecialchars($customer['cnic'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>City <span class="font-urdu">شہر</span></label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($customer['city'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Status <span class="font-urdu">حالت</span></label>
                <select name="status" class="form-control">
                    <option value="active" <?= $customer['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $customer['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Opening Balance (g) <span class="font-urdu">ابتدائی توازن</span></label>
                <input type="number" name="opening_balance" class="form-control" step="0.001" value="<?= $customer['opening_balance'] ?>">
            </div>
            
            <div class="form-group full-width">
                <label>Address <span class="font-urdu">پتہ</span></label>
                <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea>
            </div>
        </div>
        
        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-gold"><i class="bi bi-save"></i> Update Party</button>
            <a href="<?= url('customers/show.php?id=' . $id) ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>