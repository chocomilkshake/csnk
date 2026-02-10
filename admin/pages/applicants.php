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
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="mb-1 fw-semibold">List of Applicants</h4>
        <!-- Status filter buttons (top-left) -->
        <div class="btn-group mt-2" role="group" aria-label="Status filters">
            <?php
                // Helper to render a status button
                function statusBtn(string $label, string $value, string $currentStatus): string {
                    $isActive = ($value === $currentStatus) || ($value === 'all' && $currentStatus === 'all');
                    $btnClass = $isActive ? 'btn btn-sm btn-primary' : 'btn btn-sm btn-outline-primary';
                    $qParam   = isset($_SESSION['applicants_q']) && $_SESSION['applicants_q'] !== '' ? ('&q=' . urlencode((string)$_SESSION['applicants_q'])) : '';
                    $href     = 'applicants.php?status=' . urlencode($value) . $qParam;
                    return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" class="' . $btnClass . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
                }

                echo statusBtn('All', 'all', $status);
                echo statusBtn('Pending', 'pending', $status);
                echo statusBtn('On-Process', 'on_process', $status);
                echo statusBtn('Hired', 'approved', $status);
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