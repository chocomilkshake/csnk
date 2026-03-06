<?php
// FILE: admin/pages/replace-assign.php (MySQLi-only)
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

// --- CONFIG ---
const CSNK_AGENCY_CODE = 'csnk';
// Auto-move candidate to on_process after a successful assignment?
const AUTO_MOVE_CANDIDATE_TO_ON_PROCESS = true;

// --- Bootstrap ---
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
    || !hash_equals(

$sqlUpdateAssign = "
    UPDATE applicant_replacements
    SET replacement_applicant_id = ?,
        assigned_at = NOW(),
        status = 'assigned'
    WHERE id = ?
      AND replacement_applicant_id IS NULL
      AND status IN ('selection')
    LIMIT 1
";

$sqlBumpCandidate = "
    UPDATE applicants
    SET status = 'on_process', updated_at = NOW()
    WHERE id = ? AND status IN ('pending','approved')
    LIMIT 1