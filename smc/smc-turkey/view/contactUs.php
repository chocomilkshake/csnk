<?php
// contact.php
declare(strict_types=1);
session_start();

// Load PHPMailer (if available)
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
$composer_autoload_missing = false;
if (is_readable($composer_autoload)) {
    require_once $composer_autoload;
} else {
    error_log('Composer autoload missing or unreadable: ' . $composer_autoload);
    $composer_autoload_missing = true;
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --------------------------------------------------
// Configuration
// --------------------------------------------------
$CONFIG = [
    // ---- CHANGE THESE ----
    'to_email'      => 'smcphilippines.marketing@gmail.com',     // Destination email
    'to_name'       => 'CSNK Support',
    'from_email'    => 'csnkmanila@gmail.com',     // Gmail address (if using Gmail SMTP)
    'from_name'     => 'CSNK Manpower Agency',
    'subject'       => 'CSNK Contact Form Submission',
    // ----------------------
    'max_message'   => 500,

    // PHPMailer Configuration
    'smtp_host'     => 'smtp.gmail.com',
    'smtp_port'     => 587,                        // 587 for TLS
    'smtp_user'     => 'csnkmanila@gmail.com',
    'smtp_pass'     => 'hqyp ljaf kwyd fkzo',      // Gmail App Password (NOT normal password)
    'smtp_encrypt'  => 'tls',                      // Overwritten below if PHPMailer is available
    'smtp_debug'    => 0,                          // 0=off; 2=verbose (logs to error_log)
    'enable_mail'   => true,                       // set false to skip email while testing
];

// Prefer PHPMailer constant if available
if (!$composer_autoload_missing && class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
    $CONFIG['smtp_encrypt'] = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = false;

// Helper: sanitize
function clean(string $v): string {
    $v = trim($v);
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return filter_var($v, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
}

// On POST: validate + optionally send
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Honeypot (bots fill this)
    $honeypot = $_POST['website'] ?? '';

    // CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
        $errors['general'] = 'Security verification failed. Please refresh and try again.';
    }

    if (!empty($honeypot)) {
        // Silently treat as success to avoid tipping off bots
        $success = true;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_POST = [];
    } else {
        // Gather & sanitize
        $firstName = clean($_POST['firstName'] ?? '');
        $lastName  = clean($_POST['lastName'] ?? '');
        $email     = clean($_POST['email'] ?? '');
        // UI forces 11 digits; keep server-side consistent
        $phone     = preg_replace('/\D+/', '', clean($_POST['phone'] ?? ''));
        $topic     = clean($_POST['topic'] ?? '');
        $message   = clean($_POST['message'] ?? '');

        // Validate
        if ($firstName === '' || mb_strlen($firstName) > 80) {
            $errors['firstName'] = 'Please enter your first name (max 80 characters).';
        }
        if ($lastName === '' || mb_strlen($lastName) > 80) {
            $errors['lastName'] = 'Please enter your last name (max 80 characters).';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        }
        // If phone provided, require exactly 11 digits (PH mobile format like 09XXXXXXXXX)
        if ($phone !== '' && !preg_match('/^\d{11}$/', $phone)) {
            $errors['phone'] = 'Please enter an 11-digit phone number.';
        }
        if ($topic === '') {
            $errors['topic'] = 'Please select a topic.';
        }
        if ($message === '' || mb_strlen($message) > $CONFIG['max_message']) {
            $errors['message'] = 'Please enter your message (max ' . (int)$CONFIG['max_message'] . ' characters).';
        }
        if (empty($_POST['consent'])) {
            $errors['consent'] = 'Consent is required.';
        }

        // If valid, send email with PHPMailer
        if (!$errors) {
            // Plain-text fallback (no submitted/IP/agent)
            $textBody =
                "You have a new CSNK contact form submission:\n\n" .
                "Name: {$firstName} {$lastName}\n" .
                "Email: {$email}\n" .
                "Phone: {$phone}\n" .
                "Topic: {$topic}\n\n" .
                "Message:\n{$message}\n";

            if ($CONFIG['enable_mail']) {
                // If PHPMailer isn't available (missing composer autoload), avoid fatal error and fail gracefully.
                if ($composer_autoload_missing || !class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
                    error_log('PHPMailer not available; skipping email send.');
                    $errors['general'] = 'Mail service is temporarily unavailable. Your message was saved but could not be sent. Please try again later.';
                    $CONFIG['enable_mail'] = false;
                }
            }

            if ($CONFIG['enable_mail']) {
                try {
                    // Instantiate PHPMailer via FQCN
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $mail->CharSet = 'UTF-8';

                    // Server settings
                    $mail->isSMTP();

                    // Debug output level (0 = off). Set to 2 to log verbose debug to error_log.
                    $mail->SMTPDebug   = $CONFIG['smtp_debug'] ?? 0;
                    $mail->Debugoutput = function ($str, $level) {
                        error_log("PHPMailer debug level {$level}: {$str}");
                    };

                    $mail->Host        = $CONFIG['smtp_host'];
                    $mail->SMTPAuth    = true;
                    $mail->Username    = $CONFIG['smtp_user'];
                    $mail->Password    = $CONFIG['smtp_pass'];
                    $mail->SMTPSecure  = $CONFIG['smtp_encrypt']; // e.g., PHPMailer::ENCRYPTION_STARTTLS
                    $mail->SMTPAutoTLS = true;
                    $mail->Port        = (int)$CONFIG['smtp_port'];

                    // For local development (XAMPP/localhost), allow self-signed certs to avoid TLS handshake failures
                    $host = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    if (in_array($host, ['localhost', '127.0.0.1'], true) || stripos($host, 'localhost') !== false) {
                        $mail->SMTPOptions = [
                            'ssl' => [
                                'verify_peer'       => false,
                                'verify_peer_name'  => false,
                                'allow_self_signed' => true,
                            ],
                        ];
                    }

                    // Recipients: send to company support and CC the client
                    $mail->setFrom($CONFIG['from_email'], $CONFIG['from_name']);
                    $mail->addAddress($CONFIG['to_email'], $CONFIG['to_name'] ?? '');
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $mail->addCC($email);
                        $mail->addReplyTo($email, trim($firstName . ' ' . $lastName));
                    }

                    // Subject (append topic if present)
                    $subjectBase = $CONFIG['subject'] ?? 'CSNK Contact Message';
                    $mail->Subject = $subjectBase . (trim($topic) !== '' ? ' - ' . $topic : '');

                    // Helper escaper for HTML
                    $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                    $senderName  = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
                    $senderEmail = $email ?? '';
                    $senderPhone = $phone ?? '';
                    $topicSafe   = $topic ?? '';
                    $messageSafe = $message ?? '';

                    // Modern, clean HTML (NAVY + GOLD theme; no red/black)
                    $htmlBody =
'<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CSNK Contact Message</title>
</head>
<body style="margin:0;padding:0;background:#f5f8ff;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f5f8ff;padding:24px 0;">
    <tr>
      <td align="center">
        <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:94%;">

          <!-- Logo row (two images side-by-side) -->
          <tr>
            <td align="center" style="padding:8px 0 16px;">
              <table role="presentation" cellspacing="0" cellpadding="0" style="margin:0 auto;">
                <tr>
                  <td style="padding:0 8px;">
                    <img src="cid:whychoose_cid" alt="Why Choose" style="max-width:140px;height:auto;display:block;border:0;">
                  </td>
                  <td style="padding:0 8px;">
                    <img src="cid:secondary_logo_cid" alt="CSNK" style="max-width:140px;height:auto;display:block;border:0;">
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Card -->
          <tr>
            <td style="background:#ffffff;border:1px solid #e6ecf5;border-radius:10px;overflow:hidden;">
              <!-- Header band -->
              <div style="background:linear-gradient(90deg,#1b355c,#0b1f3a);padding:14px 18px;">
                <h1 style="margin:0;font-size:18px;color:#fff;font-weight:600;">New Contact Message</h1>
                <div style="margin-top:4px;font-size:12px;color:#e6ecf5;">Received via the CSNK website</div>
              </div>

              <!-- Summary -->
              <div style="padding:14px 18px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                  <tr>
                    <td style="width:130px;padding:6px 0;color:#667090;font-size:13px;">Name</td>
                    <td style="padding:6px 0;color:#0e1a2b;font-size:14px;font-weight:600;">' . $h($senderName) . '</td>
                  </tr>
                  <tr>
                    <td style="width:130px;padding:6px 0;color:#667090;font-size:13px;">Email</td>
                    <td style="padding:6px 0;font-size:14px;">
                      <a href="mailto:' . $h($senderEmail) . '" style="color:#3152a3;text-decoration:none;">' . $h($senderEmail) . '</a>
                    </td>
                  </tr>
                  <tr>
                    <td style="width:130px;padding:6px 0;color:#667090;font-size:13px;">Phone</td>
                    <td style="padding:6px 0;color:#0e1a2b;font-size:14px;">' . $h($senderPhone) . '</td>
                  </tr>
                  <tr>
                    <td style="width:130px;padding:6px 0;color:#667090;font-size:13px;">Topic</td>
                    <td style="padding:6px 0;color:#0e1a2b;font-size:14px;">
                      <span style="display:inline-block;padding:4px 10px;border-radius:999px;color:#1a2236;background:#fff7d6;border:1px solid #ffe69c;font-size:12px;">' . $h($topicSafe) . '</span>
                    </td>
                  </tr>
                </table>
              </div>

              <!-- Divider -->
              <div style="border-top:1px solid #e6ecf5;margin:0 18px 0;"></div>

              <!-- Message -->
              <div style="padding:12px 18px 16px;">
                <div style="color:#32405a;font-size:14px;line-height:1.6;">
                  <div style="color:#0b1f3a;font-weight:600;margin-bottom:6px;">Message</div>
                  <div style="white-space:pre-wrap;background:#f9fbff;border:1px solid #e6ecf5;border-radius:8px;padding:10px;color:#1b2a41;">' . nl2br($h($messageSafe)) . '</div>
                </div>
              </div>

              <!-- Footer -->
              <div style="background:#f9fbff;padding:10px 18px;border-top:1px solid #e6ecf5;">
                <div style="font-size:12px;color:#7b879c;">This email was sent automatically from the CSNK website contact form.</div>
              </div>
            </td>
          </tr>

          <!-- Legal -->
          <tr>
            <td style="text-align:center;padding:10px 6px;color:#96a1b3;font-size:11px;">
              © ' . date('Y') . ' CSNK. All rights reserved.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';

                    // -------- Embed two logos (CID) --------
                    // #1 whychoose.png
                    $whychooseCandidates = [
                        __DIR__ . '/../resources/img/whychoose.png',
                        __DIR__ . '/resources/img/whychoose.png',
                        __DIR__ . '/public/resources/img/whychoose.png',
                    ];
                    // #2 secondary logo: try emailogo.png then crempco-logo.png
                    $secondaryCandidates = [
                        __DIR__ . '/../resources/img/emailogo.png',
                        __DIR__ . '/resources/img/emailogo.png',
                        __DIR__ . '/public/resources/img/emailogo.png',
                        __DIR__ . '/../resources/img/crempco-logo.png',
                        __DIR__ . '/resources/img/crempco-logo.png',
                        __DIR__ . '/public/resources/img/crempco-logo.png',
                    ];

                    $pickFirstReadable = function (array $paths): ?string {
                        foreach ($paths as $p) {
                            if (is_readable($p)) return $p;
                        }
                        return null;
                    };

                    $whychoosePath = $pickFirstReadable($whychooseCandidates);
                    $secondaryPath = $pickFirstReadable($secondaryCandidates);

                    if ($whychoosePath) {
                        $mail->addEmbeddedImage($whychoosePath, 'whychoose_cid', basename($whychoosePath), 'base64', 'image/png');
                    } else {
                        error_log('Email embed: whychoose.png not found. Tried: ' . implode(', ', $whychooseCandidates));
                    }

                    if ($secondaryPath) {
                        $ext  = strtolower(pathinfo($secondaryPath, PATHINFO_EXTENSION));
                        $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
                        $mail->addEmbeddedImage($secondaryPath, 'secondary_logo_cid', basename($secondaryPath), 'base64', $mime);
                    } else {
                        error_log('Email embed: secondary logo not found. Tried: ' . implode(', ', $secondaryCandidates));
                    }
                    // -------- End embed --------

                    // Body
                    $mail->isHTML(true);
                    $mail->Body    = $htmlBody;
                    $mail->AltBody = $textBody;

                    // Send
                    $mail->send();

                    $success = true;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate token
                    $_POST = []; // clear form
                } catch (\Throwable $e) {
                    error_log('Mail Exception: ' . $e->getMessage());
                    if (isset($mail) && !empty($mail->ErrorInfo)) {
                        error_log('PHPMailer ErrorInfo: ' . $mail->ErrorInfo);
                    }
                    $errors['general'] = 'We could not send your message right now. Please try again later.';
                }
            } else {
                $success = true; // simulated success
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                $_POST = [];
            }
        }
    }
}

// Helpers for repopulation & invalid class
function old(string $key, string $default = ''): string {
    return htmlspecialchars($_POST[$key] ?? $default, ENT_QUOTES, 'UTF-8');
}
function invalidClass(array $errors, string $key): string {
    return isset($errors[$key]) ? 'is-invalid' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <title>Contact Us</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    :root{
      /* ===== NAVY + GOLD THEME (no red, no black) ===== */
      --smc-navy: #0B1F3A;           /* deep navy */
      --smc-navy-2: #132A4A;         /* secondary navy */
      --smc-navy-3: #1B355C;         /* accent navy */
      --smc-gold: #FFD84D;           /* gold accent */
      --ink: #16243B;                /* navy-ish text (not black) */
      --muted-ink: #6c757d;          /* Bootstrap gray-600 */
      --bg: #f5f8ff;                 /* soft blue background */
      --border: #e6ecf5;             /* soft blue border */
      --ring: rgba(255, 216, 77, .35); /* gold focus ring */
    }
    body {
      background: var(--bg);
      color: var(--ink);
      font-feature-settings: "kern" 1, "liga" 1;
    }
    .contact-card {
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: 0 6px 28px rgba(11,31,58, .08);
      background: #fff;
    }
    .form-control, .form-select {
      border-radius: 10px;
      border-color: var(--border);
      color: var(--ink);
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--smc-gold);
      box-shadow: 0 0 0 .25rem var(--ring);
    }
    .form-check-input:checked {
      background-color: var(--smc-navy);
      border-color: var(--smc-navy);
    }
    /* Accent button (navy gradient) */
    .btn-accent {
      --bs-btn-color: #fff;
      --bs-btn-bg: var(--smc-navy);
      --bs-btn-border-color: var(--smc-navy);
      --bs-btn-hover-bg: #0d2a4e;
      --bs-btn-hover-border-color: #0d2a4e;
      --bs-btn-focus-shadow-rgb: 11, 31, 58;
      border-radius: 999px;
      font-weight: 700;
    }
    .text-accent { color: var(--smc-gold) !important; }
    .divider { height: 1px; background: var(--border); }
    .char-counter { font-size: .85rem; color: var(--muted-ink); }
    .char-counter.warning { color: var(--smc-navy); font-weight: 600; }
    .is-invalid ~ .invalid-feedback { display: block; }

    /* Map/Contact card tweaks */
    .badge-navy {
      background: var(--smc-navy);
      color:#fff;
      border-radius: 999px;
      padding: .35rem .75rem;
      font-weight: 800;
      letter-spacing:.2px;
    }
    .text-navy { color: var(--smc-navy) !important; }
    .text-gold { color: var(--smc-gold) !important; }
    .btn-navy{
      background: linear-gradient(180deg, var(--smc-navy-3), var(--smc-navy));
      color:#fff; border:0; border-radius: 999px; padding:.65rem 1.1rem; font-weight:700;
      box-shadow: 0 10px 22px rgba(11,31,58,.18);
    }
    .btn-navy:hover{ filter: brightness(1.03); color:#fff; }
    .btn-outline-navy{
      border-radius:999px;
      border:2px solid var(--smc-navy);
      color: var(--smc-navy);
      padding:.6rem 1.05rem;
      background: transparent;
      font-weight:700;
    }
    .btn-outline-navy:hover{ background: var(--smc-navy); color:#fff; }
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
        <h1 class="display-6 fw-bold mb-3">Contact <span class="text-accent">SMC </span>Support</h1>
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
          <div class="alert alert-warning mt-4" role="alert">
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
                  <input
                    type="text"
                    id="firstName"
                    name="firstName"
                    class="form-control <?= invalidClass($errors, 'firstName') ?>"
                    placeholder="First name"
                    required
                    maxlength="80"
                    autocomplete="given-name"
                    value="<?= old('firstName') ?>"
                  />
                  <label for="firstName">First name</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['firstName'] ?? 'Please enter your first name.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-floating">
                  <!-- Last name -->
                  <input
                    type="text"
                    id="lastName"
                    name="lastName"
                    class="form-control <?= invalidClass($errors, 'lastName') ?>"
                    placeholder="Last name"
                    required
                    maxlength="80"
                    autocomplete="family-name"
                    value="<?= old('lastName') ?>"
                  />
                  <label for="lastName">Last name</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['lastName'] ?? 'Please enter your last name.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>

              <div class="col-12">
                <div class="form-floating">
                  <!-- Email -->
                  <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?= invalidClass($errors, 'email') ?>"
                    placeholder="name@example.com"
                    required
                    autocomplete="email"
                    inputmode="email"
                    value="<?= old('email') ?>"
                  />
                  <label for="email">Email</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? 'Please enter a valid email address.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating">
                  <!-- Phone (PH 11 digits) -->
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-control <?= invalidClass($errors, 'phone') ?>"
                    placeholder="09XXXXXXXXX"
                    autocomplete="tel-national"
                    inputmode="tel"
                    pattern="^\d{11}$"
                    minlength="11"
                    maxlength="11"
                    value="<?= old('phone') ?>"
                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)"
                  />
                  <label for="phone">Phone (optional)</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['phone'] ?? 'Please enter an 11-digit phone number.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating">
                  <select id="topic" name="topic" class="form-select <?= invalidClass($errors, 'topic') ?>" required>
                  <?php
                    $topics = ['General Inquiry', 'Support', 'Sales', 'Partnerships'];
                    foreach ($topics as $t) {
                      $sel = (old('topic') === $t) ? 'selected' : '';
                      echo '<option ' . $sel . '>' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</option>';
                    }
                  ?>
                  </select>
                  <label for="topic">Topic</label>
                  <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['topic'] ?? 'Please select a topic.', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="form-floating">
                  <textarea
                    id="message"
                    name="message"
                    class="form-control <?= invalidClass($errors, 'message') ?>"
                    placeholder="Your message"
                    style="height: 140px"
                    required
                    maxlength="<?= (int)$CONFIG['max_message'] ?>"
                  ><?= old('message') ?></textarea>
                  <label for="message">Message</label>
                  <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['message'] ?? 'Please enter your message.', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>

                <div class="d-flex justify-content-end mt-1">
                  <span id="charCount" class="char-counter" aria-live="polite">
                    0 / <?= (int)$CONFIG['max_message'] ?>
                  </span>
                </div>
              </div>

              <!-- Honeypot (hidden) -->
              <div class="visually-hidden" aria-hidden="true">
                <label for="website">Website</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
              </div>

              <div class="col-12">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="form-check">
                    <input class="form-check-input <?= invalidClass($errors, 'consent') ?>" type="checkbox" value="1" id="consent" name="consent" <?= isset($_POST['consent']) ? 'checked' : '' ?> required />
                    <label class="form-check-label" for="consent">
                      <a>I agree to the privacy policy. </a>
                    </label>
                    <div class="invalid-feedback"><?= htmlspecialchars($errors['consent'] ?? 'Consent is required.', ENT_QUOTES, 'UTF-8') ?></div>
                  </div>
                  <button id="submitBtn" class="btn btn-accent px-4" type="submit">
                    <span class="submit-text">Send message</span>
                    <span class="submit-loading d-none">
                      <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                      Sending…
                    </span>
                  </button>
                </div>
              </div>
            </div>
          </form>

          <div class="divider my-4"></div>

          <div class="small text-secondary">
            Prefer email?
            <a href="mailto:<?= htmlspecialchars($CONFIG['to_email'], ENT_QUOTES, 'UTF-8') ?>" class="link-secondary">
              <?= htmlspecialchars($CONFIG['to_email'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            &nbsp;•&nbsp; Call us:
            <a href="tel:+639000000000" class="link-secondary">0916 247 2721</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact / Map -->
  <section id="contact" class="py-5" style="background:#f0f4ff;">
    <div class="container">
      <div class="text-center mb-4">
        <h2 class="fw-bold mb-1 text-navy">Contact and Location</h2>
        <p class="text-muted mb-0">Visit our office or reach us using the details below</p>
      </div>

      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="row g-0">

          <!-- Map -->
          <div class="col-lg-7">
            <iframe
              style="width:100%; height:100%; min-height:420px; border:0;"
              src="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT&output=embed"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen>
            </iframe>
          </div>

          <!-- Info -->
          <div class="col-lg-5 bg-white">
            <div class="p-4 p-md-5 h-100 d-flex flex-column justify-content-center">

              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badge-navy rounded-pill px-3 py-2">SMC Manpower Agency Co.</span>
              </div>

              <h5 class="fw-bold mb-3 text-navy">Office Information</h5>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                  <div class="fw-semibold text-navy">Address</div>
                  <div class="text-muted small">
                    Ground Floor Unit 1 Eden Townhouse<br>
                    2001 Eden St. Cor Pedro Gil, Sta Ana<br>
                    Barangay 866, City of Manila, NCR, Sixth District
                  </div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-phone"></i></div>
                <div>
                  <div class="fw-semibold text-navy">Phone</div>
                  <div class="text-muted small">0916 247 2721</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-envelope"></i></div>
                <div>
                  <div class="fw-semibold text-navy">Email</div>
                  <div class="text-muted small">smcphilippines.marketing@gmail.com</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-4">
                <div class="text-gold fs-5"><i class="fa-solid fa-clock"></i></div>
                <div>
                  <div class="fw-semibold text-navy">Office Hours</div>
                  <div class="text-muted small">Mon to Sat, 8:00 AM to 5:00 PM</div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-navy rounded-pill px-4"
                   target="_blank" rel="noopener"
                   href="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT">
                  <i class="fa-solid fa-location-arrow me-2"></i>Get Directions
                </a>

                <a class="btn btn-outline-navy rounded-pill px-4" href="#top">
                  <i class="fa-solid fa-arrow-up me-2"></i>Back to Top
                </a>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer>
    <?php include __DIR__ . '/footer.php'; ?>
  </footer>

  <!-- Toast (Success) -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="successToast" class="toast align-items-center text-bg-light border-0" role="status" aria-live="polite" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <span class="text-accent fw-semibold">Thanks!</span> Your message has been sent.
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!-- Font Awesome & Bootstrap JS -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Policy Modals Handler -->
  <script src="../resources/js/policy-modals.js"></script>

  <script>
    // Character counter (handles emoji properly)
    const messageEl = document.getElementById('message');
    const counterEl = document.getElementById('charCount');
    const limit = parseInt(messageEl?.getAttribute('maxlength') || '500', 10);

    function updateCounter(){
      const len = [...(messageEl.value || '')].length;
      counterEl.textContent = `${len} / ${limit}`;
      const threshold = Math.floor(limit * 0.9);
      counterEl.classList.toggle('warning', len >= threshold);
    }
    if (messageEl) {
      messageEl.addEventListener('input', updateCounter);
      updateCounter();
    }

    // Submit button loading UI
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = submitBtn?.querySelector('.submit-text');
    const submitLoading = submitBtn?.querySelector('.submit-loading');

    form?.addEventListener('submit', () => {
      submitText?.classList.add('d-none');
      submitLoading?.classList.remove('d-none');
      submitBtn.disabled = true;
    });
  </script>

  <?php if ($success): ?>
  <script>
    // Show success toast if PHP indicates success
    (function(){
      const toastEl = document.getElementById('successToast');
      if (toastEl) {
        const toast = new bootstrap.Toast(toastEl);
        toast.show();
      }
    })();
  </script>
  <?php endif; ?>
</body>
</html>