<?php
// FILE: admin/pages/replace-init.php (MySQLi-only, FIXED)
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

const CSNK_AGENCY_CODE = 'csnk';

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$adminId     = isset($currentUser['id']) ? (int)$currentUser['id'] : null;

$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_REQUEST['ajax']) && (string)$_REQUEST['ajax'] === '1') ||
    (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1')
);

if ($isAjax) {
    header('Content-Type: application/json; charset=UTF-8');
}

function json_out($ok, $payload = [], $http = 200) {
    http_response_code($http);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => (bool)$ok] + $payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) json_out(false, ['message' => 'Invalid request method. Expected POST.'], 405);
    setFlashMessage('error', 'Invalid request method.');
    redirect('approved.php'); exit;
}

if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])
    || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    if ($isAjax) json_out(false, ['message' => 'Invalid security token. Please refresh the page and try again.'], 403);
    setFlashMessage('error', 'Invalid security token.');
    redirect('approved.php'); exit;
}

$originalId = isset($_POST['original_applicant_id']) ? (int)$_POST['original_applicant_id'] : 0;
$reason     = isset($_POST['reason'])      ? trim((string)$_POST['reason'])       : '';
$reportText = isset($_POST['report_text']) ? trim((string)$_POST['report_text'])  : '';

if ($originalId <= 0) {
    if ($isAjax) json_out(false, ['message' => 'Missing or invalid original applicant ID.'], 422);
    setFlashMessage('error', 'Missing required fields.');
    redirect('approved.php'); exit;
}
if ($adminId === null) {
    if ($isAjax) json_out(false, ['message' => 'Authentication error. Please log in again.'], 401);
    setFlashMessage('error', 'Authentication error. Please log in again.');
    redirect('approved.php'); exit;
}
if ($reason === '') {
    if ($isAjax) json_out(false, ['message' => 'Please select a reason for replacement.'], 422);
    setFlashMessage('error', 'Please select a reason.');
    redirect('approved.php'); exit;
}
if ($reportText === '') {
    if ($isAjax) json_out(false, ['message' => 'Please provide a report/note for the replacement.'], 422);
    setFlashMessage('error', 'Please provide a report/note.');
    redirect('approved.php'); exit;
}

// --- MySQLi connection ---
$conn = method_exists($database, 'getConnection') ? $database->getConnection() : null;
if (!($conn instanceof mysqli)) {
    if ($isAjax) json_out(false, ['message' => 'Database connection type not supported (expecting MySQLi).'], 500);
    setFlashMessage('error', 'DB connection type not supported (MySQLi required).');
    redirect('approved.php'); exit;
}

// Ensure original applicant is CSNK
$sqlGetAgencyByApplicant = "
    SELECT ag.code AS agency_code
    FROM applicants a
    JOIN business_units bu ON bu.id = a.business_unit_id
    JOIN agencies ag ON ag.id = bu.agency_id
    WHERE a.id = ?
    LIMIT 1
";
$s = $conn->prepare($sqlGetAgencyByApplicant);
if (!$s) {
    error_log('Prepare failed for agency check: ' . $conn->error);
    if ($isAjax) json_out(false, ['message' => 'Internal error (agency check).'], 500);
    setFlashMessage('error', 'Internal error (agency check).');
    redirect('approved.php'); exit;
}
$s->bind_param('i', $originalId);
$s->execute();
$r = $s->get_result();
$row = $r ? $r->fetch_assoc() : null;
$s->close();

if (!$row || strtolower((string)$row['agency_code']) !== CSNK_AGENCY_CODE) {
    if ($isAjax) json_out(false, ['message' => 'Operation blocked: original applicant is not CSNK.'], 403);
    setFlashMessage('error', 'Operation blocked: not CSNK.');
    redirect('approved.php'); exit;
}

// Upload attachments (optional)
$attachments = [];
if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
    $uploadDir = defined('REPLACEMENTS_UPLOAD_SUBDIR') ? REPLACEMENTS_UPLOAD_SUBDIR : 'replacements/';
    $attachments = uploadMultipleFiles($_FILES['attachments'], $uploadDir);
}

$applicant = new Applicant($database);
try {
    $replacementId = $applicant->createReplacementInit(
        $originalId,
        $reason,
        $reportText,
        $attachments,
        $adminId
    );

    if (!$replacementId) {
        if ($isAjax) json_out(false, ['message' => 'Failed to create replacement (only allowed for Approved applicants).'], 422);
        setFlashMessage('error', 'Failed to create replacement (allowed only for Approved applicants).');
        redirect('approved.php'); exit;
    }

    if ($isAjax) {
        json_out(true, [
            'message'        => 'Replacement request created successfully.',
            'replacement_id' => (int)$replacementId
        ]);
    } else {
        setFlashMessage('success', 'Replacement request created. Select a replacement next.');
        redirect('approved.php');
    }
} catch (Throwable $e) {
    error_log('Replace-init error: ' . $e->getMessage());
    if ($isAjax) json_out(false, ['message' => 'An internal error occurred. Please try again later.'], 500);
    setFlashMessage('error', 'An internal error occurred. Please try again.');
    redirect('approved.php'); exit;
}