<?php
// contact.php
declare(strict_types=1);
session_start();

// Load PHPMailer
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// --------------------------------------------------
// Configuration
// --------------------------------------------------
$CONFIG = [
    'to_email'      => 'CSNKSupport@gmail.com',      // <-- CHANGE: destination email
    'from_email'    => 'CSNKno-reply@gmail.com',     // <-- CHANGE: Gmail address (if using Gmail SMTP)
    'from_name'     => 'CSNK Manpower Agency',       // <-- CHANGE: from name
    'subject'       => 'CSNK Contact Form Submission', // <-- CHANGE: email subject
    'max_message'   => 500,
    
    // PHPMailer Configuration
    'smtp_host'     => 'smtp.gmail.com',             // <-- CHANGE: SMTP host (gmail.com for Gmail)
    'smtp_port'     => 587,                          // <-- CHANGE: SMTP port (587 for TLS)
    'smtp_user'     => '@gmail',     // <-- CHANGE: Gmail address
    'smtp_pass'     => '',    // <-- CHANGE: Gmail App Password (NOT regular password!)
    'smtp_encrypt'  => PHPMailer::ENCRYPTION_STARTTLS, // TLS encryption
    'enable_mail'   => true,                         // set false to skip email while testing
];

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
    } else {
        // Gather & sanitize
        $firstName = clean($_POST['firstName'] ?? '');
        $lastName  = clean($_POST['lastName'] ?? '');
        $email     = clean($_POST['email'] ?? '');
        $phone     = clean($_POST['phone'] ?? '');
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
        if ($phone !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid phone number.';
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
            $ip      = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $agent   = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $time    = date('Y-m-d H:i:s O');

            $body = "You have a new contact form submission:\n\n"
                . "Time: $time\n"
                . "IP: $ip\n"
                . "User-Agent: $agent\n\n"
                . "Name: $firstName $lastName\n"
                . "Email: $email\n"
                . "Phone: $phone\n"
                . "Topic: $topic\n\n"
                . "Message:\n$message\n";

            if ($CONFIG['enable_mail']) {
                try {
                    $mail = new PHPMailer(true);
                    
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = $CONFIG['smtp_host'];
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $CONFIG['smtp_user'];
                    $mail->Password   = $CONFIG['smtp_pass'];
                    $mail->SMTPSecure = $CONFIG['smtp_encrypt'];
                    $mail->Port       = $CONFIG['smtp_port'];
                    
                    // Recipients: send to company support and a copy to the client
                    $mail->setFrom($CONFIG['from_email'], $CONFIG['from_name']);
                    // Primary recipient: company support
                    $mail->addAddress($CONFIG['to_email']);
                    // Also send a copy to the client who submitted the form
                    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                      $mail->addAddress($email);
                    }
                    // Keep Reply-To set to the client so support can reply directly
                    $mail->addReplyTo($email, "$firstName $lastName");
                    
                    // Content
                    $mail->isHTML(false);
                    $mail->Subject = $CONFIG['subject'];
                    $mail->Body    = $body;
                    
                    // Send
                    $mail->send();
                    
                    $success = true;
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate token
                    $_POST = []; // clear form
                    
                } catch (Exception $e) {
                    $errors['general'] = 'We could not send your message right now. Please try again later.';
                    // Optionally log the error for debugging: error_log("Mail Error: {$mail->ErrorInfo}");
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
  <title >Contact Us</title>

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    :root{
      --accent-red: #D72638;      /* red hint */
      --ink: #111111;             /* black hint */
      --muted-ink: #6c757d;       /* Bootstrap gray-600 */
      --bg: #ffffff;              /* white */
      --border: #e9ecef;
      --ring: rgba(215, 38, 56, .25); /* red focus ring */
    }
    body {
      background: var(--bg);
      color: var(--ink);
      font-feature-settings: "kern" 1, "liga" 1;
    }
    .site-header {
      border-bottom: 1px solid var(--border);
      background: #fff;
    }
    .brand-dot {
      width: .65rem; height: .65rem; border-radius: 50%;
      background: var(--accent-red); display: inline-block; margin-left: .35rem;
    }
    .nav-link { color: var(--ink); }
    .nav-link.active, .nav-link:hover, .nav-link:focus { color: var(--accent-red); }

    .contact-card {
      border: 1px solid var(--border);
      border-radius: 14px;
      box-shadow: 0 6px 28px rgba(17,17,17, .04);
      background: #fff;
    }

    .form-control, .form-select {
      border-radius: 10px;
      border-color: var(--border);
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--accent-red);
      box-shadow: 0 0 0 .25rem var(--ring);
    }
    .form-check-input:checked {
      background-color: var(--accent-red);
      border-color: var(--accent-red);
    }

    .btn-accent {
      --bs-btn-color: #fff;
      --bs-btn-bg: var(--accent-red);
      --bs-btn-border-color: var(--accent-red);
      --bs-btn-hover-bg: #b81f2f;
      --bs-btn-hover-border-color: #b81f2f;
      --bs-btn-focus-shadow-rgb: 215, 38, 56;
    }
    .text-accent { color: var(--accent-red) !important; }
    .divider { height: 1px; background: var(--border); }

    .char-counter { font-size: .85rem; color: var(--muted-ink); }
    .char-counter.warning { color: var(--accent-red); font-weight: 600; }

    .site-footer {
      border-top: 1px solid var(--border);
      color: var(--muted-ink);
    }

    .is-invalid ~ .invalid-feedback { display: block; }
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
        <h1 class="display-6 fw-bold mb-3 ">Contact <span class="text-accent">CSNK </span>Support</h1>
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
                  
                <!-- Phone (PH 11 digits, digits-only UI help) -->
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
                  <div class="invalid-feedback"><?= htmlspecialchars($errors['phone'] ?? 'Please enter a valid phone number.', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-floating">
                  <select id="topic" name="topic" class="form-select <?= invalidClass($errors, 'topic') ?>" required>
                <?php
                  $topics = ['General Inquiry', 'Support', 'Sales', 'Partnerships'];
                    foreach ($topics as $t) {
                  $sel = (old('topic') === $t) ? 'selected' : '';
                  echo '<option '.$sel.'>'.htmlspecialchars($t, ENT_QUOTES, 'UTF-8').'</option>';
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
                <span class="badge bg-danger rounded-pill px-3 py-2">CSNK Manpower Agency</span>
              </div>

              <h5 class="fw-bold mb-3">Office Information</h5>

              <div class="d-flex gap-3 mb-3">
                <div class="text-danger fs-5"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                  <div class="fw-semibold">Address</div>
                  <div class="text-muted small">
                    Ground Floor Unit 1 Eden Townhouse<br>
                    2001 Eden St. Cor Pedro Gil, Sta Ana<br>
                    Barangay 866, City of Manila, NCR, Sixth District
                  </div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-danger fs-5"><i class="fa-solid fa-phone"></i></div>
                <div>
                  <div class="fw-semibold">Phone</div>
                  <div class="text-muted small">0945 657 0878</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-danger fs-5"><i class="fa-solid fa-envelope"></i></div>
                <div>
                  <div class="fw-semibold">Email</div>
                  <div class="text-muted small">csnkmanila06@gmail.com</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-4">
                <div class="text-danger fs-5"><i class="fa-solid fa-clock"></i></div>
                <div>
                  <div class="fw-semibold">Office Hours</div>
                  <div class="text-muted small">Mon to Sat, 8:00 AM to 5:00 PM</div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-danger rounded-pill px-4"
                   target="_blank" rel="noopener"
                   href="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT">
                  <i class="fa-solid fa-location-arrow me-2"></i>Get Directions
                </a>

                <a class="btn btn-outline-secondary rounded-pill px-4" href="#home">
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

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"> </script>
  <!-- Policy Modals Handler -->
  <script src="../resources/js/policy-modals.js"></script>

  <script>

    // Character counter
    const messageEl = document.getElementById('message');
    const counterEl = document.getElementById('charCount');
    const limit = parseInt(messageEl?.getAttribute('maxlength') || '500', 10);

    function updateCounter(){
      const len = messageEl.value.length;
      counterEl.textContent = `${len} / ${limit}`;
      const threshold = Math.floor(limit * 0.9);
      counterEl.classList.toggle('warning', len >= threshold);
    }
    if (messageEl) {
      messageEl.addEventListener('input', updateCounter);
      updateCounter();
    }

    // Submit button loading UI (client-side)
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

  <script>
  //message counter
  (function () {
  const textarea = document.getElementById('message');
  const counter  = document.getElementById('charCount');

  if (!textarea || !counter) return;

  // Use the element's own maxlength so it's always in sync with PHP config.
  const LIMIT = textarea.maxLength > 0 ? textarea.maxLength : Infinity;

  // Track composition so we don't disrupt IME input
  let isComposing = false;
  textarea.addEventListener('compositionstart', () => { isComposing = true; });
  textarea.addEventListener('compositionend',   () => { 
    isComposing = false; 
    enforceLimitAndUpdate();
  });

  // Count characters as Unicode code points (handles emoji properly)
  function lengthOf(str) {
    return Array.from(str).length;
  }

  function sliceToLimit(str, limit) {
    return Array.from(str).slice(0, limit).join('');
  }

  function updateCounterDisplay(len, limit) {
    // “used / limit”, same as your UI: 123 / 500
    counter.textContent = `${len} / ${limit}`;
  }

  function enforceLimitAndUpdate() {
    if (isComposing) return; // wait until IME finishes

    const value = textarea.value || '';
    const len = lengthOf(value);

    if (len > LIMIT) {
      const start = textarea.selectionStart;
      const end   = textarea.selectionEnd;
      const beforeLength = value.length;

      textarea.value = sliceToLimit(value, LIMIT);

      // Try to keep selection/caret in a sensible spot
      const afterLength = textarea.value.length;
      const delta = beforeLength - afterLength;
      if (typeof start === 'number' && typeof end === 'number') {
        const newStart = Math.max(0, start - delta);
        const newEnd   = Math.max(0, end - delta);
        textarea.setSelectionRange(newStart, newEnd);
      }
    }

    updateCounterDisplay(lengthOf(textarea.value), LIMIT);
  }

  // Update on any input changes
  textarea.addEventListener('input', enforceLimitAndUpdate);

  // In some browsers, setting value programmatically or autofill may not fire input
  // Use MutationObserver as a fallback to stay in sync
  const mo = new MutationObserver(enforceLimitAndUpdate);
  mo.observe(textarea, { attributes: true, attributeFilter: ['value'] });

  // Initialize on page load (includes prefilled old('message'))
  enforceLimitAndUpdate();
})();
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
