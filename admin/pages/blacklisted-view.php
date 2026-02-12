<?php
$pageTitle = 'Blacklist Details';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Only admin / super admin can view
$role         = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to view blacklist details.');
    redirect('dashboard.php');
}

$blacklistId = (int)($_GET['id'] ?? 0);
if ($blacklistId <= 0) {
    setFlashMessage('error', 'Invalid blacklist record.');
    redirect('blacklisted.php');
}

// Ensure CSRF for revert modal
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
$csrfToken = $_SESSION['csrf_token'];

$conn = $database->getConnection();
$record = null;
$history = [];

if ($conn instanceof mysqli) {
    $sql = "
        SELECT
            b.*,
            a.first_name, a.middle_name, a.last_name, a.suffix, a.picture, a.status,
            au.full_name AS created_by_name, au.username AS created_by_username,
            ru.full_name AS reverted_by_name, ru.username AS reverted_by_username
        FROM blacklisted_applicants b
        LEFT JOIN applicants a ON a.id = b.applicant_id
        LEFT JOIN admin_users au ON au.id = b.created_by
        LEFT JOIN admin_users ru ON ru.id = b.reverted_by
        WHERE b.id = ?
        LIMIT 1
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $blacklistId);
        $stmt->execute();
        $res = $stmt->get_result();
        $record = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }

    if ($record && !empty($record['applicant_id'])) {
        $applicantId = (int)$record['applicant_id'];
        $sqlHist = "
            SELECT
                b.*,
                au.full_name AS created_by_name, au.username AS created_by_username,
                ru.full_name AS reverted_by_name, ru.username AS reverted_by_username
            FROM blacklisted_applicants b
            LEFT JOIN admin_users au ON au.id = b.created_by
            LEFT JOIN admin_users ru ON ru.id = b.reverted_by
            WHERE b.applicant_id = ?
            ORDER BY b.created_at DESC
        ";
        if ($stmtH = $conn->prepare($sqlHist)) {
            $stmtH->bind_param("i", $applicantId);
            $stmtH->execute();
            $resH = $stmtH->get_result();
            $history = $resH ? $resH->fetch_all(MYSQLI_ASSOC) : [];
            $stmtH->close();
        }
    }
}

function jsonToList(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('strval', $arr)));
}

if (!$record) {
    setFlashMessage('error', 'Blacklist record not found.');
    redirect('blacklisted.php');
}

$appName = getFullName(
    $record['first_name'] ?? '',
    $record['middle_name'] ?? '',
    $record['last_name'] ?? '',
    $record['suffix'] ?? ''
);
$createdBy  = $record['created_by_name'] ?: ($record['created_by_username'] ?: 'System');
$revertedBy = $record['reverted_by_name'] ?: ($record['reverted_by_username'] ?: '');

$proofs           = jsonToList($record['proof_paths'] ?? null);
$complianceProofs = jsonToList($record['compliance_proof_paths'] ?? null);

$isActive = (int)($record['is_active'] ?? 0) === 1;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-1 fw-semibold">Blacklist Details</h5>
        <small class="text-muted">Full details, proofs, and history.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="blacklisted.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        <a href="view-applicant.php?id=<?php echo (int)$record['applicant_id']; ?>" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-person-vcard me-1"></i>Applicant Profile
        </a>
        <?php if ($isActive): ?>
            <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#revertModal">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Revert
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="d-flex align-items-center gap-3">
            <?php if (!empty($record['picture'])): ?>
                <img src="<?php echo htmlspecialchars(getFileUrl($record['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                     class="rounded-circle" width="64" height="64" style="object-fit: cover;" alt="Photo">
            <?php else: ?>
                <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
                     style="width: 64px; height: 64px;">
                    <?php echo strtoupper(substr((string)($record['first_name'] ?? ''), 0, 1)); ?>
                </div>
            <?php endif; ?>

            <div class="flex-grow-1">
                <div class="fw-semibold fs-5"><?php echo htmlspecialchars($appName, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="text-muted small">
                    Applicant ID: <?php echo (int)$record['applicant_id']; ?>
                    <?php if ($isActive): ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-2">Active Blacklist</span>
                    <?php else: ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle ms-2">Reverted</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <hr>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="small text-muted mb-1">Reason</div>
                <div class="fw-semibold text-danger"><?php echo htmlspecialchars((string)($record['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="col-md-6">
                <div class="small text-muted mb-1">Logged by</div>
                <div class="fw-semibold"><?php echo htmlspecialchars($createdBy, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="text-muted small"><?php echo htmlspecialchars(formatDateTime($record['created_at']), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>

            <?php if (!empty($record['issue'])): ?>
                <div class="col-12">
                    <div class="small text-muted mb-1">Issue / Details</div>
                    <div><?php echo nl2br(htmlspecialchars((string)$record['issue'], ENT_QUOTES, 'UTF-8')); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-3">
            <div class="small text-muted mb-2">Original Proofs</div>
            <?php if (empty($proofs)): ?>
                <div class="text-muted">None</div>
            <?php else: ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($proofs as $i => $p): ?>
                        <?php $url = getFileUrl($p); ?>
                        <a class="btn btn-sm btn-outline-secondary" target="_blank"
                           href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="bi bi-paperclip me-1"></i>Proof <?php echo $i + 1; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php if (!$isActive): ?>
            <div class="mt-4">
                <div class="small text-muted mb-2">Revert / Compliance</div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="small text-muted mb-1">Reverted by</div>
                        <div class="fw-semibold"><?php echo htmlspecialchars($revertedBy !== '' ? $revertedBy : '—', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="text-muted small"><?php echo !empty($record['reverted_at']) ? htmlspecialchars(formatDateTime($record['reverted_at']), ENT_QUOTES, 'UTF-8') : '—'; ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-muted mb-1">Compliance note</div>
                        <div><?php echo !empty($record['compliance_note']) ? nl2br(htmlspecialchars((string)$record['compliance_note'], ENT_QUOTES, 'UTF-8')) : '<span class="text-muted">—</span>'; ?></div>
                    </div>
                </div>

                <div class="mt-3">
                    <div class="small text-muted mb-2">Compliance proofs</div>
                    <?php if (empty($complianceProofs)): ?>
                        <div class="text-muted">None</div>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($complianceProofs as $i => $p): ?>
                                <?php $url = getFileUrl($p); ?>
                                <a class="btn btn-sm btn-outline-success" target="_blank"
                                   href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>">
                                    <i class="bi bi-file-earmark-check me-1"></i>File <?php echo $i + 1; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <div>
            <h6 class="mb-0 fw-semibold">Blacklist History</h6>
            <small class="text-muted">All blacklist records for this applicant (including reverted).</small>
        </div>
        <span class="badge bg-light text-dark"><?php echo count($history); ?> record(s)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 22%;">When</th>
                        <th>Reason</th>
                        <th style="width: 16%;">Status</th>
                        <th style="width: 22%;">Logged by</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No history.</td></tr>
                    <?php else: ?>
                        <?php foreach ($history as $h): ?>
                            <?php
                                $active = (int)($h['is_active'] ?? 0) === 1;
                                $who = $h['created_by_name'] ?: ($h['created_by_username'] ?: 'System');
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars(formatDateTime($h['created_at']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if (!$active && !empty($h['reverted_at'])): ?>
                                        <div class="text-muted small">Reverted: <?php echo htmlspecialchars(formatDateTime($h['reverted_at']), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold text-danger"><?php echo htmlspecialchars((string)($h['reason'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php if (!empty($h['issue'])): ?>
                                        <div class="text-muted small text-truncate" style="max-width: 520px;"><?php echo htmlspecialchars((string)$h['issue'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($active): ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">Reverted</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="fw-semibold"><?php echo htmlspecialchars($who, ENT_QUOTES, 'UTF-8'); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($isActive): ?>
    <!-- Revert Modal -->
    <div class="modal fade" id="revertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-arrow-counterclockwise me-2"></i>Revert Blacklist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="revert-blacklist.php" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="blacklist_id" value="<?php echo (int)$record['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">

                        <div class="mb-3">
                            <label class="form-label">Compliance Note <span class="text-muted small">(Optional)</span></label>
                            <textarea name="compliance_note" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Compliance Proof <span class="text-muted small">(Optional)</span></label>
                            <input type="file" name="compliance_proofs[]" class="form-control" accept="image/*,.pdf,.doc,.docx" multiple>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-circle me-2"></i>Confirm Revert</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>