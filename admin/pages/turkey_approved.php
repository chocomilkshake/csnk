<?php
// FILE: admin/pages/turkey_approved.php (SMC - Turkey Approved/Hired Applicants)
// Purpose: Always show only SMC applicants, with same UI as approved.php

$pageTitle = 'SMC Manpower Agency Co.';

$ADMIN_ROOT = dirname(__DIR__);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once $ADMIN_ROOT . '/includes/config.php';
require_once $ADMIN_ROOT . '/includes/Database.php';
require_once $ADMIN_ROOT . '/includes/Auth.php';
require_once $ADMIN_ROOT . '/includes/functions.php';


$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

// SMC access only for this page
if (!$auth->canSeeSMC()) {
    header('Location: applicants.php');
    exit;
}

$conn = $database->getConnection();

// Grab ALL active SMC BU IDs to enforce SMC-only on the list
$smcBuIds = [];
if ($conn instanceof mysqli) {
    $sqlSmcBus = "
        SELECT bu.id
        FROM business_units bu
        JOIN agencies ag ON ag.id = bu.agency_id
        WHERE ag.code = 'smc' AND bu.active = 1
        ORDER BY bu.id ASC
    ";
    if ($res = $conn->query($sqlSmcBus)) {
        while ($r = $res->fetch_assoc()) {
            $smcBuIds[] = (int) $r['id'];
        }
    }
}

if (!empty($smcBuIds)) {
    $_SESSION['smc_bu_id'] = $smcBuIds[0];
}

if (empty($_SESSION['current_bu_id'])) {
    header('Location: login.php');
    exit;
}

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Throwable $e) {
        $_SESSION['csrf_token'] = bin2hex((string) mt_rand());
    }
}

require_once $ADMIN_ROOT . '/includes/header.php';
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';
require_once $ADMIN_ROOT . '/includes/smc_filter_bar.php';

$applicant = new Applicant($database);

// Role helpers
$currentBuId = (int) ($_SESSION['current_bu_id'] ?? 0);
$isSuperAdmin = (isset($currentRole) && $currentRole === 'super_admin');
$isAdmin = (isset($isAdmin) ? (bool) $isAdmin : (isset($currentRole) && $currentRole === 'admin'));
$isEmployee = (isset($currentRole) && $currentRole === 'employee');

$country = $_GET['country'] ?? 'all';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = 'approved';

$countryId = ($country !== 'all') ? (int) $country : null;
$notDeleted = true;
$notBlacklisted = true;

// Pagination
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$pageSize = 25;

$buScope = null;

// Fetch applicants
$applicants = $applicant->getApplicants(
    $buScope,
    $countryId,
    $status,
    $q,
    $notDeleted,
    $notBlacklisted,
    $page,
    $pageSize
);

$totalApplicants = $applicant->getApplicantsCount(
    $buScope,
    $countryId,
    $status,
    $q,
    $notDeleted,
    $notBlacklisted
);

// Initialize SMC filters (replaces hardcoded params)
$filterState = smc_filter_boot([
    'base_url' => 'turkey_approved.php',
    'session_ns' => 'smc_tr_approved',
    'applicant' => $applicant,
    'buId' => $smcBuId,
    'allowed_statuses' => ['pending', 'on_process', 'approved'],
    'default_status' => 'approved',
    'not_deleted' => true,
    'not_blacklisted' => true,
]);

$filters = $filterState['filters'];
$q = $filterState['q'];
$status = 'approved';
$filters['status'] = 'approved';
$applicants = $applicant->getApplicants(
    $filters['buId'],
    $filters['countryId'],
    $filters['status'],
    $filters['q'],
    $filters['notDeleted'],
    $filters['notBlacklisted'],
    1,
    1000
);

// Update preserve for links
$preserveQ = $filterState['preserveQS'];

// Allowed statuses for change
$allowedStatuses = ['pending', 'on_process', 'approved'];

/**
 * Handle status update (Change Status dropdown) - SMC version
 */
if (
    isset($_GET['action'], $_GET['id'], $_GET['to']) &&
    $_GET['action'] === 'update_status'
) {
    $id = (int) $_GET['id'];
    $to = strtolower(trim((string) $_GET['to']));

    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;
        $fromStatus = '';
        $businessUnitId = null;
        if ($conn instanceof mysqli) {
            // grab previous status & BU id
            if ($stmtChk = $conn->prepare("SELECT status, business_unit_id FROM applicants WHERE id = ? LIMIT 1")) {
                $stmtChk->bind_param('i', $id);
                $stmtChk->execute();
                $resChk = $stmtChk->get_result();
                if ($resChk && ($rowChk = $resChk->fetch_assoc())) {
                    $fromStatus = $rowChk['status'];
                    $businessUnitId = $rowChk['business_unit_id'];
                }
                $stmtChk->close();
            }
            if ($stmt = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?")) {
                $stmt->bind_param("si", $to, $id);
                $updated = $stmt->execute();
                $stmt->close();
            }
        }

        if (function_exists('setFlashMessage')) {
            if ($updated)
                setFlashMessage('success', 'Status updated successfully.');
            else
                setFlashMessage('error', 'Failed to update status. Please try again.');
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
                (int) $_SESSION['admin_id'],
                'Update Applicant Status',
                "Updated status for {$label} → {$to} (SMC)"
            );
        }
        // log to status reports table
        if ($updated && $fromStatus !== '' && $fromStatus !== $to) {
            $adminId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
            $reportText = "Status changed from " . ucfirst(str_replace('_', ' ', $fromStatus))
                . " to " . ucfirst(str_replace('_', ' ', $to));
            $buIdForReport = $businessUnitId !== null ? $businessUnitId : 1;
            if ($conn instanceof mysqli) {
                if ($stmtRep = $conn->prepare("INSERT INTO applicant_status_reports (applicant_id, business_unit_id, from_status, to_status, report_text, admin_id) VALUES (?, ?, ?, ?, ?, ?)")) {
                    $stmtRep->bind_param('iisssi', $id, $buIdForReport, $fromStatus, $to, $reportText, $adminId);
                    $stmtRep->execute();
                    $stmtRep->close();
                }
            }
        }
    } else {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('error', 'Invalid status selected.');
        }
    }

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('turkey_approved.php' . $qs);
    exit;
}

function renderPreferredLocation(?string $json, int $maxLen = 30): string
{
    if (empty($json))
        return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), fn($v) => is_string($v) && $v !== ''));
    if (empty($cities))
        return 'N/A';
    $full = implode(', ', $cities);
    if (mb_strlen($full) > $maxLen)
        return $cities[0];
    return $full;
}

// Preserve query in links
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Dropdown Fix Styles ===== -->
<style>
    .table-card,
    .table-card .card-body {
        overflow: visible !important;
    }

    .table-card .table-responsive {
        overflow: visible !important;
    }

    .table-card table,
    .table-card tbody,
    .table-card tr {
        overflow: visible !important;
    }

    td.actions-cell {
        position: relative;
        overflow: visible;
        z-index: 10;
        white-space: nowrap;
    }

    .dd-modern .dropdown-menu {
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        box-shadow: 0 12px 28px rgba(15, 23, 42, .12);
        z-index: 9999 !important;
        min-width: 160px;
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
        background-color: #f8fafc;
    }

    .dd-modern .dropdown-item.disabled,
    .dd-modern .dropdown-item:disabled {
        color: #9aa0a6;
        background-color: transparent;
        pointer-events: none;
    }

    .btn-status {
        border-radius: .75rem;
    }

    table.table-styled {
        margin-bottom: 0;
    }

    .status-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .85);
    }

    .status-btn {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .9rem;
        border-radius: .75rem;
        font-size: .875rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #cbd5e1;
        color: #334155;
        background: #fff;
    }

    .status-btn--active {
        color: #fff;
        border-color: #4f46e5;
        background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%);
    }

    .country-group {
        display: inline-flex;
        gap: .5rem;
        padding: .5rem;
        border: 1px solid #e5e7eb;
        border-radius: 1rem;
        background: rgba(255, 255, 255, .85);
    }

    .country-btn {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .35rem .75rem;
        border-radius: .75rem;
        font-size: .8rem;
        font-weight: 500;
        text-decoration: none;
        border: 1px solid #cbd5e1;
        color: #334155;
        background: #fff;
    }

    .country-btn--active {
        color: #fff;
        border-color: #059669;
        background: linear-gradient(180deg, #10b981 0%, #059669 100%);
    }

    .filter-label {
        font-size: .75rem;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: .25rem;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Approved SMC Applicants</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<?php smc_filter_render($filterState); ?>



<!-- Search bar -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="turkey_approved.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Search approved applicants..."
                value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
            <button class="btn btn-outline-secondary" type="submit" title="Search"><i class="bi bi-search"></i></button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="turkey_approved.php?clear=1" title="Clear"><i
                        class="bi bi-x-lg"></i></a>
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
                    <th style="width: 420px;">Actio
                        <div class="col-md-6">
                            <label class="form-label">Reason <span class="text-danger">*</span></label>
                            <select name="reason" class="form-select" required>
                                <option value="C
                    </div>

                    <hr class="my-3">
                    <h6 class="fw-semibold">Suggested Replacement Candidates</h6>
                    <div id="replacementCandidates" class="mt-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-arrow-repeat me-1"></i> Start & Suggest
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts for Replace Modal binding (SMC Turkey Version) -->
<script src="../js/turkey_replacements.js"></script>
<script>
    // expose CSRF to JS
    window.CSRF_TOKEN = "<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";

    // Fill hidden id + label when opening modal
    document.getElementById('replaceModal')?.addEventListener('show.bs.modal', function (ev) {
        const btn = ev.relatedTarget;
        if (!btn) return;
        const id = btn.getAttribute('data-applicant-id') || '';
        const name = btn.getAttribute('data-applicant-name') || '';
        document.getElementById('replaceOriginalId').value = id;
        document.getElementById('replaceApplicantName').textContent = name;
        const wrap = document.getElementById('replacementCandidates');
        if (wrap) wrap.innerHTML = '';
    });

    // Bind the form to the helper (SMC version)
    document.addEventListener('DOMContentLoaded', function () {
        if (window.TurkeyReplacements) {
            TurkeyReplacements.bindInit('#replaceInitForm', '#replacementCandidates');
        }
    });

    // Dropdown popper fix
    document.addEventListener('DOMContentLoaded', function () {
        var btns = document.querySelectorAll('.btn-status[data-bs-toggle="dropdown"]');
        btns.forEach(function (btn) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
                new bootstrap.Dropdown(btn, { boundary: 'viewport', popperConfig: { strategy: 'fixed' } });
            }
        });
    });
</script>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>