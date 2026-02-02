<?php
  // Set the active page for navbar highlighting
  $page = 'about';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>CSNK Manpower Agency</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<!-- ✅ Reusable Navbar -->
<?php include __DIR__ . '/navbar.php'; ?>

<!-- ===================== -->
<!-- Page Content Starts   -->
<!-- ===================== -->

<style>
  /* ---------- Base / utilities applicable to this page ---------- */
  img, svg { max-width: 100%; height: auto; }

  /* ---------- HERO SECTION ---------- */
  .hero-section {
    background-color: #f8f9fb;
    position: relative;
    isolation: isolate; /* keep background layers behind content */
    padding: clamp(2rem, 6vw, 5rem) 0;
  }

  /* Background layers */
  .hero-grid,
  .hero-gradient {
    position: absolute; inset: 0;
    z-index: 0;
    pointer-events: none;
  }
  .hero-grid {
    opacity: .22;
    background-image:
      linear-gradient(to right, rgba(0,0,0,.06) 1px, transparent 1px),
      linear-gradient(to bottom, rgba(0,0,0,.06) 1px, transparent 1px);
    background-size: 32px 32px, 32px 32px;
    mask-image: linear-gradient(to bottom, rgba(0,0,0,1) 10%, rgba(0,0,0,.85) 40%, rgba(0,0,0,.6) 70%, rgba(0,0,0,0) 100%);
  }
  .hero-gradient {
    background:
      radial-gradient(900px 400px at 15% 35%, rgba(255, 159, 169, 0.88), rgba(220, 53, 69, 0) 60%),
      radial-gradient(700px 350px at 80% 45%, rgba(17, 17, 17, .12), rgba(17,17,17,0) 60%),
      linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.25) 60%, rgba(255, 84, 84, 0) 100%);
    mask-image: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,.9) 10%, rgba(0,0,0,.95) 85%, rgba(0,0,0,0) 100%);
  }

  /* Keep actual content above background layers */
  .hero-section .container { position: relative; z-index: 1; }

  /* Headings (desktop) */
  @media (min-width: 992px) {
    .hero-section .display-4 { font-size: 3rem; line-height: 1.1; }
  }

  /* Smooth swap animation */
  .fade-swap { transition: opacity .22s ease, transform .22s ease; }
  .is-swapping { opacity: 0; transform: translateY(6px); }

  /* ---------- PILL BAR (shrink-to-content & scrollable) ---------- */

  /* Wrapper: only scrolls when needed */
  .hero-pills-abs-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  /* Pill bar: fit to content, not full width */
  #heroPills {
    display: inline-flex;        /* makes white capsule hug its content */
    width: max-content;          /* shrink-wrap */
    max-width: 100%;             /* never overflow the container */
    gap: .5rem;
    align-items: center;
    padding: .5rem .6rem;        /* capsule padding */
    border-radius: 999px;
    box-shadow: 0 4px 15px rgba(0,0,0,.08);
    overflow: visible;           /* keep nice shadows visible */
    flex-wrap: nowrap;           /* one row; wrapper handles scroll on small screens */
    scroll-snap-type: x proximity;
    background: #fff;            /* ensure white capsule */
  }

  #heroPills .btn {
    flex: 0 0 auto;              /* no shrinking */
    white-space: nowrap;         /* keep labels on one line */
    scroll-snap-align: start;
  }

  .hero-section .btn-light.active { background: #111; color: #fff; }

  /* Center the capsule on md+ (optional) */
  @media (min-width: 768px) {
    .hero-pills-abs-wrapper {
      display: flex;
      justify-content: flex-start; /* change to center if you want centered pills */
    }
  }

  /* ---------- Hero visual (image) ---------- */
  .hero-visual { display: flex; justify-content: center; }
  .hero-image-wrap {
    background: transparent;
    border-radius: 1rem;
    filter: drop-shadow(0 12px 22px rgba(0,0,0,.18));
    width: clamp(260px, 40vw, 520px);  /* desktop-preferred width */
  }
  .hero-image-wrap img {
    display: block;
    width: 100%;
    height: auto;
    object-fit: contain;
  }

  /* ---------- Responsive fixes (small → medium) ---------- */

  /* Let hero content breathe on xs; avoid clipping */
  @media (max-width: 575.98px) {
    .hero-section { overflow: visible !important; }
    .hero-title-wrap .display-4 { font-size: 2rem; line-height: 1.2; }
    .hero-lead-wrap .lead { font-size: 1rem; }
  }

  /* On < lg, center the image and remove desktop offsets entirely */
  @media (max-width: 991.98px) {
    .hero-image-wrap {
      margin: 0 auto !important;
      transform: none !important;
      width: min(85vw, 420px);
    }
  }

  /* ---------- Carousel image heights by breakpoint ---------- */
  .carousel-img {
    width: 100%;
    height: 340px;
    object-fit: cover;
  }
  @media (max-width: 575.98px) { .carousel-img { height: 220px; } }
  @media (min-width: 576px) and (max-width: 991.98px) { .carousel-img { height: 280px; } }

  /* Extra room around pills on phones */
  @media (max-width: 575.98px) {
    .hero-pills-abs-wrapper { margin-bottom: .5rem; }
    .hero-pills-spacer { height: 0; }
  }
</style>

<!-- HERO -->
<section class="hero-section">
  <!-- background layers -->
  <div class="hero-grid"></div>
  <div class="hero-gradient"></div>

  <div class="container">
    <div class="row align-items-center g-4 g-lg-5">

      <!-- LEFT: Text + pills -->
      <div class="col-12 col-lg-6">
        <div class="hero-title-wrap mb-2">
          <h1 id="heroTitle" class="display-4 fw-bold mb-0 fade-swap">
            Get to know CSNK Manpower Agency
          </h1>
        </div>

        <div class="hero-lead-wrap mb-4">
          <p id="heroLead" class="lead text-black-50 mb-0 fade-swap">
            Clear, honest and customer‑first guidance. We connect families with properly
            screened domestic workers through safe and compliant processes.
          </p>
        </div>

        <!-- Pills -->
        <div class="hero-pills-abs-wrapper">
          <div id="heroPills"
               class="rounded-pill px-3 py-2 d-inline-flex align-items-center"
               role="tablist" aria-label="Hero options">

            <button type="button" class="btn btn-light rounded-pill px-3 py-2 active"
                    role="tab" aria-selected="true"
                    data-title="Get to know CSNK"
                    data-lead="CSNK Manpower Agency is dedicated to providing families with reliable 
                    and compassionate household assistance. Beyond offering quality domestic help, we 
                    are a full‑service manpower agency committed to supporting and empowering Filipino 
                    women by connecting them with safe, legitimate, and rewarding employment opportunities. 
                    Through proper screening, guidance, and documentation, we ensure that every home receives 
                    trustworthy service, while every applicant receives a fair chance to build a better future."
                    data-img="../resources/img/overview.png"
                    data-img-alt="Clients getting onboarding help">
              Overview
            </button>

            <button type="button" class="btn btn-light rounded-pill px-3 py-2"
                    role="tab" aria-selected="false"
                    data-title="Meet Founder of CSNK"
                    data-lead="CSNK was founded by Mr. Rogelio M. Lansang year 2010, because of his 
                    passion to help people and give a job for those in need. He is formerly an Overseas 
                    Filipino Worker in the Middle East for Ten (10) years from 1989 to 2004. His goal 
                    is to provide an opportunity that will help Filipino women build a better future, not
                    only for themselves, but for their families. He was successfully managing group of 
                    companies under the SMC GROUP OF COMPANY in 2006. Since then, Mr. Lansang has remained 
                    committed to ensuring that CSNK carries out its mission with integrity."
                    data-img="../resources/img/MrRog.png"
                    data-img-alt="Team collaborating on documents">
              Founder
            </button>
          </div>
        </div>

        <!-- Spacer (kept for layout compatibility) -->
        <div class="hero-pills-spacer"></div>
      </div>

      <!-- RIGHT: Image -->
      <div class="col-12 col-lg-6 hero-visual">
        <div class="hero-image-wrap rounded-4">
          <img id="heroImg"
               src="/resources/img/hero1.jpg"
               alt="Clients getting onboarding help"
               class="img-fluid fade-swap">
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Gallery (Card Style Carousel) -->
<section class="py-4 bg-dark">
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
                    <img src="/resources/img/hero1.jpg" class="carousel-img" alt="CSNK Slide 1">
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
                    <img src="/resources/img/hero2.jpg" class="carousel-img" alt="CSNK Slide 2">
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
                        <a href="#applicants" class="btn btn-outline-secondary rounded-pill px-4">Applicants</a>
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
                    <img src="/resources/img/hero3.jpg" class="carousel-img" alt="CSNK Slide 3">
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
                        <a href="#applicants" class="btn btn-danger rounded-pill px-4">
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
                    <img src="/resources/img/hero4.jpg" class="carousel-img" alt="CSNK Slide 4">
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
                        <a href="#contact" class="btn btn-danger rounded-pill px-4">Message Us</a>
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
                    <img src="/resources/img/hero5.jpg" class="carousel-img" alt="CSNK Slide 5">
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
                    <img src="/resources/img/hero6.jpg" class="carousel-img" alt="CSNK Slide 6">
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
       v>

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

      <div class="col-lg-4 col-md-12">
        <div class="card h-100 border-0 shadow-sm">
          <div class="card-body text-center p-4">
            <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x text-danger"></i></div>
            <h2 class="fw-bold text-danger mb-1">100%</h2>
            <div class="text-uppercase text-muted fw-semibold small mb-3">Verification Process</div>
            <h5 class="fw-semibold mb-2">Safe &amp; Compliant Recruitment</h5>
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

        <div class="col-lg-7">
          https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT&output=embed
          </iframe>
        </div>

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
              https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT
                <i class="fa-solid fa-location-arrow me-2"></i>Get Directions
              </a>

              #home
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
<!-- Page Content Ends     -->
<!-- ===================== -->

<!-- ✅ Reusable Footer -->
<?php include __DIR__ . '/footer.php'; ?>

<!-- Bootstrap JS (bundle includes Popper) -->
https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js</script>

<!-- Page‑local: Hero pill swapper -->
<script>
(function(){
  const container = document.getElementById('heroPills');
  const titleEl   = document.getElementById('heroTitle');
  const leadEl    = document.getElementById('heroLead');
  const imgEl     = document.getElementById('heroImg');

  if (!container || !titleEl || !leadEl || !imgEl) return;

  const pills   = container.querySelectorAll('.btn');
  const swapEls = [titleEl, leadEl, imgEl];

  function setActive(btn){
    pills.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
    btn.classList.add('active'); btn.setAttribute('aria-selected','true');
  }

  function applyFrom(btn){
    if (btn.dataset.title) titleEl.textContent = btn.dataset.title;
    if (btn.dataset.lead)  leadEl.textContent  = btn.dataset.lead;
    if (btn.dataset.img) {
      imgEl.src = btn.dataset.img;
      imgEl.alt = btn.dataset.imgAlt || btn.dataset.title || 'Hero image';
    }
  }

  function swap(btn){
    setActive(btn);
    swapEls.forEach(el => el.classList.add('is-swapping'));
    setTimeout(() => {
      applyFrom(btn);
      swapEls.forEach(el => el.classList.remove('is-swapping'));
    }, 150);
  }

  pills.forEach(btn => {
    btn.addEventListener('click', () => swap(btn));
    btn.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); swap(btn); }
    });
  });

  const init = container.querySelector('.btn.active') || pills[0];
  if (init) applyFrom(init);
})();
</script>
</body>
</html>