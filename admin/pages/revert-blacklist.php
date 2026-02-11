<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../includes/functions.php';

// Ensure session (if not already started by your bootstrap)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$database = new Database();
$auth     = new Auth($database);

// Require login (your method)
$auth->requireLogin();

/**
 * Resolve current user robustly:
 * 1) $currentUser if already set by includes
 * 2) $auth->currentUser() or $auth->getCurrentUser()
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

$role         = $resolvedUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to revert blacklisted applicants.');
    redirect('dashboard.php');
    exit;
}

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 🔐 CSRF check (keep this if your form includes a csrf_token hidden input)
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $errors[] = 'Invalid request. Please reload the page and try again.';
    }

    $blacklistId     = (int)($_POST['blacklist_id'] ?? 0);
    $complianceNote  = trim((string)($_POST['compliance_note'] ?? ''));

    if ($blacklistId <= 0) {
        $errors[] = 'Invalid blacklist record ID.';
    }

    if (empty($errors)) {
        $conn = $database->getConnection();

        if (!($conn instanceof mysqli)) {
            $errors[] = 'Database connection error.';
        } else {
            // Fetch blacklist record for logging & validation
            $sqlGet = "SELECT b.*, a.first_name, a.middle_name, a.last_name, a.suffix
                       FROM blacklisted_applicants b
                       LEFT JOIN applicants a ON a.id = b.applicant_id
                       WHERE b.id = ? LIMIT 1";
            if ($stmtGet = $conn->prepare($sqlGet)) {
                $stmtGet->bind_param("i", $blacklistId);
                $stmtGet->execute();
                $result        = $stmtGet->get_result();
                $blacklistData = $result->fetch_assoc();
                $stmtGet->close();

                if (!$blacklistData) {
                    $errors[] = 'Blacklist record not found.';
                } else {
                    // 📎 Handle compliance proof uploads (optional)
                    $complianceProofs = [];
                    if (isset($_FILES['compliance_proofs']) && is_array($_FILES['compliance_proofs']['name'])) {
                        $names = $_FILES['compliance_proofs']['name'];
                        $types = $_FILES['compliance_proofs']['type'];
                        $tmps  = $_FILES['compliance_proofs']['tmp_name'];
                        $errs  = $_FILES['compliance_proofs']['error'];
                        $sizes = $_FILES['compliance_proofs']['size'];

                        // Allow images + pdf/doc/docx (to match your modal's accept)
                        $allowedMimePrefixes = ['image/'];
                        $allowedExactMimes   = [
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ];

                        $count = count($names);
                        for ($i = 0; $i < $count; $i++) {
                            $err = $errs[$i] ?? UPLOAD_ERR_NO_FILE;
                            if ($err !== UPLOAD_ERR_OK) {
                                // Skip if no file or upload error
                                continue;
                            }

                            $file = [
                                'name'     => (string)$names[$i],
                                'type'     => (string)$types[$i],
                                'tmp_name' => (string)$tmps[$i],
                                'error'    => (int)$errs[$i],
                                'size'     => (int)$sizes[$i],
                            ];

                            // Basic MIME allowlist check
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

                        // Mark blacklist record as reverted (keep history)
                        $sqlUpdate = "
                            UPDATE blacklisted_applicants
                            SET
                                is_active = 0,
                                reverted_at = NOW(),
                                reverted_by = ?,
                                compliance_note = ?,
                                compliance_proof_paths = ?
                            WHERE id = ?
                            LIMIT 1
                        ";

                        $adminId = (int)($_SESSION['admin_id'] ?? ($resolvedUser['id'] ?? 0));
                        $proofJson = !empty($complianceProofs) ? json_encode($complianceProofs) : null;

                        if ($stmtUpd = $conn->prepare($sqlUpdate)) {
                            $stmtUpd->bind_param("issi", $adminId, $complianceNote, $proofJson, $blacklistId);
                            $okUpd = $stmtUpd->execute();
                            $stmtUpd->close();

                            if (!$okUpd || $conn->affected_rows < 1) {
                                $conn->rollback();
                                $errors[] = 'Failed to revert blacklist record.';
                            } else {
                                $conn->commit();

                                // Log activity
                                $appName = getFullName(
                                    $blacklistData['first_name'] ?? '',
                                    $blacklistData['middle_name'] ?? '',
                                    $blacklistData['last_name'] ?? '',
                                    $blacklistData['suffix'] ?? ''
                                );

                                $logDesc = "Reverted blacklist for applicant {$appName} (ID: {$blacklistData['applicant_id']})";
                                if (!empty($complianceNote)) {
                                    $logDesc .= " - Compliance note: {$complianceNote}";
                                }
                                if (!empty($complianceProofs)) {
                                    $logDesc .= " - Compliance proofs uploaded: " . count($complianceProofs);
                                }

                                if (method_exists($auth, 'logActivity')) {
                                    $auth->logActivity($adminId, 'Revert Blacklist', $logDesc);
                                }

                                $success = true;
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

// If we get here, there was an error or non-POST access
if (!empty($errors)) {
    setFlashMessage('error', implode(' ', $errors));
}
redirect('blacklisted.php');
exit;