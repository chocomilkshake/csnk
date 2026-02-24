<?php
// ========================================
// App & Environment Configuration
// ========================================

// Database Configuration
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'csnk');
define('DB_PORT', 3306); // change if needed

// ========================================
// MySQLi Connection (robust w/ diagnostics)
// ========================================
mysqli_report(MYSQLI_REPORT_OFF); // prevent warnings from throwing before we format our own messages

function csnk_connect(): mysqli
{
    $hosts = ['127.0.0.1', 'localhost'];    // try both
    $ports = [DB_PORT, 3306, 3307];         // try common ports (ordered: your constant first)
    $errors = [];

    foreach ($hosts as $h) {
        foreach ($ports as $p) {
            $link = @mysqli_init();
            if (!$link) {
                $errors[] = "mysqli_init failed";
                continue;
            }

            // Optional: set a very short timeout to fail fast (in seconds)
            @mysqli_options($link, MYSQLI_OPT_CONNECT_TIMEOUT, 3);

            $ok = @$link->real_connect($h, DB_USER, DB_PASS, DB_NAME, $p);
            if ($ok) {
                @mysqli_set_charset($link, 'utf8mb4');
                return $link;
            } else {
                $errors[] = "connect error host={$h} port={$p} : " . mysqli_connect_error();
                @mysqli_close($link);
            }
        }
    }

    http_response_code(500);
    // Helpful message for dev (hide in production)
    die(
        "Database connection error.\n\n" .
        "Tried hosts: " . implode(', ', $hosts) . "\n" .
        "Tried ports: " . implode(', ', $ports) . "\n\n" .
        "Errors:\n- " . implode("\n- ", $errors) . "\n\n" .
        "Tips:\n" .
        "• Start MySQL in XAMPP Control Panel\n" .
        "• Verify port in my.ini ([mysqld] port=...)\n" .
        "• If you set a root password, update DB_PASS in config.php\n"
    );
}

$mysqli = csnk_connect();

// Application Configuration
define('APP_NAME', 'CSNK Admin System');
define('APP_URL', 'http://localhost/csnk/admin');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Replacement uploads (new)
define('REPLACEMENTS_UPLOAD_SUBDIR', 'replacements');
define('REPLACEMENTS_UPLOAD_PATH', UPLOAD_PATH . REPLACEMENTS_UPLOAD_SUBDIR . '/');
define('REPLACEMENTS_UPLOAD_URL', UPLOAD_URL . REPLACEMENTS_UPLOAD_SUBDIR . '/');

// Ensure replacements folder exists
if (!is_dir(REPLACEMENTS_UPLOAD_PATH)) {
    @mkdir(REPLACEMENTS_UPLOAD_PATH, 0755, true);
}

// Session Configuration
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_secure', '0'); // set '1' if using HTTPS

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',      // set if you use a specific domain
        'secure' => false,   // true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Lax',   // 'Strict' if you can, 'None' requires Secure
    ]);

    session_start();
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ========================================
// MySQLi Connection (reusable)
// ========================================
$mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if (!$mysqli) {
    http_response_code(500);
    die('Database connection error: ' . mysqli_connect_error());
}
mysqli_set_charset($mysqli, 'utf8mb4');

// ========================================
// Business Unit (BU) Helpers
// ========================================

/**
 * Ensure a current BU is present in session and return it as int.
 * Call at the top of admin/public pages that should be scoped by BU.
 */
function require_bu_context(): int
{
    if (empty($_SESSION['current_bu_id'])) {
        http_response_code(403);
        die('No business unit in session. Please login again.');
    }
    return (int) $_SESSION['current_bu_id'];
}

/**
 * Returns a WHERE clause snippet for tables that have an unaliased `business_unit_id` column.
 * Usage:
 *   $sql = "SELECT * FROM applicants WHERE " . bu_where() . " AND status = ?";
 */
function bu_where(): string
{
    return ' business_unit_id = ? ';
}

/**
 * Returns a WHERE clause snippet for alias/qualified column (e.g., 'a.business_unit_id').
 * Usage:
 *   $sql = "SELECT * FROM applicants a WHERE " . bu_where_alias('a.business_unit_id');
 */
function bu_where_alias(string $aliasColumn): string
{
    // minimal validation to avoid injection in column token
    if (!preg_match('/^[A-Za-z0-9_.]+$/', $aliasColumn)) {
        throw new Exception('Invalid aliasColumn for BU filter.');
    }
    return " {$aliasColumn} = ? ";
}

/**
 * Bind BU as first param + your other params to a prepared statement.
 * Example:
 *   $stmt = mysqli_prepare($mysqli, "SELECT * FROM applicants WHERE " . bu_where() . " AND status=?");
 *   bind_bu_and_params($stmt, $currentBuId, 's', [$status]);
 */
function bind_bu_and_params(mysqli_stmt $stmt, int $currentBuId, string $types = '', array $params = []): void
{
    $typesAll = 'i' . $types; // BU is int param first
    // Build references for call_user_func_array:
    $refs = [];
    $refs[] = &$typesAll;
    $bu = $currentBuId;
    $refs[] = &$bu;
    foreach ($params as $k => $v) {
        $refs[] = &$params[$k];
    }
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $refs));
}

/**
 * Optional: resolve and set BU for the public site by host (or path).
 * Call once at the start of the public bootstrap (NOT on admin pages).
 */
function set_public_bu_from_host(mysqli $mysqli): void
{
    // Map host → BU code. Adjust to your actual domains/subdomains.
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $buCode = 'CSNK-PH';          // default to PH
    if (stripos($host, 'tr.') === 0) {
        $buCode = 'SMC-TR';
    }

    $sql = "SELECT id FROM business_units WHERE code = ?";
    $stmt = mysqli_prepare($mysqli, $sql);
    mysqli_stmt_bind_param($stmt, 's', $buCode);
    mysqli_stmt_execute($stmt);
    $row = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if (!$row) {
        http_response_code(500);
        die('Business Unit not found for host: ' . htmlspecialchars($host));
    }
    $_SESSION['current_bu_id'] = (int) $row['id'];
}