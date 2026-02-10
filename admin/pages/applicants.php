<?php
// FILE: pages/applicants.php
$pageTitle = 'List of Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for storing last search & status)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant = new Applicant($database);

/**
 * --- Search & Status Memory Behavior ---
 * - If ?clear=1 → clear stored search (q) only and redirect to same status
 * - If ?q=...   → store in session and use
 * - Else if session has last query → use it
 * - If ?status=... → store in session and use
 * - Else if session has last status → use it
 * - Default status = 'all'
 */
$allowedStatuses = ['all', 'pending', 'on_process', 'approved'];

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['applicants_q']);
    // Preserve current status on clear
    $preserveParams = [];
    $statusFromSession = $_SESSION['applicants_status'] ?? 'all';
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
    // Limit length to avoid abuse
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }
    $_SESSION['applicants_q'] = $q;
} elseif (!empty($_SESSION['applicants_q'])) {
    $q = (string)$_SESSION['applicants_q'];
}

// Handle status memory
$status = 'all';
if (isset($_GET['status'])) {
    $statusCandidate = strtolower(trim((string)$_GET['status']));
    $status = in_array($statusCandidate, $allowedStatuses, true) ? $statusCandidate : 'all';
    $_SESSION['applicants_status'] = $status;
} elseif (!empty($_SESSION['applicants_status'])) {
    $statusCandidate = strtolower((string)$_SESSION['applicants_status']);
    $status = in_array($statusCandidate, $allowedStatuses, true) ? $statusCandidate : 'all';
}

/**
 * Delete action: keep the search & status in the redirect to preserve context.
 */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($applicant->softDelete($id)) {
        $auth->logActivity($_SESSION['admin_id'], 'Delete Applicant', "Deleted applicant ID: $id");
        setFlashMessage('success', 'Applicant deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete applicant.');
    }

    $params = [];
    if ($q !== '') $params['q'] = $q;
    if ($status !== 'all') $params['status'] = $status;

    $qs = !empty($params) ? ('?' . http_build_query($params)) : '';
    redirect('applicants.php' . $qs);
    exit;
}

/**
 * Load applicants and apply filters.
 * getAll() → show active/non-deleted applicants across statuses.
 */
$applicants = $applicant->getAll();

/**
 * Helper: Render preferred_location JSON as clean text.
 */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) {
        return 'N/A';
    }
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), function($v){
        return is_string($v) && $v !== '';
    }));

    if (empty($cities)) {
        return 'N/A';
    }

    $full = implode(', ', $cities);

    if (mb_strlen($full) > $maxLen) {
        return $cities[0];
    }

    return $full;
}

/**
 * Helper: Apply a case-insensitive contains filter across multiple fields.
 */
function filterApplicantsByQuery(array $rows, string $query): array {
    if ($query === '') return $rows;

    $needle = mb_strtolower($query);

    return array_values(array_filter($rows, function(array $app) use ($needle) {
        $first  = (string)($app['first_name']   ?? '');
        $middle = (string)($app['middle_name']  ?? '');
        $last   = (string)($app['last_name']    ?? '');
        $suffix = (string)($app['suffix']       ?? '');
        $email  = (string)($app['email']        ?? '');
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

/**
 * Helper: Filter by status (pending, on_process, approved). 'all' returns as-is.
 */
function filterApplicantsByStatus(array $rows, string $status): array {
    if ($status === 'all') return $rows;
    return array_values(array_filter($rows, function(array $app) use ($status) {
        return isset($app['status']) && $app['status'] === $status;
    }));
}

// Apply status filter first, then search
if ($status !== 'all') {
    $applicants = filterApplicantsByStatus($applicants, $status);
}
if ($q !== '') {
    $applicants = filterApplicantsByQuery($applicants, $q);
}

// Preserve params for links
$paramsForLinks = [];
if ($q !== '') $paramsForLinks['q'] = $q;
if ($status !== 'all') $paramsForLinks['status'] = $status;
$preserveQS = !empty($paramsForLinks) ? ('&' . http_build_query($paramsForLinks)) : '';
$preserveQSWithQuestion = !empty($paramsForLinks) ? ('?' . http_build_query($paramsForLinks)) : '';

// Export URL includes filters
$exportParams = [];
if ($q !== '') $exportParams['q'] = $q;
if ($status !== 'all') $exportParams['status'] = $status;
$exportUrl = '../includes/excel_applicants.php' . (!empty($exportParams) ? ('?' . http_build_query($exportParams)) : '');

?>
<!-- ===== Modern, professional status button styles (self-contained) ===== -->
<style>
    .status-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;          /* slate-200 */
        border-radius: 1rem;                 /* rounded-2xl */
        background: rgba(255,255,255,0.85);  /* white/85 */
        backdrop-filter: saturate(140%) blur(2px);
        box-shadow: 0 1px 2px rgba(0,0,0,.04), 0 1px 3px rgba(0,0,0,.10);
    }
    .status-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .9rem;
        border-radius: .75rem;               /* rounded-xl */
        font-size: .875rem;                  /* text-sm */
        font-weight: 500;                    /* medium */
        text-decoration: none;
        border: 1px solid #cbd5e1;           /* slate-300 */
        color: #334155;                      /* slate-700 */
        background: #ffffff;
        transition: transform .15s ease, box-shadow .15s ease, background .15s ease, border-color .15s ease;
        box-shadow: 0 1px 2px rgba(0,0,0,.04);
    }
    .status-btn:hover {
        transform: translateY(-2px);
        background: #f8fafc;                 /* slate-50 */
        border-color: #94a3b8;               /* slate-400 */
        box-shadow: 0 6px 12px rgba(15, 23, 42, .06);
    }
    .status-btn:focus {
        outline: 3px solid rgba(99,102,241,.35); /* indigo-500 ring */
        outline-offset: 2px;
    }
    .status-btn--active {
        color: #fff;
        border-color: #4f46e5;               /* indigo-600 */
        background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%); /* indigo-500 -> 600 */
        box-shadow: 0 8px 18px rgba(79,70,229,.25);
    }
    .status-btn--active:hover {
        background: linear-gradient(180deg, #5457ee 0%, #463fd3 100%);
        border-color: #463fd3;
        transform: translateY(-2px);
    }
    .status-icon {
        font-size: .95em;
        line-height: 1;
        opacity: .9;
    }
</style>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-1 fw-semibold">List of Applicants</h4>
        <!-- Status filter buttons (modern) -->
        <div class="mt-2">
            <?php
            /**
             * Renders a modern status button that preserves current search,
             * highlights the active status, and uses Bootstrap Icons.
             */
            function renderStatusBtnModern(string $label, string $value, string $currentStatus, string $q = '', string $icon = ''): string {
                $isActive = ($value === $currentStatus) || ($value === 'all' && $currentStatus === 'all');
                $href = 'applicants.php?status=' . urlencode($value);
                if ($q !== '') $href .= '&q=' . urlencode($q);

                $classes = 'status-btn' . ($isActive ? ' status-btn--active' : '');

                $iconHtml = $icon !== '' ? '<i class="status-icon ' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i>' : '';
                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="' . $classes . '">' .
                        $iconHtml . '<span>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span></a>';
            }

            $qParam = $_SESSION['applicants_q'] ?? '';

            echo '<div class="status-group">';
                echo renderStatusBtnModern('All',        'all',        $status, $qParam, 'bi bi-list-ul');
                echo renderStatusBtnModern('Pending',    'pending',    $status, $qParam, 'bi bi-hourglass-split');
                echo renderStatusBtnModern('On-Process', 'on_process', $status, $qParam, 'bi bi-arrow-repeat');
                echo renderStatusBtnModern('Hired',      'approved',   $status, $qParam, 'bi bi-check2-circle');
            echo '</div>';
            ?>
        </div>
    </div>
    <div>
        <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success me-2">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
        <a href="add-applicant.php<?php echo $preserveQSWithQuestion; ?>" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Applicant
        </a>
    </div>
</div>

<div class="mb-3">
    <form method="get" action="applicants.php" class="d-flex justify-content-end">
        <div class="input-group" style="max-width: 420px;">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search applicants..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
            <!-- Preserve selected status when searching -->
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="applicants.php?clear=1<?php echo $status !== 'all' ? ('&status=' . urlencode($status)) : ''; ?>" title="Clear search">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-styled" id="applicantsTable">
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
                                    No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                                    <a href="applicants.php?clear=1<?php echo $status !== 'all' ? ('&status=' . urlencode($status)) : ''; ?>" class="ms-2">Clear search</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applicants as $app): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($app['picture'])): ?>
                                        <img src="<?php echo htmlspecialchars(getFileUrl($app['picture']), ENT_QUOTES, 'UTF-8'); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($app['phone_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($app['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(renderPreferredLocation($app['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php
                                    $statusColors = [
                                        'pending'    => 'warning',
                                        'on_process' => 'info',
                                        'approved'   => 'success',
                                        'deleted'    => 'secondary'
                                    ];
                                    $badgeColor = $statusColors[$app['status']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?php echo $badgeColor; ?>">
                                        <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $app['status'])), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(formatDate($app['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <?php
                                        $params = [];
                                        if ($q !== '') $params['q'] = $q;
                                        if ($status !== 'all') $params['status'] = $status;
                                        $tail = !empty($params) ? ('&' . http_build_query($params)) : '';

                                        $viewUrl  = 'view-applicant.php?id=' . (int)$app['id'] . $tail;
                                        $editUrl  = 'edit-applicant.php?id=' . (int)$app['id'] . $tail;
                                        $delUrl   = 'applicants.php?action=delete&id=' . (int)$app['id'] . $tail;
                                    ?>
                                    <div class="btn-group">
                                        <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo htmlspecialchars($delUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-danger delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this applicant?');">
                                            <i class="bi bi-trash"></i>
                                        </a>
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

<?php require_once '../includes/footer.php'; ?>