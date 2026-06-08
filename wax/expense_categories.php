<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Expense Categories";
$pdo = getDB();

// Handle Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = safe_input($_POST['name']);
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO wax_expense_categories (name) VALUES (?)");
        $stmt->execute([$name]);
    }
}

// Create Table if not exists (Auto-migration as per instruction)
$pdo->exec("CREATE TABLE IF NOT EXISTS expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    active TINYINT(1) DEFAULT 1
)");

$categories = $pdo->query("SELECT * FROM wax_expense_categories ORDER BY name ASC")->fetchAll();

include __DIR__ . '/templates/header.php';
?>

<div class="card">
    <h2>Expense Categories</h2>
    <form method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
        <input type="text" name="name" placeholder="New Category Name (e.g. Tea, Rent)" required style="flex: 1;">
        <button type="submit" class="btn btn-primary">Add</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['name']) ?></td>
                <td>Active</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <a href="expenses.php" class="btn btn-primary">Back to Expenses</a>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
