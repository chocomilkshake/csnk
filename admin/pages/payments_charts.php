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
    exit;
}

// ================= GET COMPANY FILTER =================
$company = $_GET['company'] ?? 'CSNK';
if (!in_array($company, ['CSNK', 'SMC'])) {
    $company = 'CSNK';
}

// ================= SUMMARY: GROSS / NET / REVENUE =================
$summarySql = "
    SELECT
        COALESCE(SUM(total_amount), 0) AS gross_amount,
        COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END), 0) AS net_amount,
        COUNT(*) AS total_invoices
    FROM invoice_history
    WHERE company_type = ?
";

$stmt = $conn->prepare($summarySql);
if (!$stmt) {
    echo json_encode(['error' => 'Summary prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param('s', $company);
$stmt->execute();
$summaryRow = $stmt->get_result()->fetch_assoc();

$gross = (float)($summaryRow['gross_amount'] ?? 0);
$net = (float)($summaryRow['net_amount'] ?? 0);
$pending = $gross - $net;
$revenue = $net;

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
      AND company_type = ?
";
$stmt = $conn->prepare($daysToPaySql);
        SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END) AS paid_amount,
        SUM(CASE WHEN payment_status != 'Paid' THEN total_amount ELSE 0 END) AS pending_amount
    FROM invoice_history 
    WHERE invoice_date IS NOT NULL AND company_type = ?
    GROUP BY DATE(invoice_date)
    ORDER BY date ASC
";
$stmt = $conn->prepare($timelineSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$timelineRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ================= EXISTING CHARTS (unchanged) =================

// PAID VS PENDING COUNT
$statusSql = "
    SELECT
        SUM(payment_status = 'Paid') AS paid_count,
        SUM(payment_status != 'Paid') AS pending_count
    FROM invoice_history
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