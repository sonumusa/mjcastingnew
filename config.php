<?php
// ============================================================
// MJ Casting - Configuration
// ============================================================

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mj_casting_wax');
define('DB_USER', 'root');
define('DB_PASS', '');

// Site configuration
define('SITE_NAME', 'M.J Casting');
define('SITE_NAME_URDU', 'ایم جے کاسٹنگ');
define('TIMEZONE', 'Asia/Karachi');

date_default_timezone_set(TIMEZONE);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Module detection
function getModuleName(): string {
    // Check if module is stored in session
    if (isset($_SESSION['module'])) {
        $module = $_SESSION['module'];
        // If current URL contains /wax/, force wax module
        if (strpos($_SERVER['PHP_SELF'] ?? '', '/wax/') !== false) {
            $_SESSION['module'] = 'wax';
            return 'wax';
        }
        // If current URL is gold casting pages, force casting module
        if (strpos($_SERVER['PHP_SELF'] ?? '', 'invoices') !== false || 
            strpos($_SERVER['PHP_SELF'] ?? '', 'gold-receipts') !== false ||
            strpos($_SERVER['PHP_SELF'] ?? '', 'ledger') !== false ||
            strpos($_SERVER['PHP_SELF'] ?? '', 'inventory') !== false ||
            strpos($_SERVER['PHP_SELF'] ?? '', 'customers') !== false ||
            strpos($_SERVER['PHP_SELF'] ?? '', 'reports') !== false ||
            strpos($_SERVER['PHP_SELF'] ?? '', 'settings') !== false ||
            strpos($_SERVER['PHP_SELF'] ?? '', 'dashboard') !== false) {
            $_SESSION['module'] = 'casting';
            return 'casting';
        }
        return $module;
    }
    // Default to casting if not set
    return 'casting';
}

function requireModule(string $required): void {
    $current = getModuleName();
    if ($current !== $required) {
        if ($required === 'wax') {
            redirect('wax/index.php');
        } else {
            redirect('dashboard.php');
        }
    }
}

// ============================================================
// Database Connection
// ============================================================
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}

// ============================================================
// Authentication
// ============================================================
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . 'login.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = getDB()->prepare("SELECT id, name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// ============================================================
// URL Helpers
// ============================================================
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptPath = $_SERVER['SCRIPT_NAME'];
    
    // Find where 'mjcasting' is in the path
    $pos = strpos($scriptPath, '/mjcasting/');
    
    if ($pos !== false) {
        // Extract everything up to and including /mjcasting/
        $basePath = substr($scriptPath, 0, $pos + strlen('/mjcasting/'));
    } else {
        // Fallback for root level
        $basePath = rtrim(dirname($scriptPath), '/\\') . '/';
    }
    
    return $protocol . $host . $basePath;
}

function url(string $path = ''): string {
    return getBaseUrl() . ltrim($path, '/');
}

function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

function back(): void {
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('dashboard.php')));
    exit;
}

// ============================================================
// Flash Messages
// ============================================================
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ============================================================
// Request Helpers
// ============================================================
function getInput(string $key, $default = null) {
    return $_REQUEST[$key] ?? $default;
}

function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function query(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

// ============================================================
// Decimal Parsing
// ============================================================
function parseDecimal($value): float {
    if (is_null($value) || $value === '') return 0;
    return (float) $value;
}

// ============================================================
// Number Formatting
// ============================================================
function formatWeight($value): string {
    return number_format((float) $value, 3, '.', ',') . ' g';
}

function formatAmount($value): string {
    return 'Rs. ' . number_format((float) $value, 2, '.', ',');
}

function formatDate(string $date, string $format = 'd/m/Y'): string {
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : $date;
}

function formatDateTime(string $datetime): string {
    $ts = strtotime($datetime);
    return $ts ? date('d M Y, h:i A', $ts) : $datetime;
}

// ============================================================
// CSRF Protection
// ============================================================
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="_token" value="' . generateCsrfToken() . '">';
}

function verifyCsrf(): bool {
    $token = post('_token');
    return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

function requireCsrf(): void {
    if (!verifyCsrf()) {
        setFlash('error', 'Invalid security token. Please try again.');
        back();
    }
}

// ============================================================
// Pagination Helper
// ============================================================
function paginate(PDOStatement $countStmt, string $baseSql, array $params, int $perPage = 25): array {
    $total = (int) $countStmt->fetchColumn();
    $page = max(1, (int) (query('page', 1)));
    $lastPage = max(1, ceil($total / $perPage));
    $page = min($page, $lastPage);
    $offset = ($page - 1) * $perPage;
    
    $sql = $baseSql . " LIMIT $perPage OFFSET $offset";
    $stmt = getDB()->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll();
    
    return [
        'items' => $items,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'lastPage' => $lastPage,
        'from' => $offset + 1,
        'to' => min($offset + $perPage, $total),
    ];
}

function paginationLinks(array $paginator, string $urlTemplate): string {
    if ($paginator['lastPage'] <= 1) return '';
    
    $html = '<div class="pagination"><nav><ul class="pagination-list">';
    
    // Previous
    $prevDisabled = $paginator['page'] <= 1 ? ' disabled' : '';
    $prevUrl = str_replace('__PAGE__', $paginator['page'] - 1, $urlTemplate);
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . $prevUrl . '">&laquo;</a></li>';
    
    for ($i = 1; $i <= $paginator['lastPage']; $i++) {
        $active = $i === $paginator['page'] ? ' active' : '';
        $pageUrl = str_replace('__PAGE__', $i, $urlTemplate);
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
    }
    
    // Next
    $nextDisabled = $paginator['page'] >= $paginator['lastPage'] ? ' disabled' : '';
    $nextUrl = str_replace('__PAGE__', $paginator['page'] + 1, $urlTemplate);
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . $nextUrl . '">&raquo;</a></li>';
    
    $html .= '</ul></nav></div>';
    return $html;
}

// ============================================================
// Setting helpers
// ============================================================
function getSetting(string $key, $default = null) {
    $db = getDB();
    $stmt = $db->prepare("SELECT setting_value, setting_type FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    
    if (!$row) return $default;
    
    return match ($row['setting_type']) {
        'number' => (float) $row['setting_value'],
        'boolean' => $row['setting_value'] === '1' || $row['setting_value'] === true,
        'json' => json_decode($row['setting_value'], true),
        default => $row['setting_value'],
    };
}

function setSetting(string $key, $value, string $type = 'text'): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?, setting_type = ?");
    $stmt->execute([$key, $value, $type, $value, $type]);
}

// ============================================================
// Build query string preserving existing params
// ============================================================
function preserveQueryString(array $extra = []): string {
    $params = $_GET;
    foreach ($extra as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return http_build_query($params);
}