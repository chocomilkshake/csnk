<?php
// FILE: admin/pages/replace-init.php (MySQLi-only)
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

// === REQUIRE CSRF for all POSTs ===
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
if ($