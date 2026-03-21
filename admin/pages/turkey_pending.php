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

// Grab ALL active SMC BU IDs to enforce SMC-only scope
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
            $smcBuIds[] = (int) $r['id'];
        }
    }
}
if (!empty($smcBuIds)) {
    // Optional: store first SMC BU ID in session
    $_SESSION['smc_bu_id'] = $smcBuIds[0];
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
        $_SESSION['csrf_token'] = bin2hex((string) mt_rand());
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
    $csrfOk = isset($_GET['csrf']) && hash_equals($csrf, (string) $_GET['csrf']);

    if (!$csrfOk) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid request token.');
        }
        $qs = $preserveQSWithQuestion ?: '?';
        redirect('turkey_pending.php' . $qs);
        exit;
    }

    $id = (int) $_GET['id'];
    $to = strtolower(trim((string) $_GET['to']));

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
                $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
                $reportText = "Status changed from " . ucfirst(str_replace('_', ' ', $fromStatus))
                    . " to " . ucfirst(str_replace('_', ' ', $to));
                $buIdForReport = ($businessUnitId !== null) ? $businessUnitId : 1;

                if (
                    $stmtReport = $conn->prepare(
                        "INSERT INTO applicant_status_reports
                     (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                     VALUES (?, ?, ?, ?, ?, ?)"
                    )
                ) {
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
    $csrfOk = isset($_GET['csrf']) && hash_equals($csrf, (string) $_GET['csrf']);
    if (!$csrfOk) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid request token.');
        }
        $qs = $preserveQSWithQuestion ?: '?';
        redirect('turkey_pending.php' . $qs);
        exit;
    }

    $id = (int) $_GET['id'];
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
    redirect('turkey_pending.php' . $qs);
    exit;
}

/** -----------------------------------------------------------------
 *  Only now include header / output HTML
 *  ----------------------------------------------------------------- */
require_once $ADMIN_ROOT . '/includes/header.php';
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';

$applicant = new Applicant($database);
$currentBuId = (int) ($_SESSION['current_bu_id'] ?? 0);

// For SMC pages, use SMC BU instead of the CSNK BU from session
$smcBuId = (int) ($_SESSION['smc_bu_id'] ?? 0);

// Roles (ensure $isAdmin exists)
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin = ($currentRole === 'admin');
$isEmployee = ($currentRole === 'employee');

$buScope = null;
// SMC Filter Bar
require_once $ADMIN_ROOT . '/includes/smc_filter_bar.php';

$buScope = (int) ($_SESSION['smc_bu_id'] ?? 0); // or just use $smcBuId
$smcState = smc_filter_boot([
    'base_url' => 'turkey_pending.php',
    'session_ns' => 'smc_turkey_pending',
    'allowed_statuses' => ['all', 'pending', 'on_process', 'approved'],
    // You can pass current BU here, but enforce SMC scope in SQL below
    'buId' => $_SESSION['current_bu_id'] ?? null,
    'not_deleted' => true,
    'not_blacklisted' => true,
    // Optionally enforce a default status on this page if your filter supports it:
    // 'default_status' => 'pending',
]);
$q = (string) ($smcState['q'] ?? '');
$status = (string) ($smcState['status'] ?? 'all');
$country = (string) ($smcState['country'] ?? 'all');

// ---- #3: Status counts (all/pending/on_process/approved) ----
$counts = ['all' => 0, 'pending' => 0, 'on_process' => 0, 'approved' => 0];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    $buPh = implode(',', array_fill(0, count($smcBuIds), '?'));
    $where = [];
    $types = str_repeat('i', count($smcBuIds));
    $params = $smcBuIds;

    $where[] = "a.business_unit_id IN ($buPh)";
    $where[] = "a.deleted_at IS NULL";
    $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = a.id AND b.is_active = 1
    )";

    if ($country !== 'all') {
        $where[] = "a.country_id = ?";
        $types .= 'i';
        $params[] = (int) $country;
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . ")";
        $types .= 'sss';
        array_push($params, $like, $like, $like);
    }

    // Count only these statuses
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
            $st = (string) $row['status'];
            $cnt = (int) $row['cnt'];
            if (in_array($st, ['pending', 'on_process', 'approved'], true)) {
                $counts[$st] = $cnt;
                $total += $cnt;
            }
        }
        $counts['all'] = $total;
        $stmt->close();
    }
}

$smcState['counts'] = $counts;

// ---- #4: Countries with counts (respecting filters) ----
$countriesWithCounts = [];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    $buPh = implode(',', array_fill(0, count($smcBuIds), '?'));
    $where = [];
    $types = str_repeat('i', count($smcBuIds));
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
        $types .= 's';
        $params[] = $status;
    }

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . ")";
        $types .= 'sss';
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
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'count' => (int) $row['count'],
            ];
        }
        $stmt->close();
    }
}

$smcState['countriesWithCounts'] = $countriesWithCounts;

// ---- #5: Fetch rows (SMC-only + filters) ----
$rows = [];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    $buPlaceholders = implode(',', array_fill(0, count($smcBuIds), '?'));

    $where = [];
    $types = '';
    $params = [];

    // SMC BU restriction
    $where[] = "a.business_unit_id IN ($buPlaceholders)";
    $types .= str_repeat('i', count($smcBuIds));
    array_push($params, ...$smcBuIds);

    // Status: if you want "Pending page" to always be pending, replace this block with:
    // $where[] = "a.status = 'pending'";
    if ($status !== 'all') {
        $where[] = "a.status = ?";
        $types .= 's';
        $params[] = $status;
    } else {
        $where[] = "a.status IN ('pending','on_process','approved')";
    }

    // Country
    if ($country !== 'all') {
        $where[] = "a.country_id = ?";
        $types .= 'i';
        $params[] = (int) $country;
    }

    // Not deleted / not blacklisted
    $where[] = "a.deleted_at IS NULL";
    $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = a.id AND b.is_active = 1
    )";

    // Search (applicant + latest booking fields)
    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . " OR CONCAT_WS(' ', cb.client_first_name, cb.client_middle_name, cb.client_last_name) LIKE ?"
            . " OR cb.client_email LIKE ?"
            . " OR cb.client_phone LIKE ?"
            . ")";
        $types .= 'ssssss';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);

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
            WHERE $whereSql
            ORDER BY a.created_at DESC";

    if ($stmt = $conn->prepare($sql)) {
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

function renderPreferredLocation(?string $json, int $maxLen = 30): string
{
    if (empty($json))
        return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), fn($v) => is_string($v) && $v !== ''));
    if (empty($cities))
        return 'N/A';
    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen)
        return $cities[0];
    return $full;
}
?>
<style>
    .status-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .85);
    }

    .status-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .9rem;
        border-radius: .75rem;
        font-size: .875rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #cbd5e1;
        color: #334155;
        background: #fff;
    }

    .status-btn--active {
        color: #fff;
        border-color: #4f46e5;
        background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);
    }

    .country-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .85);
    }

    .country-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .75rem;
        border-radius: .75rem;
        font-size: .8rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #cbd5e1;
        color: #334155;
        background: #fff;
    }

    .country-btn--active {
        color: #fff;
        border-color: #059669;
        background: linear-gradient(180deg, #10b981 0%, #059669 100%);
    }

    .filter-label {
        font-size: .75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: .25rem;
    }

    /* Dropdown Fix: prevent clipping + ensure stacking above other rows */
    .table-card,
    .table-card .card-body,
    .table-card .table-responsive,
    .table-card table,
    .table-card thead,
    .table-card tbody,
    .table-card tr,
    .table-card th,
    .table-card td {
        overflow: visible !important;
    }

    .table-card {
        position: relative;
        z-index: 0;
    }

    td.actions-cell {
        position: relative;
        overflow: visible;
        white-space: nowrap;
    }

    .table-card tr.row-raised {
        position: relative;
        z-index: 1060;
    }

    .dd-modern .dropdown-menu {
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        min-width: 180px;
        z-index: 9999 !important;
    }

    .dd-modern .dropdown-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .55rem .9rem;
        font-weight: 500;
    }

    .dd-modern .dropdown-item .bi {
        font-size: 1rem;
        opacity: .9;
    }

    .dd-modern .dropdown-item:hover {
        background-color: #f8fafc;
    }

    .dd-modern .dropdown-item.disabled,
    .dd-modern .dropdown-item:disabled {
        color: #9aa0a6;
        background: transparent;
        pointer-events: none;
    }

    .btn-status {
        border-radius: .75rem;
    }

    table.table-styled {
        margin-bottom: 0;
    }
</style>
<div class="container-fluid px-2">
    <?php smc_filter_render($smcState); ?>
    <div class="row align-items-center justify-content-between mb-3">

        <div class="row mb-2">
            <div class="col-12 d-flex justify-content-end">
                <form method="get" action="turkey_pending.php" class="d-flex" role="search"
                    style="max-width: 460px; width: 100%;">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search applicants..."
                            value="<?php echo h($q); ?>">
                        <input type="hidden" name="country" value="<?php echo h($country); ?>">
                        <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                        <?php if ($q !== ''): ?>
                            <a class="btn btn-outline-secondary"
                                href="turkey_pending.php?country=<?php echo h($country); ?>"><i class="bi bi-x-lg"></i></a>
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
                            <?php if (empty($rows)): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">No applicants found for the selected
                                        filters.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rows as $app): ?>
                                    <?php
                                    // Roles: super_admin/admin/employee can edit in SMC pages
                                    $canEdit = ($isSuperAdmin || $isAdmin || $isEmployee);

                                    // Normalize/defensive defaults
                                    $appId = (int) ($app['id'] ?? 0);
                                    $status = (string) ($app['status'] ?? 'pending');
                                    $pic = (string) ($app['picture'] ?? '');
                                    $fname = (string) ($app['first_name'] ?? '');
                                    $mname = (string) ($app['middle_name'] ?? '');
                                    $lname = (string) ($app['last_name'] ?? '');
                                    $suffix = (string) ($app['suffix'] ?? '');
                                    $phone = (string) ($app['phone_number'] ?? '—');
                                    $email = (string) ($app['email'] ?? 'N/A');
                                    $created = (string) ($app['created_at'] ?? '');
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($pic !== ''): ?>
                                                <img src="<?php echo h(getFileUrl($pic)); ?>" alt="Photo" class="rounded" width="50"
                                                    height="50" style="object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                                    style="width: 50px; height: 50px;">
                                                    <?php echo strtoupper(substr($fname, 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <strong><?php echo h(getFullName($fname, $mname, $lname, $suffix)); ?></strong>
                                        </td>
                                        <td><?php echo h($phone); ?></td>
                                        <td><?php echo h($email); ?></td>
                                        <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
                                        <td>
                                            <?php
                                            // Color-code status
                                            $badge = [
                                                'pending' => 'warning',
                                                'on_process' => 'info',
                                                'approved' => 'success',
                                            ][$status] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badge; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                            </span>
                                        </td>
                                        <td><?php echo h(formatDate($created)); ?></td>
                                        <td>
                                            <div class="btn-group dropup dd-modern">
                                                <a href="view-applicant.php?id=<?php echo $appId; ?>"
                                                    class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>

                                                <?php if ($canEdit): ?>
                                                    <a href="edit-applicant.php?id=<?php echo $appId; ?>"
                                                        class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>

                                                    <!-- Delete -->
                                                    <a href="turkey_pending.php?action=delete&id=<?php echo $appId; ?><?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>"
                                                        class="btn btn-sm btn-danger" title="Delete"
                                                        onclick="return confirm('Are you sure you want to delete this applicant?');">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Change Status Dropdown -->
                                                <div class="dropdown">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status"
                                                        data-bs-toggle="dropdown" data-bs-auto-close="true"
                                                        data-bs-display="static" data-bs-offset="0,8" aria-expanded="false"
                                                        aria-haspopup="true" title="Change Status"
                                                        id="changeStatusBtn-<?php echo $appId; ?>">
                                                        <i class="bi bi-arrow-left-right me-1"></i>
                                                        Change Status
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end shadow"
                                                        aria-labelledby="changeStatusBtn-<?php echo $appId; ?>">
                                                        <li>
                                                            <a class="dropdown-item <?php echo ($status === 'pending') ? 'disabled' : ''; ?>"
                                                                href="turkey_pending.php?action=update_status&id=<?php echo $appId; ?>&to=pending<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>">
                                                                <i class="bi bi-hourglass-split text-warning"></i>
                                                                <span>Pending</span>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item <?php echo ($status === 'on_process') ? 'disabled' : ''; ?>"
                                                                href="turkey_pending.php?action=update_status&id=<?php echo $appId; ?>&to=on_process<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>">
                                                                <i class="bi bi-arrow-repeat text-info"></i>
                                                                <span>On Process</span>
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a class="dropdown-item <?php echo ($status === 'approved') ? 'disabled' : ''; ?>"
                                                                href="turkey_pending.php?action=update_status&id=<?php echo $appId; ?>&to=approved<?php echo $preserveQS; ?>&csrf=<?php echo h($csrf); ?>">
                                                                <i class="bi bi-check2-circle text-success"></i>
                                                                <span>Approved</span>
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
</div>
<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>