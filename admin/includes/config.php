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