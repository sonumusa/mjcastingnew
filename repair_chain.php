<?php
// ============================================================
// repair_chain.php — EK DAAFA chalane ke liye
// ------------------------------------------------------------
// Ye file app ke ROOT folder mein rakhein (jahan config.php hai),
// browser mein kholein, phir DELETE kar dein.
// Kaam: har customer ki invoice chain (previous_balance /
// remaining_balance) dobara sahi calculate karta hai — ab
// invoice_multiple bhi shamil hai.
// ============================================================
require_once __DIR__ . '/config.php';
requireAuth();
require_once __DIR__ . '/functions/gold_calculations.php';

header('Content-Type: text/plain; charset=utf-8');

$db = getDB();
$customers = $db->query("SELECT id, name FROM customers ORDER BY id")->fetchAll(PDO::FETCH_KEY_PAIR);

$ok = 0;
foreach ($customers as $id => $name) {
    try {
        recalculateChain((int)$id);
        echo "OK   #$id  $name\n";
        $ok++;
    } catch (Throwable $e) {
        echo "FAIL #$id  $name  ->  " . $e->getMessage() . "\n";
    }
}

try {
    recalculateInventoryStock();
    echo "\nInventory stock bhi rebuild ho gaya.\n";
} catch (Throwable $e) {
    echo "\nInventory rebuild error: " . $e->getMessage() . "\n";
}

echo "\nDone: $ok / " . count($customers) . " customers ki chain rebuild ho gayi.\n";
echo "AB IS FILE KO DELETE KAR DEIN.\n";
