<?php
$pageTitle = 'List of Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($applicant->softDelete($id)) {
        $auth->logActivity($_SESSION['admin_id'], 'Delete Applicant', "Deleted applicant ID: $id");
        setFlashMessage('success', 'Applicant deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete applicant.');
    }
    // Safe redirect (functions.php updated to handle "headers already sent")
    redirect('applicants.php');
}

$applicants = $applicant->getAll();

/**
 * Helper: Render preferred_location JSON as clean text.
 * - Shows comma-separated cities (e.g., "Biringan City, Capiz City")
 * - If too long, show only the first city
 */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) {
        return 'N/A';
    }
    // Attempt to decode JSON array
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        // In case it's not valid JSON, just strip quotes/brackets best-effort
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    // Keep only non-empty strings
    $cities = array_values(array_filter(array_map('trim', $arr), function($v){
        return is_string($v) && $v !== '';
    }));

    if (empty($cities)) {
        return 'N/A';
    }

    // Prepare full label
    $full = implode(', ', $cities);

    // If too long, show only the first city
    if (mb_strlen($full) > $maxLen) {
        return $cities[0];
    }

    return $full;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">List of Applicants</h4>
    <div>
        <a href="export-excel.php?type=all" class="btn btn-success me-2">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
        <a href="add-applicant.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Applicant
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="applicantsTable">
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
                                No applicants found. Click "Add New Applicant" to get started.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applicants as $app): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($app['picture'])): ?>
                                        <img src="<?php echo getFileUrl($app['picture']); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
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
                                    <?php
                                        // Decode and render preferred_location nicely
                                        echo htmlspecialchars(renderPreferredLocation($app['preferred_location']));
                                    ?>
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
                                    <div class="btn-group">
                                        <a href="view-applicant.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit-applicant.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="applicants.php?action=delete&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-danger delete-btn" title="Delete" onclick="return confirm('Are you sure you want to delete this applicant?');">
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