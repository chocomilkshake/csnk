<?php
function uploadFile($file, $folder = 'general') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
    $maxFileSize = 5 * 1024 * 1024; // 5MB

    $fileName = $file['name'];
    $fileSize = $file['size'];
    $fileTmp = $file['tmp_name'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    if (!in_array($fileExt, $allowedExtensions, true)) {
        return false;
    }

    if ($fileSize > $maxFileSize) {
        return false;
    }

    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $destination = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmp, $destination)) {
        return $folder . '/' . $newFileName;
    }

    return false;
}

function deleteFile($filePath) {
    $fullPath = UPLOAD_PATH . $filePath;
    if (is_string($filePath) && $filePath !== '' && file_exists($fullPath)) {
        return unlink($fullPath);
    }
    return false;
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'M d, Y h:i A') {
    return date($format, strtotime($datetime));
}

function sanitizeInput($data) {
    $data = trim((string)$data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $password = '';
    $maxIndex = strlen($characters) - 1;
    for ($i = 0; $i < $length; $i++) {
        // random_int is cryptographically secure
        $password .= $characters[random_int(0, $maxIndex)];
    }
    return $password;
}

function getFileUrl($filePath) {
    if (empty($filePath)) {
        return null;
    }
    return rtrim(UPLOAD_URL, '/') . '/' . ltrim($filePath, '/');
}

function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

function getFullName($firstName, $middleName, $lastName, $suffix = null) {
    $parts = [];
    if (!empty($firstName))  $parts[] = $firstName;
    if (!empty($middleName)) $parts[] = $middleName;
    if (!empty($lastName))   $parts[] = $lastName;

    $name = trim(implode(' ', $parts));
    if (!empty($suffix)) {
        $name .= ' ' . $suffix;
    }
    return $name;
}

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Safe redirect that works even if output already started.
 * Prevents: "Warning: Cannot modify header information - headers already sent..."
 */
function redirect($url) {
    // Normalize URL (avoid header splitting etc.)
    $url = filter_var($url, FILTER_SANITIZE_URL);

    if (!headers_sent()) {
        header("Location: $url", true, 302);
        exit();
    }

    // Fallback: JS + <noscript> meta refresh if headers already sent
    echo '<script>';
    echo 'window.location.href = ' . json_encode($url, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) . ';';
    echo '</script>';
    echo '<noscript>';
    echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
    echo '</noscript>';
    exit();
}