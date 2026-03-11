<?php
// FILE: pages/revert-onhold.php
// Handle reverting an on_hold applicant back to pending

require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

// Ensure session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

// Resolve current user
$currentUser = $auth->getCurrentUser();
$role = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to revert applicants.');
    redirect('on-hold.php');
    exit;
}

// Resolve admin ID
$adminId = (int)(
    $_SESSION['admin_id'] 
    ?? $currentUser['id'] 
    ?? 0
);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF token validation
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    $postedToken = $_POST['csrf_token'] ?? '';
    if ($postedToken === '' || !hash_equals($_SESSION['csrf_token'], $postedToken)) {
        $errors[] = 'Invalid request. Please reload the page and try again.';
    }

    $applicantId = isset($_POST['applicant_id']) ? (int)$_POST['applicant_id'] : 0;
    $reason = trim($_POST['reason'] ?? '');
    $description = trim($_POST['description'] ?? '');

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
            // Verify applicant exists and is on_hold (include business_unit_id)
            $sqlCheck = "SELECT id, status, business_unit_id, first_name, middle_name, last_name, suffix FROM applicants WHERE id = ? AND status = 'on_hold' AND deleted_at IS NULL LIMIT 1";
            $stmtCheck = $conn->prepare($sqlCheck);
            $stmtCheck->bind_param("i", $applicantId);
            $stmtCheck->execute();
            $resCheck = $stmtCheck->get_result();
            $applicant = $resCheck ? $resCheck->fetch_assoc() : null;
            $stmtCheck->close();

            if (!$applicant) {
                $errors[] = 'Applicant not found or is not on hold status.';
            } else {
                $businessUnitId = (int)($applicant['business_unit_id'] ?? 0);
                
                if ($businessUnitId <= 0) {
                    $errors[] = 'Applicant does not have a valid business unit assigned.';
                } else {
                    $conn->begin_transaction();

                    try {
                        // Update applicant status to pending
                        $sqlUpdate = "UPDATE applicants SET status = 'pending', updated_at = NOW() WHERE id = ? AND status = 'on_hold'";
                        $stmtUpdate = $conn->prepare($sqlUpdate);
                        $stmtUpdate->bind_param("i", $applicantId);
                        $stmtUpdate->execute();
                        
                        if ($conn->affected_rows < 1) {
                            throw new Exception('Failed to update applicant status.');
                        }
                        $stmtUpdate->close();

                        // Insert status report (include business_unit_id)
                        $fullName = getFullName(
                            $applicant['first_name'] ?? '',
                            $applicant['middle_name'] ?? '',
                            $applicant['last_name'] ?? '',
                            $applicant['suffix'] ?? ''
                        );
                        $reportText = "Reverted from On Hold to Pending. Reason: {$reason}. Description: {$description}";
                        
                        $sqlReport = "INSERT INTO applicant_status_reports (applicant_id, from_status, to_status, report_text, admin_id) VALUES (?, 'on_hold', 'pending', ?, ?)";
                        $stmtReport = $conn->prepare($sqlReport);
                        $stmtReport->bind_param("isi", $applicantId, $reportText, $adminId);
                        $stmtReport->execute();
                        $stmtReport->close();

                        // Also add to applicant_reports
                        $sqlAppReport = "INSERT INTO applicant_reports (applicant_id, business_unit_id, admin_id, note_text) VALUES (?, ?, ?, ?)";
                        $stmtAppReport = $conn->prepare($sqlAppReport);
                        $fullReportText = "Revert to Pending - Reason: {$reason}. Description: {$description}";
                        $stmtAppReport->bind_param("iiis", $applicantId, $businessUnitId, $adminId, $fullReportText);
                        $stmtAppReport->execute();
                        $stmtAppReport->close();

                        $conn->commit();

                        // Log activity
                        if (method_exists($auth, 'logActivity')) {
                            $auth->logActivity(
                                $adminId,
                                'Revert On Hold Applicant',
                                "Reverted applicant {$fullName} (ID: {$applicantId}) from On Hold to Pending. Reason: {$reason}"
                            );
                        }

                        setFlashMessage('success', 'Applicant has been reverted to Pending status.');
                        redirect('on-hold.php');
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

if (!empty($errors)) {
    foreach ($errors as $error) {
        setFlashMessage('error', $error);
    }
}

redirect('on-hold.php');
exit;

