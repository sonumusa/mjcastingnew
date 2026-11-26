<?php
/**
 * invoices/print2.php
 * Gold RECEIVING receipt.
 *
 * - Sheet : 145 x 200 mm portrait (same page setup as print1.php)
 * - Receipt uses only ONE THIRD of the sheet (about 66 mm) so the rest of the paper is not wasted.
 * - Optional ?slot=1|2|3 prints the receipt in the top / middle / bottom third,
 *   so a partly used sheet can be fed again for the next receipt.
 */
require_once __DIR__ . '/../config.php';
requireAuth();
require_once __DIR__ . '/../functions/gold_calculations.php';

$id = (int) query('id', 0);
$slot = (int) query('slot', 1);
if ($slot < 1 || $slot > 3) {
    $slot = 1;
}

$db = getDB();
$stmt = $db->prepare("SELECT i.*, c.name AS customer_name FROM invoices i LEFT JOIN customers c ON c.id = i.customer_id WHERE i.id = ?");
$stmt->execute([$id]);
$invoice = $stmt->fetch();

if (!$invoice) {
    setFlash('error', 'Invoice not found.');
    redirect('invoices/index.php');
}

$stmt = $db->prepare("SELECT * FROM invoice_receives WHERE invoice_id = ?");
$stmt->execute([$id]);
$receives = $stmt->fetchAll();

// Format Ratti:
// - 0 or empty => empty string ''
// - No negative sign (abs)
// - Whole number => no decimals (e.g., 14)
// - Decimal => preserve decimals without trailing zeros (e.g., 0.84)
if (!function_exists('formatRattiPrint')) {
    function formatRattiPrint($value): string {
        if ($value === null || $value === '') {
            return '';
        }
        $v = abs((float)$value);
        if ($v < 0.0001) {
            return '';
        }
        if (abs($v - round($v)) < 0.0001) {
            return number_format(round($v), 0, '.', '');
        }
        return rtrim(rtrim(number_format($v, 4, '.', ''), '0'), '.');
    }
}

$workshopName = getSetting('workshop_name', 'M.J Casting');
$workshopNameUrdu = getSetting('workshop_name_urdu', 'ایم جے کاسٹنگ');
$workshopAddress = getSetting('address', '');
$phones = array_filter([
    getSetting('phone', ''),
    getSetting('phone2', ''),
    getSetting('phone3', ''),
]);

/* ---- Build the rows that will be printed ---- */
$totalKhalis = (float)$invoice['total_received_khalis'];
$rows = [];
foreach ($receives as $rec) {
    $rows[] = [
        'desc'   => trim((string)($rec['description'] ?? '')) !== '' ? $rec['description'] : 'سونا وصول',
        'gross'  => (float)$rec['gross_weight'],
        'ratti'  => (float)$rec['ratti_impurity'],
        'khalis' => (float)$rec['khalis_weight'],
    ];
}

// Old invoices may have a total but no detail rows
if (!$rows && $totalKhalis > 0) {
    $rows[] = ['desc' => 'وصولی (سونا)', 'gross' => null, 'ratti' => 0, 'khalis' => $totalKhalis];
}

// Mail = total weight - khalis (minus sign removed on display).
// Points logic:
// - Negative ratti (< 0): "{pts} Point add" (e.g., -1 => 100 Point add, -0.4 => 40 Point add)
// - Positive ratti < 1 (0 < ratti < 1): "{pts} Point less" (e.g., 0.84 => 84 Point less, 0.4 => 40 Point less)
// - Otherwise hidden unless at least one row triggers it.
$grossSum = 0;
$mailSum = 0;
$hasPoints = false;

$fmtPts = function ($p) {
    return rtrim(rtrim(number_format($p, 2, '.', ''), '0'), '.');
};

foreach ($rows as &$r) {
    $r['mail'] = $r['gross'] !== null ? round($r['gross'] - $r['khalis'], 3) : null;

    $rattiVal = (float)$r['ratti'];
    if ($rattiVal < -0.0001) {
        $pts = round(abs($rattiVal) * 100, 2);
        $r['point_text'] = $fmtPts($pts) . ' Point add';
        $hasPoints = true;
    } elseif ($rattiVal > 0.0001 && $rattiVal < 1) {
        $pts = round($rattiVal * 100, 2);
        $r['point_text'] = $fmtPts($pts) . ' Point less';
        $hasPoints = true;
    } else {
        $r['point_text'] = '';
    }

    if ($r['gross'] !== null) {
        $grossSum += $r['gross'];
        $mailSum += $r['mail'];
    }
}
unset($r);
$mailSum = round($mailSum, 3);

$cols = $hasPoints ? 7 : 6; // number of table columns

// Weight with " g" (ratti is printed without unit)
$fmtG = function ($v) {
    $v = round((float)$v, 3);
    if (abs($v) < 0.0005) {
        $v = 0;
    }
    return number_format($v, 3) . ' g';
};

// Mail weight with " g" and NO minus sign
$fmtMailG = function ($v) {
    $v = abs(round((float)$v, 3));
    if ($v < 0.0005) {
        $v = 0;
    }
    return number_format($v, 3) . ' g';
};

// Row density so more rows still fit inside one third
$n = count($rows);
$density = $n <= 4 ? 'normal' : ($n <= 6 ? 'compact' : 'grow');
$minRows = 3; // blank ruled rows so a 1-row receipt still looks like a form

$slotOffsetMm = round(($slot - 1) * (200 / 3), 3);
$slotUrl = function (int $s) use ($id) {
    return url('invoices/print2.php?id=' . $id . '&slot=' . $s);
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Receiving - <?= htmlspecialchars($invoice['invoice_no']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Nastaliq+Urdu:wght@400;700&display=swap" rel="stylesheet">
<style>
  @page { size: 145mm 200mm; margin: 0; }
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body {
    background: #e6e6e6;
    color: #000;
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 8pt;
    line-height: 1.2;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
    padding: 56px 0 24px;
    display: flex;
    justify-content: center;
  }

  /* ---------- Paper sheet (A5-ish, portrait) ---------- */
  .sheet {
    width: 145mm;
    height: 200mm;
    background: #fff;
    box-shadow: 0 0 20px rgba(0,0,0,.18);
    position: relative;
  }

  /* One third of the sheet. Ends with a dashed cut / fold line. */
  .slot {
    min-height: 66.667mm;
    padding: 1.6mm 2mm 1.8mm;
    display: flex;
    flex-direction: column;
    border-bottom: .3mm dashed #888;
    position: relative;
  }
  .slot::after {
    content: '\2702';
    position: absolute;
    left: 1mm;
    bottom: -2mm;
    font-size: 6pt;
    line-height: 1;
    color: #777;
    background: #fff;
    padding: 0 .6mm;
  }

  .receipt {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: #fff;
    border: .35mm solid #0f2f4f;
    border-radius: 1.4mm;
    padding: 1.4mm;
  }

  /* ---------- Header ---------- */
  .hdr {
    height: 13mm;
    flex-shrink: 0;
    display: flex;
    align-items: stretch;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
    border: .5mm solid #c79b2b;
    border-radius: 2mm;
    overflow: hidden;
    margin-bottom: .9mm;
  }
  .hdr-left {
    width: 22mm;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    border-right: .5mm solid #c79b2b;
  }
  .logo-hex {
    width: 7.5mm;
    height: 7.5mm;
    border: .5mm solid #d9b14a;
    border-radius: 1.6mm;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(217,177,74,.1);
    margin-bottom: .6mm;
  }
  .logo-mj { font-size: 6.5pt; font-weight: 800; color: #d9b14a; letter-spacing: .3mm; }
  .logo-text { font-size: 4pt; font-weight: 700; color: #d9b14a; letter-spacing: .3mm; text-transform: uppercase; }

  .hdr-center {
    flex: 1;
    min-width: 0;
    position: relative;
    background: #fff;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    padding: 0 5mm;
  }
  .hdr-center::before,
  .hdr-center::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 4mm;
    background: linear-gradient(135deg, #0f2f4f 0%, #143f67 100%);
  }
  .hdr-center::before { left: -2mm; clip-path: polygon(0 0, 100% 50%, 0 100%); }
  .hdr-center::after { right: -2mm; clip-path: polygon(100% 0, 0 50%, 100% 100%); }
  .company { font-size: 14pt; font-weight: 800; color: #0f2f4f; letter-spacing: .5mm; line-height: 1.1; }
  .contact {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0 2mm;
    font-size: 5pt;
    font-weight: 600;
    color: #222;
    line-height: 1.35;
    text-align: center;
  }

  .hdr-right {
    width: 34mm;
    background: linear-gradient(135deg, #143f67 0%, #0f2f4f 100%);
    border-left: .5mm solid #c79b2b;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }
  .urdu-main { font-family: 'Noto Nastaliq Urdu', serif; font-size: 8.5pt; font-weight: 700; color: #d9b14a; line-height: 1.3; }
  .urdu-sub { font-family: 'Noto Nastaliq Urdu', serif; font-size: 4.8pt; color: #d9b14a; line-height: 1.3; }
  .urdu-div { font-size: 5pt; color: #d9b14a; line-height: 1.1; }

  /* ---------- Title strip ---------- */
  .title-bar {
    height: 4.4mm;
    flex-shrink: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fbf3dc;
    border: .25mm solid #c79b2b;
    padding: 0 2.5mm;
  }
  .t-en { font-size: 6pt; font-weight: 800; letter-spacing: .5mm; color: #0f2f4f; }
  .t-ur { font-family: 'Noto Nastaliq Urdu', serif; font-size: 5.5pt; font-weight: 700; color: #0f2f4f; line-height: 1.25; }

  /* ---------- Serial / date / bill / party ---------- */
  .info {
    display: flex;
    flex-shrink: 0;
    border: .25mm solid #000;
    border-top: none;
    background: #fafafa;
  }
  .cell {
    flex: 1;
    padding: .4mm 2mm;
    text-align: center;
    border-right: .25mm solid #999;
    display: flex;
    flex-direction: column;
    justify-content: center;
  }
  .cell:last-child { border-right: none; }
  .cell.party {
    flex: 2.4;
    flex-direction: row;
    align-items: center;
    justify-content: center;
    gap: 1.5mm;
  }
  .lbl { font-family: 'Noto Nastaliq Urdu', serif; font-size: 4.6pt; color: #666; line-height: 1.25; }
  .val { font-size: 7.5pt; font-weight: 700; }
  .party .lbl { font-size: 5.2pt; }
  .party .val { font-family: 'Noto Nastaliq Urdu', 'Segoe UI', Arial, sans-serif; font-size: 7pt; line-height: 1.25; }

  /* ---------- Receiving table ---------- */
  .tbl {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    margin-top: .8mm;
    flex-shrink: 0;
  }
  .tbl th {
    background: #e0e0e0;
    border: .25mm solid #000;
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 4.8pt;
    font-weight: 700;
    line-height: 1.25;
    height: 5.4mm;
    padding: 0 .4mm;
    overflow: hidden;
    vertical-align: middle;
  }
  .tbl th.en {
    font-family: 'Segoe UI', Arial, sans-serif;
    font-size: 6pt;
    line-height: 1.2;
  }
  .tbl td {
    border: .25mm solid #000;
    text-align: center;
    vertical-align: middle;
    height: 4.8mm;
    padding: 0 .6mm;
    font-size: 7.5pt;
    overflow: hidden;
    white-space: nowrap;
  }
  .tbl td.num { font-weight: 600; }
  .tbl td.khalis { font-weight: 800; color: #080; }
  .tbl td.mail { color: #7a4b00; font-weight: 700; }
  .tbl td.pts { color: #c00; font-weight: 700; font-size: 6.5pt; }
  .tbl td.sr { font-size: 6.5pt; color: #555; background: #f4f4f4; }
  .tbl td.desc {
    text-align: right;
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 5.4pt;
    line-height: 1.25;
    padding-right: 1.2mm;
  }
  .tbl tr.total td {
    background: #0f2f4f;
    color: #f3d58a;
    border-color: #0f2f4f;
    font-weight: 800;
    height: 5.6mm;
    font-size: 8pt;
  }
  .tbl tr.total td.desc {
    font-size: 5pt;
    line-height: 1.25;
    color: #f3d58a;
  }

  /* Compact mode (5-6 rows) */
  .compact .tbl th { height: 4.6mm; font-size: 4.5pt; }
  .compact .tbl td { height: 3.8mm; font-size: 6.8pt; }
  .compact .tbl td.desc { font-size: 4.8pt; line-height: 1.2; }
  .compact .tbl td.pts { font-size: 5.8pt; }
  .compact .tbl td.sr { font-size: 5.8pt; }
  .compact .tbl tr.total td { height: 4.5mm; font-size: 7.2pt; }
  .compact .tbl tr.total td.desc { font-size: 4.6pt; }

  /* ---------- Footer ---------- */
  .foot {
    margin-top: auto;
    flex-shrink: 0;
    border-top: .25mm solid #0f2f4f;
    padding-top: .6mm;
    text-align: center;
  }
  .note {
    font-family: 'Noto Nastaliq Urdu', serif;
    font-size: 5pt;
    line-height: 1.3;
    color: #444;
  }
  .sys {
    font-size: 4.8pt;
    line-height: 1.25;
    color: #777;
    margin-top: .2mm;
  }

  /* ---------- Screen toolbar ---------- */
  .no-print {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    padding: 10px;
    text-align: center;
    background: #f0f0f0;
    border-bottom: 1px solid #ccc;
  }
  .no-print button,
  .no-print a {
    padding: 5px 10px;
    margin: 0 3px;
    font-size: 11px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
  }
  .no-print a.slot-link {
    border: 1px solid #999;
    border-radius: 3px;
    color: #222;
    background: #fff;
  }
  .no-print a.slot-link.active {
    background: #0f2f4f;
    color: #fff;
    border-color: #0f2f4f;
  }

  @media print {
    .no-print { display: none !important; }
    body { background: #fff; padding: 0; display: block; }
    .sheet { box-shadow: none; height: 199.5mm; overflow: hidden; }
  }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">Print</button>
  <button onclick="exportPdf()">Export PDF</button>
  <button onclick="exportJpg()">Export JPG</button>
  &nbsp;|&nbsp; Position:
  <a class="slot-link <?= $slot === 1 ? 'active' : '' ?>" href="<?= $slotUrl(1) ?>">Top</a>
  <a class="slot-link <?= $slot === 2 ? 'active' : '' ?>" href="<?= $slotUrl(2) ?>">Middle</a>
  <a class="slot-link <?= $slot === 3 ? 'active' : '' ?>" href="<?= $slotUrl(3) ?>">Bottom</a>
  &nbsp;|&nbsp;
  <a href="<?= url('invoices/index.php') ?>">Back Invoice List</a>
</div>

<div class="sheet">
  <div class="slot" style="margin-top: <?= $slotOffsetMm ?>mm;">
    <div class="receipt <?= $density === 'compact' ? 'compact' : '' ?>">

      <div class="hdr">
        <div class="hdr-left">
          <div class="logo-hex"><div class="logo-mj">MJ</div></div>
          <div class="logo-text">GOLD CASTING</div>
        </div>
        <div class="hdr-center">
          <div class="company">MJ CASTING</div>
          <div class="contact">
            <?php if ($workshopAddress !== ''): ?><span>📍 <?= htmlspecialchars($workshopAddress) ?></span><?php endif; ?>
            <?php if ($phones): ?><span>📞 <?= htmlspecialchars(implode(' ', $phones)) ?></span><?php endif; ?>
          </div>
        </div>
        <div class="hdr-right">
          <div class="urdu-main"><?= htmlspecialchars($workshopNameUrdu) ?></div>
          <div class="urdu-sub">گولڈ کاسٹنگ اینڈ جیولری ورکس</div>
          <div class="urdu-div">◆◆◆</div>
        </div>
      </div>

      <div class="title-bar">
        <span class="t-en">GOLD RECEIVING RECEIPT</span>
        <span class="t-ur">سونا وصولی رسید</span>
      </div>

      <div class="info">
        <div class="cell">
          <div class="lbl">سیریل نمبر</div>
          <div class="val"><?= htmlspecialchars($invoice['invoice_no']) ?></div>
        </div>
        <div class="cell">
          <div class="lbl">تاریخ</div>
          <div class="val"><?= date('d-m-Y', strtotime($invoice['invoice_date'])) ?></div>
        </div>
        <div class="cell">
          <div class="lbl">بل نمبر</div>
          <div class="val"><?= htmlspecialchars($invoice['manual_book_no'] ?: '-') ?></div>
        </div>
        <div class="cell party">
          <span class="lbl">پارٹی:</span>
          <span class="val"><?= htmlspecialchars($invoice['customer_name'] ?? 'Cash Party') ?></span>
        </div>
      </div>

      <table class="tbl">
        <colgroup>
          <?php if ($hasPoints): ?>
            <col style="width:15%"><col style="width:13%"><col style="width:8%"><col style="width:15%"><col style="width:16%"><col style="width:28%"><col style="width:5%">
          <?php else: ?>
            <col style="width:16%"><col style="width:14%"><col style="width:9%"><col style="width:16%"><col style="width:39%"><col style="width:6%">
          <?php endif; ?>
        </colgroup>
        <thead>
          <tr>
            <th>خالص وزن</th>
            <th>میل</th>
            <th>رتی</th>
            <th>کل وزن</th>
            <?php if ($hasPoints): ?><th class="en">Points</th><?php endif; ?>
            <th>تفصیل</th>
            <th class="en">#</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $i => $r): ?>
            <tr>
              <td class="num khalis"><?= $fmtG($r['khalis']) ?></td>
              <td class="num mail"><?= $r['mail'] !== null ? $fmtMailG($r['mail']) : '-' ?></td>
              <td class="num"><?= formatRattiPrint($r['ratti']) ?></td>
              <td class="num"><?= $r['gross'] !== null ? $fmtG($r['gross']) : '-' ?></td>
              <?php if ($hasPoints): ?>
                <td class="pts"><?= htmlspecialchars($r['point_text']) ?></td>
              <?php endif; ?>
              <td class="desc"><?= htmlspecialchars($r['desc']) ?></td>
              <td class="sr"><?= $i + 1 ?></td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$rows): ?>
            <tr>
              <td colspan="<?= $cols ?>" class="desc" style="text-align:center;color:#888;">کوئی سونا وصول نہیں ہوا</td>
            </tr>
          <?php endif; ?>

          <?php if ($density === 'normal'): ?>
            <?php for ($k = max($n, $rows ? 0 : 1); $k < $minRows; $k++): ?>
              <tr>
                <td>&nbsp;</td>
                <?php for ($c = 1; $c < $cols - 1; $c++): ?><td></td><?php endfor; ?>
                <td class="sr"></td>
              </tr>
            <?php endfor; ?>
          <?php endif; ?>

          <tr class="total">
            <td class="num"><?= $fmtG($totalKhalis) ?></td>
            <td class="num"><?= $n && $grossSum > 0 ? $fmtMailG($mailSum) : '-' ?></td>
            <td></td>
            <td class="num"><?= $grossSum > 0 ? $fmtG($grossSum) : '-' ?></td>
            <?php if ($hasPoints): ?><td></td><?php endif; ?>
            <td class="desc" colspan="2">ٹوٹل وصولی (خالص)</td>
          </tr>
        </tbody>
      </table>

      <div class="foot">
        <div class="note">
          نوٹ: پٹھور سونے کی کوئی گارنٹی نہیں ہے، براہ کرم مال وصول کرتے وقت چیک کریں بعد میں شکایت قابل قبول نہیں۔
        </div>
        <div class="sys">
          This document is computer generated and does not require any stamp/signature &nbsp;|&nbsp; <?= htmlspecialchars($workshopName) ?> &nbsp;|&nbsp; Printed: <?= date('d-m-Y h:i A') ?>
        </div>
      </div>

    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
const exportName = <?= json_encode($invoice['invoice_no'] . '-receiving') ?>;

async function captureReceipt() {
  const el = document.querySelector('.receipt');
  if (!window.html2canvas) {
    alert('Export library not loaded. Please check internet connection.');
    throw new Error('html2canvas missing');
  }
  await document.fonts.ready;
  return await html2canvas(el, {scale: 3, backgroundColor: '#ffffff', useCORS: true});
}

async function exportJpg() {
  const canvas = await captureReceipt();
  const a = document.createElement('a');
  a.download = exportName + '.jpg';
  a.href = canvas.toDataURL('image/jpeg', 0.95);
  a.click();
}

async function exportPdf() {
  const canvas = await captureReceipt();
  if (!window.jspdf) {
    alert('PDF library not loaded. Please check internet connection.');
    return;
  }
  const img = canvas.toDataURL('image/jpeg', 0.98);
  const { jsPDF } = window.jspdf;
  const pdf = new jsPDF({
    orientation: canvas.width > canvas.height ? 'landscape' : 'portrait',
    unit: 'pt',
    format: [canvas.width, canvas.height]
  });
  pdf.addImage(img, 'JPEG', 0, 0, canvas.width, canvas.height);
  pdf.save(exportName + '.pdf');
}
</script>
</body>
</html>
