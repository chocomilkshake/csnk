<?php
// FILE: admin/pages/turkey_blacklisted.php (SMC - Turkey Blacklisted Applicants)
$pageTitle = 'Blacklisted Applicants (SMC - Turkey)';

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

// Get blacklisted applicants for SMC
$blacklistedApplicants = [];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($conn instanceof mysqli) {
    $sql = "SELECT a.*, ba.reason, ba.blacklisted_at 
            FROM blacklisted_applicants ba 
            JOIN applicants a ON a.id = ba.applicant_id 
            WHERE a.business_unit_id = ? AND ba.is_active = 1";

    if ($q !== '') {
        $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
    }
    $sql .= " ORDER BY ba.blacklisted_at DESC";

    if ($stmt = $conn->prepare($sql)) {
        if ($q !== '') {
            $searchTerm = "%$q%";
            $stmt->bind_param('isss', $smcBuId, $searchTerm, $searchTerm, $searchTerm);
        } else {
            $stmt->bind_param('i', $smcBuId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $blacklistedApplicants = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
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
            <h4 class="mb-2 fw-semibold">SMC - Blacklisted Applicants</h4>
        </div>
    </div>

    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <form method="get" action="turkey_blacklisted.php" class="d-flex" role="search"
                style="max-width: 460px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search blacklisted applicants..."
                        value="<?php echo h($q); ?>">
                    <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
                    <?php if ($q !== ''): ?>
                        <a class="btn btn-outline-secondary" href="turkey_blacklisted.php"><i class="bi bi-x-lg"></i></a>
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
                            <th>Reason</th>
                            <th>Date Blacklisted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blacklistedApplicants)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-5">No blacklisted applicants found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($blacklistedApplicants as $app): ?>
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
                                    <td><?php echo h($app['reason'] ?? 'N/A'); ?></td>
                                    <td><?php echo h(formatDate($app['blacklisted_at'] ?? $app['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view-applicant.php?id=<?php echo (int) $app['id']; ?>"
                                                class="btn btn-sm btn-info"><i class="bi bi-eye"></i></a>
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