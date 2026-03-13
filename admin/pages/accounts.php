<?php
$pageTitle = 'Accounts Management';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);

$role = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');
$isEmployee   = ($role === 'employee');

$currentAgency = $currentUser['agency'] ?? null;

 // Branch loading + filters
$branches = $admin->getActiveBranches();
$allBranches = [0 => 'All Branches'] + $branches;

// load agency list from db (codes like 'csnk','smc' with human name)
$agencies = $admin->getAgencies();
$filterAgency = sanitizeInput($_GET['agency'] ?? '');
$filterBranch = (int)($_GET['branch'] ?? 0);
$filterStatus = sanitizeInput($_GET['status'] ?? '');
$filterSearch = sanitizeInput($_GET['search'] ?? '');
$errors = [];

function validateStrongPassword(string $pwd): ?string {
    if (mb_strlen($pwd) < 10) return 'Password must be at least 10 characters.';
    if (!preg_match('/[A-Z]/', $pwd)) return 'Password must include at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $pwd)) return 'Password must include at least one lowercase letter.';
    if (!preg_match('/\d/', $pwd)) return 'Password must include at least one number.';
    if (!preg_match('/[\W_]/', $pwd)) return 'Password must include at least one special character.';
    if (preg_match('/(.)\1{3,}/', $pwd)) return 'Password should not contain repeated characters.';
    return null;
}

function validateEmailStrictDetectTypos(string $email): array {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return [false, 'Invalid email format.', null];
    [$local, $domain] = explode('@', $email, 2);
    $domain = strtolower($domain);
    if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,10}$/i', $domain)) return [false, 'Email domain looks invalid.', null];

    $known = ['gmail.com','yahoo.com','outlook.com','hotmail.com'];
    $closest = null; $minDist = PHP_INT_MAX;
    foreach ($known as $k) {
        $d = levenshtein($domain, $k);
        if ($d < $minDist) { $minDist = $d; $closest = $k; }
    }
    if ($minDist === 1 && $closest === 'gmail.com') {
        return [false, 'Did you mean @gmail.com?', $local.'@gmail.com'];
    }
    return [true, null, null];
}

function forbidAndBack(string $msg = 'You do not have permission.') {
    setFlashMessage('error', $msg);
    redirect('accounts.php'); exit;
}

/* DELETE */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $targetId = (int)$_GET['id'];
    if ($targetId === (int)$_SESSION['admin_id']) {
        setFlashMessage('error', 'You cannot delete your own account.');
        redirect('accounts.php'); exit;
    }

    $target = $admin->getById($targetId);
    if (!$target) { setFlashMessage('error','Account not found.'); redirect('accounts.php'); exit; }

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
    redirect('accounts.php'); exit;
}

/* ADD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    if (!($isSuperAdmin || $isAdmin)) forbidAndBack('Only Admins or Super Admins can add accounts.');

    $username  = sanitizeInput($_POST['username'] ?? '');
    $fullName  = sanitizeInput($_POST['full_name'] ?? '');
    $email     = sanitizeInput($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $roleNew   = sanitizeInput($_POST['role'] ?? 'employee');
    $branchIdNew = (int)($_POST['business_unit_id'] ?? 0);
    // agency comes from hidden field set by JS/tabs
    $agencyNew = sanitizeInput($_POST['agency'] ?? '');
    $validCodes = array_column($agencies, 'code');
    if (!in_array($agencyNew, $validCodes, true)) {
        // fallback to first agency or empty
        $agencyNew = $agencies[0]['code'] ?? '';
    }

    $allowedRoles = $isSuperAdmin ? ['employee','admin','super_admin'] : ['employee','admin'];
    if (!in_array($roleNew, $allowedRoles, true)) $roleNew = 'employee';
    // non-CSNK agencies are always employee accounts
    if ($agencyNew !== 'csnk') {
        $roleNew = 'employee';
        // branch doesn't apply for other agencies
        $branchIdNew = null;
    }

    if ($username === '') $errors[] = 'Username is required.';
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '')    $errors[] = 'Email is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $password2) $errors[] = 'Passwords do not match.';

    if ($email !== '') {
        [$ok, $err, $suggest] = validateEmailStrictDetectTypos($email);
        if (!$ok) $errors[] = $err . ($suggest ? ' Suggested: '.htmlspecialchars($suggest) : '');
    }
    if ($password !== '') {
        $pwdErr = validateStrongPassword($password);
        if ($pwdErr) $errors[] = $pwdErr;
    }
    if ($username !== '' && $admin->usernameExists($username)) $errors[] = 'Username already exists.';
    if ($email !== '' && $admin->emailExists($email)) $errors[] = 'Email already exists.';

    // branch requirement only enforced for csnk agency employees
    if ($roleNew === 'employee' && $agencyNew === 'csnk' && $branchIdNew <= 0) {
        $errors[] = 'Branch is required for Employee accounts.';
    }

    if (empty($errors)) {
        $data = [
            'username'  => $username,
            'email'     => $email,
            'full_name' => $fullName,
            'password'  => $password,
            'role'      => $roleNew,
            'status'    => 'active',
            'business_unit_id' => $branchIdNew ?: null,
            'agency'    => $agencyNew,
        ];
        if ($admin->create($data)) {
            $branchLabel = $branchIdNew > 0 ? " (Branch ID:{$branchIdNew})" : '';
            $auth->logActivity($_SESSION['admin_id'], 'Create Account', "Created {$roleNew} {$username}{$branchLabel}");
            setFlashMessage('success','Account created successfully.');
            redirect('accounts.php'); exit;
        } else {
            $errors[] = 'Failed to create account.';
        }
    }
}

/* RESET PASSWORD */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!$isSuperAdmin) forbidAndBack('Only Super Admin can recover accounts.');

    $userId = (int)($_POST['user_id'] ?? 0);
    $newPwd = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    if ($userId <= 0) $errors[] = 'Invalid account.';
    if ($newPwd === '') $errors[] = 'New password is required.';
    if ($newPwd !== $conf) $errors[] = 'Passwords do not match.';
    $pwdErr = validateStrongPassword($newPwd);
    if ($pwdErr) $errors[] = $pwdErr;

    $target = $admin->getById($userId);
    if (!$target) $errors[] = 'Account not found.';

    if (empty($errors)) {
        if ($admin->updatePassword($userId, $newPwd)) {
            $auth->logActivity($_SESSION['admin_id'], 'Recover Account', "Reset password ID {$userId}");
            setFlashMessage('success', 'Password reset successfully.');
            redirect('accounts.php'); exit;
        } else {
            $errors[] = 'Failed to reset password.';
        }
    }
}

/* EDIT */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_account'])) {
    $userId   = (int)($_POST['edit_user_id'] ?? 0);
    $username = sanitizeInput($_POST['edit_username'] ?? '');
    $fullName = sanitizeInput($_POST['edit_full_name'] ?? '');
    $email    = sanitizeInput($_POST['edit_email'] ?? '');
    $status   = $_POST['edit_status'] ?? 'active';

    if ($userId <= 0)    $errors[] = 'Invalid account.';
    if ($username === '') $errors[] = 'Username is required.';
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '')    $errors[] = 'Email is required.';
    if (!in_array($status, ['active','inactive'], true)) $status = 'active';

    $target = $admin->getById($userId);
    if (!$target) $errors[] = 'Account not found.';

    if ($isSuperAdmin) {
        // can edit all
    } elseif ($isAdmin) {
        if (!in_array($target['role'], ['employee','admin'], true)) {
            forbidAndBack('Admins can edit only Employee/Admin accounts.');
        }
    } else {
        forbidAndBack('Employees cannot edit accounts.');
    }

    if ($email !== '') {
        [$ok, $err, $suggest] = validateEmailStrictDetectTypos($email);
        if (!$ok) $errors[] = $err . ($suggest ? ' Suggested: '.htmlspecialchars($suggest) : '');
    }
    if ($username !== '' && $admin->usernameExists($username, $userId)) $errors[] = 'Username already in use.';
    if ($email !== '' && $admin->emailExists($email, $userId)) $errors[] = 'Email already in use.';

    $data = [
        'username'  => $username,
        'email'     => $email,
        'full_name' => $fullName,
        'role'      => $target['role'],
        'status'    => $status,
        'business_unit_id' => $target['business_unit_id'] ?? null,
    ];

    if (empty($errors)) {
        if ($admin->update($userId, $data)) {
            $auth->logActivity($_SESSION['admin_id'], 'Edit Account', "Edited ID {$userId}");
            setFlashMessage('success', 'Account updated successfully.');
            redirect('accounts.php'); exit;
        } else {
            $errors[] = 'Failed to update account.';
        }
    }
}

/* ============================
   Load lists for the view (with branch/status/search filters)
============================= */

$rawEmployees = $admin->getByAgency($filterAgency ?: null, 'employee');
$rawAdmins    = $admin->getByAgency($filterAgency ?: null, 'admin');
// super-admin accounts are global; ignore agency filter when fetching
$rawSupers    = $isSuperAdmin ? $admin->getByAgency(null, 'super_admin') : [];

// Hide branch filters for SMC
if ($filterAgency === 'smc') {
    $filterBranch = 0;
}

/* Helper to apply filters + RBAC */
function applyAccountFilters(
    array $accounts,
    int $filterBranch,
    string $filterStatus,
    string $filterSearch,
    bool $isSuperAdmin,
    bool $isAdmin,
    ?string $currentAgency
): array {

    return array_values(array_filter($accounts, function($acc)
        use ($filterBranch, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, $currentAgency) {

        // Branch filter (skip for admins and super admins)
        $branchId = (int)($acc['business_unit_id'] ?? ($acc['branch_id'] ?? 0));
        if ($filterBranch > 0) {
            if (($acc['role'] ?? '') !== 'super_admin' && $branchId !== $filterBranch) {
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

        // RBAC (employees only)
        if (!$isSuperAdmin && !$isAdmin && $currentAgency && ($acc['agency'] ?? null) !== $currentAgency) {
            return false;
        }

        return true;
    }));
}

$employeeAccounts = applyAccountFilters($rawEmployees, $filterBranch, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, $currentAgency);
$adminAccounts    = applyAccountFilters($rawAdmins, $filterBranch, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, null);
$superAccounts    = applyAccountFilters($rawSupers, $filterBranch, $filterStatus, $filterSearch, $isSuperAdmin, $isAdmin, null);
?>
<style>

.modern-accounts {
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

.hero-section {
background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #1e293b;
  padding: 3rem 0;
  border-radius: 24px;
  margin-bottom: 2rem;
  text-align: center;
  box-shadow: 0 20px 40px rgba(0,0,0,0.08);
}

.hero-section h1 {
  font-size: clamp(2rem, 5vw, 3.5rem);
  font-weight: 800;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.hero-section .lead {
  font-size: 1.2rem;
  opacity: 0.95;
}

.filter-container {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  margin-bottom: 2rem;
  backdrop-filter: blur(10px);
}

.filter-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: center;
}

.filter-btn {
  border-radius: 50px;
  padding: 0.75rem 1.5rem;
  border: 2px solid #e5e7eb;
  background: white;
  font-weight: 600;
  transition: all 0.3s ease;
  text-decoration: none;
  color: #374151;
}

.filter-btn:hover {
  border-color: #6b7280;
  color: #374151;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.filter-btn.active {
  background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #1f2937;
  border-color: #d1d5db;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.accounts-grid {
  display: grid;
  gap: 1.5rem;
}

.account-card {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 4px 20px rgba(0,0,0,0.08);
  border: 1px solid #f1f5f9;
  transition: all 0.3s ease;
}
  .account-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.account-avatar {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: 700;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.account-info {
  flex: 1;
  margin-left: 1rem;
}

.account-name {
  font-weight: 700;
  font-size: 1.1rem;
  margin-bottom: 0.25rem;
  color: #1e293b;
}

.account-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 1rem;
  font-size: 0.875rem;
  color: #6b7280;
}

.status-badge {
  padding: 0.4rem 0.8rem;
  border-radius: 50px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.status-active {
  background: rgba(16,185,129,0.15);
  color: #059669;
  border: 1px solid rgba(16,185,129,0.3);
}

.status-inactive {
  background: rgba(107,114,128,0.15);
  color: #6b7280;
  border: 1px solid rgba(107,114,128,0.3);
}

.branch-tag {
  padding: 0.3rem 0.8rem;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #374151;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid #e2e8f0;
}

.action-buttons {
  display: flex;
  gap: 0.5rem;
}

.btn-action {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: none;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.btn-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.btn-edit { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; }
.btn-reset { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; }
.btn-delete { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; }

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  color: #6b7280;
}

.empty-state-icon {
  font-size: 4rem;
  opacity: 0.5;
  margin-bottom: 1rem;
}

@media (max-width: 768px) {
  .hero-section { padding: 2rem 1rem; }
  .filter-container { padding: 1rem; }
  .accounts-grid { grid-template-columns: 1fr; }
}

@media (min-width: 1200px) {
  .accounts-grid { grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); }
}
</style>


<button
  class="btn btn-primary shadow-lg"
  style="
    position: fixed;
    bottom: 25px;
    right: 25px;
    border-radius: 50px;
    padding: 14px 28px;
    font-weight: 600;
    z-index: 1000;
  "
  data-bs-toggle="modal"
  data-bs-target="#addAccountModal">

  <i class="bi bi-plus-circle me-2"></i>Create Account
</button>

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

        <!-- Role Filter -->
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
        
        <!-- Branch Filter -->
        <!-- Quick branch buttons (CSNK only) -->
        <?php if ($filterAgency === 'csnk'): ?>
          <?php foreach ($branches as $branch): ?>
            <a href="accounts.php?agency=csnk&branch=<?= (int)$branch['id'] ?>&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>" 
               class="btn btn-outline-primary btn-sm ms-2 <?= $filterBranch === (int)$branch['id'] ? 'active' : '' ?>">
              <?= htmlspecialchars($branch['code']) ?>
            </a>
          <?php endforeach; ?>
          <a href="accounts.php?agency=csnk&branch=0&status=<?= urlencode($filterStatus) ?>&search=<?= urlencode($filterSearch) ?>" 
             class="btn btn-secondary btn-sm ms-2 <?= $filterBranch === 0 ? 'active' : '' ?>">All CSNK</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-4 text-end">
      <!-- Filter form - preserve all GET params -->
      <form method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search..." value="<?= htmlspecialchars($filterSearch) ?>">
        <select name="status" class="form-select form-select-sm">
          <option value="">All Status</option>
          <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>>Active</option>
          <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <select name="agency" class="form-select form-select-sm">
          <option value="">All Agencies</option>
          <option value="csnk" <?= $filterAgency === 'csnk' ? 'selected' : '' ?>>CSNK</option>
          <option value="smc" <?= $filterAgency === 'smc' ? 'selected' : '' ?>>SMC</option>
        </select>
        <?php if ($filterAgency === 'csnk'): ?>
          <select name="branch" class="form-select form-select-sm" id="branchSelectFilter">
            <option value="0">All CSNK Branches</option>
            <?php foreach ($branches as $b): ?>
              <option value="<?= (int)$b['id'] ?>" <?= $filterBranch === (int)$b['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($b['name']) ?> (<?= htmlspecialchars($b['code']) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
        <button type="submit" class="btn btn-outline-primary btn-sm">Filter</button>
        <a href="accounts.php" class="btn btn-outline-secondary btn-sm">Clear</a>
      </form>
    </div>
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
          <button type="button" class="btn btn-primary btn-lg px-4" data-bs-toggle="modal" data-bs-target="#addAccountModal">
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
                <td>
                  <div class="d-flex align-items-center">
                    <div class="account-avatar flex-shrink-0">
                      <?= strtoupper(substr($acc['username'], 0, 1)) ?>
                    </div>
                    <div class="ms-3">
                      <div class="fw-semibold"><?= htmlspecialchars($acc['username']) ?></div>
                      <?php if ((int)$acc['id'] === (int)$_SESSION['admin_id']): ?>
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
                    <span class="branch-tag"><?= htmlspecialchars($acc['branch_code']) ?> - <?= htmlspecialchars($acc['branch_name']) ?></span>
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
                    <button type="button" class="btn-action btn-reset" data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                      data-user-id="<?= (int)$acc['id'] ?>" data-user-name="<?= htmlspecialchars($acc['full_name']) ?>" title="Reset Password">
                      <i class="bi bi-key-fill"></i>
                    </button>
<button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#editAccountModal"
                      data-user-id="<?= (int)$acc['id'] ?>"
                      data-username="<?= htmlspecialchars($acc['username']) ?>"
                      data-fullname="<?= htmlspecialchars($acc['full_name']) ?>"
                      data-email="<?= htmlspecialchars($acc['email']) ?>"
                      data-status="<?= htmlspecialchars($acc['status']) ?>"
                      data-branch-id="<?= (int)($acc['branch_id'] ?? ($acc['business_unit_id'] ?? 0)) ?>"
                      data-branch-name="<?= htmlspecialchars($acc['branch_name'] ?? '') ?>"
                      data-branch-code="<?= htmlspecialchars($acc['branch_code'] ?? '') ?>"
                      data-role="<?= htmlspecialchars($acc['role']) ?>" title="Edit">
                      <i class="bi bi-pencil-square"></i>
                    </button>
                    <?php if ((int)$acc['id'] !== (int)$_SESSION['admin_id']): ?>
                      <a href="accounts.php?action=delete&id=<?= (int)$acc['id'] ?>" class="btn btn-danger"
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
          <th class="