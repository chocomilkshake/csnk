<?php
/**
 * =========================================================
 * FILE UPLOAD UTILITIES
 * =========================================================
 */

function uploadFile($file, $folder = 'general')
{
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedExtensions = [
        'jpg','jpeg','png','gif','pdf','doc','docx',
        'mp4','mov','webm','ogg','mkv','avi','mpeg'
    ];

    $maxFileSize = 200 * 1024 * 1024; // 200MB

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp  = $file['tmp_name'];
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions, true)) {
        return false;
    }

    if ($fileSize > $maxFileSize) {
        return false;
    }

    $uploadDir = rtrim(UPLOAD_PATH, '/') . '/' . trim($folder, '/') . '/';
    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            return false;
        }
    }

    if (!is_writable($uploadDir)) {
        return false;
    }

    $newFileName = uniqid('file_', true) . '.' . $fileExt;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        return $folder . '/' . $newFileName;
    }

    return false;
}

function getUploadErrorDescription(int $errorCode): string
{
    switch ($errorCode) {
        case UPLOAD_ERR_OK:
            return 'No upload error.';
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the server upload_max_filesize limit.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the form MAX_FILE_SIZE limit.';
        case UPLOAD_ERR_PARTIAL:
            return 'The file was only partially uploaded.';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'The server is missing a temporary upload folder.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'The server could not write the uploaded file to disk.';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload.';
        default:
            return 'Unknown upload error.';
    }
}

function explainUploadFailure(array $file, string $folder = 'general'): string
{
    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode !== UPLOAD_ERR_OK) {
        return getUploadErrorDescription($errorCode);
    }

    $allowedExtensions = [
        'jpg','jpeg','png','gif','pdf','doc','docx',
        'mp4','mov','webm','ogg','mkv','avi','mpeg'
    ];

    $fileName = (string) ($file['name'] ?? '');
    $fileSize = (int) ($file['size'] ?? 0);
    $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $maxFileSize = 200 * 1024 * 1024;

    if ($fileExt === '' || !in_array($fileExt, $allowedExtensions, true)) {
        return 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions) . '.';
    }

    if ($fileSize > $maxFileSize) {
        return 'The file exceeds the 200MB application upload limit.';
    }

    $uploadDir = rtrim(UPLOAD_PATH, '/') . '/' . trim($folder, '/') . '/';
    if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return 'The upload folder could not be created: ' . $uploadDir;
    }

    if (!is_writable($uploadDir)) {
        return 'The upload folder is not writable: ' . $uploadDir;
    }

    $uploadMax = ini_get('upload_max_filesize') ?: 'unknown';
    $postMax = ini_get('post_max_size') ?: 'unknown';
    return 'The server rejected the upload while moving the file. Check folder permissions and PHP limits (upload_max_filesize=' . $uploadMax . ', post_max_size=' . $postMax . ').';
}

function uploadMultipleFiles(array $filesControl, string $folder = 'general'): array
{
    $saved = [];

    if (!isset($filesControl['name']) || !is_array($filesControl['name'])) {
        return $saved;
    }

    $count = count($filesControl['name']);

    for ($i = 0; $i < $count; $i++) {
        $file = [
            'name'     => $filesControl['name'][$i],
            'type'     => $filesControl['type'][$i],
            'tmp_name' => $filesControl['tmp_name'][$i],
            'error'    => $filesControl['error'][$i],
            'size'     => $filesControl['size'][$i],
        ];

        $path = uploadFile($file, $folder);
        if ($path !== false) {
            $saved[] = $path;
        }
    }

    return $saved;
}

function deleteFile($filePath)
{
    if (!is_string($filePath) || $filePath === '') {
        return false;
    }

    $fullPath = rtrim(UPLOAD_PATH, '/') . '/' . ltrim($filePath, '/');
    return file_exists($fullPath) ? unlink($fullPath) : false;
}

/**
 * =========================================================
 * FORMATTERS & HELPERS
 * =========================================================
 */

function formatDate($date, $format = 'M d, Y')
{
    return $date ? date($format, strtotime($date)) : '';
}

function formatDateTime($datetime, $format = 'M d, Y h:i A')
{
    return $datetime ? date($format, strtotime($datetime)) : '';
}

function sanitizeInput($data)
{
    return htmlspecialchars(trim((string) $data), ENT_QUOTES, 'UTF-8');
}

function generateRandomPassword($length = 8)
{
    $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $pass  = '';
    $max   = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, $max)];
    }

    return $pass;
}

function getFileUrl($filePath)
{
    if (empty($filePath)) {
        return null;
    }
    return rtrim(UPLOAD_URL, '/') . '/' . ltrim($filePath, '/');
}

/**
 * =========================================================
 * FLASH MESSAGES
 * =========================================================
 */

function setFlashMessage($type, $message)
{
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function getFlashMessage()
{
    if (!empty($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * =========================================================
 * VALIDATION
 * =========================================================
 */

function getFullName($first, $middle, $last, $suffix = null)
{
    $parts = array_filter([$first, $middle, $last]);
    $name  = implode(' ', $parts);
    return $suffix ? $name . ' ' . $suffix : $name;
}

function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * =========================================================
 * SAFE REDIRECT
 * =========================================================
 */

function redirect($url)
{
    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (!headers_sent()) {
        header("Location: $url", true, 302);
        exit;
    }

    echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    echo '<noscript><meta http-equiv="refresh" content="0;url=' .
         htmlspecialchars($url, ENT_QUOTES) . '"></noscript>';
    exit;
}

/**
 * =========================================================
 * JSON & STRING HELPERS
 * =========================================================
 */

function json_to_array_safe($json)
{
    if (!$json || $json === '[]') return [];
    $arr = json_decode($json, true);
    return is_array($arr) ? $arr : [];
}

function normalize_string_array(array $arr): array
{
    $out = [];
    foreach ($arr as $v) {
        $s = strtolower(trim((string) $v));
        if ($s !== '' && !in_array($s, $out, true)) {
            $out[] = $s;
        }
    }
    return $out;
}

function overlap_count(array $a, array $b): int
{
    return count(array_intersect(
        normalize_string_array($a),
        normalize_string_array($b)
    ));
}

/**
 * =========================================================
 * CLIENT DATA
 * =========================================================
 */

function get_client_details(mysqli $conn, string $client_email): array
{
    $stmt = $conn->prepare("
        SELECT 
            CONCAT(client_first_name,' ',client_last_name) AS client_name,
            client_phone,
            business_unit_id
        FROM client_bookings
        WHERE client_email = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $stmt->bind_param("s", $client_email);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: [];
}

/**
 * ✅ FIXED: Get ONLY correct applicants per client
 */
function get_client_applicants(mysqli $conn, int $booking_id): array
{
    $apps = [];

    $sql = "
        SELECT
            CONCAT(
                a.first_name,
                IF(a.middle_name IS NOT NULL AND a.middle_name != '', CONCAT(' ', a.middle_name), ''),
                ' ',
                a.last_name,
                IF(a.suffix IS NOT NULL AND a.suffix != '', CONCAT(' ', a.suffix), '')
            ) AS full_name
        FROM client_bookings cb
        INNER JOIN applicants a ON a.id = cb.applicant_id
        WHERE cb.id = ?
          AND cb.status IN ('submitted','confirmed','on_process','approved')
          AND a.status IN ('pending','on_process','approved')
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $apps[] = [
            'name' => $row['full_name']
        ];
    }

    return $apps;
}

/**
 * =========================================================
 * EXPORT
 * =========================================================
 */

function exportToExcel(
    array $data,
    array $headers,
    string $title,
    string $subject,
    string $description,
    string $sheetTitle,
    string $filename
) {
    // TODO: implement export logic
}
