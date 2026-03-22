<?php
session_start();

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
/* === INVOICE TABS (MODERN) === */
.invoice-tabs {
    display: flex;
    gap: 12px;
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

        <!-- RIGHT: ADD INVOICE BUTTON -->
        <a href="payment_invoice_gen.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Invoice
        </a>

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

                                <a href="payments_clients.php?action=delete&id=<?= $inv['id'] ?>"
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Delete this invoice?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

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
                <h5 class="modal-title">📄 Invoice Preview</h5>
                <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <div class="invoice-preview-paper">

                    <div class="inv-header">
                        <div>
                            <img src="../resources/img/whychoose.png" height="50"><br>
                            <small>Unit 1 Eden Townhomes<br>Pedro Gil Street, Manila</small>
                        </div>
                        <img src="../../resources/img/csnk-iconz.png" height="80">
                    </div>

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
                        <strong>Issued By:</strong> CSNK Agency
                    </div>

        document.getElementById('pv-invoice-date').textContent = new Date(d.date).toLocaleDateString('en-PH');
        document.getElementById('pv-due-date').textContent = new Date(d.due).toLocaleDateString('en-PH');
        document.getElementById('pv-ref-no').textContent = d.ref;
        document.getElementById('pv-total').textContent = 
            parseFloat(d.total).toLocaleString('en-PH', { minimumFractionDigits: 2 });

        const tbody = document.getElementById('pv-items');
        tbody.innerHTML = '<tr><td colspan="4" class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Loading...</td></tr>';

        try {
            // Fetch enriched applicants via AJAX
            const response = await fetch('payments_clients.php?get_invoice_applicants=1&id=' + d.id)
            const apps = await response.json();
            
            tbody.innerHTML = '';
            
            if (!apps || apps.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted py-3">No applicants assigned</td></tr>';
            } else {
                apps.forEach(app => {
                    tbody.insertAdjacentHTML('beforeend', `
                        <tr>
                            <td>${app.name || 'Unknown'}</td>
                            <td>${app.start_date || ''} - ${app.end_date || ''}</td>
                            <td class="text-center">${Number(app.days) > 0 ? app.days : 0}</td>
                            <td class="text-end fw-semibold">
                                ₱${parseFloat(app.amount || 0).toLocaleString('en-PH', {minimumFractionDigits: 2})}
                            </td>
                        </tr>
                    `);
                });
            }
        } catch (error) {
            console.error('Failed to load applicants:', error);
            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger py-3">Failed to load applicants</td></tr>';
        }

        // Setup PDF download
        document.getElementById('downloadPdfBtn').onclick = () => {
            window.open('../../uploads/invoices/' + currentInvoiceData.pdf, '_blank');
        };
    });
});
</script>

<?php include '../includes/footer.php'; ?>