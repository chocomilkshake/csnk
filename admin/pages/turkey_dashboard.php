<?php
// FILE: admin/pages/turkey_dashboard.php (SMC Dashboard)
// SMC-specific dashboard - only fetches SMC agency data

$ADMIN_ROOT = dirname(__DIR__);

// config.php already handles session_start() properly - do not call it here
require_once $ADMIN_ROOT . '/includes/config.php';
require_once $ADMIN_ROOT . '/includes/Database.php';
require_once $ADMIN_ROOT . '/includes/Auth.php';
require_once $ADMIN_ROOT . '/includes/functions.php';

$database = new Database();
$auth = new Auth($database);
$auth->requireLogin();

// Check if user has permission to view SMC data
if (!$auth->canSeeSMC()) {
  header('Location: applicants.php');
  exit;
}

// Resolve current user & role
$currentUser = $auth->getCurrentUser();
$role = isset($currentUser['role']) ? (string) $currentUser['role'] : 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin = ($role === 'admin');
$canSeeAdminUX = ($isAdmin || $isSuperAdmin);
$canSwitchAgency = $isSuperAdmin || $isAdmin;

$conn = $database->getConnection();


if (isset($_GET['switch_agency']) && $canSwitchAgency) {
  $switchTo = strtolower($_GET['switch_agency']);
  if (in_array($switchTo, ['csnk', 'smc'])) {
    $_SESSION['current_agency_view'] = $switchTo;
    $currentAgencyView = $switchTo;
  }
  // Remove the switch param from URL and redirect
  $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
  $params = $_GET;
  unset($params['switch_agency']);
  if (!empty($params)) {
    $redirectUrl .= '?' . http_build_query($params);
  }
  header('Location: ' . $redirectUrl);
  exit;
}

// Get SMC business unit IDs
$smcBuIds = [];
if ($conn instanceof mysqli) {
  $sqlSmcBus = "
        SELECT bu.id
        FROM business_units bu
        JOIN agencies ag ON ag.id = bu.agency_id
        WHERE ag.code = 'smc' AND bu.active = 1
    ";
  if ($res = $conn->query($sqlSmcBus)) {
    while ($r = $res->fetch_assoc()) {
      $smcBuIds[] = (int) $r['id'];
    }
  }
}

// Store first SMC BU ID in session
if (!empty($smcBuIds)) {
  $_SESSION['smc_bu_id'] = $smcBuIds[0];
}

// ============ GET SMC-SPECIFIC STATISTICS ============
$stats = [
  'total' => 0,
  'pending' => 0,
  'on_process' => 0,
  'deleted' => 0
];

if ($conn instanceof mysqli && !empty($smcBuIds)) {
  $placeholders = implode(',', array_fill(0, count($smcBuIds), '?'));

  // Total count (excluding deleted)
  $totalSql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND deleted_at IS NULL";
  if ($stmt = $conn->prepare($totalSql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $stats['total'] = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
  }

  // Pending count
  $pendingSql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND status = 'pending' AND deleted_at IS NULL";
  if ($stmt = $conn->prepare($pendingSql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $stats['pending'] = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
  }

  // On Process count
  $onProcessSql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND status = 'on_process' AND deleted_at IS NULL";
  if ($stmt = $conn->prepare($onProcessSql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $stats['on_process'] = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
  }

  // Deleted count
  $deletedSql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND deleted_at IS NOT NULL";
  if ($stmt = $conn->prepare($deletedSql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $stats['deleted'] = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
  }
}

// ============ GET RECENT APPLICANTS (SMC ONLY) ============
$recentApplicants = [];
if ($conn instanceof mysqli && !empty($smcBuIds)) {
  $placeholders = implode(',', array_fill(0, count($smcBuIds), '?'));
  $sql = "SELECT * FROM applicants WHERE business_unit_id IN ($placeholders) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5";
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $recentApplicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
}

// ============ GET ADMIN COUNT (SMC employees only) ============
$adminCount = 0;
if ($conn instanceof mysqli) {
  $sqlAdmin = "SELECT COUNT(*) FROM admin_users WHERE agency = 'smc' AND status = 'active'";
  if ($res = $conn->query($sqlAdmin)) {
    $adminCount = (int) $res->fetch_row()[0];
  }
}

// ============ RECENT ACTIVITY LOGS ============
$recentActivities = [];
if (!empty($currentUser) && $canSeeAdminUX && $conn instanceof mysqli) {
  $sql = "SELECT al.id, al.action, al.description, al.created_at, au.full_name, au.username, au.role
            FROM activity_logs al
            INNER JOIN admin_users au ON al.admin_id = au.id
            WHERE au.agency = 'smc'";
  if ($isAdmin) {
    $sql .= " AND COALESCE(au.role,'') <> 'super_admin'";
  }
  $sql .= " ORDER BY al.created_at DESC LIMIT 8";

  if ($result = $conn->query($sql)) {
    $recentActivities = $result->fetch_all(MYSQLI_ASSOC);
  }
}

// ============ BLACKLISTED APPLICANTS (SMC ONLY) ============
$blacklistedApplicants = [];
$blacklistedCount = 0;
if (!empty($currentUser) && $canSeeAdminUX && $conn instanceof mysqli && !empty($smcBuIds)) {
  $placeholders = implode(',', array_fill(0, count($smcBuIds), '?'));

  // Count
  $countSql = "SELECT COUNT(*) FROM blacklisted_applicants b
                 INNER JOIN applicants a ON a.id = b.applicant_id
                 WHERE a.business_unit_id IN ($placeholders) AND b.is_active = 1";
  if ($stmt = $conn->prepare($countSql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $blacklistedCount = (int) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
  }

  // List
  $sql = "SELECT b.id, b.applicant_id, b.reason, b.issue, b.created_at,
                   a.first_name, a.middle_name, a.last_name, a.suffix,
                   au.full_name AS created_by_name, au.username AS created_by_username
            FROM blacklisted_applicants b
            INNER JOIN applicants a ON a.id = b.applicant_id
            LEFT JOIN admin_users au ON au.id = b.created_by
            WHERE a.business_unit_id IN ($placeholders) AND b.is_active = 1
            ORDER BY b.created_at DESC LIMIT 5";
  if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
    $stmt->execute();
    $blacklistedApplicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
  }
}

// ============ HELPERS ============
function safe(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
function dash_if_blank($val): string
{
  if ($val === null || $val === '' || (is_numeric($val) && (int) $val === 0 && $val !== '0'))
    return '—';
  return (string) $val;
}

require_once $ADMIN_ROOT . '/includes/header.php';
?>

<!-- Tailwind utilities -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = { theme: { extend: {} } };
</script>

<style>
  .stat-card {
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 12px 28px rgba(0, 0, 0, .12);
    background: radial-gradient(1200px 320px at -20% -20%, rgba(243, 217, 139, .35) 0%, #0b1d3a 45%, #0a1220 100%);
    color: #f7f7f8;
  }

  .stat-card .title {
    letter-spacing: .06em;
    opacity: .85;
  }

    /* Agency Switcher Styles */
  .agency-switcher {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

    .agency-btn {
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    border: 2px solid transparent;
    transition: all 0.2s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
  }

  /* CSNK Button - Red Theme */
  .agency-btn-csnk {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    color: white;
  }

  /* SMC Button - Navy Blue with Yellow Text */
  .agency-btn-smc {
    background: #4d286c;
    color: #FFD84D;
  }

  .agency-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  }

  .agency-btn.active {
    border-color: white;
    box-shadow: 0 0 0 2px rgba(0,0,0,0.1);
  }

  .agency-btn:not(.active) {
    opacity: 0.7;
  }

  .stat-card .icon-faint {
    color: #d4af37;
    opacity: .9;
  }

  .stat-card .big {
    font-weight: 800;
    font-size: 2.25rem;
    line-height: 1.1;
  }

  .stat_chip {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .85rem;
    border-radius: 999px;
    background: rgba(212, 175, 55, .08);
    color: #f3d98b;
    border: 1px solid rgba(212, 175, 55, .28);
  }

  .soft-divider {
    height: 1px;
    background: #eef2f7;
  }

  .table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, .035);
  }

  .badge.bg-primary-subtle {
    background-color: rgba(13, 110, 253, .08) !important;
    color: #0d6efd !important;
    border: 1px solid rgba(13, 110, 253, .18) !important;
  }

  .badge.bg-danger-subtle {
    background-color: rgba(220, 53, 69, .08) !important;
    color: #dc3545 !important;
    border: 1px solid rgba(220, 53, 69, .18) !important;
  }
</style>

<?php if ($canSwitchAgency): ?>
<div class="agency-switcher">
  <a href="./dashboard.php" class="agency-btn agency-btn-csnk <?php echo ($currentAgencyView === 'csnk') ? 'active' : ''; ?>">
    <i class="bi bi-building"></i> CSNK Dashboard
  </a>
  <a href="./turkey_dashboard.php" class="agency-btn agency-btn-smc <?php echo ($currentAgencyView === 'smc') ? 'active' : ''; ?>">
    <i class="bi bi-globe"></i> SMC Dashboard
  </a>
</div>
<?php endif; ?>

<!-- ======= STATS GRID (CSNK or SMC based on view) ======= -->
<?php 
// Determine which data to display
$displayStats = ($currentAgencyView === 'smc' && $canSwitchAgency) ? $smcStats : $stats;
$displayRecentApplicants = ($currentAgencyView === 'smc' && $canSwitchAgency) ? $smcRecentApplicants : $recentApplicants;
$displayAdminCount = ($currentAgencyView === 'smc' && $canSwitchAgency) ? $smcAdminCount : $adminCount;
$dashboardLabel = ($currentAgencyView === 'smc' && $canSwitchAgency) ? 'SMC' : 'CSNK';
?>

<!-- ======= STATS GRID (SMC counts only) ======= -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-4">
  <!-- Total Applicants -->
  <div class="stat-card">
    <div class="p-5">
      <div class="flex items-center justify-between">
        <h6 class="title uppercase">TOTAL APPLICANTS</h6>
        <i class="bi bi-people text-3xl icon-faint"></i>
      </div>
      <div class="mt-3 big"><?php echo dash_if_blank((string) ($stats['total'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat_chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Active pool</span>
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
      <div class="mt-3 big"><?php echo dash_if_blank((string) ($stats['pending'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat_chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Awaiting
          review</span>
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
      <div class="mt-3 big"><?php echo dash_if_blank((string) ($stats['on_process'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat_chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Actively
          handled</span>
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
      <div class="mt-3 big"><?php echo dash_if_blank((string) ($stats['deleted'] ?? '')); ?></div>
      <div class="mt-4">
        <span class="stat_chip"><span class="w-2 h-2 rounded-full" style="background:#f3d98b"></span> Soft
          removed</span>
      </div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- Recent Applicants (SMC) -->
  <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border">
    <div class="px-5 pt-5 pb-3">
      <div class="d-flex align-items-center justify-content-between">
        <div>
          <h5 class="mb-0 fw-semibold">Recent Applicants</h5>
          <small class="text-muted">Latest profiles created in SMC system.</small>
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
              <?php foreach ($recentApplicants as $app): ?>
                <?php
                $statusColors = ['pending' => 'warning', 'on_process' => 'info', 'approved' => 'success'];
                $badgeColor = $statusColors[$app['status']] ?? 'secondary';
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <?php if (!empty($app['picture'])): ?>
                        <img src="<?php echo safe(getFileUrl($app['picture'])); ?>" alt="Profile" class="rounded-circle me-2"
                          width="40" height="40" style="object-fit:cover;">
                      <?php else: ?>
                        <div
                          class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                          style="width: 40px; height: 40px;">
                          <?php echo strtoupper(substr($app['first_name'] ?? '', 0, 1)); ?>
                        </div>
                      <?php endif; ?>
                      <strong>
                        <?php echo safe(getFullName(
                          $app['first_name'] ?? '',
                          $app['middle_name'] ?? '',
                          $app['last_name'] ?? '',
                          $app['suffix'] ?? ''
                        )); ?>
                      </strong>
                    </div>
                  </td>
                  <td class="text-muted">
                    <?php echo safe($app['phone_number'] ?? '—'); ?>
                  </td>
                  <td>
                    <span class="badge bg-<?php echo $badgeColor; ?>">
                      <?php echo safe(ucfirst(str_replace('_', ' ', $app['status'] ?? ''))); ?>
                    </span>
                  </td>
                  <td class="text-muted">
                    <?php echo safe(formatDate($app['created_at'] ?? '')); ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Right Column -->
  <div class="flex flex-col gap-4">
    <!-- Quick Stats Card -->
    <div class="bg-white rounded-2xl shadow-sm border">
      <div class="px-5 pt-5 pb-3">
        <h5 class="mb-0 fw-semibold">Quick Stats</h5>
      </div>
      <div class="soft-divider"></div>
      <div class="p-5 pt-4">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted">Staff (SMC)</span>
          <strong><?php echo dash_if_blank((string) $adminCount); ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted">Active Applicants</span>
          <strong><?php echo dash_if_blank((string) ($stats['total'] ?? '')); ?></strong>
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
        <a href="turkey_applicants.php" class="btn btn-outline-primary w-100 mb-2">
          <i class="bi bi-people me-2"></i>View All Applicants
        </a>
        <a href="accounts.php" class="btn btn-outline-secondary w-100">
          <i class="bi bi-person-plus me-2"></i>Add New Account
        </a>
      </div>
    </div>
  </div>
</div>

<!-- Recent Activity (SMC only) -->
<?php if (!empty($recentActivities) && $canSeeAdminUX): ?>
  <div class="mt-4 bg-white rounded-2xl shadow-sm border">
    <div class="px-5 py-4 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0 fw-semibold">Recent Activity</h5>
        <small class="text-muted">Latest actions by SMC staff.</small>
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
              <th style="width: 28%;">User</th>
              <th style="width: 16%;">Role</th>
              <th style="width: 18%;">Action</th>
              <th>Description</th>
              <th style="width: 18%;">When</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentActivities as $log): ?>
              <?php
              $displayName = $log['full_name'] ?: ($log['username'] ?? '');
              if ($displayName === '')
                continue;
              $roleLabel = $log['role'] ?? '';
              ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?php echo safe($displayName); ?></div>
                  <div class="text-muted small"><?php echo safe($log['username'] ?? ''); ?></div>
                </td>
                <td>
                  <?php if ($roleLabel !== ''): ?>
                    <span class="badge bg-light text-dark text-capitalize">
                      <?php echo safe(str_replace('_', ' ', $roleLabel)); ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <?php echo safe($log['action'] ?? ''); ?>
                  </span>
                </td>
                <td class="text-truncate" style="max-width: 420px;">
                  <?php echo safe($log['description'] ?? '—'); ?>
                </td>
                <td><span class="text-muted"><?php echo safe(formatDateTime($log['created_at'] ?? '')); ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- Blacklisted Applicants (SMC only) -->
<?php if ($canSeeAdminUX): ?>
  <div class="mt-4 bg-white rounded-2xl shadow-sm border">
    <div class="px-5 py-4 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0 fw-semibold">
          <i class="bi bi-slash-circle me-2 text-danger"></i>Blacklisted Applicants
        </h5>
        <small class="text-muted">SMC applicants who violated policies.</small>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="badge bg-primary-subtle text-primary px-3 py-2">
          <i class="bi bi-exclamation-triangle me-1"></i><?php echo dash_if_blank((string) $blacklistedCount); ?> Total
        </span>
        <a href="turkey_blacklisted.php" class="btn btn-sm btn-outline-primary">
          <i class="bi bi-arrow-right me-1"></i>View All
        </a>
      </div>
    </div>
    <div class="soft-divider"></div>
    <div class="p-0">
      <?php if (empty($blacklistedApplicants)): ?>
        <div class="text-center py-5">
          <i class="bi bi-check-circle" style="font-size: 3rem; color:#198754;"></i>
          <p class="text-muted mt-3 mb-0">No blacklisted applicants at this time.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="bg-white">
              <tr>
                <th style="width: 28%;">Applicant</th>
                <th style="width: 20%;">Reason</th>
                <th style="width: 18%;">Logged By</th>
                <th>Issue</th>
                <th style="width: 18%;">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($blacklistedApplicants as $bl): ?>
                <?php
                $appName = getFullName(
                  $bl['first_name'] ?? '',
                  $bl['middle_name'] ?? '',
                  $bl['last_name'] ?? '',
                  $bl['suffix'] ?? ''
                );
                $createdBy = ($bl['created_by_name'] ?? '') ?: (($bl['created_by_username'] ?? '') ?: 'System');
                $when = formatDateTime($bl['created_at'] ?? '');
                $viewUrl = 'view-applicant.php?id=' . (int) ($bl['applicant_id'] ?? 0);
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div
                        class="bg-danger bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center me-2"
                        style="width: 36px; height: 36px;">
                        <i class="bi bi-person-x text-danger"></i>
                      </div>
                      <div>
                        <div class="fw-semibold">
                          <a href="<?php echo safe($viewUrl); ?>" class="text-decoration-none">
                            <?php echo safe($appName); ?>
                          </a>
                        </div>
                        <div class="text-muted small">ID: <?php echo (int) ($bl['applicant_id'] ?? 0); ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-danger-subtle">
                      <?php echo safe($bl['reason'] ?? ''); ?>
                    </span>
                  </td>
                  <td>
                    <div class="fw-semibold small"><?php echo safe($createdBy); ?></div>
                  </td>
                  <td>
                    <div class="text-truncate" style="max-width: 280px;" title="<?php echo safe($bl['issue'] ?? ''); ?>">
                      <?php echo !empty($bl['issue']) ? safe($bl['issue']) : '<span class="text-muted">—</span>'; ?>
                    </div>
                  </td>
                  <td><span class="small text-muted"><?php echo safe($when); ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<script>
  setInterval(function () { location.reload(); }, 60000);
</script>

<?php require_once $ADMIN_ROOT . '/includes/footer.php'; ?>