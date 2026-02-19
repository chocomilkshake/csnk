<?php
session_start();
?>
<!doctype html>
<html lang="en" data-bs-theme="light">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Legal Notice - CSNK Manpower Agency</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="/csnk/resources/img/csnk-icon.png">
  <link rel="apple-touch-icon" href="/csnk/resources/img/favicons/apple-touch-icon-180.png">
  <link rel="icon" href="/csnk/resources/img/csnk-icon.ico">
</head>

<body class="bg-light text-body">
  <!-- Header -->
  <header>
    <?php $page = 'legal'; include __DIR__ . '/navbar.php'; ?>
  </header>

  <!-- Hero Section -->
  <section class="bg-white border-bottom">
    <div class="container">
      <div class="row align-items-center gy-3 py-4">
        <div class="col">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-2">
              <li class="breadcrumb-item"><a href="/" class="text-decoration-none">Home</a></li>
              <li class="breadcrumb-item active">Legal Notice</li>
            </ol>
          </nav>

          <div class="d-flex align-items-center gap-3 flex-wrap">
            <h1 class="h2 mb-0 text-danger">Legal Notice</h1>
            <span class="badge text-bg-secondary">Last Updated: February 2026</span>
          </div>

          <p class="text-secondary mt-2 mb-0">
            Important legal information regarding the use of CSNK Manpower Agency's website and services.
          </p>
        </div>

        <div class="col-auto d-none d-md-block">
          <i class="fa-solid fa-scale-balanced text-danger fs-1"></i>
        </div>
      </div>
    </div>
  </section>

  <!-- Main Content -->
  <main class="container py-4 py-md-5">
    <div class="card shadow-sm border-0">
      <div class="card-body p-3 p-md-4 p-lg-5">

        <!-- Summary -->
        <div class="alert alert-light border mb-4 d-flex gap-3">
          <i class="fa-solid fa-circle-info text-danger pt-1"></i>
          <div>
            <div class="fw-semibold">Important Notice</div>
            <div class="mt-1">
              This section outlines the legal terms, disclaimers, responsibilities, and limitations governing
              your use of our website and services.
            </div>
          </div>
        </div>

        <!-- Accordion -->
        <div class="accordion" id="legalAccordion">

          <!-- 1 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="heading1">
              <button class="accordion-button fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse1" aria-expanded="true">
                1. Website Operator
              </button>
            </h2>
            <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#legalAccordion">
              <div class="accordion-body">
                <p class="mb-2"><strong>Company Name:</strong> CSNK Manpower Agency</p>
                <p class="mb-2"><strong>Address:</strong> Ground Floor Unit 1 Eden Townhouse, 2001 Eden St. Cor Pedro Gil, Sta Ana, Barangay 866, City of Manila, NCR</p>
                <p class="mb-2"><strong>Email:</strong> csnkmanila06@gmail.com</p>
                <p class="mb-0"><strong>Phone:</strong> 0945 657 0878</p>
              </div>
            </div>
          </div>

          <!-- 2 -->
          <div class="accordion-item">
            <h2 class="accordion-header" id="heading2">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse2">
                2. Terms of Use
              </button>
            </h2>
            <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#legalAccordion">
              <div class="accordion-body">
                By using this website, you agree to comply with the terms and conditions outlined on this page.
                If you do not accept these terms, you must discontinue use of the website.
              </div>
            </div>
          </div>

          <!-- 3 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse3">
                3. Use License
              </button>
            </h2>
            <div id="collapse3" class="accordion-collapse collapse">
              <div class="accordion-body">
                <p class="mb-2">You may temporarily download materials for non-commercial, personal viewing only. Under this license you may not:</p>
                <ul>
                  <li>Modify or copy materials</li>
                  <li>Use materials for commercial/public display</li>
                  <li>Reverse engineer software</li>
                  <li>Remove copyright notices</li>
                  <li>Transfer content or mirror it to other servers</li>
                  <li>Use automated tools to access data without permission</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 4 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse4">
                4. Disclaimer
              </button>
            </h2>
            <div id="collapse4" class="accordion-collapse collapse">
              <div class="accordion-body">
                All materials on this website are provided “as is” without any expressed or implied warranties,
                including merchantability, fitness for purpose, or non‑infringement.
              </div>
            </div>
          </div>

          <!-- 5 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse5">
                5. Limitations
              </button>
            </h2>
            <div id="collapse5" class="accordion-collapse collapse">
              <div class="accordion-body">
                CSNK Manpower Agency shall not be held liable for damages resulting from the use or inability to use the materials on the website.
              </div>
            </div>
          </div>

          <!-- 6 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse6">
                6. Accuracy of Materials
              </button>
            </h2>
            <div id="collapse6" class="accordion-collapse collapse">
              <div class="accordion-body">
                The website may contain technical, typographical, or photographic errors. Information may change at any time without notice.
              </div>
            </div>
          </div>

          <!-- 7 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse7">
                7. Links
              </button>
            </h2>
            <div id="collapse7" class="accordion-collapse collapse">
              <div class="accordion-body">
                External links are not reviewed by CSNK Manpower Agency. We are not responsible for the content of external websites.
              </div>
            </div>
          </div>

          <!-- 8 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse8">
                8. Modifications
              </button>
            </h2>
            <div id="collapse8" class="accordion-collapse collapse">
              <div class="accordion-body">
                CSNK Manpower Agency may revise its terms of service at any time. Continued website use constitutes agreement to the updated terms.
              </div>
            </div>
          </div>

          <!-- 9 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse9">
                9. Governing Law
              </button>
            </h2>
            <div id="collapse9" class="accordion-collapse collapse">
              <div class="accordion-body">
                These terms are governed by the laws of the Republic of the Philippines.
              </div>
            </div>
          </div>

          <!-- 10 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse10">
                10. Employment Services Disclaimer
              </button>
            </h2>
            <div id="collapse10" class="accordion-collapse collapse">
              <div class="accordion-body">
                <p class="mb-2">CSNK Manpower Agency acts only as a placement intermediary. We are not liable for:</p>
                <ul>
                  <li>Employer‑employee disputes</li>
                  <li>Salary/wage issues</li>
                  <li>Workplace safety concerns</li>
                  <li>Employment-related financial issues</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- 11 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse11">
                11. Data Protection
              </button>
            </h2>
            <div id="collapse11" class="accordion-collapse collapse">
              <div class="accordion-body">
                We comply with applicable data protection laws. See our Privacy Policy for details.
              </div>
            </div>
          </div>

          <!-- 12 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse12">
                12. Intellectual Property Rights
              </button>
            </h2>
            <div id="collapse12" class="accordion-collapse collapse">
              <div class="accordion-body">
                All content on this website is owned by CSNK Manpower Agency and protected by copyright laws.
              </div>
            </div>
          </div>

          <!-- 13 -->
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapse13">
                13. Contact Information
              </button>
            </h2>
            <div id="collapse13" class="accordion-collapse collapse">
              <div class="accordion-body">
                <ul class="list-unstyled mb-0">
                  <li class="mb-2"><i class="fa-solid fa-envelope text-danger me-2"></i> csnkmanila06@gmail.com</li>
                  <li class="mb-2"><i class="fa-solid fa-phone text-danger me-2"></i> 0945 657 0878</li>
                  <li><i class="fa-solid fa-location-dot text-danger me-2"></i> Ground Floor Unit 1 Eden Townhouse, 2001 Eden St., Sta Ana, Manila</li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Back to Top -->
        <div class="d-flex justify-content-end mt-4">
          <a href="#" class="text-decoration-none">
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