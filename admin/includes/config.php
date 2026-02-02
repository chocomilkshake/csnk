<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'CSNK');

// Application Configuration
define('APP_NAME', 'CSNK Admin System');
define('APP_URL', 'http://localhost/csnk-server');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
session_start();

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
