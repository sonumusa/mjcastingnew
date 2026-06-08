<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$id = (int) query('id', 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM invoices WHERE id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('error', 'Invoice not found.');
} else {
    try {
        $db->beginTransaction();
        $customerId = $invoice['customer_id'];
        
        $stmt = $db->prepare("UPDATE invoices SET status = 'cancelled', deleted_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        
        recalculateChain($customerId);
        
        $db->commit();
        setFlash('success', "Invoice {$invoice['invoice_no']} cancelled successfully.");
    } catch (Exception $e) {
        $db->rollBack();
        setFlash('error', 'Failed to cancel invoice: ' . $e->getMessage());
    }
}

redirect('invoices/index.php');