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
  <link rel="icon" type="image/png" href="/csnk/resources/img/csnk-icon.png">
</head>

<body class="bg-light">

  <!-- ✅ Reusable Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- ===================== -->
  <!-- Page Content Starts   -->
  <!-- ===================== -->

  <style>
    /* ---------- Base / utilities applicable to this page ---------- */
    img,
    svg {
      max-width: 100%;
      height: auto;
    }

    /* ---------- HERO SECTION ---------- */
    .hero-section {
      background-color: #f8f9fb;
      position: relative;
      isolation: isolate;
      /* keep background layers behind content */
      padding: clamp(2rem, 6vw, 5rem) 0;
    }

    /* Background layers */
    .hero-grid,
    .hero-gradient {
      position: absolute;
      inset: 0;
      z-index: 0;
      pointer-events: none;
    }

    .hero-grid {
      opacity: .22;
      background-image:
        linear-gradient(to right, rgba(0, 0, 0, .06) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(0, 0, 0, .06) 1px, transparent 1px);
      background-size: 32px 32px, 32px 32px;
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 1) 10%, rgba(0, 0, 0, .85) 40%, rgba(0, 0, 0, .6) 70%, rgba(0, 0, 0, 0) 100%);
    }

    .hero-gradient {
      background:
        radial-gradient(900px 400px at 15% 35%, rgba(255, 159, 169, 0.88), rgba(220, 53, 69, 0) 60%),
        radial-gradient(700px 350px at 80% 45%, rgba(17, 17, 17, .12), rgba(17, 17, 17, 0) 60%),
        linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, .25) 60%, rgba(255, 84, 84, 0) 100%);
      mask-image: linear-gradient(to bottom, rgba(0, 0, 0, 0) 0%, rgba(0, 0, 0, .9) 10%, rgba(0, 0, 0, .95) 85%, rgba(0, 0, 0, 0) 100%);
    }

    /* Keep actual content above background layers */
    .hero-section .container {
      position: relative;
      z-index: 1;
    }

    /* Headings (desktop) */
    @media (min-width: 992px) {
      .hero-section .display-4 {
        font-size: 3rem;
        line-height: 1.1;
      }
    }

    /* Smooth swap animation */
    .fade-swap {
      transition: opacity .22s ease, transform .22s ease;
    }

    .is-swapping {
      opacity: 0;
      transform: translateY(6px);
    }

    /* ---------- PILL BAR (shrink-to-content & scrollable) ---------- */

    /* Wrapper: only scrolls when needed */
    .hero-pills-abs-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    /* Pill bar: fit to content, not full width */
    #heroPills {
      display: inline-flex;
      /* makes white capsule hug its content */
      width: max-content;
      /* shrink-wrap */
      max-width: 100%;
      /* never overflow the container */
      gap: .5rem;
      align-items: center;
      padding: .5rem .6rem;
      /* capsule padding */
      border-radius: 999px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
      overflow: visible;
      /* keep nice shadows visible */
      flex-wrap: nowrap;
      /* one row; wrapper handles scroll on small screens */
      scroll-snap-type: x proximity;
      background: #fff;
      /* ensure white capsule */
    }

    #heroPills .btn {
      flex: 0 0 auto;
      /* no shrinking */
      white-space: nowrap;
      /* keep labels on one line */
      scroll-snap-align: start;
    }

    .hero-section .btn-light.active {
      background: #111;
      color: #fff;
    }

    /* Center the capsule on md+ (optional) */
    @media (min-width: 768px) {
      .hero-pills-abs-wrapper {
        display: flex;
        justify-content: flex-start;
        /* change to center if you want centered pills */
      }
    }

    /* ---------- Hero visual (image) ---------- */
    .hero-visual {
      display: flex;
      justify-content: center;
    }

    .hero-image-wrap {
      background: transparent;
      border-radius: 1rem;
      filter: drop-shadow(0 12px 22px rgba(0, 0, 0, .18));
      width: clamp(260px, 40vw, 520px);
      /* desktop-preferred width */
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
      .hero-section {
        overflow: visible !important;
      }

      .hero-title-wrap .display-4 {
        font-size: 2rem;
        line-height: 1.2;
      }

      .hero-lead-wrap .lead {
        font-size: 1rem;
      }
    }

    /* On < lg, center the image and remove desktop offsets entirely */
    @media (max-width: 991.98px) {
      .hero-image-wrap {
        margin: 0 auto !important;
        transform: none !important;
        width: min(85vw, 420px);
      }
    }

    /* ---------- Carousel image heights by breakpoint (if you later add slide images) ---------- */
    .carousel-img {
      width: 100%;
      height: 340px;
      object-fit: cover;
    }

    @media (max-width: 575.98px) {
      .carousel-img {
        height: 220px;
      }
    }

    @media (min-width: 576px) and (max-width: 991.98px) {
      .carousel-img {
        height: 280px;
      }
    }

    /* Extra room around pills on phones */
    @media (max-width: 575.98px) {
      .hero-pills-abs-wrapper {
        margin-bottom: .5rem;
      }

      .hero-pills-spacer {
        height: 0;
      }
    }

    /* ====================== */
    /* Gallery with Category  */
    /* ====================== */

    /* Responsive gallery grid */
    .gallery-grid {
      display: grid;
      gap: 12px;
      grid-template-columns: repeat(2, 1fr);
    }

    @media (min-width: 576px) {
      .gallery-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }

    @media (min-width: 992px) {
      .gallery-grid {
        grid-template-columns: repeat(4, 1fr);
      }
    }

    /* Clickable thumbnail tiles (button reset + visuals) */
    .gallery-tile {
      padding: 0;
      border: 0;
      background: transparent;
      border-radius: 12px;
      overflow: hidden;
    }

    .gallery-tile img {
      display: block;
      width: 100%;
      height: 100%;
      aspect-ratio: 1 / 1;
      object-fit: cover;
    }

    /* Utility for hiding filtered items with a smooth transition */
    .gallery-tile[hidden] {
      display: none !important;
    }


    .btn-outline-secondary {
      height: 50px;
      width: 120px;
      font-size: 1rem;
      font-weight: 700;
    }

    .btn-outline-secondary.active {
      background-color: #b42a00;
      color: #fff;
    }

    .btn-outline-secondary:hover {
      background-color: #b2b2b2;
      color: #000000;

    }


    /* ====================== */
    /* FINAL CTA (Hire Now!)  */
    /* ====================== */
    .cta-hire {
      /* Soft white card with subtle, directionally-lit gradient like your ref */
      background:
        /* top-left warm highlight */
        radial-gradient(800px 260px at 8% 5%, rgba(255, 170, 120, .18), rgba(255, 170, 120, 0) 60%),
        /* bottom-right cool fade */
        radial-gradient(1000px 320px at 92% 110%, rgba(12, 32, 76, .08), rgba(12, 32, 76, 0) 60%),
        /* gentle vertical wash */
        linear-gradient(180deg, #ffffff 0%, #fbfcff 60%, #f7f9fc 100%);
      border-radius: 1.25rem;
      padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 2rem);
      box-shadow:
        0 20px 40px rgba(13, 29, 54, 0.06),
        0 1px 0 rgba(255, 255, 255, 0.6) inset;
    }

    .cta-row {
      display: grid;
      /* Title left, button right on md+; single column on mobile */
      grid-template-columns: 1fr;
      align-items: center;
      gap: clamp(.75rem, 2vw, 1rem);
    }

    @media (min-width: 768px) {
      .cta-row {
        grid-template-columns: 1fr auto;
      }
    }

    .cta-title {
      font-weight: 800;
      font-size: clamp(1.05rem, 2.1vw, 1.35rem);
      color: #1b1d22;
      margin: 0;
      line-height: 1.35;
    }

    .cta-actions {
      display: flex;
      justify-content: flex-start;
      /* mobile */
    }

    @media (min-width: 768px) {
      .cta-actions {
        justify-content: flex-end;
      }

      /* button to the far right on md+ */
    }

    /* Button */
    .cta-btn {
      --grad-a: #ff7a3d;
      /* warm orange */
      --grad-b: #ffb04a;
      /* soft orange-yellow */
      background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
      color: #fff;
      border: 0;
      border-radius: 999px;
      padding: .85rem 1.5rem;
      font-weight: 700;
      letter-spacing: .2px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: .6rem;
      box-shadow: 0 12px 26px rgba(255, 122, 61, .28);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      position: relative;
      /* for decorative ticks */
      isolation: isolate;
      /* keep inner effects confined */
      white-space: nowrap;
    }

    .cta-btn:hover,
    .cta-btn:focus {
      transform: translateY(-1px);
      box-shadow: 0 16px 34px rgba(255, 122, 61, .34);
      filter: brightness(1.03);
      color: #fff;
    }

    /* Decorative side marks to echo the ref’s “spark” shape */
    .cta-btn::after {
      content: "✦ ✦ ✦";
      font-size: .85rem;
      color: #ffa95a;
      position: absolute;
      right: -2rem;
      /* sits outside the button’s right edge */
      top: 50%;
      transform: translateY(-50%);
      opacity: .95;
      pointer-events: none;
    }

    @media (max-width: 575.98px) {
      .cta-btn::after {
        right: -1.6rem;
        font-size: .8rem;
      }
    }

    /* Respect reduced motion */
    @media (prefers-reduced-motion: reduce) {
      .cta-btn {
        transition: none !important;
      }
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
            <p id="heroLead" class="lead text-black-100 mb-0 fade-swap">
              Clear, honest and customer‑first guidance. We connect families with properly
              screened domestic workers through safe and compliant processes.
            </p>
          </div>

          <!-- Pills -->
          <div class="hero-pills-abs-wrapper">
            <div id="heroPills" class="rounded-pill px-3 py-2 d-inline-flex align-items-center" role="tablist"
              aria-label="Hero options">

              <button type="button" class="btn btn-light rounded-pill px-3 py-2 active" role="tab" aria-selected="true"
                data-title="Get to know CSNK" data-lead="CSNK Manpower Agency is dedicated to providing families with reliable 
                    and compassionate household assistance. Beyond offering quality domestic help, we 
                    are a full‑service manpower agency committed to supporting and empowering Filipino 
                    women by connecting them with safe, legitimate, and rewarding employment opportunities. 
                    Through proper screening, guidance, and documentation, we ensure that every home receives 
                    trustworthy service, while every applicant receives a fair chance to build a better future."
                data-img="../resources/img/overview2.png" data-img-alt="Overview image">
                Overview
              </button>

              <button type="button" class="btn btn-light rounded-pill px-3 py-2" role="tab" aria-selected="false"
                data-title="Meet Founder of CSNK" data-lead="CSNK was founded by Mr. Rogelio M. Lansang year 2010, because of his 
                    passion to help people and give a job for those in need. He is formerly an Overseas 
                    Filipino Worker in the Middle East for Ten (10) years from 1989 to 2004. His goal 
                    is to provide an opportunity that will help Filipino women build a better future, not
                    only for themselves, but for their families. He was successfully managing group of 
                    companies under the SMC GROUP OF COMPANY in 2006. Since then, Mr. Lansang has remained 
                    committed to ensuring that CSNK carries out its mission with integrity."
                data-img="../resources/img/MrRog.png" data-img-alt="Founder image">
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
            <img id="heroImg" src="/resources/img/hero1.jpg" alt="Hero visual" class="img-fluid fade-swap">
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ===================== -->
  <!-- Training Gallery -->
  <!-- ===================== -->
  <section id="training-gallery" class="training-gallery py-5 bg-white">
    <div class="container">

      <!-- Header + Category Filters -->
      <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
        <h2 class="h1 fw-bold mb-0">Trainings</h2>

        <!-- Category buttons (edit labels/categories as needed) -->
        <div id="galleryFilters" class="btn-group flex-wrap" role="group" aria-label="Gallery categories">
          <button type="button" class="btn btn-outline-secondary active" data-filter="all" aria-pressed="true">
            All
          </button>
          <button type="button" class="btn btn-outline-secondary" data-filter="Kasambahay">
            Kasambahay
          </button>
          <button type="button" class="btn btn-outline-secondary" data-filter="Mechanic">
            Mechanic
          </button>
          <button type="button" class="btn btn-outline-secondary" data-filter="Electrician">
            Electrician
          </button>
        </div>
      </div>



      <!-- Thumbnails Grid -->
      <div id="galleryGrid" class="gallery-grid">
        <!-- Add data-category with one or more categories separated by spaces -->
        <!-- You can also use figure/figcaption for better semantics -->
        <div class="gallery-tile" data-category=Kasambahay aria-label="Open Training photo 1">
          <img src="../resources/img/about1.jpg" alt="Training photo 1">
        </div>

        <div class="gallery-tile" data-category=Kasambahay aria-label="Open Training photo 2">
          <img src="../resources/img/about2.jpg" alt="Training photo 2">
        </div>

        <div class="gallery-tile" data-category=Mechanic aria-label="Open Training photo 3">
          <img src="../resources/img/about3.jpg" alt="Training photo 3">
        </div>

        <div class="gallery-tile" data-category=Electrician aria-label="Open Training photo 4">
          <img src="../resources/img/about4.jpg" alt="Training photo 4">
        </div>

        <div class="gallery-tile" data-category=Mechanic aria-label="Open Training photo 5">
          <img src="../resources/img/about5.jpg" alt="Training photo 5">
        </div>

        <div class="gallery-tile" data-category=Kasambahay aria-label="Open Training photo 6">
          <img src="../resources/img/about6.jpg" alt="Training photo 6">
        </div>

        <div class="gallery-tile" data-category=Mechanic aria-label="Open Training photo 7">
          <img src="../resources/img/about7.jpg" alt="Training photo 7">
        </div>

        <div class="gallery-tile" data-category=Electrician aria-label="Open Training photo 8">
          <img src="../resources/img/about8.jpg" alt="Training photo 8">
        </div>
      </div>
    </div>
  </section>

  <!-- ====================== -->
  <!-- FINAL CTA: Hire Now!  -->
  <!-- ====================== -->
  <section class="py-4 py-md-5">
    <div class="container">
      <div class="cta-hire">
        <div class="cta-row">
          <p class="cta-title">
            Hire reliable, properly screened Household Service Workers (HSWs)
            for your home.
          </p>

          <div class="cta-actions">
            <a class="cta-btn" href="./applicant.php" aria-label="Hire Now">
              Hire Now! <i class="fa-solid fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </div><!-- /.cta-hire -->
    </div>
  </section>

  <!-- ===================== -->
  <!-- Page Content Ends     -->
  <!-- ===================== -->

  <!-- ✅ Reusable Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- Bootstrap JS (bundle includes Popper + Carousel) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Policy Modals Handler -->
  <script src="../resources/js/policy-modals.js"></script>

  <!-- Page‑local: Hero pill swapper -->
  <script>
    (function () {
      const container = document.getElementById('heroPills');
      const titleEl = document.getElementById('heroTitle');
      const leadEl = document.getElementById('heroLead');
      const imgEl = document.getElementById('heroImg');

      if (!container || !titleEl || !leadEl || !imgEl) return;

      const pills = container.querySelectorAll('.btn');
      const swapEls = [titleEl, leadEl, imgEl];

      function setActive(btn) {
        pills.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
        btn.classList.add('active'); btn.setAttribute('aria-selected', 'true');
      }

      function applyFrom(btn) {
        if (btn.dataset.title) titleEl.textContent = btn.dataset.title;
        if (btn.dataset.lead) leadEl.textContent = btn.dataset.lead;
        if (btn.dataset.img) {
          imgEl.src = btn.dataset.img;
          imgEl.alt = btn.dataset.imgAlt || btn.dataset.title || 'Hero image';
        }
      }

      function swap(btn) {
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

  <!-- Page‑local: Training Gallery lightbox & keys -->
  <script>
    (function () {
      const grid = document.getElementById('galleryGrid');
      const tiles = Array.from(grid.querySelectorAll('.gallery-tile'));
      const filters = document.getElementById('galleryFilters');
      const previewImg = document.getElementById('galleryPreviewImg');
      const previewCaption = document.getElementById('galleryPreviewCaption');

      // Filter buttons
      filters.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-filter]');
        if (!btn) return;

        const filter = btn.getAttribute('data-filter');
        // Update active state
        filters.querySelectorAll('button[data-filter]').forEach(b => {
          b.classList.toggle('active', b === btn);
          b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });

        // Show/hide tiles
        tiles.forEach(tile => {
          const cats = (tile.getAttribute('data-category') || '').split(/\s+/);
          const show = (filter === 'all') || cats.includes(filter);
          if (show) {
            tile.hidden = false;
          } else {
            tile.hidden = true;
          }
        });

        // If the current preview is not part of the filtered set, switch to the first visible
        const visibleTile = tiles.find(t => !t.hidden);
        if (visibleTile) {
          const img = visibleTile.querySelector('img');
        } else {
          // If nothing matches, clear preview gracefully
          previewImg.src = '';
          previewImg.alt = 'No images for this category';
          previewCaption.textContent = 'No images for this category';
        }
      });

      // Optional: set initial preview to first tile (already set in HTML)
      // If you remove the default src in HTML, uncomment to auto-pick first:
      // const firstImg = grid.querySelector('.gallery-tile img');
      // if (firstImg) updatePreview(firstImg);
    })();
  </script>

</body>

</html>