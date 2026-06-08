<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Import Data";
$pdo = getDB();

$type = $_GET['type'] ?? 'items';
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file']['tmp_name'];
    $handle = fopen($file, "r");
    
    if ($handle !== FALSE) {
        // Skip header row
        fgetcsv($handle);
        
        $count = 0;
        try {
            $pdo->beginTransaction();
            
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Remove ID if it's the first column and looks like an ID (optional check, but user might upload exported file)
                // For simplicity, we assume the user downloads the template or export and we just map columns.
                // However, exported CSV has ID at index 0. We should probably ignore index 0 if it looks like an ID and we are doing insert.
                // Or better, ask user to map, but that's complex.
                // Let's assume the CSV structure matches the Export structure exactly.
                
                if ($type == 'items') {
                    // Export: ID, Name Urdu, Category, Default Rate, Active
                    // We ignore ID.
                    $name_urdu = $data[1] ?? '';
                    $category = $data[2] ?? 'wax';
                    $rate = $data[3] ?? 0;
                    $active = $data[4] ?? 1;
                    
                    if ($name_urdu) {
                        $stmt = $pdo->prepare("INSERT INTO wax_items (name_urdu, category, default_rate, active) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name_urdu, $category, $rate, $active]);
                        $count++;
                    }
                }
                elseif ($type == 'customers') {
                    // Export: ID, Name, Phone, Address, Opening Balance
                    $name = $data[1] ?? '';
                    $phone = $data[2] ?? '';
                    $address = $data[3] ?? '';
                    $ob = $data[4] ?? 0;
                    
                    if ($name) {
                        $stmt = $pdo->prepare("INSERT INTO wax_customers (name, phone, address, opening_balance) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$name, $phone, $address, $ob]);
                        $count++;
                    }
                }
                elseif ($type == 'expenses') {
                    // Export: ID, Date, Category, Amount, Notes
                    $date = $data[1] ?? date('Y-m-d');
                    $cat = $data[2] ?? 'General';
                    $amount = $data[3] ?? 0;
                    $notes = $data[4] ?? '';
                    
                    if ($amount > 0) {
                        $stmt = $pdo->prepare("INSERT INTO wax_expenses (expense_date, category, amount, notes) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$date, $cat, $amount, $notes]);
                        $count++;
                    }
                }
            }
            
            $pdo->commit();
            $message = "Successfully imported $count records.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Error: " . $e->getMessage();
        }
        
        fclose($handle);
    } else {
        $message = "Failed to open file.";
    }
}

include __DIR__ . '/templates/header.php';
?>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <h2>Import <?= ucfirst($type) ?></h2>
    
    <?php if ($message): ?>
        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0;"><?= $message ?></div>
    <?php endif; ?>
    
    <p>Upload a CSV file to import. The format should match the Export format.</p>
    <p><strong>Note:</strong> The first column (ID) in the CSV will be ignored during import. New records will be created.</p>
    
    <form method="POST" enctype="multipart/form-data">
        <div style="margin-bottom: 15px;">
            <input type="file" name="csv_file" accept=".csv" required class="form-control">
        </div>
        
        <div style="display: flex; justify-content: space-between;">
            <a href="<?= $type ?>.php" class="btn btn-secondary" style="background: #6c757d; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px;">Back</a>
            <button type="submit" class="btn btn-primary">Import</button>
        </div>
    </form>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
