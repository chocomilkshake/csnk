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
require_once __DIR__ . '/../includes/Aut{
    session_unset();
header('Location: login.php');
exit;