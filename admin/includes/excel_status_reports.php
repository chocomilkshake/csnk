<?php
/**
 * FILE: includes/excel_status_reports.php
 * PURPOSE: Export "Applicant Status Change Reports" to Excel (.xlsx)
 * DRIVER: MySQLi (consistent with the rest of the app)
 */

declare(strict_types=1);

// Start session (to reuse stored search if needed)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* -------------------------------------------------
 *  Composer Autoload (same pattern as excel_onprocess.php)
 * -------------------------------------------------*/
$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
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

/* -------------------------------------------------
 *  Bootstrap DB ($database wrapper with getConnection() -> mysqli)
 * -------------------------------------------------*/
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
if (!isset($database) || !is_object($database)) {
    if (!class_exists('Database')) {
        @include_once __DIR__ . '/Database.php';
    }
    if (class_exists('Database')) {
        $database = new Database();
    }
}

/** Normalize: we need an object exposing getConnection() that returns mysqli */
if ($database instanceof mysqli) {
    $mysqliConn = $database;
    $database = new class($mysqliConn) {
        private $conn;
        public function __construct($conn) { $this->conn = $conn; }
        public function getConnection() { return $this->conn; }
    };
}
if (!is_object($database) || !method_exists($database, 'getConnection')) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database wrapper with getConnection() is required for export.\n";
    exit;
}
$conn = $database->getConnection(); // mysqli

/* -------------------------------------------------
 *  Input / Filters (?q=, ?from=YYYY-MM-DD, ?to=YYYY-MM-DD)
 * -------------------------------------------------*/
function clean_str(string $v): string {
    $v = trim($v);
    $v = str_replace(["\r\n", "\r"], "\n", $v);
    return filter_var($v, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
}

$q = isset($_GET['q']) ? clean_str((string)$_GET['q']) : '';
if ($q === '' && !empty($_SESSION['onproc_q'])) {
    // Reuse on-process search if present (optional)
    $q = (string)$_SESSION['onproc_q'];
}

$from = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
$to   = isset($_GET['to'])   ? trim((string)$_GET['to'])   : '';

$fromDate = $from !== '' ? date_create($from) : null;
$toDate   = $to   !== '' ? date_create($to)   : null;

/* -------------------------------------------------
 *  Build SQL (MySQLi, ? placeholders) + bind params
 * -------------------------------------------------*/
$sql = "
SELECT
    r.id                  AS report_id,
    r.applicant_id,
    r.from_status,
    r.to_status,
    r.report_text,
    r.admin_id,
    r.created_at,
    a.first_name, a.middle_name, a.last_name, a.suffix
FROM applicant_status_reports r
LEFT JOIN applicants a ON a.id = r.applicant_id
WHERE 1 = 1
";

$where = '';
$params = [];
$types  = '';

if ($q !== '') {
    // Escape LIKE metacharacters, then wrap with %...%
    $qEsc = addcslashes($q, '%_');
    $like = '%' . $qEsc . '%';
    $where .= "
      AND (
        CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.suffix) LIKE ?
        OR r.from_status LIKE ?
        OR r.to_status LIKE ?
        OR r.report_text LIKE ?
      )
    ";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'ssss';
}
if ($fromDate instanceof DateTimeInterface) {
    $where .= " AND r.created_at >= ? ";
    $params[] = $fromDate->format('Y-m-d 00:00:00');
    $types   .= 's';
}
if ($toDate instanceof DateTimeInterface) {
    $where .= " AND r.created_at <= ? ";
    $params[] = $toDate->format('Y-m-d 23:59:59');
    $types   .= 's';
}

$sql .= $where . " ORDER BY r.created_at DESC, r.id DESC ";

// Execute (MySQLi)
$rows = [];
try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    if (!empty($params)) {
        // bind_param requires references in PHP
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $res->free();
    }
    $stmt->close();
} catch (Throwable $e) {
    error_log('excel_status_reports query failed: ' . $e->getMessage());
    $rows = [];
}

// Optional: debug rows count (comment out in production)
// error_log('excel_status_reports: rows=' . count($rows));

/* -------------------------------------------------
 *  Build spreadsheet (theme aligned with your exporters)
 * -------------------------------------------------*/
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Status Change Reports');

$spreadsheet->getProperties()
    ->setCreator('CSNK Manpower Agency')
    ->setLastModifiedBy('CSNK Manpower Agency')
    ->setTitle('Applicant Status Change Reports')
    ->setSubject('Applicant Status Change Reports')
    ->setDescription('Export of applicant status change reports from CSNK Admin.')
    ->setCategory('Export');

$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

// Palette
$ink          = 'FF111827'; // #111827
$muted        = 'FF6B7280'; // #6B7280
$headerFill   = 'FFE5E7EB'; // #E5E7EB
$zebraFill    = 'FFF9FAFB'; // #F9FAFB
$borderLight  = 'FFE5E7EB'; // #E5E7EB

// Columns
$cols    = ['A','B','C','D','E','F','G','H'];
$headers = ['#', 'Applicant ID', 'Applicant', 'From Status', 'To Status', 'Report', 'Admin ID', 'Changed At'];
$lastHeaderCol = end($cols);

// Title & subtitle rows
$headerRow  = 4;
$dataStart  = $headerRow + 1;

// Optional logo (same path pattern as your other exporter)
$logoPath = realpath(__DIR__ . '/../resources/img/whychoose.png');
if ($logoPath && is_readable($logoPath)) {
    $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
    $drawing->setName('CSNK Logo');
    $drawing->setDescription('CSNK');
    $drawing->setPath($logoPath);
    $drawing->setHeight(46);
    $drawing->setCoordinates('H1');  // rightmost top
    $drawing->setOffsetX(4);
    $drawing->setOffsetY(2);
    $drawing->setWorksheet($sheet);
}

$sheet->setCellValue('B1', 'Applicant Status Change Reports');
$sheet->mergeCells('B1:H1');
$sheet->getStyle('B1')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($ink);
$sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$subtitleParts = ['Exported on ' . date('M j, Y') . ' | ' . date('h:i A')];
if ($q !== '')   { $subtitleParts[] = 'Filter: ' . $q; }
if ($from !== ''){ $subtitleParts[] = 'From: ' . $from; }
if ($to !== '')  { $subtitleParts[] = 'To: ' . $to; }
$sheet->setCellValue('B2', implode(' — ', $subtitleParts));
$sheet->mergeCells('B2:H2');
$sheet->getStyle('B2')->getFont()->setSize(10)->getColor()->setARGB($muted);
$sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getRowDimension(1)->setRowHeight(48);
$sheet->getRowDimension(2)->setRowHeight(18);
$sheet->getRowDimension(3)->setRowHeight(6);

// Headers
foreach ($headers as $i => $label) {
    $sheet->setCellValue($cols[$i] . $headerRow, $label);
}
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$headerRow}")->applyFromArray([
    'font' => ['bold' => true, 'color' => ['argb' => 'FF374151']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $headerFill]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
    'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => $borderLight]]],
]);
$sheet->getRowDimension($headerRow)->setRowHeight(22);

// Data
$row = $dataStart;
foreach ($rows as $r) {
    $fullName = trim(
        ((string)($r['first_name'] ?? '')) . ' ' .
        ((string)($r['middle_name'] ?? '')) . ' ' .
        ((string)($r['last_name'] ?? '')) . ' ' .
        ((string)($r['suffix'] ?? ''))
    );
    $fullName = $fullName !== '' ? $fullName : '—';

    $sheet->setCellValueExplicit("A{$row}", (int)$r['report_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    $sheet->setCellValueExplicit("B{$row}", (int)$r['applicant_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    $sheet->setCellValue("C{$row}", $fullName);
    $sheet->setCellValue("D{$row}", (string)$r['from_status']);
    $sheet->setCellValue("E{$row}", (string)$r['to_status']);
    $sheet->setCellValue("F{$row}", (string)$r['report_text']);
    $sheet->setCellValueExplicit("G{$row}", (string)$r['admin_id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    // Created at → Excel date if parsable
    $createdAt = (string)($r['created_at'] ?? '');
    $excelDate = null;
    if ($createdAt !== '') {
        $ts = strtotime($createdAt);
        if ($ts !== false) {
            $excelDate = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts);
        }
    }
    if ($excelDate !== null) {
        $sheet->setCellValue("H{$row}", $excelDate);
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
    } else {
        $sheet->setCellValue("H{$row}", $createdAt);
    }

    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:H{$row}")
              ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($zebraFill);
    }
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}

$lastDataRow = max($row - 1, $headerRow);

// Borders
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$lastDataRow}")
      ->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_HAIR)
      ->getColor()->setARGB($borderLight);

// Freeze + AutoFilter + Autosize
$sheet->freezePane("A{$dataStart}");
$sheet->setAutoFilter("A{$headerRow}:{$lastHeaderCol}{$headerRow}");
foreach ($cols as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Footer spacer
$footerRow = $lastDataRow + 2;
$sheet->setCellValue("A{$footerRow}", ' ');
$sheet->mergeCells("A{$footerRow}:{$lastHeaderCol}{$footerRow}");

// Download
$filename = 'status_change_reports_' . date('Ymd_His') . '.xlsx';
while (ob_get_level() > 0) { ob_end_clean(); }

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;