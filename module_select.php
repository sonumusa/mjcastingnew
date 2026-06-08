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
    </style>
</head>
<body>
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
</body>
</html>