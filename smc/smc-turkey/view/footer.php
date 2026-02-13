<footer class="bg-white border-top">
  <div class="container py-4">
    <div class="row g-4 align-items-start">

      <!-- Logo + Social -->
      <div class="col-md-6">
        <img src="../resources/img/smcbrandname.png" alt="SMC Logo" style="height:90px;">
        <div class="mt-3 d-flex gap-3">
          <a href="https://www.facebook.com/smc.welfare/" target="_blank" class="text-danger fs-5" rel="noopener">
            <i class="fa-brands fa-facebook"></i>
          </a>
          <a href="https://smcphilippines.com/" target="_blank" class="text-danger fs-5" rel="noopener">
            <i class="fa-brands fa-instagram"></i>
          </a>
          <a href="https://twitter.com/smc_dummy" target="_blank" class="text-danger fs-5" rel="noopener">
            <i class="fa-brands fa-twitter"></i>
          </a>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="col-md-6">
        <ul class="list-unstyled text-muted mb-0">
          <li class="mb-2">
            <i class="fa-solid fa-location-dot text-danger me-2"></i>
            Unit 1 Eden Townhomes 2001 Eden Street corner Pedro Gil Street Sta. Ana Manila., 1009
          </li>
          <li class="mb-2">
            <i class="fa-solid fa-phone text-danger me-2"></i>
            0939 342 7412
          </li>
          <li class="mb-2">
            <i class="fa-solid fa-envelope text-danger me-2"></i>
            smcwelfaremonitoring@gmail.com
          </li>
          <li class="mb-0">
            <i class="fa-solid fa-clock text-danger me-2"></i>
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
        <a class="text-muted text-decoration-none me-3" href="legal-notice.php" data-bs-toggle="modal" data-bs-target="#legalModal">Legal Notice</a>
        <a class="text-muted text-decoration-none me-3" href="privacy-policy.php" data-bs-toggle="modal" data-bs-target="#privacyModal">Privacy Policy</a>
      </div>
    </div>
  </div>
</footer>

<!-- Legal Notice Modal -->
<div class="modal fade" id="legalModal" tabindex="-1" aria-labelledby="legalModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="legalModalLabel">Legal Notice</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <h6>SMC Manpower Agency Philippines Co.</h6>
        <p class="small text-muted mb-3">Last Updated: February 2026</p>
        
        <h6 class="mt-3">1. Website Operator</h6>
        <p class="small">
          <strong>Company Name:</strong> SMC Manpower Agency Philippines Co.<br>
          <strong>Address:</strong> Unit 1 Eden Townhomes 2001 Eden Street corner Pedro Gil Street Sta. Ana Manila., 1009<br>
          <strong>Email:</strong> smcwelfaremonitoring@gmail.com<br>
          <strong>Phone:</strong> 0939 342 7412
        </p>

        <h6>2. Terms of Use</h6>
        <p class="small">
          By accessing and using this website, you accept and agree to be bound by the terms and provision of this agreement.
        </p>

        <h6>3. Disclaimer</h6>
        <p class="small">
          The materials on our website are provided on an 'as is' basis. SMC Manpower Agency Philippines Co. makes no warranties and hereby disclaims 
          all warranties including implied warranties of merchantability and fitness for a particular purpose.
        </p>

        <h6>4. Employment Services</h6>
        <p class="small">
          SMC Manpower Agency Philippines Co. acts as a placement service. We do not guarantee employment and are not responsible for employment disputes, 
          wage issues, or workplace matters between candidates and employers.
        </p>

        <h6>5. Intellectual Property</h6>
        <p class="small">
          All content on our website is protected by international copyright laws. Unauthorized reproduction is prohibited.
        </p>

        <p class="small mt-3">
          <a href="legal-notice.php" class="text-danger">View Full Legal Notice →</a>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="legal-notice.php" class="btn btn-danger">View Full Page</a>
      </div>
    </div>
  </div>
</div>

<!-- Privacy Policy Modal -->
<div class="modal fade" id="privacyModal" tabindex="-1" aria-labelledby="privacyModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="privacyModalLabel">Privacy Policy</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">Last Updated: February 2026</p>
        
        <h6>1. Introduction</h6>
        <p class="small">
          SMC Manpower Agency Philippines Co. is committed to protecting your privacy. This Privacy Policy explains how we collect, use, 
          disclose, and safeguard your information.
        </p>

        <h6>2. Information We Collect</h6>
        <p class="small">
          <ul class="small">
            <li><strong>Personal Information:</strong> Name, email, phone, address, and contact details</li>
            <li><strong>Application Data:</strong> Employment history, qualifications, and documents</li>
            <li><strong>Technical Information:</strong> IP address, browser type, pages visited</li>
            <li><strong>Cookies:</strong> For enhancing your experience</li>
          </ul>
        </p>

        <h6>3. Use of Information</h6>
        <p class="small">
          We use collected information to: process applications, provide services, respond to inquiries, 
          improve our website, and comply with legal obligations.
        </p>

        <h6>4. Security</h6>
        <p class="small">
          We implement SSL encryption, password protection, and regular security audits to protect your information. 
          However, no method is completely secure.
        </p>

        <h6>5. Your Rights</h6>
        <p class="small">
          You have rights to access, correct, delete, and port your personal data, subject to legal requirements.
        </p>

        <p class="small mt-3">
          <a href="privacy-policy.php" class="text-danger">View Full Privacy Policy →</a>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <a href="privacy-policy.php" class="btn btn-danger">View Full Page</a>
      </div>
    </div>
  </div>
</div>