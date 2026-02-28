<?php
  // Set the active page for navbar highlighting
  $page = 'home';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SMC Manpower Agency Co. — Bahrain Program | Trusted Recruitment</title>

  <!-- SEO -->
  <meta name="description" content="SMC Manpower Agency Co. — DMW-licensed Philippine recruitment agency providing Bahrain-ready, compliant, and ethical placement of Filipino skilled workers.">
  <meta name="keywords" content="Bahrain recruitment, Filipino workers Bahrain, household service worker Bahrain, GCC recruitment agency, ethical recruitment, DMW licensed">
  <meta name="theme-color" content="#0B1F3A">

  <!-- Open Graph -->
  <meta property="og:title" content="SMC Manpower Agency Co. — Bahrain Program" />
  <meta property="og:description" content="Ethical, compliant, and Bahrain-ready placements for Filipino skilled workers." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="../resources/img/hero.jpeg" />
  <meta property="og:url" content="https://example.com/view/index.php" />

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="SMC Manpower Agency Co. — Bahrain Program">
  <meta name="twitter:description" content="Ethical, compliant, and Bahrain-ready placements for Filipino skilled workers.">
  <meta name="twitter:image" content="../resources/img/hero.jpeg">

  <!-- ✅ FAVICONS (root) -->
  <link rel="icon" type="image/png" href="/resources/img/smc.png" />
  <link rel="shortcut icon" href="/resources/img/smc.png" />
  <link rel="apple-touch-icon" href="/resources/img/smc.png" />
  <!-- ✅ Fallback when this file is inside /view/ -->
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <link rel="apple-touch-icon" href="../resources/img/smc.png" />

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Arabic font (used when RTL is active) -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">

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

    html, body { background: #f3f6fb; color: var(--ink); }

    /* When Arabic is active */
    body.rtl {
      direction: rtl;
      font-family: "Noto Kufi Arabic", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
    }
    /* Flip only certain icons on RTL (like arrows) */
    .rtl .flip-rtl { transform: scaleX(-1); }

    /* ======== UTILITIES ======== */
    .text-navy { color: var(--smc-navy) !important; }
    .text-gold { color: var(--smc-gold) !important; }
    .text-bh-red { color: var(--bh-red) !important; }
    .bg-navy { background: var(--smc-navy) !important; color:#fff; }
    .bg-soft-navy { background: #f3f6fb !important; }
    .border-soft { border: 1px solid rgba(11,31,58,.12); border-radius: var(--radius-inner); }
    .shadow-soft { box-shadow: 0 10px 24px rgba(13,29,54,.08); }

    .badge-navy {
      background: var(--smc-navy); color:#fff; border-radius:999px; padding:.45rem .8rem; font-weight:700;
    }
    .badge-gold {
      background: var(--smc-gold); color: var(--smc-navy); border-radius:999px; padding:.45rem .8rem; font-weight:800;
    }
    .badge-soft {
      background: #fff; color: var(--smc-navy); border: 1px solid rgba(11,31,58,.15);
      border-radius: 999px; padding:.45rem .8rem; font-weight:700;
    }
    .badge-bahrain {
      background: linear-gradient(90deg, var(--bh-red), var(--bh-red-2));
      color:#fff; border-radius:999px; padding:.45rem .9rem; font-weight:800; letter-spacing:.2px;
      box-shadow:0 6px 16px rgba(206,17,38,.28); display:inline-flex; align-items:center; gap:.5rem;
    }
    .flag-dot{ width:.58rem; height:.58rem; background: var(--bh-red); border-radius:50%; display:inline-block; box-shadow:0 0 0 3px rgba(206,17,38,.12); }

    .btn-navy{
      background: linear-gradient(180deg, var(--smc-navy-3), var(--smc-navy));
      color:#fff; border:0; border-radius: 999px; padding:.75rem 1.25rem; font-weight:800;
      box-shadow: 0 8px 18px rgba(11,31,58,.25);
    }
    .btn-navy:hover{ filter: brightness(1.03); color:#fff; }
    .btn-gold{
      background: linear-gradient(180deg, var(--smc-gold), var(--smc-gold-2));
      color: #18243b; border:0; border-radius: 999px; padding:.75rem 1.25rem; font-weight:800;
      box-shadow: 0 10px 20px rgba(244,197,66,.28);
    }
    .btn-gold:hover{ filter: brightness(1.02); color:#1b2a41; }
    .btn-outline-navy{
      border-radius:999px; border:2px solid var(--smc-navy); color: var(--smc-navy);
      padding:.65rem 1.2rem; background: transparent; font-weight:800;
    }
    .btn-outline-navy:hover{ background: var(--smc-navy); color:#fff; }
    .btn-bh{
      background: linear-gradient(90deg, var(--bh-red), var(--bh-red-2));
      color:#fff; border:0; border-radius:999px; padding:.85rem 1.35rem; font-weight:900;
      box-shadow: 0 10px 22px rgba(206,17,38,.3);
    }
    .btn-bh:hover{ filter: brightness(1.03); color:#fff; }

    /* ======== HERO ======== */
    .hero-wrap{
      border-radius: var(--radius-outer);
      color:#fff;
      padding: clamp(1.25rem, 2.8vw, 2rem);
      background:
        radial-gradient(900px 320px at 5% 0%, rgba(255, 216, 77, 0.18), rgba(0,0,0,0) 70%),
        radial-gradient(900px 340px at 97% 30%, rgba(206,17,38,.18), rgba(0,0,0,0) 60%),
        linear-gradient(120deg, var(--smc-navy) 30%, var(--smc-navy-2) 70%, #1d3a67 100%);
      box-shadow: var(--shadow);
      overflow: hidden; position: relative; isolation: isolate;
    }
    .hero-wrap::after{
      content:""; position:absolute; inset:0;
      background: radial-gradient(800px 260px at 120% -10%, rgba(5,12,24,0.35), rgba(0,0,0,0) 60%);
      z-index:0;
    }
    .hero-content{ position:relative; z-index:1; }
    .hero-ribbon{
      display:inline-flex; align-items:center; gap:.5rem;
      background:#fff; color: var(--bh-red); font-weight:900; border-radius:999px;
      padding:.4rem .8rem; box-shadow:0 8px 20px rgba(255,255,255,.08), 0 10px 20px rgba(206,17,38,.18) inset;
    }
    .hero-bullet i{ color: var(--smc-gold) !important; }

    /* ======== PROGRAM CARD ======== */
    .program-card{
      border-radius: var(--radius-outer);
      overflow: hidden; box-shadow: var(--shadow); border:0; background:#fff;
    }
    .program-left{
      background:
        radial-gradient(900px 320px at 10% -20%, rgba(255,216,77,.16), rgba(255,216,77,0) 60%),
        radial-gradient(900px 320px at 95% 120%, rgba(206,17,38,.16), rgba(206,17,38,0) 60%),
        linear-gradient(110deg, var(--smc-navy) 0%, var(--smc-navy-2) 100%);
      color:#fff;
    }

    /* ======== CARDS / GRIDS ======== */
    .feature-tile{
      background:#fff; border:1px solid rgba(11,31,58,.08);
      border-radius: var(--radius-inner); padding:1rem; height:100%;
    }
    .icon-hex{
      width:48px; height:48px; display:inline-grid; place-items:center;
      background:linear-gradient(180deg, #fff, #f1f5ff);
      border:1px solid rgba(11,31,58,.12); border-radius:10px; color:var(--bh-red);
      box-shadow:0 4px 12px rgba(11,31,58,.08);
    }

    /* ======== CAROUSEL CARDS ======== */
    .slide-card{
      border-radius: var(--radius-outer); overflow:hidden; border:0; box-shadow: var(--shadow); background:#fff;
    }

    /* ======== CTA PANEL ======== */
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
    .cta-title { font-weight:900; font-size: clamp(1.05rem, 2.1vw, 1.35rem); color:var(--smc-navy); margin:0; line-height:1.35; }
    .cta-actions { display:flex; justify-content:flex-start; }
    @media (min-width:768px){ .cta-actions { justify-content:flex-end; } }
    .cta-btn {
      --grad-a: var(--bh-red); --grad-b: var(--bh-red-2);
      background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
      color:#fff; border:0; border-radius:999px; padding:.9rem 1.6rem; font-weight:900; letter-spacing:.2px;
      text-decoration:none; display:inline-flex; align-items:center; gap:.6rem;
      box-shadow:0 12px 26px rgba(206,17,38,.28);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      position:relative; isolation:isolate; white-space:nowrap;
    }
    .cta-btn:hover,.cta-btn:focus{ transform: translateY(-1px); box-shadow:0 16px 34px rgba(206,17,38,.34); filter:brightness(1.03); color:#fff; }
    .cta-btn::after{ content:"✦ ✦ ✦"; font-size:.85rem; color:var(--smc-gold-2); position:absolute; right:-2rem; top:50%; transform:translateY(-50%); opacity:.95; pointer-events:none; }
    @media (max-width:575.98px){ .cta-btn::after{ right:-1.6rem; font-size:.8rem; } }
    @media (prefers-reduced-motion: reduce){ .cta-btn { transition:none !important; } }

    /* ======== COUNTERS ======== */
    .counter-number{ font-size: clamp(1.6rem, 3.2vw, 2.4rem); font-weight:900; color:var(--smc-navy); }
    .counter-label{ color: var(--muted); font-weight:600; letter-spacing:.25px; }

    /* ======== Floating Language Toggle ======== */
    .lang-toggle{
      position: fixed; top:16px; left:16px; z-index: 1040;
      display:inline-flex; align-items:center; gap:.5rem;
      background:#fff; color: var(--bh-red); border:2px solid var(--bh-red);
      border-radius:999px; padding:.4rem .9rem; font-weight:900;
      box-shadow:0 8px 22px rgba(206,17,38,.18), 0 1px 0 #fff inset;
      cursor:pointer;
    }
    .lang-toggle .dot { width:.5rem; height:.5rem; background:var(--bh-red); border-radius:50%; display:inline-block; }

    /* ======== Accessibility helpers ======== */
    .sr-only {
      position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0;
    }
  </style>
</head>
<body class="bg-soft-navy">

  <!-- Floating Translate Button -->
  <button id="langToggle" class="lang-toggle" type="button" aria-live="polite" aria-pressed="false" title="Translate to Arabic">
    <span class="dot" aria-hidden="true"></span>
    <span id="langToggleLabel">AR</span>
  </button>

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
              <span data-i18n="hero.ribbon">BAHRAIN PROGRAM • GCC‑Aware</span>
              <span role="img" aria-label="Bahrain Flag">🇧🇭</span>
            </div>

            <h1 class="display-6 fw-bold mb-3" data-i18n="hero.title">
              Right Person for the Right Job — <span class="text-gold">Bahrain‑Ready Placements</span>
            </h1>
            <p class="lead mb-4" data-i18n="hero.lead">
              SMC Manpower Agency Philippines Company is duly authorized and licensed by POEA/DMW under license no.
              <strong>DMW-062-LB-03232023-R</strong> to recruit, hire, and process manpower for accredited foreign principals, including employers in <strong>Bahrain</strong>.
            </p>

            <div class="d-flex gap-3 mb-2 hero-bullet">
              <i class="fa-solid fa-circle-check fa-lg mt-1"></i>
              <div>
                <div class="fw-semibold" data-i18n="hero.bullet1_title">Professional Recruitment</div>
                <div class="small opacity-75" data-i18n="hero.bullet1_desc">Ethical matching and responsible placement for Bahrain‑based clients</div>
              </div>
            </div>

            <div class="d-flex gap-3 mb-4 hero-bullet">
              <i class="fa-solid fa-globe fa-lg mt-1"></i>
              <div>
                <div class="fw-semibold" data-i18n="hero.bullet2_title">GCC‑Aware Support</div>
                <div class="small opacity-75" data-i18n="hero.bullet2_desc">Guidance from screening to deployment with GCC norms in mind</div>
              </div>
            </div>

            <div class="d-flex flex-wrap gap-2">
              <a href="#about" class="btn btn-gold" data-i18n="hero.btn_learn">
                Learn More
              </a>
              <a href="./applicant.php" class="btn btn-navy">
                <span data-i18n="hero.btn_applicants">View Applicants</span>
                <i class="fa-solid fa-users ms-2"></i>
              </a>
            </div>
          </div>

          <div class="col-lg-6">
            <img class="img-fluid rounded-4 shadow"
                 src="../resources/img/hero.jpeg"
                 alt="SMC Bahrain Program — Office & team">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TRUST BAR -->
  <section class="py-3">
    <div class="container">
      <div class="d-flex flex-wrap gap-2 gap-md-3 align-items-center justify-content-center">
        <span class="badge-bahrain"><i class="fa-solid fa-flag"></i> <span data-i18n="trust.bahrain_focused">Bahrain‑Focused</span></span>
        <span class="badge-gold"><i class="fa-solid fa-id-card-clip me-1"></i> <span data-i18n="trust.dmw">DMW Licensed</span></span>
        <span class="badge-soft"><i class="fa-solid fa-shield-halved me-1 text-bh-red"></i> <span data-i18n="trust.compliance">Compliance‑First</span></span>
        <span class="badge-soft"><i class="fa-solid fa-comments me-1 text-bh-red"></i> <span data-i18n="trust.culture">Culture & Communication</span></span>
        <span class="badge-soft"><i class="fa-solid fa-handshake-angle me-1 text-bh-red"></i> <span data-i18n="trust.ethical">Ethical Recruitment</span></span>
        <span class="badge-soft"><i class="fa-solid fa-clock me-1 text-bh-red"></i> <span data-i18n="trust.timelines">Clear Timelines</span></span>
      </div>
    </div>
  </section>

  <!-- 🇧🇭 BAHRAIN PROGRAM CARD -->
  <section id="bahrain" class="py-4">
    <div class="container">
      <div class="card program-card">
        <div class="row g-0 align-items-stretch">
          <div class="col-md-6 program-left">
            <div class="h-100 p-4 p-lg-5 d-flex flex-column justify-content-center">
              <span class="badge-bahrain"><i class="fa-solid fa-flag"></i> <span data-i18n="prog.badge">Bahrain Program</span></span>
              <h2 class="fw-bold mt-3 mb-2" data-i18n="prog.title">For Bahraini Employers</h2>
              <p class="mb-4" style="opacity:.95;" data-i18n="prog.desc">
                This international landing page is dedicated to <strong>Bahrain</strong>. We deploy
                <strong>Filipino Skilled Workers</strong> to Bahraini households and employers through a safe,
                compliant, and well‑guided process with proper screening, documentation, and clear expectations.
              </p>

              <ul class="list-unstyled m-0">
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-shield-halved fs-5 text-gold"></i>
                  <span data-i18n="prog.li1"><strong>Compliance & Safety:</strong> Verified documents and end‑to‑end support</span>
                </li>
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-language fs-5 text-gold"></i>
                  <span data-i18n="prog.li2"><strong>Culture & Communication:</strong> Orientation aligned to Bahrain norms</span>
                </li>
                <li class="d-flex gap-3">
                  <i class="fa-solid fa-handshake-angle fs-5 text-gold"></i>
                  <span data-i18n="prog.li3"><strong>Responsible Placement:</strong> Transparent terms and ethical recruitment</span>
                </li>
              </ul>

              <div class="mt-4 d-flex flex-wrap gap-2">
                <a href="./applicant.php" class="btn btn-gold">
                  <span data-i18n="prog.btn_applicants">View Applicants</span> <i class="fa-solid fa-users ms-2"></i>
                </a>
                <a href="#contact" class="btn btn-outline-navy" data-i18n="prog.btn_contact">Contact Us</a>
              </div>
            </div>
          </div>

          <div class="col-md-6 bg-white">
            <div class="p-4 p-lg-5 h-100 d-flex flex-column justify-content-center">
              <h5 class="fw-bold mb-3 text-navy" data-i18n="prog.screen_title">What We Screen</h5>
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft h-100">
                    <div class="fw-semibold text-navy" data-i18n="prog.screen1_t">Experience & Skills</div>
                    <div class="small text-muted" data-i18n="prog.screen1_d">Childcare, cleaning, laundry, cooking</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft h-100">
                    <div class="fw-semibold text-navy" data-i18n="prog.screen2_t">Background & References</div>
                    <div class="small text-muted" data-i18n="prog.screen2_d">Work history and contactable refs</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft h-100">
                    <div class="fw-semibold text-navy" data-i18n="prog.screen3_t">Health & Fitness</div>
                    <div class="small text-muted" data-i18n="prog.screen3_d">Fit‑to‑work compliance</div>
                  </div>
                </div>
                <div class="col-sm-6">
                  <div class="p-3 rounded-3 border-soft h-100">
                    <div class="fw-semibold text-navy" data-i18n="prog.screen4_t">Documents</div>
                    <div class="small text-muted" data-i18n="prog.screen4_d">Valid IDs, clearances, travel readiness</div>
                  </div>
                </div>
              </div>

              <div class="mt-4 small text-muted" data-i18n="prog.note">
                Note: Deployment is subject to documentation, employer verification, and applicable country regulations.
              </div>
            </div>
          </div>
        </div><!-- /row -->
      </div><!-- /card -->
    </div>
  </section>

  <!-- SECTORS WE SERVE -->
  <section class="py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="sectors.badge">Bahrain</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="sectors.title">Sectors We Serve</h2>
        <p class="text-muted mb-0" data-i18n="sectors.subtitle">We connect Bahrain employers with skilled Filipino talent across priority roles.</p>
      </div>
      <div class="row g-3 g-md-4">
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center h-100">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-house-chimney-user"></i></div>
            <h6 class="fw-bold text-navy mb-1" data-i18n="sectors.hsw_t">Household Service</h6>
            <div class="small text-muted" data-i18n="sectors.hsw_d">Nannies, housekeepers, cooks, elderly care aides</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center h-100">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-utensils"></i></div>
            <h6 class="fw-bold text-navy mb-1" data-i18n="sectors.hosp_t">Hospitality</h6>
            <div class="small text-muted" data-i18n="sectors.hosp_d">Kitchen staff, servers, room attendants, baristas</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center h-100">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-building"></i></div>
            <h6 class="fw-bold text-navy mb-1" data-i18n="sectors.fac_t">Facilities & Cleaning</h6>
            <div class="small text-muted" data-i18n="sectors.fac_d">Cleaners, janitors, maintenance assistants</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center h-100">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-store"></i></div>
            <h6 class="fw-bold text-navy mb-1" data-i18n="sectors.retail_t">Retail & Services</h6>
            <div class="small text-muted" data-i18n="sectors.retail_d">Sales assistants, cashiers, stock clerks</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center h-100">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-hand-holding-medical"></i></div>
            <h6 class="fw-bold text-navy mb-1" data-i18n="sectors.health_t">Healthcare (Assistive)</h6>
            <div class="small text-muted" data-i18n="sectors.health_d">Caregivers, support aides, non‑clinical roles</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center h-100">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-truck-fast"></i></div>
            <h6 class="fw-bold text-navy mb-1" data-i18n="sectors.logi_t">Logistics Support</h6>
            <div class="small text-muted" data-i18n="sectors.logi_d">Helpers, packers, basic warehouse support</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS (6 Steps) -->
  <section class="py-5 bg-soft-navy">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="process.badge">Process</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="process.title">How Recruitment Works</h2>
        <p class="text-muted mb-0" data-i18n="process.subtitle">Transparent, organized, and Bahrain‑aligned workflow from requisition to deployment.</p>
      </div>
      <div class="row g-3 g-md-4">
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-file-signature"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="process.s1_t">1) Job Requisition</h6>
                <div class="small text-muted" data-i18n="process.s1_d">Role details, headcount, scope, salary, benefits, timeline</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-magnifying-glass"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="process.s2_t">2) Sourcing & Screening</h6>
                <div class="small text-muted" data-i18n="process.s2_d">Experience, references, background, skills checks</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-video"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="process.s3_t">3) Interviews</h6>
                <div class="small text-muted" data-i18n="process.s3_d">Client interviews (online/in‑person), shortlisting, feedback loop</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-folder-open"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="process.s4_t">4) Documentation</h6>
                <div class="small text-muted" data-i18n="process.s4_d">DMW/POEA processing, medical, clearances, employer docs</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-passport"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="process.s5_t">5) Visa & Travel</h6>
                <div class="small text-muted" data-i18n="process.s5_d">Visa coordination, ticketing, pre‑departure orientation</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-people-carry-box"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="process.s6_t">6) Deployment & Support</h6>
                <div class="small text-muted" data-i18n="process.s6_d">Arrival coordination and check‑ins for a smooth start</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <p class="small text-muted mt-3 mb-0" data-i18n="process.note">
        Note: Employer obligations and timelines vary. We follow lawful, ethical, and transparent processes aligned with Bahrain and Philippine regulations.
      </p>
    </div>
  </section>

  <!-- EMPLOYERS & WORKERS BENEFITS -->
  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="p-4 p-md-5 bg-white border-soft shadow-soft h-100 rounded-4">
            <span class="badge-soft mb-2" data-i18n="benef.emp_badge">For Employers</span>
            <h3 class="fw-bold text-navy mb-3" data-i18n="benef.emp_title">What You Get</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span data-i18n="benef.emp1"><strong>Curated Candidates:</strong> Skills‑matched profiles with verified references</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span data-i18n="benef.emp2"><strong>Documentation Help:</strong> Guidance on requirements, forms, and timing</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span data-i18n="benef.emp3"><strong>Clear Communication:</strong> Updates at each stage; single point of contact</span></li>
              <li class="d-flex gap-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span data-i18n="benef.emp4"><strong>After‑Deployment Support:</strong> Check‑ins for smoother onboarding</span></li>
            </ul>
            <div class="mt-4">
              <a href="#contact" class="btn btn-bh">
                <span data-i18n="benef.emp_cta">Start a Requisition</span>
                <i class="fa-solid fa-arrow-right ms-2 flip-rtl"></i>
              </a>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="p-4 p-md-5 bg-white border-soft shadow-soft h-100 rounded-4">
            <span class="badge-soft mb-2" data-i18n="benef.work_badge">For Filipino Workers</span>
            <h3 class="fw-bold text-navy mb-3" data-i18n="benef.work_title">How We Support You</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span data-i18n="benef.work1"><strong>Ethical Recruitment:</strong> No illegal fees; transparency on terms</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span data-i18n="benef.work2"><strong>Orientation:</strong> Culture, expectations, and safety briefings</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span data-i18n="benef.work3"><strong>Guidance:</strong> Documents, medicals, and travel preparation</span></li>
              <li class="d-flex gap-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span data-i18n="benef.work4"><strong>Continued Care:</strong> Check‑ins post‑deployment</span></li>
            </ul>
            <div class="mt-4">
              <a href="./applicant.php" class="btn btn-outline-navy">
                <span data-i18n="benef.work_cta">Apply for Bahrain</span>
                <i class="fa-solid fa-arrow-right ms-2 flip-rtl"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- COMPLIANCE & ETHICS -->
  <section class="py-5 bg-soft-navy">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="comp.badge">Standards</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="comp.title">Compliance & Ethical Hiring</h2>
        <p class="text-muted mb-0" data-i18n="comp.subtitle">We prioritize safety, dignity, and lawful processes for all parties.</p>
      </div>
      <div class="row g-3 g-md-4">
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex gap-3">
              <div class="icon-hex"><i class="fa-solid fa-shield-halved"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="comp.c1_t">Verified Documentation</h6>
                <div class="small text-muted" data-i18n="comp.c1_d">IDs, clearances, contracts, and fit‑to‑work checks</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex gap-3">
              <div class="icon-hex"><i class="fa-solid fa-scale-balanced"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="comp.c2_t">Lawful, Transparent Terms</h6>
                <div class="small text-muted" data-i18n="comp.c2_d">Clarity on responsibilities, compensation, and timelines</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex gap-3">
              <div class="icon-hex"><i class="fa-solid fa-hand-holding-heart"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1" data-i18n="comp.c3_t">Worker Welfare</h6>
                <div class="small text-muted" data-i18n="comp.c3_d">Respectful, safe placements with support channels</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p class="small text-muted mt-3 mb-0" data-i18n="comp.note">
        We follow ethical recruitment practices aligned with relevant regulations in Bahrain and the Philippines.
      </p>
    </div>
  </section>

  <!-- WHY CHOOSE SMC -->
  <section id="about" class="py-5 bg-soft-navy">
    <div class="container">
      <div class="text-center mb-5">
        <span class="text-uppercase text-muted fw-semibold small" data-i18n="why.badge">Why Choose</span>
        <div class="my-2">
          <img src="../resources/img/smc.png" alt="SMC Manpower Agency Co." class="img-fluid" style="height:60px;">
        </div>
        <div class="row">
          <div class="col-lg-8 col-md-10 mx-auto">
            <p class="text-muted mb-0" data-i18n="why.desc">
              We are committed to worker well‑being and client satisfaction through clear guidance,
              organized processes, and respectful service. SMC recognizes the global demand for
              talent and provides <strong>Bahrain‑ready, personalized recruitment</strong> so that
              employers are well‑served and Filipino workers are responsibly supported overseas.
            </p>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-award fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">15</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3" data-i18n="why.years">Years of Experience</div>
              <h5 class="fw-semibold mb-2 text-navy" data-i18n="why.card1_t">Efficient Longevity Service</h5>
              <p class="text-muted small mb-0" data-i18n="why.card1_d">Dependable, long‑lasting service built on consistency, streamlined processes, and quality support at every stage.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-chart-line fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1" data-i18n="why.card2_h">Career Development</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3" data-i18n="why.card2_tag">Proven Growth Record</div>
              <h5 class="fw-semibold mb-2 text-navy" data-i18n="why.card2_t">Empowering Your Career Journey</h5>
              <p class="text-muted small mb-0" data-i18n="why.card2_d">We provide resources and clear guidance. Our goal is not just to find a job, but to support long‑term growth.</p>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-12">
          <div class="card h-100 border-0 shadow-sm">
            <div class="card-body text-center p-4">
              <div class="mb-3"><i class="fa-solid fa-shield-halved fa-3x text-gold"></i></div>
              <h2 class="fw-bold text-navy mb-1">100%</h2>
              <div class="text-uppercase text-muted fw-semibold small mb-3" data-i18n="why.card3_tag">Top‑Tier Services</div>
              <h5 class="fw-semibold mb-2 text-navy" data-i18n="why.card3_t">Industry‑Wide Opportunities</h5>
              <p class="text-muted small mb-0" data-i18n="why.card3_d">High‑quality pathways across various industries with job placement, counseling, and skills development programs.</p>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <!-- TESTIMONIALS -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="test.badge">Testimonials</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="test.title">What Clients & Workers Say</h2>
        <p class="text-muted mb-0" data-i18n="test.subtitle">Real experiences from Bahrain clients and hired Filipino workers.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="p-4 border-soft bg-white h-100 rounded-4 shadow-soft">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-hex"><i class="fa-solid fa-user-tie"></i></div>
              <div>
                <div class="fw-bold text-navy">A. Al Khalifa</div>
                <div class="small text-muted" data-i18n="test.t1_role">Household Employer — Manama</div>
              </div>
            </div>
            <p class="mb-0 text-muted" data-i18n="test.t1">“Clear communication, organized process, and responsible matching. We’re satisfied with the placement and onboarding support.”</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-4 border-soft bg-white h-100 rounded-4 shadow-soft">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-hex"><i class="fa-solid fa-briefcase"></i></div>
              <div>
                <div class="fw-bold text-navy">B. Santos</div>
                <div class="small text-muted" data-i18n="test.t2_role">Hired Worker — HSW</div>
              </div>
            </div>
            <p class="mb-0 text-muted" data-i18n="test.t2">“The orientation helped me understand what to expect in Bahrain. The agency guided me from documents to departure.”</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-4 border-soft bg-white h-100 rounded-4 shadow-soft">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-hex"><i class="fa-solid fa-hotel"></i></div>
              <div>
                <div class="fw-bold text-navy" data-i18n="test.t3_name">Hospitality Client</div>
                <div class="small text-muted" data-i18n="test.t3_role">Bahrain</div>
              </div>
            </div>
            <p class="mb-0 text-muted" data-i18n="test.t3">“Qualified candidates and timely updates. Professional experience end‑to‑end.”</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="faq.badge">FAQ</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="faq.title">Frequently Asked Questions</h2>
        <p class="text-muted mb-0" data-i18n="faq.subtitle">Fast answers for Bahrain employers and applicants.</p>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="accordion" id="faqEmployers">
            <div class="accordion-item">
              <h2 class="accordion-header" id="fe1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fe1c" aria-expanded="true" aria-controls="fe1c" data-i18n="faq.e.q1">
                  How long does hiring usually take?
                </button>
              </h2>
              <div id="fe1c" class="accordion-collapse collapse show" aria-labelledby="fe1" data-bs-parent="#faqEmployers">
                <div class="accordion-body" data-i18n="faq.e.a1">
                  It depends on role, documentation readiness, and interview availability. We outline timelines after we receive your job requisition and required documents.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fe2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fe2c" aria-expanded="false" aria-controls="fe2c" data-i18n="faq.e.q2">
                  What employer documents are needed?
                </button>
              </h2>
              <div id="fe2c" class="accordion-collapse collapse" aria-labelledby="fe2" data-bs-parent="#faqEmployers">
                <div class="accordion-body" data-i18n="faq.e.a2">
                  Typically, company or household identification, contract details, and role descriptions. We provide a checklist tailored to Bahrain requirements.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fe3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fe3c" aria-expanded="false" aria-controls="fe3c" data-i18n="faq.e.q3">
                  How do you match candidates?
                </button>
              </h2>
              <div id="fe3c" class="accordion-collapse collapse" aria-labelledby="fe3" data-bs-parent="#faqEmployers">
                <div class="accordion-body" data-i18n="faq.e.a3">
                  By role fit, experience, references, and interview performance. We present shortlisted profiles and coordinate your interviews.
                </div>
              </div>
            </div>

          </div>
        </div>

        <div class="col-lg-6">
          <div class="accordion" id="faqApplicants">
            <div class="accordion-item">
              <h2 class="accordion-header" id="fa1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fa1c" aria-expanded="true" aria-controls="fa1c" data-i18n="faq.a.q1">
                  What documents do applicants prepare?
                </button>
              </h2>
              <div id="fa1c" class="accordion-collapse collapse show" aria-labelledby="fa1" data-bs-parent="#faqApplicants">
                <div class="accordion-body" data-i18n="faq.a.a1">
                  Government IDs, resume, references, clearances, fit‑to‑work medical, and other requirements. We give a guided checklist and updates.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fa2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fa2c" aria-expanded="false" aria-controls="fa2c" data-i18n="faq.a.q2">
                  Are there fees for workers?
                </button>
              </h2>
              <div id="fa2c" class="accordion-collapse collapse" aria-labelledby="fa2" data-bs-parent="#faqApplicants">
                <div class="accordion-body" data-i18n="faq.a.a2">
                  We follow ethical recruitment practices and provide clear guidance on lawful, applicable costs. No illegal fees.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fa3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fa3c" aria-expanded="false" aria-controls="fa3c" data-i18n="faq.a.q3">
                  Can I apply if I have no overseas experience?
                </button>
              </h2>
              <div id="fa3c" class="accordion-collapse collapse" aria-labelledby="fa3" data-bs-parent="#faqApplicants">
                <div class="accordion-body" data-i18n="faq.a.a3">
                  Yes. We assess your skills and trainability. Relevant local experience and clear references help your application.
                </div>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- METRICS / COUNTERS -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="15">0</div>
          <div class="counter-label" data-i18n="metrics.yos">Years of Service</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="100">0</div>
          <div class="counter-label" data-i18n="metrics.compliance">% Compliance Focus</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="500">0</div>
          <div class="counter-label" data-i18n="metrics.screened">+ Screened Candidates</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="24">0</div>
          <div class="counter-label" data-i18n="metrics.response">Hrs Response Window</div>
        </div>
      </div>
    </div>
  </section>

  <!-- Contact / Map -->
  <section id="contact" class="py-5 bg-soft-navy">
    <div class="container">
      <div class="text-center mb-4">
        <h2 class="fw-bold mb-1 text-navy" data-i18n="contact.title">Contact and Location</h2>
        <p class="text-muted mb-0" data-i18n="contact.subtitle">Visit our office or reach us using the details below</p>
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

              <h5 class="fw-bold mb-3 text-navy" data-i18n="contact.office_info">Office Information</h5>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                  <div class="fw-semibold text-navy" data-i18n="contact.address_label">Address</div>
                  <div class="text-muted small" data-i18n="contact.address">
                    Unit 1 Eden Townhomes<br>
                    2001 Eden Street corner Pedro Gil Street, Sta. Ana<br>
                    Manila, 1009 Barangay 866, City of Manila,<br>
                    NCR, Sixth District
                  </div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-phone"></i></div>
                <div>
                  <div class="fw-semibold text-navy" data-i18n="contact.phone_label">Phone</div>
                  <div class="text-muted small">0939 342 7412</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-3">
                <div class="text-gold fs-5"><i class="fa-solid fa-envelope"></i></div>
                <div>
                  <div class="fw-semibold text-navy" data-i18n="contact.email_label">Email</div>
                  <div class="text-muted small">smcphilippines.marketing@gmail.com</div>
                </div>
              </div>

              <div class="d-flex gap-3 mb-4">
                <div class="text-gold fs-5"><i class="fa-solid fa-clock"></i></div>
                <div>
                  <div class="fw-semibold text-navy" data-i18n="contact.hours_label">Office Hours</div>
                  <div class="text-muted small" data-i18n="contact.hours">Mon to Sat, 8:00 AM to 5:00 PM</div>
                </div>
              </div>

              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-navy rounded-pill px-4"
                   target="_blank" rel="noopener"
                   href="https://www.google.com/maps?q=2F%20UNIT%201%20EDEN%20TOWNHOUSE%202001%20EDEN%20ST.%20COR%20PEDRO%20GIL%20STA%20ANA%2C%20BARANGAY%20784%2C%20CITY%20OF%20MANILA%2C%20NCR%2C%20FIRST%20DISTRICT">
                  <i class="fa-solid fa-location-arrow me-2 flip-rtl"></i><span data-i18n="contact.directions">Get Directions</span>
                </a>

                <a class="btn btn-outline-navy rounded-pill px-4" href="#home">
                  <i class="fa-solid fa-arrow-up me-2"></i><span data-i18n="contact.back_top">Back to Top</span>
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
          <p class="cta-title" data-i18n="final.cta">
            Hire reliable, properly screened Filipino Skilled Workers for Bahrain.
          </p>

          <div class="cta-actions">
            <a class="btn cta-btn" href="./applicant.php" aria-label="Hire Now">
              <span data-i18n="final.btn">Hire Now!</span> <i class="fa-solid fa-arrow-right flip-rtl"></i>
            </a>
          </div>
        </div>
      </div><!-- /.cta-hire -->
    </div>
  </section>

  <!-- ✅ Reusable Footer -->
  <?php include __DIR__ 
      "prog.btn_contact": "اتصل بنا",
      "prog.screen_title": "ماذا
      "process.s4_d": "إجراءات DMW/POEA، الفحوصات الطبية، التصاريح، وثائق صاحب العمل",
      "process.s5_t": "5) التأشيرة والسفر",
      "process.s5_d": "تنسيق التأشيرة، التذاكر، التوجيه قبل المغادرة",
      "process.s6_t": "6) الإيفاد والدعم",
      "process.s6_d": "تنسيق الوصول والمتابعة لضمان بداية سلسة",
      "process.note": "تختلف الالتزامات والجداول بحسب الدور. نلتزم بالقوانين والشفافية والمعايير الأخلاقية في البحرين والفلبين.",

      "benef.emp_badge": "لأصحاب العمل",
      "benef.emp_title": "ماذا تحصلون عليه",
      "benef.emp1": "<strong>مرشحون مختارون بعناية:</strong> ملفات متطابقة المهارات مع مراجع مؤكدة",
      "benef.emp2": "<strong>مساعدة في الوثائق:</strong> إرشاد حول المتطلبات والنماذج والجداول",
      "benef.emp3": "<strong>تواصل واضح:</strong> تحديثات في كل مرحلة ونقطة اتصال واحدة",
      "benef.emp4": "<strong>دعم بعد الإيفاد:</strong> متابعات لتيسير الاندماج",
      "benef.emp_cta": "ابدأ طلب التوظيف",
      "benef.work_badge": "للعمال الفلبينيين",
      "benef.work_title": "كيف ندعمكم",
      "benef.work1": "<strong>توظيف أخلاقي:</strong> بدون رسوم غير قانونية وشفافية في الشروط",
      "benef.work2": "<strong>توعية:</strong> الثقافة والتوقعات وإرشادات السلامة",
      "benef.work3": "<strong>إرشاد:</strong> الوثائق والفحوصات والاستعداد للسفر",
      "benef.work4": "<strong>رعاية مستمرة:</strong> متابعة بعد الإيفاد",
      "benef.work_cta": "قدّم للبحرين",

      "comp.badge": "المعايير",
      "comp.title": "الامتثال والتوظيف الأخلاقي",
      "comp.subtitle": "نضع السلامة والكرامة والالتزام بالقانون في المقدمة للجميع.",
      "comp.c1_t": "توثيق مُتحقق منه",
      "comp.c1_d": "الهويات، التصاريح، العقود، وفحوصات الجاهزية للعمل",
      "comp.c2_t": "شروط قانونية وشفافة",
      "comp.c2_d": "وضوح المسؤوليات والتعويضات والجداول الزمنية",
      "comp.c3_t": "رفاهية العامل",
      "comp.c3_d": "توظيف محترم وآمن مع قنوات دعم",
      "comp.note": "نلتزم بالممارسات الأخلاقية وفق الأنظمة المعمول بها في البحرين والفلبين.",

      "why.badge": "لماذا نحن",
      "why.desc": "نلتزم برفاهية العاملين ورضا العملاء من خلال الإرشاد الواضح، والعمليات المنظمة، والخدمة المحترمة. وتوفّر SMC <strong>توظيفاً مخصصاً وجاهزاً للبحرين</strong> لخدمة أصحاب العمل ودعم العمال الفلبينيين بمسؤولية.",
      "why.years": "سنوات من الخبرة",
      "why.card1_t": "خدمة فعّالة وطويلة الأمد",
      "why.card1_d": "خدمة يمكن الاعتماد عليها مبنية على الاتساق وتبسيط الإجراءات ودعم الجودة في كل مرحلة.",
      "why.card2_h": "تطوير المسار المهني",
      "why.card2_tag": "سجل نمو مثبت",
      "why.card2_t": "تمكين رحلتك المهنية",
      "why.card2_d": "نوفّر الموارد والإرشاد الواضح. هدفنا ليس إيجاد وظيفة فقط بل دعم النمو الطويل الأمد.",
      "why.card3_tag": "خدمات رفيعة المستوى",
      "why.card3_t": "فرص عبر قطاعات متعددة",
      "why.card3_d": "مسارات عالية الجودة عبر عدة صناعات تشمل التوظيف والإرشاد وبرامج تنمية المهارات.",

      "test.badge": "آراء العملاء والمرشحين",
      "test.title": "ماذا يقول العملاء والعاملون",
      "test.subtitle": "تجارب حقيقية من عملاء البحرين والعمال الفلبينيين المتوظفين.",
      "test.t1_role": "رب أسرة — المنامة",
      "test.t1": "“تواصل واضح، عملية منظمة، ومواءمة مسؤولة. نحن راضون عن التوظيف ودعم الاندماج.”",
      "test.t2_role": "عامل/ة منزلي/ة — HSW",
      "test.t2": "“ساعدتني التوعية على فهم التوقعات في البحرين. ووجّهتني الوكالة من الوثائق حتى السفر.”",
      "test.t3_name": "عميل ضيافة",
      "test.t3_role": "البحرين",
      "test.t3": "“مرشحون مؤهلون وتحديثات في الوقت المناسب. تجربة احترافية من البداية للنهاية.”",

      "faq.badge": "الأسئلة الشائعة",
      "faq.title": "أسئلة متكررة",
      "faq.subtitle": "إجابات سريعة لأصحاب العمل والمتقدمين في البحرين.",
      "faq.e.q1": "كم تستغرق عملية التوظيف عادة؟",
      "faq.e.a1": "يعتمد ذلك على الدور وجهوزية الوثائق وتوفر المقابلات. نشارك جدولاً زمنياً بعد استلام طلب التوظيف ووثائقكم.",
      "faq.e.q
    // Collect default EN content for each [data-i18n] node (text or HTML)
    const nodes = Array.from(document.querySelectorAll('[data-i18n]'));
    nodes.forEach(n => {
      // store original content as data attribute to restore later
      n.dataset.en = n.innerHTML;
    });

    const setLang = (lang) => {
      const html = document.documentElement;
      const body = document.body;
      const toggle = document.getElementById('langToggle');
      const toggleLabel = document.getElementById('langToggleLabel');

      if (lang === 'ar') {
        html.setAttribute('lang', 'ar');
        html.setAttribute('dir', 'rtl');
        body.classList.add('rtl');
        // Apply Arabic translations
        nodes.forEach(n => {
          const key = n.getAttribute('data-i18n');
          const val = I18N_AR[key];
          if (typeof val === 'string') n.innerHTML = val;
        });
        toggle.setAttribute('aria-pressed', 'true');
        toggle.setAttribute('title', 'الرجوع إلى الإنجليزية');
        toggleLabel.textContent = 'EN';
      } else {
        html.setAttribute('lang', 'en');
        html.setAttribute('dir', 'ltr');
        body.classList.remove('rtl');
        // Restore English
        nodes.forEach(n => { n.innerHTML = n.dataset.en; });
        toggle.setAttribute('aria-pressed', 'false');
        toggle.setAttribute('title', 'Translate to Arabic');
        toggleLabel.textContent = 'AR';
      }
      localStorage.setItem('lang', lang);
    };

    // Init
    const saved = localStorage.getItem('lang') || 'en';
    setLang(saved);

    // Toggle handler
    document.getElementById('langToggle').addEventListener('click', () => {
      const current = localStorage.getItem('lang') || 'en';
      setLang(current === 'en' ? 'ar' : 'en');
    });
  </script>
</body>
</html>