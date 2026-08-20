<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Wax & Design' ?> - M.J Casting</title>
    <script id="wax-theme-init">
        (function(){
            try {
                var saved = localStorage.getItem('mj_theme') || 'dark';
                document.documentElement.setAttribute('data-theme', saved === 'light' ? 'light' : 'dark');
            } catch(e) { document.documentElement.setAttribute('data-theme', 'dark'); }
        })();
    </script>
    <link rel="stylesheet" href="<?= $base_url ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <!-- jQuery and Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        :root {
            --primary: #38bdf8;
            --primary-dark: #0284c7;
            --success: #10b981;
            --danger: #f43f5e;
            --warning: #f59e0b;
            --info: #38bdf8;
            --dark: #0b1120;
            --light: #f8f9fa;
            --border: #1e3050;
            --bg-card: #131c31;
            --bg-body: #0b1120;
            --text-primary: #e8edf5;
            --text-secondary: #8899b4;
            --text-muted: #5a6d8a;
            --shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }
        .navbar {
            background: linear-gradient(135deg, #0d1528, #1a2744);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .navbar .brand {
            color: var(--primary);
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
            font-family: 'Playfair Display', serif;
        }
        .navbar .brand small {
            font-family: 'Inter', sans-serif;
            font-size: 0.7rem;
            color: var(--text-muted);
            display: block;
        }
        .nav-links { display: flex; gap: 4px; flex-wrap: wrap; }
        .nav-links a {
            color: var(--text-secondary);
            padding: 8px 14px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .nav-links a:hover { background: rgba(56, 189, 248, 0.1); color: var(--primary); }
        .nav-links a:last-child { color: var(--danger); }
        .nav-links a:last-child:hover { background: rgba(244,63,94,0.1); }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: var(--shadow);
        }
        h2, h3 { margin-top: 0; color: var(--text-primary); }
        label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.85rem; color: var(--text-secondary); }
        input, select, textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.9rem;
            background: #1a2744;
            color: var(--text-primary);
            transition: border-color 0.2s;
            font-family: 'Inter', sans-serif;
        }
        input:focus, select:focus, textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.1);
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.88rem; }
        th { background: #1a2744; color: var(--text-secondary); font-weight: 600; font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.05em; }
        td { color: var(--text-primary); }
        tr:hover td { background: rgba(56, 189, 248, 0.03); }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .btn-primary { background: var(--primary); color: #000; }
        .btn-primary:hover { background: #7dd3fc; transform: translateY(-1px); }
        .btn-success { background: var(--success); color: #fff; }
        .btn-success:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-warning { background: var(--warning); color: #000; }
        .btn-secondary { background: #1a2744; color: var(--text-primary); border: 1px solid var(--border); }
        .btn-secondary:hover { border-color: var(--primary); color: var(--primary); }
        .btn-sm { padding: 4px 10px; font-size: 0.78rem; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-muted { color: var(--text-muted); }
        .text-success { color: var(--success); }
        .text-danger { color: var(--danger); }
        .mono { font-family: 'JetBrains Mono', monospace; }
        .mb-4 { margin-bottom: 16px; }
        .mt-4 { margin-top: 16px; }
        .pagination { display: flex; gap: 4px; justify-content: center; margin-top: 16px; }
        .pagination a { padding: 6px 12px; border: 1px solid var(--border); border-radius: 6px; text-decoration: none; color: var(--text-secondary); font-size: 0.85rem; }
        .pagination a:hover { border-color: var(--primary); color: var(--primary); }
        .pagination .active { background: var(--primary); color: #000; border-color: var(--primary); }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.88rem; }
        .alert-success { background: rgba(16,185,129,0.1); color: var(--success); border: 1px solid rgba(16,185,129,0.2); }
        .alert-danger { background: rgba(244,63,94,0.1); color: var(--danger); border: 1px solid rgba(244,63,94,0.2); }
        .alert-info { background: rgba(56,189,248,0.1); color: var(--primary); border: 1px solid rgba(56,189,248,0.2); }
        .overflow-x { overflow-x: auto; }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 8px; }
            .nav-links { justify-content: center; }
            .container { padding: 12px; }
        }
        .module-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            background: rgba(56,189,248,0.15);
            color: var(--primary);
            border: 1px solid rgba(56,189,248,0.2);
        }
    </style>
    <?php
    // Define base URL from config if not set
    if (!isset($base_url)) {
        $base_url = rtrim(getBaseUrl(), '/');
    }
    ?>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="brand">
            🖨️ Wax & 3D Design
            <small>ویکس اور ڈیزائن <span class="module-badge"><i class="bi bi-arrow-left-right"></i> Wax Module</span></small>
        </a>
        <div class="nav-links">
            <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="invoice_create.php"><i class="bi bi-plus-circle"></i> New Invoice</a>
            <a href="bulk_invoice.php"><i class="bi bi-files"></i> Bulk Invoice</a>
            <a href="customers.php"><i class="bi bi-people"></i> Customers</a>
            <a href="items.php"><i class="bi bi-box"></i> Items</a>
            <a href="workers.php"><i class="bi bi-person-gear"></i> Workers</a>
            <a href="partners.php"><i class="bi bi-handshake"></i> Partners</a>
            <a href="payments.php"><i class="bi bi-cash-coin"></i> Payments</a>
            <a href="expenses.php"><i class="bi bi-receipt"></i> Expenses</a>
            <a href="reports.php"><i class="bi bi-file-bar-graph"></i> Reports</a>
            <a href="price_list.php"><i class="bi bi-list-ol"></i> Price List</a>
            <a href="<?= $base_url ?>/dashboard.php"><i class="bi bi-arrow-left-right"></i> Casting</a>
            <a href="<?= $base_url ?>/logout.php"><i class="bi bi-box-arrow-left"></i> Logout</a>
        </div>
    </nav>
    <div class="container">
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>