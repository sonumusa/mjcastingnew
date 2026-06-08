<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$pageTitle = 'Settings';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
    
    $settings = [
        'workshop_name' => post('workshop_name', 'M.J Casting'),
        'workshop_name_urdu' => post('workshop_name_urdu', 'ایم جے کاسٹنگ'),
        'address' => post('address', ''),
        'phone' => post('phone', ''),
        'phone2' => post('phone2', ''),
        'phone3' => post('phone3', ''),
        'city' => post('city', ''),
        'messenger' => post('messenger', ''),
        'social' => post('social', ''),
        'default_rp_rate' => post('default_rp_rate', 0),
        'default_gram_rate' => post('default_gram_rate', 0),
        'default_waste_rate' => post('default_waste_rate', 0.125),
        'default_ratti_rate' => post('default_ratti_rate', 0),
    ];
    
    foreach ($settings as $key => $value) {
        setSetting($key, $value);
    }
    
    setFlash('success', 'Settings saved successfully.');
    redirect('settings/index.php');
}

require_once __DIR__ . '/../includes/header.php';

$settings = [
    'workshop_name' => getSetting('workshop_name', 'M.J Casting'),
    'workshop_name_urdu' => getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ'),
    'address' => getSetting('address', ''),
    'phone' => getSetting('phone', ''),
    'phone2' => getSetting('phone2', ''),
    'phone3' => getSetting('phone3', ''),
    'city' => getSetting('city', ''),
    'messenger' => getSetting('messenger', ''),
    'social' => getSetting('social', ''),
    'default_rp_rate' => getSetting('default_rp_rate', 0),
    'default_gram_rate' => getSetting('default_gram_rate', 0),
    'default_waste_rate' => getSetting('default_waste_rate', 0.125),
    'default_ratti_rate' => getSetting('default_ratti_rate', 0),
];
?>

<div class="page-header">
    <div class="page-title-group">
        <h1>Settings</h1>
        <p class="font-urdu" style="margin-top:4px;">سیٹنگز</p>
    </div>
</div>

<div class="card" style="max-width:700px;padding:28px;">
    <form method="POST">
        <?= csrfField() ?>
        
        <div class="section-header" style="display:flex;align-items:center;gap:14px;margin-bottom:24px;padding-bottom:14px;border-bottom:1px solid var(--border-color);">
            <i class="bi bi-shop" style="color:var(--gold-primary);"></i>
            <h3 style="font-family:'Playfair Display',serif;margin:0;">Workshop Information</h3>
        </div>
        
        <div class="input-grid">
            <div class="form-group full-width">
                <label>Workshop Name (English)</label>
                <input type="text" name="workshop_name" class="form-control" value="<?= htmlspecialchars($settings['workshop_name']) ?>">
            </div>
            <div class="form-group full-width">
                <label>Workshop Name (Urdu)</label>
                <input type="text" name="workshop_name_urdu" class="form-control" value="<?= htmlspecialchars($settings['workshop_name_urdu']) ?>">
            </div>
            <div class="form-group full-width">
                <label>Address</label>
                <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($settings['address']) ?>">
            </div>
            <div class="form-group">
                <label>Phone 1</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($settings['phone']) ?>">
            </div>
            <div class="form-group">
                <label>Phone 2</label>
                <input type="text" name="phone2" class="form-control" value="<?= htmlspecialchars($settings['phone2']) ?>">
            </div>
            <div class="form-group">
                <label>Phone 3</label>
                <input type="text" name="phone3" class="form-control" value="<?= htmlspecialchars($settings['phone3']) ?>">
            </div>
            <div class="form-group">
                <label>City</label>
                <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($settings['city']) ?>">
            </div>
            <div class="form-group">
                <label>Messenger</label>
                <input type="text" name="messenger" class="form-control" value="<?= htmlspecialchars($settings['messenger']) ?>">
            </div>
            <div class="form-group full-width">
                <label>Social Media</label>
                <input type="text" name="social" class="form-control" value="<?= htmlspecialchars($settings['social']) ?>">
            </div>
        </div>

        <div class="section-header" style="display:flex;align-items:center;gap:14px;margin:32px 0 24px;padding-bottom:14px;border-bottom:1px solid var(--border-color);">
            <i class="bi bi-sliders" style="color:var(--gold-primary);"></i>
            <h3 style="font-family:'Playfair Display',serif;margin:0;">Default Values</h3>
        </div>

        <div class="input-grid">
            <div class="form-group">
                <label>Default RP Rate</label>
                <div class="calc-input-wrapper">
                    <input type="number" name="default_rp_rate" class="form-control" step="0.01" value="<?= $settings['default_rp_rate'] ?>">
                    <span class="unit-label">Rs</span>
                </div>
            </div>
            <div class="form-group">
                <label>Default Gram Rate</label>
                <div class="calc-input-wrapper">
                    <input type="number" name="default_gram_rate" class="form-control" step="0.01" value="<?= $settings['default_gram_rate'] ?>">
                    <span class="unit-label">Rs</span>
                </div>
            </div>
            <div class="form-group">
                <label>Default Waste Rate</label>
                <div class="calc-input-wrapper">
                    <input type="number" name="default_waste_rate" class="form-control" step="0.001" value="<?= $settings['default_waste_rate'] ?>">
                    <span class="unit-label">g</span>
                </div>
            </div>
            <div class="form-group">
                <label>Default Ratti Rate</label>
                <div class="calc-input-wrapper">
                    <input type="number" name="default_ratti_rate" class="form-control" step="0.001" value="<?= $settings['default_ratti_rate'] ?>">
                    <span class="unit-label">g</span>
                </div>
            </div>
        </div>

        <div style="margin-top:24px;">
            <button type="submit" class="btn btn-gold"><i class="bi bi-save"></i> Save Settings</button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>