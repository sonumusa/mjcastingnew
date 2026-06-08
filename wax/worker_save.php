<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $name = safe_input($_POST['name']);
    $default_percentage = floatval($_POST['default_percentage']);
    $active = intval($_POST['active']);
    $commission_type = $_POST['commission_type'] ?? 'percentage';
    $commission_type = in_array($commission_type, ['percentage', 'fixed_per_unit']) ? $commission_type : 'percentage';
    $commission_rate = floatval($_POST['commission_rate'] ?? 0);

    if (empty($name)) {
        die("Name is required");
    }

    $pdo = getDB();

    if ($id) {
        $stmt = $pdo->prepare("UPDATE wax_workers SET name=?, default_percentage=?, active=?, commission_type=?, commission_rate=? WHERE id=?");
        $stmt->execute([$name, $default_percentage, $active, $commission_type, $commission_rate, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO wax_workers (name, default_percentage, active, commission_type, commission_rate) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $default_percentage, $active, $commission_type, $commission_rate]);
    }

    redirect('wax/workers.php');
}
