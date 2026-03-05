<?php
// FILE: admin/pages/turkey_blacklisted.php (SMC - Turkey Blacklisted Applicants)
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

// Resolve current user & role
$currentUser = $auth->getCurrentUser();
$role = isset($currentUser['role']) ? (string)$currentUser['role'] : 'employee';

// Only admin / super admin can view and manage blacklist
$isSuperAdmin = ($role === 'super_admin');
$isAdmin = ($role === 'admin');
$canManage = ($isAdmin || $isSuperAdmin);

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
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

require_once $ADMIN_ROOT . '/includes/header.php';

// Get blacklisted applicants for ALL SMC business units
$rows = [];
$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($conn instanceof mysqli && !empty($smcBuIds)) {
    // Use IN clause for all SMC BU IDs
    $placeholders = implode(',', array_fill(0, count($smcBuIds), '?'));
    $sql = "SELECT 
                b.id,
                b.applicant_id,
                b.reason,
                b.issue,
                b.proof_paths,
                b.created_at,
                b.is_active,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.suffix,
                a.status,
                a.picture,
                a.phone_number,
                a.email,
                a.preferred_location,
                au.full_name AS created_by_name,
                au.username AS created_by_username
            FROM blacklisted_applicants b
            JOIN applicants a ON a.id = b.applicant_id
            LEFT JOIN admin_users au ON au.id = b.created_by
            WHERE a.business_unit_id IN ($placeholders) AND b.is_active = 1";

    if ($q !== '') {
        $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ?)";
    }
    $sql .= " ORDER BY b.created_at DESC";

    if ($stmt = $conn->prepare($sql)) {
        $types = str_repeat('i', count($smcBuIds));
        $params = array_merge($smcBuIds);
        if ($q !== '') {
            $searchTerm = "%$q%";
            $types .= 'sss';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

/** Normalize proofs JSON to array of strings. */
function formatBlacklistProofs(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('strval', $arr)));
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

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 fw-semibold">SMC - Blacklisted Applicants</h5>
        <small class="text-muted">
            Records of applicants who violated company or client policies.
        </small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-12">
                <label class="form-label small text-muted mb-1">Search Applicants</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input
                        type="text"
                        id="searchBlacklist"
                        class="form-control form-control-sm"
                        placeholder="Search by name, ID, reason, or issue..."
                    >
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="blacklistTable">
                <thead class="table-light">
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Location</th>
                        <th>Reason</th>
                        <th>Logged By</th>
                        <th>Proofs</th>
                        <th>When</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">
                                No blacklisted applicants recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $appName = getFullName(
                                $row['first_name'] ?? '',
                                $row['middle_name'] ?? '',
                                $row['last_name'] ?? '',
                                $row['suffix'] ?? ''
                            );
                            $createdBy = $row['created_by_name'] ?: ($row['created_by_username'] ?: 'System');
                            $when = formatDateTime($row['created_at']);
                            $proofs = formatBlacklistProofs($row['proof_paths'] ?? null);
                            $viewUrl = 'view-applicant.php?id=' . (int)$row['applicant_id'];
                            $blacklistId = (int)$row['id'];

                            // Search blob
                            $searchBlob = strtolower(trim(
                                $appName . ' ' . ($row['applicant_id'] ?? '') . ' ' . ($row['reason'] ?? '') . ' ' . ($row['issue'] ?? '')
                            ));
                            $searchAttr = htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8');
                            ?>
                            <tr data-search-text="<?php echo $searchAttr; ?>">
                                <td>
                                    <?php if (!empty($row['picture'])): ?>
                                        <img src="<?php echo htmlspecialchars(getFileUrl($row['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                                            alt="Photo" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px;">
                                            <?php echo strtoupper(substr((string)($row['first_name'] ?? ''), 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-muted small">
                                        ID: <?php echo (int)$row['applicant_id']; ?>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['phone_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($row['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(renderPreferredLocation($row['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <div class="fw-semibold text-danger">
                                        <?php echo htmlspecialchars($row['reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($row['issue'])): ?>
                                        <div class="text-muted small text-truncate" style="max-width: 200px;">
                                            <?php echo htmlspecialchars($row['issue'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($createdBy, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (empty($proofs)): ?>
                                        <span class="text-muted small">None</span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($proofs as $idx => $path): ?>
                                                <?php $url = getFileUrl($path); ?>
                                                <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                                                   target="_blank"
                                                   class="badge bg-light text-dark text-decoration-none">
                                                    Proof <?php echo $idx + 1; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><?php echo htmlspecialchars($when, ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group" role="group">
                                        <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye me-1"></i>View
                                        </a>

                                        <?php if ($canManage): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-success"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#revertModal<?php echo $blacklistId; ?>">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i>Revert
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <?php if ($canManage): ?>
                                <!-- Revert Modal -->
                                <div class="modal fade" id="revertModal<?php echo $blacklistId; ?>" tabindex="-1" aria-labelledby="revertModalLabel<?php echo $blacklistId; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="revertModalLabel<?php echo $blacklistId; ?>">
                                                    <i class="bi bi-arrow-counterclockwise me-2"></i>
                                                    Revert Blacklist - <?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?>
                                                </h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>

                                            <form method="POST" action="revert-blacklist.php" enctype="multipart/form-data">
                                                <div class="modal-body">
                                                    <input type="hidden" name="blacklist_id" value="<?php echo $blacklistId; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                                                    <div class="alert alert-info">
                                                        <i class="bi bi-info-circle me-2"></i>
                                                        <strong>Note:</strong> This will remove the applicant from the blacklist. You can optionally provide compliance information and proof documents.
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Compliance Note <span class="text-muted small">(Optional)</span></label>
                                                        <textarea
                                                            name="compliance_note"
                                                            class="form-control"
                                                            rows="4"
                                                            placeholder="Describe how the applicant has complied with the issue or resolved the misunderstanding..."></textarea>
                                                        <small class="text-muted">Explain how the applicant has addressed the issue or if it was a misunderstanding.</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Compliance Proof <span class="text-muted small">(Optional)</span></label>
                                                        <input
                                                            type="file"
                                                            name="compliance_proofs[]"
                                                            class="form-control"
                                                            accept="image/*,.pdf,.doc,.docx"
                                                            multiple
                                                        >
                                                        <small class="text-muted">
                                                            Upload photos, screenshots, or documents as proof of compliance. Multiple files allowed.
                                                        </small>
                                                    </div>

                                                    <div class="border-top pt-3">
                                                        <small class="text-muted">
                                                            <strong>Original Reason:</strong> <?php echo htmlspecialchars($row['reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?><br>
                                                            <?php if (!empty($row['issue'])): ?>
                                                                <strong>Original Issue:</strong> <?php echo htmlspecialchars($row['issue'], ENT_QUOTES, 'UTF-8'); ?>
                                                            <?php endif; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="bi bi-check-circle me-2"></i>Confirm Revert
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function() {
    const searchInput = document.getElementById('searchBlacklist');
    const tableBody = document.querySelector('#blacklistTable tbody');

    if (!searchInput || !tableBody) return;

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        const rows = tableBody.querySelectorAll('tr');

        rows.forEach(row => {
            const searchText = row.getAttribute('data-search-text') || '';
            const visible = searchTerm === '' || searchText.includes(searchTerm);
            row.style.display = visible ? '' : 'none';
        });
    });
})();
</script>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>

