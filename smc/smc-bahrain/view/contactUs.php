<?php
// contactUs.php
declare(strict_types=1);
session_start();

// -----------------------------
// Optional: simple session rate limit (5 posts / 10 minutes)
// -----------------------------
if (!isset($_SESSION['contact_rate'])) {
  $_SESSION['contact_rate'] = ['count' => 0, 'start' => time()];
} else {
  $window = 10 * 60; // 10 minutes
  if (time() - $_SESSION['contact_rate']['start'] > $window) {
    $_SESSION['contact_rate'] = ['count' => 0, 'start' => time()];
  }
}

// -----------------------------
// Load PHPMailer (if available)
// -----------------------------
$composer_autoload = __DIR__ . '/../vendor/autoload.php';
$composer_autoload_missing = false;
if (is_readable($composer_autoload)) {
  require_once $composer_autoload;
} else {
  error_log('Composer autoload missing or unreadable: ' . $composer_autoload);
  $composer_autoload_missing = true;
}

use PHPMailer\PHPMailer\PHPMailer;

// --------------------------------------------------
// Configuration (ENV-aware with safe fallbacks)
// --------------------------------------------------
$CONFIG = [
  // ---- UPDATE AS NEEDED ----
  'to_email'      => getenv('CONTACT_TO_EMAIL') ?: 'smcphilippines.marketing@gmail.com',
  'to_name'       => getenv('CONTACT_TO_NAME')  ?: 'SMC Support',
  'from_email'    => getenv('SMTP_FROM_EMAIL')  ?: 'csnkmanila@gmail.com',
  'from_name'     => getenv('SMTP_FROM_NAME')   ?: 'SMC Manpower Agency',
  'subject'       => getenv('MAIL_SUBJECT')     ?: 'SMC Contact Form Submission',
  // --------------------------
  'max_message'   => 500,

  // PHPMailer / SMTP
  'smtp_host'     => getenv('SMTP_HOST')        ?: 'smtp.gmail.com',
  'smtp_port'     => (int)(getenv('SMTP_PORT')  ?: 587),
  'smtp_user'     => getenv('SMTP_USER')        ?: 'csnkmanila@gmail.com',
  'smtp_pass'     => getenv('SMTP_PASS')        ?: 'hqyp ljaf kwyd fkzo', // App Password
  'smtp_encrypt'  => 'tls',
  'smtp_debug'    => (int)(getenv('SMTP_DEBUG') ?: 0),
  'enable_mail'   => filter_var(getenv('ENABLE_MAIL') ?: '1', FILTER_VALIDATE_BOOL),
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
  // Basic rate limiting
  if ($_SESSION['contact_rate']['count'] >= 5) {
    $errors['general'] = 'Too many submissions. Please wait a few minutes and try again.';
  }

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
    // International phone: + and 7-15 digits
    $phoneRaw  = clean($_POST['phone'] ?? '');
    $phone     = preg_replace('/\s+/', '', $phoneRaw);
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
    // Allow + and 7–15 digits for international; or leave blank
    if ($phone !== '' && !preg_match('/^\+?\d{7,15}$/', $phone)) {
      $errors['phone'] = 'Please enter a valid international phone (e.g., +973XXXXXXXX or +639XXXXXXXXX).';
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
      // Plain text fallback
      $textBody =
        "New SMC contact form submission:\n\n" .
        "Name: {$firstName} {$lastName}\n" .
        "Email: {$email}\n" .
        "Phone: {$phone}\n" .
        "Topic: {$topic}\n\n" .
        "Message:\n{$message}\n";

      if ($CONFIG['enable_mail']) {
        if ($composer_autoload_missing || !class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
          error_log('PHPMailer not available; skipping email send.');
          $errors['general'] = 'Mail service is temporarily unavailable. Please try again later.';
          $CONFIG['enable_mail'] = false;
        }
      }

      if ($CONFIG['enable_mail']) {
        try {
          $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
          $mail->CharSet = 'UTF-8';
          $mail->isSMTP();
          $mail->SMTPDebug   = $CONFIG['smtp_debug'] ?? 0;
          $mail->Debugoutput = function ($str, $level) { error_log("PHPMailer debug {$level}: {$str}"); };
          $mail->Host        = $CONFIG['smtp_host'];
          $mail->SMTPAuth    = true;
          $mail->Username    = $CONFIG['smtp_user'];
          $mail->Password    = $CONFIG['smtp_pass'];
          $mail->SMTPSecure  = $CONFIG['smtp_encrypt'];
          $mail->SMTPAutoTLS = true;
          $mail->Port        = (int)$CONFIG['smtp_port'];

          // Dev TLS relax for localhost
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

          // Recipients
          $mail->setFrom($CONFIG['from_email'], $CONFIG['from_name']);
          $mail->addAddress($CONFIG['to_email'], $CONFIG['to_name'] ?? '');
          if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($email, trim($firstName . ' ' . $lastName));
            // Optional: send a copy to sender
            $mail->addCC($email);
          }

          // Subject
          $subjectBase = $CONFIG['subject'] ?: 'SMC Contact Message';
          $mail->Subject = $subjectBase . (trim($topic) !== '' ? ' - ' . $topic : '');

          // Small escaper
          $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
          $senderName  = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));
          $senderEmail = $email ?? '';
          $senderPhone = $phone ?? '';
          $topicSafe   = $topic ?? '';
          $messageSafe = $message ?? '';

          // Simple brand email (no CID needed)
          $htmlBody = '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>SMC Contact</title></head><body style="margin:0;padding:0;background:#f6f8fb;font-family:Arial,Helvetica,sans-serif;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f6f8fb;padding:24px 0;">
    <tr><td align="center">
      <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="width:640px;max-width:94%;">
        <tr><td style="background:linear-gradient(90deg,#1B355C,#0B1F3A);padding:14px 18px;border-top-left-radius:10px;border-top-right-radius:10px;">
          <h1 style="margin:0;font-size:18px;color:#fff;font-weight:700;">New Contact Message</h1>
          <div style="margin-top:4px;font-size:12px;color:#cfe0ff;">Received via SMC website</div>
        </td></tr>
        <tr><td style="background:#ffffff;border:1px solid #e6ecf5;border-top:0;padding:16px 18px;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
            <tr><td style="width:130px;padding:8px 0;color:#667090;font-size:13px;">Name</td><td style="padding:8px 0;color:#0e1a2b;font-size:14px;font-weight:600;">' . $h($senderName) . '</td></tr>
            <tr><td style="width:130px;padding:8px 0;color:#667090;font-size:13px;">Email</td><td style="padding:8px 0;"><a href="mailto:' . $h($senderEmail) . '" style="color:#3152a3;text-decoration:none;">' . $h($senderEmail) . '</a></td></tr>
            <tr><td style="width:130px;padding:8px 0;color:#667090;font-size:13px;">Phone</td><td style="padding:8px 0;color:#0e1a2b;font-size:14px;">' . $h($senderPhone) . '</td></tr>
            <tr><td style="width:130px;padding:8px 0;color:#667090;font-size:13px;">Topic</td><td style="padding:8px 0;color:#0e1a2b;font-size:14px;"><span style="display:inline-block;padding:4px 10px;border-radius:999px;background:#fff7d6;border:1px solid #ffe69c;font-size:12px;">' . $h($topicSafe) . '</span></td></tr>
          </table>
          <div style="border-top:1px solid #e6ecf5;margin:12px 0;"></div>
          <div style="color:#32405a;font-size:14px;line-height:1.6;">
            <div style="color:#0b1f3a;font-weight:600;margin-bottom:6px;">Message</div>
            <div style="white-space:pre-wrap;background:#f9fbff;border:1px solid #e6ecf5;border-radius:8px;padding:10px;color:#1b2a41;">' . nl2br($h($messageSafe)) . '</div>
          </div>
        </td></tr>
        <tr><td style="background:#f9fbff;border:1px solid #e6ecf5;border-top:0;border-bottom-left-radius:10px;border-bottom-right-radius:10px;padding:10px 18px;">
          <div style="font-size:12px;color:#7b879c;">© ' . date('Y') . ' SMC Manpower Agency Philippines Company</div>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>';

          $mail->isHTML(true);
          $mail->Body    = $htmlBody;
          $mail->AltBody = $textBody;

          $mail->send();

          $success = true;
          $_SESSION['contact_rate']['count']++;
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
        $_SESSION['contact_rate']['count']++;
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
<html lang="en" dir="ltr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <title>Contact — SMC Manpower Agency Philippines Co.</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <!-- Optional Arabic font (used when RTL is active) -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet"/>

  <style>
    :root{
      /* Brand */
      --smc-navy: #0B1F3A;
      --smc-navy-2: #132A4A;
      --smc-navy-3: #1B355C;
      --smc-gold: #FFD84D;
      --bh-red:  #CE1126;

      --ink: #16243B;
      --muted-ink: #6c757d;
      --bg: #f5f8ff;
      --border: #e6ecf5;
      --ring: rgba(255, 216, 77, .35);
      --radius: 14px;

      --shadow: 0 6px 28px rgba(11,31,58, .08);
      --shadow-lg: 0 18px 40px rgba(11,31,58,.12);
    }

    html, body { background: var(--bg); color: var(--ink); }
    body.rtl { direction: rtl; font-family: "Noto Kufi Arabic", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial; }
    .flip-rtl { transition: transform .15s ease; }
    .rtl .flip-rtl { transform: scaleX(-1); }

    /* Header banner */
    .page-header {
      padding: clamp(2rem,5vw,3.6rem) 0;
      background:
        radial-gradient(900px 320px at 10% 0%, rgba(255,216,77,.14), rgba(255,216,77,0) 60%),
        radial-gradient(900px 320px at 95% 100%, rgba(206,17,38,.12), rgba(206,17,38,0) 60%),
        linear-gradient(120deg, var(--smc-navy) 20%, var(--smc-navy-2) 80%);
      color:#fff;
      border-bottom-left-radius: 2rem;
      border-bottom-right-radius: 2rem;
      box-shadow: var(--shadow-lg);
      margin-bottom:2.2rem;
    }
    .page-header h1 { font-weight:800; letter-spacing:-.3px; }
    .page-header p { opacity:.9; }

    /* Trust strip */
    .trust-strip .item{
      background:#fff; border:1px solid rgba(11,31,58,.10); border-radius:999px;
      padding:.45rem .8rem; display:inline-flex; align-items:center; gap:.5rem; font-weight:700;
    }

    /* Card */
    .contact-card {
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
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
    .btn-accent {
      --bs-btn-color: #fff;
      --bs-btn-bg: var(--smc-navy);
      --bs-btn-border-color: var(--smc-navy);
      --bs-btn-hover-bg: #0d2a4e;
      --bs-btn-hover-border-color: #0d2a4e;
      --bs-btn-focus-shadow-rgb: 11,31,58;
      border-radius: 999px;
      font-weight: 800;
    }
    .text-accent { color: var(--smc-gold) !important; }
    .divider { height: 1px; background: var(--border); }

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
      color:#fff; border:0; border-radius: 999px; padding:.65rem 1.1rem; font-weight:800;
      box-shadow: 0 10px 22px rgba(11,31,58,.18);
    }
    .btn-navy:hover{ filter: brightness(1.03); color:#fff; }
    .btn-outline-navy{
      border-radius:999px;
      border:2px solid var(--smc-navy);
      color: var(--smc-navy);
      padding:.6rem 1.05rem;
      background: transparent;
      font-weight:800;
    }
    .btn-outline-navy:hover{ background: var(--smc-navy); color:#fff; }

    .char-counter { font-size: .85rem; color: var(--muted-ink); }
    .char-counter.warning { color: var(--smc-navy); font-weight: 700; }

    /* Floating Translate button */
    .lang-toggle{
      position: fixed; top:16px; left:16px; z-index: 1040;
      display:inline-flex; align-items:center; gap:.5rem;
      background:#fff; color: var(--bh-red); border:2px solid var(--bh-red);
      border-radius:999px; padding:.4rem .9rem; font-weight:900;
      box-shadow:0 8px 22px rgba(206,17,38,.18), 0 1px 0 #fff inset;
      cursor:pointer;
    }
    .lang-toggle .dot { width:.5rem; height:.5rem; background:var(--bh-red); border-radius:50%; display:inline-block; }
  </style>
</head>
<body>

  <!-- Floating Translate Button (EN ⇄ AR) -->
  <button id="langToggle" class="lang-toggle" type="button" aria-live="polite" aria-pressed="false" title="Translate to Arabic">
    <span class="dot" aria-hidden="true"></span>
    <span id="langToggleLabel">AR</span>
  </button>

  <!-- Header / Navbar -->
  <?php $page = 'contact'; include __DIR__ . '/navbar.php'; ?>



  <!-- Trust Str

              <div class="col-12">
                <div class="form-floating">
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
                  <label for="email" data-i18n="form.email">Email</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['email'] ?? 'Please enter a valid email address.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating">
                  <input
                    type="tel"
                    id="phone"
                    name="phone"
                    class="form-control <?= invalidClass($errors, 'phone') ?>"
                    placeholder="+973XXXXXXXX"
                    au<option value="" ' . ($current===''?'selected':'') . ' disabled>Choose a topic…</option>';
                      foreach ($topics as $t) {
                        $sel = ($current === $t) ? 'selected' : '';
                        echo '<option ' . $sel . '>' . htmlspecialchars($t, ENT_QUOTES, 'UTF-8') . '</option>';
                      }
                    ?>
                  </select>
                  <label for="topic" data-i18n="form.topic">Topic</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['topic'] ?? 'Please select a topic.', ENT_QUOTES, 'UTF-8') ?></div>
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
                  <label for="message" data-i18n="form.message">Message</label>
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['message'] ?? 'Please enter your message.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>

              </div>
            </div>
          </form>

          <div class="divider my-4"></div>

          <div class="small text-secondary">
            <span data-i18n="contact.pref">Prefer email?</span>
            <a href="mailto:<?= htmlspecialchars($CONFIG['to_email'], ENT_QUOTES, 'UTF-8') ?>" class="link-secondary">
              <?= htmlspecialchars($CONFIG['to_email'], ENT_QUOTES, 'UTF-8') ?>
            </a>
            &nbsp;•&nbsp; <span data-i18n="contact.call">Call us:</span>
            <a href="tel:+639162472721" class="link-secondary">+63 916 247 2721</a>
            &nbsp;•&nbsp; <span data-i18n="contact.whatsapp">WhatsApp:</span>
            <a href="https://wa.me/639393427412" class="link-secondary" target="_blank" rel="noopener">+63 939 342 7412</a>
          </div>
        </div>
      </div>
"fw-semibold text-navy" data-i18n="office.phone_label">Phone</div>
                    <div class="text-muted small">+63 916 247 2721</div>
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
                    <div class="fw-semibold text-navy" data-i18n="office.hours_label">Office Hours</div>
                    <div class="text-muted small" data-i18n="office.hours">Mon to Sat, 8:00 AM to 5:00 PM</div>
                  </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-navy rounded-pill px-4"
                     target="_blank" rel="noopener"
                     href="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT">
                    <i class="fa-solid fa-location-arrow me-2 flip-rtl"></i><span data-i18n="office.dir">Get Directions</span>
                  </a>

                  <a class="btn btn-outline-navy rounded-pill px-4" href="#top">
                    <i class="fa-solid fa-arrow-up me-2"></i><span data-i18n="office.top">Back to Top</span>
                  </a>
                </div>
              </div>
            </div>

            <div class="col-12">
              <iframe
                style="width:100%; height:100%; min-height:300px; border:0;"
                src="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT&output=embed"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen>
              </iframe>
            </div>
          </div>
        </div>
      </div>
    </div><!-- /row -->
  </section>

  <!-- Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- Success Toast -->
  <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1080">
    <div id="successToast" class="toast align-items-center text-bg-light border-0" role="status" aria-live="polite" aria-atomic="true">
      <div class="d-flex">
        <div class="toast-body">
          <span class="text-accent fw-semibold" data-i18n="toast.thanks">Thanks!</span> <span data-i18n="toast.sent">Your message has been sent.</span>
        </div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Policy Modals Handler (if you use it elsewhere) -->
  <script src="../resources/js/policy-modals.js"></script>

  <script>
    // Character counter (emoji-safe)
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

    // Submit loading
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = submitBtn?.querySelector('.submit-text');
    const submitLoading = submitBtn?.querySelector('.submit-loading');
    form?.addEventListener('submit', () => {
      submitText?.classList.add('d-none');
      submitLoading?.classList.remove('d-none');
      submitBtn.disabled = true;
    });

    // Show success toast
    <?php if ($success): ?>
    (function(){
      const toastEl = document.getElementById('successToast');
      if (toastEl) new bootstrap.Toast(toastEl).show();
    })();
    <?php endif; ?>

    // -------------------------------
    // Simple i18n (EN ⇄ AR)
    // -------------------------------
    const I18N_AR = {
      "hero.title": "تواصل مع دعم <span class='text-accent'>إس إم سي</span>",
      "hero.sub": "نحن هنا للمساعدة. أرسل رسالتك وسنعاود الرد قريبًا.",
      "trust.bh": "تركيز على البحرين",
      "trust.dmw": "ترخيص DMW",
      "trust.compliance": "الالتزام أولاً",
      "trust.ethical": "توظيف أخلاقي",
      "trust.fast": "استجابة خلال 24 ساعة",

      "form.first": "الاسم الأول",
      "form.last": "اسم العائلة",
      "form.email": "البريد الإلكتروني",
      "form.phone": "الهاتف (اختياري، دولي)",
      "form.topic": "الموضوع",
      "form.message": "الرسالة",
      "form.consent": "أوافق على",
      "form.privacy": "سياسة الخصوصية",
      "form.submit": "إرسال الرسالة",
      "form.sending": "جاري الإرسال…",

      "contact.pref": "تفضّل البريد الإلكتروني؟",
      "contact.call": "اتصل بنا:",
      "contact.whatsapp": "واتساب:",

      "office.title": "معلومات المكتب",
      "office.addr_label": "العنوان",
      "office.addr": "وحدة 1 إدين تاون هومز<br>2001 شارع إدين زاوية شارع بيدرو جيل، سانا آنا<br>بارانغاي 866، مدينة مانيلا، NCR، المنطقة السادسة",
      "office.phone_label": "الهاتف",
      "office.hours_label": "ساعات العمل",
      "office.hours": "من الإثنين إلى السبت، 8:00 ص إلى 5:00 م",
      "office.dir": "الحصول على الاتجاهات",
      "office.top": "العودة للأعلى",

      "toast.thanks": "شكرًا!",
      "toast.sent": "تم إرسال رسالتك."
    };

    const i18nNodes = Array.from(document.querySelectorAll('[data-i18n]'));
    i18nNodes.forEach(n => { n.dataset.en = n.innerHTML; });

    const setLang = (lang) => {
      const html = document.documentElement;
      const body = document.body;
      const toggle = document.getElementById('langToggle');
      const label = document.getElementById('langToggleLabel');

      if (lang === 'ar') {
        html.setAttribute('lang', 'ar'); html.setAttribute('dir', 'rtl');
        body.classList.add('rtl');
        i18nNodes.forEach(n => {
          const key = n.getAttribute('data-i18n');
          const val = I18N_AR[key];
          if (typeof val === 'string') n.innerHTML = val;
        });
        toggle.setAttribute('aria-pressed', 'true');
        toggle.setAttribute('title', 'Return to English');
        label.textContent = 'EN';
      } else {
        html.setAttribute('lang', 'en'); html.setAttribute('dir', 'ltr');
        body.classList.remove('rtl');
        i18nNodes.forEach(n => { n.innerHTML = n.dataset.en; });
        toggle.setAttribute('aria-pressed', 'false');
        toggle.setAttribute('title', 'Translate to Arabic');
        label.textContent = 'AR';
      }
      localStorage.setItem('lang_contact', lang);
    };

    const saved = localStorage.getItem('lang_contact') || 'en';
    setLang(saved);

    document.getElementById('langToggle')?.addEventListener('click', () => {
      const current = localStorage.getItem('lang_contact') || 'en';
      setLang(current === 'en' ? 'ar' : 'en');
    });
  </script>
</body>
</html>