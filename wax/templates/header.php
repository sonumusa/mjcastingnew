<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Wax & Design' ?> - M.J Casting</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <?php if (!isset($base_url)) { $base_url = '/mj_casting_wax'; } ?>

    <style>
        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary:        #38bdf8;
            --primary-dark:   #0284c7;
            --success:        #10b981;
            --danger:         #f43f5e;
            --warning:        #f59e0b;
            --border:         #1e3050;
            --bg-card:        #131c31;
            --bg-body:        #0b1120;
            --text-primary:   #e8edf5;
            --text-secondary: #8899b4;
            --text-muted:     #5a6d8a;
            --shadow:         0 2px 8px rgba(0,0,0,0.3);
        }

        /* ══════════════════════════════════════════
           BASE
        ══════════════════════════════════════════ */
        html, body {
            width: 100%;
            min-height: 100vh;
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* ══════════════════════════════════════════
           NAVBAR  ← THE KEY FIX
        ══════════════════════════════════════════ */
        .navbar {
            /* positioning */
            position: sticky;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 9999;

            /* layout */
            display: flex;
            flex-direction: row;          /* horizontal */
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;

            /* looks */
            background: linear-gradient(135deg, #0d1528 0%, #1a2744 100%);
            padding: 10px 24px;
            border-bottom: 1px solid var(--border);
            box-shadow: 0 2px 12px rgba(0,0,0,0.4);
        }

        /* ── Brand ──────────────────────────────── */
        .navbar .brand {
            display: flex;
            flex-direction: column;
            text-decoration: none;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .navbar .brand .brand-title {
            color: var(--primary);
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .navbar .brand .brand-sub {
            color: var(--text-muted);
            font-size: 0.68rem;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 2px;
        }

        /* ── Module Badge ───────────────────────── */
        .module-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.68rem;
            font-weight: 600;
            background: rgba(56,189,248,0.15);
            color: var(--primary);
            border: 1px solid rgba(56,189,248,0.25);
        }

        /* ── Nav Links ──────────────────────────── */
        .nav-links {
            display: flex;
            flex-direction: row;           /* horizontal links */
            align-items: center;
            flex-wrap: wrap;
            gap: 2px;
        }

        .nav-links a {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            color: var(--text-secondary);
            padding: 7px 10px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.80rem;
            font-weight: 500;
            white-space: nowrap;
            transition: background 0.2s, color 0.2s;
        }

        .nav-links a:hover {
            background: rgba(56,189,248,0.12);
            color: var(--primary);
        }

        .nav-links a.active {
            background: rgba(56,189,248,0.18);
            color: var(--primary);
        }

        .nav-links a.nav-logout {
            color: var(--danger);
        }

        .nav-links a.nav-logout:hover {
            background: rgba(244,63,94,0.12);
            color: var(--danger);
        }

        /* ══════════════════════════════════════════
           CONTAINER
        ══════════════════════════════════════════ */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px 20px;
        }

        /* ══════════════════════════════════════════
           CARD
        ══════════════════════════════════════════ */
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }

        h2, h3 { margin-top: 0; color: var(--text-primary); }

        /* ══════════════════════════════════════════
           FORM ELEMENTS
        ══════════════════════════════════════════ */
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        input, select, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            background: #fff;
            color: #000;
            font-family: 'Inter', sans-serif;
            transition: border-color 0.2s;
        }

        input::placeholder, textarea::placeholder {
            color: #666;
            opacity: 1;
        }

        /* Select dropdown text color fix */
        select option {
            background: #fff;
            color: #000;
            padding: 5px;
        }

        select option:checked {
            background: #38bdf8;
            color: #000;
        }

        select option:hover {
            background: #e0e0e0;
            color: #000;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(56,189,248,0.1);
        }

        /* ══════════════════════════════════════════
           SELECT2 STYLING
        ══════════════════════════════════════════ */
        .select2-container--default .select2-selection--single {
            background-color: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
            height: auto;
            padding: 0;
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000;
            padding: 10px 14px;
            line-height: 1.4;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            right: 8px;
        }

        .select2-dropdown {
            background-color: #fff;
            border: 1px solid var(--border);
            border-radius: 8px;
        }

        .select2-results__option {
            color: #000;
            background-color: #fff;
            padding: 10px 14px;
        }

        .select2-results__option--highlighted,
        .select2-results__option[aria-selected=true] {
            background-color: #38bdf8;
            color: #000;
        }

        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(56,189,248,0.1);
        }


        /* ══════════════════════════════════════════
           TABLE
        ══════════════════════════════════════════ */
        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 0.88rem;
        }

        th {
            background: #1a2744;
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tr:hover td { background: rgba(56,189,248,0.03); }

        /* ══════════════════════════════════════════
           BUTTONS
        ══════════════════════════════════════════ */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 9px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }

        .btn:hover         { opacity: 0.88; transform: translateY(-1px); }
        .btn-sm            { padding: 4px 10px; font-size: 0.78rem; }
        .btn-primary       { background: var(--primary);  color: #000; }
        .btn-success       { background: var(--success);  color: #fff; }
        .btn-danger        { background: var(--danger);   color: #fff; }
        .btn-warning       { background: var(--warning);  color: #000; }
        .btn-secondary     { background: #1a2744; color: var(--text-primary); border: 1px solid var(--border); }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); opacity: 1; }

        /* ══════════════════════════════════════════
           ALERTS
        ══════════════════════════════════════════ */
        .alert {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 0.88rem;
        }

        .alert-success { background: rgba(16,185,129,0.1);  color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
        .alert-danger  { background: rgba(244,63,94,0.1);   color: var(--danger);  border: 1px solid rgba(244,63,94,0.2);  }
        .alert-info    { background: rgba(56,189,248,0.1);  color: var(--primary); border: 1px solid rgba(56,189,248,0.2); }

        /* ══════════════════════════════════════════
           PAGINATION
        ══════════════════════════════════════════ */
        .pagination {
            display: flex;
            gap: 4px;
            justify-content: center;
            margin-top: 16px;
        }

        .pagination a {
            padding: 6px 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-secondary);
            font-size: 0.85rem;
            transition: all 0.2s;
        }

        .pagination a:hover   { border-color: var(--primary); color: var(--primary); }
        .pagination .active   { background: var(--primary); color: #000; border-color: var(--primary); }

        /* ══════════════════════════════════════════
           UTILITIES
        ══════════════════════════════════════════ */
        .text-right   { text-align: right; }
        .text-center  { text-align: center; }
        .text-muted   { color: var(--text-muted); }
        .text-success { color: var(--success); }
        .text-danger  { color: var(--danger); }
        .mono         { font-family: 'JetBrains Mono', monospace; }
        .mb-4         { margin-bottom: 16px; }
        .mt-4         { margin-top: 16px; }
        .overflow-x   { overflow-x: auto; }

        /* ══════════════════════════════════════════
           RESPONSIVE
        ══════════════════════════════════════════ */
        @media (max-width: 1100px) {
            .nav-links a { font-size: 0.75rem; padding: 6px 8px; }
        }

        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                align-items: flex-start;
                padding: 12px 16px;
            }
            .nav-links { width: 100%; }
            .container { padding: 12px; }
        }
    </style>
</head>
<body>

<!-- ══════════════════════════════════════════════════
     TOP NAVBAR
══════════════════════════════════════════════════ -->
<nav class="navbar">

    <!-- Brand / Logo -->
    <a href="index.php" class="brand">
        <span class="brand-title">🖨️ Wax &amp; 3D Design</span>
        <span class="brand-sub">
            ویکس اور ڈیزائن
            <span class="module-badge">
                <i class="bi bi-layers"></i> Wax Module
            </span>
        </span>
    </a>

    <!-- Nav Links -->
    <div class="nav-links">

        <?php
        /* auto-highlight active page */
        $current = basename($_SERVER['PHP_SELF']);
        function nav_active(string $file, string $current): string {
            return $file === $current ? ' active' : '';
        }
        ?>

        <a href="index.php"          class="<?= nav_active('index.php',          $current) ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="invoice_create.php" class="<?= nav_active('invoice_create.php', $current) ?>">
            <i class="bi bi-plus-circle"></i> New Invoice
        </a>
        <a href="invoices.php"   class="<?= nav_active('invoices.php',   $current) ?>">
            <i class="bi bi-files"></i> Invoices List
        </a>
                <a href="payments.php"       class="<?= nav_active('payments.php',       $current) ?>">
            <i class="bi bi-cash-coin"></i> Payments
        </a>
        <a href="expenses.php"       class="<?= nav_active('expenses.php',       $current) ?>">
            <i class="bi bi-receipt"></i> Expenses
        </a>
        <a href="reports.php"        class="<?= nav_active('reports.php',        $current) ?>">
            <i class="bi bi-file-bar-graph"></i> Reports
        </a>
        <a href="customers.php"      class="<?= nav_active('customers.php',      $current) ?>">
            <i class="bi bi-people"></i> Customers
        </a>
        <a href="items.php"          class="<?= nav_active('items.php',          $current) ?>">
            <i class="bi bi-box"></i> Items
        </a>
        <a href="workers.php"        class="<?= nav_active('workers.php',        $current) ?>">
            <i class="bi bi-person-gear"></i> Workers
        </a>
        <a href="partners.php"       class="<?= nav_active('partners.php',       $current) ?>">
            <i class="bi bi-handshake"></i> Partners
        </a>

        <a href="price_list.php"     class="<?= nav_active('price_list.php',     $current) ?>">
            <i class="bi bi-list-ol"></i> Price List
        </a>
        <a href="<?= $base_url ?>/module_select.php">
            <i class="bi bi-arrow-left-right"></i> Switch
        </a>
        <a href="<?= $base_url ?>/logout.php" class="nav-logout">
            <i class="bi bi-box-arrow-left"></i> Logout
        </a>

    </div><!-- /nav-links -->
</nav>
<!-- ══════════════════════════════════════════════════ -->


<!-- ══════════════════════════════════════════════════
     PAGE CONTENT STARTS HERE
══════════════════════════════════════════════════ -->
<div class="container">

    <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>