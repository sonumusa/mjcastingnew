<?php
// ============================================================
// Gold Calculation Functions
// ============================================================

/**
 * Custom rounding: round to 2 decimal places with threshold at 8 (not 5).
 * 
 * Examples:
 *   10.156 → 10.15  (third decimal 6 < 8, truncate)
 *   10.154 → 10.15  (third decimal 4 < 8, truncate)
 *   10.158 → 10.16  (third decimal 8 >= 8, round up)
 *   10.159 → 10.16  (third decimal 9 >= 8, round up)
 *   10.150 → 10.15  (third decimal 0 < 8, truncate)
 */
function customRoundTo2(float $value): float {
    // Work with integers to avoid floating point issues
    $scaled = (int) round($value * 1000);
    $hundreds = intdiv($scaled, 10);      // value * 100 truncated
    $remainder = abs($scaled) % 10;       // third decimal digit (0-9)
    
    // Handle negative numbers correctly
    if ($scaled < 0) {
        if ($remainder >= 8) {
            return ($hundreds - 1) / 100;
        } else {
            return $hundreds / 100;
        }
    }
    
    if ($remainder >= 8) {
        return ($hundreds + 1) / 100;
    } else {
        return $hundreds / 100;
    }
}

/**
 * Calculate all invoice fields from input
 * 
 * Calculation Flow:
 * 1. Waste Weight = Casting Weight ÷ 10 × Ratti Deduction Rate  [custom rounded to 2dp]
 * 2. Total Weight = Casting Weight + Waste Weight
 * 3. Male Waste = Total Weight ÷ 96 × Ratti                     [custom rounded to 2dp]
 * 4. Gold Khalis = Total Weight - Male Waste
 * 5. Effective Gold = Gold Khalis + RP Mazdori Weight + Casting Mazdori Weight
 * 6. Grand Total = Effective Gold (in grams)
 * 7. Remaining Balance = Previous Balance + Effective Gold - Wasooli - Total Received Khalis
 */
function calculateGold(array $input): array {
    // Parse inputs
    $castingWeight = parseDecimal($input['casting_weight'] ?? 0);
    $ratti = parseDecimal($input['ratti'] ?? 0);
    $rattiRate = parseDecimal($input['ratti_rate'] ?? 0);
    $rpRate = parseDecimal($input['rp_rate'] ?? 0);
    $rpMazdoriWeight = parseDecimal($input['rp_mazdori_weight'] ?? 0);
    $rpMazdoriRate = parseDecimal($input['rp_mazdori_rate'] ?? 0);
    $castingMazdoriWeight = parseDecimal($input['casting_mazdori_weight'] ?? 0);
    $castingMazdoriRate = parseDecimal($input['casting_mazdori_rate'] ?? 0);
    $wasooli = parseDecimal($input['wasooli'] ?? 0);
    $previousBalance = parseDecimal($input['previous_balance'] ?? 0);
    $totalReceivedKhalis = parseDecimal($input['total_received_khalis'] ?? 0);

    // Step 1: Waste Weight = Casting Weight ÷ 10 × Ratti Rate (custom rounded to 2dp)
    $wasteWeight = 0;
    if ($castingWeight > 0 && $rattiRate > 0) {
        $wasteWeight = customRoundTo2(($castingWeight / 10) * $rattiRate);
    }

    // Step 2: Total Weight = Casting Weight + Waste Weight
    $totalWeight = round($castingWeight + $wasteWeight, 3);

    // Step 3: Male Waste = Total Weight ÷ 96 × Ratti (custom rounded to 2dp)
    $maleWaste = 0;
    if ($totalWeight > 0 && $ratti > 0) {
        $maleWaste = customRoundTo2(($totalWeight / 96) * $ratti);
    }

    // Step 4: Gold Khalis = Total Weight - Male Waste
    $goldKhalis = round($totalWeight - $maleWaste, 3);

    // Step 5: RP Amount (Display Only)
    $rpAmount = round($goldKhalis * $rpRate, 2);

    // Step 6: RP Mazdori Amount (Display Only)
    $rpMazdoriAmount = round($rpMazdoriWeight * $rpMazdoriRate, 2);

    // Step 7: Casting Mazdori Amount (Display Only)
    $castingMazdoriAmount = round($castingMazdoriWeight * $castingMazdoriRate, 2);

    // Step 8: Effective Gold = Gold Khalis + RP Mazdori Weight + Casting Mazdori Weight
    $effectiveGold = round($goldKhalis + $rpMazdoriWeight + $castingMazdoriWeight, 3);

    // Step 9: Grand Total
    $grandTotal = round($effectiveGold, 3);

    // Step 10: Remaining Balance
    $remainingBalance = round(
        $previousBalance + $effectiveGold - $wasooli - $totalReceivedKhalis, 
        3
    );

    return [
        'casting_weight' => $castingWeight,
        'ratti' => $ratti,
        'ratti_rate' => $rattiRate,
        'rp_rate' => $rpRate,
        'rp_mazdori_weight' => $rpMazdoriWeight,
        'rp_mazdori_rate' => $rpMazdoriRate,
        'casting_mazdori_weight' => $castingMazdoriWeight,
        'casting_mazdori_rate' => $castingMazdoriRate,
        'wasooli' => $wasooli,
        'previous_balance' => $previousBalance,
        'total_received_khalis' => $totalReceivedKhalis,
        'waste_weight' => $wasteWeight,
        'total_weight' => $totalWeight,
        'male_waste' => $maleWaste,
        'gold_khalis' => $goldKhalis,
        'rp_amount' => $rpAmount,
        'rp_mazdori_amount' => $rpMazdoriAmount,
        'casting_mazdori_amount' => $castingMazdoriAmount,
        'effective_gold' => $effectiveGold,
        'grand_total' => $grandTotal,
        'remaining_balance' => $remainingBalance,
    ];
}

/**
 * Convert impure gold gross weight to khalis pure gold using ratti formula
 * Formula: gross_weight - (gross_weight ÷ 96 × ratti_impurity)

function convertToKhalis(float $grossWeight, float $rattiImpurity): float {
    if ($grossWeight <= 0) return 0;
    $khalis = $grossWeight - (($grossWeight / 96) * $rattiImpurity);
    return round($khalis, 3);
} */

function convertToKhalis(float $grossWeight, float $rattiImpurity): float {
    if ($grossWeight <= 0) return 0;
    $khalis = $grossWeight - (($grossWeight / 96) * $rattiImpurity);
    return customRoundTo2($khalis);
}

/**
 * Get current/previous balance for a customer.
 * Balance = Opening + Invoice Given + Invoice Multiple Given + Gold Gives - Invoice Receives - Gold Receipts - Wasooli
 */
function getPreviousBalance(int $customerId, ?int $excludeInvoiceId = null): float {
    $db = getDB();

    $stmt = $db->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    if (!$customer) return 0;

    $balance = (float) $customer['opening_balance'];

    $sql = "SELECT COALESCE(SUM(effective_gold - total_received_khalis - wasooli), 0) FROM invoices WHERE customer_id = ? AND status = 'active'";
    $params = [$customerId];
    if ($excludeInvoiceId) {
        $sql .= " AND id != ?";
        $params[] = $excludeInvoiceId;
    }
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $balance += (float) $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COALESCE(SUM(total_khalis_weight), 0) FROM gold_receipts WHERE customer_id = ? AND deleted_at IS NULL");
    $stmt->execute([$customerId]);
    $balance -= (float) $stmt->fetchColumn();

    if (function_exists('tableExists') && tableExists('gold_gives')) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(total_khalis_weight), 0) FROM gold_gives WHERE customer_id = ? AND deleted_at IS NULL");
        $stmt->execute([$customerId]);
        $balance += (float) $stmt->fetchColumn();
    }

    if (function_exists('tableExists') && tableExists('invoice_multiple')) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(effective_gold - total_received_khalis - wasooli), 0) FROM invoice_multiple WHERE customer_id = ? AND status = 'active'");
        $stmt->execute([$customerId]);
        $balance += (float) $stmt->fetchColumn();
    }

    return round($balance, 3);
}

/**
 * Recalculate balance chain for a customer's invoices
 */
function recalculateChain(int $customerId, ?int $fromInvoiceId = null): void {
    $db = getDB();

    $stmt = $db->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    if (!$customer) return;

    $transactions = [];

    $stmt = $db->prepare("SELECT id, invoice_date AS txn_date, effective_gold, wasooli, total_received_khalis FROM invoices WHERE customer_id = ? AND status = 'active'");
    $stmt->execute([$customerId]);
    foreach ($stmt->fetchAll() as $row) {
        $transactions[] = ['kind'=>'invoice','id'=>(int)$row['id'],'date'=>$row['txn_date'],'sort'=>(int)$row['id']*10+1,'net'=>(float)$row['effective_gold']-(float)$row['wasooli']-(float)$row['total_received_khalis']];
    }

    $stmt = $db->prepare("SELECT id, receipt_date AS txn_date, total_khalis_weight FROM gold_receipts WHERE customer_id = ? AND deleted_at IS NULL");
    $stmt->execute([$customerId]);
    foreach ($stmt->fetchAll() as $row) {
        $transactions[] = ['kind'=>'receipt','id'=>(int)$row['id'],'date'=>$row['txn_date'],'sort'=>(int)$row['id']*10+2,'net'=>-((float)$row['total_khalis_weight'])];
    }

    if (function_exists('tableExists') && tableExists('gold_gives')) {
        $stmt = $db->prepare("SELECT id, give_date AS txn_date, total_khalis_weight FROM gold_gives WHERE customer_id = ? AND deleted_at IS NULL");
        $stmt->execute([$customerId]);
        foreach ($stmt->fetchAll() as $row) {
            $transactions[] = ['kind'=>'gold_give','id'=>(int)$row['id'],'date'=>$row['txn_date'],'sort'=>(int)$row['id']*10+3,'net'=>(float)$row['total_khalis_weight']];
        }
    }

    if (function_exists('tableExists') && tableExists('invoice_multiple')) {
        $stmt = $db->prepare("SELECT id, invoice_date AS txn_date, effective_gold, wasooli, total_received_khalis FROM invoice_multiple WHERE customer_id = ? AND status = 'active'");
        $stmt->execute([$customerId]);
        foreach ($stmt->fetchAll() as $row) {
            $transactions[] = ['kind'=>'invoice_multiple','id'=>(int)$row['id'],'date'=>$row['txn_date'],'sort'=>(int)$row['id']*10+4,'net'=>(float)$row['effective_gold']-(float)$row['wasooli']-(float)$row['total_received_khalis']];
        }
    }

    usort($transactions, function($a, $b) {
        $cmp = strcmp($a['date'], $b['date']);
        return $cmp !== 0 ? $cmp : ($a['sort'] <=> $b['sort']);
    });

    $runningBalance = (float)$customer['opening_balance'];
    foreach ($transactions as $txn) {
        $previous = round($runningBalance, 3);
        $runningBalance = round($runningBalance + (float)$txn['net'], 3);
        if ($txn['kind'] === 'invoice') {
            $db->prepare("UPDATE invoices SET previous_balance = ?, remaining_balance = ? WHERE id = ?")->execute([$previous, $runningBalance, $txn['id']]);
        } elseif ($txn['kind'] === 'invoice_multiple' && function_exists('tableExists') && tableExists('invoice_multiple')) {
            $db->prepare("UPDATE invoice_multiple SET previous_balance = ?, remaining_balance = ? WHERE id = ?")->execute([$previous, $runningBalance, $txn['id']]);
        }
    }
}


/**
 * Recalculate and persist current gold inventory / stock.
 * Stock = Opening + Received (gold receipts + invoice receives + multiple invoice receives)
 *              - Given (single invoices + multiple invoices + standalone gold gives)
 */
function recalculateInventoryStock(): array {
    $db = getDB();

    $stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
    $inventory = $stmt->fetch();
    if (!$inventory) {
        $db->query("INSERT INTO inventory (opening_balance, received, given_invoices, closing_balance, period_label) VALUES (0,0,0,0,'Current Stock')");
        $stmt = $db->query("SELECT * FROM inventory ORDER BY id DESC LIMIT 1");
        $inventory = $stmt->fetch();
    }

    $opening = (float)($inventory['opening_balance'] ?? 0);

    $receiptKhalis = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_receipts WHERE deleted_at IS NULL OR deleted_at = '0000-00-00 00:00:00'")->fetchColumn();

    // Only count receive rows that belong to active single invoices.
    $invoiceReceivedKhalis = (float)$db->query("SELECT COALESCE(SUM(ir.khalis_weight),0)
        FROM invoice_receives ir
        INNER JOIN invoices i ON i.id = ir.invoice_id
        WHERE COALESCE(i.status,'active') = 'active'")->fetchColumn();

    $multipleReceivedKhalis = 0.0;
    try {
        $multipleReceivedKhalis = (float)$db->query("SELECT COALESCE(SUM(imr.khalis_weight),0)
            FROM invoice_multiple_receives imr
            INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id
            WHERE COALESCE(im.status,'active') = 'active'")->fetchColumn();
    } catch (Throwable $e) { $multipleReceivedKhalis = 0.0; }

    $invoiceGivenWeight = (float)$db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoices WHERE COALESCE(status,'active')='active'")->fetchColumn();

    $multipleGivenWeight = 0.0;
    try {
        $multipleGivenWeight = (float)$db->query("SELECT COALESCE(SUM(effective_gold),0) FROM invoice_multiple WHERE COALESCE(status,'active')='active'")->fetchColumn();
    } catch (Throwable $e) { $multipleGivenWeight = 0.0; }

    $goldGiveWeight = 0.0;
    if (function_exists('tableExists') && tableExists('gold_gives')) {
        $goldGiveWeight = (float)$db->query("SELECT COALESCE(SUM(total_khalis_weight),0) FROM gold_gives WHERE deleted_at IS NULL")->fetchColumn();
    }

    $totalReceived = round($receiptKhalis + $invoiceReceivedKhalis + $multipleReceivedKhalis, 3);
    $givenWeight = round($invoiceGivenWeight + $multipleGivenWeight + $goldGiveWeight, 3);
    $closingBalance = round($opening + $totalReceived - $givenWeight, 3);

    $upd = $db->prepare("UPDATE inventory SET received=?, given_invoices=?, closing_balance=?, updated_by=? WHERE id=?");
    $upd->execute([$totalReceived, $givenWeight, $closingBalance, $_SESSION['user_id'] ?? null, $inventory['id']]);

    return [
        'inventory_id' => (int)$inventory['id'],
        'opening_balance' => $opening,
        'receipt_khalis' => round($receiptKhalis, 3),
        'invoice_received_khalis' => round($invoiceReceivedKhalis, 3),
        'multiple_received_khalis' => round($multipleReceivedKhalis, 3),
        'total_received' => $totalReceived,
        'invoice_given_weight' => round($invoiceGivenWeight, 3),
        'multiple_given_weight' => round($multipleGivenWeight, 3),
        'gold_give_weight' => round($goldGiveWeight, 3),
        'given_weight' => $givenWeight,
        'closing_balance' => $closingBalance,
    ];
}


/**
 * Ensure date columns exist for Multiple Invoice rows.
 * Safe to call before create/edit/print; it only alters when missing.
 */
function ensureInvoiceMultipleDateColumns(): void {
    $db = getDB();
    try {
        if (function_exists('tableExists') && tableExists('invoice_multiple_items')) {
            $stmt = $db->query("SHOW COLUMNS FROM invoice_multiple_items LIKE 'item_date'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE invoice_multiple_items ADD COLUMN item_date DATE NULL AFTER invoice_multiple_id");
                $db->exec("UPDATE invoice_multiple_items imi INNER JOIN invoice_multiple im ON im.id = imi.invoice_multiple_id SET imi.item_date = im.invoice_date WHERE imi.item_date IS NULL");
            }
        }
        if (function_exists('tableExists') && tableExists('invoice_multiple_receives')) {
            $stmt = $db->query("SHOW COLUMNS FROM invoice_multiple_receives LIKE 'receive_date'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE invoice_multiple_receives ADD COLUMN receive_date DATE NULL AFTER invoice_multiple_id");
                $db->exec("UPDATE invoice_multiple_receives imr INNER JOIN invoice_multiple im ON im.id = imr.invoice_multiple_id SET imr.receive_date = im.invoice_date WHERE imr.receive_date IS NULL");
            }
        }
    } catch (Throwable $e) {
        // Keep page usable; migration SQL can be run manually if ALTER permission is unavailable.
    }
}

/**
 * Generate invoice number
 */
function generateInvoiceNo(): string {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) as max_id FROM invoices");
    $row = $stmt->fetch();
    $number = ($row['max_id'] ?? 0) + 1;
    return 'INV-' . str_pad($number, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate receipt number
 */
function generateReceiptNo(): string {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) as max_id FROM gold_receipts");
    $row = $stmt->fetch();
    $number = ($row['max_id'] ?? 0) + 1;
    return 'RCV-' . str_pad($number, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate standalone gold give number
 */
function generateGoldGiveNo(): string {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) as max_id FROM gold_gives");
    $row = $stmt->fetch();
    $number = ($row['max_id'] ?? 0) + 1;
    return 'GV-' . str_pad($number, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate multiple invoice number
 */
function generateMultipleInvoiceNo(): string {
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) as max_id FROM invoice_multiple");
    $row = $stmt->fetch();
    $number = ($row['max_id'] ?? 0) + 1;
    return 'MINV-' . str_pad($number, 5, '0', STR_PAD_LEFT);
}

/**
 * Build calculation breakdown for display
 */
function buildCalculationBreakdown(array $invoice): array {
    return [
        'steps' => [
            ['label' => 'Casting Weight', 'value' => $invoice['casting_weight'], 'unit' => 'g', 'formula' => 'Input'],
            ['label' => 'Ratti', 'value' => $invoice['ratti'], 'unit' => '', 'formula' => 'Input'],
            ['label' => 'Ratti Rate', 'value' => $invoice['ratti_rate'], 'unit' => 'g', 'formula' => 'From System Setting'],
            ['label' => 'Waste Weight', 'value' => $invoice['waste_weight'], 'unit' => 'g', 'formula' => 'Casting ÷ 10 × Ratti Rate (custom rounded)'],
            ['label' => 'Total Weight', 'value' => $invoice['total_weight'], 'unit' => 'g', 'formula' => 'Casting + Waste'],
            ['label' => 'Male Waste', 'value' => $invoice['male_waste'], 'unit' => 'g', 'formula' => 'Total Weight ÷ 96 × Ratti (custom rounded)'],
            ['label' => 'Gold Khalis', 'value' => $invoice['gold_khalis'], 'unit' => 'g', 'formula' => 'Total Weight - Male Waste'],
            ['label' => 'RP Mazdori Weight', 'value' => $invoice['rp_mazdori_weight'], 'unit' => 'g', 'formula' => 'Input'],
            ['label' => 'Casting Mazdori Weight', 'value' => $invoice['casting_mazdori_weight'], 'unit' => 'g', 'formula' => 'Input'],
            ['label' => 'Effective Gold', 'value' => $invoice['effective_gold'], 'unit' => 'g', 'formula' => 'Gold Khalis + RP Mazdori + Casting Mazdori'],
            ['label' => 'Grand Total', 'value' => $invoice['grand_total'], 'unit' => 'g', 'formula' => 'Effective Gold'],
            ['label' => 'Total Received Khalis', 'value' => $invoice['total_received_khalis'], 'unit' => 'g', 'formula' => 'Sum of Receive Rows'],
        ],
        'balance_chain' => [
            'previous_balance' => $invoice['previous_balance'],
            'effective_gold' => $invoice['effective_gold'],
            'wasooli' => $invoice['wasooli'],
            'received_khalis' => $invoice['total_received_khalis'],
            'remaining_balance' => $invoice['remaining_balance'],
            'formula' => 'Previous + Effective - Wasooli - Received',
        ],
    ];
}