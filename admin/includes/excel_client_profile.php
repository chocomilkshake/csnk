<?php
/**
 * FILE: includes/excel_client_profile.php
 * PURPOSE: Export Client Profile (with all bookings) to Excel (.xlsx)
 * DESIGN: Matches your export theme:
 *   - Logo top-right, centered title/subtitle
 *   - Zebra striping, thin borders, freeze panes, auto-filter
 *   - Excel date formatting
 */

declare(strict_types=1);

// Start session
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* -------------------------------------------------
 *  Composer Autoload
 * -------------------------------------------------*/
$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
$autoload = null;
foreach ($autoloadCandidates as $candidate) {
    if (is_readable($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if ($autoload === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Composer autoload not found.\n";
    exit;
}
require_once $autoload;

/* -------------------------------------------------
 *  Imports
 * -------------------------------------------------*/
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/* -------------------------------------------------
 *  Database connection
 * -------------------------------------------------*/
require_once __DIR__ . '/Applicant.php';

if (!isset($database) || !is_object($database)) {
    $bootstrapCandidates = [
        __DIR__ . '/bootstrap.php',
        __DIR__ . '/init.php',
        __DIR__ . '/config.php',
        __DIR__ . '/db.php',
        dirname(__DIR__) . '/config.php',
        dirname(__DIR__) . '/db.php',
    ];
    foreach ($bootstrapCandidates as $file) {
        if (is_readable($file)) {
            require_once $file;
        }
    }

    if (!isset($database)) {
        if (!class_exists('Database')) {
            @include_once __DIR__ . '/Database.php';
        }
        if (class_exists('Database')) {
            $database = new Database();
        }
    }
}

if (!isset($database) || !is_object($database)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database connection not found.\n";
    exit;
}

/* -------------------------------------------------
 *  Get Booking ID from URL
 * -------------------------------------------------*/
$bookingId = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($bookingId === 0) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Invalid client booking ID.\n";
    exit;
}

$conn = $database->getConnection();

if (!($conn instanceof mysqli)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database connection error.\n";
    exit;
}

/* -------------------------------------------------
 *  Fetch client data and bookings
 * -------------------------------------------------*/

// Get the initial booking to identify the client
$initialBooking = null;
$clientBookings = [];

$initialSql = "SELECT * FROM client_bookings WHERE id = ?";
$stmt = $conn->prepare($initialSql);
if ($stmt) {
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $initialBooking = $result->fetch_assoc();
    $stmt->close();
}

if (!$initialBooking) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Booking not found.\n";
    exit;
}

// Get client identification details
$clientFirstName = $initialBooking['client_first_name'];
$clientMiddleName = $initialBooking['client_middle_name'] ?? '';
$clientLastName = $initialBooking['client_last_name'];
$clientPhone = $initialBooking['client_phone'];
$clientEmail = $initialBooking['client_email'];
$clientAddress = $initialBooking['client_address'] ?? '';

// Build full name
$clientFullName = trim($clientFirstName . ' ' . $clientMiddleName . ' ' . $clientLastName);

// Find all bookings for this client
$searchSql = "
    SELECT 
        cb.id AS booking_id,
        cb.client_first_name,
        cb.client_middle_name,
        cb.client_last_name,
        cb.client_phone,
        cb.client_email,
        cb.client_address,
        cb.appointment_type,
        cb.appointment_date,
        cb.appointment_time,
        cb.status AS booking_status,
        cb.created_at AS booking_created_at,
        a.id AS applicant_id,
        a.first_name AS app_first_name,
        a.middle_name AS app_middle_name,
        a.last_name AS app_last_name,
        a.suffix AS app_suffix,
        a.status AS applicant_status,
        bu.name AS business_unit_name
    FROM client_bookings cb
    INNER JOIN applicants a ON a.id = cb.applicant_id
    LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
    WHERE (
        (cb.client_first_name = ? AND cb.client_last_name = ?)
        AND (cb.client_phone = ? OR cb.client_email = ?)
    )
    ORDER BY cb.created_at DESC
";

$stmt = $conn->prepare($searchSql);
if ($stmt) {
    $stmt->bind_param("ssss", $clientFirstName, $clientLastName, $clientPhone, $clientEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $clientBookings[] = $row;
    }
    $stmt->close();
}

// If no matches by phone/email, try by name only
if (empty($clientBookings)) {
    $searchSqlNameOnly = "
        SELECT 
            cb.id AS booking_id,
            cb.client_first_name,
            cb.client_middle_name,
            cb.client_last_name,
            cb.client_phone,
            cb.client_email,
            cb.client_address,
            cb.appointment_type,
            cb.appointment_date,
            cb.appointment_time,
            cb.status AS booking_status,
            cb.created_at AS booking_created_at,
            a.id AS applicant_id,
            a.first_name AS app_first_name,
            a.middle_name AS app_middle_name,
            a.last_name AS app_last_name,
            a.suffix AS app_suffix,
            a.status AS applicant_status,
            bu.name AS business_unit_name
        FROM client_bookings cb
        INNER JOIN applicants a ON a.id = cb.applicant_id
        LEFT JOIN business_units bu ON bu.id = cb.business_unit_id
        WHERE cb.client_first_name = ? AND cb.client_last_name = ?
        ORDER BY cb.created_at DESC
    ";

    $stmt = $conn->prepare($searchSqlNameOnly);
    if ($stmt) {
        $stmt->bind_param("ss", $clientFirstName, $clientLastName);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $clientBookings[] = $row;
        }
        $stmt->close();
    }
}

// Get replacement information for all bookings
$replacementMap = [];
if (!empty($clientBookings)) {
    $bookingIds = array_column($clientBookings, 'booking_id');
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));

    $replacementSql = "
        SELECT 
            ar.id,
            ar.client_booking_id,
            ar.original_applicant_id,
            ar.replacement_applicant_id,
            ar.status AS replacement_status,
            ar.created_at,
            ao.first_name AS original_first_name,
            ao.middle_name AS original_middle_name,
            ao.last_name AS original_last_name,
            ra.first_name AS replacement_first_name,
            ra.middle_name AS replacement_middle_name,
            ra.last_name AS replacement_last_name
        FROM applicant_replacements ar
        LEFT JOIN applicants ao ON ao.id = ar.original_applicant_id
        LEFT JOIN applicants ra ON ra.id = ar.replacement_applicant_id
        WHERE ar.client_booking_id IN ($placeholders)
        ORDER BY ar.created_at DESC
    ";

    $stmt = $conn->prepare($replacementSql);
    if ($stmt) {
        $types = str_repeat('i', count($bookingIds));
        $stmt->bind_param($types, ...$bookingIds);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $replacementMap[$row['client_booking_id']] = $row;
        }
        $stmt->close();
    }
}

// Get status counts
$statusCounts = ['pending' => 0, 'on_process' => 0, 'approved' => 0];
foreach ($clientBookings as $cb) {
    $status = $cb['applicant_status'] ?? 'pending';
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}
$totalBookings = count($clientBookings);

/* -------------------------------------------------
 *  Helper functions
 * -------------------------------------------------*/
function fullNameXls(?string $first, ?string $middle, ?string $last, ?string $suffix = null): string
{
    $parts = [];
    if (!empty($first))
        $parts[] = $first;
    if (!empty($middle))
        $parts[] = $middle;
    if (!empty($last))
        $parts[] = $last;
    $name = trim(implode(' ', $parts));
    if (!empty($suffix)) {
        $name .= ' ' . $suffix;
    }
    return $name !== '' ? $name : '—';
}

function formatDateXls(?string $date, string $format = 'M d, Y'): string
{
    if (empty($date))
        return '—';
    $ts = strtotime($date);
    if ($ts === false)
        return $date;
    return date($format, $ts);
}

function formatDateTimeXls(?string $datetime, string $format = 'M d, Y h:i A'): string
{
    if (empty($datetime))
        return '—';
    $ts = strtotime($datetime);
    if ($ts === false)
        return $datetime;
    return date($format, $ts);
}

/* -------------------------------------------------
 *  Build spreadsheet
 * -------------------------------------------------*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Client Profile');

$spreadsheet->getProperties()
    ->setCreator('CSNK Manpower Agency')
    ->setLastModifiedBy('CSNK Manpower Agency')
    ->setTitle('Client Profile Export')
    ->setSubject('Client Profile')
    ->setDescription('Export of client profile and bookings from CSNK Admin.')
    ->setCategory('Export');

$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

// Palette
$ink = 'FF111827';
$muted = 'FF6B7280';
$headerFill = 'FFE5E7EB';
$zebraFill = 'FFF9FAFB';
$borderLight = 'FFE5E7EB';

// Layout
// Row 1: Logo
// Row 2: Title
// Row 3: Subtitle
// Row 4: Spacer
// Row 5: Client Info Header
// Row 6-9: Client Details
// Row 10: Spacer
// Row 11: Summary Header
// Row 12-14: Summary Stats
// Row 15: Spacer
// Row 16: Bookings Table Header
// Row 17+: Bookings Data

$logoRow = 1;
$titleRow = 2;
$subtitleRow = 3;
$clientInfoHeaderRow = 5;
$summaryHeaderRow = 11;
$bookingsHeaderRow = 16;

// Insert logo
$logoPath = realpath(__DIR__ . '/../resources/img/whychoose.png');
if ($logoPath && is_readable($logoPath)) {
    $logo = new Drawing();
    $logo->setName('CSNK Logo');
    $logo->setDescription('CSNK');
    $logo->setPath($logoPath);
    $logo->setHeight(46);
    $logo->setCoordinates('G1');
    $logo->setOffsetX(4);
    $logo->setOffsetY(2);
    $logo->setWorksheet($sheet);
}

// Title & subtitle
$title = 'Client Profile Report';
$subtitle = 'Exported on ' . date('M j, Y') . ' | ' . date('h:i A');
$subtitle .= ' | Client: ' . $clientFullName;

$sheet->setCellValue('B2', $title);
$sheet->mergeCells('B2:G2');
$sheet->getStyle('B2')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($ink);
$sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->setCellValue('B3', $subtitle);
$sheet->mergeCells('B3:G3');
$sheet->getStyle('B3')->getFont()->setSize(10)->getColor()->setARGB($muted);
$sheet->getStyle('B3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getRowDimension(1)->setRowHeight(48);
$sheet->getRowDimension(2)->setRowHeight(28);
$sheet->getRowDimension(3)->setRowHeight(18);
$sheet->getRowDimension(4)->setRowHeight(6);

// =======================
// CLIENT INFORMATION
// =======================
$sheet->setCellValue('B5', 'CLIENT INFORMATION');
$sheet->mergeCells('B5:G5');
$sheet->getStyle('B5')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB($ink);
$sheet->getStyle('B5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(5)->setRowHeight(22);

// Client details - two columns
$sheet->setCellValue('B6', 'Full Name:');
$sheet->setCellValue('C6', $clientFullName);
$sheet->setCellValue('E6', 'Phone:');
$sheet->setCellValue('F6', !empty($clientPhone) ? $clientPhone : '—');

$sheet->setCellValue('B7', 'Email:');
$sheet->setCellValue('C7', !empty($clientEmail) ? $clientEmail : '—');

$sheet->setCellValue('B8', 'Address:');
$sheet->setCellValue('C8', !empty($clientAddress) ? $clientAddress : '—');

// Style client info
$sheet->getStyle('B6:B8')->getFont()->setBold(true);
$sheet->getStyle('B6:B8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('C6:C8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getStyle('E6:F8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

$sheet->getRowDimension(6)->setRowHeight(20);
$sheet->getRowDimension(7)->setRowHeight(20);
$sheet->getRowDimension(8)->setRowHeight(20);

// =======================
// BOOKING SUMMARY
// =======================
$sheet->setCellValue('B11', 'BOOKING SUMMARY');
$sheet->mergeCells('B11:G11');
$sheet->getStyle('B11')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB($ink);
$sheet->getStyle('B11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(11)->setRowHeight(22);

$sheet->setCellValue('B12', 'Total Bookings:');
$sheet->setCellValue('C12', $totalBookings);
$sheet->setCellValue('D12', 'Pending:');
$sheet->setCellValue('E12', $statusCounts['pending']);
$sheet->setCellValue('F12', 'On Process:');
$sheet->setCellValue('G12', $statusCounts['on_process']);
$sheet->setCellValue('H12', 'Approved:');
$sheet->setCellValue('I12', $statusCounts['approved']);

$sheet->getStyle('B12:I12')->getFont()->setBold(true);
$sheet->getStyle('B12:I12')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
$sheet->getRowDimension(12)->setRowHeight(20);

// =======================
// BOOKED APPLICANTS TABLE
// =======================
$sheet->setCellValue('B15', 'BOOKED APPLICANTS');
$sheet->mergeCells('B15:G15');
$sheet->getStyle('B15')->getFont()->setBold(true)->setSize(12)->getColor()->setARGB($ink);
$sheet->getStyle('B15')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
$sheet->getRowDimension(15)->setRowHeight(22);

// Table headers
$headers = ['#', 'Applicant Name', 'Status', 'Agency', 'Appointment Type', 'Schedule', 'Booked Date', 'Replaced Applicant'];
$cols = ['B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];
$lastHeaderCol = 'I';
$headerRow = 16;

foreach ($headers as $i => $label) {
    $sheet->setCellValue($cols[$i] . $headerRow, $label);
}

$sheet->getStyle("B{$headerRow}:{$lastHeaderCol}{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF374151']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $headerFill]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $borderLight]]],
]);
$sheet->getRowDimension($headerRow)->setRowHeight(22);

// Data rows
$row = $headerRow + 1;
$index = 1;

foreach ($clientBookings as $booking) {
    // Build applicant full name
    $applicantFullName = fullNameXls(
        $booking['app_first_name'] ?? '',
        $booking['app_middle_name'] ?? '',
        $booking['app_last_name'] ?? '',
        $booking['app_suffix'] ?? ''
    );

    // Contract term
    $apptDate = !empty($booking['appointment_date']) ? formatDateXls($booking['appointment_date']) : '—';
    $apptTime = !empty($booking['appointment_time']) ? date('h:i A', strtotime($booking['appointment_time'])) : '';
    $contractTerm = trim($apptDate . ' ' . $apptTime);

    // Booked date
    $bookedDate = !empty($booking['booking_created_at']) ? formatDateTimeXls($booking['booking_created_at']) : '—';

    // Status
    $appStatus = $booking['applicant_status'] ?? 'pending';
    $statusDisplay = ucfirst(str_replace('_', ' ', $appStatus));

    // Agency
    $agency = !empty($booking['business_unit_name']) ? $booking['business_unit_name'] : '—';

    // Appointment type
    $apptType = $booking['appointment_type'] ?? '—';

    // Replacement info
    $replacedApplicant = '—';
    $bookingIdKey = $booking['booking_id'];
    if (isset($replacementMap[$bookingIdKey])) {
        $rep = $replacementMap[$bookingIdKey];
        if (!empty($rep['replacement_applicant_id'])) {
            $replacedApplicant = fullNameXls(
                $rep['original_first_name'] ?? '',
                $rep['original_middle_name'] ?? '',
                $rep['original_last_name'] ?? ''
            );
        } elseif (!empty($rep['original_applicant_id'])) {
            $replacedApplicant = 'Pending Replacement';
        }
    }

    $sheet->setCellValue("B{$row}", $index);
    $sheet->setCellValue("C{$row}", $applicantFullName);
    $sheet->setCellValue("D{$row}", $statusDisplay);
    $sheet->setCellValue("E{$row}", $agency);
    $sheet->setCellValue("F{$row}", $apptType);
    $sheet->setCellValue("G{$row}", $contractTerm);
    $sheet->setCellValue("H{$row}", $bookedDate);
    $sheet->setCellValue("I{$row}", $replacedApplicant);

    // Zebra stripe
    if ($row % 2 === 0) {
        $sheet->getStyle("B{$row}:I{$row}")
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($zebraFill);
    }

    // Center align ID column
    $sheet->getStyle("B{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
    $index++;
}

$lastDataRow = max($row - 1, $headerRow);

// Thin borders
$sheet->getStyle("B{$headerRow}:{$lastHeaderCol}{$lastDataRow}")->getBorders()->getAllBorders()
    ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB($borderLight);

// Freeze panes
$sheet->freezePane("B17");

// Autofilter
$sheet->setAutoFilter("B{$headerRow}:{$lastHeaderCol}{$headerRow}");

// Autosize columns
foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Footer spacer
$footerRow = $lastDataRow + 2;
$sheet->setCellValue("B{$footerRow}", ' ');
$sheet->mergeCells("B{$footerRow}:{$lastHeaderCol}{$footerRow}");

/* -------------------------------------------------
 *  Download
 * -------------------------------------------------*/
$safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $clientFullName);
if (strlen($safeName) > 30) {
    $safeName = substr($safeName, 0, 30);
}
$filename = 'client_profile_' . $safeName . '_' . date('Ymd_His') . '.xlsx';

// Clean buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

