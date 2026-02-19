<?php
require_once '../includes/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$database = new Database();
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

// Brute-force throttle
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];
}
$windowSeconds = 300; // 5 minutes
$maxAttempts   = 5;

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
        $username = trim((string)($_POST['username'] ?? ''));
        if (strlen($username) > 64) { $username = substr($username, 0, 64); }
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $error = 'Please fill in all fields.';
        } else {
            if ($_SESSION['login_attempts']['count'] >= $maxAttempts) {
                $error = 'Too many failed attempts. Please wait a few minutes and try again.';
            } else if ($auth->login($username, $password)) {
                $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
                $_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
                $_SESSION['csrf_login'] = bin2hex(random_bytes(32));
                $_SESSION['login_attempts']['count']++;
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
</head>
<body class="bg-body-secondary">

  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">

        <div class="card shadow border-0 rounded-4">
          <div class="card-body p-4 p-md-5">

            <!-- Header / Branding -->
            <div class="mb-3">
              <div class="d-flex justify-content-between align-items-center flex-nowrap gap-3 overflow-auto mb-2">
                <!-- NEVER STACK: kept in one row; scrolls horizontally on very small screens -->
                <img
                  src="../resources/img/csnklogo.png"
                  alt="CSNK Logo"
                  class="img-fluid"
                  style="max-height:64px"
                >
                <img
                  src="../../resources/img/smcbrandname.png"
                  alt="SMC Brand Name"
                  class="img-fluid"
                  style="max-height:64px"
                >
              </div>
              <div class="text-center">
                <h5 class="mb-0 fw-semibold">Admin System</h5>
                <small class="text-secondary">Secure Login Portal</small>
              </div>
            </div>

            <!-- Theme Toggle -->
            <div class="d-flex justify-content-end mb-3">
              <button id="themeToggle" type="button" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-sun-fill me-1" id="sunIcon"></i>
                <i class="bi bi-moon-stars-fill me-1 d-none" id="moonIcon"></i>
                Theme
              </button>
            </div>

            <!-- Error -->
            <?php if (!empty($error)): ?>
              <div class="alert alert-danger d-flex align-items-center" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <div><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
              </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" action="" autocomplete="on" novalidate>
              <input type="hidden" name="csrf_login"
                     value="<?php echo htmlspecialchars($_SESSION['csrf_login'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

              <div class="mb-3">
                <label for="username" class="form-label fw-semibold">Username</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-person"></i></span>
                  <input
                    type="text"
                    class="form-control"
                    id="username"
                    name="username"
                    placeholder="Enter your username"
                    required
                    autofocus
                    autocomplete="username"
                    inputmode="text">
                </div>
              </div>

              <div class="mb-4">
                <label for="password" class="form-label fw-semibold">Password</label>
                <div class="input-group">
                  <span class="input-group-text"><i class="bi bi-lock"></i></span>
                  <input
                    type="password"
                    class="form-control"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    autocomplete="current-password">
                  <button class="btn btn-outline-secondary" type="button" id="togglePassword" aria-label="Show or hide password">
                    <i class="bi bi-eye" id="eyeOpen"></i>
                    <i class="bi bi-eye-slash d-none" id="eyeClosed"></i>
                  </button>
                </div>
              </div>

              <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
              </button>
            </form>

            <div class="text-center mt-4">
              <small class="text-secondary">By: <strong>IT</strong>-<strong>Interns</strong></small>
            </div>

          </div>
        </div>

        <div class="text-center mt-3">
          <small class="text-secondary">© <?php echo date('Y'); ?> <?php echo APP_NAME; ?></small>
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
      if (theme === 'dark') {
        sun.classList.add('d-none'); moon.classList.remove('d-none');
      } else {
        sun.classList.remove('d-none'); moon.classList.add('d-none');
      }
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