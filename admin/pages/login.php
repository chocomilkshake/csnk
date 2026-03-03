<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$database = new Database();
/** @var mysqli $mysqli */
$mysqli = $database->getConnection(); // <-- ensure Database::getConnection() returns mysqli
$auth = new Auth($database);

// Ensure session + CSRF token
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
if (empty($_SESSION['csrf_login'])) {
  $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
}

// If already logged in
if ($auth->isLoggedIn()) {
  header('Location: dashboard.php');
  exit();
}

$error = '';

// Throttle (5 mins window / 5 attempts)
if (!isset($_SESSION['login_attempts'])) {
  $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];
}
$windowSeconds = 300;
$maxAttempts = 5;

if (time() - $_SESSION['login_attempts']['first_attempt'] > $windowSeconds) {
  $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];
}
if ($_SESSION['login_attempts']['count'] >= $maxAttempts) {
  $error = 'Too many failed attempts. Please wait a few minutes and try again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $csrf = $_POST['csrf_login'] ?? '';
  $tokenValid = hash_equals($_SESSION['csrf_login'] ?? '', $csrf);

  if (!$tokenValid) {
    $error = 'Security verification failed.';
    $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
  } else {
    $username = trim((string) ($_POST['username'] ?? ''));
    if (strlen($username) > 64) {
      $username = substr($username, 0, 64);
    }
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
      $error = 'Please fill in all fields.';
    } else {
      if ($_SESSION['login_attempts']['count'] >= $maxAttempts) {
        $error = 'Too many failed attempts. Please wait a few minutes and try again.';
      } else {
        /**
         * Auth::login() now returns user array on success with consistent session keys.
         * Session variables are already set in Auth::login().
         */
        $user = $auth->login($username, $password);

        if (is_array($user)) {
          // Session variables are already set by Auth::login()
          // Just ensure current_bu_id is set (it may have been resolved to a default)

          // If current_bu_id is 0 or not set, use the resolved value from session
          if (empty($_SESSION['current_bu_id']) || $_SESSION['current_bu_id'] === 0) {
            // Get the resolved BU from the user's business_unit_id or default
            $_SESSION['current_bu_id'] = 1; // Default to CSNK-PH (id=1)
          }

          // (Optional) Load allowed BU IDs for super_admin switcher (only if not already set by Auth)
          if (!isset($_SESSION['allowed_bu_ids']) && $_SESSION['role'] === 'super_admin') {
            $_SESSION['allowed_bu_ids'] = [];
            $sql = "SELECT business_unit_id FROM admin_user_business_units WHERE admin_user_id = ?";
            if ($stmt = mysqli_prepare($mysqli, $sql)) {
              mysqli_stmt_bind_param($stmt, 'i', $_SESSION['user_id']);
              mysqli_stmt_execute($stmt);
              $res = mysqli_stmt_get_result($stmt);
              while ($r = mysqli_fetch_assoc($res)) {
                $_SESSION['allowed_bu_ids'][] = (int) $r['business_unit_id'];
              }
              mysqli_stmt_close($stmt);
            }
            if (empty($_SESSION['allowed_bu_ids']) && $_SESSION['current_bu_id']) {
              $_SESSION['allowed_bu_ids'][] = (int) $_SESSION['current_bu_id'];
            }
          }

          // Session log is already recorded in Auth::login(), no need to duplicate

          // Reset CSRF & throttling and redirect
          $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
          $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];

          // Redirect to main dashboard - SMC employees will see SMC section in sidebar
          header('Location: dashboard.php');
          exit();
        } else {
          // Failed login
          $error = 'Invalid username or password.';
          $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
          $_SESSION['login_attempts']['count']++;
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
  <link rel="icon" href="/csnk/resources/img/csnk-iconz.ico">

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

            <!-- Login form -->
            <form method="POST" action="" autocomplete="on" novalidate>
              <input type="hidden" name="csrf_login"
                value="<?php echo htmlspecialchars($_SESSION['csrf_login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

              <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group input-group-lg">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input type="text" id="username" name="username" class="form-control"
                    placeholder="Enter your username" required autofocus autocomplete="username">
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

    document.getElementById('themeToggle').addEventListener('click', function () {
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

    togglePwdBtn.addEventListener('click', () => {
      const isPassword = pwdInput.type === 'password';
      pwdInput.type = isPassword ? 'text' : 'password';
      eyeOpen.classList.toggle('d-none', isPassword);
      eyeClosed.classList.toggle('d-none', !isPassword);
    });
  </script>
</body>

</html>