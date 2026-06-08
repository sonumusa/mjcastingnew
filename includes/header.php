<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'M.J Casting' ?> - Gold Workshop</title>
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>">
    <?= $extraCss ?? '' ?>
</head>
<body>
    <?php $currentUser = getCurrentUser(); ?>
    
    <?php if (!isset($hideSidebar)): ?>
    <!-- ============================================================ -->
    <!-- Sidebar -->
    <!-- ============================================================ -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <span class="logo-icon">&#9679;</span>
                <div class="logo-text">
                    <span class="logo-title">M.J Casting</span>
                    <span class="logo-subtitle font-urdu">ایم جے کاسٹنگ</span>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <a href="<?= url('dashboard.php') ?>" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
                <span class="font-urdu nav-urdu">ڈیش بورڈ</span>
            </a>
            
            <div class="nav-section-label">Parties</div>
            <a href="<?= url('customers/index.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'customers') !== false ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Parties</span>
                <span class="font-urdu nav-urdu">گاہک / پارٹیاں</span>
            </a>
            
            <div class="nav-section-label">Transactions</div>
            <a href="<?= url('invoices/index.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'invoices') !== false ? 'active' : '' ?>">
                <i class="bi bi-file-text"></i>
                <span>Invoices</span>
                <span class="font-urdu nav-urdu">بل</span>
            </a>
            <a href="<?= url('gold-receipts/index.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'gold-receipts') !== false ? 'active' : '' ?>">
                <i class="bi bi-inbox"></i>
                <span>Gold Receipts</span>
                <span class="font-urdu nav-urdu">سونا وصولی</span>
            </a>
            
            <div class="nav-section-label">Reports</div>
            <a href="<?= url('ledger/index.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'ledger') !== false ? 'active' : '' ?>">
                <i class="bi bi-journal"></i>
                <span>Ledger</span>
                <span class="font-urdu nav-urdu">لیجر</span>
            </a>
            <a href="<?= url('inventory/index.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'inventory') !== false ? 'active' : '' ?>">
                <i class="bi bi-box"></i>
                <span>Inventory</span>
                <span class="font-urdu nav-urdu">اسٹاک</span>
            </a>
            <a href="<?= url('reports/daily.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'reports/daily') !== false ? 'active' : '' ?>">
                <i class="bi bi-calendar-day"></i>
                <span>Daily Report</span>
                <span class="font-urdu nav-urdu">یومیہ رپورٹ</span>
            </a>
            <a href="<?= url('reports/customer.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'reports/customer') !== false ? 'active' : '' ?>">
                <i class="bi bi-person-lines-fill"></i>
                <span>Customer Report</span>
                <span class="font-urdu nav-urdu">گاہک رپورٹ</span>
            </a>
            
            <div class="nav-section-label">System</div>
            <a href="<?= url('settings/index.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'settings') !== false ? 'active' : '' ?>">
                <i class="bi bi-gear"></i>
                <span>Settings</span>
                <span class="font-urdu nav-urdu">سیٹنگز</span>
            </a>
            <a href="<?= url('sync-status.php') ?>" class="nav-item <?= strpos($_SERVER['PHP_SELF'], 'sync-status') !== false ? 'active' : '' ?>">
                <i class="bi bi-arrow-repeat"></i>
                <span>Sync Status</span>
                <span class="font-urdu nav-urdu">ہم آہنگی</span>
            </a>
            <a href="<?= url('logout.php') ?>" class="nav-item">
                <i class="bi bi-box-arrow-left"></i>
                <span>Logout</span>
                <span class="font-urdu nav-urdu">لاگ آؤٹ</span>
            </a>
        </nav>
    </aside>

    <!-- ============================================================ -->
    <!-- Main Content -->
    <!-- ============================================================ -->
    <div class="main-wrapper">
        <header class="top-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobile-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="online-status">
                    <span class="online-dot" id="online-dot"></span>
                    <span id="online-text">Online</span>
                </div>
            </div>
            <div class="header-right">
                <span class="header-user">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($currentUser['name'] ?? 'User') ?>
                </span>
                <a href="<?= url('module_select.php') ?>" class="btn btn-sm btn-outline" style="font-size:0.75rem;padding:5px 10px;color:var(--gold-primary);border-color:var(--gold-muted);">
                    <i class="bi bi-arrow-left-right"></i> Switch Module
                </a>
            </div>
        </header>

        <!-- Offline Bar -->
        <div class="offline-bar" id="offline-bar" style="top: -60px;">
            <div class="offline-content">
                <i class="bi bi-wifi-off"></i>
                <span id="offline-status-text">You are offline</span>
            </div>
            <div class="sync-progress" id="sync-progress" style="display: none;">
                <div class="sync-progress-bar" id="sync-progress-bar"></div>
            </div>
        </div>

        <main class="content-area">
            <!-- Flash Messages -->
            <?php if ($flash = getFlash()): ?>
                <div class="card flash-<?= $flash['type'] ?>" style="border-left: 4px solid <?= $flash['type'] === 'success' ? 'var(--success)' : 'var(--error)' ?>; padding: 16px 22px; margin-bottom: 24px;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>" style="color: <?= $flash['type'] === 'success' ? 'var(--success)' : 'var(--error)' ?>; font-size: 1.3rem;"></i>
                        <span style="font-weight: 500;"><?= htmlspecialchars($flash['message']) ?></span>
                    </div>
                </div>
            <?php endif; ?>
    <?php endif; ?>
<?php
function renderPagination($paginator, $baseUrl) {
    if ($paginator['lastPage'] <= 1) return '';
    $html = '<div class="pagination"><nav><ul class="pagination-list">';
    $prevDisabled = $paginator['page'] <= 1 ? ' disabled' : '';
    $prevUrl = $baseUrl . (strpos($baseUrl, '?') === false ? '?' : '&') . 'page=' . ($paginator['page'] - 1);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">&laquo;</a></li>';
    for ($i = 1; $i <= $paginator['lastPage']; $i++) {
        $active = $i === $paginator['page'] ? ' active' : '';
        $pageUrl = $baseUrl . (strpos($baseUrl, '?') === false ? '?' : '&') . 'page=' . $i;
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
    }
    $nextDisabled = $paginator['page'] >= $paginator['lastPage'] ? ' disabled' : '';
    $nextUrl = $baseUrl . (strpos($baseUrl, '?') === false ? '?' : '&') . 'page=' . ($paginator['page'] + 1);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">&raquo;</a></li>';
    $html .= '</ul></nav></div>';
    return $html;
}
?>