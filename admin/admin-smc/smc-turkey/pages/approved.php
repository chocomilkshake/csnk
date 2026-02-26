<?php
// FILE: admin/admin-smc/smc-turkey/pages/approved.php (SMC - Turkey)
// Purpose: SMC-scoped "Approved Applicants" list, BU-safe actions and search.

$pageTitle = 'Approved Applicants (SMC)';

// SMC header (auth + SMC access + BU guard + opens .content-wrapper)
require_once __DIR__ . '/../includes/header.php';

// Shared model
require_once dirname(__DIR__, 3) . '/includes/Applicant.php';

// Ensure session is active (for search persistence + CSRF)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

$applicant   = new Applicant($database);
$conn        = $database->getConnection(); // MySQLi
$currentBuId = (int)($_SESSION['current_bu_id'] ?? 0);

// Allowed statuses to transition to
$allowedStatuses = ['pending', 'on_process', 'approved'];

/* ---------- Namespaced search memory (SMC) ---------- */
$SESSION_KEY_Q = 'smc_tr_approved_q';

/**
 * Search Memory Behavior
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION[$SESSION_KEY_Q]);
    redirect('approved.php');
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

/* =========================================================
 * Soft delete (optional) — BU-safe
 * =========================================================*/
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $deleted = false;

    if ($conn instanceof mysqli) {
        // Ensure applicant belongs to current BU (safety)
        if ($stmt = $conn->prepare("UPDATE applicants SET deleted_at = NOW() WHERE id = ? AND business_unit_id = ?")) {
            $stmt->bind_param("ii", $id, $currentBuId);
            $deleted = $stmt->execute();
            $stmt->close();
        }
    }

    if (function_exists('setFlashMessage')) {
        setFlashMessage($deleted ? 'success' : 'error', $deleted ? 'Applicant deleted successfully.' : 'Failed to delete applicant.');
    }

    if ($deleted && isset($auth) && isset($_SESSION['admin_id']) && method_exists($auth, 'logActivity')) {
        $row = $applicant->getById($id, $currentBuId);
        $fullName = null;
        if (is_array($row)) {
            $fullName = getFullName(
                $row['first_name'] ?? '',
                $row['middle_name'] ?? '',
                $row['last_name'] ?? '',
                $row['suffix'] ?? ''
            );
        }
        $label = $fullName ?: "ID {$id}";
        $auth->logActivity(
            (int)$_SESSION['admin_id'],
            'Delete Applicant',
            "Deleted applicant {$label} (Approved list, SMC)"
        );
    }

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('approved.php' . $qs);
    exit;
}

/* =========================================================
 * Change status (GET) — BU-safe + report log
 * =========================================================*/
if (isset($_GET['action'], $_GET['id'], $_GET['to']) && $_GET['action'] === 'update_status') {
    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));

    if (in_array($to, $allowedStatuses, true) && $conn instanceof mysqli) {
        $updated    = false;
        $fromStatus = null;

        // Fetch current status + BU check
        if ($stmt = $conn->prepare("SELECT status FROM applicants WHERE id = ? AND business_unit_id = ? LIMIT 1")) {
            $stmt->bind_param("ii", $id, $currentBuId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($row) $fromStatus = (string)$row['status'];
        }

        if ($fromStatus !== null) {
            // Update status BU-safely
            if ($stmtUp = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ? AND business_unit_id = ?")) {
                $stmtUp->bind_param("sii", $to, $id, $currentBuId);
                $updated = $stmtUp->execute();
                $stmtUp->close();
            }

            // Write applicant_status_reports (SMC requires business_unit_id)
            if ($updated && $fromStatus !== $to) {
                $adminId    = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                $reportText = 'Status changed from ' . ucfirst(str_replace('_', ' ', $fromStatus)) . ' to ' . ucfirst(str_replace('_', ' ', $to));
                if ($stmtRep = $conn->prepare("
                    INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ")) {
                    $stmtRep->bind_param("iisssi", $id, $currentBuId, $fromStatus, $to, $reportText, $adminId);
                    $stmtRep->execute();
                    $stmtRep->close();
                }
            }
        }

        if (function_exists('setFlashMessage')) {
            if ($updated) setFlashMessage('success', 'Status updated successfully.');
            else setFlashMessage('error', 'Failed to update status. Please try again.');
        }

        if ($updated && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
            $row = $applicant->getById($id, $currentBuId);
            $fullName = null;
            if (is_array($row)) {
                $fullName = getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? '');
            }
            $label = $fullName ?: "ID {$id}";
            $auth->logActivity(
                (int)$_SESSION['admin_id'],
                'Update Applicant Status',
                "Updated status for {$label} → {$to}"
            );
        }
    } else {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Invalid status selected.');
    }

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('approved.php' . $qs);
    exit;
}

/* =========================================================
 * Load approved applicants (BU-scoped, excludes blacklisted & soft-deleted)
 * =========================================================*/
$applicants = $applicant->getAll('approved', $currentBuId);

/* =========================================================
 * Helpers
 * =========================================================*/
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) return 'N/A';

    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }

    $cities = array_values(array_filter(array_map('trim', $arr), fn($v) => is_string($v) && $v !== ''));
    if (empty($cities)) return 'N/A';

    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen) return $cities[0];
    return $full;
}

function filterRowsByQuery(array $rows, string $query): array {
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);

    return array_values(array_filter($rows, function(array $row) use ($needle) {
        $first  = (string)($row['first_name']   ?? '');
        $middle = (string)($row['middle_name']  ?? '');
        $last   = (string)($row['last_name']    ?? '');
        $suffix = (string)($row['suffix']       ?? '');

        $email  = (string)($row['email']        ?? '');
        $phone  = (string)($row['phone_number'] ?? '');
        $loc    = renderPreferredLocation($row['preferred_location'] ?? null, 999);

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
    $applicants = filterRowsByQuery($applicants, $q);
}

/* ---------- Preserve the search in action links and export URL (SMC includes) ---------- */
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../../../includes/excel_approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Fix dropdown clipping & remove table scroll wrapper ===== -->
<style>
    .table-card, .table-card .card-body { overflow: visible !important; }
    .table-card .table-responsive { overflow: visible !important; }
    .table-card table, .table-card tbody, .table-card tr { overflow: visible !important; }
    td.actions-cell { position: relative; overflow: visible; z-index: 10; white-space: nowrap; }

    .dd-modern .dropdown-menu {
        border-radius: .75rem; border: 1px solid #e5e7eb; box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        z-index: 9999 !important; min-width: 160px;
    }
    .dd-modern .dropdown-item { display: flex; align-items: center; gap: .5rem; padding: .55rem .9rem; font-weight: 500; }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled,
    .dd-modern .dropdown-item:disabled { color: #9aa0a6; background-color: transparent; pointer-events: none; }
    .btn-status { border-radius: .75rem; }
    table.table-styled { margin-bottom: 0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Approved Applicants</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- 🔎 Search bar on the right -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="approved.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search approved applicants..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="approved.php?clear=1" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <table class="table table-bordered table-striped table-hover table-styled align-middle">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Applicant</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Preferred Location</th>
                    <th>Date Approved</th>
                    <th style="width: 420px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <?php if ($q === ''): ?>
                                No approved applicants yet.
                            <?php else: ?>
                                No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                                <a href="approved.php?clear=1" class="ms-1">Clear search</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $row): ?>
                        <?php
                            $id            = (int)$row['id'];
                            $currentStatus = (string)($row['status'] ?? 'approved');
                            $fullName      = getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']);

                            // View/Edit within SMC pages
                            $viewUrl   = 'view-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $editUrl   = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $deleteUrl = 'approved.php?action=delete&id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');

                            // Change status targets (preserve q)
                            $toPendingUrl   = 'approved.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl = 'approved.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl  = 'approved.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['picture'])): ?>
                                    <img
                                        src="<?php echo htmlspecialchars(getFileUrl($row['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="Photo"
                                        class="rounded"
                                        width="50"
                                        height="50"
                                        style="object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr((string)$row['first_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></div>
                            </td>
                            <td><?php echo htmlspecialchars($row['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(renderPreferredLocation($row['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($row['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="actions-cell">
                                <div class="btn-group dd-modern dropup">
                                    <!-- View -->
                                    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- (Optional) Delete -->
                                    <a href="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-danger"
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this applicant?');">
                                        <i class="bi bi-trash"></i>
                                    </a>

                                    <!-- Change Status Dropdown -->
                                    <div class="dropdown dropup">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status"
                                                data-bs-toggle="dropdown"
                                                data-bs-auto-close="true"
                                                aria-expanded="false"
                                                aria-haspopup="true"
                                                title="Change Status"
                                                id="changeStatusBtn-<?php echo $id; ?>">
                                            <i class="bi bi-arrow-left-right me-1"></i> Change Status
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="changeStatusBtn-<?php echo $id; ?>">
                                            <li>
                                                <a class="dropdown-item <?php echo $currentStatus === 'pending' ? 'disabled' : ''; ?>"
                                                   href="<?php echo $currentStatus === 'pending' ? '#' : htmlspecialchars($toPendingUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-hourglass-split text-warning"></i>
                                                    <span>Pending</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo $currentStatus === 'on_process' ? 'disabled' : ''; ?>"
                                                   href="<?php echo $currentStatus === 'on_process' ? '#' : htmlspecialchars($toOnProcessUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-arrow-repeat text-info"></i>
                                                    <span>On-Process</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo $currentStatus === 'approved' ? 'disabled' : ''; ?>"
                                                   href="<?php echo $currentStatus === 'approved' ? '#' : htmlspecialchars($toApprovedUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-check2-circle text-success"></i>
                                                    <span>Approved</span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Initialize dropdowns with fixed popper to avoid clipping
    var btns = document.querySelectorAll('.btn-status[data-bs-toggle="dropdown"]');
    btns.forEach(function(btn) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            new bootstrap.Dropdown(btn, { boundary: 'viewport', popperConfig: { strategy: 'fixed' } });
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>