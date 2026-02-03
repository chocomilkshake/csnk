<?php
/**
 * Create Booking Request (client-facing)
 * Accepts JSON POST and saves to DB; sends email confirmation to client.
 * Response: { ok: true, booking_id: N }
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

require_once __DIR__ . '/../admin/includes/config.php';
require_once __DIR__ . '/../admin/includes/Database.php';

try{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new Exception('Invalid JSON');

    // Basic validation
    $required = ['applicant_id','services','appointment_type','date','time','client_first_name','client_last_name','client_phone','client_email','client_address'];
    foreach ($required as $k) {
        if (!isset($data[$k]) || $data[$k]==='') throw new Exception("Missing field: $k");
    }

    $db = (new Database())->getConnection();

    // Insert booking
    $stmt = $db->prepare("
        INSERT INTO client_bookings
          (applicant_id, services_json, appointment_type, appointment_date, appointment_time,
           client_first_name, client_middle_name, client_last_name, client_phone, client_email, client_address, status)
        VALUES (:applicant_id, :services_json, :appointment_type, :appointment_date, :appointment_time,
                :client_first_name, :client_middle_name, :client_last_name, :client_phone, :client_email, :client_address, 'submitted')
    ");
    $stmt->execute([
        ':applicant_id'      => (int)$data['applicant_id'],
        ':services_json'     => json_encode($data['services'], JSON_UNESCAPED_UNICODE),
        ':appointment_type'  => $data['appointment_type'],
        ':appointment_date'  => $data['date'],
        ':appointment_time'  => $data['time'],
        ':client_first_name' => $data['client_first_name'],
        ':client_middle_name'=> $data['client_middle_name'] ?? '',
        ':client_last_name'  => $data['client_last_name'],
        ':client_phone'      => $data['client_phone'],
        ':client_email'      => $data['client_email'],
        ':client_address'    => $data['client_address'],
    ]);
    $id = (int)$db->lastInsertId();

    // Send email confirmation (simple mail(); replace with PHPMailer if available)
    $to = $data['client_email'];
    $subject = 'Your Booking Request Has Been Received';
    $services = implode(', ', $data['services'] ?? []);
    $body = "Hello {$data['client_first_name']},\n\n".
            "Thank you for your booking request. Here are the details:\n".
            "Applicant ID: {$data['applicant_id']}\n".
            "Services: $services\n".
            "Interview Method: {$data['appointment_type']}\n".
            "Date & Time: {$data['date']} {$data['time']}\n".
            "Address: {$data['client_address']}\n\n".
            "We’ll reach out to confirm shortly.\n\n".
            "— CREMPCO / CSNK Manpower Agency";

    @mail($to, $subject, $body, "From: no-reply@{$_SERVER['HTTP_HOST']}");

    echo json_encode(['ok'=>true, 'booking_id'=>$id]);
} catch (Exception $e){
    http_response_code(400);
    echo json_encode(['error'=>$e->getMessage()]);
}