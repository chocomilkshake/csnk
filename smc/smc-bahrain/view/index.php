<?php
  // Set the active page for navbar highlighting
  $page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SMC Manpower Agency Co. — Bahrain Program</title>

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
      --ink: #0E1A2B;             /* Primary body text */
      --muted: #6c757d;           /* Muted text */
      --card: #ffffff;
      --border: #e9ecef;
      --shadow: 0 12px 28px rgba(7, 18, 38, .16);
      --radius-outer: 1.25rem;
      --radius-inner: 1rem;

      /* ======== BAHRAIN ACCENT ======== */
      --bh-red: #CE1126;      /* Bahrain flag red */
      --bh-red-2: #B10F20;    /* Darker Bahrain red */
    }

    body { color: var(--ink); background: #f5f7fb; }

    /* ======== REUSABLE UTILITIES ======== */
    .text-navy { color: var(--smc-navy) !important; }
    .text-navy-2 { color: var(--smc-navy-2) !important; }
    .text-gold { color: var(--smc-gold) !important; }
    .text-bh-red { color: var(--bh-red) !important; }
    .bg-navy { background: var(--smc-navy) !important; color:#fff; }
    .bg-navy-2 { background: var(--smc-navy-2) !important; color:#fff; }
    .bg-soft-navy { background: #f3f6fb !important; }
    .border-soft { border: 1px solid rgba(11,31,58,.12); border-radius: var(--radius-inner); }

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
    .badge-bahrain{
      background: linear-gradient(90deg, var(--bh-red), var(--bh-red-2));
      color:#fff;
      border-radius:999px;
      padding:.45rem .9rem;
      font-weight:800;
      letter-spacing:.2px;
      box-shadow:0 6px 16px rgba(206,17,38,.28);
      display:inline-flex; align-items:center; gap:.5rem;
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

    .btn-bh{
      --a: var(--bh-red);
      --b: var(--bh-red-2);
      background: linear-gradient(90deg, var(--a), var(--b));
      color:#fff; border:0; border-radius:999px; padding:.85rem 1.35rem; font-weight:800;
      box-shadow: 0 10px 22px rgba(206,17,38,.3);
    }
    .btn-bh:hover{ filter: brightness(1.03); color:#fff; }

    .flag-dot{ width:.58rem; height:.58rem; background: var(--bh-red); border-radius:50%; display:inline-block; box-shadow:0 0 0 3px rgba(206,17,38,.12); }

    /* ======== HERO (Navy + Gold + Bahrain Accent) ======== */
    .hero-wrap{
      border-radius: var(--radius-outer);
      color:#fff;
      padding: clamp(1.25rem, 2.8vw, 2rem);
      background:
        radial-gradient(900px 320px at 5% 0%, rgba(255, 216, 77, 0.18), rgba(0,0,0,0) 70%),
        radial-gradient(900px 340px at 97% 30%, rgba(206,17,38,.18), rgba(0,0,0,0) 60%),
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
    .hero-ribbon{
      display:inline-flex; align-items:center; gap:.5rem;
      background:#fff; color: var(--bh-red); font-weight:800; border-radius:999px;
      padding:.4rem .8rem; box-shadow:0 8px 20px rgba(255,255,255,.08), 0 10px 20px rgba(206,17,38,.18) inset;
    }
    .hero-ribbon .spark{ color: var(--smc-gold-2); }

    /* ======== BAHRAIN PROGRAM CARD ======== */
    .program-card{
      border-radius: var(--radius-outer);
      overflow: hidden;
      box-shadow: var(--shadow);
      border:0;
      background:#fff;
    }
    .program-left{
      background:
        radial-gradient(900px 320px at 10% -20%, rgba(255,216,77,.16), rgba(255,216,77,0) 60%),
        radial-gradient(900px 320px at 95% 120%, rgba(206,17,38,.16), rgba(206,17,38,0) 60%),
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

    /* ======== CTA (Soft Panel) ======== */
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
      --grad-a: var(--bh-red);
      --grad-b: var(--bh-red-2);
      background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
      color:#fff; border:0; border-radius:999px; padding:.9rem 1.6rem; font-weight:800; letter-spacing:.2px;
      text-decoration:none; display:inline-flex; align-items:center; gap:.6rem;
      box-shadow:0 12px 26px rgba(206,17,38,.28);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      position:relative; isolation:isolate; white-space:nowrap;
    }
    .cta-btn:hover,.cta-btn:focus{ transform: translateY(-1px); box-shadow:0 16px 34px rgba(206,17,38,.34); filter:brightness(1.03); color:#fff; }
    .cta-btn::after{ content:"✦ ✦ ✦"; font-size:.85rem; color:var(--smc-gold-2); position:absolute; right:-2rem; top:50%; transform:translateY(-50%); opacity:.95; pointer-events:none; }
    @media (max-width:575.98px){ .cta-btn::after{ right:-1.6rem; font-size:.8rem; } }
    @media (prefers-reduced-motion: reduce){ .cta-btn { transition:none !important; } }
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
            <div class="hero-ribbon mb-3" aria-label="Bahrain Program">
              <span class="flag-dot" aria-hidden="true"></span>
              <span>BAHRAIN PROGRAM</span>
              <span class="spark">✦</span>
              <span role="img" aria-label="Bahrain Flag">🇧🇭</span>
            </div>

            <h1 class="display-6 fw-bold mb-3">
              Right Person for the Right Job — Bahrain‑Ready Placements
            </h1>
            <p class="lead mb-4">
              SMC Manpower Agency Philippines Company is duly authorized and licensed by POEA under license no.
              <strong>DMW-062-LB-03232023-R</strong> to recruit, hire, and process manpower for its accredited foreign
              principals, including employers in <strong>Bahrain</strong>.
            </p>

            <div class="d-flex gap-3 mb-2 hero-bullet">
              <i class="fa-solid fa-circle-check fa-lg mt-1"></i>
              <div>
                <div class="fw-semibold">Professional Recruitment</div>
                <div class="small opacity-75">Ethical matching and responsible placement for Bahrain‑based clients</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-4 hero-bullet">
              <i class="fa-solid fa-globe fa-lg mt-1"></i>
              <div>
                <div class="fw-semibold">GCC‑Aware Support</div>
                <div class="small opacity-75">Guidance from screening to deployment with GCC norms in mind</div>
              </div>
            </div>

            <a href="#about" class="btn btn-gold btn-lg fw-semibold">Learn More</a>
            <a href="./applicant.php" class="btn btn-bh btn-lg fw-semibold ms-2">View Applicants</a>
          </div>

          <div class="col-lg-6">
            <img class="img-fluid rounded-4 shadow"
                 src="../resources/img/hero.jpeg"
                 alt="SMC Bahrain Program — Office and Recruitment">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- 🇧🇭 BAHRAIN PROGRAM (Directly under HERO) -->
  <section id="bahrain" class="py-4">
    <div class="container">
      <div class="card program-card">
        <div class="row g-0 align-items-stretch">
          <div class="col-md-6 program-left">
            <div class="h-100 p-4 p-lg-5 d-flex flex-column justify-content-center">
              <span class="badge-bahrain"><i class="fa-solid fa-flag"></i> Bahrain Program</span>
              <h2 class="fw-bold mt-3 mb-2">For Bahraini Employers</h2>
              <p class="mb-4" style="opacity:.95;">
                This international landing page is dedicated to <strong>Bahrain</strong>. We deploy
                <strong>Filipino Skilled Workers</strong> to Bahraini households and employers through a safe,
                compliant, and well‑guided process with proper screening, documentation, and clear expectations
                for both employer and worker.
              </p>

              <ul class="list-unstyled m-0">
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-shield-halved fs-5 text-gold"></i>
                  <span><strong>Compliance & Safety:</strong> Verified documents and step‑by‑step support</span>
                </li>
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-language fs-5 text-gold"></i>
                  <span><strong>Culture & Communication:</strong> Orientation to Bahrain norms and household expectations</span>
                </li>
                <li class="d-flex gap-3">
                  <i class="fa-solid fa-handshake-angle fs-5 text-gold"></i>
                  <span><strong>Responsible Placement:</strong> Transparent terms and ethical recruitment</span>
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
                    <div class="fw-semibold text-navy">Experience & Skills</div>
                    <div class="small text-muted">Childcare, cleaning, laundry, cooking</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft">
                    <div class="fw-semibold text-navy">Background & References</div>
                    <div class="small text-muted">Work history and contactable refs</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft">
                    <div class="fw-semibold text-navy">Health & Fitness</div>
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
                Note: Deployment is subject to documentation, employer verification, and applicable country regulations.
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
                           alt="SMC Bahrain — Trusted Recruitment"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">SMC Manpower Agency Co.</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Trusted Recruitment Agency</h3>
                        <p class="text-muted mb-4">Excellence in supplying trusted and skilled Household Service Professionals for Bahrain employers.</p>

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
                           alt="SMC Bahrain — Meticulous Selection"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Professional and Thorough</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Meticulous Selection Process</h3>
                        <p class="text-muted mb-4">We consistently present qualified candidates ready for Bahrain‑based roles.</p>

                        <div class="d-flex gap-3 mb-3">
                          <i class="fa-solid fa-file-circle-check text-gold fs-4"></i>
                          <div>
                            <div class="fw-semibold text-navy">Requirements Support</div>
                            <div class="small text-muted">Checklists, updates, and assistance</div>
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
                           alt="SMC Bahrain — Verified and Secure"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Safety and Compliance</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Verified and Secure</h3>
                        <p class="text-muted mb-4">We prioritize screening, verification, and proper documentation for Bahrain placements.</p>

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
                           alt="SMC Bahrain — Guided Process"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Trusted Assistance</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Guided Step by Step</h3>
                        <p class="text-muted mb-4">We assist applicants at every stage—from requirements to deployment.</p>

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
                           alt="SMC Bahrain — Work Abroad Ready"
                           style="height:340px; object-fit:cover;">
                    </div>
                    <div class="col-md-6 bg-white">
                      <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
                        <span class="badge-soft align-self-start">Global Opportunities</span>
                        <h3 class="fw-bold mt-3 mb-2 text-navy">Work Abroad Ready — Bahrain</h3>
                        <p class="text-muted mb-4">Efficient documentation guidance to make applications smooth and clear.</p>

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
              We are committed to worker well‑being and client satisfaction through competitive compensation support,
              comprehensive guidance, and a service culture built on clarity and respect. SMC recognizes the global
              demand for talent and provides <strong>Bahrain‑ready, personalized recruitment</strong> so employers are
              well‑served and Filipino workers are responsibly supported overseas.
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
              <div class="mb-3"><i class="fa-solid fa-award fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">15</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Years of Experience</div>
              <h5 class="fw-semibold mb-2 text-navy">Efficient Longevity Service</h5>
              <p class="text-muted small mb-0">Dependable, long‑lasting service built on consistency, streamlined processes, and quality support at every stage.</p>
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
              <p class="text-muted small mb-0">We provide resources and clear guidance. Our goal is not just to find a job, but to support long‑term growth.</p>
            </div>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="col-lg-4 col-md-12">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">100%</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3">Top‑Tier Services</div>
              <h5 class="fw-semibold mb-2 text-navy">Industry‑Wide Opportunities</h5>
              <p class="text-muted small mb-0">High‑quality pathways across various industries with job placement, counseling, and skills development programs.</p>
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
            Hire reliable, properly screened Filipino Skilled Workers for Bahrain.
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