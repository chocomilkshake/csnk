// app.js — Client listing UX (modern cards)
// v2.3 — photo-top cards, clear hierarchy, soft pills, safe image loader, same working logic
console.log('app.js loaded successfully - v2.3');

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
    { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[s]
  ));
}
function byId(id) { return document.getElementById(id); }
function toInt(n, fallback = 0){ const v = Number(n); return Number.isFinite(v) ? v : fallback; }
function toDate(d){ const v = new Date(d); return isNaN(v) ? null : v; }

function arrFromMaybe(val){
  if (!val) return [];
  if (Array.isArray(val)) return val;
  if (typeof val === 'string') {
    if (val.includes(',')) return val.split(',').map(s=>s.trim()).filter(Boolean);
    return [val.trim()].filter(Boolean);
  }
  return [];
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
      <article class="card app-card-bs h-100">
        <div class="ratio ratio-4x3 overflow-hidden shimmer"></div>
        <div class="card-body">
          <div class="shimmer" style="height:16px;width:70%;border-radius:6px;"></div>
          <div class="shimmer mt-2" style="height:12px;width:50%;border-radius:6px;"></div>
          <div class="shimmer mt-2" style="height:12px;width:60%;border-radius:6px;"></div>
          <div class="d-flex gap-2 mt-3">
            <div class="shimmer" style="height:26px;width:90px;border-radius:999px;"></div>
            <div class="shimmer" style="height:26px;width:130px;border-radius:999px;"></div>
          </div>
        </div>
        <div class="card-footer bg-white d-flex gap-2">
          <div class="shimmer" style="height:36px;width:100%;border-radius:10px;"></div>
          <div class="shimmer" style="height:36px;width:100%;border-radius:10px;"></div>
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
   Pills / badges
========================================================= */
function availabilityMeta(dateStr) {
  const d = toDate(dateStr);
  if (!d) return { text: 'Avail: —', cls: 'meta-pill' };
  const fmt = d.toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' });
  const today = new Date(); today.setHours(0,0,0,0);
  const dd = new Date(d);   dd.setHours(0,0,0,0);
  const isNow = dd <= today;
  return { text: isNow ? 'Avail: Now' : `Avail: ${fmt}`, cls: isNow ? 'meta-pill success' : 'meta-pill warn' };
}

function employmentPill(typeLabel) {
  const t = (typeLabel || '').toLowerCase();
  const cls = t.includes('full') ? 'pill-full' : 'pill-part';
  return `<span class="emp-pill ${cls}">${escapeHtml(typeLabel || '—')}</span>`;
}

/* =========================================================
   Init
========================================================= */
function initApp(){
  injectStyles();
  renderSkeleton(8);
  loadApplicants().then(setupEventListeners);
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
  const selectedAvail = filtersData.getAll('availability[]');
  const minExperience = parseInt(filtersData.get('min_experience')) || 0;
  const selectedLangs = filtersData.getAll('languages[]');
  const sortBy        = filtersData.get('sort');

  filteredApplicants = allApplicants.filter(applicant => {
    // Search by name or specialization
    if (searchQuery &&
        !String(applicant.full_name).toLowerCase().includes(searchQuery) &&
        !String(applicant.specialization).toLowerCase().includes(searchQuery)
    ) return false;

    // Location (city)
    if (locationQuery && !String(applicant.location_city).toLowerCase().includes(locationQuery)) return false;

    // Available by date (keep; you can ignore in UI if not needed)
    if (availableBy && toDate(applicant.availability_date) > toDate(availableBy)) return false;

    // Specialization exact match from filter
    if (selectedSpecs.length > 0 && !selectedSpecs.includes(applicant.specialization)) return false;

    // Employment type
    if (selectedAvail.length > 0 && !selectedAvail.includes(applicant.employment_type)) return false;

    // Experience
    if (toInt(applicant.years_experience) < minExperience) return false;

    // Languages
    if (selectedLangs.length > 0) {
      const applicantLangs = String(applicant.languages || '').split(',').map(s=>s.trim()).filter(Boolean);
      if (!selectedLangs.some(lang => applicantLangs.includes(lang))) return false;
    }

    return true;
  });

  // Sort
  if (sortBy) {
    filteredApplicants.sort((a, b) => {
      switch (sortBy) {
        case 'availability_asc':
          return toDate(a.availability_date) - toDate(b.availability_date);
        case 'experience_desc':
          return toInt(b.years_experience) - toInt(a.years_experience);
        case 'newest':
          return toDate(b.created_at) - toDate(a.created_at);
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
   Card (photo-top modern layout)
========================================================= */
function createApplicantCard(applicant) {
  const col = document.createElement('div');
  col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';

  const yoe   = `${toInt(applicant.years_experience)} yrs`;
  const avail = availabilityMeta(applicant.availability_date);

  const fullName       = escapeHtml(applicant.full_name || '—');
  const specialization = escapeHtml(applicant.specialization || '—');
  const employmentType = escapeHtml(applicant.employment_type || '—');
  const location       = `${escapeHtml(applicant.location_city || '—')}, ${escapeHtml(applicant.location_region || '—')}`;

  const html = `
    <article class="card app-card-bs h-100 hover-lift">
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
          <span class="${avail.cls}"><i class="bi bi-calendar-event me-1"></i>${escapeHtml(avail.text)}</span>
        </div>
      </div>

      <!-- Footer actions -->
      <div class="card-footer bg-white d-flex gap-2">
        <button class="btn btn-outline-dark w-100 view-profile-btn" data-applicant-id="${applicant.id}">
          <i class="bi bi-person-badge me-1"></i> View Profile
        </button>
        <button class="btn btn-brand text-white w-100 hire-btn" data-applicant-id="${applicant.id}">
          <i class="bi bi-calendar2-check me-1"></i> Hire Me
        </button>
      </div>
    </article>
  `;
  col.innerHTML = html;

  // Photo
  const img = col.querySelector('.card-photo');
  setAvatar(img, applicant.photo_url, applicant.photo_placeholder);

  // Actions
  col.querySelector('.view-profile-btn')?.addEventListener('click', (e) => {
    e.preventDefault();
    showApplicantModal(applicant);
  });

  col.querySelector('.hire-btn')?.addEventListener('click', (e) => {
    e.preventDefault();
    launchBooking(applicant);  // safe no-op if booking modal doesn't exist
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
   Modal (Profile) — City & Region only in header
========================================================= */
function showApplicantModal(applicant) {
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

  // City & Region only (no date)
  const availabilityLineEl = byId('availabilityLine');
  if (availabilityLineEl) {
    availabilityLineEl.textContent = `${applicant.location_city || '—'}, ${applicant.location_region || '—'}`;
  }

  // Specializations chips (if you want to show inside modal)
  const chipsContainer = byId('chipsContainer');
  if (chipsContainer) {
    const chips = arrFromMaybe(applicant.specializations?.length ? applicant.specializations : applicant.specialization);
    chipsContainer.innerHTML = chips.length
      ? chips.map(s => `<span class="chip">${escapeHtml(s)}</span>`).join('')
      : `<span class="chip">${escapeHtml(applicant.specialization || '—')}</span>`;
  }

  // Basic info
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

  const modalEl = byId('applicantModal');
  if (modalEl && window.bootstrap?.Modal) {
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    modalEl.dataset.applicant = JSON.stringify(applicant);
  }
}
window.showApplicantModal = showApplicantModal;

/* =========================================================
   Booking (Hire Me) — safe no-op if modal missing
========================================================= */
function launchBooking(applicant){
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

  // Reset stepper visuals to step 1 (if present)
  const panes = modalEl.querySelectorAll('[data-step-pane]');
  panes.forEach(p => p.classList.toggle('d-none', p.dataset.stepPane !== '1'));
  modalEl.querySelectorAll('.stepper .step').forEach((s,i)=>{
    s.classList.toggle('active', i===0);
    s.classList.toggle('completed', false);
  });

  // Clear previous inputs
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
   Styles injection (photo-top card design)
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

    .app-card-bs{
      border:1px solid var(--card-border);
      border-radius:16px;
      overflow:hidden; /* round the image corners */
      box-shadow:0 2px 8px rgba(0,0,0,.06);
      background:#fff;
    }
    .card-photo-wrap{ background:#f5f6f8; }
    .card-photo{ width:100%; height:100%; object-fit:cover; display:block; }

    .app-name-title{ font-weight:800; }
    .emp-pill{
      display:inline-block; border-radius:999px; padding:.26rem .56rem;
      font-weight:700; font-size:.78rem; border:1px solid transparent;
    }
    .emp-pill.pill-full{ background:#eaf7ef; color:#15803d; border-color:#cce9d7; }
    .emp-pill.pill-part{ background:#e6effd; color:#1d4ed8; border-color:#cfe0fb; }

    .meta-pill{
      display:inline-flex; align-items:center; gap:.35rem;
      padding:.32rem .6rem; border-radius:999px; font-size:.78rem; font-weight:700;
      color:#374151; background:#f3f4f6; border:1px solid #e5e7eb;
    }
    .meta-pill.success{ background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
    .meta-pill.warn{ background:#fffbeb; color:#b45309; border-color:#fde68a; }

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