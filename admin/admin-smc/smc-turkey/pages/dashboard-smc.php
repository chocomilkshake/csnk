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
  .stat-card .title { letter-spacing: .06e
  <div class="badge bg-primary-sub"></div>
                    <?php if (!emp
          <i class="bi bi-person-plus me-2"></i>Add New Account
        </a>
      </div>
    </div>
  </div>
</div>
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