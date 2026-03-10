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
                </td>    body{ background:var(--bg); " value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="row g-3">
              <div class="col-md-6">
                <div class="form-floating">
                  <!-- First name -->
                  <input type="
                  <select id="harCount');
    const limit = parseInt(messageEl?.getAttribute('maxlength') || '500', 10);
    function updateCounter() {
      const len = [...(messageEl.value || '')].length;
      counterEl.textContent = `${len}
      })();
    </script>
  <?php endif; ?>
</body>
</html>