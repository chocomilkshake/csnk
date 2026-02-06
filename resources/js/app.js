// app.js — Client listing UX (photo-top modern cards)
// v2.9 — approved applicants hidden server-side; cards clickable + modern, NO availability on cards, Hire inside modal (closes then opens booking), pushState with ID + clean URL on close
//        + Auto Rumble: newest pinned on top, remaining shuffle on each refresh (client-side)
console.log('app.js loaded successfully - v2.9');

/* =========================================================
   State
========================================================= */
let allApplicants = [];
let filteredApplicants = [];
let currentPage = 1;
const itemsPerPage = 12;

/* =========================================================
   Auto Rumble (client-side unbiased rotation)
   - Keeps newest applicants on top
   - Randomizes the rest on every page load/refresh
   - Applies only when sort === 'newest' (default) to not break other sorts
========================================================= */
const RUMBLE_ENABLED = true;                 // Master switch
const RUMBLE_ONLY_ON_SORT_NEWEST = true;     // Respect user sorting; rumble only when 'Newest'
const PIN_NEWEST_COUNT = 4;                  // Keep the first N newest items pinned at the top (page-level)
const PIN_WITHIN_DAYS = 0;                   // Alternative: if > 0, pin all created within X days (overrides count)

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

// ---- CONFIG: set your app root path here, including leading and trailing slashes ----
// NOTE: this must match your server mount (e.g., '/csnk-1/' for this project)
const APP_BASE = '/csnk-1/'; // <-- CHANGE if your app is mounted elsewhere (e.g., '/')

function normalizeSlashes(url) {
  return String(url || '').replace(/\\/g, '/').trim();
}
function isAbsoluteUrl(u) { return /^https?:\/\//i.test(u); }

function absoluteFromAppRoot(path) {
  if (!path) return '';
  const p = normalizeSlashes(path);
  if (isAbsoluteUrl(p)) return p;

  // Ensure exactly one slash between origin, base and path
  const base = APP_BASE.replace(/\/+$/,'');        // '/csnk'
  const rel  = p.replace(/^\/+/,'');               // 'admin/uploads/video/trial1.mp4'
  return `${location.origin}${base}/${rel}`.replace(/\/{2,}/g,'/'); // http://localhost/csnk/admin/uploads/video/trial1.mp4
}

function isIframeHost(url) { return /youtube\.com|youtu\.be|vimeo\.com/i.test(url); }

function toEmbedUrl(url) {
  try {
    const u = new URL(url);
    if (/youtube\.com/i.test(u.hostname)) {
      if (u.pathname === '/watch' && u.searchParams.get('v')) {
        return `https://www.youtube.com/embed/${u.searchParams.get('v')}`;
      }
      return url.replace('/watch?v=', '/embed/');
    }
    if (/youtu\.be/i.test(u.hostname)) {
      const id = u.pathname.replace('/', '');
      return `https://www.youtube.com/embed/${id}`;
    }
    if (/vimeo\.com/i.test(u.hostname)) {
      const id = u.pathname.split('/').filter(Boolean).pop();
      return `https://player.vimeo.com/video/${id}`;
    }
    return url;
  } catch {
    return url;
  }
}

function guessMime(url) {
  const u = url.split('?')[0].toLowerCase();
  if (u.endsWith('.mp4')) return 'video/mp4';
  if (u.endsWith('.webm')) return 'video/webm';
  if (u.endsWith('.ogg') || u.endsWith('.ogv')) return 'video/ogg';
  return 'video/mp4';
}

function buildApplicantVideoHtml(url, video_type) {
  const raw = (url || '').trim();
  if (!raw) {
    return `
      <div class="text-center text-muted py-5">
        <i class="bi bi-camera-video-off fs-1 d-block mb-2"></i>
        <div>No video provided for this applicant.</div>
      </div>
    `;
  }

  // Check if URL is already absolute first
  const isAbsolute = /^https?:\/\//i.test(raw);
  
  // Only process through absoluteFromAppRoot if it's a relative path
  const resolved = isAbsolute ? raw : absoluteFromAppRoot(raw);

  // Use DB-declared type if present, else infer by host
  const declared = String(video_type || '').toLowerCase();
  const useIframe = (declared === 'iframe') || (declared !== 'file' && isIframeHost(resolved));

  console.debug('[Video Build]', { raw, resolved, useIframe, video_type, declared, isAbsolute });

  if (useIframe) {
    const embed = toEmbedUrl(resolved);
    const safe  = escapeHtml(embed);
    console.debug('[Video] Embedding iframe:', safe);
    return `
      <div class="ratio ratio-16x9">
        <iframe src="${safe}" title="Applicant video"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="border:0;"></iframe>
      </div>
    `;
  }

  const src  = escapeHtml(resolved);
  const mime = guessMime(resolved);
  console.debug('[Video] Native video source:', { src, mime, resolved });
  return `
    <video controls playsinline preload="metadata" style="width:100%; display:block; background:#000; border-radius:12px;">
      <source src="${src}" type="${mime}">
      Your browser does not support the video tag.
    </video>
  `;
}

function pauseAnyVideoIn(root) {
  const v = root.querySelector('video');
  if (v) { try { v.pause(); } catch(_){} }
  const iframe = root.querySelector('iframe');
  if (iframe && iframe.src) {
    const s = iframe.src;
    iframe.src = s; // reload to stop
  }
}

function getPopoverElFor(btn) {
  const id = btn.getAttribute('aria-describedby');
  return id ? document.getElementById(id) : null;
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
  ensureVideoPopoverStyles();
  renderSkeleton(8);
  fetchApplicants({ page: 1 }).then(() => {
    setupEventListeners();

    // Deep-link: auto-open modal if URL has ?applicant=ID
    const url = new URL(window.location.href);
    const idParam = url.searchParams.get('applicant');
    if (idParam) {
      const found = filteredApplicants.find(a => String(a.id) === String(idParam));
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
// Server-driven fetch (supports filters, pagination)
let serverTotal = 0;
let serverPerPage = itemsPerPage;

async function fetchApplicants(options = {}) {
  // options may include page and per_page
  const page = options.page || 1;
  const per_page = options.per_page || serverPerPage || itemsPerPage;

  // Build params from current forms
  const params = new URLSearchParams();
  const formData    = searchForm ? new FormData(searchForm)   : new FormData();
  const filtersData = filtersForm ? new FormData(filtersForm) : new FormData();

  const q = (formData.get('q') || '').trim(); if (q) params.set('q', q);
  const location = (formData.get('location') || '').trim(); if (location) params.set('location', location);
  const available_by = (formData.get('available_by') || '').trim(); if (available_by) params.set('available_by', available_by);
  const minExp = parseInt(filtersData.get('min_experience')) || 0; if (minExp > 0) params.set('min_experience', String(minExp));
  const sortBy = (filtersData.get('sort') || '').trim(); if (sortBy) params.set('sort', sortBy);

  // multiple values: specializations[] and languages[]
  let specs = filtersData.getAll('specializations[]');
  // fallback: collect checked boxes directly if FormData returns nothing (handles malformed markup browsers might tolerate differently)
  if (!specs || specs.length === 0) {
    specs = Array.from(document.querySelectorAll('input[name="specializations[]"]:checked')).map(el => el.value);
  }
  specs.forEach(s => params.append('specializations[]', s));

  let langs = filtersData.getAll('languages[]');
  if (!langs || langs.length === 0) {
    langs = Array.from(document.querySelectorAll('input[name="languages[]"]:checked')).map(el => el.value);
  }
  langs.forEach(l => params.append('languages[]', l));

  params.set('page', String(page));
  params.set('per_page', String(per_page));

  try {
    renderSkeleton(6);
    const url = '../includes/get_applicants.php?' + params.toString();
    const res = await fetch(url, { cache: 'no-store', headers: { 'Accept':'application/json' } });
    if (!res.ok) throw new Error('Server error ' + res.status);
    const json = await res.json();

    if (json.error) throw new Error(json.error);

    // Set local state
    filteredApplicants = Array.isArray(json.data) ? json.data : [];
    serverTotal = Number(json.total || 0);
    serverPerPage = Number(json.per_page || per_page);
    currentPage = Number(json.page || page);

    renderApplicants();
  } catch (err) {
    console.error('Error fetching applicants:', err);
    if (cardsGrid) cardsGrid.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading applicants. Please try again.</p></div>';
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

    // Live search (debounced) for smoother UX
    const qInput = searchForm.querySelector('input[name="q"]');
    if (qInput) qInput.addEventListener('input', debounce(() => applyFilters(), 250));
  }

  if (filtersForm) {
    filtersForm.addEventListener('submit', function(e) {
      e.preventDefault();
      applyFilters();
    });

    // Auto-apply when any filter changes (debounced)
    filtersForm.querySelectorAll('input, select').forEach(el => {
      el.addEventListener('change', debounce(() => applyFilters(), 220));
    });

    // Also listen to range input in real time (min experience)
    const expRange = filtersForm.querySelector('input[name="min_experience"]');
    if (expRange) expRange.addEventListener('input', debounce(() => applyFilters(), 180));

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
      currentPage = 1;
      fetchApplicants({ page: 1 });
    });

    byId('applyFilters')?.addEventListener('click', () => applyFilters());
  }
}

/* =========================================================
   Filtering + Sorting
   - Normalizes specialization labels so "&" and "and" variants match
   - Location search matches city, region and preferred locations
   - Languages matching is case-insensitive
   - Debounced input + auto-apply for a smooth UX
========================================================= */

// Simple debounce helper
function debounce(fn, wait = 300){
  let t = null;
  return function(...args){
    clearTimeout(t);
    t = setTimeout(()=> fn.apply(this, args), wait);
  };
}

// Normalize values for comparison (lowercase, collapse spaces/dashes, replace &/and)
function cmpNorm(val){
  if (val == null) return '';
  return String(val).toLowerCase().replace(/\s+|[-_]/g, '').replace(/&|and/g, 'and').trim();
}

const debouncedApplyFilters = debounce(applyFiltersRaw, 220);

function applyFilters() { debouncedApplyFilters(); }

async function applyFiltersRaw(){
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

  const selectedSpecNorms = selectedSpecs.map(cmpNorm);
  const selectedLangNorms = selectedLangs.map(s => String(s || '').toLowerCase());

  // For server-side filtering we simply request page 1 with current filters
  currentPage = 1;
  await fetchApplicants({ page: 1 });
}

/* =========================================================
   Auto Rumble helpers
========================================================= */
function getCurrentSortValue() {
  try {
    return (filtersForm?.querySelector('#sort')?.value || 'newest').trim();
  } catch { return 'newest'; }
}

function randomSource() {
  // Use crypto if available for better randomness; fallback to Math.random
  try {
    const arr = new Uint32Array(1);
    crypto.getRandomValues(arr);
    return arr[0] / 0x100000000;
  } catch { return Math.random(); }
}

function shuffleArray(arr) {
  const a = arr.slice();
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(randomSource() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}

function isWithinDays(dateStr, days) {
  const d = toDate(dateStr);
  if (!d || !Number.isFinite(days) || days <= 0) return false;
  const now = new Date();
  const delta = now - d; // ms
  return delta <= (days * 24 * 60 * 60 * 1000);
}

function rumbleApplicants(list) {
  // Ensure we don’t mutate original list
  const ordered = list.slice(); // server already returns sorted by chosen 'sort'
  // Decide if rumble should apply under current sort selection
  const sortVal = getCurrentSortValue();
  if (!RUMBLE_ENABLED) return ordered;
  if (RUMBLE_ONLY_ON_SORT_NEWEST && sortVal !== 'newest') return ordered;

  let pinned = [];
  let rest = [];

  if (PIN_WITHIN_DAYS > 0) {
    pinned = ordered.filter(a => isWithinDays(a.created_at, PIN_WITHIN_DAYS));
    // Keep pinned in true 'newest' order:
    pinned.sort((a, b) => (toDate(b.created_at) - toDate(a.created_at)));
    const pinnedIds = new Set(pinned.map(a => a.id));
    rest = ordered.filter(a => !pinnedIds.has(a.id));
  } else if (PIN_NEWEST_COUNT > 0) {
    pinned = ordered.slice(0, PIN_NEWEST_COUNT);
    rest = ordered.slice(PIN_NEWEST_COUNT);
  } else {
    // No pinning; fully shuffle (still respects sort gate)
    return shuffleArray(ordered);
  }

  const shuffledRest = shuffleArray(rest);
  return [...pinned, ...shuffledRest];
}

/* =========================================================
   Rendering
========================================================= */
function renderApplicants() {
  const perPage = serverPerPage || itemsPerPage;
  const list = filteredApplicants; // server already returns the requested page

  const totalResults = serverTotal || list.length;
  const startResult  = (totalResults > 0) ? ((currentPage - 1) * perPage) + 1 : 0;
  const endResult    = startResult + list.length - 1;
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

  // 🔀 Apply Auto Rumble here (pin newest on top, shuffle the rest per refresh)
  const displayList = rumbleApplicants(list);

  displayList.forEach(applicant => cardsGrid.appendChild(createApplicantCard(applicant)));
  renderPagination();
}

/* =========================================================
   Card (photo-top modern layout) — NO availability on card
   Entire card is clickable (+ keyboard) to open the modal.
========================================================= */
function createApplicantCard(applicant) {
  const col = document.createElement('div');
  col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';

  const yoe   = `${toInt(applicant.years_experience)} yrs of experience`;

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
  const perPage = serverPerPage || itemsPerPage;
  const totalPages = Math.ceil((serverTotal || filteredApplicants.length) / perPage);
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

  addPageItem('&laquo;', currentPage === 1, () => { if (currentPage > 1) fetchApplicants({ page: currentPage - 1 }); });

  const windowSize = 5;
  let start = Math.max(1, currentPage - Math.floor(windowSize/2));
  let end   = Math.min(totalPages, start + windowSize - 1);
  if (end - start + 1 < windowSize) start = Math.max(1, end - windowSize + 1);

  for (let i = start; i <= end; i++) {
    addPageItem(String(i), false, () => { fetchApplicants({ page: i }); }, i === currentPage);
  }

  addPageItem('&raquo;', currentPage === totalPages, () => { if (currentPage < totalPages) fetchApplicants({ page: currentPage + 1 }); });

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

/* =========================================================
   Modal video popover (top-right button)
   - Adds a small "Video" button to the modal header (right side)
   - Shows a Bootstrap Popover with <video> or YouTube/Vimeo <iframe>
   - Pauses video when popover or modal closes; closes on outside click
========================================================= */
function ensureModalVideoButton(applicant) {
  const modalEl = byId('applicantModal');
  if (!modalEl) {
    console.warn('Modal not found.');
    return;
  }
  const hasPopover = !!(window.bootstrap?.Popover);

  // ----- Ensure header + tools container -----
  let header = modalEl.querySelector('.modal-header');
  if (!header) {
    header = document.createElement('div');
    header.className = 'modal-header';
    header.innerHTML = `
      <h5 class="modal-title">Profile</h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    `;
    modalEl.querySelector('.modal-content')?.prepend(header);
  }

  let tools = header.querySelector('.modal-tools');
  if (!tools) {
    tools = document.createElement('div');
    tools.className = 'modal-tools d-flex align-items-center gap-2 ms-auto';
    header.appendChild(tools);
  }

  // ----- Ensure the Video button -----
  let videoBtn = tools.querySelector('#modalVideoBtn');
  if (!videoBtn) {
    videoBtn = document.createElement('button');
    videoBtn.id = 'modalVideoBtn';
    videoBtn.type = 'button';
    videoBtn.className = 'btn btn-sm btn-outline-secondary';
    videoBtn.innerHTML = `<i class="bi bi-camera-video me-1"></i> Video`;
    videoBtn.title = 'Play introduction video';
    tools.appendChild(videoBtn);
  }

  // ----- Bind data from applicant -----
  const videoUrl  = (applicant && typeof applicant.video_url === 'string' && applicant.video_url.trim() !== '')
    ? applicant.video_url.trim()
    : '';
  const videoType = (applicant && applicant.video_type) ? String(applicant.video_type).trim() : '';

  console.debug('[Video] Applicant:', {
    id: applicant?.id,
    raw_url: applicant?.video_url,
    trimmed_url: videoUrl,
    type: videoType,
    hasPopover
  });

  videoBtn.dataset.videoUrl  = videoUrl;
  videoBtn.dataset.videoType = videoType;

  // Hide button if no video URL
  if (!videoUrl) {
    videoBtn.style.display = 'none';
    return;
  }
  videoBtn.style.display = '';

  if (hasPopover) {
    // Use Bootstrap Popover if available
    const existing = bootstrap.Popover.getInstance(videoBtn);
    if (existing) existing.dispose();

    const pop = new bootstrap.Popover(videoBtn, {
      html: true,
      trigger: 'manual',
      placement: 'bottom',   // adjust: 'bottom-end', 'right-start', 'auto', etc.
      sanitize: false,       // we provide safe markup
      container: modalEl,
      template: `
        <div class="popover video-popover" role="tooltip">
          <div class="popover-arrow"></div>
          <div class="popover-body p-0"></div>
        </div>
      `,
      content: () => buildApplicantVideoHtml(videoBtn.dataset.videoUrl, videoBtn.dataset.videoType),
    });

    // Toggle popover
    videoBtn.onclick = () => {
      const isShown = !!videoBtn.getAttribute('aria-describedby');
      isShown ? pop.hide() : pop.show();
    };

    // Outside click to close + pause on hide
    const onShown = () => {
      const popEl = getPopoverElFor(videoBtn);

      // Helpful: log the resolved absolute URL that the player will use
      try {
        const raw = videoBtn.dataset.videoUrl || '';
        const resolved = absoluteFromAppRoot(raw);
        console.debug('[Video] Resolved:', { raw, resolved, type: videoBtn.dataset.videoType });
      } catch(_) {}

      const onDocClick = (e) => {
        if (popEl && !popEl.contains(e.target) && !videoBtn.contains(e.target)) {
          pop.hide();
        }
      };
      document.addEventListener('mousedown', onDocClick, { capture: true });
      videoBtn._video_onDocClick = onDocClick;
    };

    const onHidden = () => {
      const popEl = getPopoverElFor(videoBtn);
      if (popEl) pauseAnyVideoIn(popEl);
      if (videoBtn._video_onDocClick) {
        document.removeEventListener('mousedown', videoBtn._video_onDocClick, { capture: true });
        videoBtn._video_onDocClick = null;
      }
    };

    videoBtn.addEventListener('shown.bs.popover', onShown);
    videoBtn.addEventListener('hide.bs.popover', onHidden);

    // ----- Also hide the popover when the modal closes -----
    if (!modalEl.dataset.videoPopoverBound) {
      modalEl.addEventListener('hide.bs.modal', () => {
        const inst = bootstrap.Popover.getInstance(videoBtn);
        if (inst) inst.hide();
      });
      modalEl.dataset.videoPopoverBound = '1';
    }
  } else {
    // Fallback: open a simple video modal when Popover isn't available
    videoBtn.onclick = () => openVideoFallbackModal(videoBtn.dataset.videoUrl, videoBtn.dataset.videoType);

    // Ensure the fallback modal is hidden when profile modal closes
    if (!modalEl.dataset.videoFallbackBound) {
      modalEl.addEventListener('hide.bs.modal', () => {
        const fb = byId('applicantVideoModal');
        if (fb) {
          const inst = bootstrap.Modal.getInstance(fb);
          if (inst) inst.hide();
        }
      });
      modalEl.dataset.videoFallbackBound = '1';
    }
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
  const yoeBadgeEl = byId('yoeBadge'); if (yoeBadgeEl) yoeBadgeEl.textContent = `${toInt(applicant.years_experience)} yrs of experience`;

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
  ensureModalVideoButton(applicant);

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
      border:1px solid (--card-border, #e6e9ef);
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

/* =========================================================
   Styles: video popover
========================================================= */
function ensureVideoPopoverStyles(){
  if (document.getElementById('video-popover-styles')) return;
  const css = `
  /* Make it look like a mini-player panel */
.video-popover{
  width: min(92vw, 760px);
  max-width: min(92vw, 760px);
}
@media (min-width: 992px){ /* lg+ screens */
  .video-popover {
    width: 680px; /* or 820px, your call */
  }
}
.video-popover .popover-body{ padding:0; }
.video-popover .ratio > iframe,
.video-popover video{ border-radius:12px; }

    /* Keep the header tools tidy */
    .modal-header .modal-tools .btn{ white-space: nowrap; }
  `;
  const style = document.createElement('style');
  style.id = 'video-popover-styles';
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
}

// Fallback modal for video playback when Bootstrap Popover isn't available
function openVideoFallbackModal(rawUrl, videoType) {
  const modalId = 'applicantVideoModal';
  let modal = byId(modalId);
  if (!modal) {
    modal = document.createElement('div');
    modal.id = modalId;
    modal.className = 'modal fade';
    modal.tabIndex = -1;
    modal.innerHTML = `
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Introduction Video</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-3 video-modal-body"></div>
        </div>
      </div>
    `;
    document.body.appendChild(modal);

    // Pause video on hide
    modal.addEventListener('hide.bs.modal', () => pauseAnyVideoIn(modal));
  }

  const body = modal.querySelector('.video-modal-body');
  if (body) {
    // Build content using existing helper (it will resolve relative URLs)
    body.innerHTML = buildApplicantVideoHtml(rawUrl, videoType);
  }

  if (window.bootstrap?.Modal) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  } else {
    // Last resort: open video in new tab/window
    const resolved = absoluteFromAppRoot(rawUrl);
    window.open(resolved || rawUrl, '_blank');
  }
}