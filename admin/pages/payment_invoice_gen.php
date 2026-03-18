<?php
session_start();

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../../lib/invlib/invoicr.php';

$db = new Database();
$conn = $db->getConnection();
if (!$conn) die('Database connection failed.');

$invoice_date = date('Y-m-d');
$reference_no = 'REF-' . date('Ymd') . '-' . rand(100000,999999);

$download_link = '';



$client_email   = trim($_POST['client_email'] ?? '');
    $client_address = trim($_POST['client_address'] ?? '');
    $due_date       = $_POST['due_date'] ?? '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {

    $client_name    = trim($_POST['client_name']);
    $business_unit_id = (int) ($_POST['business_unit_id'] ?? 0);

        // ✅ Set invoice prefix & template based on agency
    if ($business_unit_id == 2) {
        // SMC
        $invoice_prefix = 'SMC';
        $template_name  = 'smc';
    } else {
        // CSNK (default)
        $invoice_prefix = 'CSNK';
        $template_name  = 'csnk';
    }

    // ✅ Generate invoice number dynamically
    $invoice_num = $invoice_prefix . '-' . date('Ymd') . '-' . rand(100, 999);

    if ($business_unit_id <= 0) {
        setFlashMessage('error', 'Please select an agency before generating invoice.');
        header('Location: payment_invoice_gen.php');
        exit;
    }

    // ✅ existing validation
    if (!$client_name || empty($_POST['applicants'])) {
        setFlashMessage('error', 'Missing invoice details.');
        header('Location: payment_invoice_gen.php');
        exit;
    }

    // PDF generation continues here...

    if (!$client_name || empty($_POST['applicants'])) {
        setFlashMessage('error', 'Missing invoice details.');
        header('Location: payment_invoice_gen.php');
        exit;
    }

    $invoicr = new Invoicr();
    $invoicr->template($template_name);

    /* COMPANY */
    if ($business_unit_id == 2) {
        $companyName = 'SMC Agency';
        $companyLogo = "<img src='".__DIR__."/../../resources/img/SMC-LOGO.png'>";
    } else {
        $companyName = 'CSNK Agency';
        $companyLogo = "<img src='".__DIR__."/../../resources/img/CSNK-LOGO.png'>";
    }

    $invoicr->set('company', [
        $companyLogo,
        $companyName,
        'Unit 1 Eden Townhomes',
        'Pedro Gil Street, Manila',
        '091-0000-0000',
        'agency.com',
        'info@agency.com'
    ]);

    /* HEADER */
    $invoicr->add('head', ['Invoice #', $invoice_num]);
    $invoicr->add('head', ['Invoice Date', $invoice_date]);
    $invoicr->add('head', ['Due Date', $due_date]);
    $invoicr->add('head', ['Ref No', $reference_no]);

    /* BILL TO */
    $invoicr->set('billto', [
        $client_name,
        $client_email ?: 'N/A',
        $client_address ?: 'N/A'
    ]);

    /* ITEMS */
    $total = 0;
    foreach ($_POST['applicants'] as $app) {
        $amount = (float)$app['amount'];
        if ($amount <= 0) continue;

        $total += $amount;

        $invoicr->add('items', [
            $app['name'],
            ($app['start_date'] ?? '') . ' - ' . ($app['end_date'] ?? ''),
            $app['days'] ?? 0,
            '₱' . number_format($amount,2)
        ]);
    }

    if ($total <= 0) {
        setFlashMessage('error', 'Invoice has no valid items.');
        header('Location: payment_invoice_gen.php');
        exit;
    }

    $invoicr->add('totals', ['Total', '₱'.number_format($total,2)]);

    $invoicr->set('notes', [
        'I declare that all information contained in this invoice are certified true and correct.',
        'Issued by: CSNK Agency',
        'Payment via GCash or RCBC bank transfer.'
    ]);

    /* SAVE PDF */
$dir = $_SERVER['DOCUMENT_ROOT'] . '/csnk/uploads/invoices/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = $invoice_num . '.pdf';
    $filepath = $dir . $filename;

    $invoicr->outputPDF(3, $filepath);

    if (!file_exists($filepath)) {
        setFlashMessage('error', 'PDF generation failed.');
    } else {
        // Save to invoice_history
        $cleanApplicants = [];

        foreach ($_POST['applicants'] as $app) {

            // Skip empty rows
            if (empty(trim($app['name'] ?? ''))) {
                continue;
            }

            $cleanApplicants[] = [
                'name'        => trim($app['name']),
                'start_date' => $app['start_date'] ?? '',
                'end_date'   => $app['end_date'] ?? '',
                'days'       => (int) ($app['days'] ?? 0),
                'amount'     => (float) ($app['amount'] ?? 0),
            ];
        }

        $applicants_json = json_encode($cleanApplicants, JSON_UNESCAPED_UNICODE);
        
        $business_unit_id = (int) ($_POST['business_unit_id'] ?? 0);

        $stmt = $conn->prepare("
            INSERT INTO invoice_history (
                business_unit_id,
                reference_no,
                invoice_num,
                invoice_date,
                due_date,
                client_name,
                client_email,
                client_address,
                applicants_data,
                total_amount,
                pdf_filename
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssssds",
            $business_unit_id,
            $reference_no,
            $invoice_num,
            $invoice_date,
            $due_date,
            $client_name,
            $client_email,
            $client_address,
            $applicants_json,
            $total,
            $filename
        );
        $stmt->execute();
        
setFlashMessage('success', '✅ Invoice generated successfully and saved to history. <a href="../../uploads/invoices/' . $filename . '" target="_blank" download="' . $filename . '" class="alert-link">Download PDF</a>');
    }

    header('Location: payment_invoice_gen.php');
    exit;
}

if (!$conn) die('Database connection failed.');

$invoice_date = date('Y-m-d');
$reference_no = 'REF-' . date('Ymd') . '-' . str_pad(rand(100000,999999), 6, '0', STR_PAD_LEFT);

/* ================= FETCH CLIENTS ================= */
$clients = [];
$q = "
    SELECT cb.client_email,
           CONCAT(cb.client_first_name,' ',cb.client_last_name) AS client_name,
           cb.client_phone,
           cb.client_address,
           cb.business_unit_id
    FROM client_bookings cb
    JOIN business_units bu ON bu.id = cb.business_unit_id
    JOIN agencies ag ON ag.id = bu.agency_id
    WHERE cb.status IN ('submitted','confirmed')
";
$r = $conn->query($q);
while ($row = $r->fetch_assoc()) {

    $apps = [];
$s = $conn->prepare("
        SELECT a.id AS applicant_id,
               CONCAT(a.first_name, IF(a.middle_name IS NOT NULL AND a.middle_name != '', CONCAT(' ', a.middle_name), ''), ' ', a.last_name, IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')) AS name,
               a.email
        FROM applicants a
        JOIN client_bookings cb ON cb.applicant_id = a.id
        WHERE cb.client_email = ? AND a.status IN ('on_process','approved')
    ");


    $s->bind_param("s", $row['client_email']);
    $s->execute();
    $apps = $s->get_result()->fetch_all(MYSQLI_ASSOC);

    $row['applicants'] = $apps;
    $clients[] = $row;
}
?>

<?php include '../includes/header.php'; ?>


<div class="container-fluid py-4">

  <div class="row g-4 align-items-stretch">

    <!-- ================= LEFT: LIVE PREVIEW ================= -->
    <div class="col-lg-6">

      <div class="invoice-preview-wrapper h-100">
        <div class="invoice-preview-paper h-100">

          <!-- ✅ KEEP YOUR EXISTING LIVE PREVIEW CONTENT HERE -->
          <!-- HEADER -->
          <div class="inv-header">
            <div class="inv-logo-left">
              <img src="../resources/img/whychoose.png" alt="CSNK">
              <div class="inv-address">
                Unit 1 Eden Townhomes 2001 Eden Street corner<br>
                Pedro Gil Street Sta. Ana, Manila, Philippines
              </div>
            </div>
            <div class="inv-logo-right">
              <img src="../../resources/img/csnk-iconz.png" alt="CSNK" max-height="110px">
            </div>
          </div>

          <div class="inv-title">INVOICE</div>

          <div class="inv-meta">
            <div>
              <strong>Billed to:</strong><br>
              <span id="pv-client-name">Client’s Name</span><br>
              <span id="pv-client-email" class="muted">Client Email</span><br>
              <span id="pv-client-address" class="muted">Client’s Address</span>
            </div>
            <div class="inv-info">
              <div><strong>Invoice #</strong> <?= $invoice_num ?></div>
              <div><strong>Invoice Date</strong> <?= $invoice_date ?></div>
              <div><strong>Due Date</strong> <span id="pv-due-date">—</span></div>
              <div class="ref">Ref no: <?= $reference_no ?></div>
            </div>
          </div>

          <table class="inv-table">
            <thead>
              <tr>
    <th>Applicant Name</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>No. Days</th>
                <th class="right">Service fee</th>
              </tr>
            </thead>
            <tbody id="pv-items">
              <tr>
                <td colspan="5" class="empty">No applicants yet</td>
              </tr>
            </tbody>
<tfoot>
    <!-- separator line -->
    <tr>
        <td colspan="7" style="border-top: 2px solid #000;"></td>
    </tr>

    <!-- total row -->
    <tr>
        <td colspan="4" class="right" style="padding-top: 12px; font-weight: 600;">
            Total:
        </td>
        <td class="right" style="padding-top: 12px; font-weight: 700;">
            ₱<span id="pv-total">0.00</span>
        </td>
    </tr>
</tfoot>
          </table>

          <div class="inv-declaration">
            I declare that all information contained in this invoice are certified true and correct.
          </div>

          <div class="inv-payment">
            <strong>Issued By:</strong> CSNK Agency<br><br>
            <strong>Payment method:</strong><br>
            GCASH: 091‑0000‑0000<br>
            Bank Transfer: RCBC acc no: 1234‑1234‑1234‑1234
          </div>

        </div>
      </div>

    </div>


        <!-- RIGHT: INPUT FORM (col-lg-6) -->
        <div class="col-lg-6 ps-lg-2">
            <form method="POST">
                <input type="hidden" name="generate_invoice" value="1">
                <input type="hidden" name="business_unit_id" id="business_unit_id">
                
                <div class="card shadow-lg border-0 h-100 rounded-4 overflow-hidden">
                    <div class="card-header bg-success text-white border-0">
                        <h6 class="mb-0 fw-bold">
                            <i class="bi bi-gear-fill me-2"></i>Invoice Builder
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <!-- Client Info -->
                        <h6 class="fw-bold mb-3 pb-2 border-bottom">Client Details</h6>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Select Agency</label>
                            <select class="form-select form-control-lg" id="agency-select" onchange="filterClientsByAgency()">
                                <option value="">Select Agency</option>
                                <option value="1">CSNK</option>
                                <option value="2">SMC</option>
                            </select>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                            <label class="form-label small fw-semibold">Select Client <span class="badge bg-info">Auto-fill</span></label>
                                <select class="form-select form-control-lg"
                                    id="client-select"
                                    disabled
                                    onchange="loadClient(this)">
                                    <option value="">Select agency first</option>
                                    <?php foreach ($clients as $c): ?>
                                        <option value="<?= h($c['client_email']) ?>"
                                            data-name="<?= h($c['client_name']) ?>"
                                            data-address="<?= h($c['client_address']) ?>"
                                            data-apps="<?= htmlspecialchars(json_encode($c['applicants']), ENT_QUOTES, 'UTF-8') ?>"
                                            data-bu="<?= (int)$c['business_unit_id'] ?>">
                                            <?= h($c['client_name']) ?> (<?= count($c['applicants']) ?> applicants)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                    <div id="client-info" class="mt-2 p-3 bg-light rounded border small d-none">
                                    <strong id="client-name"></strong><br>
                                    <small class="text-muted" id="client-email"></small><br>
                                    <small class="text-muted" id="client-address"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control form-control-lg" oninput="updatePreview()">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Client Name</label>
                                <input type="text" name="client_name" id="client_name" class="form-control form-control-lg" oninput="updatePreview()">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Client Email</label>
                                <input type="email" name="client_email" id="client_email" class="form-control form-control-lg" placeholder="client@example.com" oninput="updatePreview()">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Client Address</label>
                                <textarea name="client_address" id="client_address" class="form-control" rows="3" placeholder="Complete billing address" oninput="updatePreview()"></textarea>
                            </div>
                        </div>

                        <!-- Applicant Items -->
                        <h6 class="fw-bold mb-3 pb-2 border-bottom">Salary Items <span class="badge bg-secondary ms-2" id="applicant-count">0</span></h6>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 35%">Applicant</th>
                                        <th style="width: 20%">Start Date</th>
                                        <th style="width: 20%">End Date</th>
                                        <th style="width: 10%" class="text-center">Days</th>
                                        <th style="width: 15%" class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="items">
                                    <tr>
                                        <td><input name="applicants[0][name]" class="form-control" placeholder="Applicant name"></td>
                                        <td><input name="applicants[0][start_date]" type="date" class="form-control start-date" oninput="calcDays(0)"></td>
                                        <td><input name="applicants[0][end_date]" type="date" class="form-control end-date" oninput="calcDays(0)"></td>
                                        <td class="text-center"><span class="days badge bg-secondary" data-index="0">0 days</span></td>
                                        <td><input name="applicants[0][amount]" class="form-control text-end amount" placeholder="0.00" oninput="calcTotal()"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary" onclick="addApplicant()">
                                <i class="bi bi-plus-circle me-2"></i>Add Applicant
                            </button>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-file-earmark-pdf me-2"></i>Generate Invoice PDF
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>

.invoice-preview-wrapper {
  background: #f5f5f5;
  padding: 24px;
}

.invoice-preview-paper {
  background: #fff;
  max-width: 820px;
  margin: auto;
  padding: 48px 40px;
  border: 1px solid #ddd;
  font-family: Arial, Helvetica, sans-serif;
  color: #222;
}

/* HEADER */
.inv-header {
  display: flex;
  justify-content: space-between;
  border-bottom: 2px solid #ccc;
  padding-bottom: 14px;
}
.inv-header img {
  max-height: 54px;
}
.inv-address {
  font-size: 12px;
  color: #555;
  margin-top: 6px;
}

/* TITLE */
.inv-title {
  text-align: center;
  font-size: 28px;
  letter-spacing: 4px;
  margin: 28px 0;
  font-weight: 700;
}

/* META */
.inv-meta {
  display: flex;
  justify-content: space-between;
  margin-bottom: 28px;
}
.inv-info {
  text-align: right;
  font-size: 13px;
}
.inv-info .ref {
  margin-top: 10px;
  font-size: 12px;
  color: #777;
}
.muted {
  color: #666;
}

/* TABLE */
.inv-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 28px;
}
.inv-table th {
  background: #f2f2f2;
  border-bottom: 2px solid #ccc;
  padding: 10px;
  font-size: 13px;
}
.inv-table td {
  padding: 10px;
  border-bottom: 1px solid #e0e0e0;
  font-size: 13px;
}
.inv-table .right {
  text-align: right;
}
.inv-table .empty {
  text-align: center;
  color: #999;
}

/* DECLARATION */
.inv-declaration {
  margin-top: 26px;
  font-size: 13px;
}

/* PAYMENT */
.inv-payment {
  margin-top: 30px;
  font-size: 13px;
}

.invoice-paper {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 2.5rem;
    max-width: 100%;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    box-shadow: 0 8px 32px rgba(0,0,0,0.1);
}
.invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 3px solid #dee2e6;
}
.logo-left img, .logo-right img { 
    max-height: 70px; 
    border-radius: 8px;
}
.logo-left {
    display: flex;
    flex-direction: column;
}
.company-address {
    margin-top: 0.75rem;
    font-size: 0.875rem;
    color: #6c757d;
    line-height: 1.4;
}
.invoice-title {
    text-align: center;
    font-size: 2rem;
    font-weight: bold;
    letter-spacing: 2px;
    color: #212529;
    margin: 2rem 0 1.5rem;
    text-transform: uppercase;
}
.invoice-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding: 1.5rem 0;
    border-bottom: 2px dashed #dee2e6;
}
.meta-right {
    text-align: right;
}
.meta-right div {
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}
.ref {
    margin-top: 0.5rem;
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
}
.invoice-table {
    width: 100%;
    margin-bottom: 2rem;
    border-collapse: collapse;
}
.invoice-table th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    padding: 1rem 0.75rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    border-bottom: 3px solid #dee2e6;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.invoice-table td {
    padding: 1rem 0.75rem;
    border-bottom: 1px solid #e9ecef;
    font-size: 0.95rem;
}
.invoice-table .right {
    text-align: right;
}
.invoice-table tfoot td {
    border-top: 3px double #dee2e6;
    padding-top: 1.25rem;
    font-weight: bold;
    font-size: 1.1rem;
}
.invoice-table .empty {
    text-align: center;
    color: #adb5bd;
    font-style: italic;
    padding: 3rem 1rem;
}
.declaration {
    margin-top: 2rem;
    padding: 1.5rem;
    background: #f8f9fa;
    border-left: 5px solid #0d6efd;
    font-size: 0.9rem;
    font-style: italic;
}
.payment-info {
    margin-top: 1.5rem;
    padding: 1.25rem;
    background: #f1f3f4;
    border-radius: 8px;
    font-size: 0.9rem;
}
.payment-info strong {
    color: #495057;
}
@media (max-width: 768px) {
    .invoice-paper {
        padding: 1.5rem;
    }
}
</style>

<script>

let applicantCounter = 0;

function loadClient(sel) {
    if (!document.getElementById('agency-select').value) {
        alert('Please select an agency first.');
        sel.value = '';
        return;
    }
    const opt = sel.options[sel.selectedIndex];
    if (!opt || opt.value === '') return;

    // CLIENT INFO
    document.getElementById('client_name').value = opt.dataset.name || '';
    document.getElementById('client_email').value = opt.value || '';
    document.getElementById('client_address').value = opt.dataset.address || '';
    document.getElementById('due_date').value = '';

    updatePreview();

    // SHOW CLIENT INFO
    const info = document.getElementById('client-info');
    info.classList.remove('d-none');
    document.getElementById('client-name').textContent = opt.dataset.name || '';
    document.getElementById('client-email').textContent = opt.value || '';
    document.getElementById('client-address').textContent = opt.dataset.address || '';

    // LOAD APPLICANTS
    let apps = [];
    try {
        apps = JSON.parse(opt.dataset.apps || '[]');
    } catch (e) {
        console.error('Invalid applicants JSON', e);
    }

    const tbody = document.getElementById('items');
    tbody.innerHTML = '';
    applicantCounter = 0;

    if (apps.length === 0) {
        addApplicant();
        updatePreviewItems();
        return;
    }

    apps.forEach(app => {
        const index = applicantCounter++;
        const row = tbody.insertRow();

        row.innerHTML = `
            <td>
                <input type="text"
                       class="form-control"
                       name="applicants[${index}][name]"
                       value="${app.name}"
                       readonly>
            </td>
            <td>
                <input type="date"
                       name="applicants[${index}][start_date]"
                       class="form-control start-date"
                       oninput="calcDays(${index})">
            </td>
            <td>
                <input type="date"
                       name="applicants[${index}][end_date]"
                       class="form-control end-date"
                       oninput="calcDays(${index})">
            </td>
            <td class="text-center">
                <span class="days badge bg-secondary" data-index="${index}">0 days</span>
                <input type="hidden"
                    name="applicants[${index}][days]"
                    class="days-input"
                    value="0">
            </td>
            <td>
                <input type="number"
                       name="applicants[${index}][amount]"
                       class="form-control text-end amount"
                       value="0"
                       oninput="calcTotal()">
            </td>
        `;
    });

    // ✅ THIS WAS MISSING
    updatePreviewItems();
    calcTotal();
}


function addApplicant() {
    const tbody = document.getElementById('items');
    const row = tbody.insertRow();
    const index = applicantCounter++;
    row.innerHTML = `
        <td><input name="applicants[${index}][name]" class="form-control" placeholder="Applicant name" oninput="calcTotal()"></td>
        <td><input name="applicants[${index}][start_date]" type="date" class="form-control start-date" oninput="calcDays(${index})"></td>
        <td><input name="applicants[${index}][end_date]" type="date" class="form-control end-date" oninput="calcDays(${index})"></td>
        <td class="text-center"><span class="days badge bg-secondary" data-index="${index}">0 days</span></td>
        <td><input name="applicants[${index}][amount]" class="form-control text-end amount" value="0" oninput="calcTotal()" step="100"></td>
    `;
    updateApplicantCount();
    calcTotal();
}

function updateApplicantCount() {
    const count = document.getElementById('items').rows.length;
    document.getElementById('applicant-count').textContent = count;
}

function calcTotal() {
    const amounts = document.querySelectorAll('.amount');
    let total = 0;
    amounts.forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    document.getElementById('pv-total').textContent = total.toLocaleString('en-PH', {minimumFractionDigits: 2});
    
    // Update preview items table
    updatePreviewItems();
}

function updatePreviewItems() {
    const previewBody = document.getElementById('pv-items');
    const rows = document.querySelectorAll('#items tr');
    previewBody.innerHTML = '';

    if (rows.length === 0) {
        previewBody.innerHTML = `
            <tr>
                <td colspan="5" class="empty">No applicants yet</td>
            </tr>`;
        return;
    }

    rows.forEach(row => {
        const name = row.querySelector('[name*="[name]"]')?.value || '';
        const start = row.querySelector('[name*="[start_date]"]')?.value || '—';
        const end = row.querySelector('[name*="[end_date]"]')?.value || '—';
        const days = row.querySelector('.days')?.textContent || '0 days';
        const amount = row.querySelector('[name*="[amount]"]')?.value || '0.00';

        if (!name) return;

        previewBody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${name}</td>
                <td>${start}</td>
                <td>${end}</td>
                <td class="text-center">${days}</td>
                <td class="right">₱${parseFloat(amount).toLocaleString()}</td>
            </tr>
        `);
    });
}

function filterClientsByAgency() {
    const agencyId = document.getElementById('agency-select').value;
    const clientSelect = document.getElementById('client-select');

    clientSelect.value = '';
    clientSelect.disabled = true;
    document.getElementById('client-info').classList.add('d-none');

    Array.from(clientSelect.options).forEach(opt => {
        if (opt.value === '') {
            opt.hidden = false;
            return;
        }
        opt.hidden = true;
        if (agencyId && opt.dataset.bu === agencyId) {
            opt.hidden = false;
        }
    });

    if (agencyId) {
        clientSelect.disabled = false;
        document.getElementById('business_unit_id').value = agencyId;
    }
}

function updatePreview() {
    // Client Name LIVE
    const nameEl = document.getElementById('client_name');
    if (nameEl) document.getElementById('pv-client-name').textContent = nameEl.value || 'Client Name';

    // Client Email LIVE
    const emailEl = document.getElementById('client_email');
    if (emailEl) document.getElementById('pv-client-email').textContent = emailEl.value || 'Client Email';

    // Client Address LIVE
    const addrEl = document.getElementById('client_address');
    if (addrEl) document.getElementById('pv-client-address').textContent = addrEl.value || 'Address';

    // Due Date LIVE
    const dueEl = document.getElementById('due_date');
    if (dueEl) document.getElementById('pv-due-date').textContent = dueEl.value || '—';

    calcTotal();
}

function calcDays(index) {
    const row = document.querySelector(`#items tr:nth-child(${index + 1})`);
    if (!row) return;

    const startInput = row.querySelector('.start-date');
    const endInput   = row.querySelector('.end-date');
    const daysSpan   = row.querySelector('.days');
    const daysInput  = row.querySelector('.days-input');

    if (!startInput || !endInput || !daysSpan || !daysInput) return;

    if (!startInput.value || !endInput.value) {
        daysSpan.textContent = '0 days';
        daysInput.value = 0;
        return;
    }

    const start = new Date(startInput.value);
    const end   = new Date(endInput.value);

    if (end < start) {
        daysSpan.textContent = '0 days';
        daysInput.value = 0;
        return;
    }

    const diffTime = end - start;
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24)) + 1;

    daysSpan.textContent = diffDays + ' days';
    daysInput.value = diffDays;
}

// Event listeners for manual edits
document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('input', function(e) {
        if (e.target.matches('#client_name, #client_email, #client_address, #due_date, .amount, .start-date, .end-date, [name*="[email]"]')) {
            updatePreview();
        }
    });
    
    updateApplicantCount();
    calcTotal();
});
</script>

<?php include '../includes/footer.php'; ?>