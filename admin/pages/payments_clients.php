<?php
session_start();


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
            'name'        => $app['name'] ?? 'Unknown Applicant',
            'start_date'  => $app['start_date'] ?? '',
            'end_date'    => $app['end_date'] ?? '',
            'days'        => isset($app['days']) ? (int)$app['days'] : 0,
            'amount'      => isset($app['amount']) ? (float)$app['amount'] : 0
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
    SELECT ih.*, NULL AS picture
    FROM invoice_history ih
    WHERE ih.client_name LIKE ?
      AND ih.invoice_num LIKE ?
    ORDER BY ih.id DESC
";

$stmt = $conn->prepare($sql);

$like   = '%' . $q . '%';
$prefix = $activeTab . '-%'; // CSNK-% or SMC-%

$stmt->bind_param('ss', $like, $prefix);
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
/* === MODERN DELETE TOAST (LIGHT THEME) === */
.delete-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
    min-width: 300px;
    z-index: 9999;
    animation: slideUp 0.3s ease;
}

.delete-toast.hidden {
    display: none;
}

.toast-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 18px;
}

.toast-text {
    display: flex;
    flex-direction: column;
}

.toast-text strong {
    font-size: 14px;
    color: #212529;
}

.toast-sub {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
}

.toast-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.toast-undo {
    background: none;
    border: none;
    color: #0d6efd;
    font-weight: 700;
    cursor: pointer;
    font-size: 13px;
}

.toast-undo:hover {
    text-decoration: underline;
}

.toast-close {
    background: none;
    border: none;
    color: #adb5bd;
    font-size: 16px;
    cursor: pointer;
}

.toast-close:hover {
    color: #212529;
}

/* Animation */
@keyframes slideUp {
    from {
        transform: translateY(20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* === 2026 MODERN SEARCH BAR === */
.search-container {
    width: 320px;
    max-width: 100%;
}

.search-input {
    padding: 12px 16px 12px 45px;
    border: 2px solid #e9ecef;
    border-radius: 16px;
    font-size: 15px;
    background: rgba(255,255,255,0.8);
    backdrop-filter: blur(16px);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 20px rgba(0,0,0,0.06);
}

.search-input:focus {
    border-color: #0d6efd;
    background: #fff;
    box-shadow: 0 8px 32px rgba(13,110,253,0.15);
    transform: translateY(-1px);
}

.search-input::placeholder {
    color: #adb5bd;
    font-weight: 400;
}

.search-clear {
    display: none;
    background: none;
    border: none;
    color: #6c757d;
    padding: 0 8px;
    opacity: 0.6;
}

.search-input:focus ~ .search-clear,
.search-clear:hover {
    opacity: 1;
}

.search-clear:not(:hover) i {
    font-size: 14px;
}

/* === INVOICE TABS (MODERN) === */
.invoice-tabs {
    display: flex;
    gap: 12px;
}

/* Results counter */
.search-results {
    font-size: 14px;
    color: #6c757d;
    margin-top: 8px;
}

/* No results */
.no-results {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-results i {
    font-size: 64px;
    opacity: 0.3;
    display: block;
    margin-bottom: 16px;
}


.tab-btn {
    padding: 10px 26px;
    font-weight: 700;
    border-radius: 999px;
    border: none;
    text-decoration: none;
    letter-spacing: 0.5px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    transition: all 0.25s ease;
}

.tab-csnk {
    background: linear-gradient(135deg, #c62828, #e53935);
    color: #fff;
}

.tab-smc {
    background: linear-gradient(135deg, #0b1c3d, #102a5e);
    color: #d4af37;
}

.tab-btn.inactive {
    opacity: 0.45;
    box-shadow: none;
}

.tab-btn:hover {
    opacity: 1;
    transform: translateY(-2px);
}


/* === ONLY REQUIRED CSS FOR VIEW MODAL === */
.invoice-preview-paper {
    background: #fff;
    padding: 40px;
    font-family: Arial, Helvetica, sans-serif;
}
.inv-header {
    display: flex;
    justify-content: space-between;
    border-bottom: 2px solid #ddd;
    padding-bottom: 15px;
}
.inv-title {
    text-align: center;
    font-size: 28px;
    margin: 25px 0;
    font-weight: bold;
}
.inv-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 20px;
}
.inv-table {
    width: 100%;
    border-collapse: collapse;
}
.inv-table th,
.inv-table td {
    padding: 10px;
    border-bottom: 1px solid #eee;
}
.inv-table th {
    background: #f5f5f5;
}
.inv-declaration {
    margin-top: 20px;
    font-style: italic;
}
.inv-payment {
    margin-top: 20px;
}


/* === INVOICE TABLE (MODERN) === */
.table {
    border-collapse: separate;
    border-spacing: 0 10px;
}

.table thead th {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #6c757d;
    border: none;
}

.table tbody tr {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.05);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.table tbody tr:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.08);
}

.table tbody td {
    vertical-align: middle;
    border: none;
    padding: 14px 16px;
}

/* === ACTION BUTTONS === */
.btn-group .btn {
    border-radius: 8px;
    padding: 6px 10px;
}

.btn-outline-info:hover {
    background: #0dcaf0;
    color: #fff;
}

.btn-outline-warning:hover {
    background: #ffc107;
    color: #000;
}

.btn-outline-danger:hover {
    background: #dc3545;
    color: #fff;
}
</style>

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
                <input type="search" 
                       id="invoiceSearch" 
                       class="form-control search-input shadow-sm" 
                       placeholder="🔍 Search clients, invoices..." 
                       value="<?= h($q) ?>"
                       autocomplete="off">
                <button class="btn btn-sm position-absolute end-0 top-0 bottom-0 search-clear" style="right: 10px; border-radius: 0 8px 8px 0;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <a href="payment_invoice_gen.php" class="btn btn-primary px-3 position-relative">
                <i class="bi bi-plus-circle"></i> Create Invoice
            </a>
        </div>

    </div>

    <div class="card shadow-lg">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Avatar</th>
                        <th>Client</th>
                        <th>Invoice #</th>
                        <th>Amount</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                <?php if (!$invoices): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No invoices found
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td><?= renderAvatar($inv['picture'], $inv['client_name']) ?></td>
                        <td>
                            <strong><?= h($inv['client_name']) ?></strong><br>
                            <small class="text-muted"><?= h($inv['client_email']) ?></small>
                        </td>
                        <td>
                            <?= h($inv['invoice_num']) ?>

                            <span class="badge ms-2 <?= str_starts_with($inv['invoice_num'], 'SMC-') ? 'bg-primary' : 'bg-danger' ?>">
                                <?= str_starts_with($inv['invoice_num'], 'SMC-') ? 'SMC' : 'CSNK' ?>
                            </span>
                        </td>
                        <td class="fw-bold" style="color:#198754;font-size:15px;">
                            <?= formatCurrency($inv['total_amount']) ?>
                        </td>
                        <td><?= date('M j, Y', strtotime($inv['invoice_date'])) ?></td>
                        <td><?= date('M j, Y', strtotime($inv['due_date'])) ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button
                                    class="btn btn-outline-info view-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#viewModal"
                                    data-id="<?= $inv['id'] ?>"
                                    data-client="<?= h($inv['client_name']) ?>"
                                    data-email="<?= h($inv['client_email']) ?>"
                                    data-address="<?= h($inv['client_address']) ?>"
                                    data-invoice="<?= h($inv['invoice_num']) ?>"
                                    data-date="<?= $inv['invoice_date'] ?>"
                                    data-due="<?= $inv['due_date'] ?>"
                                    data-ref="<?= h($inv['reference_no']) ?>"
                                    data-total="<?= (float) $inv['total_amount'] ?>"
                                    data-applicants='<?= $inv['applicants_data'] ?>'
                                    data-pdf="<?= h($inv['pdf_filename']) ?>"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>

                                <button class="btn btn-outline-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editModal">
                                    <i class="bi bi-pencil"></i>
                                </button>

                                <button class="btn btn-outline-danger"
                                        onclick="softDeleteInvoice(<?= $inv['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                    <!-- DELETE TOAST -->
                    <div id="deleteToast" class="delete-toast hidden">
                        <div class="toast-body">
                            <div class="toast-text">
                                <strong>Invoice deleted</strong>
                                <div class="toast-sub">
                                    Undo available for <span id="toastCountdown">10</span>s
                                </div>
                            </div>

                            <div class="toast-actions">
                                <button id="undoBtn" class="toast-undo">UNDO</button>
                                <button id="closeToast" class="toast-close">✕</button>
                            </div>
                        </div>
                    </div>
                </tbody>
            </table>
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
                    <img id="logo-smc" src="../../resources/img/smcbrandname.png" height="90" class="d-none">

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
// Client-side search (fuzzy, instant, typo-tolerant)
let allInvoices = <?= json_encode($invoices, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let filteredInvoices = [...allInvoices];
let fuse;

document.addEventListener('DOMContentLoaded', function() {
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

    searchInput.addEventListener('input', function(e) {
        const query = e.target.value.trim();
        
        if (query.length === 0) {
            filteredInvoices = [...allInvoices];
        } else {
        const results = fuse.search(query);
        filteredInvoices = results.length ? results.map(r => r.item) : [];
        }
        
        renderInvoices(filteredInvoices);
        updateResultsCount(filteredInvoices.length);
        
        // Show/hide clear button
        document.querySelector('.search-clear').style.display = query ? 'block' : 'none';
    });
    
    // Clear search
    document.querySelector('.search-clear').addEventListener('click', function() {
        searchInput.value = '';
        filteredInvoices = [...allInvoices];
        renderInvoices(filteredInvoices);
        updateResultsCount(allInvoices.length);
        this.style.display = 'none';
    });
    
    // Initial render
    renderInvoices(allInvoices);
    updateResultsCount(allInvoices.length);
    
    function renderInvoices(invoices) {
        const tbody = document.querySelector('tbody');
        
        if (invoices.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7">
                        <div class="no-results">
                            <i class="bi bi-search"></i>
                            <h5>No invoices found</h5>
                            <p class="mb-0">Try adjusting your search terms</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        tbody.innerHTML = invoices.map(inv => `
            <tr>
                <td>${renderAvatar(inv.picture, inv.client_name)}</td>
                <td>
                    <strong>${escapeHtml(inv.client_name)}</strong><br>
                    <small class="text-muted">${escapeHtml(inv.client_email)}</small>
                </td>
                <td>
                    ${escapeHtml(inv.invoice_num)}
                    <span class="badge ms-2 ${inv.invoice_num.startsWith('SMC-') ? 'bg-primary' : 'bg-danger'}">
                        ${inv.invoice_num.startsWith('SMC-') ? 'SMC' : 'CSNK'}
                    </span>
                </td>
                <td class="fw-bold" style="color:#198754;font-size:15px;">
                    ${formatCurrency(inv.total_amount)}
                </td>
                <td>${new Date(inv.invoice_date).toLocaleDateString('en-PH')}</td>
                <td>${new Date(inv.due_date).toLocaleDateString('en-PH')}</td>
                <td>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info view-btn"
                                data-bs-toggle="modal" data-bs-target="#viewModal"
                                data-id="${inv.id}"
                                data-client="${escapeHtml(inv.client_name)}"
                                data-email="${escapeHtml(inv.client_email)}"
                                data-address="${escapeHtml(inv.client_address)}"
                                data-invoice="${escapeHtml(inv.invoice_num)}"
                                data-date="${inv.invoice_date}"
                                data-due="${inv.due_date}"
                                data-ref="${escapeHtml(inv.reference_no)}"
                                data-total="${parseFloat(inv.total_amount)}"
                                data-applicants='${inv.applicants_data}'
                                data-pdf="${escapeHtml(inv.pdf_filename)}">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-outline-warning edit-btn" data-bs-toggle="modal" data-bs-target="#editModal">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <button class="btn btn-outline-danger"
                                onclick="softDeleteInvoice(<?= $inv['id'] ?>)">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');

        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', viewInvoiceHandler);
        });
    }

    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', viewInvoiceHandler);
    });
    
    function updateResultsCount(count) {
        resultsCount.innerHTML = count === 1 ? 
            '<i class="bi bi-check-circle-fill text-success me-1"></i>1 result' : 
            `<i class="bi bi-check-circle-fill text-success me-1"></i>${count} results`;
    }
    
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
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
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
        document.getElementById('modal-company').textContent = isSMC

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
});


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

        // Final delete after 10s
        deleteTimer = setTimeout(() => {
            finalizeDelete(id);
        }, 10000);
    }

    function showDeleteToast() {
        const toast = document.getElementById('deleteToast');
        toast.classList.remove('hidden');

        document.getElementById('undoBtn').onclick = undoDelete;
        document.getElementById('closeToast').onclick = closeToast;
    }

    function undoDelete() {
        clearTimeout(deleteTimer);
        clearInterval(countdownTimer);

        // Restore row
        const row = document.querySelector(
            `button[onclick="softDeleteInvoice(${pendingDeleteId})"]`
        ).closest('tr');
        row.style.display = '';

        pendingDeleteId = null;
        closeToast();
    }

    function closeToast() {
        clearInterval(countdownTimer);
        document.getElementById('deleteToast').classList.add('hidden');
    }

    function finalizeDelete(id) {
        window.location.href = `payments_clients.php?action=delete&id=${id}`;
    }
</script>

<?php include '../includes/footer.php'; ?>