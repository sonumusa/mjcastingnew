<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$page_title = "Edit Expense";
$pdo = getDB();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM wax_expenses WHERE id = ?");
$stmt->execute([$id]);
$expense = $stmt->fetch();

if (!$expense) die("Expense not found");

$categories = $pdo->query("SELECT name FROM wax_expense_categories WHERE active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/templates/header.php';
?>

<div class="card" style="max-width: 500px; margin: 0 auto;">
    <h2>Edit Expense</h2>
    <form method="POST" action="expense_update.php">
        <input type="hidden" name="id" value="<?= $id ?>">
        
        <label>Date</label>
        <input type="date" name="expense_date" value="<?= $expense['expense_date'] ?>" required>
        
        <label>Category</label>
        <select name="category" required>
            <?php foreach ($categories as $c): ?>
                <option value="<?= $c ?>" <?= $c == $expense['category'] ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
        </select>
        
        <label>Amount</label>
        <input type="number" name="amount" value="<?= $expense['amount'] ?>" step="0.01" required>
        
        <label>Notes</label>
        <input type="text" name="notes" value="<?= htmlspecialchars($expense['notes']) ?>">
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary" style="flex: 1;">Update</button>
            <a href="expenses.php" class="btn btn-danger" style="flex: 1; text-align: center; text-decoration: none;">Cancel</a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/templates/footer.php'; ?>
