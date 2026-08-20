<?php
require_once __DIR__ . '/config.php';
requireAuth();

$pageTitle = 'Select Module';
$hideSidebar = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Module - M.J Casting</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
        <script>
        (function(){try{var t=localStorage.getItem('mj_theme')||'dark';document.documentElement.setAttribute('data-theme',t==='light'?'light':'dark');}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();
    </script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #0b1120;
            color: #e8edf5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container { text-align: center; max-width: 900px; width: 100%; }
        .logo { margin-bottom: 48px; }
        .logo h1 { font-family: 'Playfair Display', serif; font-size: 2.2rem; color: #DAA520; }
        .logo p { color: #5a6d8a; font-size: 0.9rem; margin-top: 6px; font-family: 'Noto Nastaliq Urdu', serif; direction: rtl; }
        .subtitle { color: #8899b4; font-size: 1.1rem; margin-bottom: 40px; }
        .modules { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; }
        .module-card {
            background: #131c31;
            border: 1px solid #1e3050;
            border-radius: 24px;
            padding: 48px 32px;
            text-decoration: none;
            color: inherit;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        .module-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .module-card.gold {
            border-color: rgba(218, 165, 32, 0.4);
        }
        .module-card.gold:hover {
            border-color: #DAA520;
            box-shadow: 0 20px 60px rgba(218, 165, 32, 0.15);
        }
        .module-card.wax {
            border-color: rgba(56, 189, 248, 0.3);
        }
        .module-card.wax:hover {
            border-color: #38bdf8;
            box-shadow: 0 20px 60px rgba(56, 189, 248, 0.15);
        }
        .module-icon {
            font-size: 3.5rem;
        }
        .module-card.gold .module-icon { color: #DAA520; }
        .module-card.wax .module-icon { color: #38bdf8; }
        .module-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.6rem;
            font-weight: 700;
        }
        .module-card.gold .module-name { color: #DAA520; }
        .module-card.wax .module-name { color: #38bdf8; }
        .module-urdu {
            font-family: 'Noto Nastaliq Urdu', serif;
            direction: rtl;
            color: #5a6d8a;
            font-size: 1rem;
        }
        .module-desc {
            color: #8899b4;
            font-size: 0.88rem;
            line-height: 1.5;
        }
        .user-info {
            margin-top: 40px;
            color: #5a6d8a;
            font-size: 0.85rem;
        }
        .user-info a { color: #DAA520; text-decoration: none; }
        .user-info a:hover { text-decoration: underline; }
        @media (max-width: 640px) {
            .modules { grid-template-columns: 1fr; }
            .module-card { padding: 32px 24px; }
        }
    
        :root{--bg-body:#0b1120;--bg-card:#131c31;--bg-surface:#1a2744;--border-color:#1e3050;--text-primary:#e8edf5;--text-secondary:#8899b4;--text-muted:#5a6d8a;--gold-primary:#DAA520;--shadow-auth:0 20px 50px rgba(0,0,0,.5)}
        html[data-theme="light"]{--bg-body:#f5f7fb;--bg-card:#ffffff;--bg-surface:#f1f5f9;--border-color:#d9e2ec;--text-primary:#111827;--text-secondary:#4b5563;--text-muted:#6b7280;--gold-primary:#B8860B;--shadow-auth:0 20px 50px rgba(15,23,42,.12)}
        html[data-theme="light"] body{background:var(--bg-body)!important;color:var(--text-primary)!important}
        html[data-theme="light"] .card, html[data-theme="light"] .module-card{background:var(--bg-card)!important;border-color:var(--border-color)!important;box-shadow:var(--shadow-auth)!important;color:var(--text-primary)!important}
        html[data-theme="light"] .field input{background:#fff!important;color:var(--text-primary)!important;border-color:var(--border-color)!important}
        .standalone-theme-toggle{position:fixed;top:16px;right:16px;z-index:20;display:inline-flex;align-items:center;gap:7px;padding:8px 12px;border-radius:999px;border:1px solid var(--border-color);background:var(--bg-card);color:var(--text-secondary);font-weight:700;cursor:pointer}
        html[data-theme="light"] .logo p, html[data-theme="light"] .subtitle, html[data-theme="light"] .hint, html[data-theme="light"] .field label, html[data-theme="light"] .checkbox{color:var(--text-secondary)!important}


    </style>
</head>
<body>
    <button type="button" class="standalone-theme-toggle" id="standalone-theme-toggle">🌙 <span>Dark</span></button>

    <div class="container">
        <div class="logo">
            <h1>M.J Casting</h1>
            <p>ایم جے کاسٹنگ اور ڈیزائن</p>
        </div>
        <div class="subtitle">Select a module to continue — <span class="font-urdu">ماڈیول منتخب کریں</span></div>
        
        <div class="modules">
            <a href="set_module.php?module=casting" class="module-card gold">
                <div class="module-icon"><i class="bi bi-gem"></i></div>
                <div class="module-name">Gold Casting</div>
                <div class="module-urdu">گولڈ کاسٹنگ</div>
                <div class="module-desc">
                    Gold casting parchis, ratti/khalis calculations,<br>
                    inventory management, customer ledger
                </div>
            </a>
            
            <a href="set_module.php?module=wax" class="module-card wax">
                <div class="module-icon"><i class="bi bi-printer"></i></div>
                <div class="module-name">Wax & 3D Design</div>
                <div class="module-urdu">ویکس اور ڈیزائن</div>
                <div class="module-desc">
                    Wax printing & 3D modeling invoices,<br>
                    workers commission, partner shares, expenses
                </div>
            </a>
        </div>
        
        <div class="user-info">
            Logged in as <strong><?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></strong> — 
            <a href="logout.php">Logout</a>
        </div>
    </div>

<script>
(function(){
 function apply(t,p){t=t==='light'?'light':'dark';document.documentElement.setAttribute('data-theme',t);if(p){try{localStorage.setItem('mj_theme',t)}catch(e){}}var b=document.getElementById('standalone-theme-toggle');if(b){b.innerHTML=(t==='light'?'☀️ <span>Light</span>':'🌙 <span>Dark</span>');}}
 document.addEventListener('DOMContentLoaded',function(){var t='dark';try{t=localStorage.getItem('mj_theme')||document.documentElement.getAttribute('data-theme')||'dark'}catch(e){}apply(t,false);var b=document.getElementById('standalone-theme-toggle');if(b)b.addEventListener('click',function(){apply(document.documentElement.getAttribute('data-theme')==='light'?'dark':'light',true)});});
})();
</script>
</body>
</html>