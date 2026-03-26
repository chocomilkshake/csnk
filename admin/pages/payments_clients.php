<?php
session_start();
require_once '../includes/invoice_mailer.php';


/* ======================================================
   AJAX: RESEND INVOICE EMAIL
====================================================== */
if (isset($_GET['resend_invoice_email']) && isset($_GET['id'])) {
    header('Content-Type: application/json');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';
    require_once '../includes/functions.php';

    // ✅ load PHPMailer
    $composerAutoload = __DIR__ . '/../../vendor/autoload.php';
    if (is_readable($composerAutoload)) {
        require_once $composerAutoload;
    } else {
        require_once __DIR__ . '/../../lib/phpmailer/src/Exception.php';
        require_once __DIR__ . '/../../lib/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/../../lib/phpmailer/src/SMTP.php';
    }

    require_once __DIR__ . '/payment_invoice_gen.php';
    // ✅ this allows reuse of sendInvoiceEmail()

    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'DB connection failed']);
        exit;
    }

    $invoice_id = (int) $_GET['id'];

    // ✅ Fetch invoice
    $stmt = $conn->prepare("
        SELECT
            invoice_num,
            client_name,
            client_email,
            pdf_filename,
            company_type,
            payment_status,
            payment_link
        FROM invoice_history
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();

    if ($inv['payment_status'] === 'Paid') {
        echo json_encode([
            'success' => false,
            'message' => 'Invoice already paid. Resending is disabled.'
        ]);
        exit;
    }

    if (!$inv || empty($inv['client_email'])) {
        echo json_encode(['success' => false, 'message' => 'Invoice not found or email missing']);
        exit;
    }

    $pdfPath = $_SERVER['DOCUMENT_ROOT'] . '/csnk/uploads/invoices/' . $inv['pdf_filename'];

    if (!file_exists($pdfPath)) {
        echo json_encode(['success' => false, 'message' => 'Invoice PDF file not found']);
        exit;
    }

    // ✅ Send email
    $sent = sendInvoiceEmail(
        $inv['client_email'],
        $inv['client_name'],
        $inv['invoice_num'],
        $pdfPath,
        $inv['company_type'],
        $inv['payment_link'] // ✅ SAME XENDIT LINK
    );

    if ($sent) {
        echo json_encode(['success' => true, 'message' => 'Invoice email sent successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to send invoice email']);
    }

    exit;
}

/* ======================================================
   AJAX: PDF Analytics Export
====================================================== */
if (isset($_GET['export_analytics_pdf']) && isset($_POST['chartImages'])) {
    header('Content-Type: application/json');

    // Parse POST data
    $chartImages = json_decode($_POST['chartImages'], true);
    $tab = $_GET['tab'] ?? 'CSNK';
    $companyType = $tab;

    // Safe chart data validation - handle JSON decode failures
    $rawData = $_POST['chartImages'] ?? '{}';
    $chartImages = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($chartImages)) {
        echo json_encode(['success' => false, 'message' => 'Invalid chart data']);
        exit;
    }

    require_once '../includes/config.php';
    require_once '../includes/Database.php';
    require_once '../../lib/invlib/invoicr.php';

    $db = new Database();
    $conn = $db->getConnection();

    // 1. Get analytics data
    // Skip chart data fetch - use empty defaults to avoid file_get_contents failure
    $chartData = [
        'summary' => ['gross' => 0, 'net' => 0, 'pending' => 0],
        'kpis' => ['applicants_billed' => 0]
    ];

    // 2. Get top clients table data (reuse existing query logic)
    $sql = "
        SELECT
            client_booking_id,
            client_name,
            COUNT(*) AS total_invoices,
            SUM(total_amount) AS total_amount,
            SUM(payment_status = 'Paid') AS paid_count,
            SUM(payment_status != 'Paid') AS unpaid_count
        FROM invoice_history
        WHERE company_type = ?
        GROUP BY client_booking_id
        ORDER BY total_amount DESC
        LIMIT 10
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $companyType);
    $stmt->execute();
    $clients = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. Set company assets
    $company = $companyType === 'CSNK' ? 
        ['../resources/img/whychoose.png', 'CSNK Agency', '../resources/img/csnk-iconz.png'] :
        ['../resources/img/smcbrandname.png', 'SMC Agency', '../resources/img/smc.png'];

    // 4. Create temporary directory & save chart images
    $tempDir = sys_get_temp_dir() . '/html2canvas_' . uniqid();
    if (!mkdir($tempDir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create temp directory']);
        exit;
    }

    $chartFiles = [];
    foreach (['status', 'methods', 'trend', 'timeline', 'clients'] as $chartKey) {
        if (isset($chartImages[$chartKey])) {
            $dataUrl = $chartImages[$chartKey];
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl));
            $fileName = $tempDir . '/' . $chartKey . '.png';
            file_put_contents($fileName, $data);
            $chartFiles[$chartKey] = $fileName;
        }
    }

    // 5. Generate HTML with Invoicr
    $invoicr = new Invoicr();
    $invoicr->template('analytics');
    
    $invoicr->set('company', $company);
    $invoicr->set('summary', $chartData['summary'] ?? []);
    $invoicr->set('kpis', $chartData['kpis'] ?? []);
    $invoicr->set('chart_status', $chartFiles['status'] ?? '');
    $invoicr->set('chart_methods', $chartFiles['methods'] ?? '');
    $invoicr->set('chart_trend', $chartFiles['trend'] ?? '');
    $invoicr->set('chart_timeline', $chartFiles['timeline'] ?? '');
    $invoicr->set('chart_clients', $chartFiles['clients'] ?? '');
    $invoicr->set('clients', $clients ?? []);

    // 6. Generate PDF
    $pdfFile = $tempDir . '/analytics_' . $companyType . '_' . date('Y-m-d_H-i-s') . '.pdf';
    $invoicr->outputPDF(3, $pdfFile);

    // 7. Cleanup temp files except PDF
    foreach ($chartFiles as $file) {
        if (file_exists($file)) unlink($file);
    }

    if (!file_exists($pdfFile)) {
        echo json_encode(['success' => false, 'message' => 'PDF generation failed']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'pdfUrl' => $pdfFile,
        'message' => 'PDF generated successfully'
    ]);

    exit;
}

/* ======================================================
   AJAX: Get Client Invoice History
====================================================== */
if (
    isset($_GET['get_client_history']) &&
    isset($_GET['booking_id']) &&
    isset($_GET['tab'])
) {
    header('Content-Type: application/json');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';

    $db = new Database();
    $conn = $db->getConnection();

    $booking_id = (int) $_GET['booking_id'];
    $tab = strtoupper(trim($_GET['tab']));
    $companyType = ($tab === 'SMC') ? 'SMC' : 'CSNK';

    // ✅ STEP 1: Check if there are ANY pending invoices
    $check = $conn->prepare("
        SELECT COUNT(*) AS pending_count
        FROM invoice_history
        WHERE client_booking_id = ?
          AND company_type = ?
          AND payment_status != 'Paid'
    ");
    $check->bind_param('is', $booking_id, $companyType);
    $check->execute();
    $pendingCount = (int) $check->get_result()
        ->fetch_assoc()['pending_count'];

    // ✅ STEP 2: Build query dynamically
    if ($pendingCount > 0) {
        // There is at least one Pending invoice
        $sql = "
            SELECT
                id,
                invoice_num,
                invoice_date,
                due_date,
                total_amount,
                payment_status,
                reference_no,
                client_name,
                client_email,
                client_address,
                applicants_data,
                pdf_filename,
                created_at
            FROM invoice_history
            WHERE client_booking_id = ?
              AND company_type = ?
            ORDER BY
                CASE
                    WHEN payment_status = 'Paid' THEN 1
                    ELSE 0
                END ASC,
                created_at DESC
        ";
    } else {
        $sql = "
            SELECT
                id,
                invoice_num,
                invoice_date,
                due_date,
                total_amount,
                payment_status,
                reference_no,
                client_name,
                client_email,
                client_address,
                applicants_data,
                pdf_filename,
                created_at
            FROM invoice_history
            WHERE client_booking_id = ?
              AND company_type = ?
            ORDER BY created_at DESC
        ";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $booking_id, $companyType);
    $stmt->execute();

    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

/* ======================================================
   DELETE INVOICE
====================================================== */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {

    require_once '../includes/config.php';
    require_once '../includes/Database.php';

    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        die('Database connection failed.');
    }

    $invoice_id = (int) $_GET['id'];

    // ✅ Get PDF filename first (to delete file)
    $stmt = $conn->prepare("SELECT pdf_filename FROM invoice_history WHERE id = ?");
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        $pdfPath = $_SERVER['DOCUMENT_ROOT'] . '/csnk/uploads/invoices/' . $row['pdf_filename'];

        // ✅ Delete PDF file if exists
        if (!empty($row['pdf_filename']) && file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        // ✅ Delete invoice record
        $del = $conn->prepare("DELETE FROM invoice_history WHERE id = ?");
        $del->bind_param('i', $invoice_id);
        $del->execute();
    }

    // ✅ Redirect back to invoice list
    header('Location: payments_clients.php');
    exit;
}

/* ======================================================
   AJAX: Get Invoice Applicants (MUST BE FIRST)
====================================================== */
if (isset($_GET['get_invoice_applicants']) && isset($_GET['id'])) {
    header('Content-Type: application/json');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';
    require_once '../includes/functions.php';

    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        echo json_encode([]);
        exit;
    }

    $invoice_id = (int) $_GET['id'];

    $stmt = $conn->prepare("
        SELECT applicants_data
        FROM invoice_history
        WHERE id = ?
    ");
    $stmt->bind_param('i', $invoice_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if (!$row) {
        echo json_encode([]);
        exit;
    }

    $decoded = json_decode($row['applicants_data'] ?? '[]', true);
    if (!is_array($decoded)) {
        echo json_encode([]);
        exit;
    }

    $enriched_apps = [];

    foreach ($decoded as $app) {
        $item = [
            'name' => $app['name'] ?? 'Unknown Applicant',
            'start_date' => $app['start_date'] ?? '',
            'end_date' => $app['end_date'] ?? '',
            'days' => isset($app['days']) ? (int) $app['days'] : 0,
            'amount' => isset($app['amount']) ? (float) $app['amount'] : 0
        ];


        // Optional: enrich name from applicants table
        if (!empty($app['applicant_id'])) {
            $app_stmt = $conn->prepare("
                SELECT first_name, middle_name, last_name, suffix
                FROM applicants
                WHERE id = ?
            ");
            $app_stmt->bind_param('i', $app['applicant_id']);
            $app_stmt->execute();
            $a = $app_stmt->get_result()->fetch_assoc();

            if ($a) {
                $item['name'] = getFullName(
                    $a['first_name'],
                    $a['middle_name'],
                    $a['last_name'],
                    $a['suffix']
                );
            }
        }

        $enriched_apps[] = $item;
    }

    echo json_encode($enriched_apps);
    exit;
}

/* ======================================================
   NORMAL PAGE LOAD CONTINUES BELOW
====================================================== */

$pageTitle = 'Clients Invoices';

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    die('Database connection failed.');
}


/* ================= TAB FILTER ================= */
$activeTab = 'CSNK'; // default

if (isset($_GET['tab']) && $_GET['tab'] === 'SMC') {
    $activeTab = 'SMC';
}

/* ================= SEARCH ================= */
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string) $_GET['q']);
    $_SESSION['invoices_q'] = $q;
} elseif (!empty($_SESSION['invoices_q'])) {
    $q = $_SESSION['invoices_q'];
}

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['invoices_q']);
    redirect('payments_clients.php');
    exit;
}

/* ================= FETCH INVOICES ================= */
$sql = "
    SELECT
        client_booking_id,
        client_name,
        client_email,
        client_address,
        MAX(created_at) AS last_invoice_date,
        COUNT(*) AS total_invoices,
        SUM(total_amount) AS total_amount,
        SUM(payment_status = 'Paid') AS paid_count,
        SUM(payment_status != 'Paid') AS unpaid_count
    FROM invoice_history
    WHERE client_name LIKE ?
      AND company_type = ?
    GROUP BY client_booking_id
    ORDER BY last_invoice_date DESC
";

$stmt = $conn->prepare($sql);

$likeClient = '%' . $q . '%';
$companyType = $activeTab; // CSNK or SMC

$stmt->bind_param('ss', $likeClient, $companyType);
$stmt->execute();

$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ================= HELPERS ================= */
function formatCurrency($amount)
{
    return '₱' . number_format((float) $amount, 2);
}

function renderAvatar($picture, $client_name)
{
    if ($picture) {
        return '<img src="' . htmlspecialchars(getFileUrl($picture)) . '"
                     class="rounded-circle shadow-sm"
                     width="48" height="48">';
    }

    return '<div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                style="width:48px;height:48px;background:#0d6efd;">
                ' . strtoupper($client_name[0]) . '
            </div>';
}

// Use existing getFullName from functions.php

?>
<?php include '../includes/header.php'; ?>

<style>
    :root {
        --primary: #2563eb;
        --primary-light: #3b82f6;
        --success: #059669;
        --warning: #d97706;
        --danger: #dc2626;
        --muted: #6b7280;
        --light-bg: #f8fafc;
        --border-light: #e2e8f0;
        --shadow-light: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        --shadow-hover: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .backdrop-blur-lg {
        backdrop-filter: blur(20px);
    }

    .shadow-xl {
        box-shadow: var(--shadow-xl);
    }

    .rounded-3 {
        border-radius: 1.5rem;
    }

    .bg-gradient-primary {
        background: var(--gradient-primary) !important;
    }

    .fs-1-5 {
        font-size: 1.75rem;
    }

    .delete-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        min-width: 300px;
        background: #fff;
        border-radius: 14px;
        box-shadow: var(--shadow-lg);
        z-index: 9999;
        animation: slideUp .3s ease
    }

    .delete-toast.hidden {
        display: none
    }

    .toast-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 18px
    }

    .toast-text strong {
        font-size: 14px
    }

    .toast-sub {
        font-size: 12px;
        color: var(--muted)
    }

    .toast-actions {
        display: flex;
        gap: 10px
    }

    .toast-undo,
    .toast-close {
        border: none;
        background: none;
        cursor: pointer
    }

    .toast-undo {
        color: var(--primary);
        font-weight: 700
    }

    .toast-close {
        font-size: 16px;
        color: #adb5bd
    }

    .search-container {
        width: 320px;
        max-width: 100%
    }

    .search-input {
        padding: 12px 16px 12px 45px;
        border: 2px solid #e9ecef;
        border-radius: 16px;
        font-size: 15px;
        background: rgba(255, 255, 255, .85);
        backdrop-filter: blur(16px);
        box-shadow: var(--shadow-sm);
        transition: .3s
    }

    .search-input:focus {
        border-color: var(--primary);
        box-shadow: 0 8px 32px rgba(13, 110, 253, .15)
    }

    .search-clear {
        display: none;
        border: none;
        background: none;
        color: var(--muted)
    }

    .invoice-tabs {
        display: flex;
        gap: 12px
    }

    .tab-btn {
        padding: 10px 26px;
        border-radius: 999px;
        font-weight: 700;
        text-decoration: none;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .08);
        transition: .25s
    }

    .tab-btn.inactive {
        opacity: .45;
        box-shadow: none
    }

    .tab-csnk {
        background: linear-gradient(135deg, #c62828, #e53935);
        color: #fff
    }

    .tab-smc {
        background: linear-gradient(135deg, #0b1c3d, #102a5e);
        color: #d4af37
    }

    /* ✅ DASHBOARD TABS - GLASSMORPHISM */
    .dashboard-tab-btn {
        background: rgba(255,255,255,0.9) !important;
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.2);
        border-bottom: 3px solid transparent !important;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dashboard-tab-btn:hover {
        background: rgba(255,255,255,1) !important;
        transform: translateY(-2px);
        box-shadow: 0 12px 35px rgba(0,0,0,0.15);
        border-color: rgba(37,99,235,0.3);
    }

    .dashboard-tab-btn.active {
        background: linear-gradient(135deg, #2563eb 0%, #3b82f6 100%) !important;
        color: white !important;
        border-bottom-color: #1d4ed8 !important;
        box-shadow: 0 12px 40px rgba(37,99,235,0.4);
        transform: translateY(-1px);
    }

    .dashboard-tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #1d4ed8, #60a5fa);
        box-shadow: 0 2px 10px rgba(29,78,216,0.4);
    }

    /* Tab content transitions */
    .tab-pane {
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        opacity: 1;
    }
    
    .tab-pane.tab-fade-out {
        opacity: 0 !important;
        transform: translateX(20px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .tab-pane.tab-fade-in {
        opacity: 0;
        transform: translateX(-20px);
        animation: fadeInSlide 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;
    }
    
    @keyframes fadeInSlide {
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .tab-content {
        animation: fadeInUp 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Mobile tab improvements */
    @media (max-width: 768px) {
        .dashboard-tab-btn {
            font-size: 0.95rem;
            padding: 12px 16px;
        }
        
        .dashboard-tab-btn span.d-none {
            display: none !important;
        }
    }

    .btn-group .btn {
        border-radius: 8px;
        padding: 6px 10px
    }

    .btn-outline-info:hover {
        background: #0dcaf0;
        color: #fff
    }

    .btn-outline-warning:hover {
        background: var(--warning);
        color: #000
    }

    .btn-outline-danger:hover {
        background: var(--danger);
        color: #fff
    }

    @keyframes slideUp {
        from {
            transform: translateY(20px);
            opacity: 0
        }

        to {
            transform: none;
            opacity: 1
        }
    }

    /* ===== CARD ===== */
    .client-table-card {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }

    /* ===== TABLE ===== */
    .client-table thead th {
        font-size: .7rem;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #64748b;
        border-bottom: 1px solid #e5e7eb;
        padding: 14px;
    }

    .client-table tbody td {
        padding: 16px 14px;
        vertical-align: middle;
        font-size: .9rem;
    }

    .client-table tbody tr {
        transition: background .15s ease;
    }

    .client-table tbody tr:hover {
        background: #f8fafc;
    }

    /* ===== COUNT CHIP ===== */
    .count-chip {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 600;
        border: 1px solid #2563eb;
        color: #2563eb;
    }

    .count-chip.muted {
        border-color: #cbd5e1;
        color: #64748b;
    }

    /* ===== HISTORY BUTTON ===== */
    .history-btn {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        font-size: .8rem;
        padding: 6px 14px;
        background: transparent;
    }

    .history-btn:hover {
        background: #f1f5f9;
    }

    /* ===== ACTION MODAL ===== */
    .action-modal {
        border-radius: 22px;
        border: none;
    }

    /* ===== View MODAL ===== */

    .modal-l {
        max-width: 800px;
        /* change this value */
    }

    #logo-csnk {
        height: 100px;
        /* make bigger */
    }

    #badge-csnk {
        height: 180px;
        /* make bigger */
    }

    #logo-smc {
        height: 100px;
        /* make bigger */
    }

    #badge-smc {
        height: 180px;
        /* make bigger */
    }

    .invoice-preview-paper {
        background: #fff;
        padding: 40px;
        font-family: Arial, Helvetica, sans-serif
    }

    .inv-header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #ddd;
        padding-bottom: 15px
    }

    .inv-title {
        text-align: center;
        font-size: 28px;
        margin: 25px 0;
        font-weight: 700
    }

    .inv-meta {
        display: flex;
        justify-content: space-between;
        margin-bottom: 20px
    }

    .inv-table {
        width: 100%;
        border-collapse: collapse
    }

    .inv-table th,
    .inv-table td {
        padding: 10px;
        border-bottom: 1px solid #eee
    }

    .inv-table th {
        background: #f5f5f5
    }

    .inv-declaration,
    .inv-payment {
        margin-top: 20px
    }
</style>
<!-- Monitoring Base (includes fonts) -->
<link rel="stylesheet" href="../css/monitoring-base.css">

<script>
    // Ensure monitoring-page class
    document.documentElement.className += ' monitoring-page';
</script>

<!-- Tailwind CDN (AFTER base to preserve fonts) -->
<script src="https://cdn.tailwindcss.com"></script>

<script>
    // Ensure monitoring-page class
    document.documentElement.className += ' monitoring-page';
</script>

<div class="container-fluid py-4">

    <!-- DELETE TOAST - MISSING HTML -->
    <div id="deleteToast" class="delete-toast hidden">
        <div class="toast-body">
            <div class="toast-text">
                <div class="fw-bold">Invoice moved to trash</div>
                <div class="toast-sub" id="toastSubtitle">This will be permanently deleted in</div>
            </div>
            <div class="toast-actions">
                <button id="undoDelete" class="toast-undo">Undo</button>
                <button id="closeToast" class="toast-close">&times;</button>
            </div>
            <div style="font-size:12px;color:var(--muted);margin-top:4px;">
                <span id="toastCountdown">7</span>s
            </div>
        </div>
    </div>

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Client Invoice</h1>
        <p class="text-gray-600">Manage all client invoices</p>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">


        <!-- LEFT: TAB BUTTONS -->
        <div class="invoice-tabs">
            <a href="payments_clients.php?tab=CSNK"
                class="tab-btn tab-csnk <?= $activeTab === 'CSNK' ? '' : 'inactive' ?>">
                CSNK
            </a>

            <a href="payments_clients.php?tab=SMC"
                class="tab-btn tab-smc <?= $activeTab === 'SMC' ? '' : 'inactive' ?>">
                SMC
            </a>
        </div>

        

<!-- RIGHT: SEARCH + ADD BUTTONS - VISIBLE ON CLIENTS TAB (CSNK/SMC) -->
        <div class="d-flex gap-2 align-items-center search-section" id="searchSection">
            <!-- Modern Search Bar -->
            <div class="search-container position-relative flex-grow-1" style="min-width: 320px;">
                <input type="search" id="invoiceSearch" class="form-control search-input shadow-sm"
                    placeholder="🔍 Search clients, invoices..." value="<?= h($q) ?>" autocomplete="off">
                <button class="btn btn-sm position-absolute end-0 top-0 bottom-0 search-clear me-2"
                    style="right: 10px; border-radius: 0 8px 8px 0;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <a href="payment_invoice_gen.php?tab=<?= $activeTab ?>" class="btn btn-primary px-4 shadow-sm">
                <i class="bi bi-plus-circle me-1"></i>
                <span class="d-none d-md-inline">Create</span> Invoice
            </a>
            <!-- NEW PDF EXPORT BUTTON -->
            <button id="exportPdfBtn" class="btn btn-success px-4 shadow-sm position-relative" title="Download Analytics Report as PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i>
                <span class="d-none d-md-inline">PDF Report</span>
                <div id="pdfLoadingSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" style="width:1rem;height:1rem;"></div>
            </button>
        </div>

        <!-- PDF Progress Modal -->
        <div class="modal fade" id="pdfProgressModal" tabindex="-1" data-bs-backdrop="static">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-xl rounded-4">
                    <div class="modal-body text-center p-5">
                        <div class="mb-4">
                            <div class="spinner-border text-primary mb-3" style="width: 4rem; height: 4rem;" role="status"></div>
                            <h5 class="fw-bold text-primary">Generating PDF Report...</h5>
                            <p class="text-muted mb-0">Capturing charts and preparing document</p>
                        </div>
                        <div class="progress mx-auto" style="width: 80%; height: 8px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%" id="pdfProgressBar"></div>
                        </div>
                        <div class="mt-4 text-xs text-muted" id="pdfProgressText">Step 1/4: Capturing charts...</div>
                    </div>
                </div>
            </div>
        </div>


    </div>

    <!-- ✅ DASHBOARD TABS: Charts vs Clients Table -->
    <ul class="nav nav-tabs nav-fill mb-4 shadow-sm bg-white rounded-top-lg border-0 overflow-hidden" id="dashboardTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active dashboard-tab-btn px-5 py-3 fs-5 fw-semibold border-0 rounded-0 shadow-none text-slate-700 active:text-blue-600 active:border-b-2 active:border-blue-500 transition-all duration-300" 
                    id="analytics-tab-btn" data-bs-toggle="tab" data-bs-target="#analytics-tab" type="button" role="tab">
                📊
                <span class="d-none d-md-inline">Analytics</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link dashboard-tab-btn px-5 py-3 fs-5 fw-semibold border-0 rounded-0 shadow-none text-slate-700 hover:text-blue-600 transition-all duration-300" 
                    id="clients-tab-btn" data-bs-toggle="tab" data-bs-target="#clients-tab" type="button" role="tab">
                📋
                <span class="d-none d-md-inline">Clients</span>
            </button>
        </li>
    </ul>

    <div class="tab-content border border-top-0 border-gray-200 rounded-bottom-lg shadow-lg overflow-hidden bg-white" id="dashboardTabContent">
        
        <!-- 📊 ANALYTICS TAB - All 5 Charts -->
        <div class="tab-pane fade show active p-4" id="analytics-tab" role="tabpanel">
            <div class="row g-4 mb-4">
                <!-- Modern Responsive Chart Containers - ROW 1 (3 Charts) - Mobile: 1-col, Desktop: 3-col -->
                <div class="col-12 col-md-6 col-lg-6 col-xl-4">
                    <div class="card border-0 perspective-card group hover:shadow-2xl transition-all duration-500 bg-gradient-to-br from-slate-50 via-blue-50/80 to-indigo-50/50 backdrop-blur-xl border border-blue-200/50 p-8 rounded-3xl h-100 relative overflow-hidden" style="--tilt: 10deg; --scale: 1.05;">
                        <!-- Shimmer overlay -->
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-[100%] group-hover:translate-x-[100%] transition-transform duration-1000 shimmer"></div>
                        <div class="relative z-10">
                            <div class="d-flex align-items-center justify-content-between mb-5">
                                <div>
                                    <h5 class="fw-bold text-slate-900 mb-1 ls-tight">Payment Status</h5>
                                    <div class="text-xs text-slate-500 font-medium tracking-wider uppercase">Live Analytics</div>
                                </div>
                                <div class="bg-gradient-to-r from-emerald-500/20 to-green-500/20 backdrop-blur-sm border border-emerald-200/50 text-emerald-700 px-4 py-2 rounded-full text-xs font-semibold shadow-lg">● Live</div>
                            </div>
                            <div class="chart-container position-relative" style="height: 300px; width: 100%;">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-6 col-lg-6 col-xl-4">
                    <div class="card border-0 perspective-card group hover:shadow-2xl transition-all duration-500 bg-gradient-to-br from-slate-50 via-purple-50/80 to-violet-50/50 backdrop-blur-xl border border-purple-200/50 p-8 rounded-3xl h-100 relative overflow-hidden" style="--tilt: 10deg; --scale: 1.05;">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -skew-x-12 -translate-x-[100%] group-hover:translate-x-[100%] transition-transform duration-700 shimmer"></div>
                        <div class="relative z-10">
                            <div class="d-flex align-items-center justify-content-between mb-5">
                                <div>
                                    <h5 class="fw-bold text-slate-900 mb-1 ls-tight">Payment Methods</h5>
                                    <div class="text-xs text-slate-500 font-medium tracking-wider uppercase">Breakdown</div>
                                </div>
                                <div class="fs-4 text-purple-600 opacity-90 group-hover:scale-110 transition-transform"><i class="bi bi-bar-chart-fill"></i></div>
                            </div>
                            <div class="chart-container position-relative" style="height: 300px; width: 100%;">
                                <canvas id="methodChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-12 col-lg-12 col-xl-4">
                    <div class="card border-0 perspective-card group hover:shadow-2xl transition-all duration-500 bg-gradient-to-br from-slate-50 via-emerald-50/80 to-teal-50/50 backdrop-blur-xl border border-emerald-200/50 p-8 rounded-3xl h-100 relative overflow-hidden" style="--tilt: 10deg; --scale: 1.05;">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-[100%] group-hover:translate-x-[100%] transition-transform duration-1000 shimmer"></div>
                        <div class="relative z-10">
                            <div class="d-flex align-items-center justify-content-between mb-5">
                                <div>
                                    <h5 class="fw-bold text-slate-900 mb-1 ls-tight">Revenue Trend</h5>
                                    <div class="text-xs text-slate-500 font-medium tracking-wider uppercase">Growth Analytics</div>
                                </div>
                                <div class="fs-4 text-emerald-600 opacity-90 group-hover:scale-110 transition-transform"><i class="bi bi-graph-up-arrow"></i></div>
                            </div>
                            <div class="chart-container position-relative" style="height: 300px; width: 100%;">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ROW 2: Recruitment Agency Charts - Mobile: 1-col, Desktop: 2-col -->
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-6">
                    <div class="card border-0 perspective-card group hover:shadow-2xl transition-all duration-500 bg-gradient-to-br from-slate-50 via-orange-50/80 to-amber-50/50 backdrop-blur-xl border border-orange-200/50 p-8 rounded-3xl h-100 relative overflow-hidden" style="--tilt: 10deg; --scale: 1.05;">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent -skew-x-12 -translate-x-[100%] group-hover:translate-x-[100%] transition-transform duration-1000 shimmer"></div>
                        <div class="relative z-10">
                            <div class="d-flex align-items-center justify-content-between mb-5">
                                <div>
                                    <h5 class="fw-bold text-slate-900 mb-1 ls-tight">Invoice Timeline</h5>
                                    <div class="text-xs text-slate-500 font-medium tracking-wider uppercase">Stacked Paid/Pending</div>
                                </div>
                                <div class="bg-gradient-to-r from-orange-500/20 to-yellow-500/20 backdrop-blur-sm border border-orange-200/50 text-orange-700 px-4 py-2 rounded-full text-xs font-semibold shadow-lg">● Live</div>
                            </div>
                            <div class="chart-container position-relative" style="height: 300px; width: 100%;">
                                <canvas id="timelineChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 col-lg-6">
                    <div class="card border-0 perspective-card group hover:shadow-2xl transition-all duration-500 bg-gradient-to-br from-slate-50 via-indigo-50/80 to-purple-50/50 backdrop-blur-xl border border-indigo-200/50 p-8 rounded-3xl h-100 relative overflow-hidden" style="--tilt: 10deg; --scale: 1.05;">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent -skew-x-12 -translate-x-[100%] group-hover:translate-x-[100%] transition-transform duration-700 shimmer"></div>
                        <div class="relative z-10">
                            <div class="d-flex align-items-center justify-content-between mb-5">
                                <div>
                                    <h5 class="fw-bold text-slate-900 mb-1 ls-tight">Top Clients</h5>
                                    <div class="text-xs text-slate-500 font-medium tracking-wider uppercase">Revenue Distribution</div>
                                </div>
                                <div class="fs-4 text-indigo-600 opacity-90 group-hover:scale-110 transition-transform"><i class="bi bi-people-fill"></i></div>
                            </div>
                            <div class="chart-container position-relative" style="height: 300px; width: 100%;">
                                <canvas id="clientsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 📋 CLIENTS TAB - KPIs + Invoice Table -->
        <div class="tab-pane fade p-4" id="clients-tab" role="tabpanel">
            <!-- Modern Summary Cards - Recruitment Agency Edition (8 KPIs) -->
            <div class="row g-3 mb-5">
                <!-- ORIGINAL 4 -->
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-red-500/10 to-pink-500/10 backdrop-blur-sm border-red-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-cash-stack fs-2 text-red-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Gross Total</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="grossVal">₱0</div>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-blue-500/10 to-indigo-500/10 backdrop-blur-sm border-blue-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-bank fs-2 text-blue-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Net Revenue</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="netVal">₱0</div>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-emerald-500/10 to-teal-500/10 backdrop-blur-sm border-emerald-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-graph-up fs-2 text-emerald-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Total Revenue</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="revenueVal">₱0</div>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-amber-500/10 to-orange-500/10 backdrop-blur-sm border-amber-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-exclamation-triangle fs-2 text-amber-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Pending</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="pendingVal">₱0</div>
                    </div>
                </div>
                <!-- NEW 4 RECRUITMENT KPIs -->
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-violet-500/10 to-purple-500/10 backdrop-blur-sm border-violet-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-people fs-2 text-violet-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Applicants Billed</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="applicantsVal">0</div>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-indigo-500/10 to-sky-500/10 backdrop-blur-sm border-indigo-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-currency-exchange fs-2 text-indigo-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Avg Invoice</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="avgInvoiceVal">₱0</div>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-emerald-500/15 to-green-500/15 backdrop-blur-sm border-emerald-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-graph-up-arrow fs-2 text-emerald-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Conversion Rate</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="conversionVal">0<span class="text-success">%</span></div>
                    </div>
                </div>
                <div class="col-lg-3 col-xl-1.5">
                    <div class="card border-0 shadow-lg hover:shadow-xl transition-all duration-300 bg-gradient-to-r from-amber-500/10 to-yellow-500/10 backdrop-blur-sm border-amber-200/50 p-5 rounded-3xl text-center h-100">
                        <div class="mb-2">
                            <i class="bi bi-clock-history fs-2 text-amber-600 opacity-90"></i>
                        </div>
                        <h6 class="text-slate-700 fw-semibold mb-2">Avg Days to Pay</h6>
                        <div class="h2 fw-bold text-slate-900 mb-1" id="daysToPayVal">0</div>
                        <small class="text-slate-500">days</small>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- ================= GLOBAL ACTION MODAL ================= -->
    <div class="modal fade" id="actionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content action-modal">
                <div class="modal-header">
                    <h6 class="modal-title fw-semibold" id="actionModalTitle">
                        Processing
                    </h6>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center py-5">
                    <!-- ✅ REQUIRED -->
                    <div id="actionModalIcon" class="mb-4"></div>

                    <div class="fw-semibold" id="actionModalTitle2">
                        Sending Invoice
                    </div>

                    <p class="text-muted mb-0" id="actionModalMessage">
                        Please wait a moment…
                    </p>
                </div>

                <div class="modal-footer justify-content-center d-none" id="actionModalFooter">
                    <button id="actionModalOK" class="btn btn-primary px-5 rounded-pill" data-bs-dismiss="modal">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

            <table class="w-full border border-gray-300">

                <thead class="bg-gray-50 border border-gray-300">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider w-96">
                            Client</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-32">
                            Invoices</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-48">
                            Total Amount</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-32">
                            Paid</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-32">
                            Unpaid</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-700 uppercase tracking-wider w-48">
                            Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200">

                    <?php if (!$invoices): ?>
                        <tr class="hover:bg-gray-50">
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500 text-muted">
                                No clients found
                            </td>
                        </tr>
                    <?php else:
                        foreach ($invoices as $inv): ?>

                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 border-r border-gray-200"> <!-- CLIENT -->
                                    <div class="flex items-center gap-3">
                                        <?= renderAvatar(null, $inv['client_name']) ?>
                                        <div>
                                            <div class="font-semibold text-gray-900"><?= h($inv['client_name']) ?></div>
                                            <div class="text-sm text-gray-500"><?= h($inv['client_email']) ?></div>
                                        </div>
                                    </div>
                                </td>

                                <!-- TOTAL INVOICES -->
                                <td class="px-6 py-4 text-center font-semibold border-r border-gray-200">
                                    <?= (int) $inv['total_invoices'] ?>
                                </td>

                                <!-- TOTAL AMOUNT -->
                                <td class="px-6 py-4 text-center font-semibold text-gray-900 border-r border-gray-200">
                                    ₱<?= number_format($inv['total_amount'], 2) ?>
                                </td>

                                <!-- PAID -->
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span
                                        class="inline-flex px-3 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded-full">
                                        <?= (int) $inv['paid_count'] ?>
                                    </span>
                                </td>

                                <!-- UNPAID -->
                                <td class="px-6 py-4 text-center border-r border-gray-200">
                                    <span
                                        class="inline-flex px-3 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">
                                        <?= (int) $inv['unpaid_count'] ?>
                                    </span>
                                </td>

                                <!-- ACTION -->
                                <td class="px-6 py-4 text-center">
                                    <button
                                        class="inline-flex items-center gap-2 justify-center h-10 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white transition-colors"
                                        data-booking="<?= (int) $inv['client_booking_id'] ?>"
                                        data-tab="<?= addslashes($activeTab) ?>" title="View History">
                                        <i class="bi bi-clock-history text-sm"></i>
                                        <span class="text-sm">History</span>
                                    </button>
                                </td>

                            </tr>

                        <?php endforeach; endif; ?>
        </div>
        </tbody>
        </table>

    </div>
</div>
<!-- ================= GLOBAL ACTION MODAL ================= -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content action-modal">
>>>>>>> 235a54802af57f4d780274015e0b84896be86e42

            <div class="modal-header">
                <h6 class="modal-title fw-semibold" id="actionModalTitle">
                    Processing
                </h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center py-5">
                <!-- ✅ REQUIRED -->
                <div id="actionModalIcon" class="mb-4"></div>

                <div class="fw-semibold" id="actionModalTitle2">
                    Sending Invoice
                </div>

                <p class="text-muted mb-0" id="actionModalMessage">
                    Please wait a moment…
                </p>
            </div>

            <div class="modal-footer justify-content-center d-none" id="actionModalFooter">
                <button id="actionModalOK" class="btn btn-primary px-5 rounded-pill" data-bs-dismiss="modal">
                    OK
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ================= CONFIRM RESEND MODAL ================= -->
<div class="modal fade" id="confirmResendModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-envelope-paper me-2"></i>
                    Resend Invoice
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body text-center">
                <p class="mb-0">
                    Are you sure you want to resend the invoice email to the client?
                </p>
            </div>

            <div class="modal-footer justify-content-center">
                <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button class="btn btn-primary px-4" id="confirmResendBtn">
                    Yes, Resend
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ================= IMPROVED CLIENT INVOICE HISTORY MODAL ================= -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content border-0 shadow-lg overflow-hidden"
            style="background:#fff;border-radius:20px;font-family:Inter,system-ui,-apple-system,sans-serif;">

            <!-- HEADER -->
            <div class="modal-header px-5 py-4 border-0" style="background:#2563eb;color:#fff;">
                <div>
                    <h4 class="fw-semibold mb-1 d-flex align-items-center">
                        <i class="bi bi-receipt me-3"></i>
                        Invoice History
                    </h4>
                    <div id="historyClientName" style="font-size:.9rem;opacity:.85;"></div>
                </div>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-0">

                <!-- CLIENT INFO -->
                <div class="px-5 py-4 border-bottom" style="background:#f8fafc;">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 id="historyClientFullName" class="fw-semibold mb-1"
                                style="font-size:1.25rem;color:#0f172a;">
                                Loading Client...
                            </h5>
                            <div style="font-size:.9rem;color:#64748b;">
                                <span id="historyClientEmail"></span> •
                                <span id="historyClientAddress"></span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div id="historyInvoiceCount" class="fw-bold" style="font-size:1.3rem;color:#2563eb;">
                                0 Invoices
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEARCH -->
                <div class="px-5 py-3 border-bottom">
                    <div class="position-relative">
                        <input id="modalSearch" type="text" class="form-control ps-5"
                            placeholder="Search invoice number, date, status…"
                            style="border-radius:14px;border:1px solid #e5e7eb;">
                        <button id="modalSearchClear" class="position-absolute end-0 top-50 translate-middle-y me-3"
                            style="border:none;background:none;color:#9ca3af;font-size:1.2rem;cursor:pointer;display:none;">×</button>
                        <i class="bi bi-search position-absolute"
                            style="left:16px;top:50%;transform:translateY(-50%);color:#64748b;"></i>
                    </div>
                </div>

                <!-- TABLE -->
                <div class="table-responsive" style="max-height:65vh;">
                    <table class="table mb-0 align-middle">
                        <thead class="sticky-top" style="background:#f8fafc;">
                            <tr>
                                <th class="ps-5 py-3 small text-uppercase">Invoice</th>
                                <th class="py-3 small text-uppercase">Date</th>
                                <th class="py-3 small text-uppercase">Due</th>
                                <th class="py-3 small text-uppercase text-end">Amount</th>
                                <th class="py-3 small text-uppercase text-center">Status</th>
                                <th class="pe-5 py-3 small text-uppercase text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="historyTableBody"></tbody>
                    </table>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer px-5 py-3 border-0" style="background:#f8fafc;">
                <button class="btn px-4 rounded-pill" data-bs-dismiss="modal" style="border:1px solid #cbd5e1;">
                    Close
                </button>
            </div>

        </div>
    </div>
</div>

<!-- ================= VIEW MODAL ================= -->
<div class="modal fade" id="viewModal" tabindex="-1">

    <div class="modal-dialog modal-l modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    📄 <span id="modal-company">CSNK</span> Invoice Preview
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="invoice-preview-paper">

                    <!-- HEADER -->
                    <div class="inv-header d-flex justify-content-between align-items-center">

                        <!-- LEFT SIDE: MAIN LOGO + INFO -->
                        <div>
                            <!-- CSNK MAIN LOGO -->
                            <img id="logo-csnk" src="../../resources/img/whychoose.png">

                            <!-- SMC MAIN LOGO -->
                            <img id="logo-smc" src="../../resources/img/smcbrandname.png" class="d-none">

                            <div class="mt-1 small text-muted" id="company-address">
                                Unit 1 Eden Townhomes<br>
                                Pedro Gil Street, Manila
                            </div>
                        </div>

                        <!-- RIGHT SIDE: BADGE LOGO -->
                        <div>
                            <!-- CSNK BADGE -->
                            <img id="badge-csnk" src="../../resources/img/csnk-iconz.png" height="95">

                            <!-- SMC BADGE -->
                            <img id="badge-smc" src="../../resources/img/smc.png" height="90" class="d-none">
                        </div>

                    </div>

                    <!-- TITLE -->
                    <div class="inv-title">INVOICE</div>

                    <div class="inv-meta">
                        <div>
                            <strong>Billed To:</strong><br>
                            <span id="pv-client-name"></span><br>
                            <span id="pv-client-email"></span><br>
                            <span id="pv-client-address"></span>
                        </div>
                        <div class="text-end">
                            <div><strong>Invoice #:</strong> <span id="pv-invoice-num"></span></div>
                            <div><strong>Date:</strong> <span id="pv-invoice-date"></span></div>
                            <div><strong>Due:</strong> <span id="pv-due-date"></span></div>
                            <div><strong>Ref:</strong> <span id="pv-ref-no"></span></div>
                        </div>
                    </div>

                    <table class="inv-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Period</th>
                                <th class="text-center">Days</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody id="pv-items"></tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end fw-bold">TOTAL:</td>
                                <td class="text-end fw-bold text-success">
                                    ₱<span id="pv-total">0.00</span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="inv-declaration">
                        I declare that all information contained in this invoice are certified true and correct.
                    </div>

                    <div class="inv-payment">
                        <strong>Issued By:</strong> <span id="issued-by">CSNK Agency</span>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="downloadPdfBtn">
                    <i class="bi bi-download me-2"></i>Download PDF
                </button>
            </div>

        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>
<script>
    let actionModal;

    document.addEventListener('DOMContentLoaded', () => {
        actionModal = new bootstrap.Modal(
            document.getElementById('actionModal')
        );
    });

    function showActionModal(title, message, type = 'loading') {
        const modalTitle = document.getElementById('actionModalTitle');
        const modalTitle2 = document.getElementById('actionModalTitle2');
        const modalMessage = document.getElementById('actionModalMessage');
        const icon = document.getElementById('actionModalIcon');
        const footer = document.getElementById('actionModalFooter');
        const okBtn = document.getElementById('actionModalOK');

        modalTitle.textContent = title;
        modalTitle2.textContent = title;
        modalMessage.textContent = message;

        footer.classList.add('d-none');

        if (type === 'loading') {
            icon.innerHTML = `
            <div class="spinner-border spinner-border-sm text-primary shadow-sm mb-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        } else if (type === 'success') {
            icon.innerHTML = `
            <i class="bi bi-check-circle-fill text-success fs-1 mb-3" style="font-size: 4rem;"></i>
        `;
            okBtn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Done!';
            okBtn.className = 'btn btn-lg btn-success px-5 shadow-lg';
            footer.classList.remove('d-none');
        } else if (type === 'error') {
            icon.innerHTML = `
            <i class="bi bi-x-circle-fill text-danger fs-1 mb-3" style="font-size: 4rem;"></i>
        `;
            okBtn.innerHTML = '<i class="bi bi-x-circle me-2"></i>OK';
            okBtn.className = 'btn btn-lg btn-outline-danger px-5 shadow-lg';
            footer.classList.remove('d-none');
        }

        actionModal.show();

        // ✅ Auto-close success after 3 seconds
        if (type === 'success') {
            setTimeout(() => {
                actionModal.hide();
            }, 3000);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('invoiceSearch') || document.querySelector('input[type="search"]');
        const tableBody = document.querySelector('tbody');
        const resultsCount = document.createElement('div');
        resultsCount.className = 'search-results';
        tableBody.parentNode.insertBefore(resultsCount, tableBody);


        const allInvoices = <?= json_encode($invoices, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;


        // Fuse.js for fuzzy search
        fuse = new Fuse(allInvoices, {
            keys: [
                { name: 'client_name', weight: 0.5 },
                { name: 'client_email', weight: 0.3 },
                { name: 'invoice_num', weight: 0.2 }
            ],
            threshold: 0.4,        // MORE typo tolerance
            distance: 200,
            ignoreLocation: true,
            minMatchCharLength: 2
        });

        // searchInput.addEventListener('input', function(e) {
        //     const query = e.target.value.trim();

        //     if (query.length === 0) {
        //         filteredInvoices = [...allInvoices];
        //     } else {
        //     const results = fuse.search(query);
        //     filteredInvoices = results.length ? results.map(r => r.item) : [];
        //     }

        //     renderInvoices(filteredInvoices);
        //     updateResultsCount(filteredInvoices.length);

        //     // Show/hide clear button
        //     document.querySelector('.search-clear').style.display = query ? 'block' : 'none';
        // });

        // Clear search
        document.querySelector('.search-clear').addEventListener('click', function () {
            searchInput.value = '';
            filteredInvoices = [...allInvoices];
            renderInvoices(filteredInvoices);
            updateResultsCount(allInvoices.length);
            this.style.display = 'none';
        });

        // ✅ Client list is rendered by PHP now
        // renderInvoices(allInvoices);
        // updateResultsCount(allInvoices.length);


        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', viewInvoiceHandler);
        });

        function updateResultsCount(count) {
            resultsCount.innerHTML = count === 1 ?
                '<i class="bi bi-check-circle-fill text-success me-1"></i>1 result' :
                `<i class="bi bi-check-circle-fill text-success me-1"></i>${count} results`;
        }

    });

    // Make escapeHtml global so openClientHistory can reuse it
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderAvatar(picture, name) {
        // If image exists
        if (picture && picture !== '') {
            return `
                <img 
                    src="../../uploads/${picture}" 
                    class="rounded-circle shadow-sm"
                    width="48" 
                    height="48"
                    alt="Avatar"
                >
            `;
        }

        // Fallback: first letter avatar
        const letter = name ? name.charAt(0).toUpperCase() : '?';

        return `
            <div 
                class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                style="width:48px;height:48px;background:#0d6efd;"
            >
                ${letter}
            </div>
        `;
    }

    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2 });
    }

    // View modal handler
    function viewInvoiceHandler() {
        const d = this.dataset;

        // ✅ Store PDF info
        currentInvoiceData = {
            pdf: d.pdf,
            total: parseFloat(d.total)
        };

        // ✅ Detect company
        const isSMC = d.invoice && d.invoice.startsWith('SMC-');

        // ✅ Toggle LEFT logos
        document.getElementById('logo-csnk').classList.toggle('d-none', isSMC);
        document.getElementById('logo-smc').classList.toggle('d-none', !isSMC);

        // ✅ Toggle RIGHT badge logos
        document.getElementById('badge-csnk').classList.toggle('d-none', isSMC);
        document.getElementById('badge-smc').classList.toggle('d-none', !isSMC);

        // ✅ Modal title
        document.getElementById('modal-company').textContent = isSMC ? 'SMC' : 'CSNK';

        // ✅ Issued by
        document.getElementById('issued-by').textContent =
            isSMC ? 'SMC Agency' : 'CSNK Agency';

        // ✅ Client info
        document.getElementById('pv-client-name').textContent = d.client || '';
        document.getElementById('pv-client-email').textContent = d.email || '';
        document.getElementById('pv-client-address').textContent = d.address || '';
        document.getElementById('pv-invoice-num').textContent = d.invoice || '';
        document.getElementById('pv-invoice-date').textContent =
            new Date(d.date).toLocaleDateString('en-PH');
        document.getElementById('pv-due-date').textContent =
            new Date(d.due).toLocaleDateString('en-PH');
        document.getElementById('pv-ref-no').textContent = d.ref || '';

        // ✅ Total
        document.getElementById('pv-total').textContent =
            parseFloat(d.total || 0).toLocaleString('en-PH', {
                minimumFractionDigits: 2
            });

        // ✅ Applicants
        const tbody = document.getElementById('pv-items');
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="text-center">
                    <div class="spinner-border spinner-border-sm"></div> Loading...
                </td>
            </tr>
        `;

        fetch('payments_clients.php?get_invoice_applicants=1&id=' + d.id)
            .then(res => res.json())
            .then(apps => {
                tbody.innerHTML = '';

                if (!apps || apps.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center text-muted">
                                No applicants assigned
                            </td>
                        </tr>
                    `;
                    return;
                }

                apps.forEach(app => {
                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${app.name}</td>
                            <td>${app.start_date} - ${app.end_date}</td>
                            <td class="text-center">${app.days}</td>
                            <td class="text-end fw-semibold">
                                ₱${parseFloat(app.amount).toLocaleString('en-PH', {
                        minimumFractionDigits: 2
                    })}
                            </td>
                        </tr>
                    `);
                });
            });

        // ✅ Download PDF
        document.getElementById('downloadPdfBtn').onclick = () => {
            if (!currentInvoiceData.pdf) {
                alert('PDF file not found.');
                return;
            }

            const link = document.createElement('a');
            link.href = '../../uploads/invoices/' + currentInvoiceData.pdf;
            link.download = currentInvoiceData.pdf;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };
    }

    let currentInvoiceData = {};


    let deleteTimer;
    let countdownTimer;
    let deleteInvoiceId;
    let deletedRow;

    function softDeleteInvoice(id, row) {

        if (!confirm('Delete this invoice?')) return;

        deleteInvoiceId = id;
        deletedRow = row;
        row.style.display = 'none';

        showDeleteToast();

        let seconds = 7;
        document.getElementById('toastCountdown').textContent = seconds;

        countdownTimer = setInterval(() => {
            seconds--;
            document.getElementById('toastCountdown').textContent = seconds;
            if (seconds <= 0) clearInterval(countdownTimer);
        }, 1000);

        deleteTimer = setTimeout(finalizeDelete, 7000);

        document.getElementById('undoDelete').onclick = undoDelete;
        document.getElementById('closeToast').onclick = finalizeDelete;
    }

    function showDeleteToast() {
        document.getElementById('deleteToast').classList.remove('hidden');
    }

    function undoDelete() {
        clearTimeout(deleteTimer);
        clearInterval(countdownTimer);
        deletedRow.style.display = '';
        hideDeleteToast();
    }

    function hideDeleteToast() {
        document.getElementById('deleteToast').classList.add('hidden');
    }

    function finalizeDelete() {
        window.location.href =
            `payments_clients.php?action=delete&id=${deleteInvoiceId}`;
    }

    let currentHistoryData = [];

    function openClientHistory(bookingId, tab) {
        // Defensive: Handle if button element passed
        if (typeof bookingId === 'object' && bookingId.dataset) {
            bookingId = bookingId.dataset.booking;
            tab = bookingId.dataset.tab;
        }

        bookingId = bookingId?.trim();
        tab = tab?.trim();

        console.log('Opening history for booking:', bookingId, 'tab:', tab);

        const historyModalEl = document.getElementById('historyModal');
        const modal = new bootstrap.Modal(historyModalEl);
        modal.show();

        document.getElementById('historyClientFullName').textContent = 'Loading Client...';
        document.getElementById('historyClientEmail').textContent = '';
        document.getElementById('historyClientAddress').textContent = '';
        document.getElementById('historyInvoiceCount').textContent = '0 Invoices';

        const tbody = document.getElementById('historyTableBody');
        tbody.innerHTML = `
        <tr>
            <td colspan="6" class="text-center py-5">
                <div class="spinner-border text-primary"></div>
                <div class="mt-3 text-muted">Loading invoice history...</div>
            </td>
        </tr>
    `;

        fetch(
            'payments_clients.php?get_client_history=1' +
            '&booking_id=' + encodeURIComponent(bookingId) +
            '&tab=' + encodeURIComponent(tab)
        )
            .then(res => {
                if (!res.ok) throw new Error('Network response failed');
                return res.json();
            })
            .then(data => {
                currentHistoryData = data;
                if (data && data.length > 0) {
                    const first = data[0];
                    document.getElementById('historyClientFullName').textContent = first.client_name;
                    document.getElementById('historyClientEmail').textContent = first.client_email;
                    document.getElementById('historyClientAddress').textContent = first.client_address;
                    document.getElementById('historyInvoiceCount').textContent = data.length + ' Invoices';
                } else {
                    document.getElementById('historyInvoiceCount').textContent = 'No Invoices';
                }
                renderHistoryTable(data || [], bookingId);
            })
            .catch(error => {
                console.error('Error loading invoice history:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-danger py-5">
                            <i class="bi bi-exclamation-triangle-fill fs-1 mb-3"></i>
                            <div class="fw-bold">Failed to load history</div>
                            <div class="text-muted">Please refresh and try again</div>
                        </td>
                    </tr>
                `;
            });

        // Modal search handlers (re-attach always)
        const modalSearch = document.getElementById('modalSearch');
        const modalSearchClear = document.getElementById('modalSearchClear');
        if (modalSearch && !modalSearch._handlerAttached) {
            modalSearch._handlerAttached = true;
            modalSearch.oninput = function () {
                const q = this.value.toLowerCase().trim();
                const filtered = currentHistoryData.filter(inv =>
                    inv.invoice_num?.toLowerCase().includes(q) ||
                    inv.reference_no?.toLowerCase().includes(q) ||
                    inv.status?.toLowerCase().includes(q) ||
                    inv.client_name?.toLowerCase().includes(q) ||
                    String(inv.total_amount || '').includes(q)
                );
                renderHistoryTable(filtered, bookingId);
                modalSearchClear.style.display = q ? 'block' : 'none';
            };
        }
        if (modalSearchClear) {
            modalSearchClear.onclick = function () {
                modalSearch.value = '';
                this.style.display = 'none';
                renderHistoryTable(currentHistoryData, bookingId);
            };
        }
    }

    function renderHistoryTable(data, bookingId) {
        currentHistoryData = Array.isArray(data) ? data : [];
        const tbody = document.getElementById('historyTableBody');

        if (currentHistoryData.length === 0) {
            tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox fs-1 mb-3 opacity-50"></i>
                            <div class="h5 fw-semibold mb-1">No invoices found</div>
                            <div class="text-muted">No invoice history matches your search.</div>
                        </td>
                    </tr>
                `;
            return;
        }

        // ✅ Check if there is any pending invoice
        const hasPending = currentHistoryData.some(inv => inv.payment_status !== 'Paid');

        const sortedData = [...currentHistoryData].sort((a, b) => {

            // ✅ Case A: There is at least one Pending → Pending first
            if (hasPending && a.payment_status !== b.payment_status) {
                return a.payment_status === 'Paid' ? 1 : -1;
            }

            // ✅ Always newest first
            return b.id - a.id;
        });

        tbody.innerHTML = '';

        sortedData.forEach(inv => {
            const isOverdue = inv.due_date && new Date(inv.due_date) < new Date();
            const statusText = inv.payment_status || 'Pending';
            const statusStyle = statusText === 'Paid'
                ? 'border:1px solid #059669;color:#059669;background:#ecfdf5;'
                : (isOverdue
                    ? 'border:1px solid #dc2626;color:#dc2626;background:#fef2f2;'
                    : 'border:1px solid #cbd5e1;color:#0f172a;background:#f8fafc;');

            tbody.insertAdjacentHTML('beforeend', `
                    <tr class="border-bottom hover:bg-gray-50 transition-colors">
                        <td class="ps-5 py-3">
                            <div class="fw-semibold text-blue-600">#${escapeHtml(inv.invoice_num || 'N/A')}</div>
                            <div class="small text-muted">${escapeHtml(inv.reference_no || 'N/A')}</div>
                        </td>
                        <td class="py-3">
                            <div>
                                ${inv.invoice_date
                    ? new Date(inv.invoice_date).toLocaleDateString('en-PH', {
                        month: 'short',
                        day: 'numeric',
                        year: 'numeric'
                    })
                    : '-'}
                            </div>

                            <small class="text-muted">
                                ${inv.created_at
                    ? new Date(inv.created_at).toLocaleTimeString('en-PH', {
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    })
                    : ''}
                                ${inv.invoice_date
                    ? ' • ' + new Date(inv.invoice_date).toLocaleDateString('en-PH', {
                        weekday: 'short'
                    })
                    : ''}
                            </small>
                        </td>
                        <td class="py-3 fw-medium ${isOverdue ? 'text-red-700' : ''}">
                            ${inv.due_date ? new Date(inv.due_date).toLocaleDateString('en-PH', {
                        month: 'short', day: 'numeric', year: 'numeric'
                    }) : '-'}
                        </td>
                        <td class="py-3 text-end fw-semibold text-gray-900">
                            ₱${parseFloat(inv.total_amount || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 })}
                        </td>
                        <td class="text-center py-3">
                            <span class="px-3 py-1 rounded-pill fw-semibold small" style="${statusStyle}">
                                ${statusText}
                            </span>
                        </td>
                        <td class="text-center pe-5 py-3">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-sm view-btn border rounded p-1" title="Preview"
                                    data-id="${inv.id}"
                                    data-invoice="${escapeHtml(inv.invoice_num || '')}"
                                    data-date="${inv.invoice_date || ''}"
                                    data-due="${inv.due_date || ''}"
                                    data-total="${inv.total_amount || 0}"
                                    data-ref="${escapeHtml(inv.reference_no || '')}"
                                    data-client="${escapeHtml(inv.client_name || '')}"
                                    data-email="${escapeHtml(inv.client_email || '')}"
                                    data-address="${escapeHtml(inv.client_address || '')}"
                                    data-pdf="${escapeHtml(inv.pdf_filename || '')}"
                                    data-bs-toggle="modal" data-bs-target="#viewModal">
                                    <i class="bi bi-eye"></i>
                                </button>
                                ${inv.status !== 'Paid' ? `
                                <button class="btn btn-sm btn-resend border rounded p-1" title="Resend Email"
                                    data-id="${inv.id}">
                                    <i class="bi bi-send"></i>
                                </button>` : ''}
                                <a href="payment_invoice_gen.php?edit=${inv.id}&booking_id=${bookingId}" 
                                   class="btn btn-sm border rounded p-1 text-primary text-decoration-none" title="Edit"
                                   target="_blank">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button class="btn btn-sm btn-delete border rounded p-1" title="Delete"
                                    data-id="${inv.id}">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
        });

        // Re-attach event listeners for new buttons
        tbody.querySelectorAll('.view-btn').forEach(btn => {
            btn.removeEventListener('click', viewInvoiceHandler);
            btn.addEventListener('click', viewInvoiceHandler);
        });
        tbody.querySelectorAll('.btn-resend').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                resendInvoiceEmail(this.dataset.id);
            });
        });
        tbody.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                softDeleteInvoice(btn.dataset.id, btn.closest('tr'));
            };
        });
    }

    function viewInvoiceFromHistory(id) {
        // reuse existing modal logic or redirect
        window.location.href = 'payments_clients.php?view=' + id;
    }

    function editInvoiceFromHistory(id) {
        window.location.href = 'payment_invoice_edit.php?id=' + id;
    }


    // ================= RESEND TRIGGER =================
    let pendingResendInvoiceId = null;

    function resendInvoiceEmail(invoiceId) {

        if (!invoiceId) return;

        pendingResendInvoiceId = invoiceId;

        // ✅ AUTO-CLOSE INVOICE HISTORY MODAL IF OPEN
        const historyModalEl = document.getElementById('historyModal');
        const historyModalInstance = bootstrap.Modal.getInstance(historyModalEl);

        if (historyModalInstance) {
            historyModalInstance.hide();
        }

        // ✅ SMALL DELAY TO AVOID MODAL STACKING
        setTimeout(() => {
            const confirmModal = new bootstrap.Modal(
                document.getElementById('confirmResendModal')
            );
            confirmModal.show();
        }, 300);
    }


    const searchInput = document.getElementById('invoiceSearch');
    let searchTimer;

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            const q = encodeURIComponent(this.value.trim());
            window.location.href = 'payments_clients.php?q=' + q;
        }, 500);
    });


    document.addEventListener('click', function (e) {

        /* History button */
        const historyBtn = e.target.closest('[data-booking]');
        if (historyBtn) {
            openClientHistory(historyBtn.dataset.booking, historyBtn.dataset.tab);
            return;
        }

        /* Edit invoice */
        const editLink = e.target.closest('.edit-invoice-link');
        if (editLink) {
            window.location.href = `payment_invoice_gen.php?edit=${editLink.dataset.edit}&booking_id=${editLink.dataset.booking}`;
            return;
        }

        /* ================= DELETE ================= */
        const delBtn = e.target.closest('.btn-delete');
        if (delBtn) {
            softDeleteInvoice(delBtn.dataset.id, delBtn.closest('tr'));
            return;
        }

        /* ================= RESEND ================= */
        const resendBtn = e.target.closest('.btn-resend');
        if (resendBtn) {
            resendInvoiceEmail(resendBtn.dataset.id);
            return;
        }

        /* ================= VIEW ================= */
        const viewBtn = e.target.closest('.view-btn');
        if (viewBtn) {
            viewInvoiceHandler.call(viewBtn);
            return;
        }

    });

    // ================= CONFIRM RESEND HANDLER =================
    document.getElementById('confirmResendBtn').addEventListener('click', function () {

        if (!pendingResendInvoiceId) return;

        // Close confirm modal
        bootstrap.Modal.getInstance(
            document.getElementById('confirmResendModal')
        ).hide();

        // Show processing modal
        showActionModal(
            'Resending Invoice',
            'Sending invoice email to client... Please wait.',
            'loading'
        );

            fetch(
            'payments_clients.php?resend_invoice_email=1&id=' +
            encodeURIComponent(pendingResendInvoiceId)
            )
            .then(res => {
                if (!res.ok) throw new Error('HTTP ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showActionModal(
                        '✅ Email Sent Successfully',
                        data.message || 'Invoice email has been sent to the client.',
                        'success'
                    );
                } else {
                    throw new Error(data.message || 'Failed to send invoice');
                }
            })
            .catch(err => {
                showActionModal(
                    '❌ Failed to Send Email',
                    err.message,
                    'error'
                );
            })
            .finally(() => {
                pendingResendInvoiceId = null;
            });
    });

    function peso(val) {
        return Number(val).toLocaleString('en-PH', {
            minimumFractionDigits: 2
        });
    }

    
    // Enhanced Tab Management with Persistence, Lazy Charts, Smooth Transitions
    let chartInstances = {}; // Store chart instances for lazy destroy/create
    let currentCompanyTab = new URLSearchParams(window.location.search).get('tab') || 'CSNK';
let isChartsLoaded = false;
let chartLoadPromise = null; // Prevent concurrent loads
    
    // Update URL with dashboard tab state
    function updateDashboardTabURL(targetId) {
        const url = new URL(window.location);
        url.searchParams.set('dashboard_tab', targetId);
        window.history.replaceState({}, '', url);
    }
    
    // Robust tab persistence from localStorage + URL
    function getActiveTab() {
        const urlTab = new URLSearchParams(window.location.search).get('dashboard_tab');
        const lsTab = localStorage.getItem('dashboardTab');
        return urlTab || lsTab || 'analytics-tab';
    }
    
    // Smooth tab transition utility
    function switchTabSmooth(targetId, callback) {
        const targetPane = document.getElementById(targetId);
        const currentPane = document.querySelector('.tab-pane.show.active');
        
        if (currentPane) {
            currentPane.classList.add('tab-fade-out');
            setTimeout(() => {
                currentPane.classList.remove('show', 'active', 'tab-fade-out');
                if (targetPane) {
                    targetPane.classList.add('show', 'active', 'tab-fade-in');
                    setTimeout(() => {
                        targetPane.classList.remove('tab-fade-in');
                        if (callback) callback();
                    }, 400);
                }
            }, 200);
        }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        const activeTabId = getActiveTab();
        const targetBtn = document.querySelector(`[data-bs-target="#${activeTabId}"]`);
        const targetPane = document.getElementById(activeTabId);
        
        // Set initial active state
        document.querySelectorAll('.dashboard-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('show', 'active'));
        
        if (targetBtn) targetBtn.classList.add('active');
        if (targetPane) targetPane.classList.add('show', 'active');
        
        // Initial chart load if analytics active
        if (activeTabId === 'analytics-tab') {
            loadAndRenderCharts();
        }
        
        // PDF Export Button Handler
        document.getElementById('exportPdfBtn')?.addEventListener('click', exportAnalyticsPdf);

        /**
         * Step 4: Complete PDF Export Implementation with html2canvas
         */
        async function exportAnalyticsPdf() {
            const btn = document.getElementById('exportPdfBtn');
            const spinner = document.getElementById('pdfLoadingSpinner');
            const progressModal = new bootstrap.Modal(document.getElementById('pdfProgressModal'));
            const progressBar = document.getElementById('pdfProgressBar');
            const progressText = document.getElementById('pdfProgressText');

            try {
                // Show loading on button
                spinner.classList.remove('d-none');
                btn.disabled = true;

                // Load html2canvas if not already loaded
                if (typeof html2canvas === 'undefined') {
                    progressText.textContent = 'Loading html2canvas...';
                    const script = document.createElement('script');
                    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js';
                    document.head.appendChild(script);
                    await new Promise(resolve => {
                        script.onload = resolve;
                    });
                }

                // Show progress modal
                progressModal.show();

                // Step 1: Capture all 5 charts
                progressText.textContent = 'Step 1/4: Capturing charts...';
                progressBar.style.width = '25%';

                const chartIds = ['statusChart', 'methodChart', 'trendChart', 'timelineChart', 'clientsChart'];
                const chartImages = {};

                for (let i = 0; i < chartIds.length; i++) {
                    const chartId = chartIds[i];
                    const canvas = document.getElementById(chartId);
                    
                    if (canvas) {
                        const chartCanvas = await html2canvas(canvas.parentElement, {
                            scale: 3,
                            useCORS: true,
                            backgroundColor: '#ffffff',
                            allowTaint: false,
                            logging: false,
                            width: canvas.parentElement.offsetWidth,
                            height: canvas.parentElement.offsetHeight
                        });
                        
                        const key = chartId.replace('Chart', '') === 'method' ? 'methods' : chartId.replace('Chart', '');
                        chartImages[key] = chartCanvas.toDataURL('image/png', 1.0);
                    }
                }

                progressText.textContent = 'Step 2/4: Preparing data...';
                progressBar.style.width = '50%';

                const tab = new URLSearchParams(window.location.search).get('tab') || 'CSNK';

                // Step 3: Send to server
                progressText.textContent = 'Step 3/4: Generating PDF...';
                progressBar.style.width = '75%';

                const formData = new FormData();
                formData.append('chartImages', JSON.stringify(chartImages));

                const response = await fetch(`?export_analytics_pdf=1&tab=${tab}`, {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'PDF generation failed');
                }

                // Step 4: Download
                progressText.textContent = 'Step 4/4: Downloading...';
                progressBar.style.width = '100%';

                // Trigger download
                const link = document.createElement('a');
                link.href = result.pdfUrl;
                link.download = `analytics-${tab.toLowerCase()}-${new Date().toISOString().slice(0,10)}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                // Show success
                setTimeout(() => {
                    progressModal.hide();
                    spinner.classList.add('d-none');
                    btn.disabled = false;
                    
                    // Success toast
                    showActionModal(
                        '✅ PDF Downloaded!',
                        'Analytics report has been downloaded successfully.',
                        'success'
                    );
                }, 1000);

            } catch (error) {
                console.error('PDF Export Error:', error);
                progressModal.hide();
                spinner.classList.add('d-none');
                btn.disabled = false;
                
                showActionModal(
                    '❌ PDF Export Failed',
                    error.message || 'Failed to generate PDF. Please try again.',
                    'error'
                );
            }
        }

        
        // Search always visible - no toggle needed
    });

    // Enhanced tab switch event with smooth transitions
document.addEventListener('shown.bs.tab', function (e) {
        const targetId = e.target.getAttribute('data-bs-target').substring(1);
        
        // Persistence
        localStorage.setItem('dashboardTab', targetId);
        updateDashboardTabURL(targetId);
        
        // Smooth transition
        switchTabSmooth(targetId, () => {
            if (targetId === 'analytics-tab') {
                if (!chartLoadPromise) {
                    chartLoadPromise = loadAndRenderCharts().finally(() => {
                        chartLoadPromise = null;
                    });
                }
                toggleSearchSection(false);
            } else {
                destroyAllCharts();
                toggleSearchSection(true);
            }
        });
    });

    function toggleSearchSection(show) {
        // Search section always visible now
    }
    
    // Lazy chart loading with destroy/create
    function loadAndRenderCharts() {
        const tab = new URLSearchParams(window.location.search).get('tab') || 'CSNK';
        const cacheBust = Date.now();

        return fetch(`payments_charts.php?company=${tab}&cb=${cacheBust}`, {
            cache: 'no-cache'
        })
        .then(res => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.json();
        })
        .then(data => {
            if (!data || Object.keys(data).length === 0 || data.error) {
                showNoDataCharts();
                return;
            }
            renderCharts(data);
        })
        .catch(err => {
            console.error('Chart load error:', err);
            showNoDataCharts();
        });
    }

    
    function destroyAllCharts() {
        Object.values(chartInstances).forEach(chart => {
            if (chart && chart.destroy) chart.destroy();
        });
        chartInstances = {};
    }

function renderCharts(data) {

    destroyAllCharts(); // ✅ CLEAR OLD CHARTS FIRST
    chartInstances = {}; // ✅ RESET STORAGE
    
    // Populate summary cards first (safe even with empty data)
    try {
        document.getElementById('grossVal').textContent = peso(data.summary?.gross || 0);
        document.getElementById('netVal').textContent = peso(data.summary?.net || 0);
        document.getElementById('revenueVal').textContent = peso(data.summary?.revenue || 0);
        document.getElementById('pendingVal').textContent = peso(data.summary?.pending || 0);
        
        document.getElementById('applicantsVal').textContent = data.kpis?.applicants_billed || 0;
        document.getElementById('avgInvoiceVal').textContent = peso(data.kpis?.avg_invoice_value || 0);
        document.getElementById('conversionVal').innerHTML = (data.kpis?.conversion_rate || 0) + '<span class="text-success">%</span>';
        document.getElementById('daysToPayVal').textContent = data.kpis?.avg_days_to_pay || 0;
    } catch(e) {
        console.warn('Summary cards update failed:', e);
    }

        // Modern responsive charts with perfect screen fit
        // Ultra-professional chart config
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    position: (ctx) => ctx.chart.width > 768 ? 'right' : 'bottom',
                    align: 'center',
                    labels: {
                        padding: 25,
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 12,
                        boxHeight: 12,
                        font: { 
                            size: 13, 
                            family: 'Inter, -apple-system, BlinkMacSystemFont, sans-serif',
                            weight: '500'
                        },
                        color: '#64748b'
                    }
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(15, 23, 42, 0.98)',
                    titleColor: '#f8fafc',
                    bodyColor: '#f1f5f9',
                    borderColor: '#1e293b',
                    borderWidth: 1.5,
                    cornerRadius: 16,
                    displayColors: true,
                    padding: 20,
                    titleFont: { family: 'Inter', size: 14, weight: '600' },
                    bodyFont: { family: 'Inter', size: 13 },
                    footerFont: { family: 'Inter', size: 12 },
                    callbacks: {
                        title: (ctx) => ctx[0].label.replace(/\\n/g, ' '),
                        label: (ctx) => `₱${ctx.parsed.y?.toLocaleString('en-PH', {minimumFractionDigits: 0, maximumFractionDigits: 2})}`,
                        afterLabel: (ctx) => {
                            const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                            const pct = ((ctx.parsed.y/total)*100).toFixed(1);
                            return `${pct}% of total`;
                        }
                    },
                    filter: (tooltipItem) => tooltipItem.parsed.y > 0
                },
                datalabels: {
                    display: function(context) {
                        return context.dataset.data[context.dataIndex] > 0;
                    },
                    font: { 
                        weight: '700',
                        family: 'Inter, sans-serif',
                        size: 12
                    },
                    color: '#1e293b',
                    formatter: (value, ctx) => {
                        if (ctx.chart.type === 'doughnut') {
                            const total = ctx.dataset.data.reduce((a,b)=>a+b,0);
                            return `${((value/total)*100).toFixed(0)}%`;
                        }
                        return `₱${value.toLocaleString()}`;
                    },
                    anchor: 'end',
                    align: 'start',
                    offset: 4
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeOutElastic',
                delay: (ctx) => ctx.dataIndex * 100
            },
            scales: {
                x: {
                    grid: { 
                        color: ctx => ctx.tick.value === ctx.chart.scales.x.max ? '#e2e8f0' : 'transparent',
                        drawBorder: false
                    },
                    ticks: { 
                        color: '#94a3b8',
                        font: { family: 'Inter, sans-serif', size: 12 },
                        maxRotation: 0,
                        padding: 12
                    },
                    border: { display: false }
                },
                y: {
                    grid: { 
                        color: '#f1f5f9',
                        drawBorder: false
                    },
                    ticks: { 
                        color: '#94a3b8',
                        font: { family: 'Inter, sans-serif', size: 12 },
                        callback: (value) => `₱${value.toLocaleString('en-PH')}`,
                        padding: 12
                    },
                    border: { display: false }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8,
                    hoverBorderWidth: 3
                },
                bar: {
                    borderRadius: 12,
                    borderSkipped: false
                },
                line: {
                    borderWidth: 4,
                    tension: 0.45
                }
            }
        };

    const statusCtx = document.getElementById('statusChart')?.getContext('2d');
    if (statusCtx && data.status) {
        const paid = Math.max(data.status.paid || 0, 0);
        const pending = Math.max(data.status.pending || 0, 0);
        const total = paid + pending;
        
        if (total === 0) {
            // Show placeholder chart for empty data
            const emptyGradient = statusCtx.createLinearGradient(0, 0, 0, 300);
            emptyGradient.addColorStop(0, '#6b7280');
            emptyGradient.addColorStop(1, '#9ca3af');
            
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        data: [1],
                        backgroundColor: [emptyGradient],
                        borderWidth: 0,
                        cutout: '65%'
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }
        
        const gradient1 = statusCtx.createLinearGradient(0, 0, 0, 300);
        gradient1.addColorStop(0, '#10b981');
        gradient1.addColorStop(1, '#047857');
        
        const gradient2 = statusCtx.createLinearGradient(0, 0, 0, 300);
        gradient2.addColorStop(0, '#f59e0b');
        gradient2.addColorStop(1, '#d97706');
        
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Paid ✅', 'Pending ⏳'],
                datasets: [{
                    data: [paid, pending],
                    backgroundColor: [gradient1, gradient2],
                    borderWidth: 0,
                    cutout: '65%',
                    hoverOffset: 8
                }]
            },
            options: commonOptions
        });
    }

        // Payment Methods - Stacked gradient bars
    const methodCtx = document.getElementById('methodChart')?.getContext('2d');
    if (methodCtx) {
        const methodData = data.methods || [];
        if (methodData.length === 0) {
            // Empty state
            new Chart(methodCtx, {
                type: 'bar',
                data: { labels: ['No Payments'], datasets: [{ data: [1], backgroundColor: ['#6b7280'] }] },
                options: {
                    ...commonOptions,
                    plugins: { legend: { display: false }, tooltip: { enabled: false } },
                    scales: { y: { display: false } }
                }
            });
            return;
        }
        new Chart(methodCtx, {
            type: 'bar',
            data: {
                labels: methodData.map(m => m.payment_provider || 'Unknown'),
                datasets: [{
                    label: 'Revenue (₱)',
                    data: methodData.map(m => parseFloat(m.amount || 0)),
                    backgroundColor: methodData.map((_, i) => 
                        `hsl(${220 + (i * 40) % 360}, 70%, 55%)`
                    ),
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 36
                }]
            },
            options: {
                ...commonOptions,
                plugins: { ...commonOptions.plugins, legend: { display: false } },
                scales: {
                    ...commonOptions.scales,
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // ✅ Revenue Trend - Smooth area fill with gradient (FIXED)
    const trendCanvas = document.getElementById('trendChart');

        if (trendCanvas && data?.trend && Array.isArray(data.trend) && data.trend.length > 0) {
            const trendCtx = trendCanvas.getContext('2d');

            const gradientFill = trendCtx.createLinearGradient(0, 0, 0, 300);
            gradientFill.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
            gradientFill.addColorStop(1, 'rgba(16, 185, 129, 0)');

            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: data.trend.map(t =>
                        t.date
                            ? new Date(t.date).toLocaleDateString('en-PH', {
                                month: 'short',
                                day: 'numeric'
                            })
                            : ''
                    ),
                    datasets: [{
                        label: 'Daily Revenue',
                        data: data.trend.map(t => Number(t.amount) || 0),
                        borderColor: '#10b981',
                        backgroundColor: gradientFill,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#10b981',
                        pointBorderWidth: 3,
                        pointRadius: 6,
                        pointHoverRadius: 8,
                        pointHoverBorderWidth: 2
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { display: false }
                    },
                    scales: {
                        ...commonOptions.scales,
                        x: {
                            ...commonOptions.scales.x,
                            grid: { display: false }
                        }
                    }
                }
            });
        } else {
            console.warn('Trend chart skipped — no trend data available');
        }

        // NEW CHARTS: Invoice Timeline (stacked bar)
        const timelineCtx = document.getElementById('timelineChart')?.getContext('2d');
        if (timelineCtx) {
            const paidGradient = timelineCtx.createLinearGradient(0, 0, 0, 300);
            paidGradient.addColorStop(0, '#10b981');
            paidGradient.addColorStop(1, '#047857');
            
            const pendingGradient = timelineCtx.createLinearGradient(0, 0, 0, 300);
            pendingGradient.addColorStop(0, '#f59e0b');
            pendingGradient.addColorStop(1, '#d97706');
            
            new Chart(timelineCtx, {
                type: 'bar',
                data: {
                    labels: data.timeline.map(t =>
                        t.date
                            ? new Date(t.date).toLocaleDateString('en-PH', {
                                month: 'short',
                                day: 'numeric'
                            })
                            : ''
                    ),
                    datasets: [
                        {
                            label: 'Paid',
                            data: data.timeline.map(t => parseFloat(t.paid_amount || 0)),
                            backgroundColor: paidGradient,
                            borderRadius: 8,
                            borderSkipped: false,
                            stack: 'stack1'
                        },
                        {
                            label: 'Pending',
                            data: data.timeline.map(t => parseFloat(t.pending_amount || 0)),
                            backgroundColor: pendingGradient,
                            borderRadius: 8,
                            borderSkipped: false,
                            stack: 'stack1'
                        }
                    ]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            position: 'top',
                            align: 'center'
                        }
                    },
                    scales: {
                        ...commonOptions.scales,
                        x: commonOptions.scales.x,
                        y: {
                            ...commonOptions.scales.y,
                            stacked: true,
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        // NEW CHARTS: Top Clients (doughnut with revenue %)
        const clientsCtx = document.getElementById('clientsChart')?.getContext('2d');
        if (clientsCtx) {
            new Chart(clientsCtx, {
                type: 'doughnut',
                data: {
                    labels: data.top_clients.map(c => c.client_name?.slice(0, 20) || 'Unknown'),
                    datasets: [{
                        data: data.top_clients.map(c => parseFloat(c.revenue || 0)),
                        backgroundColor: [
                            '#8b5cf6', '#ec4899', '#f97316', '#14b8a6', '#3b82f6'
                        ],
                        borderWidth: 0,
                        cutout: '70%',
                        hoverOffset: 6
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: { 
                            position: 'right',
                            labels: {
                                ...commonOptions.plugins.legend.labels,
                                generateLabels: (chart) => {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map((label, i) => {
                                            const value = data.datasets[0].data[i];
                                            const total = data.datasets[0].data.reduce((a,b)=>a+b,0);
                                            const pct = ((value/total)*100).toFixed(1);
                                            return {
                                                text: `${label} (${pct}%)`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                strokeStyle: data.datasets[0].borderColor ? data.datasets[0].borderColor[i] : data.datasets[0].backgroundColor[i],
                                                lineWidth: data.datasets[0].borderWidth,
                                                hidden: false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        }
                    }
                }
            });
        }

// Enhanced empty state function
function showNoDataCharts() {
    const charts = ['statusChart', 'methodChart', 'trendChart', 'timelineChart', 'clientsChart'];
    charts.forEach(chartId => {
        const ctx = document.getElementById(chartId)?.getContext('2d');
        if (ctx) {
Chart(ctx, {
    plugins: [ChartDatalabels],
                type: 'doughnut',
                data: {
                    labels: ['No Data'],
                    datasets: [{
                        data: [1],
                        backgroundColor: ['#e2e8f0'],
                        borderWidth: 0,
                        cutout: '70%'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: false }
                    }
                }
            });
        }
    });
}

    // Resize observer for responsive charts
    const resizeObserver = new ResizeObserver(() => {
        window.dispatchEvent(new Event('chart-resize'));
    });
    document.querySelectorAll('.chart-container').forEach(container => {
        resizeObserver.observe(container);
    });
        }


</script>
<script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js"></script>
<?php include '../includes/footer.php'; ?>