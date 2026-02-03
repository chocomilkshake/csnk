// Applicant page booking modal helpers — plain JS (no HTML wrappers)
(function(){
  // If app.js already defines openBookingModal, do nothing.
  if (window.openBookingModal) return;

      const bookingEl = document.getElementById('bookingModal');
      if (!bookingEl) return;

      let currentStep = 1;
      const maxStep = 5;

      const stepPanes = [...bookingEl.querySelectorAll('[data-step-pane]')];
      const stepDots  = [...bookingEl.querySelectorAll('.stepper .step')];
      const btnNext   = bookingEl.querySelector('#bkNext');
      const btnBack   = bookingEl.querySelector('#bkBack');
      const btnDownload = bookingEl.querySelector('#bkDownload');
      const bkAvatar = bookingEl.querySelector('#bkAvatar');
      const bkName   = bookingEl.querySelector('#bkName');
      const bkMeta   = bookingEl.querySelector('#bkMeta');

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
        btnNext.textContent = (step === maxStep) ? 'Finish' : 'Next';
        if (step === 5) buildConfirmation();
      }

      function buildConfirmation(){
        const apptType = bookingEl.querySelector('input[name="apptType"]:checked')?.value || '—';
        const date = bookingEl.querySelector('#bkDate').value || '—';
        const time = bookingEl.querySelector('#bkTime').value || '—';
        const fn = bookingEl.querySelector('#bkFirstName').value || '—';
        const ln = bookingEl.querySelector('#bkLastName').value || '—';
        const phone = bookingEl.querySelector('#bkPhone').value || '—';
        const email = bookingEl.querySelector('#bkEmail').value || '—';
        const addr = bookingEl.querySelector('#bkAddress').value || '—';

        bookingEl.querySelector('#bkSummary').innerHTML = `
          <div><strong>Services:</strong> ${[...selectedServices].join(', ') || '—'}</div>
          <div><strong>Type:</strong> ${apptType}</div>
          <div><strong>Date:</strong> ${date}</div>
          <div><strong>Time:</strong> ${time}</div>
          <hr class="my-2">
          <div><strong>Name:</strong> ${fn} ${ln}</div>
          <div><strong>Phone:</strong> ${phone}</div>
          <div><strong>Email:</strong> ${email}</div>
          <div><strong>Address:</strong> ${addr}</div>
        `;

        // simple placeholder pattern as QR
        const qr = bookingEl.querySelector('#bkQR');
        qr.innerHTML = '';
        const size = 7, cell = 20;
        qr.style.position = 'relative';
        for (let y=0; y<size; y++){
          for (let x=0; x<size; x++){
            const dot = document.createElement('div');
            dot.style.position='absolute';
            dot.style.width = dot.style.height = (cell-2)+'px';
            dot.style.left = (x*cell+1)+'px';
            dot.style.top  = (y*cell+1)+'px';
            dot.style.background = ((x+y)%2===0) ? '#000' : '#fff';
            qr.appendChild(dot);
          }
        }
      }

      function validateStep(step){
        if (step === 1 && selectedServices.size === 0) { toast('Please select at least one service.'); return false; }
        if (step === 2 && !bookingEl.querySelector('input[name="apptType"]:checked')) { toast('Please choose an appointment type.'); return false; }
        if (step === 3) {
          const d = bookingEl.querySelector('#bkDate').value;
          const t = bookingEl.querySelector('#bkTime').value;
          if (!d || !t) { toast('Please select date and time.'); return false; }
        }
        if (step === 4) {
          const ids = ['bkFirstName','bkLastName','bkPhone','bkEmail','bkAddress'];
          for (const id of ids) if (!bookingEl.querySelector('#'+id).value.trim()) { toast('Please complete all fields.'); return false; }
        }
        return true;
      }

      function toast(msg){
        const el = document.createElement('div');
        el.className = 'toast align-items-center text-bg-dark border-0 position-fixed bottom-0 end-0 m-3';
        el.setAttribute('role','alert');
        el.innerHTML = `
          <div class="d-flex">
            <div class="toast-body">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
          </div>`;
        document.body.appendChild(el);
        const t = new bootstrap.Toast(el, { delay: 2200 });
        t.show();
        el.addEventListener('hidden.bs.toast', ()=> el.remove());
      }

      btnBack.addEventListener('click', () => gotoStep(currentStep - 1));
      btnNext.addEventListener('click', ()=>{
        if (!validateStep(currentStep)) return;
        if (currentStep < maxStep) gotoStep(currentStep + 1);
        else { toast('Booking finished. We will contact you shortly.'); modal.hide(); }
      });
      btnDownload.addEventListener('click', () => toast('Receipt download will be implemented.'));

      const modal = new bootstrap.Modal(bookingEl);

  // Expose a small helper for other scripts (optional)
  window.openBookingModal = function(data){
    if (data && data.name) {
      const nameEl = document.getElementById('bkName'); if (nameEl) nameEl.textContent = data.name;
      const metaEl = document.getElementById('bkMeta'); if (metaEl) metaEl.textContent = data.meta || '—';
    }
    modal.show();
  };
})();