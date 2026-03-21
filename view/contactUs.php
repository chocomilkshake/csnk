<?php
// contactUs.php
declare(strict_types=1);
use Psr\SimpleCache\InvalidArgumentException;
session_start();

/**
 * CSNK Contact Form - resilient sender for shared hosting
 * - Tries Gmail SMTP (587 STARTTLS → 465 SMTPS)
 * - If blocked, falls back to local sendmail/Exim via PHPMailer::isMail()
 * - If PHPMailer missing, falls back to PHP mail() (plain-text)
 * - Never simulates success; UI only shows toast on real send
 */

// -------------------- Load PHPMailer (manual first, then Composer) --------------------
$phpmailerLoaded = false;

// 1) Manual PHPMailer (no Composer) — place files here if possible
$phpmailerBase = __DIR__ . '/../lib/phpmailer/src';
if (is_readable($phpmailerBase . '/PHPMailer.php')) {
  require_once $phpmailerBase . '/Exception.php';
  require_once $phpmailerBase . '/PHPMailer.php';
  require_once $phpmailerBase . '/SMTP.php';
  $phpmailerLoaded = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
}

// 2) Composer autoload (if present & readable). We don't gate on PHP version anymore.
if (!$phpmailerLoaded) {
  $composerAutoload = __DIR__ . '/../vendor/autoload.php';
  if (is_readable($composerAutoload)) {
    require_once $composerAutoload;
    $phpmailerLoaded = class_exists(\PHPMailer\PHPMailer\PHPMailer::class);
  }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// -------------------- Config --------------------
$CONFIG = [
  // ---- CHANGE THESE ----
  'to_email'    => 'csnkmanila@gmail.com',
  'to_name'     => 'CSNK Support',
  'from_email'  => 'csnkmanila@gmail.com',  // used only when sending via Gmail SMTP
  'from_name'   => 'CSNK Manpower Agency',
  'subject'     => 'CSNK Contact Form Submission',
  // ----------------------
  'max_message' => 500,

  // Gmail SMTP (primary route)
  'smtp_host'   => 'smtp.gmail.com',
  'smtp_user'   => 'csnkmanila@gmail.com',
  'smtp_pass'   => getenv('SMTP_APP_PASS') ?: 'svmw uiwi vjvt hteu', // App password (spaces removed at use)
  'smtp_debug'  => 0,     // set 2 for verbose debug to error_log
];

// Allowed topics (also used in <select>)
$ALLOWED_TOPICS = ['General Inquiry', 'Support', 'Sales', 'Partnerships'];

// -------------------- CSRF token --------------------
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

// -------------------- Helpers --------------------
function clean(string $v): string {
  // Normalize and remove dangerous content
  $v = trim($v);
  $v = str_replace("\0", "", $v);             // null-byte removal
  $v = str_replace(["\r\n", "\r"], "\n", $v);
  $v = strip_tags($v);                             // strip HTML/script tags
  $v = preg_replace('/[\x00-\x1F\x7F]/u', '', $v); // control characters
  return $v;
}
function h($s): string {
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function old(string $key, string $default = ''): string {
  return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
function invalidClass(array $errors, string $key): string {
  return isset($errors[$key]) ? 'is-invalid' : '';
}

/**
 * Derive a domain-based fallback From address for local sending (no Gmail)
 * e.g., https://www.example.com -> no-reply@example.com
 */
function fallbackFromForLocal(): string {
  $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $host = strtolower(preg_replace('/:\d+$/', '', $host)); // strip port
  $host = preg_replace('/^www\./', '', $host);
  if (!filter_var('no-reply@' . $host, FILTER_VALIDATE_EMAIL)) {
    return 'no-reply@localhost.localdomain';
  }
  return 'no-reply@' . $host;
}

/**
 * Pick first readable file path from candidates
 */
function pickFirstReadable(array $paths): ?string {
  foreach ($paths as $p) { if (is_readable($p)) return $p; }
  return null;
}

/**
 * Build HTML and plain text bodies
 */
function buildBodies(array $data, int $year): array {
  $senderName  = trim(($data['firstName'] ?? '') . ' ' . ($data['lastName'] ?? ''));
  $senderEmail = $data['email'] ?? '';

        'message'   => $message,
      ], $phpmailerLoaded);

      if ($ok) {
        $success = true;
        $_SESSION['contact_last_submit'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate token
        $_POST = []; // clear form
      } else {
        $errors['general'] = $err ?: 'We could not send your message right now. Please try again later.';
      }
    }
  }
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CSNK Manpower Agency</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="icon" type="image/png" href="/csnk/resources/img/csnk-icon.png">

  <style>
    :root { --accent-red:#D72638; --ink:#111111; --muted-ink:#6c757d; --bg:#ffffff; --border:#e9ecef; --ring:rgba(215,38,56,.25); }
    body{ background:var(--bg); color:var(--ink); font-feature-settings:"kern" 1, "liga" 1; }
    .contact-card{ border:1px solid var(--border); border-radius:14px; box-shadow:0 6px 28px rgba(17,17,17,.04); background:#fff; }
    .form-control,.form-select{ border-radius:10px; border-color:var(--border); }
    .form-control:focus,.form-select:focus{ border-color:var(--accent-red); box-shadow:0 0 0 .25rem var(--ring); }
    .form-check-input:checked{ background-color:var(--accent-red); border-color:var(--accent-red); }
    .btn-accent{ --bs-btn-color:#fff; --bs-btn-bg:var(--accent-red); --bs-btn-border-color:var(--accent-red); --bs-btn-hover-bg:#b81f2f; --bs-btn-hover-border-color:#b81f2f; --bs-btn-focus-shadow-rgb:215,38,56; }
    .text-accent{ color:var(--accent-red)!important; }
    .divider{ height:1px; background:var(--border); }
    .char-counter{ font-size:.85rem; color:var(--muted-ink); }
    .char-counter.warning{ color:var(--accent-red); font-weight:600; }
    .is-invalid~.invalid-feedback{ display:block; }
  </style>
</head>

<body>
  <!-- Header -->
  <header>
    <?php $page = 'contact'; include __DIR__ . '/navbar.php'; ?>
  </header>

  <!-- Hero -->
  <section class="container py-5 py-md-6">
    <div class="row align-items-center g-4">
      <div class="col-lg-6">
        <h1 class="display-6 fw-bold mb-3">Contact <span class="text-accent">CSNK </span>Support</h1>
        <p class="lead text-secondary mb-4">
          We’re here to help. Send us a message and we’ll get back to you shortly.
        </p>

        <div class="d-flex align-items-center gap-3 mb-2">
          <span class="text-accent">●</span>
          <span class="text-secondary">Average response time: within 24 hours</span>
        </div>
        <div class="d-flex align-items-center gap-3">
          <span class="text-accent">●</span>
          <span class="text-secondary">Support hours: Mon–Fri, 8:00 AM – 5:00 PM</span>
        </div>

        <?php if (!empty($errors['general'])): ?>
          <div class="alert alert-danger mt-4" role="alert">
            <?= htmlspecialchars($errors['general'], ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="col-lg-6">
        <div class="contact-card p-4 p-md-5">
          <form id="contactForm" method="post" action="" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <!-- First name -->
                  <input type="text" id="firstName" name="firstName"
                    class="form-control <?= invalidClass($errors, 'firstName') ?>" placeholder="First name" required
                    maxlength="80" autocomplete="given-name" value="<?= old('firstName') ?>" />
                  <label for="firstName">First name</label>
                  <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['firstName'] ?? 'Please enter your first name.', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              </div>

    const submitLoading = submitBtn?.querySelector('.submit-loading');
    form?.addEventListener('submit', () => {
      submitText?.classList.add('d-none');
      submitLoading?.classList.remove('d-none');
      submitBtn.disabled = true;
    });
  </script>

  <?php if ($success): ?>
    <script>
      // Show success toast on real success only
      (function () {
        const toastEl = document.getElementById('successToast');
        if (toastEl) { new bootstrap.Toast(toastEl).show(); }
      })();
    </script>
  <?php endif; ?>
</body>
</html>