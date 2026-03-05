
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

$conn = $database->getConnection();

$smcBuId = 0;
if ($conn instanceof mysqli) {
    $sqlFindSMCBu = "SELECT bu.id FROM business_units bu JOIN agencies ag ON ag.id = bu.agency_id WHERE ag.code = 'smc' AND bu.active = 1 ORDER BY bu.id ASC LIMIT 1";
    if ($stmt = $conn->prepare($sqlFindSMCBu)) {
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!empty($row['id'])) {
            $smcBuId = (int) $row['id'];
            $_SESSION['smc_bu_id'] = $smcBuId;
        }
    }
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

require_once $ADMIN_ROOT . '/includes/header.php';
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';

$applicant = new Applicant($database);
$currentBuId = (int) ($_SESSION['current_bu_id'] ?? 0);
$smcBuId = (int) ($_SESSION['smc_bu_id'] ?? 0);

$isSuperAdmin = ($currentRole === 'super_admin');
$isEmployee = ($currentRole === 'employee');

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}
$csrf = $_SESSION['csrf_token'];

// Preserve query string
$preserveQS = !empty($_GET) ? ('&' . http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY))) : '';
$preserveQSWithQuestion = !empty($preserveQS) ? ('?' . ltrim($preserveQS, '&')) : '';

// Allowed statuses to transition to
$allowedStatuses = ['pending', 'on_process', 'approved'];

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
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Please provide a brief reason (at least 5 characters).');
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
            $businessUnitId = $row['business_unit_id'] ?? null;
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
    $buIdForReport = ($businessUnitId !== null) ? $businessUnitId : 1;
    $ok = false;

    try {
        $conn->begin_transaction();

        // Insert status report
        $stmt1 = $conn->prepare("INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param("iisssi", $id, $buIdForReport, $fromStatus, $to, $reportText, $adminId);
        $stmt1->execute();
        $stmt1->close();

        // Update status
        $stmt2 = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt2->bind_param("si", $to, $id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        $ok = true;
    } catch (Throwable $e) {
        $conn->rollback();
        error_log('Status change failed: ' . $e->getMessage());
        $ok = false;
    }

    if (function_exists('setFlashMessage')) {
        if ($ok) setFlashMessage('success', 'Status updated and report saved.');
        else setFlashMessage('error', 'Failed to update status or save report.');
    }

    if ($ok && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
        $fullName = null;
        if (method_exists($applicant, 'getById')) {
            $row = $applicant->getById($id);
            if (is_array($row)) {
                $fullName = getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? '');
            }
        }
        $label = $fullName ?: "ID {$id}";
        $auth->logActivity((int)$_SESSION['admin_id'], 'Update Applicant Status (SMC)', "Updated status for {$label} → {$to}");
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
            $buIdForReport = ($businessUnitId !== null) ? $businessUnitId : 1;
            
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

$country = $_GET['country'] ?? 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = 'on_process';

$buScope = null;
$countryId = ($country !== 'all') ? (int) $country : null;
$notDeleted = true;
$notBlacklisted = true;

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$pageSize = 25;

$applicants = $applicant->getApplicants($buScope, $countryId, $status, $q, $notDeleted, $notBlacklisted, $page, $pageSize);
$totalApplicants = $applicant->getApplicantsCount($buScope, $countryId, $status, $q, $notDeleted, $notBlacklisted);
$totalPages = ceil($totalApplicants / $pageSize);

$countriesWithCounts = $applicant->getCountriesWithCounts($buScope, $status, $q, $notDeleted, $notBlacklisted);

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
    .country-group { display: inline-flex; gap: .5rem; padding: .5rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: rgba(255, 255, 255, .85); }
    .country-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: .75rem; font-size: .8rem; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; background: #fff; }
    .country-btn--active { color: #fff; border-color: #059669; background: linear-gradient(180deg, #10b981 0%, #059669 100%); }
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

<div class="container-fluid px-2">
    <div class="row align-items-center justify-content-between mb-3">
        <div class="col-auto">
            <h4 class="mb-2 fw-semibold">SMC - On Process Applicants</h4>
            <div class="status-group">
                <a href="turkey_applicants.php" class="status-btn">All</a>
                <a href="turkey_pending.php" class="status-btn">Pending</a>
                <a href="turkey_on-process.php" class="status-btn status-btn--active">On Process</a>
                <a href="turkey_approved.php" class="status-btn">Hired</a>
            </div>
        </div>
        <?php if (!empty($countriesWithCounts)): ?>
            <div class="col-12 mt-2">
                <div class="filter-label">Filter by Country</div>
                <div class="country-group">
                    <a href="turkey_on-process.php" class="country-btn <?php echo $country === 'all' ? 'country-btn--active' : ''; ?>">All</a>
                    <?php foreach ($countriesWithCounts as $c): ?>
                        <a href="turkey_on-process.php?country=<?php echo (int)$c['id']; ?>" class="country-btn <?php echo $country === (string)$c['id'] ? 'country-btn--active' : ''; ?>"><?php echo h($c['name']); ?> (<?php echo (int)$c['count']; ?>)</a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <form method="get" action="turkey_on-process.php" class="d-flex" role="search" style="max-width: 460px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search applicants..." value="<?php echo h($q); ?>">
                    <input type="hidden" name="country" value="<?php echo h($country); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($q !== ''): ?>
                        <a class="btn btn-outline-secondary" href="turkey_on-process.php?country=<?php echo h($country); ?>"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Date Applied</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($applicants)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">No on-process applicants found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <?php
                                $canEdit = ($isSuperAdmin || $isAdmin || $isEmployee);
                                $appId = (int)$app['id'];
                                $appStatus = (string)($app['status'] ?? 'on_process');
                                $fullName = getFullName($app['first_name'] ?? '', $app['middle_name'] ?? '', $app['last_name'] ?? '', $app['suffix'] ?? '');
                                $photo = !empty($app['picture']) ? getFileUrl($app['picture']) : '';
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($app['picture'])): ?>
                                            <img src="<?php echo h(getFileUrl($app['picture'])); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo h($fullName); ?></strong></td>
                                    <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                                    <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
                                    <td><span class="badge bg-info">On Process</span></td>
                                    <td><?php echo h(formatDate($app['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group dropup dd-modern">
                                            <a href="view-applicant.php?id=<?php echo $appId; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                            <?php if ($canEdit): ?>
                                                <a href="edit-applicant.php?id=<?php echo $appId; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
                                                <a href="turkey_on-process.php?action=delete&id=<?php echo $appId; ?><?php echo $preserveQS; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this applicant?');"><i class="bi bi-trash"></i></a>
                                            <?php endif; ?>
                                            
                                            <!-- Change Status Dropdown - opens modal when changing from on_process -->
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-display="static" data-bs-offset="0,8" aria-expanded="false" aria-haspopup="true" title="Change Status" id="changeStatusBtn-<?php echo $appId; ?>"><i class="bi bi-arrow-left-right me-1"></i> Change Status</button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="changeStatusBtn-<?php echo $appId; ?>">
                                                    <li>
                                                        <a class="dropdown-item change-status <?php echo ($appStatus === 'pending') ? 'disabled' : ''; ?>" 
                                                           href="#"
                                                           data-id="<?php echo $appId; ?>"
                                                           data-from="on_process"
                                                           data-to="pending"
                                                           data-applicant="<?php echo h($fullName); ?>"
                                                           data-photo="<?php echo h($photo); ?>">
                                                            <i class="bi bi-hourglass-split text-warning"></i><span>Pending</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item <?php echo ($appStatus === 'on_process') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo $appId; ?>&to=on_process<?php echo $preserveQS; ?>">
                                                            <i class="bi bi-arrow-repeat text-info"></i><span>On Process</span>
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item change-status <?php echo ($appStatus === 'approved') ? 'disabled' : ''; ?>"
                                                           href="#"
                                                           data-id="<?php echo $appId; ?>"
                                                           data-from="on_process"
                                                           data-to="approved"
                                                           data-applicant="<?php echo h($fullName); ?>"
                                                           data-photo="<?php echo h($photo); ?>">
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
</div>

<!-- === Status Change Report Modal (SMC Version) === -->
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
    // Dropdown fix
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

    // Open modal handler
    document.querySelectorAll('.change-status').forEach(function(el) {
        el.addEventListener('click', function(ev) {
            ev.preventDefault();
            if (el.classList.contains('disabled')) return;

            var fromSt = (el.dataset.from || '').toLowerCase();
            var toSt = (el.dataset.to || '').toLowerCase();
            var id = el.dataset.id || '';
            var name = el.dataset.applicant || '';
            var photo = el.dataset.photo || '';

            // Only require report when changing FROM on_process
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

            // For other transitions, redirect directly
            window.location.href = el.getAttribute('href');
        });
    });

    // Submit - prepend reason to description
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