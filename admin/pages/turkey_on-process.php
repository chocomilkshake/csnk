<?php
// FILE: admin/pages/turkey_on-process.php (SMC - Turkey On Process Applicants)
$pageTitle = 'SMC Manpower Agency Co.';

$ADMIN_ROOT = dirname(__DIR__);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $ADMIN_ROOT . '/includes/config.php';
require_once $ADMIN_ROOT . '/includes/Database.php';
require_once $ADMIN_ROOT . '/includes/Auth.php';
require_once $ADMIN_ROOT . '/includes/functions.php';

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

// Check if user has permission to view SMC data
if (!$auth->canSeeSMC()) {
    header('Location: applicants.php');
    exit;
}

// Resolve current user & role
$currentUser = $auth->getCurrentUser();
$role = isset($currentUser['role']) ? (string)$currentUser['role'] : 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin = ($role === 'admin');
$isEmployee = ($role === 'employee');
$canEdit = ($isSuperAdmin || $isAdmin || $isEmployee);

$conn = $database->getConnection();

// Grab ALL active SMC BU IDs to enforce SMC-only on the list (for ALL roles)
$smcBuIds = [];
if ($conn instanceof mysqli) {
    $sqlSmcBus = "
        SELECT bu.id
        FROM business_units bu
        JOIN agencies ag ON ag.id = bu.agency_id
        WHERE ag.code = 'smc' AND bu.active = 1
        ORDER BY bu.id ASC
    ";
    if ($res = $conn->query($sqlSmcBus)) {
        while ($r = $res->fetch_assoc()) {
            $smcBuIds[] = (int)$r['id'];
        }
    }
}

// Store first SMC BU ID in session (if you use it elsewhere)
if (!empty($smcBuIds)) {
    $_SESSION['smc_bu_id'] = $smcBuIds[0];
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}
$csrf = $_SESSION['csrf_token'];

// Allowed statuses
$allowedStatuses = ['pending', 'on_process', 'approved'];

// Preserve query string
$preserveQS = !empty($_GET) ? ('&' . http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY))) : '';
$preserveQSWithQuestion = !empty($preserveQS) ? ('?' . ltrim($preserveQS, '&')) : '';

// Handle status update with report (POST - from modal)
if (
    isset($_POST['action']) && $_POST['action'] === 'update_status_report' &&
    isset($_POST['id'], $_POST['to'], $_POST['report_text'])
) {
    $id = (int)$_POST['id'];
    $to = strtolower(trim((string)$_POST['to']));
    $reportText = trim((string)$_POST['report_text']);

    // CSRF check
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid or missing security token. Please refresh and try again.');
        }
        redirect('turkey_on-process.php' . $preserveQSWithQuestion);
        exit;
    }

    // Validate
    if (!in_array($to, $allowedStatuses, true)) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Invalid status selected.');
        redirect('turkey_on-process.php' . $preserveQSWithQuestion);
        exit;
    }
    if ($reportText === '' || mb_strlen($reportText) < 5) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Please provide a Brief reason (at least 5 characters).');
        redirect('turkey_on-process.php' . $preserveQSWithQuestion);
        exit;
    }

    $conn = $database->getConnection();
    
    // Get current status
    $fromStatus = null;
    $businessUnitId = null;
    try {
        $stmt = $conn->prepare("SELECT status, business_unit_id FROM applicants WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $fromStatus = (string)($row['status'] ?? '');
            $businessUnitId = (int)$row['business_unit_id'] ?? null;
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('Fetch applicant failed: ' . $e->getMessage());
    }

    if ($fromStatus === null) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Applicant not found.');
        redirect('turkey_on-process.php' . $preserveQSWithQuestion);
        exit;
    }

    // Update status and insert report
    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $buIdForReport = !empty($businessUnitId) ? $businessUnitId : 1;
    $ok = false;
    $errorMsg = '';

    try {
        $conn->begin_transaction();

        // Insert status report
        $stmt1 = $conn->prepare("INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param("iisssi", $id, $buIdForReport, $fromStatus, $to, $reportText, $adminId);
        
        if (!$stmt1->execute()) {
            throw new Exception("Failed to insert status report: " . $stmt1->error);
        }
        $stmt1->close();

        // Update status
        $stmt2 = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt2->bind_param("si", $to, $id);
        
        if (!$stmt2->execute()) {
            throw new Exception("Failed to update status: " . $stmt2->error);
        }
        $stmt2->close();

        $conn->commit();
        $ok = true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Status change failed: ' . $e->getMessage());
        $errorMsg = $e->getMessage();
        $ok = false;
    }

    if (function_exists('setFlashMessage')) {
        if ($ok) setFlashMessage('success', 'Status updated and report saved.');
        else setFlashMessage('error', 'Failed to update status or save report. ' . $errorMsg);
    }

    redirect('turkey_on-process.php' . $preserveQSWithQuestion);
    exit;
}

// Handle simple GET status update (no report required)
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['to'])) {
    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));
    
    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;
        $conn = $database->getConnection();
        $businessUnitId = null;
        $fromStatus = null;
        
        if ($conn instanceof mysqli) {
            if ($stmtCheck = $conn->prepare("SELECT status, business_unit_id FROM applicants WHERE id = ? LIMIT 1")) {
                $stmtCheck->bind_param("i", $id);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                $currentApp = $resCheck ? $resCheck->fetch_assoc() : null;
                if ($currentApp) {
                    $fromStatus = $currentApp['status'];
                    $businessUnitId = $currentApp['business_unit_id'];
                }
                $stmtCheck->close();
            }
        }
        
        if ($conn instanceof mysqli) {
            if ($stmt = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?")) {
                $stmt->bind_param("si", $to, $id);
                $updated = $stmt->execute();
                $stmt->close();
            }
        }
        
        if ($updated && isset($fromStatus) && $fromStatus !== $to) {
            $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
            $reportText = "Status changed from " . ucfirst(str_replace('_', ' ', $fromStatus)) . " to " . ucfirst(str_replace('_', ' ', $to));
            $buIdForReport = !empty($businessUnitId) ? $businessUnitId : 1;
            
            if ($conn instanceof mysqli) {
                if ($stmtReport = $conn->prepare("INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id) VALUES (?, ?, ?, ?, ?, ?)")) {
                    $stmtReport->bind_param("iisssi", $id, $buIdForReport, $fromStatus, $to, $reportText, $adminId);
                    $stmtReport->execute();
                    $stmtReport->close();
                }
            }
        }
        
        if (function_exists('setFlashMessage')) {
            setFlashMessage($updated ? 'success' : 'error', $updated ? 'Status updated successfully.' : 'Failed to update status.');
        }
    } else {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid status selected.');
        }
    }
    
    $qs = $preserveQSWithQuestion ?: '?';
    redirect('turkey_on-process.php' . $qs);
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $deleted = false;
    $conn = $database->getConnection();
    
    if ($conn instanceof mysqli) {
        if ($stmt = $conn->prepare("UPDATE applicants SET deleted_at = NOW(), status = 'deleted' WHERE id = ?")) {
            $stmt->bind_param("i", $id);
            $deleted = $stmt->execute();
            $stmt->close();
        }
    }
    
    if (function_exists('setFlashMessage')) {
        setFlashMessage($deleted ? 'success' : 'error', $deleted ? 'Applicant deleted successfully.' : 'Failed to delete applicant.');
    }
    
    $qs = $preserveQSWithQuestion ?: '?';
    redirect('turkey_on-process.php' . $qs);
    exit;
}

require_once $ADMIN_ROOT . '/includes/header.php';

// Get applicants with latest booking data for SMC business units
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$rows = [];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    $placeholders = implode(',', array_fill(0, count($smcBuIds), '?'));
    
    // Join with client_bookings to get booking info
    $sql = "SELECT 
                a.id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.suffix,
                a.phone_number,
                a.email,
                a.preferred_location,
                a.picture,
                a.status,
                a.created_at,
                a.business_unit_id,
                cb.client_first_name,
                cb.client_middle_name,
                cb.client_last_name,
                cb.client_phone,
                cb.client_email,
                cb.client_address,
                cb.appointment_type,
                cb.appointment_date,
                cb.appointment_time
            FROM applicants a
            LEFT JOIN (
                SELECT cb1.* 
                FROM client_bookings cb1
                INNER JOIN (
                    SELECT applicant_id, MAX(created_at) as max_created
                    FROM client_bookings
                    GROUP BY applicant_id
                ) cb2 ON cb1.applicant_id = cb2.applicant_id AND cb1.created_at = cb2.max_created
            ) cb ON a.id = cb.applicant_id
            WHERE a.business_unit_id IN ($placeholders) 
            AND a.status = 'on_process' 
            AND a.deleted_at IS NULL
            AND NOT EXISTS (
                SELECT 1 FROM blacklisted_applicants b
                WHERE b.applicant_id = a.id AND b.is_active = 1
            )";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

// Filter by search query
function filterRowsByQuery(array $rows, string $query): array {
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);
    
    return array_values(array_filter($rows, function(array $row) use ($needle) {
        $first = mb_strtolower((string)($row['first_name'] ?? ''));
        $middle = mb_strtolower((string)($row['middle_name'] ?? ''));
        $last = mb_strtolower((string)($row['last_name'] ?? ''));
        $email = mb_strtolower((string)($row['email'] ?? ''));
        $phone = mb_strtolower((string)($row['phone_number'] ?? ''));
        
        $cfn = mb_strtolower((string)($row['client_first_name'] ?? ''));
        $cmn = mb_strtolower((string)($row['client_middle_name'] ?? ''));
        $cln = mb_strtolower((string)($row['client_last_name'] ?? ''));
        $cem = mb_strtolower((string)($row['client_email'] ?? ''));
        $cph = mb_strtolower((string)($row['client_phone'] ?? ''));
        
        $applicantName = trim($first . ' ' . $middle . ' ' . $last);
        $clientName = trim($cfn . ' ' . $cmn . ' ' . $cln);
        
        $haystack = implode(' | ', [$first, $middle, $last, $applicantName, $email, $phone, $clientName, $cem, $cph]);
        
        return mb_strpos($haystack, $needle) !== false;
    }));
}

if ($q !== '') {
    $rows = filterRowsByQuery($rows, $q);
}

/** Helpers */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), fn($v) => is_string($v) && $v !== ''));
    if (empty($cities)) return 'N/A';
    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen) return $cities[0];
    return $full;
}
?>

<style>
    .status-group { display: inline-flex; gap: .5rem; padding: .5rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: rgba(255, 255, 255, .85); }
    .status-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .9rem; border-radius: .75rem; font-size: .875rem; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; background: #fff; }
    .status-btn--active { color: #fff; border-color: #4f46e5; background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%); }
    .filter-label { font-size: .75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
    
    /* Dropdown Fix */
    .table-card, .table-card .card-body, .table-card .table-responsive, .table-card table, .table-card thead, .table-card tbody, .table-card tr, .table-card th, .table-card td { overflow: visible !important; }
    .table-card { position: relative; z-index: 0; }
    td.actions-cell { position: relative; overflow: visible; white-space: nowrap; }
    .table-card tr.row-raised { position: relative; z-index: 1060; }
    .dd-modern .dropdown-menu { border-radius: .75rem; border: 1px solid #e5e7eb; box-shadow: 0 12px 28px rgba(15, 23, 42, .12); min-width: 180px; z-index: 9999 !important; }
    .dd-modern .dropdown-item { display:flex; align-items:center; gap:.5rem; padding:.55rem .9rem; font-weight:500; }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled, .dd-modern .dropdown-item:disabled { color:#9aa0a6; background:transparent; pointer-events:none; }
    .btn-status { border-radius: .75rem; }
    table.table-styled { margin-bottom: 0; }
    
    /* Modal styles */
    .status-modal .modal-header { border-bottom: none; padding-bottom: 0; }
    .status-modal .modal-footer { border-top: none; }
    .status-modal .app-card { border: 1px solid #eef2f7; background: #f8fafc; }
    .status-modal .badge-soft { display:inline-flex; align-items:center; gap:.35rem; padding:.15rem .5rem; border-radius:.5rem; font-size:.8rem; border: 1px solid #e2e8f0; background:#f8fafc; color:#334155; }
    .status-modal .counter { font-size:.8rem; color:#64748b; }
    .status-modal .form-text { color:#64748b; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-2 fw-semibold">SMC - On Process Applicants</h4>
        <div class="status-group">
            <a href="turkey_applicants.php" class="status-btn">All</a>
            <a href="turkey_pending.php" class="status-btn">Pending</a>
            <a href="turkey_on-process.php" class="status-btn status-btn--active">On Process</a>
            <a href="turkey_approved.php" class="status-btn">Hired</a>
        </div>
    </div>
</div>

<!-- Search bar -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="turkey_on-process.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Search on-process (applicant or client)..." value="<?php echo h($q); ?>" autocomplete="off">
            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="turkey_on-process.php"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-styled align-middle mb-0">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Applicant</th>
                        <th>Client</th>
                        <th>Interview</th>
                        <th>Date & Time</th>
                        <th>Applicant Contact</th>
                        <th>Client Contact</th>
                        <th>Date Applied</th>
                        <th style="width: 320px;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <?php if ($q === ''): ?>
                                    No applicants currently on process.
                                <?php else: ?>
                                    No results for "<strong><?php echo h($q); ?></strong>".
                                    <a href="turkey_on-process.php" class="ms-1">Clear search</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                                $id = (int)$row['id'];
                                $currentStatus = (string)($row['status'] ?? 'on_process');
                                
                                $viewUrl = 'view_onprocess.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $editUrl = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $deleteUrl = 'turkey_on-process.php?action=delete&id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                                
                                // Status change URLs
                                $toPendingUrl = 'turkey_on-process.php?action=update_status&id=' . $id . '&to=pending' . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $toOnProcessUrl = 'turkey_on-process.php?action=update_status&id=' . $id . '&to=on_process' . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $toApprovedUrl = 'turkey_on-process.php?action=update_status&id=' . $id . '&to=approved' . ($q !== '' ? '&q=' . urlencode($q) : '');
                                
                                // Client info
                                $clientName = trim(($row['client_first_name'] ?? '') . ' ' . ($row['client_middle_name'] ?? '') . ' ' . ($row['client_last_name'] ?? ''));
                                $clientName = $clientName !== '' ? $clientName : '—';
                                
                                // Interview info
                                $apptType = $row['appointment_type'] ?? '—';
                                $apptDate = (string)($row['appointment_date'] ?? '');
                                $apptTime = (string)($row['appointment_time'] ?? '');
                                $dateTimeDisplay = trim($apptDate . ' ' . $apptTime);
                                $dateTimeDisplay = $dateTimeDisplay !== '' ? $dateTimeDisplay : '—';
                                
                                // Contacts
                                $appEmail = $row['email'] ?? '';
                                $appContact = trim(($row['phone_number'] ?? '') . (!empty($appEmail) ? ' / ' . $appEmail : ''));
                                $appContact = $appContact !== '' ? $appContact : '—';
                                
                                $cliEmail = $row['client_email'] ?? '';
                                $cliContact = trim(($row['client_phone'] ?? '') . (!empty($cliEmail) ? ' / ' . $cliEmail : ''));
                                $cliContact = $cliContact !== '' ? $cliContact : '—';
                                
                                $applicantName = htmlspecialchars(getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']), ENT_QUOTES, 'UTF-8');
                                $photo = !empty($row['picture']) ? getFileUrl($row['picture']) : '';
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($row['picture'])): ?>
                                        <img src="<?php echo h(getFileUrl($row['picture'])); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <?php echo strtoupper(substr($row['first_name'] ?? '', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fw-semibold"><?php echo $applicantName; ?></div>
                                    <div class="text-muted small"><?php echo h(renderPreferredLocation($row['preferred_location'] ?? null)); ?></div>
                                </td>

                                <td>
                                    <div class="fw-semibold"><?php echo h($clientName); ?></div>
                                    <div class="text-muted small"><?php echo h($row['client_address'] ?? '—'); ?></div>
                                </td>

                                <td><?php echo h($apptType); ?></td>
                                <td><?php echo h($dateTimeDisplay); ?></td>
                                <td><?php echo h($appContact); ?></td>
                                <td><?php echo h($cliContact); ?></td>
                                <td><?php echo h(formatDate($row['created_at'])); ?></td>

                                <td class="actions-cell">
                                    <div class="btn-group dd-modern dropup">
                                        <!-- View -->
                                        <a href="<?php echo h($viewUrl); ?>" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                                        
                                        <!-- Edit -->
                                        <?php if ($canEdit): ?>
                                            <a href="<?php echo h($editUrl); ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <?php endif; ?>
                                        
                                        <!-- Delete -->
                                        <?php if ($canEdit): ?>
                                            <a href="<?php echo h($deleteUrl); ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this applicant?');"><i class="bi bi-trash"></i></a>
                                        <?php endif; ?>

                                        <!-- Change Status Dropdown -->
                                        <div class="dropdown dropup">
                                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Change Status" id="changeStatusBtn-<?php echo $id; ?>">
                                                <i class="bi bi-arrow-left-right me-1"></i> Change Status
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="changeStatusBtn-<?php echo $id; ?>">
                                                <li>
                                                    <a class="dropdown-item <?php echo $currentStatus === 'pending' ? 'disabled' : ''; ?> change-status" href="#" data-href="<?php echo h($toPendingUrl); ?>" data-id="<?php echo $id; ?>" data-from="<?php echo h($currentStatus); ?>" data-to="pending" data-applicant="<?php echo $applicantName; ?>" data-app-photo="<?php echo h($photo); ?>">
                                                        <i class="bi bi-hourglass-split text-warning"></i><span>Pending</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?php echo $currentStatus === 'on_process' ? 'disabled' : ''; ?> change-status" href="#" data-href="<?php echo h($toOnProcessUrl); ?>" data-id="<?php echo $id; ?>" data-from="<?php echo h($currentStatus); ?>" data-to="on_process" data-applicant="<?php echo $applicantName; ?>" data-app-photo="<?php echo h($photo); ?>">
                                                        <i class="bi bi-arrow-repeat text-info"></i><span>On-Process</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?php echo $currentStatus === 'approved' ? 'disabled' : ''; ?> change-status" href="#" data-href="<?php echo h($toApprovedUrl); ?>" data-id="<?php echo $id; ?>" data-from="<?php echo h($currentStatus); ?>" data-to="approved" data-applicant="<?php echo $applicantName; ?>" data-app-photo="<?php echo h($photo); ?>">
                                                        <i class="bi bi-check2-circle text-success"></i><span>Approved</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- === Status Change Report Modal === -->
<div class="modal fade status-modal" id="statusReportModal" tabindex="-1" aria-labelledby="statusReportModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <form method="post" action="turkey_on-process.php" id="statusReportForm" class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary bg-opacity-25 p-2 rounded-circle">
            <i class="bi bi-arrow-left-right text-primary fs-5"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="statusReportModalLabel">Change Status & Add Report</h5>
            <p class="text-muted small mb-0">This change will be recorded in the reports log.</p>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body py-3">
        <!-- Applicant header card -->
        <div class="card app-card mb-4">
          <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="photo-slot">
                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm d-none" id="srAvatarFallback" style="width:56px;height:56px;">
                  <span class="fs-5 fw-bold" id="srAvatarLetter">A</span>
                </div>
                <img src="" class="rounded-circle shadow-sm d-none" width="56" height="56" style="object-fit: cover;" alt="Photo" id="srAvatarImg">
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold fs-5" id="sr-applicant">Applicant Name</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="badge-soft"><i class="bi bi-arrow-repeat"></i><span id="sr-from">on process</span></span>
                  <i class="bi bi-arrow-right text-muted"></i>
                  <span class="badge-soft"><i class="bi bi-check2"></i><span id="sr-to">status</span></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="action" value="update_status_report">
        <input type="hidden" name="id" id="sr-id" value="">
        <input type="hidden" name="to" id="sr-to-val" value="">
        <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">

        <div class="row g-4">
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Reason</label>
            <select class="form-select form-select-lg" id="sr-reason">
              <option value="" selected>Select a reason (optional)</option>
              <option value="Interview rescheduled">Interview rescheduled</option>
              <option value="Client confirmed / Ready">Client confirmed / Ready</option>
              <option value="Requirements complete">Requirements complete</option>
              <option value="Passed interview / assessment">Passed interview / assessment</option>
              <option value="Other">Other</option>
            </select>
            <div class="form-text mt-1">Pick a quick label; you can add details on the right.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold d-flex justify-content-between">
              <span>Description <span class="text-danger">*</span></span>
              <span class="counter badge bg-light text-secondary" id="sr-counter">0/1000</span>
            </label>
            <textarea class="form-control" id="sr-text" name="report_text" rows="4" maxlength="1000" required placeholder="Write details for this status change..."></textarea>
            <div class="form-text mt-1">Minimum 5 characters. This will be stored in the reports log.</div>
          </div>
        </div>

        <div class="alert alert-info bg-info bg-opacity-10 border-0 mt-3 mb-0">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill text-info mt-1"></i>
            <div class="small"><strong>Note:</strong> The applicant's status will be updated and this action recorded in Reports.</div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal"><i class="bi bi-x-lg me-1"></i> Cancel</button>
        <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-check2-square me-2"></i> Save & Update Status</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdowns
    var btns = document.querySelectorAll('.btn-status[data-bs-toggle="dropdown"]');
    btns.forEach(function(btn) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            new bootstrap.Dropdown(btn, { boundary: 'viewport', popperConfig: { strategy: 'fixed' } });
        }
    });

    var modalEl = document.getElementById('statusReportModal');
    var modal = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(modalEl) : null;

    // Modal elements
    var applicantNameEl = document.getElementById('sr-applicant');
    var fromBadgeEl = document.getElementById('sr-from');
    var toBadgeEl = document.getElementById('sr-to');
    var idInput = document.getElementById('sr-id');
    var toInput = document.getElementById('sr-to-val');
    var reasonSelect = document.getElementById('sr-reason');
    var descTA = document.getElementById('sr-text');
    var counterEl = document.getElementById('sr-counter');
    var avatarImg = document.getElementById('srAvatarImg');
    var avatarFallback = document.getElementById('srAvatarFallback');
    var avatarLetter = document.getElementById('srAvatarLetter');

    // Counter
    var updateCounter = function() {
        var max = parseInt(descTA.getAttribute('maxlength') || '1000', 10);
        counterEl.textContent = (descTA.value.length) + '/' + max;
    };
    descTA.addEventListener('input', updateCounter);
    updateCounter();

    // Open modal (require report only when FROM on_process and TO != from)
    document.querySelectorAll('.change-status').forEach(function(el) {
        el.addEventListener('click', function(ev) {
            ev.preventDefault();
            if (el.classList.contains('disabled')) return;

            var fromSt = (el.dataset.from || '').toLowerCase();
            var toSt = (el.dataset.to || '').toLowerCase();
            var href = el.dataset.href || '#';
            var id = el.dataset.id || '';
            var name = el.dataset.applicant || '';
            var photo = el.dataset.appPhoto || '';

            // Require a report when changing FROM on_process
            if (fromSt === 'on_process' && toSt !== fromSt) {
                idInput.value = id;
                toInput.value = toSt;
                applicantNameEl.textContent = name;
                fromBadgeEl.textContent = fromSt.replace('_', ' ');
                toBadgeEl.textContent = toSt.replace('_', ' ');

                // Photo or fallback
                if (photo && photo.trim() !== '') {
                    avatarImg.src = photo;
                    avatarImg.classList.remove('d-none');
                    avatarFallback.classList.add('d-none');
                } else {
                    var firstLetter = (name.trim().charAt(0) || 'A').toUpperCase();
                    avatarLetter.textContent = firstLetter;
                    avatarImg.classList.add('d-none');
                    avatarFallback.classList.remove('d-none');
                }

                reasonSelect.value = '';
                descTA.value = '';
                updateCounter();

                if (modal) modal.show();
                return;
            }

            // Otherwise, follow the GET link
            window.location.href = href;
        });
    });

    // When submitting, if a Reason is chosen and not already in the text, prefix it
    document.getElementById('statusReportForm').addEventListener('submit', function() {
        var reason = reasonSelect.value.trim();
        var text = descTA.value.trim();
        if (reason && reason.toLowerCase() !== 'other') {
            if (text.toLowerCase().indexOf(reason.toLowerCase()) !== 0) {
                descTA.value = reason + (text ? ': ' + text : '');
            }
        }
    });

    // Autofocus
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function(){
            reasonSelect.focus();
        });
    }
});
</script>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>

