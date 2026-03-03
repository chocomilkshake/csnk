<?php
// FILE: admin/pages/turkey_applicants.php (SMC - Turkey within CSNK Admin)
$pageTitle = 'List of Applicants (SMC - Turkey)';

/* ---------- Force SMC Business Unit for this page ---------- */
$ADMIN_ROOT = dirname(__DIR__);

// If no session, redirect to login
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

// Find SMC BU and set as current
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
            $_SESSION['current_bu_id'] = $smcBuId;
        }
    }
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

// Now include the main header
require_once $ADMIN_ROOT . '/includes/header.php';

// Shared model - Use SMC-specific Applicant model
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';

$applicant = new Applicant($database);

// BU scope (SMC-Turkey only)
$currentBuId = (int) ($_SESSION['current_bu_id'] ?? 0);

// Determine if user is super admin (for viewing all SMC applicants)
$isSuperAdmin = ($currentRole === 'super_admin');
$isEmployee = ($currentRole === 'employee');

// ---------- CENTRALIZED FILTERS ----------
$country = $_GET['country'] ?? 'all';
$status = $_GET['status'] ?? 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// BU scope: super admin/employee = unscoped (null), admin = scoped to current BU
$buScope = ($isSuperAdmin || $isEmployee) ? null : $currentBuId;

// Country ID: null for 'all', otherwise cast to int
$countryId = ($country !== 'all') ? (int) $country : null;

// Visibility flags (consistent across both queries)
$notDeleted = true;
$notBlacklisted = true;

// Pack into $filters array
$filters = [
    'buId' => $buScope,
    'countryId' => $countryId,
    'status' => $status,
    'q' => $q,
    'notDeleted' => $notDeleted,
    'notBlacklisted' => $notBlacklisted
];

// ---------- Namespaced session keys ----------
$SESSION_KEY_Q = 'smc_tr_applicants_q';
$SESSION_KEY_STATUS = 'smc_tr_applicants_status';
$SESSION_KEY_COUNTRY = 'smc_tr_applicants_country';

$allowedStatuses = ['all', 'pending', 'on_process', 'approved'];

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION[$SESSION_KEY_Q]);
    $preserveParams = [];
    $statusFromSession = $_SESSION[$SESSION_KEY_STATUS] ?? 'all';
    $countryFromSession = $_SESSION[$SESSION_KEY_COUNTRY] ?? 'all';
    if (in_array($statusFromSession, $allowedStatuses, true) && $statusFromSession !== 'all') {
        $preserveParams['status'] = $statusFromSession;
    }
    if ($countryFromSession !== 'all') {
        $preserveParams['country'] = $countryFromSession;
    }
    $qs = !empty($preserveParams) ? ('?' . http_build_query($preserveParams)) : '';
    redirect('turkey_applicants.php' . $qs);
    exit;
}

if (isset($_GET['q'])) {
    $q = trim((string) $_GET['q']);
    if (mb_strlen($q) > 100)
        $q = mb_substr($q, 0, 100);
    $_SESSION[$SESSION_KEY_Q] = $q;
} elseif (!empty($_SESSION[$SESSION_KEY_Q])) {
    $q = (string) $_SESSION[$SESSION_KEY_Q];
}

if (isset($_GET['status'])) {
    $statusCandidate = strtolower(trim((string) $_GET['status']));
    $status = in_array($statusCandidate, $allowedStatuses, true) ? $statusCandidate : 'all';
    $_SESSION[$SESSION_KEY_STATUS] = $status;
} elseif (!empty($_SESSION[$SESSION_KEY_STATUS])) {
    $statusCandidate = strtolower((string) $_SESSION[$SESSION_KEY_STATUS]);
    $status = in_array($statusCandidate, $allowedStatuses, true) ? $statusCandidate : 'all';
}

if (isset($_GET['country'])) {
    $countryCandidate = trim((string) $_GET['country']);
    if ($countryCandidate === 'all' || is_numeric($countryCandidate)) {
        $country = $countryCandidate;
        $_SESSION[$SESSION_KEY_COUNTRY] = $country;
    }
} elseif (!empty($_SESSION[$SESSION_KEY_COUNTRY])) {
    $country = (string) $_SESSION[$SESSION_KEY_COUNTRY];
}

$filters = [
    'buId' => $buScope,
    'countryId' => ($country !== 'all') ? (int) $country : null,
    'status' => $status,
    'q' => $q,
    'notDeleted' => $notDeleted,
    'notBlacklisted' => $notBlacklisted
];

// ---------- GET COUNTRY COUNTS ----------
$countriesWithCounts = $applicant->getCountriesWithCounts(
    $filters['buId'],
    $filters['status'],
    $filters['q'],
    $filters['notDeleted'],
    $filters['notBlacklisted']
);

$counts = ['all' => 0, 'pending' => 0, 'on_process' => 0, 'approved' => 0];

foreach ($countriesWithCounts as $c) {
    $counts['all'] += (int) $c['count'];
}

foreach ($countriesWithCounts as &$c) {
    $countryIdForCount = (int) $c['id'];
    $pendingCounts = $applicant->getCountriesWithCounts($filters['buId'], 'pending', $filters['q'], $filters['notDeleted'], $filters['notBlacklisted']);
    foreach ($pendingCounts as $pc) {
        if ((int) $pc['id'] === $countryIdForCount) {
            $c['pending'] = (int) $pc['count'];
            break;
        }
    }
    $onProcessCounts = $applicant->getCountriesWithCounts($filters['buId'], 'on_process', $filters['q'], $filters['notDeleted'], $filters['notBlacklisted']);
    foreach ($onProcessCounts as $opc) {
        if ((int) $opc['id'] === $countryIdForCount) {
            $c['on_process'] = (int) $opc['count'];
            break;
        }
    }
    $approvedCounts = $applicant->getCountriesWithCounts($filters['buId'], 'approved', $filters['q'], $filters['notDeleted'], $filters['notBlacklisted']);
    foreach ($approvedCounts as $ac) {
        if ((int) $ac['id'] === $countryIdForCount) {
            $c['approved'] = (int) $ac['count'];
            break;
        }
    }
    $counts['pending'] += (int) ($c['pending'] ?? 0);
    $counts['on_process'] += (int) ($c['on_process'] ?? 0);
    $counts['approved'] += (int) ($c['approved'] ?? 0);
}
unset($c);

// ---------- GET APPLICANTS LIST ----------
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$pageSize = 25;

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

$paramsForLinks = [];
if ($q !== '')
    $paramsForLinks['q'] = $q;
if ($status !== 'all')
    $paramsForLinks['status'] = $status;
if ($country !== 'all')
    $paramsForLinks['country'] = $country;
$preserveQS = !empty($paramsForLinks) ? ('&' . http_build_query($paramsForLinks)) : '';
$preserveQSWithQuestion = !empty($paramsForLinks) ? ('?' . http_build_query($paramsForLinks)) : '';
?>
<style>
    .status-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .85);
        backdrop-filter: saturate(140%) blur(2px);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 1px 3px rgba(0, 0, 0, .10);
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
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .status-btn:hover {
        transform: translateY(-2px);
        background: #f8fafc;
        border-color: #94a3b8;
        box-shadow: 0 6px 12px rgba(15, 23, 42, .06);
    }

    .status-btn:focus {
        outline: 3px solid rgba(99, 102, 241, .35);
        outline-offset: 2px;
    }

    .status-btn--active {
        color: #fff;
        border-color: #4f46e5;
        background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);
        box-shadow: 0 8px 18px rgba(79, 70, 229, .25);
    }

    .status-btn--active:hover {
        background: linear-gradient(180deg, #5457ee 0%, #463fd3 100%);
        border-color: #463fd3;
    }

    .status-icon {
        font-size: .95em;
        line-height: 1;
        opacity: .9;
    }

    .badge-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 22px;
        height: 20px;
        padding: 0 .4rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: .75rem;
        line-height: 1;
        background: #eef2ff;
        color: #4338ca;
        border: 1px solid rgba(0, 0, 0, .04);
    }

    .country-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .85);
        backdrop-filter: saturate(140%) blur(2px);
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 1px 3px rgba(0, 0, 0, .10);
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
        transition: all .15s ease;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    }

    .country-btn:hover {
        transform: translateY(-1px);
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .country-btn--active {
        color: #fff;
        border-color: #059669;
        background: linear-gradient(180deg, #10b981 0%, #059669 100%);
        box-shadow: 0 4px 10px rgba(5, 150, 105, .25);
    }

    .country-btn--active:hover {
        background: linear-gradient(180deg, #0ea56e 0%, #047857 100%);
    }

    .filter-label {
        font-size: .75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: .25rem;
    }

    .page-header-row {
        row-gap: .5rem;
    }
</style>

<div class="container-fluid px-2">
    <div class="row align-items-center justify-content-between page-header-row mb-3">
        <div class="col-auto">
            <h4 class="mb-2 fw-semibold">SMC - List of Applicants</h4>
            <?php
            $qParam = $_SESSION[$SESSION_KEY_Q] ?? '';
            $renderBtn = function (string $label, string $value, string $currentStatus, string $q, string $icon, int $count) {
                $isActive = ($value === $currentStatus) || ($value === 'all' && $currentStatus === 'all');
                $href = 'turkey_applicants.php?status=' . urlencode($value);
                if ($q !== '')
                    $href .= '&q=' . urlencode($q);
                $classes = 'status-btn' . ($isActive ? ' status-btn--active' : '');
                $iconHtml = $icon !== '' ? '<i class="status-icon ' . h($icon) . '"></i>' : '';
                $countHtml = '<span class="badge-pill ms-1">' . (int) $count . '</span>';
                return '<a href="' . h($href) . '" class="' . $classes . '">' . $iconHtml . '<span>' . h($label) . '</span>' . $countHtml . '</a>';
            };
            echo '<div class="status-group">';
            echo $renderBtn('All', 'all', $status, $qParam, 'bi bi-list-ul', $counts['all']);
            echo $renderBtn('Pending', 'pending', $status, $qParam, 'bi bi-hourglass-split', $counts['pending']);
            echo $renderBtn('On-Process', 'on_process', $status, $qParam, 'bi bi-arrow-repeat', $counts['on_process']);
            echo $renderBtn('Hired', 'approved', $status, $qParam, 'bi bi-check2-circle', $counts['approved']);
            echo '</div>';
            ?>
        </div>
        <?php if (!empty($countriesWithCounts)): ?>
            <div class="col-12 mt-2">
                <div class="filter-label">Filter by Country</div>
                <div class="country-group">
                    <?php
                    $renderCountryBtn = function (string $label, string $countryId, string $currentCountry, string $q, string $status, int $count) {
                        $isActive = ($countryId === $currentCountry) || ($countryId === 'all' && $currentCountry === 'all');
                        $href = 'turkey_applicants.php?country=' . urlencode($countryId);
                        if ($q !== '')
                            $href .= '&q=' . urlencode($q);
                        if ($status !== 'all')
                            $href .= '&status=' . urlencode($status);
                        $classes = 'country-btn' . ($isActive ? ' country-btn--active' : '');
                        $countHtml = $count > 0 ? '<span class="badge-pill ms-1">' . (int) $count . '</span>' : '';
                        return '<a href="' . h($href) . '" class="' . $classes . '">' . '<span>' . h($label) . '</span>' . $countHtml . '</a>';
                    };
                    echo $renderCountryBtn('All', 'all', $country, $q, $status, $counts['all']);
                    foreach ($countriesWithCounts as $c) {
                        $countryName = h($c['name']);
                        $countryId = (int) $c['id'];
                        $count = (int) $c['count'];
                        echo $renderCountryBtn($countryName, (string) $countryId, $country, $q, $status, $count);
                    }
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <form method="get" action="turkey_applicants.php" class="d-flex" role="search"
                style="max-width: 460px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search applicants..."
                        value="<?php echo h($q); ?>" autocomplete="off">
                    <input type="hidden" name="status" value="<?php echo h($status); ?>">
                    <button class="btn btn-outline-secondary" type="submit" title="Search"><i
                            class="bi bi-search"></i></button>
                    <?php if ($q !== ''): ?>
                        <a class="btn btn-outline-secondary"
                            href="turkey_applicants.php?clear=1<?php echo $status !== 'all' ? ('&status=' . urlencode($status)) : ''; ?>"
                            title="Clear search"><i class="bi bi-x-lg"></i></a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <div class="card table-card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-styled mb-0" id="applicantsTable">
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
                                        <a href="turkey_applicants.php?clear=1<?php echo $status !== 'all' ? ('&status=' . urlencode($status)) : ''; ?>"
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
                                $appBuId = (int) ($app['business_unit_id'] ?? 0);
                                $canEdit = ($isSuperAdmin || $isAdmin) || ($isEmployee && $appBuId === $currentBuId);
                                $statusColors = ['pending' => 'warning', 'on_process' => 'info', 'approved' => 'success', 'deleted' => 'secondary'];
                                $badgeColor = $statusColors[$currentStatus] ?? 'secondary';
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($app['picture'])): ?>
                                            <img src="<?php echo h(getFileUrl($app['picture'])); ?>" alt="Photo" class="rounded"
                                                width="50" height="50" style="object-fit: cover;">
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
                                    <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
                                    <td><span
                                            class="badge bg-<?php echo $badgeColor; ?>"><?php echo h(ucfirst(str_replace('_', ' ', $currentStatus))); ?></span>
                                    </td>
                                    <td><?php echo h(formatDate($app['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?php echo h($viewUrl); ?>" class="btn btn-sm btn-info" title="View"><i
                                                    class="bi bi-eye"></i></a>
                                            <?php if ($canEdit): ?>
                                                <a href="<?php echo h($editUrl); ?>" class="btn btn-sm btn-warning" title="Edit"><i
                                                        class="bi bi-pencil"></i></a>
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
    </div>
</div>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>