// app.js — Client listing UX (modern cards)
// v2.2 — refined spacing, chips, pills, availability badge, cleaner pagination
console.log('app.js loaded successfully - v2.2');

let allApplicants = [];
let filteredApplicants = [];
let currentPage = 1;
const itemsPerPage = 12;

// DOM elements
const searchForm   = document.getElementById('searchForm');
const filtersForm  = document.getElementById('filtersForm');
const cardsGrid    = document.getElementById('cardsGrid');
const resultsCount = document.getElementById('resultsCount');
const pagination   = document.getElementById('pagination');

// ---------- Helpers ----------
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
    // split by comma if needed
    if (val.includes(',')) return val.split(',').map(s=>s.trim()).filter(Boolean);
    return [val.trim()];
  }
  return [];
}

// Very light skeleton UI while loading
function renderSkeleton(count = 8) {
  cardsGrid.innerHTML = '';
  for (let i = 0; i < count; i++) {
    const col = document.createElement('div');
    col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';
    col.innerHTML = `
      <article class="app-card h-100">
        <div class="d-flex align-items-center">
          <div class="app-avatar shimmer"></div>
          <div class="flex-grow-1 ms-3">
            <div class="shimmer" style="height:14px;width:70%;border-radius:6px;"></div>
            <div class="shimmer mt-2" style="height:12px;width:40%;border-radius:6px;"></div>
          </div>
        </div>
        <div class="shimmer mt-3" style="height:12px;width:65%;border-radius:6px;"></div>
        <div class="shimmer mt-2" style="height:12px;width:50%;border-radius:6px;"></div>
        <div class="shimmer mt-2" style="height:12px;width:45%;border-radius:6px;"></div>
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="shimmer" style="height:26px;width:38%;border-radius:999px;"></div>
          <div class="shimmer" style="height:36px;width:120px;border-radius:10px;"></div>
        </div>
      </article>
    `;
    cardsGrid.appendChild(col);
  }
}

// Lazy-load images with fallback to placeholder
function setAvatar(imgEl, src, placeholder) {
  if (!imgEl) return;
  const fallback = placeholder || '../resources/img/avatar_placeholder.png';
  imgEl.loading = 'lazy';
  imgEl.decoding = 'async';
  imgEl.src = src || fallback;
  imgEl.onerror = () => { imgEl.src = fallback; };
}

// Chips rendering (pills)
function renderChips(values = [], maxVisible = 3) {
  const clean = (values || []).map(String).map(v => v.trim()).filter(Boolean);
  if (!clean.length) return '';
  const shown = clean.slice(0, maxVisible).map(v => `<span class="chip">${escapeHtml(v)}</span>`).join('');
  const more  = clean.length > maxVisible ? `<span class="chip chip-more">+${clean.length - maxVisible}</span>` : '';
  return shown + more;
}

// Language chips (lighter)
function renderLangPills(values = [], maxVisible = 2) {
  const clean = (values || []).map(String).map(v => v.trim()).filter(Boolean);
  if (!clean.length) return '';
  const shown = clean.slice(0, maxVisible).map(v => `<span class="lang-pill">${escapeHtml(v)}</span>`).join('');
  const more  = clean.length > maxVisible ? `<span class="lang-pill more">+${clean.length - maxVisible}</span>` : '';
  return shown + more;
}

// Availability badge class + text
function availabilityMeta(dateStr) {
  const d = toDate(dateStr);
  if (!d) return { text: 'Availability TBD', cls: 'badge-soft-secondary' };
  const today = new Date(); today.setHours(0,0,0,0);
  const dd = new Date(d);   dd.setHours(0,0,0,0);
  const isAvailable = dd <= today;

  const fmt = d.toLocaleDateString(undefined, { month:'short', day:'numeric', year:'numeric' });
  return {
    text: isAvailable ? 'Available now' : `Available ${fmt}`,
    cls:  isAvailable ? 'badge-soft-success' : 'badge-soft-warning'
  };
}

// Employment pill
function employmentPill(typeLabel) {
  const t = (typeLabel || '').toLowerCase();
  const cls = t.includes('full') ? 'pill-full' : 'pill-part';
  return `<span class="pill ${cls}">${escapeHtml(typeLabel || '—')}</span>`;
}

// ---------- Init ----------
function initApp(){
  injectStyles();   // UI styles for modern cards
  renderSkeleton(8);
  loadApplicants().then(() => {
    setupEventListeners();
  });
}

// Run immediately if DOM already loaded, otherwise wait for event
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initApp);
} else {
  initApp();
}

// ---------- Data loading ----------
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
    cardsGrid.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading applicants. Please try again.</p></div>';
  }
}

// ---------- Event listeners ----------
function setupEventListeners() {
  // Search form
  if (searchForm) {
    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      applyFilters();
    });
  }

  // Filters form
  if (filtersForm) {
    filtersForm.addEventListener('submit', function(e) {
      e.preventDefault();
      applyFilters();
    });

    // Clear quick-actions
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

// ---------- Filtering + Sorting ----------
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
    // Search: name or specialization
    if (searchQuery &&
        !String(applicant.full_name).toLowerCase().includes(searchQuery) &&
        !String(applicant.specialization).toLowerCase().includes(searchQuery)
    ) return false;

    // Location
    if (locationQuery && !String(applicant.location_city).toLowerCase().includes(locationQuery)) return false;

    // Available by date
    if (availableBy && toDate(applicant.availability_date) > toDate(availableBy)) return false;

    // Specializations
    if (selectedSpecs.length > 0 && !selectedSpecs.includes(applicant.specialization)) return false;

    // Availability (employment type)
    if (selectedAvail.length > 0 && !selectedAvail.includes(applicant.employment_type)) return false;

    // Experience
    if (toInt(applicant.years_experience) < minExperience) return false;

    // Languages
    if (selectedLangs.length > 0) {
      const applicantLangs = String(applicant.languages || '').split(',').map(s=>s.trim()).filter(Boolean);
      const hasMatchingLang = selectedLangs.some(lang => applicantLangs.includes(lang));
      if (!hasMatchingLang) return false;
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

// ---------- Rendering ----------
function renderApplicants() {
  const startIndex = (currentPage - 1) * itemsPerPage;
  const endIndex   = startIndex + itemsPerPage;
  const list       = filteredApplicants.slice(startIndex, endIndex);

  // Update results count
  const totalResults = filteredApplicants.length;
  const startResult  = totalResults > 0 ? startIndex + 1 : 0;
  const endResult    = Math.min(endIndex, totalResults);
  if (resultsCount) {
    resultsCount.textContent = `Showing ${startResult}-${endResult} of ${totalResults} applicants`;
  }

  // Clear grid
  cardsGrid.innerHTML = '';

  if (list.length === 0) {
    cardsGrid.innerHTML = '<div class="col-12 text-center"><p class="text-muted">No applicants found matching your criteria.</p></div>';
    pagination.innerHTML = '';
    return;
  }

  // Render cards
  list.forEach(applicant => cardsGrid.appendChild(createApplicantCard(applicant)));

  // Render pagination
  renderPagination();
}

// Create applicant card (refined modern layout)
function createApplicantCard(applicant) {
  const col = document.createElement('div');
  col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';

  const avail = availabilityMeta(applicant.availability_date);
  const yoe   = `${toInt(applicant.years_experience)} yrs`;
  const age   = applicant.age ? ` • Age ${toInt(applicant.age)}` : '';

  const specList = arrFromMaybe(applicant.specializations?.length ? applicant.specializations : applicant.specialization);
  const langs    = arrFromMaybe(applicant.languages);

  // Fallback if languages is comma string in your API
  const langsFromString = !langs.length && typeof applicant.languages === 'string'
    ? applicant.languages.split(',').map(s=>s.trim()).filter(Boolean)
    : langs;

  const specChips = renderChips(specList, 3);
  const langsChips = renderLangPills(langsFromString, 2);

  const html = `
    <article class="app-card h-100 hover-lift">
      <!-- Header -->
      <div class="d-flex align-items-center">
        <div class="app-avatar">
          <img class="app-avatar-img" alt="${escapeHtml(applicant.full_name)}">
        </div>
        <div class="ms-3 flex-grow-1 min-w-0">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="app-name text-truncate" title="${escapeHtml(applicant.full_name)}">${escapeHtml(applicant.full_name)}</div>
            ${employmentPill(applicant.employment_type)}
            <span class="pill pill-yoe">${escapeHtml(yoe)}</span>
          </div>
          <div class="app-meta small mt-1 text-truncate">
            <i class="bi bi-geo-alt me-1"></i>${escapeHtml(applicant.location_city)}, ${escapeHtml(applicant.location_region)}
          </div>
        </div>
      </div>

      <!-- Body -->
      <div class="mt-3 small text-muted">
        <div><i class="bi bi-star-fill me-1"></i>${escapeHtml(yoe)} experience${age}</div>
      </div>

      ${specChips ? `<div class="app-chips mt-2">${specChips}</div>` : ''}

      ${langsChips ? `<div class="mt-2">${langsChips}</div>` : ''}

      <!-- Footer -->
      <div class="d-flex justify-content-between align-items-center mt-3">
        <span class="badge ${avail.cls}">${escapeHtml(avail.text)}</span>
        <button class="btn btn-sm btn-outline-dark view-profile-btn" data-applicant-id="${applicant.id}">
          View Profile
        </button>
      </div>
    </article>
  `;

  col.innerHTML = html;

  // Avatar
  const img = col.querySelector('.app-avatar-img');
  setAvatar(img, applicant.photo_url, applicant.photo_placeholder);

  // Hook view button
  const viewBtn = col.querySelector('.view-profile-btn');
  viewBtn.addEventListener('click', () => showApplicantModal(applicant));

  return col;
}

// Render pagination (simple, centered, fixed HTML)
function renderPagination() {
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

// ---------- Modal (Profile) — City & Region only in header ----------
function showApplicantModal(applicant) {
  // Populate modal with applicant data
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

  // Specializations chips
  const chipsContainer = byId('chipsContainer');
  if (chipsContainer) {
    const chips = renderChips(arrFromMaybe(applicant.specializations?.length ? applicant.specializations : applicant.specialization), 6);
    chipsContainer.innerHTML = chips || `<span class="chip">${escapeHtml(applicant.specialization || '—')}</span>`;
  }

  // Basic info
  const cityEl = byId('cityValue'); if (cityEl) cityEl.textContent = applicant.location_city || '—';
  const regionEl = byId('regionValue'); if (regionEl) regionEl.textContent = applicant.location_region || '—';
  const yoeEl = byId('yoeValue'); if (yoeEl) yoeEl.textContent = `${toInt(applicant.years_experience)} years`;
  const employmentEl = byId('employmentValue'); if (employmentEl) employmentEl.textContent = applicant.employment_type || '—';
  const availEl = byId('availValue'); if (availEl) availEl.textContent = applicant.availability_date || '—';
  const langEl = byId('langValue'); if (langEl) {
    const langs = arrFromMaybe(applicant.languages);
    langEl.textContent = langs.length ? langs.join(', ') : (applicant.languages || '—');
  }

  // Show modal (Bootstrap 5)
  const modalEl = byId('applicantModal');
  if (modalEl) {
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
    modalEl.dataset.applicant = JSON.stringify(applicant);
  }
}

// Expose for other scripts
window.showApplicantModal = showApplicantModal;

// --- Style injection (cards + shimmer + pills) ---
function injectStyles(){
  if (document.getElementById('app-modern-card-styles')) return;
  const css = `
    :root{
      --brand-red:#c40000;
      --brand-black:#111;
      --card-border:#e9ecf1;
      --chip-bg:#fafafa;
      --chip-border:#eee;
      --muted:#6b7280;
    }

    /* Card */
    .app-card{
      border:1px solid var(--card-border);
      border-radius:16px;
      background:#fff;
      padding:1rem;
      box-shadow:0 2px 8px rgba(0,0,0,.05);
      transition:transform .12s ease, box-shadow .12s ease, border-color .12s ease;
    }
    .hover-lift:hover{
      transform:translateY(-3px);
      box-shadow:0 10px 24px rgba(0,0,0,.12);
      border-color:#e2e6ed;
    }

    /* Avatar */
    .app-avatar{
      width:56px;height:56px;border-radius:50%;
      background:#f3f4f6;border:1px solid #fff;
      display:grid;place-items:center;overflow:hidden;flex-shrink:0;
      box-shadow:inset 0 0 0 1px rgba(0,0,0,.06);
    }
    .app-avatar-img{ width:100%; height:100%; object-fit:cover; display:block; }

    /* Text + Pills */
    .app-name{ font-weight:800; font-size:1rem; line-height:1; }
    .app-meta{ color:var(--muted); }
    .pill{
      display:inline-block; border-radius:999px; font-weight:700; font-size:.78rem;
      padding:.26rem .56rem; border:1px solid transparent;
    }
    .pill-yoe{ border-color:#ef4444; color:#991b1b; background:#fff; }
    .pill-full{ background:#eaf7ef; color:#15803d; border-color:#cce9d7; }
    .pill-part{ background:#e6effd; color:#1d4ed8; border-color:#cfe0fb; }

    /* Chips */
    .app-chips .chip{
      display:inline-block; padding:.38rem .66rem; border-radius:999px;
      font-weight:700; font-size:.8rem; color:#111;
      background:var(--chip-bg); border:1px solid var(--chip-border);
      margin:.125rem .25rem .125rem 0;
    }
    .app-chips .chip-more{ background:#111; color:#fff; border-color:#111; }

    /* Languages */
    .lang-pill{
      display:inline-block; padding:.28rem .6rem; border-radius:999px;
      font-weight:600; font-size:.78rem; color:#374151;
      background:#f3f4f6; border:1px solid #e5e7eb; margin:.125rem .25rem .125rem 0;
    }
    .lang-pill.more{ background:#e5e7eb; }

    /* Availability badges */
    .badge-soft-success{ background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; font-weight:700; }
    .badge-soft-warning{ background:#fffbeb; color:#b45309; border:1px solid #fde68a; font-weight:700; }
    .badge-soft-secondary{ background:#f3f4f6; color:#374151; border:1px solid #e5e7eb; font-weight:700; }
    .badge-soft-success, .badge-soft-warning, .badge-soft-secondary{
      padding:.35rem .6rem; border-radius:999px; font-size:.78rem;
    }

    /* Buttons */
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