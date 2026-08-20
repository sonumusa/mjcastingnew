<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';
$id=(int)query('id',0); $db=getDB();
$stmt=$db->prepare("SELECT * FROM invoice_multiple WHERE id=?"); $stmt->execute([$id]); $invoice=$stmt->fetch();
if(!$invoice){ setFlash('error','Multiple invoice not found.'); }
else { $db->prepare("UPDATE invoice_multiple SET status='cancelled', deleted_at=NOW(), updated_by=? WHERE id=?")->execute([$_SESSION['user_id']??null,$id]); recalculateChain((int)$invoice['customer_id']); recalculateInventoryStock(); setFlash('success','Multiple invoice cancelled successfully.'); }
redirect('invoice-multiple/index.php');
