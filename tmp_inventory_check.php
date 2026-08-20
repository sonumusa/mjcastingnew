<?php
require 'config.php';
require 'functions/ledger_functions.php';
$db = getDB();

$openingBalance = (float)$db->query("SELECT COALESCE(SUM(opening_balance),0) FROM customers WHERE status='active'")->fetchColumn();
$receiptKhalis = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE deleted_at IS NULL OR deleted_at='0000-00-00 00:00:00'")->fetchColumn();
$invoiceReceivedKhalis = (float)$db->query("SELECT COALESCE(SUM(ir.khalis_weight),0) FROM invoice_receives ir INNER JOIN invoices i ON i.id=ir.invoice_id WHERE COALESCE(i.status,'active')='active'")->fetchColumn();
$multipleReceivedKhalis = (float)$db->query("SELECT COALESCE(SUM(imr.khalis_weight),0) FROM invoice_multiple_receives imr INNER JOIN invoice_multiple im ON im.id=imr.invoice_multiple_id WHERE COALESCE(im.status,'active')='active'")->fetchColumn();
$invoiceGivenWeight = (float)$db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoices WHERE COALESCE(status,'active')='active'")->fetchColumn();
$multipleGivenWeight = (float)$db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoice_multiple WHERE COALESCE(status,'active')='active'")->fetchColumn();
$goldGiveWeight = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE deleted_at IS NULL OR deleted_at='0000-00-00 00:00:00'")->fetchColumn();

$closing = $openingBalance + ($receiptKhalis+$invoiceReceivedKhalis+$multipleReceivedKhalis) - ($invoiceGivenWeight+$multipleGivenWeight+$goldGiveWeight);
$netInvoices = (float)$db->query("SELECT COALESCE(SUM(effective_gold-total_received_khalis-wasooli),0) FROM invoices WHERE status='active'")->fetchColumn();
$netMulti = (float)$db->query("SELECT COALESCE(SUM(effective_gold-total_received_khalis-wasooli),0) FROM invoice_multiple WHERE status='active'")->fetchColumn();
$netReceipts = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE deleted_at IS NULL")->fetchColumn();
$netGives = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE deleted_at IS NULL")->fetchColumn();
$closingLedgerStyle = $openingBalance + $netInvoices + $netMulti + $netGives - $netReceipts;

$customers = $db->query("SELECT id FROM customers WHERE status='active'")->fetchAll();
$sumCurrentBalances = 0.0;
foreach ($customers as $c) {
    $sumCurrentBalances += getCustomerCurrentBalance((int)$c['id']);
}

echo json_encode([
    'opening' => $openingBalance,
    'receipt' => $receiptKhalis,
    'singleRec' => $invoiceReceivedKhalis,
    'multiRec' => $multipleReceivedKhalis,
    'singleGiven' => $invoiceGivenWeight,
    'multiGiven' => $multipleGivenWeight,
    'goldGive' => $goldGiveWeight,
    'closingInventoryStyle' => $closing,
    'netInvoices' => $netInvoices,
    'netMulti' => $netMulti,
    'netReceipts' => $netReceipts,
    'netGives' => $netGives,
    'closingLedgerStyle' => $closingLedgerStyle,
    'sumCurrentBalances' => $sumCurrentBalances,
], JSON_PRETTY_PRINT), PHP_EOL;
