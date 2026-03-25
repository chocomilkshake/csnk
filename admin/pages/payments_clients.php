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

    $stmt = $conn->prepare("
        SELECT
            id,
            invoice_num,
            invoice_date,
            due_date,
            total_amount,
            CASE
                WHEN payment_status = 'Paid' THEN 'Paid'
                WHEN due_date < CURDATE() THEN 'OverDue'
                ELSE 'Pending'
            END AS status,
            reference_no,
            client_name,
            client_email,
            client_address,
            applicants_data,
            pdf_filename
        FROM invoice_history
        WHERE client_booking_id = ?
          AND company_type = ?
        ORDER BY invoice_date DESC
    ");

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
</style>
<script src="https://cdn.tailwindcss.com"></script>

<div class="container-fluid py-4">


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

        <!-- RIGHT: SEARCH + ADD BUTTONS -->
        <div class="d-flex gap-2 align-items-center">
            <!-- Modern Search Bar -->
            <div class="search-container position-relative">
                <input type="search" id="invoiceSearch" class="form-control search-input shadow-sm"
                    placeholder="🔍 Search clients, invoices..." value="<?= h($q) ?>" autocomplete="off">
                <button class="btn btn-sm position-absolute end-0 top-0 bottom-0 search-clear"
                    style="right: 10px; border-radius: 0 8px 8px 0;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <a href="payment_invoice_gen.php" class="btn btn-primary px-3 position-relative">
                <i class="bi bi-plus-circle"></i> Create Invoice
            </a>
        </div>

    </div>

    <div class="bg-white rounded-xl overflow-hidden">
        <div class="overflow-x-auto">


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
                                        onclick="openClientHistory(<?= (int) $inv['client_booking_id'] ?>, '<?= addslashes($activeTab) ?>')"
                                        title="View History">
                                        <i class="bi bi-clock-history text-sm"></i>
                                        <span class="text-sm">History</span>
                                    </button>
                                </td>

                            </tr>

                        <?php endforeach; endif; ?>
                </tbody>
            </table>

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
                    <div class="spinner-border text-primary mb-4" role="status"></div>
                    <div class="fw-semibold" id="actionModalTitle2">
                        Sending Invoice
                    </div>
                    <p class="text-muted mb-0" id="actionModalMessage">
                        Please wait a moment…
                    </p>
                </div>

                <div class="modal-footer justify-content-center d-none" id="actionModalFooter">
                    <button class="btn btn-primary px-5 rounded-pill" data-bs-dismiss="modal">
                        OK
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

        <div class="modal-dialog modal-xl modal-dialog-centered">
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
                                <img id="logo-csnk" src="../../resources/img/whychoose.png" height="65">

                                <!-- SMC MAIN LOGO -->
                                <img id="logo-smc" src="../../resources/img/smcbrandname.png" height="90"
                                    class="d-none">

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

    <script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2/dist/fuse.min.js"></script>
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


        // Client-side search (fuzzy, instant, typo-tolerant)
        let allInvoices = <?= json_encode($invoices, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        let filteredInvoices = [...allInvoices];
        let fuse;

        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('invoiceSearch') || document.querySelector('input[type="search"]');
            const tableBody = document.querySelector('tbody');
            const resultsCount = document.createElement('div');
            resultsCount.className = 'search-results';
            tableBody.parentNode.insertBefore(resultsCount, tableBody);

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


        let deleteTimer = null;
        let countdownTimer = null;
        let pendingDeleteId = null;
        let countdownValue = 10;

        function softDeleteInvoice(id) {
            if (!confirm('Delete this invoice?')) return;

            pendingDeleteId = id;
            countdownValue = 10;

            // Hide row immediately
            const row = document.querySelector(
                `button[onclick="softDeleteInvoice(${id})"]`
            ).closest('tr');
            row.style.display = 'none';

            showDeleteToast();

            // Countdown display
            document.getElementById('toastCountdown').textContent = countdownValue;

            countdownTimer = setInterval(() => {
                countdownValue--;
                document.getElementById('toastCountdown').textContent = countdownValue;

                if (countdownValue <= 0) {
                    clearInterval(countdownTimer);
                }
            }, 1000);
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
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        const first = data[0];
                        document.getElementById('historyClientFullName').textContent = first.client_name;
                        document.getElementById('historyClientEmail').textContent = first.client_email;
                        document.getElementById('historyClientAddress').textContent = first.client_address;
                        document.getElementById('historyInvoiceCount').textContent = data.length + ' Invoices';
                    }
                    renderHistoryTable(data);
                });
            .then(data => {
                currentHistoryData = Array.isArray(data) ? data : [];

                // Populate client info
                    onclick="editInvoiceFromHistory(${inv.id})"
                    style="border:1px solid #e5e7eb;border-radius:10px;">
                <i class="bi bi-pencil"></i>
            </button>

            <button class="btn btn-sm"
                    title="Delete"
                    onclick="softDeleteInvoice(${inv.id})"
                    style="border:1px solid #e5e7eb;border-radius:10px;">
                <i class="bi bi-trash"></i>
            </button>

            </div>
        </td>