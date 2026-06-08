<?php
// ============================================================
// Ledger & Report Functions
// ============================================================

/**
 * Get customer ledger with true chronological transaction flow
 * 
 * Balance = Opening + Effective Gold - Total Received Khalis - Wasooli - Receipt Khalis
 */
function getCustomerLedger(int $customerId, ?string $from = null, ?string $to = null): array {
    $db = getDB();
    
    // Get customer
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        return [];
    }
    
    // Get invoices
    $invoiceSql = "SELECT *, 'invoice' as txn_type, invoice_date as txn_date, id as txn_sort FROM invoices 
                   WHERE customer_id = ? AND status = 'active'";
    $invoiceParams = [$customerId];
    
    if ($from) {
        $invoiceSql .= " AND invoice_date >= ?";
        $invoiceParams[] = $from;
    }
    if ($to) {
        $invoiceSql .= " AND invoice_date <= ?";
        $invoiceParams[] = $to;
    }
    $invoiceSql .= " ORDER BY invoice_date ASC, id ASC";
    
    $stmt = $db->prepare($invoiceSql);
    $stmt->execute($invoiceParams);
    $invoices = $stmt->fetchAll();
    
    // Get receipts
    $receiptSql = "SELECT *, 'receipt' as txn_type, receipt_date as txn_date, id as txn_sort FROM gold_receipts 
                   WHERE customer_id = ?";
    $receiptParams = [$customerId];
    
    if ($from) {
        $receiptSql .= " AND receipt_date >= ?";
        $receiptParams[] = $from;
    }
    if ($to) {
        $receiptSql .= " AND receipt_date <= ?";
        $receiptParams[] = $to;
    }
    $receiptSql .= " ORDER BY receipt_date ASC, id ASC";
    
    $stmt = $db->prepare($receiptSql);
    $stmt->execute($receiptParams);
    $receipts = $stmt->fetchAll();
    
    // Build transactions
    $transactions = [];
    
    foreach ($invoices as $inv) {
        $netAmount = (float)($inv['effective_gold'] ?? 0) 
                   - (float)($inv['total_received_khalis'] ?? 0) 
                   - (float)($inv['wasooli'] ?? 0);
        
        $transactions[] = [
            'type' => 'invoice',
            'date' => $inv['invoice_date'],
            'sort_id' => $inv['id'] * 2,
            'invoice_no' => $inv['invoice_no'],
            'effective_gold' => (float)($inv['effective_gold'] ?? 0),
            'received_khalis' => (float)($inv['total_received_khalis'] ?? 0),
            'wasooli' => (float)($inv['wasooli'] ?? 0),
            'net_amount' => round($netAmount, 3),
            'data' => $inv,
        ];
    }
    
    foreach ($receipts as $rec) {
        $transactions[] = [
            'type' => 'receipt',
            'date' => $rec['receipt_date'],
            'sort_id' => $rec['id'] * 2 + 1,
            'receipt_no' => $rec['receipt_no'] ?? 'RCV-' . str_pad($rec['id'], 5, '0', STR_PAD_LEFT),
            'khalis_weight' => (float)($rec['total_khalis_weight'] ?? 0),
            'net_amount' => -((float)($rec['total_khalis_weight'] ?? 0)),
            'data' => $rec,
        ];
    }
    
    // Sort by date then by sort_id
    usort($transactions, function ($a, $b) {
        $cmp = strcmp($a['date'], $b['date']);
        if ($cmp !== 0) return $cmp;
        return $a['sort_id'] <=> $b['sort_id'];
    });
    
    // Calculate running balance
    $runningBalance = (float)($customer['opening_balance'] ?? 0);
    
    foreach ($transactions as &$txn) {
        $txn['running_balance_before'] = round($runningBalance, 3);
        $runningBalance += $txn['net_amount'];
        $txn['running_balance_after'] = round($runningBalance, 3);
    }
    unset($txn);
    
    // Totals
    $totalEffectiveGold = array_sum(array_column($invoices, 'effective_gold'));
    $totalInvoiceReceived = array_sum(array_column($invoices, 'total_received_khalis'));
    $totalReceiptKhalis = array_sum(array_column($receipts, 'total_khalis_weight'));
    $totalWasooli = array_sum(array_column($invoices, 'wasooli'));
    $totalReceived = $totalInvoiceReceived + $totalReceiptKhalis;
    
    return [
        'customer' => $customer,
        'opening_balance' => round((float)$customer['opening_balance'] ?? 0, 3),
        'transactions' => $transactions,
        'invoices' => $invoices,
        'receipts' => $receipts,
        'total_casting' => round(array_sum(array_column($invoices, 'casting_weight')), 3),
        'total_waste' => round(array_sum(array_column($invoices, 'waste_weight')), 3),
        'total_weight' => round(array_sum(array_column($invoices, 'total_weight')), 3),
        'total_gold_khalis' => round(array_sum(array_column($invoices, 'gold_khalis')), 3),
        'total_effective_gold' => round($totalEffectiveGold, 3),
        'total_received_khalis' => round($totalReceived, 3),
        'total_invoice_received' => round($totalInvoiceReceived, 3),
        'total_receipt_khalis' => round($totalReceiptKhalis, 3),
        'total_wasooli' => round($totalWasooli, 3),
        'total_rp_mazdori' => round(array_sum(array_column($invoices, 'rp_mazdori_amount')), 3),
        'current_balance' => round($runningBalance, 3),
        'calculation_breakdown' => [
            'opening' => round((float)$customer['opening_balance'] ?? 0, 3),
            '+ given' => round($totalEffectiveGold, 3),
            '- received' => round($totalReceived, 3),
            '- wasooli' => round($totalWasooli, 3),
            '= balance' => round($runningBalance, 3),
        ],
        'formula' => 'Balance = Opening + Given - Received - Wasooli',
    ];
}

/**
 * Get daily report
 */
function getDailyReport(string $date): array {
    $db = getDB();
    
    $invoices = $db->prepare("SELECT i.*, c.name as customer_name 
                              FROM invoices i 
                              LEFT JOIN customers c ON c.id = i.customer_id 
                              WHERE i.status = 'active' AND i.invoice_date = ? 
                              ORDER BY i.invoice_date, i.id");
    $invoices->execute([$date]);
    $invoicesList = $invoices->fetchAll();
    
    $receipts = $db->prepare("SELECT r.*, c.name as customer_name 
                              FROM gold_receipts r 
                              LEFT JOIN customers c ON c.id = r.customer_id 
                              WHERE r.receipt_date = ? 
                              ORDER BY r.receipt_date, r.id");
    $receipts->execute([$date]);
    $receiptsList = $receipts->fetchAll();
    
    return buildReportData($date, $invoicesList, $receiptsList);
}

/**
 * Get daily report for date range
 */
function getDailyReportRange(string $from, string $to): array {
    $db = getDB();
    
    $invoices = $db->prepare("SELECT i.*, c.name as customer_name 
                              FROM invoices i 
                              LEFT JOIN customers c ON c.id = i.customer_id 
                              WHERE i.status = 'active' AND i.invoice_date >= ? AND i.invoice_date <= ? 
                              ORDER BY i.invoice_date, i.id");
    $invoices->execute([$from, $to]);
    $invoicesList = $invoices->fetchAll();
    
    $receipts = $db->prepare("SELECT r.*, c.name as customer_name 
                              FROM gold_receipts r 
                              LEFT JOIN customers c ON c.id = r.customer_id 
                              WHERE r.receipt_date >= ? AND r.receipt_date <= ? 
                              ORDER BY r.receipt_date, r.id");
    $receipts->execute([$from, $to]);
    $receiptsList = $receipts->fetchAll();
    
    return buildReportData($from, $invoicesList, $receiptsList, true, $to);
}

/**
 * Build report data
 */
function buildReportData(string $primaryDate, array $invoices, array $receipts, bool $isRange = false, ?string $endDate = null): array {
    $totalGoldKhalis = 0;
    $totalEffectiveGold = 0;
    $totalReceivedInvoice = 0;
    $totalWasooli = 0;
    
    foreach ($invoices as $inv) {
        $totalGoldKhalis += (float)($inv['gold_khalis'] ?? 0);
        $totalEffectiveGold += (float)($inv['effective_gold'] ?? 0);
        $totalReceivedInvoice += (float)($inv['total_received_khalis'] ?? 0);
        $totalWasooli += (float)($inv['wasooli'] ?? 0);
    }
    
    $totalReceiptKhalis = 0;
    foreach ($receipts as $rec) {
        $totalReceiptKhalis += (float)($rec['total_khalis_weight'] ?? 0);
    }
    
    $totalReceivedCombined = $totalReceivedInvoice + $totalReceiptKhalis;
    
    return [
        'is_range' => $isRange,
        'date' => $primaryDate,
        'date_end' => $endDate,
        'date_string' => $primaryDate,
        'date_range' => $isRange ? $primaryDate . ' to ' . $endDate : $primaryDate,
        'total_invoices' => count($invoices),
        'total_receipts' => count($receipts),
        'total_gold_khalis' => round($totalGoldKhalis, 3),
        'total_grand_total' => round($totalEffectiveGold, 3),
        'total_received_invoice' => round($totalReceivedInvoice, 3),
        'total_receipt_khalis' => round($totalReceiptKhalis, 3),
        'total_received_combined' => round($totalReceivedCombined, 3),
        'total_wasooli' => round($totalWasooli, 3),
        'net_movement' => round($totalEffectiveGold - $totalReceivedInvoice - $totalWasooli - $totalReceiptKhalis, 3),
        'invoices' => $invoices,
        'receipts' => $receipts,
    ];
}

/**
 * Get customer report
 */
function getCustomerReport(int $customerId, string $from, string $to): array {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) return [];
    
    // Invoices
    $stmt = $db->prepare("SELECT * FROM invoices WHERE customer_id = ? AND status = 'active' 
                          AND invoice_date >= ? AND invoice_date <= ? 
                          ORDER BY invoice_date ASC, id ASC");
    $stmt->execute([$customerId, $from, $to]);
    $invoices = $stmt->fetchAll();
    
    // Receipts
    $stmt = $db->prepare("SELECT * FROM gold_receipts WHERE customer_id = ? 
                          AND receipt_date >= ? AND receipt_date <= ? 
                          ORDER BY receipt_date ASC, id ASC");
    $stmt->execute([$customerId, $from, $to]);
    $receipts = $stmt->fetchAll();
    
    // Build transactions
    $transactions = [];
    $allInvs = $db->prepare("SELECT id, effective_gold, wasooli, total_received_khalis, invoice_no, invoice_date 
                            FROM invoices WHERE customer_id = ? AND status = 'active' 
                            AND invoice_date <= ? ORDER BY invoice_date ASC, id ASC");
    $allInvs->execute([$customerId, $to]);
    $allInvoices = $allInvs->fetchAll();
    
    $allRecs = $db->prepare("SELECT id, total_khalis_weight, receipt_no, receipt_date 
                            FROM gold_receipts WHERE customer_id = ? 
                            AND receipt_date <= ? ORDER BY receipt_date ASC, id ASC");
    $allRecs->execute([$customerId, $to]);
    $allReceipts = $allRecs->fetchAll();
    
    // Start with opening balance
    $rangeBalance = (float)$customer['opening_balance'];
    
    // Process all transactions up to date range
    $allTransactions = [];
    foreach ($allInvoices as $inv) {
        $net = (float)$inv['effective_gold'] - (float)$inv['wasooli'] - (float)$inv['total_received_khalis'];
        $allTransactions[] = [
            'type' => 'invoice',
            'date' => $inv['invoice_date'],
            'id' => $inv['id'],
            'amount' => $net,
        ];
    }
    foreach ($allReceipts as $rec) {
        $allTransactions[] = [
            'type' => 'receipt',
            'date' => $rec['receipt_date'],
            'id' => $rec['id'],
            'amount' => -((float)$rec['total_khalis_weight']),
        ];
    }
    usort($allTransactions, function($a, $b) {
        $c = strcmp($a['date'], $b['date']);
        return $c !== 0 ? $c : $a['id'] - $b['id'];
    });
    
    foreach ($allTransactions as $txn) {
        $rangeBalance += $txn['amount'];
    }
    
    $totalGoldKhalis = array_sum(array_column($invoices, 'gold_khalis'));
    $totalEffective = array_sum(array_column($invoices, 'effective_gold'));
    $totalInvoiceRec = array_sum(array_column($invoices, 'total_received_khalis'));
    $totalWasooli = array_sum(array_column($invoices, 'wasooli'));
    $totalRecKhalis = array_sum(array_column($receipts, 'total_khalis_weight'));
    
    // Build transaction list for display
    $displayTransactions = [];
    foreach ($invoices as $inv) {
        $net = (float)$inv['effective_gold'] - (float)$inv['wasooli'] - (float)$inv['total_received_khalis'];
        $displayTransactions[] = [
            'type' => 'invoice',
            'date' => $inv['invoice_date'],
            'sort_id' => $inv['id'] * 2,
            'invoice_no' => $inv['invoice_no'],
            'effective_gold' => (float)$inv['effective_gold'],
            'received_khalis' => (float)$inv['total_received_khalis'],
            'wasooli' => (float)$inv['wasooli'],
            'amount' => round($net, 3),
            'data' => $inv,
        ];
    }
    foreach ($receipts as $rec) {
        $displayTransactions[] = [
            'type' => 'receipt',
            'date' => $rec['receipt_date'],
            'sort_id' => $rec['id'] * 2 + 1,
            'receipt_no' => $rec['receipt_no'],
            'khalis_weight' => (float)$rec['total_khalis_weight'],
            'amount' => -((float)$rec['total_khalis_weight']),
            'data' => $rec,
        ];
    }
    usort($displayTransactions, function($a, $b) {
        $c = strcmp($a['date'], $b['date']);
        return $c !== 0 ? $c : $a['sort_id'] - $b['sort_id'];
    });
    
    $runningBal = (float)$customer['opening_balance'];
    foreach ($displayTransactions as &$txn) {
        $runningBal += $txn['amount'];
        $txn['running_balance'] = round($runningBal, 3);
    }
    unset($txn);
    
    return [
        'customer' => $customer,
        'date_range' => [
            'from' => date('d/m/Y', strtotime($from)),
            'to' => date('d/m/Y', strtotime($to)),
        ],
        'opening_balance' => round((float)$customer['opening_balance'], 3),
        'total_invoices' => count($invoices),
        'total_gold_khalis' => round($totalGoldKhalis, 3),
        'total_grand_total' => round($totalEffective, 3),
        'total_received_khalis' => round($totalInvoiceRec + $totalRecKhalis, 3),
        'total_wasooli' => round($totalWasooli, 3),
        'total_receipts' => count($receipts),
        'current_balance' => round($rangeBalance, 3),
        'transactions' => $displayTransactions,
        'invoices' => $invoices,
        'receipts' => $receipts,
    ];
}

/**
 * Get dashboard statistics
 */
function getDashboardStats(): array {
    $db = getDB();
    
    $totalCustomers = $db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $totalInvoices = $db->query("SELECT COUNT(*) FROM invoices WHERE status = 'active'")->fetchColumn();
    $totalReceipts = $db->query("SELECT COUNT(*) FROM gold_receipts")->fetchColumn();
    
    $stmt = $db->query("SELECT COALESCE(SUM(effective_gold), 0) FROM invoices WHERE status = 'active'");
    $totalGoldGiven = (float)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COALESCE(SUM(total_received_khalis), 0) FROM invoices WHERE status = 'active'");
    $invRec = (float)$stmt->fetchColumn();
    $stmt = $db->query("SELECT COALESCE(SUM(total_khalis_weight), 0) FROM gold_receipts");
    $recKhalis = (float)$stmt->fetchColumn();
    $totalReceived = $invRec + $recKhalis;
    
    $stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
    $inventory = $stmt->fetch();
    $closingBalance = $inventory ? (float)$inventory['closing_balance'] : 0;
    
    $stmt = $db->query("SELECT COALESCE(SUM(opening_balance), 0) FROM customers");
    $totalOpening = (float)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COALESCE(SUM(rp_mazdori_amount), 0) FROM invoices WHERE status = 'active'");
    $totalRpMazdori = (float)$stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COALESCE(SUM(wasooli), 0) FROM invoices WHERE status = 'active'");
    $totalWasooli = (float)$stmt->fetchColumn();
    
    $today = date('Y-m-d');
    $stmt = $db->prepare("SELECT COUNT(*) FROM invoices WHERE status = 'active' AND invoice_date = ?");
    $stmt->execute([$today]);
    $todayInvoices = (int)$stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM gold_receipts WHERE receipt_date = ?");
    $stmt->execute([$today]);
    $todayReceipts = (int)$stmt->fetchColumn();
    
    // Calculate remaining balance
    $stmt = $db->query("SELECT id, opening_balance FROM customers WHERE status = 'active'");
    $customers = $stmt->fetchAll();
    $totalRemaining = 0;
    foreach ($customers as $c) {
        $bal = (float)$c['opening_balance'];
        $invStmt = $db->prepare("SELECT effective_gold, wasooli, total_received_khalis FROM invoices 
                                WHERE customer_id = ? AND status = 'active' ORDER BY invoice_date ASC, id ASC");
        $invStmt->execute([$c['id']]);
        foreach ($invStmt->fetchAll() as $inv) {
            $bal += (float)$inv['effective_gold'] - (float)$inv['wasooli'] - (float)$inv['total_received_khalis'];
        }
        $recStmt = $db->prepare("SELECT total_khalis_weight FROM gold_receipts WHERE customer_id = ?");
        $recStmt->execute([$c['id']]);
        foreach ($recStmt->fetchAll() as $rec) {
            $bal -= (float)$rec['total_khalis_weight'];
        }
        $totalRemaining += $bal;
    }
    
    // Party types count
    $stmt = $db->query("SELECT party_type, COUNT(*) as cnt FROM customers WHERE status = 'active' GROUP BY party_type");
    $partyTypes = ['customer' => 0, 'dukandar' => 0, 'karigar' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $partyTypes[$row['party_type']] = (int)$row['cnt'];
    }
    
    return [
        'total_customers' => $totalCustomers,
        'total_invoices' => $totalInvoices,
        'total_gold_receipts' => $totalReceipts,
        'total_gold_khalis_given' => round($totalGoldGiven, 3),
        'total_received_khalis' => round($totalReceived, 3),
        'total_inventory_closing' => round($closingBalance, 3),
        'total_opening_stock' => round($totalOpening, 3),
        'total_rp_mazdori' => round($totalRpMazdori, 3),
        'total_wasooli' => round($totalWasooli, 3),
        'total_remaining_balance' => round($totalRemaining, 3),
        'today_invoices' => $todayInvoices,
        'today_receipts' => $todayReceipts,
        'customers_by_type' => $partyTypes,
    ];
}

/**
 * Get current balance for a customer
 */
function getCustomerCurrentBalance(int $customerId): float {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) return 0;
    
    $balance = (float)$customer['opening_balance'];
    
    $stmt = $db->prepare("SELECT effective_gold, wasooli, total_received_khalis FROM invoices 
                          WHERE customer_id = ? AND status = 'active' 
                          ORDER BY invoice_date ASC, id ASC");
    $stmt->execute([$customerId]);
    foreach ($stmt->fetchAll() as $inv) {
        $balance += (float)$inv['effective_gold'] - (float)$inv['wasooli'] - (float)$inv['total_received_khalis'];
    }
    
    $stmt = $db->prepare("SELECT total_khalis_weight FROM gold_receipts WHERE customer_id = ?");
    $stmt->execute([$customerId]);
    foreach ($stmt->fetchAll() as $rec) {
        $balance -= (float)$rec['total_khalis_weight'];
    }
    
    return round($balance, 3);
}