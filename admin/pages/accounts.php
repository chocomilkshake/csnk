<?php
$pageTitle = 'Accounts Management';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);

$role = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin = ($role === 'admin');
$isEmployee = ($role === 'employee');

$currentAgency = $currentUser['agency'] ?? null;

// Branch loading + filters
$branches = $admin->getActiveBranches();
$allBranches = [0 => 'All Branches'] + $branches;

// SMC Countries (business units for SMC agency)  
$agencies = $admin->getAgencies();
$smcAgencyId = 2; // default SMC agency ID

$smcCountriesSql = "SELECT bu.id, c.iso2, c.name, c.id as country_id 
                    FROM business_units bu 
                    JOIN countries c ON bu.country_id = c.id 
                    WHERE bu.agency_id = ? AND bu.active = 1 AND c.active = 1 
                    ORDER BY c.name ASC";
$conn = $database->getConnection();
$stmt = $conn->prepare($smcCountriesSql);
$stmt->bind_param('i', $smcAgencyId);
$stmt->execute();
$result = $stmt->get_result();
$smcCountries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();
$allSmcCountries = [0 => 'All SMC Countries'] + $smcCountries;

// Flag icon helper for SMC countries
function flag_icon($iso2, $name)
{
  $flagClass = 'fi fi-' . strtolower($iso2) . ' me-1';
  return '<span class="' . $flagClass . '" style="width:20px;height:15px;border-radius:2px;"></span>' . htmlspecialchars($name);
}

// Hide incompatible filters: branches for SMC, countries for others
if (isset($filterAgency) && $filterAgency === 'smc') {
  $filterBranch = 0;
} else {
  $filterCountry = 0;
}

// load agency list from db (codes like 'csnk','smc' with human name)
$agencies = $admin->getAgencies();

$filterAgency = sanitizeInput($_GET['agency'] ?? '');
$filterBranch = (int) ($_GET['branch'] ?? 0);
$filterCountry = (int) ($_GET['country'] ?? 0); // NEW: SMC country filter
$filterStatus = sanitizeInput($_GET['status'] ?? '');
$filterSearch = sanitizeInput($_GET['search'] ?? '');
$errors = [];

function validateStrongPassword(string $pwd): ?string
{
  if (mb_strlen($pwd) < 10)
    return 'Password must be at least 10 characters.';
  if (!preg_match('/[A-Z]/', $pwd))
    return 'Password must include at least one uppercase letter.';
  if (!preg_match('/[a-z]/', $pwd))
    return 'Password must include at least one lowercase letter.';
  if (!preg_match('/\d/', $pwd))
    return 'Password must include at least one number.';
  if (!preg_match('/[\W_]/', $pwd))
    return 'Password must include at least one special character.';
  if (preg_match('/(.)\1{3,}/', $pwd))
    return 'Password should not contain repeated characters.';
  return null;
}

function validateEmailStrictDetectTypos(string $email): array
{
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    return [false, 'Invalid email format.', null];
  [$local, $domain] = explode('@', $email, 2);
  $domain = strtolower($domain);
  if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,10}$/i', $domain))
    return [false, 'Email domain looks invalid.', null];

  $known = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com'];
  $closest = null;
  $minDist = PHP_INT_MAX;
  foreach ($known as $k) {
    $d = levenshtein($domain, $k);
    if ($d < $minDist) {
      $minDist = $d;
      $closest = $k;
    }
  }
  if ($minDist === 1 && $closest === 'gmail.com') {
    return [false, 'Did you mean @gmail.com?', $local . '@gmail.com'];
  }
  return [true, null, null];
}

function forbidAndBack(string $msg = 'You do not have permission.')
{
  setFlashMessage('error', $msg);
  redirect('accounts.php');
  exit;
}

/* DELETE */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
  $targetId = (int) $_GET['id'];
  if ($targetId === (int) $_SESSION['admin_id']) {
    setFlashMessage('error', 'You cannot delete your own account.');
    redirect('accounts.php');
    exit;
  }

  $target = $admin->getById($targetId);
  if (!$target) {
    setFlashMessage('error', 'Account not found.');
    redirect('accounts.php');
    exit;
  }

  if ($isSuperAdmin) {
    // super admin can delete any
  } elseif ($isAdmin) {
    if (!in_array($target['role'], ['employee', 'admin'], true)) {
      forbidAndBack('Admins can delete only Employee/Admin accounts.');
    }
  } else {
    forbidAndBack();
  }

  if ($admin->delete($targetId)) {
    $auth->logActivity($_SESSION['admin_id'], 'Delete Account', "Deleted ID {$targetId}");
    setFlashMessage('success', 'Account deleted successfully.');
  } else {
    setFlashMessage('error', 'Failed to delete account.');
  }
  redirect('accounts.php');
  exit;
}

/* ADD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
  if (!($isSuperAdmin || $isAdmin))
    forbidAndBack('Only Admins or Super Admins can add accounts.');

  $username = sanitizeInput($_POST['username'] ?? '');
  $fullName = sanitizeInput($_POST['full_name'] ?? '');
  $email = sanitizeInput($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $password2 = $_POST['password2'] ?? '';
  $roleNew = sanitizeInput($_POST['role'] ?? 'employee');
  $branchIdNew = (int) ($_POST['business_unit_id'] ?? 0);
  // agency comes from hidden field set by JS/tabs
  $agencyNew = sanitizeInput($_POST['agency'] ?? '');
  $validCodes = array_column($agencies, 'code');
  if (!in_array($agencyNew, $validCodes, true)) {
    // fallback to first agency or empty
    $agencyNew = $agencies[0]['code'] ?? '';
  }

  $allowedRoles = $isSuperAdmin ? ['employee', 'admin', 'super_admin'] : ['employee', 'admin'];
  if (!in_array($roleNew, $allowedRoles, true))
    $roleNew = 'employee';
  // non-CSNK agencies are always employee accounts
  if ($agencyNew !== 'csnk') {
    $roleNew = 'employee';
    // branch doesn't apply for other agencies
    $branchIdNew = null;
  }

  if ($username === '')
    $errors[] = 'Username is required.';
  if ($fullName === '')
    $errors[] = 'Full name is required.';
  if ($email === '')
    $errors[] = 'Email is required.';
  if ($password === '')
    $errors[] = 'Password is required.';
  if ($password !== $password2)
    $errors[] = 'Passwords do not match.';

  if ($email !== '') {
    [$ok, $err, $suggest] = validateEmailStrictDetectTypos($email);
    if (!$ok)
      $errors[] = $err . ($suggest ? ' Suggested: ' . htmlspecialchars($suggest) : '');
  }
  if ($password !== '') {
    $pwdErr = validateStrongPassword($password);
    if ($pwdErr)
      $errors[] = $pwdErr;
  }
  if ($username !== '' && $admin->usernameExists($username))
    $errors[] = 'Username already exists.';
  if ($email !== '' && $admin->emailExists($email))
    $errors[] = 'Email already exists.';

  // branch requirement only enforced for csnk agency employees
  if ($roleNew === 'employee' && $agencyNew === 'csnk' && $branchIdNew <= 0) {
    $errors[] = 'Branch is required for Employee accounts.';
  }

  if (empty($errors)) {
    $data = [
      'username' => $username,
      'email' => $email,
      'full_name' => $fullName,
      'password' => $password,
      'role' => $roleNew,
      'status' => 'active',
      'business_unit_id' => $branchIdNew ?: null,
      'agency' => $agencyNew,
    ];
    if ($admin->create($data)) {
      $branchLabel = $branchIdNew > 0 ? " (Branch ID:{$branchIdNew})" : '';
      $auth->logActivity($_SESSION['admin_id'], 'Create Account', "Created {$roleNew} {$username}{$branchLabel}");
      setFlashMessage('success', 'Account created successfully.');
      redirect('accounts.php');
      exit;
    } else {
      $errors[] = 'Failed to create account.';
    }
  }
}

/* RESET PASSWORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
  if (!$isSuperAdmin)
    forbidAndBack('Only Super Admin can recover accounts.');

  $userId = (int) ($_POST['user_id'] ?? 0);
  $newPwd = $_POST['new_password'] ?? '';
  $conf = $_POST['confirm_password'] ?? '';

  if ($userId <= 0)
    $errors[] = 'Invalid account.';
  if ($newPwd === '')
    $errors[] = 'New password is required.';
  if ($newPwd !== $conf)
    $errors[] = 'Passwords do not match.';
  $pwdErr = validateStrongPassword($newPwd);
  if ($pwdErr)
    $errors[] = $pwdErr;

  $target = $admin->getById($userId);
  if (!$target)
    $errors[] = 'Account not found.';

  if (empty($errors)) {
    if ($admin->updatePassword($userId, $newPwd)) {
      $auth->logActivity($_SESSION['admin_id'], 'Recover Account', "Reset password ID {$userId}");
      setFlashMessage('success', 'Password reset successfully.');
      redirect('accounts.php');
      exit;
    } else {
      $errors[] = 'Failed to reset password.';
    }
  }
}

/* EDIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_account'])) {
  $userId = (int) ($_POST['edit_user_id'] ?? 0);
  $username = sanitizeInput($_POST['edit_username'] ?? '');
  $fullName = sanitizeInput($_POST['edit_full_name'] ?? '');
  $email = sanitizeInput($_POST['edit_email'] ?? '');
  $status = $_POST['edit_status'] ?? 'active';

  if ($userId <= 0)
    $errors[] = 'Invalid account.';
  if ($username === '')
    $errors[] = 'Username is required.';
  if ($fullName === '')
    $errors[] = 'Full name is required.';
  if ($email === '')
    $errors[] = 'Email is required.';
  if (!in_array($status, ['active', 'inactive'], true))
    $status = 'active';

  $target = $admin->getById($userId);
  if (!$target)
    $errors[] = 'Account not found.';

  if ($isSuperAdmin) {
    // can edit all
  } elseif ($isAdmin) {
    if (!in_array($target['role'], ['employee', 'admin'], true)) {
      forbidAndBack('Admins can edit only Employee/Admin accounts.');
    }
  } else {
    forbidAndBack('Employees cannot edit accounts.');
  }

  if ($email !== '') {
    [$ok, $err, $suggest] = validateEmailStrictDetectTypos($email);
    if (!$ok)
      $errors[] = $err . ($suggest ? ' Suggested: ' . htmlspecialchars($suggest) : '');
  }
  if ($username !== '' && $admin->usernameExists($username, $userId))
    $errors[] = 'Username already in use.';
  if ($email !== '' && $admin->emailExists($email, $userId))
    $errors[] = 'Email already in use.';

  $data = [
    'username' => $username,
    'email' => $email,
    'full_name' => $fullName,
    'role' => $target['role'],
    'status' => $status,
    'business_unit_id' => $target['business_unit_id'] ?? null,
  ];

  if (empty($errors)) {
    if ($admin->update($userId, $data)) {
      $auth->logActivity($_SESSION['admin_id'], 'Edit Account', "Edited ID {$userId}");
      setFlashMessage('success', 'Account updated successfully.');
      redirect('accounts.php');
      exit;
    } else {
      $errors[] = 'Failed to update account.';
    }
  }
}

/* ============================
   Load lists for the view (with branch/status/search filters)
============================= */

$rawEmployees = $admin->getByAgency($filterAgency ?: null, 'employee');
$rawAdmins = $admin->getByAgency($filterAgency ?: null, 'admin');
// super-admin accounts are global; ignore agency filter when fetching
$rawSupers = $isSuperAdmin ? $admin->getByAgency(null, 'super_admin') : [];

// Hide incompatible filters: branches for SMC, countries for others
if ($filterAgency === 'smc') {
  $filterBranch = 0;
} else {
  $filterCountry = 0;
}

/* Helper to apply filters + RBAC */
function applyAccountFilters(
  array $accounts,
  int $filterBranch,
  int $filterCountry, // NEW param
  string $filterStatus,
  string $filterSearch,
  bool $isSuperAdmin,
  bool $isAdmin,
  ?string $currentAgency
): array {

  return array_values(array_filter($accounts, function ($acc) use ($filterBranch, $filterCountry, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, $currentAgency) {

    // Branch filter (skip for super admins)
    $buId = (int) ($acc['business_unit_id'] ?? ($acc['branch_id'] ?? 0));
    if ($filterBranch > 0) {
      if (($acc['role'] ?? '') !== 'super_admin' && $buId !== $filterBranch) {
        return false;
      }
    }

    // Country filter (SMC BUs, skip super admins) - MIRRORS branch logic exactly
    if ($filterCountry > 0) {
      if (($acc['role'] ?? '') !== 'super_admin' && $buId !== $filterCountry) {
        return false;
      }
    }

    // Status filter
    if ($filterStatus !== '' && $acc['status'] !== $filterStatus) {
      return false;
    }

    // Search filter
    if ($filterSearch !== '') {
      $term = strtolower($filterSearch);
      if (
        !str_contains(strtolower($acc['username'] ?? ''), $term) &&
        !str_contains(strtolower($acc['full_name'] ?? ''), $term) &&
        !str_contains(strtolower($acc['email'] ?? ''), $term)
      ) {
        return false;
      }
    }

    // RBAC - employees only see their agency
    if (!$isSuperAdmin && !$isAdmin && $currentAgency && ($acc['agency'] ?? null) !== $currentAgency && $acc['role'] === 'employee') {
      return false;
    }

    return true;
  }));
}

$employeeAccounts = applyAccountFilters($rawEmployees, $filterBranch, $filterCountry, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, $currentAgency);
$adminAccounts = applyAccountFilters($rawAdmins, $filterBranch, $filterCountry, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, null);
$superAccounts = applyAccountFilters($rawSupers, $filterBranch, $filterCountry, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, null);
?>

<style>
/* ===== Modern Form Fields ===== */
.field-group{position:relative}
.input-icon{position:absolute;left:16px;top:50%;transform:translateY(-50%);color:#6b7280;z-index:3;pointer-events:none;font-size:1.1rem}
.field-group input{padding-left:50px!important}
.pwd-eye{position:absolute;right:16px;top:50%;transform:translateY(-50%);color:#6b7280;background:none;border:0;cursor:pointer;z-index:3;padding:4px;border-radius:4px;transition:.2s}
.pwd-eye:hover{color:#374151;background:rgba(0,0,0,.05)}
.pwd-eye.active{color:#10b981}
.pwd-strength{height:6px!important;background:#f1f5f9;border-radius:3px;overflow:hidden}
.pwd-weak{background:#ef4444}.pwd-fair{background:#f59e0b}.pwd-good{background:#10b981}.pwd-strong{background:#059669}

/* ===== Layout ===== */
.modern-accounts{font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif}
.hero-section{background:linear-gradient(135deg,#f8fafc 0%,#f1f5f9 100%);color:#1e293b;padding:2rem 1.5rem;border-radius:12px;margin-bottom:1.5rem;text-align:center;box-shadow:0 4px 12px rgba(0,0,0,.04)}
.hero-section h1{font-size:clamp(1.75rem,4vw,2.5rem);font-weight:700;margin-bottom:.25rem}
.hero-section .lead{font-size:1.1rem;color:#64748b}

.accounts-grid{display:grid;gap:1.25rem}

/* ===== Table ===== */
.modern-table{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.04);border:1px solid #e5e7eb}
.modern-table thead th{background:#f8fafc;border-bottom:2px solid #e5e7eb;font-weight:600;color:#374151;padding:1rem 1.25rem;white-space:nowrap}
.modern-table tbody tr{transition:.2s}
.modern-table tbody tr:hover{background:#f9fafb}
.modern-table td{padding:1rem 1.25rem;vertical-align:middle;border-color:#e5e7eb}

/* ===== Account Card ===== */
.account-card{background:#fff;border-radius:12px;padding:1.25rem;box-shadow:0 2px 8px rgba(0,0,0,.04);border:1px solid #e5e7eb;transition:.2s}
.account-card:hover{box-shadow:0 4px 12px rgba(0,0,0,.08)}
.account-avatar{width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#667eea,#764ba2);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1.1rem}
.account-info{flex:1;margin-left:1rem}
.account-name{font-weight:700;font-size:1.1rem;margin-bottom:.25rem;color:#1e293b}
.account-meta{display:flex;flex-wrap:wrap;gap:1rem;font-size:.875rem;color:#6b7280}

/* ===== Status ===== */
.status-badge{padding:.35rem .75rem;border-radius:20px;font-size:.75rem;font-weight:600;text-transform:uppercase;letter-spacing:.03em;border:1px solid transparent}
.status-active{background:rgba(16,185,129,.1);color:#059669;border-color:rgba(16,185,129,.2)}
.status-inactive{background:rgba(107,114,128,.08);color:#6b7280;border-color:rgba(107,114,128,.2)}

.branch-tag{padding:.3rem .8rem;border-radius:12px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);color:#374151;font-size:.8rem;font-weight:600;border:1px solid #e2e8f0}

/* ===== Buttons ===== */
.action-buttons{display:flex;gap:.5rem}
.btn-action{width:40px;height:40px;border-radius:12px;display:flex;align-items:center;justify-content:center;border:0;transition:.2s;font-size:1rem}
.btn-action:hover{transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,.15)}
.btn-edit{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
.btn-reset{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}
.btn-delete{background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff}

/* ===== Empty ===== */
.empty-state{text-align:center;padding:4rem 2rem;color:#6b7280}
.empty-state-icon{font-size:4rem;opacity:.5;margin-bottom:1rem}

/* ===== Mobile ===== */
@media(max-width:768px){
.hero-section{padding:1.5rem 1rem}
.modern-table{font-size:.875rem;border:0}
.modern-table thead{display:none}
.modern-table tbody,.modern-table tr,.modern-table td{display:block;width:100%}
.modern-table tr{background:#fff;border-radius:12px;margin-bottom:1rem;box-shadow:0 2px 8px rgba(0,0,0,.08);padding:1rem;border:1px solid #f1f5f9}
.modern-table td{padding:.5rem 0;border:0;display:flex;justify-content:space-between;align-items:center}
.modern-table td:before{content:attr(data-label);font-weight:600;color:#64748b;flex:0 0 120px}
.accounts-grid{grid-template-columns:1fr;gap:1rem}
.action-buttons .btn{width:36px;height:36px;font-size:.875rem}
}

@media(min-width:992px){
.accounts-grid{grid-template-columns:repeat(auto-fit,minmax(380px,1fr))}
}

/* ===== Filters ===== */
.filter-container{background:#fff;border-radius:14px;padding:clamp(10px,1.6vw,18px);margin-bottom:12px}
.filter-container .row{row-gap:2px}

.filter-tabs::-webkit-scrollbar{height:8px}
.filter-tabs::-webkit-scrollbar-thumb{background:#d6d6d6;border-radius:999px}

.filter-btn,.filter-tabs .btn{
display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;
border:1px solid var(--c-border);background:#fff;color:var(--c-text);
font-weight:600;font-size:.95rem;line-height:1.2;letter-spacing:.1px;text-wrap:nowrap
}

.filter-tabs .btn-sm{padding:6px 12px;font-size:1rem}

.filter-btn:hover,.filter-tabs .btn:hover{background:var(--c-muted);border-color:#dcdcdc}
.filter-btn.active,.filter-tabs .btn.active{background:lightslategray;color:#fff;border-color:#d0d0d0!important}

.filter-tabs .btn-group{gap:10px}
.filter-tabs+.filter-tabs{margin-top:8px}

.filter-tabs .btn-outline-primary.btn-sm,
.filter-tabs .btn-secondary.btn-sm{color:var(--c-text)}

.filter-tabs .btn-outline-primary.btn-sm.active,
.filter-tabs .btn-secondary.btn-sm.active{background:lightslategray;color:#fff!important}
</style>

<button class="btn btn-primary shadow-lg" style="
    position: fixed;
    bottom: 25px;
    right: 25px;
    border-radius: 50px;
    padding: 14px 28px;
    font-weight: 600;
    z-index: 1030;
  " data-bs-toggle="modal" data-bs-target="#addAccountModal">
  <i class="bi bi-plus-circle me-2"></i>Create Account
</button>

<script>
  (function () {
    // Bootstrap debug + fallback

    console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
    if (typeof bootstrap === 'undefined') {
      console.error('Bootstrap JS missing - modals will fail!');
      return;
    }

    // Manual fallback for all modal buttons
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const targetId = btn.dataset.bsTarget;
        const modalEl = document.querySelector(targetId);
        if (!modalEl) {
          console.error(`Modal not found: ${targetId}`);
          return;
        }
        try {
          const modal = new bootstrap.Modal(modalEl);
          modal.show();
          console.log(`Manual modal opened: ${targetId}`);
        } catch (err) {
          console.error('Modal init failed:', err);
        }
      });
    });

    console.log('Modal fallback handlers installed');
  })();

</script>

<div class="filter-container">

  <div class="row align-items-center">
    <div class="col-lg-8">
      <div class="filter-tabs">
        <!-- Agency Filter Tabs -->
        <div class="btn-group mb-2 me-3" role="group">
          <a href="accounts.php?agency=&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>&branch=<?= $filterBranch ?>"
            class="btn filter-btn <?= empty($filterAgency) ? 'active' : '' ?>">🏢 All</a>
          <?php foreach ($agencies as $ag): ?>
            <a href="accounts.php?agency=<?= urlencode($ag['code']) ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>&branch=<?= $filterBranch ?>"
              class="btn filter-btn <?= $filterAgency === $ag['code'] ? 'active' : '' ?>"><?= htmlspecialchars($ag['name']) ?></a>
          <?php endforeach; ?>
        </div>

        <!-- Role Filter - CLICKABLE -->
        <?php if ($isSuperAdmin): ?>
          <div class="btn-group mb-2 mb-lg-0" role="group">
            <button type="button" class="filter-btn active" id="btnViewEmployees">👥 Employees</button>
            <button type="button" class="filter-btn" id="btnViewAdmins">⚙️ Admins</button>
            <button type="button" class="filter-btn" id="btnViewSupers">👑 Super Admins</button>
          </div>
        <?php elseif ($isAdmin): ?>
          <div class="btn-group mb-2 mb-lg-0" role="group">
            <button type="button" class="filter-btn active" id="btnViewEmployees">👥 Employees</button>
            <button type="button" class="filter-btn" id="btnViewAdmins">⚙️ Admins</button>
          </div>
        <?php endif; ?>



      </div>
    </div>

    <!-- Branch Filters (CSNK only) -->
    <?php if ($filterAgency === 'csnk'): ?>
      <div class="filter-tabs mt-2">
        <a href="accounts.php?agency=csnk&branch=0&country=0&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>"
          class="btn btn-secondary btn-sm <?= $filterBranch === 0 ? 'active' : '' ?>">
          All CSNK
        </a>
        <?php foreach ($branches as $branch): ?>
          <a href="accounts.php?agency=csnk&branch=<?= (int) $branch['id'] ?>&country=0&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>"
            class="btn btn-outline-primary btn-sm <?= $filterBranch === (int) $branch['id'] ? 'active' : '' ?>">
            <?= htmlspecialchars($branch['code']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <!-- NEW: SMC Country Filters (SMC only) - Mirrors branches exactly -->
    <?php if ($filterAgency === 'smc'): ?>
      <div class="filter-tabs mt-2">
        <!-- All SMC FIRST -->
        <a href="accounts.php?agency=smc&country=0&branch=0&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>"
          class="btn btn-secondary btn-sm <?= $filterCountry === 0 ? 'active' : '' ?>">
          All SMC
        </a>
        <?php foreach ($smcCountries as $country): ?>
          <a href="accounts.php?agency=smc&country=<?= (int) $country['id'] ?>&branch=0&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>"
            class="btn btn-outline-primary btn-sm <?= $filterCountry === (int) $country['id'] ? 'active' : '' ?>">
            <?= flag_icon($country['iso2'], $country['name']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>


  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger shadow-sm border-start border-4 border-danger rounded-3 mb-4 p-4">
    <i class="bi bi-exclamation-triangle-fill fs-4 me-3 text-danger float-start"></i>
    <div>
      <strong>Validation Errors:</strong>
      <ul class="mb-0 mt-2">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<div class="accounts-grid">
  <!-- Employees Section -->
  <div class="account-card" id="sectionEmployees">
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
      <h5 class="mb-0 fw-bold">
        <i class="bi bi-people-fill text-primary me-2"></i>Employee Accounts
      </h5>
      <span class="badge bg-primary-subtle px-3 py-2 rounded-pill">
        <?= count($employeeAccounts) ?> users
      </span>
    </div>

    <?php if (empty($employeeAccounts)): ?>
      <div class="empty-state">
        <div class="empty-state-icon">
          <i class="bi bi-people"></i>
        </div>
        <h4 class="mb-2">No Employee Accounts</h4>
        <p class="mb-4">Get started by adding your first employee account.</p>
        <?php if ($isSuperAdmin || $isAdmin): ?>
          <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal"
            data-bs-target="#addAccountModal">
            <i class="bi bi-plus-circle me-2"></i>Add Employee
          </button>
        <?php endif; ?>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover modern-table">
          <thead class="table-light">
            <tr>
              <th>Account</th>
              <th>Name</th>
              <th>Email</th>
              <th>Branch</th>
              <th>Status</th>
              <th>Created</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($employeeAccounts as $acc): ?>
              <tr>
                <td data-label="Account">
                  <div class="d-flex align-items-center">
                    <div class="account-avatar flex-shrink-0">
                      <?= strtoupper(substr($acc['username'], 0, 1)) ?>
                    </div>
                    <div>
                      <div class="fw-semibold"><?= htmlspecialchars($acc['username']) ?></div>
                      <?php if ((int) $acc['id'] === (int) $_SESSION['admin_id']): ?>
                        <small class="text-success">This is you</small>
                      <?php endif; ?>
                    </div>
                  </div>
                </td>
                <td><?= htmlspecialchars($acc['full_name']) ?></td>
                <td>
                  <a href="mailto:<?= htmlspecialchars($acc['email']) ?>" class="text-decoration-none">
                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($acc['email']) ?>
                  </a>
                </td>
                <td>
                  <?php if (!empty($acc['branch_code'])): ?>
                    <span class="branch-tag"><?= htmlspecialchars($acc['branch_code']) ?> -
                      <?= htmlspecialchars($acc['branch_name']) ?></span>
                  <?php elseif (!empty($acc['branch_name'])): ?>
                    <span class="branch-tag"><?= htmlspecialchars($acc['branch_name']) ?></span>
                  <?php else: ?>
                    <span class="badge bg-secondary-subtle text-dark">Global</span>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="status-badge status-<?= $acc['status'] ?>">
                    <?= ucfirst($acc['status']) ?>
                  </span>
                </td>
                <td><small class="text-muted"><?= formatDate($acc['created_at']) ?></small></td>
                <td class="text-end">
                  <div class="action-buttons btn-group btn-group-sm" role="group">
                    <button type="button" class="btn-action btn-reset" data-bs-toggle="modal"
                      data-bs-target="#resetPasswordModal" data-user-id="<?= (int) $acc['id'] ?>"
                      data-user-name="<?= htmlspecialchars($acc['full_name']) ?>" title="Reset Password">
                      <i class="bi bi-key-fill"></i>
                    </button>
                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editAccountModal"
                      data-user-id="<?= (int) $acc['id'] ?>" data-username="<?= htmlspecialchars($acc['username']) ?>"
                      data-fullname="<?= htmlspecialchars($acc['full_name']) ?>"
                      data-email="<?= htmlspecialchars($acc['email']) ?>"
                      data-status="<?= htmlspecialchars($acc['status']) ?>"
                      data-branch-id="<?= (int) ($acc['branch_id'] ?? ($acc['business_unit_id'] ?? 0)) ?>"
                      data-branch-name="<?= htmlspecialchars($acc['branch_name'] ?? '') ?>"
                      data-branch-code="<?= htmlspecialchars($acc['branch_code'] ?? '') ?>"
                      data-role="<?= htmlspecialchars($acc['role']) ?>" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <?php if ((int) $acc['id'] !== (int) $_SESSION['admin_id']): ?>
                      <a href="accounts.php?action=delete&id=<?= (int) $acc['id'] ?>" class="btn btn-danger"
                        onclick="return confirm('Delete this account?')" title="Delete">
                        <i class="bi bi-trash"></i>
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>

  <?php if ($isSuperAdmin || $isAdmin): ?>
    <div class="account-card d-none" id="sectionAdmins">

      <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h5 class="mb-0 fw-bold">
          <i class="bi bi-shield-check me-2"></i>Admin Accounts
        </h5>
        <span class="status-badge"><?= count($adminAccounts) ?> users</span>
      </div>

      <?php if (empty($adminAccounts)): ?>
        <div class="empty-state">
          <div class="empty-state-icon">
            <i class="bi bi-shield"></i>
          </div>
          <h4 class="mb-2">No Admin Accounts</h4>
          <p class="mb-4">Create a new admin account to get started.</p>
          <button class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#addAccountModal">
            <i class="bi bi-plus-circle me-2"></i>Create Admin
          </button>
        </div>

      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover modern-table">
            <thead class="table-light">
              <tr>
                <th>Account</th>
                <th>Name</th>
                <th>Email</th>
                <th>Status</th>
                <th>Created</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>

              <?php foreach ($adminAccounts as $acc): ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="account-avatar flex-shrink-0">
                        <?= strtoupper(substr($acc['username'], 0, 1)) ?>
                      </div>
                      <div class="ms-3">
                        <div class="fw-semibold"><?= htmlspecialchars($acc['username']) ?></div>
                      </div>
                    </div>
                  </td>

                  <td><?= htmlspecialchars($acc['full_name']) ?></td>

                  <td>
                    <a href="mailto:<?= htmlspecialchars($acc['email']) ?>" class="text-decoration-none">
                      <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($acc['email']) ?>
                    </a>
                  </td>

                  <td>
                    <span class="status-badge status-<?= $acc['status'] ?>">
                      <?= ucfirst($acc['status']) ?>
                    </span>
                  </td>

                  <td><small class="text-muted"><?= formatDate($acc['created_at']) ?></small></td>

                  <td class="text-end">
                    <div class="action-buttons btn-group btn-group-sm">


                      <button type="button" class="btn-action btn-reset" data-bs-toggle="modal"
                        data-bs-target="#resetPasswordModal" data-user-id="<?= (int) $acc['id'] ?>"
                        data-user-name="<?= htmlspecialchars($acc['full_name']) ?>" title="Reset Password">
                        <i class="bi bi-key-fill"></i>
                      </button>

                      <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editAccountModal"
                        data-user-id="<?= (int) $acc['id'] ?>" data-username="<?= htmlspecialchars($acc['username']) ?>"
                        data-fullname="<?= htmlspecialchars($acc['full_name']) ?>"
                        data-email="<?= htmlspecialchars($acc['email']) ?>"
                        data-status="<?= htmlspecialchars($acc['status']) ?>"
                        data-branch-id="<?= (int) ($acc['branch_id'] ?? ($acc['business_unit_id'] ?? 0)) ?>"
                        data-branch-name="<?= htmlspecialchars($acc['branch_name'] ?? '') ?>"
                        data-branch-code="<?= htmlspecialchars($acc['branch_code'] ?? '') ?>"
                        data-role="<?= htmlspecialchars($acc['role']) ?>">
                        <i class="bi bi-pencil-square"></i>
                      </button>

                      <?php if ((int) $acc['id'] !== (int) $_SESSION['admin_id']): ?>
                        <a href="accounts.php?action=delete&id=<?= (int) $acc['id'] ?>" class="btn btn-danger"
                          onclick="return confirm('Delete this admin?')">
                          <i class="bi bi-trash"></i>
                        </a>
                      <?php endif; ?>

                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

            </tbody>
          </table>
        </div>
      <?php endif; ?>

    </div>
  <?php endif; ?>





  <?php if ($isSuperAdmin): ?>
    <!-- Super Admin Section -->
    <div class="account-card d-none" id="sectionSupers">
      <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <h5 class="mb-0 fw-bold">
          <i class="bi bi-crown-fill text-warning me-2"></i>Super Admin Accounts
        </h5>
        <span class
          </table>eturn;

          const agency = agencySelect.value;

          if (agency === 'smc') {
            branchWrapper.classList.add('d-none');
            if (branchSelect) branchSelect.value = '0';
          } else {
            branchWrapper.classList.remove('d-none');
          }
        }

        if (agencySelect) {
          agencySelect.addEventListener('change', updateBranchVisibility);
          updateBranchVisibility(); // initial state
        }

      })();


      agencyTabs.forEach(tab => {
        tab.addEventListener('shown.bs.tab', (e) => {
          const code = e.target.getAttribute('data-agency');
          updateAgencyFields(code);
        });
      });

      if (agencyTabs.length > 0) {
        const activeTab = document.querySelector('#agencyTabs .active');
        if (activeTab) updateAgencyFields(activeTab.getAttribute('data-agency'));
      }

      const addForm = document.getElementById('addAccountForm');
      if (addForm) {
        addForm.addEventListener('submit', () => {
          document.querySelectorAll('#addAccountForm .tab-pane').forEach(pane => {
            if (!pane.classList.contains('active')) {
              pane.querySelectorAll('input,select,textarea').forEach(el => el.disabled = true);
            }
          });
        });
        const addModal = document.getElementById('addAccountModal');
        if (addModal) {
          addModal.addEventListener('shown.bs.modal', () => {
            addForm.querySelectorAll('[disabled]').forEach(el => el.disabled = false);
          });
        }
      }

      (function () {

        const editModal = document.getElementById('editAccountModal');
        const editForm = document.getElementById('editAccountForm');
        const editBranchWrapper = document.getElementById('editBranchWrapper');
        const editBranch = document.getElementById('editBranch');

        if (!editModal || !editForm) return;

        editModal.addEventListener('shown.bs.modal', (e) => {
          const btn = e.relatedTarget;
          if (!btn) return;

          const role = btn.dataset.role || '';
          const agency = btn.dataset.agency || 'csnk'; // default safe
          const branchId = parseInt(btn.dataset.branchId || 0);

          // Populate fields
          document.getElementById('editUserId').value = btn.dataset.userId || '';
          document.getElementById('editUsername').value = btn.dataset.username || '';
          document.getElementById('editFullName').value = btn.dataset.fullname || '';
          document.getElementById('editEmail').value = btn.dataset.email || '';
          document.getElementById('editStatus').value = btn.dataset.status || 'active';

          // Branch visibility rule
          if (role === 'employee' && agency === 'csnk') {
            editBranchWrapper.classList.remove('d-none');
            editBranch.value = branchId;
          } else {
            editBranchWrapper.classList.add('d-none');
            editBranch.value = '0';
          }

          editForm.classList.add('was-validated');
        });

        editModal.addEventListener('hidden.bs.modal', () => {
          editForm.reset();
          editForm.classList.remove('was-validated');
          editBranchWrapper.classList.add('d-none');
        });

      })();

      // Reset password modal (if exists)
      const resetModal = document.getElementById('resetPasswordModal');
      if (resetModal) {
        resetModal.addEventListener('shown.bs.modal', (e) => {
          const btn = e.relatedTarget;
          if (!btn) return;
          document.getElementById('resetUserId').value = parseInt(btn.dataset.userId || 0);
          document.getElementById('resetUsername').value = btn.dataset.userName || '';
          document.getElementById('resetUserName').textContent = btn.dataset.userName || '—';
        });
      }

      // Modern form enhancements
      // Password toggle visibility
      document.querySelectorAll('.pwd-eye').forEach(eye => {
        eye.addEventListener('click', () => {
          const input = eye.closest('.field-group').querySelector('input');
          const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
          input.setAttribute('type', type);
          eye.classList.toggle('bi-eye');
          eye.classList.toggle('bi-eye-slash');
          eye.classList.toggle('active');
        });
        eye.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            eye.click();
          }
        });
      });

      // Password strength meter
      const pwdInput = document.getElementById('pwdInput');
      const pwdBar = document.getElementById('pwdBar');
      if (pwdInput && pwdBar) {
        pwdInput.addEventListener('input', () => {
          const pwd = pwdInput.value;
          let score = 0;
          let width = 0;
          
          if (pwd.length >= 10) score++;
          if (/[A-Z]/.test(pwd)) score++;
          if (/[a-z]/.test(pwd)) score++;
          if (/\d/.test(pwd)) score++;
          if (/[\W_]/.test(pwd)) score++;
          
          switch(score) {
            case 0: case 1: width = 20; pwdBar.className = 'progress-bar pwd-weak'; break;
            case 2: case 3: width = 50; pwdBar.className = 'progress-bar pwd-fair'; break;
            case 4: width = 75; pwdBar.className = 'progress-bar pwd-good'; break;
            case 5: width = 100; pwdBar.className = 'progress-bar pwd-strong'; break;
          }
          pwdBar.style.width = width + '%';
        });
      }

      // Password match validation
      const pwd2Input = document.getElementById('pwdInput2');
      const matchHint = document.getElementById('pwdMatchHint');
      if (pwdInput && pwd2Input && matchHint) {
        pwd2Input.addEventListener('input', () => {
          if (pwd2Input.value === pwdInput.value && pwd2Input.value !== '') {
            matchHint.textContent = 'Passwords match ✓';
            matchHint.className = 'form-text text-success';
          } else if (pwd2Input.value !== '') {
            matchHint.textContent = 'Passwords do not match';
            matchHint.className = 'form-text text-danger';
          } else {
            matchHint.textContent = '';
          }
        });
      }

      // Email validation hint
      const emailInput = document.getElementById('emailInput');
      const emailHint = document.getElementById('emailHint');
      if (emailInput && emailHint) {
        emailInput.addEventListener('blur', () => {
          const email = emailInput.value;
          if (email) {
            if (validateEmailStrictDetectTypos(email)[0]) {
              emailHint.textContent = 'Valid email ✓';
              emailHint.className = 'form-text text-success';
            } else {
              emailHint.textContent = 'Please check email format';
              emailHint.className = 'form-text text-danger';
            }
          }
        });
      }

      // Smooth animations (existing)
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
          }
        });
      });
      document.querySelectorAll('.account-card').forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
      });
    })();
  </script>


  <?php require_once '../includes/footer.php'; ?>