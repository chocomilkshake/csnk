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
$status = isset($_GET['status']) ? strtolower(trim((string)$_GET['status'])) : 'all';
$agencyFilter = isset($_GET['agency']) ? strtolower(trim((string)$_GET['agency'])) : 'all';
$countryFilter = isset($_GET['country']) ? max(0, (int)$_GET['country']) : 0;
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
$whereStatus = "a.deleted_at IS NULL AND (
    (SELECT COUNT(*) FROM applicant_reports r2 WHERE r2.applicant_id = a.id) > 0
    OR (SELECT COUNT(*) FROM applicant_status_reports s2 WHERE s2.applicant_id = a.id) > 0
)";

$params = [];
$types = '';

$allowedStatuses = ['all', 'pending', 'on_process', 'approved', 'on_hold', 'deleted'];
if (!in_array($status, $allowedStatuses, true)) $status = 'all';

if ($status !== 'all') {
  $whereStatus .= " AND a.status = ?";
  $params[] = $status;
  $types .= 's';
}

// Agency filter (match reports.php logic)
$currentAgency = isset($_SESSION['agency']) ? $_SESSION['agency'] : null;
$isAdminView = isset($_SESSION['admin_role']) && ($_SESSION['admin_role'] === 'admin' || $_SESSION['admin_role'] === 'super');
if (!$isAdminView && $currentAgency) {
  $whereStatus .= " AND ag.code = ?";
  $params[] = $currentAgency;
  $types .= 's';
} elseif ($isAdminView && $agencyFilter !== 'all') {
  $whereStatus .= " AND ag.code = ?";
  $params[] = $agencyFilter;
  $types .= 's';
}

// Country filter (SMC only)
if ($countryFilter > 0 && $agencyFilter === 'smc') {
  $whereStatus .= " AND bu.country_id = ?";
  $params[] = $countryFilter;
  $types .= 'i';
}

$activityExpr = "GREATEST(
    COALESCE(lr.created_at,'0000-00-00 00:00:00'),
    COALESCE(lsr.created_at,'0000-00-00 00:00:00')
)";
$orderSql = " ORDER BY {$activityExpr} DESC, a.id DESC";

$sql = "
SELECT
  a.*,
  ag.code AS agency_code,
  lr.note_text         AS latest_note,
  lr.created_at        AS latest_note_at,
  COALESCE(NULLIF(au.full_name,''), NULLIF(au.username,''), NULLIF(au.email,'')) AS latest_note_admin,
  lsr.from_status      AS last_from_status,
  lsr.to_status        AS last_to_status,
  lsr.created_at       AS last_status_at,
  COALESCE(NULLIF(au2.full_name,''), NULLIF(au2.username,''), NULLIF(au2.email,'')) AS last_status_admin,
  (SELECT COUNT(*) FROM applicant_reports r2 WHERE r2.applicant_id = a.id) AS report_count
FROM applicants a
LEFT JOIN business_units bu ON bu.id = a.business_unit_id
LEFT JOIN agencies ag ON ag.id = bu.agency_id
/* latest note */
LEFT JOIN (
  SELECT ar1.*
  FROM applicant_reports ar1
  INNER JOIN (
    SELECT applicant_id, MAX(id) AS max_id
    FROM applicant_reports
    GROUP BY applicant_id
  ) t ON t.applicant_id = ar1.applicant_id AND t.max_id = ar1.id
) lr ON lr.applicant_id = a.id
LEFT JOIN admin_users au ON au.id = lr.admin_id
/* latest status change */
LEFT JOIN (
  SELECT asr1.*
  FROM applicant_status_reports asr1
  INNER JOIN (
    SELECT applicant_id, MAX(id) AS max_sid
    FROM applicant_status_reports
    GROUP BY applicant_id
  ) ts ON ts.applicant_id = asr1.applicant_id AND ts.max_sid = asr1.id
) lsr ON lsr.applicant_id = a.id
LEFT JOIN admin_users au2 ON au2.id = lsr.admin_id
WHERE {$whereStatus}
{$orderSql}
";

$qEsc   = $q !== '' ? addcslashes($q, '%_') : '';
$rows   = [];
if ($q !== '') {
  $like = '%' . $qEsc . '%';
  $sql .= " HAVING (
    CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.suffix) LIKE ?
    OR a.email LIKE ?
    OR a.phone_number LIKE ?
    OR lr.note_text LIKE ?
    OR au.full_name LIKE ?
    OR au.username LIKE ?
    OR au.email LIKE ?
    OR au2.full_name LIKE ?
    OR au2.username LIKE ?
    OR au2.email LIKE ?
  )";
  $params = array_merge($params, [$like,$like,$like,$like,$like,$like,$like,$like,$like,$like]);
  $types .= str_repeat('s', 10);
}

try {
  if ($types !== '') {
    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param($types, ...$params);
      $stmt->execute();
      $res = $stmt->get_result();
      $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
      $stmt->close();
    }
  } else {
    if ($res = $conn->query($sql)) {
      $rows = $res->fetch_all(MYSQLI_ASSOC);
    }
  }
} catch (Throwable $e) {
  $rows = [];
}

// Post-process to match reports.php (latest activity)
foreach ($rows as &$r) {
  $noteAt = $r['latest_note_at'] ?? '';
  $statusAt = $r['last_status_at'] ?? '';
  if ($statusAt !== '' && $statusAt > $noteAt) {
    $r['latest_activity_at'] = $statusAt;
    $r['latest_activity_text'] = 'Status: ' . str_replace('_', ' ', $r['last_from_status'] ?? '') 
                                . ' → ' . str_replace('_', ' ', $r['last_to_status'] ?? '');
    $r['latest_activity_admin'] = $r['last_status_admin'] ?? '';
  } else {
    $r['latest_activity_at'] = $noteAt;
    $r['latest_activity_text'] = $r['latest_note'] ?? '';
    $r['latest_activity_admin'] = $r['latest_note_admin'] ?? '';
  }
}
unset($r);

/* ------ Build spreadsheet with SAME DESIGN as your file ------ */
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Activity Reports');

$spreadsheet->getProperties()
    ->setCreator('CSNK Manpower Agency')->setLastModifiedBy('CSNK Manpower Agency')
    ->setTitle('Activity Reports')->setSubject('Reports')
    ->setDescription('Filtered applicants with recent activity/reports/status changes')->setCategory('Export');

$spreadsheet->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

// Columns/headers (9 cols)
$headerRow=4; $dataStart=$headerRow+1;
$cols=['A','B','C','D','E','F','G','H','I'];
$headers=['#','Name','Phone','Email','Status','Latest Activity','Latest By','Latest At','Reports'];
$lastHeaderCol='I';

// Optional logo
$logoPath = realpath(__DIR__ . '/../resources/img/whychoose.png');
if ($logoPath && is_readable($logoPath)) {
    $drawing = new Drawing();
    $drawing->setName('CSNK Logo'); $drawing->setDescription('CSNK');
    $drawing->setPath($logoPath); $drawing->setHeight(46);
    $drawing->setCoordinates('H1'); $drawing->setOffsetX(4); $drawing->setOffsetY(2);
    $drawing->setWorksheet($sheet);
}

$title = 'Activity Reports (Status: ' . ucfirst(str_replace('_', ' ', $status)) . ', Agency: ' . ucfirst($agencyFilter) . ')';
$subtitle = 'Exported on ' . date('M j, Y') . ' | ' . date('h:i A');
if ($q !== '') $subtitle .= ' — Search: ' . $q;
if ($countryFilter > 0) $subtitle .= ' — Country ID: ' . $countryFilter;

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
$sheet->getColumnDimension('F')->setWidth(50);  // Extra for Latest Activity wrap

// Footer spacer
$footerRow = $lastDataRow + 2;
$sheet->setCellValue("A{$footerRow}", ' ');
$sheet->mergeCells("A{$footerRow}:{$lastHeaderCol}{$footerRow}");

// Download
while (ob_get_level() > 0) { ob_end_clean(); }
$filename = 'activity_reports_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="'.$filename.'"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output'); exit;
