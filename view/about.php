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
    /* ---------- Base / utilities applicable to this page ---------- */
    img, svg { max-width: 100%; height: auto; }

    /* ---------- HERO SECTION ---------- */
    .hero-section {
      background-color: #f8f9fb;
      position: relative;
      isolation: isolate;
      padding: clamp(2rem, 6vw, 5rem) 0;
    }
    .hero-grid, .hero-gradient {
      position: absolute; inset: 0; z-index: 0; pointer-events: none;
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
    .hero-section .container { position: relative; z-index: 1; }
    @media (min-width: 992px) {
      .hero-section .display-4 { font-size: 3rem; line-height: 1.1; }
    }
    .fade-swap { transition: opacity .22s ease, transform .22s ease; }
    .is-swapping { opacity: 0; transform: translateY(6px); }

    /* ---------- PILL BAR ---------- */
    .hero-pills-abs-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    #heroPills {
      display: inline-flex; width: max-content; max-width: 100%;
      gap: .5rem; align-items: center; padding: .5rem .6rem;
      border-radius: 999px; box-shadow: 0 4px 15px rgba(0, 0, 0, .08);
      overflow: visible; flex-wrap: nowrap; scroll-snap-type: x proximity; background: #fff;
    }
    #heroPills .btn { flex: 0 0 auto; white-space: nowrap; scroll-snap-align: start; }
    .hero-section .btn-light.active { background: #111; color: #fff; }

    .hero-visual { display: flex; justify-content: center; }
    .hero-image-wrap {
      background: transparent; border-radius: 1rem;
      filter: drop-shadow(0 12px 22px rgba(0, 0, 0, .18));
      width: clamp(260px, 40vw, 520px);
    }
    .hero-image-wrap img { display: block; width: 100%; height: auto; object-fit: contain; }

    @media (max-width: 575.98px) {
      .hero-section { overflow: visible !important; }
      .hero-title-wrap .display-4 { font-size: 2rem; line-height: 1.2; }
      .hero-lead-wrap .lead { font-size: 1rem; }
      .hero-pills-abs-wrapper { margin-bottom: .5rem; }
      .hero-pills-spacer { height: 0; }
    }
    @media (max-width: 991.98px) {
      .hero-image-wrap { margin: 0 auto !important; transform: none !important; width: min(85vw, 420px); }
    }

    /* ====================== */
    /* Gallery with Category  */
    /* ====================== */
    .gallery-grid {
      display: grid;
      gap: 12px;
      grid-template-columns: repeat(2, 1fr);
    }
    @media (min-width: 576px) { .gallery-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (min-width: 992px) { .gallery-grid { grid-template-columns: repeat(4, 1fr); } }

    .gallery-tile {
      padding: 0; border: 0; background: transparent; border-radius: 12px; overflow: hidden;
      cursor: pointer;
    }
    .gallery-tile img {
      display: block; width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;
    }
    .gallery-tile[hidden] { display: none !important; }

    .btn-outline-secondary {
      height: 50px; width: auto; padding: 0 .9rem;
      font-size: 1rem; font-weight: 700;
    }
    .btn-outline-secondary.active { background-color: #b42a00; color: #fff; border-color: #b42a00; }
    .btn-outline-secondary:hover { background-color: #b2b2b2; color: #000000; }

    /* ====================== */
    /* FINAL CTA (Hire Now!)  */
    /* ====================== */
    .cta-hire {
      background:
        radial-gradient(800px 260px at 8% 5%, rgba(255, 170, 120, .18), rgba(255, 170, 120, 0) 60%),
        radial-gradient(1000px 320px at 92% 110%, rgba(12, 32, 76, .08), rgba(12, 32, 76, 0) 60%),
        linear-gradient(180deg, #ffffff 0%, #fbfcff 60%, #f7f9fc 100%);
      border-radius: 1.25rem;
      padding: clamp(1rem, 3vw, 2rem) clamp(1rem, 3.5vw, 2rem);
      box-shadow:
        0 20px 40px rgba(13, 29, 54, 0.06),
        0 1px 0 rgba(255, 255, 255, 0.6) inset;
    }
    .cta-row {
      display: grid; grid-template-columns: 1fr; align-items: center;
      gap: clamp(.75rem, 2vw, 1rem);
    }
    @media (min-width: 768px) { .cta-row { grid-template-columns: 1fr auto; } }
    .cta-title { font-weight: 800; font-size: clamp(1.05rem, 2.1vw, 1.35rem); color: #1b1d22; margin: 0; line-height: 1.35; }
    .cta-actions { display: flex; justify-content: flex-start; }
    @media (min-width: 768px) { .cta-actions { justify-content: flex-end; } }
    .cta-btn {
      --grad-a: #ff7a3d; --grad-b: #ffb04a;
      background: linear-gradient(90deg, var(--grad-a), var(--grad-b));
      color: #fff; border: 0; border-radius: 999px; padding: .85rem 1.5rem;
      font-weight: 700; letter-spacing: .2px; text-decoration: none;
      display: inline-flex; align-items: center; gap: .6rem;
      box-shadow: 0 12px 26px rgba(255, 122, 61, .28);
      transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
      position: relative; isolation: isolate; white-space: nowrap;
    }
    .cta-btn:hover, .cta-btn:focus {
      transform: translateY(-1px); box-shadow: 0 16px 34px rgba(255, 122, 61, .34);
      filter: brightness(1.03); color: #fff;
    }
    .cta-btn::after {
      content: "✦ ✦ ✦"; font-size: .85rem; color: #ffa95a;
      position: absolute; right: -2rem; top: 50%; transform: translateY(-50%);
      opacity: .95; pointer-events: none;
    }
    @media (max-width: 575.98px) { .cta-btn::after { right: -1.6rem; font-size: .8rem; } }
    @media (prefers-reduced-motion: reduce) { .cta-btn { transition: none !important; } }
  </style>allery-grid">
        <?php if (!empty($contentItems)): ?>
          <?php foreach ($contentItems as $item):
            $itemTitle = $item['title'] ?: 'Training image';
            $catName   = $item['category_name'] ?? '';
            $catSlug   = slugify($catName);
            $imgUrl    = getContentImageUrl($item['image_path']);
          ?>
            <
  <!-- ====================== -->
  <!-- FINAL CTA: Hire Now!  -->
  <!-- ====================== -->
  <section class="py-4 py-md-5">
    <div class="container">
      <div class="cta-hire">
        <div class="cta-row">
          <p class="cta-title">

        const filter = 
        if (!visib
</body>
</html>