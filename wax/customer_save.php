<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = safe_input($_POST['name']);
    $contact = safe_input($_POST['contact']);
    $billing_style = safe_input($_POST['billing_style']);
    $opening_balance = floatval($_POST['opening_balance'] ?? 0);
    $active = intval($_POST['active']);

    if (empty($name)) {
        die("Name is required");
    }

    $pdo = getDB();

    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE wax_customers SET name=?, contact=?, billing_style=?, opening_balance=?, active=? WHERE id=?");
        $stmt->execute([$name, $contact, $billing_style, $opening_balance, $active, $id]);
    } else {
        // Create
        $stmt = $pdo->prepare("INSERT INTO wax_customers (name, contact, billing_style, opening_balance, active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $contact, $billing_style, $opening_balance, $active]);
    }

    redirect('wax/customers.php');
}
