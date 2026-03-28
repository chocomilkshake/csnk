<?php
// Set the active page for navbar highlighting
$page = 'about';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Determine Turkey Business Unit ID dynamically
 */
function getTurkeyBusinessUnitId($conn)
{
  $stmt = $conn->prepare("SELECT id FROM business_units WHERE (code LIKE '%turkey%' OR name LIKE '%turkey%') AND active = 1 LIMIT 1");
  if (!$stmt)
    return null;
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  return $row ? (int) $row['id'] : null;
}

/**
 * PROJECT BASE URL
 * For localhost: /csnk/smc/smc-turkey
 */
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

/**
 * Build absolute URL for assets (css, js, images)
 */
function asset($path)
{
  global $BASE;
  $path = '/' . ltrim($path, '/');
  return rtrim($BASE, '/') . $path;
}

/**
 * Get database connection using config
 */
function getDbConnection()
{
  $dbHost = 'localhost';
  $dbUser = 'root';
  $dbPass = '';
  $dbName = 'csnk';

  mysqli_report(MYSQLI_REPORT_OFF);

  $hosts = [$dbHost === 'localhost' ? '127.0.0.1' : $dbHost];

  foreach (array_unique($hosts) as $host) {
    $port = 3306;
    $socket = @fsockopen($host, $port, $errno, $errstr, 1);
    if (!$socket) {
      continue;
    }
    fclose($socket);

    $conn = mysqli_init();
    if (!$conn) {
      continue;
    }

    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 1);
    if (defined('MYSQLI_OPT_READ_TIMEOUT')) {
      mysqli_options($conn, MYSQLI_OPT_READ_TIMEOUT, 1);
    }

    if (@mysqli_real_connect($conn, $host, $dbUser, $dbPass, $dbName, $port)) {
      mysqli_set_charset($conn, 'utf8mb4');
      return $conn;
    }

    mysqli_close($conn);
  }

  return null;
}

/**
 * Helper: slugify label for safe filtering (EXACT match)
 */
function slugify(string $text): string
{
  $text = trim($text);
  $text = function_exists('mb_strtolower')
    ? mb_strtolower($text, 'UTF-8')
    : strtolower($text);
  $text = preg_replace('~[^\pL\d]+~u', '-', $text);
  if (function_exists('iconv')) {
    $trans = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    if ($trans !== false)
      $text = $trans;
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
function getContentImageUrl($path)
{
  global $BASE;
  if (empty($path))
    return '';
  $adminBase = str_replace('smc/smc-turkey', 'admin', $BASE);
  return rtrim($adminBase, '/') . '/uploads/' . ltrim($path, '/');
}

/* ---------- Fetch data (CMS) ---------- */
$conn = getDbConnection();
$turkeyBuId = null;
$categories = [];
$contentItems = [];
$categoryCounts = [];
$totalItems = 0;

if ($conn) {
  $turkeyBuId = getTurkeyBusinessUnitId($conn);

  if ($turkeyBuId) {
    // Categories (active only, scoped to Turkey BU)
    $sql = "SELECT id, name, business_unit_id, is_active, display_order
            FROM content_categories
            WHERE business_unit_id = ? AND is_active = 1
            ORDER BY display_order ASC, id ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param("i", $turkeyBuId);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
      }
      $stmt->close();
    }

    // Content items (active only) + join category name
    $sql = "SELECT ci.*, cc.name as category_name
            FROM content_items ci
            LEFT JOIN content_categories cc ON ci.category_id = cc.id
            WHERE ci.business_unit_id = ? AND ci.is_active = 1
            ORDER BY COALESCE(cc.display_order, 9999) ASC, ci.display_order ASC, ci.id ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param("i", $turkeyBuId);
      $stmt->execute();
      $result = $stmt->get_result();
      while ($row = $result->fetch_assoc()) {
        $contentItems[] = $row;
      }
      $stmt->close();
    }

    // Category counts
    foreach ($contentItems as $itm) {
      $slug = slugify($itm['category_name'] ?? '');
      $categoryCounts[$slug] = ($categoryCounts[$slug] ?? 0) + 1;
    }
    $totalItems = count($contentItems);
  }
  mysqli_close($conn);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SMC Manpower Agency Co.</title>

  <!-- ✅ FAVICONS -->
  <link rel="icon" type="image/png" href="/resources/img/smc.png" />
  <link rel="shortcut icon" href="/resources/img/smc.png" />
  <link rel="apple-touch-icon" href="/resources/img/smc.png" />
  <!-- Fallback for /view/ -->
  <link rel="icon" type="image/png" href="../resources/img/smc.png" />
  <link rel="apple-touch-icon" href="../resources/img/smc.png" />
  <meta name="theme-color" content="#0B1F3A">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <style>
    /* ===========================
       NAVY + GOLD THEME TOKENS
       =========================== */
    :root {
      --smc-navy: #0B1F3A;
      /* deep navy */
      --smc-navy-2: #132A4A;
      /* secondary navy */
      --smc-navy-3: #1B355C;
      /* accent navy */
      --smc-navy-ink: #16243B;
      /* readable navy text (no pure black) */
      --smc-gold: #FFD84D;
      /* gold accent */
      --soft-bg: #f5f8ff;
      /* page background sections */
      --soft-border: #e6ecf5;
      /* soft border */
      --shadow: 0 12px 28px rgba(11, 31, 58, .12);
      --r-out: 1.25rem;
      --r-in: 1rem;
    }

    body {
      color: var(--smc-navy-ink);
      background: #f8f9fb;
    }

    img,
    svg {
      max-width: 100%;
      height: auto;
    }

    .text-navy {
      color: var(--smc-navy) !important;
    }

    .text-muted-navy {
      color: #6f7e96 !important;
    }

    .border-soft {
      border: 1px solid var(--soft-border);
      border-radius: var(--r-in);
    }

    .btn-navy {
      background: linear-gradient(180deg, var(--smc-navy-3), var(--smc-navy));
      color: #fff;
      border: 0;
      border-radius: 999px;
      padding: .8rem 1.3rem;
      font-weight: 800;
      box-shadow: 0 12px 26px rgba(11, 31, 58, .22);
    }

    .btn-navy:hover {
      filter: brightness(1.03);
      color: #fff;
    }

    .btn-gold {
      background: linear-gradient(180deg, #ffe169, var(--smc-gold));
      color: #18243b;
      border: 0;
      border-radius: 999px;
      padding: .8rem 1.3rem;
      font-weight: 800;
      box-shadow: 0 12px 26px rgba(255, 216, 77, .25);
    }

    .btn-gold:hover {
      filter: brightness(1.03);
      color: #18243b;
    }

    /* ===========================
       HERO
       =========================== */
    .hero-section {
      background-color: #f8f9fb;
      position: relative;
      isolation: isolate;
      padding: clamp(2rem, 6vw, 5rem) 0;
    }

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

    .hero-section .container {
      position: relative;
      z-index: 1;
    }

    @media (max-width: 575.98px) {
      .hero-section {
        overflow: visible !important;
      }
    }

    /* Pills container */
    .hero-pills-abs-wrapper {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    #heroPills {
      display: inline-flex;
      width: max-content;
      max-width: 100%;
      gap: .5rem;
      align-items: center;
      padding: .5rem .6rem;
      border-radius: 999px;
      box-shadow: 0 4px 15px rgba(11, 31, 58, .08);
      background: #fff;
      scroll-snap-type: x proximity;
    }

    #heroPills .btn {
      flex: 0 0 auto;
      white-space: nowrap;
      scroll-snap-align: start;
    }

    .hero-section .btn-light.active {
      background: var(--smc-navy);
      color: #fff;
      border: 0;
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
      background: var(--smc-navy);
      box-shadow: 0 0 4px rgba(11, 31, 58, 0.8);
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
      background: linear-gradient(135deg, var(--smc-navy) 0%, var(--smc-navy-2) 100%);
      color: #fff;
      border-color: var(--smc-navy);
      box-shadow: 0 4px 15px rgba(11, 31, 58, 0.3);
    }

    .btn-outline-secondary:hover:not(.active) {
      background: #f8f9fa;
      color: var(--smc-navy-ink);
      border-color: #ccc;
    }

    /* Lightbox */
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
      color: var(--smc-navy-ink);
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
      color: var(--smc-navy-ink);
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
      color: var(--smc-navy-ink);
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
    .is-swapping {
      opacity: .25;
      transition: opacity .15s ease;
    }
  </style>
</head>

<body class="bg-light">

  <!-- ✅ Reusable Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <!-- ===================== -->
  <!-- Page Content Starts   -->
  <!-- ===================== -->

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
            <h1 id="heroTitle" class="display-4 fw-bold mb-0 text-navy">Get to know SMC Manpower Agency Philippines Co.
            </h1>
          </div>

          <div class="hero-lead-wrap mb-4">
            <p id="heroLead" class="lead text-muted-navy mb-0">
              Clear, honest and customer‑first guidance. We connec
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
