<?php
// FILE: admin/pages/turkey_on-hold.php (SMC - Turkey On Hold Applicants)
$pageTitle = 'SMC - On Hold Applicants';

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

// Check if user has permission to view SMC data
if (!$auth->canSeeSMC()) {
  header('Location: applicants.php');
  exit;
}

$conn = $database->getConnection();

// Find SMC BU ID
$smcBuId = 0;
if ($conn instanceof mysqli) {
  $sqlFindSMCBu = "SELECT bu.id
                     FROM business_units bu
                     JOIN agencies ag ON ag.id = bu.agency_id
                     WHERE ag.code = 'smc' AND bu.active = 1
                     ORDER BY bu.id ASC
                     LIMIT 1";
  if ($stmt = $conn->prepare($sqlFindSMCBu)) {
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!empty($row['id'])) {
      $smcBuId = (int) $row['id'];
      $_SESSION['smc_bu_id'] = $smcBuId;
    }
  }
}

if (empty($_SESSION['current_bu_id'])) {
  header('Location: login.php');
  exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
  try {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
  } catch (Throwable $e) {
    $_SESSION['csrf_token'] = bin2hex((string) mt_rand());
  }
}
$csrf = $_SESSION['csrf_token'] ?? '';

// Include the SMC Applicant class
require_once $ADMIN_ROOT . '/admin-smc/smc-turkey/includes/applicant.php';
require_once $ADMIN_ROOT . '/includes/smc_filter_bar.php';

// Get current user data
$currentUser = $auth->getCurrentUser();

  .actions-inline .btn {

</style>              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($applicants as $app): ?>
              <?php
              $id = (int) ($app['id'] ?? 0);
              $name = getFullName($app['first_name'] ?? '', $app['middle_name'] ?? '', $app['last_name'] ?? '', $app['suffix'] ?? '');
              $appStatus = (string) ($app['status'] ?? 'pending');
              $viewUrl = 'turkey_view-applicant.php?id=' . $id . $preserveQ;
              $historyUrl = 'turkey_view-applicant-history.php?id=' . $id . $preserveQ;
              $photo = !empty($app['picture']) ? getFileUrl($app['picture']) : '';
              ?>
              <tr>
                <td>
                  <?php if ($photo): ?>
                    <img src="<?php echo h($photo); ?>" alt="Photo" class="rounded" width="50" height="50"
                      style="object-fit:cover;">
                  <?php else: ?>
                    <div class="bg-secondary text-white rounded d-flex align-items-center justify-content-center"
                      style="width:50px;height:50px;">
                      <?php echo strtoupper(substr((string) ($app['first_name'] ?? ''), 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td class="fw-semibold">
                  <?php echo h($name); ?>
                  <span class="badge badge-<?php echo strtolower($appStatus); ?> ms-2">
                    <?php echo ucwords(str_replace('_', ' ', $appStatus)); ?>
                  </span>
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
                      <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown"
                        data-bs-auto-close="true" data-bs-display="static" data-bs-offset="0,8" aria-expanded="false"
                        id="changeStatusBtn-<?php echo $id; ?>">
                        <i class="bi bi-arrow-left-right me-1"></i> Change Status
                      </button>
                      <ul class="dropdown-menu dropdown-menu-end shadow"
                        aria-labelledby="changeStatusBtn-<?php echo $id; ?>">
                        <?php if ($appStatus === 'on_hold'): ?>
                        <li>
                          <!-- Revert to Pending -->
                          <a class="dropdown-item js-open-revert" href="#" data-bs-toggle="modal"
                            data-bs-target="#revertModal" data-app-id="<?php echo $id; ?>"
                            data-app-name="<?php echo h($name); ?>" data-app-photo="<?php echo h($photo); ?>">
                            <i class="bi bi-arrow-counterclockwise text-warning"></i>
                            <span>Revert to Pending</span>
                          </a>
                        </li>
                        <?php else: ?>
                        <li>
                          <span class="dropdown-item disabled text-muted" title="Only on-hold applicants can be reverted">
                            <i class="bi bi-exclamation-circle text-secondary"></i>
                            <span>Revert to Pending (only on-hold)</span>
                          </span>
                        </li>
                        <?php endif; ?>
                        <li>
                          <a class="dropdown-item" href="turkey_blacklist-applicant.php?id=<?php echo $id; ?>">
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
<div class="modal fade revert-modal" id="revertModal" tabindex="-1" aria-labelledby="revertModalLabel"
  aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <form class="modal-content border-0 shadow-lg" method="POST" action="turkey_revert-onhold.php">
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
                <div
                  class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center shadow-sm d-none"
                  id="revertAvatarFallback" style="width:56px;height:56px;">
                  <span class="fs-5 fw-bold" id="revertAvatarLetter">A</span>
                </div>
                <img src="" class="rounded-circle shadow-sm d-none" width="56" height="56" style="object-fit: cover;"
                  alt="Photo" id="revertAvatarImg">
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
            <textarea id="revertDescription" name="description" class="form-control" rows="4" maxlength="1000" required
              placeholder="Provide details (e.g., proof of recovery, availability confirmation, notes from applicant)…"></textarea>
            <div class="form-text mt-1">This will be added to Reports and Status History.</div>
          </div>
        </div>

        <!-- Info box -->
        <div class="alert alert-info bg-info bg-opacity-10 border-0 mt-3 mb-0">
          <div class="d-flex align-items-start gap-2">
            <i class="bi bi-info-circle-fill text-info mt-1"></i>
            <div class="small">
              <strong>Note:</strong> The applicant will be moved to Pending status and this action will be recorded in
              the reports.
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
  document.addEventListener('DOMContentLoaded', function () {
    // Raise row above others while a dropdown is open (nice polish)
    document.querySelectorAll('td.actions-cell .dropdown').forEach(function (dd) {
      dd.addEventListener('show.bs.dropdown', function () {
        var tr = dd.closest('tr'); if (tr) tr.classList.add('row-raised');
      });
      dd.addEventListener('hidden.bs.dropdown', function () {
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
    var updateCounter = function () {
      var max = parseInt(descTA.getAttribute('maxlength') || '1000', 10);
      descCounter.textContent = (descTA.value.length) + '/' + max;
    };
    descTA.addEventListener('input', updateCounter);
    updateCounter();

    // Wire up triggers
    document.querySelectorAll('.js-open-revert').forEach(function (trigger) {
      trigger.addEventListener('click', function (e) {
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
      modalEl.addEventListener('shown.bs.modal', function () {
        if (reasonSelect) reasonSelect.focus();
      });
    }
  });
</script>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>