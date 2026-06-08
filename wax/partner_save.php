<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = safe_input($_POST['name']);
    $share_percentage = floatval($_POST['share_percentage']);
    $active = intval($_POST['active']);

    if (empty($name)) {
        die("Name is required");
    }

    $pdo = getDB();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE wax_partners SET name=?, share_percentage=?, active=? WHERE id=?");
        $stmt->execute([$name, $share_percentage, $active, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wax_partners (name, share_percentage, active) VALUES (?, ?, ?)");
        $stmt->execute([$name, $share_percentage, $active]);
    }

    redirect('wax/partners.php');
}
