<?php
// FILE: pages/applicants.php
$pageTitle = 'List of Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for storing last search)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant = new Applicant($database);

/**
 * --- Search Memory Behavior ---
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['applicants_q']);
    redirect('applicants.php');
    exit;
}

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

/**
 * Delete action: keep the search in the redirect to preserve context.
 */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($applicant->softDelete($id)) {
        $auth->logActivity($_SESSION['admin_id'], 'Delete Applicant', "Deleted applicant ID: $id");
        setFlashMessage('success', 'Applicant deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete applicant.');
    }
    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('applicants.php' . $qs);
    exit;
}

/**
 * Load applicants and apply search (server-side filter).
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

if ($q !== '') {
    $applicants = filterApplicantsByQuery($applicants, $q);
}

$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">List of Applicants</h4>
    <div>
        <a href="export-excel.php?type=all<?php echo $q !== '' ? '&q=' . urlencode($q) : ''; ?>" class="btn btn-success me-2">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
        <a href="add-applicant.php<?php echo $q !== '' ? '?q=' . urlencode($q) : ''; ?>" class="btn btn-primary">
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
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="applicants.php?clear=1" title="Clear search">
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
                                    <a href="applicants.php?clear=1" class="ms-2">Clear search</a>
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
                                            <?php echo strtoupper(substr($app['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($app['phone_number']); ?></td>
                                <td><?php echo htmlspecialchars($app['email'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars(renderPreferredLocation($app['preferred_location'])); ?>
                                </td>
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
                                        <?php echo ucfirst(str_replace('_', ' ', $app['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($app['created_at']); ?></td>
                                <td>
                                    <?php
                                        $viewUrl  = 'view-applicant.php?id=' . (int)$app['id'] . ($q !== '' ? '&q=' . urlencode($q) : '');
                                        $editUrl  = 'edit-applicant.php?id=' . (int)$app['id'] . ($q !== '' ? '&q=' . urlencode($q) : '');
                                        $delUrl   = 'applicants.php?action=delete&id=' . (int)$app['id'] . ($q !== '' ? '&q=' . urlencode($q) : '');
                                    ?>
                                    <div class="btn-group">
                                        <a href="<?php echo $viewUrl; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?php echo $editUrl; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="<?php echo $delUrl; ?>" class="btn btn-sm btn-danger delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this applicant?');">
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