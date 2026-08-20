<?php
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';
$id=(int)query('id',0); $db=getDB();
$stmt=$db->prepare("SELECT * FROM gold_gives WHERE id=? AND deleted_at IS NULL"); $stmt->execute([$id]); $give=$stmt->fetch();
if(!$give){ setFlash('error','Gold give not found.'); } else { $db->prepare("UPDATE gold_gives SET deleted_at=NOW() WHERE id=?")->execute([$id]); recalculateChain((int)$give['customer_id']); recalculateInventoryStock(); setFlash('success','Gold give deleted successfully.'); }
redirect('gold-gives/index.php');
