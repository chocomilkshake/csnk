<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$autoloadCandidates = [
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
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
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/functions.php';
require_once dirname(__DIR__) . '/admin-smc/smc-turkey/includes/applicant.php';

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function turkeyClean(string $value): string
{
    $value = trim($value);
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    return filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
}

function turkeyAllowedPage(string $page): string
{
    $allowed = ['pending', 'on_process', 'on_hold', 'deleted', 'approved'];
    return in_array($page, $allowed, true) ? $page : 'pending';
}

function turkeyRenderLocation(?string $json): string
{
    if (empty($json)) {
        return 'N/A';
    }

    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }

    $cities = array_values(array_filter(array_map('trim', $arr), static fn($v) => is_string($v) && $v !== ''));
    return empty($cities) ? 'N/A' : implode(', ', $cities);
}

function turkeyFullName(array $row): string
{
    return getFullName(
        (string) ($row['first_name'] ?? ''),
        (string) ($row['middle_name'] ?? ''),
        (string) ($row['last_name'] ?? ''),
        (string) ($row['suffix'] ?? '')
    ) ?: '—';
}

function turkeyToExcelDate(?string $value): array
{
    $value = (string) $value;
    if ($value === '') {
        return [null, ''];
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return [null, $value];
    }

    return [ExcelDate::PHPToExcel($ts), $value];
}

function turkeyFetchSmcBuIds(mysqli $conn): array
{
    $ids = [];
    $sql = "
        SELECT bu.id
        FROM business_units bu
        JOIN agencies ag ON ag.id = bu.agency_id
        WHERE ag.code = 'smc' AND bu.active = 1
        ORDER BY bu.id ASC
    ";

    if ($res = $conn->query($sql)) {
        while ($row = $res->fetch_assoc()) {
            $ids[] = (int) $row['id'];
        }
    }

    return $ids;
}

function turkeyFilterDeletedRows(array $rows, string $query): array
{
    if ($query === '') {
        return $rows;
    }

    $needle = mb_strtolower($query);

    return array_values(array_filter($rows, static function (array $row) use ($needle): bool {
        $haystack = mb_strtolower(implode(' | ', [
            (string) ($row['first_name'] ?? ''),
            (string) ($row['middle_name'] ?? ''),
            (string) ($row['last_name'] ?? ''),
            (string) ($row['suffix'] ?? ''),
            (string) ($row['email'] ?? ''),
            (string) ($row['phone_number'] ?? ''),
            turkeyRenderLocation($row['preferred_location'] ?? null),
        ]));

        return mb_strpos($haystack, $needle) !== false;
    }));
}

function turkeyFetchPendingLikeRows(mysqli $conn, array $smcBuIds, string $status, string $country, string $q): array
{
    if (empty($smcBuIds)) {
        return [];
    }

    $buPlaceholders = implode(',', array_fill(0, count($smcBuIds), '?'));
    $where = [];
    $types = '';
    $params = [];

    $where[] = "a.business_unit_id IN ($buPlaceholders)";
    $types .= str_repeat('i', count($smcBuIds));
    array_push($params, ...$smcBuIds);

    if ($status !== 'all') {
        $where[] = "a.status = ?";
        $types .= 's';
        $params[] = $status;
    } else {
        $where[] = "a.status IN ('pending','on_process','approved')";
    }

    if ($country !== 'all') {
        $where[] = "a.country_id = ?";
        $types .= 'i';
        $params[] = (int) $country;
    }

    $where[] = "a.deleted_at IS NULL";
    $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = a.id AND b.is_active = 1
    )";

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . " OR CONCAT_WS(' ', cb.client_first_name, cb.client_middle_name, cb.client_last_name) LIKE ?"
            . " OR cb.client_email LIKE ?"
            . " OR cb.client_phone LIKE ?"
            . ")";
        $types .= 'ssssss';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);

    $sql = "SELECT
                a.id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.suffix,
                a.phone_number,
                a.email,
                a.preferred_location,
                a.status,
                a.created_at,
                cb.client_first_name,
                cb.client_middle_name,
                cb.client_last_name,
                cb.client_phone,
                cb.client_email
            FROM applicants a
            LEFT JOIN (
                SELECT cb1.*
                FROM client_bookings cb1
                INNER JOIN (
                    SELECT applicant_id, MAX(created_at) AS max_created
                    FROM client_bookings
                    GROUP BY applicant_id
                ) cb2 ON cb1.applicant_id = cb2.applicant_id AND cb1.created_at = cb2.max_created
            ) cb ON a.id = cb.applicant_id
            WHERE $whereSql
            ORDER BY a.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

function turkeyFetchOnProcessRows(mysqli $conn, array $smcBuIds, string $status, string $country, string $q): array
{
    if (empty($smcBuIds)) {
        return [];
    }

    $buPlaceholders = implode(',', array_fill(0, count($smcBuIds), '?'));
    $where = [];
    $types = '';
    $params = [];

    $where[] = "a.business_unit_id IN ($buPlaceholders)";
    $types .= str_repeat('i', count($smcBuIds));
    array_push($params, ...$smcBuIds);

    if ($status !== 'all') {
        $where[] = "a.status = ?";
        $types .= 's';
        $params[] = $status;
    } else {
        $where[] = "a.status IN ('pending','on_process','approved')";
    }

    if ($country !== 'all') {
        $where[] = "a.country_id = ?";
        $types .= 'i';
        $params[] = (int) $country;
    }

    $where[] = "a.deleted_at IS NULL";
    $where[] = "NOT EXISTS (
        SELECT 1 FROM blacklisted_applicants b
        WHERE b.applicant_id = a.id AND b.is_active = 1
    )";

    if ($q !== '') {
        $like = '%' . $q . '%';
        $where[] = "("
            . "CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name) LIKE ?"
            . " OR a.email LIKE ?"
            . " OR a.phone_number LIKE ?"
            . " OR CONCAT_WS(' ', cb.client_first_name, cb.client_middle_name, cb.client_last_name) LIKE ?"
            . " OR cb.client_email LIKE ?"
            . " OR cb.client_phone LIKE ?"
            . ")";
        $types .= 'ssssss';
        array_push($params, $like, $like, $like, $like, $like, $like);
    }

    $whereSql = implode(' AND ', $where);
    $sql = "SELECT
                a.id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.suffix,
                a.phone_number,
                a.email,
                a.preferred_location,
                a.status,
                a.created_at,
                cb.client_first_name,
                cb.client_middle_name,
                cb.client_last_name,
                cb.client_phone,
                cb.client_email,
                cb.client_address,
                cb.appointment_type,
                cb.appointment_date,
                cb.appointment_time
            FROM applicants a
            LEFT JOIN (
                SELECT cb1.*
                FROM client_bookings cb1
                INNER JOIN (
                    SELECT applicant_id, MAX(created_at) AS max_created
                    FROM client_bookings
                    GROUP BY applicant_id
                ) cb2 ON cb1.applicant_id = cb2.applicant_id AND cb1.created_at = cb2.max_created
            ) cb ON a.id = cb.applicant_id
            WHERE $whereSql
            ORDER BY a.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return $rows;
}

$database = new Database();
$conn = $database->getConnection();
$applicant = new Applicant($database);

$page = turkeyAllowedPage(strtolower((string) ($_GET['page'] ?? 'pending')));
$q = turkeyClean((string) ($_GET['q'] ?? ''));
$country = (string) ($_GET['country'] ?? 'all');
$country = ($country === 'all' || ctype_digit($country)) ? $country : 'all';
$status = strtolower(trim((string) ($_GET['status'] ?? 'all')));

$rows = [];
$title = '';
$headers = [];
$filenamePrefix = 'turkey_applicants';
$sheetTitle = 'Turkey Export';
$dateColumn = null;

switch ($page) {
    case 'pending':
        $allowedStatuses = ['all', 'pending', 'on_process', 'approved'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'pending';
        }
        $rows = turkeyFetchPendingLikeRows($conn, turkeyFetchSmcBuIds($conn), $status, $country, $q);
        $title = 'Turkey Pending Applicants';
        $sheetTitle = 'Turkey Pending';
        $filenamePrefix = 'turkey_pending';
        $headers = ['#', 'Name', 'Phone', 'Email', 'Location', 'Status', 'Date Applied'];
        $dateColumn = 'G';
        break;

    case 'on_process':
        $allowedStatuses = ['all', 'pending', 'on_process', 'approved'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'on_process';
        }
        $rows = turkeyFetchOnProcessRows($conn, turkeyFetchSmcBuIds($conn), $status, $country, $q);
        $title = 'Turkey On-Process Applicants';
        $sheetTitle = 'Turkey OnProcess';
        $filenamePrefix = 'turkey_on_process';
        $headers = ['#', 'Applicant', 'Client', 'Interview', 'Date & Time', 'Applicant Contact', 'Client Contact', 'Date Applied'];
        $dateColumn = 'H';
        break;

    case 'on_hold':
        $allowedStatuses = ['on_hold', 'pending', 'on_process', 'approved'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'on_hold';
        }
        $buScope = (int) ($_SESSION['smc_bu_id'] ?? 0);
        $rows = $applicant->getApplicants(
            $buScope > 0 ? $buScope : null,
            $country !== 'all' ? (int) $country : null,
            $status,
            $q,
            true,
            true,
            1,
            1000
        );
        $title = 'Turkey On-Hold Applicants';
        $sheetTitle = 'Turkey OnHold';
        $filenamePrefix = 'turkey_on_hold';
        $headers = ['#', 'Name', 'Phone', 'Email', 'Location', 'Status', 'Date Applied'];
        $dateColumn = 'G';
        break;

    case 'deleted':
        $rows = $applicant->getDeleted();
        $rows = turkeyFilterDeletedRows($rows, $q);
        $title = 'Turkey Deleted Applicants';
        $sheetTitle = 'Turkey Deleted';
        $filenamePrefix = 'turkey_deleted';
        $headers = ['#', 'Name', 'Phone', 'Email', 'Location', 'Status', 'Date Deleted'];
        $dateColumn = 'G';
        break;

    case 'approved':
        $rows = $applicant->getApplicants(
            (int) ($_SESSION['smc_bu_id'] ?? 0) ?: null,
            $country !== 'all' ? (int) $country : null,
            'approved',
            $q,
            true,
            true,
            1,
            1000
        );
        $status = 'approved';
        $title = 'Turkey Approved Applicants';
        $sheetTitle = 'Turkey Approved';
        $filenamePrefix = 'turkey_approved';
        $headers = ['#', 'Name', 'Email', 'Phone', 'Preferred Location', 'Date Approved'];
        $dateColumn = 'F';
        break;
}

$subtitleParts = ['Exported on ' . date('M j, Y') . ' | ' . date('h:i A')];
if ($q !== '') {
    $subtitleParts[] = 'Search: ' . $q;
}
if ($country !== 'all') {
    $subtitleParts[] = 'Country ID: ' . $country;
}
if ($status !== 'all') {
    $subtitleParts[] = 'Status: ' . ucwords(str_replace('_', ' ', $status));
}
$subtitle = implode(' | ', $subtitleParts);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle($sheetTitle);
$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

$spreadsheet->getProperties()
    ->setCreator('SMC Manpower Agency')
    ->setLastModifiedBy('SMC Manpower Agency')
    ->setTitle($title . ' Export')
    ->setSubject($title)
    ->setDescription($title . ' export')
    ->setCategory('Export');

$headerRow = 4;
$dataStart = 5;
$cols = range('A', chr(ord('A') + count($headers) - 1));
$lastHeaderCol = end($cols);

$ink = 'FF111827';
$muted = 'FF6B7280';
$headerFill = 'FFE5E7EB';
$zebraFill = 'FFF9FAFB';
$borderLight = 'FFE5E7EB';

$logoPath = realpath(dirname(__DIR__, 2) . '/resources/img/whychoose.png');
if ($logoPath && is_readable($logoPath)) {
    $logo = new Drawing();
    $logo->setName('SMC Logo');
    $logo->setDescription('SMC');
    $logo->setPath($logoPath);
    $logo->setHeight(46);
    $logo->setCoordinates($lastHeaderCol . '1');
    $logo->setOffsetX(4);
    $logo->setOffsetY(2);
    $logo->setWorksheet($sheet);
}

$titleStart = 'B1';
$titleEnd = $lastHeaderCol . '1';
$subtitleStart = 'B2';
$subtitleEnd = $lastHeaderCol . '2';

$sheet->setCellValue($titleStart, $title);
$sheet->mergeCells($titleStart . ':' . $titleEnd);
$sheet->getStyle($titleStart)->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($ink);
$sheet->getStyle($titleStart)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->setCellValue($subtitleStart, $subtitle);
$sheet->mergeCells($subtitleStart . ':' . $subtitleEnd);
$sheet->getStyle($subtitleStart)->getFont()->setSize(10)->getColor()->setARGB($muted);
$sheet->getStyle($subtitleStart)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getRowDimension(1)->setRowHeight(48);
$sheet->getRowDimension(2)->setRowHeight(18);
$sheet->getRowDimension(3)->setRowHeight(6);

foreach ($headers as $index => $label) {
    $sheet->setCellValue($cols[$index] . $headerRow, $label);
}

$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF374151']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $headerFill]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $borderLight]]],
]);

$rowNum = $dataStart;
foreach ($rows as $row) {
    switch ($page) {
        case 'pending':
        case 'on_hold':
            $sheet->setCellValueExplicit("A{$rowNum}", (int) ($row['id'] ?? 0), DataType::TYPE_NUMERIC);
            $sheet->setCellValue("B{$rowNum}", turkeyFullName($row));
            $sheet->setCellValueExplicit("C{$rowNum}", (string) ($row['phone_number'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", (string) ($row['email'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$rowNum}", turkeyRenderLocation($row['preferred_location'] ?? null));
            $sheet->setCellValue("F{$rowNum}", ucwords(str_replace('_', ' ', (string) ($row['status'] ?? 'pending'))));
            [$excelDate, $rawDate] = turkeyToExcelDate((string) ($row['created_at'] ?? ''));
            if ($excelDate !== null) {
                $sheet->setCellValue("G{$rowNum}", $excelDate);
                $sheet->getStyle("G{$rowNum}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
            } else {
                $sheet->setCellValue("G{$rowNum}", $rawDate);
            }
            break;

        case 'deleted':
            $sheet->setCellValueExplicit("A{$rowNum}", (int) ($row['id'] ?? 0), DataType::TYPE_NUMERIC);
            $sheet->setCellValue("B{$rowNum}", turkeyFullName($row));
            $sheet->setCellValueExplicit("C{$rowNum}", (string) ($row['phone_number'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", (string) ($row['email'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$rowNum}", turkeyRenderLocation($row['preferred_location'] ?? null));
            $sheet->setCellValue("F{$rowNum}", 'Deleted');
            [$excelDate, $rawDate] = turkeyToExcelDate((string) ($row['deleted_at'] ?? $row['updated_at'] ?? ''));
            if ($excelDate !== null) {
                $sheet->setCellValue("G{$rowNum}", $excelDate);
                $sheet->getStyle("G{$rowNum}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
            } else {
                $sheet->setCellValue("G{$rowNum}", $rawDate);
            }
            break;

        case 'approved':
            $sheet->setCellValueExplicit("A{$rowNum}", (int) ($row['id'] ?? 0), DataType::TYPE_NUMERIC);
            $sheet->setCellValue("B{$rowNum}", turkeyFullName($row));
            $sheet->setCellValueExplicit("C{$rowNum}", (string) ($row['email'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("D{$rowNum}", (string) ($row['phone_number'] ?? ''), DataType::TYPE_STRING);
            $sheet->setCellValue("E{$rowNum}", turkeyRenderLocation($row['preferred_location'] ?? null));
            [$excelDate, $rawDate] = turkeyToExcelDate((string) ($row['created_at'] ?? ''));
            if ($excelDate !== null) {
                $sheet->setCellValue("F{$rowNum}", $excelDate);
                $sheet->getStyle("F{$rowNum}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
            } else {
                $sheet->setCellValue("F{$rowNum}", $rawDate);
            }
            break;

        case 'on_process':
            $clientName = trim(
                (string) ($row['client_first_name'] ?? '') . ' '
                . (string) ($row['client_middle_name'] ?? '') . ' '
                . (string) ($row['client_last_name'] ?? '')
            );
            $clientName = $clientName !== '' ? $clientName : '—';
            $appContact = trim((string) ($row['phone_number'] ?? '') . (!empty($row['email']) ? ' / ' . (string) $row['email'] : ''));
            $appContact = $appContact !== '' ? $appContact : '—';
            $clientContact = trim((string) ($row['client_phone'] ?? '') . (!empty($row['client_email']) ? ' / ' . (string) $row['client_email'] : ''));
            $clientContact = $clientContact !== '' ? $clientContact : '—';
            $dateTimeDisplay = trim((string) ($row['appointment_date'] ?? '') . ' ' . (string) ($row['appointment_time'] ?? ''));
            $dateTimeDisplay = $dateTimeDisplay !== '' ? $dateTimeDisplay : '—';

            $sheet->setCellValueExplicit("A{$rowNum}", (int) ($row['id'] ?? 0), DataType::TYPE_NUMERIC);
            $sheet->setCellValue("B{$rowNum}", turkeyFullName($row));
            $sheet->setCellValue("C{$rowNum}", $clientName);
            $sheet->setCellValue("D{$rowNum}", (string) ($row['appointment_type'] ?? '—'));
            $sheet->setCellValue("E{$rowNum}", $dateTimeDisplay);
            $sheet->setCellValue("F{$rowNum}", $appContact);
            $sheet->setCellValue("G{$rowNum}", $clientContact);
            [$excelDate, $rawDate] = turkeyToExcelDate((string) ($row['created_at'] ?? ''));
            if ($excelDate !== null) {
                $sheet->setCellValue("H{$rowNum}", $excelDate);
                $sheet->getStyle("H{$rowNum}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
            } else {
                $sheet->setCellValue("H{$rowNum}", $rawDate);
            }
            break;
    }

    if ($rowNum % 2 === 0) {
        $sheet->getStyle("A{$rowNum}:{$lastHeaderCol}{$rowNum}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB($zebraFill);
    }

    $rowNum++;
}

$lastDataRow = max($rowNum - 1, $headerRow);
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$lastDataRow}")
    ->getBorders()
    ->getAllBorders()
    ->setBorderStyle(Border::BORDER_HAIR)
    ->getColor()
    ->setARGB($borderLight);

$sheet->freezePane("A{$dataStart}");
$sheet->setAutoFilter("A{$headerRow}:{$lastHeaderCol}{$headerRow}");

foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}

$filename = $filenamePrefix . '_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
