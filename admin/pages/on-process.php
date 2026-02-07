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
    if ($applicant->softDelete($id)) {
        $auth->logActivity($_SESSION['admin_id'], 'Delete Applicant', "Deleted applicant ID: $id (from On Process)");
        setFlashMessage('success', 'Applicant deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete applicant.');
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

$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">On Process Applicants</h4>
    <?php
        // Updated to use the unified exporter file with type=on_process, and preserve ?q
        $exportUrl = '../includes/excel_onprocess.php?type=on_process' . ($q !== '' ? '&q=' . urlencode($q) : '');
    ?>
    <a href="<?php echo $exportUrl; ?>" class="btn btn-success">
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
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover table-styled">
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
                        <th>Actions</th>
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
                                    No results for "<strong><?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?></strong>".
                                    <a href="on-process.php?clear=1" class="ms-1">Clear search</a>
                                <?php endif; ?>
                            </td>
                        </tr>

                    <?php else: ?>
                        <?php foreach ($applicants as $row): ?>
                            <?php
                                $viewUrl = 'view_onprocess.php?id=' . (int)$row['id'] . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $editUrl = 'edit-applicant.php?id=' . (int)$row['id'] . ($q !== '' ? '&q=' . urlencode($q) : '');
                                $delUrl  = 'on-process.php?action=delete&id=' . (int)$row['id'] . ($q !== '' ? '&q=' . urlencode($q) : '');

                                $clientName = trim(($row['client_first_name'] ?? '') . ' ' . ($row['client_middle_name'] ?? '') . ' ' . ($row['client_last_name'] ?? ''));
                                $clientName = $clientName !== '' ? $clientName : '—';
                                $apptType   = $row['appointment_type'] ?? '—';
                                $apptDate   = $row['appointment_date'] ?? '';
                                $apptTime   = $row['appointment_time'] ?? '';
                                $dateTimeDisplay = trim($apptDate . ' ' . $apptTime);
                                $dateTimeDisplay = $dateTimeDisplay !== '' ? $dateTimeDisplay : '—';

                                $appContact = trim(($row['phone_number'] ?? '') . (($row['email'] ?? '') !== '' ? ' / ' . $row['email'] : ''));
                                $appContact = $appContact !== '' ? $appContact : '—';
                                $cliContact = trim(($row['client_phone'] ?? '') . (($row['client_email'] ?? '') !== '' ? ' / ' . $row['client_email'] : ''));
                                $cliContact = $cliContact !== '' ? $cliContact : '—';
                            ?>

                            <tr>
                                <td class="tbl-photo">
                                    <?php if (!empty($row['picture'])): ?>
                                        <img src="<?= htmlspecialchars(getFileUrl($row['picture']), ENT_QUOTES, 'UTF-8') ?>"
                                             alt="Photo"
                                             class="rounded"
                                             width="50" height="50"
                                             style="object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                                             style="width: 50px; height: 50px;">
                                            <?= strtoupper(substr($row['first_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        <?= getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']); ?>
                                    </div>
                                    <div class="text-muted-small">
                                        <?= htmlspecialchars(renderPreferredLocation($row['preferred_location']), ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        <?= htmlspecialchars($clientName, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="text-muted-small">
                                        <?= htmlspecialchars($row['client_address'] ?? '—', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </td>

                                <td><?= htmlspecialchars($apptType, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($dateTimeDisplay, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($appContact, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars($cliContact, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= formatDate($row['created_at']); ?></td>

                                <td>
                                    <div class="btn-group">
                                        <a href="<?= $viewUrl ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="<?= $editUrl ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <!-- <a href="<?= $delUrl ?>" class="btn btn-sm btn-danger" title="Delete"
                                           onclick="return confirm('Delete this applicant? This is a soft delete.');">
                                            <i class="bi bi-trash"></i>
                                        </a> -->
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

<?php require_once '../includes/footer.php'; ?>