<?php
// FILE: admin/pages/turkey_replace-assign.php (SMC Turkey Version, full-featured)
// Behavior:
//  - SMC scope enforced (original & candidate must belong to SMC).
//  - Assigns replacement under transaction/lock.
//  - Candidate: pending/approved -> on_process (+ status report).
//  - Original: -> on_hold (+ status report + applicant report).
//  - Activity logged.
//  - Optional CSRF (preserves your prior behavior).
//  - Works for AJAX (JSON) and non-AJAX (flash + redirect).

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
$adminId     = isset($currentUser['id']) ? (int)$currentUser['id'] : (int)($_SESSION['admin_id'] ?? 0);

// --- CONFIG ---
const SMC_AGENCY_CODE = 'smc';
const AUTO_MOVE_CANDIDATE_TO_ON_PROCESS_TR = true;

// --- Ajax flag ---
$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') ||
    (isset($_REQUEST['ajax']) && (string)$_REQUEST['ajax'] === '1') ||
    (isset($_POST['ajax']) && (string)$_POST['ajax'] === '1')
);

// --- JSON helper ---
function tr_json_out($ok, $payload = [], $http = 200) {
    http_response_code($http);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['ok' => (bool)$ok] + $payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) tr_json_out(false, ['message' => 'Invalid request method.'], 405);
    setFlashMessage('error', 'Invalid request method.');
    redirect('turkey_approved.php'); exit;
}

// --- Optional CSRF (kept as in your original file) ---
if (!empty($_POST['csrf_token'])) {
    if (empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        if ($isAjax) tr_json_out(false, ['message' => 'Invalid security token.'], 403);
        setFlashMessage('error', 'Invalid security token.');
        redirect('turkey_approved.php'); exit;
    }
}

$replacementId = isset($_POST['replacement_id']) ? (int)$_POST['replacement_id'] : 0;
$candidateId   = isset($_POST['replacement_applicant_id']) ? (int)$_POST['replacement_applicant_id'] : 0;

if ($replacementId <= 0 || $candidateId <= 0 || $adminId <= 0) {
    if ($isAjax) tr_json_out(false, ['message' => 'Missing fields or authentication.'], 422);
    setFlashMessage('error', 'Missing fields or authentication.');
    redirect('turkey_approved.php'); exit;
}

// --- Get mysqli connection ---
$conn = method_exists($database, 'getConnection') ? $database->getConnection() : null;
if (!($conn instanceof mysqli)) {
    if ($isAjax) tr_json_out(false, ['message' => 'Database connection type not supported (expecting MySQLi).'], 500);
    setFlashMessage('error', 'DB connection type not supported (MySQLi required).');
    redirect('turkey_approved.php'); exit;
}

/** ===================== SQL ===================== **/
$sqlLockReplacement = "
    SELECT id, original_applicant_id, replacement_applicant_id, status, business_unit_id
    FROM applicant_replacements
    WHERE id = ?
    FOR UPDATE
";

$sqlGetAgencyStatusBu = "
    SELECT a.id, a.status, a.business_unit_id, ag.code AS agency_code
    FROM applicants a
    JOIN business_units bu ON bu.id = a.business_unit_id
    JOIN agencies ag ON ag.id = bu.agency_id
    WHERE a.id = ?
    LIMIT 1
";

$sqlUpdateAssign = "
    UPDATE applicant_replacements
    SET replacement_applicant_id = ?, assigned_at = NOW(), status = 'assigned'
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
";

$sqlMoveOriginalToOnHold = "
    UPDATE applicants
    SET status = 'on_hold', updated_at = NOW()
    WHERE id = ? AND status <> 'on_hold'
    LIMIT 1
";

/** ===================== Helper funcs ===================== **/

function asr_has_bu_column_tr(mysqli $conn): bool {
    $res = $conn->query("SHOW COLUMNS FROM applicant_status_reports LIKE 'business_unit_id'");
    return $res && $res->num_rows > 0;
}

function tr_insert_status_report(mysqli $conn, int $applicantId, ?int $businessUnitId, string $from, string $to, string $text, int $adminId): void {
    $hasBU = asr_has_bu_column_tr($conn);
    if ($hasBU) {
        $stmt = $conn->prepare("
            INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('iisssi', $applicantId, $businessUnitId, $from, $to, $text, $adminId);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("
            INSERT INTO applicant_status_reports (applicant_id, from_status, to_status, report_text, admin_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('isssi', $applicantId, $from, $to, $text, $adminId);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function tr_insert_applicant_report(mysqli $conn, int $applicantId, ?int $businessUnitId, int $adminId, string $note): void {
    $res = $conn->query("SHOW COLUMNS FROM applicant_reports LIKE 'business_unit_id'");
    $hasBU = $res && $res->num_rows > 0;
    if ($hasBU) {
        $stmt = $conn->prepare("INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iiis', $applicantId, $businessUnitId, $adminId, $note);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO applicant_reports (applicant_id, admin_id, note_text) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('iis', $applicantId, $adminId, $note);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/** ===================== Action ===================== **/
try {
    $allowedCandidateStatuses = ['pending', 'approved', 'on_process'];

    $conn->begin_transaction();

    // 1) Lock replacement row
    $stmt = $conn->prepare($sqlLockReplacement);
    if (!$stmt) throw new Exception('Failed to prepare replacement lock statement.');
    $stmt->bind_param('i', $replacementId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rep = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rep) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Replacement record not found.'], 404);
        setFlashMessage('error', 'Replacement record not found.');
        redirect('turkey_approved.php'); exit;
    }
    if (!empty($rep['replacement_applicant_id'])) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'This replacement is already assigned.'], 409);
        setFlashMessage('error', 'This replacement is already assigned.');
        redirect('turkey_approved.php'); exit;
    }
    if (strtolower((string)$rep['status']) !== 'selection') {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'This replacement is not in a selectable state.'], 409);
        setFlashMessage('error', 'Replacement not in selectable state.');
        redirect('turkey_approved.php'); exit;
    }

    $originalId = (int)$rep['original_applicant_id'];
    $repBuId    = isset($rep['business_unit_id']) ? (int)$rep['business_unit_id'] : null;
    if ($originalId <= 0) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Invalid replacement link to original applicant.'], 422);
        setFlashMessage('error', 'Invalid replacement link.');
        redirect('turkey_approved.php'); exit;
    }

    // 2) Ensure ORIGINAL is SMC (and capture BU/status)
    $s = $conn->prepare($sqlGetAgencyStatusBu);
    if (!$s) throw new Exception('Failed to prepare applicant agency check (original).');
    $s->bind_param('i', $originalId);
    $s->execute();
    $r = $s->get_result();
    $orig = $r ? $r->fetch_assoc() : null;
    $s->close();
    if (!$orig || strtolower((string)$orig['agency_code']) !== SMC_AGENCY_CODE) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Operation blocked: original applicant is not SMC.'], 403);
        setFlashMessage('error', 'Operation blocked: not SMC.');
        redirect('turkey_approved.php'); exit;
    }
    $origStatus = strtolower((string)$orig['status']);
    $origBuId   = isset($orig['business_unit_id']) ? (int)$orig['business_unit_id'] : $repBuId;

    // 3) Ensure CANDIDATE is SMC and in allowed status
    $s = $conn->prepare($sqlGetAgencyStatusBu);
    if (!$s) throw new Exception('Failed to prepare applicant agency check (candidate).');
    $s->bind_param('i', $candidateId);
    $s->execute();
    $r = $s->get_result();
    $cand = $r ? $r->fetch_assoc() : null;
    $s->close();

    if (!$cand) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Candidate not found.'], 404);
        setFlashMessage('error', 'Candidate not found.');
        redirect('turkey_approved.php'); exit;
    }
    if (strtolower((string)$cand['agency_code']) !== SMC_AGENCY_CODE) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Candidate is not from SMC.'], 422);
        setFlashMessage('error', 'Candidate is not from SMC.');
        redirect('turkey_approved.php'); exit;
    }
    $candStatus = strtolower((string)$cand['status']);
    $candBuId   = isset($cand['business_unit_id']) ? (int)$cand['business_unit_id'] : null;

    if (!in_array($candStatus, $allowedCandidateStatuses, true)) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Candidate is not in an assignable status (Pending/Approved/On-Process).'], 422);
        setFlashMessage('error', 'Candidate not assignable (Pending/Approved/On-Process only).');
        redirect('turkey_approved.php'); exit;
    }
    if ($candidateId === $originalId) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Cannot assign the same person as their own replacement.'], 422);
        setFlashMessage('error', 'Cannot self-replace.');
        redirect('turkey_approved.php'); exit;
    }

    // 4a) Assign the candidate
    $u = $conn->prepare($sqlUpdateAssign);
    if (!$u) throw new Exception('Failed to prepare assignment update.');
    $u->bind_param('ii', $candidateId, $replacementId);
    $u->execute();
    $affected = $u->affected_rows;
    $u->close();

    if ($affected !== 1) {
        $conn->rollback();
        if ($isAjax) tr_json_out(false, ['message' => 'Failed to assign replacement. It may have been assigned already.'], 409);
        setFlashMessage('error', 'Failed to assign (already assigned?).');
        redirect('turkey_approved.php'); exit;
    }

    // 4b) Candidate -> on_process (+status report) if pending/approved
    if (AUTO_MOVE_CANDIDATE_TO_ON_PROCESS_TR && in_array($candStatus, ['pending', 'approved'], true)) {
        $bu = $conn->prepare($sqlBumpCandidate);
        if ($bu) { $bu->bind_param('i', $candidateId); $bu->execute(); $bu->close(); }
        $reportText = "Replacement assignment — moved from {$candStatus} to on_process.";
        tr_insert_status_report($conn, $candidateId, $candBuId, $candStatus, 'on_process', $reportText, $adminId);
    }

    // 4c) ORIGINAL -> on_hold (+status report + applicant report)
    $fromOriginal = $origStatus ?: 'approved';
    $mh = $conn->prepare($sqlMoveOriginalToOnHold);
    if ($mh) { $mh->bind_param('i', $originalId); $mh->execute(); $mh->close(); }

    $origReport = "Replaced by Applicant ID {$candidateId}. Original moved to on_hold.";
    tr_insert_status_report($conn, $originalId, $origBuId, $fromOriginal, 'on_hold', $origReport, $adminId);
    tr_insert_applicant_report($conn, $originalId, $origBuId, $adminId, "Replaced by Applicant ID {$candidateId}. Status moved to On Hold.");

    // 5) Activity log
    if (method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
        $auth->logActivity((int)$_SESSION['admin_id'], 'Assign Replacement (Turkey/SMC)',
            "Assigned Applicant ID {$candidateId} as replacement for Original ID {$originalId}; original set to On Hold");
    }

    $conn->commit();

    if ($isAjax) {
        tr_json_out(true, ['message' => 'Replacement assigned successfully. Original moved to On Hold.']);
    } else {
        setFlashMessage('success', 'Replacement assigned successfully. Original moved to On Hold.');
        redirect('turkey_approved.php');
    }
    exit;

} catch (Throwable $e) {
    error_log('Turkey replace-assign exception: ' . $e->getMessage());
    try { $conn->rollback(); } catch (Throwable $e2) {}
    if ($isAjax) tr_json_out(false, ['message' => 'An internal error occurred. Please try again later.'], 500);
    setFlashMessage('error', 'An internal error occurred. Please try again.');
    redirect('turkey_approved.php'); exit;
}