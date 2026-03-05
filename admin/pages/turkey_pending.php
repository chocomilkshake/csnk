<?php
// FILE: admin/pages/turkey_pending.php (SMC - Turkey Pending Applicants)
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
// SMC employees can only see SMC, admins/super_admins can see all
if (!$auth->canSeeSMC()) {
    // User doesn't have SMC access - redirect to main applicants page
    header('Location: applicants.php');
    exit;
}

$conn = $database->getConnection();

/** -----------------------------------------------------------------
 *  Compute/prepare values that do NOT output HTML
 *  ----------------------------------------------------------------- */
$smcBuId = 0;
if ($conn instanceof mysqli) {
    $sqlFindSMCBu = "SELECT bu.id
                     FROM business_units bu
                     JOIN agencies ag ON ag.id = bu.agency_id
                     WHERE ag.code = 'smc' AND bu.active = 1
                     ORDER BY bu.id ASC
                     LIMIT 1";
    if ($stmt = $conn->prepare($sqlFindSMCBu)) {
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!empty($row['id'])) {
            $smcBuId = (int) $row['id'];
            // Store SMC BU ID in separate session variable to avoid overwriting CSNK BU
            $_SESSION['smc_bu_id'] = $smcBuId;
        }
    }
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

// Build preserved query string but EXCLUDE action parameters that cause loops
$filterOutKeys = ['page', 'action', 'id', 'to', 'csrf'];
$preserveQS = '';
if (!empty($_GET)) {
    $kept = array_filter(
        $_GET,
        function ($v, $k) use ($filterOutKeys) {
            return !in_array($k, $filterOutKeys, true) && $v !== '' && $v !== null;
        },
        ARRAY_FILTER_USE_BOTH
    );
    if (!empty($kept)) {
        $preserveQS = '&' . http_build_query($kept);
    }
}
$preserveQSWithQuestion = !empty($preserveQS) ? ('?' . ltrim($preserveQS, '&')) : '';

// CSRF token (and we'll require it on actions)
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = bin2hex((string)mt_rand());
    }
}
$csrf = $_SESSION['csrf_token'] ?? '';

/** -----------------------------------------------------------------
 *  ACTION HANDLERS — run BEFORE any output
 *  ----------------------------------------------------------------- */
$allowedStatuses = ['pending', 'on_process', 'approved'];

// Handle status update action
if (
    isset($_GET['action'], $_GET['id'], $_GET['to'])
    && $_GET['action'] === 'update_status'
) {
    // Basic CSRF check for GET action (since we add ?csrf=)
    $csrfOk = isset($_GET['csrf']) && hash_equals($csrf, (string)$_GET['csrf']);

    if (!$csrfOk) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid request token.');
        }
        $qs = $preserveQSWithQuestion ?: '?';
        redirect('turkey_pending.php' . $qs);
        exit;
    }

    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));

    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;
        $businessUnitId = null;
        $fromStatus = null;

        if ($conn instanceof mysqli) {
            // Get current status and business_unit_id
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

            // Update status
            if ($stmt = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?")) {
                $stmt->bind_param("si", $to, $id);
                $updated = $stmt->execute();
                $stmt->close();
            }

            // Record status change
            if ($updated && isset($fromStatus) && $fromStatus !== $to) {
                $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                $reportText = "Status changed from " . ucfirst(str_replace('_', ' ', $fromStatus))
                            . " to " . ucfirst(str_replace('_', ' ', $to));
                $buIdForReport = ($businessUnitId !== null) ? $businessUnitId : 1;

                if ($stmtReport = $conn->prepare(
                    "INSERT INTO applicant_status_reports
                     (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                     VALUES (?, ?, ?, ?, ?, ?)"
                )) {
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

    // Redirect back to this listing WITHOUT the action params to avoid loops
    $qs = $preserveQSWithQuestion ?: '?';
    redirect('turkey_pending.php' . $qs);
    exit;
}

// Handle delete action
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $csrfOk = isset($_GET['csrf']) && hash_equals($csrf, (string)$_GET['csrf']);
    if (!$csrfOk) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid request token.');
        }
        $qs = $preserveQSWithQuestion ?: '?';
        redirect('turkey_pending.php' . $qs);
        exit;
    }

    $id = (int)$_GET['id'];
    $deleted = false;

    if ($con
}

/** ------------------
    return $full;
}
?>
<style>

    }
    .country-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .75rem;
        border-radius: .75rem;
        font-size: .8rem;
        
    .filter-label {
        font-size: .75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: .25rem;
    }

    /* Dropdown 
    .table-card { position: relative; z-index: 0; }
    td.actions-cell { position: relative; overflow: visible; white-space: nowrap; }
    .table-card tr.row-raised { position: relative; z-index: 1060; }

    .dd-m; gap:.5rem; padding:.55rem .9rem; font
                        <a href="turkey_pending.php?country=<?php echo (int) $c['id']; ?>"
                 -flex" role="search"
                style="max-width: 460px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search applicants..."
                        value="<?php echo h($q); ?>">
                    <input 
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
                    </thead>MC pages
                                $canEdit = ($isSuperAdmin || $isAdmin || $isEmployee);
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($app['picture'])): ?>
                                            <img src="<?php echo h(getFileUrl($app['picture'])); ?>" alt="Photo" class="rounded"
                                                width="50" height="50" style="object-fit: cover;">
                             pp['id']; ?><?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"
                                                    class="btn btn-sm btn-danger"
         
    </div>
</div>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>