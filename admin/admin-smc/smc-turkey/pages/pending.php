<?php
// FILE: admin/admin-smc/smc-turkey/pages/pending.php (SMC - Turkey)
// Purpose: SMC-scoped "Pending Applicants" list (with replacement mode), BU-safe actions.

$pageTitle = 'Pending Applicants (SMC)';

// SMC header (auth + SMC access + BU guard + opens .content-wrapper)
require_once __DIR__ . '/../includes/header.php';

// Shared model
require_once dirname(__DIR__, 3) . '/includes/Applicant.php';

// Ensure session is active (for search persistence)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant   = new Applicant($database);
$conn        = $database->getConnection(); // MySQLi
$currentBuId = (int)($_SESSION['current_bu_id'] ?? 0);

// Allowed statuses to transition to (for UI links)
$allowedStatuses = ['pending', 'on_process', 'approved'];

/* ---------- Namespaced search memory (SMC) ---------- */
$SESSION_KEY_Q = 'smc_tr_pending_q';

/**
 * Search Memory Behavior
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION[$SESSION_KEY_Q]);
    redirect('pending.php');
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
 * Replacement mode (?replace_id=...)
 * =========================================================*/
$replaceId         = isset($_GET['replace_id']) ? (int)$_GET['replace_id'] : 0;
$replaceRecord     = null;
$originalApplicant = null;

if ($replaceId > 0 && $conn instanceof mysqli) {
    // Load replacement row (must be in this BU & status 'selection')
    $stmt = $conn->prepare("
        SELECT id, business_unit_id, original_applicant_id, replacement_applicant_id, reason, status
        FROM applicant_replacements
        WHERE id=? AND business_unit_id=? AND status='selection'
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("ii", $replaceId, $currentBuId);
        $stmt->execute();
        $res = $stmt->get_result();
        $replaceRecord = $res ? $res->fetch_assoc() : null;
        $stmt->close();
    }
    if ($replaceRecord) {
        // Load original applicant (must be in this BU)
        $origId = (int)($replaceRecord['original_applicant_id'] ?? 0);
        $originalApplicant = $applicant->getById($origId, $currentBuId);
        if (!$originalApplicant) {
            // invalid original => cancel replace mode
            $replaceRecord = null;
        }
    }
}

/* =========================================================
 * Actions (BU-safe): update_status or delete
 * NOTE: In replace mode, we do not allow status changes here to avoid conflicts.
 * =========================================================*/
if (isset($_GET['action']) && !$replaceRecord) {
    $action = (string)$_GET['action'];
    $id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Build redirect URL back to this page preserving search
    $qs = ($q !== '') ? ('?q=' . urlencode($q)) : '';

    if ($action === 'update_status' && $id > 0 && isset($_GET['to'])) {
        $to = strtolower(trim((string)$_GET['to']));
        if (in_array($to, $allowedStatuses, true) && $conn instanceof mysqli) {
            $updated    = false;
            $fromStatus = null;

            // Verify applicant belongs to current BU and fetch current status
            if ($stmt = $conn->prepare("SELECT status FROM applicants WHERE id=? AND business_unit_id=? LIMIT 1")) {
                $stmt->bind_param("ii", $id, $currentBuId);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                if ($row) $fromStatus = (string)$row['status'];
            }

            if ($fromStatus !== null) {
                // Update status BU-safely
                if ($stmtUp = $conn->prepare("UPDATE applicants SET status=? WHERE id=? AND business_unit_id=?")) {
                    $stmtUp->bind_param("sii", $to, $id, $currentBuId);
                    $updated = $stmtUp->execute();
                    $stmtUp->close();
                }

                // Record status change in applicant_status_reports (SMC requires business_unit_id)
                if ($updated && $fromStatus !== $to) {
                    $adminId    = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
                    $reportText = 'Status changed from ' . ucfirst(str_replace('_', ' ', $fromStatus)) . ' to ' . ucfirst(str_replace('_', ' ', $to));
                    if ($stmtRep = $conn->prepare("
                        INSERT INTO applicant_status_reports
                            (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")) {
                        $stmtRep->bind_param("iisssi", $id, $currentBuId, $fromStatus, $to, $reportText, $adminId);
                        $stmtRep->execute();
                        $stmtRep->close();
                    }
                }
            }

            if (function_exists('setFlashMessage')) {
                setFlashMessage(($updated ? 'success' : 'error'), $updated ? 'Status updated successfully.' : 'Failed to update status. Please try again.');
            }

            if ($updated && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
                $row = $applicant->getById($id, $currentBuId);
                $fullName = null;
                if (is_array($row)) {
                    $fullName = getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? '');
                }
                $label = $fullName ?: "ID {$id}";
                $auth->logActivity((int)$_SESSION['admin_id'], 'Update Applicant Status', "Updated status for {$label} → {$to}");
            }
        } else {
            if (function_exists('setFlashMessage')) setFlashMessage('error', 'Invalid status selected.');
        }

        redirect('pending.php' . $qs);
        exit;
    }

    if ($action === 'delete' && $id > 0 && $conn instanceof mysqli) {
        $deleted = false;

        // Soft delete (set deleted_at) BU-safely
        if ($stmtDel = $conn->prepare("UPDATE applicants SET deleted_at = NOW() WHERE id=? AND business_unit_id=?")) {
            $stmtDel->bind_param("ii", $id, $currentBuId);
            $deleted = $stmtDel->execute();
            $stmtDel->close();
        }

        if (function_exists('setFlashMessage')) {
            setFlashMessage($deleted ? 'success' : 'error', $deleted ? 'Applicant deleted successfully.' : 'Failed to delete applicant.');
        }

        if ($deleted && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
            $row = $applicant->getById($id, $currentBuId);
            $fullName = null;
            if (is_array($row)) {
                $fullName = getFullName($row['first_name'] ?? '', $row['middle_name'] ?? '', $row['last_name'] ?? '', $row['suffix'] ?? '');
            }
            $label = $fullName ?: "ID {$id}";
            $auth->logActivity((int)$_SESSION['admin_id'], 'Delete Applicant', "Deleted applicant {$label}");
        }

        redirect('pending.php' . $qs);
        exit;
    }
}

/* =========================================================
 * Data Loading
 *  - Replace mode: Pending candidates (BU), with similarity score vs. original
 *  - Normal mode : All pending (BU), filtered by search
 * =========================================================*/

// Utility: JSON decode as array
$decodeList = function (?string $json): array {
    if (!$json) return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
};

// Utility: similarity score (skills + preferred cities + languages)
$computeSimilarityScore = function (array $cand, array $orig) use ($decodeList): int {
    $candSkills = $decodeList($cand['specialization_skills'] ?? '[]');
    $origSkills = $decodeList($orig['specialization_skills'] ?? '[]');
    $candCities = $decodeList($cand['preferred_location'] ?? '[]');
    $origCities = $decodeList($orig['preferred_location'] ?? '[]');
    $candLangs  = $decodeList($cand['languages'] ?? '[]');
    $origLangs  = $decodeList($orig['languages'] ?? '[]');

    $norm = fn(array $a) => array_values(array_unique(array_map('mb_strtolower', array_map('trim', $a))));

    $candSkillsN = $norm($candSkills);
    $origSkillsN = $norm($origSkills);
    $candCitiesN = $norm($candCities);
    $origCitiesN = $norm($origCities);
    $candLangsN  = $norm($candLangs);
    $origLangsN  = $norm($origLangs);

    $score = 0;

    // Skills: +3 per common
    $commonSkills = array_intersect($candSkillsN, $origSkillsN);
    $score += count($commonSkills) * 3;

    // Cities: +2 per common
    $commonCities = array_intersect($candCitiesN, $origCitiesN);
    $score += count($commonCities) * 2;

    // Languages: +1 per common
    $commonLangs = array_intersect($candLangsN, $origLangsN);
    $score += count($commonLangs) * 1;

    return $score;
};

$applicants = [];

if ($replaceRecord && $originalApplicant && $conn instanceof mysqli) {
    // Candidates: pending in this BU, not soft-deleted, not blacklisted, excluding original applicant
    $sql = "
        SELECT
            a.id, a.first_name, a.middle_name, a.last_name, a.suffix,
            a.phone_number, a.email, a.preferred_location,
            a.specialization_skills, a.languages,
            a.picture, a.status, a.created_at
        FROM applicants a
        WHERE a.business_unit_id = ?
          AND a.status = 'pending'
          AND a.deleted_at IS NULL
          AND a.id <> ?
          AND NOT EXISTS (
            SELECT 1 FROM blacklisted_applicants b
            WHERE b.applicant_id = a.id AND b.is_active = 1
          )
        ORDER BY a.created_at DESC, a.id DESC
        LIMIT 500
    ";
    if ($stmt = $conn->prepare($sql)) {
        $origId = (int)$originalApplicant['id'];
        $stmt->bind_param("ii", $currentBuId, $origId);
        $stmt->execute();
        $res = $stmt->get_result();
        $candidates = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
    } else {
        $candidates = [];
    }

    // Compute similarity score vs original
    foreach ($candidates as &$c) {
        $c['_score'] = $computeSimilarityScore($c, $originalApplicant);
    }
    unset($c);

    // Sort by score DESC, then recent
    usort($candidates, function($a, $b) {
        $sa = (int)($a['_score'] ?? 0);
        $sb = (int)($b['_score'] ?? 0);
        if ($sa === $sb) {
            $ca = strtotime((string)($a['created_at'] ?? '')) ?: 0;
            $cb = strtotime((string)($b['created_at'] ?? '')) ?: 0;
            return $cb <=> $ca;
        }
        return $sb <=> $sa;
    });

    $applicants = $candidates;

} else {
    // Normal pending list (Applicant::getAll already excludes blacklisted & soft-deleted)
    $applicants = $applicant->getAll('pending', $currentBuId);
}

/* =========================================================
 * Helpers for UI
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

// Search filter
$filterRowsByQuery = function(array $rows, string $query): array {
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);
    return array_values(array_filter($rows, function(array $app) use ($needle) {
        $first  = (string)($app['first_name']   ?? '');
        $middle = (string)($app['middle_name']  ?? '');
        $last   = (string)($app['last_name']    ?? '');
        $suffix = (string)($app['suffix']       ?? '');
        $email  = (string)($app['email']        ?? '');
        $phone  = (string)($app['phone_number'] ?? '');
        $loc    = (string)($app['preferred_location'] ?? '');

        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $stack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone, $loc
        ]));

        return mb_strpos($stack, $needle) !== false;
    }));
};

if ($q !== '') {
    $applicants = $filterRowsByQuery($applicants, $q);
}

/* ---------- Preserve the search in action links and export URL (SMC includes) ---------- */
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../../../includes/excel_pending.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Dropdown Fix: prevent clipping + ensure stacking above other rows ===== -->
<style>
    .table-card,
    .table-card .card-body,
    .table-card .table-responsive,
    .table-card table,
    .table-card thead,
    .table-card tbody,
    .table-card tr,
    .table-card th,
    .table-card td { overflow: visible !important; }

    .table-card { position: relative; z-index: 0; }
    td.actions-cell { position: relative; overflow: visible; white-space: nowrap; }
    .table-card tr.row-raised { position: relative; z-index: 1060; }

    .dd-modern .dropdown-menu {
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        min-width: 180px;
        z-index: 9999 !important;
    }
    .dd-modern .dropdown-item { display:flex; align-items:center; gap:.5rem; padding:.55rem .9rem; font-weight:500; }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled, .dd-modern .dropdown-item:disabled { color:#9aa0a6; background:transparent; pointer-events:none; }
    .btn-status { border-radius: .75rem; }
    table.table-styled { margin-bottom: 0; }

    /* Replace banner */
    .replace-banner {
        border: 1px dashed #c40000;
        background: #fff6f6;
        border-radius: .75rem;
        padding: .9rem 1rem;
    }
    .badge-soft {
        border: 1px solid #e5e7eb;
        background: #f8fafc;
        color: #111827;
        border-radius: .5rem;
        padding: .25rem .5rem;
        font-weight: 600;
    }
    .score-badge {
        background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0;
        font-weight:700; border-radius:.5rem; padding:.2rem .45rem; font-size:.8rem;
    }
    .btn-assign {
        background: #0d9488; color: #fff; border: 0;
    }
    .btn-assign:hover { background:#0f766e; color:#fff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Pending Applicants</h4>
    <a href="<?php echo h($exportUrl); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<?php if ($replaceRecord && $originalApplicant): ?>
    <?php
        $origName = getFullName($originalApplicant['first_name'] ?? '', $originalApplicant['middle_name'] ?? '', $originalApplicant['last_name'] ?? '', $originalApplicant['suffix'] ?? '');
        $reason   = (string)($replaceRecord['reason'] ?? '');
    ?>
    <div class="replace-banner mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="fw-semibold">
                <i class="bi bi-arrow-repeat me-1"></i>
                Replacing for <span class="text-danger"><?php echo h($origName); ?></span>
            </div>
            <span class="badge-soft">Reason: <?php echo h($reason); ?></span>
            <span class="text-muted">Only <strong>Pending</strong> candidates are listed and ranked by similarity (skills + cities + languages).</span>
        </div>
        <div class="small text-muted mt-1">
            Tip: Click <em>View</em> to inspect details; an <strong>Assign</strong> button is also shown on the View pages during replacement.
        </div>
    </div>
<?php endif; ?>

<!-- 🔎 Search bar -->
<div class="mb-3 d-flex justify-content-end">
    <form action="pending.php" method="get" class="w-100" style="max-width: 420px;">
        <?php if ($replaceRecord): ?>
            <input type="hidden" name="replace_id" value="<?php echo (int)$replaceId; ?>">
        <?php endif; ?>
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="<?php echo $replaceRecord ? 'Search replacement candidates...' : 'Search pending applicants...'; ?>"
                value="<?php echo h($q); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <?php if ($replaceRecord): ?>
                    <a href="pending.php?replace_id=<?php echo (int)$replaceId; ?>" class="btn btn-outline-secondary" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php else: ?>
                    <a href="pending.php?clear=1" class="btn btn-outline-secondary" title="Clear">
                        <i class="bi bi-x-lg"></i>
                    </a>
                <?php endif; ?>
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
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Location</th>
                    <?php if ($replaceRecord): ?>
                        <th>Similarity</th>
                    <?php else: ?>
                        <th>Date Applied</th>
                    <?php endif; ?>
                    <th style="width: 420px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <?php if ($q === ''): ?>
                                <?php echo $replaceRecord ? 'No matching pending candidates found.' : 'No pending applicants.'; ?>
                            <?php else: ?>
                                No results for "<strong><?php echo h($q); ?></strong>".
                                <?php if (!$replaceRecord): ?>
                                    <a href="pending.php?clear=1" class="ms-1">Clear search</a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $app): ?>
                        <?php
                            $id            = (int)($app['id'] ?? 0);
                            $currentStatus = (string)($app['status'] ?? 'pending');

                            // Preserve search + replace mode in links
                            $qs = $preserveQ;
                            if ($replaceRecord) $qs .= ($qs === '' ? '?' : '&') . 'replace_id=' . (int)$replaceId;

                            $viewUrl   = 'view-applicant.php?id=' . $id . $qs;
                            $editUrl   = 'edit-applicant.php?id=' . $id . $qs;
                            $deleteUrl = 'pending.php?action=delete&id=' . $id . ($replaceRecord ? ('&replace_id='.(int)$replaceId) : '') . $preserveQ;

                            $toPendingUrl   = 'pending.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl = 'pending.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl  = 'pending.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;

                            $score = isset($app['_score']) ? (int)$app['_score'] : null;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($app['picture'])): ?>
                                    <img src="<?php echo h(getFileUrl($app['picture'])); ?>"
                                         alt="Photo"
                                         class="rounded"
                                         width="50"
                                         height="50"
                                         style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?></strong>
                            </td>
                            <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                            <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                            <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>

                            <?php if ($replaceRecord): ?>
                                <td><span class="score-badge" title="Higher is more similar"><?php echo (int)$score; ?></span></td>
                            <?php else: ?>
                                <td><?php echo h(formatDate($app['created_at'] ?? '')); ?></td>
                            <?php endif; ?>

                            <td class="actions-cell">
                                <div class="btn-group dropup dd-modern">
                                    <!-- View -->
                                    <a href="<?php echo h($viewUrl); ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="<?php echo h($editUrl); ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <?php if (!$replaceRecord): ?>
                                        <!-- Delete (only normal mode) -->
                                        <a href="<?php echo h($deleteUrl); ?>"
                                           class="btn btn-sm btn-danger"
                                           title="Delete"
                                           onclick="return confirm('Are you sure you want to delete this applicant?');">
                                            <i class="bi bi-trash"></i>
                                        </a>

                                        <!-- Change Status Dropdown -->
                                        <div class="dropdown">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status"
                                                data-bs-toggle="dropdown"
                                                data-bs-auto-close="true"
                                                data-bs-display="static"
                                                data-bs-offset="0,8"
                                                aria-expanded="false"
                                                aria-haspopup="true"
                                                title="Change Status"
                                                id="changeStatusBtn-<?php echo (int)$app['id']; ?>">
                                                <i class="bi bi-arrow-left-right me-1"></i>
                                                Change Status
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end shadow"
                                                aria-labelledby="changeStatusBtn-<?php echo (int)$app['id']; ?>">
                                                <li>
                                                    <a class="dropdown-item <?php echo ($currentStatus === 'pending') ? 'disabled' : ''; ?>"
                                                       href="<?php echo ($currentStatus === 'pending') ? '#' : h($toPendingUrl); ?>">
                                                        <i class="bi bi-hourglass-split text-warning"></i>
                                                        <span>Pending</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?php echo ($currentStatus === 'on_process') ? 'disabled' : ''; ?>"
                                                       href="<?php echo ($currentStatus === 'on_process') ? '#' : h($toOnProcessUrl); ?>">
                                                        <i class="bi bi-arrow-repeat text-info"></i>
                                                        <span>On-Process</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?php echo ($currentStatus === 'approved') ? 'disabled' : ''; ?>"
                                                       href="<?php echo ($currentStatus === 'approved') ? '#' : h($toApprovedUrl); ?>">
                                                        <i class="bi bi-check2-circle text-success"></i>
                                                        <span>Approved</span>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    <?php else: ?>
                                        <!-- ASSIGN (REPLACE MODE) — handled in view page, but quick action here if desired -->
                                        <form method="post" action="replace-assign.php" class="d-inline">
                                            <input type="hidden" name="replace_id" value="<?php echo (int)$replaceId; ?>">
                                            <input type="hidden" name="replacement_applicant_id" value="<?php echo (int)$id; ?>">
                                            <button type="submit" class="btn btn-sm btn-assign" title="Assign as replacement">
                                                <i class="bi bi-check2-circle me-1"></i> Assign
                                            </button>
                                        </form>
                                    <?php endif; ?>
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
document.addEventListener('DOMContentLoaded', function() {
    // Raise the active row while a dropdown is open so it sits above neighbors
    document.querySelectorAll('.actions-cell .dropdown').forEach(function(dd) {
        dd.addEventListener('show.bs.dropdown', function() {
            var tr = dd.closest('tr');
            if (tr) tr.classList.add('row-raised');
        });
        dd.addEventListener('hidden.bs.dropdown', function() {
            var tr = dd.closest('tr');
            if (tr) tr.classList.remove('row-raised');
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>