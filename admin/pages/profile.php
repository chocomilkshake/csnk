<?php
$pageTitle = 'My Profile';
require_once '../includes/header.php';
require_once '../includes/Admin.php';

$admin = new Admin($database);
$errors = [];
$currentUserData = $admin->getById((int)$_SESSION['admin_id']);

function validateStrongPassword(string $pwd): ?string {
    if (mb_strlen($pwd) < 10) return 'New password must be at least 10 characters.';
    if (!preg_match('/[A-Z]/', $pwd)) return 'New password must include at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $pwd)) return 'New password must include at least one lowercase letter.';
    if (!preg_match('/\d/', $pwd)) return 'New password must include at least one number.';
    if (!preg_match('/[\W_]/', $pwd)) return 'New password must include at least one special character.';
    if (preg_match('/(.)\1{3,}/', $pwd)) return 'New password must not contain repeated characters (e.g., "aaaa").';
    return null;
}

$isSuperAdmin = isset($currentUserData['role']) && $currentUserData['role'] === 'super_admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $newUsername = $isSuperAdmin ? trim((string)($_POST['username'] ?? '')) : $currentUserData['username'];
        $email    = sanitizeInput($_POST['email'] ?? '');
        $fullName = sanitizeInput($_POST['full_name'] ?? '');

        if ($email === '')    $errors[] = 'Email is required.';
        if ($fullName === '') $errors[] = 'Full name is required.';

        if ($email !== '' && $admin->emailExists($email, (int)$_SESSION['admin_id'])) {
            $errors[] = 'Email already exists.';
        }

        if ($isSuperAdmin) {
            if ($newUsername === '') $errors[] = 'Username is required.';
            if ($newUsername !== $currentUserData['username'] && $admin->usernameExists($newUsername, (int)$_SESSION['admin_id'])) {
                $errors[] = 'Username already exists.';
            }
        } else {
            $newUsername = $currentUserData['username'];
        }

        $avatarPath = $currentUserData['avatar'];
        if (isset($_FILES['avatar']) && is_uploaded_file($_FILES['avatar']['tmp_name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $newAvatarPath = uploadFile($_FILES['avatar'], 'avatars');
            if ($newAvatarPath) {
                if (!empty($avatarPath)) {
                    deleteFile($avatarPath);
                }
                $avatarPath = $newAvatarPath;
            } else {
                $errors[] = 'Failed to upload avatar.';
            }
        }

        if (empty($errors)) {
            $data = [
                'username'  => $newUsername,
                'email'     => $email,
                'full_name' => $fullName,
                'avatar'    => $avatarPath,
            ];

            if ($admin->updateProfile((int)$_SESSION['admin_id'], $data)) {
                $_SESSION['admin_username'] = $newUsername;
                $_SESSION['admin_name']     = $fullName;
                $_SESSION['admin_avatar']   = $avatarPath;

                $auth->logActivity((int)$_SESSION['admin_id'], 'Update Profile', 'Updated profile information');
                setFlashMessage('success', 'Profile updated successfully!');
                redirect('profile.php');
            } else {
                $errors[] = 'Failed to update profile.';
            }
        }
    }

    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword     = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($currentPassword === '') $errors[] = 'Current password is required.';
        if ($newPassword === '')     $errors[] = 'New password is required.';
        if ($confirmPassword === '') $errors[] = 'Confirm password is required.';
        if ($currentPassword !== '' && !password_verify($currentPassword, $currentUserData['password'])) {
            $errors[] = 'Current password is incorrect.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        }
        if ($newPassword !== '') {
            $pwdErr = validateStrongPassword($newPassword);
            if ($pwdErr) $errors[] = $pwdErr;
        }

        if (empty($errors)) {
            if ($admin->updatePassword((int)$_SESSION['admin_id'], $newPassword)) {
                $auth->logActivity((int)$_SESSION['admin_id'], 'Change Password', 'Changed account password');
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

<div class="row g-4">
  <div class="col-12 col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center p-4">
        <?php
          $avatarUrl = $currentUserData['avatar'] ? getFileUrl($currentUserData['avatar']) : null;
          $roleColors = ['super_admin'=>'danger','admin'=>'warning','employee'=>'secondary'];
          $badgeColor = $roleColors[$currentUserData['role']] ?? 'secondary';
        ?>
        <div class="position-relative d-inline-block mb-3">
          <div class="rounded-circle overflow-hidden border" style="width:140px;height:140px;border-color:#f1f1f1;background:#f7f7f7;">
            <?php if ($avatarUrl): ?>
              <img src="<?php echo $avatarUrl; ?>" alt="Avatar" style="width:100%;height:100%;object-fit:cover;display:block;">
            <?php else: ?>
              <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light" style="font-size:3rem;color:#991b1b;">
                <?php echo strtoupper(substr($currentUserData['full_name'], 0, 1)); ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($currentUserData['full_name']); ?></h5>
        <div class="text-muted mb-2">@<?php echo htmlspecialchars($currentUserData['username']); ?></div>
        <span class="badge bg-<?php echo $badgeColor; ?> rounded-pill mb-3">
          <?php echo ucfirst(str_replace('_',' ',$currentUserData['role'])); ?>
        </span>

        <div class="text-start mt-3 small">
          <div class="mb-2">
            <div class="text-muted">Email</div>
            <div class="fw-semibold"><?php echo htmlspecialchars($currentUserData['email']); ?></div>
          </div>
          <div class="mb-2">
            <div class="text-muted">Member Since</div>
            <div class="fw-semibold"><?php echo formatDate($currentUserData['created_at']); ?></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-8">
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-semibold">Update Profile</h5>
      </div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data" id="profileForm">
          <div class="mb-3">
            <label class="form-label fw-semibold">Profile Image</label>
            <div id="avatarDropzone" class="dropzone rounded-3 border border-2 d-flex align-items-center justify-content-center p-3" style="background:#fafafa; border-style:dashed; cursor:pointer;" aria-label="Drag & drop your image here or click to choose">
              <div class="text-center">
                <div class="mx-auto rounded-circle overflow-hidden mb-2" style="width:96px;height:96px;background:#f3f4f6;border:1px solid #eee;">
                  <img id="avatarPreview" src="<?php echo $avatarUrl ?: ''; ?>" alt="Preview" style="width:100%;height:100%;object-fit:cover;<?php echo $avatarUrl?'':'display:none;'; ?>">
                </div>
                <div class="small text-muted">
                  <i class="bi bi-cloud-arrow-up me-1"></i>
                  Drag &amp; drop an image here, or <u>click to browse</u>.
                </div>
              </div>
            </div>
            <input type="file" name="avatar" id="avatarInput" accept="image/*" class="d-none">
            <small class="text-muted">JPG/PNG recommended. Leave empty to keep current avatar.</small>
          </div>

          <?php if ($isSuperAdmin): ?>
            <div class="mb-3">
              <label class="form-label">Username <span class="text-danger">*</span></label>
              <input type="text" class="form-control" name="username" required value="<?php echo htmlspecialchars($currentUserData['username']); ?>">
            </div>
          <?php else: ?>
            <div class="mb-3">
              <label class="form-label">Username</label>
              <div class="input-group">
                <input type="text" class="form-control readonly-locked px-3 py-2 rounded-start-2" value="<?php echo htmlspecialchars($currentUserData['username']); ?>" readonly tabindex="-1" aria-readonly="true">
                <span class="input-group-text bg-light rounded-end-2" title="Locked">
                  <i class="bi bi-lock"></i>
                </span>
              </div>
              <div class="form-text">Username is fixed and cannot be changed.</div>
            </div>
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label">Full Name <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="full_name" required value="<?php echo htmlspecialchars($currentUserData['full_name']); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($currentUserData['email']); ?>">
          </div>

          <button type="submit" name="update_profile" class="btn btn-primary">
            <i class="bi bi-check-circle me-1"></i> Save Changes
          </button>
        </form>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-0 fw-semibold">Change Password</h5>
      </div>
      <div class="card-body">
        <form method="POST" id="passwordForm" novalidate>
          <div class="mb-3">
            <label class="form-label">Current Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="current_password" required autocomplete="current-password">
          </div>

          <div class="mb-3">
            <label class="form-label">New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="new_password" id="newPwd" required autocomplete="new-password" minlength="10" placeholder="Min 10 chars, uppercase, lowercase, number, special">
            <div class="progress mt-2" style="height:8px;">
              <div id="pwdBar" class="progress-bar" role="progressbar" style="width:0%"></div>
            </div>
            <small class="text-muted">Must be at least 10 characters, and include uppercase, lowercase, number, and special character. Avoid repeating the same character 4+ times.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Confirm New Password <span class="text-danger">*</span></label>
            <input type="password" class="form-control" name="confirm_password" id="newPwd2" required autocomplete="new-password" minlength="10">
            <div class="form-text" id="pwdMatchHint"></div>
          </div>

          <button type="submit" name="change_password" class="btn btn-warning">
            <i class="bi bi-key me-1"></i> Change Password
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
.dropzone.dragover {
  background: #fff;
  border-color: #c40000 !important;
  box-shadow: 0 0 0 4px rgba(196,0,0,.08);
}
.readonly-locked {
  background-color: #f3f4f6 !important;
  color: #6b7280 !important;
  border: 1px solid #e5e7eb !important;
  cursor: not-allowed !important;
  user-select: none;
}
.readonly-locked:focus {
  box-shadow: none !important;
  outline: none !important;
}
@media (forced-colors: active) {
  .readonly-locked {
    background-color: Canvas !important;
    color: GrayText !important;
    border-color: GrayText !important;
  }
}
</style>

<script>
(function(){
  const dz   = document.getElementById('avatarDropzone');
  const file = document.getElementById('avatarInput');
  const img  = document.getElementById('avatarPreview');
  if (!dz || !file) return;
  dz.addEventListener('click', () => file.click());
  ['dragenter', 'dragover'].forEach(evt => {
    dz.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dz.classList.add('dragover'); });
  });
  ['dragleave', 'dragend', 'drop'].forEach(evt => {
    dz.addEventListener(evt, e => { e.preventDefault(); e.stopPropagation(); dz.classList.remove('dragover'); });
  });
  dz.addEventListener('drop', (e) => {
    const f = e.dataTransfer.files && e.dataTransfer.files[0];
    if (!f) return;
    if (!f.type.startsWith('image/')) { alert('Please drop an image file.'); return; }
    assignFileToInput(f);
  });
  file.addEventListener('change', () => {
    const f = file.files && file.files[0];
    if (!f) return;
    if (!f.type.startsWith('image/')) { alert('Please select an image file.'); file.value = ''; return; }
    preview(f);
  });
  function assignFileToInput(f){
    const dt = new DataTransfer();
    dt.items.add(f);
    file.files = dt.files;
    preview(f);
  }
  function preview(f){
    const reader = new FileReader();
    reader.onload = () => { img.src = reader.result; img.style.display = 'block'; };
    reader.readAsDataURL(f);
  }
})();
(function(){
  const pwd  = document.getElementById('newPwd');
  const pwd2 = document.getElementById('newPwd2');
  const bar  = document.getElementById('pwdBar');
  const hint = document.getElementById('pwdMatchHint');
  if (!pwd || !pwd2 || !bar) return;
  function scorePassword(p){
    let s = 0;
    if (p.length >= 10) s += 25;
    if (/[A-Z]/.test(p)) s += 20;
    if (/[a-z]/.test(p)) s += 20;
    if (/\d/.test(p))    s += 20;
    if (/[\W_]/.test(p)) s += 15;
    if (/(.)\1{3,}/.test(p)) s -= 20;
    return Math.max(0, Math.min(100, s));
  }
  function updateStrength(){
    const sc = scorePassword(pwd.value);
    bar.style.width = sc + '%';
    bar.classList.remove('bg-danger','bg-warning','bg-success');
    if (sc < 50) bar.classList.add('bg-danger');
    else if (sc < 80) bar.classList.add('bg-warning');
    else bar.classList.add('bg-success');
  }
  function updateMatch(){
    if (!pwd2.value) { hint.textContent=''; hint.classList.remove('text-danger','text-success'); return; }
    if (pwd.value !== pwd2.value) { hint.textContent = 'Passwords do not match.'; hint.classList.add('text-danger'); hint.classList.remove('text-success'); }
    else { hint.textContent = 'Passwords match.'; hint.classList.add('text-success'); hint.classList.remove('text-danger'); }
  }
  pwd.addEventListener('input', updateStrength);
  pwd.addEventListener('blur', updateStrength);
  pwd2.addEventListener('input', updateMatch);
  document.getElementById('passwordForm')?.addEventListener('submit', (e)=>{
    const p = pwd.value, c = pwd2.value;
    const strong = p.length>=10 && /[A-Z]/.test(p) && /[a-z]/.test(p) && /\d/.test(p) && /[\W_]/.test(p) && !/(.)\1{3,}/.test(p);
    if (!strong) { e.preventDefault(); alert('Please use a strong password: min 10 chars with uppercase, lowercase, number, and special character (no 4+ repeats).'); return; }
    if (p !== c) { e.preventDefault(); alert('New passwords do not match.'); }
  });
})();
</script>

<?php require_once '../includes/footer.php'; ?>