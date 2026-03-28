<?php
$page = 'about';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Determine Bahrain Business Unit ID dynamically
 */
function getBahrainBusinessUnitId($conn)
{
  $stmt = $conn->prepare("SELECT id FROM business_units WHERE (code LIKE '%bahrain%' OR name LIKE '%bahrain%') AND active = 1 LIMIT 1");
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
 */
$BASE = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

/**
 * Build absolute URL for assets
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
 */
function getContentImageUrl($path)
{
  global $BASE;
  if (empty($path))
    return '';
  $adminBase = str_replace('smc/smc-bahrain', 'admin', $BASE);
  return rtrim($adminBase, '/') . '/uploads/' . ltrim($path, '/');
}

/* ---------- Fetch data (CMS) ---------- */
$conn = getDbConnection();
$bahrainBuId = null;
$categories = [];
$contentItems = [];
$categoryCounts = [];
$totalItems = 0;

if ($conn) {
  $bahrainBuId = getBahrainBusinessUnitId($conn);

  if ($bahrainBuId) {
    // Categories (active only, scoped to Bahrain BU)
    $sql = "SELECT id, name, business_unit_id, is_active, display_order
            FROM content_categories
            WHERE business_unit_id = ? AND is_active = 1
            ORDER BY display_order ASC, id ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      $stmt->bind_param("i", $bahrainBuId);
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
      $stmt->bind_param("i", $bahrainBuId);
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
<html lang="en" dir="ltr">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SMC Manpower Agency Co.</title>

  <!-- SEO -->
  <meta name="description"
    content="Learn about SMC Manpower Agency Co. — DMW-licensed, compliance-first, and ethically driven recruitment connecting Bahrain-ready and global employers with skilled Filipino talent.">
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
      --smc-navy: #0B1F3A;
      /* deep navy */
      --smc-navy-2: #132A4A;
      /* secondary navy */
      --smc-navy-3: #1B355C;
      /* accent navy */
      --smc-navy-ink: #16243B;
      /* readable navy text */
      --smc-gold: #FFD84D;
      /* gold accent */
      --soft-bg: #f5f8ff;
      /* page background sections */
      --soft-border: #e6ecf5;
      /* soft border */
      --shadow: 0 12px 28px rgba(11, 31, 58, .12);
      --r-out: 1.25rem;
      --r-in: 1rem;

      /* Optional subtle accent */
      --accent-red: #CE1126;
      /* used minimally for emphasis */
      --accent-red-2: #B10F20;
    }

    html,
    body {
      background: #f8f9fb;
      color: var(--smc-navy-ink);
    }

    img,
    svg {
      max-width: 100%;
      height: auto;
    }

    /* RTL language mode */
    body.rtl {
      direction: rtl;
      font-family: "Noto Kufi Arabic", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
    }

    .rtl .flip-rtl {
      transform: scaleX(-1);
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

    .shadow-soft {
      box-shadow: 0 10px 24px rgba(13, 29, 54, .08);
    }

    .badge-soft {
      background: #fff;
      border: 1px solid rgba(11, 31, 58, .12);
      color: var(--smc-navy);
      border-radius: 999px;
      padding: .45rem .8rem;
      font-weight: 700;
    }

    .badge-gold {
      background: var(--smc-gold);
      color: var(--smc-navy);
      border-radius: 999px;
      padding: .45rem .8rem;
      font-weight: 800;
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
       Floating Language Toggle
       =========================== */
    .lang-toggle {
      position: fixed;
      top: 16px;
      left: 16px;
      z-index: 1040;
      display: inline-flex;
      align-items: center;
      gap: .5rem;
      background: #fff;
      color: #B10F20;
      border: 2px solid #B10F20;
      border-radius: 999px;
      padding: .4rem .9rem;
      font-weight: 900;
      box-shadow: 0 8px 22px rgba(206, 17, 38, .18), 0 1px 0 #fff inset;
      cursor: pointer;
    }

    .lang-toggle .dot {
      width: .5rem;
      height: .5rem;
      background: #CE1126;
      border-radius: 50%;
      display: inline-block;
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

    /* Gallery CSS (dynamic) */
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
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .gallery-tile:hover .gallery-tile-title {
      transform: translateY(0);
    }

    .gallery-tile[hidden] {
      display: none !important;
    }

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
      gap: 8px;
      padding: 6px;
      background: #fff;
      border-radius: 999px;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
      border: 1px solid #eee;
    }

    .btn-outline-secondary {
      height: 42px;
      padding: 0 1.1rem;
      font-size: 0.9rem;
      font-weight: 600;
      border-radius: 999px;
      white-space: nowrap;
      transition: all 0.25s;
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
      transition: all 0.2s;
    }

    #lightboxModal .btn-close:hover {
      background: #fff;
      transform: scale(1.1);
    }

    #lightboxModal .btn-close::after {
      content: '\\f00d';
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
      transition: all 0.2s;
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
      transition: all 0.2s;
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

    .training-gallery .btn-icon {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .training-gallery .btn-icon.btn-outline-secondary {
      border-color: var(--soft-border);
      color: var(--smc-navy);
    }

    .training-gallery .btn-icon.btn-outline-secondary:hover {
      background: var(--smc-navy);
      color: #fff;
    }

    .cta-wrap {
      background: radial-gradient(820px 260px at 8% 5%, rgba(255, 216, 77, .13), rgba(255, 216, 77, 0) 60%), radial-gradient(900px 320px at 92% 110%, rgba(19, 42, 74, .08), rgba(19, 42, 74, 0) 60%), linear-gradient(180deg, #ffffff 0%, #f8fbff 60%, #f4f8ff 100%);
      border-radius: var(--r-out);
      box-shadow: 0 16px 36px rgba(11, 31, 58, .08), 0 1px 0 rgba(255, 255, 255, .6) inset;
    }

    .is-swapping {
      opacity: .25;
      transition: opacity .15s ease;
    }

    .sr-only {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0, 0, 0, 0);
      border: 0;
    }
  </style>
</head>

<body class="bg-light">

  <!-- Floating Translate Button (EN ⇄ AR) -->
  <button id="langToggle" class="lang-toggle" type="button" aria-live="polite" aria-pressed="false"
    title="Translate to Arabic">
    <span class="dot" aria-hidden="true"></span>ta-i18n="mv.values_t">Values</h5>
                <p class="mb-0 text-muted" data-i18n="mv.values_d">Integrity, respect, safety, clarity, and continuous
                  improvement.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- TRAINING GALLERY (Dynamic - Step 4 Complete) -->
  <section id="training-gallery" class="training-gallery py-5 bg-white">
    <div class="container">
      <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-3">
        <h2 class="h1 fw-bold mb-0 text-navy">Gallery</h2>
        <div id="galleryFilters" class="gallery-filters-wrapper btn-group flex-wrap" role="group"
          aria-label="Gallery categories">
          <button type="button" class="btn btn-outline-secondary active" data-filter="all" aria-pressed="true">
            All<?= $totalItems > 0 ? " ($totalItems)" : "" ?>
          </button>
          <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat):
              $catName = $cat['name'] ?? 'Category';
              $catSlug = slugify($catName);
              $cnt = $categoryCounts[$catSlug] ?? 0;
              ?>
              <button type="button" class="btn btn-outline-secondary" data-filter="<?= htmlspecialchars($catSlug) ?>"
                aria-pressed="false">
                <?= htmlspecialchars($catName) ?>     <?= $cnt > 0 ? " ($cnt)" : "" ?>
              </button>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

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
                    <p class="gallery-tile-title"><?= htmlspecialchars($itemTitle) ?></p>
                  </div>
                </button>
              <?php endforeach; ?>
            <?php elseif ($bahrainBuId): ?>
              <div class="col-12 text-center py-5">
                <div class="alert alert-info">
                  <i class="fas fa-images me-2"></i>No gallery content yet.
                  <a href="<?= asset('../../admin/pages/content_management.php?agency=2') ?>" target="_blank">
                    Upload in Admin → Content Management → SMC/Bahrain
                  </a>
                </div>
              </div>
            <?php else: ?>
              <div class="col-12 text-center py-5">
                <div class="alert alert-warning">
                  <i class="fas fa-exclamation-triangle me-2"></i>Gallery coming soon! Business Unit setup required.
                </div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FINAL CTA -->
  <section class="py-4 py-md-5">
    <div class="container">
      <div class="p-3 p-md-4 cta-wrap">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <p class="mb-0 fw-bold text-navy" style="font-size:1.15rem" data-i18n="final.cta">
            Hire reliable, properly screened Filipino Skilled Workers.
          </p>
          <a class="btn btn-navy rounded-pill px-4" href="./applicant.php" aria-label="Hire Now">
            <span data-i18n="final.btn">Hire Now!</span> <i class="fa-solid fa-arrow-right ms-1 flip-rtl"></i>
          </a>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Counters, Hero, Gallery JS, i18n (existing + new gallery) -->
  <script>
    // Counters (existing)
    (function () { const counters = document.querySelectorAll('.counter-number'); const animate = el => { const target = +el.getAttribute('data-count'); const duration = 1200; const start = performance.now(); const step = now => { const p = Math.min((now - start) / duration, 1); el.textContent = Math.floor(p * target).toLocaleString(); if (p < 1) requestAnimationFrame(step); }; requestAnimationFrame(step); }; let triggered = false; const onScroll = () => { if (triggered) return; const rect = counters[0]?.getBoundingClientRect(); if (rect?.top < window.innerHeight) { counters.forEach(animate); triggered = true; window.removeEventListener('scroll', onScroll); } }; window.addEventListener('scroll', onScroll); onScroll(); })();

    // Hero swap
    (function () {
      const container = document.getElementById('heroPills');
      const titleEl = document.getElementById('heroTitle');
      const leadEl = document.getElementById('heroLead');
      const imgEl = document.getElementById('heroImg');
      if (!container || !titleEl || !leadEl || !imgEl) return;

      const pills = Array.from(container.querySelectorAll('.btn'));

      function getLang() {
        return localStorage.getItem('lang_about') || 'en';
      }

      function setActive(btn) {
        pills.forEach(b => {
          b.classList.remove('active');
          b.setAttribute('aria-selected', 'false');
        });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
      }

      function applyFrom(btn) {
        const lang = getLang();
        const title = lang === 'ar' ? (btn.dataset.titleAr || btn.dataset.title) : btn.dataset.title;
        const lead = lang === 'ar' ? (btn.dataset.leadAr || btn.dataset.lead) : btn.dataset.lead;

        if (title) titleEl.textContent = title;
        if (lead) leadEl.textContent = lead;
        if (btn.dataset.img) {
          imgEl.src = btn.dataset.img;
          imgEl.alt = btn.dataset.imgAlt || title || 'Hero image';
        }
      }

      function swap(btn) {
        setActive(btn);
        [titleEl, leadEl, imgEl].forEach(el => el.classList.add('is-swapping'));
        setTimeout(() => {
          applyFrom(btn);
          [titleEl, leadEl, imgEl].forEach(el => el.classList.remove('is-swapping'));
        }, 150);
      }

      pills.forEach(btn => {
        btn.addEventListener('click', () => swap(btn));
        btn.addEventListener('keydown', e => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            swap(btn);
          }
        });
      });

      window.updateHeroContentForLanguage = function () {
        const active = container.querySelector('.btn.active') || pills[0];
        if (active) applyFrom(active);
      };

      window.updateHeroContentForLanguage();
    })();

    // Dynamic Gallery JS (from Turkey - complete)
    (function () { const grid = document.getElementById('galleryGrid'); const filters = document.getElementById('galleryFilters'); const scrollContainer = document.getElementById('galleryScrollContainer'); if (!grid || !filters) return; const tiles = Array.from(grid.querySelectorAll('.gallery-tile')); const MAX_VISIBLE_ROWS = 4; const COLS_DESKTOP = 4, COLS_TABLET = 3, COLS_MOBILE = 2; let currentFilter = 'all'; function getColumns() { return window.innerWidth >= 992 ? COLS_DESKTOP : window.innerWidth >= 576 ? COLS_TABLET : COLS_MOBILE; } function getMaxVisible() { return MAX_VISIBLE_ROWS * getColumns(); } function updateGallery() { const maxVisible = getMaxVisible(); const filteredTiles = tiles.map((tile, idx) => ({ tile, cat: (tile.getAttribute('data-category-slug') || '').toLowerCase().trim(), originalIndex: idx })).filter(item => currentFilter === 'all' || item.cat === currentFilter); tiles.forEach(t => t.classList.remove('limited')); if (filteredTiles.length > maxVisible) { filteredTiles.forEach((item, idx) => { if (idx >= maxVisible) { item.tile.classList.add('limited'); item.tile.hidden = true; } else { item.tile.classList.remove('limited'); item.tile.hidden = false; } }); } else { filteredTiles.forEach(item => { item.tile.classList.remove('limited'); item.tile.hidden = false; }); } if (scrollContainer) { if (filteredTiles.length > maxVisible && window.innerWidth < 992) { scrollContainer.style.overflowX = 'auto'; scrollContainer.style.overflowY = 'hidden'; } else { scrollContainer.style.overflowX = ''; scrollContainer.style.overflowY = ''; } } } filters.addEventListener('click', e => { const btn = e.target.closest('button[data-filter]'); if (!btn) return; currentFilter = (btn.getAttribute('data-filter') || 'all').toLowerCase(); filters.querySelectorAll('button[data-filter]').forEach(b => { b.classList.toggle('active', b === btn); b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); }); tiles.forEach(tile => { const cat = (tile.getAttribute('data-category-slug') || '').toLowerCase().trim(); tile.hidden = !(currentFilter === 'all' || cat === currentFilter); }); updateGallery(); }); let resizeTimeout; window.addEventListener('resize', () => { clearTimeout(resizeTimeout); resizeTimeout = setTimeout(updateGallery, 150); }); updateGallery(); if (scrollContainer.dataset.indicators === 'true') { const indicators = document.getElementById('swipeIndicators'); const leftBtn = document.getElementById('swipeLeft'); const rightBtn = document.getElementById('swipeRight'); function updateIndicators() { const scrollLeft = scrollContainer.scrollLeft; const scrollWidth = scrollContainer.scrollWidth - scrollContainer.clientWidth; const progress = Math.max(0, Math.min(1, scrollLeft / scrollWidth)); const index = Math.round(progress * (tiles.length - 1)) || 0; indicators.innerHTML = ''; for (let i = 0; i < Math.min(5, tiles.length); i++) { const dot = document.createElement('div'); dot.className = `swipe-dot ${i === index ? 'active' : ''}`; dot.addEventListener('click', () => { const tileWidth = tiles[i]?.offsetWidth || 0; scrollContainer.scrollTo({ left: i * tileWidth, behavior: 'smooth' }); }); indicators.appendChild(dot); } } let scrollTimeout; scrollContainer.addEventListener('scroll', () => { scrollContainer.classList.add('swiping'); updateIndicators(); clearTimeout(scrollTimeout); scrollTimeout = setTimeout(() => { scrollContainer.classList.remove('swiping'); }, 1500); }, { passive: true }); leftBtn?.addEventListener('click', () => scrollContainer.scrollBy({ left: -200, behavior: 'smooth' })); rightBtn?.addEventListener('click', () => scrollContainer.scrollBy({ left: 200, behavior: 'smooth' })); scrollContainer.addEventListener('scrollend', () => { const tilesInView = Array.from(tiles).filter(t => !t.hidden); const scrollLeft = scrollContainer.scrollLeft; const closest = tilesInView.reduce((prev, curr) => Math.abs(curr.offsetLeft - scrollLeft) < Math.abs(prev.offsetLeft - scrollLeft) ? curr : prev); scrollContainer.scrollTo({ left: closest.offsetLeft, behavior: 'smooth' }); }); updateIndicators(); } // Lightbox (Bootstrap Modal) const modalHtml = `<div class="modal fade" id="lightboxModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered modal-xl"><div class="modal-content bg-transparent border-0"><div class="modal-body p-0 d-flex flex-column align-items-center"><button type="button" class="btn btn-light position-absolute top-0 end-0 m-3 rounded-circle shadow" data-bs-dismiss="modal" aria-label="Close"><i class="fa-solid fa-xmark"></i></button><div class="w-100 text-center pt-4 pb-3"><img id="lightboxImg" src="" alt="" class="img-fluid mx-auto rounded-3" style="max-height: calc(100vh - 10rem); object-fit: contain;"></div><div class="w-100 d-flex align-items-center justify-content-between px-3 pb-3"><button id="lbPrev" class="btn btn-dark rounded-pill px-3"><i class="fa-solid fa-chevron-left me-1"></i> Prev</button><div id="lightboxCaption" class="text-white px-3 py-2 rounded-pill bg-black bg-opacity-50 small"></div><button id="lbNext" class="btn btn-dark rounded-pill px-3">Next <i class="fa-solid fa-chevron-right ms-1"></i></button></div></div></div></div></div>`; document.body.insertAdjacentHTML('beforeend', modalHtml); const modalEl = document.getElementById('lightboxModal'); const bsModal = new bootstrap.Modal(modalEl, { backdrop: true, keyboard: true }); const imgEl = document.getElementById('lightboxImg'); const captionEl = document.getElementById('lightboxCaption'); const btnPrev = document.getElementById('lbPrev'); const btnNext = document.getElementById('lbNext'); let visible = []; let index = 0; function collectVisible() { visible = tiles.filter(el => !el.hidden); } function showAt(i) { if (!visible.length) return; index = (i + visible.length) % visible.length; const tile = visible[index]; const src = tile.getAttribute('data-full'); const caption = tile.getAttribute('data-caption') || ''; imgEl.src = src; imgEl.alt = caption || 'Training image'; captionEl.textContent = caption; } grid.addEventListener('click', e => { const tile = e.target.closest('.gallery-tile'); if (!tile) return; collectVisible(); if (!visible.length) return; index = visible.indexOf(tile); if (index < 0) index = 0; showAt(index); bsModal.show(); }); btnPrev.addEventListener('click', () => showAt(index - 1)); btnNext.addEventListener('click', () => showAt(index + 1)); modalEl.addEventListener('shown.bs.modal', () => { function onKey(e) { if (e.key === 'ArrowLeft') { e.preventDefault(); showAt(index - 1); } if (e.key === 'ArrowRight') { e.preventDefault(); showAt(index + 1); } } document.addEventListener('keydown', onKey); modalEl.addEventListener('hidden.bs.modal', () => { document.removeEventListener('keydown', onKey); imgEl.src = ''; }, { once: true }); }); // i18n (existing) const I18N_AR = { "hero.title": "تعرّف على شركة إس إم سي لتوظيف العمالة الفلبينية", "hero.lead": "إرشاد واضح وصادق يضع العميل أولاً. نصل بين أصحاب العمل والأسر والعمال الفلبينيين بعد فرز مناسب عبر عمليات آمنة ومتوافقة.", "hero.pill_overview": "نظرة عامة", "hero.pill_founder": "المؤسس", "hero.pill_mv": "الرسالة والرؤية", "hero.btn_apply": "عرض المتقدمين", "hero.btn_compliance": "امتثالنا", "trust.dmw": "ترخيص DMW", "trust.compliance": "الالتزام أولاً", "trust.ethical": "توظيف أخلاقي", "trust.support": "تواصل واضح", "trust.welfare": "رفاهية العامل", "mv.badge": "من نحن", "mv.title": "الرسالة • الرؤية • القيم", "mv.subtitle": "نبني عملنا على النزاهة والوضوح والخدمة—لنخدم أصحاب العمل وندعم المواهب الفلبينية.", "mv.mission_t": "رسالتنا", "mv.mission_d": "تقديم توظيف أخلاقي ومتوافق مع القوانين وبكرامة، مع إرشاد واضح من الفرز حتى الإيفاد.", "mv.vision_t": "رؤيتنا", "mv.vision_d": "أن نكون الجسر الموثوق بين أصحاب العمل في البحرين والعالم والعمال الفلبينيين—على أساس النزاهة والنتائج.", "mv.values_t": "قيمنا", "mv.values_d": "النزاهة والاحترام والسلامة والوضوح والتحسين المستمر.", "comp.badge": "التراخيص والامتثال", "comp.title": "نضع السلامة والالتزام والوضوح أولاً", "comp.l1": "<strong>ترخيص DMW:</strong> DMW-062-LB-03232023-R (سابقاً POEA)", "comp.l2": "<strong>توثيق شفاف:</strong> عقود وهويات وتصاريح وفحوصات طبية", "comp.l3": "<strong>توظيف أخلاقي:</strong> بلا رسوم غير قانونية؛ كرامة واحترام للعاملين", "comp.l4": "<strong>مواءمة للأنظمة:</strong> إجراءات البحرين والفلبين", "comp.note": "تختلف الجداول والمتطلبات حسب الدور والحالة.", "comp.docs_title": "تدفق المستندات القياسي", "comp.d1": "طلب التوظيف وتفاصيل الدور", "comp.d2": "الاستقطاب والفرز والمقابلات", "comp.d3": "العقود وفحوصات الامتثال", "comp.d4": "إجراءات التأشيرة والسفر", "comp.d5": "الإيفاد ودعم الاندماج", "comp.cta": "تحدث مع فريق الامتثال", "lead.badge": "القيادة", "lead.title": "خبرة وغاية تقودنا", "lead.subtitle": "رسالة يقودها احتكاك حقيقي بالعمل في الخارج وعقلية خدمية.", "lead.name": "السيد روجيليو إم. لانسنج", "lead.role": "المؤسس والرئيس", "lead.bio": "عمل كعامل فلبيني في الشرق الأوسط لمدة عشر سنوات (1989–2004). أسس إس إم سي لفتح فرص عمل عادلة وكريمة للفلبينيين وتوظيف موثوق للعملاء. مع نهج يضع الإنسان أولاً وعقلية امتثال، تواصل SMC تقديم خدمة مسؤولة.", "time.badge": "محطات", "time.title": "قصتنا بإيجاز", "time.m1": "تأسيس إدارة مجموعة شركات SMC", "time.m2": "تأسيس شركة إس إم سي للتوظيف", "time.m3": "تأكيد ترخيص DMW رقم DMW-062-LB-03232023-R", "quality.title": "سياسة الجودة", "quality.q1": "متطلبات وجداول زمنية واضحة", "quality.q2": "توثيق وفرز مُتحقق منه", "quality.q3": "تواصل شفاف وتحديثات", "quality.q4": "تحسين مستمر وتغذية راجعة", "ethics.title": "مدونة الأخلاقيات", "ethics.e1": "الاحترام والكرامة لكل المرشحين", "ethics.e2": "شروط عادلة وقانونية—بدون رسوم غير قانونية", "ethics.e3": "خصوصية البيانات وسريتها", "ethics.e4": "عدم التسامح مع أي سلوك مسيء", "metrics.yos": "سنوات الخدمة", "metrics.compliance": "% تركيز على الامتثال", "metrics.screened": "+ مرشحون مُفرزون", "metrics.response": "ساعات للاستجابة", "gallery.title": "المعرض", "final.cta": "وظّف عمالة فلبينية ماهرة ومُفرزة بعناية.", "final.btn": "وظّف الآن!" }; const i18nNodes = Array.from(document.querySelectorAll('[data-i18n]')); i18nNodes.forEach(n => { n.dataset.en = n.innerHTML; }); const setLang = lang => { const html = document.documentElement; const body = document.body; const toggle = document.getElementById('langToggle'); const label = document.getElementById('langToggleLabel'); if (lang === 'ar') { html.setAttribute('lang', 'ar'); html.setAttribute('dir', 'rtl'); body.classList.add('rtl'); i18nNodes.forEach(n => { const key = n.getAttribute('data-i18n'); const val = I18N_AR[key]; if (typeof val === 'string') n.innerHTML = val; }); toggle.setAttribute('aria-pressed', 'true'); toggle.setAttribute('title', 'Return to English'); label.textContent = 'EN'; } else { html.setAttribute('lang', 'en'); html.setAttribute('dir', 'ltr'); body.classList.remove('rtl'); i18nNodes.forEach(n => { n.innerHTML = n.dataset.en; }); toggle.setAttribute('aria-pressed', 'false'); toggle.setAttribute('title', 'Translate to Arabic'); label.textContent = 'AR'; } localStorage.setItem('lang_about', lang); }; const saved = localStorage.getItem('lang_about') || 'en'; setLang(saved); document.getElementById('langToggle').addEventListener('click', () => { const current = localStorage.getItem('lang_about') || 'en'; setLang(current === 'en' ? 'ar' : 'en'); });
  </script>
</body>

</html>