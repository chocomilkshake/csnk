<?php
// Set the active page for navbar highlighting
$page = 'about';
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>About — SMC Manpower Agency Co. | Trusted Recruitment</title>

  <!-- SEO -->
  <meta name="description" content="Learn about SMC Manpower Agency Co. — DMW-licensed, compliance-first, and ethically driven recruitment connecting Bahrain-ready and global employers with skilled Filipino talent.">
  <meta name="theme-color" content="#0B1F3A">

  <!-- Open Graph -->
  <meta property="og:title" content="About — SMC Manpower Agency Co." />
  <meta property="og:description" content="DMW-licensed, compliance-first, and ethically driven recruitment." />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="../resources/img/hero1.jpg" />
  <meta property="og:url" content="https://example.com/view/about.php" />

  <!-- ✅ FAVICONS -->
  <link rel="icon" type="image/png" href="/resources/img/smc.png" />
  <link rel="shortcut icon" href="/resources/img/smc.png" />
  <link rel="apple-touch-icon" href="/resources/img/smc.png" />
  <!-- Fallback for /view/ -->
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <link rel="apple-touch-icon" href="../resources/img/smc.png" />

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Arabic font (used when RTL is active) -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Kufi+Arabic:wght@400;700&display=swap" rel="stylesheet">

  <style>
    /* ===========================
       NAVY + GOLD THEME TOKENS
       =========================== */
    :root {
      --smc-navy: #0B1F3A;    /* deep navy */
      --smc-navy-2: #132A4A;  /* secondary navy */
      --smc-navy-3: #1B355C;  /* accent navy */
      --smc-navy-ink: #16243B;/* readable navy text */
      --smc-gold: #FFD84D;    /* gold accent */
      --soft-bg: #f5f8ff;     /* page background sections */
      --soft-border: #e6ecf5; /* soft border */
      --shadow: 0 12px 28px rgba(11, 31, 58, .12);
      --r-out: 1.25rem;
      --r-in: 1rem;

      /* Optional subtle accent */
      --accent-red: #CE1126; /* used minimally for emphasis */
      --accent-red-2: #B10F20;
    }

    html, body { background: #f8f9fb; color: var(--smc-navy-ink); }
    img, svg { max-width: 100%; height: auto; }

    /* RTL language mode */
    body.rtl {
      direction: rtl;
      font-family: "Noto Kufi Arabic", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol";
    }
    .rtl .flip-rtl { transform: scaleX(-1); }

    .text-navy { color: var(--smc-navy) !important; }
    .text-muted-navy { color: #6f7e96 !important; }
    .border-soft { border: 1px solid var(--soft-border); border-radius: var(--r-in); }
    .shadow-soft { box-shadow: 0 10px 24px rgba(13,29,54,.08); }
    .badge-soft {
      background:#fff; border:1px solid rgba(11,31,58,.12); color:var(--smc-navy); border-radius:999px; padding:.45rem .8rem; font-weight:700;
    }
    .badge-gold {
      background: var(--smc-gold); color: var(--smc-navy); border-radius:999px; padding:.45rem .8rem; font-weight:800;
    }
    .btn-navy {
      background: linear-gradient(180deg, var(--smc-navy-3), var(--smc-navy));
      color: #fff; border: 0; border-radius: 999px; padding: .8rem 1.3rem; font-weight: 800;
      box-shadow: 0 12px 26px rgba(11, 31, 58, .22);
    }
    .btn-navy:hover { filter: brightness(1.03); color: #fff; }
    .btn-gold {
      background: linear-gradient(180deg, #ffe169, var(--smc-gold));
      color: #18243b; border: 0; border-radius: 999px; padding: .8rem 1.3rem; font-weight: 800;
      box-shadow: 0 12px 26px rgba(255, 216, 77, .25);
    }
    .btn-gold:hover { filter: brightness(1.03); color: #18243b; }

    /* ===========================
       Floating Language Toggle
       =========================== */
    .lang-toggle{
      position: fixed; top:16px; left:16px; z-index: 1040;
      display:inline-flex; align-items:center; gap:.5rem; background:#fff; color: #B10F20;
      border:2px solid #B10F20; border-radius:999px; padding:.4rem .9rem; font-weight:900;
      box-shadow:0 8px 22px rgba(206,17,38,.18), 0 1px 0 #fff inset; cursor:pointer;
    }
    .lang-toggle .dot { width:.5rem; height:.5rem; background:#CE1126; border-radius:50%; display:inline-block; }

    /* ===========================
       HERO
       =========================== */
    .hero-section {
      background-color: #f8f9fb;
      position: relative; isolation: isolate;
      padding: clamp(2rem, 6vw, 5rem) 0;
    }
    .hero-grid, .hero-gradient {
      position: absolute; inset: 0; z-index: 0; pointer-events: none;
    }
    .hero-grid {
      opacity: .22;
      background-image:
        linear-gradient(to right, rgba(11, 31, 58, .08) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(11, 31, 58, .08) 1px, transparent 1px);
      background-size: 32px 32px, 32px 32px;
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 12%, rgba(0, 0, 0, .85) 40%, rgba(0, 0, 0, .55) 70%, rgba(0, 0, 0, 0) 100%);
    }
    .hero-gradient {
      background:
        radial-gradient(900px 400px at 15% 35%, rgba(255, 216, 77, 0.25), rgba(0, 0, 0, 0) 60%),
        radial-gradient(700px 350px at 80% 45%, rgba(19, 42, 74, .10), rgba(19, 42, 74, 0) 60%),
        linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(240, 244, 255, .6) 60%, rgba(240, 244, 255, 0) 100%);
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, .9) 12%, rgba(0, 0, 0, .95) 85%, rgba(0, 0, 0, 0) 100%);
    }
    .hero-section .container { position: relative; z-index: 1; }
    @media (max-width: 575.98px) { .hero-section { overflow: visible !important; } }

    .hero-pills-abs-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    #heroPills {
      display: inline-flex; width: max-content; max-width: 100%; gap: .5rem; align-items: center;
      padding: .5rem .6rem; border-radius: 999px; box-shadow: 0 4px 15px rgba(11, 31, 58, .08);
      background: #fff; scroll-snap-type: x proximity;
    }
    #heroPills .btn { flex: 0 0 auto; white-space: nowrap; scroll-snap-align: start; }
    .hero-section .btn-light.active { background: var(--smc-navy); color: #fff; border: 0; }

    /* ===========================
       TRUST STRIP
       =========================== */
    .trust-strip .item{
      background:#fff; border:1px solid rgba(11,31,58,.08); border-radius:999px;
      padding:.45rem .8rem; display:inline-flex; align-items:center; gap:.5rem; font-weight:700;
    }

    /* ===========================
       CARDS / PANELS
       =========================== */
    .panel {
      background:#fff; border:1px solid rgba(11,31,58,.08); border-radius: var(--r-out);
      box-shadow: var(--shadow);
    }

    /* ===========================
       TRAINING GALLERY
       =========================== */
    .training-gallery .btn-icon {
      width: 40px; height: 40px; border-radius: 50%;
      display: inline-flex; align-items: center; justify-content: center;
    }
    .training-gallery .btn-icon.btn-outline-secondary { border-color: var(--soft-border); color: var(--smc-navy); }
    .training-gallery .btn-icon.btn-outline-secondary:hover { background: var(--smc-navy); color:#fff; }

    .training-gallery .gallery-grid { display: grid; gap: 1rem; grid-template-columns: 1fr; }
    @media (min-width:576px) { .training-gallery .gallery-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (min-width:992px) {
      .training-gallery .gallery-grid {
        grid-template-columns: 3fr 2fr 3fr; grid-template-rows: auto auto;
        grid-template-areas: "a b d" "a c d";
      }
      .training-gallery .gallery-item[data-area="a"] { grid-area: a; }
      .training-gallery .gallery-item[data-area="b"] { grid-area: b; }
      .training-gallery .gallery-item[data-area="c"] { grid-area: c; }
      .training-gallery .gallery-item[data-area="d"] { grid-area: d; }
    }
    .training-gallery .gallery-item {
      position: relative; border-radius: 1rem; overflow: hidden; background: #f8f9fa;
      transition: transform .25s ease, box-shadow .25s ease; cursor: zoom-in;
      box-shadow: 0 10px 20px rgba(11, 31, 58, .06);
    }
    .training-gallery .gallery-item:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(11, 31, 58, .12); }
    .training-gallery .gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .training-gallery .gallery-item[data-area="a"], .training-gallery .gallery-item[data-area="d"] { aspect-ratio: 3 / 4; }
    .training-gallery .gallery-item[data-area="b"], .training-gallery .gallery-item[data-area="c"] { aspect-ratio: 4 / 3; }
    .training-gallery .gallery-dots button { width: 28px; height: 4px; border-radius: 999px; background: #d9dbe1; border: 0; margin: 0 .18rem; }
    .training-gallery .gallery-dots button.active { background: var(--smc-navy); }
    #galleryModal .modal-content { background: #000; }
    #galleryModalImg { max-height: 82vh; object-fit: contain; }

    /* ===========================
       CTA
       =========================== */
    .cta-wrap {
      background:
        radial-gradient(820px 260px at 8% 5%, rgba(255, 216, 77, .13), rgba(255, 216, 77, 0) 60%),
        radial-gradient(900px 320px at 92% 110%, rgba(19, 42, 74, .08), rgba(19, 42, 74, 0) 60%),
        linear-gradient(180deg, #ffffff 0%, #f8fbff 60%, #f4f8ff 100%);
      border-radius: var(--r-out);
      box-shadow: 0 16px 36px rgba(11, 31, 58, .08), 0 1px 0 rgba(255, 255, 255, .6) inset;
    }

    /* Minor transitions on hero swap */
    .is-swapping { opacity: .25; transition: opacity .15s ease; }

    /* Accessibility helpers */
    .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); border:0; }
  </style>
</head>
<body class="bg-light">

  <!-- Floating Translate Button (EN ⇄ AR) -->
  <button id="langToggle" class="lang-toggle" type="button" aria-live="polite" aria-pressed="false" title="Translate to Arabic">
    <span class="dot" aria-hidden="true"></span>
    <span id="langToggleLabel">AR</span>
  </button>

  <!-- ✅ Reusable Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- ===================== -->
  <!-- Page Content Starts   -->
  <!-- ===================== -->

  <!-- HERO -->
  <section class="hero-section">
    <div class="hero-grid"></div>
    <div class="hero-gradient"></div>

    <div class="container">
      <div class="row align-items-center g-4 g-lg-5">

        <!-- LEFT: Text + pills -->
        <div class="col-12 col-lg-6">
          <div class="hero-title-wrap mb-2">
            <h1 id="heroTitle" class="display-5 fw-bold mb-0 text-navy" data-i18n="hero.title">
              Get to know SMC Manpower Agency Philippines Co.
            </h1>
          </div>

          <div class="hero-lead-wrap mb-4">
            <p id="heroLead" class="lead text-muted-navy mb-0" data-i18n="hero.lead">
              Clear, honest, and customer‑first guidance. We connect employers and families with properly
              screened Filipino workers through safe and compliant processes.
            </p>
          </div>

          <!-- Pills -->
          <div class="hero-pills-abs-wrapper mb-3">
            <div id="heroPills" class="rounded-pill px-3 py-2 d-inline-flex align-items-center" role="tablist" aria-label="Hero options">
              <button type="button" class="btn btn-light rounded-pill px-3 py-2 active" role="tab" aria-selected="true"
                data-title="About SMC"
                data-lead="SMC Manpower Agency Philippines Co. is a DMW-licensed, compliance-first recruitment agency connecting skilled Filipino talent with reputable employers. We prioritize transparent processes, lawful documentation, and respectful placements."
                data-img="../resources/img/hero1.jpg" data-img-alt="About SMC">
                <span data-i18n="hero.pill_overview">Overview</span>
              </button>

              <button type="button" class="btn btn-light rounded-pill px-3 py-2" role="tab" aria-selected="false"
                data-title="Meet Our Founder"
                data-lead="Founded by Mr. Rogelio M. Lansang—an OFW in the Middle East for ten years (1989–2004)—SMC’s mission is to create fair and dignified employment opportunities that uplift Filipino families."
                data-img="../resources/img/MrRog.png" data-img-alt="Founder">
                <span data-i18n="hero.pill_founder">Founder</span>
              </button>

              <button type="button" class="btn btn-light rounded-pill px-3 py-2" role="tab" aria-selected="false"
                data-title="Mission & Vision"
                data-lead="Our mission is ethical recruitment and worker welfare; our vision is to be a trusted bridge between global employers and Filipino talent—built on integrity, safety, and service."
                data-img="../resources/img/overview3.png" data-img-alt="Mission & Vision">
                <span data-i18n="hero.pill_mv">Mission & Vision</span>
              </button>
            </div>
          </div>

          <div class="d-flex gap-2">
            <a href="./applicant.php" class="btn btn-navy">
              <span data-i18n="hero.btn_apply">View Applicants</span> <i class="fa-solid fa-users ms-2"></i>
            </a>
            <a href="#compliance" class="btn btn-gold">
              <span data-i18n="hero.btn_compliance">Our Compliance</span> <i class="fa-solid fa-shield-halved ms-2"></i>
            </a>
          </div>
        </div>

        <!-- RIGHT: Image -->
        <div class="col-12 col-lg-6 hero-visual">
          <div class="hero-image-wrap rounded-4" style="filter: drop-shadow(0 12px 22px rgba(11,31,58,.18)); width: clamp(280px, 40vw, 560px);">
            <img id="heroImg" src="../resources/img/hero1.jpg" alt="Hero visual" class="img-fluid">
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- TRUST STRIP -->
  <section class="py-3">
    <div class="container trust-strip text-center">
      <div class="d-inline-flex flex-wrap gap-2 justify-content-center">
        <div class="item"><i class="fa-solid fa-id-card-clip text-navy"></i> <span data-i18n="trust.dmw">DMW Licensed</span></div>
        <div class="item"><i class="fa-solid fa-shield-halved text-navy"></i> <span data-i18n="trust.compliance">Compliance‑First</span></div>
        <div class="item"><i class="fa-solid fa-handshake-angle text-navy"></i> <span data-i18n="trust.ethical">Ethical Recruitment</span></div>
        <div class="item"><i class="fa-solid fa-comments text-navy"></i> <span data-i18n="trust.support">Clear Communication</span></div>
        <div class="item"><i class="fa-solid fa-people-roof text-navy"></i> <span data-i18n="trust.welfare">Worker Welfare</span></div>
      </div>
    </div>
  </section>

  <!-- MISSION • VISION • VALUES -->
  <section class="py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="mv.badge">Who We Are</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="mv.title">Mission • Vision • Values</h2>
        <p class="text-muted mb-0" data-i18n="mv.subtitle">Built on integrity, clarity, and service—serving employers and supporting Filipino talent.</p>
      </div>
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="panel p-4 h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-bullseye text-navy"></i></div>
              <div>
                <h5 class="fw-bold text-navy mb-1" data-i18n="mv.mission_t">Mission</h5>
                <p class="mb-0 text-muted" data-i18n="mv.mission_d">Deliver ethical, compliant, and dignified recruitment with clear guidance from screening to deployment.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="panel p-4 h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-eye text-navy"></i></div>
              <div>
                <h5 class="fw-bold text-navy mb-1" data-i18n="mv.vision_t">Vision</h5>
                <p class="mb-0 text-muted" data-i18n="mv.vision_d">Be a trusted bridge between Bahrain & global employers and Filipino workers—recognized for integrity and results.</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4">
          <div class="panel p-4 h-100">
            <div class="d-flex align-items-start gap-3">
              <div class="icon-hex"><i class="fa-solid fa-scale-balanced text-navy"></i></div>
              <div>
                <h5 class="fw-bold text-navy mb-1" data-i18n="mv.values_t">Values</h5>
                <p class="mb-0 text-muted" data-i18n="mv.values_d">Integrity, respect, safety, clarity, and continuous improvement.</p>
              </div>
            </div>
          </div>
        </div>
      </div><!--/row-->
    </div>
  </section>

  <!-- LICENSING & COMPLIANCE -->
  <section id="compliance" class="py-5 bg-white">
    <div class="container">
      <div class="row g-4 align-items-stretch">
        <div class="col-lg-7">
          <div class="panel p-4 p-md-5 h-100">
            <span class="badge-gold mb-2"><i class="fa-solid fa-shield-halved me-2"></i><span data-i18n="comp.badge">Licensing & Compliance</span></span>
            <h3 class="fw-bold text-navy mb-3" data-i18n="comp.title">We put safety, legality, and clarity first</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3">
                <i class="fa-solid fa-id-card-clip text-navy mt-1"></i>
                <span data-i18n="comp.l1"><strong>DMW License:</strong> DMW-062-LB-03232023-R (formerly POEA)</span>
              </li>
              <li class="d-flex gap-3 mb-3">
                <i class="fa-solid fa-file-signature text-navy mt-1"></i>
                <span data-i18n="comp.l2"><strong>Transparent Documentation:</strong> Contracts, IDs, clearances, medicals</span>
              </li>
              <li class="d-flex gap-3 mb-3">
                <i class="fa-solid fa-handshake-angle text-navy mt-1"></i>
                <span data-i18n="comp.l3"><strong>Ethical Recruitment:</strong> No illegal fees; respect and dignity for workers</span>
              </li>
              <li class="d-flex gap-3">
                <i class="fa-solid fa-circle-check text-navy mt-1"></i>
                <span data-i18n="comp.l4"><strong>Aligned with Regulations:</strong> Bahrain & Philippine processes observed</span>
              </li>
            </ul>
            <p class="small text-muted mt-3 mb-0" data-i18n="comp.note">Timelines and requirements vary by role and case.</p>
          </div>
        </div>
        <div class="col-lg-5">
          <div class="panel p-4 p-md-5 h-100">
            <h5 class="fw-bold text-navy mb-3" data-i18n="comp.docs_title">Standard Document Flow</h5>
            <ol class="m-0 ps-3">
              <li class="mb-2" data-i18n="comp.d1">Job requisition & role details</li>
              <li class="mb-2" data-i18n="comp.d2">Sourcing, screening & interviews</li>
              <li class="mb-2" data-i18n="comp.d3">Contracts & compliance checks</li>
              <li class="mb-2" data-i18n="comp.d4">Visa processing & travel</li>
              <li class="mb-0" data-i18n="comp.d5">Deployment & onboarding support</li>
            </ol>
            <div class="mt-4">
              <a href="./contactUs.php" class="btn btn-navy">
                <span data-i18n="comp.cta">Talk to Compliance</span> <i class="fa-solid fa-arrow-right ms-2 flip-rtl"></i>
              </a>
            </div>
          </div>
        </div>
      </div><!--/row-->
    </div>
  </section>

  <!-- LEADERSHIP -->
  <section class="py-5">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="lead.badge">Leadership</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="lead.title">Guided by Experience & Purpose</h2>
        <p class="text-muted mb-0" data-i18n="lead.subtitle">A mission led by real-world overseas experience and service mindset.</p>
      </div>
      <div class="row g-4 align-items-center">
        <div class="col-md-5">
          <div class="panel p-3 h-100">
            <img src="../resources/img/MrRog.png" class="w-100 rounded-4" alt="Founder - Mr. Rogelio M. Lansang">
          </div>
        </div>
        <div class="col-md-7">
          <div class="panel p-4 h-100">
            <h4 class="fw-bold text-navy mb-1" data-i18n="lead.name">Mr. Rogelio M. Lansang</h4>
            <div class="text-muted mb-3" data-i18n="lead.role">Founder & President</div>
            <p class="mb-0 text-muted" data-i18n="lead.bio">
              An Overseas Filipino Worker in the Middle East for ten years (1989–2004), he founded SMC to open fair, dignified employment for Filipinos and dependable recruitment for clients. With a people-first approach and compliance mindset, SMC continues to serve responsibly.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TIMELINE / MILESTONES -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="text-center mb-4">
        <span class="badge-soft" data-i18n="time.badge">Milestones</span>
        <h2 class="fw-bold text-navy mt-2" data-i18n="time.title">Our Story at a Glance</h2>
      </div>
      <div class="panel p-4 p-md-5">
        <div class="row g-4">
          <div class="col-md-4">
            <h5 class="text-navy mb-1">2006</h5>
            <p class="small text-muted mb-0" data-i18n="time.m1">SMC Group of Company management established</p>
          </div>
          <div class="col-md-4">
            <h5 class="text-navy mb-1">2010</h5>
            <p class="small text-muted mb-0" data-i18n="time.m2">SMC Manpower Agency officially founded</p>
          </div>
          <div class="col-md-4">
            <h5 class="text-navy mb-1">2023</h5>
            <p class="small text-muted mb-0" data-i18n="time.m3">DMW license DMW-062-LB-03232023-R confirmed</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- QUALITY & ETHICS -->
  <section class="py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="panel p-4 p-md-5 h-100">
            <h3 class="fw-bold text-navy mb-3" data-i18n="quality.title">Quality Policy</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-navy mt-1"></i><span data-i18n="quality.q1">Clear requirements and timelines</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-navy mt-1"></i><span data-i18n="quality.q2">Verified documentation & screening</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-circle-check text-navy mt-1"></i><span data-i18n="quality.q3">Transparent communication & updates</span></li>
              <li class="d-flex gap-3"><i class="fa-solid fa-circle-check text-navy mt-1"></i><span data-i18n="quality.q4">Continuous improvement and feedback</span></li>
            </ul>
          </div>
        </div>
        <div class="col-lg-6">
          <div class="panel p-4 p-md-5 h-100">
            <h3 class="fw-bold text-navy mb-3" data-i18n="ethics.title">Code of Ethics</h3>
            <ul class="list-unstyled m-0">
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-hand-holding-heart text-navy mt-1"></i><span data-i18n="ethics.e1">Respect and dignity for all candidates</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-scale-balanced text-navy mt-1"></i><span data-i18n="ethics.e2">Lawful, fair terms—no illegal fees</span></li>
              <li class="d-flex gap-3 mb-3"><i class="fa-solid fa-user-shield text-navy mt-1"></i><span data-i18n="ethics.e3">Data privacy & confidentiality</span></li>
              <li class="d-flex gap-3"><i class="fa-solid fa-flag text-navy mt-1"></i><span data-i18n="ethics.e4">Zero tolerance for misconduct</span></li>
            </ul>
          </div>
        </div>
      </div><!--/row-->
    </div>
  </section>

  <!-- METRICS / COUNTERS -->
  <section class="py-5 bg-white">
    <div class="container">
      <div class="row g-4 text-center">
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="15">0</div>
          <div class="text-muted fw-semibold" data-i18n="metrics.yos">Years of Service</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="100">0</div>
          <div class="text-muted fw-semibold" data-i18n="metrics.compliance">% Compliance Focus</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="500">0</div>
          <div class="text-muted fw-semibold" data-i18n="metrics.screened">+ Screened Candidates</div>
        </div>
        <div class="col-6 col-md-3">
          <div class="counter-number" data-count="24">0</div>
          <div class="text-muted fw-semibold" data-i18n="metrics.response">Hrs Response Window</div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===================== -->
  <!-- Training Gallery     -->
  <!-- ===================== -->
  <section id="training-gallery" class="training-gallery py-5 bg-white">
    <div class="container">
      <div class="d-flex align-items-center justify-content-between mb-3">
        <h2 class="h1 fw-bold mb-0 text-navy" data-i18n="gallery.title">Gallery</h2>
        <!-- Desktop/Tablet arrows -->
        <div class="d-none d-sm-flex align-items-center gap-2">
          <button class="btn btn-outline-secondary btn-icon" type="button" data-bs-target="#galleryCarousel" data-bs-slide="prev" aria-label="Previous">
            <i class="fa-solid fa-arrow-left"></i>
          </button>
          <button class="btn btn-outline-secondary btn-icon" type="button" data-bs-target="#galleryCarousel" data-bs-slide="next" aria-label="Next">
            <i class="fa-solid fa-arrow-right"></i>
          </button>
        </div>
      </div>

      <div id="galleryCarousel" class="carousel slide" data-bs-ride="false" data-bs-interval="false" data-bs-touch="true">
        <!-- Slides -->
        <div class="carousel-inner">

          <!-- Slide 1 -->
          <div class="carousel-item active">
            <div class="gallery-grid">
              <a href="https://www.facebook.com/photo/?fbid=122156331620925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="a" data-full="../resources/img/smc1.jpg">
                <img src="../resources/img/smc1.jpg" alt="Training photo 1">
              </a>
              <a href="https://www.facebook.com/photo/?fbid=122156331584925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="b" data-full="../resources/img/smc2.jpg">
                <img src="../resources/img/smc2.jpg" alt="Training photo 2">
              </a>
              <a href="https://www.facebook.com/photo/?fbid=122156331548925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="c" data-full="../resources/img/smc3.jpg">
                <img src="../resources/img/smc3.jpg" alt="Training photo 3">
              </a>
              <a href="https://www.facebook.com/photo.php?fbid=122156331764925548&set=pb.61577766467864.-2207520000&type=3"
                 class="gallery-item" data-area="d" data-full="../resources/img/smc4.jpg">
                <img src="../resources/img/smc4.jpg" alt="Training photo 4">
              </a>
            </div>
          </div>

          <!-- Slide 2 -->
          <div class="carousel-item">
            <div class="gallery-grid">
              <a href="https://www.facebook.com/photo/?fbid=122155688996925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="a" data-full="../resources/img/smc5.jpg">
                <img src="../resources/img/smc5.jpg" alt="Training photo 5">
              </a>
              <a href="https://www.facebook.com/photo/?fbid=122155689038925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="b" data-full="../resources/img/smc6.jpg">
                <img src="../resources/img/smc6.jpg" alt="Training photo 6">
              </a>
              <a href="https://www.facebook.com/photo/?fbid=122155689080925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="c" data-full="../resources/img/smc7.jpg">
                <img src="../resources/img/smc7.jpg" alt="Training photo 7">
              </a>
              <a href="https://www.facebook.com/photo/?fbid=122155689122925548&set=pb.61577766467864.-2207520000"
                 class="gallery-item" data-area="d" data-full="../resources/img/smc8.jpg">
                <img src="../resources/img/smc8.jpg" alt="Training photo 8">
              </a>
            </div>
          </div>

          <!-- Slide 3 -->
          <div class="carousel-item">
            <div class="gallery-grid">
              <a href="https://www.facebook.com/photo.php?fbid=122155689332925548&set=pb.61577766467864.-2207520000&type=3"
                 class="gallery-item" data-area="a" data-full="../resources/img/smc9.jpg">
                <img src="../resources/img/smc9.jpg" alt="Training photo 9">
              </a>
              <a href="https://www.facebooktarget="#galleryCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
          <button type="button" data-bs-target="#galleryCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
        </div>
      </div>
    </div>

    <!-- Lightbox Modal -->
    (function(){

  <!-- Page‑local: Hero pill swapper -->
  <script>el-control-next')?.click();
        } else if (e.key === 'ArrowLeft') {
          document.querySelector('#galleryCarousel .carousel-control-prev')?.click();
        }
      }, false);
    })();
  </script>

  <!-- 🔁 Simple i18n (EN ⇄ AR) -->
  <script>
    // Arabic translations for every [data-i18n] key on this page.
    // EN is taken from the DOM at load and restored when toggling back.
    const I18N_AR = {
      "hero.title": "تعرّف على شركة إس إم سي لتوظيف العمالة الفلبينية",
      "hero.lead": "إرشاد واضح وصادق يضع العميل أولاً. نصل بين أصحاب العمل والأسر والعمال الفلبينيين بعد فرز مناسب عبر عمليات آمنة ومتوافقة.",
      "hero.pill_overview": "نظرة عامة",
      "hero.pill_founder": "المؤسس",
      "hero.pill_mv": "الرسالة والرؤية",
      "hero.btn_apply": "عرض المتقدمين",
      "hero.btn_compliance": "امتثالنا",

      "trust.dmw": "ترخيص DMW",
      "trust.compliance": "الالتزام أولاً",
      "trust.ethical": "توظيف أخلاقي",
      "trust.support": "تواصل واضح",
      "trust.welfare": "رفاهية العامل",

      "mv.badge": "من نحن",tribute('dir', 'rtl');
        body.classList.add('rtl'about') || 'en';
    setLang(saved);

    // Toggle handler
    document.getElementById('langToggle').addEventListener('click', () => {
      const current = localStorage.getItem('lang_about') || 'en';
      setLang(current === 'en' ? 'ar' : 'en');
    });
  </script>
</body>
</html>