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
  <meta name="theme-color" content="#ffffff">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    :root{
      /* Brand palette (Navbar/Footer remain as-is; these are for sections only) */
      --smc-red: #D72638;       /*Primary highlight */
      --smc-navy: #0B1628;      /* Deep navy */
      --smc-navy-2: #251a1a;     /* Slightly lighter navy */
      --smc-gold: #FFD84D;      /* Gold accent */
      --ink: #111;              /* Neutral text */
      --muted: #6c757d;         /* Muted text */
      --card: #ffffff;
      --border: #e9ecef;
      --shadow: 0 12px 28px rgba(0,0,0,.12);
      --radius-outer: 1.25rem;
      --radius-inner: 1rem;
    }

  /* ========== HERO (Improved Luxury Green/Gold Theme) ========== */
  .hero-wrap{
    border-radius: var(--radius-outer);
    color:#fff;
    padding: clamp(1.25rem, 2.8vw, 2rem);

    /* Premium hero background */
    background:
      radial-gradient(900px 320px at 0% 0%, rgba(255, 215, 80, 0.20), rgba(0,0,0,0) 70%),
      radial-gradient(900px 420px at 100% 50%, rgba(255, 215, 80, 0.15), rgba(0,0,0,0) 60%),
      linear-gradient(120deg, #0f1602 30%, #3F4A02 70%, #d5f712 100%);

    box-shadow: var(--shadow);
    overflow: hidden;
    position: relative;
    isolation: isolate;
  }

  .hero-wrap::after{
    /* cinematic soft overlay */
    content:"";
    position:absolute; inset:0;
    background:
      radial-gradient(800px 260px at 120% -10%, rgba(0,0,0,0.40), rgba(0,0,0,0) 60%);
    z-index:0;
  }

  .hero-content{
    position:relative;
    z-index:1;
  }

  /* Bullet icons gold accent */
  .hero-bullet i{
    color: #FFD84D !important;
  }


    /* ========== TURKEY PROGRAM (navy + gold buttons) ========== */
    .turkey-card{
      border-radius: var(--radius-outer);
      overflow: hidden;
      box-shadow: var(--shadow);
      border:0;
    }
    .turkey-left{
      background:
        radial-gradient(900px 320px at 10% -20%, rgba(255,216,77,.18), rgba(255,216,77,0) 60%),
        linear-gradient(110deg, var(--smc-navy) 0%, var(--smc-navy-2) 100%);
      color:#fff;
    }
    .btn-gold{
      background: var(--smc-gold);
      color:#111;
      border:0;
      border-radius: 999px;
      padding: .65rem 1rem;
      font-weight: 700;
    }
    .btn-gold:hover{ filter: brightness(.95); color:#111; }
    .btn-outline-gold{
      border-radius:999px;
      border:2px solid var(--smc-gold);
      color: var(--smc-gold);
      padding:.6rem 1rem;
      background: transparent;
    }
    .btn-outline-gold:hover{ background: var(--smc-gold); color:#111; }

    /* ========== CAROUSEL (cards) ========== */
    .slide-card{
      border-radius: var(--radius-outer);
      overflow:hidden;
      border:0;
      box-shadow: var(--shadow);
    }

    /* ========== WHY CHOOSE (subtle white card with gold ticks) ========== */
    .why-header img{ height:60px; }
    .why-card .fa-award,
    .why-card .fa-chart-line,
    .why-card .fa-shield-halved{ color: var(--smc-red); }

    /* ========== CTA (keep your existing gradient, refine paddings) ========== */
    .cta-hire {
      background:
        radial-gradient(800px 260px at 8% 5%, rgba(255,170,120,.18), rgba(255,170,120,0) 60%),
        radial-gradient(1000px 320px at 92% 110%, rgba(12,32,76,.08), rgba(12,32,76,0) 60%),
        linear-gradient(180deg, #ffffff 0%, #fbfcff 60%, #f7f9fc 100%);
      border-radius: var(--radius-outer);
      padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 2rem);
      box-shadow: 0 20px 40px rgba(13,29,54,.06), 0 1px 0 rgba(255,255,255,.6) inset;
    }
    .cta-row { display:grid; grid-template-columns:1fr; align-items:center; gap: clamp(.75rem, 2vw, 1rem); }
    @media (min-width:768px){ .cta-row{ grid-template-columns:1fr auto; } }
    .cta-title { font-weight:800; font-size: clamp(1.05rem, 2.1vw, 1.35rem); color:#1b1d22; margin:0; line-height:1.35; }
    .cta-actions { display:flex; justify-content:flex-start; }
    @media (min-width:768px){ .cta-actions { justify-content:flex-end; } }
    .cta-btn {
      --grad-a:#ff7a3d; --grad-b:#ffb04a;
      background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
      color:#fff; border:0; border-radius:999px; padding:.85rem 1.5rem; font-weight:700; letter-spacing:.2px;
      text-decoration:none; display:inline-flex; align-items:center; gap:.6rem;
      box-shadow:0 12px 26px rgba(255,122,61,.28);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      position:relative; isolation:isolate; white-space:nowrap;
    }
    .cta-btn:hover,.cta-btn:focus{ transform: translateY(-1px); box-shadow:0 16px 34px rgba(255,122,61,.34); filter:brightness(1.03); color:#fff; }
    .cta-btn::after{ content:"✦ ✦ ✦"; font-size:.85rem; color:#ffa95a; position:absolute; right:-2rem; top:50%; transform:translateY(-50%); opacity:.95; pointer-events:none; }
    @media (max-width:575.98px){ .cta-btn::after{ right:-1.6rem; font-size:.8rem; } }
    @media (prefers-reduced-motion: reduce){ .cta-btn { transition:none !important; } }

    /* Utilities */
    .badge-soft{
      background: #fff; color: var(--smc-red);
      border: 1px solid rgba(215,38,56,.2);
      border-radius: 999px;
      padding:.45rem .8rem;
      font-weight:700;
    }
  </style>
</head>
<body class="bg-light">

  <!-- ✅ Reusable Navbar (colors untouched) -->
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
            <h1 class="display-6 fw-bold mb-3">Welcome to SMC Manpower Agency Co.</h1>
            <p class="lead mb-4">
              Your trusted partner for qualified housemaids and nannies. We connect families with properly screened domestic workers through safe and compliant processes.
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

  <!-- 🇹🇷 TURKEY PROGRAM (Directly under HERO) -->
  <section id="turkey" class="py-4">
    <div class="container">
      <div class="card turkey-card">
        <div class="row g-0 align-items-stretch">
          <div class="col-md-6 turkey-left">
            <div class="h-100 p-4 p-lg-5 d-flex flex-column justify-content-center">
              <span class="badge rounded-pill text-dark px-3 py-2" style="background: var(--smc-gold);">Turkey Program</span>
              <h2 class="fw-bold mt-3 mb-2">For Turkish Employers</h2>
              <p class="mb-4" style="opacity:.95;">
                This website is the international version of SMC for <strong>Turkey</strong>. We deploy
                <strong>Filipina housemaids (Pinays)</strong> to Turkish households through a safe, compliant, and
                well‑guided process with proper screening, documentation, and cultural orientation.
              </p>

              <ul class="list-unstyled m-0">
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-shield-halved fs-5"></i>
                  <span><strong>Compliance &amp; Safety:</strong> Verified documents and step‑by‑step support</span>
                </li>
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-language fs-5"></i>
                  <span><strong>Language &amp; Culture:</strong> Orientation for Turkish household norms</span>
                </li>
                <li class="d-flex gap-3">
                  <i class="fa-solid fa-handshake-angle fs-5"></i>
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
              <h5 class="fw-bold mb-3">What We Screen</h5>
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border">
                    <div class="fw-semibold">Experience &amp; Skills</div>
                    <div class="small text-muted">Childcare, cleaning, laundry, cooking</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border">
                    <div class="fw-semibold">Background &amp; References</div>
                    <div class="small text-muted">Work history and contactable refs</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border">
                    <div class="fw-semibold">Health &amp; Fitness</div>
                    <div class="small text-muted">Fit‑to‑work compliance</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border">
                    <div class="fw-semibold">Documents</div>
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
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero1.jpg"
                           class="w-100"
                           alt="SMC Slide 1"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">SMC Manpower Agency Co.</span>
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
                      <img src="../resources/img/hero2.jpg"
                           class="w-100"
                           alt="SMC Slide 2"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Fast and Clear Process</span>
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
                      <img src="../resources/img/hero3.jpg"
                           class="w-100"
                           alt="SMC Slide 3"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Safety and Compliance</span>
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
                          <a href="/view/about.php" class="btn btn-outline-secondary rounded-pill px-4">About SMC</a>
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
                      <img src="../resources/img/hero4.jpg"
                           class="w-100"
                           alt="SMC Slide 4"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Trusted Assistance</span>
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
                      <img src="../resources/img/hero5.jpg"
                           class="w-100"
                           alt="SMC Slide 5"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Quality Service</span>
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
                </div><!-- /card -->
              </div>
            </div>
          </div>

          <!-- Slide 6 -->
          <div class="carousel-item">
            <div class="row justify-content-center">
              <div class="col-12 col-lg-10 col-xl-9">
                <div class="card slide-card">
                  <div class="row g-0">
                    <div class="col-md-6">
                      <img src="../resources/img/hero6.jpg"
                           class="w-100"
                           alt="SMC Slide 6"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Global Opportunities</span>
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
                </div><!-- /card -->
              </div>
            </div>
          </div>

        </div> <!-- /.carousel-inner -->
      </div> <!-- /#csnkCarouselCards -->
    </div>
  </section>

  <!-- Why Choose SMC -->
  <section id="about" class="py-5 bg-light">
    <div class="container">

      <!-- Section Header -->
      <div class="text-center mb-5 why-header">
        <span class="text-uppercase text-muted fw-semibold small">Why Choose</span>

        <div class="my-2">
          <img src="../resources/img/whychoose.png" alt="SMC Manpower Agency Co." class="img-fluid">
        </div>

        <div class="row">
          <div class="col-lg-8 col-md-10 mx-auto">
            <p class="text-muted mb-0">
              SMC Manpower Agency Co. specializes in the recruitment and placement of qualified housemaids and nannies,
              supported by structured screening and verified documentation. We connect families with trustworthy domestic
              workers through safe, transparent, and compliant processes.
            </p>
          </div>
        </div>
      </div>

      <!-- Feature Cards -->
      <div class="row g-4 why-card">

        <!-- Card 1 -->
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-award fa-3x"></i></div>
              <h2 class="fw-bold text-danger mb-1">20,000+</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Applications Processed</div>
              <h5 class="fw-semibold mb-2">Efficient &amp; Quality Service</h5>
              <p class="text-muted small mb-0">Clear guidance, organized processing, and consistent communication.</p>
            </div>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-chart-line fa-3x"></i></div>
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
              <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x"></i></div>
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
                <span class="badge bg-danger rounded-pill px-3 py-2">SMC Manpower Agency Co.</span>
              </div>

              <h5 class="fw-bold mb-3">Office Information</h5>

              <div class="d-flex gap-3 mb-3">
                <div class="text-danger fs-5"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                  <div class="fw-semibold">Address</div>
                  <div class="text-muted small">
                    Unit 1 Eden Townhomes 
                    2001 Eden Street corner Pedro Gil Street Sta. Ana 
                    Manila., 1009 Barangay 866, City of Manila, 
                    NCR, Sixth District
                  </div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-danger fs-5"><i class="fa-solid fa-phone"></i></div>
                <div>
                  <div class="fw-semibold">Phone</div>
                  <div class="text-muted small">0939 342 7412</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-danger fs-5"><i class="fa-solid fa-envelope"></i></div>
                <div>
                  <div class="fw-semibold">Email</div>
                  <div class="text-muted small">smcwelfaremonitoring@gmail.com</div>
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

  <!-- FINAL CTA: Hire Now! -->
  <section class="py-4 py-md-5">
    <div class="container">
      <div class="cta-hire">
        <div class="cta-row">
          <p class="cta-title">
            Hire reliable, properly screened Household Service Workers (HSWs)
            for your home.
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

  <!-- ✅ Reusable Footer (colors untouched) -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- Bootstrap JS (bundle includes Popper + Carousel) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Policy Modals Handler -->
  <script src="../resources/js/policy-modals.js"></script>
</body>
</html>