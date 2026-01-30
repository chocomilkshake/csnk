<?php
  // Set the active page for navbar highlighting
  $page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>CSNK Manpower Agency</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ✅ Reusable Navbar -->
<?php include __DIR__ . '/navbar.php'; ?>


  <!-- ===================== -->
  <!-- Page Content Starts -->
  <!-- ===================== -->

  <!-- Hero -->
  <section id="home" class="py-5">
    <div class="container">
      <div class="p-4 p-lg-5 rounded-4 text-white shadow"
           style="background: linear-gradient(90deg, #dc3545, #111);">
        <div class="row align-items-center g-4">
          <div class="col-lg-6">
            <h1 class="display-6 fw-bold">Welcome to CSNK Manpower Agency</h1>
            <p class="lead mb-4">
              Your trusted partner for qualified housemaids and nannies. We connect families with properly screened domestic workers through safe and compliant processes.
            </p>

            <div class="d-flex gap-3 mb-3">
              <i class="fa-solid fa-circle-check fa-2x text-warning"></i>
              <div>
                <div class="fw-semibold">Professional Recruitment</div>
                <div class="small opacity-75">Expert matching and responsible placement</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-4">
              <i class="fa-solid fa-globe fa-2x text-warning"></i>
              <div>
                <div class="fw-semibold">Network & Support</div>
                <div class="small opacity-75">Guidance from screening to deployment</div>
              </div>
            </div>

            <a href="#about" class="btn btn-light btn-lg fw-semibold">Learn More</a>
          </div>

          <div class="col-lg-6">
            <img class="img-fluid rounded-4 shadow"
                 src="https://images.pexels.com/photos/3184292/pexels-photo-3184292.jpeg?auto=compress&cs=tinysrgb&w=900"
                 alt="Office">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Slideshow (Card Style Carousel - Auto Only, Same Heights) -->
  <section class="py-4 bg-light">
    <div class="container">
      <div id="csnkCarouselCards"
           class="carousel slide"
           data-bs-ride="carousel"
           data-bs-interval="4000"
           data-bs-touch="true"
           data-bs-pause="false">

        <!-- Dots / Indicators -->
        <div class="carousel-indicators">
          <button type="button" data-bs-target="#csnkCarouselCards" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
          <button type="button" data-bs-target="#csnkCarouselCards" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#csnkCarouselCards" data-bs-slide-to="2" aria-label="Slide 3"></button>
          <button type="button" data-bs-target="#csnkCarouselCards" data-bs-slide-to="3" aria-label="Slide 4"></button>
          <button type="button" data-bs-target="#csnkCarouselCards" data-bs-slide-to="4" aria-label="Slide 5"></button>
          <button type="button" data-bs-target="#csnkCarouselCards" data-bs-slide-to="5" aria-label="Slide 6"></button>
        </div>

        <div class="carousel-inner">

          <!-- Slide 1 -->
          <div class="carousel-item active">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero1.jpg"
                           class="w-100"
                           alt="CSNK Slide 1"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger rounded-pill align-self-start px-3 py-2">CSNK Manpower Agency</span>
                        <h3 class="fw-bold mt-3 mb-2">Trusted Domestic Recruitment</h3>
                        <p class="text-muted mb-4">Connecting families with screened housemaids and nannies through verified processes.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-circle-check text-danger fs-4"></i>
                          <div>
                            <div class="fw-semibold">Verified Processing</div>
                            <div class="small text-muted">Clear steps from start to finish</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#about" class="btn btn-danger rounded-pill px-4">
                            Learn More <i class="fa-solid fa-arrow-right ms-2"></i>
                          </a>
                          <a href="#contact" class="btn btn-outline-secondary rounded-pill px-4">Contact</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero2.jpg"
                           class="w-100"
                           alt="CSNK Slide 2"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger rounded-pill align-self-start px-3 py-2">Fast and Clear Process</span>
                        <h3 class="fw-bold mt-3 mb-2">Professional Processing</h3>
                        <p class="text-muted mb-4">We help applicants prepare requirements and complete each step efficiently.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-file-circle-check text-danger fs-4"></i>
                          <div>
                            <div class="fw-semibold">Requirements Support</div>
                            <div class="small text-muted">Checklists, updates, and assistance</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#contact" class="btn btn-danger rounded-pill px-4">
                            Contact Us <i class="fa-solid fa-phone ms-2"></i>
                          </a>
                          <a href="./applicant.php" class="btn btn-outline-secondary rounded-pill px-4">Applicants</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero3.jpg"
                           class="w-100"
                           alt="CSNK Slide 3"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger rounded-pill align-self-start px-3 py-2">Safety and Compliance</span>
                        <h3 class="fw-bold mt-3 mb-2">Verified and Secure</h3>
                        <p class="text-muted mb-4">We prioritize screening, verification, and proper documentation.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-shield-halved text-danger fs-4"></i>
                          <div>
                            <div class="fw-semibold">Safety First</div>
                            <div class="small text-muted">Compliance‑focused recruitment process</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="./applicant.php" class="btn btn-danger rounded-pill px-4">
                            View Applicants <i class="fa-solid fa-users ms-2"></i>
                          </a>
                          <a href="#about" class="btn btn-outline-secondary rounded-pill px-4">About CSNK</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Slide 4 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero4.jpg"
                           class="w-100"
                           alt="CSNK Slide 4"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger rounded-pill align-self-start px-3 py-2">Trusted Assistance</span>
                        <h3 class="fw-bold mt-3 mb-2">Guided Step by Step</h3>
                        <p class="text-muted mb-4">We assist applicants in every stage, from requirements to deployment.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-handshake text-danger fs-4"></i>
                          <div>
                            <div class="fw-semibold">Friendly Support</div>
                            <div class="small text-muted">Clear updates and simple guidance</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="./contactUs.php" class="btn btn-danger rounded-pill px-4">Message Us</a>
                          <a href="#about" class="btn btn-outline-secondary rounded-pill px-4">Learn More</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Slide 5 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero5.jpg"
                           class="w-100"
                           alt="CSNK Slide 5"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger rounded-pill align-self-start px-3 py-2">Quality Service</span>
                        <h3 class="fw-bold mt-3 mb-2">Reliable Processing</h3>
                        <p class="text-muted mb-4">We maintain quality standards to deliver a smooth and organized process.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-star text-danger fs-4"></i>
                          <div>
                            <div class="fw-semibold">High Standards</div>
                            <div class="small text-muted">Professional handling and coordination</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#about" class="btn btn-danger rounded-pill px-4">About Us</a>
                          <a href="#contact" class="btn btn-outline-secondary rounded-pill px-4">Get in Touch</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Slide 6 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero6.jpg"
                           class="w-100"
                           alt="CSNK Slide 6"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge bg-danger rounded-pill align-self-start px-3 py-2">Global Opportunities</span>
                        <h3 class="fw-bold mt-3 mb-2">Work Abroad Ready</h3>
                        <p class="text-muted mb-4">We connect applicants to employers through trusted channels.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-globe text-danger fs-4"></i>
                          <div>
                            <div class="fw-semibold">Network Reach</div>
                            <div class="small text-muted">Better opportunities with proper support</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#applicants" class="btn btn-danger rounded-pill px-4">Apply Now</a>
                          <a href="#contact" class="btn btn-outline-secondary rounded-pill px-4">Directions</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div> <!-- /.carousel-inner -->
      </div> <!-- /#csnkCarouselCards -->
    </div>
  </section>


  <!-- Why Choose CSNK -->
  <section id="about" class="py-5 bg-light">
    <div class="container">

      <!-- Section Header -->
      <div class="text-center mb-5">
        <span class="text-uppercase text-muted fw-semibold small">Why Choose</span>

        <div class="my-2">
          <img src="../resources/img/whychoose.png" alt="CSNK Manpower Agency" class="img-fluid" style="height:60px;">
        </div>

        <div class="row">
          <div class="col-lg-8 col-md-10 mx-auto">
            <p class="text-muted mb-0">
              CSNK Manpower Agency specializes in the recruitment and placement of qualified housemaids and nannies,
              supported by structured screening and verified documentation. We connect families with trustworthy domestic
              workers through safe, transparent, and compliant processes.
            </p>
          </div>
        </div>
      </div>

      <!-- Feature Cards -->
      <div class="row g-4">

        <!-- Card 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-award fa-3x text-danger"></i></div>
              <h2 class="fw-bold text-danger mb-1">20,000+</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Applications Processed</div>
              <h5 class="fw-semibold mb-2">Efficient & Quality Service</h5>
              <p class="text-muted small mb-0">Clear guidance, organized processing, and consistent communication.</p>
            </div>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-chart-line fa-3x text-danger"></i></div>
              <h2 class="fw-bold text-danger mb-1">Since 2020</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Proven Track Record</div>
              <h5 class="fw-semibold mb-2">Successful Deployments</h5>
              <p class="text-muted small mb-0">Thousands placed with compliant, step‑by‑step support.</p>
            </div>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="col-lg-4 col-md-12">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x text-danger"></i></div>
              <h2 class="fw-bold text-danger mb-1">100%</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Verification Process</div>
              <h5 class="fw-semibold mb-2">Safe & Compliant Recruitment</h5>
              <p class="text-muted small mb-0">Document validation and screening to protect families and applicants.</p>
            </div>
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

  <!-- ===================== -->
  <!-- Page Content Ends -->
  <!-- ===================== -->

<!-- ✅ Reusable Footer -->
<?php include __DIR__ . '/footer.php'; ?>

  <!-- Bootstrap JS (bundle includes Popper) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>