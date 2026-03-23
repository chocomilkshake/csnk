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
require_once $ADMIN_ROOT . '/includes/smc_filter_bar.php';

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

// --- Boot filter state ---
$smcState = smc_filter_boot([
    'base_url'         => 'turkey_on-process.php',
    'session_ns'       => 'smc_turkey_onprocess',
    'allowed_statuses' => ['pending', 'on_process', 'approved'],
    'default_status'   => 'on_process',
    'buId'             => $_SESSION['current_bu_id'] ?? null,
    'not_deleted'      => true,
    'not_blacklisted'  => true,
]);

$q       = (string)($smcState['q'] ?? '');
$status  = (string)($smcState['status'] ?? 'all');
$country = (string)($smcState['country'] ?? 'all');

// --- (4.1) counts ---
$counts = ['all' => 0, 'pending' => 0, 'on_process' => 0, 'approved' => 0];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    $buPh   = implode(',', array_fill(0, count($smcBuIds), '?'));
    $where  = [];
    $types  = str_repeat('i', count($smcBuIds));
    $params = $smcBuIds;

    $where[] = "a.business_unit_id IN ($buPh)";
    $where[] = "a.deleted_at IS NULL";
    $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = a.id AND b.is_active = 1
    )";

    if ($country !== 'all') {
        // Adjust to your column if needed
        $where[] = "a.country_id = ?";
        $types   .= 'i';
        $params[] = (int)$country;
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . ")";
        $types  .= 'sss';
        array_push($params, $like, $like, $like);
    }

    $where[] = "a.status IN ('pending','on_process','approved')";
    $whereSql = implode(' AND ', $where);

    $sqlCounts = "SELECT a.status, COUNT(*) AS cnt
                  FROM applicants a
                  WHERE $whereSql
                  GROUP BY a.status";

    if ($stmt = $conn->prepare($sqlCounts)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $total = 0;
        while ($row = $res->fetch_assoc()) {
            $st  = (string)$row['status'];
            $cnt = (int)$row['cnt'];
            if (in_array($st, ['pending','on_process','approved'], true)) {
                $counts[$st] = $cnt;
                $total += $cnt;
            }
        }
        $counts['all'] = $total;
        $stmt->close();
    }
}

$smcState['counts'] = $counts;

// --- (4.2) country counts ---
$countriesWithCounts = [];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    $buPh   = implode(',', array_fill(0, count($smcBuIds), '?'));
    $where  = [];
    $types  = str_repeat('i', count($smcBuIds));
    $params = $smcBuIds;

    $where[] = "a.business_unit_id IN ($buPh)";
    $where[] = "a.deleted_at IS NULL";
    $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = a.id AND b.is_active = 1
    )";

    if ($status === 'all') {
        $where[] = "a.status IN ('pending','on_process','approved')";
    } else {
        $where[] = "a.status = ?";
        $types   .= 's';
        $params[] = $status;
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . ")";
        $types  .= 'sss';
        array_push($params, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);

    $sqlCountries = "
        SELECT COALESCE(c.id, 0) AS id,
               COALESCE(c.name, 'Unspecified') AS name,
               COUNT(*) AS count
        FROM applicants a
        LEFT JOIN countries c ON c.id = a.country_id
        WHERE $whereSql
        GROUP BY COALESCE(c.id, 0), COALESCE(c.name, 'Unspecified')
        ORDER BY name ASC
    ";

    if ($stmt = $conn->prepare($sqlCountries)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $countriesWithCounts[] = [
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
                               <?php
$labelMap = ['all' => 'All', 'pending' => 'Pending', 'on_process' => 'On-Process', 'approved' => 'Approved'];
$curStatusLabel = $labelMap[$status] ?? ucfirst(str_replace('_',' ', $status));
$clearHref = 'turkey_on-process.php' . ($smcState['preserveQSWithQuestion'] ? '?' : '');
?>
<?php if ($q === ''): ?>
    No applicants found for <strong><?php echo h($curStatusLabel); ?></strong><?php if ($country !== 'all') echo ' in selected country'; ?>.
<?php else: ?>
    No results for "<strong><?php echo h($q); ?></strong>" under <strong><?php echo h($curStatusLabel); ?></strong><?php if ($country !== 'all') echo ' in selected country'; ?>.
    turkey_on-process.phpClear filters</a>
<?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            
                                $qsAppend = $smcState['preserveQS'] ?? ''; // e.g., "&q=...&status=...&country=..."
                                $csrfQS   = '&csrf_token=' . urlencode($csrf); // optional but recommended for GET actions

                                $id = (int)$row['id'];
                    avatarImg.classList.remove('d-none');
                    avatarFallback.classList.add('d-none');

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

