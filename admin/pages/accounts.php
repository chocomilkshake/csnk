<?php
$pageTitle = 'Accounts Management';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);
$errors = [];

$role = $currentUser['role'] ?? 'employee';
$isSuperAdmin = ($role === 'super_admin');
$isAdmin      = ($role === 'admin');
$isEmployee   = ($role === 'employee');

/* ============================
   Validation helpers
============================= */
function validateStrongPassword(string $pwd): ?string {
    // min 10 chars, 1 upper, 1 lower, 1 digit, 1 special, avoid repeated chars
    if (mb_strlen($pwd) < 10) return 'Password must be at least 10 characters.';
    if (!preg_match('/[A-Z]/', $pwd)) return 'Password must include at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $pwd)) return 'Password must include at least one lowercase letter.';
    if (!preg_match('/\d/', $pwd)) return 'Password must include at least one number.';
    if (!preg_match('/[\W_]/', $pwd)) return 'Password must include at least one special character.';
    if (preg_match('/(.)\1{3,}/', $pwd)) return 'Password should not contain repeated characters.';
    return null;
}
/**
 * Email validation with gmail typo suggestion
 * Returns [ok, errorMsg?, suggestion?]
 */
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

/* ============================
   RBAC guards
============================= */
function forbidAndBack(string $msg = 'You do not have permission.') {
    setFlashMessage('error', $msg);
    redirect('accounts.php'); exit;
}

/* ============================
   DELETE
============================= */
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'delete') {
    $targetId = (int)$_GET['id'];
    if ($targetId === (int)$_SESSION['admin_id']) {
        setFlashMessage('error', 'You cannot delete your own account.');
        redirect('accounts.php'); exit;
    }

    $target = $admin->getById($targetId);
    if (!$target) { setFlashMessage('error','Account not found.'); redirect('accounts.php'); exit; }

    if ($isSuperAdmin) {
        // super admin can delete any (except self handled above)
    } elseif ($isAdmin) {
        // admin can delete employee + admin only
        if (!in_array($target['role'], ['employee', 'admin'], true)) {
            forbidAndBack('Admins can delete only Employee/Admin accounts.');
        }
    } else {
        forbidAndBack(); // employee cannot delete
    }

    if ($admin->delete($targetId)) {
        $auth->logActivity($_SESSION['admin_id'], 'Delete Account', "Deleted ID {$targetId}");
        setFlashMessage('success', 'Account deleted successfully.');
    } else {
        setFlashMessage('error', 'Failed to delete account.');
    }
    redirect('accounts.php'); exit;
}

/* ============================
   ADD
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    if (!($isSuperAdmin || $isAdmin)) forbidAndBack('Only Admins or Super Admins can add accounts.');

    $username  = sanitizeInput($_POST['username'] ?? '');
    $fullName  = sanitizeInput($_POST['full_name'] ?? '');
    $email     = sanitizeInput($_POST['email'] ?? '');
    $password  = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password2'] ?? '');
    $roleNew   = sanitizeInput($_POST['role'] ?? 'employee');
    $agencyNew = sanitizeInput($_POST['agency'] ?? ''); // NEW

    // Allowed roles based on current user's role
    $allowedRoles = $isSuperAdmin ? ['employee','admin','super_admin'] : ['employee','admin'];
    if (!in_array($roleNew, $allowedRoles, true)) $roleNew = 'employee';

    if ($username === '') $errors[] = 'Username is required.';
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '')    $errors[] = 'Email is required.';
    if ($password === '') $errors[] = 'Password is required.';
    if ($password !== $password2) $errors[] = 'Passwords do not match.';

    if ($email !== '') {
        [$ok, $err, $suggest] = validateEmailStrictDetectTypos($email);
        if (!$ok) $errors[] = $err . ($suggest ? ' Suggested: '.htmlspecialchars($suggest,ENT_QUOTES,'UTF-8') : '');
    }
    if ($password !== '') {
        $pwdErr = validateStrongPassword($password);
        if ($pwdErr) $errors[] = $pwdErr;
    }
    if ($username !== '' && $admin->usernameExists($username)) $errors[] = 'Username already exists.';
    if ($email !== '' && $admin->emailExists($email)) $errors[] = 'Email already exists.';

    // NEW: agency required only for EMPLOYEE
    if ($roleNew === 'employee') {
        if (!in_array($agencyNew, ['csnk','smc'], true)) {
            $errors[] = 'Agency is required for Employee accounts.';
        }
    } else {
        $agencyNew = null; // admin/super_admin must be global
    }

    if (empty($errors)) {
        $data = [
            'username'  => $username,
            'email'     => $email,
            'full_name' => $fullName,
            'password'  => $password,
            'role'      => $roleNew,
            'status'    => 'active', // default
            'agency'    => $agencyNew, // NEW
        ];
        if ($admin->create($data)) {
            $auth->logActivity($_SESSION['admin_id'], 'Create Account', "Created {$roleNew} {$username}".($agencyNew ? " ({$agencyNew})" : ''));
            setFlashMessage('success','Account created successfully.');
            redirect('accounts.php'); exit;
        } else {
            $errors[] = 'Failed to create account.';
        }
    }
}

/* ============================
   RESET PASSWORD (Recover) — Super Admin only
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!$isSuperAdmin) forbidAndBack('Only Super Admin can recover accounts.');

    $userId = (int)($_POST['user_id'] ?? 0);
    $newPwd = (string)($_POST['new_password'] ?? '');
    $conf   = (string)($_POST['confirm_password'] ?? '');

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

/* ============================
   EDIT
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_account'])) {
    $userId   = (int)($_POST['edit_user_id'] ?? 0);
    $username = sanitizeInput($_POST['edit_username'] ?? '');
    $fullName = sanitizeInput($_POST['edit_full_name'] ?? '');
    $email    = sanitizeInput($_POST['edit_email'] ?? '');
    $status   = sanitizeInput($_POST['edit_status'] ?? 'active');
    $postedAgency = sanitizeInput($_POST['edit_agency'] ?? ''); // NEW

    if ($userId <= 0)    $errors[] = 'Invalid account.';
    if ($username === '') $errors[] = 'Username is required.';
    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($email === '')    $errors[] = 'Email is required.';
    if (!in_array($status, ['active','inactive'], true)) $status = 'active';

    $target = $admin->getById($userId);
    if (!$target) $errors[] = 'Account not found.';

    // RBAC for edit
    if ($isSuperAdmin) {
        // can edit all roles
    } elseif ($isAdmin) {
        if (!$target || !in_array($target['role'], ['employee','admin'], true)) {
            forbidAndBack('Admins can edit only Employee/Admin accounts.');
        }
    } else {
        forbidAndBack('Employees cannot edit accounts.');
    }

    if ($email !== '') {
        [$ok, $err, $suggest] = validateEmailStrictDetectTypos($email);
        if (!$ok) $errors[] = $err . ($suggest ? ' Suggested: '.htmlspecialchars($suggest,ENT_QUOTES,'UTF-8') : '');
    }
    if ($username !== '' && $admin->usernameExists($username, $userId)) $errors[] = 'Username already in use.';
    if ($email !== '' && $admin->emailExists($email, $userId))         $errors[] = 'Email already in use.';

    // NEW: only enforce agency for EMPLOYEE targets
    $data = [
        'username'  => $username,
        'email'     => $email,
        'full_name' => $fullName,
        'role'      => $target['role'],
        'status'    => $status,
    ];
    if ($target && $target['role'] === 'employee') {
        if (!in_array($postedAgency, ['csnk','smc'], true)) {
            $errors[] = 'Agency is required for Employee accounts.';
        } else {
            $data['agency'] = $postedAgency;
        }
    }

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
   Load lists for the view
============================= */
$employeeAccounts = $admin->getByRole('employee');
$adminAccounts    = $admin->getByRole('admin');
$superAccounts    = $isSuperAdmin ? $admin->getByRole('super_admin') : []; // only SA can view
?>
<div class="d-flex align-items-center justify-content-between mb-3">
  <div class="d-flex align-items-center gap-2 flex-wrap">
    <!-- Top-left segmented view buttons -->
    <?php if ($isSuperAdmin): ?>
      <div class="btn-group" role="group" aria-label="View filter">
        <button type="button" class="btn btn-outline-primary active" id="btnViewEmployees">Employees</button>
        <button type="button" class="btn btn-outline-primary" id="btnViewAdmins">Admins</button>
        <button type="button" class="btn btn-outline-primary" id="btnViewSupers">Super Admins</button>
      </div>
    <?php elseif ($isAdmin): ?>
      <div class="btn-group" role="group" aria-label="View filter">
        <button type="button" class="btn btn-outline-primary active" id="btnViewEmployees">Employees</button>
        <button type="button" class="btn btn-outline-primary" id="btnViewAdmins">Admins</button>
      </div>
    <?php else: ?>
    <?php endif; ?>
  </div>

  <div>
    <?php if ($isSuperAdmin || $isAdmin): ?>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
        <i class="bi bi-plus-circle me-2"></i>Add Account
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?php echo $e; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Employee Table -->
<div class="card mb-3" id="sectionEmployees">
  <div class="card-header bg-white border-0">
    <h6 class="mb-0">Employee Accounts</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Agency</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($employeeAccounts)): ?>
          <tr><td colspan="7" class="text-center text-muted">No employee accounts.</td></tr>
        <?php else: foreach ($employeeAccounts as $acc): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($acc['username']); ?></strong>
              <?php if ((int)$acc['id'] === (int)$_SESSION['admin_id']): ?>
                <span class="badge bg-info ms-2">You</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($acc['full_name']); ?></td>
            <td><?php echo htmlspecialchars($acc['email']); ?></td>
            <td>
              <?php
                $ag = $acc['agency'] ?? null;
                echo $ag ? strtoupper($ag) : '<span class="text-muted">—</span>';
              ?>
            </td>
            <td>
              <span class="badge bg-<?php echo $acc['status']==='active'?'success':'secondary'; ?>">
                <?php echo ucfirst($acc['status']); ?>
              </span>
            </td>
            <td><?php echo formatDate($acc['created_at']); ?></td>
            <td>
              <?php if ($isSuperAdmin): ?>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-secondary"
                          data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                          data-user-id="<?php echo (int)$acc['id']; ?>"
                          data-user-name="<?php echo htmlspecialchars($acc['full_name']); ?>"
                          title="Recover (Reset Password)"><i class="bi bi-key"></i></button>

                  <button type="button" class="btn btn-sm btn-warning"
                          data-bs-toggle="modal" data-bs-target="#editAccountModal"
                          data-user-id="<?php echo (int)$acc['id']; ?>"
                          data-username="<?php echo htmlspecialchars($acc['username']); ?>"
                          data-fullname="<?php echo htmlspecialchars($acc['full_name']); ?>"
                          data-email="<?php echo htmlspecialchars($acc['email']); ?>"
                          data-status="<?php echo htmlspecialchars($acc['status']); ?>"
                          data-agency="<?php echo htmlspecialchars((string)$acc['agency']); ?>"
                          data-role="employee"
                          title="Edit"><i class="bi bi-pencil-square"></i></button>

                  <?php if ((int)$acc['id'] !== (int)$_SESSION['admin_id']): ?>
                    <a href="accounts.php?action=delete&id=<?php echo (int)$acc['id']; ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this account?');"
                       title="Delete"><i class="bi bi-trash"></i></a>
                  <?php endif; ?>
                </div>
              <?php elseif ($isAdmin): ?>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-warning"
                          data-bs-toggle="modal" data-bs-target="#editAccountModal"
                          data-user-id="<?php echo (int)$acc['id']; ?>"
                          data-username="<?php echo htmlspecialchars($acc['username']); ?>"
                          data-fullname="<?php echo htmlspecialchars($acc['full_name']); ?>"
                          data-email="<?php echo htmlspecialchars($acc['email']); ?>"
                          data-status="<?php echo htmlspecialchars($acc['status']); ?>"
                          data-agency="<?php echo htmlspecialchars((string)$acc['agency']); ?>"
                          data-role="employee"
                          title="Edit"><i class="bi bi-pencil-square"></i></button>

                  <?php if ((int)$acc['id'] !== (int)$_SESSION['admin_id']): ?>
                    <a href="accounts.php?action=delete&id=<?php echo (int)$acc['id']; ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this account?');"
                       title="Delete"><i class="bi bi-trash"></i></a>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Admin Table -->
<?php if ($isSuperAdmin || $isAdmin): ?>
<div class="card mb-3 d-none" id="sectionAdmins">
  <div class="card-header bg-white border-0">
    <h6 class="mb-0">Admin Accounts</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($adminAccounts)): ?>
          <tr><td colspan="6" class="text-center text-muted">No admin accounts.</td></tr>
        <?php else: foreach ($adminAccounts as $acc): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($acc['username']); ?></strong>
              <?php if ((int)$acc['id'] === (int)$_SESSION['admin_id']): ?>
                <span class="badge bg-info ms-2">You</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($acc['full_name']); ?></td>
            <td><?php echo htmlspecialchars($acc['email']); ?></td>
            <td>
              <span class="badge bg-<?php echo $acc['status']==='active'?'success':'secondary'; ?>">
                <?php echo ucfirst($acc['status']); ?>
              </span>
            </td>
            <td><?php echo formatDate($acc['created_at']); ?></td>
            <td>
              <?php if ($isSuperAdmin): ?>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-secondary"
                          data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                          data-user-id="<?php echo (int)$acc['id']; ?>"
                          data-user-name="<?php echo htmlspecialchars($acc['full_name']); ?>"
                          title="Recover (Reset Password)"><i class="bi bi-key"></i></button>

                  <button type="button" class="btn btn-sm btn-warning"
                          data-bs-toggle="modal" data-bs-target="#editAccountModal"
                          data-user-id="<?php echo (int)$acc['id']; ?>"
                          data-username="<?php echo htmlspecialchars($acc['username']); ?>"
                          data-fullname="<?php echo htmlspecialchars($acc['full_name']); ?>"
                          data-email="<?php echo htmlspecialchars($acc['email']); ?>"
                          data-status="<?php echo htmlspecialchars($acc['status']); ?>"
                          data-agency="" data-role="admin"
                          title="Edit"><i class="bi bi-pencil-square"></i></button>

                  <?php if ((int)$acc['id'] !== (int)$_SESSION['admin_id']): ?>
                    <a href="accounts.php?action=delete&id=<?php echo (int)$acc['id']; ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this account?');"
                       title="Delete"><i class="bi bi-trash"></i></a>
                  <?php endif; ?>
                </div>
              <?php elseif ($isAdmin): ?>
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-warning"
                          data-bs-toggle="modal" data-bs-target="#editAccountModal"
                          data-user-id="<?php echo (int)$acc['id']; ?>"
                          data-username="<?php echo htmlspecialchars($acc['username']); ?>"
                          data-fullname="<?php echo htmlspecialchars($acc['full_name']); ?>"
                          data-email="<?php echo htmlspecialchars($acc['email']); ?>"
                          data-status="<?php echo htmlspecialchars($acc['status']); ?>"
                          data-agency="" data-role="admin"
                          title="Edit"><i class="bi bi-pencil-square"></i></button>

                  <?php if ((int)$acc['id'] !== (int)$_SESSION['admin_id']): ?>
                    <a href="accounts.php?action=delete&id=<?php echo (int)$acc['id']; ?>"
                       class="btn btn-sm btn-danger"
                       onclick="return confirm('Delete this account?');"
                       title="Delete"><i class="bi bi-trash"></i></a>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Super Admin Table -->
<?php if ($isSuperAdmin): ?>
<div class="card mb-3 d-none" id="sectionSupers">
  <div class="card-header bg-white border-0">
    <h6 class="mb-0">Super Admin Accounts</h6>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Email</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($superAccounts)): ?>
          <tr><td colspan="6" class="text-center text-muted">No super admin accounts.</td></tr>
        <?php else: foreach ($superAccounts as $acc): ?>
          <tr>
            <td>
              <strong><?php echo htmlspecialchars($acc['username']); ?></strong>
              <?php if ((int)$acc['id'] === (int)$_SESSION['admin_id']): ?>
                <span class="badge bg-info ms-2">You</span>
              <?php endif; ?>
            </td>
            <td><?php echo htmlspecialchars($acc['full_name']); ?></td>
            <td><?php echo htmlspecialchars($acc['email']); ?></td>
            <td>
              <span class="badge bg-<?php echo $acc['status']==='active'?'success':'secondary'; ?>">
                <?php echo ucfirst($acc['status']); ?>
              </span>
            </td>
            <td><?php echo formatDate($acc['created_at']); ?></td>
            <td>
              <div class="btn-group">
                <button type="button" class="btn btn-sm btn-secondary"
                        data-bs-toggle="modal" data-bs-target="#resetPasswordModal"
                        data-user-id="<?php echo (int)$acc['id']; ?>"
                        data-user-name="<?php echo htmlspecialchars($acc['full_name']); ?>"
                        title="Recover (Reset Password)"><i class="bi bi-key"></i></button>

                <button type="button" class="btn btn-sm btn-warning"
                        data-bs-toggle="modal" data-bs-target="#editAccountModal"
                        data-user-id="<?php echo (int)$acc['id']; ?>"
                        data-username="<?php echo htmlspecialchars($acc['username']); ?>"
                        data-fullname="<?php echo htmlspecialchars($acc['full_name']); ?>"
                        data-email="<?php echo htmlspecialchars($acc['email']); ?>"
                        data-status="<?php echo htmlspecialchars($acc['status']); ?>"
                        data-agency="" data-role="super_admin"
                        title="Edit"><i class="bi bi-pencil-square"></i></button>

                <?php if ((int)$acc['id'] !== (int)$_SESSION['admin_id']): ?>
                  <a href="accounts.php?action=delete&id=<?php echo (int)$acc['id']; ?>"
                     class="btn btn-sm btn-danger"
                     onclick="return confirm('Delete this account?');"
                     title="Delete"><i class="bi bi-trash"></i></a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($isSuperAdmin || $isAdmin): ?>
<!-- Add Account Modal -->
<div class="modal fade" id="addAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="addAccountForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Add Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="username" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="full_name" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" id="emailInput" required placeholder="name@gmail.com">
            <div class="form-text" id="emailHint"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="password" id="pwdInput" required minlength="10" placeholder="Strong password">
            <div class="progress mt-2" style="height:8px;">
              <div id="pwdBar" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>
            <small class="text-muted">Min 10 chars with uppercase, lowercase, number, and special character.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirm Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="password2" id="pwdInput2" required minlength="10" placeholder="Confirm password">
            <div class="form-text" id="pwdMatchHint"></div>
          </div>

          <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" id="roleSelect">
              <?php if ($isSuperAdmin): ?>
                <option value="employee" selected>Employee</option>
                <option value="admin">Admin</option>
                <option value="super_admin">Super Admin</option>
              <?php else: // admin ?>
                <option value="employee" selected>Employee</option>
                <option value="admin">Admin</option>
              <?php endif; ?>
            </select>
          </div>

          <!-- NEW: Agency (visible only if role = employee) -->
          <div class="mb-3" id="agencyWrapper">
            <label class="form-label">Agency (for Employee) <span class="text-danger">*</span></label>
            <select class="form-select" name="agency" id="agencySelect">
              <option value="">-- Select agency --</option>
              <option value="csnk">CSNK</option>
              <option value="smc">SMC</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="add_account" class="btn btn-primary">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Edit Account Modal -->
<?php if ($isSuperAdmin || $isAdmin): ?>
<div class="modal fade" id="editAccountModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="editAccountForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Edit Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_user_id" id="editUserId">
          <input type="hidden" id="editRoleHidden" value="">
          <div class="mb-3">
            <label class="form-label">Username <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="edit_username" id="editUsername" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="edit_full_name" id="editFullName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="edit_email" id="editEmail" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="edit_status" id="editStatus">
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>

          <!-- NEW: Agency shown only if editing an EMPLOYEE -->
          <div class="mb-3 d-none" id="editAgencyWrapper">
            <label class="form-label">Agency (Employee)</label>
            <select class="form-select" name="edit_agency" id="editAgency">
              <option value="">-- Select agency --</option>
              <option value="csnk">CSNK</option>
              <option value="smc">SMC</option>
            </select>
          </div>

          <small class="text-muted">Role is not changed here.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="edit_account" class="btn btn-warning">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Reset Password Modal (Recover) — Super Admin only -->
<?php if ($isSuperAdmin): ?>
<div class="modal fade" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="resetPwdForm" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Recover Account (Reset Password)</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="user_id" id="resetUserId">
          <div class="mb-2">
            <div class="small text-muted">Resetting password for:</div>
            <div class="fw-semibold" id="resetUserName">—</div>
          </div>
          <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" class="form-control" name="new_password" id="newPwd" required minlength="10">
          </div>
          <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" class="form-control" name="confirm_password" id="newPwd2" required minlength="10">
          </div>
          <small class="text-muted">Min 10 chars with uppercase, lowercase, number, and special character.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" name="reset_password" class="btn btn-secondary">Reset Password</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  // Segmented views
  const secEmp = document.getElementById('sectionEmployees');
  const secAdm = document.getElementById('sectionAdmins');
  const secSup = document.getElementById('sectionSupers');
  const btnEmp = document.getElementById('btnViewEmployees');
  const btnAdm = document.getElementById('btnViewAdmins');
  const btnSup = document.getElementById('btnViewSupers');

  function showOnly(section){
    if (secEmp) secEmp.classList.toggle('d-none', section !== 'emp');
    if (secAdm) secAdm.classList.toggle('d-none', section !== 'adm');
    if (secSup) secSup.classList.toggle('d-none', section !== 'sup');

    if (btnEmp) btnEmp.classList.toggle('active', section === 'emp');
    if (btnAdm) btnAdm.classList.toggle('active', section === 'adm');
    if (btnSup) btnSup.classList.toggle('active', section === 'sup');
  }
  btnEmp?.addEventListener('click', ()=>showOnly('emp'));
  btnAdm?.addEventListener('click', ()=>showOnly('adm'));
  btnSup?.addEventListener('click', ()=>showOnly('sup'));

  // Email typo hint (client)
  const email = document.getElementById('emailInput');
  const hint  = document.getElementById('emailHint');
  email?.addEventListener('blur', ()=>{
    const v = (email.value||'').trim().toLowerCase();
    if (!v.includes('@')) { hint.textContent=''; return; }
    const domain = v.split('@')[1] || '';
    const typos  = ['gmial.com','gamil.com','gmai.com','gmail.con','gmaill.com'];
    hint.textContent = typos.includes(domain) ? ('Did you mean: '+v.replace(domain,'gmail.com')+' ?') : '';
  });

  // Password strength meter (Add)
  const pwd = document.getElementById('pwdInput');
  const pwd2= document.getElementById('pwdInput2');
  const bar = document.getElementById('pwdBar');
  const matchHint = document.getElementById('pwdMatchHint');
  function score(p){
    let s=0; if(p.length>=10)s+=25; if(/[A-Z]/.test(p))s+=20; if(/[a-z]/.test(p))s+=20; if(/\d/.test(p))s+=20; if(/[\W_]/.test(p))s+=15; if(/(.)\1{3,}/.test(p))s-=20;
    return Math.max(0,Math.min(100,s));
  }
  function updateBar(){
    if(!bar||!pwd)return; const sc=score(pwd.value); bar.style.width=sc+'%'; bar.classList.remove('bg-danger','bg-warning','bg-success'); if(sc<50)bar.classList.add('bg-danger'); else if(sc<80)bar.classList.add('bg-warning'); else bar.classList.add('bg-success');
  }
  pwd?.addEventListener('input', updateBar); pwd?.addEventListener('blur', updateBar);
  pwd2?.addEventListener('input', ()=>{
    if(!pwd2.value){matchHint.textContent='';return;}
    if(pwd.value!==pwd2.value){matchHint.textContent='Passwords do not match.'; matchHint.classList.add('text-danger'); matchHint.classList.remove('text-success');}
    else {matchHint.textContent='Passwords match.'; matchHint.classList.add('text-success'); matchHint.classList.remove('text-danger');}
  });

  function isStrong(p){ return p.length>=10 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /\d/.test(p) && /[\W_]/.test(p) && !/(.)\1{3,}/.test(p); }
  document.getElementById('addAccountForm')?.addEventListener('submit', (e)=>{
    if(!isStrong(pwd.value)){ e.preventDefault(); alert('Use a strong password (min 10 chars; upper, lower, number, special).'); }
    if(pwd.value!==pwd2.value){ e.preventDefault(); alert('Passwords do not match.'); }
    const roleSel = document.getElementById('roleSelect');
    const agencySel = document.getElementById('agencySelect');
    if (roleSel?.value === 'employee') {
      if (!agencySel?.value) { e.preventDefault(); alert('Please choose an agency (CSNK or SMC) for employee.'); }
    }
  });

  // Show/hide Agency when role changes (Add modal)
  const roleSelect = document.getElementById('roleSelect');
  const agencyWrap = document.getElementById('agencyWrapper');
  function refreshAgencyVisibility() {
    if (!roleSelect || !agencyWrap) return;
    agencyWrap.style.display = (roleSelect.value === 'employee') ? 'block' : 'none';
  }
  roleSelect?.addEventListener('change', refreshAgencyVisibility);
  refreshAgencyVisibility();

  // Fill Reset modal
  const resetModal = document.getElementById('resetPasswordModal');
  resetModal?.addEventListener('show.bs.modal', function(ev){
    const btn = ev.relatedTarget;
    document.getElementById('resetUserId').value = btn?.getAttribute('data-user-id') || '';
    document.getElementById('resetUserName').textContent = btn?.getAttribute('data-user-name') || '—';
  });

  // Fill Edit modal
  const editModal = document.getElementById('editAccountModal');
  editModal?.addEventListener('show.bs.modal', function(ev){
    const btn = ev.relatedTarget;
    document.getElementById('editUserId').value   = btn?.getAttribute('data-user-id') || '';
    document.getElementById('editUsername').value = btn?.getAttribute('data-username') || '';
    document.getElementById('editFullName').value = btn?.getAttribute('data-fullname') || '';
    document.getElementById('editEmail').value    = btn?.getAttribute('data-email') || '';
    document.getElementById('editStatus').value   = (btn?.getAttribute('data-status') || 'active') === 'inactive' ? 'inactive' : 'active';
    document.getElementById('editRoleHidden').value = btn?.getAttribute('data-role') || '';

    // Show/hide Agency field only for employee target
    const role = btn?.getAttribute('data-role') || '';
    const wrap = document.getElementById('editAgencyWrapper');
    const agSel= document.getElementById('editAgency');
    if (role === 'employee') {
      wrap?.classList.remove('d-none');
      const ag = btn?.getAttribute('data-agency') || '';
      if (agSel) agSel.value = (ag === 'smc' ? 'smc' : (ag === 'csnk' ? 'csnk' : ''));
    } else {
      wrap?.classList.add('d-none');
      if (agSel) agSel.value = '';
    }
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>