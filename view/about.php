<?php
// about.php
// Set the active page for navbar highlighting
$page = 'about';

/**
 * PROJECT BASE URL
 * If your site runs at http://localhost/csnk/, set this to '/csnk'
 * If your site runs at http://localhost/, set this to ''
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
 * Database connection for dynamic content
 */
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'csnk';

$conn = mysqli_connect($dbHost, $dbUser, $dbPass, $dbName);
if (!$conn) {
    http_response_code(500);
    echo "Database connection failed.";
    exit;
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

// ---------- Fetch data ----------

// Get active categories
$categories = [];
$sql = "SELECT * FROM content_categories WHERE is_active = 1 ORDER BY display_order ASC, id ASC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
}

// Get active content items
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

// Pre-compute category counts for pill badges
$categoryCounts = [];
$totalItems = count($contentItems);
foreach ($contentItems as $itm) {
    $slug = slugify($itm['category_name'] ?? '');
    $categoryCounts[$slug] = ($categoryCounts[$slug] ?? 0) + 1;
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

  <!-- Tailwind CSS (CDN) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            brand: { DEFAULT: '#b42a00', dark: '#8d2100', light: '#ffede6' },
            ink: '#0f1220',
            soft: '#f6f8fb'
          },
          boxShadow: {
            card: '0 6px 18px rgba(0,0,0,.08)',
            cardHover: '0 14px 36px rgba(0,0,0,.16)'
          }
        }
      }
    }
  </script>

  <style>
    /* ---------- HERO BACKDROP ---------- */
    .hero-backdrop {
      position: absolute; inset: 0; pointer-events: none; z-index: 0;
      background:
        radial-gradient(900px 360px at 15% 30%, rgba(255, 159, 169, 0.20), rgba(220, 53, 69, 0) 60%),
        radial-gradient(700px 280px at 85% 45%, rgba(17, 17, 17, .08), rgba(17, 17, 17, 0) 60%),
        linear-gradient(180deg, rgba(255,255,255,0) 0%, rgba(255,255,255,.35) 60%, rgba(255,84,84,0) 100%);
      mask-image: linear-gradient(to bottom, rgba(0,0,0,0) 0%, rgba(0,0,0,.95) 10%, rgba(0,0,0,.98) 85%, rgba(0,0,0,0) 100%);
    }
    .blob {
      position: absolute; filter: blur(40px); opacity: .55; z-index: -1;
      transform: translateZ(0);
      animation: floaty 14s ease-in-out infinite;
    }
    .blob--1 { width: 420px; height: 420px; left: -120px; top: -60px; background: radial-gradient(circle at 30% 30%, #ffd0c0, rgba(255,208,192,0)); }
    .blob--2 { width: 520px; height: 520px; right: -180px; top: 120px; background: radial-gradient(circle at 60% 40%, #ffe6d9, rgba(255,230,217,0)); animation-delay: -5s; }
    @keyframes floaty {
      0%, 100% { transform: translateY(0) translateX(0); }
      50% { transform: translateY(-10px) translateX(6px); }
    }

    .fade-swap { transition: opacity .22s ease, transform .22s ease; }
    .is-swapping { opacity: 0; transform: translateY(6px); }

    /* ===== Trainings Filters rail (mobile-first) ===== */
    .filters-rail{
      display:inline-flex;        /* single row */
      flex-wrap:nowrap;           /* never wrap */
      align-items:center;
      gap:.5rem;
      width:100%;
      padding:.5rem;
      border-radius:16px;
      background:#ffffff;
      box-shadow:0 6px 20px rgba(0,0,0,.08);
      overflow-x:auto;
      overflow-y:hidden;
      -webkit-overflow-scrolling:touch;
      scroll-snap-type:x proximity;
    }
    .filters-rail::-webkit-scrollbar{ height:0; }
    .filters-rail{ scrollbar-width:none; }

    .chip{
      scroll-snap-align:start;
      flex:0 0 auto;
      display:inline-flex;
      align-items:center;
      gap:.5rem;
      min-height:44px;
      padding:0 .9rem;
      border-radius:999px;
      background:#f1f5f9;
      color:#0f172a;
      font-weight:700;
      font-size:.95rem;
      border:1px solid #e2e8f0;
      transition:all .18s ease;
      white-space:nowrap;
    }
    .chip:hover{ background:#e9eef6; }
    .chip.active{
      background:#b42a00;
      color:#fff;
      border-color:#b42a00;
      box-shadow:0 4px 12px rgba(180,42,0,.35);
    }
    .chip-badge{
      display:inline-flex;
      align-items:center;
      justify-content:center;
      min-width:18px;
      height:18px;
      padding:0 .38rem;
      font-size:.72rem;
      font-weight:800;
      border-radius:999px;
      background:rgba(0,0,0,.08);
      color:#0f172a;
    }
    .chip.active .chip-badge{
      background:rgba(255,255,255,.22);
      color:#fff;
    }
    @media (max-width: 640px){
      .filters-rail{ padding:.45rem; }
    }

    /* Horizontal scrollbars helper (elsewhere if needed) */
    .scrollbar-slim::-webkit-scrollbar { height: 8px; }
    .scrollbar-slim::-webkit-scrollbar-track { background: transparent; }
    .scrollbar-slim::-webkit-scrollbar-thumb { background: #e5e7eb; border-radius: 999px; }
    .scrollbar-slim:hover::-webkit-scrollbar-thumb { background: #d1d5db; }

    /* Lightbox image should not exceed viewport */
    .lightbox-img {
      max-width: 100%;
      max-height: calc(100vh - 10rem);
      object-fit: contain;
    }
  </style>
</head>
<body class="bg-soft text-ink">

  <!-- Reusable Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- ===================== -->
  <!-- HERO (Modern 2026)   -->
  <!-- ===================== -->
  <section class="relative overflow-hidden">
    <div class="hero-backdrop"></div>
    <div class="blob blob--1"></div>
    <div class="blob blob--2"></div>

    <div class="container mx-auto px-4 md:px-6 lg:px-8 relative z-10">
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-14 items-center py-10 lg:py-16">

        <!-- Left: Copy -->
        <div>
          <div class="mb-3">
            <h1 id="heroTitle" class="fade-swap text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">
              Get to know CSNK Manpower Agency
            </h1>
          </div>

          <div class="mb-6">
            <p id="heroLead" class="fade-swap text-slate-700 text-base sm:text-lg leading-relaxed">
              Clear, honest and customer-first guidance. We connect families with properly
              screened domestic workers through safe and compliant processes.
            </p>
          </div>

          <!-- Modern pills container -->
          <div class="w-full overflow-x-auto scrollbar-slim">
            <div id="heroPills"
                 class="inline-flex gap-2 p-2 bg-white/90 backdrop-blur rounded-full shadow-card"
                 role="tablist" aria-label="Hero options">
              <button type="button"
                      class="px-4 py-2 rounded-full font-semibold transition-all bg-ink text-white shadow
                             focus:outline-none focus:ring-2 focus:ring-brand active"
                      role="tab" aria-selected="true"
                      data-title="Get to know CSNK"
                      data-lead="CSNK Manpower Agency is dedicated to providing families with reliable and compassionate household assistance. Beyond offering quality domestic help, we are a full-service manpower agency committed to supporting and empowering Filipino women by connecting them with safe, legitimate, and rewarding employment opportunities. Through proper screening, guidance, and documentation, we ensure that every home receives trustworthy service, while every applicant receives a fair chance to build a better future."
                      data-img="<?= asset('resources/img/overview2.png') ?>"
                      data-img-alt="Overview image">
                Overview
              </button>

              <button type="button"
                      class="px-4 py-2 rounded-full font-semibold transition-all bg-slate-100 text-ink hover:bg-slate-200
                             focus:outline-none focus:ring-2 focus:ring-brand"
                      role="tab" aria-selected="false"
                      data-title="Meet Founder of CSNK"
                      data-lead="CSNK was founded by Mr. Rogelio M. Lansang year 2010, driven by the mission to provide safe, legitimate and rewarding opportunities to Filipino women, carried out with integrity."
                      data-img="<?= asset('resources/img/MrRog.png') ?>"
                      data-img-alt="Founder image">
                Founder
              </button>
            </div>
          </div>
        </div>

        <!-- Right: Visual -->
        <div class="flex justify-center lg:justify-end">
          <div class="rounded-3xl shadow-card bg-white overflow-hidden max-w-md w-full">
            <img id="heroImg"
                 src="<?= asset('resources/img/hero1.jpg') ?>"
                 alt="Hero visual"
                 class="fade-swap w-full h-auto object-contain">
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ===================== -->
  <!-- Trainings (Modern)   -->
  <!-- ===================== -->
  <section id="training-gallery" class="py-10 md:py-14">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">

      <!-- Title -->
      <div class="mb-3 sm:mb-4">
        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold">Trainings</h2>
      </div>

      <!-- FILTERS: mobile-first horizontal rail -->
      <div class="relative mx-[-8px] sm:mx-0">
        <!-- Side fades -->
        <div class="pointer-events-none absolute left-0 top-0 h-full w-6 bg-gradient-to-r from-[#f6f8fb] to-transparent"></div>
        <div class="pointer-events-none absolute right-0 top-0 h-full w-6 bg-gradient-to-l from-[#f6f8fb] to-transparent"></div>

        <!-- Optional micro arrows (auto-hidden by JS) -->
        <button type="button" id="fltPrev"
                class="hidden absolute left-1 top-1/2 -translate-y-1/2 z-10 bg-white/90 backdrop-blur rounded-full shadow p-1.5 border border-slate-200"
                aria-label="Scroll left">
          <i class="fa-solid fa-chevron-left text-slate-700 text-xs"></i>
        </button>
        <button type="button" id="fltNext"
                class="hidden absolute right-1 top-1/2 -translate-y-1/2 z-10 bg-white/90 backdrop-blur rounded-full shadow p-1.5 border border-slate-200"
                aria-label="Scroll right">
          <i class="fa-solid fa-chevron-right text-slate-700 text-xs"></i>
        </button>

        <!-- Sticky on mobile so it stays under navbar -->
        <div class="sticky top-[68px] z-20 sm:static">
          <div id="galleryFilters" class="filters-rail" role="group" aria-label="Gallery categories">
            <!-- ALL chip -->
            <button type="button" class="chip active" data-filter="all" aria-pressed="true">
              <span class="chip-label">All</span>
              <?php if ($totalItems > 0): ?>
                <span class="chip-badge"><?= $totalItems ?></span>
              <?php endif; ?>
            </button>

            <!-- Dynamic chips -->
            <?php if (!empty($categories)): ?>
              <?php foreach ($categories as $cat):
                    $catSlug = slugify($cat['name'] ?? '');
                    $cnt = $categoryCounts[$catSlug] ?? 0;
              ?>
                <button type="button" class="chip" data-filter="<?= htmlspecialchars($catSlug) ?>" aria-pressed="false">
                  <span class="chip-label"><?= htmlspecialchars($cat['name'] ?? 'Category') ?></span>
                  <?php if ($cnt > 0): ?>
                    <span class="chip-badge"><?= $cnt ?></span>
                  <?php endif; ?>
                </button>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Grid -->
      <div id="galleryGrid" class="mt-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 md:gap-6">
        <?php if (!empty($contentItems)): ?>
          <?php foreach ($contentItems as $item):
                $itemTitle = $item['title'] ?: 'Training image';
                $catName   = $item['category_name'] ?? '';
                $catSlug   = slugify($catName);
                $imgUrl    = getContentImageUrl($item['image_path']);
          ?>
          <div class="gallery-item"
               data-category-slug="<?= htmlspecialchars($catSlug) ?>"
               data-full="<?= htmlspecialchars($imgUrl) ?>"
               data-caption="<?= htmlspecialchars($itemTitle) ?>">
            <!-- Card -->
            <div class="bg-white rounded-2xl shadow-card hover:shadow-cardHover transition
                        group overflow-hidden cursor-pointer training-card">
              <div class="w-full h-44 sm:h-48 md:h-56 bg-slate-100 overflow-hidden">
                <img src="<?= htmlspecialchars($imgUrl) ?>"
                     alt="<?= htmlspecialchars($itemTitle) ?>"
                     class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-[1.06]">
              </div>
              <?php if (!empty($item['title'])): ?>
                <div class="px-3.5 py-2.5">
                  <p class="m-0 font-semibold text-[0.98rem] leading-snug line-clamp-2">
                    <?= htmlspecialchars($item['title']) ?>
                  </p>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-span-2 sm:col-span-3 lg:col-span-4">
            <div class="text-center py-10 bg-white rounded-xl shadow-card">
              <p class="text-slate-500">Contents and Blogs soon!</p>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ====================== -->
  <!-- CTA: Hire Now!        -->
  <!-- ====================== -->
  <section class="py-8 md:py-12">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
      <div class="rounded-2xl bg-white shadow-card px-6 py-6 md:px-8 md:py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <p class="m-0 text-xl md:text-2xl font-extrabold text-ink">
          Hire reliable, properly screened Household Service Workers (HSWs) for your home.
        </p>
        <a class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-brand text-white fw-bold shadow hover:bg-brand-dark"
           href="./applicant.php" aria-label="Hire Now">
          Hire Now! <i class="fa-solid fa-arrow-right"></i>
