<?php
// FILE: pages/pending.php
$pageTitle = 'Pending Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for search persistence)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$applicant = new Applicant($database);

// Allowed statuses to transition to
$allowedStatuses = ['pending', 'on_process', 'approved'];

/**
 * --- Search Memory Behavior (consistent with other pages) ---
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['pending_q']);
    redirect('pending.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }
    $_SESSION['pending_q'] = $q;
} elseif (!empty($_SESSION['pending_q'])) {
    $q = (string)$_SESSION['pending_q'];
}

/** Replacement mode? (?replace_id=...) */
$replaceId = isset($_GET['replace_id']) ? (int)$_GET['replace_id'] : 0;
$replaceRecord = null;
$originalApplicant = null;
if ($replaceId > 0) {
    $replaceRecord = $applicant->getReplacementById($replaceId);
    if ($replaceRecord && ($replaceRecord['status'] ?? '') === 'selection') {
        $originalApplicant = $applicant->getById((int)$replaceRecord['original_applicant_id']);
    } else {
        // If not in selection or not found, ignore replace mode
        $replaceRecord = null;
    }
}

/**
 * Handle actions: update_status or delete
 * Uses GET for simplicity and preserves the search query on redirect.
 * NOTE: In replace mode we do not allow status changes here to avoid conflicts.
 */
if (isset($_GET['action']) && !$replaceRecord) {
    $action = (string)$_GET['action'];
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    // Build redirect URL back to this page preserving search
    $qs = ($q !== '') ? ('?q=' . urlencode($q)) : '';

    if ($action === 'update_status' && $id > 0 && isset($_GET['to'])) {
        $to = strtolower(trim((string)$_GET['to']));
        if (in_array($to, $allowedStatuses, true)) {
            $updated = false;

            // Prefer Applicant::updateStatus if available, else ::update, else direct PDO
            if (method_exists($applicant, 'updateStatus')) {
                $updated = (bool) $applicant->updateStatus($id, $to);
            } elseif (method_exists($applicant, 'update')) {
                $updated = (bool) $applicant->update($id, ['status' => $to]);
            } else {
                try {
                    if (isset($database) && $database instanceof PDO) {
                        $stmt = $database->prepare("UPDATE applicants SET status = :st WHERE id = :id");
                        $updated = $stmt->execute([':st' => $to, ':id' => $id]);
                    }
                } catch (Throwable $e) {
                    $updated = false;
                }
            }

            if (function_exists('setFlashMessage')) {
                setFlashMessage($updated ? 'success' : 'error', $updated ? 'Status updated successfully.' : 'Failed to update status. Please try again.');
            }

            if ($updated && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
                $fullName = null;
                if (method_exists($applicant, 'getById')) {
                    $row = $applicant->getById($id);
                    if (is_array($row)) {
                        $fullName = getFullName(
                            $row['first_name'] ?? '',
                            $row['middle_name'] ?? '',
                            $row['last_name'] ?? '',
                            $row['suffix'] ?? ''
                        );
                    }
                }
                $label = $fullName ?: "ID {$id}";
                $auth->logActivity(
                    (int)$_SESSION['admin_id'],
                    'Update Applicant Status',
                    "Updated status for {$label} → {$to}"
                );
            }
        } else {
            if (function_exists('setFlashMessage')) {
                setFlashMessage('error', 'Invalid status selected.');
            }
        }

        redirect('pending.php' . $qs);
        exit;
    }

    if ($action === 'delete' && $id > 0) {
        $deleted = false;

        // Prefer softDelete if available
        if (method_exists($applicant, 'softDelete')) {
            $deleted = (bool) $applicant->softDelete($id);
        } elseif (method_exists($applicant, 'update')) {
            $deleted = (bool) $applicant->update($id, ['status' => 'deleted']);
        } else {
            try {
                if (isset($database) && $database instanceof PDO) {
                    $stmt = $database->prepare("UPDATE applicants SET status = 'deleted' WHERE id = :id");
                    $deleted = $stmt->execute([':id' => $id]);
                }
            } catch (Throwable $e) {
                $deleted = false;
            }
        }

        if (function_exists('setFlashMessage')) {
            setFlashMessage($deleted ? 'success' : 'error', $deleted ? 'Applicant deleted successfully.' : 'Failed to delete applicant.');
        }

        if ($deleted && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
            $fullName = null;
            if (method_exists($applicant, 'getById')) {
                $row = $applicant->getById($id);
                if (is_array($row)) {
                    $fullName = getFullName(
                        $row['first_name'] ?? '',
                        $row['middle_name'] ?? '',
                        $row['last_name'] ?? '',
                        $row['suffix'] ?? ''
                    );
                }
            }
            $label = $fullName ?: "ID {$id}";
            $auth->logActivity(
                (int)$_SESSION['admin_id'],
                'Delete Applicant',
                "Deleted applicant {$label}"
            );
        }

        redirect('pending.php' . $qs);
        exit;
    }
}

/**
 * Load list for display:
 * - In normal mode: all pending (with optional search filter)
 * - In REPLACE mode: pending candidates sorted by similarity to ORIGINAL (server-side)
 */
if ($replaceRecord && $originalApplicant) {
    $candidates = $applicant->searchPendingCandidatesForReplacement((int)$originalApplicant['id'], 200);
    // If user typed a search, we will filter the server-side candidates by the same text
    $filterQ = function(array $rows, string $query): array {
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
            $stack = mb_strtolower($first.' '.$middle.' '.$last.' '.$suffix.' '.$email.' '.$phone.' '.$loc);
            return mb_strpos($stack, $needle) !== false;
        }));
    };
    if ($q !== '') $candidates = $filterQ($candidates, $q);
    $applicants = $candidates; // re-use variable below
} else {
    // Normal pending list
    $applicants = $applicant->getAll('pending');

    // Filter by search
    $applicants = (function(array $rows, string $query): array {
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
    })($applicants, $q);
}

/**
 * Helper: Render preferred_location JSON as clean text.
 */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) return 'N/A';

    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }

    $cities = array_values(array_filter(array_map('trim', $arr), function($v){
        return is_string($v) && $v !== '';
    }));
    if (empty($cities)) return 'N/A';

    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen) {
        return $cities[0];
    }
    return $full;
}

// Preserve the search in action links and export URL
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_pending.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
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
    .btn-assign:hover {
        background:#0f766e; color:#fff;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Pending Applicants</h4>
    <a href="<?php echo h($exportUrl); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- 🔁 Replace Mode Banner -->
<?php if ($replaceRecord && $originalApplicant): ?>
    <?php
        $origName = getFullName($originalApplicant['first_name'] ?? '', $originalApplicant['middle_name'] ?? '', $originalApplicant['last_name'] ?? '', $originalApplicant['suffix'] ?? '');
        $reason   = (string)$replaceRecord['reason'];
    ?>
    <div class="replace-banner mb-3">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="fw-semibold">
                <i class="bi bi-arrow-repeat me-1"></i>
                Replacing for <span class="text-danger"><?php echo h($origName); ?></span>
            </div>
            <span class="badge-soft">Reason: <?php echo h($reason); ?></span>
            <span class="text-muted">Only <strong>Pending</strong> candidates are listed and ranked by similarity (skills + cities).</span>
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
                <a href="pending.php<?php echo $replaceRecord ? ('?replace_id='.(int)$replaceId) : '?clear=1'; ?>" class="btn btn-outline-secondary" title="Clear">
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
                            $id = (int)($app['id'] ?? 0);
                            $currentStatus = (string)($app['status'] ?? 'pending');

                            // View/Edit/Delete links (preserve q and replace mode)
                            $qs = $preserveQ;
                            if ($replaceRecord) $qs .= ($qs === '' ? '?' : '&') . 'replace_id=' . (int)$replaceId;

                            $viewUrl   = 'view-applicant.php?id=' . $id . $qs;
                            $editUrl   = 'edit-applicant.php?id='   . $id . $qs;
                            $deleteUrl = 'pending.php?action=delete&id=' . $id . ($replaceRecord ? ('&replace_id='.(int)$replaceId) : '') . $preserveQ;

                            // Change Status links (disabled in replace mode)
                            $toPendingUrl    = 'pending.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl  = 'pending.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl   = 'pending.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;

                            // Similarity score (from Applicant::searchPendingCandidatesForReplacement we added _score)
                            $score = isset($app['_score']) ? (int)$app['_score'] : null;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($app['picture'])): ?>
                                    <img
                                        src="<?php echo h(getFileUrl($app['picture'])); ?>"
                                        alt="Photo"
                                        class="rounded"
                                        width="50"
                                        height="50"
                                        style="object-fit: cover;"
                                    >
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong>
                                    <?php echo h(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix'])); ?>
                                </strong>
                            </td>
                            <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
                            <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
                            <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>

                            <?php if ($replaceRecord): ?>
                                <td>
                                    <span class="score-badge" title="Higher is more similar"><?php echo (int)$score; ?></span>
                                </td>
                            <?php else: ?>
                                <td><?php echo h(formatDate($app['created_at'])); ?></td>
                            <?php endif; ?>

                            <td class="actions-cell">
                                <div class="btn-group dropup dd-modern">
                                    <!-- View -->
                                    <a href="<?php echo h($viewUrl); ?>"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="<?php echo h($editUrl); ?>"
                                       class="btn btn-sm btn-warning" title="Edit">
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

                                        <!-- Change Status Dropdown (disabled when replace mode) -->
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
                                        <!-- ASSIGN (REPLACE MODE) -->
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

<?php require_once '../includes/footer.php'; ?>