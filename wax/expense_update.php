<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $date = $_POST['expense_date'];
    $category = safe_input($_POST['category']);
    $amount = floatval($_POST['amount']);
    $notes = safe_input($_POST['notes']);

    if ($id && $amount > 0) {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE wax_expenses SET expense_date=?, category=?, amount=?, notes=? WHERE id=?");
        $stmt->execute([$date, $category, $amount, $notes, $id]);
    }
    redirect('wax/expenses.php');
}
