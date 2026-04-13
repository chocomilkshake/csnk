<?php
/* ======================================================
   DATABASE CONFIGURATION
====================================================== */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'csnk');


/* ======================================================
   APPLICATION CONFIGURATION
====================================================== */
define('APP_NAME', 'CSNK Admin System');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

function detectAdminBasePath(): string
{
    $scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptName !== '') {
        $adminPos = stripos($scriptName, '/admin');
        if ($adminPos !== false) {
            return rtrim(substr($scriptName, 0, $adminPos + strlen('/admin')), '/');
        }
    }
    return '/admin';
}


define('APP_URL', $scheme . '://' . $host . detectAdminBasePath());


/* ======================================================
   UPLOAD PATHS
====================================================== */
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');


define('REPLACEMENTS_UPLOAD_SUBDIR', 'replacements');
define('REPLACEMENTS_UPLOAD_PATH', UPLOAD_PATH . REPLACEMENTS_UPLOAD_SUBDIR . '/');
define('REPLACEMENTS_UPLOAD_URL', UPLOAD_URL . REPLACEMENTS_UPLOAD_SUBDIR . '/');

if (!is_dir(REPLACEMENTS_UPLOAD_PATH)) {
    @mkdir(REPLACEMENTS_UPLOAD_PATH, 0755, true);
}


/* ======================================================
   SESSION CONFIGURATION
====================================================== */
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 1);

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}


/* ======================================================
   ✅ SESSION IDLE TIMEOUT (AUTO LOGOUT AFTER 5 MINUTES)
====================================================== */

// 5 minutes = 300 seconds
define('SESSION_IDLE_TIMEOUT', 300);

// Skip timeout check on login & logout pages
$currentScript = basename($_SERVER['PHP_SELF'] ?? '');

if (!in_array($currentScript, ['login.php', 'logout.php'], true)) {

    // Check last activity
    if (isset($_SESSION['last_activity'])) {
        if ((time() - $_SESSION['last_activity']) >= SESSION_IDLE_TIMEOUT) {

            // Destroy session securely
            session_unset();
            session_destroy();

            header('Location: /csnk/admin/pages/login.php?reason=timeout');
            exit;
        }
    }

    // Update last activity timestamp
    $_SESSION['last_activity'] = time();
}


/* ======================================================
   TIMEZONE
====================================================== */
date_default_timezone_set('Asia/Manila');


/* ======================================================
   ERROR REPORTING (TURN OFF IN PRODUCTION)
====================================================== */
error_reporting(E_ALL);
ini_set('display_errors', 0);


/* ======================================================
   ✅ SMTP CONFIGURATION — Z.COM (FINAL & CORRECT)
====================================================== */

define('SMTP_HOST', 'mail.crempcophilippines.com');
define('SMTP_PORT', 465);
define('SMTP_SECURE', 'ssl');

define('SMTP_USER', 'billing@crempcophilippines.com');
define('SMTP_PASS', ']I85gcDgU$}DSRsC'); // ✅ cPanel email password

define('SMTP_FROM_EMAIL', 'billing@crempcophilippines.com');
define('SMTP_FROM_NAME', 'CSNK Agency Billing');

define('SMTP_TIMEOUT', 30);
define('SMTP_KEEPALIVE', false);
define('SMTP_AUTO_TLS', false);


/* ======================================================
   ✅ SMC SMTP (SAME SERVER — SAFE)
====================================================== */
define('SMC_SMTP_HOST', 'mail.crempcophilippines.com');
define('SMC_SMTP_PORT', 465);
define('SMC_SMTP_SECURE', 'ssl');

define('SMC_SMTP_USER', 'billing@crempcophilippines.com');
define('SMC_SMTP_PASS', ']I85gcDgU$}DSRsC');

define('SMC_FROM_EMAIL', 'billing@crempcophilippines.com');
define('SMC_FROM_NAME', 'SMC Agency Billing');


/* ======================================================
   XENDIT PAYMENT GATEWAY CONFIGURATION
====================================================== */

// ✅ Environment: 'sandbox' or 'production'
define('XENDIT_ENV', 'sandbox');

// ✅ Xendit Secret API Key (SERVER SIDE ONLY)
define('XENDIT_SECRET_KEY', 'xnd_development_V9rC6qIrgqsJUET0lCCYR81I9K7x2K7XqWOBTNbfGbzkP33ppxDLlHARZI');

// ✅ Xendit Webhook Verification Token
define('XENDIT_WEBHOOK_TOKEN', '6XaV9HehrczUDRW6kG1hGgZ8DQnSv4vLlhod6qXyhswU1Hla');

// ✅ Xendit API Base URL
define(
    'XENDIT_API_URL',
    XENDIT_ENV === 'production'
        ? 'https://api.xendit.co'
        : 'https://api.xendit.co'
);