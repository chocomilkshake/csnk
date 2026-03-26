<?php
/**
 * payments_charts.php - ENHANCED
 * -----------------------------
 * Returns JSON data for Payments & Recruitment Agency Analytics
 * Used by payments_clients.php - Recruitment Agency Dashboard
 */

session_start();
header('Content-Type: application/json');

// ================= SECURITY =================
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'error' => 'Unauthorized access'
    ]);
    exit;
}

// ================= INCLUDES =================
require_once '../includes/config.php';
require_once '../includes/Database.php';

    'kpis' => [
        'applicants_billed' => $applicantsBilled,
        'avg_invoice_value' => $avgInvoiceValue,
        'conversion_rate' => $conversionRate,