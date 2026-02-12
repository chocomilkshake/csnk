<?php
/**
 * FILE: includes/excel_reports.php
 * PURPOSE: Export Excel (.xlsx) with unified design
 *   - Mode A: All approved applicants + ALL admin reports (existing)
 *   - Mode B: Single applicant (when ?id=123 is passed)
 * DRIVER: MySQLi
 */

declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ------ Composer Autoload (tries common locations) ------ */
$autoloadCandidates = [
    dirname(__DIR__) . '/vendor/autoload.php',
    dirname(__DIR__, 2) . '/vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
];
$autoload = null;
foreach ($autoloadCandidates as $c) { if (is_readable($c)) { $autoload = $c; break; } }
if (!$autoload) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Composer autoload not found.\n";
    exit;
}
require_once $autoload;

/* ------ PhpSpreadsheet ------ */
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/* ------ Bootstrap DB (tries your typical includes) ------ */
$bootstrapCandidates = [
    __DIR__ . '/bootstrap.php', __DIR__ . '/init.php', __DIR__ . '/config.php', __DIR__ . '/db.php',
    dirname(__DIR__) . '/includes/config.php', // common in your app
    dirname(__DIR__) . '/config.php',
    dirname(__DIR__) . '/db.php',
];
foreach ($bootstrapCandidates as $f) { if (is_readable($f)) { require_once $f; } }
if (!isset($database) || !is_object($database)) {
    if (!class_exists('Database')) { @include_once __DIR__ . '/Database.php'; }
    if (class_exists('Database')) { $database = new Database(); }
}
if ($database instanceof mysqli) {
    $mysqliConn = $database;
    $database = new class($mysqliConn) { private $c; public function __construct($c){$this->c=$c;} public function getConnection(){return $this->c;} };
}
if (!method_exists($database, 'getConnection')) {
    http_response_code(500); header('Content-Type: text/plain; charset=UTF-8');
    echo "Database wrapper with getConnection() is required for export.\n"; exit;
}
$conn = $database->getConnection();

/* ------ Inputs / filter ------ */
function clean_str(string $v): string {
    $v = trim($v); $v = str_replace(["\r\n","\r"], "\n", $v);
    return filter_var($v, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
}
$q  = isset($_GET['q']) ? clean_str((string)$_GET['q']) : '';
$id = isset($_GET['id']) ? max(0, (int)$_GET['id']) : 0;
if ($q === '' && !empty($_SESSION['reports_q'])) $q = (string)$_SESSION['reports_q'];

/* ------ Helpers ------ */
function fullNameXls(array $r): string {
    $first = (string)($r['first_name']??''); $middle=(string)($r['middle_name']??'');
    $last  = (string)($r['last_name']??'');  $suffix=(string)($r['suffix']??'');
    $name  = trim(trim($first . ' ' . ($middle?($middle.' '):'') . $last) . ' ' . $suffix);
    return $name !== '' ? $name : '—';
}

/* ------ Common palette and fonts (same design) ------ */
$ink='FF111827'; $muted='FF6B7280'; $headerFill='FFE5E7EB'; $zebraFill='FFF9FAFB'; $borderLight='FFE5E7EB';

/* ======================================================================
 * MODE B: Single applicant export when ?id=123 is passed
 * ====================================================================*/
if ($id > 0) {
    // Load applicant (basic details)
    $app = null;
    $stmtA = $conn->prepare("
        SELECT id, first_name, middle_name, last_name, suffix, email, phone_number, created_at AS approved_at
        FROM applicants
        WHERE id=? AND deleted_at IS NULL
        LIMIT 1
    ");
    if ($stmtA) {
        $stmtA->bind_param('i', $id);
        $stmtA->execute();
        $resA = $stmtA->get_result();
        $app = $resA ? $resA->fetch_assoc() : null;
        $stmtA->close();
    }
    if (!$app) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Applicant not found."; exit;
    }

    // Load ALL reports for that applicant
    $rows = [];
    $stmt = $conn->prepare("
        SELECT
            ar.note_text   AS report_text,
            ar.created_at  AS report_created_at,
            COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
        FROM applicant_reports ar
        LEFT JOIN admin_users au ON au.id = ar.admin_id
        WHERE ar.applicant_id = ?
        ORDER BY ar.created_at DESC, ar.id DESC
    ");
    if ($stmt) {
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) { while ($row = $res->fetch_assoc()) $rows[] = $row; }
        $stmt->close();
    }

    // Build sheet (same design language)
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Applicant Reports');
    $spreadsheet->getProperties()
        ->setCreator('CSNK Manpower Agency')->setLastModifiedBy('CSNK Manpower Agency')
        ->setTitle('Applicant Reports')->setSubject('Reports')
        ->setDescription('All admin reports for a single applicant')->setCategory('Export');
    $spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

    // Optional logo
    $logoPath = realpath(__DIR__ . '/../resources/img/whychoose.png');
    if ($logoPath && is_readable($logoPath)) {
        $drawing = new Drawing();
        $drawing->setName('CSNK Logo'); $drawing->setDescription('CSNK');
        $drawing->setPath($logoPath); $drawing->setHeight(46);
        $drawing->setCoordinates('D1'); $drawing->setOffsetX(2); $drawing->setOffsetY(2);
        $drawing->setWorksheet($sheet);
    }

    // Title + subtitle
    $title    = 'Applicant Reports';
    $subtitle = fullNameXls($app) . ' — Exported on ' . date('M j, Y') . ' | ' . date('h:i A');
    $sheet->setCellValue('A1', $title);
    $sheet->mergeCells('A1:D1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($ink);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->setCellValue('A2', $subtitle);
    $sheet->mergeCells('A2:D2');
    $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB($muted);
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);

    $sheet->getRowDimension(1)->setRowHeight(48);
    $sheet->getRowDimension(2)->setRowHeight(18);
    $sheet->getRowDimension(3)->setRowHeight(6);

    // Little info block (phone/email/approved)
    $infoRow = 3;
    $sheet->setCellValue("A{$infoRow}", 'Phone: ' . ((string)($app['phone_number'] ?? '—')));
    $sheet->setCellValue("C{$infoRow}", 'Email: ' . ((string)($app['email'] ?? '—')));
    $sheet->mergeCells("A{$infoRow}:B{$infoRow}");
    $sheet->mergeCells("C{$infoRow}:D{$infoRow}");
    $sheet->getStyle("A{$infoRow}:D{$infoRow}")->getFont()->getColor()->setARGB($muted);
    $sheet->getStyle("A{$infoRow}:D{$infoRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension($infoRow)->setRowHeight(16);

    // Table header (single-applicant layout)
    $headerRow = 5; $dataStart = $headerRow + 1;
    $cols = ['A','B','C','D'];
    $headers = ['#','Report / Notes','Reported By','Reported At'];
    foreach ($headers as $i=>$label){ $sheet->setCellValue($cols[$i].$headerRow, $label); }

    $sheet->getStyle("A{$headerRow}:D{$headerRow}")->applyFromArray([
        'font'      => ['bold'=>true, 'color'=>['argb'=>'FF374151']],
        'fill'      => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$headerFill]],
        'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER],
        'borders'   => ['bottom'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['argb'=>$borderLight]]],
    ]);
    $sheet->getRowDimension($headerRow)->setRowHeight(22);

    // Data rows
    $row = $dataStart; $i = 0;
    foreach ($rows as $r) {
        $i++;
        $reportText = (string)($r['report_text'] ?? '');
        $adminName  = (string)($r['admin_name'] ?? '—');
        $reportedAt = (string)($r['report_created_at'] ?? '');

        $sheet->setCellValue("A{$row}", $i);
        $sheet->setCellValue("B{$row}", $reportText !== '' ? $reportText : '—');
        $sheet->getStyle("B{$row}")->getAlignment()->setWrapText(true);

        $sheet->setCellValue("C{$row}", $adminName);

        if ($reportedAt !== '' && ($ts=strtotime($reportedAt))!==false) {
            $sheet->setCellValue("D{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts));
            $sheet->getStyle("D{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
        } else {
            $sheet->setCellValue("D{$row}", $reportedAt ?: '—');
        }

        if ($row % 2 === 0) {
            $sheet->getStyle("A{$row}:D{$row}")
                  ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($zebraFill);
        }
        $sheet->getRowDimension($row)->setRowHeight(20);
        $row++;
    }

    $lastDataRow = max($row-1, $headerRow);

    // Borders / freeze / widths / autofilter
    $sheet->getStyle("A{$headerRow}:D{$lastDataRow}")
          ->getBorders()->getAllBorders()
          ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB($borderLight);

    $sheet->freezePane("A{$dataStart}");
    $sheet->setAutoFilter("A{$headerRow}:D{$headerRow}");

    // Suggested widths to match your design language
    $sheet->getColumnDimension('A')->setWidth(6);
    $sheet->getColumnDimension('B')->setWidth(80);
    $sheet->getColumnDimension('C')->setWidth(28);
    $sheet->getColumnDimension('D')->setWidth(22);

    // Download
    while (ob_get_level() > 0) { ob_end_clean(); }
    $filename = 'applicant_reports_' . $id . '_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output'); exit;
}

/* ======================================================================
 * MODE A: All approved applicants + all reports (your original)
 * ====================================================================*/
$qEsc   = $q !== '' ? addcslashes($q, '%_') : '';
$rows   = [];
$types  = '';
$params = [];
$sql = "
SELECT
  a.id,
  a.first_name, a.middle_name, a.last_name, a.suffix,
  a.email, a.phone_number,
  a.created_at                    AS approved_at,
  ar.note_text                    AS report_text,
  ar.created_at                   AS report_created_at,
  COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS admin_name
FROM applicants a
LEFT JOIN applicant_reports ar ON ar.applicant_id = a.id
LEFT JOIN admin_users     au ON au.id = ar.admin_id
WHERE a.deleted_at IS NULL AND a.status = 'approved'
";
if ($q !== '') {
    $like = '%' . $qEsc . '%';
    $sql .= " AND (
        CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.suffix) LIKE ?
        OR a.email LIKE ?
        OR a.phone_number LIKE ?
        OR ar.note_text LIKE ?
        OR au.full_name LIKE ?
        OR au.username LIKE ?
        OR au.email LIKE ?
    )";
    $params = [$like,$like,$like,$like,$like,$like,$like];
    $types  = 'sssssss';
}
$sql .= " ORDER BY a.created_at DESC, ar.created_at DESC, ar.id DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $refs=[]; foreach ($params as $k=>$v){ $refs[$k]=&$params[$k]; }
        array_unshift($refs, $types);
        call_user_func_array([$stmt,'bind_param'], $refs);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) { while ($row = $res->fetch_assoc()) $rows[] = $row; $res->free(); }
    $stmt->close();
}

/* ------ Build spreadsheet with SAME DESIGN as your file ------ */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Approved + Reports');

$spreadsheet->getProperties()
    ->setCreator('CSNK Manpower Agency')->setLastModifiedBy('CSNK Manpower Agency')
    ->setTitle('Approved Applicants with Reports')->setSubject('Reports')
    ->setDescription('Approved applicants and their admin reports (all entries)')->setCategory('Export');

$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

// Columns/headers
$headerRow=4; $dataStart=$headerRow+1;
$cols=['A','B','C','D','E','F','G','H'];
$headers=['#','Name','Phone','Email','Report','Reported By','Reported At','Date Approved'];
$lastHeaderCol=end($cols);

// Optional logo
$logoPath = realpath(__DIR__ . '/../resources/img/whychoose.png');
if ($logoPath && is_readable($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('CSNK Logo'); $drawing->setDescription('CSNK');
    $drawing->setPath($logoPath); $drawing->setHeight(46);
    $drawing->setCoordinates('H1'); $drawing->setOffsetX(4); $drawing->setOffsetY(2);
    $drawing->setWorksheet($sheet);
}

$title='Approved Applicants + All Reports';
$subtitle='Exported on ' . date('M j, Y') . ' | ' . date('h:i A');
if ($q !== '') { $subtitle .= ' — Filter: '.$q; }

$sheet->setCellValue('B1', $title);
$sheet->mergeCells('B1:H1');
$sheet->getStyle('B1')->getFont()->setBold(true)->setSize(20)->getColor()->setARGB($ink);
$sheet->getStyle('B1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->setCellValue('B2', $subtitle);
$sheet->mergeCells('B2:H2');
$sheet->getStyle('B2')->getFont()->setSize(10)->getColor()->setARGB($muted);
$sheet->getStyle('B2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

$sheet->getRowDimension(1)->setRowHeight(48);
$sheet->getRowDimension(2)->setRowHeight(18);
$sheet->getRowDimension(3)->setRowHeight(6);

// Header cells + style
foreach ($headers as $i=>$label){ $sheet->setCellValue($cols[$i].$headerRow, $label); }
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$headerRow}")->applyFromArray([
    'font'      => ['bold'=>true,'color'=>['argb'=>'FF374151']],
    'fill'      => ['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$headerFill]],
    'alignment' => ['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER],
    'borders'   => ['bottom'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['argb'=>$borderLight]]],
]);
$sheet->getRowDimension($headerRow)->setRowHeight(22);

// Data
$row = $dataStart; $i=0;
foreach ($rows as $r) {
    $i++;
    $name = fullNameXls($r);
    $phone = (string)($r['phone_number'] ?? '');
    $email = (string)($r['email'] ?? '');
    $reportText = (string)($r['report_text'] ?? '');
    $adminName  = (string)($r['admin_name'] ?? '');
    $reportedAt = (string)($r['report_created_at'] ?? '');
    $approvedAt = (string)($r['approved_at'] ?? '');

    $sheet->setCellValueExplicit("A{$row}", (int)$r['id'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    $sheet->setCellValue("B{$row}", $name);
    $sheet->setCellValueExplicit("C{$row}", $phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("D{$row}", $email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValue("E{$row}", $reportText !== '' ? $reportText : '—');
    $sheet->getStyle("E{$row}")->getAlignment()->setWrapText(true);
    $sheet->setCellValue("F{$row}", $adminName !== '' ? $adminName : '—');

    if ($reportedAt !== '' && ($ts=strtotime($reportedAt))!==false) {
        $sheet->setCellValue("G{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts));
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
    } else {
        $sheet->setCellValue("G{$row}", $reportedAt ?: '—');
    }
    if ($approvedAt !== '' && ($ts2=strtotime($approvedAt))!==false) {
        $sheet->setCellValue("H{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts2));
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
    } else {
        $sheet->setCellValue("H{$row}", $approvedAt ?: '—');
    }

    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:H{$row}")
              ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($zebraFill);
    }
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}

$lastDataRow = max($row-1, $headerRow);

// Borders / freeze / autosize (same as your design)
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$lastDataRow}")->getBorders()->getAllBorders()
      ->setBorderStyle(Border::BORDER_HAIR)->getColor()->setARGB($borderLight);
$sheet->freezePane("A{$dataStart}");
$sheet->setAutoFilter("A{$headerRow}:{$lastHeaderCol}{$headerRow}");
foreach ($cols as $c){ $sheet->getColumnDimension($c)->setAutoSize(true); }

// Footer spacer
$footerRow = $lastDataRow + 2;
$sheet->setCellValue("A{$footerRow}", ' ');
$sheet->mergeCells("A{$footerRow}:{$lastHeaderCol}{$footerRow}");

// Download
while (ob_get_level() > 0) { ob_end_clean(); }
$filename = 'approved_with_reports_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output'); exit;
