<?php
/**
 * CSNK Admin System
 * Secure Logout Handler
 * - Works for manual logout
 * - Used by auto-logout (idle timeout)
header('Location: login.php');
exit;