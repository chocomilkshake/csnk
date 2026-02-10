<?php
// FILE: pages/deleted.php
$pageTitle = 'Deleted Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for storing last search)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant = new Applicant($database);

/**
 * --- Search Memory Behavior (same pattern as applicants.php) ---
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['deleted_q']);
    redirect('deleted.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    // Limit length to avoid abuse
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }
    $_SESSION['deleted_q'] = $q;
} elseif (!empty($_SESSION['deleted_q'])) {
    $q = (string)$_SESSION['deleted_q'];
}

/**
 * Actions: restore / permanent_delete
 * - Keep the search in the redirect to preserve context
 */
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($_GET['action'] === 'restore') {
        if ($applicant->restore($id)) {
            $auth->logActivity($_SESSION['admin_id'], 'Restore Applicant', "Restored applicant ID: $id");
            setFlashMessage('success', 'Applicant restored successfully.');
        } else {
            setFlashMessage('error', 'Failed to restore applicant.');
        }
    } elseif ($_GET['action'] === 'permanent_delete') {
        if ($applicant->permanentDelete($id)) {
            $auth->logActivity($_SESSION['admin_id'], 'Permanent Delete', "Permanently deleted applicant ID: $id");
            setFlashMessage('success', 'Applicant permanently deleted.');
        } else {
            setFlashMessage('error', 'Failed to permanently delete applicant.');
        }
    }

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('deleted.php' . $qs);
    exit;
}

/**
 * Load deleted applicants and apply search (server-side filter in PHP).
 * If you prefer DB-level search for performance, send me Applicant.php and I’ll add it there.
 */
$applicants = $applicant->getDeleted();

/**
 * Helper: Apply a case-insensitive contains filter across multiple fields.
 * (name variants, email, phone)
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

        // Combine a few name variants
        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $haystack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone
        ]));

        return mb_strpos($haystack, $needle) !== false;
    }));
}

if ($q !== '') {
    $applicants = filterApplicantsByQuery($applicants, $q);
}

// For preserving the search in action links
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';

// Export URL — call the includes exporter directly so the button downloads the .xlsx
$exportUrl = '../includes/excel_deleted-applicants.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Deleted Applicants</h4>
    <div>
        <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
    </div>
</div>

<!-- 🔎 Search bar placed BETWEEN the header/buttons and the list -->
<div class="mb-3">
    <form action="deleted.php" method="get" class="d-flex" role="search">
        <div class="input-group" style="max-width: 40px;">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search deleted applicants..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a href="deleted.php?clear=1" class="btn btn-outline-danger" title="Clear search">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-styled">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>Deleted Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applicants)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                <?php if ($q === ''): ?>
                                    No deleted applicants.
                                <?php else: ?>
                                    No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                                    <a href="deleted.php?clear=1" class="ms-1">Clear search</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applicants as $app): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($app['picture'])): ?>
                                        <img src="<?php echo htmlspecialchars(getFileUrl($app['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                                             alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <?php echo strtoupper(substr((string)$app['first_name'], 0, 1)); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars((string)$app['phone_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($app['email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo formatDate($app['deleted_at']); ?></td>
                                <td>
                                    <?php
                                        $restoreUrl = 'deleted.php?action=restore&id=' . (int)$app['id'] . $preserveQ;
                                        $permaUrl   = 'deleted.php?action=permanent_delete&id=' . (int)$app['id'] . $preserveQ;
                                    ?>
                                    <div class="btn-group">
                                        <a href="<?php echo htmlspecialchars($restoreUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-success" title="Restore">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                                        </a>
                                        <a href="<?php echo htmlspecialchars($permaUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-danger delete-btn" title="Permanent Delete"
                                           onclick="return confirm('This will permanently delete the applicant. Continue?');">
                                            <i class="bi bi-trash-fill"></i> Delete Forever
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