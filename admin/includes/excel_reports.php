<?php
/**
 * FILE: includes/excel_reports.php
 * PURPOSE: Export Approved Applicants + ALL Admin Reports to Excel (.xlsx)
 * DRIVER: MySQLi
 */

declare(strict_types=1);
if (session_status() !== PHP_SESSION_ACTIVE) { @session_start(); }

/* ------ Composer Autoload ------ */
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
    echo "Composer autoload not found.\n"; exit;
}
require_once $autoload;

/* ------ PhpSpreadsheet ------ */
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

/* ------ Bootstrap DB ------ */
$bootstrapCandidates = [
    __DIR__ . '/bootstrap.php', __DIR__ . '/init.php', __DIR__ . '/config.php', __DIR__ . '/db.php',
    dirname(__DIR__) . '/config.php', dirname(__DIR__) . '/db.php',
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

/* ------ Input / filter ------ */
function clean_str(string $v): string {
    $v = trim($v); $v = str_replace(["\r\n","\r"], "\n", $v);
    return filter_var($v, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
}
$q = isset($_GET['q']) ? clean_str((string)$_GET['q']) : '';
if ($q === '' && !empty($_SESSION['reports_q'])) $q = (string)$_SESSION['reports_q'];

/* ----------------------------------------------------------------------
 * Query: Approved applicants + ALL reports (1 row per report).
 * Applicants with NO reports are still included (LEFT JOIN keeps them).
 * --------------------------------------------------------------------*/
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
$params = []; $types=''; $where='';
if ($q !== '') {
    $qEsc = addcslashes($q, '%_'); 
    $like = '%' . $qEsc . '%';
    $where = " AND (
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
$sql .= $where . " ORDER BY a.created_at DESC, ar.created_at DESC, ar.id DESC";

$rows = [];
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        // bind_param requires references
        $refs=[]; foreach ($params as $k=>$v){ $refs[$k]=&$params[$k]; }
        array_unshift($refs, $types);
        call_user_func_array([$stmt,'bind_param'], $refs);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) $rows[] = $row;
        $res->free();
    }
    $stmt->close();
}

/* ------ Helpers ------ */
function fullNameXls(array $r): string {
    $first = (string)($r['first_name']??''); $middle=(string)($r['middle_name']??'');
    $last  = (string)($r['last_name']??'');  $suffix=(string)($r['suffix']??'');
    $name  = trim(trim($first . ' ' . ($middle?($middle.' '):'') . $last) . ' ' . $suffix);
    return $name !== '' ? $name : '—';
}

/* ------ Build spreadsheet ------ */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Approved + Reports');

$spreadsheet->getProperties()
    ->setCreator('CSNK Manpower Agency')->setLastModifiedBy('CSNK Manpower Agency')
    ->setTitle('Approved Applicants with Reports')->setSubject('Reports')
    ->setDescription('Approved applicants and their admin reports (all entries)')->setCategory('Export');

$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

// Palette
$ink='FF111827'; $muted='FF6B7280'; $headerFill='FFE5E7EB'; $zebraFill='FFF9FAFB'; $borderLight='FFE5E7EB';

// Header rows
$headerRow=4; $dataStart=$headerRow+1;
$cols=['A','B','C','D','E','F','G','H'];
$headers=['#','Name','Phone','Email','Report','Reported By','Reported At','Date Approved'];
$lastHeaderCol=end($cols);

// Logo (optional)
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

// Table headers
foreach ($headers as $i=>$label){ $sheet->setCellValue($cols[$i].$headerRow, $label); }
$sheet->getStyle("A{$headerRow}:{$lastHeaderCol}{$headerRow}")->applyFromArray([
    'font'=>['bold'=>true,'color'=>['argb'=>'FF374151']],
    'fill'=>['fillType'=>Fill::FILL_SOLID, 'startColor'=>['argb'=>$headerFill]],
    'alignment'=>['horizontal'=>Alignment::HORIZONTAL_CENTER, 'vertical'=>Alignment::VERTICAL_CENTER],
    'borders'=>['bottom'=>['borderStyle'=>Border::BORDER_THIN, 'color'=>['argb'=>$borderLight]]],
]);
$sheet->getRowDimension($headerRow)->setRowHeight(22);

// Data
$row = $dataStart;
foreach ($rows as $r) {
    $id   = (int)$r['id'];
    $name = fullNameXls($r);
    $phone = (string)($r['phone_number'] ?? '');
    $email = (string)($r['email'] ?? '');

    $reportText = (string)($r['report_text'] ?? '');
    $adminName  = (string)($r['admin_name'] ?? '');
    $reportedAt = (string)($r['report_created_at'] ?? '');
    $approvedAt = (string)($r['approved_at'] ?? '');

    // A-D: applicant basics
    $sheet->setCellValueExplicit("A{$row}", $id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
    $sheet->setCellValue("B{$row}", $name);
    $sheet->setCellValueExplicit("C{$row}", $phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit("D{$row}", $email, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    // E: Report text
    $sheet->setCellValue("E{$row}", $reportText !== '' ? $reportText : '—');

    // F: Admin name
    $sheet->setCellValue("F{$row}", $adminName !== '' ? $adminName : '—');

    // G: Reported At (Excel date if parsable)
    if ($reportedAt !== '' && ($ts=strtotime($reportedAt))!==false) {
        $sheet->setCellValue("G{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts));
        $sheet->getStyle("G{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
    } else {
        $sheet->setCellValue("G{$row}", $reportedAt ?: '—');
    }

    // H: Date Approved
    if ($approvedAt !== '' && ($ts2=strtotime($approvedAt))!==false) {
        $sheet->setCellValue("H{$row}", \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($ts2));
        $sheet->getStyle("H{$row}")->getNumberFormat()->setFormatCode('mmm d, yyyy hh:mm AM/PM');
    } else {
        $sheet->setCellValue("H{$row}", $approvedAt ?: '—');
    }

    // Zebra
    if ($row % 2 === 0) {
        $sheet->getStyle("A{$row}:H{$row}")
              ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($zebraFill);
    }
    $sheet->getRowDimension($row)->setRowHeight(20);
    $row++;
}

$lastDataRow = max($row-1, $headerRow);

// Borders / freeze / autosize
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
$filename = 'approved_with_reports_' . date('Ymd_His') . '.xlsx';
while (ob_get_level() > 0) { ob_end_clean(); }
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output'); exit;
