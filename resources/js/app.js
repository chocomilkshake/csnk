// app.js — Client listing UX (photo-top modern cards)
// v3.2 — DPA-safe rate everywhere (cards + modal):
//        - Reads exact `daily_rate` (or compatible aliases) from API
//        - Converts 695 → "₱700 - ₱800 / day" (round UP to nearest ₱100)
//        - Hides rate UI when missing/invalid
//        - Cleans & parses strings like "₱695", "695.00/day" reliably
//        - Keeps v2.9 features: cards clickable, video popover, deep-link, polling, rumble
console.log('app.js loaded successfully - v3.2');

/* =========================================================
  State
========================================================= */
let allApplicants = [];
let filteredApplicants = [];
let currentPage = 1;
const itemsPerPage = 12;

/* =========================================================
  Auto Rumble (client-side unbiased rotation)
========================================================= */
const RUMBLE_ENABLED = true;
const RUMBLE_ONLY_ON_SORT_NEWEST = true;
const PIN_NEWEST_COUNT = 4;
const PIN_WITHIN_DAYS = 0;

/* =========================================================
  DOM
========================================================= */
const searchForm   = document.getElementById('searchForm');
const filtersForm  = document.getElementById('filtersForm');
const cardsGrid    = document.getElementById('cardsGrid');
const resultsCount = document.getElementById('resultsCount');
const pagination   = document.getElementById('pagination');

/* =========================================================
  Basic helpers
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

// ---- CONFIG: app root path (match your mount, e.g., '/csnk-1/') ----
const APP_BASE = '/csnk-1/';

// Poll interval for live refresh
const POLL_INTERVAL_MS = 15000;

function normalizeSlashes(url) { return String(url || '').replace(/\\/g, '/').trim(); }
function isAbsoluteUrl(u) { return /^https?:\/\//i.test(u); }

function absoluteFromAppRoot(path) {
  if (!path) return '';
  const p = normalizeSlashes(path);
  if (isAbsoluteUrl(p)) return p;
  const base = APP_BASE.replace(/\/+$/,'');
  const rel  = p.replace(/^\/+/,'');
  return `${location.origin}${base}/${rel}`.replace(/\/{2,}/g,'/');
}

/* =========================================================
  Video helpers
========================================================= */
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
  } catch { return url; }
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
  const isAbs = /^https?:\/\//i.test(raw);
  const resolved = isAbs ? raw : absoluteFromAppRoot(raw);
  const declared = String(video_type || '').toLowerCase();
  const useIframe = (declared === 'iframe') || (declared !== 'file' && isIframeHost(resolved));

  if (useIframe) {
    const embed = escapeHtml(toEmbedUrl(resolved));
    // Proper iframe element (avoid broken markup)
    return `
      <div class="ratio ratio-16x9">
        <iframe src="${embed}" title="Applicant video"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="border:0;"></iframe>
      </div>
    `;
  }

  const src  = escapeHtml(resolved);
  const mime = guessMime(resolved);
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
  if (iframe && iframe.src) { const s = iframe.src; iframe.src = s; }
}
function getPopoverElFor(btn) {
  const id = btn.getAttribute('aria-describedby');
  return id ? document.getElementById(id) : null;
}

/* =========================================================
  DPA-safe RATE helpers  (uses your DB `daily_rate` field)
========================================================= */
/** Parse numeric from various formats: "₱695", "695.00 / day", 695, "700" */
function parseDailyRate(val){
  if (val == null) return null;
  if (typeof val === 'number') return Number.isFinite(val) ? val : null;
  if (typeof val === 'string') {
    const cleaned = val
      .replace(/,/g,'')
      .replace(/₱/g,'')
      .replace(/per\s*day/gi,'')
      .replace(/\/\s*day/gi,'')
      .replace(/[^\d.]/g,'')      // keep digits & dot
      .replace(/(\..*?)\./g,'$1'); // keep first dot only
    const num = parseFloat(cleaned);
    return Number.isFinite(num) ? num : null;
  }
  return null;
}

/** Accept possible API field names but prefer DB `daily_rate` */
function getApplicantRate(a){
  const raw = (a?.daily_rate ?? a?.dailyRate ?? a?.rate ?? a?.daily_wage ?? null);
  return parseDailyRate(raw);
}

/** Convert exact amount to a DPA-safe range, rounded UP to the nearest ₱100 */
function formatRateAsRange(amount) {
  const num = parseDailyRate(amount);
  if (!Number.isFinite(num) || num <= 0) return '—';
  const rounded = Math.ceil(num / 100) * 100;
  const lower = rounded;
  const upper = rounded + 100;
  return `₱${lower.toLocaleString('en-PH')} - ₱${upper.toLocaleString('en-PH')} / day`;
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
  // Use the placeholder from API if available, otherwise use correct path
  const fallback = placeholder || '/csnk/resources/img/placeholder-user.svg';
  const cleanSrc = (src && typeof src === 'string') ? src.trim() : '';
  
  // If we have a valid source, use it; otherwise use fallback
  // The API now provides full URLs (http:// or https://), so we just use them directly
  const useSrc = cleanSrc || fallback;

  imgEl.loading = 'lazy';
  imgEl.decoding = 'async';
  imgEl.alt = imgEl.alt || 'Photo';
  imgEl.src = useSrc;
  imgEl.onerror = () => { 
    // On error, try the placeholder
    imgEl.src = fallback; 
  };
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
    // Background polling
    if (typeof POLL_INTERVAL_MS === 'number' && POLL_INTERVAL_MS > 0) {
      setInterval(() => { try { fetchApplicants({ page: currentPage }); } catch(_){ } }, POLL_INTERVAL_MS);
    }
    // Deep-link
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
  Data loading (server-driven)
========================================================= */
let serverTotal = 0;
let serverPerPage = itemsPerPage;

async function fetchApplicants(options = {}) {
  if (fetchApplicants._inFlight) return;
  fetchApplicants._inFlight = true;

  const page = options.page || 1;
  const per_page = options.per_page || serverPerPage || itemsPerPage;

  const params = new URLSearchParams();
  const formData    = searchForm ? new FormData(searchForm)   : new FormData();
  const filtersData = filtersForm ? new FormData(filtersForm) : new FormData();

  const q = (formData.get('q') || '').trim(); if (q) params.set('q', q);
  const location = (formData.get('location') || '').trim(); if (location) params.set('location', location);
  const available_by = (formData.get('available_by') || '').trim(); if (available_by) params.set('available_by', available_by);
  const minExp = parseInt(filtersData.get('min_experience')) || 0; if (minExp > 0) params.set('min_experience', String(minExp));
  const sortBy = (filtersData.get('sort') || '').trim(); if (sortBy) params.set('sort', sortBy);

  let specs = filtersData.getAll('specializations[]');
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

  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('rotate') === '0') params.set('rotate', '0');

  try {
    renderSkeleton(6);
    const url = '../includes/get_applicants.php?' + params.toString();
    const res = await fetch(url, { cache: 'no-store', headers: { 'Accept':'application/json' } });
    if (!res.ok) throw new Error('Server error ' + res.status);
    const json = await res.json();

    if (json.error) throw new Error(json.error);

    filteredApplicants = Array.isArray(json.data) ? json.data : [];
    serverTotal  = Number(json.total || 0);
    serverPerPage = Number(json.per_page || per_page);
    currentPage  = Number(json.page || page);

    // Client-side page shuffle (deterministic per page)
    try {
      const rotateDisabled = urlParams.get('rotate') === '0';
      if (!rotateDisabled && filteredApplicants.length > 1) {
        const pageSeed = currentPage || 1;
        const sessionKey = 'csnk_shuffle_seed_' + pageSeed;
        let seed = parseInt(sessionStorage.getItem(sessionKey) || '0', 10);
        if (seed === 0) {
          seed = Math.floor(Math.random() * 1000000);
          sessionStorage.setItem(sessionKey, String(seed));
        }
        filteredApplicants = seededShuffle(filteredApplicants, seed);
        console.debug('[Advanced Randomization] Applied seeded shuffle for page', currentPage);
      }
    } catch (err) {
      console.warn('Advanced randomization failed:', err);
    }

    renderApplicants();
  } catch (err) {
    console.error('Error fetching applicants:', err);
    if (cardsGrid) cardsGrid.innerHTML = '<div class="col-12 text-center"><p class="text-danger">Error loading applicants. Please try again.</p></div>';
  } finally {
    fetchApplicants._inFlight = false;
  }
}

/* =========================================================
  Events
========================================================= */
function setupEventListeners() {
  if (searchForm) {
    searchForm.addEventListener('submit', function(e) { e.preventDefault(); applyFilters(); });
    const qInput = searchForm.querySelector('input[name="q"]');
    if (qInput) qInput.addEventListener('input', debounce(() => applyFilters(), 250));
    const locationInput = searchForm.querySelector('input[name="location"]');
    if (locationInput) locationInput.addEventListener('input', debounce(() => applyFilters(), 250));
  }

  if (filtersForm) {
    filtersForm.addEventListener('submit', function(e) { e.preventDefault(); applyFilters(); });
    filtersForm.querySelectorAll('input, select').forEach(el => {
      el.addEventListener('change', debounce(() => applyFilters(), 220));
    });
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
  Filtering (delegated to server)
========================================================= */
function debounce(fn, wait = 300){
  let t = null;
  return function(...args){
    clearTimeout(t);
    t = setTimeout(()=> fn.apply(this, args), wait);
  };
}
const debouncedApplyFilters = debounce(applyFiltersRaw, 220);
function applyFilters() { debouncedApplyFilters(); }
async function applyFiltersRaw(){
  currentPage = 1;
  await fetchApplicants({ page: 1 });
}

/* =========================================================
  Auto Rumble helpers
========================================================= */
function getCurrentSortValue() {
  try { return (filtersForm?.querySelector('#sort')?.value || 'newest').trim(); }
  catch { return 'newest'; }
}
function randomSource() {
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
function seededShuffle(arr, seed) {
  const a = arr.slice();
  let rng = seededRandom(seed);
  for (let i = a.length - 1; i > 0; i--) {
    const j = Math.floor(rng() * (i + 1));
    [a[i], a[j]] = [a[j], a[i]];
  }
  return a;
}
function seededRandom(seed) {
  let value = seed;
  return function() {
    value = (value * 9301 + 49297) % 233280;
    return value / 233280;
  };
}
function isWithinDays(dateStr, days) {
  const d = toDate(dateStr);
  if (!d || !Number.isFinite(days) || days <= 0) return false;
  const now = new Date();
  return (now - d) <= (days * 24 * 60 * 60 * 1000);
}
function rumbleApplicants(list) {
  const ordered = list.slice();
  const sortVal = getCurrentSortValue();
  if (!RUMBLE_ENABLED) return ordered;
  if (RUMBLE_ONLY_ON_SORT_NEWEST && sortVal !== 'newest') return ordered;

  let pinned = [], rest = [];
  if (PIN_WITHIN_DAYS > 0) {
    pinned = ordered.filter(a => isWithinDays(a.created_at, PIN_WITHIN_DAYS))
                    .sort((a,b)=> (toDate(b.created_at) - toDate(a.created_at)));
    const set = new Set(pinned.map(a=>a.id));
    rest = ordered.filter(a=>!set.has(a.id));
  } else if (PIN_NEWEST_COUNT > 0) {
    pinned = ordered.slice(0, PIN_NEWEST_COUNT);
    rest   = ordered.slice(PIN_NEWEST_COUNT);
  } else {
    return shuffleArray(ordered);
  }
  return [...pinned, ...shuffleArray(rest)];
}

/* =========================================================
  Rendering (cards)
========================================================= */
function renderApplicants() {
  const perPage = serverPerPage || itemsPerPage;
  const list = filteredApplicants;

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

  let displayList = rumbleApplicants(list);

  // Filter out excluded statuses (e.g., approved)
  try {
    const excludeAttr = cardsGrid?.dataset?.excludeStatus || '';
    if (excludeAttr) {
      const excludeSet = new Set(excludeAttr.split(',').map(s => s.trim().toLowerCase()).filter(Boolean));
      if (excludeSet.size > 0) {
        displayList = displayList.filter(a => !excludeSet.has(String(a.status || '').toLowerCase()));
      }
    }
  } catch(e){ console.warn('Exclude filter failed', e); }

  displayList.forEach(applicant => cardsGrid.appendChild(createApplicantCard(applicant)));
  renderPagination();
}

/* =========================================================
  Card — with DPA-safe rate BADGE (top-right of the photo)
========================================================= */
function createApplicantCard(applicant) {
  const col = document.createElement('div');
  col.className = 'col-12 col-sm-6 col-lg-4 col-xl-3';

  const yoe   = `${toInt(applicant.years_experience)} yrs of experience`;

  const fullName       = escapeHtml(applicant.full_name || [applicant.first_name, applicant.middle_name, applicant.last_name].filter(Boolean).join(' ') || '—');
  const specialization = escapeHtml(applicant.specialization || applicant.specialization_skills || '—');
  const employmentType = escapeHtml(applicant.employment_type || '—');
  const location       = `${escapeHtml(applicant.location_city || (applicant.city ?? '') || '—')}, ${escapeHtml(applicant.location_region || (applicant.region ?? '') || '—')}`;
  const status         = escapeHtml(String(applicant.status || '').toLowerCase());

  const exactRate = getApplicantRate(applicant);
  const rateRange = formatRateAsRange(exactRate);

  const html = `
    <article class="card app-card h-100 hover-lift clickable-card" role="button" tabindex="0" aria-label="View ${fullName} profile" data-status="${status}">
      <!-- Top photo -->
      <div class="ratio ratio-4x3 card-photo-wrap position-relative">
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
  setAvatar(img, applicant.photo_url || applicant.picture, applicant.photo_placeholder);

  // View Profile button
  const viewBtn = col.querySelector('.view-profile-btn');
  viewBtn.addEventListener('click', (e) => {
    e.preventDefault();
    pushApplicantId(applicant.id);
    showApplicantModal(applicant);
  });

  // Entire card is clickable + keyboard
  const card = col.querySelector('.clickable-card');
  const open = () => { pushApplicantId(applicant.id); showApplicantModal(applicant); };
  card.addEventListener('click', (e) => { if (e.target.closest('.view-profile-btn')) return; open(); });
  card.addEventListener('keydown', (e) => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); } });

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
  Modal (Profile) — DPA-safe rate in the tile
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

function ensureModalVideoButton(applicant) {
  const modalEl = byId('applicantModal');
  if (!modalEl) return;
  const hasPopover = !!(window.bootstrap?.Popover);

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

  const videoUrl  = (applicant && typeof applicant.video_url === 'string' && applicant.video_url.trim() !== '')
    ? applicant.video_url.trim() : '';
  const videoType = (applicant && applicant.video_type) ? String(applicant.video_type).trim() : '';

  videoBtn.dataset.videoUrl  = videoUrl;
  videoBtn.dataset.videoType = videoType;

  if (!videoUrl) { videoBtn.style.display = 'none'; return; }
  videoBtn.style.display = '';

  if (hasPopover) {
    const existing = bootstrap.Popover.getInstance(videoBtn);
    if (existing) existing.dispose();

    const pop = new bootstrap.Popover(videoBtn, {
      html: true, trigger: 'manual', placement: 'bottom', sanitize: false, container: modalEl,
      template: `
        <div class="popover video-popover" role="tooltip">
          <div class="popover-arrow"></div>
          <div class="popover-body p-0"></div>
        </div>
      `,
      content: () => buildApplicantVideoHtml(videoBtn.dataset.videoUrl, videoBtn.dataset.videoType),
    });

    videoBtn.onclick = () => {
      const isShown = !!videoBtn.getAttribute('aria-describedby');
      isShown ? pop.hide() : pop.show();
    };

    const onShown = () => {
      const popEl = getPopoverElFor(videoBtn);
      const onDocClick = (e) => { if (popEl && !popEl.contains(e.target) && !videoBtn.contains(e.target)) pop.hide(); };
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

    if (!modalEl.dataset.videoPopoverBound) {
      modalEl.addEventListener('hide.bs.modal', () => {
        const inst = bootstrap.Popover.getInstance(videoBtn);
        if (inst) inst.hide();
      });
      modalEl.dataset.videoPopoverBound = '1';
    }
  } else {
    videoBtn.onclick = () => openVideoFallbackModal(videoBtn.dataset.videoUrl, videoBtn.dataset.videoType);
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

function onProfileHiddenCleanUrl(){ removeApplicantIdFromUrl(); }

/* =========================================================
  Modal show — bind values + DPA-safe rate
========================================================= */
function showApplicantModal(applicant, options = { pushState: true }) {
  const dailyRateValue = getApplicantRate(applicant);
  const rateTile = byId('dailyRateValue');
  if (rateTile) rateTile.textContent = formatRateAsRange(dailyRateValue);

  const modalEl = byId('applicantModal');
  if (!modalEl) return;

  const prefEl = byId('prefLocValue');
  if (prefEl) {
    const arr = Array.isArray(applicant.preferred_locations || applicant.preferred_location) ? (applicant.preferred_locations || applicant.preferred_location) : [];
    prefEl.innerHTML = arr.length
      ? arr.map(x=>`<span class="badge text-bg-light border me-1 mb-1">${escapeHtml(x)}</span>`).join('')
      : '—';
  }

  const eduEl = byId('eduValue'); if (eduEl) eduEl.textContent = applicant.education_display || applicant.educational_attainment || applicant.education_level || '—';

  const avatar = byId('avatar');
  if (avatar) {
    const photo = applicant.photo_url || applicant.picture;
    if (photo) {
      avatar.style.backgroundImage = `url(${photo})`;
      avatar.style.backgroundSize = 'cover';
      avatar.textContent = '';
    } else {
      avatar.style.backgroundImage = '';
    }
  }
  const nameEl = byId('name');
  if (nameEl) {
    const safeName = applicant.full_name || [applicant.first_name, applicant.middle_name, applicant.last_name].filter(Boolean).join(' ') || '—';
    nameEl.textContent = safeName;
  }
  const primaryRoleEl = byId('primaryRole'); if (primaryRoleEl) primaryRoleEl.textContent = applicant.specialization || '—';
  const yoeBadgeEl = byId('yoeBadge'); if (yoeBadgeEl) yoeBadgeEl.textContent = `${toInt(applicant.years_experience)} yrs of experience`;

  const availabilityLineEl = byId('availabilityLine');
  if (availabilityLineEl) {
    availabilityLineEl.textContent = `${applicant.location_city || '—'}, ${applicant.location_region || '—'}`;
  }

  const chipsContainer = byId('chipsContainer');
  if (chipsContainer) {
    const chips = arrFromMaybe(applicant.specializations?.length ? applicant.specializations : (applicant.specialization || applicant.specialization_skills));
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
    // DB stores as longtext JSON sometimes
    const langsArr = Array.isArray(applicant.languages) ? applicant.languages : arrFromMaybe(applicant.languages);
    langEl.textContent = langsArr.length ? langsArr.join(', ') : (applicant.languages || '—');
  }

  ensureModalFooterAndHire(applicant);
  ensureModalVideoButton(applicant);

  const profileModal = bootstrap.Modal.getOrCreateInstance(modalEl);
  profileModal.show();
  modalEl.dataset.applicant = JSON.stringify(applicant);

  if (options.pushState) pushApplicantId(applicant.id);
}
window.showApplicantModal = showApplicantModal;

/* =========================================================
  Booking (Hire)
========================================================= */
(function enforceButtonTypes(){
  ['bkSubmit','bkNext','bkBack'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.setAttribute('type','button');
  });
})();
function launchBooking(applicant){
  window._lastApplicantForBooking = applicant;
  const modalEl = byId('bookingModal');
  if (!modalEl || !window.bootstrap?.Modal) return;

  const bkAvatar = modalEl.querySelector('#bkAvatar');
  if (bkAvatar) {
    const photo = applicant.photo_url || applicant.picture;
    bkAvatar.style.backgroundImage = photo ? `url('${photo}')` : '';
    bkAvatar.style.backgroundSize = photo ? 'cover' : '';
    bkAvatar.textContent = photo ? '' : '';
  }
  const bkName = modalEl.querySelector('#bkName'); if (bkName) bkName.textContent = applicant.full_name || [applicant.first_name, applicant.middle_name, applicant.last_name].filter(Boolean).join(' ') || '—';
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
  Styles injection (card layout + rate badge + shimmer)
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
      overflow:hidden;
      box-shadow:0 2px 8px rgba(0,0,0,.06);
      background:#fff;
      cursor:pointer;
      outline:none;
    }

    .card-photo-wrap{ background:#f5f6f8; position:relative; }
    .card-photo{ width:100%; height:100%; object-fit:cover; display:block; }
    .card-photo-wrap::after{
      content:'';
      position:absolute; inset:0;
      background:linear-gradient(to bottom, rgba(0,0,0,0) 60%, rgba(0,0,0,.02));
      pointer-events:none;
    }

    /* DPA-safe rate badge (top-right) */
    .rate-badge{
      position:absolute;
      top:8px; right:8px;
      background:var(--brand-red);
      color:#fff;
      font-size:.78rem;
      font-weight:800;
      border-radius:999px;
      padding:.28rem .6rem;
      box-shadow:0 4px 10px rgba(0,0,0,.15);
      max-width:85%;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }

    .app-name-title{ font-weight:800; }

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
  .video-popover{
    width: min(92vw, 760px);
    max-width: min(92vw, 760px);
  }
  @media (min-width: 992px){
    .video-popover { width: 680px; }
  }
  .video-popover .popover-body{ padding:0; }
  .video-popover .ratio > iframe,
  .video-popover video{ border-radius:12px; }

  .modal-header .modal-tools .btn{ white-space: nowrap; }
  `;
  const style = document.createElement('style');
  style.id = 'video-popover-styles';
  style.appendChild(document.createTextNode(css));
  document.head.appendChild(style);
}

/* =========================================================
  Video fallback modal
========================================================= */
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
    modal.addEventListener('hide.bs.modal', () => pauseAnyVideoIn(modal));
  }
  const body = modal.querySelector('.video-modal-body');
  if (body) body.innerHTML = buildApplicantVideoHtml(rawUrl, videoType);

  if (window.bootstrap?.Modal) {
    bootstrap.Modal.getOrCreateInstance(modal).show();
  } else {
    const resolved = absoluteFromAppRoot(rawUrl);
    window.open(resolved || rawUrl, '_blank');
  }
}