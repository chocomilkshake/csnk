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
      if (!container || !titleEl || !leadEl || !imgEl) return;
aset.title) : btn.dataset.title;
</html>