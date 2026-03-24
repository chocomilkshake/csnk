<?php
/* ======================================================
   XENDIT WEBHOOK HANDLER
   File: /admin/webhooks/xendit.php
====================================================== */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Database.php';

/* ======================================================
   BASIC SECURITY CHECKS
====================================================== */

// ✅ Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method Not Allowed';
    exit;
}

// ✅ Verify webhook token (Xendit header)
// ✅ Verify webhook token (SAFE for Apache + ngrok)
$headers = function_exists('getallheaders') ? getallheaders() : [];

$receivedToken =
    $headers['X-Callback-Token']
    ?? $headers['x-callback-token']
    ?? $headers['X-CALLBACK-TOKEN']
    ?? '';

if (!$receivedToken || trim($receivedToken) !== trim(XENDIT_WEBHOOK_TOKEN)) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

/* ======================================================
   READ RAW PAYLOAD
====================================================== */
$rawPayload = file_get_contents('php://input');
$data = json_decode($rawPayload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo 'Invalid JSON';
    exit;
}

/* ======================================================
   VALIDATE REQUIRED FIELDS
====================================================== */

// Xendit invoice payload essentials
$xenditInvoiceId = $data['id'] ?? null;
$status          = $data['status'] ?? null;

if (!$xenditInvoiceId || !$status) {
    http_response_code(422);
    echo 'Missing required fields';
    exit;
}

/* ======================================================
   MAP XENDIT STATUS → SYSTEM STATUS
====================================================== */

$statusMap = [
    'PAID'     => 'Paid',
    'EXPIRED'  => 'Expired',
    'FAILED'   => 'Failed',
    'PENDING'  => 'Pending'
];

$paymentStatus = $statusMap[$status] ?? 'Pending';

/* ======================================================
   DATABASE CONNECTION
====================================================== */

$db = new Database();
$conn = $db->getConnection();

if (!$conn) {
    http_response_code(500);
    echo 'Database connection failed';
    exit;
}

/* ======================================================
   FIND INVOICE BY XENDIT ID
====================================================== */

$stmt = $conn->prepare("
    SELECT id, payment_status 
    FROM invoice_history
    WHERE xendit_invoice_id = ?
    LIMIT 1
");
$stmt->bind_param("s", $xenditInvoiceId);
$stmt->execute();

$result = $stmt->get_result();
$invoice = $result->fetch_assoc();

if (!$invoice) {
    // ✅ Xendit invoice not found in DB
    http_response_code(404);
    echo 'Invoice not found';
    exit;
}

/* ======================================================
   PREVENT DUPLICATE UPDATES
====================================================== */

if ($invoice['payment_status'] === 'Paid') {
    // ✅ Already paid → acknowledge webhook
    http_response_code(200);
    echo 'Already processed';
    exit;
}

/* ======================================================
   UPDATE INVOICE STATUS
====================================================== */

if ($paymentStatus === 'Paid') {

    $update = $conn->prepare("
        UPDATE invoice_history
        SET payment_status = 'Paid',
            paid_at = NOW()
        WHERE xendit_invoice_id = ?
    ");
    $update->bind_param("s", $xenditInvoiceId);
    $update->execute();

} else {

    $update = $conn->prepare("
        UPDATE invoice_history
        SET payment_status = ?
        WHERE xendit_invoice_id = ?
    ");
    $update->bind_param("ss", $paymentStatus, $xenditInvoiceId);
    $update->execute();
}

/* ======================================================
   SUCCESS RESPONSE TO XENDIT
====================================================== */

http_response_code(200);
echo 'Webhook processed successfully';
exit;