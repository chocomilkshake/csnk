// FILE: admin/js/replacements.js
window.Replacements = (function () {
  const endpoints = {
    init:    'replace-init.php',
    search:  'replace-search.php',
    assign:  'replace-assign.php',
  };

  function toast(msg, type = 'info', timeout = 2500) {
    try {
      const el = document.createElement('div');
      el.className = `position-fixed top-0 start-50 translate-middle-x p-2`;
      el.style.zIndex = 20000;
      el.innerHTML = `<div class="alert alert-${type} shadow mb-0 py-2 px-3">${escapeHtml(msg)}</div>`;
      document.body.appendChild(el);
      setTimeout(() => { el.remove(); }, timeout);
    } catch (e) { alert(msg); }
  }
  function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;', "'":'&#039;'}[c]));
  }

  function renderCandidates(container, list, replacementId) {
    if (!container) return;
    if (!Array.isArray(list) || list.length === 0) {
      container.innerHTML = `<div class="text-muted">No matching pending candidates found.</div>`;
      return;
    }
    const cards = list.map(item => {
      const photo = item.picture_url
        ? `<img src="${escapeHtml(item.picture_url)}" alt="" class="rounded me-2" style="width:48px;height:48px;object-fit:cover;">`
        : `<div class="rounded bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:48px;height:48px;">${escapeHtml((item.first_name||'').slice(0,1).toUpperCase())}</div>`;

      const skills = (item.specialization_skills || []).slice(0,6).map(s => `<span class="badge bg-light text-danger border">${escapeHtml(s)}</span>`).join(' ');
      const cities = (item.preferred_location || []).slice(0,6).map(c => `<span class="badge bg-light text-primary border">${escapeHtml(c)}</span>`).join(' ');








