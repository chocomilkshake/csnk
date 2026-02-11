<?php
// FILE: pages/reports-print.php
require_once '../includes/header.php'; // loads $database, styles, bootstrap, etc.

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

// Get MySQLi connection (same style as Applicant.php)
if (!isset($database) || !method_exists($database, 'getConnection')) {
    http_response_code(500);
    echo "Database connection not available.";
    exit;
}
$conn = $database->getConnection(); // <-- mysqli

$q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Base SQL
$sql = "
SELECT
    r.id,
    r.applicant_id,
    r.from_status,
    r.to_status,
    r.report_text,
    r.admin_id,
    r.created_at,
    a.first_name,
    a.middle_name,
    a.last_name,
    a.suffix
FROM applicant_status_reports r
LEFT JOIN applicants a ON a.id = r.applicant_id
";

// Build WHERE for optional search (uses ? + bind_param)
$params = [];
$types  = '';
$where  = '';

if ($q !== '') {
    // Escape % and _ for LIKE (literal search), then add wildcards for contains
    $qEsc = addcslashes($q, '%_');
    $like = '%' . $qEsc . '%';

    $where = " WHERE CONCAT_WS(' ', a.first_name, a.middle_name, a.last_name, a.suffix) LIKE ?
               OR r.from_status LIKE ?
               OR r.to_status LIKE ?
               OR r.report_text LIKE ? ";
    $params = [$like, $like, $like, $like];
    $types  = 'ssss';
}

$sql .= $where . " ORDER BY r.created_at DESC, r.id DESC";

// Prepare & execute (mysqli)
$rows = [];
try {
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            // bind_param requires references
            $stmt->bind_param($types, ...$params);
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
    } else {
        // Optional: log prepare error for debugging
        error_log('reports-print prepare failed: ' . $conn->error);
    }
} catch (Throwable $e) {
    error_log('reports-print exception: ' . $e->getMessage());
    $rows = [];
}
?>
<style>
@media print { .no-print { display: none !important; } }
.table-print th, .table-print td { vertical-align: top; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h4 class="mb-0 fw-semibold">Status Change Reports</h4>
    <div class="d-flex gap-2">
        <a href="on-process.php<?php echo $q !== '' ? ('?q=' . urlencode($q)) : ''; ?>" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Back
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<?php if ($q !== ''): ?>
<div class="alert alert-info no-print py-2">
    Filter: <strong><?php echo htmlspecialchars($q, ENT_QUOTES, 'UTF-8'); ?></strong>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-body">
    <table class="table table-bordered table-striped table-print align-middle">
        <thead>
            <tr>
                <th style="width: 90px;">Report ID</th>
                <th>Applicant</th>
                <th style="width: 120px;">From → To</th>
                <th>Report</th>
                <th style="width: 140px;">Admin ID</th>
                <th style="width: 180px;">Changed At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="text-center text-muted py-5">No status change reports found.</td></tr>
            <?php else: ?>
                <?php foreach ($rows as $r):
                    $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? '') . ' ' . ($r['last_name'] ?? '') . ' ' . ($r['suffix'] ?? ''));
                ?>
                    <tr>
                        <td><?php echo (int)$r['id']; ?></td>
                        <td>
                            <div class="fw-semibold"><?php echo htmlspecialchars($fullName ?: '—', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div class="text-muted-small">ID: <?php echo (int)$r['applicant_id']; ?></div>
                        </td>
                        <td>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars((string)$r['from_status'], ENT_QUOTES, 'UTF-8'); ?></span>
                            &nbsp;→&nbsp;
                            <span class="badge bg-primary"><?php echo htmlspecialchars((string)$r['to_status'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </td>
                        <td><?php echo nl2br(htmlspecialchars((string)$r['report_text'], ENT_QUOTES, 'UTF-8')); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['admin_id'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo htmlspecialchars((string)$r['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>