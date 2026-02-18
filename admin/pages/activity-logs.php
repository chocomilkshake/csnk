<?php
// FILE: pages/activity-logs.php
$pageTitle = 'Activity Logs';
require_once '../includes/header.php';

$role = $currentUser['role'] ?? 'employee';
$isAdminOnly = ($role === 'admin');
if ($role === 'employee') { setFlashMessage('error','You do not have permission to view activity logs.'); redirect('dashboard.php'); exit; }

$conn = $database->getConnection();

/* Users for filter */
$adminUsers = [];
if ($conn instanceof mysqli) {
    $q = "SELECT id, full_name, username, role, avatar FROM admin_users";
    if ($isAdminOnly) $q .= " WHERE role <> 'super_admin'";
    $q .= " ORDER BY full_name ASC";
    if ($rs = $conn->query($q)) $adminUsers = $rs->fetch_all(MYSQLI_ASSOC);
}

/* Logs */
$logs = [];
if ($conn instanceof mysqli) {
    $q = "SELECT al.id, al.admin_id, al.action, al.description, al.created_at,
                 au.full_name, au.username, au.role, au.avatar
          FROM activity_logs al
          JOIN admin_users au ON au.id = al.admin_id";
    if ($isAdminOnly) $q .= " WHERE au.role <> 'super_admin'";
    $q .= " ORDER BY al.created_at DESC LIMIT 250";
    if ($rs = $conn->query($q)) $logs = $rs->fetch_all(MYSQLI_ASSOC);
}

/* Enrichment (compact) */
$applicantNameMap = $userNameMap = [];
if ($conn instanceof mysqli && $logs) {
    $candA = $candU = [];
    foreach ($logs as $lg) {
        $d = (string)($lg['description'] ?? '');
        if (preg_match_all('/\b(Applicant|applicant)[^0-9]{0,30}ID[:\s#]*([0-9]+)\b/', $d, $m, PREG_SET_ORDER))
            foreach ($m as $x) $candA[(int)$x[2]] = true;
        if (preg_match_all('/\b(Admin|User|admin|user)[^0-9]{0,30}ID[:\s#]*([0-9]+)\b/', $d, $m2, PREG_SET_ORDER))
            foreach ($m2 as $x) $candU[(int)$x[2]] = true;
    }
    if ($candA) {
        $ids = implode(',', array_map('intval', array_keys($candA)));
        if ($rs = $conn->query("SELECT id, first_name, middle_name, last_name, suffix FROM applicants WHERE id IN ($ids)")) {
            while ($r = $rs->fetch_assoc()) {
                $applicantNameMap[(int)$r['id']] = getFullName($r['first_name']??'', $r['middle_name']??'', $r['last_name']??'', $r['suffix']??'');
            }
        }
    }
    if ($candU) {
        $ids = implode(',', array_map('intval', array_keys($candU)));
        if ($rs = $conn->query("SELECT id, full_name, username FROM admin_users WHERE id IN ($ids)")) {
            while ($r = $rs->fetch_assoc()) {
                $nm = trim($r['full_name'] ?? '') ?: ($r['username'] ?? ('User #'.(int)$r['id']));
                $userNameMap[(int)$r['id']] = $nm;
            }
        }
    }
}
function enrichDescription(string $desc, array $appMap, array $userMap): string {
    $desc = preg_replace_callback('/\b(Applicant|applicant)([^0-9]{0,30}ID[:\s#]*)([0-9]+)\b/',
        fn($m)=> isset($appMap[(int)$m[3]]) ? ($m[1].$m[2].$m[3].' — '.$appMap[(int)$m[3]]) : $m[0], $desc);
    $desc = preg_replace_callback('/\b(Admin|User|admin|user)([^0-9]{0,30}ID[:\s#]*)([0-9]+)\b/',
        fn($m)=> isset($userMap[(int)$m[3]]) ? ($m[1].$m[2].$m[3].' — '.$userMap[(int)$m[3]]) : $m[0], $desc);
    return $desc;
}
function initials(string $full=null, string $user=null): string {
    $src = trim($full ?: $user ?: 'U'); $p = preg_split('/\s+/', $src);
    $a = strtoupper(substr($p[0]??'U',0,1) . substr($p[1]??'',0,1)); return $a ?: 'U';
}
?>
<!-- Header -->
<div class="flex items-center justify-between mb-4">
  <div>
    <h4 class="mb-0 fw-semibold">Activity Logs</h4>
    <p class="text-muted mb-0 text-sm">Click a row to view full details.</p>
  </div>
  <span class="badge bg-light text-dark">Showing <?=count($logs)?> entries</span>
</div>

<!-- Filters -->
<div class="card mb-3">
  <div class="card-body">
    <div class="row g-3 items-end">
      <div class="col-md-4">
        <label class="form-label text-muted text-sm mb-1">Filter by user</label>
        <select id="fUser" class="form-select form-select-sm">
          <option value="">All users</option>
          <?php foreach($adminUsers as $u): ?>
            <option value="<?=$u['id']?>"><?=htmlspecialchars(($u['full_name']?:$u['username']).' ('.$u['role'].')',ENT_QUOTES,'UTF-8')?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label text-muted text-sm mb-1">Search</label>
        <input id="fSearch" type="text" class="form-control form-control-sm" placeholder="action, description, user...">
      </div>
      <div class="col-md-4 text-md-end">
        <label class="form-label text-muted text-sm mb-1 d-block">Quick range</label>
        <div class="btn-group btn-group-sm" role="group">
          <button class="btn btn-outline-secondary active" data-range="all">All</button>
          <button class="btn btn-outline-secondary" data-range="24h">24h</button>
          <button class="btn btn-outline-secondary" data-range="3d">3d</button>
          <button class="btn btn-outline-secondary" data-range="7d">7d</button>
          <button class="btn btn-outline-secondary" data-range="30d">30d</button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Table -->
<div class="card">
  <div class="table-responsive">
    <table class="table align-middle table-hover mb-0" id="logTable">
      <thead class="table-light">
        <tr><th>User</th><th>Role</th><th>Action</th><th>Description</th><th>When</th></tr>
      </thead>
      <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No activity has been recorded yet.</td></tr>
      <?php else: foreach($logs as $log):
          $name = $log['full_name'] ?: ($log['username'] ?: 'Unknown');
          $desc = enrichDescription((string)$log['description'], $applicantNameMap, $userNameMap);
          $ini  = initials($name, $log['username']);
          $whenPretty = formatDateTime($log['created_at']);
          $avatarUrl = (!empty($log['avatar']) ? UPLOAD_URL . 'avatars/' . $log['avatar'] : '');
      ?>
        <tr class="hover:bg-slate-50 cursor-pointer"
            data-user-id="<?=$log['admin_id']?>"
            data-full-name="<?=htmlspecialchars($name,ENT_QUOTES,'UTF-8')?>"
            data-username="<?=htmlspecialchars($log['username']??'',ENT_QUOTES,'UTF-8')?>"
            data-role="<?=htmlspecialchars($log['role']??'—',ENT_QUOTES,'UTF-8')?>"
            data-action="<?=htmlspecialchars($log['action']??'',ENT_QUOTES,'UTF-8')?>"
            data-desc="<?=htmlspecialchars($desc,ENT_QUOTES,'UTF-8')?>"
            data-when="<?=htmlspecialchars($whenPretty,ENT_QUOTES,'UTF-8')?>"
            data-when-raw="<?=htmlspecialchars($log['created_at']??'',ENT_QUOTES,'UTF-8')?>"
            data-initials="<?=htmlspecialchars($ini,ENT_QUOTES,'UTF-8')?>"
            data-avatar="<?=htmlspecialchars($avatarUrl,ENT_QUOTES,'UTF-8')?>"
            data-log-id="<?=$log['id']?>"
            data-bs-toggle="modal" data-bs-target="#logModal">
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="rounded-circle bg-gradient d-flex align-items-center justify-content-center text-white fw-bold"
                   style="--tw-gradient-from:#4f46e5;--tw-gradient-to:#06b6d4;background-image:linear-gradient(135deg,var(--tw-gradient-from),var(--tw-gradient-to));width:40px;height:40px;">
                <?=$ini?>
              </div>
              <div class="leading-tight">
                <div class="fw-semibold"><?=htmlspecialchars($name,ENT_QUOTES,'UTF-8')?></div>
                <div class="text-muted small">@<?=htmlspecialchars($log['username']??'',ENT_QUOTES,'UTF-8')?></div>
              </div>
            </div>
          </td>
          <td><span class="badge bg-light text-dark text-capitalize"><?=htmlspecialchars(str_replace('_',' ',$log['role']??'—'),ENT_QUOTES,'UTF-8')?></span></td>
          <td><span class="badge bg-primary-subtle text-primary"><?=htmlspecialchars($log['action'],ENT_QUOTES,'UTF-8')?></span></td>
          <td class="text-truncate" style="max-width:560px" title="<?=htmlspecialchars($desc,ENT_QUOTES,'UTF-8')?>"><?=htmlspecialchars($desc,ENT_QUOTES,'UTF-8')?></td>
          <td><?=htmlspecialchars($whenPretty,ENT_QUOTES,'UTF-8')?></td>
        </tr>
      <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0">
        <h5 class="modal-title fw-semibold">Log Details</h5>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body pt-0">
        <!-- Admin -->
        <div class="d-flex gap-3 align-items-start mb-3">
          <div class="rounded-circle overflow-hidden" style="width:56px;height:56px;">
            <img id="mAvatarImg" src="" alt="" class="w-100 h-100 object-fit-cover d-none">
            <div id="mAvatarIni"
                 class="w-100 h-100 d-flex align-items-center justify-content-center text-white fw-bold"
                 style="background-image:linear-gradient(135deg,#4f46e5,#06b6d4);">U</div>
          </div>
          <div class="flex-grow-1">
            <div id="mFullName" class="fw-bold"></div>
            <div class="text-muted small"><span id="mUsername"></span> · <span id="mRole"></span></div>
          </div>
        </div>

        <!-- Action & Note (clearly separated) -->
        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="text-muted small mb-1">Action Performed</div>
            <div id="mAction" class="badge bg-primary-subtle text-primary px-3 py-2"></div>
          </div>
          <div class="col-md-6">
            <div class="text-muted small mb-1">Admin Note</div>
            <div id="mNote" class="p-3 border rounded bg-light" style="min-height:48px;white-space:pre-wrap;"></div>
          </div>
        </div>

        <!-- Summary (remaining description text) -->
        <div class="mb-3">
          <div class="text-muted small mb-1">Summary</div>
          <div id="mSummary" class="p-3 border rounded bg-light" style="white-space:pre-wrap;"></div>
        </div>

        <!-- Meta -->
        <div class="row g-3">
          <div class="col-md-4">
            <div class="text-muted small mb-1">When</div>
            <div id="mWhen" class="fw-semibold"></div>
            <div id="mWhenRaw" class="text-muted small"></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Log ID</div>
            <div id="mLogId" class="fw-semibold"></div>
          </div>
          <div class="col-md-4">
            <div class="text-muted small mb-1">Admin ID</div>
            <div id="mAdminId" class="fw-semibold"></div>
          </div>
        </div>
      </div>

      <div class="modal-footer border-0">
        <button id="mCopy" type="button" class="btn btn-outline-secondary"><i class="bi bi-clipboard me-1"></i>Copy Summary</button>
        <button class="btn btn-primary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
// Filters
(function(){
  const tbody=document.querySelector('#logTable tbody'); if(!tbody) return;
  const fUser=document.getElementById('fUser'), fSearch=document.getElementById('fSearch');
  const rangeBtns=[...document.querySelectorAll('[data-range]')];
  const parseDate=v=>{ if(!v) return null; const d=new Date((v+'').replace(' ','T')+'Z'); return isNaN(d)?null:d; };
  const inRange=(d,range)=>{ if(!d||range==='all') return true; const now=new Date(), day=86400000, diff=now-d;
    if(range==='24h') return diff<=day; if(range==='3d') return diff<=3*day; if(range==='7d') return diff<=7*day; if(range==='30d') return diff<=30*day; return true; };
  function apply(){ const uid=fUser?.value||'', q=(fSearch?.value||'').toLowerCase().trim(),
    r=document.querySelector('[data-range].active')?.dataset.range||'all';
    tbody.querySelectorAll('tr').forEach(tr=>{
      const okUser=!uid || uid===tr.dataset.userId;
      const okText=!q || tr.textContent.toLowerCase().includes(q);
      const okTime=inRange(parseDate(tr.dataset.whenRaw||''), r);
      tr.style.display=(okUser&&okText&&okTime)?'':'none';
    });
  }
  fUser?.addEventListener('change', apply);
  fSearch?.addEventListener('input', ()=>{ clearTimeout(window._flt); window._flt=setTimeout(apply,150); });
  rangeBtns.forEach(b=>b.addEventListener('click',()=>{ rangeBtns.forEach(x=>x.classList.remove('active')); b.classList.add('active'); apply(); }));
})();

// Modal population (with avatar + action/note split)
(function(){
  const mImg=document.getElementById('mAvatarImg'), mIni=document.getElementById('mAvatarIni');
  const mFull=document.getElementById('mFullName'), mUser=document.getElementById('mUsername'), mRole=document.getElementById('mRole');
  const mAction=document.getElementById('mAction'), mNote=document.getElementById('mNote'), mSummary=document.getElementById('mSummary');
  const mWhen=document.getElementById('mWhen'), mWhenRaw=document.getElementById('mWhenRaw'), mLogId=document.getElementById('mLogId'), mAdminId=document.getElementById('mAdminId');
  const mCopy=document.getElementById('mCopy');

  function splitNote(desc){
    if(!desc) return {summary:'', note:''};
    const rx = /(Reason|Note)\s*[:\-]\s*(.+)$/i;      // capture trailing reason/note
    const m = desc.match(rx);
    if (m) {
      const note = m[2].trim();
      const summary = desc.replace(rx,'').trim().replace(/[;,\.\s]+$/,'');
      return {summary, note};
    }
    return {summary: desc, note: ''};
  }

  document.querySelectorAll('#logTable tbody tr').forEach(tr=>{
    tr.addEventListener('click',()=>{
      const full = tr.dataset.fullName||'', uname = tr.dataset.username||'', role = (tr.dataset.role||'—').replaceAll('_',' ');
      const action = tr.dataset.action||'', desc = tr.dataset.desc||'', when = tr.dataset.when||'', whenRaw = tr.dataset.whenRaw||'';
      const logId = tr.dataset.logId||'', adminId = tr.dataset.userId||'';
      const ini = tr.dataset.initials||'U', avatar = tr.dataset.avatar||'';

      // Avatar: show image if provided, else initials gradient
      if (avatar) { mImg.src = avatar; mImg.classList.remove('d-none'); mIni.classList.add('d-none'); }
      else { mImg.classList.add('d-none'); mIni.classList.remove('d-none'); mIni.textContent = ini; }

      // Top identity
      mFull.textContent = full;
      mUser.textContent = uname ? '@'+uname : '';
      mRole.textContent = role;

      // Action + Note separation
      const {summary, note} = splitNote(desc);
      mAction.textContent = action || '—';
      mNote.textContent = note || '—';
      mSummary.textContent = summary || '—';

      // Meta
      mWhen.textContent = when || '—';
      mWhenRaw.textContent = whenRaw ? '('+whenRaw+')' : '';
      mLogId.textContent = logId || '—';
      mAdminId.textContent = adminId || '—';

      // Copy button copies Summary + Note (if exists)
      const copyText = (summary ? 'Summary: '+summary+'\n' : '') + (note ? 'Note: '+note : '');
      mCopy.onclick = ()=>{ if(!copyText.trim()) return; navigator.clipboard.writeText(copyText).then(()=>{
        mCopy.classList.replace('btn-outline-secondary','btn-success'); mCopy.innerHTML='<i class="bi bi-check-lg me-1"></i>Copied';
        setTimeout(()=>{ mCopy.classList.replace('btn-success','btn-outline-secondary'); mCopy.innerHTML='<i class="bi bi-clipboard me-1"></i>Copy Summary'; }, 1000);
      }); };
    });
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>