<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$customer_id = $_GET['customer_id'] ?? 0;
$pdo = getDB();

// Fetch Customer
$stmt = $pdo->prepare("SELECT * FROM wax_customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    die("Customer not found");
}

$page_title = "Price List: " . $customer['name'];

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_ids = $_POST['item_ids'] ?? [];
    $rates = $_POST['rates'] ?? [];

    $pdo->beginTransaction();
    
    // Delete existing rates for this customer to allow updates/removals
    // Actually, simpler is to just UPSERT or Delete/Insert. 
    // Given the constraints, let's just clear and re-insert for simplicity or use ON DUPLICATE KEY UPDATE.
    // However, we only get submitted items. If an item is unchecked/empty, we might want to remove it.
    // For now, let's just insert/update the ones provided.
    
    $stmtInsert = $pdo->prepare("
        INSERT INTO wax_price_list (customer_id, item_id, rate) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE rate = VALUES(rate)
    ");

    foreach ($item_ids as $index => $item_id) {
        $rate = floatval($rates[$index]);
        if ($rate > 0) {
            $stmtInsert->execute([$customer_id, $item_id, $rate]);
        } else {
             // If rate is 0 or empty, we might want to delete it if it exists
             $pdo->prepare("DELETE FROM wax_price_list WHERE customer_id = ? AND item_id = ?")->execute([$customer_id, $item_id]);
        }
    }

    $pdo->commit();
    $message = "Price list updated successfully!";
}

// Fetch all WAX items
$items = $pdo->query("SELECT * FROM wax_items WHERE category = 'wax' AND active = 1 ORDER BY name_urdu ASC")->fetchAll();

// Fetch existing rates
$stmtRates = $pdo->prepare("SELECT item_id, rate FROM wax_price_list WHERE customer_id = ?");
$stmtRates->execute([$customer_id]);
$existing_rates = $stmtRates->fetchAll(PDO::FETCH_KEY_PAIR); // [item_id => rate]

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Price List for <?= htmlspecialchars($customer['name']) ?></h2>
        <a href="customers.php" class="btn btn-danger">Back</a>
    </div>

    <?php if (isset($message)): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <table>
            <thead>
                <tr>
                    <th>Item (Urdu)</th>
                    <th>Default Rate</th>
                    <th>Customer Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td style="font-family: 'Noto Nastaliq Urdu', serif; font-size: 1.2em; direction: rtl; text-align: right;">
                        <?= htmlspecialchars($item['name_urdu']) ?>
                        <input type="hidden" name="item_ids[]" value="<?= $item['id'] ?>">
                    </td>
                    <td><?= $item['default_rate'] > 0 ? format_currency($item['default_rate']) : '-' ?></td>
                    <td>
                        <input type="number" step="0.01" name="rates[]" 
                               value="<?= $existing_rates[$item['id']] ?? '' ?>" 
                               placeholder="Enter rate">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 20px; text-align: right;">
            <button type="submit" class="btn btn-primary">Save Prices</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
