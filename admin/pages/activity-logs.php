<?php
$pageTitle = 'Activity Logs';
require_once '../includes/header.php';

$role = $currentUser['role'] ?? 'employee';
if ($role === 'employee') {
    setFlashMessage('error', 'You do not have permission to view activity logs.');
    redirect('dashboard.php'); exit;
}

$conn = $database->getConnection();

/* Users dropdown */
$adminUsers = [];
if ($conn instanceof mysqli) {
    $q = "SELECT id, full_name, username, role FROM admin_users";
    if ($role === 'admin') $q .= " WHERE role <> 'super_admin'";
    $q .= " ORDER BY full_name ASC";
    $adminUsers = $conn->query($q)->fetch_all(MYSQLI_ASSOC);
}

/* Logs */
$logs = [];
if ($conn instanceof mysqli) {
    $q = "
        SELECT al.*, au.full_name, au.username, au.role
        FROM activity_logs al
        JOIN admin_users au ON au.id = al.admin_id
    ";
    if ($role === 'admin') $q .= " WHERE au.role <> 'super_admin'";
    $q .= " ORDER BY al.created_at DESC LIMIT 250";
    $logs = $conn->query($q)->fetch_all(MYSQLI_ASSOC);
}

/* Enrich description with names */
function enrichDescription($d, $conn) {
    return $d; // keep short for now
}

/* Initials generator */
function initials($n,$u){
    $s = trim($n ?: $u);
    $p = explode(" ", $s);
    return strtoupper(substr($p[0]??'U',0,1) . substr($p[1]??'',0,1));
}
?>

<div class="d-flex justify-content-between mb-3">
    <div>
        <h4 class="fw-semibold">Activity Logs</h4>
        <small class="text-muted">Click a row to view full details.</small>
    </div>
    <span class="badge bg-light text-dark">Showing <?=count($logs)?> entries</span>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Filter by user</label>
                <select id="filterUser" class="form-select form-select-sm">
                    <option value="">All users</option>
                    <?php foreach($adminUsers as $u): ?>
                        <option value="<?=$u['id']?>"><?=$u['full_name'].' ('.$u['role'].')'?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label small text-muted">Search</label>
                <input id="filterSearch" type="text" class="form-control form-control-sm"
                       placeholder="action, description, user...">
            </div>

            <div class="col-md-4 text-end">
                <label class="form-label small text-muted d-block">Quick Range</label>
                <div class="btn-group btn-group-sm">
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

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="logTable">
            <thead class="table-light">
                <tr>
                    <th>User</th><th>Role</th><th>Action</th><th>Description</th><th>When</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($logs as $log): ?>
                    <?php
                        $full = $log['full_name'] ?: $log['username'];
                        $desc = enrichDescription($log['description'],$conn);
                        $ini  = initials($full,$log['username']);
                    ?>
                    <tr class="cursor-pointer"
                        data-bs-toggle="modal" data-bs-target="#logModal"
                        data-user="<?=$full?>"
                        data-username="<?=$log['username']?>"
                        data-role="<?=$log['role']?>"
                        data-action="<?=$log['action']?>"
                        data-desc="<?=htmlspecialchars($desc,ENT_QUOTES)?>"
                        data-time="<?=$log['created_at']?>"
                        data-initial="<?=$ini?>"
                    >
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center"
                                     style="width:32px;height:32px;font-size:.8rem;">
                                    <?=$ini?>
                                </div>
                                <div class="fw-semibold"><?=$full?></div>
                            </div>
                        </td>
                        <td><?=$log['role']?></td>
                        <td><span class="badge bg-primary-subtle text-primary"><?=$log['action']?></span></td>
                        <td class="text-truncate" style="max-width:450px"><?=$desc?></td>
                        <td><?=formatDateTime($log['created_at'])?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL -->
<div class="modal fade" id="logModal" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title fw-semibold">Log Details</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body space-y-3">

        <div class="d-flex items-center gap-3 mb-3">
            <div id="mAvatar"
                class="rounded-circle bg-primary text-white d-flex justify-content-center align-items-center"
                style="width:48px;height:48px;font-size:1.1rem;">
            </div>
            <div class="flex-grow">
                <div id="mUser" class="fw-bold"></div>
                <div class="text-muted small">
                    <span id="mUsername"></span> · <span id="mRole"></span>
                </div>
            </div>
        </div>

        <div>
            <div class="fw-semibold">Action</div>
            <div id="mAction" class="badge bg-primary-subtle text-primary"></div>
        </div>

        <div>
            <div class="fw-semibold">Description</div>
            <div id="mDesc" class="p-3 rounded bg-light border" style="white-space:pre-wrap;"></div>
        </div>

        <div>
            <div class="fw-semibold">Timestamp</div>
            <div id="mTime"></div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>

<script>
document.querySelectorAll("#logTable tbody tr").forEach(r=>{
  r.addEventListener("click",()=>{
    document.querySelector("#mAvatar").textContent = r.dataset.initial;
    document.querySelector("#mUser").textContent = r.dataset.user;
    document.querySelector("#mUsername").textContent = "@"+r.dataset.username;
    document.querySelector("#mRole").textContent = r.dataset.role;
    document.querySelector("#mAction").textContent = r.dataset.action;
   Content = r.dataset.desc;
    document.querySelector("#mTime").textContent = r.dataset.time;
  });
});
</script>

<?php require_once '../includes/footer.php'; ?>