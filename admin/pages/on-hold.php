<?php
// FILE: admin/pages/on-hold.php
$pageTitle = 'On Hold Applicants (CSNK)';
require_once '../includes/header.php';
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