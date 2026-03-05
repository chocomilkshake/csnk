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
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
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
    $csrfOk = isset($_GET['csrf']) && hash_equals($csrf, (string)$_GET['csrf']);

    if (!$csrfOk) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid request token.');
        }
        $qs = $preserveQSWithQuestion ?: '?';
        redirect('turkey_on-process.php' . $qs);
        exit;
    }

    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));

    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;
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

            if ($stmt = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?")) {
                $stmt->bind_param("si", $to, $id);
                $updated = $stmt->execute();
                $stmt->close();
            }

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

    $qs = $preserveQSWithQuestion ?: '?';
    redirect('turkey_on-process.php' . $qs);
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
        redirect('turkey_on-process.php' . $qs);
        exit;
    }

    $id = (int)$_GET['id'];
    $deleted = false;
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

/** -----------------------------------------------------------------
 *  Only now include header / output HTML
 *  ----------------------------------------------------------------- */
require_once $ADMIN_ROOT . '/includes/header.php';
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';

$applicant   = new Applicant($database);
$currentBuId = (int) ($_SESSION['current_bu_id'] ?? 0);
$smcBuId     = (int) ($_SESSION['smc_bu_id'] ?? 0);

// Roles (ensure $isAdmin exists)
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin      = ($currentRole === 'admin');
$isEmployee   = ($currentRole === 'employee');

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
    .dropdown-item:hover
            </form>
        </div>
    </div>
                        <?php if (empty($applicants)): ?>
                            <tr><td colspan="8" class="text-center text-muted py-5">No on-process applicants found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <?php
                                $canEdit = ($isSuperAdmin || $isAdmin || $isEmployee);
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($app['picture'])): ?>
                                            <img src="<?php echo h(getFileUrl($app['picture'])); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="widthapp['suffix'])); ?></strong></td>
                                    <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                                    <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                            
                                            
                                            <!-- Change Status Dropdown -->
                                            <div class="dropdown">
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status" data-bs-toggle="dropdown" data-bs-auto-close="true" data-bs-display="static" data-bs-offset="0,8" aria-expanded="false" aria-haspopup="true" title="Change Status" id="changeStatusBtn-<?php echo (int)$app['id']; ?>"><i class="bi bi-arrow-left-right me-1"></i> Change Status</button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="changeStatusBtn-<?php echo (int)$app['id']; ?>">
                                                    <li><a class="dropdown-item <?php echo ($app['status'] === 'pending') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo (int)$app['id']; ?>&to=pending<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"><i class="bi bi-hourglass-split text-warning"></i><span>Pending</span></a></li>
                                                    <li><a class="dropdown-item <?php echo ($app['status'] === 'on_process') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo (int)$app['id']; ?>&to=on_process<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"><i class="bi bi-arrow-repeat text-info"></i><span>On Process</span></a></li>
                                                    <li><a class="dropdown-item <?php echo ($app['status'] === 'approved') ? 'disabled' : ''; ?>" href="turkey_on-process.php?action=update_status&id=<?php echo (int)$app['id']; ?>&to=approved<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"><i class="bi bi-check2-circle text-success"></i><span>Approved</span></a></li>
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

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>