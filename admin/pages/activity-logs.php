<?php
// FILE: pages/activity-logs.php
$pageTitle = 'Activity Logs';
require_once '../includes/header.php';

$role = $currentUser['role'] ?? 'employee';
$isAdminOnly = ($role === 'admin');
if ($role === 'employee') {
    setFlashMessage('error', 'You do not have permission to view activity logs.');
    redirect('dashboard.php'); exit;
}

$conn = $database->getConnection();

/* --- Users (filter) --- */
$adminUsers = [];
if ($conn instanceof mysqli) {
    $q = "SELECT id, full_name, username, role FROM admin_users";
    if ($isAdminOnly) $q .= " WHERE role <> 'super_admin'";
    $q .= " ORDER BY full_name ASC";
    if ($rs = $conn->query($q)) $adminUsers = $rs->fetch_all(MYSQLI_ASSOC);
}

/* --- Logs --- */
$logs = [];
if ($conn instanceof mysqli) {
    $q = "SELECT al.id, al.admin_id, al.action, al.description, al.created_at,
                 au.full_name, au.username, au.role
          FROM activity_logs al
          JOIN admin_users au ON au.id = al.admin_id";
    if ($isAdminOnly) $q .= " WHERE au.role <> 'super_admin'";
    $q .= " ORDER BY al.created_at DESC LIMIT 250";
    if ($rs = $conn->query($q)) $logs = $rs->fetch_all(MYSQLI_ASSOC);
}

/* --- Enrichment (compact) --- */
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
    $src = trim($full ?: $user ?: 'U');
    $p = preg_split('/\s+/', $src);
    $a = strtoupper(substr($p[0]??'U',0,1) . substr($p[1]??'',0,1));
    return $a ?: 'U';
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
        <tr>
          <th>User</th><th>Role</th><th>Action</th><th>Description</th><th>When</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$logs): ?>
        <tr><td colspan="5" class="text-center text-muted py-4">No activity has been recorded yet.</td></tr>
      <?php else: foreach($logs as $log):
          $name = $log['full_name'] ?: ($log['username'] ?: 'Unknown');
          $desc = enrichDescription((string)$log['description'], $applicantNameMap, $userNameMap);
          $ini  = initials($name, $log['username']);
          $whenPretty = formatDateTime($log['created_at']);
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
            data-log-id="<?=$log['id']?>"
            data-bs-toggle="modal" data-bs-target="#logModal"
        >
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="w-8 h-8 rounded-circle bg-indigo-600 text-white d-flex align-items-center justify-content-center fw-bold">
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
        <!-- Header -->
        <div class="d-flex gap-3 align-items-start mb-3">
          <div id="mAvatar" class="w-12 h-12 rounded-circle bg-indigo-600 text-white d-flex align-items-center justify-content-center fw-bold fs-6">U</div>
          <div class="flex-grow-1">
            <div id="mFullName" class="fw-bold"></div>
            <div class="text-muted small"><span id="mUsername"></span> · <span id="mRole"></span></div>
            <div class="mt-2"><span id="mAction" class="badge bg-primary-subtle text-primary"></span></div>
          </div>
        </div>
        <!-- Description -->
        <div class="mb-3">
          <div class="text-muted small mb-1">Description</div>
          <div id="mDesc" class="p-3 border rounded bg-light" style="white-space:pre-wrap;"></div>
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
        <button id="mCopy" type="button" class="btn btn-outline-secondary"><i class="bi bi-clipboard me-1"></i>Copy</button>
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
  const inRange=(d,range)=>{
    if(!d||range==='all') return true;
    const now=new Date(), day=86400000, diff=now-d;
    if(range==='24h') return diff<=day;
    if(range==='3d')  return diff<=3*day;
    if(range==='7d')  return diff<=7*day;
    if(range==='30d') return diff<=30*day;
    return true;
  };

  function apply(){
    const uid=fUser?.value||'', q=(fSearch?.value||'').toLowerCase().trim();
    const r=document.querySelector('[data-range].active')?.dataset.range||'all';
    tbody.querySelectorAll('tr').forEach(tr=>{
      const rowUid=tr.dataset.userId||'';
      const whenRaw=tr.dataset.whenRaw||'';
      const okUser=!uid || uid===rowUid;
      const okText=!q || tr.textContent.toLowerCase().includes(q);
      const okTime=inRange(parseDate(whenRaw), r);
      tr.style.display=(okUser&&okText&&okTime)?'':'none';
    });
  }

  fUser?.addEventListener('change', apply);
  fSearch?.addEventListener('input', ()=>{ clearTimeout(window._flt); window._flt=setTimeout(apply,150); });
  rangeBtns.forEach(b=>b.addEventListener('click',()=>{ rangeBtns.forEach(x=>x.classList.remove('active')); b.classList.add('active'); apply(); }));
})();

// Modal population
(function(){
  const modalEl=document.getElementById('logModal'); if(!modalEl) return;
  const mAvatar=document.getElementById('mAvatar'), mFull=document.getElementById('mFullName');
  const mUser=document.getElementById('mUsername'), mRole=document.getElementById('mRole');
  const mAction=document.getElementById('mAction'), mDesc=document.getElementById('mDesc');
  const mWhen=document.getElementById('mWhen'), mWhenRaw=document.getElementById('mWhenRaw');
  const mLogId=document.getElementById('mLogId'), mAdminId=document.getElementById('mAdminId');
  const mCopy=document.getElementById('mCopy');

  document.querySelectorAll('#logTable tbody tr').forEach(tr=>{
    tr.addEventListener('click',()=>{
      mAvatar.textContent = tr.dataset.initials || 'U';
      mFull.textContent   = tr.dataset.fullName || '';
      mUser.textContent   = tr.dataset.username ? '@'+tr.dataset.username : '';
      mRole.textContent   = (tr.dataset.role || '—').replaceAll('_',' ');
      mAction.textContent = tr.dataset.action || '';
      mDesc.textContent   = tr.dataset.desc || '';
      mWhen.textContent   = tr.dataset.when || '';
      mWhenRaw.textContent= tr.dataset.whenRaw ? '('+tr.dataset.whenRaw+')' : '';
      mLogId.textContent  = tr.dataset.logId || '';
      mAdminId.textContent= tr.dataset.userId || '';
    });
  });

  mCopy?.addEventListener('click',()=>{
    const txt=mDesc?.textContent||'';
    if(!txt) return;
    navigator.clipboard.writeText(txt).then(()=>{
      mCopy.classList.replace('btn-outline-secondary','btn-success');
      mCopy.innerHTML='<i class="bi bi-check-lg me-1"></i>Copied';
      setTimeout(()=>{ mCopy.classList.replace('btn-success','btn-outline-secondary'); mCopy.innerHTML='<i class="bi bi-clipboard me-1"></i>Copy'; },1000);
    });
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>
