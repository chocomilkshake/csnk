<?php
/**
 * CSNK Admin System
 * Secure Logout Handler
 * - Works for manual logout
 * - Used by auto-logout (idle timeout)
 * - Centralized via Auth class
 */

// Load core files
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/Auth.php';

// Initialize services
$database = new Database();
$auth = new Auth($database);

// Perform logout (destroys session safely)
$auth->logout();

// Extra safety (in case logout() does not fully clear session)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_unset();
    session_destroy();
}

// Redirect to login page
header('Location: login.php');
exit;