// applicant.js — Booking wizard logic enhancements
// - Office Visit => Mon–Sat only, 08:00–17:00
// - Improved validation messages
// - Submit to server + email confirmation
(function(){
  const bookingEl = document.getElementById('bookingModal');
  if (!bookingEl) return;

  let currentStep = 1;
  const maxStep = 5;

  const stepPanes = [...bookingEl.querySelectorAll('[data-step-pane]')];
  const stepDots  = [...bookingEl.querySelectorAll('.stepper .step')];
  const btnNext   = bookingEl.querySelector('#bkNext');
  const btnBack   = bookingEl.querySelector('#bkBack');
  const btnSubmit = bookingEl.querySelector('#bkSubmit');
  const bkAvatar  = bookingEl.querySelector('#bkAvatar');
  const bkName    = bookingEl.querySelector('#bkName');
  const bkMeta    = bookingEl.querySelector('#bkMeta');

  const modal     = new bootstrap.Modal(bookingEl);

  const selectedServices = new Set();
  bookingEl.querySelectorAll('.oval-tag').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      btn.classList.toggle('active');
      const name = btn.getAttribute('data-service');
      if (btn.classList.contains('active')) selectedServices.add(name);
      else selectedServices.delete(name);
    });
  });

  function gotoStep(step){
    if (step < 1 || step > maxStep) return;
    currentStep = step;
    stepPanes.forEach(p => p.classList.toggle('d-none', parseInt(p.dataset.stepPane) !== step));
    stepDots.forEach(s=>{
      const n = parseInt(s.dataset.step, 10);
      s.classList.toggle('active', n === step);
      s.classList.toggle('completed', n < step);
    });
    btnBack.disabled = step === 1;
    if (btnNext) btnNext.textContent = (step === 4) ? 'Review' : (step === 5 ? 'Finish' : 'Next');
    if (step === 5) buildConfirmation();
  }

  function toast(msg, variant='dark'){
    const el = document.createElement('div');
    el.className = `toast align-items-center text-bg-${variant} border-0 position-fixed bottom-0 end-0 m-3`;
    el.setAttribute('role','alert');
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body">${msg}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    document.body.appendChild(el);
    const t = new bootstrap.Toast(el, { delay: 2400 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  function getApptType(){
    return bookingEl.querySelector('input[name="apptType"]:checked')?.value || '';
  }

  // Office Visit constraints
  function isOfficeVisit(){ return getApptType() === 'Office Visit'; }
  function isValidOfficeDate(dateStr){
    if (!dateStr) return false;
    const d = new Date(dateStr + 'T00:00:00');
    if (isNaN(d)) return false;
    const day = d.getDay(); // 0 Sun ... 6 Sat
    return day >= 1 && day <= 6; // Mon–Sat
  }
  function isValidOfficeTime(timeStr){
    if (!timeStr) return false;
    // "HH:MM"
    const [hh, mm] = timeStr.split(':').map(n=>parseInt(n,10));
    if (isNaN(hh) || isNaN(mm)) return false;
    const minutes = hh*60 + mm;
    const start = 8*60;  // 08:00
    const end   = 17*60; // 17:00
    return minutes >= start && minutes <= end;
  }

  function validateStep(step){
    if (step === 1 && selectedServices.size === 0) { toast('Please select at least one service.'); return false; }
    if (step === 2 && !getApptType()) { toast('Please choose an interview method.'); return false; }
    if (step === 3) {
      const d = bookingEl.querySelector('#bkDate').value;
      const t = bookingEl.querySelector('#bkTime').value;
      if (!d || !t) { toast('Please select date and time.'); return false; }
      if (isOfficeVisit()){
        if (!isValidOfficeDate(d)) { toast('Office Visit is available Mon–Sat only.'); return false; }
        if (!isValidOfficeTime(t)) { toast('Office Visit time must be between 8:00 AM and 5:00 PM.'); return false; }
      }
    }
    if (step === 4) {
      const ids = ['bkFirstName','bkLastName','bkPhone','bkEmail','bkAddress'];
      for (const id of ids) {
        if (!bookingEl.querySelector('#'+id).value.trim()) {
          toast('Please complete all required fields.');
          return false;
        }
      }
      // Basic format checks
      const phone = bookingEl.querySelector('#bkPhone').value.trim();
      const email = bookingEl.querySelector('#bkEmail').value.trim();
      if (!/^\+?\d[\d\s\-]{7,}$/.test(phone)) { toast('Please enter a valid active phone number.'); return false; }
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { toast('Please enter a valid active email.'); return false; }
    }
    return true;
  }

  function buildConfirmation(){
    const apptType = getApptType() || '—';
    const date = bookingEl.querySelector('#bkDate').value || '—';
    const time = bookingEl.querySelector('#bkTime').value || '—';
    const fn = bookingEl.querySelector('#bkFirstName').value || '—';
    const mn = bookingEl.querySelector('#bkMiddleName').value || '';
    const ln = bookingEl.querySelector('#bkLastName').value || '—';
    const phone = bookingEl.querySelector('#bkPhone').value || '—';
    const email = bookingEl.querySelector('#bkEmail').value || '—';
    const addr = bookingEl.querySelector('#bkAddress').value || '—';

    const selected = [...selectedServices].join(', ') || '—';

    bookingEl.querySelector('#bkSummary').innerHTML = `
      <div><strong>Services:</strong> ${selected}</div>
      <div><strong>Interview Method:</strong> ${apptType}</div>
      <div><strong>Date:</strong> ${date}</div>
      <div><strong>Time:</strong> ${time}</div>
      <hr class="my-2">
      <div><strong>Client:</strong> ${fn} ${mn} ${ln}</div>
      <div><strong>Phone:</strong> ${phone}</div>
      <div><strong>Email:</strong> ${email}</div>
      <div><strong>Address:</strong> ${addr}</div>
    `;
  }

  // Navigation
  btnBack.addEventListener('click', () => gotoStep(currentStep - 1));
  btnNext.addEventListener('click', ()=>{
    if (!validateStep(currentStep)) return;
    if (currentStep < maxStep) gotoStep(currentStep + 1);
    else { modal.hide(); }
  });

  // Submit to backend
  btnSubmit?.addEventListener('click', async ()=>{
    // simple re-validate
    if (!validateStep(4)) { gotoStep(4); return; }

    const apptType = getApptType();
    const payload = {
      applicant_id: getCurrentApplicantId(),
      services: [...selectedServices],
      appointment_type: apptType,
      date: bookingEl.querySelector('#bkDate').value,
      time: bookingEl.querySelector('#bkTime').value,
      client_first_name: bookingEl.querySelector('#bkFirstName').value.trim(),
      client_middle_name: bookingEl.querySelector('#bkMiddleName').value.trim(),
      client_last_name: bookingEl.querySelector('#bkLastName').value.trim(),
      client_phone: bookingEl.querySelector('#bkPhone').value.trim(),
      client_email: bookingEl.querySelector('#bkEmail').value.trim(),
      client_address: bookingEl.querySelector('#bkAddress').value.trim()
    };

    try{
      const res = await fetch('../includes/create_booking.php', {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json' },
        body: JSON.stringify(payload)
      });
      const out = await res.json();
      if (!res.ok) throw new Error(out.error || 'Failed to submit booking');

      toast('Request submitted. A confirmation email was sent.', 'success');
      modal.hide();
    }catch(err){
      console.error(err);
      toast('Unable to submit your request at the moment.', 'danger');
    }
  });

  function getCurrentApplicantId(){
    // read from appended dataset in app.js -> showApplicantModal
    const modalEl = document.getElementById('applicantModal');
    try{
      return JSON.parse(modalEl?.dataset?.applicant || '{}').id || null;
    }catch{ return null; }
  }

  // Enforce Office Visit slot automatically when selection changes:
  bookingEl.querySelectorAll('input[name="apptType"]').forEach(r=>{
    r.addEventListener('change', ()=>{
      const isOffice = isOfficeVisit();
      const dateInput = bookingEl.querySelector('#bkDate');
      const timeInput = bookingEl.querySelector('#bkTime');
      if (isOffice){
        // Set min date = today
        const today = new Date();
        const iso = today.toISOString().slice(0,10);
        dateInput.min = iso;
        // If selected day is Sun, bump to Monday
        if (dateInput.value && !isValidOfficeDate(dateInput.value)) {
          toast('Office Visit available Mon–Sat. Please pick another date.');
          dateInput.value = '';
        }
        // Suggest a valid time window
        if (timeInput.value){
          if (!isValidOfficeTime(timeInput.value)){
            toast('Office Visit time is 8:00–17:00. Please adjust.');
            timeInput.value = '';
          }
        }
      } else {
        dateInput.removeAttribute('min');
      }
    });
  });

  // Expose helper if other scripts need to open booking
  window.launchBooking = (applicant) => {
    // Header fill
    if (bkAvatar) {
      bkAvatar.style.backgroundImage = applicant.photo_url ? `url('${applicant.photo_url}')` : '';
      bkAvatar.style.backgroundSize = applicant.photo_url ? 'cover' : '';
      bkAvatar.textContent = applicant.photo_url ? '' : '';
    }
    if (bkName) bkName.textContent = applicant.full_name || '—';
    if (bkMeta) bkMeta.textContent = `${applicant.specialization || '—'} • ${applicant.location_city || '—'}, ${applicant.location_region || '—'}`;

    // Reset to step1 visuals
    gotoStep(1);
    // Clear selections
    bookingEl.querySelectorAll('.oval-tag.active').forEach(el=>el.classList.remove('active'));
    selectedServices.clear();
    bookingEl.querySelectorAll('input[name="apptType"]').forEach(inp => inp.checked = false);
    ['bkDate','bkTime','bkFirstName','bkMiddleName','bkLastName','bkPhone','bkEmail','bkAddress'].forEach(id => {
      const e = bookingEl.querySelector('#'+id); if (e) e.value = '';
    });
    const summary = bookingEl.querySelector('#bkSummary'); if (summary) summary.innerHTML = '';

    modal.show();
  };
})();