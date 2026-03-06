<?php
// FILE: admin/pages/login.php (hardened)

// --- Bootstrap & config ---
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// --------- Security Headers (do this before any output) ---------
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

// Strong CSP (allows Bootstrap CDN you use)
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm/bootstrap@5.3.2 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm/bootstrap@5.3.2 'unsafe-inline'; img-src 'self' data: https://cdn.jsdelivr.net; font-src 'self' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm; connect-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self'");

// Clickjacking, XSS and MIME protections
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');
header('X-XSS-Protection: 0'); // Deprecated, CSP is primary

// HSTS for HTTPS sites (uncomment when you have HTTPS fully enabled)
// if ($isHttps) {
//     header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
// }

// --------- Session hardening ---------
// Session cookie params are now set in config.php before session_start()
// Just ensure session is started here

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Create CSRF token (login form)
if (empty($_SESSION['csrf_login'])) {
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

// --- Instantiate DB/Auth ---
$database = new Database();
/** @var mysqli $mysqli */
$mysqli = $database->getConnection(); // ensure Database::getConnection returns mysqli
$auth   = new Auth($database);

// If already logged in, redirect
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// ---------- Brute-force controls ----------
// 4 attempts limit, 4 minutes lockout
$maxAttempts = 4;
$lockoutSeconds = 240; // 4 minutes = 240 seconds
$windowSeconds = 300;  // 5 minutes for IP+username tracking

// Get client's IP for tracking
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// IP+username based tracker functions (for additional tracking)
function bf_key(string $username): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $u  = strtolower(trim($username));
    return sha1($ip . '|' . $u);
}
function bf_file(string $key): string {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'csnk_login_' . $key . '.json';
}
function bf_load(string $key): array {
    $f = bf_file($key);
    if (is_file($f) && is_readable($f)) {
        $json = file_get_contents($f);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return ['count' => 0, 'first' => time()];
}
function bf_save(string $key, array $data): void {
    $f = bf_file($key);
    @file_put_contents($f, json_encode($data), LOCK_EX);
}
function bf_reset(string $key): void {
    $f = bf_file($key);
    if (is_file($f)) @unlink($f);
}

// IP-based lockout tracking using file
function getLockoutFile(string $ip): string {
    return sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'csnk_lockout_' . md5($ip) . '.json';
}

function getLockoutData(string $ip): array {
    $file = getLockoutFile($ip);
    if (is_file($file) && is_readable($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return ['locked' => false, 'locked_at' => 0, 'attempts' => 0];
}

function saveLockoutData(string $ip, array $data): void {
    $file = getLockoutFile($ip);
    @file_put_contents($file, json_encode($data), LOCK_EX);
}

function clearLockout(string $ip): void {
    $file = getLockoutFile($ip);
    if (is_file($file)) @unlink($file);
}

// Load current lockout status
$lockoutData = getLockoutData($clientIp);
$now = time();

// Check if currently locked
$isLocked = false;
$remainingTime = 0;

if ($lockoutData['locked'] && $lockoutData['locked_at'] > 0) {
    $elapsed = $now - $lockoutData['locked_at'];
    if ($elapsed < $lockoutSeconds) {
        $isLocked = true;
        $remainingTime = $lockoutSeconds - $elapsed;
    } else {
        // Lockout expired, reset
        clearLockout($clientIp);
        $lockoutData = ['locked' => false, 'locked_at' => 0, 'attempts' => 0];
    }
}

$error = '';
$attemptsRemaining = $maxAttempts - ($lockoutData['attempts'] ?? 0);

// If locked, show countdown
if ($isLocked) {
    $error = 'Account temporarily locked due to too many failed attempts.';
}

// Optional: require HTTPS for login POST (uncomment when site is HTTPS-only)
// if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isHttps) {
//     $error = 'Secure connection required. Please use HTTPS.';
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === '') {
    // CSRF check
    $csrf = $_POST['csrf_login'] ?? '';
    $tokenValid = hash_equals($_SESSION['csrf_login'] ?? '', $csrf);

    if (!$tokenValid) {
        $error = 'Security verification failed.';
        $_SESSION['csrf_login'] = bin2hex(random_bytes(32)); // rotate
    } else {
        // Normalize inputs
        $username = trim((string)($_POST['username'] ?? ''));
        // normalize whitespace and case-insensitive compare basis
        $username = preg_replace('/\s+/u', ' ', $username ?? '');
        if (mb_strlen($username, 'UTF-8') > 64) {
            $username = mb_substr($username, 0, 64, 'UTF-8');
        }
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please fill in all fields.';
        } else {
            // File-based brute-force tracker by IP+username
            $key   = bf_key($username);
            $now   = time();
            $track = bf_load($key);

            // Reset IP+username window after 5 minutes
            if ($now - ($track['first'] ?? $now) > $windowSeconds) {
                $track = ['count' => 0, 'first' => $now];
            }

            // Exponential backoff: add small delay based on track['count']
            if (($track['count'] ?? 0) >= 3) {
                // delay up to 2s for high counts (tunable)
                $delayMs = min(2000, (($track['count'] - 2) * 250));
                usleep($delayMs * 1000);
            }

            // Gate on both IP-based lockout
            if ($isLocked) {
                $error = 'Account temporarily locked due to too many failed attempts.';
            } else {
                // Attempt login (Auth::login should use prepared statements & password_verify)
                $user = $auth->login($username, $password);

                // Destroy password quickly (best effort)
                $password = str_repeat("\0", strlen($password));

                if (is_array($user)) {
                    // Success → regenerate session id to prevent fixation
                    session_regenerate_id(true);

                    // Ensure current_bu_id is set (default to CSNK-PH id=1 if not set)
                    if (empty($_SESSION['current_bu_id']) || $_SESSION['current_bu_id'] === 0) {
                        $_SESSION['current_bu_id'] = 1;
                    }

                    // For super_admins, load allowed BU ids if not set
                    if (!isset($_SESSION['allowed_bu_ids']) && ($_SESSION['role'] ?? '') === 'super_admin') {
                        $_SESSION['allowed_bu_ids'] = [];
                        $sql = "SELECT business_unit_id FROM admin_user_business_units WHERE admin_user_id = ?";
                        if ($stmt = mysqli_prepare($mysqli, $sql)) {
                            mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
                            mysqli_stmt_execute($stmt);
                            $res = mysqli_stmt_get_result($stmt);
                            while ($r = mysqli_fetch_assoc($res)) {
                                $_SESSION['allowed_bu_ids'][] = (int)$r['business_unit_id'];
                            }
                            mysqli_stmt_close($stmt);
                        }
                        if (empty($_SESSION['allowed_bu_ids']) && !empty($_SESSION['current_bu_id'])) {
                            $_SESSION['allowed_bu_ids'][] = (int)$_SESSION['current_bu_id'];
                        }
                    }

                    // Reset CSRF and lockout on success
                    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
                    clearLockout($clientIp);

                    // Optional bot-friction switch (for future CAPTCHA toggle)
                    $_SESSION['human_verified'] = true;

                    // Redirect based on agency
                    $userAgency = strtolower((string)($_SESSION['agency'] ?? ''));
                    if ($userAgency === 'smc') {
                        header('Location: turkey_dashboard.php');
                    } else {
                        header('Location: dashboard.php');
                    }
                    exit();
                } else {
                    // Failed login - increment attempts and check for lockout
                    $currentAttempts = ($lockoutData['attempts'] ?? 0) + 1;
                    $lockoutData['attempts'] = $currentAttempts;
                    
                    if ($currentAttempts >= $maxAttempts) {
                        // Lock the account
                        $lockoutData['locked'] = true;
                        $lockoutData['locked_at'] = time();
                        saveLockoutData($clientIp, $lockoutData);
                        $error = 'Account locked! Too many failed attempts. Please wait 4 minutes before trying again.';
                        $remainingTime = $lockoutSeconds;
                    } else {
                        $attemptsLeft = $maxAttempts - $currentAttempts;
                        $error = 'Invalid username or password. You have ' . $attemptsLeft . ' attempt(s) remaining.';
                        saveLockoutData($clientIp, $lockoutData);
                    }
                    
                    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - <?php echo APP_NAME; ?></title>

  <!-- Favicons -->
  <link rel="icon" type="image/png" href="/csnk/resources/img/csnk-iconz.png">
  <link rel="apple-touch-icon" href="/csnk/resources/img/favicons/apple-touch-icon-180.png">

  <!-- Bootstrap + Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <meta name="theme-color" content="#0d6efd">
</head>
<body class="bg-body-tertiary">

  <div class="container py-4 py-md-5">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

        <div class="card border-0 shadow-lg rounded-4">
          <div class="card-body p-4 p-md-5">

            <!-- Branding -->
            <div class="mb-3">
              <div class="d-flex align-items-center justify-content-between gap-2 flex-nowrap mb-2">
                <div class="w-50 text-center">
                  <img src="../resources/img/csnklogo.png" alt="CSNK Logo" class="img-fluid">
                </div>
                <div class="text-secondary px-2">|</div>
                <div class="w-50 text-center">
                  <img src="../../resources/img/smcbrandname.png" alt="SMC Brand Name" class="img-fluid">
                </div>
              </div>
              <div class="text-center">
                <h5 class="mb-0 fw-semibold">Admin System</h5>
                <small class="text-secondary">Secure Login Portal</small>
              </div>
            </div>

            <!-- Theme toggle -->
            <div class="d-flex justify-content-end mb-3">
              <button id="themeToggle" type="button" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-sun-fill me-1" id="sunIcon"></i>
                <i class="bi bi-moon-stars-fill me-1 d-none" id="moonIcon"></i>
                Theme
              </button>
            </div>

            <!-- Error alert -->
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            <?php endif; ?>

            <!-- Lockout countdown alert -->
            <?php if ($isLocked && $remainingTime > 0): ?>
              <div class="alert alert-warning d-flex align-items-center" role="alert" id="lockoutAlert">
                <i class="bi bi-clock-fill me-2"></i>
                <div>
                  Too many failed attempts. Please wait <span id="countdown"><?php echo $remainingTime; ?></span> seconds before trying again.
                </div>
              </div>
            <?php endif; ?>

            <!-- Attempts remaining info -->
            <?php if (!$isLocked && isset($lockoutData['attempts']) && $lockoutData['attempts'] > 0): ?>
              <div class="alert alert-info d-flex align-items-center" role="alert">
                <i class="bi bi-info-circle-fill me-2"></i>
                <div>You have <?php echo $attemptsRemaining; ?> attempt(s) remaining.</div>
              </div>
            <?php endif; ?>

            <!-- Login form -->
            <form method="POST" action="" autocomplete="on" novalidate <?php echo $isLocked ? 'id="loginForm"' : ''; ?>>
              <input type="hidden" name="csrf_login"
                value="<?php echo htmlspecialchars($_SESSION['csrf_login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

              <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" id="username" name="username" class="form-control"
                    placeholder="Enter your username" required autofocus autocomplete="username" maxlength="64">
                </div>
              </div>

              <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input type="password" id="password" name="password" class="form-control"
                    placeholder="Enter your password" required autocomplete="current-password">
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword"
                    aria-label="Show or hide password">
                    <i class="bi bi-eye" id="eyeOpen"></i>
                    <i class="bi bi-eye-slash d-none" id="eyeClosed"></i>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-100 fw-semibold rounded-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
              </button>
            </form>

            <div class="text-center mt-4">
              <small class="text-secondary">By: <strong>IT</strong>-<strong>Interns</strong></small>
            </div>

          </div>
        </div>

        <div class="text-center mt-3">
          <small class="text-secondary">© <?php echo date('Y'); ?> IT Department | <strong>Interns</strong></small>
        </div>

      </div>
    </div>
  </div>

  <!-- Bootstrap Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Init theme from localStorage
    (function initTheme() {
      const saved = localStorage.getItem('bs-theme') || 'light';
      document.documentElement.setAttribute('data-bs-theme', saved);
      updateThemeIcons(saved);
    })();

    function updateThemeIcons(theme) {
      const sun = document.getElementById('sunIcon');
      const moon = document.getElementById('moonIcon');
      if (theme === 'dark') { sun.classList.add('d-none'); moon.classList.remove('d-none'); }
      else { sun.classList.remove('d-none'); moon.classList.add('d-none'); }
    }

    document.getElementById('themeToggle')?.addEventListener('click', function () {
      const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
      const next = current === 'light' ? 'dark' : 'light';
      document.documentElement.setAttribute('data-bs-theme', next);
      localStorage.setItem('bs-theme', next);
      updateThemeIcons(next);
    });

    // Show/Hide password
    const pwdInput = document.getElementById('password');
    const togglePwdBtn = document.getElementById('togglePassword');
    const eyeOpen = document.getElementById('eyeOpen');
    const eyeClosed = document.getElementById('eyeClosed');

    togglePwdBtn?.addEventListener('click', () => {
      const isPassword = pwdInput.type === 'password';
      pwdInput.type = isPassword ? 'text' : 'password';
      eyeOpen.classList.toggle('d-none', isPassword);
      eyeClosed.classList.toggle('d-none', !isPassword);
    });

    // Lockout countdown timer
    const countdownEl = document.getElementById('countdown');
    if (countdownEl) {
      let seconds = parseInt(countdownEl.textContent, 10);
      const loginForm = document.getElementById('loginForm');
      const usernameInput = document.getElementById('username');
      const passwordInput = document.getElementById('password');
      const togglePwdBtn2 = document.getElementById('togglePassword');
      const submitBtn = loginForm ? loginForm.querySelector('button[type="submit"]') : null;

      // Disable form inputs when locked
      if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
          if (seconds > 0) {
            e.preventDefault();
            alert('Account is locked. Please wait for the countdown to finish.');
            return false;
          }
        });
      }

      if (usernameInput) usernameInput.disabled = true;
      if (passwordInput) passwordInput.disabled = true;
      if (togglePwdBtn2) togglePwdBtn2.disabled = true;
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-clock me-2"></i>Locked';
      }

      // Countdown timer
      const timer = setInterval(() => {
        seconds--;
        if (countdownEl) {
          countdownEl.textContent = seconds;
        }
        
        if (seconds <= 0) {
          clearInterval(timer);
          // Reload page to reset lockout
          window.location.reload();
        }
      }, 1000);
    }
  </script>
</body>
</html>