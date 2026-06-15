<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$customer_id = $_GET['customer_id'] ?? '';

$pdo = getDB();

$custSql = "SELECT id, name, contact, address, opening_balance as static_opening_balance FROM wax_customers WHERE 1=1";
$custParams = [];

if ($customer_id) {
    $custSql .= " AND id = ?";
    $custParams[] = $customer_id;
}
$custSql .= " ORDER BY name ASC";

$custStmt = $pdo->prepare($custSql);
$custStmt->execute($custParams);
$allCustomers = $custStmt->fetchAll();

$customers = [];
foreach ($allCustomers as $c) {
    $customers[$c['id']] = [
        'id' => $c['id'],
        'name' => $c['name'],
        'contact' => $c['contact'],
        'address' => $c['address'],
        'static_ob' => $c['static_opening_balance'],
        'items' => [],
        'invoices' => [],
        'bill_total' => 0
    ];
}

$sql = "
    SELECT 
        c.id as customer_id,
        c.name as customer_name,
        c.contact as customer_contact,
        c.address as customer_address,
        c.opening_balance as static_opening_balance,
        i.id as invoice_id,
        i.invoice_date,
        i.total_amount as invoice_total,
        it.name_urdu as design_item_name,
        it_wax.name_urdu as wax_item_name,
        ii.qty,
        ii.rate,
        ii.amount,
        ii.wax_qty,
        ii.wax_rate,
        ii.design_qty,
        ii.design_rate,
        ii.id as item_db_id,
        it.category as item_category
    FROM wax_invoice_items ii
    JOIN wax_invoices i ON ii.invoice_id = i.id
    JOIN wax_customers c ON i.customer_id = c.id
    JOIN wax_items it ON ii.item_id = it.id
    LEFT JOIN wax_items it_wax ON ii.wax_item_id = it_wax.id
    WHERE i.invoice_date BETWEEN ? AND ?
";

$params = [$start_date, $end_date];

if ($customer_id) {
    $sql .= " AND c.id = ?";
    $params[] = $customer_id;
}

$sql .= " ORDER BY c.name ASC, i.invoice_date ASC, i.id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

foreach ($rows as $row) {
    $cid = $row['customer_id'];
    if (!isset($customers[$cid])) continue;
    
    $inv_id = $row['invoice_id'];
    
    if (!isset($customers[$cid]['invoices'][$inv_id])) {
        $customers[$cid]['invoices'][$inv_id] = [
            'invoice_id' => $inv_id,
            'invoice_date' => $row['invoice_date'],
            'amount' => 0,
            'lines' => []
        ];
    }
    
    $line_id = $row['item_db_id'];
    if (isset($customers[$cid]['invoices'][$inv_id]['lines'][$line_id])) continue;
    
    $customers[$cid]['invoices'][$inv_id]['amount'] += $row['amount'];
    
    $line = [
        'db_id' => $line_id,
        'wax_name' => '', 'wax_qty' => 0, 'wax_rate' => 0,
        'des_name' => '', 'des_qty' => 0, 'des_rate' => 0,
        'amount' => $row['amount']
    ];
    
    if (!empty($row['wax_item_name'])) {
        $line['wax_name'] = $row['wax_item_name'];
        $line['wax_qty'] = $row['wax_qty'];
        $line['wax_rate'] = $row['wax_rate'];
        $line['des_name'] = $row['design_item_name'];
        $line['des_qty'] = $row['design_qty'];
        $line['des_rate'] = $row['design_rate'];
    } else {
        if ($row['item_category'] === 'wax') {
            $line['wax_name'] = $row['design_item_name'];
            $line['wax_qty'] = $row['qty'];
            $line['wax_rate'] = $row['rate'];
        } else {
            $line['des_name'] = $row['design_item_name'];
            $line['des_qty'] = $row['qty'];
            $line['des_rate'] = $row['rate'];
        }
    }
    
    $customers[$cid]['invoices'][$inv_id]['lines'][$line_id] = $line;
}

foreach (array_keys($customers) as $cid) {
    $customers[$cid]['items'] = [];
    $customers[$cid]['bill_total'] = 0;
    
    if (!empty($customers[$cid]['invoices'])) {
        foreach ($customers[$cid]['invoices'] as $inv) {
            foreach ($inv['lines'] as $line) {
                $customers[$cid]['items'][] = [
                    'invoice_id' => $inv['invoice_id'],
                    'invoice_date' => $inv['invoice_date'],
                    'wax_item_name' => $line['wax_name'] ?: '-',
                    'design_item_name' => $line['des_name'] ?: '-',
                    'wax_qty' => $line['wax_qty'],
                    'wax_rate' => $line['wax_rate'] ?: 0,
                    'design_qty' => $line['des_qty'],
                    'design_rate' => $line['des_rate'] ?: 0,
                    'amount' => $line['amount']
                ];
            }
            $customers[$cid]['bill_total'] += $inv['amount'];
        }
    }
}

foreach (array_keys($customers) as $cid) {
    $stmtInv = $pdo->prepare("SELECT SUM(total_amount) FROM wax_invoices WHERE customer_id = ? AND invoice_date < ?");
    $stmtInv->execute([$cid, $start_date]);
    $pre_invoices = $stmtInv->fetchColumn() ?: 0;
    
    $stmtPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date < ?");
    $stmtPay->execute([$cid, $start_date]);
    $pre_payments = $stmtPay->fetchColumn() ?: 0;
    
    $opening_balance = $customers[$cid]['static_ob'] + $pre_invoices - $pre_payments;
    $customers[$cid]['opening_balance'] = $opening_balance;
    
    $stmtCurPay = $pdo->prepare("SELECT SUM(amount) FROM wax_payments WHERE customer_id = ? AND payment_date BETWEEN ? AND ?");
    $stmtCurPay->execute([$cid, $start_date, $end_date]);
    $current_payments = $stmtCurPay->fetchColumn() ?: 0;
    $customers[$cid]['current_payments'] = $current_payments;
    
    $customers[$cid]['closing_balance'] = $opening_balance + $customers[$cid]['bill_total'] - $current_payments;
    $customers[$cid]['is_balance_only'] = empty($customers[$cid]['items']) && ($customers[$cid]['opening_balance'] != 0 || $customers[$cid]['closing_balance'] != 0);
}

$customers = array_filter($customers, function($cust) {
    return !empty($cust['items']) || $cust['opening_balance'] != 0 || $cust['closing_balance'] != 0 || $cust['current_payments'] != 0;
});

function format_amount($num) { return number_format(round($num), 0); }
function format_rate($num) { return number_format($num, 2); }
function format_wax_qty($num) { return number_format($num, 3); }

function number_to_urdu_words($number) {
    $number = abs(round($number));
    if ($number == 0) return 'صفر روپے';
    return trim(_urdu_num_convert($number)) . ' روپے';
}

function _urdu_num_convert($number) {
    if ($number <= 0) return '';
    $urdu = [
        1 => 'ایک', 2 => 'دو', 3 => 'تین', 4 => 'چار', 5 => 'پانچ',
        6 => 'چھ', 7 => 'سات', 8 => 'آٹھ', 9 => 'نو', 10 => 'دس',
        11 => 'گیارہ', 12 => 'بارہ', 13 => 'تیرہ', 14 => 'چودہ', 15 => 'پندرہ',
        16 => 'سولہ', 17 => 'سترہ', 18 => 'اٹھارہ', 19 => 'انیس', 20 => 'بیس',
        21 => 'اکیس', 22 => 'بائیس', 23 => 'تئیس', 24 => 'چوبیس', 25 => 'پچیس',
        26 => 'چھبیس', 27 => 'ستائیس', 28 => 'اٹھائیس', 29 => 'انتیس', 30 => 'تیس',
        31 => 'اکتیس', 32 => 'بتیس', 33 => 'تینتیس', 34 => 'چونتیس', 35 => 'پینتیس',
        36 => 'چھتیس', 37 => 'سینتیس', 38 => 'اڑتیس', 39 => 'انتالیس', 40 => 'چالیس',
        41 => 'اکتالیس', 42 => 'بیالیس', 43 => 'تینتالیس', 44 => 'چوالیس', 45 => 'پینتالیس',
        46 => 'چھیالیس', 47 => 'سینتالیس', 48 => 'اڑتالیس', 49 => 'انچاس', 50 => 'پچاس',
        51 => 'اکیاون', 52 => 'باون', 53 => 'تریپن', 54 => 'چون', 55 => 'پچپن',
        56 => 'چھپن', 57 => 'ستاون', 58 => 'اٹھاون', 59 => 'انسٹھ', 60 => 'ساٹھ',
        61 => 'اکسٹھ', 62 => 'باسٹھ', 63 => 'تریسٹھ', 64 => 'چونسٹھ', 65 => 'پینسٹھ',
        66 => 'چھیاسٹھ', 67 => 'سڑسٹھ', 68 => 'اڑسٹھ', 69 => 'انہتر', 70 => 'ستر',
        71 => 'اکہتر', 72 => 'بہتر', 73 => 'تیہتر', 74 => 'چوہتر', 75 => 'پچہتر',
        76 => 'چھیہتر', 77 => 'ستتر', 78 => 'اٹھہتر', 79 => 'اناسی', 80 => 'اسی',
        81 => 'اکیاسی', 82 => 'بیاسی', 83 => 'تراسی', 84 => 'چوراسی', 85 => 'پچاسی',
        86 => 'چھیاسی', 87 => 'ستاسی', 88 => 'اٹھاسی', 89 => 'نواسی', 90 => 'نوے',
        91 => 'اکیانوے', 92 => 'بانوے', 93 => 'ترانوے', 94 => 'چورانوے', 95 => 'پچانوے',
        96 => 'چھیانوے', 97 => 'ستانوے', 98 => 'اٹھانوے', 99 => 'ننانوے'
    ];
    $words = '';
    if ($number >= 10000000) { $words .= _urdu_num_convert(floor($number / 10000000)) . ' کروڑ '; $number %= 10000000; }
    if ($number >= 100000) { $words .= _urdu_num_convert(floor($number / 100000)) . ' لاکھ '; $number %= 100000; }
    if ($number >= 1000) { $words .= _urdu_num_convert(floor($number / 1000)) . ' ہزار '; $number %= 1000; }
    if ($number >= 100) { $words .= $urdu[floor($number / 100)] . ' سو '; $number %= 100; }
    if ($number > 0 && $number <= 99) { $words .= $urdu[$number] . ' '; }
    return $words;
}

$total_opening = 0; $total_debit = 0; $total_credit = 0; $total_closing = 0;
foreach ($customers as $c) {
    $total_opening += $c['opening_balance'];
    $total_debit += $c['bill_total'];
    $total_credit += $c['current_payments'];
    $total_closing += $c['closing_balance'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M.J RP - Bill Book</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', 'Times New Roman', serif; margin: 0; padding: 0; background: #ccc; font-size: 14px; }
        .page { width: 210mm; min-height: 297mm; padding: 0; margin: 10mm auto; background: #fff; position: relative; box-shadow: 0 0 20px rgba(139,0,0,0.15); overflow: hidden; }
        .watermark { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-30deg); opacity: 0.04; z-index: 0; pointer-events: none; }
        .watermark img { width: 300px; height: auto; }
        .header-curve { position: absolute; top: 0; left: 0; width: 100%; height: 42mm; background: linear-gradient(135deg, #1a0505 0%, #3d0f0f 30%, #5a1a1a 60%, #7a1f1f 85%, #8B0000 100%); clip-path: polygon(0 0, 100% 0, 100% 100%, 100% 100%, 0 100%); z-index: 0; }
        .header-accent { position: absolute; top: 0; left: 0; width: 100%; height: 42mm; background: linear-gradient(135deg, #B8860B 0%, #DAA520 25%, #FFD700 50%, #DAA520 75%, #B8860B 100%); clip-path: polygon(0 0, 100% 0, 100% 65%, 50% 95%, 0 65%); z-index: 1; opacity: 0.15; }
        .content-layer { position: relative; z-index: 2; padding: 7mm 8mm; height: 100%; display: flex; flex-direction: column; }
        .header-content { display: flex; justify-content: space-between; align-items: center; margin-bottom: 5mm; padding-bottom: 3mm; }
        .company-info { text-align: center; }
        .company-info h1 { margin: 0; font-size: 30px; color: #fff; text-transform: uppercase; font-weight: bold; letter-spacing: 2px; text-shadow: 2px 2px 6px rgba(0,0,0,0.5); }
        .company-info .slogan { color: #e8c84a; font-style: italic; font-size: 12px; margin: 4px 0; display: block; letter-spacing: 1px; }
        .company-info p { margin: 2px 0; font-size: 11px; color: rgba(255,255,255,0.9); }
        .logo-box { width: 70px; height: 70px; border: 3px solid #e8c84a; border-radius: 50%; padding: 4px; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.3); flex-shrink: 0; }
        .logo-box img { width: 100%; height: 100%; object-fit: contain; border-radius: 50%; }
        .customer-section { display: flex; border: 2px solid #8B0000; margin: 2mm 0; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .cust-left { flex: 1; padding: 8px 12px; border-right: 2px solid #8B0000; background: linear-gradient(to right, #FFF8E7, #fff); }
        .cust-right { width: 35%; background: #FFF8E7; }
        .cust-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .cust-table td { padding: 3px 0; vertical-align: top; }
        .cust-label { font-weight: bold; width: 65px; color: #8B0000; font-size: 13px; }
        .cust-value { color: #333; }
        .invoice-header { background: linear-gradient(135deg, #4a0f0f, #2d0a0a); color: #FFD700; text-align: center; font-weight: bold; font-size: 14px; padding: 6px; letter-spacing: 2px; text-transform: uppercase; }
        .invoice-details-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .invoice-details-table td { padding: 4px 8px; border-bottom: 1px solid #e0c0a0; color: #333; }
        .invoice-details-table tr:last-child td { border-bottom: none; }
        .balance-summary-box { background: linear-gradient(135deg, #2d0a0a 0%, #1a0505 50%, #3d0f0f 100%); border: 3px solid #FFD700; box-shadow: 0 4px 20px rgba(139,0,0,0.3); border-radius: 10px; padding: 10px 12px; margin: 2mm 0; display: flex; justify-content: space-around; align-items: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .balance-item { text-align: center; padding: 6px 12px; }
        .balance-item.opening { border-right: 2px solid rgba(255,215,0,0.4); }
        .balance-item.closing { border-left: 2px solid rgba(255,215,0,0.4); }
        .balance-label { font-size: 11px; color: #FFD700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4px; font-weight: bold; font-family: Arial, sans-serif; }
        .balance-label-urdu { font-family: 'Noto Nastaliq Urdu', Arial, sans-serif; font-size: 13px; display: block; direction: rtl; }
        .balance-amount { font-size: 20px; font-weight: bold; color: #fff; font-family: Arial, sans-serif; }
        .balance-amount.positive { color: #FF4444; }
        .balance-amount.negative { color: #32CD32; }
        .balance-item.closing .balance-amount { font-size: 24px; text-shadow: 0 0 15px rgba(255,215,0,0.6); }
        .items-table { width: 100%; border-collapse: collapse; border: 2px solid #8B0000; margin-bottom: 4px; font-size: 13px; }
        .items-table th { background: linear-gradient(135deg, #4a0f0f, #2d0a0a); color: #FFD700; padding: 7px 5px; font-size: 11px; border: 1px solid #8B0000; text-transform: uppercase; letter-spacing: 0.3px; font-family: Arial, sans-serif; }
        .items-table th.urdu-header { font-family: 'Noto Nastaliq Urdu', Arial, sans-serif; font-size: 12px; direction: rtl; }
        .items-table td { border: 1px solid #e0c0a0; padding: 5px 4px; vertical-align: middle; text-align: center; background: #fff; font-family: Arial, sans-serif; font-size: 13px; }
        .items-table td.urdu-cell { font-family: 'Noto Nastaliq Urdu', Arial, sans-serif; font-size: 14px; direction: rtl; line-height: 1.8; }
        .items-table tbody tr:nth-child(even) td { background-color: #FFF8E7; }
        .items-table td.amt-cell { font-weight: 700; font-size: 14px; color: #8B0000; }
        .items-table .sub-header { font-size: 10px; padding: 4px 3px; background: linear-gradient(135deg, #6b1515, #4a0f0f); }
        .bill-summary-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .bill-summary-table td { padding: 7px 10px; border: 1px solid #8B0000; font-family: Arial, sans-serif; font-size: 14px; }
        .summary-row { background: #FFF8E7; }
        .summary-row td:first-child { text-align: right; font-weight: 500; }
        .summary-row td:last-child { text-align: right; width: 130px; }
        .bill-closing-row { background: linear-gradient(135deg, #DAA520, #FFD700, #DAA520) !important; }
        .bill-closing-row td { color: #2d0a0a !important; font-weight: bold; font-size: 16px; padding: 10px; }
        .payment-row td { color: #228B22; }
        .continued-notice { text-align: center; padding: 8px; font-style: italic; color: #8B0000; font-size: 12px; border-top: 1px dashed #ccc; margin-top: 4px; letter-spacing: 0.5px; }
        .words-section { background: #FFF8E7; border: 1px solid #dee2e6; border-radius: 5px; padding: 8px 12px; font-size: 16px; margin: 6px 0; text-align: right; direction: rtl; font-family: 'Noto Nastaliq Urdu', Arial, sans-serif; color: #333; line-height: 2; }
        .bottom-section { display: flex; justify-content: flex-end; margin-top: 10px; padding-top: 8px; }
        .signature { text-align: center; width: 160px; }
        .signature img { max-width: 100px; max-height: 50px; object-fit: contain; }
        .signature-line { border-top: 2px solid #8B0000; margin-top: 4px; padding-top: 4px; font-size: 12px; font-weight: bold; color: #8B0000; }
        .balance-only-page .balance-summary-box { margin: 12mm 0; padding: 20px; }
        .balance-only-page .balance-item { padding: 12px 25px; }
        .balance-only-page .balance-amount { font-size: 28px; }
        .balance-only-page .balance-item.closing .balance-amount { font-size: 32px; }
        .balance-only-message { text-align: center; padding: 18px; color: #666; font-style: italic; font-size: 16px; background: #FFF8E7; border-radius: 8px; margin: 6mm 0; font-family: 'Noto Nastaliq Urdu', Arial, sans-serif; line-height: 2; }
        .summary-page-title { background: linear-gradient(135deg, #4a0f0f, #2d0a0a); color: #FFD700; text-align: center; padding: 12px; border-radius: 8px; margin: 8px 0; font-size: 20px; font-weight: bold; letter-spacing: 2px; }
        .summary-period { text-align: center; margin-bottom: 8px; font-size: 14px; color: #666; }
        .summary-stats { margin-top: 12px; padding: 10px; background: #FFF8E7; border-radius: 8px; font-size: 14px; line-height: 1.8; }
        .summary-footer-row { background: linear-gradient(135deg, #DAA520, #FFD700, #DAA520) !important; }
        .summary-footer-row td { font-weight: bold; color: #2d0a0a !important; padding: 10px !important; font-size: 14px; }
        .summary-customers-table { width: 100%; border-collapse: collapse; border: 2px solid #8B0000; font-size: 13px; }
        .summary-customers-table th { background: linear-gradient(135deg, #4a0f0f, #2d0a0a); color: #FFD700; padding: 10px 6px; font-size: 12px; border: 1px solid #8B0000; text-transform: uppercase; letter-spacing: 0.5px; font-family: Arial, sans-serif; }
        .summary-customers-table td { border: 1px solid #e0c0a0; padding: 8px 6px; vertical-align: middle; font-family: Arial, sans-serif; font-size: 14px; }
        .summary-customers-table tbody tr:nth-child(even) td { background-color: #FFF8E7; }
        .summary-customers-table tbody tr:hover td { background-color: #F0E68C; }
        .no-print { text-align: center; padding: 15px; background: linear-gradient(135deg, #4a0f0f, #2d0a0a); position: sticky; top: 0; z-index: 1000; }
        .no-print button { padding: 12px 25px; font-size: 15px; cursor: pointer; border: none; border-radius: 8px; margin: 0 8px; font-weight: bold; transition: all 0.3s ease; }
        .btn-print { background: #d4af37; color: #3a0a0a; }
        .btn-export { background: #28a745; color: #fff; }
        .no-print button:hover { transform: translateY(-2px); box-shadow: 0 4px 10px rgba(0,0,0,0.2); }
        .export-progress { display: none; color: #fff; margin-top: 10px; font-size: 14px; }
        .export-progress.show { display: block; }
        .page-export-btn { position: absolute; top: 10px; right: 10px; z-index: 100; background: linear-gradient(135deg, #8B0000, #B22222); color: #fff; border: none; border-radius: 8px; padding: 8px 12px; font-size: 12px; font-weight: bold; cursor: pointer; box-shadow: 0 3px 10px rgba(0,0,0,0.3); transition: all 0.3s ease; display: flex; align-items: center; gap: 5px; }
        .page-export-btn:hover { transform: translateY(-2px) scale(1.05); box-shadow: 0 5px 15px rgba(0,0,0,0.4); }
        .page-export-btn:active { transform: translateY(0) scale(0.98); }
        .page-export-btn.exporting { background: #6c757d; pointer-events: none; }
        .page-export-btn .icon { font-size: 14px; }
        @media print {
            body { background: none; margin: 0; }
            .page { margin: 0; border: none; box-shadow: none; page-break-after: always; height: 297mm; }
            .no-print, .page-export-btn { display: none !important; }
            .header-curve, .header-accent, .items-table th, .summary-customers-table th, .balance-summary-box, .bill-closing-row, .summary-footer-row { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>

<div class="no-print">
    <button class="btn-print" onclick="window.print()">🖨️ Print All Bills</button>
    <button class="btn-export" onclick="exportAllToZip()">📦 Export All as ZIP</button>
    <div class="export-progress" id="exportProgress">
        <span id="progressText">Preparing...</span>
    </div>
</div>

<?php
$max_rows = 21;
$page_counter = 0;
$page_counter++;
?>

<div class="page" id="bill-page-<?= $page_counter ?>" data-customer="0Summary Report">
    <button class="page-export-btn" onclick="exportSinglePage('bill-page-<?= $page_counter ?>', '0Summary Report')">
        <span class="icon">📷</span> Export JPG
    </button>
    <div class="watermark"><img src="https://mjcasting.altafhussain-co.com/wax/assets/sign.png" alt="Watermark" crossorigin="anonymous"></div>
    <div class="header-curve"></div>
    <div class="header-accent"></div>
    <div class="content-layer">
        <div class="header-content">
            <div class="company-info">
                <h1>M.J RP</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Available</span>
                <p>Shop # 882 Ateeq Center, Rang Mahal Lahore</p>
                <p>+92 302-4098908 | +92 322-4773342</p>
            </div>
            <div class="logo-box"><img src="https://mjcasting.altafhussain-co.com/wax/assets/2.png" alt="Logo" crossorigin="anonymous"></div>
        </div>
        <div class="summary-page-title">📊 CUSTOMERS SUMMARY | تمام گاہکوں کا خلاصہ</div>
        <div class="summary-period"><strong>Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></div>
        <table class="summary-customers-table">
            <thead>
                <tr>
                    <th width="6%">Sr</th>
                    <th width="30%" style="text-align:left;padding-left:8px;">Customer<br><span style="font-size:10px;opacity:0.8;">گاہک</span></th>
                    <th width="16%">Opening<br><span style="font-size:10px;opacity:0.8;">سابقہ</span></th>
                    <th width="16%">Debit<br><span style="font-size:10px;opacity:0.8;">ڈیبٹ</span></th>
                    <th width="16%">Credit<br><span style="font-size:10px;opacity:0.8;">کریڈٹ</span></th>
                    <th width="16%">Closing<br><span style="font-size:10px;opacity:0.8;">بیلنس</span></th>
                </tr>
            </thead>
            <tbody>
                <?php $sr = 1; foreach ($customers as $c): ?>
                <tr>
                    <td style="font-size:13px;"><?= $sr++ ?></td>
                    <td style="text-align:left;padding-left:8px;font-weight:600;font-size:14px;"><?= htmlspecialchars($c['name']) ?></td>
                    <td style="text-align:center;color:<?= $c['opening_balance'] >= 0 ? '#dc3545' : '#28a745' ?>;font-weight:600;"><?= format_amount(abs($c['opening_balance'])) ?></td>
                    <td style="text-align:center;font-weight:500;"><?= format_amount($c['bill_total']) ?></td>
                    <td style="text-align:center;color:#28a745;font-weight:600;"><?= format_amount($c['current_payments']) ?></td>
                    <td style="text-align:center;font-weight:bold;font-size:15px;color:<?= $c['closing_balance'] >= 0 ? '#dc3545' : '#28a745' ?>;"><?= format_amount(abs($c['closing_balance'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="summary-footer-row">
                    <td colspan="2" style="text-align:right;padding-right:10px;"><strong>TOTAL | کل رقم</strong></td>
                    <td style="text-align:center;font-size:14px;"><?= format_amount(abs($total_opening)) ?></td>
                    <td style="text-align:center;"><?= format_amount($total_debit) ?></td>
                    <td style="text-align:center;"><?= format_amount($total_credit) ?></td>
                    <td style="text-align:center;font-size:15px;"><?= format_amount(abs($total_closing)) ?></td>
                </tr>
            </tfoot>
        </table>
        <div class="summary-stats">
            <strong>Total Customers:</strong> <?= count($customers) ?> &nbsp;|&nbsp;
            <strong>Total Receivable:</strong> <?= format_amount(abs($total_closing)) ?><br>
            <strong>Total Bills:</strong> <?= format_amount($total_debit) ?> &nbsp;|&nbsp;
            <strong>Total Payments:</strong> <?= format_amount($total_credit) ?>
        </div>
        <div class="bottom-section">
            <div class="signature">
                <img src="https://mjcasting.altafhussain-co.com/wax/assets/sign.png" alt="Signature" crossorigin="anonymous">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
    </div>
</div>

<?php
foreach ($customers as $cust):
    $items = $cust['items'];
    $total_items = count($items);
    $is_balance_only = $cust['is_balance_only'];
    
    if ($is_balance_only):
        $page_counter++;
?>
<div class="page balance-only-page" id="bill-page-<?= $page_counter ?>" data-customer="<?= htmlspecialchars($cust['name']) ?>">
    <button class="page-export-btn" onclick="exportSinglePage('bill-page-<?= $page_counter ?>', '<?= htmlspecialchars(addslashes($cust['name'])) ?>')">
        <span class="icon">📷</span> Export JPG
    </button>
    <div class="watermark"><img src="https://mjcasting.altafhussain-co.com/wax/assets/sign.png" alt="Watermark" crossorigin="anonymous"></div>
    <div class="header-curve"></div>
    <div class="header-accent"></div>
    <div class="content-layer">
        <div class="header-content">
            <div class="company-info">
                <h1>M.J RP</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Available</span>
                <p>Shop # 882 Ateeq Center, Rang Mahal Lahore</p>
                <p>+92 302-4098908 | +92 322-4773342</p>
            </div>
            <div class="logo-box"><img src="https://mjcasting.altafhussain-co.com/wax/assets/2.png" alt="Logo" crossorigin="anonymous"></div>
        </div>
        <div class="customer-section">
            <div class="cust-left">
                <table class="cust-table">
                    <tr><td class="cust-label">Name:</td><td class="cust-value"><strong style="font-size:16px;"><?= htmlspecialchars($cust['name']) ?></strong></td></tr>
                    <tr><td class="cust-label">Address:</td><td class="cust-value"><?= htmlspecialchars($cust['address'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="cust-right">
                <div class="invoice-header">Statement</div>
                <table class="invoice-details-table">
                    <tr><td><strong>Period:</strong> <?= date('d-M-Y', strtotime($start_date)) ?> to <?= date('d-M-Y', strtotime($end_date)) ?></td></tr>
                    <tr><td><strong>Date:</strong> <?= date('d-M-Y') ?></td></tr>
                </table>
            </div>
        </div>
        <div class="balance-only-message">اس مدت میں کوئی لین دین نہیں ہوا — No transactions during this period</div>
        <div class="balance-summary-box">
            <div class="balance-item opening">
                <div class="balance-label"><span class="balance-label-urdu">سابقہ بیلنس</span>Opening Balance</div>
                <div class="balance-amount <?= $cust['opening_balance'] >= 0 ? 'positive' : 'negative' ?>"><?= format_amount(abs($cust['opening_balance'])) ?></div>
            </div>
            <div class="balance-item">
                <div class="balance-label"><span class="balance-label-urdu">وصولی</span>Payment</div>
                <div class="balance-amount" style="color:#51cf66;"><?= format_amount($cust['current_payments']) ?></div>
            </div>
            <div class="balance-item closing">
                <div class="balance-label"><span class="balance-label-urdu">موجودہ بیلنس</span>Closing Balance</div>
                <div class="balance-amount <?= $cust['closing_balance'] >= 0 ? 'positive' : 'negative' ?>"><?= format_amount(abs($cust['closing_balance'])) ?></div>
            </div>
        </div>
        <div class="words-section"><?= number_to_urdu_words($cust['closing_balance']) ?></div>
        <div class="bottom-section">
            <div class="signature">
                <img src="https://mjcasting.altafhussain-co.com/wax/assets/sign.png" alt="Signature" crossorigin="anonymous">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
    </div>
</div>
<?php
    else:
        $total_pages = ceil($total_items / $max_rows);
        if ($total_pages == 0) $total_pages = 1;
        $chunks = $items ? array_chunk($items, $max_rows) : [[]];
        
        foreach ($chunks as $page_index => $page_items):
            $page_num = $page_index + 1;
            $is_last_page = ($page_num == $total_pages);
            $page_counter++;
?>
<div class="page" id="bill-page-<?= $page_counter ?>" data-customer="<?= htmlspecialchars($cust['name']) ?>" data-page-num="<?= $page_num ?>" data-total-pages="<?= $total_pages ?>">
    <button class="page-export-btn" onclick="exportSinglePage('bill-page-<?= $page_counter ?>', '<?= htmlspecialchars(addslashes($cust['name'])) ?><?= $total_pages > 1 ? '_Page' . $page_num : '' ?>')">
        <span class="icon">📷</span> Export JPG
    </button>
    <div class="watermark"><img src="https://mjcasting.altafhussain-co.com/wax/assets/sign.png" alt="Watermark" crossorigin="anonymous"></div>
    <div class="header-curve"></div>
    <div class="header-accent"></div>
    <div class="content-layer">
        <div class="header-content">
            <div class="company-info">
                <h1>M.J RP</h1>
                <span class="slogan">All Kind of Jewellries Designing & 3D Printer Wax Available</span>
                <p>Shop # 882 Ateeq Center, Rang Mahal Lahore</p>
                <p>+92 302-4098908 | +92 322-4773342</p>
            </div>
            <div class="logo-box"><img src="https://mjcasting.altafhussain-co.com/wax/assets/2.png" alt="Logo" crossorigin="anonymous"></div>
        </div>
        <div class="customer-section">
            <div class="cust-left">
                <table class="cust-table">
                    <tr><td class="cust-label">Name:</td><td class="cust-value"><strong style="font-size:16px;"><?= htmlspecialchars($cust['name']) ?></strong></td></tr>
                    <tr><td class="cust-label">Address:</td><td class="cust-value"><?= htmlspecialchars($cust['address'] ?? 'N/A') ?></td></tr>
                </table>
            </div>
            <div class="cust-right">
                <div class="invoice-header">Invoice</div>
                <table class="invoice-details-table">
                    <tr><td><strong>Page:</strong> <?= $page_num ?> / <?= $total_pages ?></td></tr>
                    <tr><td><strong>Period:</strong> <?= date('d-M', strtotime($start_date)) ?> - <?= date('d-M', strtotime($end_date)) ?>, <?= date('Y', strtotime($start_date)) ?></td></tr>
                </table>
            </div>
        </div>
        <?php if ($page_num == 1): ?>
        <div class="balance-summary-box" style="padding:6px 10px;">
            <div class="balance-item opening" style="padding:4px 12px;border-right:none;">
                <div class="balance-label">سابقہ بیلنس | Opening Balance</div>
                <div class="balance-amount <?= $cust['opening_balance'] >= 0 ? 'positive' : 'negative' ?>" style="font-size:18px;"><?= format_amount(abs($cust['opening_balance'])) ?></div>
            </div>
        </div>
        <?php endif; ?>
        <table class="items-table">
            <thead>
                <tr>
                    <th width="9%" rowspan="2">Date<br><span style="font-size:9px;">تاریخ</span></th>
                    <th width="5%" rowspan="2">Bill</th>
                    <th colspan="3" style="border-bottom:2px solid #d4af37;">🔶 Wax (ویکس)</th>
                    <th colspan="3" style="border-bottom:2px solid #51cf66;">🔷 Design (ڈیزائن)</th>
                    <th width="13%" rowspan="2" style="background:linear-gradient(135deg,#2c5530,#1a3a20);">Total<br><span style="font-size:10px;">کل رقم</span></th>
                </tr>
                <tr class="sub-header">
                    <th width="12%">Item</th>
                    <th width="8%">Wt/Qty</th>
                    <th width="8%">Rate</th>
                    <th width="13%">Item</th>
                    <th width="7%">Qty</th>
                    <th width="8%">Rate</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($page_items as $item):
                    $wQty = $item['wax_qty'] > 0 ? format_wax_qty((float)$item['wax_qty']) : '-';
                    $dQty = $item['design_qty'] > 0 ? round($item['design_qty']) : '-';
                    $wRate = $item['wax_rate'] > 0 ? format_rate($item['wax_rate']) : '-';
                    $dRate = $item['design_rate'] > 0 ? format_rate($item['design_rate']) : '-';
                    $waxName = $item['wax_item_name'] ?: '-';
                    $desName = $item['design_item_name'] ?: '-';
                ?>
                <tr>
                    <td style="font-size:12px;white-space:nowrap;"><?= date('d-M', strtotime($item['invoice_date'])) ?></td>
                    <td style="font-size:12px;"><?= $item['invoice_id'] ?></td>
                    <td class="urdu-cell" style="font-size:13px;"><?= htmlspecialchars($waxName) ?></td>
                    <td style="font-size:12px;"><?= $wQty ?></td>
                    <td style="font-size:12px;"><?= $wRate ?></td>
                    <td class="urdu-cell" style="font-size:13px;"><?= htmlspecialchars($desName) ?></td>
                    <td style="font-size:12px;"><?= $dQty ?></td>
                    <td style="font-size:12px;"><?= $dRate ?></td>
                    <td class="amt-cell"><?= format_amount($item['amount']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if ($is_last_page):
                    $used_rows = count($page_items);
                    $remaining = $max_rows - $used_rows;
                    $fill_rows = min($remaining, 3);
                    for ($i = 0; $i < $fill_rows; $i++): ?>
                <tr><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>
                <?php endfor; endif; ?>
            </tbody>
        </table>
        <?php if ($is_last_page): ?>
        <table class="bill-summary-table">
            <tr class="summary-row"><td>Bill Total (اس بل کی رقم)</td><td><?= format_amount($cust['bill_total']) ?></td></tr>
            <tr class="summary-row"><td>Opening Balance (سابقہ بیلنس) (+)</td><td><?= format_amount($cust['opening_balance']) ?></td></tr>
            <tr class="summary-row payment-row"><td>Payment Received (وصولی) (−)</td><td style="color:#28a745;">− <?= format_amount($cust['current_payments']) ?></td></tr>
            <tr class="bill-closing-row" style="text-align:right"><td>موجودہ بیلنس | CLOSING BALANCE</td><td><?= format_amount($cust['closing_balance']) ?></td></tr>
        </table>
        <div class="words-section"><?= number_to_urdu_words($cust['closing_balance']) ?></div>
        <div class="bottom-section">
            <div class="signature">
                <img src="https://mjcasting.altafhussain-co.com/wax/assets/sign.png" alt="Signature" crossorigin="anonymous">
                <div class="signature-line">Authorized Signature</div>
            </div>
        </div>
        <?php else: ?>
        <div class="continued-notice">▶ Continued on next page... | اگلے صفحے پر جاری ہے</div>
        <?php endif; ?>
    </div>
</div>
<?php
        endforeach;
    endif;
endforeach;
?>

<script src="https://cdn.jsdelivr.net/npm/dom-to-image-more@3.3.0/dist/dom-to-image-more.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>

<script>
// Pass PHP dates to JavaScript
var startDateFormatted = '<?= date("d-M-Y", strtotime($start_date)) ?>';
var endDateFormatted   = '<?= date("d-M-Y", strtotime($end_date)) ?>';

function dataURLtoBlob(dataURL) {
    var arr = dataURL.split(','),
        mime = arr[0].match(/:(.*?);/)[1],
        bstr = atob(arr[1]),
        n = bstr.length,
        u8arr = new Uint8Array(n);
    while (n--) { u8arr[n] = bstr.charCodeAt(n); }
    return new Blob([u8arr], { type: mime });
}

document.fonts.ready.then(function () {
    console.log('All fonts loaded and ready for export.');
});

// Capture one .page element and return a JPEG data-URL
async function capturePage(page) {
    var scale  = 2.5;
    var width  = page.scrollWidth;
    var height = page.scrollHeight;

    return domtoimage.toJpeg(page, {
        quality : 0.95,
        bgcolor : '#ffffff',
        width   : width  * scale,
        height  : height * scale,
        style   : {
            transform       : 'scale(' + scale + ')',
            transformOrigin : 'top left',
            width           : width  + 'px',
            height          : height + 'px',
            margin          : '0',
            boxShadow       : 'none',
            border          : 'none',
            overflow        : 'hidden'
        },
        filter: function (node) {
            if (node.classList && node.classList.contains('page-export-btn'))  return false;
            if (node.classList && node.classList.contains('no-print'))        return false;
            return true;
        }
    });
}

// Make a filesystem-safe name (keep English + Urdu chars, remove junk)
function safeName(str) {
    return str
        .replace(/[\\/:*?"<>|]/g, '')          // remove Windows-illegal chars
        .replace(/\s+/g, ' ')                   // collapse spaces
        .trim()
        .substring(0, 80);
}

// ==================== SINGLE PAGE EXPORT ====================
async function exportSinglePage(pageId, customerName) {
    var page   = document.getElementById(pageId);
    var button = page.querySelector('.page-export-btn');
    if (!page) { alert('Page not found!'); return; }

    var originalText = button.innerHTML;
    button.innerHTML = '<span class="icon">⏳</span> Exporting...';
    button.classList.add('exporting');

    try {
        await document.fonts.ready;
        await new Promise(function(r){ setTimeout(r, 500); });

        var dataUrl  = await capturePage(page);
        var blob     = dataURLtoBlob(dataUrl);
        var filename = safeName(customerName) + '.jpg';

        saveAs(blob, filename);

        button.innerHTML = '<span class="icon">✅</span> Done!';
        setTimeout(function () {
            button.innerHTML = originalText;
            button.classList.remove('exporting');
        }, 1500);

    } catch (error) {
        console.error('Error exporting page:', error);
        button.innerHTML = '<span class="icon">❌</span> Error!';
        setTimeout(function () {
            button.innerHTML = originalText;
            button.classList.remove('exporting');
        }, 2000);
    }
}

// ==================== EXPORT ALL → ZIP ====================
async function exportAllToZip() {
    var pages        = document.querySelectorAll('.page');
    var progress     = document.getElementById('exportProgress');
    var progressText = document.getElementById('progressText');
    var zip          = new JSZip();

    progress.classList.add('show');
    progressText.textContent = 'Loading fonts...';

    await document.fonts.ready;
    await new Promise(function(r){ setTimeout(r, 800); });

    // ---- folder & zip names ----
    var folderName = 'M.J RP Ledger ' + endDateFormatted;
    var zipName    = folderName + '.zip';
    var folder     = zip.folder(folderName);

    // ---- track used filenames to avoid duplicates ----
    var usedNames = {};

    function uniqueName(base) {
        var clean = safeName(base);
        if (!usedNames[clean]) {
            usedNames[clean] = 1;
            return clean;
        }
        usedNames[clean]++;
        return clean + ' (' + usedNames[clean] + ')';
    }

    progressText.textContent = 'Preparing export...';

    for (var i = 0; i < pages.length; i++) {
        progressText.textContent = 'Exporting ' + (i + 1) + ' of ' + pages.length + '...';

        var page         = pages[i];
        var customerName = (page.dataset.customer || 'Customer').trim();
        var pageNum      = page.dataset.pageNum    || '';
        var totalPages   = page.dataset.totalPages || '1';

        // ---- build filename: just the customer name ----
        var baseName;
        if (i === 0) {
            baseName = '0Summary Report';
        } else {
            baseName = customerName;
            // only add Page suffix when customer has multiple pages
            if (parseInt(totalPages) > 1 && pageNum) {
                baseName += ' Page ' + pageNum;
            }
        }

        var filename = uniqueName(baseName) + '.jpg';

        try {
            var dataUrl    = await capturePage(page);
            var base64Data = dataUrl.split(',')[1];
            folder.file(filename, base64Data, { base64: true });
        } catch (error) {
            console.error('Error exporting page ' + (i + 1) + ':', error);
        }

        await new Promise(function(r){ setTimeout(r, 300); });
    }

    progressText.textContent = 'Creating ZIP file...';

    try {
        var zipBlob = await zip.generateAsync({
            type: 'blob',
            compression: 'DEFLATE',
            compressionOptions: { level: 6 }
        });

        saveAs(zipBlob, zipName);

        progressText.textContent = '✅ Download started!';
        setTimeout(function () { progress.classList.remove('show'); }, 2000);

    } catch (error) {
        console.error('Error creating ZIP:', error);
        progressText.textContent = '❌ Error creating ZIP file';
        setTimeout(function () { progress.classList.remove('show'); }, 3000);
    }
}
</script>

</body>
</html>