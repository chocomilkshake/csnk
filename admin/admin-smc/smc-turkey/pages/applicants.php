<?php
// FILE: admin/admin-smc/smc-turkey/pages/applicants.php (SMC - Turkey)
$pageTitle = 'List of Applicants (SMC - Turkey)';

// SMC header (auth + SMC access + BU guard + opens .content-wrapper)
require_once __DIR__ . '/../includes/header.php';

// Shared model
require_once dirname(__DIR__, 3) . '/includes/Applicant.php';

// Ensure session is active (for storing last search & status)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant = new Applicant($database);

// BU scope (SMC-Turkey only)
$currentBuId = (int)($_SESSION['current_bu_id'] ?? 0);

// ---------- Namespaced session keys to avoid conflicts with CSNK ----------
$SESSION_KEY_Q      = 'smc_tr_applicants_q';
$SESSION_KEY_STATUS = 'smc_tr_applicants_status';

// ---------- Search & Status memory ----------
$allowedStatuses = ['all', 'pending', 'on_process', 'approved'];

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION[$SESSION_KEY_Q]);
    // Preserve current status on clear
    $preserveParams = [];
    $statusFromSession = $_SESSION[$SESSION_KEY_STATUS] ?? 'all';
    if (in_array($statusFromSession, $allowedStatuses, true) && $statusFromSession !== 'all') {
        $preserveParams['status'] = $statusFromSession;
    }
    $qs = !empty($preserveParams) ? ('?' . http_build_query($preserveParams)) : '';
    redirect('applicants.php' . $qs);
    exit;
}

// Handle search query memory
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
    $_SESSION[$SESSION_KEY_Q] = $q;
} elseif (!empty($_SESSION[$SESSION_KEY_Q])) {
    $q = (string)$_SESSION[$SESSION_KEY_Q];
}

// Handle status memory
$status = 'all';
if (isset($_GET['status'])) {
    $statusCandidate = strtolower(trim((string)$_GET['status']));
    $status = in_array($statusCandidate, $allowedStatuses, true) ? $statusCandidate : 'all';
    $_SESSION[$SESSION_KEY_STATUS] = $status;
} elseif (!empty($_SESSION[$SESSION_KEY_STATUS])) {
    $statusCandidate = strtolower((string)$_SESSION[$SESSION_KEY_STATUS]);
    $status = in_array($statusCandidate, $allowedStatuses, true) ? $statusCandidate : 'all';
}

// ---------- Delete (BU-safe) ----------
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // BU-safety: ensure the applicant belongs to current BU before delete
    $row = null;
    if (method_exists($applicant, 'getById')) {
        $row = $applicant->getById($id);
    }
    if (!$row || (int)($row['business_unit_id'] ?? 0) !== $currentBuId) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'You are not allowed to delete this applicant.');
        }
    } else {
        if ($applicant->softDelete($id)) {
            if (isset($auth) && isset($_SESSION['admin_id']) && method_exists($auth, 'logActivity')) {
                $fullName = getFullName(
                    $row['first_name'] ?? '',
                    $row['middle_name'] ?? '',
                    $row['last_name'] ?? '',
                    $row['suffix'] ?? ''
                );
                $label = $fullName ?: ("ID {$id}");
                $auth->logActivity(
                    (int)$_SESSION['admin_id'],
                    'Delete Applicant',
                    "Deleted applicant {$label}"
                );
            }
            if (function_exists('setFlashMessage')) setFlashMessage('success', 'Applicant deleted successfully.');
        } else {
            if (function_exists('setFlashMessage')) setFlashMessage('error', 'Failed to delete applicant.');
        }
    }

    $params = [];
    if ($q !== '') $params['q'] = $q;
    if ($status !== 'all') $params['status'] = $status;
    $qs = !empty($params) ? ('?' . http_build_query($params)) : '';
    redirect('applicants.php' . $qs);
    exit;
}

// ---------- Load applicants (BU-scoped for SMC) ----------
$allInBu = [];
if ($currentBuId > 0) {
    // getAll(null, $buId) → active/non-deleted across statuses for this BU
    $allInBu = $applicant->getAll(null, $currentBuId) ?? [];
}
$applicants = $allInBu;

// ---------- Helpers ----------
function renderPreferredLocation(?string $json, int $maxLen = 30): string
{
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

function filterApplicantsByQuery(array $rows, string $query): array
{
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);
    return array_values(array_filter($rows, function (array $app) use ($needle) {
        $first  = (string)($app['first_name'] ?? '');
        $middle = (string)($app['middle_name'] ?? '');
        $last   = (string)($app['last_name'] ?? '');
        $suffix = (string)($app['suffix'] ?? '');
        $email  = (string)($app['email'] ?? '');
        $phone  = (string)($app['phone_number'] ?? '');
        $loc    = renderPreferredLocation($app['preferred_location'] ?? null, 999);

        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $haystack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone, $loc
        ]));

        return mb_strpos($haystack, $needle) !== false;
    }));
}

function filterApplicantsByStatus(array $rows, string $status): array
{
    if ($status === 'all') return $rows;
    return array_values(array_filter($rows, fn(array $app) =>
        isset($app['status']) && $app['status'] === $status
    ));
}

// Status counts (for button badges) – computed from full BU set
$counts = [
    'all'        => count($allInBu),
    'pending'    => 0,
    'on_process' => 0,
    'approved'   => 0,
];
foreach ($allInBu as $r) {
    $st = (string)($r['status'] ?? '');
    if (isset($counts[$st])) $counts[$st]++;
}

// Apply filters (status then search)
if ($status !== 'all') $applicants = filterApplicantsByStatus($applicants, $status);
if ($q !== '')        $applicants = filterApplicantsByQuery($applicants, $q);

// Preserve params for links
$paramsForLinks = [];
if ($q !== '')         $paramsForLinks['q'] = $q;
if ($status !== 'all') $paramsForLinks['status'] = $status;
$preserveQS               = !empty($paramsForLinks) ? ('&'  . http_build_query($paramsForLinks)) : '';
$preserveQSWithQuestion   = !empty($paramsForLinks) ? ('?'  . http_build_query($paramsForLinks)) : '';

// Export URL (shared includes path from SMC pages)
$exportParams = [];
if ($q !== '')         $exportParams['q'] = $q;
if ($status !== 'all') $exportParams['status'] = $status;
$exportUrl = '../../../includes/excel_applicants.php' . (!empty($exportParams) ? ('?' . http_build_query($exportParams)) : '');
?>
<!-- ===== Page-local CSS for compact CSNK-like layout ===== -->
<style>
    .status-group {
        display: inline-flex; gap: .5rem; padding: .5rem; border: 1px solid #e5e7eb;
        border-radius: 1rem; background: rgba(255,255,255,.85);
        backdrop-filter: saturate(140%) blur(2px);
        box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 1px 3px rgba(0,0,0,.10);
    }
    .status-btn {
        display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .9rem;
        border-radius: .75rem; font-size: .875rem; font-weight: 500; text-decoration: none;
        border: 1px solid #cbd5e1; color: #334155; background: #fff;
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .status-btn:hover {
        transform: translateY(-2px); background: #f8fafc; border-color: #94a3b8;
        box-shadow: 0 6px 12px rgba(15,23,42,.06);
    }
    .status-btn:focus { outline: 3px solid rgba(99,102,241,.35); outline-offset: 2px; }
    .status-btn--active {
        color: #fff; border-color: #4f46e5; background: linear-gradient(180deg,#6366f1 0%,#4f46e5 100%);
        box-shadow: 0 8px 18px rgba(79,70,229,.25);
    }
    .status-btn--active:hover { background: linear-gradient(180deg,#5457ee 0%,#463fd3 100%); border-color: #463fd3; }
    .status-icon { font-size: .95em; line-height: 1; opacity: .9; }
    .badge-pill {
        display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;padding:0 .4rem;
        border-radius:999px;font-weight:700;font-size:.75rem;line-height:1;background:#eef2ff;color:#4338ca;border:1px solid rgba(0,0,0,.04)
    }
    /* Keep the header controls compact to avoid big white space above the table */
    .page-header-row { row-gap: .5rem; }
</style>

<!-- ===== Header (title + status filters + actions) ===== -->
<div class="container-fluid px-2">
    <div class="row align-items-center justify-content-between page-header-row mb-3">
        <div class="col-auto">
            <h4 class="mb-2 fw-semibold">List of Applicants</h4>
            <?php
            // render status buttons with count badges (CSNK-like)
            $qParam = $_SESSION[$SESSION_KEY_Q] ?? '';
            $renderBtn = function(string $label, string $value, string $currentStatus, string $q, string $icon, int $count) {
                $isActive = ($value === $currentStatus) || ($value === 'all' && $currentStatus === 'all');
                $href = 'applicants.php?status=' . urlencode($value);
                if ($q !== '') $href .= '&q=' . urlencode($q);
                $classes = 'status-btn' . ($isActive ? ' status-btn--active' : '');
                $iconHtml = $icon !== '' ? '<i class="status-icon ' . h($icon) . '"></i>' : '';
                $countHtml = '<span class="badge-pill ms-1">' . (int)$count . '</span>';
                return '<a href="' . h($href) . '" class="' . $classes . '">' .
                       $iconHtml . '<span>' . h($label) . '</span>' . $countHtml . '</a>';
            };
            echo '<div class="status-group">';
            echo $renderBtn('All',        'all',        $status, $qParam, 'bi bi-list-ul',         $counts['all']);
            echo $renderBtn('Pending',    'pending',    $status, $qParam, 'bi bi-hourglass-split', $counts['pending']);
            echo $renderBtn('On-Process', 'on_process', $status, $qParam, 'bi bi-arrow-repeat',    $counts['on_process']);
            echo $renderBtn('Hired',      'approved',   $status, $qParam, 'bi bi-check2-circle',   $counts['approved']);
            echo '</div>';
            ?>
        </div>

        <div class="col-auto">
            <div class="d-flex align-items-center gap-2">
                <a href="<?php echo h($exportUrl); ?>" class="btn btn-success">
                    <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
                </a>
                <a href="add-applicant.php<?php echo h($preserveQSWithQuestion); ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add New Applicant
                </a>
            </div>
        </div>
    </div>

    <!-- ===== Search (right-aligned, like CSNK) ===== -->
    <div class="row mb-2">
        <div class="col-12 d-flex justify-content-end">
            <form method="get" action="applicants.php" class="d-flex" role="search" style="max-width: 460px; width: 100%;">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" placeholder="Search applicants..."
                           value="<?php echo h($q); ?>" autocomplete="off">
                    <input type="hidden" name="status" value="<?php echo h($status); ?>">
                    <button class="btn btn-outline-secondary" type="submit" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                    <?php if ($q !== ''): ?>
                        <a class="btn btn-outline-secondary"
                           href="applicants.php?clear=1<?php echo $status !== 'all' ? ('&status=' . urlencode($status)) : ''; ?>"
                           title="Clear search">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== Table ===== -->
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
                                    No applicants found. Click "Add New Applicant" to get started.
                                <?php else: ?>
                                    No results for "<strong><?php echo h($q); ?></strong>".
                                    <a href="applicants.php?clear=1<?php echo $status !== 'all' ? ('&status=' . urlencode($status)) : ''; ?>" class="ms-2">Clear search</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applicants as $app): ?>
                            <?php
                            $currentStatus = (string)($app['status'] ?? '');
                            $params = [];
                            if ($q !== '')         $params['q'] = $q;
                            if ($status !== 'all') $params['status'] = $status;
                            $tail  = !empty($params) ? ('&' . http_build_query($params)) : '';

                            $viewUrl = 'view-applicant.php?id=' . (int)$app['id'] . $tail;
                            $editUrl = 'edit-applicant.php?id=' . (int)$app['id'] . $tail;
                            $delUrl  = 'applicants.php?action=delete&id=' . (int)$app['id'] . $tail;

                            $statusColors = [
                                'pending'    => 'warning',
                                'on_process' => 'info',
                                'approved'   => 'success',
                                'deleted'    => 'secondary'
                            ];
                            $badgeColor = $statusColors[$currentStatus] ?? 'secondary';
                            ?>
                            <tr>
                                <td>
                                    <?php if (!empty($app['picture'])): ?>
                                        <img src="<?php echo h(getFileUrl($app['picture'])); ?>"
                                             alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?></strong>
                                </td>
                                <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                                <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                                <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo h(ucfirst(str_replace('_', ' ', $currentStatus))); ?>
                                    </span>
                                </td>
                                <td><?php echo h(formatDate($app['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="<?php echo h($viewUrl); ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo h($editUrl); ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if ($currentStatus === 'pending'): ?>
                                            <a href="<?php echo h($delUrl); ?>"
                                               class="btn btn-sm btn-danger delete-btn" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this applicant?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

        <!-- Export button also available below (optional); comment out if not needed
        <div class="card-footer bg-white d-flex justify-content-end">
            <a href="<?php echo h($exportUrl); ?>" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
            </a>
        </div>
        -->
    </div>
</div>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>