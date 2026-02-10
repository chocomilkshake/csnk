<?php
/**
 * FILE: includes/excel_applicants.php
 * PURPOSE: Export "List of Applicants" to Excel (.xlsx) with optional search filter via ?q=
 * DESIGN: Matches your export theme:
 *   - Logo top-right, centered title/subtitle
 *   - Zebra striping, thin borders, freeze panes, auto-filter
 *   - Excel date formatting for Date Applied
 */

declare(strict_types=1);

// Start session (to reuse stored search if needed)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* -------------------------------------------------
 *  Composer Autoload (try common locations)
 * -------------------------------------------------*/
$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',    // /csnk/vendor/autoload.php
    dirname(__DIR__, 2) . '/vendor/autoload.php', // two levels up
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
$autoload = null;
foreach ($autoloadCandidates as $candidate) {
    if (is_readable($candidate)) { $autoload = $candidate; break; }
}
if ($autoload === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Composer autoload not found:\n" . implode("\n", $autoloadCandidates) . "\n\n"
       . "Install PhpSpreadsheet from project root:\n"
       . "  composer require phpoffice/phpspreadsheet:^2 --with-all-dependencies\n"
       . "(For PHP < 8.1 use: ^1.29)\n";
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
 *  Project dependencies
 * -------------------------------------------------*/
require_once __DIR__ . '/Applicant.php';

// Try to obtain a database connection ($database) if not already defined
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

    // Fallback: project may expose a Database class wrapper
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
    echo "Database connection not found.\n"
       . "Ensure one of these files sets \$database (e.g., bootstrap.php / init.php / config.php / db.php).\n";
    exit;
}

/* -------------------------------------------------
 *  Utilities (mirror pages/applicants.php filtering)
 * -------------------------------------------------*/
function clean_str(string $v): string {
    $v = trim($v);
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return filter_var($v, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
}
function fullNameXls(array $r): string {
    $first  = (string)($r['first_name']   ?? '');
    $middle = (string)($r['middle_name']  ?? '');
    $last   = (string)($r['last_name']    ?? '');
    $suffix = (string)($r['suffix']       ?? '');
    $name = trim(trim($first . ' ' . ($middle ? ($middle . ' ') : '') . $last) . ' ' . $suffix);
    return $name !== '' ? $name : '—';
}
function renderPreferredLocationXls(?string $json): string {
    if (empty($json)) return 'N/A';
    $arr = json_decode($json, true);
    if (!is_array($arr)) {
        $fallback = trim($json);
        $fallback = trim($fallback, " \t\n\r\0\x0B[]\"");
        return $fallback !== '' ? $fallback : 'N/A';
    }
    $cities = array_values(array_filter(array_map('trim', $arr), fn($v) => is_string($v) && $v !== ''));
    if (empty($cities)) return 'N/A';
    return implode(', ', $cities);
}
function filterApplicantsByQuery(array $rows, string $query): array {
    if ($query === '') return $rows;
    $needle = mb_strtolower($query);

    return array_values(array_filter($rows, function(array $app) use ($needle) {
        $first  = (string)($app['first_name']   ?? '');
        $middle = (string)($app['middle_name']  ?? '');
        $last   = (string)($app['last_name']    ?? '');
        $suffix = (string)($app['suffix']       ?? '');
        $email  = (string)($app['email']        ?? '');
        $phone  = (string)($app['phone_number'] ?? '');
        $loc    = renderPreferredLocationXls($app['preferred_location'] ?? null);

        $fullName1 = trim($first . ' ' . $last);
        $fullName2 = trim($first . ' ' . $middle . ' ' . $last);
        $fullName3 = trim($last . ', ' . $first . ' ' . $middle);
        $fullName4 = trim($first . ' ' . $middle . ' ' . $last . ' ' . $suffix);

        $haystack = mb_strtolower(implode(' | ', [
            $first, $middle, $last, $suffix,
            $fullName1, $fullName2, $fullName3, $fullName4,
            $email, $phone, $loc
        ]));

        return mb_strpos($haystack, $needle) !== false;
    }));
}

/* -------------------------------------------------
 *  Read query (?q=) or reuse session
 * -------------------------------------------------*/
$q = '';
if (isset($_GET['q'])) {
    $q = clean_str((string)$_GET['q']);
    if (mb_strlen($q) > 100) $q = mb_substr($q, 0, 100);
} elseif (!empty($_SESSION['applicants_q'])) {
    $q = (string)$_SESSION['applicants_q'];
}

/* -------------------------------------------------
 *  Fetch rows
 * -------------------------------------------------*/
$applicant = new Applicant($database);
$rows = $applicant->getAll();

if ($q !== '') {
    $rows = filterApplicantsByQuery($rows, $q);
}

/* -------------------------------------------------
 *  Build spreadsheet (theme + layout)
 * -------------------------------------------------*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Applicants');

$spreadsheet->getProperties()
    ->setCreator('CSNK Manpower Agency')
    ->setLastModifiedBy('CSNK Manpower Agency')
    ->setTitle('Applicants Export')
    ->setSubject('Applicants')
    ->setDescription('Export of applicants from CSNK Admin.')
    ->setCategory('Export');

// Defaults
$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

// Palette
$ink          = 'FF111827'; // #111827
$muted        = 'FF6B7280'; // #6B7280
$headerFill   = 'FFE5E7EB'; // #E5E7EB
$zebraFill    = 'FFF9FAFB'; // #F9FAFB
$borderLight  = 'FFE5E7EB'; // #E5E7EB

// Layout rows:
// Row 1: Logo at G1
// Row 2: Subtitle merged B2:G2
// Row 3: Spacer
// Row 4: Table headers
// Row 5+: Data
$headerRow  = 4;
$dataStart  = $headerRow + 1;
$cols       = ['A','B','C','D','E','F','G'];
$lastHeaderCol = 'G';

// Insert logo (top-right)
$logoPath = realpath(__DIR__ . '/../resources/img/whychoose.png'); // includes/ -> ../resources/img
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
$title = 'List of Applicants';
$subtitle = 'Exported on ' . date('M j, Y') . ' | ' . date('h:i A');
if ($q !== '') { $subtitle .= ' — Filter: ' . $q; }

// Title (B1:G1)
$sheet->setCellValue('B1', $title);
$sheet->mergeCells('B1:G1');
$sheet->getStyle('B1')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($ink);
$sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

// Subtitle (B2:G2)
$sheet->setCellValue('B2', $subtitle);
$sheet->mergeCells('B2:G2');
$sheet->getStyle('B2')->getFont()->setSize(10)->getColor()->setARGB($muted);
$sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

// Adjust top rows to accommodate logo/title
$sheet->getRowDimension(1)->setRowHeight(48);
$sheet->getRowDimension(2)->setRowHeight(18);
$sheet->getRowDimension(3)->setRowHeight(6);

// Headers (no photo, match table columns except actions)
$headers = ['#', 'Name', 'Phone', 'Email', 'Location', 'Status', 'Date Applied'];
foreach ($headers as $i => $label) {
    $sheet->setCellValue($cols[$i] . $headerRow, $label);
}

// Header styles
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF374151']], // #374151
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $headerFill]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $borderLight]]],
]);
$sheet->getRowDimension($headerRow)->setRowHeight(22);

// Data rows
$row = $dataStart;
foreach ($rows as $r) {
    $id       = (int)($r['id'] ?? 0);
    $name     = fullNameXls($r);
    $phone    = (string)($r['phone_number'] ?? '');
    $email    = (string)($r['email'] ?? '');
    $loc      = renderPreferredLocationXls($r['preferred_location'] ?? null);
    $status   = (string)($r['status'] ?? '');
    $createdAt= (string)($r['created_at'] ?? '');

    $sheet->setCellValueExplicit("A{$row}", $id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    $sheet->setCellValue("B{$row}", $name);
    $sheet->setCellValueExplicit("C{$row}", $phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("D{$row}", $email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue("E{$row}", $loc);
    $sheet->setCellValue("F{$row}", ucwords(str_replace('_',' ', $status)));

    // Date Applied -> Excel date/time if parsable
    $excelDate = null;
    if ($createdAt !== '') {
        $ts = strtotime($createdAt);
        if ($ts !== false) {
            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts);
        }
    }
    if ($excelDate !== null) {
        $sheet->setCellValue("G{$row}", $excelDate);
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
    } else {
        $sheet->setCellValue("G{$row}", $createdAt);
    }

    // Zebra stripe every other row
    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:G{$row}")
              ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($zebraFill);
    }

    // Row height for readability
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}
$lastDataRow = max($row - 1, $headerRow);

// Thin borders around the data block
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$lastDataRow}")->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB($borderLight);

// Freeze panes and autofilter
$sheet->freezePane("A{$dataStart}");
$sheet->setAutoFilter("A{$headerRow}:{$lastHeaderCol}{$headerRow}");

// Autosize columns
foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Footer spacer
$footerRow = $lastDataRow + 2;
$sheet->setCellValue("A{$footerRow}", ' ');
$sheet->mergeCells("A{$footerRow}:{$lastHeaderCol}{$footerRow}");

// ------------- Download -------------
$filename = 'applicants_' . date('Ymd_His') . '.xlsx';

// Clean buffers to avoid BOM/whitespace corrupting the file
while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;