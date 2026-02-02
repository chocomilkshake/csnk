<?php
$pageTitle = 'Deleted Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);

if (isset($_GET['action'])) {
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

    redirect('deleted.php');
}

$applicants = $applicant->getDeleted();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">Deleted Applicants</h4>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
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
                                No deleted applicants.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applicants as $app): ?>
                            <tr>
                                <td>
                                    <?php if ($app['picture']): ?>
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
                                <td><?php echo formatDate($app['deleted_at']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="deleted.php?action=restore&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-success" title="Restore">
                                            <i class="bi bi-arrow-counterclockwise"></i> Restore
                                        </a>
                                        <a href="deleted.php?action=permanent_delete&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-danger delete-btn" title="Permanent Delete">
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
