<?php
/* BLOCK SMC employees from accessing CSNK dashboard - must be before any output */
// Start session explicitly
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Get user role and agency from session
$userRole = isset($_SESSION['role']) ? strtolower($_SESSION['role']) : '';
$userAgency = isset($_SESSION['agency']) ? strtolower($_SESSION['agency']) : '';

// Check if user is super_admin or admin
$isSuperAdmin = ($userRole === 'super_admin');
$isAdmin = ($userRole === 'admin');
$isEmployee = ($userRole === 'employee');
$canSwitchAgency = $isSuperAdmin || $isAdmin;

// Handle agency switch from URL parameter (for Admin/Super Admin)
$currentAgencyView = $_SESSION['current_agency_view'] ?? $userAgency;
if (!in_array($currentAgencyView, ['csnk', 'smc'])) {
  $currentAgencyView = 'csnk';
}

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

// For employees, determine their agency dashboard
if ($isEmployee) {
  if ($userAgency === 'smc') {
    // SMC employees should use turkey_dashboard.php - redirect immediately
    header('Location: turkey_dashboard.php');
    exit;
  }
  // CSNK employees stay on dashboard.php (CSNK view)
}

$pageTitle = 'CSNK Dashboard';
require_once '../includes/header.php';

// Get agency-specific data based on current view
$viewingAgency = ($isEmployee && $userAgency === 'csnk') ? 'csnk' : $currentAgencyView;

// Fetch SMC data if viewing SMC
$smcStats = ['total' => 0, 'pending' => 0, 'on_process' => 0, 'deleted' => 0];
$smcRecentApplicants = [];
$smcAdminCount = 0;

if ($currentAgencyView === 'smc' && $canSwitchAgency) {
    $conn = $database->getConnection();
    if ($conn instanceof mysqli) {
        // Get SMC business unit IDs
        $smcBuIds = [];
        $sqlSmcBus = "SELECT bu.id FROM business_units bu JOIN agencies ag ON ag.id = bu.agency_id WHERE ag.code = 'smc' AND bu.active = 1";
        if ($res = $conn->query($sqlSmcBus)) {
            while ($r = $res->fetch_assoc()) {
                $smcBuIds[] = (int) $r['id'];
            }
        }
        
        if (!empty($smcBuIds)) {
            $placeholders = implode(',', array_fill(0, count($smcBuIds), '?'));
            
            // SMC Total
            $sql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND deleted_at IS NULL";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
                $stmt->execute();
                $smcStats['total'] = (int) $stmt->get_result()->fetch_row()[0];
                $stmt->close();
            }
            
            // SMC Pending
            $sql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND status = 'pending' AND deleted_at IS NULL";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
                $stmt->execute();
                $smcStats['pending'] = (int) $stmt->get_result()->fetch_row()[0];
                $stmt->close();
            }
            
            // SMC On Process
            $sql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND status = 'on_process' AND deleted_at IS NULL";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
                $stmt->execute();
                $smcStats['on_process'] = (int) $stmt->get_result()->fetch_row()[0];
                $stmt->close();
            }
            
            // SMC Deleted
            $sql = "SELECT COUNT(*) FROM applicants WHERE business_unit_id IN ($placeholders) AND deleted_at IS NOT NULL";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
                $stmt->execute();
                $smcStats['deleted'] = (int) $stmt->get_result()->fetch_row()[0];
                $stmt->close();
            }
            
            // SMC Recent Applicants
            $sql = "SELECT * FROM applicants WHERE business_unit_id IN ($placeholders) AND deleted_at IS NULL ORDER BY created_at DESC LIMIT 5";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param(str_repeat('i', count($smcBuIds)), ...$smcBuIds);
                $stmt->execute();
                $smcRecentApplicants = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            }
        }
        
        // SMC Admin Count
        $sql = "SELECT COUNT(*) FROM admin_users WHERE agency = 'smc' AND status = 'active'";
        if ($res = $conn->query($sql)) {
            $smcAdminCount = (int) $res->fetch_row()[0];
        }
    }
}

require_once '../includes/Applicant.php';
require_once '../includes/Admin.php';

$applicant = new Applicant($database);
$admin = new Admin($database);

// High-level stats
$stats = $applicant->getStatistics();
$recentApplicants = array_slice($applicant->getAll(), 0, 5);
// Only count admin and employee accounts (exclude super_admin)
$adminCount = count($admin->getAll(true));

// Role flags from header.php
$currentRole = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($currentRole === 'super_admin');
$isAdmin = ($currentRole === 'admin');
$canSeeAdminUX = ($isAdmin || $isSuperAdmin);

/* ---------------------- Recent activity logs ---------------------- *
 * Admin:    hide super_admin logs + remove unknowns (INNER JOIN).
 * SuperAdmin: show all but still remove unknowns (INNER JOIN).
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

/* ---------------------- Blacklisted Applicants ---------------------- */
$blacklistedApplicants = [];
$blacklistedCount = 0;
if (!empty($currentUser) && $canSeeAdminUX) {
  $conn = $database->getConnection();
  if ($conn instanceof mysqli) {
    // Count
    $countSql = "SELECT COUNT(*) FROM blacklisted_applicants WHERE is_active = 1";
    if ($countResult = $conn->query($countSql)) {
      $blacklistedCount = (int) $countResult->fetch_row()[0];
    }

    // Recent active blacklists
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
            LEFT JOIN applicants   a  ON a.id = b.applicant_id
            LEFT JOIN admin_users  au ON au.id = b.created_by
            WHERE b.is_active = 1
            ORDER BY b.created_at DESC
            LIMIT 5
        ";
    if ($result = $conn->query($sql)) {
      $blacklistedApplicants = $result->fetch_all(MYSQLI_ASSOC);
    }
  }
}



// Helpers
function safe(?string $s): string
{
  return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}
?>
<!-- Tailwind (via CDN) layered on top of Bootstrap) -->
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          csnk: { red: '#c40000', dark: '#991b1b' }
        },
        boxShadow: {
          soft: '0 10px 25px rgba(0,0,0,.06)',
          glass: '0 10px 30px rgba(0,0,0,.08)'
        }
      }
    }
  }
</script>

<style>
  /* Hybrid polish for Bootstrap + Tailwind */
  .glass-card {
    backdrop-filter: blur(8px);
    background: linear-gradient(180deg, rgba(255, 255, 255, .72), rgba(255, 255, 255, .88));
    border: 1px solid rgba(230, 234, 242, .85);
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(16, 24, 40, .06);
  }

  .stat-chip {
    display: flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .75rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, .12);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, .25);
  }

  .truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .soft-divider {
    height: 1px;
    background: #eef2f7;
  }

  /* Agency Switcher Styles */
  .agency-switcher {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }

  :root {
    --smc-navy: #0B1F3A;
    --smc-navy-2: #132A4A;
    --smc-gold: #FFD84D;
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
    background: linear-gradient(135deg, var(--smc-navy) 0%, var(--smc-navy-2) 100%);
    color: var(--smc-gold);
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
</style>

<!-- Agency Switcher for Super Admin / Admin -->
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
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-5">
  <!-- Total Applicants -->
  <div class="relative overflow-hidden rounded-2xl shadow-soft"
    style="background: radial-gradient(1200px 300px at -20% -20%, <?php echo ($currentAgencyView === 'smc') ? '#fde68a' : '#60a5fa'; ?> 0%, <?php echo ($currentAgencyView === 'smc') ? '#d97706' : '#1d4ed8'; ?> 45%, #111827 100%);">
    <div class="p-5 text-white">
      <div class="flex items-center justify-between">
        <h6 class="uppercase tracking-wider opacity-80">Total Applicants</h6>
        <i class="bi bi-people text-3xl opacity-75"></i>
      </div>
      <div class="mt-3 text-4xl font-extrabold"><?php echo (int) $displayStats['total']; ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full bg-white"></span> Active pool</span>
      </div>
    </div>
  </div>

  <!-- Pending -->
  <div class="relative overflow-hidden rounded-2xl shadow-soft"
    style="background: radial-gradient(1200px 300px at -20% -20%, #fde68a 0%, #d97706 45%, #78350f 100%);">
    <div class="p-5 text-white">
      <div class="flex items-center justify-between">
        <h6 class="uppercase tracking-wider opacity-80">Pending</h6>
        <i class="bi bi-clock-history text-3xl opacity-75"></i>
      </div>
      <div class="mt-3 text-4xl font-extrabold"><?php echo (int) $displayStats['pending']; ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full bg-white"></span> Awaiting review</span>
      </div>
    </div>
  </div>

  <!-- On Process -->
  <div class="relative overflow-hidden rounded-2xl shadow-soft"
    style="background: radial-gradient(1200px 300px at -20% -20%, #99f6e4 0%, #06b6d4 45%, #0f172a 100%);">
    <div class="p-5 text-white">
      <div class="flex items-center justify-between">
        <h6 class="uppercase tracking-wider opacity-80">On Process</h6>
        <i class="bi bi-hourglass-split text-3xl opacity-75"></i>
      </div>
      <div class="mt-3 text-4xl font-extrabold"><?php echo (int) $displayStats['on_process']; ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full bg-white"></span> Actively handled</span>
      </div>
    </div>
  </div>

  <!-- Deleted -->
  <div class="relative overflow-hidden rounded-2xl shadow-soft"
    style="background: radial-gradient(1200px 300px at -20% -20%, #fda4af 0%, #e11d48 45%, #111827 100%);">
    <div class="p-5 text-white">
      <div class="flex items-center justify-between">
        <h6 class="uppercase tracking-wider opacity-80">Deleted</h6>
        <i class="bi bi-trash text-3xl opacity-75"></i>
      </div>
      <div class="mt-3 text-4xl font-extrabold"><?php echo (int) $displayStats['deleted']; ?></div>
      <div class="mt-4">
        <span class="stat-chip"><span class="w-2 h-2 rounded-full bg-white"></span> Soft removed</span>
      </div>
    </div>
  </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
  <!-- ===================== Recent Applicants ===================== -->
  <div class="lg:col-span-2 glass-card">
    <div class="px-10 pt-4 pb-3">
      <div class="flex items-center justify-between">
        <div>
          <h3 class="mb-1 fw-semibold">Recent Applicants</h3>
          <small class="text-muted">Latest profiles created in <?php echo $dashboardLabel; ?> system.</small>
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
            <?php if (empty($displayRecentApplicants)): ?>
              <tr>
                <td colspan="4" class="text-center text-muted py-4">No applicants yet</td>
              </tr>
            <?php else: ?>
              <?php foreach ($displayRecentApplicants as $applicantData): ?>
                <?php
                $statusColors = ['pending' => 'warning', 'on_process' => 'info', 'approved' => 'success'];
                $badgeColor = $statusColors[$applicantData['status']] ?? 'secondary';
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <?php if (!empty($applicantData['picture'])): ?>
                        <img src="<?php echo safe(getFileUrl($applicantData['picture'])); ?>" alt="Profile"
                          class="rounded-circle me-2" width="40" height="40" style="object-fit:cover;">
                      <?php else: ?>
                        <div
                          class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-2"
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
    <div class="glass-card">
      <div class="px-5 pt-5 pb-3">
        <h5 class="mb-0 fw-semibold">Quick Stats</h5>
      </div>
      <div class="soft-divider"></div>
      <div class="p-5 pt-4">
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted">Staff (<?php echo $dashboardLabel; ?>)</span>
          <strong><?php echo (int) $displayAdminCount; ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
          <span class="text-muted">Active Applicants</span>
          <strong><?php echo (int) $displayStats['total']; ?></strong>
        </div>
        <div class="d-flex justify-content-between align-items-center py-2">
          <span class="text-muted">System Status</span>
          <span class="badge bg-success">Online</span>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="glass-card">
      <div class="px-5 pt-5 pb-3">
        <h5 class="mb-0 fw-semibold">Quick Actions</h5>
      </div>
      <div class="soft-divider"></div>
      <div class="p-5 pt-4">
        <?php if ($currentAgencyView === 'smc'): ?>
          <a href="add-applicant.php" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-plus-circle me-2"></i>Add New Applicant
          </a>
          <a href="turkey_applicants.php" class="btn btn-outline-primary w-100 mb-2">
            <i class="bi bi-people me-2"></i>View All Applicants
          </a>
        <?php else: ?>
          <a href="add-applicant.php" class="btn btn-primary w-100 mb-2">
            <i class="bi bi-plus-circle me-2"></i>Add New Applicant
          </a>
          <a href="applicants.php" class="btn btn-outline-primary w-100 mb-2">
            <i class="bi bi-people me-2"></i>View All Applicants
          </a>
        <?php endif; ?>
        <a href="accounts.php" class="btn btn-outline-secondary w-100">
          <i class="bi bi-person-plus me-2"></i>Add New Account
        </a>
      </div>
    </div>
  </div>
</div>

<!-- ===================== Recent Activity (Role-gated) ===================== -->
<?php if (!empty($recentActivities) && $canSeeAdminUX): ?>
  <div class="mt-4 glass-card">
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
          <thead>
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
              // INNER JOIN ensures we don't get Unknown users anymore
              $displayName = $log['full_name'] ?: ($log['username'] ?? ''); // fallback should not occur
              if ($displayName === '')
                continue; // extra safety
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
                <td><?php echo safe(formatDateTime($log['created_at'] ?? '')); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<!-- ===================== Blacklisted Applicants (Role-gated) ===================== -->
<?php if ($canSeeAdminUX): ?>
  <div class="mt-4 glass-card" style="border-left: 4px solid #dc3545;">
    <div class="px-5 py-4 d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0 fw-semibold">
          <i class="bi bi-slash-circle text-danger me-2"></i>Blacklisted Applicants
        </h5>
        <small class="text-muted">Applicants who have violated company or client policies.</small>
      </div>
      <div class="d-flex align-items-center gap-3">
        <span class="badge bg-danger-subtle text-danger px-3 py-2">
          <i class="bi bi-exclamation-triangle me-1"></i><?php echo (int) $blacklistedCount; ?> Total
        </span>
        <a href="blacklisted.php" class="btn btn-sm btn-outline-danger">
          <i class="bi bi-arrow-right me-1"></i>View All
        </a>
      </div>
    </div>
    <div class="soft-divider"></div>
    <div class="p-0">
      <?php if (empty($blacklistedApplicants)): ?>
        <div class="text-center py-5">
          <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
          <p class="text-muted mt-3 mb-0">No blacklisted applicants at this time.</p>
        </div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead>
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
                $createdBy = $bl['created_by_name'] ?: ($bl['created_by_username'] ?: 'System');
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
                          <a href="<?php echo safe($viewUrl); ?>" class="text-decoration-none text-dark">
                            <?php echo safe($appName); ?>
                          </a>
                        </div>
                        <div class="text-muted small">ID: <?php echo (int) ($bl['applicant_id'] ?? 0); ?></div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
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
  // Refresh every 60s (your original behavior)
  setInterval(function () {
    location.reload();
  }, 60000);
</script>

<?php require_once '../includes/footer.php'; ?>