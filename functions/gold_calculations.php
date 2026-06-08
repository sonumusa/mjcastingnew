<?php
// ============================================================
// Gold Calculation Functions
// ============================================================

/**
 * Calculate all invoice fields from input
 * 
 * Calculation Flow:
 * 1. Waste Weight = Casting Weight ÷ 10 × Ratti Deduction Rate
 * 2. Total Weight = Casting Weight + Waste Weight
 * 3. Male Waste = Total Weight ÷ 96 × Ratti
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

    // Step 1: Waste Weight = Casting Weight ÷ 10 × Ratti Rate (g)
    $wasteWeight = 0;
    if ($castingWeight > 0 && $rattiRate > 0) {
        $wasteWeight = round(($castingWeight / 10) * $rattiRate, 3);
    }

    // Step 2: Total Weight = Casting Weight + Waste Weight
    $totalWeight = round($castingWeight + $wasteWeight, 3);

    // Step 3: Male Waste = Total Weight ÷ 96 × Ratti
    $maleWaste = 0;
    if ($totalWeight > 0 && $ratti > 0) {
        $maleWaste = round(($totalWeight / 96) * $ratti, 3);
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
 */
function convertToKhalis(float $grossWeight, float $rattiImpurity): float {
    if ($grossWeight <= 0) return 0;
    $khalis = $grossWeight - (($grossWeight / 96) * $rattiImpurity);
    return round($khalis, 3);
}

/**
 * Get previous balance for a customer
 */
function getPreviousBalance(int $customerId, ?int $excludeInvoiceId = null): float {
    $db = getDB();
    
    // Get customer opening balance
    $stmt = $db->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) return 0;
    
    // Find last active invoice
    $sql = "SELECT remaining_balance FROM invoices 
            WHERE customer_id = ? AND status = 'active'";
    $params = [$customerId];
    
    if ($excludeInvoiceId) {
        $sql .= " AND id != ?";
        $params[] = $excludeInvoiceId;
    }
    
    $sql .= " ORDER BY invoice_date DESC, id DESC LIMIT 1";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $lastInvoice = $stmt->fetch();
    
    return $lastInvoice ? (float) $lastInvoice['remaining_balance'] : (float) $customer['opening_balance'];
}

/**
 * Recalculate balance chain for a customer's invoices
 */
function recalculateChain(int $customerId, ?int $fromInvoiceId = null): void {
    $db = getDB();
    
    // Get customer opening balance
    $stmt = $db->prepare("SELECT opening_balance FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if (!$customer) return;
    
    // Get all active invoices ordered by date
    $stmt = $db->prepare("SELECT id, effective_gold, wasooli, total_received_khalis, previous_balance, remaining_balance 
                          FROM invoices WHERE customer_id = ? AND status = 'active' 
                          ORDER BY invoice_date ASC, id ASC");
    $stmt->execute([$customerId]);
    $invoices = $stmt->fetchAll();
    
    $runningBalance = (float) $customer['opening_balance'];
    
    foreach ($invoices as $invoice) {
        // Update previous balance
        $updateStmt = $db->prepare("UPDATE invoices SET previous_balance = ?, remaining_balance = ? WHERE id = ?");
        $newRemaining = round(
            $runningBalance 
            + (float) $invoice['effective_gold'] 
            - (float) $invoice['wasooli'] 
            - (float) $invoice['total_received_khalis'],
            3
        );
        $updateStmt->execute([$runningBalance, $newRemaining, $invoice['id']]);
        
        $runningBalance = $newRemaining;
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
 * Build calculation breakdown for display
 */
function buildCalculationBreakdown(array $invoice): array {
    return [
        'steps' => [
            ['label' => 'Casting Weight', 'value' => $invoice['casting_weight'], 'unit' => 'g', 'formula' => 'Input'],
            ['label' => 'Ratti', 'value' => $invoice['ratti'], 'unit' => '', 'formula' => 'Input'],
            ['label' => 'Ratti Rate', 'value' => $invoice['ratti_rate'], 'unit' => 'g', 'formula' => 'From System Setting'],
            ['label' => 'Waste Weight', 'value' => $invoice['waste_weight'], 'unit' => 'g', 'formula' => 'Casting ÷ 10 × Ratti Rate'],
            ['label' => 'Total Weight', 'value' => $invoice['total_weight'], 'unit' => 'g', 'formula' => 'Casting + Waste'],
            ['label' => 'Male Waste', 'value' => $invoice['male_waste'], 'unit' => 'g', 'formula' => 'Total Weight ÷ 96 × Ratti'],
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