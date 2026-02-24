<?php
/**
 * FILE: admin/pages/reports-print.php
 * PURPOSE: Export "Applicant Status Change Reports" to Excel (.xlsx)
 * DRIVER: MySQLi (robust bootstrap with multiple fallbacks)
 * NOTE: Admin column shows human name (from admin_users) instead of numeric ID.
 */

declare(strict_types=1);

// Optional: prevent notices from breaking downloads if any include prints accidentally
// error_reporting(E_ALL & ~E_NOTICE);

// Start session (for saved filters)
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

/* -------------------------------------------------
 *  Composer Autoload (same pattern as your other exporters)
 * -------------------------------------------------*/
$autoloadCandidates = [
    dirname(__DIR__, 2) . '/vendor/autoload.php',   // project_root/vendor
    dirname(__DIR__) . '/vendor/autoload.php',      // admin/vendor
    __DIR__ . '/../../vendor/autoload.php',         // admin/pages -> project_root/vendor
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
 *  Bootstrap DB (robust resolution to a mysqli connection)
 * -------------------------------------------------*/

/**
 * Try to include your app bootstrap/config files.
 * This supports common placements used across projects.
 */
$bootstrapCandidates = [
    __DIR__ . '/bootstrap.php',
    __DIR__ . '/init.php',
    __DIR__ . '/config.php',
    __DIR__ . '/db.php',
    dirname(__DIR__) . '/config.php', // admin/config.php
    dirname(__DIR__) . '/db.php',     // admin/db.php
    dirname(__DIR__, 2) . '/config.php', // project_root/config.php
    dirname(__DIR__, 2) . '/includes/config.php',
    dirname(__DIR__, 2) . '/includes/db.php',
];
foreach ($bootstrapCandidates as $file) {
    if (is_readable($file)) {
        /** @noinspection PhpIncludeInspection */
        require_once $file;
    }
}

/**
 * If $database is not already set, try to create it from the Database class.
 * This matches the pattern used in excel_reports.php
 */
if (!isset($database) || !is_object($database)) {
    // First, include config to get DB constants (required by Database class)
    if (!defined('DB_HOST')) {
        @include_once dirname(__DIR__) . '/includes/config.php';
    }
    // Then include Database class
    if (!class_exists('Database')) { 
        @include_once dirname(__DIR__) . '/includes/Database.php'; 
    }
    if (class_exists('Database')) { 
        $database = new Database(); 
    }
}

/**
 * Resolve a mysqli connection from various app styles.
 * Returns an object exposing getConnection(): mysqli
 * or throws with a descriptive message.
 */
function resolveDatabaseWrapper(): object {
    // 1) An existing $database (object or mysqli) created by your bootstrap
    if (isset($GLOBALS['database']) && is_object($GLOBALS['database'])) {
        $database = $GLOBALS['database'];

        // If it's already a mysqli instance, wrap it
        if ($database instanceof mysqli) {
            return new class($database) {
                private mysqli $conn;
                public function __construct(mysqli $conn) { $this->conn = $conn; }
                public function getConnection(): mysqli { return $this->conn; }
            };
        }

        // If it has getConnection(), use it
        if (method_exists($database, 'getConnection')) {
            $conn = $database->getConnection();
            if ($conn instanceof mysqli) {
                return new class($conn) {
                    private mysqli $conn;
                    public function __construct(mysqli $conn) { $this->conn = $conn; }
                    public function getConnection(): mysqli { return $this->conn; }
                };
            }
        }
    }

    // 2) A common global $conn (many XAMPP tutorials use this)
    if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
        return new class($GLOBALS['conn']) {
            private mysqli $conn;
            public function __construct(mysqli $conn) { $this->conn = $conn; }
            public function getConnection(): mysqli { return $this->conn; }
        };
    }

    // 3) A Database class we can instantiate
    if (class_exists('Database')) {
        try {
            $dbObj = new Database(); // works for many codebases
            if ($dbObj instanceof mysqli) {
                return new class($dbObj) {
                    private mysqli $conn;
                    public function __construct(mysqli $conn) { $this->conn = $conn; }
                    public function getConnection(): mysqli { return $this->conn; }
                };
            }
            if (method_exists($dbObj, 'getConnection')) {
                $conn = $dbObj->getConnection();
                if ($conn instanceof mysqli) {
                    return new class($conn) {
                        private mysqli $conn;
                        public function __construct(mysqli $conn) { $this->conn = $conn; }
                        public function getConnection(): mysqli { return $this->conn; }
                    };
                }
            }
        } catch (Throwable $e) {
            // fall through to next option
        }
    }

    // 4) DB_* constants
    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASS') && defined('DB_NAME')) {
        $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, defined('DB_PORT') ? (int)DB_PORT : 3306);
        if ($mysqli->connect_errno) {
            throw new RuntimeException('MySQL connect error via constants: ' . $mysqli->connect_error);
        }
        if (defined('DB_CHARSET') && DB_CHARSET) {
            @$mysqli->set_charset(DB_CHARSET);
        } else {
            @$mysqli->set_charset('utf8mb4');
        }
        return new class($mysqli) {
            private mysqli $conn;
            public function __construct(mysqli $conn) { $this->conn = $conn; }
            public function getConnection(): mysqli { return $this->conn; }
        };
    }

    // 5) Nothing worked
    throw new RuntimeException(
        "Could not resolve a database connection.\n".
        "Tried: \$database (object/mysqli), \$conn (mysqli), Database class, DB_* constants.\n".
        "Please ensure your bootstrap defines one of these."
    );
}

/**
 * Get the mysqli connection from $database wrapper.
 * The $database object was created earlier (or obtained from bootstrap).
 */
try {
    if (!isset($database) || !is_object($database)) {
        throw new RuntimeException("Database object not found. Please ensure your bootstrap defines \$database.");
    }
    
    // Check if getConnection() method exists
    if (!method_exists($database, 'getConnection')) {
        throw new RuntimeException("Database wrapper must have getConnection() method.");
    }
    
    $conn = $database->getConnection();
    
    // Verify we got a mysqli connection
    if (!($conn instanceof mysqli)) {
        throw new RuntimeException("getConnection() must return a mysqli instance.");
    }
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Database wrapper with getConnection() is required for export.\n\n"
       . "Details: " . $e->getMessage() . "\n";
    exit;
}

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
 *  NOTE: Join admin_users as au to fetch a readable admin_name
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
    a.first_name, a.middle_name, a.last_name, a.suffix,

    /* Admin name preference: full_name -> username -> email */
    COALESCE(
        NULLIF(au.full_name, ''),
        NULLIF(au.username, ''),
        NULLIF(au.email, '')
    ) AS admin_name
FROM applicant_status_reports r
LEFT JOIN applicants  a  ON a.id  = r.applicant_id
LEFT JOIN admin_users au ON au.id = r.admin_id
WHERE 1 = 1
";

$where  = '';
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
        OR r.to_status   LIKE ?
        OR r.report_text LIKE ?
        OR au.full_name  LIKE ?
        OR au.username   LIKE ?
        OR au.email      LIKE ?
      )
    ";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sssssss';
}
if ($fromDate instanceof DateTimeInterface) {
    $where   .= " AND r.created_at >= ? ";
    $params[] = $fromDate->format('Y-m-d 00:00:00');
    $types   .= 's';
}
if ($toDate instanceof DateTimeInterface) {
    $where   .= " AND r.created_at <= ? ";
    $params[] = $toDate->format('Y-m-d 23:59:59');
    $types   .= 's';
}

$sql .= $where . " ORDER BY r.created_at DESC, r.id DESC ";

// Execute (MySQLi) with safe bind-by-reference
$rows = [];
try {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    if (!empty($params)) {
        // mysqli::bind_param requires references
        $bind = [];
        $bind[0] = $types; // type string
        foreach ($params as $i => $value) {
            $bind[$i + 1] = &$params[$i];
        }
        if (!call_user_func_array([$stmt, 'bind_param'], $bind)) {
            throw new RuntimeException('bind_param failed: ' . $stmt->error);
        }
    }

    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }

    $res = $stmt->get_result();
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $res->free();
    }
    $stmt->close();
} catch (Throwable $e) {
    error_log('reports-print query failed: ' . $e->getMessage());
    $rows = [];
}

/* -------------------------------------------------
 *  Build spreadsheet
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
$headers = ['#', 'Applicant ID', 'Applicant', 'From Status', 'To Status', 'Report', 'Admin', 'Changed At'];
$lastHeaderCol = end($cols);

// Title & subtitle rows
$headerRow  = 4;
$dataStart  = $headerRow + 1;

// Optional logo (mirrors your other exporter path)
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

    // G: Admin name
    $adminName = trim((string)($r['admin_name'] ?? ''));
    if ($adminName === '') { $adminName = '—'; }
    $sheet->setCellValue("G{$row}", $adminName);

    // H: Created at -> Excel serial date if parsable
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

// Send download
$filename = 'status_change_reports_' . date('Ymd_His') . '.xlsx';

// Clean any previous output buffers (prevents corrupt xlsx)
while (ob_get_level() > 0) { @ob_end_clean(); }

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;