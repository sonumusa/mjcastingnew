<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'];
    $cats = $_POST['categories'] ?? [];
    $amounts = $_POST['amounts'] ?? [];
    $notes = $_POST['notes'] ?? [];

    if ($date) {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO wax_expenses (expense_date, category, amount, notes) VALUES (?, ?, ?, ?)");
        
        for ($i = 0; $i < count($cats); $i++) {
            $cat = safe_input($cats[$i]);
            $amount = floatval($amounts[$i]);
            $note = safe_input($notes[$i]);
            
            if (!empty($cat) && $amount > 0) {
                $stmt->execute([$date, $cat, $amount, $note]);
            }
        }
    }
   redirect('wax/expenses.php');
}
