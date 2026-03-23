<?php
session_start();

/* ======================================================
   AJAX: Get Client Invoice History
====================================================== */
if (
    isset($_GET['get_client_history']) &&
    isset($_GET['email']) &&
    isset($_GET['tab'])
) {
    header('Content-Type: application/json');

    require_once '../includes/config.php';
    require_once '../includes/Database.php';

    $db = new Database();
    $conn = $db->getConnection();

    if (!$conn) {
        echo json_encode([]);
        exit;
    }

    $email = strtolower(trim((string)($_GET['email'] ?? '')));
    $tab   = strtoupper(trim((string)($_GET['tab'] ?? 'CSNK')));
    $likeInvoice = ($tab === 'SMC') ? 'SMC-%' : 'CSNK-%';

    $stmt = $conn->prepare("
        SELECT
            id,
            invoice_num,
            invoice_date,
            due_date,
            total_amount,
            CASE
                WHEN status = 'Paid' THEN 'Paid'
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
        WHERE LOWER(TRIM(client_email)) = ?
        AND company_type = ?
        ORDER BY invoice_date DESC
    ");

    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['error' => 'DB prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('ss', $email, $likeInvoice);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['error' => 'DB execute failed: ' . $stmt->error]);
        exit;
    }

    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (empty($rows)) {
        $fallback = $conn->prepare("
            SELECT
                id,
                invoice_num,
                invoice_date,
                due_date,
                total_amount,
                CASE
                    WHEN status = 'Paid' THEN 'Paid'
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
            WHERE LOWER(TRIM(client_email)) = ?
            ORDER BY invoice_date DESC
            LIMIT 200
        ");

        if ($fallback) {
            $fallback->bind_param('s', $email);
            $fallback->execute();
            $rows = $fallback->get_result()->fetch_all(MYSQLI_ASSOC);
        }
    }

    echo json_encode($rows);
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
    SELECT
        client_name,
        client_email,
        client_address,
        MAX(created_at) AS last_invoice_date,
        COUNT(*) AS total_invoices,
        SUM(total_amount) AS total_amount,
        SUM(status = 'Paid') AS paid_count,
        SUM(status != 'Paid') AS unpaid_count
    FROM invoice_history
    WHERE client_name LIKE ?
      AND company_type = ?
    GROUP BY client_name, client_email, client_address
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
:root{--primary:#0d6efd;--success:#198754;--warning:#ffc107;--danger:#dc3545;--muted:#6c757d;--shadow-sm:0 4px 20px rgba(0,0,0,.06);--shadow-md:0 8px 24px rgba(0,0,0,.08);--shadow-lg:0 12px 30px rgba(0,0,0,.15)}
.delete-toast{position:fixed;bottom:24px;right:24px;min-width:300px;background:#fff;border-radius:14px;box-shadow:var(--shadow-lg);z-index:9999;animation:slideUp .3s ease}
.delete-toast.hidden{display:none}
.toast-body{display:flex;justify-content:space-between;align-items:center;padding:16px 18px}
.toast-text strong{font-size:14px}
.toast-sub{font-size:12px;color:var(--muted)}
.toast-actions{display:flex;gap:10px}
.toast-undo,.toast-close{border:none;background:none;cursor:pointer}
.toast-undo{color:var(--primary);font-weight:700}
.toast-close{font-size:16px;color:#adb5bd}
.search-container{width:320px;max-width:100%}
.search-input{padding:12px 16px 12px 45px;border:2px solid #e9ecef;border-radius:16px;font-size:15px;background:rgba(255,255,255,.85);backdrop-filter:blur(16px);box-shadow:var(--shadow-sm);transition:.3s}
.search-input:focus{border-color:var(--primary);box-shadow:0 8px 32px rgba(13,110,253,.15)}
.search-clear{display:none;border:none;background:none;color:var(--muted)}
.invoice-tabs{display:flex;gap:12px}
.tab-btn{padding:10px 26px;border-radius:999px;font-weight:700;text-decoration:none;box-shadow:0 6px 18px rgba(0,0,0,.08);transition:.25s}
.tab-btn.inactive{opacity:.45;box-shadow:none}
.tab-csnk{background:linear-gradient(135deg,#c62828,#e53935);color:#fff}
.tab-smc{background:linear-gradient(135deg,#0b1c3d,#102a5e);color:#d4af37}
.table{border-collapse:separate;border-spacing:0 10px}
.table thead th{font-size:12px;text-transform:uppercase;color:var(--muted);border:none}
.table tbody tr{background:#fff;border-radius:12px;box-shadow:var(--shadow-md);transition:.2s}
.table tbody tr:hover{transform:translateY(-3px);box-shadow:var(--shadow-lg)}
.table tbody td{padding:14px 16px;vertical-align:middle;border:none}
.btn-group .btn{border-radius:8px;padding:6px 10px}
.btn-outline-info:hover{background:#0dcaf0;color:#fff}
.btn-outline-warning:hover{background:var(--warning);color:#000}
.btn-outline-danger:hover{background:var(--danger);color:#fff}
.invoice-preview-paper{background:#fff;padding:40px;font-family:Arial,Helvetica,sans-serif}
.inv-header{display:flex;justify-content:space-between;border-bottom:2px solid #ddd;padding-bottom:15px}
.inv-title{text-align:center;font-size:28px;margin:25px 0;font-weight:700}
.inv-meta{display:flex;justify-content:space-between;margin-bottom:20px}
.inv-table{width:100%;border-collapse:collapse}
.inv-table th,.inv-table td{padding:10px;border-bottom:1px solid #eee}
.inv-table th{background:#f5f5f5}
.inv-declaration,.inv-payment{margin-top:20px}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:none;opacity:1}}
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
                        <th>Total Invoices</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Unpaid</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>

                    <?php if (!$invoices): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No clients found
                        </td>
                    </tr>
                    <?php else: ?>

                    <?php foreach ($invoices as $inv): ?>

                    <tr>
                        <td><?= renderAvatar(null, $inv['client_name']) ?></td>

                        <td>
                            <strong><?= h($inv['client_name']) ?></strong><br>
                            <small class="text-muted"><?= h($inv['client_email']) ?></small>
                        </td>

                        <td class="fw-bold"><?= (int)$inv['total_invoices'] ?></td>

                        <td class="fw-bold text-success">
                            ₱<?= number_format($inv['total_amount'], 2) ?>
                        </td>

                        <td>
                            <span class="badge bg-success">
                                <?= (int)$inv['paid_count'] ?>
                            </span>
                        </td>

                        <td>
                            <span class="badge bg-warning text-dark">
                                <?= (int)$inv['unpaid_count'] ?>
                            </span>
                        </td>

                        <td>
                            <button class="btn btn-outline-secondary btn-sm"
                                onclick="openClientHistory(
                                    '<?= addslashes($inv['client_email']) ?>',
                                    '<?= addslashes($activeTab) ?>'
                                )">
                                <i class="bi bi-clock-history"></i> History
                            </button>
                        </td>
                    </tr>

                    <?php endforeach; ?>
                    <?php endif; ?>

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
    document.querySelector('.search-clear').addEventListener('click', function() {
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

function openClientHistory(clientEmail, tab) {
    const modal = new bootstrap.Modal(document.getElementById('historyModal'));
    modal.show();
    document.getElementById('historyClientName').textContent = clientEmail;
    const tbody = document.getElementById('historyTableBody');

    tbody.innerHTML = `
        <tr>
            <td colspan="5" class="text-center">
                <div class="spinner-border spinner-border-sm"></div>
                Loading invoice history...
            </td>
        </tr>
    `;
    fetch(
        'payments_clients.php?get_client_history=1' +
        '&email=' + encodeURIComponent(clientEmail) +
        '&tab=' + encodeURIComponent(tab)
    )

    .then(res => {
        if (!res.ok) {
            throw new Error('Network response was not ok (' + res.status + ')');
        }
        return res.json();
    })
    .then(data => {

        tbody.innerHTML = '';

        if (!Array.isArray(data) || data.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center text-muted">
                        No invoice history found for this client.
                    </td>
                </tr>
            `;
            return;
        }

        data.forEach(inv => {
            tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${inv.invoice_num}</td>

                <td>${inv.invoice_date ? new Date(inv.invoice_date).toLocaleDateString('en-PH') : '-'}</td>

                <td>${inv.due_date ? new Date(inv.due_date).toLocaleDateString('en-PH') : '-'}</td>

                <td>₱${parseFloat(inv.total_amount || 0).toLocaleString('en-PH')}</td>

                <td>
                    <span class="badge ${
                        inv.status === 'Paid'
                            ? 'bg-success'
                            : (inv.status && inv.status.toLowerCase() === 'overdue')
                            ? 'bg-danger'
                            : 'bg-warning text-dark'
                    }">
                        ${inv.status || 'Pending'}
                    </span>
                </td>

                <td>
                    <div class="btn-group btn-group-sm">

                        <!-- ✅ VIEW (OLD LIVE PREVIEW MODAL) -->
                        <button class="btn btn-outline-info view-btn"
                            data-bs-toggle="modal"
                            data-bs-target="#viewModal"
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

                        <!-- ✅ EDIT (SAME AS OLD) -->
                        <button class="btn btn-outline-warning"
                            onclick="editInvoiceFromHistory(${inv.id})">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <!-- ✅ DELETE (SAME AS OLD) -->
                        <button class="btn btn-outline-danger"
                            onclick="softDeleteInvoice(${inv.id})">
                            <i class="bi bi-trash"></i>
                        </button>

                    </div>
                </td>
            </tr>
            `);
        });

        // ✅ Attach live preview handler to newly added buttons
        tbody.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', viewInvoiceHandler);
        });

    })
    .catch(error => {
        console.error('Error loading invoice history:', error);
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center text-danger">
                    Error loading invoice history. Check console for details.
                </td>
            </tr>
        `;
    })
}

function viewInvoiceFromHistory(id) {
    // reuse existing modal logic or redirect
    window.location.href = 'payments_clients.php?view=' + id;
}

function editInvoiceFromHistory(id) {
    window.location.href = 'payment_invoice_edit.php?id=' + id;
}

</script>

<!-- ================= CLIENT INVOICE HISTORY MODAL ================= -->
<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    📜 Invoice History — <span id="historyClientName"></span>
                </h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Invoice Date</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody"></tbody>
                </table>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>