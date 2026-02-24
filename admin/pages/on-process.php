<?php
// FILE: pages/on-process.php
$pageTitle = 'On Process Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for search persistence + CSRF)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// --- Minimal CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = bin2hex((string)mt_rand());
    }
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

/* =========================================================
 * Handle status update WITH REPORT (modal POST) — MySQLi version
 * =========================================================*/
if (
    isset($_POST['action']) && $_POST['action'] === 'update_status_report' &&
    isset($_POST['id'], $_POST['to'], $_POST['report_text'])
) {
    $id = (int)$_POST['id'];
    $to = strtolower(trim((string)$_POST['to']));
    $reportText = trim((string)$_POST['report_text']);

    // Preserve q on redirect (from session pattern)
    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';

    // CSRF check
    if (!isset($_POST['csrf_token'], $_SESSION['csrf_token']) ||
        !hash_equals((string)$_SESSION['csrf_token'], (string)$_POST['csrf_token'])) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid or missing security token. Please refresh and try again.');
        }
        redirect('on-process.php' . $qs);
        exit;
    }

    // Validate
    $allowedStatuses = ['pending', 'on_process', 'approved'];
    if (!in_array($to, $allowedStatuses, true)) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Invalid status selected.');
        redirect('on-process.php' . $qs);
        exit;
    }
    if ($reportText === '' || mb_strlen($reportText) < 5) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Please provide a brief reason (at least 5 characters).');
        redirect('on-process.php' . $qs);
        exit;
    }

    // Get MySQLi connection from your Database wrapper
    if (!isset($database) || !method_exists($database, 'getConnection')) {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Database connection unavailable.');
        redirect('on-process.php' . $qs);
        exit;
    }
    $conn = $database->getConnection(); // <-- MySQLi

    // 1) Fetch applicant ONCE (MySQLi)
    $fromStatus = null;
    $fullName = null;

    try {
        $stmt = $conn->prepare("SELECT status, first_name, middle_name, last_name, suffix FROM applicants WHERE id = ? LIMIT 1");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $fromStatus = (string)($row['status'] ?? '');
            // getFullName() is your helper
            $fullName = getFullName(
                $row['first_name'] ?? '',
                $row['middle_name'] ?? '',
                $row['last_name'] ?? '',
                $row['suffix'] ?? ''
            );
        }
        $stmt->close();
    } catch (Throwable $e) {
        error_log('Fetch applicant (MySQLi) failed: ' . $e->getMessage());
    }

    if ($fromStatus === null || $fromStatus === '') {
        if (function_exists('setFlashMessage')) setFlashMessage('error', 'Applicant not found.');
        redirect('on-process.php' . $qs);
        exit;
    }

    // 2) Transaction: insert report + update status (MySQLi)
    $adminId = isset($_SESSION['admin_id']) ? (int)$_SESSION['admin_id'] : null;
    $ok = false;

    try {
        $conn->begin_transaction();

        // Insert into applicant_status_reports
        $stmt1 = $conn->prepare("
            INSERT INTO applicant_status_reports (applicant_id, from_status, to_status, report_text, admin_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        // types: i s s s i
        $stmt1->bind_param("isssi", $id, $fromStatus, $to, $reportText, $adminId);
        $stmt1->execute();
        $stmt1->close();

        // Update applicants.status
        $stmt2 = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?");
        $stmt2->bind_param("si", $to, $id);
        $stmt2->execute();
        $stmt2->close();

        $conn->commit();
        $ok = true;
    } catch (Throwable $e) {
        if ($conn->errno) { /* ensure rollback safe */ }
        $conn->rollback();
        error_log('Status change (MySQLi) failed: ' . $e->getMessage());
        $ok = false;
    }

    if (function_exists('setFlashMessage')) {
        if ($ok) setFlashMessage('success', 'Status updated and report saved.');
        else setFlashMessage('error', 'Failed to update status or save report. Please try again.');
    }

    if ($ok && isset($auth) && method_exists($auth, 'logActivity') && isset($_SESSION['admin_id'])) {
        $label = $fullName ?: "ID {$id}";
        $auth->logActivity(
            (int)$_SESSION['admin_id'],
            'Update Applicant Status (with report)',
            "Updated status for {$label} → {$to}; Reason: " . mb_substr($reportText, 0, 200)
        );
    }

    redirect('on-process.php' . $qs);
    exit;
}


/* =========================================================
 * Existing: GET handler (fallback) – change status without report
 * =========================================================*/
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

/** Helpers */
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
$preserveQAmp = ($q !== '') ? ('&amp;q=' . urlencode($q)) : '';
$preserveQQ   = ($q !== '') ? ('?q=' . urlencode($q)) : '';
$exportUrl    = '../includes/excel_onprocess.php?type=on_process' . ($q !== '' ? ('&amp;q=' . urlencode($q)) : '');
?>
<!-- ===== Fix dropdown clipping & remove table scroll wrapper ===== -->
<style>
    .table-card, .table-card .card-body { overflow: visible !important; }
    .table-card .table-responsive { overflow: visible !important; }
    td.actions-cell { position: relative; overflow: visible; z-index: 10; white-space: nowrap; }
    .table-card table, .table-card tbody, .table-card tr { overflow: visible !important; }

    .dd-modern .dropdown-menu {
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        z-index: 9999 !important;
        min-width: 160px;
    }
    .dd-modern .dropdown-item { display: flex; align-items: center; gap: .5rem; padding: .55rem .9rem; font-weight: 500; }
    .dd-modern .dropdown-item .bi { font-size: 1rem; opacity: .9; }
    .dd-modern .dropdown-item:hover { background-color: #f8fafc; }
    .dd-modern .dropdown-item.disabled, .dd-modern .dropdown-item:disabled {
        color: #9aa0a6; background-color: transparent; pointer-events: none;
    }
    .btn-status { border-radius: .75rem; }
    table.table-styled { margin-bottom: 0; }

    /* Modal polish — match On-Hold modal look */
    .status-modal .modal-header { border-bottom: none; padding-bottom: 0; }
    .status-modal .modal-footer { border-top: none; }
    .status-modal .app-card { border: 1px solid #eef2f7; background: #f8fafc; }
    .status-modal .badge-soft {
        display:inline-flex; align-items:center; gap:.35rem;
        padding:.15rem .5rem; border-radius:.5rem; font-size:.8rem;
        border: 1px solid #e2e8f0; background:#f8fafc; color:#334155;
    }
    .status-modal .counter { font-size:.8rem; color:#64748b; }
    .status-modal .form-text { color:#64748b; }
</style>

<?php
$exportReportsUrl = '../includes/excel_status_reports.php' . $preserveQQ;
$printReportsUrl  = 'reports-print.php' . $preserveQQ;
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">On Process Applicants</h4>
    <div class="d-flex gap-2">
        <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
        </a>
        <a href="<?php echo htmlspecialchars($exportReportsUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-success">
            <i class="bi bi-journal-text me-2"></i>Export Reports
        </a>
        <a href="<?php echo htmlspecialchars($printReportsUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-primary">
            <i class="bi bi-printer me-2"></i>Reports
        </a>
    </div>
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
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-styled align-middle mb-0">
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

                                $viewUrl   = 'view_onprocess.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $editUrl   = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $deleteUrl = 'on-process.php?action=delete&id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');

                                // Change Status target links (preserve q in GET)
                                $toPendingUrl    = 'on-process.php?action=update_status&id=' . $id . '&to=pending'    . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $toOnProcessUrl  = 'on-process.php?action=update_status&id=' . $id . '&to=on_process' . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $toApprovedUrl   = 'on-process.php?action=update_status&id=' . $id . '&to=approved'   . ($q !== '' ? '&q=' . urlencode($q) : '');

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

                                $applicantName = htmlspecialchars(getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']), ENT_QUOTES, 'UTF-8');

                                $photo = !empty($row['picture']) ? getFileUrl($row['picture']) : '';
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
                                        <?php echo $applicantName; ?>
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
                                                    <a class="dropdown-item <?php echo $currentStatus === 'pending' ? 'disabled' : ''; ?> change-status"
                                                       href="#"
                                                       data-href="<?php echo htmlspecialchars($toPendingUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                       data-id="<?php echo $id; ?>"
                                                       data-from="<?php echo htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                                       data-to="pending"
                                                       data-applicant="<?php echo $applicantName; ?>"
                                                       data-app-photo="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="bi bi-hourglass-split text-warning"></i>
                                                        <span>Pending</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?php echo $currentStatus === 'on_process' ? 'disabled' : ''; ?> change-status"
                                                       href="#"
                                                       data-href="<?php echo htmlspecialchars($toOnProcessUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                       data-id="<?php echo $id; ?>"
                                                       data-from="<?php echo htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                                       data-to="on_process"
                                                       data-applicant="<?php echo $applicantName; ?>"
                                                       data-app-photo="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="bi bi-arrow-repeat text-info"></i>
                                                        <span>On-Process</span>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item <?php echo $currentStatus === 'approved' ? 'disabled' : ''; ?> change-status"
                                                       href="#"
                                                       data-href="<?php echo htmlspecialchars($toApprovedUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                                       data-id="<?php echo $id; ?>"
                                                       data-from="<?php echo htmlspecialchars($currentStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                                       data-to="approved"
                                                       data-applicant="<?php echo $applicantName; ?>"
                                                       data-app-photo="<?php echo htmlspecialchars($photo, ENT_QUOTES, 'UTF-8'); ?>">
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
</div>

<!-- === Status Change Report Modal (polished like On-Hold) === -->
<div class="modal fade status-modal" id="statusReportModal" tabindex="-1" aria-labelledby="statusReportModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <form method="post" action="on-process.php" id="statusReportForm" class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-light border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-primary bg-opacity-25 p-2 rounded-circle">
            <i class="bi bi-arrow-left-right text-primary fs-5"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="statusReportModalLabel">Change Status &amp; Add Report</h5>
            <p class="text-muted small mb-0">This change will be recorded in the reports log.</p>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body py-3">
        <!-- Applicant header card (photo + name + badges) -->
        <div class="card app-card mb-4">
          <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="photo-slot">
                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm d-none"
                     id="srAvatarFallback" style="width:56px;height:56px;">
                  <span class="fs-5 fw-bold" id="srAvatarLetter">A</span>
                </div>
                <img src="" class="rounded-circle shadow-sm d-none" width="56" height="56"
                     style="object-fit: cover;" alt="Photo" id="srAvatarImg">
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold fs-5" id="sr-applicant">Applicant Name</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="badge-soft"><i class="bi bi-hourglass-split"></i><span id="sr-from">on process</span></span>
                  <i class="bi bi-arrow-right text-muted"></i>
                  <span class="badge-soft"><i class="bi bi-check2"></i><span id="sr-to">approved</span></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="action" value="update_status_report">
        <input type="hidden" name="id" id="sr-id" value="">
        <input type="hidden" name="to" id="sr-to-val" value="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">

        <div class="row g-4">
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">Reason</label>
            <select class="form-select form-select-lg" id="sr-reason">
              <option value="" selected>Select a reason (optional)</option>
              <option value="Interview rescheduled">Interview rescheduled</option>
              <option value="Client confirmed / Ready">Client confirmed / Ready</option>
              <option value="Requirements complete">Requirements complete</option>
              <option value="Passed interview / assessment">Passed interview / assessment</option>
              <option value="Other">Other</option>
            </select>
            <div class="form-text mt-1">Pick a quick label; you can add details on the right.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold d-flex justify-content-between">
              <span>Description <span class="text-danger">*</span></span>
              <span class="counter badge bg-light text-secondary" id="sr-counter">0/1000</span>
            </label>
            <textarea
              class="form-control"
              id="sr-text"
              name="report_text"
              rows="4"
              maxlength="1000"
              required
              placeholder="Write details for this status change (e.g., client confirmation, documents verified, interview result)..."></textarea>
            <div class="form-text mt-1">Minimum 5 characters. This will be stored in the reports log.</div>
          </div>
        </div>

        <div class="alert alert-info bg-info bg-opacity-10 border-0 mt-3 mb-0">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill text-info mt-1"></i>
            <div class="small">
              <strong>Note:</strong> The applicant's status will be updated and this action recorded in Reports.
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
          <i class="bi bi-x-lg me-1"></i> Cancel
        </button>
        <button type="submit" class="btn btn-primary btn-lg">
          <i class="bi bi-check2-square me-2"></i> Save &amp; Update Status
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // Initialize Change Status dropdowns with stable positioning
  var btns = document.querySelectorAll('.btn-status[data-bs-toggle="dropdown"]');
  btns.forEach(function(btn) {
      if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
          new bootstrap.Dropdown(btn, { boundary: 'viewport', popperConfig: { strategy: 'fixed' } });
      }
  });

  var modalEl = document.getElementById('statusReportModal');
  var modal = (typeof bootstrap !== 'undefined' && bootstrap.Modal) ? new bootstrap.Modal(modalEl) : null;

  // Modal elements
  var applicantNameEl = document.getElementById('sr-applicant');
  var fromBadgeEl = document.getElementById('sr-from');
  var toBadgeEl = document.getElementById('sr-to');
  var idInput = document.getElementById('sr-id');
  var toInput = document.getElementById('sr-to-val');
  var reasonSelect = document.getElementById('sr-reason');
  var descTA = document.getElementById('sr-text');
  var counterEl = document.getElementById('sr-counter');

  var avatarImg = document.getElementById('srAvatarImg');
  var avatarFallback = document.getElementById('srAvatarFallback');
  var avatarLetter = document.getElementById('srAvatarLetter');

  // Counter
  var updateCounter = function() {
    var max = parseInt(descTA.getAttribute('maxlength') || '1000', 10);
    counterEl.textContent = (descTA.value.length) + '/' + max;
  };
  descTA.addEventListener('input', updateCounter);
  updateCounter();

  // Open modal (require report only when FROM on_process and TO != from)
  document.querySelectorAll('.change-status').forEach(function (el) {
    el.addEventListener('click', function (ev) {
      ev.preventDefault();
      if (el.classList.contains('disabled')) return;

      var fromSt = (el.dataset.from || '').toLowerCase();
      var toSt   = (el.dataset.to || '').toLowerCase();
      var href   = el.dataset.href || '#';
      var id     = el.dataset.id || '';
      var name   = el.dataset.applicant || '';
      var photo  = el.dataset.appPhoto || '';

      // Require a report when changing FROM on_process
      if (fromSt === 'on_process' && toSt !== fromSt) {
        idInput.value = id;
        toInput.value = toSt;

        applicantNameEl.textContent = name;
        fromBadgeEl.textContent = fromSt.replace('_', ' ');
        toBadgeEl.textContent = toSt.replace('_', ' ');

        // Photo or fallback
        if (photo && photo.trim() !== '') {
          avatarImg.src = photo;
          avatarImg.classList.remove('d-none');
          avatarFallback.classList.add('d-none');
        } else {
          var firstLetter = (name.trim().charAt(0) || 'A').toUpperCase();
          avatarLetter.textContent = firstLetter;
          avatarImg.classList.add('d-none');
          avatarFallback.classList.remove('d-none');
        }

        // Reset reason + description
        reasonSelect.value = '';
        descTA.value = '';
        updateCounter();

        if (modal) modal.show();
        return;
      }

      // Otherwise (no report required), follow the GET link
      window.location.href = href;
    });
  });

  // When submitting, if a Reason is chosen and not already in the text, prefix it
  document.getElementById('statusReportForm').addEventListener('submit', function() {
    var reason = reasonSelect.value.trim();
    var text = descTA.value.trim();
    if (reason && reason.toLowerCase() !== 'other') {
      // Prepend reason label if not yet present
      if (text.toLowerCase().indexOf(reason.toLowerCase()) !== 0) {
        descTA.value = reason + (text ? ': ' + text : '');
      }
    }
  });

  // Autofocus reason on open
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', function(){
      reasonSelect?.focus();
    });
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>