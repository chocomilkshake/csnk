<?php
// FILE: admin/pages/replace-assign.php (MySQLi-only, AUTO-HOLD ORIGINAL after assignment)
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';
require_once '../includes/Applicant.php';

// --- CONFIG ---
const CSNK_AGENCY_CODE = 'csnk';
const AUTO_MOVE_CANDIDATE_TO_ON_PROCESS = true; // keep your behavior

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

// === CSRF ===
if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])
    || !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
    if ($isAjax) json_out(false, ['message' => 'Invalid security token. Please refresh the page and try again.'], 403);
    setFlashMessage('error', 'Invalid security token.');
    redirect('approved.php'); exit;
}

$replacementId = isset($_POST['replacement_id']) ? (int)$_POST['replacement_id'] : 0;
$candidateId   = isset($_POST['replacement_applicant_id']) ? (int)$_POST['replacement_applicant_id'] : 0;

if ($replacementId <= 0 || $candidateId <= 0 || $adminId === null) {
    if ($isAjax) json_out(false, ['message' => 'Missing required fields or authentication.'], 422);
    setFlashMessage('error', 'Missing fields or authentication.');
    redirect('approved.php'); exit;
}

// --- Get mysqli connection ---
$conn = method_exists($database, 'getConnection') ? $database->getConnection() : null;
if (!($conn instanceof mysqli)) {
    if ($isAjax) json_out(false, ['message' => 'Database connection type not supported (expecting MySQLi).'], 500);
    setFlashMessage('error', 'DB connection type not supported (MySQLi required).');
    redirect('approved.php'); exit;
}

// SQLs
$sqlLockReplacement = "
    SELECT id, original_applicant_id, replacement_applicant_id, status, business_unit_id
    FROM applicant_replacements
    WHERE id = ?
    FOR UPDATE
";

$sqlGetAgencyAndStatusByApplicant = "
    SELECT a.id, a.status, a.business_unit_id, ag.code AS agency_code
    FROM applicants a
    JOIN business_units bu ON bu.id = a.business_unit_id
    JOIN agencies ag ON ag.id = bu.agency_id
    WHERE a.id = ?
    LIMIT 1
";

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
";

$sqlMoveOriginalToOnHold = "
    UPDATE applicants
    SET status = 'on_hold', updated_at = NOW()
    WHERE id = ? AND status <> 'on_hold'
    LIMIT 1
";

// Helpers to write status/applicant reports depending on schema
function asr_has_bu_column(mysqli $conn): bool {
    $res = $conn->query("SHOW COLUMNS FROM applicant_status_reports LIKE 'business_unit_id'");
    return $res && $res->num_rows > 0;
}

function insert_status_report(mysqli $conn, int $applicantId, ?int $businessUnitId, string $from, string $to, string $text, int $adminId): void {
    $hasBU = asr_has_bu_column($conn);
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

function insert_applicant_report(mysqli $conn, int $applicantId, ?int $businessUnitId, int $adminId, string $note): void {
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

try {
    $allowedCandidateStatuses = ['pending', 'approved', 'on_process'];

    $conn->begin_transaction();

    // 1) Lock the replacement row
    $stmt = $conn->prepare($sqlLockReplacement);
    if (!$stmt) throw new Exception('Failed to prepare replacement lock statement.');
    $stmt->bind_param('i', $replacementId);
    $stmt->execute();
    $res = $stmt->get_result();
    $rep = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$rep) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Replacement record not found.'], 404);
        setFlashMessage('error', 'Replacement record not found.');
        redirect('approved.php'); exit;
    }
    if (!empty($rep['replacement_applicant_id'])) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'This replacement is already assigned.'], 409);
        setFlashMessage('error', 'This replacement is already assigned.');
        redirect('approved.php'); exit;
    }
    if (strtolower((string)$rep['status']) !== 'selection') {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'This replacement is not in a selectable state.'], 409);
        setFlashMessage('error', 'Replacement not in selectable state.');
        redirect('approved.php'); exit;
    }

    $originalId = (int)$rep['original_applicant_id'];
    $repBuId    = isset($rep['business_unit_id']) ? (int)$rep['business_unit_id'] : null;
    if ($originalId <= 0) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Invalid replacement link to original applicant.'], 422);
        setFlashMessage('error', 'Invalid replacement link.');
        redirect('approved.php'); exit;
    }

    // 2) Ensure original is CSNK (and fetch original BU)
    $s = $conn->prepare($sqlGetAgencyAndStatusByApplicant);
    if (!$s) throw new Exception('Failed to prepare applicant agency check.');
    $s->bind_param('i', $originalId);
    $s->execute();
    $r = $s->get_result();
    $orig = $r ? $r->fetch_assoc() : null;
    $s->close();
    if (!$orig || strtolower((string)$orig['agency_code']) !== CSNK_AGENCY_CODE) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Operation blocked: original applicant is not CSNK.'], 403);
        setFlashMessage('error', 'Operation blocked: not CSNK.');
        redirect('approved.php'); exit;
    }
    $origStatus = strtolower((string)$orig['status']);
    $origBuId   = isset($orig['business_unit_id']) ? (int)$orig['business_unit_id'] : $repBuId;

    // 3) Check candidate agency + status
    $s = $conn->prepare($sqlGetAgencyAndStatusByApplicant);
    if (!$s) throw new Exception('Failed to prepare candidate agency check.');
    $s->bind_param('i', $candidateId);
    $s->execute();
    $r = $s->get_result();
    $cand = $r ? $r->fetch_assoc() : null;
    $s->close();

    if (!$cand) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Candidate not found.'], 404);
        setFlashMessage('error', 'Candidate not found.');
        redirect('approved.php'); exit;
    }
    if (strtolower((string)$cand['agency_code']) !== CSNK_AGENCY_CODE) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Candidate is not from CSNK.'], 422);
        setFlashMessage('error', 'Candidate is not from CSNK.');
        redirect('approved.php'); exit;
    }
    $candStatus = strtolower((string)$cand['status']);
    $candBuId   = isset($cand['business_unit_id']) ? (int)$cand['business_unit_id'] : null;

    if (!in_array($candStatus, $allowedCandidateStatuses, true)) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Candidate is not in an assignable status (Pending/Approved/On-Process).'], 422);
        setFlashMessage('error', 'Candidate not assignable (Pending/Approved/On-Process only).');
        redirect('approved.php'); exit;
    }
    if ($candidateId === $originalId) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Cannot assign the same person as their own replacement.'], 422);
        setFlashMessage('error', 'Cannot self-replace.');
        redirect('approved.php'); exit;
    }

    // 4a) Perform the assignment
    $u = $conn->prepare($sqlUpdateAssign);
    if (!$u) throw new Exception('Failed to prepare assignment update.');
    $u->bind_param('ii', $candidateId, $replacementId);
    $u->execute();
    $affected = $u->affected_rows;
    $u->close();

    if ($affected !== 1) {
        $conn->rollback();
        if ($isAjax) json_out(false, ['message' => 'Failed to assign replacement. It may have been assigned already.'], 409);
        setFlashMessage('error', 'Failed to assign (already assigned?).');
        redirect('approved.php'); exit;
    }

    // 4b) Optionally bump candidate to on_process and write status report
    if (AUTO_MOVE_CANDIDATE_TO_ON_PROCESS && in_array($candStatus, ['pending', 'approved'], true)) {
        $bu = $conn->prepare($sqlBumpCandidate);
        if ($bu) {
            $bu->bind_param('i', $candidateId);
            $bu->execute();
            $bu->close();
        }
        $reportText = "Replacement assignment — moved from {$candStatus} to on_process.";
        insert_status_report($conn, $candidateId, $candBuId, $candStatus, 'on_process', $reportText, $adminId);
    }

    // 4c) Move ORIGINAL to ON-HOLD and record status report + report note
    $fromOriginal = $origStatus ?: 'approved';
    $mh = $conn->prepare($sqlMoveOriginalToOnHold);
    if ($mh) {
        $mh->bind_param('i', $originalId);
        $mh->execute();
        $mh->close();
    }
    $origReport = "Replaced by Applicant ID {$candidateId}. Original moved to on_hold.";
    insert_status_report($conn, $originalId, $origBuId, $fromOriginal, 'on_hold', $origReport, $adminId);
    insert_applicant_report($conn, $originalId, $origBuId, $adminId, "Replaced by Applicant ID {$candidateId}. Status moved to On Hold.");

    // Activity log (optional)
    if (method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
        $auth->logActivity((int)$_SESSION['admin_id'], 'Assign Replacement',
            "Assigned Applicant ID {$candidateId} as replacement for Original ID {$originalId}; original set to On Hold");
    }

    $conn->commit();

    if ($isAjax) {
        json_out(true, ['message' => 'Replacement assigned successfully. Original moved to On Hold.']);
    } else {
        setFlashMessage('success', 'Replacement assigned successfully. Original moved to On Hold.');
        redirect('approved.php');
    }
    exit;

} catch (Throwable $e) {
    error_log('Replace-assign exception: ' . $e->getMessage());
    try { $conn->rollback(); } catch (Throwable $e2) {}
    if ($isAjax) json_out(false, ['message' => 'An internal error occurred. Please try again later.'], 500);
    setFlashMessage('error', 'An internal error occurred. Please try again.');
    redirect('approved.php'); exit;
}