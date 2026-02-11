<?php
$pageTitle = 'Blacklist Applicant';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

$role        = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');
if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to blacklist applicants.');
    redirect('dashboard.php');
}

$applicant = new Applicant($database);

if (!isset($_GET['id'])) {
    redirect('applicants.php');
    exit;
}

$id = (int)$_GET['id'];
$applicantData = $applicant->getById($id);
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('applicants.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim((string)($_POST['reason'] ?? ''));
    $issue  = trim((string)($_POST['issue'] ?? ''));

    if ($reason === '') {
        $errors[] = 'Reason is required.';
    } elseif (mb_strlen($reason) > 255) {
        $errors[] = 'Reason must be 255 characters or less.';
    }

    // Handle proof uploads (optional, multiple)
    $proofPaths = [];
    if (isset($_FILES['proofs']) && is_array($_FILES['proofs']['name'])) {
        $names = $_FILES['proofs']['name'];
        $types = $_FILES['proofs']['type'];
        $tmps  = $_FILES['proofs']['tmp_name'];
        $errs  = $_FILES['proofs']['error'];
        $sizes = $_FILES['proofs']['size'];

        $count = count($names);
        for ($i = 0; $i < $count; $i++) {
            if (($errs[$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $file = [
                'name'     => $names[$i],
                'type'     => $types[$i],
                'tmp_name' => $tmps[$i],
                'error'    => $errs[$i],
                'size'     => $sizes[$i],
            ];
            $saved = uploadFile($file, 'blacklist');
            if ($saved) {
                $proofPaths[] = $saved;
            }
        }
    }

    if (empty($errors)) {
        $conn = $database->getConnection();
        if ($conn instanceof mysqli) {
            $sql = "
                INSERT INTO blacklisted_applicants (applicant_id, reason, issue, proof_paths, created_by)
                VALUES (?, ?, ?, ?, ?)
            ";
            if ($stmt = $conn->prepare($sql)) {
                $proofJson = !empty($proofPaths) ? json_encode($proofPaths) : null;
                $createdBy = (int)($_SESSION['admin_id'] ?? 0);
                $stmt->bind_param(
                    "isssi",
                    $id,
                    $reason,
                    $issue,
                    $proofJson,
                    $createdBy
                );
                if ($stmt->execute()) {
                    // Log activity
                    if (isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
                        $fullName = getFullName(
                            $applicantData['first_name'] ?? '',
                            $applicantData['middle_name'] ?? '',
                            $applicantData['last_name'] ?? '',
                            $applicantData['suffix'] ?? ''
                        );
                        $auth->logActivity(
                            (int)$_SESSION['admin_id'],
                            'Blacklist Applicant',
                            "Blacklisted applicant {$fullName} (ID: {$id}) - Reason: {$reason}"
                        );
                    }
                    setFlashMessage('success', 'Applicant has been blacklisted.');
                    redirect('view-applicant.php?id=' . $id);
                    exit;
                } else {
                    $errors[] = 'Failed to save blacklist record.';
                }
                $stmt->close();
            } else {
                $errors[] = 'Failed to prepare blacklist statement.';
            }
        } else {
            $errors[] = 'Database connection error.';
        }
    }
}

$fullName = getFullName(
    $applicantData['first_name'] ?? '',
    $applicantData['middle_name'] ?? '',
    $applicantData['last_name'] ?? '',
    $applicantData['suffix'] ?? ''
);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-semibold">Blacklist Applicant</h4>
        <small class="text-muted">Mark this applicant as blacklisted with documented reason and proof.</small>
    </div>
    <a href="view-applicant.php?id=<?php echo (int)$id; ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-2"></i>Back to Applicant
    </a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h5 class="fw-semibold mb-3"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></h5>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Reason <span class="text-danger">*</span></label>
                <input type="text" name="reason" class="form-control"
                       value="<?php echo htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                       maxlength="255" required
                       placeholder="Short reason (e.g. Policy violation, Fraud attempt)">
            </div>

            <div class="mb-3">
                <label class="form-label">Details / Issue</label>
                <textarea name="issue" class="form-control" rows="4"
                          placeholder="Describe what happened or which policy was violated."><?php
                    echo htmlspecialchars($_POST['issue'] ?? '', ENT_QUOTES, 'UTF-8');
                ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload Proof (photos, screenshots)</label>
                <input type="file" name="proofs[]" class="form-control" accept="image/*" multiple>
                <small class="text-muted">
                    You can upload multiple images. They will be stored securely in the server's blacklist folder.
                </small>
            </div>

            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="view-applicant.php?id=<?php echo (int)$id; ?>" class="btn btn-outline-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-slash-circle me-2"></i>Confirm Blacklist
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>

