<?php
// FILE: admin/pages/on-hold.php
$pageTitle = 'On Hold Applicants';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

// CSRF token (used by revert form)
if (empty($_SESSION['csrf_token'])) {
    try { $_SESSION['csrf_token'] = bin2hex(random_bytes(16)); }
    catch (Throwable $e) { $_SESSION['csrf_token'] = bin2hex((string)mt_rand()); }
}

$applicant = new Applicant($database);

/**
 * --- Search Memory (same behavior as other lists) ---
 */
if (isset($_GET['clear']) && $_GET['clear'] === '1') {
    unset($_SESSION['onhold_q']);
    redirect('on-hold.php'); exit;
}
$q = '';
if (isset($_GET['q'])) {
    $q = trim((string)$_GET['q']);
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
    $_SESSION['onhold_q'] = $q;
} elseif (!empty($_SESSION['onhold_q'])) {
    $q = (string)$_SESSION['onhold_q'];
}

/**
 * Load list
 */
$applicants = [];
if (method_exists($applicant, 'getAllByStatus')) {
    $applicants = $applicant->getAllByStatus('on_hold');
} elseif (method_exists($applicant, 'getAll')) {
    // fallback if your class uses getAll(status)
    $applicants = $applicant->getAll('on_hold');
}

/**
 * Filter by search
 */
if ($q !== '') {
    $needle = mb_strtolower($q);
    $applicants = array_values(array_filter($applicants, function(array $app) use ($needle) {
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
}

/**
 * Helpers
 */
function renderPreferredLocation(?string $json, int $maxLen = 34): string {
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
    if (mb_strlen($full) > $maxLen) {
        return $cities[0] . '…';
    }
    return $full;
}

function getFullNameSafe($first, $middle, $last, $suffix) {
    return getFullName($first, $middle, $last, $suffix);
}

function h($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

// Preserve query in links
$preserveQ = ($q !== '') ? ('&q=' . urlencode($q)) : '';
?>
<style>
  /* Keep dropdowns visible above table clipping */
  .table-card,
  .table-card .card-body,
  .table-card .table-responsive { overflow: visible !important; }
  .table-card tr.row-raised { position: relative; z-index: 1060; }

  td.actions-cell { white-space: nowrap; }
  .actions-inline { display: inline-flex; gap: .5rem; align-items: center; flex-wrap: nowrap; }
  .actions-inline .btn { flex: 0 0 auto; }

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

  .badge-onhold {
    background: #f1f5f9;
    color: #0f172a;
    border: 1px solid #e2e8f0;
    border-radius: .5rem;
    padding: .25rem .5rem;
    font-weight: 600;
  }

  /* Modal polish */
  .revert-modal .modal-header {
    border-bottom: none;
    padding-bottom: 0;
  }
  .revert-modal .app-header {
    display: flex; gap: 12px; align-items: center;
    padding: .25rem 0 1rem 0;
    border-bottom: 1px solid #eef2f7;
    margin-bottom: 1rem;
  }
  .revert-modal .app-photo {
    width: 44px; height: 44px; object-fit: cover; border-radius: 8px;
    background: #f1f5f9;
  }
  .revert-modal .status-chip {
    display: inline-flex; align-items: center; gap: .35rem;
    padding: .15rem .5rem; border: 1px solid #e2e8f0; border-radius: .5rem;
    font-size: .8rem; color: #334155; background: #f8fafc;
  }
  .revert-modal .form-text { color: #64748b; }
  .revert-modal .counter {
    font-size: .8rem; color: #64748b;
  }
  .revert-modal .modal-footer { border-top: none; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0 fw-semibold">On Hold Applicants</h4>
</div>

<!-- 🔎 Search -->
<div class="mb-3 d-flex justify-content-end">
  <form action="on-hold.php" method="get" class="w-100" style="max-width: 420px;">
    <div class="input-group">
      <input
        type="text"
        name="q"
        class="form-control"
        placeholder="Search on hold applicants…"
        value="<?php echo h($q); ?>"
        autocomplete="off">
      <button class="btn btn-outline-secondary" type="submit" title="Search">
        <i class="bi bi-search"></i>
      </button>
      <?php if ($q !== ''): ?>
        <a href="on-hold.php?clear=1" class="btn btn-outline-secondary" title="Clear">
          <i class="bi bi-x-lg"></i>
        </a>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="card table-card">
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-striped table-hover align-middle mb-0">
        <thead>
          <tr>
            <th>Photo</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Location</th>
            <th>Date Applied</th>
            <th style="width: 360px;">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($applicants)): ?>
          <tr>
            <td colspan="7" class="text-center text-muted py-5">
              <i class="bi bi-inbox fs-1 d-block mb-3"></i>
              <?php if ($q === ''): ?>
                No on hold applicants.
              <?php else: ?>
                No results for "<strong><?php echo h($q); ?></strong>".
                <a href="on-hold.php?clear=1" class="ms-1">Clear search</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($applicants as $app): ?>
            <?php
              $id = (int)($app['id'] ?? 0);
              $name = getFullNameSafe($app['first_name'] ?? '', $app['middle_name'] ?? '', $app['last_name'] ?? '', $app['suffix'] ?? '');
              $viewUrl = 'view-applicant.php?id=' . $id . $preserveQ;
              $historyUrl = 'view-applicant-history.php?id=' . $id . $preserveQ;
              $photo = !empty($app['picture']) ? getFileUrl($app['picture']) : '';
            ?>
            <tr>
              <td>
                <?php if ($photo): ?>
                  <img src="<?php echo h($photo); ?>" alt="Photo" class="rounded" width="50" height="50" style="object-fit:cover;">
                <?php else: ?>
                  <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center" style="width:50px;height:50px;">
                    <?php echo strtoupper(substr((string)($app['first_name'] ?? ''), 0, 1)); ?>
                  </div>
                <?php endif; ?>
              </td>
              <td class="fw-semibold">
                <?php echo h($name); ?>
                <span class="badge-onhold ms-2">On Hold</span>
              </td>
              <td><?php echo h($app['phone_number'] ?? '—'); ?></td>
              <td><?php echo h($app['email'] ?? 'N/A'); ?></td>
              <td><?php echo h(renderPreferredLocation($app['preferred_location'] ?? null)); ?></td>
              <td><?php echo h(formatDate($app['created_at'] ?? '')); ?></td>

              <td class="actions-cell">
                <div class="actions-inline dd-modern">
                  <!-- View -->
                  <a href="<?php echo h($viewUrl); ?>" class="btn btn-sm btn-info">
                    <i class="bi bi-eye"></i> View
                  </a>

                  <!-- History -->
                  <a href="<?php echo h($historyUrl); ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-clock-history"></i> History
                  </a>

                  <!-- Change Status -->
                  <div class="dropdown">
                    <button
                      type="button"
                      class="btn btn-sm btn-outline-primary dropdown-toggle"
                      data-bs-toggle="dropdown"
                      data-bs-auto-close="true"
                      data-bs-display="static"
                      data-bs-offset="0,8"
                      aria-expanded="false"
                      id="changeStatusBtn-<?php echo $id; ?>">
                      <i class="bi bi-arrow-left-right me-1"></i> Change Status
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="changeStatusBtn-<?php echo $id; ?>">
                      <li>
                        <!-- Trigger single shared modal -->
                        <a
                          class="dropdown-item js-open-revert"
                          href="#"
                          data-bs-toggle="modal"
                          data-bs-target="#revertModal"
                          data-app-id="<?php echo $id; ?>"
                          data-app-name="<?php echo h($name); ?>"
                          data-app-photo="<?php echo h($photo); ?>"
                        >
                          <i class="bi bi-arrow-counterclockwise text-warning"></i>
                          <span>Revert to Pending</span>
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item" href="blacklist-applicant.php?id=<?php echo $id; ?>">
                          <i class="bi bi-slash-circle text-danger"></i>
                          <span>Blacklist</span>
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

<!-- 🔁 Single, reusable modal (NOTE: OUTSIDE the table) -->
<div class="modal fade revert-modal" id="revertModal" tabindex="-1"
     aria-labelledby="revertModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
<form class="modal-content border-0 shadow-lg" method="POST" action="revert-onhold.php">
      <div class="modal-header bg-light border-0 pb-0">
        <div class="d-flex align-items-center gap-3">
          <div class="bg-warning bg-opacity-25 p-2 rounded-circle">
            <i class="bi bi-arrow-counterclockwise text-warning fs-5"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="revertModalLabel">
              Revert to Pending
            </h5>
            <p class="text-muted small mb-0">Applicant will be moved back to pending status</p>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body py-3">
        <!-- Applicant header card -->
        <div class="card border bg-light mb-4">
          <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
              <div class="photo-slot">
                <!-- will be filled by JS -->
                <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm d-none"
                     id="revertAvatarFallback" style="width:56px;height:56px;">
                  <span class="fs-5 fw-bold" id="revertAvatarLetter">A</span>
                </div>
                <img src="" class="rounded-circle shadow-sm d-none" width="56" height="56"
                     style="object-fit: cover;" alt="Photo" id="revertAvatarImg">
              </div>
              <div class="flex-grow-1">
                <div class="fw-bold fs-5" id="revertApplicantName">Applicant Name</div>
                <div class="d-flex align-items-center gap-2 mt-1">
                  <span class="badge bg-secondary bg-opacity-25 text-dark">
                    <i class="bi bi-pause-circle me-1"></i>On Hold
                  </span>
                  <i class="bi bi-arrow-right text-muted"></i>
                  <span class="badge bg-warning bg-opacity-25 text-dark">
                    <i class="bi bi-hourglass-split me-1"></i>Pending
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <input type="hidden" name="csrf_token" value="<?php echo h($_SESSION['csrf_token']); ?>">
        <input type="hidden" name="applicant_id" id="revertApplicantId" value="">

        <div class="row g-4">
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold">
              Reason <span class="text-danger">*</span>
            </label>
            <select name="reason" class="form-select form-select-lg" required id="revertReason">
              <option value="" selected>Select a reason</option>
              <option value="Health Issues Resolved">Health Issues Resolved</option>
              <option value="Personal Problems Solved">Personal Problems Solved</option>
              <option value="Ready to Work">Ready to Work</option>
              <option value="Documents Complete">Documents Complete</option>
              <option value="Other">Other</option>
            </select>
            <div class="form-text mt-1">Pick the most accurate reason for reverting.</div>
          </div>

          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold d-flex justify-content-between">
              <span>Description <span class="text-danger">*</span></span>
              <span class="counter badge bg-light text-secondary" id="revertDescCounter">0/1000</span>
            </label>
            <textarea
              id="revertDescription"
              name="description"
              class="form-control"
              rows="4"
              maxlength="1000"
              required
              placeholder="Provide details (e.g., proof of recovery, availability confirmation, notes from applicant)…"></textarea>
            <div class="form-text mt-1">This will be added to Reports and Status History.</div>
          </div>
        </div>

        <!-- Info box -->
        <div class="alert alert-info bg-info bg-opacity-10 border-0 mt-3 mb-0">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill text-info mt-1"></i>
            <div class="small">
              <strong>Note:</strong> The applicant will be moved to Pending status and this action will be recorded in the reports.
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer bg-light border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary btn-lg" data-bs-dismiss="modal">
          <i class="bi bi-x-lg me-1"></i> Cancel
        </button>
        <button type="submit" class="btn btn-warning btn-lg text-dark">
          <i class="bi bi-check2-circle me-2"></i> Revert to Pending
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Raise row above others while a dropdown is open (nice polish)
  document.querySelectorAll('td.actions-cell .dropdown').forEach(function(dd) {
    dd.addEventListener('show.bs.dropdown', function() {
      var tr = dd.closest('tr'); if (tr) tr.classList.add('row-raised');
    });
    dd.addEventListener('hidden.bs.dropdown', function() {
      var tr = dd.closest('tr'); if (tr) tr.classList.remove('row-raised');
    });
  });

  // Single shared modal elements
  var modalEl = document.getElementById('revertModal');
  var applicantIdInput = document.getElementById('revertApplicantId');
  var applicantNameEl = document.getElementById('revertApplicantName');
  var avatarImg = document.getElementById('revertAvatarImg');
  var avatarFallback = document.getElementById('revertAvatarFallback');
  var avatarLetter = document.getElementById('revertAvatarLetter');
  var reasonSelect = document.getElementById('revertReason');
  var descTA = document.getElementById('revertDescription');
  var descCounter = document.getElementById('revertDescCounter');

  // Update counter
  var updateCounter = function() {
    var max = parseInt(descTA.getAttribute('maxlength') || '1000', 10);
    descCounter.textContent = (descTA.value.length) + '/' + max;
  };
  descTA.addEventListener('input', updateCounter);
  updateCounter();

  // Wire up triggers
  document.querySelectorAll('.js-open-revert').forEach(function(trigger) {
    trigger.addEventListener('click', function(e) {
      // Let the modal open (Bootstrap handles it), but populate first
      var id = trigger.getAttribute('data-app-id') || '';
      var name = trigger.getAttribute('data-app-name') || 'Applicant';
      var photo = trigger.getAttribute('data-app-photo') || '';

      applicantIdInput.value = id;
      applicantNameEl.textContent = name;

      // Photo / fallback
      if (photo && photo.trim() !== '') {
        avatarImg.src = photo;
        avatarImg.classList.remove('d-none');
        avatarFallback.classList.add('d-none');
      } else {
        // initial letter
        var firstLetter = name.trim().charAt(0).toUpperCase() || 'A';
        avatarLetter.textContent = firstLetter;
        avatarImg.classList.add('d-none');
        avatarFallback.classList.remove('d-none');
      }

      // Clear selects & description each time for safety
      reasonSelect.value = '';
      descTA.value = '';
      updateCounter();
    });
  });

  // Autofocus reason on open
  if (modalEl) {
    modalEl.addEventListener('shown.bs.modal', function(){
      if (reasonSelect) reasonSelect.focus();
    });
  }
});
</script>

<?php require_once '../includes/footer.php'; ?>