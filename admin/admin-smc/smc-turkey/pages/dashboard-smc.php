<?php
// dashboard-smc.php (SMC)
// Same UX as CSNK dashboard, but SMC theme (dark navy + gold) and SMC-scoped data.

// ---------- SAFE INCLUDES (with fallbacks) ----------
$tryIncludes = [
  // Local SMC includes (one level up from pages/)
  realpath(__DIR__ . '/../includes'),
  // Global admin includes (three levels up from pages/)
  realpath(__DIR__ . '/../../../includes'),
];

// Header (required in both stacks)
$headerIncluded = false;
foreach ($tryIncludes as $inc) {
  if ($inc && file_exists($inc . '/header.php')) {
    require_once $inc . '/header.php';
    $headerIncluded = true;
    break;
  }
}
if (!$headerIncluded) {
  die("Fatal: header.php not found in expected include paths.");
}

// Applicant (SMC-scoped version should be findable under SMC includes first)
$applicantIncluded = false;
foreach ($tryIncludes as $inc) {
  if ($inc && file_exists($inc . '/Applicant.php')) {
    require_once $inc . '/Applicant.php';
    $applicantIncluded = true;
    break;
  }
}
if (!$applicantIncluded) {
  die("Fatal: Applicant.php not found in expected include paths.");
}

// Admin (may exist only in global admin/includes)
$adminIncluded = false;
foreach ($tryIncludes as $inc) {
  if ($inc && file_exists($inc . '/Admin.php')) {
    require_once $inc . '/Admin.php';
    $adminIncluded = true;
    break;
  }
}
if (!$adminIncluded) {
  die("Fatal: Admin.php not found in expected include paths.");
}

// ---------- LIVE DATA (SMC-SCOPED VIA Applicant.php) ----------
$applicant = new Applicant($database);  // This file should be SMC-scoped (ag.code='smc') as we implemented
$admin     = new Admin($database);

$stats            = $applicant->getStatistics();           // total, pending, on_process, deleted (SMC-only)
$recentApplicants = array_slice($applicant->getAll(), 0, 5);
// Only count admin and employee accounts (exclude super_admin)
$adminCount       = count($admin->getAll(true));

// Role flags from header.php
$currentRole   = $currentUser['role'] ?? 'employee';
$isSuperAdmin  = ($currentRole === 'super_admin');
$isAdmin       = ($currentRole === 'admin');
$canSeeAdminUX = ($isAdmin || $isSuperAdmin);

/* ---------------------- Recent activity logs ---------------------- *
 * Admin:    hide super_admin logs + remove unknowns (INNER JOIN).
 * SuperAdmin: show all but still remove unknowns (INNER JOIN).
 * (No agency-scoping here because activity_logs has no agency field)
 */
$recentActivities = [];
if (!empty($currentUser) && $canSeeAdminUX) {
    $conn = $database->getConnection();
    if ($conn instanceof mysqli) {
        $sql = "
            SELECT al.id,
                   al.action,
                   al.description,
                   al.created_at,
                   au.full_name,
                   au.username,
                   au.role
            FROM activity_logs AS al
            INNER JOIN admin_users AS au ON al.admin_id = au.id
        ";
        if ($isAdmin) {
            $sql .= " WHERE COALESCE(au.role,'') <> 'super_admin' ";
        }
        $sql .= " ORDER BY al.created_at DESC LIMIT 8";

        if ($result = $conn->query($sql)) {
            $recentActivities = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

/* ---------------------- Blacklisted Applicants (SMC-only) ---------------------- *
 * We scope to SMC by joining applicants -> business_units -> agencies and ag.code='smc'
 */
$blacklistedApplicants = [];
$blacklistedCount      = 0;
if (!empty($currentUser) && $canSeeAdminUX) {
    $conn = $database->getConnection();
    if ($conn instanceof mysqli) {
        // Count (SMC-only)
        $countSql = "
            SELECT COUNT(*)
            FROM blacklisted_applicants b
            INNER JOIN applicants a ON a.id = b.applicant_id
            INNER JOIN business_units bu ON bu.id = a.business_unit_id
            INNER JOIN agencies ag ON ag.id = bu.agency_id
            WHERE b.is_active = 1
              AND ag.code = 'smc'
        ";
        if ($countResult = $conn->query($countSql)) {
            $blacklistedCount = (int)$countResult->fetch_row()[0];
        }

        // Recent active blacklists (SMC-only)
        $sql = "
            SELECT
                b.id,
                b.applicant_id,
                b.reason,
                b.issue,
                b.created_at,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.suffix,
                au.full_name AS created_by_name,
                au.username   AS created_by_username
            FROM blacklisted_applicants b
            INNER JOIN applicants     a  ON a.id = b.applicant_id
            INNER JOIN business_units bu ON bu.id = a.business_unit_id
            INNER JOIN agencies       ag ON ag.id = bu.agency_id
            LEFT  JOIN admin_users    au ON au.id = b.created_by
            WHERE b.is_active = 1
              AND ag.code = 'smc'
            ORDER BY b.created_at DESC
            LIMIT 5
        ";
        if ($result = $conn->query($sql)) {
            $blacklistedApplicants = $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}

// ---------------------- HELPERS ---------------------- //
function safe(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function dash_if_blank($val): string {
  if ($val === null || $val === '' || (is_numeric($val) && (int)$val === 0 && $val !== '0')) return '—';
  return (string)$val;
}
?>

<!-- Tailwind utilities layered on top of Bootstrap -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { theme:{ extend:{} } };
</script>

<style>
  /* ===================== ENTITY BOX THEME ONLY (SMC) ===================== */
  .stat-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(0,0,0,.12);
    background: radial-gradient(1200px 320px at -20% -20%, rgba(243,217,139,.35) 0%, #0b1d3a 45%, #0a1220 100%);
    color: #f7f7f8;
  }
  .stat-card .title { letter-spacing: .06em; opacity: .85; }
  .stat-card .icon-faint { color: #d4af37; opacity: .9; }
  .stat-card .big { font-weight: 800; font-size: 2.25rem; line-height: 1.1; }
  .stat-chip {
    display:flex; align-items:center; gap:.5rem;
    padding:.45rem .85rem; border-radius: 999px;
    background: rgba(212,175,55,.08); color: #f3d98b;
    border:1px solid rgba(212,175,55,.28);
  }
  .soft-divider { height:1px; background:#eef2f7; }
  .table-hover tbody tr:hover { background-color: rgba(0,0,0,.035); }
  .badge.bg-primary-subtle {
    background-color: rgba(13,110,253,.08) !important;
    color: #0d6efd !important;
    border: 1px solid rgba(13,110,253,.18) !important;
  }
  .badge.bg-danger-subtle {
    background-color: rgba(220,53,69,.08) !important;
    color: #dc3545 !important;
    border: 1px solid rgba(220,53,69,.18) !important;
  }
</style>

<!-- ======= STATS GRID (live SMC counts) ======= -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
  <!-- Total Applicants -->
  <div class="stat-card">
    <div class="p-5">
      <div class="flex items-center justify-between">
        <h6 class="title uppercase">TOTAL APPLICANTS</h6>
        <i class="bi bi-people text-3xl icon-faint"></i>
      </div>
      <div class="mt-3 big"><?php echo dash_if_blank((string)($stats['total'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Active pool</span>
      </div>
    </div>
  </div>

  <!-- Pending -->
  <div class="stat-card">
    <div class="p-5">
      <div class="flex items-center justify-between">
        <h6 class="title uppercase">PENDING</h6>
        <i class="bi bi-clock-history text-3xl icon-faint"></i>
      </div>
      <div class="mt-3 big"><?php echo dash_if_blank((string)($stats['pending'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Awaiting review</span>
      </div>
    </div>
  </div>

  <!-- On Process -->
  <div class="stat-card">
    <div class="p-5">
      <div class="flex items-center justify-between">
        <h6 class="title uppercase">ON PROCESS</h6>
        <i class="bi bi-hourglass-split text-3xl icon-faint"></i>
      </div>
      <div class="mt-3 big"><?php echo dash_if_blank((string)($stats['on_process'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Actively handled</span>
      </div>
    </div>
  </div>

  <!-- Deleted -->
  <div class="stat-card">
    <div class="p-5">
      <div class="flex items-center justify-between">
        <h6 class="title uppercase">DELETED</h6>
        <i class="bi bi-trash text-3xl icon-faint"></i>
      </div>
      <div class="mt-3 big"><?php echo dash_if_blank((string)($stats['deleted'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Soft removed</span>
      </div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- ===================== Recent Applicants (SMC) ===================== -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border">
    <div class="px-5 pt-5 pb-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-0 fw-semibold">Recent Applicants</h5>
          <small class="text-muted">Latest profiles created in the system.</small>
        </div>
      </div>
    </div>
    <div class="soft-divider"></div>
    <div class="p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-white">
            <tr>
              <th>Name</th>
              <th>Phone</th>
              <th>Status</th>
              <th>Date Applied</th>
            </tr>
          </thead>
          <tbody>
          <?php if (empty($recentApplicants)): ?>
            <tr>
              <td colspan="4" class="text-center text-muted py-4">No applicants yet</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentApplicants as $applicantData): ?>
              <?php
                $statusColors = ['pending'=>'warning','on_process'=>'info','approved'=>'success'];
                $badgeColor   = $statusColors[$applicantData['status']] ?? 'secondary';
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <?php if (!empty($applicantData['picture'])): ?>
                      <img src="<?php echo safe(getFileUrl($applicantData['picture'])); ?>" alt="Profile"
                           class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
                    <?php else: ?>
                      <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                           style="width: 40px; height: 40px;">
                        <?php echo strtoupper(substr($applicantData['first_name'] ?? '', 0, 1)); ?>
                      </div>
                    <?php endif; ?>
                    <strong>
                      <?php echo safe(getFullName(
                        $applicantData['first_name'] ?? '',
                        $applicantData['middle_name'] ?? '',
                        $applicantData['last_name'] ?? '',
                        $applicantData['suffix'] ?? ''
                      )); ?>
                    </strong>
                  </div>
                </td>
                <td class="text-muted">
                  <?php echo safe($applicantData['phone_number'] ?? '—'); ?>
                </td>
                <td>
                  <span class="badge bg-<?php echo $badgeColor; ?>">
                    <?php echo safe(ucfirst(str_replace('_', ' ', $applicantData['status'] ?? ''))); ?>
                  </span>
                </td>
                <td class="text-muted">
                  <?php echo safe(formatDate($applicantData['created_at'] ?? '')); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ===================== Right Column ===================== -->
  <div class="flex flex-col gap-4">
    <!-- Quick Stats Card -->
    <div class="bg-white rounded-2xl shadow-sm border">
      <div class="px-5 pt-5 pb-3">
        <h5 class="mb-0 fw-semibold">Quick Stats</h5>
      </div>
      <div class="soft-divider"></div>
      <div class="p-5 pt-4">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted">Staff (Admin &amp; Employee)</span>
          <strong><?php echo dash_if_blank((string)$adminCount); ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted">Active Applicants</span>
          <strong><?php echo dash_if_blank((string)($stats['total'] ?? '')); ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="text-muted">System Status</span>
          <span class="badge bg-success">Online</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-2xl shadow-sm border">
      <div class="px-5 pt-5 pb-3">
        <h5 class="mb-0 fw-semibold">Quick Actions</h5>
      </div>
      <div class="soft-divider"></div>
      <div class="p-5 pt-4">
        <a href="add-applicant.php" class="btn btn-primary w-100 mb-2">
          <i class="bi bi-plus-circle me-2"></i>Add New Applicant
        </a>
        <a href="applicants.php" class="btn btn-outline-primary w-100 mb-2">
          <i class="bi bi-people me-2"></i>View All Applicants
        </a>
        <a href="accounts.php" class="btn btn-outline-secondary w-100">
          <i class="bi bi-person-plus me-2"></i>Add New Account
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ===================== Recent Activity (Role-gated) ===================== -->
<?php if (!empty($recentActivities) && $canSeeAdminUX): ?>
  <div class="mt-4 bg-white rounded-2xl shadow-sm border">
    <div class="px-5 py-4 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0 fw-semibold">Recent Activity</h5>
        <small class="text-muted">Latest actions by admins and employees across the system.</small>
      </div>
      <a href="activity-logs.php" class="btn btn-sm btn-outline-secondary">
        View all logs
      </a>
    </div>
    <div class="soft-divider"></div>
    <div class="p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-white">
            <tr>
            
<!-- ===================== Blacklisted Applicants (Role-gated) ===================== -->
<?php if ($canSeeAdminUX): ?>
  <div class="mt-4 bg-white rounded-2xl shadow-sm border">
    <div class="px-5 py-4 d-flex justify-content-between align-items-center">
      <div>
        <div class="text-center py-5">
          <i class="bi bi-check-circle" style="font-size: 3rem; color:#198754;"></i>
          <p class="text-muted mt-3 mb-0">No blacklisted applicants at this time.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-white">
              <tr>
                <th style="walign-items-center justify-content-center me-2"
                           style="width: 36px; height: 36px;">
                        <i class="bi bi-person-x text-danger"></i>
                      <
<script>
  // Refresh every 60s to mirror original UX
  setInterval(function(){ location.reload(); }, 60000);
</script>

<?php require_once $tryIncludes[0] . '/footer.php'; // prefer local footer if available, else header already fatal'd above ?>