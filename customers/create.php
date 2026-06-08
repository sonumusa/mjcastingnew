<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Create Party';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $name = post('name');
    $phone = post('phone');
    $cnic = post('cnic');
    $address = post('address');
    $city = post('city');
    $openingBalance = parseDecimal(post('opening_balance', 0));
    $partyType = post('party_type', 'customer');
    
    if (empty($name)) {
        setFlash('error', 'Party name is required.');
        back();
    }
    
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO customers (name, phone, cnic, address, city, opening_balance, party_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([$name, $phone, $cnic, $address, $city, $openingBalance, $partyType]);
    
    setFlash('success', 'Party created successfully.');
    redirect('customers/index.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>New Party</h1>
        <p class="font-urdu" style="margin-top:4px;">نئی پارٹی</p>
    </div>
</div>

<div class="card" style="max-width:700px;padding:28px;">
    <form method="POST">
        <?= csrfField() ?>
        
        <div class="input-grid">
            <div class="form-group full-width">
                <label>Party Name <span class="font-urdu">نام</span> <span style="color:var(--error)">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="Enter party name">
            </div>
            
            <div class="form-group full-width">
                <label>Party Type <span class="font-urdu">قسم</span></label>
                <select name="party_type" class="form-control">
                    <option value="customer">Customer (گاہک)</option>
                    <option value="dukandar">Dukandar (دوکاندار)</option>
                    <option value="karigar">Karigar (کاریگر)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Phone <span class="font-urdu">فون</span></label>
                <input type="text" name="phone" class="form-control" placeholder="03XX-XXXXXXX">
            </div>
            
            <div class="form-group">
                <label>CNIC <span class="font-urdu">شناختی کارڈ</span></label>
                <input type="text" name="cnic" class="form-control" placeholder="XXXXX-XXXXXXX-X">
            </div>
            
            <div class="form-group">
                <label>City <span class="font-urdu">شہر</span></label>
                <input type="text" name="city" class="form-control" placeholder="City">
            </div>
            
            <div class="form-group">
                <label>Opening Balance (g) <span class="font-urdu">ابتدائی توازن</span></label>
                <input type="number" name="opening_balance" class="form-control" step="0.001" value="0">
            </div>
            
            <div class="form-group full-width">
                <label>Address <span class="font-urdu">پتہ</span></label>
                <textarea name="address" class="form-control" rows="2" placeholder="Address"></textarea>
            </div>
        </div>
        
        <div style="display:flex;gap:10px;margin-top:24px;">
            <button type="submit" class="btn btn-gold"><i class="bi bi-save"></i> Save Party</button>
            <a href="<?= url('customers/index.php') ?>" class="btn btn-outline"><i class="bi bi-x"></i> Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>