<?php
// FILE: pages/view-applicant-history.php
$pageTitle = 'Applicant History';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session for consistency (header.php usually starts it)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Resolve role (from header.php globals)
$role         = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');

if (!($isAdmin || $isSuperAdmin)) {
    setFlashMessage('error', 'You do not have permission to view applicant history.');
    redirect('dashboard.php');
    exit;
}

// Validate applicant id
if (!isset($_GET['id'])) {
    setFlashMessage('error', 'Applicant ID is required.');
    redirect('applicants.php');
    exit;
}
$applicantId = (int)$_GET['id'];
if ($applicantId <= 0) {
    setFlashMessage('error', 'Invalid applicant ID.');
    redirect('applicants.php');
    exit;
}

// Replacement context?
$replaceId = isset($_GET['replace_id']) ? (int)$_GET['replace_id'] : 0;

$applicant = new Applicant($database);
$applicantData = $applicant->getById($applicantId);
if (!$applicantData) {
    setFlashMessage('error', 'Applicant not found.');
    redirect('applicants.php');
    exit;
}
$replaceRecord = null;
if ($replaceId > 0) {
    $replaceRecord = $applicant->getReplacementById($replaceId);
}

// Determine if Assign should be visible (replace mode + candidate pending + replacement in selection)
$showAssign = false;
if ($replaceRecord && ($replaceRecord['status'] ?? '') === 'selection' && ($applicantData['status'] ?? '') === 'pending') {
    $showAssign = true;
}

// Check if currently blacklisted to show quick link
$conn = $database->getConnection();
$activeBlacklistId = null;
if ($conn instanceof mysqli) {
    if ($stmt = $conn->prepare("SELECT id FROM blacklisted_applicants WHERE applicant_id = ? AND is_active = 1 ORDER BY created_at DESC LIMIT 1")) {
        $stmt->bind_param("i", $applicantId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        if (!empty($row['id'])) {
            $activeBlacklistId = (int)$row['id'];
        }
        $stmt->close();
    }
}

// Fetch history you want (this file originally focused on blacklist history;
// you can add other history joins similarly)
$history = [];
if ($conn instanceof mysqli) {
    $sqlHist = "
        SELECT
            b.*,
            au.full_name AS created_by_name, au.username AS created_by_username,
            ru.full_name AS reverted_by_name, ru.username AS reverted_by_username
        FROM blacklisted_applicants b
        LEFT JOIN admin_users au ON au.id = b.created_by
        LEFT JOIN admin_users ru ON ru.id = b.reverted_by
        WHERE b.applicant_id = ?
        ORDER BY b.created_at DESC
    ";
    if ($stmtH = $conn->prepare($sqlHist)) {
        $stmtH->bind_param("i", $applicantId);
        $stmtH->execute();
        $resH = $stmtH->get_result();
        $history = $resH ? $resH->fetch_all(MYSQLI_ASSOC) : [];
        $stmtH->close();
    }
}

// Helpers
function jsonToListPaths(?string $json): array {
    if ($json === null || trim($json) === '') return [];
    $arr = json_decode($json, true);
    if (!is_array($arr)) return [];
    return array_values(array_filter(array_map('strval', $arr)));
}
function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$fullName = getFullName(
    $applicantData['first_name'] ?? '',
    $applicantData['middle_name'] ?? '',
    $applicantData['last_name'] ?? '',
    $applicantData['suffix'] ?? ''
);

$pictureUrl = !empty($applicantData['picture']) ? getFileUrl($applicantData['picture']) : null;

// Quick counts
$totalCount    = count($history);
$activeCount   = 0;
$revertedCount = 0;
foreach ($history as $h) {
    if ((int)($h['is_active'] ?? 0) === 1) $activeCount++; else $revertedCount++;
}
?>
<style>
:root{
  --rail:#e9ecef;
  --dot:#0d6efd;
  --dot-shadow: rgba(13,110,253,.18);
  --muted:#6c757d;
  --soft-danger-bg:#fdecec; --soft-danger:#c1121f; --soft-danger-b:#f7c9c9;
  --soft-success-bg:#e8f7ec; --soft-success:#146c43; --soft-success-b:#c7ebd2;
}
.history-toolbar{
  position: sticky; top: -.5rem; z-index: 5;
  background: #fff; border: 1px solid #eef2f7; border-radius: .75rem;
  padding: .75rem .75rem; box-shadow: 0 1px 2px rgba(0,0,0,.03);
}
.stat-chip{ display:inline-flex; align-items:center; gap:.4rem; padding:.25rem .6rem;
  border-radius:999px; background:#f8fafc; border:1px solid #eef2f7; color:#0f172a; font-weight:600; font-size:.85rem; }
.stat-chip .dot{width:.5rem;height:.5rem;border-radius:999px;display:inline-block}
.stat-chip .dot.active{background:#dc3545}
.stat-chip .dot.reverted{background:#198754}
.timeline{ position:relative; margin:0; padding:0 0 0 1.5rem; list-style:none; }
.timeline::before{ content:''; position:absolute; left:10px; top:0; bottom:0; width:2px; background:var(--rail); }
.timeline-item{ position:relative; margin-bottom:1rem; }
.timeline-item::before{ content:''; position:absolute; left:5px; top:.65rem; width:12px; height:12px; background:var(--dot); border-radius:50%;
  box-shadow: 0 0 0 4px var(--dot-shadow); }
.timeline-card{ border:1px solid #eef2f7; border-radius:.75rem; overflow:hidden; box-shadow: 0 1px 1px rgba(0,0,0,.03); }
.timeline-card .card-header{ background:#fff; border-bottom:1px solid #f1f3f5; padding:.75rem 1rem; }
.timeline-card .summary{ display:flex; flex-wrap:wrap; gap:.5rem 1rem; align-items:center; }
.badge-soft-danger{background:var(--soft-danger-bg); color:var(--soft-danger); border:1px solid var(--soft-danger-b);}
.badge-soft-success{background:var(--soft-success-bg); color:var(--soft-success); border:1px solid var(--soft-success-b);}
.meta{ display:flex; gap:.5rem 1rem; flex-wrap:wrap; color:var(--muted); font-size:.9rem; }
.meta .item{display:flex; align-items:center; gap:.4rem;}
.file-pill{ display:inline-flex; align-items:center; gap:.35rem; padding:.3rem .55rem;
  border:1px solid #e9ecef; border-radius:999px; background:#f8f9fa; color:#0d6efd; text-decoration:none; font-size:.85rem; }
.file-pill:hover{ background:#eef2f7; text-decoration:none; }
.filter-wrap{ display:flex; gap:.5rem; flex-wrap:wrap; }
.form-rounded{border-radius:999px !important;}
.btn-assign-top { background:#0d9488; color:#fff; border:0; }
.btn-assign-top:hover { background:#0f766e; color:#fff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h4 class="mb-0 fw-semibold">Applicant History</h4>
    <small class="text-muted">Blacklist timeline and related actions.</small>
  </div>
  <div class="d-flex gap-2">
    <?php if ($activeBlacklistId): ?>
      <a class="btn btn-outline-danger btn-sm" href="<?php echo 'blacklisted-view.php?id='.(int)$activeBlacklistId; ?>">
        <i class="bi bi-slash-circle me-1"></i>Active Blacklist Details
      </a>
    <?php endif; ?>

    <?php if ($showAssign): ?>
      <form method="post" action="replace-assign.php" class="d-inline">
        <input type="hidden" name="replace_id" value="<?php echo (int)$replaceId; ?>">
        <input type="hidden" name="replacement_applicant_id" value="<?php echo (int)$applicantId; ?>">
        <button type="submit" class="btn btn-assign-top btn-sm">
          <i class="bi bi-check2-circle me-1"></i> Assign (Replacement)
        </button>
      </form>
    <?php endif; ?>

    <a class="btn btn-outline-secondary btn-sm" href="<?php echo 'view-applicant.php?id='.(int)$applicantId . ($replaceId ? ('&replace_id='.(int)$replaceId) : ''); ?>">
      <i class="bi bi-arrow-left me-1"></i>Back to Applicant
    </a>
  </div>
</div>

<div class="card mb-3">
  <div class="card-body">
    <div class="d-flex align-items-center gap-3">
      <?php if ($pictureUrl): ?>
        <img src="<?php echo h($pictureUrl); ?>"
             alt="Photo" class="rounded-circle" width="64" height="64" style="object-fit:cover;">
      <?php else: ?>
        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center"
             style="width:64px;height:64px;">
          <?php echo strtoupper(substr((string)($applicantData['first_name'] ?? ''), 0, 1)); ?>
        </div>
      <?php endif; ?>
      <div class="flex-grow-1">
        <div class="fw-semibold fs-5"><?php echo h($fullName); ?></div>
        <div class="text-muted small">
          Applicant ID: <?php echo (int)$applicantId; ?>
        </div>
      </div>
      <div class="text-end">
        <span class="stat-chip me-1" title="Total records">
          <span class="dot" style="background:#64748b"></span> <?php echo (int)$totalCount; ?>
        </span>
        <span class="stat-chip me-1" title="Active">
          <span class="dot active"></span> <?php echo (int)$activeCount; ?>
        </span>
        <span class="stat-chip" title="Reverted">
          <span class="dot reverted"></span> <?php echo (int)$revertedCount; ?>
        </span>
      </div>
    </div>
  </div>
</div>

<!-- Filters -->
<div class="history-toolbar mb-3">
  <div class="filter-wrap">
    <div class="input-group input-group-sm" style="max-width: 360px;">
      <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
      <input id="historySearch" type="text" class="form-control form-control-sm form-rounded"
             placeholder="Search reason, issue, user, date...">
    </div>

    <div>
      <select id="statusFilter" class="form-select form-select-sm form-rounded">
        <option value="all" selected>Show: All</option>
        <option value="active">Active only</option>
        <option value="reverted">Reverted only</option>
      </select>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-body">
    <?php if (empty($history)): ?>
      <div class="text-center text-muted py-4">No blacklist history recorded for this applicant.</div>
    <?php else: ?>
      <ul class="timeline mb-0" id="historyTimeline">
        <?php foreach ($history as $rec): ?>
          <?php
            $isActive       = (int)($rec['is_active'] ?? 0) === 1;
            $statusKey      = $isActive ? 'active' : 'reverted';
            $createdByLabel = $rec['created_by_name'] ?: ($rec['created_by_username'] ?: 'System');
            $createdAt      = formatDateTime($rec['created_at'] ?? '');
            $reason         = (string)($rec['reason'] ?? '');
            $issue          = (string)($rec['issue'] ?? '');
            $proofs         = jsonToListPaths($rec['proof_paths'] ?? null);

            $revertedBy     = $rec['reverted_by_name'] ?: ($rec['reverted_by_username'] ?: '');
            $revertedAt     = !empty($rec['reverted_at']) ? formatDateTime($rec['reverted_at']) : '';
            $compNote       = (string)($rec['compliance_note'] ?? '');
            $compProofs     = jsonToListPaths($rec['compliance_proof_paths'] ?? null);

            $searchBlob = strtolower(trim(
              $createdAt.' '.$reason.' '.$issue.' '.$createdByLabel.' '.$revertedBy.' '.$revertedAt
            ));
            $rowId = (int)$rec['id'];
          ?>
          <li class="timeline-item history-item"
              data-status="<?php echo $statusKey; ?>"
              data-search="<?php echo h($searchBlob); ?>">
            <div class="timeline-card">
              <div class="card-header">
                <div class="summary">
                  <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?php echo h($createdAt); ?></span>
                  <?php if ($isActive): ?>
                    <span class="badge badge-soft-danger">Active</span>
                  <?php else: ?>
                    <span class="badge badge-soft-success">Reverted</span>
                  <?php endif; ?>
                  <span class="fw-semibold text-danger text-truncate" style="max-width: 46ch;" title="<?php echo h($reason); ?>">
                    <i class="bi bi-flag-fill me-1"></i><?php echo h($reason); ?>
                  </span>
                  <span class="text-muted small ms-auto">
                    <i class="bi bi-person-badge me-1"></i>Logged by:
                    <span class="fw-semibold"><?php echo h($createdByLabel); ?></span>
                  </span>
                </div>
              </div>

              <div class="accordion accordion-flush" id="recAcc<?php echo $rowId; ?>">
                <div class="accordion-item">
                  <h2 class="accordion-header" id="head<?php echo $rowId; ?>">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#col<?php echo $rowId; ?>" aria-expanded="false"
                            aria-controls="col<?php echo $rowId; ?>">
                      View details
                    </button>
                  </h2>
                  <div id="col<?php echo $rowId; ?>" class="accordion-collapse collapse" aria-labelledby="head<?php echo $rowId; ?>">
                    <div class="accordion-body">

                      <?php if ($issue !== ''): ?>
                        <div class="mb-3">
                          <div class="small text-muted mb-1">Issue / Details</div>
                          <div><?php echo nl2br(h($issue)); ?></div>
                        </div>
                      <?php endif; ?>

                      <div class="mb-3">
                        <div class="small text-muted mb-1">Original Proofs</div>
                        <?php if (empty($proofs)): ?>
                          <div class="text-muted small">None</div>
                        <?php else: ?>
                          <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($proofs as $i => $p): $url = getFileUrl($p); ?>
                              <a class="file-pill" target="_blank" href="<?php echo h($url); ?>">
                                <i class="bi bi-paperclip"></i> Proof <?php echo $i + 1; ?>
                              </a>
                            <?php endforeach; ?>
                          </div>
                        <?php endif; ?>
                      </div>

                      <div class="meta mb-2">
                        <div class="item"><i class="bi bi-clock-history"></i><span><?php echo h($createdAt); ?></span></div>
                        <div class="item"><i class="bi bi-person-badge"></i><span><?php echo h($createdByLabel); ?></span></div>
                      </div>

                      <?php if (!$isActive): ?>
                        <hr>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <div class="small text-muted mb-1">Reverted by</div>
                            <div class="fw-semibold"><?php echo h($revertedBy !== '' ? $revertedBy : '—'); ?></div>
                            <div class="text-muted small"><?php echo $revertedAt !== '' ? h($revertedAt) : '—'; ?></div>
                          </div>
                          <div class="col-md-6">
                            <div class="small text-muted mb-1">Compliance Note</div>
                            <div><?php echo $compNote !== '' ? nl2br(h($compNote)) : '<span class="text-muted">—</span>'; ?></div>
                          </div>
                        </div>

                        <div class="mt-3">
                          <div class="small text-muted mb-1">Compliance Proofs</div>
                          <?php if (empty($compProofs)): ?>
                            <div class="text-muted small### `admin/pages/approved.php`
```php
<?php
// FILE: pages/approved.php
$pageTitle = 'Approved Applicants';
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
    unset($_SESSION['approved_q']);
    redirect('approved.php');
    exit;
}

$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) {
        $q = mb_substr($q, 0, 100);
    }
    $_SESSION['approved_q'] = $q;
} elseif (!empty($_SESSION['approved_q'])) {
    $q = (string)$_SESSION['approved_q'];
}

/** Handle delete (soft delete) with search preserved — only if you want delete in Approved */
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $deleted = false;

    if (method_exists($applicant, 'softDelete')) {
        $deleted = (bool)$applicant->softDelete($id);
    } elseif (method_exists($applicant, 'update')) {
        $deleted = (bool)$applicant->update($id, ['status' => 'deleted']);
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
            "Deleted applicant {$label} (Approved list)"
        );
    }

    $qs = $q !== '' ? ('?q=' . urlencode($q)) : '';
    redirect('approved.php' . $qs);
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

        // Prefer Applicant::updateStatus if available, else ::update, else direct PDO fallback
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
    redirect('approved.php' . $qs);
    exit;
}

/** Load approved applicants */
$applicants = $applicant->getAll('approved');

/**
 * Helpers
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

        $fullName1 = trim($first + ' ' + $last);
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

// Preserve the search in action links and export URL
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Fix dropdown clipping & remove table scroll wrapper (same as pending.php) ===== -->
<style>
    /* Allow dropdowns to overflow cleanly */
    .table-card, .table-card .card-body { overflow: visible !important; }
    /* In case a .table-responsive is injected by other includes, disable its clipping */
    .table-card .table-responsive { overflow: visible !important; }
    .table-card table, .table-card tbody, .table-card tr { overflow: visible !important; }

    /* Actions cell can render dropdown outside its bounds */
    td.actions-cell {
        position: relative;
        overflow: visible;
        z-index: 10;
        white-space: nowrap;
    }

    /* Modern dropdown styling (consistent) */
    .dd-modern .dropdown-menu {
        border-radius: .75rem; /* rounded-xl */
        border: 1px solid #e5e7eb; /* slate-200 */
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

    /* Optional: table spacing without forcing scroll */
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
        <!-- Removed .table-responsive to avoid scroll/clipping (matches pending.php) -->
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
                            $id = (int)$row['id'];
                            $currentStatus = (string)($row['status'] ?? 'approved');

                            $viewUrl = 'view_approved.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $editUrl = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $deleteUrl = 'approved.php?action=delete&id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');

                            // Change Status target links (preserve q)
                            $toPendingUrl    = 'approved.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl  = 'approved.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl   = 'approved.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;

                            $fullName = getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']);
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
                                    <a href="<?php echo htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <!-- Edit -->
                                    <a href="<?php echo htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Replace (NEW) -->
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-danger btn-replace-init"
                                        title="Replace this approved applicant"
                                        data-applicant-id="<?php echo (int)$id; ?>"
                                        data-applicant-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#replaceInitModal">
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

<!-- Replace: Start Modal (NEW) -->
<div class="modal fade" id="replaceInitModal" tabindex="-1" aria-labelledby="replaceInitModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <form class="modal-content" action="replace-init.php" method="post" enctype="multipart/form-data">
      <div class="modal-header">
        <h5 class="modal-title" id="replaceInitModalLabel">
          <i class="bi bi-arrow-repeat me-2"></i>Start Replacement
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="original_applicant_id" id="replace_original_applicant_id" value="">

        <div class="alert alert-info d-flex align-items-start gap-2">
          <i class="bi bi-info-circle mt-1"></i>
          <div>
            You are starting a replacement process for:
            <strong id="replace_original_applicant_name">—</strong>.
            After submitting, you’ll be redirected to <em>Pending</em> with ranked suggestions.
          </div>
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label for="replace_reason" class="form-label">Reason</label>
            <select name="reason" id="replace_reason" class="form-select" required>
              <option value="AWOL">AWOL</option>
              <option value="Client Left">Client Left</option>
              <option value="Not Finished Contract">Not Finished Contract</option>
              <option value="Performance Issue">Performance Issue</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="col-12">
            <label for="replace_report_text" class="form-label">Notes / Complication</label>
            <textarea
                name="report_text"
                id="replace_report_text"
                class="form-control"
                rows="5"
                placeholder="Add details (e.g., issue encountered, timeline, client remarks, etc.)"
                required></textarea>
          </div>

          <div class="col-12">
            <label for="replace_attachments" class="form-label">Attachments (optional)</label>
            <input type="file" name="attachments[]" id="replace_attachments" class="form-control" multiple>
            <div class="form-text">
              You can attach proofs/papers/screenshots. Allowed types: images, pdf, doc, video (max 200MB each).
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">
          <i class="bi bi-arrow-repeat me-1"></i> Start Replacement
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize dropdowns (safety)
    var btns = document.querySelectorAll('.btn-status[data-bs-toggle="dropdown"]');
    btns.forEach(function(btn) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            new bootstrap.Dropdown(btn, { boundary: 'viewport', popperConfig: { strategy: 'fixed' } });
        }
    });

    // Hook Replace buttons to modal
    document.querySelectorAll('.btn-replace-init').forEach(function(btn){
        btn.addEventListener('click', function(e){
            var id   = this.getAttribute('data-applicant-id');
            var name = this.getAttribute('data-applicant-name') || '';
            document.getElementById('replace_original_applicant_id').value = id;
            document.getElementById('replace_original_applicant_name').textContent = name;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>