<?php
// FILE: admin/pages/replace-assign.php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

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

$replacementId = isset($_POST['replacement_id']) ? (int)$_POST['replacement_id'] : 0;
$candidateId   = isset($_POST['replacement_applicant_id']) ? (int)$_POST['replacement_applicant_id'] : 0;

// Optional CSRF
if (!empty($_POST['csrf_token'])) {
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        if ($isAjax) json_out(false, ['message' => 'Invalid security token.'], 403);
        setFlashMessage('error', 'Invalid security token.');
        redirect('approved.php'); exit;
    }
}

if ($replacementId <= 0 || $candidateId <= 0) {
    if ($isAjax) json_out(false, ['message' => 'Missing fields.'], 422);
    setFlashMessage('error', 'Missing fields.');
    redirect('approved.php'); exit;
}

$applicant = new Applicant($database);
$ok = $applicant->assignReplacement($replacementId, $candidateId, (int)($_SESSION['admin_id'] ?? 0));

if (!$ok) {
    if ($isAjax) json_out(false, ['message' => 'Failed to assign replacement.'], 500);
    setFlashMessage('error', 'Failed to assign replacement.');
    redirect('approved.php'); exit;
}

if ($isAjax) {
    json_out(true, ['message' => 'Replacement assigned.']);
} else {
    setFlashMessage('success', 'Replacement assigned successfully.');
    redirect('approved.php');
}