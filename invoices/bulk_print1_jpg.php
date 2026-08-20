<?php
require_once __DIR__ . '/../config.php';
requireAuth();

$db = getDB();

$ids = $_POST['invoice_ids'] ?? [];
if (!is_array($ids)) $ids = [];
if (empty($ids) && query('ids')) $ids = explode(',', (string)query('ids'));
$ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($id) => $id > 0)));

$invoices = [];
if ($ids) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT id, invoice_no FROM invoices WHERE id IN ($ph) ORDER BY FIELD(id, $ph)");
    $stmt->execute(array_merge($ids, $ids));
    $invoices = $stmt->fetchAll();
}

function jsSafe($value): string {
    return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bulk Invoice JPG Export</title>
<style>
body{font-family:Arial,sans-serif;background:#f5f7fb;color:#111827;margin:0;padding:24px;}
.panel{max-width:980px;margin:0 auto;background:#fff;border:1px solid #d9e2ec;border-radius:14px;padding:22px;box-shadow:0 8px 24px rgba(15,23,42,.08)}
h1{margin:0 0 8px;font-size:22px;color:#B8860B}.muted{color:#6b7280;font-size:14px;margin-bottom:18px}.actions{display:flex;gap:10px;flex-wrap:wrap;margin:16px 0}.btn{border:0;border-radius:8px;padding:10px 16px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:8px}.btn-primary{background:#B8860B;color:#fff}.btn-outline{background:#fff;border:1px solid #d9e2ec;color:#374151}.progress{margin-top:16px;padding:12px;background:#f1f5f9;border-radius:10px;font-family:monospace}.list{margin-top:14px}.list li{margin:5px 0}.warn{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;padding:12px;border-radius:10px;margin-top:12px;}
/* Full-size offscreen iframe so print1.php layout/font/alignment stays exactly like single preview. */
.frames{position:fixed;left:-5000px;top:0;width:900px;height:1300px;overflow:visible;opacity:0;pointer-events:none;}
.frames iframe{width:900px;height:1300px;border:0;background:#fff;overflow:visible;}
</style>
</head>
<body>
<div class="panel">
    <h1>Bulk Invoice Receipt JPG Export</h1>
    <?php if (!$invoices): ?>
        <div class="warn">No invoices selected. Go back and tick invoice checkboxes first.</div>
        <div class="actions"><a class="btn btn-outline" href="<?= url('invoices/index.php') ?>">Back to Invoices</a></div>
    <?php else: ?>
        <div class="muted">
            Selected <?= count($invoices) ?> invoice(s). This downloads the exact current <strong>print1.php</strong> receipt as JPG.
            If browser blocks automatic multiple downloads, click <strong>Start Download</strong> once.
        </div>
        <div class="actions">
            <button class="btn btn-primary" type="button" onclick="startDownloads()">Start Download</button>
            <a class="btn btn-outline" href="<?= url('invoices/index.php') ?>">Back to Invoices</a>
        </div>
        <div id="progress" class="progress">Ready...</div>
        <ol class="list">
            <?php foreach ($invoices as $inv): ?><li><?= htmlspecialchars($inv['invoice_no']) ?></li><?php endforeach; ?>
        </ol>
        <div class="frames" id="frames"></div>
    <?php endif; ?>
</div>

<?php if ($invoices): ?>
<script>
const invoices = <?= jsSafe(array_map(fn($i) => ['id'=>(int)$i['id'], 'invoice_no'=>$i['invoice_no']], $invoices)) ?>;
const printUrlBase = <?= jsSafe(url('invoices/print1.php?id=')) ?>;
let started = false;

function setProgress(t){document.getElementById('progress').textContent=t;}
function sleep(ms){return new Promise(r=>setTimeout(r,ms));}

function loadFrame(invoice){
    return new Promise((resolve,reject)=>{
        const iframe=document.createElement('iframe');
        // IMPORTANT: load exact print1.php, no special query/css, so output matches single print preview.
        iframe.src=printUrlBase + invoice.id;
        iframe.onload=()=>resolve(iframe);
        iframe.onerror=()=>reject(new Error('Failed to load '+invoice.invoice_no));
        document.getElementById('frames').appendChild(iframe);
    });
}

function injectHtml2Canvas(win){
    return new Promise((resolve,reject)=>{
        if(win.html2canvas) return resolve();
        const s=win.document.createElement('script');
        s.src='https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
        s.onload=()=>resolve();
        s.onerror=()=>reject(new Error('html2canvas could not load'));
        win.document.head.appendChild(s);
    });
}

async function waitForExactLayout(iframe){
    const win=iframe.contentWindow;
    const doc=win.document;
    if(doc.fonts && doc.fonts.ready) {
        try { await doc.fonts.ready; } catch(e) {}
    }
    await Promise.all(Array.from(doc.images || []).map(img => img.complete ? Promise.resolve() : new Promise(res => { img.onload=img.onerror=res; })));
    await injectHtml2Canvas(win);
    // Let print1.php finish its exact browser layout after fonts/scripts.
    await sleep(1000);
}

function downloadDataUrl(filename,dataUrl){
    const a=document.createElement('a');
    a.href=dataUrl;
    a.download=filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
}

async function startDownloads(){
    if(started) return;
    started=true;
    for(let i=0;i<invoices.length;i++){
        const invoice=invoices[i];
        setProgress(`Loading ${i+1}/${invoices.length}: ${invoice.invoice_no}`);
        try{
            const iframe=await loadFrame(invoice);
            await waitForExactLayout(iframe);
            const win=iframe.contentWindow;
            const doc=win.document;
            const el=doc.querySelector('.print-container');
            if(!el) throw new Error('print-container not found');

            setProgress(`Rendering ${i+1}/${invoices.length}: ${invoice.invoice_no}`);
            const rect = el.getBoundingClientRect();
            const canvas=await win.html2canvas(el, {
                scale: 3,
                backgroundColor: '#ffffff',
                useCORS: true,
                logging: false,
                width: Math.ceil(rect.width),
                height: Math.ceil(rect.height),
                windowWidth: 900,
                windowHeight: 1300,
                scrollX: 0,
                scrollY: 0
            });
            downloadDataUrl(invoice.invoice_no + '_receipt.jpg', canvas.toDataURL('image/jpeg', 0.98));
            iframe.remove();
            await sleep(700);
        }catch(e){
            console.error(e);
            setProgress(`Error on ${invoice.invoice_no}: ${e.message}`);
            started=false;
            return;
        }
    }
    setProgress(`Done. Downloaded ${invoices.length} JPG file(s).`);
}

window.addEventListener('load',()=>setTimeout(startDownloads,800));
</script>
<?php endif; ?>
</body>
</html>
