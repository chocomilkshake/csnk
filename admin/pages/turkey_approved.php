<?php
// FILE: admin/pages/turkey_approved.php (SMC - Turkey Approved/Hired Applicants)
// Purpose: Always show only SMC applicants, regardless of status changes elsewhere.

$pageTitle = 'Approved Applicants (SMC - Turkey)';

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

// SMC access only for this page
if (!$auth->canSeeSMC()) {
    header('Location: applicants.php');
    exit;
}

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

// Optional: keep first SMC BU id in session (if you use it elsewhere)
if (!empty($smcBuIds)) {
    $_SESSION['smc_bu_id'] = $smcBuIds[0];
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

require_once $ADMIN_ROOT . '/includes/header.php';
// NOTE: this Applicant class here is the SMC module DAO
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';

$applicant = new Applicant($database);

// Role helpers (provided by header/auth in your app)
$currentBuId   = (int) ($_SESSION['current_bu_id'] ?? 0);
$isSuperAdmin  = (isset($currentRole) && $currentRole === 'super_admin');
$isAdmin       = (isset($isAdmin) ? (bool)$isAdmin : (isset($currentRole) && $currentRole === 'admin')); // fallback
$isEmployee    = (isset($currentRole) && $currentRole === 'employee');

$country = $_GET['country'] ?? 'all';
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$status  = 'approved';

$countryId = ($country !== 'all') ? (int) $country : null;
$notDeleted = true;
$notBlacklisted = true;

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$pageSize = 25;

// IMPORTANT: We intentionally do not rely on $buScope from role here,
// we will filter by SMC BUs after fetching to guarantee SMC-only visibility.
$buScope = null;

// Fetch (using your SMC DAO)
$applicants = $applicant->getApplicants(
    $buScope,       // we'll hard-filter below
    $countryId,
    $status,
    $q,
    $notDeleted,
    $notBlacklisted,
    $page,
    $pageSize
);

$totalApplicants = $applicant->getApplicantsCount(
    $buScope,
    $countryId,
    $status,
    $q,
    $notDeleted,
    $notBlacklisted
);

// ---- Hard-filter to SMC-only in PHP to ensure agency never changes visibility ----
if (!empty($smcBuIds)) {
    $applicants = array_values(array_filter((array)$applicants, function($row) use ($smcBuIds) {
        $buId = (int)($row['business_unit_id'] ?? 0);
        return in_array($buId, $smcBuIds, true);
    }));

    // If you want the pagination totals to reflect SMC-only after filter:
    $totalApplicants = count($applicants);
}

// (Optional) refine country counts to reflect SMC-only as well
$countriesWithCounts = [];
try {
    // Build SMC-only country counts from the filtered applicant list
    $byCountry = [];
    foreach ($applicants as $row) {
        $cid = (int)($row['country_id'] ?? 0);
        $cname = (string)($row['country_name'] ?? ($row['country'] ?? ''));
        if (!isset($byCountry[$cid])) {
            $byCountry[$cid] = ['id' => $cid, 'name' => $cname, 'count' => 0];
        }
        $byCountry[$cid]['count']++;
    }
    // If you need stable country names, you can fetch from DB instead. For now, derive from rows.
    $countriesWithCounts = array_values(array_filter($byCountry, fn($c) => $c['id'] > 0));
} catch (Throwable $e) {
    // swallow; keep empty counts
}

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
</style>

<div class="container-fluid px-2">
    <div class="row align-items-center justify-content-between mb-3">
        <div class="col-auto">
            <h4 class="mb-2 fw-semibold">SMC - Hired Applicants</h4>
            <div class="status-group">
                <a href="turkey_applicants.php" class="status-btn">All</a>
                <a href="turkey_pending.php" class="status-btn">Pending</a>
                <a href="turkey_on-process.php" class="status-btn">On Process</a>
                <a href="turkey_approved.php" class="status-btn status-btn--active">Hired</a>
            </div>
        </div>
        <?php if (!empty($countriesWithCounts)): ?>
            <div class="col-12 mt-2">
                <div class="filter-label">Filter by Country</div>
                <div class="country-group">
                    <a href="turkey_approved.php" class="country-btn <?php echo $country === 'all' ? 'country-btn--active' : ''; ?>">All</a>
                    <?php foreach ($countriesWithCounts as $c): ?>
                        <a href="turkey_approved.php?country=<?php echo (int)$c['id']; ?>" class="country-btn <?php echo $country === (string)$c['id'] ? 'country-btn--active' : ''; ?>"><?php echo h($c['name']); ?> (<?php echo (int)$c['count']; ?>)</a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <form method="get" action="turkey_approved.php" class="d-flex" role="search" style="max-width: 460px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search applicants..." value="<?php echo h($q); ?>">
                    <input type="hidden" name="country" value="<?php echo h($country); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($q !== ''): ?>
                        <a class="btn btn-outline-secondary" href="turkey_approved.php?country=<?php echo h($country); ?>"><i class="bi bi-x-lg"></i></a>
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
                            <tr><td colspan="8" class="text-center text-muted py-5">No hired applicants found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($applicants as $app): ?>
                                <?php
                                $appBuId = (int) ($app['business_unit_id'] ?? 0);

                                // Check if employee can edit this applicant
                                // For this SMC page: allow admins/super_admins; employees also allowed on all SMC rows
                                $canEdit = false;
                                if ($isSuperAdmin || $isAdmin) {
                                    $canEdit = true;
                                } elseif ($isEmployee) {
                                    $canEdit = true;
                                }
                                ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($app['picture'])): ?>
                                            <img src="<?php echo h(getFileUrl($app['picture'])); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;"><?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?></strong></td>
                                    <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                                    <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                                    <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
                                    <td><span class="badge bg-success">Hired</span></td>
                                    <td><?php echo h(formatDate($app['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view-applicant.php?id=<?php echo (int)$app['id']; ?>" class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
                                            <?php if ($canEdit): ?>
                                                <a href="edit-applicant.php?id=<?php echo (int)$app['id']; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil"></i></a>
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