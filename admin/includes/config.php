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

// Auto-detect protocol (http / https)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

define('APP_URL', $scheme . '://' . $host . '/csnk/admin');

// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Replacement uploads
define('REPLACEMENTS_UPLOAD_SUBDIR', 'replacements');
define('REPLACEMENTS_UPLOAD_PATH', UPLOAD_PATH . REPLACEMENTS_UPLOAD_SUBDIR . '/');
define('REPLACEMENTS_UPLOAD_URL', UPLOAD_URL . REPLACEMENTS_UPLOAD_SUBDIR . '/');

// Ensure replacements directory exists
if (!is_dir(REPLACEMENTS_UPLOAD_PATH)) {
    @mkdir(REPLACEMENTS_UPLOAD_PATH, 0755, true);
}


/* ======================================================
   SESSION CONFIGURATION (SECURE)
====================================================== */
if (session_status() !== PHP_SESSION_ACTIVE) {

    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // set to 1 when HTTPS is enabled

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false, // true in production HTTPS
        'httponly' => true,
        'samesite' => 'Lax', // use 'Strict' if possible
    ]);

    session_start();
}


/* ======================================================
   TIMEZONE
====================================================== */
date_default_timezone_set('Asia/Manila');


/* ======================================================
   ERROR REPORTING (DEV ONLY)
====================================================== */
error_reporting(E_ALL);
ini_set('display_errors', 1); // ❗ set to 0 in production


/* ======================================================
   SMTP / EMAIL CONFIGURATION
====================================================== */
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'csnkmanila@gmail.com');        // ✅ CHANGE IF NEEDED
define('SMTP_PASS', 'svmw uiwi vjvt hteu');         // ✅ Gmail App Password
define('SMTP_FROM_EMAIL', 'csnkmanila@gmail.com');
define('SMTP_FROM_NAME', 'CSNK Billing');


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