<?php
$pageTitle = 'Accounts Management';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);
$errors = [];
$success = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    if ($id === $_SESSION['admin_id']) {
        setFlashMessage('error', 'You cannot delete your own account.');
    } else {
        if ($admin->delete($id)) {
            $auth->logActivity($_SESSION['admin_id'], 'Delete Account', "Deleted admin account ID: $id");
            setFlashMessage('success', 'Account deleted successfully.');
        } else {
            setFlashMessage('error', 'Failed to delete account.');
        }
    }

    redirect('accounts.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_account'])) {
    $username = sanitizeInput($_POST['username'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $fullName = sanitizeInput($_POST['full_name'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    $role = sanitizeInput($_POST['role'] ?? 'employee');
    $status = sanitizeInput($_POST['status'] ?? 'active');

    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($email)) $errors[] = 'Email is required.';
    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if ($admin->usernameExists($username)) {
        $errors[] = 'Username already exists.';
    }

    if ($admin->emailExists($email)) {
        $errors[] = 'Email already exists.';
    }

    if (empty($errors)) {
        $data = [
            'username' => $username,
            'email' => $email,
            'full_name' => $fullName,
            'password' => $password,
            'role' => $role,
            'status' => $status
        ];

        if ($admin->create($data)) {
            $auth->logActivity($_SESSION['admin_id'], 'Create Account', "Created new admin account: $username");
            setFlashMessage('success', 'Account created successfully!');
            redirect('accounts.php');
        } else {
            $errors[] = 'Failed to create account.';
        }
    }
}

$accounts = $admin->getAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">Accounts Management</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAccountModal">
        <i class="bi bi-plus-circle me-2"></i>Add New Account
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $account): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($account['username']); ?></strong>
                                <?php if ($account['id'] === $_SESSION['admin_id']): ?>
                                    <span class="badge bg-info ms-2">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($account['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($account['email']); ?></td>
                            <td>
                                <?php
                                $roleColors = [
                                    'super_admin' => 'danger',
                                    'admin' => 'warning',
                                    'employee' => 'secondary'
                                ];
                                $badgeColor = $roleColors[$account['role']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?php echo $badgeColor; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $account['role'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $account['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                    <?php echo ucfirst($account['status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($account['created_at']); ?></td>
                            <td>
                                <?php if ($account['id'] !== $_SESSION['admin_id']): ?>
                                    <a href="accounts.php?action=delete&id=<?php echo $account['id']; ?>" class="btn btn-sm btn-danger delete-btn">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="addAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Account</h5>
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
                        <input type="email" class="form-control" name="email" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role">
                            <option value="employee">Employee</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_account" class="btn btn-primary">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
