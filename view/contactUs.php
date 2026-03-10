<?php
// contactUs.php
declare(strict_types=1);
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
  $v = trim($v);
  $v = str_replace(["\r\n", "\r"], "\n", $v);
  return preg_replace('/[\x00-\x1F\x7F]/', '', $v);
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
  $senderPhone = $data['phone'] ?? '';
  $topicSafe   = $data['topic'] ?? '';
  $messageSafe = $data['message'] ?? '';

  $text =
    "You have a new CSNK contact form submission:\n\n" .
    "Name: {$senderName}\n" .
    "Email: {$senderEmail}\n" .
    "Phone: {$senderPhone}\n" .
    "Topic: {$topicSafe}\n\n" .
    "Message:\n{$messageSafe}\n";

  $html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="x-apple-disable-message-reformatting">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CSNK Manpower Agency</title>
</head>
<body style="margin:0;padding:0;background:#f2f4f7;font-family:Arial,Helvetica,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="padding:40px 0;background:#f2f4f7;">
    <tr><td align="center">
      <table width="700" cellpadding="0" cellspacing="0" role="presentation" style="width:700px;max-width:95%;background:rgba(255,255,255,0.88);backdrop-filter:blur(14px);border-radius:24px;border:1px solid #e4e7eb;box-shadow:0 8px 28px rgba(0,0,0,0.08);overflow:hidden;">
        <tr>
          <td align="center" style="padding:20px 0 10px;">
            <table cellspacing="0" cellpadding="0" role="presentation">
              <tr>
                <td style="padding:0 14px;">
                  <img src="cid:whychoose_cid" style="max-width:150px;height:auto;display:block;" alt="">
                </td>
                <td style="padding:0 14px;">
                  <img src="cid:secondary_logo_cid" style="max-width:150px;height:auto;display:block;" alt="">
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="background:linear-gradient(135deg,#d21f3c,#e63c43,#ff5a63);padding:26px 32px;border-top-left-radius:24px;border-top-right-radius:24px;">
            <div style="color:#ffffff;font-size:22px;font-weight:700;margin:0;">🌐 New Contact Message</div>
            <div style="color:#ffecec;font-size:13px;margin-top:4px;">Received via the CSNK Website</div>
          </td>
        </tr>
        <tr>
          <td style="padding:26px 34px 10px;">
            <table width="100%" cellspacing="0" cellpadding="0" role="presentation" style="background:#ffffff;border-radius:18px;border:1px solid #e9ecef;padding:20px 24px;box-shadow:0 2px 10px rgba(0,0,0,0.04);">
              <tr>
                <td style="width:140px;padding:10px 0;color:#6b7280;font-size:14px;font-weight:600;">👤 Name</td>
                <td style="padding:10px 0;color:#111827;font-size:15px;font-weight:700;">' . h($senderName) . '</td>
              </tr>
              <tr>
                <td style="padding:10px 0;color:#6b7280;font-size:14px;font-weight:600;">✉ Email</td>
                <td style="padding:10px 0;">
                  <a href="mailto:' . h($senderEmail) . '" style="color:#2563eb;font-size:15px;text-decoration:none;font-weight:600;">' . h($senderEmail) . '</a>
                </td>
              </tr>
              <tr>
                <td style="padding:10px 0;color:#6b7280;font-size:14px;font-weight:600;">📱 Phone</td>
                <td style="padding:10px 0;color:#111827;font-size:15px;font-weight:600;">' . h($senderPhone) . '</td>
              </tr>
              <tr>
                <td style="padding:10px 0;color:#6b7280;font-size:14px;font-weight:600;">🏷 Topic</td>
                <td style="padding:10px 0;">
                  <span style="display:inline-block;padding:6px 14px;background:#fff2f4;border:1px solid #f7cfd4;border-radius:999px;color:#d21f3c;font-size:13px;font-weight:700;">' . h($topicSafe) . '</span>
                </td>
              </tr>
            </table>
          </td>
        </tr>
        <tr>
          <td style="padding:6px 34px 30px;">
            <div style="font-size:15px;color:#d21f3c;font-weight:700;margin-bottom:10px;">💬 Message</div>
            <div style="background:#ffffff;border-radius:16px;padding:18px 22px;border:1px solid #f0c7cb;line-height:1.7;font-size:15px;color:#374151;white-space:pre-wrap;box-shadow:0 2px 8px rgba(0,0,0,0.05);">
              ' . nl2br(h($messageSafe)) . '
            </div>
          </td>
        </tr>
        <tr>
          <td style="background:#faf6f7;padding:16px 26px;border-top:1px solid #e5d1d4;text-align:center;">
            <div style="font-size:12px;color:#888888;font-style:italic;margin-bottom:4px;">This email was sent automatically from the CSNK website contact form.</div>
            <div style="font-size:12px;color:#aaaaaa;">© ' . $year . ' CSNK. All rights reserved.</div>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';

  return [$html, $text];
}

/**
 * Try Gmail SMTP (587 → 465), else local sendmail, else PHP mail()
 * Returns [bool $ok, string $err]
 */
function sendSmart(array $CONFIG, array $post, bool $phpmailerLoaded): array {
  [$htmlBody, $textBody] = buildBodies($post, (int)date('Y'));

  // Recipient and subject
  $toEmail = $CONFIG['to_email'];
  $toName  = $CONFIG['to_name'] ?? '';
  $subjectBase = $CONFIG['subject'] ?? 'CSNK Contact Message';
  $topic  = trim($post['topic'] ?? '');
  $subject = $subjectBase . ($topic !== '' ? ' - ' . $topic : '');

  // If PHPMailer is available, use it
  if ($phpmailerLoaded) {
    try {
      $mail = new PHPMailer(true);
      $mail->CharSet = 'UTF-8';
      $mail->SMTPDebug = (int)($CONFIG['smtp_debug'] ?? 0);
      $mail->Debugoutput = function ($str, $level) { error_log("PHPMailer debug {$level}: {$str}"); };

      // We'll attempt two SMTP profiles first:
      $smtpProfiles = [
        ['port' => 587, 'secure' => PHPMailer::ENCRYPTION_STARTTLS],
        ['port' => 465, 'secure' => PHPMailer::ENCRYPTION_SMTPS],
      ];

      $connected = false;
      $lastError = '';

      foreach ($smtpProfiles as $p) {
        try {
          $mail->clearAllRecipients();
          $mail->clearAttachments();
          $mail->clearCustomHeaders();

          $mail->isSMTP();
          $mail->Host       = $CONFIG['smtp_host'];
          $mail->SMTPAuth   = true;
          $mail->Username   = $CONFIG['smtp_user'];
          $mail->Password   = str_replace(' ', '', (string)$CONFIG['smtp_pass']); // strip spaces
          $mail->SMTPSecure = $p['secure'];
          $mail->SMTPAutoTLS = true;
          $mail->Port       = (int)$p['port'];

          // Allow self-signed only on localhost dev
          $host = $_SERVER['SERVER_NAME'] ?? ($_SERVER['HTTP_HOST'] ?? 'localhost');
          if (in_array($host, ['localhost', '127.0.0.1'], true) || stripos($host, 'localhost') !== false) {
            $mail->SMTPOptions = [
              'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
              ],
            ];
          }

          // Recipients & headers
          $mail->setFrom($CONFIG['from_email'], $CONFIG['from_name']);
          $mail->addAddress($toEmail, $toName);
          if (!empty($post['email']) && filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
            $replyName = trim(($post['firstName'] ?? '') . ' ' . ($post['lastName'] ?? ''));
            $mail->addCC($post['email']);
            $mail->addReplyTo($post['email'], $replyName);
          }
          $mail->Subject = $subject;

          // Embedded images (CID)
          $whychoosePath = pickFirstReadable([
            __DIR__ . '/../resources/img/whychoose.png',
            __DIR__ . '/resources/img/whychoose.png',
            __DIR__ . '/public/resources/img/whychoose.png',
          ]);
          $secondaryPath = pickFirstReadable([
            __DIR__ . '/../resources/img/emailogo.png',
            __DIR__ . '/resources/img/emailogo.png',
            __DIR__ . '/public/resources/img/emailogo.png',
            __DIR__ . '/../resources/img/crempco-logo.png',
            __DIR__ . '/resources/img/crempco-logo.png',
            __DIR__ . '/public/resources/img/crempco-logo.png',
          ]);
          if ($whychoosePath) {
            $mail->addEmbeddedImage($whychoosePath, 'whychoose_cid', basename($whychoosePath), 'base64', 'image/png');
          } else {
            error_log('Email embed: whychoose.png not found.');
          }
          if ($secondaryPath) {
            $ext  = strtolower(pathinfo($secondaryPath, PATHINFO_EXTENSION));
            $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
            $mail->addEmbeddedImage($secondaryPath, 'secondary_logo_cid', basename($secondaryPath), 'base64', $mime);
          } else {
            error_log('Email embed: secondary logo not found.');
          }

          $mail->isHTML(true);
          $mail->Body    = $htmlBody;
          $mail->AltBody = $textBody;

          $mail->send();
          $connected = true;
          break; // success via SMTP
        } catch (\Throwable $smtpEx) {
          $lastError = $smtpEx->getMessage();
          error_log('SMTP attempt failed on port ' . $p['port'] . ': ' . $lastError);
        }
      }

      if ($connected) {
        return [true, ''];
      }

      // If we got here, SMTP failed (blocked/timeout/etc). Try local sendmail via PHPMailer.
      try {
        $mail = new PHPMailer(true);
        $mail->CharSet = 'UTF-8';
        $mail->isMail(); // local MTA (Exim/sendmail)
        // Use domain-based From for SPF/DMARC alignment on local send
        $localFrom = fallbackFromForLocal();
        $mail->setFrom($localFrom, $CONFIG['from_name'] ?? 'Website');
        $mail->addAddress($toEmail, $toName);
        if (!empty($post['email']) && filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
          $replyName = trim(($post['firstName'] ?? '') . ' ' . ($post['lastName'] ?? ''));
          $mail->addReplyTo($post['email'], $replyName);
        }
        $mail->Subject = $subject;

        // Note: Local mail can embed images, but if sendmail strips cids in some stacks, it still degrades safely.
        $whychoosePath = pickFirstReadable([
          __DIR__ . '/../resources/img/whychoose.png',
          __DIR__ . '/resources/img/whychoose.png',
          __DIR__ . '/public/resources/img/whychoose.png',
        ]);
        $secondaryPath = pickFirstReadable([
          __DIR__ . '/../resources/img/emailogo.png',
          __DIR__ . '/resources/img/emailogo.png',
          __DIR__ . '/public/resources/img/emailogo.png',
          __DIR__ . '/../resources/img/crempco-logo.png',
          __DIR__ . '/resources/img/crempco-logo.png',
          __DIR__ . '/public/resources/img/crempco-logo.png',
        ]);
        if ($whychoosePath) {
          $mail->addEmbeddedImage($whychoosePath, 'whychoose_cid', basename($whychoosePath), 'base64', 'image/png');
        }
        if ($secondaryPath) {
          $ext  = strtolower(pathinfo($secondaryPath, PATHINFO_EXTENSION));
          $mime = ($ext === 'jpg' || $ext === 'jpeg') ? 'image/jpeg' : 'image/png';
          $mail->addEmbeddedImage($secondaryPath, 'secondary_logo_cid', basename($secondaryPath), 'base64', $mime);
        }

        $mail->isHTML(true);
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody;

        $mail->send();
        error_log('Mail sent via local sendmail fallback.');
        return [true, ''];
      } catch (\Throwable $sendmailEx) {
        error_log('Local sendmail fallback failed: ' . $sendmailEx->getMessage());
        return [false, 'Email service is unreachable right now. (SMTP blocked and local MTA failed)'];
      }
    } catch (\Throwable $e) {
      error_log('PHPMailer overall failure: ' . $e->getMessage());
      return [false, 'Unexpected mailer error.'];
    }
  }

  // -------------------- PHPMailer not available → PHP mail() plain text --------------------
  $to   = $toEmail;
  $subj = $subject;
  $from = fallbackFromForLocal();
  $headers  = "From: " . $CONFIG['from_name'] . " <{$from}>\r\n";
  if (!empty($post['email']) && filter_var($post['email'], FILTER_VALIDATE_EMAIL)) {
    $replyName = trim(($post['firstName'] ?? '') . ' ' . ($post['lastName'] ?? ''));
    $headers .= 'Reply-To: ' . ($replyName ? "{$replyName} <{$post['email']}>" : $post['email']) . "\r\n";
  }
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

  [, $plain] = buildBodies($post, (int)date('Y'));
  $ok = @mail($to, $subj, $plain, $headers);
  if ($ok) {
    error_log('Mail sent via PHP mail() fallback (no PHPMailer available).');
    return [true, ''];
  }
  return [false, 'Mail function failed on the server.'];
}

// -------------------- Handle POST --------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Honeypot
  $honeypot = $_POST['website'] ?? '';

  // CSRF
  $csrf = $_POST['csrf_token'] ?? '';
  if (!hash_equals($_SESSION['csrf_token'] ?? '', $csrf)) {
    $errors['general'] = 'Security verification failed. Please refresh and try again.';
  }

  if (!empty($honeypot)) {
    // Silently succeed for bots (do not actually send)
    $success = true;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $_POST = [];
  } else {
    // Gather
    $firstName = clean($_POST['firstName'] ?? '');
    $lastName  = clean($_POST['lastName']  ?? '');
    $email     = clean($_POST['email']     ?? '');
    $phone     = preg_replace('/\D+/', '', clean($_POST['phone'] ?? ''));
    $topic     = clean($_POST['topic']     ?? '');
    $message   = clean($_POST['message']   ?? '');

    // Validate
    if ($firstName === '' || mb_strlen($firstName) > 80) $errors['firstName'] = 'Please enter your first name (max 80 characters).';
    if ($lastName === ''  || mb_strlen($lastName)  > 80) $errors['lastName']  = 'Please enter your last name (max 80 characters).';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if ($phone !== '' && !preg_match('/^\d{11}$/', $phone)) $errors['phone'] = 'Please enter an 11-digit phone number.';
    if ($topic === '' || !in_array($topic, $ALLOWED_TOPICS, true)) $errors['topic'] = 'Please select a topic.';
    if ($message === '' || mb_strlen($message) > (int)$CONFIG['max_message']) $errors['message'] = 'Please enter your message (max ' . (int)$CONFIG['max_message'] . ' characters).';
    if (empty($_POST['consent'])) $errors['consent'] = 'Consent is required.';

    if (!$errors) {
      [$ok, $err] = sendSmart($CONFIG, [
        'firstName' => $firstName,
        'lastName'  => $lastName,
        'email'     => $email,
        'phone'     => $phone,
        'topic'     => $topic,
        'message'   => $message,
      ], $phpmailerLoaded);

      if ($ok) {
        $success = true;
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
              <div class="col-md-6">
                <div class="form-floating">
                  <!-- Last name -->
                  <input type="text" id="lastName" name="lastName"
                    class="form-control <?= invalidClass($errors, 'lastName') ?>" placeholder="Last name" required
                    maxlength="80" autocomplete="family-name" value="<?= old('lastName') ?>" />
                  <label for="lastName">Last name</label>
                  <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['lastName'] ?? 'Please enter your last name.', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              </div>

              <div class="col-12">
                <div class="form-floating">
                  <!-- Email -->
                  <input type="email" id="email" name="email" class="form-control <?= invalidClass($errors, 'email') ?>"
                    placeholder="name@example.com" required autocomplete="email" inputmode="email"
                    value="<?= old('email') ?>" />
                  <label for="email">Email</label>
                  <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['email'] ?? 'Please enter a valid email address.', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating">
                  <!-- Phone (PH 11 digits) -->
                  <input type="tel" id="phone" name="phone" class="form-control <?= invalidClass($errors, 'phone') ?>"
                    placeholder="09XXXXXXXXX" autocomplete="tel-national" inputmode="tel" pattern="^\d{11}$"
                    minlength="11" maxlength="11" value="<?= old('phone') ?>"
                    oninput="this.value = this.value.replace(/\D/g, '').slice(0, 11)" />
                  <label for="phone">Phone (optional)</label>
                  <div class="invalid-feedback">
                    <?= htmlspecialchars($errors['phone'] ?? 'Please enter an 11-digit phone number.', ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating">
                  <select id="topic" name="topic" class="form-select <?= invalidClass($errors, 'topic') ?>" required>
                    <?php
                      foreach ($ALLOWED_TOPICS as $t) {
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
                  <textarea id="message" name="message" class="form-control <?= invalidClass($errors, 'message') ?>"
                    placeholder="Your message" style="height: 140px" required
                    maxlength="<?= (int)$CONFIG['max_message'] ?>"><?= old('message') ?></textarea>
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
                    <input class="form-check-input <?= invalidClass($errors, 'consent') ?>" type="checkbox" value="1"
                      id="consent" name="consent" <?= isset($_POST['consent']) ? 'checked' : '' ?> required />
                    <label class="form-check-label" for="consent">
                      <a>I agree to the privacy policy. </a>
                    </label>
                    <div class="invalid-feedback">
                      <?= htmlspecialchars($errors['consent'] ?? 'Consent is required.', ENT_QUOTES, 'UTF-8') ?></div>
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
            <a href="tel:+639000000000" class="link-secondary">+63 900 000 0000</a>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact / Map -->
  <section id="contact" class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-4">
        <h2 class="fw-bold mb-1">Contact and Location</h2>
        <p class="text-muted mb-0">Visit our office or reach us using the details below</p>
      </div>

      <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="row g-0">

          <!-- Map -->
          <div class="col-lg-7">
            <iframe style="width:100%; height:100%; min-height:420px; border:0;"
              src="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT&output=embed"
              loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen>
            </iframe>
          </div>

          <!-- Info -->
          <div class="col-lg-5 bg-white">
            <div class="p-4 p-md-5 h-100 d-flex flex-column justify-content-center">

              <div class="d-flex align-items-center gap-2 mb-3">
                <span class="badg
                <div class="text-danger fs-5"><i class="fa-solid fa-envelope"></i></div>
                <div>
                  <div class="fw-semibold">Email</div>
                  <div class="text-muted small">csnkmanila06@gmail.com</div>
                </div>);
    const counterEl = document.getElementById('charCount');
    const limit = parseInt(messageEl?.getAttribute('maxlength') || '500', 10);
    function updateCounter() {
      const len = [...(messageEl.value || '')].length;
      counterEl.textContent = `${len}
      })();
    </script>
  <?php endif; ?>
</body>
</html>