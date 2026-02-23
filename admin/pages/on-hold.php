<?php
// FILE: pages/on-hold.php
$pageTitle = 'On Hold Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for search persistence)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant = new Applicant($database);

/**
 * --- Search Memory Behavior (consistent with other pages) ---
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['onhold_q']);
    redirect('on-hold.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }
    $_SESSION['onhold_q'] = $q;
} elseif (!empty($_SESSION['onhold_q'])) {
    $q = (string)$_SESSION['onhold_q'];
}

/**
 * Load list for display
 */
$applicants = $applicant->getAll('on_hold');

// Filter by search
$applicants = (function(array $rows, string $query): array {
    if ($query === '') return $rows;

    $needle = mb_strtolower($query);
    return array_values(array_filter($rows, function(array $app) use ($needle) {
        $first  = (string)($app['first_name']   ?? '');
        $middle = (string)($app['middle_name']  ?? '');
        $last   = (string)($app['last_name']    ?? '');
        $suffix = (string)($app['suffix']       ?? '');
        $email  = (string)($app['email']        ?? '');
        $phone  = (string)($app['phone_number'] ?? '');
        $loc    = (string)($app['preferred_location'] ?? '');

        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $stack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone, $loc
        ]));
        return mb_strpos($stack, $needle) !== false;
    }));
})($applicants, $q);

/**
 * Helper: Render preferred_location JSON as clean text.
 */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) return 'N/A';

    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }

    $cities = array_values(array_filter(array_map('trim', $arr), function($v){
        return is_string($v) && $v !== '';
    }));
    if (empty($cities)) return 'N/A';

    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen) {
        return $cities[0];
    }
    return $full;
}

// Preserve the search in action links
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
?>

<style>
    /* Dropdown fix */
    .table-card,
    .table-card .card-body,
    .table-card .table-responsive,
    .table-card table,
    .table-card thead,
    .table-card tbody,
    .table-card tr,
    .table-card th,
    .table-card td { overflow: visible !important; }

    .table-card { position: relative; z-index: 0; }
    td.actions-cell { position: relative; overflow: visible; white-space: nowrap; }
    .table-card tr.row-raised { position: relative; z-index: 1060; }

    .dd-modern .dropdown-menu {
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        min-width: 180px;
        z-index: 9999 !important;
    }
    .dd-modern .dropdown-item { display:flex; align-items:center; gap:.5rem; padding:.55rem .9rem; font-weight:500; }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled, .dd-modern .dropdown-item:disabled { color:#9aa0a6; background:transparent; pointer-events:none; }
    .btn-status { border-radius: .75rem; }
    table.table-styled { margin-bottom: 0; }

    .badge-onhold {
        background: #fef3c7;
        color: #92400e;
        border: 1px solid #fcd34d;
        border-radius: .5rem;
        padding: .25rem .5rem;
        font-weight: 600;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">On Hold Applicants</h4>
</div>

<!-- 🔎 Search bar -->
<div class="mb-3 d-flex justify-content-end">
    <form action="on-hold.php" method="get" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search on hold applicants..."
                value="<?php echo h($q); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a href="on-hold.php?clear=1" class="btn btn-outline-secondary" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <table class="table table-bordered table-striped table-hover table-styled align-middle">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Date Applied</th>
                    <th style="width: 320px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <?php if ($q === ''): ?>
                                No on hold applicants.
                            <?php else: ?>
                                No results for "<strong><?php echo h($q); ?></strong>".
                                <a href="on-hold.php?clear=1" class="ms-1">Clear search</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $app): ?>
                        <?php
                            $id = (int)($app['id'] ?? 0);
                            $currentStatus = (string)($app['status'] ?? 'on_hold');

                            // View link
                            $viewUrl = 'view-applicant.php?id=' . $id . $preserveQ;
                            $historyUrl = 'view-applicant-history.php?id=' . $id . $preserveQ;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($app['picture'])): ?>
                                    <img
                                        src="<?php echo h(getFileUrl($app['picture'])); ?>"
                                        alt="Photo"
                                        class="rounded"
                                        width="50"
                                        height="50"
                                        style="object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?>
                                </strong>
                            </td>
                            <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                            <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                            <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
                            <td><?php echo h(formatDate($app['created_at'])); ?></td>

                            <td class="actions-cell">
                                <div class="btn-group dropup dd-modern">
                                    <!-- View -->
                                    <a href="<?php echo h($viewUrl); ?>"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i> View
                                    </a>

                                    <!-- History -->
                                    <a href="<?php echo h($historyUrl); ?>"
                                       class="btn btn-sm btn-outline-secondary" title="History">
                                        <i class="bi bi-clock-history"></i> History
                                    </a>

                                    <!-- Change Status Dropdown -->
                                    <div class="dropdown">
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-outline-primary dropdown-toggle btn-status"
                                            data-bs-toggle="dropdown"
                                            data-bs-auto-close="true"
                                            data-bs-display="static"
                                            data-bs-offset="0,8"
                                            aria-expanded="false"
                                            aria-haspopup="true"
                                            title="Change Status"
                                            id="changeStatusBtn-<?php echo (int)$app['id']; ?>">
                                            <i class="bi bi-arrow-left-right me-1"></i>
                                            Change Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end shadow"
                                            aria-labelledby="changeStatusBtn-<?php echo (int)$app['id']; ?>">
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#revertModal-<?php echo (int)$app['id']; ?>">
                                                    <i class="bi bi-arrow-counterclockwise text-warning"></i>
                                                    <span>Revert to Pending</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="blacklist-applicant.php?id=<?php echo (int)$app['id']; ?>">
                                                    <i class="bi bi-slash-circle text-danger"></i>
                                                    <span>Blacklist</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Revert Modal -->
                        <div class="modal fade" id="revertModal-<?php echo (int)$app['id']; ?>" tabindex="-1" aria-labelledby="revertModalLabel-<?php echo (int)$app['id']; ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <form class="modal-content" method="POST" action="revert-onhold.php">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="revertModalLabel-<?php echo (int)$app['id']; ?>">
                                            <i class="bi bi-arrow-counterclockwise me-2"></i>Revert to Pending
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <input type="hidden" name="applicant_id" value="<?php echo (int)$app['id']; ?>">
                                        
                                        <div class="alert alert-info d-flex align-items-start gap-2">
                                            <i class="bi bi-info-circle mt-1"></i>
                                            <div>
                                                You are reverting <strong><?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?></strong> 
                                                from <span class="badge badge-warning">On Hold</span> to <span class="badge badge-secondary">Pending</span>.
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                                            <select name="reason" class="form-select" required>
                                                <option value="">Select a reason</option>
                                                <option value="Health Issues Resolved">Health Issues Resolved</option>
                                                <option value="Personal Problems Solved">Personal Problems Solved</option>
                                                <option value="Ready to Work">Ready to Work</option>
                                                <option value="Documents Complete">Documents Complete</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Description <span class="text-danger">*</span></label>
                                            <textarea name="description" class="form-control" rows="4" placeholder="Provide details about why the applicant is being reverted..." required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check2-circle me-1"></i> Revert to Pending
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Raise the active row while a dropdown is open so it sits above neighbors
    document.querySelectorAll('.actions-cell .dropdown').forEach(function(dd) {
        dd.addEventListener('show.bs.dropdown', function() {
            var tr = dd.closest('tr');
            if (tr) tr.classList.add('row-raised');
        });
        dd.addEventListener('hidden.bs.dropdown', function() {
            var tr = dd.closest('tr');
            if (tr) tr.classList.remove('row-raised');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
