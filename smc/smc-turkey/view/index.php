<?php
  // Set the active page for navbar highlighting
  $page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SMC Manpower Agency Co.</title>

  <!-- ✅ FAVICONS (keep both if unsure about your root path) -->
  <link rel="icon" type="image/png" href="/resources/img/smc.png" />
  <link rel="shortcut icon" href="/resources/img/smc.png" />
  <link rel="apple-touch-icon" href="/resources/img/smc.png" />
  <!-- Fallback when this file is inside /view/ -->
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <link rel="apple-touch-icon" href="../resources/img/smc.png" />
  <meta name="theme-color" content="#0B1F3A">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      /* ======== NAVY + GOLD BRAND SYSTEM ======== */
      --smc-navy: #0B1F3A;        /* Deep navy */
      --smc-navy-2: #0c1a2e;      /* Secondary navy */
      --smc-navy-3: #1B355C;      /* Accent navy */
      --smc-gold: #FFD84D;        /* Gold accent */
      --smc-gold-2: #F4C542;      /* Warm gold */
      --ink: #0E1A2B;             /* Primary body text (navy-ish, not black) */
      --muted: #6c757d;           /* Muted text (gray) */
      --card: #ffffff;
      --border: #e9ecef;
      --shadow: 0 12px 28px rgba(7, 18, 38, .16);
      --radius-outer: 1.25rem;
      --radius-inner: 1rem;
    }

    body { color: var(--ink); }

    /* ======== REUSABLE UTILITIES ======== */
    .text-navy { color: var(--smc-navy) !important; }
    .text-navy-2 { color: var(--smc-navy-2) !important; }
    .text-gold { color: var(--smc-gold) !important; }
    .bg-navy { background: var(--smc-navy) !important; color:#fff; }
    .bg-navy-2 { background: var(--smc-navy-2) !important; color:#fff; }
    .bg-soft-navy { background: #f3f6fb !important; }
    .badge-navy {
      background: var(--smc-navy);
      color:#fff;
      border-radius: 999px;
      padding: .4rem .8rem;
      font-weight:700;
    }
    .badge-gold {
      background: var(--smc-gold);
      color: var(--smc-navy);
      border-radius: 999px;
      padding: .4rem .8rem;
      font-weight:700;
    }
    .badge-soft{
      background: #fff;
      color: var(--smc-navy);
      border: 1px solid rgba(11,31,58,.15);
      border-radius: 999px;
      padding:.45rem .8rem;
      font-weight:700;
    }

    .btn-navy{
      background: linear-gradient(180deg, var(--smc-navy-3), var(--smc-navy));
      color:#fff; border:0; border-radius: 999px; padding:.75rem 1.25rem; font-weight:700;
      box-shadow: 0 8px 18px rgba(11,31,58,.25);
    }
    .btn-navy:hover{ filter: brightness(1.03); color:#fff; }

    .btn-gold{
      background: linear-gradient(180deg, var(--smc-gold), var(--smc-gold-2));
      color: #18243b; border:0; border-radius: 999px; padding:.75rem 1.25rem; font-weight:800;
      box-shadow: 0 10px 20px rgba(244,197,66,.28);
      letter-spacing:.1px;
    }
    .btn-gold:hover{ filter: brightness(1.02); color:#1b2a41; }

    .btn-outline-gold{
      border-radius:999px;
      border:2px solid var(--smc-gold);
      color: var(--smc-gold);
      padding:.65rem 1.2rem;
      background: transparent;
      font-weight:700;
    }
    .btn-outline-gold:hover{ background: var(--smc-gold); color:#18243b; }

    .btn-outline-navy{
      border-radius:999px;
      border:2px solid var(--smc-navy);
      color: var(--smc-navy);
      padding:.65rem 1.2rem;
      background: transparent;
      font-weight:700;
    }
    .btn-outline-navy:hover{ background: var(--smc-navy); color:#fff; }

    /* ======== HERO (Navy + Gold Accents) ======== */
    .hero-wrap{
      border-radius: var(--radius-outer);
      color:#fff;
      padding: clamp(1.25rem, 2.8vw, 2rem);
      background:
        radial-gradient(900px 320px at 5% 0%, rgba(255, 216, 77, 0.18), rgba(0,0,0,0) 70%),
        radial-gradient(900px 420px at 95% 50%, rgba(255, 216, 77, 0.12), rgba(0,0,0,0) 60%),
        linear-gradient(120deg, var(--smc-navy) 30%, var(--smc-navy-2) 70%, #1d3a67 100%);
      box-shadow: var(--shadow);
      overflow: hidden;
      position: relative;
      isolation: isolate;
    }
    .hero-wrap::after{
      content:"";
      position:absolute; inset:0;
      background: radial-gradient(800px 260px at 120% -10%, rgba(5,12,24,0.35), rgba(0,0,0,0) 60%);
      z-index:0;
    }
    .hero-content{ position:relative; z-index:1; }

    .hero-bullet i{ color: var(--smc-gold) !important; }

    /* ======== TURKEY PROGRAM CARD ======== */
    .turkey-card{
      border-radius: var(--radius-outer);
      overflow: hidden;
      box-shadow: var(--shadow);
      border:0;
      background:#fff;
    }
    .turkey-left{
      background:
        radial-gradient(900px 320px at 10% -20%, rgba(255,216,77,.18), rgba(255,216,77,0) 60%),
        linear-gradient(110deg, var(--smc-navy) 0%, var(--smc-navy-2) 100%);
      color:#fff;
    }

    /* ======== CAROUSEL CARDS ======== */
    .slide-card{
      border-radius: var(--radius-outer);
      overflow:hidden;
      border:0;
      box-shadow: var(--shadow);
      background:#fff;
    }

    /* ======== WHY CHOOSE (Cards) ======== */
    .why-header img{ height:60px; }
    .why-card i{ color: var(--smc-gold); }

    /* ======== CTA (Navy/Gold) ======== */
    .cta-hire {
      background:
        radial-gradient(820px 260px at 8% 5%, rgba(255,216,77,.13), rgba(255,216,77,0) 60%),
        radial-gradient(900px 320px at 92% 110%, rgba(19,42,74,.08), rgba(19,42,74,0) 60%),
        linear-gradient(180deg, #ffffff 0%, #f8fbff 60%, #f4f8ff 100%);
      border-radius: var(--radius-outer);
      padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 2rem);
      box-shadow: 0 20px 40px rgba(13,29,54,.06), 0 1px 0 rgba(255,255,255,.6) inset;
    }
    .cta-row { display:grid; grid-template-columns:1fr; align-items:center; gap: clamp(.75rem, 2vw, 1rem); }
    @media (min-width:768px){ .cta-row{ grid-template-columns:1fr auto; } }
    .cta-title { font-weight:800; font-size: clamp(1.05rem, 2.1vw, 1.35rem); color:var(--smc-navy); margin:0; line-height:1.35; }
    .cta-actions { display:flex; justify-content:flex-start; }
    @media (min-width:768px){ .cta-actions { justify-content:flex-end; } }
    .cta-btn {
      --grad-a: var(--smc-navy-3);
      --grad-b: var(--smc-navy);
      background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
      color:#fff; border:0; border-radius:999px; padding:.9rem 1.6rem; font-weight:800; letter-spacing:.2px;
      text-decoration:none; display:inline-flex; align-items:center; gap:.6rem;
      box-shadow:0 12px 26px rgba(11,31,58,.28);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      position:relative; isolation:isolate; white-space:nowrap;
    }
    .cta-btn:hover,.cta-btn:focus{ transform: translateY(-1px); box-shadow:0 16px 34px rgba(11,31,58,.34); filter:brightness(1.03); color:#fff; }
    .cta-btn::after{ content:"✦ ✦ ✦"; font-size:.85rem; color:var(--smc-gold-2); position:absolute; right:-2rem; top:50%; transform:translateY(-50%); opacity:.95; pointer-events:none; }
    @media (max-width:575.98px){ .cta-btn::after{ right:-1.6rem; font-size:.8rem; } }
    @media (prefers-reduced-motion: reduce){ .cta-btn { transition:none !important; } }

    /* ======== BORDERS + CARDS ======== */
    .border-soft { border: 1px solid rgba(11,31,58,.12); border-radius: var(--radius-inner); }
  </style>
</head>
<body class="bg-soft-navy">

  <!-- ✅ Reusable Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- ===================== -->
  <!-- Page Content Starts  -->
  <!-- ===================== -->

  <!-- HERO -->
  <section id="home" class="py-5">
    <div class="container">
      <div class="hero-wrap">
        <div class="row align-items-center gy-4 hero-content">
          <div class="col-lg-6">
            <h1 class="display-6 fw-bold mb-3">Right Person for the Right Job, Right Employment for the Right Man</h1>
            <p class="lead mb-4">
              SMC Manpower Agency Philippines Company, is duly authorized and licensed by POEA under license no. DMW-062-LB-03232023-R to recruit, hire and process manpower for its accredited foreign principals.
            </p>

            <div class="d-flex gap-3 mb-2 hero-bullet">
              <i class="fa-solid fa-circle-check fa-lg mt-1"></i>
              <div>
                <div class="fw-semibold">Professional Recruitment</div>
                <div class="small opacity-75">Expert matching and responsible placement</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-4 hero-bullet">
              <i class="fa-solid fa-globe fa-lg mt-1"></i>
              <div>
                <div class="fw-semibold">Network &amp; Support</div>
                <div class="small opacity-75">Guidance from screening to deployment</div>
              </div>
            </div>

            <a href="#about" class="btn btn-gold btn-lg fw-semibold">Learn More</a>
          </div>

          <div class="col-lg-6">
            <img class="img-fluid rounded-4 shadow"
                 src="../resources/img/hero.jpeg"
                 alt="Office">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 🇹🇷 TURKEY PROGRAM (Directly under HERO) -->
  <section id="turkey" class="py-4">
    <div class="container">
      <div class="card turkey-card">
        <div class="row g-0 align-items-stretch">
          <div class="col-md-6 turkey-left">
            <div class="h-100 p-4 p-lg-5 d-flex flex-column justify-content-center">
              <span class="badge-gold">Turkey Program</span>
              <h2 class="fw-bold mt-3 mb-2">For Turkish Employers</h2>
              <p class="mb-4" style="opacity:.95;">
                This website is the international version of SMC for <strong>Turkey</strong>. We deploy
                <strong>Filipino Skilled Workers </strong> to Turkish households through a safe, compliant, and
                well‑guided process with proper screening, documentation, and cultural orientation.
              </p>

              <ul class="list-unstyled m-0">
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-shield-halved fs-5 text-gold"></i>
                  <span><strong>Compliance &amp; Safety:</strong> Verified documents and step‑by‑step support</span>
                </li>
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-language fs-5 text-gold"></i>
                  <span><strong>Language &amp; Culture:</strong> Orientation for Turkish household norms</span>
                </li>
                <li class="d-flex gap-3">
                  <i class="fa-solid fa-handshake-angle fs-5 text-gold"></i>
                  <span><strong>Responsible Placement:</strong> Clear expectations for both employer and worker</span>
                </li>
              </ul>

              <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="./applicant.php" class="btn btn-gold">
                  View Applicants <i class="fa-solid fa-users ms-2"></i>
                </a>
                <a href="#contact" class="btn btn-outline-gold">
                  Contact Us
                </a>
              </div>
            </div>
          </div>

          <div class="col-md-6 bg-white">
            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
              <h5 class="fw-bold mb-3 text-navy">What We Screen</h5>
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft">
                    <div class="fw-semibold text-navy">Experience &amp; Skills</div>
                    <div class="small text-muted">Childcare, cleaning, laundry, cooking</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft">
                    <div class="fw-semibold text-navy">Background &amp; References</div>
                    <div class="small text-muted">Work history and contactable refs</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft">
                    <div class="fw-semibold text-navy">Health &amp; Fitness</div>
                    <div class="small text-muted">Fit‑to‑work compliance</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft">
                    <div class="fw-semibold text-navy">Documents</div>
                    <div class="small text-muted">Valid IDs, clearances, travel readiness</div>
                  </div>
                </div>
              </div>

              <div class="mt-4 small text-muted">
                Note: Deployment is subject to documentation, employer verification, and country regulations.
              </div>
            </div>
          </div>
        </div><!-- /row -->
      </div><!-- /card -->
    </div>
  </section>

  <!-- Slideshow (Card Style Carousel) -->
  <section class="py-4 bg-soft-navy">
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
        </div>

        <div class="carousel-inner">

          <!-- Slide 1 -->
          <div class="carousel-item active">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/recruitment.png"
                           class="w-100"
                           alt="SMC Slide 1"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">SMC Manpower Agency Co.</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Trusted Recruitment Agency</h3>
                        <p class="text-muted mb-4">Excellence in Supplying Trusted and Skilled Household Service Professionals</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-circle-check text-gold fs-4"></i>
                          <div>
                            <div class="fw-semibold text-navy">Verified Processing</div>
                            <div class="small text-muted">Clear steps from start to finish</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#about" class="btn btn-navy rounded-pill px-4">
                            Learn More <i class="fa-solid fa-arrow-right ms-2"></i>
                          </a>
                          <a href="#contact" class="btn btn-outline-navy rounded-pill px-4">Contact</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div><!-- /card -->
              </div>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/selection.png"
                           class="w-100"
                           alt="SMC Slide 2"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Professional and Thorough</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Meticulous Selection Process</h3>
                        <p class="text-muted mb-4">SMC consistently brings in the most qualified individuals to strengthen our team’s performance.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-file-circle-check text-gold fs-4"></i>
                          <div>
                            <div class="fw-semibold text-navy">Requirements Support</div>
                            <div class="small text-muted">Checklists, Updates, and Assistance</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#contact" class="btn btn-navy rounded-pill px-4">
                            Contact Us <i class="fa-solid fa-phone ms-2"></i>
                          </a>
                          <a href="./applicant.php" class="btn btn-outline-navy rounded-pill px-4">Applicants</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div><!-- /card -->
              </div>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/verified.png"
                           class="w-100"
                           alt="SMC Slide 3"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Safety and Compliance</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Verified and Secure</h3>
                        <p class="text-muted mb-4">We prioritize screening, verification, and proper documentation.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-shield-halved text-gold fs-4"></i>
                          <div>
                            <div class="fw-semibold text-navy">Safety First</div>
                            <div class="small text-muted">Compliance‑focused recruitment process</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="./applicant.php" class="btn btn-gold rounded-pill px-4">
                            View Applicants <i class="fa-solid fa-users ms-2"></i>
                          </a>
                          <a href="/view/about.php" class="btn btn-outline-navy rounded-pill px-4">About SMC</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div><!-- /card -->
              </div>
            </div>
          </div>

          <!-- Slide 4 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/guide.png"
                           class="w-100"
                           alt="SMC Slide 4"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Trusted Assistance</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Guided Step by Step</h3>
                        <p class="text-muted mb-4">We assist applicants in every stage, from requirements to deployment.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-handshake text-gold fs-4"></i>
                          <div>
                            <div class="fw-semibold text-navy">Friendly Support</div>
                            <div class="small text-muted">Clear updates and simple guidance</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="./contactUs.php" class="btn btn-navy rounded-pill px-4">Message Us</a>
                          <a href="#about" class="btn btn-outline-navy rounded-pill px-4">Learn More</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div><!-- /card -->
              </div>
            </div>
          </div>

          <!-- Slide 5 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/abroad.png"
                           class="w-100"
                           alt="SMC Slide 6"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Global Opportunities</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Work Abroad Ready</h3>
                        <p class="text-muted mb-4">We offer efficient travel document processing, ensuring hassle-free application.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-globe text-gold fs-4"></i>
                          <div>
                            <div class="fw-semibold text-navy">Network Reach</div>
                            <div class="small text-muted">Better opportunities with proper support</div>
                          </div>
                        </div>

                        <div class="mt-2 d-flex flex-wrap gap-2">
                          <a href="#applicants" class="btn btn-gold rounded-pill px-4">Apply Now</a>
                          <a href="#contact" class="btn btn-outline-navy rounded-pill px-4">Directions</a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div><!-- /card -->
              </div>
            </div>
          </div>

        </div> <!-- /.carousel-inner -->
      </div> <!-- /#csnkCarouselCards -->
    </div>
  </section>

  <!-- Why Choose SMC -->
  <section id="about" class="py-5 bg-soft-navy">
    <div class="container">

      <!-- Section Header -->
      <div class="text-center mb-5 why-header">
        <span class="text-uppercase text-muted fw-semibold small">Why Choose</span>

        <div class="my-2">
          <img src="../resources/img/smc.png" alt="SMC Manpower Agency Co." class="img-fluid">
        </div>

        <div class="row">
          <div class="col-lg-8 col-md-10 mx-auto">
            <p class="text-muted mb-0">
              Our commitment to employee well-being includes competitive compensation, comprehensive benefits, 
              and a supportive work culture that fosters collaboration and innovation SMC recognizes the global 
              need for employment and believes its first and foremost responsibility is to provide our clients 
              with word-class, personalized service and to provide, Filipinos the opportunity to work overseas.
          </div>
        </div>
      </div>

      <!-- Feature Cards -->
      <div class="row g-4 why-card">

        <!-- Card 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-award fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">15</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">YEARS OF EXPERIENCE</div>
              <h5 class="fw-semibold mb-2 text-navy">Efficient Longevity Service</h5>
              <p class="text-muted small mb-0">A commitment to dependable, long‑lasting service built on consistency, streamlined processes, and the ability to deliver quality support at every stage.</p>
            </div>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-chart-line fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">Career Development</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Proven Growth Record</div>
              <h5 class="fw-semibold mb-2 text-navy">Empowering Your Career Journey</h5>
              <p class="text-muted small mb-0">We provide various resources like skill enhancement programs. Our goal is not just to find you a job, but to support your long-term career growth.</p>
            </div>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="col-lg-4 col-md-12">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">100%</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Top-Tier Services</div>
              <h5 class="fw-semibold mb-2 text-navy">Industry‑Wide Opportunities</h5>
              <p class="text-muted small mb-0">High-quality career pathways across various industries. Our services include job placement, career counseling, and skill development programs.</p>
            </div>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- Contact / Map -->
  <section id="contact" class="py-5 bg-soft-navy">
    <div class="container">
      <div class="text-center mb-4">
        <h2 class="fw-bold mb-1 text-navy">Contact and Location</h2>
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
                <span class="badge-navy rounded-pill px-3 py-2">SMC Manpower Agency Co.</span>
              </div>

              <h5 class="fw-bold mb-3 text-navy">Office Information</h5>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                  <div class="fw-semibold text-navy">Address</div>
                  <div class="text-muted small">
                    Unit 1 Eden Townhomes
                    2001 Eden Street corner Pedro Gil Street Sta. Ana
                    Manila., 1009 Barangay 866, City of Manila,
                    NCR, Sixth District
                  </div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-phone"></i></div>
                <div>
                  <div class="fw-semibold text-navy">Phone</div>
                  <div class="text-muted small">0939 342 7412</div>
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
                  <div class="fw-semibold text-navy">Office Hours</div>
                  <div class="text-muted small">Mon to Sat, 8:00 AM to 5:00 PM</div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-navy rounded-pill px-4"
                   target="_blank" rel="noopener"
                   href="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT">
                  <i class="fa-solid fa-location-arrow me-2"></i>Get Directions
                </a>

                <a class="btn btn-outline-navy rounded-pill px-4" href="#home">
                  <i class="fa-solid fa-arrow-up me-2"></i>Back to Top
                </a>
              </div>

            </div>
          </div>

        </div>
      </div>
    </div>
  </section>

  <!-- FINAL CTA: Hire Now! -->
  <section class="py-4 py-md-5">
    <div class="container">
      <div class="cta-hire">
        <div class="cta-row">
          <p class="cta-title">
            Hire reliable, properly screened Filipino Skilled Workers.
          </p>

          <div class="cta-actions">
            <a class="btn cta-btn" href="./applicant.php" aria-label="Hire Now">
              Hire Now! <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div><!-- /.cta-hire -->
    </div>
  </section>

  <!-- ✅ Reusable Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- Bootstrap JS (bundle includes Popper + Carousel) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Policy Modals Handler -->
  <script src="../resources/js/policy-modals.js"></script>
</body>
</html>