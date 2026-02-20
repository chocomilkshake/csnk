<?php
// FILE: pages/approved.php
$pageTitle = 'Approved Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

// Ensure session is active (for search persistence + CSRF)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
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

// Preserve the search in action links and export URL
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
$exportUrl = '../includes/excel_approved.php' . ($q !== '' ? ('?q=' . urlencode($q)) : '');
?>
<!-- ===== Fix dropdown clipping & remove table scroll wrapper (same as pending.php) ===== -->
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

                            $fullName = getFullName($row['first_name'], $row['middle_name'], $row['last_name'], $row['suffix']);

                            $viewUrl = 'view_approved.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $editUrl = 'edit-applicant.php?id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');
                            $deleteUrl = 'approved.php?action=delete&id=' . $id . ($q !== '' ? '&q=' . urlencode($q) : '');

                            // Change Status target links (preserve q)
                            $toPendingUrl    = 'approved.php?action=update_status&id=' . $id . '&to=pending'    . $preserveQ;
                            $toOnProcessUrl  = 'approved.php?action=update_status&id=' . $id . '&to=on_process' . $preserveQ;
                            $toApprovedUrl   = 'approved.php?action=update_status&id=' . $id . '&to=approved'   . $preserveQ;
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
                                    <button class="btn btn-sm btn-danger"
                                            data-bs-toggle="modal"
                                            data-bs-target="#replaceModal"
                                            data-applicant-id="<?php echo (int)$id; ?>"
                                            data-applicant-name="<?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                            title="Replace this approved applicant">
                                        <i class="bi bi-arrow-repeat me-1"></i> Replace
                                    </button>

                                    <!-- Optional: Delete (commented out by default)
                                    <a href="<?php echo htmlspecialchars($deleteUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                       class="btn btn-sm btn-danger"
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this applicant?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    -->

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

<!-- ===== Replace Modal (NEW) ===== -->
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
                <option value="AWOL">AWOL</option>
                <option value="Client Left">Client Left</option>
                <option value="Not Finished Contract">Not Finished Contract</option>
                <option value="Performance Issue">Performance Issue</option>
                <option value="Other">Other</option>
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
            <i class="bi bi-arrow-repeat me-1"></i> Start &amp; Suggest
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Scripts for Replace Modal binding -->
<script src="../js/replacements.js"></script>
<script>
  // optional: expose CSRF to JS (used by replacements.js)
  window.CSRF_TOKEN = "<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>";

  // Fill hidden id + label when opening modal
  document.getElementById('replaceModal')?.addEventListener('show.bs.modal', function (ev) {
    const btn = ev.relatedTarget;
    if (!btn) return;
    const id   = btn.getAttribute('data-applicant-id') || '';
    const name = btn.getAttribute('data-applicant-name') || '';
    document.getElementById('replaceOriginalId').value = id;
    document.getElementById('replaceApplicantName').textContent = name;
    // Clear previous results
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

<?php require_once '../includes/footer.php'; ?>