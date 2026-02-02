<?php
$pageTitle = 'My Profile';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);
$errors = [];

$currentUserData = $admin->getById($_SESSION['admin_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $fullName = sanitizeInput($_POST['full_name'] ?? '');

        if (empty($username)) $errors[] = 'Username is required.';
        if (empty($email)) $errors[] = 'Email is required.';
        if (empty($fullName)) $errors[] = 'Full name is required.';

        if ($admin->usernameExists($username, $_SESSION['admin_id'])) {
            $errors[] = 'Username already exists.';
        }

        if ($admin->emailExists($email, $_SESSION['admin_id'])) {
            $errors[] = 'Email already exists.';
        }

        $avatarPath = $currentUserData['avatar'];
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $newAvatarPath = uploadFile($_FILES['avatar'], 'avatars');
            if ($newAvatarPath) {
                if ($avatarPath) {
                    deleteFile($avatarPath);
                }
                $avatarPath = $newAvatarPath;
            }
        }

        if (empty($errors)) {
            $data = [
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'avatar' => $avatarPath
            ];

            if ($admin->updateProfile($_SESSION['admin_id'], $data)) {
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_name'] = $fullName;
                $_SESSION['admin_avatar'] = $avatarPath;

                $auth->logActivity($_SESSION['admin_id'], 'Update Profile', 'Updated profile information');
                setFlashMessage('success', 'Profile updated successfully!');
                redirect('profile.php');
            } else {
                $errors[] = 'Failed to update profile.';
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($currentPassword)) $errors[] = 'Current password is required.';
        if (empty($newPassword)) $errors[] = 'New password is required.';
        if (empty($confirmPassword)) $errors[] = 'Confirm password is required.';

        if (!empty($currentPassword) && !password_verify($currentPassword, $currentUserData['password'])) {
            $errors[] = 'Current password is incorrect.';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }

        if (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        }

        if (empty($errors)) {
            if ($admin->updatePassword($_SESSION['admin_id'], $newPassword)) {
                $auth->logActivity($_SESSION['admin_id'], 'Change Password', 'Changed account password');
                setFlashMessage('success', 'Password changed successfully!');
                redirect('profile.php');
            } else {
                $errors[] = 'Failed to change password.';
            }
        }
    }
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-semibold">My Profile</h4>
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

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <?php if ($currentUserData['avatar']): ?>
                    <img src="<?php echo getFileUrl($currentUserData['avatar']); ?>" alt="Avatar" class="rounded-circle mb-3" width="150" height="150" style="object-fit: cover;">
                <?php else: ?>
                    <div class="bg-primary text-white rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 150px; height: 150px; font-size: 3rem;">
                        <?php echo strtoupper(substr($currentUserData['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($currentUserData['full_name']); ?></h5>
                <p class="text-muted mb-2">@<?php echo htmlspecialchars($currentUserData['username']); ?></p>

                <?php
                $roleColors = [
                    'super_admin' => 'danger',
                    'admin' => 'warning',
                    'employee' => 'secondary'
                ];
                $badgeColor = $roleColors[$currentUserData['role']] ?? 'secondary';
                ?>
                <span class="badge bg-<?php echo $badgeColor; ?> mb-3">
                    <?php echo ucfirst(str_replace('_', ' ', $currentUserData['role'])); ?>
                </span>

                <div class="text-start mt-4">
                    <div class="mb-2">
                        <small class="text-muted">Email</small>
                        <div class="fw-semibold"><?php echo htmlspecialchars($currentUserData['email']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Member Since</small>
                        <div class="fw-semibold"><?php echo formatDate($currentUserData['created_at']); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Update Profile</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Avatar</label>
                        <input type="file" class="form-control" name="avatar" accept="image/*">
                        <small class="text-muted">Leave empty to keep current avatar</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($currentUserData['username']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($currentUserData['full_name']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($currentUserData['email']); ?>">
                    </div>

                    <button type="submit" name="update_profile" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Update Profile
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-semibold">Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Current Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="confirm_password" required minlength="6">
                    </div>

                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i>Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
