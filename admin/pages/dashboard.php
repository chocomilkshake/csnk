<?php
$pageTitle = 'Dashboard';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';
require_once '../includes/Admin.php';

$applicant = new Applicant($database);
$admin = new Admin($database);

$stats = $applicant->getStatistics();
$recentApplicants = array_slice($applicant->getAll(), 0, 5);
$adminCount = count($admin->getAll());

// Recent activity logs for admins / super admins
$recentActivities = [];
if (!empty($currentUser) && in_array($currentUser['role'] ?? 'employee', ['admin', 'super_admin'], true)) {
    $conn = $database->getConnection();
    if ($conn instanceof mysqli) {
        $sql = "
            SELECT al.id,
                   al.action,
                   al.description,
                   al.created_at,
                   au.full_name,
                   au.username,
                   au.role
            FROM activity_logs AS al
            LEFT JOIN admin_users AS au ON al.admin_id = au.id
            ORDER BY al.created_at DESC
            LIMIT 8
        ";
        if ($result = $conn->query($sql)) {
            $recentActivities = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}
?>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card stat-card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-2">Total Applicants</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['total']; ?></h2>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-2">Pending</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['pending']; ?></h2>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-2">On Process</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['on_process']; ?></h2>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card stat-card bg-danger text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-white-50 mb-2">Deleted</h6>
                        <h2 class="mb-0 fw-bold"><?php echo $stats['deleted']; ?></h2>
                    </div>
                    <div class="fs-1 opacity-50">
                        <i class="bi bi-trash"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0 fw-semibold">Recent Applicants</h5>
                    <small class="text-muted">Latest profiles created in the system.</small>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Date Applied</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentApplicants)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No applicants yet</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentApplicants as $applicantData): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($applicantData['picture']): ?>
                                                    <img src="<?php echo getFileUrl($applicantData['picture']); ?>" alt="Profile" class="rounded-circle me-2" width="40" height="40">
                                                <?php else: ?>
                                                    <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px;">
                                                        <?php echo strtoupper(substr($applicantData['first_name'], 0, 1)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <strong><?php echo getFullName($applicantData['first_name'], $applicantData['middle_name'], $applicantData['last_name'], $applicantData['suffix']); ?></strong>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($applicantData['phone_number']); ?></td>
                                        <td>
                                            <?php
                                            $statusColors = [
                                                'pending' => 'warning',
                                                'on_process' => 'info',
                                                'approved' => 'success'
                                            ];
                                            $badgeColor = $statusColors[$applicantData['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $badgeColor; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $applicantData['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($applicantData['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-4 border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">Quick Stats</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Admin Accounts</span>
                    <strong><?php echo $adminCount; ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <span class="text-muted">Active Applicants</span>
                    <strong><?php echo $stats['total']; ?></strong>
                </div>
                <div class="d-flex justify-content-between align-items-center py-2">
                    <span class="text-muted">System Status</span>
                    <span class="badge bg-success">Online</span>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0 fw-semibold">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="add-applicant.php" class="btn btn-primary w-100 mb-2">
                    <i class="bi bi-plus-circle me-2"></i>Add New Applicant
                </a>
                <a href="applicants.php" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-people me-2"></i>View All Applicants
                </a>
                <a href="accounts.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-person-plus me-2"></i>Add New Account
                </a>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($recentActivities) && in_array($currentUser['role'] ?? 'employee', ['admin', 'super_admin'], true)): ?>
    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h5 class="mb-0 fw-semibold">Recent Activity</h5>
                        <small class="text-muted">Latest actions by admins and employees across the system.</small>
                    </div>
                    <a href="activity-logs.php" class="btn btn-sm btn-outline-secondary">
                        View all logs
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 28%;">User</th>
                                    <th style="width: 18%;">Role</th>
                                    <th style="width: 18%;">Action</th>
                                    <th>Description</th>
                                    <th style="width: 18%;">When</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivities as $log): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $displayName = $log['full_name'] ?: $log['username'] ?: 'Unknown user';
                                            ?>
                                            <strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        </td>
                                        <td>
                                            <?php if (!empty($log['role'])): ?>
                                                <span class="badge bg-light text-dark text-capitalize">
                                                    <?php echo str_replace('_', ' ', htmlspecialchars($log['role'], ENT_QUOTES, 'UTF-8')); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                                <?php echo htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>
                                        <td class="text-truncate" style="max-width: 340px;">
                                            <?php echo htmlspecialchars($log['description'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td>
                                            <?php echo formatDateTime($log['created_at']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    setInterval(function() {
        location.reload();
    }, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>
