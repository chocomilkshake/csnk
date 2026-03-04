<?php
// FILE: admin/pages/turkey_pending.php (SMC - Turkey Pending Applicants)
$pageTitle = 'Pending Applicants (SMC - Turkey)';

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
            // Store SMC BU ID in separate session variable to avoid overwriting CSNK BU
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

// For SMC pages, use SMC BU instead of the CSNK BU from session
$smcBuId = (int) ($_SESSION['smc_bu_id'] ?? 0);

$isSuperAdmin = ($currentRole === 'super_admin');
$isEmployee = ($currentRole === 'employee');

$country = $_GET['country'] ?? 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = 'pending';

// For SMC pages: super admin/employee/admin sees all SMC applicants (null = no BU filter)
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

$preserveQS = !empty($_GET) ? ('&' . http_build_query(array_filter($_GET, fn($k) => $k !== 'page', ARRAY_FILTER_USE_KEY))) : '';
$preserveQSWithQuestion = !empty($preserveQS) ? ('?' . ltrim($preserveQS, '&')) : '';
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
</style>

<div class="container-fluid px-2">
    <div class="row align-items-center justify-content-between mb-3">
        <div class="col-auto">
            <h4 class="mb-2 fw-semibold">SMC - Pending Applicants</h4>
            <div class="status-group">
                <a href="turkey_applicants.php" class="status-btn">All</a>
                <a href="turkey_pending.php" class="status-btn status-btn--active">Pending</a>
                <a href="turkey_on-process.php" class="status-btn">On Process</a>
                <a href="turkey_approved.php" class="status-btn">Hired</a>
            </div>
        </div>
        <?php if (!empty($countriesWithCounts)): ?>
            <div class="col-12 mt-2">
                <div class="filter-label">Filter by Country</div>
                <div class="country-group">
                    <a href="turkey_pending.php"
                        class="country-btn <?php echo $country === 'all' ? 'country-btn--active' : ''; ?>">All</a>
                    <?php foreach ($countriesWithCounts as $c): ?>
                        <a href="turkey_pending.php?country=<?php echo (int) $c['id']; ?>"
                            class="country-btn <?php echo $country === (string) $c['id'] ? 'country-btn--active' : ''; ?>"><?php echo h($c['name']); ?>
                            (<?php echo (int) $c['count']; ?>)</a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

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
                        <a class="btn btn-outline-secondary" href="turkey_pending.php?country=<?php echo h($country); ?>"><i
                                class="bi bi-x-lg"></i></a>
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
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No pending applicants found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <?php
                                $appBuId = (int) ($app['business_unit_id'] ?? 0);

                                // Check if employee can edit this applicant
                                // For super_admin/admin: can edit all
                                // For employee: Since this is the SMC page, allow all employees full access to all SMC applicants
                                $canEdit = false;

                                if ($isSuperAdmin || $isAdmin) {
                                    $canEdit = true;
                                } elseif ($isEmployee) {
                                    // All employees on this SMC page can edit all SMC applicants
                                    $canEdit = true;
                                }
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
                                    <td><span class="badge bg-warning">Pending</span></td>
                                    <td><?php echo h(formatDate($app['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view-applicant.php?id=<?php echo (int) $app['id']; ?>"
                                                class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                            <?php if ($canEdit): ?>
                                                <a href="edit-applicant.php?id=<?php echo (int) $app['id']; ?>"
                                                    class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
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