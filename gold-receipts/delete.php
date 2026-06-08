<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM gold_receipts WHERE id = ?");
$stmt->execute([$id]);
$receipt = $stmt->fetch();

if (!$receipt) {
    setFlash('error', 'Receipt not found.');
} else {
    $db->prepare("UPDATE gold_receipts SET deleted_at = NOW() WHERE id = ?")->execute([$id]);
    setFlash('success', 'Receipt deleted successfully.');
}

redirect('gold-receipts/index.php');