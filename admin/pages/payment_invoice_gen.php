<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/invoice_mailer.php';
require_once __DIR__ . '/../../lib/invlib/invoicr.php';

// Prevent clickjacking + cross-origin iframe errors
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-ancestors 'self'");

// Normalize URL path to avoid malformed //csnk paths
$reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (strpos($reqPath, '//') !== false) {
    $cleanPath = preg_replace('#/+#', '/', $reqPath);
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    header('Location: ' . $scheme . '://' . $host . $cleanPath);
    exit;
}

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


function createXenditInvoice(array $data)
{
    $url = XENDIT_API_URL . '/v2/invoices';

    $payload = json_encode([
        'external_id' => $data['reference_no'],
        'amount' => $data['amount'],
        'payer_email' => $data['email'],
        'description' => $data['description'],
        'currency' => 'PHP',

        // ✅ APP_URL already contains /admin
        'success_redirect_url' => APP_URL . '/pages/payments_success.php',
        'failure_redirect_url' => APP_URL . '/pages/payments_failed.php',
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(XENDIT_SECRET_KEY . ':'),
        ],
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        throw new Exception('Xendit CURL Error: ' . curl_error($ch));
    }

    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode >= 400) {
        throw new Exception('Xendit API Error: ' . ($result['message'] ?? 'Unknown error'));
    }

    return $result;
}

// POST HANDLER BLOCK
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_invoice'])) {
        session_start();

    if (!empty($_SESSION['invoice_processing'])) {
        setFlashMessage('warning', 'Invoice is already being processed.');
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
        exit;
    }

    $_SESSION['invoice_processing'] = true;
    unset($_SESSION['invoice_processing']);

    // ================= BASIC INPUTS =================
    $client_name       = trim($_POST['client_name']);
    $business_unit_id  = (int) ($_POST['business_unit_id'] ?? 0);
    $company_type      = ($business_unit_id == 2) ? 'SMC' : 'CSNK';

    // ✅ STEP 3 — READ client_booking_id HERE (VERY IMPORTANT)
    $booking_id = (int) ($_POST['client_booking_id'] ?? 0);

    if ($booking_id <= 0) {
        setFlashMessage('error', 'Client booking not selected.');
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
        exit;
    }

    // ================= AGENCY VALIDATION =================
    if ($business_unit_id <= 0) {
        setFlashMessage('error', 'Please select an agency before generating invoice.');
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
        exit;
    }

    if (!$client_name || empty($_POST['applicants'])) {
        setFlashMessage('error', 'Missing invoice details.');
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
        exit;
    }

    // ================= INVOICE TEMPLATE SETUP =================
    if ($business_unit_id == 2) {
        // SMC
        $invoice_prefix = 'SMC';
        $template_name  = 'smc';
    } else {
        // CSNK
        $invoice_prefix = 'CSNK';
        $template_name  = 'csnk';
    }

    // ✅ Generate invoice number
    $invoice_num = $invoice_prefix . '-' . date('Ymd') . '-' . rand(100, 999);

    // ================= INIT PDF =================
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
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
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
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
        exit;
    }

    // ✅ PDF exists → continue saving invoice
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

        $check = $conn->prepare("
            SELECT id FROM invoice_history
            WHERE reference_no = ?
            LIMIT 1
            ");
            $check->bind_param("s", $reference_no);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
            setFlashMessage(
                'warning',
                'This invoice was already generated. Please refresh the page.'
            );
            header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
            exit;
            }

        $stmt = $conn->prepare("
            INSERT INTO invoice_history (
                business_unit_id,
                client_booking_id,
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "iissssssssdss",
            $business_unit_id,
            $booking_id,
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

        // ✅ CREATE XENDIT INVOICE
        try {
            $xenditInvoice = createXenditInvoice([
                'reference_no' => $reference_no,
                'amount'       => $total,
                'email'        => $client_email,
                'description'  => "Invoice {$invoice_num} - {$client_name}",
            ]);

            $xendit_invoice_id = $xenditInvoice['id'];
            $payment_link      = $xenditInvoice['invoice_url'];

            // ✅ UPDATE invoice_history WITH XENDIT DATA
            $upd = $conn->prepare("
                UPDATE invoice_history
                SET xendit_invoice_id = ?,
                    payment_link = ?,
                    payment_status = 'Pending'
                WHERE invoice_num = ?
            ");
            $upd->bind_param("sss", $xendit_invoice_id, $payment_link, $invoice_num);
            $upd->execute();

        } catch (Exception $e) {
            setFlashMessage(
                'warning',
                'Invoice saved but Xendit invoice creation failed: ' . $e->getMessage()
            );
        }

        // ✅ EMAIL HANDLING (ASYNC-LIKE, NON-BLOCKING)
        if (filter_var($client_email, FILTER_VALIDATE_EMAIL)) {

            // Send email AFTER response is finished (prevents slow UI & double click panic)
            register_shutdown_function(function () use (
                $client_email,
                $client_name,
                $invoice_num,
                $filepath,
                $company_type,
                $payment_link
            ) {
                try {
                    sendInvoiceEmail(
                        $client_email,
                        $client_name,
                        $invoice_num,
                        $filepath,
                        $company_type,
                        $payment_link
                    );
                } catch (Throwable $e) {
                    error_log('Invoice email failed: ' . $e->getMessage());
                }
            });

            setFlashMessage(
                'success',
                '✅ Invoice generated and is being emailed to the client.'
            );

        } else {
            setFlashMessage(
                'warning',
                '⚠️ Invoice generated and saved, but client email is invalid.'
            );
        }

        // ✅ RELEASE SESSION LOCK (if you added session protection)
        unset($_SESSION['invoice_processing']);

        // ✅ IMMEDIATE REDIRECT (FAST RESPONSE)
        header('Location: ' . APP_URL . '/pages/payment_invoice_gen.php');
        exit;
    }

    /* ================= FETCH CLIENTS ================= */
    $clients = [];
    $q = "
        SELECT
            cb.id AS booking_id,
            cb.client_email,
            CONCAT(cb.client_first_name,' ',cb.client_last_name) AS client_name,
            cb.client_phone,
            cb.client_address,
            cb.business_unit_id
        FROM client_bookings cb
        WHERE cb.status IN ('submitted','confirmed','on_process','approved')
        ORDER BY cb.client_last_name, cb.client_first_name
            ";
    $r = $conn->query($q);
    while ($row = $r->fetch_assoc()) {
        $apps = get_client_applicants($conn, (int)$row['booking_id']);
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

                        </tbody>
                        <tfoot>
                            <tr class="total">
                                <td colspan="4" class="right">Total</td>
                                <td class="right">₱<span id="smc-total">0.00</span></td>
                            </tr>
                        </tfoot>
                    </table>

                    <div class="note">
                        I declare that all information contained in this invoice are certified true and correct.
                    </div>

                    <div class="payment">
                        <strong>Issued By:</strong> SMC Agency<br><br>
                    </div>

                </div>
            </div>
        </div>


        <!-- RIGHT: INPUT FORM (col-lg-6) -->
        <div class="col-lg-6 ps-lg-2">
            <form method="POST">
                <input type="hidden" name="generate_invoice" value="1">
                <input type="hidden" name="business_unit_id" id="business_unit_id">
                <input type="hidden" name="client_booking_id" id="client_booking_id">

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
                            <select class="form-select form-control-lg" id="agency-select" onchange="filterClientsByAgency(); updateInvoiceNumPreview();">
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
                                        <option
                                            value="<?= (int)$c['booking_id'] ?>"
                                            data-email="<?= h($c['client_email']) ?>"
                                            data-name="<?= h($c['client_name']) ?>"
                                            data-address="<?= h($c['client_address']) ?>"
                                            data-apps="<?= htmlspecialchars(json_encode($c['applicants']), ENT_QUOTES, 'UTF-8') ?>"
                                            data-bu="<?= (int)$c['business_unit_id'] ?>"
                                        >
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
                                <input type="date"
                                    name="due_date"
                                    id="due_date"
                                    class="form-control form-control-lg"
                                    min="<?= date('Y-m-d') ?>"
                                    oninput="updatePreview()">
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
                                <button type="submit"
                                        class="btn btn-success btn-lg"
                                        id="generateBtn">
                                <span class="btn-text">
                                    <i class="bi bi-file-earmark-pdf me-2"></i>
                                    Generate Invoice PDF
                                </span>
                                <span class="btn-loading d-none">
                                    <span class="spinner-border spinner-border-sm me-2"></span>
                                    Generating…
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .back-btn {
        font-weight: 600;
        font-size: 14px;
        text-decoration: none !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 1050;
        backdrop-filter: blur(12px);
    }
    .back-btn:hover {
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 12px 40px rgba(13, 110, 253, 0.4) !important;
        text-decoration: none !important;
    }
    .back-btn i {
        transition: transform 0.2s ease;
    }
    .back-btn:hover i {
        transform: translateX(-3px);
    }
    .back-btn:hover {
        background: #fff;
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        color: #212529;
        text-decoration: none;
    }
    .back-btn:active {
        transform: translateY(0);
    }
    .back-btn i {
        font-size: 16px;
    }
    @media (max-width: 768px) {
        .back-btn {
            padding: 10px 16px;
            font-size: 13px;
            margin-right: 1rem;
        }
    }

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
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .invoice-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 3px solid #dee2e6;
    }

    .logo-left img,
    .logo-right img {
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
        document.getElementById('client_booking_id').value = opt.value;
        if (!opt || opt.value === '') return;
        // CLIENT INFO
        document.getElementById('client_name').value = opt.dataset.name || '';
        document.getElementById('client_email').value = opt.dataset.email || '';
        document.getElementById('client_address').value = opt.dataset.address || '';
        document.getElementById('due_date').value = '';

        updatePreview();

        // SHOW CLIENT INFO
        const info = document.getElementById('client-info');
        info.classList.remove('d-none');
        document.getElementById('client-name').textContent = opt.dataset.name || '';
        document.getElementById('client-email').textContent = opt.dataset.email || '';
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

    document.addEventListener('DOMContentLoaded', () => {
        const dueDate = document.getElementById('due_date');
        if (dueDate) {
            const today = new Date().toISOString().split('T')[0];
            dueDate.setAttribute('min', today);
        }
    });

    
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('form');
        const btn  = document.getElementById('generateBtn');

        form.addEventListener('submit', () => {
        btn.disabled = true;
        btn.querySelector('.btn-text').classList.add('d-none');
        btn.querySelector('.btn-loading').classList.remove('d-none');
        });
    });

    const modal = new bootstrap.Modal(
        document.getElementById('actionModal')
        );
    modal.show();
</script>

<?php include '../includes/footer.php'; ?>