<?php
session_start();
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Privacy Policy - CSNK Manpower Agency</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <!-- Favicons (concise & correct) -->
  <link rel="icon" type="image/png" href="/csnk/resources/img/csnk-icon.png" />
  <link rel="icon" href="/csnk/resources/img/csnk-icon.ico" sizes="16x16 32x32 48x48 64x64" />
  <link rel="apple-touch-icon" href="/csnk/resources/img/favicons/apple-touch-icon-180.png" />
</head>
<body class="bg-light text-body">
  <!-- Header / Navbar -->
  <header>
    <?php $page = 'privacy'; include __DIR__ . '/navbar.php'; ?>
  </header>

  <!-- Hero -->
  <section class="bg-white border-bottom">
    <div class="container">
      <div class="row align-items-center gy-3 py-4">
        <div class="col">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
              <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Home</a></li>
              <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
            </ol>
          </nav>
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <h1 class="h2 mb-0 text-danger">Privacy Policy</h1>
            <span class="badge text-bg-secondary">Last Updated: February 2026</span>
          </div>
          <p class="text-secondary mb-0 mt-2">CSNK Manpower Agency is committed to protecting your privacy and handling your data responsibly.</p>
        </div>
        <div class="col-auto d-none d-md-block">
          <i class="fa-solid fa-shield-halved text-danger fs-1"></i>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <main class="container py-4 py-md-5">
    <div class="card shadow-sm border-0">
      <div class="card-body p-3 p-md-4 p-lg-5">

        <!-- Quick summary callout -->
        <div class="alert alert-light border d-flex align-items-start gap-3 mb-4">
          <i class="fa-solid fa-circle-info text-danger pt-1"></i>
          <div>
            <div class="fw-semibold">At a glance</div>
            <ul class="mb-0 ps-3">
              <li>We collect personal, application, and technical information to deliver our services.</li>
              <li>We don’t sell your data. We share it only with service providers, potential employers, or when required by law.</li>
              <li>You have rights to access, correct, delete, and port your data, subject to legal requirements.</li>
            </ul>
          </div>
        </div>

        <!-- Policy Sections in Accordion -->
        <div class="accordion" id="policyAccordion">
          <!-- 1 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingIntro">
              <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseIntro" aria-expanded="true" aria-controls="collapseIntro">
                1. Introduction
              </button>
            </h2>
            <div id="collapseIntro" class="accordion-collapse collapse show" aria-labelledby="headingIntro" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                CSNK Manpower Agency (“Company,” “we,” “us,” or “our”) is committed to protecting your privacy.
                This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you
                visit our website and use our services.
              </div>
            </div>
          </div>

          <!-- 2 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingCollect">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCollect" aria-expanded="false" aria-controls="collapseCollect">
                2. Information We Collect
              </button>
            </h2>
            <div id="collapseCollect" class="accordion-collapse collapse" aria-labelledby="headingCollect" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                <p class="mb-2">We may collect information about you in a variety of ways, including:</p>
                <ul class="mb-0">
                  <li><span class="fw-semibold">Personal Information:</span> Name, email address, phone number, mailing address, and other contact details.</li>
                  <li><span class="fw-semibold">Application Data:</span> Employment history, qualifications, work experience, and applicant documents.</li>
                  <li><span class="fw-semibold">Technical Information:</span> IP address, browser type, operating system, pages visited, and time spent on pages.</li>
                  <li><span class="fw-semibold">Cookies:</span> We use cookies to enhance your experience and understand site usage patterns.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 3 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingUse">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseUse" aria-expanded="false" aria-controls="collapseUse">
                3. Use of Your Information
              </button>
            </h2>
            <div id="collapseUse" class="accordion-collapse collapse" aria-labelledby="headingUse" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                <p class="mb-2">We use the information we collect for the following purposes:</p>
                <ul class="mb-0">
                  <li>Processing your job applications and communications</li>
                  <li>Providing our manpower services and connecting you with employment opportunities</li>
                  <li>Responding to your inquiries and customer support requests</li>
                  <li>Sending updates, newsletters, and promotional materials (with your consent)</li>
                  <li>Improving our website and services based on feedback and analytics</li>
                  <li>Protecting against fraud, abuse, and unauthorized access</li>
                  <li>Complying with legal obligations and industry regulations</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 4 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingDisclosure">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDisclosure" aria-expanded="false" aria-controls="collapseDisclosure">
                4. Disclosure of Your Information
              </button>
            </h2>
            <div id="collapseDisclosure" class="accordion-collapse collapse" aria-labelledby="headingDisclosure" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                <p class="mb-2">We do not sell or rent your personal information. We may share information:</p>
                <ul class="mb-0">
                  <li><span class="fw-semibold">With Service Providers:</span> Vendors who assist in operating the website and conducting business.</li>
                  <li><span class="fw-semibold">With Potential Employers:</span> To support placement services, with your application data.</li>
                  <li><span class="fw-semibold">For Legal Compliance:</span> When required by law, court order, or government authority.</li>
                  <li><span class="fw-semibold">Business Transfers:</span> In a merger, acquisition, or asset sale.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 5 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingSecurity">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSecurity" aria-expanded="false" aria-controls="collapseSecurity">
                5. Security of Your Information
              </button>
            </h2>
            <div id="collapseSecurity" class="accordion-collapse collapse" aria-labelledby="headingSecurity" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                <p class="mb-2">We implement measures such as:</p>
                <ul>
                  <li>SSL encryption for data in transit</li>
                  <li>Password protection and secure authentication</li>
                  <li>Regular security reviews</li>
                  <li>Restricted access to personal information</li>
                </ul>
                <p class="mb-0">No method of transmission or storage is completely secure; we cannot guarantee absolute security.</p>
              </div>
            </div>
          </div>

          <!-- 6 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingRights">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseRights" aria-expanded="false" aria-controls="collapseRights">
                6. Your Privacy Rights
              </button>
            </h2>
            <div id="collapseRights" class="accordion-collapse collapse" aria-labelledby="headingRights" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                <ul class="mb-0">
                  <li><span class="fw-semibold">Right to Access:</span> Request a copy of your personal information.</li>
                  <li><span class="fw-semibold">Right to Rectification:</span> Request corrections to inaccurate or incomplete data.</li>
                  <li><span class="fw-semibold">Right to Erasure:</span> Request deletion, subject to legal requirements.</li>
                  <li><span class="fw-semibold">Right to Data Portability:</span> Request your data in a portable format.</li>
                  <li><span class="fw-semibold">Right to Withdraw Consent:</span> Opt out of marketing communications at any time.</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 7 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingCookies">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCookies" aria-expanded="false" aria-controls="collapseCookies">
                7. Cookies and Tracking Technologies
              </button>
            </h2>
            <div id="collapseCookies" class="accordion-collapse collapse" aria-labelledby="headingCookies" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                We use cookies to store preferences and understand usage patterns. Most browsers allow control over cookies in settings;
                disabling cookies may limit site functionality.
              </div>
            </div>
          </div>

          <!-- 8 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingThirdParty">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThirdParty" aria-expanded="false" aria-controls="collapseThirdParty">
                8. Third‑Party Links
              </button>
            </h2>
            <div id="collapseThirdParty" class="accordion-collapse collapse" aria-labelledby="headingThirdParty" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                Our website may link to external sites. We are not responsible for their privacy practices. Review their policies before sharing information.
              </div>
            </div>
          </div>

          <!-- 9 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingChildren">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChildren" aria-expanded="false" aria-controls="collapseChildren">
                9. Children’s Privacy
              </button>
            </h2>
            <div id="collapseChildren" class="accordion-collapse collapse" aria-labelledby="headingChildren" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                Our services are not intended for children under 18. If we learn we collected such data, we will delete it promptly.
              </div>
            </div>
          </div>

          <!-- 10 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingContact">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseContact" aria-expanded="false" aria-controls="collapseContact">
                10. Contact Us
              </button>
            </h2>
            <div id="collapseContact" class="accordion-collapse collapse" aria-labelledby="headingContact" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                <ul class="list-unstyled mb-0">
                  <li class="mb-2">
                    <i class="fa-solid fa-envelope text-danger me-2"></i>
                    <a href="mailto:csnkmanila06@gmail.com" class="link-dark link-underline-opacity-0 link-underline-opacity-100-hover">csnkmanila06@gmail.com</a>
                  </li>
                  <li class="mb-2">
                    <i class="fa-solid fa-phone text-danger me-2"></i>
                    <span>0945 657 0878</span>
                  </li>
                  <li>
                    <i class="fa-solid fa-location-dot text-danger me-2"></i>
                    <span>Ground Floor Unit 1 Eden Townhouse, 2001 Eden St. Cor Pedro Gil, Sta Ana, Barangay 866, City of Manila, NCR, Sixth District</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 11 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="headingChanges">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChanges" aria-expanded="false" aria-controls="collapseChanges">
                11. Policy Changes
              </button>
            </h2>
            <div id="collapseChanges" class="accordion-collapse collapse" aria-labelledby="headingChanges" data-bs-parent="#policyAccordion">
              <div class="accordion-body">
                We may update this Privacy Policy at any time. Changes are effective upon posting. Your continued use of the website
                constitutes acceptance of the updated policy.
              </div>
            </div>
          </div>
        </div>

        <!-- Back to top -->
        <div class="d-flex justify-content-end mt-4">
          <a href="#" class="btn btn-outline-danger">
            <i class="fa-solid fa-arrow-up me-2"></i>Back to top
          </a>
        </div>

      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="mt-auto">
    <?php include __DIR__ . '/footer.php'; ?>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>