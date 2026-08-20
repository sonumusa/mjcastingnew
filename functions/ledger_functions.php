<?php
// ============================================================
// Ledger & Report Functions
// Supports: invoices, gold_receipts, gold_gives, invoice_multiple
// ============================================================

function _sumColumn(string $sql, array $params = []): float {
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    return (float)$stmt->fetchColumn();
}

function _tableReady(string $table): bool {
    return !function_exists('tableExists') || tableExists($table);
}

/**
 * Get customer ledger with true chronological transaction flow.
 * Balance = Opening + Given (invoices + invoice multiple + gold gives) - Received (invoice receives + gold receipts) - Wasooli
 */
function getCustomerLedger(int $customerId, ?string $from = null, ?string $to = null): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    if (!$customer) return [];

    $baseOpening = (float)($customer['opening_balance'] ?? 0);
    $trueOpening = $baseOpening;

    if ($from) {
        $trueOpening += _sumColumn("SELECT COALESCE(SUM(effective_gold - total_received_khalis - wasooli),0) FROM invoices WHERE customer_id=? AND status='active' AND invoice_date < ?", [$customerId, $from]);
        $trueOpening -= _sumColumn("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE customer_id=? AND receipt_date < ? AND deleted_at IS NULL", [$customerId, $from]);
        if (_tableReady('gold_gives')) {
            $trueOpening += _sumColumn("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE customer_id=? AND give_date < ? AND deleted_at IS NULL", [$customerId, $from]);
        }
        if (_tableReady('invoice_multiple')) {
            $trueOpening += _sumColumn("SELECT COALESCE(SUM(effective_gold - total_received_khalis - wasooli),0) FROM invoice_multiple WHERE customer_id=? AND status='active' AND invoice_date < ?", [$customerId, $from]);
        }
    }

    $transactions = [];

    $invoiceSql = "SELECT * FROM invoices WHERE customer_id=? AND status='active'";
    $params = [$customerId];
    if ($from) { $invoiceSql .= " AND invoice_date >= ?"; $params[] = $from; }
    if ($to) { $invoiceSql .= " AND invoice_date <= ?"; $params[] = $to; }
    $invoiceSql .= " ORDER BY invoice_date ASC, id ASC";
    $stmt = $db->prepare($invoiceSql); $stmt->execute($params); $invoices = $stmt->fetchAll();

    foreach ($invoices as $inv) {
        $net = (float)$inv['effective_gold'] - (float)$inv['total_received_khalis'] - (float)$inv['wasooli'];
        $transactions[] = [
            'type' => 'invoice', 'date' => $inv['invoice_date'], 'sort_id' => $inv['id'] * 10 + 1,
            'invoice_no' => $inv['invoice_no'], 'effective_gold' => (float)$inv['effective_gold'],
            'received_khalis' => (float)$inv['total_received_khalis'], 'wasooli' => (float)$inv['wasooli'],
            'khalis_weight' => 0.0, 'net_amount' => round($net, 3), 'data' => $inv,
        ];
    }

    $receiptSql = "SELECT * FROM gold_receipts WHERE customer_id=? AND deleted_at IS NULL";
    $params = [$customerId];
    if ($from) { $receiptSql .= " AND receipt_date >= ?"; $params[] = $from; }
    if ($to) { $receiptSql .= " AND receipt_date <= ?"; $params[] = $to; }
    $receiptSql .= " ORDER BY receipt_date ASC, id ASC";
    $stmt = $db->prepare($receiptSql); $stmt->execute($params); $receipts = $stmt->fetchAll();

    foreach ($receipts as $rec) {
        $k = (float)$rec['total_khalis_weight'];
        $transactions[] = [
            'type' => 'receipt', 'date' => $rec['receipt_date'], 'sort_id' => $rec['id'] * 10 + 2,
            'receipt_no' => $rec['receipt_no'] ?? 'RCV-' . str_pad($rec['id'], 5, '0', STR_PAD_LEFT),
            'effective_gold' => 0.0, 'received_khalis' => $k, 'wasooli' => 0.0, 'khalis_weight' => $k,
            'net_amount' => -$k, 'data' => $rec,
        ];
    }

    $goldGives = [];
    if (_tableReady('gold_gives')) {
        $giveSql = "SELECT * FROM gold_gives WHERE customer_id=? AND deleted_at IS NULL";
        $params = [$customerId];
        if ($from) { $giveSql .= " AND give_date >= ?"; $params[] = $from; }
        if ($to) { $giveSql .= " AND give_date <= ?"; $params[] = $to; }
        $giveSql .= " ORDER BY give_date ASC, id ASC";
        $stmt = $db->prepare($giveSql); $stmt->execute($params); $goldGives = $stmt->fetchAll();
        foreach ($goldGives as $g) {
            $k = (float)$g['total_khalis_weight'];
            $transactions[] = [
                'type' => 'gold_give', 'date' => $g['give_date'], 'sort_id' => $g['id'] * 10 + 3,
                'give_no' => $g['give_no'], 'effective_gold' => $k, 'received_khalis' => 0.0, 'wasooli' => 0.0,
                'khalis_weight' => $k, 'net_amount' => $k, 'data' => $g,
            ];
        }
    }

    $multipleInvoices = [];
    if (_tableReady('invoice_multiple')) {
        $multiSql = "SELECT * FROM invoice_multiple WHERE customer_id=? AND status='active'";
        $params = [$customerId];
        if ($from) { $multiSql .= " AND invoice_date >= ?"; $params[] = $from; }
        if ($to) { $multiSql .= " AND invoice_date <= ?"; $params[] = $to; }
        $multiSql .= " ORDER BY invoice_date ASC, id ASC";
        $stmt = $db->prepare($multiSql); $stmt->execute($params); $multipleInvoices = $stmt->fetchAll();
        foreach ($multipleInvoices as $mi) {
            $net = (float)$mi['effective_gold'] - (float)$mi['total_received_khalis'] - (float)$mi['wasooli'];
            $transactions[] = [
                'type' => 'invoice_multiple', 'date' => $mi['invoice_date'], 'sort_id' => $mi['id'] * 10 + 4,
                'invoice_no' => $mi['invoice_no'], 'effective_gold' => (float)$mi['effective_gold'],
                'received_khalis' => (float)$mi['total_received_khalis'], 'wasooli' => (float)$mi['wasooli'],
                'khalis_weight' => 0.0, 'net_amount' => round($net,3), 'data' => $mi,
            ];
        }
    }

    usort($transactions, fn($a,$b) => ($c = strcmp($a['date'], $b['date'])) !== 0 ? $c : ($a['sort_id'] <=> $b['sort_id']));
    $running = $trueOpening;
    foreach ($transactions as &$txn) {
        $txn['running_balance_before'] = round($running, 3);
        $running += (float)$txn['net_amount'];
        $txn['running_balance_after'] = round($running, 3);
    }
    unset($txn);

    $totalInvoiceGiven = array_sum(array_column($invoices, 'effective_gold'));
    $totalInvoiceReceived = array_sum(array_column($invoices, 'total_received_khalis'));
    $totalWasooli = array_sum(array_column($invoices, 'wasooli'));
    $totalMultiGiven = array_sum(array_column($multipleInvoices, 'effective_gold'));
    $totalMultiReceived = array_sum(array_column($multipleInvoices, 'total_received_khalis'));
    $totalMultiWasooli = array_sum(array_column($multipleInvoices, 'wasooli'));
    $totalReceiptKhalis = array_sum(array_column($receipts, 'total_khalis_weight'));
    $totalGoldGiveKhalis = array_sum(array_column($goldGives, 'total_khalis_weight'));

    $totalGiven = $totalInvoiceGiven + $totalMultiGiven + $totalGoldGiveKhalis;
    $totalReceived = $totalInvoiceReceived + $totalMultiReceived + $totalReceiptKhalis;
    $totalAllWasooli = $totalWasooli + $totalMultiWasooli;

    return [
        'customer' => $customer,
        'opening_balance' => round($trueOpening, 3),
        'transactions' => $transactions,
        'invoices' => $invoices,
        'receipts' => $receipts,
        'gold_gives' => $goldGives,
        'multiple_invoices' => $multipleInvoices,
        'total_casting' => round(array_sum(array_column($invoices,'casting_weight')) + array_sum(array_column($multipleInvoices,'total_casting_weight')), 3),
        'total_waste' => round(array_sum(array_column($invoices,'waste_weight')) + array_sum(array_column($multipleInvoices,'total_waste_weight')), 3),
        'total_weight' => round(array_sum(array_column($invoices,'total_weight')) + array_sum(array_column($multipleInvoices,'total_weight')), 3),
        'total_gold_khalis' => round(array_sum(array_column($invoices,'gold_khalis')) + array_sum(array_column($multipleInvoices,'total_gold_khalis')), 3),
        'total_effective_gold' => round($totalGiven, 3),
        'total_grand_total' => round($totalGiven, 3),
        'total_invoice_effective_gold' => round($totalInvoiceGiven, 3),
        'total_multiple_effective_gold' => round($totalMultiGiven, 3),
        'total_gold_give_khalis' => round($totalGoldGiveKhalis, 3),
        'total_received_khalis' => round($totalReceived, 3),
        'total_invoice_received' => round($totalInvoiceReceived + $totalMultiReceived, 3),
        'total_receipt_khalis' => round($totalReceiptKhalis, 3),
        'total_wasooli' => round($totalAllWasooli, 3),
        'total_rp_mazdori' => round(array_sum(array_column($invoices,'rp_mazdori_amount')) + array_sum(array_column($multipleInvoices,'total_rp_mazdori_amount')), 3),
        'current_balance' => round($running, 3),
        'calculation_breakdown' => [
            'opening' => round($trueOpening, 3), '+ given' => round($totalGiven, 3),
            '- received' => round($totalReceived, 3), '- wasooli' => round($totalAllWasooli, 3), '= balance' => round($running, 3),
        ],
        'formula' => 'Balance = Opening + Given - Received - Wasooli',
    ];
}

function getDailyReport(string $date): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id WHERE i.status='active' AND i.invoice_date=? ORDER BY i.invoice_date, i.id");
    $stmt->execute([$date]); $invoices = $stmt->fetchAll();
    $stmt = $db->prepare("SELECT r.*, c.name as customer_name FROM gold_receipts r LEFT JOIN customers c ON c.id=r.customer_id WHERE r.deleted_at IS NULL AND r.receipt_date=? ORDER BY r.receipt_date, r.id");
    $stmt->execute([$date]); $receipts = $stmt->fetchAll();
    $multi=[]; $gives=[];
    if (_tableReady('invoice_multiple')) { $stmt=$db->prepare("SELECT im.*, c.name as customer_name FROM invoice_multiple im LEFT JOIN customers c ON c.id=im.customer_id WHERE im.status='active' AND im.invoice_date=? ORDER BY im.invoice_date, im.id"); $stmt->execute([$date]); $multi=$stmt->fetchAll(); }
    if (_tableReady('gold_gives')) { $stmt=$db->prepare("SELECT g.*, c.name as customer_name FROM gold_gives g LEFT JOIN customers c ON c.id=g.customer_id WHERE g.deleted_at IS NULL AND g.give_date=? ORDER BY g.give_date, g.id"); $stmt->execute([$date]); $gives=$stmt->fetchAll(); }
    return buildReportData($date, $invoices, $receipts, false, null, $multi, $gives);
}

function getDailyReportRange(string $from, string $to): array {
    $db = getDB();
    $stmt = $db->prepare("SELECT i.*, c.name as customer_name FROM invoices i LEFT JOIN customers c ON c.id=i.customer_id WHERE i.status='active' AND i.invoice_date>=? AND i.invoice_date<=? ORDER BY i.invoice_date, i.id");
    $stmt->execute([$from,$to]); $invoices = $stmt->fetchAll();
    $stmt = $db->prepare("SELECT r.*, c.name as customer_name FROM gold_receipts r LEFT JOIN customers c ON c.id=r.customer_id WHERE r.deleted_at IS NULL AND r.receipt_date>=? AND r.receipt_date<=? ORDER BY r.receipt_date, r.id");
    $stmt->execute([$from,$to]); $receipts = $stmt->fetchAll();
    $multi=[]; $gives=[];
    if (_tableReady('invoice_multiple')) { $stmt=$db->prepare("SELECT im.*, c.name as customer_name FROM invoice_multiple im LEFT JOIN customers c ON c.id=im.customer_id WHERE im.status='active' AND im.invoice_date>=? AND im.invoice_date<=? ORDER BY im.invoice_date, im.id"); $stmt->execute([$from,$to]); $multi=$stmt->fetchAll(); }
    if (_tableReady('gold_gives')) { $stmt=$db->prepare("SELECT g.*, c.name as customer_name FROM gold_gives g LEFT JOIN customers c ON c.id=g.customer_id WHERE g.deleted_at IS NULL AND g.give_date>=? AND g.give_date<=? ORDER BY g.give_date, g.id"); $stmt->execute([$from,$to]); $gives=$stmt->fetchAll(); }
    return buildReportData($from, $invoices, $receipts, true, $to, $multi, $gives);
}

function buildReportData(string $primaryDate, array $invoices, array $receipts, bool $isRange = false, ?string $endDate = null, array $multipleInvoices = [], array $goldGives = []): array {
    $totalGoldKhalis = array_sum(array_column($invoices,'gold_khalis')) + array_sum(array_column($multipleInvoices,'total_gold_khalis'));
    $totalInvoiceGiven = array_sum(array_column($invoices,'effective_gold'));
    $totalMultiGiven = array_sum(array_column($multipleInvoices,'effective_gold'));
    $totalGoldGive = array_sum(array_column($goldGives,'total_khalis_weight'));
    $totalGivenCombined = $totalInvoiceGiven + $totalMultiGiven + $totalGoldGive;
    $totalReceivedInvoice = array_sum(array_column($invoices,'total_received_khalis')) + array_sum(array_column($multipleInvoices,'total_received_khalis'));
    $totalReceiptKhalis = array_sum(array_column($receipts,'total_khalis_weight'));
    $totalReceivedCombined = $totalReceivedInvoice + $totalReceiptKhalis;
    $totalWasooli = array_sum(array_column($invoices,'wasooli')) + array_sum(array_column($multipleInvoices,'wasooli'));

    return [
        'is_range'=>$isRange, 'date'=>$primaryDate, 'date_end'=>$endDate, 'date_string'=>$primaryDate,
        'date_range'=>$isRange ? $primaryDate . ' to ' . $endDate : $primaryDate,
        'total_invoices'=>count($invoices), 'total_multiple_invoices'=>count($multipleInvoices), 'total_receipts'=>count($receipts), 'total_gold_gives'=>count($goldGives),
        'total_gold_khalis'=>round($totalGoldKhalis,3),
        'total_grand_total'=>round($totalGivenCombined,3),
        'total_invoice_given'=>round($totalInvoiceGiven + $totalMultiGiven,3),
        'total_gold_give_khalis'=>round($totalGoldGive,3),
        'total_received_invoice'=>round($totalReceivedInvoice,3),
        'total_receipt_khalis'=>round($totalReceiptKhalis,3),
        'total_received_combined'=>round($totalReceivedCombined,3),
        'total_wasooli'=>round($totalWasooli,3),
        'net_movement'=>round($totalGivenCombined - $totalReceivedCombined - $totalWasooli,3),
        'invoices'=>$invoices, 'receipts'=>$receipts, 'multiple_invoices'=>$multipleInvoices, 'gold_gives'=>$goldGives,
    ];
}

function getCustomerReport(int $customerId, string $from, string $to): array {
    $ledger = getCustomerLedger($customerId, $from, $to);
    if (!$ledger) return [];
    $ledger['date_range'] = ['from'=>date('d/m/Y', strtotime($from)), 'to'=>date('d/m/Y', strtotime($to))];
    $ledger['total_invoices'] = count($ledger['invoices']);
    $ledger['total_multiple_invoices'] = count($ledger['multiple_invoices'] ?? []);
    $ledger['total_receipts'] = count($ledger['receipts']);
    return $ledger;
}

function getDashboardStats(): array {
    $db = getDB();
    $totalCustomers = (int)$db->query("SELECT COUNT(*) FROM customers")->fetchColumn();
    $totalInvoices = (int)$db->query("SELECT COUNT(*) FROM invoices WHERE status='active'")->fetchColumn();
    $totalReceipts = (int)$db->query("SELECT COUNT(*) FROM gold_receipts WHERE deleted_at IS NULL")->fetchColumn();
    $totalMultipleInvoices = 0; $totalGoldGivesCount = 0;
    if (_tableReady('invoice_multiple')) $totalMultipleInvoices = (int)$db->query("SELECT COUNT(*) FROM invoice_multiple WHERE status='active'")->fetchColumn();
    if (_tableReady('gold_gives')) $totalGoldGivesCount = (int)$db->query("SELECT COUNT(*) FROM gold_gives WHERE deleted_at IS NULL")->fetchColumn();

    $invoiceGiven = (float)$db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoices WHERE status='active'")->fetchColumn();
    $invRec = (float)$db->query("SELECT COALESCE(SUM(total_received_khalis),0) FROM invoices WHERE status='active'")->fetchColumn();
    $recKhalis = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE deleted_at IS NULL")->fetchColumn();
    $multiGiven = 0; $multiRec = 0; $multiWasooli = 0; $goldGiveKhalis = 0;
    if (_tableReady('invoice_multiple')) {
        $multiGiven = (float)$db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoice_multiple WHERE status='active'")->fetchColumn();
        $multiRec = (float)$db->query("SELECT COALESCE(SUM(total_received_khalis),0) FROM invoice_multiple WHERE status='active'")->fetchColumn();
        $multiWasooli = (float)$db->query("SELECT COALESCE(SUM(wasooli),0) FROM invoice_multiple WHERE status='active'")->fetchColumn();
    }
    if (_tableReady('gold_gives')) $goldGiveKhalis = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE deleted_at IS NULL")->fetchColumn();

    $totalGiven = $invoiceGiven + $multiGiven + $goldGiveKhalis;
    $totalReceived = $invRec + $multiRec + $recKhalis;

    $inventory = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1")->fetch();
    $openingStock = $inventory ? (float)$inventory['opening_balance'] : 0;
    $closingBalance = round($openingStock + $totalReceived - $totalGiven, 3);

    $totalOpening = (float)$db->query("SELECT COALESCE(SUM(opening_balance),0) FROM customers")->fetchColumn();
    $totalRpMazdori = (float)$db->query("SELECT COALESCE(SUM(rp_mazdori_amount),0) FROM invoices WHERE status='active'")->fetchColumn();
    if (_tableReady('invoice_multiple')) $totalRpMazdori += (float)$db->query("SELECT COALESCE(SUM(total_rp_mazdori_amount),0) FROM invoice_multiple WHERE status='active'")->fetchColumn();
    $totalWasooli = (float)$db->query("SELECT COALESCE(SUM(wasooli),0) FROM invoices WHERE status='active'")->fetchColumn() + $multiWasooli;

    $today = date('Y-m-d');
    $stmt=$db->prepare("SELECT COUNT(*) FROM invoices WHERE status='active' AND invoice_date=?"); $stmt->execute([$today]); $todayInvoices=(int)$stmt->fetchColumn();
    $stmt=$db->prepare("SELECT COUNT(*) FROM gold_receipts WHERE deleted_at IS NULL AND receipt_date=?"); $stmt->execute([$today]); $todayReceipts=(int)$stmt->fetchColumn();
    $todayMultipleInvoices = 0; $todayGoldGives = 0;
    if (_tableReady('invoice_multiple')) { $stmt=$db->prepare("SELECT COUNT(*) FROM invoice_multiple WHERE status='active' AND invoice_date=?"); $stmt->execute([$today]); $todayMultipleInvoices=(int)$stmt->fetchColumn(); }
    if (_tableReady('gold_gives')) { $stmt=$db->prepare("SELECT COUNT(*) FROM gold_gives WHERE deleted_at IS NULL AND give_date=?"); $stmt->execute([$today]); $todayGoldGives=(int)$stmt->fetchColumn(); }

    $customers = $db->query("SELECT id FROM customers WHERE status='active'")->fetchAll();
    $totalRemaining = 0;
    foreach ($customers as $c) $totalRemaining += getCustomerCurrentBalance((int)$c['id']);

    $stmt=$db->query("SELECT party_type, COUNT(*) as cnt FROM customers WHERE status='active' GROUP BY party_type");
    $partyTypes=['customer'=>0,'dukandar'=>0,'karigar'=>0];
    foreach($stmt->fetchAll() as $row) $partyTypes[$row['party_type']] = (int)$row['cnt'];

    return [
        'total_customers'=>$totalCustomers, 'total_invoices'=>$totalInvoices, 'total_multiple_invoices'=>$totalMultipleInvoices,
        'total_gold_receipts'=>$totalReceipts, 'total_gold_gives'=>$totalGoldGivesCount,
        'total_gold_khalis_given'=>round($totalGiven,3), 'total_invoice_given'=>round($invoiceGiven+$multiGiven,3), 'total_gold_give_khalis'=>round($goldGiveKhalis,3),
        'total_received_khalis'=>round($totalReceived,3), 'total_inventory_closing'=>$closingBalance, 'total_opening_stock'=>round($totalOpening,3),
        'total_rp_mazdori'=>round($totalRpMazdori,3), 'total_wasooli'=>round($totalWasooli,3), 'total_remaining_balance'=>round($totalRemaining,3),
        'today_invoices'=>$todayInvoices, 'today_multiple_invoices'=>$todayMultipleInvoices, 'today_receipts'=>$todayReceipts, 'today_gold_gives'=>$todayGoldGives,
        'customers_by_type'=>$partyTypes,
    ];
}

function getCustomerCurrentBalance(int $customerId): float {
    $db = getDB();
    $stmt = $db->prepare("SELECT opening_balance FROM customers WHERE id=?"); $stmt->execute([$customerId]); $customer=$stmt->fetch();
    if(!$customer) return 0;
    $balance=(float)$customer['opening_balance'];
    $balance += _sumColumn("SELECT COALESCE(SUM(effective_gold - total_received_khalis - wasooli),0) FROM invoices WHERE customer_id=? AND status='active'", [$customerId]);
    $balance -= _sumColumn("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE customer_id=? AND deleted_at IS NULL", [$customerId]);
    if (_tableReady('gold_gives')) $balance += _sumColumn("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE customer_id=? AND deleted_at IS NULL", [$customerId]);
    if (_tableReady('invoice_multiple')) $balance += _sumColumn("SELECT COALESCE(SUM(effective_gold - total_received_khalis - wasooli),0) FROM invoice_multiple WHERE customer_id=? AND status='active'", [$customerId]);
    return round($balance, 3);
}
