<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = safe_input($_POST['name'] ?? '');
    $name_urdu = safe_input($_POST['name_urdu']);
    $category = safe_input($_POST['category']);
    $default_rate = !empty($_POST['default_rate']) ? floatval($_POST['default_rate']) : null;
    $active = intval($_POST['active']);

    if (empty($name_urdu)) {
        die("Name (Urdu) is required");
    }

    $pdo = getDB();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE wax_items SET name=?, name_urdu=?, category=?, default_rate=?, active=? WHERE id=?");
        $stmt->execute([$name, $name_urdu, $category, $default_rate, $active, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wax_items (name, name_urdu, category, default_rate, active) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $name_urdu, $category, $default_rate, $active]);
    }

    redirect('wax/items.php');
}
