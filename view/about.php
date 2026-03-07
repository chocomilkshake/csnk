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
