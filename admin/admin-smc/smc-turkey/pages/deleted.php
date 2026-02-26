<?php
// FILE: admin/admin-smc/smc-turkey/pages/deleted.php (SMC - Turkey)
// Purpose: SMC-scoped "Deleted Applicants" (trash) with restore/permanent delete (BU-safe)

$pageTitle = 'Deleted Applicants (SMC)';

// SMC header (auth + SMC access + BU guard + opens .content-wrapper)
require_once __DIR__ . '/../includes/header.php';

// Shared model
require_once dirname(__DIR__, 3) . '/includes/Applicant.php';

// Ensure session is active (for storing last search)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant   = new Applicant($database);
$conn        = $database->getConnection();
$currentBuId = (int)($_SESSION['current_bu_id'] ?? 0);

/**
 * --- Search Memory Behavior (SMC) ---
 */
$SESSION_KEY_Q = 'smc_tr_deleted_q';

if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION[$SESSION_KEY_Q]);
    redirect('deleted.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
    $_SESSION[$SESSION_KEY_Q] = $q;
} elseif (!empty($_SESSION[$SESSION_KEY_Q])) {
    $q = (string)$_SESSION[$SESSION_KEY_Q];
}

/**
 * Actions: restore / permanent_delete (BU-safe)
 * - Keep the search in the redirect to preserve context
 */
if (isset($_GET['action']) && isset($_GET['id']) && $conn instanceof mysqli) {
    $id = (int)$_GET['id'];
    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';

    if ($_GET['action'] === 'restore') {
        // BU-safe: clear deleted_at
        $ok = false;
        if ($stmt = $conn->prepare("UPDATE applicants SET deleted_at = NULL WHERE id = ? AND business_unit_id = ?")) {
            $stmt->bind_param("ii", $id, $currentBuId);
            $ok = $stmt->execute();
            $stmt->close();
        }

        if ($ok && isset($auth) && isset($_SESSION['admin_id']) && method_exists($auth, 'logActivity')) {
            $row = $applicant->getById($id, $currentBuId);
            $fullName = null;
            if (is_array($row)) {
                $fullName = getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? '');
            }
            $label = $fullName ?: "ID {$id}";
            $auth->logActivity((int)$_SESSION['admin_id'], 'Restore Applicant', "Restored applicant {$label} (SMC)");
        }

        setFlashMessage($ok ? 'success' : 'error', $ok ? 'Applicant restored successfully.' : 'Failed to restore applicant.');
        redirect('deleted.php' . $qs);
        exit;
    }

    if ($_GET['action'] === 'permanent_delete') {
        // BU-safe: hard delete from applicants (and optionally cascade related SMC tables if FK not ON DELETE CASCADE)
        $ok = false;

        // You may need to delete child rows first depending on FK constraints:
        // applicant_documents, applicant_status_reports, applicant_reports, client_bookings, etc.
        // Minimal safe order (adjust to your schema config):
        $conn->begin_transaction();
        try {
            // Example: delete docs limited to BU
            if ($stmt = $conn->prepare("DELETE FROM applicant_documents WHERE applicant_id=? AND business_unit_id=?")) {
                $stmt->bind_param("ii", $id, $currentBuId);
                $stmt->execute();
                $stmt->close();
            }
            // Status reports (BU-aware)
            if ($stmt = $conn->prepare("DELETE FROM applicant_status_reports WHERE applicant_id=? AND business_unit_id=?")) {
                $stmt->bind_param("ii", $id, $currentBuId);
                $stmt->execute();
                $stmt->close();
            }
            // Reports (if BU-aware)
            if ($stmt = $conn->prepare("DELETE FROM applicant_reports WHERE applicant_id=? AND business_unit_id=?")) {
                $stmt->bind_param("ii", $id, $currentBuId);
                $stmt->execute();
                $stmt->close();
            }
            // Client bookings (composite FK)
            if ($stmt = $conn->prepare("DELETE FROM client_bookings WHERE applicant_id=? AND business_unit_id=?")) {
                $stmt->bind_param("ii", $id, $currentBuId);
                $stmt->execute();
                $stmt->close();
            }
            // Blacklist (not BU-scoped table; safe to delete all rows for this applicant)
            if ($stmt = $conn->prepare("DELETE FROM blacklisted_applicants WHERE applicant_id=?")) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $stmt->close();
            }
            // Finally delete applicant (BU-safe)
            if ($stmt = $conn->prepare("DELETE FROM applicants WHERE id=? AND business_unit_id=?")) {
                $stmt->bind_param("ii", $id, $currentBuId);
                $ok = $stmt->execute();
                $stmt->close();
            }

            if ($ok) $conn->commit();
            else $conn->rollback();
        } catch (Throwable $e) {
            $conn->rollback();
            $ok = false;
        }

        if ($ok && isset($auth) && isset($_SESSION['admin_id']) && method_exists($auth, 'logActivity')) {
            $auth->logActivity((int)$_SESSION['admin_id'], 'Permanent Delete', "Permanently deleted applicant ID {$id} (SMC)");
        }

        setFlashMessage($ok ? 'success' : 'error', $ok ? 'Applicant permanently deleted.' : 'Failed to permanently delete applicant.');
        redirect('deleted.php' . $qs);
        exit;
    }
}

/**
 * Load deleted applicants (BU-scoped)
 */
$applicants = [];
if ($conn instanceof mysqli && $currentBuId > 0) {
    $sql = "
        SELECT a.*
        FROM applicants a
        WHERE a.business_unit_id = ?
          AND a.deleted_at IS NOT NULL
        ORDER BY a.deleted_at DESC, a.id DESC
    ";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $currentBuId);
        $stmt->execute();
        $res = $stmt->get_result();
        $applicants = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    }
}

/**
 * Helper: Apply case-insensitive contains filter
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

// Preserve the search in action links
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';

// Export URL (SMC includes)
$exportUrl = '../../../includes/excel_deleted-applicants.php' . ($q !== '' ? '?q=' . urlencode($q) : '');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Deleted Applicants</h4>
    <div>
        <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
    </div>
</div>

<!-- 🔎 Search bar -->
<div class="mb-3 d-flex justify-content-end">
    <form action="deleted.php" method="get" class="w-100" style="max-width: 420px;" role="search">
        <div class="input-group" style="max-width: 420px;">
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
                                    <strong><?php echo htmlspecialchars(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix']), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars((string)$app['phone_number'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string)($app['email'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars(formatDateTime($app['deleted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>