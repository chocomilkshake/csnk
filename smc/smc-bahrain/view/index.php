<?php
  // Set the active page for navbar highlighting
  $page = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SMC Manpower Agency Co. — Bahrain Program | Trusted Recruitment</title>

  <!-- SEO -->
  <meta name="description" content="SMC Manpower Agency Co. — DMW-licensed Philippine recruitment agency providing Bahrain-ready, compliant and ethical placement of Filipino skilled workers.">
  <meta name="keywords" content="Bahrain recruitment, Filipino workers Bahrain, household service worker Bahrain, GCC recruitment agency, ethical recruitment, DMW licensed">
  <meta name="theme-color" content="#0B1F3A">
  <link rel="canonical" href="https://example.com/view/index.php">

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

  <!-- ✅ FAVICONS (root + fallback when file is under /view/) -->
  <link rel="icon" type="image/png" href="/resources/img/smc.png" />
  <link rel="shortcut icon" href="/resources/img/smc.png" />
  <link rel="apple-touch-icon" href="/resources/img/smc.png" />
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <link rel="apple-touch-icon" href="../resources/img/smc.png" />

  <!-- Performance hints -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

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
      --ink: #0E1A2B;             /* Primary body text (navy-ish) */
      --muted: #6c757d;           /* Muted text (gray) */
      --card: #ffffff;
      --border: #e9ecef;
      --shadow: 0 12px 28px rgba(7, 18, 38, .16);
      --radius-outer: 1.25rem;
      --radius-inner: 1rem;
      /* ======== BAHRAIN ACCENT ======== */
      --bh-red: #CE1126;      /* Bahrain flag red */
      --bh-red-2: #B10F20;    /* Darker Bahrain red */
    }

    body { color: var(--ink); background: #f3f6fb; }

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

    /* ======== STICKY CTA (mobile first) ======== */
    .sticky-cta{
      position: sticky; bottom: 0; z-index: 1030;
      background:#fff; border-top:1px solid rgba(11,31,58,.1);
      padding:.6rem; display:none;
    }
    @media (max-width: 991.98px){ .sticky-cta{ display:block; } }

    /* ======== FLOAT ACTION ======== */
    .fab{
      position:fixed; right:16px; bottom:88px; z-index:1030; display:flex; flex-direction:column; gap:.5rem;
    }
    .fab a{ width:48px; height:48px; border-radius:50%; display:grid; place-items:center; color:#fff; text-decoration:none; box-shadow:0 10px 20px rgba(0,0,0,.16); }
    .fab .call{ background: linear-gradient(180deg, #1cc88a, #13a06c); }
    .fab .wa{ background: linear-gradient(180deg, #25d366, #1ebe57); }
    .fab .mail{ background: linear-gradient(180deg, #4e73df, #3757c7); }
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
              <span>• GCC-Aware •</span>
              <span role="img" aria-label="Bahrain Flag">🇧🇭</span>
            </div>

            <h1 class="display-6 fw-bold mb-3">
              Right Person for the Right Job — <span class="text-gold">Bahrain‑Ready Placements</span>
            </h1>
            <p class="lead mb-4">
              SMC Manpower Agency Philippines Company is duly authorized and licensed by POEA/DMW under
              license no. <strong>DMW-062-LB-03232023-R</strong> to recruit, hire, and process manpower
              for accredited foreign principals, including employers in <strong>Bahrain</strong>.
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

            <div class="d-flex flex-wrap gap-2">
              <a href="./applicant.php" class="btn btn-gold btn-lg fw-semibold">
                View Applicants <i class="fa-solid fa-users ms-2"></i>
              </a>
              <a href="#contact" class="btn btn-outline-navy btn-lg fw-semibold">
                Contact Us
              </a>
            </div>
          </div>

          <div class="col-lg-6">
            <img class="img-fluid rounded-4 shadow"
                 src="../resources/img/hero.jpeg"
                 alt="SMC Manpower — Bahrain Program Hero">
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TRUST BAR -->
  <section class="py-3">
    <div class="container">
      <div class="d-flex flex-wrap gap-2 gap-md-3 align-items-center justify-content-center">
        <span class="badge-bahrain"><i class="fa-solid fa-flag"></i> Bahrain‑Focused</span>
        <span class="badge-gold"><i class="fa-solid fa-id-card-clip me-1"></i> DMW Licensed</span>
        <span class="badge-soft"><i class="fa-solid fa-shield-halved me-1 text-bh-red"></i> Compliance‑First</span>
        <span class="badge-soft"><i class="fa-solid fa-comments me-1 text-bh-red"></i> Culture & Communication</span>
        <span class="badge-soft"><i class="fa-solid fa-handshake-angle me-1 text-bh-red"></i> Ethical Recruitment</span>
        <span class="badge-soft"><i class="fa-solid fa-clock me-1 text-bh-red"></i> Clear Timelines</span>
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
              <span class="badge-bahrain"><i class="fa-solid fa-flag"></i> Bahrain Program</span>
              <h2 class="fw-bold mt-3 mb-2">For Bahraini Employers</h2>
              <p class="mb-4" style="opacity:.95;">
                This international landing page is dedicated to <strong>Bahrain</strong>. We deploy
                <strong>Filipino Skilled Workers</strong> to Bahraini households and employers through a safe,
                compliant, and well‑guided process with proper screening, documentation, and clear expectations.
              </p>

              <ul class="list-unstyled m-0">
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-shield-halved fs-5 text-gold"></i>
                  <span><strong>Compliance & Safety:</strong> Verified documents and end‑to‑end support</span>
                </li>
                <li class="d-flex gap-3 mb-2">
                  <i class="fa-solid fa-language fs-5 text-gold"></i>
                  <span><strong>Culture & Communication:</strong> Orientation aligned to Bahrain norms</span>
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

  <!-- SECTORS WE SERVE -->
  <section class="py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft">Bahrain</span>
        <h2 class="fw-bold text-navy mt-2">Sectors We Serve</h2>
        <p class="text-muted mb-0">We connect Bahrain employers with skilled Filipino talent across priority roles.</p>
      </div>
      <div class="row g-3 g-md-4">
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-house-chimney-user"></i></div>
            <h6 class="fw-bold text-navy mb-1">Household Service</h6>
            <div class="small text-muted">Nannies, housekeepers, cooks, elderly care aides</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-utensils"></i></div>
            <h6 class="fw-bold text-navy mb-1">Hospitality</h6>
            <div class="small text-muted">Kitchen staff, servers, room attendants, baristas</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-building"></i></div>
            <h6 class="fw-bold text-navy mb-1">Facilities & Cleaning</h6>
            <div class="small text-muted">Cleaners, janitors, maintenance assistants</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-store"></i></div>
            <h6 class="fw-bold text-navy mb-1">Retail & Services</h6>
            <div class="small text-muted">Sales assistants, cashiers, stock clerks</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-hand-holding-medical"></i></div>
            <h6 class="fw-bold text-navy mb-1">Healthcare (Assistive)</h6>
            <div class="small text-muted">Caregivers, support aides, non‑clinical roles</div>
          </div>
        </div>
        <div class="col-6 col-lg-4">
          <div class="feature-tile text-center">
            <div class="icon-hex mx-auto mb-3"><i class="fa-solid fa-truck-fast"></i></div>
            <h6 class="fw-bold text-navy mb-1">Logistics Support</h6>
            <div class="small text-muted">Helpers, packers, basic warehouse support</div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- HOW IT WORKS (6 Steps) -->
  <section class="py-5 bg-soft-navy">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft">Process</span>
        <h2 class="fw-bold text-navy mt-2">How Recruitment Works</h2>
        <p class="text-muted mb-0">Transparent, organized, and Bahrain‑aligned workflow from requisition to deployment.</p>
      </div>
      <div class="row g-3 g-md-4">
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-file-signature"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">1) Job Requisition</h6>
                <div class="small text-muted">Role details, headcount, scope, salary, benefits, timeline</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-magnifying-glass"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">2) Sourcing & Screening</h6>
                <div class="small text-muted">Experience, references, background, skills checks</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-video"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">3) Interviews</h6>
                <div class="small text-muted">Client interviews (online/in‑person), shortlisting, feedback loop</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-folder-open"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">4) Documentation</h6>
                <div class="small text-muted">DMW/POEA processing, medical, clearances, employer docs</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-passport"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">5) Visa & Travel</h6>
                <div class="small text-muted">Visa coordination, ticketing, pre‑departure orientation</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-people-carry-box"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">6) Deployment & Support</h6>
                <div class="small text-muted">Arrival coordination and check‑ins for a smooth start</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <p class="small text-muted mt-3 mb-0">
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
            <span class="badge-soft mb-2">For Employers</span>
            <h3 class="fw-bold text-navy mb-3">What You Get</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span><strong>Curated Candidates:</strong> Skills‑matched profiles with verified references</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span><strong>Documentation Help:</strong> Guidance on requirements, forms, and timing</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span><strong>Clear Communication:</strong> Updates at each stage; single point of contact</span></li>
              <li class="d-flex gap-3"><i class="fa-solid fa-circle-check text-bh-red mt-1"></i> <span><strong>After‑Deployment Support:</strong> Check‑ins for smoother onboarding</span></li>
            </ul>
            <div class="mt-4">
              <a href="#contact" class="btn btn-navy">Start a Requisition <i class="fa-solid fa-arrow-right ms-2"></i></a>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="p-4 p-md-5 bg-white border-soft shadow-soft h-100 rounded-4">
            <span class="badge-soft mb-2">For Filipino Workers</span>
            <h3 class="fw-bold text-navy mb-3">How We Support You</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span><strong>Ethical Recruitment:</strong> No illegal fees; transparency on terms</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span><strong>Orientation:</strong> Culture, expectations, and safety briefings</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span><strong>Guidance:</strong> Documents, medicals, and travel preparation</span></li>
              <li class="d-flex gap-3"><i class="fa-solid fa-heart text-bh-red mt-1"></i> <span><strong>Continued Care:</strong> Check‑ins post‑deployment</span></li>
            </ul>
            <div class="mt-4">
              <a href="./applicant.php" class="btn btn-bh">Apply for Bahrain <i class="fa-solid fa-arrow-right ms-2"></i></a>
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
        <span class="badge-soft">Standards</span>
        <h2 class="fw-bold text-navy mt-2">Compliance & Ethical Hiring</h2>
        <p class="text-muted mb-0">We prioritize safety, dignity, and lawful processes for all parties.</p>
      </div>
      <div class="row g-3 g-md-4">
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex gap-3">
              <div class="icon-hex"><i class="fa-solid fa-shield-halved"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">Verified Documentation</h6>
                <div class="small text-muted">IDs, clearances, contracts, and fit‑to‑work checks</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex gap-3">
              <div class="icon-hex"><i class="fa-solid fa-scale-balanced"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">Lawful, Transparent Terms</h6>
                <div class="small text-muted">Clarity on responsibilities, compensation, and timelines</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="feature-tile h-100">
            <div class="d-flex gap-3">
              <div class="icon-hex"><i class="fa-solid fa-hand-holding-heart"></i></div>
              <div>
                <h6 class="fw-bold text-navy mb-1">Worker Welfare</h6>
                <div class="small text-muted">Respectful, safe placements with support channels</div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p class="small text-muted mt-3 mb-0">
        We follow ethical recruitment practices aligned with relevant regulations in Bahrain and the Philippines.
      </p>
    </div>
  </section>

  <!-- Slideshow (Card Style Carousel) -->
  <section class="py-4">
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
                      <img src="../resources/img/recruitment.png" class="w-100" alt="Recruitment Overview" style="height:340px; object-fit:cover;">
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
                      <img src="../resources/img/selection.png" class="w-100" alt="Selection Process" style="height:340px; object-fit:cover;">
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
                      <img src="../resources/img/verified.png" class="w-100" alt="Compliance" style="height:340px; object-fit:cover;">
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
                      <img src="../resources/img/guide.png" class="w-100" alt="Guidance" style="height:340px; object-fit:cover;">
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
                      <img src="../resources/img/abroad.png" class="w-100" alt="Work Abroad" style="height:340px; object-fit:cover;">
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
                          <a href="./applicant.php" class="btn btn-gold rounded-pill px-4">Apply Now</a>
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

  <!-- METRICS / COUNTERS -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="15">0</div>
          <div class="counter-label">Years of Service</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="100">0</div>
          <div class="counter-label">% Compliance Focus</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="500">0</div>
          <div class="counter-label">+ Screened Candidates</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="24">0</div>
          <div class="counter-label">Hrs Response Window</div>
        </div>
      </div>
    </div>
  </section>

  <!-- WHY CHOOSE SMC -->
  <section id="about" class="py-5 bg-soft-navy">
    <div class="container">

      <!-- Section Header -->
      <div class="text-center mb-5 why-header">
        <span class="text-uppercase text-muted fw-semibold small">Why Choose</span>

        <div class="my-2">
          <img src="../resources/img/smc.png" alt="SMC Manpower Agency Co." style="height:60px;">
        </div>

        <div class="row">
          <div class="col-lg-8 col-md-10 mx-auto">
            <p class="text-muted mb-0">
              We are committed to worker well‑being and client satisfaction through clear guidance,
              organized processes, and respectful service. SMC recognizes the global demand for
              talent and provides <strong>Bahrain‑ready, personalized recruitment</strong> so that
              employers are well‑served and Filipino workers are responsibly supported overseas.
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

  <!-- TESTIMONIALS -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft">Testimonials</span>
        <h2 class="fw-bold text-navy mt-2">What Clients & Workers Say</h2>
        <p class="text-muted mb-0">Real experiences from Bahrain clients and hired Filipino workers.</p>
      </div>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="p-4 border-soft bg-white h-100 rounded-4 shadow-soft">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-hex"><i class="fa-solid fa-user-tie"></i></div>
              <div>
                <div class="fw-bold text-navy">A. Al Khalifa</div>
                <div class="small text-muted">Household Employer — Manama</div>
              </div>
            </div>
            <p class="mb-0 text-muted">“Clear communication, organized process, and responsible matching. We’re satisfied with the placement and onboarding support.”</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-4 border-soft bg-white h-100 rounded-4 shadow-soft">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-hex"><i class="fa-solid fa-briefcase"></i></div>
              <div>
                <div class="fw-bold text-navy">B. Santos</div>
                <div class="small text-muted">Hired Worker — HSW</div>
              </div>
            </div>
            <p class="mb-0 text-muted">“The orientation helped me understand what to expect in Bahrain. The agency guided me from documents to departure.”</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="p-4 border-soft bg-white h-100 rounded-4 shadow-soft">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="icon-hex"><i class="fa-solid fa-hotel"></i></div>
              <div>
                <div class="fw-bold text-navy">Hospitality Client</div>
                <div class="small text-muted">Bahrain</div>
              </div>
            </div>
            <p class="mb-0 text-muted">“Qualified candidates and timely updates. Professional experience end‑to‑end.”</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- PARTNER LOGOS (placeholders) -->
  <section class="py-4 bg-soft-navy">
    <div class="container">
      <div class="text-center text-muted small mb-3">Trusted by Employers</div>
      <div class="d-flex flex-wrap justify-content-center align-items-center gap-4 opacity-75">
        <img src="../resources/img/partner1.png" alt="Partner 1" style="height:34px;">
        <img src="../resources/img/partner2.png" alt="Partner 2" style="height:34px;">
        <img src="../resources/img/partner3.png" alt="Partner 3" style="height:34px;">
        <img src="../resources/img/partner4.png" alt="Partner 4" style="height:34px;">
      </div>
    </div>
  </section>

  <!-- FAQ -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft">FAQ</span>
        <h2 class="fw-bold text-navy mt-2">Frequently Asked Questions</h2>
        <p class="text-muted mb-0">Fast answers for Bahrain employers and applicants.</p>
      </div>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="accordion" id="faqEmployers">
            <div class="accordion-item">
              <h2 class="accordion-header" id="fe1">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fe1c" aria-expanded="true" aria-controls="fe1c">
                  How long does hiring usually take?
                </button>
              </h2>
              <div id="fe1c" class="accordion-collapse collapse show" aria-labelledby="fe1" data-bs-parent="#faqEmployers">
                <div class="accordion-body">
                  It depends on role, documentation readiness, and interview availability. We outline timelines after we receive your job requisition and required documents.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fe2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fe2c" aria-expanded="false" aria-controls="fe2c">
                  What employer documents are needed?
                </button>
              </h2>
              <div id="fe2c" class="accordion-collapse collapse" aria-labelledby="fe2" data-bs-parent="#faqEmployers">
                <div class="accordion-body">
                  Typically, company or household identification, contract details, and role descriptions. We provide a checklist tailored to Bahrain requirements.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fe3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fe3c" aria-expanded="false" aria-controls="fe3c">
                  How do you match candidates?
                </button>
              </h2>
              <div id="fe3c" class="accordion-collapse collapse" aria-labelledby="fe3" data-bs-parent="#faqEmployers">
                <div class="accordion-body">
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
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#fa1c" aria-expanded="true" aria-controls="fa1c">
                  What documents do applicants prepare?
                </button>
              </h2>
              <div id="fa1c" class="accordion-collapse collapse show" aria-labelledby="fa1" data-bs-parent="#faqApplicants">
                <div class="accordion-body">
                  Government IDs, resume, references, clearances, fit‑to‑work medical, and other requirements. We give a guided checklist and updates.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fa2">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fa2c" aria-expanded="false" aria-controls="fa2c">
                  Are there fees for workers?
                </button>
              </h2>
              <div id="fa2c" class="accordion-collapse collapse" aria-labelledby="fa2" data-bs-parent="#faqApplicants">
                <div class="accordion-body">
                  We follow ethical recruitment practices and provide clear guidance on lawful, applicable costs. No illegal fees.
                </div>
              </div>
            </div>

            <div class="accordion-item">
              <h2 class="accordion-header" id="fa3">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#fa3c" aria-expanded="false" aria-controls="fa3c">
                  Can I apply if I have no overseas experience?
                </button>
              </h2>
              <div id="fa3c" class="accordion-collapse collapse" aria-labelledby="fa3" data-bs-parent="#faqApplicants">
                <div class="accordion-body">
                  Yes. We assess your skills and trainability. Relevant local experience and clear references help your application.
                </div>
              </div>
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

  <!-- Sticky CTA (Mobile) -->
  <div class="sticky-cta">
    <div class="container">
      <div class="d-flex gap-2">
        <a href="./applicant.php" class="btn btn-bh w-100">View Applicants</a>
        <a href="#contact" class="btn btn-outline-navy w-100">Contact</a>
      </div>
    </div>
  </div>

  <!-- Floating Quick Actions -->
  <div class="fab" aria-label="Quick actions">
    <a href="tel:09393427412" class="call" title="Call">
      <i class="fa-solid fa-phone"></i>
    </a>
    <a href="https://wa.me/639393427412" target="_blank" rel="noopener" class="wa" title="WhatsApp">
      <i class="fa-brands fa-whatsapp"></i>
    </a>
    <a href="mailto:smcphilippines.marketing@gmail.com" class="mail" title="Email">
      <i class="fa-solid fa-envelope"></i>
    </a>
  </div>

  <!-- ✅ Reusable Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- JSON-LD Organization (basic) -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "SMC Manpower Agency Co.",
    "url": "https://example.com",
    "logo": "https://example.com/resources/img/smc.png",
    "sameAs": []
  }
  </script>

  <!-- Bootstrap JS (bundle includes Popper + Carousel) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Policy Modals Handler -->
  <script src="../resources/js/policy-modals.js"></script>

  <!-- Small Counters Animation -->
  <script>
    (function(){
      const counters = document.querySelectorAll('.counter-number');
      const animate = (el) => {
        const target = +el.getAttribute('data-count');
        const duration = 1200;
        const start = performance.now();
        const step = (now) => {
          const p = Math.min((now - start) / duration, 1);
          const val = Math.floor(p * target);
          el.textContent = val.toLocaleString();
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      };
      let triggered = false;
      const onScroll = () => {
        if (triggered) return;
        const rect = counters[0]?.getBoundingClientRect();
        if (!rect) return;
        if (rect.top < window.innerHeight) {
          counters.forEach(animate);
          triggered = true;
          window.removeEventListener('scroll', onScroll);
        }
      };
      window.addEventListener('scroll', onScroll);
      onScroll();
    })();
  </script>

</body>
</html>