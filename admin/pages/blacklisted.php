<?php
$pageTitle = 'Blacklisted Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Only admin / super admin can view blacklist
$role        = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');
if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to view blacklisted applicants.');
    redirect('dashboard.php');
}

$conn = $database->getConnection();
$rows = [];
if ($conn instanceof mysqli) {
    $sql = "
        SELECT
            b.id,
            b.applicant_id,
            b.reason,
            b.issue,
            b.proof_paths,
            b.created_at,
            a.first_name,
            a.middle_name,
            a.last_name,
            a.suffix,
            a.status,
            au.full_name AS created_by_name,
            au.username  AS created_by_username
        FROM blacklisted_applicants b
        LEFT JOIN applicants a ON a.id = b.applicant_id
        LEFT JOIN admin_users au ON au.id = b.created_by
        ORDER BY b.created_at DESC
    ";
    if ($res = $conn->query($sql)) {
        $rows = $res->fetch_all(MYSQLI_ASSOC);
    }
}

function formatBlacklistProofs(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('strval', $arr)));
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 fw-semibold">Blacklisted Applicants</h5>
        <small class="text-muted">
            Records of applicants who violated company or client policies.
        </small>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Applicant</th>
                        <th>Reason</th>
                        <th>Logged By</th>
                        <th>Proofs</th>
                        <th>When</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No blacklisted applicants recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php
                            $appName = getFullName(
                                $row['first_name'] ?? '',
                                $row['middle_name'] ?? '',
                                $row['last_name'] ?? '',
                                $row['suffix'] ?? ''
                            );
                            $createdBy = $row['created_by_name'] ?: $row['created_by_username'] ?: 'System';
                            $when      = formatDateTime($row['created_at']);
                            $proofs    = formatBlacklistProofs($row['proof_paths'] ?? null);
                            $viewUrl   = 'view-applicant.php?id=' . (int)$row['applicant_id'];
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-muted small">
                                        ID: <?php echo (int)$row['applicant_id']; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-danger">
                                        <?php echo htmlspecialchars($row['reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <?php if (!empty($row['issue'])): ?>
                                        <div class="text-muted small text-truncate" style="max-width: 320px;">
                                            <?php echo htmlspecialchars($row['issue'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($createdBy, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (empty($proofs)): ?>
                                        <span class="text-muted small">None</span>
                                    <?php else: ?>
                                        <div class="d-flex flex-wrap gap-1">
                                            <?php foreach ($proofs as $idx => $path): ?>
                                                <?php $url = getFileUrl($path); ?>
                                                <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                                                   target="_blank"
                                                   class="badge bg-light text-dark text-decoration-none">
                                                    Proof <?php echo $idx + 1; ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="small text-muted"><?php echo htmlspecialchars($when, ENT_QUOTES, 'UTF-8'); ?></span>
                                </td>
                                <td class="text-end">
                                    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-outline-secondary">
                                        View Applicant
                                    </a>
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

