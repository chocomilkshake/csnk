<?php
// FILE: admin/pages/turkey_revert-onhold.php
// Purpose: Revert an SMC (Turkey) on_hold applicant back to pending, with audit logs.

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$auth     = new Auth($database);
$auth->requireLogin();

// Resolve current user & role authorization
$currentUser  = $auth->getCurrentUser();
$role         = (string)($currentUser['role'] ?? 'employee');
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to revert applicants.');
    redirect('turkey_on-hold.php');
    exit;
}

// Resolve admin ID
$adminId = (int)(
    $_SESSION['admin_id']
    ?? ($currentUser['id'] ?? 0)
);

$errors = [];

// Small helper: detect if applicant_status_reports has business_unit_id column
function tr_asr_has_bu_column(mysqli $conn): bool {
    $res = $conn->query("SHOW COLUMNS FROM applicant_status_reports LIKE 'business_unit_id'");
    return $res && $res->num_rows > 0;
}

// Helper: get agency of applicant (csnk/smc/etc.)
function tr_get_agency_code_by_applicant(mysqli $conn, int $applicantId): ?string {
    $sql = "
        SELECT ag.code AS agency_code
        FROM applicants a
        JOIN business_units bu ON bu.id = a.business_unit_id
        JOIN agencies ag ON ag.id = bu.agency_id
        WHERE a.id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param('i', $applicantId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row['agency_code'] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation (token should already exist in session from list page)
    if (empty($_SESSION['csrf_token'])) {
        try {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Throwable $e) {
            $_SESSION['csrf_token'] = bin2hex((string)mt_rand());
        }
    }

    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if ($postedToken === '' || !hash_equals((string)$_SESSION['csrf_token'], $postedToken)) {
        $errors[] = 'Invalid request. Please reload the page and try again.';
    }

    $applicantId = isset($_POST['applicant_id']) ? (int)$_POST['applicant_id'] : 0;
    $reason      = trim((string)($_POST['reason'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));

    if ($applicantId <= 0) {
        $errors[] = 'Invalid applicant ID.';
    }
    if ($reason === '') {
        $errors[] = 'Reason is required.';
    }
    if ($description === '') {
        $errors[] = 'Description is required.';
    }
    if ($adminId <= 0) {
        $errors[] = 'Unable to resolve admin ID.';
    }

    if (empty($errors)) {
        $conn = $database->getConnection();

        if (!($conn instanceof mysqli)) {
            $errors[] = 'Database connection error.';
        } else {
            // Verify applicant exists, is ON HOLD, is SMC, and not deleted
            $sqlCheck = "
                SELECT id, status, business_unit_id, first_name, middle_name, last_name, suffix
                FROM applicants
                WHERE id = ?
                  AND status = 'on_hold'
                  AND deleted_at IS NULL
                LIMIT 1
            ";
            $stmtCheck = $conn->prepare($sqlCheck);
            if (!$stmtCheck) {
                $errors[] = 'Internal error preparing check.';
            } else {
                $stmtCheck->bind_param("i", $applicantId);
                $stmtCheck->execute();
                $resCheck  = $stmtCheck->get_result();
                $applicant = $resCheck ? $resCheck->fetch_assoc() : null;
                $stmtCheck->close();

                if (!$applicant) {
                    $errors[] = 'Applicant not found or is not on hold status.';
                } else {
                    // Enforce SMC agency scope
                    $agencyCode = tr_get_agency_code_by_applicant($conn, $applicantId);
                    if (strtolower((string)$agencyCode) !== 'smc') {
                        $errors[] = 'Operation blocked: applicant does not belong to SMC.';
                    } else {
                        $businessUnitId = (int)($applicant['business_unit_id'] ?? 0);
                        if ($businessUnitId <= 0) {
                            $errors[] = 'Applicant does not have a valid business unit assigned.';
                        } else {
                            // Proceed with transaction
                            $conn->begin_transaction();
                            try {
                                // 1) Update applicant status to pending
                                $sqlUpdate = "
                                    UPDATE applicants
                                    SET status = 'pending', updated_at = NOW()
                                    WHERE id = ? AND status = 'on_hold'
                                ";
                                $stmtUpdate = $conn->prepare($sqlUpdate);
                                if (!$stmtUpdate) {
                                    throw new Exception('Internal error preparing status update.');
                                }
                                $stmtUpdate->bind_param("i", $applicantId);
                                $stmtUpdate->execute();
                                if ($conn->affected_rows < 1) {
                                    throw new Exception('Failed to update applicant status.');
                                }
                                $stmtUpdate->close();

                                // 2) Insert status history (with/without BU based on schema)
                                $fullName   = getFullName(
                                    $applicant['first_name'] ?? '',
                                    $applicant['middle_name'] ?? '',
                                    $applicant['last_name'] ?? '',
                                    $applicant['suffix'] ?? ''
                                );
                                $reportText = "Reverted from On Hold to Pending. Reason: {$reason}. Description: {$description}";

                                if (tr_asr_has_bu_column($conn)) {
                                    $sqlReport = "
                                        INSERT INTO applicant_status_reports
                                        (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                                        VALUES (?, ?, 'on_hold', 'pending', ?, ?)
                                    ";
                                    $stmtReport = $conn->prepare($sqlReport);
                                    if ($stmtReport) {
                                        $stmtReport->bind_param("iisi", $applicantId, $businessUnitId, $reportText, $adminId);
                                        $stmtReport->execute();
                                        $stmtReport->close();
                                    }
                                } else {
                                    // Fallback if your table doesn't have business_unit_id
                                    $sqlReport = "
                                        INSERT INTO applicant_status_reports
                                        (applicant_id, from_status, to_status, report_text, admin_id)
                                        VALUES (?, 'on_hold', 'pending', ?, ?)
                                    ";
                                    $stmtReport = $conn->prepare($sqlReport);
                                    if ($stmtReport) {
                                        $stmtReport->bind_param("isi", $applicantId, $reportText, $adminId);
                                        $stmtReport->execute();
                                        $stmtReport->close();
                                    }
                                }

                                // 3) Insert applicant report (this table DOES have business_unit_id in your schema)
                                $fullReportText = "Revert to Pending - Reason: {$reason}. Description: {$description}";
                                $sqlAppReport   = "
                                    INSERT INTO applicant_reports
                                    (applicant_id, business_unit_id, admin_id, note_text)
                                    VALUES (?, ?, ?, ?)
                                ";
                                $stmtAppReport = $conn->prepare($sqlAppReport);
                                if ($stmtAppReport) {
                                    $stmtAppReport->bind_param("iiis", $applicantId, $businessUnitId, $adminId, $fullReportText);
                                    $stmtAppReport->execute();
                                    $stmtAppReport->close();
                                }

                                // 4) Commit
                                $conn->commit();

                                // 5) Activity log
                                if (method_exists($auth, 'logActivity')) {
                                    $auth->logActivity(
                                        $adminId,
                                        'Revert On Hold Applicant (SMC/TR)',
                                        "Reverted applicant {$fullName} (ID: {$applicantId}) from On Hold to Pending. Reason: {$reason}"
                                    );
                                }

                                setFlashMessage('success', 'Applicant has been reverted to Pending status.');
                                redirect('turkey_on-hold.php');
                                exit;

                            } catch (Exception $e) {
                                $conn->rollback();
                                $errors[] = 'Failed to revert applicant: ' . $e->getMessage();
                            }
                        }
                    }
                }
            }
        }
    }
}

// Bubble up errors as flash and go back
if (!empty($errors)) {
    foreach ($errors as $error) {
        setFlashMessage('error', $error);
    }
}

redirect('turkey_on-hold.php');
exit;