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

/**
 * Handle actions: update_status or delete
 * Uses GET for simplicity and preserves the search query on redirect.
 */
if (isset($_GET['action'])) {
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
 * Load "pending" applicants.
 * We will filter by search in-PHP here to avoid touching Applicant.php for now.
 */
$applicants = $applicant->getAll('pending');

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

/**
 * Helper: Apply a case-insensitive contains filter across multiple fields.
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
        $loc    = renderPreferredLocation($app['preferred_location'] ?? null, 999);

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
    $applicants = filterApplicantsByQuery($applicants, $q);
}

// Preserve the search in action links and export URL
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_pending.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Dropdown Fix: prevent clipping + ensure stacking above other rows ===== -->
<style>
    /* 1) Never clip dropdowns in the table/card container */
    .table-card,
    .table-card .card-body,
    .table-card .table-responsive,
    .table-card table,
    .table-card thead,
    .table-card tbody,
    .table-card tr,
    .table-card th,
    .table-card td {
        overflow: visible !important;
    }

    /* 2) Base container context */
    .table-card { position: relative; z-index: 0; }

    /* 3) Actions cell keeps menu visible and aligned */
    td.actions-cell { position: relative; overflow: visible; white-space: nowrap; }

    /* 4) Elevate the currently-open row so its dropdown sits on top of neighbors */
    .table-card tr.row-raised { position: relative; z-index: 1060; }

    /* 5) Modern dropdown styling + very high z-index to be safe */
    .dd-modern .dropdown-menu {
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        min-width: 180px;
        z-index: 9999 !important;
    }
    .dd-modern .dropdown-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .55rem .9rem;
        font-weight: 500;
    }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled,
    .dd-modern .dropdown-item:disabled {
        color: #9aa0a6; background-color: transparent; pointer-events: none;
    }
    .btn-status { border-radius: .75rem; }

    table.table-styled { margin-bottom: 0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Pending Applicants</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- 🔎 Search bar on the RIGHT side under the Export Excel button -->
<div class="mb-3 d-flex justify-content-end">
    <form action="pending.php" method="get" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search pending applicants..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a href="pending.php?clear=1" class="btn btn-outline-secondary" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <!-- No .table-responsive wrapper to avoid scroll/clipping -->
        <table class="table table-bordered table-striped table-hover table-styled align-middle">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Location</th>
                    <th>Date Applied</th>
                    <th style="width: 320px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <?php if ($q === ''): ?>
                                No pending applicants.
                            <?php else: ?>
                                No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                                <a href="pending.php?clear=1" class="ms-1">Clear search</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $app): ?>
                        <?php
                            $id = (int)($app['id'] ?? 0);
                            $currentStatus = (string)($app['status'] ?? 'pending');

                            // Change Status links (preserve q)
                            $toPendingUrl    = 'pending.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl  = 'pending.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl   = 'pending.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;

                            // View/Edit/Delete links (preserve q)
                            $viewUrl         = 'view-applicant.php?id=' . $id . $preserveQ;
                            $editUrl         = 'edit-applicant.php?id=' . $id . $preserveQ;
                            $deleteUrl       = 'pending.php?action=delete&id=' . $id . $preserveQ;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($app['picture'])): ?>
                                    <img
                                        src="<?php echo htmlspecialchars(getFileUrl($app['picture']), ENT_QUOTES, 'UTF-8'); ?>"
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
                                    <?php echo htmlspecialchars(getFullName($app['first_name'], $app['middle_name'], $app['last_name'], $app['suffix']), ENT_QUOTES, 'UTF-8'); ?>
                                </strong>
                            </td>
                            <td><?php echo htmlspecialchars($app['phone_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($app['email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(renderPreferredLocation($app['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($app['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="actions-cell">
                                <!-- Single button group with a dropup menu -->
                                <div class="btn-group dropup dd-modern">
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

                                    <!-- Delete -->
                                    <a href="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>"
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
                                                   href="<?php echo ($currentStatus === 'pending') ? '#' : htmlspecialchars($toPendingUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-hourglass-split text-warning"></i>
                                                    <span>Pending</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo ($currentStatus === 'on_process') ? 'disabled' : ''; ?>"
                                                   href="<?php echo ($currentStatus === 'on_process') ? '#' : htmlspecialchars($toOnProcessUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <i class="bi bi-arrow-repeat text-info"></i>
                                                    <span>On-Process</span>
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo ($currentStatus === 'approved') ? 'disabled' : ''; ?>"
                                                   href="<?php echo ($currentStatus === 'approved') ? '#' : htmlspecialchars($toApprovedUrl, ENT_QUOTES, 'UTF-8'); ?>">
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
document.addEventListener('DOMContentLoaded', function() {
    // Raise the active row while a dropdown is open so it sits above neighbors
    // Bootstrap 5 auto-initializes dropdowns via data-bs-toggle="dropdown"
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