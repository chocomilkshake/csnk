<?php
$pageTitle = 'On Process Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$applicant = new Applicant($database);
$applicants = $applicant->getAll('on_process');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">On Process Applicants</h4>
    <a href="export-excel.php?type=on_process" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
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
                        <th>Location</th>
                        <th>Date Applied</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applicants)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                                No applicants currently on process.
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
                                <td><?php echo htmlspecialchars($app['preferred_location'] ?? 'N/A'); ?></td>
                                <td><?php echo formatDate($app['created_at']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view-applicant.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="edit-applicant.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
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
