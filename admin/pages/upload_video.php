<?php
declare(strict_types=1);



/* Optional during dev: log errors to a file but don't print them to output */
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/php_upload_errors.log'); // ensure the folder exists & writable

header('Content-Type: application/json');

// 1) Add this block near the top: resolve folders under the web root and create them if needed
$docRoot   = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/');
$videosDir = $docRoot . '/uploads/videos';
$thumbsDir = $docRoot . '/uploads/video_thumbnails';

if (!is_dir($videosDir) && !mkdir($videosDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cannot create videos directory.']);
    exit;
}
if (!is_dir($thumbsDir) && !mkdir($thumbsDir, 0775, true)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Cannot create thumbnails directory.']);
    exit;
}

//Helpers
function urlFromPathUnderDocRoot(string $absPath): string {
    $docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT']), '/');
    $abs     = str_replace('\\','/', $absPath);
    $rel     = substr($abs, strlen($docRoot)); // e.g. /uploads/videos/abc.mp4
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . $rel;
}


// 3) (Existing) validate request & file
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}
if (!isset($_FILES['video'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No video file uploaded']);
    exit;
}

$file = $_FILES['video'];
if (!empty($file['error'])) {
    echo json_encode(['success' => false, 'message' => 'Upload error code: '.$file['error']]);
    exit;
}

// 4) (Existing) validate size & MIME with finfo
$maxBytes = 500 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 500MB.']);
    exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$allowed = [
    'mp4'  => 'video/mp4',
    'webm' => 'video/webm',
    'ogg'  => 'video/ogg',
    'mov'  => 'video/quicktime',
    'mkv'  => 'video/x-matroska',
];
$ext = array_search($mime, $allowed, true);
if ($ext === false) {
    echo json_encode(['success' => false, 'message' => 'Unsupported video type: '.$mime]);
    exit;
}

// 5) (Existing) save file using the *public* directories defined above
$basename   = bin2hex(random_bytes(8)) . '_' . time();
$filename   = $basename . '.' . $ext;
$targetPath = $videosDir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file.']);
    exit;
}

// 6) (Optional) ffprobe/ffmpeg for duration & thumbnail
$durationSeconds = null;
$ffprobe = trim((string) shell_exec('command -v ffprobe'));
if ($ffprobe !== '') {
    $cmd = $ffprobe . ' -v error -show_entries format=duration -of default=nw=1:nk=1 ' . escapeshellarg($targetPath) . ' 2>&1';
    $out = shell_exec($cmd);
    if ($out !== null && is_numeric(trim($out))) {
        $durationSeconds = (int) round((float) trim($out));
    }
} elseif (isset($_POST['duration_hint']) && is_numeric($_POST['duration_hint'])) {
    $durationSeconds = (int) $_POST['duration_hint'];
}
$thumbnailUrl = null;
$ffmpeg = trim((string) shell_exec('command -v ffmpeg'));
if ($ffmpeg !== '') {
    $thumbPath = $thumbsDir . '/' . $basename . '.jpg';
    $cmd = $ffmpeg . ' -y -ss 00:00:01 -i ' . escapeshellarg($targetPath)
         . ' -frames:v 1 -vf "scale=640:-1" -q:v 3 ' . escapeshellarg($thumbPath) . ' 2>&1';
    shell_exec($cmd);
    if (file_exists($thumbPath)) {
        $thumbnailUrl = urlFromPathUnderDocRoot($thumbPath);
    }
}

// 7) Build URLs using the helper (these become your JSON response)
$videoUrl  = urlFromPathUnderDocRoot($targetPath);
$title     = $_POST['title'] ?? pathinfo($file['name'], PATHINFO_FILENAME);

// 8) Respond with JSON only
echo json_encode([
    'success' => true,
    'video_url' => $videoUrl,
    'video_provider' => 'file',
    'video_type' => $ext,
    'video_title' => $title,
    'video_thumbnail_url' => $thumbnailUrl,
    'video_duration_seconds' => $durationSeconds,
]);