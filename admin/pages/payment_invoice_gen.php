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
$reference_no = 'REF-' . date('Ymd') . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

$preview_agency = 'csnk'; // default
$invoice_num = 'CSNK-' . date('m-d-Y') . '-' . rand(100, 999);

$download_link = '';



$client_email   = trim($_POST['client_email'] ?? '');
$client_address = trim($_POST['client_address'] ?? '');
$due_date       = $_POST['due_date'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {

    $client_name    = trim($_POST['client_name']);
    $business_unit_id = (int) ($_POST['business_unit_id'] ?? 0);
    $company_type = ($business_unit_id == 2) ? 'SMC' : 'CSNK';

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
        $companyLogo = "<img src='" . __DIR__ . "/../../resources/img/SMC-LOGO.png'>";
    } else {
        $companyName = 'CSNK Agency';
        $companyLogo = "<img src='" . __DIR__ . "/../../resources/img/CSNK-LOGO.png'>";
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
            '₱' . number_format($amount, 2)
        ]);
    }

    if ($total <= 0) {
        setFlashMessage('error', 'Invoice has no valid items.');
        header('Location: payment_invoice_gen.php');
        exit;
    }

    $invoicr->add('totals', ['Total', '₱' . number_format($total, 2)]);

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
                pdf_filename,
                company_type
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssssdss",
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
            $filename,
            $company_type
        );
        $stmt->execute();

        setFlashMessage('success', '✅ Invoice generated successfully and saved to history. <a href="../../uploads/invoices/' . $filename . '" target="_blank" download="' . $filename . '" class="alert-link">Download PDF</a>');
    }

    header('Location: payment_invoice_gen.php');
    exit;
}

if (!$conn) die('Database connection failed.');

$invoice_date = date('Y-m-d');
$reference_no = 'REF-' . date('Ymd') . '-' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);

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


<div class="container-fluid py-4" style="position: relative;">
    <a href="payments_clients.php" class="back-btn position-absolute top-20 end-0 me-4 mt-3 p-3 text-white shadow-lg rounded-pill bg-primary" title="← Back to Clients">
        Back
    </a>

    <div class="row g-4 align-items-stretch">

        <!-- ================= LEFT: LIVE PREVIEW ================= -->
        <div class="col-lg-6">

            <div class="invoice-preview-wrapper h-100">
                <div class="invoice-preview-paper h-100 template-csnk" id="preview-csnk">
                    <style>
                        /* CSNK Template - default */
                        .template-csnk .inv-header {
                            display: flex;
                            justify-content: space-between;
                            border-bottom: 2px solid #ccc;
                            padding-bottom: 14px;
                        }

                        .template-csnk .inv-header img {
                            max-height: 54px;
                        }

                        .template-csnk .inv-address {
                            font-size: 12px;
                            color: #555;
                            margin-top: 6px;
                        }

                        .template-csnk .inv-title {
                            text-align: center;
                            font-size: 28px;
                            letter-spacing: 4px;
                            margin: 28px 0;
                            font-weight: 700;
                        }

                        .template-csnk .inv-table th {
                            background: #f2f2f2;
                            border-bottom: 2px solid #ccc;
                            padding: 10px;
                            font-size: 13px;
                        }

                        .template-csnk .inv-table .right {
                            text-align: right;
                        }
                    </style>


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
                            <div id="pv-invoice-num"><strong>Invoice #</strong> <?= $invoice_num ?></div>
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
                <div class="invoice-preview-paper h-100 template-smc d-none" id="preview-smc">
                    <style>
                        /* ================= SMC LIVE PREVIEW ================= */
                        .template-smc {
                            font-family: DejaVuSans, Arial, Helvetica, sans-serif;
                            font-size: 14px;
                        }

                        .template-smc .header {
                            width: 100%;
                            border-bottom: 2px solid #003366;
                            margin-bottom: 18px;
                        }

                        .template-smc .header td {
                            vertical-align: top;
                        }

                        .template-smc .logo-main {
                            height: 65px;
                        }

                        .template-smc .logo-icon {
                            height: 100px;
                        }

                        .template-smc .company-name {
                            font-size: 18px;
                            font-weight: bold;
                            color: #003366;
                            margin-top: 6px;
                        }

                        .template-smc .address {
                            font-size: 12.5px;
                        }

                        .template-smc .title {
                            text-align: center;
                            font-size: 14px;
                            font-weight: bold;
                            letter-spacing: 4px;
                            margin: 20px 0;
                        }

                        .template-smc .meta {
                            width: 100%;
                            margin-bottom: 16px;
                        }

                        .template-smc .meta-right {
                            text-align: right;
                        }

                        .template-smc .items {
                            width: 100%;
                            border-collapse: collapse;
                        }

                        .template-smc .items th {
                            background: #e6f0ff;
                            border-bottom: 2px solid #003366;
                            padding: 8px;
                        }

                        .template-smc .items td {
                            padding: 8px;
                            border-bottom: 1px solid #ccc;
                        }

                        .template-smc .right {
                            text-align: right;
                        }

                        .template-smc .center {
                            text-align: center;
                        }

                        .template-smc .total td {
                            border-top: 2px solid #003366;
                            font-weight: bold;
                        }

                        .template-smc .note {
                            margin-top: 18px;
                            font-style: italic;
                        }

                        .template-smc .payment {
                            margin-top: 18px;
                        }
                    </style>

                    <!-- ================= HEADER ================= -->
                    <table class="header">
                        <tr>
                            <td width="70%">
                                <img src="../../resources/img/smcbrandname.png" class="logo-main"><br>
                                <div class="company-name">SMC Agency</div>
                                <div class="inv-address">
                                    Unit 1 Eden Townhomes 2001 Eden Street corner<br>
                                    Pedro Gil Street Sta. Ana, Manila, Philippines
                                </div>
                            </td>
                            <td width="30%" class="right">
                                <img src="../../resources/img/smc.png" class="logo-icon">
                            </td>
                        </tr>
                    </table>

                    <!-- ================= TITLE ================= -->
                    <div class="title">SMC MANPOWER AGENCY PHILIPPINES CO. (INVOICE)</div>

                    <!-- ================= META ================= -->
                    <table class="meta">
                        <tr>
                            <td width="60%">
                                <strong>Billed To:</strong><br>
                                <span id="smc-client-name">Client Name</span><br>
                                <span id="smc-client-email">Client Email</span><br>
                                <span id="smc-client-address">Client Address</span>
                            </td>
                            <td width="40%" class="meta-right">
                                <div><strong>Invoice #:</strong> <span id="smc-invoice-num"></span></div>
                                <div><strong>Invoice Date:</strong> <?= $invoice_date ?></div>
                                <div><strong>Due Date:</strong> <span id="smc-due-date">—</span></div>
                                <div>Ref No: <?= $reference_no ?></div>
                            </td>
                        </tr>
                    </table>

                    <!-- ================= ITEMS ================= -->
                    <table class="items">
                        <thead>
                            <tr>
                                <th>Applicant Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>No. Days</th>
                                <th class="right">Service Fee</th>

                            </tr>
                        </thead>
                        <tbody id="smc-items">
                            <tr>
                                <td colspan="7" class="empty">No applicants yet</td>
                            </tr>

        text-align: center;
            </td>
            <td>
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
        document.getElementById('pv-total').textContent = total.toLocaleString('en-PH', {
            minimumFractionDigits: 2
        });

        // Update preview items table
        updatePreviewItems();
    }

    function updatePreviewItems() {
        const csnkBody = document.getElementById('pv-items');
        const smcBody = document.getElementById('smc-items');

        const rows = document.querySelectorAll('#items tr');

        csnkBody.innerHTML = '';
        smcBody.innerHTML = '';

        let hasItems = false;

        rows.forEach(row => {
            const nameInput = row.querySelector('[name*="[name]"]');
            const startInput = row.querySelector('[name*="[start_date]"]');
            const endInput = row.querySelector('[name*="[end_date]"]');
            const daysSpan = row.querySelector('.days');
            const amtInput = row.querySelector('[name*="[amount]"]');

            if (!nameInput || !nameInput.value.trim()) return;

            hasItems = true;

            const name = nameInput.value;
            const start = startInput?.value || '—';
            const end = endInput?.value || '—';
            const days = parseInt(daysSpan?.textContent) || 0;
            const amt = parseFloat(amtInput?.value || 0);

            /* ===== CSNK ROW ===== */
            csnkBody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>${name}</td>
                    <td>${start}</td>
                    <td>${end}</td>
                    <td class="text-center">${days}</td>
                    <td class="right">₱${amt.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                </tr>
            `);

            /* ===== SMC ROW ===== */
            smcBody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>${name}</td>
                    <td>${start}</td>
                    <td>${end}</td>
                    <td class="center">${days}</td>
                    <td class="right">₱${amt.toLocaleString('en-PH', { minimumFractionDigits: 2 })}</td>
                </tr>
            `);
        });

        if (!hasItems) {
            csnkBody.innerHTML = `
            <tr>
                <td colspan="5" class="empty">No applicants yet</td>
            </tr>
        `;

            smcBody.innerHTML = `
            <tr>
                <td colspan="5" class="center">No applicants yet</td>
            </tr>
            `;
        }

        // ✅ Update totals on BOTH previews
        document.getElementById('smc-total').textContent =
            document.getElementById('pv-total').textContent;
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
        updateInvoiceNumPreview();
    }

    function updateInvoiceNumPreview() {
        const agency = document.getElementById('agency-select').value;

        document.getElementById('preview-csnk').classList.add('d-none');
        document.getElementById('preview-smc').classList.add('d-none');

        if (!agency) return;

        const prefix = agency === '2' ? 'SMC' : 'CSNK';
        const today = new Date();
        const dateStr = today.toISOString().slice(0, 10).replace(/-/g, '');

        const rand = Math.floor(Math.random() * 900) + 100;
        const invoiceNum = `${prefix}-${dateStr}-${rand}`;

        if (agency === '2') {
            document.getElementById('preview-smc').classList.remove('d-none');
            document.getElementById('smc-invoice-num').textContent = invoiceNum;
        } else {
            document.getElementById('preview-csnk').classList.remove('d-none');
            document.getElementById('pv-invoice-num').innerHTML = `<strong>Invoice #</strong> ${invoiceNum}`;
        }
    }

    function updatePreview() {
        const name = document.getElementById('client_name').value || 'Client Name';
        const email = document.getElementById('client_email').value || 'Client Email';
        const addr = document.getElementById('client_address').value || 'Client Address';
        const due = document.getElementById('due_date').value || '—';

        /* ===== CSNK PREVIEW ===== */
        document.getElementById('pv-client-name').textContent = name;
        document.getElementById('pv-client-email').textContent = email;
        document.getElementById('pv-client-address').textContent = addr;
        document.getElementById('pv-due-date').textContent = due;

        /* ===== SMC PREVIEW ===== */
        document.getElementById('smc-client-name').textContent = name;
        document.getElementById('smc-client-email').textContent = email;
        document.getElementById('smc-client-address').textContent = addr;
        document.getElementById('smc-due-date').textContent = due;

        calcTotal();
    }

    function calcDays(index) {
        const row = document.querySelector(`#items tr:nth-child(${index + 1})`);
        if (!row) return;

        const startInput = row.querySelector('.start-date');
        const endInput = row.querySelector('.end-date');
        const daysSpan = row.querySelector('.days');
        const daysInput = row.querySelector('.days-input');

        if (!startInput || !endInput || !daysSpan || !daysInput) return;

        if (!startInput.value || !endInput.value) {
            daysSpan.textContent = '0 days';
            daysInput.value = 0;
            return;
        }

        const start = new Date(startInput.value);
        const end = new Date(endInput.value);

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
            if (
                e.target.matches(
                    '#client_name, #client_email, #client_address, #due_date, ' +
                    '.amount, .start-date, .end-date'
                )
            ) {
                updatePreview();
            }
        });

        updateApplicantCount();
        calcTotal();
    });
</script>

<?php include '../includes/footer.php'; ?>