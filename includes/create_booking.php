<?php
/**
 * Create Booking Request (client-facing, mysqli version)
 * Accepts JSON POST and saves to DB; returns { ok: true, booking_id, status_updated }
 */
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok'=>false, 'error'=>'Method not allowed']);
  exit;
}

require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/Database.php';

function fail(int $code, array $payload){ http_response_code($code); echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); exit; }
function required(array $src, string $key): string {
  if (!isset($src[$key])) fail(422, ['ok'=>false,'error'=>"Missing field: $key"]);
  $v = is_string($src[$key]) ? trim($src[$key]) : $src[$key];
  if ($v === '') fail(422, ['ok'=>false,'error'=>"Missing field: $key"]);
  return (string)$v;
}

try {
  $raw  = file_get_contents('php://input');
  $data = json_decode($raw, true);
  if (!is_array($data)) fail(400, ['ok'=>false,'error'=>'Invalid JSON']);

  // Required fields (names aligned with your modal JS)
  $applicant_id       = (int) required($data, 'applicant_id');
  $services           = $data['services'] ?? [];
  $appointment_type   = required($data, 'appointment_type'); // 'Video Call' | 'Audio Call' | 'Chat' | 'House Visit' | 'Office Visit'
  $appointment_date   = required($data, 'date');             // YYYY-MM-DD
  $appointment_time   = required($data, 'time');             // HH:MM
  $client_first_name  = required($data, 'client_first_name');
  $client_middle_name = (string)($data['client_middle_name'] ?? '');
  $client_last_name   = required($data, 'client_last_name');
  $client_phone       = required($data, 'client_phone');
  $client_email       = required($data, 'client_email');
  $client_address     = required($data, 'client_address');

  // Validate formats
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
    fail(422, ['ok'=>false,'error'=>'Invalid date format (YYYY-MM-DD)']);
  }
  if (!preg_match('/^\d{2}:\d{2}$/', $appointment_time)) {
    fail(422, ['ok'=>false,'error'=>'Invalid time format (HH:MM)']);
  }
  if (!filter_var($client_email, FILTER_VALIDATE_EMAIL)) {
    fail(422, ['ok'=>false,'error'=>'Invalid email address']);
  }

  // Whitelist appointment_type against your ENUM set to avoid SQL error
  $allowedTypes = ['Video Call','Audio Call','Chat','House Visit','Office Visit'];
  if (!in_array($appointment_type, $allowedTypes, true)) {
    fail(422, ['ok'=>false,'error'=>'Invalid appointment_type']);
  }

  // Normalize services
  if (!is_array($services)) $services = [];
  $services = array_values(array_filter(array_map(fn($x)=>trim((string)$x), $services)));
  $services_json = json_encode($services, JSON_UNESCAPED_UNICODE);

  // --- DB (mysqli) ---
  $db = (new Database())->getConnection();   // returns mysqli
  if (!($db instanceof mysqli)) {
    fail(500, ['ok'=>false, 'error'=>'Database connection is not mysqli instance']);
  }
  $db->set_charset('utf8mb4');

  $sql = "INSERT INTO client_bookings
          (applicant_id, services_json, appointment_type, appointment_date, appointment_time,
           client_first_name, client_middle_name, client_last_name, client_phone, client_email, client_address, status, created_at, updated_at)
          VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())";

  $stmt = $db->prepare($sql);
  if (!$stmt) fail(500, ['ok'=>false, 'error'=>'Prepare failed: '.$db->error]);

  $status = 'submitted';

  // bind_param types: i=integer, s=string
  $stmt->bind_param(
    'isssssssssss',
    $applicant_id,
    $services_json,
    $appointment_type,
    $appointment_date,
    $appointment_time,
    $client_first_name,
    $client_middle_name,
    $client_last_name,
    $client_phone,
    $client_email,
    $client_address,
    $status
  );

  if (!$stmt->execute()) {
    fail(500, ['ok'=>false, 'error'=>'Execute failed: '.$stmt->error]);
  }

  $booking_id = (int)$stmt->insert_id;
  $stmt->close();

  // NEW: auto-mark applicant as ON PROCESS
  $statusUpdated = false;
  $upd = $db->prepare("UPDATE applicants SET status = 'on_process', updated_at = NOW() WHERE id = ? AND deleted_at IS NULL");
  if ($upd) {
    $upd->bind_param('i', $applicant_id);
    if ($upd->execute()) {
      $statusUpdated = ($upd->affected_rows >= 0);
    }
    $upd->close();
  }

  http_response_code(201);
  echo json_encode(['ok'=>true, 'booking_id'=>$booking_id, 'status_updated'=>$statusUpdated], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  fail(500, ['ok'=>false, 'error'=>$e->getMessage()]);
}