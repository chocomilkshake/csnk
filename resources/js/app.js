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
      ``


  // NEW: small utility helpers to make filtering safer/readable
  const norm = (s) => String(s ?? '').toLowerCase().trim();
  const num = (n, d = 0) => (Number.isFinite(Number(n)) ? Number(n) : d);
  const dateOrNull = (v) => {
    const t = Date.parse(v);
    return Number.isFinite(t) ? new Date(t) : null;
  };

  // NEW: debounce to avoid too many refreshes on rapid filter changes
  function debounce(fn, delay = 250) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  }

  // NEW: show/hide loading affordance
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

      // CHANGE: use absolute from root to avoid URL resolution issues
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
      const langs = params.getAll('languages[]').map(norm); // NEW: match languages case-insensitively

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

      // Sort (CHANGE: removed duplicate sort block)
      filtered.sort((a, b) => {
        switch (sort) {
          case 'experience_desc':
            return num(b.years_experience, 0) - num(a.years_experience, 0);
          case 'newest': {
            const da = dateOrNull(a.created_at)?.getTime() ?? 0;
            const db = dateOrNull(b.created_at)?.getTime() ?? 0;
            return db - da;
          }
          // default: availability ascending
          default: {
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

  //newwwwww
  // --- Render a single applicant into the modal ---
function renderApplicantToModal(a){
  // Safe helpers (reuse from your code)
  const escape = (s) => String(s ?? '');

  // Avatar initials
  const initials = (a.full_name || '')
    .split(' ')
    .map(p => p[0])
    .join('')
    .slice(0,2)
    .toUpperCase() || 'AP';
  document.getElementById('avatar').textContent = initials;

  // Header fields
  document.getElementById('name').textContent = a.full_name || 'Applicant';
  document.getElementById('primaryRole').textContent = a.specialization || '—';
  document.getElementById('yoeBadge').textContent = `${a.years_experience ?? 0} yrs`;

  // Availability line (City, Region • Available from: <date>)
  const availDate = Date.parse(a.availability_date);
  const availStr = Number.isFinite(availDate)
    ? new Date(availDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';
  document.getElementById('availabilityLine').innerHTML =
  `${escape(a.location_city) || '—'}, ${escape(a.location_region) || '—'} • Available from: <strong class="text-dark"${availStr}</strong>`;

  // Specialization chips (your data has a SINGLE specialization string)
  const chips = document.getElementById('chipsContainer');
  chips.innerHTML = '';
  if (a.specialization) {
    const span = document.createElement('span');
    span.className = 'chip';
    span.textContent = a.specialization;
    chips.appendChild(span);
  }

  // Basic info
  document.getElementById('cityValue').textContent = a.location_city || '—';
  document.getElementById('regionValue').textContent = a.location_region || '—';
  document.getElementById('yoeValue').textContent = a.years_experience ?? '—';
  document.getElementById('employmentValue').textContent = a.employment_type || '—';
  document.getElementById('availValue').textContent = availStr;

  // Languages: your filters split by comma, mirror here
  const langs = String(a.languages || '')
    .split(',')
    .map(s => s.trim())
    .filter(Boolean);
  document.getElementById('langValue').textContent = langs.length ? langs.join(', ') : '—';

  // (Optional) Use photo in avatar circle background? You can add an <img> if you want.
}

let PROFILE_MODAL_INSTANCE = null;
async function openProfileModal(id){
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

  // Demo actions (replace with your integration)
  document.getElementById('shortlistBtn').onclick = () => alert(`Shortlisted: ${found.full_name}`);
  document.getElementById('messageBtn').onclick = () => alert(`Message sent to: ${found.full_name}`);
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

  // NEW: pushState for user-initiated changes (makes back button work)
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

  // NEW: ensure URL used in <img> is safe-ish and fallback on error
  
function safeImg(src) {
  const fallback = '../resources/img/placeholder-user.svg'; // ✅ match your structure
  const val = String(src || '').trim();
  if (!val) return fallback;
  if (/^(https?:)?\/\//i.test(val) || val.startsWith('/') || val.startsWith('./') || val.startsWith('../')) {
    return val;
  }
  return fallback;
}


  // --- Card template (with data-id and hover activator) ---
 function cardTemplate(a) {
  const availDate = Date.parse(a.availability_date);
  const availStr = Number.isFinite(availDate)
    ? new Date(availDate).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' })
    : '—';

  const id = a.id ?? a.applicant_id ?? '';

  return `
    <div class="col-12 col-sm-6 col-lg-4">
      <article class="card h-100 shadow-sm position-relative hover-activator" data-id="${escapeHtml(id)}">
        <img
          src="${escapeHtml(safeImg(a.photo_url))}"
          class="card-img-top object-fit-cover"
          style="height:220px"
          alt="${escapeHtml(a.full_name)}"
          loading="lazy"
          onerror="this.onerror=null;this.src='../resources/img/placeholder-user.svg';"
        />

        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <h2 class="h6 card-title mb-1">${escapeHtml(a.full_name)}</h2>
            <span class="badge text-bg-light border">${escapeHtml(a.employment_type)}</span>
          </div>

          <div class="small text-secondary mb-2">
            <i class="bi bi-geo-alt"></i>
            ${escapeHtml(a.location_city)}, ${escapeHtml(a.location_region)}
          </div>

          <div class="mb-2">
            <span class="badge rounded-pill text-bg-primary-subtle text-primary">
              ${escapeHtml(a.specialization)}
            </span>
          </div>

          <div class="d-flex justify-content-between small">
            <span><i class="bi bi-briefcase"></i> ${escapeHtml(a.years_experience ?? 0)} yrs</span>
            <span class="text-nowrap"><i class="bi bi-calendar-check"></i> ${escapeHtml(availStr)}</span>
          </div>
        </div>

        <div class="card-footer bg-white">
          <!-- Use stretched-link to make the whole card clickable on click (not on hover) -->
          <button
            class="btn btn-sm btn-outline-primary w-100 view-profile-btn position-relative"
            type="button"
            data-id="${escapeHtml(id)}"
          >
            View Profile
          </a>
          <a class="btn btn-sm btn-outline-primary w-100 hire-me-btn position-relative stretched-link col-6 mt-2"
            href="/profile.html?id=${encodeURIComponent(id)}">
            Hire Me
          </a>
        </div>
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
        updateURL(params, { push: true });          // NEW: push history
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

    // (Optional) Could add first/last with ellipsis if many pages
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
      setLoading(true); // NEW: loading state
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
    updateURL(p, { push: true }); // NEW
    refresh(p);
  });

  document.getElementById('resetFilters').addEventListener('click', (e) => {
    e.preventDefault();
    searchForm.reset();
    filtersForm.reset();
    const p = new URLSearchParams();
    p.set('page', '1');
    p.set('limit', String(PAGE_SIZE_DEFAULT));
    updateURL(p, { push: true }); // NEW
    refresh(p);
  });

  searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const p = paramsFromForms();
    updateURL(p, { push: true }); // NEW
    refresh(p);
  });

  // NEW: debounce filter interactions so quick toggles don't trigger many refreshes
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
  // NEW: One place to define what "View Profile" does
function openProfileById(id) {
  if (!id) return;
  openProfileModal(id); // show modal
}

  // NEW: Event delegation for clicks on the button or anywhere on the card
  grid.addEventListener('click', (e) => {
    const btn = e.target.closest('.view-profile-btn');
    const card = e.target.closest('article.hover-activator');
    if (!btn && !card) return;

    e.preventDefault();
    const host = btn ? btn.closest('article.hover-activator') : card;
    const id = host?.getAttribute('data-id');
    openProfileById(id);
  });



  // NEW: Back/forward navigation support
  window.addEventListener('popstate', () => {
    refresh(currentParams());
  });

  // --- First load ---
  (async function firstLoad() {
    const params = currentParams();
    if (!params.get('limit')) params.set('limit', String(PAGE_SIZE_DEFAULT));
    if (!params.get('page')) params.set('page', '1');

    updateURL(params, { push: false }); // CHANGE: replace on initial load only
    await refresh(params);
  })();
})();
