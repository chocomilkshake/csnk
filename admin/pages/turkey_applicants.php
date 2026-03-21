<?php
// FILE: admin/pages/turkey_applicants.php (SMC - Turkey within CSNK Admin)
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

require_once $ADMIN_ROOT . '/includes/smc_filter_bar.php';

$buScope = null;

$filterState = smc_filter_boot([
    'base_url' => 'turkey_applicants.php',
    'session_ns' => 'smc_tr_applicants',
    'applicant' => $applicant,
    'buId' => $buScope,
    'allowed_statuses' => ['all', 'pending', 'on_process', 'approved'],
]);

$filters = $filterState['filters'];
$status = $filters['status'] ?? 'all';
$country = $filters['countryId'] ?? 'all';
$q = $filterState['q'];
$preserveQS = $filterState['preserveQS'];

$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$pageSize = 25;

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
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

    if ($deleted && isset($auth) && isset($_SESSION['admin_id']) && method_exists($auth, 'logActivity')) {
        $fullName = null;
        if (method_exists($applicant, 'getById')) {
            $row = $applicant->getById($id);
            if (is_array($row)) {
                $fullName = getFullName(
                    $row['first_name'] ?? '',
                    $row['middle_name'] ?? '',
                    $row['last_name'] ?? '',
                    $row['suffix'] ?? ''
                );
            }
        }
        $label = $fullName ?: "ID {$id}";
        $auth->logActivity(
            (int) $_SESSION['admin_id'],
            'Delete Applicant',
            "Deleted applicant {$label} (SMC)"
        );
    }

    $params = [];
    if ($q !== '')
        $params['q'] = $q;
    if ($status !== 'all')
        $params['status'] = $status;
    if ($country !== 'all')
        $params['country'] = $country;

    $qs = !empty($params) ? ('?' . http_build_query($params)) : '';
    redirect('turkey_applicants.php' . $qs);
    exit;
}

$applicants = $applicant->getApplicants(
    $filters['buId'],
    $filters['countryId'],
    $filters['status'],
    $filters['q'],
    $filters['notDeleted'],
    $filters['notBlacklisted'],
    $page,
    $pageSize
);

$totalApplicants = $applicant->getApplicantsCount(
    $filters['buId'],
    $filters['countryId'],
    $filters['status'],
    $filters['q'],
    $filters['notDeleted'],
    $filters['notBlacklisted']
);

$totalPages = ceil($totalApplicants / $pageSize);

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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">List of SMC Applicants</h4>
</div>

<?php smc_filter_render($filterState); ?>

<div class="container-fluid px-2">
    <div class="row align-items-center justify-content-between page-header-row mb-3">

        <div class="row mb-2">
            <div class="col-12 d-flex justify-content-end">
                <form method="get" action="turkey_applicants.php" class="d-flex" role="search"
                    style="max-width: 460px; width: 100%;">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="Search applicants..."
                            value="<?php echo h($q); ?>" autocomplete="off">
                        <input type="hidden" name="status" value="<?php echo h($filterState['status']); ?>">
                        <button class="btn btn-outline-secondary" type="submit" title="Search"><i
                                class="bi bi-search"></i></button>
                        <?php if ($q !== ''): ?>
                            <a class="btn btn-outline-secondary"
                                href="turkey_applicants.php?clear=1<?php echo $filterState['preserveQS']; ?>"
                                title="Clear search"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <div class="card table-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-styled mb-0"
                            id="applicantsTable">
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
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                            <?php if ($q === ''): ?>
                                                No applicants found.
                                            <?php else: ?>
                                                No results for "<strong><?php echo h($q); ?></strong>".
                                                <a href="turkey_applicants.php?clear=1<?php echo $filterState['preserveQS']; ?>"
                                                    class="ms-2">Clear search</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($applicants as $app): ?>
                                        <?php
                                        $currentStatus = (string) ($app['status'] ?? '');
                                        $params = [];
                                        if ($q !== '')
                                            $params['q'] = $q;
                                        if ($status !== 'all')
                                            $params['status'] = $status;
                                        $tail = !empty($params) ? ('&' . http_build_query($params)) : '';
                                        $viewUrl = 'view-applicant.php?id=' . (int) $app['id'] . $tail;
                                        $editUrl = 'edit-applicant.php?id=' . (int) $app['id'] . $tail;
                                        $delUrl = 'turkey_applicants.php?action=delete&id=' . (int) $app['id'] . $tail;
                                        $appBuId = (int) ($app['business_unit_id'] ?? 0);

                                        $canEdit = false;
                                        if ($isSuperAdmin || $isAdmin) {
                                            $canEdit = true;
                                        } elseif ($isEmployee) {
                                            $canEdit = true;
                                        }

                                        $statusColors = ['pending' => 'warning', 'on_process' => 'info', 'approved' => 'success', 'deleted' => 'secondary'];
                                        $badgeColor = $statusColors[$currentStatus] ?? 'secondary';
                                        ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($app['picture'])): ?>
                                                    <img src="<?php echo h(getFileUrl($app['picture'])); ?>" alt="Photo"
                                                        class="rounded" width="50" height="50" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                                        style="width: 50px; height: 50px;">
                                                        <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?></strong>
                                            </td>
                                            <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                                            <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                                            <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?>
                                            </td>
                                            <td><span
                                                    class="badge bg-<?php echo $badgeColor; ?>"><?php echo h(ucfirst(str_replace('_', ' ', $currentStatus))); ?></span>
                                            </td>
                                            <td><?php echo h(formatDate($app['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="<?php echo h($viewUrl); ?>" class="btn btn-sm btn-info"
                                                        title="View"><i class="bi bi-eye"></i></a>
                                                    <?php if ($canEdit): ?>
                                                        <a href="<?php echo h($editUrl); ?>" class="btn btn-sm btn-warning"
                                                            title="Edit"><i class="bi bi-pencil"></i></a>
                                                        <?php if ($currentStatus === 'pending'): ?>
                                                            <a href="<?php echo h($delUrl); ?>" class="btn btn-sm btn-danger"
                                                                title="Delete"
                                                                onclick="return confirm('Are you sure you want to delete this applicant?');"><i
                                                                    class="bi bi-trash"></i></a>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <a href="#" class="btn btn-sm btn-secondary" title="Edit"
                                                            onclick="alert('You do not have access to edit applicants from another country.'); return false;"><i
                                                                class="bi bi-pencil"></i></a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>