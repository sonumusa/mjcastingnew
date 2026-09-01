<?php
// ============================================================
// diag_balance.php — sirf verify karne ke liye (optional)
// ------------------------------------------------------------
// Root folder mein rakhein, browser mein kholein:
//   diag_balance.php?customer_id=123
// Fix ke baad dono numbers EQUAL ane chahiye.
// Kam khatam hone ke baad file DELETE kar dein.
// ============================================================
require_once __DIR__ . '/config.php';
requireAuth();
require_once __DIR__ . '/functions/gold_calculations.php';

header('Content-Type: text/plain; charset=utf-8');

$cid = (int)($_GET['customer_id'] ?? 0);
if (!$cid) {
    exit("Use: diag_balance.php?customer_id=CUSTOMER_ID\n");
}

echo "customer_id = $cid\n\n";

echo "getPreviousBalance()        (DB save wala) : ";
try {
    echo getPreviousBalance($cid);
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
echo "\n";

echo "getCustomerCurrentBalance() (screen wala)  : ";
if (function_exists('getCustomerCurrentBalance')) {
    try {
        echo getCustomerCurrentBalance($cid);
    } catch (Throwable $e) {
        echo "ERROR: " . $e->getMessage();
    }
} else {
    echo "(function ledger_functions.php mein nahi mila na-load)";
}
echo "\n\nDono numbers barabar hone chahiye.\n";
