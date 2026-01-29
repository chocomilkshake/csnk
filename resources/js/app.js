// IIFE to avoid globals
(function () {
  const PAGE_SIZE_DEFAULT = 12;

  // --- DOM refs ---
  const grid = document.getElementById('cardsGrid');
  const pagination = document.getElementById('pagination');
  const resultsCount = document.getElementById('resultsCount');
  const searchForm = document.getElementById('searchForm');
  const filtersForm = document.getElementById('filtersForm');
  const yearSpan = document.getElementById('year');
  if (yearSpan) yearSpan.textContent = new Date().getFullYear();

  // --- Helpers ---
  // Data URL (relative to the page `view/applicant.html`)
  const DATA_URL = new URL('../resources/data/applicants.json', window.location.href).toString();
  console.log('Fetching applicants from:', DATA_URL); // <- keep this for debugging

  // Small utility helpers to make filtering safer/readable
  const norm = (s) => String(s ?? '').toLowerCase().trim();
  const num = (n, d = 0) => (Number.isFinite(Number(n)) ? Number(n) : d);
  const dateOrNull = (v) => {
    const t = Date.parse(v);
    return Number.isFinite(t) ? new Date(t) : null;
  };

  // Debounce to avoid too many refreshes on rapid filter changes
  function debounce(fn, delay = 250) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  }

  // Show/hide loading affordance
  function setLoading(isLoading) {
    if (isLoading) {
      grid.setAttribute('aria-busy', 'true');
      grid.innerHTML = `
        <div class="d-flex align-items-center justify-content-center py-5 text-secondary">
          <div class="spinner-border me-2" role="status" aria-label="Loading"></div>
          Loading applicants…
        </div>`;
    } else {
      grid.removeAttribute('aria-busy');
    }
  }

  // --- Data service ---
  const DataService = {
    cache: null,
    async loadAll() {
      if (this.cache) return this.cache;

      const dataURL = DATA_URL;

      let res;
      try {
        res = await fetch(dataURL, { cache: 'no-store' });
      } catch (err) {
        throw new Error(`Network error while fetching ${dataURL}: ${err?.message || err}`);
      }
      if (!res.ok) {
        throw new Error(`Failed to load ${dataURL} — HTTP ${res.status} ${res.statusText}`);
      }

      let data;
      try {
        data = await res.json();
      } catch (err) {
        throw new Error(`Invalid JSON in ${dataURL}: ${err?.message || err}`);
      }

      this.cache = Array.isArray(data) ? data : [];
      return this.cache;
    },

    async fetchApplicants(params) {
      const all = await this.loadAll();

      // Query params (safe parsing)
      const page  = Math.max(1, parseInt(params.get('page')  || '1', 10));
      const limit = Math.max(1, parseInt(params.get('limit') || PAGE_SIZE_DEFAULT, 10));
      const q = norm(params.get('q') || '');
      const locationQ = norm(params.get('location') || '');
      const minExp = num(params.get('min_experience') || 0, 0);
      const availableBy = params.get('available_by');
      const availableByDate = availableBy ? dateOrNull(availableBy) : null;
      const sort = params.get('sort') || 'availability_asc';
      const specs = params.getAll('specializations[]'); // assumes values match exactly what's in data
      const emp = params.getAll('availability[]');      // same assumption
      const langs = params.getAll('languages[]').map(norm); // match languages case-insensitively

      // Filter
      let filtered = all.filter((a) => {
        const name = norm(a.full_name);
        const spec = norm(a.specialization);
        const city = norm(a.location_city);
        const region = norm(a.location_region);
        const yexp = num(a.years_experience, 0);
        const availDate = dateOrNull(a.availability_date);

        if (q && !(name.includes(q) || spec.includes(q) || city.includes(q))) return false;
        if (locationQ && !(city.includes(locationQ) || region.includes(locationQ))) return false;
        if (minExp > 0 && yexp < minExp) return false;
        if (availableByDate && (availDate && availDate > availableByDate)) return false;

        if (specs.length && !specs.includes(a.specialization)) return false;
        if (emp.length && !emp.includes(a.employment_type)) return false;

        if (langs.length) {
          const aset = new Set(
            String(a.languages || '')
              .split(',')
              .map((s) => norm(s))
              .filter(Boolean)
          );
          if (!langs.some((l) => aset.has(l))) return false;
        }
        return true;
      });

      // Sort
      filtered.sort((a, b) => {
        switch (sort) {
          case 'experience_desc':
            return num(b.years_experience, 0) - num(a.years_experience, 0);
          case 'newest': {
            const da = dateOrNull(a.created_at)?.getTime() ?? 0;
            const db = dateOrNull(b.created_at)?.getTime() ?? 0;
            return db - da;
          }
          default: { // availability ascending
            const da = dateOrNull(a.availability_date)?.getTime() ?? 0;
            const db = dateOrNull(b.availability_date)?.getTime() ?? 0;
            return da - db;
          }
        }
      });

      // Pagination
      const total = filtered.length;
      const pages = Math.max(1, Math.ceil(total / limit));
      const safePage = Math.min(page, pages);
      const start = (safePage - 1) * limit;
      const data = filtered.slice(start, start + limit);

      return { data, total, page: safePage, pages };
    }
  };

  // --- Render a single applicant into the profile modal ---
  function renderApplicantToModal(a) {
    const escape = (s) => String(s ?? '');

    // Avatar initials
    const initials = (a.full_name || '')
      .split(' ')
      .map(p => p[0])
      .join('')
      .slice(0, 2)
      .toUpperCase() || 'AP';

  const avatarEl = document.getElementById('avatar');
  if (avatarEl) {
    const photo = safeImg(pickPhoto(a));
    if (photo && !/placeholder-user\.svg$/.test(photo)) {
      avatarEl.innerHTML = `<img src="${photo}" alt="${escapeHtml(a.full_name)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
    } else {
      avatarEl.textContent = initials;
    }
  }
  ``

    // Header fields
    const nameEl = document.getElementById('name');
    const roleEl = document.getElementById('primaryRole');
    const yoeBadgeEl = document.getElementById('yoeBadge');
    if (nameEl) nameEl.textContent = a.full_name || 'Applicant';
    if (roleEl) roleEl.textContent = a.specialization || '—';
    if (yoeBadgeEl) yoeBadgeEl.textContent = `${a.years_experience ?? 0} yrs`;

    // Availability line
    const availDate = Date.parse(a.availability_date);
    const availStr = Number.isFinite(availDate)
      ? new Date(availDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
      : '—';
    const availabilityLine = document.getElementById('availabilityLine');
    if (availabilityLine) {
      availabilityLine.innerHTML =
        `${escape(a.location_city) || '—'}, ${escape(a.location_region) || '—'} • ` +
        `Available from: <strong class="text-danger-emphasis">${availStr}</strong>`;
    }

    // Specialization chips (single specialization)
    const chips = document.getElementById('chipsContainer');
    if (chips) {
      chips.innerHTML = '';
      if (a.specialization) {
        const span = document.createElement('span');
        span.className = 'chip';
        span.textContent = a.specialization;
        chips.appendChild(span);
      }
    }

    // Basic info
    const cityValue = document.getElementById('cityValue');
    const regionValue = document.getElementById('regionValue');
    const yoeValue = document.getElementById('yoeValue');
    const empValue = document.getElementById('employmentValue');
    const availValue = document.getElementById('availValue');
    const langValue = document.getElementById('langValue');

    if (cityValue) cityValue.textContent = a.location_city || '—';
    if (regionValue) regionValue.textContent = a.location_region || '—';
    if (yoeValue) yoeValue.textContent = a.years_experience ?? '—';
    if (empValue) empValue.textContent = a.employment_type || '—';
    if (availValue) availValue.textContent = availStr;

    const langs = String(a.languages || '')
      .split(',')
      .map(s => s.trim())
      .filter(Boolean);
    if (langValue) langValue.textContent = langs.length ? langs.join(', ') : '—';
  }

  let PROFILE_MODAL_INSTANCE = null;
  async function openProfileModal(id) {
    // Ensure data is loaded
    const data = DataService.cache || await DataService.loadAll();
    const found = data.find(x => (x.id ?? x.applicant_id) == id);
    if (!found) return;

    // Render
    renderApplicantToModal(found);

    // Show modal
    const modalEl = document.getElementById('applicantModal');
    if (!PROFILE_MODAL_INSTANCE) {
      PROFILE_MODAL_INSTANCE = new bootstrap.Modal(modalEl);
    }
    PROFILE_MODAL_INSTANCE.show();

    // Optional actions (guarded if elements exist)
    const shortlistBtn = document.getElementById('shortlistBtn');
    const messageBtn = document.getElementById('messageBtn');
    if (shortlistBtn) shortlistBtn.onclick = () => alert(`Shortlisted: ${found.full_name}`);
    if (messageBtn) messageBtn.onclick = () => alert(`Message sent to: ${found.full_name}`);
  }

  // --- Params building ---
  function paramsFromForms() {
    const sParams = new URLSearchParams(new FormData(searchForm));
    const fData = new FormData(filtersForm);
    for (const [k, v] of fData.entries()) {
      if (k.endsWith('[]')) sParams.append(k, v);
      else sParams.set(k, v);
    }
    sParams.set('page', '1'); // reset page on new search/filter
    return sParams;
  }

  // pushState for user-initiated changes (makes back button work)
  function updateURL(params, { push = true } = {}) {
    const u = new URL(window.location.href);
    u.search = params.toString();
    if (push) history.pushState({}, '', u);
    else history.replaceState({}, '', u);
  }

  // Escaping
  function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, (m) =>
      ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
    );
  }

  // Ensure URL used in <img> is safe-ish and fallback on error
  function safeImg(src) {
    const fallback = '../resources/img/placeholder-user.svg';
    const val = String(src || '').trim();
    if (!val) return fallback;
    if (/^(https?:)?\/\//i.test(val) || val.startsWith('/') || val.startsWith('./') || val.startsWith('../')) {
      return val;
    }
    return fallback;
  }


  // Try common fields used in your data to find a photo URL
  function pickPhoto(a) {
    return a.photo || a.photo_url || a.image || a.avatar || '';
  }
  ``


  // --- Card template (with data-id and hover activator) ---
  function cardTemplate(a) {
    const availDate = Date.parse(a.availability_date);
    const availStr = Number.isFinite(availDate)
      ? new Date(availDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
      : '—';
    const id = a.id ?? a.applicant_id ?? '';

    const photo = safeImg(pickPhoto(a));
    return `
    <article class="col-12 col-md-6 col-lg-4 hover-activator" data-id="${id}">
      <div class="card h-100">
        <!-- Photo -->
        <div class="ratio ratio-16x9 bg-light">
          <img src="${photo}" alt="${escapeHtml(a.full_name)}" class="object-fit-cover w-100 h-100">
        </div>

        <div class="card-body">
          <h6 class="card-title mb-1">${escapeHtml(a.full_name)}</h6>
          <div class="text-muted small mb-2">${escapeHtml(a.specialization)} • ${escapeHtml(a.employment_type)}</div>
          <div class="small">
            <i class="bi bi-geo-alt text-danger"></i>
            ${escapeHtml(a.location_city)}, ${escapeHtml(a.location_region)}
          </div>
          <div class="small mt-1 d-flex flex-wrap gap-1">
            <span class="badge text-bg-light border">${escapeHtml(a.years_experience ?? 0)} yrs</span>
            <span class="badge text-bg-light border">Avail: ${escapeHtml(availStr)}</span>
          </div>
        </div>

        <div class="card-footer bg-white d-flex gap-2">
          <a href="#" class="btn btn-sm btn-outline-dark flex-fill view-profile-btn">
            <i class="bi bi-person-badge me-1"></i> View Profile
          </a>
      </article>
    </div>
  `;
}

  // --- Rendering ---
  function renderGrid(json) {
    const { data, total } = json;
    if (!data || data.length === 0) {
      grid.innerHTML = `<div class="text-center text-secondary py-5">No results</div>`;
    } else {
      grid.innerHTML = data.map(cardTemplate).join('');
    }
    resultsCount.textContent = `Showing ${data.length} of ${total} applicants`;
    renderPagination(json.page, json.pages);
  }

  function renderPagination(page, pages) {
    pagination.innerHTML = '';

    const makeItem = (label, p, disabled = false, active = false) => {
      const li = document.createElement('li');
      li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

      const a = document.createElement('a');
      a.className = 'page-link';
      a.href = '#';
      a.textContent = label;
      if (active) a.setAttribute('aria-current', 'page');

      a.addEventListener('click', (e) => {
        e.preventDefault();
        if (disabled || active) return;
        const params = currentParams();
        params.set('page', String(p));
        updateURL(params, { push: true });
        refresh(params);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

      li.appendChild(a);
      return li;
    };

    const prevDisabled = page <= 1;
    const nextDisabled = page >= pages;
    pagination.appendChild(makeItem('Previous', page - 1, prevDisabled));

    const span = 2;
    const start = Math.max(1, page - span);
    const end = Math.min(pages, page + span);

    for (let i = start; i <= end; i++) {
      pagination.appendChild(makeItem(String(i), i, false, i === page));
    }
    pagination.appendChild(makeItem('Next', page + 1, nextDisabled));
  }

  function currentParams() {
    return new URLSearchParams(location.search);
  }

  // --- Load/refresh pipeline ---
  async function refresh(params) {
    try {
      setLoading(true);
      const json = await DataService.fetchApplicants(params);
      renderGrid(json);
    } catch (e) {
      grid.innerHTML = `<div class="alert alert-danger">${escapeHtml(e.message)}</div>`;
    } finally {
      setLoading(false);
    }
  }

  // --- Events: search & filters ---
  document.getElementById('applyFilters').addEventListener('click', (e) => {
    e.preventDefault();
    const p = paramsFromForms();
    updateURL(p, { push: true });
    refresh(p);
  });

  document.getElementById('resetFilters').addEventListener('click', (e) => {
    e.preventDefault();
    searchForm.reset();
    filtersForm.reset();
    const p = new URLSearchParams();
    p.set('page', '1');
    p.set('limit', String(PAGE_SIZE_DEFAULT));
    updateURL(p, { push: true });
    refresh(p);
  });

  searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const p = paramsFromForms();
    updateURL(p, { push: true });
    refresh(p);
  });

  // Debounced filter interactions
  const debouncedFilterChange = debounce(() => {
    const p = paramsFromForms();
    updateURL(p, { push: true });
    refresh(p);
  }, 250);

  filtersForm.querySelectorAll('input[type="checkbox"], input[type="range"], select').forEach((el) => {
    el.addEventListener('change', debouncedFilterChange);
    if (el.type === 'range') el.addEventListener('input', debouncedFilterChange);
  });

  // Clearers
  const clear = (name) =>
    filtersForm
      .querySelectorAll(`[name="${name}"]`)
      .forEach((el) => {
        if (el.type === 'checkbox' || el.type === 'radio') el.checked = false;
        else el.value = '';
      });

  document.getElementById('clearSpecs').addEventListener('click', (e) => {
    e.preventDefault();
    clear('specializations[]');
    debouncedFilterChange();
  });
  document.getElementById('clearAvail').addEventListener('click', (e) => {
    e.preventDefault();
    clear('availability[]');
    debouncedFilterChange();
  });
  document.getElementById('clearLangs').addEventListener('click', (e) => {
    e.preventDefault();
    clear('languages[]');
    debouncedFilterChange();
  });

  // --- Card interactions: click + hover ---
  function openProfileById(id) {
    if (!id) return;
    openProfileModal(id); // show modal
  }

  // Event delegation for View Profile + Hire Me on the grid
  grid.addEventListener('click', (e) => {
    const hire = e.target.closest('.hire-me-btn');
    const view = e.target.closest('.view-profile-btn');
    const card = e.target.closest('article.hover-activator');
    if (!hire && !view && !card) return;

    e.preventDefault();
    const host = (hire || view) ? (hire || view).closest('article.hover-activator') : card;
    const id = host?.getAttribute('data-id');
    if (!id) return;

    if (hire) openBookingModal(id);   // show our new 5-step modal
    else openProfileById(id);         // existing path
  });

  // === BOOKING MODAL WIZARD (Bootstrap 5) =====================================
  let BOOKING_MODAL_INSTANCE = null;
  const bookingEl = document.getElementById('bookingModal');

  // Only wire up if the modal exists on the page
  if (bookingEl) {
    const stepPanes = [...bookingEl.querySelectorAll('[data-step-pane]')];
    const stepDots  = [...bookingEl.querySelectorAll('.stepper .step')];
    const btnNext   = bookingEl.querySelector('#bkNext');
    const btnBack   = bookingEl.querySelector('#bkBack');
    const btnDownload = bookingEl.querySelector('#bkDownload');

    // Header (applicant summary in modal)
    const bkAvatar = bookingEl.querySelector('#bkAvatar');
    const bkName   = bookingEl.querySelector('#bkName');
    const bkMeta   = bookingEl.querySelector('#bkMeta');

    // Inputs
    const selectedServices = new Set();
    bookingEl.querySelectorAll('.oval-tag').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        btn.classList.toggle('active');
        const name = btn.getAttribute('data-service');
        if (btn.classList.contains('active')) selectedServices.add(name);
        else selectedServices.delete(name);
      });
    });

    let currentStep = 1;
    const maxStep = 5;

    // Open with a specific applicant ID
    async function openBookingModal(id){
      const data = DataService.cache ?? await DataService.loadAll();
      const a = data.find(x => (x.id ?? x.applicant_id) == id);
      if (!a) return;

      // Prefill header
      const initials = String(a.full_name || '')
        .split(' ').map(p => p[0]).join('').slice(0,2).toUpperCase() || 'AP';

  const photo = safeImg(pickPhoto(a));
  if (bkAvatar) {
    if (photo && !/placeholder-user\.svg$/.test(photo)) {
      bkAvatar.innerHTML = `<img src="${photo}" alt="${escapeHtml(a.full_name)}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
    } else {
      bkAvatar.textContent = initials;
    }
  }
  if (bkName) bkName.textContent = a.full_name || 'Applicant';
  if (bkMeta) bkMeta.textContent = `${a.location_city || '—'}, ${a.location_region || '—'} • ${a.specialization || '—'}`;

      // Reset state
      resetBookingWizard();

      if (!BOOKING_MODAL_INSTANCE) BOOKING_MODAL_INSTANCE = new bootstrap.Modal(bookingEl);
      BOOKING_MODAL_INSTANCE.show();

      // Keep the selected applicant on the element to read later for confirmation
      bookingEl.dataset.applicantId = String(id);
    }
    // Expose to global scope for the grid handler
    window.openBookingModal = openBookingModal;

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

      if (step === 5) {
        buildConfirmation();
      }
    }

    function resetBookingWizard(){
      currentStep = 1;
      selectedServices.clear();
      bookingEl.querySelectorAll('.oval-tag').forEach(b=>b.classList.remove('active'));
      bookingEl.querySelectorAll('input[name="apptType"]').forEach(r=>r.checked=false);
      ['bkDate','bkTime','bkFirstName','bkLastName','bkPhone','bkEmail','bkAddress']
        .forEach(id => (bookingEl.querySelector('#'+id).value = ''));
      gotoStep(1);
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
        const required = ['bkFirstName','bkLastName','bkPhone','bkEmail','bkAddress'];
        for (const id of required) {
          if (!bookingEl.querySelector('#'+id).value.trim()) { toast('Please complete all fields.'); return false; }
        }
      }
      return true;
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

      // Simple placeholder "QR" (draw a pattern)
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

    // Back / Next
    btnBack.addEventListener('click', () => gotoStep(currentStep - 1));
    btnNext.addEventListener('click', () => {
      if (!validateStep(currentStep)) return;
      if (currentStep < maxStep) gotoStep(currentStep + 1);
      else {
        toast('Booking finished. We will contact you shortly.');
        BOOKING_MODAL_INSTANCE?.hide();
      }
    });

    btnDownload.addEventListener('click', () => {
      // Stub for receipt download – connect to backend as needed
      toast('Receipt download will be implemented.');
    });
  } else {
    // Fallback if the modal HTML isn't present yet
    window.openBookingModal = function () {
      alert('Booking modal not found on this page.');
    };
  }
  // ===========================================================================

  // Small toast (Bootstrap)
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

  // Back/forward navigation support
  window.addEventListener('popstate', () => {
    refresh(currentParams());
  });

  // --- First load ---
  (async function firstLoad() {
    const params = currentParams();
    if (!params.get('limit')) params.set('limit', String(PAGE_SIZE_DEFAULT));
    if (!params.get('page')) params.set('page', '1');

    updateURL(params, { push: false }); // replace on initial load only
    await refresh(params);
  })();
})();