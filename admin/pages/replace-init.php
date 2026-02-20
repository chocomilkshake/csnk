<?php
// FILE: admin/pages/replace-init.php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$adminId     = isset($currentUser['id']) ? (int)$currentUser['id'] : null;

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
       || (isset($_REQUEST['ajax']) && (string)$_REQUEST['ajax'] === '1');

function json_out($ok, $payload = [], $http = 200) {
    http_response_code($http);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => (bool)$ok] + $payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) json_out(false, ['message' => 'Invalid method.'], 405);
    setFlashMessage('error', 'Invalid request method.');
    redirect('approved.php'); exit;
}

$originalId = isset($_POST['original_applicant_id']) ? (int)$_POST['original_applicant_id'] : 0;
$reason     = isset($_POST['reason'])     ? trim((string)$_POST['reason'])     : '';
$reportText = isset($_POST['report_text'])? trim((string)$_POST['report_text']): '';

if ($originalId <= 0 || $adminId === null) {
    if ($isAjax) json_out(false, ['message' => 'Missing required fields.'], 422);
    setFlashMessage('error', 'Missing required fields.');
    redirect('approved.php'); exit;
}

// Optional CSRF
if (!empty($_POST['csrf_token'])) {
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        if ($isAjax) json_out(false, ['message' => 'Invalid security token.'], 403);
        setFlashMessage('error', 'Invalid security token.');
        redirect('approved.php'); exit;
    }
}

// Upload attachments (optional)
$attachments = [];
if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
    $attachments = uploadMultipleFiles($_FILES['attachments'], REPLACEMENTS_UPLOAD_SUBDIR);
}

$applicant = new Applicant($database);
$replacementId = $applicant->createReplacementInit(
    $originalId,
    $reason,
    $reportText,
    $attachments,
    $adminId
);

if (!$replacementId) {
    if ($isAjax) json_out(false, ['message' => 'Failed to start replacement.'], 500);
    setFlashMessage('error', 'Failed to start replacement.');
    redirect('approved.php'); exit;
}

if ($isAjax) {
    json_out(true, [
        'message'         => 'Replacement request created.',
        'replacement_id'  => (int)$replacementId
    ]);
} else {
    setFlashMessage('success', 'Replacement request created. Select a replacement next.');
    redirect('approved.php');
}