
// FILE: admin/js/turkey_replacements.js (SMC Turkey Version)
window.TurkeyReplacements = (function () {
  const endpoints = {
    init:    'turkey_replace-init.php',
    search:  'turkey_replace-search.php',
    assign:  'turkey_replace-assign.php',
  };

  function toast(msg, type = 'info', timeout = 2500) {
    try {
      const el = document.createElement('div');
      el.className = 'position-fixed top-0 start-50 translate-middle-x p-2';
      el.style.zIndex = 20000;
      el.innerHTML = '<div class="alert alert-' + type + ' shadow mb-0 py-2 px-3">' + escapeHtml(msg) + '</div>';
      document.body.appendChild(el);
      setTimeout(function() { el.remove(); }, timeout);
    } catch (e) { alert(msg); }
  }

  function escapeHtml(s) {
    if (!s) return '';
    return s.replace(/[&<>"']/g, function(c) {
      return {'&':'&amp;','<':'<','>':'>','"':'"',"'":'&#039;'}[c] || c;
    });
  }

  function renderCandidates(container, list, replacementId) {
    if (!container) return;
    if (!Array.isArray(list) || list.length === 0) {
      container.innerHTML = '<div class="text-muted">No matching pending candidates found.</div>';
      return;
    }
    var cards = list.map(function(item) {
      var photo = item.picture_url
        ? '<img src="' + escapeHtml(item.picture_url) + '" alt="" class="rounded me-2" style="width:48px;height:48px;object-fit:cover;">'
        : '<div class="rounded bg-secondary text-white d-flex align-items-center justify-content-center me-2" style="width:48px;height:48px;">' + escapeHtml((item.first_name||'').slice(0,1).toUpperCase()) + '</div>';

      var skills = (item.specialization_skills || []).slice(0,6).map(function(s) { return '<span class="badge bg-light text-danger border">' + escapeHtml(s) + '</span>'; }).join(' ');
      var cities = (item.preferred_location || []).slice(0,6).map(function(c) { return '<span class="badge bg-light text-primary border">' + escapeHtml(c) + '</span>'; }).join(' ');

      return '<div class="border rounded p-2 mb-2 d-flex align-items-center">' +
        photo +
        '<div class="flex-grow-1">' +
          '<div class="fw-semibold">' + escapeHtml(item.full_name) + '</div>' +
          '<div class="small text-muted">' +
            (item.email ? escapeHtml(item.email) : '—') + ' • ' + (item.phone_number ? escapeHtml(item.phone_number) : '—') +
          '</div>' +
          '<div class="mt-1 small">' +
            '<span class="badge bg-secondary-subtle text-dark border me-1">Score: ' + item._score + '</span>' +
            '<span class="badge bg-secondary-subtle text-dark border">Experience: ' + item.years_experience + ' yr(s)</span>' +
          '</div>' +
          '<div class="mt-2 d-flex flex-wrap gap-1">' + skills + '</div>' +
          '<div class="mt-1 d-flex flex-wrap gap-1">' + cities + '</div>' +
        '</div>' +
        '<div class="ms-2">' +
          '<button class="btn btn-sm btn-success" data-assign data-candidate-id="' + item.id + '" data-replacement-id="' + replacementId + '">' +
            '<i class="bi bi-check2-circle me-1"></i>Assign' +
          '</button>' +
        '</div>' +
      '</div>';
    }).join('');
    container.innerHTML = cards;

    container.querySelectorAll('button[data-assign]').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var repId = parseInt(btn.getAttribute('data-replacement-id') || '0', 10);
        var cand = parseInt(btn.getAttribute('data-candidate-id') || '0', 10);
        assign(repId, cand, btn);
      });
    });
  }

  function searchCandidates(targetContainer, params) {
    if (!targetContainer) return;
    targetContainer.innerHTML = '<div class="text-muted">Finding best candidates…</div>';
    var url = new URL(endpoints.search, window.location.href);
    Object.entries(params || {}).forEach(function(kv) {
      url.searchParams.set(kv[0], String(kv[1]));
    });

    fetch(url.toString(), { credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.ok) throw new Error(data.message || 'Search failed.');
        renderCandidates(targetContainer, data.data, params.replacement_id);
      })
      .catch(function(err) {
        targetContainer.innerHTML = '<div class="text-danger">' + escapeHtml(err.message || 'Search failed') + '</div>';
      });
  }

  function assign(replacementId, candidateId, buttonEl) {
    if (!replacementId || !candidateId) return;
    var fd = new FormData();
    fd.append('replacement_id', String(replacementId));
    fd.append('replacement_applicant_id', String(candidateId));
    fd.append('ajax', '1');
    if (window.CSRF_TOKEN) fd.append('csrf_token', String(window.CSRF_TOKEN));

    if (buttonEl) { buttonEl.disabled = true; buttonEl.textContent = 'Assigning…'; }
    fetch(endpoints.assign, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (!data.ok) throw new Error(data.message || 'Failed to assign.');
        toast('Replacement assigned.', 'success');
        setTimeout(function() { window.location.reload(); }, 800);
      })
      .catch(function(err) {
        toast(err.message || 'Failed to assign.', 'danger', 3000);
      })
      .finally(function() {
        if (buttonEl) { buttonEl.disabled = false; buttonEl.textContent = 'Assign'; }
      });
  }

  function bindInit(formSelector, resultsContainerSelector) {
    var form = document.querySelector(formSelector);
    var container = document.querySelector(resultsContainerSelector);
    if (!form) return;

    form.addEventListener('submit', function(e) {
      e.preventDefault();
      var fd = new FormData(form);
      fd.append('ajax', '1');
      if (window.CSRF_TOKEN) fd.append('csrf_token', String(window.CSRF_TOKEN));

      var submitBtn = form.querySelector('[type="submit"]');
      var oldText = submitBtn ? submitBtn.innerHTML : '';
      if (submitBtn) { submitBtn.disabled = true; submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting…'; }

      fetch(endpoints.init, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (!data.ok) throw new Error(data.message || 'Failed to start replacement.');
          var repId = data.replacement_id;
          toast('Replacement created. Loading candidates…', 'success');
          searchCandidates(container, { replacement_id: repId });
        })
        .catch(function(err) {
          toast(err.message || 'Failed to start replacement.', 'danger', 3500);
        })
        .finally(function() {
          if (submitBtn) { submitBtn.disabled = false; submitBtn.innerHTML = oldText; }
        });
    });
  }

  return { bindInit: bindInit, search: searchCandidates, assign: assign };
})();

