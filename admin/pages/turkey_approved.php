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
            $smcBuIds[] = (int)$r['id'];
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
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

require_once $ADMIN_ROOT . '/includes/header.php';
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';

$applicant = new Applicant($database);

// Role helpers
$currentBuId   = (int) ($_SESSION['current_bu_id'] ?? 0);
$isSuperAdmin  = (isset($currentRole) && $currentRole === 'super_admin');
$isAdmin       = (isset($isAdmin) ? (bool)$isAdmin : (isset($currentRole) && $currentRole === 'admin'));
$isEmployee    = (isset($currentRole) && $currentRole === 'employee');

$country = $_GET['country'] ?? 'all';
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$status  = 'approved';

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

// Hard-filter to SMC-only
if (!empty($smcBuIds)) {
    $applicants = array_values(array_filter((array)$applicants, function($row) use ($smcBuIds) {
        $buId = (int)($row['business_unit_id'] ?? 0);
        return in_array($buId, $smcBuIds, true);
    }));
    $totalApplicants = count($applicants);
}

// Country counts
$countriesWithCounts = [];
try {
    $byCountry = [];
    foreach ($applicants as $row) {
        $cid = (int)($row['country_id'] ?? 0);
        $cname = (string)($row['country_name'] ?? ($row['country'] ?? ''));
        if (!isset($byCountry[$cid])) {
            $byCountry[$cid] = ['id' => $cid, 'name' => $cname, 'count' => 0];
        }
        $byCountry[$cid]['count']++;
    }
    $countriesWithCounts = array_values(array_filter($byCountry, fn($c) => $c['id'] > 0));
} catch (Throwable $e) {}

// Allowed statuses for change
$allowedStatuses = ['pending', 'on_process', 'approved'];

/**
 * Handle status update (Change Status dropdown) - SMC version
 */
if (
    isset($_GET['action'], $_GET['id'], $_GET['to']) &&
    $_GET['action'] === 'update_status'
) {
    $id = (int)$_GET['id'];
    $to = strtolower(trim((string)$_GET['to']));

    if (in_array($to, $allowedStatuses, true)) {
        $updated = false;
        
        if ($conn instanceof mysqli) {
            if ($stmt = $conn->prepare("UPDATE applicants SET status = ? WHERE id = ?")) {
                $stmt->bind_param("si", $to, $id);
                $updated = $stmt->execute();
                $stmt->close();
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
                "Updated status for {$label} → {$to} (SMC)"
            );
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

// Preserve query in links
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Dropdown Fix Styles ===== -->
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
    
    .status-group { display: inline-flex; gap: .5rem; padding: .5rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: rgba(255, 255, 255, .85); }
    .status-btn { display: inline-flex; align-items: center; gap: .5rem; padding: .45rem .9rem; border-radius: .75rem; font-size: .875rem; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; background: #fff; }
    .status-btn--active { color: #fff; border-color: #4f46e5; background: linear-gradient(180deg, #6366f1 0%, #4f46e5 100%); }
    .country-group { display: inline-flex; gap: .5rem; padding: .5rem; border: 1px solid #e5e7eb; border-radius: 1rem; background: rgba(255, 255, 255, .85); }
    .country-btn { display: inline-flex; align-items: center; gap: .35rem; padding: .35rem .75rem; border-radius: .75rem; font-size: .8rem; font-weight: 500; text-decoration: none; border: 1px solid #cbd5e1; color: #334155; background: #fff; }
    .country-btn--active { color: #fff; border-color: #059669; background: linear-gradient(180deg, #10b981 0%, #059669 100%); }
    .filter-label { font-size: .75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .5px; margin-bottom: .25rem; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0 fw-semibold">Approved Applicants (SMC - Turkey)</h4>
    <a href="<?php echo htmlspecialchars($exportUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success">
        <i class="bi bi-file-earmark-excel me-2"></i>Export Excel
    </a>
</div>

<!-- Status Navigation -->
<div class="mb-3">
    <div class="status-group">
        <a href="turkey_applicants.php" class="status-btn">All</a>
        <a href="turkey_pending.php" class="status-btn">Pending</a>
        <a href="turkey_on-process.php" class="status-btn">On Process</a>
        <a href="turkey_approved.php" class="status-btn status-btn--active">Hired</a>
    </div>
</div>

<!-- Country Filter -->
<?php if (!empty($countriesWithCounts)): ?>
    <div class="mb-3">
        <div class="filter-label">Filter by Country</div>
        <div class="country-group">
            <a href="turkey_approved.php" class="country-btn <?php echo $country === 'all' ? 'country-btn--active' : ''; ?>">All</a>
            <?php foreach ($countriesWithCounts as $c): ?>
                <a href="turkey_approved.php?country=<?php echo (int)$c['id']; ?>" class="country-btn <?php echo $country === (string)$c['id'] ? 'country-btn--active' : ''; ?>"><?php echo h($c['name']); ?> (<?php echo (int)$c['count']; ?>)</a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Search bar -->
<div class="mb-3 d-flex justify-content-end">
    <form method="get" action="turkey_approved.php" class="w-100" style="max-width: 420px;">
        <div class="input-group">
            <input type="text" name="q" class="form-control" placeholder="Search approved applicants..." value="<?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
            <button class="btn btn-outline-secondary" type="submit" title="Search"><i class="bi bi-search"></i></button>
            <?php if ($q !== ''): ?>
                <a class="btn btn-outline-secondary" href="turkey_approved.php?clear=1" title="Clear"><i class="bi bi-x-lg"></i></a>
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
                                <a href="turkey_approved.php?clear=1" class="ms-1">Clear search</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $row): ?>
                        <?php
                            $id = (int)$row['id'];
                            $currentStatus = (string)($row['status'] ?? 'approved');
                            $fullName = getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']);

                            $viewUrl = 'view-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $editUrl = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');

                            // Change Status target links (preserve q)
                            $toPendingUrl    = 'turkey_approved.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl  = 'turkey_approved.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl   = 'turkey_approved.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;
                        ?>
                        <tr>
                            <td>
                                <?php if (!empty($row['picture'])): ?>
                                    <img src="<?php echo htmlspecialchars(getFileUrl($row['picture']), ENT_QUOTES, 'UTF-8'); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <?php echo strtoupper(substr((string)$row['first_name'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['email'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($row['phone_number'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(renderPreferredLocation($row['preferred_location'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($row['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="actions-cell">
                                <div class="btn-group dd-modern dropup">
                                    <!-- View -->
                                    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Replace (SMC version) -->
                                    <button class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#replaceModal"
                                            data-applicant-id="<?php echo (int)$id; ?>"
                                            data-applicant-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Replace this approved applicant">
                                        <i class="bi bi-arrow-repeat me-1"></i> Replace
                                    </button>

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

<!-- ===== Replace Modal ===== -->
<div class="modal fade" id="replaceModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <form id="replaceInitForm" enctype="multipart/form-data">
        <div class="modal-header">
          <h5 class="modal-title">Replace Applicant — <span id="replaceApplicantName"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="original_applicant_id" id="replaceOriginalId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Reason <span class="text-danger">*</span></label>
                <select name="reason" class="form-select" required>
                    <option value="Compensation and Benefits Concerns">Compensation and Benefits Concerns</option>
                    <option value="Workload and Duty-Related Concerns">Workload and Duty-Related Concerns</option>
                    <option value="Employer Conduct and Treatment Issues">Employer Conduct and Treatment Issues</option>
                    <option value="Living Conditions and Accommodation Concerns">Living Conditions and Accommodation Concerns</option>
                    <option value="Communication and Interpersonal Issues">Communication and Interpersonal Issues</option>
                    <option value="Trust and Security Concerns">Trust and Security Concerns</option>
                    <option value="Performance and Work Quality Issues">Performance and Work Quality Issues</option>
                    <option value="Contract and Agreement Violations">Contract and Agreement Violations</option>
                    <option value="Health and Safety Concerns">Health and Safety Concerns</option>
                    <option value="Personal or Family-Related Concerns">Personal or Family-Related Concerns</option>
                    <option value="Legal Compliance Issues">Legal Compliance Issues</option>
                    <option value="Other Concerns">Other Concerns</option>
                </select>
            </div>
            <div class="col-12">
              <label class="form-label">Report / Note <span class="text-danger">*</span></label>
              <textarea name="report_text" class="form-control" rows="4" required></textarea>
            </div>
            <div class="col-12">
              <label class="form-label">Attachments (optional)</label>
              <input type="file" name="attachments[]" class="form-control" multiple>
              <div class="form-text">You can upload images/documents/videos as evidence. (Up to 200MB per file)</div>
            </div>
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

<!-- Scripts for Replace Modal binding -->
<script src="../js/replacements.js"></script>
<script>
  // expose CSRF to JS
  window.CSRF_TOKEN = "<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";

  // Fill hidden id + label when opening modal
  document.getElementById('replaceModal')?.addEventListener('show.bs.modal', function (ev) {
    const btn = ev.relatedTarget;
    if (!btn) return;
    const id   = btn.getAttribute('data-applicant-id') || '';
    const name = btn.getAttribute('data-applicant-name') || '';
    document.getElementById('replaceOriginalId').value = id;
    document.getElementById('replaceApplicantName').textContent = name;
    const wrap = document.getElementById('replacementCandidates');
    if (wrap) wrap.innerHTML = '';
  });

  // Bind the form to the helper
  document.addEventListener('DOMContentLoaded', function() {
    if (window.Replacements) {
      Replacements.bindInit('#replaceInitForm', '#replacementCandidates');
    }
  });

  // Dropdown popper fix
  document.addEventListener('DOMContentLoaded', function() {
      var btns = document.querySelectorAll('.btn-status[data-bs-toggle="dropdown"]');
      btns.forEach(function(btn) {
          if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
              new bootstrap.Dropdown(btn, { boundary: 'viewport', popperConfig: { strategy: 'fixed' } });
          }
      });
  });
</script>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>

