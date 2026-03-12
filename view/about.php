<?php
// Set the active page for navbar highlighting
$page = 'about';

/**
 * PROJECT BASE URL
 * For localhost: /csnk
 * For production: change this to '' (empty) or your domain
 */
$BASE = '/csnk';

/**
 * Build absolute URL for assets (css, js, images)
 */
function asset(string $path): string {
  global $BASE;
  $path = '/' . ltrim($path, '/');
  return rtrim($BASE, '/') . $path;
}

/**
 * Get database connection using config
 */
function getDbConnection() {
  // Defaults
  $dbHost = 'localhost';
  $dbUser = 'root';
  $dbPass = '';
  $dbName = 'csnk';

  // Load from config file if exists
  $configFile = __DIR__ . '/admin/includes/config.php';
  if (file_exists($configFile)) {
    include $configFile;
    if (defined('DB_HOST')) $dbHost = DB_HOST;
    if (defined('DB_USER')) $dbUser = DB_USER;
    if (defined('DB_PASS')) $dbPass = DB_PASS;
    if (defined('DB_NAME')) $dbName = DB_NAME;
  }

  $conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
  if (!$conn) {
    return null;
  }
  return $conn;
}

/**
 * Helper: slugify label for safe filtering (EXACT match)
 */
function slugify(string $text): string {
  $text = trim($text);
  $text = mb_strtolower($text, 'UTF-8');
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  if (function_exists('iconv')) {
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($trans !== false) $text = $trans;
  }
  $text = preg_replace('~[^-\w]+~', '', $text);
  $text = trim($text, '-');
  $text = preg_replace('~-+~', '-', $text);
  return $text !== '' ? $text : 'n-a';
}

/**
 * Helper: build content image URL (uploaded via admin)
 * Your admin uploads are under: /csnk/admin/uploads/<path>
 */
function getContentImageUrl($path) {
  global $BASE;
  if (empty($path)) return '';
  $base = rtrim($BASE, '/');
  return $base . '/admin/uploads/' . ltrim($path, '/');
}

/* ---------- Fetch data (CMS) ---------- */
$conn = getDbConnection();
if (!$conn) {
  // DB not available - fallback to static state
  $categories = [];
  $contentItems = [];
  $categoryCounts = [];
  $totalItems = 0;
} else {
  // Categories (active only)
  $categories = [];
  $sql = "SELECT * FROM content_categories WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
  $result = mysqli_query($conn, $sql);
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $categories[] = $row;
    }
  }

  // Content items (active only) + join category name
  $contentItems = [];
  $sql = "SELECT ci.*, cc.name as category_name
          FROM content_items ci
          LEFT JOIN content_categories cc ON ci.category_id = cc.id
          WHERE ci.is_active = 1
          ORDER BY COALESCE(cc.display_order, 9999) ASC, ci.display_order ASC, ci.id ASC";
  $result = mysqli_query($conn, $sql);
  if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
      $contentItems[] = $row;
    }
  }
  mysqli_close($conn);

  // Category counts
  $categoryCounts = [];
  foreach ($contentItems as $itm) {
    $slug = slugify($itm['category_name'] ?? '');
    $categoryCounts[$slug] = ($categoryCounts[$slug] ?? 0) + 1;
  }
  $totalItems = count($contentItems);
}
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
  <link rel="icon" type="image/png" href="<?= asset('resources/img/csnk-icon.png') ?>">
</head>

<body class="bg-light">

  <!-- ✅ Reusable Navbar (old behavior preserved) -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- ===================== -->
  <!-- Page Content Starts   -->
  <!-- ===================== -->

  <style>
    /* ====================== */
    /* Modern Training Gallery */
    /* ====================== */

    .training-gallery{
      background:linear-gradient(180deg,#ffffff,#f8fafc);
    }

    /* FILTER BUTTONS */

    #galleryFilters{
      gap:.5rem;
    }

    #galleryFilters .btn{
      border-radius:999px;
      font-weight:600;
      padding:.45rem 1rem;
      font-size:.9rem;
      transition:all .2s ease;
    }

    #galleryFilters .btn:hover{
      transform:translateY(-1px);
    }

    #galleryFilters .btn.active{
      background:#b42a00;
      border-color:#b42a00;
      color:#fff;
      box-shadow:0 6px 14px rgba(180,42,0,.25);
    }

    /* GRID */

    .gallery-grid{
      display:grid;
      gap:16px;
      grid-template-columns:repeat(2,1fr);
      margin-top:20px;
    }

    @media(min-width:576px){
      .gallery-grid{
        grid-template-columns:repeat(3,1fr);
      }
    }

    @media(min-width:992px){
      .gallery-grid{
        grid-template-columns:repeat(4,1fr);
      }
    }

    /* TILE CARD */

    .gallery-tile{
      position:relative;
      border:0;
      padding:0;
      overflow:hidden;
      border-radius:16px;
      background:#fff;
      cursor:pointer;

      box-shadow:
      0 4px 12px rgba(0,0,0,.05),
      0 1px 0 rgba(255,255,255,.6) inset;

      transition:
      transform .25s ease,
      box-shadow .25s ease;
    }

    /* IMAGE */

    .gallery-tile img{
      width:100%;
      height:100%;
      aspect-ratio:1/1;
      object-fit:cover;

      transition:transform .5s ease;
    }

    /* HOVER EFFECT */

    .gallery-tile:hover{
      transform:translateY(-6px);
      box-shadow:
      0 16px 30px rgba(0,0,0,.12);
    }

    .gallery-tile:hover img{
      transform:scale(1.08);
    }

    /* OVERLAY */

    .gallery-overlay{
      position:absolute;
      inset:0;

      background:linear-gradient(
        to top,
        rgba(0,0,0,.55),
        rgba(0,0,0,.2),
        rgba(0,0,0,0)
      );

      display:flex;
      align-items:flex-end;
      padding:12px;

      opacity:0;
      transition:opacity .25s ease;
    }

    .gallery-tile:hover .gallery-overlay{
      opacity:1;
    }

    /* TITLE */

    .gallery-title{
      color:#fff;
      font-weight:600;
      font-size:.85rem;
    }

    /* CATEGORY BADGE */

    .gallery-badge{
      position:absolute;
      top:10px;
      left:10px;

      background:#b42a00;
      color:#fff;

      font-size:.65rem;
      font-weight:700;

      padding:.25rem .55rem;
      border-radius:999px;

      letter-spacing:.03em;
    }

    /* MOBILE TAP EFFECT */

    @media(max-width:768px){

      .gallery-overlay{
        opacity:1;
        background:linear-gradient(
          to top,
          rgba(0,0,0,.6),
          rgba(0,0,0,.2),
          rgba(0,0,0,0)
        );
      }

    }

    /* ====================== */
    /* FINAL CTA: Hire Now!   */
    /* ====================== */

    .cta-hire{
      background:
        radial-gradient(800px 260px at 8% 5%, rgba(255,170,120,.18), rgba(255,170,120,0) 60%),
        radial-gradient(1000px 320px at 92% 110%, rgba(12,32,76,.08), rgba(12,32,76,0) 60%),
        linear-gradient(180deg,#ffffff 0%,#fbfcff 60%,#f7f9fc 100%);

      border-radius:20px;
      padding:clamp(1rem,3vw,2rem) clamp(1rem,3.5vw,2rem);

      box-shadow:
      0 20px 40px rgba(13,29,54,.06),
      0 1px 0 rgba(255,255,255,.6) inset;
    }

    /* layout */

    .cta-row{
      display:grid;
      grid-template-columns:1fr;
      align-items:center;
      gap:1rem;
    }

    @media(min-width:768px){
      .cta-row{
        grid-template-columns:1fr auto;
      }
    }

    /* text */

    .cta-title{
      font-weight:800;
      font-size:clamp(1.1rem,2vw,1.35rem);
      color:#1b1d22;
      margin:0;
      line-height:1.4;
    }

    /* button area */

    .cta-actions{
      display:flex;
      justify-content:flex-start;
    }

    @media(min-width:768px){
      .cta-actions{
        justify-content:flex-end;
      }
    }

    /* CTA BUTTON */

    .cta-btn{

      background:linear-gradient(90deg,#ff7a3d,#ffb04a);

      color:#fff;
      border:none;

      border-radius:999px;

      padding:.85rem 1.6rem;

      font-weight:700;
      text-decoration:none;

      display:inline-flex;
      align-items:center;
      gap:.6rem;

      box-shadow:0 12px 26px rgba(255,122,61,.28);

      transition:
      transform .18s ease,
      box-shadow .18s ease,
      filter .18s ease;
    }

    .cta-btn:hover{
      transform:translateY(-2px);

      box-shadow:0 16px 34px rgba(255,122,61,.34);

      filter:brightness(1.05);

      color:#fff;
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
                data-title="Get to know CSNK"
                data-lead="CSNK Manpower Agency is dedicated to providing families with reliable 
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

        <!-- RIGHT: Image -->
        <div class="col-12 col-lg-6 hero-visual">
          <div class="hero-image-wrap rounded-4">
            <img id="heroImg" src="<?= asset('resources/img/hero1.jpg') ?>" alt="Hero visual" class="img-fluid fade-swap">
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
            All<?= $totalItems > 0 ? " ($totalItems)" : "" ?>
          </button>

          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat):
              $catName = $cat['name'] ?? 'Category';
              $catSlug = slugify($catName);
              $cnt = $categoryCounts[$catSlug] ?? 0;
            ?>
              <button type="button" class="btn btn-outline-secondary" data-filter="<?= htmlspecialchars($catSlug) ?>" aria-pressed="false">
                <?= htmlspecialchars($catName) ?><?= $cnt > 0 ? " ($cnt)" : "" ?>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- Thumbnails Grid (CMS-driven) -->
<div id="galleryGrid" class="gallery-grid">

<?php if (!empty($contentItems)): ?>

<?php foreach ($contentItems as $item):

$itemTitle = $item['title'] ?: 'Training image';
$catName   = $item['category_name'] ?? '';
$catSlug   = slugify($catName);
$imgUrl    = getContentImageUrl($item['image_path']);

?>

<button class="gallery-tile"
        data-category-slug="<?= htmlspecialchars($catSlug) ?>"
        data-full="<?= htmlspecialchars($imgUrl) ?>"
        data-caption="<?= htmlspecialchars($itemTitle) ?>"
        aria-label="Open <?= htmlspecialchars($itemTitle) ?>">

    <img src="<?= htmlspecialchars($imgUrl) ?>"
         alt="<?= htmlspecialchars($itemTitle) ?>">

    <!-- category badge -->
    <?php if($catName): ?>
    <div class="gallery-badge">
        <?= htmlspecialchars($catName) ?>
    </div>
    <?php endif; ?>

    <!-- overlay -->
    <div class="gallery-overlay">
        <div class="gallery-title">
            <?= htmlspecialchars($itemTitle) ?>
        </div>
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

  <!-- Page‑local: CMS Gallery filter + Bootstrap Lightbox with Next/Prev -->
  <script>
    (function () {
      const grid = document.getElementById('galleryGrid');
      const filters = document.getElementById('galleryFilters');
      if (!grid || !filters) return;

      const tiles = Array.from(grid.querySelectorAll('.gallery-tile'));

      // Filter buttons (All + dynamic categories)
      filters.addEventListener('click', (e) => {
        const btn = e.target.closest('button[data-filter]');
        if (!btn) return;

        const filter = (btn.getAttribute('data-filter') || 'all').toLowerCase();

        // Update active state
        filters.querySelectorAll('button[data-filter]').forEach(b => {
          b.classList.toggle('active', b === btn);
          b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
        });

        // Show/hide tiles based on slug (strict match)
        tiles.forEach(tile => {
          const cat = (tile.getAttribute('data-category-slug') || '').toLowerCase().trim();
          const show = (filter === 'all') ? true : (cat === filter);
          tile.hidden = !show;
        });
      });

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