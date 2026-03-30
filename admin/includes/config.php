<?php
/* ======================================================
   DATABASE CONFIGURATION
====================================================== */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');=== */
define('APP_NAME', 'CSNK Admin System');

// Auto-detect protocol (http / https)
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['H
// Upload paths
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', APP_URL . '/uploads/');

// Replacement uploads

/* ======================================================
   TIMEZONE
====================================================== */