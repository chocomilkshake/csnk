<?php
// FILE: admin/pages/on-hold.php
$pageTitle = 'On Hold Applicants (CSNK)';
require_once '../includes/header.php';
require_once '../includes/Applicant.php';

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