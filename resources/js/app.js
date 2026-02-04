// app.js — Client listing UX (photo-top modern cards)
// v2.8 — approved applicants hidden server-side; cards clickable + modern, NO availability on cards, Hire inside modal (closes then opens booking), pushState with ID + clean URL on close
console.log('app.js loaded successfully - v2.8');

/* =========================================================
   State
========================================================= */
let allApplicants = [];
let filteredApplicants = [];
let currentPage = 1;
const itemsPerPage = 12;

/* =========================================================
   DOM
========================================================= */
const searchForm   = document.getElementById('searchForm');
const filtersForm  = document.getElementById('filtersForm');
const cardsGrid    = document.getElementById('cardsGrid');
const resultsCount = document.getElementById('resultsCount');
const pagination   = document.getElementById('pagination');

/* =========================================================
   Helpers
========================================================= */
function escapeHtml(str) {
  return String(str ?? '').replace(/[&<>"']/g, s => (
    { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":"&#039;" }[s]
  ));
}
function byId(id) { return document.getElementById(id); }
function toInt(n, fallback = 0){ const v = Number(n); return Number.isFinite(v) ? v : fallback; }
function toDate(d){
  if (!d) return null;
  const v = new Date(d);
  return isNaN(v) ? null : v;
}
function arrFromMaybe(val){
  if (!val) return [];
  if (Array.isArray(val)) return val;
  if (typeof val === 'string') {
    if (val.includes(',')) return val.split(',').map(s=>s.trim()).filter(Boolean);
    return [val.trim()].filter(Boolean);
  }
  return [];
}
function normLabel(s){
  return String(s || '').toLowerCase().replace(/[\s\-]/g, '');
}



/** Update URL with applicant ID without navigating */
function pushApplicantId(id){
  const url = new URL(window.location.href);
  url.searchParams.set('applicant', String(id));
  history.pushState({ applicant: id }, '', url);
}
/** Remove applicant ID from URL (on modal close) */
function removeApplicantIdFromUrl(){
  const url = new URL(window.location.href);
  if (url.searchParams.has('applicant')) {
    url.searchParams.delete('applicant');
    history.replaceState({}, '', url);
  }
}

/* =========================================================
   Skeleton loader
========================================================= */
function renderSkeleton(count = 8) {
  if (!cardsGrid) return;
  cardsGrid.innerHTML = '';
  for (let i = 0; i < count; i++) {
    const col = document.createElement('div');
    col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';
    col.innerHTML = `
      <article class="card app-card h-100">
        <div class="ratio ratio-4x3 overflow-hidden shimmer"></div>
        <div class="card-body">
          <div class="shimmer" style="height:16px;width:70%;border-radius:6px;"></div>
          <div class="shimmer mt-2" style="height:12px;width:55%;border-radius:6px;"></div>
          <div class="shimmer mt-2" style="height:12px;width:62%;border-radius:6px;"></div>
          <div class="d-flex gap-2 mt-3">
            <div class="shimmer" style="height:26px;width:90px;border-radius:999px;"></div>
            <div class="shimmer" style="height:26px;width:120px;border-radius:999px;"></div>
          </div>
        </div>
        <div class="card-footer bg-white">
          <div class="shimmer" style="height:40px;width:100%;border-radius:10px;"></div>
        </div>
      </article>
    `;
    cardsGrid.appendChild(col);
  }
}

/* =========================================================
   Image loader (safe + mixed content aware)
========================================================= */
function setAvatar(imgEl, src, placeholder) {
  if (!imgEl) return;
  const fallback = placeholder || '../resources/img/avatar_placeholder.png';
  const isHttpsPage = location.protocol === 'https:';
  const cleanSrc = (src && typeof src === 'string') ? src.trim() : '';
  // Avoid mixed content on HTTPS pages
  const useSrc = (!cleanSrc || (isHttpsPage && cleanSrc.startsWith('http:'))) ? '' : cleanSrc;

  imgEl.loading = 'lazy';
  imgEl.decoding = 'async';
  imgEl.alt = imgEl.alt || 'Photo';
  imgEl.src = useSrc || fallback;
  imgEl.onerror = () => { imgEl.src = fallback; };
}

/* =========================================================
   Micro UI (pills etc.)
========================================================= */
function employmentPill(typeLabel) {
  const t = (typeLabel || '').toLowerCase();
  const cls = t.includes('full') ? 'emp-full' : 'emp-part';
  return `<span class="emp-pill ${cls}">${escapeHtml(typeLabel || '—')}</span>`;
}

/* =========================================================
   Init
========================================================= */
function initApp(){
  injectStyles();
  renderSkeleton(8);
  loadApplicants().then(() => {
    setupEventListeners();

    // Deep-link: auto-open modal if URL has ?applicant=ID
    const url = new URL(window.location.href);
    const idParam = url.searchParams.get('applicant');
    if (idParam) {
      const found = allApplicants.find(a => String(a.id) === String(idParam));
      if (found) showApplicantModal(found, { pushState: false });
    }
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}

/* =========================================================
   Data loading
========================================================= */
async function loadApplicants() {
  try {
    const response = await fetch('../includes/get_applicants.php', {
      cache: 'no-store',
      headers: { 'Accept': 'application/json' }
    });

    if (!response.ok) {
      throw new Error('Failed to load applicants: ' + response.status + ' ' + response.statusText);
    }

    const data = await response.json();

    allApplicants = Array.isArray(data) ? data : [];
    filteredApplicants = [...allApplicants];
    renderApplicants();
  } catch (error) {
    console.error('Error loading applicants:', error);
    if (cardsGrid) {
      cardsGrid.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading applicants. Please try again.</p></div>';
    }
  }
}

/* =========================================================
   Events
========================================================= */
function setupEventListeners() {
  if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      applyFilters();
    });
  }

  if (filtersForm) {
    filtersForm.addEventListener('submit', function(e) {
      e.preventDefault();
      applyFilters();
    });

    byId('clearSpecs')?.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelectorAll('#filtersForm input[name="specializations[]"]').forEach(cb => cb.checked = false);
      applyFilters();
    });

    byId('clearAvail')?.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelectorAll('#filtersForm input[name="availability[]"]').forEach(cb => cb.checked = false);
      applyFilters();
    });

    byId('clearLangs')?.addEventListener('click', (e) => {
      e.preventDefault();
      document.querySelectorAll('#filtersForm input[name="languages[]"]').forEach(cb => cb.checked = false);
      applyFilters();
    });

    byId('resetFilters')?.addEventListener('click', (e) => {
      e.preventDefault();
      searchForm?.reset();
      filtersForm.reset();
      filteredApplicants = [...allApplicants];
      currentPage = 1;
      renderApplicants();
    });

    byId('applyFilters')?.addEventListener('click', () => applyFilters());
  }
}

/* =========================================================
   Filtering + Sorting
========================================================= */
function applyFilters() {
  const formData    = searchForm ? new FormData(searchForm)   : new FormData();
  const filtersData = filtersForm ? new FormData(filtersForm) : new FormData();

  const searchQuery   = (formData.get('q') || '').toLowerCase().trim();
  const locationQuery = (formData.get('location') || '').toLowerCase().trim();
  const availableBy   = formData.get('available_by');

  const selectedSpecs = filtersData.getAll('specializations[]');
  const selectedAvail = filtersData.getAll('availability[]'); // employment type in UI
  const minExperience = parseInt(filtersData.get('min_experience')) || 0;
  const selectedLangs = filtersData.getAll('languages[]');
  const sortBy        = filtersData.get('sort');

  filteredApplicants = allApplicants.filter(applicant => {
    // Search by name or specialization (both string and array)
    if (searchQuery) {
      const nameMatch = String(applicant.full_name || '').toLowerCase().includes(searchQuery);
      const primarySpecMatch = String(applicant.specialization || '').toLowerCase().includes(searchQuery);
      const arraySpecMatch = Array.isArray(applicant.specializations)
        ? applicant.specializations.some(s => String(s).toLowerCase().includes(searchQuery))
        : false;
      if (!nameMatch && !primarySpecMatch && !arraySpecMatch) return false;
    }

    // Location (city)
    if (locationQuery && !String(applicant.location_city || '').toLowerCase().includes(locationQuery)) return false;

    // Available by date (kept in filters even if not shown on cards)
    if (availableBy) {
      const appDate = toDate(applicant.availability_date);
      const byDate  = toDate(availableBy);
      if (appDate && byDate && appDate > byDate) return false;
    }

    // Specialization filter using specializations array (fallback to primary string)
    if (selectedSpecs.length > 0) {
      const applicantSpecs = Array.isArray(applicant.specializations) ? applicant.specializations : [];
      const hasArrayMatch = selectedSpecs.some(sel => applicantSpecs.includes(sel));
      const hasPrimaryMatch = selectedSpecs.includes(applicant.specialization);
      if (!hasArrayMatch && !hasPrimaryMatch) return false;
    }

    // Employment type: accept both "Full Time"/"Part Time" (raw) and "Full-time"/"Part-time" (label)
    if (selectedAvail.length > 0) {
      const selectedNorms = selectedAvail.map(normLabel);
      const typeNorms = [applicant.employment_type, applicant.employment_type_raw].map(normLabel);
      const match = typeNorms.some(t => selectedNorms.includes(t));
      if (!match) return false;
    }

    // Experience
    if (toInt(applicant.years_experience) < minExperience) return false;

    // Languages (prefer array; fallback to CSV string)
    if (selectedLangs.length > 0) {
      const langArr = Array.isArray(applicant.languages_array)
        ? applicant.languages_array
        : String(applicant.languages || '').split(',').map(s => s.trim()).filter(Boolean);
      const hasLang = selectedLangs.some(lang => langArr.includes(lang));
      if (!hasLang) return false;
    }

    return true;
  });

  // Sort (robust guards)
  if (sortBy) {
    filteredApplicants.sort((a, b) => {
      switch (sortBy) {
        case 'availability_asc': {
          const da = toDate(a.availability_date);
          const db = toDate(b.availability_date);
          if (da && db) return da - db;
          if (da && !db) return -1;
          if (!da && db) return 1;
          return 0;
        }
        case 'experience_desc':
          return toInt(b.years_experience) - toInt(a.years_experience);
        case 'newest': {
          const ca = toDate(a.created_at);
          const cb = toDate(b.created_at);
          if (ca && cb) return cb - ca;
          if (cb && !ca) return 1;
          if (!cb && ca) return -1;
          return 0;
        }
        default:
          return 0;
      }
    });
  }

  currentPage = 1;
  renderApplicants();
}

/* =========================================================
   Rendering
========================================================= */
function renderApplicants() {
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex   = startIndex + itemsPerPage;
  const list       = filteredApplicants.slice(startIndex, endIndex);

  const totalResults = filteredApplicants.length;
  const startResult  = totalResults > 0 ? startIndex + 1 : 0;
  const endResult    = Math.min(endIndex, totalResults);
  if (resultsCount) {
    resultsCount.textContent = `Showing ${startResult}-${endResult} of ${totalResults} applicants`;
  }

  if (!cardsGrid) return;
  cardsGrid.innerHTML = '';

  if (list.length === 0) {
    cardsGrid.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No applicants found matching your criteria.</p></div>';
    if (pagination) pagination.innerHTML = '';
    return;
  }

  list.forEach(applicant => cardsGrid.appendChild(createApplicantCard(applicant)));
  renderPagination();
}

/* =========================================================
   Card (photo-top modern layout) — NO availability on card
   Entire card is clickable (+ keyboard) to open the modal.
========================================================= */
function createApplicantCard(applicant) {
  const col = document.createElement('div');
  col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';

  const yoe   = `${toInt(applicant.years_experience)} yrs`;

  const fullName       = escapeHtml(applicant.full_name || '—');
  const specialization = escapeHtml(applicant.specialization || '—');
  const employmentType = escapeHtml(applicant.employment_type || '—');
  const location       = `${escapeHtml(applicant.location_city || '—')}, ${escapeHtml(applicant.location_region || '—')}`;

  const html = `
    <article class="card app-card h-100 hover-lift clickable-card" role="button" tabindex="0" aria-label="View ${fullName} profile">
      <!-- Top photo -->
      <div class="ratio ratio-4x3 card-photo-wrap">
        <img class="card-photo" alt="${fullName}">
      </div>

      <!-- Body -->
      <div class="card-body">
        <h6 class="mb-1 app-name-title text-truncate" title="${fullName}">${fullName}</h6>
        <div class="text-muted small mb-2 text-truncate">
          ${specialization} • ${employmentType}
        </div>
        <div class="text-muted small mb-2 text-truncate">
          <i class="bi bi-geo-alt text-danger me-1"></i>${location}
        </div>
        <div class="d-flex flex-wrap gap-2">
          <span class="meta-pill"><i class="bi bi-award me-1"></i>${yoe}</span>
        </div>
      </div>

      <!-- Footer: single action -->
      <div class="card-footer bg-white">
        <button class="btn btn-outline-dark w-100 view-profile-btn" data-applicant-id="${applicant.id}">
          <i class="bi bi-person-badge me-1"></i> View Profile
        </button>
      </div>
    </article>
  `;
  col.innerHTML = html;

  // Photo
  const img = col.querySelector('.card-photo');
  setAvatar(img, applicant.photo_url, applicant.photo_placeholder);

  // View Profile button
  const viewBtn = col.querySelector('.view-profile-btn');
  viewBtn.addEventListener('click', (e) => {
    e.preventDefault();
    pushApplicantId(applicant.id);
    showApplicantModal(applicant);
  });

  // Entire card is clickable (and keyboard-accessible)
  const card = col.querySelector('.clickable-card');
  const open = () => {
    pushApplicantId(applicant.id);
    showApplicantModal(applicant);
  };
  card.addEventListener('click', (e) => {
    if (e.target.closest('.view-profile-btn')) return;
    open();
  });
  card.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      open();
    }
  });

  return col;
}

/* =========================================================
   Pagination
========================================================= */
function renderPagination() {
  if (!pagination) return;
  const totalPages = Math.ceil(filteredApplicants.length / itemsPerPage);
  pagination.innerHTML = '';
  if (totalPages <= 1) return;

  const ul = document.createElement('ul');
  ul.className = 'pagination justify-content-center';

  const addPageItem = (label, disabled, handler, isActive=false) => {
    const li = document.createElement('li');
    li.className = `page-item ${disabled ? 'disabled' : ''} ${isActive ? 'active' : ''}`;
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.innerHTML = label;
    if (!disabled) a.addEventListener('click', (e) => { e.preventDefault(); handler && handler(); });
    li.appendChild(a);
    ul.appendChild(li);
  };

  addPageItem('&laquo;', currentPage === 1, () => { currentPage--; renderApplicants(); });

  const windowSize = 5;
  let start = Math.max(1, currentPage - Math.floor(windowSize/2));
  let end   = Math.min(totalPages, start + windowSize - 1);
  if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

  for (let i = start; i <= end; i++) {
    addPageItem(String(i), false, () => { currentPage = i; renderApplicants(); }, i === currentPage);
  }

  addPageItem('&raquo;', currentPage === totalPages, () => { currentPage++; renderApplicants(); });

  pagination.appendChild(ul);
}

/* =========================================================
   Modal (Profile) — add "Hire Me" inside modal footer
   + City & Region only in header
   + Close profile then open booking (smooth handoff)
   + Clean URL when profile modal closes
========================================================= */
function ensureModalFooterAndHire(applicant){
  const modalEl = byId('applicantModal');
  if (!modalEl) return;

  let footer = modalEl.querySelector('.modal-footer');
  if (!footer) {
    footer = document.createElement('div');
    footer.className = 'modal-footer d-flex justify-content-between';
    footer.innerHTML = `
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      <div class="d-flex gap-2">
        <button type="button" class="btn btn-brand text-white" id="modalHireBtn">
          <i class="bi bi-calendar2-check me-1"></i> Hire Me
        </button>
      </div>
    `;
    modalEl.querySelector('.modal-content')?.appendChild(footer);
  }

  modalEl.removeEventListener('hidden.bs.modal', onProfileHiddenCleanUrl);
  modalEl.addEventListener('hidden.bs.modal', onProfileHiddenCleanUrl);

  const hireBtn = footer.querySelector('#modalHireBtn');
  if (hireBtn) {
    hireBtn.onclick = () => {
      const profileModal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
      const afterHide = () => {
        modalEl.removeEventListener('hidden.bs.modal', afterHide);
        launchBooking(applicant);
      };
      modalEl.addEventListener('hidden.bs.modal', afterHide, { once: true });
      profileModal.hide();
    };
  }
}

function onProfileHiddenCleanUrl(){
  removeApplicantIdFromUrl();
}

function showApplicantModal(applicant, options = { pushState: true }) {
  const modalEl = byId('applicantModal');
  if (!modalEl) return;

  const prefEl = byId('prefLocValue');
  if (prefEl) {
    const arr = Array.isArray(applicant.preferred_locations) ? applicant.preferred_locations : [];
    prefEl.innerHTML = arr.length
      ? arr.map(x=>`<span class="badge text-bg-light border me-1 mb-1">${escapeHtml(x)}</span>`).join('')
      : '—';
  }
  const eduEl = byId('eduValue'); if (eduEl) eduEl.textContent = applicant.education_display || applicant.education_level || '—';

  const avatar = byId('avatar');
  if (avatar) {
    if (applicant.photo_url) {
      avatar.style.backgroundImage = `url(${applicant.photo_url})`;
      avatar.style.backgroundSize = 'cover';
      avatar.textContent = '';
    } else {
      avatar.style.backgroundImage = '';
    }
  }
  const nameEl = byId('name'); if (nameEl) nameEl.textContent = applicant.full_name || '—';
  const primaryRoleEl = byId('primaryRole'); if (primaryRoleEl) primaryRoleEl.textContent = applicant.specialization || '—';
  const yoeBadgeEl = byId('yoeBadge'); if (yoeBadgeEl) yoeBadgeEl.textContent = `${toInt(applicant.years_experience)} yrs`;

  const availabilityLineEl = byId('availabilityLine');
  if (availabilityLineEl) {
    availabilityLineEl.textContent = `${applicant.location_city || '—'}, ${applicant.location_region || '—'}`;
  }

  const chipsContainer = byId('chipsContainer');
  if (chipsContainer) {
    const chips = arrFromMaybe(applicant.specializations?.length ? applicant.specializations : applicant.specialization);
    chipsContainer.innerHTML = chips.length
      ? chips.map(s => `<span class="chip">${escapeHtml(s)}</span>`).join('')
      : `<span class="chip">${escapeHtml(applicant.specialization || '—')}</span>`;
  }

  const cityEl = byId('cityValue'); if (cityEl) cityEl.textContent = applicant.location_city || '—';
  const regionEl = byId('regionValue'); if (regionEl) regionEl.textContent = applicant.location_region || '—';
  const yoeEl = byId('yoeValue'); if (yoeEl) yoeEl.textContent = `${toInt(applicant.years_experience)} years`;
  const employmentEl = byId('employmentValue'); if (employmentEl) employmentEl.textContent = applicant.employment_type || '—';
  const availEl = byId('availValue'); if (availEl) availEl.textContent = applicant.availability_date || '—';
  const langEl = byId('langValue');
  if (langEl) {
    const langs = arrFromMaybe(applicant.languages);
    langEl.textContent = langs.length ? langs.join(', ') : (applicant.languages || '—');
  }

  ensureModalFooterAndHire(applicant);

  const profileModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  profileModal.show();
  modalEl.dataset.applicant = JSON.stringify(applicant);

  if (options.pushState) pushApplicantId(applicant.id);
}
window.showApplicantModal = showApplicantModal;


/* Booking: helper to enforce button types in some templates */
(function enforceButtonTypes(){
  ['bkSubmit','bkNext','bkBack'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.setAttribute('type','button');
  });
})();

/* =========================================================
   Booking (Hire)
========================================================= */
function launchBooking(applicant){
  window._lastApplicantForBooking = applicant;
  const modalEl = byId('bookingModal');
  if (!modalEl || !window.bootstrap?.Modal) return;

  const bkAvatar = modalEl.querySelector('#bkAvatar');
  if (bkAvatar) {
    bkAvatar.style.backgroundImage = applicant.photo_url ? `url('${applicant.photo_url}')` : '';
    bkAvatar.style.backgroundSize = applicant.photo_url ? 'cover' : '';
    bkAvatar.textContent = applicant.photo_url ? '' : '';
  }
  const bkName = modalEl.querySelector('#bkName'); if (bkName) bkName.textContent = applicant.full_name || '—';
  const bkMeta = modalEl.querySelector('#bkMeta'); if (bkMeta) bkMeta.textContent = `${applicant.specialization || '—'} • ${applicant.location_city || '—'}, ${applicant.location_region || '—'}`;

  const panes = modalEl.querySelectorAll('[data-step-pane]');
  panes.forEach(p => p.classList.toggle('d-none', p.dataset.stepPane !== '1'));
  modalEl.querySelectorAll('.stepper .step').forEach((s,i)=>{
    s.classList.toggle('active', i===0);
    s.classList.toggle('completed', false);
  });

  modalEl.querySelectorAll('.oval-tag.active').forEach(el=>el.classList.remove('active'));
  modalEl.querySelectorAll('input[name="apptType"]').forEach(inp => inp.checked = false);
  ['bkDate','bkTime','bkFirstName','bkLastName','bkPhone','bkEmail','bkAddress'].forEach(id => {
    const e = modalEl.querySelector('#'+id); if (e) e.value = '';
  });
  const summary = modalEl.querySelector('#bkSummary'); if (summary) summary.innerHTML = '';
  const qr = modalEl.querySelector('#bkQR'); if (qr) qr.innerHTML = '';

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

/* =========================================================
   Styles injection (modern professional cards)
========================================================= */
function injectStyles(){
  if (document.getElementById('app-modern-card-styles')) return;
  const css = `
    :root{
      --brand-red:#c40000;
      --card-border:#e6e9ef;
      --muted:#6b7280;
    }

    .hover-lift{ transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease; }
    .hover-lift:hover{ transform:translateY(-3px); box-shadow:0 12px 28px rgba(0,0,0,.12); border-color:#dee3eb; }

    .app-card{
      border:1px solid var(--card-border);
      border-radius:16px;
      overflow:hidden; /* round the top photo */
      box-shadow:0 2px 8px rgba(0,0,0,.06);
      background:#fff;
      cursor:pointer;
      outline: none;
    }
    .app-card:focus{ box-shadow:0 0 0 3px rgba(196,0,0,.25), 0 12px 28px rgba(0,0,0,.12); }

    .card-photo-wrap{ background:#f5f6f8; position:relative; }
    .card-photo{ width:100%; height:100%; object-fit:cover; display:block; }
    .card-photo-wrap::after{
      content:'';
      position:absolute; inset:0;
      background:linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,.02));
      pointer-events:none;
    }

    .app-name-title{ font-weight:800; }
    .emp-pill{
      display:inline-block; border-radius:999px; padding:.26rem .56rem;
      font-weight:700; font-size:.78rem; border:1px solid transparent;
    }
    .emp-pill.emp-full{ background:#eaf7ef; color:#15803d; border-color:#cce9d7; }
    .emp-pill.emp-part{ background:#e6effd; color:#1d4ed8; border-color:#cfe0fb; }

    .meta-pill{
      display:inline-flex; align-items:center; gap:.35rem;
      padding:.32rem .6rem; border-radius:999px; font-size:.78rem; font-weight:700;
      color:#374151; background:#f3f4f6; border:1px solid #e5e7eb;
    }

    .btn-brand{ background:var(--brand-red); border-color:var(--brand-red); }
    .btn-brand:hover{ filter:brightness(.92); }

    /* Shimmer */
    .shimmer{ position:relative; overflow:hidden; background-color:rgba(0,0,0,.06); }
    .shimmer::after{
      content:''; position:absolute; inset:0; transform:translateX(-100%);
      background-image:linear-gradient(90deg, rgba(255,255,255,0) 0, rgba(255,255,255,.45) 50%, rgba(255,255,255,0) 100%);
      animation:shimmer 1.5s infinite;
    }
    @keyframes shimmer{ 100% { transform: translateX(100%); } }
  `;
  const style = document.createElement('style');
  style.id = 'app-modern-card-styles';
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
}