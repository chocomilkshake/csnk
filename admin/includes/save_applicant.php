<?php
require_once __DIR__ . '/config.php'; // your DB connection file

// Example PDO $pdo from your Database.php
// $pdo = (new Database())->pdo();

$first  = $_POST['first_name']  ?? null;
$middle = $_POST['middle_name'] ?? null;
$last   = $_POST['last_name']   ?? null;
$suffix = $_POST['suffix']      ?? null;

// Video fields from hidden inputs
$video_url              = $_POST['video_url'] ?? null;
$video_provider         = $_POST['video_provider'] ?? null;  // expect 'file'
$video_type             = $_POST['video_type'] ?? null;      // 'mp4' or mime
$video_title            = $_POST['video_title'] ?? null;
$video_thumbnail_url    = $_POST['video_thumbnail_url'] ?? null;
$video_duration_seconds = isset($_POST['video_duration_seconds']) && $_POST['video_duration_seconds'] !== ''
    ? (int) $_POST['video_duration_seconds']
    : null;

// DEBUG (optional): log to file to ensure POST is present
// file_put_contents(__DIR__.'/save_debug.log', print_r($_POST, true), FILE_APPEND);

$sql = "INSERT INTO applicants
          (first_name, middle_name, last_name, suffix,
           video_url, video_provider, video_type, video_title, video_thumbnail_url, video_duration_seconds,
           status, created_at, updated_at)
        VALUES
          (:first_name, :middle_name, :last_name, :suffix,
           :video_url, :video_provider, :video_type, :video_title, :video_thumbnail_url, :video_duration_seconds,
           :status, NOW(), NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
  ':first_name' => $first,
  ':middle_name' => $middle,
  ':last_name' => $last,
  ':suffix' => $suffix,

  // IMPORTANT: do NOT force 'iframe' here; use the values from the upload
  ':video_url' => $video_url,
  ':video_provider' => $video_provider ?: 'file',
  ':video_type' => $video_type,
  ':video_title' => $video_title,
  ':video_thumbnail_url' => $video_thumbnail_url,
  ':video_duration_seconds' => $video_duration_seconds,

  ':status' => 'on_process'
]);

header('Location: ../pages/list-applicants.php');
exit;