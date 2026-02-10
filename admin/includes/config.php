<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'csnk');

// Application Configuration
define('APP_NAME', 'CSNK Admin System');
define('APP_URL', 'http://localhost/csnk/admin');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Session Configuration
// Only adjust session ini and start a session when there isn't already an active session.
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
    session_start();
} else {
    // Session already active — avoid changing session ini settings or calling session_start() again.
}

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
