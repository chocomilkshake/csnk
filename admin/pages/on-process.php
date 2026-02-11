<?php
// FILE: pages/on-process.php
$pageTitle = 'On Process Applicants';
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
 * --- Search Memory Behavior (consistent) ---
 * - If ?clear=1 → clear stored search and redirect to clean list
 * - If ?q=...  → store in session and use
 * - Else if session has last query → use it
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['onproc_q']);
    redirect('on-process.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }
    $_SESSION['onproc_q'] = $q;
} elseif (!empty($_SESSION['onproc_q'])) {
    $q = (string)$_SESSION['onproc_q'];
}

/** Handle delete (soft delete) with search preserved */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    $deleted = false;
    if (method_exists($applicant, 'softDelete')) {
        $deleted = (bool)$applicant->softDelete($id);
    } elseif (method_exists($applicant, 'update')) {
        $deleted = (bool)$applicant->update($id, ['status' => 'deleted']);
    } else {
        // Fallback direct SQL
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

    if ($deleted && isset($auth) && isset($_SESSION['admin_id']) && method_exists($auth, 'logActivity')) {
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
            "Deleted applicant {$label} (On Process list)"
        );
    }

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('on-process.php' . $qs);
    exit;
}

/**
 * Handle status update (Change Status dropdown).
 * Uses GET for simplicity and preserves the search query on redirect.
 */
if (
    isset($_GET['action'], $_GET['id'], $_GET['to']) &&
    $_GET['action'] === 'update_status'
) {
    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));

    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;

        // Prefer Applicant::updateStatus if available, else ::update, else direct PDO
        if (method_exists($applicant, 'updateStatus')) {
            $updated = (bool)$applicant->updateStatus($id, $to);
        } elseif (method_exists($applicant, 'update')) {
            $updated = (bool)$applicant->update($id, ['status' => $to]);
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
            if ($updated) setFlashMessage('success', 'Status updated successfully.');
            else setFlashMessage('error', 'Failed to update status. Please try again.');
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

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('on-process.php' . $qs);
    exit;
}

/** Load on_process applicants + latest booking data */
$applicants = $applicant->getOnProcessWithLatestBooking();

/**
 * Helpers
 */
function renderPreferredLocation(?string $json, int $maxLen = 30): string {
    if (empty($json)) {
        return 'N/A';
    }
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), function($v){
        return is_string($v) && $v !== '';
    }));

    if (empty($cities)) {
        return 'N/A';
    }

    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen) {
        return $cities[0];
    }
    return $full;
}

function filterRowsByQuery(array $rows, string $query): array {
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);

    return array_values(array_filter($rows, function(array $row) use ($needle) {
        // Applicant name fields
        $first  = (string)($row['first_name']   ?? '');
        $middle = (string)($row['middle_name']  ?? '');
        $last   = (string)($row['last_name']    ?? '');
        $suffix = (string)($row['suffix']       ?? '');

        // Applicant contacts
        $email  = (string)($row['email']        ?? '');
        $phone  = (string)($row['phone_number'] ?? '');
        $loc    = renderPreferredLocation($row['preferred_location'] ?? null, 999);

        // Client fields (latest booking)
        $cfn = (string)($row['client_first_name']  ?? '');
        $cmn = (string)($row['client_middle_name'] ?? '');
        $cln = (string)($row['client_last_name']   ?? '');
        $cem = (string)($row['client_email']       ?? '');
        $cph = (string)($row['client_phone']       ?? '');

        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $clientFull = trim($cfn . ' ' . $cmn . ' ' . $cln);

        $haystack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone, $loc,
            $clientFull, $cem, $cph
        ]));

        return mb_strpos($haystack, $needle) !== false;
    }));
}

if ($q !== '') {
    $applicants = filterRowsByQuery($applicants, $q);
}

// Preserve the search in action links and export URL
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_onprocess.php?type=on_process' . ($q !== '' ? ('&q=' . urlencode($q)) : '');
?>
<!-- ===== Fix dropdown clipping & remove table scroll wrapper ===== -->
<style>
    /* Allow dropdowns to overflow: no clipping at any layer */
    .table-card, .table-card .card-body { overflow: visible !important; }
    /* If any .table-responsive exists (from includes), neutralize its clipping */
    .table-card .table-responsive { overflow: visible !important; }

    /* Ensure the actions cell can render dropdown outside its bounds */
    td.actions-cell {
        position: relative;
        overflow: visible;
        z-index: 10;
        white-space: nowrap;
    }

    /* Modern dropdown styling (same as pending.php) */
    .dd-modern .dropdown-menu {
        border-radius: .75rem; /* rounded-xl */
        border: 1px solid #e5e7eb; /* slate-200 */
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        overflow: hidden;
        z-index: 2000; /* above table & card */
    }
    .dd-modern .dropdown-item {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .55rem .9rem;
        font-weight: 500;
    }
    .dd-modern .dropdown-item .bi {
        font-size: 1rem;
        opacity: .9;
    }
    .dd-modern .dropdown-item:hover {
        background-color: #f8fafc; /* slate-50 */
    }
    .dd-modern .dropdown-item.disabled,
    .dd-modern .dropdown-item:disabled {
        color: #9aa0a6;
        background-color: transparent;
        pointer-events: none;
    }
    .btn-status {
        border-radius: .75rem; /* rounded-xl */
    }

    /* Optional: keep table tidy without forcing scroll */
    table.table-styled { margin-bottom: 0; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">On Process Applicants</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- 🔎 Search bar on the right -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="on-process.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input
                type="text"
                name="q"
                class="form-control"
                placeholder="Search on-process (applicant or client)..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>"
                autocomplete="off"
            >
            <button class="btn btn-outline-secondary" type="submit" title="Search">
                <i class="bi bi-search"></i>
            </button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="on-process.php?clear=1" title="Clear">
                    <i class="bi bi-x-lg"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="card table-card">
    <div class="card-body">
        <!-- Removed .table-responsive to avoid scroll/clipping -->
        <table class="table table-bordered table-striped table-hover table-styled align-middle">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Applicant</th>
                    <th>Client</th>
                    <th>Interview</th>
                    <th>Date &amp; Time</th>
                    <th>Applicant Contact</th>
                    <th>Client Contact</th>
                    <th>Date Applied</th>
                    <th style="width: 320px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-5">
                            <i class="bi bi-inbox fs-1 d-block mb-3"></i>
                            <?php if ($q === ''): ?>
                                No applicants currently on process.
                            <?php else: ?>
                                No results for "<strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>".
                                <a href="on-process.php?clear=1" class="ms-1">Clear search</a>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php else: ?>
                    <?php foreach ($applicants as $row): ?>
                        <?php
                            $id = (int)$row['id'];
                            $currentStatus = (string)($row['status'] ?? 'on_process');

                            $viewUrl = 'view_onprocess.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $editUrl = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $deleteUrl = 'on-process.php?action=delete&id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');

                            // Change Status target links (preserve q)
                            $toPendingUrl    = 'on-process.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl  = 'on-process.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl   = 'on-process.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;

                            $clientName = trim(($row['client_first_name'] ?? '') . ' ' . ($row['client_middle_name'] ?? '') . ' ' . ($row['client_last_name'] ?? ''));
                            $clientName = $clientName !== '' ? $clientName : '—';

                            $apptType   = $row['appointment_type'] ?? '—';
                            $apptDate   = (string)($row['appointment_date'] ?? '');
                            $apptTime   = (string)($row['appointment_time'] ?? '');
                            $dateTimeDisplay = trim($apptDate . ' ' . $apptTime);
                            $dateTimeDisplay = $dateTimeDisplay !== '' ? $dateTimeDisplay : '—';

                            $appContact = trim(($row['phone_number'] ?? '') . ((($row['email'] ?? '') !== '') ? ' / ' . $row['email'] : ''));
                            $appContact = $appContact !== '' ? $appContact : '—';

                            $cliContact = trim(($row['client_phone'] ?? '') . ((($row['client_email'] ?? '') !== '') ? ' / ' . $row['client_email'] : ''));
                            $cliContact = $cliContact !== '' ? $cliContact : '—';
                        ?>
                        <tr>
                            <td class="tbl-photo">
                                <?php if (!empty($row['picture'])): ?>
                                    <img src="<?php echo htmlspecialchars(getFileUrl($row['picture']), ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="Photo"
                                         class="rounded"
                                         width="50" height="50"
                                         style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                         style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr((string)$row['first_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars(getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="text-muted-small">
                                    <?php echo htmlspecialchars(renderPreferredLocation($row['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                                <div class="text-muted-small">
                                    <?php echo htmlspecialchars($row['client_address'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>

                            <td><?php echo htmlspecialchars($apptType, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($dateTimeDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($appContact, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($cliContact, ENT_QUOTES, 'UTF-8'); ?></td>
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

                                    <!-- Delete -->
                                    <a href="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-danger"
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this applicant?');">
                                        <i class="bi bi-trash"></i>
                                    </a>

                                    <!-- Change Status Dropdown (opens upward; not clipped) -->
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary dropdown-toggle btn-status"
                                            data-bs-toggle="dropdown"
                                            data-bs-display="static"
                                            data-bs-boundary="viewport"
                                            data-bs-offset="0,8"
                                            aria-expanded="false"
                                            title="Change Status">
                                        <i class="bi bi-arrow-left-right me-1"></i> Change Status
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
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
                            </td>
                        </tr>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>

        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>