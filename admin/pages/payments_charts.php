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

if (strtolower((string) ($_SESSION['admin_role'] ?? $_SESSION['role'] ?? 'employee')) === 'employee') {
    http_response_code(403);
    echo json_encode([
        'error' => 'Forbidden'
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
$stmt = $conn->prepare($applicantsSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$applicantsRow = $stmt->get_result()->fetch_assoc();
$applicantsBilled = (int)($applicantsRow['applicants_billed'] ?? 0);

// 2. AVG INVOICE VALUE
$avgInvoiceSql = "SELECT AVG(total_amount) AS avg_invoice_value FROM invoice_history WHERE company_type = ?";
$stmt = $conn->prepare($avgInvoiceSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$avgRow = $stmt->get_result()->fetch_assoc();
$avgInvoiceValue = round((float)($avgRow['avg_invoice_value'] ?? 0), 2);

// 3. CONVERSION RATE %
$conversionSql = "
    SELECT 
        COUNT(*) AS total_invoices,
        SUM(payment_status = 'Paid') AS paid_count
    FROM invoice_history 
    WHERE company_type = ?
";
$stmt = $conn->prepare($conversionSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$convRow = $stmt->get_result()->fetch_assoc();
$totalInvoices = (int)($convRow['total_invoices'] ?? 0);
$paidCount = (int)($convRow['paid_count'] ?? 0);
$conversionRate = $totalInvoices > 0 ? round(($paidCount / $totalInvoices) * 100, 1) : 0;

// 4. AVG DAYS TO PAY
$daysToPaySql = "
    SELECT AVG(DATEDIFF(paid_at, created_at)) AS avg_days_to_pay
    FROM invoice_history 
    WHERE payment_status = 'Paid' 
      AND paid_at IS NOT NULL AND created_at IS NOT NULL
      AND company_type = ?
";
$stmt = $conn->prepare($daysToPaySql);
$stmt->bind_param('s', $company);
$stmt->execute();
$daysRow = $stmt->get_result()->fetch_assoc();
$avgDaysToPay = $daysRow['avg_days_to_pay'] !== null ? round((float)$daysRow['avg_days_to_pay'], 1) : 0;

// 5. TOP 5 CLIENTS DISTRIBUTION
$topClientsSql = "
    SELECT 
        client_name,
        SUM(total_amount) AS revenue,
        COUNT(*) AS invoice_count,
        ROUND((SUM(total_amount) / ?) * 100, 1) AS revenue_pct
    FROM invoice_history 
    WHERE company_type = ?
    GROUP BY client_name 
    ORDER BY revenue DESC 
    LIMIT 5
";
$totalRevenue = $gross; // For pct calculation
$stmt = $conn->prepare($topClientsSql);
$stmt->bind_param('ds', $totalRevenue, $company);
$stmt->execute();
$topClients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// 6. INVOICE TIMELINE (Stacked: Paid/Pending by date)
$timelineSql = "
    SELECT 
        DATE(invoice_date) AS date,
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
    SELECT
        DATE(paid_at) AS date,
        SUM(total_amount) AS amount
    FROM invoice_history
    WHERE payment_status = 'Paid'
      AND paid_at IS NOT NULL
      AND company_type = ?
    GROUP BY DATE(paid_at)
    ORDER BY DATE(paid_at) ASC
";
$stmt = $conn->prepare($trendSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$trendRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// PAYMENT METHOD BREAKDOWN
$methodSql = "
    SELECT
        payment_provider,
        SUM(total_amount) AS amount
    FROM invoice_history
    WHERE payment_status = 'Paid'
      AND company_type = ?
    GROUP BY payment_provider
";
$stmt = $conn->prepare($methodSql);
$stmt->bind_param('s', $company);
$stmt->execute();
$methodRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ================= ENHANCED JSON RESPONSE =================
echo json_encode([
    'summary' => [
        'gross' => $gross,
        'net' => $net,
        'revenue' => $revenue,
        'pending' => $pending,
    ],
    'kpis' => [
        'applicants_billed' => $applicantsBilled,
        'avg_invoice_value' => $avgInvoiceValue,
        'conversion_rate' => $conversionRate,
        'avg_days_to_pay' => $avgDaysToPay,
    ],
    'status' => [
        'paid' => (int)($statusRow['paid_count'] ?? 0),
        'pending' => (int)($statusRow['pending_count'] ?? 0),
    ],
    'trend' => $trendRows,
    'methods' => $methodRows,
    'timeline' => $timelineRows,
    'top_clients' => $topClients,
], JSON_NUMERIC_CHECK);

exit;
?>
