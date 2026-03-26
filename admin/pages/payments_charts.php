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

// ================= DB CONNECTION =================
$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    echo json_encode([
        'error' => 'Database connection failed'
    ]);
// ================= NEW RECRUITMENT AGENCY ANALYTICS =================

// 1. APPLICANTS BILLED (parse JSON_LENGTH)
$applicantsSql = "
    SELECT 
        COALESCE(SUM(JSON_LENGTH(applicants_data)), 0) AS applicants_billed
    FROM invoice_history 
    WHERE company_type = ?
";
// 4. AVG DAYS TO PAY
$daysToPaySql = "
    SELECT AVG(DATEDIFF(paid_at, created_at)) AS avg_days_to_pay
    FROM invoice_history 
    WHERE payment_status = 'Paid' 
      AND paid_at IS NOT NULL AND created_at IS NOT NULL
        SUM(payment_status = 'Paid') AS paid_count
    WHERE company_type = ?
";
$stmt = $conn->prepare($statusSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$statusRow = $stmt->get_result()->fetch_assoc();

// REVENUE TREND (PAID OVER TIME)
$trendSql = "
    ],
    'kpis' => [
        'applicants_billed' => $applicantsBilled,
        'avg_invoice_value' => $avgInvoiceValue,
        'conversion_rate' => $conversionRate,