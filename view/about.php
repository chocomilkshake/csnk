 0, 0, 0) 0%, rgba(0, 0, 0, .9) 10%, rgba(0, 0, 0, .95) 85%, rgba(0, 0, 0, 0) 100%);
    }

    .hero-section .container {
      position: relative;
      background: linear-gradient(145deg, #ffffff 0%, #f0f0f0 100%);
      padding: 8px;
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .hero-image-wrap:hover img {
      transform: scale(1.03);
    }

    /* Corner accent */
    .hero-corner-accent {
      position: absolute;
      bottom: -5px;
      right: -5px;
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #ff6b6b 0%, #ffa500 100%);
      border-radius: 0 0 14px 0;
      z-index: 2;
    }

    .hero-corner-accent::after {
      content: '';
      position: absolute;
      top: 8px;
      right: 8px;
      width: 12px;
      height: 12px;
      background: white;
      border-radius: 50%;
    }

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

      .hero-pills-abs-wrapper {
        margin-bottom: .5rem;
      }

      .hero-pills-spacer {
        height: 0;
      }

      .hero-image-container {
        width: min(85vw, 420px);
        padding: 15px;
      }

      .hero-float-shape-1 {
        width: 45px;
        height: 45px;
      }

      .hero-float-shape-2 {
        width: 28px;
        height: 28px;
      }

      .hero-float-shape-3 {
        width: 20px;
        height: 20px;
      }
    }

    @media (max-width: 991.98px) {
      .hero-image-container {
        margin: 0 auto !important;
      }
    }

    @media (prefers-reduced-motion: reduce) {

      .hero-float-shape,
      .hero-image-wrap {
        animation: none !important;
        transition: none !important;
      }
    }

    /* ====================== */
    /* Gallery with Category  */
    /* ====================== */
    .gallery-wrapper {
      position: relative;
    }

    .gallery-scroll-container {
      overflow-x: auto;
      overflow-y: hidden;
      scroll-behavior: smooth;
      -webkit-overflow-scrolling: touch;
      scroll-snap-type: x mandatory;
      scrollbar-width: thin;
      scrollbar-color: #ccc #f5f5f5;
      padding-bottom: 10px;
      position: relative;
    }

    /* Swipe indicators (mobile/tablet only) */
    .gallery-swipe-indicators {
      display: none;
      position: absolute;
      bottom: 8px;
      left: 50%;
      transform: translateX(-50%);
      gap: 4px;
      z-index: 10;
    }

    @media (max-width: 991px) {
      .gallery-swipe-indicators {
        display: flex;
      }
    }

    .swipe-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.7);
      transition: all 0.3s ease;
      cursor: pointer;
    }

    .swipe-dot.active {
      background: #dc3545;
      box-shadow: 0 0 4px rgba(220, 53, 69, 0.8);
    }

    /* Touch feedback arrows */
    .swipe-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.9);
      border: none;
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      opacity: 0.7;
      transition: all 0.3s ease;
      z-index: 10;
      pointer-events: none;
    }

    .gallery-scroll-container.swiping .swipe-arrow {
      pointer-events: auto;
      opacity: 1;
    }

    @media (hover: hover) {
      .swipe-arrow {
        display: none;
      }
    }

    .gallery-scroll-container::-webkit-scrollbar {
      height: 6px;
    }

    .gallery-scroll-container::-webkit-scrollbar-track {
      background: #f5f5f5;
      border-radius: 3px;
    }

    .gallery-scroll-container::-webkit-scrollbar-thumb {
      background: #ccc;
      border-radius: 3px;
    }

    .gallery-scroll-container::-webkit-scrollbar-thumb:hover {
      background: #999;
    }

    .gallery-grid {
      display: flex;
      flex-wrap: nowrap;
      gap: 16px;
      padding: 4px;
    }

    @media (min-width: 992px) {
      .gallery-grid {
        flex-wrap: wrap;
      }
    }

    .gallery-tile {
      flex: 0 0 auto;
      width: calc(50% - 8px);
      padding: 0;
      border: 0;
      background: transparent;
      border-radius: 16px;
      overflow: hidden;
      cursor: pointer;
      position: relative;
    }

    @media (min-width: 576px) {
      .gallery-tile {
        width: calc(33.333% - 11px);
      }
    }

    @media (min-width: 992px) {
      .gallery-tile {
        width: calc(25% - 12px);
        flex: none;
      }
    }

    .gallery-tile img {
      display: block;
      width: 100%;
      height: 180px;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .gallery-tile:hover img {
      transform: scale(1.1);
    }

    /* Overlay with title */
    .gallery-tile-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.75) 0%, rgba(0, 0, 0, 0) 60%);
      opacity: 0;
      transition: opacity 0.3s ease;
      display: flex;
      align-items: flex-end;
      padding: 16px;
      border-radius: 16px;
    }

    .gallery-tile:hover .gallery-tile-overlay {
      opacity: 1;
    }

    .gallery-tile-title {
      color: white;
      font-size: 0.9rem;
      font-weight: 600;
      margin: 0;
      transform: translateY(10px);
      transition: transform 0.3s ease;
      line-clamp: 2;
      -webkit-line-clamp: 2;
      display: -webkit-box;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .gallery-tile:hover .gallery-tile-title {
      transform: translateY(0);
    }

    .gallery-tile[hidden] {
      display: none !important;
    }

    /* View more indicator */
    .gallery-view-more {
      flex: 0 0 auto;
      width: calc(50% - 8px);
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-radius: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      min-height: 180px;
    }

    @media (min-width: 576px) {
      .gallery-view-more {
        width: calc(33.333% - 11px);
      }
    }

    @media (min-width: 992px) {
      .gallery-view-more {
        width: calc(25% - 12px);
        flex: none;
      }
    }

    .gallery-view-more:hover {
      background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
      transform: translateY(-4px);
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }

    .gallery-view-more-content {
      text-align: center;
      color: #6c757d;
    }

    .gallery-view-more-icon {
      font-size: 2rem;
      margin-bottom: 8px;
    }

    .gallery-view-more-text {
      font-weight: 600;
      font-size: 0.95rem;
    }

    /* Enhanced filter buttons */
    .gallery-filters-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
      -ms-overflow-style: none;
    }

    .gallery-filters-wrapper::-webkit-scrollbar {
      display: none;
    }

    #galleryFilters {
      display: inline-flex;
      flex-wrap: nowrap;
      gap: 8px;
      padding: 6px;
      background: #fff;
      border-radius: 999px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      border: 1px solid #eee;
    }

    .btn-outline-secondary {
      height: 42px;
      width: auto;
      padding: 0 1.1rem;
      font-size: 0.9rem;
      font-weight: 600;
      border-radius: 999px;
      white-space: nowrap;
      transition: all 0.25s ease;
    }

    .btn-outline-secondary.active {
      background: linear-gradient(135deg, #b42a00 0%, #d63318 100%);
      color: #fff;
      border-color: #b42a00;
      box-shadow: 0 4px 15px rgba(180, 42, 0, 0.3);
    }

    .btn-outline-secondary:hover:not(.active) {
      background: #f8f9fa;
      color: #333;
      border-color: #ccc;
    }

    /* ====================== */
    /* FINAL CTA: Hire Now!   */
    /* ====================== */

    .cta-hire {
      background:
        radial-gradient(800px 260px at 8% 5%, rgba(255, 170, 120, .18), rgba(255, 170, 120, 0) 60%),
        radial-gradient(1000px 320px at 92% 110%, rgba(12, 32, 76, .08), rgba(12, 32, 76, 0) 60%),
        linear-gradient(180deg, #ffffff 0%, #fbfcff 60%, #f7f9fc 100%);

      border-radius: 20px;
      padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 2rem);

      box-shadow:
        0 20px 40px rgba(13, 29, 54, .06),
        0 1px 0 rgba(255, 255, 255, .6) inset;
    }

    .cta-row {
      display: grid;
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
    }

    @media (min-width: 768px) {
      .cta-actions {
        justify-content: flex-end;
      }
    }

    .cta-btn {
      --grad-a: #ff7a3d;
      --grad-b: #ffb04a;
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
      isolation: isolate;
      white-space: nowrap;
    }

    .cta-btn:hover,
    .cta-btn:focus {
      transform: translateY(-1px);
      box-shadow: 0 16px 34px rgba(255, 122, 61, .34);
      filter: brightness(1.03);
      color: #fff;
    }

    .cta-btn::after {
      content: "✦ ✦ ✦";
      font-size: .85rem;
      color: #ffa95a;
      position: absolute;
      right: -2rem;
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

    @media (prefers-reduced-motion: reduce) {
      .cta-btn {
        transition: none !important;
      }
    }

    /* ===== ENHANCED LIGHTBOX ===== */
    #lightboxModal .modal-dialog {
      max-width: 900px;
    }

    #lightboxModal .modal-content {
      background: transparent;
      border: none;
      box-shadow: none;
    }

    #lightboxModal .modal-backdrop {
      background-color: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
    }

    #lightboxModal .modal-body {
      padding: 0;
      position: relative;
    }

    #lightboxModal .btn-close {
      position: absolute;
      top: 15px;
      right: 15px;
      z-index: 10;
      background: rgba(255, 255, 255, 0.95);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 1;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      transition: all 0.2s ease;
    }

    #lightboxModal .btn-close:hover {
      background: #fff;
      transform: scale(1.1);
    }

    #lightboxModal .btn-close::after {
      content: '\f00d';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      color: #333;
      font-size: 1rem;
    }

    #lightboxModal .btn-close span {
      display: none;
    }

    #lightboxImg {
      max-height: calc(100vh - 12rem);
      width: auto;
      max-width: 100%;
      border-radius: 12px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
    }

    #lightboxCaption {
      font-size: 1.1rem;
      font-weight: 500;
      color: #fff;
      text-align: center;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      padding: 12px 24px;
      border-radius: 999px;
    }

    #lbPrev,
    #lbNext {
      background: rgba(255, 255, 255, 0.95);
      border: none;
      color: #333;
      padding: 12px 20px;
      border-radius: 999px;
      font-weight: 600;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
      transition: all 0.2s ease;
    }

    #lbPrev:hover,
    #lbNext:hover {
      background: #fff;
      transform: scale(1.05);
    }

    #lbPrev i,
    #lbNext i {
      font-size: 0.9rem;
    }

    /* Lightbox navigation arrows on image */
    .lightbox-nav-overlay {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 50px;
      height: 50px;
      background: rgba(255, 255, 255, 0.9);
      border: none;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s ease;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      z-index: 5;
    }

    .lightbox-nav-overlay:hover {
      background: #fff;
      transform: translateY(-50%) scale(1.1);
    }

    .lightbox-nav-overlay.prev {
      left: 10px;
    }

    .lightbox-nav-overlay.next {
      right: 10px;
    }

    .lightbox-nav-overlay i {
      color: #333;
      font-size: 1.2rem;
    }

    @media (max-width: 767px) {
      .lightbox-nav-overlay {
        width: 40px;
        height: 40px;
      }

      .lightbox-nav-overlay.prev {
        left: 5px;
      }

      .lightbox-nav-overlay.next {
        right: 5px;
      }
    }
  </style>

  <!-- HERO -->
  <section class="hero-section">
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
                data-img="<?= asset('resources/img/overview2.png') ?>" data-img-alt="Overview image">
                Overview
              </button>

              <button type="button" class="btn btn-light rounded-pill px-3 py-2" role="tab" aria-selected="false"
                data-title="Meet Founder of CSNK"
                data-lead="CSNK was founded by Mr. Rogelio M. Lansang year 2010, driven by the mission to provide safe, legitimate and rewarding opportunities to Filipino women, carried out with integrity."
                data-img="<?= asset('resources/img/MrRog.png') ?>" data-img-alt="Founder image">
                Founder
              </button>
            </div>
          </div>

          <!-- Spacer (kept for layout compatibility) -->
          <div class="hero-pills-spacer"></div>
        </div>

        <!-- RIGHT: Enhanced Image -->
        <div class="col-12 col-lg-6 hero-visual">
          <div class="hero-image-container">
            <!-- Floating decorative shapes -->
            <div class="hero-float-shape hero-float-shape-1"></div>
            <div class="hero-float-shape hero-float-shape-2"></div>
            <div class="hero-float-shape hero-float-shape-3"></div>

            <!-- Layered frame -->
            <div class="hero-frame">
              <div class="hero-image-wrap rounded-4">
                <img id="heroImg" src="<?= asset('resources/img/hero1.jpg') ?>" alt="Hero visual"
                  class="img-fluid fade-swap">
                <!-- Corner accent -->
                <div class="hero-corner-accent"></div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ===================== -->
  <!-- Training Gallery      -->
  <!-- ===================== -->
  <section id="training-gallery" class="training-gallery py-5 bg-white">
    <div class="container">

      <!-- Header + Category Filters -->
      <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
        <h2 class="h1 fw-bold mb-0">Trainings</h2>

        <!-- Category buttons (CMS-driven) -->
        <div id="galleryFilters" class="btn-group flex-wrap" role="group" aria-label="Gallery categories">
          <!-- ALL -->
          <button type="button" class="btn btn-outline-secondary active" data-filter="all" aria-pressed="true">
            All
            <?= $totalItems > 0 ? " ($totalItems)" : "" ?>
          </button>

          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat):
              $catName = $cat['name'] ?? 'Category';
              $catSlug = slugify($catName);
              $cnt = $categoryCounts[$catSlug] ?? 0;
              ?>
              <button type="button" class="btn btn-outline-secondary" data-filter="<?= htmlspecialchars($catSlug) ?>"
                aria-pressed="false">
                <?= htmlspecialchars($catName) ?>
                <?= $cnt > 0 ? " ($cnt)" : "" ?>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Thumbnails Grid (CMS-driven) with row limiter -->
      <div class="gallery-wrapper">
        <div id="galleryScrollContainer" class="gallery-scroll-container" data-indicators="true">
          <div class="gallery-swipe-indicators" id="swipeIndicators"></div>
          <button class="swipe-arrow" id="swipeLeft"><i class="fas fa-chevron-left"></i></button>
          <button class="swipe-arrow" id="swipeRight"><i class="fas fa-chevron-right"></i></button>
          <div id="galleryGrid" class="gallery-grid">
            <?php if (!empty($contentItems)): ?>
              <?php foreach ($contentItems as $item):
                $itemTitle = $item['title'] ?: 'Training image';
                $catName = $item['category_name'] ?? '';
                $catSlug = slugify($catName);
                $imgUrl = getContentImageUrl($item['image_path']);
                ?>
                <button class="gallery-tile" data-category-slug="<?= htmlspecialchars($catSlug) ?>"
                  data-full="<?= htmlspecialchars($imgUrl) ?>" data-caption="<?= htmlspecialchars($itemTitle) ?>"
                  aria-label="Open <?= htmlspecialchars($itemTitle) ?>">
                  <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($itemTitle) ?>">
                  <div class="gallery-tile-overlay">
                    <p class="gallery-tile-title">
                      <?= htmlspecialchars($itemTitle) ?>
                    </p>
                  </div>
                </button>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="col-12">
                <div class="text-center py-5 bg-white rounded-3 border">
                  <p class="text-muted mb-0">Contents and Blogs soon!</p>
                </div>
              </div>
            <?php endif; ?>
          </div>
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
  <!-- Policy Modals Handler (kept as in your old page) -->
  <script src="<?= asset('resources/js/policy-modals.js') ?>"></script>

  <!-- Page‑local: Hero pill swapper (unchanged from old) -->
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

  <!-- Page‑local: CMS Gallery filter + Bootstrap Lightbox with Next/Prev + Row Limiter -->
  <script>
    (function () {
      const grid = document.getElementById('galleryGrid');
      const filters = document.getElementById('galleryFilters');
      const scrollContainer = document.getElementById('galleryScrollContainer');
      if (!grid || !filters) return;

      const tiles = Array.from(grid.querySelectorAll('.gallery-tile'));

      // Configuration: Max rows to show (4 rows - shows rest of pictures)
      const MAX_VISIBLE_ROWS = 4;
      const COLS_DESKTOP = 4;
      const COLS_TABLET = 3;
      const COLS_MOBILE = 2;

      let currentFilter = 'all';
      let hasMoreItems = false;
      let hiddenTiles = [];

      // Function to get columns based on viewport
      function getColumns() {
        if (window.innerWidth >= 992) return COLS_DESKTOP;
        if (window.innerWidth >= 576) return COLS_TABLET;
        return COLS_MOBILE;
      }

      // Function to calculate max visible items
      function getMaxVisible() {
        return MAX_VISIBLE_ROWS * getColumns();
      }

      // Function to update gallery based on filter and row limit
      function updateGallery() {
        const maxVisible = getMaxVisible();

        // Get visible tiles based on filter
        const filteredTiles = tiles.map((tile, idx) => ({
          tile,
          cat: (tile.getAttribute('data-category-slug') || '').toLowerCase().trim(),
          originalIndex: idx
        })).filter(item => {
          if (currentFilter === 'all') return true;
          return item.cat === currentFilter;
        });

        // Reset all tiles first
        tiles.forEach(t => t.classList.remove('limited'));

        if (filteredTiles.length > maxVisible) {
          // Hide tiles beyond the limit
          filteredTiles.forEach((item, idx) => {
            if (idx >= maxVisible) {
              item.tile.classList.add('limited');
              item.tile.hidden = true;
            } else {
              item.tile.classList.remove('limited');
              item.tile.hidden = false;
            }
          });
          hasMoreItems = true;
        } else {
          // Show all if within limit
          filteredTiles.forEach(item => {
            item.tile.classList.remove('limited');
            item.tile.hidden = false;
          });
          hasMoreItems = false;
        }

        // Update scroll container behavior
        if (scrollContainer) {
          if (hasMoreItems && window.innerWidth < 992) {
            scrollContainer.style.overflowX = 'auto';
            scrollContainer.style.overflowY = 'hidden';
          } else {
            scrollContainer.style.overflowX = '';
            scrollContainer.style.overflowY = '';
          }
        }
      }

      // Filter buttons (All + dynamic categories)
      filters.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-filter]');
        if (!btn) return;

        currentFilter = (btn.getAttribute('data-filter') || 'all').toLowerCase();

        // Update active state
        filters.querySelectorAll('button[data-filter]').forEach(b => {
          b.classList.toggle('active', b === btn);
          b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });

        // Show/hide tiles based on slug (strict match)
        tiles.forEach(tile => {
          const cat = (tile.getAttribute('data-category-slug') || '').toLowerCase().trim();
          const show = (currentFilter === 'all') ? true : (cat === currentFilter);
          tile.hidden = !show;
        });

        // Apply row limiter
        updateGallery();
      });

      // Handle window resize
      let resizeTimeout;
      window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(updateGallery, 150);
      });

      // Initial setup
      updateGallery();

      // Enhanced swipe logic (preserves existing)
      if (scrollContainer.dataset.indicators === 'true') {
        const indicators = document.getElementById('swipeIndicators');
        const leftBtn = document.getElementById('swipeLeft');
        const rightBtn = document.getElementById('swipeRight');

        function updateIndicators() {
          const scrollLeft = scrollContainer.scrollLeft;
          const scrollWidth = scrollContainer.scrollWidth - scrollContainer.clientWidth;
          const progress = Math.max(0, Math.min(1, scrollLeft / scrollWidth));
          const index = Math.round(progress * (tiles.length - 1)) || 0;

          // Dots
          indicators.innerHTML = '';
          for (let i = 0; i < Math.min(5, tiles.length); i++) {
            const dot = document.createElement('div');
            dot.className = `swipe-dot ${i === index ? 'active' : ''}`;
            dot.addEventListener('click', () => {
              const tileWidth = tiles[i]?.offsetWidth || 0;
              scrollContainer.scrollTo({ left: i * tileWidth, behavior: 'smooth' });
            });
            indicators.appendChild(dot);
          }
        }

        // Scroll events
        let scrollTimeout;
        scrollContainer.addEventListener('scroll', () => {
          scrollContainer.classList.add('swiping');
          updateIndicators();
          clearTimeout(scrollTimeout);
          scrollTimeout = setTimeout(() => {
            scrollContainer.classList.remove('swiping');
          }, 1500);
        }, { passive: true });

        // Arrow buttons (touch only)
        leftBtn?.addEventListener('click', () => scrollContainer.scrollBy({ left: -200, behavior: 'smooth' }));
        rightBtn?.addEventListener('click', () => scrollContainer.scrollBy({ left: 200, behavior: 'smooth' }));

        // Snap to tile edges on scroll end
        scrollContainer.addEventListener('scrollend', () => {
          const tilesInView = Array.from(tiles).filter(t => !t.hidden);
          const scrollLeft = scrollContainer.scrollLeft;
          const closest = tilesInView.reduce((prev, curr) =>
            Math.abs(curr.offsetLeft - scrollLeft) < Math.abs(prev.offsetLeft - scrollLeft) ? curr : prev
          );
          scrollContainer.scrollTo({ left: closest.offsetLeft, behavior: 'smooth' });
        });

        updateIndicators();
      }

      // ===== Lightbox (Bootstrap Modal) with Next/Prev (from new page) =====
      // Create modal HTML once
      const modalHtml = `
        <div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true">
          <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0">
              <div class="modal-body p-0 d-flex flex-column align-items-center">
                <button type="button" class="btn btn-light position-absolute top-0 end-0 m-3 rounded-circle shadow" data-bs-dismiss="modal" aria-label="Close">
                  <i class="fa-solid fa-xmark"></i>
                </button>

                <div class="w-100 text-center pt-4 pb-3">
                  <img id="lightboxImg" src="" alt="" class="img-fluid mx-auto rounded-3" style="max-height: calc(100vh - 10rem); object-fit: contain;">
                </div>

                <div class="w-100 d-flex align-items-center justify-content-between px-3 pb-3">
                  <button id="lbPrev" class="btn btn-dark rounded-pill px-3">
                    <i class="fa-solid fa-chevron-left me-1"></i> Prev
                  </button>
                  <div id="lightboxCaption" class="text-white px-3 py-2 rounded-pill bg-black bg-opacity-50 small"></div>
                  <button id="lbNext" class="btn btn-dark rounded-pill px-3">
                    Next <i class="fa-solid fa-chevron-right ms-1"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>`;
      document.body.insertAdjacentHTML('beforeend', modalHtml);

      const modalEl = document.getElementById('lightboxModal');
      const bsModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true });

      const imgEl = document.getElementById('lightboxImg');
      const captionEl = document.getElementById('lightboxCaption');
      const btnPrev = document.getElementById('lbPrev');
      const btnNext = document.getElementById('lbNext');

      let visible = [];   // currently visible tiles
      let index = 0;      // current index in visible

      function collectVisible() {
        visible = tiles.filter(el => !el.hidden);
      }

      function showAt(i) {
        if (!visible.length) return;
        index = (i + visible.length) % visible.length;
        const tile = visible[index];
        const src = tile.getAttribute('data-full');
        const caption = tile.getAttribute('data-caption') || '';
        imgEl.src = src;
        imgEl.alt = caption || 'Training image';
        captionEl.textContent = caption;
      }

      // Open lightbox on tile click
      grid.addEventListener('click', (e) => {
        const tile = e.target.closest('.gallery-tile');
        if (!tile) return;

        collectVisible();
        if (!visible.length) return;

        // set index to the clicked one
        index = visible.indexOf(tile);
        if (index < 0) index = 0;

        showAt(index);
        bsModal.show();
      });

      btnPrev.addEventListener('click', () => showAt(index - 1));
      btnNext.addEventListener('click', () => showAt(index + 1));

      // Keyboard navigation when modal is open
      modalEl.addEventListener('shown.bs.modal', () => {
        function onKey(e) {
          if (e.key === 'ArrowLeft') { e.preventDefault(); showAt(index - 1); }
          if (e.key === 'ArrowRight') { e.preventDefault(); showAt(index + 1); }
        }
        document.addEventListener('keydown', onKey);
        modalEl.addEventListener('hidden.bs.modal', () => {
          document.removeEventListener('keydown', onKey);
          imgEl.src = '';
        }, { once: true });
      });
    })();
  </script>

</body>

</html>
