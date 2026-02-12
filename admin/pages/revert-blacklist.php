<?php
declare(strict_types=1);

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

// Require login
$auth->requireLogin();

/**
 * Resolve current user robustly:
 * 1) $currentUser if already set by includes
 * 2) $auth->getCurrentUser()
 * 3) $_SESSION['currentUser']
 */
$resolvedUser = [];
if (isset($currentUser) && is_array($currentUser)) {
    $resolvedUser = $currentUser;
} elseif (method_exists($auth, 'getCurrentUser')) {
    $u = $auth->getCurrentUser();
    if (is_array($u)) $resolvedUser = $u;
} else {
    $resolvedUser = (array)($_SESSION['currentUser'] ?? []);
}

$role         = (string)($resolvedUser['role'] ?? 'employee');
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

// Only Admin/Super Admin
if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to revert blacklisted applicants.');
    redirect('dashboard.php');
    exit;
}

/** Resolve acting admin ID robustly */
$adminId = (int)(
    $_SESSION['admin_id']
    ?? $_SESSION['user_id']
    ?? $resolvedUser['id']
    ?? $resolvedUser['user_id']
    ?? $resolvedUser['admin_id']
    ?? 0
);

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $postedToken  = (string)($_POST['csrf_token'] ?? '');
    $sessionToken = (string)($_SESSION['csrf_token'] ?? '');
    if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
        $errors[] = 'Invalid request. Please reload the page and try again.';
    }

    $blacklistId    = (int)($_POST['blacklist_id'] ?? 0);
    $complianceNote = trim((string)($_POST['compliance_note'] ?? ''));

    if ($blacklistId <= 0) {
        $errors[] = 'Invalid blacklist record ID.';
    }

    if ($adminId <= 0) {
        $errors[] = 'Unable to resolve acting admin ID.';
    }

    if (empty($errors)) {
        $conn = $database->getConnection();

        if (!($conn instanceof mysqli)) {
            $errors[] = 'Database connection error.';
        } else {
            // Fetch ACTIVE record for validation + logging
            $sqlGet = "
                SELECT b.*, a.first_name, a.middle_name, a.last_name, a.suffix
                FROM blacklisted_applicants b
                LEFT JOIN applicants a ON a.id = b.applicant_id
                WHERE b.id = ? AND b.is_active = 1
                LIMIT 1
            ";
            if ($stmtGet = $conn->prepare($sqlGet)) {
                $stmtGet->bind_param("i", $blacklistId);
                $stmtGet->execute();
                $res  = $stmtGet->get_result();
                $data = $res ? $res->fetch_assoc() : null;
                $stmtGet->close();

                if (!$data) {
                    $errors[] = 'Active blacklist record not found or already reverted.';
                } else {
                    // Handle optional compliance proofs
                    $complianceProofs = [];
                    if (isset($_FILES['compliance_proofs']) && is_array($_FILES['compliance_proofs']['name'])) {
                        $names = $_FILES['compliance_proofs']['name'];
                        $types = $_FILES['compliance_proofs']['type'];
                        $tmps  = $_FILES['compliance_proofs']['tmp_name'];
                        $errs  = $_FILES['compliance_proofs']['error'];
                        $sizes = $_FILES['compliance_proofs']['size'];

                        $allowedMimePrefixes = ['image/'];
                        $allowedExactMimes   = [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ];

                        $count = count($names);
                        for ($i = 0; $i < $count; $i++) {
                            $err = (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE);
                            if ($err !== UPLOAD_ERR_OK) {
                                continue;
                            }

                            $file = [
                                'name'     => (string)($names[$i] ?? ''),
                                'type'     => (string)($types[$i] ?? ''),
                                'tmp_name' => (string)($tmps[$i] ?? ''),
                                'error'    => (int)($errs[$i] ?? UPLOAD_ERR_NO_FILE),
                                'size'     => (int)($sizes[$i] ?? 0),
                            ];

                            $mime = $file['type'] ?? '';
                            $isAllowed = false;
                            foreach ($allowedMimePrefixes as $prefix) {
                                if (strpos($mime, $prefix) === 0) {
                                    $isAllowed = true;
                                    break;
                                }
                            }
                            if (!$isAllowed && in_array($mime, $allowedExactMimes, true)) {
                                $isAllowed = true;
                            }
                            if (!$isAllowed) {
                                $errors[] = 'One of the uploaded files has an unsupported type.';
                                break;
                            }

                            if (!function_exists('uploadFile')) {
                                $errors[] = 'Upload handler not found. Please define uploadFile() helper.';
                                break;
                            }

                            $saved = uploadFile($file, 'compliance');
                            if ($saved) {
                                $complianceProofs[] = $saved;
                            } else {
                                $errors[] = 'Failed to save one of the compliance proof files.';
                                break;
                            }
                        }
                    }

                    if (empty($errors)) {
                        $conn->begin_transaction();

                        $sqlUpdate = "
                            UPDATE blacklisted_applicants
                            SET
                                is_active = 0,
                                reverted_at = NOW(),
                                reverted_by = ?,
                                compliance_note = ?,
                                compliance_proof_paths = ?
                            WHERE id = ? AND is_active = 1
                            LIMIT 1
                        ";

                        $proofJson = !empty($complianceProofs) ? json_encode($complianceProofs) : null;

                        $okUpd = false;
                        if ($stmtUpd = $conn->prepare($sqlUpdate)) {
                            $stmtUpd->bind_param("issi", $adminId, $complianceNote, $proofJson, $blacklistId);
                            $okUpd = $stmtUpd->execute();
                            $affected = $stmtUpd->affected_rows;
                            $stmtUpd->close();

                            if (!$okUpd || $affected < 1) {
                                $conn->rollback();
                                // Either no matching active row or constraint issue
                                $errors[] = 'Failed to revert blacklist record.';
                            } else {
                                // (Optional) If you want to update applicant status post-revert, do it here:
                                // $applicantId = (int)$data['applicant_id'];
                                // $conn->query("UPDATE applicants SET status = 'pending' WHERE id = {$applicantId} LIMIT 1");

                                $conn->commit();

                                $appName = getFullName(
                                    $data['first_name'] ?? '',
                                    $data['middle_name'] ?? '',
                                    $data['last_name'] ?? '',
                                    $data['suffix'] ?? ''
                                );

                                $logDesc = "Reverted blacklist for applicant {$appName} (ID: {$data['applicant_id']})";
                                if ($complianceNote !== '') {
                                    $logDesc .= " - Compliance note: {$complianceNote}";
                                }
                                if (!empty($complianceProofs)) {
                                    $logDesc .= " - Compliance proofs uploaded: " . count($complianceProofs);
                                }

                                if (method_exists($auth, 'logActivity')) {
                                    $auth->logActivity($adminId, 'Revert Blacklist', $logDesc);
                                }

                                setFlashMessage('success', 'Applicant has been removed from blacklist successfully.');
                                redirect('blacklisted.php');
                                exit;
                            }
                        } else {
                            $conn->rollback();
                            $errors[] = 'Failed to prepare revert statement.';
                        }
                    }
                }
            } else {
                $errors[] = 'Failed to prepare fetch statement.';
            }
        }
    }
}

// If we get here: error or non-POST access
if (!empty($errors)) {
    setFlashMessage('error', implode(' ', $errors));
}
redirect('blacklisted.php');
exit;