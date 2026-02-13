<footer class="bg-white border-top">
  <div class="container py-4">
    <div class="row g-4 align-items-start">

      <!-- Logo + Social -->
      <div class="col-md-6">
        <img src="../resources/img/smcbrandname.png" alt="SMC Logo" style="height:90px;">
        <div class="mt-3 d-flex gap-3">
          <a href="https://www.facebook.com/smc.welfare/" target="_blank" class="text-navy-dark fs-5" rel="noopener">
            <i class="fa-brands fa-facebook"></i>
          </a>
          <a href="https://smcphilippines.com/" target="_blank" class="text-navy-dark fs-5" rel="noopener">
            <i class="fa-brands fa-instagram"></i>
          </a>
          <a href="https://twitter.com/smc_dummy" target="_blank" class="text-navy-dark fs-5" rel="noopener">
            <i class="fa-brands fa-twitter"></i>
          </a>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="col-md-6">
        <ul class="list-unstyled text-muted mb-0">
          <li class="mb-2">
            <i class="fa-solid fa-location-dot text-navy-dark me-2"></i>
            Unit 1 Eden Townhomes 2001 Eden Street corner Pedro Gil Street Sta. Ana Manila., 1009
          </li>
          <li class="mb-2">
            <i class="fa-solid fa-phone text-navy-dark me-2"></i>
            0916 247 2721
          </li>
          <li class="mb-2">
            <i class="fa-solid fa-envelope text-navy-dark me-2"></i>
            smcphilippines.marketing@gmail.com
          </li>
          <li class="mb-0">
            <i class="fa-solid fa-clock text-navy-dark me-2"></i>
            Mon - Sat: 8:00 AM - 5:00 PM
          </li>
        </ul>
      </div>

    </div>

    <hr class="my-4">

    <!-- Bottom -->
    <div class="text-center small text-muted">
      <div>Copyright © <?= date('Y'); ?> SMC Manpower Agency Philippines Co. All Rights Reserved.</div>
      <div class="mt-2">
        <a class="text-muted text-decoration-none me-3 link-navy" href="legal-notice.php" data-bs-toggle="modal" data-bs-target="#legalModal">Legal Notice</a>
        <a class="text-muted text-decoration-none me-3 link-navy" href="privacy-policy.php" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
      </div>
    </div>
  </div>
</footer>

<!-- Legal Notice Modal -->
<div class="modal fade" id="legalModal" tabindex="-1" aria-labelledby="legalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-navy-dark text-white">
        <h5 class="modal-title" id="legalModalLabel">Legal Notice</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6 class="text-navy-dark">SMC Manpower Agency Philippines Co.</h6>
        <p class="small text-muted mb-3">Last Updated: February 2026</p>

        <h6 class="mt-3 text-navy-dark">1. Website Operator</h6>
        <p class="small">
          <strong>Company Name:</strong> SMC Manpower Agency Philippines Co.<br>
          <strong>Address:</strong> Unit 1 Eden Townhomes, Pedro Gil St., Sta. Ana, Manila<br>
          <strong>Email:</strong> smcphilippines.marketing@gmail.com<br>
          <strong>Phone:</strong> 0916 247 2721
        </p>

        <h6 class="text-navy-dark">2. Terms of Use</h6>
        <p class="small">By using this website, you agree to all terms and conditions.</p>

        <h6 class="text-navy-dark">3. Disclaimer</h6>
        <p class="small">All content is provided “as is” without warranties.</p>

        <h6 class="text-navy-dark">4. Employment Services</h6>
        <p class="small">SMC acts as a placement agency; employment outcomes are not guaranteed.</p>

        <h6 class="text-navy-dark">5. Intellectual Property</h6>
        <p class="small">All website content is protected by copyright laws.</p>

        <p class="small mt-3">
          <a href="legal-notice.php" class="text-navy-dark fw-semibold">View Full Legal Notice →</a>
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-navy-dark" data-bs-dismiss="modal">Close</button>
        <a href="legal-notice.php" class="btn btn-navy-dark">View Full Page</a>
      </div>
    </div>
  </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-navy-dark text-white">
        <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">Last Updated: February 2026</p>

        <h6 class="text-navy-dark">1. Introduction</h6>
        <p class="small">We are committed to protecting your privacy.</p>

        <h6 class="text-navy-dark">2. Information We Collect</h6>
        <ul class="small">
          <li>Personal Info</li>
          <li>Application Data</li>
          <li>Technical Data</li>
          <li>Cookies</li>
        </ul>

        <h6 class="text-navy-dark">3. Use of Information</h6>
        <p class="small">We use data to improve services, process applications, and comply with legal requirements.</p>

        <h6 class="text-navy-dark">4. Security</h6>
        <p class="small">We use encryption and safeguards, but no system is perfect.</p>

        <h6 class="text-navy-dark">5. Your Rights</h6>
        <p class="small">You can request access, edits, or deletion of your data.</p>

        <p class="small mt-3">
          <a href="privacy-policy.php" class="text-navy-dark fw-semibold">View Full Privacy Policy →</a>
        </p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-navy-dark" data-bs-dismiss="modal">Close</button>
        <a href="privacy-policy.php" class="btn btn-navy-dark">View Full Page</a>
      </div>
    </div>
  </div>
</div>

<style>
  :root{
    --smc-navy-dark: #07162A; /* darker navy */
    --smc-navy: #0B1F3A;
    --smc-gold: #FFD84D;
  }

  .text-navy-dark { color: var(--smc-navy-dark) !important; }
  .bg-navy-dark { background: var(--smc-navy-dark) !important; color:#fff; }

  .btn-navy-dark {
    background: linear-gradient(180deg, #0C223D, var(--smc-navy-dark));
    color:#fff;
    border-radius:999px;
    border:0;
    padding:.6rem 1.2rem;
    font-weight:700;
  }
  .btn-navy-dark:hover { filter:brightness(1.05); color:#fff; }

  .btn-outline-navy-dark {
    border:2px solid var(--smc-navy-dark);
    color: var(--smc-navy-dark);
    border-radius:999px;
    padding:.55rem 1.15rem;
    font-weight:700;
  }
  .btn-outline-navy-dark:hover {
    background: var(--smc-navy-dark);
    color:#fff;
  }

  .link-navy {
    color: var(--smc-navy-dark);
  }
  .link-navy:hover {
    color: #0E2A4C;
  }
</style>